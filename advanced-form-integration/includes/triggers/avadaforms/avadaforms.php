<?php

// Get Forms List
function adfoin_avadaforms_get_forms( $form_provider ) {

    if( $form_provider != 'avadaforms' ) {
        return;
    }

    global $wpdb;
    $raw = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}fusion_forms", ARRAY_A );
    $forms = wp_list_pluck( $raw, 'name', 'id' );

    return $forms;
}

// Get Form Fields
function adfoin_avadaforms_get_form_fields( $form_provider, $form_id ) {

    if( $form_provider != 'avadaforms' ) {
        return;
    }

    global $wpdb;
    $raw = $wpdb->get_results("SELECT id, field_key, label, field_type FROM {$wpdb->prefix}fusion_form_fields WHERE form_id = {$form_id}");
    $fields = wp_list_pluck( $raw, 'label', 'field_key' );

    $special_tags = adfoin_get_special_tags();

    if( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

// Get Form Name
function adfoin_avadaforms_get_form_name( $form_id ) {
    global $wpdb;
    $form_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}fusion_forms WHERE id = {$form_id}");
    return $form_name;
}

// Hook into form submission
add_action( 'fusion_form_submission', 'adfoin_avadaforms_submission', 10, 2 );

function adfoin_avadaforms_submission( $form_id, $form_data ) {

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'avadaforms', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = $form_data;
    $post = adfoin_get_post_object();
    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $posted_data = apply_filters( 'adfoin_avadaforms_submission', $posted_data, $form_id );

    $integration->send( $saved_records, $posted_data );
}