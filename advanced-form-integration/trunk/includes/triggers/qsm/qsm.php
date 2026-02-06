<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the available Quiz And Survey Master triggers.
 *
 * @param string $form_provider The provider slug.
 *
 * @return array|void
 */
function adfoin_qsm_get_forms( $form_provider ) {
    if ( 'qsm' !== $form_provider ) {
        return;
    }

    return array(
        'submitQuiz' => __( 'Quiz Submitted', 'advanced-form-integration' ),
        'passQuiz'   => __( 'Quiz Passed', 'advanced-form-integration' ),
        'failQuiz'   => __( 'Quiz Failed', 'advanced-form-integration' ),
    );
}

/**
 * Provide the static field map for QSM triggers.
 *
 * QSM does not expose dynamic per-question fields via the public hook, so we
 * expose the core submission data that is always present.
 *
 * @param string $form_provider Provider slug.
 * @param string $form_id       Trigger key.
 *
 * @return array|void
 */
function adfoin_qsm_get_form_fields( $form_provider, $form_id ) {
    if ( 'qsm' !== $form_provider ) {
        return;
    }

    if ( ! in_array( $form_id, array( 'submitQuiz', 'passQuiz', 'failQuiz' ), true ) ) {
        return array();
    }

    return array(
        'quiz_id'          => __( 'Quiz ID', 'advanced-form-integration' ),
        'quiz_name'        => __( 'Quiz Name', 'advanced-form-integration' ),
        'points'           => __( 'Total Points', 'advanced-form-integration' ),
        'score'            => __( 'Score (%)', 'advanced-form-integration' ),
        'correct_answers'  => __( 'Correct Answers', 'advanced-form-integration' ),
        'total_questions'  => __( 'Total Questions', 'advanced-form-integration' ),
        'user_id'          => __( 'User ID (if logged in)', 'advanced-form-integration' ),
        'user_name'        => __( 'User Name', 'advanced-form-integration' ),
        'user_email'       => __( 'User Email', 'advanced-form-integration' ),
        'user_ip'          => __( 'User IP', 'advanced-form-integration' ),
        'result_id'        => __( 'Result ID', 'advanced-form-integration' ),
        'result_unique_id' => __( 'Result Unique ID', 'advanced-form-integration' ),
        'result_status'    => __( 'Result Status', 'advanced-form-integration' ),
    );
}

/**
 * Prepare the payload that is sent to action providers.
 *
 * @param array $results_array          Raw result array saved by QSM.
 * @param int   $results_id             Stored result ID.
 * @param obj   $qmn_quiz_options       Quiz options object.
 * @param array $qmn_array_for_variables Runtime quiz data.
 * @param string $status                Current status (submitted/passed/failed).
 *
 * @return array
 */
function adfoin_qsm_prepare_posted_data( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables, $status ) {
    $defaults = array(
        'total_points'      => 0,
        'total_score'       => 0,
        'total_correct'     => 0,
        'total_questions'   => 0,
        'user_id'           => get_current_user_id(),
        'user_name'         => '',
        'user_email'        => '',
        'user_ip'           => '',
        'result_unique_id'  => '',
        'contact'           => array(),
        'question_answers_array' => array(),
    );

    $data = wp_parse_args( $qmn_array_for_variables, $defaults );

    $user_email = $data['user_email'];
    if ( empty( $user_email ) ) {
        $user_email = adfoin_qsm_get_contact_value_by_use( $data['contact'], 'email', $user_email );
    }

    if ( empty( $user_email ) || 'None' === $user_email ) {
        $user_email = __( 'Anonymous', 'advanced-form-integration' );
    }

    $quiz_id = isset( $qmn_quiz_options->quiz_id ) ? intval( $qmn_quiz_options->quiz_id ) : 0;

    return apply_filters(
        'adfoin_qsm_posted_data',
        array(
            'quiz_id'          => $quiz_id,
            'quiz_name'        => isset( $qmn_quiz_options->quiz_name ) ? $qmn_quiz_options->quiz_name : '',
            'points'           => $data['total_points'],
            'score'            => $data['total_score'],
            'correct_answers'  => $data['total_correct'],
            'total_questions'  => $data['total_questions'],
            'user_id'          => $data['user_id'],
            'user_name'        => $data['user_name'],
            'user_email'       => $user_email,
            'user_ip'          => $data['user_ip'],
            'result_id'        => $results_id,
            'result_unique_id' => $data['result_unique_id'],
            'result_status'    => $status,
            'contact_fields'   => $data['contact'],
            'question_answers' => $data['question_answers_array'],
            'raw_results'      => $results_array,
        ),
        $results_array,
        $results_id,
        $qmn_quiz_options,
        $qmn_array_for_variables,
        $status
    );
}

