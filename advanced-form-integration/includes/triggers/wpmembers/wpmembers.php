<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get WP Members Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_wpmembers_get_forms( $form_provider ) {
	if ( $form_provider !== 'wp-members' ) {
		return;
	}

	$triggers = array(
		'userActivated' => __( 'User Activated', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get WP Members Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_wpmembers_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'wp-members' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'userActivated' ) {
		$fields = array(
			'user_id'           => __( 'User ID', 'advanced-form-integration' ),
			'first_name'        => __( 'First Name', 'advanced-form-integration' ),
			'last_name'         => __( 'Last Name', 'advanced-form-integration' ),
			'user_email'        => __( 'Email', 'advanced-form-integration' ),
			'registration_date' => __( 'Registration Date', 'advanced-form-integration' ),
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
function adfoin_wpmembers_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );

	if ( $user ) {
		$user_data['first_name']        = $user->first_name;
		$user_data['last_name']         = $user->last_name;
		$user_data['user_email']        = $user->user_email;
		$user_data['user_id']           = $user_id;
		$user_data['registration_date'] = isset( $user->user_registered ) ? $user->user_registered : '';
	}

	return $user_data;
}

/**
 * Handle WP Members User Activation.
 *
 * Fired when a user is activated (either via admin or via email confirmation).
 *
 * @param int $user_id The activated user’s ID.
 */
function adfoin_wpmembers_handle_user_activated( $user_id ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'wp-members', 'userActivated' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Gather user data.
	$posted_data = adfoin_wpmembers_get_userdata( $user_id );

	// Send data to the integration.
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpmem_user_activated', 'adfoin_wpmembers_handle_user_activated', 10, 1 );
add_action( 'wpmem_account_validation_success', 'adfoin_wpmembers_handle_user_activated', 10, 1 );