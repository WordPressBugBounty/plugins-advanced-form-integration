<?php

/**
 * Jobber — Create Client & Job via the GraphQL API.
 *
 * Endpoint: POST https://api.getjobber.com/api/graphql
 * Headers:  Authorization: Bearer {token}, X-JOBBER-GRAPHQL-VERSION: YYYY-MM-DD
 * Auth:     OAuth 2.0 authorization code + rotating refresh tokens
 *           (ADFOIN_Jobber); legacy pasted access tokens still accepted.
 *
 * @link https://developer.getjobber.com/docs
 */

/**
 * Pinned Jobber GraphQL API version.
 *
 * @link https://developer.getjobber.com/docs/changelog
 */
if ( ! defined( 'ADFOIN_JOBBER_DEFAULT_API_VERSION' ) ) {
    define( 'ADFOIN_JOBBER_DEFAULT_API_VERSION', '2025-04-16' );
}

/**
 * Jobber OAuth 2.0 (authorization-code grant) with refresh tokens.
 *
 * Jobber access tokens expire after 60 minutes. Accounts connected through
 * the OAuth popup store client_id / client_secret / access_token /
 * refresh_token / token_expires and are refreshed automatically. Jobber
 * apps have Refresh Token Rotation ON by default (required for the App
 * Marketplace): every refresh returns a NEW refresh token and the old one
 * stops working, so the returned refresh_token is always persisted.
 *
 * Legacy accounts saved before OAuth support only carry a pasted
 * `accessToken` — adfoin_jobber_request() keeps sending those as-is.
 *
 * @link https://developer.getjobber.com/docs/building_your_app/app_authorization/
 * @link https://developer.getjobber.com/docs/building_your_app/refresh_token_rotation/
 */
