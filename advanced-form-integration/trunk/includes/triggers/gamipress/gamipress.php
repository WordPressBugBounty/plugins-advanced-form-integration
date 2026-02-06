<?php
// Get GamiPress triggers
function adfoin_gamipress_get_forms( $form_provider ) {
    if( $form_provider != 'gamipress' ) {
        return;
    }

    $triggers = array(
        'rank_eanred' => __( 'Rank Earned', 'advanced-form-integration' ),
        'achievement_gained' => __( 'Achievement Gained', 'advanced-form-integration' ),
        'achievement_revoked' => __( 'Achievement Revoked', 'advanced-form-integration' ),
        'points_earned' => __( 'Points Earned', 'advanced-form-integration' )
    );

    return $triggers;
}

// Get GamiPress fields
function adfoin_gamipress_get_form_fields( $form_provider, $form_id ) {
    if ( 'gamipress' !== $form_provider ) {
        return;
    }

    $fields = array(
        'user_id'      => __( 'User ID', 'advanced-form-integration' ),
        'user_login'   => __( 'User Login', 'advanced-form-integration' ),
        'first_name'   => __( 'First Name', 'advanced-form-integration' ),
        'last_name'    => __( 'Last Name', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email', 'advanced-form-integration' ),
        'display_name' => __( 'Display Name', 'advanced-form-integration' ),
        'avatar_url'   => __( 'Avatar URL', 'advanced-form-integration' ),
    );

    $form_id = (string) $form_id;

    if ( in_array( $form_id, array( 'rank_eanred', 'rank_earned' ), true ) ) {
        $fields['rank_id']             = __( 'Rank ID', 'advanced-form-integration' );
        $fields['rank']                = __( 'Rank', 'advanced-form-integration' );
        $fields['rank_type']           = __( 'Rank Type', 'advanced-form-integration' );
        $fields['rank_slug']           = __( 'Rank Slug', 'advanced-form-integration' );
        $fields['rank_url']            = __( 'Rank URL', 'advanced-form-integration' );
        $fields['previous_rank_id']    = __( 'Previous Rank ID', 'advanced-form-integration' );
        $fields['previous_rank']       = __( 'Previous Rank', 'advanced-form-integration' );
        $fields['previous_rank_type']  = __( 'Previous Rank Type', 'advanced-form-integration' );
        $fields['previous_rank_slug']  = __( 'Previous Rank Slug', 'advanced-form-integration' );
        $fields['previous_rank_url']   = __( 'Previous Rank URL', 'advanced-form-integration' );
        $fields['admin_id']            = __( 'Admin ID', 'advanced-form-integration' );
        $fields['achievement_id']      = __( 'Related Achievement ID', 'advanced-form-integration' );
    }

    if ( 'achievement_gained' === $form_id ) {
        $fields['achievement_id']              = __( 'Achievement ID', 'advanced-form-integration' );
        $fields['achievement_type']            = __( 'Achievement Type', 'advanced-form-integration' );
        $fields['achievement_title']           = __( 'Achievement Title', 'advanced-form-integration' );
        $fields['achievement_slug']            = __( 'Achievement Slug', 'advanced-form-integration' );
        $fields['achievement_url']             = __( 'Achievement URL', 'advanced-form-integration' );
        $fields['achievement_author_id']       = __( 'Achievement Author ID', 'advanced-form-integration' );
        $fields['achievement_content']         = __( 'Achievement Content', 'advanced-form-integration' );
        $fields['achievement_parent_id']       = __( 'Parent Achievement ID', 'advanced-form-integration' );
        $fields['achievement_parent_title']    = __( 'Parent Achievement Title', 'advanced-form-integration' );
        $fields['achievement_parent_type']     = __( 'Parent Achievement Type', 'advanced-form-integration' );
        $fields['achievement_parent_slug']     = __( 'Parent Achievement Slug', 'advanced-form-integration' );
        $fields['achievement_parent_url']      = __( 'Parent Achievement URL', 'advanced-form-integration' );
        $fields['achievement_parent_author_id']= __( 'Parent Achievement Author ID', 'advanced-form-integration' );
        $fields['achievement_parent_content']  = __( 'Parent Achievement Content', 'advanced-form-integration' );
        $fields['award']                       = __( 'Award Slug', 'advanced-form-integration' );
        $fields['trigger']                     = __( 'Trigger', 'advanced-form-integration' );
        $fields['site_id']                     = __( 'Site ID', 'advanced-form-integration' );
        $fields['trigger_args']                = __( 'Trigger Arguments', 'advanced-form-integration' );
    }

    if ( 'achievement_revoked' === $form_id ) {
        $fields['achievement_id']              = __( 'Achievement ID', 'advanced-form-integration' );
        $fields['achievement_type']            = __( 'Achievement Type', 'advanced-form-integration' );
        $fields['achievement_title']           = __( 'Achievement Title', 'advanced-form-integration' );
        $fields['achievement_slug']            = __( 'Achievement Slug', 'advanced-form-integration' );
        $fields['achievement_url']             = __( 'Achievement URL', 'advanced-form-integration' );
        $fields['achievement_author_id']       = __( 'Achievement Author ID', 'advanced-form-integration' );
        $fields['achievement_content']         = __( 'Achievement Content', 'advanced-form-integration' );
        $fields['achievement_parent_id']       = __( 'Parent Achievement ID', 'advanced-form-integration' );
        $fields['achievement_parent_title']    = __( 'Parent Achievement Title', 'advanced-form-integration' );
        $fields['achievement_parent_type']     = __( 'Parent Achievement Type', 'advanced-form-integration' );
        $fields['achievement_parent_slug']     = __( 'Parent Achievement Slug', 'advanced-form-integration' );
        $fields['achievement_parent_url']      = __( 'Parent Achievement URL', 'advanced-form-integration' );
        $fields['achievement_parent_author_id']= __( 'Parent Achievement Author ID', 'advanced-form-integration' );
        $fields['achievement_parent_content']  = __( 'Parent Achievement Content', 'advanced-form-integration' );
        $fields['earning_id']                  = __( 'Earning ID', 'advanced-form-integration' );
        $fields['post_id']                     = __( 'Post ID', 'advanced-form-integration' );
        $fields['post_title']                  = __( 'Post Title', 'advanced-form-integration' );
        $fields['post_url']                    = __( 'Post URL', 'advanced-form-integration' );
        $fields['post_type']                   = __( 'Post Type', 'advanced-form-integration' );
        $fields['post_author_id']              = __( 'Post Author ID', 'advanced-form-integration' );
        $fields['post_content']                = __( 'Post Content', 'advanced-form-integration' );
        $fields['post_parent_id']              = __( 'Post Parent ID', 'advanced-form-integration' );
    }

    if ( 'points_earned' === $form_id ) {
        $fields['total_points']        = __( 'Total Points', 'advanced-form-integration' );
        $fields['points_type']         = __( 'Points Type', 'advanced-form-integration' );
        $fields['points_type_label']   = __( 'Points Type Label', 'advanced-form-integration' );
        $fields['points_type_id']      = __( 'Points Type ID', 'advanced-form-integration' );
        $fields['new_points']          = __( 'New Points', 'advanced-form-integration' );
        $fields['points_reason']       = __( 'Points Reason', 'advanced-form-integration' );
        $fields['points_log_type']     = __( 'Points Log Type', 'advanced-form-integration' );
        $fields['admin_id']            = __( 'Admin ID', 'advanced-form-integration' );
        $fields['achievement_id']      = __( 'Related Achievement ID', 'advanced-form-integration' );
    }

    return $fields;
}

// Get User data
function adfoin_gamipress_get_userdata( $user_id ) {
    $user_data = array(
        'user_id'      => $user_id ? (int) $user_id : '',
        'user_login'   => '',
        'first_name'   => '',
        'last_name'    => '',
        'avatar_url'   => $user_id ? get_avatar_url( $user_id ) : '',
        'user_email'   => '',
        'display_name' => '',
    );

    if ( ! $user_id ) {
        return $user_data;
    }

    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_login']   = isset( $user->user_login ) ? $user->user_login : '';
        $user_data['first_name']   = isset( $user->first_name ) ? $user->first_name : '';
        $user_data['last_name']    = isset( $user->last_name ) ? $user->last_name : '';
        $user_data['user_email']   = isset( $user->user_email ) ? $user->user_email : '';
        $user_data['display_name'] = isset( $user->display_name ) ? $user->display_name : '';
    }

    return $user_data;
}

