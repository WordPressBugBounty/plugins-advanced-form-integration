<?php

// Get wpForo Triggers
function adfoin_wpforo_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpforo' ) {
        return;
    }

    return [
        'createTopic' => __( 'User creates a topic', 'advanced-form-integration' ),
        'createReply' => __( 'User replies to a topic', 'advanced-form-integration' ),
        'userRegistered' => __( 'User registers (wpForo)', 'advanced-form-integration' ),
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
            // Core Topic Info
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title' => __( 'Topic Title', 'advanced-form-integration' ),
            'topic_slug' => __( 'Topic Slug', 'advanced-form-integration' ),
            
            // Forum Info
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            
            // User Info
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            
            // Timestamps
            'topic_created' => __( 'Topic Created (Timestamp)', 'advanced-form-integration' ),
            'topic_modified' => __( 'Topic Modified (Timestamp)', 'advanced-form-integration' ),
            
            // Status & Privacy
            'topic_type' => __( 'Topic Type (normal/sticky/etc)', 'advanced-form-integration' ),
            'topic_status' => __( 'Topic Status (0=open, 1=pending)', 'advanced-form-integration' ),
            'topic_private' => __( 'Is Private? (0/1)', 'advanced-form-integration' ),
            
            // Content Info
            'topic_tags' => __( 'Topic Tags', 'advanced-form-integration' ),
            'topic_has_attachments' => __( 'Has Attachments? (0/1)', 'advanced-form-integration' ),
            
            // Engagement Metrics
            'topic_views' => __( 'View Count', 'advanced-form-integration' ),
            'topic_replies' => __( 'Reply Count at Creation', 'advanced-form-integration' ),
            
            // SEO
            'topic_meta_key' => __( 'SEO Meta Key', 'advanced-form-integration' ),
            'topic_meta_desc' => __( 'SEO Meta Description', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'createReply' ) {
        $fields = [
            // Core Reply Info
            'reply_id' => __( 'Reply ID', 'advanced-form-integration' ),
            'reply_title' => __( 'Reply Title', 'advanced-form-integration' ),
            'reply_content' => __( 'Reply Content', 'advanced-form-integration' ),
            'reply_url' => __( 'Reply URL', 'advanced-form-integration' ),
            
            // Topic Info
            'topic_id' => __( 'Topic ID', 'advanced-form-integration' ),
            'topic_title' => __( 'Topic Title', 'advanced-form-integration' ),
            
            // Forum Info
            'forum_id' => __( 'Forum ID', 'advanced-form-integration' ),
            'forum_name' => __( 'Forum Name', 'advanced-form-integration' ),
            
            // User Info
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            
            // Timestamps
            'reply_created' => __( 'Reply Created (Timestamp)', 'advanced-form-integration' ),
            'reply_modified' => __( 'Reply Modified (Timestamp)', 'advanced-form-integration' ),
            
            // Status & Privacy
            'reply_status' => __( 'Reply Status (0=open, 1=pending)', 'advanced-form-integration' ),
            'reply_private' => __( 'Is Private? (0/1)', 'advanced-form-integration' ),
            
            // Nested Reply Info
            'reply_parent_id' => __( 'Parent Post ID (Nested Replies)', 'advanced-form-integration' ),
            'reply_thread_root' => __( 'Thread Root ID (Nested Replies)', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'userRegistered' ) {
        $fields = [
            // Core User Info
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_login' => __( 'Username', 'advanced-form-integration' ),
            'user_email' => __( 'Email Address', 'advanced-form-integration' ),
            'user_display_name' => __( 'Display Name', 'advanced-form-integration' ),
            'user_first_name' => __( 'First Name', 'advanced-form-integration' ),
            'user_last_name' => __( 'Last Name', 'advanced-form-integration' ),
            'user_url' => __( 'Website URL', 'advanced-form-integration' ),
            
            // Registration Details
            'user_registered' => __( 'Registration Timestamp', 'advanced-form-integration' ),
            'registration_method' => __( 'Registration Method (wpForo)', 'advanced-form-integration' ),
            
            // User Role & Capabilities
            'user_roles' => __( 'User Roles (comma-separated)', 'advanced-form-integration' ),
            'user_capabilities' => __( 'User Capabilities', 'advanced-form-integration' ),
            
            // wpForo Member Profile
            'wpforo_member_status' => __( 'wpForo Member Status', 'advanced-form-integration' ),
            'wpforo_member_type' => __( 'wpForo Member Type', 'advanced-form-integration' ),
            'wpforo_member_joined' => __( 'wpForo Member Joined', 'advanced-form-integration' ),
            
            // Profile
            'user_bio' => __( 'User Bio/Description', 'advanced-form-integration' ),
            'user_avatar_url' => __( 'Avatar URL', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Handle Topic Creation
add_action( 'wpforo_after_add_topic', 'adfoin_wpforo_handle_create_topic', 10, 2 );
function adfoin_wpforo_handle_create_topic( $topic, $forum ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'createTopic' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_info = get_userdata( $topic['userid'] );

    $posted_data = [
        // Core Topic Info
        'topic_id' => $topic['topicid'],
        'topic_title' => $topic['title'],
        'topic_slug' => isset( $topic['slug'] ) ? $topic['slug'] : '',
        
        // Forum Info
        'forum_id' => $forum['forumid'],
        'forum_name' => $forum['title'],
        
        // User Info
        'user_id' => $topic['userid'],
        'user_name' => $user_info->user_login,
        'user_email' => $user_info->user_email,
        
        // Timestamps
        'topic_created' => isset( $topic['created'] ) ? $topic['created'] : '',
        'topic_modified' => isset( $topic['modified'] ) ? $topic['modified'] : '',
        
        // Status & Privacy
        'topic_type' => isset( $topic['type'] ) ? $topic['type'] : 0,
        'topic_status' => isset( $topic['status'] ) ? $topic['status'] : 0,
        'topic_private' => isset( $topic['private'] ) ? $topic['private'] : 0,
        
        // Content Info
        'topic_tags' => isset( $topic['tags'] ) ? $topic['tags'] : '',
        'topic_has_attachments' => isset( $topic['has_attach'] ) ? $topic['has_attach'] : 0,
        
        // Engagement Metrics
        'topic_views' => isset( $topic['views'] ) ? $topic['views'] : 0,
        'topic_replies' => isset( $topic['posts'] ) ? $topic['posts'] : 0,
        
        // SEO
        'topic_meta_key' => isset( $topic['meta_key'] ) ? $topic['meta_key'] : '',
        'topic_meta_desc' => isset( $topic['meta_desc'] ) ? $topic['meta_desc'] : '',
    ];

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

// Handle Reply Creation
add_action( 'wpforo_after_add_post', 'adfoin_wpforo_handle_create_reply', 10, 3 );
function adfoin_wpforo_handle_create_reply( $post, $topic, $forum ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'createReply' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_info = get_userdata( $post['userid'] );

    $posted_data = [
        // Core Reply Info
        'reply_id' => $post['postid'],
        'reply_title' => isset( $post['title'] ) ? $post['title'] : '',
        'reply_content' => isset( $post['body'] ) ? $post['body'] : '',
        'reply_url' => isset( $post['posturl'] ) ? $post['posturl'] : '',
        
        // Topic Info
        'topic_id' => $topic['topicid'],
        'topic_title' => $topic['title'],
        
        // Forum Info
        'forum_id' => $post['forumid'],
        'forum_name' => $forum['title'],
        
        // User Info
        'user_id' => $post['userid'],
        'user_name' => $user_info->user_login,
        'user_email' => $user_info->user_email,
        
        // Timestamps
        'reply_created' => isset( $post['created'] ) ? $post['created'] : '',
        'reply_modified' => isset( $post['modified'] ) ? $post['modified'] : '',
        
        // Status & Privacy
        'reply_status' => isset( $post['status'] ) ? $post['status'] : 0,
        'reply_private' => isset( $post['private'] ) ? $post['private'] : 0,
        
        // Nested Reply Info
        'reply_parent_id' => isset( $post['parentid'] ) ? $post['parentid'] : 0,
        'reply_thread_root' => isset( $post['root'] ) ? $post['root'] : 0,
    ];

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

// Handle User Registration (wpForo specific)
add_action( 'wpforo_create_user_after', 'adfoin_wpforo_handle_user_registration', 10, 1 );
function adfoin_wpforo_handle_user_registration( $data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforo', 'userRegistered' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // Extract user_id from data array
    $user_id = isset( $data['user_id'] ) ? $data['user_id'] : 0;
    
    if ( ! $user_id ) {
        return;
    }

    $user_data = get_userdata( $user_id );
    
    if ( ! $user_data ) {
        return;
    }

    // Get user roles
    $user_roles = isset( $user_data->roles ) ? implode( ', ', $user_data->roles ) : '';
    
    // Get wpForo specific member data if available
    $wpforo_member_status = '';
    $wpforo_member_type = '';
    $wpforo_member_joined = '';
    
    if ( function_exists( 'wpforo_get_member' ) ) {
        $member = wpforo_get_member( $user_id );
        if ( $member ) {
            $wpforo_member_status = isset( $member['status'] ) ? $member['status'] : '';
            $wpforo_member_type = isset( $member['type'] ) ? $member['type'] : '';
            $wpforo_member_joined = isset( $member['joined'] ) ? $member['joined'] : '';
        }
    }

    // Get user avatar
    $user_avatar_url = get_avatar_url( $user_id );

    $posted_data = [
        // Core User Info
        'user_id' => $user_id,
        'user_login' => isset( $data['user_login'] ) ? $data['user_login'] : $user_data->user_login,
        'user_email' => isset( $data['user_email'] ) ? $data['user_email'] : $user_data->user_email,
        'user_display_name' => $user_data->display_name,
        'user_first_name' => isset( $user_data->first_name ) ? $user_data->first_name : '',
        'user_last_name' => isset( $user_data->last_name ) ? $user_data->last_name : '',
        'user_url' => isset( $user_data->user_url ) ? $user_data->user_url : '',
        
        // Registration Details
        'user_registered' => $user_data->user_registered,
        'registration_method' => 'wpForo',
        
        // User Role & Capabilities
        'user_roles' => $user_roles,
        'user_capabilities' => isset( $user_data->caps ) ? count( $user_data->caps ) . ' capabilities' : 'none',
        
        // wpForo Member Profile
        'wpforo_member_status' => $wpforo_member_status,
        'wpforo_member_type' => $wpforo_member_type,
        'wpforo_member_joined' => $wpforo_member_joined,
        
        // Profile
        'user_bio' => isset( $user_data->description ) ? $user_data->description : '',
        'user_avatar_url' => $user_avatar_url,
    ];

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
