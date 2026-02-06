<?php

class ADFOIN_ZohoRecruit extends Advanced_Form_Integration_OAuth2 {

    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';
    const token_endpoint         = 'https://accounts.zoho.com/oauth/v2/token';
    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    public $data_center;
    public $state;
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

        $option = (array) maybe_unserialize( get_option( 'adfoin_zohorecruit_keys' ) );

        if ( isset( $option['data_center'] ) ) {
            $this->data_center = $option['data_center'];
        }

        if ( isset( $option['client_id'] ) ) {
            $this->client_id = $option['client_id'];
        }

        if ( isset( $option['client_secret'] ) ) {
            $this->client_secret = $option['client_secret'];
        }

        if ( isset( $option['access_token'] ) ) {
            $this->access_token = $option['access_token'];
        }

        if ( isset( $option['refresh_token'] ) ) {
            $this->refresh_token = $option['refresh_token'];
        }

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_zohorecruit_credentials', array( $this, 'ajax_get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zohorecruit_credentials', array( $this, 'ajax_save_credentials' ), 10, 0 );
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

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/zohorecruit',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_oauth_callback' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_oauth_callback( $request ) {
        $params = $request->get_params();

        $code  = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';
        $state = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';

        if ( $code && $state ) {
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_zohorecruit_auth_redirect',
                    'code'    => $code,
                    'state'   => $state,
                ),
                admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohorecruit' )
            );

