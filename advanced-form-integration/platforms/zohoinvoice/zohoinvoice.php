<?php

class ADFOIN_ZohoInvoice extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'zohoinvoice';

    const authorization_endpoint = 'https://accounts.zoho.com/oauth/v2/auth';
    const token_endpoint         = 'https://accounts.zoho.com/oauth/v2/token';
    const refresh_token_endpoint = 'https://accounts.zoho.com/oauth/v2/token';

    public $data_center = 'com';
    public $cred_id     = '';
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
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
        add_action( 'wp_ajax_adfoin_get_zohoinvoice_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zohoinvoice_credentials', array( $this, 'save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_zohoinvoice_connection', array( $this, 'test_connection' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_zohoinvoice_organizations', array( $this, 'ajax_get_organizations' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['zohoinvoice'] = array(
            'title' => __( 'Zoho Invoice', 'advanced-form-integration' ),
            'tasks' => array(
                'upsert_customer'         => __( 'Customer (search or create)', 'advanced-form-integration' ),
                'upsert_item'             => __( 'Item (search or create)', 'advanced-form-integration' ),
                'create_invoice'          => __( 'Invoice', 'advanced-form-integration' ),
                'create_estimate'         => __( 'Estimate', 'advanced-form-integration' ),
                'create_recurring_invoice'=> __( 'Recurring Invoice', 'advanced-form-integration' ),
                'create_customer_payment' => __( 'Customer Payment', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['zohoinvoice'] = __( 'Zoho Invoice', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'zohoinvoice' !== $current_tab ) {
            return;
        }

        $redirect_uri = $this->get_redirect_uri();

        $fields = array(
            array(
                'name' => 'client_id', 'label' => __( 'Client ID', 'advanced-form-integration' ),
                'type' => 'text', 'required' => true, 'mask' => true, 'show_in_table' => true,
            ),
            array(
                'name' => 'client_secret', 'label' => __( 'Client Secret', 'advanced-form-integration' ),
                'type' => 'text', 'required' => false, 'mask' => true, 'show_in_table' => true,
                'placeholder' => __( 'Leave blank to keep current', 'advanced-form-integration' ),
            ),
            array(
                'name' => 'data_center', 'label' => __( 'Data Center', 'advanced-form-integration' ),
                'type' => 'select', 'required' => false, 'show_in_table' => false,
                'options' => array(
                    'com' => 'zoho.com', 'eu' => 'zoho.eu', 'in' => 'zoho.in',
                    'com.au' => 'zoho.com.au', 'com.cn' => 'zoho.com.cn',
                    'jp' => 'zoho.jp', 'sa' => 'zoho.sa',
                ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Create a Server-based application in %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Use the redirect URI below as Authorized Redirect URI.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret into the Add Account form, choose data center, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'enable_test' => true,
            'modal_title' => __( 'Connect Zoho Invoice', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'zohoinvoice', 'Zoho Invoice', $fields, $instructions, $config );
    }

    public function register_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohoinvoice', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params    = $request->get_params();
        $code      = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $raw_state = isset( $params['state'] ) ? trim( $params['state'] ) : '';

        if ( ! $code || ! $raw_state ) {
            return;
        }

        $context = self::consume_oauth_state( $raw_state, 'zohoinvoice' );
        $cred_id = $context ? $context['cred_id'] : '';

        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';

        if ( ! $cred_id ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false,
                __( 'OAuth state invalid or expired. Please try again.', 'advanced-form-integration' ) );
            exit;
        }

        $this->cred_id = $cred_id;
        $credentials   = adfoin_read_credentials( 'zohoinvoice' );
        foreach ( $credentials as $cred ) {
            if ( $cred['id'] == $cred_id ) {
                $this->data_center   = $cred['data_center'] ?? 'com';
                $this->client_id     = $cred['client_id'] ?? '';
                $this->client_secret = $cred['client_secret'] ?? '';
                $this->update_oauth_endpoints( $this->data_center );
            }
        }

        $response = $this->request_token( $code );

        $success = false;
        $message = __( 'Unknown error', 'advanced-form-integration' );

        if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $body['access_token'] ) ) {
                $success = true;
                $message = __( 'Connected successfully!', 'advanced-form-integration' );
            } else {
                $message = isset( $body['error'] ) ? $body['error'] : $message;
            }
        } else {
            $message = is_wp_error( $response ) ? $response->get_error_message() : $message;
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        if ( 'adfoin_zohoinvoice_auth_redirect' !== $action ) {
            return;
        }

        $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
        $context = self::consume_oauth_state( $state, 'zohoinvoice' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( $code && $cred_id ) {
            $this->cred_id = $cred_id;
            $credentials = adfoin_read_credentials( 'zohoinvoice' );
            foreach ( $credentials as $cred ) {
                if ( $cred['id'] == $cred_id ) {
                    $this->data_center   = $cred['data_center'] ?? 'com';
                    $this->client_id     = $cred['client_id'] ?? '';
                    $this->client_secret = $cred['client_secret'] ?? '';
                    $this->update_oauth_endpoints( $this->data_center );
                }
            }
            $this->request_token( $code );

            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
        }
    }

    protected function request_token( $authorization_code ) {
        $response = wp_remote_post(
            esc_url_raw( $this->token_endpoint ),
            array(
                'timeout' => 30,
                'body'    => array(
                    'code'          => $authorization_code,
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'redirect_uri'  => $this->get_redirect_uri(),
                    'grant_type'    => 'authorization_code',
                ),
            )
        );

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 401 === $code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            $this->apply_token_response( $body );
        }
        $this->save_data();
        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return;
        }
        $response = wp_remote_post(
            esc_url_raw( $this->refresh_token_endpoint ),
            array(
                'timeout' => 30,
                'body'    => array(
                    'refresh_token' => $this->refresh_token,
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'grant_type'    => 'refresh_token',
                ),
            )
        );
        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 401 === $code ) {
            $this->access_token  = null;
            $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } else {
            $this->apply_token_response( $body );
        }
        $this->save_data();
    }

    protected function save_data() {
        $this->persist_token_to_credential( array(
            'data_center'   => $this->data_center,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ) );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohoinvoice' );
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
        $base = $this->get_accounts_base( $data_center ? $data_center : $this->data_center );
        return trailingslashit( $base ) . 'oauth/v2/' . $path;
    }

    protected function update_oauth_endpoints( $data_center ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $this->token_endpoint         = $this->get_oauth_endpoint( 'token', $data_center );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    protected function get_apis_base_url() {
        $map = array(
            'com'    => 'https://www.zohoapis.com/invoice/v3/',
            'eu'     => 'https://www.zohoapis.eu/invoice/v3/',
            'in'     => 'https://www.zohoapis.in/invoice/v3/',
            'com.cn' => 'https://www.zohoapis.com.cn/invoice/v3/',
            'com.au' => 'https://www.zohoapis.com.au/invoice/v3/',
            'jp'     => 'https://www.zohoapis.jp/invoice/v3/',
            'sa'     => 'https://www.zohoapis.sa/invoice/v3/',
        );
        return $map[ $this->data_center ] ?? $map['com'];
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) {
            return;
        }
        $this->data_center   = $credentials['data_center']   ?? 'com';
        $this->client_id     = $credentials['client_id']     ?? '';
        $this->client_secret = $credentials['client_secret'] ?? '';
        $this->access_token  = $credentials['access_token']  ?? '';
        $this->refresh_token = $credentials['refresh_token'] ?? '';
        $this->cred_id       = $credentials['id'] ?? '';
        $this->update_oauth_endpoints( $this->data_center );
    }

    public function test_connection() {
        $this->run_test_connection_ajax( function () {
            return $this->zohoinvoice_request( 'organizations' );
        } );
    }

    public function get_credentials() {
        adfoin_verify_nonce();
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_verify_nonce();

        $platform    = 'zohoinvoice';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';
        $data_center   = isset( $_POST['data_center'] ) ? sanitize_text_field( wp_unslash( $_POST['data_center'] ) ) : 'com';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $existing = null;
        foreach ( $credentials as $cred_check ) {
            if ( isset( $cred_check['id'] ) && $cred_check['id'] === $id ) {
                $existing = $cred_check;
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
            wp_send_json_error( array(
                'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ),
            ) );
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
                if ( isset( $cred['client_id'], $cred['client_secret'], $cred['data_center'] ) &&
                    $cred['client_id'] === $client_id &&
                    $cred['client_secret'] === $client_secret &&
                    $cred['data_center'] === $data_center ) {
                    $new_data['access_token']  = $cred['access_token'] ?? '';
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

        $redirect_uri  = $this->get_redirect_uri();
        $scope         = 'ZohoInvoice.fullaccess.all';
        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );

        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'access_type'   => 'offline',
                'redirect_uri'  => $redirect_uri,
                'scope'         => $scope,
                'state'         => self::issue_oauth_state( 'zohoinvoice', $id ),
            ),
            $auth_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function get_credentials_by_id( $cred_id ) {
        $all = adfoin_read_credentials( 'zohoinvoice' );
        if ( ! is_array( $all ) || empty( $all ) ) {
            return array();
        }
        foreach ( $all as $single ) {
            if ( $cred_id && ( $single['id'] ?? '' ) === $cred_id ) {
                return $single;
            }
        }
        return $all[0];
    }

    public function get_credentials_list() {
        $credentials = adfoin_read_credentials( 'zohoinvoice' );
        if ( ! is_array( $credentials ) ) {
            return;
        }
        foreach ( $credentials as $option ) {
            printf(
                '<option value="%1$s">%2$s</option>',
                esc_attr( $option['id'] ),
                esc_html( $option['title'] )
            );
        }
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="zohoinvoice-action-template">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Zoho Invoice Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchOrganizations">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohoinvoice' ); ?>" target="_blank">
                            <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Organization', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[organizationId]" v-model="fielddata.organizationId">
                            <option value=""><?php esc_html_e( 'Select Organization...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in organizations" :value="index">{{ item }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': organizationLoading}"></div>
                    </td>
                </tr>

                <tr v-if="['create_invoice','create_estimate','create_recurring_invoice'].indexOf(action.task) !== -1">
                    <th scope="row"><?php esc_attr_e( 'Line Items Source', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[line_items_source]" v-model="fielddata.line_items_source">
                            <option value="manual"><?php esc_html_e( 'Manual JSON / Mapped Field', 'advanced-form-integration' ); ?></option>
                            <option value="woocommerce"><?php esc_html_e( 'WooCommerce Order Items (auto)', 'advanced-form-integration' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Choose "WooCommerce Order Items" when this integration is triggered by a WooCommerce order — line items will be built automatically.', 'advanced-form-integration' ); ?></p>
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
        adfoin_verify_nonce();
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( ! $cred_id ) {
            wp_send_json_success( array() );
        }
        $this->set_credentials( $cred_id );
        $response = $this->zohoinvoice_request( 'organizations', 'GET' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $organizations = array();
        if ( isset( $body['organizations'] ) && is_array( $body['organizations'] ) ) {
            foreach ( $body['organizations'] as $org ) {
                if ( isset( $org['organization_id'], $org['name'] ) ) {
                    $organizations[ $org['organization_id'] ] = $org['name'];
                }
            }
        }
        wp_send_json_success( $organizations );
    }

    public function zohoinvoice_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $organization_id = '' ) {
        static $refreshed = false;

        $base_url = $this->get_apis_base_url();
        $url      = $base_url . ltrim( $endpoint, '/' );

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'timeout' => 30,
        );

        if ( $organization_id ) {
            $url = add_query_arg( 'organization_id', $organization_id, $url );
            $args['headers']['X-com-zoho-invoice-organizationid'] = $organization_id;
        }

        if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
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

    /* ---------- Data helpers (mirror Inventory's pattern) ---------- */

    public function build_customer_payload( $field_data, $posted_data ) {
        $get = function( $key ) use ( $field_data, $posted_data ) {
            if ( empty( $field_data[ $key ] ) ) {
                return '';
            }
            return adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        };

        $payload = array();

        $contact_name = $get( 'contact_name' );
        $first_name   = $get( 'first_name' );
        $last_name    = $get( 'last_name' );
        $email        = $get( 'email' );
        $phone        = $get( 'phone' );
        $company_name = $get( 'company_name' );
        $website      = $get( 'website' );

        if ( ! $contact_name ) {
            $contact_name = trim( $first_name . ' ' . $last_name );
        }
        if ( ! $contact_name && $company_name ) {
            $contact_name = $company_name;
        }
        if ( ! $contact_name && $email ) {
            $contact_name = $email;
        }

        if ( $contact_name ) { $payload['contact_name'] = $contact_name; }
        if ( $company_name ) { $payload['company_name'] = $company_name; }
        if ( $website )      { $payload['website']      = $website; }

        $contact_person = array_filter( array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone'      => $phone,
        ) );
        if ( ! empty( $contact_person ) ) {
            $payload['contact_persons'] = array( $contact_person );
        }

        $billing = $this->build_address( 'billing', $field_data, $posted_data );
        if ( ! empty( $billing ) ) { $payload['billing_address'] = $billing; }
        $shipping = $this->build_address( 'shipping', $field_data, $posted_data );
        if ( ! empty( $shipping ) ) { $payload['shipping_address'] = $shipping; }

        $payload['contact_type'] = 'customer';

        return $payload;
    }

    protected function build_address( $prefix, $field_data, $posted_data ) {
        $map = array(
            "{$prefix}_address" => 'address',
            "{$prefix}_street2" => 'street2',
            "{$prefix}_city"    => 'city',
            "{$prefix}_state"   => 'state',
            "{$prefix}_zip"     => 'zip',
            "{$prefix}_country" => 'country',
            "{$prefix}_phone"   => 'phone',
        );
        $address = array();
        foreach ( $map as $form_key => $api_key ) {
            if ( empty( $field_data[ $form_key ] ) ) {
                continue;
            }
            $value = adfoin_get_parsed_values( $field_data[ $form_key ], $posted_data );
            if ( '' !== $value && null !== $value ) {
                $address[ $api_key ] = $value;
            }
        }
        return $address;
    }

    public function find_customer( $customer_payload, $org_id, $record = array() ) {
        $email = $customer_payload['contact_persons'][0]['email'] ?? '';
        $name  = $customer_payload['contact_name'] ?? '';

        $attempts = array();
        if ( $email ) { $attempts[] = array( 'email' => $email ); }
        if ( $name )  { $attempts[] = array( 'contact_name' => $name ); }

        foreach ( $attempts as $params ) {
            $endpoint = add_query_arg( $params, 'contacts' );
            $response = $this->zohoinvoice_request( $endpoint, 'GET', array(), $record, $org_id );
            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $body['contacts'][0]['contact_id'] ) ) {
                    return $body['contacts'][0];
                }
            }
        }
        return array();
    }

    public function find_or_create_customer( $customer_payload, $org_id, $record = array() ) {
        $existing = $this->find_customer( $customer_payload, $org_id, $record );
        if ( ! empty( $existing['contact_id'] ) ) {
            return $existing;
        }
        $create = $this->zohoinvoice_request( 'contacts', 'POST', $customer_payload, $record, $org_id );
        if ( is_wp_error( $create ) ) {
            return array();
        }
        $body = json_decode( wp_remote_retrieve_body( $create ), true );
        return $body['contact'] ?? array();
    }

    public function find_or_create_item( $item_payload, $org_id, $record = array() ) {
        $sku  = $item_payload['sku']  ?? '';
        $name = $item_payload['name'] ?? '';

        $attempts = array();
        if ( $sku )  { $attempts[] = array( 'sku' => $sku ); }
        if ( $name ) { $attempts[] = array( 'name' => $name ); }

        foreach ( $attempts as $params ) {
            $endpoint = add_query_arg( $params, 'items' );
            $response = $this->zohoinvoice_request( $endpoint, 'GET', array(), $record, $org_id );
            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $body['items'][0]['item_id'] ) ) {
                    return $body['items'][0];
                }
            }
        }

        if ( empty( $name ) ) {
            return array();
        }
        if ( empty( $item_payload['item_type'] ) ) {
            $item_payload['item_type'] = 'goods';
        }

        $create = $this->zohoinvoice_request( 'items', 'POST', $item_payload, $record, $org_id );
        if ( is_wp_error( $create ) ) {
            return array();
        }
        $body = json_decode( wp_remote_retrieve_body( $create ), true );
        return $body['item'] ?? array();
    }

    public function build_line_items( $entries, $org_id, $record = array() ) {
        $lines = array();
        foreach ( $entries as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }
            $name = $entry['item_name'] ?? ( $entry['name'] ?? '' );
            if ( ! $name && empty( $entry['sku'] ) ) {
                continue;
            }
            $item_payload = array( 'name' => $name );
            if ( ! empty( $entry['sku'] ) )         { $item_payload['sku'] = $entry['sku']; }
            if ( isset( $entry['rate'] ) )          { $item_payload['rate'] = (float) $entry['rate']; }
            if ( ! empty( $entry['description'] ) ) { $item_payload['description'] = $entry['description']; }

            $item = $this->find_or_create_item( $item_payload, $org_id, $record );
            if ( empty( $item['item_id'] ) ) {
                continue;
            }
            $line = array(
                'item_id'  => $item['item_id'],
                'rate'     => isset( $entry['rate'] ) ? (float) $entry['rate'] : ( $item['rate'] ?? 0 ),
                'quantity' => isset( $entry['quantity'] ) ? (float) $entry['quantity'] : 1,
                'name'     => $item['name'] ?? $name,
            );
            if ( ! empty( $entry['description'] ) ) { $line['description'] = $entry['description']; }
            if ( ! empty( $entry['tax_id'] ) )      { $line['tax_id'] = $entry['tax_id']; }
            $lines[] = $line;
        }
        return $lines;
    }

    public function parse_line_items( $line_items_json ) {
        if ( empty( $line_items_json ) ) {
            return array();
        }
        $decoded = json_decode( $line_items_json, true );
        if ( ! is_array( $decoded ) ) {
            return array();
        }
        return $decoded;
    }

    public function build_wc_line_item_entries( $posted_data ) {
        $ids        = $posted_data['items_id']       ?? null;
        $names      = $posted_data['items_name']     ?? array();
        $skus       = $posted_data['items_sku']      ?? array();
        $prices     = $posted_data['items_price']    ?? array();
        $quantities = $posted_data['items_quantity'] ?? array();

        if ( ! is_array( $ids ) && '' !== $ids && null !== $ids ) {
            $ids        = array( $ids );
            $names      = array( $names );
            $skus       = array( $skus );
            $prices     = array( $prices );
            $quantities = array( $quantities );
        }
        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return array();
        }

        $entries = array();
        foreach ( $ids as $idx => $_pid ) {
            $name = $names[ $idx ]      ?? '';
            $sku  = $skus[ $idx ]       ?? '';
            $rate = $prices[ $idx ]     ?? 0;
            $qty  = $quantities[ $idx ] ?? 1;
            $entry = array(
                'item_name' => $name,
                'rate'      => $rate,
                'quantity'  => $qty,
            );
            if ( $sku ) { $entry['sku'] = $sku; }
            $entries[] = $entry;
        }
        return $entries;
    }
}

