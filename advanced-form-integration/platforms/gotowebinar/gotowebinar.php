<?php

if ( ! class_exists( 'ADFoin_GoToWebinar' ) ) :

class ADFoin_GoToWebinar extends Advanced_Form_Integration_OAuth2 {

    const AUTHORIZATION_ENDPOINT = 'https://authentication.logmeininc.com/oauth/authorize';
    const TOKEN_ENDPOINT         = 'https://authentication.logmeininc.com/oauth/token';
    const REFRESH_TOKEN_ENDPOINT = 'https://authentication.logmeininc.com/oauth/token';
    const API_BASE               = 'https://api.getgo.com/G2W/rest/v2';
    const DEFAULT_SCOPE          = 'G2W:read G2W:write';

    private static $instance;

    protected $client_id     = '';
    protected $client_secret = '';
    protected $access_token  = '';
    protected $refresh_token = '';
    protected $expires_at    = 0;
    protected $organizer_key = '';
    protected $account_key   = '';
    protected $scope         = self::DEFAULT_SCOPE;
    protected $cred_id       = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::AUTHORIZATION_ENDPOINT;
        $this->token_endpoint         = self::TOKEN_ENDPOINT;
        $this->refresh_token_endpoint = self::REFRESH_TOKEN_ENDPOINT;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_gotowebinar_keys', array( $this, 'save_keys' ) );

        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_gotowebinar_fields', array( $this, 'ajax_get_fields' ) );

        add_action( 'adfoin_gotowebinar_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_gotowebinar_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
        add_action( 'wp_ajax_adfoin_save_gotowebinar_credentials', array( $this, 'save_credentials' ), 10, 0 );
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $stored = (array) maybe_unserialize( get_option( 'adfoin_gotowebinar_keys' ) );

        if ( isset( $stored['client_id'] ) ) {
            $this->client_id = $stored['client_id'];
        }

        if ( isset( $stored['client_secret'] ) ) {
            $this->client_secret = $stored['client_secret'];
        }

        if ( isset( $stored['access_token'] ) ) {
            $this->access_token = $stored['access_token'];
        }

        if ( isset( $stored['refresh_token'] ) ) {
            $this->refresh_token = $stored['refresh_token'];
        }

        if ( isset( $stored['expires_at'] ) ) {
            $this->expires_at = $stored['expires_at'];
        }

        if ( isset( $stored['organizer_key'] ) ) {
            $this->organizer_key = $stored['organizer_key'];
        }

        if ( isset( $stored['account_key'] ) ) {
            $this->account_key = $stored['account_key'];
        }

        if ( isset( $stored['scope'] ) ) {
            $this->scope = $stored['scope'];
        }
    }

