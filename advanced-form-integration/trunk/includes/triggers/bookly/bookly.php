<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Bookly triggers.
 *
 * @param string $form_provider Integration key.
 * @return array<string,string>|void
 */
function adfoin_bookly_get_forms( $form_provider ) {
	if ( $form_provider !== 'bookly' ) {
		return;
	}

	return array(
		'appointmentCreated'        => __( 'Appointment Created', 'advanced-form-integration' ),
		'appointmentUpdated'        => __( 'Appointment Updated', 'advanced-form-integration' ),
		'appointmentRescheduled'    => __( 'Appointment Rescheduled', 'advanced-form-integration' ),
		'appointmentDeleted'        => __( 'Appointment Deleted', 'advanced-form-integration' ),
		'appointmentStatusChanged'  => __( 'Appointment Status Changed', 'advanced-form-integration' ),
		'appointmentApproved'       => __( 'Appointment Approved', 'advanced-form-integration' ),
		'appointmentCancelled'      => __( 'Appointment Cancelled', 'advanced-form-integration' ),
		'appointmentRejected'       => __( 'Appointment Rejected', 'advanced-form-integration' ),
		'appointmentPending'        => __( 'Appointment Pending', 'advanced-form-integration' ),
		'appointmentWaitlisted'     => __( 'Appointment Waitlisted', 'advanced-form-integration' ),
		'appointmentDone'           => __( 'Appointment Done', 'advanced-form-integration' ),
	);
}

/**
 * Get Bookly mapped fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger identifier.
 * @return array<string,string>|void
 */
function adfoin_bookly_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'bookly' ) {
		return;
	}

	$fields = array(
		'trigger'                                   => __( 'Trigger', 'advanced-form-integration' ),
		'customer_appointment_id'                   => __( 'Customer Appointment ID', 'advanced-form-integration' ),
		'appointment_id'                            => __( 'Appointment ID', 'advanced-form-integration' ),
		'appointment_status'                        => __( 'Status', 'advanced-form-integration' ),
		'appointment_status_label'                  => __( 'Status Label', 'advanced-form-integration' ),
		'previous_status'                           => __( 'Previous Status', 'advanced-form-integration' ),
		'previous_status_label'                     => __( 'Previous Status Label', 'advanced-form-integration' ),
		'created_from'                              => __( 'Created From', 'advanced-form-integration' ),
		'created_at'                                => __( 'Created At', 'advanced-form-integration' ),
		'updated_at'                                => __( 'Updated At', 'advanced-form-integration' ),
		'status_changed_at'                         => __( 'Status Changed At', 'advanced-form-integration' ),
		'number_of_persons'                         => __( 'Number Of Persons', 'advanced-form-integration' ),
		'previous_number_of_persons'                => __( 'Previous Number Of Persons', 'advanced-form-integration' ),
		'units'                                     => __( 'Units', 'advanced-form-integration' ),
        'previous_units'                            => __( 'Previous Units', 'advanced-form-integration' ),
		'time_zone'                                 => __( 'Time Zone', 'advanced-form-integration' ),
		'time_zone_offset'                          => __( 'Time Zone Offset', 'advanced-form-integration' ),
		'custom_fields_json'                        => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
		'custom_fields_plain'                       => __( 'Custom Fields (Text)', 'advanced-form-integration' ),
		'previous_custom_fields_json'               => __( 'Previous Custom Fields (JSON)', 'advanced-form-integration' ),
		'previous_custom_fields_plain'              => __( 'Previous Custom Fields (Text)', 'advanced-form-integration' ),
		'extras_json'                               => __( 'Extras (JSON)', 'advanced-form-integration' ),
		'extras_plain'                              => __( 'Extras (Text)', 'advanced-form-integration' ),
		'previous_extras_json'                      => __( 'Previous Extras (JSON)', 'advanced-form-integration' ),
		'previous_extras_plain'                     => __( 'Previous Extras (Text)', 'advanced-form-integration' ),
		'notes'                                     => __( 'Notes', 'advanced-form-integration' ),
		'previous_notes'                            => __( 'Previous Notes', 'advanced-form-integration' ),
		'payment_id'                                => __( 'Payment ID', 'advanced-form-integration' ),
		'payment_status'                            => __( 'Payment Status', 'advanced-form-integration' ),
		'payment_type'                              => __( 'Payment Type', 'advanced-form-integration' ),
		'payment_total'                             => __( 'Payment Total', 'advanced-form-integration' ),
		'payment_paid'                              => __( 'Payment Paid', 'advanced-form-integration' ),
		'payment_gateway'                           => __( 'Payment Gateway', 'advanced-form-integration' ),
		'payment_details_json'                      => __( 'Payment Details (JSON)', 'advanced-form-integration' ),
		'customer_id'                               => __( 'Customer ID', 'advanced-form-integration' ),
		'customer_full_name'                        => __( 'Customer Full Name', 'advanced-form-integration' ),
		'customer_first_name'                       => __( 'Customer First Name', 'advanced-form-integration' ),
		'customer_last_name'                        => __( 'Customer Last Name', 'advanced-form-integration' ),
		'customer_email'                            => __( 'Customer Email', 'advanced-form-integration' ),
		'customer_phone'                            => __( 'Customer Phone', 'advanced-form-integration' ),
		'customer_birthday'                         => __( 'Customer Birthday', 'advanced-form-integration' ),
		'customer_country'                          => __( 'Customer Country', 'advanced-form-integration' ),
		'customer_state'                            => __( 'Customer State', 'advanced-form-integration' ),
		'customer_postcode'                         => __( 'Customer Postcode', 'advanced-form-integration' ),
		'customer_city'                             => __( 'Customer City', 'advanced-form-integration' ),
		'customer_street'                           => __( 'Customer Street', 'advanced-form-integration' ),
		'customer_street_number'                    => __( 'Customer Street Number', 'advanced-form-integration' ),
		'customer_additional_address'               => __( 'Customer Additional Address', 'advanced-form-integration' ),
		'customer_full_address'                     => __( 'Customer Full Address', 'advanced-form-integration' ),
		'customer_notes'                            => __( 'Customer Notes', 'advanced-form-integration' ),
		'customer_group_id'                         => __( 'Customer Group ID', 'advanced-form-integration' ),
		'staff_id'                                  => __( 'Staff ID', 'advanced-form-integration' ),
		'staff_name'                                => __( 'Staff Name', 'advanced-form-integration' ),
		'staff_email'                               => __( 'Staff Email', 'advanced-form-integration' ),
		'staff_phone'                               => __( 'Staff Phone', 'advanced-form-integration' ),
		'previous_staff_id'                         => __( 'Previous Staff ID', 'advanced-form-integration' ),
		'previous_staff_name'                       => __( 'Previous Staff Name', 'advanced-form-integration' ),
		'service_id'                                => __( 'Service ID', 'advanced-form-integration' ),
		'service_name'                              => __( 'Service Name', 'advanced-form-integration' ),
		'service_price'                             => __( 'Service Price', 'advanced-form-integration' ),
		'service_duration'                          => __( 'Service Duration (Seconds)', 'advanced-form-integration' ),
		'previous_service_id'                       => __( 'Previous Service ID', 'advanced-form-integration' ),
		'previous_service_name'                     => __( 'Previous Service Name', 'advanced-form-integration' ),
		'appointment_location_id'                   => __( 'Location ID', 'advanced-form-integration' ),
		'previous_appointment_location_id'          => __( 'Previous Location ID', 'advanced-form-integration' ),
		'appointment_internal_note'                 => __( 'Internal Note', 'advanced-form-integration' ),
		'previous_appointment_internal_note'        => __( 'Previous Internal Note', 'advanced-form-integration' ),
		'appointment_custom_service_name'           => __( 'Custom Service Name', 'advanced-form-integration' ),
		'appointment_custom_service_price'          => __( 'Custom Service Price', 'advanced-form-integration' ),
		'appointment_start'                         => __( 'Start Date', 'advanced-form-integration' ),
		'appointment_end'                           => __( 'End Date', 'advanced-form-integration' ),
		'appointment_start_timestamp'               => __( 'Start Timestamp', 'advanced-form-integration' ),
		'appointment_end_timestamp'                 => __( 'End Timestamp', 'advanced-form-integration' ),
		'previous_appointment_start'                => __( 'Previous Start Date', 'advanced-form-integration' ),
		'previous_appointment_end'                  => __( 'Previous End Date', 'advanced-form-integration' ),
		'previous_appointment_start_timestamp'      => __( 'Previous Start Timestamp', 'advanced-form-integration' ),
		'previous_appointment_end_timestamp'        => __( 'Previous End Timestamp', 'advanced-form-integration' ),
		'appointment_created_at'                    => __( 'Appointment Created At', 'advanced-form-integration' ),
		'appointment_updated_at'                    => __( 'Appointment Updated At', 'advanced-form-integration' ),
		'appointment_created_from'                  => __( 'Appointment Created From', 'advanced-form-integration' ),
		'appointment_extras_duration'               => __( 'Extras Duration (Seconds)', 'advanced-form-integration' ),
		'appointment_online_meeting_provider'       => __( 'Online Meeting Provider', 'advanced-form-integration' ),
		'appointment_online_meeting_id'             => __( 'Online Meeting ID', 'advanced-form-integration' ),
		'appointment_online_meeting_data_json'      => __( 'Online Meeting Data (JSON)', 'advanced-form-integration' ),
		'series_id'                                 => __( 'Series ID', 'advanced-form-integration' ),
		'package_id'                                => __( 'Package ID', 'advanced-form-integration' ),
		'collaborative_service_id'                  => __( 'Collaborative Service ID', 'advanced-form-integration' ),
		'collaborative_token'                       => __( 'Collaborative Token', 'advanced-form-integration' ),
		'compound_service_id'                       => __( 'Compound Service ID', 'advanced-form-integration' ),
		'compound_token'                            => __( 'Compound Token', 'advanced-form-integration' ),
		'group_id'                                  => __( 'Group Booking ID', 'advanced-form-integration' ),
	);

	return $fields;
}

