<?php

// Get WP Booking Calendar Triggers
function adfoin_wpbookingcalendar_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpbookingcalendar' ) {
        return;
    }

    $triggers = array(
        'bookingCancelled' => __( 'Booking Cancelled', 'advanced-form-integration' ),
        'bookingApproved' => __( 'Booking Approved', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get WP Booking Calendar Fields
function adfoin_wpbookingcalendar_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpbookingcalendar' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'bookingCancelled' ) {
        $fields = [
            'booking_id' => __( 'Booking ID', 'advanced-form-integration' ),
            'booking_status' => __( 'Booking Status', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'bookingApproved' ) {
        $fields = [
            'booking_id' => __( 'Booking ID', 'advanced-form-integration' ),
            'booking_status' => __( 'Booking Status', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_wpbookingcalendar_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_id'] = $user_id;
        $user_data['user_email'] = $user->user_email;
    }

    return $user_data;
}

// Send Data
function adfoin_wpbookingcalendar_send_trigger_data( $saved_records, $posted_data ) {
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

// Handle Booking Cancelled
add_action( 'wpbc_move_booking_to_trash', 'adfoin_wpbookingcalendar_handle_booking_cancelled', 10, 2 );
function adfoin_wpbookingcalendar_handle_booking_cancelled( $params, $action_result ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpbookingcalendar', 'bookingCancelled' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $booking_id = $params['booking_id'];
    $user_id = get_current_user_id();

    if ( ! $action_result['after_action_result'] || $user_id === 0 ) {
        return;
    }

    $posted_data = [
        'booking_id' => $booking_id,
        'booking_status' => 'Cancelled',
    ];

    $user_data = adfoin_wpbookingcalendar_get_userdata( $user_id );
    $posted_data = array_merge( $posted_data, $user_data );

    $posted_data['post_id'] = $booking_id;

    adfoin_wpbookingcalendar_send_trigger_data( $saved_records, $posted_data );
}

// Handle Booking Approved
add_action( 'wpbc_set_booking_approved', 'adfoin_wpbookingcalendar_handle_booking_approved', 10, 2 );
function adfoin_wpbookingcalendar_handle_booking_approved( $params, $action_result ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpbookingcalendar', 'bookingApproved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $booking_id = $params['booking_id'];
    $user_id = get_current_user_id();

    if ( ! $action_result['after_action_result'] || $user_id === 0 ) {
        return;
    }

    $posted_data = [
        'booking_id' => $booking_id,
        'booking_status' => 'Approved',
    ];

    $user_data = adfoin_wpbookingcalendar_get_userdata( $user_id );
    $posted_data = array_merge( $posted_data, $user_data );

    $posted_data['post_id'] = $booking_id;

    adfoin_wpbookingcalendar_send_trigger_data( $saved_records, $posted_data );
}