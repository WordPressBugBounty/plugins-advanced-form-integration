<?php

add_filter( 'adfoin_action_providers', 'adfoin_webbabookinglite_actions', 10, 1 );

function adfoin_webbabookinglite_actions( $actions ) {
    $actions['webbabookinglite'] = array(
        'title' => __( 'Webba Booking Lite', 'advanced-form-integration' ),
        'tasks' => array(
            'create_booking' => __( 'Create Booking', 'advanced-form-integration' ),
            'update_booking' => __( 'Update Booking', 'advanced-form-integration' ),
            'delete_booking' => __( 'Delete Booking', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_webbabookinglite_action_fields' );

function adfoin_webbabookinglite_action_fields() {
    ?>
    <script type="text/template" id="webbabookinglite-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_booking'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Service ID, timestamp (seconds), and customer details are required. Custom field data should be provided as a JSON array matching the plugin schema.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_booking'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Booking ID is required. Only supplied fields are updated. For custom fields provide a JSON array as with creation.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'delete_booking'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the booking ID to cancel or delete. Set delete mode to permanent to remove the record entirely.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_webbabookinglite_job_queue', 'adfoin_webbabookinglite_job_queue', 10, 1 );

function adfoin_webbabookinglite_job_queue( $data ) {
    adfoin_webbabookinglite_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_webbabookinglite_plugin_ready() {
    return class_exists( 'WBK_Booking_Factory' ) && function_exists( 'WBK_Model_Utils' );
}

function adfoin_webbabookinglite_send_data( $record, $posted_data ) {
    if ( ! adfoin_webbabookinglite_plugin_ready() ) {
        adfoin_webbabookinglite_action_log( $record, __( 'Webba Booking Lite is not active.', 'advanced-form-integration' ), array(), false );
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
        case 'create_booking':
            adfoin_webbabookinglite_action_create_booking( $record, $parsed );
            break;
        case 'update_booking':
            adfoin_webbabookinglite_action_update_booking( $record, $parsed );
            break;
        case 'delete_booking':
            adfoin_webbabookinglite_action_delete_booking( $record, $parsed );
            break;
        default:
            adfoin_webbabookinglite_action_log(
                $record,
                __( 'Unknown Webba Booking Lite task received.', 'advanced-form-integration' ),
                array( 'task' => $task ),
                false
            );
            break;
    }
}

function adfoin_webbabookinglite_action_create_booking( $record, $parsed ) {
    $service_id = isset( $parsed['service_id'] ) ? absint( $parsed['service_id'] ) : 0;
    $timestamp  = isset( $parsed['timestamp'] ) ? intval( $parsed['timestamp'] ) : 0;

    if ( ! $service_id || ! $timestamp ) {
        adfoin_webbabookinglite_action_log(
            $record,
            __( 'Service ID and timestamp are required.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $booking_data = array(
        'service_id'        => $service_id,
        'time'              => $timestamp,
        'duration'          => isset( $parsed['duration'] ) ? absint( $parsed['duration'] ) : 60,
        'quantity'          => isset( $parsed['quantity'] ) ? absint( $parsed['quantity'] ) : 1,
        'name'              => isset( $parsed['name'] ) ? sanitize_text_field( $parsed['name'] ) : '',
        'email'             => isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '',
        'phone'             => isset( $parsed['phone'] ) ? sanitize_text_field( $parsed['phone'] ) : '',
        'description'       => isset( $parsed['description'] ) ? sanitize_textarea_field( $parsed['description'] ) : '',
        'time_offset'       => isset( $parsed['time_offset'] ) ? intval( $parsed['time_offset'] ) : 0,
        'service_category'  => isset( $parsed['service_category'] ) ? absint( $parsed['service_category'] ) : 0,
        'locale'            => isset( $parsed['locale'] ) ? sanitize_text_field( $parsed['locale'] ) : '',
        'attachment'        => isset( $parsed['attachment'] ) ? esc_url_raw( $parsed['attachment'] ) : '',
        'extra'             => adfoin_webbabookinglite_prepare_extra( $parsed['extra_json'] ?? '' ),
    );

    $booking_factory = new WBK_Booking_Factory();
    $result = $booking_factory->build_from_array( $booking_data );

    if ( ! is_array( $result ) || true !== $result[0] ) {
        adfoin_webbabookinglite_action_log(
            $record,
            __( 'Failed to create booking.', 'advanced-form-integration' ),
            array(
                'error' => is_array( $result ) ? $result[1] : __( 'Unknown error', 'advanced-form-integration' ),
            ),
            false
        );
        return;
    }

    $booking_id = absint( $result[1] );

    if ( $booking_id ) {
        $booking_factory->post_production( array( $booking_id ), 'on_manual_booking' );
    }

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        adfoin_webbabookinglite_update_status( $booking_id, $parsed['status'] );
    }

    adfoin_webbabookinglite_action_log(
        $record,
        __( 'Booking created successfully.', 'advanced-form-integration' ),
        array(
            'booking_id' => $booking_id,
        ),
        true
    );
}

function adfoin_webbabookinglite_action_update_booking( $record, $parsed ) {
    $booking_id = isset( $parsed['booking_id'] ) ? absint( $parsed['booking_id'] ) : 0;

    if ( ! $booking_id ) {
        adfoin_webbabookinglite_action_log(
            $record,
            __( 'Booking ID is required for update.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $booking = new WBK_Booking( $booking_id );
    if ( ! $booking->is_loaded() ) {
        adfoin_webbabookinglite_action_log(
            $record,
            __( 'Booking could not be found.', 'advanced-form-integration' ),
            array( 'booking_id' => $booking_id ),
            false
        );
        return;
    }

    $fields_updated = false;

    if ( isset( $parsed['service_id'] ) && '' !== $parsed['service_id'] ) {
        $booking->set( 'service_id', absint( $parsed['service_id'] ) );
        $fields_updated = true;
    }

    if ( isset( $parsed['timestamp'] ) && '' !== $parsed['timestamp'] ) {
        $timestamp = intval( $parsed['timestamp'] );
        $booking->set( 'time', $timestamp );
        $booking->set( 'day', strtotime( date( 'Y-m-d', $timestamp ) . ' 00:00:00' ) );
        $fields_updated = true;
    }

    $maybe_update = array(
        'duration'   => 'duration',
        'quantity'   => 'quantity',
        'name'       => 'name',
        'email'      => 'email',
        'phone'      => 'phone',
        'description'=> 'description',
        'time_offset'=> 'time_offset',
        'service_category' => 'service_category',
        'locale'     => 'locale',
        'attachment' => 'attachment',
    );

    foreach ( $maybe_update as $parsed_key => $field ) {
        if ( isset( $parsed[ $parsed_key ] ) && '' !== $parsed[ $parsed_key ] ) {
            $value = $parsed[ $parsed_key ];
            if ( in_array( $field, array( 'duration', 'quantity', 'time_offset', 'service_category' ), true ) ) {
                $value = intval( $value );
            } elseif ( 'attachment' === $field ) {
                $value = esc_url_raw( $value );
            } elseif ( 'email' === $field ) {
                $value = sanitize_email( $value );
            } else {
                $value = sanitize_text_field( $value );
            }
            $booking->set( $field, $value );
            $fields_updated = true;
        }
    }

    if ( isset( $parsed['extra_json'] ) && '' !== $parsed['extra_json'] ) {
        $extra = adfoin_webbabookinglite_prepare_extra( $parsed['extra_json'] );
        if ( false !== $extra ) {
            $booking->set( 'extra', $extra );
            $fields_updated = true;
        }
    }

    if ( $fields_updated ) {
        $booking->save();
    }

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        adfoin_webbabookinglite_update_status( $booking_id, $parsed['status'] );
    }

    adfoin_webbabookinglite_action_log(
        $record,
        __( 'Booking updated successfully.', 'advanced-form-integration' ),
        array(
            'booking_id' => $booking_id,
        ),
        true
    );
}

function adfoin_webbabookinglite_action_delete_booking( $record, $parsed ) {
    $booking_id = isset( $parsed['booking_id'] ) ? absint( $parsed['booking_id'] ) : 0;

    if ( ! $booking_id ) {
        adfoin_webbabookinglite_action_log(
            $record,
            __( 'Booking ID is required for deletion.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $force = isset( $parsed['force_delete'] ) ? adfoin_webbabookinglite_to_bool( $parsed['force_delete'] ) : false;
    $mode  = isset( $parsed['delete_mode'] ) ? sanitize_key( $parsed['delete_mode'] ) : 'auto';

    $factory = new WBK_Booking_Factory();
    if ( 'permanent' === $mode ) {
        $factory->destroy( $booking_id, '', true );
    } elseif ( 'admin' === $mode ) {
        $factory->destroy( $booking_id, 'administrator', $force );
    } elseif ( 'customer' === $mode ) {
        $factory->destroy( $booking_id, 'customer', $force );
    } else {
        $factory->destroy( $booking_id, '', $force );
    }

    adfoin_webbabookinglite_action_log(
        $record,
        __( 'Booking deleted successfully.', 'advanced-form-integration' ),
        array(
            'booking_id' => $booking_id,
        ),
        true
    );
}

function adfoin_webbabookinglite_prepare_extra( $extra_json ) {
    $extra_json = trim( (string) $extra_json );
    if ( '' === $extra_json ) {
        return '';
    }

    $decoded = json_decode( $extra_json );
    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return false;
    }

    if ( ! is_array( $decoded ) ) {
        return false;
    }

    $sanitised = array();
    foreach ( $decoded as $item ) {
        if ( is_array( $item ) ) {
            $sanitised_item = array();
            foreach ( $item as $sub ) {
                if ( is_array( $sub ) ) {
                    $sanitised_item[] = implode( ', ', array_map( 'sanitize_text_field', $sub ) );
                } else {
                    $sanitised_item[] = sanitize_text_field( $sub );
                }
            }
            $sanitised[] = $sanitised_item;
        }
    }

    return json_encode( $sanitised );
}

function adfoin_webbabookinglite_to_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }
    $value = strtolower( trim( (string) $value ) );
    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function adfoin_webbabookinglite_update_status( $booking_id, $status ) {
    $status = sanitize_key( $status );

    $allowed = array( 'pending', 'approved', 'cancelled', 'rejected' );
    if ( ! in_array( $status, $allowed, true ) ) {
        return;
    }

    if ( 'approved' === $status ) {
        $factory = new WBK_Booking_Factory();
        $factory->set_as_approved( array( $booking_id ) );
        return;
    }

    WBK_Model_Utils::set_booking_status( $booking_id, $status );

    if ( 'cancelled' === $status ) {
        WBK_Email_Processor::send( array( $booking_id ), 'booking_cancelled_by_admin' );
    }
}

function adfoin_webbabookinglite_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'webbabookinglite', $log_args, $record );
}

