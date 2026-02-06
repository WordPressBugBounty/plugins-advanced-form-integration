<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get FluentCart triggers.
 *
 * @param string $form_provider Integration key.
 * @return array|void
 */
function adfoin_fluentcart_get_forms( $form_provider ) {
	if ( $form_provider !== 'fluentcart' ) {
		return;
	}

	return array(
		'orderCreated'         => __( 'Order Created', 'advanced-form-integration' ),
		'orderPaid'            => __( 'Order Paid', 'advanced-form-integration' ),
		'orderStatusChanged'   => __( 'Order Status Changed', 'advanced-form-integration' ),
		'paymentStatusChanged' => __( 'Payment Status Changed', 'advanced-form-integration' ),
		'shippingStatusChanged'=> __( 'Shipping Status Changed', 'advanced-form-integration' ),
		'orderRefunded'        => __( 'Order Refunded', 'advanced-form-integration' ),
	);
}

/**
 * Get FluentCart fields for the given trigger.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger key.
 * @return array|void
 */
function adfoin_fluentcart_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'fluentcart' ) {
		return;
	}

	$fields = adfoin_fluentcart_fields();

	if ( isset( $fields[ $form_id ] ) ) {
		return $fields[ $form_id ];
	}

	return $fields['default'];
}

/**
 * Field catalogue shared across all FluentCart triggers.
 *
 * @return array<string,array<string,string>>
 */
