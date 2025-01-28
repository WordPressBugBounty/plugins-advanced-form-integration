<?php

// Get MemberPress Triggers
function adfoin_memberpress_get_forms( $form_provider ) {
    if ( $form_provider != 'memberpress' ) {
        return;
    }

    $triggers = array(
        'purchaseMembership' => __( 'Membership Purchased', 'advanced-form-integration' ),
        'cancelMembership' => __( 'Membership Cancelled', 'advanced-form-integration' ),
        'expireMembership' => __( 'Membership Expired', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get MemberPress Fields
function adfoin_memberpress_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'memberpress' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'purchaseMembership' ) {
        $fields = array(
            'membership_id' => __( 'Membership ID', 'advanced-form-integration' ),
            'membership_name' => __( 'Membership Name', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'transaction_id' => __( 'Transaction ID', 'advanced-form-integration' ),
            'price' => __( 'Price', 'advanced-form-integration' ),
            'payment_method' => __( 'Payment Method', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'cancelMembership' || $form_id === 'expireMembership' ) {
        $fields = array(
            'membership_id' => __( 'Membership ID', 'advanced-form-integration' ),
            'membership_name' => __( 'Membership Name', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'reason' => __( 'Reason (Cancellation/Expiry)', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Membership Purchased
function adfoin_memberpress_handle_membership_purchase( $transaction ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'memberpress', 'purchaseMembership' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $membership_id = $transaction->product_id;
    $user_id = $transaction->user_id;
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'membership_id' => $membership_id,
        'membership_name' => get_the_title( $membership_id ),
        'user_id' => $user_id,
        'user_name' => $user_name,
        'transaction_id' => $transaction->id,
        'price' => $transaction->total,
        'payment_method' => $transaction->payment_method,
    );

    adfoin_memberpress_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'mepr-event-non-recurring-transaction-completed', 'adfoin_memberpress_handle_membership_purchase', 10, 1 );
add_action( 'mepr-event-recurring-transaction-completed', 'adfoin_memberpress_handle_membership_purchase', 10, 1 );

// Handle Membership Cancelled
function adfoin_memberpress_handle_membership_cancel( $subscription_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'memberpress', 'cancelMembership' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $subscription = MeprSubscription::get_one( $subscription_id );
    $user_id = $subscription->user_id;
    $membership_id = $subscription->product_id;
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'membership_id' => $membership_id,
        'membership_name' => get_the_title( $membership_id ),
        'user_id' => $user_id,
        'user_name' => $user_name,
        'reason' => __( 'User Cancelled Subscription', 'advanced-form-integration' ),
    );

    adfoin_memberpress_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'mepr-event-subscription-cancelled', 'adfoin_memberpress_handle_membership_cancel', 10, 1 );

// Handle Membership Expired
function adfoin_memberpress_handle_membership_expire( $subscription_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'memberpress', 'expireMembership' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $subscription = MeprSubscription::get_one( $subscription_id );
    $user_id = $subscription->user_id;
    $membership_id = $subscription->product_id;
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'membership_id' => $membership_id,
        'membership_name' => get_the_title( $membership_id ),
        'user_id' => $user_id,
        'user_name' => $user_name,
        'reason' => __( 'Membership Expired', 'advanced-form-integration' ),
    );

    adfoin_memberpress_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'mepr-event-subscription-expired', 'adfoin_memberpress_handle_membership_expire', 10, 1 );

// Send data
function adfoin_memberpress_send_trigger_data( $saved_records, $posted_data ) {
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