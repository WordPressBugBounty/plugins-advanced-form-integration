<?php

class ADFOIN_Salesforce extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'salesforce';

    const authorization_endpoint = 'https://login.salesforce.com/services/oauth2/authorize';
    const token_endpoint         = 'https://login.salesforce.com/services/oauth2/token';
    const refresh_token_endpoint = 'https://login.salesforce.com/services/oauth2/token';

    /**
     * Salesforce REST API version. Centralized so future bumps are a
     * one-line change. Salesforce supports prior versions for years, so
     * this rarely needs to move.
     */
    const API_VERSION = 'v62.0';

    private static $instance;
    protected $client_id = '';
    protected $client_secret = '';
    protected $access_token = '';
    protected $refresh_token = '';
    protected $instance_url = '';
    protected $cred_id = '';
    /**
     * Per-credential login domain. Empty → falls back to the
     * `login.salesforce.com` constant defaults set in __construct().
     * Set to `test.salesforce.com` for Sandbox, or `<sub>.my.salesforce.com`
     * for My-Domain-enforced orgs.
     */
    protected $login_domain = '';

    public static function get_instance() {
        if (empty(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;
        
        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action('admin_init', array($this, 'auth_redirect'));
        add_filter('adfoin_action_providers', array($this, 'adfoin_salesforce_actions'), 10, 1);
        add_filter('adfoin_settings_tabs', array($this, 'adfoin_salesforce_settings_tab'), 10, 1);
        add_action('adfoin_settings_view', array($this, 'adfoin_salesforce_settings_view'), 10, 1);
        add_action('adfoin_action_fields', array($this, 'action_fields'), 10, 1);
        add_action('rest_api_init', array($this, 'create_webhook_route'));
        add_action('wp_ajax_adfoin_get_salesforce_credentials', array($this, 'get_credentials'));
        add_filter('adfoin_get_credentials', array($this, 'modify_credentials'), 10, 2);
        add_action('wp_ajax_adfoin_save_salesforce_credentials', array($this, 'save_credentials'));
        add_action('wp_ajax_adfoin_test_salesforce_connection', array($this, 'test_connection'));
        add_action('wp_ajax_adfoin_get_salesforce_fields', array($this, 'get_fields'));
        add_action('wp_ajax_adfoin_get_salesforce_campaigns', array($this, 'get_campaigns'));
        add_action('wp_ajax_adfoin_get_salesforce_owners', array($this, 'get_owner_list'));
    }

    /**
     * Load legacy credentials from old option for backward compatibility
     */
    protected function load_legacy_credentials() {
        $option = (array) maybe_unserialize(get_option('adfoin_salesforce_keys'));

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
        if (isset($option['instance_url'])) {
            $this->instance_url = $option['instance_url'];
        }
        if (isset($option['login_domain'])) {
            $this->login_domain = $option['login_domain'];
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
            $this->update_oauth_endpoints();
            return true;
        }

        // Load from OAuth Manager
        $credentials = adfoin_read_credentials( 'salesforce' );

        foreach ( $credentials as $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] == $cred_id ) {
                $this->cred_id       = $cred_id;
                $this->client_id     = isset( $credential['client_id'] ) ? $credential['client_id'] : '';
                $this->client_secret = isset( $credential['client_secret'] ) ? $credential['client_secret'] : '';
                $this->access_token  = isset( $credential['access_token'] ) ? $credential['access_token'] : '';
                $this->refresh_token = isset( $credential['refresh_token'] ) ? $credential['refresh_token'] : '';
                $this->instance_url  = isset( $credential['instance_url'] ) ? $credential['instance_url'] : '';
                $this->login_domain  = isset( $credential['login_domain'] ) ? $credential['login_domain'] : '';
                $this->update_oauth_endpoints();
                return true;
            }
        }

        return false;
    }

    /**
     * Re-derive the authorization/token endpoints from $this->login_domain.
     * Called after load_credentials hydrates the per-account login domain.
     * Empty login_domain falls back to the production defaults set in the
     * constructor — preserves behavior for credentials saved before this
     * field existed.
     */
    protected function update_oauth_endpoints() {
        $domain = $this->normalize_login_domain( $this->login_domain );
        if ( ! $domain ) {
            // Reset to production defaults (legacy behavior).
            $this->authorization_endpoint = self::authorization_endpoint;
            $this->token_endpoint         = self::token_endpoint;
            $this->refresh_token_endpoint = self::refresh_token_endpoint;
            return;
        }
        $this->authorization_endpoint = 'https://' . $domain . '/services/oauth2/authorize';
        $this->token_endpoint         = 'https://' . $domain . '/services/oauth2/token';
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    /**
     * Accept user-supplied login domain in any reasonable form (with or
     * without scheme, with or without trailing slash) and return the bare
     * host. Returns '' for empty input.
     */
    protected function normalize_login_domain( $value ) {
        $value = is_string( $value ) ? trim( $value ) : '';
        if ( '' === $value ) {
            return '';
        }
        // Strip scheme + trailing slash if user pasted a full URL.
        if ( preg_match( '~^https?://~i', $value ) ) {
            $parsed = wp_parse_url( $value );
            $value  = $parsed['host'] ?? '';
        }
        $value = rtrim( $value, '/' );
        // Salesforce login hosts are alphanumeric, dot, dash. Reject the rest.
        if ( ! preg_match( '/^[A-Za-z0-9.\-]+$/', $value ) ) {
            return '';
        }
        return strtolower( $value );
    }

    public function create_webhook_route() {
        register_rest_route('advancedformintegration', '/salesforce', [
            'methods' => 'GET',
            'callback' => [$this, 'get_webhook_data'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_webhook_data($request) {
        $params = $request->get_params();
        $code   = isset($params['code']) ? sanitize_text_field($params['code']) : '';
        $state  = isset($params['state']) ? sanitize_text_field($params['state']) : '';
        $context = self::consume_oauth_state( $state, 'salesforce' );
        $state   = $context ? $context['cred_id'] : '';

        if ($code) {
            // New OAuth Manager flow with state parameter
            if ( $state ) {
                // load_credentials sets client_id/secret/login_domain AND
                // refreshes $this->token_endpoint via update_oauth_endpoints().
                // We can't use any pre-existing tokens (we're getting a new
                // pair), but the side effect of setting the right login
                // domain is what we need.
                $this->load_credentials( $state );

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
                'action' => 'adfoin_salesforce_auth_redirect',
                'code' => $code,
            ], admin_url('admin.php?page=advanced-form-integration'));

            wp_safe_redirect($redirect_to);
            exit();
        }
    }

    public function auth_redirect() {
        $action = isset($_GET['action']) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

        if ('adfoin_salesforce_auth_redirect' == $action) {
            $code  = isset($_GET['code']) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
            $state = isset($_GET['state']) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
            $context = self::consume_oauth_state( $state, 'salesforce' );
            $state   = $context ? $context['cred_id'] : '';

            if ($code) {
                // If state exists, use new credential system
                if ( $state ) {
                    $this->load_credentials( $state );
                }

                $this->request_token($code);
                
                // For popup flow
                if ( $state ) {
                    require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
                    ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
                    exit;
                }
            }

            wp_safe_redirect(admin_url('admin.php?page=advanced-form-integration-settings&tab=salesforce'));
            exit();
        }
    }

    public function adfoin_salesforce_actions($actions) {
        $actions['salesforce'] = [
            'title' => __('Salesforce', 'advanced-form-integration'),
            'tasks' => [
                'add_lead'        => __('Add new Lead', 'advanced-form-integration'),
                'add_contact'     => __('Add new Account, Contact, Opportunity, Case', 'advanced-form-integration'),
            ],
        ];
        return $actions;
    }

    public function adfoin_salesforce_settings_tab($providers) {
        $providers['salesforce'] = __('Salesforce', 'advanced-form-integration');
        return $providers;
    }

    public function adfoin_salesforce_settings_view($current_tab) {
        if ($current_tab !== 'salesforce') return;

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
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'login_domain',
                'label'         => __( 'Login Domain', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'show_in_table' => false,
                'placeholder'   => 'login.salesforce.com',
                'description'   => __( 'Production / Developer Edition: leave blank or use <code>login.salesforce.com</code>. Sandbox: <code>test.salesforce.com</code>. My Domain: <code>yourorg.my.salesforce.com</code>.', 'advanced-form-integration' ),
            ),
        );

        // Instructions
        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'Log in to your Salesforce account.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click the Settings icon (gear) in the top-right corner and select Setup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'In the left-hand menu, go to Platform Tools, then click Apps, and select App Manager.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click on New Connected App to create a new app.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Enter the Connected App Name, API Name, and Contact Email.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Check Enable OAuth Settings under the API section.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Redirect URI below and paste in Callback URL:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Add Full Access and Perform requests anytime OAuth scopes.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Uncheck Require Proof Key for Code Exchange (PKCE).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Save and get Consumer Key (Client ID) and Consumer Secret (Client Secret).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        // Configuration
        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Salesforce', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        // Render using OAuth Manager
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'salesforce', 'Salesforce', $fields, $instructions, $config );
    }

    /**
     * Get credentials via AJAX
     */
    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    /**
     * Modify credentials to include legacy data for backward compatibility
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'salesforce' == $platform && empty( $credentials ) ) {
            $option = (array) maybe_unserialize( get_option( 'adfoin_salesforce_keys' ) );

            if ( isset( $option['client_id'] ) && isset( $option['client_secret'] ) && ! empty( $option['client_id'] ) ) {
                $credentials[] = array(
                    'id'            => 'legacy_123456',
                    'title'         => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
                    'client_id'     => $option['client_id'],
                    'client_secret' => $option['client_secret'],
                    'access_token'  => isset( $option['access_token'] ) ? $option['access_token'] : '',
                    'refresh_token' => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
                    'instance_url'  => isset( $option['instance_url'] ) ? $option['instance_url'] : '',
                );
            }
        }
        return $credentials;
    }

    /**
     * Save credentials via AJAX
     */
    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'salesforce';
        $credentials = adfoin_read_credentials( $platform );
        
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle Deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                // If deleting legacy credential, also clear the old option
                if ( isset( $credentials[ $index ]['id'] ) && strpos( $credentials[ $index ]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_salesforce_keys' );
                }
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        // Handle Save/Update
        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';
        $login_domain  = isset( $_POST['login_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['login_domain'] ) ) : '';
        // Validate / canonicalize: bare host only, default to '' (= production).
        $login_domain  = $this->normalize_login_domain( $login_domain );

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        // Preserve previously-saved values when the edit form submits an
        // empty client_id / client_secret. client_secret is the common case
        // because we never ship it to the browser (safe_credentials_list
        // strips it). Without this guard, editing the title would silently
        // wipe the stored secret.
        $existing = null;
        foreach ( $credentials as $cred_check ) {
            if ( isset( $cred_check['id'] ) && $cred_check['id'] === $id ) {
                $existing = $cred_check;
                break;
            }
        }
        if ( $existing ) {
            if ( '' === $client_id && ! empty( $existing['client_id'] ) ) {
                $client_id = $existing['client_id'];
            }
            if ( '' === $client_secret && ! empty( $existing['client_secret'] ) ) {
                $client_secret = $existing['client_secret'];
            }
            // login_domain can be intentionally blank (= production) so we
            // don't auto-fill it; the user submitted what they wanted.
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array(
                'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ),
            ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'login_domain'  => $login_domain,
            'access_token'  => '',
            'refresh_token' => '',
            'instance_url'  => '',
        );

        // Check if updating existing credential
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] == $id ) {
                // Preserve tokens if credentials + login domain haven't changed.
                $existing_domain = isset( $cred['login_domain'] ) ? $cred['login_domain'] : '';
                if ( isset( $cred['client_id'] ) && $cred['client_id'] == $client_id &&
                     isset( $cred['client_secret'] ) && $cred['client_secret'] == $client_secret &&
                     $existing_domain === $login_domain ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['instance_url']  = isset( $cred['instance_url'] ) ? $cred['instance_url'] : '';
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

        // Generate Auth URL — use the per-credential login domain so the
        // user is sent to the right Salesforce host (production vs.
        // sandbox vs. My Domain).
        $auth_base = $login_domain
            ? 'https://' . $login_domain . '/services/oauth2/authorize'
            : self::authorization_endpoint;

        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'prompt'        => 'login consent',
                'state'         => self::issue_oauth_state( 'salesforce', $id ),
            ),
            $auth_base
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    /**
     * Test Connection AJAX — verifies the cached access token works by
     * hitting Salesforce's /services/data/ (lists API versions; cheap +
     * doesn't need any object-level permission).
     */
    public function test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->salesforce_request( '', 'GET' );
        } );
    }

    protected function authorize($scope = '') {
        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id' => $this->client_id,
                'redirect_uri' => urlencode($this->get_redirect_uri()),
                'prompt' => 'login consent'
            ),
            $this->authorization_endpoint
        );

        if (wp_redirect(esc_url_raw($endpoint))) {
            exit();
        }
    }

    protected function request_token($code) {
        $args = array(
            'headers' => array(
                'user-agent'   => 'wordpress/advanced-form-integration',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 30,
            'method'  => 'POST',
            'body'    => array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->get_redirect_uri(),
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
        );

        // $this->token_endpoint is set by update_oauth_endpoints() to honor
        // the per-credential login domain (production / sandbox / My Domain).
        $response      = wp_remote_request( esc_url_raw( $this->token_endpoint ), $args );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->apply_token_response( $response_body );

        if ( isset( $response_body['instance_url'] ) ) {
            $this->instance_url = $response_body['instance_url'];
        }

        $this->save_data();

        return $response;
    }
    protected function refresh_token() {
        $ref_endpoint = $this->refresh_token_endpoint;

        $endpoint = add_query_arg(
            array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
            $ref_endpoint
        );

        $request = [
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ];

        $response      = wp_remote_post(esc_url_raw($endpoint), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        $this->apply_token_response( $response_body );

        $this->save_data();
        return $response;
    }

    protected function get_redirect_uri() {
        return site_url('/wp-json/advancedformintegration/salesforce');
    }

    protected function save_data() {
        // OAuth Manager flow: persist canonical token fields plus the
        // Salesforce-specific instance_url + login_domain via the base helper.
        if ( ! empty( $this->cred_id ) && strpos( $this->cred_id, 'legacy_' ) !== 0 ) {
            $this->persist_token_to_credential( array(
                'instance_url' => $this->instance_url,
                'login_domain' => $this->login_domain,
            ) );
            return;
        }

        // Legacy save method for backward compatibility
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'instance_url' => $this->instance_url,
            'login_domain' => $this->login_domain,
        ];
        update_option('adfoin_salesforce_keys', maybe_serialize( $data ));
    }

    protected function reset_data() {
        $this->client_id = '';
        $this->client_secret = '';
        $this->access_token = '';
        $this->refresh_token = '';
        $this->instance_url = '';
        $this->save_data();
    }

    public function action_fields() {
        ?>
        <script type='text/template' id='salesforce-action-template'>
            <table class='form-table'>
                <!-- Account picker: shown for every task -->
                <tr valign='top' class='alternate'>
                    <td scope='row-title'>
                        <label for='tablecell'>
                            <?php esc_attr_e('Select Account', 'advanced-form-integration'); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="onAccountChange">
                            <option value=""> <?php _e('Select Account...', 'advanced-form-integration'); ?> </option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforce' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign='top' v-if="action.task == 'add_lead' || action.task == 'add_contact'">
                    <th scope='row'>
                        <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                    </th>
                    <td scope='row'>
                        <div class='spinner' v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_lead' || action.task == 'add_contact'">
                    <td scope='row-title'>
                        <label for='owner'>
                            <?php esc_attr_e('Owner', 'advanced-form-integration'); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[ownerId]" v-model="fielddata.ownerId">
                            <option value=''><?php _e('Select Owner...', 'advanced-form-integration'); ?></option>
                            <option v-for='(name, id) in fielddata.owners' :value='id'>{{name}}</option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': ownerLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <tr valign='top' class='alternate' v-if="action.task == 'add_lead'">
                    <td scope='row-title'>
                        <label for='campaign'>
                            <?php esc_attr_e('Campaign', 'advanced-form-integration'); ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[campaignId]" v-model="fielddata.campaignId">
                            <option value=''><?php _e('Select Campaign...', 'advanced-form-integration'); ?></option>
                            <option v-for='(name, id) in fielddata.campaigns' :value='id'>{{name}}</option>
                        </select>
                        <div class='spinner' v-bind:class="{'is-active': campaignLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for='field in fields' v-bind:key='field.value' v-bind:field='field' v-bind:trigger='trigger' v-bind:action='action' v-bind:fielddata='fielddata'></editable-field>

                <?php adfoin_pro_feature_notice( 'add_lead', 'Salesforce [PRO]', 'custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_or_update_lead($lead_data, $record) {
        // Check if lead already exists
        $existing_lead = $this->find_lead($lead_data['Email']);
        
        if ($existing_lead) {
            // Update existing lead
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Lead/' . $existing_lead['Id'];
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($lead_data),
                'method' => 'PATCH',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $existing_lead['Id'];
        } else {
            // Create new lead
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Lead/';
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($lead_data),
                'method' => 'POST',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $response;
        }
    }

    private function find_lead($email) {
        $email = $this->soql_escape( $email );
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id FROM Lead WHERE Email = '$email' LIMIT 1");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['records']) && !empty($response_body['records'])) {
            return $response_body['records'][0];
        }

        return false;
    }
    public function create_or_update_account($account_data, $record) {
        // Check if account already exists
        $existing_account = $this->find_account($account_data['Name']);
        
        if ($existing_account) {
            // Update existing account
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Account/' . $existing_account['Id'];
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($account_data),
                'method' => 'PATCH',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $existing_account['Id'];
        } else {
            // Create new account
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Account/';
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($account_data),
                'method' => 'POST',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $response;
        }
    }

    private function find_account($name) {
        $name = $this->soql_escape( $name );
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id FROM Account WHERE Name = '$name' LIMIT 1");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['records']) && !empty($response_body['records'])) {
            return $response_body['records'][0];
        }

        return false;
    }

    public function create_or_update_contact($contact_data, $record) {
        // Check if contact already exists
        $existing_contact = $this->find_contact($contact_data['Email']);
        
        if ($existing_contact) {
            // Update existing contact
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Contact/' . $existing_contact['Id'];
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($contact_data),
                'method' => 'PATCH',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $existing_contact['Id'];
        } else {
            // Create new contact
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Contact/';
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($contact_data),
                'method' => 'POST',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $response;
        }
    }

    private function find_contact($email) {
        $email = $this->soql_escape( $email );
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id FROM Contact WHERE Email = '$email' LIMIT 1");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['records']) && !empty($response_body['records'])) {
            return $response_body['records'][0];
        }

        return false;
    }

    public function create_or_update_opportunity($opportunity_data, $record) {
        // Check if opportunity already exists
        $existing_opportunity = $this->find_opportunity($opportunity_data['Name']);
        
        if ($existing_opportunity) {
            // Update existing opportunity
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Opportunity/' . $existing_opportunity['Id'];
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($opportunity_data),
                'method' => 'PATCH',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $existing_opportunity['Id'];
        } else {
            // Create new opportunity
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Opportunity/';
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($opportunity_data),
                'method' => 'POST',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $response;
        }
    }

    private function find_opportunity($name) {
        $name = $this->soql_escape( $name );
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id FROM Opportunity WHERE Name = '$name' LIMIT 1");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['records']) && !empty($response_body['records'])) {
            return $response_body['records'][0];
        }

        return false;
    }

    public function create_or_update_case($case_data, $record) {
        // Check if case already exists
        $existing_case = $this->find_case($case_data['Subject']);
        
        if ($existing_case) {
            // Update existing case
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Case/' . $existing_case['Id'];
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($case_data),
                'method' => 'PATCH',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $existing_case['Id'];
        } else {
            // Create new case
            $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Case/';
            $args = [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($case_data),
                'method' => 'POST',
            ];
            $response = $this->remote_request($url, $args, $record);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response_body['id'])) {
                return $response_body['id'];
            }

            return $response;
        }
    }

    private function find_case($subject) {
        $subject = $this->soql_escape( $subject );
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id FROM Case WHERE Subject = '$subject' LIMIT 1");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['records']) && !empty($response_body['records'])) {
            return $response_body['records'][0];
        }

        return false;
    }
    public function create_opportunity($opportunity_data, $record) {
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Opportunity/';
        $args = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($opportunity_data),
            'method' => 'POST',
        ];
        $response = $this->remote_request($url, $args, $record);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['id'])) {
            return $response_body['id'];
        }

        return $response;
    }

    public function create_case($case_data, $record) {
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/Case/';
        $args = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($case_data),
            'method' => 'POST',
        ];
        $response = $this->remote_request($url, $args, $record);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_body['id'])) {
            return $response_body['id'];
        }

        return $response;
    }
    public function get_campaigns() {
        if (!adfoin_verify_nonce()) return;

        // Load credentials for the requested account so this AJAX call
        // doesn't accidentally hit whichever org the singleton was last
        // hydrated for. Without this, multi-account sites silently leaked
        // campaigns from one org into another's UI.
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( $cred_id ) {
            $this->load_credentials( $cred_id );
        }
        if ( ! $this->instance_url || ! $this->access_token ) {
            wp_send_json_success( array() );
        }

        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id, Name FROM Campaign");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        $campaigns = [];
        if (isset($response_body['records'])) {
            foreach ($response_body['records'] as $record) {
                $campaigns[$record['Id']] = $record['Name'];
            }
        }

        wp_send_json_success($campaigns);
    }

    public function assign_lead_to_campaign($lead_id, $campaign_id, $record) {
        $campaign_member_data = [
            'LeadId' => $lead_id,
            'CampaignId' => $campaign_id,
        ];
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/CampaignMember/';
        $args = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($campaign_member_data),
            'method' => 'POST',
        ];
        $campaign_response = $this->remote_request($url, $args, $record);

        if (is_wp_error($campaign_response)) {
            // Handle error
        } else {
            // Handle success
        }
    }

    // get owner list through api
    public function get_owner_list() {
        // Was missing nonce verification entirely — every other AJAX
        // handler in this class checks. Closes a CSRF gap.
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        // Multi-account fix — see get_campaigns().
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( $cred_id ) {
            $this->load_credentials( $cred_id );
        }
        if ( ! $this->instance_url || ! $this->access_token ) {
            wp_send_json_success( array() );
        }

        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/query/?q=' . urlencode("SELECT Id, Name FROM User");
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        $owners = [];
        if (isset($response_body['records'])) {
            foreach ($response_body['records'] as $record) {
                $owners[$record['Id']] = $record['Name'];
            }
        }

        wp_send_json_success($owners);
    }

    /* ---------- Public helpers for sub-platforms ---------- */

    /**
     * Public accessor for the Salesforce instance URL. Sub-platforms
     * (Tasks, Files, Knowledge, etc.) need this to construct API URLs
     * that aren't under /services/data/.
     */
    public function get_instance_url() {
        return $this->instance_url;
    }

    /**
     * Public accessor for the access token. Used by sub-platforms that
     * need to build multipart requests by hand (e.g., Files).
     */
    public function get_access_token() {
        return $this->access_token;
    }

    /**
     * Render <option> tags for the credentials dropdown so sub-platforms
     * can reuse the same account list in their action UIs. Same pattern
     * as ADFOIN_Dynamics365::get_credentials_list().
     */
    public function get_credentials_list() {
        $credentials = adfoin_read_credentials( 'salesforce' );
        if ( ! is_array( $credentials ) ) {
            return;
        }
        foreach ( $credentials as $option ) {
            if ( empty( $option['id'] ) ) {
                continue;
            }
            printf(
                '<option value="%1$s">%2$s</option>',
                esc_attr( $option['id'] ),
                esc_html( $option['title'] ?? $option['id'] )
            );
        }
    }

    /**
     * Escape a value for safe inclusion inside a SOQL string literal.
     * SOQL escapes both backslash and single quote with a backslash.
     */
    public function soql_escape( $value ) {
        return str_replace( array( "\\", "'" ), array( "\\\\", "\\'" ), (string) $value );
    }

    /**
     * Wrapper around the Salesforce REST API. Accepts a relative endpoint
     * (e.g. 'sobjects/Contact/') and the method/body, builds the full URL
     * via the loaded credential's instance_url, and routes through the
     * existing remote_request (so the 401 → refresh retry path is
     * preserved). Public so sub-platforms can use the same auth state.
     *
     * @param string $endpoint Relative path under /services/data/' . self::API_VERSION . '/
     * @param string $method   HTTP method (default GET).
     * @param mixed  $body     Array/object to JSON-encode for POST/PUT/PATCH.
     * @param array  $record   Integration record (for log writes).
     * @return array|WP_Error  wp_remote_* response.
     */
    public function salesforce_request( $endpoint, $method = 'GET', $body = null, $record = array() ) {
        if ( ! $this->instance_url ) {
            return new WP_Error( 'salesforce_no_instance', __( 'Salesforce instance URL is not set. Reconnect the account.', 'advanced-form-integration' ) );
        }

        $url = rtrim( $this->instance_url, '/' ) . '/services/data/' . self::API_VERSION . '/' . ltrim( $endpoint, '/' );

        $args = array(
            'method'  => strtoupper( $method ),
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        );

        if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) && null !== $body ) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = is_string( $body ) ? $body : wp_json_encode( $body );
        }

        return $this->remote_request( $url, $args, $record );
    }

    /**
     * Tracks whether THIS call to remote_request has already attempted a
     * token refresh. Instance scope (not `static`) so two unrelated API
     * calls in the same PHP request don't see each other's flag — the
     * old `static $refreshed` bug silently skipped the retry on call #2
     * if call #1 had refreshed.
     */
    private $retried_after_refresh = false;

    protected function remote_request($url, $request = array(), $record = array()) {
        $is_top_level = ! $this->retried_after_refresh;

        $request = wp_parse_args($request, []);

        $request['headers'] = array_merge(
            isset($request['headers']) ? (array) $request['headers'] : array(),
            array('Authorization' => 'Bearer ' . $this->access_token)
        );

        $response = wp_remote_request(esc_url_raw($url), $request);

        if (401 === wp_remote_retrieve_response_code($response) && ! $this->retried_after_refresh) {
            $this->refresh_token();
            $this->retried_after_refresh = true;

            // Recurse: the recursive call sees retried_after_refresh=true
            // and skips this branch, so we get exactly one retry attempt.
            $response = $this->remote_request($url, $request, $record);
        }

        if ($record) {
            adfoin_add_to_log($response, $url, $request, $record);
        }

        // Reset the flag for the next top-level API call in this request.
        if ($is_top_level) {
            $this->retried_after_refresh = false;
        }

        return $response;
    }

    /**
     * @deprecated Kept as a thin wrapper so Pro (and any third-party
     *             extensions that may have called it) keep working.
     *             Internally delegates to load_legacy_credentials() which
     *             handles the same legacy single-account fallback.
     */
    public function set_credentials() {
        $this->load_legacy_credentials();
    }

    public function get_fields() {
        if (!adfoin_verify_nonce()) return;

        // Multi-account fix: the describe API call inside get_object_fields
        // uses $this->instance_url / $this->access_token. Without loading
        // the requested account first, multi-account sites would describe
        // the wrong org's schema.
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( $cred_id ) {
            $this->load_credentials( $cred_id );
        }

        $fields = [];

        $task = isset($_POST['task']) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';

        if ('add_lead' == $task) {
            $fields = array_merge($fields, $this->get_lead_fields());
        } elseif ('add_contact' == $task) {
            $fields = array_merge($fields, $this->get_account_fields());
            $fields = array_merge($fields, $this->get_contact_fields());
            $fields = array_merge($fields, $this->get_opportunity_fields());
            $fields = array_merge($fields, $this->get_case_fields());
        }

        wp_send_json_success($fields);
    }

    public function get_lead_fields() {
        return $this->get_object_fields('Lead');
    }

    public function get_account_fields() {
        return $this->get_object_fields('Account');
    }

    public function get_contact_fields() {
        return $this->get_object_fields('Contact');
    }

    public function get_opportunity_fields() {
        return $this->get_object_fields('Opportunity');
    }

    public function get_case_fields() {
        return $this->get_object_fields('Case');
    }

    private function get_object_fields($object) {
        $url = $this->instance_url . '/services/data/' . self::API_VERSION . '/sobjects/' . $object . '/describe/';
        $args = [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'method' => 'GET',
        ];

        $response = $this->remote_request($url, $args);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        $fields = [];
        if (isset($response_body['fields'])) {
            $skip_list = ['OwnerId', 'AccountId', 'ContactId'];
            foreach ($response_body['fields'] as $field) {
                if( in_array($field['name'], $skip_list) ) continue;

                if ($field['updateable'] == true && strpos($field['name'], '__c') === false) {
                    $description = '';
                    if (isset($field['picklistValues']) && !empty($field['picklistValues'])) {
                        $values = wp_list_pluck($field['picklistValues'], 'value');
                        $description = implode(', ', $values);
                    }

                    $label = $field['label'];

                    if (in_array($object, ['Account', 'Contact', 'Opportunity', 'Case'])) {
                        $label = $label . " [$object]";
                    }

                    if (
                        ($object == 'Account' && $field['name'] == 'Name') ||
                        ($object == 'Contact' && $field['name'] == 'LastName') ||
                        ($object == 'Lead' && in_array($field['name'], ['Company', 'LastName', 'Email'])) ||
                        ($object == 'Opportunity' && in_array($field['name'], ['Name', 'CloseDate']))
                    ) {
                        $description = 'Required';
                    }

                    $fields[] = ['key' => strtolower($object) . '__' . $field['type'] . '__' . $field['name'], 'value' => $label, 'description' => $description];
                }
            }
        }

        return $fields;
    }
}

