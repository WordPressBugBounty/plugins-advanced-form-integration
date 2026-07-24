<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * YITH WooCommerce Wishlist trigger — fires when a visitor adds a product to
 * a wishlist.
 *
 * Confirmed against the plugin's own source
 * (includes/class-yith-wcwl-wishlists.php, add_item() method):
 *
 *     do_action( 'yith_wcwl_added_to_wishlist', $product_id, $wishlist_id, $user_id );
 *
 * fires right after the wishlist item is persisted, and is officially
 * documented in the plugin's own docblock ("Allows to fire some action when
 * a product has been added to a wishlist"). Works for logged-in users and
 * guests alike — guest wishlists are session-based, so $user_id is 0 in
 * that case and the email lookup below comes back empty (expected).
 *
 * @link https://github.com/yithemes/yith-woocommerce-wishlist/blob/master/includes/class-yith-wcwl-wishlists.php
 */

add_action( 'plugins_loaded', 'adfoin_yithwishlist_register_hooks', 20 );

function adfoin_yithwishlist_register_hooks() {
    if ( ! defined( 'YITH_WCWL' ) ) {
        return;
    }

    add_action( 'yith_wcwl_added_to_wishlist', 'adfoin_yithwishlist_handle_added', 10, 3 );
}

// Get YITH Wishlist Triggers
function adfoin_yithwishlist_get_forms( $form_provider ) {
    if ( $form_provider !== 'yithwishlist' ) {
        return;
    }

    return array(
        'productAddedToWishlist' => __( 'Product Added To Wishlist', 'advanced-form-integration' ),
    );
}

// Get YITH Wishlist Fields
function adfoin_yithwishlist_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'yithwishlist' || $form_id !== 'productAddedToWishlist' ) {
        return;
    }

    return array(
        'product_id'   => __( 'Product ID', 'advanced-form-integration' ),
        'product_name' => __( 'Product Name', 'advanced-form-integration' ),
        'product_url'  => __( 'Product URL', 'advanced-form-integration' ),
        'wishlist_id'  => __( 'Wishlist ID', 'advanced-form-integration' ),
        'user_id'      => __( 'User ID (0 for guests)', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email (only for logged-in users)', 'advanced-form-integration' ),
    );
}

// Handle Product Added To Wishlist
function adfoin_yithwishlist_handle_added( $product_id, $wishlist_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'yithwishlist', 'productAddedToWishlist' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
    $user    = $user_id ? get_userdata( $user_id ) : false;

    $posted_data = array(
        'product_id'   => $product_id,
        'product_name' => $product ? $product->get_name() : '',
        'product_url'  => $product_id ? get_permalink( $product_id ) : '',
        'wishlist_id'  => $wishlist_id,
        'user_id'      => $user_id,
        'user_email'   => $user ? $user->user_email : '',
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
