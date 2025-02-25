<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Simply Schedule Appointments Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_simplyscheduleappointments_get_forms( $form_provider ) {
	if ( $form_provider !== 'simplyscheduleappointments' ) {
		return;
	}
	$triggers = array(
		'newBooking' => __( 'New Appointment Booked', 'advanced-form-integration' ),
	);
	return $triggers;
}

/**
 * Get Simply Schedule Appointments Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_simplyscheduleappointments_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'simplyscheduleappointments' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'newBooking' ) {
		$fields = array(
			'appointment_id' => __( 'Appointment ID', 'advanced-form-integration' ),
			'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
			'first_name'     => __( 'First Name', 'advanced-form-integration' ),
			'last_name'      => __( 'Last Name', 'advanced-form-integration' ),
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
function adfoin_simplyscheduleappointments_get_userdata( $user_id ) {
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
 * Handle New Appointment Booked.
 *
 * This function fires when an appointment is booked via Simply Schedule Appointments.
 *
 * @param int   $appointment_id The appointment ID.
 * @param array $data_after     Data after booking.
 * @param array $data_before    Data before booking.
 * @param array $response       Additional response data.
 */
function adfoin_simplyscheduleappointments_handle_new_booking( $appointment_id, $data_after, $data_before, $response ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'simply-schedule-appointments', 'newBooking' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Create the appointment object (provided by Simply Schedule Appointments).
	$appointment = new SSA_Appointment_Object( $appointment_id );
	// Retrieve appointment payload in a format for WP Fusion.
	$data = $appointment->get_webhook_payload( 'wpfusion' );

	// Extract customer information.
	$customer_info = isset( $data['appointment']['customer_information'] ) ? $data['appointment']['customer_information'] : array();
	$name_parts    = isset( $customer_info['Name'] ) ? explode( ' ', $customer_info['Name'] ) : array( '', '' );

	$posted_data = array(
		'appointment_id' => $appointment_id,
		'customer_email' => isset( $customer_info['Email'] ) ? $customer_info['Email'] : '',
		'first_name'     => $name_parts[0],
		'last_name'      => ( count( $name_parts ) > 1 ? $name_parts[1] : '' ),
		// Additional fields from $data or appointment meta can be added here.
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'ssa/appointment/booked', 'adfoin_ssa_handle_new_booking', 10, 4 );
