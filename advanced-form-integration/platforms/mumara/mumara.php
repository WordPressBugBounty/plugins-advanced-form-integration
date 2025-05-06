<?php

add_filter('adfoin_action_providers', 'adfoin_mumara_actions', 10, 1);
function adfoin_mumara_actions($actions) {
    $actions['mumara'] = [
        'title' => __('Mumara', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add Subscriber', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_mumara_settings_tab', 10, 1);
function adfoin_mumara_settings_tab($providers) {
    $providers['mumara'] = __('Mumara', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_mumara_settings_view', 10, 1);
function adfoin_mumara_settings_view($current_tab) {
    if ($current_tab !== 'mumara') return;

    $title = __('Mumara', 'advanced-form-integration');
    $key = 'mumara';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiUrl', 'label' => __('API URL', 'advanced-form-integration'), 'hidden' => false],
            ['key' => 'apiUsername', 'label' => __('API Username', 'advanced-form-integration'), 'hidden' => false],
            ['key' => 'apiPassword', 'label' => __('API Password', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Enter your Mumara API URL, Username, and Password.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_mumara_credentials', 'adfoin_get_mumara_credentials');
function adfoin_get_mumara_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('mumara'));
}

add_action('wp_ajax_adfoin_save_mumara_credentials', 'adfoin_save_mumara_credentials');
function adfoin_save_mumara_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'mumara') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('mumara', $data);
    }

    wp_send_json_success();
}

function adfoin_mumara_credentials_list() {
    foreach (adfoin_read_credentials('mumara') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_mumara_lists', 'adfoin_get_mumara_lists');
function adfoin_get_mumara_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $response = adfoin_mumara_request('get_lists', [], $cred_id);

    if (is_wp_error($response)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['result']) && is_array($body['result'])) {
        $lists = [];
        foreach ($body['result'] as $list) {
            $lists[$list['id']] = $list['listname'];
        }
        wp_send_json_success($lists);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_mumara_job_queue', 'adfoin_mumara_job_queue', 10, 1);
function adfoin_mumara_job_queue($data) {
    adfoin_mumara_send_data($data['record'], $data['posted_data']);
}

function adfoin_mumara_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'] ?? '';
    $list_id = $data['listId'] ?? '';

    $email = adfoin_get_parsed_values($data['email'] ?? '', $posted_data);
    $first_name = adfoin_get_parsed_values($data['first_name'] ?? '', $posted_data);
    $last_name = adfoin_get_parsed_values($data['last_name'] ?? '', $posted_data);

    $body = [
        'listID' => $list_id,
        'emailaddress' => $email,
        'firstname' => $first_name,
        'lastname' => $last_name,
    ];

    adfoin_mumara_request('add_subscriber', $body, $cred_id, 'POST', $record);
}

function adfoin_mumara_request($action, $data = [], $cred_id = '', $method = 'POST', $record = []) {
    $credentials = adfoin_get_credentials_by_id('mumara', $cred_id);

    $url = rtrim($credentials['apiUrl'], '/') . '/api.php';
    $fields = [
        'api_username' => $credentials['apiUsername'],
        'api_password' => $credentials['apiPassword'],
        'api_action' => $action,
        'api_output' => 'json'
    ];

    $fields = array_merge($fields, $data);

    $args = [
        'method' => 'POST',
        'timeout' => 30,
        'body' => http_build_query($fields),
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]
    ];

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('adfoin_action_fields', 'adfoin_mumara_action_fields');
function adfoin_mumara_action_fields() {
?>
<script type="text/template" id="mumara-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?></th>
            <td></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('Mumara Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_mumara_credentials_list(); ?>
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
    </table>
</script>
<?php
}