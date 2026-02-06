<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Easy Appointments triggers.
 *
 * @param string $form_provider Integration key.
 * @return array<string,string>|void
 */
function adfoin_easyappointments_get_forms( $form_provider ) {
	if ( $form_provider !== 'easyappointments' ) {
		return;
	}

	return array(
		'appointmentCreated'       => __( 'Appointment Created', 'advanced-form-integration' ),
		'appointmentUpdated'       => __( 'Appointment Updated', 'advanced-form-integration' ),
		'appointmentDeleted'       => __( 'Appointment Deleted', 'advanced-form-integration' ),
		'appointmentStatusChanged' => __( 'Appointment Status Changed', 'advanced-form-integration' ),
		'appointmentPending'       => __( 'Appointment Pending', 'advanced-form-integration' ),
		'appointmentReserved'      => __( 'Appointment Reserved', 'advanced-form-integration' ),
		'appointmentAbandoned'     => __( 'Appointment Abandoned', 'advanced-form-integration' ),
		'appointmentCancelled'     => __( 'Appointment Cancelled', 'advanced-form-integration' ),
		'appointmentConfirmed'     => __( 'Appointment Confirmed', 'advanced-form-integration' ),
	);
}

/**
 * Get Easy Appointments mapped fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger identifier.
 * @return array<string,string>|void
 */
function adfoin_easyappointments_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'easyappointments' ) {
		return;
	}

	$fields = array(
		'trigger'                        => __( 'Trigger', 'advanced-form-integration' ),
		'appointment_id'                 => __( 'Appointment ID', 'advanced-form-integration' ),
		'status'                         => __( 'Status', 'advanced-form-integration' ),
		'status_label'                   => __( 'Status Label', 'advanced-form-integration' ),
		'previous_status'                => __( 'Previous Status', 'advanced-form-integration' ),
		'previous_status_label'          => __( 'Previous Status Label', 'advanced-form-integration' ),
		'location_id'                    => __( 'Location ID', 'advanced-form-integration' ),
		'location_name'                  => __( 'Location Name', 'advanced-form-integration' ),
		'location_address'               => __( 'Location Address', 'advanced-form-integration' ),
		'location_location'              => __( 'Location Reference', 'advanced-form-integration' ),
		'previous_location_id'           => __( 'Previous Location ID', 'advanced-form-integration' ),
		'previous_location_name'         => __( 'Previous Location Name', 'advanced-form-integration' ),
		'previous_location_address'      => __( 'Previous Location Address', 'advanced-form-integration' ),
		'service_id'                     => __( 'Service ID', 'advanced-form-integration' ),
		'service_name'                   => __( 'Service Name', 'advanced-form-integration' ),
		'service_duration'               => __( 'Service Duration (Minutes)', 'advanced-form-integration' ),
		'service_price'                  => __( 'Service Price', 'advanced-form-integration' ),
		'previous_service_id'            => __( 'Previous Service ID', 'advanced-form-integration' ),
		'previous_service_name'          => __( 'Previous Service Name', 'advanced-form-integration' ),
		'previous_service_duration'      => __( 'Previous Service Duration (Minutes)', 'advanced-form-integration' ),
		'previous_service_price'         => __( 'Previous Service Price', 'advanced-form-integration' ),
		'worker_id'                      => __( 'Worker ID', 'advanced-form-integration' ),
		'worker_name'                    => __( 'Worker Name', 'advanced-form-integration' ),
		'worker_email'                   => __( 'Worker Email', 'advanced-form-integration' ),
		'worker_phone'                   => __( 'Worker Phone', 'advanced-form-integration' ),
		'previous_worker_id'             => __( 'Previous Worker ID', 'advanced-form-integration' ),
		'previous_worker_name'           => __( 'Previous Worker Name', 'advanced-form-integration' ),
		'previous_worker_email'          => __( 'Previous Worker Email', 'advanced-form-integration' ),
		'previous_worker_phone'          => __( 'Previous Worker Phone', 'advanced-form-integration' ),
		'date'                           => __( 'Date', 'advanced-form-integration' ),
        'previous_date'                  => __( 'Previous Date', 'advanced-form-integration' ),
		'start_time'                     => __( 'Start Time', 'advanced-form-integration' ),
		'end_time'                       => __( 'End Time', 'advanced-form-integration' ),
		'end_date'                       => __( 'End Date', 'advanced-form-integration' ),
		'previous_start_time'            => __( 'Previous Start Time', 'advanced-form-integration' ),
		'previous_end_time'              => __( 'Previous End Time', 'advanced-form-integration' ),
		'previous_end_date'              => __( 'Previous End Date', 'advanced-form-integration' ),
		'start_timestamp'                => __( 'Start Timestamp', 'advanced-form-integration' ),
		'end_timestamp'                  => __( 'End Timestamp', 'advanced-form-integration' ),
		'previous_start_timestamp'       => __( 'Previous Start Timestamp', 'advanced-form-integration' ),
		'previous_end_timestamp'         => __( 'Previous End Timestamp', 'advanced-form-integration' ),
		'created_at'                     => __( 'Created At', 'advanced-form-integration' ),
		'created_timestamp'              => __( 'Created Timestamp', 'advanced-form-integration' ),
		'price'                          => __( 'Price', 'advanced-form-integration' ),
		'previous_price'                 => __( 'Previous Price', 'advanced-form-integration' ),
		'customer_name'                  => __( 'Customer Name', 'advanced-form-integration' ),
		'customer_email'                 => __( 'Customer Email', 'advanced-form-integration' ),
		'customer_phone'                 => __( 'Customer Phone', 'advanced-form-integration' ),
		'customer_description'           => __( 'Customer Description', 'advanced-form-integration' ),
		'previous_customer_name'         => __( 'Previous Customer Name', 'advanced-form-integration' ),
		'previous_customer_email'        => __( 'Previous Customer Email', 'advanced-form-integration' ),
		'previous_customer_phone'        => __( 'Previous Customer Phone', 'advanced-form-integration' ),
		'previous_customer_description'  => __( 'Previous Customer Description', 'advanced-form-integration' ),
		'customer_id'                    => __( 'Customer ID', 'advanced-form-integration' ),
		'customer_record_name'           => __( 'Customer Record Name', 'advanced-form-integration' ),
		'customer_record_email'          => __( 'Customer Record Email', 'advanced-form-integration' ),
		'customer_record_mobile'         => __( 'Customer Record Mobile', 'advanced-form-integration' ),
		'customer_record_address'        => __( 'Customer Record Address', 'advanced-form-integration' ),
		'previous_customer_record_name'  => __( 'Previous Customer Record Name', 'advanced-form-integration' ),
		'previous_customer_record_email' => __( 'Previous Customer Record Email', 'advanced-form-integration' ),
		'previous_customer_record_mobile'=> __( 'Previous Customer Record Mobile', 'advanced-form-integration' ),
		'previous_customer_record_address'=> __( 'Previous Customer Record Address', 'advanced-form-integration' ),
		'ip_address'                     => __( 'IP Address', 'advanced-form-integration' ),
		'previous_ip_address'            => __( 'Previous IP Address', 'advanced-form-integration' ),
		'session_key'                    => __( 'Session Key', 'advanced-form-integration' ),
		'previous_session_key'           => __( 'Previous Session Key', 'advanced-form-integration' ),
		'recurrence_id'                  => __( 'Recurrence ID', 'advanced-form-integration' ),
		'meta_fields_json'               => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
		'meta_fields_plain'              => __( 'Custom Fields (Text)', 'advanced-form-integration' ),
		'previous_meta_fields_json'      => __( 'Previous Custom Fields (JSON)', 'advanced-form-integration' ),
		'previous_meta_fields_plain'     => __( 'Previous Custom Fields (Text)', 'advanced-form-integration' ),
	);

	global $wpdb;
	$meta_table = $wpdb->prefix . 'ea_meta_fields';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta_table ) ) === $meta_table ) {
		$meta_slugs = $wpdb->get_col( "SELECT slug FROM {$meta_table}" );
		foreach ( (array) $meta_slugs as $slug ) {
			$normalized = adfoin_easyappointments_normalize_meta_key( $slug );
			$label      = sprintf( __( 'Custom Field: %s', 'advanced-form-integration' ), $slug );
			$fields[ "meta_{$normalized}" ]           = $label;
			$fields[ "previous_meta_{$normalized}" ]  = sprintf( __( 'Previous %s', 'advanced-form-integration' ), $label );
		}
	}

	return $fields;
}

