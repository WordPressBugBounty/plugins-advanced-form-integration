<?php

// Register Mailmodo action provider
add_filter('adfoin_action_providers', 'adfoin_mailmodo_actions', 10, 1);
function adfoin_mailmodo_actions($actions) {
    $actions['mailmodo'] = [
        'title' => __('Mailmodo', 'advanced-form-integration'),
        'tasks' => [
            'subscribe' => __('Add Contact to List', 'advanced-form-integration') // Renamed task for clarity
        ]
    ];
    return $actions;
}

// Register Mailmodo settings tab
add_filter('adfoin_settings_tabs', 'adfoin_mailmodo_settings_tab', 10, 1);
function adfoin_mailmodo_settings_tab($providers) {
    $providers['mailmodo'] = __('Mailmodo', 'advanced-form-integration');
    return $providers;
}

// Display Mailmodo settings view
add_action('adfoin_settings_view', 'adfoin_mailmodo_settings_view', 10, 1);
function adfoin_mailmodo_settings_view($current_tab) {
    if ($current_tab !== 'mailmodo') return;

    $title = __('Mailmodo', 'advanced-form-integration');
    $key = 'mailmodo';
    // Only API Key is needed for Mailmodo
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            // Renamed key to apiKey for consistency within the plugin's credential storage
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your Mailmodo API Key. You can generate one in your Mailmodo account under Settings -> API Keys.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

// AJAX handler to get saved Mailmodo credentials
add_action('wp_ajax_adfoin_get_mailmodo_credentials', 'adfoin_get_mailmodo_credentials');
function adfoin_get_mailmodo_credentials() {
    adfoin_verify_nonce();
    // Use the generic credential reading function
    wp_send_json_success(adfoin_read_credentials('mailmodo'));
}

// AJAX handler to save Mailmodo credentials
add_action('wp_ajax_adfoin_save_mailmodo_credentials', 'adfoin_save_mailmodo_credentials');
function adfoin_save_mailmodo_credentials() {

    adfoin_verify_nonce();

    if ($_POST['platform'] === 'mailmodo') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        // Use the generic credential saving function
        adfoin_save_credentials('mailmodo', $data);
    }

    wp_send_json_success();
}

