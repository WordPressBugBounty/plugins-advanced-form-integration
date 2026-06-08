<?php

/**
 * Fortnox — Create Customer via POST /3/customers.
 *
 * Swedish SMB accounting platform. Multi-account OAuth via AFI's OAuth Manager
 * popup flow. Fortnox transitioned from legacy access tokens to OAuth2 in 2022.
 *
 * Auth: Authorization: Bearer {access_token}; client_id + client_secret sent
 * as HTTP Basic on the token endpoint. Tokens refreshed automatically with a
 * one-shot 401 retry inside remote_request().
 *
 * @link https://developer.fortnox.se/
 */

class ADFOIN_Fortnox extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'fortnox';

    const authorization_endpoint = 'https://apps.fortnox.se/oauth-v1/auth';
    const token_endpoint         = 'https://apps.fortnox.se/oauth-v1/token';
    const refresh_token_endpoint = 'https://apps.fortnox.se/oauth-v1/token';
    const api_base_url           = 'https://api.fortnox.se/3/';
    const oauth_scopes           = 'customer companyinformation';

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

        add_action( 'wp_ajax_adfoin_get_fortnox_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_fortnox_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_fortnox_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_fortnox_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['fortnox'] = array(
            'title' => __( 'Fortnox', 'advanced-form-integration' ),
            'tasks' => array(
                'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['fortnox'] = __( 'Fortnox', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'fortnox' !== $current_tab ) {
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
        $instructions .= '<li>' . sprintf( __( 'Sign in to %s and create (or open) an Integration.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.fortnox.se/my-apps/">Fortnox Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Set the Integration\'s OAuth2 Redirect URI to the URL below.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Grant the integration the <strong>customer</strong> and <strong>companyinformation</strong> scopes.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Fortnox', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'fortnox',
            __( 'Fortnox', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="fortnox-action-template">
            <table class="form-table" v-if="action.task == 'create_customer'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Fortnox Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=fortnox' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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
        register_rest_route( 'advancedformintegration', '/fortnox', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Fortnox with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, and closes the popup with
     * a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'fortnox' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'fortnox' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id     = $entry['client_id']     ?? $entry['clientId']     ?? '';
                $this->client_secret = $entry['client_secret'] ?? $entry['clientSecret'] ?? '';
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
                $message = $body['error_description'] ?? $body['error'] ?? __( 'Token exchange failed.', 'advanced-form-integration' );
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

        $platform    = 'fortnox';
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

        // Preserve hidden values (client_secret is never re-sent from the
        // browser on update).
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
            'access_token'  => '',
            'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id )
                    && ( ( $cred['client_secret'] ?? '' ) === $client_secret );

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

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'scope'         => self::oauth_scopes,
            'state'         => self::issue_oauth_state( 'fortnox', $id ),
            'access_type'   => 'offline',
            'redirect_uri'  => $this->get_redirect_uri(),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->fortnox_request( 'companyinformation', 'GET' );
        } );
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();
        wp_send_json_success( adfoin_fortnox_fields() );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/fortnox' );
    }

    /**
     * Fortnox's token endpoint requires HTTP Basic auth (client_id:client_secret)
     * and rejects credentials in the POST body — opposite of Zoho.
     */
    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type'   => 'authorization_code',
                'code'         => $authorization_code,
                'redirect_uri' => $this->get_redirect_uri(),
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
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refresh_token,
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
        $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
        $this->token_expires = isset( $credentials['tokenExpires'] ) ? (int) $credentials['tokenExpires'] : 0;

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) {
            return array();
        }

        foreach ( adfoin_read_credentials( 'fortnox' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function fortnox_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = self::api_base_url . ltrim( $endpoint, '/' );
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
     * Inject Bearer token and refresh once on 401. Mirrors the base
     * class implementation but adds the per-call $record so the logger
     * can attribute the request to the form submission.
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

ADFOIN_Fortnox::get_instance();

function adfoin_fortnox_fields() {
    return array(
        array( 'key' => 'name',               'value' => __( 'Name (required)', 'advanced-form-integration' ),                    'required' => true ),
        array( 'key' => 'email',              'value' => __( 'Email', 'advanced-form-integration' ),                              'required' => false ),
        array( 'key' => 'phone',              'value' => __( 'Phone', 'advanced-form-integration' ),                              'required' => false ),
        array( 'key' => 'organisationNumber', 'value' => __( 'Organisation / VAT Number', 'advanced-form-integration' ),          'required' => false ),
        array( 'key' => 'type',               'value' => __( 'Customer Type (PRIVATE or COMPANY, defaults to PRIVATE)', 'advanced-form-integration' ), 'required' => false ),
        array( 'key' => 'address1',           'value' => __( 'Street Address', 'advanced-form-integration' ),                     'required' => false ),
        array( 'key' => 'zipCode',            'value' => __( 'Zip Code', 'advanced-form-integration' ),                           'required' => false ),
        array( 'key' => 'city',               'value' => __( 'City', 'advanced-form-integration' ),                               'required' => false ),
        array( 'key' => 'countryCode',        'value' => __( 'Country Code (ISO-2, defaults to SE)', 'advanced-form-integration' ), 'required' => false ),
        array( 'key' => 'comments',           'value' => __( 'Comments', 'advanced-form-integration' ),                           'required' => false ),
    );
}

/**
 * Map flat form-field keys -> Fortnox TitleCase Customer keys.
 */
function adfoin_fortnox_field_map() {
    return array(
        'name'               => 'Name',
        'email'              => 'Email',
        'phone'              => 'Phone1',
        'organisationNumber' => 'OrganisationNumber',
        'type'               => 'Type',
        'address1'           => 'Address1',
        'zipCode'            => 'ZipCode',
        'city'               => 'City',
        'countryCode'        => 'CountryCode',
        'comments'           => 'Comments',
    );
}

add_action( 'adfoin_fortnox_job_queue', 'adfoin_fortnox_job_queue', 10, 1 );

function adfoin_fortnox_job_queue( $data ) {
    adfoin_fortnox_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_fortnox_send_data( $record, $posted_data ) {
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

    $field_map = adfoin_fortnox_field_map();
    $reserved  = array( 'credId' => 1 );
    $customer  = array();

    foreach ( $field_data as $form_key => $value ) {
        if ( isset( $reserved[ $form_key ] ) || ! isset( $field_map[ $form_key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' === $parsed || null === $parsed ) {
            continue;
        }
        $customer[ $field_map[ $form_key ] ] = $parsed;
    }

    if ( empty( $customer['Name'] ) ) {
        return; // Name is required by Fortnox.
    }

    // Normalize Type — Fortnox only accepts PRIVATE | COMPANY (uppercase).
    if ( ! empty( $customer['Type'] ) ) {
        $type = strtoupper( trim( $customer['Type'] ) );
        $customer['Type'] = ( 'COMPANY' === $type ) ? 'COMPANY' : 'PRIVATE';
    } else {
        $customer['Type'] = 'PRIVATE';
    }

    if ( empty( $customer['CountryCode'] ) ) {
        $customer['CountryCode'] = 'SE';
    }

    $fortnox = ADFOIN_Fortnox::get_instance();
    if ( ! $fortnox->set_credentials( $cred_id ) ) {
        return;
    }

    $fortnox->fortnox_request( 'customers', 'POST', array( 'Customer' => $customer ), $record );
}
