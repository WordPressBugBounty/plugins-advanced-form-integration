<?php

add_filter( 'adfoin_form_providers', 'adfoin_woocommerce_add_provider' );

function adfoin_woocommerce_add_provider( $providers ) {

    // class_exists() is always available (unlike is_plugin_active(), which only
    // exists once wp-admin/includes/plugin.php is loaded) and is the idiomatic
    // "is WooCommerce active" check.
    if ( class_exists( 'WooCommerce' ) ) {
        $providers['woocommerce'] = __( 'WooCommerce', 'advanced-form-integration' );
    }

    return $providers;
}

/**
 * Single source of truth for the WooCommerce trigger list.
 * Used by adfoin_woocommerce_get_forms() and adfoin_woocommerce_get_form_name().
 */
function adfoin_woocommerce_get_triggers() {
    return array(
        '1' => __( 'All New order', 'advanced-form-integration' ),
        '2' => __( 'Order Status Processing', 'advanced-form-integration' ),
        '3' => __( 'Order Status On-Hold', 'advanced-form-integration' ),
        '4' => __( 'Order Status Completed', 'advanced-form-integration' ),
        '5' => __( 'Order Status Failed', 'advanced-form-integration' ),
        '6' => __( 'Order Status Pending', 'advanced-form-integration' ),
        '7' => __( 'Order Status Refunded', 'advanced-form-integration' ),
        '8' => __( 'Order Status Cancelled', 'advanced-form-integration' ),
        '9' => __( 'Subscription Created', 'advanced-form-integration' ),
        '10' => __( 'Subscription Cancelled', 'advanced-form-integration' ),
        '11' => __( 'Subscription Expired', 'advanced-form-integration' ),
        // '12' => __( 'Subscription Updated', 'advanced-form-integration' ),
        '13' => __( 'Subscription Trial Ended', 'advanced-form-integration' ),
        '14' => __( 'Product Stock Status Changed', 'advanced-form-integration' ),
        // Fires on ANY status transition, including custom/non-standard statuses
        // added by other plugins. Use the Status To / Status From fields (or
        // conditional logic on them) to target a specific status.
        '15' => __( 'Order Status Changed (any/custom status)', 'advanced-form-integration' ),
    );
}

function adfoin_woocommerce_get_forms( $form_provider ) {

    if ( $form_provider != 'woocommerce' ) {
        return;
    }

    $triggers = adfoin_woocommerce_get_triggers();

    return $triggers;
}

function adfoin_woocommerce_get_form_fields( $form_provider, $form_id ) {



    if ( $form_provider != 'woocommerce' ) {

        return;

    }



    $fields = array();

    $subscription_indexes = array( '9', '10', '11', '12', '13' );



    if ( in_array( $form_id, $subscription_indexes ) ) {

        $fields = adfoin_get_woocommerce_subscription_fields();

    } elseif ( '14' === $form_id ) {

        $fields = adfoin_get_woocommerce_stock_fields();

    } else {

        $fields = adfoin_get_woocommerce_order_fields();

        // Surface custom checkout fields we've seen on past orders so users can
        // point-and-click map them instead of hand-typing {{key}}. These are
        // intentionally NOT added to adfoin_get_woocommerce_order_fields() so the
        // checkout-field capture keeps treating them as custom.
        $seen = adfoin_woocommerce_get_seen_custom_fields();

        foreach ( $seen as $seen_key => $seen_label ) {
            if ( ! isset( $fields[ $seen_key ] ) ) {
                $fields[ $seen_key ] = $seen_label;
            }
        }

    }

    return apply_filters( 'adfoin_woocommerce_form_fields', $fields, $form_id );

}

/**
 * Custom checkout-field keys observed on previous orders, exposed as mappable
 * fields. Keys are recorded by adfoin_woocommerce_save_checkout_fields() each
 * time a checkout posts a field we don't already know about.
 *
 * @return array key => label (label is the key itself).
 */
function adfoin_woocommerce_get_seen_custom_fields() {
    $known  = (array) maybe_unserialize( get_option( 'adfoin_wc_seen_checkout_keys', array() ) );
    $fields = array();

    foreach ( $known as $key ) {
        $key = (string) $key;
        if ( '' !== $key ) {
            $fields[ $key ] = $key;
        }
    }

    return $fields;
}

/**
 * Remember custom checkout-field keys so they can be offered in the mapping UI.
 * Capped and write-on-change to keep this cheap on the checkout path.
 */
function adfoin_woocommerce_remember_custom_keys( $keys ) {
    if ( empty( $keys ) || ! is_array( $keys ) ) {
        return;
    }

    $known   = (array) maybe_unserialize( get_option( 'adfoin_wc_seen_checkout_keys', array() ) );
    $skip    = array( 'coupons_applied', 'coupons_amount_total' );
    $changed = false;

    foreach ( $keys as $key ) {
        $key = (string) $key;

        if ( '' === $key || in_array( $key, $skip, true ) || in_array( $key, $known, true ) ) {
            continue;
        }

        if ( count( $known ) >= 200 ) { // hard cap; avoid unbounded growth.
            break;
        }

        $known[] = $key;
        $changed = true;
    }

    if ( $changed ) {
        update_option( 'adfoin_wc_seen_checkout_keys', maybe_serialize( $known ), false );
    }
}



function adfoin_get_woocommerce_stock_fields() {

    return array(

        'product_id'     => __( 'Product ID', 'advanced-form-integration' ),

        'product_name'   => __( 'Product Name', 'advanced-form-integration' ),

        'product_sku'    => __( 'Product SKU', 'advanced-form-integration' ),

        'product_url'    => __( 'Product URL', 'advanced-form-integration' ),

        'stock_status'   => __( 'Stock Status', 'advanced-form-integration' ),

        'stock_quantity' => __( 'Stock Quantity', 'advanced-form-integration' ),

    );

}



