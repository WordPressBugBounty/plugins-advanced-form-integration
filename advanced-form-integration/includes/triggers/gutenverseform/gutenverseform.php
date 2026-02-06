<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenverse Form provider key.
 */
define( 'ADFOIN_GUTENVERSEFORM_PROVIDER', 'gutenverseform' );

/**
 * Check if Gutenverse Form plugin is active.
 *
 * @return bool
 */
function adfoin_gutenverseform_is_active() {
	return class_exists( '\Gutenverse_Form\Init' );
}

/**
 * Get available Gutenverse forms.
 *
 * @param string $form_provider Provider key.
 * @return array<int,string>|void
 */
function adfoin_gutenverseform_get_forms( $form_provider ) {
	if ( ADFOIN_GUTENVERSEFORM_PROVIDER !== $form_provider || ! adfoin_gutenverseform_is_active() ) {
		return;
	}

	$forms = get_posts(
		array(
			'post_type'      => \Gutenverse_Form\Form::POST_TYPE,
			'post_status'    => array( 'publish' ),
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
 * Retrieve Gutenverse form field labels.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_gutenverseform_get_form_fields( $form_provider, $form_id ) {
	if ( ADFOIN_GUTENVERSEFORM_PROVIDER !== $form_provider ) {
		return;
	}

	$form_id = absint( $form_id );
	if ( ! $form_id ) {
		return array();
	}

	$fields       = adfoin_gutenverseform_collect_form_fields( $form_id );
	$field_labels = array();

	foreach ( $fields as $field ) {
		if ( empty( $field['name'] ) ) {
			continue;
		}

		if ( adfoin_fs()->is_not_paying() && ! adfoin_gutenverseform_field_allowed_in_free( $field['type'], $field['name'] ) ) {
			continue;
		}

		$field_labels[ $field['name'] ] = $field['label'];
	}

	$field_labels['form_id']         = __( 'Form ID', 'advanced-form-integration' );
	$field_labels['form_title']      = __( 'Form Title', 'advanced-form-integration' );
	$field_labels['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );
	$field_labels['user_ip']         = __( 'User IP', 'advanced-form-integration' );
	$field_labels['entry_id']        = __( 'Entry ID', 'advanced-form-integration' );

	$special = adfoin_get_special_tags();
	if ( is_array( $special ) ) {
		$field_labels = $field_labels + $special;
	}

	return $field_labels;
}

/**
 * Helper for integration UI.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return string|void
 */
function adfoin_gutenverseform_get_form_name( $form_provider, $form_id ) {
	if ( ADFOIN_GUTENVERSEFORM_PROVIDER !== $form_provider ) {
		return;
	}

	return get_the_title( absint( $form_id ) );
}

add_filter( 'rest_post_dispatch', 'adfoin_gutenverseform_capture_submission', 10, 3 );

/**
 * Capture REST submission responses.
 *
 * @param WP_HTTP_Response|WP_Error $response Response object.
 * @param WP_REST_Server            $server   Server instance.
 * @param WP_REST_Request           $request  Request instance.
 * @return WP_HTTP_Response|WP_Error
 */
function adfoin_gutenverseform_capture_submission( $response, $server, $request ) {
	if ( ! adfoin_gutenverseform_should_listen( $request, $response ) ) {
		return $response;
	}

	$form_entry = $request->get_param( 'form-entry' );
	$form_id    = isset( $form_entry['formId'] ) ? absint( $form_entry['formId'] ) : 0;

	if ( ! $form_id ) {
		return $response;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return $response;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( ADFOIN_GUTENVERSEFORM_PROVIDER, (string) $form_id );

	if ( empty( $saved_records ) ) {
		return $response;
	}

	$payload = adfoin_gutenverseform_build_payload( $form_id, $form_entry, $response );

	if ( empty( $payload ) ) {
		return $response;
	}

	$integration->send( $saved_records, $payload );

	return $response;
}

/**
 * Determine whether we should inspect this REST request.
 *
 * @param WP_REST_Request           $request  Request object.
 * @param WP_HTTP_Response|WP_Error $response Response object.
 * @return bool
 */
function adfoin_gutenverseform_should_listen( $request, $response ) {
	if ( ! adfoin_gutenverseform_is_active() ) {
		return false;
	}

	if ( ! ( $request instanceof WP_REST_Request ) ) {
		return false;
	}

	$route = $request->get_route();
	if ( false === strpos( $route, 'gutenverse-form-client/v1/form/submit' ) ) {
		return false;
	}

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$response_data = $response instanceof WP_HTTP_Response ? $response->get_data() : null;

	if ( empty( $response_data ) || empty( $response_data['entry_id'] ) ) {
		return false;
	}

	return true;
}

/**
 * Build payload from REST request.
 *
 * @param int                              $form_id    Form ID.
 * @param array<string,mixed>|null         $form_entry Form entry payload.
 * @param WP_HTTP_Response|WP_Error|mixed  $response   Response object.
 * @return array<string,mixed>
 */
function adfoin_gutenverseform_build_payload( $form_id, $form_entry, $response ) {
	if ( empty( $form_entry ) || empty( $form_entry['data'] ) ) {
		return array();
	}

	$entries = adfoin_gutenverseform_normalize_entries( $form_entry['data'] );

	if ( empty( $entries ) ) {
		return array();
	}

	$is_free = adfoin_fs()->is_not_paying();
	$fields  = array();

	foreach ( $entries as $entry ) {
		if ( empty( $entry['id'] ) ) {
			continue;
		}

		if ( $is_free && ! adfoin_gutenverseform_field_allowed_in_free( $entry['type'], $entry['id'] ) ) {
			continue;
		}

		$fields[ $entry['id'] ] = $entry['value'];
	}

	if ( empty( $fields ) ) {
		return array();
	}

	$post_id = isset( $form_entry['postId'] ) ? absint( $form_entry['postId'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;

	$response_data = $response instanceof WP_HTTP_Response ? $response->get_data() : array();

	$fields['form_id']         = $form_id;
	$fields['form_title']      = get_the_title( $form_id );
	$fields['submission_date'] = current_time( 'mysql' );
	$fields['user_ip']         = adfoin_get_user_ip();
	$fields['entry_id']        = isset( $response_data['entry_id'] ) ? $response_data['entry_id'] : '';

	$special = adfoin_get_special_tags_values( $post );
	if ( is_array( $special ) ) {
		$fields = $fields + $special;
	}

	return $fields;
}

/**
 * Normalize entry data matching Gutenverse sanitization.
 *
 * @param array<int,array<string,mixed>> $entry_data Entry data.
 * @return array<int,array<string,mixed>>
 */
function adfoin_gutenverseform_normalize_entries( $entry_data ) {
	if ( ! function_exists( 'rest_sanitize_array' ) ) {
		return array();
	}

	$entry_data = rest_sanitize_array( (array) $entry_data );
	$normalized = array();

	foreach ( $entry_data as $data ) {
		if ( empty( $data['id'] ) ) {
			continue;
		}

		$type = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'text';
		$id   = sanitize_key( $data['id'] );
		$value_raw = isset( $data['value'] ) ? $data['value'] : '';

		switch ( $type ) {
			case 'email':
				$value = sanitize_email( $value_raw );
				break;
			case 'textarea':
				$value = sanitize_textarea_field( $value_raw );
				break;
			case 'number':
				$value = floatval( $value_raw );
				break;
			case 'switch':
				$value = rest_sanitize_boolean( $value_raw );
				break;
			case 'multiselect':
			case 'multi-group-select':
			case 'checkbox':
				$value = rest_sanitize_array( (array) $value_raw );
				$value = implode(
					', ',
					array_map(
						static function ( $item ) {
							return sanitize_text_field( $item );
						},
						(array) $value
					)
				);
				break;
			default:
				$value = sanitize_text_field( $value_raw );
		}

		$normalized[] = array(
			'id'    => $id,
			'value' => $value,
			'type'  => $type,
		);
	}

	return $normalized;
}

/**
 * Collect field definitions from form content.
 *
 * @param int $form_id Form ID.
 * @return array<int,array<string,string>>
 */
function adfoin_gutenverseform_collect_form_fields( $form_id ) {
	$content = get_post_field( 'post_content', $form_id );
	if ( empty( $content ) || ! function_exists( 'parse_blocks' ) ) {
		return array();
	}

	$blocks = parse_blocks( $content );
	return adfoin_gutenverseform_extract_fields_from_blocks( $blocks );
}

/**
 * Recursively extract fields from Gutenberg blocks.
 *
 * @param array<int,mixed> $blocks Blocks array.
 * @return array<int,array<string,string>>
 */
function adfoin_gutenverseform_extract_fields_from_blocks( $blocks ) {
	$fields = array();

	foreach ( (array) $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}

		if ( 0 === strpos( $block['blockName'], 'gutenverse/form-input-' ) ) {
			$field = adfoin_gutenverseform_field_from_block( $block );
			if ( ! empty( $field['name'] ) ) {
				$fields[] = $field;
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$fields = array_merge( $fields, adfoin_gutenverseform_extract_fields_from_blocks( $block['innerBlocks'] ) );
		}
	}

	return $fields;
}

/**
 * Convert block data into field definition.
 *
 * @param array<string,mixed> $block Block data.
 * @return array<string,string>
 */
function adfoin_gutenverseform_field_from_block( $block ) {
	$attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

	$name = '';
	if ( ! empty( $attrs['inputName'] ) ) {
		$name = sanitize_key( $attrs['inputName'] );
	} elseif ( ! empty( $attrs['name'] ) ) {
		$name = sanitize_key( $attrs['name'] );
	}

	$label = '';
	if ( ! empty( $attrs['inputLabel'] ) ) {
		$label = sanitize_text_field( $attrs['inputLabel'] );
	} elseif ( ! empty( $attrs['inputPlaceholder'] ) ) {
		$label = sanitize_text_field( $attrs['inputPlaceholder'] );
	} elseif ( $name ) {
		$label = ucwords( str_replace( array( '-', '_' ), ' ', $name ) );
	} else {
		$label = __( 'Field', 'advanced-form-integration' );
	}

	$type = adfoin_gutenverseform_infer_field_type( $block['blockName'] );

	return array(
		'name'  => $name,
		'label' => $label,
		'type'  => $type,
	);
}

/**
 * Infer field type from block name.
 *
 * @param string $block_name Block identifier.
 * @return string
 */
function adfoin_gutenverseform_infer_field_type( $block_name ) {
	$type = str_replace( 'gutenverse/form-input-', '', $block_name );

	switch ( $type ) {
		case 'email':
			return 'email';
		case 'telp':
		case 'mobile':
			return 'phone';
		case 'checkbox':
		case 'multiselect':
		case 'multi-group-select':
		case 'radio':
		case 'select':
			return $type;
		default:
			return 'text';
	}
}

/**
 * Determine if field should be available in free version.
 *
 * @param string $type Field type.
 * @param string $name Field name.
 * @return bool
 */
function adfoin_gutenverseform_field_allowed_in_free( $type, $name ) {
	if ( ! adfoin_fs()->is_not_paying() ) {
		return true;
	}

	if ( 'email' === $type ) {
		return true;
	}

	if ( 'text' === $type && false !== strpos( $name, 'name' ) ) {
		return true;
	}

	return false;
}
