<?php

function adfoin_wsform_get_forms(  $form_provider  ) {
    if ( $form_provider != 'wsform' ) {
        return;
    }
    $raw_forms = wsf_form_get_all( true, 'label' );
    $forms = array();
    if ( $raw_forms ) {
        foreach ( $raw_forms as $form ) {
            $forms[$form['id']] = $form['label'];
        }
    }
    return $forms;
}

function adfoin_wsform_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'wsform' ) {
        return;
    }
    $fields = array();
    $form_object = wsf_form_get_form_object( $form_id );
    $raw_fields = wsf_form_get_fields( $form_object );
    foreach ( $raw_fields as $field ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'text' == $field->type || 'email' == $field->type ) {
                $fields[$field->id] = $field->label;
            }
        }
    }
    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_wsform_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != 'wsform' ) {
        return;
    }
    $form_object = wsf_form_get_form_object( $form_id );
    return $form_object->label;
}

add_action(
    'wsf_submit_post_complete',
    'adfoin_wsform_submission',
    10,
    1
);
function adfoin_wsform_submission(  $submission  ) {
    global $wpdb;
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wsform', $submission->form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    if ( isset( $submission->meta ) && is_array( $submission->meta ) ) {
        foreach ( $submission->meta as $meta ) {
            if ( adfoin_fs()->is_not_paying() ) {
                if ( 'text' == $meta['type'] || 'email' == $meta['type'] ) {
                    $posted_data[$meta['id']] = $meta['value'];
                }
            }
        }
    }
    $post_id = ( isset( $submission->meta['post_id'] ) ? $submission->meta['post_id'] : 0 );
    $post = get_post( $post_id );
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['form_id'] = $submission->form_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_wsform_trigger_fields' );
}
function adfoin_wsform_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'wsform' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports text and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
