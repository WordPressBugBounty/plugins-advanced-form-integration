<?php

/**
 * Salesforce Account Engagement (Pardot) — Upsert Prospect via
 * POST /api/v5/objects/prospects/do/upsertLatestByEmail.
 *
 * Multi-account OAuth via AFI's OAuth Manager popup flow — same standard
 * Salesforce OAuth2 (login.salesforce.com) already used by the plain
 * Salesforce/Jungo actions in this plugin, PLUS a mandatory
 * `Pardot-Business-Unit-Id` header required on every call (confirmed via
 * developer.salesforce.com Account Engagement API docs — omitting it
 * returns HTTP 400 even with a valid token). The Connected App needs the
 * `pardot_api` OAuth scope, without which non-JWT OAuth flows are rejected.
 * Custom fields use Salesforce's `__c` suffix convention.
 *
 * @link https://developer.salesforce.com/docs/marketing/pardot/guide/prospect-v5.html
 * @link https://developer.salesforce.com/docs/marketing/pardot/guide/authentication.html
 */

class ADFOIN_Pardot extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'pardot';
    protected $business_unit_id = '';

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
        add_action( 'wp_ajax_adfoin_get_pardot_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_pardot_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_pardot_connection', array( $this, 'ajax_test_connection' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['pardot'] = array(
            'title' => __( 'Pardot (Account Engagement)', 'advanced-form-integration' ),
            'tasks' => array( 'upsert_prospect' => __( 'Create/Update Prospect', 'advanced-form-integration' ) ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['pardot'] = __( 'Pardot (Account Engagement)', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'pardot' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array( 'name' => 'client_id', 'label' => __( 'Consumer Key', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true, 'show_in_table' => true ),
            array( 'name' => 'client_secret', 'label' => __( 'Consumer Secret', 'advanced-form-integration' ), 'type' => 'text', 'required' => false, 'mask' => true, 'show_in_table' => true, 'placeholder' => __( 'Leave blank to keep current', 'advanced-form-integration' ) ),
            array( 'name' => 'business_unit_id', 'label' => __( 'Business Unit ID', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'show_in_table' => true, 'placeholder' => '0Uv...' ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'In Salesforce Setup, create a Connected App (App Manager > New Connected App) with OAuth enabled and the "pardot_api" scope added.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Callback URL below into the Connected App.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Find your Business Unit ID by searching "Business Unit Setup" in Salesforce Setup.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Copy the Consumer Key/Secret and Business Unit ID into the Add Account form here, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array( 'show_status' => true, 'enable_test' => true, 'modal_title' => __( 'Connect Pardot', 'advanced-form-integration' ), 'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ) );

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'pardot', __( 'Pardot (Account Engagement)', 'advanced-form-integration' ), $fields, $instructions, $config );
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="pardot-action-template">
            <table class="form-table">
                <tr valign="top" v-if="action.task == 'upsert_prospect'">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
                </tr>
                <tr valign="top" class="alternate" v-if="action.task == 'upsert_prospect'">
                    <td scope="row-title"><label><?php esc_attr_e( 'Pardot Account', 'advanced-form-integration' ); ?></label></td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=pardot' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'upsert_prospect', 'Pardot [PRO]', 'custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    public function create_webhook_route() {
        register_rest_route( 'advancedformintegration', '/pardot', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] )  ? trim( $params['code'] )  : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        $context = self::consume_oauth_state( $state, 'pardot' );
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
        foreach ( adfoin_read_credentials( 'pardot' ) as $entry ) {
            if ( ( $entry['id'] ?? '' ) === $cred_id ) {
                $this->client_id         = $entry['client_id']         ?? '';
                $this->client_secret     = $entry['client_secret']     ?? '';
                $this->business_unit_id  = $entry['business_unit_id']  ?? '';
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

        $platform    = 'pardot';
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

        $id               = isset( $_POST['id'] )               ? sanitize_text_field( wp_unslash( $_POST['id'] ) )               : '';
        $title            = isset( $_POST['title'] )            ? sanitize_text_field( wp_unslash( $_POST['title'] ) )            : '';
        $client_id        = isset( $_POST['client_id'] )        ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) )        : '';
        $client_secret    = isset( $_POST['client_secret'] )    ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) )    : '';
        $business_unit_id = isset( $_POST['business_unit_id'] ) ? sanitize_text_field( wp_unslash( $_POST['business_unit_id'] ) ) : '';

        if ( empty( $id ) ) $id = wp_generate_uuid4();

        $existing = null;
        foreach ( $credentials as $cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) { $existing = $cred; break; }
        }

        if ( $existing ) {
            if ( '' === $client_id && ! empty( $existing['client_id'] ) )         $client_id = $existing['client_id'];
            if ( '' === $client_secret && ! empty( $existing['client_secret'] ) ) $client_secret = $existing['client_secret'];
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Consumer Key and Consumer Secret are required.', 'advanced-form-integration' ) ) );
        }

        if ( '' === $business_unit_id ) {
            wp_send_json_error( array( 'message' => __( 'Business Unit ID is required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id' => $id, 'title' => $title, 'client_id' => $client_id, 'client_secret' => $client_secret,
            'business_unit_id' => $business_unit_id, 'access_token' => '', 'refresh_token' => '',
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( ( $cred['id'] ?? '' ) === $id ) {
                $same = ( ( $cred['client_id'] ?? '' ) === $client_id ) && ( ( $cred['client_secret'] ?? '' ) === $client_secret );
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

        if ( ! $found ) $credentials[] = $new_data;

        adfoin_save_credentials( $platform, $credentials );

        $auth_url = add_query_arg( array(
            'response_type' => 'code',
            'client_id'     => $client_id,
            'redirect_uri'  => $this->get_redirect_uri(),
            'state'         => self::issue_oauth_state( 'pardot', $id ),
        ), 'https://login.salesforce.com/services/oauth2/authorize' );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function ajax_test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->pardot_request( 'prospects', 'GET', array( 'limit' => 1 ) );
        } );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/pardot' );
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post( 'https://login.salesforce.com/services/oauth2/token', array(
            'timeout' => 30,
            'body'    => array(
                'code' => $authorization_code, 'client_id' => $this->client_id, 'client_secret' => $this->client_secret,
                'redirect_uri' => $this->get_redirect_uri(), 'grant_type' => 'authorization_code',
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

        $response = wp_remote_post( 'https://login.salesforce.com/services/oauth2/token', array(
            'timeout' => 30,
            'body'    => array(
                'refresh_token' => $this->refresh_token, 'client_id' => $this->client_id,
                'client_secret' => $this->client_secret, 'grant_type' => 'refresh_token',
            ),
        ) );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code || 400 === $code ) {
            $this->access_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } elseif ( 200 === $code && ! empty( $body['access_token'] ) ) {
            $this->apply_token_response( $body );
        }
        $this->save_data();

        return $response;
    }

    protected function save_data() {
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential( array( 'business_unit_id' => $this->business_unit_id ) );
        }
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) return false;

        $this->cred_id           = $credentials['id'] ?? $cred_id;
        $this->client_id         = $credentials['client_id']         ?? '';
        $this->client_secret     = $credentials['client_secret']     ?? '';
        $this->access_token      = $credentials['access_token']      ?? '';
        $this->refresh_token     = $credentials['refresh_token']     ?? '';
        $this->business_unit_id  = $credentials['business_unit_id']  ?? '';

        return true;
    }

    public function get_credentials_by_id( $cred_id ) {
        if ( ! $cred_id ) return array();
        foreach ( adfoin_read_credentials( 'pardot' ) as $single ) {
            if ( ( $single['id'] ?? '' ) === $cred_id ) return $single;
        }
        return array();
    }

    public function get_credentials_list() {
        foreach ( adfoin_read_credentials( 'pardot' ) as $option ) {
            printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ?? '' ) );
        }
    }

    public function pardot_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        $url    = 'https://pi.pardot.com/api/v5/objects/' . ltrim( $endpoint, '/' );
        $method = strtoupper( $method );

        $args = array(
            'timeout' => 30,
            'method'  => $method,
            'headers' => array(
                'Content-Type'            => 'application/json',
                'Pardot-Business-Unit-Id' => $this->business_unit_id,
            ),
        );
        if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( 'GET' === $method && ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
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

ADFOIN_Pardot::get_instance();

add_action( 'wp_ajax_adfoin_get_pardot_fields', 'adfoin_get_pardot_fields' );
function adfoin_get_pardot_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'email',      'value' => 'Email', 'required' => true ),
        array( 'key' => 'firstName',  'value' => 'First Name' ),
        array( 'key' => 'lastName',   'value' => 'Last Name' ),
        array( 'key' => 'company',    'value' => 'Company' ),
        array( 'key' => 'campaignId', 'value' => 'Campaign ID', 'description' => 'Optional — new prospects default to the account\'s oldest campaign if left blank' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_pardot_build_prospect( $fields ) {
    $map = array( 'email' => 'email', 'firstName' => 'firstName', 'lastName' => 'lastName', 'company' => 'company', 'campaignId' => 'campaignId' );
    $prospect = array();
    foreach ( $map as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $prospect[ $remote ] = $fields[ $local ];
    }
    return $prospect;
}

add_action( 'adfoin_pardot_job_queue', 'adfoin_pardot_job_queue', 10, 1 );
function adfoin_pardot_job_queue( $data ) {
    adfoin_pardot_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_pardot_send_data( $record, $posted_data ) {
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
    if ( $record['task'] !== 'upsert_prospect' ) return;
    if ( empty( $fields['email'] ) ) return;

    $pardot = ADFOIN_Pardot::get_instance();
    if ( ! $pardot->set_credentials( $cred_id ) ) {
        return;
    }

    $pardot->pardot_request( 'prospects/do/upsertLatestByEmail', 'POST', adfoin_pardot_build_prospect( $fields ), $record );
}
