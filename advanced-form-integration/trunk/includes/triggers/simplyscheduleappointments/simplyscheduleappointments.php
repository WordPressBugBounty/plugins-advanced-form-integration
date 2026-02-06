<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Simply Schedule Appointments triggers.
 *
 * @param string $form_provider Integration key.
 * @return array<string,string>|void
 */
function adfoin_simplyscheduleappointments_get_forms( $form_provider ) {
	if ( $form_provider !== 'simplyscheduleappointments' ) {
		return;
	}

	return array(
		'appointmentBooked'         => __( 'Appointment Booked', 'advanced-form-integration' ),
		'appointmentPending'        => __( 'Appointment Pending', 'advanced-form-integration' ),
		'appointmentRescheduled'    => __( 'Appointment Rescheduled', 'advanced-form-integration' ),
		'appointmentCancelled'      => __( 'Appointment Cancelled', 'advanced-form-integration' ),
		'appointmentEdited'         => __( 'Appointment Edited', 'advanced-form-integration' ),
		'appointmentCustomerUpdated'=> __( 'Customer Information Updated', 'advanced-form-integration' ),
		'appointmentAbandoned'      => __( 'Appointment Abandoned', 'advanced-form-integration' ),
		'appointmentNoShow'         => __( 'Appointment Marked as No Show', 'advanced-form-integration' ),
		'appointmentNoShowReverted' => __( 'Appointment No Show Reverted', 'advanced-form-integration' ),
	);
}

/**
 * Get Simply Schedule Appointments mapped fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger identifier.
 * @return array<string,string>|void
 */
function adfoin_simplyscheduleappointments_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'simplyscheduleappointments' ) {
		return;
	}

	$fields = array(
		'trigger'                         => __( 'Trigger', 'advanced-form-integration' ),
		'appointment_id'                  => __( 'Appointment ID', 'advanced-form-integration' ),
		'appointment_status'              => __( 'Appointment Status', 'advanced-form-integration' ),
		'appointment_status_label'        => __( 'Appointment Status Label', 'advanced-form-integration' ),
		'previous_status'                 => __( 'Previous Status', 'advanced-form-integration' ),
		'previous_status_label'           => __( 'Previous Status Label', 'advanced-form-integration' ),
		'appointment_type_id'             => __( 'Appointment Type ID', 'advanced-form-integration' ),
		'appointment_type_title'          => __( 'Appointment Type Title', 'advanced-form-integration' ),
		'appointment_type_slug'           => __( 'Appointment Type Slug', 'advanced-form-integration' ),
		'rescheduled_from_appointment_id' => __( 'Rescheduled From Appointment ID', 'advanced-form-integration' ),
		'rescheduled_to_appointment_id'   => __( 'Rescheduled To Appointment ID', 'advanced-form-integration' ),
		'group_id'                        => __( 'Group ID', 'advanced-form-integration' ),
		'author_id'                       => __( 'Author ID', 'advanced-form-integration' ),
		'customer_id'                     => __( 'Customer ID', 'advanced-form-integration' ),
		'customer_name'                   => __( 'Customer Name', 'advanced-form-integration' ),
		'customer_email'                  => __( 'Customer Email', 'advanced-form-integration' ),
		'customer_phone'                  => __( 'Customer Phone', 'advanced-form-integration' ),
		'customer_timezone'               => __( 'Customer Timezone', 'advanced-form-integration' ),
		'customer_locale'                 => __( 'Customer Locale', 'advanced-form-integration' ),
		'customer_information_json'       => __( 'Customer Information (JSON)', 'advanced-form-integration' ),
		'customer_information_plain'      => __( 'Customer Information (Text)', 'advanced-form-integration' ),
		'start_date'                      => __( 'Start Date', 'advanced-form-integration' ),
		'end_date'                        => __( 'End Date', 'advanced-form-integration' ),
		'start_date_timestamp'            => __( 'Start Date Timestamp', 'advanced-form-integration' ),
		'end_date_timestamp'              => __( 'End Date Timestamp', 'advanced-form-integration' ),
		'previous_start_date'             => __( 'Previous Start Date', 'advanced-form-integration' ),
		'previous_end_date'               => __( 'Previous End Date', 'advanced-form-integration' ),
		'date_created'                    => __( 'Created At', 'advanced-form-integration' ),
		'date_modified'                   => __( 'Updated At', 'advanced-form-integration' ),
		'expiration_date'                 => __( 'Expiration Date', 'advanced-form-integration' ),
		'appointment_title'               => __( 'Appointment Title', 'advanced-form-integration' ),
		'appointment_description'         => __( 'Appointment Description', 'advanced-form-integration' ),
		'payment_method'                  => __( 'Payment Method', 'advanced-form-integration' ),
		'payment_received'                => __( 'Payment Received Amount', 'advanced-form-integration' ),
		'google_calendar_id'              => __( 'Google Calendar ID', 'advanced-form-integration' ),
		'google_calendar_event_id'        => __( 'Google Calendar Event ID', 'advanced-form-integration' ),
		'web_meeting_id'                  => __( 'Web Meeting ID', 'advanced-form-integration' ),
		'web_meeting_url'                 => __( 'Web Meeting URL', 'advanced-form-integration' ),
		'web_meeting_password'            => __( 'Web Meeting Password', 'advanced-form-integration' ),
		'allow_sms'                       => __( 'Allow SMS', 'advanced-form-integration' ),
		'staff_ids_json'                  => __( 'Staff IDs (JSON)', 'advanced-form-integration' ),
		'primary_staff_id'                => __( 'Primary Staff ID', 'advanced-form-integration' ),
		'public_edit_url'                 => __( 'Public Edit URL', 'advanced-form-integration' ),
		'meta_key'                        => __( 'Event Meta Key', 'advanced-form-integration' ),
		'meta_value'                      => __( 'Event Meta Value', 'advanced-form-integration' ),
		'meta_raw'                        => __( 'Event Meta (JSON)', 'advanced-form-integration' ),
	);

	return $fields;
}

