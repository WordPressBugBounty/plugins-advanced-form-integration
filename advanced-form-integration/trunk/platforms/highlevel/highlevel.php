<?php

add_filter('adfoin_action_providers', 'adfoin_highlevel_actions', 10, 1);

function adfoin_highlevel_actions($actions) {
    $actions['highlevel'] = [
        'title' => __('HighLevel', 'advanced-form-integration'),
        'tasks' => [
            'create_contact' => __('Create Contact', 'advanced-form-integration'),
        ]
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_highlevel_settings_tab', 10, 1);

function adfoin_highlevel_settings_tab($providers) {
    $providers['highlevel'] = __('HighLevel', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_highlevel_settings_view', 10, 1);

function adfoin_highlevel_settings_view($current_tab) {
    if ($current_tab != 'highlevel') {
        return;
    }

    $title = __('HighLevel', 'advanced-form-integration');
    $key   = 'highlevel';
    $arguments = json_encode([
        'platform' => $key,
        'fields'   => [
            [
                'key'   => 'apiKey',
                'label' => __('API Key', 'advanced-form-integration'),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = __('<p>Go to Settings > Business Profile and copy the API Key</p>', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_highlevel_credentials', 'adfoin_get_highlevel_credentials', 10, 0);

function adfoin_get_highlevel_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('highlevel');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_highlevel_credentials', 'adfoin_save_highlevel_credentials', 10, 0);

function adfoin_save_highlevel_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);

    if ('highlevel' == $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_highlevel_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('highlevel');

    foreach ($credentials as $option) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_highlevel_action_fields');

function adfoin_highlevel_action_fields() {
    ?>
    <script type="text/template" id="highlevel-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?></th>
                <td></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('HighLevel Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_highlevel_credentials_list();
                        ?>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_highlevel_fields', 'adfoin_get_highlevel_fields');

function adfoin_get_highlevel_fields() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $users = adfoin_highlevel_get_users_list($cred_id);
    $ps = adfoin_highlevel_get_pipelines_and_stages($cred_id);

    $contact_fields = [
        ['key' => 'contact_type', 'value' => 'Contact Type', 'description' => 'lead or customer'],
        ['key' => 'contact_firstName', 'value' => 'First Name', 'description' => ''],
        ['key' => 'contact_lastName', 'value' => 'Last Name', 'description' => ''],
        ['key' => 'contact_name', 'value' => 'Name', 'description' => ''],
        ['key' => 'contact_email', 'value' => 'Email', 'description' => ''],
        ['key' => 'contact_phone', 'value' => 'Phone', 'description' => ''],
        ['key' => 'contact_address1', 'value' => 'Address 1', 'description' => ''],
        ['key' => 'contact_city', 'value' => 'City', 'description' => ''],
        ['key' => 'contact_state', 'value' => 'State', 'description' => ''],
        ['key' => 'contact_postalCode', 'value' => 'Postal Code', 'description' => ''],
        ['key' => 'contact_website', 'value' => 'Website', 'description' => ''],
        ['key' => 'contact_timezone', 'value' => 'Timezone', 'description' => ''],
        ['key' => 'contact_companyName', 'value' => 'Company Name', 'description' => ''],
        ['key' => 'contact_source', 'value' => 'Source', 'description' => ''],
        ['key' => 'contact_dateOfBirth', 'value' => 'Date of Birth', 'description' => ''],
        ['key' => 'contact_assignedTo', 'value' => 'Owner ID', 'description' => $users],

    ];

    $opportunity_fields = [
        ['key' => 'opportunity_title', 'value' => 'Opportunity Name', 'description' => 'Required for opportunity creation'],
        ['key' => 'opportunity_pipeline', 'value' => 'Pipeline ID', 'description' => $ps['pipelines']],
        ['key' => 'opportunity_stage', 'value' => 'Stage ID', 'description' => $ps['stages']],
        ['key' => 'opportunity_value', 'value' => 'Opportunity Value', 'description' => ''],
        ['key' => 'opportunity_status', 'value' => 'Opportunity Status', 'description' => 'open, won, lost, abandoned'],
    ];

    $contact_fields = array_merge($contact_fields, $opportunity_fields);

    wp_send_json_success($contact_fields);
}

function adfoin_highlevel_get_users_list($cred_id) {
    $response = adfoin_highlevel_request('users', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    $users = [];
    
    if( $body['users'] && is_array($body['users']) ) {
        foreach ($body['users'] as $user) {
            $users[] = $user['name'] . ': ' . $user['id'];
        }
    }

    return implode(', ', $users);
}

function adfoin_highlevel_get_pipelines_and_stages($cred_id) {
    $response = adfoin_highlevel_request('pipelines', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    $pipelines = [];
    $stages = [];
    
    if (isset($body['pipelines']) && is_array($body['pipelines'])) {
        foreach ($body['pipelines'] as $pipeline) {
            $pipelines[] = $pipeline['name'] . ': ' . $pipeline['id'];
            if (isset($pipeline['stages']) && is_array($pipeline['stages'])) {
                foreach ($pipeline['stages'] as $stage) {
                    $stages[] = $stage['name'] . ': ' . $stage['id'];
                }
            }
        }
    }

    return [
        'pipelines' => implode(', ', $pipelines),
        'stages' => implode(', ', $stages),
    ];
}

function adfoin_highlevel_search_contact($email, $cred_id) {
    $response = adfoin_highlevel_request('contacts/lookup?email=' . urlencode($email), 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return false;
    }

    if( isset($body['contacts'], ['contacts'][0] ) ) {
        return $body['contacts'][0]['id'];
    }
}

add_action('adfoin_highlevel_job_queue', 'adfoin_highlevel_job_queue', 10, 1);

function adfoin_highlevel_job_queue($data) {
    adfoin_highlevel_send_data($data['record'], $data['posted_data']);
}

/*
 * Handles sending data to HighLevel API
 */
function adfoin_highlevel_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $task = $record['task'];
    $contact_id = '';
    $cred_id = empty($data['credId']) ? '' : $data['credId'];

    if ($task == 'create_contact') {
        $contact_data = array_filter([
            'type' => empty($data['contact_type']) ? 'lead' : adfoin_get_parsed_values( $data['contact_type'], $posted_data ),
            'firstName' => adfoin_get_parsed_values( $data['contact_firstName'], $posted_data ),
            'lastName' => adfoin_get_parsed_values( $data['contact_lastName'], $posted_data ),
            'name' => adfoin_get_parsed_values( $data['contact_name'], $posted_data ),
            'email' => adfoin_get_parsed_values( $data['contact_email'], $posted_data ),
            'phone' => adfoin_get_parsed_values( $data['contact_phone'], $posted_data ),
            'address1' => adfoin_get_parsed_values( $data['contact_address1'], $posted_data ),
            'city' => adfoin_get_parsed_values( $data['contact_city'], $posted_data ),
            'state' => adfoin_get_parsed_values( $data['contact_state'], $posted_data ),
            'postalCode' => adfoin_get_parsed_values( $data['contact_postalCode'], $posted_data ),
            'website' => adfoin_get_parsed_values( $data['contact_website'], $posted_data ),
            'timezone' => adfoin_get_parsed_values( $data['contact_timezone'], $posted_data ),
            'companyName' => adfoin_get_parsed_values( $data['contact_companyName'], $posted_data ),
            'source' => adfoin_get_parsed_values( $data['contact_source'], $posted_data ),
            'dateOfBirth' => adfoin_get_parsed_values( $data['contact_dateOfBirth'], $posted_data ),
            'assignedTo' => adfoin_get_parsed_values( $data['contact_assignedTo'], $posted_data ),
        ]);

        $contact_id = adfoin_highlevel_search_contact($contact_data['email'], $cred_id);

        if ($contact_id) {
            $contact_response = adfoin_highlevel_request('contacts/' . $contact_id, 'PUT', $contact_data, $record, $cred_id);
        } else {
            $contact_response = adfoin_highlevel_request('contacts', 'POST', $contact_data, $record, $cred_id);

            if (!is_wp_error($contact_response)) {
                $contact_body = json_decode(wp_remote_retrieve_body($contact_response), true);
                if (isset($contact_body['contact'], $contact_body['contact']['id'])) {
                    $contact_id = $contact_body['contact']['id'];
                }
            }
        }

        $opportunity_data = array_filter([
            'title' => adfoin_get_parsed_values( $data['opportunity_title'], $posted_data ),
            'stageId' => adfoin_get_parsed_values( $data['opportunity_stage'], $posted_data ),
            'value' => adfoin_get_parsed_values( $data['opportunity_value'], $posted_data ),
            'status' => adfoin_get_parsed_values( $data['opportunity_status'], $posted_data ),
            'contactId' => $contact_id
        ]);

        if( $contact_data['assignedTo'] ) {
            $opportunity_data['assignedTo'] = $contact_data['assignedTo'];
        }

        $pipeline_id = adfoin_get_parsed_values( $data['opportunity_pipeline'], $posted_data );

        $opportunity_response = adfoin_highlevel_request('pipelines/'. $pipeline_id.'/opportunities', 'POST', $opportunity_data, $record, $cred_id);

    }
}

/*
 * HighLevel API Request
 */
function adfoin_highlevel_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
    $base_url = 'https://rest.gohighlevel.com/v1/';
    $url = $base_url . $endpoint;

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
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
