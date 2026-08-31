<?php

/**
 * OAuth 2.0 client for Zendesk Support, per Zendesk's documented flow
 * (https://support.zendesk.com/hc/en-us/articles/4408845965210). Coexists
 * with the legacy email+API-token accounts created before this was added —
 * `adfoin_zendesk_request()` below picks Bearer vs Basic auth per credential
 * record, and this class's `$platform_slug` is how
 * ADFOIN_Account_Manager::is_oauth_platform() routes modern-shaped
 * `adfoin_save_zendesk_credentials` submissions here instead of the old
 * bulk-array handler.
 *
 * Zendesk's token endpoint takes client_id/client_secret in the JSON body
 * (not an Authorization: Basic header, unlike most other OAuth2 platforms in
 * this codebase), and rotates both access and refresh tokens on every
 * refresh — request_token()/refresh_token() are overridden for the former;
 * the latter is why refresh_token() keeps the base class's per-credential
 * lock (same hazard Constant Contact has).
 */
class ADFOIN_Zendesk extends Advanced_Form_Integration_OAuth2 {

    // `read` is required (not just `tickets:read`) because ticket field
    // definitions (ticket_fields.json, used by pro/zendeskpro to list custom
    // fields) are account-configuration data, not ticket data — Zendesk
    // doesn't cover them under the granular tickets:* scope.
    const SCOPE = 'read tickets:write';

    protected $platform_slug = 'zendesk';

