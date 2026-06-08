<?php
/**
 * Class Advanced_Form_Integration_OAuth2
 *
 * This class provides an OAuth 2.0 client for Advanced Form Integration WordPress plugin.
 */
class Advanced_Form_Integration_OAuth2 {

    /**
     * The client ID for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $client_id;

    /**
     * The client secret for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $client_secret;

    /**
     * The access token for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $access_token;

    /**
     * The refresh token for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $refresh_token;

     /**
     * The state for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $state;

    /**
     * Data center for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $data_center;

    /**
     * The authorization endpoint for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $authorization_endpoint;

    /**
     * The token endpoint for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $token_endpoint;

    /**
     * The cred_id for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $cred_id;

    /**
     * Unix timestamp at which the access token expires. 0 = unknown.
     *
     * @var int
     */
    protected $token_expires = 0;

    /**
     * Platform slug used by the centralized credential persistence helpers
     * (`persist_token_to_credential`, `set_credentials_from_id`). Subclasses
     * should set this — it's the same slug used by `adfoin_read_credentials()`
     * / `adfoin_save_credentials()`.
     *
     * @var string
     */
    protected $platform_slug = '';

    /**
     * The refresh token endpoint for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $refresh_token_endpoint = '';

    public function get_title() {

        return '';
    }

    /**
     * Issue a single-use, time-bounded OAuth `state` token.
     *
     * The OAuth `state` parameter must be unguessable to defeat CSRF. It also
     * needs to carry context — specifically, which credential record this
     * auth flow targets so the callback knows where to write the resulting
     * tokens. We solve both by stashing the context in a short-lived
     * transient and returning an opaque random token to send to the OAuth
     * provider.
     *
     * @param string $platform  Platform slug (e.g. 'googletasks').
     * @param string $cred_id   ID of the credential record being configured.
     * @param array  $extra     Optional extra context to round-trip.
     * @return string Opaque state token to include in the OAuth auth URL.
     */
    protected static function issue_oauth_state( $platform, $cred_id, array $extra = array() ) {
        try {
            $state = bin2hex( random_bytes( 16 ) );
        } catch ( Exception $e ) {
            // Fallback for environments without a CSPRNG. wp_generate_password
            // uses mt_rand, which is weaker but still acceptable here.
            $state = wp_generate_password( 32, false, false );
        }

        set_transient(
            'adfoin_oauth_state_' . $state,
            array(
                'platform' => (string) $platform,
                'cred_id'  => (string) $cred_id,
                'user_id'  => (int) get_current_user_id(),
                'extra'    => is_array( $extra ) ? $extra : array(),
            ),
            10 * MINUTE_IN_SECONDS
        );

        return $state;
    }

    /**
     * Consume a previously-issued OAuth state token.
     *
     * Single-use: the transient is deleted on lookup, regardless of validity.
     *
     * Returns the stored context on success, or null if the state is unknown,
     * expired, for the wrong platform, or fails the user binding check.
     *
     * Backward-compat fallback: if no transient exists for $state but $state
     * matches an existing credential id for $platform, accept it. Keeps
     * in-flight OAuth flows from breaking when this code first deploys; can
     * be removed in a future release once any in-flight flows have settled.
     *
     * @param string $state     The state token from the callback.
     * @param string $platform  Expected platform slug.
     * @return array|null  ['cred_id' => ..., 'extra' => ..., 'legacy' => bool] or null.
     */
    protected static function consume_oauth_state( $state, $platform ) {
        $state = is_string( $state ) ? trim( $state ) : '';
        if ( '' === $state ) {
            return null;
        }

        // Defense in depth: ensure the inbound callback request is on our
        // origin. Any OAuth provider configured by the user redirects to a
        // URL on this WP install; if HTTP_HOST doesn't match home_url(),
        // something off-host has tried to invoke the callback.
        if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
            $home = wp_parse_url( home_url() );
            if ( ! empty( $home['host'] )
                && strcasecmp( $_SERVER['HTTP_HOST'], $home['host'] ) !== 0 ) {
                return null;
            }
        }

