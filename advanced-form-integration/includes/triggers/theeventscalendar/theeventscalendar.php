<?php

// Get The Events Calendar Triggers
function adfoin_theeventscalendar_get_forms( $form_provider ) {
    if ( $form_provider != 'theeventscalendar' ) {
        return;
    }

    $triggers = array(
        'confirmRSVP' => __( 'Confirm RSVP for an Event', 'advanced-form-integration' ),
        'purchaseTicket' => __( 'Purchase Ticket for an Event', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get The Events Calendar Fields
function adfoin_theeventscalendar_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'theeventscalendar' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'confirmRSVP' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'event_id' => __( 'Event ID', 'advanced-form-integration' ),
            'event_name' => __( 'Event Name', 'advanced-form-integration' ),
            'ticket_id' => __( 'Ticket ID', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'purchaseTicket' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'event_id' => __( 'Event ID', 'advanced-form-integration' ),
            'event_name' => __( 'Event Name', 'advanced-form-integration' ),
            'ticket_id' => __( 'Ticket ID', 'advanced-form-integration' ),
            'order_id' => __( 'Order ID', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle RSVP Confirmation
function adfoin_theeventscalendar_handle_rsvp( $product_id, $order_id, $qty ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'theeventscalendar', 'confirmRSVP' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $attendees = tribe_tickets_get_attendees( $order_id, 'rsvp_order' );

    if ( empty( $attendees ) ) {
        return;
    }

    foreach ( $attendees as $attendee ) {
        $user_id = absint( $attendee['user_id'] );
        $event_id = absint( $attendee['event_id'] );
        $ticket_id = absint( $attendee['ticket_id'] );

        if ( $attendee['order_status'] === 'yes' ) {
            $event_name = get_the_title( $event_id );

            $posted_data = array(
                'user_id' => $user_id,
                'event_id' => $event_id,
                'event_name' => $event_name,
                'ticket_id' => $ticket_id,
            );

            adfoin_theeventscalendar_send_trigger_data( $saved_records, $posted_data );
        }
    }
}

add_action( 'event_tickets_rsvp_tickets_generated_for_product', 'adfoin_theeventscalendar_handle_rsvp', 10, 3 );

// Handle Ticket Purchase
function adfoin_theeventscalendar_handle_ticket_purchase( $product_id, $order_id, $qty ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'theeventscalendar', 'purchaseTicket' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $attendees = tribe_tickets_get_attendees( $order_id );

    if ( empty( $attendees ) ) {
        return;
    }

    foreach ( $attendees as $attendee ) {
        $user_id = absint( $attendee['user_id'] );
        $event_id = absint( $attendee['event_id'] );
        $ticket_id = absint( $attendee['ticket_id'] );

        $event_name = get_the_title( $event_id );

        $posted_data = array(
            'user_id' => $user_id,
            'event_id' => $event_id,
            'event_name' => $event_name,
            'ticket_id' => $ticket_id,
            'order_id' => $order_id,
        );

        adfoin_theeventscalendar_send_trigger_data( $saved_records, $posted_data );
    }
}

add_action( 'event_tickets_woocommerce_tickets_generated_for_product', 'adfoin_theeventscalendar_handle_ticket_purchase', 10, 3 );

// Send Trigger Data
function adfoin_theeventscalendar_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data
                )
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}
