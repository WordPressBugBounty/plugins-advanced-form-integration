<?php

// add_filter( 'adfoin_form_providers', 'adfoin_smartforms_add_provider' );

// function adfoin_smartforms_add_provider( $providers ) {

//     if ( is_plugin_active( 'smart-forms/smartforms.php' ) ) {
//         $providers['smartforms'] = __( 'Smart Forms', 'advanced-form-integration' );
//     }

//     return $providers;
// }

function adfoin_smartforms_get_forms( $form_provider ) {

    if ( $form_provider != 'smartforms' ) {
        return;
    }

    global $wpdb;

    $query  = "SELECT form_id, form_name FROM {$wpdb->prefix}rednao_smart_forms_table_name";
    $result = $wpdb->get_results( $query, ARRAY_A );
    $forms  = wp_list_pluck( $result, 'form_name', 'form_id' );

    return $forms;
}

function adfoin_smartforms_get_form_fields( $form_provider, $form_id ) {

    if ( $form_provider != 'smartforms' ) {
        return;
    }

    global $wpdb;

    $query        = $wpdb->prepare( "SELECT element_options FROM {$wpdb->prefix}rednao_smart_forms_table_name WHERE form_id = %s", $form_id );
    $result       = $wpdb->get_results( $query, ARRAY_A );
    $decoded      = json_decode( $result[0]["element_options"] );
    $fields       = wp_list_pluck( $decoded, 'Label', 'Id' );
    $fields['form_id']  = __( 'Form ID', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

/*
 * Get Form name by form id
 */
function adfoin_smartforms_get_form_name( $form_provider, $form_id ) {

    if ( $form_provider != "smartforms" ) {
        return;
    }

    global $wpdb;

    $form_name = $wpdb->get_var( $wpdb->prepare( "SELECT form_name FROM {$wpdb->prefix}rednao_smart_forms_table_name WHERE form_id = %d", $form_id ) );

    return $form_name;
}

add_action( 'sf_after_saving_form', 'adfoin_smartforms_submission' );

function adfoin_smartforms_submission( $data ) {

    $form_id     = $data->FormId;
    $posted_data = array();

    if( is_array( $data->FormEntryData ) ) {
        foreach( $data->FormEntryData as $key => $value ) {
            $posted_data[$key] = $value["value"];
        }
    }

    $posted_data["submission_date"] = date( "Y-m-d H:i:s" );
    $posted_data["user_ip"]         = adfoin_get_user_ip();

    global $wpdb, $post;

    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'smartforms', $form_id );
    $posted_data['form_id'] = $form_id;

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_smartforms_trigger_fields' );
}

function adfoin_smartforms_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'smartforms' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' ); ?></p>
    </div>
    <?php
}