add_action( 'plugins_loaded', 'adfoin_bookly_bootstrap', 20 );

/**
 * Bootstrap integration.
 */
function adfoin_bookly_bootstrap() {
	if ( ! class_exists( '\Bookly\Lib\Entities\CustomerAppointment' ) ) {
		return;
	}

	add_action( 'init', 'adfoin_bookly_maybe_register_watchers' );
}

/**
 * Check current AJAX action and register watchers when needed.
 */
function adfoin_bookly_maybe_register_watchers() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	if ( empty( $action ) ) {
		return;
	}

	$watched = array(
		'bookly_save_appointment',
		'bookly_save_appointment_form',
		'bookly_delete_customer_appointments',
		'bookly_cancel_appointment',
		'bookly_approve_appointment',
		'bookly_reject_appointment',
	);

	if ( in_array( $action, $watched, true ) ) {
		adfoin_bookly_prepare_watchers( $action );
	}
}

/**
 * Shared integration state.
 *
 * @return array<string,mixed>
 */
function &adfoin_bookly_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'max_id'               => null,
			'appointments'         => array(),
			'cas'                  => array(),
			'shutdown_registered'  => false,
			'actions'              => array(),
			'processed'            => array(),
		);
	}

	return $state;
}

/**
 * Register snapshots for current action.
 *
 * @param string $action AJAX action.
 */
