<?php

add_filter('adfoin_action_providers', 'adfoin_courrielleur_actions', 10, 1);
function adfoin_courrielleur_actions($actions) {
    $actions['courrielleur'] = [
        'title' => __('Courrielleur', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add Subscriber', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_courrielleur_settings_tab', 10, 1);
function adfoin_courrielleur_settings_tab($providers) {
    $providers['courrielleur'] = __('Courrielleur', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_courrielleur_settings_view', 10, 1);
function adfoin_courrielleur_settings_view($current_tab) {
    if ($current_tab !== 'courrielleur') return;

    $title = __('Courrielleur', 'advanced-form-integration');
    $key = 'courrielleur';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiUrl', 'label' => __('API domain', 'advanced-form-integration'), 'hidden' => false],
            ['key' => 'apiToken', 'label' => __('API Token', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your Courrielleur API URL and a Personal Access Token.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_courrielleur_credentials', 'adfoin_get_courrielleur_credentials');
function adfoin_get_courrielleur_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('courrielleur'));
}

add_action('wp_ajax_adfoin_save_courrielleur_credentials', 'adfoin_save_courrielleur_credentials');
function adfoin_save_courrielleur_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'courrielleur') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('courrielleur', $data);
    }

    wp_send_json_success();
}

function adfoin_courrielleur_credentials_list() {
    foreach (adfoin_read_credentials('courrielleur') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_courrielleur_lists', 'adfoin_get_courrielleur_lists');
function adfoin_get_courrielleur_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $response = adfoin_courrielleur_request('email-lists', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['data'])) {
        $lists = wp_list_pluck($body['data'], 'name', 'uuid');
        wp_send_json_success($lists);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_courrielleur_job_queue', 'adfoin_courrielleur_job_queue', 10, 1);
function adfoin_courrielleur_job_queue($data) {
    adfoin_courrielleur_send_data($data['record'], $data['posted_data']);
}

function adfoin_courrielleur_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'] ?? '';
    $list_id = $data['listId'] ?? '';

    $email = adfoin_get_parsed_values($data['email'] ?? '', $posted_data);
    $first_name = adfoin_get_parsed_values($data['first_name'] ?? '', $posted_data);
    $last_name = adfoin_get_parsed_values($data['last_name'] ?? '', $posted_data);

    $body = [
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
    ];

    adfoin_courrielleur_request("email-lists/{$list_id}/subscribers", 'POST', $body, $record, $cred_id);
}

function adfoin_courrielleur_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('courrielleur', $cred_id);
    $base_url = rtrim($credentials['apiUrl'], '/') . '/api/';
    if (strpos($base_url, 'https://') === false && strpos($base_url, 'http://') === false) {
        $base_url = 'https://' . $base_url;
    }

    $api_token = $credentials['apiToken'] ?? '';

    $url = $base_url . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_token,
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

add_action('adfoin_action_fields', 'adfoin_courrielleur_action_fields');
function adfoin_courrielleur_action_fields() {
?>
<script type="text/template" id="courrielleur-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('Courrielleur Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_courrielleur_credentials_list(); ?>
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
?>