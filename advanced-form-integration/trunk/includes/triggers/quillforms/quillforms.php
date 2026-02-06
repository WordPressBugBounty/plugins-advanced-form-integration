<?php

function adfoin_quillforms_get_forms(  $form_provider  ) {
    if ( 'quillforms' !== $form_provider ) {
        return;
    }
    if ( !post_type_exists( 'quill_forms' ) ) {
        return array();
    }
    $forms = get_posts( array(
        'post_type'      => 'quill_forms',
        'post_status'    => array('publish'),
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    if ( empty( $forms ) ) {
        return array();
    }
    $choices = array();
    foreach ( $forms as $form ) {
        $title = get_the_title( $form->ID );
        $choices[(string) $form->ID] = ( $title ? $title : sprintf( __( 'Form #%d', 'advanced-form-integration' ), $form->ID ) );
    }
    return $choices;
}

function adfoin_quillforms_get_form_fields(  $form_provider, $form_id  ) {
    if ( 'quillforms' !== $form_provider ) {
        return;
    }
    $form_data = adfoin_quillforms_resolve_form_data( $form_id );
    if ( empty( $form_data ) ) {
        return array();
    }
    $blocks = adfoin_quillforms_index_form_blocks( $form_data );
    if ( empty( $blocks ) ) {
        return array();
    }
    $fields = array();
    foreach ( $blocks as $field_id => $block ) {
        if ( !adfoin_quillforms_block_allowed_for_mapping( $block ) ) {
            continue;
        }
        $fields[$field_id] = adfoin_quillforms_get_block_label( $block );
    }
    $special_tags = adfoin_get_special_tags();
    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }
    return $fields;
}

function adfoin_quillforms_get_form_name(  $form_provider, $form_id  ) {
    if ( 'quillforms' !== $form_provider ) {
        return;
    }
    $form = get_post( $form_id );
    if ( $form && 'quill_forms' === $form->post_type ) {
        return get_the_title( $form );
    }
    return '';
}

add_action(
    'quillforms_entry_processed',
    'adfoin_quillforms_after_submission',
    10,
    3
);
function adfoin_quillforms_after_submission(  $entry, $form_data, $run_type  ) {
    if ( 'submission' !== $run_type ) {
        return;
    }
    if ( empty( $entry ) || empty( $entry->form_id ) ) {
        return;
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'quillforms', $entry->form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $posted_data = adfoin_quillforms_build_submission_payload( $entry, $form_data, $run_type );
    global $post;
    $special_tag_values = adfoin_get_special_tags_values( ( isset( $post ) ? $post : null ) );
    if ( is_array( $special_tag_values ) ) {
        $posted_data = array_merge( $posted_data, $special_tag_values );
    }
    $integration->send( $saved_records, $posted_data );
}

function adfoin_quillforms_build_submission_payload(  $entry, $form_data, $run_type  ) {
    $payload = array();
    $blocks = adfoin_quillforms_index_form_blocks( $form_data );
    $records = ( method_exists( $entry, 'get_readable_records' ) ? $entry->get_readable_records( $form_data, 'html' ) : array() );
    if ( isset( $records['fields'] ) && is_array( $records['fields'] ) ) {
        foreach ( $records['fields'] as $field_id => $record ) {
            if ( empty( $blocks[$field_id] ) ) {
                continue;
            }
            if ( !adfoin_quillforms_block_allowed_for_mapping( $blocks[$field_id] ) ) {
                continue;
            }
            $value = '';
            if ( isset( $record['readable_value'] ) ) {
                $value = $record['readable_value'];
            } elseif ( isset( $record['value'] ) ) {
                $value = $record['value'];
            }
            if ( is_array( $value ) || is_object( $value ) ) {
                $value = wp_json_encode( $value );
            }
            $payload[$field_id] = $value;
        }
    }
    $payload['form_id'] = ( isset( $entry->form_id ) ? $entry->form_id : '' );
    $payload['form_title'] = ( isset( $form_data['title'] ) ? $form_data['title'] : '' );
    if ( empty( $payload['form_title'] ) && $payload['form_id'] ) {
        $payload['form_title'] = get_the_title( $payload['form_id'] );
    }
    $payload['entry_id'] = ( isset( $entry->ID ) ? $entry->ID : '' );
    $payload['run_type'] = $run_type;
    $payload['submission_date'] = current_time( 'mysql' );
    if ( is_callable( array($entry, 'get_meta_value') ) ) {
        $user_ip = $entry->get_meta_value( 'user_ip' );
        if ( $user_ip ) {
            $payload['user_ip'] = $user_ip;
        }
        $user_agent = $entry->get_meta_value( 'user_agent' );
        if ( $user_agent ) {
            $payload['user_agent'] = $user_agent;
        }
        $submission_id = $entry->get_meta_value( 'submission_id' );
        if ( $submission_id ) {
            $payload['submission_id'] = $submission_id;
        }
    }
    return $payload;
}

function adfoin_quillforms_resolve_form_data(  $form_id  ) {
    $form_id = absint( $form_id );
    if ( !$form_id || !class_exists( '\\QuillForms\\Core' ) ) {
        return array();
    }
    $form_data = \QuillForms\Core::get_form_data( $form_id );
    return ( is_array( $form_data ) ? $form_data : array() );
}

function adfoin_quillforms_index_form_blocks(  $form_data  ) {
    if ( !class_exists( '\\QuillForms\\Core' ) ) {
        return array();
    }
    if ( empty( $form_data['blocks'] ) || !is_array( $form_data['blocks'] ) ) {
        return array();
    }
    $flat_blocks = \QuillForms\Core::get_blocks_recursively( $form_data['blocks'] );
    if ( empty( $flat_blocks ) ) {
        return array();
    }
    $indexed = array();
    foreach ( $flat_blocks as $block ) {
        if ( empty( $block['id'] ) ) {
            continue;
        }
        $indexed[$block['id']] = $block;
    }
    return $indexed;
}

function adfoin_quillforms_block_allowed_for_mapping(  $block  ) {
    $is_editable = adfoin_quillforms_block_is_editable( $block );
    $allowed = false;
    if ( adfoin_fs()->is_not_paying() ) {
        $allowed = $is_editable && adfoin_quillforms_basic_field_allowed( $block );
    }
    return $allowed;
}

function adfoin_quillforms_block_is_editable(  $block  ) {
    if ( empty( $block['id'] ) || !class_exists( '\\QuillForms\\Managers\\Blocks_Manager' ) ) {
        return false;
    }
    $manager = \QuillForms\Managers\Blocks_Manager::instance();
    $type = $manager->create( $block );
    if ( !$type ) {
        return false;
    }
    return !empty( $type->supported_features['editable'] );
}

function adfoin_quillforms_basic_field_allowed(  $block  ) {
    $name = ( isset( $block['name'] ) ? $block['name'] : '' );
    $label = '';
    if ( isset( $block['attributes']['label'] ) ) {
        $label = strtolower( wp_strip_all_tags( $block['attributes']['label'] ) );
    }
    if ( 'email' === $name ) {
        return true;
    }
    if ( 'short-text' === $name && false !== strpos( $label, 'name' ) ) {
        return true;
    }
    return false;
}

function adfoin_quillforms_get_block_label(  $block  ) {
    if ( !empty( $block['attributes']['label'] ) ) {
        return trim( wp_strip_all_tags( $block['attributes']['label'] ) );
    }
    return sprintf( __( 'Field %s', 'advanced-form-integration' ), ( isset( $block['id'] ) ? $block['id'] : '' ) );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_quillforms_trigger_fields' );
}
function adfoin_quillforms_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'quillforms'" is="quillforms" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php 
}

add_action( 'adfoin_trigger_templates', 'adfoin_quillforms_trigger_template' );
function adfoin_quillforms_trigger_template() {
    ?>
    <script type="text/template" id="quillforms-template">
        <tr valign="top" class="alternate" v-if="trigger.formId">
            <td scope="row-title">
                <label for="tablecell">
                    <span class="dashicons dashicons-info-outline"></span>
                </label>
            </td>
            <td>
                <p>
                    <?php 
    esc_attr_e( 'The basic AFI plugin supports name and email fields only', 'advanced-form-integration' );
    ?>
                </p>
            </td>
        </tr>
    </script>
    <?php 
}
