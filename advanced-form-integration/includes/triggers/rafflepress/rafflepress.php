<?php

// Get RafflePress Triggers
function adfoin_rafflepress_get_forms($form_provider) {
    if ($form_provider != 'rafflepress') {
        return;
    }

    $triggers = [
        'newGiveawayEntry' => __('New Giveaway Entry', 'advanced-form-integration'),
    ];

    return $triggers;
}

// Get RafflePress Fields
function adfoin_rafflepress_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'rafflepress') {
        return;
    }

    if ($form_id === 'newGiveawayEntry') {
        $fields = [
            'giveaway_id'       => __('Giveaway ID', 'advanced-form-integration'),
            'giveaway_name'     => __('Giveaway Name', 'advanced-form-integration'),
            'starts'            => __('Starts', 'advanced-form-integration'),
            'ends'              => __('Ends', 'advanced-form-integration'),
            'active'            => __('Active', 'advanced-form-integration'),
            'name'              => __('Name', 'advanced-form-integration'),
            'first_name'        => __('First Name', 'advanced-form-integration'),
            'last_name'         => __('Last Name', 'advanced-form-integration'),
            'email'             => __('Email', 'advanced-form-integration'),
            'prize_name'        => __('Prize Name', 'advanced-form-integration'),
            'prize_description' => __('Prize Description', 'advanced-form-integration'),
            'prize_image'       => __('Prize Image', 'advanced-form-integration'),
        ];

        return $fields;
    }

    return [];
}

// Handle New Giveaway Entry
function adfoin_rafflepress_handle_new_entry($giveaway_details) {
    if (!is_plugin_active('rafflepress/rafflepress.php') && !is_plugin_active('rafflepress-pro/rafflepress-pro.php')) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('rafflepress', 'newGiveawayEntry');

    if (empty($saved_records)) {
        return;
    }

    $posted_data = [
        'giveaway_id'       => $giveaway_details['giveaway_id'],
        'giveaway_name'     => $giveaway_details['giveaway']->name,
        'starts'            => $giveaway_details['giveaway']->starts,
        'ends'              => $giveaway_details['giveaway']->ends,
        'active'            => $giveaway_details['giveaway']->active,
        'name'              => $giveaway_details['name'],
        'first_name'        => $giveaway_details['first_name'],
        'last_name'         => $giveaway_details['last_name'],
        'email'             => $giveaway_details['email'],
        'prize_name'        => $giveaway_details['settings']->prizes[0]->name ?? '',
        'prize_description' => $giveaway_details['settings']->prizes[0]->description ?? '',
        'prize_image'       => $giveaway_details['settings']->prizes[0]->image ?? '',
    ];

    adfoin_rafflepress_send_trigger_data($saved_records, $posted_data);
}

// Send Trigger Data
function adfoin_rafflepress_send_trigger_data($saved_records, $posted_data) {
    $job_queue = get_option('adfoin_general_settings_job_queue');

    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];

        if ($job_queue) {
            as_enqueue_async_action("adfoin_{$action_provider}_job_queue", [
                'data' => [
                    'record'      => $record,
                    'posted_data' => $posted_data,
                ],
            ]);
        } else {
            call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
        }
    }
}

// Add Action Hook for New Entry
add_action('rafflepress_giveaway_webhooks', 'adfoin_rafflepress_handle_new_entry', 10, 1);