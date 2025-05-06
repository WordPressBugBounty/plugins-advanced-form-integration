<?php

add_filter('adfoin_action_providers', 'adfoin_copernica_actions', 10, 1);
function adfoin_copernica_actions($actions) {
    $actions['copernica'] = [
        'title' => __('Copernica', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add Profile', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_copernica_settings_tab', 10, 1);
function adfoin_copernica_settings_tab($providers) {
    $providers['copernica'] = __('Copernica', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_copernica_settings_view', 10, 1);
function adfoin_copernica_settings_view($current_tab) {
    if ($current_tab !== 'copernica') return;

    $title = __('Copernica', 'advanced-form-integration');
    $key = 'copernica';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'accessToken', 'label' => __('Access Token', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Go to Configuration > API access tokens and create. Select SOAP and REST API', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_copernica_credentials', 'adfoin_get_copernica_credentials');
function adfoin_get_copernica_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('copernica'));
}

add_action('wp_ajax_adfoin_save_copernica_credentials', 'adfoin_save_copernica_credentials');
function adfoin_save_copernica_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'copernica') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('copernica', $data);
    }

    wp_send_json_success();
}

function adfoin_copernica_credentials_list() {
    foreach (adfoin_read_credentials('copernica') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('adfoin_action_fields', 'adfoin_copernica_action_fields');
function adfoin_copernica_action_fields() {
?>
<script type="text/template" id="copernica-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>
        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th><?php esc_attr_e('Copernica Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getDatabases">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_copernica_credentials_list(); ?>
                </select>
            </td>
        </tr>
        <tr class="alternate" valign="top" v-if="action.task == 'add_subscriber'">
            <th><?php esc_attr_e('Database', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[databaseId]" v-model="fielddata.databaseId" @change="getFields">
                    <option value=""><?php _e('Select Database...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.databases" :value="id">{{ name }}</option>
                </select>
                <div class="spinner" v-bind:class="{'is-active': dbLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

function adfoin_get_copernica_jwt($cred_id) {
    $credentials = adfoin_get_credentials_by_id('copernica', $cred_id);
    $access_token = $credentials['accessToken'] ?? '';

    $jwt_data = get_option("adfoin_copernica_jwt_{$cred_id}", []);

    if (!empty($jwt_data['jwt']) && !empty($jwt_data['fetched_at']) && time() - $jwt_data['fetched_at'] < 86000) {
        return $jwt_data['jwt'];
    }

    $response = wp_remote_post('https://authenticate.copernica.com', [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => http_build_query(['access_token' => $access_token])
    ]);

    if (is_wp_error($response)) return '';

    if (wp_remote_retrieve_response_code($response) !== 200) {
        return '';
    }

    $body = wp_remote_retrieve_body($response);

    if (!empty($body)) {
        update_option("adfoin_copernica_jwt_{$cred_id}", [
            'jwt' => $body,
            'fetched_at' => time()
        ]);
        return $body;
    }

    return '';
}

function adfoin_copernica_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $jwt = adfoin_get_copernica_jwt($cred_id);

    $url = 'https://rest.copernica.com/v4/' . ltrim($endpoint, '/');

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt,
            'Content-Type'  => 'application/json'
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

add_action('wp_ajax_adfoin_get_copernica_databases', 'adfoin_get_copernica_databases');
function adfoin_get_copernica_databases() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $response = adfoin_copernica_request('databases', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error('Request failed');
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $output = [];

    if (isset($data['data']) && is_array($data['data'])) {
        $output = wp_list_pluck($data['data'], 'name', 'ID');
    }

    wp_send_json_success($output);
}

add_action('wp_ajax_adfoin_get_copernica_fields', 'adfoin_get_copernica_fields');
function adfoin_get_copernica_fields() {
    if (!adfoin_verify_nonce()) return;

    $cred_id    = sanitize_text_field($_POST['credId']);
    $databaseId = sanitize_text_field($_POST['databaseId']);

    $response = adfoin_copernica_request("database/{$databaseId}/fields", 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error('Request failed');
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['data']) && !empty($body['data'])) {
        $fields = [];

        foreach ($body['data'] as $field) {
            $field_type = $field['type'] ? $field['type'] : '';
            $description = '';

            if ($field_type === 'select' && !empty($field['value'])) {
                $options = array_filter(array_map('trim', explode("\n", $field['value'])));
                $description = __('Options: ', 'advanced-form-integration') . implode(', ', $options);
            }

            $fields[] = [
                'key'       => $field_type . '__' . $field['name'],
                'value'       => $field['name'],
                'description' => $description
            ];
        }

        wp_send_json_success($fields);
    } else {
        wp_send_json_error('No fields found');
        return;
        
    }
}

add_action('adfoin_copernica_job_queue', 'adfoin_copernica_job_queue', 10, 1);
function adfoin_copernica_job_queue($data) {
    adfoin_copernica_send_data($data['record'], $data['posted_data']);
}

function adfoin_copernica_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : [];
    $task = isset($record_data['task']) ? $record_data['task'] : '';
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $database_id = isset($data['databaseId']) ? $data['databaseId'] : '';

    unset($data['credId'], $data['databaseId']);

    $profile_data = array();

    foreach ($data as $key => $value) {
        if (empty($value)) {
            continue;
        }

        $key_parts = explode('__', $key, 2);
        $parsed_value = adfoin_get_parsed_values($value, $posted_data);

        if (!empty($parsed_value)) {
            $profile_data[$key_parts[1]] = $parsed_value;
        }
    }

    $body = array(
        'fields' => $profile_data
    );

    adfoin_copernica_request("database/{$database_id}/profiles", 'POST', $body, $record, $cred_id);
}
