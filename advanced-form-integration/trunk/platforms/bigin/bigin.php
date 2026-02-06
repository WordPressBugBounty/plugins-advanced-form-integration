<?php

class ADFOIN_Bigin extends Advanced_Form_Integration_OAuth2 {
    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';

    const token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    public $data_center;

    public $state;

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;
        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();
        add_action( 'admin_init', array($this, 'auth_redirect') );
        add_action( 'rest_api_init', array($this, 'create_webhook_route') );
        add_filter(
            'adfoin_action_providers',
            array($this, 'adfoin_bigin_actions'),
            10,
            1
        );
        add_filter(
            'adfoin_settings_tabs',
            array($this, 'adfoin_bigin_settings_tab'),
            10,
            1
        );
        add_action(
            'adfoin_settings_view',
            array($this, 'adfoin_bigin_settings_view'),
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
            'wp_ajax_adfoin_get_bigin_users',
            array($this, 'get_users'),
            10,
            0
        );
        add_action(
            'wp_ajax_adfoin_get_bigin_modules',
            array($this, 'get_modules'),
            10,
            0
        );
        add_action( 'wp_ajax_adfoin_get_bigin_module_fields', array($this, 'get_fields') );
        add_action(
            'wp_ajax_adfoin_get_bigin_credentials',
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
            'wp_ajax_adfoin_save_bigin_credentials',
            array($this, 'save_credentials'),
            10,
            0
        );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_bigin_keys' ) );
        if ( isset( $option['data_center'] ) ) {
            $this->data_center = $option['data_center'];
        }
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
    }

    /**
     * Load credentials for a specific account
     */
    public function load_credentials( $cred_id ) {
        if ( empty( $cred_id ) ) {
            return false;
        }
        // Handle legacy credential ID
        if ( strpos( $cred_id, 'legacy_' ) === 0 ) {
            $this->load_legacy_credentials();
            return true;
        }
        // Load from OAuth Manager
        $credentials = adfoin_read_credentials( 'bigin' );
        foreach ( $credentials as $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] == $cred_id ) {
                $this->state = $cred_id;
                $this->data_center = ( isset( $credential['data_center'] ) ? $credential['data_center'] : 'com' );
                $this->client_id = ( isset( $credential['client_id'] ) ? $credential['client_id'] : '' );
                $this->client_secret = ( isset( $credential['client_secret'] ) ? $credential['client_secret'] : '' );
                $this->access_token = ( isset( $credential['access_token'] ) ? $credential['access_token'] : '' );
                $this->refresh_token = ( isset( $credential['refresh_token'] ) ? $credential['refresh_token'] : '' );
                return true;
            }
        }
        return false;
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/bigin', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code = ( isset( $params['code'] ) ? trim( $params['code'] ) : '' );
        $state = ( isset( $params['state'] ) ? trim( $params['state'] ) : '' );
        if ( $code ) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                $this->state = $state;
                $credentials = adfoin_read_credentials( 'bigin' );
                foreach ( $credentials as $value ) {
                    if ( $value['id'] == $state ) {
                        $this->data_center = ( isset( $value['data_center'] ) ? $value['data_center'] : 'com' );
                        $this->client_id = ( isset( $value['client_id'] ) ? $value['client_id'] : '' );
                        $this->client_secret = ( isset( $value['client_secret'] ) ? $value['client_secret'] : '' );
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
            // Legacy flow - redirect to admin
            $redirect_to = add_query_arg( [
                'service' => 'authorize',
                'action'  => 'adfoin_bigin_auth_redirect',
                'code'    => $code,
            ], admin_url( 'admin.php?page=advanced-form-integration' ) );
            wp_safe_redirect( $redirect_to );
            exit;
        }
    }

    public function adfoin_bigin_actions( $actions ) {
        $actions['bigin'] = array(
            'title' => __( 'Bigin', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe' => __( 'Add new record', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function adfoin_bigin_settings_tab( $providers ) {
        $providers['bigin'] = __( 'Bigin', 'advanced-form-integration' );
        return $providers;
    }

    public function adfoin_bigin_settings_view( $current_tab ) {
        if ( $current_tab != 'bigin' ) {
            return;
        }
        $redirect_uri = $this->get_redirect_uri();
        // Define fields for OAuth Manager
        $fields = array(array(
            'name'          => 'data_center',
            'label'         => __( 'Data Center', 'advanced-form-integration' ),
            'type'          => 'select',
            'required'      => true,
            'mask'          => false,
            'show_in_table' => true,
            'options'       => array(
                'com'    => 'zoho.com',
                'eu'     => 'zoho.eu',
                'in'     => 'zoho.in',
                'com.cn' => 'zoho.com.cn',
                'com.au' => 'zoho.com.au',
                'jp'     => 'zoho.jp',
            ),
        ), array(
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
        ));
        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s and create a Server-based Application.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Insert a suitable Client Name and your website URL as Homepage URL.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Authorized Redirect URIs:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy Client ID and Client Secret and paste in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Select the correct data center for your Zoho account.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';
        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Bigin', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'bigin',
            'Bigin',
            $fields,
            $instructions,
            $config
        );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $all_credentials = adfoin_read_credentials( 'bigin' );
        wp_send_json_success( $all_credentials );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'bigin' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_bigin_keys' ) );
            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && !empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'data_center'   => ( isset( $option['data_center'] ) ? $option['data_center'] : 'com' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => ( isset( $option['access_token'] ) ? $option['access_token'] : '' ),
                    'refresh_token' => ( isset( $option['refresh_token'] ) ? $option['refresh_token'] : '' ),
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $platform = 'bigin';
        $credentials = adfoin_read_credentials( $platform );
        if ( !is_array( $credentials ) ) {
            $credentials = array();
        }
        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[$index] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[$index]['id'] ) && strpos( $credentials[$index]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_bigin_keys' );
                }
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
        $data_center = ( isset( $_POST['data_center'] ) ? sanitize_text_field( $_POST['data_center'] ) : 'com' );
        $client_id = ( isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '' );
        $client_secret = ( isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '' );
        if ( empty( $id ) ) {
            $id = uniqid();
        }
        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'data_center'   => $data_center,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
        );
        // Check if updating existing credential
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials haven't changed
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
        $scope = 'ZohoBigin.modules.ALL,ZohoBigin.org.ALL,ZohoBigin.settings.ALL,ZohoBigin.users.ALL,ZohoBigin.coql.READ';
        $auth_endpoint = $this->authorization_endpoint;
        if ( $data_center && $data_center !== 'com' ) {
            $auth_endpoint = str_replace( 'com', $data_center, $this->authorization_endpoint );
        }
        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => $scope,
            'state'         => $id,
        ), $auth_endpoint );
        wp_send_json_success( array(
            'auth_url' => $auth_url,
        ) );
    }

    public function auth_redirect() {
        $action = ( isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '' );
        $code = ( isset( $_GET['code'] ) ? trim( $_GET['code'] ) : '' );
        $state = ( isset( $_GET['state'] ) ? trim( $_GET['state'] ) : '' );
        if ( 'adfoin_bigin_auth_redirect' == $action ) {
            if ( $code ) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->state = $state;
                    $credentials = adfoin_read_credentials( 'bigin' );
                    foreach ( $credentials as $value ) {
                        if ( $value['id'] == $state ) {
                            $this->data_center = ( isset( $value['data_center'] ) ? $value['data_center'] : 'com' );
                            $this->client_id = ( isset( $value['client_id'] ) ? $value['client_id'] : '' );
                            $this->client_secret = ( isset( $value['client_secret'] ) ? $value['client_secret'] : '' );
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
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=bigin' ) );
            exit;
        }
    }

    protected function request_token( $authorization_code ) {
        $tok_endpoint = $this->token_endpoint;
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $tok_endpoint = str_replace( 'com', $this->data_center, $this->token_endpoint );
        }
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
        $ref_endpoint = $this->refresh_token_endpoint;
        if ( $this->data_center && $this->data_center !== 'com' ) {
            $ref_endpoint = str_replace( 'com', $this->data_center, $this->refresh_token_endpoint );
        }
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

    public function action_fields() {
        ?>
        <script type='text/template' id='bigin-action-template'>
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

                <tr valign='top' class='alternate' v-if="action.task == 'subscribe'">
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php 
        esc_attr_e( 'Bigin Account', 'advanced-form-integration' );
        ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getUsers">
                            <option value=""><?php 
        _e( 'Select Account...', 'advanced-form-integration' );
        ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id" > {{item.title}}  </option>
                        </select>
                        <a href="<?php 
        echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=bigin' );
        ?>" 
                           target="_blank" 
                           style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                            <?php 
        esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
        ?>
                        </a>
                        <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
                        <select name="fieldData[userId]" v-model="fielddata.userId">
                            <option value=''> <?php 
        _e( 'Select Owner...', 'advanced-form-integration' );
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

    protected function save_data() {
        // If using OAuth Manager (has state), save to credentials
        if ( !empty( $this->state ) && strpos( $this->state, 'legacy_' ) !== 0 ) {
            $credentials = adfoin_read_credentials( 'bigin' );
            foreach ( $credentials as &$credential ) {
                if ( isset( $credential['id'] ) && $credential['id'] == $this->state ) {
                    $credential['access_token'] = $this->access_token;
                    $credential['refresh_token'] = $this->refresh_token;
                    break;
                }
            }
            adfoin_save_credentials( 'bigin', $credentials );
            return;
        }
        // Legacy save method for backward compatibility
        $data = (array) maybe_unserialize( get_option( 'adfoin_bigin_keys' ) );
        $option = array_merge( $data, array(
            'data_center'   => $this->data_center,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ) );
        update_option( 'adfoin_bigin_keys', maybe_serialize( $option ) );
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
        return site_url( '/wp-json/advancedformintegration/bigin' );
    }

    public function bigin_request(
        $endpoint,
        $method = 'GET',
        $data = array(),
        $record = array()
    ) {
        $base_url = 'https://www.zohoapis.com/bigin/v2/';
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
     * Get Owners
     */
    public function get_users() {
        // Security Check
        if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        if ( empty( $cred_id ) ) {
            wp_send_json_error( __( 'No account selected', 'advanced-form-integration' ) );
        }
        // Load credentials for the selected account
        $this->load_credentials( $cred_id );
        if ( empty( $this->access_token ) ) {
            wp_send_json_error( __( 'Account not connected', 'advanced-form-integration' ) );
        }
        $response = $this->bigin_request( 'users?type=AdminUsers' );
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
        if ( empty( $cred_id ) ) {
            wp_send_json_error( __( 'No account selected', 'advanced-form-integration' ) );
        }
        // Load credentials for the selected account
        $this->load_credentials( $cred_id );
        if ( empty( $this->access_token ) ) {
            wp_send_json_error( __( 'Account not connected', 'advanced-form-integration' ) );
        }
        $response = $this->bigin_request( 'settings/modules' );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $response_body ) ) {
            wp_send_json_error();
        }
        if ( !empty( $response_body['modules'] ) && is_array( $response_body['modules'] ) ) {
            $skip_list = array('Calls');
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
        $module = ( isset( $_POST['module'] ) ? $_POST['module'] : '' );
        $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
        if ( $module ) {
            // Load credentials for the selected account
            if ( $cred_id ) {
                $this->load_credentials( $cred_id );
            }
            $response = $this->bigin_request( "settings/fields?module={$module}&type=all" );
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
                        continue;
                    }
                    if ( 'Contact_Name' == $api_name || 'Who_Id' == $api_name ) {
                        $display_label = 'Contact Email';
                    }
                    if ( 'bigint' == $data_type && 'Participants' == $api_name ) {
                        $helptext = 'Example: lead--john@example.com,contact--david@example.com';
                    }
                    if ( 'multiselectpicklist' == $data_type && 'Tax' == $api_name ) {
                        $items = array();
                        if ( isset( $field['pick_list_values'] ) && is_array( $field['pick_list_values'] ) ) {
                            foreach ( $field['pick_list_values'] as $pick ) {
                                $items[] = $pick['display_value'] . ': ' . $pick['id'];
                            }
                        }
                        $helptext = implode( ', ', $items );
                    }
                    if ( 'picklist' == $data_type && is_array( $field['pick_list_values'] ) ) {
                        $picklist = wp_list_pluck( $field['pick_list_values'], 'actual_value' );
                        $helptext = implode( ' | ', $picklist );
                    }
                    array_push( $final_data, array(
                        'key'         => $data_type . '__' . $api_name,
                        'value'       => $display_label,
                        'description' => $helptext,
                    ) );
                }
                if ( 'Tasks' == $module || 'Events' == $module ) {
                    array_push( $final_data, array(
                        'key'         => 'text__$se_module',
                        'value'       => 'Module Name',
                        'description' => 'Accounts | Deals',
                    ) );
                    array_push( $final_data, array(
                        'key'         => 'text__What_Id',
                        'value'       => 'Module Record',
                        'description' => 'Account Name | Deal Name',
                    ) );
                }
            }
        }
        wp_send_json_success( $final_data );
    }

    public function search_record(
        $module,
        $search_key,
        $search_value,
        $record
    ) {
        sleep( 5 );
        $result = array(
            'id'   => '',
            'data' => array(),
        );
        $body = array(
            'select_query' => "select {$search_key}, id, Tag from {$module} where {$search_key} = '{$search_value}'",
        );
        $response = $this->bigin_request(
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

    public function is_duplicate( $module, $holder, $record ) {
        if ( 'Contacts' == $module ) {
            $contact_id = $this->search_record(
                'Contacts',
                'Email',
                $holder['Email'],
                $record
            )['id'];
            if ( $contact_id ) {
                return $contact_id;
            }
        }
        if ( 'Accounts' == $module ) {
            $account_id = $this->search_record(
                'Accounts',
                'Account_Name',
                $holder['Account_Name'],
                $record
            )['id'];
            if ( $account_id ) {
                return $account_id;
            }
        }
        // if( 'Leads' == $module ) {
        //     $lead_id = $this->search_record( 'Leads', 'Email', $holder['Email'], $record )['id];
        //     if( $lead_id ) {
        //         return $lead_id;
        //     }
        // }
        // if( 'Leads' == $module ) {
        //     $lead_id = $this->search_record( 'Leads', 'Email', $holder['Email'], $record )['id];
        //     if( $lead_id ) {
        //         return $lead_id;
        //     }
        // }
        return false;
    }

}

$bigin = ADFOIN_Bigin::get_instance();
add_action(
    'adfoin_bigin_job_queue',
    'adfoin_bigin_job_queue',
    10,
    1
);
function adfoin_bigin_job_queue(  $data  ) {
    adfoin_bigin_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Bigin API
 */
function adfoin_bigin_send_data(  $record, $posted_data  ) {
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
    $owner = ( isset( $data['userId'] ) ? $data['userId'] : '' );
    $module = ( isset( $data['moduleId'] ) ? $data['moduleId'] : '' );
    $task = $record['task'];
    // Backward compatibility: If no credId, try to get first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'bigin' );
        if ( !empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = ( isset( $first_credential['id'] ) ? $first_credential['id'] : '' );
        }
    }
    unset($data['credId']);
    unset($data['userId']);
    unset($data['moduleId']);
    if ( $task == 'subscribe' ) {
        $bigin = ADFOIN_Bigin::get_instance();
        // Load credentials for the selected account
        if ( $cred_id ) {
            $bigin->load_credentials( $cred_id );
        }
        $holder = array();
        $account_id = '';
        $contact_id = '';
        $campaign_id = '';
        $task_module = '';
        $account_lookups = array('Parent_Account', 'Account_Name');
        $contact_lookups = array('Contact_Name', 'Who_Id', 'Related_To');
        $campaign_lookups = array('Parent_Campaign', 'Campaign_Source');
        foreach ( $data as $key => $value ) {
            list( $data_type, $original_key ) = explode( '__', $key );
            $value = adfoin_get_parsed_values( $value, $posted_data );
            if ( 'datetime' == $data_type && $value ) {
                // if( 'Start_DateTime' == $original_key || 'End_DateTime' == $original_key ) {
                $timezone = wp_timezone();
                $date = date_create( $value, $timezone );
                if ( $date ) {
                    $value = date_format( $date, 'c' );
                }
                // }
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
                        if ( 'contact' == $type ) {
                            $participant_id = $bigin->search_record(
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
                    $account_id = $bigin->search_record(
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
                    $contact_id = $bigin->search_record(
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
                    $campaign_id = $bigin->search_record(
                        'Campaigns',
                        'Campaign_Name',
                        $value,
                        $record
                    )['id'];
                    if ( $campaign_id ) {
                        $value = $campaign_id;
                    }
                }
                if ( 'Product_Name' == $original_key ) {
                    $product_id = $bigin->search_record(
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
                    $deal_id = $bigin->search_record(
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
                    $contacts_response = $bigin->bigin_request( 'Accounts/' . $account_id . '/Contacts?fields=id,First_Name,Last_Name' );
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
                    $account_id = $bigin->search_record(
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
            $return = $bigin->bigin_request(
                $module . '/upsert',
                'POST',
                $request_data,
                $record
            );
            // $id           = $bigin->is_duplicate( $module, $holder, $record );
            // if( $id ) {
            //     $return = $bigin->bigin_request( $module . '/' . $id, 'PUT', $request_data, $record );
            // } else {
            //     $return = $bigin->bigin_request( $module, 'POST', $request_data, $record );
            // }
        }
    }
    return;
}
