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
add_action('fluent_support/ticket_created_behalf_of_customer', 'adfoin_fluentsupport_handle_agent_ticket', 10, 3);
function adfoin_fluentsupport_handle_agent_ticket($ticket, $customer, $agent) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('fluentsupport', 'agentOpenTicket');

    if (empty($saved_records)) {
        return;
    }

    if (empty($agent)) {
        return;
    }

    $ticket_id = adfoin_fluentsupport_get_ticket_id($ticket);
    if (!$ticket_id) {
        return;
    }

    $agent_user_id = isset($agent->user_id) ? absint($agent->user_id) : 0;
    $agent_name = '';

    if ($agent_user_id) {
        $user = get_user_by('id', $agent_user_id);
        if ($user) {
            $agent_name = $user->display_name;
        }
    }

    if (!$agent_name && isset($agent->full_name)) {
        $agent_name = $agent->full_name;
    }

    $posted_data = array(
        'ticket_id'    => $ticket_id,
        'ticket_title' => adfoin_fluentsupport_get_ticket_title($ticket),
        'agent_id'     => $agent_user_id ? $agent_user_id : absint(isset($agent->id) ? $agent->id : 0),
        'agent_name'   => $agent_name ? $agent_name : __('Unknown Agent', 'advanced-form-integration'),
        'created_at'   => adfoin_fluentsupport_get_ticket_created_at($ticket),
    );

    adfoin_fluentsupport_send_trigger_data($saved_records, $posted_data);
}

// Hook into FluentSupport "client opens ticket" action
add_action('fluent_support/ticket_created', 'adfoin_fluentsupport_handle_client_ticket', 10, 2);
function adfoin_fluentsupport_handle_client_ticket($ticket, $customer) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('fluentsupport', 'clientOpenTicket');

    if (empty($saved_records)) {
        return;
    }

    // Skip if ticket source is null
    $ticket_source = adfoin_fluentsupport_get_ticket_source($ticket);
    if ($ticket_source === '') {
        return;
    }

    if (class_exists('\FluentSupport\App\Services\Helper') && \FluentSupport\App\Services\Helper::getAgentByUserId()) {
        return;
    }

    $ticket_id = adfoin_fluentsupport_get_ticket_id($ticket);
    if (!$ticket_id) {
        return;
    }

    $client_email = adfoin_fluentsupport_get_customer_email($customer);
    if (!$client_email || !is_email($client_email)) {
        return;
    }

    $client_name = adfoin_fluentsupport_get_customer_name($customer);
    $client_id = adfoin_fluentsupport_get_customer_user_id($customer);
    if (!$client_id) {
        $client_id = adfoin_fluentsupport_get_customer_id($customer);
    }

    $posted_data = array(
        'ticket_id'    => $ticket_id,
        'ticket_title' => adfoin_fluentsupport_get_ticket_title($ticket),
        'client_id'    => absint($client_id),
        'client_name'  => $client_name,
        'client_email' => $client_email,
        'created_at'   => adfoin_fluentsupport_get_ticket_created_at($ticket),
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

function adfoin_fluentsupport_get_ticket_id($ticket) {
    if (is_object($ticket) && isset($ticket->id)) {
        return absint($ticket->id);
    }

    if (is_array($ticket) && isset($ticket['id'])) {
        return absint($ticket['id']);
    }

    return 0;
}

function adfoin_fluentsupport_get_ticket_title($ticket) {
    $title = '';

    if (is_object($ticket) && isset($ticket->title)) {
        $title = $ticket->title;
    } elseif (is_array($ticket) && isset($ticket['title'])) {
        $title = $ticket['title'];
    }

    if (!is_string($title) || $title === '') {
        $title = __('No Title', 'advanced-form-integration');
    }

    return $title;
}

function adfoin_fluentsupport_get_ticket_source($ticket) {
    if (is_object($ticket) && isset($ticket->source)) {
        return (string) $ticket->source;
    }

    if (is_array($ticket) && isset($ticket['source'])) {
        return (string) $ticket['source'];
    }

    return '';
}

function adfoin_fluentsupport_get_ticket_created_at($ticket) {
    $created_at = '';

    if (is_object($ticket) && isset($ticket->created_at)) {
        $raw = $ticket->created_at;
        if ($raw instanceof \DateTimeInterface) {
            $created_at = $raw->format('Y-m-d H:i:s');
        } elseif (is_string($raw) && $raw !== '') {
            $created_at = $raw;
        }
    } elseif (is_array($ticket) && !empty($ticket['created_at'])) {
        $created_at = $ticket['created_at'];
    }

    if (!$created_at) {
        $created_at = current_time('mysql');
    }

    return $created_at;
}

function adfoin_fluentsupport_get_customer_email($customer) {
    if (is_object($customer) && isset($customer->email)) {
        return $customer->email;
    }

    if (is_array($customer) && isset($customer['email'])) {
        return $customer['email'];
    }

    return '';
}

function adfoin_fluentsupport_get_customer_name($customer) {
    $name = '';

    if (is_object($customer)) {
        if (isset($customer->full_name) && $customer->full_name) {
            $name = $customer->full_name;
        } else {
            $first = isset($customer->first_name) ? $customer->first_name : '';
            $last  = isset($customer->last_name) ? $customer->last_name : '';
            $name  = trim($first . ' ' . $last);
        }
    } elseif (is_array($customer)) {
        $first = isset($customer['first_name']) ? $customer['first_name'] : '';
        $last  = isset($customer['last_name']) ? $customer['last_name'] : '';
        $name  = trim($first . ' ' . $last);
        if (!$name && isset($customer['full_name'])) {
            $name = $customer['full_name'];
        }
    }

    if (!$name) {
        $name = __('Unknown Customer', 'advanced-form-integration');
    }

    return $name;
}

function adfoin_fluentsupport_get_customer_user_id($customer) {
    if (is_object($customer) && isset($customer->user_id)) {
        return absint($customer->user_id);
    }

    if (is_array($customer) && isset($customer['user_id'])) {
        return absint($customer['user_id']);
    }

    return 0;
}

function adfoin_fluentsupport_get_customer_id($customer) {
    if (is_object($customer) && isset($customer->id)) {
        return absint($customer->id);
    }

    if (is_array($customer) && isset($customer['id'])) {
        return absint($customer['id']);
    }

    return 0;
}
