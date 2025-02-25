<?php
/**
 * Get WP Pizza Triggers.
 *
 * @param string $form_provider The current integration provider.
 * @return array|void
 */
function adfoin_wppizza_get_forms( $form_provider ) {
	if ( $form_provider !== 'wppizza' ) {
		return;
	}

	$triggers = array(
		'newOrder' => __( 'New Order Placed', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get WP Pizza Form Fields.
 *
 * @param string $form_provider The integration provider.
 * @param string $form_id       The specific trigger ID.
 * @return array|void
 */
function adfoin_wppizza_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'wppizza' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'newOrder' ) {
		$fields = array(
			'order_id'       => __( 'Order ID', 'advanced-form-integration' ),
			'customer_name'  => __( 'Customer Name', 'advanced-form-integration' ),
			'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
			'order_total'    => __( 'Order Total', 'advanced-form-integration' ),
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
function adfoin_wppizza_get_userdata( $user_id ) {
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
 * Handle New Order Placed.
 *
 * This function is triggered when an order is executed by WPPizza.
 *
 * @param int   $order_id      The order ID.
 * @param mixed $deprecated    Unused parameter.
 * @param mixed $print_templates Unused parameter.
 * @param array $order_details Order details array.
 */
function adfoin_wppizza_handle_new_order( $order_id, $deprecated, $print_templates, $order_details ) {
	$integration  = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'wppizza', 'newOrder' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Assuming customer details are stored in a 'customer' section of the order details.
	$customer_data = isset( $order_details['sections']['customer'] ) ? $order_details['sections']['customer'] : array();

	$posted_data = array(
		'order_id'       => $order_id,
		'customer_name'  => isset( $customer_data['cname']['value'] ) ? $customer_data['cname']['value'] : '',
		'customer_email' => isset( $customer_data['cemail']['value'] ) ? $customer_data['cemail']['value'] : '',
		'order_total'    => isset( $order_details['sections']['order']['total']['value'] ) ? $order_details['sections']['order']['total']['value'] : '',
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'wppizza_on_order_execute', 'adfoin_wppizza_handle_new_order', 10, 4 );