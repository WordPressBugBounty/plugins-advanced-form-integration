<?php

/**
 * Awesome Support action platform — local same-site integration (no
 * REST/API keys). Slug is `awesomesupportac`, not `awesomesupport` — the
 * trigger side already uses that slug (includes/triggers/awesomesupport/awesomesupport.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Ticket creation goes through the real wpas_insert_ticket( $data, $post_id,
 * $agent_id, $channel_term ) function (includes/functions-post.php),
 * confirmed by reading its full implementation. Important: it enforces
 * `user_can( $data['post_author'], 'create_ticket' )` and returns false
 * otherwise — a plain new WP user (default role) does NOT have this
 * capability, only the plugin's own default customer role does. Confirmed
 * via includes/functions-user.php: new customers get
 * wpas_get_option('new_user_role', 'wpas_user') — so new users created here
 * are explicitly given the 'wpas_user' role, not a generic default.
 *
 * @link https://plugins.trac.wordpress.org/browser/awesome-support/trunk/includes/functions-post.php
 * @link https://plugins.trac.wordpress.org/browser/awesome-support/trunk/includes/functions-user.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_awesomesupportac_actions', 10, 1 );

function adfoin_awesomesupportac_actions( $actions ) {

    $actions['awesomesupportac'] = array(
        'title' => __( 'Awesome Support', 'advanced-form-integration' ),
        'tasks' => array(
            'add_ticket' => __( 'Create Ticket', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_awesomesupportac_action_fields' );

function adfoin_awesomesupportac_action_fields() {
    ?>
    <script type="text/template" id="awesomesupportac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_ticket'">
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

add_action( 'wp_ajax_adfoin_get_awesomesupportac_fields', 'adfoin_get_awesomesupportac_fields', 10, 0 );

function adfoin_get_awesomesupportac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Customer Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing user is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'Customer Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
        array( 'key' => 'message', 'value' => __( 'Message', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

/**
 * Find an existing user by email, or create one with the 'wpas_user' role
 * (Awesome Support's own default customer role — a plain default-role user
 * lacks the 'create_ticket' capability wpas_insert_ticket() requires).
 */
function adfoin_awesomesupportac_find_or_create_user( $email, $name ) {
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

    $role = get_role( 'wpas_user' ) ? 'wpas_user' : 'subscriber';

    $user_id = wp_insert_user(
        array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password( 24 ),
            'display_name' => $name ? $name : $email,
            'role'         => $role,
        )
    );

    return is_wp_error( $user_id ) ? 0 : $user_id;
}

add_action( 'adfoin_awesomesupportac_job_queue', 'adfoin_awesomesupportac_job_queue', 10, 1 );

function adfoin_awesomesupportac_job_queue( $data ) {
    adfoin_awesomesupportac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating an Awesome Support ticket
 */
function adfoin_awesomesupportac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'wpas_insert_ticket' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_ticket' !== $task ) {
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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) || empty( $prepared_data['subject'] ) || empty( $prepared_data['message'] ) ) {
        $response_body['message'] = __( 'A valid email, subject, and message are all required.', 'advanced-form-integration' );
    } else {
        $user_id = adfoin_awesomesupportac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

        if ( ! $user_id ) {
            $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
        } else {
            $ticket_id = wpas_insert_ticket(
                array(
                    'post_title'   => $prepared_data['subject'],
                    'post_content' => $prepared_data['message'],
                    'post_author'  => $user_id,
                ),
                false,
                false,
                'other'
            );

            if ( $ticket_id ) {
                $status_code              = 200;
                $response_body['success'] = true;
                $response_body['message'] = __( 'Ticket created successfully.', 'advanced-form-integration' );
                $response_body['id']      = $ticket_id;
            } else {
                $response_body['message'] = __( 'Failed to create the ticket. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'awesomesupportac', $log_args, $record );
}
