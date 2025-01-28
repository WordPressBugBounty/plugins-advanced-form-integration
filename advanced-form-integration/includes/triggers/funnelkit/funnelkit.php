<?php

// Get FunnelKit Triggers
function adfoin_funnelkit_get_forms($form_provider) {
    if ($form_provider != 'funnelkit') {
        return;
    }

    $triggers = array(
        'contactAddedToList' => __('Contact is added to a list', 'advanced-form-integration'),
        'tagAddedToContact'  => __('Tag is added to a contact', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get FunnelKit Fields
function adfoin_funnelkit_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'funnelkit') {
        return;
    }

    $fields = array();

    if ($form_id === 'contactAddedToList') {
        $fields = [
            'contact_email' => __('Contact Email', 'advanced-form-integration'),
            'list_id'       => __('List ID', 'advanced-form-integration'),
            'list_name'     => __('List Name', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'tagAddedToContact') {
        $fields = [
            'contact_email' => __('Contact Email', 'advanced-form-integration'),
            'tag_id'        => __('Tag ID', 'advanced-form-integration'),
            'tag_name'      => __('Tag Name', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into FunnelKit "contact added to list" action
add_action('bwfan_contact_added_to_lists', 'adfoin_funnelkit_handle_contact_added_to_list', 10, 2);
function adfoin_funnelkit_handle_contact_added_to_list($lists, $contact) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('funnelkit', 'contactAddedToList');

    if (empty($saved_records)) {
        return;
    }

    $email = $contact->contact->get_email();

    if (!is_array($lists)) {
        $posted_data = array(
            'contact_email' => $email,
            'list_id'       => $lists->get_id(),
            'list_name'     => $lists->get_name(),
        );
        adfoin_funnelkit_send_trigger_data($saved_records, $posted_data);
    } else {
        foreach ($lists as $list) {
            $posted_data = array(
                'contact_email' => $email,
                'list_id'       => $list->get_id(),
                'list_name'     => $list->get_name(),
            );
            adfoin_funnelkit_send_trigger_data($saved_records, $posted_data);
        }
    }
}

// Hook into FunnelKit "tag added to contact" action
add_action('bwfan_tags_added_to_contact', 'adfoin_funnelkit_handle_tag_added_to_contact', 10, 2);
function adfoin_funnelkit_handle_tag_added_to_contact($tags, $contact) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('funnelkit', 'tagAddedToContact');

    if (empty($saved_records)) {
        return;
    }

    $email = $contact->contact->get_email();

    if (!is_array($tags)) {
        $posted_data = array(
            'contact_email' => $email,
            'tag_id'        => $tags->get_id(),
            'tag_name'      => $tags->get_name(),
        );
        adfoin_funnelkit_send_trigger_data($saved_records, $posted_data);
    } else {
        foreach ($tags as $tag) {
            $posted_data = array(
                'contact_email' => $email,
                'tag_id'        => $tag->get_id(),
                'tag_name'      => $tag->get_name(),
            );
            adfoin_funnelkit_send_trigger_data($saved_records, $posted_data);
        }
    }
}

// Send data
function adfoin_funnelkit_send_trigger_data($saved_records, $posted_data) {
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