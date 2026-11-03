<?php

class ADFOIN_Moneybird {

    const API_BASE = 'https://moneybird.com/api/v2';

    private static $instance;

    protected $cred_id      = '';
    protected $access_token = '';

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );

        add_action( 'adfoin_settings_view', array( $this, 'render_settings' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'render_action_template' ), 10, 1 );
        add_action( 'wp_ajax_adfoin_get_moneybird_fields', array( $this, 'ajax_get_fields' ) );
        add_action( 'wp_ajax_adfoin_get_moneybird_administrations', array( $this, 'ajax_get_administrations' ) );

        add_action( 'adfoin_moneybird_job_queue', array( $this, 'handle_job_queue' ), 10, 1 );

        // Account manager hooks
        add_action( 'wp_ajax_adfoin_get_moneybird_credentials', array( $this, 'get_credentials' ) );
        add_action( 'wp_ajax_adfoin_save_moneybird_credentials', array( $this, 'save_credentials' ) );
        add_filter( 'adfoin_skip_legacy_save_bridge', array( $this, 'skip_legacy_save_bridge' ), 10, 2 );
    }

    /**
     * Keep the generic account-manager bridge away from Moneybird saves so the
     * token is verified and stored by save_credentials().
     */
    public function skip_legacy_save_bridge( $skip, $platform ) {
        return 'moneybird' === $platform ? true : $skip;
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

        if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
            require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
        }

        $fields = array(
            array(
                'name'          => 'access_token',
                'label'         => __( 'API Token', 'advanced-form-integration' ),
                'type'          => 'password',
                'required'      => true,
                'mask'          => true,
                'show_in_table' => true,
            ),
        );

        $instructions  = '<ol class="afi-instructions-list">';
        $instructions .= '<li>' . __( 'In Moneybird, open Settings → Connections and under External applications click New API token.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Give it a name and tick the Sales invoices and Documents permissions, then copy the token.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '<li>' . __( 'Click Add Account, enter a Title, paste the token into API Token and click Save Account.', 'advanced-form-integration' ) . '</li>';
        $instructions .= '</ol>';

        ADFOIN_Account_Manager::render_settings_view( 'moneybird', __( 'Moneybird', 'advanced-form-integration' ), $fields, $instructions );
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

        if ( ! $this->access_token ) {
            wp_send_json_error( array( 'message' => __( 'Moneybird API token is missing. Edit the account and add a token.', 'advanced-form-integration' ) ) );
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

        $this->set_credentials_by_id( $cred_id );

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
        if ( ! $this->access_token ) {
            $error = new WP_Error( 'moneybird_missing_token', __( 'Moneybird API token is missing. Edit the account and add a token.', 'advanced-form-integration' ) );
            if ( $record ) {
                adfoin_add_to_log( $error, '', array(), $record );
            }
            return $error;
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
     * Account manager credential methods
     */
    public function get_credentials() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( isset( $_POST['_nonce'] ) ? $_POST['_nonce'] : '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        wp_send_json_success( $this->safe_credentials_list() );
    }

    /**
     * AJAX: save (or update) an API-token account from the settings modal.
     * The token is checked against Moneybird before it is stored.
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
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => 'Deleted' ) );
            }
            wp_send_json_error( __( 'Invalid index', 'advanced-form-integration' ) );
        }

        $id            = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $api_token = isset( $_POST['access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) ) : '';

        $existing = array();
        foreach ( $credentials as $cred ) {
            if ( $id && isset( $cred['id'] ) && $cred['id'] === $id ) {
                $existing = $cred;
                break;
            }
        }

        if ( '' === $api_token ) {
            wp_send_json_error( array( 'message' => __( 'Enter a Moneybird API token.', 'advanced-form-integration' ) ) );
        }

        $check = $this->verify_api_token( $api_token );
        if ( is_wp_error( $check ) ) {
            wp_send_json_error( array( 'message' => $check->get_error_message() ) );
        }

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        // The API token is used directly as the Bearer token; there is no refresh step.
        $new_data = array(
            'id'            => $id,
            'title'         => $title,
            'access_token'  => $api_token,
        );

        if ( $existing ) {
            foreach ( $credentials as &$cred ) {
                if ( isset( $cred['id'] ) && $cred['id'] === $id ) {
                    $cred = $new_data;
                    break;
                }
            }
            unset( $cred );
        } else {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        wp_send_json_success( array( 'message' => __( 'Connected', 'advanced-form-integration' ) ) );
    }

    /**
     * Check a personal API token against GET /administrations before saving it.
     *
     * @return true|WP_Error
     */
    protected function verify_api_token( $api_token ) {
        $response = wp_remote_get(
            self::API_BASE . '/administrations',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'Accept'        => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );

        if ( 401 === $status || 403 === $status ) {
            return new WP_Error( 'moneybird_invalid_token', __( 'Moneybird rejected this API token. Check that it was copied in full and has not been revoked.', 'advanced-form-integration' ) );
        }

        if ( $status >= 400 ) {
            /* translators: %d: HTTP status code */
            return new WP_Error( 'moneybird_token_check_failed', sprintf( __( 'Moneybird returned HTTP %d while checking the API token.', 'advanced-form-integration' ), $status ) );
        }

        return true;
    }

    /**
     * Accounts for the settings table and the account picker, without the token.
     */
    protected function safe_credentials_list() {
        $credentials = adfoin_read_credentials( 'moneybird' );
        $list        = array();

        foreach ( (array) $credentials as $cred ) {
            if ( ! is_array( $cred ) ) {
                continue;
            }

            $connected = ! empty( $cred['access_token'] );
            unset( $cred['access_token'] );
            $cred['connected']         = $connected;
            $cred['connection_failed'] = false;
            $list[]                    = $cred;
        }

        return $list;
    }

    /**
     * Load the API token for a saved account.
     */
    public function set_credentials_by_id( $cred_id ) {
        $this->cred_id      = $cred_id;
        $this->access_token = '';

        foreach ( (array) adfoin_read_credentials( 'moneybird' ) as $cred ) {
            if ( isset( $cred['id'] ) && $cred['id'] === $cred_id ) {
                $this->access_token = isset( $cred['access_token'] ) ? $cred['access_token'] : '';
                break;
            }
        }
    }
}

ADFOIN_Moneybird::get_instance();

function adfoin_moneybird_send_data( $record, $posted_data ) {
    ADFOIN_Moneybird::get_instance()->handle_job_queue( array(
        'record'      => $record,
        'posted_data' => $posted_data,
    ) );
}