<?php

/**
 * Breakdance trigger handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve Breakdance forms.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_breakdance_get_forms( $form_provider ) {
    if ( 'breakdance' !== $form_provider ) {
        return;
    }

    if ( ! function_exists( '\\Breakdance\\Data\\get_tree' ) ) {
        return array();
    }

    $posts = get_posts(
        array(
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'meta_key'       => '_breakdance_data',
            'fields'         => 'ids',
        )
    );

    if ( empty( $posts ) ) {
        return array();
    }

    $forms = array();

    foreach ( $posts as $post_id ) {
        $tree = \Breakdance\Data\get_tree( $post_id );

        if ( empty( $tree['root']['children'] ) ) {
            continue;
        }

        adfoin_breakdance_collect_forms_from_tree( $tree['root']['children'], $post_id, $forms );
    }

    if ( empty( $forms ) ) {
        return array();
    }

    $form_list = array();

    foreach ( $forms as $form ) {
        $key   = $form['post_id'] . '_' . $form['element_id'];
        $title = get_the_title( $form['post_id'] );

        if ( ! $title ) {
            /* translators: %d: post ID */
            $title = sprintf( __( 'Post #%d', 'advanced-form-integration' ), $form['post_id'] );
        }

        $name = trim( $form['name'] );

        if ( '' === $name ) {
            /* translators: %d: form element ID */
            $name = sprintf( __( 'Form #%d', 'advanced-form-integration' ), $form['element_id'] );
        }

        $form_list[ $key ] = $title . ' - ' . $name;
    }

    return $form_list;
}

/**
 * Traverse Breakdance tree and collect form modules.
 *
 * @param array<int,array<string,mixed>> $nodes   Tree nodes.
 * @param int                            $post_id Post ID.
 * @param array<int,array<string,mixed>> $forms   Reference accumulator.
 *
 * @return void
 */
function adfoin_breakdance_collect_forms_from_tree( $nodes, $post_id, &$forms ) {
    if ( empty( $nodes ) || ! is_array( $nodes ) ) {
        return;
    }

    foreach ( $nodes as $node ) {
        if ( ! is_array( $node ) ) {
            continue;
        }

        $type = $node['data']['type'] ?? '';

        if ( 'EssentialElements\\FormBuilder' === $type ) {
            $name = '';

            if ( isset( $node['data']['properties']['content']['form']['form_name'] ) ) {
                $raw_name = $node['data']['properties']['content']['form']['form_name'];
                if ( is_string( $raw_name ) ) {
                    $name = $raw_name;
                }
            }

            $forms[] = array(
                'post_id'    => $post_id,
                'element_id' => (int) $node['id'],
                'name'       => $name,
            );
        }

        if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
            adfoin_breakdance_collect_forms_from_tree( $node['children'], $post_id, $forms );
        }
    }
}

/**
 * Parse stored form identifier.
 *
 * @param string|int $form_id Stored identifier.
 *
 * @return array{post_id:int,element_id:int}|null
 */
function adfoin_breakdance_parse_form_identifier( $form_id ) {
    $parts = explode( '_', (string) $form_id );

    if ( 2 !== count( $parts ) ) {
        return null;
    }

    $post_id    = absint( $parts[0] );
    $element_id = absint( $parts[1] );

    if ( ! $post_id || ! $element_id ) {
        return null;
    }

    return array(
        'post_id'    => $post_id,
        'element_id' => $element_id,
    );
}

/**
 * Retrieve Breakdance form fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Stored identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_breakdance_get_form_fields( $form_provider, $form_id ) {
    if ( 'breakdance' !== $form_provider ) {
        return;
    }

    $parsed = adfoin_breakdance_parse_form_identifier( $form_id );

    if ( ! $parsed ) {
        return array();
    }

    if ( ! function_exists( '\\Breakdance\\Forms\\getFormSettings' ) ) {
        return array();
    }

    $settings = \Breakdance\Forms\getFormSettings( $parsed['post_id'], $parsed['element_id'] );

    if ( ! $settings || empty( $settings['form']['fields'] ) ) {
        return array();
    }

    $fields = array();

    foreach ( $settings['form']['fields'] as $field ) {
        $field = is_array( $field ) ? $field : (array) $field;

        $field_id = $field['advanced']['id'] ?? '';

        if ( ! $field_id ) {
            continue;
        }

        $type  = $field['type'] ?? '';
        $label = $field['label'] ?? $field_id;

        if ( in_array( $type, array( 'html', 'step' ), true ) ) {
            continue;
        }

        if ( adfoin_fs()->is_not_paying() ) {
            if ( ! in_array( $type, array( 'text', 'email' ), true ) ) {
                continue;
            }
        }

        $fields[ $field_id ] = $label;
    }

    if ( empty( $fields ) ) {
        return array();
    }

    $fields['form_id']         = __( 'Form ID', 'advanced-form-integration' );
    $fields['form_name']       = __( 'Form Name', 'advanced-form-integration' );
    $fields['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );
    $fields['user_ip']         = __( 'User IP', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if ( is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

/**
 * Retrieve Breakdance form name.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Stored identifier.
 *
 * @return string|void
 */
