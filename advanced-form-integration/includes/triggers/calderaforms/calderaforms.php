<?php

function adfoin_calderaforms_get_forms(  $form_provider  ) {
    if ( $form_provider != 'calderaforms' ) {
        return;
    }
    if ( !class_exists( 'Caldera_Forms_Forms' ) ) {
        return;
    }
    $forms = Caldera_Forms_Forms::get_forms();
    $data = [];
    foreach ( $forms as $form ) {
        $data[] = Caldera_Forms_Forms::get_form( $form );
    }
    $filtered = wp_list_pluck( $data, 'name', 'ID' );
    return $filtered;
}

function adfoin_calderaforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'calderaforms' ) {
        return;
    }
    $data = Caldera_Forms_Forms::get_form( $form_id );
    $fields = array();
    foreach ( $data['fields'] as $field ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'text' == $field['type'] || 'email' == $field['type'] ) {
                $fields[$field['ID']] = $field['label'];
            }
        }
    }
    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    $fields = array_merge( $fields, $special_tags );
    return $fields;
}

function adfoin_calderaforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != 'calderaforms' ) {
        return;
    }
    $data = Caldera_Forms_Forms::get_form( $form_id );
    $form_name = $data['name'];
    return $form_name;
}

add_action( 'caldera_forms_submit_complete', 'adfoin_calderaforms_submission', 55 );
function adfoin_calderaforms_submission(  $form  ) {
    $data = array();
    foreach ( $form['fields'] as $field_id => $field ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'text' == $field['type'] || 'email' == $field['type'] ) {
                $posted_data[$field_id] = Caldera_Forms::get_field_data( $field_id, $form );
            }
        }
    }
    $posted_data['submission_date'] = date( 'Y-m-d H:i:s' );
    $posted_data['user_ip'] = adfoin_get_user_ip();
    $form_id = $form['ID'];
    global $wpdb, $post;
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $special_tag_values ) ) {
        $posted_data = array_merge( $posted_data, $special_tag_values );
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'calderaforms', $form_id );
    $posted_data['form_id'] = $form_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
    return;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_calderaforms_trigger_fields' );
}
function adfoin_calderaforms_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'calderaforms' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