add_action( 'plugins_loaded', 'adfoin_easyappointments_bootstrap', 20 );

/**
 * Bootstrap integration.
 */
function adfoin_easyappointments_bootstrap() {
	global $wpdb;

	$table = $wpdb->prefix . 'ea_appointments';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return;
	}

	add_action( 'ea_new_app', 'adfoin_easyappointments_handle_new_app', 999, 3 );
	add_action( 'ea_edit_app', 'adfoin_easyappointments_handle_edit_app', 999 );

	$hooks = array(
		'wp_ajax_ea_appointment',
		'wp_ajax_ea_final_appointment',
		'wp_ajax_nopriv_ea_final_appointment',
		'wp_ajax_ea_cancel_appointment',
		'wp_ajax_nopriv_ea_cancel_appointment',
		'wp_ajax_cancel_selected_appointments',
		'wp_ajax_delete_selected_appointment',
	);

	foreach ( $hooks as $hook ) {
		add_action( $hook, 'adfoin_easyappointments_watch_ajax', 0 );
	}
}

/**
 * Track ajax entry points for later inspection.
 */
function adfoin_easyappointments_watch_ajax() {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	if ( ! $action ) {
		return;
	}

	adfoin_easyappointments_prepare_watchers( $action );
}

/**
 * Internal state holder.
 *
 * @return array<string,mixed>
 */
