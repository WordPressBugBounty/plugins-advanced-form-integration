<?php

/**
 * DocuSign — Send Envelope From Template via POST
 * {base_uri}/restapi/v2.1/accounts/{accountId}/envelopes.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow (Authorization
 * Code Grant + refresh_token — the practical choice for "connect once in
 * WP admin, then works unattended," vs. JWT Grant which needs an RSA
 * keypair and per-integration consent management).
 *
 * Confirmed via developers.docusign.com: token endpoint is
 * account-d.docusign.com/oauth/token (demo) or account.docusign.com/oauth/token
 * (production); after token exchange, GET .../oauth/userinfo returns the
 * `accounts[]` array with each account's `account_id` and `base_uri` — the
 * base_uri is account-specific and must be discovered this way, not
 * assumed. All subsequent API calls go to {base_uri}/restapi/v2.1/...
 *
 * @link https://developers.docusign.com/platform/auth/authcode/
 * @link https://developers.docusign.com/docs/esign-rest-api/reference/envelopes/envelopes/create/
 */

require_once dirname( __DIR__, 2 ) . '/includes/esign-template-picker.php';

class ADFOIN_DocuSign extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'docusign';

    protected $account_id = '';
    protected $base_uri   = '';
    protected $environment = 'demo';

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_docusign_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_docusign_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_docusign_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['docusign'] = array(
            'title' => __( 'DocuSign', 'advanced-form-integration' ),
            'tasks' => array( 'send_envelope' => __( 'Send Envelope From Template', 'advanced-form-integration' ) ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['docusign'] = __( 'DocuSign', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'docusign' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'Integration Key', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'Secret Key', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'environment',
                'label'         => __( 'Environment', 'advanced-form-integration' ),
                'type'          => 'select',
                'required'      => false,
                'show_in_table' => false,
                'options'       => array(
                    'demo'       => 'Demo / Sandbox (account-d.docusign.com)',
                    'production' => 'Production (account.docusign.com)',
                ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Open the %s and click Add to create an integration.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://apps.docusign.com/dev-console/integrations">DocuSign Developer Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Integration type: Private custom integration.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Authentication: answer Yes to "able to securely store a client secret", keep Authorization Code Grant (PKCE on or off both work), then under Client secret click Add and copy the secret. It is shown only once.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Under Redirect URI click Add, paste the URL below exactly, then click Save.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Click Add Account here, paste the Client ID (Integration Key) and Client secret, choose Demo for a developer account or Production for a live account, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect DocuSign', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'docusign', __( 'DocuSign', 'advanced-form-integration' ), $fields, $instructions, $config );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="docusign-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'send_envelope'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'send_envelope'">
                    <td scope="row-title"><label><?php esc_attr_e( 'DocuSign Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=docusign' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <?php adfoin_esign_template_picker_rows( 'send_envelope' ); ?>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'send_envelope', 'DocuSign [PRO]', 'prefilled tab values' ); ?>
            </table>
        </script>
        <?php
        adfoin_esign_template_picker_script();
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/docusign', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();

        // WP::parse_request() unsets $_GET['error'] (it treats it as its own
        // 404 flag), so the OAuth error param never reaches the REST request.
        // Read it from the raw query string instead.
        if ( empty( $params['error'] ) && ! empty( $_SERVER['QUERY_STRING'] ) ) {
            parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ), $raw_query );

            if ( ! empty( $raw_query['error'] ) ) {
                $params['error'] = $raw_query['error'];
            }
        }

        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context       = self::consume_oauth_state( $state, 'docusign' );
        $cred_id       = $context ? $context['cred_id'] : '';
        $code_verifier = $context && isset( $context['extra']['code_verifier'] ) ? $context['extra']['code_verifier'] : '';

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        // DocuSign reports a refused or misconfigured authorization by
        // redirecting with error / error_description instead of a code.
        if ( ! empty( $params['error'] ) ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, self::describe_error( $params ) );
            exit;
        }

        if ( ! $code || ! $cred_id ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'OAuth state invalid or expired. Please try again.', 'advanced-form-integration' ) );
            exit;
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'docusign' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
                $this->environment   = $entry['environment']   ?? 'demo';
                $found = true;
                break;
            }
        }

        if ( ! $found || ! $this->client_id || ! $this->client_secret ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'Credential not found or incomplete.', 'advanced-form-integration' ) );
            exit;
        }

        $response = $this->request_token( $code, $code_verifier );

        $success = false;
        $body    = is_wp_error( $response ) ? array() : json_decode( wp_remote_retrieve_body( $response ), true );

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
        } elseif ( empty( $body['access_token'] ) ) {
            $message = self::describe_error( is_array( $body ) ? $body : array(), __( 'Token exchange failed.', 'advanced-form-integration' ) );
        } elseif ( empty( $this->account_id ) || empty( $this->base_uri ) ) {
            $message = __( 'Signed in, but DocuSign did not return an account for this user. Check that the user has access to a DocuSign account in the selected environment (Demo or Production), then try again.', 'advanced-form-integration' );
        } else {
            $success = true;
            $message = __( 'Connected successfully!', 'advanced-form-integration' );
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

        $platform    = 'docusign';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) $credentials = array();

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
        $environment   = isset( $_POST['environment'] )   ? sanitize_text_field( wp_unslash( $_POST['environment'] ) )   : 'demo';

        if ( empty( $id ) ) $id = wp_generate_uuid4();

        $existing = null;
        foreach ( $credentials as $cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) { $existing = $cred; break; }
        }

        if ( $existing ) {
            if ( '' === $client_id && ! empty( $existing['client_id'] ) )         $client_id = $existing['client_id'];
            if ( '' === $client_secret && ! empty( $existing['client_secret'] ) ) $client_secret = $existing['client_secret'];
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Integration Key and Secret Key are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'environment'   => $environment ?: 'demo',
            'access_token'  => '',
            'refresh_token' => '',
            'account_id'    => '',
            'base_uri'      => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id ) && ( ( $cred['client_secret'] ?? '' ) === $client_secret );
                if ( $same ) {
                    $new_data['access_token']  = $cred['access_token']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? '';
                    $new_data['account_id']    = $cred['account_id']    ?? '';
                    $new_data['base_uri']      = $cred['base_uri']      ?? '';
                }
                $cred  = $new_data;
                $found = true;
                break;
            }
        }
        unset( $cred );

        if ( ! $found ) $credentials[] = $new_data;

        adfoin_save_credentials( $platform, $credentials );

        // PKCE (S256). Always sent: DocuSign accepts it whether or not the
        // integration has "Require PKCE" switched on, and that switch is on
        // in many new Developer Console integrations.
        $code_verifier = self::base64url( random_bytes( 32 ) );

        $auth_url = add_query_arg( array(
            'response_type'         => 'code',
            'scope'                 => 'signature',
            'client_id'             => $client_id,
            'redirect_uri'          => $this->get_redirect_uri(),
            'state'                 => self::issue_oauth_state( 'docusign', $id, array( 'code_verifier' => $code_verifier ) ),
            'code_challenge'        => self::base64url( hash( 'sha256', $code_verifier, true ) ),
            'code_challenge_method' => 'S256',
        ), $this->get_account_host( $environment ) . '/oauth/auth' );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->docusign_request( 'templates' );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/docusign' );
    }

    protected function get_account_host( $environment = '' ) {
        $environment = $environment ?: $this->environment;
        return 'production' === $environment ? 'https://account.docusign.com' : 'https://account-d.docusign.com';
    }

    protected static function base64url( $bytes ) {
        return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
    }

    /**
     * Readable message from a DocuSign OAuth error (redirect params or token
     * response body), with a hint for the common setup mistakes.
     */
    protected static function describe_error( array $error, $fallback = '' ) {
        $code        = isset( $error['error'] ) ? sanitize_text_field( $error['error'] ) : '';
        $description = isset( $error['error_description'] ) ? sanitize_text_field( $error['error_description'] ) : '';

        if ( '' === $code && '' === $description ) {
            return $fallback ? $fallback : __( 'Unknown error.', 'advanced-form-integration' );
        }

        $hints = array(
            'invalid_client'     => __( 'Check the Client ID and Client secret, and that Demo / Production matches where the integration was created.', 'advanced-form-integration' ),
            'unauthorized_client'=> __( 'Check the Client ID and Client secret, and that Demo / Production matches where the integration was created.', 'advanced-form-integration' ),
            'invalid_grant'      => __( 'The sign-in expired or was already used. Click Save & Authorize again.', 'advanced-form-integration' ),
            'access_denied'      => __( 'Access was not granted in DocuSign.', 'advanced-form-integration' ),
        );

        $message = trim( $code . ( $description ? ': ' . $description : '' ) );

        if ( isset( $hints[ $code ] ) ) {
            $message .= ' ' . $hints[ $code ];
        }

        return $message;
    }

    protected function request_token( $authorization_code, $code_verifier = '' ) {
        $body = array(
            'grant_type' => 'authorization_code',
            'code'       => $authorization_code,
        );

        if ( $code_verifier ) {
            $body['code_verifier'] = $code_verifier;
        }

        $response = wp_remote_post( $this->get_account_host() . '/oauth/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => $body,
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
            $this->fetch_user_info();
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( $this->get_account_host() . '/oauth/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
            ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // DocuSign answers an expired / revoked refresh token with 400 invalid_grant.
        if ( 401 === $code || ( 400 === $code && isset( $body['error'] ) && 'invalid_grant' === $body['error'] ) ) {
            $this->access_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
        }

        $this->save_data();

        return $response;
    }

    /**
     * GET .../oauth/userinfo — the only way to learn the account-specific
     * base_uri; DocuSign does not expose it any other way.
     */
    protected function fetch_user_info() {
        $response = wp_remote_get( $this->get_account_host() . '/oauth/userinfo', array(
            'timeout' => 30,
            'headers' => array( 'Authorization' => 'Bearer ' . $this->access_token ),
        ) );

        if ( is_wp_error( $response ) ) {
            return;
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['accounts'] ) || ! is_array( $body['accounts'] ) ) {
            return;
        }

        $account = $body['accounts'][0];
        foreach ( $body['accounts'] as $acc ) {
            if ( ! empty( $acc['is_default'] ) ) { $account = $acc; break; }
        }

        $this->account_id = $account['account_id'] ?? '';
        $this->base_uri    = $account['base_uri']   ?? '';
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential( array(
                'environment' => $this->environment,
                'account_id'  => $this->account_id,
                'base_uri'    => $this->base_uri,
            ) );
        }
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
        $this->environment   = $credentials['environment']   ?? 'demo';
        $this->account_id    = $credentials['account_id']    ?? '';
        $this->base_uri      = $credentials['base_uri']      ?? '';

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) return array();
        foreach ( adfoin_read_credentials( 'docusign' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) return $single;
        }
        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'docusign' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function docusign_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        if ( empty( $this->base_uri ) || empty( $this->account_id ) ) {
            return new WP_Error( 'docusign_no_account', __( 'DocuSign account info not available — reconnect this account.', 'advanced-form-integration' ) );
        }

        $url    = rtrim( $this->base_uri, '/' ) . '/restapi/v2.1/accounts/' . rawurlencode( $this->account_id ) . '/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array( 'Content-Type' => 'application/json' ),
        );
        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        return $this->remote_request( $url, $args, $record );
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        // Shared refresh-and-retry: refreshes once per call on 401 and
        // rebuilds the Authorization header from the new token.
        $response = $this->request_with_token_refresh( $url, $request, null, $sent_args );

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $sent_args, $record );
        }

        return $response;
    }
}