function adfoin_fluentcart_fields() {
	$common = array(
		'event_trigger'                  => __( 'Trigger Name', 'advanced-form-integration' ),
		'event_hook'                     => __( 'Trigger Hook', 'advanced-form-integration' ),
		'event_context_json'             => __( 'Trigger Context (JSON)', 'advanced-form-integration' ),
		'order_id'                       => __( 'Order ID', 'advanced-form-integration' ),
		'order_uuid'                     => __( 'Order UUID', 'advanced-form-integration' ),
		'order_parent_id'                => __( 'Parent Order ID', 'advanced-form-integration' ),
		'order_type'                     => __( 'Order Type', 'advanced-form-integration' ),
		'order_fulfillment_type'         => __( 'Fulfillment Type', 'advanced-form-integration' ),
		'order_mode'                     => __( 'Order Mode', 'advanced-form-integration' ),
		'order_status'                   => __( 'Current Order Status', 'advanced-form-integration' ),
		'order_old_status'               => __( 'Previous Order Status', 'advanced-form-integration' ),
		'order_new_status'               => __( 'New Order Status', 'advanced-form-integration' ),
		'order_status_change_type'       => __( 'Status Change Type', 'advanced-form-integration' ),
		'order_manage_stock'             => __( 'Manage Stock (Status Change)', 'advanced-form-integration' ),
		'order_activity_title'           => __( 'Status Activity Title', 'advanced-form-integration' ),
		'order_activity_content'         => __( 'Status Activity Content', 'advanced-form-integration' ),
		'order_activity_json'            => __( 'Status Activity (JSON)', 'advanced-form-integration' ),
		'payment_status'                 => __( 'Payment Status', 'advanced-form-integration' ),
		'payment_method'                 => __( 'Payment Method', 'advanced-form-integration' ),
		'payment_method_title'           => __( 'Payment Method Title', 'advanced-form-integration' ),
		'payment_mode'                   => __( 'Payment Mode', 'advanced-form-integration' ),
		'shipping_status'                => __( 'Shipping Status', 'advanced-form-integration' ),
		'currency'                       => __( 'Currency', 'advanced-form-integration' ),
		'currency_rate'                  => __( 'Exchange Rate', 'advanced-form-integration' ),
		'subtotal'                       => __( 'Subtotal', 'advanced-form-integration' ),
		'subtotal_raw'                   => __( 'Subtotal (Raw)', 'advanced-form-integration' ),
		'discount_manual'                => __( 'Manual Discount Total', 'advanced-form-integration' ),
		'discount_manual_raw'            => __( 'Manual Discount Total (Raw)', 'advanced-form-integration' ),
		'discount_coupon'                => __( 'Coupon Discount Total', 'advanced-form-integration' ),
		'discount_coupon_raw'            => __( 'Coupon Discount Total (Raw)', 'advanced-form-integration' ),
		'discount_tax'                   => __( 'Discount Tax Total', 'advanced-form-integration' ),
		'discount_tax_raw'               => __( 'Discount Tax Total (Raw)', 'advanced-form-integration' ),
		'shipping_total'                 => __( 'Shipping Total', 'advanced-form-integration' ),
		'shipping_total_raw'             => __( 'Shipping Total (Raw)', 'advanced-form-integration' ),
		'shipping_tax'                   => __( 'Shipping Tax', 'advanced-form-integration' ),
		'shipping_tax_raw'               => __( 'Shipping Tax (Raw)', 'advanced-form-integration' ),
		'tax_total'                      => __( 'Tax Total', 'advanced-form-integration' ),
		'tax_total_raw'                  => __( 'Tax Total (Raw)', 'advanced-form-integration' ),
		'total_amount'                   => __( 'Order Total', 'advanced-form-integration' ),
		'total_amount_raw'               => __( 'Order Total (Raw)', 'advanced-form-integration' ),
		'total_paid'                     => __( 'Total Paid', 'advanced-form-integration' ),
		'total_paid_raw'                 => __( 'Total Paid (Raw)', 'advanced-form-integration' ),
		'total_refund'                   => __( 'Total Refunded', 'advanced-form-integration' ),
		'total_refund_raw'               => __( 'Total Refunded (Raw)', 'advanced-form-integration' ),
		'balance_due'                    => __( 'Balance Due', 'advanced-form-integration' ),
		'balance_due_raw'                => __( 'Balance Due (Raw)', 'advanced-form-integration' ),
		'order_note'                     => __( 'Order Note', 'advanced-form-integration' ),
		'order_ip_address'               => __( 'Order IP Address', 'advanced-form-integration' ),
		'order_config_json'              => __( 'Order Configuration (JSON)', 'advanced-form-integration' ),
		'order_created_at'               => __( 'Order Created At', 'advanced-form-integration' ),
		'order_updated_at'               => __( 'Order Updated At', 'advanced-form-integration' ),
		'order_completed_at'             => __( 'Order Completed At', 'advanced-form-integration' ),
		'order_refunded_at'              => __( 'Order Refunded At', 'advanced-form-integration' ),
		'order_invoice_number'           => __( 'Invoice Number', 'advanced-form-integration' ),
		'order_receipt_number'           => __( 'Receipt Number', 'advanced-form-integration' ),
		'order_view_url'                 => __( 'Customer View URL', 'advanced-form-integration' ),
		'order_admin_url'                => __( 'Admin View URL', 'advanced-form-integration' ),
		'order_receipt_url'              => __( 'Receipt Download URL', 'advanced-form-integration' ),
		'customer_id'                    => __( 'Customer ID', 'advanced-form-integration' ),
		'customer_uuid'                  => __( 'Customer UUID', 'advanced-form-integration' ),
		'customer_user_id'               => __( 'Customer WP User ID', 'advanced-form-integration' ),
		'customer_contact_id'            => __( 'Customer Contact ID', 'advanced-form-integration' ),
		'customer_email'                 => __( 'Customer Email', 'advanced-form-integration' ),
		'customer_first_name'            => __( 'Customer First Name', 'advanced-form-integration' ),
		'customer_last_name'             => __( 'Customer Last Name', 'advanced-form-integration' ),
		'customer_full_name'             => __( 'Customer Full Name', 'advanced-form-integration' ),
		'customer_status'                => __( 'Customer Status', 'advanced-form-integration' ),
		'customer_country'               => __( 'Customer Country Code', 'advanced-form-integration' ),
		'customer_country_name'          => __( 'Customer Country Name', 'advanced-form-integration' ),
		'customer_state'                 => __( 'Customer State/Region', 'advanced-form-integration' ),
		'customer_city'                  => __( 'Customer City', 'advanced-form-integration' ),
		'customer_postcode'              => __( 'Customer Postal Code', 'advanced-form-integration' ),
		'customer_photo_url'             => __( 'Customer Photo URL', 'advanced-form-integration' ),
		'customer_purchase_count'        => __( 'Customer Purchase Count', 'advanced-form-integration' ),
		'customer_purchase_value_json'   => __( 'Customer Purchase Value (JSON)', 'advanced-form-integration' ),
		'customer_ltv'                   => __( 'Customer Lifetime Value', 'advanced-form-integration' ),
		'customer_aov'                   => __( 'Customer Average Order Value', 'advanced-form-integration' ),
		'customer_notes'                 => __( 'Customer Notes', 'advanced-form-integration' ),
		'customer_formatted_address_json'=> __( 'Customer Address (JSON)', 'advanced-form-integration' ),
		'customer_user_link'             => __( 'Customer User Edit URL', 'advanced-form-integration' ),
		'billing_name'                   => __( 'Billing Name', 'advanced-form-integration' ),
		'billing_first_name'             => __( 'Billing First Name', 'advanced-form-integration' ),
		'billing_last_name'              => __( 'Billing Last Name', 'advanced-form-integration' ),
		'billing_email'                  => __( 'Billing Email', 'advanced-form-integration' ),
		'billing_phone'                  => __( 'Billing Phone', 'advanced-form-integration' ),
		'billing_address_1'              => __( 'Billing Address 1', 'advanced-form-integration' ),
		'billing_address_2'              => __( 'Billing Address 2', 'advanced-form-integration' ),
		'billing_city'                   => __( 'Billing City', 'advanced-form-integration' ),
		'billing_state'                  => __( 'Billing State', 'advanced-form-integration' ),
		'billing_state_name'             => __( 'Billing State Name', 'advanced-form-integration' ),
		'billing_postcode'               => __( 'Billing Postal Code', 'advanced-form-integration' ),
		'billing_country'                => __( 'Billing Country Code', 'advanced-form-integration' ),
		'billing_country_name'           => __( 'Billing Country Name', 'advanced-form-integration' ),
		'billing_company'                => __( 'Billing Company', 'advanced-form-integration' ),
		'billing_formatted'              => __( 'Billing Address (Formatted)', 'advanced-form-integration' ),
		'billing_meta_json'              => __( 'Billing Meta (JSON)', 'advanced-form-integration' ),
		'shipping_name'                  => __( 'Shipping Name', 'advanced-form-integration' ),
		'shipping_first_name'            => __( 'Shipping First Name', 'advanced-form-integration' ),
		'shipping_last_name'             => __( 'Shipping Last Name', 'advanced-form-integration' ),
		'shipping_email'                 => __( 'Shipping Email', 'advanced-form-integration' ),
		'shipping_phone'                 => __( 'Shipping Phone', 'advanced-form-integration' ),
		'shipping_address_1'             => __( 'Shipping Address 1', 'advanced-form-integration' ),
		'shipping_address_2'             => __( 'Shipping Address 2', 'advanced-form-integration' ),
		'shipping_city'                  => __( 'Shipping City', 'advanced-form-integration' ),
		'shipping_state'                 => __( 'Shipping State', 'advanced-form-integration' ),
		'shipping_state_name'            => __( 'Shipping State Name', 'advanced-form-integration' ),
		'shipping_postcode'              => __( 'Shipping Postal Code', 'advanced-form-integration' ),
		'shipping_country'               => __( 'Shipping Country Code', 'advanced-form-integration' ),
		'shipping_country_name'          => __( 'Shipping Country Name', 'advanced-form-integration' ),
		'shipping_company'               => __( 'Shipping Company', 'advanced-form-integration' ),
		'shipping_formatted'             => __( 'Shipping Address (Formatted)', 'advanced-form-integration' ),
		'shipping_meta_json'             => __( 'Shipping Meta (JSON)', 'advanced-form-integration' ),
		'order_items_count'              => __( 'Item Count', 'advanced-form-integration' ),
		'order_items_quantity'           => __( 'Item Quantity Total', 'advanced-form-integration' ),
		'order_items_names'              => __( 'Item Names', 'advanced-form-integration' ),
		'order_items_payment_types'      => __( 'Item Payment Types', 'advanced-form-integration' ),
		'order_items_subtotal'           => __( 'Items Subtotal', 'advanced-form-integration' ),
		'order_items_subtotal_raw'       => __( 'Items Subtotal (Raw)', 'advanced-form-integration' ),
		'order_items_total'              => __( 'Items Total', 'advanced-form-integration' ),
		'order_items_total_raw'          => __( 'Items Total (Raw)', 'advanced-form-integration' ),
		'order_items_tax_total'          => __( 'Items Tax Total', 'advanced-form-integration' ),
		'order_items_tax_total_raw'      => __( 'Items Tax Total (Raw)', 'advanced-form-integration' ),
		'order_items_discount_total'     => __( 'Items Discount Total', 'advanced-form-integration' ),
		'order_items_discount_total_raw' => __( 'Items Discount Total (Raw)', 'advanced-form-integration' ),
		'order_items_json'               => __( 'Items (JSON)', 'advanced-form-integration' ),
		'transaction_id'                 => __( 'Transaction ID', 'advanced-form-integration' ),
		'transaction_uuid'               => __( 'Transaction UUID', 'advanced-form-integration' ),
		'transaction_status'             => __( 'Transaction Status', 'advanced-form-integration' ),
		'transaction_type'               => __( 'Transaction Type', 'advanced-form-integration' ),
		'transaction_payment_method'     => __( 'Transaction Payment Method', 'advanced-form-integration' ),
		'transaction_payment_mode'       => __( 'Transaction Payment Mode', 'advanced-form-integration' ),
		'transaction_payment_method_type'=> __( 'Transaction Payment Method Type', 'advanced-form-integration' ),
		'transaction_vendor_charge_id'   => __( 'Transaction Gateway Reference', 'advanced-form-integration' ),
		'transaction_currency'           => __( 'Transaction Currency', 'advanced-form-integration' ),
		'transaction_total'              => __( 'Transaction Total', 'advanced-form-integration' ),
		'transaction_total_raw'          => __( 'Transaction Total (Raw)', 'advanced-form-integration' ),
		'transaction_rate'               => __( 'Transaction Exchange Rate', 'advanced-form-integration' ),
		'transaction_card_brand'         => __( 'Card Brand', 'advanced-form-integration' ),
		'transaction_card_last4'         => __( 'Card Last 4', 'advanced-form-integration' ),
		'transaction_meta_json'          => __( 'Transaction Meta (JSON)', 'advanced-form-integration' ),
		'transaction_url'                => __( 'Transaction URL', 'advanced-form-integration' ),
		'transaction_receipt_url'        => __( 'Transaction Receipt URL', 'advanced-form-integration' ),
		'transaction_created_at'         => __( 'Transaction Created At', 'advanced-form-integration' ),
		'refund_type'                    => __( 'Refund Type', 'advanced-form-integration' ),
		'refunded_amount'                => __( 'Refunded Amount', 'advanced-form-integration' ),
		'refunded_amount_raw'            => __( 'Refunded Amount (Raw)', 'advanced-form-integration' ),
		'refund_manage_stock'            => __( 'Refund Manage Stock', 'advanced-form-integration' ),
		'refund_item_ids'                => __( 'Refunded Item IDs', 'advanced-form-integration' ),
		'refunded_items_count'           => __( 'Refunded Item Count', 'advanced-form-integration' ),
		'refunded_items_json'            => __( 'Refunded Items (JSON)', 'advanced-form-integration' ),
		'refund_new_items_json'          => __( 'Refund Request Items (JSON)', 'advanced-form-integration' ),
	);

	return array(
		'default' => $common,
	);
}

