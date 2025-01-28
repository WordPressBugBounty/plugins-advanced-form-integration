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
        ];
    } elseif ( $form_id === 'createReply' ) {
        $fields = [
            'reply_id' => __( 'Reply ID', 'advanced-form-integration' ),
            'reply_content' => __( 'Reply Content', 'advanced-form-integration' ),
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Send Trigger Data
function adfoin_wpforo_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", [
                'data' => [
                    'record' => $record,
                    'posted_data' => $posted_data,
                ],
            ] );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

// Handle Topic Creation
add_action( 'wpforo_after_add_topic', 'adfoin_wpforo_handle_create_topic', 10, 1 );
function adfoin_wpforo_handle_create_topic( $topic ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'createTopic' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'topic_id' => $topic['topicid'],
        'topic_title' => $topic['title'],
        'forum_id' => $topic['forumid'],
        'forum_name' => wpforo_forum_name( $topic['forumid'] ),
        'user_id' => $topic['userid'],
    ];

    adfoin_wpforo_send_trigger_data( $saved_records, $posted_data );
}

// Handle Reply Creation
add_action( 'wpforo_after_add_post', 'adfoin_wpforo_handle_create_reply', 10, 2 );
function adfoin_wpforo_handle_create_reply( $post, $topic ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'createReply' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'reply_id' => $post['postid'],
        'reply_content' => $post['body'],
        'topic_id' => $topic['topicid'],
        'forum_id' => $post['forumid'],
        'forum_name' => wpforo_forum_name( $post['forumid'] ),
        'user_id' => $post['userid'],
    ];

    adfoin_wpforo_send_trigger_data( $saved_records, $posted_data );
}