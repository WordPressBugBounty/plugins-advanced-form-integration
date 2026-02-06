<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Event Espresso Decaf triggers.
 *
 * @param string $form_provider Integration key.
 * @return array|void
 */
function adfoin_eventespressodecaf_get_forms( $form_provider ) {
	if ( $form_provider !== 'eventespressodecaf' ) {
		return;
	}

	return array(
		'registrationApproved'      => __( 'Registration Approved', 'advanced-form-integration' ),
		'registrationStatusUpdated' => __( 'Registration Status Updated', 'advanced-form-integration' ),
	);
}

/**
 * Get Event Espresso Decaf fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger key.
 * @return array|void
 */
function adfoin_eventespressodecaf_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'eventespressodecaf' ) {
		return;
	}

	return array(
		'registration_id'          => __( 'Registration ID', 'advanced-form-integration' ),
		'registration_code'        => __( 'Registration Code', 'advanced-form-integration' ),
		'registration_date'        => __( 'Registration Date', 'advanced-form-integration' ),
		'old_status'               => __( 'Old Status', 'advanced-form-integration' ),
		'old_status_label'         => __( 'Old Status Label', 'advanced-form-integration' ),
		'new_status'               => __( 'New Status', 'advanced-form-integration' ),
		'new_status_label'         => __( 'New Status Label', 'advanced-form-integration' ),
		'event_id'                 => __( 'Event ID', 'advanced-form-integration' ),
		'event_name'               => __( 'Event Name', 'advanced-form-integration' ),
		'event_slug'               => __( 'Event Slug', 'advanced-form-integration' ),
		'event_start_date'         => __( 'Event Start Date', 'advanced-form-integration' ),
		'event_end_date'           => __( 'Event End Date', 'advanced-form-integration' ),
		'event_permalink'          => __( 'Event Permalink', 'advanced-form-integration' ),
		'attendee_id'              => __( 'Attendee ID', 'advanced-form-integration' ),
		'attendee_first_name'      => __( 'Attendee First Name', 'advanced-form-integration' ),
		'attendee_last_name'       => __( 'Attendee Last Name', 'advanced-form-integration' ),
		'attendee_full_name'       => __( 'Attendee Full Name', 'advanced-form-integration' ),
		'attendee_email'           => __( 'Attendee Email', 'advanced-form-integration' ),
		'attendee_phone'           => __( 'Attendee Phone', 'advanced-form-integration' ),
		'attendee_address'         => __( 'Attendee Address', 'advanced-form-integration' ),
		'attendee_address_2'       => __( 'Attendee Address 2', 'advanced-form-integration' ),
		'attendee_city'            => __( 'Attendee City', 'advanced-form-integration' ),
		'attendee_state'           => __( 'Attendee State', 'advanced-form-integration' ),
		'attendee_state_code'      => __( 'Attendee State Code', 'advanced-form-integration' ),
		'attendee_state_name'      => __( 'Attendee State Name', 'advanced-form-integration' ),
		'attendee_country'         => __( 'Attendee Country', 'advanced-form-integration' ),
		'attendee_country_name'    => __( 'Attendee Country Name', 'advanced-form-integration' ),
		'attendee_zip'             => __( 'Attendee ZIP / Postal Code', 'advanced-form-integration' ),
		'ticket_id'                => __( 'Ticket ID', 'advanced-form-integration' ),
		'ticket_name'              => __( 'Ticket Name', 'advanced-form-integration' ),
		'ticket_description'       => __( 'Ticket Description', 'advanced-form-integration' ),
		'ticket_price'             => __( 'Ticket Price', 'advanced-form-integration' ),
		'transaction_id'           => __( 'Transaction ID', 'advanced-form-integration' ),
		'transaction_status'       => __( 'Transaction Status', 'advanced-form-integration' ),
		'transaction_status_label' => __( 'Transaction Status Label', 'advanced-form-integration' ),
		'transaction_total'        => __( 'Transaction Total', 'advanced-form-integration' ),
		'transaction_paid'         => __( 'Transaction Paid Amount', 'advanced-form-integration' ),
		'transaction_balance_due'  => __( 'Transaction Balance Due', 'advanced-form-integration' ),
		'registration_answers'     => __( 'Registration Answers (JSON)', 'advanced-form-integration' ),
	);
}

add_action(
	'AHEE__EE_Registration__set_status__after_update',
	'adfoin_eventespressodecaf_handle_registration_update',
	10,
	4
);

/**
 * Handle registration status transitions coming from Event Espresso Decaf.
 *
 * @param EE_Registration $registration Registration object.
 * @param string          $old_status   Previous status code.
 * @param string          $new_status   Updated status code.
 * @param mixed           $context      Additional context.
 */
