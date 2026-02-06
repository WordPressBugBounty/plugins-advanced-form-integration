<?php

// Get Fluent Community triggers.
function adfoin_fluentcommunity_get_forms( $form_provider ) {
    if ( $form_provider !== 'fluentcommunity' ) {
        return;
    }

    return array(
        'spaceCreated'   => __( 'Space Created', 'advanced-form-integration' ),
        'spaceJoined'    => __( 'Member Joined Space', 'advanced-form-integration' ),
        'feedCreated'    => __( 'Feed Created', 'advanced-form-integration' ),
        'courseEnrolled' => __( 'Course Enrollment', 'advanced-form-integration' ),
    );
}

// Get Fluent Community fields.
function adfoin_fluentcommunity_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'fluentcommunity' ) {
        return;
    }

    $fields = array();

    if ( 'spaceCreated' === $form_id ) {
        $fields = array(
            'space_id'          => __( 'Space ID', 'advanced-form-integration' ),
            'space_title'       => __( 'Space Title', 'advanced-form-integration' ),
            'space_slug'        => __( 'Space Slug', 'advanced-form-integration' ),
            'space_description' => __( 'Space Description', 'advanced-form-integration' ),
            'space_type'        => __( 'Space Type', 'advanced-form-integration' ),
            'space_privacy'     => __( 'Space Privacy', 'advanced-form-integration' ),
            'space_status'      => __( 'Space Status', 'advanced-form-integration' ),
            'space_created_by'  => __( 'Space Created By (User ID)', 'advanced-form-integration' ),
            'space_logo'        => __( 'Space Logo', 'advanced-form-integration' ),
            'space_cover_photo' => __( 'Space Cover Photo', 'advanced-form-integration' ),
            'space_settings'    => __( 'Space Settings (JSON)', 'advanced-form-integration' ),
            'space_meta'        => __( 'Space Meta (JSON)', 'advanced-form-integration' ),
            'space_url'         => __( 'Space URL', 'advanced-form-integration' ),
            'space_data'        => __( 'Space Additional Data (JSON)', 'advanced-form-integration' ),
        );
    } elseif ( 'spaceJoined' === $form_id ) {
        $fields = array(
            'space_id'              => __( 'Space ID', 'advanced-form-integration' ),
            'space_title'           => __( 'Space Title', 'advanced-form-integration' ),
            'space_slug'            => __( 'Space Slug', 'advanced-form-integration' ),
            'space_type'            => __( 'Space Type', 'advanced-form-integration' ),
            'space_privacy'         => __( 'Space Privacy', 'advanced-form-integration' ),
            'space_status'          => __( 'Space Status', 'advanced-form-integration' ),
            'space_created_by'      => __( 'Space Created By (User ID)', 'advanced-form-integration' ),
            'space_url'             => __( 'Space URL', 'advanced-form-integration' ),
            'user_id'               => __( 'User ID', 'advanced-form-integration' ),
            'user_login'            => __( 'User Login', 'advanced-form-integration' ),
            'user_email'            => __( 'User Email', 'advanced-form-integration' ),
            'user_display_name'     => __( 'User Display Name', 'advanced-form-integration' ),
            'user_first_name'       => __( 'User First Name', 'advanced-form-integration' ),
            'user_last_name'        => __( 'User Last Name', 'advanced-form-integration' ),
            'user_roles'            => __( 'User Roles (JSON)', 'advanced-form-integration' ),
            'user_registered'       => __( 'User Registered At', 'advanced-form-integration' ),
            'user_url'              => __( 'User URL', 'advanced-form-integration' ),
            'membership_id'         => __( 'Membership ID', 'advanced-form-integration' ),
            'membership_role'       => __( 'Membership Role', 'advanced-form-integration' ),
            'membership_status'     => __( 'Membership Status', 'advanced-form-integration' ),
            'membership_created_at' => __( 'Membership Created At', 'advanced-form-integration' ),
            'membership_updated_at' => __( 'Membership Updated At', 'advanced-form-integration' ),
            'joined_by'             => __( 'Joined By', 'advanced-form-integration' ),
        );
    } elseif ( 'feedCreated' === $form_id ) {
        $fields = array(
            'feed_id'             => __( 'Feed ID', 'advanced-form-integration' ),
            'feed_title'          => __( 'Feed Title', 'advanced-form-integration' ),
            'feed_message'        => __( 'Feed Message', 'advanced-form-integration' ),
            'feed_message_rendered' => __( 'Feed Message Rendered', 'advanced-form-integration' ),
            'feed_type'           => __( 'Feed Type', 'advanced-form-integration' ),
            'feed_content_type'   => __( 'Feed Content Type', 'advanced-form-integration' ),
            'feed_space_id'       => __( 'Feed Space ID', 'advanced-form-integration' ),
            'feed_privacy'        => __( 'Feed Privacy', 'advanced-form-integration' ),
            'feed_status'         => __( 'Feed Status', 'advanced-form-integration' ),
            'feed_priority'       => __( 'Feed Priority', 'advanced-form-integration' ),
            'feed_comments_count' => __( 'Feed Comments Count', 'advanced-form-integration' ),
            'feed_reactions_count'=> __( 'Feed Reactions Count', 'advanced-form-integration' ),
            'feed_featured_image' => __( 'Feed Featured Image', 'advanced-form-integration' ),
            'feed_meta'           => __( 'Feed Meta (JSON)', 'advanced-form-integration' ),
            'feed_url'            => __( 'Feed URL', 'advanced-form-integration' ),
            'feed_created_at'     => __( 'Feed Created At', 'advanced-form-integration' ),
            'feed_updated_at'     => __( 'Feed Updated At', 'advanced-form-integration' ),
            'author_id'           => __( 'Author ID', 'advanced-form-integration' ),
            'author_login'        => __( 'Author Login', 'advanced-form-integration' ),
            'author_email'        => __( 'Author Email', 'advanced-form-integration' ),
            'author_display_name' => __( 'Author Display Name', 'advanced-form-integration' ),
            'author_roles'        => __( 'Author Roles (JSON)', 'advanced-form-integration' ),
        );
    } elseif ( 'courseEnrolled' === $form_id ) {
        $fields = array(
            'course_id'              => __( 'Course ID', 'advanced-form-integration' ),
            'course_title'           => __( 'Course Title', 'advanced-form-integration' ),
            'course_slug'            => __( 'Course Slug', 'advanced-form-integration' ),
            'course_description'     => __( 'Course Description', 'advanced-form-integration' ),
            'course_type'            => __( 'Course Type', 'advanced-form-integration' ),
            'course_privacy'         => __( 'Course Privacy', 'advanced-form-integration' ),
            'course_status'          => __( 'Course Status', 'advanced-form-integration' ),
            'course_created_by'      => __( 'Course Created By (User ID)', 'advanced-form-integration' ),
            'course_settings'        => __( 'Course Settings (JSON)', 'advanced-form-integration' ),
            'course_meta'            => __( 'Course Meta (JSON)', 'advanced-form-integration' ),
            'course_url'             => __( 'Course URL', 'advanced-form-integration' ),
            'user_id'                => __( 'User ID', 'advanced-form-integration' ),
            'user_login'             => __( 'User Login', 'advanced-form-integration' ),
            'user_email'             => __( 'User Email', 'advanced-form-integration' ),
            'user_display_name'      => __( 'User Display Name', 'advanced-form-integration' ),
            'user_first_name'        => __( 'User First Name', 'advanced-form-integration' ),
            'user_last_name'         => __( 'User Last Name', 'advanced-form-integration' ),
            'user_roles'             => __( 'User Roles (JSON)', 'advanced-form-integration' ),
            'user_registered'        => __( 'User Registered At', 'advanced-form-integration' ),
            'user_url'               => __( 'User URL', 'advanced-form-integration' ),
            'enrollment_id'          => __( 'Enrollment ID', 'advanced-form-integration' ),
            'enrollment_role'        => __( 'Enrollment Role', 'advanced-form-integration' ),
            'enrollment_status'      => __( 'Enrollment Status', 'advanced-form-integration' ),
            'enrollment_created_at'  => __( 'Enrollment Created At', 'advanced-form-integration' ),
            'enrollment_updated_at'  => __( 'Enrollment Updated At', 'advanced-form-integration' ),
            'enrolled_by'            => __( 'Enrolled By', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

/**
 * Normalize Fluent Community data.
 *
 * @param mixed $data Data to normalize.
 *
 * @return array
 */
function adfoin_fluentcommunity_normalize_data( $data ) {
    if ( empty( $data ) ) {
        return array();
    }

    if ( is_array( $data ) ) {
        return $data;
    }

    $converted = json_decode( wp_json_encode( $data ), true );

    return is_array( $converted ) ? $converted : array();
}

/**
 * Prepare payload for a space-like object.
 *
 * @param mixed  $space   Space or course data.
 * @param string $prefix  Field prefix.
 *
 * @return array
 */
function adfoin_fluentcommunity_prepare_space_payload( $space, $prefix = 'space_' ) {
    $space_data = adfoin_fluentcommunity_normalize_data( $space );

    if ( empty( $space_data ) ) {
        return array();
    }

    $payload = array(
        $prefix . 'id'          => $space_data['id'] ?? '',
        $prefix . 'title'       => $space_data['title'] ?? '',
        $prefix . 'slug'        => $space_data['slug'] ?? '',
        $prefix . 'description' => $space_data['description'] ?? '',
        $prefix . 'type'        => $space_data['type'] ?? '',
        $prefix . 'privacy'     => $space_data['privacy'] ?? '',
        $prefix . 'status'      => $space_data['status'] ?? '',
        $prefix . 'created_by'  => $space_data['created_by'] ?? '',
        $prefix . 'logo'        => $space_data['logo'] ?? '',
        $prefix . 'cover_photo' => $space_data['cover_photo'] ?? '',
    );

    if ( isset( $space_data['settings'] ) ) {
        $payload[ $prefix . 'settings' ] = is_array( $space_data['settings'] )
            ? wp_json_encode( $space_data['settings'] )
            : $space_data['settings'];
    }

    if ( isset( $space_data['meta'] ) ) {
        $payload[ $prefix . 'meta' ] = is_array( $space_data['meta'] )
            ? wp_json_encode( $space_data['meta'] )
            : $space_data['meta'];
    }

    if ( is_object( $space ) && method_exists( $space, 'getPermalink' ) ) {
        $payload[ $prefix . 'url' ] = $space->getPermalink();
    }

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Prepare user payload.
 *
 * @param int    $user_id User ID.
 * @param string $prefix  Field prefix.
 *
 * @return array
 */
function adfoin_fluentcommunity_prepare_user_payload( $user_id, $prefix = 'user_' ) {
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return array();
    }

    $payload = array(
        $prefix . 'id'            => $user->ID,
        $prefix . 'login'         => $user->user_login,
        $prefix . 'email'         => $user->user_email,
        $prefix . 'display_name'  => $user->display_name,
        $prefix . 'first_name'    => get_user_meta( $user->ID, 'first_name', true ),
        $prefix . 'last_name'     => get_user_meta( $user->ID, 'last_name', true ),
        $prefix . 'roles'         => wp_json_encode( $user->roles ),
        $prefix . 'registered'    => $user->user_registered,
        $prefix . 'url'           => $user->user_url,
        $prefix . 'nicename'      => $user->user_nicename,
    );

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Prepare membership or enrollment payload.
 *
 * @param mixed  $space   Space object.
 * @param int    $user_id User ID.
 * @param mixed  $pivot   Pivot data.
 * @param string $prefix  Field prefix.
 *
 * @return array
 */
function adfoin_fluentcommunity_prepare_membership_payload( $space, $user_id, $pivot = null, $prefix = 'membership_' ) {
    $membership = array();

    if ( $pivot ) {
        $membership = adfoin_fluentcommunity_normalize_data( $pivot );
    } elseif ( is_object( $space ) && method_exists( $space, 'getMembership' ) ) {
        $membership_model = $space->getMembership( $user_id );
        if ( $membership_model ) {
            $membership = adfoin_fluentcommunity_normalize_data( $membership_model );
        }
    }

    if ( empty( $membership ) ) {
        return array();
    }

    $payload = array(
        $prefix . 'id'         => $membership['id'] ?? '',
        $prefix . 'role'       => $membership['role'] ?? '',
        $prefix . 'status'     => $membership['status'] ?? '',
        $prefix . 'created_at' => $membership['created_at'] ?? '',
        $prefix . 'updated_at' => $membership['updated_at'] ?? '',
    );

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Prepare feed payload.
 *
 * @param mixed $feed Feed data.
 *
 * @return array
 */
function adfoin_fluentcommunity_prepare_feed_payload( $feed ) {
    $feed_data = adfoin_fluentcommunity_normalize_data( $feed );

    if ( empty( $feed_data ) ) {
        return array();
    }

    $payload = array(
        'feed_id'              => $feed_data['id'] ?? '',
        'feed_title'           => $feed_data['title'] ?? '',
        'feed_message'         => $feed_data['message'] ?? '',
        'feed_message_rendered'=> $feed_data['message_rendered'] ?? '',
        'feed_type'            => $feed_data['type'] ?? '',
        'feed_content_type'    => $feed_data['content_type'] ?? '',
        'feed_space_id'        => $feed_data['space_id'] ?? '',
        'feed_privacy'         => $feed_data['privacy'] ?? '',
        'feed_status'          => $feed_data['status'] ?? '',
        'feed_priority'        => $feed_data['priority'] ?? '',
        'feed_comments_count'  => $feed_data['comments_count'] ?? '',
        'feed_reactions_count' => $feed_data['reactions_count'] ?? '',
        'feed_featured_image'  => $feed_data['featured_image'] ?? '',
        'feed_created_at'      => $feed_data['created_at'] ?? '',
        'feed_updated_at'      => $feed_data['updated_at'] ?? '',
    );

    if ( isset( $feed_data['meta'] ) ) {
        $meta = $feed_data['meta'];
        if ( is_string( $meta ) ) {
            $meta = maybe_unserialize( $meta );
        }
        $payload['feed_meta'] = is_array( $meta ) ? wp_json_encode( $meta ) : $feed_data['meta'];
    }

    if ( is_object( $feed ) && method_exists( $feed, 'getPermalink' ) ) {
        $payload['feed_url'] = $feed->getPermalink();
    }

    $payload = array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );

    $author_payload = array();
    if ( ! empty( $feed_data['user_id'] ) ) {
        $author_payload = adfoin_fluentcommunity_prepare_user_payload( $feed_data['user_id'], 'author_' );
    }

    return array_merge( $payload, $author_payload );
}

/**
 * Dispatch data to saved integrations.
 *
 * @param string $trigger Trigger key.
 * @param array  $payload Payload data.
 *
 * @return void
 */
function adfoin_fluentcommunity_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $records     = $integration->get_by_trigger( 'fluentcommunity', $trigger );

    if ( empty( $records ) ) {
        return;
    }

    $integration->send( $records, $payload );
}

// Handle space created events.
add_action( 'fluent_community/space/created', 'adfoin_fluentcommunity_handle_space_created', 10, 2 );
function adfoin_fluentcommunity_handle_space_created( $space, $data = array() ) {
    $payload = adfoin_fluentcommunity_prepare_space_payload( $space );

    if ( ! empty( $data ) ) {
        $payload['space_data'] = wp_json_encode( adfoin_fluentcommunity_normalize_data( $data ) );
    }

    adfoin_fluentcommunity_dispatch( 'spaceCreated', $payload );
}

// Handle space joined events.
add_action( 'fluent_community/space/joined', 'adfoin_fluentcommunity_handle_space_joined', 10, 4 );
function adfoin_fluentcommunity_handle_space_joined( $space, $user_id, $by = '', $pivot = null ) {
    $payload = array_merge(
        adfoin_fluentcommunity_prepare_space_payload( $space ),
        adfoin_fluentcommunity_prepare_user_payload( $user_id ),
        adfoin_fluentcommunity_prepare_membership_payload( $space, $user_id, $pivot )
    );

    if ( $by ) {
        $payload['joined_by'] = $by;
    }

    adfoin_fluentcommunity_dispatch( 'spaceJoined', $payload );
}

// Handle feed created events.
add_action( 'fluent_community/feed/created', 'adfoin_fluentcommunity_handle_feed_created', 10, 1 );
function adfoin_fluentcommunity_handle_feed_created( $feed ) {
    $payload = adfoin_fluentcommunity_prepare_feed_payload( $feed );

    adfoin_fluentcommunity_dispatch( 'feedCreated', $payload );
}

// Handle course enrollment events.
add_action( 'fluent_community/course/enrolled', 'adfoin_fluentcommunity_handle_course_enrolled', 10, 4 );
function adfoin_fluentcommunity_handle_course_enrolled( $course, $user_id, $by = '', $pivot = null ) {
    $payload = array_merge(
        adfoin_fluentcommunity_prepare_space_payload( $course, 'course_' ),
        adfoin_fluentcommunity_prepare_user_payload( $user_id ),
        adfoin_fluentcommunity_prepare_membership_payload( $course, $user_id, $pivot, 'enrollment_' )
    );

    if ( $by ) {
        $payload['enrolled_by'] = $by;
    }

    adfoin_fluentcommunity_dispatch( 'courseEnrolled', $payload );
}
