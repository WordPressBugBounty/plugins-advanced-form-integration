<?php

// Get Sensei LMS Triggers
function adfoin_senseilms_get_forms( $form_provider ) {
    if ( $form_provider != 'senseilms' ) {
        return;
    }

    $triggers = array(
        'completeCourse' => __( 'Course Completed', 'advanced-form-integration' ),
        'completeLesson' => __( 'Lesson Completed', 'advanced-form-integration' ),
        'attemptQuiz' => __( 'Quiz Attempted', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Sensei LMS Fields
function adfoin_senseilms_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'senseilms' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'completeCourse' ) {
        $fields = array(
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'course_title' => __( 'Course Title', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'completeLesson' ) {
        $fields = array(
            'lesson_id' => __( 'Lesson ID', 'advanced-form-integration' ),
            'lesson_title' => __( 'Lesson Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'attemptQuiz' ) {
        $fields = array(
            'quiz_id' => __( 'Quiz ID', 'advanced-form-integration' ),
            'quiz_title' => __( 'Quiz Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_name' => __( 'User Name', 'advanced-form-integration' ),
            'quiz_score' => __( 'Quiz Score', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Course Completion
function adfoin_senseilms_handle_course_complete( $user_id, $course_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'senseilms', 'completeCourse' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $course = get_post( $course_id );
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'course_id' => $course_id,
        'course_title' => $course->post_title ?? '',
        'user_id' => $user_id,
        'user_name' => $user_name,
    );

    adfoin_senseilms_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'sensei_user_course_end', 'adfoin_senseilms_handle_course_complete', 10, 2 );

// Handle Lesson Completion
function adfoin_senseilms_handle_lesson_complete( $user_id, $lesson_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'senseilms', 'completeLesson' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $lesson = get_post( $lesson_id );
    $course_id = get_post_meta( $lesson_id, '_lesson_course', true );
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'lesson_id' => $lesson_id,
        'lesson_title' => $lesson->post_title ?? '',
        'course_id' => $course_id,
        'user_id' => $user_id,
        'user_name' => $user_name,
    );

    adfoin_senseilms_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'sensei_user_lesson_end', 'adfoin_senseilms_handle_lesson_complete', 10, 2 );

// Handle Quiz Attempt
function adfoin_senseilms_handle_quiz_attempt( $quiz_id, $user_id, $score ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'senseilms', 'attemptQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $quiz = get_post( $quiz_id );
    $course_id = get_post_meta( $quiz_id, '_quiz_course', true );
    $user_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'quiz_id' => $quiz_id,
        'quiz_title' => $quiz->post_title ?? '',
        'course_id' => $course_id,
        'user_id' => $user_id,
        'user_name' => $user_name,
        'quiz_score' => $score,
    );

    adfoin_senseilms_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'sensei_user_quiz_grade', 'adfoin_senseilms_handle_quiz_attempt', 10, 3 );

// Send Trigger Data
function adfoin_senseilms_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data
                )
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}