<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Upsell Plugin Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_upsell_get_forms( $form_provider ) {
	if ( $form_provider !== 'upsell' ) {
		return;
	}
	$triggers = array(
		'orderCreated' => __( 'Order Created', 'advanced-form-integration' ),
	);
	return $triggers;
}

/**
 * Get Upsell Plugin Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_upsell_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'upsell' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'orderCreated' ) {
		$fields = array(
			'order_id'       => __( 'Order ID', 'advanced-form-integration' ),
			'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
			'total_amount'   => __( 'Total Amount', 'advanced-form-integration' ),
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
function adfoin_upsell_get_userdata( $user_id ) {
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
 * Handle Upsell Order Created.
 *
 * This function fires when an Upsell order is completed.
 *
 * @param object $order The Upsell order object.
 */
function adfoin_upsell_handle_order_created( $order ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'upsell', 'orderCreated' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Extract key order details.
	$order_id       = $order->id;
	$customer_email = $order->getAttribute( 'customer_email' );
	$total_amount   = $order->getAttribute( 'total' );

	$posted_data = array(
		'order_id'       => $order_id,
		'customer_email' => $customer_email,
		'total_amount'   => $total_amount,
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'upsell_process_checkout_completed', 'adfoin_upsell_handle_order_created', 10, 1 );