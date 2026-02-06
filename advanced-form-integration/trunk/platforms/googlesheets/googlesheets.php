<?php

class ADFOIN_GoogleSheets extends Advanced_Form_Integration_OAuth2 {

    const service_name           = 'googlesheets';
    const authorization_endpoint = 'https://accounts.google.com/o/oauth2/auth';
    const token_endpoint         = 'https://www.googleapis.com/oauth2/v3/token';

    private static $instance;
    protected $client_id          = '';
    protected $client_secret      = '';
    protected $google_access_code = '';
    protected $sheet_lists        = array();
    protected $token_expires      = 0;
    protected $cred_id            = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $this->token_endpoint         = self::token_endpoint;
        $this->authorization_endpoint = self::authorization_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'adfoin_googlesheets_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'adfoin_googlesheets_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'adfoin_googlesheets_settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_spreadsheet_list', array( $this, 'get_spreadsheet_list' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_googlesheets_get_worksheets', array( $this, 'get_worksheets' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_googlesheets_get_headers', array( $this, 'get_headers' ), 10, 0 );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );
        add_action( 'wp_ajax_adfoin_get_googlesheets_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_googlesheets_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_googlesheets_keys' ) );

        if ( isset( $option['client_id'] ) ) {
            $this->client_id = $option['client_id'];
        }
        if ( isset( $option['client_secret'] ) ) {
            $this->client_secret = $option['client_secret'];
        }
        if ( isset( $option['access_token'] ) ) {
            $this->access_token = $option['access_token'];
        }
        if ( isset( $option['refresh_token'] ) ) {
            $this->refresh_token = $option['refresh_token'];
        }
        if ( isset( $option['token_expires'] ) ) {
            $this->token_expires = $option['token_expires'];
        }
        if ( $this->is_active() && isset( $option['sheet_lists'] ) ) {
            $this->sheet_lists = $option['sheet_lists'];
        }
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/googlesheets',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_webhook_data' ),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        if ( $code ) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                $this->cred_id = $state;
                $credentials = adfoin_read_credentials( 'googlesheets' );
                
                foreach ( $credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                        $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
                    }
                }

                $response = $this->request_token( $code );
                
                $success = false;
                $message = 'Unknown error';
                
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['access_token'] ) ) {
                        $success = true;
                        $message = 'Connected successfully!';
                    } else {
                        $message = isset( $body['error'] ) ? $body['error'] : 'Token exchange failed.';
                    }
                } else {
                    $message = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error';
                }

                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
                exit;
            }
            
            // Legacy flow - redirect to admin
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_googlesheets_auth_redirect',
                    'code'    => $code,
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( trim( $_GET['action'] ) ) : '';

        if ( 'adfoin_googlesheets_auth_redirect' == $action ) {
            $code  = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
            $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

            if ( $code ) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->cred_id = $state;
                    $credentials = adfoin_read_credentials( 'googlesheets' );
                    
                    foreach ( $credentials as $value ) {
                        if ( $value['id'] == $state ) {
                            $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                            $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
                        }
                    }
                }
                
                $this->request_token( $code );
                
                // For popup flow
                if ( $state ) {
                    require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                    ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
                    exit;
                }
            }

            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=googlesheets' ) );
            exit();
        }
    }

    public function adfoin_googlesheets_actions( $actions ) {
        $actions['googlesheets'] = array(
            'title' => __( 'Google Sheets', 'advanced-form-integration' ),
            'tasks' => array(
                'add_row' => __( 'Add New Row', 'advanced-form-integration' )
            )
        );
        return $actions;
    }

    public function adfoin_googlesheets_settings_tab( $providers ) {
        $providers['googlesheets'] = __( 'Google Sheets', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_googlesheets_settings_view( $current_tab ) {
        if ( $current_tab != 'googlesheets' ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'Client ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Client Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
        );

        // Instructions
        $domain = parse_url( get_site_url() );
        $host   = $domain['host'];

        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s and create a New Project.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://console.cloud.google.com/cloud-resource-manager/">Google Developer Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Go to Library and search for Google Sheets API, open it and click ENABLE.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Again search for Google Drive API, open it and click ENABLE.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . sprintf( __( 'Go to OAuth consent screen, select External, click Create. Enter %s in Authorized domains. Set publishing status to in production.', 'advanced-form-integration' ), '<code>' . esc_html( $host ) . '</code>' ) . '</li>';
        $instructions .= '<li>' . __( 'Go to Credentials, click CREATE CREDENTIALS, select OAuth client ID, select Web application.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Authorized redirect URIs:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Google Sheets', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'googlesheets', 'Google Sheets', $fields, $instructions, $config );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $all_credentials = adfoin_read_credentials( 'googlesheets' );
        wp_send_json_success( $all_credentials );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'googlesheets' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_googlesheets_keys' ) );

            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && ! empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
                    'token_expires' => isset( $option['token_expires'] ) ? $option['token_expires'] : 0,
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'googlesheets';
        $credentials = adfoin_read_credentials( $platform );
        
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && strpos( $credentials[ $index ]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_googlesheets_keys' );
                }
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( 'Invalid index' );
        }

        // Handle Save/Update
        $id            = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '';

        if ( empty( $id ) ) {
            $id = uniqid();
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
            'token_expires' => 0,
        );

        // Check if updating existing credential
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials haven't changed
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id &&
                     isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['token_expires'] = isset( $cred['token_expires'] ) ? $cred['token_expires'] : 0;
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Generate Auth URL
        $scope    = 'https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/drive.readonly';
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $scope,
                'state'         => $id,
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    protected function request_token( $authorization_code ) {
        $args = array(
            'headers' => array(),
            'body'    => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
            )
        );

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        }

        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        // Cache token expiry
        if ( isset( $response_body['expires_in'] ) ) {
            $this->token_expires = time() + (int) $response_body['expires_in'];
        } else {
            $this->token_expires = time() + 3600;
        }

        $this->save_data();

        return $response;
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="googlesheets-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'add_row'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Google Sheets Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getSpreadsheets">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <?php $this->get_credentials_list(); ?>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=googlesheets' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Spreadsheet', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[spreadsheetId]" v-model="fielddata.spreadsheetId" @change="getWorksheets" required="required">
                            <option value=""><?php _e( 'Select Spreadsheet...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in fielddata.spreadsheetList" :value="index">{{item}}</option>
                        </select>
                        <span @click="getSpreadsheets" class="afi-refresh-button dashicons dashicons-update"></span>
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:5px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Worksheet', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[worksheetId]" v-model="fielddata.worksheetId" @change="getHeaders" required="required">
                            <option value=""><?php _e( 'Select Worksheet...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in fielddata.worksheetList" :value="index">{{item}}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': worksheetLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:5px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <input type="hidden" name="fieldData[worksheetName]" :value="fielddata.worksheetName" />
                <input type="hidden" name="fieldData[worksheetList]" :value="JSON.stringify( fielddata.worksheetList )" />
            </table>
        </script>
        <?php
    }

    protected function save_data() {
        // If using new credential system
        if ( $this->cred_id ) {
            $credentials = adfoin_read_credentials( 'googlesheets' );

            foreach ( $credentials as &$value ) {
                if ( $value['id'] == $this->cred_id ) {
                    if ( $this->access_token ) {
                        $value['access_token'] = $this->access_token;
                    }
                    if ( $this->refresh_token ) {
                        $value['refresh_token'] = $this->refresh_token;
                    }
                    if ( $this->token_expires ) {
                        $value['token_expires'] = $this->token_expires;
                    }
                }
            }

            adfoin_save_credentials( 'googlesheets', $credentials );
            return;
        }

        // Legacy save for backward compatibility
        $data = (array) maybe_unserialize( get_option( 'adfoin_googlesheets_keys' ) );

        $option = array_merge(
            $data,
            array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'access_token'  => $this->access_token,
                'refresh_token' => $this->refresh_token,
                'sheet_lists'   => $this->sheet_lists,
                'token_expires' => $this->token_expires,
            )
        );

        update_option( 'adfoin_googlesheets_keys', maybe_serialize( $option ) );
    }

    protected function reset_data() {
        $this->client_id          = '';
        $this->client_secret      = '';
        $this->google_access_code = '';
        $this->access_token       = '';
        $this->refresh_token      = '';
        $this->token_expires      = 0;
        $this->sheet_lists        = array();

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/googlesheets' );
    }

    /**
     * Set credentials from credential ID
     */
    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );

        if ( empty( $credentials ) ) {
            return;
        }

        $this->client_id     = isset( $credentials['client_id'] ) ? $credentials['client_id'] : '';
        $this->client_secret = isset( $credentials['client_secret'] ) ? $credentials['client_secret'] : '';
        $this->access_token  = isset( $credentials['access_token'] ) ? $credentials['access_token'] : '';
        $this->refresh_token = isset( $credentials['refresh_token'] ) ? $credentials['refresh_token'] : '';
        $this->token_expires = isset( $credentials['token_expires'] ) ? $credentials['token_expires'] : 0;
        $this->cred_id       = $credentials['id'];
    }

    /**
     * Get credentials list for dropdown
     */
    public function get_credentials_list() {
        $html        = '';
        $credentials = adfoin_read_credentials( 'googlesheets' );

        foreach ( $credentials as $option ) {
            $html .= '<option value="' . esc_attr( $option['id'] ) . '">' . esc_html( $option['title'] ) . '</option>';
        }

        echo $html;
    }

    /**
     * Get credentials by ID
     */
    public function get_credentials_by_id( $cred_id ) {
        $credentials     = array();
        $all_credentials = adfoin_read_credentials( 'googlesheets' );

        if ( is_array( $all_credentials ) && ! empty( $all_credentials ) ) {
            // Default to first credential
            $credentials = $all_credentials[0];

            foreach ( $all_credentials as $single ) {
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                    break;
                }
            }
        }

        return $credentials;
    }

    public function get_spreadsheet_list() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
        
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $endpoint = "https://www.googleapis.com/drive/v3/files?q=mimeType%20%3D%20'application%2Fvnd.google-apps.spreadsheet'&pageSize=1000";

        if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
            $endpoint .= "&supportsAllDrives=true&includeItemsFromAllDrives=true";
        }

        $request = array(
            'method'  => 'GET',
            'headers' => array(),
        );

        $response      = $this->remote_request( $endpoint, $request );
        $response_body = wp_remote_retrieve_body( $response );

        if ( empty( $response_body ) ) {
            wp_send_json_error( __( 'Empty response from Google API', 'advanced-form-integration' ) );
            return;
        }

        $body = json_decode( $response_body, true );

        if ( isset( $body['error'] ) ) {
            wp_send_json_error( isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'advanced-form-integration' ) );
            return;
        }

        if ( ! isset( $body['files'] ) || ! is_array( $body['files'] ) ) {
            wp_send_json_error( __( 'No spreadsheets found', 'advanced-form-integration' ) );
            return;
        }

        $spreadsheet_list          = $body['files'];
        $spreadsheets_id_and_title = wp_list_pluck( $spreadsheet_list, 'name', 'id' );

        wp_send_json_success( $spreadsheets_id_and_title );
    }

    public function get_worksheets() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $spreadsheet_id = isset( $_POST['spreadsheetId'] ) ? sanitize_text_field( $_POST['spreadsheetId'] ) : '';
        $cred_id        = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        if ( ! $spreadsheet_id ) {
            wp_send_json_error( __( 'Spreadsheet ID is required', 'advanced-form-integration' ) );
            return;
        }

        $endpoint = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/";

        $request = array(
            'method'  => 'GET',
            'headers' => array(),
        );

        $response = $this->remote_request( $endpoint, $request );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            wp_send_json_error( isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'advanced-form-integration' ) );
            return;
        }

        if ( ! isset( $body['sheets'] ) || ! is_array( $body['sheets'] ) ) {
            wp_send_json_error( __( 'No worksheets found', 'advanced-form-integration' ) );
            return;
        }

        $sheets = array();

        foreach ( $body['sheets'] as $value ) {
            if ( isset( $value['properties']['sheetId'] ) && isset( $value['properties']['title'] ) ) {
                $sheets[ $value['properties']['sheetId'] ] = $value['properties']['title'];
            }
        }

        if ( empty( $sheets ) ) {
            wp_send_json_error( __( 'No worksheets found', 'advanced-form-integration' ) );
        } else {
            wp_send_json_success( $sheets );
        }
    }

    public function get_headers() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $spreadsheet_id = isset( $_REQUEST['spreadsheetId'] ) ? sanitize_text_field( $_REQUEST['spreadsheetId'] ) : '';
        $worksheet_name = isset( $_REQUEST['worksheetName'] ) ? sanitize_text_field( $_REQUEST['worksheetName'] ) : '';
        $cred_id        = isset( $_REQUEST['credId'] ) ? sanitize_text_field( $_REQUEST['credId'] ) : '';

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        if ( ! $spreadsheet_id || ! $worksheet_name ) {
            wp_send_json_error( __( 'Spreadsheet ID and Worksheet name are required', 'advanced-form-integration' ) );
            return;
        }

        $worksheet_name_encoded = rawurlencode( $worksheet_name );
        $endpoint               = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_name_encoded}!A1:ZZ1";

        $request = array(
            'method'  => 'GET',
            'headers' => array(),
        );

        $response      = $this->remote_request( $endpoint, $request );
        $response_code = wp_remote_retrieve_response_code( $response );

        if ( $response_code != 200 ) {
            $body          = json_decode( wp_remote_retrieve_body( $response ), true );
            $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Failed to fetch headers', 'advanced-form-integration' );
            wp_send_json_error( $error_message );
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['values'] ) || ! is_array( $body['values'] ) || empty( $body['values'][0] ) ) {
            wp_send_json_error( __( 'No headers found in the first row', 'advanced-form-integration' ) );
            return;
        }

        $combined = array();
        $key      = 'A';

        if ( is_array( $body['values'][0] ) ) {
            foreach ( $body['values'][0] as $value ) {
                $combined[ $key ] = $value;
                $key++;

                if ( $key == 'ZZ' ) {
                    break;
                }
            }
        }

        wp_send_json_success( $combined );
    }

    protected function remote_request( $url, $request = array(), $record = array(), $retry_count = 0 ) {
        $max_retries = 2;

        if ( ! $this->check_token_expiry( $this->access_token ) ) {
            $this->refresh_token();
        }

        $request['headers'] = array(
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
        );

        $request['timeout'] = 30;

        $response      = wp_remote_request( esc_url_raw( $url ), $request );
        $response_code = wp_remote_retrieve_response_code( $response );

        // Handle 401 Unauthorized with retry limit
        if ( 401 === $response_code && $retry_count < $max_retries ) {
            $this->refresh_token();
            return $this->remote_request( $url, $request, $record, $retry_count + 1 );
        }

        // Handle 429 Rate Limiting with retry limit
        if ( 429 === $response_code && $retry_count < $max_retries ) {
            $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
            $sleep_time  = $retry_after ? min( (int) $retry_after, 10 ) : 5;
            sleep( $sleep_time );
            return $this->remote_request( $url, $request, $record, $retry_count + 1 );
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }

    public function check_token_expiry( $token = '' ) {
        if ( empty( $token ) ) {
            return false;
        }

        // Use cached expiry time if available (with 60 second buffer)
        if ( $this->token_expires > 0 ) {
            return time() < ( $this->token_expires - 60 );
        }

        // Fallback to API check for backward compatibility
        $return = wp_remote_get( 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $token );

        if ( is_wp_error( $return ) ) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $return );

        if ( $response_code == 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $return ), true );
            if ( isset( $body['expires_in'] ) ) {
                $this->token_expires = time() + (int) $body['expires_in'];
                $this->save_data();
            }
            return true;
        }

        return false;
    }

    protected function refresh_token() {
        $args = array(
            'headers' => array(),
            'body'    => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
            ),
        );

        $response      = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        }

        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        // Cache token expiry
        if ( isset( $response_body['expires_in'] ) ) {
            $this->token_expires = time() + (int) $response_body['expires_in'];
        } else {
            $this->token_expires = time() + 3600;
        }

        $this->save_data();

        return $response;
    }

    public function append_new_row( $record, $spreadsheet_id = '', $worksheet_name = '', $data_array = array() ) {
        if ( empty( $worksheet_name ) || empty( $data_array ) ) {
            return 'worksheet_name or data_array is empty';
        }

        $final = array();

        foreach ( $data_array as $key => $val ) {
            $final[] = $val ? $val : '';
        }

        $last_key               = key( array_slice( $data_array, -1, 1, true ) );
        $worksheet_name_encoded = rawurlencode( $worksheet_name );

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_name_encoded}!A:{$last_key}:append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS";

        $args = array(
            'method' => 'POST',
            'body'   => wp_json_encode(
                array(
                    'range'          => $worksheet_name . '!A:' . $last_key,
                    'majorDimension' => 'ROWS',
                    'values'         => array( $final ),
                )
            ),
        );

        $return = $this->remote_request( $url, $args, $record );

        return $return;
    }
}