add_action( 'ssa/appointment/booked', 'adfoin_simplyscheduleappointments_handle_booked', 10, 4 );
add_action( 'ssa/appointment/pending', 'adfoin_simplyscheduleappointments_handle_pending', 10, 4 );
add_action( 'ssa/appointment/rescheduled', 'adfoin_simplyscheduleappointments_handle_rescheduled', 10, 4 );
add_action( 'ssa/appointment/canceled', 'adfoin_simplyscheduleappointments_handle_cancelled', 10, 4 );
add_action( 'ssa/appointment/edited', 'adfoin_simplyscheduleappointments_handle_edited', 10, 4 );
add_action( 'ssa/appointment/customer_information_edited', 'adfoin_simplyscheduleappointments_handle_customer_updated', 10, 4 );
add_action( 'ssa/appointment/abandoned', 'adfoin_simplyscheduleappointments_handle_abandoned', 10, 4 );
add_action( 'ssa/appointment/no_show', 'adfoin_simplyscheduleappointments_handle_no_show', 10, 3 );
add_action( 'ssa/appointment/no_show_reverted', 'adfoin_simplyscheduleappointments_handle_no_show_reverted', 10, 3 );

/**
 * SSA appointment booked.
 */
function adfoin_simplyscheduleappointments_handle_booked( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentBooked',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment pending.
 */
function adfoin_simplyscheduleappointments_handle_pending( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentPending',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment rescheduled.
 */
function adfoin_simplyscheduleappointments_handle_rescheduled( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentRescheduled',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment cancelled.
 */
function adfoin_simplyscheduleappointments_handle_cancelled( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentCancelled',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment edited (non-status change).
 */
function adfoin_simplyscheduleappointments_handle_edited( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentEdited',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment customer information edited.
 */
function adfoin_simplyscheduleappointments_handle_customer_updated( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentCustomerUpdated',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment abandoned.
 */
function adfoin_simplyscheduleappointments_handle_abandoned( $appointment_id, $data_after, $data_before = array(), $response = null ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentAbandoned',
		$appointment_id,
		$data_after,
		$data_before
	);
}

/**
 * SSA appointment marked as no-show.
 */
function adfoin_simplyscheduleappointments_handle_no_show( $appointment_id, $data_after, $meta ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentNoShow',
		$appointment_id,
		$data_after,
		array(),
		array( 'meta' => $meta )
	);
}

/**
 * SSA appointment no-show reverted.
 */
function adfoin_simplyscheduleappointments_handle_no_show_reverted( $appointment_id, $data_after, $meta ) {
	adfoin_simplyscheduleappointments_process_trigger(
		'appointmentNoShowReverted',
		$appointment_id,
		$data_after,
		array(),
		array( 'meta' => $meta )
	);
}

/**
 * Dispatch SSA triggers to Advanced Form Integration.
 *
 * @param string $trigger        Trigger key.
 * @param int    $appointment_id Appointment identifier.
 * @param array  $data_after     Current appointment data.
 * @param array  $data_before    Previous appointment data.
 * @param array  $context        Extra context (meta etc).
 * @return void
 */
function adfoin_simplyscheduleappointments_process_trigger( $trigger, $appointment_id, $data_after = array(), $data_before = array(), $context = array() ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'simplyscheduleappointments', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_simplyscheduleappointments_build_payload( $trigger, $appointment_id, $data_after, $data_before, $context );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build normalized payload for SSA appointments.
 *
 * @param string $trigger        Trigger key.
 * @param int    $appointment_id Appointment identifier.
 * @param array  $data_after     Updated appointment data.
 * @param array  $data_before    Previous appointment data.
 * @param array  $context        Extra context.
 * @return array<string,mixed>
 */
function adfoin_simplyscheduleappointments_build_payload( $trigger, $appointment_id, $data_after = array(), $data_before = array(), $context = array() ) {
	$appointment_id = absint( $appointment_id );
	if ( ! $appointment_id ) {
		return array();
	}

	$current_data = is_array( $data_after ) ? $data_after : array();
	$current_data['id'] = $appointment_id;

	$db_data           = array();
	$appointment_obj   = null;

	if ( class_exists( 'SSA_Appointment_Object' ) ) {
		try {
			$appointment_obj = new SSA_Appointment_Object( $appointment_id );
			$appointment_obj->get( 1 );
			$db_data = $appointment_obj->data; // Magic getter exposes protected property.
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Swallow and fall back to provided data.
		}
	}

	if ( ! is_array( $db_data ) ) {
		$db_data = array();
	}

	$appointment_data = array_merge( $db_data, $current_data );

	$customer_info = isset( $appointment_data['customer_information'] ) ? $appointment_data['customer_information'] : array();
	$customer_info = adfoin_simplyscheduleappointments_normalize_array( $customer_info );
	$appointment_data['customer_information'] = $customer_info;

	$staff_ids = array();
	if ( function_exists( 'ssa' ) && isset( ssa()->appointment_model ) && method_exists( ssa()->appointment_model, 'get_staff_ids' ) ) {
		try {
			$staff_ids = ssa()->appointment_model->get_staff_ids( $appointment_id, $appointment_data );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Ignore staff lookup failures.
		}
	}
	$staff_ids = is_array( $staff_ids ) ? $staff_ids : array();

	$appointment_type_id     = isset( $appointment_data['appointment_type_id'] ) ? absint( $appointment_data['appointment_type_id'] ) : 0;
	$appointment_type_title  = '';
	$appointment_type_slug   = '';

	if ( $appointment_type_id && class_exists( 'SSA_Appointment_Type_Object' ) ) {
		try {
			$appointment_type        = new SSA_Appointment_Type_Object( $appointment_type_id );
			$appointment_type_title  = adfoin_simplyscheduleappointments_to_string( $appointment_type->get_title() );
			$appointment_type_slug   = adfoin_simplyscheduleappointments_to_string( $appointment_type->get_slug() );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Leave appointment type fields empty.
		}
	}

	$customer_name  = '';
	$customer_email = '';
	if ( $appointment_obj ) {
		try {
			$customer_name  = adfoin_simplyscheduleappointments_to_string( $appointment_obj->get_customer_name() );
			$customer_email = adfoin_simplyscheduleappointments_to_string( $appointment_obj->get_customer_email() );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Fall back to customer information array.
		}
	}

	if ( '' === $customer_name && ! empty( $customer_info ) ) {
		foreach ( array( 'name', 'Name', 'full_name', 'Full Name' ) as $key ) {
			if ( isset( $customer_info[ $key ] ) ) {
				$customer_name = adfoin_simplyscheduleappointments_to_string( $customer_info[ $key ] );
				break;
			}
		}
	}

	if ( '' === $customer_email && ! empty( $customer_info ) ) {
		foreach ( array( 'email', 'Email' ) as $key ) {
			if ( isset( $customer_info[ $key ] ) ) {
				$customer_email = adfoin_simplyscheduleappointments_to_string( $customer_info[ $key ] );
				break;
			}
		}
	}

	$customer_phone = adfoin_simplyscheduleappointments_extract_phone( $customer_info );

	$public_edit_url = '';
	if ( function_exists( 'ssa' ) && isset( ssa()->appointment_model ) && method_exists( ssa()->appointment_model, 'get_public_edit_url' ) ) {
		try {
			$public_edit_url = ssa()->appointment_model->get_public_edit_url( $appointment_id, $appointment_data );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			$public_edit_url = '';
		}
	}

	$previous_data      = is_array( $data_before ) ? $data_before : array();
	$previous_info      = isset( $previous_data['customer_information'] ) ? $previous_data['customer_information'] : array();
	$previous_info      = adfoin_simplyscheduleappointments_normalize_array( $previous_info );
	$previous_status    = isset( $previous_data['status'] ) ? $previous_data['status'] : '';
	$previous_start     = isset( $previous_data['start_date'] ) ? $previous_data['start_date'] : '';
	$previous_end       = isset( $previous_data['end_date'] ) ? $previous_data['end_date'] : '';

	$meta_data = isset( $context['meta'] ) ? $context['meta'] : array();
	$meta_data = adfoin_simplyscheduleappointments_normalize_array( $meta_data );

	$payload = array(
		'trigger'                         => $trigger,
		'appointment_id'                  => $appointment_id,
		'appointment_status'              => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['status'] ) ? $appointment_data['status'] : '' ),
		'appointment_status_label'        => adfoin_simplyscheduleappointments_status_label( isset( $appointment_data['status'] ) ? $appointment_data['status'] : '' ),
		'previous_status'                 => adfoin_simplyscheduleappointments_to_string( $previous_status ),
		'previous_status_label'           => adfoin_simplyscheduleappointments_status_label( $previous_status ),
		'appointment_type_id'             => $appointment_type_id,
		'appointment_type_title'          => $appointment_type_title,
		'appointment_type_slug'           => $appointment_type_slug,
		'rescheduled_from_appointment_id' => isset( $appointment_data['rescheduled_from_appointment_id'] ) ? absint( $appointment_data['rescheduled_from_appointment_id'] ) : 0,
		'rescheduled_to_appointment_id'   => isset( $appointment_data['rescheduled_to_appointment_id'] ) ? absint( $appointment_data['rescheduled_to_appointment_id'] ) : 0,
		'group_id'                        => isset( $appointment_data['group_id'] ) ? absint( $appointment_data['group_id'] ) : 0,
		'author_id'                       => isset( $appointment_data['author_id'] ) ? absint( $appointment_data['author_id'] ) : 0,
		'customer_id'                     => isset( $appointment_data['customer_id'] ) ? absint( $appointment_data['customer_id'] ) : 0,
		'customer_name'                   => $customer_name,
		'customer_email'                  => $customer_email,
		'customer_phone'                  => $customer_phone,
		'customer_timezone'               => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['customer_timezone'] ) ? $appointment_data['customer_timezone'] : '' ),
		'customer_locale'                 => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['customer_locale'] ) ? $appointment_data['customer_locale'] : '' ),
		'customer_information_json'       => adfoin_simplyscheduleappointments_json( $customer_info ),
		'customer_information_plain'      => adfoin_simplyscheduleappointments_plain_text( $customer_info ),
		'start_date'                      => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['start_date'] ) ? $appointment_data['start_date'] : '' ),
		'end_date'                        => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['end_date'] ) ? $appointment_data['end_date'] : '' ),
		'start_date_timestamp'            => adfoin_simplyscheduleappointments_timestamp( isset( $appointment_data['start_date'] ) ? $appointment_data['start_date'] : '' ),
		'end_date_timestamp'              => adfoin_simplyscheduleappointments_timestamp( isset( $appointment_data['end_date'] ) ? $appointment_data['end_date'] : '' ),
		'previous_start_date'             => adfoin_simplyscheduleappointments_to_string( $previous_start ),
		'previous_end_date'               => adfoin_simplyscheduleappointments_to_string( $previous_end ),
		'date_created'                    => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['date_created'] ) ? $appointment_data['date_created'] : '' ),
		'date_modified'                   => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['date_modified'] ) ? $appointment_data['date_modified'] : '' ),
		'expiration_date'                 => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['expiration_date'] ) ? $appointment_data['expiration_date'] : '' ),
		'appointment_title'               => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['title'] ) ? $appointment_data['title'] : '' ),
		'appointment_description'         => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['description'] ) ? $appointment_data['description'] : '' ),
		'payment_method'                  => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['payment_method'] ) ? $appointment_data['payment_method'] : '' ),
		'payment_received'                => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['payment_received'] ) ? $appointment_data['payment_received'] : '' ),
		'google_calendar_id'              => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['google_calendar_id'] ) ? $appointment_data['google_calendar_id'] : '' ),
		'google_calendar_event_id'        => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['google_calendar_event_id'] ) ? $appointment_data['google_calendar_event_id'] : '' ),
		'web_meeting_id'                  => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['web_meeting_id'] ) ? $appointment_data['web_meeting_id'] : '' ),
		'web_meeting_url'                 => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['web_meeting_url'] ) ? $appointment_data['web_meeting_url'] : '' ),
		'web_meeting_password'            => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['web_meeting_password'] ) ? $appointment_data['web_meeting_password'] : '' ),
		'allow_sms'                       => adfoin_simplyscheduleappointments_to_string( isset( $appointment_data['allow_sms'] ) ? $appointment_data['allow_sms'] : '' ),
		'staff_ids_json'                  => adfoin_simplyscheduleappointments_json( $staff_ids ),
		'primary_staff_id'                => isset( $staff_ids[0] ) ? absint( $staff_ids[0] ) : 0,
		'public_edit_url'                 => adfoin_simplyscheduleappointments_to_string( $public_edit_url ),
		'meta_key'                        => adfoin_simplyscheduleappointments_to_string( isset( $meta_data['meta_key'] ) ? $meta_data['meta_key'] : '' ),
		'meta_value'                      => adfoin_simplyscheduleappointments_to_string( isset( $meta_data['meta_value'] ) ? $meta_data['meta_value'] : '' ),
		'meta_raw'                        => adfoin_simplyscheduleappointments_json( $meta_data ),
	);

	// Flatten customer information for easier mapping.
	foreach ( $customer_info as $key => $value ) {
		$normalized_key                  = 'customer_' . adfoin_simplyscheduleappointments_normalize_key( $key );
		$payload[ $normalized_key ]      = adfoin_simplyscheduleappointments_to_string( $value );
	}

	// Add previous customer information snapshot.
	$payload['previous_customer_information_json']  = adfoin_simplyscheduleappointments_json( $previous_info );
	$payload['previous_customer_information_plain'] = adfoin_simplyscheduleappointments_plain_text( $previous_info );

	foreach ( $previous_info as $key => $value ) {
		$normalized_key                         = 'previous_customer_' . adfoin_simplyscheduleappointments_normalize_key( $key );
		$payload[ $normalized_key ]             = adfoin_simplyscheduleappointments_to_string( $value );
	}

	return $payload;
}

