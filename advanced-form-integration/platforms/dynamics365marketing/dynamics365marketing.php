<?php

/**
 * Dynamics 365 Marketing connector — Free tier.
 *
 * Marketing sits on top of the same Dataverse instance as Dynamics 365 CRM,
 * so this platform does NOT manage its own OAuth credentials. Instead it
 * reuses the connection configured under the "Dynamics 365 CRM" tab and
 * delegates all token + API work to the ADFOIN_Dynamics365 singleton.
 *
 * Free tier exposes a single upsert action:
 *   - create_marketing_contact: upsert a Contact (by email) with the
 *     marketing-relevant subset of fields (consent flags, lead source, etc.)
 *
 * Pro tier (separate file) adds:
 *   - extended field map
 *   - marketing-list membership (lists / msdyncrm_marketinglist)
 */
class ADFOIN_Dynamics365Marketing {

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

        add_action( 'wp_ajax_adfoin_get_dynamics365marketing_fields', array( $this, 'ajax_get_fields' ), 10, 0 );
        add_action( 'wp_ajax_adfoin_get_dynamics365marketing_lists', array( $this, 'ajax_get_lists' ), 10, 0 );
    }

    public function register_actions( $actions ) {
        $actions['dynamics365marketing'] = array(
            'title' => 'Dynamics 365 Marketing',
            'tasks' => array(
                'create_marketing_contact' => __( 'Create / Update Marketing Contact (by email)', 'advanced-form-integration' ),
            ),
        );
        return $actions;
    }

    public function register_settings_tab( $providers ) {
        $providers['dynamics365marketing'] = 'Dynamics 365 Marketing';
        return $providers;
    }

    /**
     * The settings tab is informational only — no credentials are stored
     * for this platform. Users connect once under "Dynamics 365 CRM" and
     * both platforms share the resulting token.
     */
    public function settings_view( $current_tab ) {
        if ( 'dynamics365marketing' !== $current_tab ) {
            return;
        }

        $crm_settings_url = admin_url( 'admin.php?page=advanced-form-integration-settings&tab=dynamics365' );

        ?>
        <div class="afi-container" id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="afi-accounts-card">
                        <div class="afi-accounts-header">
                            <h2 class="afi-accounts-title">
                                <span class="dashicons dashicons-megaphone"></span>
                                <?php esc_html_e( 'Dynamics 365 Marketing', 'advanced-form-integration' ); ?>
                            </h2>
                        </div>
                        <div class="afi-accounts-body" style="padding: 20px;">
                            <p>
                                <?php
                                printf(
                                    /* translators: %s: link to Dynamics 365 CRM settings tab */
                                    wp_kses(
                                        __( 'Dynamics 365 Marketing shares its data store (Dataverse) with Dynamics 365 CRM, so it reuses the connection you configure under the <a href="%s">Dynamics 365 CRM</a> tab. There is nothing to configure here.', 'advanced-form-integration' ),
                                        array( 'a' => array( 'href' => array() ) )
                                    ),
                                    esc_url( $crm_settings_url )
                                );
                                ?>
                            </p>
                            <p>
                                <a href="<?php echo esc_url( $crm_settings_url ); ?>" class="button button-primary">
                                    <?php esc_html_e( 'Open Dynamics 365 CRM settings', 'advanced-form-integration' ); ?>
                                </a>
                            </p>
                            <hr>
                            <h3><?php esc_html_e( 'What this platform does', 'advanced-form-integration' ); ?></h3>
                            <ul style="list-style: disc; padding-left: 20px;">
                                <li><?php esc_html_e( 'Upserts a Contact in Dynamics by email: updates the existing record if one matches, otherwise creates a new one.', 'advanced-form-integration' ); ?></li>
                                <li><?php esc_html_e( 'Sets marketing-specific fields like consent flags (Do not email / bulk email / phone), lead source, and campaign attribution.', 'advanced-form-integration' ); ?></li>
                            </ul>
                            <h3 style="margin-top: 20px;"><?php esc_html_e( 'Upgrade for marketing-list automation', 'advanced-form-integration' ); ?></h3>
                            <p>
                                <?php
                                printf(
                                    /* translators: %s: link to pricing page */
                                    wp_kses(
                                        __( 'The <a href="%s" target="_blank" rel="noopener">Pro connector</a> adds extended field mapping plus automatic membership in static Marketing Lists (Outbound Marketing).', 'advanced-form-integration' ),
                                        array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
                                    ),
                                    esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) )
                                );
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Action fields template. We render the same accounts picker used by
     * the Dynamics 365 platform — driven by the same credential list — so
     * the user picks ONE connection and it works for both.
     */
    public function action_fields() {
        $crm = class_exists( 'ADFOIN_Dynamics365' ) ? ADFOIN_Dynamics365::get_instance() : null;
        ?>
        <script type="text/template" id="dynamics365marketing-action-template">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <?php esc_attr_e( 'Dynamics 365 Account', 'advanced-form-integration' ); ?>
                    </th>
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
                           target="_blank"
                           style="margin-left:10px;text-decoration:none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top:3px;"></span>
                            <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                        </a>
                        <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                    </td>
                </tr>

                <tr valign="top" class="alternate">
                    <th scope="row">
                        <?php esc_html_e( 'Marketing List (optional)', 'advanced-form-integration' ); ?>
                    </th>
                    <td>
                        <select name="fieldData[listId]" v-model="fielddata.listId">
                            <option value=""><?php esc_html_e( '— Don\'t add to a list —', 'advanced-form-integration' ); ?></option>
                            <option v-for="(name, id) in fielddata.lists" :value="id">{{ name }}</option>
                        </select>
                        <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                        <p class="description"><?php esc_html_e( 'Only static contact lists appear here. Dynamic segments are populated by query and can\'t accept members via the API.', 'advanced-form-integration' ); ?></p>
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
                <?php adfoin_pro_feature_notice( 'create_marketing_contact', 'Dynamics 365 Marketing [PRO]', 'custom fields' ); ?>
            </table>
        </script>
        <?php
    }

    /**
     * Fetch static Marketing Lists (contact-typed) for the dropdown. Same
     * filter as the Pro tier — restrict to static lists whose members are
     * Contacts so we don't show dynamic segments (read-only) or
     * lead/account lists (wrong entity).
     */
    public function ajax_get_lists() {
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

        $d = ADFOIN_Dynamics365::get_instance();
        $d->set_credentials( $cred_id );

        $endpoint = 'lists?' . http_build_query( array(
            '$select'  => 'listid,listname',
            '$filter'  => 'createdfromcode eq 2 and type eq false',
            '$orderby' => 'listname asc',
            '$top'     => 200,
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

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $lists = array();
        if ( ! empty( $body['value'] ) && is_array( $body['value'] ) ) {
            foreach ( $body['value'] as $list ) {
                if ( ! empty( $list['listid'] ) && ! empty( $list['listname'] ) ) {
                    $lists[ $list['listid'] ] = $list['listname'];
                }
            }
        }

        wp_send_json_success( $lists );
    }

    /**
     * POST to /lists(<listId>)/Members/$ref with `@odata.id` pointing at
     * the contact. Returns 204 No Content on success.
     */
    public function add_contact_to_list( $d, $list_id, $contact_id, $record ) {
        $instance_url = trailingslashit( $d->instance_url );
        $odata_id     = $instance_url . 'api/data/v9.2/contacts(' . $contact_id . ')';
        return $d->dynamics365_request(
            sprintf( 'lists(%s)/Members/$ref', $list_id ),
            'POST',
            array( '@odata.id' => $odata_id ),
            $record
        );
    }

    /**
     * Field list returned over AJAX so the JS component can refresh when
     * the user changes the task selection. Kept as a method (not hardcoded
     * in the JS) so Pro can extend it and so we can localize labels.
     */
    public function ajax_get_fields() {
        adfoin_require_manage_options();
        if ( ! wp_verify_nonce( $_POST['_nonce'] ?? '', 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_marketing_contact';
        wp_send_json_success( $this->get_fields_for_task( $task ) );
    }

    protected function get_fields_for_task( $task ) {
        // Marketing-relevant subset of the Contact entity. Free tier
        // deliberately omits extended demographic/firmographic fields so
        // the Pro upgrade has obvious value. Consent flags ARE here in
        // Free because GDPR-aware sites should be able to honour them
        // without having to upgrade.
        return array(
            array( 'key' => 'firstname',                  'value' => __( 'First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'lastname',                   'value' => __( 'Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'emailaddress1',              'value' => __( 'Email (required — used to dedupe)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'telephone1',                 'value' => __( 'Business Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'mobilephone',                'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'companyname',                'value' => __( 'Company Name', 'advanced-form-integration' ) ),
            array( 'key' => 'jobtitle',                   'value' => __( 'Job Title', 'advanced-form-integration' ) ),
            array( 'key' => 'address1_city',              'value' => __( 'City', 'advanced-form-integration' ) ),
            array( 'key' => 'address1_country',           'value' => __( 'Country', 'advanced-form-integration' ) ),

            // Marketing-specific
            array( 'key' => 'leadsourcecode',             'value' => __( 'Lead Source (option set value)', 'advanced-form-integration' ), 'description' => __( 'Numeric option set value, e.g. 1 = Advertisement.', 'advanced-form-integration' ) ),
            array( 'key' => 'donotemail',                 'value' => __( 'Do Not Email (true/false)', 'advanced-form-integration' ) ),
            array( 'key' => 'donotbulkemail',             'value' => __( 'Do Not Bulk Email (true/false)', 'advanced-form-integration' ) ),
            array( 'key' => 'donotphone',                 'value' => __( 'Do Not Phone (true/false)', 'advanced-form-integration' ) ),
        );
    }
}

ADFOIN_Dynamics365Marketing::get_instance();

/* ---------- Job queue + dispatch ---------- */

function adfoin_dynamics365marketing_job_queue( $data ) {
    if ( ! isset( $data['record'], $data['posted_data'] ) ) {
        return;
    }
    adfoin_dynamics365marketing_send_data( $data['record'], $data['posted_data'] );
}
add_action( 'adfoin_dynamics365marketing_job_queue', 'adfoin_dynamics365marketing_job_queue', 10, 1 );
add_action( 'adfoin_job_queue', 'adfoin_dynamics365marketing_job_queue', 10, 1 );

function adfoin_dynamics365marketing_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) &&
        function_exists( 'adfoin_check_conditional_logic' ) &&
        adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id    = $field_data['credId'] ?? ( $record['cred_id'] ?? '' );
    $list_id    = $field_data['listId'] ?? '';
    $task       = $record['task'] ?? '';

    if ( ! $cred_id || ! $task ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Dynamics365' ) ) {
        // Defensive — should never happen since the CRM platform is
        // required for the credential dropdown, but guard against a
        // misconfigured install that disabled CRM but kept Marketing.
        return;
    }

    $d = ADFOIN_Dynamics365::get_instance();
    $d->set_credentials( $cred_id );

    if ( empty( $d->access_token ) ) {
        $d->request_token();
    }
    if ( empty( $d->access_token ) ) {
        return; // mark_connection_failed already fired inside the base class
    }

    // Resolve tag templates ({{billing_email}} etc.). Skip control keys so
    // they never end up in the Dynamics payload.
    $values = array();
    $skip   = array( 'credId', 'listId', 'lists', 'cl' );
    foreach ( $field_data as $key => $value ) {
        if ( in_array( $key, $skip, true ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) {
            $values[ $key ] = $parsed;
        }
    }

    if ( 'create_marketing_contact' === $task ) {
        adfoin_dynamics365marketing_handle_contact( $d, $values, $list_id, $record );
    }
}

/**
 * Upsert a Marketing Contact by email, optionally adding the resulting
 * contact to a static Marketing List. Same dedupe semantics as the CRM
 * contact handler — the form-submitter doesn't have to choose between
 * "create a contact" and "add to a list" as separate steps.
 *
 * @param ADFOIN_Dynamics365 $d        Hydrated CRM instance (auth ready).
 * @param array              $values   Resolved field map (email required).
 * @param string             $list_id  Optional Marketing List GUID.
 * @param array              $record   Integration record (for logging).
 */
function adfoin_dynamics365marketing_handle_contact( $d, $values, $list_id, $record ) {
    $email = $values['emailaddress1'] ?? '';
    if ( ! $email ) {
        return; // email is the dedupe key
    }

    $payload = $d->finalize_payload( 'contact', $values );

    $existing   = $d->find_record_by_email( 'contacts', $email, $record );
    $contact_id = $existing['contactid'] ?? '';

    if ( $contact_id ) {
        // Update existing contact only if we have fields beyond email —
        // an empty PATCH is harmless but pollutes the audit log.
        if ( count( $payload ) > 1 || ! isset( $payload['emailaddress1'] ) ) {
            $d->dynamics365_request( "contacts({$contact_id})", 'PATCH', $payload, $record );
        }
    } else {
        $response = $d->dynamics365_request( 'contacts', 'POST', $payload, $record );
        if ( ! is_wp_error( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['contactid'] ) ) {
                $contact_id = $body['contactid'];
            } else {
                $hdr = wp_remote_retrieve_header( $response, 'OData-EntityId' );
                if ( $hdr && preg_match( '/contacts\(([0-9a-fA-F-]+)\)/', $hdr, $m ) ) {
                    $contact_id = $m[1];
                }
            }
        }
    }

    if ( $list_id && $contact_id ) {
        ADFOIN_Dynamics365Marketing::get_instance()->add_contact_to_list( $d, $list_id, $contact_id, $record );
    }
}
