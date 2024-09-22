<?php

// Get Brizy Forms
function adfoin_brizy_get_forms( $form_provider ) {

    if( $form_provider != 'brizy' ) {
        return;
    }

    $all_forms = array();

    $posts = get_posts( array(
        'post_type' => array( 'post', 'page', 'editor-template' ),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'brizy',
            ),
        ),
    ) );

    if( empty( $posts ) && !is_array( $posts ) ) {
        return;
    }

    $all_forms = array();

    foreach ( $posts as $post ) {
        $post_meta = get_post_meta( $post->ID, 'brizy', true );
        $meta_content = base64_decode( $post_meta[0]['brizy-post']['compiled_html'] );

        foreach ( $post_meta as $form ) {
            $all_forms[$post->ID . '_' . $form['id']] = $form['label'] ? $form['label'] : 'Untitled form ' . $form['id'];

            $dom = new DOMDocument();
            $dom->loadHTML( $meta_content );
            $xpath = new DOMXPath( $dom );
            $form = $xpath->query( '//*[@id="' . $form['id'] . '"]' );

            if( $form->length > 0 ) {
                $form = $form->item(0);
                $form_fields = $form->getElementsByTagName( 'input' );

                foreach ( $form_fields as $field ) {
                    $all_forms[$post->ID . '_' . $form['id']]['fields'][$field->getAttribute( 'name' )] = $field->getAttribute( 'name' );
                }
            }
        }
    }

    return $all_forms;
}

// Get Brizy Form Fields
function adfoin_brizy_get_form_fields( $form_provider, $form_id ) {
    
        if( $form_provider != 'brizy' ) {
            return;
        }
    
        $post_id = intval( explode( '_', $form_id )[0] );
    
        if( empty( $post_id ) ) {
            return;
        }
    
        $post_meta = get_post_meta( $post_id, 'brizy', true );
        $meta_content = base64_decode( $post_meta[0]['brizy-post']['compiled_html'] );
    
        if( empty( $post_meta ) || !is_array( $post_meta ) ) {
            return;
        }
    
        $form_fields = array();
    
        $dom = new DOMDocument();
        $dom->loadHTML( $meta_content );
        $xpath = new DOMXPath( $dom );
        $form = $xpath->query( '//*[@id="' . explode( '_', $form_id )[1] . '"]' );
    
        if( $form->length > 0 ) {
            $form = $form->item(0);
            $form_fields = $form->getElementsByTagName( 'input' );
    
            foreach ( $form_fields as $field ) {
                $form_fields[$field->getAttribute( 'name' )] = $field->getAttribute( 'name' );
            }
        }
    
        return $form_fields;
}

// Form submit data
add_action( 'brizy_form_submit_data', 'adfoin_brizy_submission', 10, 2 );

function adfoin_brizy_submission( $fields, $form ) {
    if( !method_exists( $form, 'getId' ) ) {
        return;
    }

    $form_id = $form->getId();
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'brizy', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array();

    foreach ( $fields as $field ) {
        $posted_data[$field->getName()] = $field->getValue();
    }
}