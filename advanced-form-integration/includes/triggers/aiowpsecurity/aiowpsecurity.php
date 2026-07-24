<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * All-In-One Security (AIOS) trigger — fires when the plugin locks out an
 * IP/range after too many failed login attempts.
 *
 * Confirmed against the plugin's own source
 * (classes/wp-security-user-login.php, lock_the_user() method):
 *
 *     do_action( 'aiowps_lockdown_event', $ip_range, $username );
 *
 * fires right after the lockdown record is inserted into the login-lockdown
 * DB table, before the plugin sends its own lockout notification email. Only
 * $ip_range and $username are passed by the hook itself — the username is
 * whatever was typed at the login form (often not a real account on
 * brute-force attempts), so the WP user lookup below is best-effort and can
 * come back empty.
 *
 * @link https://plugins.trac.wordpress.org/browser/all-in-one-wp-security-and-firewall/trunk/classes/wp-security-user-login.php
 */

add_action( 'plugins_loaded', 'adfoin_aiowpsecurity_register_hooks', 20 );

function adfoin_aiowpsecurity_register_hooks() {
    if ( ! class_exists( 'AIO_WP_Security' ) ) {
        return;
    }

    add_action( 'aiowps_lockdown_event', 'adfoin_aiowpsecurity_handle_lockout', 10, 2 );
}

// Get AIOS Triggers
function adfoin_aiowpsecurity_get_forms( $form_provider ) {
    if ( $form_provider !== 'aiowpsecurity' ) {
        return;
    }

    return array(
        'loginLockout' => __( 'Login Locked Out', 'advanced-form-integration' ),
    );
}

// Get AIOS Fields
function adfoin_aiowpsecurity_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'aiowpsecurity' || $form_id !== 'loginLockout' ) {
        return;
    }

    return array(
        'username'   => __( 'Attempted Username', 'advanced-form-integration' ),
        'user_email' => __( 'User Email (only if the username matches a real account)', 'advanced-form-integration' ),
        'ip_address' => __( 'IP Address / Range', 'advanced-form-integration' ),
        'lockout_time' => __( 'Lockout Time', 'advanced-form-integration' ),
    );
}

// Handle Login Lockout
function adfoin_aiowpsecurity_handle_lockout( $ip_range, $username ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'aiowpsecurity', 'loginLockout' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // Best-effort: most brute-force attempts use usernames that don't map to
    // a real account, so this frequently comes back empty — that's expected.
    $user = $username ? get_user_by( 'login', $username ) : false;

    $posted_data = array(
        'username'     => $username,
        'user_email'   => $user ? $user->user_email : '',
        'ip_address'   => $ip_range,
        'lockout_time' => current_time( 'mysql' ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
