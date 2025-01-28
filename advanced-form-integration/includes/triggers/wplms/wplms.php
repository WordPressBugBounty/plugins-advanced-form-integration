<?php

// Get WPLMS Triggers
function adfoin_wplms_get_triggers( $form_provider ) {
    if ( $form_provider !== 'wplms' ) {
        return;
    }

    return [
        'completeCourse' => __( 'User completes a course', 'advanced-form-integration' ),
        'completeQuiz' => __( 'User completes a quiz', 'advanced-form-integration' ),
        'completeAssignment' => __( 'User completes an assignment', 'advanced-form-integration' ),
        'completeUnit' => __( 'User completes a unit', 'advanced-form-integration' ),
    ];
}

// Get WPLMS Fields
function adfoin_wplms_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wplms' ) {
        return;
    }

    $fields = [];

    if ( $form_id === 'completeCourse' ) {
        $fields = [
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'course_title' => __( 'Course Title', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'completion_date' => __( 'Completion Date', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'completeQuiz' ) {
        $fields = [
            'quiz_id' => __( 'Quiz ID', 'advanced-form-integration' ),
            'quiz_title' => __( 'Quiz Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'score' => __( 'Score', 'advanced-form-integration' ),
            'completion_date' => __( 'Completion Date', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'completeAssignment' ) {
        $fields = [
            'assignment_id' => __( 'Assignment ID', 'advanced-form-integration' ),
            'assignment_title' => __( 'Assignment Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'completion_date' => __( 'Completion Date', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'completeUnit' ) {
        $fields = [
            'unit_id' => __( 'Unit ID', 'advanced-form-integration' ),
            'unit_title' => __( 'Unit Title', 'advanced-form-integration' ),
            'course_id' => __( 'Course ID', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'completion_date' => __( 'Completion Date', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Send Trigger Data
function adfoin_wplms_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", [
                'data' => [
                    'record' => $record,
                    'posted_data' => $posted_data,
                ],
            ] );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

// Handle Course Completion
add_action( 'wplms_submit_course', 'adfoin_wplms_handle_complete_course', 10, 2 );
function adfoin_wplms_handle_complete_course( $course_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeCourse' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'course_id' => $course_id,
        'course_title' => get_the_title( $course_id ),
        'user_id' => $user_id,
        'completion_date' => current_time( 'mysql' ),
    ];

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}

// Handle Quiz Completion
add_action( 'wplms_submit_quiz', 'adfoin_wplms_handle_complete_quiz', 10, 2 );
function adfoin_wplms_handle_complete_quiz( $quiz_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'quiz_id' => $quiz_id,
        'quiz_title' => get_the_title( $quiz_id ),
        'course_id' => get_post_meta( $quiz_id, 'course_id', true ),
        'user_id' => $user_id,
        'score' => get_user_meta( $user_id, 'quiz_score_' . $quiz_id, true ),
        'completion_date' => current_time( 'mysql' ),
    ];

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}

// Handle Assignment Completion
add_action( 'wplms_submit_assignment', 'adfoin_wplms_handle_complete_assignment', 10, 2 );
function adfoin_wplms_handle_complete_assignment( $assignment_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeAssignment' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'assignment_id' => $assignment_id,
        'assignment_title' => get_the_title( $assignment_id ),
        'course_id' => get_post_meta( $assignment_id, 'course_id', true ),
        'user_id' => $user_id,
        'completion_date' => current_time( 'mysql' ),
    ];

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}

// Handle Unit Completion
add_action( 'wplms_unit_complete', 'adfoin_wplms_handle_complete_unit', 10, 4 );
function adfoin_wplms_handle_complete_unit( $unit_id, $course_progress, $course_id, $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeUnit' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'unit_id' => $unit_id,
        'unit_title' => get_the_title( $unit_id ),
        'course_id' => $course_id,
        'user_id' => $user_id,
        'completion_date' => current_time( 'mysql' ),
    ];

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}