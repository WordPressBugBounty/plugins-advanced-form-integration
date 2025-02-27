<?php

// Get MyCred Triggers
function adfoin_mycred_get_forms( $form_provider ) {
    if ( $form_provider !== 'mycred' ) {
        return;
    }

    $triggers = array(
        'currentBalance' => __( "User's Current Balance Reached", 'advanced-form-integration' ),
        'earnsRank'     => __( 'User Earns a Rank', 'advanced-form-integration' ),
        'totalBalance'  => __( "User's Total Balance Reached", 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get MyCred Fields
function adfoin_mycred_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'mycred' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'currentBalance' ) {
        $fields = array(
            'user_id'          => __( 'User ID', 'advanced-form-integration' ),
            'current_balance'  => __( 'Current Balance', 'advanced-form-integration' ),
            'required_balance' => __( 'Balance Threshold', 'advanced-form-integration' ),
            'point_type'       => __( 'Point Type', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'earnsRank' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'rank_id' => __( 'Earned Rank ID', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'totalBalance' ) {
        $fields = array(
            'user_id'       => __( 'User ID', 'advanced-form-integration' ),
            'total_balance' => __( 'Total Balance', 'advanced-form-integration' ),
            'point_type'    => __( 'Point Type', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Current Balance Update
add_action( 'mycred_update_user_balance', 'adfoin_mycred_handle_current_balance', 10, 4 );
function adfoin_mycred_handle_current_balance( $user_id, $current_balance, $required_balance, $point_type ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'mycred', 'currentBalance' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! function_exists( 'mycred_get_users_balance' ) ) {
        return;
    }

    $posted_data = array(
        'user_id'         => $user_id,
        'current_balance' => $current_balance,
        'required_balance'=> $required_balance,
        'point_type'      => $point_type,
    );

    $integration->send( $saved_records, $posted_data );
}

// Handle Earns Rank
add_action( 'mycred_user_got_promoted', 'adfoin_mycred_handle_earns_rank', 10, 3 );
add_action( 'mycred_user_got_demoted',   'adfoin_mycred_handle_earns_rank', 10, 3 );
function adfoin_mycred_handle_earns_rank( $user_id, $rank_id, $results ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'mycred', 'earnsRank' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // $results is assumed to be an object containing additional rank info.
    $posted_data = array(
        'user_id' => $user_id,
        'rank_id' => $rank_id,
        // Optionally include additional data from $results here.
    );

    $integration->send( $saved_records, $posted_data );
}

// Handle Total Balance Update
add_action( 'mycred_update_user_total_balance', 'adfoin_mycred_handle_total_balance', 10, 4 );
function adfoin_mycred_handle_total_balance( $total_balance, $user_id, $point_type, $obj ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'mycred', 'totalBalance' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! function_exists( 'mycred_get_users_total_balance' ) ) {
        return;
    }

    $posted_data = array(
        'user_id'       => $user_id,
        'total_balance' => $total_balance,
        'point_type'    => $point_type,
        // Optionally add more details from $obj if needed.
    );

    $integration->send( $saved_records, $posted_data );
}