            wp_safe_redirect( $redirect_to );
            exit();
        }

        return array( 'status' => 'ok' );
    }

    public function settings_view( $current_tab ) {
        if ( 'zohorecruit' !== $current_tab ) {
            return;
        }

        $title = __( 'Zoho Recruit', 'advanced-form-integration' );
        $key   = 'zohorecruit';
        $arguments = wp_json_encode(
            array(
                'platform' => $key,
                'fields'   => array(
                    array(
                        'key'   => 'title',
                        'label' => __( 'Title', 'advanced-form-integration' ),
                        'hidden'=> false,
                    ),
                    array(
                        'key'   => 'dataCenter',
                        'label' => __( 'Data Center (com, eu, in, com.au, com.cn)', 'advanced-form-integration' ),
                        'hidden'=> false,
                    ),
                    array(
                        'key'   => 'clientId',
                        'label' => __( 'Client ID', 'advanced-form-integration' ),
                        'hidden'=> true,
                    ),
                    array(
                        'key'   => 'clientSecret',
                        'label' => __( 'Client Secret', 'advanced-form-integration' ),
                        'hidden'=> true,
                    ),
                    array(
                        'key'   => 'accessToken',
                        'label' => __( 'Access Token', 'advanced-form-integration' ),
                        'hidden'=> true,
                    ),
                    array(
                        'key'   => 'refreshToken',
                        'label' => __( 'Refresh Token', 'advanced-form-integration' ),
                        'hidden'=> true,
                    ),
                ),
            )
        );

        $instructions = sprintf(
            '<p>%s</p><ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
            esc_html__( 'Follow these steps to connect Zoho Recruit:', 'advanced-form-integration' ),
            esc_html__( 'In Zoho API Console, create a new Server-based application.', 'advanced-form-integration' ),
            esc_html__( 'Use the redirect URI shown below as the Authorized Redirect URI.', 'advanced-form-integration' ),
            esc_html__( 'Enter the generated Client ID and Client Secret into the fields provided.', 'advanced-form-integration' ),
            esc_html__( 'Select the correct data center (com, eu, in, com.au, com.cn).', 'advanced-form-integration' ),
            esc_html__( 'Click “Authorize” to grant the ZohoRecruit.modules.ALL scope.', 'advanced-form-integration' )
        );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function ajax_get_credentials() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $all_credentials = adfoin_read_credentials( 'zohorecruit' );

        wp_send_json_success( $all_credentials );
    }

    public function ajax_save_credentials() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $platform = sanitize_text_field( $_POST['platform'] ?? '' );

        if ( 'zohorecruit' === $platform ) {
            $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] ?? array() );
            adfoin_save_credentials( $platform, $data );
        }

        wp_send_json_success();
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
                            <?php $this->get_credentials_list(); ?>
                        </select>
                    </td>
                </tr>
                <tr v-if="action.task == 'create_candidate'">
                    <th scope="row"><?php esc_html_e( 'Organization', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[organizationId]" v-model="fielddata.organizationId">
                            <option value=""><?php esc_html_e( 'Select Organization...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in organizations" :value="index">{{ item }}</option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': organizationLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

    public function ajax_get_organizations() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        if ( ! $cred_id ) {
            wp_send_json_success( array() );
        }

        $this->set_credentials( $cred_id );

        $response = $this->zohorecruit_request( 'org', 'GET' );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        $organizations = array();

        if ( isset( $body['org_data'] ) && is_array( $body['org_data'] ) ) {
            foreach ( $body['org_data'] as $org ) {
                if ( isset( $org['org_id'], $org['org_name'] ) ) {
                    $organizations[ $org['org_id'] ] = $org['org_name'];
                }
            }
        }

        wp_send_json_success( $organizations );
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

        if ( 'adfoin_zohorecruit_auth_redirect' !== $action ) {
            return;
        }

        $code  = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

        if ( ! $code || ! $state ) {
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohorecruit' ) );
            exit();
        }

        $credentials = adfoin_read_credentials( 'zohorecruit' );

        foreach ( $credentials as $entry ) {
            if ( $state === ( $entry['id'] ?? '' ) ) {
                $this->data_center   = $entry['dataCenter'] ?? 'com';
                $this->client_id     = $entry['clientId'] ?? '';
                $this->client_secret = $entry['clientSecret'] ?? '';
                $this->state         = $state;
                break;
            }
        }

        if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohorecruit' ) );
            exit();
        }

        $this->request_token( $code );

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohorecruit' ) );
        exit();
    }

    protected function authorize( $scope = '' ) {
        $state = sanitize_text_field( $_GET['state'] ?? '' );

        if ( empty( $this->client_id ) || empty( $this->client_secret ) || empty( $state ) ) {
            return;
        }

        if ( empty( $scope ) ) {
            $scope = 'ZohoRecruit.modules.ALL';
        }

        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'access_type'   => 'offline',
            'redirect_uri'  => $this->get_redirect_uri(),
            'scope'         => $scope,
            'state'         => $state,
        );

        $auth_endpoint = $this->authorization_endpoint;

        if ( $this->data_center && 'com' !== $this->data_center ) {
            $auth_endpoint = str_replace( 'com', $this->data_center, $auth_endpoint );
        }

        $endpoint = add_query_arg( $data, $auth_endpoint );

        wp_redirect( esc_url_raw( $endpoint ) );
        exit();
    }

    protected function request_token( $authorization_code ) {
        $token_endpoint = $this->token_endpoint;

        if ( $this->data_center && 'com' !== $this->data_center ) {
            $token_endpoint = str_replace( 'com', $this->data_center, $token_endpoint );
        }

        $endpoint = add_query_arg(
            array(
                'code'         => $authorization_code,
                'redirect_uri' => $this->get_redirect_uri(),
                'grant_type'   => 'authorization_code',
            ),
            $token_endpoint
        );

        $response = wp_remote_post(
            esc_url_raw( $endpoint ),
            array(
                'headers' => array(
                    'Authorization' => $this->get_http_authorization_header( 'basic' ),
                ),
            )
        );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
        }

        if ( isset( $body['refresh_token'] ) ) {
            $this->refresh_token = $body['refresh_token'];
        }

        $this->save_data();
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return;
        }

        $refresh_endpoint = $this->refresh_token_endpoint;

        if ( $this->data_center && 'com' !== $this->data_center ) {
            $refresh_endpoint = str_replace( 'com', $this->data_center, $refresh_endpoint );
        }

        $endpoint = add_query_arg(
            array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
            ),
            $refresh_endpoint
        );

        $response = wp_remote_post(
            esc_url_raw( $endpoint ),
            array(
                'headers' => array(
                    'Authorization' => $this->get_http_authorization_header( 'basic' ),
                ),
            )
        );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
        }

        if ( isset( $body['refresh_token'] ) ) {
            $this->refresh_token = $body['refresh_token'];
        }

        $this->save_data();
    }

    protected function save_data() {
        $credentials = adfoin_read_credentials( 'zohorecruit' );

        foreach ( $credentials as &$entry ) {
            if ( ( $entry['id'] ?? '' ) === $this->state ) {
                $entry['accessToken']  = $this->access_token;
                $entry['refreshToken'] = $this->refresh_token;
            }
        }

        adfoin_save_credentials( 'zohorecruit', $credentials );

        update_option(
            'adfoin_zohorecruit_keys',
            maybe_serialize(
                array(
                    'data_center'   => $this->data_center,
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'access_token'  => $this->access_token,
                    'refresh_token' => $this->refresh_token,
                )
            )
        );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohorecruit' );
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );

        if ( empty( $credentials ) ) {
            return;
        }

        $this->data_center   = $credentials['dataCenter'] ?? 'com';
        $this->client_id     = $credentials['clientId'] ?? '';
        $this->client_secret = $credentials['clientSecret'] ?? '';
        $this->access_token  = $credentials['accessToken'] ?? '';
        $this->refresh_token = $credentials['refreshToken'] ?? '';
        $this->state         = $credentials['id'] ?? '';
    }

    public function get_credentials_by_id( $cred_id ) {
        $all_credentials = adfoin_read_credentials( 'zohorecruit' );

        if ( empty( $all_credentials ) || ! is_array( $all_credentials ) ) {
            return array();
        }

        foreach ( $all_credentials as $single ) {
            if ( $cred_id && ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }

        return $all_credentials[0];
    }

    public function get_credentials_list() {
        $credentials = adfoin_read_credentials( 'zohorecruit' );

        foreach ( $credentials as $option ) {
            printf(
                '<option value="%1$s">%2$s</option>',
                esc_attr( $option['id'] ),
                esc_html( $option['title'] )
            );
        }
    }

    public function zohorecruit_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $organization_id = '' ) {
        static $refreshed = false;

        $base_url = 'https://recruit.zoho.com/recruit/v2/';

        if ( $this->data_center && 'com' !== $this->data_center ) {
            if ( 'com.au' === $this->data_center ) {
                $base_url = 'https://recruit.zoho.com.au/recruit/v2/';
            } elseif ( 'com.cn' === $this->data_center ) {
                $base_url = 'https://recruit.zoho.com.cn/recruit/v2/';
            } else {
                $base_url = str_replace( 'com', $this->data_center, $base_url );
            }
        }

        $url = $base_url . ltrim( $endpoint, '/' );

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
        );

        if ( $organization_id ) {
            $args['headers']['X-ZOHO-RECRUIT-ORG'] = $organization_id;
        }

        if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        } elseif ( ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );

        if ( 401 === wp_remote_retrieve_response_code( $response ) && ! $refreshed ) {
            $this->refresh_token();
            $refreshed = true;

            $args['headers']['Authorization'] = 'Zoho-oauthtoken ' . $this->access_token;
            $response = wp_remote_request( esc_url_raw( $url ), $args );
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        return $response;
    }
}

$adfoin_zohorecruit = ADFOIN_ZohoRecruit::get_instance();

function adfoin_zohorecruit_fields() {
    return array(
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '', 'required' => true, 'controlType' => 'text' ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => '', 'required' => false, 'controlType' => 'text' ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ), 'description' => '', 'required' => false, 'controlType' => 'text' ),
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

function adfoin_zohorecruit_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'zohorecruit' || ( $data['task'] ?? '' ) !== 'create_candidate' ) {
        return;
    }

    adfoin_zohorecruit_send_data( $data['record'], $data['posted_data'] );
}

add_action( 'adfoin_job_queue', 'adfoin_zohorecruit_job_queue', 10, 1 );

function adfoin_zohorecruit_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
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
    $recruit->set_credentials( $cred_id );

    $payload = array( 'data' => array( $candidate ) );

    $recruit->zohorecruit_request( 'Candidates', 'POST', $payload, $record, $org_id );
}