$salesforce = ADFOIN_Salesforce::get_instance();
add_action('adfoin_salesforce_job_queue', 'adfoin_salesforce_job_queue', 10, 1);

function adfoin_salesforce_job_queue($data) {
    adfoin_salesforce_send_data($data['record'], $data['posted_data']);
}

/*
 * Handles sending data to Salesforce API
 */
function adfoin_salesforce_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if ( isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data) ) return;

    $data = $record_data['field_data'];
    $task = $record['task'];
    $salesforce = ADFOIN_Salesforce::get_instance();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $owner_id = isset($data['ownerId']) ? $data['ownerId'] : '';

    unset($data['credId']);
    unset($data['ownerId']);

    // Load credentials if provided
    if ($cred_id) {
        $salesforce->load_credentials($cred_id);
    }

    if ($task == 'add_lead') {
        $campaign_id = isset($data['campaignId']) ? $data['campaignId'] : '';

        unset($data['campaignId']);
        
        $lead_data = [];
        foreach ($data as $key => $value) {
            list(, , $key) = explode('__', $key, 3);
            $parsed_value = adfoin_get_parsed_values($value, $posted_data);

            // Use explicit null/'' check instead of truthy so legitimate
            // values like 0 (e.g., NumberOfEmployees) and false (boolean
            // picklists) aren't silently dropped.
            if ('' !== $parsed_value && null !== $parsed_value) {
                $lead_data[$key] = $parsed_value;
            }
        }

        if ($owner_id) {
            $lead_data['OwnerId'] = $owner_id;
        }

        $lead_id = $salesforce->create_or_update_lead($lead_data, $record);

        if ($campaign_id && $lead_id) {
            $salesforce->assign_lead_to_campaign($lead_id, $campaign_id, $record);
        }
    }

    if( $task == 'add_contact' ) {
        $account_data = [];
        $contact_data = [];
        $opportunity_data = [];
        $case_data = [];

        foreach ($data as $key => $value) {
            list($object, $type, $key) = explode('__', $key, 3);
            $parsed_value = adfoin_get_parsed_values($value, $posted_data);

            if ('' !== $parsed_value && null !== $parsed_value) {
                if ($object == 'account') {
                    $account_data[$key] = $parsed_value;
                } elseif ($object == 'contact') {
                    $contact_data[$key] = $parsed_value;
                } elseif ($object == 'opportunity') {
                    $opportunity_data[$key] = $parsed_value;
                } elseif ($object == 'case') {
                    $case_data[$key] = $parsed_value;
                }
            }
        }

        if (!empty($account_data)) {
            if ($owner_id) {
                $account_data['OwnerId'] = $owner_id;
            }
            $account_id = $salesforce->create_or_update_account($account_data, $record);
        }

        if (!empty($contact_data)) {
            if (isset($account_id)) {
                $contact_data['AccountId'] = $account_id;
            }
            if ($owner_id) {
                $contact_data['OwnerId'] = $owner_id;
            }
            $contact_id = $salesforce->create_or_update_contact($contact_data, $record);
        }

        if (!empty($opportunity_data)) {
            if (isset($account_id)) {
                $opportunity_data['AccountId'] = $account_id;
            }
            if (isset($contact_id)) {
                $opportunity_data['ContactId'] = $contact_id;
            }
            if ($owner_id) {
                $opportunity_data['OwnerId'] = $owner_id;
            }
            $opportunity_id = $salesforce->create_opportunity($opportunity_data, $record);
        }

        if (!empty($case_data)) {
            if (isset($account_id)) {
                $case_data['AccountId'] = $account_id;
            }
            if (isset($contact_id)) {
                $case_data['ContactId'] = $contact_id;
            }
            if ($owner_id) {
                $case_data['OwnerId'] = $owner_id;
            }
            $case_id = $salesforce->create_case($case_data, $record);
        }
    }
}