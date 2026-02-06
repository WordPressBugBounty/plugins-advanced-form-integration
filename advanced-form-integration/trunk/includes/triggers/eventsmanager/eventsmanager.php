<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns available Events Manager triggers.
 *
 * @param string $form_provider Integration key.
 * @return array<string,string>|void
 */
function adfoin_eventsmanager_get_forms( $form_provider ) {
	if ( $form_provider !== 'eventsmanager' ) {
		return;
	}

	return array(
		'bookingCreated'             => __( 'Booking Created', 'advanced-form-integration' ),
		'bookingUpdated'             => __( 'Booking Updated', 'advanced-form-integration' ),
		'bookingDeleted'             => __( 'Booking Deleted', 'advanced-form-integration' ),
		'bookingStatusChanged'       => __( 'Booking Status Changed', 'advanced-form-integration' ),
		'bookingPending'             => __( 'Booking Pending', 'advanced-form-integration' ),
		'bookingApproved'            => __( 'Booking Approved', 'advanced-form-integration' ),
		'bookingRejected'            => __( 'Booking Rejected', 'advanced-form-integration' ),
		'bookingCancelled'           => __( 'Booking Cancelled', 'advanced-form-integration' ),
		'bookingAwaitingOnlinePayment' => __( 'Booking Awaiting Online Payment', 'advanced-form-integration' ),
		'bookingAwaitingPayment'     => __( 'Booking Awaiting Payment', 'advanced-form-integration' ),
		'bookingWaitlist'            => __( 'Booking Waitlist', 'advanced-form-integration' ),
		'bookingWaitlistApproved'    => __( 'Booking Waitlist Approved', 'advanced-form-integration' ),
		'bookingWaitlistExpired'     => __( 'Booking Waitlist Expired', 'advanced-form-integration' ),
	);
}

/**
 * Returns Events Manager field labels.
 *
 * @return array<string,string>
 */