function adfoin_bookly_prepare_watchers( $action ) {
	global $wpdb;

	$state = &adfoin_bookly_state();
	$state['actions'][ $action ] = true;

	$table = $wpdb->prefix . 'bookly_customer_appointments';
	if ( null === $state['max_id'] ) {
		$state['max_id'] = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$table}" );
	}

	switch ( $action ) {
		case 'bookly_save_appointment':
			// front-end booking may create appointments; no additional snapshot required.
			break;
		case 'bookly_save_appointment_form':
			$appointment_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;
			if ( $appointment_id > 0 ) {
				$state['appointments'][ $appointment_id ] = adfoin_bookly_snapshot_appointment( $appointment_id );
			}
			break;
		case 'bookly_delete_customer_appointments':
			$data = isset( $_REQUEST['data'] ) ? wp_unslash( $_REQUEST['data'] ) : array();
			if ( is_string( $data ) ) {
				$decoded = json_decode( $data, true );
				if ( is_array( $decoded ) ) {
					$data = $decoded;
				} else {
					$data = array();
				}
			}
			if ( ! is_array( $data ) ) {
				$data = array();
			}
			$ca_ids        = array();
			$appointment_ids = array();
			foreach ( $data as $item ) {
				if ( isset( $item['ca_id'] ) && $item['ca_id'] !== 'null' ) {
					$ca_ids[] = (int) $item['ca_id'];
				}
				if ( isset( $item['id'] ) && $item['id'] !== 'null' ) {
					$appointment_ids[] = (int) $item['id'];
				}
			}
			if ( $ca_ids ) {
				$state['cas'] = $state['cas'] + adfoin_bookly_snapshot_cas( $ca_ids );
			}
			foreach ( array_unique( $appointment_ids ) as $appointment_id ) {
				if ( $appointment_id > 0 ) {
					$state['appointments'][ $appointment_id ] = adfoin_bookly_snapshot_appointment( $appointment_id );
				}
			}
			break;
		case 'bookly_cancel_appointment':
		case 'bookly_approve_appointment':
		case 'bookly_reject_appointment':
			$token = isset( $_REQUEST['token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['token'] ) ) : '';
			if ( $token ) {
				$ca = new \Bookly\Lib\Entities\CustomerAppointment();
				if ( $ca->loadBy( array( 'token' => $token ) ) ) {
					$state['cas'][ $ca->getId() ] = $ca->getFields();
					$appointment_id = (int) $ca->getAppointmentId();
					if ( $appointment_id > 0 ) {
						$state['appointments'][ $appointment_id ] = adfoin_bookly_snapshot_appointment( $appointment_id );
					}
				}
			}
			break;
		default:
			break;
	}

	if ( ! $state['shutdown_registered'] ) {
		$state['shutdown_registered'] = true;
		register_shutdown_function( 'adfoin_bookly_handle_shutdown' );
	}
}

/**
 * Snapshot appointment row and related customer appointments.
 *
 * @param int $appointment_id Appointment ID.
 * @return array<string,mixed>
 */
function adfoin_bookly_snapshot_appointment( $appointment_id ) {
	global $wpdb;
	$table_appointments = $wpdb->prefix . 'bookly_appointments';
	$table_customers    = $wpdb->prefix . 'bookly_customer_appointments';

	$appointment = $appointment_id > 0
		? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_appointments} WHERE id = %d LIMIT 1", $appointment_id ), ARRAY_A )
		: null;

	$cas = array();
	if ( $appointment_id > 0 ) {
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE appointment_id = %d", $appointment_id ), ARRAY_A );
		foreach ( (array) $rows as $row ) {
			$cas[ (int) $row['id'] ] = $row;
		}
	}

	return array(
		'appointment' => $appointment,
		'cas'         => $cas,
	);
}

/**
 * Snapshot specific customer appointments.
 *
 * @param array<int> $ids Customer appointment IDs.
 * @return array<int,array<string,mixed>>
 */
function adfoin_bookly_snapshot_cas( array $ids ) {
	global $wpdb;
	$table = $wpdb->prefix . 'bookly_customer_appointments';
	$ids   = array_filter( array_map( 'intval', $ids ) );
	if ( empty( $ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$query        = $wpdb->prepare( "SELECT * FROM {$table} WHERE id IN ({$placeholders})", $ids );
	$rows         = $wpdb->get_results( $query, ARRAY_A );

	$result = array();
	foreach ( (array) $rows as $row ) {
		$result[ (int) $row['id'] ] = $row;
	}

	return $result;
}

/**
 * Get raw appointment row.
 *
 * @param int $appointment_id Appointment ID.
 * @return array<string,mixed>|null
 */
function adfoin_bookly_get_raw_appointment( $appointment_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'bookly_appointments';

	if ( $appointment_id < 1 ) {
		return null;
	}

	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $appointment_id ), ARRAY_A );
}

/**
 * Shutdown handler processes detected changes.
 */
function adfoin_bookly_handle_shutdown() {
	global $wpdb;

	$state = &adfoin_bookly_state();
	if ( empty( $state['actions'] ) ) {
		return;
	}

	$table = $wpdb->prefix . 'bookly_customer_appointments';

	if ( null !== $state['max_id'] ) {
		$new_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE id > %d", $state['max_id'] ), ARRAY_A );
		foreach ( (array) $new_rows as $row ) {
			$ca_id = (int) $row['id'];
			if ( isset( $state['processed'][ $ca_id ] ) ) {
				continue;
			}
			adfoin_bookly_process_change( 'created', null, $row, null, null );
			$state['processed'][ $ca_id ] = true;
		}
	}

	foreach ( $state['appointments'] as $appointment_id => $snapshot ) {
		$before_cas        = $snapshot['cas'];
		$before_appointment = $snapshot['appointment'];

		$after_snapshot    = adfoin_bookly_snapshot_appointment( $appointment_id );
		$after_cas         = $after_snapshot['cas'];
		$after_appointment = $after_snapshot['appointment'];

        $appointment_changed = adfoin_bookly_has_appointment_changes( $before_appointment, $after_appointment );

		foreach ( $after_cas as $ca_id => $after_row ) {
			if ( isset( $state['processed'][ $ca_id ] ) ) {
				continue;
			}
			$before_row    = isset( $before_cas[ $ca_id ] ) ? $before_cas[ $ca_id ] : null;
			$status_changed = adfoin_bookly_status_changed( $before_row, $after_row );
			$customer_changed = $before_row ? adfoin_bookly_customer_rows_different( $before_row, $after_row ) : true;

			if ( ! $before_row ) {
				adfoin_bookly_process_change( 'created', null, $after_row, $before_appointment, $after_appointment );
			} else {
				if ( $status_changed ) {
					adfoin_bookly_process_change( 'status', $before_row, $after_row, $before_appointment, $after_appointment );
				}
				if ( $appointment_changed ) {
					adfoin_bookly_process_change( 'appointment_changed', $before_row, $after_row, $before_appointment, $after_appointment );
				}
				if ( ! $status_changed && ! $appointment_changed && $customer_changed ) {
					adfoin_bookly_process_change( 'updated', $before_row, $after_row, $before_appointment, $after_appointment );
				}
			}
			$state['processed'][ $ca_id ] = true;
		}

		foreach ( $before_cas as $ca_id => $before_row ) {
			if ( isset( $state['processed'][ $ca_id ] ) ) {
				continue;
			}
			if ( ! isset( $after_cas[ $ca_id ] ) ) {
				adfoin_bookly_process_change( 'deleted', $before_row, null, $before_appointment, $after_appointment );
				$state['processed'][ $ca_id ] = true;
			}
		}
	}

	foreach ( $state['cas'] as $ca_id => $before_row ) {
		if ( isset( $state['processed'][ $ca_id ] ) ) {
			continue;
		}

		$after_row          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $ca_id ), ARRAY_A );
		$appointment_before = $before_row ? adfoin_bookly_get_raw_appointment( (int) $before_row['appointment_id'] ) : null;
		$appointment_after  = $after_row ? adfoin_bookly_get_raw_appointment( (int) $after_row['appointment_id'] ) : null;

		if ( $after_row ) {
			$status_changed     = adfoin_bookly_status_changed( $before_row, $after_row );
			$appointment_changed = adfoin_bookly_has_appointment_changes( $appointment_before, $appointment_after );
			$customer_changed   = $before_row ? adfoin_bookly_customer_rows_different( $before_row, $after_row ) : true;

			if ( $status_changed ) {
				adfoin_bookly_process_change( 'status', $before_row, $after_row, $appointment_before, $appointment_after );
			}
			if ( $appointment_changed ) {
				adfoin_bookly_process_change( 'appointment_changed', $before_row, $after_row, $appointment_before, $appointment_after );
			}
			if ( ! $status_changed && ! $appointment_changed && $customer_changed ) {
				adfoin_bookly_process_change( 'updated', $before_row, $after_row, $appointment_before, $appointment_after );
			}
		} else {
			adfoin_bookly_process_change( 'deleted', $before_row, null, $appointment_before, $appointment_after );
		}

		$state['processed'][ $ca_id ] = true;
	}

	$state = array(
		'max_id'               => null,
		'appointments'         => array(),
		'cas'                  => array(),
		'shutdown_registered'  => false,
		'actions'              => array(),
		'processed'            => array(),
	);
}

