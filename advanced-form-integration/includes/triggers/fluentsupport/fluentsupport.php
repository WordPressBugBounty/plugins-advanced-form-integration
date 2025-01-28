<?php

// Get FluentSupport Triggers
function adfoin_fluentsupport_get_forms($form_provider) {
    if ($form_provider != 'fluentsupport') {
        return;
    }

    $triggers = array(
        'agentOpenTicket'  => __('Agent opens a ticket', 'advanced-form-integration'),
        'clientOpenTicket' => __('Client opens a ticket', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get FluentSupport Fields
function adfoin_fluentsupport_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'fluentsupport') {
        return;
    }

    $fields = array();

    if ($form_id === 'agentOpenTicket') {
        $fields = [
            'ticket_id'   => __('Ticket ID', 'advanced-form-integration'),
            'ticket_title'=> __('Ticket Title', 'advanced-form-integration'),
            'agent_id'    => __('Agent ID', 'advanced-form-integration'),
            'agent_name'  => __('Agent Name', 'advanced-form-integration'),
            'created_at'  => __('Created At', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'clientOpenTicket') {
        $fields = [
            'ticket_id'    => __('Ticket ID', 'advanced-form-integration'),
            'ticket_title' => __('Ticket Title', 'advanced-form-integration'),
            'client_id'    => __('Client ID', 'advanced-form-integration'),
            'client_name'  => __('Client Name', 'advanced-form-integration'),
            'client_email' => __('Client Email', 'advanced-form-integration'),
            'created_at'   => __('Created At', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into FluentSupport "agent opens ticket" action
add_action('fluent_support/ticket_created', 'adfoin_fluentsupport_handle_agent_ticket', 10, 1);
function adfoin_fluentsupport_handle_agent_ticket($ticket) {
    global $wpdb;

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('fluentsupport', 'agentOpenTicket');

    if (empty($saved_records)) {
        return;
    }

    $ticket_id = absint($ticket['id']);
    $user = wp_get_current_user();

    $agent = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}fs_persons WHERE email='{$user->user_email}'");

    // Bail if agent not found or not an agent
    if (!$agent || $agent->person_type !== 'agent') {
        return;
    }

    $posted_data = array(
        'ticket_id'   => $ticket_id,
        'ticket_title'=> $ticket['title'] ?? __('No Title', 'advanced-form-integration'),
        'agent_id'    => $user->ID,
        'agent_name'  => $user->display_name,
        'created_at'  => current_time('mysql'),
    );

    adfoin_fluentsupport_send_trigger_data($saved_records, $posted_data);
}

// Hook into FluentSupport "client opens ticket" action
add_action('fluent_support/ticket_created', 'adfoin_fluentsupport_handle_client_ticket', 10, 1);
function adfoin_fluentsupport_handle_client_ticket($ticket) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('fluentsupport', 'clientOpenTicket');

    if (empty($saved_records)) {
        return;
    }

    // Skip if ticket source is null
    if (empty($ticket['source'])) {
        return;
    }

    $ticket_id = absint($ticket['id']);
    $client = get_user_by('email', $ticket['customer']['email']);

    // Bail if client not found
    if (!$client) {
        return;
    }

    $posted_data = array(
        'ticket_id'    => $ticket_id,
        'ticket_title' => $ticket['title'] ?? __('No Title', 'advanced-form-integration'),
        'client_id'    => $client->ID,
        'client_name'  => $client->display_name,
        'client_email' => $client->user_email,
        'created_at'   => current_time('mysql'),
    );

    adfoin_fluentsupport_send_trigger_data($saved_records, $posted_data);
}

// Send data
function adfoin_fluentsupport_send_trigger_data($saved_records, $posted_data) {
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