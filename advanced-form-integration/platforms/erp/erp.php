<?php

/**
 * WP ERP action platform — local same-site integration (no REST/API keys).
 * WP ERP (HR, Accounting & CRM suite by WeDevs) shares a single "People"
 * model across its CRM/HR modules. Contact creation goes through the real
 * global erp_insert_people( $args ) function (includes/functions-people.php),
 * confirmed against the plugin's own source — it validates email, accepts
 * first_name/last_name/phone/company/etc, and returns the person ID (or
 * WP_Error on failure). 'type' is hardcoded to 'contact' here since that's
 * WP ERP's standard CRM contact type (as opposed to 'company', 'customer',
 * or 'vendor').
 *
 * @link https://plugins.trac.wordpress.org/browser/erp/trunk/includes/functions-people.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_erp_actions', 10, 1 );

function adfoin_erp_actions( $actions ) {

    $actions['erp'] = array(
        'title' => __( 'WP ERP', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_erp_action_fields' );

function adfoin_erp_action_fields() {
    ?>
    <script type="text/template" id="erp-action-template">
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

add_action( 'wp_ajax_adfoin_get_erp_fields', 'adfoin_get_erp_fields', 10, 0 );

function adfoin_get_erp_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing contact is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'website', 'value' => __( 'Website', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_erp_job_queue', 'adfoin_erp_job_queue', 10, 1 );

function adfoin_erp_job_queue( $data ) {
    adfoin_erp_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles adding a WP ERP CRM contact
 */
function adfoin_erp_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'erp_insert_people' ) ) {
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
        $args         = $prepared_data;
        $args['type'] = 'contact';

        $person_id = erp_insert_people( $args );

        if ( is_wp_error( $person_id ) ) {
            $response_body['message'] = $person_id->get_error_message();
        } elseif ( $person_id ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Contact added successfully.', 'advanced-form-integration' );
            $response_body['id']      = $person_id;
        } else {
            $response_body['message'] = __( 'Failed to add the contact. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'erp', $log_args, $record );
}