function &adfoin_easyappointments_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'max_id'              => null,
			'before'              => array(),
			'processed'           => array(),
			'shutdown_registered' => false,
		);
	}

	return $state;
}

/**
 * Prepare watcher bookkeeping for current action.
 *
 * @param string $action Current ajax action.
 */
function adfoin_easyappointments_prepare_watchers( $action ) {
	global $wpdb;

	$state = &adfoin_easyappointments_state();

	if ( null === $state['max_id'] ) {
		$state['max_id'] = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}ea_appointments" );
	}

	switch ( $action ) {
		case 'ea_appointment':
			$method = 'POST';
			if ( isset( $_REQUEST['_method'] ) ) {
				$method = strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['_method'] ) ) );
			} elseif ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
				$method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
			}
			$appointment_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
			if ( in_array( $method, array( 'PUT', 'DELETE' ), true ) && $appointment_id ) {
				adfoin_easyappointments_snapshot_store( $appointment_id );
			}
			break;

		case 'ea_final_appointment':
			if ( isset( $_REQUEST['id'] ) ) {
				$appointment_id = absint( $_REQUEST['id'] );
				if ( $appointment_id ) {
					adfoin_easyappointments_snapshot_store( $appointment_id );
				}
			}
			break;

		case 'ea_cancel_appointment':
			if ( isset( $_REQUEST['id'] ) ) {
				$appointment_id = absint( $_REQUEST['id'] );
				if ( $appointment_id ) {
					adfoin_easyappointments_snapshot_store( $appointment_id );
				}
			}
			break;

		case 'cancel_selected_appointments':
		case 'delete_selected_appointment':
			if ( isset( $_POST['appointments'] ) && is_array( $_POST['appointments'] ) ) {
				foreach ( $_POST['appointments'] as $appointment_id ) {
					$appointment_id = absint( $appointment_id );
					if ( $appointment_id ) {
						adfoin_easyappointments_snapshot_store( $appointment_id );
					}
				}
			}
			break;
	}

	if ( ! $state['shutdown_registered'] ) {
		$state['shutdown_registered'] = true;
		register_shutdown_function( 'adfoin_easyappointments_handle_shutdown' );
	}
}

/**
 * Capture a snapshot for the given appointment id.
 *
 * @param int $appointment_id Appointment ID.
 */
function adfoin_easyappointments_snapshot_store( $appointment_id ) {
	$state = &adfoin_easyappointments_state();
	if ( isset( $state['before'][ $appointment_id ] ) ) {
		return;
	}

	$snapshot = adfoin_easyappointments_fetch_snapshot( $appointment_id );
	if ( $snapshot ) {
		$state['before'][ $appointment_id ] = $snapshot;
	}
}

/**
 * Handle ea_new_app hook.
 *
 * @param int   $appointment_id Appointment ID.
 * @param array $data           Raw appointment payload.
 * @param bool  $from_customer  Flag indicating booking source.
 */
function adfoin_easyappointments_handle_new_app( $appointment_id, $data, $from_customer ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$appointment_id = absint( $appointment_id );
	if ( ! $appointment_id ) {
		return;
	}

	$state       = &adfoin_easyappointments_state();
	$before_data = isset( $state['before'][ $appointment_id ] ) ? $state['before'][ $appointment_id ]['data'] : null;
	$current     = adfoin_easyappointments_fetch_snapshot( $appointment_id );

	if ( ! $current ) {
		return;
	}

	adfoin_easyappointments_process_change( 'created', $current['data'], $before_data );
	$state['processed'][ $appointment_id ] = true;
	unset( $state['before'][ $appointment_id ] );
}

/**
 * Handle ea_edit_app hook.
 *
 * @param int $appointment_id Appointment ID.
 */
function adfoin_easyappointments_handle_edit_app( $appointment_id ) {
	$appointment_id = absint( $appointment_id );
	if ( ! $appointment_id ) {
		return;
	}

	$state       = &adfoin_easyappointments_state();
	$before_data = isset( $state['before'][ $appointment_id ] ) ? $state['before'][ $appointment_id ]['data'] : null;
	$current     = adfoin_easyappointments_fetch_snapshot( $appointment_id );

	if ( ! $current ) {
		return;
	}

	adfoin_easyappointments_process_change( 'updated', $current['data'], $before_data );
	$state['processed'][ $appointment_id ] = true;
	unset( $state['before'][ $appointment_id ] );
}

/**
 * Shutdown handler compares stored snapshots with latest DB state.
 */
