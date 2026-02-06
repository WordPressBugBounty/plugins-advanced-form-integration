<?php

add_filter( 'adfoin_action_providers', 'adfoin_latepoint_actions', 10, 1 );

function adfoin_latepoint_actions( $actions ) {

    $actions['latepoint'] = array(
        'title' => __( 'LatePoint', 'advanced-form-integration' ),
        'tasks' => array(
            'create_customer'       => __( 'Create or Update Customer', 'advanced-form-integration' ),
            'update_customer'       => __( 'Update Customer Profile', 'advanced-form-integration' ),
            'update_booking_status' => __( 'Update Booking Status', 'advanced-form-integration' ),
            'add_booking_note'      => __( 'Add Booking Activity Note', 'advanced-form-integration' ),
            'add_customer_note'     => __( 'Add Customer Note', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_latepoint_action_fields' );

function adfoin_latepoint_action_fields() {
    ?>
    <script type="text/template" id="latepoint-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_customer'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Email is required. If a customer with that email already exists their record will be updated; otherwise a new customer is created. Optional fields update core profile or meta values.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_customer'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a customer ID or email to locate the customer. Only supplied fields are updated. Meta JSON accepts a key/value object.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_booking_status'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Booking ID is required. Status must be a valid LatePoint status key such as approved, pending, cancelled, no_show, or completed (custom statuses are also accepted).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'add_booking_note'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Adds an activity log entry linked to the booking. Provide a note body; optional initiator values identify who created the note.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'add_customer_note'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Adds a timeline note on the customer profile. Supply a customer ID or email along with the note content.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            :key="field.value"
                            :field="field"
                            :trigger="trigger"
                            :action="action"
                            :fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_latepoint_job_queue', 'adfoin_latepoint_job_queue', 10, 1 );

function adfoin_latepoint_job_queue( $data ) {
    adfoin_latepoint_send_data( $data['record'], $data['posted_data'] );
}

if ( ! function_exists( 'adfoin_latepoint_is_ready' ) ) {
    function adfoin_latepoint_is_ready() {
        return class_exists( 'OsBookingModel' );
    }
}

function adfoin_latepoint_send_data( $record, $posted_data ) {
    if ( ! adfoin_latepoint_is_ready() ) {
        adfoin_latepoint_action_log( $record, __( 'LatePoint is not active.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $parsed = array();

    if ( is_array( $field_data ) ) {
        foreach ( $field_data as $key => $value ) {
            $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
        }
    }

    switch ( $task ) {
        case 'create_customer':
            adfoin_latepoint_action_create_customer( $record, $parsed );
            break;
        case 'update_customer':
            adfoin_latepoint_action_update_customer( $record, $parsed );
            break;
        case 'update_booking_status':
            adfoin_latepoint_action_update_booking_status( $record, $parsed );
            break;
        case 'add_booking_note':
            adfoin_latepoint_action_add_booking_note( $record, $parsed );
            break;
        case 'add_customer_note':
            adfoin_latepoint_action_add_customer_note( $record, $parsed );
            break;
        default:
            adfoin_latepoint_action_log(
                $record,
                __( 'Unknown LatePoint task received.', 'advanced-form-integration' ),
                array( 'task' => $task ),
                false
            );
            break;
    }
}

function adfoin_latepoint_action_create_customer( $record, $parsed ) {
    if ( ! class_exists( 'OsCustomerModel' ) ) {
        adfoin_latepoint_action_log( $record, __( 'LatePoint customer model unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $email = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';
    if ( ! $email ) {
        adfoin_latepoint_action_log( $record, __( 'Customer email is required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $customer = adfoin_latepoint_action_get_customer_by_email( $email );
    $is_update = $customer instanceof OsCustomerModel && ! $customer->is_new_record();

    if ( ! $is_update ) {
        $customer = new OsCustomerModel();
    }

    $data = array(
        'email'      => $email,
        'first_name' => isset( $parsed['first_name'] ) ? sanitize_text_field( $parsed['first_name'] ) : '',
        'last_name'  => isset( $parsed['last_name'] ) ? sanitize_text_field( $parsed['last_name'] ) : '',
        'phone'      => isset( $parsed['phone'] ) ? sanitize_text_field( $parsed['phone'] ) : '',
        'notes'      => isset( $parsed['notes'] ) ? sanitize_textarea_field( $parsed['notes'] ) : '',
        'admin_notes'=> isset( $parsed['admin_notes'] ) ? sanitize_textarea_field( $parsed['admin_notes'] ) : '',
        'wordpress_user_id' => isset( $parsed['wordpress_user_id'] ) && $parsed['wordpress_user_id'] !== '' ? absint( $parsed['wordpress_user_id'] ) : $customer->wordpress_user_id,
    );

    if ( isset( $parsed['is_guest'] ) && '' !== $parsed['is_guest'] ) {
        $data['is_guest'] = adfoin_latepoint_action_to_bool( $parsed['is_guest'] ) ? 1 : 0;
    }

    // Remove keys with null to avoid overwriting with empties unless provided.
    $filtered = array();
    foreach ( $data as $key => $value ) {
        if ( '' !== $value && null !== $value ) {
            $filtered[ $key ] = $value;
        }
    }
    $customer->set_data( $filtered );

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        $customer->status = sanitize_key( $parsed['status'] );
    }

    $timezone = isset( $parsed['timezone'] ) ? sanitize_text_field( $parsed['timezone'] ) : '';
    $meta_updates = array();
    if ( isset( $parsed['meta_json'] ) && '' !== trim( $parsed['meta_json'] ) ) {
        $meta_updates = adfoin_latepoint_action_decode_json( $parsed['meta_json'] );
        if ( false === $meta_updates ) {
            adfoin_latepoint_action_log(
                $record,
                __( 'Customer meta JSON could not be parsed.', 'advanced-form-integration' ),
                array( 'meta_json' => $parsed['meta_json'] ),
                false
            );
            $meta_updates = array();
        }
    }

    $password = isset( $parsed['password'] ) ? $parsed['password'] : '';
    $timeline_note = isset( $parsed['timeline_note'] ) ? sanitize_textarea_field( $parsed['timeline_note'] ) : '';

    if ( ! $customer->save() ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Failed to save customer.', 'advanced-form-integration' ),
            array( 'email' => $email, 'errors' => adfoin_latepoint_action_collect_model_errors( $customer ) ),
            false
        );
        return;
    }

    if ( '' !== $password ) {
        $customer->update_password( $password );
    }

    if ( '' !== $timezone && method_exists( $customer, 'set_timezone_name' ) ) {
        $customer->set_timezone_name( $timezone );
    }

    adfoin_latepoint_action_update_customer_meta_fields( $customer, $meta_updates );

    if ( '' !== $timeline_note ) {
        $customer->add_note( $timeline_note );
    }

    adfoin_latepoint_action_log(
        $record,
        $is_update ? __( 'Customer updated successfully.', 'advanced-form-integration' ) : __( 'Customer created successfully.', 'advanced-form-integration' ),
        array(
            'customer_id' => $customer->id,
            'email'       => $customer->email,
        ),
        true
    );
}

function adfoin_latepoint_action_update_customer( $record, $parsed ) {
    if ( ! class_exists( 'OsCustomerModel' ) ) {
        adfoin_latepoint_action_log( $record, __( 'LatePoint customer model unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $customer = null;
    $customer_id = isset( $parsed['customer_id'] ) && $parsed['customer_id'] !== '' ? absint( $parsed['customer_id'] ) : 0;
    $email       = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';

    if ( $customer_id ) {
        $customer = adfoin_latepoint_action_get_customer_by_id( $customer_id );
    } elseif ( $email ) {
        $customer = adfoin_latepoint_action_get_customer_by_email( $email );
    }

    if ( ! $customer ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Customer could not be located.', 'advanced-form-integration' ),
            array(
                'customer_id' => $customer_id,
                'email'       => $email,
            ),
            false
        );
        return;
    }

    $update_data = array();

    if ( $email && $email !== $customer->email ) {
        $update_data['email'] = $email;
    }

    if ( isset( $parsed['first_name'] ) && '' !== $parsed['first_name'] ) {
        $update_data['first_name'] = sanitize_text_field( $parsed['first_name'] );
    }
    if ( isset( $parsed['last_name'] ) && '' !== $parsed['last_name'] ) {
        $update_data['last_name'] = sanitize_text_field( $parsed['last_name'] );
    }
    if ( isset( $parsed['phone'] ) && '' !== $parsed['phone'] ) {
        $update_data['phone'] = sanitize_text_field( $parsed['phone'] );
    }
    if ( isset( $parsed['notes'] ) ) {
        $update_data['notes'] = sanitize_textarea_field( $parsed['notes'] );
    }
    if ( isset( $parsed['admin_notes'] ) ) {
        $update_data['admin_notes'] = sanitize_textarea_field( $parsed['admin_notes'] );
    }
    if ( isset( $parsed['is_guest'] ) && '' !== $parsed['is_guest'] ) {
        $update_data['is_guest'] = adfoin_latepoint_action_to_bool( $parsed['is_guest'] ) ? 1 : 0;
    }
    if ( isset( $parsed['wordpress_user_id'] ) && $parsed['wordpress_user_id'] !== '' ) {
        $update_data['wordpress_user_id'] = absint( $parsed['wordpress_user_id'] );
    }

    if ( ! empty( $update_data ) ) {
        $customer->set_data( $update_data );
    }

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        $customer->status = sanitize_key( $parsed['status'] );
    }

    $timezone = isset( $parsed['timezone'] ) ? sanitize_text_field( $parsed['timezone'] ) : '';
    $meta_updates = array();
    if ( isset( $parsed['meta_json'] ) && '' !== trim( $parsed['meta_json'] ) ) {
        $meta_updates = adfoin_latepoint_action_decode_json( $parsed['meta_json'] );
        if ( false === $meta_updates ) {
            adfoin_latepoint_action_log(
                $record,
                __( 'Customer meta JSON could not be parsed.', 'advanced-form-integration' ),
                array( 'meta_json' => $parsed['meta_json'] ),
                false
            );
            $meta_updates = array();
        }
    }

    $password = isset( $parsed['password'] ) ? $parsed['password'] : '';
    $timeline_note = isset( $parsed['timeline_note'] ) ? sanitize_textarea_field( $parsed['timeline_note'] ) : '';

    if ( ! $customer->save() ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Failed to update customer.', 'advanced-form-integration' ),
            array(
                'customer_id' => $customer->id,
                'errors'      => adfoin_latepoint_action_collect_model_errors( $customer ),
            ),
            false
        );
        return;
    }

    if ( '' !== $password ) {
        $customer->update_password( $password );
    }

    if ( '' !== $timezone && method_exists( $customer, 'set_timezone_name' ) ) {
        $customer->set_timezone_name( $timezone );
    }

    adfoin_latepoint_action_update_customer_meta_fields( $customer, $meta_updates );

    if ( '' !== $timeline_note ) {
        $customer->add_note( $timeline_note );
    }

    adfoin_latepoint_action_log(
        $record,
        __( 'Customer updated successfully.', 'advanced-form-integration' ),
        array(
            'customer_id' => $customer->id,
            'email'       => $customer->email,
        ),
        true
    );
}

function adfoin_latepoint_action_update_booking_status( $record, $parsed ) {
    if ( ! class_exists( 'OsBookingModel' ) ) {
        adfoin_latepoint_action_log( $record, __( 'LatePoint booking model unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $booking_id = isset( $parsed['booking_id'] ) ? absint( $parsed['booking_id'] ) : 0;
    $status_raw = isset( $parsed['status'] ) ? $parsed['status'] : '';
    $status     = sanitize_key( $status_raw );

    if ( ! $booking_id ) {
        adfoin_latepoint_action_log( $record, __( 'Booking ID is required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    if ( '' === $status ) {
        adfoin_latepoint_action_log( $record, __( 'A status value is required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $allowed_statuses = adfoin_latepoint_action_get_allowed_statuses();
    if ( ! in_array( $status, $allowed_statuses, true ) ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Invalid LatePoint booking status provided.', 'advanced-form-integration' ),
            array( 'status' => $status_raw ),
            false
        );
        return;
    }

    $booking = new OsBookingModel( $booking_id );
    if ( ! $booking->id ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Booking could not be found.', 'advanced-form-integration' ),
            array( 'booking_id' => $booking_id ),
            false
        );
        return;
    }

    $note = isset( $parsed['note'] ) ? sanitize_textarea_field( $parsed['note'] ) : '';
    $initiated = adfoin_latepoint_action_parse_initiator( $parsed );

    $result = $booking->update_status( $status );
    if ( ! $result ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Failed to update booking status.', 'advanced-form-integration' ),
            array(
                'booking_id' => $booking_id,
                'errors'     => adfoin_latepoint_action_collect_model_errors( $booking ),
            ),
            false
        );
        return;
    }

    if ( '' !== $note && class_exists( 'OsActivitiesHelper' ) ) {
        adfoin_latepoint_action_create_activity(
            array(
                'booking_id'      => $booking_id,
                'customer_id'     => $booking->customer_id,
                'service_id'      => $booking->service_id,
                'agent_id'        => $booking->agent_id,
                'code'            => 'booking_note',
                'description'     => $note,
                'initiated_by'    => $initiated['initiated_by'],
                'initiated_by_id' => $initiated['initiated_by_id'],
            )
        );
    }

    adfoin_latepoint_action_log(
        $record,
        __( 'Booking status updated successfully.', 'advanced-form-integration' ),
        array(
            'booking_id' => $booking_id,
            'status'     => $status,
        ),
        true
    );
}

function adfoin_latepoint_action_add_booking_note( $record, $parsed ) {
    if ( ! class_exists( 'OsActivityModel' ) || ! class_exists( 'OsBookingModel' ) ) {
        adfoin_latepoint_action_log( $record, __( 'LatePoint activity logging is unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $booking_id = isset( $parsed['booking_id'] ) ? absint( $parsed['booking_id'] ) : 0;
    $note       = isset( $parsed['note'] ) ? sanitize_textarea_field( $parsed['note'] ) : '';
    $code       = isset( $parsed['code'] ) ? sanitize_key( $parsed['code'] ) : 'booking_note';

    if ( ! $booking_id || '' === $note ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Booking ID and note content are required.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $booking = new OsBookingModel( $booking_id );
    if ( ! $booking->id ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Booking could not be found.', 'advanced-form-integration' ),
            array( 'booking_id' => $booking_id ),
            false
        );
        return;
    }

    $initiated = adfoin_latepoint_action_parse_initiator( $parsed );

    $activity = adfoin_latepoint_action_create_activity(
        array(
            'booking_id'      => $booking_id,
            'customer_id'     => $booking->customer_id,
            'service_id'      => $booking->service_id,
            'agent_id'        => $booking->agent_id,
            'code'            => $code ?: 'booking_note',
            'description'     => $note,
            'initiated_by'    => $initiated['initiated_by'],
            'initiated_by_id' => $initiated['initiated_by_id'],
        )
    );

    if ( ! $activity ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Failed to create booking note.', 'advanced-form-integration' ),
            array( 'booking_id' => $booking_id ),
            false
        );
        return;
    }

    adfoin_latepoint_action_log(
        $record,
        __( 'Booking note added successfully.', 'advanced-form-integration' ),
        array(
            'booking_id' => $booking_id,
            'activity_id' => $activity->id ?? null,
        ),
        true
    );
}

function adfoin_latepoint_action_add_customer_note( $record, $parsed ) {
    if ( ! class_exists( 'OsCustomerModel' ) ) {
        adfoin_latepoint_action_log( $record, __( 'LatePoint customer model unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $customer = null;
    $customer_id = isset( $parsed['customer_id'] ) && $parsed['customer_id'] !== '' ? absint( $parsed['customer_id'] ) : 0;
    $email       = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';
    $note        = isset( $parsed['note'] ) ? sanitize_textarea_field( $parsed['note'] ) : '';

    if ( ! $customer_id && ! $email ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'A customer ID or email is required.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    if ( '' === $note ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Note content is required.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    if ( $customer_id ) {
        $customer = adfoin_latepoint_action_get_customer_by_id( $customer_id );
    } elseif ( $email ) {
        $customer = adfoin_latepoint_action_get_customer_by_email( $email );
    }

    if ( ! $customer ) {
        adfoin_latepoint_action_log(
            $record,
            __( 'Customer could not be found.', 'advanced-form-integration' ),
            array(
                'customer_id' => $customer_id,
                'email'       => $email,
            ),
            false
        );
        return;
    }

    $customer->add_note( $note );

    adfoin_latepoint_action_log(
        $record,
        __( 'Customer note added successfully.', 'advanced-form-integration' ),
        array(
            'customer_id' => $customer->id,
        ),
        true
    );
}

function adfoin_latepoint_action_update_customer_meta_fields( OsCustomerModel $customer, $meta_updates ) {
    if ( empty( $meta_updates ) || ! is_array( $meta_updates ) ) {
        return;
    }

    foreach ( $meta_updates as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $meta_key = sanitize_key( $key );

        if ( is_array( $value ) || is_object( $value ) ) {
            $meta_value = maybe_serialize( $value );
        } else {
            $meta_value = sanitize_text_field( (string) $value );
        }
        $customer->save_meta_by_key( $meta_key, $meta_value );
    }
}

function adfoin_latepoint_action_get_customer_by_email( $email ) {
    if ( ! $email ) {
        return false;
    }

    $query = new OsCustomerModel();
    $found = $query->where( array( 'email' => $email ) )->set_limit( 1 )->get_results_as_models();

    if ( $found instanceof OsCustomerModel && $found->id ) {
        return $found;
    }

    return false;
}

function adfoin_latepoint_action_get_customer_by_id( $customer_id ) {
    if ( ! $customer_id ) {
        return false;
    }

    $customer = new OsCustomerModel( $customer_id );
    if ( $customer->id ) {
        return $customer;
    }

    return false;
}

function adfoin_latepoint_action_decode_json( $value ) {
    if ( '' === trim( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return false;
    }

    return $decoded;
}

function adfoin_latepoint_action_collect_model_errors( $model ) {
    if ( ! is_object( $model ) || ! method_exists( $model, 'get_error_messages' ) ) {
        return array();
    }

    $messages = $model->get_error_messages();
    if ( empty( $messages ) ) {
        return array();
    }

    if ( is_array( $messages ) ) {
        return array_values( array_filter( array_map( 'trim', $messages ) ) );
    }

    return array( (string) $messages );
}

function adfoin_latepoint_action_get_allowed_statuses() {
    if ( class_exists( 'OsBookingHelper' ) ) {
        $statuses = OsBookingHelper::get_statuses_list();
        return array_keys( (array) $statuses );
    }

    return array( 'approved', 'pending', 'cancelled', 'no_show', 'completed' );
}

function adfoin_latepoint_action_to_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function adfoin_latepoint_action_parse_initiator( $parsed ) {
    $initiated_by    = isset( $parsed['initiated_by'] ) ? sanitize_key( $parsed['initiated_by'] ) : '';
    $initiated_by_id = isset( $parsed['initiated_by_id'] ) && $parsed['initiated_by_id'] !== '' ? absint( $parsed['initiated_by_id'] ) : 0;

    return array(
        'initiated_by'    => $initiated_by,
        'initiated_by_id' => $initiated_by_id,
    );
}

function adfoin_latepoint_action_create_activity( $data ) {
    if ( ! class_exists( 'OsActivitiesHelper' ) ) {
        return false;
    }

    return OsActivitiesHelper::create_activity( $data );
}

function adfoin_latepoint_action_log( $record, $message, $payload, $success ) {
    $log_response = array(
        'response' => array(
            'code'    => $success ? 200 : 400,
            'message' => $message,
        ),
        'body' => array(
            'success' => $success,
            'message' => $message,
        ),
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $payload,
    );

    adfoin_add_to_log( $log_response, 'latepoint', $log_args, $record );
}

