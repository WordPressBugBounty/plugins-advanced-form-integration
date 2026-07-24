<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve MW WP Form entries.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_mwwpform_get_forms( $form_provider ) {
	if ( 'mwwpform' !== $form_provider ) {
		return;
	}

	$forms = adfoin_mwwpform_fetch_forms();

	if ( empty( $forms ) ) {
		return array();
	}

	asort( $forms, SORT_NATURAL | SORT_FLAG_CASE );

	return $forms;
}

/**
 * Retrieve fields for MW WP Form.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form key (post ID).
 * @return array<string,string>|void
 */
function adfoin_mwwpform_get_form_fields( $form_provider, $form_id ) {
	if ( 'mwwpform' !== $form_provider ) {
		return;
	}

	$form = adfoin_mwwpform_get_form_by_id( $form_id );

	if ( empty( $form ) ) {
		return array();
	}

	$fields = array(
		'form_id'        => __( 'Form ID', 'advanced-form-integration' ),
		'form_title'     => __( 'Form Title', 'advanced-form-integration' ),
		'form_key'       => __( 'Form Key', 'advanced-form-integration' ),
		'view_condition' => __( 'View Condition', 'advanced-form-integration' ),
		'post_condition' => __( 'Post Condition', 'advanced-form-integration' ),
	);

	$markup_fields = adfoin_mwwpform_extract_names_from_markup( $form->post_content );

	foreach ( $markup_fields as $field_name ) {
		$key   = adfoin_mwwpform_normalize_field_key( $field_name );
		$label = sprintf( __( 'Field: %s', 'advanced-form-integration' ), $field_name );

		if ( ! isset( $fields[ $key ] ) ) {
			$fields[ $key ] = $label;
		}
	}

	$fields['form_id']  = __( 'Form ID', 'advanced-form-integration' );

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

/**
 * MW WP Form's actual submission-complete hook is dynamically namespaced per
 * form key — `mwform_after_send_{$form_key}` (confirmed in
 * classes/controllers/class.main.php) — not a single static action name.
 * There is no wildcard hook support in WordPress, so the real per-form hook
 * is registered here, dynamically, as soon as MW WP Form announces which
 * form_key is being processed (`mwform_start_main_process`, fired
 * unconditionally at the top of every real submission attempt).
 */
add_action( 'mwform_start_main_process', 'adfoin_mwwpform_register_after_send_hook', 10, 1 );

/**
 * Register the real per-form completion hook for the form being submitted.
 *
 * @param string $form_key Form key.
 * @return void
 */
function adfoin_mwwpform_register_after_send_hook( $form_key ) {
	if ( empty( $form_key ) ) {
		return;
	}

	add_action(
		'mwform_after_send_' . $form_key,
		function ( $data ) use ( $form_key ) {
			adfoin_mwwpform_handle_submission( $data, $form_key );
		},
		20,
		1
	);
}

/**
 * Process MW WP Form submissions.
 *
 * @param MW_WP_Form_Data $data     Data object.
 * @param string          $form_key Form key.
 * @return void
 */
function adfoin_mwwpform_handle_submission( $data, $form_key ) {
	if ( ! class_exists( 'MW_WP_Form_Data' ) || ! $data instanceof MW_WP_Form_Data ) {
		return;
	}

	if ( empty( $form_key ) ) {
		return;
	}

	$form     = adfoin_mwwpform_get_form_by_key( $form_key );
	$form_id  = $form ? (string) $form->ID : '';

	if ( empty( $form_id ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'mwwpform', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_mwwpform_prepare_payload( $data, $form );

	if ( empty( $payload ) ) {
		return;
	}

	$post               = adfoin_get_post_object();
	$special_tag_values = adfoin_get_special_tags_values( $post );

	if ( is_array( $special_tag_values ) ) {
		$payload = array_merge( $payload, $special_tag_values );
	}

	$payload['form_id'] = $form_id;

	adfoin_dispatch_integrations( $saved_records, $payload );
}

/**
 * Prepare submission payload.
 *
 * @param MW_WP_Form_Data $data Data object.
 * @param WP_Post         $form Form post.
 * @return array<string,mixed>
 */
function adfoin_mwwpform_prepare_payload( $data, $form ) {
	$payload = array(
		'form_id'        => (string) $form->ID,
		'form_title'     => $form->post_title,
		'form_key'       => $data->get_form_key(),
		'view_condition' => $data->get_view_flg(),
		'post_condition' => $data->get_post_condition(),
	);

	$variables = $data->gets();

	if ( is_array( $variables ) ) {
		foreach ( $variables as $name => $value ) {
			if ( '' === $name || $name[0] === '_' ) {
				continue;
			}

			$key = adfoin_mwwpform_normalize_field_key( $name );

			if ( '' === $key ) {
				continue;
			}

			$payload[ $key ] = adfoin_mwwpform_format_value( $value );
		}
	}

	return $payload;
}

/**
 * Fetch MW WP Form posts.
 *
 * @return array<string,string>
 */
function adfoin_mwwpform_fetch_forms() {
	$forms = array();

	$posts = get_posts(
		array(
			'post_type'      => MWF_Config::NAME,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	if ( empty( $posts ) ) {
		return $forms;
	}

	foreach ( $posts as $post ) {
		$title            = $post->post_title ? $post->post_title : sprintf( __( 'Form #%d', 'advanced-form-integration' ), $post->ID );
		$forms[ (string) $post->ID ] = $title;
	}

	return $forms;
}

/**
 * Retrieve form post by ID.
 *
 * @param int|string $form_id Post ID.
 * @return WP_Post|null
 */
function adfoin_mwwpform_get_form_by_id( $form_id ) {
	$form_id = absint( $form_id );

	if ( $form_id < 1 ) {
		return null;
	}

	$post = get_post( $form_id );

	if ( $post && MWF_Config::NAME === $post->post_type ) {
		return $post;
	}

	return null;
}

/**
 * Retrieve form post by key.
 *
 * @param string $form_key Form key.
 * @return WP_Post|null
 */
function adfoin_mwwpform_get_form_by_key( $form_key ) {
	$form_key = sanitize_text_field( $form_key );

	if ( '' === $form_key ) {
		return null;
	}

	$form_id = MWF_Functions::get_form_id_from_form_key( $form_key );

	return $form_id ? adfoin_mwwpform_get_form_by_id( $form_id ) : null;
}

/**
 * Normalize field key.
 *
 * @param string $name Field name.
 * @return string
 */
function adfoin_mwwpform_normalize_field_key( $name ) {
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
 * Extract unique field names from form markup.
 *
 * @param string $markup Form markup.
 * @return array<int,string>
 */
function adfoin_mwwpform_extract_names_from_markup( $markup ) {
	if ( empty( $markup ) ) {
		return array();
	}

	$names = array();

	if ( preg_match_all( '/\bname\s*=\s*(["\'])(.*?)\1/i', $markup, $matches ) ) {
		foreach ( $matches[2] as $name ) {
			$name = trim( $name );

			if ( '' === $name || '_' === $name[0] ) {
				continue;
			}

			$names[] = $name;
		}
	}

	return array_values( array_unique( $names ) );
}

/**
 * Format field value to string.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adfoin_mwwpform_format_value( $value ) {
	if ( is_array( $value ) ) {
		$value = wp_json_encode( $value );
	}

	return (string) $value;
}
