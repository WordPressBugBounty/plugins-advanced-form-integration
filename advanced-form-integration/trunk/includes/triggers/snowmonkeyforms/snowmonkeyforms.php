<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve Snow Monkey Forms list.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_snowmonkeyforms_get_forms( $form_provider ) {
    if ( 'snowmonkeyforms' !== $form_provider ) {
        return;
    }

    $query = new WP_Query(
        array(
            'post_type'           => 'snow-monkey-forms',
            'post_status'         => 'publish',
            'posts_per_page'      => -1,
            'orderby'             => 'title',
            'order'               => 'ASC',
            'suppress_filters'    => true,
            'ignore_sticky_posts' => true,
            'fields'              => 'ids',
        )
    );

    if ( empty( $query->posts ) ) {
        return array();
    }

    $forms = array();

    foreach ( $query->posts as $post_id ) {
        $forms[ (string) $post_id ] = get_the_title( $post_id );
    }

    wp_reset_postdata();

    return $forms;
}

/**
 * Retrieve Snow Monkey Forms mapped fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 *
 * @return array<string,string>|void
 */
function adfoin_snowmonkeyforms_get_form_fields( $form_provider, $form_id ) {
    if ( 'snowmonkeyforms' !== $form_provider ) {
        return;
    }

    $form_id = absint( $form_id );

    if ( ! $form_id || ! class_exists( '\Snow_Monkey\Plugin\Forms\App\Model\Setting' ) ) {
        return array();
    }

    $fields = array();

    try {
        $setting = new \Snow_Monkey\Plugin\Forms\App\Model\Setting( $form_id );
    } catch ( \Throwable $th ) {
        return $fields;
    }

    $field_map = adfoin_snowmonkeyforms_extract_field_map( $setting );

    if ( ! empty( $field_map ) ) {
        foreach ( $field_map as $name => $label ) {
            $fields[ $name ] = $label;
        }
    }

    $special_tags = adfoin_get_special_tags();

    if ( is_array( $special_tags ) && ! empty( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

/**
 * Retrieve Snow Monkey Form title.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 *
 * @return string|void
 */
function adfoin_snowmonkeyforms_get_form_name( $form_provider, $form_id ) {
    if ( 'snowmonkeyforms' !== $form_provider ) {
        return;
    }

    return get_the_title( absint( $form_id ) );
}

add_action( 'snow_monkey_forms/administrator_mailer/after_send', 'adfoin_snowmonkeyforms_handle_submission', 10, 4 );

/**
 * Handle Snow Monkey Forms submission event.
 *
 * @param bool                                                 $is_sended  Whether Snow Monkey Forms completed admin email processing.
 * @param \Snow_Monkey\Plugin\Forms\App\Model\Responser        $responser  Submission responder.
 * @param \Snow_Monkey\Plugin\Forms\App\Model\Setting          $setting    Form settings.
 * @param \Snow_Monkey\Plugin\Forms\App\Model\MailParser       $mail_parser Mail parser instance.
 *
 * @return void
 */
function adfoin_snowmonkeyforms_handle_submission( $is_sended, $responser, $setting, $mail_parser ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    try {
        if ( ! $is_sended ) {
            return;
        }

        if ( ! ( $responser instanceof \Snow_Monkey\Plugin\Forms\App\Model\Responser ) ) {
            return;
        }

        if ( ! ( $setting instanceof \Snow_Monkey\Plugin\Forms\App\Model\Setting ) ) {
            return;
        }

        if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
            return;
        }

        $form_id   = 0;
        $token     = '';
        $method    = '';

        if ( ! $form_id ) {
            $form_id = absint( $setting->get( 'form_id' ) );
        }

        if ( ! $form_id ) {
            return;
        }

        static $processed = array();

        $fingerprint = $form_id . ':' . $token . ':' . $method;

        if ( isset( $processed[ $fingerprint ] ) ) {
            return;
        }

        $processed[ $fingerprint ] = true;

        $integration   = new Advanced_Form_Integration_Integration();
        $saved_records = $integration->get_by_trigger( 'snowmonkeyforms', (string) $form_id );

        if ( empty( $saved_records ) ) {
            return;
        }

        $payload = adfoin_snowmonkeyforms_build_payload( $form_id, $responser, $setting );

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
                $callback = "adfoin_{$action_provider}_send_data";

                if ( is_callable( $callback ) ) {
                    call_user_func( $callback, $record, $payload );
                }
            }
        }
    } catch ( \Throwable $th ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( '[AFI Snow Monkey Forms] %s in %s on line %d', $th->getMessage(), $th->getFile(), $th->getLine() ) );
        }
    }
}

/**
 * Build submission payload.
 *
 * @param int                                                  $form_id   Form identifier.
 * @param \Snow_Monkey\Plugin\Forms\App\Model\Responser        $responser Submission responder.
 * @param \Snow_Monkey\Plugin\Forms\App\Model\Setting          $setting   Form settings.
 * @param array<string,mixed>|null                             $meta      Form meta.
 *
 * @return array<string,string>
 */
function adfoin_snowmonkeyforms_build_payload( $form_id, $responser, $setting ) {
    $data = $responser->get_all();
    unset( $data['snow-monkey-forms-meta'] );

    $payload = array();

    //temporary map
    $payload = array(
        'form_id'    => (string) $form_id,
        'form_title' => get_the_title( $form_id ),
    );

    if(is_array($data)){
        $payload = array_merge( $payload, array_map( 'trim', $data ) );
    }

    // foreach ( $data as $field_name => $value ) {
    //     if ( ! is_string( $field_name ) ) {
    //         continue;
    //     }

    //     $payload[ $field_name ] = adfoin_snowmonkeyforms_normalize_value( $field_name, $value, $setting );

    //     if ( isset( $field_map[ $field_name ] ) ) {
    //         $label_key = adfoin_snowmonkeyforms_label_key( $field_map[ $field_name ] );
    //         if ( $label_key && ! array_key_exists( $label_key, $payload ) ) {
    //             $payload[ $label_key ] = $payload[ $field_name ];
    //         }
    //     }
    // }

    return $payload;
}