function adfoin_eventespressodecaf_handle_registration_update( $registration, $old_status, $new_status, $context ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	if ( ! $registration || ! ( $registration instanceof EE_Registration ) ) {
		return;
	}

	$integration           = new Advanced_Form_Integration_Integration();
	$status_records        = $integration->get_by_trigger( 'eventespressodecaf', 'registrationStatusUpdated' );
	$approved_records      = array();
	$reg_status_class_name = '\\EventEspresso\\core\\domain\\services\\registration\\RegStatus';

	if ( class_exists( $reg_status_class_name ) && $new_status === $reg_status_class_name::APPROVED ) {
		$approved_records = $integration->get_by_trigger( 'eventespressodecaf', 'registrationApproved' );
	}

	if ( empty( $status_records ) && empty( $approved_records ) ) {
		return;
	}

	$payload = adfoin_eventespressodecaf_build_registration_payload( $registration, $old_status, $new_status );
	if ( empty( $payload ) ) {
		return;
	}

	if ( ! empty( $status_records ) ) {
		$integration->send( $status_records, $payload );
	}

	if ( ! empty( $approved_records ) ) {
		$integration->send( $approved_records, $payload );
	}
}

/**
 * Build the payload that will be pushed to connected actions.
 *
 * @param EE_Registration $registration Registration object.
 * @param string          $old_status   Previous status code.
 * @param string          $new_status   Updated status code.
 * @return array
 */
function adfoin_eventespressodecaf_build_registration_payload( $registration, $old_status, $new_status ) {
	if ( ! $registration || ! ( $registration instanceof EE_Registration ) ) {
		return array();
	}

	$payload = array(
		'registration_id'          => adfoin_eventespressodecaf_call( $registration, 'ID' ),
		'registration_code'        => adfoin_eventespressodecaf_call( $registration, 'reg_code' ),
		'registration_date'        => adfoin_eventespressodecaf_call( $registration, 'get', array( 'REG_date' ) ),
		'old_status'               => $old_status,
		'old_status_label'         => adfoin_eventespressodecaf_status_label( $old_status ),
		'new_status'               => $new_status ?: adfoin_eventespressodecaf_call( $registration, 'status_ID' ),
		'new_status_label'         => '',
		'event_id'                 => '',
		'event_name'               => '',
		'event_slug'               => '',
		'event_start_date'         => '',
		'event_end_date'           => '',
		'event_permalink'          => '',
		'attendee_id'              => '',
		'attendee_first_name'      => '',
		'attendee_last_name'       => '',
		'attendee_full_name'       => '',
		'attendee_email'           => '',
		'attendee_phone'           => '',
		'attendee_address'         => '',
		'attendee_address_2'       => '',
		'attendee_city'            => '',
		'attendee_state'           => '',
		'attendee_state_code'      => '',
		'attendee_state_name'      => '',
		'attendee_country'         => '',
		'attendee_country_name'    => '',
		'attendee_zip'             => '',
		'ticket_id'                => '',
		'ticket_name'              => '',
		'ticket_description'       => '',
		'ticket_price'             => '',
		'transaction_id'           => '',
		'transaction_status'       => '',
		'transaction_status_label' => '',
		'transaction_total'        => '',
		'transaction_paid'         => '',
		'transaction_balance_due'  => '',
		'registration_answers'     => '',
	);

	$payload['new_status_label'] = adfoin_eventespressodecaf_status_label( $payload['new_status'] );

	$event = adfoin_eventespressodecaf_call( $registration, 'event_obj' );
	if ( $event ) {
		$payload['event_id']         = adfoin_eventespressodecaf_call( $event, 'ID' );
		$payload['event_name']       = adfoin_eventespressodecaf_call( $event, 'name' );
		$payload['event_slug']       = adfoin_eventespressodecaf_call( $event, 'slug' );
		$payload['event_start_date'] = adfoin_eventespressodecaf_call( $event, 'get', array( 'EVT_start_date' ) );
		$payload['event_end_date']   = adfoin_eventespressodecaf_call( $event, 'get', array( 'EVT_end_date' ) );

		if ( function_exists( 'get_permalink' ) && $payload['event_id'] ) {
			$payload['event_permalink'] = get_permalink( $payload['event_id'] );
		}
	}

	$attendee = adfoin_eventespressodecaf_call( $registration, 'attendee' );
	if ( $attendee ) {
		$payload['attendee_id']           = adfoin_eventespressodecaf_call( $attendee, 'ID' );
		$payload['attendee_first_name']   = adfoin_eventespressodecaf_call( $attendee, 'fname' );
		$payload['attendee_last_name']    = adfoin_eventespressodecaf_call( $attendee, 'lname' );
		$payload['attendee_full_name']    = adfoin_eventespressodecaf_call( $attendee, 'full_name' );
		$payload['attendee_email']        = adfoin_eventespressodecaf_call( $attendee, 'email' );
		$payload['attendee_phone']        = adfoin_eventespressodecaf_call( $attendee, 'phone' );
		$payload['attendee_address']      = adfoin_eventespressodecaf_call( $attendee, 'address' );
		$payload['attendee_address_2']    = adfoin_eventespressodecaf_call( $attendee, 'address2' );
		$payload['attendee_city']         = adfoin_eventespressodecaf_call( $attendee, 'city' );
		$payload['attendee_state']        = adfoin_eventespressodecaf_call( $attendee, 'state' );
		$payload['attendee_state_code']   = adfoin_eventespressodecaf_call( $attendee, 'state_abbrev' );
		$payload['attendee_state_name']   = adfoin_eventespressodecaf_call( $attendee, 'state_name' );
		$payload['attendee_country']      = adfoin_eventespressodecaf_call( $attendee, 'country_ID' );
		$payload['attendee_country_name'] = adfoin_eventespressodecaf_call( $attendee, 'country_name' );
		$payload['attendee_zip']          = adfoin_eventespressodecaf_call( $attendee, 'zip' );
	}

	$ticket = adfoin_eventespressodecaf_call( $registration, 'ticket' );
	if ( $ticket ) {
		$payload['ticket_id']          = adfoin_eventespressodecaf_call( $ticket, 'ID' );
		$payload['ticket_name']        = adfoin_eventespressodecaf_call( $ticket, 'name' );
		$payload['ticket_description'] = adfoin_eventespressodecaf_call( $ticket, 'description' );
		$payload['ticket_price']       = adfoin_eventespressodecaf_call( $ticket, 'price' );
	}

	$transaction = adfoin_eventespressodecaf_call( $registration, 'transaction' );
	if ( $transaction ) {
		$payload['transaction_id']           = adfoin_eventespressodecaf_call( $transaction, 'ID' );
		$payload['transaction_status']       = adfoin_eventespressodecaf_call( $transaction, 'status_ID' );
		$payload['transaction_status_label'] = adfoin_eventespressodecaf_status_label( $payload['transaction_status'] );
		$payload['transaction_total']        = adfoin_eventespressodecaf_call( $transaction, 'total' );
		$payload['transaction_paid']         = adfoin_eventespressodecaf_call( $transaction, 'paid' );

		if ( $payload['transaction_total'] !== '' && $payload['transaction_paid'] !== '' ) {
			$payload['transaction_balance_due'] = (float) $payload['transaction_total'] - (float) $payload['transaction_paid'];
		}
	}

	$payload['registration_answers'] = adfoin_eventespressodecaf_answers_json( $registration );

	return $payload;
}

