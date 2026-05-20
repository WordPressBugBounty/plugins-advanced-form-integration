<?php

/**
 * Dataverse Generic connector.
 *
 * Power-user platform: posts a record to any Dataverse table — built-in
 * (contacts, leads, opportunities, …) or custom (publisher_mytable). The
 * user picks an entity set from a dropdown that's auto-populated from the
 * connected environment's metadata, then maps fields by logical name.
 *
 * Reuses the Dynamics 365 CRM connection. No separate auth/credentials.
 */
class ADFOIN_Dataverse {

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

        add_action( 'wp_ajax_adfoin_get_dataverse_entities', array( $this, 'ajax_get_entities' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dataverse'] = array(
            'title' => 'Dataverse (Generic)',
            'tasks' => array(
                'create_record' => __( 'Create Record in any table', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dataverse'] = 'Dataverse (Generic)';
        return $providers;
    }

    public function settings_view( $current_tab ) {
        if ( 'dataverse' !== $current_tab ) {
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
                                <span class="dashicons dashicons-database"></span>
                                <?php esc_html_e( 'Dataverse (Generic)', 'advanced-form-integration' ); ?>
                            </h2>
                        </div>
                        <div class="afi-accounts-body" style="padding:20px;">
                            <p>
                                <?php
                                printf(
                                    wp_kses(
                                        __( 'Dataverse uses the same connection as <a href="%s">Dynamics 365 CRM</a> — there\'s nothing to configure here.', 'advanced-form-integration' ),
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
                                <li><?php esc_html_e( 'Posts a record to any Dataverse table — built-in (contacts, leads, accounts, …) or custom.', 'advanced-form-integration' ); ?></li>
                                <li><?php esc_html_e( 'Use this when no first-party action covers your scenario — e.g., posting to a custom table, a less-common entity like queueitem or opportunityproduct, or a Power Platform solution-defined table.', 'advanced-form-integration' ); ?></li>
                                <li><?php esc_html_e( 'Field mappings use Dataverse logical names (e.g., new_segment, msdyn_*). Lookups in the form: prefix the key with "ref:" and pass the GUID as the value.', 'advanced-form-integration' ); ?></li>
                            </ul>
                            <p>
                                <strong><?php esc_html_e( 'Security note:', 'advanced-form-integration' ); ?></strong>
                                <?php esc_html_e( 'Whatever security role your Application User has determines which tables this platform can write to. Verify the role in Power Platform Admin Center before relying on a specific table.', 'advanced-form-integration' ); ?>
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
        <script type="text/template" id="dataverse-action-template">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_attr_e( 'Dynamics 365 Account', 'advanced-form-integration' ); ?></th>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="onAccountChange">
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
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Target Table', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" name="fieldData[entitySet]" v-model="fielddata.entitySet"
                               list="adfoin-dataverse-entities" class="regular-text"
                               placeholder="<?php esc_attr_e( 'e.g., contacts, leads, new_custompublisher_records', 'advanced-form-integration' ); ?>">
                        <datalist id="adfoin-dataverse-entities">
                            <option v-for="(label, name) in entities" :value="name">{{ label }}</option>
                        </datalist>
                        <div class="spinner" v-bind:class="{'is-active': entitiesLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                        <p class="description"><?php esc_html_e( 'Entity-set (plural) name. Suggestions are loaded from the connected environment. Type a custom name if your table isn\'t suggested.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>

                <tr class="alternate">
                    <th scope="row"><?php esc_html_e( 'Field Mappings', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e( 'Map each Dataverse column by its logical (schema) name. Use trigger tags like {first_name} in the value.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
                <tr class="alternate" v-for="(row, idx) in fielddata.rows" :key="'r-' + idx">
                    <td>
                        <input type="text" :name="'fieldData[rows][' + idx + '][key]'"
                               v-model="row.key"
                               placeholder="<?php esc_attr_e( 'Logical name (e.g. firstname, emailaddress1)', 'advanced-form-integration' ); ?>"
                               class="regular-text">
                    </td>
                    <td>
                        <input type="text" :name="'fieldData[rows][' + idx + '][value]'"
                               v-model="row.value"
                               placeholder="<?php esc_attr_e( 'Trigger tag or static value', 'advanced-form-integration' ); ?>"
                               class="regular-text">
                        <button type="button" class="button" @click="removeRow(idx)" style="margin-left:8px;">
                            <span class="dashicons dashicons-trash" style="margin-top:3px;"></span>
                        </button>
                    </td>
                </tr>
                <tr class="alternate">
                    <td></td>
                    <td>
                        <button type="button" class="button" @click="addRow">
                            <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
                            <?php esc_html_e( 'Add Field', 'advanced-form-integration' ); ?>
                        </button>
                    </td>
                </tr>

                <tr class="alternate">
                    <th scope="row"><?php esc_html_e( 'Lookup Bindings (optional)', 'advanced-form-integration' ); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e( 'For lookup columns: target entity-set + lookup field + GUID. Renders as @odata.bind.', 'advanced-form-integration' ); ?></p>
                    </td>
                </tr>
                <tr class="alternate" v-for="(row, idx) in fielddata.lookups" :key="'l-' + idx">
                    <td>
                        <input type="text" :name="'fieldData[lookups][' + idx + '][field]'"
                               v-model="row.field"
                               placeholder="<?php esc_attr_e( 'Lookup field name (e.g. parentcustomerid_account)', 'advanced-form-integration' ); ?>"
                               class="regular-text">
                        <br>
                        <input type="text" :name="'fieldData[lookups][' + idx + '][entitySet]'"
                               v-model="row.entitySet"
                               placeholder="<?php esc_attr_e( 'Target entity set (e.g. accounts)', 'advanced-form-integration' ); ?>"
                               class="regular-text" style="margin-top:5px;">
                    </td>
                    <td>
                        <input type="text" :name="'fieldData[lookups][' + idx + '][value]'"
                               v-model="row.value"
                               placeholder="<?php esc_attr_e( 'GUID or trigger tag', 'advanced-form-integration' ); ?>"
                               class="regular-text">
                        <button type="button" class="button" @click="removeLookup(idx)" style="margin-left:8px;">
                            <span class="dashicons dashicons-trash" style="margin-top:3px;"></span>
                        </button>
                    </td>
                </tr>
                <tr class="alternate">
                    <td></td>
                    <td>
                        <button type="button" class="button" @click="addLookup">
                            <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
                            <?php esc_html_e( 'Add Lookup Binding', 'advanced-form-integration' ); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </script>
        <?php
    }

    /**
     * Fetch the connected environment's entity sets via EntityDefinitions.
     * Cached on the credential record for 6 hours — metadata is large and
     * doesn't change often.
     */
    public function ajax_get_entities() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }
        $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
        if ( ! $cred_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing credential id', 'advanced-form-integration' ) ) );
        }
        if ( ! class_exists( 'ADFOIN_Dynamics365' ) ) {
            wp_send_json_error( array( 'message' => __( 'Dynamics 365 connection module is not loaded.', 'advanced-form-integration' ) ) );
        }

        $cache_key = 'adfoin_dataverse_entities_' . md5( $cred_id );
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            wp_send_json_success( $cached );
        }

        $d = ADFOIN_Dynamics365::get_instance();
        $d->set_credentials( $cred_id );

        $endpoint = 'EntityDefinitions?' . http_build_query( array(
            '$select' => 'LogicalName,EntitySetName,DisplayName',
            '$filter' => 'IsValidForCreate eq true',
        ) );

        $response = $d->dynamics365_request( $endpoint, 'GET' );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $msg  = is_array( $body ) && isset( $body['error']['message'] ) ? $body['error']['message'] : sprintf( 'HTTP %d', $code );
            wp_send_json_error( array( 'message' => $msg ) );
        }

        $body     = json_decode( wp_remote_retrieve_body( $response ), true );
        $entities = array();
        if ( ! empty( $body['value'] ) && is_array( $body['value'] ) ) {
            foreach ( $body['value'] as $e ) {
                $set = $e['EntitySetName'] ?? '';
                if ( ! $set ) { continue; }
                $label = $e['DisplayName']['UserLocalizedLabel']['Label'] ?? '';
                if ( ! $label ) {
                    $label = $e['LogicalName'] ?? $set;
                }
                $entities[ $set ] = $label . ' (' . $set . ')';
            }
            // Sort by entity-set name for a stable list.
            ksort( $entities );
        }

        set_transient( $cache_key, $entities, 6 * HOUR_IN_SECONDS );
        wp_send_json_success( $entities );
    }
}

ADFOIN_Dataverse::get_instance();

/* ---------- Dispatch ---------- */

function adfoin_dataverse_job_queue( $data ) {
    if ( ! isset( $data['record'], $data['posted_data'] ) ) {
        return;
    }
    adfoin_dataverse_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_dataverse_job_queue', 'adfoin_dataverse_job_queue', 10, 1 );
add_action( 'adfoin_job_queue', 'adfoin_dataverse_job_queue', 10, 1 );

function adfoin_dataverse_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id    = $field_data['credId']    ?? ( $record['cred_id'] ?? '' );
    $entity_set = $field_data['entitySet'] ?? '';
    $rows       = isset( $field_data['rows'] )    && is_array( $field_data['rows'] )    ? $field_data['rows']    : array();
    $lookups    = isset( $field_data['lookups'] ) && is_array( $field_data['lookups'] ) ? $field_data['lookups'] : array();
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || 'create_record' !== $task ) { return; }
    if ( ! $entity_set ) { return; }
    if ( ! class_exists( 'ADFOIN_Dynamics365' ) ) { return; }

    // Sanitize entity-set name — allow alphanumeric + underscore only. Stops
    // OData injection (..) and forces a clean table name.
    $entity_set = preg_replace( '/[^a-z0-9_]/i', '', $entity_set );
    if ( ! $entity_set ) { return; }

    $d = ADFOIN_Dynamics365::get_instance();
    $d->set_credentials( $cred_id );
    if ( empty( $d->access_token ) ) { $d->request_token(); }
    if ( empty( $d->access_token ) ) { return; }

    // Build payload from row-style mappings.
    $payload = array();
    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) { continue; }
        $key = isset( $row['key'] ) ? sanitize_key( wp_unslash( $row['key'] ) ) : '';
        if ( '' === $key ) { continue; }
        $raw = isset( $row['value'] ) ? wp_unslash( $row['value'] ) : '';
        $val = adfoin_get_parsed_values( $raw, $posted_data );
        if ( $val === '' || $val === null ) { continue; }

        // Type-coerce common patterns. We don't have entity metadata
        // here, so this is best-effort: digits-only stays string,
        // 'true'/'false' becomes bool, ISO-ish dates stay as strings
        // (Dynamics accepts them via OData).
        $lower = strtolower( (string) $val );
        if ( $lower === 'true' )       { $val = true; }
        elseif ( $lower === 'false' )  { $val = false; }

        $payload[ $key ] = $val;
    }

    // Lookup bindings — user supplies field name, target entity set, and
    // the GUID. We render @odata.bind for each.
    foreach ( $lookups as $row ) {
        if ( ! is_array( $row ) ) { continue; }
        $field  = isset( $row['field'] )     ? sanitize_key( wp_unslash( $row['field'] ) ) : '';
        $target = isset( $row['entitySet'] ) ? preg_replace( '/[^a-z0-9_]/i', '', wp_unslash( $row['entitySet'] ) ) : '';
        if ( ! $field || ! $target ) { continue; }
        $raw  = isset( $row['value'] ) ? wp_unslash( $row['value'] ) : '';
        $guid = adfoin_get_parsed_values( $raw, $posted_data );
        $guid = trim( $guid, "{} \t\n\r\0\x0B" );
        if ( ! $guid ) { continue; }
        $payload[ $field . '@odata.bind' ] = '/' . $target . '(' . $guid . ')';
    }

    if ( empty( $payload ) ) { return; }

    $d->dynamics365_request( $entity_set, 'POST', $payload, $record );
}