$adfoin_zohoinvoice = ADFOIN_ZohoInvoice::get_instance();

function adfoin_zohoinvoice_job_queue( $data ) {
    adfoin_zohoinvoice_send_data( $data['record'], $data['posted_data'] );
}
// AFI's dispatcher routes per-platform via `adfoin_<provider>_job_queue` — the
// generic `adfoin_job_queue` action is never fired, so registering there kept
// this platform permanently on the synchronous fallback path.
add_action( 'adfoin_zohoinvoice_job_queue', 'adfoin_zohoinvoice_job_queue', 10, 1 );

function adfoin_zohoinvoice_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $org_id     = $field_data['organizationId'] ?? '';
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || ! $org_id ) {
        return;
    }

    $inv = ADFOIN_ZohoInvoice::get_instance();
    $inv->set_credentials( $cred_id );

    switch ( $task ) {
        case 'upsert_customer':
            $payload = $inv->build_customer_payload( $field_data, $posted_data );
            if ( empty( $payload['contact_name'] ) ) {
                return;
            }
            $inv->find_or_create_customer( $payload, $org_id, $record );
            break;

        case 'upsert_item':
            $name = adfoin_get_parsed_values( $field_data['item_name'] ?? '', $posted_data );
            if ( ! $name ) {
                return;
            }
            $item_payload = array( 'name' => $name );
            $rate = adfoin_get_parsed_values( $field_data['item_rate'] ?? '', $posted_data );
            if ( '' !== $rate ) { $item_payload['rate'] = (float) $rate; }
            $sku = adfoin_get_parsed_values( $field_data['item_sku'] ?? '', $posted_data );
            if ( $sku ) { $item_payload['sku'] = $sku; }
            $description = adfoin_get_parsed_values( $field_data['item_description'] ?? '', $posted_data );
            if ( $description ) { $item_payload['description'] = $description; }
            $inv->find_or_create_item( $item_payload, $org_id, $record );
            break;

        case 'create_customer_payment':
            adfoin_zohoinvoice_handle_payment( $inv, $field_data, $posted_data, $org_id, $record );
            break;

        case 'create_invoice':
        case 'create_estimate':
        case 'create_recurring_invoice':
            adfoin_zohoinvoice_handle_document( $inv, $task, $field_data, $posted_data, $org_id, $record );
            break;
    }
}

