<?php

add_filter('adfoin_action_providers', 'adfoin_enormail_actions', 10, 1);
function adfoin_enormail_actions($actions) {
    $actions['enormail'] = [
        'title' => __('Enormail', 'advanced-form-integration'),
        'tasks' => ['add_subscriber' => __('Add Subscriber', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_enormail_settings_tab', 10, 1);
function adfoin_enormail_settings_tab($providers) {
    $providers['enormail'] = __('Enormail', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_enormail_settings_view', 10, 1);
function adfoin_enormail_settings_view($current_tab) {
    if ($current_tab !== 'enormail') return;

    $title = __('Enormail', 'advanced-form-integration');
    $key = 'enormail';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Get your Enormail API Key from your Enormail account.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_enormail_credentials', 'adfoin_get_enormail_credentials');
function adfoin_get_enormail_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('enormail'));
}

add_action('wp_ajax_adfoin_save_enormail_credentials', 'adfoin_save_enormail_credentials');
function adfoin_save_enormail_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'enormail') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('enormail', $data);
    }

    wp_send_json_success();
}

function adfoin_enormail_credentials_list() {
    foreach (adfoin_read_credentials('enormail') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_enormail_lists', 'adfoin_get_enormail_lists');
function adfoin_get_enormail_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $listsRes = adfoin_enormail_request('lists.json', 'GET', [], [], $cred_id);

    if (is_wp_error($listsRes)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($listsRes), true);

    if (!empty($body)) {
        $lists = wp_list_pluck($body, 'title', 'listid');
        wp_send_json_success($lists);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_enormail_job_queue', 'adfoin_enormail_job_queue', 10, 1);
function adfoin_enormail_job_queue($data) {
    adfoin_enormail_send_data($data['record'], $data['posted_data']);
}

function adfoin_enormail_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data    = $record_data['field_data'];
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $list_id = isset($data['listId']) ? $data['listId'] : '';

    $email  = adfoin_get_parsed_values(isset($data['email']) ? $data['email'] : '', $posted_data);
    $name   = adfoin_get_parsed_values(isset($data['name']) ? $data['name'] : '', $posted_data);
    
    unset($data['credId'], $data['listId'], $data['email'], $data['name'], $data['tags'], $data['customFields']);
    $fields = [];

    foreach ($data as $key => $field) {
        if ($field) {
            $value = adfoin_get_parsed_values($field, $posted_data);
            if ($value !== '') {
                $fields[$key] = $value;
            }
        }
    }

    $body = [
        'email' => $email,
        'name'  => $name,
        'listid' => $list_id,
        'activate_autoresponder' => 1,
    ];

    if (!empty($fields)) {
        $body['fields'] = $fields;
    }

    adfoin_enormail_request("contacts/{$list_id}", 'POST', $body, $record, $cred_id);
}

function adfoin_enormail_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('enormail', $cred_id);
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
    $base_url = 'https://api.enormail.eu/api/1.0/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($api_key . ':')
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

add_action('adfoin_action_fields', 'adfoin_enormail_action_fields');
function adfoin_enormail_action_fields() {
?>
<script type="text/template" id="enormail-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
            </th>
            <td scope="row">

            </td>
        </tr>
        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Enormail Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_enormail_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Mailing List', 'advanced-form-integration'); ?></th>
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