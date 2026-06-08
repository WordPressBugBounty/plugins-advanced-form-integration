<?php

/**
 * Dynamics 365 Sales connector.
 *
 * Sales sits on the same Dataverse instance as Dynamics 365 CRM, so this
 * platform reuses the connection configured under the "Dynamics 365 CRM"
 * tab and delegates all token + API work to ADFOIN_Dynamics365.
 *
 * Tasks:
 *   - create_opportunity   — opportunities entity
 *   - create_quote         — quotes entity
 *   - create_salesorder    — salesorders entity
 *   - create_invoice       — invoices entity
 *
 * Each "deal-ish" task accepts a customer the same way: an account by name
 * or a contact by email, whichever the form has. The customerid lookup on
 * Dynamics is polymorphic — both bind cleanly via @odata.bind.
 */
class ADFOIN_Dynamics365Sales {

    private static $instance;

    public static function get_instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'adfoin_action_providers', array( $this, 'register_actions' ), 10, 1 );
        add_filter( 'adfoin_settings_tabs', array( $this, 'register_settings_tab' ), 10, 1 );
        add_action( 'adfoin_settings_view', array( $this, 'settings_view' ), 10, 1 );
        add_action( 'adfoin_action_fields', array( $this, 'action_fields' ), 10, 1 );

        add_action( 'wp_ajax_adfoin_get_dynamics365sales_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dynamics365sales'] = array(
            'title' => 'Dynamics 365 Sales',
            'tasks' => array(
                'create_opportunity' => __( 'Create Opportunity', 'advanced-form-integration' ),
                'create_quote'       => __( 'Create Quote', 'advanced-form-integration' ),
                'create_salesorder'  => __( 'Create Sales Order', 'advanced-form-integration' ),
                'create_invoice'     => __( 'Create Invoice', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dynamics365sales'] = 'Dynamics 365 Sales';
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'dynamics365sales' !== $current_tab ) {
            return;
        }
        $crm_url = admin_url( 'admin.php?page=advanced-form-integration-settings&tab=dynamics365' );
        ?>
        <div class="afi-container" id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="afi-accounts-card">
                        <div class="afi-accounts-header">
                            <h2 class="afi-accounts-title">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php esc_html_e( 'Dynamics 365 Sales', 'advanced-form-integration' ); ?>
                            </h2>
                        </div>
                        <div class="afi-accounts-body" style="padding:20px;">
                            <p>
                                <?php
                                printf(
                                    wp_kses(
                                        __( 'Dynamics 365 Sales shares its data store (Dataverse) with Dynamics 365 CRM, so it reuses the connection you configure under the <a href="%s">Dynamics 365 CRM</a> tab. There is nothing to configure here.', 'advanced-form-integration' ),
                                        array( 'a' => array( 'href' => array() ) )
                                    ),
                                    esc_url( $crm_url )
                                );
                                ?>
                            </p>
                            <p>
                                <a href="<?php echo esc_url( $crm_url ); ?>" class="button button-primary">
                                    <?php esc_html_e( 'Open Dynamics 365 CRM settings', 'advanced-form-integration' ); ?>
                                </a>
                            </p>
                            <hr>
                            <h3><?php esc_html_e( 'What this platform does', 'advanced-form-integration' ); ?></h3>
                            <ul style="list-style:disc;padding-left:20px;">
                                <li><?php esc_html_e( 'Creates Opportunities, Quotes, Sales Orders, and Invoices.', 'advanced-form-integration' ); ?></li>
                                <li><?php esc_html_e( 'Auto-resolves the customer: looks up an Account by name (preferred) or a Contact by email, and binds the resulting record as the customerid.', 'advanced-form-integration' ); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function action_fields() {
        $crm = class_exists( 'ADFOIN_Dynamics365' ) ? ADFOIN_Dynamics365::get_instance() : null;
        ?>
        <script type="text/template" id="dynamics365sales-action-template">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_attr_e( 'Dynamics 365 Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                            <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                            <?php
                            if ( $crm && method_exists( $crm, 'get_credentials_list' ) ) {
                                $crm->get_credentials_list();
                            }
                            ?>
                        </select>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=dynamics365' ) ); ?>"
                           target="_blank" style="margin-left:10px;text-decoration:none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top:3px;"></span>
                            <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
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

                <?php if ( function_exists( 'adfoin_fs' ) && adfoin_fs()->is_not_paying() ) : ?>
                <tr class="alternate">
                    <th scope="row"><?php esc_html_e( 'Need custom Dataverse fields?', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p><?php printf( wp_kses( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">AFI Pro</a> to map any custom column on Opportunity, Quote, Sales Order, or Invoice (new_*, prefix_*, etc.).', 'advanced-form-integration' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </script>
        <?php
    }

    public function ajax_get_fields() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }
        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_opportunity';
        wp_send_json_success( $this->get_fields_for_task( $task ) );
    }

    /**
     * The customer-resolution fields (account_name, contact_email) are
     * shared across every "deal-ish" task because Dynamics's customerid
     * is polymorphic.
     */
    protected function customer_fields() {
        return array(
            array( 'key' => 'account_name',  'value' => __( 'Account Name (preferred; looks up an Account)', 'advanced-form-integration' ), 'description' => __( 'If an Account with this name exists, it will be linked as the customer.', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_email', 'value' => __( 'Contact Email (fallback; looks up or creates a Contact)', 'advanced-form-integration' ), 'description' => __( 'Used when no matching Account is found.', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_first', 'value' => __( 'Contact First Name (used if contact must be created)', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_last',  'value' => __( 'Contact Last Name (used if contact must be created)', 'advanced-form-integration' ) ),
        );
    }

    protected function get_fields_for_task( $task ) {
        $customer = $this->customer_fields();

        if ( 'create_opportunity' === $task ) {
            return array_merge( $customer, array(
                array( 'key' => 'name',                   'value' => __( 'Opportunity Name (Topic)', 'advanced-form-integration' ), 'required' => true ),
                array( 'key' => 'description',            'value' => __( 'Description / Notes', 'advanced-form-integration' ) ),
                array( 'key' => 'estimatedvalue',         'value' => __( 'Estimated Revenue', 'advanced-form-integration' ) ),
                array( 'key' => 'estimatedclosedate',     'value' => __( 'Estimated Close Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
                array( 'key' => 'budgetamount',           'value' => __( 'Budget Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'closeprobability',       'value' => __( 'Probability % (integer)', 'advanced-form-integration' ) ),
                array( 'key' => 'purchasetimeframe',      'value' => __( 'Purchase Timeframe (option set)', 'advanced-form-integration' ) ),
                array( 'key' => 'purchaseprocess',        'value' => __( 'Purchase Process (option set)', 'advanced-form-integration' ) ),
                array( 'key' => 'currentsituation',       'value' => __( 'Current Situation', 'advanced-form-integration' ) ),
                array( 'key' => 'customerneed',           'value' => __( 'Customer Need', 'advanced-form-integration' ) ),
                array( 'key' => 'proposedsolution',       'value' => __( 'Proposed Solution', 'advanced-form-integration' ) ),
                array( 'key' => 'ownerid',                'value' => __( 'Owner ID (systemuser GUID)', 'advanced-form-integration' ) ),
            ) );
        }

        if ( 'create_quote' === $task ) {
            return array_merge( $customer, array(
                array( 'key' => 'name',                   'value' => __( 'Quote Name', 'advanced-form-integration' ), 'required' => true ),
                array( 'key' => 'description',            'value' => __( 'Description', 'advanced-form-integration' ) ),
                array( 'key' => 'effectivefrom',          'value' => __( 'Effective From (YYYY-MM-DD)', 'advanced-form-integration' ) ),
                array( 'key' => 'effectiveto',            'value' => __( 'Effective To (YYYY-MM-DD)', 'advanced-form-integration' ) ),
                array( 'key' => 'requestdeliveryby',      'value' => __( 'Requested Delivery By (YYYY-MM-DD)', 'advanced-form-integration' ) ),
                array( 'key' => 'totalamount',            'value' => __( 'Total Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'discountamount',         'value' => __( 'Discount Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'ownerid',                'value' => __( 'Owner ID (systemuser GUID)', 'advanced-form-integration' ) ),
            ) );
        }

        if ( 'create_salesorder' === $task ) {
            return array_merge( $customer, array(
                array( 'key' => 'name',                   'value' => __( 'Order Name', 'advanced-form-integration' ), 'required' => true ),
                array( 'key' => 'description',            'value' => __( 'Description', 'advanced-form-integration' ) ),
                array( 'key' => 'requestdeliveryby',      'value' => __( 'Requested Delivery By (YYYY-MM-DD)', 'advanced-form-integration' ) ),
                array( 'key' => 'totalamount',            'value' => __( 'Total Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'discountamount',         'value' => __( 'Discount Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'pricelevelid',           'value' => __( 'Price List ID (pricelevel GUID)', 'advanced-form-integration' ) ),
                array( 'key' => 'ownerid',                'value' => __( 'Owner ID (systemuser GUID)', 'advanced-form-integration' ) ),
            ) );
        }

        if ( 'create_invoice' === $task ) {
            return array_merge( $customer, array(
                array( 'key' => 'name',                   'value' => __( 'Invoice Name', 'advanced-form-integration' ), 'required' => true ),
                array( 'key' => 'description',            'value' => __( 'Description', 'advanced-form-integration' ) ),
                array( 'key' => 'duedate',                'value' => __( 'Due Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
                array( 'key' => 'totalamount',            'value' => __( 'Total Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'discountamount',         'value' => __( 'Discount Amount', 'advanced-form-integration' ) ),
                array( 'key' => 'pricelevelid',           'value' => __( 'Price List ID (pricelevel GUID)', 'advanced-form-integration' ) ),
                array( 'key' => 'ownerid',                'value' => __( 'Owner ID (systemuser GUID)', 'advanced-form-integration' ) ),
            ) );
        }

        return array();
    }

    /**
     * Resolve the polymorphic customerid for a deal-style record. Prefers
     * an existing Account by name; falls back to upsert-by-email Contact.
     * Returns ['type' => 'accounts'|'contacts', 'id' => GUID] or [].
     */
    public function resolve_customer( $d, $values, $record ) {
        $account_name  = $values['account_name']  ?? '';
        $contact_email = $values['contact_email'] ?? '';

        if ( $account_name ) {
            $account = $d->find_account_by_name( $account_name, $record );
            if ( ! empty( $account['accountid'] ) ) {
                return array( 'type' => 'accounts', 'id' => $account['accountid'] );
            }
        }

        if ( $contact_email ) {
            $existing = $d->find_record_by_email( 'contacts', $contact_email, $record );
            if ( ! empty( $existing['contactid'] ) ) {
                return array( 'type' => 'contacts', 'id' => $existing['contactid'] );
            }

            // Create a minimal contact so the deal has something to bind to.
            $first = $values['contact_first'] ?? '';
            $last  = $values['contact_last']  ?? '';
            if ( ! $last ) {
                // Dynamics requires Last_Name; use the email's local part.
                $local = strstr( $contact_email, '@', true );
                $last  = $local ?: $contact_email;
            }
            $payload  = $d->finalize_payload( 'contact', array(
                'firstname'     => $first,
                'lastname'      => $last,
                'emailaddress1' => $contact_email,
            ) );
            $response = $d->dynamics365_request( 'contacts', 'POST', $payload, $record );

            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                $id   = $body['contactid'] ?? '';
                if ( ! $id ) {
                    $hdr = wp_remote_retrieve_header( $response, 'OData-EntityId' );
                    if ( $hdr && preg_match( '/contacts\(([0-9a-fA-F-]+)\)/', $hdr, $m ) ) {
                        $id = $m[1];
                    }
                }
                if ( $id ) {
                    return array( 'type' => 'contacts', 'id' => $id );
                }
            }
        }

        return array();
    }
}

ADFOIN_Dynamics365Sales::get_instance();

/* ---------- Dispatch ---------- */

function adfoin_dynamics365sales_job_queue( $data ) {
    if ( ! isset( $data['record'], $data['posted_data'] ) ) {
        return;
    }
    adfoin_dynamics365sales_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_dynamics365sales_job_queue', 'adfoin_dynamics365sales_job_queue', 10, 1 );
add_action( 'adfoin_job_queue', 'adfoin_dynamics365sales_job_queue', 10, 1 );

function adfoin_dynamics365sales_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id    = $field_data['credId'] ?? ( $record['cred_id'] ?? '' );
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || ! $task ) {
        return;
    }
    if ( ! class_exists( 'ADFOIN_Dynamics365' ) ) {
        return;
    }

    $d = ADFOIN_Dynamics365::get_instance();
    $d->set_credentials( $cred_id );
    if ( empty( $d->access_token ) ) {
        $d->request_token();
    }
    if ( empty( $d->access_token ) ) {
        return;
    }

    $values = array();
    $skip   = array( 'credId', 'cl' );
    foreach ( $field_data as $key => $value ) {
        if ( in_array( $key, $skip, true ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) {
            $values[ $key ] = $parsed;
        }
    }

    $sales    = ADFOIN_Dynamics365Sales::get_instance();
    $customer = $sales->resolve_customer( $d, $values, $record );

    // Strip the customer-resolution keys from the deal payload — they don't
    // belong on Opportunity/Quote/SalesOrder/Invoice records.
    foreach ( array( 'account_name', 'contact_email', 'contact_first', 'contact_last' ) as $k ) {
        unset( $values[ $k ] );
    }

    $entity_set = '';
    switch ( $task ) {
        case 'create_opportunity': $entity_set = 'opportunities'; break;
        case 'create_quote':       $entity_set = 'quotes';        break;
        case 'create_salesorder':  $entity_set = 'salesorders';   break;
        case 'create_invoice':     $entity_set = 'invoices';      break;
    }
    if ( ! $entity_set ) {
        return;
    }

    // Generic numeric / lookup coercion. Use the contact-entity rules — they
    // cover the common cases (ownerid → @odata.bind, numeric ints).
    $payload = $d->finalize_payload( 'contact', $values );

    // Float fields specific to deal records.
    foreach ( array( 'estimatedvalue', 'budgetamount', 'totalamount', 'discountamount' ) as $float_key ) {
        if ( isset( $payload[ $float_key ] ) ) {
            $payload[ $float_key ] = (float) $payload[ $float_key ];
        }
    }
    if ( isset( $payload['closeprobability'] ) ) {
        $payload['closeprobability'] = (int) $payload['closeprobability'];
    }

    // Bind customer (polymorphic). Quote/SalesOrder/Invoice use the same
    // customerid lookup as Opportunity.
    if ( ! empty( $customer['id'] ) ) {
        $payload['customerid_' . rtrim( $customer['type'], 's' ) . '@odata.bind'] =
            '/' . $customer['type'] . '(' . $customer['id'] . ')';
    }

    // Optional pricelevel binding (Sales Order / Invoice).
    if ( ! empty( $payload['pricelevelid'] ) ) {
        $payload['pricelevelid@odata.bind'] = '/pricelevels(' . trim( $payload['pricelevelid'], '{}' ) . ')';
        unset( $payload['pricelevelid'] );
    }

    if ( empty( $payload['name'] ) ) {
        return; // Topic / Name is required on all four deal entities.
    }

    $d->dynamics365_request( $entity_set, 'POST', $payload, $record );
}