/**
 * Check if status changed.
 *
 * @param array<string,mixed>|null $before_before Before row.
 * @param array<string,mixed>|null $after_row     After row.
 * @return bool
 */
function adfoin_bookly_status_changed( $before_before, $after_row ) {
	if ( ! is_array( $after_row ) ) {
		return false;
	}
	$current = isset( $after_row['status'] ) ? (string) $after_row['status'] : '';
	$previous = is_array( $before_before ) && isset( $before_before['status'] ) ? (string) $before_before['status'] : '';

	return $previous !== $current;
}

/**
 * Determine if appointment core fields changed.
 *
 * @param array<string,mixed>|null $before Before snapshot.
 * @param array<string,mixed>|null $after  After snapshot.
 * @return bool
 */
function adfoin_bookly_has_appointment_changes( $before, $after ) {
	if ( ! is_array( $before ) || ! is_array( $after ) ) {
		return false;
	}

	$keys = array(
		'start_date',
		'end_date',
		'staff_id',
		'service_id',
		'location_id',
		'custom_service_name',
		'custom_service_price',
	);

	foreach ( $keys as $key ) {
		$before_value = isset( $before[ $key ] ) ? (string) $before[ $key ] : '';
		$after_value  = isset( $after[ $key ] ) ? (string) $after[ $key ] : '';
		if ( $before_value !== $after_value ) {
			return true;
		}
	}

	return false;
}

/**
 * Detect differences in customer appointment data.
 *
 * @param array<string,mixed> $before Before row.
 * @param array<string,mixed> $after  After row.
 * @return bool
 */
