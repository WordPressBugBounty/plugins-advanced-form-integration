<?php

class ADFOIN_Moneybird extends Advanced_Form_Integration_OAuth2 {

    const AUTHORIZATION_ENDPOINT = 'https://moneybird.com/oauth/authorize';
    const TOKEN_ENDPOINT         = 'https://moneybird.com/oauth/token';
    const REFRESH_TOKEN_ENDPOINT = 'https://moneybird.com/oauth/token';
    const API_BASE               = 'https://moneybird.com/api/v2';
    const DEFAULT_SCOPE          = 'sales_invoices documents contacts administrations';

    private static $instance;

    protected $client_id     = '';
    protected $client_secret = '';
    protected $access_token  = '';
    protected $refresh_token = '';
    protected $expires_at    = 0;
    protected $scope         = self::DEFAULT_SCOPE;

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
        $option = (array) maybe_unserialize( get_option( 'adfoin_moneybird_keys' ) );

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

        if ( isset( $option['expires_at'] ) ) {
            $this->expires_at = (int) $option['expires_at'];
        }

        if ( isset( $option['scope'] ) ) {
            $this->scope = $option['scope'];
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
        add_action( 'admin_post_adfoin_save_moneybird_keys', array( $this, 'save_keys' ) );

        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_moneybird_fields', array( $this, 'ajax_get_fields' ) );

        add_action( 'adfoin_moneybird_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_moneybird_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_moneybird_credentials', array( $this, 'save_credentials' ) );
    }

    public function register_actions( $actions ) {
        $actions['moneybird'] = array(
            'title' => __( 'Moneybird', 'advanced-form-integration' ),
            'tasks' => array(
                'create_contact'       => __( 'Create Contact', 'advanced-form-integration' ),
                'create_sales_invoice' => __( 'Create Sales Invoice', 'advanced-form-integration' ),
            ),
        );

        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['moneybird'] = __( 'Moneybird', 'advanced-form-integration' );

        return $providers;
    }

