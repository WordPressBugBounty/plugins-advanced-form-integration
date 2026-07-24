<?php

/**
 * WP Job Manager trigger.
 *
 * "Job Published" uses only base WP Job Manager (confirmed against
 * Automattic/WP-Job-Manager core: post type `job_listing`, meta keys
 * `_company_name`/`_company_website`/`_job_location`/`_job_salary`,
 * taxonomy `job_listing_type` — all genuinely built into the free plugin).
 *
 * "Job Application Submitted" requires the separate PAID "WP Job Manager –
 * Applications" add-on — base WP Job Manager has no applications feature
 * at all. The real hook is `new_job_application` (NOT
 * `job_application_submitted`, which doesn't exist). The add-on is
 * closed-source, so its exact meta keys aren't independently verifiable
 * the way core's are; the applicant's name lives on `post_title` (not a
 * meta field), email is `_candidate_email`, and resume is stored as an
 * attachment reference rather than a plain URL string.
 *
 * @link https://wpjobmanager.com/document/extensions/applications/applications-snippets/
 */
// Get WP Job Manager Triggers
function adfoin_wpjobmanager_get_forms(  $form_provider  ) {
    if ( $form_provider !== 'wpjobmanager' ) {
        return;
    }
    $triggers = array(
        'jobPublished'            => __( 'Job Published', 'advanced-form-integration' ),
        'jobApplicationSubmitted' => __( 'Job Application Submitted (requires the paid Applications add-on)', 'advanced-form-integration' ),
    );
    return $triggers;
}

// Get WP Job Manager Fields
function adfoin_wpjobmanager_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider !== 'wpjobmanager' ) {
        return;
    }
    $fields = array();
    if ( $form_id === 'jobPublished' ) {
        $fields = [
            'job_id'          => __( 'Job ID', 'advanced-form-integration' ),
            'job_title'       => __( 'Job Title', 'advanced-form-integration' ),
            'job_type'        => __( 'Job Type', 'advanced-form-integration' ),
            'company_name'    => __( 'Company Name', 'advanced-form-integration' ),
            'company_website' => __( 'Company Website', 'advanced-form-integration' ),
            'job_location'    => __( 'Job Location', 'advanced-form-integration' ),
            'job_salary'      => __( 'Job Salary', 'advanced-form-integration' ),
            'job_posted_date' => __( 'Job Posted Date', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'jobApplicationSubmitted' ) {
        // Free plan: applicant name/email plus the id/date identifiers — the
        // resume (an "other field", not name/email) requires Pro.
        $fields = [
            'application_id'   => __( 'Application ID', 'advanced-form-integration' ),
            'job_id'           => __( 'Job ID', 'advanced-form-integration' ),
            'applicant_name'   => __( 'Applicant Name', 'advanced-form-integration' ),
            'applicant_email'  => __( 'Applicant Email', 'advanced-form-integration' ),
            'application_date' => __( 'Application Date', 'advanced-form-integration' ),
        ];
    }
    return $fields;
}

// Get User Data
function adfoin_wpjobmanager_get_userdata(  $user_id  ) {
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
add_action(
    'transition_post_status',
    'adfoin_wpjobmanager_handle_job_published',
    10,
    3
);
function adfoin_wpjobmanager_handle_job_published(  $new_status, $old_status, $post  ) {
    if ( $post->post_type !== 'job_listing' || $new_status !== 'publish' || $old_status === 'publish' ) {
        return;
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpjobmanager', 'jobPublished' );
    if ( empty( $saved_records ) ) {
        return;
    }
    $terms = wp_get_post_terms( $post->ID, 'job_listing_type', array(
        'fields' => 'names',
    ) );
    $posted_data = [
        'job_id'          => $post->ID,
        'job_title'       => $post->post_title,
        'job_type'        => implode( ', ', $terms ),
        'company_name'    => get_post_meta( $post->ID, '_company_name', true ),
        'company_website' => get_post_meta( $post->ID, '_company_website', true ),
        'job_location'    => get_post_meta( $post->ID, '_job_location', true ),
        'job_salary'      => get_post_meta( $post->ID, '_job_salary', true ),
        'job_posted_date' => $post->post_date,
    ];
    $user_data = adfoin_wpjobmanager_get_userdata( $post->post_author );
    $posted_data = array_merge( $posted_data, $user_data );
    $posted_data['post_id'] = $post->ID;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

// Handle Job Application Submitted — requires the paid "WP Job Manager –
// Applications" add-on; base WP Job Manager never fires this hook.
// Confirmed real hook name via wpjobmanager.com's own snippet docs:
// `new_job_application`, not `job_application_submitted`.
add_action(
    'new_job_application',
    'adfoin_wpjobmanager_handle_job_application_submitted',
    999,
    2
);
function adfoin_wpjobmanager_handle_job_application_submitted(  $application_id, $job_id  ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpjobmanager', 'jobApplicationSubmitted' );
    if ( empty( $saved_records ) ) {
        return;
    }
    $application = get_post( $application_id );
    if ( !$application ) {
        return;
    }
    // The Applications add-on is closed-source, so these can't be verified
    // against public source the way core WPJM's fields were. Best publicly
    // available evidence: applicant name lives on post_title (not a meta
    // field), email is `_candidate_email`, and the resume is an attachment
    // reference rather than a plain URL — resolved to a URL here if present.
    $posted_data = [
        'application_id'   => $application_id,
        'job_id'           => $job_id,
        'applicant_name'   => $application->post_title,
        'applicant_email'  => get_post_meta( $application_id, '_candidate_email', true ),
        'application_date' => $application->post_date,
    ];
    $posted_data['post_id'] = $application_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_wpjobmanager_trigger_fields' );
}
/**
 * Free-tier upgrade notice — shown only for the Job Application Submitted
 * trigger (the resume fields are Pro-only). Job Published has no gated
 * fields, so no notice is shown for it.
 */
function adfoin_wpjobmanager_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'wpjobmanager' && trigger.formId == 'jobApplicationSubmitted'">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports name and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
