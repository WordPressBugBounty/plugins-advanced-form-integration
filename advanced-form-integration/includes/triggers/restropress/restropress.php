<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get RestroPress Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_restropress_get_forms( $form_provider ) {
	if ( $form_provider !== 'restropress' ) {
		return;
	}
	return array(
		'orderComplete' => __( 'Order Complete', 'advanced-form-integration' ),
	);
}

/**
 * Get RestroPress Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_restropress_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'restropress' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'orderComplete' ) {
		$fields = array(
			'order_id'      => __( 'Order ID', 'advanced-form-integration' ),
			'order_total'   => __( 'Order Total', 'advanced-form-integration' ),
			'customer_email'=> __( 'Customer Email', 'advanced-form-integration' ),
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
function adfoin_restropress_get_userdata( $user_id ) {
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
 * Handle RestroPress Order Completion.
 *
 * This function fires when a purchase is completed in RestroPress.
 *
 * @param int $order_id The order ID.
 */
function adfoin_restropress_handle_order( $order_id ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'restropress', 'orderComplete' );
	if ( empty( $saved_records ) ) {
		return;
	}

	// Retrieve order details.
	$order_total = rpress_get_payment_amount( $order_id );
	$customer_info = rpress_get_payment_meta_user_info( $order_id );
	$customer_email = isset( $customer_info['email'] ) ? $customer_info['email'] : '';

	$posted_data = array(
		'order_id'      => $order_id,
		'order_total'   => $order_total,
		'customer_email'=> $customer_email,
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'rpress_complete_purchase', 'adfoin_restropress_handle_order', 10, 1 );