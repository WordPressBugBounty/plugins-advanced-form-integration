<?php

/**
 * Dynamics 365 Customer Service connector.
 *
 * Same Dataverse instance as Dynamics 365 CRM — reuses the connection
 * configured under that tab.
 *
 * Tasks:
 *   - create_case  — `incidents` entity. "Form → support ticket" is the
 *                    canonical Customer Service flow.
 *   - add_note     — `annotations` entity attached to an existing case
 *                    via objectid_incident@odata.bind.
 */
class ADFOIN_Dynamics365CustomerService {

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

        add_action( 'wp_ajax_adfoin_get_dynamics365customerservice_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dynamics365customerservice'] = array(
            'title' => 'Dynamics 365 Customer Service',
            'tasks' => array(
                'create_case' => __( 'Create Case (incident)', 'advanced-form-integration' ),
                'add_note'    => __( 'Add Note to existing Case', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dynamics365customerservice'] = 'Dynamics 365 Customer Service';
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'dynamics365customerservice' !== $current_tab ) {
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
                                <span class="dashicons dashicons-sos"></span>
                                <?php esc_html_e( 'Dynamics 365 Customer Service', 'advanced-form-integration' ); ?>
                            </h2>
                        </div>
                        <div class="afi-accounts-body" style="padding:20px;">
                            <p>
                                <?php
                                printf(
                                    wp_kses(
                                        __( 'Customer Service shares its data store (Dataverse) with Dynamics 365 CRM, so it reuses the connection you configure under the <a href="%s">Dynamics 365 CRM</a> tab.', 'advanced-form-integration' ),
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
                                <li><?php esc_html_e( 'Creates a Case (incident) and auto-links it to an existing Contact by email — or an Account by name if no contact matches.', 'advanced-form-integration' ); ?></li>
                                <li><?php esc_html_e( 'Optionally adds a Note (annotation) to a specific Case GUID, useful for follow-up forms.', 'advanced-form-integration' ); ?></li>
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
        <script type="text/template" id="dynamics365customerservice-action-template">
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
                        <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
                        <p><?php printf( wp_kses( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">AFI Pro</a> to map any custom column on Case (incident) or Annotation (new_*, prefix_*, etc.).', 'advanced-form-integration' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
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
        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_case';
        wp_send_json_success( $this->get_fields_for_task( $task ) );
    }

    protected function get_fields_for_task( $task ) {
        if ( 'add_note' === $task ) {
            return array(
                array( 'key' => 'case_id',    'value' => __( 'Case GUID (incidentid)', 'advanced-form-integration' ), 'required' => true ),
                array( 'key' => 'subject',    'value' => __( 'Note Subject', 'advanced-form-integration' ), 'required' => true ),
                array( 'key' => 'notetext',   'value' => __( 'Note Text', 'advanced-form-integration' ) ),
            );
        }

        // create_case
        return array(
            // Customer resolution
            array( 'key' => 'contact_email', 'value' => __( 'Contact Email (links to Contact by email)', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_first', 'value' => __( 'Contact First Name (used if contact must be created)', 'advanced-form-integration' ) ),
            array( 'key' => 'contact_last',  'value' => __( 'Contact Last Name (used if contact must be created)', 'advanced-form-integration' ) ),
            array( 'key' => 'account_name',  'value' => __( 'Account Name (fallback customer)', 'advanced-form-integration' ) ),

            // Case fields
            array( 'key' => 'title',                'value' => __( 'Case Title (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'description',          'value' => __( 'Description', 'advanced-form-integration' ) ),
            array( 'key' => 'casetypecode',         'value' => __( 'Case Type (1=Question, 2=Problem, 3=Request)', 'advanced-form-integration' ) ),
            array( 'key' => 'caseorigincode',       'value' => __( 'Origin (1=Phone, 2=Email, 3=Web, 2483=Facebook, 3986=Twitter)', 'advanced-form-integration' ) ),
            array( 'key' => 'prioritycode',         'value' => __( 'Priority (1=High, 2=Normal, 3=Low)', 'advanced-form-integration' ) ),
            array( 'key' => 'severitycode',         'value' => __( 'Severity (option set)', 'advanced-form-integration' ) ),
            array( 'key' => 'productserialnumber',  'value' => __( 'Product Serial Number', 'advanced-form-integration' ) ),
            array( 'key' => 'customercontacted',    'value' => __( 'Customer Contacted (true/false)', 'advanced-form-integration' ) ),
            array( 'key' => 'firstresponsesent',    'value' => __( 'First Response Sent (true/false)', 'advanced-form-integration' ) ),
            array( 'key' => 'ownerid',              'value' => __( 'Owner ID (systemuser or team GUID)', 'advanced-form-integration' ) ),
        );
    }
}

ADFOIN_Dynamics365CustomerService::get_instance();

/* ---------- Dispatch ---------- */

function adfoin_dynamics365customerservice_job_queue( $data ) {
    if ( ! isset( $data['record'], $data['posted_data'] ) ) {
        return;
    }
    adfoin_dynamics365customerservice_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_dynamics365customerservice_job_queue', 'adfoin_dynamics365customerservice_job_queue', 10, 1 );
add_action( 'adfoin_job_queue', 'adfoin_dynamics365customerservice_job_queue', 10, 1 );

function adfoin_dynamics365customerservice_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id    = $field_data['credId'] ?? ( $record['cred_id'] ?? '' );
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || ! $task ) { return; }
    if ( ! class_exists( 'ADFOIN_Dynamics365' ) ) { return; }

    $d = ADFOIN_Dynamics365::get_instance();
    $d->set_credentials( $cred_id );
    if ( empty( $d->access_token ) ) { $d->request_token(); }
    if ( empty( $d->access_token ) ) { return; }

    $values = array();
    foreach ( $field_data as $key => $value ) {
        if ( in_array( $key, array( 'credId', 'cl' ), true ) ) { continue; }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) {
            $values[ $key ] = $parsed;
        }
    }

    if ( 'add_note' === $task ) {
        adfoin_dynamics365customerservice_handle_add_note( $d, $values, $record );
        return;
    }

    if ( 'create_case' === $task ) {
        adfoin_dynamics365customerservice_handle_create_case( $d, $values, $record );
    }
}

function adfoin_dynamics365customerservice_handle_create_case( $d, $values, $record ) {
    // Customer resolution: Contact by email (preferred) → Account by name.
    // Cases have a polymorphic customerid like Opportunity.
    $customer = array();
    if ( ! empty( $values['contact_email'] ) ) {
        $existing = $d->find_record_by_email( 'contacts', $values['contact_email'], $record );
        if ( ! empty( $existing['contactid'] ) ) {
            $customer = array( 'type' => 'contacts', 'id' => $existing['contactid'] );
        } else {
            // Auto-create a minimal contact so the case has somewhere to land.
            $last  = $values['contact_last'] ?: ( strstr( $values['contact_email'], '@', true ) ?: $values['contact_email'] );
            $first = $values['contact_first'] ?? '';
            $payload = $d->finalize_payload( 'contact', array(
                'firstname'     => $first,
                'lastname'      => $last,
                'emailaddress1' => $values['contact_email'],
            ) );
            $resp = $d->dynamics365_request( 'contacts', 'POST', $payload, $record );
            if ( ! is_wp_error( $resp ) ) {
                $body = json_decode( wp_remote_retrieve_body( $resp ), true );
                $id   = $body['contactid'] ?? '';
                if ( ! $id ) {
                    $hdr = wp_remote_retrieve_header( $resp, 'OData-EntityId' );
                    if ( $hdr && preg_match( '/contacts\(([0-9a-fA-F-]+)\)/', $hdr, $m ) ) {
                        $id = $m[1];
                    }
                }
                if ( $id ) { $customer = array( 'type' => 'contacts', 'id' => $id ); }
            }
        }
    }
    if ( empty( $customer ) && ! empty( $values['account_name'] ) ) {
        $account = $d->find_account_by_name( $values['account_name'], $record );
        if ( ! empty( $account['accountid'] ) ) {
            $customer = array( 'type' => 'accounts', 'id' => $account['accountid'] );
        }
    }

    // Strip customer-resolution fields before building the case payload.
    foreach ( array( 'contact_email', 'contact_first', 'contact_last', 'account_name' ) as $k ) {
        unset( $values[ $k ] );
    }

    // Booleans + option sets coerced via the contact-entity rules; explicit
    // post-process for case-specific keys.
    $payload = $d->finalize_payload( 'contact', $values );

    foreach ( array( 'casetypecode', 'caseorigincode', 'prioritycode', 'severitycode' ) as $int_key ) {
        if ( isset( $payload[ $int_key ] ) ) {
            $payload[ $int_key ] = (int) $payload[ $int_key ];
        }
    }
    foreach ( array( 'customercontacted', 'firstresponsesent' ) as $bool_key ) {
        if ( isset( $payload[ $bool_key ] ) ) {
            $v = strtolower( (string) $payload[ $bool_key ] );
            $payload[ $bool_key ] = in_array( $v, array( 'true', '1', 'yes', 'on' ), true );
        }
    }

    if ( empty( $payload['title'] ) ) {
        return; // Title is required on incident
    }

    if ( ! empty( $customer['id'] ) ) {
        // Polymorphic binding: customerid_account or customerid_contact.
        $payload['customerid_' . rtrim( $customer['type'], 's' ) . '@odata.bind'] =
            '/' . $customer['type'] . '(' . $customer['id'] . ')';
    } else {
        // customerid is required on incident — no point sending if unresolved.
        return;
    }

    $d->dynamics365_request( 'incidents', 'POST', $payload, $record );
}

function adfoin_dynamics365customerservice_handle_add_note( $d, $values, $record ) {
    $case_id = $values['case_id'] ?? '';
    $subject = $values['subject'] ?? '';
    if ( ! $case_id || ! $subject ) {
        return;
    }
    $payload = array(
        'subject'                  => $subject,
        'notetext'                 => $values['notetext'] ?? '',
        'objectid_incident@odata.bind' => '/incidents(' . trim( $case_id, '{}' ) . ')',
    );
    $d->dynamics365_request( 'annotations', 'POST', $payload, $record );
}