ADFOIN_DocuSign::get_instance();

add_action( 'wp_ajax_adfoin_get_docusign_fields', 'adfoin_get_docusign_fields' );
function adfoin_get_docusign_fields() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_docusign_base_fields() );
}

/**
 * Mapping fields shared by the free and Pro "Send Envelope From Template"
 * actions. Template and role come from the picker rows (templateId / roleName).
 */
function adfoin_docusign_base_fields() {
    return array(
        array( 'key' => 'recipientEmail', 'value' => __( 'Recipient Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'recipientName',  'value' => __( 'Recipient Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'emailSubject',   'value' => __( 'Email Subject', 'advanced-form-integration' ) ),
    );
}

function adfoin_docusign_is_template_id( $value ) {
    return is_string( $value ) && (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim( $value ) );
}

/**
 * Connect the shared client to the credential posted with an AJAX request.
 *
 * @return ADFOIN_DocuSign|WP_Error
 */
function adfoin_docusign_client_from_request() {
    $cred_id  = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $docusign = ADFOIN_DocuSign::get_instance();

    if ( ! $cred_id || ! $docusign->set_credentials( $cred_id ) ) {
        return new WP_Error( 'docusign_no_account', __( 'Select a connected DocuSign account.', 'advanced-form-integration' ) );
    }

    return $docusign;
}

/**
 * Roles on a template, each with the labels of its text tabs (the tabs that
 * Pro can prefill).
 *
 * @return array|WP_Error List of { roleName, type, textTabs[] }.
 */
function adfoin_docusign_get_template_roles( $docusign, $template_id ) {
    $response = $docusign->docusign_request( 'templates/' . rawurlencode( $template_id ) . '/recipients?include_tabs=true' );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $body ) ) {
        return new WP_Error( 'docusign_template', isset( $body['message'] ) ? $body['message'] : __( 'Could not read the template.', 'advanced-form-integration' ) );
    }

    $roles = array();

    foreach ( array( 'signers', 'inPersonSigners', 'carbonCopies', 'certifiedDeliveries', 'agents', 'editors', 'intermediaries' ) as $type ) {
        foreach ( isset( $body[ $type ] ) && is_array( $body[ $type ] ) ? $body[ $type ] : array() as $recipient ) {
            if ( empty( $recipient['roleName'] ) ) {
                continue;
            }

            $labels = array();

            foreach ( isset( $recipient['tabs']['textTabs'] ) ? (array) $recipient['tabs']['textTabs'] : array() as $tab ) {
                if ( ! empty( $tab['tabLabel'] ) ) {
                    $labels[] = $tab['tabLabel'];
                }
            }

            $roles[] = array(
                'roleName' => $recipient['roleName'],
                'type'     => $type,
                'textTabs' => array_values( array_unique( $labels ) ),
            );
        }
    }

    return $roles;
}