/**
 * Safely normalize arbitrary data into an array.
 *
 * @param mixed $value Potential array/json.
 * @return array<string,mixed>
 */
function adfoin_simplyscheduleappointments_normalize_array( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}

	if ( is_object( $value ) ) {
		return (array) $value;
	}

	if ( is_string( $value ) && $value !== '' ) {
		$decoded = json_decode( $value, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}

	return array();
}

/**
 * Convert arbitrary value to string.
 *
 * @param mixed $value Value to convert.
 * @return string
 */
function adfoin_simplyscheduleappointments_to_string( $value ) {
	if ( null === $value ) {
		return '';
	}

	if ( is_bool( $value ) ) {
		return $value ? 'yes' : 'no';
	}

	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	if ( empty( $value ) ) {
		return '';
	}

	return adfoin_simplyscheduleappointments_json( $value );
}

/**
 * Convert value to JSON string.
 *
 * @param mixed $value Value to encode.
 * @return string
 */
function adfoin_simplyscheduleappointments_json( $value ) {
	if ( empty( $value ) && '0' !== $value ) {
		return '';
	}

	$encoded = wp_json_encode( $value );

	return false === $encoded ? '' : $encoded;
}

/**
 * Convert an SSA date string into a timestamp.
 *
 * @param string $date_string Date string.
 * @return int
 */
