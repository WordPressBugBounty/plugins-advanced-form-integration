<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tickera (Event Ticketing System) triggers for Advanced Form Integration.
 *
 * Hooks:
 *  - tickera_order_created                -> newOrder   (order placed, any status)
 *  - tickera_order_updated_status_to_paid -> orderPaid  (order transitioned to paid)
 *
 * Both hooks pass: ( $order_id, $status, $cart_contents, $cart_info, $payment_info ).
 * NOTE: Tickera passes the order *name* (e.g. "C2F09IE46") as $order_id, not the
 * numeric post ID. We resolve the real post ID via tickera_get_order_id_by_name().
 *
 * Data model:
 *  - $cart_contents : array( ticket_type_id => quantity )
 *  - $cart_info     : [ 'buyer_data' => [...], 'owner_data' => [...], 'gateway_class' => '', 'currency' => '' ]
 *  - $payment_info  : [ 'subtotal', 'fees_total', 'tax_total', 'total', 'discount', 'transaction_id', 'currency', 'gateway' ]
 *  - Order post meta: tc_cart_contents, tc_cart_info, tc_payment_info, tc_order_date, tc_discount_code
 */

/**
 * Return available Tickera triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_tickera_get_forms( $form_provider ) {
	if ( 'tickera' !== $form_provider ) {
		return;
	}

	return array(
		'newOrder'  => __( 'New Order Created', 'advanced-form-integration' ),
		'orderPaid' => __( 'Order Paid', 'advanced-form-integration' ),
	);
}

/**
 * Return mappable fields for Tickera triggers.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_tickera_get_form_fields( $form_provider, $form_id ) {
	if ( 'tickera' !== $form_provider ) {
		return;
	}

	if ( ! in_array( $form_id, array( 'newOrder', 'orderPaid' ), true ) ) {
		return array();
	}

	$fields = array(
		// Order
		'order_id'        => __( 'Order ID (Post ID)', 'advanced-form-integration' ),
		'order_number'    => __( 'Order Number (Name)', 'advanced-form-integration' ),
		'order_status'    => __( 'Order Status', 'advanced-form-integration' ),
		'order_date'      => __( 'Order Date', 'advanced-form-integration' ),
		'order_edit_url'  => __( 'Order Admin Edit URL', 'advanced-form-integration' ),
		// Buyer
		'buyer_first_name' => __( 'Buyer First Name', 'advanced-form-integration' ),
		'buyer_last_name'  => __( 'Buyer Last Name', 'advanced-form-integration' ),
		'buyer_full_name'  => __( 'Buyer Full Name', 'advanced-form-integration' ),
		'buyer_email'      => __( 'Buyer Email', 'advanced-form-integration' ),
		// Payment
		'currency'        => __( 'Currency', 'advanced-form-integration' ),
		'subtotal'        => __( 'Subtotal', 'advanced-form-integration' ),
		'fees_total'      => __( 'Fees Total', 'advanced-form-integration' ),
		'tax_total'       => __( 'Tax Total', 'advanced-form-integration' ),
		'discount'        => __( 'Discount', 'advanced-form-integration' ),
		'total'           => __( 'Total', 'advanced-form-integration' ),
		'total_formatted' => __( 'Total (Formatted)', 'advanced-form-integration' ),
		'transaction_id'  => __( 'Transaction ID', 'advanced-form-integration' ),
		'payment_method'  => __( 'Payment Method (Gateway)', 'advanced-form-integration' ),
		'discount_code'   => __( 'Discount / Coupon Code', 'advanced-form-integration' ),
		// Tickets & events
		'ticket_count'    => __( 'Ticket Count', 'advanced-form-integration' ),
		'ticket_summary'  => __( 'Ticket Summary (Text)', 'advanced-form-integration' ),
		'tickets_json'    => __( 'Tickets (JSON)', 'advanced-form-integration' ),
		'event_names'     => __( 'Event Name(s)', 'advanced-form-integration' ),
		'event_locations' => __( 'Event Location(s)', 'advanced-form-integration' ),
		'attendees_json'  => __( 'Attendees (JSON)', 'advanced-form-integration' ),
	);

	// Back-compat for integrations saved against the old field keys.
	$fields['event_name']  = __( 'Event Name (first, deprecated)', 'advanced-form-integration' );
	$fields['ticket_name'] = __( 'Ticket Name(s), deprecated', 'advanced-form-integration' );

	return $fields;
}

/**
 * Handle "tickera_order_created".
 *
 * @param string $order_id      Order name (e.g. "C2F09IE46").
 * @param string $status        Order status.
 * @param array  $cart_contents Ticket type id => quantity.
 * @param array  $cart_info     Buyer / owner / gateway info.
 * @param array  $payment_info  Totals, fees, tax, transaction id, etc.
 */