function adfoin_get_woocommerce_customer_fields() {
    $fields = array(
        'customer_id'                 => __( 'Customer ID', 'advanced-form-integration' ),
        'customer_ip_address'         => __( 'Customer IP Address', 'advanced-form-integration' ),
        'customer_user_agent'         => __( 'Customer User Agent', 'advanced-form-integration' ),
        'customer_note'               => __( 'Customer Note', 'advanced-form-integration' ),
        'billing_first_name'          => __( 'Billing First Name', 'advanced-form-integration' ),
        'billing_last_name'           => __( 'Billing Last Name', 'advanced-form-integration' ),
        'formatted_billing_full_name' => __( 'Formatted Billing Full Name', 'advanced-form-integration' ),
        'billing_company'             => __( 'Billing Company', 'advanced-form-integration' ),
        'billing_address_1'           => __( 'Billing Address 1', 'advanced-form-integration' ),
        'billing_address_2'           => __( 'Billing Address 2', 'advanced-form-integration' ),
        'billing_city'                => __( 'Billing City', 'advanced-form-integration' ),
        'billing_state'               => __( 'Billing State', 'advanced-form-integration' ),
        'billing_state_full'          => __( 'Billing State Full Name', 'advanced-form-integration' ),
        'billing_postcode'            => __( 'Billing Postcode', 'advanced-form-integration' ),
        'billing_country'             => __( 'Billing Country', 'advanced-form-integration' ),
        'billing_email'               => __( 'Billing Email', 'advanced-form-integration' ),
        'billing_phone'               => __( 'Billing Phone', 'advanced-form-integration' ),
        'formatted_billing_address'   => __( 'Formatted Billing Address', 'advanced-form-integration' ),
        'shipping_first_name'         => __( 'Shipping First Name', 'advanced-form-integration' ),
        'shipping_last_name'          => __( 'Shipping Last Name', 'advanced-form-integration' ),
        'shipping_full_name'          => __( 'Shipping Full Name', 'advanced-form-integration' ),
        'shipping_company'            => __( 'Shipping Company', 'advanced-form-integration' ),
        'shipping_address_1'          => __( 'Shipping Address 1', 'advanced-form-integration' ),
        'shipping_address_2'          => __( 'Shipping Address 2', 'advanced-form-integration' ),
        'shipping_city'               => __( 'Shipping City', 'advanced-form-integration' ),
        'shipping_state'              => __( 'Shipping State', 'advanced-form-integration' ),
        'shipping_state_full'         => __( 'Shipping State Full Name', 'advanced-form-integration' ),
        'shipping_postcode'           => __( 'Shipping Postcode', 'advanced-form-integration' ),
        'shipping_country'            => __( 'Shipping Country', 'advanced-form-integration' ),
        'shipping_email'              => __( 'Shipping Email', 'advanced-form-integration' ),
        'shipping_phone'              => __( 'Shipping Phone', 'advanced-form-integration' ),
        'formatted_shipping_address'  => __( 'Formatted Shipping Address', 'advanced-form-integration' ),
        'shipping_address_map_url'    => __( 'Shipping Address Map URL', 'advanced-form-integration' ),
    );

    if( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
        $special_tags = adfoin_get_special_tags( 'utm' );
        $fields       = array_merge( $fields, $special_tags );
    }

    return $fields;
}

