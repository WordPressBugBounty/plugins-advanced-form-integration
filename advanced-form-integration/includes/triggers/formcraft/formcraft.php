<?php

function adfoin_formcraft_get_forms(  $form_provider  ) {
    if ( $form_provider != 'formcraft' ) {
        return;
    }
    global $wpdb;
    $query = "SELECT id, name FROM {$wpdb->prefix}formcraft_3_forms";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $forms = wp_list_pluck( $result, 'name', 'id' );
    return $forms;
}

function adfoin_formcraft_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'formcraft' ) {
        return;
    }
    global $wpdb;
    $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}formcraft_3_forms WHERE id = %d", $form_id );
    $result = $wpdb->get_results( $query, ARRAY_A );
    $field_data = json_decode( stripslashes( $result[0]['meta_builder'] ), 1 );
    $fields = array();
    foreach ( $field_data['fields'] as $field ) {
        $field_title = ( isset( $field['elementDefaults'], $field['elementDefaults']['main_label'] ) ? $field['elementDefaults']['main_label'] : $field['identifier'] );
        if ( adfoin_fs()->is_not_paying() ) {
            if ( 'oneLineText' == $field['type'] || 'email' == $field['type'] ) {
                $fields[$field['identifier']] = $field_title;
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

function adfoin_formcraft_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != 'formcraft' ) {
        return;
    }
    global $wpdb;
    $form_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}formcraft_b_forms WHERE id = %d", $form_id ) );
    return $form_name;
}

function adfoin_formcraft_if_file_exists(  $maybe_unique_key  ) {
    global $wpdb;
    $fc_files_table = $wpdb->prefix . 'formcraft_3_files';
    $url = $wpdb->get_var( $wpdb->prepare( "SELECT file_url FROM {$fc_files_table} WHERE uniq_key = %s", $maybe_unique_key ) );
    if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE ) {
        return false;
    } else {
        return $url;
    }
}

add_action(
    'formcraft_after_save',
    'adfoin_formcraft_submission',
    10,
    3
);
function adfoin_formcraft_submission(  $template, $meta, $content  ) {
    if ( !isset( $_POST['id'] ) || !ctype_digit( $_POST['id'] ) ) {
        return;
    }
    if ( isset( $_POST['website'] ) && $_POST['website'] != '' ) {
        return;
    }
    global $wpdb, $post;
    $form_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'formcraft', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    foreach ( $content as $single ) {
        if ( 'fileupload' == $single['type'] ) {
            $posted_data[$single['identifier']] = $single['url'];
            continue;
        }
        if ( 'matrix' == $single['type'] ) {
            if ( is_array( $single['value'] ) && count( $single['value'] ) > 0 ) {
                $items = array();
                foreach ( $single['value'] as $question ) {
                    if ( isset( $question['question'] ) && isset( $question['value'] ) ) {
                        $items[] = "{$question['question']}: {$question['value']}";
                    }
                }
            }
            $posted_data[$single['identifier']] = implode( '/n', $items );
            continue;
        }
        $posted_data[$single['identifier']] = html_entity_decode( $single['value'] );
    }
    // foreach( $_POST as $key => $value ) {
    //     if( is_array( $value ) ) {
    //         $value = implode( ",", $value );
    //     }
    //     $posted_data[$key] = adfoin_sanitize_text_or_array_field( $value );
    // }
    // foreach( $posted_data as &$single_data ) {
    //     $exploded = explode( ',', $single_data );
    //     $file_links      = array();
    //     foreach( $exploded as $single ) {
    //         if( adfoin_is_valid_md5( $single ) ) {
    //             $file_links[] = adfoin_formcraft_if_file_exists( $single );
    //         }
    //     }
    //     if( $file_links ) {
    //         $single_data = implode( ',', $file_links );
    //     }
    // }
    $posted_data['submission_date'] = date( 'Y-m-d H:i:s' );
    $posted_data['user_ip'] = adfoin_get_user_ip();
    $posted_data['entry_id'] = $template['Entry ID'];
    if ( !is_object( $post ) ) {
        $post_id = url_to_postid( wp_get_referer() );
        if ( $post_id ) {
            $post = get_post( $post_id, 'OBJECT' );
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['form_id'] = $form_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_formcraft_trigger_fields' );
}
function adfoin_formcraft_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'formcraft' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
