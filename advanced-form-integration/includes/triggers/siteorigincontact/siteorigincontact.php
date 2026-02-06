<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve SiteOrigin Contact Form entries.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_siteorigincontact_get_forms( $form_provider ) {
	if ( 'siteorigincontact' !== $form_provider || ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
		return array();
	}

	$instances = adfoin_siteorigincontact_collect_instances();

	if ( empty( $instances ) ) {
		return array();
	}

	$forms = array();

	foreach ( $instances as $hash => $instance ) {
		$forms[ $hash ] = adfoin_siteorigincontact_get_form_label( $hash, $instance );
	}

	asort( $forms, SORT_NATURAL | SORT_FLAG_CASE );

	return $forms;
}

/**
 * Retrieve SiteOrigin Contact Form fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form hash.
 * @return array<string,string>|void
 */
function adfoin_siteorigincontact_get_form_fields( $form_provider, $form_id ) {
	if ( 'siteorigincontact' !== $form_provider || empty( $form_id ) ) {
		return array();
	}

	$instance = adfoin_siteorigincontact_get_instance_by_hash( $form_id );

	if ( empty( $instance ) ) {
		return array();
	}

	$field_labels = adfoin_siteorigincontact_generate_field_labels( $instance );

	$fields = array(
		'form_hash'             => __( 'Form Hash', 'advanced-form-integration' ),
		'form_title'            => __( 'Form Title', 'advanced-form-integration' ),
		'submission_timestamp'  => __( 'Submission Timestamp', 'advanced-form-integration' ),
		'submission_datetime'   => __( 'Submission DateTime', 'advanced-form-integration' ),
		'submission_url'        => __( 'Submission URL', 'advanced-form-integration' ),
		'user_ip'               => __( 'User IP', 'advanced-form-integration' ),
		'user_agent'            => __( 'User Agent', 'advanced-form-integration' ),
		'email_to'              => __( 'Notification Email (To)', 'advanced-form-integration' ),
		'email_from'            => __( 'Notification Email (From)', 'advanced-form-integration' ),
	);

	foreach ( $field_labels as $field_key => $label ) {
		$fields[ $field_key ] = $label;
		$fields[ $field_key . '_label' ] = sprintf( __( '%s (Label)', 'advanced-form-integration' ), $label );
	}

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

add_action( 'siteorigin_widgets_contact_sent', 'adfoin_siteorigincontact_handle_submission', 10, 2 );

/**
 * Handle SiteOrigin Contact Form submissions.
 *
 * @param array $instance     Widget instance data.
 * @param array $email_fields Prepared email fields.
 * @return void
 */
function adfoin_siteorigincontact_handle_submission( $instance, $email_fields ) {
	if ( empty( $instance ) || ! is_array( $instance ) ) {
		return;
	}

	$form_hash = adfoin_siteorigincontact_get_submitted_hash();

	if ( empty( $form_hash ) ) {
		return;
	}

	adfoin_siteorigincontact_cache_instance( $form_hash, $instance );

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'siteorigincontact', $form_hash );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_siteorigincontact_prepare_payload( $instance, $form_hash );

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
 * Prepare payload array to send via AFI.
 *
 * @param array  $instance  Widget instance.
 * @param string $form_hash Hash identifier.
 * @return array<string,mixed>
 */
function adfoin_siteorigincontact_prepare_payload( $instance, $form_hash ) {
	$post_vars = wp_unslash( $_POST );

	$fields = isset( $instance['fields'] ) && is_array( $instance['fields'] ) ? $instance['fields'] : array();
	$fields = apply_filters( 'siteorigin_widgets_contact_fields', $fields );

	$widget = adfoin_siteorigincontact_widget();
	$payload_fields = array();

	$field_index = 0;

	foreach ( $fields as $field ) {
		if ( empty( $field['type'] ) ) {
			continue;
		}

		$label = ! empty( $field['label'] ) ? $field['label'] : 'field_' . $field_index;
		$field_index++;

		$field_name = $widget ? $widget->name_from_label( $label, $post_vars ) : adfoin_siteorigincontact_fallback_field_name( $label );

		if ( empty( $field_name ) ) {
			continue;
		}

		$value = isset( $post_vars[ $field_name ] ) ? $post_vars[ $field_name ] : '';
		$value = adfoin_siteorigincontact_normalize_value( $value );

		$payload_fields[ $field_name ] = $value;
		$payload_fields[ $field_name . '_label' ] = $label;
	}

	$payload = array(
		'form_hash'            => $form_hash,
		'form_title'           => adfoin_siteorigincontact_get_form_label( $form_hash, $instance ),
		'form_submit_text'     => isset( $instance['settings']['submit_text'] ) ? $instance['settings']['submit_text'] : '',
		'email_to'             => isset( $instance['settings']['to'] ) ? $instance['settings']['to'] : '',
		'email_from'           => isset( $instance['settings']['from'] ) ? $instance['settings']['from'] : '',
		'submission_timestamp' => time(),
		'submission_datetime'  => current_time( 'mysql' ),
		'submission_url'       => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		'user_ip'              => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'user_agent'           => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
	);

	return array_merge( $payload, $payload_fields );
}

/**
 * Normalize incoming field values.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function adfoin_siteorigincontact_normalize_value( $value ) {
	if ( is_array( $value ) ) {
		$value = implode(
			', ',
			array_map(
				static function( $single ) {
					return is_scalar( $single ) ? sanitize_text_field( $single ) : '';
				},
				$value
			)
		);
	} elseif ( is_string( $value ) ) {
		$value = sanitize_textarea_field( $value );
	} else {
		$value = '';
	}

	return $value;
}

/**
 * Retrieve submitted hash from POST data.
 *
 * @return string
 */
function adfoin_siteorigincontact_get_submitted_hash() {
	if ( empty( $_POST ) ) {
		return '';
	}

	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, 'instance_hash' ) === 0 ) {
			return sanitize_text_field( wp_unslash( $value ) );
		}
	}

	return '';
}

