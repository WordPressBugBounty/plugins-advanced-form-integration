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
    $saved_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = 'wsform' AND form_id = %s", $submission->form_id ), ARRAY_A );
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
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );
    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record'      => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_wsform_trigger_fields' );
}
function adfoin_wsform_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'wsform'" is="wsform" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php 
}

add_action( "adfoin_trigger_templates", "adfoin_wsform_trigger_template" );
function adfoin_wsform_trigger_template() {
    ?>
        <script type="text/template" id="wsform-template">
            <tr valign="top" class="alternate" v-if="trigger.formId">
                <td scope="row-title">
                    <label for="tablecell">
                        <span class="dashicons dashicons-info-outline"></span>
                    </label>
                </td>
                <td>
                    <p>
                        <?php 
    esc_attr_e( 'The basic AFI plugin supports text and email fields only', 'advanced-form-integration' );
    ?>
                    </p>
                </td>
            </tr>
        </script>
    <?php 
}