$googlesheets = ADFOIN_GoogleSheets::get_instance();

/*
 * Saves connection mapping
 */
function adfoin_googlesheets_save_integration() {
    $params = array();
    parse_str( adfoin_sanitize_text_or_array_field( $_POST['formData'] ), $params );

    $trigger_data = isset( $_POST['triggerData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['triggerData'] ) : array();
    $action_data  = isset( $_POST['actionData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['actionData'] ) : array();
    $field_data   = isset( $_POST['fieldData'] ) ? adfoin_sanitize_text_or_array_field( $_POST['fieldData'] ) : array();

    $integration_title = isset( $trigger_data['integrationTitle'] ) ? $trigger_data['integrationTitle'] : '';
    $form_provider_id  = isset( $trigger_data['formProviderId'] ) ? $trigger_data['formProviderId'] : '';
    $form_id           = isset( $trigger_data['formId'] ) ? $trigger_data['formId'] : '';
    $form_name         = isset( $trigger_data['formName'] ) ? $trigger_data['formName'] : '';
    $action_provider   = isset( $action_data['actionProviderId'] ) ? $action_data['actionProviderId'] : '';
    $task              = isset( $action_data['task'] ) ? $action_data['task'] : '';
    $type              = isset( $params['type'] ) ? $params['type'] : '';

    $all_data = array(
        'trigger_data' => $trigger_data,
        'action_data'  => $action_data,
        'field_data'   => $field_data,
    );

    global $wpdb;

    $integration_table = $wpdb->prefix . 'adfoin_integration';

    if ( $type == 'new_integration' ) {
        $result = $wpdb->insert(
            $integration_table,
            array(
                'title'           => $integration_title,
                'form_provider'   => $form_provider_id,
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'action_provider' => $action_provider,
                'task'            => $task,
                'data'            => json_encode( $all_data, true ),
                'status'          => 1,
            )
        );
    }

    if ( $type == 'update_integration' ) {
        $id = esc_sql( trim( $params['edit_id'] ) );

        if ( $type != 'update_integration' && ! empty( $id ) ) {
            return;
        }

        $result = $wpdb->update(
            $integration_table,
            array(
                'title'         => $integration_title,
                'form_provider' => $form_provider_id,
                'form_id'       => $form_id,
                'form_name'     => $form_name,
                'data'          => json_encode( $all_data, true ),
            ),
            array(
                'id' => $id,
            )
        );
    }

    if ( $result ) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_googlesheets_job_queue', 'adfoin_googlesheets_job_queue', 10, 1 );

function adfoin_googlesheets_job_queue( $data ) {
    adfoin_googlesheets_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Google Sheets API
 */
function adfoin_googlesheets_send_data( $record, $submitted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $submitted_data ) ) {
                return;
            }
        }
    }

    $all_data = apply_filters(
        'afi_googlesheets_before_process',
        array(
            'submitted_data' => $submitted_data,
            'record_data'    => $record_data,
        )
    );

    $posted_data    = $all_data['submitted_data'];
    $data           = $all_data['record_data']['field_data'];
    $spreadsheet_id = isset( $data['spreadsheetId'] ) ? $data['spreadsheetId'] : '';
    $worksheet_name = isset( $data['worksheetName'] ) ? $data['worksheetName'] : '';
    $cred_id        = isset( $data['credId'] ) ? $data['credId'] : '';
    $task           = $record['task'];

    if ( $task == 'add_row' ) {
        unset( $data['spreadsheetId'] );
        unset( $data['spreadsheetList'] );
        unset( $data['worksheetId'] );
        unset( $data['worksheetList'] );
        unset( $data['worksheetName'] );
        unset( $data['credId'] );

        $holder       = array();
        $googlesheets = ADFOIN_GoogleSheets::get_instance();

        // Set credentials if provided
        if ( $cred_id ) {
            $googlesheets->set_credentials( $cred_id );
        }

        if ( empty( $data ) ) {
            $key = 'A';

            if ( is_array( $posted_data ) ) {
                $posted_data = array_filter( $posted_data );

                foreach ( $posted_data as $value ) {
                    $holder[ $key ] = $value;
                    $key++;

                    if ( $key == 'ZZ' ) {
                        break;
                    }
                }
            }
        } else {
            foreach ( $data as $key => $value ) {
                $holder[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        $googlesheets->append_new_row( $record, $spreadsheet_id, $worksheet_name, $holder );
    }

    return;
}