    public function render_settings( $current_tab ) {
        if ( 'moneybird' !== $current_tab ) {
            return;
        }

        if ( ! class_exists( 'ADFOIN_OAuth_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-oauth-manager.php';
        }

        ADFOIN_OAuth_Manager::render_oauth_settings_view(
            'moneybird',
            __( 'Moneybird', 'advanced-form-integration' ),
            $this->get_redirect_uri(),
            array(
                __( 'Log in to your Moneybird account and open Settings → Moneybird Labs → API clients.', 'advanced-form-integration' ),
                __( 'Create a new API client, set the redirect URI below, and copy the Client ID and Client Secret.', 'advanced-form-integration' ),
                __( 'Ensure the client has scopes for contacts, sales invoices, documents, and administrations.', 'advanced-form-integration' ),
                __( 'Paste the credentials here and click "Save & Authorize" to complete OAuth.', 'advanced-form-integration' ),
                __( 'After authorizing, map Moneybird fields inside each integration to push contacts or invoices.', 'advanced-form-integration' ),
            ),
            array(
                'scope' => array(
                    'label' => __( 'Scopes', 'advanced-form-integration' ),
                    'default' => self::DEFAULT_SCOPE,
                    'description' => __( 'Defaults to "sales_invoices documents contacts administrations".', 'advanced-form-integration' ),
                ),
            )
        );
    }
    public function save_keys() {
        if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ), 'adfoin_moneybird_settings' ) ) {
            wp_die( esc_html__( 'Security check failed', 'advanced-form-integration' ) );
        }

        $this->client_id     = isset( $_POST['adfoin_moneybird_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_moneybird_client_id'] ) ) : '';
        $this->client_secret = isset( $_POST['adfoin_moneybird_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_moneybird_client_secret'] ) ) : '';
        $scope               = isset( $_POST['adfoin_moneybird_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_moneybird_scope'] ) ) : self::DEFAULT_SCOPE;

        $this->scope = $scope;
        $this->save_data();
        $this->authorize( $scope );

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=moneybird' ) );
        exit;
    }

    protected function authorize( string $scope = '' ) {
        $scope = $scope ? $scope : self::DEFAULT_SCOPE;

        $endpoint = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $this->client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $scope,
                'state'         => wp_create_nonce( 'adfoin_moneybird_state' ),
            ),
            $this->authorization_endpoint
        );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit;
        }
    }

    protected function request_token( $code ) {
        $body = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->get_redirect_uri(),
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        );

        $response = wp_remote_post(
            $this->token_endpoint,
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body'    => http_build_query( $body, '', '&' ),
                'timeout' => 30,
            )
        );

        $this->handle_token_response( $response );

        return $response;
    }

    protected function refresh_token() {
        if ( ! $this->refresh_token ) {
            return new WP_Error( 'moneybird_missing_refresh_token', __( 'Refresh token is missing.', 'advanced-form-integration' ) );
        }

        $body = array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refresh_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        );

        $response = wp_remote_post(
            $this->refresh_token_endpoint,
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body'    => http_build_query( $body, '', '&' ),
                'timeout' => 30,
            )
        );

        $this->handle_token_response( $response );

        return $response;
    }

    protected function handle_token_response( $response ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['access_token'] ) ) {
            $this->access_token = $body['access_token'];
        }

        if ( isset( $body['refresh_token'] ) ) {
            $this->refresh_token = $body['refresh_token'];
        }

        if ( isset( $body['expires_in'] ) ) {
            $this->expires_at = time() + (int) $body['expires_in'];
        }

        $this->save_data();
    }

    protected function get_redirect_uri() {
        return site_url( '/wp-json/advancedformintegration/moneybird' );
    }

    protected function save_data( $extra = array() ) {
        $data = array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_at'    => $this->expires_at,
            'scope'         => $this->scope,
        );

        if ( ! empty( $extra ) && is_array( $extra ) ) {
            $data = array_merge( $data, $extra );
        }

        update_option( 'adfoin_moneybird_keys', maybe_serialize( $data ) );
    }

    protected function reset_data() {
        $this->client_id     = '';
        $this->client_secret = '';
        $this->access_token  = '';
        $this->refresh_token = '';
        $this->expires_at    = 0;
        $this->save_data();
    }

    public function set_credentials() {
        $option = (array) maybe_unserialize( get_option( 'adfoin_moneybird_keys' ) );

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

        if ( isset( $option['expires_at'] ) ) {
            $this->expires_at = (int) $option['expires_at'];
        }

        if ( isset( $option['scope'] ) ) {
            $this->scope = $option['scope'];
        }
    }

    public function register_webhook_route() {
        register_rest_route(
            'advancedformintegration',
            '/moneybird',
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
                    'action'  => 'adfoin_moneybird_auth_redirect',
                    'code'    => rawurlencode( $code ),
                ),
                admin_url( 'admin.php?page=advanced-form-integration' )
            );

            wp_safe_redirect( $redirect_to );
            exit;
        }

        return new WP_Error( 'moneybird_missing_code', __( 'Authorization code not found.', 'advanced-form-integration' ) );
    }

    public function auth_redirect() {
        if ( ! isset( $_GET['action'] ) ) {
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

        if ( 'adfoin_moneybird_auth_redirect' !== $action ) {
            return;
        }

        $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

        if ( $code ) {
            $this->request_token( $code );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=moneybird' ) );
        exit;
    }

    public function render_action_template() {
        ?>
        <script type="text/template" id="moneybird-action-template">
            <table class="form-table" v-if="action.task == 'create_contact' || action.task == 'create_sales_invoice'">
                <tr valign="top">
                    <th scope="row"><?php esc_attr_e( 'Moneybird Account', 'advanced-form-integration' ); ?></th>
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
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=moneybird' ) ); ?>" target="_blank" class="button"><?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td>
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                    v-bind:key="field.value"
                    v-bind:field="field"
                    v-bind:trigger="trigger"
                    v-bind:action="action"
                    v-bind:fielddata="fielddata"></editable-field>

                <tr class="alternate" v-if="action.task == 'create_sales_invoice'">
                    <th scope="row"><?php esc_html_e( 'Invoice Tips', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php esc_html_e( 'Provide an Administration ID and either an existing contact ID or the contact details to create one automatically. Use the "Invoice Lines (JSON)" field to add at least one line item.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
            </table>
        </script>
        <?php
    }

    public function ajax_get_fields() {
        if ( ! adfoin_verify_nonce() ) {
            return;
        }

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
        $task = isset( $_POST['task'] ) ? sanitize_text_field( $_POST['task'] ) : '';

        // Set credentials if provided
        if ( $cred_id ) {
            $this->set_credentials_by_id( $cred_id );
        }

        $fields = array();

        if ( 'create_sales_invoice' === $task ) {
            $fields = $this->get_sales_invoice_fields();
        } else {
            $fields = $this->get_contact_fields();
        }

        wp_send_json_success( $fields );
    }
    protected function get_contact_fields() {
        return array(
            array( 'key' => 'administration_id', 'value' => __( 'Administration ID', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'contact_id', 'value' => __( 'Contact ID (update existing)', 'advanced-form-integration' ) ),
            array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
            array( 'key' => 'firstname', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'lastname', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'attention', 'value' => __( 'Attention', 'advanced-form-integration' ) ),
            array( 'key' => 'customer_id', 'value' => __( 'Customer ID', 'advanced-form-integration' ) ),
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
            array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'mobile', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'address1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'address2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
            array( 'key' => 'zipcode', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
            array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
            array( 'key' => 'country', 'value' => __( 'Country (ISO code)', 'advanced-form-integration' ) ),
            array( 'key' => 'tax_number', 'value' => __( 'VAT / Tax Number', 'advanced-form-integration' ) ),
            array( 'key' => 'chamber_of_commerce', 'value' => __( 'Chamber of Commerce', 'advanced-form-integration' ) ),
            array( 'key' => 'invoice_email', 'value' => __( 'Invoice Email', 'advanced-form-integration' ) ),
            array( 'key' => 'notes', 'value' => __( 'Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        );
    }

    protected function get_sales_invoice_fields() {
        $contact_fields = $this->get_contact_fields();

        $invoice_fields = array(
            array( 'key' => 'contact_id', 'value' => __( 'Contact ID (existing contact)', 'advanced-form-integration' ) ),
            array( 'key' => 'reference', 'value' => __( 'Reference', 'advanced-form-integration' ) ),
            array( 'key' => 'invoice_date', 'value' => __( 'Invoice Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'due_date', 'value' => __( 'Due Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'workflow_id', 'value' => __( 'Workflow ID', 'advanced-form-integration' ) ),
            array( 'key' => 'document_style_id', 'value' => __( 'Document Style ID', 'advanced-form-integration' ) ),
            array( 'key' => 'prices_are_incl_tax', 'value' => __( 'Prices Are Inclusive Tax (true/false)', 'advanced-form-integration' ) ),
            array( 'key' => 'payment_conditions', 'value' => __( 'Payment Conditions', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'details_json', 'value' => __( 'Invoice Lines (JSON Array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: [{"description":"Service","price":100,"tax_rate_id":"..."}]', 'advanced-form-integration' ) ),
            array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields (JSON)', 'advanced-form-integration' ), 'type' => 'textarea' ),
            array( 'key' => 'send_email', 'value' => __( 'Send Invoice Email (true/false)', 'advanced-form-integration' ) ),
            array( 'key' => 'email_message', 'value' => __( 'Invoice Email Message', 'advanced-form-integration' ), 'type' => 'textarea' ),
        );

        // Remove duplicate contact_id entry from base contact fields when merging.
        $contact_fields = array_filter( $contact_fields, function ( $field ) {
            return 'contact_id' !== $field['key'];
        } );

        return array_merge( $contact_fields, $invoice_fields );
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

        if ( empty( $record ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );
        $task        = $record_data['action_data']['task'] ?? '';

        if ( 'create_sales_invoice' === $task ) {
            $this->process_sales_invoice( $record, $posted_data );
        } else {
            $this->process_contact( $record, $posted_data );
        }
    }

    protected function process_contact( $record, $posted_data ) {
        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data        = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $administration_id = $this->parse_field_value( $field_data, 'administration_id', $posted_data );

        if ( '' === $administration_id ) {
            adfoin_add_to_log( new WP_Error( 'moneybird_missing_administration', __( 'Moneybird administration ID is required.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $contact_payload = $this->collect_contact_payload( $field_data, $posted_data, true );

        if ( is_wp_error( $contact_payload ) ) {
            adfoin_add_to_log( $contact_payload, '', array(), $record );
            return;
        }

        $contact_id = $this->parse_field_value( $field_data, 'contact_id', $posted_data );

        if ( $contact_id ) {
            $this->moneybird_request( $administration_id, 'contacts/' . rawurlencode( $contact_id ), 'PATCH', array( 'contact' => $contact_payload ), $record );
            return;
        }

        $this->moneybird_request( $administration_id, 'contacts', 'POST', array( 'contact' => $contact_payload ), $record );
    }

    protected function process_sales_invoice( $record, $posted_data ) {
        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data        = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $administration_id = $this->parse_field_value( $field_data, 'administration_id', $posted_data );

        if ( '' === $administration_id ) {
            adfoin_add_to_log( new WP_Error( 'moneybird_missing_administration', __( 'Moneybird administration ID is required.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }

        $contact_id = $this->parse_field_value( $field_data, 'contact_id', $posted_data );

        if ( '' === $contact_id ) {
            $contact_payload = $this->collect_contact_payload( $field_data, $posted_data, false );

            if ( is_wp_error( $contact_payload ) ) {
                adfoin_add_to_log( $contact_payload, '', array(), $record );
                return;
            }

            if ( empty( $contact_payload ) ) {
                adfoin_add_to_log( new WP_Error( 'moneybird_missing_contact', __( 'Provide a contact ID or map contact fields to create a new contact.', 'advanced-form-integration' ) ), '', array(), $record );
                return;
            }

            $response = $this->moneybird_request( $administration_id, 'contacts', 'POST', array( 'contact' => $contact_payload ), $record );

            if ( is_wp_error( $response ) ) {
                return;
            }

            $contact_data = json_decode( wp_remote_retrieve_body( $response ), true );
            $contact_id   = $contact_data['id'] ?? '';

            if ( '' === $contact_id ) {
                adfoin_add_to_log( new WP_Error( 'moneybird_contact_missing_id', __( 'Moneybird contact ID not returned.', 'advanced-form-integration' ) ), '', array(), $record );
                return;
            }
        }

        $invoice_payload = $this->collect_invoice_payload( $field_data, $posted_data, $contact_id );

        if ( is_wp_error( $invoice_payload ) ) {
            adfoin_add_to_log( $invoice_payload, '', array(), $record );
            return;
        }

        $response = $this->moneybird_request( $administration_id, 'sales_invoices', 'POST', array( 'sales_invoice' => $invoice_payload ), $record );

        if ( is_wp_error( $response ) ) {
            return;
        }

        $send_invoice = $this->parse_field_value( $field_data, 'send_email', $posted_data );

        if ( '' !== $send_invoice && $this->truthy( $send_invoice ) ) {
            $invoice_data = json_decode( wp_remote_retrieve_body( $response ), true );
            $invoice_id   = $invoice_data['id'] ?? '';

            if ( $invoice_id ) {
                $email_payload = array();
                $message       = $this->parse_field_value( $field_data, 'email_message', $posted_data );

                if ( '' !== $message ) {
                    $email_payload['email'] = array( 'message' => $message );
                }

                $this->moneybird_request( $administration_id, 'sales_invoices/' . rawurlencode( $invoice_id ) . '/send_invoice', 'PATCH', $email_payload, $record );
            }
        }
    }

    protected function collect_contact_payload( $field_data, $posted_data, $require_minimum ) {
        $map = array(
            'company_name'        => 'company_name',
            'firstname'           => 'firstname',
            'lastname'            => 'lastname',
            'attention'           => 'attention',
            'customer_id'         => 'customer_id',
            'email'               => 'email',
            'phone'               => 'phone',
            'mobile'              => 'mobile_phone',
            'address1'            => 'address1',
            'address2'            => 'address2',
            'zipcode'             => 'zipcode',
            'city'                => 'city',
            'state'               => 'state',
            'country'             => 'country',
            'tax_number'          => 'tax_number',
            'chamber_of_commerce' => 'chamber_of_commerce',
            'invoice_email'       => 'invoice_email',
            'notes'               => 'notes',
        );

        $payload = array();

        foreach ( $map as $key => $api_field ) {
            $value = $this->parse_field_value( $field_data, $key, $posted_data );

            if ( '' === $value ) {
                continue;
            }

            $payload[ $api_field ] = $value;
        }

        if ( $require_minimum ) {
            $has_name = ! empty( $payload['company_name'] ) || ( ! empty( $payload['firstname'] ) && ! empty( $payload['lastname'] ) );

            if ( ! $has_name ) {
                return new WP_Error( 'moneybird_contact_missing_name', __( 'Moneybird requires a company name or first and last name.', 'advanced-form-integration' ) );
            }
        }

        return $payload;
    }

    protected function collect_invoice_payload( $field_data, $posted_data, $contact_id ) {
        $details_json = $this->parse_field_value( $field_data, 'details_json', $posted_data );
        $details      = array();

        if ( '' !== $details_json ) {
            $decoded = json_decode( $details_json, true );

            if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
                return new WP_Error( 'moneybird_invalid_details_json', __( 'Invoice lines JSON is invalid.', 'advanced-form-integration' ) );
            }

            $details = $decoded;
        }

        if ( empty( $details ) ) {
            return new WP_Error( 'moneybird_missing_invoice_lines', __( 'Provide at least one invoice line in the Invoice Lines (JSON) field.', 'advanced-form-integration' ) );
        }

        $payload = array(
            'contact_id'         => $contact_id,
            'details_attributes' => $details,
        );

        $map = array(
            'reference'          => 'reference',
            'invoice_date'       => 'invoice_date',
            'due_date'           => 'due_date',
            'workflow_id'        => 'workflow_id',
            'document_style_id'  => 'document_style_id',
            'payment_conditions' => 'payment_conditions',
        );

        foreach ( $map as $key => $api_field ) {
            $value = $this->parse_field_value( $field_data, $key, $posted_data );

            if ( '' === $value ) {
                continue;
            }

            $payload[ $api_field ] = $value;
        }

        $incl_tax = $this->parse_field_value( $field_data, 'prices_are_incl_tax', $posted_data );

        if ( '' !== $incl_tax ) {
            $payload['prices_are_incl_tax'] = $this->truthy( $incl_tax );
        }

        $custom_fields = $this->parse_field_value( $field_data, 'custom_fields_json', $posted_data );

        if ( '' !== $custom_fields ) {
            $decoded = json_decode( $custom_fields, true );

            if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
                $payload['custom_fields'] = $decoded;
            }
        }

        return $payload;
    }

    protected function moneybird_request( $administration_id, $endpoint, $method = 'POST', $body = array(), $record = array() ) {
        $maybe_refreshed = $this->maybe_refresh_access_token();

        if ( is_wp_error( $maybe_refreshed ) ) {
            if ( $record ) {
                adfoin_add_to_log( $maybe_refreshed, '', array(), $record );
            }
            return $maybe_refreshed;
        }

        $url = self::API_BASE . '/' . rawurlencode( $administration_id ) . '/' . ltrim( $endpoint, '/' );

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
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );
        $status   = wp_remote_retrieve_response_code( $response );

        if ( 401 === $status ) {
            $refresh = $this->refresh_token();

            if ( ! is_wp_error( $refresh ) && $this->access_token ) {
                $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
                $response                         = wp_remote_request( esc_url_raw( $url ), $args );
                $status                           = wp_remote_retrieve_response_code( $response );
            }
        }

        if ( $record ) {
            adfoin_add_to_log( $response, $url, $args, $record );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( $status >= 400 ) {
            $body  = wp_remote_retrieve_body( $response );
            $error = $body ? $body : __( 'Moneybird request failed.', 'advanced-form-integration' );

            return new WP_Error( 'moneybird_http_error', $error, array( 'status' => $status ) );
        }

        return $response;
    }

    protected function maybe_refresh_access_token() {
        if ( ! $this->access_token ) {
            return new WP_Error( 'moneybird_missing_token', __( 'Moneybird access token is missing. Re-authorize the connection.', 'advanced-form-integration' ) );
        }

        if ( $this->expires_at && $this->expires_at < time() + 60 ) {
            $refresh = $this->refresh_token();

            if ( is_wp_error( $refresh ) ) {
                return $refresh;
            }
        }

        return true;
    }

    protected function parse_field_value( $field_data, $key, $posted_data ) {
        if ( ! isset( $field_data[ $key ] ) ) {
            return '';
        }

        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

        if ( is_array( $value ) ) {
            return '';
        }

        return is_string( $value ) ? trim( $value ) : '';
    }

    protected function truthy( $value ) {
        $value = strtolower( trim( (string) $value ) );

        return in_array( $value, array( '1', 'yes', 'true', 'on' ), true );
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

        $credentials = ADFOIN_OAuth_Manager::get_credentials( 'moneybird' );
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

        if ( 'moneybird' === $platform ) {
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

        $credentials = ADFOIN_OAuth_Manager::get_credentials_by_id( 'moneybird', $cred_id );

        if ( $credentials ) {
            $this->client_id = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
            $this->client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
            $this->access_token = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
            $this->refresh_token = isset( $credentials['refreshToken'] ) ? $credentials['refreshToken'] : '';
            $this->expires_at = isset( $credentials['expiresAt'] ) ? (int) $credentials['expiresAt'] : 0;
            $this->scope = isset( $credentials['scope'] ) ? $credentials['scope'] : self::DEFAULT_SCOPE;
        }
    }
}

ADFOIN_Moneybird::get_instance();