function adfoin_eventsmanager_field_labels() {
	return array(
		'booking_id'                    => __( 'Booking ID', 'advanced-form-integration' ),
		'booking_uuid'                  => __( 'Booking UUID', 'advanced-form-integration' ),
		'booking_status_code'           => __( 'Booking Status (Code)', 'advanced-form-integration' ),
		'booking_status_label'          => __( 'Booking Status', 'advanced-form-integration' ),
		'booking_spaces'                => __( 'Booking Spaces', 'advanced-form-integration' ),
		'booking_price'                 => __( 'Booking Price', 'advanced-form-integration' ),
		'booking_tax_rate'              => __( 'Booking Tax Rate', 'advanced-form-integration' ),
		'booking_taxes'                 => __( 'Booking Taxes', 'advanced-form-integration' ),
		'booking_comment'               => __( 'Booking Comment', 'advanced-form-integration' ),
		'booking_date'                  => __( 'Booking Date', 'advanced-form-integration' ),
		'booking_datetime'              => __( 'Booking DateTime', 'advanced-form-integration' ),
		'booking_meta_json'             => __( 'Booking Meta (JSON)', 'advanced-form-integration' ),
		'booking_meta_plain'            => __( 'Booking Meta (Text)', 'advanced-form-integration' ),
		'registration_json'             => __( 'Registration Data (JSON)', 'advanced-form-integration' ),
		'registration_plain'            => __( 'Registration Data (Text)', 'advanced-form-integration' ),
		'tickets_json'                  => __( 'Tickets (JSON)', 'advanced-form-integration' ),
		'tickets_plain'                 => __( 'Tickets (Text)', 'advanced-form-integration' ),
		'tickets_count'                 => __( 'Ticket Count', 'advanced-form-integration' ),
		'tickets_total_spaces'          => __( 'Ticket Spaces Total', 'advanced-form-integration' ),
		'person_id'                     => __( 'Person ID', 'advanced-form-integration' ),
		'person_name'                   => __( 'Person Name', 'advanced-form-integration' ),
		'person_first_name'             => __( 'Person First Name', 'advanced-form-integration' ),
		'person_last_name'              => __( 'Person Last Name', 'advanced-form-integration' ),
		'person_email'                  => __( 'Person Email', 'advanced-form-integration' ),
		'person_phone'                  => __( 'Person Phone', 'advanced-form-integration' ),
		'person_username'               => __( 'Person Username', 'advanced-form-integration' ),
		'person_guest'                  => __( 'Person Is Guest', 'advanced-form-integration' ),
		'user_id'                       => __( 'WordPress User ID', 'advanced-form-integration' ),
		'event_internal_id'             => __( 'Event ID', 'advanced-form-integration' ),
		'event_uid'                     => __( 'Event UID', 'advanced-form-integration' ),
		'event_post_id'                 => __( 'Event Post ID', 'advanced-form-integration' ),
		'event_name'                    => __( 'Event Name', 'advanced-form-integration' ),
		'event_slug'                    => __( 'Event Slug', 'advanced-form-integration' ),
		'event_status'                  => __( 'Event Status', 'advanced-form-integration' ),
		'event_type'                    => __( 'Event Type', 'advanced-form-integration' ),
		'event_start_date'              => __( 'Event Start Date', 'advanced-form-integration' ),
		'event_start_time'              => __( 'Event Start Time', 'advanced-form-integration' ),
		'event_end_date'                => __( 'Event End Date', 'advanced-form-integration' ),
		'event_end_time'                => __( 'Event End Time', 'advanced-form-integration' ),
		'event_all_day'                 => __( 'Event All Day', 'advanced-form-integration' ),
		'event_timezone'                => __( 'Event Timezone', 'advanced-form-integration' ),
		'event_rsvp_end_date'           => __( 'Event RSVP End Date', 'advanced-form-integration' ),
		'event_rsvp_end_time'           => __( 'Event RSVP End Time', 'advanced-form-integration' ),
		'event_spaces'                  => __( 'Event Total Spaces', 'advanced-form-integration' ),
		'event_rsvp_spaces'             => __( 'Event RSVP Spaces', 'advanced-form-integration' ),
		'event_owner_id'                => __( 'Event Owner ID', 'advanced-form-integration' ),
		'event_owner_email'             => __( 'Event Owner Email', 'advanced-form-integration' ),
		'event_owner_name'              => __( 'Event Owner Name', 'advanced-form-integration' ),
		'location_id'                   => __( 'Location ID', 'advanced-form-integration' ),
        'location_name'                 => __( 'Location Name', 'advanced-form-integration' ),
		'location_slug'                 => __( 'Location Slug', 'advanced-form-integration' ),
		'location_address'              => __( 'Location Address', 'advanced-form-integration' ),
		'location_town'                 => __( 'Location Town', 'advanced-form-integration' ),
		'location_state'                => __( 'Location State', 'advanced-form-integration' ),
		'location_postcode'             => __( 'Location Postcode', 'advanced-form-integration' ),
		'location_country'              => __( 'Location Country', 'advanced-form-integration' ),
		'location_latitude'             => __( 'Location Latitude', 'advanced-form-integration' ),
		'location_longitude'            => __( 'Location Longitude', 'advanced-form-integration' ),
		'is_deleted'                    => __( 'Booking Deleted', 'advanced-form-integration' ),
	);
}

/**
 * Returns Events Manager fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 * @return array<string,string>|void
 */
function adfoin_eventsmanager_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'eventsmanager' ) {
		return;
	}

	$fields           = adfoin_eventsmanager_field_labels();
	$previous_fields  = array();
	foreach ( $fields as $key => $label ) {
		if ( strpos( $key, 'previous_' ) === 0 ) {
			continue;
		}
		$previous_fields[ 'previous_' . $key ] = sprintf( __( 'Previous %s', 'advanced-form-integration' ), $label );
	}

	return $fields + $previous_fields;
}

add_action( 'plugins_loaded', 'adfoin_eventsmanager_bootstrap', 20 );

/**
 * Bootstrap Events Manager triggers.
 */
