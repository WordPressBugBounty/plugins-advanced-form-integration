<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * wpDiscuz trigger — fires when a visitor successfully posts a comment
 * through wpDiscuz's AJAX comment form.
 *
 * Confirmed against the plugin's own source (class.WpdiscuzCore.php):
 *
 *     do_action( 'wpdiscuz_after_comment_post', $newComment, $currentUser );
 *
 * fires only after the new comment has already passed the trash/spam
 * checks (those branches `wp_send_json_error()` and return earlier in the
 * same method) — genuinely posted/approved-or-pending, not rejected.
 * $newComment is a real WP_Comment object; $currentUser is the current
 * WP_User (ID is 0 for guest commenters, which is the common case here).
 *
 * @link https://plugins.trac.wordpress.org/browser/wpdiscuz/trunk/class.WpdiscuzCore.php
 */

add_action( 'plugins_loaded', 'adfoin_wpdiscuz_register_hooks', 20 );

function adfoin_wpdiscuz_register_hooks() {
    if ( ! class_exists( 'WpdiscuzCore' ) ) {
        return;
    }

    add_action( 'wpdiscuz_after_comment_post', 'adfoin_wpdiscuz_handle_comment', 10, 2 );
}

// Get wpDiscuz Triggers
function adfoin_wpdiscuz_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpdiscuz' ) {
        return;
    }

    return array(
        'commentPosted' => __( 'Comment Posted', 'advanced-form-integration' ),
    );
}

// Get wpDiscuz Fields
function adfoin_wpdiscuz_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpdiscuz' || $form_id !== 'commentPosted' ) {
        return;
    }

    return array(
        'comment_id'      => __( 'Comment ID', 'advanced-form-integration' ),
        'post_id'         => __( 'Post ID', 'advanced-form-integration' ),
        'post_title'      => __( 'Post Title', 'advanced-form-integration' ),
        'author_name'     => __( 'Author Name', 'advanced-form-integration' ),
        'author_email'    => __( 'Author Email', 'advanced-form-integration' ),
        'comment_content' => __( 'Comment Content', 'advanced-form-integration' ),
        'user_id'         => __( 'User ID (0 for guests)', 'advanced-form-integration' ),
    );
}

// Handle Comment Posted
function adfoin_wpdiscuz_handle_comment( $new_comment, $current_user ) {
    if ( ! is_object( $new_comment ) || empty( $new_comment->comment_ID ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpdiscuz', 'commentPosted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'comment_id'      => $new_comment->comment_ID,
        'post_id'         => $new_comment->comment_post_ID,
        'post_title'      => get_the_title( $new_comment->comment_post_ID ),
        'author_name'     => $new_comment->comment_author,
        'author_email'    => $new_comment->comment_author_email,
        'comment_content' => $new_comment->comment_content,
        'user_id'         => ( is_object( $current_user ) && ! empty( $current_user->ID ) ) ? $current_user->ID : 0,
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
