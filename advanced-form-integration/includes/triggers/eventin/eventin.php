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

// Send Trigger Data
function adfoin_eventin_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

// Hooks for Eventin Actions
add_action( 'eventin_event_created', 'adfoin_eventin_handle_event_created', 10, 2 );
add_action( 'eventin_event_updated', 'adfoin_eventin_handle_event_updated', 10, 2 );
add_action( 'eventin_event_deleted', 'adfoin_eventin_handle_event_deleted', 10, 1 );
add_action( 'eventin_speaker_created', 'adfoin_eventin_handle_speaker_created', 10, 2 );
add_action( 'eventin_speaker_updated', 'adfoin_eventin_handle_speaker_updated', 10, 2 );
add_action( 'eventin_order_created', 'adfoin_eventin_handle_order_created', 10, 1 );
add_action( 'eventin_attendee_updated', 'adfoin_eventin_handle_attendee_updated', 10, 2 );

// Handle Event Created
function adfoin_eventin_handle_event_created( $event, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = $event; // Format the event data as needed.
    adfoin_eventin_send_trigger_data( $saved_records, $posted_data );
}

// Handle Event Updated
function adfoin_eventin_handle_event_updated( $event, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = $event; // Format the updated event data as needed.
    adfoin_eventin_send_trigger_data( $saved_records, $posted_data );
}

// Handle Event Deleted
function adfoin_eventin_handle_event_deleted( $event_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventDeleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [ 'event_id' => $event_id ];
    adfoin_eventin_send_trigger_data( $saved_records, $posted_data );
}