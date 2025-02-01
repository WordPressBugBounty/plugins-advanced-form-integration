<?php

// Get FluentBooking Triggers
function adfoin_fluentbooking_get_forms( $form_provider ) {
    if ( $form_provider != 'fluentbooking' ) {
        return;
    }

    $triggers = array(
        'bookingScheduled' => __( 'Booking Scheduled', 'advanced-form-integration' ),
        'bookingCompleted' => __( 'Booking Completed', 'advanced-form-integration' ),
        'bookingCancelled' => __( 'Booking Cancelled', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get FluentBooking Fields
function adfoin_fluentbooking_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'fluentbooking' ) {
        return;
    }

    $fields = array();

    if ( in_array( $form_id, ['bookingScheduled', 'bookingCompleted', 'bookingCancelled'], true ) ) {
        $fields = [
            'id' => __( 'Booking ID', 'advanced-form-integration' ),
            'calendar_id' => __( 'Calendar ID', 'advanced-form-integration' ),
            'event_id' => __( 'Event ID', 'advanced-form-integration' ),
            'person_time_zone' => __( 'Person Time Zone', 'advanced-form-integration' ),
            'location_type' => __( 'Location Type', 'advanced-form-integration' ),
            'location_description' => __( 'Location Description', 'advanced-form-integration' ),
            'start_time' => __( 'Start Time', 'advanced-form-integration' ),
            'end_time' => __( 'End Time', 'advanced-form-integration' ),
            'slot_minutes' => __( 'Slot Minutes', 'advanced-form-integration' ),
            'status' => __( 'Status', 'advanced-form-integration' ),
            'event_type' => __( 'Event Type', 'advanced-form-integration' ),
            'cancelled_by' => __( 'Cancelled By', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Hooks for FluentBooking Actions
add_action( 'fluent_booking/after_booking_scheduled', 'adfoin_fluentbooking_handle_booking_scheduled', 10, 2 );
add_action( 'fluent_booking/booking_schedule_completed', 'adfoin_fluentbooking_handle_booking_completed', 10, 2 );
add_action( 'fluent_booking/booking_schedule_cancelled', 'adfoin_fluentbooking_handle_booking_cancelled', 10, 2 );

// Handle Booking Scheduled
function adfoin_fluentbooking_handle_booking_scheduled( $booking, $calendarSlot ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'fluentbooking', 'bookingScheduled' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_fluentbooking_format_booking_data( $booking, $calendarSlot );
    $integration->send( $saved_records, $posted_data );
}

// Handle Booking Completed
function adfoin_fluentbooking_handle_booking_completed( $booking, $calendarSlot ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'fluentbooking', 'bookingCompleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_fluentbooking_format_booking_data( $booking, $calendarSlot );
    $integration->send( $saved_records, $posted_data );
}

// Handle Booking Cancelled
function adfoin_fluentbooking_handle_booking_cancelled( $booking, $calendarSlot ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'fluentbooking', 'bookingCancelled' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_fluentbooking_format_booking_data( $booking, $calendarSlot );
    $integration->send( $saved_records, $posted_data );
}

// Format Booking Data
function adfoin_fluentbooking_format_booking_data( $booking, $calendarSlot ) {
    $formData = array(
        'id' => isset($booking['id']) ? $booking['id'] : '',
        'calendar_id' => isset($booking['calendar_id']) ? $booking['calendar_id'] : '',
        'event_id' => isset($booking['event_id']) ? $booking['event_id'] : '',
        'person_time_zone' => isset($booking['person_time_zone']) ? $booking['person_time_zone'] : '',
        'location_type' => isset($booking['location_details']['type']) ? $booking['location_details']['type'] : '',
        'location_description' => isset($booking['location_details']['description']) ? $booking['location_details']['description'] : '',
        'start_time' => isset($calendarSlot['start_time']) ? $calendarSlot['start_time'] : '',
        'end_time' => isset($calendarSlot['end_time']) ? $calendarSlot['end_time'] : '',
        'slot_minutes' => isset($calendarSlot['slot_minutes']) ? $calendarSlot['slot_minutes'] : '',
        'status' => isset($booking['status']) ? $booking['status'] : '',
        'event_type' => isset($calendarSlot['event_type']) ? $calendarSlot['event_type'] : '',
        'cancelled_by' => isset($booking['cancelled_by']) ? $booking['cancelled_by'] : '',
    );

    return $formData;
}