/**
 * Retrieve saved integrations for the supplied GamiPress trigger keys.
 *
 * @param Advanced_Form_Integration_Integration $integration Integration instance.
 * @param array<string>                         $trigger_keys Trigger identifiers.
 *
 * @return array<int,array>
 */
function adfoin_gamipress_get_saved_records( Advanced_Form_Integration_Integration $integration, array $trigger_keys ) {
    $records = array();

    foreach ( $trigger_keys as $trigger_key ) {
        $results = $integration->get_by_trigger( 'gamipress', $trigger_key );

        if ( empty( $results ) ) {
            continue;
        }

        foreach ( $results as $record ) {
            if ( isset( $record['id'] ) ) {
                $records[ $record['id'] ] = $record;
            } else {
                $records[] = $record;
            }
        }
    }

    return array_values( $records );
}

/**
 * Extract relevant details from a WP_Post instance.
 *
 * @param WP_Post|int|null $post Post object or ID.
 *
 * @return array<string,mixed>
 */
function adfoin_gamipress_collect_post_details( $post ) {
    if ( $post && ! $post instanceof WP_Post ) {
        $post = get_post( $post );
    }

    $details = array(
        'id'         => '',
        'type'       => '',
        'title'      => '',
        'slug'       => '',
        'url'        => '',
        'author_id'  => '',
        'content'    => '',
        'parent_id'  => '',
    );

    if ( ! ( $post instanceof WP_Post ) ) {
        return $details;
    }

    $details['id']        = $post->ID;
    $details['type']      = $post->post_type;
    $details['title']     = $post->post_title;
    $details['slug']      = $post->post_name;
    $permalink            = get_permalink( $post );
    $details['url']       = is_string( $permalink ) ? $permalink : '';
    $details['author_id'] = $post->post_author;
    $details['content']   = $post->post_content;
    $details['parent_id'] = $post->post_parent;

    return $details;
}

