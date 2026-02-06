<?php

// Get wpForo Triggers
function adfoin_wpforo_get_triggers( $form_provider ) {
    if ( $form_provider !== 'wpforo' ) {
        return;
    }

    return [
        'createTopic' => __( 'User creates a topic', 'advanced-form-integration' ),
        'createReply' => __( 'User replies to a topic', 'advanced-form-integration' ),
    ];
}

// Get wpForo Fields
function adfoin_wpforo_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpforo' ) {
        return;
    }

    $fields = [];

    if ( $form_id === 'createTopic' ) {
        $fields = [
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title' => __( 'Topic Title', 'advanced-form-integration' ),
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'createReply' ) {
        $fields = [
            'reply_id' => __( 'Reply ID', 'advanced-form-integration' ),
            'reply_title' => __( 'Reply Title', 'advanced-form-integration' ),
            'reply_content' => __( 'Reply Content', 'advanced-form-integration' ),
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title' => __( 'Topic Title', 'advanced-form-integration' ),
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Handle Topic Creation
add_action( 'wpforo_after_add_topic', 'adfoin_wpforo_handle_create_topic', 10, 2 );
function adfoin_wpforo_handle_create_topic( $topic, $forum ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'createTopic' );

    // if ( empty( $saved_records ) ) {
    //     return;
    // }

    $user_info = get_userdata( $topic['userid'] );

    $posted_data = [
        'topic_id' => $topic['topicid'],
        'topic_title' => $topic['title'],
        'forum_id' => $forum['forumid'],
        'forum_name' => $forum['title'],
        'user_id' => $topic['userid'],
        'user_name' => $user_info->user_login,
        'user_email' => $user_info->user_email,
    ];

    $integration->send( $saved_records, $posted_data );
}

// Handle Reply Creation
add_action( 'wpforo_after_add_post', 'adfoin_wpforo_handle_create_reply', 10, 3 );
function adfoin_wpforo_handle_create_reply( $post, $topic, $forum ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'createReply' );

    // if ( empty( $saved_records ) ) {
    //     return;
    // }

    $user_info = get_userdata( $post['userid'] );

    $posted_data = [
        'reply_id' => $post['postid'],
        'reply_title' => $post['title'],
        'reply_content' => $post['body'],
        'topic_id' => $topic['topicid'],
        'topic_title' => $topic['title'],
        'forum_id' => $post['forumid'],
        'forum_name' => $forum['title'],
        'user_id' => $post['userid'],
        'user_name' => $user_info->user_login,
        'user_email' => $user_info->user_email,
    ];

    $integration->send( $saved_records, $posted_data );
}