function adfoin_zohoinvoice_handle_payment( $inv, $field_data, $posted_data, $org_id, $record ) {
    $customer_payload = $inv->build_customer_payload( $field_data, $posted_data );
    if ( empty( $customer_payload['contact_name'] ) ) {
        return;
    }
    $customer = $inv->find_or_create_customer( $customer_payload, $org_id, $record );
    if ( empty( $customer['contact_id'] ) ) {
        return;
    }
    $payload = array(
        'customer_id'      => $customer['contact_id'],
        'payment_mode'     => adfoin_get_parsed_values( $field_data['payment_mode'] ?? '', $posted_data ),
        'amount'           => (float) adfoin_get_parsed_values( $field_data['payment_amount'] ?? '', $posted_data ),
        'date'             => adfoin_get_parsed_values( $field_data['payment_date'] ?? '', $posted_data ),
        'reference_number' => adfoin_get_parsed_values( $field_data['payment_reference'] ?? '', $posted_data ),
    );
    $invoice_id = adfoin_get_parsed_values( $field_data['invoice_id'] ?? '', $posted_data );
    if ( $invoice_id ) {
        $payload['invoice_id'] = $invoice_id;
    }
    $payload = array_filter( $payload, function( $v ) { return $v !== '' && $v !== null; } );
    if ( empty( $payload['amount'] ) || empty( $payload['payment_mode'] ) ) {
        return;
    }
    $inv->zohoinvoice_request( 'customerpayments', 'POST', $payload, $record, $org_id );
}

