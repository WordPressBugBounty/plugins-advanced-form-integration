<?php

// Get MemberPress Triggers
function adfoin_memberpress_get_forms( $form_provider ) {
    if ( $form_provider != 'memberpress' ) {
        return;
    }

    $triggers = array(
        'purchaseOneTimeProduct' => __( 'One-Time Subscription Purchased', 'advanced-form-integration' ),
        'purchaseRecurringProduct' => __( 'Recurring Subscription Purchased', 'advanced-form-integration' ),
        // 'renewRecurringSubscription' => __( 'Recurring Subscription Renewed', 'advanced-form-integration' ),
        'userAddedToMembership' => __( 'User Added to Membership', 'advanced-form-integration' ),
        // 'userRemovedFromOneTimeMembership' => __( 'User Removed from One-Time Subscription', 'advanced-form-integration' ),
        // 'userRemovedFromRecurringMembership' => __( 'User Removed from Recurring Subscription', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get MemberPress Fields
function adfoin_memberpress_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'memberpress' ) {
        return;
    }

    $fields = array(
        'membership_id' => __( 'Membership ID', 'advanced-form-integration' ),
        'membership_name' => __( 'Membership Name', 'advanced-form-integration' ),
        'user_id' => __( 'User ID', 'advanced-form-integration' ),
        'user_name' => __( 'User Name', 'advanced-form-integration' ),
        'transaction_id' => __( 'Transaction ID', 'advanced-form-integration' ),
        'price' => __( 'Price', 'advanced-form-integration' ),
        'payment_method' => __( 'Payment Method', 'advanced-form-integration' ),
    );

    if ( in_array( $form_id, ['renewRecurringSubscription', 'userRemovedFromOneTimeMembership', 'userRemovedFromRecurringMembership'] ) ) {
        $fields['reason'] = __( 'Reason', 'advanced-form-integration' );
    }

    return $fields;
}

// Handle Membership Events
function adfoin_memberpress_handle_event( $event, $trigger ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'memberpress', $trigger );

    if ( empty( $saved_records ) ) {
        return;
    }

    $transaction = $event->get_data();
    $product = $transaction->product();
    $user_id = absint( $transaction->user()->ID );

    $posted_data = array(
        'membership_id' => $product->ID,
        'membership_name' => get_the_title( $product->ID ),
        'user_id' => $user_id,
        'user_name' => get_the_author_meta( 'display_name', $user_id ),
        'transaction_id' => $transaction->id,
        'price' => $transaction->amount,
        'payment_method' => $transaction->payment_method,
    );

    if ( $trigger === 'renewRecurringSubscription' ) {
        $posted_data['reason'] = __( 'Subscription Renewed', 'advanced-form-integration' );
    }

    $integration->send( $saved_records, $posted_data );
}

add_action( 'mepr-event-transaction-completed', function( $event ) {
    adfoin_memberpress_handle_event( $event, 'purchaseOneTimeProduct' );
}, 10, 1 );

add_action( 'mepr-event-transaction-completed', function( $event ) {
    adfoin_memberpress_handle_event( $event, 'purchaseRecurringProduct' );
}, 10, 1 );

// add_action( 'mepr-event-renewal-transaction-completed', function( $event ) {
//     adfoin_memberpress_handle_event( $event, 'renewRecurringSubscription' );
// }, 10, 1 );

add_action( 'mepr-event-transaction-completed', function( $event ) {
    adfoin_memberpress_handle_event( $event, 'userAddedToMembership' );
}, 10, 1 );

// add_action( 'mepr_subscription_deleted', function( $subscription_id ) {
//     adfoin_memberpress_handle_event( $subscription_id, 'userRemovedFromRecurringMembership' );
// }, 10, 1 );

// add_action( 'mepr_post_delete_transaction', 'adfoin_memberpress_handle_delete_transaction', 10, 3 );

// function adfoin_memberpress_handle_delete_transaction( $id, $user, $result ) {
//     $event = new stdClass();
//     $event->id = $id;
//     $event->user = $user;
//     $event->result = $result;

//     adfoin_memberpress_handle_event( $event, 'userRemovedFromOneTimeMembership' );
// }
