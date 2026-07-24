<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Temporary Login Without Password trigger — fires when a shared,
 * self-expiring temporary login link is actually used (a security/ops
 * event — e.g. alerting a Slack channel whenever a developer/editor uses
 * the temp-access link a site owner shared with them).
 *
 * Confirmed against the plugin's own source
 * (public/class-wp-temporary-login-without-password-public.php):
 *
 *     do_action( 'wtlwp_after_login_success', $temporary_user_id );
 *
 * fires only on the successful-login branch (`$do_login` stayed true after
 * the pre-check filter and any existing-session handling), right after the
 * plugin sets the auth cookie and bumps its own `_wtlwp_login_count` user
 * meta — which is read back below to expose how many times this temp
 * account has been used in total.
 *
 * @link https://plugins.trac.wordpress.org/browser/temporary-login-without-password/trunk/public/class-wp-temporary-login-without-password-public.php
 */

add_action( 'plugins_loaded', 'adfoin_temporarylogin_register_hooks', 20 );

function adfoin_temporarylogin_register_hooks() {
    if ( ! defined( 'WTLWP_PLUGIN_VERSION' ) ) {
        return;
    }

    add_action( 'wtlwp_after_login_success', 'adfoin_temporarylogin_handle_login', 10, 1 );
}

// Get Temporary Login Triggers
function adfoin_temporarylogin_get_forms( $form_provider ) {
    if ( $form_provider !== 'temporarylogin' ) {
        return;
    }

    return array(
        'tempLoginUsed' => __( 'Temporary Login Used', 'advanced-form-integration' ),
    );
}

// Get Temporary Login Fields
function adfoin_temporarylogin_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'temporarylogin' || $form_id !== 'tempLoginUsed' ) {
        return;
    }

    return array(
        'user_id'      => __( 'Temporary User ID', 'advanced-form-integration' ),
        'user_email'   => __( 'Temporary User Email', 'advanced-form-integration' ),
        'user_login'   => __( 'Temporary Username', 'advanced-form-integration' ),
        'display_name' => __( 'Display Name', 'advanced-form-integration' ),
        'login_count'  => __( 'Total Login Count (for this temp account)', 'advanced-form-integration' ),
        'login_time'   => __( 'Login Time', 'advanced-form-integration' ),
    );
}

// Handle Temporary Login Used
function adfoin_temporarylogin_handle_login( $temporary_user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'temporarylogin', 'tempLoginUsed' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user = get_userdata( $temporary_user_id );

    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'user_id'      => $temporary_user_id,
        'user_email'   => $user->user_email,
        'user_login'   => $user->user_login,
        'display_name' => $user->display_name,
        'login_count'  => get_user_meta( $temporary_user_id, '_wtlwp_login_count', true ),
        'login_time'   => current_time( 'mysql' ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