function adfoin_get_woocommerce_order_fields() {

    $fields = array(
        // order fields
        'id'                          => __( 'Order ID', 'advanced-form-integration' ),
        'order_number'                => __( 'Order Number', 'advanced-form-integration' ),
        'parent_id'                   => __( 'Parent ID', 'advanced-form-integration' ),
        'user_id'                     => __( 'User ID', 'advanced-form-integration' ),
        'payment_method'              => __( 'Payment Method', 'advanced-form-integration' ),
        'payment_method_title'        => __( 'Payment Method Title', 'advanced-form-integration' ),
        'transaction_id'              => __( 'Transaction ID', 'advanced-form-integration' ),
        'created_via'                 => __( 'Order Created Via', 'advanced-form-integration' ),
        'date_completed'              => __( 'Date Completed', 'advanced-form-integration' ),
        'date_created'                => __( 'Date Created', 'advanced-form-integration' ),
        'date_modified'               => __( 'Date Modified', 'advanced-form-integration' ),
        'date_paid'                   => __( 'Date Paid', 'advanced-form-integration' ),
        'cart_hash'                   => __( 'Cart Hash', 'advanced-form-integration' ),
        'currency'                    => __( 'Currency', 'advanced-form-integration' ),

        //item fields
        'total'                  => __( 'Total', 'advanced-form-integration' ),
        'formatted_order_total'  => __( 'Formatted Order Total', 'advanced-form-integration' ),
        'order_item_total'       => __( 'Order Item Total', 'advanced-form-integration' ),
        'prices_include_tax'     => __( 'Prices Include Tax', 'advanced-form-integration' ),
        'discount_total'         => __( 'Discount Total', 'advanced-form-integration' ),
        'discount_tax'           => __( 'Discount Tax', 'advanced-form-integration' ),
        'shipping_total'         => __( 'Shipping Total', 'advanced-form-integration' ),
        'shipping_tax'           => __( 'Shipping Tax', 'advanced-form-integration' ),
        'cart_tax'               => __( 'Cart Tax', 'advanced-form-integration' ),
        'total_tax'              => __( 'Total Tax', 'advanced-form-integration' ),
        'total_discount'         => __( 'Total Discount', 'advanced-form-integration' ),
        'subtotal'               => __( 'Subtotal', 'advanced-form-integration' ),
        'tax_totals'             => __( 'Tax Totals', 'advanced-form-integration' ),
        'items'                  => __( 'Items Full JSON', 'advanced-form-integration' ),
        'items_id'               => __( 'Line Item(s) ID', 'advanced-form-integration' ),
        'items_name'             => __( 'Line Item(s) Name', 'advanced-form-integration' ),
        'items_sku'              => __( 'Line Item(s) SKU', 'advanced-form-integration' ),
        'items_variation_id'     => __( 'Line Item(s) Variation ID', 'advanced-form-integration' ),
        'items_quantity'         => __( 'Line Item(s) Quantity', 'advanced-form-integration' ),
        'items_total'            => __( 'Line Item(s) Total', 'advanced-form-integration' ),
        'items_price'            => __( 'Line Item(s) Price', 'advanced-form-integration' ),
        'items_sale_price'       => __( 'Line Item(s) Sale Price', 'advanced-form-integration' ),
        'items_regular_price'    => __( 'Line Item(s) Regular Price', 'advanced-form-integration' ),
        'items_subtotal'         => __( 'Line Item(s) Subtotal', 'advanced-form-integration' ),
        'items_subtotal_tax'     => __( 'Line Item(s) Subtotal Tax', 'advanced-form-integration' ),
        'items_subtotal_with_tax' => __( 'Line Item(s) Subtotal With Tax', 'advanced-form-integration' ),
        'items_total_tax'         => __( 'Line Item(s) Total Tax', 'advanced-form-integration' ),
        'items_total_with_tax'    => __( 'Line Item(s) Total With Tax', 'advanced-form-integration' ),
        'items_number_in_cart'    => __( 'Line Item(s) Number In Cart', 'advanced-form-integration' ),
        'items_attributes'        => __( 'Line Item(s) Attributes', 'advanced-form-integration' ),

        'taxes'                  => __( 'Taxes', 'advanced-form-integration' ),
        'shipping_methods'       => __( 'Shipping Methods', 'advanced-form-integration' ),
        'shipping_method'        => __( 'Shipping Method', 'advanced-form-integration' ),
        'coupons_applied'        => __( 'Coupons Applied', 'advanced-form-integration' ),
        'coupons_amount_total'   => __( 'Coupons Amount Total', 'advanced-form-integration' ),
        'status'                 => __( 'Status', 'advanced-form-integration' ),
        // Populated only for the "Order Status Changed" trigger (#15).
        'status_from'            => __( 'Previous Status (on status change)', 'advanced-form-integration' ),
        'status_to'              => __( 'New Status (on status change)', 'advanced-form-integration' ),
    );

    $customer_fields = adfoin_get_woocommerce_customer_fields();
    $fields          = array_merge( $fields, $customer_fields );

    // Let add-ons / site code register extra order fields (custom checkout
    // fields, Product Add-Ons, Dokan vendor meta, etc.) so they appear in the
    // mapping dropdown. NOTE: keys added here are also treated as "known"
    // fields by the checkout-field capture, so register only fields you supply.
    return apply_filters( 'adfoin_woocommerce_order_fields', $fields );
}

function adfoin_get_woocommerce_subscription_fields() {

    $fields = array(
        // subscription fields
        'id'                => __( 'Subscription ID', 'advanced-form-integration' ),
        'user_id'           => __( 'User ID', 'advanced-form-integration' ),
        'status'            => __( 'Subscription Status', 'advanced-form-integration' ),
        'currency'          => __( 'Currency', 'advanced-form-integration' ),
        'billing_period'    => __( 'Billing Period', 'advanced-form-integration' ),
        'billing_interval'  => __( 'Billing Interval', 'advanced-form-integration' ),
        'trial_period'      => __( 'Trial Period', 'advanced-form-integration' ),
        'is_manual'    => __( 'Manual Renewal', 'advanced-form-integration' ),
        'sign_up_fee'        => __( 'Signup Fee', 'advanced-form-integration' ),
        'start'        => __( 'Subscription Start Date', 'advanced-form-integration' ),
        'end'          => __( 'Subscription End Date', 'advanced-form-integration' ),
        'trial_end'    => __( 'Trial End Date', 'advanced-form-integration' ),
        'last_payment' => __( 'Last Payement Date', 'advanced-form-integration' ),
        'next_payment' => __( 'Next Payment Date', 'advanced-form-integration' ),
        
        'order_key'         => __( 'Order Key', 'advanced-form-integration' ),
        'payment_method'              => __( 'Payment Method', 'advanced-form-integration' ),
        'payment_method_title'        => __( 'Payment Method Title', 'advanced-form-integration' ),
        'transaction_id'              => __( 'Transaction ID', 'advanced-form-integration' ),
        'created_via'                 => __( 'Order Created Via', 'advanced-form-integration' ),

        'total'                  => __( 'Total', 'advanced-form-integration' ),
        'formatted_order_total'  => __( 'Formatted Order Total', 'advanced-form-integration' ),
        'order_item_total'       => __( 'Order Item Total', 'advanced-form-integration' ),
        'prices_include_tax'     => __( 'Prices Include Tax', 'advanced-form-integration' ),
        'discount_total'         => __( 'Discount Total', 'advanced-form-integration' ),
        'discount_tax'           => __( 'Discount Tax', 'advanced-form-integration' ),
        'shipping_total'         => __( 'Shipping Total', 'advanced-form-integration' ),
        'shipping_tax'           => __( 'Shipping Tax', 'advanced-form-integration' ),
        'cart_tax'               => __( 'Cart Tax', 'advanced-form-integration' ),
        'total_tax'              => __( 'Total Tax', 'advanced-form-integration' ),
        'total_discount'         => __( 'Total Discount', 'advanced-form-integration' ),
        'subtotal'               => __( 'Subtotal', 'advanced-form-integration' ),
        'tax_totals'             => __( 'Tax Totals', 'advanced-form-integration' ),
        'items'                  => __( 'Items Full JSON', 'advanced-form-integration' ),
        'items_id'               => __( 'Line Item(s) ID', 'advanced-form-integration' ),
        'items_name'             => __( 'Line Item(s) Name', 'advanced-form-integration' ),
        'items_sku'              => __( 'Line Item(s) SKU', 'advanced-form-integration' ),
        'items_variation_id'     => __( 'Line Item(s) Variation ID', 'advanced-form-integration' ),
        'items_quantity'         => __( 'Line Item(s) Quantity', 'advanced-form-integration' ),
        'items_total'            => __( 'Line Item(s) Total', 'advanced-form-integration' ),
        'items_price'            => __( 'Line Item(s) Price', 'advanced-form-integration' ),
        'items_sale_price'       => __( 'Line Item(s) Sale Price', 'advanced-form-integration' ),
        'items_regular_price'    => __( 'Line Item(s) Regular Price', 'advanced-form-integration' ),
        'items_subtotal'         => __( 'Line Item(s) Subtotal', 'advanced-form-integration' ),
        'items_subtotal_tax'     => __( 'Line Item(s) Subtotal Tax', 'advanced-form-integration' ),
        'items_subtotal_with_tax' => __( 'Line Item(s) Subtotal With Tax', 'advanced-form-integration' ),
        'items_total_tax'         => __( 'Line Item(s) Total Tax', 'advanced-form-integration' ),
        'items_total_with_tax'    => __( 'Line Item(s) Total With Tax', 'advanced-form-integration' ),
    );

    $customer_fields = adfoin_get_woocommerce_customer_fields();

    $fields = array_merge( $fields, $customer_fields );

    return $fields;
}

