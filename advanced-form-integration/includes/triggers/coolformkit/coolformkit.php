<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve Cool FormKit forms.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_coolformkit_get_forms( $form_provider ) {
	if ( 'coolformkit' !== $form_provider ) {
		return;
	}

	$forms = adfoin_coolformkit_discover_forms();

	if ( empty( $forms ) ) {
		return array();
	}

	asort( $forms, SORT_NATURAL | SORT_FLAG_CASE );

	return $forms;
}

/**
 * Retrieve Cool FormKit fields for a given form.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Compound identifier.
 * @return array<string,string>|void
 */
function adfoin_coolformkit_get_form_fields( $form_provider, $form_id ) {
	if ( 'coolformkit' !== $form_provider ) {
		return;
	}

	$form_id = sanitize_text_field( $form_id );

	if ( empty( $form_id ) ) {
		return array();
	}

	$widget = adfoin_coolformkit_get_widget_instance( $form_id );

	if ( empty( $widget ) || empty( $widget['settings']['form_fields'] ) ) {
		return array();
	}

	$fields = array(
		'form_id'              => __( 'Form Identifier', 'advanced-form-integration' ),
		'form_name'            => __( 'Form Name', 'advanced-form-integration' ),
		'form_post_id'         => __( 'Form Post ID', 'advanced-form-integration' ),
		'submission_timestamp' => __( 'Submission Timestamp', 'advanced-form-integration' ),
		'submission_url'       => __( 'Submission URL', 'advanced-form-integration' ),
		'user_ip'              => __( 'User IP', 'advanced-form-integration' ),
		'user_agent'           => __( 'User Agent', 'advanced-form-integration' ),
	);

	foreach ( $widget['settings']['form_fields'] as $field ) {
		if ( empty( $field['custom_id'] ) ) {
			continue;
		}

		$field_key   = sanitize_key( $field['custom_id'] );
		$field_label = ! empty( $field['field_label'] ) ? $field['field_label'] : $field_key;

		$fields[ $field_key ]                = $field_label;
		$fields[ $field_key . '_label' ]     = sprintf( __( '%s (Label)', 'advanced-form-integration' ), $field_label );
		$fields[ $field_key . '_raw_value' ] = sprintf( __( '%s (Raw Value)', 'advanced-form-integration' ), $field_label );
	}

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

add_action( 'cfkef/form/entries', 'adfoin_coolformkit_handle_submission', 20, 3 );

/**
 * Handle Cool FormKit submissions.
 *
 * @param Cool_FormKit\Modules\Forms\Classes\Form_Record       $record       Record instance.
 * @param Cool_FormKit\Modules\Forms\Components\Ajax_Handler   $ajax_handler Ajax handler.
 * @param mixed                                                $action       Current action.
 * @return void
 */
function adfoin_coolformkit_handle_submission( $record, $ajax_handler, $action ) {
	if ( empty( $record ) || ! is_object( $record ) ) {
		return;
	}

	$form_post_id = $record->get_form_settings( 'form_post_id' );
	$current_form = is_object( $ajax_handler ) && method_exists( $ajax_handler, 'get_current_form' )
		? $ajax_handler->get_current_form()
		: array();

	$element_id = isset( $current_form['id'] ) ? sanitize_text_field( $current_form['id'] ) : '';

	if ( empty( $form_post_id ) || empty( $element_id ) ) {
		return;
	}

	$form_key = absint( $form_post_id ) . '|' . $element_id;

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'coolformkit', $form_key );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_coolformkit_prepare_payload( $record, $form_post_id, $element_id, $form_key );

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
 * Prepare payload data.
 *
 * @param Cool_FormKit\Modules\Forms\Classes\Form_Record $record     Record instance.
 * @param int                                            $post_id    Post ID.
 * @param string                                         $element_id Elementor widget ID.
 * @param string                                         $form_key   Compound form key.
 * @return array<string,mixed>
 */
function adfoin_coolformkit_prepare_payload( $record, $post_id, $element_id, $form_key ) {
	$fields = $record->get_field( null );

	$payload = array(
		'form_id'              => $form_key,
		'form_post_id'         => absint( $post_id ),
		'form_widget_id'       => $element_id,
		'form_name'            => $record->get_form_settings( 'form_name' ),
		'submission_timestamp' => time(),
		'submission_url'       => isset( $_POST['referrer'] ) ? esc_url_raw( wp_unslash( $_POST['referrer'] ) ) : '',
		'user_ip'              => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'user_agent'           => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);

	if ( is_array( $fields ) ) {
		foreach ( $fields as $key => $field ) {
			$field_key = sanitize_key( $key );

			if ( empty( $field_key ) ) {
				continue;
			}

			$value     = isset( $field['value'] ) ? $field['value'] : '';
			$raw_value = isset( $field['raw_value'] ) ? $field['raw_value'] : '';

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			if ( is_array( $raw_value ) ) {
				$raw_value = wp_json_encode( $raw_value );
			}

			$payload[ $field_key ]                = $value;
			$payload[ $field_key . '_label' ]     = isset( $field['title'] ) ? $field['title'] : $field_key;
			$payload[ $field_key . '_raw_value' ] = $raw_value;
		}
	}

	$meta = $record->get_form_meta(
		array(
			'page_url',
			'page_title',
			'user_agent',
			'remote_ip',
			'date',
			'time',
		)
	);

	if ( is_array( $meta ) ) {
		foreach ( $meta as $meta_key => $meta_value ) {
			$key = sanitize_key( $meta_key );
			if ( empty( $key ) ) {
				continue;
			}

			$payload[ 'meta_' . $key ] = isset( $meta_value['value'] ) ? $meta_value['value'] : '';
		}
	}

	return $payload;
}

/**
 * Discover available forms by scanning Elementor data.
 *
 * @return array<string,string>
 */
function adfoin_coolformkit_discover_forms() {
	$forms = array();

	$args = array(
		'post_type'      => 'any',
		'post_status'    => array( 'publish', 'private' ),
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_elementor_data',
				'value'   => '"widgetType":"cool-form"',
				'compare' => 'LIKE',
			),
		),
	);

	$posts = get_posts( $args );

	if ( empty( $posts ) ) {
		return $forms;
	}

	foreach ( $posts as $post ) {
		$data = get_post_meta( $post->ID, '_elementor_data', true );
		$elements = adfoin_coolformkit_decode_elementor_data( $data );

		if ( empty( $elements ) ) {
			continue;
		}

		adfoin_coolformkit_collect_forms_from_elements( $forms, $elements, $post );
	}

	return $forms;
}

