<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter( 'adfoin_form_providers', 'adfoin_woocommercesubscriptions_filter_provider' );

/**
 * Conditionally expose the WooCommerce Subscriptions provider.
 *
 * @param array $providers Registered providers.
 *
 * @return array
 */
function adfoin_woocommercesubscriptions_filter_provider( $providers ) {
	if ( ! is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
		unset( $providers['woocommercesubscriptions'] );

		return $providers;
	}

	$providers['woocommercesubscriptions'] = __( 'WooCommerce Subscriptions', 'advanced-form-integration' );

	return $providers;
}

/**
 * Map WooCommerce Subscriptions triggers.
 *
 * @return array<string,string>
 */
function adfoin_woocommercesubscriptions_triggers() {
	return array(
		'subscriptionCreated'                => __( 'Subscription Created', 'advanced-form-integration' ),
		'subscriptionPaymentComplete'        => __( 'Subscription Payment Complete', 'advanced-form-integration' ),
		'subscriptionPaymentFailed'          => __( 'Subscription Payment Failed', 'advanced-form-integration' ),
		'subscriptionRenewalPaymentComplete' => __( 'Subscription Renewal Payment Complete', 'advanced-form-integration' ),
		'subscriptionRenewalPaymentFailed'   => __( 'Subscription Renewal Payment Failed', 'advanced-form-integration' ),
		'subscriptionTrialEnded'             => __( 'Subscription Trial Ended', 'advanced-form-integration' ),
		'subscriptionStatusUpdated'          => __( 'Subscription Status Changed', 'advanced-form-integration' ),
		'subscriptionStatusPending'          => __( 'Subscription Status Pending', 'advanced-form-integration' ),
		'subscriptionStatusActive'           => __( 'Subscription Status Active', 'advanced-form-integration' ),
		'subscriptionStatusOnHold'           => __( 'Subscription Status On Hold', 'advanced-form-integration' ),
		'subscriptionStatusPendingCancel'    => __( 'Subscription Status Pending Cancellation', 'advanced-form-integration' ),
		'subscriptionStatusCancelled'        => __( 'Subscription Status Cancelled', 'advanced-form-integration' ),
		'subscriptionStatusExpired'          => __( 'Subscription Status Expired', 'advanced-form-integration' ),
		'subscriptionStatusSwitched'         => __( 'Subscription Status Switched', 'advanced-form-integration' ),
		'subscriptionStatusFailed'           => __( 'Subscription Status Failed', 'advanced-form-integration' ),
	);
}

/**
 * Provide trigger list for the UI.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommercesubscriptions_get_forms( $form_provider ) {
	if ( 'woocommercesubscriptions' !== $form_provider ) {
		return;
	}

	return adfoin_woocommercesubscriptions_triggers();
}

/**
 * Provide field map for WooCommerce Subscriptions data.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommercesubscriptions_get_form_fields( $form_provider, $form_id ) {
	if ( 'woocommercesubscriptions' !== $form_provider ) {
		return;
	}

	$fields = adfoin_get_woocommerce_subscription_fields();

	$fields = array_merge(
		$fields,
		array(
			'subscription_form_id'                         => __( 'Trigger Key', 'advanced-form-integration' ),
			'subscription_trigger'                         => __( 'Trigger Name', 'advanced-form-integration' ),
			'subscription_triggered_at'                    => __( 'Trigger Time', 'advanced-form-integration' ),
			'subscription_status_from'                     => __( 'Previous Status', 'advanced-form-integration' ),
			'subscription_status_from_label'               => __( 'Previous Status Label', 'advanced-form-integration' ),
			'subscription_status_to'                       => __( 'Current Status', 'advanced-form-integration' ),
			'subscription_status_to_label'                 => __( 'Current Status Label', 'advanced-form-integration' ),
			'subscription_payment_failed_status'           => __( 'Failure Target Status', 'advanced-form-integration' ),
			'subscription_payment_failed_status_label'     => __( 'Failure Target Status Label', 'advanced-form-integration' ),
			'subscription_related_order_id'                => __( 'Related Order ID', 'advanced-form-integration' ),
			'subscription_related_order_number'            => __( 'Related Order Number', 'advanced-form-integration' ),
			'subscription_related_order_status'            => __( 'Related Order Status', 'advanced-form-integration' ),
			'subscription_related_order_total'             => __( 'Related Order Total', 'advanced-form-integration' ),
			'subscription_related_order_currency'          => __( 'Related Order Currency', 'advanced-form-integration' ),
			'subscription_related_order_payment_method'    => __( 'Related Order Payment Method', 'advanced-form-integration' ),
			'subscription_related_order_payment_method_title' => __( 'Related Order Payment Method Title', 'advanced-form-integration' ),
			'subscription_related_order_transaction_id'    => __( 'Related Order Transaction ID', 'advanced-form-integration' ),
			'subscription_related_order_created'           => __( 'Related Order Created', 'advanced-form-integration' ),
		)
	);

	return $fields;
}

/**
 * Resolve human readable trigger label.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return string|false
 */
