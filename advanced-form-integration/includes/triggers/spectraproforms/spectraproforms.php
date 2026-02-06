<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve Spectra Pro (Spectra) forms.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_spectraproforms_get_forms( $form_provider ) {
    if ( 'spectraproforms' !== $form_provider ) {
        return;
    }

    $forms = adfoin_spectraproforms_discover_forms();

    if ( empty( $forms ) ) {
        return array();
    }

    $form_list = array();

    foreach ( $forms as $form ) {
        $post_id  = $form['post_id'];
        $block_id = $form['block_id'];
        $label    = $form['label'];

        $title = get_the_title( $post_id );

        if ( ! $title ) {
            /* translators: %d: Post ID */
            $title = sprintf( __( 'Post #%d', 'advanced-form-integration' ), $post_id );
        }

        if ( '' === $label ) {
            /* translators: %s: Block ID */
            $label = sprintf( __( 'Form %s', 'advanced-form-integration' ), $block_id );
        }

        $key                = $post_id . ':' . $block_id;
        $form_list[ $key ] = $title . ' - ' . $label;
    }

    return $form_list;
}

/**
 * Retrieve Spectra Pro form fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Stored identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_spectraproforms_get_form_fields( $form_provider, $form_id ) {
    if ( 'spectraproforms' !== $form_provider ) {
        return;
    }

    $parsed = adfoin_spectraproforms_parse_identifier( $form_id );

    if ( ! $parsed ) {
        return array();
    }

    $form_block = adfoin_spectraproforms_locate_form_block( $parsed['post_id'], $parsed['block_id'] );

    if ( ! $form_block ) {
        return array();
    }

    $field_map = adfoin_spectraproforms_extract_fields_from_block( $form_block, $parsed['post_id'] );

    $fields = array();

    foreach ( $field_map as $field_key => $field_label ) {
        $fields[ $field_key ] = $field_label;
    }

    $fields['form_id']        = __( 'Form ID', 'advanced-form-integration' );
    $fields['form_name']      = __( 'Form Name', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if ( is_array( $special_tags ) && ! empty( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

/**
 * Retrieve Spectra Pro form name.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Stored identifier.
 *
 * @return string|void
 */
function adfoin_spectraproforms_get_form_name( $form_provider, $form_id ) {
    if ( 'spectraproforms' !== $form_provider ) {
        return;
    }

    $parsed = adfoin_spectraproforms_parse_identifier( $form_id );

    if ( ! $parsed ) {
        return '';
    }

    $form_block = adfoin_spectraproforms_locate_form_block( $parsed['post_id'], $parsed['block_id'] );

    if ( ! $form_block ) {
        return '';
    }

    $attrs = isset( $form_block['attrs'] ) && is_array( $form_block['attrs'] ) ? $form_block['attrs'] : array();

    if ( ! empty( $attrs['formLabel'] ) && is_string( $attrs['formLabel'] ) ) {
        $label = trim( wp_strip_all_tags( $attrs['formLabel'] ) );
        if ( '' !== $label ) {
            return $label;
        }
    }

    $title = get_the_title( $parsed['post_id'] );

    return $title ? $title : '';
}

add_action( 'uagb_form_success', 'adfoin_spectraproforms_handle_submission', 10, 1 );

/**
 * Handle Spectra Pro form submission event.
 *
 * @param array<string,mixed> $form_data Submitted data array.
 *
 * @return void
 */
