<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Nextend Social Login and Register trigger — fires when a new WordPress
 * account is created via a social provider (Facebook, Google, X, etc.).
 *
 * Confirmed against the plugin's own source (includes/user.php,
 * registerComplete() method), and matching the plugin's own official
 * developer hooks documentation:
 *
 *     do_action( 'nsl_register_new_user', $user_id, $this->provider );
 *
 * fires only on a genuine NEW registration (registerComplete() already
 * bailed above if $user_id was an error/zero) — deliberately not using the
 * sibling `nsl_login` hook, which also fires for every *returning* social
 * login, to avoid re-dispatching the same "new lead" event on repeat visits.
 * $provider exposes ->getId() (e.g. 'facebook', 'google', 'twitter') — the
 * user's own email/name are read back via get_userdata() since $user_id is
 * already a real WP account by the time this fires.
 *
 * @link https://plugins.trac.wordpress.org/browser/nextend-facebook-connect/trunk/includes/user.php
 * @link https://social-login.nextendweb.com/documentation/for-developers/hooks/
 */

add_action( 'plugins_loaded', 'adfoin_nextendsociallogin_register_hooks', 20 );

function adfoin_nextendsociallogin_register_hooks() {
    if ( ! class_exists( 'NextendSocialLogin' ) ) {
        return;
    }

    add_action( 'nsl_register_new_user', 'adfoin_nextendsociallogin_handle_new_user', 10, 2 );
}

// Get Nextend Social Login Triggers
function adfoin_nextendsociallogin_get_forms( $form_provider ) {
    if ( $form_provider !== 'nextendsociallogin' ) {
        return;
    }

    return array(
        'newUserRegistered' => __( 'New User Registered (via Social Login)', 'advanced-form-integration' ),
    );
}

// Get Nextend Social Login Fields
function adfoin_nextendsociallogin_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'nextendsociallogin' || $form_id !== 'newUserRegistered' ) {
        return;
    }

    return array(
        'user_id'      => __( 'User ID', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email', 'advanced-form-integration' ),
        'first_name'   => __( 'First Name', 'advanced-form-integration' ),
        'last_name'    => __( 'Last Name', 'advanced-form-integration' ),
        'display_name' => __( 'Display Name', 'advanced-form-integration' ),
        'provider'     => __( 'Social Provider (facebook, google, etc.)', 'advanced-form-integration' ),
    );
}

// Handle New User Registered
function adfoin_nextendsociallogin_handle_new_user( $user_id, $provider ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'nextendsociallogin', 'newUserRegistered' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'user_id'      => $user_id,
        'user_email'   => $user->user_email,
        'first_name'   => $user->first_name,
        'last_name'    => $user->last_name,
        'display_name' => $user->display_name,
        'provider'     => ( is_object( $provider ) && method_exists( $provider, 'getId' ) ) ? $provider->getId() : '',
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
