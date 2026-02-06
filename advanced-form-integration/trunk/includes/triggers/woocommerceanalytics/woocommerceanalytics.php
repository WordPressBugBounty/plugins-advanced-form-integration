<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter( 'adfoin_form_providers', 'adfoin_woocommerceanalytics_register_provider' );

/**
 * Register the WooCommerce Analytics trigger provider when the plugin is active.
 *
 * @param array $providers Registered providers.
 *
 * @return array
 */
function adfoin_woocommerceanalytics_register_provider( $providers ) {
	if ( ! adfoin_woocommerceanalytics_is_plugin_active() ) {
		unset( $providers['woocommerceanalytics'] );

		return $providers;
	}

	$providers['woocommerceanalytics'] = __( 'WooCommerce Analytics', 'advanced-form-integration' );

	return $providers;
}

/**
 * Determine whether WooCommerce Analytics is active.
 *
 * @return bool
 */
function adfoin_woocommerceanalytics_is_plugin_active() {
	return is_plugin_active( 'woocommerce-analytics/woocommerce-analytics.php' );
}

/**
 * Retrieve available triggers for WooCommerce Analytics.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_triggers() {
	return array(
		'orderSynced'            => __( 'Analytics Order Stats Synced', 'advanced-form-integration' ),
		'orderDeleted'           => __( 'Analytics Order Stats Deleted', 'advanced-form-integration' ),
		'missingOrdersDetected'  => __( 'Analytics Missing Orders Detected', 'advanced-form-integration' ),
	);
}

/**
 * Provide trigger list for the UI.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommerceanalytics_get_forms( $form_provider ) {
	if ( 'woocommerceanalytics' !== $form_provider ) {
		return;
	}

	return adfoin_woocommerceanalytics_triggers();
}

/**
 * Provide field map for the UI.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommerceanalytics_get_form_fields( $form_provider, $form_id ) {
	if ( 'woocommerceanalytics' !== $form_provider ) {
		return;
	}

	$fields = adfoin_woocommerceanalytics_common_fields();

	switch ( $form_id ) {
		case 'orderSynced':
			$fields = array_merge(
				$fields,
				adfoin_woocommerceanalytics_order_fields(),
				adfoin_woocommerceanalytics_attribution_fields()
			);
			break;

		case 'orderDeleted':
			$fields = array_merge(
				$fields,
				array(
					'order_id'           => __( 'Order ID', 'advanced-form-integration' ),
					'deletion_data_raw'  => __( 'Deletion Payload (JSON)', 'advanced-form-integration' ),
				)
			);
			break;

		case 'missingOrdersDetected':
			$fields = array_merge(
				$fields,
				array(
					'missing_order_ids'      => __( 'Missing Order IDs (JSON)', 'advanced-form-integration' ),
					'missing_order_ids_csv'  => __( 'Missing Order IDs (CSV)', 'advanced-form-integration' ),
				)
			);
			break;
	}

	return $fields;
}

/**
 * Resolve trigger labels.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return string|false
 */
function adfoin_woocommerceanalytics_get_form_name( $form_provider, $form_id ) {
	if ( 'woocommerceanalytics' !== $form_provider ) {
		return false;
	}

	$triggers = adfoin_woocommerceanalytics_triggers();

	return isset( $triggers[ $form_id ] ) ? $triggers[ $form_id ] : false;
}

