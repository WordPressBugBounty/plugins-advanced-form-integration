<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hustle (WPMU DEV) trigger — fires when a visitor submits one of Hustle's
 * popup/slide-in/embed optin forms, saved to its local subscriber list.
 *
 * Confirmed against the plugin's own source
 * (inc/front/hustle-module-front-ajax.php, handle_form() method):
 *
 *     do_action( 'hustle_form_submit_before_set_fields', $entry, $module_id, $field_data_array );
 *
 * Deliberately NOT the sibling `hustle_form_after_handle_submit` hook,
 * which fires unconditionally regardless of whether the submission
 * actually succeeded (the success/failure check happens after it fires).
 * This hook, by contrast, only fires inside the
 * `if ( $user_exists || $entry->save() )` branch — i.e. only after the
 * entry has already been confirmed saved to Hustle's "local_list"
 * integration. $field_data_array is a list of {name, value} pairs (the
 * same shape the plugin uses internally for hustle_ip/active_integrations),
 * flattened below into a plain map plus a best-effort 'email' field.
 *
 * Hustle popups/embeds are individual modules with no simple enumerable
 * "form" picker exposed as a public API, so — same reasoning as the
 * Jetpack/Otter Forms triggers — this is a single site-wide trigger rather
 * than a per-module picker; $module_id is still exposed as a field so
 * users can filter by it downstream if they have multiple optins.
 *
 * @link https://plugins.trac.wordpress.org/browser/wordpress-popup/trunk/inc/front/hustle-module-front-ajax.php
 */

add_action( 'plugins_loaded', 'adfoin_hustle_register_hooks', 20 );

function adfoin_hustle_register_hooks() {
    if ( ! class_exists( 'Hustle_Module_Model' ) ) {
        return;
    }

    add_action( 'hustle_form_submit_before_set_fields', 'adfoin_hustle_handle_submission', 10, 3 );
}

// Get Hustle Triggers
function adfoin_hustle_get_forms( $form_provider ) {
    if ( $form_provider !== 'hustle' ) {
        return;
    }

    return array(
        'optinSubmitted' => __( 'Optin/Popup Form Submitted (site-wide)', 'advanced-form-integration' ),
    );
}

// Get Hustle Fields
function adfoin_hustle_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'hustle' || $form_id !== 'optinSubmitted' ) {
        return;
    }

    return array(
        'module_id'       => __( 'Module ID (which popup/embed was submitted)', 'advanced-form-integration' ),
        'email'           => __( 'Email', 'advanced-form-integration' ),
        'all_fields_json' => __( 'All Submitted Fields (JSON, for fields not listed above)', 'advanced-form-integration' ),
    );
}

// Handle Optin Submitted
function adfoin_hustle_handle_submission( $entry, $module_id, $field_data_array ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'hustle', 'optinSubmitted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $all_fields = array();
    $email      = '';

    if ( is_array( $field_data_array ) ) {
        foreach ( $field_data_array as $field ) {
            if ( ! is_array( $field ) || empty( $field['name'] ) ) {
                continue;
            }

            $value = isset( $field['value'] ) ? $field['value'] : '';
            $all_fields[ $field['name'] ] = $value;

            if ( 'email' === $field['name'] && ! is_array( $value ) ) {
                $email = $value;
            }
        }
    }

    $posted_data = array(
        'module_id'       => $module_id,
        'email'           => $email,
        'all_fields_json' => wp_json_encode( $all_fields ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
