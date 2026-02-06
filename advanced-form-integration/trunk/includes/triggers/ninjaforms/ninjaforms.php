<?php

function adfoin_ninjaforms_get_forms(  $form_provider  ) {
    if ( $form_provider != 'ninjaforms' ) {
        return;
    }
    // Safely fetch forms from Ninja Forms if available
    if ( !function_exists( 'Ninja_Forms' ) ) {
        return;
    }
    $nf = call_user_func( 'Ninja_Forms' );
    if ( !$nf || !method_exists( $nf, 'form' ) ) {
        return;
    }
    $data = $nf->form()->get_forms();
    $forms = array();
    foreach ( $data as $single ) {
        $forms[$single->get_id()] = $single->get_setting( "title" );
    }
    return $forms;
}

function adfoin_ninjaforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'ninjaforms' ) {
        return;
    }
    if ( !function_exists( 'Ninja_Forms' ) ) {
        return;
    }
    $nf = call_user_func( 'Ninja_Forms' );
    if ( !$nf || !method_exists( $nf, 'form' ) ) {
        return;
    }
    $data = $nf->form( $form_id )->get_fields();
    $fields = array();
    foreach ( $data as $single ) {
        $type = $single->get_settings( 'type' );
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'firstname' == $type || 'lastname' == $type || 'email' == $type ) {
                $fields[$single->get_id()] = $single->get_setting( "label" );
            }
        }
    }
    $special_tags = adfoin_get_special_tags();
    $fields["form_id"] = __( "Form ID", "advanced-form-integration" );
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_ninjaforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != "ninjaforms" ) {
        return;
    }
    if ( !function_exists( 'Ninja_Forms' ) ) {
        return;
    }
    $nf = call_user_func( 'Ninja_Forms' );
    if ( !$nf || !method_exists( $nf, 'form' ) ) {
        return;
    }
    $form = $nf->form( $form_id )->get();
    $form_name = $form->get_setting( "title" );
    return $form_name;
}

add_action( 'ninja_forms_after_submission', 'adfoin_ninjaforms_after_submission' );
/**
 * Resolve a string's Ninja Forms merge tags using available APIs.
 *
 * @param string $value
 * @param array  $form_data The raw Ninja Forms $form_data from the submission hook.
 * @return string
 */
function adfoin_ninjaforms_resolve_merge_tags_value(  $value, $form_data  ) {
    if ( '' === $value || !is_string( $value ) ) {
        return $value;
    }
    // Quick check to avoid work if no tag-like syntax is present.
    if ( false === strpos( $value, '{' ) || false === strpos( $value, '}' ) ) {
        return $value;
    }
    try {
        // Primary path: use Ninja Forms' central filter which all merge tag providers hook into.
        if ( has_filter( 'ninja_forms_merge_tags' ) ) {
            $filtered_value = apply_filters( 'ninja_forms_merge_tags', $value );
            if ( is_string( $filtered_value ) ) {
                return $filtered_value;
            }
        }
        $nf = null;
        if ( function_exists( 'Ninja_Forms' ) ) {
            $nf = call_user_func( 'Ninja_Forms' );
        }
        if ( $nf && isset( $nf->merge_tags ) ) {
            $mt = $nf->merge_tags;
            // Try common method names across NF versions.
            if ( method_exists( $mt, 'process' ) ) {
                $context = array(
                    'form_id' => ( isset( $form_data['form_id'] ) ? $form_data['form_id'] : null ),
                    'fields'  => ( isset( $form_data['fields'] ) ? $form_data['fields'] : array() ),
                    'form'    => $form_data,
                );
                $result = $mt->process( $value, $context );
                if ( is_string( $result ) ) {
                    return $result;
                }
            } elseif ( method_exists( $mt, 'replace' ) ) {
                $result = $mt->replace( $value, $form_data );
                if ( is_string( $result ) ) {
                    return $result;
                }
            } elseif ( method_exists( $mt, 'parse' ) ) {
                $result = $mt->parse( $value, $form_data );
                if ( is_string( $result ) ) {
                    return $result;
                }
            }
        }
        // Fallback: allow 3rd-parties to resolve if NF API differs on this site.
        $filtered = apply_filters( 'adfoin_ninjaforms_resolve_merge_tags_value', $value, $form_data );
        if ( is_string( $filtered ) ) {
            return $filtered;
        }
    } catch ( \Throwable $e ) {
        // Swallow and return original on any parser errors.
    }
    return $value;
}

/**
 * Iterate over $posted_data and resolve merge tags in string values.
 *
 * @param array $posted_data
 * @param array $form_data
 * @return array
 */
function adfoin_ninjaforms_expand_merge_tags_in_posted_data(  array $posted_data, array $form_data  ) {
    $walk = function ( $val ) use(&$walk, $form_data) {
        if ( is_string( $val ) ) {
            return adfoin_ninjaforms_resolve_merge_tags_value( $val, $form_data );
        }
        if ( is_array( $val ) ) {
            foreach ( $val as $k => $v ) {
                $val[$k] = $walk( $v );
            }
            return $val;
        }
        return $val;
    };
    return $walk( $posted_data );
}

function adfoin_ninjaforms_after_submission(  $form_data  ) {
    global $wpdb, $post;
    $saved_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = 'ninjaforms' AND form_id = %s", $form_data['form_id'] ), ARRAY_A );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    if ( isset( $form_data['fields'] ) && is_array( $form_data['fields'] ) ) {
        foreach ( $form_data['fields'] as $field ) {
            if ( isset( $field['id'] ) && isset( $field['value'] ) ) {
                if ( adfoin_fs()->is_not_paying() ) {
                    if ( 'firstname' == $field['type'] || 'lastname' == $field['type'] || 'email' == $field['type'] ) {
                        $posted_data[$field['id']] = $field['value'];
                    }
                }
            }
        }
    }
    if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $post_id = url_to_postid( wp_get_referer() );
        if ( $post_id ) {
            $post = get_post( $post_id, 'OBJECT' );
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data["submission_date"] = date( "Y-m-d H:i:s" );
    $posted_data["form_id"] = $form_data["form_id"];
    $posted_data["submission_id"] = adfoin_ninjaforms_get_submission_id( $form_data["actions"]["save"]["sub_id"] );
    $posted_data["user_ip"] = adfoin_get_user_ip();
    $job_queue = get_option( "adfoin_general_settings_job_queue" );
    // Expand Ninja Forms merge tags inside posted_data values before dispatching.
    $posted_data = adfoin_ninjaforms_expand_merge_tags_in_posted_data( $posted_data, $form_data );
    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record'      => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

function adfoin_ninjaforms_get_submission_id(  $post_id  ) {
    $submission_id = 0;
    if ( $post_id ) {
        $submission_id = get_post_meta( $post_id, '_seq_num', true );
    }
    return $submission_id;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_ninjaforms_trigger_fields' );
}
function adfoin_ninjaforms_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'ninjaforms'" is="ninjaforms" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php 
}

add_action( "adfoin_trigger_templates", "adfoin_ninjaforms_trigger_template" );
function adfoin_ninjaforms_trigger_template() {
    ?>
        <script type="text/template" id="ninjaforms-template">
            <tr valign="top" class="alternate" v-if="trigger.formId">
                <td scope="row-title">
                    <label for="tablecell">
                        <span class="dashicons dashicons-info-outline"></span>
                    </label>
                </td>
                <td>
                    <p>
                        <?php 
    esc_attr_e( 'The basic AFI plugin supports name and email fields only', 'advanced-form-integration' );
    ?>
                    </p>
                </td>
            </tr>
        </script>
    <?php 
}
