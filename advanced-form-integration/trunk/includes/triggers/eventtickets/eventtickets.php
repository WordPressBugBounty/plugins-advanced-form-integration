<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Get EventTickets Triggers.
 *
 * @param string $form_provider The integration provider.
 * @return array|void
 */
function adfoin_eventtickets_get_forms( $form_provider ) {
    if ( $form_provider != 'eventtickets' ) {
        return;
    }
    
    $triggers = array(
        'attendsEvent'         => __( 'User Attends Event', 'advanced-form-integration' ),
        'attendeeRegistered'   => __( 'Attendee Registered for Event', 'advanced-form-integration' ),
        'newAttendee'          => __( 'New Attendee', 'advanced-form-integration' ),
        'attendeeRegisteredWC' => __( 'Attendee Registered with WooCommerce', 'advanced-form-integration' ),
    );
    
    return $triggers;
}

/**
 * Get EventTickets Form Fields.
 *
 * @param string $form_provider The integration provider.
 * @param string $form_id       The specific trigger ID.
 * @return array|void
 */
function adfoin_eventtickets_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'eventtickets' ) {
        return;
    }
    
    $fields = array();
    
    if ( $form_id === 'attendsEvent' ) {
        // When a user attends an event (check-in).
        $fields = array(
            'attendee_id'      => __( 'Attendee ID', 'advanced-form-integration' ),
            'event_id'         => __( 'Event ID', 'advanced-form-integration' ),
            'purchaser_name'   => __( 'Purchaser Name', 'advanced-form-integration' ),
            'purchaser_email'  => __( 'Purchaser Email', 'advanced-form-integration' ),
            'holder_name'      => __( 'Holder Name', 'advanced-form-integration' ),
            'holder_email'     => __( 'Holder Email', 'advanced-form-integration' ),
            'ticket_id'        => __( 'Ticket ID', 'advanced-form-integration' ),
            'ticket_name'      => __( 'Ticket Name', 'advanced-form-integration' ),
            'qr_ticket_id'     => __( 'QR Ticket ID', 'advanced-form-integration' ),
            'price_paid'       => __( 'Price Paid', 'advanced-form-integration' ),
            'currency'         => __( 'Currency', 'advanced-form-integration' ),
            'attendee_date'    => __( 'Attendee Date', 'advanced-form-integration' ),
            'order_id'         => __( 'Order ID', 'advanced-form-integration' ),
            'order_status'     => __( 'Order Status', 'advanced-form-integration' ),
            'check_in'         => __( 'Check In', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'attendeeRegistered' ) {
        // When an attendee is registered for an event.
        $fields = array(
            'attendee_id'      => __( 'Attendee ID', 'advanced-form-integration' ),
            'event_id'         => __( 'Event ID', 'advanced-form-integration' ),
            'purchaser_name'   => __( 'Purchaser Name', 'advanced-form-integration' ),
            'purchaser_email'  => __( 'Purchaser Email', 'advanced-form-integration' ),
            'ticket_id'        => __( 'Ticket ID', 'advanced-form-integration' ),
            'ticket_name'      => __( 'Ticket Name', 'advanced-form-integration' ),
            'order_id'         => __( 'Order ID', 'advanced-form-integration' ),
            'order_status'     => __( 'Order Status', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'newAttendee' ) {
        // When new tickets are generated (new attendee record).
        $fields = array(
            'purchaser_name'    => __( 'Purchaser Name', 'advanced-form-integration' ),
            'purchaser_email'   => __( 'Purchaser Email', 'advanced-form-integration' ),
            'holder_names'      => __( 'Holder Name(s)', 'advanced-form-integration' ),
            'holder_emails'     => __( 'Holder Email(s)', 'advanced-form-integration' ),
            'attendee_ids'      => __( 'Attendee ID(s)', 'advanced-form-integration' ),
            'ticket_ids'        => __( 'Ticket ID(s)', 'advanced-form-integration' ),
            'qr_ticket_ids'     => __( 'QR Ticket ID(s)', 'advanced-form-integration' ),
            'ticket_name'       => __( 'Ticket Name', 'advanced-form-integration' ),
            'order_ids'         => __( 'Order ID(s)', 'advanced-form-integration' ),
            'order_status'      => __( 'Order Status', 'advanced-form-integration' ),
            'purchase_time'     => __( 'Purchase Time', 'advanced-form-integration' ),
            'event_id'          => __( 'Event ID', 'advanced-form-integration' ),
            'event_name'        => __( 'Event Name', 'advanced-form-integration' ),
            'event_date'        => __( 'Event Date', 'advanced-form-integration' ),
            'event_date_gmt'    => __( 'Event Date GMT', 'advanced-form-integration' ),
            'event_modified'    => __( 'Event Modified', 'advanced-form-integration' ),
            'event_modified_gmt'=> __( 'Event Modified GMT', 'advanced-form-integration' ),
            'event_guid'        => __( 'Event GUID', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'attendeeRegisteredWC' ) {
        // When an attendee is registered using WooCommerce.
        $fields = array(
            'attendee_id'     => __( 'Attendee ID', 'advanced-form-integration' ),
            'order_id'        => __( 'Order ID', 'advanced-form-integration' ),
            'ticket_id'       => __( 'Ticket ID', 'advanced-form-integration' ),
            'event_id'        => __( 'Event ID', 'advanced-form-integration' ),
            'purchaser_email' => __( 'Purchaser Email', 'advanced-form-integration' ),
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
function adfoin_eventtickets_get_userdata( $user_id ) {
	$user_data = array();
	$user = get_userdata( $user_id);
	if ( $user ) {
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
		$user_data['user_email'] = $user->user_email;
		$user_data['user_id']    = $user_id;
	}
	return $user_data;
}

/**
 * Handle "User Attends Event" event.
 *
 * This function fires when an attendee checks in via an EventTickets hook.
 *
 * @param int $attendee_id The attendee ID.
 * @param mixed $qr      QR code or additional data.
 */
function adfoin_eventtickets_handle_attends_event( $attendee_id, $qr ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventtickets', 'attendsEvent' );
	if ( empty( $saved_records ) ) {
		return;
	}
	// Ensure the required function exists.
	if ( ! function_exists( 'tribe_tickets_get_attendees' ) ) {
		return;
	}
	$attendees = tribe_tickets_get_attendees( $attendee_id, 'rsvp_order' );
	if ( empty( $attendees ) ) {
		return;
	}
	$attendee = $attendees[0];
	$posted_data = array(
		'attendee_id'      => isset( $attendee['attendee_id'] ) ? $attendee['attendee_id'] : '',
		'event_id'         => isset( $attendee['event_id'] ) ? $attendee['event_id'] : '',
		'purchaser_name'   => isset( $attendee['purchaser_name'] ) ? $attendee['purchaser_name'] : '',
		'purchaser_email'  => isset( $attendee['purchaser_email'] ) ? $attendee['purchaser_email'] : '',
		'holder_name'      => isset( $attendee['holder_name'] ) ? $attendee['holder_name'] : '',
		'holder_email'     => isset( $attendee['holder_email'] ) ? $attendee['holder_email'] : '',
		'ticket_id'        => isset( $attendee['ticket_id'] ) ? $attendee['ticket_id'] : '',
		'ticket_name'      => isset( $attendee['ticket_name'] ) ? $attendee['ticket_name'] : '',
		'qr_ticket_id'     => isset( $attendee['qr_ticket_id'] ) ? $attendee['qr_ticket_id'] : '',
		'price_paid'       => isset( $attendee['price_paid'] ) ? $attendee['price_paid'] : '',
		'currency'         => isset( $attendee['currency'] ) ? $attendee['currency'] : '',
		'attendee_date'    => isset( $attendee['post_date'] ) ? $attendee['post_date'] : '',
		'order_id'         => isset( $attendee['order_id'] ) ? $attendee['order_id'] : '',
		'order_status'     => isset( $attendee['order_status'] ) ? $attendee['order_status'] : '',
		'check_in'         => isset( $attendee['check_in'] ) ? $attendee['check_in'] : '',
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'event_tickets_checkin', 'adfoin_eventtickets_handle_attends_event', 10, 2 );
add_action( 'eddtickets_checkin', 'adfoin_eventtickets_handle_attends_event', 10, 2 );
add_action( 'rsvp_checkin', 'adfoin_eventtickets_handle_attends_event', 10, 2 );
add_action( 'wootickets_checkin', 'adfoin_eventtickets_handle_attends_event', 10, 2 );

/**
 * Handle "Attendee Registered for Event" event.
 *
 * Fires when an attendee is registered for an event.
 *
 * @param int   $attendee_id        The attendee ID.
 * @param int   $post_id            The post ID.
 * @param mixed $order              Order details.
 * @param int   $attendeeProductId  The product ID.
 * @param mixed $attendeeOrderStatus Optional order status.
 */
function adfoin_eventtickets_handle_attendee_registered( $attendee_id, $post_id, $order, $attendeeProductId, $attendeeOrderStatus = null ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventtickets', 'attendeeRegistered' );
	if ( empty( $saved_records ) ) {
		return;
	}
	if ( ! function_exists( 'tribe_tickets_get_attendees' ) ) {
		return;
	}
	$attendees = tribe_tickets_get_attendees( $attendee_id );
	if ( empty( $attendees ) ) {
		return;
	}
	$attendee = $attendees[0];
	$posted_data = array(
		'attendee_id'      => isset( $attendee['attendee_id'] ) ? $attendee['attendee_id'] : '',
		'event_id'         => isset( $attendee['event_id'] ) ? $attendee['event_id'] : '',
		'purchaser_name'   => isset( $attendee['purchaser_name'] ) ? $attendee['purchaser_name'] : '',
		'purchaser_email'  => isset( $attendee['purchaser_email'] ) ? $attendee['purchaser_email'] : '',
		'ticket_id'        => isset( $attendee['ticket_id'] ) ? $attendee['ticket_id'] : '',
		'ticket_name'      => isset( $attendee['ticket_name'] ) ? $attendee['ticket_name'] : '',
		'order_id'         => isset( $attendee['order_id'] ) ? $attendee['order_id'] : '',
		'order_status'     => isset( $attendee['order_status'] ) ? $attendee['order_status'] : '',
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'event_tickets_rsvp_attendee_created', 'adfoin_eventtickets_handle_attendee_registered', 10, 5 );
add_action( 'event_ticket_woo_attendee_created', 'adfoin_eventtickets_handle_attendee_registered', 10, 5 );
add_action( 'event_ticket_edd_attendee_created', 'adfoin_eventtickets_handle_attendee_registered', 10, 5 );
add_action( 'event_tickets_tpp_attendee_created', 'adfoin_eventtickets_handle_attendee_registered', 10, 5 );
add_action( 'event_tickets_tpp_attendee_updated', 'adfoin_eventtickets_handle_attendee_registered', 10, 5 );
add_action( 'tec_tickets_commerce_attendee_after_create', 'adfoin_eventtickets_handle_attendee_registered', 10, 5 );

/**
 * Handle "New Attendee" event.
 *
 * Fires when tickets are generated and a new attendee record is created.
 *
 * @param int $product_id The product ID.
 * @param int $order_id   The order ID.
 */
function adfoin_eventtickets_handle_new_attendee( $product_id, $order_id ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventtickets', 'newAttendee' );
	if ( empty( $saved_records ) ) {
		return;
	}
	// Ensure required function exists.
	if ( ! function_exists( 'tribe_events_get_ticket_event' ) ) {
		return;
	}
	$event = tribe_events_get_ticket_event( $product_id );
	if ( empty( $event ) ) {
		return;
	}
	$posted_data = array(
		'ticket_name'  => get_the_title( $product_id ),
		'order_id'     => $order_id,
		'event_id'     => $event->ID,
		'event_name'   => $event->post_title,
		'event_date'   => $event->post_date,
		'order_status' => '', // Add additional data as needed.
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'event_tickets_rsvp_tickets_generated_for_product', 'adfoin_eventtickets_handle_new_attendee', 10, 2 );
add_action( 'event_tickets_woocommerce_tickets_generated_for_product', 'adfoin_eventtickets_handle_new_attendee', 10, 2 );
add_action( 'event_tickets_tpp_tickets_generated_for_product', 'adfoin_eventtickets_handle_new_attendee', 10, 2 );

/**
 * Handle "Attendee Registered with WooCommerce" event.
 *
 * Fires when an attendee is created via WooCommerce.
 *
 * @param mixed $attendee     The attendee object.
 * @param array $attendee_data Data related to the attendee.
 * @param object $ticket      The ticket object.
 * @param mixed $repository   Additional repository info.
 */
function adfoin_eventtickets_handle_attendee_registered_wc( $attendee, $attendee_data, $ticket, $repository ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'eventtickets', 'attendeeRegisteredWC' );
	if ( empty( $saved_records ) ) {
		return;
	}
	$orderId = isset( $attendee_data['order_id'] ) ? $attendee_data['order_id'] : '';
	$productId = $ticket->ID;
	if ( ! function_exists( 'tribe_events_get_ticket_event' ) ) {
		return;
	}
	$event = tribe_events_get_ticket_event( $productId );
	if ( empty( $event ) ) {
		return;
	}
	$posted_data = array(
		'order_id'        => $orderId,
		'ticket_id'       => $productId,
		'event_id'        => $event->ID,
		'purchaser_email' => isset( $attendee_data['attendee_email'] ) ? $attendee_data['attendee_email'] : '',
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'tribe_tickets_attendee_repository_create_attendee_for_ticket_after_create', 'adfoin_eventtickets_handle_attendee_registered_wc', 10, 4 );