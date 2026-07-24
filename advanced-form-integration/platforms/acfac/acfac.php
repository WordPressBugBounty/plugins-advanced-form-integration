<?php

/**
 * Advanced Custom Fields (ACF) action platform — local same-site integration
 * (no REST/API keys). Slug is `acfac`, not `acf` — the trigger side already
 * uses that slug (includes/triggers/acf/acf.php); this codebase's convention
 * for a same-slug trigger/action pair is an `ac` suffix on the action (see
 * gravityformsac, wpformsac, buddypressac).
 *
 * Writes through the real global update_field( $selector, $value, $post_id )
 * function (includes/api/api-template.php), confirmed against the plugin's
 * own source. Its full body shows it gracefully falls back to a plain
 * meta-value save (via acf_update_value()) even when $selector doesn't match
 * a registered field — so the selector can be either the field's name or key,
 * same as ACF's own documented usage. $post_id follows ACF's own convention
 * ('123' for a post, 'user_5' for a user, 'option' for the options page,
 * 'term_9' for a term) rather than a plain integer only.
 *
 * @link https://plugins.trac.wordpress.org/browser/advanced-custom-fields/trunk/includes/api/api-template.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_acfac_actions', 10, 1 );

function adfoin_acfac_actions( $actions ) {

    $actions['acfac'] = array(
        'title' => __( 'Advanced Custom Fields', 'advanced-form-integration' ),
        'tasks' => array(
            'update_field' => __( 'Update Field', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_acfac_action_fields' );

function adfoin_acfac_action_fields() {
    ?>
    <script type="text/template" id="acfac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'update_field'">
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

add_action( 'wp_ajax_adfoin_get_acfac_fields', 'adfoin_get_acfac_fields', 10, 0 );

function adfoin_get_acfac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array(
            'key'         => 'postId',
            'value'       => __( 'Post ID', 'advanced-form-integration' ),
            'description' => __( 'Required. A post ID (123), or ACF\'s own conventions: user_5, term_9, option.', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'selector',
            'value'       => __( 'Field Name or Key', 'advanced-form-integration' ),
            'description' => __( 'Required. The ACF field\'s name (e.g. phone_number) or key (e.g. field_123).', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'value',
            'value'       => __( 'Value', 'advanced-form-integration' ),
            'description' => __( 'Required.', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_acfac_job_queue', 'adfoin_acfac_job_queue', 10, 1 );

function adfoin_acfac_job_queue( $data ) {
    adfoin_acfac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles writing an ACF field value
 */
function adfoin_acfac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'update_field' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'update_field' !== $task ) {
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

    if ( empty( $prepared_data['postId'] ) || empty( $prepared_data['selector'] ) || ! isset( $prepared_data['value'] ) ) {
        $response_body['message'] = __( 'Post ID, field name/key, and value are all required.', 'advanced-form-integration' );
    } else {
        $updated = update_field( $prepared_data['selector'], $prepared_data['value'], $prepared_data['postId'] );

        if ( $updated ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Field updated successfully.', 'advanced-form-integration' );
        } else {
            $response_body['message'] = __( 'Failed to update the field. Please verify the supplied post ID and field name/key.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'acfac', $log_args, $record );
}