add_action( 'fluent_cart/order_created', 'adfoin_fluentcart_handle_order_created', 10, 1 );
add_action( 'fluent_cart/order_paid', 'adfoin_fluentcart_handle_order_paid', 10, 1 );
add_action( 'fluent_cart/order_status_changed', 'adfoin_fluentcart_handle_order_status_changed', 10, 1 );
add_action( 'fluent_cart/payment_status_changed', 'adfoin_fluentcart_handle_payment_status_changed', 10, 1 );
add_action( 'fluent_cart/shipping_status_changed', 'adfoin_fluentcart_handle_shipping_status_changed', 10, 1 );
add_action( 'fluent_cart/order_refunded', 'adfoin_fluentcart_handle_order_refunded', 10, 1 );

/**
 * Process "Order Created" events.
 *
 * @param array $payload Event payload.
 */
function adfoin_fluentcart_handle_order_created( $payload ) {
	adfoin_fluentcart_dispatch( 'orderCreated', $payload );
}

/**
 * Process "Order Paid" events.
 *
 * @param array $payload Event payload.
 */
function adfoin_fluentcart_handle_order_paid( $payload ) {
	adfoin_fluentcart_dispatch( 'orderPaid', $payload );
}

/**
 * Process standard order status transitions.
 *
 * @param array $payload Event payload.
 */
function adfoin_fluentcart_handle_order_status_changed( $payload ) {
	adfoin_fluentcart_dispatch(
		'orderStatusChanged',
		$payload,
		array(
			'status_change_type' => 'order_status',
		)
	);
}

/**
 * Process payment status updates.
 *
 * @param array $payload Event payload.
 */
function adfoin_fluentcart_handle_payment_status_changed( $payload ) {
	adfoin_fluentcart_dispatch(
		'paymentStatusChanged',
		$payload,
		array(
			'status_change_type' => 'payment_status',
		)
	);
}

/**
 * Process shipping status updates.
 *
 * @param array $payload Event payload.
 */
function adfoin_fluentcart_handle_shipping_status_changed( $payload ) {
	adfoin_fluentcart_dispatch(
		'shippingStatusChanged',
		$payload,
		array(
			'status_change_type' => 'shipping_status',
		)
	);
}

/**
 * Process refunds.
 *
 * @param array $payload Event payload.
 */
function adfoin_fluentcart_handle_order_refunded( $payload ) {
	adfoin_fluentcart_dispatch( 'orderRefunded', $payload );
}

/**
 * Dispatch FluentCart events through Advanced Form Integration.
 *
 * @param string $trigger Trigger key.
 * @param mixed  $event   Event payload.
 * @param array  $context Additional context.
 */
function adfoin_fluentcart_dispatch( $trigger, $event, $context = array() ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'fluentcart', $trigger );

	// if ( empty( $saved_records ) ) {
	// 	return;
	// }

	$context            = is_array( $context ) ? $context : array();
	$context['trigger'] = $trigger;
	$context['hook']    = current_filter();

	$payload = adfoin_fluentcart_build_payload( $event, $context );
	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build a normalized FluentCart payload.
 *
 * @param mixed $event   Event data.
 * @param array $context Additional context.
 * @return array<string,mixed>
 */
