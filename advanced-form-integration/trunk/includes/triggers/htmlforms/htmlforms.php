<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get HTML Forms list.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_htmlforms_get_forms( $form_provider ) {
	if ( 'htmlforms' !== $form_provider ) {
		return;
	}

	if ( ! function_exists( 'hf_get_forms' ) ) {
		return array();
	}

	$forms  = hf_get_forms();
	$choices = array();

	if ( ! empty( $forms ) && is_array( $forms ) ) {
		foreach ( $forms as $form ) {
			if ( empty( $form ) || empty( $form->ID ) ) {
				continue;
			}

			$title = ! empty( $form->title ) ? $form->title : sprintf( __( 'Form #%d', 'advanced-form-integration' ), $form->ID );
			$choices[ (string) $form->ID ] = $title;
		}
	}

	return $choices;
}

/**
 * Get HTML Forms fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_htmlforms_get_form_fields( $form_provider, $form_id ) {
	if ( 'htmlforms' !== $form_provider ) {
		return;
	}

	if ( ! function_exists( 'hf_get_form' ) ) {
		return array();
	}

	try {
		$form = hf_get_form( $form_id );
	} catch ( \Exception $e ) {
		return array();
	}

	if ( empty( $form ) ) {
		return array();
	}

	$fields = array(
		'form_id'              => __( 'Form ID', 'advanced-form-integration' ),
		'form_title'           => __( 'Form Title', 'advanced-form-integration' ),
		'form_slug'            => __( 'Form Slug', 'advanced-form-integration' ),
		'submission_id'        => __( 'Submission ID', 'advanced-form-integration' ),
		'submission_timestamp' => __( 'Submission Timestamp', 'advanced-form-integration' ),
		'ip_address'           => __( 'IP Address', 'advanced-form-integration' ),
		'user_agent'           => __( 'User Agent', 'advanced-form-integration' ),
		'referer_url'          => __( 'Referrer URL', 'advanced-form-integration' ),
	);

	$field_names = adfoin_htmlforms_extract_field_names( $form->markup );

	foreach ( $field_names as $original_name ) {
		$key   = adfoin_htmlforms_normalize_field_key( $original_name );
		$label = sprintf( __( 'Field: %s', 'advanced-form-integration' ), $original_name );

		if ( isset( $fields[ $key ] ) ) {
			continue;
		}

		$fields[ $key ] = $label;
	}

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

add_action( 'hf_form_success', 'adfoin_htmlforms_handle_submission', 20, 2 );

/**
 * Handle HTML Forms submission.
 *
 * @param \HTML_Forms\Submission $submission Submission object.
 * @param \HTML_Forms\Form       $form       Form object.
 * @return void
 */
function adfoin_htmlforms_handle_submission( $submission, $form ) {
	if ( ! $submission instanceof \HTML_Forms\Submission || ! $form instanceof \HTML_Forms\Form ) {
		return;
	}

	$form_id = (string) $form->ID;

	if ( '' === $form_id ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'htmlforms', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_htmlforms_prepare_payload( $submission, $form );

	if ( empty( $payload ) ) {
		return;
	}

	$post               = adfoin_get_post_object();
	$special_tag_values = adfoin_get_special_tags_values( $post );

	if ( is_array( $special_tag_values ) ) {
		$payload = array_merge( $payload, $special_tag_values );
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Prepare payload array.
 *
 * @param \HTML_Forms\Submission $submission Submission.
 * @param \HTML_Forms\Form       $form       Form.
 * @return array<string,mixed>
 */
function adfoin_htmlforms_prepare_payload( $submission, $form ) {
	$payload = array(
		'form_id'              => (string) $form->ID,
		'form_title'           => $form->title,
		'form_slug'            => $form->slug,
		'submission_id'        => isset( $submission->id ) ? (int) $submission->id : 0,
		'submission_timestamp' => isset( $submission->submitted_at ) ? $submission->submitted_at : '',
		'ip_address'           => isset( $submission->ip_address ) ? $submission->ip_address : '',
		'user_agent'           => isset( $submission->user_agent ) ? $submission->user_agent : '',
		'referer_url'          => isset( $submission->referer_url ) ? $submission->referer_url : '',
	);

	if ( ! empty( $submission->data ) && is_array( $submission->data ) ) {
		foreach ( $submission->data as $name => $value ) {
			if ( '' === $name || $name[0] === '_' ) {
				continue;
			}

			$key = adfoin_htmlforms_normalize_field_key( $name );

			if ( '' === $key ) {
				continue;
			}

			$payload[ $key ] = adfoin_htmlforms_format_value( $value );
		}
	}

	return $payload;
}

/**
 * Normalize field name into mapping key.
 *
 * @param string $name Original field name.
 * @return string
 */
function adfoin_htmlforms_normalize_field_key( $name ) {
	$original = (string) $name;
	$name     = trim( (string) $name );

	if ( '' === $name ) {
		return '';
	}

	$name = preg_replace( '/\[[^\]]*\]/', '_', $name );
	$name = preg_replace( '/[^a-zA-Z0-9_]+/', '_', $name );
	$name = trim( $name, '_' );

	if ( '' === $name ) {
		$name = sanitize_key( $original );
	}

	if ( '' === $name ) {
		$name = 'field_' . substr( md5( $original ), 0, 8 );
	}

	return $name;
}

/**
 * Extract fields from markup.
 *
 * @param string $markup Form markup.
 * @return array<int,string>
 */
function adfoin_htmlforms_extract_field_names( $markup ) {
	if ( empty( $markup ) ) {
		return array();
	}

	$names = array();

	if ( preg_match_all( '/\bname\s*=\s*(["\'])(.*?)\1/i', $markup, $matches ) ) {
		foreach ( $matches[2] as $name ) {
			$name = trim( $name );

			if ( '' === $name ) {
				continue;
			}

			if ( '_' === $name[0] ) {
				continue;
			}

			$names[] = $name;
		}
	}

	$names = array_values( array_unique( $names ) );

	return $names;
}

/**
 * Format submission value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adfoin_htmlforms_format_value( $value ) {
	if ( is_array( $value ) ) {
		$value = wp_json_encode( $value );
	}

	return (string) $value;
}
