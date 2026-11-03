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
    $arguments = wp_json_encode([
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
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('campayn'));
}

add_action('wp_ajax_adfoin_save_campayn_credentials', 'adfoin_save_campayn_credentials');
function adfoin_save_campayn_credentials() {
    adfoin_verify_nonce();

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
    adfoin_verify_nonce();

    $cred_id  = isset($_POST['credId']) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_campayn_request('lists.json', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body      = json_decode(wp_remote_retrieve_body($response), true);
    $http_code = wp_remote_retrieve_response_code($response);

    if ($http_code == 200 && is_array($body) && !isset($body['success'])) {
        $lists = [];
        foreach ($body as $list_item) {
            if (isset($list_item['id']) && isset($list_item['list_name'])) {
                $lists[$list_item['id']] = $list_item['list_name'];
            }
        }
        wp_send_json_success($lists);
    }

    wp_send_json_error(sprintf(
        __('Could not load Campayn lists: %s', 'advanced-form-integration'),
        !empty($body['error']) ? $body['error'] : (!empty($body['msg']) ? $body['msg'] : $http_code . ' ' . wp_remote_retrieve_response_message($response))
    ));
}

add_action('adfoin_campayn_job_queue', 'adfoin_campayn_job_queue', 10, 1);
function adfoin_campayn_job_queue($data) {
    adfoin_campayn_send_data($data['record'], $data['posted_data']);
}

function adfoin_campayn_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data    = $record_data['field_data'];
    $contact = adfoin_campayn_base_contact($data, $posted_data, $record);

    if (!$contact) return;

    adfoin_campayn_add_contact($contact, $data, $record);
}

function adfoin_campayn_value($data, $key, $posted_data) {
    return trim((string) adfoin_get_parsed_values(isset($data[$key]) ? $data[$key] : '', $posted_data));
}

/*
 * Email and name, shared by the free and PRO actions.
 * Campayn accepts contacts without a (valid) email, so it is checked here.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_campayn_base_contact($data, $posted_data, $record) {
    if (empty($data['listId'])) {
        adfoin_campayn_log_skipped($record, __('Not sent: no Campayn list is selected in the integration.', 'advanced-form-integration'));
        return false;
    }

    $raw   = adfoin_campayn_value($data, 'email', $posted_data);
    $email = sanitize_email($raw);

    if (!$email) {
        adfoin_campayn_log_skipped(
            $record,
            $raw === ''
                ? __('Not sent: the mapped email field is empty.', 'advanced-form-integration')
                : sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw)
        );
        return false;
    }

    $contact = array('email' => $email);

    foreach (array('first_name', 'last_name') as $key) {
        $value = adfoin_campayn_value($data, $key, $posted_data);

        if ($value !== '') {
            $contact[$key] = $value;
        }
    }

    return $contact;
}

function adfoin_campayn_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('campayn_not_sent', $message), 'https://campayn.com/api/v1/', array('method' => 'POST'), $record);
}

/*
 * Adds the contact to the list unless the email is already on it.
 *
 * Campayn creates a second contact for an email that is already on the list,
 * and its API can't update contacts (PUT/POST to a contact fail with a server
 * error), so existing contacts are left unchanged. It also answers 200 for a
 * list that doesn't exist, so the response body is checked.
 *
 * Each submission gets one log entry.
 */
function adfoin_campayn_add_contact($contact, $data, $record, $notes = array()) {
    $cred_id  = isset($data['credId']) ? $data['credId'] : '';
    $list_id  = rawurlencode($data['listId']);
    $endpoint = "lists/{$list_id}/contacts.json";
    $lookup   = adfoin_campayn_request($endpoint . '?filter[contact]=' . rawurlencode($contact['email']), 'GET', array(), array(), $cred_id);

    if (is_wp_error($lookup) || wp_remote_retrieve_response_code($lookup) != 200) {
        adfoin_campayn_log($lookup, $endpoint, 'GET', array(), $record, $cred_id);
        return $lookup;
    }

    $found = json_decode(wp_remote_retrieve_body($lookup), true);

    foreach (is_array($found) ? $found : array() as $existing) {
        if (isset($existing['email']) && strcasecmp($existing['email'], $contact['email']) === 0) {
            $notes[] = __('already on this list, not changed: Campayn\'s API cannot update contacts', 'advanced-form-integration');
            adfoin_campayn_log($lookup, $endpoint, 'GET', array(), $record, $cred_id, $notes);
            return $lookup;
        }
    }

    $response = adfoin_campayn_request($endpoint, 'POST', $contact, array(), $cred_id);
    $body     = is_wp_error($response) ? null : json_decode(wp_remote_retrieve_body($response), true);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200 && empty($body['success'])) {
        $response['response']['code']    = 400;
        $response['response']['message'] = __('Campayn did not confirm the contact. Check that the list still exists.', 'advanced-form-integration');
    }

    adfoin_campayn_log($response, $endpoint, 'POST', $contact, $record, $cred_id, $notes);

    return $response;
}

function adfoin_campayn_build_request($endpoint, $method, $data, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('campayn', $cred_id);
    $api_key     = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';

    if (!$api_key) {
        return new WP_Error('campayn_no_account', __('No Campayn account is selected, or the account has no API key. Check the integration and AFI Settings > Campayn.', 'advanced-form-integration'));
    }

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
        $args['body'] = wp_json_encode($data);
    }

    return array('https://campayn.com/api/v1/' . ltrim($endpoint, '/'), $args);
}

function adfoin_campayn_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $request = adfoin_campayn_build_request($endpoint, $method, $data, $cred_id);

    if (is_wp_error($request)) {
        if (!empty($record)) {
            adfoin_add_to_log($request, $endpoint, array('method' => $method, 'body' => $data), $record);
        }

        return $request;
    }

    list($url, $args) = $request;

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

/*
 * Logs a response that was fetched without a record (see adfoin_campayn_add_contact()).
 */
function adfoin_campayn_log($response, $endpoint, $method, $data, $record, $cred_id, $notes = array()) {
    $request = adfoin_campayn_build_request($endpoint, $method, $data, $cred_id);
    $url     = is_wp_error($request) ? $endpoint : $request[0];
    $args    = is_wp_error($request) ? array('method' => $method, 'body' => $data) : $request[1];

    if ($notes && !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
        $response['response']['message'] = sprintf('OK (%s)', implode('; ', $notes));
    }

    adfoin_add_to_log($response, $url, $args, $record);
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=campayn' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('Mailing List', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[listId]" v-model="fielddata.listId" required>
                    <option value=""><?php _e('Select List...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.lists" :value="id">{{ name }}</option>
                </select>
                <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                <p class="description" v-if="listError" style="color: #b32d2e;">{{ listError }}</p>
            </td>
        </tr>

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>

        <?php adfoin_pro_feature_notice('add_subscriber', 'Campayn [PRO]', 'phone, company, address, birthday and custom fields'); ?>
    </table>
</script>
<?php
}