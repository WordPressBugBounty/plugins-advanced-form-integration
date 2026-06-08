<?php

class ADFOIN_ZohoCreator extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'zohocreator';

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
        add_action( 'wp_ajax_adfoin_get_zohocreator_credentials', array( $this, 'get_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_save_zohocreator_credentials', array( $this, 'save_credentials' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_test_zohocreator_connection', array( $this, 'test_connection' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_zohocreator_apps', array( $this, 'ajax_get_apps' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['zohocreator'] = array(
            'title' => __( 'Zoho Creator', 'advanced-form-integration' ),
            'tasks' => array(
                'add_record' => __( 'Add Record to Form', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['zohocreator'] = __( 'Zoho Creator', 'advanced-form-integration' );
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'zohocreator' !== $current_tab ) { return; }
        $redirect_uri = $this->get_redirect_uri();
        $fields = array(
            array( 'name' => 'client_id', 'label' => __( 'Client ID', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true, 'show_in_table' => true ),
            array( 'name' => 'client_secret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'type' => 'text', 'required' => false, 'mask' => true, 'show_in_table' => true, 'placeholder' => __( 'Leave blank to keep current', 'advanced-form-integration' ) ),
            array( 'name' => 'data_center', 'label' => __( 'Data Center', 'advanced-form-integration' ), 'type' => 'select', 'required' => false, 'show_in_table' => false,
                'options' => array( 'com' => 'zoho.com', 'eu' => 'zoho.eu', 'in' => 'zoho.in', 'com.au' => 'zoho.com.au', 'com.cn' => 'zoho.com.cn', 'jp' => 'zoho.jp', 'sa' => 'zoho.sa' ),
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . sprintf( __( 'Create a Server-based application in %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://api-console.zoho.com/">Zoho API Console</a>' ) . '</li>';
        $instructions .= '<li>' . __( 'Use the redirect URI below as Authorized Redirect URI.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Copy the Client ID and Client Secret, choose data center, then Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true, 'enable_test' => true,
            'modal_title' => __( 'Connect Zoho Creator', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'zohocreator', 'Zoho Creator', $fields, $instructions, $config );
    }

    public function register_webhook_route() {
        register_rest_route( 'advancedformintegration', '/zohocreator', array(
            'methods' => 'GET', 'callback' => array( $this, 'get_webhook_data' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_webhook_data( $request ) {
        $params    = $request->get_params();
        $code      = isset( $params['code'] ) ? trim( $params['code'] ) : '';
        $raw_state = isset( $params['state'] ) ? trim( $params['state'] ) : '';
        if ( ! $code || ! $raw_state ) { return; }

        $context = self::consume_oauth_state( $raw_state, 'zohocreator' );
        $cred_id = $context ? $context['cred_id'] : '';
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        if ( ! $cred_id ) {
            ADFOIN_OAuth_Manager::handle_callback_close_popup( false, __( 'OAuth state invalid or expired. Please try again.', 'advanced-form-integration' ) );
            exit;
        }

        $this->cred_id = $cred_id;
        $credentials   = adfoin_read_credentials( 'zohocreator' );
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
                $message = $body['error'] ?? $message;
            }
        } else {
            $message = is_wp_error( $response ) ? $response->get_error_message() : $message;
        }

        ADFOIN_OAuth_Manager::handle_callback_close_popup( $success, $message );
        exit;
    }

    public function auth_redirect() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        if ( 'adfoin_zohocreator_auth_redirect' !== $action ) { return; }
        $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
        $context = self::consume_oauth_state( $state, 'zohocreator' );
        $cred_id = $context ? $context['cred_id'] : '';

        if ( $code && $cred_id ) {
            $this->cred_id = $cred_id;
            $credentials = adfoin_read_credentials( 'zohocreator' );
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
        $response = wp_remote_post( esc_url_raw( $this->token_endpoint ), array(
            'timeout' => 30,
            'body'    => array(
                'code' => $authorization_code,
                'client_id' => $this->client_id, 'client_secret' => $this->client_secret,
                'redirect_uri' => $this->get_redirect_uri(), 'grant_type' => 'authorization_code',
            ),
        ) );
        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 401 === $code ) {
            $this->access_token = null; $this->refresh_token = null;
        } else { $this->apply_token_response( $body ); }
        $this->save_data();
        return $response;
    }

    protected function refresh_token() {
        if ( empty( $this->refresh_token ) ) { return; }
        $response = wp_remote_post( esc_url_raw( $this->refresh_token_endpoint ), array(
            'timeout' => 30,
            'body'    => array(
                'refresh_token' => $this->refresh_token,
                'client_id' => $this->client_id, 'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
            ),
        ) );
        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 401 === $code ) {
            $this->access_token = null; $this->refresh_token = null;
            $this->mark_connection_failed( 'refresh_token_revoked' );
        } else { $this->apply_token_response( $body ); }
        $this->save_data();
    }

    protected function save_data() {
        $this->persist_token_to_credential( array(
            'data_center' => $this->data_center,
            'client_id' => $this->client_id, 'client_secret' => $this->client_secret,
        ) );
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/zohocreator' );
    }

    protected function get_accounts_base( $dc = 'com' ) {
        $map = array(
            'com' => 'https://accounts.zoho.com', 'eu' => 'https://accounts.zoho.eu',
            'in' => 'https://accounts.zoho.in', 'com.cn' => 'https://accounts.zoho.com.cn',
            'com.au' => 'https://accounts.zoho.com.au', 'jp' => 'https://accounts.zoho.jp',
            'sa' => 'https://accounts.zoho.sa',
        );
        return $map[ $dc ] ?? $map['com'];
    }

    protected function get_oauth_endpoint( $path = 'auth', $dc = '' ) {
        return trailingslashit( $this->get_accounts_base( $dc ?: $this->data_center ) ) . 'oauth/v2/' . $path;
    }

    protected function update_oauth_endpoints( $dc ) {
        $this->authorization_endpoint = $this->get_oauth_endpoint( 'auth', $dc );
        $this->token_endpoint         = $this->get_oauth_endpoint( 'token', $dc );
        $this->refresh_token_endpoint = $this->token_endpoint;
    }

    protected function get_apis_base_url() {
        $map = array(
            'com'    => 'https://www.zohoapis.com/creator/v2.1/',
            'eu'     => 'https://www.zohoapis.eu/creator/v2.1/',
            'in'     => 'https://www.zohoapis.in/creator/v2.1/',
            'com.cn' => 'https://www.zohoapis.com.cn/creator/v2.1/',
            'com.au' => 'https://www.zohoapis.com.au/creator/v2.1/',
            'jp'     => 'https://www.zohoapis.jp/creator/v2.1/',
            'sa'     => 'https://www.zohoapis.sa/creator/v2.1/',
        );
        return $map[ $this->data_center ] ?? $map['com'];
    }

    public function set_credentials( $cred_id ) {
        $credentials = $this->get_credentials_by_id( $cred_id );
        if ( empty( $credentials ) ) { return; }
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
            return $this->zohocreator_request( 'meta/applications' );
        } );
    }

    public function get_credentials() {
        adfoin_verify_nonce();
        wp_send_json_success( $this->safe_credentials_list() );
    }

    public function save_credentials() {
        adfoin_verify_nonce();
        $platform    = 'zohocreator';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) { $credentials = array(); }

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

        if ( empty( $id ) ) { $id = wp_generate_uuid4(); }

        $existing = null;
        foreach ( $credentials as $cred_check ) {
            if ( isset( $cred_check['id'] ) && $cred_check['id'] === $id ) {
                $existing = $cred_check;
                break;
            }
        }
        if ( $existing ) {
            if ( '' === $client_id && ! empty( $existing['client_id'] ) ) { $client_id = $existing['client_id']; }
            if ( '' === $client_secret && ! empty( $existing['client_secret'] ) ) { $client_secret = $existing['client_secret']; }
        } elseif ( '' === $client_id || '' === $client_secret ) {
            wp_send_json_error( array( 'message' => __( 'Client ID and Client Secret are required.', 'advanced-form-integration' ) ) );
        }

        $new_data = array(
            'id' => $id, 'title' => $title,
            'client_id' => $client_id, 'client_secret' => $client_secret,
            'data_center' => $data_center,
            'access_token' => '', 'refresh_token' => '',
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
        if ( ! $found ) { $credentials[] = $new_data; }
        adfoin_save_credentials( $platform, $credentials );

        $redirect_uri  = $this->get_redirect_uri();
        // Scopes cover form submit + reading app/form metadata so the picker
        // UI can populate dropdowns.
        $scope         = 'ZohoCreator.form.CREATE,ZohoCreator.report.READ,ZohoCreator.meta.application.READ';
        $auth_endpoint = $this->get_oauth_endpoint( 'auth', $data_center );
        $auth_url = add_query_arg( array(
            'response_type' => 'code', 'client_id' => $client_id,
            'access_type' => 'offline', 'redirect_uri' => $redirect_uri,
            'scope' => $scope, 'state' => self::issue_oauth_state( 'zohocreator', $id ),
        ), $auth_endpoint );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
    }

    public function get_credentials_by_id( $cred_id ) {
        $all = adfoin_read_credentials( 'zohocreator' );
        if ( ! is_array( $all ) || empty( $all ) ) { return array(); }
        foreach ( $all as $single ) {
            if ( $cred_id && ( $single['id'] ?? '' ) === $cred_id ) { return $single; }
        }
        return $all[0];
    }

    public function get_credentials_list() {
        $credentials = adfoin_read_credentials( 'zohocreator' );
        if ( ! is_array( $credentials ) ) { return; }
        foreach ( $credentials as $option ) {
            printf( '<option value="%1$s">%2$s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
        }
    }

    public function action_fields() {
        ?>
        <script type="text/template" id="zohocreator-action-template">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Zoho Creator Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchApps">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                        <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=zohocreator' ); ?>" target="_blank">
                            <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_attr_e( 'Account Owner', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[account_owner_name]" v-model="fielddata.account_owner_name">
                            <option value=""><?php esc_html_e( 'Select Owner...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in apps" :value="index">{{ item.workspace_name }}</option>
                        </select>
                        <p class="description"><?php esc_html_e( 'The "workspace owner" portion of your Creator app URL.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_attr_e( 'App Link Name', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="fieldData[app_link_name]" v-model="fielddata.app_link_name" class="regular-text" placeholder="my_app">
                        <p class="description"><?php esc_html_e( 'The app link name as it appears in the Creator URL (e.g. my-app).', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_attr_e( 'Form Link Name', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="fieldData[form_link_name]" v-model="fielddata.form_link_name" class="regular-text" placeholder="Contact_Form">
                        <p class="description"><?php esc_html_e( 'The form link name (snake_case).', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_attr_e( 'Mode', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[mode]" v-model="fielddata.mode">
                            <option value="single"><?php esc_html_e( 'Single Record', 'advanced-form-integration' ); ?></option>
                            <option value="wc_items"><?php esc_html_e( 'One Record per WooCommerce Line Item', 'advanced-form-integration' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'When triggered by a WooCommerce order, the second option adds one Creator record per line item, replacing {{items_*}} tags with each item\'s value.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_attr_e( 'Field Mappings', 'advanced-form-integration' ); ?></th>
                    <td>
                        <textarea name="fieldData[mappings]" v-model="fielddata.mappings" rows="8" class="large-text code" placeholder='Email = (billing email tag)&#10;Name = (billing first + last tags)&#10;Phone = (billing phone tag)'></textarea>
                        <p class="description"><?php esc_html_e( 'One mapping per line as "field_link_name = value". Tags from the trigger are supported.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
            </table>
        </script>
        <?php
    }

    public function ajax_get_apps() {
        adfoin_verify_nonce();
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( ! $cred_id ) { wp_send_json_success( array() ); }
        $this->set_credentials( $cred_id );

        $response = $this->zohocreator_request( 'meta/applications' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $apps = array();
        if ( isset( $body['applications'] ) && is_array( $body['applications'] ) ) {
            foreach ( $body['applications'] as $a ) {
                $owner = $a['workspace_name'] ?? '';
                if ( $owner && empty( $apps[ $owner ] ) ) {
                    $apps[ $owner ] = array(
                        'workspace_name' => $owner,
                    );
                }
            }
        }
        wp_send_json_success( $apps );
    }

    public function zohocreator_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
        static $refreshed = false;
        $url = $this->get_apis_base_url() . ltrim( $endpoint, '/' );
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Zoho-oauthtoken ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'timeout' => 30,
        );
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

    /**
     * Parse the field-mappings textarea into [link_name => template] pairs.
     * One mapping per line in the form `link_name = template`.
     */
    public function parse_mappings( $raw ) {
        $pairs = array();
        if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
            return $pairs;
        }
        $lines = preg_split( "/\r\n|\r|\n/", $raw );
        foreach ( $lines as $line ) {
            if ( '' === trim( $line ) ) { continue; }
            $parts = explode( '=', $line, 2 );
            if ( count( $parts ) !== 2 ) { continue; }
            $key = trim( $parts[0] );
            $val = trim( $parts[1] );
            if ( '' !== $key ) {
                $pairs[ $key ] = $val;
            }
        }
        return $pairs;
    }

    /**
     * Run adfoin_get_parsed_values over a mapping set, returning the
     * resolved [link_name => value] record ready for the Creator API.
     */
    public function resolve_mappings( $pairs, $posted_data ) {
        $out = array();
        foreach ( $pairs as $key => $template ) {
            $value = adfoin_get_parsed_values( $template, $posted_data );
            if ( '' !== $value && null !== $value ) {
                $out[ $key ] = $value;
            }
        }
        return $out;
    }
}

$adfoin_zohocreator = ADFOIN_ZohoCreator::get_instance();

function adfoin_zohocreator_job_queue( $data ) {
    if ( ( $data['action_provider'] ?? '' ) !== 'zohocreator' ) { return; }
    adfoin_zohocreator_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_job_queue', 'adfoin_zohocreator_job_queue', 10, 1 );

function adfoin_zohocreator_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $owner      = $field_data['account_owner_name'] ?? '';
    $app        = $field_data['app_link_name'] ?? '';
    $form       = $field_data['form_link_name'] ?? '';
    $mode       = $field_data['mode'] ?? 'single';

    if ( ! $cred_id || ! $owner || ! $app || ! $form ) { return; }

    $cr = ADFOIN_ZohoCreator::get_instance();
    $cr->set_credentials( $cred_id );

    $pairs    = $cr->parse_mappings( $field_data['mappings'] ?? '' );
    if ( empty( $pairs ) ) { return; }

    $endpoint = sprintf( '%s/%s/form/%s', $owner, $app, $form );

    if ( 'wc_items' === $mode && isset( $posted_data['items_id'] ) && is_array( $posted_data['items_id'] ) ) {
        // Push one Creator record per WooCommerce line item by indexing into
        // the items_* parallel arrays. Each iteration substitutes the
        // single-item value for items_* tags before evaluating mappings.
        foreach ( $posted_data['items_id'] as $idx => $_pid ) {
            $iter = $posted_data;
            foreach ( array( 'items_id', 'items_name', 'items_sku', 'items_price', 'items_quantity', 'items_total', 'items_variation_id' ) as $col ) {
                if ( isset( $posted_data[ $col ] ) && is_array( $posted_data[ $col ] ) ) {
                    $iter[ $col ] = $posted_data[ $col ][ $idx ] ?? '';
                }
            }
            $row = $cr->resolve_mappings( $pairs, $iter );
            if ( empty( $row ) ) { continue; }
            $cr->zohocreator_request( $endpoint, 'POST', array( 'data' => $row ), $record );
        }
        return;
    }

    $row = $cr->resolve_mappings( $pairs, $posted_data );
    if ( empty( $row ) ) { return; }

    $cr->zohocreator_request( $endpoint, 'POST', array( 'data' => $row ), $record );
}