    public function register_actions( $actions ) {
        $actions['gotowebinar'] = array(
            'title' => __( 'GoToWebinar', 'advanced-form-integration' ),
            'tasks' => array(
                'create_registrant' => __( 'Register Attendee', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['gotowebinar'] = __( 'GoToWebinar', 'advanced-form-integration' );
        return $providers;
    }

    public function render_settings( $current_tab ) {
        if ( 'gotowebinar' !== $current_tab ) {
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
            array(
                'name'          => 'scope',
                'label'         => __( 'Scopes', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'default'       => self::DEFAULT_SCOPE,
                'description'   => __( 'Default: G2W:read G2W:write', 'advanced-form-integration' ),
            ),
        );

        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Create a GoTo developer app (%s) with GoToWebinar scopes.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.goto.com/">https://developer.goto.com/</a>' ) . '</li>';
        $instructions .= '<li>' . sprintf( __( 'Set the redirect URI to:', 'advanced-form-integration' ) ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Paste the client ID and client secret in the Add Account form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Save & Authorize to connect GoToWebinar.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect GoToWebinar', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'gotowebinar', 'GoToWebinar', $fields, $instructions, $config );
    
        ?>
        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="container">
            <input type="hidden" name="action" value="adfoin_save_gotowebinar_keys">
            <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>">

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                    <td>
                        <ol>
                            <li><?php esc_html_e( 'Create a GoTo developer app (https://developer.goto.com/) with GoToWebinar scopes.', 'advanced-form-integration' ); ?></li>
                            <li><?php printf( esc_html__( 'Set the redirect URI to %s', 'advanced-form-integration' ), '<code>' . esc_html( $redirect_uri ) . '</code>' ); ?></li>
                            <li><?php esc_html_e( 'Paste the client ID and client secret below.', 'advanced-form-integration' ); ?></li>
                            <li><?php esc_html_e( 'Click “Save & Authorize” to connect GoToWebinar.', 'advanced-form-integration' ); ?></li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Status', 'advanced-form-integration' ); ?></th>
                    <td>
                        <?php
                        if ( $this->access_token && $this->refresh_token ) {
                            esc_html_e( 'Connected', 'advanced-form-integration' );
                        } else {
                            esc_html_e( 'Not Connected', 'advanced-form-integration' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Client ID', 'advanced-form-integration' ); ?></th>
                    <td><input type="text" name="adfoin_gotowebinar_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Client Secret', 'advanced-form-integration' ); ?></th>
                    <td><input type="text" name="adfoin_gotowebinar_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Scopes', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="adfoin_gotowebinar_scope" value="<?php echo esc_attr( $scope ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Default: G2W:read G2W:write', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Save & Authorize', 'advanced-form-integration' ) ); ?>
        </form>
        <?php
    }

    public function save_keys() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ), 'adfoin_gotowebinar_settings' ) ) {
            wp_die( esc_html__( 'Security check failed', 'advanced-form-integration' ) );
        }

        $this->client_id     = isset( $_POST['adfoin_gotowebinar_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_gotowebinar_client_id'] ) ) : '';
        $this->client_secret = isset( $_POST['adfoin_gotowebinar_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_gotowebinar_client_secret'] ) ) : '';
        $this->scope         = isset( $_POST['adfoin_gotowebinar_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_gotowebinar_scope'] ) ) : self::DEFAULT_SCOPE;