function adfoin_woocommercesubscriptions_get_form_name( $form_provider, $form_id ) {
	if ( 'woocommercesubscriptions' !== $form_provider ) {
		return false;
	}

	$triggers = adfoin_woocommercesubscriptions_triggers();

	return isset( $triggers[ $form_id ] ) ? $triggers[ $form_id ] : false;
}

add_action( 'plugins_loaded', 'adfoin_woocommercesubscriptions_bootstrap', 20 );

/**
 * Register runtime watchers once WooCommerce Subscriptions is available.
 */
function adfoin_woocommercesubscriptions_bootstrap() {
	if ( ! function_exists( 'wcs_get_subscription' ) ) {
		return;
	}

	add_action( 'woocommerce_new_subscription', 'adfoin_woocommercesubscriptions_subscription_created', 10, 1 );
	add_action( 'woocommerce_subscription_payment_complete', 'adfoin_woocommercesubscriptions_payment_complete', 10, 1 );
	add_action( 'woocommerce_subscription_payment_failed', 'adfoin_woocommercesubscriptions_payment_failed', 10, 2 );
	add_action( 'woocommerce_subscription_renewal_payment_complete', 'adfoin_woocommercesubscriptions_renewal_payment_complete', 10, 2 );
	add_action( 'woocommerce_subscription_renewal_payment_failed', 'adfoin_woocommercesubscriptions_renewal_payment_failed', 10, 2 );
	add_action( 'woocommerce_subscription_trial_ended', 'adfoin_woocommercesubscriptions_trial_ended', 10, 1 );
	add_action( 'woocommerce_subscription_status_changed', 'adfoin_woocommercesubscriptions_status_changed', 10, 4 );

	adfoin_woocommercesubscriptions_register_status_hooks();
}

/**
 * Register dynamic status hooks.
 *
 * @return void
 */
function adfoin_woocommercesubscriptions_register_status_hooks() {
	foreach ( adfoin_woocommercesubscriptions_status_map() as $status => $trigger ) {
		add_action( "woocommerce_subscription_status_{$status}", 'adfoin_woocommercesubscriptions_handle_named_status', 10, 1 );
	}
}

/**
 * Handle specific status based on current filter name.
 *
 * @param mixed $subscription Subscription object or ID.
 */
function adfoin_woocommercesubscriptions_handle_named_status( $subscription ) {
	$hook   = current_filter();
	$status = str_replace( 'woocommerce_subscription_status_', '', $hook );
	$map    = adfoin_woocommercesubscriptions_status_map();

	if ( ! isset( $map[ $status ] ) ) {
		return;
	}

	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription );

	if ( ! $subscription ) {
		return;
	}

	$status_label = adfoin_woocommercesubscriptions_status_label( $status );

	$extra = array(
		'subscription_status_to'       => $status,
		'subscription_status_to_label' => $status_label,
	);

	adfoin_woocommercesubscriptions_dispatch( $map[ $status ], $subscription, $extra );
}

/**
 * Provide a reusable status to trigger map.
 *
 * @return array<string,string>
 */