/**
 * Convert registration answers into a JSON structure.
 *
 * @param EE_Registration $registration Registration object.
 * @return string
 */
function adfoin_eventespressodecaf_answers_json( $registration ) {
	$answers_payload = array();
	$answers         = adfoin_eventespressodecaf_call( $registration, 'answers' );

	if ( is_array( $answers ) ) {
		foreach ( $answers as $answer ) {
			if ( ! $answer ) {
				continue;
			}

			$question = adfoin_eventespressodecaf_call( $answer, 'question' );
			$label    = '';

			if ( $question ) {
				$label = adfoin_eventespressodecaf_call( $question, 'admin_label' );

				if ( $label === '' ) {
					$label = adfoin_eventespressodecaf_call( $question, 'display_text' );
				}

				if ( $label === '' ) {
					$label = adfoin_eventespressodecaf_call( $question, 'system_ID' );
				}
			}

			if ( $label === '' ) {
				$label = 'question_' . adfoin_eventespressodecaf_call( $answer, 'question_ID' );
			}

			$value = adfoin_eventespressodecaf_call( $answer, 'pretty_value' );
			if ( $value === '' ) {
				$value = adfoin_eventespressodecaf_call( $answer, 'value' );
			}

			$answers_payload[ $label ] = $value;
		}
	}

	if ( empty( $answers_payload ) ) {
		return '';
	}

	return function_exists( 'wp_json_encode' )
		? wp_json_encode( $answers_payload )
		: json_encode( $answers_payload );
}

/**
 * Translate an Event Espresso status code into a human readable label.
 *
 * @param string $status Status code.
 * @return string
 */
function adfoin_eventespressodecaf_status_label( $status ) {
	if ( $status === '' || ! class_exists( 'EEM_Status' ) ) {
		return $status;
	}

	try {
		$labels = EEM_Status::instance()->localized_status( array( $status => $status ), false, 'sentence' );
		if ( isset( $labels[ $status ] ) ) {
			return $labels[ $status ];
		}
	} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
	}

	return $status;
}

/**
 * Safe helper for method calls that might throw or be unavailable.
 *
 * @param object $object  Target instance.
 * @param string $method  Method name.
 * @param array  $args    Optional arguments.
 * @param mixed  $default Default value.
 * @return mixed
 */
function adfoin_eventespressodecaf_call( $object, $method, $args = array(), $default = '' ) {
	if ( ! is_object( $object ) || ! is_callable( array( $object, $method ) ) ) {
		return $default;
	}

	try {
		return call_user_func_array( array( $object, $method ), $args );
	} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
		return $default;
	}
}
