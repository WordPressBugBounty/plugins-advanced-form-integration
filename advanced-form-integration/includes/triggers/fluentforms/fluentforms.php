<?php

function adfoin_fluentforms_get_forms(  $form_provider  ) {
    if ( $form_provider != 'fluentforms' ) {
        return;
    }
    global $wpdb;
    $query = "SELECT id, title FROM {$wpdb->prefix}fluentform_forms";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $forms = wp_list_pluck( $result, 'title', 'id' );
    return $forms;
}

function adfoin_fluentforms_get_single_field(  $single_field  ) {
    $fields = array();
    $field_list = array(
        'input_email',
        'input_text',
        'textarea',
        'select_country',
        'input_number',
        'select',
        'input_radio',
        'input_checkbox',
        'input_url',
        'input_date',
        'input_image',
        'input_file',
        'phone',
        'custom_payment_component',
        'multi_payment_component',
        'subscription_payment_component',
        'item_quantity_component',
        'payment_method',
        // Fluent Forms Pro field types
        'net_promoter_score',
        'rangeslider',
        'chained_select',
        'cpt_selection',
        'color_picker',
        'dynamic_field',
        'payment_coupon',
        'quiz_score',
        // Post / CPT creation (Post Feed) fields
        'post_title',
        'post_content',
        'post_excerpt',
        'post_update',
        'featured_image',
        'taxonomy',
    );
    if ( 'address' == $single_field->element ) {
        $fields[$single_field->attributes->name . '_address_line_1'] = $single_field->settings->label . ' Address Line 1';
        $fields[$single_field->attributes->name . '_address_line_2'] = $single_field->settings->label . ' Address Line 2';
        $fields[$single_field->attributes->name . '_city'] = $single_field->settings->label . ' City';
        $fields[$single_field->attributes->name . '_state'] = $single_field->settings->label . ' State';
        $fields[$single_field->attributes->name . '_zip'] = $single_field->settings->label . ' Zip';
        $fields[$single_field->attributes->name . '_country'] = $single_field->settings->label . ' Country';
    }
    if ( 'input_name' == $single_field->element ) {
        $number = intval( str_replace( 'names_', '', $single_field->attributes->name ) );
        $fn = 'first_name';
        $mn = 'middle_name';
        $ln = 'last_name';
        if ( $number > 0 ) {
            $fn = strval( $number ) . '_' . $fn;
            $mn = strval( $number ) . '_' . $mn;
            $ln = strval( $number ) . '_' . $ln;
        }
        $fields[$fn] = 'First Name';
        $fields[$mn] = 'Middle Name';
        $fields[$ln] = 'Last Name';
    }
    // Repeater field: expose one tag per column (positional). The submission
    // handler fills <name>_col_<index> with that column's value from every row,
    // so columns can be mapped individually and feed the "one row per line item /
    // repeater entry" expansion, like WooCommerce line items.
    if ( 'repeater_field' == $single_field->element ) {
        $repeater_name = ( isset( $single_field->attributes->name ) ? $single_field->attributes->name : '' );
        $repeater_label = ( isset( $single_field->settings->label ) ? $single_field->settings->label : $repeater_name );
        $columns = ( isset( $single_field->fields ) && is_array( $single_field->fields ) ? $single_field->fields : array() );
        if ( '' !== $repeater_name ) {
            foreach ( $columns as $index => $column ) {
                $col_label = ( isset( $column->settings->label ) ? $column->settings->label : 'Column ' . ($index + 1) );
                $fields[$repeater_name . '_col_' . $index] = $repeater_label . ' - ' . $col_label;
            }
        }
    }
    if ( in_array( $single_field->element, $field_list ) ) {
        $name = ( isset( $single_field->attributes->name ) ? $single_field->attributes->name : '' );
        $label = ( isset( $single_field->settings->label ) ? $single_field->settings->label : $name );
        if ( '' !== $name ) {
            $fields[$name] = $label;
        }
    }
    return $fields;
}

