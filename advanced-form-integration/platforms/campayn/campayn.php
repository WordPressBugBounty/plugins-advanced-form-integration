<?php

add_filter('adfoin_action_providers', 'adfoin_campayn_actions', 10, 1);
function adfoin_campayn_actions($actions) {
    $actions['campayn'] = [
        'title' => __('Campayn', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add Contact to List', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_campayn_settings_tab', 10, 1);
function adfoin_campayn_settings_tab($providers) {
    $providers['campayn'] = __('Campayn', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_campayn_settings_view', 10, 1);
function adfoin_campayn_settings_view($current_tab) {
    if ($current_tab !== 'campayn') return;

    $title = __('Campayn', 'advanced-form-integration');
    $key = 'campayn';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your Campayn API Key. You can find this in your Campayn account settings.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_campayn_credentials', 'adfoin_get_campayn_credentials');
function adfoin_get_campayn_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('campayn'));
}

add_action('wp_ajax_adfoin_save_campayn_credentials', 'adfoin_save_campayn_credentials');
function adfoin_save_campayn_credentials() {
    if (!adfoin_verify_nonce()) return;

    if (isset($_POST['platform']) && $_POST['platform'] === 'campayn') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('campayn', $data);
    }

    wp_send_json_success();
}

function adfoin_campayn_credentials_list() {
    foreach (adfoin_read_credentials('campayn') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_campayn_lists', 'adfoin_get_campayn_lists');
function adfoin_get_campayn_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $response = adfoin_campayn_request('lists', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $http_code = wp_remote_retrieve_response_code($response);

    if ($http_code === 200 && is_array($body)) {
        $lists = [];
        foreach($body as $list_item) {
            if (isset($list_item['id']) && isset($list_item['list_name'])) {
                $lists[$list_item['id']] = $list_item['list_name'];
            }
        }
        wp_send_json_success($lists);
    }

    wp_die();
}

add_action('adfoin_campayn_job_queue', 'adfoin_campayn_job_queue', 10, 1);
function adfoin_campayn_job_queue($data) {
    adfoin_campayn_send_data($data['record'], $data['posted_data']);
}

function adfoin_campayn_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $list_id = isset($data['listId']) ? $data['listId'] : '';

    // Map fields from form data
    $email      = adfoin_get_parsed_values(isset($data['email']) ? $data['email'] : '', $posted_data);
    $first_name = adfoin_get_parsed_values(isset($data['first_name']) ? $data['first_name'] : '', $posted_data);
    $last_name  = adfoin_get_parsed_values(isset($data['last_name']) ? $data['last_name'] : '', $posted_data);
    

    $custom_fields = isset($data['custom_fields']) ? adfoin_get_parsed_values($data['custom_fields'], $posted_data) : [];
    if (is_string($custom_fields)) {
        $custom_fields = json_decode($custom_fields, true);
        if (!is_array($custom_fields)) $custom_fields = [];
    }

    $payload = array(
        'email'      => $email,
        'first_name' => $first_name,
        'last_name'  => $last_name
    );

    if (!empty($phones))        $payload['phones'] = $phones;
    if (!empty($sites))         $payload['sites'] = $sites;
    if (!empty($social))        $payload['social'] = $social;
    if (!empty($custom_fields)) $payload['custom_fields'] = $custom_fields;

    // Remove empty values
    $payload = array_filter($payload, function($v) {
        return !is_null($v) && $v !== '';
    });

    $endpoint = "lists/{$list_id}/contacts.json";
    adfoin_campayn_request($endpoint, 'POST', $payload, $record, $cred_id);
}

function adfoin_campayn_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('campayn', $cred_id);
    $api_key = $credentials['apiKey'];
    $base_url = 'https://campayn.com/api/v1/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'TRUEREST apikey=' . $api_key,
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

add_action('adfoin_action_fields', 'adfoin_campayn_action_fields');
function adfoin_campayn_action_fields() {
?>
<script type="text/template" id="campayn-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('Campayn Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_campayn_credentials_list(); ?>
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