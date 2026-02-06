<?php

class ADFOIN_ZohoPeople extends Advanced_Form_Integration_OAuth2 {

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

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'adfoin_zohopeople_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'adfoin_zohopeople_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'adfoin_zohopeople_settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );
        add_action( 'wp_ajax_adfoin_get_zohopeople_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2);
        add_action( 'wp_ajax_adfoin_save_zohopeople_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohopeople',
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
            // Set credentials context
            $zoho_credentials = adfoin_read_credentials( 'zohopeople' );
            foreach( $zoho_credentials as $value ) {
                if( $value['id'] == $state ) {
                    $this->data_center   = isset( $value['data_center'] ) ? $value['data_center'] : ( $value['dataCenter'] ?? 'com' );
                    $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : ( $value['clientId'] ?? '' );
                    $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : ( $value['clientSecret'] ?? '' );
                    $this->update_oauth_endpoints( $this->data_center );
                }
            }

            // Request Token
            $response = $this->request_token( $code );
            
            // Check success
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

            // Output Close Popup HTML
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
            exit;
        }
    }

    public function adfoin_zohopeople_actions( $actions ) {
        $actions['zohopeople'] = array(
            'title' => __( 'Zoho People', 'advanced-form-integration' ),
            'tasks' => array(
                'create_employee' => __( 'Create Employee', 'advanced-form-integration' )
            )
        );

        return $actions;
    }

