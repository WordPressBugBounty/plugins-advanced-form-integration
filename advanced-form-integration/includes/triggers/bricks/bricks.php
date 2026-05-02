<?php

// Get forms list
function adfoin_bricks_get_forms(  $form_provider  ) {
    if ( $form_provider != 'bricks' ) {
        return;
    }
    $cached = get_transient( 'adfoin_bricks_forms' );
    if ( false !== $cached && is_array( $cached ) ) {
        return $cached;
    }
    $all_forms = array();
    $posts = get_posts( array(
        'post_type'      => array(
            'post',
            'page',
            'products',
            'stories',
            'insights',
            'events',
            'industries',
            'podcasts',
            'product',
            'story',
            'insight',
            'event',
            'industry',
            'podcast',
            'bricks_template'
        ),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key' => '_bricks_page_content_2',
            ),
            array(
                'key' => '_bricks_page_footer_2',
            ),
            array(
                'key' => '_bricks_page_header_2',
            ),
        ),
    ) );
    if ( empty( $posts ) || !is_array( $posts ) ) {
        set_transient( 'adfoin_bricks_forms', $all_forms, DAY_IN_SECONDS );
        return $all_forms;
    }
    foreach ( $posts as $post_id ) {
        $post_meta = get_post_meta( $post_id, '_bricks_page_content_2', true );
        $post_meta = ( !empty( $post_meta ) ? $post_meta : get_post_meta( $post_id, '_bricks_page_footer_2', true ) );
        $post_meta = ( !empty( $post_meta ) ? $post_meta : get_post_meta( $post_id, '_bricks_page_header_2', true ) );
        if ( empty( $post_meta ) || !is_array( $post_meta ) ) {
            continue;
        }
        foreach ( $post_meta as $form ) {
            if ( isset( $form['name'] ) && $form['name'] == 'form' ) {
                $all_forms[$post_id . '_' . $form['id']] = ( !empty( $form['label'] ) ? $form['label'] : 'Untitled form ' . $form['id'] );
            }
        }
    }
    set_transient( 'adfoin_bricks_forms', $all_forms, DAY_IN_SECONDS );
    return $all_forms;
}

/**
 * Invalidate the cached Bricks form list whenever a post is saved,
 * deleted, or trashed.
 */
function adfoin_bricks_clear_forms_cache() {
    delete_transient( 'adfoin_bricks_forms' );
}

add_action( 'save_post', 'adfoin_bricks_clear_forms_cache' );
add_action( 'deleted_post', 'adfoin_bricks_clear_forms_cache' );
add_action( 'trashed_post', 'adfoin_bricks_clear_forms_cache' );
// Get form fields
function adfoin_bricks_get_form_fields(  $form_provider, $form_ids  ) {
    if ( $form_provider != 'bricks' ) {
        return;
    }
    list( $post_id, $form_id ) = explode( '_', $form_ids );
    $form_settings = \Bricks\Helpers::get_element_settings( $post_id, $form_id );
    if ( empty( $form_settings ) ) {
        return;
    }
    $fields = array();
    foreach ( $form_settings['fields'] as $field ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( $field['type'] == 'text' || $field['type'] == 'email' ) {
                $fields[$field['id']] = $field['label'];
            }
        }
    }
    $fields['postId'] = __( 'Post ID', 'advanced-form-integration' );
    $fields['formId'] = __( 'Form ID', 'advanced-form-integration' );
    $fields['referrer'] = __( 'Form Page Link', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

// submit form
add_action(
    'bricks/form/custom_action',
    'adfoin_bricks_submission',
    10,
    1
);
function adfoin_bricks_submission(  $form  ) {
    $fields = $form->get_fields();
    $post_id = ( isset( $fields['postId'] ) ? $fields['postId'] : false );
    $form_id = ( isset( $fields['formId'] ) ? $fields['formId'] : false );
    if ( !$post_id || !$form_id ) {
        return;
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'bricks', $post_id . '_' . $form_id );
    if ( empty( $saved_records ) ) {
        $saved_records = $integration->get_by_trigger_partial( 'bricks', $form_id );
        if ( empty( $saved_records ) ) {
            return;
        }
    }
    $posted_data = array();
    $field_types = array();
    $form_settings = $form->get_settings();
    if ( !empty( $form_settings['fields'] ) ) {
        foreach ( $form_settings['fields'] as $field ) {
            $field_types[$field['id']] = $field['type'];
        }
    }
    foreach ( $fields as $key => $value ) {
        $field_id = str_replace( 'form-field-', '', $key );
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'text' == $field_types[$field_id] || 'email' == $field_types[$field_id] ) {
                $posted_data[$field_id] = $value;
            }
        }
    }
    $post = get_post( $post_id, 'OBJECT' );
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['formId'] = $fields['formId'];
    $posted_data['postId'] = $fields['postId'];
    $posted_data['referrer'] = $fields['referrer'];
    $integration->send( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_bricks_trigger_fields' );
}
function adfoin_bricks_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'bricks' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'Enable custom action in form settings. The basic AFI plugin supports text and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
