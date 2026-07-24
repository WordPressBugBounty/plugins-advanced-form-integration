<?php

/**
 * Ultimate Member action platform — local same-site integration (no
 * REST/API keys). Slug is `ultimatememberac`, not `ultimatemember` — the
 * trigger side already uses that slug (includes/triggers/ultimatemember/ultimatemember.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Writes through the real UM()->user()->set( $user_id ) + UM()->user()->update_profile(
 * $changes ) methods (includes/core/class-user.php), confirmed against the
 * plugin's own source. $changes is a flat associative array — keys matching
 * UM's core user fields (user_email, display_name, user_url, role, etc) are
 * routed to wp_update_user(); everything else is saved as usermeta. Since
 * UM's custom profile fields are admin-configurable (like ACF), the field
 * key is a free-text input here rather than a fixed dropdown, same approach
 * as platforms/acfac/acfac.php.
 *
 * @link https://plugins.trac.wordpress.org/browser/ultimate-member/trunk/includes/core/class-user.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_ultimatememberac_actions', 10, 1 );

function adfoin_ultimatememberac_actions( $actions ) {

    $actions['ultimatememberac'] = array(
        'title' => __( 'Ultimate Member', 'advanced-form-integration' ),
        'tasks' => array(
            'update_profile_field' => __( 'Update Profile Field', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_ultimatememberac_action_fields' );

function adfoin_ultimatememberac_action_fields() {
    ?>
    <script type="text/template" id="ultimatememberac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'update_profile_field'">
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

add_action( 'wp_ajax_adfoin_get_ultimatememberac_fields', 'adfoin_get_ultimatememberac_fields', 10, 0 );

function adfoin_get_ultimatememberac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'User Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing user is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'User Display Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'meta_key', 'value' => __( 'Field Key', 'advanced-form-integration' ), 'description' => __( 'Required. The UM profile field\'s meta key (e.g. description, phone_number).', 'advanced-form-integration' ) ),
        array( 'key' => 'value', 'value' => __( 'Value', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

/**
 * Find an existing user by email, or create one.
 *
 * Mirrors platforms/wordpress/wordpress.php's create_user task: a random
 * password is generated since these are non-interactive, local integrations.
 */
function adfoin_ultimatememberac_find_or_create_user( $email, $name ) {
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

add_action( 'adfoin_ultimatememberac_job_queue', 'adfoin_ultimatememberac_job_queue', 10, 1 );

function adfoin_ultimatememberac_job_queue( $data ) {
    adfoin_ultimatememberac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles writing a Ultimate Member profile field value
 */
function adfoin_ultimatememberac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'UM' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'update_profile_field' !== $task ) {
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

    if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) || empty( $prepared_data['meta_key'] ) || ! isset( $prepared_data['value'] ) ) {
        $response_body['message'] = __( 'Email, field key, and value are all required.', 'advanced-form-integration' );
    } else {
        $user_id = adfoin_ultimatememberac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

        if ( ! $user_id ) {
            $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
        } else {
            UM()->user()->set( $user_id );
            // 'account' context is required — any other value (including the
            // default '') sets updating_process = true, which enables banned-key
            // validation and silently drops any key that isn't already a
            // registered UM custom field. 'account' skips that check, same as
            // updating your own profile from the front-end account page would.
            UM()->user()->update_profile( array( $prepared_data['meta_key'] => $prepared_data['value'] ), 'account' );

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Profile field updated successfully.', 'advanced-form-integration' );
            $response_body['id']      = $user_id;
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

    adfoin_add_to_log( $log_response, 'ultimatememberac', $log_args, $record );
}
