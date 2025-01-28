<?php

// Get LearnPress Triggers
function adfoin_learnpress_get_forms( $form_provider ) {
    if ( $form_provider != 'learnpress' ) {
        return;
    }

    $triggers = array(
        'completeCourse' => __( 'Course Completed', 'advanced-form-integration' ),
        'completeLesson' => __( 'Lesson Completed', 'advanced-form-integration' ),
        'attemptQuiz' => __( 'Quiz Attempted', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get LearnPress Fields
function adfoin_learnpress_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'learnpress' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'completeCourse' ) {
        $fields = array(
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'course_title' => __( 'Course Title', 'advanced-form-integration' ),
            'course_duration' => __( 'Course Duration', 'advanced-form-integration' ),
            'student_id' => __( 'Student ID', 'advanced-form-integration' ),
            'student_name' => __( 'Student Name', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'completeLesson' ) {
        $fields = array(
            'lesson_id' => __( 'Lesson ID', 'advanced-form-integration' ),
            'lesson_title' => __( 'Lesson Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'course_title' => __( 'Course Title', 'advanced-form-integration' ),
            'student_id' => __( 'Student ID', 'advanced-form-integration' ),
            'student_name' => __( 'Student Name', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'attemptQuiz' ) {
        $fields = array(
            'quiz_id' => __( 'Quiz ID', 'advanced-form-integration' ),
            'quiz_title' => __( 'Quiz Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'student_id' => __( 'Student ID', 'advanced-form-integration' ),
            'student_name' => __( 'Student Name', 'advanced-form-integration' ),
            'total_questions' => __( 'Total Questions', 'advanced-form-integration' ),
            'correct_answers' => __( 'Correct Answers', 'advanced-form-integration' ),
            'quiz_score' => __( 'Quiz Score', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Course Completed
function adfoin_learnpress_handle_course_complete( $course_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'learnpress', 'completeCourse' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $course = get_post( $course_id );
    $student_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'course_id' => $course_id,
        'course_title' => $course->post_title ?? '',
        'course_duration' => get_post_meta( $course_id, 'lp_duration', true ),
        'student_id' => $user_id,
        'student_name' => $student_name,
    );

    adfoin_learnpress_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'learn-press/user-course-finished', 'adfoin_learnpress_handle_course_complete', 10, 2 );

// Handle Lesson Completed
function adfoin_learnpress_handle_lesson_complete( $lesson_id, $course_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'learnpress', 'completeLesson' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $lesson = get_post( $lesson_id );
    $course = get_post( $course_id );
    $student_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'lesson_id' => $lesson_id,
        'lesson_title' => $lesson->post_title ?? '',
        'course_id' => $course_id,
        'course_title' => $course->post_title ?? '',
        'student_id' => $user_id,
        'student_name' => $student_name,
    );

    adfoin_learnpress_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'learn-press/user-completed-lesson', 'adfoin_learnpress_handle_lesson_complete', 10, 3 );

// Handle Quiz Attempted
function adfoin_learnpress_handle_quiz_attempt( $quiz_id, $course_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'learnpress', 'attemptQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $quiz = get_post( $quiz_id );
    $student_name = get_the_author_meta( 'display_name', $user_id );

    $posted_data = array(
        'quiz_id' => $quiz_id,
        'quiz_title' => $quiz->post_title ?? '',
        'course_id' => $course_id,
        'student_id' => $user_id,
        'student_name' => $student_name,
        'total_questions' => get_post_meta( $quiz_id, '_lp_total_questions', true ),
        'correct_answers' => get_post_meta( $quiz_id, '_lp_correct_answers', true ),
        'quiz_score' => get_post_meta( $quiz_id, '_lp_score', true ),
    );

    adfoin_learnpress_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'learn-press/user/quiz-finished', 'adfoin_learnpress_handle_quiz_attempt', 10, 3 );

// Send data
function adfoin_learnpress_send_trigger_data( $saved_records, $posted_data ) {
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