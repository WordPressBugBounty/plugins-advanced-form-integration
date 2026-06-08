<?php

/**
 * Deputy — Create Employee via POST {install_url}/api/v1/supervise/employee.
 *
 * Multi-account OAuth2 via AFI's OAuth Manager popup flow.
 * Auth header: `Authorization: OAuth {access_token}` (Deputy's bespoke
 * scheme — NOT `Bearer`).
 *
 * Per-install subdomain quirk: Deputy customers each have their own
 * install at https://{slug}.{region}.deputy.com/. The token-exchange
 * response includes an `endpoint` field that points to that install — we
 * persist it on the credential record as `install_url` and use it as the
 * base for every subsequent API call. Same with `intCompanyId`: we
 * discover the first active company via /resource/Company and store
 * `company_id` so the dispatcher can inject it on every employee POST
 * without re-hitting the discovery endpoint.
 *
 * @link https://developer.deputy.com/deputy-docs/docs
 */

class ADFOIN_Deputy extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'deputy';

    const authorization_endpoint = 'https://once.deputy.com/my/oauth/login';
    const token_endpoint         = 'https://once.deputy.com/my/oauth/access_token';
    const refresh_token_endpoint = 'https://once.deputy.com/my/oauth/access_token';
    const oauth_scopes           = 'longlife_refresh_token';

    /** Per-install API base URL (e.g. https://mycompany.au.deputy.com). */
    public $install_url = '';

    /** Discovered intCompanyId for the first active company on this install. */
    public $company_id = 0;

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->authorization_endpoint = self::authorization_endpoint;
        $this->token_endpoint         = self::token_endpoint;
        $this->refresh_token_endpoint = self::refresh_token_endpoint;

        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_deputy_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_deputy_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_deputy_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_deputy_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['deputy'] = array(
            'title' => __( 'Deputy', 'advanced-form-integration' ),
            'tasks' => array(
                'create_employee' => __( 'Create Employee', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['deputy'] = __( 'Deputy', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'deputy' !== $current_tab ) {
            return;
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
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to %s and open the "My Apps" tab.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://once.deputy.com/my/apps">Deputy developer portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Build New OAuth App</strong> and fill in the name and description.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into the app\'s "Redirect URI" field.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Save the app, then copy the Client ID and Client Secret into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Deputy', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'deputy',
            __( 'Deputy', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="deputy-action-template">
            <table class="form-table" v-if="action.task == 'create_employee'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Deputy Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=deputy' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/deputy', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Deputy with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens + install URL + company id,
     * and closes the popup with a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'deputy' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'deputy' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
                $found = true;
                break;
            }
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        if ( ! $found || ! $this->client_id || ! $this->client_secret ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Credential not found or incomplete.', 'advanced-form-integration' ) );
            exit;
        }

        $response = $this->request_token( $code );

        $success = false;
        $message = __( 'Unknown error.', 'advanced-form-integration' );

        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['access_token'] ) ) {
                $success = true;
                $message = __( 'Connected successfully!', 'advanced-form-integration' );
            } else {
                $message = $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' );
            }
        } elseif ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    public function ajax_get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function ajax_save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'advanced-form-integration' ) ) );
        }

        $platform    = 'deputy';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Deletion path.
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                unset( $credentials[ $index ] );
                adfoin_save_credentials( $platform, array_values( $credentials ) );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] )            ? sanitize_text_field( wp_unslash( $_POST['id'] ) )            : '';
        $title         = isset( $_POST['title'] )         ? sanitize_text_field( wp_unslash( $_POST['title'] ) )         : '';
        $client_id     = isset( $_POST['client_id'] )     ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) )     : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        // Locate any existing record so we can preserve hidden values
        // (client_secret is never re-sent from the browser on update).
        $existing = null;
        foreach ( $credentials as $cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $existing = $cred;
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
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
            'install_url'   => '',
            'company_id'    => 0,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                // Preserve tokens + install_url + company_id when nothing material changed.
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

                if ( $same ) {
                    $new_data['access_token']  = $cred['access_token']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? '';
                    $new_data['install_url']   = $cred['install_url']   ?? '';
                    $new_data['company_id']    = $cred['company_id']    ?? 0;
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

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => self::oauth_scopes,
            'state'         => self::issue_oauth_state( 'deputy', $id ),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->deputy_request( 'me', 'GET' );
        } );
    }

    /**
     * Field list for the action editor (consumed by the Vue component to
     * render editable-field rows). Snake_case keys here are mapped to
     * Deputy's Hungarian-notation API field names in the dispatcher.
     */
    public function ajax_get_fields() {
        adfoin_verify_nonce();

        $fields = array(
            array( 'key' => 'first_name',               'value' => __( 'First Name (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'last_name',                'value' => __( 'Last Name (required)', 'advanced-form-integration' ),  'required' => true ),
            array( 'key' => 'email',                    'value' => __( 'Email', 'advanced-form-integration' ) ),
            array( 'key' => 'mobile',                   'value' => __( 'Mobile', 'advanced-form-integration' ) ),
            array( 'key' => 'address1',                 'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'city',                     'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'postcode',                 'value' => __( 'Postcode', 'advanced-form-integration' ) ),
            array( 'key' => 'state',                    'value' => __( 'State', 'advanced-form-integration' ) ),
            array( 'key' => 'country',                  'value' => __( 'Country', 'advanced-form-integration' ) ),
            array( 'key' => 'employment_contract_id',   'value' => __( 'Employment Contract ID (leave blank for default)', 'advanced-form-integration' ) ),
        );

        wp_send_json_success( $fields );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/deputy' );
    }

    /**
     * Exchange the auth code for tokens. Deputy returns an `endpoint` field
     * that gives the per-install API base — we have to persist it and then
     * use it to discover the company id, both of which get saved alongside
     * the tokens in a single round-trip so the dispatcher doesn't have to
     * do JIT discovery on every send (it still falls back to JIT if either
     * is missing — see deputy_request()).
     */
    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'authorization_code',
                'code'          => $authorization_code,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => self::oauth_scopes,
            ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );

            // `endpoint` carries the per-install API base URL — e.g.
            // https://mycompany.au.deputy.com — without it we have no way
            // to address subsequent /api/v1/ calls.
            $install_url = isset( $body['endpoint'] ) ? untrailingslashit( (string) $body['endpoint'] ) : '';
            if ( $install_url ) {
                $this->install_url = $install_url;
            }

            // Discover the first active company so the dispatcher can
            // inject intCompanyId without an extra round-trip per submit.
            $company_id = $this->install_url ? $this->discover_company_id() : 0;
            if ( $company_id ) {
                $this->company_id = $company_id;
            }

            $extras = array();
            if ( $this->install_url ) {
                $extras['install_url'] = $this->install_url;
            }
            if ( $this->company_id ) {
                $extras['company_id'] = $this->company_id;
            }

            $this->persist_token_to_credential( $extras );
        } else {
            // Even on failure, persist whatever apply_token_response cleared
            // so we don't leave a half-baked record around.
            $this->save_data();
        }

        return $response;
    }

    /**
     * Deputy supports refresh via standard refresh_token grant. The
     * `endpoint` field is normally NOT re-sent on refresh, so we
     * deliberately re-persist the existing install_url + company_id rather
     * than letting them get nulled out by persist_token_to_credential's
     * empty-skip behaviour.
     */
    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( esc_url_raw( $this->refresh_token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'scope'         => self::oauth_scopes,
            ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            if ( method_exists( $this, 'mark_connection_failed' ) ) {
                $this->mark_connection_failed( 'refresh_token_revoked' );
            }
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );

            // Refresh responses sometimes re-send `endpoint` — pick it up
            // if so, otherwise keep what we already have on the record.
            if ( ! empty( $body['endpoint'] ) ) {
                $this->install_url = untrailingslashit( (string) $body['endpoint'] );
            }
        }

        $extras = array();
        if ( $this->install_url ) {
            $extras['install_url'] = $this->install_url;
        }
        if ( $this->company_id ) {
            $extras['company_id'] = $this->company_id;
        }
        $this->persist_token_to_credential( $extras );

        return $response;
    }

    /**
     * Hydrate $this from a stored credential record. Used by the dispatcher
     * and the test-connection AJAX handler.
     */
    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }

        $this->cred_id       = $credentials['id']            ?? $cred_id;
        $this->client_id     = $credentials['client_id']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? '';
        $this->token_expires = isset( $credentials['token_expires'] ) ? (int) $credentials['token_expires'] : 0;
        $this->install_url   = isset( $credentials['install_url'] ) ? untrailingslashit( (string) $credentials['install_url'] ) : '';
        $this->company_id    = isset( $credentials['company_id'] )  ? (int) $credentials['company_id'] : 0;

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'deputy' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'deputy' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    /**
     * GET /api/v1/resource/Company → pick the first active company's Id.
     * Returns 0 if discovery fails — caller should treat that as fatal
     * (the create-employee POST requires intCompanyId).
     */
    protected function discover_company_id() {
        if ( ! $this->install_url || ! $this->access_token ) {
            return 0;
        }

        $url      = $this->install_url . '/api/v1/resource/Company';
        $response = wp_remote_get( esc_url_raw( $url ), array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'OAuth ' . $this->access_token,
                'Accept'        => 'application/json',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return 0;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            return 0;
        }

        // /resource/Company returns a bare array of company objects.
        foreach ( $body as $company ) {
            if ( ! is_array( $company ) ) {
                continue;
            }
            // Prefer Active === 1 / true; fall back to first record if none
            // explicitly flagged active so we still return *something*.
            if ( ! empty( $company['Active'] ) && ! empty( $company['Id'] ) ) {
                return (int) $company['Id'];
            }
        }

        // Fallback: first record with an Id.
        foreach ( $body as $company ) {
            if ( is_array( $company ) && ! empty( $company['Id'] ) ) {
                return (int) $company['Id'];
            }
        }

        return 0;
    }

    /**
     * Build {install_url}/api/v1/{endpoint} and call it with the OAuth-
     * scheme Authorization header. Handles JIT re-discovery of install_url
     * / company_id if they're missing on the credential record, and a
     * single 401 refresh-retry via the inherited remote_request flow that
     * we open-code here (we don't use the parent's because Deputy needs
     * the bespoke `OAuth ` scheme, not `Bearer `).
     */
    public function deputy_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        // JIT re-discovery: if either piece is missing for any reason
        // (legacy connect that pre-dates this code, manual edit, etc.),
        // grab it lazily before issuing the real request.
        if ( ! $this->install_url || ! $this->company_id ) {
            $this->jit_refresh_install_metadata();
        }

        if ( ! $this->install_url ) {
            return new WP_Error( 'deputy_missing_install_url', __( 'Deputy install URL not configured. Re-authorize the account.', 'advanced-form-integration' ) );
        }

        $url    = $this->install_url . '/api/v1/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
        );

        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( 'GET' === $method && ! empty( $data ) && is_array( $data ) ) {
            $url = add_query_arg( $data, $url );
        }

        $response = $this->deputy_remote_request( $url, $args );

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        return $response;
    }

    /**
     * Inject `Authorization: OAuth <token>` and reactively refresh on 401.
     * Open-coded (vs. inheriting from the parent's remote_request) so we
     * can use Deputy's bespoke scheme prefix.
     */
    protected function deputy_remote_request( $url, $request = array() ) {
        $request            = wp_parse_args( $request, array( 'timeout' => 30 ) );
        $request['headers'] = array_merge(
            $request['headers'] ?? array(),
            array( 'Authorization' => 'OAuth ' . $this->access_token )
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        if ( 401 === (int) wp_remote_retrieve_response_code( $response )
            && ! isset( $request['_retry_after_refresh'] ) ) {
            $refresh_response = $this->refresh_token();

            if ( ! is_wp_error( $refresh_response )
                && 200 === (int) wp_remote_retrieve_response_code( $refresh_response )
                && ! empty( $this->access_token ) ) {
                $request['_retry_after_refresh']     = true;
                $request['headers']['Authorization'] = 'OAuth ' . $this->access_token;
                $response = wp_remote_request( esc_url_raw( $url ), $request );
            }
        }

        return $response;
    }

    /**
     * JIT recovery for legacy/incomplete credential records that don't yet
     * have install_url or company_id stored. Called from deputy_request()
     * before the first real call; persists whatever it discovers so this
     * only happens once per credential.
     */
    protected function jit_refresh_install_metadata() {
        // Without an access token we can't discover anything.
        if ( empty( $this->access_token ) ) {
            return;
        }

        // install_url has to come from a fresh refresh response (Deputy
        // doesn't expose it on any other endpoint). Only attempt this if
        // install_url is missing AND we have a refresh token.
        if ( ! $this->install_url && ! empty( $this->refresh_token ) ) {
            $this->refresh_token();
        }

        // With install_url in hand, we can discover the company id.
        if ( $this->install_url && ! $this->company_id ) {
            $company_id = $this->discover_company_id();
            if ( $company_id ) {
                $this->company_id = $company_id;
                $this->persist_token_to_credential( array(
                    'install_url' => $this->install_url,
                    'company_id'  => $this->company_id,
                ) );
            }
        }
    }
}

