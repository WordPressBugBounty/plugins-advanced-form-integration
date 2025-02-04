<?php

// Get QSM Triggers
function adfoin_qsm_get_forms( $form_provider ) {
    if ( $form_provider != 'qsm' ) {
        return;
    }

    $triggers = array(
        'submitQuiz' => __( 'Quiz Submitted', 'advanced-form-integration' ),
        'passQuiz' => __( 'Quiz Passed', 'advanced-form-integration' ),
        'failQuiz' => __( 'Quiz Failed', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get QSM Fields
function adfoin_qsm_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'qsm' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'submitQuiz' || $form_id === 'passQuiz' || $form_id === 'failQuiz' ) {
        $fields = array(
            'quiz_id' => __( 'Quiz ID', 'advanced-form-integration' ),
            'quiz_name' => __( 'Quiz Name', 'advanced-form-integration' ),
            'points' => __( 'Total Points', 'advanced-form-integration' ),
            'correct_answers' => __( 'Correct Answers', 'advanced-form-integration' ),
            'user_id' => __( 'User ID (if logged in)', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Quiz Submission
function adfoin_qsm_handle_quiz_submission( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'qsm', 'submitQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_id = get_current_user_id();
    $quiz_id = $qmn_quiz_options->quiz_id;
    $quiz_name = $qmn_quiz_options->quiz_name;
    $points = isset($qmn_array_for_variables['total_correct']) ? $qmn_array_for_variables['total_correct'] : 0;

    if ( empty( $quiz_id ) ) {
        return;
    }

    $posted_data = array(
        'quiz_id' => $quiz_id,
        'quiz_name' => $quiz_name,
        'points' => $points,
        'correct_answers' => isset($qmn_array_for_variables['correct']) ? $qmn_array_for_variables['correct'] : 0,
        'user_id' => $user_id,
        'user_email' => isset($qmn_array_for_variables['email']) ? $qmn_array_for_variables['email'] : __( 'Anonymous', 'advanced-form-integration' ),
    );

    $integration->send( $saved_records, $posted_data );
}

add_action( 'qsm_quiz_submitted', 'adfoin_qsm_handle_quiz_submission', 10, 4 );

// Handle Quiz Passed
function adfoin_qsm_handle_quiz_pass( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'qsm', 'passQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $pass_status = isset($qmn_array_for_variables['pass']) ? $qmn_array_for_variables['pass'] : false;
    if ( !$pass_status ) {
        return;
    }

    adfoin_qsm_handle_quiz_submission( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables );
}

// Handle Quiz Failed
function adfoin_qsm_handle_quiz_fail( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'qsm', 'failQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $pass_status = isset($qmn_array_for_variables['pass']) ? $qmn_array_for_variables['pass'] : true;
    if ( $pass_status ) {
        return;
    }

    adfoin_qsm_handle_quiz_submission( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables );
}

add_action( 'qsm_quiz_submitted', 'adfoin_qsm_handle_quiz_pass', 10, 4 );
add_action( 'qsm_quiz_submitted', 'adfoin_qsm_handle_quiz_fail', 10, 4 );