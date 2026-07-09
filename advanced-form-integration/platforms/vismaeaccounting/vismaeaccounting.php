<?php

/**
 * Visma eAccounting — Create Customer via POST /v2/customers.
 *
 * Pan-Nordic SMB accounting platform (NO/SE/DK/FI). Multi-account OAuth via
 * AFI's OAuth Manager popup flow.
 *
 * Auth: Authorization: Bearer {access_token}; client_id + client_secret sent
 * as HTTP Basic on the token endpoint (NOT in the body). Tokens are refreshed
 * automatically with a one-shot 401 retry inside remote_request().
 *
 * @link https://eaccountingapi.vismaonline.com/swagger/
 */

class ADFOIN_VismaEAccounting extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'vismaeaccounting';

    const authorization_endpoint = 'https://identity.vismaonline.com/connect/authorize';
    const token_endpoint         = 'https://identity.vismaonline.com/connect/token';
    const refresh_token_endpoint = 'https://identity.vismaonline.com/connect/token';
    const api_base_url           = 'https://eaccountingapi.vismaonline.com/v2/';
    const oauth_scopes           = 'ea:api ea:sales offline_access';

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

        add_action( 'wp_ajax_adfoin_get_vismaeaccounting_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_vismaeaccounting_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_vismaeaccounting_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_vismaeaccounting_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['vismaeaccounting'] = array(
            'title' => __( 'Visma eAccounting', 'advanced-form-integration' ),
            'tasks' => array(
                'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['vismaeaccounting'] = __( 'Visma eAccounting', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'vismaeaccounting' !== $current_tab ) {
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
        $instructions .= '<li>' . sprintf( __( 'Sign in to %s and request API access for your Visma eAccounting application.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.visma.com/">Visma Developer Portal</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'When configuring your application, set the OAuth2 Redirect URI to the URL below.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Request the <strong>ea:api</strong>, <strong>ea:sales</strong> and <strong>offline_access</strong> scopes for the application.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret into the Add Account form here.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click <strong>Save &amp; Authorize</strong> — AFI handles the rest in a popup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Visma eAccounting', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'vismaeaccounting',
            __( 'Visma eAccounting', 'advanced-form-integration' ),
            $fields,
            $instructions,
            $config
        );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="vismaeaccounting-action-template">
            <table class="form-table" v-if="action.task == 'create_customer'">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td></td>
                </tr>

                <tr class="alternate">
                    <td scope="row-title">
                        <label><?php esc_html_e( 'Visma eAccounting Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=vismaeaccounting' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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
        register_rest_route( 'advancedformintegration', '/vismaeaccounting', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /**
     * REST callback hit by Visma with ?code=…&state=…. Resolves the saved
     * credential, exchanges the code for tokens, and closes the popup with
     * a success/failure message.
     */
    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'vismaeaccounting' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( ! $code || ! $cred_id ) {
            return array( 'status' => 'ignored' );
        }

        $this->cred_id = $cred_id;

        $found = false;
        foreach ( adfoin_read_credentials( 'vismaeaccounting' ) as $entry ) {
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

        $platform    = 'vismaeaccounting';
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
            'state'         => self::issue_oauth_state( 'vismaeaccounting', $id ),
            'redirect_uri'  => $this->get_redirect_uri(),
        ), self::authorization_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->vismaeaccounting_request( 'companysettings', 'GET' );
        } );
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();
        wp_send_json_success( adfoin_vismaeaccounting_fields() );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/vismaeaccounting' );
    }

    /**
     * Visma's /connect/token endpoint requires HTTP Basic auth
     * (client_id:client_secret) — credentials must NOT be in the POST body.
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

        foreach ( adfoin_read_credentials( 'vismaeaccounting' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return array();
    }

    public function vismaeaccounting_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
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
        $refreshed = false;

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

ADFOIN_VismaEAccounting::get_instance();

function adfoin_vismaeaccounting_fields() {
    return array(
        array( 'key' => 'name',                        'value' => __( 'Name (required)', 'advanced-form-integration' ),                              'required' => true ),
        array( 'key' => 'email',                       'value' => __( 'Email Address', 'advanced-form-integration' ),                                'required' => false ),
        array( 'key' => 'telephone',                   'value' => __( 'Telephone', 'advanced-form-integration' ),                                    'required' => false ),
        array( 'key' => 'mobile_phone',                'value' => __( 'Mobile Phone', 'advanced-form-integration' ),                                 'required' => false ),
        array( 'key' => 'invoice_address1',            'value' => __( 'Invoice Address (street)', 'advanced-form-integration' ),                     'required' => false ),
        array( 'key' => 'invoice_postal_code',         'value' => __( 'Invoice Postal Code', 'advanced-form-integration' ),                          'required' => false ),
        array( 'key' => 'invoice_city',                'value' => __( 'Invoice City', 'advanced-form-integration' ),                                 'required' => false ),
        array( 'key' => 'invoice_country_code',        'value' => __( 'Invoice Country Code (ISO-2, defaults to NO)', 'advanced-form-integration' ), 'required' => false ),
        array( 'key' => 'corporate_identity_number',   'value' => __( 'Corporate Identity / VAT Number', 'advanced-form-integration' ),              'required' => false ),
        array( 'key' => 'note',                        'value' => __( 'Note', 'advanced-form-integration' ),                                         'required' => false ),
    );
}

/**
 * Map flat snake_case form-field keys -> Visma eAccounting TitleCase Customer keys.
 */
function adfoin_vismaeaccounting_field_map() {
    return array(
        'name'                      => 'Name',
        'email'                     => 'EmailAddress',
        'telephone'                 => 'Telephone',
        'mobile_phone'              => 'MobilePhone',
        'invoice_address1'          => 'InvoiceAddress1',
        'invoice_postal_code'       => 'InvoicePostalCode',
        'invoice_city'              => 'InvoiceCity',
        'invoice_country_code'      => 'InvoiceCountryCode',
        'corporate_identity_number' => 'CorporateIdentityNumber',
        'note'                      => 'Note',
    );
}

add_action( 'adfoin_vismaeaccounting_job_queue', 'adfoin_vismaeaccounting_job_queue', 10, 1 );

function adfoin_vismaeaccounting_job_queue( $data ) {
    adfoin_vismaeaccounting_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_vismaeaccounting_send_data( $record, $posted_data ) {
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

    $field_map = adfoin_vismaeaccounting_field_map();
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
        return; // Name is required by Visma eAccounting.
    }

    if ( empty( $customer['InvoiceCountryCode'] ) ) {
        $customer['InvoiceCountryCode'] = 'NO';
    }

    $visma = ADFOIN_VismaEAccounting::get_instance();
    if ( ! $visma->set_credentials( $cred_id ) ) {
        return;
    }

    $visma->vismaeaccounting_request( 'customers', 'POST', $customer, $record );
}
