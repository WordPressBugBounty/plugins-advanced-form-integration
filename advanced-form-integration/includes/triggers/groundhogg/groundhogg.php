<?php

// Get Groundhogg Triggers
function adfoin_groundhogg_get_forms($form_provider) {
    if ($form_provider != 'groundhogg') {
        return;
    }

    $triggers = array(
        'tagAdded' => __('Tag added to user', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get Groundhogg Fields
function adfoin_groundhogg_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'groundhogg') {
        return;
    }

    $fields = array();

    if ($form_id === 'tagAdded') {
        $fields = [
            'user_id' => __('User ID', 'advanced-form-integration'),
            'user_email' => __('User Email', 'advanced-form-integration'),
            'tag_id' => __('Tag ID', 'advanced-form-integration'),
            'tag_name' => __('Tag Name', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into Groundhogg's tag added action
add_action('groundhogg/contact/tag_applied', 'adfoin_groundhogg_handle_tag_added', 10, 2);
function adfoin_groundhogg_handle_tag_added($contact, $tag_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('groundhogg', 'tagAdded');

    if (empty($saved_records)) {
        return;
    }

    // Ensure the contact has a WordPress user ID
    $user_id = $contact->get_user_id();

    if ($user_id === 0) {
        return;
    }

    // Get user information
    $user = get_userdata($user_id);

    if (!$user) {
        return;
    }

    // Get tag details
    $tag_name = Groundhogg\Plugin::$instance->utils->tags->get_tag_name($tag_id);

    $posted_data = array(
        'user_id' => $user_id,
        'user_email' => $user->user_email,
        'tag_id' => $tag_id,
        'tag_name' => $tag_name,
    );

    adfoin_groundhogg_send_trigger_data($saved_records, $posted_data);
}

// Send data
function adfoin_groundhogg_send_trigger_data($saved_records, $posted_data) {
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