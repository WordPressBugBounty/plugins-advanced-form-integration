<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'adfoin_updraftplus_register_hooks', 20 );

/**
 * Register hooks if UpdraftPlus is active.
 */
function adfoin_updraftplus_register_hooks() {
	if ( ! class_exists( 'UpdraftPlus' ) ) {
		return;
	}

	add_action( 'updraftplus_backup_success', 'adfoin_updraftplus_handle_backup_success', 10, 1 );
	add_action( 'updraftplus_backup_failed', 'adfoin_updraftplus_handle_backup_failed', 10, 1 );
	add_action( 'updraftplus_backup_has_errors', 'adfoin_updraftplus_handle_backup_errors', 10, 1 );
}

/**
 * Get forms (triggers).
 *
 * @param string $form_provider Provider name.
 *
 * @return array|void
 */
function adfoin_updraftplus_get_forms( $form_provider ) {
	if ( 'updraftplus' !== $form_provider ) {
		return;
	}

	return array(
		'backup_success'     => __( 'Backup Completed Successfully', 'advanced-form-integration' ),
		'backup_has_errors'  => __( 'Backup Completed With Errors', 'advanced-form-integration' ),
		'backup_failed'      => __( 'Backup Failed', 'advanced-form-integration' ),
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
function adfoin_updraftplus_get_form_fields( $form_provider, $form_id ) {
	if ( 'updraftplus' !== $form_provider ) {
		return;
	}

	switch ( $form_id ) {
		case 'backup_success':
			return adfoin_updraftplus_get_success_fields();
		case 'backup_failed':
		case 'backup_has_errors':
			return adfoin_updraftplus_get_failure_fields();
	}
}

/**
 * Get fields for success trigger.
 *
 * @return array
 */
function adfoin_updraftplus_get_success_fields() {
	return array(
		'status'              => __( 'Job Status', 'advanced-form-integration' ),
		'backup_timestamp'    => __( 'Backup Timestamp', 'advanced-form-integration' ),
		'backup_nonce'        => __( 'Backup Job ID', 'advanced-form-integration' ),
		'backup_files'        => __( 'Backup Files (JSON)', 'advanced-form-integration' ),
		'backup_entities'     => __( 'Backed-up Items (JSON)', 'advanced-form-integration' ),
		'backup_storage'      => __( 'Backup Storage Location(s)', 'advanced-form-integration' ),
		'is_multisite'        => __( 'Is Multisite', 'advanced-form-integration' ),
		'site_url'            => __( 'Site URL', 'advanced-form-integration' ),
		'wordpress_version'   => __( 'WordPress Version', 'advanced-form-integration' ),
		'updraftplus_version' => __( 'UpdraftPlus Version', 'advanced-form-integration' ),
	);
}

/**
 * Get fields for failure/error triggers.
 *
 * @return array
 */
function adfoin_updraftplus_get_failure_fields() {
	return array(
		'status'              => __( 'Job Status', 'advanced-form-integration' ),
		'error_message'       => __( 'Error Message', 'advanced-form-integration' ),
		'error_code'          => __( 'Error Code', 'advanced-form-integration' ),
		'error_data'          => __( 'Error Data (JSON)', 'advanced-form-integration' ),
		'site_url'            => __( 'Site URL', 'advanced-form-integration' ),
		'wordpress_version'   => __( 'WordPress Version', 'advanced-form-integration' ),
		'updraftplus_version' => __( 'UpdraftPlus Version', 'advanced-form-integration' ),
	);
}

/**
 * Handle UpdraftPlus backup success.
 *
 * @param array $backup_job_data Data about the completed job.
 */
function adfoin_updraftplus_handle_backup_success( $backup_job_data ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'updraftplus', 'backup_success' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_updraftplus_prepare_success_payload( $backup_job_data );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

/**
 * Handle UpdraftPlus backup errors.
 *
 * @param array $data Error data.
 */
function adfoin_updraftplus_handle_backup_errors( $data ) {
	adfoin_updraftplus_dispatch_failure( 'backup_has_errors', $data );
}

/**
 * Handle UpdraftPlus backup failure.
 *
 * @param array $data Error data.
 */
function adfoin_updraftplus_handle_backup_failed( $data ) {
	adfoin_updraftplus_dispatch_failure( 'backup_failed', $data );
}

/**
 * Dispatch failure triggers.
 *
 * @param string $trigger The trigger ID.
 * @param mixed  $data    The data from the hook.
 */
function adfoin_updraftplus_dispatch_failure( $trigger, $data ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'updraftplus', $trigger );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_updraftplus_prepare_failure_payload( $data, $trigger );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

/**
 * Prepare payload for the success trigger.
 *
 * @param array $job_data Data from the hook.
 *
 * @return array
 */
function adfoin_updraftplus_prepare_success_payload( $job_data ) {
	$updraftplus = UpdraftPlus::instance();

	$payload = array(
		'status'              => 'success',
		'backup_timestamp'    => isset( $job_data['timestamp'] ) ? $job_data['timestamp'] : '',
		'backup_nonce'        => isset( $job_data['nonce'] ) ? $job_data['nonce'] : '',
		'backup_files'        => isset( $job_data['backup_files'] ) ? wp_json_encode( $job_data['backup_files'] ) : '[]',
		'backup_entities'     => isset( $job_data['backupable_entities'] ) ? wp_json_encode( $job_data['backupable_entities'] ) : '[]',
		'backup_storage'      => implode( ', ', $updraftplus->get_storage_descriptions() ),
		'is_multisite'        => is_multisite() ? 'true' : 'false',
		'site_url'            => site_url(),
		'wordpress_version'   => get_bloginfo( 'version' ),
		'updraftplus_version' => $updraftplus->version,
	);

	return $payload;
}

/**
 * Prepare payload for failure/error triggers.
 *
 * @param mixed  $data    Data from the hook.
 * @param string $status  The status to set (failed or has_errors).
 *
 * @return array
 */
function adfoin_updraftplus_prepare_failure_payload( $data, $status ) {
	$updraftplus = UpdraftPlus::instance();

	$payload = array(
		'status'              => $status,
		'error_message'       => '',
		'error_code'          => '',
		'error_data'          => '[]',
		'site_url'            => site_url(),
		'wordpress_version'   => get_bloginfo( 'version' ),
		'updraftplus_version' => $updraftplus->version,
	);

	if ( is_wp_error( $data ) ) {
		$payload['error_message'] = $data->get_error_message();
		$payload['error_code']    = $data->get_error_code();
		$payload['error_data']    = wp_json_encode( $data->get_error_data() );
	} elseif ( is_string( $data ) ) {
		$payload['error_message'] = $data;
	} elseif ( is_array( $data ) ) {
		$payload['error_message'] = isset( $data['message'] ) ? $data['message'] : 'See error data for details.';
		$payload['error_code']    = isset( $data['code'] ) ? $data['code'] : '';
		$payload['error_data']    = wp_json_encode( $data );
	}

	return $payload;
}
