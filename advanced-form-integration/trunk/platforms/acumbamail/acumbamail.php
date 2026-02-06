<?php
add_filter('adfoin_action_providers', 'adfoin_acumbamail_actions', 10, 1);
function adfoin_acumbamail_actions($actions) {
    $actions['acumbamail'] = [
        'title' => __('Acumbamail', 'advanced-form-integration'),
        'tasks' => ['add_subscriber' => __('Add Subscriber', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_acumbamail_settings_tab', 10, 1);
function adfoin_acumbamail_settings_tab($providers) {
    $providers['acumbamail'] = __('Acumbamail', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_acumbamail_settings_view', 10, 1);
function adfoin_acumbamail_settings_view($current_tab) {
    if ($current_tab !== 'acumbamail') return;

    $title = __('Acumbamail', 'advanced-form-integration');
    $key = 'acumbamail';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Get your API key from your Acumbamail account settings.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_acumbamail_credentials', 'adfoin_get_acumbamail_credentials');
function adfoin_get_acumbamail_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('acumbamail'));
}

add_action('wp_ajax_adfoin_save_acumbamail_credentials', 'adfoin_save_acumbamail_credentials');
function adfoin_save_acumbamail_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'acumbamail') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('acumbamail', $data);
    }

    wp_send_json_success();
}

function adfoin_acumbamail_credentials_list() {
    foreach (adfoin_read_credentials('acumbamail') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_acumbamail_lists', 'adfoin_get_acumbamail_lists');
function adfoin_get_acumbamail_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $listsRes = adfoin_acumbamail_request('getLists/', 'POST', [], [], $cred_id);

    if (is_wp_error($listsRes)) wp_send_json_error();

    if (is_array($listsRes)) {
        $lists = [];
        foreach ($listsRes as $id => $list) {
            $lists[$id] = $list['name'];
        }
        wp_send_json_success($lists);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_acumbamail_job_queue', 'adfoin_acumbamail_job_queue', 10, 1);
function adfoin_acumbamail_job_queue($data) {
    adfoin_acumbamail_send_data($data['record'], $data['posted_data']);
}

function adfoin_acumbamail_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'];
    $list_id = $data['listId'];
    unset($data['credId'], $data['listId']);

    $fields = [];
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $fields[$key] = $parsed;
        }
    }

    $finalData = []; // Prepare merge fields
    foreach ($fields as $key => $value) {
        if (!in_array($key, ['email', 'name', 'surname'])) {
            $finalData[$key] = $value;
        }
    }

    $doubleOptin = $fields['double_optin'] ? $fields['double_optin'] : false;

    $body = [
        'list_id'           => $list_id,
        'welcome_email'     => 1,
        'update_subscriber' => 1,
        'double_optin'      => $doubleOptin ? 1 : 0,
        'merge_fields'      => $fields
    ];

    adfoin_acumbamail_request('addSubscriber/', 'POST', $body, $record, $cred_id);
}

function adfoin_acumbamail_request($endpoint, $method = 'POST', $body = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('acumbamail', $cred_id);

    $base_url = 'https://acumbamail.com/api/1/';
    $url = $base_url . ltrim($endpoint, '/');
    $body['auth_token'] = $credentials['apiKey'];

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => $body,
    ];

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    if (is_wp_error($response)) {
        return $response;
    }

    $responseBody = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($responseBody['error'])) {
        return new WP_Error('api_error', $responseBody['error']);
    }

    return $responseBody;
}

add_action('adfoin_action_fields', 'adfoin_acumbamail_action_fields');
function adfoin_acumbamail_action_fields() {
?>
<script type="text/template" id="acumbamail-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
            </th>
            <td scope="row">

            </td>
        </tr>
        <tr class="alternate" valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Acumbamail Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_acumbamail_credentials_list(); ?>
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