class ADFOIN_Jobber extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'jobber';

    const service_name           = 'jobber';
    const authorization_endpoint = 'https://api.getjobber.com/api/oauth/authorize';
    const token_endpoint         = 'https://api.getjobber.com/api/oauth/token';
    const refresh_token_endpoint = 'https://api.getjobber.com/api/oauth/token';
    const api_endpoint           = 'https://api.getjobber.com/api/graphql';

    /** Jobber documents a 60 minute access-token lifetime. */
    const default_token_lifetime = 3600;

    /** @var string Pinned X-JOBBER-GRAPHQL-VERSION for the loaded credential. */
    public $api_version = '';

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

        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );

        // Action names kept from the pre-OAuth version — the free action
        // editor and jobberpro-component.js list accounts through
        // adfoin_get_jobber_credentials.
        add_action( 'wp_ajax_adfoin_get_jobber_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_jobber_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_jobber_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_settings_tab( $providers ) {
        $providers['jobber'] = __( 'Jobber', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'jobber' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'Client ID', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Client Secret', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => false,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'apiVersion',
                'label'         => __( 'GraphQL Version (optional)', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'show_in_table' => false,
                'placeholder'   => ADFOIN_JOBBER_DEFAULT_API_VERSION,
                'description'   => __( 'Leave blank to use the bundled default, or pin a YYYY-MM-DD version from the Jobber changelog. Sent as the X-JOBBER-GRAPHQL-VERSION header.', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to the %s and create (or open) an app.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.getjobber.com/">Jobber Developer Center</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Set the app\'s <strong>OAuth Callback URL</strong> to the URL below:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Under <strong>Scopes</strong>, grant read and write access to <strong>Clients</strong> and <strong>Jobs</strong>, then save the app.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the app\'s <strong>Client ID</strong> and <strong>Client Secret</strong> into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> and approve access in the popup. Access tokens are refreshed automatically.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Accounts added earlier with a pasted access token keep working until that token expires. Edit the account, enter the Client ID and Secret, and authorize to switch it to auto-refresh.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Jobber', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'jobber',
            __( 'Jobber', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/jobber', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Jobber with ?code=…&state=…. Exchanges the code
     * for tokens and closes the popup.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'jobber' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        if ( ! $this->set_credentials( $cred_id ) || ! $this->client_id || ! $this->client_secret ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Credential not found or incomplete.', 'advanced-form-integration' ) );
            exit;
        }

        $response = $this->request_token( $code );

        $success = false;
        $message = __( 'Unknown error.', 'advanced-form-integration' );

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        } else {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 === (int) wp_remote_retrieve_response_code( $response ) && ! empty( $body['access_token'] ) ) {
                $success = true;
                $message = __( 'Connected successfully!', 'advanced-form-integration' );
            } else {
                $message = $body['error_description'] ?? ( $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' ) );
            }
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    public function ajax_get_credentials() {
        adfoin_verify_nonce();

        $list = $this->safe_credentials_list();

        // Legacy pasted-token accounts have no refresh token; report them as
        // connected so the table doesn't flag a still-usable account.
        $raw = adfoin_read_credentials( 'jobber' );
        foreach ( $list as &$row ) {
            if ( ! empty( $row['connected'] ) || ! empty( $row['connection_failed'] ) ) {
                continue;
            }
            foreach ( $raw as $cred ) {
                if ( isset( $cred['id'], $row['id'] ) && (string) $cred['id'] === (string) $row['id'] ) {
                    $row['connected'] = ! empty( $cred['accessToken'] );
                    break;
                }
            }
        }
        unset( $row );

        wp_send_json_success( $list );
    }

    public function ajax_save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'jobber';
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
        $api_version   = isset( $_POST['apiVersion'] )    ? sanitize_text_field( wp_unslash( $_POST['apiVersion'] ) )    : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $existing_index = null;
        foreach ( $credentials as $index => $cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $existing_index = $index;
                break;
            }
        }

        $existing = null !== $existing_index ? $credentials[ $existing_index ] : array();

        // Secrets are left blank in the edit form to keep the stored value.
        if ( '' === $client_id && ! empty( $existing['client_id'] ) ) {
            $client_id = $existing['client_id'];
        }
        if ( '' === $client_secret && ! empty( $existing['client_secret'] ) ) {
            $client_secret = $existing['client_secret'];
        }

        $has_oauth_app = '' !== $client_id && '' !== $client_secret;
        $is_legacy     = ( ! empty( $existing['accessToken'] ) || ! empty( $existing['access_token'] ) )
            && empty( $existing['refresh_token'] ) && empty( $existing['refreshToken'] );

        if ( ! $has_oauth_app && ! $is_legacy ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        // Start from the existing record so legacy keys (accessToken) and
        // stored tokens survive an edit.
        // adfoin_read_credentials() exposes camelCase aliases of the token
        // keys (accessToken <-> access_token, ...); store snake_case only so
        // a stale alias can't resurrect a cleared token on the next read.
        $aliases = array(
            'clientId'     => 'client_id',
            'clientSecret' => 'client_secret',
            'accessToken'  => 'access_token',
            'refreshToken' => 'refresh_token',
            'tokenExpires' => 'token_expires',
        );
        foreach ( $aliases as $camel => $snake ) {
            if ( isset( $existing[ $camel ] ) && ( ! isset( $existing[ $snake ] ) || '' === $existing[ $snake ] ) ) {
                $existing[ $snake ] = $existing[ $camel ];
            }
            unset( $existing[ $camel ] );
        }

        $new_data = array_merge( $existing, array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'apiVersion'    => $api_version,
        ) );

        // A different OAuth app invalidates the stored token pair. A legacy
        // pasted-token account being switched to OAuth (no client_id stored
        // yet) keeps its token until the authorization completes.
        $same_app = ( ( $existing['client_id'] ?? '' ) === $client_id )
            && ( ( $existing['client_secret'] ?? '' ) === $client_secret );

        if ( ! $same_app && ! empty( $existing['client_id'] ) ) {
            $new_data['access_token']  = '';
            $new_data['refresh_token'] = '';
            unset( $new_data['token_expires'] );
        }

        if ( null !== $existing_index ) {
            $credentials[ $existing_index ] = $new_data;
        } else {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Legacy pasted-token account edited without OAuth app details —
        // plain save, nothing to authorize.
        if ( ! $has_oauth_app ) {
            wp_send_json_success();
        }

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'state'         => self::issue_oauth_state( 'jobber', $id ),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            $response = $this->graphql_request( '{ account { id name } }' );

            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['errors'][0]['message'] ) ) {
                    return new WP_Error( 'jobber_graphql_error', $body['errors'][0]['message'] );
                }
            }

            return $response;
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/jobber' );
    }

    /**
     * Apply a token response, then fill in token_expires when Jobber omits
     * `expires_in`: read the JWT `exp` claim, else assume 60 minutes.
     */
    protected function apply_token_response( $body ): void {
        $has_new_token = is_array( $body ) && ! empty( $body['access_token'] );

        if ( $has_new_token ) {
            $this->token_expires = 0;
        }

        parent::apply_token_response( $body );

        if ( $has_new_token && empty( $this->token_expires ) ) {
            $exp = self::jwt_expiry( $this->access_token );
            $this->token_expires = $exp ? $exp : time() + self::default_token_lifetime;
        }
    }

    /**
     * Read the `exp` claim from a JWT access token without verifying it.
     * Returns 0 when the token isn't a JWT or has no usable `exp`.
     */
    protected static function jwt_expiry( $token ) {
        $parts = explode( '.', (string) $token );
        if ( 3 !== count( $parts ) ) {
            return 0;
        }

        $payload = base64_decode( strtr( $parts[1], '-_', '+/' ) );
        $claims  = $payload ? json_decode( $payload, true ) : null;

        if ( is_array( $claims ) && isset( $claims['exp'] ) && is_numeric( $claims['exp'] ) && (int) $claims['exp'] > time() ) {
            return (int) $claims['exp'];
        }

        return 0;
    }

    /**
     * POST /api/oauth/token with grant_type=refresh_token, client_id,
     * client_secret and refresh_token (form-encoded). Serialized per
     * credential because rotation invalidates the old refresh token — a
     * concurrent refresh with the stale token would fail.
     */
    protected function refresh_token() {
        if ( empty( $this->refresh_token ) || empty( $this->client_id ) || empty( $this->client_secret ) ) {
            return null;
        }

        $lock_key  = $this->token_refresh_lock_key();
        $have_lock = $lock_key ? $this->acquire_token_refresh_lock( $lock_key ) : true;

        if ( ! $have_lock ) {
            $this->wait_for_token_refresh_lock( $lock_key );
            $this->reload_oauth_credentials();
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
            ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        $revoked = 401 === $code
            || ( 400 === $code && is_array( $body ) && isset( $body['error'] ) && 'invalid_grant' === $body['error'] );

        if ( $revoked ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
            $this->save_data();
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            // Rotation: apply_token_response stores the new refresh_token.
            $this->apply_token_response( $body );
            $this->save_data();
        } else {
            error_log( sprintf( 'Jobber OAuth refresh failed with code %d', $code ) );
        }

        if ( $lock_key ) {
            $this->release_token_refresh_lock( $lock_key );
        }

        return $response;
    }

    /**
     * Load a credential record. Legacy pasted-token rows (`accessToken`) are
     * mapped onto access_token with no refresh token, so the test-connection
     * button still works for them.
     */
    public function set_credentials( $cred_id ) {
        $cred = adfoin_get_credentials_by_id( 'jobber', $cred_id );

        if ( empty( $cred ) || ! is_array( $cred ) ) {
            return false;
        }

        $this->cred_id       = $cred['id'] ?? $cred_id;
        $this->client_id     = $cred['client_id'] ?? '';
        $this->client_secret = $cred['client_secret'] ?? '';
        $this->refresh_token = $cred['refresh_token'] ?? '';
        $this->access_token  = ! empty( $cred['access_token'] ) ? $cred['access_token'] : ( $cred['accessToken'] ?? '' );
        $this->token_expires = isset( $cred['token_expires'] ) ? (int) $cred['token_expires'] : 0;
        $this->api_version   = isset( $cred['apiVersion'] ) ? trim( (string) $cred['apiVersion'] ) : '';

        return true;
    }

    /**
     * Re-read credentials after a concurrent refresh (base implementation
     * only knows the canonical keys; keep apiVersion too).
     */
    protected function reload_oauth_credentials() {
        if ( empty( $this->cred_id ) ) {
            return;
        }

        wp_cache_delete( 'adfoin_credentials', 'options' );
        wp_cache_delete( 'alloptions', 'options' );

        $this->set_credentials( $this->cred_id );
    }

    /**
     * Headers sent on every GraphQL call, minus Authorization (added by
     * request_with_token_refresh so it is rebuilt after a refresh).
     */
    protected function build_base_headers() {
        return array(
            'X-JOBBER-GRAPHQL-VERSION' => '' !== $this->api_version ? $this->api_version : ADFOIN_JOBBER_DEFAULT_API_VERSION,
            'Content-Type'             => 'application/json',
            'Accept'                   => 'application/json',
        );
    }

    /**
     * Send a GraphQL document with proactive + 401 token refresh.
     *
     * @param string     $query     GraphQL document.
     * @param array      $variables Variables payload.
     * @param array|null $sent_args Out: args actually sent, for logging.
     * @return array|WP_Error
     */
    public function graphql_request( $query, $variables = array(), &$sent_args = null ) {
        if ( ! empty( $this->cred_id ) && function_exists( 'adfoin_mark_credential_used' ) ) {
            adfoin_mark_credential_used( $this->platform_slug, $this->cred_id );
        }

        $args = array(
            'timeout' => 30,
            'method'  => 'POST',
            'headers' => $this->build_base_headers(),
            'body'    => wp_json_encode(
                array(
                    'query'     => $query,
                    'variables' => empty( $variables ) ? new stdClass() : $variables,
                )
            ),
        );

        return $this->request_with_token_refresh( self::api_endpoint, $args, null, $sent_args );
    }
}

ADFOIN_Jobber::get_instance();

add_filter( 'adfoin_action_providers', 'adfoin_jobber_actions', 10, 1 );

function adfoin_jobber_actions( $actions ) {
    $actions['jobber'] = array(
        'title' => __( 'Jobber', 'advanced-form-integration' ),
        'tasks' => array(
            'create_job' => __( 'Create Client & Job', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_jobber_action_fields' );

function adfoin_jobber_action_fields() {
    ?>
    <script type="text/template" id="jobber-action-template">
        <table class="form-table" v-if="action.task == 'create_job'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Jobber Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_jobber_credentials_list(); ?>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=jobber' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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
            <?php adfoin_pro_feature_notice( 'create_job', 'Jobber [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_jobber_fields', 'adfoin_get_jobber_fields' );

function adfoin_get_jobber_fields() {
    adfoin_verify_nonce();

    // Basic field set the free plugin pushes through clientCreate /
    // clientEdit / jobCreate. Tags + external IDs + custom-JSON merges
    // are gated to Pro.
    $fields = array(
        array( 'key' => 'client_id',        'value' => __( 'Existing Client ID (skips clientCreate)', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name',       'value' => __( 'Client First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',        'value' => __( 'Client Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name',     'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',            'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile_phone',     'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',            'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line1',    'value' => __( 'Street Address', 'advanced-form-integration' ), 'description' => __( 'The address is also used for the job\'s property. Jobs need a street or city.', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line2',    'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'city',             'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'province',         'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code',      'value' => __( 'Postal / Zip Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country',          'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title',        'value' => __( 'Job Title', 'advanced-form-integration' ), 'description' => __( 'Required for jobCreate.', 'advanced-form-integration' ) ),
        array( 'key' => 'job_description',  'value' => __( 'Job Description', 'advanced-form-integration' ), 'description' => __( 'Added to the job instructions.', 'advanced-form-integration' ) ),
        array( 'key' => 'job_instructions', 'value' => __( 'Job Instructions / Notes', 'advanced-form-integration' ) ),
        array( 'key' => 'job_start_at',     'value' => __( 'Job Start Date', 'advanced-form-integration' ), 'description' => __( 'Example: 2026-05-15', 'advanced-form-integration' ) ),
        array( 'key' => 'job_end_at',       'value' => __( 'Job End Date', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_jobber_job_queue', 'adfoin_jobber_job_queue', 10, 1 );

function adfoin_jobber_job_queue( $data ) {
    adfoin_jobber_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_jobber_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_jobber_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $client_id = '';
    if ( isset( $field_data['client_id'] ) ) {
        $client_id = trim( (string) adfoin_get_parsed_values( $field_data['client_id'], $posted_data ) );
    }

    $job_attributes = adfoin_jobber_collect_job_attributes( $field_data, $posted_data );

    if ( empty( $job_attributes['title'] ) ) {
        // A job title is required; bail rather than create an orphan client.
        return;
    }

    adfoin_jobber_create_client_and_job(
        $client_id,
        adfoin_jobber_collect_client_fields( $field_data, $posted_data ),
        adfoin_jobber_collect_address_fields( $field_data, $posted_data ),
        $job_attributes,
        $record,
        $credentials
    );
}

/**
 * Resolve the client (edit an existing one or create a new one), find or
 * create the property the job attaches to, then create the job.
 *
 * Jobber jobs belong to a property, not a client:
 *   jobCreate(input: JobCreateAttributes!) needs propertyId + invoicing.
 * A property needs an address with at least a street or city.
 *
 * @param string $client_id      Existing client EncodedId, or '' to create one.
 * @param array  $client_input   ClientCreateInput-shaped payload.
 * @param array  $address        AddressAttributes for the job's property.
 * @param array  $job_attributes JobCreateAttributes minus propertyId.
 * @param array  $record         Integration record (for logging).
 * @param array  $credentials    Jobber credential row.
 * @param array  $client_tags    Tag labels to add to the client.
 * @return array|WP_Error|null   jobCreate result, WP_Error, or null when skipped.
 */
function adfoin_jobber_create_client_and_job( $client_id, $client_input, $address, $job_attributes, $record, $credentials, $client_tags = array() ) {
    $property_id = '';

    if ( $client_id ) {
        $existing = adfoin_jobber_get_client( $client_id, $credentials );

        if ( is_wp_error( $existing ) ) {
            adfoin_jobber_log_error( $existing, $record );
            return $existing;
        }

        $edit_input = adfoin_jobber_client_edit_input( $client_input, $existing );

        if ( ! empty( $client_tags ) ) {
            $edit_input['tagsToAdd'] = array_values( $client_tags );
        }

        if ( ! empty( $edit_input ) ) {
            adfoin_jobber_run_mutation(
                'clientEdit',
                'mutation clientEdit($clientId: EncodedId!, $input: ClientEditInput!) { clientEdit(clientId: $clientId, input: $input) { client { id } userErrors { message } } }',
                array(
                    'clientId' => $client_id,
                    'input'    => $edit_input,
                ),
                $record,
                $credentials
            );
        }

        $property_id = adfoin_jobber_extract_nested_value( $existing, array( 'properties', 0, 'id' ) );
    } elseif ( ! empty( $client_input ) ) {
        // Create the job's property together with the client.
        if ( adfoin_jobber_address_is_usable( $address ) ) {
            $client_input['properties'] = array( array( 'address' => $address ) );
        }

        $create_response = adfoin_jobber_run_mutation(
            'clientCreate',
            'mutation clientCreate($input: ClientCreateInput!) { clientCreate(input: $input) { client { id properties { id } } userErrors { message } } }',
            array( 'input' => $client_input ),
            $record,
            $credentials
        );

        if ( is_wp_error( $create_response ) ) {
            return $create_response;
        }

        $client_id   = adfoin_jobber_extract_nested_value( $create_response, array( 'data', 'clientCreate', 'client', 'id' ) );
        $property_id = adfoin_jobber_extract_nested_value( $create_response, array( 'data', 'clientCreate', 'client', 'properties', 0, 'id' ) );

        // ClientCreateInput has no tags; add them with a follow-up edit.
        if ( $client_id && ! empty( $client_tags ) ) {
            adfoin_jobber_run_mutation(
                'clientEdit',
                'mutation clientEdit($clientId: EncodedId!, $input: ClientEditInput!) { clientEdit(clientId: $clientId, input: $input) { client { id } userErrors { message } } }',
                array(
                    'clientId' => $client_id,
                    'input'    => array( 'tagsToAdd' => array_values( $client_tags ) ),
                ),
                $record,
                $credentials
            );
        }
    }

    if ( ! $client_id ) {
        return null;
    }

    if ( ! $property_id ) {
        if ( ! adfoin_jobber_address_is_usable( $address ) ) {
            $error = new WP_Error(
                'jobber_missing_property',
                __( 'Jobber jobs need a property address. Map Street Address or City so AFI can create the property.', 'advanced-form-integration' )
            );
            adfoin_jobber_log_error( $error, $record );
            return $error;
        }

        $property_response = adfoin_jobber_run_mutation(
            'propertyCreate',
            'mutation propertyCreate($clientId: EncodedId!, $input: PropertyCreateInput!) { propertyCreate(clientId: $clientId, input: $input) { properties { id } userErrors { message } } }',
            array(
                'clientId' => $client_id,
                'input'    => array( 'properties' => array( array( 'address' => $address ) ) ),
            ),
            $record,
            $credentials
        );

        if ( is_wp_error( $property_response ) ) {
            return $property_response;
        }

        $property_id = adfoin_jobber_extract_nested_value( $property_response, array( 'data', 'propertyCreate', 'properties', 0, 'id' ) );

        if ( ! $property_id ) {
            return null;
        }
    }

    $job_attributes['propertyId'] = $property_id;

    // invoicing is required. Visit-based, invoiced on completion matches
    // the default for a new one-off job in Jobber.
    if ( empty( $job_attributes['invoicing'] ) ) {
        $job_attributes['invoicing'] = array(
            'invoicingType'     => 'VISIT_BASED',
            'invoicingSchedule' => 'ON_COMPLETION',
        );
    }

    return adfoin_jobber_run_mutation(
        'jobCreate',
        'mutation jobCreate($input: JobCreateAttributes!) { jobCreate(input: $input) { job { id jobNumber } userErrors { message } } }',
        array( 'input' => $job_attributes ),
        $record,
        $credentials
    );
}

/**
 * Fetch a client's properties, emails and phones (used to pick the job's
 * property and to avoid re-adding contact details on edit).
 *
 * @return array|WP_Error Client node.
 */
function adfoin_jobber_get_client( $client_id, $credentials ) {
    $response = adfoin_jobber_request(
        'query client($id: EncodedId!) { client(id: $id) { id properties { id } emails { address } phones { number } } }',
        array( 'id' => $client_id ),
        array(),
        $credentials
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $decoded['errors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_graphql_error', $decoded['errors'][0]['message'] );
    }

    if ( empty( $decoded['data']['client'] ) ) {
        return new WP_Error( 'jobber_client_not_found', sprintf( __( 'Jobber client %s was not found.', 'advanced-form-integration' ), $client_id ) );
    }

    return $decoded['data']['client'];
}

/**
 * Convert a ClientCreateInput-shaped payload to ClientEditInput:
 * emails/phones become emailsToAdd/phonesToAdd (skipping ones the client
 * already has), properties are dropped (handled separately).
 */
function adfoin_jobber_client_edit_input( $client_input, $existing ) {
    $edit = $client_input;
    unset( $edit['emails'], $edit['phones'], $edit['properties'] );

    $known_emails = array_map( 'strtolower', wp_list_pluck( isset( $existing['emails'] ) ? (array) $existing['emails'] : array(), 'address' ) );
    $known_phones = array_map( 'adfoin_jobber_phone_digits', wp_list_pluck( isset( $existing['phones'] ) ? (array) $existing['phones'] : array(), 'number' ) );

    foreach ( isset( $client_input['emails'] ) ? $client_input['emails'] : array() as $email ) {
        if ( ! in_array( strtolower( $email['address'] ), $known_emails, true ) ) {
            unset( $email['primary'] );
            $edit['emailsToAdd'][] = $email;
        }
    }

    foreach ( isset( $client_input['phones'] ) ? $client_input['phones'] : array() as $phone ) {
        if ( ! in_array( adfoin_jobber_phone_digits( $phone['number'] ), $known_phones, true ) ) {
            unset( $phone['primary'] );
            $edit['phonesToAdd'][] = $phone;
        }
    }

    return $edit;
}

function adfoin_jobber_phone_digits( $number ) {
    return preg_replace( '/\D+/', '', (string) $number );
}

/**
 * Jobber rejects a property address without a street or city
 * ("Properties must have an address"); country alone is not enough.
 */
function adfoin_jobber_address_is_usable( $address ) {
    foreach ( array( 'street1', 'street2', 'city', 'province', 'postalCode' ) as $key ) {
        if ( ! empty( $address[ $key ] ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Record a failure that never reached the API (missing property, client
 * lookup failed) so it shows up in the integration log.
 */
function adfoin_jobber_log_error( $error, $record ) {
    if ( $record ) {
        adfoin_add_to_log( $error, ADFOIN_Jobber::api_endpoint, array(), $record );
    }
}

function adfoin_jobber_credentials_list() {
    $credentials = adfoin_read_credentials( 'jobber' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_jobber_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'jobber', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'jobber_missing_credentials', __( 'Jobber credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

/**
 * Execute an arbitrary GraphQL mutation against Jobber.
 *
 * Centralised so both free + pro share the same userErrors / GraphQL
 * errors handling. Pass the mutation document verbatim plus its
 * variable map; we don't construct mutations dynamically anymore
 * because each Jobber mutation has its own argument shape (some take
 * `input`, some take `attributes`, some take `clientId` alongside).
 *
 * @param string $operation   Top-level mutation field name (e.g. "clientCreate").
 * @param string $query       Full GraphQL mutation document.
 * @param array  $variables   Variables payload.
 * @param array  $record      Submission record (for logging).
 * @param array  $credentials Jobber credential row.
 * @return array|WP_Error Decoded response, or WP_Error on transport / GraphQL / userErrors.
 */
if ( ! function_exists( 'adfoin_jobber_run_mutation' ) ) :
function adfoin_jobber_run_mutation( $operation, $query, $variables, $record, $credentials ) {
    $response = adfoin_jobber_request( $query, $variables, $record, $credentials );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );

    if ( ! $body ) {
        return new WP_Error( 'jobber_empty_response', __( 'Empty response returned from Jobber.', 'advanced-form-integration' ) );
    }

    $decoded = json_decode( $body, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'jobber_invalid_json', __( 'Invalid JSON received from Jobber.', 'advanced-form-integration' ) );
    }

    // Top-level GraphQL errors (auth, schema, etc.).
    if ( isset( $decoded['errors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_graphql_error', $decoded['errors'][0]['message'] );
    }

    // userErrors live under data.<operation>.userErrors per Jobber's
    // mutation convention.
    $root = $decoded['data'][ $operation ] ?? null;

    if ( isset( $root['userErrors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_user_error', $root['userErrors'][0]['message'] );
    }

    return $decoded;
}
endif;

if ( ! function_exists( 'adfoin_jobber_request' ) ) :
function adfoin_jobber_request( $query, $variables = array(), $record = array(), $credentials = array() ) {
    // OAuth-connected accounts: route through ADFOIN_Jobber so expired
    // access tokens are refreshed (proactively and on 401) and the rotated
    // refresh token is persisted.
    if ( ! empty( $credentials['refresh_token'] ) && ! empty( $credentials['id'] ) && class_exists( 'ADFOIN_Jobber' ) ) {
        $jobber = ADFOIN_Jobber::get_instance();

        if ( $jobber->set_credentials( $credentials['id'] ) ) {
            $sent_args = array();
            $response  = $jobber->graphql_request( $query, $variables, $sent_args );

            if ( $record ) {
                adfoin_add_to_log( adfoin_jobber_response_for_log( $response ), ADFOIN_Jobber::api_endpoint, $sent_args, $record );
            }

            return $response;
        }
    }

    // Legacy pasted-token accounts (no refresh token): send as before.
    $access_token = isset( $credentials['accessToken'] ) ? trim( $credentials['accessToken'] ) : '';
    if ( '' === $access_token && isset( $credentials['access_token'] ) ) {
        $access_token = trim( $credentials['access_token'] );
    }
    $api_version  = isset( $credentials['apiVersion'] ) ? trim( $credentials['apiVersion'] ) : '';

    if ( '' === $access_token ) {
        return new WP_Error( 'jobber_missing_token', __( 'Jobber access token is missing.', 'advanced-form-integration' ) );
    }

    // Jobber requires the version header on every request. Fall back to
    // the bundled default when the credential row doesn't pin one.
    if ( '' === $api_version ) {
        $api_version = ADFOIN_JOBBER_DEFAULT_API_VERSION;
    }

    $url = 'https://api.getjobber.com/api/graphql';

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Authorization'             => 'Bearer ' . $access_token,
            'X-JOBBER-GRAPHQL-VERSION'  => $api_version,
            'Content-Type'              => 'application/json',
            'Accept'                    => 'application/json',
        ),
        'body'    => wp_json_encode(
            array(
                'query'     => $query,
                'variables' => $variables,
            )
        ),
        'method'  => 'POST',
    );

    $response = wp_remote_post( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( adfoin_jobber_response_for_log( $response ), $url, $args, $record );
    }

    return $response;
}
endif;

/**
 * Jobber answers GraphQL and validation errors with HTTP 200. Return a copy
 * of the response with status 400 when the body carries `errors` or a
 * non-empty `userErrors`, so the log shows the call as failed.
 */
function adfoin_jobber_response_for_log( $response ) {
    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return $response;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
    $failed  = ! empty( $decoded['errors'] );

    if ( ! $failed && isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
        foreach ( $decoded['data'] as $root ) {
            if ( is_array( $root ) && ! empty( $root['userErrors'] ) ) {
                $failed = true;
                break;
            }
        }
    }

    if ( $failed ) {
        $response['response'] = array(
            'code'    => 400,
            'message' => 'GraphQL Error',
        );
    }

    return $response;
}

/**
 * Build a ClientCreateInput / ClientEditInput payload from the mapped
 * form fields. Both Create and Edit accept the same set of top-level
 * fields per Jobber's schema convention, so we share the collector.
 */
function adfoin_jobber_collect_client_fields( $field_data, $posted_data ) {
    $payload = array();

    $map = array(
        'first_name'   => 'firstName',
        'last_name'    => 'lastName',
        'company_name' => 'companyName',
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $payload[ $api_key ] = $value;
    }

    if ( isset( $field_data['email'] ) ) {
        $email = adfoin_get_parsed_values( $field_data['email'], $posted_data );
        if ( '' !== $email && null !== $email ) {
            $payload['emails'] = array(
                array(
                    'address'     => $email,
                    'primary'     => true,
                    'description' => 'MAIN',
                ),
            );
        }
    }

    $phones = array();
    if ( isset( $field_data['mobile_phone'] ) ) {
        $value = adfoin_get_parsed_values( $field_data['mobile_phone'], $posted_data );
        if ( '' !== $value && null !== $value ) {
            $phones[] = array(
                'number'      => $value,
                'description' => 'MOBILE',
            );
        }
    }
    if ( isset( $field_data['phone'] ) ) {
        $value = adfoin_get_parsed_values( $field_data['phone'], $posted_data );
        if ( '' !== $value && null !== $value ) {
            $phones[] = array(
                'number'      => $value,
                'description' => 'MAIN',
                'primary'     => empty( $phones ),
            );
        }
    }
    if ( ! empty( $phones ) ) {
        $payload['phones'] = $phones;
    }

    $address_payload = adfoin_jobber_collect_address_fields( $field_data, $posted_data );

    if ( ! empty( $address_payload ) ) {
        $payload['billingAddress'] = $address_payload;
    }

    return $payload;
}

/**
 * Build an AddressAttributes payload from the mapped address fields. Used
 * as the client's billing address and the job's property address.
 */
function adfoin_jobber_collect_address_fields( $field_data, $posted_data ) {
    $address_fields = array(
        'address_line1' => 'street1',
        'address_line2' => 'street2',
        'city'          => 'city',
        'province'      => 'province',
        'postal_code'   => 'postalCode',
        'country'       => 'country',
    );

    $address_payload = array();

    foreach ( $address_fields as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $address_payload[ $api_key ] = $value;
    }

    return $address_payload;
}

/**
 * Build a JobCreateAttributes payload from the mapped form fields.
 *
 * propertyId and invoicing are added by adfoin_jobber_create_client_and_job().
 * Jobber jobs have no description field, so Job Description and Job
 * Instructions are joined into `instructions`. Start/end dates become
 * timeframe.startAt plus a duration in days.
 */
function adfoin_jobber_collect_job_attributes( $field_data, $posted_data ) {
    $payload = array();
    $values  = array();

    foreach ( array( 'job_title', 'job_description', 'job_instructions' ) as $field_key ) {
        $values[ $field_key ] = isset( $field_data[ $field_key ] )
            ? trim( (string) adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data ) )
            : '';
    }

    if ( '' !== $values['job_title'] ) {
        $payload['title'] = $values['job_title'];
    }

    $instructions = trim( implode( "\n\n", array_filter( array( $values['job_description'], $values['job_instructions'] ), 'strlen' ) ) );
    if ( '' !== $instructions ) {
        $payload['instructions'] = $instructions;
    }

    $start_date = adfoin_jobber_normalise_date( $field_data, $posted_data, 'job_start_at' );
    $end_date   = adfoin_jobber_normalise_date( $field_data, $posted_data, 'job_end_at' );

    if ( $start_date ) {
        $payload['timeframe'] = array( 'startAt' => $start_date );

        $days = $end_date ? (int) round( ( strtotime( $end_date ) - strtotime( $start_date ) ) / DAY_IN_SECONDS ) : 0;
        if ( $days > 0 ) {
            $payload['timeframe']['durationValue'] = $days;
            $payload['timeframe']['durationUnits'] = 'DAYS';
        }
    }

    return $payload;
}

/**
 * Convert a mapped date/time field to an ISO 8601 date (YYYY-MM-DD), the
 * format TimeframeAttributes.startAt expects. Returns '' when the field is
 * missing or unparseable.
 */
function adfoin_jobber_normalise_date( $field_data, $posted_data, $field_key ) {
    if ( ! isset( $field_data[ $field_key ] ) ) {
        return '';
    }

    $value = trim( (string) adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data ) );
    if ( '' === $value ) {
        return '';
    }

    if ( preg_match( '/^(\d{4}-\d{2}-\d{2})/', $value, $match ) ) {
        return $match[1];
    }

    $ts = strtotime( $value );

    return false === $ts ? '' : gmdate( 'Y-m-d', $ts );
}

function adfoin_jobber_extract_nested_value( $source, $path ) {
    if ( is_wp_error( $source ) ) {
        return '';
    }

    if ( is_array( $source ) && isset( $source[0] ) ) {
        $source = $source[0];
    }

    if ( is_array( $source ) || is_object( $source ) ) {
        foreach ( $path as $key ) {
            if ( is_array( $source ) && isset( $source[ $key ] ) ) {
                $source = $source[ $key ];
            } elseif ( is_object( $source ) && isset( $source->{$key} ) ) {
                $source = $source->{$key};
            } else {
                return '';
            }
        }
    }

    if ( is_array( $source ) || is_object( $source ) ) {
        return '';
    }

    return $source;
}

function adfoin_jobber_prepare_list_field( $value ) {
    if ( is_array( $value ) ) {
        $items = $value;
    } else {
        $items = explode( ',', (string) $value );
    }

    $items = array_map( 'trim', $items );
    $items = array_filter( $items, 'strlen' );

    return array_values( array_unique( $items ) );
}

if ( ! function_exists( 'adfoin_jobber_merge_recursive' ) ) :
function adfoin_jobber_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
            $base[ $key ] = adfoin_jobber_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }

    return $base;
}
endif;
