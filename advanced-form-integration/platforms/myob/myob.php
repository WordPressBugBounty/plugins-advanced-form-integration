<?php

/**
 * MYOB AccountRight Live — Create Customer via
 * POST /accountright/{company_file_id}/Contact/Customer.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Authorization: Bearer {access_token} plus the developer-key header
 * (x-myobapi-key) and API version pin (x-myobapi-version: v2). Tokens are
 * refreshed automatically on 401.
 *
 * MYOB scopes every Contact/Invoice/Sale endpoint under a company file
 * (Guid). We discover the user's first file by calling
 * GET https://api.myob.com/accountright/ right after the OAuth handshake
 * and cache its Id on the credential record so subsequent calls don't have
 * to re-discover it. If it ever goes missing (e.g. legacy credential), the
 * request method performs a just-in-time re-discovery.
 *
 * Many MYOB AccountRight company files require an additional file-level
 * credential (the "Sign-in", a.k.a. file user account). For those we send
 * x-myobapi-cftoken: base64(cf_username:cf_password). Pure online files
 * sometimes don't need it — we therefore expose both fields as optional.
 *
 * @link https://developer.myob.com/api/accountright/
 */

class ADFOIN_MYOB extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'myob';

    const authorization_endpoint = 'https://secure.myob.com/oauth2/account/authorize/';
    const token_endpoint         = 'https://secure.myob.com/oauth2/v1/authorize/';
    const refresh_token_endpoint = 'https://secure.myob.com/oauth2/v1/authorize/';
    const api_base               = 'https://api.myob.com/accountright/';
    const oauth_scopes           = 'CompanyFile';

    /** @var string MYOB company file Guid — cached on the credential record. */
    public $company_file_id = '';

    /** @var string Optional file-level "Sign-in" username (for x-myobapi-cftoken). */
    public $cf_username = '';

    /** @var string Optional file-level "Sign-in" password (for x-myobapi-cftoken). */
    public $cf_password = '';

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

        add_action( 'wp_ajax_adfoin_get_myob_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_myob_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_myob_connection', array( $this, 'ajax_test_connection' ), 10, 0 );

        add_action( 'wp_ajax_adfoin_get_myob_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['myob'] = array(
            'title' => __( 'MYOB AccountRight', 'advanced-form-integration' ),
            'tasks' => array(
                'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['myob'] = __( 'MYOB AccountRight', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'myob' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'API Key (Client ID)', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'API Secret (Client Secret)', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => true,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'cf_username',
                'label'         => __( 'Company File Username (optional)', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => false,
                'description'   => __( 'Username for your MYOB company file Sign-in. Only required for files that have a per-file user/password (most AccountRight Live files do). Leave blank for online-only files with no file-level credential.', 'advanced-form-integration' ),
            ),
            array(
                'name'          => 'cf_password',
                'label'         => __( 'Company File Password (optional)', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => false,
                'mask'          => true,
                'show_in_table' => false,
                'placeholder'   => __( 'Leave blank to keep current', 'advanced-form-integration' ),
                'description'   => __( 'Password matching the Company File Username above. Sent as x-myobapi-cftoken (base64). Leave blank for online-only files with no file-level credential.', 'advanced-form-integration' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to %s and register a new app (App type: <strong>Desktop</strong> works for server-side flows too).', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://my.myob.com.au/Bd/RegisteredDevAccount.aspx">MYOB Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Under <strong>Redirect URL</strong>, add the URL below:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'After registration, copy the <strong>Key</strong> (this is your Client ID) and <strong>Secret</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste them into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'If your company file is protected by a Sign-in (username/password), enter those too — they\'re sent as <code>x-myobapi-cftoken</code> for file-level access.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup and discovers your first company file automatically.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect MYOB AccountRight', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'myob',
            __( 'MYOB AccountRight', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="myob-action-template">
            <table class="form-table" v-if="action.task == 'create_customer'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'MYOB Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=myob' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Contact Type', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[contactType]" v-model="fielddata.contactType">
                            <option value="individual"><?php esc_html_e( 'Individual', 'advanced-form-integration' ); ?></option>
                            <option value="company"><?php esc_html_e( 'Company', 'advanced-form-integration' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'MYOB stores customers as either an Individual (IsIndividual=true; requires First Name + Last Name) or a Company (IsIndividual=false; requires Company Name).', 'advanced-form-integration' ); ?></p>
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
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        wp_send_json_success( adfoin_myob_fields() );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/myob', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by MYOB with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, discovers the first
     * company file, and closes the popup with a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'myob' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'myob' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
                $this->cf_username   = $entry['cf_username']   ?? '';
                $this->cf_password   = $entry['cf_password']   ?? '';
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

        $platform    = 'myob';
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
        $cf_username   = isset( $_POST['cf_username'] )   ? sanitize_text_field( wp_unslash( $_POST['cf_username'] ) )   : '';
        $cf_password   = isset( $_POST['cf_password'] )   ? sanitize_text_field( wp_unslash( $_POST['cf_password'] ) )   : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        // Locate any existing record so we can preserve hidden values
        // (secret/password are never re-sent from the browser on update).
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
            if ( '' === $cf_username && ! empty( $existing['cf_username'] ) ) {
                $cf_username = $existing['cf_username'];
            }
            if ( '' === $cf_password && ! empty( $existing['cf_password'] ) ) {
                $cf_password = $existing['cf_password'];
            }
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'API Key and API Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'              => $id,
            'title'           => $title,
            'client_id'       => $client_id,
            'client_secret'   => $client_secret,
            'cf_username'     => $cf_username,
            'cf_password'     => $cf_password,
            'access_token'    => '',
            'refresh_token'   => '',
            'company_file_id' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                // Preserve tokens + company_file_id when nothing OAuth-relevant
                // changed. The file-level credentials can be edited freely
                // without invalidating the token pair.
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

                if ( $same ) {
                    $new_data['access_token']    = $cred['access_token']    ?? '';
                    $new_data['refresh_token']   = $cred['refresh_token']   ?? '';
                    $new_data['company_file_id'] = $cred['company_file_id'] ?? '';
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
            'state'         => self::issue_oauth_state( 'myob', $id ),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            // GET / on the AccountRight root lists the user's company files —
            // it's the same call we use for discovery and doesn't require
            // company_file_id to already be cached.
            return wp_remote_request( esc_url_raw( self::api_base ), array(
                'timeout' => 30,
                'method'  => 'GET',
                'headers' => $this->build_base_headers(),
            ) );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/myob' );
    }

    /**
     * Token endpoint expects application/x-www-form-urlencoded with
     * client_id, client_secret, scope=CompanyFile, grant_type, code,
     * redirect_uri.
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
                'scope'         => self::oauth_scopes,
                'grant_type'    => 'authorization_code',
                'code'          => $authorization_code,
                'redirect_uri'  => $this->get_redirect_uri(),
            ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );

            // Discovery: pick first company file and cache its Id on the
            // credential record so subsequent API calls don't have to ask.
            $company_file_id = $this->discover_company_file();
            if ( $company_file_id ) {
                $this->company_file_id = $company_file_id;
            }
        }

        $this->save_data();

        return $response;
    }

    /**
     * Refresh access token via the same form-encoded shape. MYOB documents
     * the refresh body with the same scope=CompanyFile field; keep it.
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
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
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
        }

        $this->save_data();

        return $response;
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            // Always include company_file_id in the persisted extras so
            // token refreshes don't accidentally wipe it.
            $extras = array();
            if ( ! empty( $this->company_file_id ) ) {
                $extras['company_file_id'] = $this->company_file_id;
            }
            $this->persist_token_to_credential( $extras );
        }
    }

    /**
     * Discover the user's first MYOB company file by calling
     * GET https://api.myob.com/accountright/. Returns the Guid or ''.
     */
    public function discover_company_file() {
        if ( empty( $this->access_token ) ) {
            return '';
        }

        $response = wp_remote_get( esc_url_raw( self::api_base ), array(
            'timeout' => 30,
            'headers' => $this->build_base_headers(),
        ) );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // The /accountright/ endpoint returns a bare JSON array of
        // {Id, Name, LibraryPath, Uri} objects; be permissive in case MYOB
        // ever wraps it (e.g. {Items: [...]} like their list responses).
        $candidates = array();
        if ( is_array( $body ) ) {
            if ( isset( $body['Items'] ) && is_array( $body['Items'] ) ) {
                $candidates = $body['Items'];
            } else {
                $candidates = $body;
            }
        }

        foreach ( $candidates as $file ) {
            if ( ! is_array( $file ) ) {
                continue;
            }
            $id = $file['Id'] ?? ( $file['id'] ?? '' );
            if ( $id ) {
                return (string) $id;
            }
        }

        return '';
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }

        $this->cred_id         = $credentials['id'] ?? $cred_id;
        $this->client_id       = $credentials['client_id']       ?? '';
        $this->client_secret   = $credentials['client_secret']   ?? '';
        $this->access_token    = $credentials['access_token']    ?? '';
        $this->refresh_token   = $credentials['refresh_token']   ?? '';
        $this->token_expires   = isset( $credentials['token_expires'] ) ? (int) $credentials['token_expires'] : 0;
        $this->company_file_id = $credentials['company_file_id'] ?? '';
        $this->cf_username     = $credentials['cf_username']     ?? '';
        $this->cf_password     = $credentials['cf_password']     ?? '';

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'myob' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    /**
     * Headers common to every MYOB API call: x-myobapi-key (developer app
     * Client ID), x-myobapi-version (pin to v2), Accept, and the optional
     * x-myobapi-cftoken when the credential has per-file Sign-in details.
     *
     * Authorization: Bearer is intentionally NOT added here — it's added
     * by remote_request() so it can be re-applied after a 401 refresh.
     */
    protected function build_base_headers() {
        $headers = array(
            'x-myobapi-key'     => $this->client_id,
            'x-myobapi-version' => 'v2',
            'Accept'            => 'application/json',
        );

        if ( ! empty( $this->cf_username ) && ! empty( $this->cf_password ) ) {
            $headers['x-myobapi-cftoken'] = base64_encode( $this->cf_username . ':' . $this->cf_password );
        }

        return $headers;
    }

    /**
     * Issue a request against the MYOB AccountRight API.
     *
     * `$endpoint` should be relative to the company file context
     * (e.g. `Contact/Customer`). We prepend `{company_file_id}/`
     * automatically. If company_file_id isn't cached yet, attempt one
     * just-in-time discovery before failing.
     */
    public function myob_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        if ( empty( $this->company_file_id ) ) {
            $company_file_id = $this->discover_company_file();
            if ( $company_file_id ) {
                $this->company_file_id = $company_file_id;
                $this->persist_token_to_credential( array( 'company_file_id' => $company_file_id ) );
            } else {
                return new WP_Error( 'myob_missing_company_file', __( 'MYOB company file could not be discovered. Please reconnect this account.', 'advanced-form-integration' ) );
            }
        }

        $url    = self::api_base . rawurlencode( $this->company_file_id ) . '/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array_merge(
                $this->build_base_headers(),
                array( 'Content-Type' => 'application/json' )
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
     * Inject Authorization: Bearer and refresh once on 401. MYOB-specific
     * x-myobapi-* headers must survive the retry, so we don't reset
     * $request['headers'] — we only patch Authorization back in.
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

ADFOIN_MYOB::get_instance();

function adfoin_myob_fields() {
    return array(
        array( 'key' => 'company_name', 'value' => __( 'Company Name (required when Contact Type = Company)', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name',   'value' => __( 'First Name (required when Contact Type = Individual)', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',    'value' => __( 'Last Name (required when Contact Type = Individual)', 'advanced-form-integration' ) ),
        array( 'key' => 'email',        'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone1',       'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'street',       'value' => __( 'Street', 'advanced-form-integration' ) ),
        array( 'key' => 'city',         'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state',        'value' => __( 'State', 'advanced-form-integration' ) ),
        array( 'key' => 'postcode',     'value' => __( 'Postcode', 'advanced-form-integration' ) ),
        array( 'key' => 'country',      'value' => __( 'Country (defaults to Australia)', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_myob_job_queue', 'adfoin_myob_job_queue', 10, 1 );

function adfoin_myob_job_queue( $data ) {
    adfoin_myob_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_myob_send_data( $record, $posted_data ) {
    if ( 'create_customer' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve every mapped flat value up-front. Reserved keys (credId,
    // contactType) are control state, not part of the API payload.
    $reserved = array( 'credId' => 1, 'contactType' => 1 );
    $values   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' === $parsed || null === $parsed ) {
            continue;
        }
        $values[ $key ] = $parsed;
    }

    $contact_type = ( ( $field_data['contactType'] ?? 'individual' ) === 'company' ) ? 'company' : 'individual';

    // Build the TitleCase MYOB customer payload. IsIndividual is the key
    // discriminator: true = person, false = organisation.
    $payload = array(
        'IsIndividual' => ( 'individual' === $contact_type ),
        'IsActive'     => true,
    );

    if ( 'company' === $contact_type ) {
        if ( empty( $values['company_name'] ) ) {
            return; // required for company contacts
        }
        $payload['CompanyName'] = $values['company_name'];
        // MYOB still accepts FirstName/LastName on a company record as the
        // primary individual contact — pass them through if supplied.
        if ( ! empty( $values['first_name'] ) ) {
            $payload['FirstName'] = $values['first_name'];
        }
        if ( ! empty( $values['last_name'] ) ) {
            $payload['LastName'] = $values['last_name'];
        }
    } else {
        if ( empty( $values['first_name'] ) || empty( $values['last_name'] ) ) {
            return; // both required for individual contacts
        }
        $payload['FirstName'] = $values['first_name'];
        $payload['LastName']  = $values['last_name'];
        if ( ! empty( $values['company_name'] ) ) {
            $payload['CompanyName'] = $values['company_name'];
        }
    }

    // Build a single Location=1 (primary/billing) address only if any
    // address-related field is supplied. Country defaults to Australia
    // since MYOB AccountRight is an AU/NZ product.
    $address_field_map = array(
        'street'   => 'Street',
        'city'     => 'City',
        'state'    => 'State',
        'postcode' => 'PostCode',
        'country'  => 'Country',
        'phone1'   => 'Phone1',
        'email'    => 'Email',
    );
    $address = array();
    foreach ( $address_field_map as $flat => $api_key ) {
        if ( ! empty( $values[ $flat ] ) ) {
            $address[ $api_key ] = $values[ $flat ];
        }
    }
    if ( ! empty( $address ) ) {
        if ( empty( $address['Country'] ) ) {
            $address['Country'] = 'Australia';
        }
        $address['Location'] = 1;
        $payload['Addresses'] = array( $address );
    }

    $myob = ADFOIN_MYOB::get_instance();
    if ( ! $myob->set_credentials( $cred_id ) ) {
        return;
    }

    $myob->myob_request( 'Contact/Customer', 'POST', $payload, $record );
}