function adfoin_tickera_handle_order_created( $order_id, $status, $cart_contents, $cart_info, $payment_info ) {
	adfoin_tickera_process( 'newOrder', $order_id, $status, $cart_contents, $cart_info, $payment_info );
}
add_action( 'tickera_order_created', 'adfoin_tickera_handle_order_created', 10, 5 );

/**
 * Handle "tickera_order_updated_status_to_paid".
 *
 * @param string $order_id      Order name or numeric ID.
 * @param string $status        Order status (order_paid).
 * @param array  $cart_contents Ticket type id => quantity.
 * @param array  $cart_info     Buyer / owner / gateway info.
 * @param array  $payment_info  Totals, fees, tax, transaction id, etc.
 */
function adfoin_tickera_handle_order_paid( $order_id, $status, $cart_contents, $cart_info, $payment_info ) {
	adfoin_tickera_process( 'orderPaid', $order_id, $status, $cart_contents, $cart_info, $payment_info );
}
add_action( 'tickera_order_updated_status_to_paid', 'adfoin_tickera_handle_order_paid', 10, 5 );

/**
 * Shared processor: resolve data, build payload, dispatch.
 *
 * @param string $trigger       Trigger key (newOrder|orderPaid).
 * @param string $order_id      Order name or numeric ID.
 * @param string $status        Order status.
 * @param array  $cart_contents Ticket type id => quantity.
 * @param array  $cart_info     Buyer / owner / gateway info.
 * @param array  $payment_info  Totals, fees, tax, transaction id, etc.
 */
function adfoin_tickera_process( $trigger, $order_id, $status, $cart_contents, $cart_info, $payment_info ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'tickera', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$order_post_id = adfoin_tickera_resolve_order_post_id( $order_id );

	// Fall back to order post meta when the hook didn't supply data.
	if ( $order_post_id ) {
		if ( empty( $cart_contents ) ) {
			$cart_contents = get_post_meta( $order_post_id, 'tc_cart_contents', true );
		}
		if ( empty( $cart_info ) ) {
			$cart_info = get_post_meta( $order_post_id, 'tc_cart_info', true );
		}
		if ( empty( $payment_info ) ) {
			$payment_info = get_post_meta( $order_post_id, 'tc_payment_info', true );
		}
	}

	$cart_contents = is_array( $cart_contents ) ? $cart_contents : array();
	$cart_info     = is_array( $cart_info ) ? $cart_info : array();
	$payment_info  = is_array( $payment_info ) ? $payment_info : array();

	$payload = adfoin_tickera_build_payload( $order_post_id, $order_id, $status, $cart_contents, $cart_info, $payment_info );

	if ( empty( $payload ) ) {
		return;
	}

	adfoin_dispatch_integrations( $saved_records, $payload );
}

/**
 * Resolve the numeric order post ID from the value Tickera passes
 * (which is the order *name*, e.g. "C2F09IE46", in most hooks).
 *
 * @param mixed $order_id Order name or numeric ID.
 * @return int
 */
function adfoin_tickera_resolve_order_post_id( $order_id ) {
	if ( is_numeric( $order_id ) ) {
		return absint( $order_id );
	}

	if ( function_exists( 'tickera_get_order_id_by_name' ) ) {
		$post = tickera_get_order_id_by_name( $order_id );
		if ( $post && isset( $post->ID ) ) {
			return (int) $post->ID;
		}
	}

	return 0;
}

