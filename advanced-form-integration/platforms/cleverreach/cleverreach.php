<?php

class ADFOIN_CleverReach extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'cleverreach';

    const authorization_endpoint = 'https://rest.cleverreach.com/oauth/authorize.php';
    const token_endpoint         = 'https://rest.cleverreach.com/oauth/token.php';

    private static $instance;

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Load legacy credentials for backward compatibility
     */
    private function load_legacy_credentials() {
        $option = (array) maybe_unserialize(get_option('adfoin_cleverreach_keys'));

        if (isset($option['client_id'])) {
            $this->client_id = $option['client_id'];
        }

        if (isset($option['client_secret'])) {
            $this->client_secret = $option['client_secret'];
        }

        if (isset($option['access_token'])) {
            $this->access_token = $option['access_token'];
        }

        if (isset($option['refresh_token'])) {
            $this->refresh_token = $option['refresh_token'];
        }
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint = self::token_endpoint;
        $this->refresh_token_endpoint = self::token_endpoint;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter('adfoin_action_providers', array($this, 'adfoin_cleverreach_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_cleverreach_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_cleverreach_settings_view'), 10, 1);
        add_action('admin_post_adfoin_save_cleverreach_keys', array($this, 'adfoin_save_cleverreach_keys'), 10, 0);

        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('wp_ajax_adfoin_get_cleverreach_groups', array($this, 'get_groups'), 10, 0);
        add_action('wp_ajax_adfoin_get_cleverreach_fields', array($this, 'get_fields'), 10, 0);

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_cleverreach_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_cleverreach_credentials', array( $this, 'save_credentials' ) );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
    }

    public function create_webhook_route() {
        register_rest_route('advancedformintegration', '/cleverreach', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_webhook_data'),
            'permission_callback' => '__return_true'
        ));
    }

    public function get_webhook_data($request) {
        $params = $request->get_params();
        $code = isset($params['code']) ? trim($params['code']) : '';
        $state = isset($params['state']) ? trim($params['state']) : '';

        if ($code) {
            $redirect_to = add_query_arg(
                [
                    'service' => 'authorize',
                    'action' => 'adfoin_cleverreach_auth_redirect',
                    'code' => $code,
                    'state' => $state,
                ],
                admin_url('admin.php?page=advanced-form-integration')
            );

            wp_safe_redirect($redirect_to);
            exit();
        }
    }

    public function auth_redirect() {
        $auth = isset($_GET['auth']) ? trim($_GET['auth']) : '';
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $action = isset($_GET['action']) ? trim($_GET['action']) : '';
        $state = isset($_GET['state']) ? trim($_GET['state']) : '';

        if ('adfoin_cleverreach_auth_redirect' == $action) {
            // admin_init fires for every logged-in user; only an admin should
            // be able to complete this OAuth flow (CWE-862).
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $code = isset($_GET['code']) ? $_GET['code'] : '';

            if ($code) {
                // Check if this is an OAuth Manager request — state must be
                // a valid, transient-backed token issued by issue_oauth_state().
                $context = self::consume_oauth_state( $state, 'cleverreach' );
                if ( $context && $context['cred_id'] ) {
                    $this->handle_oauth_manager_callback( $code, $context['cred_id'] );

                    // Use OAuth Manager popup close handler
                    if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
                        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                    }
                    
                    $success = ! empty( $this->access_token );
                    $message = $success ? __( 'Authorization successful!', 'advanced-form-integration' ) : __( 'Authorization failed. Please try again.', 'advanced-form-integration' );
                    ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
                } else {
                    // Legacy callback handling - redirect to settings page
                    $this->request_token($code);
                    wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=cleverreach'));
                    exit();
                }
            }

            // If no code or other error, redirect to settings
            wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=cleverreach'));
            exit();
        }
    }

    /**
     * Handle OAuth Manager callback
     */
    private function handle_oauth_manager_callback( $code, $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'cleverreach', $cred_id );
        if ( ! $credentials ) {
            return;
        }

        // Setting cred_id BEFORE request_token means save_data routes
        // the new tokens into the right credential record automatically.
        $this->cred_id       = $cred_id;
        $this->client_id     = isset( $credentials['clientId'] ) ? $credentials['clientId'] : ( isset( $credentials['client_id'] ) ? $credentials['client_id'] : '' );
        $this->client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : ( isset( $credentials['client_secret'] ) ? $credentials['client_secret'] : '' );

        $this->request_token( $code );
    }

    public function adfoin_cleverreach_actions($actions) {
        $actions['cleverreach'] = array(
            'title' => __('CleverReach', 'advanced-form-integration'),
            'tasks' => array(
                'subscribe' => __('Subscribe to List', 'advanced-form-integration'),
            )
        );
        return $actions;
    }

    public function adfoin_cleverreach_settings_tab($providers) {
        $providers['cleverreach'] = __('CleverReach', 'advanced-form-integration');
        return $providers;
    }

    public function adfoin_cleverreach_settings_view($current_tab) {
        if ($current_tab != 'cleverreach') {
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
            '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol><div style="margin-top: 15px;"><strong>%s:</strong><div style="margin-top: 8px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; font-family: monospace; font-size: 12px; line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word;">%s</div></div>',
            __( 'Go to <strong>My Account > Interfaces > REST API</strong> and click <strong>Create OAuth2 App</strong>.', 'advanced-form-integration' ),
            __( 'Copy the Redirect URL below and paste it in the OAuth2 app settings.', 'advanced-form-integration' ),
            __( 'Select the <strong>Recipients</strong> and <strong>Forms</strong> scopes and click <strong>Create Now</strong>.', 'advanced-form-integration' ),
            __( 'Open the newly created app from the list.', 'advanced-form-integration' ),
            __( 'Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> and paste them in the form above.', 'advanced-form-integration' ),
            __( 'Redirect URI', 'advanced-form-integration' ),
            esc_html( $redirect_uri )
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'cleverreach', __( 'CleverReach', 'advanced-form-integration' ), $fields, $instructions );
    }

    public function adfoin_save_cleverreach_keys() {
        adfoin_require_manage_options();
        if (!wp_verify_nonce($_POST['_nonce'], 'adfoin_cleverreach_settings')) {
            die(__('Security check Failed', 'advanced-form-integration'));
        }

        $client_id = isset($_POST["adfoin_cleverreach_client_id"]) ? sanitize_text_field( wp_unslash( $_POST["adfoin_cleverreach_client_id"] ) ) : "";
        $client_secret = isset($_POST["adfoin_cleverreach_client_secret"]) ? sanitize_text_field( wp_unslash( $_POST["adfoin_cleverreach_client_secret"] ) ) : "";

        if (!$client_id || !$client_secret) {
            $this->reset_data();
        } else {
            $this->client_id = trim($client_id);
            $this->client_secret = trim($client_secret);

            $this->save_data();
            $this->authorize();
        }

        advanced_form_integration_redirect("admin.php?page=advanced-form-integration-settings&tab=cleverreach");
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="cleverreach-action-template">
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
                            <?php esc_attr_e( 'CleverReach Account', 'advanced-form-integration' ); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=cleverreach' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" v-if="action.task == 'subscribe'">
                    <th scope="row">
                        <?php esc_attr_e('List', 'advanced-form-integration'); ?>
                    </th>
                    <td>
                        <select name="fieldData[groupId]" v-model="fielddata.groupId">
                            <option value=""> <?php _e('Select List...', 'advanced-form-integration'); ?> </option>
                            <option v-for="(list, index) in fielddata.groups" :value="index"> {{ list }} </option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'subscribe', 'CleverReach [PRO]', 'list fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function get_groups() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $response = $this->request( 'groups' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error( __( 'Could not load CleverReach lists. Please reconnect the account.', 'advanced-form-integration' ) );
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $lists = array();

        foreach ( is_array( $body ) ? $body : array() as $list ) {
            if ( isset( $list['id'], $list['name'] ) ) {
                $lists[ $list['id'] ] = $list['name'];
            }
        }

        wp_send_json_success( $lists );
    }

    /**
     * Free: Email plus the global (account-wide) fields.
     * List-specific fields are part of CleverReach [PRO].
     */
    public function get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = $this->build_field_list( 0, false );

        if ( is_wp_error( $fields ) ) {
            wp_send_json_error( $fields->get_error_message() );
        }

        wp_send_json_success( $fields );
    }

    /**
     * Email, the global fields and (optionally) the selected list's fields.
     * List fields are keyed "list__<name>" so they are sent as the receiver's
     * list "attributes" instead of "global_attributes".
     *
     * @return array|WP_Error
     */
    public function build_field_list( $group_id = 0, $include_list_fields = false ) {
        $fields = array(
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        );

        $global = $this->get_attributes( 0 );

        if ( is_wp_error( $global ) ) {
            return $global;
        }

        foreach ( $global as $attribute ) {
            $fields[] = array(
                'key'         => $attribute['name'],
                'value'       => $attribute['label'],
                'description' => $attribute['description'],
            );
        }

        if ( $include_list_fields && $group_id ) {
            $local = $this->get_attributes( $group_id );

            if ( ! is_wp_error( $local ) ) {
                foreach ( $local as $attribute ) {
                    $fields[] = array(
                        'key'         => 'list__' . $attribute['name'],
                        'value'       => $attribute['label'] . ' ' . __( '(List field)', 'advanced-form-integration' ),
                        'description' => $attribute['description'],
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Attributes of a list, or the global attributes when $group_id is 0.
     *
     * @return array|WP_Error List of [ name, label, description ].
     */
    public function get_attributes( $group_id = 0 ) {
        $endpoint = $group_id ? 'groups/' . (int) $group_id . '/attributes' : 'attributes';
        $response = $this->request( $endpoint );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return new WP_Error( 'cleverreach_attributes', __( 'Could not load CleverReach fields. Please reconnect the account.', 'advanced-form-integration' ) );
        }

        $body       = json_decode( wp_remote_retrieve_body( $response ), true );
        $attributes = array();

        foreach ( is_array( $body ) ? $body : array() as $attribute ) {
            if ( empty( $attribute['name'] ) ) {
                continue;
            }

            // Global attributes have group id 0; skip them when listing a list.
            // The list endpoint returns "groups_id", others "group_id".
            $attr_group = isset( $attribute['groups_id'] ) ? $attribute['groups_id'] : ( isset( $attribute['group_id'] ) ? $attribute['group_id'] : null );

            if ( $group_id && null !== $attr_group && 0 === (int) $attr_group ) {
                continue;
            }

            $label = ! empty( $attribute['description'] ) ? $attribute['description'] : $attribute['name'];
            $type  = isset( $attribute['type'] ) ? $attribute['type'] : '';

            $attributes[] = array(
                'name'        => $attribute['name'],
                'label'       => $label,
                'description' => 'date' === $type ? __( 'Date (YYYY-MM-DD)', 'advanced-form-integration' ) : '',
            );
        }

        return $attributes;
    }

    protected function authorize($scope = '') {
        $data = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => urlencode($this->get_redirect_uri())
        );

        $endpoint = add_query_arg($data, self::authorization_endpoint);

        if (wp_redirect(esc_url_raw($endpoint))) {
            exit();
        }
    }

    protected function request_token($authorization_code) {
        $body = array(
            'code' => $authorization_code,
            'redirect_uri' => $this->get_redirect_uri(),
            'grant_type' => 'authorization_code'
        );

        $request = [
            'timeout' => 30,
            'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
            ),
            'body' => $body
        ];

        $response = wp_remote_post(esc_url_raw(self::token_endpoint), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (401 == $response_code) {
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            $this->apply_token_response( $response_body );
        }

        $this->save_data();

        return $response;
    }

    /**
     * CleverReach expects grant_type / refresh_token in the POST body; the
     * shared implementation sends them in the query string, which CleverReach
     * rejects with 400 "The grant type was not specified in the request".
     */
    protected function refresh_token() {
        $lock_key  = $this->token_refresh_lock_key();
        $have_lock = $lock_key ? $this->acquire_token_refresh_lock( $lock_key ) : true;

        if ( ! $have_lock ) {
            $this->wait_for_token_refresh_lock( $lock_key );
            $this->reload_oauth_credentials();

            return array(
                'headers'  => array(),
                'body'     => '',
                'response' => array( 'code' => 200, 'message' => 'OK (refreshed by concurrent request)' ),
            );
        }

        $response = wp_remote_post( self::token_endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Accept'        => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
            ),
            'body'    => array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
            ),
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        $revoked = 401 === $response_code
            || ( 400 === $response_code && is_array( $response_body ) && isset( $response_body['error'] ) && 'invalid_grant' === $response_body['error'] );

        if ( $revoked ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $response_code ) {
            $this->apply_token_response( $response_body );
        } else {
            error_log( sprintf( 'CleverReach token refresh failed with code %d', $response_code ) );
        }

        $this->save_data();

        if ( $lock_key ) {
            $this->release_token_refresh_lock( $lock_key );
        }

        return $response;
    }

    protected function save_data() {
        // Multi-account flow: persist tokens into the credential record
        // identified by cred_id. Tokens are written under camelCase keys to match what set_credentials reads.
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
            return;
        }

        // Legacy single-account fallback for installs that haven't migrated
        // through the OAuth Manager UI yet.
        update_option( 'adfoin_cleverreach_keys', maybe_serialize( array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ) ) );
    }

    protected function reset_data() {
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/cleverreach');
    }

    public function request($endpoint, $method = 'GET', $data = array(), $record = array()) {
        $base_url = "https://rest.cleverreach.com/v3/";
        $url = $base_url . $endpoint;

        $args = array(
            'timeout' => 30,
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token
            ),
        );

        if ('POST' == $method || 'PUT' == $method) {
            $args['body'] = wp_json_encode($data);
        }

        $response = $this->remote_request($url, $args, $record);

        return $response;
    }

    protected function remote_request($url, $request = array(), $record = array()) {
        // Shared refresh-and-retry: on 401 the token is refreshed and the
        // Authorization header rebuilt from it (the caller's header is stale).
        $response = $this->request_with_token_refresh($url, $request, null, $sent_args);

        if ($record) {
            adfoin_add_to_log($response, $url, $sent_args, $record);
        }

        return $response;
    }

    /**
     * OAuth Manager credential management methods
     */
    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_verify_nonce();

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::maybe_handle_delete( 'cleverreach', 'adfoin_cleverreach_keys' );

        // Get form data
        $cred_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $client_id = isset( $_POST['clientId'] ) ? sanitize_text_field( wp_unslash( $_POST['clientId'] ) ) : '';
        $client_secret = isset( $_POST['clientSecret'] ) ? sanitize_text_field( wp_unslash( $_POST['clientSecret'] ) ) : '';

        if ( empty( $client_id ) || empty( $client_secret ) ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        // Prepare credentials array
        $credentials = array(
            'title' => $title,
            'clientId' => $client_id,
            'clientSecret' => $client_secret
        );

        // If updating, preserve the ID
        if ( ! empty( $cred_id ) ) {
            $credentials['id'] = $cred_id;
        }

        // Save credentials
        ADFOIN_OAuth_Manager::save_credentials( 'cleverreach', $credentials );

        // Get the saved credential ID (in case it was just created)
        if ( empty( $cred_id ) ) {
            $cred_id = $credentials['id'];
        }

        // Generate OAuth authorization URL — state is a transient-backed
        // token, not the raw credential ID.
        $state = self::issue_oauth_state( 'cleverreach', $cred_id );
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id' => $client_id,
                'redirect_uri' => urlencode( $this->get_redirect_uri() ),
                'state' => $state
            ),
            self::authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    /**
     * Set credentials for OAuth Manager
     */
    public function set_credentials( $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'cleverreach', $cred_id );

        if ( $credentials ) {
            $this->cred_id       = $cred_id;
            $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
            $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
            $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
            $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        }
    }

    /**
     * Surface the legacy single-account credential set as a `legacy_*` record
     * when the multi-account store is otherwise empty. Lets users see and
     * manage their existing connection in the OAuth Manager UI.
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'cleverreach' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }
        $option = (array) maybe_unserialize( get_option( 'adfoin_cleverreach_keys' ) );
        if ( empty( $option['client_id'] ) || empty( $option['client_secret'] ) ) {
            return $credentials;
        }
        $credentials[] = array(
            'id'            => 'legacy_123456',
            'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
            'clientId'     => $option['client_id'],
            'client_secret' => $option['client_secret'],
            'access_token'  => isset( $option['access_token'] )  ? $option['access_token']  : '',
            'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
        );
        return $credentials;
    }

}

$cleverreach = ADFOIN_CleverReach::get_instance();

add_action('adfoin_cleverreach_job_queue', 'adfoin_cleverreach_job_queue', 10, 1);

function adfoin_cleverreach_job_queue($data) {
    adfoin_cleverreach_send_data($data['record'], $data['posted_data']);
}

function adfoin_cleverreach_send_data( $record, $posted_data ) {
    // Free: global fields only. List fields are sent by CleverReach [PRO].
    adfoin_cleverreach_upsert_receiver( $record, $posted_data, false );
}

/**
 * CleverReach client for the integration's account.
 */