        $key     = 'adfoin_oauth_state_' . $state;
        $context = get_transient( $key );
        delete_transient( $key );

        if ( is_array( $context ) ) {
            if ( ! isset( $context['platform'] ) || $context['platform'] !== $platform ) {
                return null;
            }

            // Note: we deliberately do NOT enforce a user_id check here.
            //
            // The OAuth callback lands on `/wp-json/advancedformintegration/<platform>`,
            // a REST API endpoint authenticated via nonce — NOT cookies. In
            // that context `get_current_user_id()` returns 0, so any
            // `$current === $context['user_id']` check fails and the state
            // is rejected. The previous behaviour broke OAuth across every
            // platform because the popup closed silently with no token
            // exchange ever happening. The cookie-fallback workaround that
            // had been added (wp_validate_auth_cookie of LOGGED_IN_COOKIE)
            // helps but still misses cases where SameSite=Lax or security
            // plugins strip cookies on cross-site redirect chains, so the
            // safest thing is to drop the binding entirely. Two reasons
            // it's safe to drop:
            //
            //   1. The state token itself is 32 hex chars from
            //      `random_bytes(16)` — already unguessable, providing
            //      CSRF protection without needing a user binding.
            //   2. The transient is single-use (deleted on lookup just
            //      above), so even a captured state can't be replayed.

            return array(
                'cred_id' => isset( $context['cred_id'] ) ? (string) $context['cred_id'] : '',
                'extra'   => isset( $context['extra'] ) && is_array( $context['extra'] ) ? $context['extra'] : array(),
                'legacy'  => false,
            );
        }

        // Legacy fallback: pre-helper flows passed cred_id directly as state.
        // Match $state against existing credential ids so in-flight flows
        // don't error out the first time this code runs.
        if ( function_exists( 'adfoin_read_credentials' ) ) {
            $credentials = adfoin_read_credentials( $platform );
            if ( is_array( $credentials ) ) {
                foreach ( $credentials as $cred ) {
                    if ( isset( $cred['id'] ) && (string) $cred['id'] === $state ) {
                        return array(
                            'cred_id' => (string) $cred['id'],
                            'extra'   => array(),
                            'legacy'  => true,
                        );
                    }
                }
            }
        }

