<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Tickera Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_tickera_get_forms( $form_provider ) {
	if ( $form_provider !== 'tickera' ) {
		return;
	}
	$triggers = array(
		'newOrder' => __( 'New Order Created', 'advanced-form-integration' ),
	);
	return $triggers;
}

/**
 * Get Tickera Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_tickera_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'tickera' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'newOrder' ) {
		$fields = array(
			'order_id'  => __( 'Order ID', 'advanced-form-integration' ),
			'event_name'  => __( 'Event Name', 'advanced-form-integration' ),
			'ticket_name' => __( 'Ticket Name', 'advanced-form-integration' ),
			// Add additional fields as needed.
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
function adfoin_tickera_get_userdata( $user_id ) {
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
 * Handle New Order Created.
 *
 * This function fires when Tickera creates a new order.
 *
 * @param int    $order_id      The Tickera order ID.
 * @param string $status        The order status.
 * @param array  $cart_contents The cart contents.
 * @param array  $cart_info     Additional cart info.
 */
function adfoin_tickera_handle_order_created( $order_id, $status, $cart_contents, $cart_info ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'tickera', 'newOrder' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Retrieve order details.
	$order = get_post( $order_id );
	if ( ! $order ) {
		return;
	}

	// Assume event and ticket data are stored as post meta.
	$event_name  = get_post_meta( $order_id, 'tc_event_name', true );
	$ticket_name = get_post_meta( $order_id, 'tc_ticket_name', true );

	$posted_data = array(
		'order_id'   => $order_id,
		'event_name' => $event_name,
		'ticket_name'=> $ticket_name,
		// Additional fields can be added here.
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'tc_order_created', 'adfoin_tickera_handle_order_created', 10, 4 );