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
        $instructions .= '<li>' . sprintf( __( 'Create an app at %s and add an Authorization Code Grant.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://admindemo.docusign.com/api-integrator-key">DocuSign Apps and Keys</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into the app\'s Redirect URIs.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy the Integration Key and Secret Key into the Add Account form here, choose Demo or Production to match your DocuSign account, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
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
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=docusign' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'send_envelope', 'DocuSign [PRO]', 'prefilled tab values' ); ?>
            </table>
        </script>
        <?php
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
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'docusign' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
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

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'scope'         => 'signature',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'state'         => self::issue_oauth_state( 'docusign', $id ),
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

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( $this->get_account_host() . '/oauth/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => array(
                'grant_type' => 'authorization_code',
                'code'       => $authorization_code,
            ),
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

        if ( 401 === $code ) {
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
        static $refreshed = false;

        $request            = wp_parse_args( $request, array() );
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

ADFOIN_DocuSign::get_instance();

add_action( 'wp_ajax_adfoin_get_docusign_fields', 'adfoin_get_docusign_fields' );
function adfoin_get_docusign_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'templateId',    'value' => 'Template ID', 'description' => 'From DocuSign Templates', 'required' => true ),
        array( 'key' => 'recipientEmail','value' => 'Recipient Email', 'required' => true ),
        array( 'key' => 'recipientName', 'value' => 'Recipient Name', 'required' => true ),
        array( 'key' => 'roleName',      'value' => 'Template Role Name', 'description' => 'Must match a role defined on the template, e.g. "Signer"' ),
        array( 'key' => 'emailSubject',  'value' => 'Email Subject' ),
    );
    wp_send_json_success( $fields );
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
