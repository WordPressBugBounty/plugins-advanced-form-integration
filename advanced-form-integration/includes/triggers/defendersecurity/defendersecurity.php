<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Defender Security (WPMU DEV) trigger — fires when Defender locks out or
 * bans an IP after too many failed login attempts.
 *
 * Confirmed against the plugin's own source
 * (src/component/class-login-lockout.php, process_fail_attempt()):
 *
 *     do_action( 'wd_login_lockout', $model, $scenario );
 *
 * fires on both branches this file cares about: a banned-username hit
 * (SCENARIO_BAN) and a genuine attempt-threshold lockout
 * (SCENARIO_LOGIN_LOCKOUT) — not the earlier per-attempt logging that
 * happens on every single failed try. $model is a Lockout_Ip record with
 * real public properties (ip, status, lock_time, attempt, meta) confirmed
 * directly in src/model/class-lockout-ip.php; the attempted username isn't
 * one of them (the table is keyed by IP, not username), so it's not
 * available here — consistent with how the AIOS/Kadence Security triggers
 * built earlier in this session are IP-centric too.
 *
 * @link https://plugins.trac.wordpress.org/browser/defender-security/trunk/src/component/class-login-lockout.php
 * @link https://plugins.trac.wordpress.org/browser/defender-security/trunk/src/model/class-lockout-ip.php
 */

add_action( 'plugins_loaded', 'adfoin_defendersecurity_register_hooks', 20 );

function adfoin_defendersecurity_register_hooks() {
    if ( ! defined( 'DEFENDER_VERSION' ) ) {
        return;
    }

    add_action( 'wd_login_lockout', 'adfoin_defendersecurity_handle_lockout', 10, 2 );
}

// Get Defender Security Triggers
function adfoin_defendersecurity_get_forms( $form_provider ) {
    if ( $form_provider !== 'defendersecurity' ) {
        return;
    }

    return array(
        'loginLockout' => __( 'Login Locked Out / IP Banned', 'advanced-form-integration' ),
    );
}

// Get Defender Security Fields
function adfoin_defendersecurity_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'defendersecurity' || $form_id !== 'loginLockout' ) {
        return;
    }

    return array(
        'ip_address'     => __( 'IP Address', 'advanced-form-integration' ),
        'scenario'       => __( 'Scenario (ban or login_lockout)', 'advanced-form-integration' ),
        'attempt_count'  => __( 'Failed Attempt Count', 'advanced-form-integration' ),
        'lockout_message' => __( 'Lockout Message', 'advanced-form-integration' ),
        'lock_time'      => __( 'Lock Time (timestamp)', 'advanced-form-integration' ),
        'release_time'   => __( 'Release Time (timestamp, if temporary)', 'advanced-form-integration' ),
    );
}

// Handle Login Lockout / IP Ban
function adfoin_defendersecurity_handle_lockout( $model, $scenario ) {
    if ( ! is_object( $model ) || empty( $model->ip ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'defendersecurity', 'loginLockout' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'ip_address'       => $model->ip,
        'scenario'         => $scenario,
        'attempt_count'    => isset( $model->attempt ) ? $model->attempt : '',
        'lockout_message'  => isset( $model->lockout_message ) ? $model->lockout_message : '',
        'lock_time'         => isset( $model->lock_time ) ? $model->lock_time : '',
        'release_time'      => isset( $model->release_time ) ? $model->release_time : '',
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
