<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Strong Testimonials trigger — fires when a visitor submits a new
 * testimonial via the plugin's front-end submission form.
 *
 * Confirmed against the plugin's own source
 * (includes/class-strong-testimonials-form.php):
 *
 *     do_action( 'wpmtst_new_testimonial_submit', $form_values, $form_name );
 *
 * fires only after the testimonial post/meta/attachments have all saved
 * successfully and form validation produced zero errors (the code comment
 * right above the save reads "Post inserted successfully, carry on.").
 * $form_values is a flat array merging the testimonial's WP post fields
 * (post_title is the submitter's name/subject line, post_content is the
 * testimonial text, 'id' is the new testimonial post ID) with whatever
 * custom fields the site owner configured on the submission form (email,
 * rating, company, etc. — these vary per site, so they're only reliably
 * available via the JSON fallback field below).
 *
 * @link https://plugins.trac.wordpress.org/browser/strong-testimonials/trunk/includes/class-strong-testimonials-form.php
 */

add_action( 'plugins_loaded', 'adfoin_strongtestimonials_register_hooks', 20 );

function adfoin_strongtestimonials_register_hooks() {
    if ( ! defined( 'WPMTST_VERSION' ) ) {
        return;
    }

    add_action( 'wpmtst_new_testimonial_submit', 'adfoin_strongtestimonials_handle_submission', 10, 2 );
}

// Get Strong Testimonials Triggers
function adfoin_strongtestimonials_get_forms( $form_provider ) {
    if ( $form_provider !== 'strongtestimonials' ) {
        return;
    }

    return array(
        'testimonialSubmitted' => __( 'New Testimonial Submitted', 'advanced-form-integration' ),
    );
}

// Get Strong Testimonials Fields
function adfoin_strongtestimonials_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'strongtestimonials' || $form_id !== 'testimonialSubmitted' ) {
        return;
    }

    return array(
        'testimonial_id'  => __( 'Testimonial ID', 'advanced-form-integration' ),
        'name'            => __( 'Name (post title)', 'advanced-form-integration' ),
        'testimonial'     => __( 'Testimonial Text (post content)', 'advanced-form-integration' ),
        'category'        => __( 'Category', 'advanced-form-integration' ),
        'form_name'       => __( 'Submission Form Name', 'advanced-form-integration' ),
        'all_fields_json' => __( 'All Submitted Fields (JSON, for custom fields like email/rating/company)', 'advanced-form-integration' ),
    );
}

// Handle New Testimonial Submitted
function adfoin_strongtestimonials_handle_submission( $form_values, $form_name ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'strongtestimonials', 'testimonialSubmitted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! is_array( $form_values ) ) {
        return;
    }

    $posted_data = array(
        'testimonial_id'  => isset( $form_values['id'] ) ? $form_values['id'] : '',
        'name'            => isset( $form_values['post_title'] ) ? $form_values['post_title'] : '',
        'testimonial'     => isset( $form_values['post_content'] ) ? $form_values['post_content'] : '',
        'category'        => isset( $form_values['category'] ) ? $form_values['category'] : '',
        'form_name'       => $form_name,
        'all_fields_json' => wp_json_encode( $form_values ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
