<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPC Smart Wishlist for WooCommerce trigger — fires when a visitor adds a
 * product to their wishlist.
 *
 * Confirmed against the plugin's own source (wpc-smart-wishlist.php,
 * ajax_add() method):
 *
 *     do_action( 'woosw_add', $product_id, $key );
 *
 * fires unconditionally at the end of the add-to-wishlist AJAX handler
 * (including the failure path, where $product_id is reset to 0 — guarded
 * against below). $key is an opaque wishlist token, not a user ID directly
 * — for a logged-in visitor it's stored as their `woosw_key` user meta
 * (Woosw_Helper::get_key()), so the owning user is resolved with a reverse
 * meta lookup; for guests (or if "Disable for unauthenticated" is off) it's
 * a cookie value with no owning WP user, so the email/user fields are
 * empty — expected, not a bug.
 *
 * @link https://plugins.trac.wordpress.org/browser/woo-smart-wishlist/trunk/wpc-smart-wishlist.php
 */

add_action( 'plugins_loaded', 'adfoin_wpcsmartwishlist_register_hooks', 20 );

function adfoin_wpcsmartwishlist_register_hooks() {
    if ( ! defined( 'WOOSW_VERSION' ) ) {
        return;
    }

    add_action( 'woosw_add', 'adfoin_wpcsmartwishlist_handle_added', 10, 2 );
}

// Get WPC Smart Wishlist Triggers
function adfoin_wpcsmartwishlist_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpcsmartwishlist' ) {
        return;
    }

    return array(
        'productAddedToWishlist' => __( 'Product Added To Wishlist', 'advanced-form-integration' ),
    );
}

// Get WPC Smart Wishlist Fields
function adfoin_wpcsmartwishlist_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpcsmartwishlist' || $form_id !== 'productAddedToWishlist' ) {
        return;
    }

    return array(
        'product_id'   => __( 'Product ID', 'advanced-form-integration' ),
        'product_name' => __( 'Product Name', 'advanced-form-integration' ),
        'product_url'  => __( 'Product URL', 'advanced-form-integration' ),
        'user_id'      => __( 'User ID (only for logged-in users)', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email (only for logged-in users)', 'advanced-form-integration' ),
    );
}

// Handle Product Added To Wishlist
function adfoin_wpcsmartwishlist_handle_added( $product_id, $key ) {
    if ( empty( $product_id ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpcsmartwishlist', 'productAddedToWishlist' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

    $user_id    = '';
    $user_email = '';

    if ( $key ) {
        $owners = get_users( array(
            'meta_key'   => 'woosw_key',
            'meta_value' => $key,
            'number'     => 1,
            'fields'     => array( 'ID', 'user_email' ),
        ) );

        if ( ! empty( $owners ) ) {
            $user_id    = $owners[0]->ID;
            $user_email = $owners[0]->user_email;
        }
    }

    $posted_data = array(
        'product_id'   => $product_id,
        'product_name' => $product ? $product->get_name() : '',
        'product_url'  => get_permalink( $product_id ),
        'user_id'      => $user_id,
        'user_email'   => $user_email,
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