function adfoin_zohoinvoice_handle_document( $inv, $task, $field_data, $posted_data, $org_id, $record ) {
    $customer_payload = $inv->build_customer_payload( $field_data, $posted_data );
    if ( empty( $customer_payload['contact_name'] ) ) {
        return;
    }
    $customer = $inv->find_or_create_customer( $customer_payload, $org_id, $record );
    if ( empty( $customer['contact_id'] ) ) {
        return;
    }

    $source = $field_data['line_items_source'] ?? 'manual';

    if ( 'woocommerce' === $source ) {
        $entries = $inv->build_wc_line_item_entries( $posted_data );
    } else {
        $line_items_raw = adfoin_get_parsed_values( $field_data['line_items_json'] ?? '', $posted_data );
        $entries        = $inv->parse_line_items( $line_items_raw );
    }
    $line_items = $inv->build_line_items( $entries, $org_id, $record );
    if ( empty( $line_items ) ) {
        return;
    }

    $payload = array(
        'customer_id'      => $customer['contact_id'],
        'line_items'       => $line_items,
        'reference_number' => adfoin_get_parsed_values( $field_data['reference_number'] ?? '', $posted_data ),
        'notes'            => adfoin_get_parsed_values( $field_data['notes'] ?? '', $posted_data ),
        'date'             => adfoin_get_parsed_values( $field_data['date'] ?? '', $posted_data ),
        'currency_id'      => adfoin_get_parsed_values( $field_data['currency_id'] ?? '', $posted_data ),
    );

    if ( 'create_invoice' === $task ) {
        $payload['due_date'] = adfoin_get_parsed_values( $field_data['due_date'] ?? '', $posted_data );
    }

    if ( 'create_recurring_invoice' === $task ) {
        $payload['repeat_every']    = adfoin_get_parsed_values( $field_data['repeat_every'] ?? '1', $posted_data );
        $payload['frequency']       = adfoin_get_parsed_values( $field_data['frequency'] ?? 'months', $posted_data );
        $payload['start_date']      = $payload['date'] ?: date( 'Y-m-d' );
        $payload['recurrence_name'] = adfoin_get_parsed_values( $field_data['recurrence_name'] ?? '', $posted_data );
        if ( empty( $payload['recurrence_name'] ) ) {
            $payload['recurrence_name'] = 'Auto Recurring ' . date( 'Y-m-d H:i:s' );
        }
    }

    $payload = array_filter( $payload, function( $v ) {
        return is_array( $v ) ? ! empty( $v ) : ( $v !== '' && $v !== null );
    } );

    $endpoint = '';
    switch ( $task ) {
        case 'create_invoice':           $endpoint = 'invoices'; break;
        case 'create_estimate':          $endpoint = 'estimates'; break;
        case 'create_recurring_invoice': $endpoint = 'recurringinvoices'; break;
    }
    if ( $endpoint ) {
        $inv->zohoinvoice_request( $endpoint, 'POST', $payload, $record, $org_id );
    }
}
