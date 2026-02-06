<?php

if ( ! class_exists( 'ADFoin_Salesforce_Field_Service' ) ) :

class ADFoin_Salesforce_Field_Service extends Advanced_Form_Integration_OAuth2 {

    const AUTHORIZATION_ENDPOINT = 'https://login.salesforce.com/services/oauth2/authorize';
    const TOKEN_ENDPOINT         = 'https://login.salesforce.com/services/oauth2/token';
    const REFRESH_TOKEN_ENDPOINT = 'https://login.salesforce.com/services/oauth2/token';
    const API_VERSION            = 'v62.0';

    private static $instance;

    protected $client_id     = '';
    protected $client_secret = '';
    protected $access_token  = '';
    protected $refresh_token = '';
    protected $instance_url  = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load legacy credentials for backward compatibility
     */
    private function load_legacy_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_salesforcefieldservice_keys' ) );

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

        if ( isset( $option['instance_url'] ) ) {
            $this->instance_url = $option['instance_url'];
        }
    }

    public function __construct() {
        $this->authorization_endpoint = self::AUTHORIZATION_ENDPOINT;
        $this->token_endpoint         = self::TOKEN_ENDPOINT;
        $this->refresh_token_endpoint = self::REFRESH_TOKEN_ENDPOINT;

        // Load legacy credentials for backward compatibility
        $this->load_legacy_credentials();

        add_action( 'admin_init', array( $this, 'auth_redirect' ) );
        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );

        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );

        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'admin_post_adfoin_save_salesforcefieldservice_keys', array( $this, 'save_keys' ) );

        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_salesforcefieldservice_fields', array( $this, 'ajax_get_fields' ) );

        add_action( 'adfoin_salesforcefieldservice_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_salesforcefieldservice_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_salesforcefieldservice_credentials', array( $this, 'save_credentials' ) );
    }

    public function register_actions( $actions ) {
        $actions['salesforcefieldservice'] = array(
            'title' => __( 'Salesforce Field Service', 'advanced-form-integration' ),
            'tasks' => array(
                'create_work_order' => __( 'Create / Update Work Order', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['salesforcefieldservice'] = __( 'Salesforce Field Service', 'advanced-form-integration' );

        return $providers;
    }

    public function render_settings( $current_tab ) {
        if ( 'salesforcefieldservice' !== $current_tab ) {
            return;
        }

        $option        = (array) maybe_unserialize( get_option( 'adfoin_salesforcefieldservice_keys' ) );
        $nonce         = wp_create_nonce( 'adfoin_salesforcefieldservice_settings' );
        $client_id     = isset( $option['client_id'] ) ? $option['client_id'] : '';
        $client_secret = isset( $option['client_secret'] ) ? $option['client_secret'] : '';
        $redirect_uri  = $this->get_redirect_uri();
        ?>
        <form name="salesforcefieldservice_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
              method="post" class="container">

            <input type="hidden" name="action" value="adfoin_save_salesforcefieldservice_keys">
            <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                    <td>
                        <ol>
                            <li><?php esc_html_e( 'Log in to Salesforce and open Setup.', 'advanced-form-integration' ); ?></li>
                            <li><?php esc_html_e( 'Navigate to App Manager → New Connected App.', 'advanced-form-integration' ); ?></li>
                            <li><?php esc_html_e( 'Enable OAuth settings and paste the callback URL below.', 'advanced-form-integration' ); ?></li>
                            <li><?php esc_html_e( 'Add the scopes “Access and manage your data (api)” and “Perform requests at any time (refresh_token, offline_access)”.', 'advanced-form-integration' ); ?></li>
                            <li><?php esc_html_e( 'Save the Connected App, open the Consumer Details, and copy the Client ID and Client Secret.', 'advanced-form-integration' ); ?></li>
                            <li><?php esc_html_e( 'Ensure Field Service users have access to the Connected App and Work Order / Service Appointment objects.', 'advanced-form-integration' ); ?></li>
                        </ol>
                        <p>
                            <strong><?php esc_html_e( 'Callback URL:', 'advanced-form-integration' ); ?></strong>
                            <code><?php echo esc_html( $redirect_uri ); ?></code>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Status', 'advanced-form-integration' ); ?></th>
                    <td>
                        <?php
                        if ( $this->is_active() && $this->instance_url ) {
                            esc_html_e( 'Connected', 'advanced-form-integration' );
                        } else {
                            esc_html_e( 'Not Connected', 'advanced-form-integration' );
                        }
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Client ID', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="adfoin_salesforcefieldservice_client_id"
                               value="<?php echo esc_attr( $client_id ); ?>"
                               placeholder="<?php esc_attr_e( 'Enter Client ID', 'advanced-form-integration' ); ?>"
                               class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Client Secret', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="adfoin_salesforcefieldservice_client_secret"
                               value="<?php echo esc_attr( $client_secret ); ?>"
                               placeholder="<?php esc_attr_e( 'Enter Client Secret', 'advanced-form-integration' ); ?>"
                               class="regular-text"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Re-authorize', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php esc_html_e( 'If requests fail, revoke the Connected App in Salesforce and authorize again.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save & Authorize', 'advanced-form-integration' ) ); ?>
        </form>
        <?php
    }

    public function save_keys() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ), 'adfoin_salesforcefieldservice_settings' ) ) {
            wp_die( esc_html__( 'Security check failed', 'advanced-form-integration' ) );
        }

        $client_id     = isset( $_POST['adfoin_salesforcefieldservice_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_salesforcefieldservice_client_id'] ) ) : '';
        $client_secret = isset( $_POST['adfoin_salesforcefieldservice_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_salesforcefieldservice_client_secret'] ) ) : '';

        $this->client_id     = trim( $client_id );
        $this->client_secret = trim( $client_secret );

        $this->save_data();
        $this->authorize();

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforcefieldservice' ) );
        exit;
    }

    protected function authorize( string $scope = '' ) {
        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $this->client_id,
                'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
                'prompt'        => 'login consent',
            ),
            $this->authorization_endpoint
        );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $code ) {
        $url = $this->token_endpoint . '?' . http_build_query(
            array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->get_redirect_uri(),
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
            '',
            '&'
        );

        $response      = wp_remote_request(
            $url,
            array(
                'headers' => array(
                    'user-agent' => 'wordpress/advanced-form-integration',
                ),
                'timeout' => 30,
                'method'  => 'POST',
            )
        );

        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        }

        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        if ( isset( $response_body['instance_url'] ) ) {
            $this->instance_url = $response_body['instance_url'];
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        if ( ! $this->refresh_token ) {
            return new WP_Error( 'salesforce_field_service_missing_refresh_token', __( 'Refresh token is missing.', 'advanced-form-integration' ) );
        }

        $endpoint = add_query_arg(
            array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
            $this->refresh_token_endpoint
        );

        $response      = wp_remote_post(
            esc_url_raw( $endpoint ),
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
            )
        );

        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $response_body['access_token'] ) ) {
            $this->access_token = $response_body['access_token'];
        }

        if ( isset( $response_body['refresh_token'] ) ) {
            $this->refresh_token = $response_body['refresh_token'];
        }

        if ( isset( $response_body['instance_url'] ) ) {
            $this->instance_url = $response_body['instance_url'];
        }

        $this->save_data();

        return $response;
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/salesforcefieldservice' );
    }

    protected function save_data() {
        $data = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'instance_url'  => $this->instance_url,
        );

        update_option( 'adfoin_salesforcefieldservice_keys', maybe_serialize( $data ) );
    }

    protected function reset_data() {
        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';
        $this->refresh_token = '';
        $this->instance_url  = '';

        $this->save_data();
    }

    public function set_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_salesforcefieldservice_keys' ) );

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

        if ( isset( $option['instance_url'] ) ) {
            $this->instance_url = $option['instance_url'];
        }
    }

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/salesforcefieldservice',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'handle_webhook_data' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_webhook_data( $request ) {
        $params = $request->get_params();
        $code   = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';

        if ( $code ) {
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_salesforcefieldservice_auth_redirect',
                    'code'    => $code,
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            );

            wp_safe_redirect( $redirect_to );
            exit;
        }

        return new WP_Error( 'salesforce_field_service_missing_code', __( 'Authorization code not found.', 'advanced-form-integration' ) );
    }

    public function auth_redirect() {
        if ( ! isset( $_GET['action'] ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

        if ( 'adfoin_salesforcefieldservice_auth_redirect' !== $action ) {
            return;
        }

        $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

        if ( $code ) {
            $this->request_token( $code );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforcefieldservice' ) );
        exit;
    }

    public function render_action_template() {
        ?>
        <script type="text/template" id="salesforcefieldservice-action-template">
            <table class="form-table" v-if="action.task == 'create_work_order'">
                <tr valign="top">
                    <th scope="row"><?php esc_attr_e( 'Salesforce Field Service Account', 'advanced-form-integration' ); ?></th>
                    <td scope="row">
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getCredentials">
                            <option value=""><?php esc_attr_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(item, index) in credentialsList" :value="index" v-html="item.label"></option>
                        </select>
                        <div class="spinner" v-bind:class="{'is-active': credentialsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_attr_e( 'Manage Accounts', 'advanced-form-integration' ); ?></th>
                    <td scope="row">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesforcefieldservice' ) ); ?>" target="_blank" class="button"><?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td scope="row">
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                                v-bind:key="field.value"
                                v-bind:field="field"
                                v-bind:trigger="trigger"
                                v-bind:action="action"
                                v-bind:fielddata="fielddata"></editable-field>

                <?php if ( adfoin_fs()->is_not_paying() ) : ?>
                <tr class="alternate">
                    <th scope="row"><?php esc_attr_e( 'Need service appointments?', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Salesforce Field Service [PRO]</a> to schedule Service Appointments, attach crews, and send custom payloads.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </script>
        <?php
    }

    public function ajax_get_fields() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials_by_id( $cred_id );
        }

        wp_send_json_success( $this->get_field_definitions() );
    }

    protected function get_field_definitions() {
        return array(
            array( 'key' => 'work_order_id', 'value' => __( 'Work Order ID (update existing)', 'advanced-form-integration' ) ),
            array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'status', 'value' => __( 'Status', 'advanced-form-integration' ) ),
            array( 'key' => 'priority', 'value' => __( 'Priority', 'advanced-form-integration' ) ),
            array( 'key' => 'account_id', 'value' => __( 'Account ID', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_id', 'value' => __( 'Contact ID', 'advanced-form-integration' ) ),
            array( 'key' => 'owner_id', 'value' => __( 'Owner ID', 'advanced-form-integration' ) ),
            array( 'key' => 'work_type_id', 'value' => __( 'Work Type ID', 'advanced-form-integration' ) ),
            array( 'key' => 'asset_id', 'value' => __( 'Asset ID', 'advanced-form-integration' ) ),
            array( 'key' => 'service_contract_id', 'value' => __( 'Service Contract ID', 'advanced-form-integration' ) ),
            array( 'key' => 'duration', 'value' => __( 'Estimated Duration (minutes)', 'advanced-form-integration' ) ),
            array( 'key' => 'duration_type', 'value' => __( 'Duration Type (Minutes/Hours)', 'advanced-form-integration' ) ),
            array( 'key' => 'earliest_start_time', 'value' => __( 'Earliest Start (ISO 8601)', 'advanced-form-integration' ) ),
            array( 'key' => 'due_date', 'value' => __( 'Due Date (ISO 8601)', 'advanced-form-integration' ) ),
            array( 'key' => 'street', 'value' => __( 'Service Street', 'advanced-form-integration' ) ),
            array( 'key' => 'city', 'value' => __( 'Service City', 'advanced-form-integration' ) ),
            array( 'key' => 'state', 'value' => __( 'Service State / Province', 'advanced-form-integration' ) ),
            array( 'key' => 'postal_code', 'value' => __( 'Service Postal Code', 'advanced-form-integration' ) ),
            array( 'key' => 'country', 'value' => __( 'Service Country', 'advanced-form-integration' ) ),
        );
    }

    public function handle_job_queue( $data ) {
        $cred_id = isset( $data['record']['data'] ) ? json_decode( $data['record']['data'], true )['field_data']['credId'] ?? '' : '';
        
        if ( $cred_id ) {
            $this->set_credentials_by_id( $cred_id );
        } else {
            $this->set_credentials();
        }

        $record      = isset( $data['record'] ) ? $data['record'] : array();
        $posted_data = isset( $data['posted_data'] ) ? $data['posted_data'] : array();

        $this->process_work_order( $record, $posted_data );
    }

    protected function process_work_order( $record, $posted_data ) {
        if ( empty( $record ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();

        $payload = $this->build_work_order_payload( $field_data, $posted_data );

        if ( is_wp_error( $payload ) ) {
            adfoin_add_to_log( $payload, '', array(), $record );
            return;
        }

        if ( empty( $payload ) ) {
            return;
        }

        if ( ! $this->instance_url || ! $this->access_token ) {
            adfoin_add_to_log(
                new WP_Error( 'salesforce_field_service_missing_credentials', __( 'Salesforce Field Service credentials are not connected.', 'advanced-form-integration' ) ),
                '',
                array(),
                $record
            );
            return;
        }

        $work_order_id = $this->parse_field( $field_data, 'work_order_id', $posted_data );

        if ( $work_order_id ) {
            $this->api_request( 'sobjects/WorkOrder/' . rawurlencode( $work_order_id ), 'PATCH', $payload, $record );
            return;
        }

        $this->api_request( 'sobjects/WorkOrder', 'POST', $payload, $record );
    }

    protected function build_work_order_payload( $field_data, $posted_data ) {
        $subject = $this->parse_field( $field_data, 'subject', $posted_data );

        if ( '' === $subject ) {
            return new WP_Error( 'salesforce_field_service_missing_subject', __( 'Subject is required to create a Work Order.', 'advanced-form-integration' ) );
        }

        $payload = array(
            'Subject' => $subject,
        );

        $map = array(
            'description'         => 'Description',
            'status'              => 'Status',
            'priority'            => 'Priority',
            'account_id'          => 'AccountId',
            'contact_id'          => 'ContactId',
            'owner_id'            => 'OwnerId',
            'work_type_id'        => 'WorkTypeId',
            'asset_id'            => 'AssetId',
            'service_contract_id' => 'ServiceContractId',
        );

        foreach ( $map as $key => $field_name ) {
            $value = $this->parse_field( $field_data, $key, $posted_data );

            if ( '' !== $value ) {
                $payload[ $field_name ] = $value;
            }
        }

        $duration = $this->parse_field( $field_data, 'duration', $posted_data );

        if ( '' !== $duration ) {
            $payload['Duration'] = (float) $duration;
        }

        $duration_type = $this->parse_field( $field_data, 'duration_type', $posted_data );

        if ( '' !== $duration_type ) {
            $payload['DurationType'] = $duration_type;
        } elseif ( isset( $payload['Duration'] ) ) {
            $payload['DurationType'] = 'Minutes';
        }

        $earliest = $this->parse_field( $field_data, 'earliest_start_time', $posted_data );

        if ( '' !== $earliest ) {
            $payload['EarliestStartTime'] = $earliest;
        }

        $due_date = $this->parse_field( $field_data, 'due_date', $posted_data );

        if ( '' !== $due_date ) {
            $payload['DueDate'] = $due_date;
        }

        $address_map = array(
            'street'      => 'Street',
            'city'        => 'City',
            'state'       => 'State',
            'postal_code' => 'PostalCode',
            'country'     => 'Country',
        );

        foreach ( $address_map as $key => $field_name ) {
            $value = $this->parse_field( $field_data, $key, $posted_data );

            if ( '' !== $value ) {
                $payload[ $field_name ] = $value;
            }
        }

        return $payload;
    }

    protected function parse_field( $field_data, $key, $posted_data ) {
        if ( ! isset( $field_data[ $key ] ) ) {
            return '';
        }

        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

        if ( is_array( $value ) ) {
            return '';
        }

        return is_string( $value ) ? trim( $value ) : '';
    }

    protected function api_request( $path, $method = 'POST', $payload = array(), $record = array() ) {
        $base = $this->instance_url ? rtrim( $this->instance_url, '/' ) : '';

        if ( '' === $base ) {
            return new WP_Error( 'salesforce_field_service_missing_instance', __( 'Salesforce instance URL is missing.', 'advanced-form-integration' ) );
        }

        $url = $base . '/services/data/' . self::API_VERSION . '/' . ltrim( $path, '/' );

        $args = array(
            'timeout' => 30,
            'method'  => strtoupper( $method ),
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Accept'        => 'application/json',
            ),
        );

        if ( in_array( $args['method'], array( 'POST', 'PATCH', 'PUT' ), true ) ) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode( $payload );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );
        $status   = wp_remote_retrieve_response_code( $response );

        if ( 401 === $status && $this->refresh_token ) {
            $refresh = $this->refresh_token();

            if ( ! is_wp_error( $refresh ) && $this->access_token ) {
                $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
                $response = wp_remote_request( esc_url_raw( $url ), $args );
                $status   = wp_remote_retrieve_response_code( $response );
            }
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( $status >= 400 ) {
            $body    = wp_remote_retrieve_body( $response );
            $message = $body ? $body : __( 'Salesforce Field Service request failed.', 'advanced-form-integration' );

            return new WP_Error( 'salesforce_field_service_http_error', $message, array( 'status' => $status ) );
        }

        return $response;
    }

    /**
     * OAuth Manager credential management methods
     */
    public function get_credentials() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials( 'salesforcefieldservice' );
        wp_send_json_success( $credentials );
    }

    public function save_credentials() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $platform = sanitize_text_field( $_POST['platform'] );
        $credentials = isset( $_POST['credentials'] ) ? $_POST['credentials'] : array();

        if ( 'salesforcefieldservice' === $platform ) {
            ADFOIN_OAuth_Manager::save_credentials( $platform, $credentials );
        }

        wp_send_json_success();
    }

    /**
     * Set credentials for OAuth Manager
     */
    public function set_credentials_by_id( $cred_id ) {
        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'salesforcefieldservice', $cred_id );

        if ( $credentials ) {
            $this->client_id = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
            $this->client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
            $this->access_token = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
            $this->refresh_token = isset( $credentials['refreshToken'] ) ? $credentials['refreshToken'] : '';
            $this->instance_url = isset( $credentials['instanceUrl'] ) ? $credentials['instanceUrl'] : '';
        }
    }
}

if ( ! function_exists( 'adfoin_salesforcefieldservice_merge_recursive' ) ) :
function adfoin_salesforcefieldservice_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( isset( $base[ $key ] ) && is_array( $base[ $key ] ) && is_array( $value ) ) {
            $base[ $key ] = adfoin_salesforcefieldservice_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }

    return $base;
}
endif;

ADFoin_Salesforce_Field_Service::get_instance();

endif;
