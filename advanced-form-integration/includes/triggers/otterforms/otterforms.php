<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Otter Blocks (ThemeIsle) Form trigger — fires on a successful native Otter
 * form-block submission.
 *
 * Confirmed against the plugin's own source
 * (inc/server/class-form-server.php, frontend() method):
 *
 *     do_action( 'otter_form_after_submit', $form_data );
 *
 * fires only on the success path — after anti-spam/validation pass, the
 * submission Record is saved, and the configured provider has been called —
 * never on a failed/rejected submission. $form_data is a Form_Data_Request
 * instance (inc/integrations/api/form-request-data.php); this exact hook is
 * already used by at least one other real plugin (WP Maintenance Mode /
 * LightStart's otter_add_subscriber()), corroborating it as a genuine,
 * stable public extension point rather than an internal implementation
 * detail.
 *
 * Otter forms are individual blocks embedded in arbitrary posts/pages (like
 * Elementor Pro's form widget) with no separate, reliably-enumerable "list
 * of forms" — so, same reasoning as the Jetpack Forms trigger, this is a
 * single site-wide "Any Otter Form Submitted" event rather than a form
 * picker. get_fields() returns each input as {label, type, value, ...} —
 * reliable enough to flatten generically, plus a couple of best-effort
 * aliases (email via the class's own get_first_email_from_input_fields()
 * helper; name/message by label/type matching).
 *
 * @link https://plugins.trac.wordpress.org/browser/otter-blocks/trunk/inc/server/class-form-server.php
 * @link https://plugins.trac.wordpress.org/browser/otter-blocks/trunk/inc/integrations/api/form-request-data.php
 */

add_action( 'plugins_loaded', 'adfoin_otterforms_register_hooks', 20 );

function adfoin_otterforms_register_hooks() {
    if ( ! defined( 'OTTER_BLOCKS_VERSION' ) ) {
        return;
    }

    add_action( 'otter_form_after_submit', 'adfoin_otterforms_handle_submission', 10, 1 );
}

// Get Otter Forms Triggers
function adfoin_otterforms_get_forms( $form_provider ) {
    if ( $form_provider !== 'otterforms' ) {
        return;
    }

    return array(
        'anyForm' => __( 'Any Otter Form Submitted (site-wide)', 'advanced-form-integration' ),
    );
}

// Get Otter Forms Fields
function adfoin_otterforms_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'otterforms' || $form_id !== 'anyForm' ) {
        return;
    }

    return array(
        'email'            => __( 'Email', 'advanced-form-integration' ),
        'name'              => __( 'Name (best-effort, from a text field labeled "name")', 'advanced-form-integration' ),
        'message'           => __( 'Message (best-effort, from a textarea field)', 'advanced-form-integration' ),
        'form_option_id'    => __( 'Form Option ID (identifies which form config was used)', 'advanced-form-integration' ),
        'record_id'         => __( 'Form Record ID (Otter\'s own saved submission, if any)', 'advanced-form-integration' ),
        'all_fields_json'   => __( 'All Fields (JSON, for fields not listed above)', 'advanced-form-integration' ),
    );
}

// Handle Otter Form Submission
function adfoin_otterforms_handle_submission( $form_data ) {
    if ( ! is_object( $form_data ) || ! method_exists( $form_data, 'get_fields' ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'otterforms', 'anyForm' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $fields = $form_data->get_fields();

    $all_fields    = array();
    $name_guess    = '';
    $message_guess = '';

    if ( is_array( $fields ) ) {
        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            $label = isset( $field['label'] ) ? $field['label'] : '';
            $type  = isset( $field['type'] ) ? $field['type'] : '';
            $value = isset( $field['value'] ) ? $field['value'] : '';

            if ( $label ) {
                $all_fields[ $label ] = $value;
            }

            $label_lower = strtolower( (string) $label );
            if ( 'textarea' === $type ) {
                $message_guess = $value;
            } elseif ( 'email' !== $type && false !== strpos( $label_lower, 'name' ) ) {
                $name_guess = $value;
            }
        }
    }

    $posted_data = array(
        'email'           => method_exists( $form_data, 'get_first_email_from_input_fields' ) ? $form_data->get_first_email_from_input_fields() : '',
        'name'            => $name_guess,
        'message'         => $message_guess,
        'form_option_id'  => method_exists( $form_data, 'get_form_option_id' ) ? $form_data->get_form_option_id() : '',
        'record_id'       => method_exists( $form_data, 'get_record_id' ) && $form_data->has_record_id() ? $form_data->get_record_id() : '',
        'all_fields_json' => wp_json_encode( $all_fields ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
