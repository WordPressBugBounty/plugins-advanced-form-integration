<?php

function adfoin_gravityforms_get_forms(  $form_provider  ) {
    if ( $form_provider != 'gravityforms' ) {
        return;
    }
    if ( !class_exists( 'GFAPI' ) ) {
        return array();
    }
    $result = GFAPI::get_forms();
    $forms = wp_list_pluck( $result, 'title', 'id' );
    return $forms;
}

function adfoin_gravityforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'gravityforms' ) {
        return;
    }
    if ( !class_exists( 'GFAPI' ) ) {
        return array();
    }
    $form = GFAPI::get_form( $form_id );
    $fields = array();
    // GFAPI::get_form() returns false for a deleted/invalid form id.
    $raw_fields = ( $form && isset( $form['fields'] ) ? json_decode( json_encode( $form['fields'] ) ) : array() );
    $raw_fields = ( is_array( $raw_fields ) ? $raw_fields : array() );
    foreach ( $raw_fields as $field ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( $field->type == 'name' || $field->type == 'email' ) {
                if ( $field->inputs ) {
                    foreach ( $field->inputs as $input ) {
                        $fields[$input->id] = $input->label;
                    }
                    continue;
                }
                $fields[$field->id] = $field->label;
            }
        }
    }
    // Gravity Forms entry metadata — basic identifiers users frequently need
    // for downstream automations (e.g. linking a CRM contact back to the
    // submission, or building a row key in Google Sheets). Available to free
    // and Pro alike since these are entry meta, not form fields.
    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $fields['entry_id'] = __( 'Entry ID', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_gravityforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != "gravityforms" ) {
        return;
    }
    if ( !class_exists( 'GFAPI' ) ) {
        return '';
    }
    $form = GFAPI::get_form( $form_id );
    return ( $form && isset( $form['title'] ) ? $form['title'] : '' );
}

// add_action( 'gform_partialentries_post_entry_saved', 'adfoin_gravityforms_after_submission', 10, 2 );
/**
 * Return a Gravity Forms List field's column labels (empty array for a
 * single-column list). Works with both the stdClass field used by the field
 * list and the GF_Field object available during submission.
 */
function adfoin_gravityforms_list_columns(  $field  ) {
    if ( empty( $field ) ) {
        return array();
    }
    if ( is_object( $field ) ) {
        $choices = ( isset( $field->choices ) ? $field->choices : null );
    } elseif ( is_array( $field ) ) {
        $choices = ( isset( $field['choices'] ) ? $field['choices'] : null );
    } else {
        $choices = null;
    }
    if ( !is_array( $choices ) || empty( $choices ) ) {
        return array();
    }
    $labels = array();
    foreach ( $choices as $choice ) {
        if ( is_object( $choice ) ) {
            $labels[] = ( isset( $choice->text ) ? (string) $choice->text : '' );
        } elseif ( is_array( $choice ) ) {
            $labels[] = ( isset( $choice['text'] ) ? (string) $choice['text'] : '' );
        } else {
            $labels[] = '';
        }
    }
    return $labels;
}

