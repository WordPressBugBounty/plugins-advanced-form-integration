<?php

class ADFOIN_ZohoCRM extends Advanced_Form_Integration_OAuth2 {
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
            array($this, 'adfoin_zohocrm_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_zohocrm_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_zohocrm_settings_view'),
            10,
            1
        );
        // add_action( 'admin_post_adfoin_save_zohocrm_keys', array( $this, 'adfoin_save_zohocrm_keys' ), 10, 0 ); // Deprecated in favor of AJAX
        add_action(
            'adfoin_action_fields',
            array($this, 'action_fields'),
            10,
            1
        );
        add_action(
            'wp_ajax_adfoin_get_zohocrm_users',
            array($this, 'get_users'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_get_zohocrm_modules',
            array($this, 'get_modules'),
            10,
            0
        );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_zohocrm_module_fields', array($this, 'get_fields') );
        add_action(
            'wp_ajax_adfoin_get_zohocrm_credentials',
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
            'wp_ajax_adfoin_save_zohocrm_credentials',
            array($this, 'save_credentials'),
            10,
            0
        );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
    }

    public function enqueue_scripts() {
        // No longer needed - JavaScript is now inline in OAuth Manager
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohocrm', array(
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
            $zoho_credentials = adfoin_read_credentials( 'zohocrm' );
            foreach ( $zoho_credentials as $value ) {
                if ( $value['id'] == $state ) {
                    $this->data_center = ( isset( $value['dataCenter'] ) ? $value['dataCenter'] : $value['data_center'] ?? 'com' );
                    $this->client_id = ( isset( $value['clientId'] ) ? $value['clientId'] : $value['client_id'] ?? '' );
                    $this->client_secret = ( isset( $value['clientSecret'] ) ? $value['clientSecret'] : $value['client_secret'] ?? '' );
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

    public function adfoin_zohocrm_actions( $actions ) {
        $actions['zohocrm'] = array(
            'title' => __( 'Zoho CRM', 'advanced-form-integration' ),
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
        $all_credentials = adfoin_read_credentials( 'zohocrm' );
        // Convert old camelCase format to new snake_case format for backward compatibility
        if ( is_array( $all_credentials ) ) {
            foreach ( $all_credentials as &$cred ) {
                // Convert camelCase to snake_case
                if ( isset( $cred['clientId'] ) && !isset( $cred['client_id'] ) ) {
                    $cred['client_id'] = $cred['clientId'];
                }
                if ( isset( $cred['clientSecret'] ) && !isset( $cred['client_secret'] ) ) {
                    $cred['client_secret'] = $cred['clientSecret'];
                }
                if ( isset( $cred['dataCenter'] ) && !isset( $cred['data_center'] ) ) {
                    $cred['data_center'] = $cred['dataCenter'];
                }
                if ( isset( $cred['accessToken'] ) && !isset( $cred['access_token'] ) ) {
                    $cred['access_token'] = $cred['accessToken'];
                }
                if ( isset( $cred['refreshToken'] ) && !isset( $cred['refresh_token'] ) ) {
                    $cred['refresh_token'] = $cred['refreshToken'];
                }
            }
        }
        wp_send_json_success( $all_credentials );
    }

    function modify_credentials( $credentials, $platform ) {
        if ( 'zohocrm' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_zohocrm_keys' ) );
            if ( !empty( $option ) && isset( $option['data_center'] ) && isset( $option['client_id'] ) && isset( $option['client_secret'] ) ) {
                $credentials[] = array(
                    'id'           => '123456',
                    'title'        => __( 'Untitled', 'advanced-form-integration' ),
                    'dataCenter'   => $option['data_center'],
                    'clientId'     => $option['client_id'],
                    'clientSecret' => $option['client_secret'],
                    'accessToken'  => $option['access_token'],
                    'refreshToken' => $option['refresh_token'],
                );
            }
        }
        return $credentials;
    }

    /*
     * Save Zoho CRM credentials
     */
    function save_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = 'zohocrm';
        $credentials = adfoin_read_credentials( $platform );
        if ( !is_array( $credentials ) ) {
            $credentials = array();
        }
        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[$index] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[$index]['id'] ) && $credentials[$index]['id'] === '123456' ) {
                    delete_option( 'adfoin_zohocrm_keys' );
                }
                unset($credentials[$index]);
                adfoin_save_credentials( $platform, array_values( $credentials ) );
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
        $scope = 'ZohoCRM.Files.READ,ZohoCRM.Files.CREATE,ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.users.ALL,ZohoCRM.coql.READ,ZohoCRM.settings.tags.ALL,ZohoCRM.change_owner.CREATE';
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

    public function adfoin_zohocrm_settings_tab( $providers ) {
        $providers['zohocrm'] = __( 'Zoho CRM', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_zohocrm_settings_view( $current_tab ) {
        if ( $current_tab != 'zohocrm' ) {
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
        $instructions .= '<li>' . sprintf( __( 'Go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho CRM API Console</a>' ) . '</li>';
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
            'modal_title' => __( 'Connect Zoho CRM', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'zohocrm',
            'Zoho CRM',
            $fields,
            $instructions,
            $config
        );
    }

    public function auth_redirect() {
        // This is for the old query-param based redirect if still used,
        // but we are moving to REST API callback for the popup flow mostly.
        // However, if the user manually hits the URL or Zoho redirects here (if not using REST API),
        // we should handle it.
        // The REST API route /wp-json/advancedformintegration/zohocrm is the preferred redirect_uri now.
        $action = ( isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '' );
        if ( 'adfoin_zohocrm_auth_redirect' == $action ) {
            // ... Logic similar to get_webhook_data but for admin-ajax/admin-post style redirects ...
            // Since we updated the redirect_uri to be the REST endpoint in get_redirect_uri(),
            // this might not be hit anymore unless old apps use it.
            // But let's keep it safe.
            $code = ( isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' );
            $state = ( isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '' );
            if ( $state && $code ) {
                $this->cred_id = $state;
                $zoho_credentials = adfoin_read_credentials( 'zohocrm' );
                foreach ( $zoho_credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->data_center = ( isset( $value['dataCenter'] ) ? $value['dataCenter'] : $value['data_center'] ?? 'com' );
                        $this->client_id = ( isset( $value['clientId'] ) ? $value['clientId'] : $value['client_id'] ?? '' );
                        $this->client_secret = ( isset( $value['clientSecret'] ) ? $value['clientSecret'] : $value['client_secret'] ?? '' );
                        $this->update_oauth_endpoints( $this->data_center );
                    }
                }
                $this->request_token( $code );
                // For popup flow, we should output the close script even here?
                // If the user opened it in a popup, yes.
                // If they opened it in the same window (old behavior), we should redirect back.
                // The new JS opens a popup. So we should assume popup.
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
        $zoho_credentials = adfoin_read_credentials( 'zohocrm' );
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
        adfoin_save_credentials( 'zohocrm', $zoho_credentials );
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
        return site_url( '/wp-json/advancedformintegration/zohocrm' );
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

    /**
     * Resolve Zoho API base URL (for CRM APIs) by data center.
     */
    protected function get_apis_base_url() {
        $base = 'https://www.zohoapis.com/crm/v3/';
        $map = array(
            'com'          => 'https://www.zohoapis.com/crm/v3/',
            'eu'           => 'https://www.zohoapis.eu/crm/v3/',
            'in'           => 'https://www.zohoapis.in/crm/v3/',
            'com.cn'       => 'https://www.zohoapis.com.cn/crm/v3/',
            'com.au'       => 'https://www.zohoapis.com.au/crm/v3/',
            'jp'           => 'https://www.zohoapis.jp/crm/v3/',
            'sa'           => 'https://www.zohoapis.sa/crm/v3/',
            'zohocloud.ca' => 'https://www.zohoapis.ca/crm/v3/',
            'ca'           => 'https://www.zohoapis.ca/crm/v3/',
        );
        if ( $this->data_center && isset( $map[$this->data_center] ) ) {
            $base = $map[$this->data_center];
        }
        return $base;
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

    public function zohocrm_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        $base_url = $this->get_apis_base_url();
        $url = $base_url . $endpoint;
        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );
        if ( 'POST' == $method || 'PUT' == $method ) {
            if ( $data ) {
                $args['body'] = json_encode( $data );
            }
        }
        $response = $this->remote_request( $url, $args, $record );
        return $response;
    }

    public function search_record(
        $module,
        $search_key,
        $search_value,
        $record
    ) {
        $result = array(
            'id'   => '',
            'data' => array(),
        );
        if ( empty( $module ) || empty( $search_key ) || '' === $search_value ) {
            return $result;
        }
        $search_value = str_replace( "'", "\\'", $search_value );
        $body = array(
            'select_query' => "select {$search_key}, id, Tag from {$module} where {$search_key} = '{$search_value}'",
        );
        $response = $this->zohocrm_request(
            'coql',
            'POST',
            $body,
            $record
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['data'], $body['data'][0], $body['data'][0]['id'] ) ) {
            $result['id'] = $body['data'][0]['id'];
            $result['data'] = $body['data'][0];
        }
        return $result;
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;
        $request = wp_parse_args( $request, [] );
        $request['headers'] = array_merge( $request['headers'], array(
            'Authorization' => $this->get_http_authorization_header( 'bearer' ),
        ) );
        $response = wp_remote_request( esc_url_raw( $url ), $request );
        if ( 401 === wp_remote_retrieve_response_code( $response ) and !$refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $response = $this->remote_request( $url, $request, $record );
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
     * Get Owners
     */
    public function get_users() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohocrm_request( 'users?type=ActiveUsers' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['users'] ) && is_array( $response_body['users'] ) ) {
            $users = array();
            foreach ( $response_body['users'] as $value ) {
                $users[$value['id']] = $value['full_name'];
            }
            wp_send_json_success( $users );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Modules
     */
    public function get_modules() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohocrm_request( 'settings/modules' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['modules'] ) && is_array( $response_body['modules'] ) ) {
            $skip_list = array(
                'Quotes',
                'Sales_Orders',
                'Purchase_Orders',
                'Invoices',
                'Projects',
                'Notes'
            );
            $modules = array();
            foreach ( $response_body['modules'] as $single ) {
                if ( in_array( $single['api_name'], $skip_list ) ) {
                    continue;
                }
                if ( isset( $single['editable'] ) && true == $single['editable'] && 'Associated_Products' != $single['api_name'] ) {
                    $modules[$single['api_name']] = $single['plural_label'];
                }
            }
            wp_send_json_success( $modules );
        } else {
            wp_send_json_error();
        }
    }

    /*
     * Get Module Fields
     */
    function get_fields() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $final_data = array();
        $module = ( isset( $_POST['module'] ) ? sanitize_text_field( $_POST['module'] ) : '' );
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        if ( $module ) {
            $response = $this->zohocrm_request( "settings/fields?module={$module}&type=all" );
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $body['fields'] ) && is_array( $body['fields'] ) ) {
                $suppression_list = array(
                    'Created_By',
                    'Modified_By',
                    'Created_Time',
                    'Modified_Time',
                    'Layout',
                    'Tag',
                    'Recurring_Activity',
                    'BEST_TIME',
                    'What_Id',
                    'Record_Image'
                );
                foreach ( $body['fields'] as $field ) {
                    $helptext = '';
                    $data_type = $field['data_type'];
                    $api_name = $field['api_name'];
                    $display_label = $field['display_label'];
                    if ( isset( $field['field_read_only'] ) && $field['field_read_only'] == true ) {
                        continue;
                    }
                    if ( in_array( $api_name, $suppression_list ) ) {
                        continue;
                    }
                    if ( $field['custom_field'] == true ) {
                        $display_label .= ' (Custom)';
                    }
                    if ( $field['system_mandatory'] == true ) {
                        $display_label .= ' (Required)';
                    }
                    $final_data[] = array(
                        'key'         => $api_name,
                        'value'       => $display_label,
                        'description' => $helptext,
                    );
                }
            }
        }
        wp_send_json_success( $final_data );
    }

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'zohocrm' );
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials = array();
        $all_credentials = adfoin_read_credentials( 'zohocrm' );
        if ( is_array( $all_credentials ) ) {
            $credentials = $all_credentials[0];
            foreach ( $all_credentials as $single ) {
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                }
            }
        }
        // Ensure snake_case format for consistency
        if ( !empty( $credentials ) ) {
            if ( isset( $credentials['clientId'] ) && !isset( $credentials['client_id'] ) ) {
                $credentials['client_id'] = $credentials['clientId'];
            }
            if ( isset( $credentials['clientSecret'] ) && !isset( $credentials['client_secret'] ) ) {
                $credentials['client_secret'] = $credentials['clientSecret'];
            }
            if ( isset( $credentials['dataCenter'] ) && !isset( $credentials['data_center'] ) ) {
                $credentials['data_center'] = $credentials['dataCenter'];
            }
            if ( isset( $credentials['accessToken'] ) && !isset( $credentials['access_token'] ) ) {
                $credentials['access_token'] = $credentials['accessToken'];
            }
            if ( isset( $credentials['refreshToken'] ) && !isset( $credentials['refresh_token'] ) ) {
                $credentials['refresh_token'] = $credentials['refreshToken'];
            }
        }
        return $credentials;
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='zohocrm-action-template'>
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
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getUsers">
                        <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <?php 
        $this->get_credentials_list();
        ?>
                        </select>
                        <a href="<?php 
        echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohocrm' );
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
        esc_attr_e( 'Zoho User', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[userId]" v-model="fielddata.userId" @change="getModules">
                            <option value=''> <?php 
        _e( 'Select User...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.users' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': userLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Module', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[moduleId]" v-model="fielddata.moduleId" @change=getFields>
                            <option value=''> <?php 
        _e( 'Select Module...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.modules' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': moduleLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
            printf( __( 'To unlock custom fields and tags consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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

}

ADFOIN_ZohoCRM::get_instance();
add_action(
    'adfoin_zohocrm_job_queue',
    'adfoin_zohocrm_job_queue',
    10,
    1
);
function adfoin_zohocrm_job_queue(  $data  ) {
    adfoin_zohocrm_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zoho API
 */
function adfoin_zohocrm_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $owner = ( isset( $data['userId'] ) ? $data['userId'] : '' );
    $module = ( isset( $data['moduleId'] ) ? $data['moduleId'] : '' );
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record['task'];
    // Backward compatibility: If no credId, use the first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'zohocrm' );
        if ( !empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = ( isset( $first_credential['id'] ) ? $first_credential['id'] : '' );
        }
    }
    unset($data['credId']);
    unset($data['userId']);
    unset($data['moduleId']);
    if ( $task == 'subscribe' ) {
        $zohocrm = ADFOIN_ZohoCRM::get_instance();
        $holder = array();
        $account_id = '';
        $contact_id = '';
        $vendor_id = '';
        $campaign_id = '';
        $task_module = '';
        $account_lookups = array('Parent_Account', 'Account_Name');
        $contact_lookups = array('Contact_Name', 'Who_Id', 'Related_To');
        $campaign_lookups = array('Parent_Campaign', 'Campaign_Source');
        $zohocrm->set_credentials( $cred_id );
        foreach ( $data as $key => $value ) {
            // Extract data type and original key from format "type__Field_Name"
            if ( strpos( $key, '__' ) !== false ) {
                list( $data_type, $original_key ) = explode( '__', $key, 2 );
            } else {
                // Legacy format without type prefix
                $data_type = '';
                $original_key = $key;
            }
            $value = adfoin_get_parsed_values( $value, $posted_data );
            if ( 'datetime' == $data_type && $value ) {
                $timezone = wp_timezone();
                $date = date_create( $value, $timezone );
                if ( $date ) {
                    $value = date_format( $date, 'c' );
                }
            }
            if ( 'date' == $data_type && $value ) {
                $date = date_create( $value );
                if ( $date ) {
                    $value = date_format( $date, 'Y-m-d' );
                }
            }
            if ( 'multiselectpicklist' == $data_type && $value ) {
                if ( 'Tax' == $original_key ) {
                    $formatted_tax_ids = array();
                    $tax_ids = explode( ',', $value );
                    foreach ( $tax_ids as $tax_id ) {
                        array_push( $formatted_tax_ids, array(
                            'id' => $tax_id,
                        ) );
                    }
                    $value = $formatted_tax_ids;
                }
            }
            if ( 'bigint' == $data_type && $value ) {
                if ( 'Participants' == $original_key ) {
                    $participants = array();
                    $raw_participants = explode( ',', $value );
                    foreach ( $raw_participants as $single ) {
                        list( $type, $email ) = explode( '--', $single );
                        if ( 'lead' == $type ) {
                            $participant_id = $zohocrm->search_record(
                                'Leads',
                                'Email',
                                $email,
                                $record
                            )['id'];
                            if ( $participant_id ) {
                                array_push( $participants, array(
                                    'type'        => 'lead',
                                    'participant' => $participant_id,
                                ) );
                            }
                        }
                        if ( 'contact' == $type ) {
                            $participant_id = $zohocrm->search_record(
                                'Contacts',
                                'Email',
                                $email,
                                $record
                            )['id'];
                            if ( $participant_id ) {
                                array_push( $participants, array(
                                    'type'        => 'contact',
                                    'participant' => $participant_id,
                                ) );
                            }
                        }
                    }
                    $value = $participants;
                }
            }
            if ( 'lookup' == $data_type && $value ) {
                if ( in_array( $original_key, $account_lookups ) ) {
                    $account_id = $zohocrm->search_record(
                        'Accounts',
                        'Account_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $account_id ) {
                        $value = $account_id;
                    }
                }
                if ( in_array( $original_key, $contact_lookups ) ) {
                    $contact_id = $zohocrm->search_record(
                        'Contacts',
                        'Email',
                        $value,
                        $record
                    )['id'];
                    if ( $contact_id ) {
                        $value = $contact_id;
                    }
                }
                if ( in_array( $original_key, $campaign_lookups ) ) {
                    $campaign_id = $zohocrm->search_record(
                        'Campaigns',
                        'Campaign_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $campaign_id ) {
                        $value = $campaign_id;
                    }
                }
                if ( 'Vendor_Name' == $original_key ) {
                    $vendor_id = $zohocrm->search_record(
                        'Vendors',
                        'Vendor_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $vendor_id ) {
                        $value = $vendor_id;
                    }
                }
                if ( 'Product_Name' == $original_key ) {
                    $product_id = $zohocrm->search_record(
                        'Products',
                        'Product_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $product_id ) {
                        $value = $product_id;
                    }
                }
                if ( 'Deal_Name' == $original_key ) {
                    $deal_id = $zohocrm->search_record(
                        'Deals',
                        'Deal_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $deal_id ) {
                        $value = $deal_id;
                    }
                }
                if ( 'Reporting_To' == $original_key && $account_id ) {
                    $contacts_response = $zohocrm->zohocrm_request( 'Accounts/' . $account_id . '/Contacts?fields=id,First_Name,Last_Name' );
                    $contacts_body = json_decode( wp_remote_retrieve_body( $contacts_response ), true );
                    if ( isset( $contacts_body['data'] ) && is_array( $contacts_body['data'] ) ) {
                        foreach ( $contacts_body['data'] as $contact ) {
                            $contact_name = $contact['First_Name'] . ' ' . $contact['Last_Name'];
                            $contact_id = ( $contact_name == $value ? $contact['id'] : '' );
                            if ( $contact_id ) {
                                $value = $contact_id;
                            }
                        }
                    }
                }
            }
            if ( 'boolean' == $data_type ) {
                if ( strtolower( $value ) == 'true' ) {
                    $value = true;
                } else {
                    $value = false;
                }
            }
            if ( '$se_module' == $original_key ) {
                $task_module = $value;
            }
            if ( 'What_Id' == $original_key ) {
                if ( 'Accounts' == $task_module ) {
                    $account_id = $zohocrm->search_record(
                        'Accounts',
                        'Account_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $account_id ) {
                        $value = $account_id;
                    }
                }
            }
            $holder[$original_key] = $value;
        }
        if ( $owner ) {
            $holder['Owner'] = "{$owner}";
        }
        if ( $module && $holder ) {
            $request_data = array(
                'data' => array(array_filter( $holder )),
            );
            $zohocrm->zohocrm_request(
                $module . '/upsert',
                'POST',
                $request_data,
                $record
            );
        }
    }
    return;
}
