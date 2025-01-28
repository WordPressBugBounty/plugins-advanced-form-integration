<?php

// Get Thrive Leads Triggers
function adfoin_thriveleads_get_forms( $form_provider ) {
    if( $form_provider != 'thriveleads' ) {
        return;
    }

    $triggers = array(
        'submitForm' => __( 'Form Submitted', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Thrive Leads Fields
function adfoin_thriveleads_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'thriveleads' ) {
        return;
    }

    $fields = array();

    if ($form_id === 'submitForm') {
        $fields = [
            'form_id' => __( 'Form ID', 'advanced-form-integration' ),
            'form_name' => __( 'Form Name', 'advanced-form-integration' ),
            'group_id' => __( 'Group ID', 'advanced-form-integration' ),
            'group_name' => __( 'Group Name', 'advanced-form-integration' ),
            'user_email' => __( 'User Email', 'advanced-form-integration' ),
            'user_ip' => __( 'User IP Address', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Get User Data
function adfoin_thriveleads_get_userdata( $user_id ) {
    $user_data = array();
    $user = get_userdata( $user_id );

    if( $user ) {
        $user_data['user_email'] = $user->user_email;
        $user_data['user_id'] = $user_id;
    }

    return $user_data;
}

// Send Data
function adfoin_thriveleads_send_trigger_data( $saved_records, $posted_data ) {
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

add_action( 'tcb_api_form_submit', 'adfoin_thriveleads_handle_form_submit', 10, 1 );

// Handle Form Submit
function adfoin_thriveleads_handle_form_submit( $post ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'thriveleads', 'submitForm' );

    if( empty( $saved_records ) ) {
        return;
    }

    $posted_data = array(
        'form_id' => $post['thrive_leads']['tl_data']['_key'] ?? null,
        'form_name' => $post['thrive_leads']['tl_data']['form_name'] ?? null,
        'group_id' => $post['thrive_leads']['tl_data']['main_group_id'] ?? null,
        'group_name' => $post['thrive_leads']['tl_data']['main_group_name'] ?? null,
    );

    if ( isset( $post['user_email'] ) ) {
        $posted_data['user_email'] = $post['user_email'];
    } else {
        $user_data = adfoin_thriveleads_get_userdata( get_current_user_id() );
        if ( $user_data ) {
            $posted_data = array_merge( $posted_data, $user_data );
        }
    }

    $posted_data['post_id'] = $posted_data['form_id'];

    adfoin_thriveleads_send_trigger_data( $saved_records, $posted_data );
}