add_action(
    'gform_after_submission',
    'adfoin_gravityforms_after_submission',
    10,
    2
);
function adfoin_gravityforms_after_submission(  $entry, $form  ) {
    if ( isset( $entry['status'] ) && 'spam' == $entry['status'] ) {
        return;
    }
    global $post;
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'gravityforms', $entry['form_id'] );
    if ( empty( $saved_records ) ) {
        return;
    }
    $fields = $form['fields'];
    $field_types = array();
    $field_lookup = array();
    foreach ( $fields as $key => $value ) {
        $field_types[$value['id']] = $value['type'];
        // Keep the full field object too — Survey resolution needs access to
        // the `choices` array and (for Likert) the `gsurveyLikertRows` /
        // `gsurveyLikertEnableMultipleRows` properties.
        $field_lookup[$value['id']] = $value;
    }
    $posted_data = array();
    $last_key = '';
    foreach ( $entry as $key => $value ) {
        $intkey = intval( $key );
        if ( $last_key !== $intkey ) {
            $field_type = ( isset( $field_types[$intkey] ) ? $field_types[$intkey] : '' );
            if ( adfoin_fs()->is_not_paying() ) {
                if ( 'name' == $field_type || 'email' == $field_type ) {
                    $posted_data[$key] = $value;
                }
            }
        } else {
            // Sub-key (e.g. `1.2` for Likert row 2) — skip if the parent
            // field is a survey since we already resolved every sub-key above.
            $parent_type = ( isset( $field_types[$intkey] ) ? $field_types[$intkey] : '' );
            if ( 'survey' !== $parent_type ) {
                $posted_data[$key] = $value;
            }
        }
        $last_key = $intkey;
    }
    $posted_data['submission_date'] = current_time( 'mysql' );
    $posted_data['form_id'] = ( isset( $entry['form_id'] ) ? $entry['form_id'] : '' );
    $posted_data['entry_id'] = ( isset( $entry['id'] ) ? $entry['id'] : '' );
    // Resolve the entry's source page so post-based special tags ({{post_id}},
    // {{post_title}}, ...) resolve. Fall back to the global $post when the source
    // URL doesn't map to a singular post. Local var — never clobbers the global.
    $resolved_post = $post;
    $source_url = ( isset( $entry['source_url'] ) ? $entry['source_url'] : '' );
    if ( $source_url ) {
        $source_post_id = url_to_postid( $source_url );
        if ( $source_post_id ) {
            $resolved_post = get_post( $source_post_id );
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $resolved_post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

/**
 * Resolve a Gravity Forms Survey Add-On field's entry values to their
 * human-readable display text.
 *
 * The Survey Add-On stores the internal choice value (`gsurvey<hash>`) in
 * the entry, not the user-visible label. Without this resolver the
 * downstream integration (CRM, Sheets row, etc.) receives gibberish like
 * `gsurvey504d2075fc` instead of "Strongly Agree".
 *
 * Handles every Survey sub-type the add-on supports:
 *
 *   - radio / select / checkbox: single value at entry key `<id>`
 *   - rating (stars): textual label already, no resolution needed
 *   - rank: comma-separated values, each resolved against `choices`
 *   - text / textarea: plain text, no resolution needed
 *   - likert single-row: single value at entry key `<id>`
 *   - likert multi-row: per-row values at `<id>.1`, `<id>.2`, … each
 *     resolved against the column `choices`; the row label is the
 *     human-readable row name from `gsurveyLikertRows`
 *
 * Prefers the field class's own `get_value_export($entry, $input_id, true)`
 * when the Survey Add-On exposes it (most reliable, handles future
 * sub-types). Falls back to manual choice resolution if the method isn't
 * available or the add-on field class isn't loaded.
 *
 * @param object|array|null $field    The form field definition (object or array).
 * @param array             $entry    The Gravity Forms entry array.
 * @param int               $field_id Numeric field id.
 * @return array  Map of entry-key → display value. Caller merges into posted_data.
 */
function adfoin_gravityforms_resolve_survey_field(  $field, $entry, $field_id  ) {
    if ( empty( $field ) ) {
        return array();
    }
    $field_arr = ( is_object( $field ) ? json_decode( wp_json_encode( $field ), true ) : (array) $field );
    $is_likert_multi = !empty( $field_arr['gsurveyLikertEnableMultipleRows'] );
    // Collect every entry key that belongs to this survey field.
    $survey_keys = array();
    foreach ( $entry as $entry_key => $entry_val ) {
        if ( (string) intval( $entry_key ) !== (string) $field_id ) {
            continue;
        }
        $survey_keys[] = $entry_key;
    }
    if ( !$survey_keys ) {
        $survey_keys = array((string) $field_id);
    }
    // 1. Preferred path — the Survey Add-On's field class implements
    // get_value_export() and returns the display text when $use_text=true.
    if ( is_object( $field ) && method_exists( $field, 'get_value_export' ) ) {
        $resolved = array();
        foreach ( $survey_keys as $sk ) {
            $input_id = ( strpos( (string) $sk, '.' ) !== false ? (string) $sk : '' );
            try {
                $resolved[$sk] = $field->get_value_export( $entry, $input_id, true );
            } catch ( Exception $e ) {
                $resolved[$sk] = ( isset( $entry[$sk] ) ? $entry[$sk] : '' );
            }
        }
        return $resolved;
    }
    // 2. Manual fallback — look up the raw entry value in the field's
    // `choices` array. `choices` is an array of { value, text } pairs.
    $choices = ( isset( $field_arr['choices'] ) && is_array( $field_arr['choices'] ) ? $field_arr['choices'] : array() );
    $value_to_text = array();
    foreach ( $choices as $choice ) {
        if ( isset( $choice['value'], $choice['text'] ) ) {
            $value_to_text[(string) $choice['value']] = (string) $choice['text'];
        }
    }
    // Likert multi-row: also build the row-id → row-text map so the resolved
    // payload looks like "<Row Label>: <Column Label>" rather than just the
    // column label (the row context would otherwise be lost).
    $row_lookup = array();
    if ( $is_likert_multi && !empty( $field_arr['gsurveyLikertRows'] ) && is_array( $field_arr['gsurveyLikertRows'] ) ) {
        foreach ( $field_arr['gsurveyLikertRows'] as $idx => $row ) {
            if ( isset( $row['value'], $row['text'] ) ) {
                $row_lookup[(string) ($idx + 1)] = (string) $row['text'];
            }
        }
    }
    $resolved = array();
    foreach ( $survey_keys as $sk ) {
        $raw = ( isset( $entry[$sk] ) ? (string) $entry[$sk] : '' );
        if ( '' === $raw ) {
            $resolved[$sk] = '';
            continue;
        }
        // Rank fields store comma-separated values.
        if ( strpos( $raw, ',' ) !== false ) {
            $parts = array_map( 'trim', explode( ',', $raw ) );
            $mapped = array();
            foreach ( $parts as $p ) {
                $mapped[] = ( isset( $value_to_text[$p] ) ? $value_to_text[$p] : $p );
            }
            $resolved[$sk] = implode( ', ', $mapped );
            continue;
        }
        $display = ( isset( $value_to_text[$raw] ) ? $value_to_text[$raw] : $raw );
        // Multi-row Likert: prefix with the row label so the downstream
        // payload tells you which row this answer belongs to.
        if ( $is_likert_multi && strpos( (string) $sk, '.' ) !== false ) {
            list( , $row_idx ) = explode( '.', (string) $sk, 2 );
            if ( isset( $row_lookup[$row_idx] ) ) {
                $display = $row_lookup[$row_idx] . ': ' . $display;
            }
        }
        $resolved[$sk] = $display;
    }
    return $resolved;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_gravityforms_trigger_fields' );
}
/**
 * Render the free-tier upgrade notice inside the Step 2 trigger card.
 *
 * Shown only when the user has selected Gravity Forms as the form
 * provider AND picked a specific form. Hidden automatically in the Pro
 * plugin because the action above is only registered for non-paying
 * users.
 */
function adfoin_gravityforms_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'gravityforms' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
