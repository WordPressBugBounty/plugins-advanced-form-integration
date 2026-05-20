<?php

/**
 * Employment Hero — Create Employee via
 * POST /api/v1/organisations/{organisation_id}/employees.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow.
 * Auth: Authorization: Bearer {access_token}; tokens refreshed automatically.
 *
 * Employment Hero scopes every employee-facing endpoint under the user's
 * organisation_id. We discover it from GET /api/v1/organisations right after
 * the OAuth handshake and cache it on the credential record so subsequent
 * calls don't have to re-discover it. If it ever goes missing (e.g. legacy
 * credential), the request method performs a just-in-time re-discovery.
 *
 * @link https://developer.employmenthero.com/
 */

class ADFOIN_EmploymentHero extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'employmenthero';

    const authorization_endpoint = 'https://oauth.employmenthero.com/oauth2/authorize';
    const token_endpoint         = 'https://oauth.employmenthero.com/oauth2/token';
    const refresh_token_endpoint = 'https://oauth.employmenthero.com/oauth2/token';
    const api_base               = 'https://api.employmenthero.com/api/v1/';
    const oauth_scopes           = 'urn:mainapp:organisations:read urn:mainapp:employees:read urn:mainapp:employees:write';

    /** @var string Employment Hero organisation_id — cached on the credential record. */
    public $organisation_id = '';

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

        add_action( 'wp_ajax_adfoin_get_employmenthero_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_employmenthero_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_employmenthero_connection', array( $this, 'ajax_test_connection' ), 10, 0 );

        add_action( 'wp_ajax_adfoin_get_employmenthero_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['employmenthero'] = array(
            'title' => __( 'Employment Hero', 'advanced-form-integration' ),
            'tasks' => array(
                'create_employee' => __( 'Create Employee', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['employmenthero'] = __( 'Employment Hero', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'employmenthero' !== $current_tab ) {
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
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Sign in to Employment Hero and open the %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.employmenthero.com/">Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Create a new OAuth application (or open an existing one).', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Under <strong>Redirect URIs</strong>, add the URL below:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Grant the following scopes: <code>urn:mainapp:organisations:read</code>, <code>urn:mainapp:employees:read</code>, <code>urn:mainapp:employees:write</code>.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the <strong>Client ID</strong> and <strong>Client Secret</strong> into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup and discovers your organisation automatically.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Employment Hero', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'employmenthero',
            __( 'Employment Hero', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="employmenthero-action-template">
            <table class="form-table" v-if="action.task == 'create_employee'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Employment Hero Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=employmenthero' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

    public function ajax_get_fields() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        wp_send_json_success( adfoin_employmenthero_fields() );
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/employmenthero', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Employment Hero with ?code=…&state=…. Resolves the
     * saved credential, exchanges the code for tokens, discovers the
     * organisation_id, and closes the popup with a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'employmenthero' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'employmenthero' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? '';
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

        $platform    = 'employmenthero';
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
            'id'              => $id,
            'title'           => $title,
            'client_id'       => $client_id,
            'client_secret'   => $client_secret,
            'access_token'    => '',
            'refresh_token'   => '',
            'organisation_id' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                // Preserve tokens + organisation_id when nothing material changed.
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

                if ( $same ) {
                    $new_data['access_token']    = $cred['access_token']    ?? '';
                    $new_data['refresh_token']   = $cred['refresh_token']   ?? '';
                    $new_data['organisation_id'] = $cred['organisation_id'] ?? '';
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
            'state'         => self::issue_oauth_state( 'employmenthero', $id ),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            // GET /organisations is the natural connectivity probe — it's the
            // same call we use for organisation discovery and doesn't require
            // organisation_id to already be cached.
            return wp_remote_request( esc_url_raw( self::api_base . 'organisations' ), array(
                'timeout' => 30,
                'method'  => 'GET',
                'headers' => array(
                    'Authorization' => $this->get_http_authorization_header( 'bearer' ),
                    'Accept'        => 'application/json',
                ),
            ) );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/employmenthero' );
    }

    /**
     * Token endpoint expects application/x-www-form-urlencoded body with
     * grant_type, code, redirect_uri, client_id, client_secret.
     */
    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'grant_type'    => 'authorization_code',
                'code'          => $authorization_code,
                'redirect_uri'  => $this->get_redirect_uri(),
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );

            // Discovery: pick first organisation and cache its id on the
            // credential record so subsequent API calls don't have to ask.
            $organisation_id = $this->discover_organisation_id();
            if ( $organisation_id ) {
                $this->organisation_id = $organisation_id;
            }
        }

        $this->save_data();

        return $response;
    }

    /**
     * Refresh access token via the same form-encoded body shape.
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
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
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
            // Always include organisation_id in the persisted extras so token
            // refreshes don't accidentally wipe it.
            $extras = array();
            if ( ! empty( $this->organisation_id ) ) {
                $extras['organisation_id'] = $this->organisation_id;
            }
            $this->persist_token_to_credential( $extras );
        }
    }

    /**
     * Discover the user's primary Employment Hero organisation_id by calling
     * GET /api/v1/organisations with the just-issued access token. Returns
     * the organisation id or '' on failure.
     */
    public function discover_organisation_id() {
        if ( empty( $this->access_token ) ) {
            return '';
        }

        $response = wp_remote_get( esc_url_raw( self::api_base . 'organisations' ), array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Accept'        => 'application/json',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // Employment Hero may wrap the list under {data: {items: [...]}}, or
        // {data: [...]}, or return the array at top level. Be permissive.
        $candidates = array();
        if ( isset( $body['data']['items'] ) && is_array( $body['data']['items'] ) ) {
            $candidates = $body['data']['items'];
        } elseif ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            $candidates = $body['data'];
        } elseif ( isset( $body['items'] ) && is_array( $body['items'] ) ) {
            $candidates = $body['items'];
        } elseif ( is_array( $body ) ) {
            $candidates = $body;
        }

        foreach ( $candidates as $org ) {
            if ( ! is_array( $org ) ) {
                continue;
            }
            $id = $org['id'] ?? ( $org['organisation_id'] ?? '' );
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
        $this->organisation_id = $credentials['organisation_id'] ?? '';

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'employmenthero' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    /**
     * Issue a request against the Employment Hero API.
     *
     * `$endpoint` should be relative to the organisation context
     * (e.g. `employees`). We prepend `organisations/{organisation_id}/`
     * automatically. If organisation_id isn't cached yet, attempt one
     * just-in-time discovery before failing.
     */
    public function employmenthero_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        if ( empty( $this->organisation_id ) ) {
            $organisation_id = $this->discover_organisation_id();
            if ( $organisation_id ) {
                $this->organisation_id = $organisation_id;
                $this->persist_token_to_credential( array( 'organisation_id' => $organisation_id ) );
            } else {
                return new WP_Error( 'employmenthero_missing_organisation_id', __( 'Employment Hero organisation_id could not be discovered. Please reconnect this account.', 'advanced-form-integration' ) );
            }
        }

        $url    = self::api_base . 'organisations/' . rawurlencode( $this->organisation_id ) . '/' . ltrim( $endpoint, '/' );
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

    /**
     * Inject Authorization: Bearer and refresh once on 401.
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

ADFOIN_EmploymentHero::get_instance();

function adfoin_employmenthero_fields() {
    return array(
        array( 'key' => 'first_name',      'value' => __( 'First Name', 'advanced-form-integration' ),                  'required' => true ),
        array( 'key' => 'last_name',       'value' => __( 'Last Name', 'advanced-form-integration' ),                   'required' => true ),
        array( 'key' => 'email',           'value' => __( 'Email', 'advanced-form-integration' ),                       'required' => true ),
        array( 'key' => 'mobile_phone',    'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title',       'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'start_date',      'value' => __( 'Start Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'employment_type', 'value' => __( 'Employment Type (e.g. full_time, part_time, casual)', 'advanced-form-integration' ) ),
        array( 'key' => 'date_of_birth',   'value' => __( 'Date of Birth (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'gender',          'value' => __( 'Gender (male/female/other)', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line_1',  'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'city',            'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state',           'value' => __( 'State', 'advanced-form-integration' ) ),
        array( 'key' => 'post_code',       'value' => __( 'Post Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country',         'value' => __( 'Country (ISO-2, defaults to AU)', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_employmenthero_job_queue', 'adfoin_employmenthero_job_queue', 10, 1 );

function adfoin_employmenthero_job_queue( $data ) {
    adfoin_employmenthero_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_employmenthero_send_data( $record, $posted_data ) {
    if ( 'create_employee' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve every mapped flat value up-front. Reserved keys (credId, etc.)
    // are not part of the API payload.
    $reserved = array( 'credId' => 1 );
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

    // Employment Hero requires first_name, last_name, email at minimum.
    if ( empty( $values['first_name'] ) || empty( $values['last_name'] ) || empty( $values['email'] ) ) {
        return;
    }

    $top_level_keys = array(
        'first_name',
        'last_name',
        'email',
        'mobile_phone',
        'job_title',
        'start_date',
        'employment_type',
        'date_of_birth',
        'gender',
    );

    $payload = array();
    foreach ( $top_level_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $payload[ $key ] = $values[ $key ];
        }
    }

    // Build nested residential_address only when any address-related field
    // is supplied. Country defaults to AU per Employment Hero's primary market.
    $address_field_map = array(
        'address_line_1' => 'line_1',
        'city'           => 'city',
        'state'          => 'state',
        'post_code'      => 'post_code',
        'country'        => 'country',
    );
    $address = array();
    foreach ( $address_field_map as $flat => $api_key ) {
        if ( ! empty( $values[ $flat ] ) ) {
            $address[ $api_key ] = $values[ $flat ];
        }
    }
    if ( ! empty( $address ) ) {
        if ( empty( $address['country'] ) ) {
            $address['country'] = 'AU';
        }
        $payload['residential_address'] = $address;
    }

    $employmenthero = ADFOIN_EmploymentHero::get_instance();
    if ( ! $employmenthero->set_credentials( $cred_id ) ) {
        return;
    }

    $employmenthero->employmenthero_request( 'employees', 'POST', $payload, $record );
}
