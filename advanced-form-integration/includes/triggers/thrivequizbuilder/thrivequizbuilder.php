<?php

// Get Thrive Quiz Builder Triggers
function adfoin_thrivequizbuilder_get_forms( $form_provider ) {
    if( $form_provider != 'thrivequizbuilder' ) {
        return;
    }

    $triggers = array(
        'completeQuiz' => __( 'Quiz Completed', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Thrive Quiz Builder Fields
function adfoin_thrivequizbuilder_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'thrivequizbuilder' ) {
        return;
    }

    $fields = array();

    if ($form_id === 'completeQuiz') {
        $fields = [
            'quiz_id' => __( 'Quiz ID', 'advanced-form-integration' ),
            'quiz_name' => __( 'Quiz Name', 'advanced-form-integration' ),
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'score' => __( 'Score', 'advanced-form-integration' ),
            'max_score' => __( 'Maximum Score', 'advanced-form-integration' ),
            'completion_time' => __( 'Completion Time', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_thrivequizbuilder_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if( $user ) {
        $user_data['user_email'] = $user->user_email;
        $user_data['user_id'] = $user_id;
    }

    return $user_data;
}

// Send Data
function adfoin_thrivequizbuilder_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ($job_queue) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data
                )
            ) );
        } else {
            call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
        }
    }
}

add_action( 'tqb_quiz_completed', 'adfoin_thrivequizbuilder_handle_quiz_complete', 10, 2 );

// Handle Quiz Complete
function adfoin_thrivequizbuilder_handle_quiz_complete( $quiz, $user ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'thrivequizbuilder', 'completeQuiz' );

    if( empty( $saved_records ) ) {
        return;
    }

    $quiz_id = $quiz->ID ?? null;
    $quiz_name = $quiz->post_title ?? null;
    $user_id = get_current_user_id();

    // Bail if no user is logged in
    if ( $user_id === 0 ) {
        return;
    }

    // Additional data
    $score = get_post_meta( $quiz_id, 'quiz_score', true );
    $max_score = get_post_meta( $quiz_id, 'quiz_max_score', true );
    $completion_time = current_time( 'mysql' );

    $posted_data = array(
        'quiz_id' => $quiz_id,
        'quiz_name' => $quiz_name,
        'user_id' => $user_id,
        'score' => $score,
        'max_score' => $max_score,
        'completion_time' => $completion_time,
    );

    $user_data = adfoin_thrivequizbuilder_get_userdata( $user_id );

    if ( $user_data ) {
        $posted_data = array_merge( $posted_data, $user_data );
    }

    $posted_data['post_id'] = $quiz_id;

    adfoin_thrivequizbuilder_send_trigger_data( $saved_records, $posted_data );
}