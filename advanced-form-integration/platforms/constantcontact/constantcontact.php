<?php

class ADFOIN_ConstantContact extends Advanced_Form_Integration_OAuth2 {

    const service_name           = 'constant_contact';
    const authorization_endpoint = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
    const token_endpoint         = 'https://authz.constantcontact.com/oauth2/default/v1/token';
    const refresh_token_endpoint = 'https://authz.constantcontact.com/oauth2/default/v1/token';

    private static $instance;
    protected      $contact_lists = array();
    protected      $cred_id = '';

    public static function get_instance() {

        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_constantcontact_keys' ) );

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

        if ( $this->is_active() ) {
            if ( isset( $option['contact_lists'] ) ) {
                $this->contact_lists = $option['contact_lists'];
            }
        }
    }

    private function __construct() {

        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'adfoin_constantcontact_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'adfoin_constantcontact_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'adfoin_constantcontact_settings_view' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_constantcontact_keys', array( $this, 'adfoin_save_constantcontact_keys' ), 10, 0 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_constantcontact_list', array( $this, 'get_constantcontact_list' ), 10, 0 );
        add_action( "rest_api_init", array( $this, "create_webhook_route" ) );
        add_action( 'wp_ajax_adfoin_get_constantcontact_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_constantcontact_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/constantcontact',
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

        if ( $code ) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                $this->cred_id = $state;
                $credentials = adfoin_read_credentials( 'constantcontact' );
                
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
                [
                    'service' => 'authorize',
                    'action'  => 'adfoin_constantcontact_auth_redirect',
                    'code'    => $code,
                ],
                admin_url( 'admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function adfoin_constantcontact_actions( $actions ) {

        $actions['constantcontact'] = array(
            'title' => __( 'Constant Contact', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' )
            )
        );

        return $actions;
    }

    public function adfoin_constantcontact_settings_tab( $providers ) {
        $providers['constantcontact'] = __( 'Constant Contact', 'advanced-form-integration' );

        return $providers;
    }

    public function adfoin_constantcontact_settings_view( $current_tab ) {
        if( $current_tab != 'constantcontact' ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'API Key', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'API Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
        );

        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.constantcontact.com/pages/dma/portal/">Constant Contact Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Create an application, insert a suitable name.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Select \'Authorization Code Flow and Implicit Flow\'.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Select \'Rotating Refresh Tokens\' and click Create.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Edit the newly created app, Copy the URL from below and paste in Redirect URI input box:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Generate API secret, then copy both API key and secret from the app and paste in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Save the Application.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Save & Authorize below.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Constant Contact', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'constantcontact', 'Constant Contact', $fields, $instructions, $config );
    }

    public function adfoin_save_constantcontact_keys() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_constantcontact_settings' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $api_key    = isset( $_POST["adfoin_constantcontact_api_key"] ) ? sanitize_text_field( $_POST["adfoin_constantcontact_api_key"] ) : "";
        $api_secret = isset( $_POST["adfoin_constantcontact_api_secret"] ) ? sanitize_text_field( $_POST["adfoin_constantcontact_api_secret"] ) : "";

        if( !$api_key || !$api_secret ) {
            $this->reset_data();
        } else{
            $this->client_id     = trim( $api_key );
            $this->client_secret = trim( $api_secret );

            $this->save_data();
            $this->authorize( 'account_read contact_data campaign_data offline_access' );
        }

        advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=constantcontact" );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $all_credentials = adfoin_read_credentials( 'constantcontact' );
        wp_send_json_success( $all_credentials );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'constantcontact' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_constantcontact_keys' ) );

            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && ! empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
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

        $platform    = 'constantcontact';
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
                    delete_option( 'adfoin_constantcontact_keys' );
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
        $scope    = 'account_read contact_data campaign_data offline_access';
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'state'         => $id,
                'nonce'         => 'advancedformintegration',
                'scope'         => $scope,
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="constantcontact-action-template">
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
                            <?php esc_attr_e( 'Constant Contact Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getConstantContactList">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <?php $this->get_credentials_list(); ?>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=constantcontact' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Constant Contact List', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                            <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Permission Type', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[permission]" v-model="fielddata.permission">
                            <option value="explicit"> <?php _e( 'Express', 'advanced-form-integration' ); ?> </option>
                            <option value="implicit"> <?php _e( 'Implied', 'advanced-form-integration' ); ?> </option>
                        </select>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'Create Source', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[createSource]" v-model="fielddata.createSource">
                            <option value="Account"> <?php _e( 'Account', 'advanced-form-integration' ); ?> </option>
                            <option value="Contact"> <?php _e( 'Contact', 'advanced-form-integration' ); ?> </option>
                        </select>
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
        $state  = isset( $_GET['state'] ) ? trim( $_GET['state'] ) : '';

        if ( 'adfoin_constantcontact_auth_redirect' == $action ) {
            $code = isset( $_GET['code'] ) ? $_GET['code'] : '';

            if ( $code ) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->cred_id = $state;
                    $credentials = adfoin_read_credentials( 'constantcontact' );
                    
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

            if ( ! empty( $this->access_token ) ) {
                $message = 'success';
            } else {
                $message = 'failed';
            }

            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=constantcontact' ) );

            exit();
        }
    }

    protected function authorize( $scope = '' ) {

        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
            'state'         => 'advancedformintegration',
            'nonce'         => 'advancedformintegration'
        );

        if( $scope ) {
            $data["scope"] = $scope;
        }

        $endpoint = add_query_arg( $data, $this->authorization_endpoint );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit();
        }
    }

    protected function request_token( $authorization_code ) {

        $endpoint = add_query_arg(
            array(
                'code'          => $authorization_code,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'grant_type'    => 'authorization_code'
            ),
            $this->token_endpoint
        );

        $request = [
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret),
            )
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

    protected function save_data() {
        // If using new credential system
        if ( $this->cred_id ) {
            $credentials = adfoin_read_credentials( 'constantcontact' );

            foreach ( $credentials as &$value ) {
                if ( $value['id'] == $this->cred_id ) {
                    if ( $this->access_token ) {
                        $value['access_token'] = $this->access_token;
                    }
                    if ( $this->refresh_token ) {
                        $value['refresh_token'] = $this->refresh_token;
                    }
                }
            }

            adfoin_save_credentials( 'constantcontact', $credentials );
            return;
        }

        // Legacy save for backward compatibility
        $option = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token
        );

        update_option( 'adfoin_constantcontact_keys', maybe_serialize( $option ) );
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
        $this->cred_id       = $credentials['id'];
    }

    /**
     * Get credentials list for dropdown
     */
    public function get_credentials_list() {
        $html        = '';
        $credentials = adfoin_read_credentials( 'constantcontact' );

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
        $all_credentials = adfoin_read_credentials( 'constantcontact' );

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

    protected function reset_data() {

        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';
        $this->refresh_token = '';

        $this->save_data();
    }

    protected function get_redirect_uri() {

        return site_url( '/wp-json/advancedformintegration/constantcontact' );
    }

    public function create_contact( $properties, $record = array() ) {
        $response = $this->request( 'contacts', 'POST', $properties, $record );

        return $response;
    }

    public function update_contact( $contact_id, $properties, $record = array() ) {
        $response = $this->request( 'contacts/' . $contact_id, 'PUT', $properties, $record );

        return $response;
    }

    public function get_constantcontact_list() {
        // Security Check
        if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $this->get_contact_lists();
    }

    public function request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $base_url = 'https://api.cc.email/v3/';
        $url      = $base_url . $endpoint;

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );

        if ( 'POST' == $method || 'PUT' == $method ) {
            $args['body'] = json_encode( $data );
        }

        $response = $this->remote_request( $url, $args, $record );

        // if ( $record ) {
        //     adfoin_add_to_log( $response, $url, $args, $record );
        // }

        return $response;
    }

    public function get_contact_lists() {
        $response      = $this->request( 'contact_lists?limit=500' );
        $response_body = wp_remote_retrieve_body( $response );

        if ( empty( $response_body ) ) {
            return false;
        }

        $response_body = json_decode( $response_body, true );

        if ( !empty( $response_body['lists'] ) ) {
            $lists = wp_list_pluck( $response_body['lists'], 'name', 'list_id' );

            wp_send_json_success( $lists );
        } else {
            wp_send_json_error();
        }
    }

    public function contact_exists( $email ) {
        // URL encode the email to handle special characters
        $encoded_email = urlencode( $email );
        $response      = $this->request( 'contacts?status=all&email=' . $encoded_email );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );
        $contact_id    = '';

        if( isset( $response_body['contacts'] ) && is_array( $response_body['contacts'] ) ) {
            // Use case-insensitive comparison for email matching
            if( count( $response_body['contacts'] ) > 0 && 
                isset( $response_body['contacts'][0]['email_address']['address'] ) &&
                strcasecmp( $response_body['contacts'][0]['email_address']['address'], $email ) === 0 ) {
                $contact_id = $response_body['contacts'][0]['contact_id'];

                if( $contact_id ) {
                    return $contact_id;
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {

        $request = wp_parse_args( $request, [ ] );

        $request['headers'] = array_merge(
            $request['headers'],
            array( 'Authorization' => $this->get_http_authorization_header( 'bearer' ), )
            
        );

        // Increase timeout to 30 seconds for Constant Contact API
        if ( ! isset( $request['timeout'] ) ) {
            $request['timeout'] = 30;
        }

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        // Check if we need to refresh token (avoid using static variable for concurrent requests)
        if ( 401 === wp_remote_retrieve_response_code( $response ) ) {
            // Check if this is not already a retry by looking at request context
            if ( ! isset( $request['_retry_after_refresh'] ) ) {
                $refresh_response = $this->refresh_token();
                
                // Only retry if refresh was successful
                if ( ! is_wp_error( $refresh_response ) && 200 === wp_remote_retrieve_response_code( $refresh_response ) ) {
                    // Mark this as a retry to prevent infinite loops
                    $request['_retry_after_refresh'] = true;
                    
                    // Update authorization header with new token
                    $request['headers']['Authorization'] = $this->get_http_authorization_header( 'bearer' );
                    
                    $response = wp_remote_request( esc_url_raw( $url ), $request );
                } else {
                    // Log refresh failure
                    error_log( 'ConstantContact: Failed to refresh token for credential ID: ' . $this->cred_id );
                }
            }
        }

        if( $record ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }
}

$constantcontact = ADFOIN_ConstantContact::get_instance();

add_action( 'adfoin_constantcontact_job_queue', 'adfoin_constantcontact_job_queue', 10, 1 );

function adfoin_constantcontact_job_queue( $data ) {
    adfoin_constantcontact_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Constant Contact API
 */
function adfoin_constantcontact_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data       = $record_data['field_data'];
    $list_id    = isset( $data['listId'] ) ? $data['listId'] : '';
    $permission = isset( $data['permission'] ) ? $data['permission'] : 'explicit';
    $create_source = isset( $data['createSource'] ) ? $data['createSource'] : 'Account';
    $cred_id    = isset( $data['credId'] ) ? $data['credId'] : '';
    $task       = $record['task'];


    if( $task == 'subscribe' ) {
        $email          = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );
        
        // Prevent race condition when multiple integrations process the same email
        $lock_key = 'adfoin_cc_processing_' . md5( strtolower( $email ) . $cred_id );
        $lock_timeout = 15; // 15 seconds timeout for the lock
        
        // Check if another integration is currently processing this email
        $lock_value = get_transient( $lock_key );
        if ( $lock_value ) {
            // Wait 5 seconds before checking again to avoid race condition
            sleep( 5 );
        }
        
        // Set a lock to indicate this email is being processed
        set_transient( $lock_key, time(), $lock_timeout );
        
        $first_name     = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values($data['firstName'], $posted_data );
        $last_name      = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values($data['lastName'], $posted_data );
        $company_name   = empty( $data['companyName'] ) ? '' : adfoin_get_parsed_values($data['companyName'], $posted_data );
        $job_title      = empty( $data['jobTitle'] ) ? '' : adfoin_get_parsed_values($data['jobTitle'], $posted_data );
        $work_phone     = empty( $data['workPhone'] ) ? '' : adfoin_get_parsed_values($data['workPhone'], $posted_data );
        $home_phone     = empty( $data['homePhone'] ) ? '' : adfoin_get_parsed_values($data['homePhone'], $posted_data );
        $mobile_phone   = empty( $data['mobilePhone'] ) ? '' : adfoin_get_parsed_values($data['mobilePhone'], $posted_data );
        $birthday_month = empty( $data['birthdayMonth'] ) ? '' : adfoin_get_parsed_values($data['birthdayMonth'], $posted_data );
        $birthday_day   = empty( $data['birthdayDay'] ) ? '' : adfoin_get_parsed_values($data['birthdayDay'], $posted_data );
        $anniversary    = empty( $data['anniversary'] ) ? '' : adfoin_get_parsed_values($data['anniversary'], $posted_data );
        $address_type   = empty( $data['addressType'] ) ? '' : adfoin_get_parsed_values($data['addressType'], $posted_data );
        $address1       = empty( $data['address1'] ) ? '' : adfoin_get_parsed_values($data['address1'], $posted_data );
        $city           = empty( $data['city'] ) ? '' : adfoin_get_parsed_values($data['city'], $posted_data );
        $state          = empty( $data['state'] ) ? '' : adfoin_get_parsed_values($data['state'], $posted_data );
        $zip            = empty( $data['zip'] ) ? '' : adfoin_get_parsed_values($data['zip'], $posted_data );
        $country        = empty( $data['country'] ) ? '' : adfoin_get_parsed_values($data['country'], $posted_data );
        $properties     = array();

        if( $email ) { $properties['email_address'] = array( 'address' => $email, 'permission_to_send' => $permission ); }
        if( $first_name ) { $properties['first_name'] = $first_name; }
        if( $last_name ) { $properties['last_name'] = $last_name; }
        if( $company_name ) { $properties['company_name'] = $company_name; }
        if( $job_title ) { $properties['job_title'] = $job_title; }
        if( $birthday_month ) { $properties['birthday_month'] = $birthday_month; }
        if( $birthday_day ) { $properties['birthday_day'] = $birthday_day; }
        if( $anniversary ) { $properties['anniversary'] = $anniversary; }

        if( $list_id ) {
            $properties['list_memberships'] = array( $list_id );
        }

        if( $work_phone || $home_phone || $mobile_phone ) {
            $properties['phone_numbers'] = array();

            if( $work_phone ) {
                array_push( $properties['phone_numbers'], array( 'phone_number' => $work_phone, 'kind' => 'work' ) );
            }

            if( $home_phone ) {
                array_push( $properties['phone_numbers'], array( 'phone_number' => $home_phone, 'kind' => 'home' ) );
            }

            if( $mobile_phone ) {
                array_push( $properties['phone_numbers'], array( 'phone_number' => $mobile_phone, 'kind' => 'mobile' ) );
            }
        }

        if( $address1 || $city || $state || $zip || $country ) {
            $kind = $address_type ? $address_type : 'home';
            $properties['street_addresses'] = array(array(
                'kind'        => $kind,
                'street'      => $address1,
                'city'        => $city,
                'state'       => $state,
                'postal_code' => $zip,
                'country'     => $country
            ));
        }

        $constantcontact = ADFOIN_ConstantContact::get_instance();

        // Set credentials if provided
        if ( $cred_id ) {
            $constantcontact->set_credentials( $cred_id );
        }

        $contact_id      = $constantcontact->contact_exists( $email );

        if( $contact_id ) {
            unset( $properties['email_address']['permission_to_send'] );
            $properties['update_source'] = $create_source;
            $return = $constantcontact->update_contact( $contact_id, $properties, $record );
        } else {
            $properties['create_source'] = $create_source;
            $return = $constantcontact->create_contact( $properties, $record );
        }
        
        // Release the lock after processing is complete
        delete_transient( $lock_key );
    }

    return;
}
