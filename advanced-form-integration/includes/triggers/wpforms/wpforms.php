<?php

function adfoin_wpforms_get_forms(  $form_provider  ) {
    if ( $form_provider != 'wpforms' ) {
        return;
    }
    $args = [
        'post_type'      => 'wpforms',
        'posts_per_page' => -1,
    ];
    $data = get_posts( $args );
    $forms = wp_list_pluck( $data, 'post_title', 'ID' );
    return $forms;
}

function adfoin_wpforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'wpforms' ) {
        return;
    }
    $form = get_post( $form_id );
    $data = ( $form && isset( $form->post_content ) ? json_decode( $form->post_content ) : null );
    $raw_fields = ( is_object( $data ) && isset( $data->fields ) ? $data->fields : array() );
    $fields = array();
    foreach ( $raw_fields as $field ) {
        // Skip display / structural / captcha types — they carry no user value.
        if ( isset( $field->type ) && in_array( $field->type, adfoin_wpforms_excluded_field_types(), true ) ) {
            continue;
        }
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'name' == $field->type || 'email' == $field->type ) {
                if ( 'name' == $field->type ) {
                    $fields[$field->id . '_first'] = __( 'First Name', 'advanced-form-integration' );
                    $fields[$field->id . '_middle'] = __( 'Middle Name', 'advanced-form-integration' );
                    $fields[$field->id . '_last'] = __( 'Last Name', 'advanced-form-integration' );
                }
                if ( 'email' == $field->type ) {
                    $fields[$field->id] = $field->label;
                }
            }
        }
    }
    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $fields['form_title'] = __( 'Form Title', 'advanced-form-integration' );
    $fields['entry_id'] = __( 'Entry ID', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_wpforms_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != 'wpforms' ) {
        return;
    }
    $form = get_post( $form_id );
    return ( $form && isset( $form->post_title ) ? $form->post_title : '' );
}

/**
 * WPForms field types with no useful user value (display / structural /
 * captcha). Excluded from the mappable field list. Filterable.
 */
function adfoin_wpforms_excluded_field_types() {
    return apply_filters( 'adfoin_wpforms_excluded_field_types', array(
        'divider',
        'html',
        'pagebreak',
        'entry-preview',
        'content',
        'internal-information',
        'captcha'
    ) );
}

add_action(
    'wpforms_process_complete',
    'adfoin_wpforms_submission',
    10,
    4
);
function adfoin_wpforms_submission(
    $fields,
    $entry,
    $form_data,
    $entry_id = 0
) {
    global $post;
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpforms', $form_data['id'] );
    if ( empty( $saved_records ) ) {
        return;
    }
    $form_fields = $form_data['fields'];
    $form_field_types = array();
    $posted_data = array();
    foreach ( $form_fields as $key => $value ) {
        $form_field_types[$value['id']] = $value['type'];
    }
    foreach ( $entry['fields'] as $key => $value ) {
        $field_type = ( isset( $form_field_types[$key] ) ? $form_field_types[$key] : '' );
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'name' == $field_type ) {
                $posted_data[$key . '_first'] = ( isset( $value['first'] ) ? $value['first'] : '' );
                $posted_data[$key . '_middle'] = ( isset( $value['middle'] ) ? $value['middle'] : '' );
                $posted_data[$key . '_last'] = ( isset( $value['last'] ) ? $value['last'] : '' );
                $posted_data[$key] = ( isset( $fields[$key], $fields[$key]['value'] ) ? $fields[$key]['value'] : '' );
            }
            if ( 'email' == $field_type ) {
                if ( is_array( $value ) && isset( $value['primary'] ) ) {
                    $posted_data[$key] = $value['primary'];
                    continue;
                }
                $posted_data[$key] = $value;
            }
        }
    }
    // Resolve the submission's source page for post-based special tags without
    // clobbering the global $post — WPForms stores the URL in entry_meta.
    $resolved_post = $post;
    $source_url = ( isset( $form_data['entry_meta']['page_url'] ) ? $form_data['entry_meta']['page_url'] : '' );
    if ( $source_url ) {
        $source_post_id = url_to_postid( $source_url );
        if ( $source_post_id ) {
            $resolved_post = get_post( $source_post_id );
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $resolved_post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['submission_date'] = current_time( 'mysql' );
    $posted_data['user_ip'] = adfoin_get_user_ip();
    $posted_data['form_id'] = $form_data['id'];
    $posted_data['form_title'] = $form_data['settings']['form_title'];
    $posted_data['entry_id'] = $entry_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_wpforms_trigger_fields' );
}
function adfoin_wpforms_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'wpforms' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
