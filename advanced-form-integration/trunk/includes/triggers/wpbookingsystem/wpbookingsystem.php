<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get WP Booking System Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_wpbookingsystem_get_forms( $form_provider ) {
	if ( $form_provider !== 'wp-booking-system' ) {
		return;
	}

	$triggers = array(
		'newBooking' => __( 'New Booking Submitted', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get WP Booking System Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_wpbookingsystem_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'wp-booking-system' ) {
		return;
	}

	$fields = array();
	if ( $form_id === 'newBooking' ) {
		$fields = array(
			'booking_id'      => __( 'Booking ID', 'advanced-form-integration' ),
			'customer_name'   => __( 'Customer Name', 'advanced-form-integration' ),
			'customer_email'  => __( 'Customer Email', 'advanced-form-integration' ),
			'booking_date'    => __( 'Booking Date', 'advanced-form-integration' ),
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
function adfoin_wpbookingsystem_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );
	if ( $user ) {
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
		$user_data['nickname']   = $user->nickname;
		$user_data['avatar_url'] = get_avatar_url( $user_id );
		$user_data['user_email'] = $user->user_email;
		$user_data['user_id']    = $user_id;
	}
	return $user_data;
}

/**
 * Handle New Booking Submission.
 *
 * This function fires when a booking is submitted via WP Booking System.
 *
 * @param int    $booking_id   The booking ID.
 * @param array  $post_data    The submitted form data.
 * @param object $form         The form object.
 * @param array  $form_args    Additional form arguments.
 * @param array  $form_fields  The form fields.
 */
function adfoin_wpbookingsystem_handle_new_booking( $booking_id, $post_data, $form, $form_args, $form_fields ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'wp-booking-system', 'newBooking' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Retrieve the booking. Assumes wpbs_get_booking() returns a booking object.
	$booking = wpbs_get_booking( $booking_id );
	if ( ! $booking ) {
		return;
	}

	// Retrieve booking fields (adjust the keys as needed).
	$fields = $booking->get( 'fields' );

	$customer_name  = isset( $fields['customer_name'] ) ? $fields['customer_name'] : '';
	$customer_email = isset( $fields['customer_email'] ) ? $fields['customer_email'] : '';
	$booking_date   = isset( $fields['booking_date'] ) ? $fields['booking_date'] : '';

	$posted_data = array(
		'booking_id'     => $booking_id,
		'customer_name'  => $customer_name,
		'customer_email' => $customer_email,
		'booking_date'   => $booking_date,
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpbs_submit_form_after', 'adfoin_wpbookingsystem_handle_new_booking', 10, 5 );