function adfoin_simplyscheduleappointments_timestamp( $date_string ) {
	$date_string = adfoin_simplyscheduleappointments_to_string( $date_string );
	if ( '' === $date_string ) {
		return 0;
	}

	$timestamp = strtotime( $date_string );
	return $timestamp ? $timestamp : 0;
}

/**
 * Produce a readable label for SSA appointment statuses.
 *
 * @param string $status Status machine name.
 * @return string
 */
function adfoin_simplyscheduleappointments_status_label( $status ) {
	$status = adfoin_simplyscheduleappointments_to_string( $status );
	if ( '' === $status ) {
		return '';
	}

	$status = str_replace( array( '_', '-' ), ' ', strtolower( $status ) );
	return ucwords( $status );
}

/**
 * Convert key string into snake-like identifier.
 *
 * @param string $key Original key.
 * @return string
 */
function adfoin_simplyscheduleappointments_normalize_key( $key ) {
	$key = strtolower( adfoin_simplyscheduleappointments_to_string( $key ) );
	$key = preg_replace( '/[^a-z0-9]+/', '_', $key );
	$key = trim( (string) $key, '_' );

	return $key ? $key : 'field';
}

/**
 * Flatten customer information array to readable text.
 *
 * @param array<string,mixed> $customer_info Customer data.
 * @return string
 */
function adfoin_simplyscheduleappointments_plain_text( $customer_info ) {
	if ( empty( $customer_info ) ) {
		return '';
	}

	$lines = array();
	foreach ( $customer_info as $key => $value ) {
		$label = adfoin_simplyscheduleappointments_to_string( $key );
		$text  = adfoin_simplyscheduleappointments_to_string( $value );

		if ( '' === $label && '' === $text ) {
			continue;
		}

		$lines[] = $label . ': ' . $text;
	}

	return implode( "\n", $lines );
}

/**
 * Attempt to locate a phone number from customer information.
 *
 * @param array<string,mixed> $customer_info Customer data.
 * @return string
 */
function adfoin_simplyscheduleappointments_extract_phone( $customer_info ) {
	if ( empty( $customer_info ) ) {
		return '';
	}

	$phone_keys = array(
		'phone',
		'phone_number',
		'phone number',
		'Phone',
		'Phone Number',
		'telephone',
		'Telephone',
		'mobile',
		'Mobile',
	);

	foreach ( $phone_keys as $phone_key ) {
		if ( isset( $customer_info[ $phone_key ] ) ) {
			return adfoin_simplyscheduleappointments_to_string( $customer_info[ $phone_key ] );
		}
	}

	return '';
}