    protected $subdomain = '';
    protected $email     = '';
    protected $api_token = '';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_zendesk_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zendesk_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_zendesk_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function settings_view( $current_tab ) {
        if ( 'zendesk' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'subdomain',
                'label'         => __( 'Zendesk Subdomain', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => false,
                'show_in_table' => true,
                'placeholder'   => 'yourcompany',
            ),
            array(
                'name'          => 'client_id',
                'label'         => __( 'Identifier', 'advanced-form-integration' ),
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
        $instructions .= '<li>' . __( 'In Zendesk, go to Admin Center → Apps and integrations → APIs → OAuth Clients.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Add OAuth Client, give it a name, description and paste the Redirect URL below into the Redirect URLs field.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Important: set Client kind to <strong>Confidential</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Save, then copy the Identifier and Secret.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Enter your Zendesk subdomain (the "yourcompany" part of yourcompany.zendesk.com) plus the Identifier and Secret below, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';
        $instructions .= '<p class="description">' . __( 'Already using an API token? Existing token-based accounts keep working as-is — this is only needed for new accounts.', 'advanced-form-integration' ) . '</p>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Zendesk Support', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'zendesk',
            __( 'Zendesk Support', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zendesk', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Zendesk with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, and closes the popup.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'zendesk' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id || ! $this->set_credentials( $cred_id )
            || empty( $this->subdomain ) || empty( $this->client_id ) || empty( $this->client_secret ) ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'OAuth state invalid, expired, or credential incomplete. Please try again.', 'advanced-form-integration' ) );
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
                $message = $body['error_description'] ?? ( $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' ) );
            }
        } elseif ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        } else {
            $body    = json_decode( wp_remote_retrieve_body( $response ), true );
            $message = is_array( $body ) ? ( $body['error_description'] ?? ( $body['error'] ?? $message ) ) : $message;
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    public function ajax_get_credentials() {
        adfoin_verify_nonce();
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function ajax_save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'zendesk';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                unset( $credentials[ $index ] );
                adfoin_save_credentials( $platform, array_values( $credentials ) );
                wp_send_json_success( array( 'message' => __( 'Deleted', 'advanced-form-integration' ) ) );
            }
            wp_send_json_error( array( 'message' => __( 'Invalid index', 'advanced-form-integration' ) ) );
        }

        $id            = isset( $_POST['id'] )            ? sanitize_text_field( wp_unslash( $_POST['id'] ) )            : '';
        $title         = isset( $_POST['title'] )         ? sanitize_text_field( wp_unslash( $_POST['title'] ) )         : '';
        $subdomain     = isset( $_POST['subdomain'] )     ? sanitize_text_field( wp_unslash( $_POST['subdomain'] ) )     : '';
        $client_id     = isset( $_POST['client_id'] )     ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) )     : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

        // Tolerate a pasted full host ("https://foo.zendesk.com/") instead of the bare subdomain.
        $subdomain = preg_replace( '#^https?://#i', '', $subdomain );
        $subdomain = preg_replace( '#\.zendesk\.com.*$#i', '', $subdomain );
        $subdomain = strtolower( trim( $subdomain, "/ \t\n\r\0\x0B" ) );

        if ( '' === $subdomain ) {
            wp_send_json_error( array( 'message' => __( 'Zendesk subdomain is required.', 'advanced-form-integration' ) ) );
        }

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

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
        }

        $has_legacy = $existing && ! empty( $existing['email'] ) && ! empty( $existing['apiToken'] );

        if ( ( '' === $client_id || '' === $client_secret ) && ! $has_legacy ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required to connect via OAuth.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'subdomain'     => $subdomain,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        );

        // Preserve legacy API-token fields untouched so existing accounts keep
        // working as-is until (unless) this record completes an OAuth authorization.
        if ( $existing ) {
            if ( ! empty( $existing['email'] ) ) {
                $new_data['email'] = $existing['email'];
            }
            if ( ! empty( $existing['apiToken'] ) ) {
                $new_data['apiToken'] = $existing['apiToken'];
            }
        }

        // Whenever an Identifier + Secret are present, always send the user
        // through the authorize step — including when editing an already-
        // connected record with unchanged credentials. This used to be
        // skipped for "same app, already connected" as an optimization, but
        // that made the "Update & Authorize" button silently do nothing when
        // an admin needed to re-run authorization to pick up a scope change
        // (e.g. after this app started requesting a wider scope) — clicking
        // it just re-saved the old token with no way to force a refresh.
        $needs_auth = ( '' !== $client_id && '' !== $client_secret );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $cred  = array_merge( $cred, $new_data );
                $found = true;
                break;
            }
        }
        unset( $cred );

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        if ( ! $needs_auth ) {
            wp_send_json_success( array( 'message' => __( 'Account saved successfully', 'advanced-form-integration' ) ) );
        }

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => self::SCOPE,
            'state'         => self::issue_oauth_state( 'zendesk', $id ),
        ), sprintf( 'https://%s.zendesk.com/oauth/authorizations/new', $subdomain ) );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( '' === $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id', 'advanced-form-integration' ) ) );
        }

        $response = adfoin_zendesk_request( 'users/me.json', 'GET', array(), array(), $cred_id );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );

        if ( $code < 200 || $code >= 300 ) {
            $body  = json_decode( wp_remote_retrieve_body( $response ), true );
            $error = is_array( $body ) ? ( $body['error'] ?? '' ) : '';
            $msg   = is_array( $error ) ? ( $error['title'] ?? __( 'Request failed', 'advanced-form-integration' ) ) : ( $error ?: sprintf( 'HTTP %d', $code ) );
            wp_send_json_error( array( 'message' => $msg ) );
        }

        wp_send_json_success( array( 'message' => __( 'Connection OK', 'advanced-form-integration' ) ) );
    }

    /**
     * Public passthrough to the inherited (protected) Bearer-auth request
     * helper — proactive/reactive token refresh, 429 backoff, credential
     * last-used stamping all come along for free.
     */
    public function api_request( $endpoint, $method = 'GET', $data = array() ) {
        $url = sprintf( 'https://%s.zendesk.com/api/v2/', $this->subdomain ) . ltrim( $endpoint, '/' );

        $args = array(
            'method'  => $method,
            'headers' => array( 'Content-Type' => 'application/json' ),
        );

        if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        return $this->remote_request( $url, $args );
    }

    public function set_credentials( $cred_id ) {
        return $this->set_credentials_from_id( $cred_id );
    }

    /**
     * Hydrates the generic OAuth fields via the parent, then layers on
     * Zendesk's own fields (subdomain, plus the legacy email/apiToken pair)
     * from the same stored record.
     */
    protected function set_credentials_from_id( $cred_id ): bool {
        $found = parent::set_credentials_from_id( $cred_id );

        if ( ! $found ) {
            return false;
        }

        foreach ( adfoin_read_credentials( 'zendesk' ) as $cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $cred_id ) {
                $this->subdomain = isset( $cred['subdomain'] ) ? $cred['subdomain'] : '';
                $this->email     = isset( $cred['email'] ) ? $cred['email'] : '';
                $this->api_token = isset( $cred['apiToken'] ) ? $cred['apiToken'] : '';
                break;
            }
        }

        return true;
    }

    /**
     * The base class only counts an account "connected" when both
     * access_token and refresh_token are present. Legacy API-token accounts
     * have neither but are perfectly functional — flag them connected too so
     * the Manage Accounts table doesn't show a false "Not Connected".
     */
    protected function safe_credentials_list( $platform = null ): array {
        $list = parent::safe_credentials_list( $platform );

        foreach ( $list as &$row ) {
            if ( empty( $row['connected'] ) && ! empty( $row['subdomain'] ) && ! empty( $row['email'] ) && ! empty( $row['apiToken'] ) ) {
                $row['connected'] = true;
            }
        }
        unset( $row );

        return $list;
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zendesk' );
    }

    /**
     * Zendesk's token endpoint takes client_id/client_secret in the JSON
     * body rather than an Authorization: Basic header (confirmed against
     * Zendesk's own OAuth docs) — the base class default does the opposite,
     * so this is fully overridden rather than calling parent::.
     */
    protected function request_token( $authorization_code ) {
        $endpoint = sprintf( 'https://%s.zendesk.com/oauth/tokens', $this->subdomain );

        $response = wp_remote_post( esc_url_raw( $endpoint ), array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'grant_type'    => 'authorization_code',
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => self::SCOPE,
            ) ),
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $response_code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            $this->apply_token_response( $response_body );
        }

        $this->save_data();

        return $response;
    }

    /**
     * Zendesk rotates BOTH the access and refresh token on every refresh
     * ("invalidating the previous ones" per their docs) — same hazard
     * Constant Contact has, so this keeps the base class's per-credential
     * lock (acquire/wait/reload/release) rather than reinventing it, only
     * swapping the actual token-endpoint call for Zendesk's body-based
     * client auth + per-subdomain URL.
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

        $endpoint = sprintf( 'https://%s.zendesk.com/oauth/tokens', $this->subdomain );

        $response = wp_remote_post( esc_url_raw( $endpoint ), array(
            'timeout' => 30,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ) ),
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $response_code ) {
            error_log( sprintf( 'Zendesk OAuth2 refresh failed with 401 for cred_id: %s', (string) $this->cred_id ) );

            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $response_code ) {
            $this->apply_token_response( $response_body );
        } else {
            error_log( sprintf( 'Zendesk OAuth2 refresh failed with code %d for cred_id: %s', $response_code, (string) $this->cred_id ) );
        }

        $this->save_data();

        if ( $lock_key ) {
            $this->release_token_refresh_lock( $lock_key );
        }

        return $response;
    }
}

add_filter( 'adfoin_action_providers', 'adfoin_zendesk_actions', 10, 1 );

function adfoin_zendesk_actions( $actions ) {
    $actions['zendesk'] = array(
        'title' => __( 'Zendesk Support', 'advanced-form-integration' ),
        'tasks' => array(
            'create_ticket' => __( 'Create Ticket', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_zendesk_settings_tab', 10, 1 );

function adfoin_zendesk_settings_tab( $providers ) {
    $providers['zendesk'] = __( 'Zendesk Support', 'advanced-form-integration' );

    return $providers;
}

function adfoin_zendesk_credentials_list() {
    $html        = '';
    $credentials = adfoin_read_credentials( 'zendesk' );

    foreach ( $credentials as $option ) {
        $html .= '<option value="' . esc_attr( $option['id'] ) . '">' . esc_html( $option['title'] ) . '</option>';
    }

    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_zendesk_action_fields', 10, 1 );

function adfoin_zendesk_action_fields() {
    ?>
    <script type="text/template" id="zendesk-action-template">
        <table class="form-table">
            <tr v-if="action.task == 'create_ticket'">
                <th scope="row"><?php esc_html_e( 'Ticket Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="afi-spinner" :class="{'is-active': false}"></div>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'create_ticket'">
                <td scope="row-title">
                    <label for="zendesk-credential"><?php esc_html_e( 'Zendesk Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select id="zendesk-credential" name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_zendesk_credentials_list(); ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zendesk' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>
            <editable-field
                v-for="field in fields"
                :key="field.value"
                :field="field"
                :trigger="trigger"
                :action="action"
                :fielddata="fielddata">
            </editable-field>

            <?php adfoin_pro_feature_notice( 'create_ticket', 'Zendesk [PRO]', 'additional fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_job_queue', 'adfoin_zendesk_job_queue', 10, 1 );

function adfoin_zendesk_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'zendesk' || ( $data['task'] ?? '' ) !== 'create_ticket' ) {
        return;
    }

    adfoin_zendesk_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_zendesk_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( empty( $cred_id ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'zendesk', $cred_id );
    $subdomain   = $credentials['subdomain'] ?? '';
    $has_oauth   = ! empty( $credentials['access_token'] );
    $has_legacy  = ! empty( $credentials['email'] ) && ! empty( $credentials['apiToken'] );

    if ( empty( $subdomain ) || ( ! $has_oauth && ! $has_legacy ) ) {
        return;
    }

    $ticket = array();

    $subject = adfoin_get_parsed_values( $field_data['ticket_subject'] ?? '', $posted_data );
    if ( ! $subject ) {
        $subject = __( 'New Ticket', 'advanced-form-integration' );
    }
    $ticket['subject'] = $subject;

    $comment       = adfoin_get_parsed_values( $field_data['ticket_comment'] ?? '', $posted_data );
    $comment_value = $comment !== '' ? $comment : $subject;
    $ticket['comment'] = array(
        'body'   => $comment_value,
        'public' => true,
    );

    $requester_email = adfoin_get_parsed_values( $field_data['requester_email'] ?? '', $posted_data );
    $requester_name  = adfoin_get_parsed_values( $field_data['requester_name'] ?? '', $posted_data );
    if ( $requester_email ) {
        $ticket['requester'] = array(
            'email' => $requester_email,
        );

        if ( $requester_name ) {
            $ticket['requester']['name'] = $requester_name;
        }
    }

    $priority = adfoin_get_parsed_values( $field_data['ticket_priority'] ?? '', $posted_data );
    if ( $priority ) {
        $ticket['priority'] = strtolower( $priority );
    }

    $status = adfoin_get_parsed_values( $field_data['ticket_status'] ?? '', $posted_data );
    if ( $status ) {
        $ticket['status'] = strtolower( $status );
    }

    if ( empty( $ticket ) ) {
        return;
    }

    adfoin_zendesk_request( 'tickets.json', 'POST', array( 'ticket' => $ticket ), $record, $cred_id );
}
/**
 * Sends a Zendesk API request, authenticating with OAuth (Bearer) when the
 * credential record has an access_token, falling back to the legacy
 * email+API-token (Basic) scheme when it doesn't — so accounts created
 * before the OAuth migration keep working unattended. Same signature/return
 * shape as before this migration; pro/zendeskpro/zendeskpro.php calls this
 * directly and needs no changes on its calling side.
 */
