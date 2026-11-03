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
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiToken', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
            ['key' => 'baseUrl', 'label' => __('Keila URL (optional)', 'advanced-form-integration')],
        ]
    ]);
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        __('In your Keila project, open the API Keys page, create a key and paste it here.', 'advanced-form-integration'),
        __('Self-hosted Keila only: enter your Keila address, e.g. https://keila.example.com. Leave it empty for app.keila.io.', 'advanced-form-integration')
    );

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_keila_credentials', 'adfoin_get_keila_credentials');
function adfoin_get_keila_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('keila'));
}

add_action('wp_ajax_adfoin_save_keila_credentials', 'adfoin_save_keila_credentials');
function adfoin_save_keila_credentials() {

    adfoin_verify_nonce();

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

    $data    = $record_data['field_data'];
    $cred_id = $data['credId'] ?? '';
    $email   = adfoin_keila_parse_email($data, $posted_data, $record);

    if (!$email) return;

    $contact = ['email' => $email];

    foreach (['first_name', 'last_name'] as $key) {
        $value = trim((string) adfoin_get_parsed_values($data[$key] ?? '', $posted_data));

        if ($value !== '') {
            $contact[$key] = $value;
        }
    }

    adfoin_keila_upsert_contact($contact, [], $record, $cred_id);
}

/*
 * Returns the sanitized email, or '' after logging why the submission was skipped.
 */
function adfoin_keila_parse_email($data, $posted_data, $record) {
    $raw   = trim((string) adfoin_get_parsed_values($data['email'] ?? '', $posted_data));
    $email = sanitize_email($raw);

    if (!$email) {
        adfoin_keila_log_skipped(
            $record,
            $raw === ''
                ? __('Not sent: the mapped email field is empty.', 'advanced-form-integration')
                : sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw)
        );
    }

    return $email;
}

function adfoin_keila_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('keila_not_sent', $message), 'contacts', ['method' => 'POST'], $record);
}

/*
 * Creates the contact, or updates it when the email already exists in Keila.
 * $custom_data is merged into the existing contact's custom data on update.
 * Each submission gets one log entry: the first failed request, or the last one.
 */
function adfoin_keila_upsert_contact($contact, $custom_data, $record, $cred_id) {
    $create = $contact;

    if ($custom_data) {
        $create['data'] = $custom_data;
    }

    $response = adfoin_keila_request('contacts', 'POST', ['data' => $create], [], $cred_id);

    if (!adfoin_keila_is_duplicate_email($response)) {
        adfoin_keila_log($response, 'contacts', 'POST', ['data' => $create], $record, $cred_id);
        return $response;
    }

    $id       = rawurlencode($contact['email']);
    $requests = [];
    $update   = $contact;

    unset($update['email']);

    if ($update) {
        $requests[] = ["contacts/{$id}?id_type=email", ['data' => $update]];
    }

    if ($custom_data) {
        $requests[] = ["contacts/{$id}/data?id_type=email", ['data' => $custom_data]];
    }

    if (!$requests) {
        // Only the email was mapped and the contact exists: nothing to change.
        $response['response']['code'] = 200;
        adfoin_keila_log($response, 'contacts', 'POST', ['data' => $create], $record, $cred_id, __('OK (contact already exists)', 'advanced-form-integration'));
        return $response;
    }

    foreach ($requests as $request) {
        list($endpoint, $body) = $request;

        $response = adfoin_keila_request($endpoint, 'PATCH', $body, [], $cred_id);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 300) {
            break;
        }
    }

    adfoin_keila_log($response, $endpoint, 'PATCH', $body, $record, $cred_id, __('OK (existing contact updated)', 'advanced-form-integration'));

    return $response;
}

function adfoin_keila_is_duplicate_email($response) {
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 400) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    foreach ($body['errors'] ?? [] as $error) {
        if (substr($error['pointer'] ?? '', -6) === '/email' && strpos($error['detail'] ?? '', 'already been taken') !== false) {
            return true;
        }
    }

    return false;
}

/*
 * Keila accounts are on app.keila.io unless a self-hosted URL is saved with the account.
 */
function adfoin_keila_base_url($credentials) {
    $url = trim($credentials['baseUrl'] ?? '');

    if ($url === '') {
        return 'https://app.keila.io/api/v1/';
    }

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }

    $url = preg_replace('#/+(api(/v1)?/*)?$#i', '', $url);

    return $url . '/api/v1/';
}

function adfoin_keila_build_request($endpoint, $method, $data, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('keila', $cred_id);
    $api_token   = $credentials['apiToken'] ?? '';

    if (!$api_token) {
        return new WP_Error('keila_no_account', __('No Keila account is selected, or the account has no API key. Check the integration and AFI Settings > Keila.', 'advanced-form-integration'));
    }

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type' => 'application/json'
        ]
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $args['body'] = wp_json_encode($data);
    }

    return [adfoin_keila_base_url($credentials) . ltrim($endpoint, '/'), $args];
}

function adfoin_keila_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $request = adfoin_keila_build_request($endpoint, $method, $data, $cred_id);

    if (is_wp_error($request)) {
        if (!empty($record)) {
            adfoin_add_to_log($request, $endpoint, ['method' => $method, 'body' => $data], $record);
        }

        return $request;
    }

    list($url, $args) = $request;

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, adfoin_keila_args_for_log($args), $record);
    }

    return $response;
}

function adfoin_keila_args_for_log($args) {
    $args['headers']['Authorization'] = 'Bearer ' . substr($args['headers']['Authorization'], 7, 4) . '****';

    return $args;
}

/*
 * Logs a response that was fetched without a record (see adfoin_keila_upsert_contact()).
 */
function adfoin_keila_log($response, $endpoint, $method, $data, $record, $cred_id, $message = '') {
    $request = adfoin_keila_build_request($endpoint, $method, $data, $cred_id);
    $url     = is_wp_error($request) ? $endpoint : $request[0];
    $args    = is_wp_error($request) ? ['method' => $method, 'body' => $data] : adfoin_keila_args_for_log($request[1]);

    if ($message && !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
        $response['response']['message'] = $message;
    }

    adfoin_add_to_log($response, $url, $args, $record);
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=keila' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>

        <?php adfoin_pro_feature_notice('add_contact', 'Keila [PRO]', 'External ID, status and custom data'); ?>
    </table>
</script>
<?php
}
?>