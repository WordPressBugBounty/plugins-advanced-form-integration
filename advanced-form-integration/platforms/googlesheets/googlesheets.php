<?php

class ADFOIN_GoogleSheets extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'googlesheets';

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
        add_action( 'wp_ajax_adfoin_test_googlesheets_connection', array( $this, 'test_connection' ), 10, 0 );
    }

    /**
     * Verify an account's tokens by hitting a cheap authenticated Google
     * endpoint. If the access token is expired (or absent) but a refresh
     * token is present, remote_request() refreshes it transparently — so
     * pressing "Test Connection" on a stale account also fixes it.
     */
    public function test_connection() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( '' === $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id', 'advanced-form-integration' ) ) );
        }

        $this->set_credentials( $cred_id );

        // Never authorized at all.
        if ( empty( $this->access_token ) && empty( $this->refresh_token ) ) {
            wp_send_json_error( array(
                'message' => __( 'Account not authorized. Click "Authorize" to connect this Google account first.', 'advanced-form-integration' ),
            ) );
        }

        // If the access token is missing but we still have a refresh token,
        // refresh proactively so the verification below has something to test.
        if ( empty( $this->access_token ) && ! empty( $this->refresh_token ) ) {
            $refresh_result = $this->refresh_token();
            if ( is_wp_error( $refresh_result ) || empty( $this->access_token ) ) {
                $msg = is_wp_error( $refresh_result )
                    ? $refresh_result->get_error_message()
                    : __( 'Token refresh failed. Please re-authorize the account.', 'advanced-form-integration' );
                wp_send_json_error( array( 'message' => $msg ) );
            }
        }

        // Cheap "who am I" call against the Drive API. remote_request()
        // auto-refreshes on 401 and on detected expiry, so an expired
        // access token gets repaired here transparently.
        $endpoint = 'https://www.googleapis.com/drive/v3/about?fields=user(emailAddress,displayName)';
        $response = $this->remote_request( $endpoint, array( 'method' => 'GET' ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 200 && $code < 300 ) {
            $email = isset( $body['user']['emailAddress'] ) ? $body['user']['emailAddress'] : '';
            $name  = isset( $body['user']['displayName'] ) ? $body['user']['displayName'] : '';

            $message = __( 'Connection OK', 'advanced-form-integration' );
            if ( $email ) {
                $message = $name
                    ? sprintf( __( 'Connected as %1$s (%2$s)', 'advanced-form-integration' ), $name, $email )
                    : sprintf( __( 'Connected as %s', 'advanced-form-integration' ), $email );
            }

            wp_send_json_success( array(
                'message' => $message,
                'email'   => $email,
                'name'    => $name,
            ) );
        }

        // Surface the most informative error message Google returned.
        $msg = isset( $body['error']['message'] ) ? $body['error']['message']
            : ( isset( $body['error_description'] ) ? $body['error_description']
            : ( isset( $body['error'] ) ? ( is_string( $body['error'] ) ? $body['error'] : wp_json_encode( $body['error'] ) )
            : sprintf( 'HTTP %d', $code ) ) );

        wp_send_json_error( array( 'message' => $msg ) );
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
        $context = self::consume_oauth_state( $state, 'googlesheets' );
        $state   = $context ? $context['cred_id'] : '';

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
            // admin_init fires for every logged-in user; only an admin should
            // be able to complete this OAuth flow (CWE-862).
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
            $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
            $context = self::consume_oauth_state( $state, 'googlesheets' );
            $state   = $context ? $context['cred_id'] : '';

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
            'enable_test' => true,
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
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
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
        adfoin_require_manage_options();
        $nonce = isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'googlesheets';
        $credentials = adfoin_read_credentials( $platform );

        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && strpos( $credentials[ $index ]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_googlesheets_keys' );
                }
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        // Handle Save/Update
        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
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
                'state'         => self::issue_oauth_state( 'googlesheets', $id ),
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    protected function request_token( $authorization_code ) {
        $args = array(
            'timeout' => 15,
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

        $this->apply_token_response( $response_body );

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
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
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
                        <select name="fieldData[spreadsheetId]" v-model="fielddata.spreadsheetId" @change="getWorksheets" required="required" style="vertical-align:middle;">
                            <option value=""><?php _e( 'Select Spreadsheet...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in filteredSpreadsheets()" :value="index">{{item}}</option>
                        </select>
                        <input type="text" v-model="spreadsheetSearch" class="afi-sheet-filter" placeholder="<?php esc_attr_e( 'Filter spreadsheets...', 'advanced-form-integration' ); ?>" style="max-width:200px;margin-left:8px;vertical-align:middle;" />
                        <button type="button" class="afi-icon-btn" v-bind:class="{'is-loading': listLoading}" v-bind:disabled="listLoading" @click="getSpreadsheets" title="<?php esc_attr_e( 'Refresh spreadsheets', 'advanced-form-integration' ); ?>" aria-label="<?php esc_attr_e( 'Refresh spreadsheets', 'advanced-form-integration' ); ?>">
                            <svg class="afi-refresh-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                        </button>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Worksheet', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[worksheetId]" v-model="fielddata.worksheetId" @change="getHeaders" required="required" style="vertical-align:middle;">
                            <option value=""><?php _e( 'Select Worksheet...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in fielddata.worksheetList" :value="index">{{item}}</option>
                        </select>
                        <button type="button" class="afi-icon-btn" v-bind:class="{'is-loading': worksheetLoading}" v-bind:disabled="worksheetLoading" @click="refreshWorksheets" title="<?php esc_attr_e( 'Refresh worksheets', 'advanced-form-integration' ); ?>" aria-label="<?php esc_attr_e( 'Refresh worksheets', 'advanced-form-integration' ); ?>">
                            <svg class="afi-refresh-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                        </button>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Header Row', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="number" min="1" step="1" name="fieldData[headerRow]" v-model="fielddata.headerRow" @change="getHeaders" style="width:80px;" />
                        <p class="description"><?php esc_html_e( 'Row number that holds the column headers. Default is 1.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Cell Format', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[valueInputOption]" v-model="fielddata.valueInputOption">
                            <option value="USER_ENTERED"><?php esc_html_e( 'Automatic (parse numbers, dates, formulas)', 'advanced-form-integration' ); ?></option>
                            <option value="USER_ENTERED_STRIP"><?php esc_html_e( 'Automatic + strip HTML tags from values', 'advanced-form-integration' ); ?></option>
                            <option value="RAW"><?php esc_html_e( 'Raw (store values exactly as submitted)', 'advanced-form-integration' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Automatic + strip HTML stores the visible text only (e.g. a link field becomes its text). Use Raw to keep leading zeros, phone numbers, and values starting with + or = from being reformatted.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Options', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox" name="fieldData[bottomAppend]" value="true" v-model="fielddata.bottomAppend">
                            <?php esc_html_e( 'Always add to the next empty row at the bottom', 'advanced-form-integration' ); ?>
                        </label>
                        <p class="description" style="margin-top:0;"><?php esc_html_e( 'Use this if new rows overwrite an existing row or land in a gap under the header. Writes strictly below the last filled row.', 'advanced-form-integration' ); ?></p>
                        <label style="display:block;margin:8px 0 6px;">
                            <input type="checkbox" name="fieldData[createWorksheet]" value="true" v-model="fielddata.createWorksheet">
                            <?php esc_html_e( 'Create the worksheet automatically if it does not exist', 'advanced-form-integration' ); ?>
                        </label>
                        <p class="description" style="margin-top:0;"><?php esc_html_e( 'Recreates the selected tab if it was renamed or deleted in the sheet.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'add_row', 'Google Sheets [PRO]', 'WooCommerce order item rows' ); ?>
                <input type="hidden" name="fieldData[worksheetName]" :value="fielddata.worksheetName" />
                <input type="hidden" name="fieldData[worksheetList]" :value="JSON.stringify( fielddata.worksheetList )" />
            </table>
        </script>
        <?php
    }

    protected function save_data() {
        // OAuth Manager flow: persist canonical token fields via the base helper.
        if ( $this->cred_id ) {
            $this->persist_token_to_credential();
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
     * Returns true when at least a refresh token (or a still-valid access token)
     * is available — i.e. the OAuth flow has been completed for this account.
     */
    public function has_credentials() {
        return ! empty( $this->access_token ) || ! empty( $this->refresh_token );
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
        adfoin_require_manage_options();
        $nonce = isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        // Include spreadsheets living on Shared Drives / shared-with-me, not just
        // files the user owns. Without these flags a sheet the user can edit but
        // does not own never shows up in the picker ("can't find my spreadsheet").
        // Available on all plans — visibility of an existing sheet should not be
        // a paid gate.
        $base_endpoint = "https://www.googleapis.com/drive/v3/files?q=mimeType%20%3D%20'application%2Fvnd.google-apps.spreadsheet'&pageSize=1000&supportsAllDrives=true&includeItemsFromAllDrives=true&fields=nextPageToken,files(id,name)";

        $request = array(
            'timeout' => 30,
            'method'  => 'GET',
            'headers' => array(),
        );

        // Walk every page of results. Drive caps pageSize at 1000, so accounts
        // with more spreadsheets need the nextPageToken loop or the tail is
        // silently dropped. The page cap is a safety valve (~20,000 sheets).
        $all_files  = array();
        $page_token = '';
        $max_pages  = 20;
        $last_error = '';

        for ( $page = 0; $page < $max_pages; $page++ ) {
            $endpoint = $base_endpoint;

            if ( $page_token ) {
                $endpoint .= '&pageToken=' . rawurlencode( $page_token );
            }

            $response      = $this->remote_request( $endpoint, $request );
            $response_body = wp_remote_retrieve_body( $response );

            if ( empty( $response_body ) ) {
                $last_error = __( 'Empty response from Google API', 'advanced-form-integration' );
                break;
            }

            $body = json_decode( $response_body, true );

            if ( isset( $body['error'] ) ) {
                $last_error = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'advanced-form-integration' );
                break;
            }

            if ( isset( $body['files'] ) && is_array( $body['files'] ) ) {
                $all_files = array_merge( $all_files, $body['files'] );
            }

            $page_token = isset( $body['nextPageToken'] ) ? $body['nextPageToken'] : '';

            if ( ! $page_token ) {
                break;
            }
        }

        // Surface an error only when nothing was collected; a late-page failure
        // shouldn't hide the spreadsheets already gathered.
        if ( empty( $all_files ) ) {
            wp_send_json_error( $last_error ? $last_error : __( 'No spreadsheets found', 'advanced-form-integration' ) );
            return;
        }

        $spreadsheets_id_and_title = wp_list_pluck( $all_files, 'name', 'id' );

        wp_send_json_success( $spreadsheets_id_and_title );
    }

    public function get_worksheets() {
        adfoin_require_manage_options();
        $nonce = isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $spreadsheet_id = isset( $_POST['spreadsheetId'] ) ? sanitize_text_field( wp_unslash( $_POST['spreadsheetId'] ) ) : '';
        $cred_id        = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        if ( ! $spreadsheet_id ) {
            wp_send_json_error( __( 'Spreadsheet ID is required', 'advanced-form-integration' ) );
            return;
        }

        $endpoint = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/";

        $request = array(
            'timeout' => 30,
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
        adfoin_require_manage_options();
        $nonce = isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '';
        if ( ! wp_verify_nonce( $nonce, 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $spreadsheet_id = isset( $_REQUEST['spreadsheetId'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['spreadsheetId'] ) ) : '';
        $worksheet_name = isset( $_REQUEST['worksheetName'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['worksheetName'] ) ) : '';
        $cred_id        = isset( $_REQUEST['credId'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['credId'] ) ) : '';
        $header_row     = isset( $_REQUEST['headerRow'] ) ? absint( wp_unslash( $_REQUEST['headerRow'] ) ) : 1;

        if ( $header_row < 1 ) {
            $header_row = 1;
        }

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        if ( ! $spreadsheet_id || ! $worksheet_name ) {
            wp_send_json_error( __( 'Spreadsheet ID and Worksheet name are required', 'advanced-form-integration' ) );
            return;
        }

        // Headers are read from the configured header row (default 1), so sheets
        // with title/branding rows above the column labels still map correctly.
        $worksheet_name_encoded = rawurlencode( $worksheet_name );
        $endpoint               = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_name_encoded}!A{$header_row}:ZZ{$header_row}";

        $request = array(
            'timeout' => 30,
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

        // Attempt token refresh if current token is missing or expired.
        if ( ! $this->check_token_expiry( $this->access_token ) ) {
            $refresh_result = $this->refresh_token();

            // If refresh failed (WP_Error or empty access token after refresh), bail early.
            if ( is_wp_error( $refresh_result ) || empty( $this->access_token ) ) {
                $error_message = is_wp_error( $refresh_result )
                    ? $refresh_result->get_error_message()
                    : __( 'Google Sheets: Token refresh failed. Please re-authorize the connection in Settings → Google Sheets.', 'advanced-form-integration' );

                if ( $record ) {
                    adfoin_add_to_log( new WP_Error( 'auth_failed', $error_message ), $url, $request, $record );
                }

                return new WP_Error( 'auth_failed', $error_message );
            }
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

        // Use cached expiry time if available (with 60 second buffer).
        if ( $this->token_expires > 0 ) {
            return time() < ( $this->token_expires - 60 );
        }

        // Expiry unknown (e.g. a legacy account saved before we tracked it).
        // Report "expired" so the caller refreshes the token; the refresh
        // response repopulates token_expires going forward. This replaces the
        // old call to the deprecated oauth2/v1/tokeninfo endpoint.
        return false;
    }

    protected function refresh_token() {
        // No refresh token stored — cannot recover without re-authorization.
        if ( empty( $this->refresh_token ) ) {
            return new WP_Error(
                'missing_refresh_token',
                __( 'Google Sheets: No refresh token found. Please re-authorize the connection in Settings → Google Sheets.', 'advanced-form-integration' )
            );
        }

        $args = array(
            'timeout' => 15,
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

        $this->apply_token_response( $response_body );

        $this->save_data();

        return $response;
    }

    /**
     * Best-effort cross-process lock backed by the options table.
     *
     * `add_option()` performs an INSERT against a UNIQUE option_name index, so
     * the first caller wins and concurrent callers get false — good enough to
     * (a) de-duplicate identical submissions and (b) serialize strict
     * bottom-append writes for one site. An expired lock is taken over so a
     * crashed request can't wedge the key forever.
     *
     * @return bool True when the lock was acquired.
     */
    public function acquire_lock( $key, $ttl = 30 ) {
        $name = 'adfoin_gslock_' . md5( $key );
        $now  = time();

        if ( add_option( $name, (string) ( $now + $ttl ), '', 'no' ) ) {
            return true;
        }

        $expires = (int) get_option( $name, 0 );
        if ( $expires > 0 && $now > $expires ) {
            update_option( $name, (string) ( $now + $ttl ), 'no' );
            return true;
        }

        return false;
    }

    public function release_lock( $key ) {
        delete_option( 'adfoin_gslock_' . md5( $key ) );
    }

    /**
     * Resolve the next empty row at the BOTTOM of the sheet, based on the first
     * column. Used by strict bottom-append so new rows always land after the
     * last populated row — even when a blank row sits just under the header
     * (the case where Google's native :append table-detection would otherwise
     * drop the row into the gap). Returns a 1-based row number, or 0 if the
     * read failed (caller falls back to native append).
     */
    protected function get_append_start_row( $spreadsheet_id, $worksheet_name, $record = array() ) {
        $worksheet_encoded = rawurlencode( $worksheet_name );
        $url               = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_encoded}!A:A?majorDimension=COLUMNS";

        $response = $this->remote_request( $url, array( 'method' => 'GET' ), $record );

        if ( is_wp_error( $response ) ) {
            return 0;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return 0;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // Sheets trims trailing empty cells, so the count of column A equals the
        // last populated row number; the next free row is one below it.
        $column = ( isset( $body['values'][0] ) && is_array( $body['values'][0] ) ) ? $body['values'][0] : array();

        return count( $column ) + 1;
    }

    /**
     * Ensure $worksheet_name exists in the spreadsheet, creating it when the
     * "Create worksheet if missing" option is on. Short-circuits via a transient
     * so a busy integration doesn't fetch sheet metadata on every submission.
     *
     * @return true|WP_Error True when the worksheet exists (or was created).
     */
    public function ensure_worksheet( $spreadsheet_id, $worksheet_name, $record = array() ) {
        $cache_key = 'adfoin_gsws_' . md5( $spreadsheet_id . '|' . $worksheet_name );
        if ( get_transient( $cache_key ) ) {
            return true;
        }

        $meta = $this->remote_request(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/?fields=sheets.properties.title",
            array( 'method' => 'GET' ),
            $record
        );

        if ( is_wp_error( $meta ) ) {
            return $meta;
        }

        $meta_body = json_decode( wp_remote_retrieve_body( $meta ), true );
        $titles    = array();

        if ( isset( $meta_body['sheets'] ) && is_array( $meta_body['sheets'] ) ) {
            foreach ( $meta_body['sheets'] as $sheet ) {
                if ( isset( $sheet['properties']['title'] ) ) {
                    $titles[] = $sheet['properties']['title'];
                }
            }
        }

        if ( in_array( $worksheet_name, $titles, true ) ) {
            set_transient( $cache_key, 1, 5 * MINUTE_IN_SECONDS );
            return true;
        }

        // Create the missing tab.
        $create = $this->remote_request(
            "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}:batchUpdate",
            array(
                'method' => 'POST',
                'body'   => wp_json_encode(
                    array(
                        'requests' => array(
                            array(
                                'addSheet' => array(
                                    'properties' => array( 'title' => $worksheet_name ),
                                ),
                            ),
                        ),
                    )
                ),
            ),
            $record
        );

        if ( is_wp_error( $create ) ) {
            return $create;
        }

        $create_code = (int) wp_remote_retrieve_response_code( $create );
        if ( $create_code >= 200 && $create_code < 300 ) {
            set_transient( $cache_key, 1, 5 * MINUTE_IN_SECONDS );
            return true;
        }

        $body = json_decode( wp_remote_retrieve_body( $create ), true );
        $msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Failed to create worksheet.', 'advanced-form-integration' );

        return new WP_Error( 'create_worksheet_failed', $msg );
    }

    /**
     * Append a single row. Thin wrapper around append_rows() (the batch
     * primitive) kept for backward compatibility.
     *
     * @param string $value_input_option 'USER_ENTERED' (default) or 'RAW'.
     * @param string $append_mode        'native' (Google :append, default) or
     *                                    'bottom' (strict next-empty-row write).
     */
    public function append_new_row( $record, $spreadsheet_id = '', $worksheet_name = '', $data_array = array(), $value_input_option = 'USER_ENTERED', $append_mode = 'native' ) {
        if ( empty( $worksheet_name ) || empty( $data_array ) ) {
            $error = new WP_Error(
                'missing_input',
                __( 'Google Sheets: Worksheet name or data is empty. Skipping row append.', 'advanced-form-integration' )
            );

            if ( $record ) {
                adfoin_add_to_log( $error, '', array(), $record );
            }

            return $error;
        }

        return $this->append_rows( $record, $spreadsheet_id, $worksheet_name, array( array_values( $data_array ) ), $value_input_option, $append_mode );
    }

    /**
     * Append one or more rows in a single Sheets API call.
     *
     * This is the canonical write path; append_new_row() delegates here with a
     * single row. Callers that need to write many rows at once (bulk imports,
     * line-item expansion) should call this directly to avoid N API round-trips.
     *
     * @param array  $rows               Array of rows; each row an array of cell values.
     * @param string $value_input_option 'USER_ENTERED' (default) or 'RAW'.
     * @param string $append_mode        'native' (Google :append, default) or
     *                                    'bottom' (strict next-empty-row write).
     */
    public function append_rows( $record, $spreadsheet_id = '', $worksheet_name = '', $rows = array(), $value_input_option = 'USER_ENTERED', $append_mode = 'native' ) {
        if ( empty( $spreadsheet_id ) || empty( $worksheet_name ) || empty( $rows ) || ! is_array( $rows ) ) {
            $error = new WP_Error(
                'missing_input',
                __( 'Google Sheets: Spreadsheet, worksheet, or row data is empty. Skipping append.', 'advanced-form-integration' )
            );

            if ( $record ) {
                adfoin_add_to_log( $error, '', array(), $record );
            }

            return $error;
        }

        // Normalize every row to a sequential list of scalar strings and find
        // the widest row so the append range covers all columns.
        $values   = array();
        $max_cols = 0;

        foreach ( $rows as $row ) {
            $clean = array();

            foreach ( array_values( (array) $row ) as $cell ) {
                if ( is_array( $cell ) ) {
                    $cell = implode( ', ', array_map( 'strval', $cell ) );
                }
                // Match the legacy ternary exactly ($val ? $val : '') so existing
                // integrations write byte-identical cells. Note this preserves the
                // long-standing quirk that a falsy scalar (0, '0', '', null, false)
                // becomes an empty cell; changing that is out of scope here.
                $clean[] = $cell ? $cell : '';
            }

            $values[] = $clean;
            $max_cols = max( $max_cols, count( $clean ) );
        }

        if ( $max_cols < 1 ) {
            $error = new WP_Error(
                'missing_input',
                __( 'Google Sheets: No column values to write. Skipping append.', 'advanced-form-integration' )
            );

            if ( $record ) {
                adfoin_add_to_log( $error, '', array(), $record );
            }

            return $error;
        }

        $end_col            = $this->column_index_to_letter( $max_cols );
        $value_input_option = ( 'RAW' === $value_input_option ) ? 'RAW' : 'USER_ENTERED';
        $worksheet_encoded  = rawurlencode( $worksheet_name );

        // Strict bottom-append (opt-in): write to the first empty row below the
        // last populated row instead of letting Google's :append table-detection
        // choose the spot. This fixes the "new rows overwrite line 2 / land in a
        // gap under the header" reports. A short per-sheet lock serializes the
        // read-then-write so two near-simultaneous submissions don't target the
        // same row. Any failure here falls through to native :append.
        if ( 'bottom' === $append_mode ) {
            $lock_key = 'wlock_' . $spreadsheet_id . '_' . $worksheet_name;
            $locked   = false;

            for ( $attempt = 0; $attempt < 12; $attempt++ ) {
                if ( $this->acquire_lock( $lock_key, 20 ) ) {
                    $locked = true;
                    break;
                }
                usleep( 500000 ); // 0.5s
            }

            $start_row = $this->get_append_start_row( $spreadsheet_id, $worksheet_name, $record );

            if ( $start_row > 0 ) {
                $row_count = count( $values );
                $end_row   = $start_row + $row_count - 1;
                $range     = $worksheet_name . '!A' . $start_row . ':' . $end_col . $end_row;
                $put_url   = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_encoded}!A{$start_row}:{$end_col}{$end_row}?valueInputOption={$value_input_option}";

                $put_args = array(
                    'timeout' => 30,
                    'method'  => 'PUT',
                    'body'    => wp_json_encode(
                        array(
                            'range'          => $range,
                            'majorDimension' => 'ROWS',
                            'values'         => $values,
                        )
                    ),
                );

                $put_response = $this->remote_request( $put_url, $put_args, $record );

                if ( $locked ) {
                    $this->release_lock( $lock_key );
                }

                // On success, we're done. On failure (e.g. the target row is
                // beyond the sheet's grid size, which update — unlike append —
                // won't auto-grow), fall through to native :append so the row
                // still lands instead of being lost.
                if ( ! $this->response_failed( $put_response ) ) {
                    return $put_response;
                }
            } elseif ( $locked ) {
                // Could not resolve the bottom row — release the lock and fall back.
                $this->release_lock( $lock_key );
            }
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_encoded}!A:{$end_col}:append?valueInputOption={$value_input_option}&insertDataOption=INSERT_ROWS";

        $args = array(
            'timeout' => 30,
            'method'  => 'POST',
            'body'    => wp_json_encode(
                array(
                    'range'          => $worksheet_name . '!A:' . $end_col,
                    'majorDimension' => 'ROWS',
                    'values'         => $values,
                )
            ),
        );

        return $this->remote_request( $url, $args, $record );
    }

    /**
     * Convert a 1-based column index to its A1 letter (1 => A, 27 => AA).
     */
    protected function column_index_to_letter( $index ) {
        $index  = max( 1, (int) $index );
        $letter = '';

        while ( $index > 0 ) {
            $remainder = ( $index - 1 ) % 26;
            $letter    = chr( 65 + $remainder ) . $letter;
            $index     = intdiv( $index - 1, 26 );
        }

        return $letter;
    }

    /**
     * Convert a column letter to a 0-based index (A => 0, B => 1, AA => 26).
     */
    public function column_letter_to_index( $letter ) {
        $letter = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $letter ) );

        if ( '' === $letter ) {
            return -1;
        }

        $index = 0;
        for ( $i = 0, $len = strlen( $letter ); $i < $len; $i++ ) {
            $index = $index * 26 + ( ord( $letter[ $i ] ) - 64 );
        }

        return $index - 1;
    }

    /**
     * Find the first data row whose cell in $search_column equals $search_value.
     *
     * Used by the PRO Update/Upsert/Delete tasks. Uses a two-phase fetch so the
     * whole sheet is never pulled into memory:
     *   1. Read ONLY the search column (e.g. `!B:B`) to locate the matching row.
     *   2. Read ONLY that single row (e.g. `!A12:ZZ12`) to return its values.
     * Delete callers can pass $need_values = false to skip phase 2 entirely.
     *
     * Row indexing matches the legacy full-sheet scan: array index 0 == row 1,
     * so the first data row sits at index $header_row.
     *
     * @return array|false|WP_Error array( 'row_number' => int (1-based), 'values' => array )
     *                              when matched, false when no match, WP_Error on API error.
     */
    public function find_row( $record, $spreadsheet_id, $worksheet_name, $search_column, $search_value, $header_row = 1, $need_values = true ) {
        if ( empty( $spreadsheet_id ) || empty( $worksheet_name ) || '' === (string) $search_column ) {
            return new WP_Error( 'missing_input', __( 'Google Sheets: Spreadsheet, worksheet, or search column is missing.', 'advanced-form-integration' ) );
        }

        $col_index = $this->column_letter_to_index( $search_column );
        if ( $col_index < 0 ) {
            return false;
        }

        $header_row        = max( 1, (int) $header_row );
        $worksheet_encoded = rawurlencode( $worksheet_name );
        $search_letter     = $this->column_index_to_letter( $col_index + 1 );

        // Phase 1: fetch ONLY the search column. majorDimension=COLUMNS makes
        // values[0] the column top-to-bottom, so values[0][N] is row N+1.
        $url      = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_encoded}!{$search_letter}:{$search_letter}?majorDimension=COLUMNS";
        $response = $this->remote_request( $url, array( 'method' => 'GET' ), $record );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 ) {
            $msg = isset( $body['error']['message'] ) ? $body['error']['message'] : sprintf( 'HTTP %d', $code );
            return new WP_Error( 'lookup_failed', $msg );
        }

        if ( ! isset( $body['values'][0] ) || ! is_array( $body['values'][0] ) ) {
            return false;
        }

        $column           = $body['values'][0];
        $count            = count( $column );
        $match_row_number = 0;

        // The first data row sits at array index $header_row (row $header_row + 1).
        for ( $i = $header_row; $i < $count; $i++ ) {
            if ( isset( $column[ $i ] ) && (string) $column[ $i ] === (string) $search_value ) {
                $match_row_number = $i + 1;
                break;
            }
        }

        if ( $match_row_number < 1 ) {
            return false;
        }

        if ( ! $need_values ) {
            // Delete only needs the row number; skip the second round-trip.
            return array(
                'row_number' => $match_row_number,
                'values'     => array(),
            );
        }

        // Phase 2: fetch ONLY the matched row to return its current values.
        $row_url      = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_encoded}!A{$match_row_number}:ZZ{$match_row_number}?majorDimension=ROWS";
        $row_response = $this->remote_request( $row_url, array( 'method' => 'GET' ), $record );

        if ( is_wp_error( $row_response ) ) {
            return $row_response;
        }

        $row_code = (int) wp_remote_retrieve_response_code( $row_response );
        $row_body = json_decode( wp_remote_retrieve_body( $row_response ), true );

        if ( $row_code < 200 || $row_code >= 300 ) {
            $msg = isset( $row_body['error']['message'] ) ? $row_body['error']['message'] : sprintf( 'HTTP %d', $row_code );
            return new WP_Error( 'lookup_failed', $msg );
        }

        $values = ( isset( $row_body['values'][0] ) && is_array( $row_body['values'][0] ) ) ? $row_body['values'][0] : array();

        return array(
            'row_number' => $match_row_number,
            'values'     => $values,
        );
    }

    /**
     * Overwrite a single existing row (1-based) with $row_values.
     *
     * @param string $value_input_option 'USER_ENTERED' (default) or 'RAW'.
     */
    public function update_row_values( $record, $spreadsheet_id, $worksheet_name, $row_number, $row_values, $value_input_option = 'USER_ENTERED' ) {
        $row_number = (int) $row_number;

        if ( empty( $spreadsheet_id ) || empty( $worksheet_name ) || $row_number < 1 || empty( $row_values ) ) {
            $error = new WP_Error( 'missing_input', __( 'Google Sheets: Missing data for row update.', 'advanced-form-integration' ) );

            if ( $record ) {
                adfoin_add_to_log( $error, '', array(), $record );
            }

            return $error;
        }

        $clean = array();
        foreach ( array_values( (array) $row_values ) as $cell ) {
            if ( is_array( $cell ) ) {
                $cell = implode( ', ', array_map( 'strval', $cell ) );
            }
            $clean[] = $cell ? $cell : '';
        }

        $end_col            = $this->column_index_to_letter( count( $clean ) );
        $value_input_option = ( 'RAW' === $value_input_option ) ? 'RAW' : 'USER_ENTERED';
        $worksheet_encoded  = rawurlencode( $worksheet_name );
        $range              = $worksheet_name . '!A' . $row_number . ':' . $end_col . $row_number;

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}/values/{$worksheet_encoded}!A{$row_number}:{$end_col}{$row_number}?valueInputOption={$value_input_option}";

        $args = array(
            'timeout' => 30,
            'method'  => 'PUT',
            'body'    => wp_json_encode(
                array(
                    'range'          => $range,
                    'majorDimension' => 'ROWS',
                    'values'         => array( $clean ),
                )
            ),
        );

        return $this->remote_request( $url, $args, $record );
    }

    /**
     * Delete a single row (1-based), shifting rows below it up.
     *
     * @param int $sheet_id Numeric sheetId (the Worksheet dropdown value).
     */
    public function delete_row_dimension( $record, $spreadsheet_id, $sheet_id, $row_number ) {
        $row_number = (int) $row_number;

        if ( empty( $spreadsheet_id ) || '' === (string) $sheet_id || $row_number < 1 ) {
            $error = new WP_Error( 'missing_input', __( 'Google Sheets: Missing data for row deletion.', 'advanced-form-integration' ) );

            if ( $record ) {
                adfoin_add_to_log( $error, '', array(), $record );
            }

            return $error;
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheet_id}:batchUpdate";

        $args = array(
            'timeout' => 30,
            'method'  => 'POST',
            'body'    => wp_json_encode(
                array(
                    'requests' => array(
                        array(
                            'deleteDimension' => array(
                                'range' => array(
                                    'sheetId'    => (int) $sheet_id,
                                    'dimension'  => 'ROWS',
                                    'startIndex' => $row_number - 1,
                                    'endIndex'   => $row_number,
                                ),
                            ),
                        ),
                    ),
                )
            ),
        );

        return $this->remote_request( $url, $args, $record );
    }

    /**
     * Shared add-row pipeline used by both the free and PRO send_data handlers.
     *
     * Runs conditional logic, applies the pre-process filter, resolves the
     * account, guards against unauthorized accounts, builds the column map, and
     * appends the row. Extracted so free and PRO no longer carry near-duplicate
     * copies that can silently drift apart.
     */
    public function process_row_action( $record, $submitted_data ) {
        $record_data = json_decode( $record['data'], true );

        // Conditional logic gate.
        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $submitted_data ) ) {
            return;
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
        $value_input    = ( isset( $data['valueInputOption'] ) && 'RAW' === $data['valueInputOption'] ) ? 'RAW' : 'USER_ENTERED';
        $task           = $record['task'];
        $multiple_row   = ( isset( $data['wcMultipleRow'] ) && 'true' == $data['wcMultipleRow'] );
        // Strip HTML is folded into the Cell Format dropdown as a third choice
        // ("Automatic + strip HTML"); it writes USER_ENTERED with markup removed.
        $strip_html     = ( isset( $data['valueInputOption'] ) && 'USER_ENTERED_STRIP' === $data['valueInputOption'] );
        $create_ws      = ( isset( $data['createWorksheet'] ) && 'true' == $data['createWorksheet'] );
        $append_mode    = ( isset( $data['bottomAppend'] ) && 'true' == $data['bottomAppend'] ) ? 'bottom' : 'native';
        $form_provider  = isset( $record['form_provider'] ) ? $record['form_provider'] : '';

        /**
         * Override the row-placement strategy: 'native' (Google :append) or
         * 'bottom' (strict next-empty-row). Defaults to the per-integration
         * checkbox. Lets a site force one behavior globally.
         */
        $append_mode = apply_filters( 'afi_googlesheets_append_mode', $append_mode, $record, $submitted_data );

        if ( 'add_row' !== $task ) {
            return;
        }

        // Strip control/UI keys so only mapped columns remain in $data.
        // NOTE: `wcMultipleRow` is a control flag (captured above), not a column.
        // For WooCommerce the per-item expansion happens upstream in the trigger
        // (includes/triggers/woocommerce/woocommerce.php), so values arriving here
        // are already per-item scalars. For other triggers the expansion happens
        // below in expand_rows(), from array-valued (repeater / list) fields.
        foreach ( array( 'spreadsheetId', 'spreadsheetList', 'worksheetId', 'worksheetList', 'worksheetName', 'credId', 'wcMultipleRow', 'valueInputOption', 'headerRow', 'searchColumn', 'searchValue', 'createWorksheet', 'bottomAppend' ) as $control_key ) {
            unset( $data[ $control_key ] );
        }

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        // Bail early if no usable token is available at all.
        if ( ! $this->has_credentials() ) {
            adfoin_add_to_log(
                new WP_Error(
                    'no_credentials',
                    __( 'Google Sheets: No access token or refresh token found for this account. Please re-authorize the connection in Settings → Google Sheets.', 'advanced-form-integration' )
                ),
                '',
                array(),
                $record
            );
            return;
        }

        $holder = array();

        if ( empty( $data ) ) {
            // No explicit field mapping: dump submitted values positionally.
            // Preserve column positions — do NOT array_filter(), which drops
            // blank values and shifts every subsequent column one cell left.
            if ( is_array( $posted_data ) ) {
                $key = 'A';

                foreach ( $posted_data as $value ) {
                    $holder[ $key ] = is_array( $value ) ? implode( ', ', $value ) : $value;
                    $key++;

                    if ( 'ZZ' == $key ) {
                        break;
                    }
                }
            }
        } else {
            foreach ( $data as $key => $value ) {
                $holder[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
            }
        }

        // Optionally strip HTML markup from every value before writing. Form
        // fields that contain an <a> tag (e.g. a privacy-policy link) otherwise
        // dump raw markup into the cell; users want the visible text only.
        if ( $strip_html ) {
            $holder = $this->strip_html_values( $holder );
        }

        // Create the target tab on the fly when asked (handles a tab that was
        // renamed/deleted after the integration was built, and lets people point
        // at a not-yet-created worksheet). A failure is logged but we still try
        // the write so a transient metadata hiccup doesn't drop the submission.
        if ( $create_ws ) {
            $ensured = $this->ensure_worksheet( $spreadsheet_id, $worksheet_name, $record );
            if ( is_wp_error( $ensured ) ) {
                adfoin_add_to_log( $ensured, '', array(), $record );
            }
        }

        // Duplicate-submission guard: suppress a byte-identical row for the same
        // integration arriving within a short window (a form/host firing its
        // submit hook twice, a double-clicked submit, an Action Scheduler retry
        // racing the sync path). Distinct submissions hash differently and pass.
        $fingerprint = md5( $record['id'] . '|' . $spreadsheet_id . '|' . $worksheet_name . '|' . $task . '|' . wp_json_encode( $holder ) );
        if ( apply_filters( 'afi_googlesheets_dedupe', true, $record, $submitted_data ) && ! $this->acquire_lock( 'dup_' . $fingerprint, 120 ) ) {
            adfoin_add_to_log(
                new WP_Error( 'duplicate_suppressed', __( 'Google Sheets: Duplicate submission suppressed (an identical row for this integration was written moments ago).', 'advanced-form-integration' ) ),
                '',
                array(),
                $record
            );
            return;
        }

        // Generalized line-item / repeater expansion: when enabled, a mapped
        // field that resolves to multiple values (a repeater/list field, a
        // multi-product cart, etc.) produces one row per value instead of being
        // squashed into one cell. WooCommerce is excluded because its trigger
        // already dispatches one job per order item, so the values here are
        // already per-item scalars (expanding again would double-count).
        if ( $multiple_row && 'woocommerce' !== $form_provider ) {
            $rows = $this->expand_rows( $holder );

            if ( count( $rows ) > 1 ) {
                $result = $this->append_rows( $record, $spreadsheet_id, $worksheet_name, $rows, $value_input, $append_mode );
                $this->release_dedupe_on_failure( $result, 'dup_' . $fingerprint );
                return $result;
            }
        }

        $result = $this->append_new_row( $record, $spreadsheet_id, $worksheet_name, $holder, $value_input, $append_mode );
        $this->release_dedupe_on_failure( $result, 'dup_' . $fingerprint );

        return $result;
    }

    /**
     * Whether an append/update result represents a failure (WP_Error or non-2xx).
     * A null/unknown result (a path that intentionally skipped) is NOT a failure.
     */
    public function response_failed( $result ) {
        if ( is_wp_error( $result ) ) {
            return true;
        }

        if ( is_array( $result ) ) {
            $code = (int) wp_remote_retrieve_response_code( $result );
            return $code < 200 || $code >= 300;
        }

        return false;
    }

    /**
     * Whether a failure is worth retrying. Rate limits (429) and server errors
     * (5xx) are transient; a WP_Error (auth, missing input) or 4xx won't fix
     * itself on a blind retry, so we leave those for the user to resolve.
     */
    public function response_is_transient( $result ) {
        if ( is_array( $result ) ) {
            $code = (int) wp_remote_retrieve_response_code( $result );
            return 429 === $code || ( $code >= 500 && $code < 600 );
        }

        return false;
    }

    /**
     * Release the duplicate-suppression lock when the write failed, so a retry
     * or a genuine re-submission isn't mistaken for a duplicate. The lock is
     * kept only when the row actually landed.
     */
    protected function release_dedupe_on_failure( $result, $dedupe_key ) {
        if ( $this->response_failed( $result ) ) {
            $this->release_lock( $dedupe_key );
        }
    }

    /**
     * Strip HTML tags (and decode entities) from a column => value map, leaving
     * the human-readable text. Array values (repeater/list fields) are walked
     * element-by-element so the per-element structure survives for expand_rows().
     */
    protected function strip_html_values( $holder ) {
        foreach ( $holder as $key => $value ) {
            if ( is_array( $value ) ) {
                $holder[ $key ] = array_map( array( $this, 'strip_html_scalar' ), $value );
            } else {
                $holder[ $key ] = $this->strip_html_scalar( $value );
            }
        }

        return $holder;
    }

    public function strip_html_scalar( $value ) {
        if ( ! is_scalar( $value ) ) {
            return $value;
        }

        $text = wp_strip_all_tags( (string) $value );

        return trim( html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
    }

    /**
     * Expand a column => value map into one row per repeated value.
     *
     * Array-valued columns (a repeater/list field, multiple cart products, etc.)
     * are walked in parallel up to the longest array; scalar columns repeat on
     * every row. Returns an array of positional rows for append_rows().
     */
    protected function expand_rows( $holder ) {
        $row_count = 1;

        foreach ( $holder as $val ) {
            if ( is_array( $val ) ) {
                $row_count = max( $row_count, count( $val ) );
            }
        }

        $rows = array();

        for ( $i = 0; $i < $row_count; $i++ ) {
            $row = array();

            foreach ( $holder as $key => $val ) {
                if ( is_array( $val ) ) {
                    $vals        = array_values( $val );
                    $row[ $key ] = isset( $vals[ $i ] ) ? $vals[ $i ] : '';
                } else {
                    $row[ $key ] = $val;
                }
            }

            $rows[] = array_values( $row );
        }

        return $rows;
    }
}

$googlesheets = ADFOIN_GoogleSheets::get_instance();

add_action( 'adfoin_googlesheets_job_queue', 'adfoin_googlesheets_job_queue', 10, 1 );

function adfoin_googlesheets_job_queue( $data ) {
    $retry = isset( $data['retry'] ) ? (int) $data['retry'] : 0;
    adfoin_googlesheets_send_data( $data['record'], $data['posted_data'], $retry );
}

/*
 * Handles sending data to Google Sheets API.
 * Delegates to the shared writer on ADFOIN_GoogleSheets so the free and PRO
 * paths stay in lockstep.
 */
function adfoin_googlesheets_send_data( $record, $submitted_data, $retry = 0 ) {
    $gs     = ADFOIN_GoogleSheets::get_instance();
    $result = $gs->process_row_action( $record, $submitted_data );
    adfoin_googlesheets_schedule_retry( $gs, $result, 'adfoin_googlesheets_job_queue', $record, $submitted_data, $retry );
}

/**
 * Re-queue a Google Sheets write once, after a delay, when it failed for a
 * transient reason (429 rate limit / 5xx). This turns a momentary Google
 * hiccup or burst rate-limit — a frequent cause of "some orders never reach
 * the sheet" — into a delivered row instead of a silent miss. Requires Action
 * Scheduler (bundled with WooCommerce and many hosts); a no-op without it.
 * Capped at a single retry so a hard outage can't build an infinite backlog.
 */
function adfoin_googlesheets_schedule_retry( $gs, $result, $hook, $record, $submitted_data, $retry ) {
    if ( $retry >= 1 ) {
        return;
    }

    if ( ! function_exists( 'as_schedule_single_action' ) ) {
        return;
    }

    if ( ! $gs->response_is_transient( $result ) ) {
        return;
    }

    as_schedule_single_action(
        time() + 120,
        $hook,
        array(
            'data' => array(
                'record'      => $record,
                'posted_data' => $submitted_data,
                'retry'       => $retry + 1,
            ),
        ),
        'adfoin'
    );
}
