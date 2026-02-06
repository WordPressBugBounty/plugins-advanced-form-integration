<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter( 'adfoin_form_providers', 'adfoin_fundraising_register_provider' );

/**
 * Conditionally register the Fundraising for WooCommerce trigger provider.
 *
 * @param array $providers Registered providers.
 *
 * @return array
 */
function adfoin_fundraising_register_provider( $providers ) {
	if ( ! adfoin_fundraising_is_plugin_active() ) {
		unset( $providers['fundraisingforwoocommerce'] );

		return $providers;
	}

	$providers['fundraisingforwoocommerce'] = __( 'Fundraising for WooCommerce', 'advanced-form-integration' );

	return $providers;
}

/**
 * Determine whether Giveaway/Fundraising plugin is active.
 *
 * @return bool
 */
function adfoin_fundraising_is_plugin_active() {
	return is_plugin_active( 'lottery-for-woocommerce/lottery-for-woocommerce.php' ) || is_plugin_active( 'fundraising-for-woocommerce/fundraising-for-woocommerce.php' );
}

/**
 * Trigger map.
 *
 * @return array<string,string>
 */
function adfoin_fundraising_triggers() {
	return array(
		'lotteryStarted'        => __( 'Fundraising Lottery Started', 'advanced-form-integration' ),
		'lotteryEnded'          => __( 'Fundraising Lottery Ended', 'advanced-form-integration' ),
		'lotteryFinished'       => __( 'Fundraising Lottery Winners Declared', 'advanced-form-integration' ),
		'lotteryFailed'         => __( 'Fundraising Lottery Failed', 'advanced-form-integration' ),
		'lotteryRelisted'       => __( 'Fundraising Lottery Relisted', 'advanced-form-integration' ),
		'ticketCreated'         => __( 'Fundraising Ticket Created', 'advanced-form-integration' ),
		'ticketConfirmed'       => __( 'Fundraising Ticket Confirmed', 'advanced-form-integration' ),
	);
}

/**
 * Return trigger list for UI.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_fundraising_get_forms( $form_provider ) {
	if ( 'fundraisingforwoocommerce' !== $form_provider ) {
		return;
	}

	return adfoin_fundraising_triggers();
}

/**
 * Provide fields for the provider.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return array<string,string>|void
 */
function adfoin_fundraising_get_form_fields( $form_provider, $form_id ) {
	if ( 'fundraisingforwoocommerce' !== $form_provider ) {
		return;
	}

	return adfoin_get_fundraising_fields();
}

/**
 * Resolve trigger label.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return string|false
 */
function adfoin_fundraising_get_form_name( $form_provider, $form_id ) {
	if ( 'fundraisingforwoocommerce' !== $form_provider ) {
		return false;
	}

	$triggers = adfoin_fundraising_triggers();

	return isset( $triggers[ $form_id ] ) ? $triggers[ $form_id ] : false;
}

/**
 * Field definitions.
 *
 * @return array<string,string>
 */
