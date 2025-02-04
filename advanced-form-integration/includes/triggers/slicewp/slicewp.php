<?php

// Get SliceWP Triggers
function adfoin_slicewp_get_forms( $form_provider ) {
    if ( $form_provider != 'slicewp' ) {
        return;
    }

    $triggers = array(
        'becomeAffiliate' => __( 'Become an Affiliate', 'advanced-form-integration' ),
        // 'generateReferral' => __( 'Generate a Referral', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get SliceWP Fields
function adfoin_slicewp_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'slicewp' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'becomeAffiliate' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'affiliate_id' => __( 'Affiliate ID', 'advanced-form-integration' ),
            'status' => __( 'Affiliate Status', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'generateReferral' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'affiliate_id' => __( 'Affiliate ID', 'advanced-form-integration' ),
            'referral_amount' => __( 'Referral Amount', 'advanced-form-integration' ),
            'referral_status' => __( 'Referral Status', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Become an Affiliate
function adfoin_slicewp_handle_become_affiliate( $affiliate_id, $affiliate_data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'slicewp', 'becomeAffiliate' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( $affiliate_data['status'] !== 'active' ) {
        return;
    }

    // Get the affiliate user ID
    $affiliate = slicewp_get_affiliate( $affiliate_id );
    $user_id = $affiliate->get( 'user_id' );
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'user_id' => $user_id,
        'user_name' => $user_name,
        'affiliate_id' => $affiliate_id,
        'status' => __( 'Active', 'advanced-form-integration' ),
    );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'slicewp_insert_affiliate', 'adfoin_slicewp_handle_become_affiliate', 10, 2 );
add_action( 'slicewp_update_affiliate', 'adfoin_slicewp_handle_become_affiliate', 10, 2 );

// Handle Generate a Referral
// function adfoin_slicewp_handle_generate_referral( $referral_id, $referral_data ) {
//     $integration = new Advanced_Form_Integration_Integration();
//     $saved_records = $integration->get_by_trigger( 'slicewp', 'generateReferral' );

//     if ( empty( $saved_records ) ) {
//         return;
//     }

//     $affiliate = slicewp_get_affiliate( $referral_data['affiliate_id'] );
//     $user_id = $affiliate->get( 'user_id' );
//     $user_name = get_the_author_meta( 'display_name', $user_id );

//     $posted_data = array(
//         'user_id' => $user_id,
//         'user_name' => $user_name,
//         'affiliate_id' => $referral_data['affiliate_id'],
//         'referral_amount' => $referral_data['amount'],
//         'referral_status' => $referral_data['status'],
//     );

//     $integration->send( $saved_records, $posted_data );
// }

// add_action( 'slicewp_insert_referral', 'adfoin_slicewp_handle_generate_referral', 10, 2 );