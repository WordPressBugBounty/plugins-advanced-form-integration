<?php

add_filter('adfoin_action_providers', 'adfoin_freshdesk_actions', 10, 1);

function adfoin_freshdesk_actions($actions)
{
    $actions['freshdesk'] = [
        'title' => __('Freshdesk', 'advanced-form-integration'),
        'tasks' => [
            'create_ticket' => __('Create Ticket', 'advanced-form-integration'),
        ],
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_freshdesk_settings_tab', 10, 1);

function adfoin_freshdesk_settings_tab($providers)
{
    $providers['freshdesk'] = __('Freshdesk', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_freshdesk_settings_view', 10, 1);

function adfoin_freshdesk_settings_view($current_tab)
{
    if ($current_tab != 'freshdesk') {
        return;
    }

    $title = __('Freshdesk', 'advanced-form-integration');
    $key = 'freshdesk';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'apiKey',
                'label' => __('API Key', 'advanced-form-integration'),
                'hidden' => true
            ],
            [
                'key' => 'appDomain',
                'label' => __('App Domain', 'advanced-form-integration'),
                'hidden' => false
            ]
        ]
    ]);
    $instructions = __('<p>To find the API Key and App Domain, follow these steps:</p><ol><li>Copy your account\'s full domain (e.g., https://afi1234.freshdesk.com).</li><li>Open Profile Settings, click <strong>View API Key</strong>, and copy it.</li></ol>', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_freshdesk_credentials', 'adfoin_get_freshdesk_credentials', 10, 0);

function adfoin_get_freshdesk_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('freshdesk');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_freshdesk_credentials', 'adfoin_save_freshdesk_credentials', 10, 0);

function adfoin_save_freshdesk_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);

    if ('freshdesk' == $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);

        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_freshdesk_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('freshdesk');

    foreach ($credentials as $option) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_freshdesk_action_fields');

function adfoin_freshdesk_action_fields() {
    ?>

    <script type="text/template" id="freshdesk-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_ticket'">
                <th scope="row">
                    <?php esc_attr_e('Ticket Fields', 'advanced-form-integration'); ?>
                </th>
                <td scope="row">
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_ticket'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Freshdesk Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="fetchTicketFields">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_freshdesk_credentials_list();
                        ?>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': ticketFieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>

    <?php
}

add_action('wp_ajax_adfoin_get_freshdesk_ticket_fields', 'adfoin_get_freshdesk_ticket_fields');

function adfoin_get_freshdesk_ticket_fields() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    // $agents = adfoin_freshdesk_get_agents_list($cred_id);
    $groups = adfoin_freshdesk_get_groups_list($cred_id);

    $fields = [
        ['key' => 'ticket_subject', 'value' => 'Ticket Subject', 'description' => ''],
        ['key' => 'ticket_description', 'value' => 'Ticket Description', 'description' => ''],
        ['kty' => 'ticket_type', 'value' => 'Type', 'description' => ''],
        ['key' => 'ticket_source', 'value' => 'Source ID', 'description' => 'Email: 1, Portal: 2, Phone: 3, Chat: 7, Feedback Widget: 9, Outbound Email: 10'],
        ['key' => 'ticket_status', 'value' => 'Status', 'description' => 'Open: 2, Pending: 3, Resolved: 4, Closed: 5'],
        ['key' => 'ticket_priority', 'value' => 'Priority', 'description' => 'Low: 1, Medium: 2, High: 3, Urgent: 4'],
        ['key' => 'ticket_group_id', 'value' => 'Group ID', 'description' => $groups],
        // ['key' => 'ticket_agent_id', 'value' => 'Agent ID', 'description' => $agents],
        ['key' => 'ticket_product_id', 'value' => 'Product ID', 'description' => ''],
        ['key' => 'ticket_cc_emails', 'value' => 'CC Emails', 'description' => ''],
        ['key' => 'contact_name', 'value' => 'Contact Name', 'description' => ''],
        ['key' => 'contact_email', 'value' => 'Contact Email', 'description' => ''],
        ['key' => 'contact_phone', 'value' => 'Contact Phone', 'description' => ''],
        ['key' => 'contact_job_title', 'value' => 'Contact Job Title', 'description' => ''],
        ['key' => 'contact_mobile', 'value' => 'Contact Mobile', 'description' => ''],
        ['key' => 'contact_twitter_id', 'value' => 'Contact Twitter', 'description' => ''],
        ['key' => 'contact_unique_external_id', 'value' => 'Contact Unique External ID', 'description' => ''],
        ['key' => 'contact_language', 'value' => 'Contact Language', 'description' => ''],
        ['key' => 'contact_time_zone', 'value' => 'Contact Time Zone', 'description' => ''],
        ['key' => 'contact_description', 'value' => 'Contact Description', 'description' => ''],
        ['key' => 'contact_address', 'value' => 'Contact Address', 'description' => ''],
        ['key' => 'company_name', 'value' => 'Company Name', 'description' => ''],
        ['key' => 'company_description', 'value' => 'Company Description', 'description' => ''],
        ['key' => 'company_notes', 'value' => 'Company Notes', 'description' => ''],
        ['key' => 'company_domains', 'value' => 'Company Domains', 'description' => '']
    ];

    wp_send_json_success($fields);
}

