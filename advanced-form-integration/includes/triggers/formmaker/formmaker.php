<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide Form Maker form list.
 *
 * @param string $form_provider Current provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_formmaker_get_forms( $form_provider ) {
    if ( 'formmaker' !== $form_provider ) {
        return;
    }

    global $wpdb;

    $table = "{$wpdb->prefix}formmaker";

    if ( ! adfoin_formmaker_table_exists( $table ) ) {
        return array();
    }

    $forms = $wpdb->get_results(
        "SELECT id, title FROM {$table} ORDER BY title ASC",
        ARRAY_A
    );

    if ( empty( $forms ) ) {
        return array();
    }

    $formatted = array();

    foreach ( $forms as $form ) {
        $form_id = isset( $form['id'] ) ? (string) $form['id'] : '';
        if ( '' === $form_id ) {
            continue;
        }

        $title = isset( $form['title'] ) ? wp_strip_all_tags( $form['title'] ) : '';

        if ( '' === $title ) {
            $title = sprintf(
                /* translators: %s - form ID */
                __( 'Form %s', 'advanced-form-integration' ),
                $form_id
            );
        }

        $formatted[ $form_id ] = $title;
    }

    return $formatted;
}

/**
 * Provide Form Maker field map for a form.
 *
 * @param string $form_provider Current provider key.
 * @param string $form_id       Selected form identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_formmaker_get_form_fields( $form_provider, $form_id ) {
    if ( 'formmaker' !== $form_provider ) {
        return;
    }

    $form_id = absint( $form_id );

    if ( ! $form_id ) {
        return array();
    }

    $form_data  = adfoin_formmaker_get_form_data( $form_id );
    $field_defs = isset( $form_data['fields'] ) ? (array) $form_data['fields'] : array();

    $fields = array(
        'form_id'        => __( 'Form ID', 'advanced-form-integration' ),
        'form_title'     => __( 'Form Title', 'advanced-form-integration' )
    );

    foreach ( $field_defs as $field_id => $definition ) {
        $field_key = 'field_' . $field_id;
        $label     = isset( $definition['label'] ) ? $definition['label'] : '';

        $fields[ $field_key ] = $label ? $label : sprintf(
            /* translators: %s - field ID */
            __( 'Field %s', 'advanced-form-integration' ),
            $field_id
        );

        if ( isset( $definition['type'] ) && 'type_file_upload' === $definition['type'] ) {
            $fields[ "{$field_key}_link" ] = sprintf(
                /* translators: %s - field label */
                __( '%s (File URL)', 'advanced-form-integration' ),
                $fields[ $field_key ]
            );
        }
    }

    $special_tags = adfoin_get_special_tags();

    if ( is_array( $special_tags ) && ! empty( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

/**
 * Provide the form title.
 *
 * @param string $form_provider Current provider key.
 * @param string $form_id       Form identifier.
 *
 * @return string|void
 */
function adfoin_formmaker_get_form_name( $form_provider, $form_id ) {
    if ( 'formmaker' !== $form_provider ) {
        return;
    }

    $form_id = absint( $form_id );

    if ( ! $form_id ) {
        return '';
    }

    $form_data = adfoin_formmaker_get_form_data( $form_id );

    return isset( $form_data['title'] ) ? $form_data['title'] : '';
}

add_action( 'fm_addon_frontend_init', 'adfoin_formmaker_handle_submission', 10, 1 );

/**
 * Handle Form Maker submissions.
 *
 * @param array<string,mixed> $params Submission payload.
 *
 * @return void
 */
function adfoin_formmaker_handle_submission( $params ) {
    if ( ! is_array( $params ) ) {
        return;
    }

    $form_id = isset( $params['form_id'] ) ? absint( $params['form_id'] ) : 0;

    if ( ! $form_id ) {
        return;
    }

    if ( isset( $params['intent_action'] ) && 'cancel' === $params['intent_action'] ) {
        return;
    }

    $custom_fields = isset( $params['custom_fields'] ) ? (array) $params['custom_fields'] : array();
    $submission_id = isset( $custom_fields['subid'] ) ? absint( $custom_fields['subid'] ) : 0;

    if ( ! $submission_id && empty( $params['fvals'] ) ) {
        return;
    }

    static $processed = array();

    $fingerprint = $form_id . ':' . ( $submission_id ? $submission_id : md5( wp_json_encode( $params['fvals'] ?? array() ) ) );

    if ( isset( $processed[ $fingerprint ] ) ) {
        return;
    }

    $processed[ $fingerprint ] = true;

    if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'formmaker', (string) $form_id );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = adfoin_formmaker_build_payload( $form_id, $params );

    if ( empty( $payload ) ) {
        return;
    }

    global $post;

    $special_tag_values = adfoin_get_special_tags_values( $post );

    if ( is_array( $special_tag_values ) && ! empty( $special_tag_values ) ) {
        $payload = $payload + $special_tag_values;
    }

    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];

        if ( $job_queue ) {
            as_enqueue_async_action(
                "adfoin_{$action_provider}_job_queue",
                array(
                    'data' => array(
                        'record'      => $record,
                        'posted_data' => $payload,
                    ),
                )
            );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $payload );
        }
    }
}