// Helper function to generate Mailmodo account options (for the action settings dropdown)
function adfoin_mailmodo_credentials_list() {
    // Use the generic credential reading function
    foreach (adfoin_read_credentials('mailmodo') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_mailmodo_lists', 'adfoin_get_mailmodo_lists');

function adfoin_get_mailmodo_lists() {
    adfoin_verify_nonce();

    $cred_id  = isset($_POST['credId']) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_mailmodo_request('getAllContactLists', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $code          = (int) wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (401 === $code || 403 === $code) {
        wp_send_json_error(__('Mailmodo rejected the API key. Check it under AFI Settings > Mailmodo.', 'advanced-form-integration'));
    }

    if ($code >= 300) {
        $reason = is_array($response_body) ? ($response_body['message'] ?? $response_body['Error'] ?? '') : '';
        wp_send_json_error(sprintf(__('Could not load lists (HTTP %1$d) %2$s', 'advanced-form-integration'), $code, $reason));
    }

    $lists = [];

    foreach ($response_body['listDetails'] ?? [] as $list) {
        if (!empty($list['name'])) {
            $lists[$list['name']] = $list['name'];
        }
    }

    if (!$lists) {
        wp_send_json_error(__('No contact lists found in this Mailmodo account. Create one in Mailmodo first.', 'advanced-form-integration'));
    }

    wp_send_json_success($lists);
}

// Hook into the job queue for Mailmodo actions
add_action('adfoin_mailmodo_job_queue', 'adfoin_mailmodo_job_queue', 10, 1);
function adfoin_mailmodo_job_queue($data) {
    // Call the data sending function
    adfoin_mailmodo_send_data($data['record'], $data['posted_data']);
}

// Function to process and send data to Mailmodo API
function adfoin_mailmodo_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && is_array($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $body = adfoin_mailmodo_build_contact($data, $posted_data, $record);

    if ($body) {
        adfoin_mailmodo_request('addToList', 'POST', $body, $record, $data['credId'] ?? '');
    }
}

/*
 * Mailmodo's built-in contact properties. The editor uses the same keys.
 */
function adfoin_mailmodo_standard_properties() {
    return ['first_name', 'last_name', 'name', 'gender', 'age', 'birthday', 'phone', 'address1', 'address2', 'city', 'state', 'country', 'postal_code', 'designation', 'company', 'industry', 'description', 'anniversary_date'];
}

/*
 * Builds the addToList body, or logs why the submission was skipped and returns null.
 * $extra_properties (from PRO) are added to the contact's data.
 */
function adfoin_mailmodo_build_contact($data, $posted_data, $record, $extra_properties = []) {
    $list_name = isset($data['listId']) ? trim((string) $data['listId']) : '';
    $raw_email = isset($data['email']) ? trim((string) adfoin_get_parsed_values($data['email'], $posted_data)) : '';
    $email     = sanitize_email($raw_email);

    if (!$email) {
        adfoin_mailmodo_log_skipped(
            $record,
            $raw_email === ''
                ? __('Not sent: the mapped email field is empty.', 'advanced-form-integration')
                : sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw_email)
        );
        return null;
    }

    if ($list_name === '') {
        adfoin_mailmodo_log_skipped($record, __('Not sent: no Mailmodo list is selected in the integration.', 'advanced-form-integration'));
        return null;
    }

    $properties = [];

    foreach (adfoin_mailmodo_standard_properties() as $key) {
        $value = isset($data[$key]) ? trim((string) adfoin_get_parsed_values($data[$key], $posted_data)) : '';

        if ($value === '') continue;

        if ($key === 'age') {
            // Mailmodo stores age as a number.
            if (!is_numeric($value)) continue;
            $value = (int) $value;
        }

        $properties[$key] = $value;
    }

    $properties = array_merge($properties, $extra_properties);
    $body       = ['email' => $email, 'listName' => $list_name];

    if ($properties) {
        $body['data'] = $properties;
    }

    return $body;
}

/*
 * Mailmodo can answer 200 with {"success": false}; log that as a failure.
 */
function adfoin_mailmodo_response_for_log($response) {
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_array($body) && isset($body['success']) && false === $body['success']) {
        $response['response']['code']    = 400;
        $response['response']['message'] = 'Mailmodo: ' . ($body['message'] ?? 'contact not added');
    }

    return $response;
}

function adfoin_mailmodo_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('mailmodo_not_sent', $message), 'https://api.mailmodo.com/api/v1/addToList', ['method' => 'POST'], $record);
}

function adfoin_mailmodo_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('mailmodo', $cred_id);
    $api_key = $credentials['apiKey'] ?? '';
    $base_url = 'https://api.mailmodo.com/api/v1/';
    $url = $base_url . ltrim($endpoint, '/');

    if ($api_key === '') {
        $error = new WP_Error('mailmodo_no_account', __('No Mailmodo account is selected, or the account has no API key. Check the integration and AFI Settings > Mailmodo.', 'advanced-form-integration'));

        if (!empty($record)) {
            adfoin_add_to_log($error, $url, ['method' => $method, 'body' => $data], $record);
        }

        return $error;
    }

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'mmApiKey'     => $api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]
    ];

    if (in_array($method, ['POST', 'PUT'])) {
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        $log_args = $args;
        $log_args['headers']['mmApiKey'] = substr($api_key, 0, 4) . '****';
        adfoin_add_to_log(adfoin_mailmodo_response_for_log($response), $url, $log_args, $record);
    }

    return $response;
}

add_action('adfoin_action_fields', 'adfoin_mailmodo_action_fields');

function adfoin_mailmodo_action_fields() {
?>
<script type="text/template" id="mailmodo-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'subscribe'">
            <th scope="row">
                <?php esc_html_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'subscribe'">
            <th scope="row"><?php esc_html_e('Mailmodo Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists" required>
                    <option value=""><?php esc_html_e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_mailmodo_credentials_list(); ?>
                </select>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailmodo' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'subscribe'">
            <th scope="row"><?php _e('Mailing List', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[listId]" v-model="fielddata.listId" required>
                    <option value=""><?php _e('Select List...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.lists" :value="id">{{ name }}</option>
                </select>
                <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                <p class="description" style="color: #b32d2e;" v-if="listError">{{ listError }}</p>
            </td>
        </tr>

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>

        <?php adfoin_pro_feature_notice('subscribe', 'Mailmodo [PRO]', 'custom properties'); ?>

    </table>
</script>
<?php
}
?>