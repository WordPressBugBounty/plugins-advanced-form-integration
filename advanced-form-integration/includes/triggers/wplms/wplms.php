<?php

/*
 * Get WPLMS triggers
 */
function adfoin_wplms_get_forms( $form_provider ) {
    if ( $form_provider !== 'wplms' ) {
        return;
    }

    $triggers = array(
        'completeCourse'     => __( 'User completes a course', 'advanced-form-integration' ),
        'completeQuiz'       => __( 'User completes a quiz', 'advanced-form-integration' ),
        'completeAssignment' => __( 'User completes an assignment', 'advanced-form-integration' ),
        'completeUnit'       => __( 'User completes a unit', 'advanced-form-integration' ),
    );

    return $triggers;
}

/*
 * Get WPLMS fields
 */
function adfoin_wplms_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wplms' ) {
        return;
    }

    $fields = array();

    if ( ! in_array( $form_id, array( 'completeCourse', 'completeQuiz', 'completeAssignment', 'completeUnit' ), true ) ) {
        return $fields;
    }

    if ( in_array( $form_id, array( 'completeQuiz' ), true ) ) {
        $fields['quiz_id']    = __( 'Quiz ID', 'advanced-form-integration' );
        $fields['quiz_title'] = __( 'Quiz Title', 'advanced-form-integration' );
        $fields['quiz_url']   = __( 'Quiz URL', 'advanced-form-integration' );
        $fields['score']         = __( 'Score', 'advanced-form-integration' );
        $fields['passing_score'] = __( 'Passing Score', 'advanced-form-integration' );
    }

    if ( in_array( $form_id, array( 'completeAssignment' ), true ) ) {
        $fields['assignment_id']    = __( 'Assignment ID', 'advanced-form-integration' );
        $fields['assignment_title'] = __( 'Assignment Title', 'advanced-form-integration' );
        $fields['assignment_url']   = __( 'Assignment URL', 'advanced-form-integration' );
        $fields['marks']            = __( 'Marks', 'advanced-form-integration' );
        $fields['total_marks']      = __( 'Total Marks', 'advanced-form-integration' );
    }

    if ( in_array( $form_id, array( 'completeUnit' ), true ) ) {
        $fields['unit_id']    = __( 'Unit ID', 'advanced-form-integration' );
        $fields['unit_title'] = __( 'Unit Title', 'advanced-form-integration' );
        $fields['unit_url']   = __( 'Unit URL', 'advanced-form-integration' );
    }

    $fields['course_id']    = __( 'Course ID', 'advanced-form-integration' );
    $fields['course_title'] = __( 'Course Title', 'advanced-form-integration' );
    $fields['course_url']   = __( 'Course URL', 'advanced-form-integration' );

    $fields['completion_date'] = __( 'Completion Date', 'advanced-form-integration' );

    $fields['user_id']    = __( 'User ID', 'advanced-form-integration' );
    $fields['first_name'] = __( 'First Name', 'advanced-form-integration' );
    $fields['last_name']  = __( 'Last Name', 'advanced-form-integration' );
    $fields['user_email'] = __( 'Email', 'advanced-form-integration' );

    return $fields;
}

/*
 * Get id, title and URL of a WPLMS post object
 */
function adfoin_wplms_get_post_data( $post_id, $post_type ) {
    $data = array();

    if ( empty( $post_id ) ) {
        return $data;
    }

    $post_data = get_post( $post_id );

    if ( $post_data ) {
        $data["{$post_type}_id"]    = $post_data->ID;
        $data["{$post_type}_title"] = $post_data->post_title;
        $data["{$post_type}_url"]   = get_permalink( $post_id );
    }

    return $data;
}

/*
 * Get data of the user who triggered the event
 */
function adfoin_wplms_get_userdata( $user_id ) {
    $user_data = array();
    $user      = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_id']    = $user->ID;
        $user_data['first_name'] = $user->first_name;
        $user_data['last_name']  = $user->last_name;
        $user_data['user_email'] = $user->user_email;
    }

    return $user_data;
}

/*
 * Send trigger data
 */
function adfoin_wplms_send_trigger_data( $saved_records, $posted_data ) {
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

/*
 * Handle course completion
 */
add_action( 'wplms_submit_course', 'adfoin_wplms_handle_complete_course', 10, 2 );
function adfoin_wplms_handle_complete_course( $course_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeCourse' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_wplms_get_post_data( $course_id, 'course' );

    $posted_data['completion_date'] = current_time( 'mysql' );

    $posted_data = array_merge( $posted_data, adfoin_wplms_get_userdata( $user_id ) );

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}

/*
 * Handle quiz completion
 */
add_action( 'wplms_submit_quiz', 'adfoin_wplms_handle_complete_quiz', 10, 2 );
function adfoin_wplms_handle_complete_quiz( $quiz_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeQuiz' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_wplms_get_post_data( $quiz_id, 'quiz' );

    // WPLMS stores a user's quiz marks as post meta on the quiz, keyed by user ID.
    $posted_data['score']         = get_post_meta( $quiz_id, $user_id, true );
    $posted_data['passing_score'] = get_post_meta( $quiz_id, 'vibe_quiz_passing_score', true );

    $course_id   = get_post_meta( $quiz_id, 'vibe_quiz_course', true );
    $posted_data = array_merge( $posted_data, adfoin_wplms_get_post_data( $course_id, 'course' ) );

    $posted_data['completion_date'] = current_time( 'mysql' );

    $posted_data = array_merge( $posted_data, adfoin_wplms_get_userdata( $user_id ) );

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}

/*
 * Handle assignment completion
 */
add_action( 'wplms_submit_assignment', 'adfoin_wplms_handle_complete_assignment', 10, 2 );
function adfoin_wplms_handle_complete_assignment( $assignment_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeAssignment' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_wplms_get_post_data( $assignment_id, 'assignment' );

    // WPLMS stores a user's assignment marks as post meta on the assignment, keyed by user ID.
    $posted_data['marks']       = get_post_meta( $assignment_id, $user_id, true );
    $posted_data['total_marks'] = get_post_meta( $assignment_id, 'vibe_assignment_marks', true );

    $course_id   = get_post_meta( $assignment_id, 'vibe_assignment_course', true );
    $posted_data = array_merge( $posted_data, adfoin_wplms_get_post_data( $course_id, 'course' ) );

    $posted_data['completion_date'] = current_time( 'mysql' );

    $posted_data = array_merge( $posted_data, adfoin_wplms_get_userdata( $user_id ) );

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}

/*
 * Handle unit completion
 */
add_action( 'wplms_unit_complete', 'adfoin_wplms_handle_complete_unit', 10, 4 );
function adfoin_wplms_handle_complete_unit( $unit_id, $course_progress, $course_id, $user_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wplms', 'completeUnit' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_wplms_get_post_data( $unit_id, 'unit' );

    $posted_data = array_merge( $posted_data, adfoin_wplms_get_post_data( $course_id, 'course' ) );

    $posted_data['completion_date'] = current_time( 'mysql' );

    $posted_data = array_merge( $posted_data, adfoin_wplms_get_userdata( $user_id ) );

    adfoin_wplms_send_trigger_data( $saved_records, $posted_data );
}
