<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'adfoin_solidsecurity_register_hooks', 20 );

/**
 * Register hooks if Solid Security is active.
 */
function adfoin_solidsecurity_register_hooks() {
	if ( ! class_exists( 'ITSEC_Core' ) ) {
		return;
	}

	add_action( 'itsec_lockout_host', 'adfoin_solidsecurity_handle_host_lockout', 10, 1 );
	add_action( 'itsec_lockout_username', 'adfoin_solidsecurity_handle_user_lockout', 10, 1 );
}

/**
 * Get forms (triggers).
 *
 * @param string $form_provider Provider name.
 *
 * @return array|void
 */
function adfoin_solidsecurity_get_forms( $form_provider ) {
	if ( 'solidsecurity' !== $form_provider ) {
		return;
	}

	return array(
		'lockout' => __( 'Host or User Locked Out', 'advanced-form-integration' ),
	);
}

/**
 * Get form fields.
 *
 * @param string $form_provider Provider name.
 * @param string $form_id       Form ID.
 *
 * @return array|void
 */
function adfoin_solidsecurity_get_form_fields( $form_provider, $form_id ) {
	if ( 'solidsecurity' !== $form_provider ) {
		return;
	}

	if ( 'lockout' === $form_id ) {
		return array(
			'lockout_type'     => __( 'Lockout Type (host or user)', 'advanced-form-integration' ),
			'lockout_id'       => __( 'Lockout ID', 'advanced-form-integration' ),
			'lockout_host'     => __( 'Host IP Address', 'advanced-form-integration' ),
			'lockout_user'     => __( 'User ID', 'advanced-form-integration' ),
			'lockout_username' => __( 'Username', 'advanced-form-integration' ),
			'start_time'       => __( 'Lockout Start Time (UTC)', 'advanced-form-integration' ),
			'end_time'         => __( 'Lockout End Time (UTC)', 'advanced-form-integration' ),
			'reason'           => __( 'Lockout Reason', 'advanced-form-integration' ),
		);
	}
}

/**
 * Handle host lockouts.
 *
 * @param array $lockout_data Data about the lockout.
 */
function adfoin_solidsecurity_handle_host_lockout( $lockout_data ) {
	adfoin_solidsecurity_dispatch_lockout( $lockout_data, 'host' );
}

/**
 * Handle username lockouts.
 *
 * @param array $lockout_data Data about the lockout.
 */
function adfoin_solidsecurity_handle_user_lockout( $lockout_data ) {
	adfoin_solidsecurity_dispatch_lockout( $lockout_data, 'user' );
}

/**
 * Dispatch the lockout trigger.
 *
 * @param array  $lockout_data Data from the hook.
 * @param string $type         The type of lockout (host or user).
 */
function adfoin_solidsecurity_dispatch_lockout( $lockout_data, $type ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'solidsecurity', 'lockout' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_solidsecurity_prepare_lockout_payload( $lockout_data, $type );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

/**
 * Prepare payload for the lockout trigger.
 *
 * @param array  $data Data from the hook.
 * @param string $type The type of lockout.
 *
 * @return array
 */
function adfoin_solidsecurity_prepare_lockout_payload( $data, $type ) {
	return array(
		'lockout_type'     => $type,
		'lockout_id'       => isset( $data['lockout_id'] ) ? $data['lockout_id'] : '',
		'lockout_host'     => isset( $data['lockout_host'] ) ? $data['lockout_host'] : '',
		'lockout_user'     => isset( $data['lockout_user'] ) ? $data['lockout_user'] : '',
		'lockout_username' => isset( $data['lockout_username'] ) ? $data['lockout_username'] : '',
		'start_time'       => isset( $data['lockout_start_gmt'] ) ? $data['lockout_start_gmt'] : '',
		'end_time'         => isset( $data['lockout_end_gmt'] ) ? $data['lockout_end_gmt'] : '',
		'reason'           => isset( $data['lockout_reason'] ) ? $data['lockout_reason'] : '',
	);
}
