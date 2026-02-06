<?php

class ADFOIN_ZohoCampaigns extends Advanced_Form_Integration_OAuth2 {

    const service_name           = 'zohocampaigns';
    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';
    const token_endpoint         = 'https://accounts.zoho.com/oauth/v2/token';
    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    public $data_center;
    private static $instance;

    public static function get_instance() {

        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {

        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'adfoin_zohocampaigns_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'adfoin_zohocampaigns_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'adfoin_zohocampaigns_settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_zohocampaigns_list', array( $this, 'get_zohocampaigns_list' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_zohocampaigns_contact_fifelds', array( $this, 'get_zohocampaigns_contact_fields' ), 10, 0 );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );
        add_action( 'wp_ajax_adfoin_get_zohocampaigns_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2);
        add_action( 'wp_ajax_adfoin_save_zohocampaigns_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohocampaigns',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_webhook_data' ),
                'permission_callback' => '__return_true'
            )
        );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();

        $code = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $state = isset( $params['state'] ) ? trim( $params['state'] ) : '';
        $this->cred_id = $state;

        if ( $code ) {
            // This is the REST API callback.
            // We need to process the token and then return HTML to close the popup.
            
            // 1. Set credentials context
            $zoho_credentials = adfoin_read_credentials( 'zohocampaigns' );
            foreach( $zoho_credentials as $value ) {
                if( $value['id'] == $state ) {
                    $this->data_center   = isset( $value['data_center'] ) ? $value['data_center'] : 'com';
                    $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : '';
                    $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : '';
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
                    $message = isset($body['error']) ? $body['error'] : 'Token exchange failed.';
                }
            } else {
                $message = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP Error';
            }

            // 4. Output Close Popup HTML
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
            exit;
        }
    }

    public function adfoin_zohocampaigns_actions( $actions ) {

        $actions['zohocampaigns'] = array(
            'title' => __( 'ZOHO Campaigns', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' )
            )
        );

        return $actions;
    }

    public function adfoin_zohocampaigns_settings_tab( $providers ) {
        $providers['zohocampaigns'] = __( 'ZOHO Campaigns', 'advanced-form-integration' );

        return $providers;
    }

    function get_credentials() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $all_credentials = adfoin_read_credentials( 'zohocampaigns' );
        wp_send_json_success( $all_credentials );
    }

    function modify_credentials( $credentials, $platform ) {

        if ( 'zohocampaigns' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_zohocampaigns_keys' ) );

            if ( isset( $option['data_center'] ) && isset( $option['client_id'] ) && isset( $option['client_secret'] ) ) {
                $credentials[] = array(
                    'id'           => 'legacy',
                    'title'        => __( 'Legacy Account', 'advanced-form-integration' ),
                    'data_center'  => $option['data_center'],
                    'client_id'    => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : ''
                );
            }
        }

        return $credentials;
    }

    /*
    * Save Zoho Campaigns credentials
    */
    function save_credentials() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform = 'zohocampaigns';
        $credentials = adfoin_read_credentials( $platform );
        if (!is_array($credentials)) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[$index] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( 'Invalid index' );
        }

        // Handle Save/Update
        $id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
        $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $client_id = isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '';
        $data_center = isset( $_POST['data_center'] ) ? sanitize_text_field( $_POST['data_center'] ) : 'com';

        if ( empty( $id ) ) {
            $id = uniqid();
            $new_entry = true;
        } else {
            $new_entry = false;
        }

        $new_data = array(
            'id' => $id,
            'title' => $title,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'data_center' => $data_center,
            'access_token' => '', // Reset on save/update
            'refresh_token' => ''
        );

