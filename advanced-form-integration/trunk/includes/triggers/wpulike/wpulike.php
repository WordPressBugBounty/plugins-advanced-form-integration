<?php

// Get WP ULike Triggers
function adfoin_wpulike_get_triggers( $form_provider ) {
    if ( $form_provider !== 'wpulike' ) {
        return;
    }

    $triggers = array(
        'likePost' => __( 'User likes a post', 'advanced-form-integration' ),
        'likeComment' => __( 'User likes a comment', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get WP ULike Fields
function adfoin_wpulike_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpulike' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'likePost' ) {
        $fields = [
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'post_id' => __( 'Post ID', 'advanced-form-integration' ),
            'post_title' => __( 'Post Title', 'advanced-form-integration' ),
            'status' => __( 'Like Status', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'likeComment' ) {
        $fields = [
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'comment_id' => __( 'Comment ID', 'advanced-form-integration' ),
            'post_id' => __( 'Post ID', 'advanced-form-integration' ),
            'comment_content' => __( 'Comment Content', 'advanced-form-integration' ),
            'status' => __( 'Like Status', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_wpulike_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_id'] = $user_id;
        $user_data['user_email'] = $user->user_email;
    }

    return $user_data;
}

// Handle Post Like
add_action( 'wp_ulike_after_process', 'adfoin_wpulike_handle_post_like', 10, 4 );
function adfoin_wpulike_handle_post_like( $id, $key, $user_id, $status ) {
    if ( $key !== '_liked' || $status !== 'like' ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpulike', 'likePost' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'user_id' => $user_id,
        'post_id' => $id,
        'post_title' => get_the_title( $id ),
        'status' => $status,
    ];

    $integration->send( $saved_records, $posted_data );
}

// Handle Comment Like
add_action( 'wp_ulike_after_process', 'adfoin_wpulike_handle_comment_like', 10, 4 );
function adfoin_wpulike_handle_comment_like( $id, $key, $user_id, $status ) {
    if ( $key !== '_commentliked' || $status !== 'like' ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpulike', 'likeComment' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $comment = get_comment( $id );

    $posted_data = [
        'user_id' => $user_id,
        'comment_id' => $id,
        'post_id' => $comment->comment_post_ID,
        'comment_content' => $comment->comment_content,
        'status' => $status,
    ];

    $integration->send( $saved_records, $posted_data );
}