    function get_credentials() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $all_credentials = adfoin_read_credentials( 'zohopeople' );
        wp_send_json_success( $all_credentials );
    }

    function modify_credentials( $credentials, $platform ) {
        if ( 'zohopeople' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_zohopeople_keys' ) );

            if ( isset( $option['data_center'] ) && isset( $option['client_id'] ) && isset( $option['client_secret'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy',
                    'title'         => __( 'Legacy Account', 'advanced-form-integration' ),
                    'data_center'   => $option['data_center'],
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : ''
                );
            }
        }

        return $credentials;
    }

    function save_credentials() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform = 'zohopeople';
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
        }

        $new_data = array(
            'id' => $id,
            'title' => $title,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'data_center' => $data_center,
            'access_token' => '',
            'refresh_token' => ''
        );

        // If updating, preserve tokens if credentials haven't changed
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
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
        $scope = 'ZohoPeople.forms.ALL,ZohoPeople.employee.ALL';
        
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

    public function adfoin_zohopeople_settings_tab( $providers ) {
        $providers['zohopeople'] = __( 'Zoho People', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_zohopeople_settings_view( $current_tab ) {
        if( $current_tab != 'zohopeople' ) {
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
                    'com'          => 'zoho.com',
                    'eu'           => 'zoho.eu',
                    'in'           => 'zoho.in',
                    'com.cn'       => 'zoho.com.cn',
                    'com.au'       => 'zoho.com.au',
                    'jp'           => 'zoho.jp',
                    'sa'           => 'zoho.sa',
                    'zohocloud.ca' => 'zohocloud.ca',
                ),
            ),
        );
        
        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __('Go to %s.', 'advanced-form-integration'), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho People API Console</a>' ) . '</li>';
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
            'modal_title'  => __( 'Connect Zoho People', 'advanced-form-integration' ),
            'submit_text'  => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'zohopeople', 'Zoho People', $fields, $instructions, $config );
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='zohopeople-action-template'>
            <table class='form-table'>
                <tr valign='top' v-if="action.task == 'create_employee'">
                    <th scope='row'>
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope='row'></td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'create_employee'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Zoho Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                            <?php
                                $this->get_credentials_list();
                            ?>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohopeople' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>
            </table>
        </script>
        <?php
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

        if ( 'adfoin_zohopeople_auth_redirect' == $action ) {
            $code   = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
            $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

            if( $state && $code ) {
                $this->cred_id = $state;
                $zoho_credentials = adfoin_read_credentials( 'zohopeople' );
                foreach( $zoho_credentials as $value ) {
                    if( $value['id'] == $state ) {
                        $this->data_center   = isset( $value['data_center'] ) ? $value['data_center'] : ( $value['dataCenter'] ?? 'com' );
                        $this->client_id     = isset( $value['client_id'] ) ? $value['client_id'] : ( $value['clientId'] ?? '' );
                        $this->client_secret = isset( $value['client_secret'] ) ? $value['client_secret'] : ( $value['clientSecret'] ?? '' );
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
            'id'            => $this->cred_id
        );

        $zoho_credentials = adfoin_read_credentials( 'zohopeople' );

        foreach( $zoho_credentials as &$value ) {
            if( $value['id'] == $this->cred_id ) {
                if( $this->access_token ){
                    $value['access_token'] = $this->access_token;
                }
                if( $this->refresh_token ){
                    $value['refresh_token'] = $this->refresh_token;
                }
            }
        }

        adfoin_save_credentials( 'zohopeople', $zoho_credentials );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohopeople' );
    }

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

        return $map[ $data_center ] ?? $map['com'];
    }

    protected function get_oauth_endpoint( $path = 'auth', $data_center = '' ) {
        $base = $this->get_accounts_base( $data_center ? $data_center : $this->data_center );
        return trailingslashit( $base ) . 'oauth/v2/' . $path;
    }

    protected function update_oauth_endpoints( $data_center ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $this->token_endpoint         = $this->get_oauth_endpoint( 'token', $data_center );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    protected function get_apis_base_url() {
        $base = 'https://people.zoho.com/api/';

        $map = array(
            'com'          => 'https://people.zoho.com/api/',
            'eu'           => 'https://people.zoho.eu/api/',
            'in'           => 'https://people.zoho.in/api/',
            'com.cn'       => 'https://people.zoho.com.cn/api/',
            'com.au'       => 'https://people.zoho.com.au/api/',
            'jp'           => 'https://people.zoho.jp/api/',
            'sa'           => 'https://people.zoho.sa/api/',
            'zohocloud.ca' => 'https://people.zohocloud.ca/api/',
            'ca'           => 'https://people.zohocloud.ca/api/',
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

        $this->data_center   = $credentials['data_center'] ?? ( $credentials['dataCenter'] ?? 'com' );
        $this->client_id     = $credentials['client_id'] ?? ( $credentials['clientId'] ?? '' );
        $this->client_secret = $credentials['client_secret'] ?? ( $credentials['clientSecret'] ?? '' );
        $this->access_token  = $credentials['access_token'] ?? ( $credentials['accessToken'] ?? '' );
        $this->refresh_token = $credentials['refresh_token'] ?? ( $credentials['refreshToken'] ?? '' );
        $this->cred_id       = $credentials['id'];
        $this->update_oauth_endpoints( $this->data_center );
    }

    /**
     * Format data array into Zoho People format with double quotes
     * Example: {First_Name:"John",Last_Name:"Doe"}
     * 
     * @param array $data Associative array of field => value
     * @return string Formatted string
     */
    private function format_input_data( $data ) {
        if ( empty( $data ) ) {
            return '{}';
        }

        $pairs = array();
        foreach ( $data as $key => $value ) {
            // Escape double quotes and backslashes in values
            $escaped_value = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $value );
            // Format as Key:"value" (with double quotes)
            $pairs[] = $key . ':"' . $escaped_value . '"';
        }

        return '{' . implode( ',', $pairs ) . '}';
    }

    /**
     * Make request to Zoho People JSON API
     * 
     * @param string $form_type Form type (e.g., 'employee')
     * @param string $method API method (e.g., 'insertRecord', 'updateRecord', 'getRecords')
     * @param array $data Data to send
     * @param array $record Integration record for logging
     * @return array|WP_Error Response
     */
    public function zohopeople_json_api_request( $form_type, $method, $data = array(), $record = array() ) {
        $base_url = $this->get_apis_base_url();
        $http_method = ( $method == 'getRecords' ) ? 'GET' : 'POST';
        
        $url = $base_url . 'forms/json/' . $form_type . '/' . $method;

        if( $method == 'getRecords' ) {
            $url = $base_url . 'forms/' . $form_type . '/' . $method;
            //add input data to url with searchParams
            if ( ! empty( $data ) ) {
                $input_data = $this->format_input_data( $data );
                $url = add_query_arg( 'searchParams', urlencode( $input_data ), $url );
            }
        }

        $args = array(
            'timeout' => 30,
            'method'  => $http_method,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        );

        // Format data and send as form-encoded body
        if ( ! empty( $data ) && ( $http_method == 'POST' ) ) {
            $body = array();
            $field_data = $data;

            // recordId must be its own parameter for updateRecord
            if ( isset( $field_data['recordId'] ) ) {
                $body['recordId'] = $field_data['recordId'];
                unset( $field_data['recordId'] );
            }

            // Optional params that should not live inside inputData
            foreach ( array( 'isDraft', 'tabularData' ) as $extra_param ) {
                if ( isset( $field_data[ $extra_param ] ) ) {
                    $body[ $extra_param ] = $field_data[ $extra_param ];
                    unset( $field_data[ $extra_param ] );
                }
            }

            // Avoid duplicate value errors on update when EmployeeID is present
            if ( $method === 'updateRecord' && isset( $field_data['EmployeeID'] ) ) {
                unset( $field_data['EmployeeID'] );
            }

            $input_data = $this->format_input_data( $field_data );
            $body['inputData'] = $input_data;

            $args['body'] = http_build_query( $body );
        }

        $response = $this->remote_request( $url, $args, $record );
        return $response;
    }

    /**
     * Legacy method - kept for backward compatibility
     * @deprecated Use zohopeople_json_api_request() instead
     */
    public function zohopeople_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
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

        if ('POST' == $method || 'PUT' == $method) {
            if( $data ) {
                $args['body'] = json_encode( $data );
            }
        }

        $response = $this->remote_request($url, $args, $record );
        return $response;
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;

        $request = wp_parse_args( $request, [ ] );

        $request['headers'] = array_merge(
            $request['headers'],
            array( 'Authorization' => 'Zoho-oauthtoken ' . $this->access_token )
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        if ( 401 === wp_remote_retrieve_response_code( $response ) and !$refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $response = $this->remote_request( $url, $request, $record );
        }

        if( $record ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }

    public function get_credentials_list() {
        $html = '';
        $credentials = adfoin_read_credentials( 'zohopeople' );
    
        foreach( $credentials as $option ) {
            $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
        }
    
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials     = array();
        $all_credentials = adfoin_read_credentials( 'zohopeople' );
    
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

ADFOIN_ZohoPeople::get_instance();

add_action( 'adfoin_zohopeople_job_queue', 'adfoin_zohopeople_job_queue', 10, 1 );

function adfoin_zohopeople_job_queue( $data ) {
    adfoin_zohopeople_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_zohopeople_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data      = $record_data['field_data'];
    $cred_id   = isset( $data['credId'] ) ? $data['credId'] : '';
    $task      = $record['task'];

    // Backward compatibility
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'zohopeople' );
        if ( ! empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = isset( $first_credential['id'] ) ? $first_credential['id'] : '';
        }
    }

    unset( $data['credId'] );

    if( $task == 'create_employee' ) {
        $zohopeople = ADFOIN_ZohoPeople::get_instance();
        $zohopeople->set_credentials( $cred_id );
        $holder = array();

        foreach ( $data as $key => $value ) {
            $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
        }

        if( $holder ) {
            // Search for existing employee by email
            $email_value = '';
            $email_field = '';
            
            // Look for email field (common variations)
            foreach ( array( 'Email_ID', 'EmailID', 'Email', 'email', 'email_id' ) as $field ) {
                if ( isset( $holder[$field] ) && ! empty( $holder[$field] ) ) {
                    $email_value = $holder[$field];
                    $email_field = $field;
                    break;
                }
            }

            $record_id = null;

            // If email found, search for existing employee
            if ( $email_value && $email_field ) {
                $search_response = $zohopeople->zohopeople_json_api_request( 
                    'employee', 
                    'getRecords', 
                    array( 
                        'searchField' => $email_field,
                        'searchOperator' => 'Is',
                        'searchText' => $email_value 
                    ),
                    array() // Don't log search request
                );

                if ( ! is_wp_error( $search_response ) ) {
                    $search_body = json_decode( wp_remote_retrieve_body( $search_response ), true );
                    
                    // Check if employee found
                    if ( isset( $search_body['response']['status'] ) && 
                         $search_body['response']['status'] == 0 && 
                         isset( $search_body['response']['result'] ) && 
                         is_array( $search_body['response']['result'] ) && 
                         count( $search_body['response']['result'] ) > 0 ) {
                        
                        // Get the record ID from first result
                        $result_array = $search_body['response']['result'][0] ?? $search_body['response']['result'][0] ?? null;
                        //now recurively search for Zoho_ID key
                        if ( is_array( $result_array ) ) {
                            $iterator = new RecursiveIteratorIterator( new RecursiveArrayIterator( $result_array ) );
                            $found_id = false;
                            foreach ( $iterator as $key => $value ) {
                                if ( $key === 'Zoho_ID' ) {
                                    $record_id = $value;
                                    $found_id = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            // Update if found, otherwise create
            if ( $record_id ) {
                // Add record ID to data for update
                $holder['recordId'] = $record_id;
                $return = $zohopeople->zohopeople_json_api_request( 'employee', 'updateRecord', $holder, $record );
            } else {
                // Create new employee
                $return = $zohopeople->zohopeople_json_api_request( 'employee', 'insertRecord', $holder, $record );
            }
        }
    }

    return;
}
