<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cart Abandonment Recovery for WooCommerce trigger — fires once a cart is
 * confirmed abandoned (after the site owner's configured cutoff time, via
 * the plugin's own cron sweep — not on every checkout-field autosave).
 *
 * Confirmed against the plugin's own source
 * (modules/cart-abandonment/classes/class-cartflows-ca-tracking.php):
 *
 *     do_action( 'wcf_ca_process_abandoned_order', $checkout_details );
 *
 * fires from update_order_status() only for carts whose `order_status` just
 * flipped from "normal" to "abandoned" — a one-time state transition per
 * cart, unlike the earlier `wcf_ca_after_save_abandonment_data` hook (fired
 * on every AJAX autosave while the customer is still actively typing).
 * $checkout_details is the DB row object: `email`, `cart_total`,
 * `cart_contents` (serialized WC cart snapshot), `other_fields` (serialized
 * array of the extra checkout fields captured — name, phone, addresses),
 * `checkout_id`, `coupon_code`.
 *
 * @link https://plugins.trac.wordpress.org/browser/woo-cart-abandonment-recovery/trunk/modules/cart-abandonment/classes/class-cartflows-ca-tracking.php
 */

add_action( 'plugins_loaded', 'adfoin_wooabandonedcart_register_hooks', 20 );

function adfoin_wooabandonedcart_register_hooks() {
    if ( ! defined( 'CARTFLOWS_CA_FILE' ) ) {
        return;
    }

    add_action( 'wcf_ca_process_abandoned_order', 'adfoin_wooabandonedcart_handle_abandoned', 10, 1 );
}

/**
 * Flatten the plugin's DB row (with two serialized columns) into a plain
 * associative array. Shared by the field list and the dispatch handler so
 * they can't drift apart.
 */
function adfoin_wooabandonedcart_normalize( $checkout_details ) {
    if ( ! $checkout_details ) {
        return array();
    }

    $other_fields = array();
    if ( isset( $checkout_details->other_fields ) ) {
        $unserialized = maybe_unserialize( $checkout_details->other_fields );
        if ( is_array( $unserialized ) ) {
            $other_fields = $unserialized;
        }
    }

    $cart_contents = array();
    if ( isset( $checkout_details->cart_contents ) ) {
        $unserialized = maybe_unserialize( $checkout_details->cart_contents );
        if ( is_array( $unserialized ) ) {
            $cart_contents = $unserialized;
        }
    }

    // Raw WC cart items carry full product object references that don't
    // serialize meaningfully for downstream integrations — flatten to a
    // simple "Name x Qty" summary instead.
    $cart_items_summary = array();
    foreach ( $cart_contents as $item ) {
        if ( ! is_array( $item ) || empty( $item['data'] ) || ! is_object( $item['data'] ) || ! method_exists( $item['data'], 'get_name' ) ) {
            continue;
        }
        $cart_items_summary[] = $item['data']->get_name() . ' x ' . ( isset( $item['quantity'] ) ? $item['quantity'] : 1 );
    }

    $get_other = function ( $key ) use ( $other_fields ) {
        return isset( $other_fields[ $key ] ) ? $other_fields[ $key ] : '';
    };

    return array(
        'email'               => isset( $checkout_details->email ) ? $checkout_details->email : '',
        'first_name'          => $get_other( 'wcf_first_name' ),
        'last_name'           => $get_other( 'wcf_last_name' ),
        'phone'               => $get_other( 'wcf_phone_number' ),
        'cart_total'          => isset( $checkout_details->cart_total ) ? $checkout_details->cart_total : '',
        'cart_items'          => implode( ', ', $cart_items_summary ),
        'coupon_code'         => isset( $checkout_details->coupon_code ) ? $checkout_details->coupon_code : '',
        'checkout_id'         => isset( $checkout_details->checkout_id ) ? $checkout_details->checkout_id : '',
        'billing_company'     => $get_other( 'wcf_billing_company' ),
        'billing_address_1'   => $get_other( 'wcf_billing_address_1' ),
        'billing_address_2'   => $get_other( 'wcf_billing_address_2' ),
        'billing_state'       => $get_other( 'wcf_billing_state' ),
        'billing_postcode'    => $get_other( 'wcf_billing_postcode' ),
        'shipping_first_name' => $get_other( 'wcf_shipping_first_name' ),
        'shipping_last_name'  => $get_other( 'wcf_shipping_last_name' ),
        'shipping_company'    => $get_other( 'wcf_shipping_company' ),
        'shipping_country'    => $get_other( 'wcf_shipping_country' ),
        'shipping_address_1'  => $get_other( 'wcf_shipping_address_1' ),
        'shipping_address_2'  => $get_other( 'wcf_shipping_address_2' ),
        'shipping_city'       => $get_other( 'wcf_shipping_city' ),
        'shipping_state'      => $get_other( 'wcf_shipping_state' ),
        'shipping_postcode'   => $get_other( 'wcf_shipping_postcode' ),
        'order_comments'      => $get_other( 'wcf_order_comments' ),
        'country'             => $get_other( 'wcf_country' ),
        'location'            => $get_other( 'wcf_location' ),
    );
}

// Get Cart Abandonment Recovery Triggers
function adfoin_wooabandonedcart_get_forms( $form_provider ) {
    if ( $form_provider !== 'wooabandonedcart' ) {
        return;
    }

    return array(
        'cartAbandoned' => __( 'Cart Abandoned', 'advanced-form-integration' ),
    );
}

// Get Cart Abandonment Recovery Fields
function adfoin_wooabandonedcart_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wooabandonedcart' || $form_id !== 'cartAbandoned' ) {
        return;
    }

    return array(
        'email'               => __( 'Email', 'advanced-form-integration' ),
        'first_name'          => __( 'First Name', 'advanced-form-integration' ),
        'last_name'           => __( 'Last Name', 'advanced-form-integration' ),
        'phone'               => __( 'Phone', 'advanced-form-integration' ),
        'cart_total'          => __( 'Cart Total', 'advanced-form-integration' ),
        'cart_items'          => __( 'Cart Items (Name x Qty, comma separated)', 'advanced-form-integration' ),
        'coupon_code'         => __( 'Recovery Coupon Code (if enabled)', 'advanced-form-integration' ),
        'checkout_id'         => __( 'Checkout Page ID', 'advanced-form-integration' ),
        'billing_company'     => __( 'Billing Company', 'advanced-form-integration' ),
        'billing_address_1'   => __( 'Billing Address 1', 'advanced-form-integration' ),
        'billing_address_2'   => __( 'Billing Address 2', 'advanced-form-integration' ),
        'billing_state'       => __( 'Billing State', 'advanced-form-integration' ),
        'billing_postcode'    => __( 'Billing Postcode', 'advanced-form-integration' ),
        'shipping_first_name' => __( 'Shipping First Name', 'advanced-form-integration' ),
        'shipping_last_name'  => __( 'Shipping Last Name', 'advanced-form-integration' ),
        'shipping_company'    => __( 'Shipping Company', 'advanced-form-integration' ),
        'shipping_country'    => __( 'Shipping Country', 'advanced-form-integration' ),
        'shipping_address_1'  => __( 'Shipping Address 1', 'advanced-form-integration' ),
        'shipping_address_2'  => __( 'Shipping Address 2', 'advanced-form-integration' ),
        'shipping_city'       => __( 'Shipping City', 'advanced-form-integration' ),
        'shipping_state'      => __( 'Shipping State', 'advanced-form-integration' ),
        'shipping_postcode'   => __( 'Shipping Postcode', 'advanced-form-integration' ),
        'order_comments'      => __( 'Order Comments', 'advanced-form-integration' ),
        'country'             => __( 'Country', 'advanced-form-integration' ),
        'location'            => __( 'Location (Country, City)', 'advanced-form-integration' ),
    );
}

// Handle Cart Abandoned
function adfoin_wooabandonedcart_handle_abandoned( $checkout_details ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wooabandonedcart', 'cartAbandoned' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_wooabandonedcart_normalize( $checkout_details );

    if ( empty( $posted_data['email'] ) ) {
        return;
    }

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
