<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get LatePoint Triggers.
 *
 * @param string $form_provider The integration provider.
 * @return array|void
 */
function adfoin_latepoint_get_forms( $form_provider ) {
	if ( $form_provider != 'latepoint' ) {
		return;
	}

	$triggers = array(
		'bookingCreated' => __( 'New Booking Created', 'advanced-form-integration' ),
		'bookingUpdated' => __( 'Booking Updated', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get LatePoint Form Fields.
 *
 * @param string $form_provider The integration provider.
 * @param string $form_id       The specific trigger ID.
 * @return array|void
 */
function adfoin_latepoint_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider != 'latepoint' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'bookingCreated' ) {
		$fields = array(
			'booking_id'     => __( 'Booking ID', 'advanced-form-integration' ),
			'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
			'service_name'   => __( 'Service Name', 'advanced-form-integration' ),
			'booking_start'  => __( 'Booking Start Date', 'advanced-form-integration' ),
			'booking_end'    => __( 'Booking End Date', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'bookingUpdated' ) {
		$fields = array(
			'booking_id'     => __( 'Booking ID', 'advanced-form-integration' ),
			'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
			'booking_status' => __( 'Booking Status', 'advanced-form-integration' ),
		);
	}

	return $fields;
}

/**
 * Get User Data.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_latepoint_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );
	if ( $user ) {
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
		$user_data['user_email'] = $user->user_email;
		$user_data['user_id']    = $user_id;
	}
	return $user_data;
}

/**
 * Handle LatePoint Booking Created.
 *
 * This function fires when a new booking is created in LatePoint.
 *
 * @param object $booking The LatePoint booking object.
 */
function adfoin_latepoint_handle_booking_created( $booking ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'latepoint', 'bookingCreated' );
	if ( empty( $saved_records ) ) {
		return;
	}

	// Build the data array from booking details.
	$contact_data = array(
		'booking_id'     => $booking->id,
		'customer_email' => $booking->customer->email,
		'service_name'   => $booking->service->name,
		'booking_start'  => $booking->start_date,
		'booking_end'    => $booking->end_date,
	);

	$integration->send( $saved_records, $contact_data );
}
add_action( 'latepoint_booking_created', 'adfoin_latepoint_handle_booking_created' );

/**
 * Handle LatePoint Booking Updated.
 *
 * This function fires when a booking is updated and its status is either completed or cancelled.
 *
 * @param object $booking The LatePoint booking object.
 */
function adfoin_latepoint_handle_booking_updated( $booking ) {
	if ( $booking->status !== 'completed' && $booking->status !== 'cancelled' ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'latepoint', 'bookingUpdated' );
	if ( empty( $saved_records ) ) {
		return;
	}

	$contact_data = array(
		'booking_id'     => $booking->id,
		'customer_email' => $booking->customer->email,
		'booking_status' => $booking->status,
	);

	$integration->send( $saved_records, $contact_data );
}
add_action( 'latepoint_booking_updated', 'adfoin_latepoint_handle_booking_updated' );