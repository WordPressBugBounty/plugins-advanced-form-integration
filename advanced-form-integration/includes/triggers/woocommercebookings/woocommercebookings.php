<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter( 'adfoin_form_providers', 'adfoin_woocommercebookings_filter_provider' );

/**
 * Conditionally expose the WooCommerce Bookings provider.
 *
 * @param array $providers Registered providers.
 *
 * @return array
 */
function adfoin_woocommercebookings_filter_provider( $providers ) {
	if ( ! is_plugin_active( 'woocommerce-bookings/woocommerce-bookings.php' ) ) {
		unset( $providers['woocommercebookings'] );

		return $providers;
	}

	$providers['woocommercebookings'] = __( 'WooCommerce Bookings', 'advanced-form-integration' );

	return $providers;
}

/**
 * Get trigger labels.
 *
 * @return array<string,string>
 */
function adfoin_woocommercebookings_triggers() {
	return array(
		'bookingCreated'                    => __( 'Booking Created', 'advanced-form-integration' ),
		'bookingStatusChanged'              => __( 'Booking Status Changed', 'advanced-form-integration' ),
		'bookingStatusUnpaid'               => __( 'Booking Status: Unpaid', 'advanced-form-integration' ),
		'bookingStatusPendingConfirmation'  => __( 'Booking Status: Pending Confirmation', 'advanced-form-integration' ),
		'bookingStatusConfirmed'            => __( 'Booking Status: Confirmed', 'advanced-form-integration' ),
		'bookingStatusPaid'                 => __( 'Booking Status: Paid', 'advanced-form-integration' ),
		'bookingStatusCancelled'            => __( 'Booking Status: Cancelled', 'advanced-form-integration' ),
		'bookingStatusComplete'             => __( 'Booking Status: Complete', 'advanced-form-integration' ),
	);
}

/**
 * Return triggers for a provider.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommercebookings_get_forms( $form_provider ) {
	if ( 'woocommercebookings' !== $form_provider ) {
		return;
	}

	return adfoin_woocommercebookings_triggers();
}

/**
 * Return field map for the provider.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommercebookings_get_form_fields( $form_provider, $form_id ) {
	if ( 'woocommercebookings' !== $form_provider ) {
		return;
	}

	return adfoin_get_woocommercebookings_fields();
}

/**
 * Resolve trigger label.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return string|false
 */
function adfoin_woocommercebookings_get_form_name( $form_provider, $form_id ) {
	if ( 'woocommercebookings' !== $form_provider ) {
		return false;
	}

	$triggers = adfoin_woocommercebookings_triggers();

	return isset( $triggers[ $form_id ] ) ? $triggers[ $form_id ] : false;
}

/**
 * Define available booking/order fields.
 *
 * @return array<string,string>
 */
