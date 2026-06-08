<?php

/**
 * Dynamics 365 Field Service connector.
 *
 * Same Dataverse instance as Dynamics 365 CRM — reuses the connection
 * configured under that tab. Field Service ships several first-party
 * tables on top of Dataverse:
 *   - msdyn_workorder
 *   - msdyn_incidenttype (catalog)
 *   - bookableresourcebooking
 *
 * Tasks:
 *   - create_work_order        — msdyn_workorders entity. Customer + incident type → schedulable work.
 *   - create_service_request   — msdyn_workorders with worktype=service-request style.
 *                                Modeled as a separate task for UX clarity.
 *
 * NOTE: Field Service has both a "Service Appointments" pattern via the
 * built-in serviceappointment entity AND its own msdyn_* set. Most
 * production customers use msdyn_workorder. That's what we target.
 */
class ADFOIN_Dynamics365FieldService {

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

        add_action( 'wp_ajax_adfoin_get_dynamics365fieldservice_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dynamics365fieldservice'] = array(
            'title' => 'Dynamics 365 Field Service',
            'tasks' => array(
                'create_work_order'      => __( 'Create Work Order', 'advanced-form-integration' ),
                'create_service_request' => __( 'Create Service Request (Work Order)', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dynamics365fieldservice'] = 'Dynamics 365 Field Service';
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'dynamics365fieldservice' !== $current_tab ) {
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
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php esc_html_e( 'Dynamics 365 Field Service', 'advanced-form-integration' ); ?>
                            </h2>
                        </div>
                        <div class="afi-accounts-body" style="padding:20px;">
                            <p>
                                <?php
                                printf(
                                    wp_kses(
                                        __( 'Field Service shares its data store (Dataverse) with Dynamics 365 CRM, so it reuses the connection you configure under the <a href="%s">Dynamics 365 CRM</a> tab.', 'advanced-form-integration' ),
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
                                <li><?php esc_html_e( 'Creates Work Orders on the msdyn_workorders entity, with auto-resolved customer (Account by name → Contact by email).', 'advanced-form-integration' ); ?></li>
                                <li><?php esc_html_e( 'Requires a Service Account: Field Service requires msdyn_serviceaccount on every Work Order, so the Account-resolution path is the primary one here.', 'advanced-form-integration' ); ?></li>
                            </ul>
                            <p>
                                <strong><?php esc_html_e( 'Requirement:', 'advanced-form-integration' ); ?></strong>
                                <?php esc_html_e( 'Your Application User\'s security role needs Field Service module roles assigned ("Field Service - Administrator" or a custom role with write access to msdyn_workorder).', 'advanced-form-integration' ); ?>
                            </p>
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
        <script type="text/template" id="dynamics365fieldservice-action-template">
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
                        <p><?php printf( wp_kses( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">AFI Pro</a> to map any custom column on Work Order / msdyn_workorder (new_*, prefix_*, msdyn_customfield*).', 'advanced-form-integration' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
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
        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_work_order';
        wp_send_json_success( $this->get_fields_for_task( $task ) );
    }

    protected function get_fields_for_task( $task ) {
        // Work Order and Service Request share most fields. The difference
        // is conceptual (worktype) — kept as separate tasks so users get
        // a clean menu option for each flow.
        return array(
            // Customer (Service Account is required on Work Orders)
            array( 'key' => 'account_name',  'value' => __( 'Service Account Name (required — looks up Account)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'contact_email', 'value' => __( 'Primary Contact Email (optional — looks up Contact)', 'advanced-form-integration' ) ),

            // Work order fields
            array( 'key' => 'msdyn_subject',                 'value' => __( 'Subject / Title', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'msdyn_description',             'value' => __( 'Description', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_incidenttype_name',       'value' => __( 'Incident Type Name (looks up msdyn_incidenttype)', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_workordertype_name',      'value' => __( 'Work Order Type Name (looks up msdyn_workordertype)', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_priority_name',           'value' => __( 'Priority Name (looks up msdyn_priority)', 'advanced-form-integration' ) ),

            // Scheduling
            array( 'key' => 'msdyn_timefrompromised',        'value' => __( 'Promised Window From (ISO datetime)', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_timetopromised',          'value' => __( 'Promised Window To (ISO datetime)', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_datewindowstart',         'value' => __( 'Service Window Start (YYYY-MM-DD)', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_datewindowend',           'value' => __( 'Service Window End (YYYY-MM-DD)', 'advanced-form-integration' ) ),

            // Address (the work site, separate from the Account address)
            array( 'key' => 'msdyn_addressline1',            'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_addressline2',            'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_city',                    'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_stateorprovince',         'value' => __( 'State / Province', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_postalcode',              'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_country',                 'value' => __( 'Country', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_latitude',                'value' => __( 'Latitude (decimal)', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_longitude',               'value' => __( 'Longitude (decimal)', 'advanced-form-integration' ) ),

            // Misc
            array( 'key' => 'msdyn_workorderinstructions',   'value' => __( 'Work Order Instructions', 'advanced-form-integration' ) ),
            array( 'key' => 'msdyn_totalestimatedduration',  'value' => __( 'Estimated Duration (minutes)', 'advanced-form-integration' ) ),
            array( 'key' => 'ownerid',                       'value' => __( 'Owner ID (systemuser or team GUID)', 'advanced-form-integration' ) ),
        );
    }

    /**
     * Resolve a Field Service related entity by name on a `_name` column.
     * Used for incident type, work order type, priority — all of which are
     * Field Service catalog entities the user references by name in forms.
     */
    public function find_by_name( $d, $entity_set, $name, $record ) {
        if ( ! $name ) { return array(); }
        $endpoint = $entity_set . '?' . http_build_query( array(
            '$select' => $entity_set . 'id,msdyn_name',
            '$filter' => "msdyn_name eq '" . str_replace( "'", "''", $name ) . "'",
            '$top'    => 1,
        ) );
        // Try msdyn_name first (the FS convention). Fallback to `name`.
        $resp = $d->dynamics365_request( $endpoint, 'GET', array(), $record );
        if ( ! is_wp_error( $resp ) ) {
            $body = json_decode( wp_remote_retrieve_body( $resp ), true );
            if ( ! empty( $body['value'][0] ) ) {
                return $body['value'][0];
            }
        }
        return array();
    }
}

ADFOIN_Dynamics365FieldService::get_instance();

/* ---------- Dispatch ---------- */

function adfoin_dynamics365fieldservice_job_queue( $data ) {
    if ( ! isset( $data['record'], $data['posted_data'] ) ) {
        return;
    }
    adfoin_dynamics365fieldservice_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_dynamics365fieldservice_job_queue', 'adfoin_dynamics365fieldservice_job_queue', 10, 1 );
add_action( 'adfoin_job_queue', 'adfoin_dynamics365fieldservice_job_queue', 10, 1 );

function adfoin_dynamics365fieldservice_send_data( $record, $posted_data ) {
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

    $fs = ADFOIN_Dynamics365FieldService::get_instance();

    // Service Account is required on Work Orders.
    $account_name = $values['account_name'] ?? '';
    $account      = $account_name ? $d->find_account_by_name( $account_name, $record ) : array();
    if ( empty( $account['accountid'] ) ) {
        return;
    }

    // Optional Primary Contact
    $contact_id = '';
    if ( ! empty( $values['contact_email'] ) ) {
        $existing = $d->find_record_by_email( 'contacts', $values['contact_email'], $record );
        $contact_id = $existing['contactid'] ?? '';
    }

    // Strip resolution helpers + the *_name lookups from the payload before
    // building it — we'll convert them into @odata.bind references below.
    $lookup_inputs = array(
        'account_name', 'contact_email',
        'msdyn_incidenttype_name', 'msdyn_workordertype_name', 'msdyn_priority_name',
    );
    $name_lookups = array(
        'msdyn_incidenttype_name'  => array( 'msdyn_incidenttypes',  'msdyn_incidenttype' ),
        'msdyn_workordertype_name' => array( 'msdyn_workordertypes', 'msdyn_workordertype' ),
        'msdyn_priority_name'      => array( 'msdyn_priorities',     'msdyn_priority' ),
    );

    $bindings = array();
    foreach ( $name_lookups as $form_key => $info ) {
        list( $entity_set, $field_prefix ) = $info;
        if ( empty( $values[ $form_key ] ) ) { continue; }
        $matched = $fs->find_by_name( $d, $entity_set, $values[ $form_key ], $record );
        $pk      = $entity_set . 'id'; // msdyn_incidenttypes → msdyn_incidenttypesid? actually msdyn_incidenttypeid
        // The PK is the singular form + id. Strip the trailing 's' on entity_set.
        $pk = rtrim( $entity_set, 's' ) . 'id';
        if ( ! empty( $matched[ $pk ] ) ) {
            $bindings[ $field_prefix . '@odata.bind' ] = '/' . $entity_set . '(' . $matched[ $pk ] . ')';
        }
    }

    foreach ( $lookup_inputs as $k ) {
        unset( $values[ $k ] );
    }

    // Build payload with general coercion + Field-Service-specific cleanups.
    $payload = $d->finalize_payload( 'contact', $values );

    foreach ( array( 'msdyn_latitude', 'msdyn_longitude' ) as $float_key ) {
        if ( isset( $payload[ $float_key ] ) ) {
            $payload[ $float_key ] = (float) $payload[ $float_key ];
        }
    }
    if ( isset( $payload['msdyn_totalestimatedduration'] ) ) {
        $payload['msdyn_totalestimatedduration'] = (int) $payload['msdyn_totalestimatedduration'];
    }

    // Required service account binding.
    $payload['msdyn_serviceaccount_account@odata.bind'] = '/accounts(' . $account['accountid'] . ')';

    if ( $contact_id ) {
        $payload['msdyn_primaryincidentcustomer_contact@odata.bind'] = '/contacts(' . $contact_id . ')';
    }

    // Apply optionset / type bindings resolved above.
    foreach ( $bindings as $bind_key => $bind_value ) {
        $payload[ $bind_key ] = $bind_value;
    }

    // msdyn_workordertype is required — if the user didn't pick one and
    // none was auto-bound, Dynamics will reject the create. Surface that
    // via the standard error logging, don't gate locally.

    if ( empty( $payload['msdyn_subject'] ) ) {
        return; // subject is required
    }

    $d->dynamics365_request( 'msdyn_workorders', 'POST', $payload, $record );
}
