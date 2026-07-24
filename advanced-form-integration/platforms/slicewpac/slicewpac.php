<?php

/**
 * SliceWP action platform — local same-site integration (no REST/API keys).
 * Slug is `slicewpac`, not `slicewp` — the trigger side already uses that
 * slug (includes/triggers/slicewp/slicewp.php); this codebase's convention
 * for a same-slug trigger/action pair is an `ac` suffix on the action (see
 * gravityformsac, wpformsac, buddypressac).
 *
 * Affiliate creation goes through the real slicewp_insert_affiliate( $data )
 * function (includes/base/affiliate/functions.php) — a thin wrapper around
 * slicewp()->db['affiliates']->insert( $data ), confirmed against the
 * plugin's own source. Requires a 'user_id'; other keys (status,
 * payment_email, website) are optional.
 *
 * @link https://plugins.trac.wordpress.org/browser/slicewp/trunk/includes/base/affiliate/functions.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_slicewpac_actions', 10, 1 );

function adfoin_slicewpac_actions( $actions ) {

    $actions['slicewpac'] = array(
        'title' => __( 'SliceWP', 'advanced-form-integration' ),
        'tasks' => array(
            'add_affiliate' => __( 'Add Affiliate', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_slicewpac_action_fields' );

function adfoin_slicewpac_action_fields() {
    ?>
    <script type="text/template" id="slicewpac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_affiliate'">
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

add_action( 'wp_ajax_adfoin_get_slicewpac_fields', 'adfoin_get_slicewpac_fields', 10, 0 );

function adfoin_get_slicewpac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing user is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'payment_email', 'value' => __( 'Payment Email', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'website', 'value' => __( 'Website', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

/**
 * Find an existing user by email, or create one.
 *
 * Mirrors platforms/wordpress/wordpress.php's create_user task: a random
 * password is generated since these are non-interactive, local integrations.
 */
function adfoin_slicewpac_find_or_create_user( $email, $name ) {
    if ( ! is_email( $email ) ) {
        return 0;
    }

    $user = get_user_by( 'email', $email );

    if ( $user ) {
        return $user->ID;
    }

    $username = sanitize_user( current( explode( '@', $email ) ), true );

    if ( username_exists( $username ) ) {
        $username .= wp_rand( 100, 999 );
    }

    $user_id = wp_insert_user(
        array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24 ),
            'display_name' => $name ? $name : $email,
        )
    );

    return is_wp_error( $user_id ) ? 0 : $user_id;
}

add_action( 'adfoin_slicewpac_job_queue', 'adfoin_slicewpac_job_queue', 10, 1 );

function adfoin_slicewpac_job_queue( $data ) {
    adfoin_slicewpac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles adding a SliceWP affiliate
 */
function adfoin_slicewpac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'slicewp_insert_affiliate' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_affiliate' !== $task ) {
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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) ) {
        $response_body['message'] = __( 'A valid email address is required.', 'advanced-form-integration' );
    } else {
        $user_id = adfoin_slicewpac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

        if ( ! $user_id ) {
            $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
        } else {
            $affiliate_id = slicewp_insert_affiliate(
                array(
                    'user_id'       => $user_id,
                    'status'        => 'active',
                    'payment_email' => isset( $prepared_data['payment_email'] ) ? $prepared_data['payment_email'] : '',
                    'website'       => isset( $prepared_data['website'] ) ? $prepared_data['website'] : '',
                )
            );

            if ( $affiliate_id ) {
                $status_code              = 200;
                $response_body['success'] = true;
                $response_body['message'] = __( 'Affiliate added successfully.', 'advanced-form-integration' );
                $response_body['id']      = $affiliate_id;
            } else {
                $response_body['message'] = __( 'Failed to add the affiliate. Please verify the supplied data.', 'advanced-form-integration' );
            }
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

    adfoin_add_to_log( $log_response, 'slicewpac', $log_args, $record );
}
