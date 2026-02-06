<?php

// Get Forms List
function adfoin_userregistration_get_forms( $form_provider ) {
    if ( $form_provider != 'userregistration' ) {
        return;
    }

    $all_forms = ur_get_all_user_registration_form();

    return $all_forms;
}

// Get Form Fields
function adfoin_userregistration_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'userregistration' ) {
        return;
    }
    
    $form_data = UR()->form->get_form( $form_id );
    $fields = array();
    $data = json_decode( $form_data->post_content, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return $fields;
    }

    foreach ( $data as $group ) {
        foreach ( $group as $inputs ) {
            foreach ( $inputs as $field ) {
                $field_key = $field['general_setting']['field_name'];
                $label = $field['general_setting']['label'];
                $fields[$field_key] = $label;
            }
        }
    }

    $fields['user_id'] = __( 'User ID', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

// Process User Registration Submission
add_action( 'user_registration_after_user_meta_update', 'adfoin_userregistration_submission', 10, 3 );

function adfoin_userregistration_submission( $form_data, $form_id, $user_id ) {
    
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'userregistration', $form_id );
    
    if ( empty( $saved_records ) ) {
        return;
    }
    
    $posted_data = array();

    foreach ( $form_data as $key => $value ) {
        $posted_data[$key] = $value->value;
    }

    $posted_data['user_id'] = $user_id;

    $post = adfoin_get_post_object();
    $special_tag_values = adfoin_get_special_tags_values( $post );
    
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    
    $integration->send( $saved_records, $posted_data );
}
?>