add_action( 'wp_ajax_adfoin_get_docusign_templates', 'adfoin_get_docusign_templates' );
function adfoin_get_docusign_templates() {
    adfoin_verify_nonce();

    $docusign = adfoin_docusign_client_from_request();

    if ( is_wp_error( $docusign ) ) {
        wp_send_json_error( array( 'message' => $docusign->get_error_message() ) );
    }

    $templates = array();
    $count     = 100;

    // The list is paged; stop after 2,000 templates.
    for ( $start = 0; $start < 2000; $start += $count ) {
        $response = $docusign->docusign_request( 'templates?count=' . $count . '&start_position=' . $start . '&order_by=name' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error( array( 'message' => isset( $body['message'] ) ? $body['message'] : __( 'Could not load templates.', 'advanced-form-integration' ) ) );
        }

        $page = isset( $body['envelopeTemplates'] ) && is_array( $body['envelopeTemplates'] ) ? $body['envelopeTemplates'] : array();

        foreach ( $page as $template ) {
            $templates[] = array( 'id' => $template['templateId'], 'name' => $template['name'] );
        }

        if ( count( $page ) < $count ) {
            break;
        }
    }

    usort( $templates, function ( $a, $b ) {
        return strcasecmp( $a['name'], $b['name'] );
    } );

    wp_send_json_success( $templates );
}

