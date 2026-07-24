<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * FormLayer trigger — fires on a successful form submission.
 *
 * Confirmed against the plugin's own source (main/ajax.php), which even
 * carries an explicit code comment confirming third-party-integration
 * intent ("Trigger integrations via action hook"):
 *
 *     do_action( 'formlayer_after_submission', $form_id, $submitted_data, $entry_id );
 *
 * fires after the notification email step, with $form_id already resolved
 * to the real `formlayer_form` post ID (not the public-facing display ID —
 * confirmed by tracing `$form_id = Util::get_post_id_by_display_id(...)`
 * earlier in the same handler). $submitted_data is a flat field-name-keyed
 * array (the same field-name convention `Util::get_field_name()` uses),
 * so it can be dispatched directly without any reshaping.
 *
 * Forms are a real, listable custom post type (`formlayer_form`), and
 * `Util::get_form_field_labels($form_id)` gives the exact field_name→label
 * map for a given form, so this gets a proper per-form picker.
 *
 * @link https://plugins.trac.wordpress.org/browser/formlayer/trunk/main/ajax.php
 * @link https://plugins.trac.wordpress.org/browser/formlayer/trunk/main/util.php
 */

add_action( 'plugins_loaded', 'adfoin_formlayer_register_hooks', 20 );

function adfoin_formlayer_register_hooks() {
    if ( ! defined( 'FORMLAYER_VERSION' ) ) {
        return;
    }

    add_action( 'formlayer_after_submission', 'adfoin_formlayer_handle_submission', 10, 3 );
}

// Get FormLayer Forms
function adfoin_formlayer_get_forms( $form_provider ) {
    if ( $form_provider !== 'formlayer' ) {
        return;
    }

    $forms = array();

    $posts = get_posts( array(
        'post_type'      => 'formlayer_form',
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'draft' ),
    ) );

    foreach ( $posts as $post ) {
        $forms[ $post->ID ] = $post->post_title;
    }

    return $forms;
}

// Get FormLayer Fields
function adfoin_formlayer_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'formlayer' || ! $form_id ) {
        return;
    }

    $fields = array();

    if ( class_exists( '\FormLayer\Util' ) ) {
        $fields = \FormLayer\Util::get_form_field_labels( $form_id );
    }

    $fields['entry_id'] = __( 'Entry ID', 'advanced-form-integration' );
    $fields['form_id']  = __( 'Form ID', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

// Handle Form Submission
function adfoin_formlayer_handle_submission( $form_id, $submitted_data, $entry_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'formlayer', (string) $form_id );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = is_array( $submitted_data ) ? $submitted_data : array();

    $post               = adfoin_get_post_object();
    $special_tag_values = adfoin_get_special_tags_values( $post );

    if ( is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $posted_data['entry_id'] = $entry_id;
    $posted_data['form_id']  = $form_id;

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
