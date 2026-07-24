<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Two Factor trigger — fires right after a user successfully completes 2FA
 * login (a security-monitoring event, not a lead-capture one — e.g. for
 * alerting a Slack channel whenever an admin logs in with 2FA).
 *
 * Confirmed against the plugin's own source (class-two-factor-core.php),
 * with a proper docblock, and matching the plugin's own officially
 * documented "Actions & Filters" list in readme.txt:
 *
 *     do_action( 'two_factor_user_authenticated', $user, $provider );
 *
 * fires immediately after `wp_set_auth_cookie()` — genuinely successful
 * completion, not just an attempt. $provider exposes ->get_key() (e.g.
 * 'totp', 'email', 'backup_codes') identifying which 2FA method was used.
 *
 * @link https://plugins.trac.wordpress.org/browser/two-factor/trunk/class-two-factor-core.php
 * @link https://wordpress.org/plugins/two-factor/#actions-filters
 */

add_action( 'plugins_loaded', 'adfoin_twofactor_register_hooks', 20 );

function adfoin_twofactor_register_hooks() {
    if ( ! class_exists( 'Two_Factor_Core' ) ) {
        return;
    }

    add_action( 'two_factor_user_authenticated', 'adfoin_twofactor_handle_authenticated', 10, 2 );
}

// Get Two Factor Triggers
function adfoin_twofactor_get_forms( $form_provider ) {
    if ( $form_provider !== 'twofactor' ) {
        return;
    }

    return array(
        'userAuthenticated' => __( '2FA Login Successful', 'advanced-form-integration' ),
    );
}

// Get Two Factor Fields
function adfoin_twofactor_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'twofactor' || $form_id !== 'userAuthenticated' ) {
        return;
    }

    return array(
        'user_id'      => __( 'User ID', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email', 'advanced-form-integration' ),
        'user_login'   => __( 'Username', 'advanced-form-integration' ),
        'display_name' => __( 'Display Name', 'advanced-form-integration' ),
        'provider'     => __( '2FA Method Used (totp, email, backup_codes, etc.)', 'advanced-form-integration' ),
        'login_time'   => __( 'Login Time', 'advanced-form-integration' ),
    );
}

// Handle 2FA Login Successful
function adfoin_twofactor_handle_authenticated( $user, $provider ) {
    if ( ! is_object( $user ) || empty( $user->ID ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'twofactor', 'userAuthenticated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'user_id'      => $user->ID,
        'user_email'   => $user->user_email,
        'user_login'   => $user->user_login,
        'display_name' => $user->display_name,
        'provider'     => ( is_object( $provider ) && method_exists( $provider, 'get_key' ) ) ? $provider->get_key() : '',
        'login_time'   => current_time( 'mysql' ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
