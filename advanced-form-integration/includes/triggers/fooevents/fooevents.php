<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get FooEvents Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_fooevents_get_forms( $form_provider ) {
	if ( $form_provider !== 'fooevents' ) {
		return;
	}

	$triggers = array(
		'newTicket' => __( 'New Ticket Created', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get FooEvents Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_fooevents_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'fooevents' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'newTicket' ) {
		$fields = array(
			'ticket_id'      => __( 'Ticket ID', 'advanced-form-integration' ),
			'event_name'     => __( 'Event Name', 'advanced-form-integration' ),
			'attendee_name'  => __( 'Attendee Name', 'advanced-form-integration' ),
			'attendee_email' => __( 'Attendee Email', 'advanced-form-integration' ),
			'order_id'       => __( 'Order ID', 'advanced-form-integration' ),
			'purchase_date'  => __( 'Purchase Date', 'advanced-form-integration' ),
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
function adfoin_fooevents_get_userdata( $user_id ) {
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
 * Handle New Ticket Created.
 *
 * This function fires when FooEvents creates a new ticket.
 *
 * @param int $ticket_id The ticket post ID.
 */
function adfoin_fooevents_handle_new_ticket( $ticket_id ) {
	$integration  = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'fooevents', 'newTicket' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Retrieve the ticket post.
	$ticket = get_post( $ticket_id );
	if ( ! $ticket ) {
		return;
	}

	// Fetch ticket meta values.
	$event_name     = get_post_meta( $ticket_id, 'event_name', true );
	$attendee_name  = get_post_meta( $ticket_id, 'attendee_name', true );
	$attendee_email = get_post_meta( $ticket_id, 'attendee_email', true );
	$order_id       = get_post_meta( $ticket_id, 'order_id', true );
	$purchase_date  = get_the_date( '', $ticket_id );

	$posted_data = array(
		'ticket_id'      => $ticket_id,
		'event_name'     => $event_name,
		'attendee_name'  => $attendee_name,
		'attendee_email' => $attendee_email,
		'order_id'       => $order_id,
		'purchase_date'  => $purchase_date,
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'fooevents_create_ticket', 'adfoin_fooevents_handle_new_ticket', 10, 1 );