function adfoin_woocommerce_get_form_name( $form_provider, $form_id ) {

    if ( $form_provider != 'woocommerce' ) {
        return;
    }

    $triggers = adfoin_woocommerce_get_triggers();

    if( $form_id ) {
        return $triggers[$form_id];
    }

    return false;
}

// Save WooCommerce POST fields
add_action('woocommerce_checkout_update_order_meta', 'adfoin_woocommerce_save_checkout_fields', 10, 2);

function adfoin_woocommerce_save_checkout_fields( $order_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce' );

    if( empty( $saved_records ) ) {
        return;
    }

    $fields                = adfoin_get_woocommerce_order_fields();
    $field_keys            = array_keys( $fields );
    $filtered              = array();

    if( isset( $_POST ) && is_array( $_POST ) ) {
        foreach( $_POST as $key => $value ) {
            if( is_string( $value ) && ! in_array( $key, $field_keys ) ) {
                $filtered[$key] = adfoin_sanitize_text_or_array_field( $value );
            }
        }
    }

    // Remember the custom field keys so they can be offered for point-and-click
    // mapping next time (fixes "I can't find my custom checkout field").
    adfoin_woocommerce_remember_custom_keys( array_keys( $filtered ) );

    $applied_coupons = WC()->cart->get_applied_coupons();

    if( $applied_coupons ) {
        $filtered['coupons_applied'] = implode( $applied_coupons );

        $amounts = array();

        foreach( $applied_coupons  as $coupon ) {
            $amounts[] = WC()->cart->get_coupon_discount_amount( $coupon, false );
        }

        $coupon_total_amount = array_sum( $amounts );

        $filtered['coupons_amount_total'] = $coupon_total_amount;

    }

    // Store against this specific order instead of a shared site-wide option,
    // so one checkout's fields can't bleed onto (or be wiped by) a concurrent
    // order. Falls back to the legacy option only if the order isn't retrievable.
    $order = wc_get_order( $order_id );

    if ( $order instanceof WC_Order ) {
        $order->update_meta_data( '_adfoin_wc_checkout_fields', maybe_serialize( $filtered ) );
        $order->save();
    } else {
        update_option( 'adfoin_wc_checkout_fields', maybe_serialize( $filtered ) );
    }

    return;
}

add_action( 'woocommerce_new_order', 'adfoin_woocommerce_after_admin_order', 10, 2 );

function adfoin_woocommerce_after_admin_order( $order_id ) {
    if( !$order_id ) {
        return;
    }
    $order = wc_get_order( $order_id );
    $via = $order->get_created_via();
    if( !($via == 'admin' || $via == 'rest-api') ) {
        return;
    }

    $order = apply_filters( 'adfoin_woocommerce_after_admin_order', $order );

    adfoin_woocommerce_after_submission( $order, 1 );
}

add_action( 'woocommerce_checkout_order_created', 'adfoin_woocommerce_after_checkout_order', 10, 1 );
function adfoin_woocommerce_after_checkout_order( $order ) {
    adfoin_woocommerce_after_submission( $order, 1 );
}

add_action( 'woocommerce_order_status_processing', 'adfoin_woocommerce_order_status_processing', 10, 2 );

function adfoin_woocommerce_order_status_processing( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 2 );

}

add_action( 'woocommerce_order_status_on-hold', 'adfoin_woocommerce_order_status_onhold', 10, 2 );

function adfoin_woocommerce_order_status_onhold( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 3 );

}

add_action( 'woocommerce_order_status_completed', 'adfoin_woocommerce_order_status_completed', 10, 2 );

function adfoin_woocommerce_order_status_completed( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 4 );

}

add_action( 'woocommerce_order_status_failed', 'adfoin_woocommerce_order_status_failed', 10, 2 );

function adfoin_woocommerce_order_status_failed( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 5 );

}

add_action( 'woocommerce_order_status_pending', 'adfoin_woocommerce_order_status_pending', 10, 2 );

function adfoin_woocommerce_order_status_pending( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 6 );

}

add_action( 'woocommerce_order_status_refunded', 'adfoin_woocommerce_order_status_refunded', 10, 2 );

function adfoin_woocommerce_order_status_refunded( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 7 );

}

add_action( 'woocommerce_order_status_cancelled', 'adfoin_woocommerce_order_status_cancelled', 10, 2 );

