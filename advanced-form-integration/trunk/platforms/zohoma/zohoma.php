<?php

class ADFOIN_ZohoMA extends Advanced_Form_Integration_OAuth2 {
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
            array($this, 'adfoin_zohoma_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_zohoma_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_zohoma_settings_view'),
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
            'wp_ajax_adfoin_get_zohoma_lists',
            array($this, 'get_lists'),
            10,
            0
        );
        // add_action( 'wp_ajax_adfoin_get_zohoma_owners', array( $this, 'get_owners' ), 10, 0 );
        // add_action( 'wp_ajax_adfoin_get_zohoma_departments', array( $this, 'get_departments' ), 10, 0 );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_action( 'wp_ajax_adfoin_get_zohoma_fields', array($this, 'get_fields') );
        add_action(
            'wp_ajax_adfoin_get_zohoma_credentials',
            array($this, 'get_credentials'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_save_zohoma_credentials',
            array($this, 'save_credentials'),
            10,
            0
        );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohoma', array(
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
            // Set credentials context
            $zoho_credentials = adfoin_read_credentials( 'zohoma' );
            foreach ( $zoho_credentials as $value ) {
                if ( $value['id'] == $state ) {
                    $this->data_center = $value['data_center'] ?? $value['dataCenter'] ?? 'com';
                    $this->client_id = $value['client_id'] ?? $value['clientId'] ?? '';
                    $this->client_secret = $value['client_secret'] ?? $value['clientSecret'] ?? '';
                    $this->update_oauth_endpoints( $this->data_center );
                }
            }
            $response = $this->request_token( $code );
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
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
            exit;
        }
    }

    public function adfoin_zohoma_actions( $actions ) {
        $actions['zohoma'] = array(
            'title' => __( 'Zoho Marketing Automation', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Add New Lead', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    function get_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $all_credentials = adfoin_read_credentials( 'zohoma' );
        if ( is_array( $all_credentials ) ) {
            foreach ( $all_credentials as &$cred ) {
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

    /*
     * Save Zoho Marketing Automation credentials
     */
    function save_credentials() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = 'zohoma';
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
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
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
        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $redirect_uri = $this->get_redirect_uri();
        // Required Zoho Marketing Automation scopes for lists, leads, and metadata APIs
        $scope = 'ZohoMarketingAutomation.lead.ALL';
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

    public function adfoin_zohoma_settings_tab( $providers ) {
        $providers['zohoma'] = __( 'Zoho Marketing Automation', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_zohoma_settings_view( $current_tab ) {
        if ( $current_tab != 'zohoma' ) {
            return;
        }
        $redirect_uri = $this->get_redirect_uri();
        // Define fields for the OAuth Manager
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
        $instructions .= '<li>' . sprintf( __( 'Go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Add Client, choose Server-based Applications.', 'advanced-form-integration' ) . '</li>';
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
            'modal_title' => __( 'Connect Zoho Marketing Automation', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'zohoma',
            'Zoho Marketing Automation',
            $fields,
            $instructions,
            $config
        );
    }

    protected function authorize( $scope = '' ) {
        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => $this->get_redirect_uri(),
        );
        if ( $scope ) {
            $data['scope'] = $scope;
        } else {
            $data['scope'] = 'ZohoMarketingAutomation.lead.ALL';
        }
        $auth_endpoint = $this->authorization_endpoint;
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $auth_endpoint = str_replace( 'com', $this->data_center, $this->authorization_endpoint );
        }
        $endpoint = add_query_arg( $data, $auth_endpoint );
        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $authorization_code ) {
        $tok_endpoint = $this->token_endpoint;
        $tok_endpoint = $this->get_oauth_endpoint( 'token', $this->data_center );
        $endpoint = add_query_arg( array(
            'code'         => $authorization_code,
            'redirect_uri' => $this->get_redirect_uri(),
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
        $ref_endpoint = $this->refresh_token_endpoint;
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
        $credentials = adfoin_read_credentials( 'zohoma' );
        if ( !is_array( $credentials ) ) {
            $credentials = array();
        }
        foreach ( $credentials as $option ) {
            $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
        }
        echo $html;
    }

    function get_credentials_by_id( $cred_id ) {
        $credentials = array();
        $all_credentials = adfoin_read_credentials( 'zohoma' );
        if ( is_array( $all_credentials ) ) {
            foreach ( $all_credentials as $single ) {
                if ( empty( $credentials ) ) {
                    $credentials = $single;
                }
                if ( $cred_id && $cred_id == $single['id'] ) {
                    $credentials = $single;
                    break;
                }
            }
        }
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
        <script type='text/template' id='zohoma-action-template'>
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
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?> </option>
                            <?php 
        $this->get_credentials_list();
        ?>
                        </select>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'List', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=''> <?php 
        _e( 'Select List...', 'advanced-form-integration' );
        ?> </option>
                            <option v-for='(item, index) in fielddata.lists' :value='index' > {{item}}  </option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
            printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
        if ( 'adfoin_zohoma_auth_redirect' == $action ) {
            $code = ( isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' );
            $state = ( isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '' );
            if ( $state && $code ) {
                $this->cred_id = $state;
                $zoho_credentials = adfoin_read_credentials( 'zohoma' );
                foreach ( $zoho_credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->data_center = $value['data_center'] ?? $value['dataCenter'] ?? 'com';
                        $this->client_id = $value['client_id'] ?? $value['clientId'] ?? '';
                        $this->client_secret = $value['client_secret'] ?? $value['clientSecret'] ?? '';
                        $this->update_oauth_endpoints( $this->data_center );
                    }
                }
                $response = $this->request_token( $code );
                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                $success = !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200;
                $message = ( $success ? 'Connected via Legacy Redirect' : (( is_wp_error( $response ) ? $response->get_error_message() : 'Authorization failed' )) );
                ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
            }
        }
    }

    protected function save_data() {
        $zoho_credentials = adfoin_read_credentials( 'zohoma' );
        if ( !is_array( $zoho_credentials ) ) {
            $zoho_credentials = array();
        }
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
        adfoin_save_credentials( 'zohoma', $zoho_credentials );
    }

    protected function reset_data() {
        $this->data_center = 'com';
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';
        $this->cred_id = '';
        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohoma' );
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
     * Resolve Zoho Marketing Automation API base URL by data center.
     */
    protected function get_apis_base_url() {
        $map = array(
            'com'          => 'https://marketinghub.zoho.com/api/v1/',
            'eu'           => 'https://marketinghub.zoho.eu/api/v1/',
            'in'           => 'https://marketinghub.zoho.in/api/v1/',
            'com.cn'       => 'https://marketinghub.zoho.com.cn/api/v1/',
            'com.au'       => 'https://marketinghub.zoho.com.au/api/v1/',
            'jp'           => 'https://marketinghub.zoho.jp/api/v1/',
            'sa'           => 'https://marketinghub.zoho.sa/api/v1/',
            'zohocloud.ca' => 'https://marketinghub.zoho.ca/api/v1/',
            'ca'           => 'https://marketinghub.zoho.ca/api/v1/',
        );
        $base = $map['com'];
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

    public function zohoma_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        $base_url = $this->get_apis_base_url();
        $url = $base_url . $endpoint;
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        );
        if ( 'POST' == $method || 'PUT' == $method ) {
            $args['body'] = $data;
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
    public function get_lists() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohoma_request( 'getmailinglists?resfmt=JSON&range=100' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( isset( $response_body['list_of_details'] ) && is_array( $response_body['list_of_details'] ) ) {
            $lists = array();
            foreach ( $response_body['list_of_details'] as $value ) {
                $lists[$value['listkey']] = $value['listname'];
            }
            wp_send_json_success( $lists );
        } else {
            wp_send_json_error();
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
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        $this->set_credentials( $cred_id );
        $response = $this->zohoma_request( 'lead/allfields?type=json' );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $lead_fields = array();
        if ( isset( $body['response'], $body['response']['fieldnames'], $body['response']['fieldnames']['fieldname'] ) && is_array( $body['response']['fieldnames']['fieldname'] ) ) {
            foreach ( $body['response']['fieldnames']['fieldname'] as $field ) {
                if ( in_array( $field['FIELD_NAME'], array(
                    'do_not_call',
                    'do_not_call_reason',
                    'contact_owner',
                    'preferred_language',
                    'total_sales_activity'
                ) ) ) {
                    continue;
                }
                $description = '';
                if ( 'standard' == $field['TYPE'] ) {
                    if ( isset( $field['values'] ) && !empty( $field['values'] ) ) {
                        $description = $field['values'];
                    }
                    array_push( $lead_fields, array(
                        'key'         => $field['UITYPE'] . '__' . $field['DISPLAY_NAME'],
                        'value'       => $field['DISPLAY_NAME'],
                        'description' => $description,
                    ) );
                }
            }
        }
        wp_send_json_success( $lead_fields );
    }

    public function create_lead( $lead_data, $record ) {
        $response = $this->zohoma_request(
            'json/listsubscribe',
            'POST',
            $lead_data,
            $record
        );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['id'] ) ) {
            return $body['id'];
        }
        return false;
    }

}

$zohoma = ADFOIN_ZohoMA::get_instance();
add_action(
    'adfoin_zohoma_job_queue',
    'adfoin_zohoma_job_queue',
    10,
    1
);
function adfoin_zohoma_job_queue(  $data  ) {
    adfoin_zohoma_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zoho API
 */
function adfoin_zohoma_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $list_id = ( isset( $data['listId'] ) ? $data['listId'] : '' );
    $task = $record['task'];
    unset($data['credId'], $data['listId']);
    if ( $task == 'subscribe' ) {
        $zohoma = ADFOIN_ZohoMA::get_instance();
        $holder = array();
        $lead_id = '';
        $zohoma->set_credentials( $cred_id );
        foreach ( $data as $key => $value ) {
            list( $type, $field ) = explode( '__', $key );
            if ( $value ) {
                $value = adfoin_get_parsed_values( $value, $posted_data );
                if ( 'datetime' == $type || 'date' == $type ) {
                    $timezone = wp_timezone();
                    $date = date_create( $value, $timezone );
                    if ( $date ) {
                        $value = date_format( $date, 'c' );
                    }
                }
            }
            $holder[$field] = $value;
        }
        $holder = array_filter( $holder );
        $lead_data = array(
            'resfmt'   => 'JSON',
            'listkey'  => $list_id,
            'leadinfo' => wp_json_encode( $holder ),
        );
        $zohoma->create_lead( $lead_data, $record );
    }
    return;
}
