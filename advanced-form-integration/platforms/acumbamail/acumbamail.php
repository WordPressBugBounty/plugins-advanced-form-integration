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
    $arguments = wp_json_encode([
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
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('acumbamail'));
}

add_action('wp_ajax_adfoin_save_acumbamail_credentials', 'adfoin_save_acumbamail_credentials');
function adfoin_save_acumbamail_credentials() {
    adfoin_verify_nonce();

    if ($_POST['platform'] === 'acumbamail') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('acumbamail', $data);
    }

    wp_send_json_success();
}

add_action('wp_ajax_adfoin_get_acumbamail_lists', 'adfoin_get_acumbamail_lists');
function adfoin_get_acumbamail_lists() {
    adfoin_verify_nonce();

    $cred_id = isset($_POST['credId']) ? sanitize_text_field(wp_unslash($_POST['credId'])) : '';
    $lists   = adfoin_acumbamail_lists($cred_id);

    if (is_wp_error($lists)) {
        wp_send_json_error(sprintf(__('Could not load Acumbamail lists: %s', 'advanced-form-integration'), $lists->get_error_message()));
    }

    wp_send_json_success($lists);
}

add_action('wp_ajax_adfoin_get_acumbamail_fields', 'adfoin_get_acumbamail_fields');
function adfoin_get_acumbamail_fields() {
    adfoin_verify_nonce();

    $cred_id = isset($_POST['credId']) ? sanitize_text_field(wp_unslash($_POST['credId'])) : '';
    $list_id = isset($_POST['listId']) ? sanitize_text_field(wp_unslash($_POST['listId'])) : '';
    $fields  = adfoin_acumbamail_merge_fields($cred_id, $list_id);

    if (is_wp_error($fields)) {
        wp_send_json_error(sprintf(__('Could not load the list fields: %s', 'advanced-form-integration'), $fields->get_error_message()));
    }

    $data = [];

    foreach ($fields as $tag => $type) {
        $data[] = ['key' => $tag, 'value' => $tag, 'type' => $type];
    }

    wp_send_json_success($data);
}

/*
 * The account's lists as ID => name, or WP_Error.
 */
function adfoin_acumbamail_lists($cred_id) {
    $response = adfoin_acumbamail_request('getLists/', [], $cred_id);

    if (is_wp_error($response)) return $response;

    $body  = json_decode(wp_remote_retrieve_body($response), true);
    $lists = [];

    foreach (is_array($body) ? $body : [] as $id => $list) {
        $lists[$id] = is_array($list) && isset($list['name']) ? $list['name'] : $id;
    }

    return $lists;
}

/*
 * A list's merge tags as tag => type, or WP_Error. Acumbamail allows 10
 * calls a minute, so results are cached briefly.
 */
function adfoin_acumbamail_merge_fields($cred_id, $list_id) {
    if (!$list_id) return new WP_Error('acumbamail_no_list', __('Select a list first.', 'advanced-form-integration'));

    $cache_key = 'adfoin_acumbamail_fields_' . md5($cred_id . '|' . $list_id);
    $cached    = get_transient($cache_key);

    if (is_array($cached)) return $cached;

    $response = adfoin_acumbamail_request('getMergeFields/', ['list_id' => $list_id], $cred_id);

    if (is_wp_error($response)) return $response;

    $body   = json_decode(wp_remote_retrieve_body($response), true);
    $fields = [];

    foreach (is_array($body) ? $body : [] as $tag => $type) {
        $fields[$tag] = is_string($type) ? $type : '';
    }

    set_transient($cache_key, $fields, 5 * MINUTE_IN_SECONDS);

    return $fields;
}

add_action('adfoin_acumbamail_job_queue', 'adfoin_acumbamail_job_queue', 10, 1);
function adfoin_acumbamail_job_queue($data) {
    adfoin_acumbamail_send_data($data['record'], $data['posted_data']);
}

/*
 * Parsed, trimmed value of a mapped field.
 */
function adfoin_acumbamail_value($data, $key, $posted_data) {
    if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') return '';

    return trim((string) adfoin_get_parsed_values($data[$key], $posted_data));
}

/*
 * Email, account and list checks shared by every task. Returns the email,
 * or false after logging why the submission was skipped.
 */
function adfoin_acumbamail_check($data, $posted_data, $record) {
    $email = adfoin_acumbamail_value($data, 'email', $posted_data);

    if (empty($data['credId'])) {
        adfoin_acumbamail_log_skipped($record, __('No Acumbamail account is selected in the integration.', 'advanced-form-integration'));
        return false;
    }

    if (empty($data['listId'])) {
        adfoin_acumbamail_log_skipped($record, __('No Acumbamail list is selected in the integration.', 'advanced-form-integration'));
        return false;
    }

    if (!is_email($email)) {
        adfoin_acumbamail_log_skipped($record, $email === '' ? __('Email is empty.', 'advanced-form-integration') : sprintf(__('Invalid email: %s', 'advanced-form-integration'), $email));
        return false;
    }

    return $email;
}

/*
 * Merge fields for the free integration: the email only. Integrations saved
 * with First/Last Name mapped keep sending them ("name"/"surname" tags).
 */
