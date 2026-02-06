<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get ProfilePress Triggers.
 *
 * @param string $form_provider The integration provider.
 * @return array|void
 */
function adfoin_profilepress_get_forms( $form_provider ) {
	if ( $form_provider !== 'profilepress' ) {
		return;
	}
	$triggers = array(
		'userRegistered' => __( 'New User Registered', 'advanced-form-integration' ),
		'profileUpdated' => __( 'User Profile Updated', 'advanced-form-integration' ),
	);
	return $triggers;
}

/**
 * Get ProfilePress Form Fields.
 *
 * @param string $form_provider The integration provider.
 * @param string $form_id       The trigger ID.
 * @return array|void
 */
function adfoin_profilepress_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'profilepress' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'userRegistered' ) {
		$fields = array(
			'user_id'    => __( 'User ID', 'advanced-form-integration' ),
			'user_login' => __( 'User Login', 'advanced-form-integration' ),
			'user_email' => __( 'Email', 'advanced-form-integration' ),
			'first_name' => __( 'First Name', 'advanced-form-integration' ),
			'last_name'  => __( 'Last Name', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'profileUpdated' ) {
		$fields = array(
			'user_id'    => __( 'User ID', 'advanced-form-integration' ),
			'first_name' => __( 'First Name', 'advanced-form-integration' ),
			'last_name'  => __( 'Last Name', 'advanced-form-integration' ),
			'user_email' => __( 'Email', 'advanced-form-integration' ),
		);
	}
	return $fields;
}

/**
 * Get User Data.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_profilepress_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );
	if ( $user ) {
		$user_data['user_id']    = $user_id;
		$user_data['user_login'] = $user->user_login;
		$user_data['user_email'] = $user->user_email;
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
	}
	return $user_data;
}

/**
 * Handle ProfilePress User Registration.
 *
 * Fires when a user registers via ProfilePress.
 *
 * @param int   $form_id   The registration form ID.
 * @param array $user_data The submitted user data.
 * @param int   $user_id   The new user ID.
 */
function adfoin_profilepress_handle_user_registration( $form_id, $user_data, $user_id ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'profilepress', 'userRegistered' );
	if ( empty( $saved_records ) ) {
		return;
	}
	$posted_data = adfoin_profilepress_get_userdata( $user_id );
	$integration->send( $saved_records, $posted_data );
}

/**
 * Handle ProfilePress Profile Update.
 *
 * Fires when a user updates their profile.
 *
 * @param array $user_data The updated user data.
 * @param int   $form_id   The profile update form ID.
 */
function adfoin_profilepress_handle_user_update( $user_data, $form_id ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'profilepress', 'profileUpdated' );
	if ( empty( $saved_records ) ) {
		return;
	}
	$integration->send( $saved_records, $user_data );
}

// Hook into ProfilePress registration and profile update events.
// For the full version use the "pp_after_*" hooks; for the free version use "ppress_after_*" hooks.
if ( class_exists( 'ProfilePress_Dir' ) ) {
	add_action( 'pp_after_registration', 'adfoin_profilepress_handle_user_registration', 10, 3 );
	add_action( 'pp_after_profile_update', 'adfoin_profilepress_handle_user_update', 10, 2 );
} else {
	add_action( 'ppress_after_registration', 'adfoin_profilepress_handle_user_registration', 10, 3 );
	add_action( 'ppress_after_profile_update', 'adfoin_profilepress_handle_user_update', 10, 2 );
}