<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provider slug constant.
 */
define( 'ADFOIN_SBCF_PROVIDER', 'simplebasiccontactform' );

/**
 * Return the available Simple Basic Contact Form fields based on license tier.
 *
 * @return array<string,string>
 */
function adfoin_simplebasiccontactform_field_config() {
	$fields = array(
		'contact_name'  => __( 'Name', 'advanced-form-integration' ),
		'contact_email' => __( 'Email', 'advanced-form-integration' ),
	);

	if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
		$fields['contact_confirm_email'] = __( 'Confirm Email', 'advanced-form-integration' );
		$fields['contact_subject']       = __( 'Subject', 'advanced-form-integration' );
		$fields['contact_message']       = __( 'Message', 'advanced-form-integration' );
		$fields['contact_response']      = __( 'Captcha/Response', 'advanced-form-integration' );
		$fields['contact_recipient']     = __( 'Recipient Email', 'advanced-form-integration' );
		$fields['contact_headers']       = __( 'Email Headers', 'advanced-form-integration' );
		$fields['contact_from_email']    = __( 'From Email', 'advanced-form-integration' );
		$fields['form_url']              = __( 'Form Page URL', 'advanced-form-integration' );
		$fields['user_agent']            = __( 'User Agent', 'advanced-form-integration' );
	}

	return $fields;
}

/**
 * Return list of SBCF forms.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_simplebasiccontactform_get_forms( $form_provider ) {
	if ( ADFOIN_SBCF_PROVIDER !== $form_provider || ! function_exists( 'scf_shortcode' ) ) {
		return;
	}

	return array(
		'contactForm' => __( 'Simple Basic Contact Form', 'advanced-form-integration' ),
	);
}

/**
 * Return SBCF field map.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form identifier.
 * @return array<string,string>|void
 */
function adfoin_simplebasiccontactform_get_form_fields( $form_provider, $form_id ) {
	if ( ADFOIN_SBCF_PROVIDER !== $form_provider || 'contactForm' !== $form_id ) {
		return;
	}

	$fields = adfoin_simplebasiccontactform_field_config();

	$fields['form_id']         = __( 'Form ID', 'advanced-form-integration' );
	$fields['form_title']      = __( 'Form Title', 'advanced-form-integration' );
	$fields['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );
	$fields['user_ip']         = __( 'User IP', 'advanced-form-integration' );

	$special = adfoin_get_special_tags();
	if ( is_array( $special ) ) {
		$fields = $fields + $special;
	}

	return $fields;
}

/**
 * Helper for integration UI.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form identifier.
 * @return string|void
 */
function adfoin_simplebasiccontactform_get_form_name( $form_provider, $form_id ) {
	if ( ADFOIN_SBCF_PROVIDER !== $form_provider ) {
		return;
	}

	return __( 'Simple Basic Contact Form', 'advanced-form-integration' );
}

add_action( 'plugins_loaded', 'adfoin_simplebasiccontactform_bootstrap', 20 );

/**
 * Register SBCF hook listeners.
 */
function adfoin_simplebasiccontactform_bootstrap() {
	if ( ! function_exists( 'scf_shortcode' ) ) {
		return;
	}

	add_action( 'scf_send_email', 'adfoin_simplebasiccontactform_handle_submission', 99, 5 );
}

/**
 * Handle SBCF submission.
 *
 * @param string $recipient Recipient email.
 * @param string $topic     Email subject.
 * @param string $message   Sent message.
 * @param string $headers   Headers.
 * @param string $from      From email.
 */
function adfoin_simplebasiccontactform_handle_submission( $recipient, $topic, $message, $headers, $from ) {
	static $handled = false;

	if ( $handled || empty( $_POST ) || ! class_exists( 'Advanced_Form_Integration_Integration' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( ADFOIN_SBCF_PROVIDER, 'contactForm' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_simplebasiccontactform_prepare_payload( $recipient, $topic, $headers, $from );

	if ( empty( $payload ) ) {
		return;
	}

	$handled = true;
	$integration->send( $records, $payload );
}

/**
 * Build payload for SBCF submission.
 *
 * @param string $recipient Recipient email.
 * @param string $subject   Email subject.
 * @param string $headers   Headers.
 * @param string $from      From email.
 * @return array<string,mixed>
 */
function adfoin_simplebasiccontactform_prepare_payload( $recipient, $subject, $headers, $from ) {
	$field_config = adfoin_simplebasiccontactform_field_config();

	$data = array(
		'contact_name'          => adfoin_simplebasiccontactform_get_post_value( 'scf_name' ),
		'contact_email'         => adfoin_simplebasiccontactform_get_post_value( 'scf_email', 'email' ),
		'contact_confirm_email' => adfoin_simplebasiccontactform_get_post_value( 'scf_confirm_email', 'email' ),
		'contact_subject'       => sanitize_text_field( $subject ),
		'contact_message'       => adfoin_simplebasiccontactform_get_post_value( 'scf_message', 'textarea' ),
		'contact_response'      => adfoin_simplebasiccontactform_get_post_value( 'scf_response' ),
		'contact_recipient'     => sanitize_email( $recipient ),
		'contact_headers'       => sanitize_textarea_field( $headers ),
		'contact_from_email'    => sanitize_email( $from ),
		'form_url'              => adfoin_simplebasiccontactform_get_server_value( 'HTTP_REFERER', 'url' ),
		'user_agent'            => adfoin_simplebasiccontactform_get_server_value( 'HTTP_USER_AGENT' ),
	);

	$allowed_fields = array_keys( $field_config );
	$payload        = array();

	foreach ( $data as $key => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}

		if ( in_array( $key, $allowed_fields, true ) ) {
			$payload[ $key ] = $value;
		}
	}

	if ( empty( $payload ) ) {
		return array();
	}

	$payload['form_id']         = 'contactForm';
	$payload['form_title']      = __( 'Simple Basic Contact Form', 'advanced-form-integration' );
	$payload['submission_date'] = current_time( 'mysql' );
	$payload['user_ip']         = function_exists( 'scf_get_ip_address' ) ? sanitize_text_field( scf_get_ip_address() ) : adfoin_get_user_ip();

	global $post;
	$special = adfoin_get_special_tags_values( $post );
	if ( is_array( $special ) ) {
		$payload = $payload + $special;
	}

	return $payload;
}

/**
 * Sanitize POST values.
 *
 * @param string $key  Field key.
 * @param string $type Field type.
 * @return string
 */
function adfoin_simplebasiccontactform_get_post_value( $key, $type = 'text' ) {
	if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return '';
	}

	$value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	switch ( $type ) {
		case 'email':
			return sanitize_email( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Retrieve sanitized server variables.
 *
 * @param string $key  Server key.
 * @param string $type Sanitize type.
 * @return string
 */
function adfoin_simplebasiccontactform_get_server_value( $key, $type = 'text' ) {
	if ( empty( $_SERVER[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return '';
	}

	$value = wp_unslash( $_SERVER[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( 'url' === $type ) {
		return esc_url_raw( $value );
	}

	return sanitize_text_field( $value );
}