function adfoin_eventsmanager_bootstrap() {
	if ( ! class_exists( 'EM_Booking' ) ) {
		return;
	}

	add_action( 'em_booking_save_pre', 'adfoin_eventsmanager_capture_before', 10, 1 );
	add_filter( 'em_booking_save', 'adfoin_eventsmanager_handle_save', 100, 3 );
	add_action( 'em_booking_added', 'adfoin_eventsmanager_handle_booking_added', 100, 1 );
	add_action( 'em_booking_status_changed', 'adfoin_eventsmanager_handle_status_changed', 100, 2 );
	add_action( 'em_booking_deleted', 'adfoin_eventsmanager_handle_booking_deleted', 100, 1 );
}

/**
 * Global state store.
 *
 * @return array<string,mixed>
 */
function &adfoin_eventsmanager_state() {
	static $state = null;
	if ( null === $state ) {
		$state = array(
			'before' => array(),
		);
	}

	return $state;
}

/**
 * Capture snapshot prior to saving.
 *
 * @param EM_Booking $booking Booking instance.
 */
function adfoin_eventsmanager_capture_before( $booking ) {
	if ( ! ( $booking instanceof EM_Booking ) ) {
		return;
	}

	$booking_id = (int) $booking->booking_id;
	if ( $booking_id < 1 ) {
		return;
	}

	$snapshot = adfoin_eventsmanager_snapshot_by_id( $booking_id );
	if ( $snapshot ) {
		$state = &adfoin_eventsmanager_state();
		$state['before'][ $booking_id ] = $snapshot;
	}
}

/**
 * Filter handler after booking save.
 *
 * @param bool       $result    Save result.
 * @param EM_Booking $booking   Booking.
 * @param bool       $is_update Whether this was an update.
 * @return bool
 */
function adfoin_eventsmanager_handle_save( $result, $booking, $is_update ) {
	if ( ! $result || ! $is_update || ! ( $booking instanceof EM_Booking ) ) {
		return $result;
	}

	$current = adfoin_eventsmanager_collect_data( $booking );
	$before  = adfoin_eventsmanager_consume_snapshot( $booking->booking_id );

	$payload = adfoin_eventsmanager_build_payload( $current, $before );
	adfoin_eventsmanager_dispatch( 'bookingUpdated', $payload );

	return $result;
}

/**
 * Booking added action handler.
 *
 * @param EM_Booking $booking Booking instance.
 */
function adfoin_eventsmanager_handle_booking_added( $booking ) {
	if ( ! ( $booking instanceof EM_Booking ) ) {
		return;
	}

	$current = adfoin_eventsmanager_collect_data( $booking );
	$payload = adfoin_eventsmanager_build_payload( $current, null );

	adfoin_eventsmanager_dispatch( 'bookingCreated', $payload );
	adfoin_eventsmanager_dispatch_status_triggers( $payload );
}

/**
 * Booking status changed handler.
 *
 * @param EM_Booking $booking Booking instance.
 * @param array      $args    Arguments (status info).
 */
function adfoin_eventsmanager_handle_status_changed( $booking, $args ) {
	if ( ! ( $booking instanceof EM_Booking ) ) {
		return;
	}

	$current = adfoin_eventsmanager_collect_data( $booking );
	$before  = $current;

	if ( isset( $booking->previous_status ) ) {
		$before['booking_status_code']  = (int) $booking->previous_status;
		$before['booking_status_label'] = adfoin_eventsmanager_status_label( $booking->previous_status );
	}

	$payload = adfoin_eventsmanager_build_payload( $current, $before );

	adfoin_eventsmanager_dispatch( 'bookingStatusChanged', $payload );
	adfoin_eventsmanager_dispatch_status_triggers( $payload );
}

/**
 * Booking deleted handler.
 *
 * @param EM_Booking $booking Booking instance.
 */
