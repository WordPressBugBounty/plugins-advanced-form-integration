<?php

class ADFOIN_Moneybird extends Advanced_Form_Integration_OAuth2 {

    protected $platform_slug = 'moneybird';

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
        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_moneybird_fields', array( $this, 'ajax_get_fields' ) );
        add_action( 'wp_ajax_adfoin_get_moneybird_administrations', array( $this, 'ajax_get_administrations' ) );

        add_action( 'adfoin_moneybird_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );

        // OAuth Manager hooks
        add_action( 'wp_ajax_adfoin_get_moneybird_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_moneybird_credentials', array( $this, 'save_credentials' ) );
        add_filter( 'adfoin_get_credentials', array( $this, 'modify_credentials' ), 10, 2 );
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
                'name'        => 'scope',
                'label'       => __( 'Scopes', 'advanced-form-integration' ),
                'type'        => 'text',
                'required'    => false,
                'description' => __( 'Defaults to "sales_invoices documents contacts administrations".', 'advanced-form-integration' ),
            ),
        );

        $instructions = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'Log in to your Moneybird account and open Settings → Moneybird Labs → API clients.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Create a new API client and set the redirect URI to:', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li><code class="afi-code-block">' . esc_html( $redirect_uri ) . '</code></li>';
        $instructions .= '<li>' . __( 'Ensure the client has scopes for contacts, sales invoices, documents, and administrations.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Paste the Client ID and Client Secret here and click Save & Authorize.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        $config = array(
            'show_status' => true,
            'modal_title' => __( 'Connect Moneybird', 'advanced-form-integration' ),
            'submit_text' => __( 'Save & Authorize', 'advanced-form-integration' ),
        );

        ADFOIN_OAuth_Manager::render_oauth_settings_view( 'moneybird', __( 'Moneybird', 'advanced-form-integration' ), $fields, $instructions, $config );
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

    protected function save_data() {
        // Multi-account flow: persist tokens into the credential record
        // identified by cred_id under canonical snake_case keys.
        // (set_credentials_by_id reads both casings; the read shim normalizes
        // legacy camelCase records on the way out.)
        if ( ! empty( $this->cred_id ) ) {
            $this->persist_token_to_credential();
            return;
        }

        // Legacy single-account fallback for installs that haven't migrated
        // through the OAuth Manager UI yet.
        update_option( 'adfoin_moneybird_keys', maybe_serialize( array(
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
        ) ) );
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
        $state  = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';

        if ( $code ) {
            $redirect_to = add_query_arg(
                array(
                    'service' => 'authorize',
                    'action'  => 'adfoin_moneybird_auth_redirect',
                    'code'    => rawurlencode( $code ),
                    'state'   => rawurlencode( $state ),
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

        // admin_init fires for every logged-in user; only an admin should
        // be able to complete this OAuth flow (CWE-862).
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

        // Modern flow: consume_oauth_state returns the cred_id this auth
        // request was issued for. Setting cred_id (via set_credentials_by_id)
        // routes the new tokens into THIS record on save_data.
        $context = self::consume_oauth_state( $state, 'moneybird' );
        if ( $context && $context['cred_id'] ) {
            $this->set_credentials_by_id( $context['cred_id'] );
        } elseif ( $state && ! wp_verify_nonce( $state, 'adfoin_moneybird_state' ) ) {
            // Unknown / invalid state — bail.
            wp_safe_redirect( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=moneybird' ) );
            exit;
        }

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
                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_attr_e( 'Moneybird Account', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId">
                            <option value=""><?php esc_attr_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': credentialsLoading}"></div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=moneybird' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                    </td>
                </tr>

                <tr valign="top" class="alternate">
                    <td scope="row-title">
                        <label><?php esc_attr_e( 'Administration', 'advanced-form-integration' ); ?></label>
                    </td>
                    <td>
                        <select name="fieldData[administrationId]" v-model="fielddata.administrationId" required="required">
                            <option value=""><?php esc_attr_e( 'Select Administration...', 'advanced-form-integration' ); ?></option>
                            <option v-for="(label, id) in fielddata.administrations" :value="id">{{ label }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': administrationsLoading}"></div>
                        <p class="description"><?php esc_html_e( 'Pick the Moneybird administration this action should write to.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                    <td>
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <editable-field v-for="field in fields"
                    v-bind:key="field.value"
                    v-bind:field="field"
                    v-bind:trigger="trigger"
                    v-bind:action="action"
                    v-bind:fielddata="fielddata"></editable-field>
                <?php adfoin_pro_feature_notice( 'create_contact', 'Moneybird [PRO]', 'custom fields' ); ?>

                <tr class="alternate" v-if="action.task == 'create_sales_invoice'">
                    <th scope="row"><?php esc_html_e( 'Invoice Tips', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php printf(
                            /* translators: %s — admin URL for plan pricing page. */
                            wp_kses(
                                __( 'Provide an existing Contact ID, or map contact fields so AFI can create one. Then supply at least one invoice line via the "Invoice Lines (JSON)" field. Need an easier setup for WooCommerce orders? <a href="%s">Upgrade to AFI Pro</a> — it auto-builds invoice lines from your WC items and fills contact info from billing fields.', 'advanced-form-integration' ),
                                array( 'a' => array( 'href' => array() ) )
                            ),
                            esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) )
                        ); ?></p>
                    </td>
                </tr>
            </table>
        </script>
        <?php
    }

    public function ajax_get_fields() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';

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

    /**
     * AJAX: list administrations the connected account has access to.
     *
     * /administrations.json is the one Moneybird endpoint that lives
     * OUTSIDE the per-administration prefix, so it bypasses the regular
     * moneybird_request() helper. Cached per credential for 1 hour to
     * keep the action-editor responsive.
     */
    public function ajax_get_administrations() {
        adfoin_verify_nonce();

        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

        if ( ! $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id.', 'advanced-form-integration' ) ) );
        }

        $cache_key = 'adfoin_moneybird_admins_' . md5( $cred_id );
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            wp_send_json_success( $cached );
        }

        $this->set_credentials_by_id( $cred_id );

        $refresh = $this->maybe_refresh_access_token();
        if ( is_wp_error( $refresh ) ) {
            wp_send_json_error( array( 'message' => $refresh->get_error_message() ) );
        }

        $response = wp_remote_get(
            self::API_BASE . '/administrations.json',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Accept'        => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( $status >= 400 ) {
            wp_send_json_error( array( 'message' => sprintf( 'HTTP %d', $status ) ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $list = array();
        if ( is_array( $body ) ) {
            foreach ( $body as $admin ) {
                if ( empty( $admin['id'] ) ) {
                    continue;
                }
                $label = isset( $admin['name'] ) ? $admin['name'] : (string) $admin['id'];
                if ( ! empty( $admin['country'] ) ) {
                    $label .= ' (' . $admin['country'] . ')';
                }
                $list[ (string) $admin['id'] ] = $label;
            }
        }

        set_transient( $cache_key, $list, HOUR_IN_SECONDS );
        wp_send_json_success( $list );
    }
    protected function get_contact_fields() {
        // Whitelist matches Moneybird's documented Contact resource:
        //   https://developer.moneybird.com/api/contacts
        // Removed: `mobile` (not a Moneybird field), `invoice_email`
        // (the doc'd field is `send_invoices_to_email`), `notes` (lives
        // on a sub-resource POST /contacts/{id}/notes, not on the
        // contact body). administration_id is now picked via the
        // dedicated dropdown in the template, not via an editable-field.
        return array(
            array( 'key' => 'contact_id', 'value' => __( 'Contact ID (update existing)', 'advanced-form-integration' ) ),
            array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
            array( 'key' => 'firstname', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'lastname', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'attention', 'value' => __( 'Attention', 'advanced-form-integration' ) ),
            array( 'key' => 'customer_id', 'value' => __( 'Customer ID', 'advanced-form-integration' ), 'description' => __( 'Optional. If set, AFI looks up an existing contact by this ID before creating.', 'advanced-form-integration' ) ),
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
            array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'address1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'address2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
            array( 'key' => 'zipcode', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
            array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'country', 'value' => __( 'Country (ISO 2-letter)', 'advanced-form-integration' ) ),
            array( 'key' => 'tax_number', 'value' => __( 'VAT / Tax Number', 'advanced-form-integration' ) ),
            array( 'key' => 'chamber_of_commerce', 'value' => __( 'Chamber of Commerce', 'advanced-form-integration' ) ),
            array( 'key' => 'send_invoices_to_email', 'value' => __( 'Send Invoices To Email', 'advanced-form-integration' ) ),
            array( 'key' => 'send_invoices_to_attention', 'value' => __( 'Send Invoices To Attention', 'advanced-form-integration' ) ),
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
        $record      = isset( $data['record'] ) ? $data['record'] : array();
        $posted_data = isset( $data['posted_data'] ) ? $data['posted_data'] : array();

        if ( empty( $record ) ) {
            return;
        }

        $record_data = json_decode( $record['data'], true );
        $cred_id     = isset( $record_data['field_data']['credId'] ) ? $record_data['field_data']['credId'] : '';

        if ( $cred_id ) {
            $this->set_credentials_by_id( $cred_id );
        } else {
            $this->set_credentials();
        }

        $task = isset( $record['task'] ) ? $record['task'] : '';

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
        $administration_id = isset( $field_data['administrationId'] ) ? trim( (string) $field_data['administrationId'] ) : '';

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

        // No explicit Contact ID? Try Moneybird's customer_id lookup
        // endpoint so re-submissions update the same contact instead of
        // creating duplicates. This is the standard upsert path —
        // critical for WooCommerce flows where the same shopper might
        // hit checkout multiple times.
        if ( ! $contact_id && ! empty( $contact_payload['customer_id'] ) ) {
            $contact_id = $this->find_contact_id_by_customer_id( $administration_id, $contact_payload['customer_id'] );
        }

        if ( $contact_id ) {
            $this->moneybird_request( $administration_id, 'contacts/' . rawurlencode( $contact_id ), 'PATCH', array( 'contact' => $contact_payload ), $record );
            return;
        }

        $this->moneybird_request( $administration_id, 'contacts', 'POST', array( 'contact' => $contact_payload ), $record );
    }

    /**
     * Look up an existing contact by the user-supplied `customer_id`
     * field via Moneybird's GET /contacts/customer_id/{customer_id}
     * endpoint. Returns the Moneybird ID, or '' if not found / errored.
     *
     * Reused by Pro to make the "WooCommerce order → Moneybird invoice"
     * flow idempotent: pass the WC user ID (or email) as customer_id
     * and the second submission updates instead of duplicating.
     */
    public function find_contact_id_by_customer_id( $administration_id, $customer_id ) {
        if ( '' === (string) $administration_id || '' === (string) $customer_id ) {
            return '';
        }

        // Empty $record on purpose — a 404 here ("not found") is the
        // expected signal that no existing contact matches, NOT an
        // error worth logging against the submission.
        $response = $this->moneybird_request(
            $administration_id,
            'contacts/customer_id/' . rawurlencode( $customer_id ),
            'GET',
            array(),
            array()
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset( $body['id'] ) ? (string) $body['id'] : '';
    }

    protected function process_sales_invoice( $record, $posted_data ) {
        $record_data = json_decode( $record['data'], true );

        if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
            return;
        }

        $field_data        = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $administration_id = isset( $field_data['administrationId'] ) ? trim( (string) $field_data['administrationId'] ) : '';

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

            // Upsert by customer_id when supplied — keeps repeat
            // submissions (same shopper / form filler) on the same
            // Moneybird contact instead of duplicating.
            if ( ! empty( $contact_payload['customer_id'] ) ) {
                $contact_id = $this->find_contact_id_by_customer_id( $administration_id, $contact_payload['customer_id'] );
            }

            if ( $contact_id ) {
                $this->moneybird_request( $administration_id, 'contacts/' . rawurlencode( $contact_id ), 'PATCH', array( 'contact' => $contact_payload ), $record );
            } else {
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

                // Per Moneybird docs the email endpoint is `/sends_an_invoice`,
                // not `/send_invoice`. The old path returned 404.
                $this->moneybird_request( $administration_id, 'sales_invoices/' . rawurlencode( $invoice_id ) . '/sends_an_invoice', 'PATCH', $email_payload, $record );
            }
        }
    }

    protected function collect_contact_payload( $field_data, $posted_data, $require_minimum ) {
        // Map of (form-field-key) → (Moneybird Contact API field name).
        // Field names verified against https://developer.moneybird.com/api/contacts.
        $map = array(
            'company_name'               => 'company_name',
            'firstname'                  => 'firstname',
            'lastname'                   => 'lastname',
            'attention'                  => 'attention',
            'customer_id'                => 'customer_id',
            'email'                      => 'email',
            'phone'                      => 'phone',
            'address1'                   => 'address1',
            'address2'                   => 'address2',
            'zipcode'                    => 'zipcode',
            'city'                       => 'city',
            'country'                    => 'country',
            'tax_number'                 => 'tax_number',
            'chamber_of_commerce'        => 'chamber_of_commerce',
            'send_invoices_to_email'     => 'send_invoices_to_email',
            'send_invoices_to_attention' => 'send_invoices_to_attention',
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

    /**
     * Moneybird API request. Reused by the Pro add-on.
     *
     * @return array|WP_Error
     */
    public function moneybird_request( $administration_id, $endpoint, $method = 'POST', $body = array(), $record = array() ) {
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
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    /**
     * AJAX: save (or update) a credential record from the OAuth Manager modal,
     * then return the auth_url for the popup to navigate to. State is bound
     * to the credential id via issue_oauth_state so the callback knows
     * which record to write tokens back to.
     */
    public function save_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $platform    = 'moneybird';
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                if ( isset( $credentials[ $index ]['id'] ) && strpos( $credentials[ $index ]['id'], 'legacy_' ) === 0 ) {
                    delete_option( 'adfoin_moneybird_keys' );
                }
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
        $scope         = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : self::DEFAULT_SCOPE;
        if ( '' === $scope ) {
            $scope = self::DEFAULT_SCOPE;
        }

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'scope'         => $scope,
            'access_token'  => '',
            'refresh_token' => '',
            'expires_at'    => 0,
        );

        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( isset( $cred['id'] ) && $cred['id'] === $id ) {
                if ( isset( $cred['client_id'] ) && $cred['client_id'] === $client_id
                    && isset( $cred['client_secret'] ) && $cred['client_secret'] === $client_secret ) {
                    $new_data['access_token']  = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                    $new_data['refresh_token'] = isset( $cred['refresh_token'] ) ? $cred['refresh_token'] : '';
                    $new_data['expires_at']    = isset( $cred['expires_at'] ) ? $cred['expires_at'] : 0;
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

        $auth_url = add_query_arg(
            array(
                'response_type' => 'code',
                'client_id'     => $client_id,
                'redirect_uri'  => $this->get_redirect_uri(),
                'scope'         => $scope,
                'state'         => self::issue_oauth_state( 'moneybird', $id ),
            ),
            $this->authorization_endpoint
        );

        wp_send_json_success( array( 'auth_url' => $auth_url ) );
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
            // Read both snake_case (new records) and camelCase (legacy records)
            // — limitation #3 normalizes these on save going forward.
            $this->cred_id       = $cred_id;
            $this->client_id     = $credentials['client_id']     ?? $credentials['clientId']     ?? '';
            $this->client_secret = $credentials['client_secret'] ?? $credentials['clientSecret'] ?? '';
            $this->access_token  = $credentials['access_token']  ?? $credentials['accessToken']  ?? '';
            $this->refresh_token = $credentials['refresh_token'] ?? $credentials['refreshToken'] ?? '';
            $this->expires_at    = isset( $credentials['expires_at'] ) ? (int) $credentials['expires_at'] : ( isset( $credentials['expiresAt'] ) ? (int) $credentials['expiresAt'] : 0 );
            $this->scope = isset( $credentials['scope'] ) ? $credentials['scope'] : self::DEFAULT_SCOPE;
        }
    }

    /**
     * Expose legacy single-account credentials as a multi-account record.
     */
    public function modify_credentials( $credentials, $platform ) {
        if ( 'moneybird' !== $platform || ! empty( $credentials ) ) {
            return $credentials;
        }
        $option = (array) maybe_unserialize( get_option( 'adfoin_moneybird_keys' ) );
        if ( empty( $option['client_id'] ) || empty( $option['client_secret'] ) ) {
            return $credentials;
        }
        $credentials[] = array(
            'id'                  => 'legacy_123456',
            'title'               => __( 'Default Account (Legacy)', 'advanced-form-integration' ),
            'clientId'     => $option['client_id'],
            'clientSecret' => $option['client_secret'],
            'accessToken'        => isset( $option['access_token'] )  ? $option['access_token']  : '',
            'refreshToken'       => isset( $option['refresh_token'] ) ? $option['refresh_token'] : '',
        );
        return $credentials;
    }

}

ADFOIN_Moneybird::get_instance();