function adfoin_breakdance_get_form_name( $form_provider, $form_id ) {
    if ( 'breakdance' !== $form_provider ) {
        return;
    }

    $parsed = adfoin_breakdance_parse_form_identifier( $form_id );

    if ( ! $parsed ) {
        return '';
    }

    if ( function_exists( '\\Breakdance\\Forms\\getFormSettings' ) ) {
        $settings = \Breakdance\Forms\getFormSettings( $parsed['post_id'], $parsed['element_id'] );

        if ( $settings && ! empty( $settings['form']['form_name'] ) ) {
            return $settings['form']['form_name'];
        }
    }

    $title = get_the_title( $parsed['post_id'] );

    return $title ? $title : '';
}

/**
 * Handle Breakdance form submissions.
 *
 * @return void
 */
function adfoin_breakdance_submission() {
    $post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $element_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( ! $post_id || ! $element_id ) {
        return;
    }

    $form_identifier = $post_id . '_' . $element_id;

    global $wpdb;

    $saved_records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = %s AND form_id = %s",
            'breakdance',
            $form_identifier
        ),
        ARRAY_A
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $field_values = array();

    if ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $field_values = wp_unslash( $_POST['fields'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    if ( ! function_exists( '\\Breakdance\\Forms\\getFormSettings' ) ) {
        return;
    }

    $settings = \Breakdance\Forms\getFormSettings( $post_id, $element_id );

    if ( ! $settings || empty( $settings['form']['fields'] ) ) {
        return;
    }

    $field_map = array();

    foreach ( $settings['form']['fields'] as $field ) {
        $field = is_array( $field ) ? $field : (array) $field;

        $field_id = $field['advanced']['id'] ?? '';

        if ( ! $field_id ) {
            continue;
        }

        $field_map[ $field_id ] = array(
            'label' => $field['label'] ?? $field_id,
            'type'  => $field['type'] ?? '',
        );
    }

    $posted_data = array();

    foreach ( $field_values as $field_id => $value ) {
        $field_id = sanitize_key( $field_id );

        if ( '' === $field_id || in_array( $field_id, array( 'hpname', 'csrfToken' ), true ) ) {
            continue;
        }

        $field_meta = isset( $field_map[ $field_id ] ) ? $field_map[ $field_id ] : array();
        $field_type = $field_meta['type'] ?? '';

        if ( adfoin_fs()->is_not_paying() ) {
            if ( ! in_array( $field_type, array( 'text', 'email' ), true ) ) {
                continue;
            }
        }

        $normalized = adfoin_breakdance_normalize_value( $value );

        if ( '' === $normalized ) {
            continue;
        }

        $posted_data[ $field_id ] = $normalized;
    }

    if ( empty( $posted_data ) ) {
        return;
    }

    $posted_data['form_id']         = $form_identifier;
    $posted_data['form_name']       = $settings['form']['form_name'] ?? get_the_title( $post_id );
    $posted_data['submission_date'] = current_time( 'mysql' );
    $posted_data['user_ip']         = adfoin_get_user_ip();

    $post = get_post( $post_id );

    if ( $post instanceof WP_Post ) {
        $special_tag_values = adfoin_get_special_tags_values( $post );

        if ( is_array( $special_tag_values ) ) {
            $posted_data = $posted_data + $special_tag_values;
        }
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
                        'posted_data' => $posted_data,
                    ),
                )
            );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

/**
 * Normalize submitted values.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function adfoin_breakdance_normalize_value( $value ) {
    if ( is_array( $value ) ) {
        $normalized = array();

        foreach ( $value as $item ) {
            $item_value = adfoin_breakdance_normalize_value( $item );

            if ( '' !== $item_value ) {
                $normalized[] = $item_value;
            }
        }

        return implode( ', ', $normalized );
    }

    if ( is_bool( $value ) ) {
        return $value ? '1' : '0';
    }

    if ( is_scalar( $value ) ) {
        return sanitize_text_field( (string) $value );
    }

    return '';
}

add_action( 'wp_ajax_breakdance_form_custom', 'adfoin_breakdance_submission', 5 );
add_action( 'wp_ajax_nopriv_breakdance_form_custom', 'adfoin_breakdance_submission', 5 );
