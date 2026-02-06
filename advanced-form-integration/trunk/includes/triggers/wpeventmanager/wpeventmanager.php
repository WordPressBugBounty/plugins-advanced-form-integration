<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get WP Event Manager Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_wpeventmanager_get_forms( $form_provider ) {
	if ( $form_provider !== 'wp-event-manager' ) {
		return;
	}

	$triggers = array(
		'newRegistration' => __( 'New Event Registration', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get WP Event Manager Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_wpeventmanager_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'wp-event-manager' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'newRegistration' ) {
		$fields = array(
			'registration_id'  => __( 'Registration ID', 'advanced-form-integration' ),
			'event_name'       => __( 'Event Name', 'advanced-form-integration' ),
			'event_start_date' => __( 'Event Start Date', 'advanced-form-integration' ),
			'event_start_time' => __( 'Event Start Time', 'advanced-form-integration' ),
			'first_name'       => __( 'Attendee First Name', 'advanced-form-integration' ),
			'last_name'        => __( 'Attendee Last Name', 'advanced-form-integration' ),
			'user_email'       => __( 'Attendee Email', 'advanced-form-integration' ),
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
function adfoin_wpeventmanager_get_userdata( $user_id ) {
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
 * Handle New Event Registration.
 *
 * This function is triggered when an event registration is created,
 * saved, or confirmed. It gathers registration and event data, and then
 * sends it via the integration.
 *
 * @param mixed $registration Registration ID or object.
 * @param mixed $event_id     (Optional) Event ID.
 */
function adfoin_wpeventmanager_handle_registration( $registration, $event_id = 0 ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'wp-event-manager', 'newRegistration' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Determine registration ID and event ID.
	if ( is_object( $registration ) && isset( $registration->ID ) ) {
		$registration_id = $registration->ID;
		if ( empty( $event_id ) && isset( $registration->post_parent ) ) {
			$event_id = $registration->post_parent;
		}
	} else {
		$registration_id = $registration;
	}

	// Both registration and event IDs are required.
	if ( ! $registration_id || ! $event_id ) {
		return;
	}

	// Retrieve registration meta.
	$registration_data = get_post_meta( $registration_id );
	if ( empty( $registration_data ) ) {
		return;
	}

	// Collapse the meta array.
	$registration_data = array_map( function( $item ) {
		return maybe_unserialize( $item[0] );
	}, $registration_data );

	// Gather event details.
	$event_name       = get_the_title( $event_id );
	$event_start_date = get_post_meta( $event_id, '_event_start_date', true );
	$event_start_time = get_post_meta( $event_id, '_event_start_time', true );

	// Extract attendee name (try different keys).
	if ( ! empty( $registration_data['full-name'] ) ) {
		$name_parts = explode( ' ', $registration_data['full-name'] );
	} elseif ( ! empty( $registration_data['attendee_name'] ) ) {
		$name_parts = explode( ' ', $registration_data['attendee_name'] );
	} else {
		$name_parts = explode( ' ', get_the_title( $registration_id ) );
	}

	$first_name = $name_parts[0];
	$last_name  = ( count( $name_parts ) > 1 ) ? implode( ' ', array_slice( $name_parts, 1 ) ) : '';

	// Ensure we have a valid email address.
	if ( empty( $registration_data['user_email'] ) || ! is_email( $registration_data['user_email'] ) ) {
		wpf_log( 'notice', 0, sprintf( 'Unable to sync event registration #%d: no valid email provided.', $registration_id ), array( 'meta_array' => $registration_data ) );
		return;
	}

	$posted_data = array(
		'registration_id'  => $registration_id,
		'event_name'       => $event_name,
		'event_start_date' => $event_start_date,
		'event_start_time' => $event_start_time,
		'first_name'       => $first_name,
		'last_name'        => $last_name,
		'user_email'       => $registration_data['user_email'],
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'new_event_registration', 'adfoin_wpeventmanager_handle_registration', 20, 2 );
add_action( 'event_manager_registrations_save_event_registration', 'adfoin_wpeventmanager_handle_registration', 10, 2 );
add_action( 'waiting_to_confirmed', 'adfoin_wpeventmanager_handle_registration' );