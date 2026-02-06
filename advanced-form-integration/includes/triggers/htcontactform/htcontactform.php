<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build selection of HT Contact Forms.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_htcontactform_get_forms( $form_provider ) {
	if ( 'htcontactform' !== $form_provider ) {
		return;
	}

	$forms = adfoin_htcontactform_fetch_forms();

	if ( empty( $forms ) ) {
		return array();
	}

	asort( $forms, SORT_NATURAL | SORT_FLAG_CASE );

	return $forms;
}

/**
 * Retrieve HT Contact Form fields for mapping.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_htcontactform_get_form_fields( $form_provider, $form_id ) {
	if ( 'htcontactform' !== $form_provider ) {
		return;
	}

	$form = adfoin_htcontactform_get_form( $form_id );

	if ( empty( $form ) ) {
		return array();
	}

	$fields = array(
		'form_id'        => __( 'Form ID', 'advanced-form-integration' ),
		'form_title'     => __( 'Form Title', 'advanced-form-integration' ),
		'form_shortcode' => __( 'Form Shortcode', 'advanced-form-integration' ),
		'entry_id'       => __( 'Entry ID', 'advanced-form-integration' ),
		'created_at'     => __( 'Created At', 'advanced-form-integration' ),
		'user_id'        => __( 'User ID', 'advanced-form-integration' ),
		'ip_address'     => __( 'IP Address', 'advanced-form-integration' ),
		'browser'        => __( 'Browser', 'advanced-form-integration' ),
		'device'         => __( 'Device', 'advanced-form-integration' ),
		'source_url'     => __( 'Source URL', 'advanced-form-integration' ),
	);

	$field_names = adfoin_htcontactform_extract_field_names( $form );

	foreach ( $field_names as $name ) {
		$key   = adfoin_htcontactform_normalize_field_key( $name );
		$label = sprintf( __( 'Field: %s', 'advanced-form-integration' ), $name );

		if ( ! isset( $fields[ $key ] ) ) {
			$fields[ $key ] = $label;
		}
	}

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

add_action( 'ht_form/after_submission', 'adfoin_htcontactform_handle_submission', 20, 3 );

/**
 * Handle HT Contact Form submissions.
 *
 * @param array $form      Form config.
 * @param array $form_data Submission data.
 * @param array $meta      Submission metadata.
 * @return void
 */
function adfoin_htcontactform_handle_submission( $form, $form_data, $meta ) {
	if ( empty( $form ) || empty( $form['id'] ) || ! is_array( $form_data ) ) {
		return;
	}

	$form_id = (string) $form['id'];

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'htcontactform', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_htcontactform_prepare_payload( $form, $form_data, $meta );

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
 * Prepare outgoing payload.
 *
 * @param array $form      Form config.
 * @param array $form_data Submitted data.
 * @param array $meta      Submission metadata.
 * @return array<string,mixed>
 */
function adfoin_htcontactform_prepare_payload( $form, $form_data, $meta ) {
	$payload = array(
		'form_id'        => (string) $form['id'],
		'form_title'     => isset( $form['title'] ) ? $form['title'] : '',
		'form_shortcode' => isset( $form['shortcode'] ) ? $form['shortcode'] : '',
		'entry_id'       => isset( $form_data['entry_id'] ) ? $form_data['entry_id'] : '',
		'created_at'     => isset( $meta['created_at'] ) ? $meta['created_at'] : current_time( 'mysql' ),
		'user_id'        => isset( $meta['user_id'] ) ? $meta['user_id'] : '',
		'ip_address'     => isset( $meta['ip_address'] ) ? $meta['ip_address'] : '',
		'browser'        => isset( $meta['browser'] ) ? $meta['browser'] : '',
		'device'         => isset( $meta['device'] ) ? $meta['device'] : '',
		'source_url'     => isset( $meta['source_url'] ) ? $meta['source_url'] : '',
	);

	foreach ( $form_data as $key => $value ) {
		if ( 'entry_id' === $key || 'form_id' === $key ) {
			continue;
		}

		$normalized = adfoin_htcontactform_normalize_field_key( $key );

		if ( '' === $normalized ) {
			continue;
		}

		$payload[ $normalized ] = adfoin_htcontactform_format_value( $value );
	}

	return $payload;
}

/**
 * Fetch HT Contact Forms list.
 *
 * @return array<string,string>
 */
function adfoin_htcontactform_fetch_forms() {
	if ( ! class_exists( '\HTContactFormAdmin\Includes\Models\Form' ) ) {
		return array();
	}

	$model = \HTContactFormAdmin\Includes\Models\Form::get_instance();
	$forms = $model->get_all();
	$list  = array();

	if ( is_array( $forms ) ) {
		foreach ( $forms as $form ) {
			if ( empty( $form['id'] ) ) {
				continue;
			}

			$title            = ! empty( $form['title'] ) ? $form['title'] : sprintf( __( 'Form #%d', 'advanced-form-integration' ), $form['id'] );
			$list[ (string) $form['id'] ] = $title;
		}
	}

	return $list;
}

/**
 * Retrieve form by ID.
 *
 * @param int|string $form_id Form ID.
 * @return array|null
 */
function adfoin_htcontactform_get_form( $form_id ) {
	if ( ! class_exists( '\HTContactFormAdmin\Includes\Models\Form' ) ) {
		return null;
	}

	$model = \HTContactFormAdmin\Includes\Models\Form::get_instance();
	$form  = $model->get( absint( $form_id ) );

	return is_wp_error( $form ) ? null : $form;
}

/**
 * Extract input names from form data.
 *
 * @param array $form Form response.
 * @return array<int,string>
 */
function adfoin_htcontactform_extract_field_names( $form ) {
	$names = array();

	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $names;
	}

	foreach ( $form['fields'] as $field ) {
		if ( empty( $field['name'] ) ) {
			continue;
		}

		$names[] = $field['name'];
	}

	return array_values( array_unique( $names ) );
}

/**
 * Sanitize field key.
 *
 * @param string $name Original name.
 * @return string
 */
function adfoin_htcontactform_normalize_field_key( $name ) {
	$original = (string) $name;
	$name     = trim( $original );

	if ( '' === $name ) {
		return '';
	}

	$name = preg_replace( '/\[[^\]]*\]/', '_', $name );
	$name = preg_replace( '/[^a-zA-Z0-9_]+/', '_', $name );
	$name = trim( $name, '_' );

	if ( '' === $name ) {
		$name = sanitize_key( $original );
	}

	return $name;
}

/**
 * Normalize field value into string.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adfoin_htcontactform_format_value( $value ) {
	if ( is_array( $value ) ) {
		return wp_json_encode( $value );
	}

	return (string) $value;
}