        // If updating, preserve tokens if client ID/Secret haven't changed
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if not explicitly clearing them
                if ( isset($cred['client_id']) && $cred['client_id'] == $client_id && 
                     isset($cred['client_secret']) && $cred['client_secret'] == $client_secret && 
                     isset($cred['data_center']) && $cred['data_center'] == $data_center ) {
                     $new_data['access_token'] = isset($cred['access_token']) ? $cred['access_token'] : '';
                     $new_data['refresh_token'] = isset($cred['refresh_token']) ? $cred['refresh_token'] : '';
                }
                $cred = $new_data;
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Generate Auth URL
        $redirect_uri = $this->get_redirect_uri();
        $scope = 'ZohoCampaigns.contact.READ,ZohoCampaigns.contact.UPDATE';
        
        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => $redirect_uri,
            'scope'         => $scope,
            'state'         => $id
        ), $auth_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function adfoin_zohocampaigns_settings_view( $current_tab ) {
        if( $current_tab != 'zohocampaigns' ) {
            return;
        }
        
        $redirect_uri = $this->get_redirect_uri();
        
        // Define fields
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
            array(
                'name'          => 'data_center',
                'label'         => __( 'Data Center', 'advanced-form-integration' ),
                'type'          => 'select',
                'required'      => false,
                'show_in_table' => false,
                'options'       => array(
                    'com'    => 'zoho.com',
                    'eu'     => 'zoho.eu',
                    'in'     => 'zoho.in',
                    'com.cn' => 'zoho.com.cn',
                    'com.au' => 'zoho.com.au',
                    'jp'     => 'zoho.jp',
                ),
            ),
        );
        
        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __('Go to %s.', 'advanced-form-integration'), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho Campaigns API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __('Click Add Client, Choose Server-based Applications.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li>' . __('Insert a suitable Client Name.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li>' . __('Insert URL of your website as Homepage URL.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li>' . __('Copy the Redirect URI below and paste in Authorized Redirect URIs input box.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __('Click CREATE.', 'advanced-form-integration') . '</li>';
        $instructions .= '<li>' . __('Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration') . '</li>';
        $instructions .= '</ol>';
        
        // Configuration
        $config = array(
            'show_status'  => true,
            'modal_title'  => __( 'Connect Zoho Campaigns', 'advanced-form-integration' ),
            'submit_text'  => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'zohocampaigns', 'Zoho Campaigns', $fields, $instructions, $config );
    }

    public function adfoin_save_zohocampaigns_keys() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_zohocampaigns_settings' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $data_center   = isset( $_POST['zoho_data_center'] ) ? sanitize_text_field( $_POST['zoho_data_center'] ) : 'com';
        $client_id     = isset( $_POST['adfoin_zohocampaigns_client_id'] ) ? sanitize_text_field( $_POST['adfoin_zohocampaigns_client_id'] ) : '';
        $client_secret = isset( $_POST['adfoin_zohocampaigns_client_secret'] ) ? sanitize_text_field( $_POST['adfoin_zohocampaigns_client_secret'] ) : '';

        if( !$client_id || !$client_secret ) {
            $this->reset_data();
        } else{
            $this->data_center   = trim( $data_center );
            $this->client_id     = trim( $client_id );
            $this->client_secret = trim( $client_secret );
        }

        $this->save_data();
        $this->authorize( 'ZohoCampaigns.contact.READ,ZohoCampaigns.contact.UPDATE' );

        advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=zohocampaigns" );
    }


    protected function request_token( $authorization_code ) {

        $tok_endpoint = $this->get_oauth_endpoint( 'token', $this->data_center );

        $endpoint = add_query_arg(
            array(
                'code'         => $authorization_code,
                'redirect_uri' => urlencode( $this->get_redirect_uri() ),
                'grant_type'   => 'authorization_code',
            ),
            $tok_endpoint
        );

        $request = [
            'headers' => [
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ],
        ];

        $response      = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
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

        $endpoint = add_query_arg(
            array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
            ),
            $ref_endpoint
        );

        $request = [
            'headers' => array(
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ),
        ];

        $response      = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
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

    public function action_fields() {
        ?>
        <script type="text/template" id="zohocampaigns-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">

                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Zoho Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                            <?php
                                $this->get_credentials_list();
                            ?>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohocampaigns' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Mailing List', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                            <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                            <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            </table>
        </script>
        <?php
    }

    public function auth_redirect() {

        $auth   = isset( $_GET['auth'] ) ? trim( $_GET['auth'] ) : '';
        $code   = isset( $_GET['code'] ) ? trim( $_GET['code'] ) : '';
        $action = isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '';

        if ( 'adfoin_zohocampaigns_auth_redirect' == $action ) {
            $code = isset( $_GET['code'] ) ? $_GET['code'] : '';

            if ( $code ) {
                $this->request_token( $code );
            }

            if ( ! empty( $this->access_token ) ) {
                $message = 'success';
            } else {
                $message = 'failed';
            }

            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohocampaigns' ) );

            exit();
        }
    }

    protected function save_data() {
        
        $data = array(
            'data_center'  => $this->data_center,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'id'            => $this->cred_id
        );

        $zohocampaigns_credentials = adfoin_read_credentials( 'zohocampaigns' );

        foreach( $zohocampaigns_credentials as &$value ) {
            if( $value['id'] == $this->cred_id ) {
                if( $this->access_token ){
                    $value['access_token'] = $this->access_token;
                }
                if( $this->refresh_token ){
                    $value['refresh_token'] = $this->refresh_token;
                }
            }
        }

        adfoin_save_credentials( 'zohocampaigns', $zohocampaigns_credentials );

    }



    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohocampaigns' );
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
        );

        return $map[ $data_center ] ?? $map['com'];
    }

    /**
     * Resolve full OAuth endpoint (auth/token) by data center.
     */
    protected function get_oauth_endpoint( $path = 'auth', $data_center = '' ) {
        $base = $this->get_accounts_base( $data_center ? $data_center : $this->data_center );
        return trailingslashit( $base ) . 'oauth/v2/' . $path;
    }

    /**
     * Update stored OAuth endpoints to match a data center.
     */
    protected function update_oauth_endpoints( $data_center ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $this->token_endpoint         = $this->get_oauth_endpoint( 'token', $data_center );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    /**
     * Resolve Zoho Campaigns API base URL by data center.
     */
    protected function get_campaigns_base_url() {
        $base = 'https://campaigns.zoho.com/api/v1.1/';

        $map = array(
            'com'          => 'https://campaigns.zoho.com/api/v1.1/',
            'eu'           => 'https://campaigns.zoho.eu/api/v1.1/',
            'in'           => 'https://campaigns.zoho.in/api/v1.1/',
            'com.cn'       => 'https://campaigns.zoho.com.cn/api/v1.1/',
            'com.au'       => 'https://campaigns.zoho.com.au/api/v1.1/',
            'jp'           => 'https://campaigns.zoho.jp/api/v1.1/',
        );

        if( $this->data_center && isset( $map[ $this->data_center ] ) ) {
            $base = $map[ $this->data_center ];
        }

        return $base;
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );

        if( empty( $credentials ) ) {
            return;
        }

        $this->data_center   = $credentials['data_center'] ?? 'com';
        $this->client_id     = $credentials['client_id'] ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->access_token  = $credentials['access_token'] ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? '';
        $this->cred_id       = $credentials['id'];
        $this->update_oauth_endpoints( $this->data_center );
    }

    public function zohocampaigns_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {

        $base_url = $this->get_campaigns_base_url();

        $url = $base_url . $endpoint;

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );

        if ('POST' == $method || 'PUT' == $method) {
            if( $data ) {
                $args['body'] = json_encode( $data );
            }
        
        }

        $response = $this->remote_request($url, $args, $record );

        return $response;
    }

    public function create_contact( $listkey, $properties, $record ) {

        $endpoint = $this->get_campaigns_base_url() . 'json/listsubscribe';

        $request = array(
            'method'  => 'POST',
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        );

        $data = array(
            'resfmt' => 'JSON',
            'listkey' => $listkey,
            'contactinfo' => json_encode( $properties )
        );

        $url      = add_query_arg( $data, $endpoint );
        $response = $this->remote_request( $url, $request );

        adfoin_add_to_log( $response, $url, $data, $record );

        return $response;
    }

    public function get_zohocampaigns_list() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
        $this->set_credentials( $cred_id );

        $this->get_contact_lists();
    }

    public function get_contact_lists() {

        $endpoint = $this->get_campaigns_base_url() . "getmailinglists?resfmt=JSON&sort=desc&fromindex=0&range=100";

        $request = [
            'method'  => 'GET',
            'headers' => [
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Zoho-oauthtoken ' . $this->access_token
            ],
        ];

        $response = $this->remote_request( $endpoint, $request );

        adfoin_add_to_log( $response, $endpoint, $request, array( "id" => "999" ) );

        if( is_wp_error( $response ) ) {
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );

        if ( empty( $response_body ) ) {
            return false;
        }

        $response_body = json_decode( $response_body, true );

        if ( isset( $response_body['list_of_details'] ) && !empty( $response_body['list_of_details'] ) ) {
            $lists = wp_list_pluck( $response_body['list_of_details'], 'listname', 'listkey' );

            wp_send_json_success( $lists );
        } else {
            wp_send_json_error();
        }
    }

    public function get_zohocampaigns_contact_fields() {

        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
        $this->set_credentials( $cred_id );

        $endpoint = $this->get_campaigns_base_url() . "contact/allfields?type=json";

        $request = [
            'method'  => 'GET',
            'headers' => [
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Zoho-oauthtoken ' . $this->access_token
            ],
        ];

        $response = $this->remote_request( $endpoint, $request );

        adfoin_add_to_log( $response, $endpoint, $request, array( "id" => "999" ) );

        if( is_wp_error( $response ) ) {
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );

        if ( empty( $response_body ) ) {
            return array();
        }

        $response_body = json_decode( $response_body, true );
        $fields        = array();

        if ( isset( $response_body['response']['fieldnames']['fieldname'] ) && !empty( $response_body['response']['fieldnames']['fieldname'] ) ) {
            foreach( $response_body['response']['fieldnames']['fieldname'] as $field ) {
                array_push( $fields, array( 'key' => $field['DISPLAY_NAME'], 'value' => $field['DISPLAY_NAME'], 'descriptioin' => '' ) );
            }

            wp_send_json_success( $fields );
        } else {
            wp_send_json_error();
        }
    }

    protected function remote_request( $url, $request = array() ) {

        static $refreshed = false;

        $request['headers'] = array_merge(
            $request['headers'],
            array( 'Authorization' => $this->get_http_authorization_header( 'bearer' ), )
            
        );

        $response = wp_remote_request( $url, $request );

        if ( 401 === wp_remote_retrieve_response_code( $response )
            and !$refreshed
        ) {
            $this->refresh_token();
            $refreshed = true;

            $response = $this->remote_request( $url, $request );
        }

        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if( isset( $response_body["message"] ) ) {
            if ( "Unauthorized request." == $response_body["message"] ) {
                $this->refresh_token();
                $refreshed = true;

                $response = $this->remote_request( $url, $request );
            }
        }

        return $response;
    }

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'zohocampaigns' );
    
        foreach( $credentials as $option ) {
            $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
        }
    
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials     = array();
        $all_credentials = adfoin_read_credentials( 'zohocampaigns' );
    
        if( is_array( $all_credentials ) ) {
            $credentials = $all_credentials[0];
    
            foreach( $all_credentials as $single ) {
                if( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                }
            }
        }
        
        return $credentials;
    }
}

$zohocampaigns = ADFOIN_ZohoCampaigns::get_instance();

add_action( 'adfoin_zohocampaigns_job_queue', 'adfoin_zohocampaigns_job_queue', 10, 1 );

function adfoin_zohocampaigns_job_queue( $data ) {
    adfoin_zohocampaigns_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zoho Campaign API
 */
function adfoin_zohocampaigns_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data['field_data'];
    $list_id = $data['listId'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = $record['task'];

    // Backward compatibility: If no credId, use the first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'zohocampaigns' );
        if ( ! empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = isset( $first_credential['id'] ) ? $first_credential['id'] : '';
        }
    }

    unset( $data['credId'] );

    if( $task == 'subscribe' ) {
        $email      = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values($data['firstName'], $posted_data);
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values($data['lastName'], $posted_data);

        $properties = array(
            'Contact Email' => trim( $email )
        );

        if( $first_name ) { $properties['First Name'] = $first_name; }
        if( $last_name ) { $properties['Last Name'] = $last_name; }

        $zohocampaigns = ADFOIN_ZohoCampaigns::get_instance();
        $zohocampaigns->set_credentials( $cred_id );
        $return = $zohocampaigns->create_contact( $list_id, $properties, $record );
    }

    return;
}