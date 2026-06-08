<?php

class ADFOIN_MicrosoftToDo extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'mstodo';

    const service_name = 'mstodo';
    const graph_base   = 'https://graph.microsoft.com/v1.0/';

    /**
     * Microsoft tenant ("common", "consumers", "organizations" or a tenant id).
     * Stored per-credential because each registered Azure app picks its own
     * "supported account types" setting.
     */
    protected $tenant_id = 'common';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Endpoints are tenant-scoped, so we compose them on demand inside
        // request_token() / refresh_token() / get_authorize_endpoint(). Set
        // defaults here against `common` so any base-class default that
        // dereferences the endpoint still works.
        $this->authorization_endpoint = $this->build_authorize_endpoint( 'common' );
        $this->token_endpoint         = $this->build_token_endpoint( 'common' );
        $this->refresh_token_endpoint = $this->token_endpoint;

        add_action( 'rest_api_init', array( $this, 'register_callback_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_mstodo_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_mstodo_credentials', array( $this, 'save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_mstodo_lists', array( $this, 'ajax_get_lists' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_mstodo_connection', array( $this, 'test_connection' ), 10, 0 );
    }

    /* ---------- Provider registrations ---------- */

    public function register_actions( $actions ) {
        $actions['mstodo'] = array(
            'title' => __( 'Microsoft To Do', 'advanced-form-integration' ),
            'tasks' => array(
                'create_task' => __( 'Create Task', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $tabs ) {
        $tabs['mstodo'] = __( 'Microsoft To Do', 'advanced-form-integration' );
        return $tabs;
    }

    /* ---------- Settings view (OAuth Manager UI) ---------- */

    public function render_settings( $current_tab ) {
        if ( 'mstodo' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'tenant_id',
                'label'         => __( 'Tenant', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => false,
                'show_in_table' => true,
                'placeholder'   => 'common',
                'description'   => __( 'Use <code>common</code> for any MS account, <code>consumers</code> for personal accounts only, <code>organizations</code> for work/school only, or paste a specific Directory (tenant) ID.', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'client_id',
                'label'         => __( 'Application (Client) ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Client Secret Value', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s → App registrations → New registration.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade">portal.azure.com</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Name the app (e.g. "AFI Microsoft To Do"). For <em>Supported account types</em> pick the one that matches your Tenant value above — most personal-account flows want <strong>Accounts in any organizational directory and personal Microsoft accounts</strong> (tenant = <code>common</code>).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'For <em>Redirect URI</em> pick <strong>Web</strong> and paste:', 'advanced-form-integration' ) . '<br><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'After registration, copy the <strong>Application (client) ID</strong> from the Overview blade.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Open <em>Certificates &amp; secrets</em> → New client secret → copy the <strong>Value</strong> column (shown once).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Open <em>API permissions</em> → Add a permission → Microsoft Graph → <strong>Delegated permissions</strong> → check <code>Tasks.ReadWrite</code> and <code>offline_access</code> → Add. If your account type requires admin consent, click <strong>Grant admin consent</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Add Account</strong> above, paste the values, and finish the popup sign-in.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Microsoft To Do', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'mstodo', __( 'Microsoft To Do', 'advanced-form-integration' ), $fields, $instructions, $config );
    }

    /* ---------- Credential CRUD ---------- */

    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform    = 'mstodo';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Deletion.
        if ( isset( $_POST['delete_index'] ) ) {
            $index = (int) wp_unslash( $_POST['delete_index'] );
            if ( isset( $credentials[ $index ] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $tenant_id     = isset( $_POST['tenant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['tenant_id'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        if ( '' === $tenant_id ) {
            $tenant_id = 'common';
        }

        if ( '' === $id ) {
            $id = wp_generate_uuid4();
        }

        // Preserve the existing client_secret when the user submits the
        // edit form with the field blank (it's never echoed back to the
        // browser, so a blank submit means "keep current").
        $existing = null;
        foreach ( $credentials as $check ) {
            if ( isset( $check['id'] ) && (string) $check['id'] === (string) $id ) {
                $existing = $check;
                break;
            }
        }

        if ( $existing ) {
            if ( '' === $client_secret ) {
                $client_secret = isset( $existing['client_secret'] ) ? $existing['client_secret'] : '';
            }
            if ( '' === $client_id ) {
                $client_id = isset( $existing['client_id'] ) ? $existing['client_id'] : '';
            }
        }

        if ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array(
                'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ),
            ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'tenant_id'     => $tenant_id,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'access_token'  => '',
            'refresh_token' => '',
            'token_expires' => 0,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $id ) {
                // If only the title was edited (client_id/secret/tenant are
                // unchanged), preserve any existing token state so the user
                // doesn't have to re-authorize.
                $unchanged = (
                    isset( $cred['client_id'], $cred['client_secret'], $cred['tenant_id'] )
                    && $cred['client_id'] === $client_id
                    && $cred['client_secret'] === $client_secret
                    && $cred['tenant_id'] === $tenant_id
                );
                if ( $unchanged ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['token_expires'] = isset( $cred['token_expires'] ) ? (int) $cred['token_expires'] : 0;
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

        // Build the authorize URL the OAuth Manager will pop open.
        // NOTE: add_query_arg() URL-encodes values for us — do NOT pre-encode
        // them or Microsoft will receive a double-encoded redirect_uri and
        // bounce back with `invalid_request`, which closes the popup silently.
        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'response_mode' => 'query',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $this->oauth_scope(),
                'prompt'        => 'select_account',
                'state'         => self::issue_oauth_state( 'mstodo', $id ),
            ),
            $this->build_authorize_endpoint( $tenant_id )
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    /* ---------- OAuth callback ---------- */

    public function register_callback_route() {
        register_rest_route(
            'advancedformintegration',
            '/mstodo',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_oauth_callback' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_oauth_callback( $request ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';

        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? trim( (string) $params['code'] ) : '';
        $error  = isset( $params['error'] ) ? trim( (string) $params['error'] ) : '';
        $desc   = isset( $params['error_description'] ) ? trim( (string) $params['error_description'] ) : '';
        $state  = isset( $params['state'] ) ? trim( (string) $params['state'] ) : '';

        if ( $error ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, $desc ? $desc : $error );
            exit;
        }

        $context = self::consume_oauth_state( $state, 'mstodo' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Missing authorization code or expired state.', 'advanced-form-integration' ) );
            exit;
        }

        if ( ! $this->load_credentials( $cred_id ) ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Credential not found for this OAuth callback.', 'advanced-form-integration' ) );
            exit;
        }

        $response = $this->request_token( $code );
        $body     = is_array( $response ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;
        $status   = is_array( $response ) ? (int) wp_remote_retrieve_response_code( $response ) : 0;

        if ( 200 === $status && ! empty( $body['access_token'] ) ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( true, __( 'Microsoft To Do connected.', 'advanced-form-integration' ) );
            exit;
        }

        $message = __( 'Token exchange failed.', 'advanced-form-integration' );
        if ( is_array( $body ) ) {
            if ( ! empty( $body['error_description'] ) ) {
                $message = (string) $body['error_description'];
            } elseif ( ! empty( $body['error'] ) ) {
                $message = is_string( $body['error'] ) ? $body['error'] : wp_json_encode( $body['error'] );
            }
        } elseif ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( false, $message );
        exit;
    }

    /* ---------- OAuth helpers (override) ---------- */

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/mstodo' );
    }

    protected function oauth_scope() {
        // offline_access guarantees a refresh_token; Tasks.ReadWrite is the
        // delegated scope for Microsoft To Do (todo, taskLists, tasks).
        // Never combine with `.default` — Microsoft rejects mixed scopes.
        return 'offline_access Tasks.ReadWrite';
    }

    protected function build_authorize_endpoint( $tenant_id ) {
        $tenant = $tenant_id ? rawurlencode( $tenant_id ) : 'common';
        return 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/authorize';
    }

    protected function build_token_endpoint( $tenant_id ) {
        $tenant = $tenant_id ? rawurlencode( $tenant_id ) : 'common';
        return 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token';
    }

    /**
     * Load credentials by id, including the Microsoft-specific tenant_id.
     */
    protected function load_credentials( $cred_id ) {
        if ( empty( $cred_id ) ) {
            return false;
        }

        $credentials = adfoin_read_credentials( 'mstodo' );
        if ( ! is_array( $credentials ) ) {
            return false;
        }

        foreach ( $credentials as $cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $cred_id ) {
                $this->cred_id       = $cred['id'];
                $this->tenant_id     = isset( $cred['tenant_id'] ) ? $cred['tenant_id'] : 'common';
                $this->client_id     = isset( $cred['client_id'] ) ? $cred['client_id'] : '';
                $this->client_secret = isset( $cred['client_secret'] ) ? $cred['client_secret'] : '';
                $this->access_token  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                $this->refresh_token = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                $this->token_expires = isset( $cred['token_expires'] ) ? (int) $cred['token_expires'] : 0;

                // Refresh the cached endpoints now that tenant_id is known.
                $this->token_endpoint         = $this->build_token_endpoint( $this->tenant_id );
                $this->refresh_token_endpoint = $this->token_endpoint;
                $this->authorization_endpoint = $this->build_authorize_endpoint( $this->tenant_id );

                return true;
            }
        }

        return false;
    }

    protected function save_data() {
        // Persist canonical token fields + tenant_id back into the credential.
        $this->persist_token_to_credential( array( 'tenant_id' => $this->tenant_id ) );
    }

    /**
     * MS Graph token endpoint expects everything in the form body.
     * Basic-auth header is NOT supported for v2.0 tokens.
     */
    protected function request_token( $authorization_code ) {
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'code'          => $authorization_code,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
                'scope'         => $this->oauth_scope(),
            ),
        );

        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->apply_token_response( $body );
        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return new WP_Error( 'mstodo_no_refresh_token', __( 'No refresh token stored — reconnect the Microsoft To Do account.', 'advanced-form-integration' ) );
        }

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
                'scope'         => $this->oauth_scope(),
            ),
        );

        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), $args );
        $status   = (int) wp_remote_retrieve_response_code( $response );
        $body     = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $status && is_array( $body ) ) {
            $this->apply_token_response( $body );
        } elseif ( 400 === $status || 401 === $status ) {
            // Refresh tokens that have been revoked or invalidated return 400
            // with error="invalid_grant". Surface as a connection failure so
            // the OAuth Manager UI can flag the account.
            $reason = is_array( $body ) && isset( $body['error'] ) ? (string) $body['error'] : 'refresh_failed';
            $this->mark_connection_failed( $reason );
        }

        $this->save_data();

        return $response;
    }

    /* ---------- AJAX: lists + test ---------- */

    public function ajax_get_lists() {
        adfoin_require_manage_options();
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( empty( $cred_id ) ) {
            wp_send_json_error( array( 'message' => __( 'No account selected.', 'advanced-form-integration' ) ) );
        }

        if ( ! $this->load_credentials( $cred_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Credential not found.', 'advanced-form-integration' ) ) );
        }

        if ( empty( $this->access_token ) && empty( $this->refresh_token ) ) {
            wp_send_json_error( array( 'message' => __( 'This account is not connected. Re-authorize it from the settings tab.', 'advanced-form-integration' ) ) );
        }

        $response = $this->graph_request( 'me/todo/lists', 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $status   = (int) wp_remote_retrieve_response_code( $response );
        $raw_body = wp_remote_retrieve_body( $response );
        $body     = json_decode( $raw_body, true );

        if ( 200 !== $status ) {
            // Surface as much of Microsoft's actual error as possible so the
            // user can act on it.
            $parts = array( sprintf( 'Microsoft Graph returned HTTP %d.', $status ) );

            if ( is_array( $body ) && ! empty( $body['error'] ) ) {
                if ( ! empty( $body['error']['code'] ) ) {
                    $parts[] = '[' . $body['error']['code'] . ']';
                }
                if ( ! empty( $body['error']['message'] ) ) {
                    $parts[] = $body['error']['message'];
                }
            } elseif ( '' !== trim( (string) $raw_body ) ) {
                $parts[] = mb_substr( trim( (string) $raw_body ), 0, 300 );
            }

            // 401 [UnknownError] is Graph's catch-all when the token shape
            // looks valid but is being rejected by the backing service. The
            // real reason is almost always in the WWW-Authenticate header
            // (Microsoft's `Bearer error_description=...` field) or in the
            // token's `scp` claim. Decode both and append to the message —
            // these two pieces almost always make the actual cause obvious.
            if ( 401 === $status || 403 === $status ) {
                $www_auth = wp_remote_retrieve_header( $response, 'www-authenticate' );
                if ( $www_auth ) {
                    if ( preg_match( '/error_description="([^"]+)"/i', (string) $www_auth, $m ) ) {
                        $parts[] = 'WWW-Authenticate: ' . $m[1];
                    } else {
                        $parts[] = 'WWW-Authenticate: ' . mb_substr( (string) $www_auth, 0, 300 );
                    }
                }

                // Decode the access token JWT payload to show what scopes are
                // actually present. JWTs are three base64url segments joined
                // by dots — middle segment is the JSON payload.
                if ( ! empty( $this->access_token ) ) {
                    $segments = explode( '.', $this->access_token );
                    if ( 3 === count( $segments ) ) {
                        $payload_b64 = strtr( $segments[1], '-_', '+/' );
                        $payload_b64 .= str_repeat( '=', ( 4 - strlen( $payload_b64 ) % 4 ) % 4 );
                        $payload = json_decode( base64_decode( $payload_b64 ), true );
                        if ( is_array( $payload ) ) {
                            $scp = $payload['scp']  ?? '(scp claim missing)';
                            $tid = $payload['tid']  ?? '(tid claim missing)';
                            $aud = $payload['aud']  ?? '(aud claim missing)';
                            $exp = isset( $payload['exp'] ) ? (int) $payload['exp'] : 0;
                            $expired = $exp && $exp < time();
                            $parts[] = sprintf(
                                'Token claims — scopes=[%s] tenant=%s audience=%s%s.',
                                $scp,
                                $tid,
                                $aud,
                                $expired ? ' EXPIRED' : ''
                            );
                        }
                    }
                }

                $parts[] = __( 'If Tasks.ReadWrite is not in the scopes list, the access token was issued before you added the permission — delete this account from the settings tab and re-add it to mint a fresh token.', 'advanced-form-integration' );
            }

            wp_send_json_error( array( 'message' => implode( ' ', $parts ) ) );
        }

        $lists = array();
        if ( is_array( $body ) && ! empty( $body['value'] ) ) {
            foreach ( $body['value'] as $list ) {
                if ( isset( $list['id'], $list['displayName'] ) ) {
                    $lists[ $list['id'] ] = $list['displayName'];
                }
            }
        }

        wp_send_json_success( $lists );
    }

    public function test_connection() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $cred_id = isset( $_POST['cred_id'] ) ? sanitize_text_field( wp_unslash( $_POST['cred_id'] ) )
            : ( isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '' );

        if ( ! $this->load_credentials( $cred_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Credential not found.', 'advanced-form-integration' ) ) );
        }

        if ( empty( $this->refresh_token ) ) {
            wp_send_json_error( array( 'message' => __( 'Account not authorized yet.', 'advanced-form-integration' ) ) );
        }

        $response = $this->graph_request( 'me/todo/lists?$top=1', 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }
        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $msg  = is_array( $body ) && isset( $body['error']['message'] )
                ? $body['error']['message']
                : __( 'Graph returned HTTP ', 'advanced-form-integration' ) . $status;
            wp_send_json_error( array( 'message' => $msg ) );
        }

        wp_send_json_success( array( 'message' => __( 'Connected to Microsoft To Do.', 'advanced-form-integration' ) ) );
    }

    /* ---------- Graph wrapper ---------- */

    protected function graph_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        if ( empty( $this->access_token ) && empty( $this->refresh_token ) ) {
            return new WP_Error( 'mstodo_not_authorized', __( 'Microsoft To Do account is not authorized.', 'advanced-form-integration' ) );
        }

        $url     = self::graph_base . ltrim( $endpoint, '/' );
        $version = defined( 'ADVANCED_FORM_INTEGRATION_VERSION' ) ? ADVANCED_FORM_INTEGRATION_VERSION : 'dev';

        $request = array(
            'method'      => strtoupper( $method ),
            'timeout'     => 30,
            'sslverify'   => true,
            'redirection' => 0,
            'user-agent'  => 'AdvancedFormIntegration/' . $version . '; +' . home_url(),
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
        );

        if ( in_array( $request['method'], array( 'POST', 'PATCH', 'PUT' ), true ) && ! empty( $data ) ) {
            $request['body'] = wp_json_encode( $data );
        }

        // Use the base-class remote_request — it adds the Bearer header,
        // pre-emptively refreshes on token expiry, and retries once on a 401.
        $response = $this->remote_request( $url, $request );

        if ( $record && function_exists( 'adfoin_add_to_log' ) ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }

    /**
     * Create a To Do task and (optionally) attach a linkedResource.
     */
    public function create_task( $list_id, $payload, $record, $cred_id, $linked_resource = array() ) {
        if ( ! $this->load_credentials( $cred_id ) ) {
            return new WP_Error( 'mstodo_cred_not_found', __( 'Microsoft To Do credential not found.', 'advanced-form-integration' ) );
        }

        $endpoint = 'me/todo/lists/' . rawurlencode( $list_id ) . '/tasks';
        $response = $this->graph_request( $endpoint, 'POST', $payload, $record );

        // If a linkedResource was requested, attach it after the task is
        // created (Graph only allows linkedResource on the dedicated
        // sub-resource, not in the task POST body).
        if ( ! empty( $linked_resource ) && ! is_wp_error( $response ) ) {
            $status = (int) wp_remote_retrieve_response_code( $response );
            if ( $status >= 200 && $status < 300 ) {
                $body    = json_decode( wp_remote_retrieve_body( $response ), true );
                $task_id = is_array( $body ) && isset( $body['id'] ) ? (string) $body['id'] : '';
                if ( '' !== $task_id ) {
                    $this->graph_request(
                        'me/todo/lists/' . rawurlencode( $list_id ) . '/tasks/' . rawurlencode( $task_id ) . '/linkedResources',
                        'POST',
                        $linked_resource,
                        $record
                    );
                }
            }
        }

        return $response;
    }

    /* ---------- Action UI template ---------- */

    public function action_fields() {
        ?>
        <script type="text/template" id="mstodo-action-template">
            <table class="form-table" v-if="action.task == 'create_task'">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Microsoft To Do Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                            <option value=""><?php esc_html_e( 'Select account…', 'advanced-form-integration' ); ?></option>
                            <?php foreach ( adfoin_read_credentials( 'mstodo' ) as $cred ) :
                                $cid    = isset( $cred['id'] ) ? $cred['id'] : '';
                                $ctitle = isset( $cred['title'] ) ? $cred['title'] : __( 'Untitled', 'advanced-form-integration' );
                                ?>
                                <option value="<?php echo esc_attr( $cid ); ?>"><?php echo esc_html( $ctitle ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br/>
                        <a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mstodo' ) ); ?>"><?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    </td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'To Do List', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                            <option v-for="(label, id) in fielddata.lists" :key="id" :value="id">{{ label }}</option>
                        </select>
                        <button type="button" class="afi-icon-btn" v-bind:class="{'is-loading': listsLoading}" v-bind:disabled="listsLoading" @click="getLists(true)" title="<?php esc_attr_e( 'Refresh lists', 'advanced-form-integration' ); ?>" aria-label="<?php esc_attr_e( 'Refresh lists', 'advanced-form-integration' ); ?>">
                            <svg class="afi-refresh-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                        </button>
                        <p v-if="listError" class="adfoin-error" style="color:#b32d2e;">{{ listError }}</p>
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
}

$mstodo_instance = ADFOIN_MicrosoftToDo::get_instance();

/* ---------- Submission dispatcher ---------- */

add_action( 'adfoin_mstodo_job_queue', 'adfoin_mstodo_job_queue', 10, 1 );

function adfoin_mstodo_job_queue( $data ) {
    adfoin_mstodo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mstodo_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( function_exists( 'adfoin_check_conditional_logic' ) ) {
        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'create_task' !== $task ) {
        return;
    }

    $cred_id = isset( $field_data['credId'] ) ? (string) $field_data['credId'] : '';
    $list_id = isset( $field_data['listId'] ) ? (string) $field_data['listId'] : '';

    if ( '' === $cred_id || '' === $list_id ) {
        return;
    }

    $get = function ( $key ) use ( $field_data, $posted_data ) {
        if ( empty( $field_data[ $key ] ) ) {
            return '';
        }
        return trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
    };

    $title = $get( 'title' );

    if ( '' === $title ) {
        return;
    }

    $body_content      = $get( 'bodyContent' );
    $body_content_type = strtolower( $get( 'bodyContentType' ) );
    $due_datetime      = $get( 'dueDateTime' );
    $due_timezone      = $get( 'dueTimeZone' );
    $reminder_dt       = $get( 'reminderDateTime' );
    $reminder_timezone = $get( 'reminderTimeZone' );
    $importance        = strtolower( $get( 'importance' ) );
    $status            = strtolower( $get( 'status' ) );
    $categories_csv    = $get( 'categories' );
    $is_reminder_on    = strtolower( $get( 'isReminderOn' ) );

    $payload = array( 'title' => $title );

    if ( '' !== $body_content ) {
        if ( ! in_array( $body_content_type, array( 'text', 'html' ), true ) ) {
            $body_content_type = 'text';
        }
        $payload['body'] = array(
            'contentType' => $body_content_type,
            'content'     => $body_content,
        );
    }

    if ( '' !== $due_datetime ) {
        $iso = adfoin_mstodo_to_iso( $due_datetime );
        if ( '' !== $iso ) {
            $payload['dueDateTime'] = array(
                'dateTime' => $iso,
                'timeZone' => '' !== $due_timezone ? $due_timezone : 'UTC',
            );
        }
    }

    if ( '' !== $reminder_dt ) {
        $iso = adfoin_mstodo_to_iso( $reminder_dt );
        if ( '' !== $iso ) {
            $payload['reminderDateTime'] = array(
                'dateTime' => $iso,
                'timeZone' => '' !== $reminder_timezone ? $reminder_timezone : 'UTC',
            );
            $payload['isReminderOn']     = true;
        }
    }

    if ( in_array( $is_reminder_on, array( '1', 'true', 'yes', 'on' ), true ) ) {
        $payload['isReminderOn'] = true;
    } elseif ( in_array( $is_reminder_on, array( '0', 'false', 'no', 'off' ), true ) ) {
        $payload['isReminderOn'] = false;
    }

    if ( in_array( $importance, array( 'low', 'normal', 'high' ), true ) ) {
        $payload['importance'] = $importance;
    }

    if ( in_array( $status, array( 'notstarted', 'inprogress', 'completed', 'waitingonothers', 'deferred' ), true ) ) {
        $map = array(
            'notstarted'      => 'notStarted',
            'inprogress'      => 'inProgress',
            'completed'       => 'completed',
            'waitingonothers' => 'waitingOnOthers',
            'deferred'        => 'deferred',
        );
        $payload['status'] = $map[ $status ];
    }

    if ( '' !== $categories_csv ) {
        $categories = array_filter( array_map( 'trim', explode( ',', $categories_csv ) ) );
        if ( ! empty( $categories ) ) {
            $payload['categories'] = array_values( $categories );
        }
    }

    /**
     * Filter the Microsoft To Do task payload before sending. Useful for
     * adding `recurrence`, `startDateTime`, `checklistItems`, etc.
     *
     * @param array $payload     Final task body sent to Graph.
     * @param array $field_data  Raw field_data map.
     * @param array $posted_data Form submission values.
     * @param array $record      Integration record.
     */
    $payload = apply_filters( 'adfoin_mstodo_task_payload', $payload, $field_data, $posted_data, $record );

    // Optional linkedResource (e.g. a URL or related form link).
    $linked_url   = $get( 'linkedResourceUrl' );
    $linked_name  = $get( 'linkedResourceDisplayName' );
    $linked_app   = $get( 'linkedResourceApplicationName' );
    $linked       = array();
    if ( '' !== $linked_url ) {
        $linked = array(
            'webUrl'          => $linked_url,
            'applicationName' => '' !== $linked_app ? $linked_app : 'Advanced Form Integration',
        );
        if ( '' !== $linked_name ) {
            $linked['displayName'] = $linked_name;
        }
    }

    ADFOIN_MicrosoftToDo::get_instance()->create_task( $list_id, $payload, $record, $cred_id, $linked );
}

/**
 * Coerce a date string into the ISO 8601 format Microsoft Graph wants
 * (YYYY-MM-DDTHH:MM:SS — no timezone offset, the timezone goes in the
 * accompanying `timeZone` field). Returns '' on failure so callers can
 * skip emitting the dateTimeTimeZone object entirely.
 */
if ( ! function_exists( 'adfoin_mstodo_to_iso' ) ) :
function adfoin_mstodo_to_iso( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    // Already in the right shape?
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?(\.\d+)?$/', $value ) ) {
        // Pad to seconds if missing.
        if ( ! preg_match( '/:\d{2}(\.\d+)?$/', $value ) ) {
            $value .= ':00';
        }
        return $value;
    }

    $timestamp = strtotime( $value );
    if ( false === $timestamp ) {
        return '';
    }
    return gmdate( 'Y-m-d\TH:i:s', $timestamp );
}
endif;
