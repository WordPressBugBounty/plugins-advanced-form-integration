<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve myCRED triggers.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_mycred_get_forms( $form_provider ) {
    if ( 'mycred' !== $form_provider ) {
        return;
    }

    return array(
        'currentBalance' => __( "User's Current Balance Updated", 'advanced-form-integration' ),
        'earnsRank'      => __( 'User Earns a Rank', 'advanced-form-integration' ),
        'totalBalance'   => __( "User's Total Balance Updated", 'advanced-form-integration' ),
    );
}

/**
 * Base user fields shared by all triggers.
 *
 * @return array<string,string>
 */
function adfoin_mycred_user_fields() {
    return array(
        'user_id'          => __( 'User ID', 'advanced-form-integration' ),
        'user_email'       => __( 'User Email', 'advanced-form-integration' ),
        'user_login'       => __( 'User Login', 'advanced-form-integration' ),
        'user_display_name'=> __( 'User Display Name', 'advanced-form-integration' ),
        'user_first_name'  => __( 'User First Name', 'advanced-form-integration' ),
        'user_last_name'   => __( 'User Last Name', 'advanced-form-integration' ),
        'user_roles'       => __( 'User Roles', 'advanced-form-integration' ),
    );
}

/**
 * Retrieve mapped fields for myCRED triggers.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_mycred_get_form_fields( $form_provider, $form_id ) {
    if ( 'mycred' !== $form_provider ) {
        return;
    }

    $fields = adfoin_mycred_user_fields();

    switch ( $form_id ) {
        case 'currentBalance':
            $fields['current_balance']          = __( 'Current Balance', 'advanced-form-integration' );
            $fields['current_balance_formatted']= __( 'Current Balance (Formatted)', 'advanced-form-integration' );
            $fields['balance_change']           = __( 'Balance Change', 'advanced-form-integration' );
            $fields['balance_change_formatted'] = __( 'Balance Change (Formatted)', 'advanced-form-integration' );
            $fields['previous_balance']         = __( 'Previous Balance', 'advanced-form-integration' );
            $fields['previous_balance_formatted']= __( 'Previous Balance (Formatted)', 'advanced-form-integration' );
            $fields['point_type']               = __( 'Point Type', 'advanced-form-integration' );
            $fields['point_type_label']         = __( 'Point Type Label', 'advanced-form-integration' );
            $fields['update_timestamp']         = __( 'Update Timestamp', 'advanced-form-integration' );
            break;

        case 'earnsRank':
            $fields['rank_id']             = __( 'Rank ID', 'advanced-form-integration' );
            $fields['rank_title']          = __( 'Rank Title', 'advanced-form-integration' );
            $fields['rank_slug']           = __( 'Rank Slug', 'advanced-form-integration' );
            $fields['rank_minimum']        = __( 'Rank Minimum', 'advanced-form-integration' );
            $fields['rank_maximum']        = __( 'Rank Maximum', 'advanced-form-integration' );
            $fields['previous_rank_id']    = __( 'Previous Rank ID', 'advanced-form-integration' );
            $fields['previous_rank_title'] = __( 'Previous Rank Title', 'advanced-form-integration' );
            $fields['point_type']          = __( 'Point Type', 'advanced-form-integration' );
            $fields['point_type_label']    = __( 'Point Type Label', 'advanced-form-integration' );
            $fields['rank_event']          = __( 'Rank Event Type', 'advanced-form-integration' );
            $fields['update_timestamp']    = __( 'Update Timestamp', 'advanced-form-integration' );
            break;

        case 'totalBalance':
            $fields['total_balance']          = __( 'Total Balance', 'advanced-form-integration' );
            $fields['total_balance_formatted']= __( 'Total Balance (Formatted)', 'advanced-form-integration' );
            $fields['point_type']             = __( 'Point Type', 'advanced-form-integration' );
            $fields['point_type_label']       = __( 'Point Type Label', 'advanced-form-integration' );
            $fields['update_timestamp']       = __( 'Update Timestamp', 'advanced-form-integration' );
            break;
    }

    return $fields;
}

/**
 * Normalize value to string.
 *
 * @param mixed $value Value to normalize.
 *
 * @return string
 */