/**
 * Normalize control value.
 *
 * @param string                                               $field_name Field name.
 * @param mixed                                                $value      Original value.
 * @param \Snow_Monkey\Plugin\Forms\App\Model\Setting          $setting    Form settings.
 *
 * @return string
 */
function adfoin_snowmonkeyforms_normalize_value( $field_name, $value, $setting ) {
    $control = $setting->get_control( $field_name );

    if ( class_exists( '\Snow_Monkey\Plugin\Forms\App\Model\FileUploader' ) && \Snow_Monkey\Plugin\Forms\App\Model\FileUploader::has_error_code( $value ) ) {
        $error_codes = \Snow_Monkey\Plugin\Forms\App\Model\FileUploader::get_error_codes();
        $value       = isset( $error_codes[ intval( $value ) ] ) ? $error_codes[ intval( $value ) ] : (string) $value;
    }

    if ( is_array( $value ) ) {
        $pieces = array_filter(
            array_map(
                static function ( $item ) {
                    if ( is_scalar( $item ) ) {
                        return (string) $item;
                    }

                    $encoded = wp_json_encode( $item );
                    return is_string( $encoded ) ? $encoded : '';
                },
                $value
            ),
            static function ( $item ) {
                return '' !== $item;
            }
        );

        $delimiter = ', ';

        if ( $control && method_exists( $control, 'get_property' ) ) {
            $maybe_delimiter = $control->get_property( 'delimiter' );
            if ( is_string( $maybe_delimiter ) && '' !== $maybe_delimiter ) {
                $delimiter = $maybe_delimiter;
            }
        }

        return implode( $delimiter, array_unique( $pieces ) );
    }

    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_scalar( $value ) || null === $value ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Extract field map for labels.
 *
 * @param \Snow_Monkey\Plugin\Forms\App\Model\Setting $setting Form settings.
 *
 * @return array<string,string>
 */
function adfoin_snowmonkeyforms_extract_field_map( $setting ) {
    $input_content = $setting->get( 'input_content' );

    if ( empty( $input_content ) || ! is_string( $input_content ) ) {
        return array();
    }

    if ( ! function_exists( 'parse_blocks' ) ) {
        return array();
    }

    $blocks = parse_blocks( $input_content );
    $map    = array();

    adfoin_snowmonkeyforms_traverse_blocks( $blocks, $map );

    return $map;
}

/**
 * Traverse block structure to map field names to labels.
 *
 * @param array<int,array<string,mixed>> $blocks Block structure.
 * @param array<string,string>           $map    Field map accumulator.
 * @param string                         $label  Current label context.
 *
 * @return void
 */
function adfoin_snowmonkeyforms_traverse_blocks( array $blocks, array &$map, $label = '' ) {
    foreach ( $blocks as $block ) {
        if ( empty( $block['blockName'] ) ) {
            if ( ! empty( $block['innerBlocks'] ) ) {
                adfoin_snowmonkeyforms_traverse_blocks( $block['innerBlocks'], $map, $label );
            }
            continue;
        }

        $name  = (string) $block['blockName'];
        $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

        if ( 'snow-monkey-forms/item' === $name ) {
            $item_label = '';

            if ( isset( $attrs['label'] ) && is_string( $attrs['label'] ) ) {
                $item_label = wp_strip_all_tags( $attrs['label'] );
            }

            if ( '' === $item_label && isset( $attrs['labelFor'] ) && is_string( $attrs['labelFor'] ) ) {
                $item_label = sanitize_text_field( $attrs['labelFor'] );
            }

            if ( '' === $item_label ) {
                $item_label = $label;
            }

            if ( ! empty( $block['innerBlocks'] ) ) {
                adfoin_snowmonkeyforms_traverse_blocks( $block['innerBlocks'], $map, $item_label );
            }
            continue;
        }

        if ( 0 === strpos( $name, 'snow-monkey-forms/control-' ) ) {
            $field_name = isset( $attrs['name'] ) ? trim( (string) $attrs['name'] ) : '';

            if ( '' !== $field_name ) {
                $field_label = $label;

                if ( '' === $field_label && isset( $attrs['label'] ) && is_string( $attrs['label'] ) ) {
                    $field_label = wp_strip_all_tags( $attrs['label'] );
                }

                if ( '' === $field_label && isset( $attrs['placeholder'] ) && is_string( $attrs['placeholder'] ) ) {
                    $field_label = wp_strip_all_tags( $attrs['placeholder'] );
                }

                if ( '' === $field_label ) {
                    $field_label = $field_name;
                }

                $map[ $field_name ] = $field_label;
            }

            if ( ! empty( $block['innerBlocks'] ) ) {
                adfoin_snowmonkeyforms_traverse_blocks( $block['innerBlocks'], $map, $label );
            }
            continue;
        }

        if ( ! empty( $block['innerBlocks'] ) ) {
            adfoin_snowmonkeyforms_traverse_blocks( $block['innerBlocks'], $map, $label );
        }
    }
}

/**
 * Generate a key based on the field label.
 *
 * @param string $label Field label.
 *
 * @return string
 */
function adfoin_snowmonkeyforms_label_key( $label ) {
    $label = sanitize_key( remove_accents( strtolower( $label ) ) );

    if ( '' === $label ) {
        return '';
    }

    return 'label_' . $label;
}
