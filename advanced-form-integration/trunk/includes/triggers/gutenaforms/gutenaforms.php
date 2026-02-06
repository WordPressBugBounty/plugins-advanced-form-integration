<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return available Gutena Forms entries.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_gutenaforms_get_forms( $form_provider ) {
	if ( 'gutenaforms' !== $form_provider ) {
		return;
	}

	$forms    = array();
	$form_ids = adfoin_gutenaforms_get_form_ids();

	foreach ( $form_ids as $form_id ) {
		$schema = adfoin_gutenaforms_get_form_schema( $form_id );
		if ( empty( $schema ) ) {
			continue;
		}

		$forms[ $form_id ] = adfoin_gutenaforms_resolve_form_name( $form_id, $schema );
	}

	if ( empty( $forms ) ) {
		$forms = adfoin_gutenaforms_get_forms_from_table();
	}

	if ( ! empty( $forms ) && is_array( $forms ) ) {
		asort( $forms, SORT_NATURAL | SORT_FLAG_CASE );
	}

	return $forms;
}

/**
 * Return Gutena Forms fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Block form ID.
 * @return array<string,string>|void
 */
function adfoin_gutenaforms_get_form_fields( $form_provider, $form_id ) {
	if ( 'gutenaforms' !== $form_provider ) {
		return;
	}

	$form_id = sanitize_key( $form_id );
	if ( empty( $form_id ) ) {
		return array();
	}

	$schema = adfoin_gutenaforms_get_form_schema( $form_id );
	if ( empty( $schema ) ) {
		return array();
	}

	$fields = array(
		'form_id'                  => __( 'Form ID', 'advanced-form-integration' ),
		'form_name'                => __( 'Form Name', 'advanced-form-integration' ),
		'form_description'         => __( 'Form Description', 'advanced-form-integration' ),
		'admin_emails'             => __( 'Admin Emails', 'advanced-form-integration' ),
		'email_subject'            => __( 'Admin Email Subject', 'advanced-form-integration' ),
		'reply_to_email'           => __( 'Reply-To Email', 'advanced-form-integration' ),
		'reply_to_first_name'      => __( 'Reply-To First Name', 'advanced-form-integration' ),
		'reply_to_last_name'       => __( 'Reply-To Last Name', 'advanced-form-integration' ),
		'submission_timestamp'     => __( 'Submission Timestamp', 'advanced-form-integration' ),
		'submission_datetime'      => __( 'Submission DateTime', 'advanced-form-integration' ),
		'submission_timezone'      => __( 'Submission Timezone', 'advanced-form-integration' ),
		'submission_payload_json'  => __( 'Submission Payload (JSON)', 'advanced-form-integration' ),
		'submission_field_count'   => __( 'Submission Field Count', 'advanced-form-integration' ),
	);

	if ( ! empty( $schema['form_fields'] ) && is_array( $schema['form_fields'] ) ) {
		foreach ( $schema['form_fields'] as $field_key => $field_meta ) {
			if ( empty( $field_key ) || ! is_array( $field_meta ) ) {
				continue;
			}

			if ( ! empty( $field_meta['fieldType'] ) && 'optin' === $field_meta['fieldType'] ) {
				continue;
			}

			$label          = ! empty( $field_meta['fieldName'] ) ? $field_meta['fieldName'] : str_replace( '_', ' ', $field_key );
			$fields[ $field_key ]          = $label;
			$fields[ $field_key . '_label' ] = sprintf( __( '%s (Label)', 'advanced-form-integration' ), $label );
			$fields[ $field_key . '_raw' ]   = sprintf( __( '%s (Raw Value)', 'advanced-form-integration' ), $label );
		}
	}

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $fields ) && is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

add_action( 'gutena_forms_submitted_data', 'adfoin_gutenaforms_handle_submission', PHP_INT_MAX, 3 );

/**
 * Handle Gutena Forms submission.
 *
 * @param array<string,mixed> $raw_data    Submitted data.
 * @param string              $form_id     Block form ID.
 * @param array<string,mixed> $field_schema Field catalog.
 * @return void
 */
