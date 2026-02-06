<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADFOIN_HASHFORM_PROVIDER', 'hashform' );

/**
 * Check if Hash Form is active.
 *
 * @return bool
 */
function adfoin_hashform_is_active() {
	return class_exists( 'HashFormEntry' ) && class_exists( 'HashFormFields' );
}

/**
 * Fetch available Hash Form forms.
 *
 * @param string $form_provider Provider key.
 * @return array<int,string>|void
 */
function adfoin_hashform_get_forms( $form_provider ) {
	if ( ADFOIN_HASHFORM_PROVIDER !== $form_provider || ! adfoin_hashform_is_active() ) {
		return;
	}

	global $wpdb;

	$table = $wpdb->prefix . 'hashform_forms';
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM {$table} WHERE status != %s ORDER BY name ASC", 'trash' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( empty( $rows ) ) {
		return array();
	}

	$forms = array();
	foreach ( $rows as $row ) {
		$title            = $row->name ? $row->name : sprintf( __( 'Form #%d', 'advanced-form-integration' ), $row->id );
		$forms[ $row->id ] = $title;
	}

	return $forms;
}

/**
 * Return Hash Form field list.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_hashform_get_form_fields( $form_provider, $form_id ) {
	if ( ADFOIN_HASHFORM_PROVIDER !== $form_provider ) {
		return;
	}

	$form_id = absint( $form_id );
	if ( $form_id < 1 ) {
		return array();
	}

	$fields_data = adfoin_hashform_collect_fields( $form_id );
	if ( empty( $fields_data ) ) {
		return array();
	}

	$fields = array();
	foreach ( $fields_data as $field ) {
		if ( adfoin_fs()->is_not_paying() && ! adfoin_hashform_field_allowed_in_free( $field['type'], $field['key'] ) ) {
			continue;
		}

		$fields[ $field['key'] ] = $field['label'];
	}

	$fields['form_id']         = __( 'Form ID', 'advanced-form-integration' );
	$fields['form_title']      = __( 'Form Title', 'advanced-form-integration' );
	$fields['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );
	$fields['user_ip']         = __( 'User IP', 'advanced-form-integration' );
	$fields['entry_id']        = __( 'Entry ID', 'advanced-form-integration' );
	$fields['entry_url']       = __( 'Entry Admin URL', 'advanced-form-integration' );
	$fields['location']        = __( 'Submission Page', 'advanced-form-integration' );

	$special = adfoin_get_special_tags();
	if ( is_array( $special ) ) {
		$fields = $fields + $special;
	}

	return $fields;
}

/**
 * Helper for UI titles.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return string|void
 */
function adfoin_hashform_get_form_name( $form_provider, $form_id ) {
	if ( ADFOIN_HASHFORM_PROVIDER !== $form_provider ) {
		return;
	}

	return get_the_title( absint( $form_id ) );
}

add_action( 'plugins_loaded', 'adfoin_hashform_bootstrap', 20 );

/**
 * Hook RomeTheme submission events.
 */
function adfoin_hashform_bootstrap() {
	if ( ! adfoin_hashform_is_active() ) {
		return;
	}

	add_action( 'hashform_after_email', 'adfoin_hashform_handle_submission', 10, 1 );
}

/**
 * Handle Hash Form submission.
 *
 * @param array<string,mixed> $submission Submission details.
 */
