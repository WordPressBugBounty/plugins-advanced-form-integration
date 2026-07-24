<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * kk Star Ratings trigger — fires when a visitor casts a star rating on a
 * post/page.
 *
 * Confirmed against the plugin's own source
 * (src/core/actions/save.php), which explicitly documents this as a
 * long-standing, intentionally-preserved back-compat extension point:
 *
 *     do_action( 'kksr_rate', $id, $outOf5, $fingerprint );
 *
 * ($outOf5 is the star rating already normalized to a 0-5 scale;
 * $fingerprint is an anonymous browser fingerprint, not a real user
 * identity — this plugin's ratings are anonymous by design, no
 * login/email involved). The sibling `kksr_vote` hook carries the same
 * event with a different/legacy argument order — `kksr_rate` was chosen
 * here for its simpler, stable signature.
 *
 * @link https://plugins.trac.wordpress.org/browser/kk-star-ratings/trunk/src/core/actions/save.php
 */

add_action( 'plugins_loaded', 'adfoin_kkstarratings_register_hooks', 20 );

function adfoin_kkstarratings_register_hooks() {
    if ( ! defined( 'KK_STAR_RATINGS' ) ) {
        return;
    }

    add_action( 'kksr_rate', 'adfoin_kkstarratings_handle_rate', 10, 3 );
}

// Get kk Star Ratings Triggers
function adfoin_kkstarratings_get_forms( $form_provider ) {
    if ( $form_provider !== 'kkstarratings' ) {
        return;
    }

    return array(
        'postRated' => __( 'Post Rated', 'advanced-form-integration' ),
    );
}

// Get kk Star Ratings Fields
function adfoin_kkstarratings_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'kkstarratings' || $form_id !== 'postRated' ) {
        return;
    }

    return array(
        'post_id'     => __( 'Post ID', 'advanced-form-integration' ),
        'post_title'  => __( 'Post Title', 'advanced-form-integration' ),
        'post_url'    => __( 'Post URL', 'advanced-form-integration' ),
        'rating'      => __( 'Rating (out of 5)', 'advanced-form-integration' ),
        'fingerprint' => __( 'Visitor Fingerprint (anonymous)', 'advanced-form-integration' ),
    );
}

// Handle Post Rated
function adfoin_kkstarratings_handle_rate( $id, $out_of_5, $fingerprint ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'kkstarratings', 'postRated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'post_id'     => $id,
        'post_title'  => get_the_title( $id ),
        'post_url'    => get_permalink( $id ),
        'rating'      => $out_of_5,
        'fingerprint' => $fingerprint,
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
