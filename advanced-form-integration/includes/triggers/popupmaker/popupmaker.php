<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Popup Maker Triggers.
 *
 * @param string $form_provider The integration provider.
 * @return array|void
 */
function adfoin_popupmaker_get_forms( $form_provider ) {
	if ( $form_provider != 'popup-maker' ) {
		return;
	}

	$triggers = array(
		'formSubmitted' => __( 'Popup Form Submitted', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get Popup Maker Form Fields.
 *
 * @param string $form_provider The integration provider.
 * @param string $form_id       The specific trigger ID.
 * @return array|void
 */
function adfoin_popupmaker_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider != 'popup-maker' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'formSubmitted' ) {
		$fields = array(
			'popup_id' => __( 'Popup ID', 'advanced-form-integration' ),
			'fname'    => __( 'First Name', 'advanced-form-integration' ),
			'lname'    => __( 'Last Name', 'advanced-form-integration' ),
			'email'    => __( 'Email', 'advanced-form-integration' ),
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
function adfoin_popupmaker_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );

	if ( $user ) {
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
		$user_data['user_email'] = $user->user_email;
		$user_data['user_id']    = $user_id;
	}

	return $user_data;
}

/**
 * Handle Popup Maker Form Submission.
 *
 * This function fires when a Popup Maker form is submitted.
 *
 * @param array $values   The submitted form values.
 * @param array $response The response data.
 * @param array $errors   Any errors encountered.
 */
function adfoin_popupmaker_handle_form_submission( $values, $response, $errors ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'popup-maker', 'formSubmitted' );

	if ( empty( $saved_records ) ) {
		return;
	}

	// Build the data array to send.
	$posted_data = array(
		'popup_id'   => isset( $values['popup_id'] ) ? $values['popup_id'] : '',
		'first_name' => isset( $values['fname'] ) ? $values['fname'] : '',
		'last_name'  => isset( $values['lname'] ) ? $values['lname'] : '',
		'user_email' => isset( $values['email'] ) ? $values['email'] : '',
	);

	$integration->send( $saved_records, $posted_data );
}
add_action( 'pum_sub_form_submission', 'adfoin_popupmaker_handle_form_submission', 10, 3 );