<?php
/**
 * Integration functions for WPUserManager events.
 *
 * This file defines the triggers, fields, and event handlers for WPUserManager.
 * It follows a similar structure to the Asgaros Forum integration.
 */

/**
 * Get WPUserManager Triggers.
 *
 * @param string $form_provider The current integration provider.
 * @return array|void
 */
function adfoin_wpusermanager_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpusermanager' ) {
        return;
    }

    $triggers = array(
        'userApproved'             => __( 'User Approved', 'advanced-form-integration' ),
        'groupMembershipApproved'  => __( 'Group Membership Approved', 'advanced-form-integration' ),
        'groupMembershipRejected'  => __( 'Group Membership Rejected', 'advanced-form-integration' ),
        'userJoinsGroup'           => __( 'User Joins Group', 'advanced-form-integration' ),
        'userLeavesGroup'          => __( 'User Leaves Group', 'advanced-form-integration' ),
        'userRejected'             => __( 'User Rejected', 'advanced-form-integration' ),
    );

    return $triggers;
}

/**
 * Get WPUserManager Form Fields.
 *
 * @param string $form_provider The integration provider.
 * @param string $form_id       The specific trigger ID.
 * @return array|void
 */
function adfoin_wpusermanager_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpusermanager' ) {
        return;
    }

    $fields = array();

    switch ( $form_id ) {
        case 'userApproved':
            $fields = array(
                'user_id'      => __( 'User ID', 'advanced-form-integration' ),
                'display_name' => __( 'Display Name', 'advanced-form-integration' ),
                'user_email'   => __( 'User Email', 'advanced-form-integration' ),
            );
            break;

        case 'groupMembershipApproved':
            $fields = array(
                'group_id'     => __( 'Group ID', 'advanced-form-integration' ),
                'group_name'   => __( 'Group Name', 'advanced-form-integration' ),
                'user_id'      => __( 'User ID', 'advanced-form-integration' ),
                'display_name' => __( 'Display Name', 'advanced-form-integration' ),
            );
            break;

        case 'groupMembershipRejected':
            $fields = array(
                'group_id'     => __( 'Group ID', 'advanced-form-integration' ),
                'group_name'   => __( 'Group Name', 'advanced-form-integration' ),
                'user_id'      => __( 'User ID', 'advanced-form-integration' ),
                'display_name' => __( 'Display Name', 'advanced-form-integration' ),
            );
            break;

        case 'userJoinsGroup':
            $fields = array(
                'group_id'      => __( 'Group ID', 'advanced-form-integration' ),
                'group_name'    => __( 'Group Name', 'advanced-form-integration' ),
                'user_id'       => __( 'User ID', 'advanced-form-integration' ),
                'display_name'  => __( 'Display Name', 'advanced-form-integration' ),
                'privacy_method'=> __( 'Privacy Method', 'advanced-form-integration' ),
            );
            break;

        case 'userLeavesGroup':
            $fields = array(
                'group_id'     => __( 'Group ID', 'advanced-form-integration' ),
                'group_name'   => __( 'Group Name', 'advanced-form-integration' ),
                'user_id'      => __( 'User ID', 'advanced-form-integration' ),
                'display_name' => __( 'Display Name', 'advanced-form-integration' ),
            );
            break;

        case 'userRejected':
            $fields = array(
                'user_id'      => __( 'User ID', 'advanced-form-integration' ),
                'display_name' => __( 'Display Name', 'advanced-form-integration' ),
                'user_email'   => __( 'User Email', 'advanced-form-integration' ),
            );
            break;
    }

    return $fields;
}

/**
 * Get User Data.
 *
 * Returns basic user data for use in the integration.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_wpusermanager_get_userdata( $user_id ) {
    $user_data = array();
    $user      = get_userdata( $user_id );

    if ( $user ) {
        $user_data['first_name'] = $user->first_name;
        $user_data['last_name']  = $user->last_name;
        $user_data['nickname']   = $user->nickname;
        $user_data['avatar_url'] = get_avatar_url( $user_id );
        $user_data['user_email'] = $user->user_email;
        $user_data['user_id']    = $user_id;
    }

    return $user_data;
}

/* -------------------------------------------------------------------------- */
/*                         WPUserManager Event Handlers                       */
/* -------------------------------------------------------------------------- */

