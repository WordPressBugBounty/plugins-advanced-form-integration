<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve WPZOOM Forms list.
 *
 * @param string $form_provider Provider key.
 * @return array<int|string,string>|void
 */
function adfoin_wpzoomforms_get_forms( $form_provider ) {
	if ( $form_provider !== 'wpzoomforms' ) {
		return;
	}

	$forms = get_posts(
		array(
			'post_type'      => 'wpzf-form',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	if ( empty( $forms ) ) {
		return array();
	}

	$options = array();

	foreach ( $forms as $form_id ) {
		$options[ $form_id ] = get_the_title( $form_id );
	}

	return $options;
}

/**
 * Retrieve WPZOOM form fields.
 *
 * @param string $form_provider Provider key.
 * @param string|int $form_id   Form ID.
 * @return array<string,string>|void
 */
function adfoin_wpzoomforms_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'wpzoomforms' ) {
		return;
	}

	$form_id = absint( $form_id );
	if ( $form_id < 1 ) {
		return array();
	}

	$field_map = adfoin_wpzoomforms_parse_form_fields( $form_id );
	if ( empty( $field_map ) ) {
		return array();
	}

	$fields = array();

	foreach ( $field_map as $field_id => $meta ) {
		$key   = 'wpzf_' . $field_id;
		$type  = isset( $meta['type'] ) ? $meta['type'] : 'text';

		if ( adfoin_fs()->is_not_paying() ) {
			if ( ! in_array( $type, array( 'name', 'email' ), true ) ) {
				continue;
			}
		}

		$fields[ $key ] = $meta['label'];
	}

	$fields['form_id']         = __( 'Form ID', 'advanced-form-integration' );
	$fields['form_title']      = __( 'Form Title', 'advanced-form-integration' );
	$fields['form_url']        = __( 'Form URL', 'advanced-form-integration' );
	$fields['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );
	$fields['user_ip']         = __( 'User IP', 'advanced-form-integration' );

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $fields ) && is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

/**
 * Helper for UI: return form title.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return string|void
 */
function adfoin_wpzoomforms_get_form_name( $form_provider, $form_id ) {
	if ( $form_provider !== 'wpzoomforms' ) {
		return;
	}

	return get_the_title( absint( $form_id ) );
}

add_action( 'admin_post_wpzf_submit', 'adfoin_wpzoomforms_capture_submission', 5 );
add_action( 'admin_post_nopriv_wpzf_submit', 'adfoin_wpzoomforms_capture_submission', 5 );

/**
 * Capture WPZOOM form submissions before redirect.
 */
function adfoin_wpzoomforms_capture_submission() {
	static $handled = false;

	if ( $handled ) {
		return;
	}

	$handled = true;

	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'wpzf_submit' ) ) { // phpcs:ignore
		return;
	}

	if ( ! class_exists( 'WPZOOM_Forms_Settings' ) ) {
		return;
	}

	$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
	if ( $form_id < 1 ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'wpzoomforms', (string) $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	if ( ! adfoin_wpzoomforms_captcha_passed() ) {
		return;
	}

	$field_map = adfoin_wpzoomforms_parse_form_fields( $form_id );
	if ( empty( $field_map ) ) {
		return;
	}

	$payload = adfoin_wpzoomforms_build_payload( $form_id, $field_map );
	if ( empty( $payload ) || empty( $payload['data'] ) ) {
		return;
	}

	if ( ! adfoin_wpzoomforms_not_spam_check( $payload['akismet'] ) ) {
		return;
	}

	$posted_data = $payload['data'];

	$posted_data['form_id']         = $form_id;
	$posted_data['form_title']      = get_the_title( $form_id );
	$posted_data['form_url']        = isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) ) : wp_get_referer();
	$posted_data['submission_date'] = current_time( 'mysql' );
	$posted_data['user_ip']         = adfoin_get_user_ip();

	global $post;
	if ( ! is_object( $post ) ) {
		$referer = wp_get_referer();
		if ( $referer ) {
			$post_id = url_to_postid( $referer );
			if ( $post_id ) {
				$post = get_post( $post_id ); // phpcs:ignore
			}
		}
	}

	$special_tag_values = adfoin_get_special_tags_values( $post );
	if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
		$posted_data = $posted_data + $special_tag_values;
	}

	$integration->send( $saved_records, $posted_data );
}