function adfoin_easyappointments_handle_shutdown() {
	global $wpdb;

	$state = &adfoin_easyappointments_state();

	foreach ( $state['before'] as $appointment_id => $snapshot ) {
		if ( isset( $state['processed'][ $appointment_id ] ) ) {
			continue;
		}

		$current = adfoin_easyappointments_fetch_snapshot( $appointment_id );
		if ( $current ) {
			$before_data = $snapshot['data'];
			$after_data  = $current['data'];
			$hash_before = adfoin_easyappointments_snapshot_hash( $before_data );
			$hash_after  = adfoin_easyappointments_snapshot_hash( $after_data );

			if ( $hash_before !== $hash_after ) {
				adfoin_easyappointments_process_change( 'updated', $after_data, $before_data );
			}
		} else {
			adfoin_easyappointments_process_change( 'deleted', null, $snapshot['data'] );
		}
	}

	if ( null !== $state['max_id'] ) {
		$table    = $wpdb->prefix . 'ea_appointments';
		$new_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE id > %d", $state['max_id'] ) );
		foreach ( (array) $new_ids as $appointment_id ) {
			$appointment_id = (int) $appointment_id;
			if ( isset( $state['processed'][ $appointment_id ] ) ) {
				continue;
			}
			$current = adfoin_easyappointments_fetch_snapshot( $appointment_id );
			if ( $current ) {
				adfoin_easyappointments_process_change( 'created', $current['data'], null );
			}
		}
	}

	$state = array(
		'max_id'              => null,
		'before'              => array(),
		'processed'           => array(),
		'shutdown_registered' => false,
	);
}

/**
 * Process a detected change.
 *
 * @param string                     $context Change context.
 * @param array<string,mixed>|null   $after   Current snapshot.
 * @param array<string,mixed>|null   $before  Previous snapshot.
 */
function adfoin_easyappointments_process_change( $context, $after, $before ) {
	$after   = is_array( $after ) ? $after : null;
	$before  = is_array( $before ) ? $before : null;
	$payload = adfoin_easyappointments_build_payload( $after, $before );

	if ( empty( $payload ) ) {
		return;
	}

	$current_status  = isset( $after['status'] ) ? (string) $after['status'] : '';
	$previous_status = isset( $before['status'] ) ? (string) $before['status'] : '';
	$status_changed  = $current_status !== $previous_status;

	$hash_before_no_status = adfoin_easyappointments_snapshot_hash( $before, array( 'status' ) );
	$hash_after_no_status  = adfoin_easyappointments_snapshot_hash( $after, array( 'status' ) );
	$fields_changed        = $hash_before_no_status !== $hash_after_no_status;

	if ( 'created' === $context ) {
		$send_created = empty( $before );
		if ( ! $send_created && 'reservation' === $previous_status && $current_status && 'reservation' !== $current_status ) {
			$send_created = true;
		}

		if ( $send_created ) {
			adfoin_easyappointments_send( 'appointmentCreated', $payload );
		} else {
			$context = 'updated';
		}
	}

	if ( 'deleted' === $context ) {
		adfoin_easyappointments_send( 'appointmentDeleted', $payload );
		return;
	}

	if ( 'updated' === $context && $fields_changed ) {
		adfoin_easyappointments_send( 'appointmentUpdated', $payload );
	}

	if ( $status_changed && $current_status ) {
		adfoin_easyappointments_send( 'appointmentStatusChanged', $payload );
		$map = array(
			'pending'     => 'appointmentPending',
			'reservation' => 'appointmentReserved',
			'abandoned'   => 'appointmentAbandoned',
			'canceled'    => 'appointmentCancelled',
			'cancelled'   => 'appointmentCancelled',
			'confirmed'   => 'appointmentConfirmed',
		);

		$key = strtolower( $current_status );
		if ( isset( $map[ $key ] ) ) {
			adfoin_easyappointments_send( $map[ $key ], $payload );
		}
	}
}

/**
 * Build payload array.
 *
 * @param array<string,mixed>|null $current Current snapshot.
 * @param array<string,mixed>|null $previous Previous snapshot.
 * @return array<string,mixed>
 */
