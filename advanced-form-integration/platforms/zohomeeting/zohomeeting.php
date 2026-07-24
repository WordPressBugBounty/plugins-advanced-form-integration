<?php

/**
 * Zoho Meeting / Zoho Webinar — Register Contact to a webinar via
 * POST https://webinar.zoho.com/api/v2/{zsoid}/register/{webinarKey}.json.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Zoho-oauthtoken {access_token}; tokens refreshed automatically.
 *
 * @link https://www.zoho.com/webinar/api/webinar-api/bulk-registration.html
 */

class ADFOIN_ZohoMeeting extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'zohomeeting';

    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';
    const token_endpoint         = 'https://accounts.zoho.com/oauth/v2/token';
    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';
    const oauth_scopes           = 'ZohoMeeting.webinar.UPDATE,ZohoMeeting.webinar.READ,aaaserver.profile.READ';

    public $data_center;

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

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'create_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_zohomeeting_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zohomeeting_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_zohomeeting_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['zohomeeting'] = array(
            'title' => __( 'Zoho Meeting', 'advanced-form-integration' ),
            'tasks' => array(
                'register_contact' => __( 'Register Contact to Webinar', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['zohomeeting'] = __( 'Zoho Meeting', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'zohomeeting' !== $current_tab ) {
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
            array(
                'name'          => 'data_center',
                'label'         => __( 'Data Center', 'advanced-form-integration' ),
                'type'          => 'select',
                'required'      => false,
                'show_in_table' => false,
                'options'       => array(
                    'com'    => 'zoho.com',
                    'eu'     => 'zoho.eu',
                    'in'     => 'zoho.in',
                    'com.cn' => 'zoho.com.cn',
                    'com.au' => 'zoho.com.au',
                    'jp'     => 'zoho.jp',
                    'sa'     => 'zoho.sa',
                ),
            ),
        );

        $instructions  = '<div class="notice notice-info" style="margin:0 0 12px 0; padding:8px 12px;">';
        $instructions .= sprintf(
            /* translators: %s: Zoho Webinar product name */
            __( 'This integration targets the %s product (the webinar add-on bundled with Zoho Meeting plans). The "Register Contact to Webinar" task calls the documented bulk-registration endpoint.', 'advanced-form-integration' ),
            '<strong>Zoho Webinar</strong>'
        );
        $instructions .= '</div>';
        $instructions .= '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Add Client</strong>, choose <strong>Server-based Applications</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Set a client name and your website as Homepage URL.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into Authorized Redirect URIs.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Click CREATE, then copy the Client ID + Client Secret into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Zoho Meeting', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'zohomeeting',
            __( 'Zoho Meeting', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="zohomeeting-action-template">
            <table class="form-table" v-if="action.task == 'register_contact'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Zoho Meeting Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohomeeting' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Webinar Key', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="fieldData[webinarKey]" v-model="fielddata.webinarKey" placeholder="<?php esc_attr_e( 'Numeric webinar key from Zoho Webinar', 'advanced-form-integration' ); ?>">
                        <p class="description"><?php esc_html_e( 'Find the webinar key in the URL of your webinar in the Zoho Webinar dashboard.', 'advanced-form-integration' ); ?></p>
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
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohomeeting', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'zohomeeting' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'zohomeeting' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->data_center   = $entry['data_center']   ?? $entry['dataCenter']   ?? 'com';
                $this->client_id     = $entry['client_id']     ?? $entry['clientId']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? $entry['clientSecret'] ?? '';
                $this->update_oauth_endpoints( $this->data_center );
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

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

        if ( 'adfoin_zohomeeting_auth_redirect' !== $action ) {
            return;
        }

        // admin_init fires for every logged-in user; only an admin should
        // be able to complete this OAuth flow (CWE-862).
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $code  = isset( $_GET['code'] )  ? sanitize_text_field( wp_unslash( $_GET['code'] ) )  : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

        $context = self::consume_oauth_state( $state, 'zohomeeting' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return;
        }

        $this->cred_id = $cred_id;

        foreach ( adfoin_read_credentials( 'zohomeeting' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->data_center   = $entry['data_center']   ?? $entry['dataCenter']   ?? 'com';
                $this->client_id     = $entry['client_id']     ?? $entry['clientId']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? $entry['clientSecret'] ?? '';
                $this->update_oauth_endpoints( $this->data_center );
                break;
            }
        }

        if ( $this->client_id && $this->client_secret ) {
            $this->request_token( $code );

            if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
                require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            }
            ADFOIN_OAuth_Manager::handle_callback_close_popup( true, __( 'Connected via legacy redirect.', 'advanced-form-integration' ) );
        }
    }

    public function ajax_get_credentials() {
        adfoin_verify_nonce();
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function ajax_save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'zohomeeting';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

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
        $data_center   = isset( $_POST['data_center'] )   ? sanitize_text_field( wp_unslash( $_POST['data_center'] ) )   : 'com';

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
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'data_center'   => $data_center,
            'access_token'  => '',
            'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret )
                    && ( ( $cred['data_center'] ?? '' ) === $data_center );

                if ( $same ) {
                    $new_data['access_token']  = $cred['access_token']  ?? '';
                    $new_data['refresh_token'] = $cred['refresh_token'] ?? '';
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

        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $auth_url      = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => self::oauth_scopes,
            'state'         => self::issue_oauth_state( 'zohomeeting', $id ),
        ), $auth_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            // Cheap authenticated probe — list webinars (returns 200 with empty
            // result for new accounts).
            return $this->zohomeeting_request( 'webinar.json', 'GET' );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohomeeting' );
    }

    protected function get_accounts_base( $data_center = 'com' ) {
        $map = array(
            'com'    => 'https://accounts.zoho.com',
            'eu'     => 'https://accounts.zoho.eu',
            'in'     => 'https://accounts.zoho.in',
            'com.cn' => 'https://accounts.zoho.com.cn',
            'com.au' => 'https://accounts.zoho.com.au',
            'jp'     => 'https://accounts.zoho.jp',
            'sa'     => 'https://accounts.zoho.sa',
        );
        return $map[ $data_center ] ?? $map['com'];
    }

    protected function get_oauth_endpoint( $path = 'auth', $data_center = '' ) {
        $base = $this->get_accounts_base( $data_center ?: $this->data_center );
        return trailingslashit( $base ) . 'oauth/v2/' . $path;
    }

    protected function update_oauth_endpoints( $data_center ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $this->token_endpoint         = $this->get_oauth_endpoint( 'token', $data_center );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    /**
     * Zoho Webinar lives at webinar.zoho.{dc}; the older meeting.zoho.{dc}
     * host serves the Meeting (video call) APIs, not webinar registration.
     */
    protected function get_webinar_base_url() {
        $map = array(
            'com'    => 'https://webinar.zoho.com/api/v2/',
            'eu'     => 'https://webinar.zoho.eu/api/v2/',
            'in'     => 'https://webinar.zoho.in/api/v2/',
            'com.cn' => 'https://webinar.zoho.com.cn/api/v2/',
            'com.au' => 'https://webinar.zoho.com.au/api/v2/',
            'jp'     => 'https://webinar.zoho.jp/api/v2/',
            'sa'     => 'https://webinar.zoho.sa/api/v2/',
        );
        $dc = $this->data_center ?: 'com';
        return $map[ $dc ] ?? $map['com'];
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'body'    => array(
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
            ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return null;
        }

        $response = wp_remote_post( esc_url_raw( $this->refresh_token_endpoint ), array(
            'timeout' => 30,
            'body'    => array(
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
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
            $this->persist_token_to_credential();
        }
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return false;
        }

        $this->cred_id       = $credentials['id'] ?? $cred_id;
        $this->data_center   = $credentials['data_center']   ?? $credentials['dataCenter']   ?? 'com';
        $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        $this->token_expires = isset( $credentials['tokenExpires'] ) ? (int) $credentials['tokenExpires'] : 0;

        $this->update_oauth_endpoints( $this->data_center );

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }
        foreach ( adfoin_read_credentials( 'zohomeeting' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }
        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'zohomeeting' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    /**
     * Call a Zoho Webinar / Meeting endpoint. The first path segment after
     * /api/v2/ is the zsoid (organization id) — we need it for registration.
     * When the caller's endpoint already includes the zsoid, we pass through.
     */
    public function zohomeeting_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = $this->get_webinar_base_url() . ltrim( $endpoint, '/' );
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

        return $this->remote_request( $url, $args, $record );
    }

    protected function remote_request( $url, $request = array(), $record = array() ) {
        $refreshed = false;

        $request            = wp_parse_args( $request, array() );
        $request['headers'] = array_merge(
            $request['headers'] ?? array(),
            array( 'Authorization' => 'Zoho-oauthtoken ' . $this->access_token )
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        if ( 401 === (int) wp_remote_retrieve_response_code( $response ) && ! $refreshed ) {
            $this->refresh_token();
            $refreshed = true;
            $request['headers']['Authorization'] = 'Zoho-oauthtoken ' . $this->access_token;
            $response = wp_remote_request( esc_url_raw( $url ), $request );
        }

        // Retry on rate limiting (HTTP 429), honouring Retry-After.
        $rl_attempts = 0;
        while ( 429 === wp_remote_retrieve_response_code( $response ) && $rl_attempts < 2 ) {
            $retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
            $retry_after = ( $retry_after > 0 && $retry_after <= 10 ) ? $retry_after : 3;
            sleep( $retry_after );
            $rl_attempts++;
            $response = wp_remote_request( esc_url_raw( $url ), $request );
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $request, $record );
        }

        return $response;
    }

    /**
     * Get the zsoid (organization id) for the connected account by hitting
     * the profile endpoint. Cached per credential.
     */
    public function get_zsoid() {
        if ( empty( $this->cred_id ) ) {
            return '';
        }

        $cached_key = 'adfoin_zohomeeting_zsoid_' . $this->cred_id;
        $cached     = get_transient( $cached_key );

        if ( is_string( $cached ) && $cached ) {
            return $cached;
        }

        // /api/v2/profile.json returns the current user's profile including zsoid.
        $url      = $this->get_webinar_base_url() . 'profile.json';
        $args     = array(
            'timeout' => 30,
            'method'  => 'GET',
            'headers' => array(
                'Accept' => 'application/json',
            ),
        );
        $response = $this->remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $zsoid = $body['profile']['zsoid'] ?? ( $body['zsoid'] ?? '' );

        if ( $zsoid ) {
            set_transient( $cached_key, (string) $zsoid, DAY_IN_SECONDS );
        }

        return (string) $zsoid;
    }
}

ADFOIN_ZohoMeeting::get_instance();

add_action( 'wp_ajax_adfoin_get_zohomeeting_fields', 'adfoin_get_zohomeeting_fields' );

function adfoin_get_zohomeeting_fields() {
    adfoin_verify_nonce();

    wp_send_json_success( array(
        array( 'key' => 'email',     'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',  'value' => __( 'Last Name', 'advanced-form-integration' ) ),
    ) );
}

add_action( 'adfoin_zohomeeting_job_queue', 'adfoin_zohomeeting_job_queue', 10, 1 );

function adfoin_zohomeeting_job_queue( $data ) {
    adfoin_zohomeeting_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_zohomeeting_send_data( $record, $posted_data ) {
    if ( 'register_contact' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data  = $record_data['field_data'] ?? array();
    $cred_id     = $field_data['credId']     ?? '';
    $webinar_key = isset( $field_data['webinarKey'] ) ? trim( (string) adfoin_get_parsed_values( $field_data['webinarKey'], $posted_data ) ) : '';

    if ( ! $cred_id || ! $webinar_key ) {
        return;
    }

    $email = isset( $field_data['email'] )
        ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
        : '';

    if ( ! $email ) {
        return;
    }

    $registrant = array( 'email' => $email );

    foreach ( array( 'firstName', 'lastName' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $registrant[ $key ] = $value;
        }
    }

    $meeting = ADFOIN_ZohoMeeting::get_instance();
    if ( ! $meeting->set_credentials( $cred_id ) ) {
        return;
    }

    $zsoid = $meeting->get_zsoid();
    if ( ! $zsoid ) {
        return;
    }

    $endpoint = $zsoid . '/register/' . rawurlencode( $webinar_key ) . '.json';
    $payload  = array( 'registrant' => array( $registrant ) );

    $meeting->zohomeeting_request( $endpoint, 'POST', $payload, $record );
}
