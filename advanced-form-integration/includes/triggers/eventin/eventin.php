<?php

// Get Eventin Triggers
function adfoin_eventin_get_forms( $form_provider ) {
    if ( $form_provider != 'eventin' ) {
        return;
    }

    $triggers = array(
        'eventCreated' => __( 'Event Created', 'advanced-form-integration' ),
        'eventUpdated' => __( 'Event Updated', 'advanced-form-integration' ),
        'eventDeleted' => __( 'Event Deleted', 'advanced-form-integration' ),
        'speakerCreated' => __( 'Speaker/Organizer Created', 'advanced-form-integration' ),
        'speakerUpdated' => __( 'Speaker/Organizer Updated', 'advanced-form-integration' ),
        'speakerDeleted' => __( 'Speaker/Organizer Deleted', 'advanced-form-integration' ),
        'attendeeUpdated' => __( 'Attendee Updated', 'advanced-form-integration' ),
        'attendeeDeleted' => __( 'Attendee Deleted', 'advanced-form-integration' ),
        'orderCreated' => __( 'Order Created', 'advanced-form-integration' ),
        'orderDeleted' => __( 'Order Deleted', 'advanced-form-integration' ),
        'scheduleDeleted' => __( 'Schedule Deleted', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Eventin Fields
function adfoin_eventin_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'eventin' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'eventCreated' || $form_id === 'eventUpdated' ) {
        $fields = [
            'title' => __( 'Event Title', 'advanced-form-integration' ),
            'start_date' => __( 'Start Date', 'advanced-form-integration' ),
            'end_date' => __( 'End Date', 'advanced-form-integration' ),
            'start_time' => __( 'Start Time', 'advanced-form-integration' ),
            'end_time' => __( 'End Time', 'advanced-form-integration' ),
            'event_type' => __( 'Event Type', 'advanced-form-integration' ),
            'address' => __( 'Address', 'advanced-form-integration' ),
            'integration' => __( 'Integration', 'advanced-form-integration' ),
            'custom_url' => __( 'Custom URL', 'advanced-form-integration' ),
            'timezone' => __( 'Timezone', 'advanced-form-integration' ),
        ];

        if ( $form_id === 'eventUpdated' ) {
            $fields['status'] = __( 'Event Status', 'advanced-form-integration' );
        }
    } elseif ( $form_id === 'speakerCreated' || $form_id === 'speakerUpdated' ) {
        $fields = [
            'name' => __( 'Name', 'advanced-form-integration' ),
            'category' => __( 'Category', 'advanced-form-integration' ),
            'designation' => __( 'Designation', 'advanced-form-integration' ),
            'email' => __( 'Email', 'advanced-form-integration' ),
            'summary' => __( 'Summary', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'orderCreated' ) {
        $fields = [
            'order_id' => __( 'Order ID', 'advanced-form-integration' ),
            'customer_name' => __( 'Customer Name', 'advanced-form-integration' ),
            'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
            'status' => __( 'Order Status', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'attendeeUpdated' ) {
        $fields = [
            'attendee_id' => __( 'Attendee ID', 'advanced-form-integration' ),
            'attendee_name' => __( 'Attendee Name', 'advanced-form-integration' ),
            'attendee_email' => __( 'Attendee Email', 'advanced-form-integration' ),
            'ticket_status' => __( 'Ticket Status', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

add_action( 'eventin_order_created', 'adfoin_eventin_handle_order_created', 10, 1 );
// Handle Order Created
function adfoin_eventin_handle_order_created( $order ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'orderCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'order_id' => $order->get_id(),
        'customer_name' => $order->get_customer_name(),
        'customer_email' => $order->get_customer_email(),
        'status' => $order->get_status(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_attendee_updated', 'adfoin_eventin_handle_attendee_updated', 10, 2 );
// Handle Attendee Updated
function adfoin_eventin_handle_attendee_updated( $attendee, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'attendeeUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'attendee_id' => $attendee->get_id(),
        'attendee_name' => $attendee->get_name(),
        'attendee_email' => $attendee->get_email(),
        'ticket_status' => $attendee->get_ticket_status(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_event_created', 'adfoin_eventin_handle_event_created', 10, 2 );
// Handle Event Created
function adfoin_eventin_handle_event_created( $event, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'title' => $event->get_title(),
        'start_date' => $event->etn_start_date,
        'end_date' => $event->etn_end_date,
        'start_time' => $event->etn_start_time,
        'end_time' => $event->etn_end_time,
        'event_type' => $event->event_type,
        'address' => $event->get_address(),
        'integration' => $event->get_meeting_platform(),
        'custom_url' => $event->external_link,
        'timezone' => $event->get_timezone(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_event_updated', 'adfoin_eventin_handle_event_updated', 10, 2 );
// Handle Event Updated
function adfoin_eventin_handle_event_updated( $event, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'title' => $event->get_title(),
        'start_date' => $event->etn_start_date,
        'end_date' => $event->etn_end_date,
        'start_time' => $event->etn_start_time,
        'end_time' => $event->etn_end_time,
        'event_type' => $event->event_type,
        'address' => $event->get_address(),
        'integration' => $event->get_meeting_platform(),
        'custom_url' => $event->external_link,
        'timezone' => $event->get_timezone(),
        'status' => $event->get_status(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_event_deleted', 'adfoin_eventin_handle_event_deleted', 10, 1 );
// Handle Event Deleted
function adfoin_eventin_handle_event_deleted( $event_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventDeleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [ 'event_id' => $event_id ];
    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_speaker_created', 'adfoin_eventin_handle_speaker_created', 10, 2 );
// Handle Speaker Created
function adfoin_eventin_handle_speaker_created( $speaker, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'speakerCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'name' => $speaker->get_name(),
        'category' => $speaker->get_category(),
        'designation' => $speaker->get_designation(),
        'email' => $speaker->get_email(),
        'summary' => $speaker->get_summary(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_speaker_updated', 'adfoin_eventin_handle_speaker_updated', 10, 2 );
// Handle Speaker Updated
function adfoin_eventin_handle_speaker_updated( $speaker, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'speakerUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'name' => $speaker->get_name(),
        'category' => $speaker->get_category(),
        'designation' => $speaker->get_designation(),
        'email' => $speaker->get_email(),
        'summary' => $speaker->get_summary(),
    ];

    $integration->send( $saved_records, $posted_data );
}