function adfoin_zendesk_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'zendesk', $cred_id );
    $subdomain   = $credentials['subdomain'] ?? '';

    if ( empty( $subdomain ) ) {
        return new WP_Error( 'zendesk_credentials_missing', __( 'Zendesk credentials are incomplete.', 'advanced-form-integration' ) );
    }

    $base_url = sprintf( 'https://%s.zendesk.com/api/v2/', $subdomain );
    $url      = $base_url . ltrim( $endpoint, '/' );

    if ( ! empty( $credentials['access_token'] ) ) {
        $zendesk = ADFOIN_Zendesk::get_instance();
        $zendesk->set_credentials( $cred_id );
        $response = $zendesk->api_request( $endpoint, $method, $data );

        // Rebuilt (not the real request args, which live inside the OAuth
        // client) purely for a redacted log entry — the real Authorization
        // header never touches the log table.
        $args = array(
            'method'  => $method,
            'headers' => array( 'Authorization' => 'Bearer [redacted]' ),
        );
        if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }
    } elseif ( ! empty( $credentials['email'] ) && ! empty( $credentials['apiToken'] ) ) {
        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $credentials['email'] . '/token:' . $credentials['apiToken'] ),
            ),
        );

        if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        // Redact for the log entry only — the real request already went out above.
        $args['headers']['Authorization'] = 'Basic [redacted]';
    } else {
        return new WP_Error( 'zendesk_credentials_missing', __( 'Zendesk credentials are incomplete.', 'advanced-form-integration' ) );
    }

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

ADFOIN_Zendesk::get_instance();