function adfoin_fluentcart_build_payload( $event, $context ) {
	$order = adfoin_fluentcart_get_event_value( $event, 'order' );

	if ( ! $order ) {
		return array();
	}

	$order_array = adfoin_fluentcart_model_to_array( $order );

	$customer = adfoin_fluentcart_get_event_value( $event, 'customer' );
	if ( ! $customer && is_object( $order ) && isset( $order->customer ) ) {
		$customer = $order->customer;
	}

	$transaction = adfoin_fluentcart_get_event_value( $event, 'transaction' );
	if ( ! $transaction && is_object( $order ) && method_exists( $order, 'getLatestTransaction' ) ) {
		$transaction = $order->getLatestTransaction();
	}

	$billing_address  = adfoin_fluentcart_extract_address( $order, 'billing_address' );
	$shipping_address = adfoin_fluentcart_extract_address( $order, 'shipping_address' );

	$items = adfoin_fluentcart_normalize_items_from_order( $order );

	$transaction_data = adfoin_fluentcart_normalize_transaction( $transaction );

	$activity_data = adfoin_fluentcart_normalize_array(
		adfoin_fluentcart_get_event_value( $event, 'activity', array() )
	);

	$manage_stock_value = adfoin_fluentcart_get_event_value( $event, 'manage_stock', null );
	if ( null === $manage_stock_value ) {
		$manage_stock_value = adfoin_fluentcart_get_event_value( $event, 'manageStock', null );
	}

	$order_config = isset( $order_array['config'] ) ? $order_array['config'] : array();
	if ( is_string( $order_config ) ) {
		$decoded_order_config = json_decode( $order_config, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			$order_config = $decoded_order_config;
		}
	}

	$customer_array = adfoin_fluentcart_model_to_array( $customer );
	$customer_address = adfoin_fluentcart_normalize_array(
		isset( $customer_array['formatted_address'] ) ? $customer_array['formatted_address'] : array()
	);
	$customer_purchase_value = adfoin_fluentcart_normalize_array(
		isset( $customer_array['purchase_value'] ) ? $customer_array['purchase_value'] : array()
	);

	$billing_norm  = adfoin_fluentcart_normalize_address( $billing_address );
	$shipping_norm = adfoin_fluentcart_normalize_address( $shipping_address );

	$order_subtotal_raw      = adfoin_fluentcart_to_int( isset( $order_array['subtotal'] ) ? $order_array['subtotal'] : 0 );
	$order_manual_discount   = adfoin_fluentcart_to_int( isset( $order_array['manual_discount_total'] ) ? $order_array['manual_discount_total'] : 0 );
	$order_coupon_discount   = adfoin_fluentcart_to_int( isset( $order_array['coupon_discount_total'] ) ? $order_array['coupon_discount_total'] : 0 );
	$order_discount_tax      = adfoin_fluentcart_to_int( isset( $order_array['discount_tax'] ) ? $order_array['discount_tax'] : 0 );
	$order_shipping_total    = adfoin_fluentcart_to_int( isset( $order_array['shipping_total'] ) ? $order_array['shipping_total'] : 0 );
	$order_shipping_tax      = adfoin_fluentcart_to_int( isset( $order_array['shipping_tax'] ) ? $order_array['shipping_tax'] : 0 );
	$order_tax_total_raw     = adfoin_fluentcart_to_int( isset( $order_array['tax_total'] ) ? $order_array['tax_total'] : 0 );
	$order_total_amount_raw  = adfoin_fluentcart_to_int( isset( $order_array['total_amount'] ) ? $order_array['total_amount'] : 0 );
	$order_total_paid_raw    = adfoin_fluentcart_to_int( isset( $order_array['total_paid'] ) ? $order_array['total_paid'] : 0 );
	$order_total_refund_raw  = adfoin_fluentcart_to_int( isset( $order_array['total_refund'] ) ? $order_array['total_refund'] : 0 );
	$order_balance_due_raw   = max( 0, $order_total_amount_raw - $order_total_paid_raw );

	$items_names           = array();
	$items_payment_types   = array();
	$items_quantity_sum    = 0;
	$items_subtotal_raw    = 0;
	$items_total_raw       = 0;
	$items_tax_total_raw   = 0;
	$items_discount_raw    = 0;

	foreach ( $items as $item ) {
		$items_names[]         = $item['name'];
		$items_payment_types[] = $item['payment_type'];
		$items_quantity_sum   += $item['quantity'];
		$items_subtotal_raw   += $item['subtotal_raw'];
		$items_total_raw      += $item['line_total_raw'];
		$items_tax_total_raw  += $item['tax_amount_raw'];
		$items_discount_raw   += $item['discount_total_raw'];
	}

	$refunded_amount_raw = adfoin_fluentcart_to_int(
		adfoin_fluentcart_get_event_value( $event, 'refunded_amount', 0 )
	);
	$refund_type = adfoin_fluentcart_to_string(
		adfoin_fluentcart_get_event_value( $event, 'type', '' )
	);

	$refund_item_ids = adfoin_fluentcart_get_event_value( $event, 'refunded_item_ids', array() );
	if ( ! is_array( $refund_item_ids ) ) {
		$refund_item_ids = array();
	}

	$refunded_items = adfoin_fluentcart_iterable_to_array(
		adfoin_fluentcart_get_event_value( $event, 'refunded_items', array() )
	);
	$refunded_items_normalized = array();
	foreach ( $refunded_items as $refunded_item ) {
		$refunded_items_normalized[] = adfoin_fluentcart_model_to_array( $refunded_item );
	}

	$refund_request_items = adfoin_fluentcart_iterable_to_array(
		adfoin_fluentcart_get_event_value( $event, 'new_refunded_items', array() )
	);
	$refund_request_normalized = array();
	foreach ( $refund_request_items as $refund_item ) {
		$refund_request_normalized[] = adfoin_fluentcart_model_to_array( $refund_item );
	}

	$transaction_meta = isset( $transaction_data['meta'] ) ? $transaction_data['meta'] : array();

	$payload = array(
		'event_trigger'                  => adfoin_fluentcart_to_string( isset( $context['trigger'] ) ? $context['trigger'] : '' ),
		'event_hook'                     => adfoin_fluentcart_to_string( isset( $context['hook'] ) ? $context['hook'] : '' ),
		'event_context_json'             => adfoin_fluentcart_json( $context ),
		'order_id'                       => adfoin_fluentcart_to_string( isset( $order_array['id'] ) ? $order_array['id'] : '' ),
		'order_uuid'                     => adfoin_fluentcart_to_string( isset( $order_array['uuid'] ) ? $order_array['uuid'] : '' ),
		'order_parent_id'                => adfoin_fluentcart_to_string( isset( $order_array['parent_id'] ) ? $order_array['parent_id'] : '' ),
		'order_type'                     => adfoin_fluentcart_to_string( isset( $order_array['type'] ) ? $order_array['type'] : '' ),
		'order_fulfillment_type'         => adfoin_fluentcart_to_string( isset( $order_array['fulfillment_type'] ) ? $order_array['fulfillment_type'] : '' ),
		'order_mode'                     => adfoin_fluentcart_to_string( isset( $order_array['mode'] ) ? $order_array['mode'] : '' ),
		'order_status'                   => adfoin_fluentcart_to_string( isset( $order_array['status'] ) ? $order_array['status'] : '' ),
		'order_old_status'               => adfoin_fluentcart_to_string( adfoin_fluentcart_get_event_value( $event, 'old_status', '' ) ),
		'order_new_status'               => adfoin_fluentcart_to_string( adfoin_fluentcart_get_event_value( $event, 'new_status', '' ) ),
		'order_status_change_type'       => adfoin_fluentcart_to_string( isset( $context['status_change_type'] ) ? $context['status_change_type'] : '' ),
		'order_manage_stock'             => adfoin_fluentcart_bool_to_string( $manage_stock_value ),
		'order_activity_title'           => adfoin_fluentcart_to_string( isset( $activity_data['title'] ) ? $activity_data['title'] : '' ),
		'order_activity_content'         => adfoin_fluentcart_to_string( isset( $activity_data['content'] ) ? $activity_data['content'] : '' ),
		'order_activity_json'            => adfoin_fluentcart_json( $activity_data ),
		'payment_status'                 => adfoin_fluentcart_to_string( isset( $order_array['payment_status'] ) ? $order_array['payment_status'] : '' ),
		'payment_method'                 => adfoin_fluentcart_to_string( isset( $order_array['payment_method'] ) ? $order_array['payment_method'] : '' ),
		'payment_method_title'           => adfoin_fluentcart_to_string( isset( $order_array['payment_method_title'] ) ? $order_array['payment_method_title'] : '' ),
		'payment_mode'                   => adfoin_fluentcart_to_string( isset( $order_array['payment_mode'] ) ? $order_array['payment_mode'] : '' ),
		'shipping_status'                => adfoin_fluentcart_to_string( isset( $order_array['shipping_status'] ) ? $order_array['shipping_status'] : '' ),
		'currency'                       => adfoin_fluentcart_to_string( isset( $order_array['currency'] ) ? $order_array['currency'] : '' ),
		'currency_rate'                  => adfoin_fluentcart_to_string( isset( $order_array['rate'] ) ? $order_array['rate'] : '' ),
		'subtotal'                       => adfoin_fluentcart_amount_to_decimal( $order_subtotal_raw ),
		'subtotal_raw'                   => $order_subtotal_raw !== 0 ? (string) $order_subtotal_raw : '',
		'discount_manual'                => adfoin_fluentcart_amount_to_decimal( $order_manual_discount ),
		'discount_manual_raw'            => $order_manual_discount !== 0 ? (string) $order_manual_discount : '',
		'discount_coupon'                => adfoin_fluentcart_amount_to_decimal( $order_coupon_discount ),
		'discount_coupon_raw'            => $order_coupon_discount !== 0 ? (string) $order_coupon_discount : '',
		'discount_tax'                   => adfoin_fluentcart_amount_to_decimal( $order_discount_tax ),
		'discount_tax_raw'               => $order_discount_tax !== 0 ? (string) $order_discount_tax : '',
		'shipping_total'                 => adfoin_fluentcart_amount_to_decimal( $order_shipping_total ),
		'shipping_total_raw'             => $order_shipping_total !== 0 ? (string) $order_shipping_total : '',
		'shipping_tax'                   => adfoin_fluentcart_amount_to_decimal( $order_shipping_tax ),
		'shipping_tax_raw'               => $order_shipping_tax !== 0 ? (string) $order_shipping_tax : '',
		'tax_total'                      => adfoin_fluentcart_amount_to_decimal( $order_tax_total_raw ),
		'tax_total_raw'                  => $order_tax_total_raw !== 0 ? (string) $order_tax_total_raw : '',
		'total_amount'                   => adfoin_fluentcart_amount_to_decimal( $order_total_amount_raw ),
		'total_amount_raw'               => (string) $order_total_amount_raw,
		'total_paid'                     => adfoin_fluentcart_amount_to_decimal( $order_total_paid_raw ),
		'total_paid_raw'                 => (string) $order_total_paid_raw,
		'total_refund'                   => adfoin_fluentcart_amount_to_decimal( $order_total_refund_raw ),
		'total_refund_raw'               => (string) $order_total_refund_raw,
		'balance_due'                    => adfoin_fluentcart_amount_to_decimal( $order_balance_due_raw ),
		'balance_due_raw'                => (string) $order_balance_due_raw,
		'order_note'                     => adfoin_fluentcart_to_string( isset( $order_array['note'] ) ? $order_array['note'] : '' ),
		'order_ip_address'               => adfoin_fluentcart_to_string( isset( $order_array['ip_address'] ) ? $order_array['ip_address'] : '' ),
		'order_config_json'              => adfoin_fluentcart_json( $order_config ),
		'order_created_at'               => adfoin_fluentcart_to_string( isset( $order_array['created_at'] ) ? $order_array['created_at'] : '' ),
		'order_updated_at'               => adfoin_fluentcart_to_string( isset( $order_array['updated_at'] ) ? $order_array['updated_at'] : '' ),
		'order_completed_at'             => adfoin_fluentcart_to_string( isset( $order_array['completed_at'] ) ? $order_array['completed_at'] : '' ),
		'order_refunded_at'              => adfoin_fluentcart_to_string( isset( $order_array['refunded_at'] ) ? $order_array['refunded_at'] : '' ),
		'order_invoice_number'           => adfoin_fluentcart_to_string( isset( $order_array['invoice_no'] ) ? $order_array['invoice_no'] : '' ),
		'order_receipt_number'           => adfoin_fluentcart_to_string( isset( $order_array['receipt_number'] ) ? $order_array['receipt_number'] : '' ),
		'order_view_url'                 => method_exists( $order, 'getViewUrl' ) ? adfoin_fluentcart_to_string( $order->getViewUrl() ) : '',
		'order_admin_url'                => method_exists( $order, 'getViewUrl' ) ? adfoin_fluentcart_to_string( $order->getViewUrl( 'admin' ) ) : '',
		'order_receipt_url'              => method_exists( $order, 'getReceiptUrl' ) ? adfoin_fluentcart_to_string( $order->getReceiptUrl() ) : '',
		'customer_id'                    => adfoin_fluentcart_to_string( isset( $customer_array['id'] ) ? $customer_array['id'] : '' ),
		'customer_uuid'                  => adfoin_fluentcart_to_string( isset( $customer_array['uuid'] ) ? $customer_array['uuid'] : '' ),
		'customer_user_id'               => adfoin_fluentcart_to_string( isset( $customer_array['user_id'] ) ? $customer_array['user_id'] : '' ),
		'customer_contact_id'            => adfoin_fluentcart_to_string( isset( $customer_array['contact_id'] ) ? $customer_array['contact_id'] : '' ),
		'customer_email'                 => adfoin_fluentcart_to_string( isset( $customer_array['email'] ) ? $customer_array['email'] : '' ),
		'customer_first_name'            => adfoin_fluentcart_to_string( isset( $customer_array['first_name'] ) ? $customer_array['first_name'] : '' ),
		'customer_last_name'             => adfoin_fluentcart_to_string( isset( $customer_array['last_name'] ) ? $customer_array['last_name'] : '' ),
		'customer_full_name'             => adfoin_fluentcart_to_string( isset( $customer_array['full_name'] ) ? $customer_array['full_name'] : '' ),
		'customer_status'                => adfoin_fluentcart_to_string( isset( $customer_array['status'] ) ? $customer_array['status'] : '' ),
		'customer_country'               => adfoin_fluentcart_to_string( isset( $customer_array['country'] ) ? $customer_array['country'] : '' ),
		'customer_country_name'          => adfoin_fluentcart_to_string(
			isset( $customer_array['country_name'] ) ? $customer_array['country_name'] : ( isset( $customer_address['country'] ) ? $customer_address['country'] : '' )
		),
		'customer_state'                 => adfoin_fluentcart_to_string( isset( $customer_array['state'] ) ? $customer_array['state'] : '' ),
		'customer_city'                  => adfoin_fluentcart_to_string( isset( $customer_array['city'] ) ? $customer_array['city'] : '' ),
		'customer_postcode'              => adfoin_fluentcart_to_string( isset( $customer_array['postcode'] ) ? $customer_array['postcode'] : '' ),
		'customer_photo_url'             => adfoin_fluentcart_to_string( isset( $customer_array['photo'] ) ? $customer_array['photo'] : '' ),
		'customer_purchase_count'        => adfoin_fluentcart_to_string( isset( $customer_array['purchase_count'] ) ? $customer_array['purchase_count'] : '' ),
		'customer_purchase_value_json'   => adfoin_fluentcart_json( $customer_purchase_value ),
		'customer_ltv'                   => adfoin_fluentcart_to_string( isset( $customer_array['ltv'] ) ? $customer_array['ltv'] : '' ),
		'customer_aov'                   => adfoin_fluentcart_to_string( isset( $customer_array['aov'] ) ? $customer_array['aov'] : '' ),
		'customer_notes'                 => adfoin_fluentcart_to_string( isset( $customer_array['notes'] ) ? $customer_array['notes'] : '' ),
		'customer_formatted_address_json'=> adfoin_fluentcart_json( $customer_address ),
		'customer_user_link'             => adfoin_fluentcart_to_string( isset( $customer_array['user_link'] ) ? $customer_array['user_link'] : '' ),
		'billing_name'                   => $billing_norm['name'],
		'billing_first_name'             => $billing_norm['first_name'],
		'billing_last_name'              => $billing_norm['last_name'],
		'billing_email'                  => $billing_norm['email'],
		'billing_phone'                  => $billing_norm['phone'],
		'billing_address_1'              => $billing_norm['address_1'],
		'billing_address_2'              => $billing_norm['address_2'],
		'billing_city'                   => $billing_norm['city'],
		'billing_state'                  => $billing_norm['state'],
		'billing_state_name'             => $billing_norm['state_name'],
		'billing_postcode'               => $billing_norm['postcode'],
		'billing_country'                => $billing_norm['country'],
		'billing_country_name'           => $billing_norm['country_name'],
		'billing_company'                => $billing_norm['company'],
		'billing_formatted'              => $billing_norm['formatted'],
		'billing_meta_json'              => adfoin_fluentcart_json( $billing_norm['meta'] ),
		'shipping_name'                  => $shipping_norm['name'],
		'shipping_first_name'            => $shipping_norm['first_name'],
		'shipping_last_name'             => $shipping_norm['last_name'],
		'shipping_email'                 => $shipping_norm['email'],
		'shipping_phone'                 => $shipping_norm['phone'],
		'shipping_address_1'             => $shipping_norm['address_1'],
		'shipping_address_2'             => $shipping_norm['address_2'],
		'shipping_city'                  => $shipping_norm['city'],
		'shipping_state'                 => $shipping_norm['state'],
		'shipping_state_name'            => $shipping_norm['state_name'],
		'shipping_postcode'              => $shipping_norm['postcode'],
		'shipping_country'               => $shipping_norm['country'],
		'shipping_country_name'          => $shipping_norm['country_name'],
		'shipping_company'               => $shipping_norm['company'],
		'shipping_formatted'             => $shipping_norm['formatted'],
		'shipping_meta_json'             => adfoin_fluentcart_json( $shipping_norm['meta'] ),
		'order_items_count'              => (string) count( $items ),
		'order_items_quantity'           => adfoin_fluentcart_format_number( $items_quantity_sum ),
		'order_items_names'              => adfoin_fluentcart_to_string( implode( ', ', array_filter( array_unique( $items_names ) ) ) ),
		'order_items_payment_types'      => adfoin_fluentcart_to_string( implode( ', ', array_filter( array_unique( $items_payment_types ) ) ) ),
		'order_items_subtotal'           => adfoin_fluentcart_amount_to_decimal( $items_subtotal_raw ),
		'order_items_subtotal_raw'       => $items_subtotal_raw !== 0 ? (string) $items_subtotal_raw : '',
		'order_items_total'              => adfoin_fluentcart_amount_to_decimal( $items_total_raw ),
		'order_items_total_raw'          => $items_total_raw !== 0 ? (string) $items_total_raw : '',
		'order_items_tax_total'          => adfoin_fluentcart_amount_to_decimal( $items_tax_total_raw ),
		'order_items_tax_total_raw'      => $items_tax_total_raw !== 0 ? (string) $items_tax_total_raw : '',
		'order_items_discount_total'     => adfoin_fluentcart_amount_to_decimal( $items_discount_raw ),
		'order_items_discount_total_raw' => $items_discount_raw !== 0 ? (string) $items_discount_raw : '',
		'order_items_json'               => adfoin_fluentcart_json( $items ),
		'transaction_id'                 => adfoin_fluentcart_to_string( isset( $transaction_data['id'] ) ? $transaction_data['id'] : '' ),
		'transaction_uuid'               => adfoin_fluentcart_to_string( isset( $transaction_data['uuid'] ) ? $transaction_data['uuid'] : '' ),
		'transaction_status'             => adfoin_fluentcart_to_string( isset( $transaction_data['status'] ) ? $transaction_data['status'] : '' ),
		'transaction_type'               => adfoin_fluentcart_to_string( isset( $transaction_data['type'] ) ? $transaction_data['type'] : '' ),
		'transaction_payment_method'     => adfoin_fluentcart_to_string( isset( $transaction_data['payment_method'] ) ? $transaction_data['payment_method'] : '' ),
		'transaction_payment_mode'       => adfoin_fluentcart_to_string( isset( $transaction_data['payment_mode'] ) ? $transaction_data['payment_mode'] : '' ),
		'transaction_payment_method_type'=> adfoin_fluentcart_to_string( isset( $transaction_data['payment_method_type'] ) ? $transaction_data['payment_method_type'] : '' ),
		'transaction_vendor_charge_id'   => adfoin_fluentcart_to_string( isset( $transaction_data['vendor_charge_id'] ) ? $transaction_data['vendor_charge_id'] : '' ),
		'transaction_currency'           => adfoin_fluentcart_to_string( isset( $transaction_data['currency'] ) ? $transaction_data['currency'] : '' ),
		'transaction_total'              => adfoin_fluentcart_amount_to_decimal( isset( $transaction_data['total_raw'] ) ? $transaction_data['total_raw'] : 0 ),
		'transaction_total_raw'          => isset( $transaction_data['total_raw'] ) ? (string) $transaction_data['total_raw'] : '',
		'transaction_rate'               => adfoin_fluentcart_to_string( isset( $transaction_data['rate'] ) ? $transaction_data['rate'] : '' ),
		'transaction_card_brand'         => adfoin_fluentcart_to_string( isset( $transaction_data['card_brand'] ) ? $transaction_data['card_brand'] : '' ),
		'transaction_card_last4'         => adfoin_fluentcart_to_string( isset( $transaction_data['card_last_4'] ) ? $transaction_data['card_last_4'] : '' ),
		'transaction_meta_json'          => adfoin_fluentcart_json( $transaction_meta ),
		'transaction_url'                => adfoin_fluentcart_to_string( isset( $transaction_data['url'] ) ? $transaction_data['url'] : '' ),
		'transaction_receipt_url'        => adfoin_fluentcart_to_string( isset( $transaction_data['receipt_url'] ) ? $transaction_data['receipt_url'] : '' ),
		'transaction_created_at'         => adfoin_fluentcart_to_string( isset( $transaction_data['created_at'] ) ? $transaction_data['created_at'] : '' ),
		'refund_type'                    => $refund_type,
		'refunded_amount'                => adfoin_fluentcart_amount_to_decimal( $refunded_amount_raw ),
		'refunded_amount_raw'            => $refunded_amount_raw !== 0 ? (string) $refunded_amount_raw : '',
		'refund_manage_stock'            => adfoin_fluentcart_bool_to_string( adfoin_fluentcart_get_event_value( $event, 'manage_stock', adfoin_fluentcart_get_event_value( $event, 'manageStock', '' ) ) ),
		'refund_item_ids'                => ! empty( $refund_item_ids ) ? implode( ', ', array_map( 'strval', $refund_item_ids ) ) : '',
		'refunded_items_count'           => (string) count( $refunded_items_normalized ),
		'refunded_items_json'            => adfoin_fluentcart_json( $refunded_items_normalized ),
		'refund_new_items_json'          => adfoin_fluentcart_json( $refund_request_normalized ),
	);

	return $payload;
}

