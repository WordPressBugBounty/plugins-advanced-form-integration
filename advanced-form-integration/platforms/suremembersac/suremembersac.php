<?php

/**
 * SureMembers action platform — local same-site integration (no REST/API
 * keys). Slug is `suremembersac`, not `suremembers` — the trigger side
 * already uses that slug (includes/triggers/suremembers/suremembers.php);
 * this codebase's convention for a same-slug trigger/action pair is an `ac`
 * suffix on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * Access is granted via the real \SureMembersCore\Inc\Access::grant(
 * $user_id, $access_group_ids, $integration, $expiration, $send_email )
 * static method (inc/access.php), confirmed against the plugin's own source.
 * Access groups are a plain custom post type referenced via the
 * SUREMEMBERS_POST_TYPE constant rather than a hardcoded guessed slug string.
 *
 * @link https://plugins.trac.wordpress.org/browser/suremembers-core/trunk/inc/access.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_suremembersac_actions', 10, 1 );

function adfoin_suremembersac_actions( $actions ) {

    $actions['suremembersac'] = array(
        'title' => __( 'SureMembers', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_group' => __( 'Add User to Access Group', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_suremembersac_action_fields' );

function adfoin_suremembersac_action_fields() {
    ?>
    <script type="text/template" id="suremembersac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_to_group'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_to_group'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Access Group', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId" required="required">
                        <option value=""><?php _e( 'Select Access Group...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.groupList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_suremembersac_groups', 'adfoin_get_suremembersac_groups', 10, 0 );

function adfoin_get_suremembersac_groups() {
    adfoin_verify_nonce();

    if ( ! defined( 'SUREMEMBERS_POST_TYPE' ) ) {
        wp_send_json_error( __( 'SureMembers is not active.', 'advanced-form-integration' ) );
    }

    $posts  = get_posts(
        array(
            'post_type'      => SUREMEMBERS_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 999,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
    $groups = array();

    foreach ( $posts as $post ) {
        $groups[ $post->ID ] = $post->post_title;
    }

    wp_send_json_success( $groups );
}

add_action( 'wp_ajax_adfoin_get_suremembersac_fields', 'adfoin_get_suremembersac_fields', 10, 0 );

function adfoin_get_suremembersac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'User Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing user is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'User Display Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

/**
 * Find an existing user by email, or create one.
 *
 * Mirrors platforms/wordpress/wordpress.php's create_user task: a random
 * password is generated since these are non-interactive, local integrations.
 */
function adfoin_suremembersac_find_or_create_user( $email, $name ) {
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

add_action( 'adfoin_suremembersac_job_queue', 'adfoin_suremembersac_job_queue', 10, 1 );

function adfoin_suremembersac_job_queue( $data ) {
    adfoin_suremembersac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles granting SureMembers access group access
 */
function adfoin_suremembersac_send_data( $record, $posted_data ) {

    if ( ! class_exists( '\SureMembersCore\Inc\Access' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_to_group' !== $task ) {
        return;
    }

    $group_id = isset( $field_data['groupId'] ) ? absint( $field_data['groupId'] ) : 0;
    unset( $field_data['groupId'] );

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
        $response_body['message'] = __( 'A valid user email is required.', 'advanced-form-integration' );
    } elseif ( ! $group_id ) {
        $response_body['message'] = __( 'An access group is required.', 'advanced-form-integration' );
    } else {
        $user_id = adfoin_suremembersac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

        if ( ! $user_id ) {
            $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
        } else {
            \SureMembersCore\Inc\Access::grant( $user_id, array( $group_id ) );

            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'User added to access group successfully.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'suremembersac', $log_args, $record );
}
