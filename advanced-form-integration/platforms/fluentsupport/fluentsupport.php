<?php

/**
 * Fluent Support Integration
 */

add_filter('adfoin_action_providers', 'adfoin_fluentsupport_actions', 10, 1);

function adfoin_fluentsupport_actions($actions)
{
    $actions['fluentsupport'] = [
        'title' => __('Fluent Support', 'advanced-form-integration'),
        'tasks' => [
            'create_ticket' => __('Create Ticket', 'advanced-form-integration'),
        ],
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_fluentsupport_settings_tab', 10, 1);

function adfoin_fluentsupport_settings_tab($providers)
{
    $providers['fluentsupport'] = __('Fluent Support', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_fluentsupport_settings_view', 10, 1);

function adfoin_fluentsupport_settings_view($current_tab)
{
    if ($current_tab != 'fluentsupport') {
        return;
    }

    if (!is_plugin_active('fluent-support/fluent-support.php')) {
        echo '<div class="notice notice-error"><p>' . __('Fluent Support plugin is not active or installed.', 'advanced-form-integration') . '</p></div>';
        return;
    }

    $nonce = wp_create_nonce('adfoin_fluentsupport_settings');
    ?>

    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e('Settings', 'advanced-form-integration'); ?></th>
            <td>
                <p><?php _e('Fluent Support does not require additional API tokens. Ensure the plugin is activated and properly configured.', 'advanced-form-integration'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

add_action('adfoin_action_fields', 'adfoin_fluentsupport_action_fields');

function adfoin_fluentsupport_action_fields()
{
    ?>
    <script type="text/template" id="fluentsupport-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_ticket'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'create_ticket'">
                <th scope="row"><?php esc_attr_e('MailBox', 'advanced-form-integration'); ?></th>
                <td>
                    <select name="fieldData[mailboxId]" v-model="fielddata.mailboxId" required="required">
                        <option value=""><?php _e('Select Mailbox...', 'advanced-form-integration'); ?></option>
                        <option v-for="(mailbox, index) in fielddata.mailboxes" :value="mailbox.id">{{ mailbox.name }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': mailboxLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:5px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_ticket'">
                <th scope="row"><?php esc_attr_e('Agent', 'advanced-form-integration'); ?></th>
                <td>
                    <select name="fieldData[agentId]" v-model="fielddata.agentId" required="required">
                        <option value=""><?php _e('Select Agent...', 'advanced-form-integration'); ?></option>
                        <option v-for="(agent, index) in fielddata.agents" :value="agent.id">{{ agent.name }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': agentLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:5px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

// Register AJAX handlers for fetching mailboxes and agents
add_action('wp_ajax_adfoin_get_fluentsupport_mailboxes', 'adfoin_get_fluentsupport_mailboxes');
add_action('wp_ajax_adfoin_get_fluentsupport_agents', 'adfoin_get_fluentsupport_agents');

/**
 * Get Fluent Support Mailboxes
 */
function adfoin_get_fluentsupport_mailboxes()
{
    // Check if Fluent Support is active
    if (!is_plugin_active('fluent-support/fluent-support.php')) {
        wp_send_json_error(__('Fluent Support plugin is not active or installed.', 'advanced-form-integration'));
    }

    // Fetch mailboxes
    try {
        $mailboxes = \FluentSupport\App\Models\MailBox::all();

        if (empty($mailboxes)) {
            wp_send_json_error(__('No mailboxes found.', 'advanced-form-integration'));
        }

        // Format the response
        $formattedMailboxes = [];
        foreach ($mailboxes as $mailbox) {
            $formattedMailboxes[] = [
                'id' => $mailbox->id,
                'name' => $mailbox->name,
            ];
        }

        wp_send_json_success($formattedMailboxes);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

/**
 * Get Fluent Support Agents
 */
function adfoin_get_fluentsupport_agents()
{
    // Check if Fluent Support is active
    if (!is_plugin_active('fluent-support/fluent-support.php')) {
        wp_send_json_error(__('Fluent Support plugin is not active or installed.', 'advanced-form-integration'));
    }

    // Fetch agents
    try {
        $agents = \FluentSupport\App\Models\Agent::get();

        if (empty($agents)) {
            wp_send_json_error(__('No agents found.', 'advanced-form-integration'));
        }

        // Format the response
        $formattedAgents = [];
        foreach ($agents as $agent) {
            $formattedAgents[] = [
                'id' => $agent->id,
                'name' => $agent->full_name,
            ];
        }

        wp_send_json_success($formattedAgents);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}


add_action('adfoin_job_queue', 'adfoin_fluentsupport_job_queue', 10, 1);

function adfoin_fluentsupport_job_queue($data)
{
    if ($data['action_provider'] === 'fluentsupport' && $data['task'] === 'create_ticket') {
        adfoin_fluentsupport_send_data($data['record'], $data['posted_data']);
    }
}

function adfoin_fluentsupport_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (array_key_exists('cl', $record_data['action_data'])) {
        if ($record_data['action_data']['cl']['active'] == 'yes') {
            if (!adfoin_match_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];

    if ($task == 'create_ticket') {
        $mailbox_id = isset($data['mailboxId']) ? $data['mailboxId'] : null;
        $agent_id = isset($data['agentId']) ? $data['agentId'] : null;
        $subject = isset($data['subject']) ? adfoin_get_parsed_values( $data['subject'], $posted_data ) : '';
        $message = isset($data['message']) ? adfoin_get_parsed_values( $data['message'], $posted_data ) : '';
        $email = isset($data['email']) ? adfoin_get_parsed_values( $data['email'], $posted_data ) : '';
        $first_name = isset($data['firstName']) ? adfoin_get_parsed_values( $data['firstName'], $posted_data ) : '';
        $last_name = isset($data['lastName']) ? adfoin_get_parsed_values( $data['lastName'], $posted_data ) : '';

        if (empty($email) || empty($subject) || empty($message)) {
            return new WP_Error('REQ_FIELD_EMPTY', __('Email, Subject, and Message fields are required.', 'advanced-form-integration'));
        }

        if (!is_plugin_active('fluent-support/fluent-support.php')) {
            return new WP_Error('PLUGIN_NOT_ACTIVE', __('Fluent Support plugin is not active or installed.', 'advanced-form-integration'));
        }

        // if (!function_exists('adfoin_fluentsupport_get_or_create_customer')) {
        //     return new WP_Error('FUNCTION_NOT_FOUND', __('Required function adfoin_fluentsupport_get_or_create_customer does not exist.', 'advanced-form-integration'));
        // }

        $customer_id = adfoin_fluentsupport_get_or_create_customer($email, $first_name, $last_name);
        if (is_wp_error($customer_id)) {
            return $customer_id;
        }

        if (!class_exists('\FluentSupport\App\Models\Ticket')) {
            return new WP_Error('CLASS_NOT_FOUND', __('Required class \FluentSupport\App\Models\Ticket does not exist.', 'advanced-form-integration'));
        }

        $ticket_data = array(
            'mailbox_id' => $mailbox_id,
            'agent_id' => $agent_id,
            'customer_id' => $customer_id,
            'title' => $subject,
            'content' => $message,
            'status' => 'new',
            'priority' => 'normal',
        );

        $ticket = \FluentSupport\App\Models\Ticket::create($ticket_data);

        if (!isset($ticket->id)) {
            return new WP_Error('TICKET_CREATION_FAILED', __('Failed to create a ticket.', 'advanced-form-integration'));
        }

        return $ticket;
    }

    return;
}

function adfoin_fluentsupport_get_or_create_customer($email, $first_name, $last_name) {
    if (!class_exists('\FluentSupport\App\Models\Customer')) {
        return new WP_Error('CLASS_NOT_FOUND', __('Required class \FluentSupport\App\Models\Customer does not exist.', 'advanced-form-integration'));
    }

    $customer = \FluentSupport\App\Models\Customer::where('email', $email)->first();

    if ($customer) {
        return $customer->id;
    }

    $customer_data = array(
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
    );

    $customer = \FluentSupport\App\Models\Customer::create($customer_data);

    if (!isset($customer->id)) {
        return new WP_Error('CUSTOMER_CREATION_FAILED', __('Failed to create a customer.', 'advanced-form-integration'));
    }

    return $customer->id;
}

