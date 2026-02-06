<?php

class ADFOIN_ZohoBooks extends Advanced_Form_Integration_OAuth2 {

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
        add_action( 'wp_ajax_adfoin_get_zohobooks_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zohobooks_credentials', array( $this, 'save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_zohobooks_organizations', array( $this, 'ajax_get_organizations' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['zohobooks'] = array(
            'title' => __( 'Zoho Books', 'advanced-form-integration' ),
            'tasks' => array(
                'create_contact'           => __( 'Create Contact', 'advanced-form-integration' ), // backward compatibility
                'upsert_customer'          => __( 'Customer (search or create)', 'advanced-form-integration' ),
                'upsert_item'              => __( 'Item (search or create)', 'advanced-form-integration' ),
                'create_estimate'          => __( 'Quote (Estimate)', 'advanced-form-integration' ),
                'create_invoice'           => __( 'Invoice', 'advanced-form-integration' ),
                'create_recurring_invoice' => __( 'Recurring Invoice', 'advanced-form-integration' ),
                'create_salesorder'        => __( 'Sales Receipt (Sales Order)', 'advanced-form-integration' ),
                'create_customer_payment'  => __( 'Customer Payment', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['zohobooks'] = __( 'Zoho Books', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'zohobooks' !== $current_tab ) {
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
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
            array(
                'name'          => 'data_center',
                'label'         => __( 'Data Center', 'advanced-form-integration' ),
                'type'          => 'select',
                'required'      => false,
                'show_in_table' => false,
                'options'       => array(
                    'com'          => 'zoho.com',
                    'eu'           => 'zoho.eu',
                    'in'           => 'zoho.in',
                    'com.au'       => 'zoho.com.au',
                    'com.cn'       => 'zoho.com.cn',
                    'jp'           => 'zoho.jp',
                    'sa'           => 'zoho.sa',
                    'zohocloud.ca' => 'zohocloud.ca',
                ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __('Create a Server-based application in %s.', 'advanced-form-integration'), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __('Use the redirect URI below as Authorized Redirect URI.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __('Copy the Client ID and Client Secret into the Add Account form, choose data center, then click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Zoho Books', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'zohobooks', 'Zoho Books', $fields, $instructions, $config );
    }

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/zohobooks',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_webhook_data' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function get_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $state  = isset( $params['state'] ) ? trim( $params['state'] ) : '';
        $this->cred_id = $state;

        if ( $code ) {
            $credentials = adfoin_read_credentials( 'zohobooks' );
            foreach ( $credentials as $cred ) {
                if ( $cred['id'] == $state ) {
                    $this->data_center   = $cred['data_center'] ?? ( $cred['dataCenter'] ?? 'com' );
                    $this->client_id     = $cred['client_id'] ?? ( $cred['clientId'] ?? '' );
                    $this->client_secret = $cred['client_secret'] ?? ( $cred['clientSecret'] ?? '' );
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

            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
            exit;
        }
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

        if ( 'adfoin_zohobooks_auth_redirect' !== $action ) {
            return;
        }

        $code  = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

        if ( $code && $state ) {
            $this->cred_id = $state;
            $credentials = adfoin_read_credentials( 'zohobooks' );
            foreach ( $credentials as $cred ) {
                if ( $cred['id'] == $state ) {
                    $this->data_center   = $cred['data_center'] ?? ( $cred['dataCenter'] ?? 'com' );
                    $this->client_id     = $cred['client_id'] ?? ( $cred['clientId'] ?? '' );
                    $this->client_secret = $cred['client_secret'] ?? ( $cred['clientSecret'] ?? '' );
                    $this->update_oauth_endpoints( $this->data_center );
                }
            }

            $this->request_token( $code );

            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
            ADFOIN_OAuth_Manager::handle_callback_close_popup( true, 'Connected via Legacy Redirect' );
        }
    }

    protected function request_token( $authorization_code ) {
        $endpoint = $this->token_endpoint;

        $body = array(
            'code'         => $authorization_code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $this->get_redirect_uri(),
            'client_id'    => $this->client_id,
            'client_secret'=> $this->client_secret,
        );

        $response = wp_remote_post(
            esc_url_raw( $endpoint ),
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => $body,
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

        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) {
            return;
        }

        $endpoint = $this->refresh_token_endpoint;

        $body = array(
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        );

        $response = wp_remote_post(
            esc_url_raw( $endpoint ),
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => $body,
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
        $credentials = adfoin_read_credentials( 'zohobooks' );

        foreach ( $credentials as &$entry ) {
            if ( ( $entry['id'] ?? '' ) === $this->cred_id ) {
                $entry['data_center']   = $this->data_center;
                $entry['client_id']     = $this->client_id;
                $entry['client_secret'] = $this->client_secret;
                if ( $this->access_token ) {
                    $entry['access_token'] = $this->access_token;
                }
                if ( $this->refresh_token ) {
                    $entry['refresh_token'] = $this->refresh_token;
                }
            }
        }

        adfoin_save_credentials( 'zohobooks', $credentials );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohobooks' );
    }

    protected function get_accounts_base( $data_center = 'com' ) {
        $map = array(
            'com'          => 'https://accounts.zoho.com',
            'eu'           => 'https://accounts.zoho.eu',
            'in'           => 'https://accounts.zoho.in',
            'com.cn'       => 'https://accounts.zoho.com.cn',
            'com.au'       => 'https://accounts.zoho.com.au',
            'jp'           => 'https://accounts.zoho.jp',
            'sa'           => 'https://accounts.zoho.sa',
            'zohocloud.ca' => 'https://accounts.zohocloud.ca',
            'ca'           => 'https://accounts.zohocloud.ca',
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
        $base = 'https://books.zoho.com/api/v3/';

        $map = array(
            'com'          => 'https://books.zoho.com/api/v3/',
            'eu'           => 'https://books.zoho.eu/api/v3/',
            'in'           => 'https://books.zoho.in/api/v3/',
            'com.cn'       => 'https://books.zoho.com.cn/api/v3/',
            'com.au'       => 'https://books.zoho.com.au/api/v3/',
            'jp'           => 'https://books.zoho.jp/api/v3/',
            'sa'           => 'https://books.zoho.sa/api/v3/',
        );

        if ( $this->data_center && isset( $map[ $this->data_center ] ) ) {
            $base = $map[ $this->data_center ];
        }

        return $base;
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );

        if ( empty( $credentials ) ) {
            return;
        }

        $this->data_center   = $credentials['data_center'] ?? ( $credentials['dataCenter'] ?? 'com' );
        $this->client_id     = $credentials['client_id'] ?? ( $credentials['clientId'] ?? '' );
        $this->client_secret = $credentials['client_secret'] ?? ( $credentials['clientSecret'] ?? '' );
        $this->access_token  = $credentials['access_token'] ?? ( $credentials['accessToken'] ?? '' );
        $this->refresh_token = $credentials['refresh_token'] ?? ( $credentials['refreshToken'] ?? '' );
        $this->cred_id       = $credentials['id'] ?? '';
        $this->update_oauth_endpoints( $this->data_center );
    }

    public function get_credentials() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $all_credentials = adfoin_read_credentials( 'zohobooks' );
        wp_send_json_success( $all_credentials );
    }

    public function save_credentials() {
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            die( __( 'Security check Failed', 'advanced-form-integration' ) );
        }

        $platform     = 'zohobooks';
        $credentials  = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Delete
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( $_POST['delete_index'] );
            if ( isset( $credentials[ $index ] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( 'Invalid index' );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
        $title         = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $client_id     = isset( $_POST['client_id'] ) ? sanitize_text_field( $_POST['client_id'] ) : '';
        $client_secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( $_POST['client_secret'] ) : '';
        $data_center   = isset( $_POST['data_center'] ) ? sanitize_text_field( $_POST['data_center'] ) : 'com';

        if ( empty( $id ) ) {
            $id = uniqid();
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
                // Preserve tokens only if credentials match
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

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        // Generate OAuth URL
        $redirect_uri = $this->get_redirect_uri();
        $scope        = 'ZohoBooks.fullaccess.all';
        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );

        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'access_type'   => 'offline',
                'redirect_uri'  => $redirect_uri,
                'scope'         => $scope,
                'state'         => $id,
            ),
            $auth_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function get_credentials_by_id( $cred_id ) {
        $all_credentials = adfoin_read_credentials( 'zohobooks' );

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
        $credentials = adfoin_read_credentials( 'zohobooks' );

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
        <script type="text/template" id="zohobooks-action-template">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Zoho Books Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchOrganizations">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <?php $this->get_credentials_list(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Organization', 'advanced-form-integration' ); ?></th>
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

        $response = $this->zohobooks_request( 'organizations', 'GET' );

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

    public function zohobooks_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $organization_id = '' ) {
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
            $args['headers']['X-com-zoho-books-organizationid'] = $organization_id;
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

    /* ---------- Data helpers ---------- */

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

    public function find_or_create_customer( $customer_payload, $org_id, $record = array() ) {
        $search_email = $customer_payload['email'] ?? '';
        $search_name  = $customer_payload['contact_name'] ?? '';

        $params = array();
        if ( $search_email ) {
            $params['email'] = $search_email;
        } elseif ( $search_name ) {
            $params['contact_name'] = $search_name;
        }

        if ( $params ) {
            $endpoint = add_query_arg( $params, 'contacts' );
            $response = $this->zohobooks_request( $endpoint, 'GET', array(), $record, $org_id );
            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['contacts'][0]['contact_id'] ) ) {
                    return $body['contacts'][0];
                }
            }
        }

        $create = $this->zohobooks_request( 'contacts', 'POST', $customer_payload, $record, $org_id );
        if ( is_wp_error( $create ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $create ), true );
        return $body['contact'] ?? array();
    }

    public function find_or_create_item( $item_payload, $org_id, $record = array() ) {
        $search_name = $item_payload['name'] ?? '';
        if ( $search_name ) {
            $endpoint = add_query_arg( array( 'name' => $search_name ), 'items' );
            $response = $this->zohobooks_request( $endpoint, 'GET', array(), $record, $org_id );
            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['items'][0]['item_id'] ) ) {
                    return $body['items'][0];
                }
            }
        }

        $create = $this->zohobooks_request( 'items', 'POST', $item_payload, $record, $org_id );
        if ( is_wp_error( $create ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $create ), true );
        return $body['item'] ?? array();
    }

    public function build_customer_payload( $field_data, $posted_data ) {
        $map = array(
            'contact_name' => 'contact_name',
            'company_name' => 'company_name',
            'email'        => 'email',
            'phone'        => 'phone',
            'website'      => 'website',
            'notes'        => 'notes',
        );

        $payload = array();
        foreach ( $map as $form_key => $api_key ) {
            if ( empty( $field_data[ $form_key ] ) ) {
                continue;
            }

            $value = adfoin_get_parsed_values( $field_data[ $form_key ], $posted_data );
            if ( $value !== '' && $value !== null ) {
                $payload[ $api_key ] = $value;
            }
        }

        if ( empty( $payload['contact_name'] ) && ! empty( $payload['company_name'] ) ) {
            $payload['contact_name'] = $payload['company_name'];
        }

        if ( empty( $payload['company_name'] ) && ! empty( $payload['contact_name'] ) ) {
            $payload['company_name'] = $payload['contact_name'];
        }

        if ( empty( $payload['contact_type'] ) ) {
            $payload['contact_type'] = 'customer';
        }

        return $payload;
    }

    public function build_item_payload_from_entry( $entry ) {
        $payload = array();
        $payload['name'] = $entry['item_name'] ?? ( $entry['name'] ?? '' );
        if ( isset( $entry['rate'] ) ) {
            $payload['rate'] = (float) $entry['rate'];
        }
        if ( isset( $entry['description'] ) ) {
            $payload['description'] = $entry['description'];
        }
        if ( empty( $payload['item_type'] ) ) {
            $payload['item_type'] = 'goods';
        }
        return $payload;
    }

    public function build_line_items( $entries, $org_id, $record = array() ) {
        $line_items = array();

        foreach ( $entries as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $item_payload = $this->build_item_payload_from_entry( $entry );
            if ( empty( $item_payload['name'] ) ) {
                continue;
            }

            $item = $this->find_or_create_item( $item_payload, $org_id, $record );
            if ( empty( $item['item_id'] ) ) {
                continue;
            }

            $line = array(
                'item_id'   => $item['item_id'],
                'rate'      => isset( $entry['rate'] ) ? (float) $entry['rate'] : ( isset( $item['rate'] ) ? $item['rate'] : 0 ),
                'quantity'  => isset( $entry['quantity'] ) ? (float) $entry['quantity'] : 1,
                'name'      => $item['name'] ?? $item_payload['name'],
                'description' => $entry['description'] ?? '',
            );

            if ( isset( $entry['tax_id'] ) ) {
                $line['tax_id'] = $entry['tax_id'];
            }

            $line_items[] = $line;
        }

        return $line_items;
    }
}

$adfoin_zohobooks = ADFOIN_ZohoBooks::get_instance();

function adfoin_zohobooks_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'zohobooks' ) {
        return;
    }

    $record      = $data['record'];
    $posted_data = $data['posted_data'];
    adfoin_zohobooks_send_data( $record, $posted_data );
}

add_action( 'adfoin_job_queue', 'adfoin_zohobooks_job_queue', 10, 1 );

function adfoin_zohobooks_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $org_id     = $field_data['organizationId'] ?? '';
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || ! $org_id ) {
        return;
    }

    $books = ADFOIN_ZohoBooks::get_instance();
    $books->set_credentials( $cred_id );

    switch ( $task ) {
        case 'create_contact':
        case 'upsert_customer':
            $customer_payload = $books->build_customer_payload( $field_data, $posted_data );
            if ( empty( $customer_payload ) ) {
                return;
            }
            $books->find_or_create_customer( $customer_payload, $org_id, $record );
            break;

        case 'upsert_item':
            $item_payload = array(
                'name'        => adfoin_get_parsed_values( $field_data['item_name'] ?? '', $posted_data ),
                'rate'        => (float) adfoin_get_parsed_values( $field_data['item_rate'] ?? '', $posted_data ),
                'description' => adfoin_get_parsed_values( $field_data['item_description'] ?? '', $posted_data ),
                'item_type'   => adfoin_get_parsed_values( $field_data['item_type'] ?? 'goods', $posted_data ),
            );

            $item_payload = array_filter(
                $item_payload,
                function( $value ) {
                    return '' !== $value && null !== $value;
                }
            );

            if ( empty( $item_payload['name'] ) ) {
                return;
            }

            if ( empty( $item_payload['item_type'] ) ) {
                $item_payload['item_type'] = 'goods';
            }

            $books->find_or_create_item( $item_payload, $org_id, $record );
            break;

        case 'create_estimate':
        case 'create_invoice':
        case 'create_recurring_invoice':
        case 'create_salesorder':
        case 'create_customer_payment':
            adfoin_zohobooks_handle_document( $books, $task, $field_data, $posted_data, $org_id, $record );
            break;
    }
}


function adfoin_zohobooks_handle_document( $books, $task, $field_data, $posted_data, $org_id, $record ) {
    $customer_payload = $books->build_customer_payload( $field_data, $posted_data );
    if ( empty( $customer_payload ) ) {
        return;
    }

    $customer = $books->find_or_create_customer( $customer_payload, $org_id, $record );
    if ( empty( $customer['contact_id'] ) ) {
        return;
    }

    if ( 'create_customer_payment' === $task ) {
        $payload = array(
            'customer_id'     => $customer['contact_id'],
            'payment_mode'    => adfoin_get_parsed_values( $field_data['payment_mode'] ?? '', $posted_data ),
            'amount'          => (float) adfoin_get_parsed_values( $field_data['payment_amount'] ?? '', $posted_data ),
            'date'            => adfoin_get_parsed_values( $field_data['payment_date'] ?? '', $posted_data ),
            'reference_number'=> adfoin_get_parsed_values( $field_data['payment_reference'] ?? '', $posted_data ),
        );

        $invoice_id = adfoin_get_parsed_values( $field_data['invoice_id'] ?? '', $posted_data );
        if ( $invoice_id ) {
            $payload['invoice_id'] = $invoice_id;
        }

        $payload = array_filter(
            $payload,
            function( $value ) {
                return $value !== '' && $value !== null;
            }
        );

        if ( empty( $payload['amount'] ) || empty( $payload['payment_mode'] ) ) {
            return;
        }

        $books->zohobooks_request( 'customerpayments', 'POST', $payload, $record, $org_id );
        return;
    }

    $line_items_raw = adfoin_get_parsed_values( $field_data['line_items_json'] ?? '', $posted_data );
    $line_entries   = $books->parse_line_items( $line_items_raw );
    $line_items     = $books->build_line_items( $line_entries, $org_id, $record );

    if ( empty( $line_items ) ) {
        return;
    }

    $payload = array(
        'customer_id'   => $customer['contact_id'],
        'line_items'    => $line_items,
        'notes'         => adfoin_get_parsed_values( $field_data['notes'] ?? '', $posted_data ),
        'reference_number' => adfoin_get_parsed_values( $field_data['reference_number'] ?? '', $posted_data ),
        'date'          => adfoin_get_parsed_values( $field_data['date'] ?? '', $posted_data ),
        'due_date'      => adfoin_get_parsed_values( $field_data['due_date'] ?? '', $posted_data ),
        'currency_id'   => adfoin_get_parsed_values( $field_data['currency_code'] ?? '', $posted_data ),
    );

    $payload = array_filter(
        $payload,
        function( $value ) {
            return $value !== '' && $value !== null;
        }
    );

    $endpoint = '';
    switch ( $task ) {
        case 'create_estimate':
            $endpoint = 'estimates';
            break;
        case 'create_invoice':
            $endpoint = 'invoices';
            break;
        case 'create_recurring_invoice':
            $endpoint = 'recurringinvoices';
            $payload['repeat_every'] = $payload['repeat_every'] ?? 1;
            $payload['frequency']    = $payload['frequency'] ?? 'months';
            $payload['start_date']   = $payload['date'] ?? date( 'Y-m-d' );
            $payload['recurrence_name'] = $payload['recurrence_name'] ?? 'Auto Recurring';
            break;
        case 'create_salesorder':
            $endpoint = 'salesorders';
            break;
    }

    if ( $endpoint ) {
        $books->zohobooks_request( $endpoint, 'POST', $payload, $record, $org_id );
    }
}
