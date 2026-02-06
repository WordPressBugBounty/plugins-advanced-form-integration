<?php

// Get GravityKit Triggers
function adfoin_gravitykit_get_forms($form_provider) {
    if ($form_provider != 'gravitykit') {
        return;
    }

    $triggers = array(
        'anonymousEntryApproved' => __('Guest gets entry approved in a form', 'advanced-form-integration'),
        'userEntryApproved'      => __('User gets entry approved in a form', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get GravityKit Fields
function adfoin_gravitykit_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'gravitykit') {
        return;
    }

    $fields = array();

    if ($form_id === 'anonymousEntryApproved') {
        $fields = [
            'form_id'    => __('Form ID', 'advanced-form-integration'),
            'entry_id'   => __('Entry ID', 'advanced-form-integration'),
            'form_title' => __('Form Title', 'advanced-form-integration'),
            'approved_at'=> __('Approval Time', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'userEntryApproved') {
        $fields = [
            'form_id'    => __('Form ID', 'advanced-form-integration'),
            'entry_id'   => __('Entry ID', 'advanced-form-integration'),
            'user_id'    => __('User ID', 'advanced-form-integration'),
            'user_email' => __('User Email', 'advanced-form-integration'),
            'form_title' => __('Form Title', 'advanced-form-integration'),
            'approved_at'=> __('Approval Time', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into GravityKit anonymous entry approved action
add_action('gravityview/approve_entries/approved', 'adfoin_gravitykit_handle_anonymous_entry_approved', 10, 1);
function adfoin_gravitykit_handle_anonymous_entry_approved($entry_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('gravitykit', 'anonymousEntryApproved');

    if (empty($saved_records)) {
        return;
    }

    $form_id = adfoin_gravitykit_get_entry_form($entry_id);
    $form_title = adfoin_gravitykit_get_form_title($form_id);

    $posted_data = array(
        'form_id'    => $form_id,
        'entry_id'   => $entry_id,
        'form_title' => $form_title,
        'approved_at'=> current_time('mysql'),
    );

    adfoin_gravitykit_send_trigger_data($saved_records, $posted_data);
}

// Hook into GravityKit user entry approved action
add_action('gravityview/approve_entries/approved', 'adfoin_gravitykit_handle_user_entry_approved', 10, 1);
function adfoin_gravitykit_handle_user_entry_approved($entry_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('gravitykit', 'userEntryApproved');

    if (empty($saved_records)) {
        return;
    }

    $user_id = adfoin_gravitykit_get_entry_user($entry_id);

    // Bail if no user is associated with the entry
    if (empty($user_id)) {
        return;
    }

    $form_id = adfoin_gravitykit_get_entry_form($entry_id);
    $form_title = adfoin_gravitykit_get_form_title($form_id);
    $user = get_userdata($user_id);

    $posted_data = array(
        'form_id'    => $form_id,
        'entry_id'   => $entry_id,
        'user_id'    => $user_id,
        'user_email' => $user->user_email,
        'form_title' => $form_title,
        'approved_at'=> current_time('mysql'),
    );

    adfoin_gravitykit_send_trigger_data($saved_records, $posted_data);
}

// Utility functions to retrieve form and user information
function adfoin_gravitykit_get_entry_form($entry_id) {
    // Replace with logic to get the form ID associated with the entry
    return gform_get_meta($entry_id, 'form_id');
}

function adfoin_gravitykit_get_entry_user($entry_id) {
    // Replace with logic to get the user ID associated with the entry
    return gform_get_meta($entry_id, 'user_id');
}

function adfoin_gravitykit_get_form_title($form_id) {
    $form = GFAPI::get_form($form_id);
    return $form['title'] ?? __('Unknown Form', 'advanced-form-integration');
}

// Send data
function adfoin_gravitykit_send_trigger_data($saved_records, $posted_data) {
    $job_queue = get_option('adfoin_general_settings_job_queue');

    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];
        if ($job_queue) {
            as_enqueue_async_action("adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data,
                ),
            ));
        } else {
            call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
        }
    }
}