<?php

/**
 * Paid Memberships Pro action platform — local same-site integration (no
 * REST/API keys). Note: PMPro left the WordPress.org plugin directory in Oct
 * 2024 (self-hosted updates since, at paidmembershipspro.com) but is still
 * actively maintained; the functions below are unchanged from before that move.
 *
 * User resolution (find by email, else create) mirrors platforms/wordpress/wordpress.php's
 * create_user task. Level list via pmpro_getAllLevels(true) and the actual
 * level change via pmpro_changeMembershipLevel($level_id, $user_id) — both
 * confirmed against the plugin's own source (includes/functions.php).
 *
 * @link https://plugins.trac.wordpress.org/browser/paid-memberships-pro/trunk/includes/functions.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_paidmembershippproac_actions', 10, 1 );

function adfoin_paidmembershippproac_actions( $actions ) {

    $actions['paidmembershippproac'] = array(
        'title' => __( 'Paid Memberships Pro', 'advanced-form-integration' ),
        'tasks' => array(
            'add_member' => __( 'Add/Update Member', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_paidmembershippproac_action_fields' );

function adfoin_paidmembershippproac_action_fields() {
    ?>
    <script type="text/template" id="paidmembershippproac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_member'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_member'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Membership Level', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[levelId]" v-model="fielddata.levelId" required="required">
                        <option value=""><?php _e( 'Select Level...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.levelList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': levelLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_paidmembershippproac_levels', 'adfoin_get_paidmembershippproac_levels', 10, 0 );

function adfoin_get_paidmembershippproac_levels() {
    adfoin_verify_nonce();

    if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
        wp_send_json_error( __( 'Paid Memberships Pro is not active.', 'advanced-form-integration' ) );
    }

    $raw_levels = pmpro_getAllLevels( true );
    $levels     = array();

    if ( is_array( $raw_levels ) ) {
        foreach ( $raw_levels as $level ) {
            $levels[ $level->id ] = $level->name;
        }
    }

    wp_send_json_success( $levels );
}

add_action( 'wp_ajax_adfoin_get_paidmembershippproac_fields', 'adfoin_get_paidmembershippproac_fields', 10, 0 );

function adfoin_get_paidmembershippproac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing user is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

/**
 * Find an existing user by email, or create one.
 *
 * Mirrors platforms/wordpress/wordpress.php's create_user task: a random
 * password is generated since these are non-interactive, local integrations.
 */
function adfoin_paidmembershippproac_find_or_create_user( $email, $first_name, $last_name ) {
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
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => wp_generate_password( 24 ),
            'first_name' => $first_name,
            'last_name'  => $last_name,
        )
    );

    return is_wp_error( $user_id ) ? 0 : $user_id;
}

add_action( 'adfoin_paidmembershippproac_job_queue', 'adfoin_paidmembershippproac_job_queue', 10, 1 );

function adfoin_paidmembershippproac_job_queue( $data ) {
    adfoin_paidmembershippproac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles adding/updating a Paid Memberships Pro member
 */
function adfoin_paidmembershippproac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'add_member' !== $task ) {
        return;
    }

    $level_id = isset( $field_data['levelId'] ) ? absint( $field_data['levelId'] ) : 0;
    unset( $field_data['levelId'] );

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
    } elseif ( ! $level_id ) {
        $response_body['message'] = __( 'A membership level is required.', 'advanced-form-integration' );
    } else {
        $user_id = adfoin_paidmembershippproac_find_or_create_user(
            $prepared_data['email'],
            isset( $prepared_data['first_name'] ) ? $prepared_data['first_name'] : '',
            isset( $prepared_data['last_name'] ) ? $prepared_data['last_name'] : ''
        );

        if ( ! $user_id ) {
            $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
        } elseif ( pmpro_changeMembershipLevel( $level_id, $user_id ) ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Member added/updated successfully.', 'advanced-form-integration' );
            $response_body['id']      = $user_id;
        } else {
            $response_body['message'] = __( 'Failed to change the membership level. Please verify the supplied data.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'paidmembershippproac', $log_args, $record );
}