/**
 * Build an achievement context payload.
 *
 * @param int $post_id Achievement or requirement post ID.
 *
 * @return array<string,mixed>
 */
function adfoin_gamipress_prepare_achievement_context( $post_id ) {
    $context = array(
        'achievement_id'                => $post_id ? (int) $post_id : '',
        'achievement_type'              => '',
        'achievement_title'             => '',
        'achievement_slug'              => '',
        'achievement_url'               => '',
        'achievement_author_id'         => '',
        'achievement_content'           => '',
        'achievement_parent_id'         => '',
        'achievement_parent_type'       => '',
        'achievement_parent_title'      => '',
        'achievement_parent_slug'       => '',
        'achievement_parent_url'        => '',
        'achievement_parent_author_id'  => '',
        'achievement_parent_content'    => '',
        'achievement_parent_parent_id'  => '',
    );

    if ( ! $post_id ) {
        return $context;
    }

    $achievement = adfoin_gamipress_collect_post_details( $post_id );

    if ( $achievement['id'] === '' ) {
        return $context;
    }

    $context['achievement_type']      = $achievement['type'];
    $context['achievement_title']     = $achievement['title'];
    $context['achievement_slug']      = $achievement['slug'];
    $context['achievement_url']       = $achievement['url'];
    $context['achievement_author_id'] = $achievement['author_id'];
    $context['achievement_content']   = $achievement['content'];

    $parent_details = adfoin_gamipress_collect_post_details( $achievement['parent_id'] );

    if ( $parent_details['id'] !== '' ) {
        $context['achievement_parent_id']        = $parent_details['id'];
        $context['achievement_parent_type']      = $parent_details['type'];
        $context['achievement_parent_title']     = $parent_details['title'];
        $context['achievement_parent_slug']      = $parent_details['slug'];
        $context['achievement_parent_url']       = $parent_details['url'];
        $context['achievement_parent_author_id'] = $parent_details['author_id'];
        $context['achievement_parent_content']   = $parent_details['content'];
        $context['achievement_parent_parent_id'] = $parent_details['parent_id'];
    }

    return $context;
}