function adfoin_bookly_customer_rows_different( array $before, array $after ) {
	$ignore = array(
		'id',
		'status',
		'updated_at',
		'status_changed_at',
		'created_at',
	);

	foreach ( $before as $key => $value ) {
		if ( in_array( $key, $ignore, true ) ) {
			continue;
		}
		$after_value = isset( $after[ $key ] ) ? $after[ $key ] : null;
		if ( (string) $value !== (string) $after_value ) {
			return true;
		}
	}

	foreach ( $after as $key => $value ) {
		if ( in_array( $key, $ignore, true ) ) {
			continue;
		}
		if ( ! array_key_exists( $key, $before ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Handle change type and trigger events accordingly.
 *
 * @param string                      $type                Change type.
 * @param array<string,mixed>|null    $before_row          Before row.
 * @param array<string,mixed>|null    $after_row           After row.
 * @param array<string,mixed>|null    $appointment_before  Appointment before.
 * @param array<string,mixed>|null    $appointment_after   Appointment after.
 */
function adfoin_bookly_process_change( $type, $before_row, $after_row, $appointment_before, $appointment_after ) {
	$ca_id = null;
	if ( is_array( $after_row ) && isset( $after_row['id'] ) ) {
		$ca_id = (int) $after_row['id'];
	} elseif ( is_array( $before_row ) && isset( $before_row['id'] ) ) {
		$ca_id = (int) $before_row['id'];
	}

	if ( ! $ca_id ) {
		return;
	}

	$current_status  = is_array( $after_row ) && isset( $after_row['status'] ) ? (string) $after_row['status'] : '';
	$previous_status = is_array( $before_row ) && isset( $before_row['status'] ) ? (string) $before_row['status'] : '';

	switch ( $type ) {
		case 'created':
			$payload = adfoin_bookly_build_payload( $ca_id, $after_row, null, $appointment_before, $appointment_after );
			if ( $payload ) {
				adfoin_bookly_send( 'appointmentCreated', $payload );
				adfoin_bookly_fire_status_triggers( '', $current_status, $payload );
			}
			break;
		case 'status':
			$payload = adfoin_bookly_build_payload( $ca_id, $after_row, $before_row, $appointment_before, $appointment_after );
			if ( $payload ) {
				adfoin_bookly_send( 'appointmentStatusChanged', $payload );
				adfoin_bookly_fire_status_triggers( $previous_status, $current_status, $payload );
			}
			break;
		case 'appointment_changed':
			$payload = adfoin_bookly_build_payload( $ca_id, $after_row, $before_row, $appointment_before, $appointment_after );
			if ( $payload ) {
				adfoin_bookly_send( 'appointmentRescheduled', $payload );
			}
			break;
		case 'updated':
			$payload = adfoin_bookly_build_payload( $ca_id, $after_row, $before_row, $appointment_before, $appointment_after );
			if ( $payload ) {
				adfoin_bookly_send( 'appointmentUpdated', $payload );
			}
			break;
		case 'deleted':
			$payload = adfoin_bookly_build_payload( $ca_id, null, $before_row, $appointment_before, $appointment_after );
			if ( $payload ) {
				adfoin_bookly_send( 'appointmentDeleted', $payload );
			}
			break;
	}
}

/**
 * Trigger status-specific events.
 *
 * @param string $previous_status Previous status.
 * @param string $current_status  Current status.
 * @param array<string,mixed> $payload Payload.
 */
function adfoin_bookly_fire_status_triggers( $previous_status, $current_status, $payload ) {
	$current_status = strtolower( (string) $current_status );
	$previous_status = strtolower( (string) $previous_status );

	if ( $current_status === $previous_status ) {
		return;
	}

	$map = array(
		'approved'   => 'appointmentApproved',
		'cancelled'  => 'appointmentCancelled',
		'rejected'   => 'appointmentRejected',
		'pending'    => 'appointmentPending',
		'waitlisted' => 'appointmentWaitlisted',
		'done'       => 'appointmentDone',
	);

	if ( isset( $map[ $current_status ] ) ) {
		adfoin_bookly_send( $map[ $current_status ], $payload );
	}
}

/**
 * Send payload to integration.
 *
 * @param string                 $trigger Trigger key.
 * @param array<string,mixed>    $payload Payload array.
 */
function adfoin_bookly_send( $trigger, $payload ) {
	if ( empty( $payload ) ) {
		return;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'bookly', $trigger );

	if ( empty( $records ) ) {
		return;
	}

	$data              = $payload;
	$data['trigger']   = $trigger;
	$integration->send( $records, $data );
}

/**
 * Build payload for a customer appointment.
 *
 * @param int                           $ca_id               Customer appointment ID.
 * @param array<string,mixed>|null      $current_row         Current row.
 * @param array<string,mixed>|null      $previous_row        Previous row.
 * @param array<string,mixed>|null      $appointment_before  Appointment before.
 * @param array<string,mixed>|null      $appointment_after   Appointment after.
 * @return array<string,mixed>
 */
function adfoin_bookly_build_payload( $ca_id, $current_row, $previous_row, $appointment_before, $appointment_after ) {
	$row = null;
	if ( is_array( $current_row ) ) {
		$row = adfoin_bookly_fetch_joined_row( $ca_id );
	}

	if ( ! $row && is_array( $previous_row ) ) {
		$row = $previous_row;
		if ( is_array( $appointment_before ) ) {
			$row = array_merge( $row, array(
				'appointment_start_date'  => isset( $appointment_before['start_date'] ) ? $appointment_before['start_date'] : '',
				'appointment_end_date'    => isset( $appointment_before['end_date'] ) ? $appointment_before['end_date'] : '',
				'appointment_staff_id'    => isset( $appointment_before['staff_id'] ) ? $appointment_before['staff_id'] : null,
				'appointment_service_id'  => isset( $appointment_before['service_id'] ) ? $appointment_before['service_id'] : null,
				'appointment_location_id' => isset( $appointment_before['location_id'] ) ? $appointment_before['location_id'] : null,
			) );
		}
	}

	if ( ! is_array( $row ) ) {
		return array();
	}

	$previous_row = is_array( $previous_row ) ? $previous_row : array();

	$appointment_current  = adfoin_bookly_get_raw_appointment( isset( $row['appointment_id'] ) ? (int) $row['appointment_id'] : 0 );
	$appointment_before   = is_array( $appointment_before ) ? $appointment_before : array();
	$appointment_after    = is_array( $appointment_after ) ? $appointment_after : $appointment_current;

	$service_id_current  = isset( $row['appointment_service_id'] ) ? (int) $row['appointment_service_id'] : ( isset( $appointment_after['service_id'] ) ? (int) $appointment_after['service_id'] : 0 );
	$service_name_current = isset( $row['service_title'] ) && $row['service_title'] !== '' ? $row['service_title'] : adfoin_bookly_get_service_name( $service_id_current );
	$service_id_previous  = isset( $appointment_before['service_id'] ) ? (int) $appointment_before['service_id'] : 0;
	$service_name_previous = adfoin_bookly_get_service_name( $service_id_previous );

	$staff_id_current  = isset( $row['appointment_staff_id'] ) ? (int) $row['appointment_staff_id'] : ( isset( $appointment_after['staff_id'] ) ? (int) $appointment_after['staff_id'] : 0 );
	$staff_name_current = isset( $row['staff_full_name'] ) && $row['staff_full_name'] !== '' ? $row['staff_full_name'] : adfoin_bookly_get_staff_name( $staff_id_current );
	$staff_id_previous  = isset( $appointment_before['staff_id'] ) ? (int) $appointment_before['staff_id'] : 0;
	$staff_name_previous = adfoin_bookly_get_staff_name( $staff_id_previous );

	$custom_fields        = adfoin_bookly_decode_json( isset( $row['custom_fields'] ) ? $row['custom_fields'] : '[]' );
	$prev_custom_fields   = adfoin_bookly_decode_json( isset( $previous_row['custom_fields'] ) ? $previous_row['custom_fields'] : '[]' );
	$extras               = adfoin_bookly_decode_json( isset( $row['extras'] ) ? $row['extras'] : '[]' );
	$prev_extras          = adfoin_bookly_decode_json( isset( $previous_row['extras'] ) ? $previous_row['extras'] : '[]' );
	$online_data          = isset( $row['appointment_online_meeting_data'] ) ? $row['appointment_online_meeting_data'] : '';

	$payload = array(
		'customer_appointment_id'              => $ca_id,
		'appointment_id'                       => isset( $row['appointment_id'] ) ? (int) $row['appointment_id'] : 0,
		'appointment_status'                   => adfoin_bookly_to_string( isset( $row['status'] ) ? $row['status'] : '' ),
		'appointment_status_label'             => adfoin_bookly_status_label( isset( $row['status'] ) ? $row['status'] : '' ),
		'previous_status'                      => adfoin_bookly_to_string( isset( $previous_row['status'] ) ? $previous_row['status'] : '' ),
		'previous_status_label'                => adfoin_bookly_status_label( isset( $previous_row['status'] ) ? $previous_row['status'] : '' ),
		'created_from'                         => adfoin_bookly_to_string( isset( $row['created_from'] ) ? $row['created_from'] : '' ),
		'created_at'                           => adfoin_bookly_to_string( isset( $row['created_at'] ) ? $row['created_at'] : '' ),
		'updated_at'                           => adfoin_bookly_to_string( isset( $row['updated_at'] ) ? $row['updated_at'] : '' ),
		'status_changed_at'                    => adfoin_bookly_to_string( isset( $row['status_changed_at'] ) ? $row['status_changed_at'] : '' ),
		'number_of_persons'                    => adfoin_bookly_to_string( isset( $row['number_of_persons'] ) ? $row['number_of_persons'] : '' ),
		'previous_number_of_persons'           => adfoin_bookly_to_string( isset( $previous_row['number_of_persons'] ) ? $previous_row['number_of_persons'] : '' ),
		'units'                                => adfoin_bookly_to_string( isset( $row['units'] ) ? $row['units'] : '' ),
        'previous_units'                       => adfoin_bookly_to_string( isset( $previous_row['units'] ) ? $previous_row['units'] : '' ),
		'time_zone'                            => adfoin_bookly_to_string( isset( $row['time_zone'] ) ? $row['time_zone'] : '' ),
		'time_zone_offset'                     => adfoin_bookly_to_string( isset( $row['time_zone_offset'] ) ? $row['time_zone_offset'] : '' ),
		'custom_fields_json'                   => adfoin_bookly_json( $custom_fields ),
		'custom_fields_plain'                  => adfoin_bookly_custom_fields_plain( $custom_fields ),
		'previous_custom_fields_json'          => adfoin_bookly_json( $prev_custom_fields ),
		'previous_custom_fields_plain'         => adfoin_bookly_custom_fields_plain( $prev_custom_fields ),
		'extras_json'                          => adfoin_bookly_json( $extras ),
		'extras_plain'                         => adfoin_bookly_plain_from_array( $extras ),
		'previous_extras_json'                 => adfoin_bookly_json( $prev_extras ),
		'previous_extras_plain'                => adfoin_bookly_plain_from_array( $prev_extras ),
		'notes'                                => adfoin_bookly_to_string( isset( $row['notes'] ) ? $row['notes'] : '' ),
		'previous_notes'                       => adfoin_bookly_to_string( isset( $previous_row['notes'] ) ? $previous_row['notes'] : '' ),
		'payment_id'                           => adfoin_bookly_to_string( isset( $row['payment_id'] ) ? $row['payment_id'] : '' ),
		'payment_status'                       => adfoin_bookly_to_string( isset( $row['payment_status'] ) ? $row['payment_status'] : '' ),
		'payment_type'                         => adfoin_bookly_to_string( isset( $row['payment_type'] ) ? $row['payment_type'] : '' ),
		'payment_total'                        => adfoin_bookly_to_string( isset( $row['payment_total'] ) ? $row['payment_total'] : '' ),
		'payment_paid'                         => adfoin_bookly_to_string( isset( $row['payment_paid'] ) ? $row['payment_paid'] : '' ),
		'payment_gateway'                      => adfoin_bookly_to_string( isset( $row['payment_gateway'] ) ? $row['payment_gateway'] : '' ),
		'payment_details_json'                 => adfoin_bookly_json( adfoin_bookly_decode_json( isset( $row['payment_details'] ) ? $row['payment_details'] : '' ) ),
		'customer_id'                          => isset( $row['customer_id'] ) ? (int) $row['customer_id'] : 0,
		'customer_full_name'                   => adfoin_bookly_to_string( isset( $row['customer_full_name'] ) ? $row['customer_full_name'] : '' ),
		'customer_first_name'                  => adfoin_bookly_to_string( isset( $row['customer_first_name'] ) ? $row['customer_first_name'] : '' ),
		'customer_last_name'                   => adfoin_bookly_to_string( isset( $row['customer_last_name'] ) ? $row['customer_last_name'] : '' ),
		'customer_email'                       => adfoin_bookly_to_string( isset( $row['customer_email'] ) ? $row['customer_email'] : '' ),
		'customer_phone'                       => adfoin_bookly_to_string( isset( $row['customer_phone'] ) ? $row['customer_phone'] : '' ),
		'customer_birthday'                    => adfoin_bookly_to_string( isset( $row['customer_birthday'] ) ? $row['customer_birthday'] : '' ),
		'customer_country'                     => adfoin_bookly_to_string( isset( $row['customer_country'] ) ? $row['customer_country'] : '' ),
		'customer_state'                       => adfoin_bookly_to_string( isset( $row['customer_state'] ) ? $row['customer_state'] : '' ),
		'customer_postcode'                    => adfoin_bookly_to_string( isset( $row['customer_postcode'] ) ? $row['customer_postcode'] : '' ),
		'customer_city'                        => adfoin_bookly_to_string( isset( $row['customer_city'] ) ? $row['customer_city'] : '' ),
		'customer_street'                      => adfoin_bookly_to_string( isset( $row['customer_street'] ) ? $row['customer_street'] : '' ),
		'customer_street_number'               => adfoin_bookly_to_string( isset( $row['customer_street_number'] ) ? $row['customer_street_number'] : '' ),
		'customer_additional_address'          => adfoin_bookly_to_string( isset( $row['customer_additional_address'] ) ? $row['customer_additional_address'] : '' ),
		'customer_full_address'                => adfoin_bookly_to_string( isset( $row['customer_full_address'] ) ? $row['customer_full_address'] : '' ),
		'customer_notes'                       => adfoin_bookly_to_string( isset( $row['customer_notes'] ) ? $row['customer_notes'] : '' ),
		'customer_group_id'                    => adfoin_bookly_to_string( isset( $row['group_id'] ) ? $row['group_id'] : '' ),
		'staff_id'                             => $staff_id_current,
		'staff_name'                           => adfoin_bookly_to_string( $staff_name_current ),
		'staff_email'                          => adfoin_bookly_to_string( isset( $row['staff_email'] ) ? $row['staff_email'] : '' ),
		'staff_phone'                          => adfoin_bookly_to_string( isset( $row['staff_phone'] ) ? $row['staff_phone'] : '' ),
		'previous_staff_id'                    => $staff_id_previous,
		'previous_staff_name'                  => adfoin_bookly_to_string( $staff_name_previous ),
		'service_id'                           => $service_id_current,
		'service_name'                         => adfoin_bookly_to_string( $service_name_current ),
		'service_price'                        => adfoin_bookly_to_string( isset( $row['service_price'] ) ? $row['service_price'] : '' ),
		'service_duration'                     => adfoin_bookly_to_string( isset( $row['service_duration'] ) ? $row['service_duration'] : '' ),
		'previous_service_id'                  => $service_id_previous,
		'previous_service_name'                => adfoin_bookly_to_string( $service_name_previous ),
		'appointment_location_id'              => isset( $row['appointment_location_id'] ) ? (int) $row['appointment_location_id'] : ( isset( $appointment_after['location_id'] ) ? (int) $appointment_after['location_id'] : 0 ),
		'previous_appointment_location_id'     => isset( $appointment_before['location_id'] ) ? (int) $appointment_before['location_id'] : 0,
		'appointment_internal_note'            => adfoin_bookly_to_string( isset( $row['appointment_internal_note'] ) ? $row['appointment_internal_note'] : ( isset( $appointment_after['internal_note'] ) ? $appointment_after['internal_note'] : '' ) ),
		'previous_appointment_internal_note'   => adfoin_bookly_to_string( isset( $appointment_before['internal_note'] ) ? $appointment_before['internal_note'] : '' ),
		'appointment_custom_service_name'      => adfoin_bookly_to_string( isset( $row['appointment_custom_service_name'] ) ? $row['appointment_custom_service_name'] : ( isset( $appointment_after['custom_service_name'] ) ? $appointment_after['custom_service_name'] : '' ) ),
		'appointment_custom_service_price'     => adfoin_bookly_to_string( isset( $row['appointment_custom_service_price'] ) ? $row['appointment_custom_service_price'] : ( isset( $appointment_after['custom_service_price'] ) ? $appointment_after['custom_service_price'] : '' ) ),
		'appointment_start'                    => adfoin_bookly_to_string( isset( $row['appointment_start_date'] ) ? $row['appointment_start_date'] : ( isset( $appointment_after['start_date'] ) ? $appointment_after['start_date'] : '' ) ),
		'appointment_end'                      => adfoin_bookly_to_string( isset( $row['appointment_end_date'] ) ? $row['appointment_end_date'] : ( isset( $appointment_after['end_date'] ) ? $appointment_after['end_date'] : '' ) ),
		'appointment_start_timestamp'          => adfoin_bookly_timestamp( isset( $row['appointment_start_date'] ) ? $row['appointment_start_date'] : ( isset( $appointment_after['start_date'] ) ? $appointment_after['start_date'] : '' ) ),
		'appointment_end_timestamp'            => adfoin_bookly_timestamp( isset( $row['appointment_end_date'] ) ? $row['appointment_end_date'] : ( isset( $appointment_after['end_date'] ) ? $appointment_after['end_date'] : '' ) ),
		'previous_appointment_start'           => adfoin_bookly_to_string( isset( $appointment_before['start_date'] ) ? $appointment_before['start_date'] : '' ),
		'previous_appointment_end'             => adfoin_bookly_to_string( isset( $appointment_before['end_date'] ) ? $appointment_before['end_date'] : '' ),
		'previous_appointment_start_timestamp' => adfoin_bookly_timestamp( isset( $appointment_before['start_date'] ) ? $appointment_before['start_date'] : '' ),
		'previous_appointment_end_timestamp'   => adfoin_bookly_timestamp( isset( $appointment_before['end_date'] ) ? $appointment_before['end_date'] : '' ),
		'appointment_created_at'               => adfoin_bookly_to_string( isset( $appointment_after['created_at'] ) ? $appointment_after['created_at'] : '' ),
		'appointment_updated_at'               => adfoin_bookly_to_string( isset( $appointment_after['updated_at'] ) ? $appointment_after['updated_at'] : '' ),
		'appointment_created_from'             => adfoin_bookly_to_string( isset( $appointment_after['created_from'] ) ? $appointment_after['created_from'] : '' ),
		'appointment_extras_duration'          => adfoin_bookly_to_string( isset( $appointment_after['extras_duration'] ) ? $appointment_after['extras_duration'] : '' ),
		'appointment_online_meeting_provider'  => adfoin_bookly_to_string( isset( $row['appointment_online_meeting_provider'] ) ? $row['appointment_online_meeting_provider'] : ( isset( $appointment_after['online_meeting_provider'] ) ? $appointment_after['online_meeting_provider'] : '' ) ),
		'appointment_online_meeting_id'        => adfoin_bookly_to_string( isset( $row['appointment_online_meeting_id'] ) ? $row['appointment_online_meeting_id'] : ( isset( $appointment_after['online_meeting_id'] ) ? $appointment_after['online_meeting_id'] : '' ) ),
		'appointment_online_meeting_data_json' => adfoin_bookly_json( adfoin_bookly_decode_json( $online_data ) ),
		'series_id'                            => adfoin_bookly_to_string( isset( $row['series_id'] ) ? $row['series_id'] : '' ),
		'package_id'                           => adfoin_bookly_to_string( isset( $row['package_id'] ) ? $row['package_id'] : '' ),
		'collaborative_service_id'             => adfoin_bookly_to_string( isset( $row['collaborative_service_id'] ) ? $row['collaborative_service_id'] : '' ),
		'collaborative_token'                  => adfoin_bookly_to_string( isset( $row['collaborative_token'] ) ? $row['collaborative_token'] : '' ),
		'compound_service_id'                  => adfoin_bookly_to_string( isset( $row['compound_service_id'] ) ? $row['compound_service_id'] : '' ),
		'compound_token'                       => adfoin_bookly_to_string( isset( $row['compound_token'] ) ? $row['compound_token'] : '' ),
		'group_id'                             => adfoin_bookly_to_string( isset( $row['group_id'] ) ? $row['group_id'] : '' ),
	);

	return $payload;
}

/**
 * Fetch joined appointment/customer data.
 *
 * @param int $ca_id Customer appointment ID.
 * @return array<string,mixed>|null
 */
function adfoin_bookly_fetch_joined_row( $ca_id ) {
	global $wpdb;

	$table_ca      = $wpdb->prefix . 'bookly_customer_appointments';
	$table_app     = $wpdb->prefix . 'bookly_appointments';
	$table_cust    = $wpdb->prefix . 'bookly_customers';
	$table_staff   = $wpdb->prefix . 'bookly_staff';
	$table_service = $wpdb->prefix . 'bookly_services';
	$table_payment = $wpdb->prefix . 'bookly_payments';

	$sql = "
		SELECT
			ca.*,
			a.start_date AS appointment_start_date,
			a.end_date AS appointment_end_date,
			a.staff_id AS appointment_staff_id,
			a.service_id AS appointment_service_id,
			a.location_id AS appointment_location_id,
			a.internal_note AS appointment_internal_note,
			a.custom_service_name AS appointment_custom_service_name,
			a.custom_service_price AS appointment_custom_service_price,
			a.extras_duration AS appointment_extras_duration,
			a.created_from AS appointment_created_from,
			a.created_at AS appointment_created_at,
			a.updated_at AS appointment_updated_at,
			a.online_meeting_provider AS appointment_online_meeting_provider,
			a.online_meeting_id AS appointment_online_meeting_id,
			a.online_meeting_data AS appointment_online_meeting_data,
			c.full_name AS customer_full_name,
			c.first_name AS customer_first_name,
			c.last_name AS customer_last_name,
			c.email AS customer_email,
			c.phone AS customer_phone,
			c.country AS customer_country,
			c.state AS customer_state,
			c.postcode AS customer_postcode,
			c.city AS customer_city,
			c.street AS customer_street,
			c.street_number AS customer_street_number,
			c.additional_address AS customer_additional_address,
			c.full_address AS customer_full_address,
			c.notes AS customer_notes,
			c.birthday AS customer_birthday,
			c.group_id,
			st.full_name AS staff_full_name,
			st.email AS staff_email,
			st.phone AS staff_phone,
			s.title AS service_title,
			s.price AS service_price,
			s.duration AS service_duration,
			p.total AS payment_total,
			p.paid AS payment_paid,
			p.status AS payment_status,
			p.type AS payment_type,
			p.gateway AS payment_gateway,
			p.created_at AS payment_created_at,
			p.updated_at AS payment_updated_at,
			p.details AS payment_details
		FROM {$table_ca} AS ca
		LEFT JOIN {$table_app} AS a ON a.id = ca.appointment_id
		LEFT JOIN {$table_cust} AS c ON c.id = ca.customer_id
		LEFT JOIN {$table_staff} AS st ON st.id = a.staff_id
		LEFT JOIN {$table_service} AS s ON s.id = a.service_id
		LEFT JOIN {$table_payment} AS p ON p.id = ca.payment_id
		WHERE ca.id = %d
		LIMIT 1
	";

	$row = $wpdb->get_row( $wpdb->prepare( $sql, $ca_id ), ARRAY_A );

	return $row ?: null;
}

/**
 * Decode JSON string.
 *
 * @param mixed $value Value to decode.
 * @return mixed
 */
function adfoin_bookly_decode_json( $value ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		return $value;
	}

	if ( ! is_string( $value ) || $value === '' ) {
		return array();
	}

	$decoded = json_decode( $value, true );

	return $decoded === null ? array() : $decoded;
}

/**
 * Convert mixed value to string.
 *
 * @param mixed $value Value to convert.
 * @return string
 */
function adfoin_bookly_to_string( $value ) {
	if ( null === $value ) {
		return '';
	}
	if ( is_bool( $value ) ) {
		return $value ? 'yes' : 'no';
	}
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	return adfoin_bookly_json( $value );
}

/**
 * Encode value as JSON string.
 *
 * @param mixed $value Value to encode.
 * @return string
 */
function adfoin_bookly_json( $value ) {
	$encoded = wp_json_encode( $value );

	return false === $encoded ? '' : $encoded;
}

/**
 * Convert status to readable label.
 *
 * @param string $status Status.
 * @return string
 */
function adfoin_bookly_status_label( $status ) {
	$status = strtolower( (string) $status );
	if ( '' === $status ) {
		return '';
	}
	$status = str_replace( array( '_', '-' ), ' ', $status );

	return ucwords( $status );
}

/**
 * Convert date to timestamp.
 *
 * @param string $date Date string.
 * @return int
 */
function adfoin_bookly_timestamp( $date ) {
	$date = adfoin_bookly_to_string( $date );
	if ( '' === $date ) {
		return 0;
	}

	$timestamp = strtotime( $date );
	return $timestamp ? (int) $timestamp : 0;
}

/**
 * Render custom fields plain text.
 *
 * @param mixed $custom_fields Custom fields.
 * @return string
 */
function adfoin_bookly_custom_fields_plain( $custom_fields ) {
	if ( ! is_array( $custom_fields ) ) {
		return '';
	}

	$lines = array();
	foreach ( $custom_fields as $field ) {
		if ( is_array( $field ) ) {
			$label = '';
			if ( isset( $field['label'] ) && $field['label'] !== '' ) {
				$label = $field['label'];
			} elseif ( isset( $field['name'] ) && $field['name'] !== '' ) {
				$label = $field['name'];
			} elseif ( isset( $field['id'] ) ) {
				$label = 'field_' . $field['id'];
			}
			$value = '';
			if ( isset( $field['value'] ) ) {
				$value = $field['value'];
			} elseif ( isset( $field['answer'] ) ) {
				$value = $field['answer'];
			}
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'adfoin_bookly_to_string', $value ) );
			}
			$value = adfoin_bookly_to_string( $value );
			if ( $label !== '' ) {
				$lines[] = $label . ': ' . $value;
			} else {
				$lines[] = $value;
			}
		} else {
			$lines[] = adfoin_bookly_to_string( $field );
		}
	}

	return implode( "\n", array_filter( $lines ) );
}

