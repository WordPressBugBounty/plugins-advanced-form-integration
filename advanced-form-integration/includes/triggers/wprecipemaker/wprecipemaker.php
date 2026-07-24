<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP Recipe Maker trigger — fires the first time a visitor rates a recipe.
 *
 * Confirmed against the plugin's own source
 * (includes/public/class-wprm-rating-database.php), with a code comment
 * explicitly stating third-party intent ("Trigger action for gamification
 * plugins"):
 *
 *     do_action( 'wprm_rating_first_time', $rating );
 *
 * fires only when no existing rating from this user/IP for this recipe was
 * found (a genuine first-time vote) — re-rating the same recipe later
 * deletes and replaces the old row without re-firing this hook, so this
 * naturally avoids re-dispatching on every re-rate. $rating is a plain
 * array: recipe_id, user_id (0 for guests), ip, rating (0-5), comment_id,
 * approved.
 *
 * @link https://plugins.trac.wordpress.org/browser/wp-recipe-maker/trunk/includes/public/class-wprm-rating-database.php
 */

add_action( 'plugins_loaded', 'adfoin_wprecipemaker_register_hooks', 20 );

function adfoin_wprecipemaker_register_hooks() {
    if ( ! defined( 'WPRM_VERSION' ) ) {
        return;
    }

    add_action( 'wprm_rating_first_time', 'adfoin_wprecipemaker_handle_rating', 10, 1 );
}

// Get WP Recipe Maker Triggers
function adfoin_wprecipemaker_get_forms( $form_provider ) {
    if ( $form_provider !== 'wprecipemaker' ) {
        return;
    }

    return array(
        'recipeRated' => __( 'Recipe Rated (First Time)', 'advanced-form-integration' ),
    );
}

// Get WP Recipe Maker Fields
function adfoin_wprecipemaker_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wprecipemaker' || $form_id !== 'recipeRated' ) {
        return;
    }

    return array(
        'recipe_id'    => __( 'Recipe ID', 'advanced-form-integration' ),
        'recipe_title' => __( 'Recipe Title', 'advanced-form-integration' ),
        'recipe_url'   => __( 'Recipe URL', 'advanced-form-integration' ),
        'rating'       => __( 'Rating (0-5)', 'advanced-form-integration' ),
        'user_id'      => __( 'User ID (0 for guests)', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email (only if logged in)', 'advanced-form-integration' ),
    );
}

// Handle Recipe Rated
function adfoin_wprecipemaker_handle_rating( $rating ) {
    if ( ! is_array( $rating ) || empty( $rating['recipe_id'] ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wprecipemaker', 'recipeRated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $recipe_id = $rating['recipe_id'];
    $user_id   = isset( $rating['user_id'] ) ? $rating['user_id'] : 0;
    $user      = $user_id ? get_userdata( $user_id ) : false;

    $posted_data = array(
        'recipe_id'    => $recipe_id,
        'recipe_title' => get_the_title( $recipe_id ),
        'recipe_url'   => get_permalink( $recipe_id ),
        'rating'       => isset( $rating['rating'] ) ? $rating['rating'] : '',
        'user_id'      => $user_id,
        'user_email'   => $user ? $user->user_email : '',
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
