<?php

// Get Event Tickets Triggers
function adfoin_eventtickets_get_forms( $form_provider ) {
	if ( $form_provider !== 'eventtickets' ) {
		return;
	}

	$triggers = array(
		'ticketPurchased' => __( 'Ticket Purchased', 'advanced-form-integration' ),
		'ticketCheckedIn' => __( 'Ticket Checked In', 'advanced-form-integration' ),
	);

	return $triggers;
}

// Get Event Tickets Fields
function adfoin_eventtickets_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'eventtickets' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'ticketPurchased' ) {
		$fields = array(
			'attendee_id'    => __( 'Attendee ID', 'advanced-form-integration' ),
			'event_id'       => __( 'Event ID', 'advanced-form-integration' ),
			'ticket_id'      => __( 'Ticket ID', 'advanced-form-integration' ),
			'ticket_name'    => __( 'Ticket Name', 'advanced-form-integration' ),
			'purchaser_id'   => __( 'Purchaser ID', 'advanced-form-integration' ),
			'purchaser_name' => __( 'Purchaser Name', 'advanced-form-integration' ),
			'purchase_date'  => __( 'Purchase Date', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'ticketCheckedIn' ) {
		$fields = array(
			'attendee_id'  => __( 'Attendee ID', 'advanced-form-integration' ),
			'event_id'     => __( 'Event ID', 'advanced-form-integration' ),
			'ticket_id'    => __( 'Ticket ID', 'advanced-form-integration' ),
			'checkin_time' => __( 'Check-in Time', 'advanced-form-integration' ),
		);
	}

	return $fields;
}

// Handle Ticket Purchased
// (Assuming the eventtickets plugin fires the 'event_tickets_attendee_created' hook when a ticket is purchased)
add_action( 'event_tickets_attendee_created', 'adfoin_eventtickets_handle_ticket_purchase', 10, 6 );
function adfoin_eventtickets_handle_ticket_purchase( $attendee_id, $event_id, $ticket_id, $purchaser_id, $ticket_name = '', $purchase_date = '' ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventtickets', 'ticketPurchased' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Ensure the eventtickets plugin functions exist
	if ( ! function_exists( 'event_tickets_get_event' ) ) {
		return;
	}

	$event = event_tickets_get_event( $event_id );
	if ( ! $event ) {
		return;
	}

	// Attempt to retrieve ticket details if available
	$ticket = function_exists( 'event_tickets_get_ticket' ) ? event_tickets_get_ticket( $ticket_id ) : null;
	$purchaser_name = get_the_author_meta( 'display_name', $purchaser_id );

	$posted_data = array(
		'attendee_id'    => $attendee_id,
		'event_id'       => $event_id,
		'ticket_id'      => $ticket_id,
		'ticket_name'    => $ticket_name ? $ticket_name : ( $ticket ? $ticket->name : '' ),
		'purchaser_id'   => $purchaser_id,
		'purchaser_name' => $purchaser_name,
		'purchase_date'  => $purchase_date,
	);

	$integration->send( $saved_records, $posted_data );
}

// Handle Ticket Checked In
// (Assuming the eventtickets plugin fires the 'event_tickets_checkin' hook when a ticket is checked in)
add_action( 'event_tickets_checkin', 'adfoin_eventtickets_handle_ticket_checkin', 10, 4 );
function adfoin_eventtickets_handle_ticket_checkin( $attendee_id, $event_id, $ticket_id, $checkin_time ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventtickets', 'ticketCheckedIn' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Ensure the eventtickets plugin functions exist
	if ( ! function_exists( 'event_tickets_get_event' ) ) {
		return;
	}

	$event = event_tickets_get_event( $event_id );
	if ( ! $event ) {
		return;
	}

	// Optionally, you can retrieve more ticket info if needed.
	$ticket = function_exists( 'event_tickets_get_ticket' ) ? event_tickets_get_ticket( $ticket_id ) : null;

	$posted_data = array(
		'attendee_id'  => $attendee_id,
		'event_id'     => $event_id,
		'ticket_id'    => $ticket_id,
		'checkin_time' => $checkin_time,
	);

	$integration->send( $saved_records, $posted_data );
}