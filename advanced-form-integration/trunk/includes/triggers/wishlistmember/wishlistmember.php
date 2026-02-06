<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Wishlist Member Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_wlm_get_forms( $form_provider ) {
	if ( $form_provider !== 'wishlist-member' ) {
		return;
	}

	$triggers = array(
		'userRegistered' => __( 'User Registered', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get Wishlist Member Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_wlm_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'wishlist-member' ) {
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
	}

	return $fields;
}

/**
 * Get User Data.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_wlm_get_userdata( $user_id ) {
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
 * Handle Wishlist Member User Registration.
 *
 * Fired when a user is registered via Wishlist Member.
 *
 * @param int   $user_id The ID of the newly registered user.
 * @param array $data    Additional registration data.
 */
function adfoin_wlm_handle_user_registered( $user_id, $data ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'wishlist-member', 'userRegistered' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Gather the user data.
	$posted_data = adfoin_wlm_get_userdata( $user_id );

	// Send the data to the integration.
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wishlistmember_user_registered', 'adfoin_wlm_handle_user_registered', 10, 2 );