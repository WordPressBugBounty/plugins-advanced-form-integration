<?php

/**
 * LearnDash action platform — local same-site integration (no REST/API
 * keys). Slug is `learndashac`, not `learndash` — the trigger side already
 * uses that slug (includes/triggers/learndash/learndash.php), and this
 * codebase's convention for a same-slug trigger/action pair is an `ac` suffix
 * on the action (see gravityformsac, wpformsac, buddypressac).
 *
 * LearnDash isn't distributed on WordPress.org (commercial, sold directly
 * from learndash.com), so this was verified against the actual installed
 * copy on this site (wp-content/plugins/sfwd-lms/), not SVN.
 *
 * Enrollment is done via the real ld_update_course_access( $user_id,
 * $course_id, $remove = false ) function (includes/course/ld-course-user-functions.php)
 * — confirmed by reading its full implementation, which sets the course
 * access-from user meta and fires 'learndash_update_course_access'. Courses
 * are a plain custom post type, 'sfwd-courses' (confirmed via
 * includes/class-ld-lms.php's post type registration).
 */

add_filter( 'adfoin_action_providers', 'adfoin_learndashac_actions', 10, 1 );

function adfoin_learndashac_actions( $actions ) {

    $actions['learndashac'] = array(
        'title' => __( 'LearnDash', 'advanced-form-integration' ),
        'tasks' => array(
            'enroll_course' => __( 'Enroll User in Course', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_learndashac_action_fields' );

function adfoin_learndashac_action_fields() {
    ?>
    <script type="text/template" id="learndashac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'enroll_course'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'enroll_course'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Course', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[courseId]" v-model="fielddata.courseId" required="required">
                        <option value=""><?php _e( 'Select Course...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.courseList" :value="index">{{item}}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': courseLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_learndashac_courses', 'adfoin_get_learndashac_courses', 10, 0 );

function adfoin_get_learndashac_courses() {
    adfoin_verify_nonce();

    if ( ! post_type_exists( 'sfwd-courses' ) ) {
        wp_send_json_error( __( 'LearnDash is not active.', 'advanced-form-integration' ) );
    }

    $posts   = get_posts(
        array(
            'post_type'      => 'sfwd-courses',
            'post_status'    => 'publish',
            'posts_per_page' => 999,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );
    $courses = array();

    foreach ( $posts as $post ) {
        $courses[ $post->ID ] = $post->post_title;
    }

    wp_send_json_success( $courses );
}

add_action( 'wp_ajax_adfoin_get_learndashac_fields', 'adfoin_get_learndashac_fields', 10, 0 );

function adfoin_get_learndashac_fields() {
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
function adfoin_learndashac_find_or_create_user( $email, $name ) {
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

add_action( 'adfoin_learndashac_job_queue', 'adfoin_learndashac_job_queue', 10, 1 );

function adfoin_learndashac_job_queue( $data ) {
    adfoin_learndashac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles enrolling a user into a LearnDash course
 */
function adfoin_learndashac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'ld_update_course_access' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'enroll_course' !== $task ) {
        return;
    }

    $course_id = isset( $field_data['courseId'] ) ? absint( $field_data['courseId'] ) : 0;
    unset( $field_data['courseId'] );

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
    } elseif ( ! $course_id ) {
        $response_body['message'] = __( 'A course is required.', 'advanced-form-integration' );
    } else {
        $user_id = adfoin_learndashac_find_or_create_user( $prepared_data['email'], isset( $prepared_data['name'] ) ? $prepared_data['name'] : '' );

        if ( ! $user_id ) {
            $response_body['message'] = __( 'Failed to find or create the WordPress user.', 'advanced-form-integration' );
        } elseif ( ld_update_course_access( $user_id, $course_id, false ) ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'User enrolled in course successfully.', 'advanced-form-integration' );
            $response_body['id']      = $user_id;
        } else {
            $response_body['message'] = __( 'Failed to enroll the user in the course.', 'advanced-form-integration' );
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

    adfoin_add_to_log( $log_response, 'learndashac', $log_args, $record );
}
