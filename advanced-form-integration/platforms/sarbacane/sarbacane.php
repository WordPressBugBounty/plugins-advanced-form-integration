<?php
add_filter('adfoin_action_providers', 'adfoin_sarbacane_actions', 10, 1);
function adfoin_sarbacane_actions($actions) {
    $actions['sarbacane'] = [
        'title' => __('Sarbacane', 'advanced-form-integration'),
        'tasks' => ['add_subscriber' => __('Add Subscriber', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_sarbacane_settings_tab', 10, 1);
function adfoin_sarbacane_settings_tab($providers) {
    $providers['sarbacane'] = __('Sarbacane', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_sarbacane_settings_view', 10, 1);
function adfoin_sarbacane_settings_view($current_tab) {
    if ($current_tab !== 'sarbacane') return;

    $title = __('Sarbacane', 'advanced-form-integration');
    $key = 'sarbacane';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'accountId', 'label' => __('Account ID', 'advanced-form-integration')],
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Go to Campaigns > Settings > API & Webhooks. Create an API Key', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_sarbacane_credentials', 'adfoin_get_sarbacane_credentials');
function adfoin_get_sarbacane_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('sarbacane'));
}

add_action('wp_ajax_adfoin_save_sarbacane_credentials', 'adfoin_save_sarbacane_credentials');
function adfoin_save_sarbacane_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'sarbacane') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('sarbacane', $data);
    }

    wp_send_json_success();
}

function adfoin_sarbacane_credentials_list() {
    foreach (adfoin_read_credentials('sarbacane') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_sarbacane_lists', 'adfoin_get_sarbacane_lists');
function adfoin_get_sarbacane_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $listsRes = adfoin_sarbacane_request('lists?limit=1000', 'GET', [], [], $cred_id);

    if (is_wp_error($listsRes)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($listsRes), true);

    if (!empty($body)) {
        $lists = wp_list_pluck($body, 'name', 'id');
        wp_send_json_success($lists);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_sarbacane_job_queue', 'adfoin_sarbacane_job_queue', 10, 1);
function adfoin_sarbacane_job_queue($data) {
    adfoin_sarbacane_send_data($data['record'], $data['posted_data']);
}

function adfoin_sarbacane_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic(isset($record_data['action_data']['cl']) ? $record_data['action_data']['cl'] : array(), $posted_data)) return;

    $data    = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $list_id = isset($data['listId']) ? $data['listId'] : '';

    $email  = adfoin_get_parsed_values(isset($data['email']) ? $data['email'] : '', $posted_data);
    $phone = adfoin_get_parsed_values(isset($data['phone']) ? $data['phone'] : '', $posted_data);

    $contact_data = array_filter(array(
        'email'  => $email,
        'phone' => $phone
    ));

    adfoin_sarbacane_request("lists/{$list_id}/contacts/upsert", 'POST', $contact_data, $record, $cred_id);
}

function adfoin_sarbacane_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('sarbacane', $cred_id);
    $account_id = isset($credentials['accountId']) ? $credentials['accountId'] : '';
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';

    $base_url = 'https://api.sarbacane.com/v1/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = array(
        'method' => $method,
        'timeout' => 30,
        'headers' => array(
            'apiKey'        => $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'accountId'     => $account_id
        ),
    );

    if (in_array($method, array('POST', 'PUT'))) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('adfoin_action_fields', 'adfoin_sarbacane_action_fields');
function adfoin_sarbacane_action_fields() {
?>
<script type="text/template" id="sarbacane-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?></th>
            <td></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Sarbacane Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_sarbacane_credentials_list(); ?>
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