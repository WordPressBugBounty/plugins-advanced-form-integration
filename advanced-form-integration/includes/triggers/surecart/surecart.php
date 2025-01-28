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
            'order_id' => __( 'Order ID', 'advanced-form-integration' ),
            'order_total' => __( 'Order Total', 'advanced-form-integration' ),
            'products' => __( 'Purchased Products', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'subscriptionCreated' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'subscription_id' => __( 'Subscription ID', 'advanced-form-integration' ),
            'subscription_status' => __( 'Subscription Status', 'advanced-form-integration' ),
            'products' => __( 'Subscription Products', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Purchase Created
function adfoin_surecart_handle_purchase( $purchase ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'surecart', 'makePurchase' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $customer = new SureCart\Models\Customer;
    $customer_data = $customer::find( $purchase->customer );
    $user_data = get_user_by( 'email', $customer_data->email );

    // Bail if user data is not found
    if ( ! $user_data ) {
        return;
    }

    $posted_data = array(
        'user_id' => $user_data->ID,
        'user_email' => $customer_data->email,
        'order_id' => $purchase->initial_order,
        'order_total' => $purchase->total,
        'products' => json_encode( $purchase->items ), // Assuming $purchase->items contains product details
    );

    adfoin_surecart_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'surecart/purchase_created', 'adfoin_surecart_handle_purchase', 10, 1 );

// Handle Subscription Created
function adfoin_surecart_handle_subscription( $subscription ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'surecart', 'subscriptionCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $customer = new SureCart\Models\Customer;
    $customer_data = $customer::find( $subscription->customer );
    $user_data = get_user_by( 'email', $customer_data->email );

    // Bail if user data is not found
    if ( ! $user_data ) {
        return;
    }

    $posted_data = array(
        'user_id' => $user_data->ID,
        'user_email' => $customer_data->email,
        'subscription_id' => $subscription->id,
        'subscription_status' => $subscription->status,
        'products' => json_encode( $subscription->items ),
    );

    adfoin_surecart_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'surecart/subscription_created', 'adfoin_surecart_handle_subscription', 10, 1 );

// Send Trigger Data
function adfoin_surecart_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data
                )
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}