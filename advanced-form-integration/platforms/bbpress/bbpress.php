<?php

add_filter( 'adfoin_action_providers', 'adfoin_bbpress_actions', 10, 1 );

/**
 * Register bbPress as an action provider.
 *
 * @param array $actions Existing action providers.
 *
 * @return array
 */
function adfoin_bbpress_actions( $actions ) {
    $actions['bbpress'] = array(
        'title' => __( 'bbPress', 'advanced-form-integration' ),
        'tasks' => array(
            'create_forum' => __( 'Create Forum', 'advanced-form-integration' ),
            'create_topic' => __( 'Create Topic', 'advanced-form-integration' ),
            'create_reply' => __( 'Create Reply', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_bbpress_action_fields' );

/**
 * Render the Vue template for bbPress action fields.
 *
 * @return void
 */
function adfoin_bbpress_action_fields() {
    ?>
    <script type="text/template" id="bbpress-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_forum'">
                <th scope="row">
                    <?php esc_attr_e( 'Forum Fields', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <p><?php esc_html_e( 'Map your form data to create a new bbPress forum.', 'advanced-form-integration' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Forum slug, parent, author, type, status, and visibility are optional.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Title', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.title" name="fieldData[title]" required>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Description', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.content" name="fieldData[content]">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.slug" name="fieldData[slug]">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Parent Forum ID/Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.parent" name="fieldData[parent]">
                    <p class="description"><?php esc_html_e( 'Accepts a numeric ID, slug, or title of the parent forum.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Author', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.author" name="fieldData[author]">
                    <p class="description"><?php esc_html_e( 'User ID, email, or username of the forum author. Defaults to the current user.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Forum Status', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.forumStatus" name="fieldData[forumStatus]">
                    <p class="description"><?php esc_html_e( 'Optional. Accepts open or closed.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Visibility', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.visibility" name="fieldData[visibility]">
                    <p class="description"><?php esc_html_e( 'Optional. Accepts public, private, or hidden.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_forum'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Forum Type', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.forumType" name="fieldData[forumType]">
                    <p class="description"><?php esc_html_e( 'Optional. Accepts forum or category.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_topic'">
                <th scope="row">
                    <?php esc_attr_e( 'Topic Fields', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <p><?php esc_html_e( 'Map fields to publish a new topic inside an existing forum.', 'advanced-form-integration' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Forum, title, and content are required. Status, stickiness, tags, slug, and author are optional.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Forum ID/Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.forum" name="fieldData[forum]" required>
                    <p class="description"><?php esc_html_e( 'Target forum ID, slug, or exact title.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Title', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.title" name="fieldData[title]" required>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Content', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.content" name="fieldData[content]" required>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.slug" name="fieldData[slug]">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Author', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.author" name="fieldData[author]">
                    <p class="description"><?php esc_html_e( 'User ID, email, or username. Defaults to the current user.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Status', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.postStatus" name="fieldData[postStatus]">
                    <p class="description"><?php esc_html_e( 'Optional. Accepts open, closed, pending, spam, or trash.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Stickiness', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.stickType" name="fieldData[stickType]">
                    <p class="description"><?php esc_html_e( 'Optional. Accepts stick or super. Leave blank for normal.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_topic'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Tags', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.tags" name="fieldData[tags]">
                    <p class="description"><?php esc_html_e( 'Comma or pipe separated list of topic tags.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_reply'">
                <th scope="row">
                    <?php esc_attr_e( 'Reply Fields', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <p><?php esc_html_e( 'Create a reply for an existing topic.', 'advanced-form-integration' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Topic and content are required. Forum, author, status, title, and reply-to are optional.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Topic ID/Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.topic" name="fieldData[topic]" required>
                    <p class="description"><?php esc_html_e( 'Target topic ID, slug, or exact title.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Forum ID/Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.forum" name="fieldData[forum]">
                    <p class="description"><?php esc_html_e( 'Optional. Defaults to the forum associated with the topic.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Content', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.content" name="fieldData[content]" required>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Title', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.title" name="fieldData[title]">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Author', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.author" name="fieldData[author]">
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Status', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.postStatus" name="fieldData[postStatus]">
                    <p class="description"><?php esc_html_e( 'Optional. Accepts publish, pending, spam, or trash.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_reply'">
                <td scope="row-title">
                    <label><?php esc_attr_e( 'Reply To ID/Slug', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.replyTo" name="fieldData[replyTo]">
                    <p class="description"><?php esc_html_e( 'Optional. Set to another reply to create a threaded response.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_bbpress_job_queue', 'adfoin_bbpress_job_queue', 10, 1 );

/**
 * Process the bbPress job queue.
 *
 * @param array $data Job queue payload.
 *
 * @return void
 */
function adfoin_bbpress_job_queue( $data ) {
    adfoin_bbpress_send_data( $data['record'], $data['posted_data'] );
}

if ( ! function_exists( 'adfoin_bbpress_send_data' ) ) {
    /**
     * Send parsed data to bbPress.
     *
     * @param array $record      Integration record.
     * @param array $posted_data Trigger payload.
     *
     * @return void
     */
    function adfoin_bbpress_send_data( $record, $posted_data ) {
        $record_data = json_decode( $record['data'], true );

        $cl = isset( $record_data['action_data']['cl'] ) ? $record_data['action_data']['cl'] : array();
        if ( adfoin_check_conditional_logic( $cl, $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $fields     = adfoin_bbpress_prepare_fields( $field_data, $posted_data );
        $task       = isset( $record['task'] ) ? $record['task'] : '';

        $result = array(
            'success' => false,
            'message' => __( 'Unsupported action task.', 'advanced-form-integration' ),
        );

        switch ( $task ) {
            case 'create_forum':
                $result = adfoin_bbpress_create_forum( $fields );
                break;
            case 'create_topic':
                $result = adfoin_bbpress_create_topic( $fields );
                break;
            case 'create_reply':
                $result = adfoin_bbpress_create_reply( $fields );
                break;
        }

        $log_args = array(
            'method' => 'LOCAL',
            'body'   => $fields,
        );

        $log_response = array(
            'response' => array(
                'code'    => ! empty( $result['success'] ) ? 200 : 400,
                'message' => isset( $result['message'] ) ? $result['message'] : '',
            ),
            'body'     => $result,
        );

        adfoin_add_to_log( $log_response, 'bbpress', $log_args, $record );
    }
}

if ( ! function_exists( 'adfoin_bbpress_prepare_fields' ) ) {
    /**
     * Prepare mapped field values.
     *
     * @param array $field_data  Saved field mapping.
     * @param array $posted_data Trigger payload.
     *
     * @return array
     */
    function adfoin_bbpress_prepare_fields( $field_data, $posted_data ) {
        $prepared = array();

        if ( empty( $field_data ) || ! is_array( $field_data ) ) {
            return $prepared;
        }

        foreach ( $field_data as $key => $value ) {
            if ( '' === $key || null === $value ) {
                continue;
            }

            $parsed = adfoin_get_parsed_values( $value, $posted_data );

            if ( is_string( $parsed ) ) {
                $prepared[ $key ] = html_entity_decode( $parsed, ENT_QUOTES, get_bloginfo( 'charset' ) );
            } else {
                $prepared[ $key ] = $parsed;
            }
        }

        return $prepared;
    }
}

if ( ! function_exists( 'adfoin_bbpress_create_forum' ) ) {
    /**
     * Create a bbPress forum.
     *
     * @param array $fields Parsed field values.
     *
     * @return array
     */
    function adfoin_bbpress_create_forum( $fields ) {
        if ( ! function_exists( 'bbp_insert_forum' ) ) {
            return array(
                'success' => false,
                'message' => __( 'bbPress is not available.', 'advanced-form-integration' ),
            );
        }

        $title = isset( $fields['title'] ) ? trim( wp_strip_all_tags( $fields['title'] ) ) : '';
        if ( '' === $title ) {
            return array(
                'success' => false,
                'message' => __( 'Forum title is required.', 'advanced-form-integration' ),
            );
        }

        $content       = isset( $fields['content'] ) ? $fields['content'] : '';
        $slug          = isset( $fields['slug'] ) ? sanitize_title( $fields['slug'] ) : '';
        $author_id     = adfoin_bbpress_resolve_user_id( isset( $fields['author'] ) ? $fields['author'] : '' );
        $parent_forum  = adfoin_bbpress_resolve_forum_id( isset( $fields['parent'] ) ? $fields['parent'] : '' );
        $forum_status  = adfoin_bbpress_normalize_forum_status( isset( $fields['forumStatus'] ) ? $fields['forumStatus'] : '' );
        $visibility    = adfoin_bbpress_resolve_visibility_status( isset( $fields['visibility'] ) ? $fields['visibility'] : '' );
        $forum_type    = adfoin_bbpress_normalize_forum_type( isset( $fields['forumType'] ) ? $fields['forumType'] : '' );
        $current_user  = get_current_user_id();
        $resolved_user = $author_id ? $author_id : $current_user;

        $forum_data = array(
            'post_title'   => $title,
            'post_content' => wp_kses_post( $content ),
            'post_status'  => bbp_get_public_status_id(),
        );

        if ( $slug ) {
            $forum_data['post_name'] = $slug;
        }

        if ( $resolved_user ) {
            $forum_data['post_author'] = absint( $resolved_user );
        }

        if ( $parent_forum ) {
            $forum_data['post_parent'] = absint( $parent_forum );
        }

        $forum_id = bbp_insert_forum( $forum_data );

        if ( empty( $forum_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Unable to create forum.', 'advanced-form-integration' ),
            );
        }

        if ( 'closed' === $forum_status && function_exists( 'bbp_close_forum' ) ) {
            bbp_close_forum( $forum_id );
        } elseif ( 'open' === $forum_status && function_exists( 'bbp_open_forum' ) ) {
            bbp_open_forum( $forum_id );
        }

        if ( $forum_type && function_exists( 'bbp_categorize_forum' ) && function_exists( 'bbp_normalize_forum' ) ) {
            if ( 'category' === $forum_type ) {
                bbp_categorize_forum( $forum_id );
            } elseif ( 'forum' === $forum_type ) {
                bbp_normalize_forum( $forum_id );
            }
        }

        if ( $visibility ) {
            adfoin_bbpress_apply_forum_visibility( $forum_id, $visibility );
        }

        return array(
            'success'   => true,
            'message'   => __( 'Forum created successfully.', 'advanced-form-integration' ),
            'entity_id' => (int) $forum_id,
            'entity'    => 'forum',
        );
    }
}

if ( ! function_exists( 'adfoin_bbpress_create_topic' ) ) {
    /**
     * Create a bbPress topic.
     *
     * @param array $fields Parsed field values.
     *
     * @return array
     */
    function adfoin_bbpress_create_topic( $fields ) {
        if ( ! function_exists( 'bbp_insert_topic' ) ) {
            return array(
                'success' => false,
                'message' => __( 'bbPress is not available.', 'advanced-form-integration' ),
            );
        }

        $forum_id = adfoin_bbpress_resolve_forum_id( isset( $fields['forum'] ) ? $fields['forum'] : '' );
        if ( ! $forum_id ) {
            return array(
                'success' => false,
                'message' => __( 'Forum could not be resolved for the topic.', 'advanced-form-integration' ),
            );
        }

        $title = isset( $fields['title'] ) ? trim( wp_strip_all_tags( $fields['title'] ) ) : '';
        if ( '' === $title ) {
            return array(
                'success' => false,
                'message' => __( 'Topic title is required.', 'advanced-form-integration' ),
            );
        }

        $content      = isset( $fields['content'] ) ? $fields['content'] : '';
        $slug         = isset( $fields['slug'] ) ? sanitize_title( $fields['slug'] ) : '';
        $author_id    = adfoin_bbpress_resolve_user_id( isset( $fields['author'] ) ? $fields['author'] : '' );
        $current_user = get_current_user_id();
        $resolved_user = $author_id ? $author_id : $current_user;

        $topic_status = adfoin_bbpress_resolve_topic_status( isset( $fields['postStatus'] ) ? $fields['postStatus'] : '' );
        $stick_type   = isset( $fields['stickType'] ) ? strtolower( trim( $fields['stickType'] ) ) : '';

        $topic_data = array(
            'post_parent'  => $forum_id,
            'post_title'   => $title,
            'post_content' => wp_kses_post( $content ),
            'post_status'  => bbp_get_public_status_id(),
        );

        if ( $slug ) {
            $topic_data['post_name'] = $slug;
        }

        if ( $resolved_user ) {
            $topic_data['post_author'] = absint( $resolved_user );
        }

        $topic_meta = array(
            'forum_id' => $forum_id,
        );

        $topic_id = bbp_insert_topic( $topic_data, $topic_meta );

        if ( empty( $topic_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Unable to create topic.', 'advanced-form-integration' ),
            );
        }

        if ( $topic_status ) {
            adfoin_bbpress_apply_topic_status( $topic_id, $topic_status );
        }

        if ( $stick_type && function_exists( 'bbp_stick_topic' ) ) {
            if ( 'super' === $stick_type ) {
                bbp_stick_topic( $topic_id, true );
            } elseif ( 'stick' === $stick_type ) {
                bbp_stick_topic( $topic_id );
            }
        }

        if ( ! empty( $fields['tags'] ) && function_exists( 'bbp_allow_topic_tags' ) && bbp_allow_topic_tags() ) {
            $raw_tags = preg_split( '/[,|]+/', $fields['tags'] );
            $tag_list = array_filter( array_map( 'trim', $raw_tags ) );

            if ( ! empty( $tag_list ) ) {
                wp_set_post_terms( $topic_id, $tag_list, bbp_get_topic_tag_tax_id(), false );
            }
        }

        return array(
            'success'   => true,
            'message'   => __( 'Topic created successfully.', 'advanced-form-integration' ),
            'entity_id' => (int) $topic_id,
            'entity'    => 'topic',
        );
    }
}

if ( ! function_exists( 'adfoin_bbpress_create_reply' ) ) {
    /**
     * Create a bbPress reply.
     *
     * @param array $fields Parsed field values.
     *
     * @return array
     */
    function adfoin_bbpress_create_reply( $fields ) {
        if ( ! function_exists( 'bbp_insert_reply' ) ) {
            return array(
                'success' => false,
                'message' => __( 'bbPress is not available.', 'advanced-form-integration' ),
            );
        }

        $topic_id = adfoin_bbpress_resolve_topic_id( isset( $fields['topic'] ) ? $fields['topic'] : '' );
        if ( ! $topic_id ) {
            return array(
                'success' => false,
                'message' => __( 'Topic could not be resolved for the reply.', 'advanced-form-integration' ),
            );
        }

        $forum_id = adfoin_bbpress_resolve_forum_id( isset( $fields['forum'] ) ? $fields['forum'] : '' );

        if ( ! $forum_id && function_exists( 'bbp_get_topic_forum_id' ) ) {
            $forum_id = bbp_get_topic_forum_id( $topic_id );
        }

        if ( ! $forum_id ) {
            return array(
                'success' => false,
                'message' => __( 'Forum could not be determined for the reply.', 'advanced-form-integration' ),
            );
        }

        $content = isset( $fields['content'] ) ? $fields['content'] : '';
        if ( '' === trim( $content ) ) {
            return array(
                'success' => false,
                'message' => __( 'Reply content is required.', 'advanced-form-integration' ),
            );
        }

        $title        = isset( $fields['title'] ) ? trim( wp_strip_all_tags( $fields['title'] ) ) : '';
        $author_id    = adfoin_bbpress_resolve_user_id( isset( $fields['author'] ) ? $fields['author'] : '' );
        $reply_to     = adfoin_bbpress_resolve_reply_id( isset( $fields['replyTo'] ) ? $fields['replyTo'] : '' );
        $reply_status = adfoin_bbpress_resolve_reply_status( isset( $fields['postStatus'] ) ? $fields['postStatus'] : '' );
        $current_user = get_current_user_id();
        $resolved_user = $author_id ? $author_id : $current_user;

        $reply_data = array(
            'post_parent'  => $topic_id,
            'post_content' => wp_kses_post( $content ),
            'post_status'  => bbp_get_public_status_id(),
        );

        if ( $title ) {
            $reply_data['post_title'] = $title;
        }

        if ( $resolved_user ) {
            $reply_data['post_author'] = absint( $resolved_user );
        }

        $reply_meta = array(
            'topic_id' => $topic_id,
            'forum_id' => $forum_id,
        );

        if ( $reply_to ) {
            $reply_meta['reply_to'] = $reply_to;
        }

        $reply_id = bbp_insert_reply( $reply_data, $reply_meta );

        if ( empty( $reply_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Unable to create reply.', 'advanced-form-integration' ),
            );
        }

        if ( $reply_status ) {
            adfoin_bbpress_apply_reply_status( $reply_id, $reply_status );
        }

        return array(
            'success'   => true,
            'message'   => __( 'Reply created successfully.', 'advanced-form-integration' ),
            'entity_id' => (int) $reply_id,
            'entity'    => 'reply',
        );
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_user_id' ) ) {
    /**
     * Resolve a user identifier to a user ID.
     *
     * @param string $value Provided user reference.
     *
     * @return int
     */
    function adfoin_bbpress_resolve_user_id( $value ) {
        if ( '' === $value || null === $value ) {
            return 0;
        }

        if ( is_numeric( $value ) ) {
            return absint( $value );
        }

        $value = trim( $value );

        $user = get_user_by( 'login', $value );
        if ( ! $user ) {
            $user = get_user_by( 'email', $value );
        }
        if ( ! $user ) {
            $user = get_user_by( 'slug', sanitize_title( $value ) );
        }

        return $user ? (int) $user->ID : 0;
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_post_id' ) ) {
    /**
     * Resolve a value to a post ID of the requested type.
     *
     * @param string $value     Provided identifier.
     * @param string $post_type Post type to search.
     *
     * @return int
     */
    function adfoin_bbpress_resolve_post_id( $value, $post_type ) {
        if ( '' === $value || null === $value ) {
            return 0;
        }

        if ( is_numeric( $value ) ) {
            $post_id = absint( $value );
            $post    = get_post( $post_id );

            if ( $post && $post_type === $post->post_type ) {
                return $post_id;
            }

            return 0;
        }

        $value = trim( $value );

        $post = get_page_by_path( $value, OBJECT, $post_type );

        if ( ! $post ) {
            $post = get_page_by_path( sanitize_title( $value ), OBJECT, $post_type );
        }

        if ( ! $post ) {
            $post = get_page_by_title( $value, OBJECT, $post_type );
        }

        return $post ? (int) $post->ID : 0;
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_forum_id' ) ) {
    /**
     * Resolve a forum identifier to a forum ID.
     *
     * @param string $value Provided identifier.
     *
     * @return int
     */
    function adfoin_bbpress_resolve_forum_id( $value ) {
        if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
            return 0;
        }

        return adfoin_bbpress_resolve_post_id( $value, bbp_get_forum_post_type() );
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_topic_id' ) ) {
    /**
     * Resolve a topic identifier to a topic ID.
     *
     * @param string $value Provided identifier.
     *
     * @return int
     */
    function adfoin_bbpress_resolve_topic_id( $value ) {
        if ( ! function_exists( 'bbp_get_topic_post_type' ) ) {
            return 0;
        }

        return adfoin_bbpress_resolve_post_id( $value, bbp_get_topic_post_type() );
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_reply_id' ) ) {
    /**
     * Resolve a reply identifier to a reply ID.
     *
     * @param string $value Provided identifier.
     *
     * @return int
     */
    function adfoin_bbpress_resolve_reply_id( $value ) {
        if ( ! function_exists( 'bbp_get_reply_post_type' ) ) {
            return 0;
        }

        return adfoin_bbpress_resolve_post_id( $value, bbp_get_reply_post_type() );
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_visibility_status' ) ) {
    /**
     * Convert a visibility label into a bbPress post_status.
     *
     * @param string $visibility Visibility value.
     *
     * @return string
     */
    function adfoin_bbpress_resolve_visibility_status( $visibility ) {
        if ( '' === $visibility || null === $visibility || ! is_string( $visibility ) ) {
            return '';
        }

        $visibility = strtolower( trim( $visibility ) );
        $map        = array(
            'public'  => function_exists( 'bbp_get_public_status_id' ) ? bbp_get_public_status_id() : 'publish',
            'publish' => function_exists( 'bbp_get_public_status_id' ) ? bbp_get_public_status_id() : 'publish',
            'open'    => function_exists( 'bbp_get_public_status_id' ) ? bbp_get_public_status_id() : 'publish',
            'private' => function_exists( 'bbp_get_private_status_id' ) ? bbp_get_private_status_id() : 'private',
            'hidden'  => function_exists( 'bbp_get_hidden_status_id' ) ? bbp_get_hidden_status_id() : 'hidden',
        );

        return isset( $map[ $visibility ] ) ? $map[ $visibility ] : '';
    }
}

if ( ! function_exists( 'adfoin_bbpress_normalize_forum_status' ) ) {
    /**
     * Normalize forum status.
     *
     * @param string $status Status string.
     *
     * @return string
     */
    function adfoin_bbpress_normalize_forum_status( $status ) {
        if ( '' === $status || null === $status || ! is_string( $status ) ) {
            return '';
        }

        $status = strtolower( trim( $status ) );
        return in_array( $status, array( 'open', 'closed' ), true ) ? $status : '';
    }
}

if ( ! function_exists( 'adfoin_bbpress_normalize_forum_type' ) ) {
    /**
     * Normalize forum type.
     *
     * @param string $type Type string.
     *
     * @return string
     */
    function adfoin_bbpress_normalize_forum_type( $type ) {
        if ( '' === $type || null === $type || ! is_string( $type ) ) {
            return '';
        }

        $type = strtolower( trim( $type ) );
        return in_array( $type, array( 'forum', 'category' ), true ) ? $type : '';
    }
}

if ( ! function_exists( 'adfoin_bbpress_apply_forum_visibility' ) ) {
    /**
     * Apply visibility to a forum.
     *
     * @param int    $forum_id   Forum ID.
     * @param string $visibility Visibility status.
     *
     * @return void
     */
    function adfoin_bbpress_apply_forum_visibility( $forum_id, $visibility ) {
        if ( ! $forum_id || ! $visibility ) {
            return;
        }

        $current_visibility = get_post_status( $forum_id );

        if ( function_exists( 'bbp_get_private_status_id' ) && $visibility === bbp_get_private_status_id() && function_exists( 'bbp_privatize_forum' ) ) {
            bbp_privatize_forum( $forum_id, $current_visibility );
        } elseif ( function_exists( 'bbp_get_hidden_status_id' ) && $visibility === bbp_get_hidden_status_id() && function_exists( 'bbp_hide_forum' ) ) {
            bbp_hide_forum( $forum_id, $current_visibility );
        } elseif ( function_exists( 'bbp_publicize_forum' ) ) {
            bbp_publicize_forum( $forum_id, $current_visibility );
        }
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_topic_status' ) ) {
    /**
     * Normalize a topic status.
     *
     * @param string $status Status string.
     *
     * @return string
     */
    function adfoin_bbpress_resolve_topic_status( $status ) {
        if ( '' === $status || null === $status || ! is_string( $status ) ) {
            return '';
        }

        $status = strtolower( trim( $status ) );
        $map    = array();

        if ( function_exists( 'bbp_get_public_status_id' ) ) {
            $map['open']    = bbp_get_public_status_id();
            $map['publish'] = bbp_get_public_status_id();
            $map['public']  = bbp_get_public_status_id();
        }

        if ( function_exists( 'bbp_get_closed_status_id' ) ) {
            $map['closed'] = bbp_get_closed_status_id();
        }

        if ( function_exists( 'bbp_get_pending_status_id' ) ) {
            $map['pending'] = bbp_get_pending_status_id();
        }

        if ( function_exists( 'bbp_get_spam_status_id' ) ) {
            $map['spam'] = bbp_get_spam_status_id();
        }

        if ( function_exists( 'bbp_get_trash_status_id' ) ) {
            $map['trash'] = bbp_get_trash_status_id();
        }

        return isset( $map[ $status ] ) ? $map[ $status ] : '';
    }
}

if ( ! function_exists( 'adfoin_bbpress_apply_topic_status' ) ) {
    /**
     * Apply the requested status to a topic.
     *
     * @param int    $topic_id Topic ID.
     * @param string $status   bbPress status slug.
     *
     * @return void
     */
    function adfoin_bbpress_apply_topic_status( $topic_id, $status ) {
        if ( ! $topic_id || '' === $status ) {
            return;
        }

        if ( function_exists( 'bbp_get_closed_status_id' ) && $status === bbp_get_closed_status_id() && function_exists( 'bbp_close_topic' ) ) {
            bbp_close_topic( $topic_id );
            return;
        }

        if ( function_exists( 'bbp_get_pending_status_id' ) && $status === bbp_get_pending_status_id() && function_exists( 'bbp_unapprove_topic' ) ) {
            bbp_unapprove_topic( $topic_id );
            return;
        }

        if ( function_exists( 'bbp_get_spam_status_id' ) && $status === bbp_get_spam_status_id() && function_exists( 'bbp_spam_topic' ) ) {
            bbp_spam_topic( $topic_id );
            return;
        }

        if ( function_exists( 'bbp_get_trash_status_id' ) && $status === bbp_get_trash_status_id() && function_exists( 'bbp_trash_topic' ) ) {
            bbp_trash_topic( $topic_id );
            return;
        }

        if ( function_exists( 'bbp_approve_topic' ) ) {
            bbp_approve_topic( $topic_id );
        }
    }
}

if ( ! function_exists( 'adfoin_bbpress_resolve_reply_status' ) ) {
    /**
     * Normalize a reply status.
     *
     * @param string $status Status string.
     *
     * @return string
     */
    function adfoin_bbpress_resolve_reply_status( $status ) {
        if ( '' === $status || null === $status || ! is_string( $status ) ) {
            return '';
        }

        $status = strtolower( trim( $status ) );
        $map    = array();

        if ( function_exists( 'bbp_get_public_status_id' ) ) {
            $map['publish'] = bbp_get_public_status_id();
            $map['public']  = bbp_get_public_status_id();
        }
        if ( function_exists( 'bbp_get_pending_status_id' ) ) {
            $map['pending'] = bbp_get_pending_status_id();
        }
        if ( function_exists( 'bbp_get_spam_status_id' ) ) {
            $map['spam'] = bbp_get_spam_status_id();
        }
        if ( function_exists( 'bbp_get_trash_status_id' ) ) {
            $map['trash'] = bbp_get_trash_status_id();
        }

        return isset( $map[ $status ] ) ? $map[ $status ] : '';
    }
}

if ( ! function_exists( 'adfoin_bbpress_apply_reply_status' ) ) {
    /**
     * Apply the requested status to a reply.
     *
     * @param int    $reply_id Reply ID.
     * @param string $status   bbPress status slug.
     *
     * @return void
     */
    function adfoin_bbpress_apply_reply_status( $reply_id, $status ) {
        if ( ! $reply_id || '' === $status ) {
            return;
        }

        if ( function_exists( 'bbp_get_pending_status_id' ) && $status === bbp_get_pending_status_id() && function_exists( 'bbp_unapprove_reply' ) ) {
            bbp_unapprove_reply( $reply_id );
            return;
        }

        if ( function_exists( 'bbp_get_spam_status_id' ) && $status === bbp_get_spam_status_id() && function_exists( 'bbp_spam_reply' ) ) {
            bbp_spam_reply( $reply_id );
            return;
        }

        if ( function_exists( 'bbp_get_trash_status_id' ) && $status === bbp_get_trash_status_id() && function_exists( 'bbp_trash_reply' ) ) {
            bbp_trash_reply( $reply_id );
            return;
        }

        if ( function_exists( 'bbp_approve_reply' ) ) {
            bbp_approve_reply( $reply_id );
        }
    }
}