/**
 * Safely extract a value from an event payload.
 *
 * @param mixed  $event Event payload.
 * @param string $key   Key to fetch.
 * @param mixed  $default Default value.
 * @return mixed
 */
function adfoin_fluentcart_get_event_value( $event, $key, $default = null ) {
	$variants = adfoin_fluentcart_key_variants( $key );

	foreach ( $variants as $variant ) {
		if ( is_array( $event ) && array_key_exists( $variant, $event ) ) {
			return $event[ $variant ];
		}

		if ( is_object( $event ) && isset( $event->{$variant} ) ) {
			return $event->{$variant};
		}
	}

	return $default;
}

/**
 * Generate alternative key spellings.
 *
 * @param string $key Base key.
 * @return array<int,string>
 */
function adfoin_fluentcart_key_variants( $key ) {
	$variants = array( $key );

	if ( strpos( $key, '_' ) !== false ) {
		$camel = lcfirst( str_replace( ' ', '', ucwords( str_replace( '_', ' ', $key ) ) ) );
		$variants[] = $camel;
	} else {
		$snake = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $key ) );
		$variants[] = $snake;
	}

	return array_unique( $variants );
}

/**
 * Convert a FluentCart model or collection to array.
 *
 * @param mixed $model Model instance.
 * @return array
 */