/**
 * Build payload from submission parameters.
 *
 * @param int                   $form_id Form identifier.
 * @param array<string,mixed>   $params  Submission parameters.
 *
 * @return array<string,string>
 */
function adfoin_formmaker_build_payload( $form_id, $params ) {
    $form_data  = adfoin_formmaker_get_form_data( $form_id );
    $field_defs = isset( $form_data['fields'] ) ? (array) $form_data['fields'] : array();

    $custom_fields = isset( $params['custom_fields'] ) ? (array) $params['custom_fields'] : array();
    $submission_id = isset( $custom_fields['subid'] ) ? absint( $custom_fields['subid'] ) : 0;

    $payload = array(
        'form_id'        => (string) $form_id,
        'form_title'     => adfoin_formmaker_normalize_value( $form_data['title'] ?? ( $params['form_title'] ?? '' ) )
    );

    $submission_values = array();

    if ( $submission_id ) {
        $submission_values = adfoin_formmaker_fetch_submission_values( $form_id, $submission_id, $field_defs );
    }

    if ( empty( $submission_values ) && ! empty( $params['fvals'] ) ) {
        $submission_values = adfoin_formmaker_convert_fvals( (array) $params['fvals'] );
    } else {
        $fallback_values = adfoin_formmaker_convert_fvals( (array) ( $params['fvals'] ?? array() ) );
        foreach ( $fallback_values as $key => $value ) {
            if ( ! isset( $submission_values[ $key ] ) || '' === $submission_values[ $key ] ) {
                $submission_values[ $key ] = $value;
            }
        }
    }

    foreach ( $submission_values as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $payload[ $key ] = adfoin_formmaker_normalize_value( $value );
    }

    return array_map( 'adfoin_formmaker_normalize_value', $payload );
}

/**
 * Fetch stored submission rows.
 *
 * @param int                   $form_id       Form identifier.
 * @param int                   $submission_id Submission identifier.
 * @param array<string,array>   $field_defs    Field definitions.
 *
 * @return array<string,string>
 */
function adfoin_formmaker_fetch_submission_values( $form_id, $submission_id, $field_defs ) {
    global $wpdb;

    $table = "{$wpdb->prefix}formmaker_submits";

    if ( ! adfoin_formmaker_table_exists( $table ) ) {
        return array();
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT element_label, element_value FROM {$table} WHERE form_id = %d AND group_id = %d",
            $form_id,
            $submission_id
        ),
        ARRAY_A
    );

    if ( empty( $rows ) ) {
        return array();
    }

    $values = array();

    foreach ( $rows as $row ) {
        $label = isset( $row['element_label'] ) ? (string) $row['element_label'] : '';
        if ( '' === $label ) {
            continue;
        }

        $value = isset( $row['element_value'] ) ? $row['element_value'] : '';
        $value = adfoin_formmaker_clean_submission_value( $value );

        if ( is_numeric( $label ) ) {
            $key = 'field_' . $label;
            $values[ $key ] = $value;

            if ( isset( $field_defs[ $label ]['type'] ) && 'type_file_upload' === $field_defs[ $label ]['type'] ) {
                $values[ "{$key}_link" ] = $value;
            }
            continue;
        }

        $normalized_key = sanitize_key( $label );

        if ( '' !== $normalized_key ) {
            $values[ $normalized_key ] = $value;
        }
    }

    return $values;
}

/**
 * Convert Form Maker placeholder values to payload keys.
 *
 * @param array<string,mixed> $fvals Placeholder values.
 *
 * @return array<string,string>
 */
function adfoin_formmaker_convert_fvals( $fvals ) {
    $converted = array();

    foreach ( $fvals as $raw_key => $value ) {
        if ( ! is_string( $raw_key ) || '' === $raw_key ) {
            continue;
        }

        $clean_value = adfoin_formmaker_clean_submission_value( $value );

        if ( preg_match( '/^\{(\d+)\}$/', $raw_key, $matches ) ) {
            $converted[ 'field_' . $matches[1] ] = $clean_value;
            continue;
        }

        if ( preg_match( '/^\{(\d+)\((.+)\)\}$/', $raw_key, $matches ) ) {
            $suffix = sanitize_key( $matches[2] );
            $converted[ 'field_' . $matches[1] . '_' . $suffix ] = $clean_value;
            continue;
        }

        $normalized_key = sanitize_key( trim( $raw_key, '{}' ) );

        if ( '' !== $normalized_key ) {
            $converted[ $normalized_key ] = $clean_value;
        }
    }

    return $converted;
}

