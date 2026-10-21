<?php

function adfoin_formcraftb_get_forms(  $form_provider  ) {
    if ( $form_provider != 'formcraftb' ) {
        return;
    }
    global $wpdb;
    $query = "SELECT id, name FROM {$wpdb->prefix}formcraft_b_forms";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $forms = wp_list_pluck( $result, 'name', 'id' );
    return $forms;
}

function adfoin_formcraftb_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'formcraftb' ) {
        return;
    }
    global $wpdb;
    $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}formcraft_b_forms WHERE id = %d", $form_id );
    $result = $wpdb->get_results( $query, ARRAY_A );
    $field_data = json_decode( stripslashes( $result[0]['meta_builder'] ), 1 );
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

function adfoin_formcraftb_get_form_name(  $form_provider, $form_id  ) {
    if ( $form_provider != 'formcraftb' ) {
        return;
    }
    global $wpdb;
    $form_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}formcraft_b_forms WHERE id = %d", $form_id ) );
    return $form_name;
}

add_action( 'wp_ajax_formcraft_basic_form_submit', 'adfoin_formcraftb_submission' );
add_action( 'wp_ajax_nopriv_formcraft_basic_form_submit', 'adfoin_formcraftb_submission' );
function adfoin_formcraftb_submission() {
    if ( !isset( $_POST['id'] ) || !ctype_digit( $_POST['id'] ) ) {
        return;
    }
    if ( isset( $_POST['website'] ) && $_POST['website'] != '' ) {
        return;
    }
    global $wpdb;
    $form_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'formcraftb', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = array();
    foreach ( $_POST as $key => $value ) {
        if ( in_array( $key, array('action', '_wpnonce', 'website'), true ) ) {
            continue;
        }
        $posted_data[$key] = html_entity_decode( sanitize_text_field( $value ) );
    }
    $posted_data['submission_date'] = date( 'Y-m-d' );
    $posted_data['user_ip'] = adfoin_get_user_ip();
    // Merge special-tag values (the field-mapping UI exposes them via
    // adfoin_get_special_tags(); this is the matching runtime side that
    // was missing before — picked tags now actually substitute).
    global $post;
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }
    $posted_data['form_id'] = $form_id;
    // This runs before FormCraft checks its nonce and validates the fields,
    // and FormCraft has no post-save hook. It die()s after responding, which
    // still fires 'shutdown' — dispatch there, only if it saved a submission.
    $table = $wpdb->prefix . 'formcraft_b_submissions';
    $last_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$table}" );
    $visitor = ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
    add_action( 'shutdown', function () use(
        $wpdb,
        $table,
        $last_id,
        $form_id,
        $visitor,
        $saved_records,
        $posted_data
    ) {
        $saved = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE id > %d AND form = %d AND visitor LIKE %s LIMIT 1",
            $last_id,
            $form_id,
            '%' . $wpdb->esc_like( $visitor ) . '%'
        ) );
        if ( $saved ) {
            adfoin_dispatch_integrations( $saved_records, $posted_data );
        }
    } );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_formcraftb_trigger_fields' );
}
function adfoin_formcraftb_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'formcraftb' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