/**
 * Common payload fields shared by all triggers.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_common_fields() {
	return array(
		'trigger_key'   => __( 'Trigger Key', 'advanced-form-integration' ),
		'trigger_name'  => __( 'Trigger Name', 'advanced-form-integration' ),
		'triggered_at'  => __( 'Trigger Time', 'advanced-form-integration' ),
	);
}

/**
 * Order stats fields.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_order_fields() {
	return array(
		'order_id'                  => __( 'Order ID', 'advanced-form-integration' ),
		'order_parent_id'           => __( 'Parent Order ID', 'advanced-form-integration' ),
		'order_status'              => __( 'Order Status', 'advanced-form-integration' ),
		'order_date_created'        => __( 'Order Date Created', 'advanced-form-integration' ),
		'order_date_paid'           => __( 'Order Date Paid', 'advanced-form-integration' ),
		'order_date_completed'      => __( 'Order Date Completed', 'advanced-form-integration' ),
		'order_num_items_sold'      => __( 'Items Sold', 'advanced-form-integration' ),
		'order_total_sales'         => __( 'Total Sales', 'advanced-form-integration' ),
		'order_tax_total'           => __( 'Tax Total', 'advanced-form-integration' ),
		'order_total_fees'          => __( 'Total Fees', 'advanced-form-integration' ),
		'order_total_fees_tax'      => __( 'Total Fees Tax', 'advanced-form-integration' ),
		'order_shipping_total'      => __( 'Shipping Total', 'advanced-form-integration' ),
		'order_shipping_tax'        => __( 'Shipping Tax', 'advanced-form-integration' ),
		'order_discount_total'      => __( 'Discount Total', 'advanced-form-integration' ),
		'order_discount_tax'        => __( 'Discount Tax', 'advanced-form-integration' ),
		'order_net_total'           => __( 'Net Total', 'advanced-form-integration' ),
		'order_returning_customer'  => __( 'Returning Customer', 'advanced-form-integration' ),
		'order_customer_id'         => __( 'Customer ID', 'advanced-form-integration' ),
		'order_stats_raw'           => __( 'Order Stats (JSON)', 'advanced-form-integration' ),
	);
}

/**
 * Attribution related fields.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_attribution_fields() {
	return array(
		'attr_utm_campaign'        => __( 'UTM Campaign', 'advanced-form-integration' ),
		'attr_utm_source'          => __( 'UTM Source', 'advanced-form-integration' ),
		'attr_utm_medium'          => __( 'UTM Medium', 'advanced-form-integration' ),
		'attr_utm_content'         => __( 'UTM Content', 'advanced-form-integration' ),
		'attr_utm_term'            => __( 'UTM Term', 'advanced-form-integration' ),
		'attr_utm_source_platform' => __( 'UTM Source Platform', 'advanced-form-integration' ),
		'attr_origin'              => __( 'Origin', 'advanced-form-integration' ),
		'attr_device_type'         => __( 'Device Type', 'advanced-form-integration' ),
		'attr_source_type'         => __( 'Source Type', 'advanced-form-integration' ),
		'attr_raw'                 => __( 'Attribution Data (JSON)', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_woocommerceanalytics_bootstrap', 20 );

/**
 * Register runtime listeners.
 *
 * @return void
 */
function adfoin_woocommerceanalytics_bootstrap() {
	if ( ! adfoin_woocommerceanalytics_is_plugin_active() ) {
		return;
	}

	add_action( 'woocommerce_analytics_sync_reports_data', 'adfoin_woocommerceanalytics_handle_order_synced', 20, 1 );
	add_action( 'woocommerce_analytics_delete_reports_data', 'adfoin_woocommerceanalytics_handle_order_deleted', 20, 1 );
	add_action( 'woocommerce_analytics_missing_orders_detected', 'adfoin_woocommerceanalytics_handle_missing_orders', 20, 1 );
}

/**
 * Handle analytics report sync events.
 *
 * @param array $data Sync payload.
 *
 * @return void
 */