function adfoin_spectraproforms_handle_submission( $form_data ) {
    if ( empty( $form_data ) || ! is_array( $form_data ) ) {
        return;
    }

    $block_id = '';

    if ( isset( $form_data['id'] ) ) {
        $block_id = (string) $form_data['id'];
    } elseif ( isset( $_POST['block_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $block_id = sanitize_text_field( wp_unslash( $_POST['block_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    $block_id = trim( $block_id );

    if ( '' === $block_id ) {
        return;
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( ! $post_id ) {
        return;
    }

    $form_identifier = $post_id . ':' . $block_id;

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'spectraproforms', $form_identifier );

    if ( empty( $saved_records ) ) {
        return;
    }

    $form_block = adfoin_spectraproforms_locate_form_block( $post_id, $block_id );

    $field_map = array();

    if ( $form_block ) {
        $field_map = adfoin_spectraproforms_extract_fields_from_block( $form_block, $post_id );
    }

    $payload = adfoin_spectraproforms_prepare_payload( $form_data, $field_map, $post_id, $block_id, $form_block );

    if ( empty( $payload ) ) {
        return;
    }

    $post = get_post( $post_id );

    if ( $post instanceof \WP_Post ) {
        $special_tag_values = adfoin_get_special_tags_values( $post );

        if ( is_array( $special_tag_values ) ) {
            $payload = $payload + $special_tag_values;
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
                        'posted_data' => $payload,
                    ),
                )
            );
        } else {
            $callback = "adfoin_{$action_provider}_send_data";

            if ( is_callable( $callback ) ) {
                call_user_func( $callback, $record, $payload );
            }
        }
    }
}

/**
 * Prepare payload for Spectra Pro submission.
 *
 * @param array<string,mixed>  $form_data Submitted data.
 * @param array<string,string> $field_map Label => key mapping.
 * @param int                  $post_id   Post ID containing the form.
 * @param string               $block_id  Block identifier.
 * @param array<string,mixed>  $form_block Parsed block data.
 *
 * @return array<string,string>
 */
function adfoin_spectraproforms_prepare_payload( $form_data, $field_map, $post_id, $block_id, $form_block ) {
    $payload = array();

    foreach ( $form_data as $raw_label => $value ) {
        if ( 'id' === $raw_label ) {
            continue;
        }

        $label = is_string( $raw_label ) ? trim( $raw_label ) : '';

        if ( '' === $label ) {
            continue;
        }

        $field_key = '';

        if ( isset( $field_map[ $label ] ) ) {
            $field_key = $field_map[ $label ];
        } else {
            $field_key = adfoin_spectraproforms_label_to_key( $label );
        }

        if ( '' === $field_key ) {
            continue;
        }

        $payload[ $field_key ] = adfoin_spectraproforms_normalize_value( $value );
    }

    if ( empty( $payload ) ) {
        return array();
    }

    $payload['form_id']         = (string) $block_id;
    $payload['form_post_id']    = (string) $post_id;
    $payload['form_post_title'] = get_the_title( $post_id );
    $payload['submission_date'] = current_time( 'mysql' );
    $payload['user_ip']         = adfoin_get_user_ip();
    $payload['user_agent']      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

    $form_label = '';

    if ( $form_block ) {
        $attrs = isset( $form_block['attrs'] ) && is_array( $form_block['attrs'] ) ? $form_block['attrs'] : array();

        if ( isset( $attrs['formLabel'] ) && is_string( $attrs['formLabel'] ) ) {
            $form_label = trim( wp_strip_all_tags( $attrs['formLabel'] ) );
        }
    }

    if ( '' === $form_label ) {
        $form_label = get_the_title( $post_id );
    }

    if ( '' !== $form_label ) {
        $payload['form_name'] = $form_label;
    }

    return $payload;
}

/**
 * Normalize value for transport.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function adfoin_spectraproforms_normalize_value( $value ) {
    if ( is_array( $value ) ) {
        $normalized = array();

        foreach ( $value as $item ) {
            $item_value = adfoin_spectraproforms_normalize_value( $item );

            if ( '' !== $item_value ) {
                $normalized[] = $item_value;
            }
        }

        return implode( ', ', $normalized );
    }

    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_scalar( $value ) || null === $value ) {
        $value = (string) $value;
        return sanitize_text_field( $value );
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Discover Spectra forms across published posts.
 *
 * @return array<int,array<string,mixed>>
 */