function adfoin_get_fundraising_fields() {
	return array(
		'fundraising_trigger'                       => __( 'Trigger Name', 'advanced-form-integration' ),
		'fundraising_form_id'                       => __( 'Trigger Key', 'advanced-form-integration' ),
		'fundraising_triggered_at'                  => __( 'Trigger Time', 'advanced-form-integration' ),
		'fundraising_event_result'                  => __( 'Event Result', 'advanced-form-integration' ),
		'fundraising_event_result_label'            => __( 'Event Result Label', 'advanced-form-integration' ),
		'fundraising_event_iteration'               => __( 'Relist Iteration', 'advanced-form-integration' ),
		'fundraising_product_id'                    => __( 'Product ID', 'advanced-form-integration' ),
		'fundraising_product_name'                  => __( 'Product Name', 'advanced-form-integration' ),
        'fundraising_product_sku'                   => __( 'Product SKU', 'advanced-form-integration' ),
        'fundraising_product_url'                   => __( 'Product URL', 'advanced-form-integration' ),
        'fundraising_product_status'                => __( 'Product Status', 'advanced-form-integration' ),
        'fundraising_product_type'                  => __( 'Product Type', 'advanced-form-integration' ),
        'fundraising_lottery_status'                => __( 'Lottery Status', 'advanced-form-integration' ),
        'fundraising_lottery_status_prefixed'       => __( 'Lottery Status (Prefixed)', 'advanced-form-integration' ),
        'fundraising_lottery_status_label'          => __( 'Lottery Status Label', 'advanced-form-integration' ),
        'fundraising_lottery_schedule_type'         => __( 'Schedule Type', 'advanced-form-integration' ),
        'fundraising_start_date'                    => __( 'Start Date', 'advanced-form-integration' ),
        'fundraising_start_date_gmt'                => __( 'Start Date (GMT)', 'advanced-form-integration' ),
        'fundraising_end_date'                      => __( 'End Date', 'advanced-form-integration' ),
        'fundraising_end_date_gmt'                  => __( 'End Date (GMT)', 'advanced-form-integration' ),
        'fundraising_minimum_tickets'               => __( 'Minimum Tickets', 'advanced-form-integration' ),
        'fundraising_maximum_tickets'               => __( 'Maximum Tickets', 'advanced-form-integration' ),
        'fundraising_user_minimum_tickets'          => __( 'User Minimum Tickets', 'advanced-form-integration' ),
        'fundraising_user_maximum_tickets'          => __( 'User Maximum Tickets', 'advanced-form-integration' ),
        'fundraising_order_maximum_tickets'         => __( 'Order Maximum Tickets', 'advanced-form-integration' ),
        'fundraising_ticket_range_type'             => __( 'Ticket Range Type', 'advanced-form-integration' ),
        'fundraising_ticket_price_type'             => __( 'Ticket Price Type', 'advanced-form-integration' ),
        'fundraising_regular_price'                 => __( 'Regular Price', 'advanced-form-integration' ),
        'fundraising_sale_price'                    => __( 'Sale Price', 'advanced-form-integration' ),
        'fundraising_ticket_generation_type'        => __( 'Ticket Generation Type', 'advanced-form-integration' ),
        'fundraising_ticket_number_type'            => __( 'Ticket Number Type', 'advanced-form-integration' ),
        'fundraising_ticket_prefix'                 => __( 'Ticket Prefix', 'advanced-form-integration' ),
        'fundraising_ticket_suffix'                 => __( 'Ticket Suffix', 'advanced-form-integration' ),
        'fundraising_ticket_count'                  => __( 'Ticket Count', 'advanced-form-integration' ),
        'fundraising_unique_winners'                => __( 'Unique Winners', 'advanced-form-integration' ),
        'fundraising_winners_count'                 => __( 'Winners Count', 'advanced-form-integration' ),
        'fundraising_winner_selection_method'       => __( 'Winner Selection Method', 'advanced-form-integration' ),
        'fundraising_selected_gift_product_ids'     => __( 'Selected Gift Product IDs', 'advanced-form-integration' ),
        'fundraising_selected_gift_product_names'   => __( 'Selected Gift Product Names', 'advanced-form-integration' ),
        'fundraising_winner_user_ids'               => __( 'Winner User IDs', 'advanced-form-integration' ),
        'fundraising_winner_user_emails'            => __( 'Winner User Emails', 'advanced-form-integration' ),
        'fundraising_closed_flag'                   => __( 'Closed Flag', 'advanced-form-integration' ),
        'fundraising_closed_date'                   => __( 'Closed Date', 'advanced-form-integration' ),
        'fundraising_closed_date_gmt'               => __( 'Closed Date (GMT)', 'advanced-form-integration' ),
        'fundraising_failed_reason'                 => __( 'Failed Reason', 'advanced-form-integration' ),
        'fundraising_failed_date'                   => __( 'Failed Date', 'advanced-form-integration' ),
        'fundraising_failed_date_gmt'               => __( 'Failed Date (GMT)', 'advanced-form-integration' ),
        'fundraising_relisted_flag'                 => __( 'Relisted Flag', 'advanced-form-integration' ),
        'fundraising_relisted_date'                 => __( 'Relisted Date', 'advanced-form-integration' ),
        'fundraising_relisted_date_gmt'             => __( 'Relisted Date (GMT)', 'advanced-form-integration' ),
        'fundraising_relist_history'                => __( 'Relist History', 'advanced-form-integration' ),
        'fundraising_list_count'                    => __( 'Relist Count', 'advanced-form-integration' ),
        'fundraising_finished_date'                 => __( 'Finished Date', 'advanced-form-integration' ),
        'fundraising_finished_date_gmt'             => __( 'Finished Date (GMT)', 'advanced-form-integration' ),
        'fundraising_question_required'             => __( 'Question Required', 'advanced-form-integration' ),
        'fundraising_force_answer'                  => __( 'Force Answer', 'advanced-form-integration' ),
        'fundraising_validate_correct_answer'       => __( 'Validate Correct Answer', 'advanced-form-integration' ),
        'fundraising_ticket_id'                     => __( 'Ticket ID', 'advanced-form-integration' ),
        'fundraising_ticket_status'                 => __( 'Ticket Status', 'advanced-form-integration' ),
        'fundraising_ticket_status_label'           => __( 'Ticket Status Label', 'advanced-form-integration' ),
        'fundraising_ticket_number'                 => __( 'Ticket Number', 'advanced-form-integration' ),
        'fundraising_ticket_amount'                 => __( 'Ticket Amount', 'advanced-form-integration' ),
        'fundraising_ticket_currency'               => __( 'Ticket Currency', 'advanced-form-integration' ),
        'fundraising_ticket_user_id'                => __( 'Ticket User ID', 'advanced-form-integration' ),
        'fundraising_ticket_user_name'              => __( 'Ticket User Name', 'advanced-form-integration' ),
        'fundraising_ticket_user_email'             => __( 'Ticket User Email', 'advanced-form-integration' ),
        'fundraising_ticket_first_name'             => __( 'Ticket First Name', 'advanced-form-integration' ),
        'fundraising_ticket_last_name'              => __( 'Ticket Last Name', 'advanced-form-integration' ),
        'fundraising_ticket_ip_address'             => __( 'Ticket IP Address', 'advanced-form-integration' ),
        'fundraising_ticket_answer'                 => __( 'Ticket Answer', 'advanced-form-integration' ),
        'fundraising_ticket_answers'                => __( 'Ticket Answers', 'advanced-form-integration' ),
        'fundraising_ticket_is_valid_answer'        => __( 'Ticket Valid Answer', 'advanced-form-integration' ),
        'fundraising_ticket_list_count'             => __( 'Ticket List Count', 'advanced-form-integration' ),
        'fundraising_ticket_created'                => __( 'Ticket Created Date', 'advanced-form-integration' ),
        'fundraising_ticket_created_gmt'            => __( 'Ticket Created Date (GMT)', 'advanced-form-integration' ),
        'fundraising_ticket_view_order_url'         => __( 'Ticket View Order URL', 'advanced-form-integration' ),
        'fundraising_instant_winner_ticket'         => __( 'Instant Winner Ticket', 'advanced-form-integration' ),
        'fundraising_instant_winner_ticket_ids'     => __( 'Instant Winner Ticket IDs', 'advanced-form-integration' ),
        'fundraising_order_id'                      => __( 'Order ID', 'advanced-form-integration' ),
        'fundraising_order_number'                  => __( 'Order Number', 'advanced-form-integration' ),
        'fundraising_order_status'                  => __( 'Order Status', 'advanced-form-integration' ),
        'fundraising_order_status_label'            => __( 'Order Status Label', 'advanced-form-integration' ),
        'fundraising_order_total'                   => __( 'Order Total', 'advanced-form-integration' ),
        'fundraising_order_currency'                => __( 'Order Currency', 'advanced-form-integration' ),
        'fundraising_order_payment_method'          => __( 'Order Payment Method', 'advanced-form-integration' ),
        'fundraising_order_payment_method_title'    => __( 'Order Payment Method Title', 'advanced-form-integration' ),
        'fundraising_order_transaction_id'          => __( 'Order Transaction ID', 'advanced-form-integration' ),
        'fundraising_order_customer_id'             => __( 'Customer ID', 'advanced-form-integration' ),
        'fundraising_order_billing_email'           => __( 'Billing Email', 'advanced-form-integration' ),
        'fundraising_order_created'                 => __( 'Order Created', 'advanced-form-integration' ),
        'fundraising_order_completed'               => __( 'Order Completed', 'advanced-form-integration' ),
        'fundraising_order_paid_date'               => __( 'Order Paid Date', 'advanced-form-integration' ),
        'fundraising_order_url'                     => __( 'Order Admin URL', 'advanced-form-integration' ),
        'fundraising_customer_ip_address'           => __( 'Customer IP Address', 'advanced-form-integration' ),
        'fundraising_customer_user_agent'           => __( 'Customer User Agent', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_fundraising_bootstrap', 20 );

/**
 * Attach runtime listeners.
 */
function adfoin_fundraising_bootstrap() {
	if ( ! adfoin_fundraising_is_plugin_active() ) {
		return;
	}

	if ( ! function_exists( 'lty_is_lottery_product' ) || ! function_exists( 'lty_get_lottery_statuses' ) || ! class_exists( 'WC_Product_Lottery' ) ) {
		return;
	}

	add_action( 'lty_lottery_after_started', 'adfoin_fundraising_handle_lottery_started', 10, 1 );
	add_action( 'lty_lottery_after_ended', 'adfoin_fundraising_handle_lottery_ended', 10, 1 );
	add_action( 'lty_lottery_product_after_finished', 'adfoin_fundraising_handle_lottery_finished', 10, 1 );
	add_action( 'lty_lottery_after_relisted', 'adfoin_fundraising_handle_lottery_relisted', 10, 2 );
	add_action( 'lty_lottery_ticket_after_created', 'adfoin_fundraising_handle_ticket_created', 10, 2 );
	add_action( 'lty_lottery_ticket_confirmed', 'adfoin_fundraising_handle_ticket_confirmed', 10, 4 );
}

/**
 * Handle lottery started events.
 *
 * @param int $product_id Product ID.
 */
function adfoin_fundraising_handle_lottery_started( $product_id ) {
	$product = adfoin_fundraising_get_lottery_product( $product_id );

	if ( ! $product ) {
		return;
	}

	$payload = adfoin_fundraising_build_product_payload( $product );

	adfoin_fundraising_dispatch(
		'lotteryStarted',
		$payload,
		array(
			'product' => $product,
		)
	);
}

/**
 * Handle lottery ended events.
 *
 * @param int $product_id Product ID.
 */
function adfoin_fundraising_handle_lottery_ended( $product_id ) {
	$product = adfoin_fundraising_get_lottery_product( $product_id );

	if ( ! $product ) {
		return;
	}

	$status      = adfoin_fundraising_trim_status( $product->get_lty_lottery_status() );
	$status_label = adfoin_fundraising_status_label( $status );

	$payload = array_merge(
		adfoin_fundraising_build_product_payload( $product ),
		array(
			'fundraising_event_result'       => $status,
			'fundraising_event_result_label' => $status_label,
		)
	);

	adfoin_fundraising_dispatch(
		'lotteryEnded',
		$payload,
		array(
			'product' => $product,
		)
	);

	if ( 'lottery_failed' === $status ) {
		adfoin_fundraising_dispatch(
			'lotteryFailed',
			$payload,
			array(
				'product' => $product,
			)
		);
	}
}

/**
 * Handle lottery winners declared.
 *
 * @param int $product_id Product ID.
 */
function adfoin_fundraising_handle_lottery_finished( $product_id ) {
	$product = adfoin_fundraising_get_lottery_product( $product_id );

	if ( ! $product ) {
		return;
	}

	$payload = adfoin_fundraising_build_product_payload( $product );

	adfoin_fundraising_dispatch(
		'lotteryFinished',
		$payload,
		array(
			'product' => $product,
		)
	);
}

/**
 * Handle lottery relisted events.
 *
 * @param int   $product_id  Product ID.
 * @param array $relist_data Relist history.
 */
function adfoin_fundraising_handle_lottery_relisted( $product_id, $relist_data ) {
	$product = adfoin_fundraising_get_lottery_product( $product_id );

	if ( ! $product ) {
		return;
	}

	$payload = array_merge(
		adfoin_fundraising_build_product_payload( $product ),
		array(
			'fundraising_event_result'     => 'relisted',
			'fundraising_event_result_label' => __( 'Relisted', 'advanced-form-integration' ),
			'fundraising_event_iteration'  => count( (array) $relist_data ),
			'fundraising_relist_history'   => adfoin_fundraising_encode( $relist_data ),
		)
	);

	adfoin_fundraising_dispatch(
		'lotteryRelisted',
		$payload,
		array(
			'product' => $product,
		)
	);
}

/**
 * Handle ticket creation.
 *
 * @param array $ticket_ids Ticket IDs.
 * @param int   $order_id   Order ID.
 */
function adfoin_fundraising_handle_ticket_created( $ticket_ids, $order_id ) {
	if ( ! is_array( $ticket_ids ) ) {
		return;
	}

	foreach ( $ticket_ids as $ticket_id ) {
		$ticket = lty_get_lottery_ticket( $ticket_id );

		if ( ! $ticket instanceof LTY_Lottery_Ticket || ! $ticket->exists() ) {
			continue;
		}

		adfoin_fundraising_send_ticket_event( 'ticketCreated', $ticket );
	}
}

/**
 * Handle ticket confirmation.
 *
 * @param int   $primary_ticket_id Primary ticket ID.
 * @param array $ticket_map        Ticket data map.
 * @param int   $order_id          Order ID.
 * @param array $instant_ids       Instant winner ticket IDs.
 */
function adfoin_fundraising_handle_ticket_confirmed( $primary_ticket_id, $ticket_map, $order_id, $instant_ids ) {
	$ticket_ids = array();

	if ( is_array( $ticket_map ) ) {
		foreach ( $ticket_map as $tickets ) {
			if ( is_array( $tickets ) ) {
				$ticket_ids = array_merge( $ticket_ids, array_map( 'absint', array_keys( $tickets ) ) );
			}
		}
	}

	if ( $primary_ticket_id ) {
		$ticket_ids[] = absint( $primary_ticket_id );
	}

	$ticket_ids   = array_unique( array_filter( $ticket_ids ) );
	$instant_list = array_unique( array_map( 'absint', (array) $instant_ids ) );

	foreach ( $ticket_ids as $ticket_id ) {
		$ticket = lty_get_lottery_ticket( $ticket_id );

		if ( ! $ticket instanceof LTY_Lottery_Ticket || ! $ticket->exists() ) {
			continue;
		}

		$extra = array(
			'fundraising_instant_winner_ticket'     => adfoin_fundraising_bool_to_string( in_array( $ticket_id, $instant_list, true ) ),
			'fundraising_instant_winner_ticket_ids' => ! empty( $instant_list ) ? wp_json_encode( $instant_list ) : '',
		);

		adfoin_fundraising_send_ticket_event( 'ticketConfirmed', $ticket, $extra );
	}
}

/**
 * Send a ticket driven event.
 *
 * @param string              $form_id Trigger key.
 * @param LTY_Lottery_Ticket  $ticket  Ticket object.
 * @param array               $extra   Additional payload data.
 */
function adfoin_fundraising_send_ticket_event( $form_id, LTY_Lottery_Ticket $ticket, array $extra = array() ) {
	$product = adfoin_fundraising_get_lottery_product( $ticket->get_product_id() );
	$order   = $ticket->get_order();
	$user    = $ticket->get_user();

	$payload = array_merge(
		adfoin_fundraising_build_ticket_payload( $ticket ),
		$extra
	);

	adfoin_fundraising_dispatch(
		$form_id,
		$payload,
		array(
			'product' => $product,
			'ticket'  => $ticket,
			'order'   => $order,
			'user'    => $user instanceof WP_User ? $user : null,
		)
	);
}

/**
 * Dispatch data to saved integrations.
 *
 * @param string               $form_id Trigger key.
 * @param array<string,mixed>  $payload Prepared payload.
 * @param array<string,mixed>  $context Context objects.
 */
function adfoin_fundraising_dispatch( $form_id, array $payload, array $context = array() ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'fundraisingforwoocommerce', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$data = array_merge(
		array(
			'fundraising_form_id'      => $form_id,
			'fundraising_trigger'      => adfoin_fundraising_get_form_name( 'fundraisingforwoocommerce', $form_id ),
			'fundraising_triggered_at' => current_time( 'mysql' ),
		),
		$payload
	);

	if ( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
		$data = array_merge( $data, adfoin_capture_utm_and_url_values() );
	}

	$placeholders = adfoin_fundraising_meta_placeholders( $saved_records );

	if ( ! empty( $placeholders ) ) {
		$product = isset( $context['product'] ) ? $context['product'] : null;
		$ticket  = isset( $context['ticket'] ) ? $context['ticket'] : null;
		$order   = isset( $context['order'] ) ? $context['order'] : null;
		$user    = isset( $context['user'] ) ? $context['user'] : null;

		$meta_values = adfoin_fundraising_meta_values( $placeholders, $product, $ticket, $order, $user );

		if ( ! empty( $meta_values ) ) {
			$data = array_merge( $data, $meta_values );
		}
	}

	foreach ( $saved_records as $record ) {
		$action_provider = $record['action_provider'];

		if ( function_exists( "adfoin_{$action_provider}_send_data" ) ) {
			call_user_func( "adfoin_{$action_provider}_send_data", $record, $data );
		}
	}
}

/**
 * Build a product payload.
 *
 * @param WC_Product $product Lottery product.
 *
 * @return array<string,mixed>
 */
function adfoin_fundraising_build_product_payload( $product ) {
	$lottery = adfoin_fundraising_get_lottery_product( $product );

	if ( ! $lottery ) {
		return array();
	}

	$status_prefixed = $lottery->get_lty_lottery_status();
	$status          = adfoin_fundraising_trim_status( $status_prefixed );

	$selected_gifts = array_filter( (array) $lottery->get_selected_gift_products() );
	$gift_names     = array();

	foreach ( $selected_gifts as $gift_id ) {
		$gift_product = wc_get_product( $gift_id );
		if ( $gift_product instanceof WC_Product ) {
			$gift_names[] = $gift_product->get_name();
		}
	}

	$winner_user_ids    = array();
	$winner_user_emails = array();

	if ( function_exists( 'lty_get_current_winner_user_ids' ) ) {
		$winner_user_ids = lty_get_current_winner_user_ids( $lottery->get_id() );
	}

	if ( function_exists( 'lty_get_current_winner_user_emails' ) ) {
		$winner_user_emails = lty_get_current_winner_user_emails( $lottery->get_id() );
	}

	return array(
		'fundraising_product_id'                  => $lottery->get_id(),
		'fundraising_product_name'                => $lottery->get_name(),
		'fundraising_product_sku'                 => $lottery->get_sku(),
		'fundraising_product_url'                 => get_permalink( $lottery->get_id() ),
		'fundraising_product_status'              => get_post_status( $lottery->get_id() ),
		'fundraising_product_type'                => $lottery->get_type(),
		'fundraising_lottery_status'              => $status,
		'fundraising_lottery_status_prefixed'     => $status_prefixed,
		'fundraising_lottery_status_label'        => adfoin_fundraising_status_label( $status ),
		'fundraising_lottery_schedule_type'       => $lottery->get_lty_lottery_schedule_type(),
		'fundraising_start_date'                  => $lottery->get_lty_start_date(),
		'fundraising_start_date_gmt'              => $lottery->get_lty_start_date_gmt(),
		'fundraising_end_date'                    => $lottery->get_lty_end_date(),
		'fundraising_end_date_gmt'                => $lottery->get_lty_end_date_gmt(),
		'fundraising_minimum_tickets'             => $lottery->get_lty_minimum_tickets(),
		'fundraising_maximum_tickets'             => $lottery->get_lty_maximum_tickets(),
		'fundraising_user_minimum_tickets'        => $lottery->get_lty_user_minimum_tickets(),
		'fundraising_user_maximum_tickets'        => $lottery->get_lty_user_maximum_tickets(),
		'fundraising_order_maximum_tickets'       => $lottery->get_lty_order_maximum_tickets(),
		'fundraising_ticket_range_type'           => $lottery->get_lty_ticket_range_slider_type(),
		'fundraising_ticket_price_type'           => $lottery->get_lty_ticket_price_type(),
		'fundraising_regular_price'               => $lottery->get_lty_regular_price(),
		'fundraising_sale_price'                  => $lottery->get_lty_sale_price(),
		'fundraising_ticket_generation_type'      => $lottery->get_lty_ticket_generation_type(),
		'fundraising_ticket_number_type'          => $lottery->get_lty_ticket_number_type(),
		'fundraising_ticket_prefix'               => $lottery->get_lty_ticket_prefix(),
		'fundraising_ticket_suffix'               => $lottery->get_lty_ticket_suffix(),
		'fundraising_ticket_count'                => $lottery->get_lty_ticket_count(),
		'fundraising_unique_winners'              => adfoin_fundraising_bool_to_string( 'yes' === $lottery->get_lty_lottery_unique_winners() ),
		'fundraising_winners_count'               => $lottery->get_lty_winners_count(),
		'fundraising_winner_selection_method'     => $lottery->get_lty_winner_selection_method(),
		'fundraising_selected_gift_product_ids'   => adfoin_fundraising_encode( $selected_gifts ),
		'fundraising_selected_gift_product_names' => ! empty( $gift_names ) ? implode( ', ', $gift_names ) : '',
		'fundraising_winner_user_ids'             => adfoin_fundraising_encode( $winner_user_ids ),
		'fundraising_winner_user_emails'          => adfoin_fundraising_encode( $winner_user_emails ),
		'fundraising_closed_flag'                 => $lottery->get_lty_closed(),
		'fundraising_closed_date'                 => $lottery->get_lty_closed_date(),
		'fundraising_closed_date_gmt'             => $lottery->get_lty_closed_date_gmt(),
		'fundraising_failed_reason'               => $lottery->get_lty_failed_reason(),
		'fundraising_failed_date'                 => $lottery->get_lty_failed_date(),
		'fundraising_failed_date_gmt'             => $lottery->get_lty_failed_date_gmt(),
		'fundraising_relisted_flag'               => $lottery->get_lty_relisted(),
		'fundraising_relisted_date'               => $lottery->get_lty_relisted_date(),
		'fundraising_relisted_date_gmt'           => $lottery->get_lty_relisted_date_gmt(),
		'fundraising_relist_history'              => adfoin_fundraising_encode( $lottery->get_lty_relists() ),
		'fundraising_list_count'                  => $lottery->get_lty_list_count(),
		'fundraising_finished_date'               => $lottery->get_lty_finished_date(),
		'fundraising_finished_date_gmt'           => $lottery->get_lty_finished_date_gmt(),
		'fundraising_question_required'           => adfoin_fundraising_bool_to_string( 'yes' === $lottery->get_lty_manage_question() ),
		'fundraising_force_answer'                => adfoin_fundraising_bool_to_string( 'yes' === $lottery->get_lty_force_answer() ),
		'fundraising_validate_correct_answer'     => adfoin_fundraising_bool_to_string( 'yes' === $lottery->get_lty_validate_correct_answer() ),
	);
}

/**
 * Build ticket payload.
 *
 * @param LTY_Lottery_Ticket $ticket Ticket object.
 *
 * @return array<string,mixed>
 */
function adfoin_fundraising_build_ticket_payload( LTY_Lottery_Ticket $ticket ) {
	$product_payload = array();
	$product         = adfoin_fundraising_get_lottery_product( $ticket->get_product_id() );

	if ( $product ) {
		$product_payload = adfoin_fundraising_build_product_payload( $product );
	}

	$order = $ticket->get_order();

	$order_data = array(
		'fundraising_order_id'                   => '',
		'fundraising_order_number'               => '',
		'fundraising_order_status'               => '',
		'fundraising_order_status_label'         => '',
		'fundraising_order_total'                => '',
		'fundraising_order_currency'             => '',
		'fundraising_order_payment_method'       => '',
		'fundraising_order_payment_method_title' => '',
		'fundraising_order_transaction_id'       => '',
		'fundraising_order_customer_id'          => '',
		'fundraising_order_billing_email'        => '',
		'fundraising_order_created'              => '',
		'fundraising_order_completed'            => '',
		'fundraising_order_paid_date'            => '',
		'fundraising_order_url'                  => '',
		'fundraising_customer_ip_address'        => '',
		'fundraising_customer_user_agent'        => '',
	);

	if ( $order instanceof WC_Order ) {
		$order_data = array(
			'fundraising_order_id'                   => $order->get_id(),
			'fundraising_order_number'               => $order->get_order_number(),
			'fundraising_order_status'               => $order->get_status(),
			'fundraising_order_status_label'         => wc_get_order_status_name( $order->get_status() ),
			'fundraising_order_total'                => $order->get_total(),
			'fundraising_order_currency'             => $order->get_currency(),
			'fundraising_order_payment_method'       => $order->get_payment_method(),
			'fundraising_order_payment_method_title' => $order->get_payment_method_title(),
			'fundraising_order_transaction_id'       => $order->get_transaction_id(),
			'fundraising_order_customer_id'          => $order->get_customer_id(),
			'fundraising_order_billing_email'        => $order->get_billing_email(),
			'fundraising_order_created'              => adfoin_fundraising_format_wc_datetime( $order->get_date_created() ),
			'fundraising_order_completed'            => adfoin_fundraising_format_wc_datetime( $order->get_date_completed() ),
			'fundraising_order_paid_date'            => adfoin_fundraising_format_wc_datetime( $order->get_date_paid() ),
			'fundraising_order_url'                  => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'fundraising_customer_ip_address'        => $order->get_customer_ip_address(),
			'fundraising_customer_user_agent'        => $order->get_customer_user_agent(),
		);
	}

	$user = $ticket->get_user();

	$user_first_name = '';
	$user_last_name  = '';

	if ( $user instanceof WP_User ) {
		$user_first_name = $user->first_name;
		$user_last_name  = $user->last_name;
	}

	return array_merge(
		$product_payload,
		array(
			'fundraising_ticket_id'             => $ticket->get_id(),
			'fundraising_ticket_status'         => $ticket->get_status(),
			'fundraising_ticket_status_label'   => adfoin_fundraising_ticket_status_label( $ticket->get_status() ),
			'fundraising_ticket_number'         => $ticket->get_lottery_ticket_number(),
			'fundraising_ticket_amount'         => $ticket->get_amount(),
			'fundraising_ticket_currency'       => $ticket->get_currency(),
			'fundraising_ticket_user_id'        => $ticket->get_user_id(),
			'fundraising_ticket_user_name'      => $ticket->get_user_name(),
			'fundraising_ticket_user_email'     => $ticket->get_user_email(),
			'fundraising_ticket_first_name'     => $user_first_name ? $user_first_name : $ticket->get_first_name(),
			'fundraising_ticket_last_name'      => $user_last_name ? $user_last_name : $ticket->get_last_name(),
			'fundraising_ticket_ip_address'     => $ticket->get_ip_address(),
			'fundraising_ticket_answer'         => $ticket->get_answer(),
			'fundraising_ticket_answers'        => adfoin_fundraising_encode( $ticket->get_answers() ),
			'fundraising_ticket_is_valid_answer'=> adfoin_fundraising_bool_to_string( 'yes' === $ticket->get_valid_answer() ),
			'fundraising_ticket_list_count'     => $ticket->get_list_count(),
			'fundraising_ticket_created_gmt'    => adfoin_fundraising_format_datetime( $ticket->get_created_date() ),
			'fundraising_ticket_created'        => $ticket->get_formatted_created_date(),
			'fundraising_ticket_view_order_url' => $ticket->get_view_order_link(),
		),
		$order_data
	);
}

/**
 * Gather meta placeholders.
 *
 * @param array<int,array<string,mixed>> $records Records.
 *
 * @return array<string,string[]>
 */
function adfoin_fundraising_meta_placeholders( $records ) {
	$placeholders = array(
		'lottery' => array(),
		'product' => array(),
		'ticket'  => array(),
		'order'   => array(),
		'user'    => array(),
	);

	if ( empty( $records ) ) {
		return $placeholders;
	}

	$prefixes = array(
		'lottery' => 'lotterymeta_',
		'product' => 'productmeta_',
		'ticket'  => 'ticketmeta_',
		'order'   => 'ordermeta_',
		'user'    => 'usermeta_',
	);

	foreach ( $records as $record ) {
		if ( empty( $record['data'] ) ) {
			continue;
		}

		$data = json_decode( $record['data'], true );

		if ( empty( $data['field_data'] ) || ! is_array( $data['field_data'] ) ) {
			continue;
		}

		foreach ( $data['field_data'] as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			foreach ( $prefixes as $type => $prefix ) {
				if ( false === strpos( $value, $prefix ) ) {
					continue;
				}

				preg_match_all( '/' . preg_quote( $prefix, '/' ) . '.+?\}\}/', $value, $matches );

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
 * Resolve meta values for requested placeholders.
 *
 * @param array<string,string[]>         $placeholders Placeholders.
 * @param WC_Product|null                $product      Product.
 * @param LTY_Lottery_Ticket|false|null  $ticket       Ticket.
 * @param WC_Order|null                  $order        Order.
 * @param WP_User|null                   $user         User.
 *
 * @return array<string,string>
 */
function adfoin_fundraising_meta_values( $placeholders, $product, $ticket, $order, $user ) {
	$values = array();

	if ( $product && ( ! empty( $placeholders['lottery'] ) || ! empty( $placeholders['product'] ) ) ) {
		$product_meta = adfoin_fundraising_collect_post_meta( $product->get_id() );

		foreach ( $placeholders['lottery'] as $tag ) {
			$key             = str_replace( 'lotterymeta_', '', $tag );
			$values[ $tag ]  = adfoin_fundraising_meta_to_string( isset( $product_meta[ $key ] ) ? $product_meta[ $key ] : '' );
		}

		foreach ( $placeholders['product'] as $tag ) {
			$key             = str_replace( 'productmeta_', '', $tag );
			$values[ $tag ]  = adfoin_fundraising_meta_to_string( isset( $product_meta[ $key ] ) ? $product_meta[ $key ] : '' );
		}
	}

	if ( $ticket && ! empty( $placeholders['ticket'] ) ) {
		$ticket_meta = adfoin_fundraising_collect_post_meta( $ticket->get_id() );

		foreach ( $placeholders['ticket'] as $tag ) {
			$key             = str_replace( 'ticketmeta_', '', $tag );
			$values[ $tag ]  = adfoin_fundraising_meta_to_string( isset( $ticket_meta[ $key ] ) ? $ticket_meta[ $key ] : '' );
		}
	}

	if ( $order && ! empty( $placeholders['order'] ) ) {
		foreach ( $placeholders['order'] as $tag ) {
			$key             = str_replace( 'ordermeta_', '', $tag );
			$meta_value      = $order->get_meta( $key, true );
			$values[ $tag ]  = adfoin_fundraising_meta_to_string( $meta_value );
		}
	}

	if ( $user instanceof WP_User && ! empty( $placeholders['user'] ) ) {
		foreach ( $placeholders['user'] as $tag ) {
			$key             = str_replace( 'usermeta_', '', $tag );
			$meta_value      = get_user_meta( $user->ID, $key, true );
			$values[ $tag ]  = adfoin_fundraising_meta_to_string( $meta_value );
		}
	}

	return $values;
}

/**
 * Collect post meta keyed array.
 *
 * @param int $post_id Post ID.
 *
 * @return array<string,mixed>
 */
function adfoin_fundraising_collect_post_meta( $post_id ) {
	if ( ! $post_id ) {
		return array();
	}

	$meta = get_post_meta( $post_id );

	if ( empty( $meta ) ) {
		return array();
	}

	$formatted = array();

	foreach ( $meta as $key => $values ) {
		if ( is_array( $values ) ) {
			$values = array_map( 'maybe_unserialize', $values );
			$formatted[ $key ] = ( 1 === count( $values ) ) ? $values[0] : $values;
		} else {
			$formatted[ $key ] = maybe_unserialize( $values );
		}
	}

	return $formatted;
}

/**
 * Convert meta values to strings.
 *
 * @param mixed $value Meta value.
 *
 * @return string
 */
function adfoin_fundraising_meta_to_string( $value ) {
	if ( is_bool( $value ) ) {
		return adfoin_fundraising_bool_to_string( $value );
	}

	if ( is_array( $value ) || is_object( $value ) ) {
		return wp_json_encode( $value );
	}

	return (string) $value;
}

/**
 * Get a lottery product instance.
 *
 * @param mixed $product Product or ID.
 *
 * @return WC_Product_Lottery|null
 */
function adfoin_fundraising_get_lottery_product( $product ) {
	if ( $product instanceof WC_Product_Lottery ) {
		return $product;
	}

	if ( $product instanceof WC_Product ) {
		if ( 'lottery' === $product->get_type() ) {
			return $product instanceof WC_Product_Lottery ? $product : new WC_Product_Lottery( $product->get_id() );
		}

		return null;
	}

	if ( is_numeric( $product ) ) {
		$product_obj = wc_get_product( $product );

		if ( $product_obj instanceof WC_Product && 'lottery' === $product_obj->get_type() ) {
			return $product_obj instanceof WC_Product_Lottery ? $product_obj : new WC_Product_Lottery( $product_obj->get_id() );
		}
	}

	return null;
}

/**
 * Trim lottery status prefix.
 *
 * @param string $status Status value.
 *
 * @return string
 */
function adfoin_fundraising_trim_status( $status ) {
	$status = (string) $status;

	if ( 0 === strpos( $status, 'lty_lottery_' ) ) {
		return substr( $status, strlen( 'lty_lottery_' ) );
	}

	return $status;
}

/**
 * Lottery status labels.
 *
 * @param string $status Status key.
 *
 * @return string
 */
function adfoin_fundraising_status_label( $status ) {
	if ( function_exists( 'lty_get_lottery_statuses' ) ) {
		$statuses = lty_get_lottery_statuses();

		if ( isset( $statuses[ 'lty_lottery_' . $status ] ) ) {
			return $statuses[ 'lty_lottery_' . $status ];
		}
	}

	return ucwords( str_replace( '_', ' ', $status ) );
}

/**
 * Ticket status label.
 *
 * @param string $status Ticket status.
 *
 * @return string
 */
function adfoin_fundraising_ticket_status_label( $status ) {
	if ( function_exists( 'lty_get_ticket_status_labels' ) ) {
		$labels = lty_get_ticket_status_labels();

		if ( isset( $labels[ $status ] ) ) {
			return $labels[ $status ];
		}
	}

	return ucwords( str_replace( '_', ' ', $status ) );
}

/**
 * Encode array/object values.
 *
 * @param mixed $value Value.
 *
 * @return string
 */
function adfoin_fundraising_encode( $value ) {
	if ( empty( $value ) ) {
		return '';
	}

	return wp_json_encode( $value );
}

/**
 * Format timestamp.
 *
 * @param int|string $timestamp Timestamp or strtotime string.
 *
 * @return string
 */
function adfoin_fundraising_format_datetime( $timestamp ) {
	if ( empty( $timestamp ) ) {
		return '';
	}

	$timestamp = is_numeric( $timestamp ) ? (int) $timestamp : strtotime( (string) $timestamp );

	if ( ! $timestamp ) {
		return '';
	}

	return gmdate( 'Y-m-d H:i:s', $timestamp );
}

/**
 * Format WooCommerce datetime.
 *
 * @param WC_DateTime|null $datetime Datetime object.
 *
 * @return string
 */
function adfoin_fundraising_format_wc_datetime( $datetime ) {
	if ( $datetime instanceof WC_DateTime ) {
		return $datetime->date( 'Y-m-d H:i:s' );
	}

	return '';
}

/**
 * Convert booleans to yes/no string.
 *
 * @param mixed $value Value.
 *
 * @return string
 */
function adfoin_fundraising_bool_to_string( $value ) {
	return $value ? 'yes' : 'no';
}
