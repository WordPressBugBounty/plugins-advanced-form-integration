<?php

// Get bbPress Triggers
function adfoin_bbpress_get_forms($form_provider) {
    if ($form_provider != 'bbpress') {
        return;
    }

    $triggers = array(
        'createForum' => __('User creates a forum', 'advanced-form-integration'),
        'createTopic' => __('User creates a topic', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get bbPress Fields
function adfoin_bbpress_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'bbpress') {
        return;
    }

    $fields = array();

    if ($form_id === 'createForum') {
        $fields = [
            'forum_id'      => __('Forum ID', 'advanced-form-integration'),
            'forum_title'   => __('Forum Title', 'advanced-form-integration'),
            'forum_content' => __('Forum Content', 'advanced-form-integration'),
            'user_id'       => __('User ID', 'advanced-form-integration'),
            'user_name'     => __('User Name', 'advanced-form-integration'),
            'created_at'    => __('Created At', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'createTopic') {
        $fields = [
            'topic_id'      => __('Topic ID', 'advanced-form-integration'),
            'topic_title'   => __('Topic Title', 'advanced-form-integration'),
            'topic_content' => __('Topic Content', 'advanced-form-integration'),
            'forum_id'      => __('Forum ID', 'advanced-form-integration'),
            'forum_title'   => __('Forum Title', 'advanced-form-integration'),
            'user_id'       => __('User ID', 'advanced-form-integration'),
            'user_name'     => __('User Name', 'advanced-form-integration'),
            'created_at'    => __('Created At', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into bbPress "create forum" action
add_action('bbp_new_forum', 'adfoin_bbpress_handle_create_forum', 10, 1);

function adfoin_bbpress_handle_create_forum($forum) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('bbpress', 'createForum');

    if (empty($saved_records)) {
        return;
    }

    $forum_id = $forum['forum_id'];
    $user_id = $forum['forum_author'];
    $user_name = get_the_author_meta('display_name', $user_id);
    $forum_post = get_post($forum_id);

    $posted_data = array(
        'forum_id'      => $forum_id,
        'forum_title'   => $forum_post->post_title,
        'forum_content' => $forum_post->post_content,
        'user_id'       => $user_id,
        'user_name'     => $user_name,
        'created_at'    => $forum_post->post_date,
    );

    $integration->send($saved_records, $posted_data);
}

// Hook into bbPress "create topic" action
add_action('bbp_new_topic', 'adfoin_bbpress_handle_create_topic', 10, 4);

function adfoin_bbpress_handle_create_topic($topic_id, $forum_id, $anonymous_data, $topic_author) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('bbpress', 'createTopic');

    if (empty($saved_records)) {
        return;
    }

    $user_name = get_the_author_meta('display_name', $topic_author);
    $topic_post = get_post($topic_id);
    $forum_post = get_post($forum_id);

    $posted_data = array(
        'topic_id'      => $topic_id,
        'topic_title'   => $topic_post->post_title,
        'topic_content' => $topic_post->post_content,
        'forum_id'      => $forum_id,
        'forum_title'   => $forum_post->post_title,
        'user_id'       => $topic_author,
        'user_name'     => $user_name,
        'created_at'    => $topic_post->post_date,
    );

    $integration->send($saved_records, $posted_data);
}