function adfoin_spectraproforms_discover_forms() {
    static $cache = null;

    if ( null !== $cache ) {
        return $cache;
    }

    $post_types = get_post_types(
        array(
            'public' => true,
        ),
        'names'
    );

    if ( empty( $post_types ) ) {
        $cache = array();
        return $cache;
    }

    if ( isset( $post_types['attachment'] ) ) {
        unset( $post_types['attachment'] );
    }

    $post_ids = get_posts(
        array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'nopaging'       => true,
        )
    );

    if ( empty( $post_ids ) ) {
        $cache = array();
        return $cache;
    }

    $forms = array();

    foreach ( $post_ids as $post_id ) {
        $content = get_post_field( 'post_content', $post_id );

        if ( empty( $content ) ) {
            continue;
        }

        $blocks = parse_blocks( $content );

        if ( empty( $blocks ) ) {
            continue;
        }

        adfoin_spectraproforms_collect_forms_from_blocks( $blocks, (int) $post_id, $forms );
    }

    $cache = $forms;

    return $forms;
}

/**
 * Collect forms from parsed block array.
 *
 * @param array<int,array<string,mixed>> $blocks  Block array.
 * @param int                            $post_id Parent post ID.
 * @param array<int,array<string,mixed>> $forms   Reference accumulator.
 * @param array<int>                     $visited Reusable block tracker.
 *
 * @return void
 */
function adfoin_spectraproforms_collect_forms_from_blocks( $blocks, $post_id, &$forms, $visited = array() ) {
    if ( empty( $blocks ) || ! is_array( $blocks ) ) {
        return;
    }

    foreach ( $blocks as $block ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

        if ( 'uagb/forms' === $block_name ) {
            $attrs    = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
            $block_id = isset( $attrs['block_id'] ) ? (string) $attrs['block_id'] : '';

            if ( '' !== $block_id ) {
                $label = '';

                if ( isset( $attrs['formLabel'] ) && is_string( $attrs['formLabel'] ) ) {
                    $label = trim( wp_strip_all_tags( $attrs['formLabel'] ) );
                }

                $forms[] = array(
                    'post_id'  => (int) $post_id,
                    'block_id' => $block_id,
                    'label'    => $label,
                );
            }
        }

        if ( 'core/block' === $block_name && ! empty( $block['attrs']['ref'] ) ) {
            $ref_id = absint( $block['attrs']['ref'] );

            if ( $ref_id && ! in_array( $ref_id, $visited, true ) ) {
                $visited[] = $ref_id;
                $reusable  = get_post( $ref_id );

                if ( $reusable instanceof \WP_Post ) {
                    $ref_blocks = parse_blocks( $reusable->post_content );
                    adfoin_spectraproforms_collect_forms_from_blocks( $ref_blocks, $post_id, $forms, $visited );
                }
            }
        }

        if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
            adfoin_spectraproforms_collect_forms_from_blocks( $block['innerBlocks'], $post_id, $forms, $visited );
        }
    }
}

/**
 * Locate specific Spectra form block.
 *
 * @param int    $post_id  Post ID.
 * @param string $block_id Block identifier.
 *
 * @return array<string,mixed>|null
 */
function adfoin_spectraproforms_locate_form_block( $post_id, $block_id ) {
    $content = get_post_field( 'post_content', $post_id );

    if ( empty( $content ) ) {
        return null;
    }

    $blocks = parse_blocks( $content );

    if ( empty( $blocks ) ) {
        return null;
    }

    return adfoin_spectraproforms_search_block_tree( $blocks, $block_id );
}

/**
 * Recursively search block tree for matching form.
 *
 * @param array<int,array<string,mixed>> $blocks   Blocks array.
 * @param string                         $block_id Block identifier.
 * @param array<int>                     $visited  Reusable tracker.
 *
 * @return array<string,mixed>|null
 */