function adfoin_gutenaforms_handle_submission( $raw_data, $form_id, $field_schema ) {
	$form_id = sanitize_key( $form_id );

	if ( empty( $form_id ) || empty( $raw_data ) || ! is_array( $raw_data ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'gutenaforms', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_gutenaforms_prepare_payload( $raw_data, $form_id, $field_schema );

	if ( empty( $payload ) ) {
		return;
	}

	$post                = adfoin_get_post_object();
	$special_tag_values  = adfoin_get_special_tags_values( $post );

	if ( is_array( $special_tag_values ) ) {
		$payload = array_merge( $payload, $special_tag_values );
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Prepare payload for AFI actions.
 *
 * @param array<string,mixed> $raw_data Raw submission data.
 * @param string              $form_id  Form identifier.
 * @param array<string,mixed> $field_schema Field schema.
 * @return array<string,mixed>
 */
function adfoin_gutenaforms_prepare_payload( $raw_data, $form_id, $field_schema ) {
	$payload             = array();
	$schema              = adfoin_gutenaforms_get_form_schema( $form_id );
	$form_attrs          = isset( $schema['form_attrs'] ) && is_array( $schema['form_attrs'] ) ? $schema['form_attrs'] : array();
	$submission_time     = current_time( 'timestamp' );
	$timezone_string     = get_option( 'timezone_string' );
	$timezone_string     = $timezone_string ? $timezone_string : 'UTC';
	$submission_datetime = wp_date( 'c', $submission_time );

	foreach ( $raw_data as $field_key => $field_meta ) {
		if ( ! is_array( $field_meta ) ) {
			continue;
		}

		$field_key_sanitized = sanitize_key( $field_key );
		if ( empty( $field_key_sanitized ) ) {
			continue;
		}

		$value = isset( $field_meta['value'] ) ? $field_meta['value'] : '';
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}

		$payload[ $field_key_sanitized ] = $value;

		if ( isset( $field_meta['label'] ) ) {
			$payload[ $field_key_sanitized . '_label' ] = $field_meta['label'];
		}

		if ( isset( $field_meta['raw_value'] ) ) {
			$payload[ $field_key_sanitized . '_raw' ] = is_scalar( $field_meta['raw_value'] )
				? $field_meta['raw_value']
				: wp_json_encode( $field_meta['raw_value'] );
		}
	}

	$payload['form_id']                 = $form_id;
	$payload['form_name']               = adfoin_gutenaforms_resolve_form_name( $form_id, $schema );
	$payload['form_description']        = isset( $form_attrs['formDescription'] ) ? $form_attrs['formDescription'] : '';
	$payload['admin_emails']            = adfoin_gutenaforms_flatten_emails( isset( $form_attrs['adminEmails'] ) ? $form_attrs['adminEmails'] : '' );
	$payload['email_subject']           = isset( $form_attrs['adminEmailSubject'] ) ? $form_attrs['adminEmailSubject'] : '';
	$payload['reply_to_email']          = isset( $form_attrs['replyToEmail'] ) ? $form_attrs['replyToEmail'] : '';
	$payload['reply_to_first_name']     = isset( $form_attrs['replyToName'] ) ? $form_attrs['replyToName'] : '';
	$payload['reply_to_last_name']      = isset( $form_attrs['replyToLastName'] ) ? $form_attrs['replyToLastName'] : '';
	$payload['submission_timestamp']    = $submission_time;
	$payload['submission_datetime']     = $submission_datetime;
	$payload['submission_timezone']     = $timezone_string;
	$payload['submission_field_count']  = count( $raw_data );
	$payload['submission_payload_json'] = wp_json_encode( $raw_data );

	return $payload;
}

/**
 * Fetch stored form IDs.
 *
 * @return array<int,string>
 */
function adfoin_gutenaforms_get_form_ids() {
	$form_ids = get_option( 'gutena_form_ids', array() );

	if ( empty( $form_ids ) || ! is_array( $form_ids ) ) {
		return array();
	}

	$sanitized = array();

	foreach ( $form_ids as $form_id ) {
		$key = sanitize_key( $form_id );
		if ( ! empty( $key ) ) {
			$sanitized[] = $key;
		}
	}

	return array_unique( $sanitized );
}

/**
 * Retrieve form schema by form ID.
 *
 * @param string $form_id Form identifier.
 * @return array<string,mixed>
 */
function adfoin_gutenaforms_get_form_schema( $form_id ) {
	$form_id = sanitize_key( $form_id );

	if ( empty( $form_id ) ) {
		return array();
	}

	$schema = get_option( $form_id );

	if ( empty( $schema ) ) {
		$row = adfoin_gutenaforms_get_form_row_by_block_id( $form_id );

		if ( $row && ! empty( $row->form_schema ) ) {
			$schema = maybe_unserialize( $row->form_schema );
		}
	}

	return is_array( $schema ) ? $schema : array();
}

/**
 * Resolve a user friendly form name.
 *
 * @param string                     $form_id Form identifier.
 * @param array<string,mixed>        $schema  Form schema.
 * @return string
 */
function adfoin_gutenaforms_resolve_form_name( $form_id, $schema ) {
	$form_attrs = isset( $schema['form_attrs'] ) && is_array( $schema['form_attrs'] ) ? $schema['form_attrs'] : array();

	if ( ! empty( $form_attrs['formName'] ) ) {
		return $form_attrs['formName'];
	}

	if ( isset( $schema['form_name'] ) && $schema['form_name'] ) {
		return $schema['form_name'];
	}

	return sprintf( __( 'Form %s', 'advanced-form-integration' ), $form_id );
}

/**
 * Create label/value map from database table.
 *
 * @return array<string,string>
 */
function adfoin_gutenaforms_get_forms_from_table() {
	global $wpdb;

	if ( empty( $wpdb ) ) {
		return array();
	}

	$table = $wpdb->prefix . 'gutenaforms';

	if ( ! adfoin_gutenaforms_table_exists( $table ) ) {
		return array();
	}

	$rows = $wpdb->get_results(
		"SELECT block_form_id, form_name FROM {$table} WHERE published = 1",
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		return array();
	}

	$forms = array();

	foreach ( $rows as $row ) {
		$block_id = sanitize_key( $row['block_form_id'] );
		if ( empty( $block_id ) ) {
			continue;
		}

		$forms[ $block_id ] = ! empty( $row['form_name'] )
			? $row['form_name']
			: sprintf( __( 'Form %s', 'advanced-form-integration' ), $block_id );
	}

	return $forms;
}

/**
 * Retrieve form row from DB.
 *
 * @param string $block_form_id Form identifier.
 * @return object|null
 */
function adfoin_gutenaforms_get_form_row_by_block_id( $block_form_id ) {
	global $wpdb;

	if ( empty( $wpdb ) ) {
		return null;
	}

	$table = $wpdb->prefix . 'gutenaforms';

	if ( ! adfoin_gutenaforms_table_exists( $table ) ) {
		return null;
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT form_schema, form_name FROM {$table} WHERE block_form_id = %s",
			$block_form_id
		)
	);
}

/**
 * Check whether Gutena Forms table exists.
 *
 * @param string $table Table name.
 * @return bool
 */
function adfoin_gutenaforms_table_exists( $table ) {
	global $wpdb;

	if ( empty( $wpdb ) || empty( $table ) ) {
		return false;
	}

	/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery */
	$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

	return ( $result === $table );
}

/**
 * Convert admin email settings into CSV string.
 *
 * @param mixed $emails Stored admin email setting.
 * @return string
 */
function adfoin_gutenaforms_flatten_emails( $emails ) {
	if ( empty( $emails ) ) {
		return '';
	}

	if ( is_array( $emails ) ) {
		$emails = array_map( 'sanitize_email', $emails );
		return implode( ',', array_filter( $emails ) );
	}

	return sanitize_email( $emails );
}
