<?php

/**
 * QuickBooks Online — Create/Update Customer and Create Invoice.
 *
 * OAuth2 with production + sandbox environment switching.
 *
 * @link https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
 * @link https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/customer
 * @link https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice
 */

class ADFOIN_QuickBooksOnline extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'quickbooksonline';

    const AUTHORIZATION_ENDPOINT = 'https://appcenter.intuit.com/connect/oauth2';
    const TOKEN_ENDPOINT         = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    const API_BASE_PRODUCTION    = 'https://quickbooks.api.intuit.com/v3/company/';
    const API_BASE_SANDBOX       = 'https://sandbox-quickbooks.api.intuit.com/v3/company/';
    const DEFAULT_SCOPE          = 'com.intuit.quickbooks.accounting';
    // Pin a schema version so future API revs don't surprise users. Bump
    // when you've verified the integration against a newer minorversion.
    const MINOR_VERSION          = '70';

    private static $instance;

    protected $client_id     = '';
    protected $client_secret = '';
    protected $access_token  = '';
    protected $refresh_token = '';
    protected $expires_at    = 0;
    protected $realm_id      = '';
    protected $environment   = 'production';
    protected $scope         = self::DEFAULT_SCOPE;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->authorization_endpoint = self::AUTHORIZATION_ENDPOINT;
        $this->token_endpoint         = self::TOKEN_ENDPOINT;
        $this->refresh_token_endpoint = self::TOKEN_ENDPOINT;

        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_quickbooksonline_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_quickbooksonline_credentials', array( $this, 'save_credentials' ) );
        add_action( 'wp_ajax_adfoin_get_quickbooksonline_fields', array( $this, 'ajax_get_fields' ) );
        add_action( 'wp_ajax_adfoin_get_quickbooksonline_items', array( $this, 'ajax_get_items' ) );

        add_action( 'adfoin_quickbooksonline_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );
    }

    public function register_actions( $actions ) {
        $actions['quickbooksonline'] = array(
            'title' => __( 'QuickBooks Online', 'advanced-form-integration' ),
            'tasks' => array(
                'create_customer' => __( 'Create / Update Customer', 'advanced-form-integration' ),
                'create_invoice'  => __( 'Create Invoice', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $tabs ) {
        $tabs['quickbooksonline'] = __( 'QuickBooks Online', 'advanced-form-integration' );
        return $tabs;
    }

    public function render_settings( $current_tab ) {
        if ( 'quickbooksonline' !== $current_tab ) {
            return;
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $redirect_uri = $this->get_redirect_uri();

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
                'name'        => 'environment',
                'label'       => __( 'Environment', 'advanced-form-integration' ),
                'type'        => 'select',
                'required'    => true,
                'options'     => array(
                    'production' => __( 'Production', 'advanced-form-integration' ),
                    'sandbox'    => __( 'Sandbox', 'advanced-form-integration' ),
                ),
                'description' => __( 'Use Sandbox while testing — switch to Production for live data.', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to %s and create a new app.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener" href="https://developer.intuit.com/app/developer/dashboard">Intuit Developer Dashboard</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Under the app, open "Keys & OAuth" and copy the Client ID + Secret for the environment you want (Production or Sandbox).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Add this redirect URI to the app settings:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . sprintf( __( 'Make sure the app requests the %s scope.', 'advanced-form-integration' ), '<code>com.intuit.quickbooks.accounting</code>' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste your Client ID + Secret here, pick the environment, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect QuickBooks Online', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'quickbooksonline', __( 'QuickBooks Online', 'advanced-form-integration' ), $fields, $instructions, $config );
    }

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/quickbooksonline',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_oauth_callback' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Intuit returns `code`, `state`, and `realmId` as query params.
     * We persist realmId on the credential record so every subsequent
     * API call has the right company prefix.
     */
    public function handle_oauth_callback( $request ) {
        $params   = $request->get_params();
        $code     = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';
        $state    = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';
        $realm_id = isset( $params['realmId'] ) ? sanitize_text_field( $params['realmId'] ) : '';

        $context = self::consume_oauth_state( $state, 'quickbooksonline' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Authorization failed — missing code or state.', 'advanced-form-integration' ) );
            exit;
        }

        $this->set_credentials_by_id( $cred_id );
        $this->realm_id = $realm_id;

        $response = $this->request_token( $code );

        $success = false;
        $message = 'Unknown error';

        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $body['access_token'] ) ) {
                $success = true;
                $message = __( 'Connected successfully!', 'advanced-form-integration' );
            } else {
                $message = isset( $body['error_description'] ) ? $body['error_description'] : __( 'Token exchange failed.', 'advanced-form-integration' );
            }
        } else {
            $message = is_wp_error( $response ) ? $response->get_error_message() : __( 'HTTP error during token exchange.', 'advanced-form-integration' );
        }

        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    /**
     * QBO's token endpoint expects HTTP Basic auth (client_id:client_secret
     * base64-encoded) — NOT client credentials in the body like Moneybird.
     * This is the single most common stumbling block when wiring Intuit
     * OAuth from scratch.
     */
    protected function request_token( $code ) {
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
            ),
            'body'    => http_build_query(
                array(
                    'grant_type'   => 'authorization_code',
                    'code'         => $code,
                    'redirect_uri' => $this->get_redirect_uri(),
                ),
                '',
                '&'
            ),
        );

        $response = wp_remote_post( $this->token_endpoint, $args );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->apply_token_response( is_array( $body ) ? $body : array() );
        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( ! $this->refresh_token ) {
            return new WP_Error( 'quickbooksonline_missing_refresh_token', __( 'QuickBooks refresh token is missing.', 'advanced-form-integration' ) );
        }

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
            ),
            'body'    => http_build_query(
                array(
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $this->refresh_token,
                ),
                '',
                '&'
            ),
        );

        $response = wp_remote_post( $this->refresh_token_endpoint, $args );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->apply_token_response( is_array( $body ) ? $body : array() );
        $this->save_data();

        return $response;
    }

    /**
     * AJAX: save credentials, then return the auth URL for the popup to
     * navigate to. State binds the popup callback back to this record so
     * tokens land on the right credential.
     */
    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $platform    = 'quickbooksonline';
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
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';
        $environment   = isset( $_POST['environment'] ) ? sanitize_text_field( wp_unslash( $_POST['environment'] ) ) : 'production';

        if ( ! in_array( $environment, array( 'production', 'sandbox' ), true ) ) {
            $environment = 'production';
        }

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'environment'   => $environment,
            'realm_id'      => '',
            'access_token'  => '',
            'refresh_token' => '',
            'expires_at'    => 0,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && $cred['id'] === $id ) {
                // Preserve tokens + realm_id when only the title changes.
                if ( isset( $cred['client_id'] ) && $cred['client_id'] === $client_id
                    && isset( $cred['client_secret'] ) && $cred['client_secret'] === $client_secret
                    && isset( $cred['environment'] ) && $cred['environment'] === $environment ) {
                    $new_data['realm_id']      = isset( $cred['realm_id'] ) ? $cred['realm_id'] : '';
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['expires_at']    = isset( $cred['expires_at'] ) ? $cred['expires_at'] : 0;
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
                'scope'         => self::DEFAULT_SCOPE,
                'state'         => self::issue_oauth_state( 'quickbooksonline', $id ),
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function set_credentials_by_id( $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'quickbooksonline', $cred_id );

        if ( ! $credentials ) {
            return;
        }

        $this->cred_id       = $cred_id;
        $this->client_id     = isset( $credentials['client_id'] ) ? $credentials['client_id'] : '';
        $this->client_secret = isset( $credentials['client_secret'] ) ? $credentials['client_secret'] : '';
        $this->access_token  = isset( $credentials['access_token'] ) ? $credentials['access_token'] : '';
        $this->refresh_token = isset( $credentials['refresh_token'] ) ? $credentials['refresh_token'] : '';
        $this->expires_at    = isset( $credentials['expires_at'] ) ? (int) $credentials['expires_at'] : 0;
        $this->realm_id      = isset( $credentials['realm_id'] ) ? $credentials['realm_id'] : '';
        $this->environment   = isset( $credentials['environment'] ) ? $credentials['environment'] : 'production';
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/quickbooksonline' );
    }

    protected function save_data() {
        // realm_id was captured on the OAuth callback BEFORE
        // request_token ran — persist it alongside the tokens.
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential( array( 'realm_id' => $this->realm_id ) );
        }
    }

    protected function reset_data() {
        $this->access_token  = '';
        $this->refresh_token = '';
        $this->expires_at    = 0;
        $this->save_data();
    }

    /**
     * Return the correct API base for the chosen environment plus
     * the realm_id prefix. Every QBO endpoint hangs off this.
     */
    protected function api_base() {
        $base = ( 'sandbox' === $this->environment ) ? self::API_BASE_SANDBOX : self::API_BASE_PRODUCTION;
        return $base . rawurlencode( $this->realm_id ) . '/';
    }

    /**
     * Public so the Pro add-on can reuse the same authenticated
     * transport without duplicating refresh / 401-retry logic.
     */
    public function qbo_request( $endpoint, $method = 'POST', $body = array(), $record = array(), $query_args = array() ) {
        if ( ! $this->access_token ) {
            return new WP_Error( 'quickbooksonline_missing_token', __( 'QuickBooks access token is missing. Re-authorize the account.', 'advanced-form-integration' ) );
        }

        if ( ! $this->realm_id ) {
            return new WP_Error( 'quickbooksonline_missing_realm', __( 'QuickBooks realmId (company) is missing. Re-authorize the account.', 'advanced-form-integration' ) );
        }

        // Proactive refresh on cached expiry. is_token_expired() returns
        // false when expires_at is unknown (the reactive 401 path
        // below handles that case).
        if ( $this->is_token_expired() ) {
            $this->refresh_token();
        }

        $query_args = array_merge( array( 'minorversion' => self::MINOR_VERSION ), $query_args );
        $url        = $this->api_base() . ltrim( $endpoint, '/' );
        $url        = add_query_arg( $query_args, $url );

        $args = array(
            'timeout' => 30,
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Accept'        => 'application/json',
            ),
        );

        if ( in_array( $args['method'], array( 'POST', 'PATCH', 'PUT' ), true ) ) {
            $args['headers']['Content-Type'] = 'application/json';
            if ( ! empty( $body ) ) {
                $args['body'] = wp_json_encode( $body );
            }
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );
        $status   = (int) wp_remote_retrieve_response_code( $response );

        // Reactive 401: refresh once, then retry.
        if ( 401 === $status ) {
            $refresh = $this->refresh_token();
            if ( ! is_wp_error( $refresh ) && $this->access_token ) {
                $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
                $response                         = wp_remote_request( esc_url_raw( $url ), $args );
                $status                           = (int) wp_remote_retrieve_response_code( $response );
            }
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( $status >= 400 ) {
            return new WP_Error( 'quickbooksonline_http_error', wp_remote_retrieve_body( $response ), array( 'status' => $status ) );
        }

        return $response;
    }

    public function render_action_template() {
        ?>
        <script type="text/template" id="quickbooksonline-action-template">
            <table class="form-table" v-if="action.task == 'create_customer' || action.task == 'create_invoice'">

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'QuickBooks Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credentialsLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=quickbooksonline' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td>
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                    v-bind:key="field.value"
                    v-bind:field="field"
                    v-bind:trigger="trigger"
                    v-bind:action="action"
                    v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_customer', 'QuickBooks Online [PRO]', 'WooCommerce auto-fill' ); ?>

                <tr class="alternate" v-if="action.task == 'create_invoice'">
                    <th scope="row"><?php esc_html_e( 'Invoice Tips', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php printf(
                            wp_kses(
                                __( 'Either map a Customer ID (existing customer) or contact fields so AFI can create one. Provide invoice lines as JSON in the "Invoice Lines (JSON)" field — each line needs at minimum an ItemRef ID and a unit price. Need an easier setup for WooCommerce orders? <a href="%s">Upgrade to AFI Pro</a> to auto-build line items from your WC cart.', 'advanced-form-integration' ),
                                array( 'a' => array( 'href' => array() ) )
                            ),
                            esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) )
                        ); ?></p>
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

        $task    = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        if ( $cred_id ) {
            $this->set_credentials_by_id( $cred_id );
        }

        $fields = ( 'create_invoice' === $task ) ? $this->get_invoice_fields() : $this->get_customer_fields();
        wp_send_json_success( $fields );
    }

    /**
     * AJAX: list Items (products/services) on the company file. The Pro
     * Vue can use this to populate an Item picker, but the free template
     * just exposes the raw ItemRef ID via the JSON invoice-lines field.
     */
    public function ajax_get_items() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( ! $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id.', 'advanced-form-integration' ) ) );
        }

        $this->set_credentials_by_id( $cred_id );

        $response = $this->qbo_request(
            "query",
            'GET',
            array(),
            array(),
            array( 'query' => 'select Id, Name from Item where Active = true MAXRESULTS 500' )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $items = array();
        if ( ! empty( $body['QueryResponse']['Item'] ) && is_array( $body['QueryResponse']['Item'] ) ) {
            foreach ( $body['QueryResponse']['Item'] as $item ) {
                if ( ! empty( $item['Id'] ) && ! empty( $item['Name'] ) ) {
                    $items[ (string) $item['Id'] ] = $item['Name'];
                }
            }
        }

        wp_send_json_success( $items );
    }

    protected function get_customer_fields() {
        // Field set mirrors QBO's Customer resource. Display fields are
        // surfaced via merge tags from the form trigger.
        return array(
            array( 'key' => 'customer_id',     'value' => __( 'Existing Customer ID (skips create)', 'advanced-form-integration' ) ),
            array( 'key' => 'display_name',    'value' => __( 'Display Name', 'advanced-form-integration' ), 'description' => __( 'Required if no Company / First-Last combination is provided. Must be unique in QuickBooks.', 'advanced-form-integration' ) ),
            array( 'key' => 'given_name',      'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'family_name',     'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'company_name',    'value' => __( 'Company Name', 'advanced-form-integration' ) ),
            array( 'key' => 'email',           'value' => __( 'Email', 'advanced-form-integration' ) ),
            array( 'key' => 'phone',           'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'mobile',          'value' => __( 'Mobile', 'advanced-form-integration' ) ),
            array( 'key' => 'website',         'value' => __( 'Website', 'advanced-form-integration' ) ),
            array( 'key' => 'address_line1',   'value' => __( 'Billing Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'address_line2',   'value' => __( 'Billing Address Line 2', 'advanced-form-integration' ) ),
            array( 'key' => 'city',            'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'state',           'value' => __( 'State / Region', 'advanced-form-integration' ) ),
            array( 'key' => 'postal_code',     'value' => __( 'Postal / ZIP Code', 'advanced-form-integration' ) ),
            array( 'key' => 'country',         'value' => __( 'Country', 'advanced-form-integration' ) ),
            array( 'key' => 'notes',           'value' => __( 'Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        );
    }

    protected function get_invoice_fields() {
        $customer_fields = $this->get_customer_fields();

        // Pull out the lookup row — invoice context uses customer_id
        // separately below for clarity.
        $customer_fields = array_values( array_filter( $customer_fields, function ( $f ) {
            return 'customer_id' !== $f['key'];
        } ) );

        $invoice_fields = array(
            array( 'key' => 'customer_id',  'value' => __( 'Existing QBO Customer ID', 'advanced-form-integration' ), 'description' => __( 'If empty, AFI will create or upsert the customer from the fields below before creating the invoice.', 'advanced-form-integration' ) ),
            array( 'key' => 'doc_number',   'value' => __( 'Document / Invoice Number', 'advanced-form-integration' ) ),
            array( 'key' => 'txn_date',     'value' => __( 'Transaction Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'due_date',     'value' => __( 'Due Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'currency',     'value' => __( 'Currency Code (e.g. USD)', 'advanced-form-integration' ) ),
            array( 'key' => 'bill_email',   'value' => __( 'Billing Email (for send_invoice)', 'advanced-form-integration' ) ),
            array( 'key' => 'private_note', 'value' => __( 'Private Note', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'customer_memo','value' => __( 'Message on Invoice', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'lines_json',   'value' => __( 'Invoice Lines (JSON Array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: [{"Amount":100,"DetailType":"SalesItemLineDetail","SalesItemLineDetail":{"ItemRef":{"value":"1"},"Qty":1,"UnitPrice":100}}]', 'advanced-form-integration' ) ),
            array( 'key' => 'send_email',   'value' => __( 'Send invoice email after create (true/false)', 'advanced-form-integration' ) ),
        );

        return array_merge( $customer_fields, $invoice_fields );
    }

    public function handle_job_queue( $data ) {
        $record      = isset( $data['record'] ) ? $data['record'] : array();
        $posted_data = isset( $data['posted_data'] ) ? $data['posted_data'] : array();

        if ( empty( $record ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );
        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
        $task       = isset( $record['task'] ) ? $record['task'] : '';

        if ( ! $cred_id ) {
            adfoin_add_to_log( new WP_Error( 'quickbooksonline_missing_cred', __( 'No QuickBooks account selected.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $this->set_credentials_by_id( $cred_id );

        if ( 'create_invoice' === $task ) {
            $this->process_invoice( $record, $field_data, $posted_data );
        } else {
            $this->process_customer( $record, $field_data, $posted_data );
        }
    }

    protected function process_customer( $record, $field_data, $posted_data ) {
        $customer_payload = $this->collect_customer_payload( $field_data, $posted_data );

        if ( empty( $customer_payload ) ) {
            adfoin_add_to_log( new WP_Error( 'quickbooksonline_empty_customer', __( 'No customer fields mapped.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $existing_id = $this->parse_field_value( $field_data, 'customer_id', $posted_data );

        if ( '' === $existing_id && ! empty( $customer_payload['PrimaryEmailAddr']['Address'] ) ) {
            $existing_id = $this->find_customer_id_by_email( $customer_payload['PrimaryEmailAddr']['Address'] );
        }

        if ( $existing_id ) {
            $sync_token = $this->get_customer_sync_token( $existing_id );
            if ( '' === $sync_token ) {
                return;
            }
            $customer_payload['Id']         = (string) $existing_id;
            $customer_payload['SyncToken']  = (string) $sync_token;
            $customer_payload['sparse']     = true; // partial update
            $this->qbo_request( 'customer', 'POST', $customer_payload, $record );
            return;
        }

        $this->qbo_request( 'customer', 'POST', $customer_payload, $record );
    }

    protected function process_invoice( $record, $field_data, $posted_data ) {
        $customer_id = $this->parse_field_value( $field_data, 'customer_id', $posted_data );

        if ( '' === $customer_id ) {
            // Build the customer first, with email-based upsert.
            $customer_payload = $this->collect_customer_payload( $field_data, $posted_data );
            if ( empty( $customer_payload ) ) {
                adfoin_add_to_log( new WP_Error( 'quickbooksonline_missing_customer', __( 'Provide a Customer ID or map customer fields to create one.', 'advanced-form-integration' ) ), '', array(), $record );
                return;
            }

            if ( ! empty( $customer_payload['PrimaryEmailAddr']['Address'] ) ) {
                $customer_id = $this->find_customer_id_by_email( $customer_payload['PrimaryEmailAddr']['Address'] );
            }

            if ( ! $customer_id ) {
                $response = $this->qbo_request( 'customer', 'POST', $customer_payload, $record );
                if ( is_wp_error( $response ) ) {
                    return;
                }
                $body        = json_decode( wp_remote_retrieve_body( $response ), true );
                $customer_id = isset( $body['Customer']['Id'] ) ? (string) $body['Customer']['Id'] : '';
            }

            if ( ! $customer_id ) {
                return;
            }
        }

        $invoice_payload = $this->collect_invoice_payload( $field_data, $posted_data, $customer_id );

        if ( is_wp_error( $invoice_payload ) ) {
            adfoin_add_to_log( $invoice_payload, '', array(), $record );
            return;
        }

        $response = $this->qbo_request( 'invoice', 'POST', $invoice_payload, $record );
        if ( is_wp_error( $response ) ) {
            return;
        }

        $send_flag = strtolower( (string) $this->parse_field_value( $field_data, 'send_email', $posted_data ) );
        if ( in_array( $send_flag, array( 'true', '1', 'yes', 'on' ), true ) ) {
            $body       = json_decode( wp_remote_retrieve_body( $response ), true );
            $invoice_id = isset( $body['Invoice']['Id'] ) ? (string) $body['Invoice']['Id'] : '';
            $bill_email = $this->parse_field_value( $field_data, 'bill_email', $posted_data );
            if ( $invoice_id ) {
                $args = array();
                if ( '' !== $bill_email ) {
                    $args['sendTo'] = $bill_email;
                }
                $this->qbo_request( 'invoice/' . rawurlencode( $invoice_id ) . '/send', 'POST', array(), $record, $args );
            }
        }
    }

    /**
     * Public so Pro can reuse the same mapping for WC-autofill paths.
     */
    public function collect_customer_payload( $field_data, $posted_data ) {
        $payload = array();

        // Identity
        $given     = $this->parse_field_value( $field_data, 'given_name', $posted_data );
        $family    = $this->parse_field_value( $field_data, 'family_name', $posted_data );
        $display   = $this->parse_field_value( $field_data, 'display_name', $posted_data );
        $company   = $this->parse_field_value( $field_data, 'company_name', $posted_data );

        if ( $given )    { $payload['GivenName']        = $given; }
        if ( $family )   { $payload['FamilyName']       = $family; }
        if ( $company )  { $payload['CompanyName']      = $company; }
        if ( $display )  { $payload['DisplayName']      = $display; }

        // DisplayName is required when neither a Company nor a
        // FullyQualifiedName resolves — fall back to the most
        // distinctive available value to avoid a 400.
        if ( empty( $payload['DisplayName'] ) ) {
            if ( $company ) {
                $payload['DisplayName'] = $company;
            } elseif ( $given || $family ) {
                $payload['DisplayName'] = trim( $given . ' ' . $family );
            }
        }

        $email = $this->parse_field_value( $field_data, 'email', $posted_data );
        if ( $email ) {
            $payload['PrimaryEmailAddr'] = array( 'Address' => $email );
        }

        $phone  = $this->parse_field_value( $field_data, 'phone', $posted_data );
        if ( $phone )  { $payload['PrimaryPhone'] = array( 'FreeFormNumber' => $phone ); }

        $mobile = $this->parse_field_value( $field_data, 'mobile', $posted_data );
        if ( $mobile ) { $payload['Mobile']       = array( 'FreeFormNumber' => $mobile ); }

        $website = $this->parse_field_value( $field_data, 'website', $posted_data );
        if ( $website ) { $payload['WebAddr'] = array( 'URI' => $website ); }

        // Billing address — QBO accepts the address as a nested object.
        $address = array();
        foreach ( array(
            'address_line1' => 'Line1',
            'address_line2' => 'Line2',
            'city'          => 'City',
            'state'         => 'CountrySubDivisionCode',
            'postal_code'   => 'PostalCode',
            'country'       => 'Country',
        ) as $form_key => $api_key ) {
            $value = $this->parse_field_value( $field_data, $form_key, $posted_data );
            if ( '' !== $value ) {
                $address[ $api_key ] = $value;
            }
        }
        if ( ! empty( $address ) ) {
            $payload['BillAddr'] = $address;
        }

        $notes = $this->parse_field_value( $field_data, 'notes', $posted_data );
        if ( $notes ) {
            $payload['Notes'] = $notes;
        }

        return $payload;
    }

    /**
     * Build a QBO Invoice payload. Returns WP_Error when the invoice
     * lines JSON is invalid or empty (Pro overrides this with WC items
     * before calling).
     */
    public function collect_invoice_payload( $field_data, $posted_data, $customer_id, $extra_lines = array() ) {
        $lines = array();

        if ( ! empty( $extra_lines ) ) {
            $lines = array_merge( $lines, $extra_lines );
        }

        $lines_raw = $this->parse_field_value( $field_data, 'lines_json', $posted_data );
        if ( '' !== $lines_raw ) {
            $decoded = json_decode( $lines_raw, true );
            if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
                $lines = array_merge( $lines, $decoded );
            }
        }

        if ( empty( $lines ) ) {
            return new WP_Error( 'quickbooksonline_missing_lines', __( 'Cannot create invoice: no invoice lines.', 'advanced-form-integration' ) );
        }

        // QBO needs DetailType + SalesItemLineDetail per line. If a
        // caller supplied bare {Description, Amount, ItemRef.value},
        // wrap it into the documented shape so we don't 400.
        foreach ( $lines as &$line ) {
            if ( ! is_array( $line ) ) {
                continue;
            }
            if ( empty( $line['DetailType'] ) ) {
                $line['DetailType'] = 'SalesItemLineDetail';
            }
            if ( empty( $line['SalesItemLineDetail'] ) && isset( $line['ItemRef'] ) ) {
                $line['SalesItemLineDetail'] = array( 'ItemRef' => $line['ItemRef'] );
                unset( $line['ItemRef'] );
            }
        }
        unset( $line );

        $invoice = array(
            'CustomerRef' => array( 'value' => (string) $customer_id ),
            'Line'        => $lines,
        );

        $doc_number = $this->parse_field_value( $field_data, 'doc_number', $posted_data );
        if ( '' !== $doc_number ) { $invoice['DocNumber'] = $doc_number; }

        $txn_date = $this->parse_field_value( $field_data, 'txn_date', $posted_data );
        if ( '' !== $txn_date ) { $invoice['TxnDate'] = $txn_date; }

        $due_date = $this->parse_field_value( $field_data, 'due_date', $posted_data );
        if ( '' !== $due_date ) { $invoice['DueDate'] = $due_date; }

        $currency = $this->parse_field_value( $field_data, 'currency', $posted_data );
        if ( '' !== $currency ) { $invoice['CurrencyRef'] = array( 'value' => strtoupper( $currency ) ); }

        $bill_email = $this->parse_field_value( $field_data, 'bill_email', $posted_data );
        if ( '' !== $bill_email ) { $invoice['BillEmail'] = array( 'Address' => $bill_email ); }

        $private_note = $this->parse_field_value( $field_data, 'private_note', $posted_data );
        if ( '' !== $private_note ) { $invoice['PrivateNote'] = $private_note; }

        $customer_memo = $this->parse_field_value( $field_data, 'customer_memo', $posted_data );
        if ( '' !== $customer_memo ) { $invoice['CustomerMemo'] = array( 'value' => $customer_memo ); }

        return $invoice;
    }

    /**
     * Find a customer by email — used as the upsert key when neither
     * a Customer ID nor DisplayName uniqueness is guaranteed. Returns
     * the QBO Id or '' on miss.
     */
    public function find_customer_id_by_email( $email ) {
        if ( '' === (string) $email ) {
            return '';
        }
        // Escape single quotes per QBO query syntax (SQL-ish, ' → '').
        $escaped = str_replace( "'", "''", (string) $email );

        $response = $this->qbo_request(
            'query',
            'GET',
            array(),
            array(),
            array( 'query' => "select Id from Customer where PrimaryEmailAddr = '{$escaped}'" )
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['QueryResponse']['Customer'][0]['Id'] ) ) {
            return (string) $body['QueryResponse']['Customer'][0]['Id'];
        }
        return '';
    }

    /**
     * QBO's optimistic-locking update flow requires the current
     * SyncToken on the resource. Fetch it before the sparse update.
     */
    public function get_customer_sync_token( $customer_id ) {
        $response = $this->qbo_request( 'customer/' . rawurlencode( $customer_id ), 'GET' );
        if ( is_wp_error( $response ) ) {
            return '';
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset( $body['Customer']['SyncToken'] ) ? (string) $body['Customer']['SyncToken'] : '';
    }

    /**
     * Resolve a template tag against the posted form data and trim.
     * Public so Pro can call it from its own send-data path.
     */
    public function parse_field_value( $field_data, $key, $posted_data ) {
        if ( ! isset( $field_data[ $key ] ) ) {
            return '';
        }
        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        if ( is_array( $value ) ) {
            return '';
        }
        return is_string( $value ) ? trim( $value ) : (string) $value;
    }
}

ADFOIN_QuickBooksOnline::get_instance();