function adfoin_spectraproforms_search_block_tree( $blocks, $block_id, $visited = array() ) {
    foreach ( $blocks as $block ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

        if ( 'uagb/forms' === $block_name ) {
            $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

            if ( isset( $attrs['block_id'] ) && (string) $attrs['block_id'] === (string) $block_id ) {
                return $block;
            }
        }

        if ( 'core/block' === $block_name && ! empty( $block['attrs']['ref'] ) ) {
            $ref_id = absint( $block['attrs']['ref'] );

            if ( $ref_id && ! in_array( $ref_id, $visited, true ) ) {
                $visited[] = $ref_id;
                $reusable  = get_post( $ref_id );

                if ( $reusable instanceof \WP_Post ) {
                    $ref_blocks = parse_blocks( $reusable->post_content );
                    $found      = adfoin_spectraproforms_search_block_tree( $ref_blocks, $block_id, $visited );

                    if ( $found ) {
                        return $found;
                    }
                }
            }
        }

        if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
            $inner = adfoin_spectraproforms_search_block_tree( $block['innerBlocks'], $block_id, $visited );

            if ( $inner ) {
                return $inner;
            }
        }
    }

    return null;
}

/**
 * Extract fields from form block.
 *
 * @param array<string,mixed> $form_block Parsed form block.
 *
 * @return array<string,string> Field key => label.
 */
function adfoin_spectraproforms_extract_fields_from_block( $form_block, $post_id ) {
    $fields = array();

    if ( empty( $form_block['innerBlocks'] ) || ! is_array( $form_block['innerBlocks'] ) ) {
        return $fields;
    }

    adfoin_spectraproforms_traverse_field_blocks( $form_block['innerBlocks'], $fields, $post_id );

    return $fields;
}

/**
 * Traverse inner blocks to collect field labels.
 *
 * @param array<int,array<string,mixed>> $blocks Nested blocks.
 * @param array<string,string>           $fields Reference accumulator.
 *
 * @return void
 */
function adfoin_spectraproforms_traverse_field_blocks( $blocks, &$fields, $post_id ) {
    foreach ( $blocks as $block ) {
        if ( ! is_array( $block ) ) {
            continue;
        }

        $block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

        if ( 0 === strpos( (string) $block_name, 'uagb/forms-' ) && 'uagb/forms-button' !== $block_name ) {
            $label = adfoin_spectraproforms_determine_field_label( $block, $post_id );

            if ( '' !== $label ) {
                $key = adfoin_spectraproforms_label_to_key( $label );

                if ( '' !== $key && ! isset( $fields[ $key ] ) ) {
                    $fields[ $key ] = $label;
                }
            }
        }

        if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
            adfoin_spectraproforms_traverse_field_blocks( $block['innerBlocks'], $fields, $post_id );
        }
    }
}

/**
 * Determine field label from block attributes.
 *
 * @param array<string,mixed> $block   Block data.
 * @param int                 $post_id Post ID.
 *
 * @return string
 */