add_action( 'wp_ajax_adfoin_get_docusign_template_roles', 'adfoin_get_docusign_template_roles_ajax' );
function adfoin_get_docusign_template_roles_ajax() {
    adfoin_verify_nonce();

    $template_id = isset( $_POST['templateId'] ) ? sanitize_text_field( wp_unslash( $_POST['templateId'] ) ) : '';

    if ( ! adfoin_docusign_is_template_id( $template_id ) ) {
        wp_send_json_success( array() );
    }

    $docusign = adfoin_docusign_client_from_request();

    if ( is_wp_error( $docusign ) ) {
        wp_send_json_error( array( 'message' => $docusign->get_error_message() ) );
    }

    $roles = adfoin_docusign_get_template_roles( $docusign, $template_id );

    if ( is_wp_error( $roles ) ) {
        wp_send_json_error( array( 'message' => $roles->get_error_message() ) );
    }

    wp_send_json_success( $roles );
}

function adfoin_docusign_build_envelope( $fields ) {
    $role = array(
        'email'    => $fields['recipientEmail'],
        'name'     => $fields['recipientName'],
        'roleName' => ! empty( $fields['roleName'] ) ? $fields['roleName'] : 'Signer',
    );

    return array(
        'templateId'     => $fields['templateId'],
        'templateRoles'  => array( $role ),
        'status'         => 'sent',
        'emailSubject'   => ! empty( $fields['emailSubject'] ) ? $fields['emailSubject'] : 'Please sign this document',
    );
}

add_action( 'adfoin_docusign_job_queue', 'adfoin_docusign_job_queue', 10, 1 );
function adfoin_docusign_job_queue( $data ) {
    adfoin_docusign_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_docusign_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    if ( ! $cred_id ) return;

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'send_envelope' ) return;
    if ( empty( $fields['templateId'] ) || empty( $fields['recipientEmail'] ) ) return;

    $docusign = ADFOIN_DocuSign::get_instance();
    if ( ! $docusign->set_credentials( $cred_id ) ) {
        return;
    }

    $docusign->docusign_request( 'envelopes', 'POST', adfoin_docusign_build_envelope( $fields ), $record );
}
