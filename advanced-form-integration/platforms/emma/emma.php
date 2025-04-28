<?php
add_filter('adfoin_action_providers', 'adfoin_emma_actions', 10, 1);
function adfoin_emma_actions($actions) {
    $actions['emma'] = [
        'title' => __('Emma', 'advanced-form-integration'),
        'tasks' => ['add_contact' => __('Add Contact to Group', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_emma_settings_tab', 10, 1);
function adfoin_emma_settings_tab($providers) {
    $providers['emma'] = __('Emma', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_emma_settings_view', 10, 1);
function adfoin_emma_settings_view($current_tab) {
    if ($current_tab !== 'emma') return;

    $title = __('Emma', 'advanced-form-integration');
    $key = 'emma';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'publicKey', 'label' => __('Public API Key', 'advanced-form-integration')],
            ['key' => 'privateKey', 'label' => __('Private API Key', 'advanced-form-integration')],
            ['key' => 'accountId', 'label' => __('Account ID', 'advanced-form-integration')]
        ]
    ]);
    $instructions = __('Get your Emma API credentials from your account settings.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_emma_credentials', 'adfoin_get_emma_credentials');
function adfoin_get_emma_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('emma'));
}

add_action('wp_ajax_adfoin_save_emma_credentials', 'adfoin_save_emma_credentials');
function adfoin_save_emma_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'emma') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('emma', $data);
    }

    wp_send_json_success();
}

function adfoin_emma_credentials_list() {
    foreach (adfoin_read_credentials('emma') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_emma_groups', 'adfoin_get_emma_groups');
function adfoin_get_emma_groups() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $credentials = adfoin_get_credentials_by_id('emma', $cred_id);

    $account_id = $credentials['accountId'] ?? '';
    $url = "https://api.e2ma.net/{$account_id}/groups";

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($credentials['publicKey'] . ':' . $credentials['privateKey']),
            'Accept'        => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $groups = wp_list_pluck($body, 'group_name', 'group_id');

    wp_send_json_success($groups);
}

add_action('adfoin_emma_job_queue', 'adfoin_emma_job_queue', 10, 1);
function adfoin_emma_job_queue($data) {
    adfoin_emma_send_data($data['record'], $data['posted_data']);
}

function adfoin_emma_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'];
    $group_id = $data['groupId'];
    unset($data['credId'], $data['groupId']);

    $contact = [];
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $contact[$key] = $parsed;
        }
    }

    $body = [
        'group_ids' => [$group_id],
        'fields'    => $contact
    ];

    adfoin_emma_request('members/signup', 'POST', $body, $record, $cred_id);
}

function adfoin_emma_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('emma', $cred_id);
    $account_id = $credentials['accountId'] ?? '';
    $url = "https://api.e2ma.net/{$account_id}/{$endpoint}";

    $args = [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($credentials['publicKey'] . ':' . $credentials['privateKey']),
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode($data)
    ];

    $response = wp_remote_request($url, $args);
    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'adfoin_action_fields', 'adfoin_emma_action_fields' );

function adfoin_emma_action_fields() {
?>
<script type="text/template" id="emma-action-template">
    <table class="form-table">
        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td><label><?php _e('Emma Account', 'advanced-form-integration'); ?></label></td>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getGroups">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_emma_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td><label><?php _e('Group', 'advanced-form-integration'); ?></label></td>
            <td>
                <select name="fieldData[groupId]" v-model="fielddata.groupId" required>
                    <option value=""><?php _e('Select Group...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.groups" :value="id">{{ name }}</option>
                </select>
                <div class="spinner" v-bind:class="{ 'is-active': groupLoading }"></div>
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
