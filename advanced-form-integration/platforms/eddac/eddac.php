<?php

/**
 * Easy Digital Downloads action platform — local same-site integration (no
 * REST/API keys). Slug is `eddac`, not `edd` — the trigger side already uses
 * that slug (includes/triggers/edd/edd.php); this codebase's convention for a
 * same-slug trigger/action pair is an `ac` suffix on the action (see
 * gravityformsac, wpformsac, buddypressac).
 *
 * Customer creation goes through the real edd_add_customer( $data ) function
 * (includes/customer-functions.php) — confirmed against the plugin's own
 * source. Email is the only required key; it looks up an existing customer
 * internally and won't duplicate.
 *
 * @link https://plugins.trac.wordpress.org/browser/easy-digital-downloads/trunk/includes/customer-functions.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_eddac_actions', 10, 1 );

function adfoin_eddac_actions( $actions ) {

    $actions['eddac'] = array(
        'title' => __( 'Easy Digital Downloads', 'advanced-form-integration' ),
        'tasks' => array(
            'add_customer' => __( 'Add Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_eddac_action_fields' );

function adfoin_eddac_action_fields() {
    ?>
    <script type="text/template" id="eddac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_customer'">
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

add_action( 'wp_ajax_adfoin_get_eddac_fields', 'adfoin_get_eddac_fields', 10, 0 );

function adfoin_get_eddac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Used to find or create the customer.', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'Name', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_eddac_job_queue', 'adfoin_eddac_job_queue', 10, 1 );

function adfoin_eddac_job_queue( $data ) {
    adfoin_eddac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating an Easy Digital Downloads customer
 */
function adfoin_eddac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'edd_add_customer' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_customer' !== $task ) {
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
        $customer_id = edd_add_customer(
            array(
                'email' => $prepared_data['email'],
                'name'  => isset( $prepared_data['name'] ) ? $prepared_data['name'] : '',
            )
        );

        if ( $customer_id ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Customer created/found successfully.', 'advanced-form-integration' );
            $response_body['id']      = $customer_id;
        } else {
            $response_body['message'] = __( 'Failed to create the EDD customer. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'eddac', $log_args, $record );
}
