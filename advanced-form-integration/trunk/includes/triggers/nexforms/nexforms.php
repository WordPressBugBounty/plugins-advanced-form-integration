<?php
/**
 * Nex-Forms trigger integration.
 *
 * @package advanced-form-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADFOIN_NEXFORMS_PROVIDER', 'nexforms' );

/**
 * Check whether Nex-Forms is active.
 *
 * @return bool
 */
function adfoin_nexforms_is_active() {
	return class_exists( 'NEXForms5_Config' );
}

/**
 * Determine whether a table exists in the database.
 *
 * @param string $table_name Table name.
 * @return bool
 */
function adfoin_nexforms_table_exists( $table_name ) {
	global $wpdb;

	$table_name = (string) $table_name;

	if ( '' === $table_name ) {
		return false;
	}

	$pattern = $wpdb->esc_like( $table_name );
	$result  = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$pattern
		)
	);

	return (string) $result === $table_name;
}

/**
 * Retrieve Nex-Forms form list.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_nexforms_get_forms( $form_provider ) {
	if ( ADFOIN_NEXFORMS_PROVIDER !== $form_provider ) {
		return;
	}

	global $wpdb;

	$table   = $wpdb->prefix . 'wap_nex_forms';

	if ( ! adfoin_nexforms_table_exists( $table ) ) {
		return array();
	}

	$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT Id, title FROM {$table} WHERE is_form = %s ORDER BY title ASC",
			'1'
		)
	);

	if ( empty( $results ) ) {
		return array();
	}

	$forms = array();

	foreach ( $results as $row ) {
		$form_id = isset( $row->Id ) ? absint( $row->Id ) : 0;

		if ( $form_id < 1 ) {
			continue;
		}

		$title = isset( $row->title ) && '' !== $row->title
			? wp_strip_all_tags( $row->title )
			: sprintf( __( 'Form #%d', 'advanced-form-integration' ), $form_id );

		$forms[ (string) $form_id ] = $title;
	}

	return $forms;
}

/**
 * Retrieve Nex-Forms field map.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return array<string,string>|void
 */
function adfoin_nexforms_get_form_fields( $form_provider, $form_id ) {
	if ( ADFOIN_NEXFORMS_PROVIDER !== $form_provider ) {
		return;
	}

	$form_id = absint( $form_id );
	if ( $form_id < 1 ) {
		return array();
	}

	global $wpdb;

	$table = $wpdb->prefix . 'wap_nex_forms';

	if ( ! adfoin_nexforms_table_exists( $table ) ) {
		return array();
	}

	$fields = array(
		'form_id'          => __( 'Form ID', 'advanced-form-integration' ),
		'form_title'       => __( 'Form Title', 'advanced-form-integration' ),
		'entry_id'         => __( 'Entry ID', 'advanced-form-integration' ),
		'submission_date'  => __( 'Submission Date', 'advanced-form-integration' ),
		'submission_url'   => __( 'Submission URL', 'advanced-form-integration' ),
		'submission_title' => __( 'Submission Page Title', 'advanced-form-integration' ),
		'user_ip'          => __( 'User IP', 'advanced-form-integration' ),
		'user_agent'       => __( 'User Agent', 'advanced-form-integration' ),
	);

	$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT form_fields FROM {$table} WHERE Id = %d",
			$form_id
		)
	);

	if ( $row && ! empty( $row->form_fields ) ) {
		$discovered_fields = adfoin_nexforms_extract_form_fields( $row->form_fields );

		if ( ! empty( $discovered_fields ) ) {
			foreach ( $discovered_fields as $key => $label ) {
				if ( adfoin_fs()->is_not_paying() && ! adfoin_nexforms_field_allowed_in_free( $key ) ) {
					continue;
				}

				$fields[ $key ] = $label;
			}
		}
	}

	$special_tags = adfoin_get_special_tags();
	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

/**
 * Retrieve Nex-Forms form title.
 *
 * @param string $form_provider Provider key.
 * @param int    $form_id       Form ID.
 * @return string|void
 */
