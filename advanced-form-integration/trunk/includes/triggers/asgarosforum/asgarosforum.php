<?php

// Get Asgaros Forum Triggers
function adfoin_asgarosforum_get_forms( $form_provider ) {
    if ( $form_provider != 'asgarosforum' ) {
        return;
    }

    $triggers = array(
        'newTopic' => __( 'New Topic Created', 'advanced-form-integration' ),
        'newPost' => __( 'New Post Added', 'advanced-form-integration' ),
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

add_action( 'asgarosforum_after_add_topic_submit', 'adfoin_asgarosforum_handle_new_topic', 10, 6 );

// Handle New Topic
function adfoin_asgarosforum_handle_new_topic( $post_id, $topic_id, $subject, $content, $link, $author_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'asgarosforum', 'newTopic' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if (! class_exists('AsgarosForum')) {
        return;
    }

    $forum = new AsgarosForum();

    if (! isset($post_id)) {
        return;
    }

    $topic    = $forum->content->get_topic($topic_id);
    $forum_id = $topic->parent_id;

    $posted_data = array(
        'topic_id' => $topic_id,
        'topic_title' => $topic->title,
        'post_id'  => $post_id,
        'forum_id' => $forum_id,
        'forum_name' => $forum->content->get_forum($forum_id),
        'author_id' => $author_id,
        'author_name' => get_the_author_meta('display_name', $author_id),
    );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'asgarosforum_after_add_post_submit', 'adfoin_asgarosforum_handle_new_post', 10, 6 );

// Handle New Post
function adfoin_asgarosforum_handle_new_post( $post_id, $topic_id, $subject, $content, $link, $author_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'asgarosforum', 'newPost' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if (! class_exists('AsgarosForum')) {
        return;
    }

    $forum = new AsgarosForum();

    if (! isset($post_id)) {
        return;
    }

    $post = $forum->content->get_post($post_id);
    $topic = $forum->content->get_topic($topic_id);
    $forum_id = $topic->parent_id;

    $posted_data = array(
        'post_id' => $post_id,
        'topic_id' => $topic_id,
        'forum_id' => $forum_id,
        'forum_name' => $forum->content->get_forum($forum_id),
        'author_id' => $author_id,
        'author_name' => get_the_author_meta('display_name', $author_id),
        'content' => $post->content,
        'created_at' => $post->created,
    );

    $integration->send( $saved_records, $posted_data );
}
