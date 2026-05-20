<?php

/**
 * FreeAgent — Create Contact via POST /v2/contacts.
 *
 * Multi-account OAuth2 (authorization_code) via AFI's OAuth Manager popup.
 * Auth: Authorization: Bearer {access_token}; tokens refreshed on 401.
 *
 * FreeAgent requires a User-Agent header on every API call and does not use
 * scopes — the grant authorizes the full company the user picks during the
 * approval step. Defaults to production endpoints; sandbox can be reached by
 * editing the credential's `environment` field to "sandbox".
 *
 * @link https://dev.freeagent.com/docs/
 */

class ADFOIN_FreeAgent extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'freeagent';

    const authorization_endpoint = 'https://api.freeagent.com/v2/approve_app';
    const token_endpoint         = 'https://api.freeagent.com/v2/token_endpoint';
    const refresh_token_endpoint = 'https://api.freeagent.com/v2/token_endpoint';

    const sandbox_authorization_endpoint = 'https://api.sandbox.freeagent.com/v2/approve_app';
    const sandbox_token_endpoint         = 'https://api.sandbox.freeagent.com/v2/token_endpoint';

    const api_base         = 'https://api.freeagent.com/v2/';
    const sandbox_api_base = 'https://api.sandbox.freeagent.com/v2/';

    const user_agent = 'AdvancedFormIntegrationWP';

    public $environment = 'production';

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

        add_action( 'wp_ajax_adfoin_get_freeagent_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_freeagent_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_freeagent_connection', array( $this, 'ajax_test_connection' ), 10, 0 );

        add_action( 'wp_ajax_adfoin_get_freeagent_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['freeagent'] = array(
            'title' => __( 'FreeAgent', 'advanced-form-integration' ),
            'tasks' => array(
                'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['freeagent'] = __( 'FreeAgent', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'freeagent' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name'          => 'client_id',
                'label'         => __( 'OAuth Identifier', 'advanced-form-integration' ),
                'type'          => 'text',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'client_secret',
                'label'         => __( 'OAuth Secret', 'advanced-form-integration' ),
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
                    'production' => __( 'Production (api.freeagent.com)', 'advanced-form-integration' ),
                    'sandbox'    => __( 'Sandbox (api.sandbox.freeagent.com)', 'advanced-form-integration' ),
                ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to FreeAgent and open the %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://dev.freeagent.com/apps">Developer Dashboard</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Create new app</strong> and enter a name + description.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Redirect URI below into <strong>OAuth redirect URIs</strong> on the app form.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Save the app, then copy <strong>OAuth Identifier</strong> and <strong>OAuth Secret</strong> into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the OAuth round-trip in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect FreeAgent', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'freeagent',
            __( 'FreeAgent', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="freeagent-action-template">
            <table class="form-table" v-if="action.task == 'create_contact'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'FreeAgent Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=freeagent' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/freeagent', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by FreeAgent with ?code=…&state=…. Looks up the
     * credential by state, exchanges the code for tokens, and closes the
     * popup with a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'freeagent' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'freeagent' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->environment   = $entry['environment']   ?? 'production';
                $this->client_id     = $entry['client_id']     ?? $entry['clientId']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? $entry['clientSecret'] ?? '';
                $this->update_oauth_endpoints( $this->environment );
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

        $platform    = 'freeagent';
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
        $environment   = isset( $_POST['environment'] )   ? sanitize_text_field( wp_unslash( $_POST['environment'] ) )   : 'production';

        if ( ! in_array( $environment, array( 'production', 'sandbox' ), true ) ) {
            $environment = 'production';
        }

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        // Preserve client_secret on updates (browser doesn't re-send it).
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
            wp_send_json_error( array( 'message' => __( 'OAuth Identifier and OAuth Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'environment'   => $environment,
            'access_token'  => '',
            'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret )
                    && ( ( $cred['environment'] ?? '' ) === $environment );

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

        $auth_endpoint = $this->get_oauth_endpoint( 'approve_app', $environment );
        $auth_url      = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'state'         => self::issue_oauth_state( 'freeagent', $id ),
        ), $auth_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->freeagent_request( 'company', 'GET' );
        } );
    }

    public function ajax_get_fields() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        wp_send_json_success( adfoin_freeagent_fields() );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/freeagent' );
    }

    protected function get_oauth_endpoint( $path = 'approve_app', $environment = '' ) {
        $env = $environment ?: ( $this->environment ?: 'production' );
        $base = ( 'sandbox' === $env ) ? 'https://api.sandbox.freeagent.com/v2/' : 'https://api.freeagent.com/v2/';
        return $base . ltrim( $path, '/' );
    }

    protected function update_oauth_endpoints( $environment ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'approve_app', $environment );
        $this->token_endpoint         = $this->get_oauth_endpoint( 'token_endpoint', $environment );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    protected function get_apis_base_url() {
        return ( 'sandbox' === ( $this->environment ?: 'production' ) ) ? self::sandbox_api_base : self::api_base;
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
                'User-Agent'   => self::user_agent,
            ),
            'body'    => array(
                'grant_type'    => 'authorization_code',
                'code'          => $authorization_code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $this->get_redirect_uri(),
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
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
                'User-Agent'   => self::user_agent,
            ),
            'body'    => array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code || 400 === $code ) {
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
        $this->environment   = $credentials['environment']   ?? 'production';
        $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        $this->token_expires = isset( $credentials['tokenExpires'] ) ? (int) $credentials['tokenExpires'] : 0;

        $this->update_oauth_endpoints( $this->environment );

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'freeagent' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function freeagent_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = $this->get_apis_base_url() . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => self::user_agent,
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

ADFOIN_FreeAgent::get_instance();

function adfoin_freeagent_fields() {
    return array(
        array( 'key' => 'first_name',        'value' => __( 'First Name', 'advanced-form-integration' ),                        'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'last_name',         'value' => __( 'Last Name', 'advanced-form-integration' ),                         'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'organisation_name', 'value' => __( 'Organisation Name', 'advanced-form-integration' ),                  'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'email',             'value' => __( 'Email', 'advanced-form-integration' ),                              'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'phone_number',      'value' => __( 'Phone Number', 'advanced-form-integration' ),                       'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'mobile',            'value' => __( 'Mobile', 'advanced-form-integration' ),                             'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'address1',          'value' => __( 'Address Line 1', 'advanced-form-integration' ),                     'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'address2',          'value' => __( 'Address Line 2', 'advanced-form-integration' ),                     'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'town',              'value' => __( 'Town / City', 'advanced-form-integration' ),                        'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'region',            'value' => __( 'Region / County', 'advanced-form-integration' ),                    'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'postcode',          'value' => __( 'Postcode', 'advanced-form-integration' ),                           'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'country',           'value' => __( 'Country (e.g. United Kingdom)', 'advanced-form-integration' ),      'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'notes',             'value' => __( 'Notes', 'advanced-form-integration' ),                              'description' => '', 'required' => false, 'controlType' => 'text' ),
    );
}

add_action( 'adfoin_freeagent_job_queue', 'adfoin_freeagent_job_queue', 10, 1 );

function adfoin_freeagent_job_queue( $data ) {
    adfoin_freeagent_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_freeagent_send_data( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
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
    $values   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // FreeAgent requires either first/last name OR organisation_name; bail
    // early if all three are empty so we don't post obviously invalid data.
    if ( empty( $values['first_name'] ) && empty( $values['last_name'] ) && empty( $values['organisation_name'] ) ) {
        return;
    }

    $contact = array();
    $allowed = array(
        'first_name',
        'last_name',
        'organisation_name',
        'email',
        'phone_number',
        'mobile',
        'address1',
        'address2',
        'town',
        'region',
        'postcode',
        'country',
    );
    foreach ( $allowed as $key ) {
        if ( isset( $values[ $key ] ) ) {
            $contact[ $key ] = $values[ $key ];
        }
    }

    // FreeAgent uses a separate field for notes; treat blank as omit.
    if ( ! empty( $values['notes'] ) ) {
        $contact['notes'] = $values['notes'];
    }

    $payload = array( 'contact' => $contact );

    $freeagent = ADFOIN_FreeAgent::get_instance();
    if ( ! $freeagent->set_credentials( $cred_id ) ) {
        return;
    }

    $freeagent->freeagent_request( 'contacts', 'POST', $payload, $record );
}