function adfoin_nexforms_get_form_name( $form_provider, $form_id ) {
	if ( ADFOIN_NEXFORMS_PROVIDER !== $form_provider ) {
		return;
	}

	$form_id = absint( $form_id );
	if ( $form_id < 1 ) {
		return '';
	}

	global $wpdb;

	$table = $wpdb->prefix . 'wap_nex_forms';

	if ( ! adfoin_nexforms_table_exists( $table ) ) {
		return '';
	}

	$title = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT title FROM {$table} WHERE Id = %d",
			$form_id
		)
	);

	return $title ? wp_strip_all_tags( $title ) : '';
}

add_action( 'plugins_loaded', 'adfoin_nexforms_bootstrap', 20 );

/**
 * Register submission hook once dependencies are ready.
 *
 * @return void
 */
// function adfoin_nexforms_bootstrap() {
	// if ( ! adfoin_nexforms_is_active() ) {
	// 	return;
	// }

	// add_action( 'NEXForms_submit_form_data', 'adfoin_nexforms_handle_submission', 10, 0 );
// }

/**
 * Handle Nex-Forms submissions.
 *
 * @return void
 */
function adfoin_nexforms_bootstrap() {
	if ( empty( $_POST['nex_forms_Id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$form_id = absint( wp_unslash( $_POST['nex_forms_Id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( $form_id < 1 || ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration = new Advanced_Form_Integration_Integration();

	$saved_records = $integration->get_by_trigger( ADFOIN_NEXFORMS_PROVIDER, (string) $form_id );

	if ( empty( $saved_records ) ) {
		$saved_records = $integration->get_by_trigger_partial( ADFOIN_NEXFORMS_PROVIDER, (string) $form_id );
	}

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_nexforms_prepare_payload( $form_id );

	if ( empty( $payload ) ) {
		return;
	}

	// $post               = adfoin_get_post_object();
	// $special_tag_values = adfoin_get_special_tags_values( $post );

	// if ( is_array( $special_tag_values ) ) {
	// 	$payload = array_merge( $payload, $special_tag_values );
	// }

	$integration->send( $saved_records, $payload );
}

/**
 * Prepare payload for Nex-Forms submission.
 *
 * @param int $form_id Form ID.
 * @return array<string,mixed>
 */
function adfoin_nexforms_prepare_payload( $form_id ) {
	$payload   = array();
	$form_id   = absint( $form_id );
	$form_name = adfoin_nexforms_get_form_name( ADFOIN_NEXFORMS_PROVIDER, $form_id );

	$payload['form_id'] = (string) $form_id;

	if ( '' !== $form_name ) {
		$payload['form_title'] = $form_name;
	}

	$entry_id = adfoin_nexforms_resolve_entry_id();
	$entry    = null;

	if ( $entry_id ) {
		$entry = adfoin_nexforms_fetch_entry( $entry_id );
	}

	if ( ! $entry ) {
		$entry = adfoin_nexforms_fetch_latest_entry( $form_id );
		if ( $entry ) {
			$entry_id = isset( $entry->Id ) ? absint( $entry->Id ) : 0;
		}
	}

	if ( $entry_id ) {
		$payload['entry_id'] = (string) $entry_id;
	}

	if ( $entry && ! empty( $entry->date_time ) ) {
		$payload['submission_date'] = $entry->date_time;
	} else {
		$payload['submission_date'] = current_time( 'mysql' );
	}

	$payload['submission_url']   = isset( $_POST['page'] ) ? esc_url_raw( wp_unslash( (string) $_POST['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$payload['submission_title'] = isset( $_POST['nf_page_title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['nf_page_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$payload['user_ip']          = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ip'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$payload['user_agent']       = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '';

	if ( $entry && ! empty( $entry->form_data ) ) {
		$payload = array_merge( $payload, adfoin_nexforms_normalize_entry_fields( $entry->form_data ) );
	} else {
		$payload = array_merge( $payload, adfoin_nexforms_normalize_post_data() );
	}

	return $payload;
}

/**
 * Resolve entry ID from the current request.
 *
 * @return int
 */
function adfoin_nexforms_resolve_entry_id() {
	$candidates = array(
		'nf_set_entry_update_id',
		'nf_entry_redirect_id',
		'nf_entry_id',
	);

	foreach ( $candidates as $candidate ) {
		if ( empty( $_REQUEST[ $candidate ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}

		$value = absint( wp_unslash( $_REQUEST[ $candidate ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $value > 0 ) {
			return $value;
		}
	}

	return 0;
}

/**
 * Fetch Nex-Forms entry by ID.
 *
 * @param int $entry_id Entry ID.
 * @return object|null
 */
function adfoin_nexforms_fetch_entry( $entry_id ) {
	$entry_id = absint( $entry_id );

	if ( $entry_id < 1 ) {
		return null;
	}

	global $wpdb;

	$table = $wpdb->prefix . 'wap_nex_forms_entries';

	if ( ! adfoin_nexforms_table_exists( $table ) ) {
		return null;
	}

	return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE Id = %d",
			$entry_id
		)
	);
}

/**
 * Fetch the most recent entry for a form.
 *
 * @param int $form_id Form ID.
 * @return object|null
 */
function adfoin_nexforms_fetch_latest_entry( $form_id ) {
	$form_id = absint( $form_id );

	if ( $form_id < 1 ) {
		return null;
	}

	global $wpdb;

	$table = $wpdb->prefix . 'wap_nex_forms_entries';

	if ( ! adfoin_nexforms_table_exists( $table ) ) {
		return null;
	}

	return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE nex_forms_Id = %d ORDER BY Id DESC LIMIT 1",
			$form_id
		)
	);
}

/**
 * Normalise stored entry data into a payload array.
 *
 * @param string $form_data_json Stored JSON string.
 * @return array<string,string>
 */
function adfoin_nexforms_normalize_entry_fields( $form_data_json ) {
	$fields = array();
	$data   = json_decode( $form_data_json, true );

	if ( empty( $data ) || ! is_array( $data ) ) {
		return $fields;
	}

	foreach ( $data as $item ) {
		if ( empty( $item['field_name'] ) ) {
			continue;
		}

		$field_key = adfoin_nexforms_normalize_field_key( $item['field_name'] );

		if ( '' === $field_key || adfoin_nexforms_is_excluded_field( $field_key ) ) {
			continue;
		}

		if ( adfoin_fs()->is_not_paying() && ! adfoin_nexforms_field_allowed_in_free( $field_key ) ) {
			continue;
		}

		$value = isset( $item['field_value'] ) ? $item['field_value'] : '';

		$fields[ $field_key ] = adfoin_nexforms_normalize_field_value( $value );
	}

	return $fields;
}

/**
 * Normalise fallback POST payload.
 *
 * @return array<string,string>
 */
function adfoin_nexforms_normalize_post_data() {
	$fields = array();

	foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$field_key = adfoin_nexforms_normalize_field_key( $key );

		if ( '' === $field_key || adfoin_nexforms_is_excluded_field( $field_key ) ) {
			continue;
		}

		if ( adfoin_fs()->is_not_paying() && ! adfoin_nexforms_field_allowed_in_free( $field_key ) ) {
			continue;
		}

		$fields[ $field_key ] = adfoin_nexforms_normalize_field_value( wp_unslash( $value ) );
	}

	return $fields;
}

/**
 * Normalise field values into strings.
 *
 * @param mixed $value Raw field value.
 * @return string
 */
function adfoin_nexforms_normalize_field_value( $value ) {
	if ( is_null( $value ) ) {
		return '';
	}

	if ( is_bool( $value ) ) {
		return $value ? 'Yes' : 'No';
	}

	if ( is_numeric( $value ) && ! is_string( $value ) ) {
		return (string) $value;
	}

	if ( is_object( $value ) ) {
		$value = (array) $value;
	}

	if ( is_array( $value ) ) {
		$normalised = array();

		foreach ( $value as $item ) {
			$item_value = adfoin_nexforms_normalize_field_value( $item );

			if ( '' !== $item_value ) {
				$normalised[] = $item_value;
			}
		}

		return implode( ', ', $normalised );
	}

	$value = (string) $value;
	$value = trim( $value );

	if ( '' === $value ) {
		return '';
	}

	if ( is_serialized( $value ) ) {
		return adfoin_nexforms_normalize_field_value( maybe_unserialize( $value ) );
	}

	$decoded_json = json_decode( $value, true );

	if ( null !== $decoded_json && ( is_array( $decoded_json ) || is_object( $decoded_json ) ) ) {
		return adfoin_nexforms_normalize_field_value( $decoded_json );
	}

	return sanitize_text_field( $value );
}

/**
 * Extract form field definitions from Nex-Forms markup.
 *
 * @param string $html Form markup.
 * @return array<string,string>
 */
function adfoin_nexforms_extract_form_fields( $html ) {
	if ( empty( $html ) ) {
		return array();
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return adfoin_nexforms_extract_form_fields_with_regex( $html );
	}

	$fields = array();

	libxml_use_internal_errors( true );

	$dom    = new DOMDocument();
	$loaded = $dom->loadHTML(
		'<!DOCTYPE html><html><body>' . $html . '</body></html>'
	);

	if ( $loaded ) {
		$xpath    = new DOMXPath( $dom );
		$elements = $xpath->query( '//input | //textarea | //select' );

		if ( $elements ) {
			foreach ( $elements as $element ) {
				if ( ! $element instanceof DOMElement ) {
					continue;
				}

				$name = $element->getAttribute( 'name' );

				if ( '' === $name ) {
					continue;
				}

				$field_key = adfoin_nexforms_normalize_field_key( $name );

				if ( '' === $field_key || isset( $fields[ $field_key ] ) || adfoin_nexforms_is_excluded_field( $field_key ) ) {
					continue;
				}

				$label = adfoin_nexforms_extract_label_from_dom_element( $element, $xpath );

				if ( '' === $label ) {
					$label = adfoin_nexforms_humanize_key( $field_key );
				}

				$label = adfoin_nexforms_sanitise_label( $label );

				if ( '' === $label ) {
					$label = adfoin_nexforms_humanize_key( $field_key );
				}

				$fields[ $field_key ] = $label;
			}
		}
	}

	libxml_clear_errors();
	libxml_use_internal_errors( false );

	if ( empty( $fields ) ) {
		return adfoin_nexforms_extract_form_fields_with_regex( $html );
	}

	return $fields;
}

/**
 * Regex fallback for field extraction.
 *
 * @param string $html Form markup.
 * @return array<string,string>
 */
function adfoin_nexforms_extract_form_fields_with_regex( $html ) {
	$fields = array();

	if ( empty( $html ) ) {
		return $fields;
	}

	if ( ! preg_match_all( '/<(input|textarea|select)\b[^>]*name=(["\'])(.+?)\2[^>]*>/i', $html, $matches, PREG_SET_ORDER ) ) {
		return $fields;
	}

	foreach ( $matches as $match ) {
		$raw_name = html_entity_decode( $match[3], ENT_QUOTES, 'UTF-8' );
		$field    = adfoin_nexforms_normalize_field_key( $raw_name );

		if ( '' === $field || isset( $fields[ $field ] ) || adfoin_nexforms_is_excluded_field( $field ) ) {
			continue;
		}

		$fields[ $field ] = adfoin_nexforms_sanitise_label( adfoin_nexforms_humanize_key( $field ) );
	}

	return $fields;
}

/**
 * Derive a field label from an input DOM element.
 *
 * @param DOMElement $element DOM element.
 * @param DOMXPath   $xpath   Path helper.
 * @return string
 */
function adfoin_nexforms_extract_label_from_dom_element( DOMElement $element, DOMXPath $xpath ) {
	$candidates = array(
		$element->getAttribute( 'data-label' ),
		$element->getAttribute( 'placeholder' ),
		$element->getAttribute( 'aria-label' ),
	);

	foreach ( $candidates as $candidate ) {
		$label = adfoin_nexforms_sanitise_label( $candidate );

		if ( '' !== $label ) {
			return $label;
		}
	}

	$container_nodes = $xpath->query( 'ancestor-or-self::*[contains(concat(" ", normalize-space(@class), " "), " form_field ")]', $element );

	if ( $container_nodes instanceof DOMNodeList && $container_nodes->length > 0 ) {
		$container = $container_nodes->item( 0 );

		$label_node = $xpath->query( './/span[contains(concat(" ", normalize-space(@class), " "), " the_label ")]', $container );

		if ( $label_node instanceof DOMNodeList && $label_node->length > 0 ) {
			$label = adfoin_nexforms_sanitise_label( $label_node->item( 0 )->textContent );

			if ( '' !== $label ) {
				return $label;
			}
		}

		$label_node = $xpath->query( './/label', $container );

		if ( $label_node instanceof DOMNodeList && $label_node->length > 0 ) {
			$label = adfoin_nexforms_sanitise_label( $label_node->item( 0 )->textContent );

			if ( '' !== $label ) {
				return $label;
			}
		}
	}

	if ( $element->hasAttribute( 'name' ) ) {
		$field_key = adfoin_nexforms_normalize_field_key( $element->getAttribute( 'name' ) );

		if ( '' !== $field_key ) {
			$label = adfoin_nexforms_sanitise_label( adfoin_nexforms_humanize_key( $field_key ) );

			if ( '' !== $label ) {
				return $label;
			}
		}
	}

	return '';
}

/**
 * Sanitise extracted labels.
 *
 * @param string $label Raw label.
 * @return string
 */
function adfoin_nexforms_sanitise_label( $label ) {
	if ( null === $label ) {
		return '';
	}

	$label = wp_strip_all_tags( (string) $label, true );
	$label = trim( preg_replace( '/\s+/', ' ', $label ) );
	$label = ltrim( $label, "* \t\n\r\0\x0B" );

	return $label;
}

/**
 * Convert a field key into a readable label.
 *
 * @param string $field Field key.
 * @return string
 */
function adfoin_nexforms_humanize_key( $field ) {
	$field = (string) $field;
	$field = trim( str_replace( array( '_', '-' ), ' ', $field ) );
	$field = preg_replace( '/\s+/', ' ', $field );

	return ucwords( $field );
}

/**
 * Normalise Nex-Forms field keys.
 *
 * @param string $name Raw field name.
 * @return string
 */
function adfoin_nexforms_normalize_field_key( $name ) {
	$name = html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' );
	$name = trim( $name );

	if ( '' === $name ) {
		return '';
	}

	$bracket_pos = strpos( $name, '[' );

	if ( false !== $bracket_pos ) {
		$name = substr( $name, 0, $bracket_pos );
	}

	return rtrim( $name, '[]' );
}

/**
 * Determine whether a field should be excluded from mapping.
 *
 * @param string $field_key Field key.
 * @return bool
 */
function adfoin_nexforms_is_excluded_field( $field_key ) {
	$field_key = strtolower( (string) $field_key );

	if ( '' === $field_key ) {
		return true;
	}

	if ( 0 === strpos( $field_key, 'real_val__' ) || 0 === strpos( $field_key, 'gu__' ) ) {
		return true;
	}

	return in_array( $field_key, adfoin_nexforms_get_excluded_keys(), true );
}

/**
 * Keys that should not be exposed or processed.
 *
 * @return array<int,string>
 */
function adfoin_nexforms_get_excluded_keys() {
	$keys = array(
		'action',
		'ajaxurl',
		'company_url',
		'current_page',
		'format_date',
		'hs_form_guid',
		'hs_portal_id',
		'ip',
		'math_result',
		'ms_current_step',
		'multi_step_name',
		'nf_entry_id',
		'nf_entry_redirect_id',
		'nf_entry_update_id',
		'nf_page_id',
		'nf_page_title',
		'nf_preview_id',
		'nf_set_entry_update_id',
		'nex_forms_id',
		'nex_forms_Id',
		'page',
		'page_id',
		'paypal_invoice',
		'paypal_return_url',
		'required',
		'set_autocomplete_items',
		'set_check_items',
		'set_file_ext',
		'set_radio_items',
		'submit',
		'xform_submit',
	);

	return array_unique( $keys );
}

/**
 * Determine whether a field is available in the free plan.
 *
 * @param string $field_key Field key.
 * @return bool
 */
function adfoin_nexforms_field_allowed_in_free( $field_key ) {
	$field_key = strtolower( (string) $field_key );

	$allowed_fragments = array( 'name', 'email', 'first', 'last' );

	foreach ( $allowed_fragments as $fragment ) {
		if ( false !== strpos( $field_key, $fragment ) ) {
			return true;
		}
	}

	return false;
}
