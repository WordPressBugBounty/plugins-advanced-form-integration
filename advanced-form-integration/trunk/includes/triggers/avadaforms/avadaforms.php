<?php

// Get Forms List
function adfoin_avadaforms_get_forms( $form_provider ) {

    if( $form_provider != 'avadaforms' ) {
        return;
    }

    global $wpdb;
    $raw = $wpdb->get_results( "SELECT id, form_id FROM {$wpdb->prefix}fusion_forms", ARRAY_A );

    foreach ( $raw as &$form ) {
        $form['title'] = get_the_title( $form['form_id'] );
    }

    $forms = wp_list_pluck( $raw, 'title', 'id' );

    return $forms;
}

// Get Form Fields
function adfoin_avadaforms_get_form_fields( $form_provider, $form_id ) {

    if( $form_provider != 'avadaforms' ) {
        return;
    }

    global $wpdb;
    $raw = $wpdb->get_results("SELECT id, field_name, field_label FROM {$wpdb->prefix}fusion_form_fields WHERE form_id = {$form_id}");
    $fields = wp_list_pluck( $raw, 'field_label', 'field_name' );

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
add_action( 'fusion_form_submission_data', 'adfoin_avadaforms_submission', 10, 2 );

function adfoin_avadaforms_submission( $data, $form_post_id ) {

    $form_id = $data['submission']['form_id'];
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'avadaforms', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = $data['data'];
    $post = adfoin_get_post_object( $form_post_id );
    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $posted_data = apply_filters( 'adfoin_avadaforms_submission', $posted_data, $form_id );

    $integration->send( $saved_records, $posted_data );
}