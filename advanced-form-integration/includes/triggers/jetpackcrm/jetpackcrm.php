<?php

// Get JetpackCRM Triggers
function adfoin_jetpackcrm_get_forms( $form_provider ) {
    if ( $form_provider != 'jetpackcrm' ) {
        return;
    }

    $triggers = array(
        'addContact' => __( 'Contact Added', 'advanced-form-integration' ),
        'updateContact' => __( 'Contact Updated', 'advanced-form-integration' ),
        'addCompany' => __( 'Company Added', 'advanced-form-integration' ),
        'addTagToContact' => __( 'Tag Added to Contact', 'advanced-form-integration' ),
        'addTagToCompany' => __( 'Tag Added to Company', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get JetpackCRM Fields
function adfoin_jetpackcrm_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'jetpackcrm' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'addContact' || $form_id === 'updateContact' ) {
        $fields = array(
            'contact_id' => __( 'Contact ID', 'advanced-form-integration' ),
            'first_name' => __( 'First Name', 'advanced-form-integration' ),
            'last_name' => __( 'Last Name', 'advanced-form-integration' ),
            'email' => __( 'Email', 'advanced-form-integration' ),
            'phone' => __( 'Phone Number', 'advanced-form-integration' ),
            'address' => __( 'Address', 'advanced-form-integration' ),
            'status' => __( 'Contact Status', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'addCompany' ) {
        $fields = array(
            'company_id' => __( 'Company ID', 'advanced-form-integration' ),
            'company_name' => __( 'Company Name', 'advanced-form-integration' ),
            'email' => __( 'Company Email', 'advanced-form-integration' ),
            'phone' => __( 'Company Phone', 'advanced-form-integration' ),
            'address' => __( 'Company Address', 'advanced-form-integration' ),
            'industry' => __( 'Industry', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'addTagToContact' || $form_id === 'addTagToCompany' ) {
        $fields = array(
            'tag_id' => __( 'Tag ID', 'advanced-form-integration' ),
            'tag_name' => __( 'Tag Name', 'advanced-form-integration' ),
            'entity_id' => __( 'Entity ID (Contact/Company)', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Contact Added
function adfoin_jetpackcrm_handle_contact_add( $contact_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'jetpackcrm', 'addContact' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $contact_data = array(
        'contact_id' => $contact_id,
        'first_name' => get_post_meta( $contact_id, 'first_name', true ),
        'last_name' => get_post_meta( $contact_id, 'last_name', true ),
        'email' => get_post_meta( $contact_id, 'email', true ),
        'phone' => get_post_meta( $contact_id, 'phone', true ),
        'address' => get_post_meta( $contact_id, 'address', true ),
        'status' => get_post_meta( $contact_id, 'status', true ),
    );

    adfoin_jetpackcrm_send_trigger_data( $saved_records, $contact_data );
}

add_action( 'zbs_new_customer', 'adfoin_jetpackcrm_handle_contact_add', 10, 1 );

// Handle Company Added
function adfoin_jetpackcrm_handle_company_add( $company_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'jetpackcrm', 'addCompany' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $company_data = array(
        'company_id' => $company_id,
        'company_name' => get_post_meta( $company_id, 'company_name', true ),
        'email' => get_post_meta( $company_id, 'email', true ),
        'phone' => get_post_meta( $company_id, 'phone', true ),
        'address' => get_post_meta( $company_id, 'address', true ),
        'industry' => get_post_meta( $company_id, 'industry', true ),
    );

    adfoin_jetpackcrm_send_trigger_data( $saved_records, $company_data );
}

add_action( 'zbs_new_company', 'adfoin_jetpackcrm_handle_company_add', 10, 1 );

// Send data
function adfoin_jetpackcrm_send_trigger_data( $saved_records, $posted_data ) {
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