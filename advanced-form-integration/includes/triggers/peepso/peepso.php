<?php

// Get PeepSo Triggers
function adfoin_peepso_get_forms( $form_provider ) {
    if ( $form_provider !== 'peepso' ) {
        return;
    }

    $triggers = array(
        'userGainsFollower'      => __( 'User Gains a New Follower', 'advanced-form-integration' ),
        'userLosesFollower'      => __( 'User Loses a Follower', 'advanced-form-integration' ),
        'userPublishesActivity'  => __( 'User Publishes an Activity Post', 'advanced-form-integration' ),
        'userUnfollowsUser'      => __( 'User Unfollows a User', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get PeepSo Fields
function adfoin_peepso_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'peepso' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'userGainsFollower' ) {
        $fields = array(
            'user_id'     => __( 'User ID', 'advanced-form-integration' ),
            'follower_id' => __( 'Follower ID', 'advanced-form-integration' ),
            'avatar_url'  => __( 'Follower Avatar URL', 'advanced-form-integration' ),
            'username'    => __( 'Follower Username', 'advanced-form-integration' ),
            'first_name'  => __( 'Follower First Name', 'advanced-form-integration' ),
            'last_name'   => __( 'Follower Last Name', 'advanced-form-integration' ),
            'gender'      => __( 'Follower Gender', 'advanced-form-integration' ),
            'birthdate'   => __( 'Follower Birthdate', 'advanced-form-integration' ),
            'followers'   => __( 'Follower Count', 'advanced-form-integration' ),
            'following'   => __( 'Following Count', 'advanced-form-integration' ),
            'profile_url' => __( 'Follower Profile URL', 'advanced-form-integration' ),
            'email'       => __( 'Follower Email', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'userLosesFollower' ) {
        $fields = array(
            'user_id'     => __( 'User ID', 'advanced-form-integration' ),
            'follower_id' => __( 'Follower ID', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'userPublishesActivity' ) {
        $fields = array(
            'user_id'      => __( 'User ID', 'advanced-form-integration' ),
            'activity_id'  => __( 'Activity Post ID', 'advanced-form-integration' ),
            'post_content' => __( 'Activity Post Content', 'advanced-form-integration' ),
            'post_url'     => __( 'Activity Post URL', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'userUnfollowsUser' ) {
        $fields = array(
            'user_id'             => __( 'User ID', 'advanced-form-integration' ),
            'unfollowed_user_id'  => __( 'Unfollowed User ID', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// --------------------------------------------------------------------
// Handle "User Gains a New Follower"
// --------------------------------------------------------------------
add_action( 'peepso_ajax_start', 'adfoin_peepso_handle_user_gains_follower', 10, 1 );
function adfoin_peepso_handle_user_gains_follower( $action ) {
    // Only proceed if the AJAX action matches the follow-status update.
    if ( $action !== 'followerajax.set_follow_status' ) {
        return;
    }

    // In a "gain" event the POST variable "follow" should not be 0.
    if ( function_exists( 'automator_filter_has_var' ) && automator_filter_has_var( 'follow', INPUT_POST ) ) {
        $follow = automator_filter_input( 'follow', INPUT_POST );
        if ( intval( $follow ) === 0 ) {
            return;
        }
    } else {
        return;
    }

    $user_id     = isset( $_POST['uid'] ) ? absint( $_POST['uid'] ) : false;
    $follower_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : false;
    if ( ! $user_id || ! $follower_id ) {
        return;
    }

    if ( ! class_exists( 'PeepSoUser' ) ) {
        return;
    }
    $peepso_user = PeepSoUser::get_instance( $follower_id );
    if ( ! $peepso_user ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'peepso', 'userGainsFollower' );
    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'user_id'     => $user_id,
        'follower_id' => $follower_id,
        'avatar_url'  => $peepso_user->get_avatar(),
        'username'    => $peepso_user->get_username(),
        'first_name'  => Automator()->helpers->recipe->peepso->get_name( $peepso_user->get_fullname(), 'first' ),
        'last_name'   => Automator()->helpers->recipe->peepso->get_name( $peepso_user->get_fullname(), 'last' ),
        'gender'      => Automator()->helpers->recipe->peepso->get_gender( $follower_id ),
        'birthdate'   => Automator()->helpers->recipe->peepso->get_birthdate( $follower_id ),
        'followers'   => class_exists( 'PeepSoUserFollower' ) ? PeepSoUserFollower::count_followers( $follower_id ) : 0,
        'following'   => class_exists( 'PeepSoUserFollower' ) ? PeepSoUserFollower::count_following( $follower_id ) : 0,
        'profile_url' => $peepso_user->get_profileurl(),
        'email'       => $peepso_user->get_email(),
    );

    $integration->send( $saved_records, $posted_data );
}

// --------------------------------------------------------------------
// Handle "User Loses a Follower"
// --------------------------------------------------------------------
add_action( 'peepso_ajax_start', 'adfoin_peepso_handle_user_loses_follower', 10, 1 );
function adfoin_peepso_handle_user_loses_follower( $action ) {
    if ( $action !== 'followerajax.set_follow_status' ) {
        return;
    }

    // For a loss event, the "follow" POST variable should be 0.
    if ( function_exists( 'automator_filter_has_var' ) && automator_filter_has_var( 'follow', INPUT_POST ) ) {
        $follow = automator_filter_input( 'follow', INPUT_POST );
        if ( intval( $follow ) === 1 ) {
            return;
        }
    } else {
        return;
    }

    $user_id     = isset( $_POST['uid'] ) ? absint( $_POST['uid'] ) : false;
    $follower_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : false;
    if ( ! $user_id || ! $follower_id ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'peepso', 'userLosesFollower' );
    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'user_id'     => $user_id,
        'follower_id' => $follower_id,
    );

    $integration->send( $saved_records, $posted_data );
}

// --------------------------------------------------------------------
// Handle "User Publishes an Activity Post"
// --------------------------------------------------------------------
add_action( 'peepso_activity_after_add_post', 'adfoin_peepso_handle_user_publishes_activity', 10, 2 );
function adfoin_peepso_handle_user_publishes_activity( $external_act_id, $act_id ) {
    global $user_ID;
    if ( ! $user_ID ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'peepso', 'userPublishesActivity' );
    if ( empty( $saved_records ) ) {
        return;
    }

    // Get the activity post content.
    $activity_post = get_post( $external_act_id );
    if ( ! $activity_post ) {
        return;
    }

    // Assume PeepSoActivity class is available to fetch activity details.
    if ( ! class_exists( 'PeepSoActivity' ) ) {
        return;
    }
    $peep_activity = new PeepSoActivity();
    $activity_data = $peep_activity->get_activity( $act_id );

    $posted_data = array(
        'user_id'      => $user_ID,
        'activity_id'  => $act_id,
        'post_content' => $activity_post->post_content,
        'post_url'     => PeepSo::get_page( 'activity_status', false ) . get_the_title( $activity_data->act_external_id ),
    );

    $integration->send( $saved_records, $posted_data );
}

// --------------------------------------------------------------------
// Handle "User Unfollows a User"
// --------------------------------------------------------------------
add_action( 'peepso_ajax_start', 'adfoin_peepso_handle_user_unfollows_user', 10, 1 );
function adfoin_peepso_handle_user_unfollows_user( $action ) {
    if ( $action !== 'followerajax.set_follow_status' ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'peepso', 'userUnfollowsUser' );
    if ( empty( $saved_records ) ) {
        return;
    }

    // For an unfollow event, the "follow" POST variable should not equal 1.
    if ( function_exists( 'automator_filter_has_var' ) && automator_filter_has_var( 'follow', INPUT_POST ) ) {
        $follow = automator_filter_input( 'follow', INPUT_POST );
        if ( intval( $follow ) === 1 ) {
            return;
        }
    } else {
        return;
    }

    $user_id     = isset( $_POST['uid'] ) ? absint( $_POST['uid'] ) : false;
    $follower_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : false;
    if ( ! $user_id || ! $follower_id ) {
        return;
    }

    if ( ! class_exists( 'PeepSoUser' ) ) {
        return;
    }

    $peepso_user   = PeepSoUser::get_instance( $follower_id );
    $peepso_c_user = PeepSoUser::get_instance( $user_id );
    if ( ! $peepso_user || ! $peepso_c_user ) {
        return;
    }

    $posted_data = array(
        'user_id'             => $user_id,
        'unfollowed_user_id'  => $follower_id,
        // Additional data from both user objects.
        'follower_avatar'     => $peepso_user->get_avatar(),
        'follower_username'   => $peepso_user->get_username(),
        'follower_first_name' => Automator()->helpers->recipe->peepso->get_name( $peepso_user->get_fullname(), 'first' ),
        'follower_last_name'  => Automator()->helpers->recipe->peepso->get_name( $peepso_user->get_fullname(), 'last' ),
        'follower_gender'     => Automator()->helpers->recipe->peepso->get_gender( $follower_id ),
        'follower_birthdate'  => Automator()->helpers->recipe->peepso->get_birthdate( $follower_id ),
        'follower_followers'  => class_exists( 'PeepSoUserFollower' ) ? PeepSoUserFollower::count_followers( $follower_id ) : 0,
        'follower_following'  => class_exists( 'PeepSoUserFollower' ) ? PeepSoUserFollower::count_following( $follower_id ) : 0,
        'follower_profileurl' => $peepso_user->get_profileurl(),
        'follower_email'      => $peepso_user->get_email(),
        'user_avatar'         => $peepso_c_user->get_avatar(),
        'user_gender'         => Automator()->helpers->recipe->peepso->get_gender( $user_id ),
        'user_birthdate'      => Automator()->helpers->recipe->peepso->get_birthdate( $user_id ),
        'user_followers'      => class_exists( 'PeepSoUserFollower' ) ? PeepSoUserFollower::count_followers( $user_id ) : 0,
        'user_following'      => class_exists( 'PeepSoUserFollower' ) ? PeepSoUserFollower::count_following( $user_id ) : 0,
        'user_profileurl'     => $peepso_c_user->get_profileurl(),
        'user_email'          => $peepso_c_user->get_email(),
    );

    $integration->send( $saved_records, $posted_data );
}