ADFOIN_Deputy::get_instance();

add_action( 'adfoin_deputy_job_queue', 'adfoin_deputy_job_queue', 10, 1 );

function adfoin_deputy_job_queue( $data ) {
    adfoin_deputy_send_data( $data['record'], $data['posted_data'] );
}

/**
 * Map of snake_case form-field keys → Deputy's Hungarian-notation API
 * column names. Used by the dispatcher to translate the flat field_data
 * payload into the shape Deputy's /supervise/employee endpoint expects.
 */
function adfoin_deputy_field_map() {
    return array(
        'first_name'             => 'strFirstName',
        'last_name'              => 'strLastName',
        'email'                  => 'strEmail',
        'mobile'                 => 'strMobile',
        'address1'               => 'strAddress1',
        'city'                   => 'strCity',
        'postcode'               => 'strPostcode',
        'state'                  => 'strState',
        'country'                => 'strCountry',
        'employment_contract_id' => 'intEmploymentContractId',
    );
}

function adfoin_deputy_send_data( $record, $posted_data ) {
    if ( 'create_employee' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    $deputy = ADFOIN_Deputy::get_instance();
    if ( ! $deputy->set_credentials( $cred_id ) ) {
        return;
    }

    $field_map = adfoin_deputy_field_map();
    $payload   = array();

    foreach ( $field_map as $form_key => $api_key ) {
        if ( ! isset( $field_data[ $form_key ] ) || '' === $field_data[ $form_key ] ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $form_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        // intEmploymentContractId is the only numeric field we expose —
        // cast so Deputy doesn't reject a string-encoded int.
        if ( 'intEmploymentContractId' === $api_key ) {
            $payload[ $api_key ] = (int) $value;
        } else {
            $payload[ $api_key ] = $value;
        }
    }

    // first_name + last_name are both required per the task definition.
    if ( empty( $payload['strFirstName'] ) || empty( $payload['strLastName'] ) ) {
        return;
    }

    // Always inject intCompanyId — the dispatcher does NOT expose it as a
    // form-mapped field. Pulled from the credential record (discovered at
    // OAuth time, JIT-discovered on first request if missing).
    $company_id = (int) ( $deputy->company_id ?: 0 );

    // Last-ditch JIT discovery in case set_credentials loaded a record
    // that pre-dates the company_id persistence (legacy connect).
    if ( ! $company_id ) {
        // deputy_request() runs jit_refresh_install_metadata() before its
        // first call, so a cheap GET here populates company_id as a side
        // effect; we then re-read it off the instance.
        $deputy->deputy_request( 'me', 'GET' );
        $company_id = (int) ( $deputy->company_id ?: 0 );
    }

    if ( ! $company_id ) {
        return;
    }

    $payload['intCompanyId'] = $company_id;

    $deputy->deputy_request( 'supervise/employee', 'POST', $payload, $record );
}
