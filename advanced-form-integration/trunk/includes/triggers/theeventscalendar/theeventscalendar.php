<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get The Events Calendar triggers.
 *
 * @param string $form_provider Integration key.
 * @return array|void
 */
function adfoin_theeventscalendar_get_forms( $form_provider ) {
	if ( $form_provider !== 'theeventscalendar' ) {
		return;
	}

	return array(
		'confirmRSVP'    => __( 'Confirm RSVP for an Event', 'advanced-form-integration' ),
		'purchaseTicket' => __( 'Purchase Ticket for an Event', 'advanced-form-integration' ),
	);
}

/**
 * Get The Events Calendar fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger key.
 * @return array|void
 */
function adfoin_theeventscalendar_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'theeventscalendar' ) {
		return;
	}

	$fields = array(
		'attendee_id'        => __( 'Attendee ID', 'advanced-form-integration' ),
		'user_id'            => __( 'User ID', 'advanced-form-integration' ),
		'event_id'           => __( 'Event ID', 'advanced-form-integration' ),
		'event_name'         => __( 'Event Name', 'advanced-form-integration' ),
		'event_start_date'   => __( 'Event Start Date', 'advanced-form-integration' ),
		'event_end_date'     => __( 'Event End Date', 'advanced-form-integration' ),
		'event_permalink'    => __( 'Event Permalink', 'advanced-form-integration' ),
		'ticket_id'          => __( 'Ticket ID', 'advanced-form-integration' ),
		'ticket_name'        => __( 'Ticket Name', 'advanced-form-integration' ),
		'ticket_product_id'  => __( 'Ticket Product ID', 'advanced-form-integration' ),
		'order_id'           => __( 'Order ID', 'advanced-form-integration' ),
		'order_status'       => __( 'Order Status', 'advanced-form-integration' ),
		'order_provider'     => __( 'Ticket Provider', 'advanced-form-integration' ),
		'purchaser_name'     => __( 'Purchaser Name', 'advanced-form-integration' ),
		'purchaser_email'    => __( 'Purchaser Email', 'advanced-form-integration' ),
		'holder_name'        => __( 'Holder Name', 'advanced-form-integration' ),
		'holder_email'       => __( 'Holder Email', 'advanced-form-integration' ),
		'check_in'           => __( 'Check In Status', 'advanced-form-integration' ),
		'security_code'      => __( 'Security Code', 'advanced-form-integration' ),
		'price_paid'         => __( 'Price Paid', 'advanced-form-integration' ),
		'currency'           => __( 'Currency', 'advanced-form-integration' ),
		'attendee_created'   => __( 'Attendee Created Date', 'advanced-form-integration' ),
		'attendee_meta'      => __( 'Attendee Meta (JSON)', 'advanced-form-integration' ),
	);

	return $fields;
}

add_action(
	'event_tickets_rsvp_tickets_generated_for_product',
	'adfoin_theeventscalendar_handle_rsvp',
	10,
	3
);

/**
 * Handle RSVP confirmations triggered by The Events Calendar.
 *
 * @param int $product_id Product (ticket) ID.
 * @param int $order_id   RSVP order identifier.
 * @param int $quantity   Ticket quantity.
 */