/**
 * Generate field labels from stored instance.
 *
 * @param array $instance Widget instance.
 * @return array<string,string>
 */
function adfoin_siteorigincontact_generate_field_labels( $instance ) {
	$fields = isset( $instance['fields'] ) && is_array( $instance['fields'] ) ? $instance['fields'] : array();

	if ( empty( $fields ) ) {
		return array();
	}

	$widget = adfoin_siteorigincontact_widget();
	global $field_ids;
	$field_ids = array();

	$labels = array();
	$index  = 0;

	foreach ( $fields as $field ) {
		if ( empty( $field['type'] ) ) {
			continue;
		}

		$label = ! empty( $field['label'] ) ? $field['label'] : sprintf( __( 'Field %d', 'advanced-form-integration' ), $index + 1 );
		$name  = $widget ? $widget->name_from_label( $label ) : adfoin_siteorigincontact_fallback_field_name( $label, $index );
		$labels[ $name ] = $label;
		$index++;
	}

	return $labels;
}

/**
 * Resolve human-readable form label.
 *
 * @param string $hash     Form hash.
 * @param array  $instance Stored instance.
 * @return string
 */
function adfoin_siteorigincontact_get_form_label( $hash, $instance ) {
	if ( ! empty( $instance['title'] ) ) {
		return $instance['title'];
	}

	if ( ! empty( $instance['settings']['submit_text'] ) ) {
		return sprintf(
			__( 'Contact Form (%1$s) - %2$s', 'advanced-form-integration' ),
			$hash,
			$instance['settings']['submit_text']
		);
	}

	return sprintf( __( 'Contact Form (%s)', 'advanced-form-integration' ), $hash );
}

/**
 * Cache widget instance for later lookups.
 *
 * @param string $hash     Form hash.
 * @param array  $instance Instance data.
 * @return void
 */