function adfoin_mycred_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Format amount using myCRED formatting when available.
 *
 * @param mixed  $amount     Amount.
 * @param string $point_type Point type.
 *
 * @return string
 */
function adfoin_mycred_format_amount( $amount, $point_type ) {
    if ( ! function_exists( 'mycred' ) ) {
        return adfoin_mycred_normalize_value( $amount );
    }

    $mycred = mycred( $point_type );

    if ( ! $mycred || ! method_exists( $mycred, 'format_creds' ) ) {
        return adfoin_mycred_normalize_value( $amount );
    }

    return adfoin_mycred_normalize_value( $mycred->format_creds( $amount ) );
}

/**
 * Collect user context for payloads.
 *
 * @param int $user_id User ID.
 *
 * @return array<string,string>
 */
function adfoin_mycred_collect_user_context( $user_id ) {
    $context = array(
        'user_id'          => adfoin_mycred_normalize_value( $user_id ),
        'user_email'       => '',
        'user_login'       => '',
        'user_display_name'=> '',
        'user_first_name'  => '',
        'user_last_name'   => '',
        'user_roles'       => '',
    );

    if ( ! $user_id ) {
        return $context;
    }

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return $context;
    }

    $context['user_email']        = adfoin_mycred_normalize_value( $user->user_email );
    $context['user_login']        = adfoin_mycred_normalize_value( $user->user_login );
    $context['user_display_name'] = adfoin_mycred_normalize_value( $user->display_name );
    $context['user_first_name']   = adfoin_mycred_normalize_value( $user->first_name );
    $context['user_last_name']    = adfoin_mycred_normalize_value( $user->last_name );
    $context['user_roles']        = adfoin_mycred_normalize_value( $user->roles );

    return $context;
}

/**
 * Retrieve point type label.
 *
 * @param string $point_type Point type.
 *
 * @return string
 */
function adfoin_mycred_point_type_label( $point_type ) {
    if ( function_exists( 'mycred_get_point_type_name' ) ) {
        return adfoin_mycred_normalize_value( mycred_get_point_type_name( $point_type, true ) );
    }

    return adfoin_mycred_normalize_value( $point_type );
}

/**
 * Dispatch payload to saved integrations.
 *
 * @param string               $trigger Trigger key.
 * @param array<string,string> $payload Payload data.
 *
 * @return void
 */
function adfoin_mycred_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'mycred', $trigger );

    if ( empty( $saved_records ) ) {
        return;
    }

    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];

        if ( $job_queue ) {
            as_enqueue_async_action(
                "adfoin_{$action_provider}_job_queue",
                array(
                    'data' => array(
                        'record'      => $record,
                        'posted_data' => $payload,
                    ),
                )
            );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $payload );
        }
    }
}

add_action( 'mycred_update_user_balance', 'adfoin_mycred_handle_current_balance', 10, 4 );

/**
 * Handle current balance updates.
 *
 * @param int    $user_id         User ID.
 * @param mixed  $current_balance Current balance.
 * @param mixed  $amount          Balance change.
 * @param string $point_type      Point type key.
 *
 * @return void
 */