function adfoin_get_woocommercebookings_fields() {
	return array(
		'booking_trigger'                  => __( 'Trigger Name', 'advanced-form-integration' ),
		'booking_form_id'                  => __( 'Trigger Key', 'advanced-form-integration' ),
		'booking_triggered_at'             => __( 'Trigger Time', 'advanced-form-integration' ),
		'booking_id'                       => __( 'Booking ID', 'advanced-form-integration' ),
		'booking_parent_id'                => __( 'Parent Booking ID', 'advanced-form-integration' ),
		'booking_order_id'                 => __( 'Related Order ID', 'advanced-form-integration' ),
		'booking_order_item_id'            => __( 'Order Item ID', 'advanced-form-integration' ),
		'booking_status'                   => __( 'Booking Status', 'advanced-form-integration' ),
		'booking_status_label'             => __( 'Booking Status Label', 'advanced-form-integration' ),
		'booking_previous_status'          => __( 'Previous Booking Status', 'advanced-form-integration' ),
		'booking_previous_status_label'    => __( 'Previous Status Label', 'advanced-form-integration' ),
		'booking_all_day'                  => __( 'All Day Booking', 'advanced-form-integration' ),
		'booking_cost'                     => __( 'Booking Cost', 'advanced-form-integration' ),
		'booking_start'                    => __( 'Start Date (UTC)', 'advanced-form-integration' ),
		'booking_start_local'              => __( 'Start Date (Local)', 'advanced-form-integration' ),
		'booking_end'                      => __( 'End Date (UTC)', 'advanced-form-integration' ),
		'booking_end_local'                => __( 'End Date (Local)', 'advanced-form-integration' ),
		'booking_start_timestamp'          => __( 'Start Timestamp', 'advanced-form-integration' ),
		'booking_end_timestamp'            => __( 'End Timestamp', 'advanced-form-integration' ),
		'booking_duration_minutes'         => __( 'Duration (Minutes)', 'advanced-form-integration' ),
		'booking_duration_hours'           => __( 'Duration (Hours)', 'advanced-form-integration' ),
		'booking_persons_total'            => __( 'Persons Total', 'advanced-form-integration' ),
		'booking_person_counts'            => __( 'Person Counts (JSON)', 'advanced-form-integration' ),
		'booking_person_details'           => __( 'Person Details (JSON)', 'advanced-form-integration' ),
		'booking_person_details_plain'     => __( 'Person Details (Text)', 'advanced-form-integration' ),
		'booking_resource_id'              => __( 'Resource ID', 'advanced-form-integration' ),
		'booking_resource_name'            => __( 'Resource Name', 'advanced-form-integration' ),
		'booking_resource_base_cost'       => __( 'Resource Base Cost', 'advanced-form-integration' ),
		'booking_resource_block_cost'      => __( 'Resource Block Cost', 'advanced-form-integration' ),
		'booking_product_id'               => __( 'Product ID', 'advanced-form-integration' ),
		'booking_product_name'             => __( 'Product Name', 'advanced-form-integration' ),
		'booking_product_sku'              => __( 'Product SKU', 'advanced-form-integration' ),
		'booking_product_type'             => __( 'Product Type', 'advanced-form-integration' ),
		'booking_product_url'              => __( 'Product URL', 'advanced-form-integration' ),
		'booking_display_cost'             => __( 'Display Cost', 'advanced-form-integration' ),
		'booking_created'                  => __( 'Booking Created', 'advanced-form-integration' ),
		'booking_modified'                 => __( 'Booking Modified', 'advanced-form-integration' ),
		'booking_timezone'                 => __( 'Booking Timezone', 'advanced-form-integration' ),
		'booking_local_timezone'           => __( 'Customer Timezone', 'advanced-form-integration' ),
		'booking_customer_id'              => __( 'Customer User ID', 'advanced-form-integration' ),
		'booking_customer_name'            => __( 'Customer Name', 'advanced-form-integration' ),
		'booking_customer_email'           => __( 'Customer Email', 'advanced-form-integration' ),
		'booking_is_guest'                 => __( 'Is Guest Booking', 'advanced-form-integration' ),
		'booking_cancel_url'               => __( 'Cancel URL', 'advanced-form-integration' ),
		'booking_edit_url'                 => __( 'Admin Edit URL', 'advanced-form-integration' ),
		'booking_notes'                    => __( 'Customer Note', 'advanced-form-integration' ),
		'order_id'                         => __( 'Order ID', 'advanced-form-integration' ),
		'order_number'                     => __( 'Order Number', 'advanced-form-integration' ),
		'order_status'                     => __( 'Order Status', 'advanced-form-integration' ),
		'order_total'                      => __( 'Order Total', 'advanced-form-integration' ),
		'order_subtotal'                   => __( 'Order Subtotal', 'advanced-form-integration' ),
		'order_total_tax'                  => __( 'Order Total Tax', 'advanced-form-integration' ),
		'order_discount_total'             => __( 'Order Discount Total', 'advanced-form-integration' ),
		'order_discount_tax'               => __( 'Order Discount Tax', 'advanced-form-integration' ),
		'order_shipping_total'             => __( 'Order Shipping Total', 'advanced-form-integration' ),
		'order_shipping_tax'               => __( 'Order Shipping Tax', 'advanced-form-integration' ),
		'order_currency'                   => __( 'Order Currency', 'advanced-form-integration' ),
		'order_payment_method'             => __( 'Payment Method', 'advanced-form-integration' ),
		'order_payment_method_title'       => __( 'Payment Method Title', 'advanced-form-integration' ),
		'order_transaction_id'             => __( 'Transaction ID', 'advanced-form-integration' ),
		'order_created'                    => __( 'Order Created', 'advanced-form-integration' ),
		'order_completed'                  => __( 'Order Completed', 'advanced-form-integration' ),
		'order_paid_date'                  => __( 'Order Paid Date', 'advanced-form-integration' ),
		'order_items'                      => __( 'Order Items (JSON)', 'advanced-form-integration' ),
		'order_coupons'                    => __( 'Order Coupons', 'advanced-form-integration' ),
		'order_url'                        => __( 'Order Admin URL', 'advanced-form-integration' ),
		'billing_first_name'               => __( 'Billing First Name', 'advanced-form-integration' ),
		'billing_last_name'                => __( 'Billing Last Name', 'advanced-form-integration' ),
		'billing_company'                  => __( 'Billing Company', 'advanced-form-integration' ),
		'billing_address_1'                => __( 'Billing Address 1', 'advanced-form-integration' ),
		'billing_address_2'                => __( 'Billing Address 2', 'advanced-form-integration' ),
		'billing_city'                     => __( 'Billing City', 'advanced-form-integration' ),
		'billing_state'                    => __( 'Billing State', 'advanced-form-integration' ),
		'billing_postcode'                 => __( 'Billing Postcode', 'advanced-form-integration' ),
		'billing_country'                  => __( 'Billing Country', 'advanced-form-integration' ),
		'billing_email'                    => __( 'Billing Email', 'advanced-form-integration' ),
		'billing_phone'                    => __( 'Billing Phone', 'advanced-form-integration' ),
		'shipping_first_name'              => __( 'Shipping First Name', 'advanced-form-integration' ),
		'shipping_last_name'               => __( 'Shipping Last Name', 'advanced-form-integration' ),
		'shipping_company'                 => __( 'Shipping Company', 'advanced-form-integration' ),
		'shipping_address_1'               => __( 'Shipping Address 1', 'advanced-form-integration' ),
		'shipping_address_2'               => __( 'Shipping Address 2', 'advanced-form-integration' ),
		'shipping_city'                    => __( 'Shipping City', 'advanced-form-integration' ),
		'shipping_state'                   => __( 'Shipping State', 'advanced-form-integration' ),
		'shipping_postcode'                => __( 'Shipping Postcode', 'advanced-form-integration' ),
		'shipping_country'                 => __( 'Shipping Country', 'advanced-form-integration' ),
		'shipping_phone'                   => __( 'Shipping Phone', 'advanced-form-integration' ),
		'customer_ip_address'              => __( 'Customer IP Address', 'advanced-form-integration' ),
		'customer_user_agent'              => __( 'Customer User Agent', 'advanced-form-integration' ),
		'customer_note'                    => __( 'Order Customer Note', 'advanced-form-integration' ),
		'booking_meta_json'                => __( 'Booking Meta (JSON)', 'advanced-form-integration' ),
		'order_meta_json'                  => __( 'Order Meta (JSON)', 'advanced-form-integration' ),
		'product_meta_json'                => __( 'Product Meta (JSON)', 'advanced-form-integration' ),
		'resource_meta_json'               => __( 'Resource Meta (JSON)', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_woocommercebookings_bootstrap', 20 );

/**
 * Register runtime watchers once WooCommerce Bookings is available.
 */
function adfoin_woocommercebookings_bootstrap() {
	if ( ! function_exists( 'get_wc_booking' ) ) {
		return;
	}

	add_action( 'woocommerce_new_booking', 'adfoin_woocommercebookings_handle_new_booking', 10, 1 );
	add_action( 'woocommerce_booking_status_changed', 'adfoin_woocommercebookings_handle_status_changed', 10, 4 );
}

/**
 * Handle creation events.
 *
 * @param int $booking_id Booking ID.
 */
function adfoin_woocommercebookings_handle_new_booking( $booking_id ) {
	$booking = adfoin_woocommercebookings_resolve_booking( $booking_id );
	if ( ! $booking ) {
		return;
	}

	$payload = adfoin_woocommercebookings_prepare_payload( $booking );

	adfoin_woocommercebookings_send_payload( 'bookingCreated', $booking, $payload );
}

/**
 * Handle status transition events.
 *
 * @param string     $from       Previous status.
 * @param string     $to         New status.
 * @param int        $booking_id Booking ID.
 * @param WC_Booking $booking    Booking instance.
 */
function adfoin_woocommercebookings_handle_status_changed( $from, $to, $booking_id, $booking ) {
	$booking = adfoin_woocommercebookings_resolve_booking( $booking, $booking_id );
	if ( ! $booking ) {
		return;
	}

	$base_payload                               = adfoin_woocommercebookings_prepare_payload( $booking );
	$base_payload['booking_previous_status']    = $from;
	$base_payload['booking_previous_status_label'] = adfoin_woocommercebookings_status_label( $from );

	adfoin_woocommercebookings_send_payload( 'bookingStatusChanged', $booking, $base_payload );

	$status_map = array(
		'unpaid'               => 'bookingStatusUnpaid',
		'pending-confirmation' => 'bookingStatusPendingConfirmation',
		'confirmed'            => 'bookingStatusConfirmed',
		'paid'                 => 'bookingStatusPaid',
		'cancelled'            => 'bookingStatusCancelled',
		'complete'             => 'bookingStatusComplete',
	);

	if ( isset( $status_map[ $to ] ) ) {
		$target_payload                    = $base_payload;
		$target_payload['booking_status']  = $to;
		$target_payload['booking_status_label'] = adfoin_woocommercebookings_status_label( $to );

		adfoin_woocommercebookings_send_payload( $status_map[ $to ], $booking, $target_payload );
	}
}

/**
 * Normalize booking payload.
 *
 * @param WC_Booking $booking Booking instance.
 *
 * @return array<string,mixed>
 */
function adfoin_woocommercebookings_prepare_payload( WC_Booking $booking ) {
	$order    = $booking->get_order();
	$product  = $booking->get_product();
	$resource = $booking->get_resource();
	$customer = $booking->get_customer();

	$start      = $booking->get_start( 'view' );
	$end        = $booking->get_end( 'view' );
	$persons    = $booking->get_person_counts();
	$person_map = adfoin_woocommercebookings_person_details( $booking, $product );

	$order_items = $order ? adfoin_woocommercebookings_order_items_payload( $order ) : array();
	$coupons     = $order ? $order->get_coupon_codes() : array();

	$order_created   = $order ? adfoin_woocommercebookings_format_wc_datetime( $order->get_date_created() ) : '';
	$order_completed = $order ? adfoin_woocommercebookings_format_wc_datetime( $order->get_date_completed() ) : '';
	$order_paid      = $order ? adfoin_woocommercebookings_format_wc_datetime( $order->get_date_paid() ) : '';

	$booking_meta  = adfoin_woocommercebookings_collect_post_meta( $booking->get_id() );
	$order_meta    = ( $order && $order->get_id() ) ? adfoin_woocommercebookings_collect_post_meta( $order->get_id() ) : array();
	$product_meta  = ( $product && $product->get_id() ) ? adfoin_woocommercebookings_collect_post_meta( $product->get_id() ) : array();
	$resource_meta = ( $resource && $resource->get_id() ) ? adfoin_woocommercebookings_collect_post_meta( $resource->get_id() ) : array();

	$customer_id = $booking->get_customer_id();
	if ( ! $customer_id && $order ) {
		$customer_id = $order->get_customer_id();
	}

	$payload = array(
		'booking_id'                       => $booking->get_id(),
		'booking_parent_id'                => $booking->get_parent_id(),
		'booking_order_id'                 => $booking->get_order_id(),
		'booking_order_item_id'            => $booking->get_order_item_id(),
		'booking_status'                   => $booking->get_status(),
		'booking_status_label'             => adfoin_woocommercebookings_status_label( $booking->get_status() ),
		'booking_previous_status'          => '',
		'booking_previous_status_label'    => '',
		'booking_all_day'                  => $booking->is_all_day() ? 'yes' : 'no',
		'booking_cost'                     => $booking->get_cost(),
		'booking_start'                    => adfoin_woocommercebookings_format_datetime( $start ),
		'booking_start_local'              => $booking->get_start_date(),
		'booking_end'                      => adfoin_woocommercebookings_format_datetime( $end ),
		'booking_end_local'                => $booking->get_end_date(),
		'booking_start_timestamp'          => $start,
		'booking_end_timestamp'            => $end,
		'booking_duration_minutes'         => ( $start && $end ) ? round( ( $end - $start ) / MINUTE_IN_SECONDS ) : '',
		'booking_duration_hours'           => ( $start && $end ) ? round( ( $end - $start ) / HOUR_IN_SECONDS, 2 ) : '',
		'booking_persons_total'            => $booking->get_persons_total(),
		'booking_person_counts'            => adfoin_woocommercebookings_encode_or_empty( $persons ),
		'booking_person_details'           => adfoin_woocommercebookings_encode_or_empty( $person_map ),
		'booking_person_details_plain'     => adfoin_woocommercebookings_person_details_plain( $person_map ),
		'booking_resource_id'              => $booking->get_resource_id(),
		'booking_resource_name'            => $resource ? $resource->get_name() : '',
		'booking_resource_base_cost'       => $resource ? $resource->get_base_cost() : '',
		'booking_resource_block_cost'      => $resource ? $resource->get_block_cost() : '',
		'booking_product_id'               => $booking->get_product_id(),
		'booking_product_name'             => $product ? $product->get_name() : '',
		'booking_product_sku'              => $product ? $product->get_sku() : '',
		'booking_product_type'             => $product ? $product->get_type() : '',
		'booking_product_url'              => $product ? $product->get_permalink() : '',
		'booking_display_cost'             => ( $product && method_exists( $product, 'get_display_cost' ) ) ? $product->get_display_cost() : '',
		'booking_created'                  => adfoin_woocommercebookings_format_datetime( $booking->get_date_created() ),
		'booking_modified'                 => adfoin_woocommercebookings_format_datetime( $booking->get_date_modified() ),
		'booking_timezone'                 => method_exists( $booking, 'get_booking_timezone' ) ? $booking->get_booking_timezone() : '',
		'booking_local_timezone'           => $booking->get_local_timezone(),
		'booking_customer_id'              => $booking->get_customer_id(),
		'booking_customer_name'            => isset( $customer->name ) ? $customer->name : '',
		'booking_customer_email'           => isset( $customer->email ) ? $customer->email : '',
		'booking_is_guest'                 => ( isset( $customer->user_id ) && $customer->user_id ) ? 'no' : 'yes',
		'booking_cancel_url'               => $booking->get_cancel_url(),
		'booking_edit_url'                 => get_edit_post_link( $booking->get_id(), '' ),
		'booking_notes'                    => $order ? $order->get_customer_note() : '',
		'order_id'                         => $order ? $order->get_id() : '',
		'order_number'                     => $order ? $order->get_order_number() : '',
		'order_status'                     => $order ? $order->get_status() : '',
		'order_total'                      => $order ? $order->get_total() : '',
		'order_subtotal'                   => $order ? $order->get_subtotal() : '',
		'order_total_tax'                  => $order ? $order->get_total_tax() : '',
		'order_discount_total'             => $order ? $order->get_discount_total() : '',
		'order_discount_tax'               => $order ? $order->get_discount_tax() : '',
		'order_shipping_total'             => $order ? $order->get_shipping_total() : '',
		'order_shipping_tax'               => $order ? $order->get_shipping_tax() : '',
		'order_currency'                   => $order ? $order->get_currency() : '',
		'order_payment_method'             => $order ? $order->get_payment_method() : '',
		'order_payment_method_title'       => $order ? $order->get_payment_method_title() : '',
		'order_transaction_id'             => $order ? $order->get_transaction_id() : '',
		'order_created'                    => $order_created,
		'order_completed'                  => $order_completed,
		'order_paid_date'                  => $order_paid,
		'order_items'                      => adfoin_woocommercebookings_encode_or_empty( $order_items ),
		'order_coupons'                    => ! empty( $coupons ) ? implode( ',', $coupons ) : '',
		'order_url'                        => ( $order && $order->get_id() ) ? admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) : '',
		'billing_first_name'               => $order ? $order->get_billing_first_name() : '',
		'billing_last_name'                => $order ? $order->get_billing_last_name() : '',
		'billing_company'                  => $order ? $order->get_billing_company() : '',
		'billing_address_1'                => $order ? $order->get_billing_address_1() : '',
		'billing_address_2'                => $order ? $order->get_billing_address_2() : '',
		'billing_city'                     => $order ? $order->get_billing_city() : '',
		'billing_state'                    => $order ? $order->get_billing_state() : '',
		'billing_postcode'                 => $order ? $order->get_billing_postcode() : '',
		'billing_country'                  => $order ? $order->get_billing_country() : '',
		'billing_email'                    => $order ? $order->get_billing_email() : '',
		'billing_phone'                    => $order ? $order->get_billing_phone() : '',
		'shipping_first_name'              => $order ? $order->get_shipping_first_name() : '',
		'shipping_last_name'               => $order ? $order->get_shipping_last_name() : '',
		'shipping_company'                 => $order ? $order->get_shipping_company() : '',
		'shipping_address_1'               => $order ? $order->get_shipping_address_1() : '',
		'shipping_address_2'               => $order ? $order->get_shipping_address_2() : '',
		'shipping_city'                    => $order ? $order->get_shipping_city() : '',
		'shipping_state'                   => $order ? $order->get_shipping_state() : '',
		'shipping_postcode'                => $order ? $order->get_shipping_postcode() : '',
		'shipping_country'                 => $order ? $order->get_shipping_country() : '',
		'shipping_phone'                   => ( $order && method_exists( $order, 'get_shipping_phone' ) ) ? $order->get_shipping_phone() : '',
		'customer_ip_address'              => $order ? $order->get_customer_ip_address() : '',
		'customer_user_agent'              => $order ? $order->get_customer_user_agent() : '',
		'customer_note'                    => $order ? $order->get_customer_note() : '',
		'booking_meta_json'                => adfoin_woocommercebookings_encode_or_empty( $booking_meta ),
		'order_meta_json'                  => adfoin_woocommercebookings_encode_or_empty( $order_meta ),
		'product_meta_json'                => adfoin_woocommercebookings_encode_or_empty( $product_meta ),
		'resource_meta_json'               => adfoin_woocommercebookings_encode_or_empty( $resource_meta ),
	);

	if ( method_exists( $booking, 'get_booking_timezone' ) && ! $payload['booking_timezone'] ) {
		$payload['booking_timezone'] = $booking->get_booking_timezone();
	}

	if ( $customer_id ) {
		$payload['booking_customer_id'] = $customer_id;
	}

	return $payload;
}

/**
 * Dispatch payload to saved actions.
 *
 * @param string     $form_id Trigger key.
 * @param WC_Booking $booking Booking instance.
 * @param array      $payload Prepared payload data.
 */
function adfoin_woocommercebookings_send_payload( $form_id, WC_Booking $booking, array $payload ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'woocommercebookings', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$order    = $booking->get_order();
	$product  = $booking->get_product();
	$resource = $booking->get_resource();

	$data                            = $payload;
	$data['booking_form_id']         = $form_id;
	$data['booking_trigger']         = adfoin_woocommercebookings_get_form_name( 'woocommercebookings', $form_id );
	$data['booking_triggered_at']    = adfoin_woocommercebookings_format_datetime( current_time( 'timestamp' ) );

	if ( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
		$data = array_merge( $data, adfoin_capture_utm_and_url_values() );
	}

	$meta_placeholders = adfoin_woocommercebookings_meta_placeholders( $saved_records );
	$meta_values       = adfoin_woocommercebookings_meta_values(
		$booking,
		$order,
		$product,
		$resource,
		$meta_placeholders
	);

	if ( ! empty( $meta_values ) ) {
		$data = array_merge( $data, $meta_values );
	}

	foreach ( $saved_records as $record ) {
		$action_provider = $record['action_provider'];

		if ( function_exists( "adfoin_{$action_provider}_send_data" ) ) {
			call_user_func( "adfoin_{$action_provider}_send_data", $record, $data );
		}
	}
}

/**
 * Resolve booking instance from supplied data.
 *
 * @param mixed $booking    Booking instance or ID.
 * @param int   $booking_id Booking ID fallback.
 *
 * @return WC_Booking|false
 */
function adfoin_woocommercebookings_resolve_booking( $booking, $booking_id = 0 ) {
	if ( $booking instanceof WC_Booking ) {
		return $booking;
	}

	$booking_id = $booking ? $booking : $booking_id;

	if ( ! $booking_id ) {
		return false;
	}

	try {
		return get_wc_booking( $booking_id );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Convert timestamp/int to formatted datetime string.
 *
 * @param int|string $timestamp Timestamp or numeric string.
 *
 * @return string
 */
function adfoin_woocommercebookings_format_datetime( $timestamp ) {
	if ( empty( $timestamp ) ) {
		return '';
	}

	$timestamp = is_numeric( $timestamp ) ? (int) $timestamp : strtotime( $timestamp );

	if ( ! $timestamp ) {
		return '';
	}

	return gmdate( 'Y-m-d H:i:s', $timestamp );
}

/**
 * Format WC_DateTime safely.
 *
 * @param WC_DateTime|null $datetime Datetime object.
 *
 * @return string
 */
function adfoin_woocommercebookings_format_wc_datetime( $datetime ) {
	if ( empty( $datetime ) ) {
		return '';
	}

	return $datetime instanceof WC_DateTime ? $datetime->date( 'Y-m-d H:i:s' ) : '';
}

/**
 * Retrieve display label for a booking status.
 *
 * @param string $status Status key.
 *
 * @return string
 */
function adfoin_woocommercebookings_status_label( $status ) {
	$all_statuses = array_merge(
		get_wc_booking_statuses( null, true ),
		get_wc_booking_statuses( 'user', true )
	);

	return isset( $all_statuses[ $status ] ) ? $all_statuses[ $status ] : ucfirst( str_replace( '-', ' ', $status ) );
}

/**
 * Build person details for the payload.
 *
 * @param WC_Booking            $booking Booking instance.
 * @param WC_Product_Booking|WP_Post|false $product Product object.
 *
 * @return array<int,array<string,mixed>>
 */
function adfoin_woocommercebookings_person_details( WC_Booking $booking, $product ) {
	$counts  = $booking->get_person_counts();
	$details = array();

	if ( empty( $counts ) ) {
		return $details;
	}

	foreach ( $counts as $person_type_id => $count ) {
		$name = '';

		if ( $person_type_id && $product && is_callable( array( $product, 'get_person' ) ) ) {
			try {
				$person_type = $product->get_person( $person_type_id );
				if ( $person_type && method_exists( $person_type, 'get_name' ) ) {
					$name = $person_type->get_name();
				}
			} catch ( Exception $e ) {
				$name = '';
			}
		}

		$details[] = array(
			'id'    => $person_type_id,
			'name'  => $name,
			'count' => $count,
		);
	}

	return $details;
}

/**
 * Render person details as plain text.
 *
 * @param array<int,array<string,mixed>> $person_map Person detail map.
 *
 * @return string
 */
function adfoin_woocommercebookings_person_details_plain( $person_map ) {
	if ( empty( $person_map ) ) {
		return '';
	}

	$parts = array();

	foreach ( $person_map as $entry ) {
		$label   = isset( $entry['name'] ) && $entry['name'] ? $entry['name'] : sprintf( __( 'Person Type %d', 'advanced-form-integration' ), isset( $entry['id'] ) ? $entry['id'] : 0 );
		$parts[] = sprintf( '%s: %s', $label, isset( $entry['count'] ) ? $entry['count'] : 0 );
	}

	return implode( ' | ', $parts );
}

/**
 * Encode data into JSON or return empty string.
 *
 * @param mixed $data Arbitrary data.
 *
 * @return string
 */
function adfoin_woocommercebookings_encode_or_empty( $data ) {
	if ( empty( $data ) ) {
		return '';
	}

	return wp_json_encode( $data );
}

/**
 * Collect post meta in a serialisable format.
 *
 * @param int $post_id Post ID.
 *
 * @return array<string,mixed>
 */
function adfoin_woocommercebookings_collect_post_meta( $post_id ) {
	$meta = get_post_meta( $post_id );

	if ( empty( $meta ) ) {
		return array();
	}

	$formatted = array();

	foreach ( $meta as $key => $values ) {
		if ( is_array( $values ) ) {
			$formatted[ $key ] = array_map( 'maybe_unserialize', $values );
			if ( count( $formatted[ $key ] ) === 1 ) {
				$formatted[ $key ] = $formatted[ $key ][0];
			}
		} else {
			$formatted[ $key ] = maybe_unserialize( $values );
		}
	}

	return $formatted;
}

/**
 * Prepare order items payload.
 *
 * @param WC_Order $order Order instance.
 *
 * @return array<int,array<string,mixed>>
 */
function adfoin_woocommercebookings_order_items_payload( WC_Order $order ) {
	$items_data = array();

	foreach ( $order->get_items() as $item ) {
		$product      = $item->get_product();
		$meta_entries = array();

		foreach ( $item->get_meta_data() as $meta ) {
			$meta_entries[] = array(
				'key'   => $meta->key,
				'value' => $meta->value,
			);
		}

		$items_data[] = array(
			'id'           => $item->get_id(),
			'name'         => $item->get_name(),
			'product_id'   => $item->get_product_id(),
			'variation_id' => $item->get_variation_id(),
			'quantity'     => $item->get_quantity(),
			'subtotal'     => $item->get_subtotal(),
			'subtotal_tax' => $item->get_subtotal_tax(),
			'total'        => $item->get_total(),
			'total_tax'    => $item->get_total_tax(),
			'sku'          => $product ? $product->get_sku() : '',
			'meta_data'    => $meta_entries,
		);
	}

	return $items_data;
}

/**
 * Extract requested meta placeholders from saved records.
 *
 * @param array<int,array<string,mixed>> $saved_records Saved automation records.
 *
 * @return array<string,string[]>
 */
function adfoin_woocommercebookings_meta_placeholders( $saved_records ) {
	$placeholders = array(
		'booking'  => array(),
		'order'    => array(),
		'product'  => array(),
		'resource' => array(),
		'customer' => array(),
	);

	if ( empty( $saved_records ) ) {
		return $placeholders;
	}

	$prefixes = array(
		'booking'  => 'bookingmeta_',
		'order'    => 'ordermeta_',
		'product'  => 'productmeta_',
		'resource' => 'resourcemeta_',
		'customer' => 'customermeta_',
	);

	foreach ( $saved_records as $record ) {
		if ( empty( $record['data'] ) ) {
			continue;
		}

		$data = json_decode( $record['data'], true );

		if ( empty( $data['field_data'] ) || ! is_array( $data['field_data'] ) ) {
			continue;
		}

		foreach ( $data['field_data'] as $field_value ) {
			if ( ! is_string( $field_value ) ) {
				continue;
			}

			foreach ( $prefixes as $type => $prefix ) {
				if ( false === strpos( $field_value, $prefix ) ) {
					continue;
				}

				preg_match_all( '/' . preg_quote( $prefix, '/' ) . '.+?\\}\\}/', $field_value, $matches );

				if ( empty( $matches[0] ) ) {
					continue;
				}

				foreach ( $matches[0] as $tag ) {
					$tag = str_replace( '}}', '', $tag );

					if ( $tag ) {
						$placeholders[ $type ][] = $tag;
					}
				}
			}
		}
	}

	foreach ( $placeholders as $type => $tags ) {
		$placeholders[ $type ] = array_unique( $tags );
	}

	return $placeholders;
}

/**
 * Resolve placeholder meta values for the payload.
 *
 * @param WC_Booking          $booking       Booking instance.
 * @param WC_Order|false      $order         Order instance.
 * @param WC_Product|false    $product       Product instance.
 * @param WC_Product_Booking_Resource|false $resource Resource instance.
 * @param array<string,string[]>            $placeholders Placeholder map.
 *
 * @return array<string,mixed>
 */
function adfoin_woocommercebookings_meta_values( WC_Booking $booking, $order, $product, $resource, $placeholders ) {
	$values       = array();
	$customer_id  = $booking->get_customer_id();

	if ( ! $customer_id && $order ) {
		$customer_id = $order->get_customer_id();
	}

	foreach ( $placeholders['booking'] as $tag ) {
		$key             = str_replace( 'bookingmeta_', '', $tag );
		$values[ $tag ]  = get_post_meta( $booking->get_id(), $key, true );
	}

	if ( $order ) {
		foreach ( $placeholders['order'] as $tag ) {
			$key             = str_replace( 'ordermeta_', '', $tag );
			$values[ $tag ]  = get_post_meta( $order->get_id(), $key, true );
		}
	}

	if ( $product && $product->get_id() ) {
		foreach ( $placeholders['product'] as $tag ) {
			$key             = str_replace( 'productmeta_', '', $tag );
			$values[ $tag ]  = get_post_meta( $product->get_id(), $key, true );
		}
	}

	if ( $resource && $resource->get_id() ) {
		foreach ( $placeholders['resource'] as $tag ) {
			$key             = str_replace( 'resourcemeta_', '', $tag );
			$values[ $tag ]  = get_post_meta( $resource->get_id(), $key, true );
		}
	}

	if ( $customer_id ) {
		foreach ( $placeholders['customer'] as $tag ) {
			$key             = str_replace( 'customermeta_', '', $tag );
			$values[ $tag ]  = get_user_meta( (int) $customer_id, $key, true );
		}
	}

	return $values;
}
