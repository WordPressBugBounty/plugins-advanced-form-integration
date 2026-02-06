<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADFOIN_VSCF_FORM_ID', 'default' );

/**
 * Return field configuration with free/pro awareness.
 *
 * @return array<string,string>
 */
function adfoin_verysimplecontactform_fields_config() {
	$fields = array(
		'form_name'  => __( 'Name', 'advanced-form-integration' ),
		'form_email' => __( 'Email', 'advanced-form-integration' ),
	);

	if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
		$fields['form_subject'] = __( 'Subject', 'advanced-form-integration' );
		$fields['form_message'] = __( 'Message', 'advanced-form-integration' );
		$fields['form_privacy'] = __( 'Privacy Consent', 'advanced-form-integration' );
		$fields['form_sum']     = __( 'Captcha Sum', 'advanced-form-integration' );
		$fields['form_first_random']  = __( 'Honeypot First', 'advanced-form-integration' );
		$fields['form_second_random'] = __( 'Honeypot Second', 'advanced-form-integration' );
		$fields['form_page_url']      = __( 'Page URL', 'advanced-form-integration' );
	}

	return $fields;
}

/**
 * List VS Contact Form triggers.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_verysimplecontactform_get_forms( $form_provider ) {
	if ( 'verysimplecontactform' !== $form_provider || ! function_exists( 'vscf_shortcode' ) ) {
		return;
	}

	return array(
		ADFOIN_VSCF_FORM_ID => __( 'VS Contact Form', 'advanced-form-integration' ),
	);
}

/**
 * Provide available fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form identifier.
 * @return array<string,string>|void
 */
function adfoin_verysimplecontactform_get_form_fields( $form_provider, $form_id ) {
	if ( 'verysimplecontactform' !== $form_provider || ADFOIN_VSCF_FORM_ID !== $form_id ) {
		return;
	}

	$fields = adfoin_verysimplecontactform_fields_config();

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
 * UI helper.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 * @return string|void
 */
function adfoin_verysimplecontactform_get_form_name( $form_provider, $form_id ) {
	if ( 'verysimplecontactform' !== $form_provider ) {
		return;
	}

	return __( 'VS Contact Form', 'advanced-form-integration' );
}

add_action( 'plugins_loaded', 'adfoin_verysimplecontactform_setup', 20 );

/**
 * Initialize hook listeners.
 */
function adfoin_verysimplecontactform_setup() {
	if ( ! function_exists( 'vscf_shortcode' ) ) {
		return;
	}

	add_action( 'vscf_before_send_mail', 'adfoin_verysimplecontactform_capture', 10, 1 );
}

/**
 * Capture VS Contact Form submission data.
 *
 * @param array<string,string> $form_data Sanitized form data.
 */
function adfoin_verysimplecontactform_capture( $form_data ) {
	if ( empty( $form_data ) || ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'verysimplecontactform', ADFOIN_VSCF_FORM_ID );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_verysimplecontactform_build_payload( $form_data );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build payload to dispatch.
 *
 * @param array<string,string> $form_data Form submission data.
 * @return array<string,string>
 */
function adfoin_verysimplecontactform_build_payload( $form_data ) {
	$fields = adfoin_verysimplecontactform_fields_config();

	$payload = array();
	foreach ( $fields as $key => $label ) {
		if ( isset( $form_data[ $key ] ) ) {
			$payload[ $key ] = adfoin_verysimplecontactform_sanitize_value( $key, $form_data[ $key ] );
		} elseif ( 'form_page_url' === $key && function_exists( 'vscf_page_url' ) ) {
			$payload[ $key ] = esc_url_raw( vscf_page_url() );
		}
	}

	if ( empty( $payload ) ) {
		return array();
	}

	$payload['form_id']         = ADFOIN_VSCF_FORM_ID;
	$payload['form_title']      = __( 'VS Contact Form', 'advanced-form-integration' );
	$payload['submission_date'] = current_time( 'mysql' );
	$payload['user_ip']         = function_exists( 'vscf_ip_address' ) ? sanitize_text_field( vscf_ip_address() ) : adfoin_get_user_ip();

	global $post;
	if ( ! is_object( $post ) ) {
		$referer = wp_get_referer();
		if ( $referer ) {
			$post_id = url_to_postid( $referer );
			if ( $post_id ) {
				$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
	}

	$special = adfoin_get_special_tags_values( $post );
	if ( is_array( $special ) ) {
		$payload = $payload + $special;
	}

	return $payload;
}

/**
 * Sanitize individual field values.
 *
 * @param string $key   Field key.
 * @param mixed  $value Raw value.
 * @return string
 */
function adfoin_verysimplecontactform_sanitize_value( $key, $value ) {
	if ( 'form_message' === $key ) {
		return sanitize_textarea_field( $value );
	}

	if ( 'form_subject' === $key ) {
		return sanitize_text_field( $value );
	}

	return sanitize_text_field( $value );
}
