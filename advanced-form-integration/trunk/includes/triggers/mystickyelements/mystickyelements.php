<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY', 'contactForm' );

/**
 * Check if My Sticky Elements plugin is active.
 *
 * @return bool
 */
function adfoin_mystickyelements_is_active() {
	return defined( 'MY_STICKY_ELEMENT_VERSION' ) || class_exists( 'MyStickyElementsFrontPage_pro' );
}

/**
 * Return available My Sticky Elements forms.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_mystickyelements_get_forms( $form_provider ) {
	if ( 'mystickyelements' !== $form_provider ) {
		return;
	}

	if ( ! adfoin_mystickyelements_is_active() ) {
		return array();
	}

	return array(
		ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY => __( 'Sticky Contact Form', 'advanced-form-integration' ),
	);
}

/**
 * Return My Sticky Elements field map.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_mystickyelements_get_form_fields( $form_provider, $form_id ) {
	if ( 'mystickyelements' !== $form_provider || ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY !== $form_id ) {
		return;
	}

	$fields = array(
		'contact-form-name'     => __( 'Contact Name', 'advanced-form-integration' ),
		'contact-form-email'    => __( 'Contact Email', 'advanced-form-integration' ),
		'contact-form-phone'    => __( 'Contact Phone', 'advanced-form-integration' ),
		'contact_code'          => __( 'Phone Country Code', 'advanced-form-integration' ),
		'contact-form-message'  => __( 'Message', 'advanced-form-integration' ),
		'contact-form-dropdown' => __( 'Dropdown Selection', 'advanced-form-integration' ),
		'stickyelements-page-link' => __( 'Page URL', 'advanced-form-integration' ),
		'form_title'            => __( 'Form Title', 'advanced-form-integration' ),
		'form_id'               => __( 'Form ID', 'advanced-form-integration' ),
		'submission_date'       => __( 'Submission Date', 'advanced-form-integration' ),
		'user_ip'               => __( 'User IP', 'advanced-form-integration' ),
	);

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $fields ) && is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

/**
 * Helper to expose form title.
 *
 * @param string $form_provider Provider slug.
 * @param string $form_id       Form ID.
 * @return string|void
 */
function adfoin_mystickyelements_get_form_name( $form_provider, $form_id ) {
	if ( 'mystickyelements' !== $form_provider || ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY !== $form_id ) {
		return;
	}

	return __( 'Sticky Contact Form', 'advanced-form-integration' );
}

add_action( 'plugins_loaded', 'adfoin_mystickyelements_bootstrap', 20 );

/**
 * Initialize listeners.
 */
function adfoin_mystickyelements_bootstrap() {
	if ( ! adfoin_mystickyelements_is_active() ) {
		return;
	}

	add_action( 'wp_ajax_mystickyelements_contact_form', 'adfoin_mystickyelements_prepare_capture', 0 );
	add_action( 'wp_ajax_nopriv_mystickyelements_contact_form', 'adfoin_mystickyelements_prepare_capture', 0 );
}

/**
 * Determine whether we should listen for submissions.
 *
 * @return bool
 */
function adfoin_mystickyelements_should_listen() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		$cached = false;
		return $cached;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'mystickyelements', ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY );

	if ( empty( $records ) ) {
		$cached = false;
		return $cached;
	}

	$GLOBALS['adfoin_mystickyelements_records'] = $records;
	$cached = true;

	return $cached;
}

/**
 * Prepare output buffering to capture AJAX responses.
 */
function adfoin_mystickyelements_prepare_capture() {
	if ( empty( $_POST ) || ! adfoin_mystickyelements_should_listen() ) { // phpcs:ignore
		return;
	}

	if ( ! empty( $GLOBALS['adfoin_mystickyelements_buffer_active'] ) ) {
		return;
	}

	$GLOBALS['adfoin_mystickyelements_buffer_active'] = true;
	ob_start();
	add_action( 'shutdown', 'adfoin_mystickyelements_finalize_capture', 0 );
}

/**
 * Finalize buffering, mirror response, and dispatch integration.
 */
