<?php

// Get WP Simple Pay Triggers
function adfoin_wpsimplepay_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpsimplepay' ) {
        return;
    }

    $triggers = array(
        'completePurchase' => __( 'Purchase Completed', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get WP Simple Pay Fields
function adfoin_wpsimplepay_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpsimplepay' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'completePurchase' ) {
        $fields = [
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'post_id' => __( 'Form ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'amount' => __( 'Payment Amount', 'advanced-form-integration' ),
            'currency' => __( 'Payment Currency', 'advanced-form-integration' ),
            'payment_status' => __( 'Payment Status', 'advanced-form-integration' ),
            'transaction_id' => __( 'Transaction ID', 'advanced-form-integration' ),
            'payment_date' => __( 'Payment Date', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_wpsimplepay_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_id'] = $user_id;
        $user_data['user_email'] = $user->user_email;
    }

    return $user_data;
}

// Handle Purchase Completion
add_action( 'simpay_webhook_payment_intent_succeeded', 'adfoin_wpsimplepay_handle_complete_purchase', 10, 2 );
function adfoin_wpsimplepay_handle_complete_purchase( $event, $payment ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpsimplepay', 'completePurchase' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user = get_user_by( 'email', $payment->customer->email );

    // Bail if user can't be found
    if ( ! $user ) {
        return;
    }

    $user_id = $user->ID;

    $post_id = isset( $payment->metadata->simpay_form_id ) 
        ? $payment->metadata->simpay_form_id 
        : end( $payment->lines->data )->metadata->simpay_form_id;

    $posted_data = [
        'user_id' => $user_id,
        'post_id' => $post_id,
        'user_email' => $payment->customer->email,
        'amount' => $payment->amount / 100,
        'currency' => strtoupper( $payment->currency ),
        'payment_status' => $payment->status,
        'transaction_id' => $payment->id,
        'payment_date' => date( 'Y-m-d H:i:s', $payment->created ),
    ];

    $integration->send( $saved_records, $posted_data );
}