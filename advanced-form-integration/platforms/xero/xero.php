<?php

/**
 * Xero — Create/Update Contact and Create Invoice via OAuth2.
 *
 * Tenant is discovered via GET /connections after auth and sent on
 * every request as the Xero-tenant-id header.
 *
 * @link https://developer.xero.com/documentation/guides/oauth2/overview/
 * @link https://developer.xero.com/documentation/api/accounting/contacts
 * @link https://developer.xero.com/documentation/api/accounting/invoices
 */

class ADFOIN_Xero extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'xero';

    const AUTHORIZATION_ENDPOINT = 'https://login.xero.com/identity/connect/authorize';
    const TOKEN_ENDPOINT         = 'https://identity.xero.com/connect/token';
    const API_BASE               = 'https://api.xero.com/api.xro/2.0/';
    const CONNECTIONS_ENDPOINT   = 'https://api.xero.com/connections';
    // accounting.contacts + accounting.transactions cover Contacts +
    // Invoices. offline_access keeps the refresh_token alive past the
    // 30-minute access-token TTL. openid + profile + email are required
    // when the app is configured for OIDC.
    const DEFAULT_SCOPE          = 'openid profile email accounting.contacts accounting.transactions offline_access';

    private static $instance;

    protected $client_id     = '';
    protected $client_secret = '';
    protected $access_token  = '';
    protected $refresh_token = '';
    protected $expires_at    = 0;

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

        add_action( 'wp_ajax_adfoin_get_xero_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_xero_credentials', array( $this, 'save_credentials' ) );
        add_action( 'wp_ajax_adfoin_get_xero_tenants', array( $this, 'ajax_get_tenants' ) );
        add_action( 'wp_ajax_adfoin_get_xero_fields', array( $this, 'ajax_get_fields' ) );

        add_action( 'adfoin_xero_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );
    }

    public function register_actions( $actions ) {
        $actions['xero'] = array(
            'title' => __( 'Xero', 'advanced-form-integration' ),
            'tasks' => array(
                'create_contact' => __( 'Create / Update Contact', 'advanced-form-integration' ),
                'create_invoice' => __( 'Create Invoice', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $tabs ) {
        $tabs['xero'] = __( 'Xero', 'advanced-form-integration' );
        return $tabs;
    }

    public function render_settings( $current_tab ) {
        if ( 'xero' !== $current_tab ) {
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
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to %s and create a new "Web app" type.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener" href="https://developer.xero.com/app/manage/">Xero Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Add this redirect URI to the app:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . sprintf( __( 'Copy the Client ID + Secret. The app needs at least these scopes: %s.', 'advanced-form-integration' ), '<code>accounting.contacts accounting.transactions offline_access</code>' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste them here and click Save & Authorize. After consent, AFI fetches the list of organisations the token has access to.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Xero', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'xero', __( 'Xero', 'advanced-form-integration' ), $fields, $instructions, $config );
    }

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/xero',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_oauth_callback' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_oauth_callback( $request ) {
        $params  = $request->get_params();
        $code    = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';
        $state   = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'xero' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Authorization failed — missing code or state.', 'advanced-form-integration' ) );
            exit;
        }

        $this->set_credentials_by_id( $cred_id );

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
     * Xero requires HTTP Basic auth for the token endpoint, same shape
     * as Intuit. (See xero-php-oauth2 reference SDK.)
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
            return new WP_Error( 'xero_missing_refresh_token', __( 'Xero refresh token is missing.', 'advanced-form-integration' ) );
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

    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $platform    = 'xero';
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
            'expires_at'    => 0,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && $cred['id'] === $id ) {
                if ( isset( $cred['client_id'] ) && $cred['client_id'] === $client_id
                    && isset( $cred['client_secret'] ) && $cred['client_secret'] === $client_secret ) {
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
                'state'         => self::issue_oauth_state( 'xero', $id ),
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

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'xero', $cred_id );
        if ( ! $credentials ) {
            return;
        }

        $this->cred_id       = $cred_id;
        $this->client_id     = isset( $credentials['client_id'] ) ? $credentials['client_id'] : '';
        $this->client_secret = isset( $credentials['client_secret'] ) ? $credentials['client_secret'] : '';
        $this->access_token  = isset( $credentials['access_token'] ) ? $credentials['access_token'] : '';
        $this->refresh_token = isset( $credentials['refresh_token'] ) ? $credentials['refresh_token'] : '';
        $this->expires_at    = isset( $credentials['expires_at'] ) ? (int) $credentials['expires_at'] : 0;
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/xero' );
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
        }
    }

    protected function reset_data() {
        $this->access_token  = '';
        $this->refresh_token = '';
        $this->expires_at    = 0;
        $this->save_data();
    }

    /**
     * AJAX: list Xero organisations the token has access to. Cached
     * per credential for 1 hour. Xero scopes each access token to ONE
     * or MORE tenants, so the user picks which one this action should
     * write to.
     */
    public function ajax_get_tenants() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( ! $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id.', 'advanced-form-integration' ) ) );
        }

        $cache_key = 'adfoin_xero_tenants_' . md5( $cred_id );
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            wp_send_json_success( $cached );
        }

        $this->set_credentials_by_id( $cred_id );

        if ( $this->is_token_expired() ) {
            $this->refresh_token();
        }

        if ( ! $this->access_token ) {
            wp_send_json_error( array( 'message' => __( 'Account not authorized.', 'advanced-form-integration' ) ) );
        }

        $response = wp_remote_get(
            self::CONNECTIONS_ENDPOINT,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Accept'        => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( 401 === $status ) {
            // Reactive refresh + retry.
            $this->refresh_token();
            $response = wp_remote_get(
                self::CONNECTIONS_ENDPOINT,
                array(
                    'timeout' => 30,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->access_token,
                        'Accept'        => 'application/json',
                    ),
                )
            );
            $status = (int) wp_remote_retrieve_response_code( $response );
        }

        if ( $status >= 400 ) {
            wp_send_json_error( array( 'message' => sprintf( 'HTTP %d', $status ) ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $list = array();
        if ( is_array( $body ) ) {
            foreach ( $body as $conn ) {
                if ( empty( $conn['tenantId'] ) ) {
                    continue;
                }
                $label = isset( $conn['tenantName'] ) ? $conn['tenantName'] : (string) $conn['tenantId'];
                $list[ (string) $conn['tenantId'] ] = $label;
            }
        }

        set_transient( $cache_key, $list, HOUR_IN_SECONDS );
        wp_send_json_success( $list );
    }

    /**
     * Public so the Pro add-on can reuse the authenticated transport.
     * Every Xero API call requires both Authorization AND
     * Xero-tenant-id headers — caller passes the tenant ID, we inject
     * everything else.
     */
    public function xero_request( $endpoint, $method = 'GET', $body = array(), $record = array(), $tenant_id = '' ) {
        if ( ! $this->access_token ) {
            return new WP_Error( 'xero_missing_token', __( 'Xero access token is missing. Re-authorize the account.', 'advanced-form-integration' ) );
        }

        if ( '' === (string) $tenant_id ) {
            return new WP_Error( 'xero_missing_tenant', __( 'Xero tenant ID is required.', 'advanced-form-integration' ) );
        }

        if ( $this->is_token_expired() ) {
            $this->refresh_token();
        }

        $url = self::API_BASE . ltrim( $endpoint, '/' );

        $args = array(
            'timeout' => 30,
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization'  => 'Bearer ' . $this->access_token,
                'Xero-tenant-id' => $tenant_id,
                'Accept'         => 'application/json',
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
            return new WP_Error( 'xero_http_error', wp_remote_retrieve_body( $response ), array( 'status' => $status ) );
        }

        return $response;
    }

    public function render_action_template() {
        ?>
        <script type="text/template" id="xero-action-template">
            <table class="form-table" v-if="action.task == 'create_contact' || action.task == 'create_invoice'">

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Xero Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credentialsLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=xero' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Organisation', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[tenantId]" v-model="fielddata.tenantId" required="required">
                            <option value=""><?php esc_html_e( 'Select Organisation...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(label, id) in fielddata.tenants" :value="id">{{ label }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': tenantsLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                        <p class="description"><?php esc_html_e( 'Pick which Xero organisation this action should write to.', 'advanced-form-integration' ); ?></p>
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
                <?php adfoin_pro_feature_notice( 'create_contact', 'Xero [PRO]', 'WooCommerce auto-fill' ); ?>

                <tr class="alternate" v-if="action.task == 'create_invoice'">
                    <th scope="row"><?php esc_html_e( 'Invoice Tips', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php printf(
                            wp_kses(
                                __( 'Either map a Contact ID (existing) or contact fields so AFI can create one. Provide invoice lines as JSON — each line needs Description, Quantity, UnitAmount, and AccountCode. Need an easier setup for WooCommerce orders? <a href="%s">Upgrade to AFI Pro</a> to auto-build line items from your WC cart.', 'advanced-form-integration' ),
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

        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';
        $fields = ( 'create_invoice' === $task ) ? $this->get_invoice_fields() : $this->get_contact_fields();
        wp_send_json_success( $fields );
    }

    protected function get_contact_fields() {
        return array(
            array( 'key' => 'contact_id',      'value' => __( 'Existing Contact ID (skips create)', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_number',  'value' => __( 'Contact Number / External ID', 'advanced-form-integration' ), 'description' => __( 'Your stable cross-system identifier. AFI uses this for upsert when set.', 'advanced-form-integration' ) ),
            array( 'key' => 'name',            'value' => __( 'Name (required when no ContactID)', 'advanced-form-integration' ), 'description' => __( 'Xero requires Name. Falls back to "First Last" or Company when blank.', 'advanced-form-integration' ) ),
            array( 'key' => 'first_name',      'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name',       'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'email_address',   'value' => __( 'Email Address', 'advanced-form-integration' ) ),
            array( 'key' => 'phone_default',   'value' => __( 'Phone (Default)', 'advanced-form-integration' ) ),
            array( 'key' => 'phone_mobile',    'value' => __( 'Phone (Mobile)', 'advanced-form-integration' ) ),
            array( 'key' => 'address_line1',   'value' => __( 'Billing Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'address_line2',   'value' => __( 'Billing Address Line 2', 'advanced-form-integration' ) ),
            array( 'key' => 'city',            'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'region',          'value' => __( 'Region / State', 'advanced-form-integration' ) ),
            array( 'key' => 'postal_code',     'value' => __( 'Postal / ZIP Code', 'advanced-form-integration' ) ),
            array( 'key' => 'country',         'value' => __( 'Country', 'advanced-form-integration' ) ),
            array( 'key' => 'tax_number',      'value' => __( 'Tax Number', 'advanced-form-integration' ) ),
            array( 'key' => 'company_number',  'value' => __( 'Company Number', 'advanced-form-integration' ) ),
        );
    }

    protected function get_invoice_fields() {
        $contact_fields = $this->get_contact_fields();
        $contact_fields = array_values( array_filter( $contact_fields, function ( $f ) {
            return 'contact_id' !== $f['key'];
        } ) );

        $invoice_fields = array(
            array( 'key' => 'contact_id',     'value' => __( 'Existing Xero Contact ID', 'advanced-form-integration' ), 'description' => __( 'If empty, AFI will create or upsert the contact from the fields below before creating the invoice.', 'advanced-form-integration' ) ),
            array( 'key' => 'reference',      'value' => __( 'Reference', 'advanced-form-integration' ) ),
            array( 'key' => 'invoice_number', 'value' => __( 'Invoice Number (overrides auto-assignment)', 'advanced-form-integration' ) ),
            array( 'key' => 'date',           'value' => __( 'Invoice Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'due_date',       'value' => __( 'Due Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'currency_code',  'value' => __( 'Currency Code (e.g. USD)', 'advanced-form-integration' ) ),
            array( 'key' => 'status',         'value' => __( 'Status (DRAFT / SUBMITTED / AUTHORISED)', 'advanced-form-integration' ), 'description' => __( 'Default DRAFT. Use AUTHORISED to create an "Approved" invoice ready to send.', 'advanced-form-integration' ) ),
            array( 'key' => 'line_amount_types','value' => __( 'Line Amount Types (Exclusive / Inclusive / NoTax)', 'advanced-form-integration' ) ),
            array( 'key' => 'lines_json',     'value' => __( 'Line Items (JSON Array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: [{"Description":"Service","Quantity":1,"UnitAmount":50,"AccountCode":"200"}]', 'advanced-form-integration' ) ),
            array( 'key' => 'send_email',     'value' => __( 'Send invoice email after create (true/false)', 'advanced-form-integration' ), 'description' => __( 'Only works on AUTHORISED invoices — Xero will not email DRAFTs.', 'advanced-form-integration' ) ),
        );

        return array_merge( $contact_fields, $invoice_fields );
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
        $tenant_id  = isset( $field_data['tenantId'] ) ? trim( (string) $field_data['tenantId'] ) : '';
        $task       = isset( $record['task'] ) ? $record['task'] : '';

        if ( ! $cred_id || ! $tenant_id ) {
            adfoin_add_to_log( new WP_Error( 'xero_missing_account', __( 'Xero account or organisation not selected.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $this->set_credentials_by_id( $cred_id );

        if ( 'create_invoice' === $task ) {
            $this->process_invoice( $record, $field_data, $posted_data, $tenant_id );
        } else {
            $this->process_contact( $record, $field_data, $posted_data, $tenant_id );
        }
    }

    /**
     * Public so the Pro add-on can build a Xero Contact body without
     * duplicating the field-to-API translation logic.
     */
    public function collect_contact_payload( $field_data, $posted_data ) {
        $payload = array();

        $first   = $this->parse_field_value( $field_data, 'first_name', $posted_data );
        $last    = $this->parse_field_value( $field_data, 'last_name', $posted_data );
        $name    = $this->parse_field_value( $field_data, 'name', $posted_data );

        if ( $first ) { $payload['FirstName'] = $first; }
        if ( $last )  { $payload['LastName']  = $last;  }
        if ( $name )  { $payload['Name']      = $name;  }

        // Xero needs Name. Fall back to "First Last" when blank.
        if ( empty( $payload['Name'] ) ) {
            if ( $first || $last ) {
                $payload['Name'] = trim( $first . ' ' . $last );
            }
        }

        $email = $this->parse_field_value( $field_data, 'email_address', $posted_data );
        if ( $email ) {
            $payload['EmailAddress'] = $email;
        }

        $contact_number = $this->parse_field_value( $field_data, 'contact_number', $posted_data );
        if ( $contact_number ) {
            $payload['ContactNumber'] = $contact_number;
        }

        $tax_number = $this->parse_field_value( $field_data, 'tax_number', $posted_data );
        if ( $tax_number ) {
            $payload['TaxNumber'] = $tax_number;
        }

        $company_number = $this->parse_field_value( $field_data, 'company_number', $posted_data );
        if ( $company_number ) {
            $payload['CompanyNumber'] = $company_number;
        }

        // Phones — Xero accepts an array keyed by PhoneType enum.
        $phones = array();
        $phone_default = $this->parse_field_value( $field_data, 'phone_default', $posted_data );
        if ( $phone_default ) {
            $phones[] = array( 'PhoneType' => 'DEFAULT', 'PhoneNumber' => $phone_default );
        }
        $phone_mobile = $this->parse_field_value( $field_data, 'phone_mobile', $posted_data );
        if ( $phone_mobile ) {
            $phones[] = array( 'PhoneType' => 'MOBILE', 'PhoneNumber' => $phone_mobile );
        }
        if ( ! empty( $phones ) ) {
            $payload['Phones'] = $phones;
        }

        // Address — Xero wants Addresses[] with AddressType=STREET.
        $address = array( 'AddressType' => 'STREET' );
        $has_addr = false;
        foreach ( array(
            'address_line1' => 'AddressLine1',
            'address_line2' => 'AddressLine2',
            'city'          => 'City',
            'region'        => 'Region',
            'postal_code'   => 'PostalCode',
            'country'       => 'Country',
        ) as $form_key => $api_key ) {
            $value = $this->parse_field_value( $field_data, $form_key, $posted_data );
            if ( '' !== $value ) {
                $address[ $api_key ] = $value;
                $has_addr = true;
            }
        }
        if ( $has_addr ) {
            $payload['Addresses'] = array( $address );
        }

        return $payload;
    }

    /**
     * Look up an existing contact by ContactNumber via Xero's
     * `Contacts?where=ContactNumber=="..."` filter. Returns the
     * ContactID or '' on miss. Critical for WC upsert: same shopper →
     * same Xero contact instead of duplicates.
     */
    public function find_contact_id_by_number( $tenant_id, $contact_number ) {
        if ( '' === (string) $contact_number || '' === (string) $tenant_id ) {
            return '';
        }
        $escaped = str_replace( '"', '\\"', (string) $contact_number );
        $response = $this->xero_request(
            'Contacts?where=' . rawurlencode( 'ContactNumber=="' . $escaped . '"' ),
            'GET',
            array(),
            array(), // empty record — a 404 is the "not found" signal, not a log-worthy error
            $tenant_id
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['Contacts'][0]['ContactID'] ) ) {
            return (string) $body['Contacts'][0]['ContactID'];
        }
        return '';
    }

    /**
     * Look up by email. Used as the WC autofill upsert key when no
     * ContactNumber is set.
     */
    public function find_contact_id_by_email( $tenant_id, $email ) {
        if ( '' === (string) $email || '' === (string) $tenant_id ) {
            return '';
        }
        $escaped = str_replace( '"', '\\"', (string) $email );
        $response = $this->xero_request(
            'Contacts?where=' . rawurlencode( 'EmailAddress=="' . $escaped . '"' ),
            'GET',
            array(),
            array(),
            $tenant_id
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['Contacts'][0]['ContactID'] ) ) {
            return (string) $body['Contacts'][0]['ContactID'];
        }
        return '';
    }

    /**
     * Build the Xero Invoice payload. Returns WP_Error when LineItems
     * are missing or unparseable. Pro overrides this with WC-derived
     * lines before calling.
     */
    public function collect_invoice_payload( $field_data, $posted_data, $contact_id, $extra_lines = array() ) {
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
            return new WP_Error( 'xero_missing_lines', __( 'Cannot create invoice: no LineItems.', 'advanced-form-integration' ) );
        }

        $invoice = array(
            'Type'      => 'ACCREC',
            'Contact'   => array( 'ContactID' => (string) $contact_id ),
            'LineItems' => $lines,
        );

        $reference = $this->parse_field_value( $field_data, 'reference', $posted_data );
        if ( '' !== $reference ) { $invoice['Reference'] = $reference; }

        $invoice_number = $this->parse_field_value( $field_data, 'invoice_number', $posted_data );
        if ( '' !== $invoice_number ) { $invoice['InvoiceNumber'] = $invoice_number; }

        $date = $this->parse_field_value( $field_data, 'date', $posted_data );
        if ( '' !== $date ) { $invoice['Date'] = $date; }

        $due_date = $this->parse_field_value( $field_data, 'due_date', $posted_data );
        if ( '' !== $due_date ) { $invoice['DueDate'] = $due_date; }

        $currency = $this->parse_field_value( $field_data, 'currency_code', $posted_data );
        if ( '' !== $currency ) { $invoice['CurrencyCode'] = strtoupper( $currency ); }

        $status = strtoupper( $this->parse_field_value( $field_data, 'status', $posted_data ) );
        if ( in_array( $status, array( 'DRAFT', 'SUBMITTED', 'AUTHORISED' ), true ) ) {
            $invoice['Status'] = $status;
        }

        $line_amount_types = $this->parse_field_value( $field_data, 'line_amount_types', $posted_data );
        if ( in_array( $line_amount_types, array( 'Exclusive', 'Inclusive', 'NoTax' ), true ) ) {
            $invoice['LineAmountTypes'] = $line_amount_types;
        }

        return $invoice;
    }

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

    protected function process_contact( $record, $field_data, $posted_data, $tenant_id ) {
        $payload = $this->collect_contact_payload( $field_data, $posted_data );

        if ( empty( $payload ) ) {
            adfoin_add_to_log( new WP_Error( 'xero_empty_contact', __( 'No contact fields mapped.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $contact_id = $this->parse_field_value( $field_data, 'contact_id', $posted_data );

        if ( '' === $contact_id && ! empty( $payload['ContactNumber'] ) ) {
            $contact_id = $this->find_contact_id_by_number( $tenant_id, $payload['ContactNumber'] );
        }

        if ( '' === $contact_id && ! empty( $payload['EmailAddress'] ) ) {
            $contact_id = $this->find_contact_id_by_email( $tenant_id, $payload['EmailAddress'] );
        }

        if ( $contact_id ) {
            $payload['ContactID'] = (string) $contact_id;
        }

        // Xero POST /Contacts upserts when ContactID is present in the body.
        $this->xero_request( 'Contacts', 'POST', array( 'Contacts' => array( $payload ) ), $record, $tenant_id );
    }

    protected function process_invoice( $record, $field_data, $posted_data, $tenant_id ) {
        $contact_id = $this->parse_field_value( $field_data, 'contact_id', $posted_data );

        if ( '' === $contact_id ) {
            $contact_payload = $this->collect_contact_payload( $field_data, $posted_data );
            if ( empty( $contact_payload ) ) {
                adfoin_add_to_log( new WP_Error( 'xero_missing_contact', __( 'Provide a Contact ID or map contact fields to create one.', 'advanced-form-integration' ) ), '', array(), $record );
                return;
            }

            if ( ! empty( $contact_payload['ContactNumber'] ) ) {
                $contact_id = $this->find_contact_id_by_number( $tenant_id, $contact_payload['ContactNumber'] );
            }
            if ( ! $contact_id && ! empty( $contact_payload['EmailAddress'] ) ) {
                $contact_id = $this->find_contact_id_by_email( $tenant_id, $contact_payload['EmailAddress'] );
            }

            if ( ! $contact_id ) {
                $response = $this->xero_request( 'Contacts', 'POST', array( 'Contacts' => array( $contact_payload ) ), $record, $tenant_id );
                if ( is_wp_error( $response ) ) {
                    return;
                }
                $body       = json_decode( wp_remote_retrieve_body( $response ), true );
                $contact_id = isset( $body['Contacts'][0]['ContactID'] ) ? (string) $body['Contacts'][0]['ContactID'] : '';
                if ( ! $contact_id ) {
                    return;
                }
            }
        }

        $invoice = $this->collect_invoice_payload( $field_data, $posted_data, $contact_id );
        if ( is_wp_error( $invoice ) ) {
            adfoin_add_to_log( $invoice, '', array(), $record );
            return;
        }

        $response = $this->xero_request( 'Invoices', 'POST', array( 'Invoices' => array( $invoice ) ), $record, $tenant_id );
        if ( is_wp_error( $response ) ) {
            return;
        }

        $send_flag = strtolower( (string) $this->parse_field_value( $field_data, 'send_email', $posted_data ) );
        if ( in_array( $send_flag, array( 'true', '1', 'yes', 'on' ), true ) ) {
            $body       = json_decode( wp_remote_retrieve_body( $response ), true );
            $invoice_id = isset( $body['Invoices'][0]['InvoiceID'] ) ? (string) $body['Invoices'][0]['InvoiceID'] : '';
            $status     = isset( $body['Invoices'][0]['Status'] ) ? (string) $body['Invoices'][0]['Status'] : '';

            // Xero only emails AUTHORISED invoices. DRAFT / SUBMITTED
            // would 4xx — skip rather than spamming the log.
            if ( $invoice_id && 'AUTHORISED' === $status ) {
                $this->xero_request( 'Invoices/' . rawurlencode( $invoice_id ) . '/Email', 'POST', array(), $record, $tenant_id );
            }
        }
    }
}

ADFOIN_Xero::get_instance();
