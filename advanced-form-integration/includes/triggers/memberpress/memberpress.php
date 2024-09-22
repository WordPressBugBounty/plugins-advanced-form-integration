<?php

function adfoin_memberpress_get_forms( $form_provider ) {
    
        if( $form_provider != 'memberpress' ) {
            return;
        }
    
        $forms = array(
            'purchasedOneTimeSubscription' => __( 'Purchased Subscription', 'advanced-form-integration' ),
            // 'purchasedRecurringSubscription' => __( 'Purchased Recurring Subscription', 'advanced-form-integration' ),
            'membershipCancelled' => __( 'Membership Cancelled', 'advanced-form-integration' ),
            'membershipExpired' => __( 'Membership Expired', 'advanced-form-integration' ),
            'membershipPaused' => __( 'Membership Paused', 'advanced-form-integration' ),
        );

        return $forms;
}

function adfoin_memberpress_get_form_fields( $form_provider, $form_id ) {

    if( $form_provider != 'memberpress' ) {
        return;
    }

    $fields = array(
        'user_id' => __( 'User ID', 'advanced-form-integration' ),
        'user_email' => __( 'User Email', 'advanced-form-integration' ),
        'user_firstname' => __( 'User First Name', 'advanced-form-integration' ),
        'user_lastname' => __( 'User Last Name', 'advanced-form-integration' ),
        'user_nicename' => __( 'User Nicename', 'advanced-form-integration' ),
        'avatar_url' => __( 'Avatar URL', 'advanced-form-integration' ),
    );

    if( in_array( $form_id, array( 'purchasedOneTimeSubscription', 'purchasedRecurringSubscription' ) ) ) {
        $fields['membership_id'] = __( 'Membership ID', 'advanced-form-integration' );
        $fields['membership_name'] = __( 'Membership Name', 'advanced-form-integration' );
        $fields['membership_description'] = __( 'Membership Description', 'advanced-form-integration' );
    } elseif( in_array( $form_id, array( 'membershipCancelled', 'membershipExpired', 'membershipPaused' ) ) ) {
        $fields['subscription_id'] = __( 'Subscription ID', 'advanced-form-integration' );
        $fields['gateway'] = __( 'Gateway', 'advanced-form-integration' );
        $fields['product_id'] = __( 'Product ID', 'advanced-form-integration' );
        $fields['price'] = __( 'Price', 'advanced-form-integration' );
        $fields['period_type'] = __( 'Period Type', 'advanced-form-integration' );
        $fields['trial_amount'] = __( 'Trial Amount', 'advanced-form-integration' );
    }

    return $fields;
}

// Get User Data
function adfoin_memberpress_get_userdata( $user_id ) {
    $user = get_userdata( $user_id );
    $user_data = array(
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'nickname' => $user->nickname,
        'avatar_url' => get_avatar_url( $user_id ),
        'user_email' => $user->user_email
    );

    return $user_data;
}

// submit form
add_action( 'mepr-event-transaction-completed', 'adfoin_memberpress_subscribe', 10, 1 );

function adfoin_memberpress_subscribe( $event ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'memberpress', 'purchasedOneTimeSubscription' );

    if( empty( $saved_records ) ) {
        return;
    }

    $transaction = $event->get_data();
    $product = $transaction->product();
    $product_id = $product->ID;
    $user_id = absint( $transaction->user()->ID );

    if( 'lifetime' == $product->period_type ) {
        return;
    }

    $user_data = adfoin_memberpress_get_userdata( $user_id );
    $membership_data = array(
        'membership_id' => $product_id,
        'membership_name' => $product->post_title,
        'membership_description' => $product->post_content,
        'user_id' => $user_id,
        'user_email' => $user_data['user_email'],
        'user_firstname' => $user_data['first_name'],
        'user_lastname' => $user_data['last_name'],
        'user_nicename' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
    );

    $integration->send( $saved_records, $membership_data );
}

// add_action( 'mepr-event-transaction-completed', 'adfoin_memberpress_subscribe_recurring', 10, 1 );

// function adfoin_memberpress_subscribe_recurring( $event ) {
//     $integration = new Advanced_Form_Integration_Integration();
//     $saved_records = $integration->get_by_trigger( 'memberpress', 'purchasedRecurringSubscription' );

//     if( empty( $saved_records ) ) {
//         return;
//     }

//     $transaction = $event->get_data();
//     $product = $transaction->product();
//     $product_id = $product->ID;
//     $user_id = absint( $transaction->user()->ID );

//     if( 'lifetime' == $product->period_type ) {
//         return;
//     }

//     $user_data = adfoin_memberpress_get_userdata( $user_id );
//     $membership_data = array(
//         'membership_id' => $product_id,
//         'membership_name' => $product->post_title,
//         'membership_description' => $product->post_content,
//         'user_id' => $user_id,
//         'user_email' => $user_data['user_email'],
//         'user_firstname' => $user_data['first_name'],
//         'user_lastname' => $user_data['last_name'],
//         'user_nicename' => $user_data['nickname'],
//         'avatar_url' => $user_data['avatar_url'],
//     );

//     $integration->send( $saved_records, $membership_data );
// }

add_action( 'mepr_subscription_transition_status', 'adfoin_memberpress_cancel_membership', 10, 3 );

function adfoin_memberpress_cancel_membership( $old_status, $new_status, $subscription ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'memberpress', 'membershipCancelled' );

    if( empty( $saved_records ) ) {
        return;
    }

    if( $old_status == $new_status && $new_status != 'cancelled' ) {
        return;
    }

    $user_id = absint( $subscription->user_id );
    $user_data = adfoin_memberpress_get_userdata( $user_id );
    $membership_data = array(
        'subscription_id' => $subscription->id,
        'gateway' => $subscription->gateway,
        'product_id' => $subscription->product_id,
        'price' => $subscription->total,
        'period_type' => $subscription->period_type,
        'trial_amount' => $subscription->trial_amount,
        'user_id' => $user_id,
        'user_email' => $user_data['user_email'],
        'user_firstname' => $user_data['first_name'],
        'user_lastname' => $user_data['last_name'],
        'user_nicename' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
    );

    $integration->send( $saved_records, $membership_data );
}