function adfoin_eventsmanager_handle_booking_deleted( $booking ) {
	if ( ! ( $booking instanceof EM_Booking ) ) {
		return;
	}

	$before = adfoin_eventsmanager_consume_snapshot( $booking->booking_id );
	if ( ! $before ) {
		$before = adfoin_eventsmanager_collect_data( $booking );
	}

	if ( empty( $before ) ) {
		return;
	}

	$current                   = $before;
	$current['is_deleted']     = 'yes';
	$current['booking_status_code']  = -1;
	$current['booking_status_label'] = __( 'Deleted', 'events-manager' );

	$payload = adfoin_eventsmanager_build_payload( $current, $before );
	adfoin_eventsmanager_dispatch( 'bookingDeleted', $payload );
}

/**
 * Dispatch status-specific triggers.
 *
 * @param array<string,mixed> $payload Payload.
 */
function adfoin_eventsmanager_dispatch_status_triggers( $payload ) {
	$status_code = isset( $payload['booking_status_code'] ) ? (int) $payload['booking_status_code'] : null;

	$map = array(
		0 => 'bookingPending',
		1 => 'bookingApproved',
		2 => 'bookingRejected',
		3 => 'bookingCancelled',
		4 => 'bookingAwaitingOnlinePayment',
		5 => 'bookingAwaitingPayment',
		6 => 'bookingWaitlist',
		7 => 'bookingWaitlistApproved',
		8 => 'bookingWaitlistExpired',
	);

	if ( isset( $map[ $status_code ] ) ) {
		adfoin_eventsmanager_dispatch( $map[ $status_code ], $payload );
	}
}

/**
 * Dispatch payload to AFI.
 *
 * @param string               $trigger Trigger key.
 * @param array<string,mixed>  $payload Payload data.
 */
