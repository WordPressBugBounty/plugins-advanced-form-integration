<?php

function adfoin_jetformbuilder_get_forms(  $form_provider  ) {
    if ( $form_provider != 'jetformbuilder' ) {
        return;
    }
    $forms = \Jet_Form_Builder\Classes\Tools::get_forms_list_for_js( true );
    return $forms;
}

function adfoin_jetformbuilder_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider != 'jetformbuilder' ) {
        return;
    }
    $content = Jet_Form_Builder\Blocks\Block_Helper::get_blocks_by_post( $form_id );
    $fields_array = Jet_Form_Builder\Blocks\Block_Helper::filter_blocks_by_namespace( $content );
    $fields = array();
    foreach ( $fields_array as $field ) {
        if ( isset( $field['attrs']['name'] ) && isset( $field['attrs']['label'] ) ) {
            if ( adfoin_fs()->is_not_paying() ) {
                if ( $field['blockName'] == 'jet-forms/text-field' ) {
                    $fields[$field['attrs']['name']] = $field['attrs']['label'];
                }
            }
        }
    }
    return $fields;
}

add_action(
    'jet-form-builder/form-handler/after-send',
    'adfoin_jet_form_builder_submit',
    10,
    2
);
function adfoin_jet_form_builder_submit(  $data, $is_success  ) {
    global $wpdb, $post;
    $form_id = ( isset( $data->form_id ) ? $data->form_id : false );
    if ( !$form_id ) {
        return;
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'jetformbuilder', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $record_id = jet_fb_action_handler()->get_context( JFB_Modules\Form_Record\Action_Types\Save_Record::ID, 'id' );
    if ( empty( $record_id ) ) {
        return;
    }
    $fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}jet_fb_records_fields WHERE record_id = {$record_id}", ARRAY_A );
    $posted_data = array();
    if ( !empty( $fields ) ) {
        foreach ( $fields as $field ) {
            if ( adfoin_fs()->is_not_paying() ) {
                if ( $field['field_type'] == 'text-field' ) {
                    $posted_data[$field['field_name']] = $field['field_value'];
                }
            }
        }
    }
    $special_tag_values = adfoin_get_special_tags_values( $post );
    if ( is_array( $special_tag_values ) ) {
        $posted_data = array_merge( $posted_data, $special_tag_values );
    }
    $integration->send( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_jetformbuilder_trigger_fields' );
}
function adfoin_jetformbuilder_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'jetformbuilder'" is="bricks" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php 
}

add_action( 'adfoin_trigger_templates', 'adfoin_jetformbuilder_trigger_template' );
function adfoin_jetformbuilder_trigger_template() {
    ?>
        <script type="text/template" id="jetformbuilder-template">
            <tr valign="top" class="alternate" v-if="trigger.formId">
                <td scope="row-title">
                    <label for="tablecell">
                        <span class="dashicons dashicons-info-outline"></span>
                    </label>
                </td>
                <td>
                    <p>
                        <?php 
    esc_attr_e( 'The basic AFI plugin supports text field only', 'advanced-form-integration' );
    ?>
                    </p>
                </td>
            </tr>
        </script>
    <?php 
}
