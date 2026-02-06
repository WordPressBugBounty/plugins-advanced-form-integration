<?php

// Get Forms List
function adfoin_convertpro_get_forms( $form_provider ) {

    if( $form_provider != 'convertpro' ) {
        return;
    }
    
    $raw = get_posts( array(
        'post_type' => 'cp_popups',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ) );
    
    $forms = wp_list_pluck( $raw, 'post_title', 'ID' );

    return $forms;
}

// Get Form Fields
function adfoin_convertpro_get_form_fields( $form_provider, $form_id ) {

    if( $form_provider != 'convertpro' ) {
        return;
    }

    $raw = get_post_meta( $form_id, 'cp_modal_data', true );
    $settings = json_decode( $raw, true );
    $fields = array();

    if( isset( $settings[0] ) && is_array( $settings[0] ) ) {
        foreach( $settings[0] as $field ) {
            if( $field['type'] == 'cp_email' ) {
                $fields['email'] = 'Email';
            }

            if( $field['type'] == 'cp_text' ) {
                $fields[$field['input_text_name']] = $field['input_text_placeholder'];
            }

            if( $field['type'] == 'cp_number' ) {
                $fields[$field['input_text_name']] = $field['input_text_placeholder'];
            }

            if( $field['type'] == 'cp_textarea' ) {
                $fields[$field['dropdown_name']] = $field['input_text_placeholder'];
            }

            if( $field['type'] == 'cp_dropdown' ) {
                $fields[$field['input_text_name']] = $field['input_text_placeholder'];
            }

            if( $field['type'] == 'cp_radio' ) {
                $fields[$field['radio_name']] = $field['input_text_placeholder'];
            }

            if( $field['type'] == 'cp_checkbox' ) {
                $fields[$field['checkbox_name']] = $field['input_text_placeholder'];
            }

            if( $field['type'] == 'cp_date' ) {
                $fields[$field['date_name']] = $field['date_placeholder'];
            }

            if( $field['type'] == 'cp_hidden_input' ) {
                $fields[$field['hidden_input_name']] = $field['hidden_input_name'];
            }
        }
    }

    return $fields;
}

add_action( 'cpro_form_submit', 'adfoin_convertpro_submission', 10, 2 );

function adfoin_convertpro_submission( $response, $post_data ) {

    $form_id = (int) $post_data['style_id'];

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'convertpro', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    global $post;

    $posted_data = $post_data['param'];

    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $posted_data = apply_filters( 'adfoin_convertpro_submission', $posted_data, $form_id );

    $integration->send( $saved_records, $posted_data );
}