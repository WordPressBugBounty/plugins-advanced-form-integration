<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADFOIN_ROMETHEMEFORM_PROVIDER', 'romethemeform' );

/**
 * Determine if RomeTheme Form plugin is active.
 *
 * @return bool
 */
function adfoin_romethemeform_is_active() {
	return class_exists( '\RomethemeForm\Form\Form' );
}

/**
 * Return RomeTheme forms.
 *
 * @param string $form_provider Provider key.
 * @return array<int,string>|void
 */
function adfoin_romethemeform_get_forms( $form_provider ) {
	if ( ADFOIN_ROMETHEMEFORM_PROVIDER !== $form_provider || ! adfoin_romethemeform_is_active() ) {
		return;
	}

	$forms = get_posts(
		array(
			'post_type'      => 'romethemeform_form',
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
 * Return field map for a form.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_romethemeform_get_form_fields( $form_provider, $form_id ) {
	if ( ADFOIN_ROMETHEMEFORM_PROVIDER !== $form_provider ) {
		return;
	}

	$form_id = absint( $form_id );
	if ( ! $form_id ) {
		return array();
	}

	$fields = adfoin_romethemeform_collect_fields( $form_id );
	if ( empty( $fields ) ) {
		return array();
	}

	$field_map = array();

	foreach ( $fields as $field ) {
		if ( empty( $field['id'] ) || empty( $field['label'] ) ) {
			continue;
		}

		if ( adfoin_fs()->is_not_paying() && 'email' !== $field['type'] && false === strpos( $field['id'], 'name' ) ) {
			continue;
		}

		$field_map[ $field['id'] ] = $field['label'];
	}

	$field_map['form_id']         = __( 'Form ID', 'advanced-form-integration' );
	$field_map['form_title']      = __( 'Form Title', 'advanced-form-integration' );
	$field_map['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );
	$field_map['user_ip']         = __( 'User IP', 'advanced-form-integration' );
	$field_map['entry_id']        = __( 'Entry ID', 'advanced-form-integration' );
	$field_map['entry_url']       = __( 'Entry Admin URL', 'advanced-form-integration' );

	$special = adfoin_get_special_tags();
	if ( is_array( $special ) ) {
		$field_map = $field_map + $special;
	}

	return $field_map;
}

/**
 * Helper for UI.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return string|void
 */
function adfoin_romethemeform_get_form_name( $form_provider, $form_id ) {
	if ( ADFOIN_ROMETHEMEFORM_PROVIDER !== $form_provider ) {
		return;
	}

	return get_the_title( absint( $form_id ) );
}

add_action( 'plugins_loaded', 'adfoin_romethemeform_bootstrap', 20 );

/**
 * Hook into RomeTheme submissions.
 */
function adfoin_romethemeform_bootstrap() {
	if ( ! adfoin_romethemeform_is_active() ) {
		return;
	}

	add_action( 'wp_ajax_rformsendform', 'adfoin_romethemeform_capture_submission', 100 );
	add_action( 'wp_ajax_nopriv_rformsendform', 'adfoin_romethemeform_capture_submission', 100 );
}

/**
 * Capture submission before RomeTheme sends its response.
 */
function adfoin_romethemeform_capture_submission() {
	if ( empty( $_POST ) || ! class_exists( 'Advanced_Form_Integration_Integration' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$form_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	if ( ! $form_id ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( ADFOIN_ROMETHEMEFORM_PROVIDER, (string) $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_romethemeform_build_payload( $form_id );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build payload from submission data.
 *
 * @param int $form_id Form ID.
 * @return array<string,mixed>
 */
function adfoin_romethemeform_build_payload( $form_id ) {
	$data_raw = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$data     = json_decode( sanitize_text_field( $data_raw ), true );

	if ( empty( $data ) || ! is_array( $data ) ) {
		return array();
	}

	$fields = array();
	foreach ( $data as $key => $value ) {
		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$fields[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
	}

	if ( empty( $fields ) ) {
		return array();
	}

	$entry_id_header = adfoin_romethemeform_get_last_entry_id();
	$entry_url       = $entry_id_header ? admin_url( 'admin.php?page=romethemeform-entries&entry_id=' . $entry_id_header ) : '';

	global $post;
	$special = adfoin_get_special_tags_values( $post );

	$payload = $fields;
	$payload['form_id']         = $form_id;
	$payload['form_title']      = get_the_title( $form_id );
	$payload['submission_date'] = current_time( 'mysql' );
	$payload['user_ip']         = adfoin_get_user_ip();
	$payload['entry_id']        = $entry_id_header;
	$payload['entry_url']       = $entry_url;

	if ( is_array( $special ) ) {
		$payload = $payload + $special;
	}

	return $payload;
}

/**
 * Retrieve last inserted entry ID stored by RomeTheme.
 *
 * @return int
 */
function adfoin_romethemeform_get_last_entry_id() {
	global $wpdb;

	return (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts} WHERE post_type = 'romethemeform_entry'" );
}

/**
 * Collect form field definitions by parsing Elementor document.
 *
 * @param int $form_id Form ID.
 * @return array<int,array<string,string>>
 */
function adfoin_romethemeform_collect_fields( $form_id ) {
	if ( ! function_exists( 'parse_blocks' ) ) {
		return array();
	}

	$document = \Elementor\Plugin::$instance->documents->get_doc_for_frontend( $form_id );
	if ( ! $document ) {
		return array();
	}

	$data = $document->get_elements_data();
	if ( empty( $data ) || ! is_array( $data ) ) {
		return array();
	}

	return adfoin_romethemeform_extract_fields_from_widgets( $data );
}

/**
 * Recursively extract field info from Elementor widgets.
 *
 * @param array<int,array<string,mixed>> $elements Element data.
 * @return array<int,array<string,string>>
 */
function adfoin_romethemeform_extract_fields_from_widgets( $elements ) {
	$fields = array();

	foreach ( $elements as $element ) {
		if ( isset( $element['elType'] ) && 'widget' === $element['elType'] && ! empty( $element['widgetType'] ) ) {
			$field = adfoin_romethemeform_field_from_widget( $element );
			if ( ! empty( $field['id'] ) ) {
				$fields[] = $field;
			}
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$fields = array_merge( $fields, adfoin_romethemeform_extract_fields_from_widgets( $element['elements'] ) );
		}
	}

	return $fields;
}

/**
 * Convert widget settings to field definition.
 *
 * @param array<string,mixed> $element Elementor widget data.
 * @return array<string,string>
 */
function adfoin_romethemeform_field_from_widget( $element ) {
	$settings = isset( $element['settings'] ) ? $element['settings'] : array();
	$type     = isset( $element['widgetType'] ) ? $element['widgetType'] : '';

	$name = '';
	if ( isset( $settings['input_name'] ) && $settings['input_name'] ) {
		$name = sanitize_key( $settings['input_name'] );
	} elseif ( isset( $settings['field_name'] ) && $settings['field_name'] ) {
		$name = sanitize_key( $settings['field_name'] );
	}

	$label = '';
	if ( isset( $settings['input_label'] ) && $settings['input_label'] ) {
		$label = sanitize_text_field( $settings['input_label'] );
	} elseif ( $name ) {
		$label = ucwords( str_replace( array( '-', '_' ), ' ', $name ) );
	} else {
		$label = __( 'Field', 'advanced-form-integration' );
	}

	return array(
		'id'    => $name,
		'label' => $label,
		'type'  => adfoin_romethemeform_normalize_field_type( $type ),
	);
}

/**
 * Normalize field type slug.
 *
 * @param string $widget_type Widget type.
 * @return string
 */
function adfoin_romethemeform_normalize_field_type( $widget_type ) {
	if ( false !== strpos( $widget_type, 'email' ) ) {
		return 'email';
	}

	if ( false !== strpos( $widget_type, 'phone' ) ) {
		return 'phone';
	}

	return 'text';
}