/**
 * Decode Elementor JSON data.
 *
 * @param string $raw Raw meta value.
 * @return array
 */
function adfoin_coolformkit_decode_elementor_data( $raw ) {
	if ( empty( $raw ) ) {
		return array();
	}

	$data = json_decode( $raw, true );

	if ( null === $data ) {
		$data = json_decode( wp_unslash( $raw ), true );
	}

	return is_array( $data ) ? $data : array();
}

/**
 * Recursively collect forms from elements.
 *
 * @param array        $forms   Accumulator.
 * @param array        $elements Element list.
 * @param WP_Post      $post    Parent post.
 * @return void
 */
function adfoin_coolformkit_collect_forms_from_elements( &$forms, $elements, $post ) {
	foreach ( $elements as $element ) {
		if ( isset( $element['widgetType'] ) && 'cool-form' === $element['widgetType'] ) {
			$widget_id = isset( $element['id'] ) ? $element['id'] : '';
			if ( empty( $widget_id ) ) {
				continue;
			}

			$key   = absint( $post->ID ) . '|' . $widget_id;
			$title = adfoin_coolformkit_format_form_label( $post, $element );

			$forms[ $key ] = $title;
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			adfoin_coolformkit_collect_forms_from_elements( $forms, $element['elements'], $post );
		}
	}
}

/**
 * Format form label for UI.
 *
 * @param WP_Post $post   Parent post.
 * @param array   $widget Widget data.
 * @return string
 */
function adfoin_coolformkit_format_form_label( $post, $widget ) {
	$form_name = '';

	if ( isset( $widget['settings']['form_name'] ) && '' !== $widget['settings']['form_name'] ) {
		$form_name = $widget['settings']['form_name'];
	}

	if ( ! $form_name ) {
		$form_name = sprintf( __( 'Form %s', 'advanced-form-integration' ), $widget['id'] );
	}

	return sprintf(
		__( '%1$s (#%2$d) – %3$s', 'advanced-form-integration' ),
		get_the_title( $post ),
		$post->ID,
		$form_name
	);
}

/**
 * Get widget data by compound form ID.
 *
 * @param string $form_id Compound ID.
 * @return array
 */
function adfoin_coolformkit_get_widget_instance( $form_id ) {
	list( $post_id, $widget_id ) = array_pad( explode( '|', $form_id ), 2, '' );

	$post_id   = absint( $post_id );
	$widget_id = sanitize_text_field( $widget_id );

	if ( empty( $post_id ) || empty( $widget_id ) ) {
		return array();
	}

	$data     = get_post_meta( $post_id, '_elementor_data', true );
	$elements = adfoin_coolformkit_decode_elementor_data( $data );

	if ( empty( $elements ) ) {
		return array();
	}

	return adfoin_coolformkit_find_widget_recursive( $elements, $widget_id );
}

/**
 * Recursively search for widget by ID.
 *
 * @param array  $elements Elements structure.
 * @param string $widget_id Widget identifier.
 * @return array
 */
function adfoin_coolformkit_find_widget_recursive( $elements, $widget_id ) {
	foreach ( $elements as $element ) {
		if ( isset( $element['id'] ) && $widget_id === $element['id'] ) {
			return $element;
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$found = adfoin_coolformkit_find_widget_recursive( $element['elements'], $widget_id );
			if ( ! empty( $found ) ) {
				return $found;
			}
		}
	}

	return array();
}