        $this->save_data();
        $this->authorize( $this->scope );

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=gotowebinar' ) );
        exit;
    }

    protected function authorize( string $scope = '' ) {
        $scope = $scope ? $scope : self::DEFAULT_SCOPE;

        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $this->client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $scope,
                'state'         => wp_create_nonce( 'adfoin_gotowebinar_state' ),
            ),
            $this->authorization_endpoint
        );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $code ) {
        $body = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->get_redirect_uri(),
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        );

        $response = wp_remote_post(
            $this->token_endpoint,
            array(
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => http_build_query( $body, '', '&' ),
                'timeout' => 30,
            )
        );

        $this->handle_token_response( $response );
        return $response;
    }

    protected function refresh_token() {
        if ( ! $this->refresh_token ) {
            return new WP_Error( 'gotowebinar_missing_refresh_token', __( 'Refresh token is missing.', 'advanced-form-integration' ) );
        }

        $body = array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refresh_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        );

        $response = wp_remote_post(
            $this->refresh_token_endpoint,
            array(
                'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
                'body'    => http_build_query( $body, '', '&' ),
                'timeout' => 30,
            )
        );

        $this->handle_token_response( $response );
        return $response;
    }

    protected function handle_token_response( $response ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
        }

        if ( isset( $body['refresh_token'] ) ) {
            $this->refresh_token = $body['refresh_token'];
        }

        if ( isset( $body['expires_in'] ) ) {
            $this->expires_at = time() + (int) $body['expires_in'];
        }

        if ( isset( $body['organizer_key'] ) ) {
            $this->organizer_key = $body['organizer_key'];
        }

        if ( isset( $body['account_key'] ) ) {
            $this->account_key = $body['account_key'];
        }

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/gotowebinar' );
    }

    protected function save_data( $extra = array() ) {
        $data = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_at'    => $this->expires_at,
            'organizer_key' => $this->organizer_key,
            'account_key'   => $this->account_key,
            'scope'         => $this->scope,
        );

        if ( ! empty( $extra ) && is_array( $extra ) ) {
            $data = array_merge( $data, $extra );
        }

        update_option( 'adfoin_gotowebinar_keys', maybe_serialize( $data ) );
    }

    protected function reset_data() {
        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';
        $this->refresh_token = '';
        $this->expires_at    = 0;
        $this->organizer_key = '';
        $this->account_key   = '';
        $this->scope         = self::DEFAULT_SCOPE;
        $this->save_data();
    }

    public function set_credentials() {
        $stored = (array) maybe_unserialize( get_option( 'adfoin_gotowebinar_keys' ) );

        $this->client_id     = isset( $stored['client_id'] ) ? $stored['client_id'] : '';
        $this->client_secret = isset( $stored['client_secret'] ) ? $stored['client_secret'] : '';
        $this->access_token  = isset( $stored['access_token'] ) ? $stored['access_token'] : '';
        $this->refresh_token = isset( $stored['refresh_token'] ) ? $stored['refresh_token'] : '';
        $this->expires_at    = isset( $stored['expires_at'] ) ? (int) $stored['expires_at'] : 0;
        $this->organizer_key = isset( $stored['organizer_key'] ) ? $stored['organizer_key'] : '';
        $this->account_key   = isset( $stored['account_key'] ) ? $stored['account_key'] : '';
        $this->scope         = isset( $stored['scope'] ) ? $stored['scope'] : self::DEFAULT_SCOPE;
    }

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/gotowebinar',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_webhook_data' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';

        if ( $code ) {
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_gotowebinar_auth_redirect',
                    'code'    => rawurlencode( $code ),
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            );

            wp_safe_redirect( $redirect_to );
            exit;
        }

        return new WP_Error( 'gotowebinar_missing_code', __( 'Authorization code not found.', 'advanced-form-integration' ) );
    }

    public function auth_redirect() {
        if ( ! isset( $_GET['action'] ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

        if ( 'adfoin_gotowebinar_auth_redirect' !== $action ) {
            return;
        }

        $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

        if ( $code ) {
            $this->request_token( $code );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=gotowebinar' ) );
        exit;
    }

    public function render_action_template() {
        ?>
        <script type="text/template" id="gotowebinar-action-template">
            <table class="form-table" v-if="action.task == 'create_registrant'">
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'GoToWebinar Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="loadFields">
                            <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                        </select>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=gotowebinar' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                    v-bind:key="field.value"
                    v-bind:field="field"
                    v-bind:trigger="trigger"
                    v-bind:action="action"
                    v-bind:fielddata="fielddata"></editable-field>

                <tr class="alternate">
                    <th scope="row"><?php esc_html_e( 'Tips', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php esc_html_e( 'Map the webinar key (required) and optionally a session key. GoToWebinar requires an email and at least one name field.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
            </table>
        </script>
        <?php
    }

    public function ajax_get_fields() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        }

        $fields = array(
            array( 'key' => 'webinar_key', 'value' => __( 'Webinar Key', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'session_key', 'value' => __( 'Session Key (optional)', 'advanced-form-integration' ) ),
            array( 'key' => 'email', 'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'language', 'value' => __( 'Language', 'advanced-form-integration' ) ),
            array( 'key' => 'time_zone', 'value' => __( 'Time Zone', 'advanced-form-integration' ) ),
            array( 'key' => 'organization', 'value' => __( 'Organization', 'advanced-form-integration' ) ),
            array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
            array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'source', 'value' => __( 'Source', 'advanced-form-integration' ) ),
            array( 'key' => 'questions_and_comments', 'value' => __( 'Questions & Comments', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'responses_json', 'value' => __( 'Responses (JSON array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: [{"questionKey":"123","answer":"Yes"}]', 'advanced-form-integration' ) ),
            array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea' ),
        );

        wp_send_json_success( $fields );
    }

    public function handle_job_queue( $data ) {
        $record      = isset( $data['record'] ) ? $data['record'] : array();
        $posted_data = isset( $data['posted_data'] ) ? $data['posted_data'] : array();

        if ( empty( $record ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );
        $field_data  = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $cred_id     = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials( $cred_id );
        } else {
            $this->set_credentials();
        }

        $this->process_registrant( $record, $posted_data );
    }

    protected function process_registrant( $record, $posted_data ) {
        if ( empty( $record ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data  = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $webinar_key = $this->parse_field( $field_data, 'webinar_key', $posted_data );
        $session_key = $this->parse_field( $field_data, 'session_key', $posted_data );

        if ( '' === $webinar_key ) {
            adfoin_add_to_log( new WP_Error( 'gotowebinar_missing_webinar', __( 'Webinar key is required.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $payload = $this->collect_payload( $field_data, $posted_data );

        if ( is_wp_error( $payload ) ) {
            adfoin_add_to_log( $payload, '', array(), $record );
            return;
        }

        if ( empty( $payload ) ) {
            return;
        }

        $endpoint = 'webinars/' . rawurlencode( $webinar_key );

        if ( '' !== $session_key ) {
            $endpoint .= '/sessions/' . rawurlencode( $session_key ) . '/registrants';
        } else {
            $endpoint .= '/registrants';
        }

        $this->api_request( $endpoint, 'POST', $payload, $record );
    }

    protected function collect_payload( $field_data, $posted_data ) {
        $email = $this->parse_field( $field_data, 'email', $posted_data );

        if ( '' === $email ) {
            return new WP_Error( 'gotowebinar_missing_email', __( 'Email is required for GoToWebinar registrants.', 'advanced-form-integration' ) );
        }

        $payload = array( 'email' => $email );

        $map = array(
            'first_name'            => 'firstName',
            'last_name'             => 'lastName',
            'language'              => 'language',
            'time_zone'             => 'timeZone',
            'organization'          => 'organization',
            'job_title'             => 'jobTitle',
            'phone'                 => 'phone',
            'source'                => 'source',
            'questions_and_comments'=> 'questionsAndComments',
        );

        foreach ( $map as $key => $api_field ) {
            $value = $this->parse_field( $field_data, $key, $posted_data );

            if ( '' === $value ) {
                continue;
            }

            $payload[ $api_field ] = $value;
        }

        $responses = $this->parse_field( $field_data, 'responses_json', $posted_data );

        if ( '' !== $responses ) {
            $decoded = json_decode( $responses, true );

            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
                return new WP_Error( 'gotowebinar_invalid_responses', __( 'Responses JSON is invalid.', 'advanced-form-integration' ) );
            }

            $payload['responses'] = $decoded;
        }

        $custom = $this->parse_field( $field_data, 'custom_fields_json', $posted_data );

        if ( '' !== $custom ) {
            $decoded = json_decode( $custom, true );

            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
                return new WP_Error( 'gotowebinar_invalid_custom_fields', __( 'Custom fields JSON is invalid.', 'advanced-form-integration' ) );
            }

            $payload = array_merge( $payload, $decoded );
        }

        return $payload;
    }

    protected function api_request( $endpoint, $method = 'POST', $body = array(), $record = array() ) {
        $maybe_refreshed = $this->maybe_refresh_access_token();

        if ( is_wp_error( $maybe_refreshed ) ) {
            if ( $record ) {
                adfoin_add_to_log( $maybe_refreshed, '', array(), $record );
            }
            return $maybe_refreshed;
        }

        $url  = self::API_BASE . '/' . ltrim( $endpoint, '/' );
        $args = array(
            'timeout' => 30,
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ),
        );

        if ( $this->organizer_key ) {
            $args['headers']['OrganizerKey'] = $this->organizer_key;
        }

        if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );
        $status   = wp_remote_retrieve_response_code( $response );

        if ( 401 === $status ) {
            $refresh = $this->refresh_token();

            if ( ! is_wp_error( $refresh ) && $this->access_token ) {
                $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
                $response                         = wp_remote_request( esc_url_raw( $url ), $args );
                $status                           = wp_remote_retrieve_response_code( $response );
            }
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( $status >= 400 ) {
            $body    = wp_remote_retrieve_body( $response );
            $message = $body ? $body : __( 'GoToWebinar request failed.', 'advanced-form-integration' );

            return new WP_Error( 'gotowebinar_http_error', $message, array( 'status' => $status ) );
        }

        return $response;
    }

    protected function maybe_refresh_access_token() {
        if ( ! $this->access_token ) {
            return new WP_Error( 'gotowebinar_missing_token', __( 'GoToWebinar access token is missing. Re-authorize the connection.', 'advanced-form-integration' ) );
        }

        if ( $this->expires_at && $this->expires_at < time() + 60 ) {
            $refresh = $this->refresh_token();

            if ( is_wp_error( $refresh ) ) {
                return $refresh;
            }
        }

        return true;
    }

    protected function parse_field( $field_data, $key, $posted_data ) {
        if ( ! isset( $field_data[ $key ] ) ) {
            return '';
        }

        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

        if ( is_array( $value ) ) {
            return '';
        }

        return is_string( $value ) ? trim( $value ) : '';
    }
}

ADFoin_GoToWebinar::get_instance();

endif;