function adfoin_fluentcart_model_to_array( $model ) {
	if ( is_array( $model ) ) {
		return $model;
	}

	if ( is_object( $model ) ) {
		if ( method_exists( $model, 'toArray' ) ) {
			try {
				return (array) $model->toArray();
			} catch ( \Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
			}
		}

		if ( $model instanceof \JsonSerializable ) {
			return (array) $model->jsonSerialize();
		}

		return get_object_vars( $model );
	}

	return array();
}

/**
 * Normalize a potentially iterable list into an array.
 *
 * @param mixed $value Source value.
 * @return array
 */
function adfoin_fluentcart_iterable_to_array( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}

	if ( $value instanceof \Traversable ) {
		return iterator_to_array( $value );
	}

	if ( is_object( $value ) ) {
		if ( method_exists( $value, 'all' ) ) {
			try {
				return $value->all();
			} catch ( \Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
			}
		}

		if ( method_exists( $value, 'toArray' ) ) {
			try {
				return (array) $value->toArray();
			} catch ( \Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
			}
		}
	}

	return array();
}

/**
 * Convert FluentCart addresses to a normalized structure.
 *
 * @param mixed $address Address model.
 * @return array<string,mixed>
 */
function adfoin_fluentcart_normalize_address( $address ) {
	if ( ! $address ) {
		return array(
			'name'          => '',
			'first_name'    => '',
			'last_name'     => '',
			'email'         => '',
			'phone'         => '',
			'address_1'     => '',
			'address_2'     => '',
			'city'          => '',
			'state'         => '',
			'state_name'    => '',
			'postcode'      => '',
			'country'       => '',
			'country_name'  => '',
			'company'       => '',
			'formatted'     => '',
			'formatted_arr' => array(),
			'meta'          => array(),
		);
	}

	$array     = adfoin_fluentcart_model_to_array( $address );
	$formatted = array();

	if ( isset( $array['formatted_address'] ) ) {
		$formatted = adfoin_fluentcart_normalize_array( $array['formatted_address'] );
	} elseif ( is_object( $address ) && method_exists( $address, 'getFormattedAddress' ) ) {
		$formatted = adfoin_fluentcart_normalize_array( $address->getFormattedAddress() );
	}

	$meta = array();
	if ( isset( $array['meta'] ) ) {
		$meta = adfoin_fluentcart_normalize_array( $array['meta'] );
	} elseif ( is_object( $address ) && isset( $address->meta ) ) {
		$meta = adfoin_fluentcart_normalize_array( $address->meta );
	}

	$phone_candidates = array( 'phone', 'billing_phone', 'shipping_phone', 'phone_number', 'mobile' );
	$company_candidates = array( 'company', 'billing_company', 'shipping_company' );

	$phone = '';
	foreach ( $phone_candidates as $candidate ) {
		if ( isset( $meta[ $candidate ] ) && '' !== $meta[ $candidate ] ) {
			$phone = $meta[ $candidate ];
			break;
		}
	}

	$company = '';
	foreach ( $company_candidates as $candidate ) {
		if ( isset( $meta[ $candidate ] ) && '' !== $meta[ $candidate ] ) {
			$company = $meta[ $candidate ];
			break;
		}
	}

	$formatted_string = implode(
		', ',
		array_filter(
			array(
				isset( $array['name'] ) ? $array['name'] : ( isset( $formatted['full_name'] ) ? $formatted['full_name'] : '' ),
				isset( $array['address_1'] ) ? $array['address_1'] : '',
				isset( $array['address_2'] ) ? $array['address_2'] : '',
				isset( $array['city'] ) ? $array['city'] : '',
				isset( $formatted['state'] ) ? $formatted['state'] : ( isset( $array['state'] ) ? $array['state'] : '' ),
				isset( $array['postcode'] ) ? $array['postcode'] : '',
				isset( $formatted['country'] ) ? $formatted['country'] : ( isset( $array['country'] ) ? $array['country'] : '' ),
			)
		)
	);

	return array(
		'name'          => adfoin_fluentcart_to_string( isset( $array['name'] ) ? $array['name'] : ( isset( $formatted['full_name'] ) ? $formatted['full_name'] : '' ) ),
		'first_name'    => adfoin_fluentcart_to_string( isset( $array['first_name'] ) ? $array['first_name'] : ( isset( $formatted['first_name'] ) ? $formatted['first_name'] : '' ) ),
		'last_name'     => adfoin_fluentcart_to_string( isset( $array['last_name'] ) ? $array['last_name'] : ( isset( $formatted['last_name'] ) ? $formatted['last_name'] : '' ) ),
		'email'         => adfoin_fluentcart_to_string( isset( $array['email'] ) ? $array['email'] : '' ),
		'phone'         => adfoin_fluentcart_to_string( $phone ),
		'address_1'     => adfoin_fluentcart_to_string( isset( $array['address_1'] ) ? $array['address_1'] : '' ),
		'address_2'     => adfoin_fluentcart_to_string( isset( $array['address_2'] ) ? $array['address_2'] : '' ),
		'city'          => adfoin_fluentcart_to_string( isset( $array['city'] ) ? $array['city'] : '' ),
		'state'         => adfoin_fluentcart_to_string( isset( $array['state'] ) ? $array['state'] : '' ),
		'state_name'    => adfoin_fluentcart_to_string( isset( $formatted['state'] ) ? $formatted['state'] : '' ),
		'postcode'      => adfoin_fluentcart_to_string( isset( $array['postcode'] ) ? $array['postcode'] : '' ),
		'country'       => adfoin_fluentcart_to_string( isset( $array['country'] ) ? $array['country'] : '' ),
		'country_name'  => adfoin_fluentcart_to_string( isset( $formatted['country'] ) ? $formatted['country'] : '' ),
		'company'       => adfoin_fluentcart_to_string( $company ),
		'formatted'     => $formatted_string,
		'formatted_arr' => $formatted,
		'meta'          => $meta,
	);
}

