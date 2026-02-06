<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get WP Travel Engine triggers.
 *
 * @param string $form_provider Integration key.
 * @return array|void
 */
function adfoin_wte_get_forms( $form_provider ) {
    if ( $form_provider !== 'wptravelengine' ) {
        return;
    }

    return array(
        'booking_created' => __( 'Booking Created', 'advanced-form-integration' ),
        'enquiry_created' => __( 'Enquiry Created', 'advanced-form-integration' ),
    );
}

/**
 * Get WP Travel Engine fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger identifier.
 * @return array|void
 */
function adfoin_wte_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wptravelengine' ) {
        return;
    }

    $fields = array();

    if ( 'booking_created' === $form_id ) {
        $fields = array(
            'trip_name'      => __( 'Trip Name', 'advanced-form-integration' ),
            'trip_price'     => __( 'Trip Price', 'advanced-form-integration' ),
            'pax'            => __( 'PAX', 'advanced-form-integration' ),
            'full_name'      => __( 'Full Name', 'advanced-form-integration' ),
            'email'          => __( 'Email', 'advanced-form-integration' ),
            'phone'          => __( 'Phone', 'advanced-form-integration' ),
            'country'        => __( 'Country', 'advanced-form-integration' ),
            'address'        => __( 'Address', 'advanced-form-integration' ),
            'city'           => __( 'City', 'advanced-form-integration' ),
            'state'          => __( 'State', 'advanced-form-integration' ),
            'zip'            => __( 'Zip', 'advanced-form-integration' ),
            'booking_id'     => __( 'Booking ID', 'advanced-form-integration' ),
        );
    } elseif ( 'enquiry_created' === $form_id ) {
        $fields = array(
            'trip_name' => __( 'Trip Name', 'advanced-form-integration' ),
            'full_name' => __( 'Full Name', 'advanced-form-integration' ),
            'email'     => __( 'Email', 'advanced-form-integration' ),
            'message'   => __( 'Message', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

add_action( 'wptravelengine_after_booking_created', 'adfoin_wte_handle_booking_created', 10, 1 );
add_action( 'wte_after_enquiry_created', 'adfoin_wte_handle_enquiry_created', 10, 1 );


/**
 * Handle WP Travel Engine "booking created" trigger.
 *
 * @param int $booking_id Booking ID.
 */
function adfoin_wte_handle_booking_created( $booking_id ) {
    adfoin_wte_process_trigger( 'booking_created', $booking_id );
}

/**
 * Handle WP Travel Engine "enquiry created" trigger.
 *
 * @param int $post_id Post ID.
 */
function adfoin_wte_handle_enquiry_created( $post_id ) {
    adfoin_wte_process_trigger( 'enquiry_created', $post_id );
}

/**
 * Common processor for WP Travel Engine triggers.
 *
 * @param string $trigger    Trigger key.
 * @param int    $id         ID of the object.
 */
function adfoin_wte_process_trigger( $trigger, $id ) {
    if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wptravelengine', $trigger );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = adfoin_wte_build_payload( $trigger, $id );
    if ( empty( $payload ) ) {
        return;
    }

    $integration->send( $saved_records, $payload );
}

/**
 * Build a normalized payload from WP Travel Engine objects.
 *
 * @param string $trigger Trigger key.
 * @param int    $id      ID of the object.
 * @return array<string,mixed>
 */
function adfoin_wte_build_payload( $trigger, $id ) {
    $payload = array();

    if ( 'booking_created' === $trigger ) {
        $booking = get_post( $id );
        $meta    = get_post_meta( $id );

        $trip_id   = isset( $meta['trip_id'][0] ) ? $meta['trip_id'][0] : '';
        $trip_name = $trip_id ? get_the_title( $trip_id ) : '';
        $pax       = isset( $meta['place_order_pax'][0] ) ? $meta['place_order_pax'][0] : '';

        $booking_meta = get_post_meta( $id, 'wte_booking_meta', true );
        $trip_price   = isset( $booking_meta['price'] ) ? $booking_meta['price'] : '';

        $customer_data = isset( $meta['place_order_customer'][0] ) ? maybe_unserialize( $meta['place_order_customer'][0] ) : array();

        $payload = array(
            'trip_name'  => $trip_name,
            'trip_price' => $trip_price,
            'pax'        => $pax,
            'full_name'  => $customer_data['fname'] . ' ' . $customer_data['lname'],
            'email'      => $customer_data['email'],
            'phone'      => $customer_data['phone'],
            'country'    => $customer_data['country'],
            'address'    => $customer_data['address'],
            'city'       => $customer_data['city'],
            'state'      => $customer_data['state'],
            'zip'        => $customer_data['zip'],
            'booking_id' => $id,
        );
    } elseif ( 'enquiry_created' === $trigger ) {
        $enquiry   = get_post( $id );
        $meta      = get_post_meta( $id );
        $trip_id   = isset( $meta['wp_travel_engine_post_id'][0] ) ? $meta['wp_travel_engine_post_id'][0] : '';
        $trip_name = $trip_id ? get_the_title( $trip_id ) : '';

        $payload = array(
            'trip_name' => $trip_name,
            'full_name' => isset( $meta['wp_travel_engine_name'][0] ) ? $meta['wp_travel_engine_name'][0] : '',
            'email'     => isset( $meta['wp_travel_engine_email'][0] ) ? $meta['wp_travel_engine_email'][0] : '',
            'message'   => $enquiry->post_content,
        );
    }

    return $payload;
}
