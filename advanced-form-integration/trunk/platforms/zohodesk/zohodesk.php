<?php

class ADFOIN_ZohoDesk extends Advanced_Form_Integration_OAuth2 {
    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';

    const token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    public $data_center;

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;
        add_action( 'admin_init', array($this, 'auth_redirect') );
        add_filter(
            'adfoin_action_providers',
            array($this, 'adfoin_zohodesk_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_zohodesk_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_zohodesk_settings_view'),
            10,
            1
        );
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action(
            'wp_ajax_adfoin_get_zohodesk_organizations',
            array($this, 'get_organizations'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_get_zohodesk_owners',
            array($this, 'get_owners'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_get_zohodesk_departments',
            array($this, 'get_departments'),
            10,
            0
        );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_zohodesk_fields', array($this, 'get_fields') );
        add_action(
            'wp_ajax_adfoin_get_zohodesk_credentials',
            array($this, 'get_credentials'),
            10,
            0
        );
        add_filter(
            'adfoin_get_credentials',
            array($this, 'modify_credentials'),
            10,
            2
        );
        add_action(
            'wp_ajax_adfoin_save_zohodesk_credentials',
            array($this, 'save_credentials'),
            10,
            0
        );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohodesk', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code = ( isset( $params['code'] ) ? trim( $params['code'] ) : '' );
        $state = ( isset( $params['state'] ) ? trim( $params['state'] ) : '' );
        $this->cred_id = $state;
        if ( $code ) {
            // This is the REST API callback.
            // We need to process the token and then return HTML to close the popup.
            // 1. Set credentials context
            $zoho_credentials = adfoin_read_credentials( 'zohodesk' );
            foreach ( $zoho_credentials as $value ) {
                if ( $value['id'] == $state ) {
                    $this->data_center = ( isset( $value['data_center'] ) ? $value['data_center'] : $value['dataCenter'] ?? 'com' );
                    $this->client_id = ( isset( $value['client_id'] ) ? $value['client_id'] : $value['clientId'] ?? '' );
                    $this->client_secret = ( isset( $value['client_secret'] ) ? $value['client_secret'] : $value['clientSecret'] ?? '' );
                    $this->update_oauth_endpoints( $this->data_center );
                }
            }
            // 2. Request Token
            $response = $this->request_token( $code );
            // 3. Check success
            $success = false;
            $message = 'Unknown error';
            if ( !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['access_token'] ) ) {
                    $success = true;
                    $message = 'Connected successfully!';
                } else {
                    $message = ( isset( $body['error'] ) ? $body['error'] : 'Token exchange failed.' );
                }
            } else {
                $message = ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error' );
            }
            // 4. Output Close Popup HTML
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
            exit;
        }
    }

    public function adfoin_zohodesk_actions( $actions ) {
        $actions['zohodesk'] = array(
            'title' => __( 'Zoho Desk', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Add new record', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    function get_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $all_credentials = adfoin_read_credentials( 'zohodesk' );
        wp_send_json_success( $all_credentials );
    }

    function modify_credentials( $credentials, $platform ) {
        if ( 'zohodesk' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_zohodesk_keys' ) );
            if ( isset( $option['data_center'] ) && isset( $option['client_id'] ) && isset( $option['client_secret'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy',
                    'title'         => __( 'Legacy Account', 'advanced-form-integration' ),
                    'data_center'   => $option['data_center'],
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => ( isset( $option['access_token'] ) ? $option['access_token'] : '' ),
                    'refresh_token' => ( isset( $option['refresh_token'] ) ? $option['refresh_token'] : '' ),
                );
            }
        }
        return $credentials;
    }

    /*
     * Save Zoho Desk credentials
     */
    function save_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = 'zohodesk';
        $credentials = adfoin_read_credentials( $platform );
        if ( !is_array( $credentials ) ) {
            $credentials = array();
        }
        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[$index] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array(
                    'message' => 'Deleted',
                ) );
            }
            wp_send_json_error( 'Invalid index' );
        }
        // Handle Save/Update
        $id = ( isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '' );
        $title = ( isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '' );
        $client_id = ( isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '' );
        $client_secret = ( isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '' );
        $data_center = ( isset( $_POST['data_center'] ) ? sanitize_text_field( $_POST['data_center'] ) : 'com' );
        if ( empty( $id ) ) {
            $id = uniqid();
            $new_entry = true;
        } else {
            $new_entry = false;
        }
        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'data_center'   => $data_center,
            'access_token'  => '',
            'refresh_token' => '',
        );
        // If updating, preserve tokens if client ID/Secret haven't changed
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if not explicitly clearing them
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id && isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret && isset( $cred['data_center'] ) && $cred['data_center'] == $data_center ) {
                    $new_data['access_token'] = ( isset( $cred['access_token'] ) ? $cred['access_token'] : '' );
                    $new_data['refresh_token'] = ( isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '' );
                }
                $cred = $new_data;
                $found = true;
                break;
            }
        }
        if ( !$found ) {
            $credentials[] = $new_data;
        }
        adfoin_save_credentials( $platform, $credentials );
        // Generate Auth URL
        $redirect_uri = $this->get_redirect_uri();
        $scope = 'Desk.tickets.ALL,Desk.contacts.ALL,Desk.basic.ALL,Desk.settings.ALL';
        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => $redirect_uri,
            'scope'         => $scope,
            'state'         => $id,
        ), $auth_endpoint );
        wp_send_json_success( array(
            'auth_url' => $auth_url,
        ) );
    }

    public function adfoin_zohodesk_settings_tab( $providers ) {
        $providers['zohodesk'] = __( 'Zoho Desk', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_zohodesk_settings_view( $current_tab ) {
        if ( $current_tab != 'zohodesk' ) {
            return;
        }
        $redirect_uri = $this->get_redirect_uri();
        // Define fields
        $fields = array(array(
            'name'          => 'client_id',
            'label'         => __( 'Client ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ), array(
            'name'          => 'client_secret',
            'label'         => __( 'Client Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ), array(
            'name'          => 'data_center',
            'label'         => __( 'Data Center', 'advanced-form-integration' ),
            'type'          => 'select',
            'required'      => false,
            'show_in_table' => false,
            'options'       => array(
                'com'          => 'zoho.com',
                'eu'           => 'zoho.eu',
                'in'           => 'zoho.in',
                'com.cn'       => 'zoho.com.cn',
                'com.au'       => 'zoho.com.au',
                'jp'           => 'zoho.jp',
                'sa'           => 'zoho.sa',
                'zohocloud.ca' => 'zohocloud.ca',
            ),
        ));
        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho Desk API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Add Client, Choose Server-based Applications.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Insert a suitable Client Name.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Insert URL of your website as Homepage URL.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Authorized Redirect URIs input box.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Click CREATE.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';
        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Zoho Desk', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'zohodesk',
            'Zoho Desk',
            $fields,
            $instructions,
            $config
        );
    }

    protected function request_token( $authorization_code ) {
        $tok_endpoint = $this->get_oauth_endpoint( 'token', $this->data_center );
        $endpoint = add_query_arg( array(
            'code'         => $authorization_code,
            'redirect_uri' => urlencode( $this->get_redirect_uri() ),
            'grant_type'   => 'authorization_code',
        ), $tok_endpoint );
        $request = [
            'headers' => [
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ],
        ];
        $response = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if ( 401 == $response_code ) {
            // Unauthorized
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }
            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                $this->refresh_token = null;
            }
        }
        $this->save_data();
        return $response;
    }

    protected function refresh_token() {
        $ref_endpoint = $this->get_oauth_endpoint( 'token', $this->data_center );
        $endpoint = add_query_arg( array(
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
        ), $ref_endpoint );
        $request = [
            'headers' => array(
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ),
        ];
        $response = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        if ( 401 == $response_code ) {
            // Unauthorized
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }
            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            }
        }
        $this->save_data();
        return $response;
    }

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'zohodesk' );
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials = array();
        $all_credentials = adfoin_read_credentials( 'zohodesk' );
        if ( is_array( $all_credentials ) ) {
            $credentials = $all_credentials[0];
            foreach ( $all_credentials as $single ) {
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                }
            }
        }
        return $credentials;
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='zohodesk-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'subscribe'">
                    <th scope='row'>
                        <?php 
        esc_attr_e( 'Map Fields', 'advanced-form-integration' );
        ?>
                    </th>
                    <td scope='row'>

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
        esc_attr_e( 'Zoho Account', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getOrganizations">
                        <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <?php 
        $this->get_credentials_list();
        ?>
                        </select>
                        <a href="<?php 
        echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohodesk' );
        ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
        esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
        ?>
                        </a>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Organization', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[orgId]" v-model="fielddata.orgId" @change="getDepartments">
                            <option value=''> <?php 
        _e( 'Select Organization...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.organizations' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': organizationLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Department', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[departmentId]" v-model="fielddata.departmentId" @change=getFields>
                            <option value=''> <?php 
        _e( 'Select Department...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.departments' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': departmentLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Owner', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[ownerId]" v-model="fielddata.ownerId">
                            <option value=''> <?php 
        _e( 'Select Owner...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.owners' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': ownerLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>


                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>

                <?php 
        if ( adfoin_fs()->is_not_paying() ) {
            ?>
                        <tr valign="top" v-if="action.task == 'subscribe'">
                            <th scope="row">
                                <?php 
            esc_attr_e( 'Go Pro', 'advanced-form-integration' );
            ?>
                            </th>
                            <td scope="row">
                                <span><?php 
            printf( __( 'To unlock custom fields, tags and attachments consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
            ?></span>
                            </td>
                        </tr>
                        <?php 
        }
        ?>
            </table>
        </script>


        <?php 
    }

    public function auth_redirect() {
        $action = ( isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '' );
        if ( 'adfoin_zohodesk_auth_redirect' == $action ) {
            $code = ( isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' );
            $state = ( isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '' );
            if ( $state && $code ) {
                $this->cred_id = $state;
                $zoho_credentials = adfoin_read_credentials( 'zohodesk' );
                foreach ( $zoho_credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->data_center = ( isset( $value['data_center'] ) ? $value['data_center'] : $value['dataCenter'] ?? 'com' );
                        $this->client_id = ( isset( $value['client_id'] ) ? $value['client_id'] : $value['clientId'] ?? '' );
                        $this->client_secret = ( isset( $value['client_secret'] ) ? $value['client_secret'] : $value['clientSecret'] ?? '' );
                        $this->update_oauth_endpoints( $this->data_center );
                    }
                }
                $this->request_token( $code );
                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
            }
        }
    }

    protected function save_data() {
        $data = array(
            'data_center'   => $this->data_center,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'id'            => $this->cred_id,
        );
        $zoho_credentials = adfoin_read_credentials( 'zohodesk' );
        foreach ( $zoho_credentials as &$value ) {
            if ( $value['id'] == $this->cred_id ) {
                if ( $this->access_token ) {
                    $value['access_token'] = $this->access_token;
                }
                if ( $this->refresh_token ) {
                    $value['refresh_token'] = $this->refresh_token;
                }
            }
        }
        adfoin_save_credentials( 'zohodesk', $zoho_credentials );
    }

    protected function reset_data() {
        $this->data_center = 'com';
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';
        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohodesk' );
    }

    /**
     * Resolve Zoho Accounts base URL by data center.
     */
    protected function get_accounts_base( $data_center = 'com' ) {
        $map = array(
            'com'          => 'https://accounts.zoho.com',
            'eu'           => 'https://accounts.zoho.eu',
            'in'           => 'https://accounts.zoho.in',
            'com.cn'       => 'https://accounts.zoho.com.cn',
            'com.au'       => 'https://accounts.zoho.com.au',
            'jp'           => 'https://accounts.zoho.jp',
            'sa'           => 'https://accounts.zoho.sa',
            'zohocloud.ca' => 'https://accounts.zohocloud.ca',
            'ca'           => 'https://accounts.zohocloud.ca',
        );
        return $map[$data_center] ?? $map['com'];
    }

    /**
     * Resolve full OAuth endpoint (auth/token) by data center.
     */
    protected function get_oauth_endpoint( $path = 'auth', $data_center = '' ) {
        $base = $this->get_accounts_base( ( $data_center ? $data_center : $this->data_center ) );
        return trailingslashit( $base ) . 'oauth/v2/' . $path;
    }

    /**
     * Update stored OAuth endpoints to match a data center.
     */
    protected function update_oauth_endpoints( $data_center ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $this->token_endpoint = $this->get_oauth_endpoint( 'token', $data_center );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return;
        }
        $this->data_center = $credentials['data_center'] ?? $credentials['dataCenter'] ?? 'com';
        $this->client_id = $credentials['client_id'] ?? $credentials['clientId'] ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
        $this->access_token = $credentials['access_token'] ?? $credentials['accessToken'] ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        $this->cred_id = $credentials['id'];
        $this->update_oauth_endpoints( $this->data_center );
    }

    public function zohodesk_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array(),
        $org_id = ''
    ) {
        $base_url = 'https://desk.zoho.com/api/v1/';
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $base_url = str_replace( 'com', $this->data_center, $base_url );
        }
        $url = $base_url . $endpoint;
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );
        if ( $org_id ) {
            $args['headers']['orgId'] = $org_id;
        }
        if ( 'POST' == $method || 'PUT' == $method ) {
            if ( $data ) {
                $args['body'] = json_encode( $data );
            }
        }
        $response = $this->remote_request( $url, $args, $record );
        return $response;
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;
        $request = wp_parse_args( $request, [] );
        $request['headers'] = array_merge( $request['headers'], array(
            'Authorization' => $this->get_http_authorization_header( 'bearer' ),
        ) );
        $request['timeout'] = 30;
        $response = wp_remote_request( esc_url_raw( $url ), $request );
        if ( 401 === wp_remote_retrieve_response_code( $response ) and !$refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $response = $this->remote_request( $url, $request );
        }
        if ( $record ) {
            adfoin_add_to_log(
                $response,
                $url,
                $request,
                $record
            );
        }
        return $response;
    }

    /*
     * Get Organizations
     */
    public function get_organizations() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohodesk_request( 'organizations' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
            $organizations = array();
            foreach ( $response_body['data'] as $value ) {
                $organizations[$value['id']] = $value['companyName'];
            }
            wp_send_json_success( $organizations );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Ownwers
     */
    public function get_owners() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohodesk_request( 'agents?status=ACTIVE&limit=100' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
            $owners = array();
            foreach ( $response_body['data'] as $value ) {
                $owners[$value['id']] = $value['name'];
            }
            wp_send_json_success( $owners );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Departments
     */
    public function get_departments() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $org_id = ( isset( $_POST['orgId'] ) ? sanitize_text_field( $_POST['orgId'] ) : '' );
        $this->set_credentials( $cred_id );
        $base_url = 'https://desk.zoho.com/api/v1/';
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $base_url = str_replace( 'com', $this->data_center, $base_url );
        }
        $url = $base_url . 'departments?isEnabled=true&limit=200';
        $args = array(
            'method'  => 'GET',
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'orgId'        => $org_id,
            ),
        );
        $response = $this->remote_request( $url, $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
            $departments = array();
            foreach ( $response_body['data'] as $value ) {
                $departments[$value['id']] = $value['name'];
            }
            wp_send_json_success( $departments );
        } else {
            wp_send_json_error();
        }
    }

    function get_teams( $department_id, $org_id ) {
        $response = $this->zohodesk_request(
            "departments/{$department_id}/teams",
            'GET',
            array(),
            array(),
            $org_id
        );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( !empty( $response_body['teams'] ) && is_array( $response_body['teams'] ) ) {
            $teams = array();
            foreach ( $response_body['teams'] as $value ) {
                $teams[] = $value['name'] . ": " . $value['id'];
            }
            return implode( ', ', $teams );
        } else {
            return '';
        }
    }

    function get_agents( $org_id ) {
        $response = $this->zohodesk_request(
            "agents?status=ACTIVE&limit=200",
            'GET',
            array(),
            array(),
            $org_id
        );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( !empty( $response_body['data'] ) && is_array( $response_body['data'] ) ) {
            $agents = array();
            foreach ( $response_body['data'] as $value ) {
                $agents[] = $value['name'] . ": " . $value['id'];
            }
            return implode( ', ', $agents );
        } else {
            return '';
        }
    }

    /*
     * Get Fields
     */
    function get_fields() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        // get teams from 'departments/{department_id}/teams'
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $org_id = ( isset( $_POST['orgId'] ) ? sanitize_text_field( $_POST['orgId'] ) : '' );
        $department_id = ( isset( $_POST['departmentId'] ) ? sanitize_text_field( $_POST['departmentId'] ) : '' );
        $this->set_credentials( $cred_id );
        $teams = $this->get_teams( $department_id, $org_id );
        // $agents = $this->get_agents( $org_id );
        $account_fields = array(
            array(
                'key'   => 'accounts__accountName',
                'value' => __( 'Name [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__description',
                'value' => __( 'Description [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__email',
                'value' => __( 'Email [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__website',
                'value' => __( 'Website [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__fax',
                'value' => __( 'Fax [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__industry',
                'value' => __( 'Industry [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__city',
                'value' => __( 'City [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__country',
                'value' => __( 'Country [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__state',
                'value' => __( 'State [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__street',
                'value' => __( 'Street [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__code',
                'value' => __( 'Zip Code [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__phone',
                'value' => __( 'Phone [Account]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'accounts__annualrevenue',
                'value' => __( 'Annual Revenue [Account]', 'advanced-form-integration' ),
            )
        );
        $contact_fields = array(
            array(
                'key'   => 'contacts__firstName',
                'value' => __( 'First Name [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__lastName',
                'value' => __( 'Last Name [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__email',
                'value' => __( 'Email [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__secondaryemail',
                'value' => __( 'Secondary Email [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__phone',
                'value' => __( 'Phone [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__mobile',
                'value' => __( 'Mobile [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__description',
                'value' => __( 'Description [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__title',
                'value' => __( 'Title [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__type',
                'value' => __( 'Contact Type [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__street',
                'value' => __( 'Street [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__city',
                'value' => __( 'City [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__state',
                'value' => __( 'State [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__zip',
                'value' => __( 'Zip Code [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__country',
                'value' => __( 'Country [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__facebook',
                'value' => __( 'Facebook [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__twitter',
                'value' => __( 'Twitter [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__photoURL',
                'value' => __( 'Photo URL [Contact]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'contacts__webUrl',
                'value' => __( 'Web URL [Contact]', 'advanced-form-integration' ),
            )
        );
        $ticket_fields = array(
            array(
                'key'   => 'tickets__subject',
                'value' => __( 'Subject [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__description',
                'value' => __( 'Description [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__email',
                'value' => __( 'Email [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__phone',
                'value' => __( 'Phone [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__status',
                'value' => __( 'Status [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__priority',
                'value' => __( 'Priority [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__language',
                'value' => __( 'Language [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__category',
                'value' => __( 'Category [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__subCategory',
                'value' => __( 'Sub Category [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__resolution',
                'value' => __( 'Resolution [Ticket]', 'advanced-form-integration' ),
            ),
            // array( 'key' => 'tickets__assigneeId', 'value' => __( 'Assignee ID [Ticket]', 'advanced-form-integration' ), 'description' => $agents ),
            array(
                'key'   => 'tickets__dueDate',
                'value' => __( 'Due Date [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__responseDueDate',
                'value' => __( 'Response Due Date [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__classification',
                'value' => __( 'Classification [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'   => 'tickets__webUrl',
                'value' => __( 'Web URL [Ticket]', 'advanced-form-integration' ),
            ),
            array(
                'key'         => 'tickets__teamId',
                'value'       => __( 'Team ID [Ticket]', 'advanced-form-integration' ),
                'description' => $teams,
            ),
        );
        wp_send_json_success( array_merge( $account_fields, $contact_fields, $ticket_fields ) );
    }

    public function search_record( $module, $search_key, $search_value ) {
        sleep( 5 );
        $result = array(
            'id'   => '',
            'data' => array(),
        );
        $endpoint = "{$module}/search?limit=1&{$search_key}={$search_value}";
        $response = $this->zohodesk_request( $endpoint );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['data'], $body['data'][0], $body['data'][0]['id'] ) ) {
            $result['id'] = $body['data'][0]['id'];
            $result['data'] = $body['data'][0];
        }
        return $result;
    }

    public function is_duplicate( $module, $holder ) {
        // if( 'Leads' == $module ) {
        //     $lead_id = $this->search_record( 'Leads', 'Email', $holder['Email'], $record )['id'];
        //     if( $lead_id ) {
        //         return $lead_id;
        //     }
        // }
        // if( 'Contacts' == $module ) {
        //     $contact_id = $this->search_record( 'Contacts', 'Email', $holder['Email'], $record )['id'];
        //     if( $contact_id ) {
        //         return $contact_id;
        //     }
        // }
        if ( 'accounts' == $module ) {
            $account = $this->search_record( $module, 'accountName', $holder['accountName'] )['id'];
            if ( $account ) {
                return $account;
            }
        }
        if ( 'contacts' == $module ) {
            $contact = $this->search_record( $module, 'email', $holder['email'] )['id'];
            if ( $contact ) {
                return $contact;
            }
        }
        return false;
    }

    public function create_account( $account_data, $record, $org_id ) {
        $response = $this->zohodesk_request(
            'accounts',
            'POST',
            $account_data,
            $record,
            $org_id
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

    public function update_account(
        $account_id,
        $account_data,
        $record,
        $org_id
    ) {
        $response = $this->zohodesk_request(
            'accounts/' . $account_id,
            'PUT',
            $account_data,
            $record,
            $org_id
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

    public function create_contact( $contact_data, $record, $org_id ) {
        $response = $this->zohodesk_request(
            'contacts',
            'POST',
            $contact_data,
            $record,
            $org_id
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

    public function update_contact(
        $contact_id,
        $contact_data,
        $record,
        $org_id
    ) {
        $response = $this->zohodesk_request(
            'contacts/' . $contact_id,
            'PUT',
            $contact_data,
            $record,
            $org_id
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

    public function create_ticket( $ticket_data, $record, $org_id ) {
        $response = $this->zohodesk_request(
            'tickets',
            'POST',
            $ticket_data,
            $record,
            $org_id
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

}

$zohodesk = ADFOIN_ZohoDesk::get_instance();
add_action(
    'adfoin_zohodesk_job_queue',
    'adfoin_zohodesk_job_queue',
    10,
    1
);
function adfoin_zohodesk_job_queue(  $data  ) {
    adfoin_zohodesk_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zoho API
 */
function adfoin_zohodesk_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $owner_id = ( isset( $data['ownerId'] ) ? $data['ownerId'] : '' );
    $org_id = ( isset( $data['orgId'] ) ? $data['orgId'] : '' );
    $dept_id = ( isset( $data['departmentId'] ) ? $data['departmentId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    // Backward compatibility: If no credId, use the first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'zohodesk' );
        if ( !empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = ( isset( $first_credential['id'] ) ? $first_credential['id'] : '' );
        }
    }
    unset(
        $data['ownerId'],
        $data['orgId'],
        $data['departmentId'],
        $data['credId']
    );
    if ( $task == 'subscribe' ) {
        $zohodesk = ADFOIN_ZohoDesk::get_instance();
        $holder = array();
        $account_id = '';
        $account_data = array();
        $contact_id = '';
        $contact_data = array();
        $ticket_id = '';
        $ticket_data = array();
        $zohodesk->set_credentials( $cred_id );
        foreach ( $data as $key => $value ) {
            $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
        }
        foreach ( $holder as $key => $value ) {
            if ( substr( $key, 0, 10 ) == 'accounts__' && $value ) {
                $key = substr( $key, 10 );
                $account_data[$key] = $value;
            }
            if ( substr( $key, 0, 10 ) == 'contacts__' && $value ) {
                $key = substr( $key, 10 );
                $contact_data[$key] = $value;
            }
            if ( substr( $key, 0, 9 ) == 'tickets__' && $value ) {
                $key = substr( $key, 9 );
                $ticket_data[$key] = $value;
            }
        }
        $account_data = array_filter( $account_data );
        $contact_data = array_filter( $contact_data );
        $ticket_data = array_filter( $ticket_data );
        if ( $account_data ) {
            if ( $owner_id ) {
                $account_data['ownerId'] = $owner_id;
            }
            $account_id = $zohodesk->is_duplicate( 'accounts', $account_data );
            if ( $account_id ) {
                $zohodesk->update_account(
                    $account_id,
                    $account_data,
                    $record,
                    $org_id
                );
            } else {
                $account_id = $zohodesk->create_account( $account_data, $record, $org_id );
            }
        }
        if ( $contact_data ) {
            if ( $owner_id ) {
                $contact_data['ownerId'] = $owner_id;
            }
            if ( $account_id ) {
                $contact_data['accountId'] = $account_id;
            }
            $contact_id = $zohodesk->is_duplicate( 'contacts', $contact_data );
            if ( $contact_id ) {
                $zohodesk->update_contact(
                    $contact_id,
                    $contact_data,
                    $record,
                    $org_id
                );
            } else {
                $contact_id = $zohodesk->create_contact( $contact_data, $record, $org_id );
            }
        }
        if ( $ticket_data ) {
            if ( $owner_id ) {
                $ticket_data['assigneeId'] = $owner_id;
            }
            if ( $contact_id ) {
                $ticket_data['contactId'] = $contact_id;
            }
            if ( $dept_id ) {
                $ticket_data['departmentId'] = $dept_id;
            }
            $ticket_id = $zohodesk->create_ticket( $ticket_data, $record, $org_id );
        }
    }
    return;
}