function adfoin_spectraproforms_determine_field_label( $block, $post_id ) {
    $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

    $candidates = array(
        'label',
        'fieldLabel',
        'formFieldLabel',
        'radioName',
        'checkboxLabel',
        'toggleLabel',
        'acceptText',
        'name',
        'heading',
        'text',
        'placeholder',
    );

    foreach ( $candidates as $candidate ) {
        if ( isset( $attrs[ $candidate ] ) && is_string( $attrs[ $candidate ] ) ) {
            $label = trim( wp_strip_all_tags( $attrs[ $candidate ] ) );

            if ( '' !== $label ) {
                return $label;
            }
        }
    }

    if ( isset( $attrs['hiddenFieldName'] ) && is_string( $attrs['hiddenFieldName'] ) ) {
        $label = trim( $attrs['hiddenFieldName'] );

        if ( '' !== $label ) {
            return $label;
        }
    }

    $block_identifier = isset( $attrs['block_id'] ) ? (string) $attrs['block_id'] : '';

    if ( $block_identifier && isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
        $inner_label = adfoin_spectraproforms_extract_label_from_html( $block['innerHTML'], $block_identifier );

        if ( '' !== $inner_label ) {
            return $inner_label;
        }
    }

    if ( $block_identifier ) {
        $resolved_block = adfoin_spectraproforms_find_block_by_id( $post_id, $block_identifier );

        if ( $resolved_block && isset( $resolved_block['innerHTML'] ) && is_string( $resolved_block['innerHTML'] ) ) {
            $resolved_label = adfoin_spectraproforms_extract_label_from_html( $resolved_block['innerHTML'], $block_identifier );

            if ( '' !== $resolved_label ) {
                return $resolved_label;
            }
        }
    }

    $block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

    if ( $block_name ) {
        return ucfirst( str_replace( array( 'uagb/forms-', '-' ), array( '', ' ' ), $block_name ) );
    }

    return '';
}

/**
 * Extract label text from block HTML by block ID.
 *
 * @param string $html      Block HTML.
 * @param string $block_id  Block identifier.
 *
 * @return string
 */
function adfoin_spectraproforms_extract_label_from_html( $html, $block_id ) {
    if ( '' === $html || '' === $block_id ) {
        return '';
    }

    $pattern = '/id=["\']' . preg_quote( $block_id, '/' ) . '["\'][^>]*>(.*?)<\/(div|label|span)>/si';

    if ( preg_match( $pattern, $html, $matches ) ) {
        $label = trim( wp_strip_all_tags( $matches[1] ) );

        if ( '' !== $label ) {
            return $label;
        }
    }

    return '';
}

/**
 * Locate a block by ID within a post.
 *
 * @param int    $post_id  Post ID.
 * @param string $block_id Block identifier.
 *
 * @return array<string,mixed>|null
 */
function adfoin_spectraproforms_find_block_by_id( $post_id, $block_id ) {
    static $cache = array();

    if ( isset( $cache[ $post_id ][ $block_id ] ) ) {
        return $cache[ $post_id ][ $block_id ];
    }

    $content = get_post_field( 'post_content', $post_id );

    if ( empty( $content ) ) {
        $cache[ $post_id ][ $block_id ] = null;
        return null;
    }

    $blocks = parse_blocks( $content );

    if ( empty( $blocks ) ) {
        $cache[ $post_id ][ $block_id ] = null;
        return null;
    }

    $result = adfoin_spectraproforms_search_block_tree( $blocks, $block_id );

    if ( ! isset( $cache[ $post_id ] ) ) {
        $cache[ $post_id ] = array();
    }

    $cache[ $post_id ][ $block_id ] = $result ? $result : null;

    return $cache[ $post_id ][ $block_id ];
}

/**
 * Convert label to payload key.
 *
 * @param string $label Field label.
 *
 * @return string
 */
function adfoin_spectraproforms_label_to_key( $label ) {
    $label = trim( $label );

    if ( '' === $label ) {
        return '';
    }

    $key = sanitize_title( $label );

    if ( '' === $key ) {
        $key = 'field_' . substr( md5( $label ), 0, 8 );
    }

    return sanitize_key( $key );
}

/**
 * Parse stored identifier.
 *
 * @param string $form_id Stored form identifier.
 *
 * @return array{post_id:int,block_id:string}|null
 */
function adfoin_spectraproforms_parse_identifier( $form_id ) {
    $form_id = (string) $form_id;

    $parts = explode( ':', $form_id );

    if ( 2 !== count( $parts ) ) {
        return null;
    }

    $post_id  = absint( $parts[0] );
    $block_id = trim( $parts[1] );

    if ( ! $post_id || '' === $block_id ) {
        return null;
    }

    return array(
        'post_id'  => $post_id,
        'block_id' => $block_id,
    );
}