/**
 * Clean stored submission value.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function adfoin_formmaker_clean_submission_value( $value ) {
    if ( is_array( $value ) || is_object( $value ) ) {
        $encoded = wp_json_encode( $value );
        return is_string( $encoded ) ? $encoded : '';
    }

    $value = wp_unslash( $value );

    if ( ! is_string( $value ) ) {
        return (string) $value;
    }

    return str_replace(
        array( '***map***', '*@@url@@*', '@@@@@@@@@', '@@@', '***grading***', '***br***' ),
        array( ' ', '', ' ', ' ', ' ', ', ' ),
        $value
    );
}

/**
 * Normalize value for payload transport.
 *
 * @param mixed $value Value.
 *
 * @return string
 */
function adfoin_formmaker_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        $value = wp_unslash( $value );
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Retrieve cached form metadata.
 *
 * @param int $form_id Form identifier.
 *
 * @return array<string,mixed>
 */
function adfoin_formmaker_get_form_data( $form_id ) {
    static $cache = array();

    if ( isset( $cache[ $form_id ] ) ) {
        return $cache[ $form_id ];
    }

    if ( ! $form_id ) {
        $cache[ $form_id ] = array();
        return $cache[ $form_id ];
    }

    global $wpdb;

    $table = "{$wpdb->prefix}formmaker";

    if ( ! adfoin_formmaker_table_exists( $table ) ) {
        $cache[ $form_id ] = array();
        return $cache[ $form_id ];
    }

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, title, form_fields FROM {$table} WHERE id = %d LIMIT 1",
            $form_id
        )
    );

    if ( ! $row ) {
        $cache[ $form_id ] = array();
        return $cache[ $form_id ];
    }

    if ( is_object( $row ) && class_exists( 'WDW_FM_Library' ) ) {
        $row = WDW_FM_Library::convert_json_options_to_old( $row, 'form_fields' );
        $row = WDW_FM_Library::convert_json_options_to_old( $row, 'title' );
    }

    $title       = '';
    $form_fields = '';

    if ( is_object( $row ) ) {
        $title       = isset( $row->title ) ? $row->title : '';
        $form_fields = isset( $row->form_fields ) ? $row->form_fields : '';
    } elseif ( is_array( $row ) ) {
        $title       = isset( $row['title'] ) ? $row['title'] : '';
        $form_fields = isset( $row['form_fields'] ) ? $row['form_fields'] : '';
    }

    $title = wp_strip_all_tags( (string) $title );

    $fields = adfoin_formmaker_extract_fields( $form_fields );

    $cache[ $form_id ] = array(
        'title'  => $title,
        'fields' => $fields,
    );

    return $cache[ $form_id ];
}

/**
 * Parse raw form field definition string.
 *
 * @param string $form_fields Raw field definition payload.
 *
 * @return array<string,array<string,string>>
 */
function adfoin_formmaker_extract_fields( $form_fields ) {
    $parsed = array();

    if ( empty( $form_fields ) || ! is_string( $form_fields ) ) {
        return $parsed;
    }

    $chunks = explode( '*:*new_field*:*', $form_fields );

    foreach ( $chunks as $chunk ) {
        if ( '' === trim( $chunk ) ) {
            continue;
        }

        $id_split = explode( '*:*id*:*', $chunk );

        if ( count( $id_split ) < 2 ) {
            continue;
        }

        $field_id = trim( $id_split[0] );

        if ( '' === $field_id ) {
            continue;
        }

        $after_id = $id_split[1];

        $type_split = explode( '*:*type*:*', $after_id );

        if ( count( $type_split ) < 2 ) {
            continue;
        }

        $field_type = trim( $type_split[0] );
        $after_type = $type_split[1];

        $label_split = explode( '*:*w_field_label*:*', $after_type );

        if ( count( $label_split ) < 2 ) {
            continue;
        }

        $label_raw = $label_split[0];
        $label     = trim( wp_strip_all_tags( html_entity_decode( wp_unslash( $label_raw ) ) ) );

        if ( '' === $label ) {
            $label = sprintf(
                /* translators: %s - field ID */
                __( 'Field %s', 'advanced-form-integration' ),
                $field_id
            );
        }

        $parsed[ $field_id ] = array(
            'label' => $label,
            'type'  => $field_type,
        );
    }

    return $parsed;
}

/**
 * Cached table existence lookup.
 *
 * @param string $table Fully qualified table name.
 *
 * @return bool
 */
function adfoin_formmaker_table_exists( $table ) {
    static $cache = array();

    if ( isset( $cache[ $table ] ) ) {
        return $cache[ $table ];
    }

    global $wpdb;

    $result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

    $cache[ $table ] = ( $result === $table );

    return $cache[ $table ];
}

