<?php

// Get SureCart Triggers
function adfoin_surecart_get_forms( $form_provider ) {
    if ( $form_provider != 'surecart' ) {
        return;
    }

    $triggers = array(
        'makePurchase' => __( 'Make a Purchase', 'advanced-form-integration' ),
        'subscriptionCreated' => __( 'Subscription Created', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get SureCart Fields
function adfoin_surecart_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'surecart' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'makePurchase' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'first_name' => __( 'First Name', 'advanced-form-integration' ),
            'last_name' => __( 'Last Name', 'advanced-form-integration' ),
            'full_name' => __( 'Full Name', 'advanced-form-integration' ),
            'phone' => __( 'Phone', 'advanced-form-integration' ),
            'order_id' => __( 'Order ID', 'advanced-form-integration' ),
            'order_total' => __( 'Order Total', 'advanced-form-integration' ),
            'products' => __( 'Purchased Products', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'subscriptionCreated' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'first_name' => __( 'First Name', 'advanced-form-integration' ),
            'last_name' => __( 'Last Name', 'advanced-form-integration' ),
            'full_name' => __( 'Full Name', 'advanced-form-integration' ),
            'phone' => __( 'Phone', 'advanced-form-integration' ),
            'subscription_id' => __( 'Subscription ID', 'advanced-form-integration' ),
            'subscription_status' => __( 'Subscription Status', 'advanced-form-integration' ),
            'products' => __( 'Subscription Products', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

/**
 * Customer details for a SureCart purchase/subscription. Guest customers
 * (no WordPress account) are included; user_id is then empty.
 *
 * @param string|object $customer_id SureCart customer ID (or loaded model).
 * @return array
 */
function adfoin_surecart_customer_fields( $customer_id ) {
    $customer = is_object( $customer_id ) ? $customer_id : null;

    if ( ! $customer && $customer_id && class_exists( '\\SureCart\\Models\\Customer' ) ) {
        $customer = \SureCart\Models\Customer::find( $customer_id );
    }

    if ( ! $customer || is_wp_error( $customer ) || empty( $customer->email ) ) {
        return array();
    }

    $user  = get_user_by( 'email', $customer->email );
    $first = isset( $customer->first_name ) ? (string) $customer->first_name : '';
    $last  = isset( $customer->last_name ) ? (string) $customer->last_name : '';
    $full  = isset( $customer->name ) ? (string) $customer->name : '';

    // Fall back to the WordPress profile, then to splitting the full name.
    if ( '' === $first && '' === $last && $user ) {
        $first = (string) $user->first_name;
        $last  = (string) $user->last_name;
    }

    if ( '' === $first && '' === $last && '' !== trim( $full ) ) {
        $parts = preg_split( '/\s+/', trim( $full ), 2 );
        $first = $parts[0];
        $last  = isset( $parts[1] ) ? $parts[1] : '';
    }

    if ( '' === trim( $full ) ) {
        $full = trim( $first . ' ' . $last );
    }

    return array(
        'user_id'    => $user ? $user->ID : '',
        'user_email' => $customer->email,
        'first_name' => $first,
        'last_name'  => $last,
        'full_name'  => $full,
        'phone'      => isset( $customer->phone ) ? (string) $customer->phone : '',
    );
}

// Handle Purchase Created
function adfoin_surecart_handle_purchase( $purchase ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'surecart', 'makePurchase' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $customer_fields = adfoin_surecart_customer_fields( $purchase->customer );

    if ( empty( $customer_fields['user_email'] ) ) {
        return;
    }

    $posted_data = $customer_fields + array(
        'order_id' => $purchase->initial_order,
        'order_total' => $purchase->total,
        'products' => wp_json_encode( $purchase->items ), // Assuming $purchase->items contains product details
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

add_action( 'surecart/purchase_created', 'adfoin_surecart_handle_purchase', 10, 1 );

// Handle Subscription Created
function adfoin_surecart_handle_subscription( $subscription ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'surecart', 'subscriptionCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $customer_fields = adfoin_surecart_customer_fields( $subscription->customer );

    if ( empty( $customer_fields['user_email'] ) ) {
        return;
    }

    $posted_data = $customer_fields + array(
        'subscription_id' => $subscription->id,
        'subscription_status' => $subscription->status,
        'products' => wp_json_encode( $subscription->items ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

add_action( 'surecart/subscription_created', 'adfoin_surecart_handle_subscription', 10, 1 );