function adfoin_mycred_handle_current_balance( $user_id, $current_balance, $amount, $point_type ) {
    $user_context = adfoin_mycred_collect_user_context( $user_id );

    $current_numeric = is_numeric( $current_balance ) ? (float) $current_balance : null;
    $change_numeric  = is_numeric( $amount ) ? (float) $amount : null;
    $previous_numeric = null;

    if ( null !== $current_numeric && null !== $change_numeric ) {
        $previous_numeric = $current_numeric - $change_numeric;
    }

    $payload = array_merge(
        $user_context,
        array(
            'current_balance'           => adfoin_mycred_normalize_value( $current_balance ),
            'current_balance_formatted' => adfoin_mycred_format_amount( $current_balance, $point_type ),
            'balance_change'            => adfoin_mycred_normalize_value( $amount ),
            'balance_change_formatted'  => adfoin_mycred_format_amount( $amount, $point_type ),
            'previous_balance'          => adfoin_mycred_normalize_value( $previous_numeric ),
            'previous_balance_formatted'=> adfoin_mycred_format_amount( $previous_numeric, $point_type ),
            'point_type'                => adfoin_mycred_normalize_value( $point_type ),
            'point_type_label'          => adfoin_mycred_point_type_label( $point_type ),
            'update_timestamp'          => current_time( 'mysql' ),
        )
    );

    adfoin_mycred_dispatch( 'currentBalance', $payload );
}

add_action( 'mycred_user_got_promoted', 'adfoin_mycred_handle_rank_event', 10, 4 );

/**
 * Handle rank promotions.
 *
 * @param int         $user_id User ID.
 * @param int         $rank_id New rank ID.
 * @param object|null $results Rank result object.
 * @param string      $point_type Point type key.
 *
 * @return void
 */
function adfoin_mycred_handle_rank_event( $user_id, $rank_id, $results, $point_type ) {
    $user_context = adfoin_mycred_collect_user_context( $user_id );
    $rank         = function_exists( 'mycred_get_rank' ) ? mycred_get_rank( $rank_id ) : null;

    $previous_rank_id    = '';
    $previous_rank_title = '';

    if ( is_object( $results ) && ! empty( $results->current_id ) ) {
        $previous_rank_id = $results->current_id;
        $previous_rank    = function_exists( 'mycred_get_rank' ) ? mycred_get_rank( $results->current_id ) : null;

        if ( $previous_rank && isset( $previous_rank->post_title ) ) {
            $previous_rank_title = $previous_rank->post_title;
        }
    }

    $payload = array_merge(
        $user_context,
        array(
            'rank_id'             => adfoin_mycred_normalize_value( $rank_id ),
            'rank_title'          => adfoin_mycred_normalize_value( $rank->post_title ?? '' ),
            'rank_slug'           => adfoin_mycred_normalize_value( $rank->post_name ?? '' ),
            'rank_minimum'        => adfoin_mycred_normalize_value( $rank->minimum ?? '' ),
            'rank_maximum'        => adfoin_mycred_normalize_value( $rank->maximum ?? '' ),
            'previous_rank_id'    => adfoin_mycred_normalize_value( $previous_rank_id ),
            'previous_rank_title' => adfoin_mycred_normalize_value( $previous_rank_title ),
            'point_type'          => adfoin_mycred_normalize_value( $point_type ),
            'point_type_label'    => adfoin_mycred_point_type_label( $point_type ),
            'rank_event'          => 'promoted',
            'update_timestamp'    => current_time( 'mysql' ),
        )
    );

    adfoin_mycred_dispatch( 'earnsRank', $payload );
}

add_action( 'mycred_update_user_total_balance', 'adfoin_mycred_handle_total_balance', 10, 4 );

/**
 * Handle total balance updates.
 *
 * @param mixed       $total_balance Total balance.
 * @param int         $user_id       User ID.
 * @param string      $point_type    Point type.
 * @param object|null $reference     Reference object.
 *
 * @return void
 */
function adfoin_mycred_handle_total_balance( $total_balance, $user_id, $point_type, $reference ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $user_context = adfoin_mycred_collect_user_context( $user_id );

    $payload = array_merge(
        $user_context,
        array(
            'total_balance'           => adfoin_mycred_normalize_value( $total_balance ),
            'total_balance_formatted' => adfoin_mycred_format_amount( $total_balance, $point_type ),
            'point_type'              => adfoin_mycred_normalize_value( $point_type ),
            'point_type_label'        => adfoin_mycred_point_type_label( $point_type ),
            'update_timestamp'        => current_time( 'mysql' ),
        )
    );

    adfoin_mycred_dispatch( 'totalBalance', $payload );
}