function adfoin_easyappointments_build_payload( $current, $previous ) {
	if ( ! $current && ! $previous ) {
		return array();
	}

	$current = is_array( $current ) ? $current : array();
	$previous = is_array( $previous ) ? $previous : array();

	$meta_current  = isset( $current['meta'] ) && is_array( $current['meta'] ) ? $current['meta'] : array();
	$meta_previous = isset( $previous['meta'] ) && is_array( $previous['meta'] ) ? $previous['meta'] : array();

	ksort( $meta_current );
	ksort( $meta_previous );

	$meta_plain_current  = adfoin_easyappointments_plain_from_assoc( $meta_current );
	$meta_plain_previous = adfoin_easyappointments_plain_from_assoc( $meta_previous );

	$customer_current  = isset( $current['customer_record'] ) && is_array( $current['customer_record'] ) ? $current['customer_record'] : array();
	$customer_previous = isset( $previous['customer_record'] ) && is_array( $previous['customer_record'] ) ? $previous['customer_record'] : array();

	$appointment_id = isset( $current['id'] ) ? (int) $current['id'] : ( isset( $previous['id'] ) ? (int) $previous['id'] : 0 );
	$date           = isset( $current['date'] ) ? $current['date'] : ( isset( $previous['date'] ) ? $previous['date'] : '' );
	$start_time     = isset( $current['start'] ) ? $current['start'] : ( isset( $previous['start'] ) ? $previous['start'] : '' );
	$end_time       = isset( $current['end'] ) ? $current['end'] : ( isset( $previous['end'] ) ? $previous['end'] : '' );
	$end_date       = isset( $current['end_date'] ) ? $current['end_date'] : ( isset( $previous['end_date'] ) ? $previous['end_date'] : '' );

	$payload = array(
		'appointment_id'                 => $appointment_id,
		'status'                         => adfoin_easyappointments_to_string( isset( $current['status'] ) ? $current['status'] : '' ),
		'status_label'                   => adfoin_easyappointments_status_label( isset( $current['status'] ) ? $current['status'] : '' ),
		'previous_status'                => adfoin_easyappointments_to_string( isset( $previous['status'] ) ? $previous['status'] : '' ),
		'previous_status_label'          => adfoin_easyappointments_status_label( isset( $previous['status'] ) ? $previous['status'] : '' ),
		'location_id'                    => isset( $current['location'] ) ? (int) $current['location'] : ( isset( $previous['location'] ) ? (int) $previous['location'] : 0 ),
		'location_name'                  => adfoin_easyappointments_to_string( isset( $current['location_name'] ) ? $current['location_name'] : ( isset( $previous['location_name'] ) ? $previous['location_name'] : '' ) ),
		'location_address'               => adfoin_easyappointments_to_string( isset( $current['location_address'] ) ? $current['location_address'] : ( isset( $previous['location_address'] ) ? $previous['location_address'] : '' ) ),
		'location_location'              => adfoin_easyappointments_to_string( isset( $current['location_location'] ) ? $current['location_location'] : ( isset( $previous['location_location'] ) ? $previous['location_location'] : '' ) ),
		'previous_location_id'           => isset( $previous['location'] ) ? (int) $previous['location'] : 0,
		'previous_location_name'         => adfoin_easyappointments_to_string( isset( $previous['location_name'] ) ? $previous['location_name'] : '' ),
		'previous_location_address'      => adfoin_easyappointments_to_string( isset( $previous['location_address'] ) ? $previous['location_address'] : '' ),
		'service_id'                     => isset( $current['service'] ) ? (int) $current['service'] : ( isset( $previous['service'] ) ? (int) $previous['service'] : 0 ),
		'service_name'                   => adfoin_easyappointments_to_string( isset( $current['service_name'] ) ? $current['service_name'] : ( isset( $previous['service_name'] ) ? $previous['service_name'] : '' ) ),
		'service_duration'               => adfoin_easyappointments_to_string( isset( $current['service_duration'] ) ? $current['service_duration'] : ( isset( $previous['service_duration'] ) ? $previous['service_duration'] : '' ) ),
		'service_price'                  => adfoin_easyappointments_to_string( isset( $current['service_price'] ) ? $current['service_price'] : ( isset( $previous['service_price'] ) ? $previous['service_price'] : '' ) ),
		'previous_service_id'            => isset( $previous['service'] ) ? (int) $previous['service'] : 0,
		'previous_service_name'          => adfoin_easyappointments_to_string( isset( $previous['service_name'] ) ? $previous['service_name'] : '' ),
		'previous_service_duration'      => adfoin_easyappointments_to_string( isset( $previous['service_duration'] ) ? $previous['service_duration'] : '' ),
		'previous_service_price'         => adfoin_easyappointments_to_string( isset( $previous['service_price'] ) ? $previous['service_price'] : '' ),
		'worker_id'                      => isset( $current['worker'] ) ? (int) $current['worker'] : ( isset( $previous['worker'] ) ? (int) $previous['worker'] : 0 ),
		'worker_name'                    => adfoin_easyappointments_to_string( isset( $current['worker_name'] ) ? $current['worker_name'] : ( isset( $previous['worker_name'] ) ? $previous['worker_name'] : '' ) ),
		'worker_email'                   => adfoin_easyappointments_to_string( isset( $current['worker_email'] ) ? $current['worker_email'] : ( isset( $previous['worker_email'] ) ? $previous['worker_email'] : '' ) ),
		'worker_phone'                   => adfoin_easyappointments_to_string( isset( $current['worker_phone'] ) ? $current['worker_phone'] : ( isset( $previous['worker_phone'] ) ? $previous['worker_phone'] : '' ) ),
		'previous_worker_id'             => isset( $previous['worker'] ) ? (int) $previous['worker'] : 0,
		'previous_worker_name'           => adfoin_easyappointments_to_string( isset( $previous['worker_name'] ) ? $previous['worker_name'] : '' ),
		'previous_worker_email'          => adfoin_easyappointments_to_string( isset( $previous['worker_email'] ) ? $previous['worker_email'] : '' ),
		'previous_worker_phone'          => adfoin_easyappointments_to_string( isset( $previous['worker_phone'] ) ? $previous['worker_phone'] : '' ),
		'date'                           => adfoin_easyappointments_to_string( $date ),
		'previous_date'                  => adfoin_easyappointments_to_string( isset( $previous['date'] ) ? $previous['date'] : '' ),
		'start_time'                     => adfoin_easyappointments_to_string( $start_time ),
		'end_time'                       => adfoin_easyappointments_to_string( $end_time ),
		'end_date'                       => adfoin_easyappointments_to_string( $end_date ),
		'previous_start_time'            => adfoin_easyappointments_to_string( isset( $previous['start'] ) ? $previous['start'] : '' ),
		'previous_end_time'              => adfoin_easyappointments_to_string( isset( $previous['end'] ) ? $previous['end'] : '' ),
		'previous_end_date'              => adfoin_easyappointments_to_string( isset( $previous['end_date'] ) ? $previous['end_date'] : '' ),
		'start_timestamp'                => adfoin_easyappointments_timestamp( $date, $start_time ),
		'end_timestamp'                  => adfoin_easyappointments_timestamp( $end_date ? $end_date : $date, $end_time ),
		'previous_start_timestamp'       => adfoin_easyappointments_timestamp( isset( $previous['date'] ) ? $previous['date'] : '', isset( $previous['start'] ) ? $previous['start'] : '' ),
		'previous_end_timestamp'         => adfoin_easyappointments_timestamp( isset( $previous['end_date'] ) && $previous['end_date'] ? $previous['end_date'] : ( isset( $previous['date'] ) ? $previous['date'] : '' ), isset( $previous['end'] ) ? $previous['end'] : '' ),
		'created_at'                     => adfoin_easyappointments_to_string( isset( $current['created'] ) ? $current['created'] : ( isset( $previous['created'] ) ? $previous['created'] : '' ) ),
		'created_timestamp'              => adfoin_easyappointments_datetime_timestamp( isset( $current['created'] ) ? $current['created'] : ( isset( $previous['created'] ) ? $previous['created'] : '' ) ),
		'price'                          => adfoin_easyappointments_to_string( isset( $current['price'] ) ? $current['price'] : ( isset( $previous['price'] ) ? $previous['price'] : '' ) ),
		'previous_price'                 => adfoin_easyappointments_to_string( isset( $previous['price'] ) ? $previous['price'] : '' ),
		'customer_name'                  => adfoin_easyappointments_to_string( isset( $current['name'] ) ? $current['name'] : ( isset( $previous['name'] ) ? $previous['name'] : '' ) ),
		'customer_email'                 => adfoin_easyappointments_to_string( isset( $current['email'] ) ? $current['email'] : ( isset( $previous['email'] ) ? $previous['email'] : '' ) ),
		'customer_phone'                 => adfoin_easyappointments_to_string( isset( $current['phone'] ) ? $current['phone'] : ( isset( $previous['phone'] ) ? $previous['phone'] : '' ) ),
		'customer_description'           => adfoin_easyappointments_to_string( isset( $current['description'] ) ? $current['description'] : ( isset( $previous['description'] ) ? $previous['description'] : '' ) ),
		'previous_customer_name'         => adfoin_easyappointments_to_string( isset( $previous['name'] ) ? $previous['name'] : '' ),
		'previous_customer_email'        => adfoin_easyappointments_to_string( isset( $previous['email'] ) ? $previous['email'] : '' ),
		'previous_customer_phone'        => adfoin_easyappointments_to_string( isset( $previous['phone'] ) ? $previous['phone'] : '' ),
		'previous_customer_description'  => adfoin_easyappointments_to_string( isset( $previous['description'] ) ? $previous['description'] : '' ),
		'customer_id'                    => isset( $current['customer_id'] ) ? (int) $current['customer_id'] : ( isset( $previous['customer_id'] ) ? (int) $previous['customer_id'] : 0 ),
		'customer_record_name'           => adfoin_easyappointments_to_string( isset( $customer_current['name'] ) ? $customer_current['name'] : ( isset( $customer_previous['name'] ) ? $customer_previous['name'] : '' ) ),
		'customer_record_email'          => adfoin_easyappointments_to_string( isset( $customer_current['email'] ) ? $customer_current['email'] : ( isset( $customer_previous['email'] ) ? $customer_previous['email'] : '' ) ),
		'customer_record_mobile'         => adfoin_easyappointments_to_string( isset( $customer_current['mobile'] ) ? $customer_current['mobile'] : ( isset( $customer_previous['mobile'] ) ? $customer_previous['mobile'] : '' ) ),
		'customer_record_address'        => adfoin_easyappointments_to_string( isset( $customer_current['address'] ) ? $customer_current['address'] : ( isset( $customer_previous['address'] ) ? $customer_previous['address'] : '' ) ),
		'previous_customer_record_name'  => adfoin_easyappointments_to_string( isset( $customer_previous['name'] ) ? $customer_previous['name'] : '' ),
		'previous_customer_record_email' => adfoin_easyappointments_to_string( isset( $customer_previous['email'] ) ? $customer_previous['email'] : '' ),
		'previous_customer_record_mobile'=> adfoin_easyappointments_to_string( isset( $customer_previous['mobile'] ) ? $customer_previous['mobile'] : '' ),
		'previous_customer_record_address'=> adfoin_easyappointments_to_string( isset( $customer_previous['address'] ) ? $customer_previous['address'] : '' ),
		'ip_address'                     => adfoin_easyappointments_to_string( isset( $current['ip'] ) ? $current['ip'] : ( isset( $previous['ip'] ) ? $previous['ip'] : '' ) ),
		'previous_ip_address'            => adfoin_easyappointments_to_string( isset( $previous['ip'] ) ? $previous['ip'] : '' ),
		'session_key'                    => adfoin_easyappointments_to_string( isset( $current['session'] ) ? $current['session'] : ( isset( $previous['session'] ) ? $previous['session'] : '' ) ),
		'previous_session_key'           => adfoin_easyappointments_to_string( isset( $previous['session'] ) ? $previous['session'] : '' ),
		'recurrence_id'                  => adfoin_easyappointments_to_string( isset( $current['recurrence_id'] ) ? $current['recurrence_id'] : ( isset( $previous['recurrence_id'] ) ? $previous['recurrence_id'] : '' ) ),
		'meta_fields_json'               => adfoin_easyappointments_json( $meta_current ),
		'meta_fields_plain'              => $meta_plain_current,
		'previous_meta_fields_json'      => adfoin_easyappointments_json( $meta_previous ),
		'previous_meta_fields_plain'     => $meta_plain_previous,
	);

	foreach ( $meta_current as $slug => $value ) {
		$key = 'meta_' . adfoin_easyappointments_normalize_meta_key( $slug );
		$payload[ $key ] = adfoin_easyappointments_to_string( $value );
	}

	foreach ( $meta_previous as $slug => $value ) {
		$key = 'meta_' . adfoin_easyappointments_normalize_meta_key( $slug );
		$payload[ "previous_{$key}" ] = adfoin_easyappointments_to_string( $value );
	}

	return $payload;
}