add_action('adfoin_job_queue', 'adfoin_freshdesk_job_queue', 10, 1);

function adfoin_freshdesk_job_queue($data)
{
    if ($data['action_provider'] === 'freshdesk' && $data['task'] === 'create_ticket') {
        adfoin_freshdesk_send_data($data['record'], $data['posted_data']);
    }
}

function adfoin_freshdesk_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = empty($data['credId']) ? '' : $data['credId'];
    $company_id = '';
    $contact_id = '';
    $ticket_id = '';

    $ticket_fields = array_filter([
        'subject' => empty($data['ticket_subject']) ? '' : adfoin_get_parsed_values($data['ticket_subject'], $posted_data),
        'description' => empty($data['ticket_description']) ? '' : adfoin_get_parsed_values($data['ticket_description'], $posted_data),
        'priority' => empty($data['ticket_priority']) ? 1 : intval( adfoin_get_parsed_values( $data['ticket_priority'], $posted_data) ),
        'status' => empty($data['ticket_status']) ? 2 : intval( adfoin_get_parsed_values($data['ticket_status'], $posted_data) ),
        'group_id' => empty($data['ticket_group_id']) ? '' : intval( adfoin_get_parsed_values($data['ticket_group_id'], $posted_data) ),
        // 'agent_id' => empty($data['ticket_agent_id']) ? '' : adfoin_get_parsed_values($data['ticket_agent_id'], $posted_data),
        'product_id' => empty($data['ticket_product_id']) ? '' : adfoin_get_parsed_values($data['ticket_product_id'], $posted_data),
        'cc_emails' => empty($data['ticket_cc_emails']) ? '' : adfoin_get_parsed_values($data['ticket_cc_emails'], $posted_data),
    ]);

    $contact_fields = array_filter([
        'name' => empty($data['contact_name']) ? '' : adfoin_get_parsed_values($data['contact_name'], $posted_data),
        'email' => empty($data['contact_email']) ? '' : adfoin_get_parsed_values($data['contact_email'], $posted_data),
        'phone' => empty($data['contact_phone']) ? '' : adfoin_get_parsed_values($data['contact_phone'], $posted_data),
        'job_title' => empty($data['contact_job_title']) ? '' : adfoin_get_parsed_values($data['contact_job_title'], $posted_data),
        'mobile' => empty($data['contact_mobile']) ? '' : adfoin_get_parsed_values($data['contact_mobile'], $posted_data),
        'twitter_id' => empty($data['contact_twitter_id']) ? '' : adfoin_get_parsed_values($data['contact_twitter_id'], $posted_data),
        'unique_external_id' => empty($data['contact_unique_external_id']) ? '' : adfoin_get_parsed_values($data['contact_unique_external_id'], $posted_data),
        'language' => empty($data['contact_language']) ? '' : adfoin_get_parsed_values($data['contact_language'], $posted_data),
        'time_zone' => empty($data['contact_time_zone']) ? '' : adfoin_get_parsed_values($data['contact_time_zone'], $posted_data),
        'description' => empty($data['contact_description']) ? '' : adfoin_get_parsed_values($data['contact_description'], $posted_data),
        'address' => empty($data['contact_address']) ? '' : adfoin_get_parsed_values($data['contact_address'], $posted_data),
    ]);

    if( isset( $data['company_name'] ) && $data['company_name'] != '' ) {
        $company_fields = array_filter([
            'name' => empty($data['company_name']) ? '' : adfoin_get_parsed_values($data['company_name'], $posted_data),
            'description' => empty($data['company_description']) ? '' : adfoin_get_parsed_values($data['company_description'], $posted_data),
            'notes' => empty($data['company_notes']) ? '' : adfoin_get_parsed_values($data['company_notes'], $posted_data),
            'domains' => empty($data['company_domains']) ? '' : explode(',', adfoin_get_parsed_values($data['company_domains'], $posted_data)),
        ]);

        $company_id = adfoin_freshdesk_find_company($company_fields, $cred_id);

        if (!$company_id) {
            $company_id = adfoin_freshdesk_create_company($company_fields, $record, $cred_id);
        } else {
            adfoin_freshdesk_request('companies/' . $company_id, 'PUT', $company_fields, $record, $cred_id);
        }

        if($company_id) {
            $contact_fields['company_id'] = $company_id;
        }
    }

    $contact_id = adfoin_freshdesk_find_contact($contact_fields, $cred_id);

    if (!$contact_id) {
        $contact_id = adfoin_freshdesk_create_contact($contact_fields, $record, $cred_id);
    } else {
        $response = adfoin_freshdesk_request('contacts/' . $contact_id, 'PUT', $contact_fields, $record, $cred_id);
    }

    if ($contact_id) {
        $ticket_fields['requester_id'] = $contact_id;
    }

    $response = adfoin_freshdesk_request('tickets', 'POST', $ticket_fields, $record, $cred_id);

    return $response;
}

