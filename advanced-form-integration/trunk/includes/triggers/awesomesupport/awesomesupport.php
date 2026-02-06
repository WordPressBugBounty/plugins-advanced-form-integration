<?php

// Get Awesome Support Triggers
function adfoin_awesomesupport_get_forms($form_provider) {
    if ($form_provider != 'awesomesupport') {
        return;
    }

    $triggers = array(
        'agentOpenTicket'  => __('Agent opens a ticket', 'advanced-form-integration'),
        'clientOpenTicket' => __('Client opens a ticket', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get Awesome Support Fields
function adfoin_awesomesupport_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'awesomesupport') {
        return;
    }

    $fields = array();

    if ($form_id === 'agentOpenTicket') {
        $fields = [
            'ticket_id'      => __('Ticket ID', 'advanced-form-integration'),
            'ticket_subject' => __('Ticket Subject', 'advanced-form-integration'),
            'ticket_content' => __('Ticket Content', 'advanced-form-integration'),
            'agent_id'       => __('Agent ID', 'advanced-form-integration'),
            'agent_name'     => __('Agent Name', 'advanced-form-integration'),
            'created_at'     => __('Created At', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'clientOpenTicket') {
        $fields = [
            'ticket_id'      => __('Ticket ID', 'advanced-form-integration'),
            'ticket_subject' => __('Ticket Subject', 'advanced-form-integration'),
            'ticket_content' => __('Ticket Content', 'advanced-form-integration'),
            'client_id'      => __('Client ID', 'advanced-form-integration'),
            'client_name'    => __('Client Name', 'advanced-form-integration'),
            'created_at'     => __('Created At', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into Awesome Support "agent opens ticket" action
add_action('wpas_open_ticket_after', 'adfoin_awesomesupport_handle_agent_open_ticket', 10, 1);
function adfoin_awesomesupport_handle_agent_open_ticket($ticket_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('awesomesupport', 'agentOpenTicket');

    if (empty($saved_records)) {
        return;
    }

    $agent_id = intval(get_post_meta($ticket_id, '_wpas_assignee', true));
    $agent_name = get_the_author_meta('display_name', $agent_id);
    $ticket = get_post($ticket_id);

    $posted_data = array(
        'ticket_id'      => $ticket_id,
        'ticket_subject' => $ticket->post_title,
        'ticket_content' => $ticket->post_content,
        'agent_id'       => $agent_id,
        'agent_name'     => $agent_name,
        'created_at'     => $ticket->post_date,
    );

    $integration->send($saved_records, $posted_data);
}

// Hook into Awesome Support "client opens ticket" action
add_action('wpas_post_new_ticket_admin', 'adfoin_awesomesupport_handle_client_open_ticket', 10, 1);
function adfoin_awesomesupport_handle_client_open_ticket($ticket_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('awesomesupport', 'clientOpenTicket');

    if (empty($saved_records)) {
        return;
    }

    $client_id = absint(get_post_field('post_author', $ticket_id));
    $client_name = get_the_author_meta('display_name', $client_id);
    $ticket = get_post($ticket_id);

    $posted_data = array(
        'ticket_id'      => $ticket_id,
        'ticket_subject' => $ticket->post_title,
        'ticket_content' => $ticket->post_content,
        'client_id'      => $client_id,
        'client_name'    => $client_name,
        'created_at'     => $ticket->post_date,
    );

    $integration->send($saved_records, $posted_data);
}