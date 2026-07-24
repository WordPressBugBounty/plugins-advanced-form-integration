<?php

class VerticalResponse extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'verticalresponse';

    // const service_name           = 'verticalresponse';
    const authorization_endpoint = 'https://vrapi.verticalresponse.com/api/v1/oauth/authorize';
    const token_endpoint         = 'https://vrapi.verticalresponse.com/api/v1/oauth/access_token';
    public $client_id     = 'etufm7r8ncfkj9d4bdxvwkjw';
    public $client_secret = 'ct4ghxeJ7EKwtatgDuyzwx9P';

    private static $instance;

    public static function get_instance() {

        if ( empty( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    protected function __construct() {

        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_verticalresponse_keys', array( $this, 'save_keys' ), 10, 0 );

        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_verticalresponse_list', array( $this, 'get_verticalresponse_list' ), 10, 0 );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_verticalresponse_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_verticalresponse_credentials', array( $this, 'save_credentials' ) );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_get_verticalresponse_fields', array( $this, 'ajax_get_fields' ) );
    }

    public function is_active() {
        return !empty( $this->access_token );
    }

    /**
     * Load legacy credentials for backward compatibility
     */
    private function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_verticalresponse_keys' ) );

        if ( isset( $option['client_id'] ) ) {
            $this->client_id = $option['client_id'];
        }

        if ( isset( $option['client_secret'] ) ) {
            $this->client_secret = $option['client_secret'];
        }

        if ( isset( $option['access_token'] ) ) {
            $this->access_token = $option['access_token'];
        }
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/verticalresponse',
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

        if ( $code ) {

            $redirect_to = add_query_arg(
                [
                    'service' => 'authorize',
                    'action'  => 'adfoin_verticalresponse_auth_redirect',
                    'code'    => $code,
                ],
                admin_url( 'admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }
    }

    public function actions( $actions ) {

        $actions['verticalresponse'] = array(
            'title' => __( 'Vertical Response', 'advanced-form-integration' ),
            'tasks' => array(
                'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' )
            )
        );

        return $actions;
    }

    public function settings_tab( $providers ) {
        $providers['verticalresponse'] = __( 'Vertical Response', 'advanced-form-integration' );

        return $providers;
    }

    public function settings_view( $current_tab ) {
        if( $current_tab != 'verticalresponse' ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        // Define fields for OAuth Manager
        $fields = array(
            array(
                'name' => 'clientId',
                'label' => __( 'Client ID', 'advanced-form-integration' ),
                'type' => 'text',
                'required' => true,
                'placeholder' => __( 'Enter your Client ID', 'advanced-form-integration' ),
                'show_in_table' => true
            ),
            array(
                'name' => 'clientSecret',
                'label' => __( 'Client Secret', 'advanced-form-integration' ),
                'type' => 'text',
                'required' => true,
                'mask' => true,
                'placeholder' => __( 'Enter your Client Secret', 'advanced-form-integration' ),
                'show_in_table' => false
            )
        );

        $instructions = sprintf(
            '<p>%s</p><p><strong>%s:</strong> %s</p>',
            __( 'To connect your VerticalResponse account, you need to create an OAuth application.', 'advanced-form-integration' ),
            __( 'Redirect URI', 'advanced-form-integration' ),
            $redirect_uri
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect VerticalResponse', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'verticalresponse', __( 'VerticalResponse', 'advanced-form-integration' ), $fields, $instructions, $config );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="verticalresponse-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </th>
                    <td scope="row">
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'VerticalResponse Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadFields">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=verticalresponse' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php esc_attr_e( 'VerticalResponse List', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                            <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'subscribe', 'Vertical Response [PRO]', 'custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '';

        if ( 'adfoin_verticalresponse_auth_redirect' != $action ) {
            return;
        }

        // admin_init fires for every logged-in user; only an admin should
        // be able to complete this OAuth flow (CWE-862).
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $code  = isset( $_GET['code'] ) ? trim( $_GET['code'] ) : '';
        $state = isset( $_GET['state'] ) ? trim( $_GET['state'] ) : '';

        if ( $code ) {
            // Modern flow: state was issued via issue_oauth_state and carries cred_id.
            // Note: there is deliberately no fallback that trusts a raw
            // "oauth_manager_<cred_id>" state string. The credential-save
            // flow only ever issues transient-backed state, and the removed
            // fallback let anyone who knew a credential ID drive this public
            // callback into overwriting that credential's tokens.
            $context = self::consume_oauth_state( $state, 'verticalresponse' );
            if ( $context && $context['cred_id'] ) {
                $this->handle_oauth_manager_callback( $code, $context['cred_id'] );
            } else {
                // Pre-multi-account single-account legacy flow.
                $this->request_token( $code );
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=verticalresponse' ) );
        exit();
    }

    /**
     * Handle OAuth Manager callback
     */
    private function handle_oauth_manager_callback( $code, $cred_id ) {
        $credentials = adfoin_get_credentials_by_id( 'verticalresponse', $cred_id );
        if ( ! $credentials ) {
            return;
        }

        // Setting cred_id BEFORE request_token means save_data routes the
        // new tokens into THIS record automatically via persist_token_to_credential.
        $this->cred_id       = $cred_id;
        $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';

        $this->request_token( $code );
    }

    /**
     * Request token for OAuth Manager
     */
    private function request_token_for_oauth_manager( $code ) {
        $endpoint = add_query_arg(
            array(
                'code'          => $code,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret
            ),
            $this->token_endpoint
        );

        return wp_remote_get( $endpoint );
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
            $data['scope'] = $scope;
        }

        $endpoint = add_query_arg( $data, $this->authorization_endpoint );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit();
        }
    }

    protected function request_token( $code ) {

        $endpoint = add_query_arg(
            array(
                'code'          => $code,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret
            ),
            $this->token_endpoint
        );

        $response      = wp_remote_get( $endpoint );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
        } else {
            $this->apply_token_response( $response_body );
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {

        $data = (array) maybe_unserialize( get_option( 'adfoin_verticalresponse_keys' ) );

        $option = array_merge(
            $data,
            array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'access_token'  => $this->access_token
            )
        );

        update_option( 'adfoin_verticalresponse_keys', maybe_serialize( $option ) );
    }

    protected function reset_data() {

        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/verticalresponse' );
    }

    public function create_contact( $properties, $record = array() ) {
        $contact_id = $this->contact_exists( $properties['email'] );

        if( $contact_id ) {
            unset( $properties['email'] );
            $response = $this->request( 'contacts/' . $contact_id, 'PUT', $properties, $record );
        } else {
            $response = $this->request( 'contacts', 'POST', $properties, $record );
        }

        return $response;
    }

    public function add_to_list( $list_id, $email, $record = array() ) {
        $response = $this->request(
            'lists/' . $list_id . '/contacts', 
            'POST', 
            array( 'email' => $email ), 
            $record
        );

        return $response;
    }

    public function get_verticalresponse_list() {
        // Security Check
        adfoin_require_manage_options();
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $this->get_contact_lists();
    }

    public function get_contact_lists() {
        $response      = $this->request( 'lists' );
        $response_body = wp_remote_retrieve_body( $response );

        if ( empty( $response_body ) ) {
            return false;
        }

        $response_body = json_decode( $response_body, true );

        if ( isset( $response_body['items'] ) && !empty( $response_body['items'] ) ) {
            $lists = array();

            foreach( $response_body['items'] as $item ) {
                $lists[$item['attributes']['id']] = $item['attributes']['name'];
            }

            wp_send_json_success( $lists );
        } else {
            wp_send_json_error();
        }
    }

    public function request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $base_url = 'https://vrapi.verticalresponse.com/api/v1/';
        $url      = $base_url . $endpoint;

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ),
        );

        if ( 'POST' == $method || 'PUT' == $method ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = $this->remote_request($url, $args);

        if ( $record ) {
            adfoin_add_to_log($response, $url, $args, $record);
        }

        return $response;
    }

    public function contact_exists( $email ) {
        if( !$email ) {
            return false;
        }

        $return = $this->request( 'contacts?email=' . $email );
        $body = json_decode( wp_remote_retrieve_body( $return ), true );

        if( isset( $body['items'] ) && 
        is_array( $body['items'] ) && 
        count( $body['items'] ) > 0 ) {
            $contact_id = $body['items'][0]['attributes']['id'];

            return $contact_id;
        } else {
            return false;
        }
    }

    /**
     * OAuth Manager credential management methods
     */
    public function get_credentials() {
        adfoin_require_manage_options();
        adfoin_verify_nonce();

        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_require_manage_options();
        adfoin_verify_nonce();

        $platform    = 'verticalresponse';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        // Form input names are camelCase for back-compat with existing field
        // definitions; record stores snake_case (the read shim normalizes).
        $client_id     = isset( $_POST['clientId'] ) ? sanitize_text_field( wp_unslash( $_POST['clientId'] ) ) : '';
        $client_secret = isset( $_POST['clientSecret'] ) ? sanitize_text_field( wp_unslash( $_POST['clientSecret'] ) ) : '';

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
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && $cred['id'] === $id ) {
                $existing_client_id     = $cred['client_id']     ?? $cred['clientId']     ?? '';
                $existing_client_secret = $cred['client_secret'] ?? $cred['clientSecret'] ?? '';
                if ( $existing_client_id === $client_id && $existing_client_secret === $client_secret ) {
                    $new_data['access_token']  = $cred['access_token']  ?? $cred['accessToken']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? $cred['refreshToken'] ?? '';
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }
        unset( $cred );

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'state'         => self::issue_oauth_state( 'verticalresponse', $id ),
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = array(
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
            array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'address_line_1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'address_line_2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
            array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'state', 'value' => __( 'State', 'advanced-form-integration' ) ),
            array( 'key' => 'postal_code', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
            array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        );

        wp_send_json_success( $fields );
    }

    /**
     * Set credentials for OAuth Manager
     */
    public function set_credentials( $cred_id ) {
        $credentials = adfoin_get_credentials_by_id( 'verticalresponse', $cred_id );
        if ( ! $credentials ) {
            return;
        }

        $this->cred_id       = $cred_id;
        $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
    }

    /**
     * Surface the legacy single-account credential set as a `legacy_*` record
     * when the multi-account store is otherwise empty.
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'verticalresponse' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }
        $option = (array) maybe_unserialize( get_option( 'adfoin_verticalresponse_keys' ) );
        if ( empty( $option['client_id'] ) || empty( $option['client_secret'] ) ) {
            return $credentials;
        }
        $credentials[] = array(
            'id'            => 'legacy_123456',
            'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
            'client_id'     => $option['client_id'],
            'client_secret' => $option['client_secret'],
            'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
        );
        return $credentials;
    }
}

$verticalresponse = VerticalResponse::get_instance();

add_action( 'adfoin_verticalresponse_job_queue', 'adfoin_verticalresponse_job_queue', 10, 1 );

function adfoin_verticalresponse_job_queue( $data ) {
    adfoin_verticalresponse_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Vertical Response API
 */
function adfoin_verticalresponse_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = $record['task'];

    if( $task == 'subscribe' ) {
        unset( $data['listId'] );
        unset( $data['credId'] );
        unset( $data['task'] );

        $properties = array();

        foreach( $data as $key => $value ) {
            if( $value ) {
                $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

                if( $parsed_value ) {
                    $properties[$key] = $parsed_value;
                }
            }
        }
        
        $verticalresponse = VerticalResponse::get_instance();

        // Set credentials if provided
        if ( $cred_id ) {
            $verticalresponse->set_credentials( $cred_id );
        }

        $verticalresponse->create_contact( $properties, $record );

        if( $list_id ) {
            $verticalresponse->add_to_list( $list_id, $properties['email'], $record );
        }
    }

    return;
}