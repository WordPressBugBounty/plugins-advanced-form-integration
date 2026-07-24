<?php

/**
 * WP Booking Calendar action platform — local same-site integration (no
 * REST/API keys). Slug is `wpbookingcalendarac`, not `wpbookingcalendar` —
 * the trigger side already uses that slug (includes/triggers/wpbookingcalendar/wpbookingcalendar.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Booking creation goes through the plugin's own documented developer API,
 * wpbc_api_booking_add_new( $booking_dates, $booking_data, $resource_id,
 * $params ) (core/wpbc-dev-api.php), confirmed against the plugin's own
 * source. $booking_data supports a simple flat key => value format (treated
 * as 'text' fields), not just the more verbose nested
 * key => ['value'=>..., 'type'=>...] format — confirmed by reading the
 * function's own doc comments and body. Returns a booking ID or WP_Error.
 *
 * @link https://plugins.trac.wordpress.org/browser/booking/trunk/core/wpbc-dev-api.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_wpbookingcalendarac_actions', 10, 1 );

function adfoin_wpbookingcalendarac_actions( $actions ) {

    $actions['wpbookingcalendarac'] = array(
        'title' => __( 'WP Booking Calendar', 'advanced-form-integration' ),
        'tasks' => array(
            'add_booking' => __( 'Create Booking', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_wpbookingcalendarac_action_fields' );

function adfoin_wpbookingcalendarac_action_fields() {
    ?>
    <script type="text/template" id="wpbookingcalendarac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_booking'">
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

add_action( 'wp_ajax_adfoin_get_wpbookingcalendarac_fields', 'adfoin_get_wpbookingcalendarac_fields', 10, 0 );

function adfoin_get_wpbookingcalendarac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'date', 'value' => __( 'Booking Date', 'advanced-form-integration' ), 'description' => __( 'Required. Format: YYYY-MM-DD.', 'advanced-form-integration' ) ),
        array( 'key' => 'resource_id', 'value' => __( 'Resource ID', 'advanced-form-integration' ), 'description' => __( 'The booking calendar/resource ID. Defaults to 1 if left blank.', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_wpbookingcalendarac_job_queue', 'adfoin_wpbookingcalendarac_job_queue', 10, 1 );

function adfoin_wpbookingcalendarac_job_queue( $data ) {
    adfoin_wpbookingcalendarac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a WP Booking Calendar booking
 */
function adfoin_wpbookingcalendarac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'wpbc_api_booking_add_new' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_booking' !== $task ) {
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

    if ( empty( $prepared_data['date'] ) ) {
        $response_body['message'] = __( 'A booking date is required.', 'advanced-form-integration' );
    } else {
        $resource_id = ! empty( $prepared_data['resource_id'] ) ? absint( $prepared_data['resource_id'] ) : 1;

        $booking_data = array();

        foreach ( array( 'name', 'email', 'phone' ) as $field_key ) {
            if ( ! empty( $prepared_data[ $field_key ] ) ) {
                $booking_data[ $field_key ] = $prepared_data[ $field_key ];
            }
        }

        $booking_id = wpbc_api_booking_add_new( array( $prepared_data['date'] ), $booking_data, $resource_id );

        if ( is_wp_error( $booking_id ) ) {
            $response_body['message'] = $booking_id->get_error_message();
        } elseif ( $booking_id ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Booking created successfully.', 'advanced-form-integration' );
            $response_body['id']      = $booking_id;
        } else {
            $response_body['message'] = __( 'Failed to create the booking. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'wpbookingcalendarac', $log_args, $record );
}