function adfoin_theeventscalendar_handle_rsvp( $product_id, $order_id, $quantity ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	if ( ! function_exists( 'tribe_tickets_get_attendees' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'theeventscalendar', 'confirmRSVP' );

	if ( empty( $saved_records ) ) {
		return;
	}

	$attendees = tribe_tickets_get_attendees( $order_id, 'rsvp_order' );
	if ( empty( $attendees ) || ! is_array( $attendees ) ) {
		return;
	}

	foreach ( $attendees as $attendee ) {
		if ( ! is_array( $attendee ) ) {
			continue;
		}

		$status = strtolower( (string) adfoin_theeventscalendar_array_get( $attendee, 'order_status' ) );
		if ( $status !== 'yes' ) {
			continue;
		}

		$payload = adfoin_theeventscalendar_build_payload( $attendee, $order_id );
		if ( empty( $payload ) ) {
			continue;
		}

		$integration->send( $saved_records, $payload );
	}
}

add_action(
	'event_tickets_woocommerce_tickets_generated_for_product',
	'adfoin_theeventscalendar_handle_ticket_purchase',
	10,
	3
);

/**
 * Handle paid ticket purchases fired by The Events Calendar.
 *
 * @param int $product_id Product (ticket) ID.
 * @param int $order_id   WooCommerce order ID.
 * @param int $quantity   Ticket quantity.
 */
function adfoin_theeventscalendar_handle_ticket_purchase( $product_id, $order_id, $quantity ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	if ( ! function_exists( 'tribe_tickets_get_attendees' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'theeventscalendar', 'purchaseTicket' );

	if ( empty( $saved_records ) ) {
		return;
	}

	$attendees = tribe_tickets_get_attendees( $order_id );
	if ( empty( $attendees ) || ! is_array( $attendees ) ) {
		return;
	}

	foreach ( $attendees as $attendee ) {
		if ( ! is_array( $attendee ) ) {
			continue;
		}

		$payload = adfoin_theeventscalendar_build_payload( $attendee, $order_id );
		if ( empty( $payload ) ) {
			continue;
		}

		$integration->send( $saved_records, $payload );
	}
}

/**
 * Build a normalized payload from a raw attendee record.
 *
 * @param array $attendee Raw attendee data.
 * @param int   $fallback_order_id Order ID fallback.
 * @return array<string,mixed>
 */
function adfoin_theeventscalendar_build_payload( $attendee, $fallback_order_id ) {
	if ( ! is_array( $attendee ) ) {
		return array();
	}

	$event_id    = absint( adfoin_theeventscalendar_array_get( $attendee, 'event_id' ) );
	$order_id    = adfoin_theeventscalendar_array_get( $attendee, 'order_id', $fallback_order_id );
	$ticket_name = adfoin_theeventscalendar_array_get(
		$attendee,
		'ticket_name',
		adfoin_theeventscalendar_array_get( $attendee, 'ticket' )
	);

	$payload = array(
		'attendee_id'        => absint( adfoin_theeventscalendar_array_get( $attendee, 'attendee_id' ) ),
		'user_id'            => absint( adfoin_theeventscalendar_array_get( $attendee, 'user_id' ) ),
		'event_id'           => $event_id,
		'event_name'         => $event_id ? get_the_title( $event_id ) : '',
		'event_start_date'   => $event_id ? get_post_meta( $event_id, '_EventStartDate', true ) : '',
		'event_end_date'     => $event_id ? get_post_meta( $event_id, '_EventEndDate', true ) : '',
		'event_permalink'    => $event_id ? adfoin_theeventscalendar_permalink( $event_id ) : '',
		'ticket_id'          => absint( adfoin_theeventscalendar_array_get( $attendee, 'ticket_id' ) ),
		'ticket_name'        => $ticket_name,
		'ticket_product_id'  => absint( adfoin_theeventscalendar_array_get( $attendee, 'product_id' ) ),
		'order_id'           => $order_id,
		'order_status'       => adfoin_theeventscalendar_array_get( $attendee, 'order_status' ),
		'order_provider'     => adfoin_theeventscalendar_array_get(
			$attendee,
			'provider_slug',
			adfoin_theeventscalendar_array_get( $attendee, 'provider' )
		),
		'purchaser_name'     => adfoin_theeventscalendar_array_get( $attendee, 'purchaser_name' ),
		'purchaser_email'    => adfoin_theeventscalendar_array_get( $attendee, 'purchaser_email' ),
		'holder_name'        => adfoin_theeventscalendar_array_get( $attendee, 'holder_name' ),
		'holder_email'       => adfoin_theeventscalendar_array_get( $attendee, 'holder_email' ),
		'check_in'           => adfoin_theeventscalendar_array_get( $attendee, 'check_in' ),
		'security_code'      => adfoin_theeventscalendar_array_get( $attendee, 'security_code' ),
		'price_paid'         => adfoin_theeventscalendar_array_get( $attendee, 'price_paid' ),
		'currency'           => adfoin_theeventscalendar_array_get( $attendee, 'currency' ),
		'attendee_created'   => adfoin_theeventscalendar_array_get( $attendee, 'post_date' ),
		'attendee_meta'      => adfoin_theeventscalendar_encode_meta(
			adfoin_theeventscalendar_array_get( $attendee, 'attendee_meta', array() )
		),
	);

	return $payload;
}

/**
 * Safe helper to fetch an array value.
 *
 * @param array  $array   Source array.
 * @param string $key     Target key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function adfoin_theeventscalendar_array_get( $array, $key, $default = '' ) {
	if ( ! is_array( $array ) || ! array_key_exists( $key, $array ) ) {
		return $default;
	}

	$value = $array[ $key ];

	if ( is_scalar( $value ) || $value === null ) {
		return $value;
	}

	return $value;
}

/**
 * Convert attendee meta to JSON string.
 *
 * @param array $meta Raw attendee meta.
 * @return string
 */
function adfoin_theeventscalendar_encode_meta( $meta ) {
	if ( empty( $meta ) || ! is_array( $meta ) ) {
		return '';
	}

	$normalized = array();

	foreach ( $meta as $key => $item ) {
		$label = '';
		$value = '';

		if ( is_array( $item ) ) {
			$label = isset( $item['label'] ) && $item['label'] !== '' ? $item['label'] : '';
			if ( $label === '' && isset( $item['slug'] ) ) {
				$label = $item['slug'];
			}
			if ( $label === '' ) {
				$label = is_scalar( $key ) ? $key : '';
			}

			if ( isset( $item['value'] ) ) {
				$value = $item['value'];
			}
		} else {
			$label = is_scalar( $key ) ? $key : '';
			$value = $item;
		}

		if ( $label === '' ) {
			continue;
		}

		if ( is_array( $value ) ) {
			$value = array_values( array_map( 'strval', $value ) );
		} elseif ( is_object( $value ) ) {
			$value = (array) $value;
		}

		if ( is_array( $value ) ) {
			$normalized[ $label ] = $value;
		} else {
			$normalized[ $label ] = (string) $value;
		}
	}

	if ( empty( $normalized ) ) {
		return '';
	}

	return function_exists( 'wp_json_encode' )
		? wp_json_encode( $normalized )
		: json_encode( $normalized );
}

/**
 * Retrieve the event permalink safely.
 *
 * @param int $event_id Event ID.
 * @return string
 */
function adfoin_theeventscalendar_permalink( $event_id ) {
	if ( ! $event_id || ! function_exists( 'get_permalink' ) ) {
		return '';
	}

	$permalink = get_permalink( $event_id );
	return is_string( $permalink ) ? $permalink : '';
}
