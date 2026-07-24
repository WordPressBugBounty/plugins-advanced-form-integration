<?php

/**
 * PropertyHive action platform — local same-site integration (no REST/API
 * keys). Properties are a plain custom post type, 'property' — confirmed
 * against the plugin's own source (includes/ph-property-functions.php).
 * There's no dedicated creation helper, so this uses wp_insert_post() +
 * post meta directly, same pattern as platforms/wpjobmanager/wpjobmanager.php.
 *
 * Scope is deliberately limited to the plugin's simple flat meta fields
 * (_price, _department, _address_street, _address_postcode — confirmed
 * against class-ph-property.php). PropertyHive's full data model for rooms/
 * bedrooms/floor plans is stored as indexed sub-records (_room_name_0,
 * _room_description_0, etc), which doesn't map cleanly to a flat form
 * submission, so it's intentionally left out — this creates a valid draft
 * listing with the basics; an agent fills in room details afterward.
 *
 * @link https://plugins.trac.wordpress.org/browser/propertyhive/trunk/includes/ph-property-functions.php
 * @link https://plugins.trac.wordpress.org/browser/propertyhive/trunk/includes/class-ph-property.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_propertyhive_actions', 10, 1 );

function adfoin_propertyhive_actions( $actions ) {

    $actions['propertyhive'] = array(
        'title' => __( 'PropertyHive', 'advanced-form-integration' ),
        'tasks' => array(
            'add_property' => __( 'Create Property', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_propertyhive_action_fields' );

function adfoin_propertyhive_action_fields() {
    ?>
    <script type="text/template" id="propertyhive-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_property'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_propertyhive_fields', 'adfoin_get_propertyhive_fields', 10, 0 );

function adfoin_get_propertyhive_fields() {
    adfoin_verify_nonce();

    if ( ! post_type_exists( 'property' ) ) {
        wp_send_json_error( __( 'PropertyHive is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'title', 'value' => __( 'Property Title', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'price', 'value' => __( 'Price / Rent', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'department', 'value' => __( 'Department', 'advanced-form-integration' ), 'description' => __( 'e.g. residential-sale, residential-let, commercial-sale, commercial-let.', 'advanced-form-integration' ) ),
        array( 'key' => 'address_street', 'value' => __( 'Street Address', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'address_postcode', 'value' => __( 'Postcode', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_propertyhive_job_queue', 'adfoin_propertyhive_job_queue', 10, 1 );

function adfoin_propertyhive_job_queue( $data ) {
    adfoin_propertyhive_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a PropertyHive property listing
 */
function adfoin_propertyhive_send_data( $record, $posted_data ) {

    if ( ! post_type_exists( 'property' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_property' !== $task ) {
        return;
    }

    $prepared_data = array();

    foreach ( $field_data as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed_value || null === $parsed_value ) {
            continue;
        }

        $prepared_data[ $key ] = $parsed_value;
    }

    $request_payload = $prepared_data;
    $response_body   = array( 'success' => false );
    $status_code     = 400;

    if ( empty( $prepared_data['title'] ) ) {
        $response_body['message'] = __( 'A property title is required.', 'advanced-form-integration' );
    } else {
        $post_id = wp_insert_post(
            array(
                'post_type'    => 'property',
                'post_title'   => $prepared_data['title'],
                'post_content' => isset( $prepared_data['description'] ) ? $prepared_data['description'] : '',
                'post_status'  => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $response_body['message'] = __( 'Failed to create the property. Please verify the supplied data.', 'advanced-form-integration' );
        } else {
            $meta_map = array(
                'price'            => '_price',
                'department'       => '_department',
                'address_street'   => '_address_street',
                'address_postcode' => '_address_postcode',
            );

            foreach ( $meta_map as $field_key => $meta_key ) {
                if ( isset( $prepared_data[ $field_key ] ) ) {
                    update_post_meta( $post_id, $meta_key, $prepared_data[ $field_key ] );
                }
            }

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Property created successfully.', 'advanced-form-integration' );
            $response_body['id']      = $post_id;
        }
    }

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    $log_response = array(
        'response' => array(
            'code'    => $status_code,
            'message' => $response_body['message'],
        ),
        'body'     => $response_body,
    );

    adfoin_add_to_log( $log_response, 'propertyhive', $log_args, $record );
}
