<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get WP Courseware Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_wpcourseware_get_forms( $form_provider ) {
	if ( $form_provider != 'courseware' ) {
		return;
	}

	$triggers = array(
		'courseCompleted'  => __( 'Course Completed', 'advanced-form-integration' ),
		'moduleCompleted'  => __( 'Module Completed', 'advanced-form-integration' ),
		'unitCompleted'    => __( 'Unit Completed', 'advanced-form-integration' ),
		'userEnrolled'     => __( 'User Enrolled', 'advanced-form-integration' ),
		'userUnenrolled'   => __( 'User Unenrolled', 'advanced-form-integration' ),
	);

	return $triggers;
}

/**
 * Get WP Courseware Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_wpcourseware_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider != 'courseware' ) {
		return;
	}

	$fields = array();

	if ( $form_id === 'courseCompleted' ) {
		$fields = array(
			'course_id'    => __( 'Course ID', 'advanced-form-integration' ),
			'course_title' => __( 'Course Title', 'advanced-form-integration' ),
			'user_id'      => __( 'User ID', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'moduleCompleted' ) {
		$fields = array(
			'module_id'    => __( 'Module ID', 'advanced-form-integration' ),
			'module_title' => __( 'Module Title', 'advanced-form-integration' ),
			'user_id'      => __( 'User ID', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'unitCompleted' ) {
		$fields = array(
			'unit_id'      => __( 'Unit ID', 'advanced-form-integration' ),
			'unit_title'   => __( 'Unit Title', 'advanced-form-integration' ),
			'user_id'      => __( 'User ID', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'userEnrolled' ) {
		$fields = array(
			'course_id'    => __( 'Course ID', 'advanced-form-integration' ),
			'course_title' => __( 'Course Title', 'advanced-form-integration' ),
			'user_id'      => __( 'User ID', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'userUnenrolled' ) {
		$fields = array(
			'course_id'    => __( 'Course ID', 'advanced-form-integration' ),
			'course_title' => __( 'Course Title', 'advanced-form-integration' ),
			'user_id'      => __( 'User ID', 'advanced-form-integration' ),
		);
	}

	return $fields;
}

/**
 * Get User Data.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_wpcourseware_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );
	if ( $user ) {
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
		$user_data['user_email'] = $user->user_email;
		$user_data['user_id']    = $user_id;
	}
	return $user_data;
}

/**
 * Handle Course Completion.
 *
 * Fires when a user completes a course.
 *
 * @param int   $user_id    The user ID.
 * @param int   $course_id  The course ID.
 * @param mixed $course_data Additional course data.
 */
function adfoin_wpcourseware_handle_course_completion( $user_id, $course_id, $course_data ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'courseware', 'courseCompleted' );
	if ( empty( $saved_records ) ) {
		return;
	}

	$posted_data = array(
		'course_id'    => $course_id,
		'course_title' => isset( $course_data->course_title ) ? $course_data->course_title : '',
		'user_id'      => $user_id,
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpcw_user_completed_course', 'adfoin_wpcourseware_handle_course_completion', 10, 3 );

/**
 * Handle Module Completion.
 *
 * Fires when a user completes a module.
 *
 * @param int   $user_id    The user ID.
 * @param int   $module_id  The module ID.
 * @param mixed $module_data Additional module data.
 */
function adfoin_wpcourseware_handle_module_completion( $user_id, $module_id, $module_data ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'courseware', 'moduleCompleted' );
	if ( empty( $saved_records ) ) {
		return;
	}

	$posted_data = array(
		'module_id'    => $module_id,
		'module_title' => isset( $module_data->module_title ) ? $module_data->module_title : '',
		'user_id'      => $user_id,
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpcw_user_completed_module', 'adfoin_wpcourseware_handle_module_completion', 10, 3 );

/**
 * Handle Unit Completion.
 *
 * Fires when a user completes a unit.
 *
 * @param int   $user_id   The user ID.
 * @param int   $unit_id   The unit ID.
 * @param mixed $unit_data Additional unit data.
 */
function adfoin_wpcourseware_handle_unit_completion( $user_id, $unit_id, $unit_data ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'courseware', 'unitCompleted' );
	if ( empty( $saved_records ) ) {
		return;
	}

	$posted_data = array(
		'unit_id'    => $unit_id,
		'unit_title' => isset( $unit_data->unit_title ) ? $unit_data->unit_title : '',
		'user_id'    => $user_id,
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpcw_user_completed_unit', 'adfoin_wpcourseware_handle_unit_completion', 10, 3 );

/**
 * Handle User Enrollment.
 *
 * Fires when a user is enrolled in one or more courses.
 *
 * @param int   $user_id         The user ID.
 * @param array $courses_enrolled Array of enrolled course IDs.
 */
function adfoin_wpcourseware_handle_user_enroll( $user_id, $courses_enrolled ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'courseware', 'userEnrolled' );
	if ( empty( $saved_records ) ) {
		return;
	}

	// For simplicity, send data for the first course.
	$course_id = reset( $courses_enrolled );
	$course_data = (object) array(
		'course_title' => 'Course #' . $course_id,
	);
	$posted_data = array(
		'course_id'    => $course_id,
		'course_title' => $course_data->course_title,
		'user_id'      => $user_id,
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpcw_enroll_user', 'adfoin_wpcourseware_handle_user_enroll', 10, 2 );

/**
 * Handle User Unenrollment.
 *
 * Fires when a user is unenrolled from one or more courses.
 *
 * @param int   $user_id             The user ID.
 * @param array $course_ids_to_remove Array of course IDs to remove.
 */
function adfoin_wpcourseware_handle_user_unenroll( $user_id, $course_ids_to_remove ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'courseware', 'userUnenrolled' );
	if ( empty( $saved_records ) ) {
		return;
	}

	// For simplicity, send data for the first course removed.
	$course_id = reset( $course_ids_to_remove );
	$course_data = (object) array(
		'course_title' => 'Course #' . $course_id,
	);
	$posted_data = array(
		'course_id'    => $course_id,
		'course_title' => $course_data->course_title,
		'user_id'      => $user_id,
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'wpcw_unenroll_user', 'adfoin_wpcourseware_handle_user_unenroll', 10, 2 );