<?php

// Get Ultimate Member Triggers
function adfoin_ultimatemember_get_forms( $form_provider ) {
    if( $form_provider != 'ultimatemember' ) {
        return;
    }

    $triggers = array(
        'userApproved' => __( 'User Account Approved', 'advanced-form-integration' ),
        'userInactive' => __( 'User Account Marked as Inactive', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Ultimate Member Fields
function adfoin_ultimatemember_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'ultimatemember' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'userApproved' ) {
        $fields = [
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'first_name' => __( 'First Name', 'advanced-form-integration' ),
            'last_name' => __( 'Last Name', 'advanced-form-integration' ),
            'display_name' => __( 'Display Name', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'userInactive' ) {
        $fields = [
            'user_id' => __( 'User ID', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'deactivation_reason' => __( 'Deactivation Reason', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_ultimatemember_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if( $user ) {
        $user_data['user_id'] = $user_id;
        $user_data['user_email'] = $user->user_email;
        $user_data['first_name'] = get_user_meta( $user_id, 'first_name', true );
        $user_data['last_name'] = get_user_meta( $user_id, 'last_name', true );
        $user_data['display_name'] = $user->display_name;
    }

    return $user_data;
}

// Send Data
function adfoin_ultimatemember_send_trigger_data( $saved_records, $posted_data ) {
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

add_action( 'um_after_user_is_approved', 'adfoin_ultimatemember_handle_user_approved', 10, 1 );

// Handle User Approved
function adfoin_ultimatemember_handle_user_approved( $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'ultimatemember', 'userApproved' );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_ultimatemember_get_userdata( $user_id );

    $posted_data['post_id'] = $user_id;

    adfoin_ultimatemember_send_trigger_data( $saved_records, $posted_data );
}

add_action( 'um_after_user_is_inactive', 'adfoin_ultimatemember_handle_user_inactive', 10, 1 );

// Handle User Inactive
function adfoin_ultimatemember_handle_user_inactive( $user_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'ultimatemember', 'userInactive' );

    if( empty( $saved_records ) ) {
        return;
    }

    $deactivation_reason = get_user_meta( $user_id, 'um_deactivation_reason', true );

    $posted_data = adfoin_ultimatemember_get_userdata( $user_id );
    $posted_data['deactivation_reason'] = $deactivation_reason;
    $posted_data['post_id'] = $user_id;

    adfoin_ultimatemember_send_trigger_data( $saved_records, $posted_data );
}