function adfoin_cleverreach_client( $cred_id = '' ) {
    $cleverreach = ADFOIN_CleverReach::get_instance();

    if ( $cred_id ) {
        $cleverreach->set_credentials( $cred_id );
    }

    return $cleverreach;
}

/**
 * Build the receiver payload (email, global_attributes, attributes) from the
 * mapped fields. Action settings (list, account, Pro options) are skipped.
 *
 * @param bool $with_list_fields Send "list__" fields as the list's attributes (Pro).
 */
function adfoin_cleverreach_build_receiver( $data, $posted_data, $with_list_fields = false ) {
    $reserved = array( 'groupId', 'credId', 'groups', 'doiFormId', 'forms', 'tags', 'source' );

    $global_attributes = array();
    $list_attributes   = array();
    $receiver          = array( 'email' => '' );

    foreach ( $data as $key => $value ) {
        if ( in_array( $key, $reserved, true ) || ! is_scalar( $value ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        if ( 'email' === $key ) {
            $receiver['email'] = trim( $value );
        } elseif ( 0 === strpos( $key, 'list__' ) ) {
            if ( $with_list_fields ) {
                $list_attributes[ substr( $key, 6 ) ] = $value;
            }
        } else {
            $global_attributes[ $key ] = $value;
        }
    }

    if ( $global_attributes ) {
        $receiver['global_attributes'] = $global_attributes;
    }

    if ( $list_attributes ) {
        $receiver['attributes'] = $list_attributes;
    }

    return $receiver;
}

/**
 * Add or update a receiver in the selected CleverReach list.
 *
 * @param bool $with_list_fields Send "list__" fields as the list's attributes (Pro).
 */
function adfoin_cleverreach_upsert_receiver( $record, $posted_data, $with_list_fields = false ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    if ( 'subscribe' !== $record['task'] ) {
        return;
    }

    $data     = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $group_id = isset( $data['groupId'] ) ? $data['groupId'] : '';
    $cred_id  = isset( $data['credId'] ) ? $data['credId'] : '';
    $receiver = adfoin_cleverreach_build_receiver( $data, $posted_data, $with_list_fields );

    if ( ! $receiver['email'] || ! $group_id ) {
        return;
    }

    adfoin_cleverreach_client( $cred_id )->request( 'groups/' . rawurlencode( $group_id ) . '/receivers/upsert', 'POST', $receiver, $record );
}
