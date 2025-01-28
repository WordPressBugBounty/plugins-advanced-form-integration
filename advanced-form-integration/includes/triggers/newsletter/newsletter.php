<?php

// Get Newsletter Triggers
function adfoin_newsletter_get_forms( $form_provider ) {
    if ( $form_provider != 'newsletter' ) {
        return;
    }

    $triggers = array(
        'subscribeToList' => __( 'Subscribe to List', 'advanced-form-integration' ),
        'unsubscribeFromList' => __( 'Unsubscribe from List', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Newsletter Fields
function adfoin_newsletter_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'newsletter' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'subscribeToList' ) {
        $fields = array(
            'email' => __( 'User Email', 'advanced-form-integration' ),
            'list_id' => __( 'List ID', 'advanced-form-integration' ),
            'list_name' => __( 'List Name', 'advanced-form-integration' ),
        );
    } elseif ( $form_id === 'unsubscribeFromList' ) {
        $fields = array(
            'email' => __( 'User Email', 'advanced-form-integration' ),
            'list_id' => __( 'List ID', 'advanced-form-integration' ),
            'list_name' => __( 'List Name', 'advanced-form-integration' ),
            'reason' => __( 'Reason for Unsubscribe', 'advanced-form-integration' ),
        );
    }

    return $fields;
}

// Handle Subscribe to List
function adfoin_newsletter_handle_subscribe( $user ) {
    global $wpdb;

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'newsletter', 'subscribeToList' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_id = $user->id ?? 0;
    $user_email = $user->email;

    $logs_table = $wpdb->prefix . 'newsletter_user_logs';
    $log = $wpdb->get_row( "SELECT MAX(id), data FROM {$logs_table} WHERE user_id = {$user_id} AND source = 'subscribe'" );

    if ( empty( $log->data ) ) {
        return;
    }

    $lists = json_decode( $log->data, true );

    foreach ( $lists as $list_id => $status ) {
        if ( $status !== '1' ) {
            continue;
        }

        $posted_data = array(
            'email' => $user_email,
            'list_id' => $list_id,
            'list_name' => adfoin_newsletter_get_list_name( $list_id ),
        );

        adfoin_newsletter_send_trigger_data( $saved_records, $posted_data );
    }
}

add_action( 'newsletter_user_post_subscribe', 'adfoin_newsletter_handle_subscribe', 10, 1 );

// Handle Unsubscribe from List
function adfoin_newsletter_handle_unsubscribe( $user ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'newsletter', 'unsubscribeFromList' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user_email = $user->email;
    $unsubscribed_lists = \Newsletter::instance()->get_unsubscribed_lists( $user_email );

    foreach ( $unsubscribed_lists as $list_id ) {
        $posted_data = array(
            'email' => $user_email,
            'list_id' => $list_id,
            'list_name' => adfoin_newsletter_get_list_name( $list_id ),
            'reason' => __( 'User unsubscribed from the list.', 'advanced-form-integration' ),
        );

        adfoin_newsletter_send_trigger_data( $saved_records, $posted_data );
    }
}

add_action( 'newsletter_user_post_unsubscribe', 'adfoin_newsletter_handle_unsubscribe', 10, 1 );

// Get List Name by ID
function adfoin_newsletter_get_list_name( $list_id ) {
    if ( class_exists( '\Newsletter' ) ) {
        $lists = \Newsletter::instance()->get_lists();
        return $lists[$list_id]->name ?? __( 'Unknown List', 'advanced-form-integration' );
    }
    return __( 'Unknown List', 'advanced-form-integration' );
}

// Send Trigger Data
function adfoin_newsletter_send_trigger_data( $saved_records, $posted_data ) {
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