/**
 * Build submission payload.
 *
 * @param int   $form_id    Form ID.
 * @param array $field_map  Parsed field metadata.
 * @return array<string,array<string,mixed>>
 */
function adfoin_wpzoomforms_build_payload( $form_id, $field_map ) {
	$posted_data = array();
	$field_data  = array(
		'_wpzf_form_id' => $form_id,
		'_wpzf_fields'  => array(),
	);

	$replyto_field = isset( $_POST['wpzf_replyto'] ) ? sanitize_text_field( wp_unslash( $_POST['wpzf_replyto'] ) ) : '';

	foreach ( $_POST as $key => $value ) { // phpcs:ignore
		if ( strpos( $key, 'wpzf_' ) !== 0 ) {
			continue;
		}

		if ( in_array( $key, array( 'wpzf_replyto', 'wpzf_subject' ), true ) ) {
			continue;
		}

		$field_id = substr( $key, 5 );
		$label    = isset( $field_map[ $field_id ]['label'] ) ? $field_map[ $field_id ]['label'] : sprintf( __( 'Field %s', 'advanced-form-integration' ), $field_id );
		$type     = isset( $field_map[ $field_id ]['type'] ) ? $field_map[ $field_id ]['type'] : 'text';

		$clean_value = adfoin_wpzoomforms_normalize_value( $value );
		$field_key   = 'wpzf_' . $field_id;

		if ( adfoin_fs()->is_not_paying() && ! in_array( $type, array( 'name', 'email' ), true ) ) {
			continue;
		}

		$posted_data[ $field_key ]           = $clean_value;
		$field_data['_wpzf_fields'][ $label ] = $clean_value;

		if ( 'name' === $type && empty( $field_data['name'] ) ) {
			$field_data['name'] = $clean_value;
		}

		if ( 'website' === $type && empty( $field_data['url'] ) ) {
			$field_data['url'] = $clean_value;
		}
	}

	$from_email = '';
	if ( $replyto_field && isset( $_POST[ $replyto_field ] ) ) {
		$from_email = sanitize_email( wp_unslash( $_POST[ $replyto_field ] ) );
	}

	$field_data['from']    = $from_email;
	$field_data['message'] = $field_data['_wpzf_fields'];

	return array(
		'data'    => $posted_data,
		'akismet' => $field_data,
	);
}

/**
 * Validate CAPTCHA result using plugin settings.
 *
 * @return bool
 */
