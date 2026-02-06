<?php

/**
 * SureForms trigger integration.
 *
 * @package advanced-form-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve SureForms form list.
 *
 * @param string $form_provider Trigger provider key.
 *
 * @return array<int,string>|void
 */
function adfoin_sureforms_get_forms( $form_provider ) {
    if ( 'sureforms' !== $form_provider ) {
        return;
    }

    if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
        return array();
    }

    $forms = get_posts(
        array(
            'post_type'              => SRFM_FORMS_POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'fields'                 => 'ids',
        )
    );

    if ( empty( $forms ) ) {
        return array();
    }

    $formatted = array();

    foreach ( $forms as $form_id ) {
        $formatted[ $form_id ] = get_the_title( $form_id );
    }

    return $formatted;
}

/**
 * Retrieve SureForms form fields.
 *
 * @param string $form_provider Trigger provider key.
 * @param int    $form_id       Form ID.
 *
 * @return array<string,string>|void
 */
function adfoin_sureforms_get_form_fields( $form_provider, $form_id ) {
    if ( 'sureforms' !== $form_provider ) {
        return;
    }

    if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
        return array();
    }

    $form = get_post( $form_id );

    if ( ! $form || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
        return array();
    }

    $block_map = adfoin_sureforms_get_block_map( $form );

    if ( empty( $block_map['blocks'] ) ) {
        return array();
    }

    $fields = array();

    foreach ( $block_map['blocks'] as $block_id => $meta ) {
        if ( adfoin_sureforms_is_field_restricted( $meta['type'] ) ) {
            continue;
        }

        $fields[ $meta['slug'] ] = $meta['label'];
    }

    $fields['form_id']         = __( 'Form ID', 'advanced-form-integration' );
    $fields['form_title']      = __( 'Form Title', 'advanced-form-integration' );
    $fields['submission_id']   = __( 'Submission ID', 'advanced-form-integration' );
    $fields['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();
    if ( is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

/**
 * Get SureForms form title.
 *
 * @param string $form_provider Trigger provider key.
 * @param int    $form_id       Form ID.
 *
 * @return string|void
 */
function adfoin_sureforms_get_form_name( $form_provider, $form_id ) {
    if ( 'sureforms' !== $form_provider ) {
        return;
    }

    if ( ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
        return '';
    }

    $form = get_post( $form_id );

    if ( ! $form || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
        return '';
    }

    return $form->post_title;
}

add_action( 'srfm_form_submit', 'adfoin_sureforms_handle_submission', 10, 1 );

/**
 * Dispatch SureForms submission to configured integrations.
 *
 * @param array<string,mixed> $response SureForms submission payload.
 *
 * @return void
 */
function adfoin_sureforms_handle_submission( $response ) {
    if ( empty( $response ) || ! is_array( $response ) ) {
        return;
    }

    $form_id = isset( $response['form_id'] ) ? absint( $response['form_id'] ) : 0;

    if ( ! $form_id || ! defined( 'SRFM_FORMS_POST_TYPE' ) ) {
        return;
    }

    $form = get_post( $form_id );

    if ( ! $form || SRFM_FORMS_POST_TYPE !== $form->post_type ) {
        return;
    }

    global $wpdb, $post;

    $saved_records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = %s AND form_id = %s",
            'sureforms',
            $form_id
        ),
        ARRAY_A
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();

    $block_map = adfoin_sureforms_get_block_map( $form );

    $posted_data = adfoin_sureforms_build_payload( $payload, $block_map );

    $posted_data['form_id']         = $form_id;
    $posted_data['form_title']      = get_the_title( $form_id );
    $posted_data['submission_id']   = isset( $response['entry_id'] ) ? $response['entry_id'] : '';
    $posted_data['submission_date'] = date_i18n( 'Y-m-d H:i:s' );

    if ( isset( $response['to_emails'] ) && is_array( $response['to_emails'] ) ) {
        $posted_data['notification_emails'] = implode( ', ', $response['to_emails'] );
    }

    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
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
            call_user_func(
                "adfoin_{$action_provider}_send_data",
                $record,
                $posted_data
            );
        }
    }
}

