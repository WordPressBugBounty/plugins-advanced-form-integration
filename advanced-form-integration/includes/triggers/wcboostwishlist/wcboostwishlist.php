<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WCBoost – Wishlist trigger — fires when a visitor adds a product to their
 * wishlist.
 *
 * Confirmed against the plugin's own source (includes/wishlist.php,
 * add_item() method):
 *
 *     do_action( 'wcboost_wishlist_add_item', $item );
 *
 * fires only after the item has been persisted (`$item->save()` /
 * `$this->save()` already ran above), and is explicitly skipped during
 * initial data-store loading (`! $this->get_data_store()->is_reading()`),
 * so it only fires for a genuine new add, not every time a wishlist is
 * read from the database. $item is a WCBoost\Wishlist\WishlistItem
 * (WC_Data-based) object; the owning Wishlist (and its get_user_id()) is
 * loaded separately via $item->get_wishlist_id(), following the same
 * WC_Data CRUD pattern WooCommerce itself uses.
 *
 * @link https://plugins.trac.wordpress.org/browser/wcboost-wishlist/trunk/includes/wishlist.php
 * @link https://plugins.trac.wordpress.org/browser/wcboost-wishlist/trunk/includes/wishlist-item.php
 */

add_action( 'plugins_loaded', 'adfoin_wcboostwishlist_register_hooks', 20 );

function adfoin_wcboostwishlist_register_hooks() {
    if ( ! defined( 'WCBOOST_WISHLIST_VERSION' ) ) {
        return;
    }

    add_action( 'wcboost_wishlist_add_item', 'adfoin_wcboostwishlist_handle_added', 10, 1 );
}

// Get WCBoost Wishlist Triggers
function adfoin_wcboostwishlist_get_forms( $form_provider ) {
    if ( $form_provider !== 'wcboostwishlist' ) {
        return;
    }

    return array(
        'productAddedToWishlist' => __( 'Product Added To Wishlist', 'advanced-form-integration' ),
    );
}

// Get WCBoost Wishlist Fields
function adfoin_wcboostwishlist_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wcboostwishlist' || $form_id !== 'productAddedToWishlist' ) {
        return;
    }

    return array(
        'product_id'  => __( 'Product ID', 'advanced-form-integration' ),
        'product_name' => __( 'Product Name', 'advanced-form-integration' ),
        'product_url' => __( 'Product URL', 'advanced-form-integration' ),
        'wishlist_id' => __( 'Wishlist ID', 'advanced-form-integration' ),
        'user_id'     => __( 'User ID (0 for guests)', 'advanced-form-integration' ),
        'user_email'  => __( 'User Email (only for logged-in users)', 'advanced-form-integration' ),
    );
}

// Handle Product Added To Wishlist
function adfoin_wcboostwishlist_handle_added( $item ) {
    if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wcboostwishlist', 'productAddedToWishlist' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $product_id  = $item->get_product_id();
    $wishlist_id = $item->get_wishlist_id();

    $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

    $user_id    = 0;
    $user_email = '';

    if ( $wishlist_id && class_exists( '\WCBoost\Wishlist\Wishlist' ) ) {
        $wishlist = new \WCBoost\Wishlist\Wishlist( $wishlist_id );
        $user_id  = method_exists( $wishlist, 'get_user_id' ) ? $wishlist->get_user_id() : 0;

        if ( $user_id ) {
            $user = get_userdata( $user_id );

            if ( $user ) {
                $user_email = $user->user_email;
            }
        }
    }

    $posted_data = array(
        'product_id'   => $product_id,
        'product_name' => $product ? $product->get_name() : '',
        'product_url'  => $product_id ? get_permalink( $product_id ) : '',
        'wishlist_id'  => $wishlist_id,
        'user_id'      => $user_id,
        'user_email'   => $user_email,
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