function adfoin_hashform_handle_submission( $submission ) {
	if ( empty( $submission ) || empty( $submission['form'] ) || ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$form    = $submission['form'];
	$form_id = isset( $form->id ) ? absint( $form->id ) : 0;

	if ( $form_id < 1 ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( ADFOIN_HASHFORM_PROVIDER, (string) $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_hashform_build_payload( $submission );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build payload data.
 *
 * @param array<string,mixed> $submission Submission.
 * @return array<string,mixed>
 */
function adfoin_hashform_build_payload( $submission ) {
	$form        = $submission['form'];
	$form_id     = isset( $form->id ) ? absint( $form->id ) : 0;
	$entry_id    = isset( $submission['entry_id'] ) ? absint( $submission['entry_id'] ) : 0;
	$metas       = isset( $submission['metas'] ) ? (array) $submission['metas'] : array();
	$location    = isset( $submission['location'] ) ? esc_url_raw( $submission['location'] ) : '';
	$fields_data = adfoin_hashform_collect_fields( $form_id );

	if ( empty( $fields_data ) ) {
		return array();
	}

	$field_lookup = array();
	foreach ( $fields_data as $field ) {
		$field_lookup[ $field['id'] ] = $field;
	}

	$entry = null;
	if ( $entry_id ) {
		$entry = HashFormEntry::get_entry_vars( $entry_id );
	}

	$payload = array();

	foreach ( $metas as $field_id => $meta ) {
		if ( empty( $field_lookup[ $field_id ] ) ) {
			continue;
		}

		$field = $field_lookup[ $field_id ];

		if ( adfoin_fs()->is_not_paying() && ! adfoin_hashform_field_allowed_in_free( $field['type'], $field['key'] ) ) {
			continue;
		}

		$value = adfoin_hashform_normalize_meta_value( $meta );
		if ( '' === $value || null === $value ) {
			continue;
		}

		$payload[ $field['key'] ] = $value;
	}

	if ( empty( $payload ) ) {
		return array();
	}

	$payload['form_id']         = $form_id;
	$payload['form_title']      = isset( $form->name ) ? $form->name : sprintf( __( 'Form #%d', 'advanced-form-integration' ), $form_id );
	$payload['submission_date'] = $entry && ! empty( $entry->created_at ) ? $entry->created_at : current_time( 'mysql' );
	$payload['user_ip']         = $entry && ! empty( $entry->ip ) ? $entry->ip : adfoin_get_user_ip();
    $payload['entry_id']        = $entry_id;
	$payload['entry_url']       = $entry_id ? admin_url( 'admin.php?page=hashform-entries&hashform_action=view&id=' . $entry_id ) : '';
	$payload['location']        = $location;

	$special = adfoin_get_special_tags_values( null );
	if ( is_array( $special ) ) {
		$payload = $payload + $special;
	}

	return $payload;
}

/**
 * Normalize meta value.
 *
 * @param array<string,mixed> $meta Meta entry.
 * @return string
 */
function adfoin_hashform_normalize_meta_value( $meta ) {
	if ( empty( $meta['value'] ) ) {
		return '';
	}

	$value = HashFormHelper::unserialize_or_decode( $meta['value'] );

	if ( is_array( $value ) ) {
		$flat = array();
		foreach ( $value as $item ) {
			if ( is_array( $item ) ) {
				$flat[] = implode( ' ', array_filter( $item ) );
			} else {
				$flat[] = $item;
			}
		}

		$value = implode( ', ', array_filter( $flat ) );
	}

	return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
}

/**
 * Collect form fields with metadata.
 *
 * @param int $form_id Form ID.
 * @return array<int,array<string,string>>
 */
function adfoin_hashform_collect_fields( $form_id ) {
	static $cache = array();

	if ( isset( $cache[ $form_id ] ) ) {
		return $cache[ $form_id ];
	}

	if ( ! $form_id || ! class_exists( 'HashFormFields' ) ) {
		return array();
	}

	$fields = HashFormFields::get_form_fields( $form_id );
	if ( empty( $fields ) ) {
		return array();
	}

	$mapped = array();
	foreach ( $fields as $field ) {
		$key   = ! empty( $field->field_key ) ? 'field_' . sanitize_key( $field->field_key ) : 'field_' . $field->id;
		$label = ! empty( $field->name ) ? $field->name : sprintf( __( 'Field %d', 'advanced-form-integration' ), $field->id );
		$type  = isset( $field->type ) ? $field->type : '';

		$mapped[] = array(
			'id'    => (int) $field->id,
			'key'   => $key,
			'label' => $label,
			'type'  => $type,
		);
	}

	$cache[ $form_id ] = $mapped;
	return $mapped;
}

/**
 * Determine free-tier field availability.
 *
 * @param string $type Field type.
 * @param string $key  Field key.
 * @return bool
 */
function adfoin_hashform_field_allowed_in_free( $type, $key ) {
	if ( ! adfoin_fs()->is_not_paying() ) {
		return true;
	}

	return in_array( $type, array( 'name', 'email' ), true );
}
