<?php

// Get RCP Triggers
function adfoin_rcp_get_forms( $form_provider ) {
    if ( $form_provider != 'restrictcontentpro' ) {
        return;
    }

    $triggers = array(
        'purchaseMembership' => __( 'Membership Purchased', 'advanced-form-integration' ),
        'cancelMembership' => __( 'Membership Cancelled', 'advanced-form-integration' ),
        'activateFreeMembership' => __( 'Free Membership Activated', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get RCP Fields
function adfoin_rcp_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'restrictcontentpro' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'purchaseMembership' || $form_id === 'cancelMembership' || $form_id === 'activateFreeMembership' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'membership_id' => __( 'Membership ID', 'advanced-form-integration' ),
            'membership_name' => __( 'Membership Name', 'advanced-form-integration' ),
            'status' => __( 'Membership Status', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Membership Purchased
function adfoin_rcp_handle_membership_purchase( $membership_id, $membership ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'restrictcontentpro', 'purchaseMembership' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // Bail if it's not a paid membership
    if ( ! $membership->is_paid() ) {
        return;
    }

    $user_id = $membership->get_user_id();
    $membership_name = $membership->get_membership_level();

    $posted_data = array(
        'user_id' => $user_id,
        'membership_id' => $membership_id,
        'membership_name' => $membership_name,
        'status' => __( 'Purchased', 'advanced-form-integration' ),
    );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'rcp_membership_post_activate', 'adfoin_rcp_handle_membership_purchase', 10, 2 );

// Handle Membership Cancelled
function adfoin_rcp_handle_membership_cancel( $old_status, $membership_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'restrictcontentpro', 'cancelMembership' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $membership = rcp_get_membership( $membership_id );
    $user_id = $membership->get_user_id();
    $membership_name = $membership->get_membership_level();

    $posted_data = array(
        'user_id' => $user_id,
        'membership_id' => $membership_id,
        'membership_name' => $membership_name,
        'status' => __( 'Cancelled', 'advanced-form-integration' ),
    );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'rcp_transition_membership_status_cancelled', 'adfoin_rcp_handle_membership_cancel', 10, 2 );

// Handle Free Membership Activated
function adfoin_rcp_handle_free_membership( $membership_id, $membership ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'restrictcontentpro', 'activateFreeMembership' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // Bail if it's not a free membership
    if ( $membership->is_paid() ) {
        return;
    }

    $user_id = $membership->get_user_id();
    $membership_name = $membership->get_membership_level();

    $posted_data = array(
        'user_id' => $user_id,
        'membership_id' => $membership_id,
        'membership_name' => $membership_name,
        'status' => __( 'Free Activated', 'advanced-form-integration' ),
    );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'rcp_membership_post_activate', 'adfoin_rcp_handle_free_membership', 10, 2 );