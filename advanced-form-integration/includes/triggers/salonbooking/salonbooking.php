<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Salon Booking Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_salonbooking_get_forms( $form_provider ) {
	if ( $form_provider !== 'salon-booking' ) {
		return;
	}
	$triggers = array(
		'bookingCreated' => __( 'New Booking Created', 'advanced-form-integration' ),
	);
	return $triggers;
}

/**
 * Get Salon Booking Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_salonbooking_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'salon-booking' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'bookingCreated' ) {
		$fields = array(
			'booking_id'    => __( 'Booking ID', 'advanced-form-integration' ),
			'customer_email'=> __( 'Customer Email', 'advanced-form-integration' ),
			'first_name'    => __( 'First Name', 'advanced-form-integration' ),
			'last_name'     => __( 'Last Name', 'advanced-form-integration' ),
			'address'       => __( 'Address', 'advanced-form-integration' ),
			'phone'         => __( 'Phone Number', 'advanced-form-integration' ),
		);
	}
	return $fields;
}

/**
 * Get basic user data.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_salonbooking_get_userdata( $user_id ) {
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
 * Handle New Booking Created.
 *
 * This function fires when a booking is created in Salon Booking.
 *
 * @param object $booking The booking object.
 */
function adfoin_salonbooking_handle_booking_created( $booking ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'salon-booking', 'bookingCreated' );
	if ( empty( $saved_records ) ) {
		return;
	}

	// Retrieve booking details using methods provided by the Salon Booking plugin.
	$booking_id    = $booking->getId();
	$contact_data  = array(
		'booking_id'    => $booking_id,
		'customer_email'=> $booking->getEmail(),
		'first_name'    => $booking->getFirstName(),
		'last_name'     => $booking->getLastName(),
		'address'       => $booking->getAddress(),
		'phone'         => $booking->getPhone(),
	);

	// Send the data to the CRM.
	$integration->send( $saved_records, $contact_data );
}
add_action( 'sln.booking_builder.create.booking_created', 'adfoin_salonbooking_handle_booking_created' );