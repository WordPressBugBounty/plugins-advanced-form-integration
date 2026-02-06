<?php

add_filter('adfoin_action_providers', 'adfoin_smartrmail_actions', 10, 1);
function adfoin_smartrmail_actions($actions) {
    $actions['smartrmail'] = [
        'title' => __('SmartrMail', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add/Update Subscriber to List', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_smartrmail_settings_tab', 10, 1);
function adfoin_smartrmail_settings_tab($providers) {
    $providers['smartrmail'] = __('SmartrMail', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_smartrmail_settings_view', 10, 1);
function adfoin_smartrmail_settings_view($current_tab) {
    if ($current_tab !== 'smartrmail') return;

    $title = __('SmartrMail', 'advanced-form-integration');
    $key = 'smartrmail';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            // SmartrMail uses a single Private API Token
            ['key' => 'apiToken', 'label' => __('Private API Token', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your SmartrMail Private API Token. You can generate this in your SmartrMail account under Account > API.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_smartrmail_credentials', 'adfoin_get_smartrmail_credentials');
function adfoin_get_smartrmail_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('smartrmail'));
}

add_action('wp_ajax_adfoin_save_smartrmail_credentials', 'adfoin_save_smartrmail_credentials');
function adfoin_save_smartrmail_credentials() {
    if (!adfoin_verify_nonce()) return;

    if (isset($_POST['platform']) && $_POST['platform'] === 'smartrmail') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('smartrmail', $data);
    }

    wp_send_json_success();
}

function adfoin_smartrmail_credentials_list() {
    foreach (adfoin_read_credentials('smartrmail') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_smartrmail_lists', 'adfoin_get_smartrmail_lists');
function adfoin_get_smartrmail_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $response = adfoin_smartrmail_request('lists', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $http_code = wp_remote_retrieve_response_code($response);

    if ($http_code === 200 && is_array($body)) {
        $lists = [];
        foreach($body as $list_item) {
            if (isset($list_item['id']) && isset($list_item['name'])) {
                $lists[$list_item['id']] = $list_item['name'];
            }
        }
        wp_send_json_success($lists);
    } elseif (isset($body['message'])) {
        wp_send_json_error($body['message']);
    } else {
        wp_send_json_error(__('Unable to retrieve lists or unexpected response format.', 'advanced-form-integration'));
    }
}

add_action('adfoin_smartrmail_job_queue', 'adfoin_smartrmail_job_queue', 10, 1);
function adfoin_smartrmail_job_queue($data) {
    adfoin_smartrmail_send_data($data['record'], $data['posted_data']);
}

function adfoin_smartrmail_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    // PHP 5.6 compatible array syntax and null coalescing
    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $list_id = isset($data['listId']) ? $data['listId'] : '';

    if (empty($cred_id) || empty($list_id)) {
        adfoin_add_to_log(null, 'Missing credentials or list ID for SmartrMail.', array(), $record);
        return;
    }

    $email = adfoin_get_parsed_values(isset($data['email']) ? $data['email'] : '', $posted_data);
    $first_name = adfoin_get_parsed_values(isset($data['first_name']) ? $data['first_name'] : '', $posted_data);
    $last_name = adfoin_get_parsed_values(isset($data['last_name']) ? $data['last_name'] : '', $posted_data);
    $phone = adfoin_get_parsed_values(isset($data['phone']) ? $data['phone'] : '', $posted_data);

    if (!is_email($email)) {
        adfoin_add_to_log(null, 'Invalid email for SmartrMail.', array('email' => $email), $record);
        return;
    }

    $custom_fields = array();
    foreach ($data as $key => $value) {
        if (strpos($key, 'custom_field_') === 0) {
            $field_slug = substr($key, strlen('custom_field_'));
            $field_value = adfoin_get_parsed_values($value, $posted_data);
            if ($field_value !== '') {
                $custom_fields[] = array(
                    'field_name' => $field_slug,
                    'value' => $field_value,
                    'value_type' => 'text', // Default to text, adjust as needed
                );
            }
        }
    }

    $subscriber = array(
        'email' => $email,
        'subscribed' => true,
    );

    if (!empty($first_name)) {
        $subscriber['first_name'] = $first_name;
    }
    if (!empty($last_name)) {
        $subscriber['last_name'] = $last_name;
    }
    if (!empty($phone)) {
        $subscriber['phone'] = $phone;
    }
    if (!empty($custom_fields)) {
        $subscriber['custom_fields'] = $custom_fields;
    }

    // API expects an array of subscribers
    $payload = array(
        'subscribers' => array($subscriber)
    );

    // Check if subscriber exists
    $check_email = urlencode($email);
    $check_endpoint = "subscribers/{$check_email}";
    $check_response = adfoin_smartrmail_request($check_endpoint, 'GET', [], $record, $cred_id);
    $check_code = wp_remote_retrieve_response_code($check_response);

    if ($check_code === 404) {
        // Subscriber does not exist, create new
        $endpoint = "lists/" . urlencode($list_id) . "/list_subscribers";
        adfoin_smartrmail_request($endpoint, 'POST', $payload, $record, $cred_id);
    } elseif ($check_code === 200) {
        // Subscriber exists, update
        $update_endpoint = "subscribers/{$check_email}";
        // The API expects the subscriber object directly for update, not wrapped in 'subscribers'
        adfoin_smartrmail_request($update_endpoint, 'PUT', $subscriber, $record, $cred_id);
    }
}

function adfoin_smartrmail_request($endpoint, $method = 'GET', $body_data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('smartrmail', $cred_id);
    $api_token = $credentials['apiToken'];
    $base_url = 'https://go.smartrmail.com/api/v1/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'Advanced-Form-Integration-WordPress-Plugin',
            'Authorization' => 'token ' . $api_token
        ]
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $args['body'] = json_encode($body_data);
    } elseif ($method === 'GET' && !empty($body_data)) {
        $url = add_query_arg($body_data, $url);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('adfoin_action_fields', 'adfoin_smartrmail_action_fields');
function adfoin_smartrmail_action_fields() {
?>
<script type="text/template" id="smartrmail-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('SmartrMail Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_smartrmail_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
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

        <!--
        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('Tags (comma-separated)', 'advanced-form-integration'); ?></th>
            <td>
                <input type="text" class="widefat" name="fieldData[tags]" v-model="fielddata.tags" />
                <p class="description"><?php _e('Enter tags separated by commas.', 'advanced-form-integration'); ?></p>
            </td>
        </tr>
        -->
    </table>
</script>
<?php
}