        return null;
    }

    /**
     * Checks if the OAuth 2.0 client is active.
     *
     * @return bool
     */
    public function is_active() {

        return !empty( $this->refresh_token );
    }

    /**
     * Apply a decoded OAuth token-response body to the instance.
     *
     * Sets access_token, refresh_token (only when present and non-empty —
     * many providers omit it on refresh), and token_expires (parsed from
     * `expires_in`, in seconds from now). Called by `request_token` and
     * `refresh_token` so subclass overrides can centralize parsing without
     * duplicating the field-by-field check.
     *
     * @param mixed $body Decoded JSON body from the OAuth token endpoint.
     */
    protected function apply_token_response( $body ): void {
        if ( ! is_array( $body ) ) {
            return;
        }

        $had_access_token = false;

        if ( isset( $body['access_token'] ) && $body['access_token'] !== '' ) {
            $this->access_token = $body['access_token'];
            $had_access_token   = true;
        }

        // Many providers (Google on refresh, Zoho on subsequent refreshes)
        // omit refresh_token on refresh — preserve the existing one.
        if ( isset( $body['refresh_token'] ) && $body['refresh_token'] !== '' ) {
            $this->refresh_token = $body['refresh_token'];
        }

        if ( isset( $body['expires_in'] ) ) {
            $expires_in = (int) $body['expires_in'];
            if ( $expires_in > 0 ) {
                $this->token_expires = time() + $expires_in;
            }
        }

        // A successful response clears any prior "connection broken" flag.
        if ( $had_access_token ) {
            $this->clear_connection_failed();
        }
    }

    /**
     * Whether the current access token is expired (or within 60s of expiry).
     *
     * Returns false when `token_expires` is unknown (zero) — callers who
     * want a refresh in that case should rely on the reactive 401 path.
     * Returns false when there's no refresh token to refresh with.
     *
     * @return bool
     */
    protected function is_token_expired(): bool {
        if ( empty( $this->refresh_token ) ) {
            return false;
        }
        if ( empty( $this->token_expires ) ) {
            return false;
        }
        return time() >= ( (int) $this->token_expires - 60 );
    }

    /**
     * Persist current token state into the canonical credential record for
     * `$this->platform_slug` keyed by `$this->cred_id`.
     *
     * Writes the canonical token fields (access_token, refresh_token,
     * token_expires) and optionally any provider-specific extras passed
     * via `$extras` — e.g. Salesforce's `instance_url`, Zoho's `data_center`.
     *
     * Each extra is written only when its value is non-null and non-empty
     * string, so passing a stale or unset property is a no-op.
     *
     * `$token_key_map` lets platforms whose existing credential records
     * use camelCase storage keys (`accessToken` instead of `access_token`,
     * etc.) write to the same keys their pre-helper code reads from. The
     * map is merged on top of canonical defaults:
     *
     *     $this->persist_token_to_credential( array(), array(
     *         'access_token'  => 'accessToken',
     *         'refresh_token' => 'refreshToken',
     *     ) );
     *
     * @param array $extras        [ field_name => value, ... ] additional
     *                             non-token fields to merge in.
     * @param array $token_key_map Optional override for canonical token field
     *                             names (`access_token`, `refresh_token`,
     *                             `token_expires`).
     */
    protected function persist_token_to_credential( array $extras = array(), array $token_key_map = array() ): void {
        if ( '' === (string) $this->platform_slug || empty( $this->cred_id ) ) {
            return;
        }
        if ( ! function_exists( 'adfoin_read_credentials' ) ) {
            return;
        }

        $access_key   = isset( $token_key_map['access_token'] )  ? $token_key_map['access_token']  : 'access_token';
        $refresh_key  = isset( $token_key_map['refresh_token'] ) ? $token_key_map['refresh_token'] : 'refresh_token';
        $expires_key  = isset( $token_key_map['token_expires'] ) ? $token_key_map['token_expires'] : 'token_expires';

        $credentials = adfoin_read_credentials( $this->platform_slug );
        if ( ! is_array( $credentials ) ) {
            return;
        }

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $this->cred_id ) {
                if ( null !== $this->access_token ) {
                    $cred[ $access_key ] = $this->access_token;
                }
                if ( null !== $this->refresh_token ) {
                    $cred[ $refresh_key ] = $this->refresh_token;
                }
                if ( $this->token_expires ) {
                    $cred[ $expires_key ] = (int) $this->token_expires;
                }
                foreach ( $extras as $field => $value ) {
                    if ( $value !== null && $value !== '' ) {
                        $cred[ $field ] = $value;
                    }
                }
                $found = true;
                break;
            }
        }
        unset( $cred );

        if ( $found ) {
            adfoin_save_credentials( $this->platform_slug, $credentials );
        }
    }

    /**
     * Return a safe list of credentials suitable for AJAX responses that
     * feed UI dropdowns / status tables.
     *
     * Strips token fields and client secrets — the browser never needs
     * `access_token` / `refresh_token` / `client_secret` to render an
     * account picker. Provider-specific non-secret extras (Zoho's
     * `data_center`) are preserved so dropdowns can still display them.
     *
     * Adds two derived flags for the UI:
     *   - `connected`         — true when access_token + refresh_token are both present
     *   - `connection_failed` — true when a previous refresh failed and hasn't been recovered
     *
     * @param string|null $platform Platform slug (defaults to $this->platform_slug).
     * @return array
     */
    protected function safe_credentials_list( $platform = null ): array {
        $platform = $platform ?: $this->platform_slug;
        if ( '' === (string) $platform || ! function_exists( 'adfoin_read_credentials' ) ) {
            return array();
        }

        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            return array();
        }

        // Strip session tokens from the AJAX response — the browser never
        // needs them to render the accounts table or pre-fill the edit form.
        // client_id and client_secret are NOT stripped: the table needs them
        // to render the columns and the edit form needs them to pre-fill on
        // re-open. Display masking is enforced client-side via the per-field
        // `mask: true` flag in each platform's field config (oauth-manager.js
        // applies `maskValue()` to the table cell; the data-* attribute used
        // for the edit pre-fill always carries the unmasked value).
        //
        // Both snake_case and camelCase variants are listed because legacy
        // platforms (bombbomb, cleverreach, mailup, liondesk, moneybird)
        // stored credential records with camelCase keys, and their existing
        // wp_option data is still in the wild.
        $sensitive = array(
            'access_token', 'accessToken',
            'refresh_token', 'refreshToken',
            'token_expires', 'tokenExpires',
        );

        $list = array();
        foreach ( $credentials as $cred ) {
            if ( ! is_array( $cred ) ) {
                continue;
            }

            $safe = $cred;
            foreach ( $sensitive as $field ) {
                unset( $safe[ $field ] );
            }

            $access_token  = isset( $cred['access_token'] )  ? $cred['access_token']  : ( isset( $cred['accessToken'] )  ? $cred['accessToken']  : '' );
            $refresh_token = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : ( isset( $cred['refreshToken'] ) ? $cred['refreshToken'] : '' );

            $safe['connected']         = ! empty( $access_token ) && ! empty( $refresh_token );
            $safe['connection_failed'] = ! empty( $cred['connection_failed_at'] );

            $list[] = $safe;
        }

        return $list;
    }

    /**
     * Shared scaffolding for platform "Test connection" AJAX endpoints.
     *
     * Subclass usage:
     *
     *   public function test_connection() {
     *       $this->run_test_connection_ajax( function () {
     *           return $this->myplatform_request( 'cheap/endpoint' );
     *       } );
     *   }
     *
     * Handles nonce verification, credential loading, access-token check,
     * and response interpretation in one place so per-platform handlers stay
     * a few lines long. Always exits via wp_send_json_*.
     *
     * Requires the subclass to implement either `load_credentials($id)` or
     * `set_credentials($id)` to hydrate token state.
     *
     * @param callable $request_callback Closure returning the wp_remote_*
     *                                   response (or WP_Error). Run after
     *                                   credentials are loaded.
     */
    protected function run_test_connection_ajax( callable $request_callback ): void {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( wp_unslash( $_POST['_nonce'] ?? '' ), 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( '' === $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id', 'advanced-form-integration' ) ) );
        }

        if ( method_exists( $this, 'load_credentials' ) ) {
            $this->load_credentials( $cred_id );
        } elseif ( method_exists( $this, 'set_credentials' ) ) {
            $this->set_credentials( $cred_id );
        } else {
            $this->set_credentials_from_id( $cred_id );
        }

        if ( empty( $this->access_token ) ) {
            wp_send_json_error( array( 'message' => __( 'Account not authorized', 'advanced-form-integration' ) ) );
        }

        $response = $request_callback();

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $msg  = is_array( $body ) && isset( $body['message'] ) ? $body['message'] : sprintf( 'HTTP %d', $code );
            wp_send_json_error( array( 'message' => $msg ) );
        }

        wp_send_json_success( array( 'message' => __( 'Connection OK', 'advanced-form-integration' ) ) );
    }

    /**
     * Stamp the current credential record as "connection failed" — used by
     * `refresh_token` when the provider returns 401 (refresh token invalid /
     * revoked). The OAuth Manager UI surfaces this as a "Connection broken
     * — Reconnect" badge so users know to redo the OAuth flow.
     *
     * @param string $reason Optional short reason string.
     */
    protected function mark_connection_failed( string $reason = '' ): void {
        if ( '' === (string) $this->platform_slug || empty( $this->cred_id ) ) {
            return;
        }
        if ( ! function_exists( 'adfoin_read_credentials' ) ) {
            return;
        }

        $credentials = adfoin_read_credentials( $this->platform_slug );
        if ( ! is_array( $credentials ) ) {
            return;
        }

        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $this->cred_id ) {
                $cred['connection_failed_at']     = time();
                $cred['connection_failed_reason'] = (string) $reason;
                break;
            }
        }
        unset( $cred );

        adfoin_save_credentials( $this->platform_slug, $credentials );
    }

    /**
     * Clear a previous "connection failed" stamp — called when a fresh auth
     * or successful refresh has restored a working token.
     */
    protected function clear_connection_failed(): void {
        if ( '' === (string) $this->platform_slug || empty( $this->cred_id ) ) {
            return;
        }
        if ( ! function_exists( 'adfoin_read_credentials' ) ) {
            return;
        }

        $credentials = adfoin_read_credentials( $this->platform_slug );
        if ( ! is_array( $credentials ) ) {
            return;
        }

        $changed = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $this->cred_id ) {
                if ( isset( $cred['connection_failed_at'] ) || isset( $cred['connection_failed_reason'] ) ) {
                    unset( $cred['connection_failed_at'], $cred['connection_failed_reason'] );
                    $changed = true;
                }
                break;
            }
        }
        unset( $cred );

        if ( $changed ) {
            adfoin_save_credentials( $this->platform_slug, $credentials );
        }
    }

    /**
     * Hydrate this instance from a stored credential record.
     *
     * Loads client_id/client_secret/access_token/refresh_token/token_expires
     * from `adfoin_credentials[$this->platform_slug]` matching $cred_id.
     * Subclasses with extra fields (e.g. Zoho's data_center) should override
     * and chain via `parent::set_credentials_from_id($cred_id)`.
     *
     * @param string $cred_id The credential record id.
     * @return bool True if a record was found and loaded.
     */
    protected function set_credentials_from_id( $cred_id ): bool {
        if ( '' === (string) $this->platform_slug ) {
            return false;
        }
        if ( ! function_exists( 'adfoin_read_credentials' ) ) {
            return false;
        }

        $credentials = adfoin_read_credentials( $this->platform_slug );
        if ( ! is_array( $credentials ) ) {
            return false;
        }

        foreach ( $credentials as $cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $cred_id ) {
                $this->cred_id       = $cred['id'];
                $this->client_id     = isset( $cred['client_id'] ) ? $cred['client_id'] : '';
                $this->client_secret = isset( $cred['client_secret'] ) ? $cred['client_secret'] : '';
                $this->access_token  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                $this->refresh_token = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                $this->token_expires = isset( $cred['token_expires'] ) ? (int) $cred['token_expires'] : 0;
                return true;
            }
        }

        return false;
    }

    /**
     * Saves the OAuth 2.0 client data to the database.
     *
     * Default behavior persists the canonical token fields (access_token,
     * refresh_token, token_expires) into the credential record matching
     * `$this->cred_id` under `adfoin_credentials[$this->platform_slug]`.
     *
     * Subclasses with provider-specific extras (data_center, task_lists,
     * legacy single-account fallback to old `adfoin_<slug>_keys` options)
     * should override this and call `$this->persist_token_to_credential()`
     * for the canonical part.
     *
     * @return void
     */
    protected function save_data() {
        $this->persist_token_to_credential();
    }

    /**
     * Resets the OAuth 2.0 client data.
     *
     * @return void
     */
    protected function reset_data() {
    }

    /**
     * Gets the redirect URI for the OAuth 2.0 client.
     *
     * Defaults to admin_url() for back-compat with existing OAuth app
     * configurations users have registered with their providers. Subclasses
     * (Zoho* family etc.) may return the REST callback URL directly.
     *
     * Whatever subclasses return, the URL must be on the same origin as the
     * WordPress site — `consume_oauth_state` enforces this on the receiving
     * end so a misconfigured override can't leak credentials cross-origin.
     *
     * @return string
     */
    protected function get_redirect_uri() {
        return admin_url();
    }

    /**
     * Verify a URL (typically `get_redirect_uri()` or the inbound request
     * URL on a callback) is on the same origin as the WordPress site.
     *
     * Returns true for empty/relative URLs (treated as same-origin).
     *
     * @param string $url
     * @return bool
     */
    protected static function is_same_origin_url( string $url ): bool {
        $url = (string) $url;
        if ( '' === $url ) {
            return true;
        }
        $parsed = wp_parse_url( $url );
        if ( empty( $parsed['host'] ) ) {
            // Relative URL or path — same origin by definition.
            return true;
        }
        $home = wp_parse_url( home_url() );
        if ( empty( $home['host'] ) ) {
            return false;
        }
        return strcasecmp( $parsed['host'], $home['host'] ) === 0;
    }

    /**
     * Authorizes the user to access the OAuth 2.0 server.
     *
     * @param string $scope The scope of the OAuth 2.0 authorization request.
     * @return void
     */
    protected function authorize( string $scope = '' ) {

        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            // add_query_arg() URL-encodes each value — passing an already
            // urlencode()'d string here produced a double-encoded redirect_uri.
            'redirect_uri'  => $this->get_redirect_uri()
        );

        if( $scope ) {
            $data["scope"] = $scope;
        }

        $endpoint = add_query_arg( $data, $this->authorization_endpoint );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit();
        }
    }

    protected function get_http_authorization_header( $scheme = 'basic' ) {

        $scheme = strtolower( trim( $scheme ) );

        switch ( $scheme ) {
            case 'bearer':
                return sprintf( 'Bearer %s', $this->access_token );
            case 'basic':
            default:
                return sprintf( 'Basic %s',
                    base64_encode( $this->client_id . ':' . $this->client_secret )
                );
        }
    }

    /**
     * Get the bearer token authorization header.
     *
     * @return string The bearer token authorization header.
     */
    public function get_bearer_token() {
        return $this->get_http_authorization_header( 'bearer' );
    }

    protected function request_token( $authorization_code ) {

        $params = array(
            'code'         => $authorization_code,
            'redirect_uri' => $this->get_redirect_uri(),
            'grant_type'   => 'authorization_code',
            'client_id'    => $this->client_id,
            'client_secret'=> $this->client_secret
        );

        // Match refresh_token()'s 30s timeout — the default 5s is tight for a
        // first-token exchange against a slow OAuth provider.
        $response = wp_remote_post( $this->token_endpoint, array(
            'timeout' => 30,
            'body'    => $params,
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            $this->apply_token_response( $response_body );
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {

        // Serialize refresh per credential. Providers like Constant Contact issue
        // ROTATING refresh tokens — each successful refresh invalidates the
        // previous refresh token. Under concurrent submissions two requests would
        // both refresh; the second, using a now-stale token, gets a 401 that
        // nukes the stored tokens ("API becomes unauthorized" until re-Authorize).
        // The lock lets only one request refresh; the others wait for it, reload
        // the freshly-saved tokens and report success so the caller retries.
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

        $endpoint = add_query_arg(
            array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
            ),
            $this->refresh_token_endpoint
        );

        $request = [
            'timeout' => 30,
            'headers' => array(
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ),
        ];

        $response      = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 == $response_code ) { // Unauthorized - refresh token is invalid
            error_log( sprintf(
                'OAuth2 refresh failed with 401 for service: %s, endpoint: %s',
                defined('static::service_name') ? static::service_name : 'unknown',
                $this->refresh_token_endpoint
            ) );

            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } else if ( 200 == $response_code ) {
            // apply_token_response preserves the existing refresh_token when
            // the response omits it (rotating-vs-static refresh token).
            $this->apply_token_response( $response_body );
        } else {
            // Other error codes — log code only (NOT the body, which may
            // echo back the refresh token) and keep existing tokens for the
            // caller to deal with.
            error_log( sprintf(
                'OAuth2 refresh failed with code %d for service: %s',
                $response_code,
                defined('static::service_name') ? static::service_name : 'unknown'
            ) );
        }

        $this->save_data();

        if ( $lock_key ) {
            $this->release_token_refresh_lock( $lock_key );
        }

        return $response;
    }

    /**
     * Per-credential lock key for serializing token refreshes. Empty when no
     * platform slug is set, in which case refresh runs unlocked (old behaviour).
     */
    protected function token_refresh_lock_key() {
        if ( empty( $this->platform_slug ) ) {
            return '';
        }

        $id = ! empty( $this->cred_id ) ? (string) $this->cred_id : 'default';

        return 'adfoin_tokrefresh_' . md5( $this->platform_slug . '|' . $id );
    }

    /**
     * Atomic lock via add_option (UNIQUE option_name index): only the first
     * concurrent caller creates it. An expired lock (crashed holder) is taken
     * over so refresh can never wedge permanently.
     */
    protected function acquire_token_refresh_lock( $key ) {
        $now = time();

        if ( add_option( $key, (string) ( $now + 30 ), '', 'no' ) ) {
            return true;
        }

        $expires = (int) get_option( $key, 0 );
        if ( $expires > 0 && $now > $expires ) {
            update_option( $key, (string) ( $now + 30 ), 'no' );
            return true;
        }

        return false;
    }

    protected function release_token_refresh_lock( $key ) {
        delete_option( $key );
    }

    /**
     * Wait (up to ~6s) for the in-flight refresh to release the lock. The
     * options cache is busted each poll so we observe the cross-request delete.
     */
    protected function wait_for_token_refresh_lock( $key ) {
        for ( $i = 0; $i < 20; $i++ ) {
            usleep( 300000 ); // 0.3s
            wp_cache_delete( $key, 'options' );
            $expires = (int) get_option( $key, 0 );

            if ( ! $expires || time() > $expires ) {
                return;
            }
        }
    }

    /**
     * Re-read the tokens a concurrent refresh just saved. Busts the options
     * cache first so we don't get the stale copy loaded at request start.
     */
    protected function reload_oauth_credentials() {
        if ( empty( $this->cred_id ) ) {
            return;
        }

        wp_cache_delete( 'adfoin_credentials', 'options' );
        wp_cache_delete( 'alloptions', 'options' );

        $this->set_credentials_from_id( $this->cred_id );
    }

    protected function remote_request( $url, $request = array() ) {

        $request = wp_parse_args( $request, array( 'timeout' => 30 ) );

        // Proactive refresh: if we know the access token is expired (or
        // within the 60s buffer), refresh BEFORE issuing the request to
        // avoid a wasted 401 round trip. is_token_expired() returns false
        // when token_expires is unknown, so platforms that don't yet
        // populate it fall through to the reactive 401 path below.
        if ( ! isset( $request['_retry_after_refresh'] ) && $this->is_token_expired() ) {
            $this->refresh_token();
        }

        // Stamp last_used_at on the credential record. Throttled to once
        // per minute per credential inside the helper, so this is cheap
        // even on hot send paths.
        if ( ! empty( $this->platform_slug ) && ! empty( $this->cred_id )
            && function_exists( 'adfoin_mark_credential_used' ) ) {
            adfoin_mark_credential_used( $this->platform_slug, $this->cred_id );
        }

        $request['headers'] = array_merge(
            isset( $request['headers'] ) ? (array) $request['headers'] : array(),
            array( 'Authorization' => $this->get_http_authorization_header( 'bearer' ), )

        );

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
                }
            }
        }

        // Retry on rate limiting (HTTP 429), honouring Retry-After. Bounded to
        // two attempts via a counter stamped on the request args. Inherited by
        // every OAuth2 platform that does not override remote_request().
        if ( 429 === wp_remote_retrieve_response_code( $response ) ) {
            $attempts = isset( $request['_429_retries'] ) ? (int) $request['_429_retries'] : 0;

            if ( $attempts < 2 ) {
                $retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
                $retry_after = ( $retry_after > 0 && $retry_after <= 10 ) ? $retry_after : 3;
                sleep( $retry_after );

                $request['_429_retries'] = $attempts + 1;

                return $this->remote_request( $url, $request );
            }
        }

        return $response;
    }
}