/**
 * Send payload through AFI integration.
 *
 * @param string                $trigger Trigger name.
 * @param array<string,mixed>   $payload Payload data.
 */
function adfoin_easyappointments_send( $trigger, $payload ) {
	if ( empty( $payload ) ) {
		return;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'easyappointments', $trigger );

	if ( empty( $records ) ) {
		return;
	}

	$data            = $payload;
	$data['trigger'] = $trigger;
	$integration->send( $records, $data );
}

/**
 * Fetch snapshot info.
 *
 * @param int $appointment_id Appointment ID.
 * @return array<string,mixed>|null
 */
function adfoin_easyappointments_fetch_snapshot( $appointment_id ) {
	$data = adfoin_easyappointments_get_full_appointment( $appointment_id );
	if ( empty( $data ) ) {
		return null;
	}

	return adfoin_easyappointments_prepare_snapshot( $data );
}

/**
 * Normalize snapshot structure with hashes.
 *
 * @param array<string,mixed> $data Appointment data.
 * @return array<string,mixed>
 */
function adfoin_easyappointments_prepare_snapshot( array $data ) {
	if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
		ksort( $data['meta'] );
	}
	if ( isset( $data['customer_record'] ) && is_array( $data['customer_record'] ) ) {
		ksort( $data['customer_record'] );
	}

	return array(
		'data' => $data,
		'hash' => adfoin_easyappointments_snapshot_hash( $data ),
	);
}

