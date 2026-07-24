<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Email Subscribers & Newsletters trigger — fires once a visitor is a
 * confirmed subscriber (covers both opt-in modes).
 *
 * Confirmed against the plugin's own source. There are two call sites for
 * the same hook, both firing only on genuine confirmed-subscriber status
 * (not a pending double-optin):
 *
 *   - Single opt-in: lite/includes/classes/class-es-handle-subscription.php
 *     fires `ig_es_contact_subscribed( $merge_tags )` immediately after
 *     form submission (the sibling `ig_es_contact_unconfirmed` fires
 *     instead when double opt-in is pending).
 *   - Double opt-in: lite/public/class-email-subscribers-public.php fires
 *     the same `ig_es_contact_subscribed( $data )` after the visitor clicks
 *     the confirmation link in their inbox.
 *
 * Both call sites build the same shape — email, contact_id, name,
 * first_name, last_name, list_name, list_ids — so a single handler covers
 * either opt-in mode correctly.
 *
 * @link https://plugins.trac.wordpress.org/browser/email-subscribers/trunk/lite/includes/classes/class-es-handle-subscription.php
 * @link https://plugins.trac.wordpress.org/browser/email-subscribers/trunk/lite/public/class-email-subscribers-public.php
 */

add_action( 'plugins_loaded', 'adfoin_emailsubscribers_register_hooks', 20 );

function adfoin_emailsubscribers_register_hooks() {
    if ( ! function_exists( 'ES' ) ) {
        return;
    }

    add_action( 'ig_es_contact_subscribed', 'adfoin_emailsubscribers_handle_subscribed', 10, 1 );
}

// Get Email Subscribers Triggers
function adfoin_emailsubscribers_get_forms( $form_provider ) {
    if ( $form_provider !== 'emailsubscribers' ) {
        return;
    }

    return array(
        'contactSubscribed' => __( 'Contact Subscribed', 'advanced-form-integration' ),
    );
}

// Get Email Subscribers Fields
function adfoin_emailsubscribers_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'emailsubscribers' || $form_id !== 'contactSubscribed' ) {
        return;
    }

    return array(
        'contact_id' => __( 'Contact ID', 'advanced-form-integration' ),
        'email'      => __( 'Email', 'advanced-form-integration' ),
        'name'       => __( 'Full Name', 'advanced-form-integration' ),
        'first_name' => __( 'First Name', 'advanced-form-integration' ),
        'last_name'  => __( 'Last Name', 'advanced-form-integration' ),
        'list_name'  => __( 'List Name(s)', 'advanced-form-integration' ),
        'list_ids'   => __( 'List ID(s)', 'advanced-form-integration' ),
    );
}

// Handle Contact Subscribed
function adfoin_emailsubscribers_handle_subscribed( $data ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'emailsubscribers', 'contactSubscribed' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! is_array( $data ) ) {
        return;
    }

    $list_ids = isset( $data['list_ids'] ) ? $data['list_ids'] : '';

    if ( is_array( $list_ids ) ) {
        $list_ids = implode( ',', $list_ids );
    }

    $posted_data = array(
        'contact_id' => isset( $data['contact_id'] ) ? $data['contact_id'] : '',
        'email'      => isset( $data['email'] ) ? $data['email'] : '',
        'name'       => isset( $data['name'] ) ? $data['name'] : '',
        'first_name' => isset( $data['first_name'] ) ? $data['first_name'] : '',
        'last_name'  => isset( $data['last_name'] ) ? $data['last_name'] : '',
        'list_name'  => isset( $data['list_name'] ) ? $data['list_name'] : '',
        'list_ids'   => $list_ids,
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
