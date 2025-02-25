<?php

function adfoin_eform_get_forms( $form_provider ) {

    if ( $form_provider != 'eform' ) {
        return;
    }

    $raw_forms = IPT_FSQM_Form_Elements_Static::get_forms();
    $forms  = wp_list_pluck( $raw_forms, 'name', 'id' );

    return $forms;
}

function adfoin_eform_get_form_fields( $form_provider, $form_id ) {

    if ( $form_provider != 'eform' ) {
        return;
    }

    global $wpdb;

    $form = IPT_FSQM_Form_Elements_Static::get_form( $form_id );
    $pinfo        = maybe_unserialize( $form->pinfo );
    $freetype     = maybe_unserialize( $form->freetype );
    $fields1      = array();
    $fields2      = array();

    if ( is_array( $pinfo ) ) {
        foreach ( $pinfo as $key => $value ) {
            $fields1[ $key ] = $value['title'];
        }
    }

    if ( is_array( $freetype ) ) {
        foreach ( $freetype as $key => $value ) {
            $fields2[ $key ] = $value['title'];
        }
    }

    $special_tags = adfoin_get_special_tags();
    $all_fields   = $fields1 + $fields2 + $special_tags;

    return $all_fields;
}

/*
 * Get Form name by form id
 */
function adfoin_eform_get_form_name( $form_provider, $form_id ) {

    if ( $form_provider != "eform" ) {
        return;
    }

    global $wpdb;

    $form_name = $wpdb->get_var( "SELECT name FROM {$wpdb->prefix}fsq_form WHERE id = " . $form_id );

    return $form_name;
}

add_action( 'ipt_fsqm_hook_save_success', 'adfoin_eform_submission', 10, 1 );

function adfoin_eform_submission( $form ) {

    $form_id     = $form->form_id;

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eform', $form_id );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array();
    $submission = $form->post;
    $form_submission = isset( $submission['ipt_fsqm_form_' . $form_id] ) ? $submission['ipt_fsqm_form_' . $form_id] : array();

    if( isset( $form_submission['freetype'] ) ) {
        foreach ( $form_submission['freetype'] as $key => $value ) {
            $posted_data[ $key ] = $value['value'];
        }
    }

    if( isset( $form_submission['pinfo'] ) ) {
        foreach ( $form_submission['pinfo'] as $key => $value ) {
            $posted_data[ $key ] = $value['value'];
        }
    }


    global $post;

    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $integration->send( $saved_records, $posted_data );
}
