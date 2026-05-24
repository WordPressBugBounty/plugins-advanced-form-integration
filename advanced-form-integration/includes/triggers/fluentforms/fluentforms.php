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
        'payment_method'
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
    if ( in_array( $single_field->element, $field_list ) ) {
        $fields[$single_field->attributes->name] = $single_field->settings->label;
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
    foreach ( $data->fields as $single_field ) {
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

function adfoin_fluentforms_transform_form_fields(  $fields  ) {
    $data = [];
    foreach ( $fields['fields'] as $field ) {
        if ( !array_key_exists( 'name', $field['attributes'] ) ) {
            continue;
        }
        if ( adfoin_fluentforms_has_sub_fields( $field ) ) {
            $data = array_merge( $data, adfoin_fluentforms_get_sub_fields( $field ) );
            continue;
        }
        $data[] = [
            'id'    => $field['attributes']['name'],
            'label' => adfoin_fluentforms_get_label( $field['attributes']['name'] ),
        ];
    }
    return $data;
}

function adfoin_fluentforms_has_sub_fields(  $field  ) {
    return array_key_exists( 'fields', $field );
}

function adfoin_fluentforms_get_sub_fields(  $field  ) {
    $data = [];
    foreach ( $field['fields'] as $sub_field ) {
        if ( !array_key_exists( 'name', $sub_field['attributes'] ) ) {
            continue;
        }
        $data[] = [
            'id'    => $sub_field['attributes']['name'],
            'label' => adfoin_fluentforms_get_label( $sub_field['attributes']['name'] ),
        ];
    }
    return $data;
}

function adfoin_fluentforms_get_label(  $label  ) {
    return ucwords( str_replace( ['-', '_'], [' ', ' '], $label ) );
}

function adfoin_fluentforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != "fluentforms" ) {
        return;
    }
    $form = wpFluent()->table( 'fluentform_forms' )->select( 'title' )->where( 'id', $form_id )->first();
    return $form->title;
}

// add_action( 'fluentform_partial_submission_step_completed', 'adfoin_fluentforms_partial_submission', 99, 4 );
// function adfoin_fluentforms_partial_submission( $step, $data, $exist_id, $form_id ) {
//     $data['form_id']            = $form_id;
//     $data['submission_type']    = 'partial';
//     adfoin_fluentforms_submission( $data );
// }
add_action(
    "fluentform/submission_inserted",
    "adfoin_fluentforms_submission",
    20,
    3
);
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
        if ( adfoin_fs()->is_not_paying() ) {
            if ( strpos( $key, 'names' ) !== false ) {
                $number = intval( str_replace( 'names_', '', $key ) );
                if ( $number > 0 ) {
                    if ( is_array( $value ) ) {
                        foreach ( $value as $name_part_key => $name_part_value ) {
                            $all_data[strval( $number ) . '_' . $name_part_key] = $name_part_value;
                        }
                    }
                } else {
                    $all_data = $all_data + $value;
                }
                $all_data[$key] = $value;
            }
            if ( strpos( $key, 'email' ) !== false ) {
                $all_data[$key] = $value;
            }
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $special_tag_values ) ) {
        $all_data = array_merge( $all_data, $special_tag_values );
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
