<?php

// Get Forms List
function adfoin_avadaforms_get_forms( $form_provider ) {

    if( $form_provider != 'avadaforms' ) {
        return;
    }

    // Avada only adds a row to the fusion_forms table after the first submission,
    // so list the form posts directly to include forms that have not been submitted yet.
    $posts = get_posts(
        array(
            'post_type'      => 'fusion_form',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    $forms = wp_list_pluck( $posts, 'post_title', 'ID' );

    return $forms;
}

// Get Form Fields
function adfoin_avadaforms_get_form_fields( $form_provider, $form_id ) {

    if( $form_provider != 'avadaforms' ) {
        return;
    }

    $post_id = adfoin_avadaforms_get_post_id( $form_id );
    $fields  = array();
    $post    = $post_id ? get_post( $post_id ) : null;

    if( $post && preg_match_all( '/\[(fusion_form_[a-z_]+)([^\]]*)\]/', $post->post_content, $matches, PREG_SET_ORDER ) ) {
        foreach( $matches as $match ) {
            if( 'fusion_form_submit' == $match[1] ) {
                continue;
            }

            $atts = shortcode_parse_atts( rtrim( $match[2], ' /' ) );

            if( empty( $atts['name'] ) ) {
                continue;
            }

            $fields[$atts['name']] = !empty( $atts['label'] ) ? $atts['label'] : $atts['name'];
        }
    }

    // Fall back to fields stored by Avada on previous submissions.
    if( empty( $fields ) && $post_id ) {
        global $wpdb;
        $raw = $wpdb->get_results( $wpdb->prepare( "SELECT ff.field_name, ff.field_label FROM {$wpdb->prefix}fusion_form_fields ff INNER JOIN {$wpdb->prefix}fusion_forms f ON f.id = ff.form_id WHERE f.form_id = %d", $post_id ) );
        $fields = wp_list_pluck( $raw, 'field_label', 'field_name' );
    }

    $fields['form_id']  = __( 'Form ID', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

// Get Form Name
function adfoin_avadaforms_get_form_name( $form_id ) {
    $post_id = adfoin_avadaforms_get_post_id( $form_id );

    return $post_id ? get_the_title( $post_id ) : '';
}

// Resolve a form post ID. Older integrations were saved with the fusion_forms table ID.
function adfoin_avadaforms_get_post_id( $form_id ) {
    if( 'fusion_form' == get_post_type( $form_id ) ) {
        return absint( $form_id );
    }

    global $wpdb;
    $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT form_id FROM {$wpdb->prefix}fusion_forms WHERE id = %d", $form_id ) );

    return absint( $post_id );
}

// Hook into form submission
add_action( 'fusion_form_submission_data', 'adfoin_avadaforms_submission', 10, 2 );

function adfoin_avadaforms_submission( $data, $form_post_id ) {

    $table_form_id = isset( $data['submission']['form_id'] ) ? $data['submission']['form_id'] : '';
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'avadaforms', $form_post_id );

    // Integrations saved before forms were keyed by post ID use the fusion_forms table ID.
    if( $table_form_id && $table_form_id != $form_post_id ) {
        $legacy_records = $integration->get_by_trigger( 'avadaforms', $table_form_id );

        if( !empty( $legacy_records ) ) {
            $saved_records = array_merge( (array) $saved_records, $legacy_records );
        }
    }

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = $data['data'];
    $post = adfoin_get_post_object( $form_post_id );
    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $posted_data = apply_filters( 'adfoin_avadaforms_submission', $posted_data, $form_post_id );

    $posted_data['form_id'] = $form_post_id;

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
