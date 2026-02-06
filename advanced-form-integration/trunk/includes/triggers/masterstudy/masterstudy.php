<?php

// Get MasterStudy LMS forms list
function adfoin_masterstudy_get_forms( $form_provider ) {

    if( $form_provider != 'masterstudy' ) {
        return;
    }

    $forms = array(
        'courseCompleted' => __( 'Course Completed', 'advanced-form-integration' ),
        'lessonCompleted' => __( 'Lesson Completed', 'advanced-form-integration' ),
        'courseEnrolled' => __( 'Course Enrolled', 'advanced-form-integration' ),
        'quizPassed' => __( 'Quiz Passed', 'advanced-form-integration' ),
        'quizFailed' => __( 'Quiz Failed', 'advanced-form-integration' )
    );

    return $forms;
}

// Get MasterStudy LMS form fields
function adfoin_masterstudy_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'masterstudy' ) {
        return;
    }

    $fields = array(
        'user_email' => __( 'Email', 'advanced-form-integration'),
        'first_name' => __( 'First Name', 'advanced-form-integration'),
        'last_name' => __( 'Last Name', 'advanced-form-integration'),
        'nickname' => __( 'Nick Name', 'advanced-form-integration'),
        'avatar_url' => __( 'Avatar URL', 'advanced-form-integration')
    );

    if( in_array( $form_id, array( 'courseCompleted', 'courseEnrolled' ) ) ) {
        $fields['course_id'] = __( 'Course Id', 'advanced-form-integration' );
        $fields['course_title'] = __( 'Course Title', 'advanced-form-integration' );
        $fields['course_description'] = __( 'Course Description', 'advanced-form-integration' );
    } elseif( $form_id == 'lessonCompleted' ) {
        $fields['lesson_id'] = __( 'Lesson Id', 'advanced-form-integration' );
        $fields['lesson_title'] = __( 'Lesson Title', 'advanced-form-integration' );
        $fields['lesson_description'] = __( 'Lesson Description', 'advanced-form-integration' );
    } elseif( in_array( $form_id, array( 'quizPassed', 'quizFailed' ) ) ) {
        $fields['quiz_id'] = __( 'Quiz Id', 'advanced-form-integration' );
        $fields['quiz_title'] = __( 'Quiz Title', 'advanced-form-integration' );
        $fields['quiz_description'] = __( 'Quiz Description', 'advanced-form-integration' );
        $fields['course_id'] = __( 'Course Id', 'advanced-form-integration' );
        $fields['course_title'] = __( 'Course Title', 'advanced-form-integration' );
        $fields['course_description'] = __( 'Course Description', 'advanced-form-integration' );
    }

    return $fields;
}

// Get User Data
function adfoin_masterstudy_get_userdata( $user_id ) {
    $user = get_userdata( $user_id );
    $user_data = array(
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'nickname' => $user->nickname,
        'avatar_url' => get_avatar_url( $user_id ),
        'user_email' => $user->user_email
    );

    return $user_data;
}

// Handle MasterStudy LMS form submission
add_action( 'stm_lms_progress_updated', 'adfoin_masterstudy_handle_course_complete', 10, 3 );

function adfoin_masterstudy_handle_course_complete( $course_id, $user_id, $progress ) {
    $intergration = new Advanced_Form_Integration_Integration();
    $saved_records = $intergration->get_by_trigger( 'masterstudy', 'courseCompleted' );

    if( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_masterstudy_get_userdata( $user_id );

    $posted_data = array(
        'user_email' => $user_data['user_email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'nickname' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
        'course_id' => $course_id,
        'course_title' => get_the_title( $course_id ),
        'course_description' => get_the_content( $course_id )
    );

    $intergration->send( $saved_records, $posted_data );
}

add_action( 'course_enrolled', 'adfoin_masterstudy_handle_course_enroll', 10, 2 );

function adfoin_masterstudy_handle_course_enroll( $user_id, $course_id ) {
    $intergration = new Advanced_Form_Integration_Integration();
    $saved_records = $intergration->get_by_trigger( 'masterstudy', 'courseEnrolled' );

    if( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_masterstudy_get_userdata( $user_id );

    $posted_data = array(
        'user_email' => $user_data['user_email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'nickname' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
        'course_id' => $course_id,
        'course_title' => get_the_title( $course_id ),
        'course_description' => get_the_content( $course_id )
    );

    $intergration->send( $saved_records, $posted_data );
}

add_action( 'lesson_completed', 'adfoin_masterstudy_handle_lesson_complete', 10, 2 );

function adfoin_masterstudy_handle_lesson_complete( $user_id, $lesson_id ) {
    $intergration = new Advanced_Form_Integration_Integration();
    $saved_records = $intergration->get_by_trigger( 'masterstudy', 'lessonCompleted' );

    if( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_masterstudy_get_userdata( $user_id );

    $posted_data = array(
        'user_email' => $user_data['user_email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'nickname' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
        'lesson_id' => $lesson_id,
        'lesson_title' => get_the_title( $lesson_id ),
        'lesson_description' => get_the_content( $lesson_id )
    );

    $intergration->send( $saved_records, $posted_data );
}

add_action( 'stm_lms_quiz_passed', 'adfoin_masterstudy_handle_quiz_complete', 10, 3 );

function adfoin_masterstudy_handle_quiz_complete( $user_id, $quiz_id, $user_quiz_progress ) {
    $intergration = new Advanced_Form_Integration_Integration();
    $saved_records = $intergration->get_by_trigger( 'masterstudy', 'quizPassed' );

    if( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_masterstudy_get_userdata( $user_id );

    $posted_data = array(
        'user_email' => $user_data['user_email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'nickname' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
        'quiz_id' => $quiz_id,
        'quiz_title' => get_the_title( $quiz_id ),
        'quiz_description' => get_the_content( $quiz_id ),
        'course_id' => $user_quiz_progress->course_id,
        'course_title' => get_the_title( $user_quiz_progress->course_id ),
        'course_description' => get_the_content( $user_quiz_progress->course_id )
    );

    $intergration->send( $saved_records, $posted_data );
}

add_action( 'stm_lms_quiz_failed', 'adfoin_masterstudy_handle_quiz_failed', 10, 3 );

function adfoin_masterstudy_handle_quiz_failed( $user_id, $quiz_id, $user_quiz_progress ) {
    $intergration = new Advanced_Form_Integration_Integration();
    $saved_records = $intergration->get_by_trigger( 'masterstudy', 'quizFailed' );

    if( empty( $saved_records ) ) {
        return;
    }

    $user_data = adfoin_masterstudy_get_userdata( $user_id );

    $posted_data = array(
        'user_email' => $user_data['user_email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'nickname' => $user_data['nickname'],
        'avatar_url' => $user_data['avatar_url'],
        'quiz_id' => $quiz_id,
        'quiz_title' => get_the_title( $quiz_id ),
        'quiz_description' => get_the_content( $quiz_id ),
        'course_id' => $user_quiz_progress->course_id,
        'course_title' => get_the_title( $user_quiz_progress->course_id ),
        'course_description' => get_the_content( $user_quiz_progress->course_id )
    );

    $intergration->send( $saved_records, $posted_data );
}