function adfoin_woocommerce_order_status_cancelled( $order_id, $order = null ) {
    if( !$order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    adfoin_woocommerce_after_submission( $order, 8 );

}

// NOTE: woocommerce_subscription_payment_complete fires on the INITIAL payment
// AND every renewal payment, so the "Subscription Created" trigger (#9) also
// fires on renewals. This is long-standing behaviour and some integrations may
// rely on it, so it is intentionally left unchanged. If a creation-only trigger
// is ever wanted, hook woocommerce_checkout_subscription_created instead (note
// that only covers checkout-created subscriptions, not admin/API-created ones),
// or gate on $subscription->get_payment_count() === 1.
add_action( 'woocommerce_subscription_payment_complete', 'adfoin_woocommerce_subscription_created' );

function adfoin_woocommerce_subscription_created( $subscription ) {
  
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce', 9 );

    if ( empty($saved_records) ) {
        return;
    }

    adfoin_woocommerce_send_subscription_data( $subscription, $saved_records );

}

add_action( 'woocommerce_subscription_status_cancelled', 'adfoin_woocommerce_subscription_status_cancelled' );

function adfoin_woocommerce_subscription_status_cancelled( $subscription ) {
  
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce', 10 );

    if ( empty($saved_records) ) {
        return;
    }

    adfoin_woocommerce_send_subscription_data( $subscription, $saved_records );

}

add_action( 'woocommerce_subscription_status_expired', 'adfoin_woocommerce_subscription_status_expired' );

function adfoin_woocommerce_subscription_status_expired( $subscription ) {
  
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce', 11 );

    if ( empty($saved_records) ) {
        return;
    }

    adfoin_woocommerce_send_subscription_data( $subscription, $saved_records );

}

add_action( 'woocommerce_scheduled_subscription_trial_end', 'adfoin_woocommerce_subscription_trial_end' );

function adfoin_woocommerce_subscription_trial_end( $subscription_id ) {
  
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce', 13 );

    if ( empty($saved_records) ) {
        return;
    }

    if ( !function_exists( 'wcs_get_subscription' ) ) {
        return;
    }

    $subscription = wcs_get_subscription( $subscription_id );

    adfoin_woocommerce_send_subscription_data( $subscription, $saved_records );

}

add_action( 'woocommerce_product_set_stock_status', 'adfoin_woocommerce_handle_stock_status_change', 10, 3 );

function adfoin_woocommerce_handle_stock_status_change( $product_id, $stock_status, $product ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce', 14 );

    if ( empty( $saved_records ) ) {
        return;
    }

    adfoin_woocommerce_send_stock_data( $product, $stock_status, $saved_records );
}

function adfoin_woocommerce_send_stock_data( $product, $stock_status, $saved_records ) {
    if ( ! is_a( $product, 'WC_Product' ) ) {
        return;
    }

    $posted_data = array(
        'product_id'     => $product->get_id(),
        'product_name'   => $product->get_name(),
        'product_sku'    => $product->get_sku(),
        'product_url'    => $product->get_permalink(),
        'stock_status'   => $stock_status,
        'stock_quantity' => $product->get_stock_quantity(),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

/**
 * Build the base posted_data shared by orders and subscriptions: the
 * order-field loop (with tax_totals / shipping_methods / taxes special-casing)
 * plus billing/shipping state-full names.
 *
 * @param WC_Abstract_Order $object       Order or subscription object.
 * @param bool              $format_dates Orders format date_created/modified/completed
 *                                        to 'Y-m-d H:i:s'; subscriptions keep the raw value.
 */
function adfoin_woocommerce_build_base_posted_data( $object, $format_dates = false ) {
    $posted_data = array();
    $field_keys  = array_keys( adfoin_get_woocommerce_order_fields() );

    foreach ( $field_keys as $key ) {
        if ( method_exists( $object, 'get_' . $key ) ) {
            $result            = call_user_func( array( $object, 'get_' . $key ) );
            $posted_data[$key] = $result;

            if ( $format_dates && 'date_created' == $key ) {
                $posted_data['date_created'] = $object->get_date_created() !== null ? date( 'Y-m-d H:i:s', $object->get_date_created()->getOffsetTimestamp() ) : '';
            }

            if ( $format_dates && 'date_modified' == $key ) {
                $posted_data['date_modified'] = $object->get_date_modified() !== null ? date( 'Y-m-d H:i:s', $object->get_date_modified()->getOffsetTimestamp() ) : '';
            }

            if ( $format_dates && 'date_completed' == $key ) {
                $posted_data['date_completed'] = $object->get_date_completed() !== null ? date( 'Y-m-d H:i:s', $object->get_date_completed()->getOffsetTimestamp() ) : '';
            }

            if ( 'tax_totals' == $key ) {
                $posted_data['tax_totals'] = wp_json_encode( $object->get_tax_totals() );
            }

            if ( 'shipping_methods' == $key ) {
                $shipping_methods = $object->get_shipping_methods();
                $methods_data     = array();

                if ( is_array( $shipping_methods ) ) {
                    foreach ( $shipping_methods as $single_method ) {
                        $methods_data[] = $single_method->get_data();
                    }

                    $posted_data['shipping_methods'] = wp_json_encode( $methods_data );
                }
            }

            if ( 'taxes' == $key ) {
                $taxes      = $object->get_taxes();
                $taxes_data = array();

                if ( is_array( $taxes ) ) {
                    foreach ( $taxes as $single_tax ) {
                        $taxes_data[] = $single_tax->get_data();
                    }

                    $posted_data['taxes'] = wp_json_encode( $taxes_data );
                }
            }
        }
    }

    if ( isset( $posted_data['billing_state'] ) && $posted_data['billing_state'] ) {
        $posted_data['billing_state_full'] = adfoin_woocommerce_get_full_state( $object, 'billing' );
    }

    if ( isset( $posted_data['shipping_state'] ) && $posted_data['shipping_state'] ) {
        $posted_data['shipping_state_full'] = adfoin_woocommerce_get_full_state( $object, 'shipping' );
    }

    return $posted_data;
}

/**
 * Build per-line item data shared by orders and subscriptions.
 *
 * @param array $items                WC order/subscription line items.
 * @param array $saved_records        Records (used to resolve mapped item-meta tags).
 * @param bool  $with_number_in_cart  Orders include items_number_in_cart; subscriptions don't.
 * @return array Keyed by 1-based line number.
 */
function adfoin_woocommerce_build_item_data( $items, $saved_records, $with_number_in_cart = false ) {
    $item_data = array();

    if ( ! is_array( $items ) ) {
        return $item_data;
    }

    $line = 1;

    // Invariant across the item loop — resolve the mapped meta keys once.
    $item_metas = adfoin_woocommerce_get_meta_tags( $saved_records, 'item' );

    foreach ( $items as $item ) {
        $item_data[$line]['items_id'] = $item->get_product_id();
        $item_data[$line]['items_name'] = $item->get_name();
        $item_data[$line]['items_variation_id'] = $item->get_variation_id();
        $item_data[$line]['items_quantity'] = $item->get_quantity();
        $item_data[$line]['items_subtotal'] = $item->get_subtotal();
        $item_data[$line]['items_subtotal_tax'] = $item->get_subtotal_tax();
        $item_data[$line]['items_subtotal_with_tax'] = $item->get_subtotal() + $item->get_subtotal_tax();
        $item_data[$line]['items_total_tax'] = $item->get_total_tax();
        $item_data[$line]['items_total_with_tax'] = $item->get_total_tax() + $item->get_total();
        $item_data[$line]['items_total'] = $item->get_total();

        if ( $with_number_in_cart ) {
            $item_data[$line]['items_number_in_cart'] = $line;
        }

        $item_data[$line]['items'] = $item->get_data();
        // Encode this line's item data (was $item_data['items'] — an undefined
        // key that always JSON-encoded to the string "null").
        $item_data[$line]['items'] = wp_json_encode( $item_data[$line]['items'] );

        if ( $item->get_variation_id() ) {
            $product = wc_get_product( $item->get_variation_id() );
        } else {
            $product = wc_get_product( $item->get_product_id() );
        }

        // Products can be deleted after an order is placed, in which case
        // wc_get_product() returns false. Guard so we degrade to empty
        // values instead of fataling on the whole order.
        if ( $product instanceof WC_Product ) {
            $item_data[$line]['items_sku'] = $product->get_sku();
            $item_data[$line]['items_price'] = $product->get_price();
            $item_data[$line]['items_sale_price'] = $product->get_sale_price();
            $item_data[$line]['items_regular_price'] = $product->get_regular_price();
        } else {
            $item_data[$line]['items_sku'] = '';
            $item_data[$line]['items_price'] = '';
            $item_data[$line]['items_sale_price'] = '';
            $item_data[$line]['items_regular_price'] = '';
        }

        $variation_id = $item->get_variation_id();
        // Initialise for every item so simple products carry the key too;
        // otherwise the $merged_items builder reads an undefined index.
        $item_data[$line]['items_attributes'] = '';

        if ( $variation_id ) {
            $variation = new WC_Product_Variation( $variation_id );
            $attributes = $variation->get_attributes();
            $item_data[$line]['items_attributes'] = implode( ',', $attributes );
        }

        foreach ( $item_metas as $item_meta ) {
            $meta_tag   = str_replace( 'itemmeta_', '', $item_meta );
            $item_id = $item->get_id();
            $meta_value = wc_get_order_item_meta( (int) $item_id, $meta_tag );
            $item_data[$line][$item_meta] = $meta_value;
        }

        $line++;
    }

    return $item_data;
}

/**
 * Collapse per-line $item_data into column arrays (items_id => array, etc.) so
 * each mapped item field carries every line's value.
 */
function adfoin_woocommerce_build_merged_items( $item_data ) {
    $merged_items = array();

    if ( ! empty( $item_data ) && is_array( $item_data ) ) {
        $item_keys = array_keys( array_merge( ...$item_data ) );

        foreach ( $item_data as $item ) {
            foreach ( $item_keys as $key ) {
                if ( ! isset( $merged_items[$key] ) ) {
                    $merged_items[$key] = array();
                }

                $merged_items[$key][] = $item[$key];
            }
        }
    }

    return $merged_items;
}

/**
 * Dispatch records. The Pro multi-row branch (googlesheetspro / googlecalendar
 * with the "wcMultipleRow" box) sends one queue job per $item_data row;
 * everything else dispatches a single job. The dispatch helper accepts batches,
 * so single-record calls wrap $record in array().
 */
function adfoin_woocommerce_dispatch_records( $saved_records, $posted_data, $item_data ) {
    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( ! function_exists( "adfoin_{$action_provider}_send_data" ) ) {
            continue;
        }

        // "One record per line item": dispatch the integration once per order
        // line item instead of once per order. Previously limited to
        // googlesheetspro / googlecalendar; now available to EVERY platform so
        // multi-product orders register each product (fixes e.g. WebinarJam /
        // Sendy line-item cases). Still a Professional feature, still opt-in via
        // the wcMultipleRow flag. Google Sheets PRO also uses this flag for its
        // own row-expansion, so its behaviour is unchanged.
        if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
            $record_data  = json_decode( $record['data'], true );
            $field_data   = ( is_array( $record_data ) && isset( $record_data['field_data'] ) ) ? $record_data['field_data'] : array();
            $wc_multi_row = ( isset( $field_data['wcMultipleRow'] ) && 'true' == $field_data['wcMultipleRow'] );

            if ( $wc_multi_row && is_array( $item_data ) && ! empty( $item_data ) ) {
                foreach ( $item_data as $item ) {
                    $single_row = $item + $posted_data;
                    adfoin_woocommerce_safe_dispatch( $record, $single_row );
                }

                continue;
            }
        }

        adfoin_woocommerce_safe_dispatch( $record, $posted_data );
    }
}

/**
 * Dispatch a single record, never letting a platform-side error bubble up into
 * the WooCommerce checkout/order flow. With the job queue on (forced for
 * WooCommerce — see adfoin_woocommerce_force_queue) the heavy API call already
 * runs out-of-band; this try/catch protects the synchronous fallback so a buggy
 * integration can't block checkout completion.
 */
function adfoin_woocommerce_safe_dispatch( $record, $posted_data ) {
    try {
        adfoin_dispatch_integrations( array( $record ), $posted_data );
    } catch ( \Throwable $e ) {
        adfoin_add_to_log(
            new WP_Error( 'woocommerce_dispatch_exception', $e->getMessage() ),
            '',
            array(),
            $record
        );
    }
}

/**
 * Run WooCommerce integrations through Action Scheduler by default. WooCommerce
 * bundles Action Scheduler, so order/checkout integrations move off the
 * checkout request — keeping checkout fast and ensuring a slow or failing API
 * call can never block order completion. Sites can opt back into synchronous
 * processing by returning false from this filter at a later priority.
 */
add_filter( 'adfoin_should_queue', 'adfoin_woocommerce_force_queue', 10, 2 );

function adfoin_woocommerce_force_queue( $should_queue, $record ) {
    if (
        isset( $record['form_provider'] )
        && 'woocommerce' === $record['form_provider']
        && function_exists( 'as_enqueue_async_action' )
    ) {
        return true;
    }

    return $should_queue;
}

function adfoin_woocommerce_send_subscription_data( $subscription, $saved_records, $extra_data = array() ) {

    $posted_data = adfoin_woocommerce_build_base_posted_data( $subscription, false );

    // Subscription-specific fields. The field list (and merge-tag dropdown)
    // advertises these exact keys, so emit them under the advertised names.
    // Previously they were written under mismatched keys (start_date, etc.) and
    // the *_period / sign_up_fee / order_key keys were never set at all, so all
    // of these tags resolved empty. The five additional getters are guarded with
    // method_exists() since this is the first time they're called here.
    $posted_data['is_manual']        = $subscription->is_manual();
    $posted_data['start']            = $subscription->get_date( 'start' );
    $posted_data['end']              = $subscription->get_date( 'end' );
    $posted_data['trial_end']        = $subscription->get_date( 'trial_end' );
    $posted_data['last_payment']     = $subscription->get_date( 'last_payment' );
    $posted_data['next_payment']     = $subscription->get_date( 'next_payment' );
    $posted_data['order_key']        = method_exists( $subscription, 'get_order_key' ) ? $subscription->get_order_key() : '';
    $posted_data['billing_period']   = method_exists( $subscription, 'get_billing_period' ) ? $subscription->get_billing_period() : '';
    $posted_data['billing_interval'] = method_exists( $subscription, 'get_billing_interval' ) ? $subscription->get_billing_interval() : '';
    $posted_data['trial_period']     = method_exists( $subscription, 'get_trial_period' ) ? $subscription->get_trial_period() : '';
    $posted_data['sign_up_fee']      = method_exists( $subscription, 'get_sign_up_fee' ) ? $subscription->get_sign_up_fee() : '';

    // Legacy keys (never advertised in the dropdown) retained for back-compat,
    // in case an older integration hand-typed them. Same values as above.
    $posted_data['manual_renewal']    = $posted_data['is_manual'];
    $posted_data['start_date']        = $posted_data['start'];
    $posted_data['end_date']          = $posted_data['end'];
    $posted_data['trial_end_date']    = $posted_data['trial_end'];
    $posted_data['last_payment_date'] = $posted_data['last_payment'];
    $posted_data['next_payment_date'] = $posted_data['next_payment'];

    $item_data = adfoin_woocommerce_build_item_data( $subscription->get_items(), $saved_records, false );

    if ( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
        $utm_data    = adfoin_capture_utm_and_url_values();
        $posted_data = $posted_data + $utm_data;
    }

    $posted_data = $posted_data + adfoin_woocommerce_build_merged_items( $item_data );

    if ( ! empty( $extra_data ) && is_array( $extra_data ) ) {
        $posted_data = array_merge( $posted_data, $extra_data );
    }

    adfoin_woocommerce_dispatch_records( $saved_records, $posted_data, $item_data );
}

function adfoin_woocommerce_after_submission( $order, $form_id, $extra = array() ) {

    // Handle AWCDP_Order (Deposits & Partial Payments) - use parent order for all data
    if ( is_a( $order, 'AWCDP_Order' ) ) {
        $parent_id = $order->get_parent_id();
        if ( $parent_id ) {
            $parent_order = wc_get_order( $parent_id );
            if ( $parent_order ) {
                $order = $parent_order;
            }
        }
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'woocommerce', $form_id );

    if ( empty($saved_records) ) {
        return;
    }

    $posted_data = adfoin_woocommerce_build_base_posted_data( $order, true );

    $item_data = adfoin_woocommerce_build_item_data( $order->get_items(), $saved_records, true );

    // Pull the per-order checkout fields saved during checkout. Re-read a fresh
    // order instance so the meta is guaranteed to be loaded from storage
    // (classic or HPOS), then clear it so it's consumed exactly once.
    $order_id       = isset( $posted_data['id'] ) ? $posted_data['id'] : 0;
    $extra_data     = array();
    $checkout_order = $order_id ? wc_get_order( $order_id ) : false;

    if ( $checkout_order instanceof WC_Order ) {
        $stored = $checkout_order->get_meta( '_adfoin_wc_checkout_fields', true );

        if ( $stored ) {
            $extra_data = maybe_unserialize( $stored );
            $checkout_order->delete_meta_data( '_adfoin_wc_checkout_fields' );
            $checkout_order->save();
        }
    }

    // Back-compat: orders placed before this update (or via the option fallback
    // above) may still carry their data in the legacy global option.
    if ( empty( $extra_data ) ) {
        $legacy = maybe_unserialize( get_option( 'adfoin_wc_checkout_fields' ) );

        if ( is_array( $legacy ) && ! empty( $legacy ) ) {
            $extra_data = $legacy;
            update_option( 'adfoin_wc_checkout_fields', maybe_serialize( array() ) );
        }
    }

    if( is_array( $extra_data ) ) {
        $posted_data = $posted_data + $extra_data;
    }

    // Merge custom order meta as merge tags. On classic storage we read the raw
    // post meta exactly as before; under HPOS (where orders are not posts)
    // get_post_meta() returns nothing, so we read through the order object.
    // ($order_id was resolved above when reading the checkout fields.)
    $meta_data = array();

    if (
        class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
        && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
    ) {
        foreach ( $order->get_meta_data() as $meta ) {
            $single = $meta->get_data();
            $key    = isset( $single['key'] ) ? $single['key'] : '';

            // Keep the legacy shape (first value per key wins) so the loop below
            // and the resulting merge tags behave identically to classic storage.
            if ( '' === $key || isset( $meta_data[ $key ] ) ) {
                continue;
            }

            $value             = isset( $single['value'] ) ? $single['value'] : '';
            $meta_data[ $key ] = array( is_scalar( $value ) ? $value : maybe_serialize( $value ) );
        }
    } else {
        $meta_data = get_post_meta( $order_id );
    }

    if( $meta_data ) {
        foreach( $meta_data as $metakey => $metavalue ) {
            $posted_data[$metakey] = isset( $metavalue[0] ) ? $metavalue[0] : '';
        }
    }

    if( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
        $utm_data = adfoin_capture_utm_and_url_values();
        $posted_data = $posted_data + $utm_data;
    }

    $merged_items = adfoin_woocommerce_build_merged_items( $item_data );

    $user_metas = adfoin_woocommerce_get_meta_tags( $saved_records, 'user' );

    if( is_array( $user_metas ) && !empty( $user_metas ) ) {
        foreach( $user_metas as $user_meta ) {
            $meta_tag   = str_replace( 'usermeta_', '', $user_meta );
            $user_id = $order->get_user_id();
            $meta_value = get_user_meta( (int) $user_id, $meta_tag );
            $posted_data[$user_meta] = $meta_value;
        }
    }

    $posted_data = $posted_data + $merged_items;

    // Trigger-specific extras (e.g. the from/to statuses for "Order Status
    // Changed"). Merged last so they win over any same-named field.
    if ( ! empty( $extra ) && is_array( $extra ) ) {
        $posted_data = array_merge( $posted_data, $extra );
    }

    adfoin_woocommerce_dispatch_records( $saved_records, $posted_data, $item_data );
}

/**
 * "Order Status Changed" trigger (#15). Fires on EVERY status transition,
 * including custom/non-standard statuses registered by other plugins (e.g. a
 * "changé" status). The previous/new statuses are exposed as status_from /
 * status_to so users can target a specific status with conditional logic.
 */
add_action( 'woocommerce_order_status_changed', 'adfoin_woocommerce_order_status_changed', 10, 4 );

function adfoin_woocommerce_order_status_changed( $order_id, $status_from, $status_to, $order = null ) {
    if ( ! $order_id ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order_id );
    }

    if ( ! $order instanceof WC_Order ) {
        return;
    }

    adfoin_woocommerce_after_submission(
        $order,
        15,
        array(
            'status_from' => $status_from,
            'status_to'   => $status_to,
        )
    );
}