/**
 * Handle User Approved.
 *
 * Fired when a user is approved (via the hook provided by WPUserManager).
 *
 * @param int $user_id The ID of the approved user.
 */
function adfoin_wpusermanager_handle_user_approved( $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpusermanager', 'userApproved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'user_id'      => $user_id,
        'display_name' => $user->display_name,
        'user_email'   => $user->user_email,
    );

    $integration->send( $saved_records, $posted_data );
}
add_action( 'wpumuv_after_user_approval', 'adfoin_wpusermanager_handle_user_approved', 10, 1 );

/**
 * Handle Group Membership Approved.
 *
 * Fired when a user’s group membership is approved.
 *
 * @param int $group_id The group ID.
 * @param int $user_id  The user ID.
 */
function adfoin_wpusermanager_handle_group_membership_approved( $group_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpusermanager', 'groupMembershipApproved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! class_exists( 'WPUM_Groups' ) ) {
        return;
    }

    // Retrieve the group name. This example assumes the group is stored as a post.
    $group_name = get_post_field( 'post_title', $group_id );

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'group_id'     => $group_id,
        'group_name'   => $group_name,
        'user_id'      => $user_id,
        'display_name' => $user->display_name,
    );

    $integration->send( $saved_records, $posted_data );
}
add_action( 'wpumgp_after_membership_approved', 'adfoin_wpusermanager_handle_group_membership_approved', 10, 2 );

/**
 * Handle Group Membership Rejected.
 *
 * Fired when a user’s request to join a group is rejected.
 *
 * @param int $group_id The group ID.
 * @param int $user_id  The user ID.
 */
function adfoin_wpusermanager_handle_group_membership_rejected( $group_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpusermanager', 'groupMembershipRejected' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! class_exists( 'WPUM_Groups' ) ) {
        return;
    }

    $group_name = get_post_field( 'post_title', $group_id );

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'group_id'     => $group_id,
        'group_name'   => $group_name,
        'user_id'      => $user_id,
        'display_name' => $user->display_name,
    );

    $integration->send( $saved_records, $posted_data );
}
add_action( 'wpumgp_after_membership_rejected', 'adfoin_wpusermanager_handle_group_membership_rejected', 10, 2 );

/**
 * Handle User Joins Group.
 *
 * Fired when a user joins a group.
 *
 * @param int    $group_id       The group ID.
 * @param int    $user_id        The user ID.
 * @param string $privacy_method The group’s privacy method.
 */
function adfoin_wpusermanager_handle_user_joins_group( $group_id, $user_id, $privacy_method ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpusermanager', 'userJoinsGroup' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! class_exists( 'WPUM_Groups' ) ) {
        return;
    }

    $group_name = get_post_field( 'post_title', $group_id );

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'group_id'       => $group_id,
        'group_name'     => $group_name,
        'user_id'        => $user_id,
        'display_name'   => $user->display_name,
        'privacy_method' => $privacy_method,
    );

    $integration->send( $saved_records, $posted_data );
}
add_action( 'wpumgp_after_member_join', 'adfoin_wpusermanager_handle_user_joins_group', 10, 3 );

/**
 * Handle User Leaves Group.
 *
 * Fired when a user leaves a group.
 *
 * @param int $group_id The group ID.
 * @param int $user_id  The user ID.
 */
function adfoin_wpusermanager_handle_user_leaves_group( $group_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpusermanager', 'userLeavesGroup' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! class_exists( 'WPUM_Groups' ) ) {
        return;
    }

    $group_name = get_post_field( 'post_title', $group_id );

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'group_id'     => $group_id,
        'group_name'   => $group_name,
        'user_id'      => $user_id,
        'display_name' => $user->display_name,
    );

    $integration->send( $saved_records, $posted_data );
}
add_action( 'wpumgp_after_member_leave', 'adfoin_wpusermanager_handle_user_leaves_group', 10, 2 );

/**
 * Handle User Rejected.
 *
 * Fired before a user is rejected.
 *
 * @param int $user_id The user ID.
 */
function adfoin_wpusermanager_handle_user_rejected( $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpusermanager', 'userRejected' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $posted_data = array(
        'user_id'      => $user_id,
        'display_name' => $user->display_name,
        'user_email'   => $user->user_email,
    );

    $integration->send( $saved_records, $posted_data );
}
add_action( 'wpumuv_before_user_rejection', 'adfoin_wpusermanager_handle_user_rejected', 10, 1 );