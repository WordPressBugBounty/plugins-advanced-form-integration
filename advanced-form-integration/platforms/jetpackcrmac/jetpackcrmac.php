<?php

/**
 * Jetpack CRM (formerly Zero BS CRM) action platform — local same-site
 * integration (no REST/API keys).
 *
 * Contact create/update goes through the plugin's own public integration
 * entry point, zeroBS_integrations_addOrUpdateCustomer() (includes/
 * ZeroBSCRM.IntegrationFuncs.php) — the same function the plugin's own
 * api/create_customer.php REST endpoint calls internally. It expects a
 * $customerFields array keyed by zbsc_-prefixed field names (zbsc_email,
 * zbsc_fname, zbsc_lname, zbsc_addr1, etc, confirmed against the function's
 * own field-by-field switch/handling) and looks up an existing contact by
 * email before deciding whether to create or update.
 *
 * Tags are not part of that helper's field set, so they're applied
 * separately via the DAL contact object ($zbs->DAL->contacts->addUpdateContact(),
 * includes/ZeroBSCRM.DAL3.Obj.Contacts.php), whose $args['data'] accepts
 * 'tags' (array of tag IDs or tag name strings) + 'tag_mode' ('append' here
 * so existing tags on an updated contact are preserved). The tag list itself
 * comes from $zbs->DAL->getAllTags() (includes/ZeroBSCRM.DAL3.php), which
 * returns every tag regardless of object type (Jetpack CRM tags aren't
 * scoped per-object-type at the definition level).
 *
 * @link https://plugins.trac.wordpress.org/browser/zero-bs-crm/trunk/includes/ZeroBSCRM.IntegrationFuncs.php
 * @link https://plugins.trac.wordpress.org/browser/zero-bs-crm/trunk/includes/ZeroBSCRM.DAL3.Obj.Contacts.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_jetpackcrmac_actions', 10, 1 );

function adfoin_jetpackcrmac_actions( $actions ) {

    $actions['jetpackcrmac'] = array(
        'title' => __( 'Jetpack CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_jetpackcrmac_action_fields' );

function adfoin_jetpackcrmac_action_fields() {
    ?>
    <script type="text/template" id="jetpackcrmac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Tag', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[tagId]" v-model="fielddata.tagId">
                        <option value=""><?php _e( 'Select Tag... (optional)', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.tagList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': tagLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_jetpackcrmac_tags', 'adfoin_get_jetpackcrmac_tags', 10, 0 );

/*
 * Get Jetpack CRM tags
 */
function adfoin_get_jetpackcrmac_tags() {
    adfoin_verify_nonce();

    global $zbs;

    if ( ! isset( $zbs ) || ! isset( $zbs->DAL ) || ! method_exists( $zbs->DAL, 'getAllTags' ) ) {
        wp_send_json_error( __( 'Jetpack CRM is not active.', 'advanced-form-integration' ) );
    }

    $raw_tags = $zbs->DAL->getAllTags( array( 'perPage' => 999 ) );
    $tags     = array();

    if ( is_array( $raw_tags ) ) {
        foreach ( $raw_tags as $tag ) {
            $tag_id   = isset( $tag['ID'] ) ? $tag['ID'] : ( isset( $tag['id'] ) ? $tag['id'] : '' );
            $tag_name = isset( $tag['name'] ) ? $tag['name'] : ( isset( $tag['zbstag_name'] ) ? $tag['zbstag_name'] : '' );

            if ( '' === $tag_id ) {
                continue;
            }

            $tags[ $tag_id ] = $tag_name;
        }
    }

    wp_send_json_success( $tags );
}

add_action( 'wp_ajax_adfoin_get_jetpackcrmac_contact_fields', 'adfoin_get_jetpackcrmac_contact_fields', 10, 0 );

/*
 * Get Jetpack CRM contact fields
 */
function adfoin_get_jetpackcrmac_contact_fields() {
    adfoin_verify_nonce();

    if ( ! function_exists( 'zeroBS_integrations_addOrUpdateCustomer' ) ) {
        wp_send_json_error( __( 'Jetpack CRM is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'zbsc_email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Used to find or create the contact.', 'advanced-form-integration' ) ),
        array( 'key' => 'zbsc_prefix', 'value' => __( 'Name Prefix', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_fname', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_lname', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_status', 'value' => __( 'Status', 'advanced-form-integration' ), 'description' => __( 'e.g. Lead, Customer', 'advanced-form-integration' ) ),
        array( 'key' => 'zbsc_hometel', 'value' => __( 'Home Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_worktel', 'value' => __( 'Work Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_mobtel', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_addr1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_addr2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_city', 'value' => __( 'City', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_county', 'value' => __( 'County / State', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_postcode', 'value' => __( 'Postal / ZIP Code', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'zbsc_notes', 'value' => __( 'Notes', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_jetpackcrmac_job_queue', 'adfoin_jetpackcrmac_job_queue', 10, 1 );

function adfoin_jetpackcrmac_job_queue( $data ) {
    adfoin_jetpackcrmac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating/updating a Jetpack CRM contact
 */
function adfoin_jetpackcrmac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'zeroBS_integrations_addOrUpdateCustomer' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_contact' !== $task ) {
        return;
    }

    $tag_id = isset( $field_data['tagId'] ) ? $field_data['tagId'] : '';
    unset( $field_data['tagId'] );

    $prepared_data = array();

    foreach ( $field_data as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed_value || null === $parsed_value ) {
            continue;
        }

        $prepared_data[ $key ] = $parsed_value;
    }

    $request_payload = $prepared_data;
    $response_body   = array( 'success' => false );
    $status_code     = 400;

    if ( empty( $prepared_data['zbsc_email'] ) || ! is_email( $prepared_data['zbsc_email'] ) ) {
        $response_body['message'] = __( 'A valid email address is required to create a Jetpack CRM contact.', 'advanced-form-integration' );
    } else {
        $customer_id = zeroBS_integrations_addOrUpdateCustomer(
            'adfoin',
            $prepared_data['zbsc_email'],
            $prepared_data,
            '',
            'auto',
            false,
            false,
            'update'
        );

        if ( $customer_id ) {
            global $zbs;

            if ( $tag_id && isset( $zbs ) && isset( $zbs->DAL ) && isset( $zbs->DAL->contacts ) ) {
                // Use the dedicated tag-only method, not addUpdateContact() — passing
                // 'tags'/'tag_mode' through addUpdateContact()'s generic $args['data']
                // merges in that method's OTHER default (blank) fields and overwrites
                // the email/name/etc. that zeroBS_integrations_addOrUpdateCustomer()
                // just set above. 'tagIDs' (not 'tags') because the dropdown supplies a
                // tag ID — 'tags' expects tag name strings and looks them up by name.
                $zbs->DAL->contacts->addUpdateContactTags(
                    array(
                        'id'     => $customer_id,
                        'tagIDs' => array( $tag_id ),
                        'mode'   => 'append',
                    )
                );
            }

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Contact created/updated successfully.', 'advanced-form-integration' );
            $response_body['id']      = $customer_id;
        } else {
            $response_body['message'] = __( 'Failed to create/update Jetpack CRM contact. Please verify the supplied data.', 'advanced-form-integration' );
        }
    }

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    $log_response = array(
        'response' => array(
            'code'    => $status_code,
            'message' => $response_body['message'],
        ),
        'body'     => $response_body,
    );

    adfoin_add_to_log( $log_response, 'jetpackcrmac', $log_args, $record );
}