function adfoin_acumbamail_base_fields($data, $posted_data, $email) {
    $fields = ['email' => $email];
    $names  = ['name' => 'nameTag', 'surname' => 'surnameTag'];

    foreach ($names as $key => $tag_key) {
        $value = adfoin_acumbamail_value($data, $key, $posted_data);
        $tag   = isset($data[$tag_key]) && $data[$tag_key] !== '' ? $data[$tag_key] : $key;

        if ($value !== '') {
            $fields[$tag] = $value;
        }
    }

    return $fields;
}

function adfoin_acumbamail_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data  = isset($record_data['field_data']) ? $record_data['field_data'] : [];
    $email = adfoin_acumbamail_check($data, $posted_data, $record);

    if (!$email) return;

    adfoin_acumbamail_add_subscriber($data, adfoin_acumbamail_base_fields($data, $posted_data, $email), $record);
}

/*
 * Add (or update) a subscriber. Shared with Acumbamail [PRO].
 */
function adfoin_acumbamail_add_subscriber($data, $merge_fields, $record) {
    $body = [
        'list_id'           => $data['listId'],
        'merge_fields'      => $merge_fields,
        'double_optin'      => !empty($data['doubleOptin']) ? 1 : 0,
        // Older integrations had no setting and always updated existing subscribers.
        'update_subscriber' => !isset($data['updateSubscriber']) || $data['updateSubscriber'] ? 1 : 0,
        'complete_json'     => 1,
    ];

    return adfoin_acumbamail_request('addSubscriber/', $body, $data['credId'], $record);
}

/*
 * Readable error from an Acumbamail response.
 */
function adfoin_acumbamail_error_message($response) {
    if (is_wp_error($response)) return $response->get_error_message();

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = trim(wp_strip_all_tags(wp_remote_retrieve_body($response)));
    $body = json_decode($raw, true);

    if (is_array($body)) {
        $message = isset($body['error']) ? $body['error'] : (isset($body['message']) ? $body['message'] : wp_json_encode($body));
    } elseif (is_string($body)) {
        $message = $body;
    } else {
        $message = $raw;
    }

    if (is_array($message)) $message = wp_json_encode($message);

    if ($code === 401) {
        $hint = __('check the API key. The Acumbamail API also needs a paid plan (Basic or higher).', 'advanced-form-integration');
        return trim('Unauthorized: ' . $hint . ($message !== '' && $message !== 'Unauthorized' ? ' (' . $message . ')' : ''));
    }

    if ($message === '') {
        $message = trim($code . ' ' . wp_remote_retrieve_response_message($response));
    }

    return $message;
}

function adfoin_acumbamail_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('acumbamail_not_sent', $message), 'https://acumbamail.com/api/1/', ['method' => 'POST'], $record);
}

/*
 * POST to an Acumbamail API function. Returns the HTTP response, or WP_Error
 * for missing credentials, transport errors and non-2xx responses. Logs
 * once when a record is given.
 */
function adfoin_acumbamail_request($endpoint, $body = [], $cred_id = '', $record = []) {
    $credentials = adfoin_get_credentials_by_id('acumbamail', $cred_id);
    $api_key     = is_array($credentials) && !empty($credentials['apiKey']) ? trim($credentials['apiKey']) : '';
    $url         = 'https://acumbamail.com/api/1/' . ltrim($endpoint, '/');

    $args = [
        'method'  => 'POST',
        'timeout' => 30,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => array_merge($body, ['auth_token' => $api_key, 'response_type' => 'json']),
    ];

    if ($api_key === '') {
        $response = new WP_Error('acumbamail_no_credentials', __('The Acumbamail account was not found. Select an account in the integration.', 'advanced-form-integration'));
    } else {
        $response = wp_remote_request($url, $args);
    }

    $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);

    if (!empty($record)) {
        if ($code >= 300) {
            $response['response']['message'] = adfoin_acumbamail_error_message($response);
        } elseif ($code) {
            $result = json_decode(wp_remote_retrieve_body($response), true);
            $id     = is_array($result) && isset($result['id']) ? $result['id'] : (is_numeric($result) ? $result : '');
            $response['response']['message'] = $id !== '' ? sprintf('OK (subscriber #%s)', $id) : 'OK';
        }

        adfoin_add_to_log($response, $url, $args, $record);
    }

    if (is_wp_error($response)) return $response;

    if ($code < 200 || $code >= 300) {
        return new WP_Error('acumbamail_http_error', adfoin_acumbamail_error_message($response));
    }

    return $response;
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
                <select name="fieldData[credId]" v-model="fielddata.credId">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                </select>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=acumbamail' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Mailing List', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[listId]" v-model="fielddata.listId" required>
                    <option value=""><?php _e('Select List...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in lists" :value="id">{{ name }}</option>
                </select>
                <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                <p class="description" v-if="listError" style="color: #b32d2e;">{{ listError }}</p>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php esc_attr_e('Options', 'advanced-form-integration'); ?></th>
            <td>
                <label><input type="checkbox" name="fieldData[doubleOptin]" value="1" v-model="fielddata.doubleOptin"> <?php esc_html_e('Double opt-in (send a confirmation email first)', 'advanced-form-integration'); ?></label><br>
                <label><input type="checkbox" name="fieldData[updateSubscriber]" value="1" v-model="fielddata.updateSubscriber"> <?php esc_html_e('Update the subscriber if already in the list', 'advanced-form-integration'); ?></label>
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