/**
 * Retrieve full appointment data.
 *
 * @param int $appointment_id Appointment ID.
 * @return array<string,mixed>
 */
function adfoin_easyappointments_get_full_appointment( $appointment_id ) {
	global $wpdb;

	$appointment_id = absint( $appointment_id );
	if ( ! $appointment_id ) {
		return array();
	}

	$table_app      = $wpdb->prefix . 'ea_appointments';
	$table_services = $wpdb->prefix . 'ea_services';
	$table_workers  = $wpdb->prefix . 'ea_staff';
	$table_locations= $wpdb->prefix . 'ea_locations';
	$table_meta     = $wpdb->prefix . 'ea_meta_fields';
	$table_fields   = $wpdb->prefix . 'ea_fields';
	$table_customers= $wpdb->prefix . 'ea_customers';

	$sql = $wpdb->prepare(
		"SELECT 
			a.*,
			s.name  AS service_name,
			s.duration AS service_duration,
			s.price AS service_price,
			w.name AS worker_name,
			w.email AS worker_email,
			w.phone AS worker_phone,
			l.name AS location_name,
			l.address AS location_address,
			l.location AS location_location
		FROM {$table_app} a
		LEFT JOIN {$table_services} s ON s.id = a.service
		LEFT JOIN {$table_workers} w ON w.id = a.worker
		LEFT JOIN {$table_locations} l ON l.id = a.location
		WHERE a.id = %d
		LIMIT 1",
		$appointment_id
	);

	$result = $wpdb->get_row( $sql, ARRAY_A );
	if ( ! $result ) {
		return array();
	}

	$meta_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT m.slug, f.value FROM {$table_meta} m INNER JOIN {$table_fields} f ON m.id = f.field_id WHERE f.app_id = %d",
			$appointment_id
		),
		ARRAY_A
	);

	$meta = array();
	foreach ( (array) $meta_rows as $row ) {
		$slug = isset( $row['slug'] ) ? (string) $row['slug'] : '';
		if ( '' !== $slug ) {
			$meta[ $slug ] = $row['value'];
		}
	}

	$customer_record = array();
	if ( ! empty( $result['customer_id'] ) ) {
		$customer_record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_customers} WHERE id = %d",
				(int) $result['customer_id']
			),
			ARRAY_A
		);
		if ( ! is_array( $customer_record ) ) {
			$customer_record = array();
		}
	}

	$result['meta']            = $meta;
	$result['customer_record'] = $customer_record;

	return $result;
}