/**
 * Extract address relation from order.
 *
 * @param mixed  $order Order instance.
 * @param string $relation Relation name.
 * @return mixed
 */
function adfoin_fluentcart_extract_address( $order, $relation ) {
	if ( is_object( $order ) && isset( $order->{$relation} ) ) {
		return $order->{$relation};
	}

	if ( is_array( $order ) && isset( $order[ $relation ] ) ) {
		return $order[ $relation ];
	}

	return null;
}

/**
 * Normalize order items into a structured list.
 *
 * @param mixed $order Order model.
 * @return array<int,array<string,mixed>>
 */
function adfoin_fluentcart_normalize_items_from_order( $order ) {
	$items_source = null;

	if ( is_object( $order ) && isset( $order->order_items ) ) {
		$items_source = $order->order_items;
	} elseif ( is_array( $order ) && isset( $order['order_items'] ) ) {
		$items_source = $order['order_items'];
	}

	$items_array = adfoin_fluentcart_iterable_to_array( $items_source );

	$normalized = array();

	foreach ( $items_array as $item ) {
		$item_array         = adfoin_fluentcart_model_to_array( $item );
		$quantity           = isset( $item_array['quantity'] ) ? (float) $item_array['quantity'] : 0;
		$unit_price_raw     = adfoin_fluentcart_to_int( isset( $item_array['unit_price'] ) ? $item_array['unit_price'] : 0 );
		$subtotal_raw       = adfoin_fluentcart_to_int( isset( $item_array['subtotal'] ) ? $item_array['subtotal'] : 0 );
		$line_total_raw     = adfoin_fluentcart_to_int( isset( $item_array['line_total'] ) ? $item_array['line_total'] : 0 );
		$tax_amount_raw     = adfoin_fluentcart_to_int( isset( $item_array['tax_amount'] ) ? $item_array['tax_amount'] : 0 );
		$discount_total_raw = adfoin_fluentcart_to_int( isset( $item_array['discount_total'] ) ? $item_array['discount_total'] : 0 );

		$other_info = array();
		if ( isset( $item_array['other_info'] ) ) {
			$other_info = adfoin_fluentcart_normalize_array( $item_array['other_info'] );
		}

		$line_meta = array();
		if ( isset( $item_array['line_meta'] ) ) {
			$line_meta = adfoin_fluentcart_normalize_array( $item_array['line_meta'] );
		}

		$normalized[] = array(
			'id'                => adfoin_fluentcart_to_int( isset( $item_array['id'] ) ? $item_array['id'] : 0 ),
			'order_id'          => adfoin_fluentcart_to_int( isset( $item_array['order_id'] ) ? $item_array['order_id'] : 0 ),
			'product_id'        => adfoin_fluentcart_to_int( isset( $item_array['post_id'] ) ? $item_array['post_id'] : 0 ),
			'variant_id'        => adfoin_fluentcart_to_int( isset( $item_array['object_id'] ) ? $item_array['object_id'] : 0 ),
			'post_title'        => adfoin_fluentcart_to_string( isset( $item_array['post_title'] ) ? $item_array['post_title'] : '' ),
			'item_title'        => adfoin_fluentcart_to_string( isset( $item_array['title'] ) ? $item_array['title'] : '' ),
			'name'              => adfoin_fluentcart_to_string(
				isset( $item_array['title'] ) ? $item_array['title'] : ( isset( $item_array['post_title'] ) ? $item_array['post_title'] : '' )
			),
			'quantity'          => $quantity,
			'unit_price'        => adfoin_fluentcart_amount_to_decimal( $unit_price_raw ),
			'unit_price_raw'    => $unit_price_raw,
			'subtotal'          => adfoin_fluentcart_amount_to_decimal( $subtotal_raw ),
			'subtotal_raw'      => $subtotal_raw,
			'line_total'        => adfoin_fluentcart_amount_to_decimal( $line_total_raw ),
			'line_total_raw'    => $line_total_raw,
			'tax_amount'        => adfoin_fluentcart_amount_to_decimal( $tax_amount_raw ),
			'tax_amount_raw'    => $tax_amount_raw,
			'discount_total'    => adfoin_fluentcart_amount_to_decimal( $discount_total_raw ),
			'discount_total_raw'=> $discount_total_raw,
			'payment_type'      => adfoin_fluentcart_to_string( isset( $item_array['payment_type'] ) ? $item_array['payment_type'] : '' ),
			'fulfillment_type'  => adfoin_fluentcart_to_string( isset( $item_array['fulfillment_type'] ) ? $item_array['fulfillment_type'] : '' ),
			'sku'               => adfoin_fluentcart_to_string( isset( $line_meta['sku'] ) ? $line_meta['sku'] : '' ),
			'other_info'        => $other_info,
			'line_meta'         => $line_meta,
			'payment_info'      => adfoin_fluentcart_to_string( isset( $item_array['payment_info'] ) ? $item_array['payment_info'] : '' ),
			'setup_info'        => adfoin_fluentcart_to_string( isset( $item_array['setup_info'] ) ? $item_array['setup_info'] : '' ),
		);
	}

	return $normalized;
}