function adfoin_wpzoomforms_captcha_passed() {
	if ( ! class_exists( 'WPZOOM_Forms_Settings' ) ) {
		return true;
	}

	$service = WPZOOM_Forms_Settings::get( 'wpzf_global_captcha_service' );

	if ( 'recaptcha' === $service ) {
		$type    = WPZOOM_Forms_Settings::get( 'wpzf_global_captcha_type' );
		$token   = '';
		$secret  = '';

		if ( isset( $_POST['g-recaptcha-response'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) );
		}

		if ( 'v3' === $type && isset( $_POST['recaptcha_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_POST['recaptcha_token'] ) );
		}

		if ( empty( $token ) ) {
			return false;
		}

		if ( 'v3' === $type ) {
			$secret = trim( (string) WPZOOM_Forms_Settings::get( 'wpzf_global_captcha_secret_key_v3' ) );
		} else {
			$secret = trim( (string) WPZOOM_Forms_Settings::get( 'wpzf_global_captcha_secret_key' ) );
		}

		if ( empty( $secret ) ) {
			return false;
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => adfoin_get_user_ip(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 'v3' === $type ) {
			return ! empty( $body['success'] ) && isset( $body['score'] ) && floatval( $body['score'] ) >= 0.5;
		}

		return ! empty( $body['success'] );
	}

	if ( 'turnstile' === $service ) {
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		$secret = trim( (string) WPZOOM_Forms_Settings::get( 'wpzf_global_turnstile_secret_key' ) );

		if ( empty( $token ) || empty( $secret ) ) {
			return false;
		}

		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => adfoin_get_user_ip(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] );
	}

	return true;
}

/**
 * Parse Gutenberg blocks to extract field info.
 *
 * @param int $form_id Form ID.
 * @return array<string,array<string,string>>
 */
function adfoin_wpzoomforms_parse_form_fields( $form_id ) {
	$content = get_post_field( 'post_content', $form_id, 'raw' );
	if ( empty( $content ) ) {
		return array();
	}

	$blocks = parse_blocks( $content );
	if ( empty( $blocks ) ) {
		return array();
	}

	return adfoin_wpzoomforms_collect_fields_from_blocks( $blocks );
}

/**
 * Recursively collect form fields.
 *
 * @param array<int,mixed> $blocks Block array.
 * @return array<string,array<string,string>>
 */
function adfoin_wpzoomforms_collect_fields_from_blocks( $blocks ) {
	$fields = array();

	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}

		$block_name = $block['blockName'];

		if ( strpos( $block_name, 'wpzoom-forms/' ) === 0 && ! preg_match( '/(label|submit)-field$/', $block_name ) ) {
			$attrs    = isset( $block['attrs'] ) ? $block['attrs'] : array();
			$field_id = isset( $attrs['id'] ) ? (string) $attrs['id'] : '';
			$label    = isset( $attrs['name'] ) && $attrs['name'] ? $attrs['name'] : '';

			if ( $field_id ) {
				$fields[ $field_id ] = array(
					'label' => $label ? $label : sprintf( __( 'Field %s', 'advanced-form-integration' ), $field_id ),
					'type'  => adfoin_wpzoomforms_detect_field_type( $block_name ),
				);
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$fields = $fields + adfoin_wpzoomforms_collect_fields_from_blocks( $block['innerBlocks'] );
		}
	}

	return $fields;
}

/**
 * Detect simplified field type based on block name.
 *
 * @param string $block_name Block identifier.
 * @return string
 */
function adfoin_wpzoomforms_detect_field_type( $block_name ) {
	$map = array(
		'name-field'         => 'name',
		'text-email-field'   => 'email',
		'phone-field'        => 'phone',
		'website-field'      => 'website',
		'textarea-field'     => 'textarea',
		'multi-checkbox'     => 'checkbox',
		'checkbox-field'     => 'checkbox',
		'date-field'         => 'date',
	);

	foreach ( $map as $needle => $type ) {
		if ( false !== strpos( $block_name, $needle ) ) {
			return $type;
		}
	}

	return 'text';
}

/**
 * Normalize posted values.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adfoin_wpzoomforms_normalize_value( $value ) {
	if ( is_array( $value ) ) {
		$value = array_map(
			static function( $item ) {
				return sanitize_textarea_field( wp_unslash( $item ) );
			},
			$value
		);

		$value = implode( ', ', array_filter( $value, 'strlen' ) );

		return $value;
	}

	return sanitize_textarea_field( wp_unslash( $value ) );
}

/**
 * Run Akismet spam check via WPZOOM helper if available.
 *
 * @param array $details Submission summary.
 * @return bool
 */
function adfoin_wpzoomforms_not_spam_check( $details ) {
	global $wpzoom_forms;

	if ( is_object( $wpzoom_forms ) && method_exists( $wpzoom_forms, 'not_spam' ) ) {
		return (bool) $wpzoom_forms->not_spam( $details );
	}

	return true;
}
