<?php

add_filter('adfoin_action_providers', 'adfoin_keila_actions', 10, 1);
function adfoin_keila_actions($actions) {
    $actions['keila'] = [
        'title' => __('Keila', 'advanced-form-integration'),
        'tasks' => [
            'add_contact' => __('Add Contact', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_keila_settings_tab', 10, 1);
function adfoin_keila_settings_tab($providers) {
    $providers['keila'] = __('Keila', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_keila_settings_view', 10, 1);
function adfoin_keila_settings_view($current_tab) {
    if ($current_tab !== 'keila') return;

    $title = __('Keila', 'advanced-form-integration');
    $key = 'keila';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiToken', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Enter your Keila API Key.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_keila_credentials', 'adfoin_get_keila_credentials');
function adfoin_get_keila_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('keila'));
}

add_action('wp_ajax_adfoin_save_keila_credentials', 'adfoin_save_keila_credentials');
function adfoin_save_keila_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'keila') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('keila', $data);
    }

    wp_send_json_success();
}

function adfoin_keila_credentials_list() {
    foreach (adfoin_read_credentials('keila') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('adfoin_keila_job_queue', 'adfoin_keila_job_queue', 10, 1);
function adfoin_keila_job_queue($data) {
    adfoin_keila_send_data($data['record'], $data['posted_data']);
}

function adfoin_keila_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'] ?? '';

    $email       = adfoin_get_parsed_values($data['email'] ?? '', $posted_data);
    $first_name  = adfoin_get_parsed_values($data['first_name'] ?? '', $posted_data);
    $last_name   = adfoin_get_parsed_values($data['last_name'] ?? '', $posted_data);
    $city        = adfoin_get_parsed_values($data['city'] ?? '', $posted_data);
    $external_id = adfoin_get_parsed_values($data['external_id'] ?? '', $posted_data);

    // unset($data['credId'], $data['email'], $data['first_name'], $data['last_name'], $data['city'], $data['external_id']);

    // $custom_data = [];

    // foreach ($data as $key => $value) {
    //     $parsed = adfoin_get_parsed_values($value, $posted_data);
    //     if ($parsed !== '') {
    //         $custom_data[$key] = $parsed;
    //     }
    // }

    $payload = [
        'data' => array_filter([
            'email'       => $email,
            'first_name'  => $first_name,
            'last_name'   => $last_name,
            'status'      => 'active',
            'city'        => $city,
            'external_id' => $external_id,
            // 'data'        => $custom_data
        ])
    ];

    adfoin_keila_request('contacts', 'POST', $payload, $record, $cred_id);
}

function adfoin_keila_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('keila', $cred_id);
    $api_token = $credentials['apiToken'] ?? '';

    $base_url = 'https://app.keila.io/api/v1/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type' => 'application/json'
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

add_action('adfoin_action_fields', 'adfoin_keila_action_fields');
function adfoin_keila_action_fields() {
?>
<script type="text/template" id="keila-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_contact'">
            <th scope="row"><?php esc_attr_e('Keila Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_keila_credentials_list(); ?>
                </select>
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