function adfoin_mystickyelements_finalize_capture() {
	if ( empty( $GLOBALS['adfoin_mystickyelements_buffer_active'] ) ) {
		return;
	}

	$GLOBALS['adfoin_mystickyelements_buffer_active'] = false;

	$buffer = ob_get_contents();

	if ( false === $buffer ) {
		$buffer = '';
	}

	if ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	$decoded = json_decode( $buffer, true );

	if ( is_array( $decoded ) && ! empty( $decoded['status'] ) && empty( $decoded['error'] ) ) {
		adfoin_mystickyelements_dispatch_submission();
	}

	echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Dispatch submission to AFI actions.
 */
function adfoin_mystickyelements_dispatch_submission() {
	$records = isset( $GLOBALS['adfoin_mystickyelements_records'] ) ? $GLOBALS['adfoin_mystickyelements_records'] : array();

	if ( empty( $records ) ) {
		$integration = new Advanced_Form_Integration_Integration();
		$records     = $integration->get_by_trigger( 'mystickyelements', ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY );
	}

	if ( empty( $records ) ) {
		return;
	}

	$posted_data = adfoin_mystickyelements_collect_posted_data();

	if ( empty( $posted_data ) ) {
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();
	$integration->send( $records, $posted_data );
}

/**
 * Gather submitted values.
 *
 * @return array<string,string>
 */
function adfoin_mystickyelements_collect_posted_data() {
	$data         = array();
	$contact_form = get_option( 'mystickyelements-contact-form', array() );

	$name    = adfoin_mystickyelements_get_post_value( 'contact-form-name' );
	$email   = adfoin_mystickyelements_get_post_value( 'contact-form-email', 'email' );
	$phone   = adfoin_mystickyelements_get_post_value( 'contact-form-phone' );
	$code    = adfoin_mystickyelements_get_post_value( 'contact_code' );
	$message = adfoin_mystickyelements_get_post_value( 'contact-form-message', 'textarea' );
	$dropdown = adfoin_mystickyelements_get_post_value( 'contact-form-dropdown' );
	$page_link = adfoin_mystickyelements_get_post_value( 'stickyelements-page-link', 'url' );

	if ( $phone && $code && false === strpos( $phone, $code ) ) {
		$phone = '+' . ltrim( $code, '+' ) . ' ' . $phone;
	}

	$data['contact-form-name']     = $name;
	$data['contact-form-email']    = $email;
	$data['contact-form-phone']    = $phone;
	$data['contact_code']          = $code;
	$data['contact-form-message']  = $message;
	$data['contact-form-dropdown'] = $dropdown;
	$data['stickyelements-page-link'] = $page_link;
	$data['form_id']               = ADFOIN_MYSTICKYELEMENTS_TRIGGER_KEY;
	$data['form_title']            = isset( $contact_form['contact_title_text'] ) && $contact_form['contact_title_text']
		? sanitize_text_field( $contact_form['contact_title_text'] )
		: __( 'Contact Form', 'advanced-form-integration' );
	$data['submission_date']       = current_time( 'mysql' );
	$data['user_ip']               = adfoin_get_user_ip();

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

	$special_tag_values = adfoin_get_special_tags_values( $post );

	if ( is_array( $special_tag_values ) ) {
		$data = $data + $special_tag_values;
	}

	$primary_values = array_filter(
		array(
			$data['contact-form-name'],
			$data['contact-form-email'],
			$data['contact-form-phone'],
			$data['contact-form-message'],
			$data['contact-form-dropdown'],
		),
		static function ( $value ) {
			return '' !== $value && null !== $value;
		}
	);

	if ( empty( $primary_values ) ) {
		return array();
	}

	return $data;
}

/**
 * Sanitize POST value helper.
 *
 * @param string $key  Field key.
 * @param string $type Type (text|email|textarea|url).
 * @return string
 */
function adfoin_mystickyelements_get_post_value( $key, $type = 'text' ) {
	if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return '';
	}

	$value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	switch ( $type ) {
		case 'email':
			return sanitize_email( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'url':
			return esc_url_raw( $value );
		default:
			return sanitize_text_field( $value );
	}
}
