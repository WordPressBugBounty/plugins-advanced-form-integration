<?php

/**
 * FreshBooks — Create Client via POST /accounting/account/{account_id}/users/clients.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Authorization: Bearer {access_token}; tokens refreshed automatically.
 *
 * FreshBooks scopes a user's data under an alpha-numeric account_id discovered
 * from GET /auth/api/v1/users/me after the OAuth handshake. We cache that id
 * on the credential record so subsequent calls don't have to re-discover it.
 *
 * @link https://www.freshbooks.com/api/start
 */

class ADFOIN_FreshBooks extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'freshbooks';

    const authorization_endpoint = 'https://auth.freshbooks.com/oauth/authorize';
    const token_endpoint         = 'https://api.freshbooks.com/auth/oauth/token';
    const refresh_token_endpoint = 'https://api.freshbooks.com/auth/oauth/token';
    const api_base               = 'https://api.freshbooks.com/';
    const oauth_scopes           = 'user:profile:read user:clients:read user:clients:write';

    /** @var string FreshBooks account_id (alpha-numeric) — cached on the credential record. */
    public $account_id = '';

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

        add_action( 'wp_ajax_adfoin_get_freshbooks_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_freshbooks_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_freshbooks_connection', array( $this, 'ajax_test_connection' ), 10, 0 );

        add_action( 'wp_ajax_adfoin_get_freshbooks_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['freshbooks'] = array(
            'title' => __( 'FreshBooks', 'advanced-form-integration' ),
            'tasks' => array(
                'create_client' => __( 'Create Client', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['freshbooks'] = __( 'FreshBooks', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'freshbooks' !== $current_tab ) {
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
        $instructions .= '<li>' . sprintf( __( 'Sign in to FreshBooks and go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://my.freshbooks.com/#/developer">Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Create an App</strong> and give it a name (e.g. WordPress).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Under <strong>Redirect URIs</strong>, paste the URL below:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Set the scopes to <code>user:profile:read user:clients:read user:clients:write</code>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Save the app, copy the <strong>Client ID</strong> and <strong>Client Secret</strong> into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect FreshBooks', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'freshbooks',
            __( 'FreshBooks', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="freshbooks-action-template">
            <table class="form-table" v-if="action.task == 'create_client'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'FreshBooks Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=freshbooks' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

    public function ajax_get_fields() {
        adfoin_verify_nonce();

        wp_send_json_success( adfoin_freshbooks_fields() );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/freshbooks', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by FreshBooks with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, discovers the account_id, and
     * closes the popup with a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'freshbooks' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'freshbooks' ) as $entry ) {
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
                // Token exchange succeeded — now discover account_id.
                $account_id = $this->discover_account_id();
                if ( $account_id ) {
                    $this->account_id = $account_id;
                    $this->persist_token_to_credential( array( 'account_id' => $account_id ) );
                    $success = true;
                    $message = __( 'Connected successfully!', 'advanced-form-integration' );
                } else {
                    $message = __( 'Connected, but could not discover FreshBooks account_id. Please reconnect.', 'advanced-form-integration' );
                }
            } else {
                $message = $body['error_description'] ?? ( $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' ) );
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

        $platform    = 'freshbooks';
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
            'account_id'    => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                // Preserve tokens + account_id when nothing material changed.
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

                if ( $same ) {
                    $new_data['access_token']  = $cred['access_token']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? '';
                    $new_data['account_id']    = $cred['account_id']    ?? '';
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
            'state'         => self::issue_oauth_state( 'freshbooks', $id ),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            // /auth/api/v1/users/me is independent of account_id, so it works
            // as a connectivity probe even before account_id is cached.
            return wp_remote_request( esc_url_raw( self::api_base . 'auth/api/v1/users/me' ), array(
                'timeout' => 30,
                'method'  => 'GET',
                'headers' => array(
                    'Authorization' => $this->get_http_authorization_header( 'bearer' ),
                    'Content-Type'  => 'application/json',
                    'Api-Version'   => 'alpha',
                    'Accept'        => 'application/json',
                ),
            ) );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/freshbooks' );
    }

    /**
     * FreshBooks' token endpoint expects a JSON body (Content-Type:
     * application/json), not the standard form-urlencoded body that the
     * base `Advanced_Form_Integration_OAuth2::request_token` would send.
     * That's why we override here instead of inheriting.
     */
    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'code'          => $authorization_code,
                'redirect_uri'  => $this->get_redirect_uri(),
            ) ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
        }

        $this->save_data();

        return $response;
    }

    /**
     * Refresh access token. FreshBooks again wants JSON.
     */
    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( esc_url_raw( $this->refresh_token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $this->refresh_token,
                'redirect_uri'  => $this->get_redirect_uri(),
            ) ),
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
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            // Always include account_id in the persisted extras so token
            // refreshes don't accidentally wipe it.
            $extras = array();
            if ( ! empty( $this->account_id ) ) {
                $extras['account_id'] = $this->account_id;
            }
            $this->persist_token_to_credential( $extras );
        }
    }

    /**
     * Discover the user's primary FreshBooks account_id by calling
     * GET /auth/api/v1/users/me with the just-issued access token.
     * Returns the alpha-numeric account_id or '' on failure.
     */
    public function discover_account_id() {
        if ( empty( $this->access_token ) ) {
            return '';
        }

        $response = wp_remote_get( esc_url_raw( self::api_base . 'auth/api/v1/users/me' ), array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'Api-Version'   => 'alpha',
                'Accept'        => 'application/json',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // Response shape: { response: { business_memberships: [ { business: { account_id: '...' } } ] } }
        $memberships = $body['response']['business_memberships'] ?? array();
        if ( is_array( $memberships ) && ! empty( $memberships ) ) {
            $first = $memberships[0];
            $account_id = $first['business']['account_id'] ?? '';
            if ( $account_id ) {
                return (string) $account_id;
            }
        }

        return '';
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }

        $this->cred_id       = $credentials['id'] ?? $cred_id;
        $this->client_id     = $credentials['client_id']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? '';
        $this->token_expires = isset( $credentials['token_expires'] ) ? (int) $credentials['token_expires'] : 0;
        $this->account_id    = $credentials['account_id']    ?? '';

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'freshbooks' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    /**
     * Issue a request against the FreshBooks accounting API.
     *
     * `$endpoint` should be relative to the account context (e.g.
     * `users/clients`). We prepend `accounting/account/{account_id}/`
     * automatically. If account_id isn't cached yet, attempt one
     * just-in-time discovery before failing.
     */
    public function freshbooks_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        if ( empty( $this->account_id ) ) {
            $account_id = $this->discover_account_id();
            if ( $account_id ) {
                $this->account_id = $account_id;
                $this->persist_token_to_credential( array( 'account_id' => $account_id ) );
            } else {
                return new WP_Error( 'freshbooks_missing_account_id', __( 'FreshBooks account_id could not be discovered. Please reconnect this account.', 'advanced-form-integration' ) );
            }
        }

        $url    = self::api_base . 'accounting/account/' . rawurlencode( $this->account_id ) . '/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'Api-Version'  => 'alpha',
            ),
        );

        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( 'GET' === $method && ! empty( $data ) && is_array( $data ) ) {
            $url = add_query_arg( $data, $url );
        }

        return $this->remote_request( $url, $args, $record );
    }

    /**
     * Inject Authorization: Bearer and refresh once on 401.
     */
    protected function remote_request( $url, $request = array(), $record = array() ) {
        static $refreshed = false;

        $request            = wp_parse_args( $request, array( 'timeout' => 30 ) );
        $request['headers'] = array_merge(
            $request['headers'] ?? array(),
            array( 'Authorization' => $this->get_http_authorization_header( 'bearer' ) )
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        if ( 401 === (int) wp_remote_retrieve_response_code( $response ) && ! $refreshed ) {
            $this->refresh_token();
            $refreshed = true;

            $request['headers']['Authorization'] = $this->get_http_authorization_header( 'bearer' );
            $response = wp_remote_request( esc_url_raw( $url ), $request );
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }
}

ADFOIN_FreshBooks::get_instance();

function adfoin_freshbooks_fields() {
    return array(
        array( 'key' => 'fname',         'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lname',         'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',         'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'organization',  'value' => __( 'Organization', 'advanced-form-integration' ) ),
        array( 'key' => 'home_phone',    'value' => __( 'Home Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'mob_phone',     'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'p_street',      'value' => __( 'Street', 'advanced-form-integration' ) ),
        array( 'key' => 'p_city',        'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'p_province',    'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'p_code',        'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'p_country',     'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'currency_code', 'value' => __( 'Currency Code (e.g. USD)', 'advanced-form-integration' ) ),
        array( 'key' => 'language',      'value' => __( 'Language (e.g. en)', 'advanced-form-integration' ) ),
        array( 'key' => 'notes',         'value' => __( 'Notes', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_freshbooks_job_queue', 'adfoin_freshbooks_job_queue', 10, 1 );

function adfoin_freshbooks_job_queue( $data ) {
    adfoin_freshbooks_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_freshbooks_send_data( $record, $posted_data ) {
    if ( 'create_client' !== ( $record['task'] ?? '' ) ) {
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

    $reserved = array( 'credId' => 1 );
    $client   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' === $parsed || null === $parsed ) {
            continue;
        }
        $client[ $key ] = $parsed;
    }

    // FreshBooks requires at minimum a name handle on the client — either
    // organization or fname/lname. Bail silently if nothing useful was mapped.
    if ( empty( $client['email'] ) && empty( $client['fname'] ) && empty( $client['lname'] ) && empty( $client['organization'] ) ) {
        return;
    }

    $freshbooks = ADFOIN_FreshBooks::get_instance();
    if ( ! $freshbooks->set_credentials( $cred_id ) ) {
        return;
    }

    $freshbooks->freshbooks_request( 'users/clients', 'POST', array( 'client' => $client ), $record );
}
