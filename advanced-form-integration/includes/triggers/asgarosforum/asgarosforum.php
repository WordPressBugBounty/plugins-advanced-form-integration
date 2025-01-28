<?php

// Get Asgaros Forum Triggers
function adfoin_asgarosforum_get_forms( $form_provider ) {
    if ( $form_provider != 'asgarosforum' ) {
        return;
    }

    $triggers = array(
        'newTopic' => __( 'New Topic Created', 'advanced-form-integration' ),
        'newPost' => __( 'New Post Added', 'advanced-form-integration' ),
        'userMentioned' => __( 'User Mentioned', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Asgaros Forum Fields
function adfoin_asgarosforum_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'asgarosforum' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'newTopic' ) {
        $fields = [
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title' => __( 'Topic Title', 'advanced-form-integration' ),
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            'author_id' => __( 'Author ID', 'advanced-form-integration' ),
            'author_name' => __( 'Author Name', 'advanced-form-integration' ),
            'created_at' => __( 'Created At', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'newPost' ) {
        $fields = [
            'post_id' => __( 'Post ID', 'advanced-form-integration' ),
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            'author_id' => __( 'Author ID', 'advanced-form-integration' ),
            'author_name' => __( 'Author Name', 'advanced-form-integration' ),
            'content' => __( 'Post Content', 'advanced-form-integration' ),
            'created_at' => __( 'Created At', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'userMentioned' ) {
        $fields = [
            'mentioned_user_id' => __( 'Mentioned User ID', 'advanced-form-integration' ),
            'mentioned_user_name' => __( 'Mentioned User Name', 'advanced-form-integration' ),
            'post_id' => __( 'Post ID', 'advanced-form-integration' ),
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            'author_id' => __( 'Author ID', 'advanced-form-integration' ),
            'author_name' => __( 'Author Name', 'advanced-form-integration' ),
            'content' => __( 'Post Content', 'advanced-form-integration' ),
            'created_at' => __( 'Created At', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_asgarosforum_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['first_name'] = $user->first_name;
        $user_data['last_name'] = $user->last_name;
        $user_data['nickname'] = $user->nickname;
        $user_data['avatar_url'] = get_avatar_url( $user_id );
        $user_data['user_email'] = $user->user_email;
        $user_data['user_id'] = $user_id;
    }

    return $user_data;
}

// Send Trigger Data
function adfoin_asgarosforum_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

add_action( 'asgarosforum_after_topic_create', 'adfoin_asgarosforum_handle_new_topic', 10, 2 );

// Handle New Topic
function adfoin_asgarosforum_handle_new_topic( $topic_id, $forum_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'asgarosforum', 'newTopic' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'topic_id' => $topic_id,
        'forum_id' => $forum_id,
    );

    $topic = AsgarosForumTopics::getTopic( $topic_id );

    if ( $topic ) {
        $posted_data['topic_title'] = $topic->title;
        $posted_data['author_id'] = $topic->author_id;
        $posted_data['author_name'] = get_the_author_meta( 'display_name', $topic->author_id );
        $posted_data['created_at'] = $topic->created;
    }

    $forum = AsgarosForumForum::getForum( $forum_id );

    if ( $forum ) {
        $posted_data['forum_name'] = $forum->name;
    }

    adfoin_asgarosforum_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'asgarosforum_after_post_create', 'adfoin_asgarosforum_handle_new_post', 10, 2 );

// Handle New Post
function adfoin_asgarosforum_handle_new_post( $post_id, $topic_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'asgarosforum', 'newPost' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'post_id' => $post_id,
        'topic_id' => $topic_id,
    );

    $post = AsgarosForumPosts::getPost( $post_id );

    if ( $post ) {
        $posted_data['content'] = $post->content;
        $posted_data['author_id'] = $post->author_id;
        $posted_data['author_name'] = get_the_author_meta( 'display_name', $post->author_id );
        $posted_data['created_at'] = $post->created;
    }

    $topic = AsgarosForumTopics::getTopic( $topic_id );

    if ( $topic ) {
        $posted_data['forum_id'] = $topic->parent_id;
        $posted_data['forum_name'] = AsgarosForumForum::getForum( $topic->parent_id )->name;
    }

    adfoin_asgarosforum_send_trigger_data( $saved_records, $posted_data );
}