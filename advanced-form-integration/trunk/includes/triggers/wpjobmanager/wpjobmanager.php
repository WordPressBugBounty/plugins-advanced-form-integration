<?php

// Get WP Job Manager Triggers
function adfoin_wpjobmanager_get_forms( $form_provider ) {
    if ( $form_provider !== 'wpjobmanager' ) {
        return;
    }

    $triggers = array(
        'jobPublished' => __( 'Job Published', 'advanced-form-integration' ),
        'jobApplicationSubmitted' => __( 'Job Application Submitted', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get WP Job Manager Fields
function adfoin_wpjobmanager_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wpjobmanager' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'jobPublished' ) {
        $fields = [
            'job_id' => __( 'Job ID', 'advanced-form-integration' ),
            'job_title' => __( 'Job Title', 'advanced-form-integration' ),
            'job_type' => __( 'Job Type', 'advanced-form-integration' ),
            'company_name' => __( 'Company Name', 'advanced-form-integration' ),
            'company_website' => __( 'Company Website', 'advanced-form-integration' ),
            'job_location' => __( 'Job Location', 'advanced-form-integration' ),
            'job_salary' => __( 'Job Salary', 'advanced-form-integration' ),
            'job_posted_date' => __( 'Job Posted Date', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'jobApplicationSubmitted' ) {
        $fields = [
            'application_id' => __( 'Application ID', 'advanced-form-integration' ),
            'job_id' => __( 'Job ID', 'advanced-form-integration' ),
            'applicant_name' => __( 'Applicant Name', 'advanced-form-integration' ),
            'applicant_email' => __( 'Applicant Email', 'advanced-form-integration' ),
            'application_date' => __( 'Application Date', 'advanced-form-integration' ),
            'resume_url' => __( 'Resume URL', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_wpjobmanager_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if ( $user ) {
        $user_data['user_id'] = $user_id;
        $user_data['user_email'] = $user->user_email;
        $user_data['display_name'] = $user->display_name;
    }

    return $user_data;
}

// Handle Job Published
add_action( 'transition_post_status', 'adfoin_wpjobmanager_handle_job_published', 10, 3 );
function adfoin_wpjobmanager_handle_job_published( $new_status, $old_status, $post ) {
    if ( $post->post_type !== 'job_listing' || $new_status !== 'publish' || $old_status === 'publish' ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpjobmanager', 'jobPublished' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $terms = wp_get_post_terms( $post->ID, 'job_listing_type', array( 'fields' => 'names' ) );

    $posted_data = [
        'job_id' => $post->ID,
        'job_title' => $post->post_title,
        'job_type' => implode( ', ', $terms ),
        'company_name' => get_post_meta( $post->ID, '_company_name', true ),
        'company_website' => get_post_meta( $post->ID, '_company_website', true ),
        'job_location' => get_post_meta( $post->ID, '_job_location', true ),
        'job_salary' => get_post_meta( $post->ID, '_job_salary', true ),
        'job_posted_date' => $post->post_date,
    ];

    $user_data = adfoin_wpjobmanager_get_userdata( $post->post_author );
    $posted_data = array_merge( $posted_data, $user_data );

    $posted_data['post_id'] = $post->ID;

    $integration->send( $saved_records, $posted_data );
}

// Handle Job Application Submitted
add_action( 'job_application_submitted', 'adfoin_wpjobmanager_handle_job_application_submitted', 10, 2 );
function adfoin_wpjobmanager_handle_job_application_submitted( $application_id, $job_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpjobmanager', 'jobApplicationSubmitted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $application = get_post( $application_id );

    if ( ! $application || $application->post_type !== 'job_application' ) {
        return;
    }

    $posted_data = [
        'application_id' => $application_id,
        'job_id' => $job_id,
        'applicant_name' => get_post_meta( $application_id, '_applicant_name', true ),
        'applicant_email' => get_post_meta( $application_id, '_applicant_email', true ),
        'application_date' => $application->post_date,
        'resume_url' => get_post_meta( $application_id, '_resume_url', true ),
    ];

    $posted_data['post_id'] = $application_id;

    $integration->send( $saved_records, $posted_data );
}