function adfoin_woocommerceanalytics_handle_order_synced( $data ) {
	if ( empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	$payload = adfoin_woocommerceanalytics_prepare_synced_payload( $data );

	if ( empty( $payload ) ) {
		return;
	}

	adfoin_woocommerceanalytics_dispatch( 'orderSynced', $payload );
}

/**
 * Handle analytics deletion events.
 *
 * @param array $data Deletion payload.
 *
 * @return void
 */
function adfoin_woocommerceanalytics_handle_order_deleted( $data ) {
	if ( empty( $data ) || ! is_array( $data ) ) {
		return;
	}

	$payload = adfoin_woocommerceanalytics_prepare_deletion_payload( $data );

	if ( empty( $payload ) ) {
		return;
	}

	adfoin_woocommerceanalytics_dispatch( 'orderDeleted', $payload );
}

/**
 * Handle missing order detection events.
 *
 * @param array $missing_ids Missing order IDs.
 *
 * @return void
 */
function adfoin_woocommerceanalytics_handle_missing_orders( $missing_ids ) {
	if ( empty( $missing_ids ) || ! is_array( $missing_ids ) ) {
		return;
	}

	$payload = adfoin_woocommerceanalytics_prepare_missing_payload( $missing_ids );

	if ( empty( $payload ) ) {
		return;
	}

	adfoin_woocommerceanalytics_dispatch( 'missingOrdersDetected', $payload );
}

/**
 * Prepare payload for synced order stats.
 *
 * @param array $data Sync payload.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_prepare_synced_payload( array $data ) {
	$payload = adfoin_woocommerceanalytics_base_payload( 'orderSynced' );

	$stats = isset( $data['order_stats'] ) && is_array( $data['order_stats'] ) ? $data['order_stats'] : array();

	if ( ! empty( $stats ) ) {
		adfoin_woocommerceanalytics_set_field( $payload, 'order_id',                  isset( $stats['order_id'] ) ? $stats['order_id'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_parent_id',           isset( $stats['parent_id'] ) ? $stats['parent_id'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_status',              isset( $stats['status'] ) ? $stats['status'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_date_created',        isset( $stats['date_created'] ) ? adfoin_woocommerceanalytics_format_datetime( $stats['date_created'] ) : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_date_paid',           isset( $stats['date_paid'] ) ? adfoin_woocommerceanalytics_format_datetime( $stats['date_paid'] ) : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_date_completed',      isset( $stats['date_completed'] ) ? adfoin_woocommerceanalytics_format_datetime( $stats['date_completed'] ) : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_num_items_sold',      isset( $stats['num_items_sold'] ) ? $stats['num_items_sold'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_total_sales',         isset( $stats['total_sales'] ) ? $stats['total_sales'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_tax_total',           isset( $stats['tax_total'] ) ? $stats['tax_total'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_total_fees',          isset( $stats['total_fees'] ) ? $stats['total_fees'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_total_fees_tax',      isset( $stats['total_fees_tax'] ) ? $stats['total_fees_tax'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_shipping_total',      isset( $stats['shipping_total'] ) ? $stats['shipping_total'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_shipping_tax',        isset( $stats['shipping_tax'] ) ? $stats['shipping_tax'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_discount_total',      isset( $stats['discount_total'] ) ? $stats['discount_total'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_discount_tax',        isset( $stats['discount_tax'] ) ? $stats['discount_tax'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_net_total',           isset( $stats['net_total'] ) ? $stats['net_total'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_returning_customer',  isset( $stats['returning_customer'] ) ? $stats['returning_customer'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_customer_id',         isset( $stats['customer_id'] ) ? $stats['customer_id'] : '' );
		adfoin_woocommerceanalytics_set_field( $payload, 'order_stats_raw',           $stats );
	}

	$attribution = isset( $data['order_attribution_data'] ) && is_array( $data['order_attribution_data'] ) ? $data['order_attribution_data'] : array();

	if ( ! empty( $attribution ) ) {
		foreach ( $attribution as $key => $value ) {
			adfoin_woocommerceanalytics_set_field( $payload, 'attr_' . $key, $value );
		}

		adfoin_woocommerceanalytics_set_field( $payload, 'attr_raw', $attribution );
	}

	return $payload;
}

/**
 * Prepare payload for analytics deletions.
 *
 * @param array $data Deletion payload.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_prepare_deletion_payload( array $data ) {
	$payload = adfoin_woocommerceanalytics_base_payload( 'orderDeleted' );

	$order_id = '';

	if ( isset( $data['id'] ) ) {
		$order_id = $data['id'];
	} elseif ( isset( $data['order_id'] ) ) {
		$order_id = $data['order_id'];
	}

	if ( '' === $order_id ) {
		return array();
	}

	adfoin_woocommerceanalytics_set_field( $payload, 'order_id', $order_id );
	adfoin_woocommerceanalytics_set_field( $payload, 'deletion_data_raw', $data );

	return $payload;
}

/**
 * Prepare payload for missing order detection.
 *
 * @param array $missing_ids Missing order IDs.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_prepare_missing_payload( array $missing_ids ) {
	$filtered_ids = array_filter( array_map( 'intval', $missing_ids ) );

	if ( empty( $filtered_ids ) ) {
		return array();
	}

	$payload = adfoin_woocommerceanalytics_base_payload( 'missingOrdersDetected' );

	adfoin_woocommerceanalytics_set_field( $payload, 'missing_order_ids', $filtered_ids );
	adfoin_woocommerceanalytics_set_field( $payload, 'missing_order_ids_csv', implode( ',', $filtered_ids ) );

	return $payload;
}

/**
 * Provide base payload structure.
 *
 * @param string $form_id Trigger key.
 *
 * @return array<string,string>
 */
function adfoin_woocommerceanalytics_base_payload( $form_id ) {
	return array(
		'trigger_key'  => adfoin_woocommerceanalytics_normalize( $form_id ),
		'trigger_name' => adfoin_woocommerceanalytics_normalize( adfoin_woocommerceanalytics_get_form_name( 'woocommerceanalytics', $form_id ) ),
		'triggered_at' => adfoin_woocommerceanalytics_normalize( current_time( 'mysql' ) ),
	);
}

/**
 * Dispatch payload to saved integrations.
 *
 * @param string              $form_id Trigger key.
 * @param array<string,string> $payload Payload data.
 *
 * @return void
 */
function adfoin_woocommerceanalytics_dispatch( $form_id, array $payload ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'woocommerceanalytics', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	if ( '1' == get_option( 'adfoin_general_settings_utm' ) && function_exists( 'adfoin_capture_utm_and_url_values' ) ) {
		$payload = array_merge( $payload, adfoin_capture_utm_and_url_values() );
	}

	foreach ( $saved_records as $record ) {
		$action_provider = $record['action_provider'];

		if ( function_exists( "adfoin_{$action_provider}_send_data" ) ) {
			call_user_func( "adfoin_{$action_provider}_send_data", $record, $payload );
		}
	}
}

/**
 * Normalize payload values.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function adfoin_woocommerceanalytics_normalize( $value ) {
	if ( $value instanceof DateTimeInterface ) {
		return $value->format( DATE_ATOM );
	}

	if ( is_bool( $value ) ) {
		return $value ? 'true' : 'false';
	}

	if ( is_null( $value ) || '' === $value ) {
		return '';
	}

	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	if ( is_object( $value ) && isset( $value->date ) ) {
		return (string) $value->date;
	}

	$encoded = wp_json_encode( $value );

	return is_string( $encoded ) ? $encoded : '';
}

/**
 * Format WooCommerce Analytics datetime objects.
 *
 * @param mixed $value Raw datetime value.
 *
 * @return string
 */
function adfoin_woocommerceanalytics_format_datetime( $value ) {
	if ( $value instanceof DateTimeInterface ) {
		return $value->format( DATE_ATOM );
	}

	if ( is_object( $value ) && isset( $value->date ) ) {
		return (string) $value->date;
	}

	if ( is_array( $value ) && isset( $value['date'] ) ) {
		return (string) $value['date'];
	}

	return adfoin_woocommerceanalytics_normalize( $value );
}

/**
 * Assign a value into the payload after normalization.
 *
 * @param array<string,string> $payload Payload reference.
 * @param string               $field   Field key.
 * @param mixed                $value   Value to assign.
 *
 * @return void
 */
function adfoin_woocommerceanalytics_set_field( array &$payload, $field, $value ) {
	$payload[ $field ] = adfoin_woocommerceanalytics_normalize( $value );
}
