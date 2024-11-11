<?php
function adfoin_bitform_get_forms( $form_provider ) {

    if ( $form_provider != 'bitform' ) {
        return;
    }

    $raw_forms = \BitCode\BitForm\API\BitForm_Public\BitForm_Public::getForms();
    $forms     = array();

    if ( $raw_forms ) {
        foreach( $raw_forms as $form ) {
            $forms[$form->id] = $form->form_name;
        }
    }

    return $forms;
}

function adfoin_bitform_get_form_fields( $form_provider, $form_id ) {

    if ( $form_provider != 'bitform' ) {
        return;
    }

    $fields = array();

    $raw_fields = \BitCode\BitForm\API\BitForm_Public\BitForm_Public::getFields($form_id);

    foreach( $raw_fields as $key=>$field ) {
        
        if( 'button' == $field->typ ) {
            continue;
        }

        $fields[$key] = $field->lbl;
    }

    $special_tags = adfoin_get_special_tags();

    if( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

function adfoin_bitform_get_form_name( $form_provider, $form_id ) {

    if ( $form_provider != 'bitform' ) {
        return;
    }

    $forms = \BitCode\BitForm\API\BitForm_Public\BitForm_Public::getForms();

    foreach( $forms as $form ) {
        if( $form->id == $form_id ) {
            return $form->form_name;
        }
    }
}

add_action( 'bitform_submit_success', 'adfoin_bitform_submission', 10, 3 );

function adfoin_bitform_submission( $form_id, $entry_id, $form_data ) {

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'bitform', $form_id );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = $form_data;
    $post = adfoin_get_post_object();
    $special_tag_values = adfoin_get_special_tags_values( $post );

    if( is_array( $posted_data ) && is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $integration->send( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_bitform_trigger_fields' );
}

function adfoin_bitform_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'bitform'" is="bitform" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php
}

add_action( "adfoin_trigger_templates", "adfoin_bitform_trigger_template" );

function adfoin_bitform_trigger_template() {
    ?>
        <script type="text/template" id="bitform-template">
            <tr valign="top" class="alternate" v-if="trigger.formId">
                <td scope="row-title">
                    <label for="tablecell">
                        <span class="dashicons dashicons-info-outline"></span>
                    </label>
                </td>
                <td>
                    <p>
                        <?php esc_attr_e( 'The basic AFI plugin supports text and email fields only', 'advanced-form-integration' ); ?>
                    </p>
                </td>
            </tr>
        </script>
    <?php
}