function adfoin_siteorigincontact_cache_instance( $hash, $instance ) {
	if ( empty( $hash ) || empty( $instance ) || ! is_array( $instance ) ) {
		return;
	}

	$instance = adfoin_siteorigincontact_sanitize_instance( $instance );

	$stored = get_option( 'adfoin_siteorigincontact_instances', array() );
	$stored[ $hash ] = array(
		'instance'   => $instance,
		'updated_at' => time(),
	);

	if ( count( $stored ) > 100 ) {
		$stored = array_slice( $stored, -100, null, true );
	}

	update_option( 'adfoin_siteorigincontact_instances', $stored, false );
}

/**
 * Retrieve cached instances from option + transients.
 *
 * @return array<string,array>
 */
function adfoin_siteorigincontact_collect_instances() {
	$instances = array();

	$cached = get_option( 'adfoin_siteorigincontact_instances', array() );

	if ( is_array( $cached ) ) {
		foreach ( $cached as $hash => $data ) {
			if ( ! empty( $data['instance'] ) && is_array( $data['instance'] ) ) {
				$instances[ $hash ] = $data['instance'];
			}
		}
	}

	$hashes = adfoin_siteorigincontact_get_transient_hashes();

	foreach ( $hashes as $hash ) {
		if ( isset( $instances[ $hash ] ) ) {
			continue;
		}

		$transient_instance = adfoin_siteorigincontact_get_instance_by_hash( $hash );

		if ( ! empty( $transient_instance ) ) {
			$instances[ $hash ] = $transient_instance;
			adfoin_siteorigincontact_cache_instance( $hash, $transient_instance );
		}
	}

	return $instances;
}

/**
 * Fetch stored instance by hash.
 *
 * @param string $hash Form hash.
 * @return array<string,mixed>|array
 */
function adfoin_siteorigincontact_get_instance_by_hash( $hash ) {
	if ( empty( $hash ) ) {
		return array();
	}

	$widget = adfoin_siteorigincontact_widget();

	if ( $widget && method_exists( $widget, 'get_stored_instance' ) ) {
		$instance = $widget->get_stored_instance( $hash );
		if ( ! empty( $instance ) && is_array( $instance ) ) {
			return $instance;
		}
	}

	$cached = get_option( 'adfoin_siteorigincontact_instances', array() );

	if ( isset( $cached[ $hash ]['instance'] ) && is_array( $cached[ $hash ]['instance'] ) ) {
		return $cached[ $hash ]['instance'];
	}

	return array();
}

/**
 * Retrieve transients hashes from the database.
 *
 * @return array<int,string>
 */
function adfoin_siteorigincontact_get_transient_hashes() {
	global $wpdb;

	if ( empty( $wpdb ) ) {
		return array();
	}

	$hashes  = array();
	$patterns = array(
		'_transient_sow_inst[sow-contact-form][',
		'_site_transient_sow_inst[sow-contact-form][',
	);

	foreach ( $patterns as $pattern ) {
		$like = $wpdb->esc_like( $pattern ) . '%';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);

		if ( empty( $results ) ) {
			continue;
		}

		foreach ( $results as $name ) {
			if ( preg_match( '/sow_inst\[sow-contact-form]\[([^\]]+)\]/', $name, $match ) ) {
				$hashes[] = $match[1];
			}
		}
	}

	return array_unique( $hashes );
}

/**
 * Sanitize instance array for storage.
 *
 * @param array $instance Raw instance.
 * @return array
 */
function adfoin_siteorigincontact_sanitize_instance( $instance ) {
	unset( $instance['form_id'] );
	unset( $instance['_sow_form_id'] );

	return $instance;
}

/**
 * Singleton accessor for widget class.
 *
 * @return SiteOrigin_Widgets_ContactForm_Widget
 */
function adfoin_siteorigincontact_widget() {
	static $widget = null;

	if ( null === $widget ) {
		if ( class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
			$widget = new SiteOrigin_Widgets_ContactForm_Widget();
		} else {
			return null;
		}
	}

	return $widget;
}

/**
 * Fallback field name slug generator.
 *
 * @param string   $label Field label.
 * @param int|null $index Optional index.
 * @return string
 */
function adfoin_siteorigincontact_fallback_field_name( $label, $index = null ) {
	$base  = sanitize_title( $label );
	$suffix = is_null( $index ) ? '' : '-' . (int) $index;

	return $base . $suffix;
}