/**
 * Helper to read a specific contact field by its "use" value.
 *
 * @param array  $contact_fields Contact field array from QSM.
 * @param string $use            Target use (email, name, etc).
 * @param mixed  $fallback       Value to fall back on.
 *
 * @return string
 */
function adfoin_qsm_get_contact_value_by_use( $contact_fields, $use, $fallback = '' ) {
    if ( empty( $contact_fields ) || ! is_array( $contact_fields ) ) {
        return $fallback;
    }

    foreach ( $contact_fields as $field ) {
        if ( isset( $field['use'] ) && $field['use'] === $use && ! empty( $field['value'] ) && 'None' !== $field['value'] ) {
            return $field['value'];
        }
    }

    return $fallback;
}

/**
 * Send data to saved integrations for the provided trigger.
 *
 * @param string $trigger                  Trigger key.
 * @param array  $results_array            Raw result array.
 * @param int    $results_id               Result ID.
 * @param object $qmn_quiz_options         Quiz options.
 * @param array  $qmn_array_for_variables  Submission data.
 * @param string $status                   Submission status label.
 */
function adfoin_qsm_maybe_send( $trigger, $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables, $status ) {
    $integration    = new Advanced_Form_Integration_Integration();
    $saved_records  = $integration->get_by_trigger( 'qsm', $trigger );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_qsm_prepare_posted_data( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables, $status );

    $integration->send( $saved_records, $posted_data );
}

/**
 * Determine whether the submission passed the quiz.
 *
 * By default QSM does not expose a unified pass/fail flag. We attempt to detect
 * this from the submission payload and allow site owners to override the logic
 * through the `adfoin_qsm_is_pass` filter.
 *
 * @param object $qmn_quiz_options        Quiz options object.
 * @param array  $qmn_array_for_variables Submission data.
 *
 * @return bool|null True for pass, false for fail, null when undetermined.
 */
function adfoin_qsm_is_pass( $qmn_quiz_options, $qmn_array_for_variables ) {
    if ( isset( $qmn_array_for_variables['pass'] ) ) {
        return (bool) $qmn_array_for_variables['pass'];
    }

    $status = null;
    if ( isset( $qmn_quiz_options->quiz_settings ) ) {
        $settings = maybe_unserialize( $qmn_quiz_options->quiz_settings );

        if ( is_array( $settings ) && isset( $settings['quiz_options'] ) ) {
            $quiz_options = maybe_unserialize( $settings['quiz_options'] );
            if ( is_array( $quiz_options ) ) {
                if ( isset( $quiz_options['passing_percentage'] ) && is_numeric( $quiz_options['passing_percentage'] ) ) {
                    $required = floatval( $quiz_options['passing_percentage'] );
                    if ( isset( $qmn_array_for_variables['total_score'] ) ) {
                        $status = floatval( $qmn_array_for_variables['total_score'] ) >= $required;
                    }
                } elseif ( isset( $quiz_options['passing_points'] ) && is_numeric( $quiz_options['passing_points'] ) ) {
                    $required_points = floatval( $quiz_options['passing_points'] );
                    if ( isset( $qmn_array_for_variables['total_points'] ) ) {
                        $status = floatval( $qmn_array_for_variables['total_points'] ) >= $required_points;
                    }
                }
            }
        }
    }

    return apply_filters( 'adfoin_qsm_is_pass', $status, $qmn_quiz_options, $qmn_array_for_variables );
}

/**
 * Fire the “Quiz Submitted” trigger.
 */
function adfoin_qsm_handle_quiz_submission( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
    adfoin_qsm_maybe_send( 'submitQuiz', $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables, 'submitted' );
}
add_action( 'qsm_quiz_submitted', 'adfoin_qsm_handle_quiz_submission', 10, 4 );

/**
 * Fire the “Quiz Passed” trigger when possible.
 */
function adfoin_qsm_handle_quiz_pass( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
    $pass_status = adfoin_qsm_is_pass( $qmn_quiz_options, $qmn_array_for_variables );

    if ( true !== $pass_status ) {
        return;
    }

    adfoin_qsm_maybe_send( 'passQuiz', $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables, 'passed' );
}
add_action( 'qsm_quiz_submitted', 'adfoin_qsm_handle_quiz_pass', 10, 4 );

/**
 * Fire the “Quiz Failed” trigger when possible.
 */
function adfoin_qsm_handle_quiz_fail( $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables ) {
    $pass_status = adfoin_qsm_is_pass( $qmn_quiz_options, $qmn_array_for_variables );

    if ( false !== $pass_status ) {
        return;
    }

    adfoin_qsm_maybe_send( 'failQuiz', $results_array, $results_id, $qmn_quiz_options, $qmn_array_for_variables, 'failed' );
}
add_action( 'qsm_quiz_submitted', 'adfoin_qsm_handle_quiz_fail', 10, 4 );
