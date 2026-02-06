<?php

add_filter( 'adfoin_action_providers', 'adfoin_appointmenthourbooking_actions', 10, 1 );

function adfoin_appointmenthourbooking_actions( $actions ) {

    $actions['appointmenthourbooking'] = array(
        'title' => __( 'Appointment Hour Booking', 'advanced-form-integration' ),
        'tasks' => array(
            'create_booking' => __( 'Create Booking', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_appointmenthourbooking_action_fields' );

function adfoin_appointmenthourbooking_action_fields() {
    ?>
    <script type="text/template" id="appointmenthourbooking-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_booking'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the calendar ID (form ID) and booking details. Start and end times must be provided using 24-hour format (HH:MM).', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_appointmenthourbooking_job_queue', 'adfoin_appointmenthourbooking_job_queue', 10, 1 );

function adfoin_appointmenthourbooking_job_queue( $data ) {
    adfoin_appointmenthourbooking_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_appointmenthourbooking_send_data( $record, $posted_data ) {
    global $cp_appb_plugin, $wpdb;

    if ( ! isset( $cp_appb_plugin ) || ! is_a( $cp_appb_plugin, 'CP_AppBookingPlugin' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'create_booking' !== $task ) {
        return;
    }

    $calendar_id = ! empty( $field_data['calendar_id'] ) ? adfoin_get_parsed_values( $field_data['calendar_id'], $posted_data ) : '';
    if ( empty( $calendar_id ) ) {
        adfoin_appointmenthourbooking_log( $record, array(
            'success' => false,
            'message' => __( 'Calendar ID is required to create a booking.', 'advanced-form-integration' ),
        ), array() );
        return;
    }

    $calendar_id = absint( $calendar_id );

    $service_field   = ! empty( $field_data['service_field'] ) ? sanitize_text_field( adfoin_get_parsed_values( $field_data['service_field'], $posted_data ) ) : 'fieldname1';
    $service_index   = ! empty( $field_data['service_index'] ) ? intval( adfoin_get_parsed_values( $field_data['service_index'], $posted_data ) ) : 0;
    $service_name    = ! empty( $field_data['service_name'] ) ? sanitize_text_field( adfoin_get_parsed_values( $field_data['service_name'], $posted_data ) ) : '';
    $service_price   = isset( $field_data['service_price'] ) ? floatval( adfoin_get_parsed_values( $field_data['service_price'], $posted_data ) ) : 0;
    $service_duration = isset( $field_data['service_duration'] ) ? intval( adfoin_get_parsed_values( $field_data['service_duration'], $posted_data ) ) : 0;
    $service_sid     = ! empty( $field_data['service_id'] ) ? sanitize_text_field( adfoin_get_parsed_values( $field_data['service_id'], $posted_data ) ) : '';
    $status          = isset( $field_data['status'] ) ? sanitize_text_field( adfoin_get_parsed_values( $field_data['status'], $posted_data ) ) : '';
    $quantity        = isset( $field_data['quantity'] ) ? intval( adfoin_get_parsed_values( $field_data['quantity'], $posted_data ) ) : 1;
    if ( $quantity < 1 ) {
        $quantity = 1;
    }

    $date_input  = isset( $field_data['date'] ) ? adfoin_get_parsed_values( $field_data['date'], $posted_data ) : '';
    $start_input = isset( $field_data['start_time'] ) ? adfoin_get_parsed_values( $field_data['start_time'], $posted_data ) : '';
    $end_input   = isset( $field_data['end_time'] ) ? adfoin_get_parsed_values( $field_data['end_time'], $posted_data ) : '';

    $booking_date = adfoin_appointmenthourbooking_parse_date( $date_input );
    $start_time   = adfoin_appointmenthourbooking_parse_time( $start_input );
    $end_time     = adfoin_appointmenthourbooking_parse_time( $end_input );

    if ( ! $booking_date || ! $start_time || ! $end_time ) {
        adfoin_appointmenthourbooking_log( $record, array(
            'success' => false,
            'message' => __( 'Valid booking date, start time, and end time are required (expected format YYYY-MM-DD and HH:MM).', 'advanced-form-integration' ),
        ), array() );
        return;
    }

    $slot = $start_time . '/' . $end_time;

    $customer_email = isset( $field_data['customer_email'] ) ? sanitize_email( adfoin_get_parsed_values( $field_data['customer_email'], $posted_data ) ) : '';
    $customer_name  = isset( $field_data['customer_name'] ) ? sanitize_text_field( adfoin_get_parsed_values( $field_data['customer_name'], $posted_data ) ) : '';
    $customer_phone = isset( $field_data['customer_phone'] ) ? sanitize_text_field( adfoin_get_parsed_values( $field_data['customer_phone'], $posted_data ) ) : '';
    $notes          = isset( $field_data['notes'] ) ? sanitize_textarea_field( adfoin_get_parsed_values( $field_data['notes'], $posted_data ) ) : '';

    $original_calendar = $cp_appb_plugin->getId();
    $cp_appb_plugin->setId( $calendar_id );

    $form_structure_raw = $cp_appb_plugin->get_option( 'form_structure', CP_APPBOOK_DEFAULT_form_structure );
    $form_structure     = json_decode( $cp_appb_plugin->cleanJSON( $form_structure_raw ) );

    if ( ! $service_name || ! $service_duration || ! $service_price || ! $service_sid ) {
        list( $service_name, $service_duration, $service_price, $service_sid ) = adfoin_appointmenthourbooking_guess_service_details(
            $form_structure,
            $service_field,
            $service_index,
            $service_name,
            $service_duration,
            $service_price,
            $service_sid
        );
    }

    $service_name = sanitize_text_field( $service_name );
    $service_sid  = sanitize_text_field( $service_sid );
    $service_duration = intval( $service_duration );
    $service_price    = floatval( $service_price );
    if ( ! $service_name ) {
        $service_name = __( 'Service', 'advanced-form-integration' );
    }

    $apps = array(
        array(
            'id'           => 1,
            'cancelled'    => $status,
            'serviceindex' => $service_index,
            'service'      => $service_name,
            'duration'     => $service_duration,
            'price'        => $service_price,
            'date'         => $booking_date,
            'slot'         => $slot,
            'military'     => 1,
            'field'        => $service_field,
            'quant'        => $quantity,
            'sid'          => $service_sid,
        ),
    );

    $final_price = number_format( (float) $service_price * $quantity, 2, '.', '' );

    $params = array(
        'final_price'        => $final_price,
        'final_price_short'  => number_format( (float) $service_price * $quantity, 0 ),
        'request_timestamp'  => $cp_appb_plugin->format_date( date( 'Y-m-d', current_time( 'timestamp' ) ) ) . ' ' . date( 'H:i:s', current_time( 'timestamp' ) ),
        'apps'               => $apps,
        'formid'             => $calendar_id,
        'formname'           => $cp_appb_plugin->get_option( 'form_name', 'Booking Form' ),
        'referrer'           => '',
        'email'              => $customer_email,
        'customer_email'     => $customer_email,
        'customer_name'      => $customer_name,
        'customer_phone'     => $customer_phone,
        'notes'              => $notes,
        'app_service_1'      => $service_name,
        'app_status_1'       => $status,
        'app_duration_1'     => $service_duration,
        'app_price_1'        => $service_price,
        'app_date_1'         => $cp_appb_plugin->format_date( $booking_date ),
        'app_slot_1'         => str_replace( '/', ' - ', $slot ),
        'app_starttime_1'    => $start_time,
        'app_endtime_1'      => $end_time,
        'app_quantity_1'     => $quantity,
    );

    $buffer  = __( 'Appointments', 'appointment-hour-booking' ) . ":\n" . $cp_appb_plugin->get_appointments_text( $apps ) . "\n";
    if ( $customer_name ) {
        $buffer .= __( 'Name', 'appointment-hour-booking' ) . ': ' . $customer_name . "\n";
    }
    if ( $customer_email ) {
        $buffer .= __( 'Email', 'appointment-hour-booking' ) . ': ' . $customer_email . "\n";
    }
    if ( $customer_phone ) {
        $buffer .= __( 'Phone', 'appointment-hour-booking' ) . ': ' . $customer_phone . "\n";
    }
    if ( $notes ) {
        $buffer .= __( 'Notes', 'appointment-hour-booking' ) . ': ' . $notes . "\n";
    }

    do_action( 'cpappb_process_data_before_insert', $params );

    if ( ! $cp_appb_plugin->performAdvancedDoubleBookingVerification( $params ) ) {
        $cp_appb_plugin->setId( $original_calendar );
        adfoin_appointmenthourbooking_log( $record, array(
            'success' => false,
            'message' => __( 'Selected slot is no longer available.', 'advanced-form-integration' ),
        ), $params );
        return;
    }

    $current_user = wp_get_current_user();

    $cp_appb_plugin->add_field_verify( $wpdb->prefix . $cp_appb_plugin->table_messages, 'whoadded' );

    $notify_email = $customer_email;
    if ( empty( $notify_email ) ) {
        $default_destination = $cp_appb_plugin->get_option( 'fp_destination_emails', get_option( 'admin_email' ) );
        if ( is_string( $default_destination ) ) {
            $dest_parts    = array_map( 'trim', explode( ',', $default_destination ) );
            $notify_email = sanitize_email( reset( $dest_parts ) );
        } elseif ( is_array( $default_destination ) && ! empty( $default_destination ) ) {
            $notify_email = sanitize_email( reset( $default_destination ) );
        }
    }

    $rows_affected = $wpdb->insert(
        $wpdb->prefix . $cp_appb_plugin->table_messages,
        array(
            'formid'      => $calendar_id,
            'time'        => current_time( 'mysql' ),
            'ipaddr'      => get_option( 'cp_cpappb_storeip', CP_APPBOOK_DEFAULT_track_IP ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) : '',
            'notifyto'    => $notify_email,
            'posted_data' => serialize( $params ),
            'data'        => $buffer,
            'whoadded'    => (string) $current_user->ID,
        )
    );

    if ( ! $rows_affected ) {
        $cp_appb_plugin->setId( $original_calendar );
        adfoin_appointmenthourbooking_log( $record, array(
            'success' => false,
            'message' => __( 'Failed to save booking in database.', 'advanced-form-integration' ),
        ), $params );
        return;
    }

    $item_number          = $wpdb->insert_id;
    $params['itemnumber'] = $item_number;

    do_action_ref_array( 'cpappb_process_data', array( &$params ) );

    $wpdb->update(
        $wpdb->prefix . $cp_appb_plugin->table_messages,
        array( 'posted_data' => serialize( $params ) ),
        array( 'id' => $item_number ),
        array( '%s' ),
        array( '%d' )
    );

    $cp_appb_plugin->ready_to_go_reservation( $item_number, '' );

    $cp_appb_plugin->setId( $original_calendar );

    adfoin_appointmenthourbooking_log( $record, array(
        'success' => true,
        'message' => __( 'Booking created successfully.', 'advanced-form-integration' ),
        'id'      => $item_number,
    ), $params );
}

function adfoin_appointmenthourbooking_guess_service_details( $form_structure, $service_field, $service_index, $service_name, $service_duration, $service_price, $service_sid ) {
    if ( is_array( $form_structure ) && isset( $form_structure[0] ) && is_array( $form_structure[0] ) ) {
        foreach ( $form_structure[0] as $field ) {
            if ( isset( $field->ftype ) && 'fapp' === $field->ftype && ( empty( $service_field ) || $field->name === $service_field ) ) {
                if ( isset( $field->services ) && is_array( $field->services ) ) {
                    if ( ! isset( $field->services[ $service_index ] ) ) {
                        $service_index = 0;
                    }
                    if ( isset( $field->services[ $service_index ] ) ) {
                        $service_obj = $field->services[ $service_index ];
                        if ( ! $service_name && isset( $service_obj->name ) ) {
                            $service_name = $service_obj->name;
                        }
                        if ( ! $service_duration && isset( $service_obj->duration ) ) {
                            $service_duration = intval( $service_obj->duration );
                        }
                        if ( ! $service_price && isset( $service_obj->price ) ) {
                            $service_price = floatval( $service_obj->price );
                        }
                        if ( ! $service_sid && isset( $service_obj->idx ) ) {
                            $service_sid = $service_obj->idx;
                        }
                    }
                }
                break;
            }
        }
    }

    return array( $service_name, $service_duration, $service_price, $service_sid );
}

function adfoin_appointmenthourbooking_parse_date( $date_str ) {
    if ( empty( $date_str ) ) {
        return '';
    }

    $timestamp = strtotime( $date_str );
    if ( ! $timestamp ) {
        return '';
    }

    return date( 'Y-m-d', $timestamp );
}

function adfoin_appointmenthourbooking_parse_time( $time_str ) {
    if ( empty( $time_str ) ) {
        return '';
    }

    $time_str = trim( $time_str );

    if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $time_str, $matches ) ) {
        return sprintf( '%02d:%02d', $matches[1], $matches[2] );
    }

    $timestamp = strtotime( $time_str );
    if ( ! $timestamp ) {
        return '';
    }

    return date( 'H:i', $timestamp );
}

function adfoin_appointmenthourbooking_log( $record, $response_body, $request_payload ) {
    $log_response = array(
        'response' => array(
            'code'    => ! empty( $response_body['success'] ) ? 200 : 400,
            'message' => isset( $response_body['message'] ) ? $response_body['message'] : '',
        ),
        'body'     => $response_body,
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    adfoin_add_to_log( $log_response, 'appointmenthourbooking', $log_args, $record );
}
