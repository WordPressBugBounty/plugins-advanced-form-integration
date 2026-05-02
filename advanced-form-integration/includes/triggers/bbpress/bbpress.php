<?php

/**
 * bbPress trigger integration for Advanced Form Integration.
 *
 * Registers triggers, field maps, and action hooks for:
 *   - User creates a forum  (bbp_new_forum)
 *   - User creates a topic  (bbp_new_topic)
 *   - User creates a reply  (bbp_new_reply)
 */

// ---------------------------------------------------------------------------
// Trigger list
// ---------------------------------------------------------------------------

/**
 * Return the available bbPress trigger events.
 *
 * Called via call_user_func( "adfoin_{$form_provider}_get_forms", $form_provider )
 * by the AFI submission class.
 *
 * @param string $form_provider Active form provider slug.
 *
 * @return array|void
 */
function adfoin_bbpress_get_forms( $form_provider ) {
    if ( 'bbpress' !== $form_provider ) {
        return;
    }

    return array(
        'createForum' => __( 'User creates a forum', 'advanced-form-integration' ),
        'createTopic' => __( 'User creates a topic', 'advanced-form-integration' ),
        'createReply' => __( 'User creates a reply', 'advanced-form-integration' ),
    );
}

// ---------------------------------------------------------------------------
// Field maps
// ---------------------------------------------------------------------------

/**
 * Return the available merge fields for a given bbPress trigger.
 *
 * @param string $form_provider Active form provider slug.
 * @param string $form_id       Trigger ID (e.g. 'createForum').
 *
 * @return array|void
 */
