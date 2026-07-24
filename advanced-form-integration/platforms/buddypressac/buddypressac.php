<?php

/**
 * BuddyPress action platform — local same-site integration (no REST/API
 * keys). Slug is `buddypressac`, not `buddypress` — the trigger side already
 * registers a Vue component named 'buddypress' (assets/js/triggers.js,
 * `simpleTriggers` factory list). The action loader's
 * adfoinComponentLoader.loadPlatform() short-circuits when
 * Vue.component(name) already resolves to anything, so reusing the same name
 * here would silently skip loading this action's real component — the exact
 * bug this codebase already hit and fixed once (gravityforms -> gravityformsac,
 * wpforms -> wpformsac).
 *
 * User resolution (find by email, else create) mirrors platforms/wordpress/wordpress.php's
 * create_user task. Group membership via groups_join_group() and profile
 * field updates via xprofile_set_field_data() — both confirmed against
 * BuddyPress's own source (bp-groups/bp-groups-functions.php,
 * bp-xprofile/bp-xprofile-functions.php).
 *
 * @link https://plugins.trac.wordpress.org/browser/buddypress/trunk/bp-groups/bp-groups-functions.php
 * @link https://plugins.trac.wordpress.org/browser/buddypress/trunk/bp-xprofile/bp-xprofile-functions.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_buddypressac_actions', 10, 1 );

function adfoin_buddypressac_actions( $actions ) {

    $actions['buddypressac'] = array(
        'title' => __( 'BuddyPress', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_group'         => __( 'Add User to Group', 'advanced-form-integration' ),
            'update_profile_field' => __( 'Update Profile Field', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_buddypressac_action_fields' );

function adfoin_buddypressac_action_fields() {
    ?>
    <script type="text/template" id="buddypressac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_to_group' || action.task == 'update_profile_field'">
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
                        <?php esc_attr_e( 'Group', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId" required="required">
                        <option value=""><?php _e( 'Select Group...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.groupList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': groupLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'update_profile_field'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Profile Field', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[profileFieldId]" v-model="fielddata.profileFieldId" required="required">
                        <option value=""><?php _e( 'Select Field...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.profileFieldList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': profileFieldLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_buddypressac_groups', 'adfoin_get_buddypressac_groups', 10, 0 );

function adfoin_get_buddypressac_groups() {
    adfoin_verify_nonce();

    if ( ! function_exists( 'groups_get_groups' ) ) {
        wp_send_json_error( __( 'BuddyPress (with Groups enabled) is not active.', 'advanced-form-integration' ) );
    }

    $result = groups_get_groups( array( 'per_page' => 999 ) );
    $groups = array();

    if ( ! empty( $result['groups'] ) ) {
        foreach ( $result['groups'] as $group ) {
            $groups[ $group->id ] = $group->name;
        }
    }

    wp_send_json_success( $groups );
}

add_action( 'wp_ajax_adfoin_get_buddypressac_profile_fields', 'adfoin_get_buddypressac_profile_fields', 10, 0 );

function adfoin_get_buddypressac_profile_fields() {
    adfoin_verify_nonce();

    if ( ! function_exists( 'bp_xprofile_get_groups' ) ) {
        wp_send_json_error( __( 'BuddyPress (with Extended Profiles enabled) is not active.', 'advanced-form-integration' ) );
    }

    $groups = bp_xprofile_get_groups( array( 'fetch_fields' => true ) );
    $fields = array();

    if ( is_array( $groups ) ) {
        foreach ( $groups as $group ) {
            if ( empty( $group->fields ) ) {
                continue;
            }

            foreach ( $group->fields as $field ) {
                $fields[ $field->id ] = $field->name;
            }
        }
    }

    wp_send_json_success( $fields );
}

add_action( 'wp_ajax_adfoin_get_buddypressac_fields', 'adfoin_get_buddypressac_fields', 10, 0 );

function adfoin_get_buddypressac_fields() {
    adfoin_verify_nonce();

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'User Email', 'advanced-form-integration' ), 'description' => __( 'Required. Existing user is matched by email, otherwise a new one is created.', 'advanced-form-integration' ) ),
    );

    if ( 'add_to_group' === $task ) {
        $fields[] = array( 'key' => 'name', 'value' => __( 'User Display Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) );
    } elseif ( 'update_profile_field' === $task ) {
        $fields[] = array( 'key' => 'name', 'value' => __( 'User Display Name', 'advanced-form-integration' ), 'description' => __( 'Only used if a new user is created.', 'advanced-form-integration' ) );
        $fields[] = array( 'key' => 'value', 'value' => __( 'Field Value', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) );
    }

    wp_send_json_success( $fields );
}

/**
 * Find an existing user by email, or create one.
 *
 * Mirrors platforms/wordpress/wordpress.php's create_user task: a random
 * password is generated since these are non-interactive, local integrations.
 */
function adfoin_buddypressac_find_or_create_user( $email, $name ) {
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

add_action( 'adfoin_buddypressac_job_queue', 'adfoin_buddypressac_job_queue', 10, 1 );

function adfoin_buddypressac_job_queue( $data ) {
    adfoin_buddypressac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles BuddyPress group membership / profile field updates
 */
function adfoin_buddypressac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'groups_join_group' ) && ! function_exists( 'xprofile_set_field_data' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $group_id         = isset( $field_data['groupId'] ) ? absint( $field_data['groupId'] ) : 0;
    $profile_field_id = isset( $field_data['profileFieldId'] ) ? absint( $field_data['profileFieldId'] ) : 0;
    unset( $field_data['groupId'], $field_data['profileFieldId'] );

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
    } elseif ( 'add_to_group' === $task ) {
        if ( ! $group_id ) {
            $response_body['message'] = __( 'A group is required.', 'advanced-form-integration' );
        } elseif ( ! function_exists( 'groups_join_group' ) ) {
            $response_body['message'] = __( 'BuddyPress Groups is not active.', 'advanced-form-integration' );
        } else {
            $user_id = adfoin_buddypressac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

            if ( ! $user_id ) {
                $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
            } elseif ( groups_join_group( $group_id, $user_id ) ) {
                $status_code              = 200;
                $response_body['success'] = true;
                $response_body['message'] = __( 'User added to group successfully.', 'advanced-form-integration' );
                $response_body['id']      = $user_id;
            } else {
                $response_body['message'] = __( 'Failed to add the user to the group.', 'advanced-form-integration' );
            }
        }
    } elseif ( 'update_profile_field' === $task ) {
        if ( ! $profile_field_id ) {
            $response_body['message'] = __( 'A profile field is required.', 'advanced-form-integration' );
        } elseif ( empty( $prepared_data['value'] ) ) {
            $response_body['message'] = __( 'A field value is required.', 'advanced-form-integration' );
        } elseif ( ! function_exists( 'xprofile_set_field_data' ) ) {
            $response_body['message'] = __( 'BuddyPress Extended Profiles is not active.', 'advanced-form-integration' );
        } else {
            $user_id = adfoin_buddypressac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

            if ( ! $user_id ) {
                $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
            } elseif ( xprofile_set_field_data( $profile_field_id, $user_id, $prepared_data['value'] ) ) {
                $status_code              = 200;
                $response_body['success'] = true;
                $response_body['message'] = __( 'Profile field updated successfully.', 'advanced-form-integration' );
                $response_body['id']      = $user_id;
            } else {
                $response_body['message'] = __( 'Failed to update the profile field.', 'advanced-form-integration' );
            }
        }
    } else {
        return;
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

    adfoin_add_to_log( $log_response, 'buddypressac', $log_args, $record );
}
