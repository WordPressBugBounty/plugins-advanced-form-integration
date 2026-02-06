<?php
add_filter('adfoin_action_providers', 'adfoin_laposta_actions', 10, 1);
function adfoin_laposta_actions($actions) {
    $actions['laposta'] = [
        'title' => __('Laposta', 'advanced-form-integration'),
        'tasks' => ['add_subscriber' => __('Add Subscriber', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_laposta_settings_tab', 10, 1);
function adfoin_laposta_settings_tab($providers) {
    $providers['laposta'] = __('Laposta', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_laposta_settings_view', 10, 1);
function adfoin_laposta_settings_view($current_tab) {
    if ($current_tab !== 'laposta') return;

    $title = __('Laposta', 'advanced-form-integration');
    $key = 'laposta';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Get your API key from Laposta dashboard > Settings > API.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_laposta_credentials', 'adfoin_get_laposta_credentials');
function adfoin_get_laposta_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('laposta'));
}

add_action('wp_ajax_adfoin_save_laposta_credentials', 'adfoin_save_laposta_credentials');
function adfoin_save_laposta_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'laposta') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('laposta', $data);
    }

    wp_send_json_success();
}

function adfoin_laposta_credentials_list() {
    foreach (adfoin_read_credentials('laposta') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_laposta_lists', 'adfoin_get_laposta_lists');
function adfoin_get_laposta_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $listsRes = adfoin_laposta_request('list', 'GET', [], [], $cred_id);

    if (is_wp_error($listsRes)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($listsRes), true);

    if (isset($body['data']) && is_array($body['data'])) {
        $lists = [];
        foreach ($body['data'] as $item) {
            $lists[$item['list']['list_id']] = $item['list']['name'];
        }
        wp_send_json_success($lists);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_laposta_job_queue', 'adfoin_laposta_job_queue', 10, 1);
function adfoin_laposta_job_queue($data) {
    adfoin_laposta_send_data($data['record'], $data['posted_data']);
}

function adfoin_laposta_send_data($record, $posted_data) {
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

    $body = [
        'list_id' => $list_id,
        'email'   => isset( $fields['email'] ) ? $fields['email'] : '',
        'ip' => isset( $fields['ip'] ) ? $fields['ip'] : '',
        'options' => ['upsert' => true]
    ];

    unset($fields['email'], $fields['ip']);

    if (isset($fields['firstname'])) {
        $fields['voornaam'] = $fields['firstname'];
    }

    if (isset($fields['lastname'])) {
        $fields['achternaam'] = $fields['lastname'];
    }

    if (!empty($fields)) {
        $body['custom_fields'] = $fields;
    }

    adfoin_laposta_request('member', 'POST', $body, $record, $cred_id);
}

function adfoin_laposta_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('laposta', $cred_id);
    $url = 'https://api.laposta.org/v2/' . ltrim($endpoint, '/');

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($credentials['apiKey'] . ':'),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json'
        ],
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

add_action('adfoin_action_fields', 'adfoin_laposta_action_fields');
function adfoin_laposta_action_fields() {
?>
<script type="text/template" id="laposta-action-template">
    <table class="form-table">
    <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
            </th>
            <td scope="row">
            
            </td>
        </tr>
        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Laposta Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_laposta_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('List', 'advanced-form-integration'); ?></th>
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
