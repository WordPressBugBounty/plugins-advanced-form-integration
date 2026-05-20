<?php

function adfoin_quform_get_forms(  $form_provider  ) {
    if ( $form_provider != 'quform' ) {
        return;
    }
    global $wpdb;
    $query = "SELECT id, name FROM {$wpdb->prefix}quform_forms";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $forms = wp_list_pluck( $result, 'name', 'id' );
    return $forms;
}

function adfoin_quform_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'quform' ) {
        return;
    }
    global $wpdb;
    $query = "SELECT config FROM {$wpdb->prefix}quform_forms WHERE id = {$form_id}";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $data = maybe_unserialize( base64_decode( stripslashes( $result[0]["config"] ) ) );
    $fields = array();
    $fields = adfoin_quform_get_form_fields_recursive( $data['elements'], $fields, $form_id );
    $fields['form_id'] = __( 'Form ID', 'advanced-form-integration' );
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_quform_get_form_fields_recursive(  $elements, $fields, $form_id  ) {
    foreach ( $elements as $element ) {
        if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
            $fields = adfoin_quform_get_form_fields_recursive( $element['elements'], $fields, $form_id );
        } else {
            if ( 'submit' != $element['type'] ) {
                if ( adfoin_fs()->is_not_paying() ) {
                    if ( 'text' == $element['type'] || 'email' == $element['type'] ) {
                        $fields['quform_' . $form_id . '_' . $element['id']] = $element['label'];
                    }
                }
            }
        }
    }
    return $fields;
}

function adfoin_quform_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != "quform" ) {
        return;
    }
    global $wpdb;
    $form_name = $wpdb->get_var( "SELECT name FROM {$wpdb->prefix}quform_forms WHERE id = " . $form_id );
    return $form_name;
}

add_filter(
    'quform_post_process',
    'adfoin_quform_post_process',
    10,
    2
);
function adfoin_quform_post_process(  $result, $form  ) {
    $raw_data = $form->getValues();
    $form_id = $form->getId();
    global $wpdb, $post;
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'quform', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    foreach ( $raw_data as $key => $value ) {
        if ( is_array( $value ) ) {
            $value_data = array();
            foreach ( $value as $k => $v ) {
                if ( isset( $v['url'] ) ) {
                    $value_data[] = $v['url'];
                } else {
                    $value_data[] = $v;
                }
            }
            $value = implode( ', ', $value_data );
        }
        $posted_data[$key] = $value;
    }
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['form_id'] = $form_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_quform_trigger_fields' );
}
function adfoin_quform_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'quform' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