function adfoin_woocommercesubscriptions_status_map() {
	return array(
		'pending'        => 'subscriptionStatusPending',
		'active'         => 'subscriptionStatusActive',
		'on-hold'        => 'subscriptionStatusOnHold',
		'pending-cancel' => 'subscriptionStatusPendingCancel',
		'cancelled'      => 'subscriptionStatusCancelled',
		'expired'        => 'subscriptionStatusExpired',
		'switched'       => 'subscriptionStatusSwitched',
		'failed'         => 'subscriptionStatusFailed',
	);
}

/**
 * Handle new subscription creation.
 *
 * @param int $subscription_id Subscription ID.
 */
function adfoin_woocommercesubscriptions_subscription_created( $subscription_id ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription_id );

	if ( ! $subscription ) {
		return;
	}

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionCreated', $subscription );
}

/**
 * Handle payment completion.
 *
 * @param WC_Subscription|int $subscription Subscription object.
 */
function adfoin_woocommercesubscriptions_payment_complete( $subscription ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription );

	if ( ! $subscription ) {
		return;
	}

	$order = $subscription->get_last_order( 'all', 'any' );
	$extra = adfoin_woocommercesubscriptions_order_context( $order );

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionPaymentComplete', $subscription, $extra );
}

/**
 * Handle payment failures.
 *
 * @param WC_Subscription|int $subscription Subscription object.
 * @param string              $new_status   Status the subscription moved to.
 */
function adfoin_woocommercesubscriptions_payment_failed( $subscription, $new_status ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription );

	if ( ! $subscription ) {
		return;
	}

	$order = $subscription->get_last_order( 'all', 'any' );

	$extra = array_merge(
		array(
			'subscription_payment_failed_status'       => $new_status,
			'subscription_payment_failed_status_label' => adfoin_woocommercesubscriptions_status_label( $new_status ),
		),
		adfoin_woocommercesubscriptions_order_context( $order )
	);

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionPaymentFailed', $subscription, $extra );
}

/**
 * Handle renewal payment completion.
 *
 * @param WC_Subscription|int $subscription Subscription object.
 * @param WC_Order|false      $order        Related renewal order.
 */
function adfoin_woocommercesubscriptions_renewal_payment_complete( $subscription, $order ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription );

	if ( ! $subscription ) {
		return;
	}

	$extra = adfoin_woocommercesubscriptions_order_context( $order );

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionRenewalPaymentComplete', $subscription, $extra );
}

/**
 * Handle renewal payment failure.
 *
 * @param WC_Subscription|int $subscription Subscription object.
 * @param WC_Order|false      $order        Related renewal order.
 */
function adfoin_woocommercesubscriptions_renewal_payment_failed( $subscription, $order ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription );

	if ( ! $subscription ) {
		return;
	}

	$extra = array_merge(
		array(
			'subscription_payment_failed_status'       => $subscription->get_status(),
			'subscription_payment_failed_status_label' => adfoin_woocommercesubscriptions_status_label( $subscription->get_status() ),
		),
		adfoin_woocommercesubscriptions_order_context( $order )
	);

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionRenewalPaymentFailed', $subscription, $extra );
}

/**
 * Handle trial ended event.
 *
 * @param int $subscription_id Subscription ID.
 */
function adfoin_woocommercesubscriptions_trial_ended( $subscription_id ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription_id );

	if ( ! $subscription ) {
		return;
	}

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionTrialEnded', $subscription );
}

/**
 * Handle generic status changes.
 *
 * @param int               $subscription_id Subscription ID.
 * @param string            $status_from     Previous status.
 * @param string            $status_to       New status.
 * @param WC_Subscription   $subscription    Subscription object.
 */