/**
 * Convert arbitrary value to a string suitable for logging.
 *
 * @param mixed $value Value to format.
 *
 * @return string
 */
function adfoin_gamipress_stringify_value( $value ) {
    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    if ( is_object( $value ) || is_array( $value ) ) {
        $encoded = wp_json_encode( $value );

        return is_string( $encoded ) ? $encoded : '';
    }

    return '';
}

add_action( 'gamipress_update_user_rank', 'adfoin_gamipress_update_user_rank', 10, 5 );

function adfoin_gamipress_update_user_rank( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = adfoin_gamipress_get_saved_records(
        $integration,
        array( 'rank_eanred', 'rank_earned' )
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_gamipress_get_userdata( $user_id );

    $posted_data               = $user_data;
    $posted_data['user_id']    = $user_id;
    $posted_data['admin_id']   = $admin_id;
    $posted_data['achievement_id'] = $achievement_id;

    $new_rank_post = $new_rank instanceof WP_Post ? $new_rank : get_post( $new_rank );
    $old_rank_post = $old_rank instanceof WP_Post ? $old_rank : ( $old_rank ? get_post( $old_rank ) : null );

    if ( $new_rank_post instanceof WP_Post ) {
        $posted_data['rank_id']    = $new_rank_post->ID;
        $posted_data['rank']       = $new_rank_post->post_title;
        $posted_data['rank_type']  = $new_rank_post->post_type;
        $posted_data['rank_slug']  = $new_rank_post->post_name;
        $posted_data['rank_url']   = get_permalink( $new_rank_post );
    } else {
        $posted_data['rank']      = '';
        $posted_data['rank_type'] = '';
    }

    if ( $old_rank_post instanceof WP_Post ) {
        $posted_data['previous_rank_id']   = $old_rank_post->ID;
        $posted_data['previous_rank']      = $old_rank_post->post_title;
        $posted_data['previous_rank_type'] = $old_rank_post->post_type;
        $posted_data['previous_rank_slug'] = $old_rank_post->post_name;
        $posted_data['previous_rank_url']  = get_permalink( $old_rank_post );
    }

    $integration->send( $saved_records, $posted_data );
}

add_action( 'gamipress_award_achievement', 'adfoin_gamipress_award_achievement', 10, 5 );

function adfoin_gamipress_award_achievement( $user_id, $achievement_id, $trigger, $site_id, $args ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = adfoin_gamipress_get_saved_records(
        $integration,
        array( 'achievement_gained' )
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_gamipress_get_userdata( $user_id );

    $context = adfoin_gamipress_prepare_achievement_context( $achievement_id );

    $posted_data = $user_data;

    $posted_data['user_id']                    = $user_id;
    $posted_data['achievement_id']             = $context['achievement_id'];
    $posted_data['achievement_type']           = $context['achievement_type'];
    $posted_data['achievement_title']          = $context['achievement_title'];
    $posted_data['achievement_slug']           = $context['achievement_slug'];
    $posted_data['achievement_url']            = $context['achievement_url'];
    $posted_data['achievement_author_id']      = $context['achievement_author_id'];
    $posted_data['achievement_content']        = wp_strip_all_tags( (string) $context['achievement_content'] );
    $posted_data['achievement_parent_id']      = $context['achievement_parent_id'];
    $posted_data['achievement_parent_title']   = $context['achievement_parent_title'];
    $posted_data['achievement_parent_type']    = $context['achievement_parent_type'];
    $posted_data['achievement_parent_slug']    = $context['achievement_parent_slug'];
    $posted_data['achievement_parent_url']     = $context['achievement_parent_url'];
    $posted_data['achievement_parent_author_id']= $context['achievement_parent_author_id'];
    $posted_data['achievement_parent_content'] = wp_strip_all_tags( (string) $context['achievement_parent_content'] );
    $posted_data['award']                      = $context['achievement_slug'];
    $posted_data['trigger']                    = $trigger;
    $posted_data['site_id']                    = $site_id;
    $posted_data['trigger_args']               = adfoin_gamipress_stringify_value( $args );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'gamipress_revoke_achievement_to_user', 'adfoin_gamipress_revoke_achievement_to_user', 10, 3 );

function adfoin_gamipress_revoke_achievement_to_user( $user_id, $achievement_id, $earning_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = adfoin_gamipress_get_saved_records(
        $integration,
        array( 'achievement_revoked' )
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_gamipress_get_userdata( $user_id );

    $context        = adfoin_gamipress_prepare_achievement_context( $achievement_id );
    $achievement    = adfoin_gamipress_collect_post_details( $achievement_id );
    $parent_details = adfoin_gamipress_collect_post_details( $achievement['parent_id'] );

    $reference = $parent_details['id'] !== '' ? $parent_details : $achievement;

    $posted_data = $user_data;

    $posted_data['user_id']                    = $user_id;
    $posted_data['earning_id']                 = $earning_id;
    $posted_data['achievement_id']             = $context['achievement_id'];
    $posted_data['achievement_type']           = $context['achievement_type'];
    $posted_data['achievement_title']          = $context['achievement_title'];
    $posted_data['achievement_slug']           = $context['achievement_slug'];
    $posted_data['achievement_url']            = $context['achievement_url'];
    $posted_data['achievement_author_id']      = $context['achievement_author_id'];
    $posted_data['achievement_content']        = wp_strip_all_tags( (string) $context['achievement_content'] );
    $posted_data['achievement_parent_id']      = $context['achievement_parent_id'];
    $posted_data['achievement_parent_title']   = $context['achievement_parent_title'];
    $posted_data['achievement_parent_type']    = $context['achievement_parent_type'];
    $posted_data['achievement_parent_slug']    = $context['achievement_parent_slug'];
    $posted_data['achievement_parent_url']     = $context['achievement_parent_url'];
    $posted_data['achievement_parent_author_id']= $context['achievement_parent_author_id'];
    $posted_data['achievement_parent_content'] = wp_strip_all_tags( (string) $context['achievement_parent_content'] );

    $posted_data['post_id']        = $achievement_id;
    $posted_data['post_title']     = $reference['title'];
    $posted_data['post_url']       = $reference['url'];
    $posted_data['post_type']      = $reference['type'];
    $posted_data['post_author_id'] = $reference['author_id'];
    $posted_data['post_content']   = wp_strip_all_tags( (string) $reference['content'] );
    $posted_data['post_parent_id'] = $reference['parent_id'];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'gamipress_update_user_points', 'adfoin_gamipress_update_user_points', 10, 8 );

function adfoin_gamipress_update_user_points( $user_id, $new_points, $total_points, $admin_id, $achievement_id, $points_type, $reason, $log_type ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = adfoin_gamipress_get_saved_records(
        $integration,
        array( 'points_earned' )
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_gamipress_get_userdata( $user_id );
    $points_type_object = function_exists( 'gamipress_get_points_type' ) ? gamipress_get_points_type( $points_type ) : null;

    $posted_data = $user_data;

    $posted_data['user_id']        = $user_id;
    $posted_data['total_points']   = $total_points;
    $posted_data['new_points']     = $new_points;
    $posted_data['points_type']    = is_object( $points_type_object ) ? $points_type_object->post_name : $points_type;
    $posted_data['points_type_id'] = is_object( $points_type_object ) ? $points_type_object->ID : '';
    $posted_data['points_type_label'] = is_object( $points_type_object ) ? $points_type_object->post_title : '';
    $posted_data['points_reason']  = adfoin_gamipress_stringify_value( $reason );
    $posted_data['points_log_type']= $log_type;
    $posted_data['admin_id']       = $admin_id;
    $posted_data['achievement_id'] = $achievement_id;

    $integration->send( $saved_records, $posted_data );
}
