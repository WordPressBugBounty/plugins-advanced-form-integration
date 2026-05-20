<?php

function adfoin_happyforms_get_forms(  $form_provider  ) {
    if ( $form_provider != 'happyforms' ) {
        return;
    }
    global $wpdb;
    $form_data = get_posts( array(
        'post_type'      => 'happyform',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ) );
    $forms = wp_list_pluck( $form_data, 'post_title', 'ID' );
    return $forms;
}

function adfoin_happyforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'happyforms' ) {
        return;
    }
    if ( !$form_id ) {
        return;
    }
    $form_data = happyforms_get_form_controller()->get( $form_id );
    $fields = array();
    foreach ( $form_data['parts'] as $single_field ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'single_line_text' == $single_field['type'] || 'email' == $single_field['type'] ) {
                $fields[$single_field['id']] = $single_field['label'];
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

/*
 * Get Form name by form id
 */
function adfoin_happyforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != 'happyforms' ) {
        return;
    }
    $form = get_post( $form_id );
    $form_name = $form->post_title;
    return $form_name;
}

add_action(
    'happyforms_submission_success',
    'adfoin_happyforms_submission',
    30,
    2
);
function adfoin_happyforms_submission(  $submission, $form  ) {
    global $wpdb;
    $form_id = $form['ID'];
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'happyforms', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    foreach ( $submission as $key => $value ) {
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'single_line_text' == substr( $key, 0, 16 ) || 'email' == substr( $key, 0, 5 ) ) {
                $posted_data[$key] = $value;
            }
        }
    }
    $posted_data['submission_date'] = date( 'Y-m-d H:i:s' );
    $posted_data['user_ip'] = adfoin_get_user_ip();
    global $wpdb, $post;
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['form_id'] = $form_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_happyforms_trigger_fields' );
}
function adfoin_happyforms_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'happyforms' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
