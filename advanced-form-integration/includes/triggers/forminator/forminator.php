<?php

function adfoin_forminator_get_forms(  $form_provider  ) {
    if ( $form_provider != 'forminator' ) {
        return;
    }
    $form_data = get_posts( array(
        'post_type'      => 'forminator_forms',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ) );
    $forms = wp_list_pluck( $form_data, "post_title", "ID" );
    return $forms;
}

function adfoin_forminator_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'forminator' ) {
        return;
    }
    if ( !$form_id ) {
        return;
    }
    $fields = array();
    $form_data = get_post_meta( $form_id );
    $meta = ( isset( $form_data['forminator_form_meta'][0] ) ? $form_data['forminator_form_meta'][0] : '' );
    $data = ( $meta ? maybe_unserialize( $meta ) : array() );
    $form_fields = ( is_array( $data ) && isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array() );
    foreach ( $form_fields as $field ) {
        // Skip display / structural / payment-processing types (and the group
        // container — its sub-fields are exposed individually below).
        if ( in_array( $field['type'], adfoin_forminator_excluded_field_types(), true ) ) {
            continue;
        }
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'name' == $field['type'] && 'true' == $field['multiple_name'] ) {
                $fields[$field['id'] . '-prefix'] = __( 'Prefix', 'advanced-form-integration' );
                $fields[$field['id'] . '-first-name'] = __( 'First Name', 'advanced-form-integration' );
                $fields[$field['id'] . '-middle-name'] = __( 'Middle Name', 'advanced-form-integration' );
                $fields[$field['id'] . '-last-name'] = __( 'Last Name', 'advanced-form-integration' );
                $fields[$field['id']] = __( 'Name', 'advanced-form-integration' );
            }
            if ( 'name' == $field['type'] && 'false' == $field['multiple_name'] ) {
                $fields[$field['id']] = ( isset( $field['field_label'] ) ? $field['field_label'] : $field['id'] );
            }
            if ( 'email' == $field['type'] ) {
                $fields[$field['id']] = ( isset( $field['field_label'] ) ? $field['field_label'] : $field['id'] );
            }
        }
    }
    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $fields['entry_id'] = __( 'Entry ID', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

/*
 * Get Form name by form id
 */
function adfoin_forminator_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != "forminator" ) {
        return;
    }
    $form = get_post( $form_id );
    return ( $form && isset( $form->post_title ) ? $form->post_title : '' );
}

/**
 * Forminator field types with no useful user value (display / structural /
 * payment-processing) and the group container (its sub-fields are exposed
 * individually). Excluded from the mappable field list. Filterable.
 */
function adfoin_forminator_excluded_field_types() {
    return apply_filters( 'adfoin_forminator_excluded_field_types', array(
        'html',
        'page-break',
        'section',
        'captcha',
        'paypal',
        'stripe',
        'stripe-payment-element',
        'group'
    ) );
}

/**
 * Map a Forminator form's group (repeater) fields to their sub-field ids:
 * array( '<group_id>' => array( '<sub_id>', ... ) ). Read from the form meta.
 */
function adfoin_forminator_get_group_subfields(  $form_id  ) {
    $map = array();
    $form_data = get_post_meta( $form_id );
    $meta = ( isset( $form_data['forminator_form_meta'][0] ) ? $form_data['forminator_form_meta'][0] : '' );
    $data = ( $meta ? maybe_unserialize( $meta ) : array() );
    if ( !is_array( $data ) || !isset( $data['fields'] ) || !is_array( $data['fields'] ) ) {
        return $map;
    }
    foreach ( $data['fields'] as $field ) {
        if ( !empty( $field['parent_group'] ) && !empty( $field['id'] ) ) {
            $map[$field['parent_group']][] = $field['id'];
        }
    }
    return $map;
}

add_action(
    'forminator_custom_form_submit_before_set_fields',
    'adfoin_forminator_submission',
    999,
    3
);
function adfoin_forminator_submission(  $entry, $form_id, $field_data_array  ) {
    global $post;
    // Resolve the source page for post-based special tags without clobbering
    // the global $post — use a local var.
    $resolved_post = $post;
    if ( !$resolved_post && wp_get_referer() ) {
        $resolved_post = get_post( url_to_postid( wp_get_referer() ) );
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'forminator', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    foreach ( $_POST as $key => $value ) {
        // Forminator field keys look like "<type>-<n>" (e.g. name-1, email-1,
        // name-1-first-name). Skip everything else (nonce, action, form_id,
        // referer, render_id, …) so internals don't leak into the payload.
        if ( !preg_match( '/-\\d+/', (string) $key ) ) {
            continue;
        }
        if ( adfoin_fs()->is_not_paying() ) {
            if ( strpos( $key, 'name' ) !== false ) {
                $posted_data[$key] = adfoin_sanitize_text_or_array_field( $value );
            }
            if ( strpos( $key, 'email' ) !== false ) {
                $posted_data[$key] = adfoin_sanitize_text_or_array_field( $value );
            }
        }
    }
    // Group (repeater) fields: collect each sub-field's value across all
    // repetitions into an array (<sub_id>_group) so it can be expanded one row
    // per repetition. Copy 1 uses the base key; copies 2+ append "-<n>".
    if ( adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) {
        $has_group = false;
        foreach ( $_POST as $pk => $pv ) {
            if ( false !== strpos( (string) $pk, '-copies' ) ) {
                $has_group = true;
                break;
            }
        }
        if ( $has_group ) {
            $group_map = adfoin_forminator_get_group_subfields( $form_id );
            foreach ( $group_map as $group_id => $sub_ids ) {
                $copies = ( isset( $_POST[$group_id . '-copies'] ) ? (array) $_POST[$group_id . '-copies'] : array() );
                if ( !$copies ) {
                    continue;
                }
                foreach ( $sub_ids as $sub_id ) {
                    $values = array();
                    foreach ( $copies as $idx ) {
                        $idx = intval( $idx );
                        $copy_key = ( $idx <= 1 ? $sub_id : $sub_id . '-' . $idx );
                        $values[] = ( isset( $_POST[$copy_key] ) ? adfoin_sanitize_text_or_array_field( $_POST[$copy_key] ) : '' );
                    }
                    $posted_data[$sub_id . '_group'] = $values;
                }
            }
        }
    }
    $posted_data["submission_date"] = current_time( "mysql" );
    $posted_data["user_ip"] = adfoin_get_user_ip();
    $special_tag_values = adfoin_get_special_tags_values( $resolved_post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['form_id'] = $form_id;
    // Forminator's `forminator_custom_form_submit_before_set_fields` hook fires
    // after the entry row is inserted (so entry_id is set) but before field
    // data is persisted. Guard with isset() — older Forminator versions may
    // not have it populated yet, in which case we resolve to ''.
    $posted_data['entry_id'] = ( isset( $entry ) && is_object( $entry ) && !empty( $entry->entry_id ) ? $entry->entry_id : '' );
    adfoin_dispatch_integrations( $saved_records, $posted_data );
    return;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_forminator_trigger_fields' );
}
function adfoin_forminator_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'forminator' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