function adfoin_eventsmanager_dispatch( $trigger, $payload ) {
	if ( empty( $payload ) ) {
		return;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventsmanager', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload['trigger'] = $trigger;
	$integration->send( $saved_records, $payload );
}

/**
 * Build final payload with previous values.
 *
 * @param array<string,mixed>      $current Current data.
 * @param array<string,mixed>|null $previous Previous data.
 * @return array<string,mixed>
 */
function adfoin_eventsmanager_build_payload( array $current, $previous = null ) {
	$previous = is_array( $previous ) ? $previous : array();
	$payload  = $current;

	$fields = array_keys( adfoin_eventsmanager_field_labels() );
	foreach ( $fields as $key ) {
		$prefixed = 'previous_' . $key;
		$value    = isset( $previous[ $key ] ) ? $previous[ $key ] : null;
		if ( null !== $value ) {
			$payload[ $prefixed ] = $value;
		}
	}

	return $payload;
}

/**
 * Consume snapshot from state.
 *
 * @param int $booking_id Booking ID.
 * @return array<string,mixed>|null
 */
function adfoin_eventsmanager_consume_snapshot( $booking_id ) {
	$state = &adfoin_eventsmanager_state();
	$booking_id = (int) $booking_id;

	if ( isset( $state['before'][ $booking_id ] ) ) {
		$snapshot = $state['before'][ $booking_id ];
		unset( $state['before'][ $booking_id ] );
		return $snapshot;
	}

	return null;
}

/**
 * Create snapshot by ID.
 *
 * @param int $booking_id Booking ID.
 * @return array<string,mixed>|null
 */
function adfoin_eventsmanager_snapshot_by_id( $booking_id ) {
	$booking_id = (int) $booking_id;
	if ( $booking_id < 1 ) {
		return null;
	}

	$booking = new EM_Booking( $booking_id );
	if ( empty( $booking->booking_id ) ) {
		return null;
	}

	return adfoin_eventsmanager_collect_data( $booking );
}

/**
 * Collects booking data into a normalized array.
 *
 * @param EM_Booking $booking Booking.
 * @return array<string,mixed>
 */
function adfoin_eventsmanager_collect_data( EM_Booking $booking ) {
	$booking->compat_keys();

	$api        = $booking->to_api( array( 'event' => true ) );
	$event_api  = isset( $api['event'] ) && is_array( $api['event'] ) ? $api['event'] : array();
	$tickets    = isset( $api['tickets'] ) && is_array( $api['tickets'] ) ? $api['tickets'] : array();
	$person_api = isset( $api['person'] ) && is_array( $api['person'] ) ? $api['person'] : array();

	$event  = $booking->get_event(); // ensure loaded.
	$person = $booking->get_person();

	$tickets_list = array();
	$total_spaces = 0;
	foreach ( $tickets as $ticket_id => $ticket ) {
		$ticket_record = array(
			'id'          => $ticket_id,
			'name'        => isset( $ticket['name'] ) ? $ticket['name'] : '',
			'description' => isset( $ticket['description'] ) ? $ticket['description'] : '',
			'spaces'      => isset( $ticket['spaces'] ) ? (int) $ticket['spaces'] : 0,
			'price'       => isset( $ticket['price'] ) ? $ticket['price'] : '',
			'attendees'   => isset( $ticket['attendees'] ) ? array_values( (array) $ticket['attendees'] ) : array(),
		);
		$total_spaces += $ticket_record['spaces'];
		$tickets_list[] = $ticket_record;
	}

	$meta                   = is_array( $booking->booking_meta ) ? $booking->booking_meta : array();
	$registration_meta      = isset( $meta['registration'] ) && is_array( $meta['registration'] ) ? $meta['registration'] : array();
	$flat_meta              = adfoin_eventsmanager_flatten_assoc( $meta );
	$flat_registration_meta = adfoin_eventsmanager_flatten_assoc( $registration_meta );

	$location_api = array();
	if ( isset( $event_api['location'] ) && is_array( $event_api['location'] ) ) {
		$location_api = $event_api['location'];
	}

	$location_address = isset( $location_api['address'] ) && is_array( $location_api['address'] ) ? $location_api['address'] : array();

	$person_first_name = '';
	$person_last_name  = '';

	if ( ! empty( $person->first_name ) || ! empty( $person->last_name ) ) {
		$person_first_name = $person->first_name;
		$person_last_name  = $person->last_name;
	} else {
		if ( isset( $registration_meta['first_name'] ) ) {
			$person_first_name = $registration_meta['first_name'];
		}
		if ( isset( $registration_meta['last_name'] ) ) {
			$person_last_name = $registration_meta['last_name'];
		}
	}

	$person_phone = '';
	if ( ! empty( $person->phone ) ) {
		$person_phone = $person->phone;
	} elseif ( isset( $registration_meta['phone'] ) ) {
		$person_phone = $registration_meta['phone'];
	}

	$data = array(
		'booking_id'            => (int) $booking->booking_id,
		'booking_uuid'          => isset( $booking->booking_uuid ) ? $booking->booking_uuid : '',
		'booking_status_code'   => isset( $booking->booking_status ) ? (int) $booking->booking_status : null,
		'booking_status_label'  => adfoin_eventsmanager_status_label( isset( $booking->booking_status ) ? $booking->booking_status : null ),
		'booking_spaces'        => isset( $booking->booking_spaces ) ? (int) $booking->booking_spaces : 0,
		'booking_price'         => method_exists( $booking, 'get_price' ) ? $booking->get_price() : null,
		'booking_tax_rate'      => method_exists( $booking, 'get_tax_rate' ) ? $booking->get_tax_rate( true ) : null,
		'booking_taxes'         => isset( $booking->booking_taxes ) ? $booking->booking_taxes : null,
		'booking_comment'       => isset( $booking->booking_comment ) ? $booking->booking_comment : '',
		'booking_date'          => isset( $booking->booking_date ) ? $booking->booking_date : '',
		'booking_datetime'      => isset( $api['datetime'] ) ? $api['datetime'] : '',
		'booking_meta_json'     => adfoin_eventsmanager_json( $meta ),
		'booking_meta_plain'    => adfoin_eventsmanager_plain_from_assoc( $flat_meta ),
		'registration_json'     => adfoin_eventsmanager_json( $registration_meta ),
		'registration_plain'    => adfoin_eventsmanager_plain_from_assoc( $flat_registration_meta ),
		'tickets_json'          => adfoin_eventsmanager_json( $tickets_list ),
		'tickets_plain'         => adfoin_eventsmanager_tickets_plain( $tickets_list ),
		'tickets_count'         => count( $tickets_list ),
		'tickets_total_spaces'  => $total_spaces,
		'person_id'             => isset( $booking->person_id ) ? (int) $booking->person_id : 0,
		'person_name'           => $person->get_name(),
		'person_first_name'     => $person_first_name,
		'person_last_name'      => $person_last_name,
		'person_email'          => $person->user_email,
		'person_phone'          => $person_phone,
		'person_username'       => isset( $person->user_login ) ? $person->user_login : '',
		'person_guest'          => adfoin_eventsmanager_bool_string( ! empty( $person_api['guest'] ) ),
		'user_id'               => isset( $person->ID ) ? (int) $person->ID : 0,
		'event_internal_id'     => isset( $booking->event_id ) ? (int) $booking->event_id : 0,
		'event_uid'             => isset( $event_api['id'] ) ? $event_api['id'] : '',
		'event_post_id'         => isset( $event_api['post_id'] ) ? (int) $event_api['post_id'] : ( isset( $event->post_id ) ? (int) $event->post_id : 0 ),
		'event_name'            => isset( $event_api['name'] ) ? $event_api['name'] : $event->event_name,
		'event_slug'            => isset( $event_api['slug'] ) ? $event_api['slug'] : $event->event_slug,
		'event_status'          => isset( $event_api['status'] ) ? $event_api['status'] : '',
		'event_type'            => isset( $event_api['type'] ) ? $event_api['type'] : '',
		'event_start_date'      => isset( $event_api['when']['start_date'] ) ? $event_api['when']['start_date'] : '',
		'event_start_time'      => isset( $event_api['when']['start_time'] ) ? $event_api['when']['start_time'] : '',
		'event_end_date'        => isset( $event_api['when']['end_date'] ) ? $event_api['when']['end_date'] : '',
		'event_end_time'        => isset( $event_api['when']['end_time'] ) ? $event_api['when']['end_time'] : '',
		'event_all_day'         => adfoin_eventsmanager_bool_string( isset( $event_api['when']['all_day'] ) ? $event_api['when']['all_day'] : false ),
		'event_timezone'        => isset( $event_api['when']['timezone'] ) ? $event_api['when']['timezone'] : '',
		'event_rsvp_end_date'   => isset( $event_api['bookings']['end_date'] ) ? $event_api['bookings']['end_date'] : '',
		'event_rsvp_end_time'   => isset( $event_api['bookings']['end_time'] ) ? $event_api['bookings']['end_time'] : '',
		'event_spaces'          => isset( $event_api['bookings']['spaces'] ) ? $event_api['bookings']['spaces'] : '',
		'event_rsvp_spaces'     => isset( $event_api['bookings']['rsvp_spaces'] ) ? $event_api['bookings']['rsvp_spaces'] : '',
		'event_owner_id'        => isset( $event_api['owner']['id'] ) ? $event_api['owner']['id'] : ( isset( $event->event_owner ) ? $event->event_owner : '' ),
		'event_owner_email'     => isset( $event_api['owner']['email'] ) ? $event_api['owner']['email'] : '',
		'event_owner_name'      => isset( $event_api['owner']['name'] ) ? $event_api['owner']['name'] : '',
		'location_id'           => isset( $location_api['id'] ) ? $location_api['id'] : '',
		'location_name'         => isset( $location_api['name'] ) ? $location_api['name'] : '',
		'location_slug'         => isset( $location_api['slug'] ) ? $location_api['slug'] : '',
		'location_address'      => isset( $location_address['address'] ) ? $location_address['address'] : '',
		'location_town'         => isset( $location_address['town'] ) ? $location_address['town'] : '',
		'location_state'        => isset( $location_address['state'] ) ? $location_address['state'] : '',
		'location_postcode'     => isset( $location_address['postcode'] ) ? $location_address['postcode'] : '',
		'location_country'      => isset( $location_address['country'] ) ? $location_address['country'] : '',
		'location_latitude'     => isset( $location_api['geo']['latitude'] ) ? $location_api['geo']['latitude'] : '',
		'location_longitude'    => isset( $location_api['geo']['longitude'] ) ? $location_api['geo']['longitude'] : '',
		'is_deleted'            => 'no',
	);

	return $data;
}

/**
 * Convert value to string.
 *
 * @param mixed $value Value.
 * @return string
 */
function adfoin_eventsmanager_to_string( $value ) {
	if ( null === $value ) {
		return '';
	}
	if ( is_bool( $value ) ) {
		return $value ? 'yes' : 'no';
	}
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	return adfoin_eventsmanager_json( $value );
}

/**
 * Convert value to JSON string.
 *
 * @param mixed $value Value.
 * @return string
 */
function adfoin_eventsmanager_json( $value ) {
	$encoded = wp_json_encode( $value );
	return false === $encoded ? '' : $encoded;
}

/**
 * Transform associative array to plain text.
 *
 * @param array<string,mixed> $assoc Values.
 * @return string
 */
function adfoin_eventsmanager_plain_from_assoc( $assoc ) {
	if ( empty( $assoc ) ) {
		return '';
	}

	$lines = array();
	foreach ( $assoc as $key => $value ) {
		$lines[] = $key . ': ' . adfoin_eventsmanager_to_string( $value );
	}

	return implode( "\n", $lines );
}

/**
 * Flatten associative array (one level deep).
 *
 * @param array<string,mixed>  $data   Input.
 * @param string               $prefix Prefix.
 * @return array<string,mixed>
 */
function adfoin_eventsmanager_flatten_assoc( $data, $prefix = '' ) {
	$result = array();

	foreach ( (array) $data as $key => $value ) {
		$new_key = $prefix ? "{$prefix}.{$key}" : $key;
		if ( is_array( $value ) ) {
			$result += adfoin_eventsmanager_flatten_assoc( $value, $new_key );
		} else {
			$result[ $new_key ] = $value;
		}
	}

	return $result;
}

/**
 * Create readable tickets list.
 *
 * @param array<int,array<string,mixed>> $tickets Tickets.
 * @return string
 */
function adfoin_eventsmanager_tickets_plain( $tickets ) {
	if ( empty( $tickets ) ) {
		return '';
	}

	$lines = array();
	foreach ( $tickets as $ticket ) {
		$name    = isset( $ticket['name'] ) ? $ticket['name'] : '';
		$spaces  = isset( $ticket['spaces'] ) ? $ticket['spaces'] : '';
		$price   = isset( $ticket['price'] ) ? $ticket['price'] : '';
		$lines[] = sprintf( '%s | %s %s', $name, __( 'Spaces:', 'advanced-form-integration' ) . ' ' . $spaces, __( 'Price:', 'advanced-form-integration' ) . ' ' . $price );
	}

	return implode( "\n", $lines );
}

/**
 * Convert boolean to yes/no string.
 *
 * @param mixed $value Value.
 * @return string
 */
function adfoin_eventsmanager_bool_string( $value ) {
	return $value ? 'yes' : 'no';
}

/**
 * Provide status label from code.
 *
 * @param int|null $status Status code.
 * @return string
 */
function adfoin_eventsmanager_status_label( $status ) {
	$map = array(
		0 => __( 'Pending', 'events-manager' ),
		1 => __( 'Approved', 'events-manager' ),
		2 => __( 'Rejected', 'events-manager' ),
		3 => __( 'Cancelled', 'events-manager' ),
		4 => __( 'Awaiting Online Payment', 'events-manager' ),
		5 => __( 'Awaiting Payment', 'events-manager' ),
		6 => __( 'Waitlist', 'events-manager' ),
		7 => __( 'Waitlist Approved', 'events-manager' ),
		8 => __( 'Waitlist Expired', 'events-manager' ),
	);

	$status = is_numeric( $status ) ? (int) $status : null;

	return isset( $map[ $status ] ) ? $map[ $status ] : '';
}