function adfoin_freshdesk_find_company($company_fields, $cred_id) {
    $company_name = $company_fields['name'];

    $response = adfoin_freshdesk_request( 'companies/autocomplete?name=' . $company_name, 'GET', [], [], $cred_id );

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || empty($body['companies'])) {
        return false;
    }

    return $body['companies'][0]['id'];
}

function adfoin_freshdesk_create_company($company_fields, $record, $cred_id) {
    $response = adfoin_freshdesk_request('companies', 'POST', $company_fields, $record, $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    return $body['id'];
}

function adfoin_freshdesk_find_contact($contact_fields, $cred_id) {
    $contact_email = $contact_fields['email'];

    $response = adfoin_freshdesk_request('contacts?email=' . $contact_email, 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    return $body[0]['id'];
}

function adfoin_freshdesk_create_contact($contact_fields, $record, $cred_id) {
    $response = adfoin_freshdesk_request('contacts', 'POST', $contact_fields, $record, $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    return $body['id'];
}

function adfoin_freshdesk_get_agents_list($cred_id) {
    $response = adfoin_freshdesk_request('agents', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    $agents = [];
    foreach ($body as $agent) {
        $agents[] = $agent['contact']['name'] . ': ' . $agent['id'];
    }

    return implode(', ', $agents);
}
function adfoin_freshdesk_get_groups_list($cred_id) {
    $response = adfoin_freshdesk_request('groups', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    $groups = [];
    foreach ($body as $group) {
        $groups[] = $group['name'] . ': ' . $group['id'];
    }

    return implode(', ', $groups);
}

/*
 * Freshdesk API Request
 */
function adfoin_freshdesk_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('freshdesk', $cred_id);
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
    $app_domain = isset($credentials['appDomain']) ? $credentials['appDomain'] : '';
    $url = $app_domain . '/api/v2/' . $endpoint;

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($api_key . ':X')
        ],
    ];

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}
