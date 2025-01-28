<?php

// Get Thrive Apprentice Triggers
function adfoin_thriveapprentice_get_forms( $form_provider ) {
    if ( $form_provider != 'thriveapprentice' ) {
        return;
    }

    $triggers = array(
        'completeCourse' => __( 'Complete a Course', 'advanced-form-integration' ),
        'completeLesson' => __( 'Complete a Lesson', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Thrive Apprentice Fields
function adfoin_thriveapprentice_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'thriveapprentice' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'completeCourse' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'course_title' => __( 'Course Title', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'completeLesson' ) {
        $fields = array(
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'lesson_id' => __( 'Lesson ID', 'advanced-form-integration' ),
            'lesson_title' => __( 'Lesson Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'course_title' => __( 'Course Title', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Course Completion
function adfoin_thriveapprentice_handle_course_complete( $course_details, $user_details ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'thriveapprentice', 'completeCourse' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'user_id' => $user_details['user_id'],
        'course_id' => $course_details['course_id'],
        'course_title' => $course_details['course_title'],
    );

    adfoin_thriveapprentice_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'thrive_apprentice_course_finish', 'adfoin_thriveapprentice_handle_course_complete', 10, 2 );

// Handle Lesson Completion
function adfoin_thriveapprentice_handle_lesson_complete( $lesson_details, $user_details ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'thriveapprentice', 'completeLesson' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'user_id' => $user_details['user_id'],
        'lesson_id' => $lesson_details['lesson_id'],
        'lesson_title' => $lesson_details['lesson_title'],
        'course_id' => $lesson_details['course_id'],
        'course_title' => $lesson_details['course_title'],
    );

    adfoin_thriveapprentice_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'thrive_apprentice_lesson_complete', 'adfoin_thriveapprentice_handle_lesson_complete', 10, 2 );

// Send Trigger Data
function adfoin_thriveapprentice_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}