function adfoin_bbpress_get_form_fields( $form_provider, $form_id ) {
    if ( 'bbpress' !== $form_provider ) {
        return;
    }

    $fields = array();

    if ( 'createForum' === $form_id ) {
        $fields = array(
            'forum_id'      => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_title'   => __( 'Forum Title', 'advanced-form-integration' ),
            'forum_content' => __( 'Forum Content', 'advanced-form-integration' ),
            'user_id'       => __( 'User ID', 'advanced-form-integration' ),
            'user_name'     => __( 'User Name', 'advanced-form-integration' ),
            'created_at'    => __( 'Created At', 'advanced-form-integration' ),
        );
    } elseif ( 'createTopic' === $form_id ) {
        $fields = array(
            'topic_id'      => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title'   => __( 'Topic Title', 'advanced-form-integration' ),
            'topic_content' => __( 'Topic Content', 'advanced-form-integration' ),
            'forum_id'      => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_title'   => __( 'Forum Title', 'advanced-form-integration' ),
            'user_id'       => __( 'User ID', 'advanced-form-integration' ),
            'user_name'     => __( 'User Name', 'advanced-form-integration' ),
            'created_at'    => __( 'Created At', 'advanced-form-integration' ),
        );
    } elseif ( 'createReply' === $form_id ) {
        $fields = array(
            'reply_id'      => __( 'Reply ID', 'advanced-form-integration' ),
            'reply_content' => __( 'Reply Content', 'advanced-form-integration' ),
            'topic_id'      => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title'   => __( 'Topic Title', 'advanced-form-integration' ),
            'forum_id'      => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_title'   => __( 'Forum Title', 'advanced-form-integration' ),
            'user_id'       => __( 'User ID', 'advanced-form-integration' ),
            'user_name'     => __( 'User Name', 'advanced-form-integration' ),
            'created_at'    => __( 'Created At', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// ---------------------------------------------------------------------------
// Hook: bbp_new_forum
// Fired by bbPress as: do_action( 'bbp_new_forum', $forum_array )
// where $forum_array contains: forum_id, post_parent, forum_author, …
// ---------------------------------------------------------------------------

add_action( 'bbp_new_forum', 'adfoin_bbpress_handle_create_forum', 10, 1 );

/**
 * Handle a newly created bbPress forum.
 *
 * @param array $forum Associative array passed by bbPress:
 *                     forum_id, post_parent, forum_author, last_topic_id, …
 *
 * @return void
 */
function adfoin_bbpress_handle_create_forum( $forum ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'bbpress', 'createForum' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $forum_id   = (int) $forum['forum_id'];
    $user_id    = (int) $forum['forum_author'];
    $user_name  = get_the_author_meta( 'display_name', $user_id );
    $forum_post = get_post( $forum_id );

    if ( ! $forum_post ) {
        return;
    }

    $posted_data = array(
        'forum_id'      => $forum_id,
        'forum_title'   => $forum_post->post_title,
        'forum_content' => $forum_post->post_content,
        'user_id'       => $user_id,
        'user_name'     => $user_name,
        'created_at'    => $forum_post->post_date,
    );

    $integration->send( $saved_records, $posted_data );
}

// ---------------------------------------------------------------------------
// Hook: bbp_new_topic
// Fired by bbPress as:
//   do_action( 'bbp_new_topic', $topic_id, $forum_id, $anonymous_data, $topic_author )
// ---------------------------------------------------------------------------

add_action( 'bbp_new_topic', 'adfoin_bbpress_handle_create_topic', 10, 4 );

/**
 * Handle a newly created bbPress topic.
 *
 * @param int        $topic_id       The new topic's post ID.
 * @param int        $forum_id       The parent forum's post ID.
 * @param array|bool $anonymous_data Anonymous poster data (false when logged in).
 * @param int        $topic_author   User ID of the topic author.
 *
 * @return void
 */
function adfoin_bbpress_handle_create_topic( $topic_id, $forum_id, $anonymous_data, $topic_author ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'bbpress', 'createTopic' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_name  = get_the_author_meta( 'display_name', $topic_author );
    $topic_post = get_post( $topic_id );
    $forum_post = get_post( $forum_id );

    if ( ! $topic_post || ! $forum_post ) {
        return;
    }

    $posted_data = array(
        'topic_id'      => (int) $topic_id,
        'topic_title'   => $topic_post->post_title,
        'topic_content' => $topic_post->post_content,
        'forum_id'      => (int) $forum_id,
        'forum_title'   => $forum_post->post_title,
        'user_id'       => (int) $topic_author,
        'user_name'     => $user_name,
        'created_at'    => $topic_post->post_date,
    );

    $integration->send( $saved_records, $posted_data );
}

// ---------------------------------------------------------------------------
// Hook: bbp_new_reply
// Fired by bbPress as:
//   do_action( 'bbp_new_reply', $reply_id, $topic_id, $forum_id,
//              $anonymous_data, $reply_author, $is_edit, $reply_to )
// ---------------------------------------------------------------------------

add_action( 'bbp_new_reply', 'adfoin_bbpress_handle_create_reply', 10, 5 );

/**
 * Handle a newly created bbPress reply.
 *
 * @param int        $reply_id       The new reply's post ID.
 * @param int        $topic_id       The parent topic's post ID.
 * @param int        $forum_id       The parent forum's post ID.
 * @param array|bool $anonymous_data Anonymous poster data (false when logged in).
 * @param int        $reply_author   User ID of the reply author.
 *
 * @return void
 */
function adfoin_bbpress_handle_create_reply( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'bbpress', 'createReply' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_name  = get_the_author_meta( 'display_name', $reply_author );
    $reply_post = get_post( $reply_id );
    $topic_post = get_post( $topic_id );
    $forum_post = get_post( $forum_id );

    if ( ! $reply_post || ! $topic_post || ! $forum_post ) {
        return;
    }

    $posted_data = array(
        'reply_id'      => (int) $reply_id,
        'reply_content' => $reply_post->post_content,
        'topic_id'      => (int) $topic_id,
        'topic_title'   => $topic_post->post_title,
        'forum_id'      => (int) $forum_id,
        'forum_title'   => $forum_post->post_title,
        'user_id'       => (int) $reply_author,
        'user_name'     => $user_name,
        'created_at'    => $reply_post->post_date,
    );

    $integration->send( $saved_records, $posted_data );
}
