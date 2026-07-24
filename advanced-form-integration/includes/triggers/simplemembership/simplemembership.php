<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Simple Membership trigger — fires when a visitor completes front-end
 * registration.
 *
 * Confirmed against the plugin's own source
 * (classes/class.swpm-front-registration.php):
 *
 *     do_action( 'swpm_front_end_registration_complete_user_data', $this->member_info );
 *
 * fires only after create_swpm_user(), prepare_and_create_wp_user_front_end(),
 * and send_reg_email() have all already succeeded — genuine registration
 * completion, not a validation failure. $member_info is a plain array
 * (email, user_name, first_name, last_name, membership_level, account_state,
 * member_since — confirmed directly from where each key is set in the same
 * file). The membership level ID is resolved to its display name via
 * SwpmPermission::get_instance($level)->get('alias'), the same accessor
 * the plugin's own registration code uses for the same purpose.
 *
 * @link https://plugins.trac.wordpress.org/browser/simple-membership/trunk/classes/class.swpm-front-registration.php
 */

add_action( 'plugins_loaded', 'adfoin_simplemembership_register_hooks', 20 );

function adfoin_simplemembership_register_hooks() {
    if ( ! defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
        return;
    }

    add_action( 'swpm_front_end_registration_complete_user_data', 'adfoin_simplemembership_handle_registration', 10, 1 );
}

// Get Simple Membership Triggers
function adfoin_simplemembership_get_forms( $form_provider ) {
    if ( $form_provider !== 'simplemembership' ) {
        return;
    }

    return array(
        'newMemberRegistered' => __( 'New Member Registered', 'advanced-form-integration' ),
    );
}

// Get Simple Membership Fields
function adfoin_simplemembership_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'simplemembership' || $form_id !== 'newMemberRegistered' ) {
        return;
    }

    return array(
        'user_name'        => __( 'Username', 'advanced-form-integration' ),
        'email'            => __( 'Email', 'advanced-form-integration' ),
        'first_name'       => __( 'First Name', 'advanced-form-integration' ),
        'last_name'        => __( 'Last Name', 'advanced-form-integration' ),
        'membership_level' => __( 'Membership Level Name', 'advanced-form-integration' ),
        'account_state'    => __( 'Account State', 'advanced-form-integration' ),
        'member_since'     => __( 'Member Since', 'advanced-form-integration' ),
    );
}

// Handle New Member Registered
function adfoin_simplemembership_handle_registration( $member_info ) {
    if ( ! is_array( $member_info ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'simplemembership', 'newMemberRegistered' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $level_name = '';

    if ( ! empty( $member_info['membership_level'] ) && class_exists( 'SwpmPermission' ) ) {
        $level = SwpmPermission::get_instance( $member_info['membership_level'] );

        if ( is_object( $level ) && method_exists( $level, 'get' ) ) {
            $level_name = $level->get( 'alias' );
        }
    }

    $posted_data = array(
        'user_name'        => isset( $member_info['user_name'] ) ? $member_info['user_name'] : '',
        'email'            => isset( $member_info['email'] ) ? $member_info['email'] : '',
        'first_name'       => isset( $member_info['first_name'] ) ? $member_info['first_name'] : '',
        'last_name'        => isset( $member_info['last_name'] ) ? $member_info['last_name'] : '',
        'membership_level' => $level_name,
        'account_state'    => isset( $member_info['account_state'] ) ? $member_info['account_state'] : '',
        'member_since'     => isset( $member_info['member_since'] ) ? $member_info['member_since'] : '',
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
