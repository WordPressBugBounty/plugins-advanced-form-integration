<?php

// Register Mailmodo action provider
add_filter('adfoin_action_providers', 'adfoin_mailmodo_actions', 10, 1);
function adfoin_mailmodo_actions($actions) {
    $actions['mailmodo'] = [
        'title' => __('Mailmodo', 'advanced-form-integration'),
        'tasks' => [
            'subscribe' => __('Add Contact to List', 'advanced-form-integration') // Renamed task for clarity
        ]
    ];
    return $actions;
}

// Register Mailmodo settings tab
add_filter('adfoin_settings_tabs', 'adfoin_mailmodo_settings_tab', 10, 1);
function adfoin_mailmodo_settings_tab($providers) {
    $providers['mailmodo'] = __('Mailmodo', 'advanced-form-integration');
    return $providers;
}

// Display Mailmodo settings view
add_action('adfoin_settings_view', 'adfoin_mailmodo_settings_view', 10, 1);
function adfoin_mailmodo_settings_view($current_tab) {
    if ($current_tab !== 'mailmodo') return;

    $title = __('Mailmodo', 'advanced-form-integration');
    $key = 'mailmodo';
    // Only API Key is needed for Mailmodo
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            // Renamed key to apiKey for consistency within the plugin's credential storage
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your Mailmodo API Key. You can generate one in your Mailmodo account under Settings -> API Keys.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

// AJAX handler to get saved Mailmodo credentials
add_action('wp_ajax_adfoin_get_mailmodo_credentials', 'adfoin_get_mailmodo_credentials');
function adfoin_get_mailmodo_credentials() {
    if (!adfoin_verify_nonce()) return;
    // Use the generic credential reading function
    wp_send_json_success(adfoin_read_credentials('mailmodo'));
}

// AJAX handler to save Mailmodo credentials
add_action('wp_ajax_adfoin_save_mailmodo_credentials', 'adfoin_save_mailmodo_credentials');
function adfoin_save_mailmodo_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'mailmodo') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        // Use the generic credential saving function
        adfoin_save_credentials('mailmodo', $data);
    }

    wp_send_json_success();
}

// Helper function to generate Mailmodo account options (for the action settings dropdown)
function adfoin_mailmodo_credentials_list() {
    // Use the generic credential reading function
    foreach (adfoin_read_credentials('mailmodo') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_mailmodo_lists', 'adfoin_get_mailmodo_lists');

function adfoin_get_mailmodo_lists() {
    if (!adfoin_verify_nonce()) {
        return;
    }

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $lists = [];

    $response = adfoin_mailmodo_request('getAllContactLists', 'GET', [], [], $cred_id);

    if (!is_wp_error($response)) {
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        if(isset($response_body['listDetails']) && !empty($response_body['listDetails'])) {
            foreach ($response_body['listDetails'] as $list) {
                $lists[$list['name']] = $list['name'];
            }
        } else {
            wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
        } 
    }
    wp_send_json_success($lists);
}

// Hook into the job queue for Mailmodo actions
add_action('adfoin_mailmodo_job_queue', 'adfoin_mailmodo_job_queue', 10, 1);
function adfoin_mailmodo_job_queue($data) {
    // Call the data sending function
    adfoin_mailmodo_send_data($data['record'], $data['posted_data']);
}

// Function to process and send data to Mailmodo API
function adfoin_mailmodo_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && is_array($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $list_id = isset($data['listId']) ? $data['listId'] : '';

    $body = array(
        'email' => isset($data['email']) ? adfoin_get_parsed_values($data['email'], $posted_data) : ''
    );

    $recipient_properties = array_filter([
        'first_name' => isset($data['first_name']) ? adfoin_get_parsed_values($data['first_name'], $posted_data) : '',
        'last_name' => isset($data['last_name']) ? adfoin_get_parsed_values($data['last_name'], $posted_data) : '',
    ]);

    if(!empty($list_id)) {
        $body['listName'] = $list_id;
    }

    if (!empty($recipient_properties)) {
        $body['data'] = $recipient_properties;
    }

    // Send the request
    adfoin_mailmodo_request("addToList", 'POST', $body, $record, $cred_id);
}

function adfoin_mailmodo_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('mailmodo', $cred_id);
    $api_key = $credentials['apiKey'];
    $base_url = 'https://api.mailmodo.com/api/v1/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'mmApiKey'     => $api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]
    ];

    if (in_array($method, ['POST', 'PUT'])) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('adfoin_action_fields', 'adfoin_mailmodo_action_fields');

function adfoin_mailmodo_action_fields() {
?>
<script type="text/template" id="mailmodo-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'subscribe'">
            <th scope="row">
                <?php esc_html_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'subscribe'">
            <th scope="row"><?php esc_html_e('Mailmodo Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists" required>
                    <option value=""><?php esc_html_e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_mailmodo_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'subscribe'">
            <th scope="row"><?php _e('Mailing List', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[listId]" v-model="fielddata.listId" required>
                    <option value=""><?php _e('Select List...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.lists" :value="id">{{ name }}</option>
                </select>
                <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>

    </table>
</script>
<?php
}
?>