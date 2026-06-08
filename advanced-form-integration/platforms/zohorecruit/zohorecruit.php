<?php

/**
 * Zoho Recruit — Create Candidate via POST /recruit/v2/Candidates.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Zoho-oauthtoken {access_token}; tokens refreshed automatically.
 *
 * @link https://www.zoho.com/recruit/developer-guide/apiv2/
 */

class ADFOIN_ZohoRecruit extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'zohorecruit';

    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';
    const token_endpoint         = 'https://accounts.zoho.com/oauth/v2/token';
    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';
    const oauth_scopes           = 'ZohoRecruit.modules.ALL,ZohoRecruit.settings.ALL,ZohoRecruit.org.READ';

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

        add_action( 'wp_ajax_adfoin_get_zohorecruit_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zohorecruit_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_zohorecruit_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );

        add_action( 'wp_ajax_adfoin_get_zohorecruit_organizations', array( $this, 'ajax_get_organizations' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['zohorecruit'] = array(
            'title' => __( 'Zoho Recruit', 'advanced-form-integration' ),
            'tasks' => array(
                'create_candidate' => __( 'Create Candidate', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['zohorecruit'] = __( 'Zoho Recruit', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'zohorecruit' !== $current_tab ) {
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

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Go to %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Add Client</strong>, choose <strong>Server-based Applications</strong>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Set a client name and your website as the Homepage URL.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into Authorized Redirect URIs.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Click CREATE, then copy the Client ID and Client Secret into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Zoho Recruit', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'zohorecruit',
            __( 'Zoho Recruit', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="zohorecruit-action-template">
            <table class="form-table">
                <tr v-if="action.task == 'create_candidate'">
                    <th scope="row"><?php esc_html_e( 'Zoho Recruit Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchOrganizations">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohorecruit' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <tr v-if="action.task == 'create_candidate'">
                    <th scope="row"><?php esc_html_e( 'Organization', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[organizationId]" v-model="fielddata.organizationId">
                            <option value=""><?php esc_html_e( 'Select Organization...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in organizations" :value="index">{{ item }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': organizationLoading}"></div>
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
                <?php adfoin_pro_feature_notice( 'create_candidate', 'Zoho Recruit [PRO]', 'tags and custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohorecruit', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Zoho with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, and closes the popup with
     * a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'zohorecruit' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'zohorecruit' ) as $entry ) {
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

    /**
     * Legacy admin-redirect fallback for installs that may have an older
     * redirect URI still on file with Zoho.
     */
    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

        if ( 'adfoin_zohorecruit_auth_redirect' !== $action ) {
            return;
        }

        $code  = isset( $_GET['code'] )  ? sanitize_text_field( wp_unslash( $_GET['code'] ) )  : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

        $context = self::consume_oauth_state( $state, 'zohorecruit' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return;
        }

        $this->cred_id = $cred_id;

        foreach ( adfoin_read_credentials( 'zohorecruit' ) as $entry ) {
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

    /**
     * Migrate a legacy single-option zohorecruit_keys record (if any) into
     * the multi-account store so existing installs see their connection
     * in the OAuth Manager UI.
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'zohorecruit' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }

        $option = (array) maybe_unserialize( get_option( 'adfoin_zohorecruit_keys' ) );

        if ( ! empty( $option['client_id'] ) && ! empty( $option['client_secret'] ) ) {
            $credentials[] = array(
                'id'            => '123456',
                'title'         => __( 'Untitled', 'advanced-form-integration' ),
                'data_center'   => $option['data_center']   ?? 'com',
                'client_id'     => $option['client_id'],
                'client_secret' => $option['client_secret'],
                'access_token'  => $option['access_token']  ?? '',
                'refresh_token' => $option['refresh_token'] ?? '',
            );
        }

        return $credentials;
    }

    public function ajax_save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'zohorecruit';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Deletion path.
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                if ( ( $credentials[ $index ]['id'] ?? '' ) === '123456' ) {
                    delete_option( 'adfoin_zohorecruit_keys' );
                }
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
            'data_center'   => $data_center,
            'access_token'  => '',
            'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                // Preserve tokens when nothing material changed.
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
            'state'         => self::issue_oauth_state( 'zohorecruit', $id ),
        ), $auth_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->zohorecruit_request( 'users?type=CurrentUser', 'GET' );
        } );
    }

    public function ajax_get_organizations() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        if ( ! $cred_id ) {
            wp_send_json_success( array() );
        }

        $this->set_credentials( $cred_id );

        $response = $this->zohorecruit_request( 'org', 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $body          = json_decode( wp_remote_retrieve_body( $response ), true );
        $organizations = array();

        if ( ! empty( $body['org_data'] ) && is_array( $body['org_data'] ) ) {
            foreach ( $body['org_data'] as $org ) {
                if ( isset( $org['org_id'], $org['org_name'] ) ) {
                    $organizations[ $org['org_id'] ] = $org['org_name'];
                }
            }
        }

        wp_send_json_success( $organizations );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohorecruit' );
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

    protected function get_apis_base_url() {
        $map = array(
            'com'    => 'https://recruit.zoho.com/recruit/v2/',
            'eu'     => 'https://recruit.zoho.eu/recruit/v2/',
            'in'     => 'https://recruit.zoho.in/recruit/v2/',
            'com.cn' => 'https://recruit.zoho.com.cn/recruit/v2/',
            'com.au' => 'https://recruit.zoho.com.au/recruit/v2/',
            'jp'     => 'https://recruit.zoho.jp/recruit/v2/',
            'sa'     => 'https://recruit.zoho.sa/recruit/v2/',
        );
        $dc = $this->data_center ?: 'com';
        return $map[ $dc ] ?? $map['com'];
    }

    protected function request_token( $authorization_code ) {
        // Zoho's /oauth/v2/token requires credentials in the POST body;
        // Basic Auth returns invalid_client.
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

        foreach ( adfoin_read_credentials( 'zohorecruit' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'zohorecruit' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function zohorecruit_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $organization_id = '' ) {
        $url    = $this->get_apis_base_url() . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
        );

        if ( $organization_id ) {
            $args['headers']['X-ZOHO-RECRUIT-ORG'] = $organization_id;
        }

        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( 'GET' === $method && ! empty( $data ) && is_array( $data ) ) {
            $url = add_query_arg( $data, $url );
        }

        return $this->remote_request( $url, $args, $record );
    }

    /**
     * Inject Authorization: Bearer (Zoho accepts both Bearer and
     * Zoho-oauthtoken prefixes) and refresh once on 401.
     */
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
}

ADFOIN_ZohoRecruit::get_instance();

function adfoin_zohorecruit_fields() {
    return array(
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'last_name',  'value' => __( 'Last Name', 'advanced-form-integration' ),  'description' => '', 'required' => true,  'controlType' => 'text' ),
        array( 'key' => 'email',      'value' => __( 'Email', 'advanced-form-integration' ),      'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'phone',      'value' => __( 'Phone', 'advanced-form-integration' ),      'description' => '', 'required' => false, 'controlType' => 'text' ),
    );
}

function adfoin_zohorecruit_basic_field_map() {
    return array(
        'first_name' => 'First_Name',
        'last_name'  => 'Last_Name',
        'email'      => 'Email',
        'phone'      => 'Phone',
    );
}

function adfoin_zohorecruit_prepare_candidate_data( $field_data, $posted_data, $field_map ) {
    $candidate = array();

    foreach ( $field_map as $field_key => $api_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $candidate[ $api_key ] = $value;
    }

    return $candidate;
}

add_action( 'adfoin_zohorecruit_job_queue', 'adfoin_zohorecruit_job_queue', 10, 1 );

function adfoin_zohorecruit_job_queue( $data ) {
    adfoin_zohorecruit_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_zohorecruit_send_data( $record, $posted_data ) {
    if ( 'create_candidate' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $org_id     = $field_data['organizationId'] ?? '';

    if ( ! $cred_id || ! $org_id ) {
        return;
    }

    $candidate = adfoin_zohorecruit_prepare_candidate_data( $field_data, $posted_data, adfoin_zohorecruit_basic_field_map() );

    if ( empty( $candidate['Last_Name'] ) && ! empty( $candidate['First_Name'] ) ) {
        $candidate['Last_Name'] = $candidate['First_Name'];
    }

    if ( empty( $candidate['Last_Name'] ) ) {
        return;
    }

    $recruit = ADFOIN_ZohoRecruit::get_instance();
    if ( ! $recruit->set_credentials( $cred_id ) ) {
        return;
    }

    $recruit->zohorecruit_request( 'Candidates', 'POST', array( 'data' => array( $candidate ) ), $record, $org_id );
}