function adfoin_woocommerce_get_meta_tags( $saved_records, $type ) {
    $item_metas = array();

    if( is_array( $saved_records ) ) {
        foreach( $saved_records as $record ) {
            if( isset( $record['data'] ) ) {
                $data = json_decode( $record['data'], true );

                if( isset( $data['field_data'] ) && is_array( $data['field_data'] ) ) {
                    foreach( $data['field_data'] as $field ) {
                        if( $type == 'item' && false !== strpos( $field, 'itemmeta_' ) ) {
                            preg_match_all( '/itemmeta_.+?\}\}/', $field, $matches );

                            if( isset( $matches[0] ) ) {
                                foreach( $matches[0] as $match ) {
                                    $tag = str_replace( '}}', '', $match );

                                    if( $tag ) {
                                        $item_metas[] = $tag;
                                    }
                                    
                                }
                            }
                        }

                        if( $type == 'user' && false !== strpos( $field, 'usermeta_' ) ) {
                            preg_match_all( '/usermeta_.+?\}\}/', $field, $matches );

                            if( isset( $matches[0] ) ) {
                                foreach( $matches[0] as $match ) {
                                    $tag = str_replace( '}}', '', $match );

                                    if( $tag ) {
                                        $item_metas[] = $tag;
                                    }
                                    
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return array_unique( $item_metas );
}


/**
 * Retrieves the full name of a state (region) based on the provided country and state code.
 *
 * This function retrieves the full name of a state (region) based on the given country and state code.
 * It is commonly used in WooCommerce to get the full name of a state for an order's billing or shipping address.
 *
 * @param WC_Order $order The WooCommerce order object.
 * @param string   $type  Optional. The type of address for which to retrieve the state.
 *                        Accepts 'billing' (default) or 'shipping'.
 * @return string|null The full name of the state (region) if found; otherwise, returns null.
 */
function adfoin_woocommerce_get_full_state($order, $type = 'billing') {
    $country = $type === 'billing' ? $order->get_billing_country() : $order->get_shipping_country();
    $state = $type === 'billing' ? $order->get_billing_state() : $order->get_shipping_state();

    $states = WC()->countries->get_states($country);
    $state_full = isset($states[$state]) ? $states[$state] : '';

    return $state_full;
}