function adfoin_fluentforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'fluentforms' ) {
        return;
    }
    global $wpdb;
    $query = $wpdb->prepare( "SELECT form_fields FROM {$wpdb->prefix}fluentform_forms WHERE id = %d", $form_id );
    $result = $wpdb->get_var( $query );
    $data = json_decode( $result );
    $fields = array();
    $form_field_list = ( isset( $data->fields ) && is_array( $data->fields ) ? $data->fields : array() );
    foreach ( $form_field_list as $single_field ) {
        if ( 'container' == $single_field->element ) {
            foreach ( $single_field->columns as $single_column ) {
                foreach ( $single_column->fields as $single_column_field ) {
                    if ( adfoin_fs()->is_not_paying() ) {
                        if ( 'input_name' == $single_column_field->element || 'input_email' == $single_column_field->element ) {
                            $single_field_value = adfoin_fluentforms_get_single_field( $single_column_field );
                            $fields = $fields + $single_field_value;
                        }
                    }
                }
            }
            continue;
        }
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'input_name' == $single_field->element || 'input_email' == $single_field->element ) {
                $single_field_value = adfoin_fluentforms_get_single_field( $single_field );
                $fields = $fields + $single_field_value;
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

function adfoin_fluentforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != "fluentforms" ) {
        return;
    }
    $form = wpFluent()->table( 'fluentform_forms' )->select( 'title' )->where( 'id', $form_id )->first();
    return ( $form ? $form->title : '' );
}

add_action(
    "fluentform/submission_inserted",
    "adfoin_fluentforms_submission",
    20,
    3
);
/**
 * True when an array value looks like a Fluent Forms address field (keyed by
 * address sub-fields). Lets us flatten address fields by value shape, so a
 * renamed address field is handled the same as the default "address_*" one.
 */
function adfoin_fluentforms_is_address_value(  $value  ) {
    if ( !is_array( $value ) ) {
        return false;
    }
    $address_keys = array(
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'country'
    );
    return count( array_intersect( array_keys( $value ), $address_keys ) ) > 0;
}

/**
 * True when an array value looks like a Fluent Forms repeater submission: an
 * array of rows where each row is itself an array of column values.
 */
function adfoin_fluentforms_is_repeater_value(  $value  ) {
    return is_array( $value ) && !empty( $value ) && is_array( reset( $value ) );
}

function adfoin_fluentforms_submission(  $entryId, $formData, $form  ) {
    $form_id = $form->id;
    global $wpdb, $post;
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'fluentforms', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    // Use the formData directly as it's already in the correct format
    $posted_data = $formData;
    $all_data = array();
    foreach ( $posted_data as $key => $value ) {
        // Precise name-field match ("names" or "names_<n>") so unrelated fields
        // like "usernames" / "company_names" are not treated as name fields.
        $is_name_field = 'names' === $key || 0 === strpos( $key, 'names_' );
        if ( adfoin_fs()->is_not_paying() ) {
            // Free plan: name + email fields only.
            if ( $is_name_field ) {
                $number = intval( str_replace( 'names_', '', $key ) );
                if ( is_array( $value ) ) {
                    if ( $number > 0 ) {
                        foreach ( $value as $name_part_key => $name_part_value ) {
                            $all_data[strval( $number ) . '_' . $name_part_key] = $name_part_value;
                        }
                    } else {
                        $all_data = $all_data + $value;
                    }
                }
                $all_data[$key] = $value;
            }
            if ( false !== strpos( $key, 'email' ) ) {
                $all_data[$key] = $value;
            }
        }
    }
    // Resolve the entry's source page so post-based special tags ({{post_id}},
    // {{post_title}}, ...) resolve. Fall back to the global $post when the source
    // URL doesn't map to a singular post. A local var is used so the global
    // $post is never clobbered.
    $resolved_post = $post;
    $source_url = $wpdb->get_var( $wpdb->prepare( "SELECT source_url FROM {$wpdb->prefix}fluentform_submissions WHERE id = %d", $entryId ) );
    if ( $source_url ) {
        $source_post_id = url_to_postid( $source_url );
        if ( $source_post_id ) {
            $resolved_post = get_post( $source_post_id );
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $resolved_post );
    // Submitted form data wins over special tags on key collisions (consistent
    // with the other triggers) — union instead of array_merge.
    if ( is_array( $special_tag_values ) ) {
        $all_data = $all_data + $special_tag_values;
    }
    $all_data['form_id'] = $form_id;
    $all_data['entry_id'] = ( isset( $entryId ) ? $entryId : '' );
    adfoin_dispatch_integrations( $saved_records, $all_data );
    return;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_fluentforms_trigger_fields' );
}
function adfoin_fluentforms_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'fluentforms' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