/**
 * Render plain text from array.
 *
 * @param mixed $items Array of values.
 * @return string
 */
function adfoin_bookly_plain_from_array( $items ) {
	if ( ! is_array( $items ) ) {
		return '';
	}

	$lines = array();
	foreach ( $items as $key => $value ) {
		if ( is_array( $value ) ) {
			$lines[] = adfoin_bookly_plain_from_array( $value );
		} else {
			$label = is_string( $key ) ? $key : '';
			$text  = adfoin_bookly_to_string( $value );
			$lines[] = $label !== '' ? $label . ': ' . $text : $text;
		}
	}

	return implode( "\n", array_filter( $lines ) );
}

/**
 * Retrieve staff name.
 *
 * @param int $staff_id Staff ID.
 * @return string
 */
function adfoin_bookly_get_staff_name( $staff_id ) {
	static $cache = array();
	$staff_id     = (int) $staff_id;
	if ( $staff_id < 1 ) {
		return '';
	}
	if ( isset( $cache[ $staff_id ] ) ) {
		return $cache[ $staff_id ];
	}
	if ( ! class_exists( '\Bookly\Lib\Entities\Staff' ) ) {
		return '';
	}
	$staff = \Bookly\Lib\Entities\Staff::find( $staff_id );
	$cache[ $staff_id ] = $staff ? $staff->getFullName() : '';

	return $cache[ $staff_id ];
}

/**
 * Retrieve service name.
 *
 * @param int $service_id Service ID.
 * @return string
 */
function adfoin_bookly_get_service_name( $service_id ) {
	static $cache = array();
	$service_id   = (int) $service_id;
	if ( $service_id < 1 ) {
		return '';
	}
	if ( isset( $cache[ $service_id ] ) ) {
		return $cache[ $service_id ];
	}
	if ( ! class_exists( '\Bookly\Lib\Entities\Service' ) ) {
		return '';
	}
	$service = \Bookly\Lib\Entities\Service::find( $service_id );
	$cache[ $service_id ] = $service ? $service->getTitle() : '';

	return $cache[ $service_id ];
}
