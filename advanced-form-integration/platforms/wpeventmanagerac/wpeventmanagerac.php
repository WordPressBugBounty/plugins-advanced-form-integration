<?php

/**
 * WP Event Manager action platform — local same-site integration (no
 * REST/API keys). Slug is `wpeventmanagerac`, not `wpeventmanager` — the
 * trigger side already uses that slug (includes/triggers/wpeventmanager/wpeventmanager.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Events are a plain custom post type, 'event_listing' — confirmed against
 * the plugin's own source (includes/wp-event-manager-post-types.php). There
 * is no dedicated creation helper, so this uses wp_insert_post() + post meta
 * directly, same pattern as platforms/wpjobmanager/wpjobmanager.php and
 * platforms/propertyhive/propertyhive.php. Meta keys (_event_start_date,
 * _event_end_date, _event_location) are confirmed real, read directly from
 * that file's own database queries/meta updates.
 *
 * @link https://plugins.trac.wordpress.org/browser/wp-event-manager/trunk/includes/wp-event-manager-post-types.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_wpeventmanagerac_actions', 10, 1 );

function adfoin_wpeventmanagerac_actions( $actions ) {

    $actions['wpeventmanagerac'] = array(
        'title' => __( 'WP Event Manager', 'advanced-form-integration' ),
        'tasks' => array(
            'add_event' => __( 'Create Event Listing', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_wpeventmanagerac_action_fields' );

function adfoin_wpeventmanagerac_action_fields() {
    ?>
    <script type="text/template" id="wpeventmanagerac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_event'">
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

add_action( 'wp_ajax_adfoin_get_wpeventmanagerac_fields', 'adfoin_get_wpeventmanagerac_fields', 10, 0 );

function adfoin_get_wpeventmanagerac_fields() {
    adfoin_verify_nonce();

    if ( ! post_type_exists( 'event_listing' ) ) {
        wp_send_json_error( __( 'WP Event Manager is not active.', 'advanced-form-integration' ) );
    }

    $fields = array(
        array( 'key' => 'title', 'value' => __( 'Event Title', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'event_location', 'value' => __( 'Event Location', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'event_start_date', 'value' => __( 'Start Date', 'advanced-form-integration' ), 'description' => __( 'Format: YYYY-MM-DD.', 'advanced-form-integration' ) ),
        array( 'key' => 'event_end_date', 'value' => __( 'End Date', 'advanced-form-integration' ), 'description' => __( 'Format: YYYY-MM-DD.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_wpeventmanagerac_job_queue', 'adfoin_wpeventmanagerac_job_queue', 10, 1 );

function adfoin_wpeventmanagerac_job_queue( $data ) {
    adfoin_wpeventmanagerac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a WP Event Manager event listing
 */
function adfoin_wpeventmanagerac_send_data( $record, $posted_data ) {

    if ( ! post_type_exists( 'event_listing' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_event' !== $task ) {
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
        $response_body['message'] = __( 'An event title is required.', 'advanced-form-integration' );
    } else {
        $post_id = wp_insert_post(
            array(
                'post_type'    => 'event_listing',
                'post_title'   => $prepared_data['title'],
                'post_content' => isset( $prepared_data['description'] ) ? $prepared_data['description'] : '',
                'post_status'  => 'publish',
            )
        );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $response_body['message'] = __( 'Failed to create the event listing. Please verify the supplied data.', 'advanced-form-integration' );
        } else {
            $meta_map = array(
                'event_location'   => '_event_location',
                'event_start_date' => '_event_start_date',
                'event_end_date'   => '_event_end_date',
            );

            foreach ( $meta_map as $field_key => $meta_key ) {
                if ( isset( $prepared_data[ $field_key ] ) ) {
                    update_post_meta( $post_id, $meta_key, $prepared_data[ $field_key ] );
                }
            }

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Event listing created successfully.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'wpeventmanagerac', $log_args, $record );
}