/**
 * Generate hash for snapshot data.
 *
 * @param array<string,mixed>|null $data Snapshot.
 * @param array<int,string>        $ignore_keys Keys to ignore.
 * @return string
 */
function adfoin_easyappointments_snapshot_hash( $data, $ignore_keys = array() ) {
	if ( ! is_array( $data ) ) {
		return '';
	}

	$clone = $data;

	foreach ( (array) $ignore_keys as $key ) {
		unset( $clone[ $key ] );
	}

	if ( isset( $clone['meta'] ) && is_array( $clone['meta'] ) ) {
		ksort( $clone['meta'] );
	}
	if ( isset( $clone['customer_record'] ) && is_array( $clone['customer_record'] ) ) {
		ksort( $clone['customer_record'] );
	}

	ksort( $clone );

	return md5( wp_json_encode( $clone ) );
}

/**
 * Convert heterogenous value to string.
 *
 * @param mixed $value Value.
 * @return string
 */
function adfoin_easyappointments_to_string( $value ) {
	if ( null === $value ) {
		return '';
	}
	if ( is_bool( $value ) ) {
		return $value ? 'yes' : 'no';
	}
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	return adfoin_easyappointments_json( $value );
}

/**
 * Encode value as JSON string.
 *
 * @param mixed $value Source value.
 * @return string
 */
function adfoin_easyappointments_json( $value ) {
	$encoded = wp_json_encode( $value );
	return false === $encoded ? '' : $encoded;
}

/**
 * Convert meta array to plain text.
 *
 * @param array<string,mixed> $meta Meta map.
 * @return string
 */
function adfoin_easyappointments_plain_from_assoc( $meta ) {
	if ( empty( $meta ) || ! is_array( $meta ) ) {
		return '';
	}

	$lines = array();
	foreach ( $meta as $key => $value ) {
		$lines[] = $key . ': ' . adfoin_easyappointments_to_string( $value );
	}

	return implode( "\n", $lines );
}

/**
 * Normalize meta key for payload usage.
 *
 * @param string $slug Slug.
 * @return string
 */
function adfoin_easyappointments_normalize_meta_key( $slug ) {
	$normalized = preg_replace( '/[^a-z0-9_]+/i', '_', $slug );
	$normalized = trim( (string) $normalized, '_' );

	return $normalized ? strtolower( $normalized ) : 'field';
}

/**
 * Transform status to label.
 *
 * @param string $status Raw status.
 * @return string
 */
function adfoin_easyappointments_status_label( $status ) {
	$status = strtolower( (string) $status );

	$map = array(
		'pending'     => __( 'Pending', 'easy-appointments' ),
		'reservation' => __( 'Reservation', 'easy-appointments' ),
		'abandoned'   => __( 'Abandoned', 'easy-appointments' ),
		'canceled'    => __( 'Cancelled', 'easy-appointments' ),
		'cancelled'   => __( 'Cancelled', 'easy-appointments' ),
		'confirmed'   => __( 'Confirmed', 'easy-appointments' ),
	);

	if ( isset( $map[ $status ] ) ) {
		return $map[ $status ];
	}

	return ucwords( str_replace( array( '_', '-' ), ' ', $status ) );
}

/**
 * Build timestamp from date/time pair.
 *
 * @param string $date Date.
 * @param string $time Time.
 * @return int
 */
function adfoin_easyappointments_timestamp( $date, $time ) {
	$date = adfoin_easyappointments_to_string( $date );
	$time = adfoin_easyappointments_to_string( $time );

	if ( '' === $date || '' === $time ) {
		return 0;
	}

	$timestamp = strtotime( "{$date} {$time}" );
	return $timestamp ? $timestamp : 0;
}

/**
 * Convert datetime string to timestamp.
 *
 * @param string $datetime Datetime.
 * @return int
 */
function adfoin_easyappointments_datetime_timestamp( $datetime ) {
	$datetime = adfoin_easyappointments_to_string( $datetime );
	if ( '' === $datetime ) {
		return 0;
	}
	$timestamp = strtotime( $datetime );
	return $timestamp ? $timestamp : 0;
}