/**
 * Normalize transaction data.
 *
 * @param mixed $transaction Transaction model.
 * @return array<string,mixed>
 */
function adfoin_fluentcart_normalize_transaction( $transaction ) {
	if ( ! $transaction ) {
		return array();
	}

	$array = adfoin_fluentcart_model_to_array( $transaction );

	$meta = array();
	if ( isset( $array['meta'] ) ) {
		$meta = adfoin_fluentcart_normalize_array( $array['meta'] );
	}

	$total_raw = adfoin_fluentcart_to_int( isset( $array['total'] ) ? $array['total'] : 0 );

	$receipt_url = '';
	if ( is_object( $transaction ) && method_exists( $transaction, 'getReceiptPageUrl' ) ) {
		$receipt_url = $transaction->getReceiptPageUrl( true );
	}

	return array(
		'id'                  => isset( $array['id'] ) ? $array['id'] : '',
		'uuid'                => isset( $array['uuid'] ) ? $array['uuid'] : '',
		'status'              => isset( $array['status'] ) ? $array['status'] : '',
		'type'                => isset( $array['transaction_type'] ) ? $array['transaction_type'] : '',
		'payment_method'      => isset( $array['payment_method'] ) ? $array['payment_method'] : '',
		'payment_mode'        => isset( $array['payment_mode'] ) ? $array['payment_mode'] : '',
		'payment_method_type' => isset( $array['payment_method_type'] ) ? $array['payment_method_type'] : '',
		'vendor_charge_id'    => isset( $array['vendor_charge_id'] ) ? $array['vendor_charge_id'] : '',
		'currency'            => isset( $array['currency'] ) ? $array['currency'] : '',
		'total_raw'           => $total_raw,
		'rate'                => isset( $array['rate'] ) ? $array['rate'] : '',
		'card_brand'          => isset( $array['card_brand'] ) ? $array['card_brand'] : '',
		'card_last_4'         => isset( $array['card_last_4'] ) ? $array['card_last_4'] : '',
		'meta'                => $meta,
		'url'                 => isset( $array['url'] ) ? $array['url'] : '',
		'created_at'          => isset( $array['created_at'] ) ? $array['created_at'] : '',
		'receipt_url'         => $receipt_url,
	);
}

/**
 * Normalize arbitrary value to array.
 *
 * @param mixed $value Input value.
 * @return array
 */
function adfoin_fluentcart_normalize_array( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}

	if ( is_object( $value ) ) {
		return adfoin_fluentcart_model_to_array( $value );
	}

	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			return $decoded;
		}
	}

	return array();
}

/**
 * Convert value to integer when possible.
 *
 * @param mixed $value Value to cast.
 * @return int
 */
function adfoin_fluentcart_to_int( $value ) {
	if ( is_numeric( $value ) ) {
		return (int) round( (float) $value );
	}

	return 0;
}

/**
 * Cast a value to string safely.
 *
 * @param mixed  $value   Input value.
 * @param string $default Default fallback.
 * @return string
 */
function adfoin_fluentcart_to_string( $value, $default = '' ) {
	if ( null === $value ) {
		return $default;
	}

	if ( $value instanceof \DateTimeInterface ) {
		return $value->format( 'Y-m-d H:i:s' );
	}

	if ( is_bool( $value ) ) {
		return $value ? '1' : '0';
	}

	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	return $default;
}

/**
 * Convert a boolean-like value to "yes"/"no".
 *
 * @param mixed $value Value to convert.
 * @return string
 */
function adfoin_fluentcart_bool_to_string( $value ) {
	if ( is_string( $value ) ) {
		$value = strtolower( $value );
		if ( in_array( $value, array( 'yes', 'true', '1' ), true ) ) {
			return 'yes';
		}
		if ( in_array( $value, array( 'no', 'false', '0' ), true ) ) {
			return 'no';
		}
	}

	return $value ? 'yes' : 'no';
}

/**
 * Encode value as JSON string.
 *
 * @param mixed $value Value to encode.
 * @return string
 */
function adfoin_fluentcart_json( $value ) {
	if ( empty( $value ) && 0 !== $value ) {
		return '';
	}

	if ( is_object( $value ) && method_exists( $value, 'toArray' ) ) {
		$value = $value->toArray();
	}

	$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $value ) : json_encode( $value );

	return is_string( $encoded ) ? $encoded : '';
}

/**
 * Convert FluentCart monetary values to decimal representation.
 *
 * @param mixed $amount Raw amount (cents).
 * @return string
 */
function adfoin_fluentcart_amount_to_decimal( $amount ) {
	if ( '' === $amount || null === $amount ) {
		return '';
	}

	if ( ! is_numeric( $amount ) ) {
		return adfoin_fluentcart_to_string( $amount );
	}

	if ( class_exists( '\FluentCart\App\Helpers\Helper' ) && method_exists( '\FluentCart\App\Helpers\Helper', 'toDecimalWithoutComma' ) ) {
		return \FluentCart\App\Helpers\Helper::toDecimalWithoutComma( $amount );
	}

	return number_format( (float) $amount / 100, 2, '.', '' );
}

/**
 * Format numeric value without trailing zeros.
 *
 * @param mixed $value Numeric value.
 * @return string
 */
function adfoin_fluentcart_format_number( $value ) {
	if ( ! is_numeric( $value ) ) {
		return adfoin_fluentcart_to_string( $value );
	}

	if ( floor( $value ) == $value ) {
		return (string) (int) $value;
	}

	return rtrim( rtrim( number_format( (float) $value, 4, '.', '' ), '0' ), '.' );
}