function adfoin_woocommercesubscriptions_status_changed( $subscription_id, $status_from, $status_to, $subscription ) {
	$subscription = adfoin_woocommercesubscriptions_resolve_subscription( $subscription, $subscription_id );

	if ( ! $subscription ) {
		return;
	}

	$extra = array(
		'subscription_status_from'       => $status_from,
		'subscription_status_from_label' => adfoin_woocommercesubscriptions_status_label( $status_from ),
		'subscription_status_to'         => $status_to,
		'subscription_status_to_label'   => adfoin_woocommercesubscriptions_status_label( $status_to ),
	);

	adfoin_woocommercesubscriptions_dispatch( 'subscriptionStatusUpdated', $subscription, $extra );
}

/**
 * Resolve subscription object from mixed input.
 *
 * @param mixed $subscription Subscription object or ID.
 * @param int   $fallback_id  Optional fallback ID.
 *
 * @return WC_Subscription|false
 */
function adfoin_woocommercesubscriptions_resolve_subscription( $subscription, $fallback_id = 0 ) {
	if ( $subscription instanceof WC_Subscription ) {
		return $subscription;
	}

	$subscription_id = 0;

	if ( is_numeric( $subscription ) ) {
		$subscription_id = (int) $subscription;
	} elseif ( $fallback_id ) {
		$subscription_id = (int) $fallback_id;
	}

	if ( ! $subscription_id ) {
		return false;
	}

	$subscription_object = wcs_get_subscription( $subscription_id );

	return $subscription_object instanceof WC_Subscription ? $subscription_object : false;
}

/**
 * Dispatch prepared payload to automation records.
 *
 * @param string            $form_id      Trigger identifier.
 * @param WC_Subscription   $subscription Subscription instance.
 * @param array<string,mixed> $extra      Additional payload data.
 */
function adfoin_woocommercesubscriptions_dispatch( $form_id, WC_Subscription $subscription, array $extra = array() ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'woocommercesubscriptions', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$defaults = array(
		'subscription_form_id'      => $form_id,
		'subscription_trigger'      => adfoin_woocommercesubscriptions_get_form_name( 'woocommercesubscriptions', $form_id ),
		'subscription_triggered_at' => current_time( 'mysql' ),
	);

	$extra_data = array_merge( $defaults, $extra );

	adfoin_woocommerce_send_subscription_data( $subscription, $saved_records, $extra_data );
}

/**
 * Build related order context.
 *
 * @param WC_Order|false $order Order object.
 *
 * @return array<string,string|float>
 */
function adfoin_woocommercesubscriptions_order_context( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return array();
	}

	return array(
		'subscription_related_order_id'                     => $order->get_id(),
		'subscription_related_order_number'                 => $order->get_order_number(),
		'subscription_related_order_status'                 => $order->get_status(),
		'subscription_related_order_total'                  => $order->get_total(),
		'subscription_related_order_currency'               => $order->get_currency(),
		'subscription_related_order_payment_method'         => $order->get_payment_method(),
		'subscription_related_order_payment_method_title'   => $order->get_payment_method_title(),
		'subscription_related_order_transaction_id'         => $order->get_transaction_id(),
		'subscription_related_order_created'                => adfoin_woocommercesubscriptions_format_wc_datetime( $order->get_date_created() ),
	);
}

/**
 * Format WooCommerce datetime values.
 *
 * @param WC_DateTime|null $datetime Date object.
 *
 * @return string
 */
function adfoin_woocommercesubscriptions_format_wc_datetime( $datetime ) {
	if ( $datetime instanceof WC_DateTime ) {
		return $datetime->date( 'Y-m-d H:i:s' );
	}

	return '';
}

/**
 * Retrieve a localized label for a subscription status.
 *
 * @param string $status Status key.
 *
 * @return string
 */
function adfoin_woocommercesubscriptions_status_label( $status ) {
	if ( empty( $status ) ) {
		return '';
	}

	if ( function_exists( 'wcs_get_subscription_status_name' ) ) {
		$status_key = strncmp( $status, 'wc-', 3 ) === 0 ? $status : 'wc-' . $status;
		$label      = wcs_get_subscription_status_name( $status_key );

		if ( ! is_wp_error( $label ) ) {
			return $label;
		}
	}

	return ucwords( str_replace( '-', ' ', $status ) );
}
