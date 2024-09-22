<?php

add_filter( 'adfoin_action_providers', 'adfoin_academylms_actions', 10, 1 );

function adfoin_academylms_actions( $actions ) {

    $actions['academylms'] = array(
        'title' => __( 'Academy LMS', 'advanced-form-integration' ),
        'tasks' => array(
            'enroll'   => __( 'Enroll to a course', 'advanced-form-integration' ),
            'unenroll' => __( 'Unenroll from a course', 'advanced-form-integration' ),
            // 'completeCourse' => __( 'Complete a course', 'advanced-form-integration' ),
            // 'completeLesson' => __( 'Complete a lesson', 'advanced-form-integration' ),
            // 'resetProgress' => __( 'Reset course progress', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_academylms_settings_tab', 10, 1 );

function adfoin_academylms_settings_tab( $providers ) {
    $providers['academylms'] = __( 'Academy LMS', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_academylms_settings_view', 10, 1 );

function adfoin_academylms_settings_view( $current_tab ) {
    if( $current_tab != 'academylms' ) {
        return;
    }

    $nonce        = wp_create_nonce( 'adfoin_academylms_settings' );
    ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					
						<h2 class="hndle"><span><?php esc_attr_e( 'Academy LMS', 'advanced-form-integration' ); ?></span></h2>
						<div class="inside">
                            <p>
                                Make sure that the plugin is activated. Then, set up a new integration and proceed.
                            </p>
						</div>
						<!-- .inside -->
					
				</div>
				<!-- .meta-box-sortables .ui-sortable -->
			</div>
			<!-- post-body-content -->


		</div>
		<!-- #post-body .metabox-holder .columns-2 -->
		<br class="clear">
	</div>
    <?php
}

add_action( 'adfoin_action_fields', 'adfoin_academylms_action_fields' );

function adfoin_academylms_action_fields() {
    ?>
    <script type="text/template" id="academylms-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Course', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[courseId]" v-model="fielddata.courseId">
                        <option value=""> <?php _e( 'Select Course...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.courses" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': courseLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_academylms_courses', 'adfoin_get_academylms_courses', 10, 0 );

function adfoin_get_academylms_courses() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $course_list = get_posts( array(
        'post_type' => 'academy_courses',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ) );

    $courses = wp_list_pluck( $course_list, 'post_title', 'ID' );
    
    wp_send_json_success( $courses );
}

add_action( 'wp_ajax_adfoin_get_academylms_lessons', 'adfoin_get_academylms_lessons', 10, 0 );

function adfoin_get_academylms_lessons() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $lesson_list = \Academy\Traits\Lessons::get_lessons();

    $lessons = array();

    foreach( $lesson_list as $lesson ) {
        $lessons[$lesson->ID] = $lesson->lesson_title;
    }
    
    wp_send_json_success( $lessons );
}

add_action( 'adfoin_academylms_job_queue', 'adfoin_academylms_job_queue', 10, 1 );

function adfoin_academylms_job_queue( $data ) {
    adfoin_academylms_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to academylms API
 */
function adfoin_academylms_send_data( $record, $posted_data ) {

    $record_data    = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data['field_data'];
    $course_id = empty( $data['courseId'] ) ? '' : adfoin_get_parsed_values( $data['courseId'], $posted_data );
    // $lesson_id = empty( $data['lessonId'] ) ? '' : adfoin_get_parsed_values( $data['lessonId'], $posted_data );
    $task    = $record['task'];
    $email = isset( $data['email'] ) ? adfoin_get_parsed_values( $data['email'], $posted_data ) : '';

    if( $task == 'enroll' ) {
        adfoin_academylms_enroll_course( $course_id, $email, $data, $posted_data );
    } elseif( $task == 'unenroll' ) {
        adfoin_academylms_unenroll_course( $course_id, $email );
    } else {
        return;
    }

    return;
}

function adfoin_academylms_enroll_course( $course_id, $email, $data, $posted_data ) {
    $course_id = intval( $course_id );

    $user_id = email_exists( $email );

    if( !$user_id ) {
        $first_name = isset( $data['firstName'] ) ? adfoin_get_parsed_values( $data['firstName'], $posted_data ) : '';
        $last_name = isset( $data['lastName'] ) ? adfoin_get_parsed_values( $data['lastName'], $posted_data ) : '';
        $username = isset( $data['username'] ) ? adfoin_get_parsed_values( $data['username'], $posted_data ) : '';
        $password = isset( $data['password'] ) ? adfoin_get_parsed_values( $data['password'], $posted_data ) : '';

        if( !$password ) {
            $password = wp_generate_password( 12, false );
        }

        $user_id = wp_create_user( $username, $password, $email );
        wp_update_user( array( 'ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name ) );
    }
             
    if( $course_id && $user_id ) {
        $user = new WP_User( $user_id );
        $user->add_role( 'academy_student' );

        add_filter( 'is_course_purchasable', '__return_false', 10 );
        $result = \Academy\Helper::do_enroll( $course_id, $user_id );
        remove_filter( 'is_course_purchasable', '__return_false', 10 );
    }
}

function adfoin_academylms_unenroll_course( $course_id, $email ) {

    $course_id = intval( $course_id );
    $user_id = email_exists( $email );

    if( $course_id && $user_id ) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT ID,
                post_author,
                post_date,
                post_date_gmt,
                post_title,
                post_status as enrolled_status
            FROM {$wpdb->posts}
            WHERE post_type = %s
                AND post_parent = %d
                AND post_author = %d",
            'academy_enrolled',
            $course_id,
            $user_id
        );

        $enrolled = $wpdb->get_row( $query );

        if( $enrolled ) {
			$wpdb->delete(
				$wpdb->posts,
				array(
					'post_type'   => 'academy_enrolled',
					'post_author' => $user_id,
					'post_parent' => $course_id
				)
			);

			$order_id = get_post_meta( $enrolled->ID, 'academy_enrolled_by_order_id', true );
			delete_post_meta( $enrolled->ID, 'academy_enrolled_by_order_id' );
			delete_post_meta( $enrolled->ID, 'academy_enrolled_by_product_id' );
			delete_post_meta( $order_id, 'is_academy_order_for_course' );
			delete_post_meta( $order_id, 'academy_order_for_course_id_' . $course_id );
			delete_user_meta( $user_id, 'academy_course_' . $course_id . '_completed_topics' );
        }
    }
}