/**
 * Generate SureForms field map for submissions.
 *
 * @param array<string,mixed> $payload  Submission data.
 * @param array<string,mixed> $block_map Normalised block metadata.
 *
 * @return array<string,string>
 */
function adfoin_sureforms_build_payload( $payload, $block_map ) {
    $prepared = array();

    if ( empty( $payload ) || ! is_array( $payload ) ) {
        return $prepared;
    }

    $label_lookup = isset( $block_map['labels'] ) ? $block_map['labels'] : array();

    foreach ( $payload as $key => $value ) {
        if ( is_array( $value ) ) {
            $value = implode(
                ', ',
                array_filter(
                    array_map(
                        static function( $item ) {
                            return is_scalar( $item ) ? urldecode( (string) $item ) : '';
                        },
                        $value
                    )
                )
            );
        } elseif ( is_string( $value ) ) {
            $value = urldecode( $value );
        } elseif ( is_scalar( $value ) ) {
            $value = (string) $value;
        } else {
            $value = '';
        }

        if ( '' === $value ) {
            continue;
        }

        $slug = '';

        if ( isset( $block_map['slugs'][ $key ] ) ) {
            $slug = $block_map['slugs'][ $key ];
        } else {
            $normalized = adfoin_sureforms_normalize_label( $key );
            if ( isset( $label_lookup[ $normalized ] ) ) {
                $slug = $label_lookup[ $normalized ];
            }
        }

        if ( ! $slug ) {
            $slug = sanitize_title( $key );
        }

        if ( isset( $block_map['types'][ $slug ] ) && adfoin_sureforms_is_field_restricted( $block_map['types'][ $slug ] ) ) {
            continue;
        }

        $prepared[ $slug ] = $value;
    }

    return $prepared;
}

/**
 * Build SureForms block metadata.
 *
 * @param WP_Post $form Form post object.
 *
 * @return array<string,array>
 */
function adfoin_sureforms_get_block_map( $form ) {
    static $cache = array();

    if ( isset( $cache[ $form->ID ] ) ) {
        return $cache[ $form->ID ];
    }

    $result = array(
        'blocks' => array(),
        'labels' => array(),
        'slugs'  => array(),
        'types'  => array(),
    );

    if ( empty( $form->post_content ) || ! function_exists( 'parse_blocks' ) ) {
        $cache[ $form->ID ] = $result;
        return $result;
    }

    $blocks = parse_blocks( $form->post_content );

    adfoin_sureforms_map_blocks( $blocks, $result );

    $cache[ $form->ID ] = $result;

    return $result;
}

/**
 * Walk SureForms blocks to gather metadata.
 *
 * @param array<int,array<mixed>> $blocks Parsed blocks.
 * @param array<string,array>     $result Accumulator.
 *
 * @return void
 */
function adfoin_sureforms_map_blocks( $blocks, &$result ) {
    if ( empty( $blocks ) || ! is_array( $blocks ) ) {
        return;
    }

    foreach ( $blocks as $block ) {
        if ( empty( $block['blockName'] ) ) {
            continue;
        }

        if ( ! empty( $block['innerBlocks'] ) ) {
            adfoin_sureforms_map_blocks( $block['innerBlocks'], $result );
        }

        if ( 0 !== strpos( $block['blockName'], 'srfm/' ) ) {
            continue;
        }

        $type = substr( $block['blockName'], 5 );

        if ( 'submit' === $type ) {
            continue;
        }

        $attrs    = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
        $block_id = isset( $attrs['block_id'] ) ? sanitize_text_field( $attrs['block_id'] ) : '';

        if ( empty( $block_id ) ) {
            continue;
        }

        $label = adfoin_sureforms_resolve_label( $type, $attrs );
        $slug  = isset( $attrs['slug'] ) ? sanitize_title( $attrs['slug'] ) : sanitize_title( $label );

        if ( empty( $slug ) ) {
            $slug = 'field_' . $block_id;
        }

        $result['blocks'][ $block_id ] = array(
            'slug'  => $slug,
            'label' => $label,
            'type'  => $type,
        );

        $result['labels'][ adfoin_sureforms_normalize_label( $label ) ] = $slug;
        $result['slugs'][ $slug ]                                       = $slug;
        $result['types'][ $slug ]                                       = $type;
    }
}