/**
 * Build the flat payload.
 *
 * @param int    $order_post_id Numeric order post ID (0 if unresolved).
 * @param string $order_number  Order name / number.
 * @param string $status        Order status.
 * @param array  $cart_contents Ticket type id => quantity.
 * @param array  $cart_info     Buyer / owner / gateway info.
 * @param array  $payment_info  Totals, fees, tax, transaction id, etc.
 * @return array
 */
function adfoin_tickera_build_payload( $order_post_id, $order_number, $status, $cart_contents, $cart_info, $payment_info ) {
	$buyer = isset( $cart_info['buyer_data'] ) && is_array( $cart_info['buyer_data'] ) ? $cart_info['buyer_data'] : array();
	$owner = isset( $cart_info['owner_data'] ) && is_array( $cart_info['owner_data'] ) ? $cart_info['owner_data'] : array();

	$buyer_first = isset( $buyer['first_name_post_meta'] ) ? $buyer['first_name_post_meta'] : '';
	$buyer_last  = isset( $buyer['last_name_post_meta'] ) ? $buyer['last_name_post_meta'] : '';
	$buyer_email = isset( $buyer['email_post_meta'] ) ? $buyer['email_post_meta'] : '';

	// Payment / money fields.
	$currency = '';
	if ( isset( $payment_info['currency'] ) && $payment_info['currency'] ) {
		$currency = $payment_info['currency'];
	} elseif ( isset( $cart_info['currency'] ) && $cart_info['currency'] ) {
		$currency = $cart_info['currency'];
	}
	if ( ! $currency ) {
		$settings = get_option( 'tickera_general_setting', false );
		if ( is_array( $settings ) ) {
			$currency = ! empty( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : ( isset( $settings['currencies'] ) ? $settings['currencies'] : '' );
		}
	}

	$subtotal       = isset( $payment_info['subtotal'] ) ? $payment_info['subtotal'] : '';
	$fees_total     = isset( $payment_info['fees_total'] ) ? $payment_info['fees_total'] : '';
	$tax_total      = isset( $payment_info['tax_total'] ) ? $payment_info['tax_total'] : '';
	$total          = isset( $payment_info['total'] ) ? $payment_info['total'] : '';
	$discount       = isset( $payment_info['discount'] ) ? $payment_info['discount'] : ( isset( $payment_info['discount_total'] ) ? $payment_info['discount_total'] : '' );
	$transaction_id = isset( $payment_info['transaction_id'] ) ? $payment_info['transaction_id'] : '';
	$gateway        = isset( $cart_info['gateway_admin_name'] ) ? $cart_info['gateway_admin_name'] : ( isset( $cart_info['gateway_class'] ) ? $cart_info['gateway_class'] : '' );

	$total_formatted = ( '' !== $total ) ? adfoin_tickera_format_amount( $total ) : '';

	$discount_code = '';
	if ( $order_post_id ) {
		$discount_code = get_post_meta( $order_post_id, 'tc_discount_code', true );
	}
	if ( ! $discount_code && isset( $cart_info['coupon_code'] ) ) {
		$discount_code = $cart_info['coupon_code'];
	}

	// Order date.
	$order_date = '';
	if ( $order_post_id ) {
		$order_date = get_post_meta( $order_post_id, 'tc_order_date', true );
		if ( ! $order_date ) {
			$post = get_post( $order_post_id );
			if ( $post ) {
				$order_date = $post->post_date;
			}
		}
	}

	// Tickets / events / attendees.
	$tickets     = adfoin_tickera_build_tickets( $order_post_id, $cart_contents );
	$events      = adfoin_tickera_collect_events( $order_post_id );
	$attendees   = adfoin_tickera_build_attendees( $order_post_id, $owner );

	$ticket_names = array();
	foreach ( $tickets as $t ) {
		if ( ! empty( $t['ticket_name'] ) ) {
			$ticket_names[] = $t['ticket_name'] . ' x' . $t['quantity'];
		}
	}

	$event_names     = array();
	$event_locations = array();
	foreach ( $events as $ev ) {
		if ( ! empty( $ev['name'] ) && ! in_array( $ev['name'], $event_names, true ) ) {
			$event_names[] = $ev['name'];
		}
		if ( ! empty( $ev['location'] ) && ! in_array( $ev['location'], $event_locations, true ) ) {
			$event_locations[] = $ev['location'];
		}
	}

	$order_edit_url = $order_post_id ? admin_url( 'post.php?post=' . $order_post_id . '&action=edit' ) : '';

	return array(
		'order_id'         => $order_post_id,
		'order_number'     => $order_number,
		'order_status'     => $status,
		'order_date'       => $order_date,
		'order_edit_url'   => $order_edit_url,
		'buyer_first_name' => $buyer_first,
		'buyer_last_name'  => $buyer_last,
		'buyer_full_name'  => trim( $buyer_first . ' ' . $buyer_last ),
		'buyer_email'      => $buyer_email,
		'currency'         => $currency,
		'subtotal'         => $subtotal,
		'fees_total'       => $fees_total,
		'tax_total'        => $tax_total,
		'discount'         => $discount,
		'total'            => $total,
		'total_formatted'  => $total_formatted,
		'transaction_id'   => $transaction_id,
		'payment_method'   => $gateway,
		'discount_code'    => $discount_code,
		'ticket_count'     => array_sum( array_map( 'intval', $cart_contents ) ),
		'ticket_summary'   => implode( ', ', $ticket_names ),
		'tickets_json'     => wp_json_encode( $tickets ),
		'event_names'      => implode( ' | ', $event_names ),
		'event_locations'  => implode( ' | ', $event_locations ),
		'attendees_json'   => wp_json_encode( $attendees ),
		// Deprecated back-compat keys.
		'event_name'       => isset( $event_names[0] ) ? $event_names[0] : '',
		'ticket_name'      => implode( ', ', $ticket_names ),
	);
}

/**
 * Build per-ticket rows from cart contents (ticket_type_id => qty).
 *
 * @param int   $order_post_id Order post ID.
 * @param array $cart_contents Ticket type id => quantity.
 * @return array
 */
function adfoin_tickera_build_tickets( $order_post_id, $cart_contents ) {
	$tickets = array();

	foreach ( $cart_contents as $ticket_type_id => $quantity ) {
		$ticket_type_id = absint( $ticket_type_id );
		$quantity       = (int) $quantity;

		$ticket_name = $ticket_type_id ? get_the_title( $ticket_type_id ) : '';
		$event_id    = 0;

		if ( $ticket_type_id ) {
			// Tickera stores the linked event on the ticket type via 'event_name' meta (an event post ID).
			$event_id = (int) get_post_meta( $ticket_type_id, 'event_name', true );
		}

		$tickets[] = array(
			'ticket_type_id' => $ticket_type_id,
			'ticket_name'    => $ticket_name,
			'quantity'       => $quantity,
			'event_id'       => $event_id,
			'event_name'     => $event_id ? get_the_title( $event_id ) : '',
			'unit_price'     => $ticket_type_id ? get_post_meta( $ticket_type_id, 'price_per_ticket', true ) : '',
		);
	}

	return $tickets;
}

/**
 * Collect unique events for the order. Prefers ticket-instance meta (event_id)
 * which Tickera writes when the order is created; falls back to ticket types.
 *
 * @param int $order_post_id Order post ID.
 * @return array
 */
function adfoin_tickera_collect_events( $order_post_id ) {
	$events = array();
	$seen   = array();

	$add_event = function( $event_id ) use ( &$events, &$seen ) {
		$event_id = (int) $event_id;
		if ( ! $event_id || isset( $seen[ $event_id ] ) ) {
			return;
		}
		$seen[ $event_id ] = true;
		$events[] = array(
			'id'       => $event_id,
			'name'     => get_the_title( $event_id ),
			'location' => get_post_meta( $event_id, 'event_location', true ),
		);
	};

	if ( $order_post_id ) {
		$instances = adfoin_tickera_get_ticket_instance_ids( $order_post_id );
		foreach ( $instances as $instance_id ) {
			$add_event( get_post_meta( $instance_id, 'event_id', true ) );
		}
	}

	return $events;
}

/**
 * Get ticket instance post IDs for the order.
 *
 * @param int $order_post_id Order post ID.
 * @return array
 */
function adfoin_tickera_get_ticket_instance_ids( $order_post_id ) {
	if ( ! $order_post_id ) {
		return array();
	}

	if ( class_exists( '\\Tickera\\TC_Orders' ) && method_exists( '\\Tickera\\TC_Orders', 'get_tickets_ids' ) ) {
		$ids = \Tickera\TC_Orders::get_tickets_ids( $order_post_id );
		return is_array( $ids ) ? $ids : array();
	}

	$posts = get_posts(
		array(
			'post_type'      => 'tc_tickets_instances',
			'post_status'    => 'any',
			'post_parent'    => $order_post_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	return is_array( $posts ) ? $posts : array();
}

/**
 * Build attendee rows from ticket instances + owner_data.
 *
 * @param int   $order_post_id Order post ID.
 * @param array $owner_data    cart_info['owner_data'].
 * @return array
 */
function adfoin_tickera_build_attendees( $order_post_id, $owner_data ) {
	$attendees = array();
	$instances = adfoin_tickera_get_ticket_instance_ids( $order_post_id );

	foreach ( $instances as $instance_id ) {
		$first = get_post_meta( $instance_id, 'first_name', true );
		$last  = get_post_meta( $instance_id, 'last_name', true );
		$email = get_post_meta( $instance_id, 'owner_email', true );
		if ( ! $email ) {
			$email = get_post_meta( $instance_id, 'email', true );
		}

		$ticket_type_id = get_post_meta( $instance_id, 'ticket_type_id', true );
		$event_id       = get_post_meta( $instance_id, 'event_id', true );

		$attendees[] = array(
			'ticket_instance_id' => (int) $instance_id,
			'ticket_code'        => get_post_meta( $instance_id, 'ticket_code', true ),
			'first_name'         => $first,
			'last_name'          => $last,
			'email'              => $email,
			'ticket_type_id'     => (int) $ticket_type_id,
			'ticket_name'        => $ticket_type_id ? get_the_title( $ticket_type_id ) : '',
			'event_id'           => (int) $event_id,
			'event_name'         => $event_id ? get_the_title( $event_id ) : '',
		);
	}

	// If no instances exist yet (e.g. some gateways), fall back to owner_data.
	if ( empty( $attendees ) && ! empty( $owner_data ) && is_array( $owner_data ) ) {
		$first = isset( $owner_data['first_name_post_meta'] ) ? $owner_data['first_name_post_meta'] : ( isset( $owner_data['first_name'] ) ? $owner_data['first_name'] : '' );
		$last  = isset( $owner_data['last_name_post_meta'] ) ? $owner_data['last_name_post_meta'] : ( isset( $owner_data['last_name'] ) ? $owner_data['last_name'] : '' );
		$email = isset( $owner_data['email_post_meta'] ) ? $owner_data['email_post_meta'] : ( isset( $owner_data['email'] ) ? $owner_data['email'] : '' );
		if ( $first || $last || $email ) {
			$attendees[] = array(
				'first_name' => $first,
				'last_name'  => $last,
				'email'      => $email,
			);
		}
	}

	return $attendees;
}

/**
 * Format an amount using Tickera's currency format if available.
 *
 * @param float|string $amount Amount.
 * @return string
 */
function adfoin_tickera_format_amount( $amount ) {
	// Tickera registers $tc->get_cart_currency_and_format() on this filter.
	$formatted = apply_filters( 'tickera_cart_currency_and_format', $amount );
	if ( is_string( $formatted ) && $formatted !== '' ) {
		return $formatted;
	}

	return is_scalar( $amount ) ? (string) $amount : '';
}
