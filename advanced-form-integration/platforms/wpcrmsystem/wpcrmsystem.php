<?php

/**
 * WP CRM System action platform — local same-site integration (no
 * REST/API keys). A real, dedicated CRM plugin (10,000+ installs) not
 * previously covered by this codebase as either a trigger or an action.
 *
 * Contact creation goes through the real static method
 * WPCRM_System_Create::contacts( $fields, $custom_fields, $categories,
 * $status, $author, $update ) (includes/class-wpcrmsystemcreate.php),
 * confirmed against the plugin's own source. It already does find-or-create
 * by email internally ('update-create' mode: creates if no contact with
 * that email exists, updates if one does) — no separate lookup needed. Its
 * $fields array is accessed directly without isset() checks
 * ($fields['first_name'], etc), so every recognized key is always passed
 * here (defaulting to '') to avoid PHP undefined-array-key warnings.
 *
 * @link https://plugins.trac.wordpress.org/browser/wp-crm-system/trunk/includes/class-wpcrmsystemcreate.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_wpcrmsystem_actions', 10, 1 );

function adfoin_wpcrmsystem_actions( $actions ) {

    $actions['wpcrmsystem'] = array(
        'title' => __( 'WP CRM System', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add/Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_wpcrmsystem_action_fields' );

function adfoin_wpcrmsystem_action_fields() {
    ?>
    <script type="text/template" id="wpcrmsystem-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_wpcrmsystem_fields', 'adfoin_get_wpcrmsystem_fields', 10, 0 );

function adfoin_get_wpcrmsystem_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing contact is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'mobile', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'address_1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'address_2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'state', 'value' => __( 'State', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'postal', 'value' => __( 'Postal / ZIP Code', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_wpcrmsystem_job_queue', 'adfoin_wpcrmsystem_job_queue', 10, 1 );

function adfoin_wpcrmsystem_job_queue( $data ) {
    adfoin_wpcrmsystem_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles adding/updating a WP CRM System contact
 */
function adfoin_wpcrmsystem_send_data( $record, $posted_data ) {

    if ( ! class_exists( 'WPCRM_System_Create' ) ) {
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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) ) {
        $response_body['message'] = __( 'A valid email address is required.', 'advanced-form-integration' );
    } else {
        // WPCRM_System_Create::contacts() reads every one of these keys directly
        // without isset() checks, so all must be present (defaulting to '').
        $fields = wp_parse_args(
            $prepared_data,
            array(
                'prefix'     => '',
                'first_name' => '',
                'last_name'  => '',
                'org'        => '',
                'role'       => '',
                'url'        => '',
                'email'      => '',
                'phone'      => '',
                'mobile'     => '',
                'fax'        => '',
                'address_1'  => '',
                'address_2'  => '',
                'city'       => '',
                'state'      => '',
                'postal'     => '',
                'country'    => '',
                'additional' => '',
            )
        );

        $contact_id = WPCRM_System_Create::contacts( $fields, '', '', 'publish', '', 'update-create' );

        if ( $contact_id ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Contact added/updated successfully.', 'advanced-form-integration' );
            $response_body['id']      = $contact_id;
        } else {
            $response_body['message'] = __( 'Failed to add/update the contact. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'wpcrmsystem', $log_args, $record );
}