/**
 * Resolve block label fallback.
 *
 * @param string               $type  Block type.
 * @param array<string,mixed> $attrs Block attributes.
 *
 * @return string
 */
function adfoin_sureforms_resolve_label( $type, $attrs ) {
    $candidates = array(
        'label',
        'questionLabel',
        'checkboxLabel',
        'gdprLabel',
        'fieldLabel',
        'title',
        'name',
    );

    foreach ( $candidates as $key ) {
        if ( ! empty( $attrs[ $key ] ) && is_string( $attrs[ $key ] ) ) {
            return wp_strip_all_tags( $attrs[ $key ] );
        }
    }

    $defaults = array(
        'input'        => __( 'Text Field', 'advanced-form-integration' ),
        'email'        => __( 'Email', 'advanced-form-integration' ),
        'textarea'     => __( 'Textarea', 'advanced-form-integration' ),
        'number'       => __( 'Number', 'advanced-form-integration' ),
        'checkbox'     => __( 'Checkbox', 'advanced-form-integration' ),
        'gdpr'         => __( 'GDPR', 'advanced-form-integration' ),
        'phone'        => __( 'Phone', 'advanced-form-integration' ),
        'address'      => __( 'Address', 'advanced-form-integration' ),
        'dropdown'     => __( 'Dropdown', 'advanced-form-integration' ),
        'multi-choice' => __( 'Multi Choice', 'advanced-form-integration' ),
        'radio'        => __( 'Radio', 'advanced-form-integration' ),
        'url'          => __( 'Website', 'advanced-form-integration' ),
    );

    return isset( $defaults[ $type ] ) ? $defaults[ $type ] : __( 'Field', 'advanced-form-integration' );
}

/**
 * Determine if a SureForms field should be restricted for current license.
 *
 * @param string $type Block type.
 *
 * @return bool
 */
function adfoin_sureforms_is_field_restricted( $type ) {
    if ( adfoin_fs()->is_not_paying() ) {
        $allowed = array( 'input', 'email' );
        return ! in_array( $type, $allowed, true );
    }

    if ( adfoin_fs()->is__premium_only() && ! adfoin_fs()->is_plan( 'professional', true ) ) {
        return true;
    }

    return false;
}

/**
 * Normalize labels for lookups.
 *
 * @param string $label Raw label.
 *
 * @return string
 */
function adfoin_sureforms_normalize_label( $label ) {
    $label = wp_strip_all_tags( (string) $label );
    $label = strtolower( trim( $label ) );
    $label = preg_replace( '/\s+/', ' ', $label );

    return (string) $label;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_sureforms_trigger_fields' );
}

/**
 * Render SureForms free plan note.
 *
 * @return void
 */
function adfoin_sureforms_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'sureforms'" is="sureforms" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php
}

add_action( 'adfoin_trigger_templates', 'adfoin_sureforms_trigger_template' );

/**
 * Output SureForms trigger template.
 *
 * @return void
 */
function adfoin_sureforms_trigger_template() {
    ?>
    <script type="text/template" id="sureforms-template">
        <tr valign="top" class="alternate" v-if="trigger.formId">
            <td scope="row-title">
                <label for="tablecell">
                    <span class="dashicons dashicons-info-outline"></span>
                </label>
            </td>
            <td>
                <p>
                    <?php esc_attr_e( 'The basic AFI plugin supports name and email fields only', 'advanced-form-integration' ); ?>
                </p>
            </td>
        </tr>
    </script>
    <?php
}
