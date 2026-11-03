<?php

add_filter('adfoin_action_providers', 'adfoin_smartrmail_actions', 10, 1);
function adfoin_smartrmail_actions($actions) {
    $actions['smartrmail'] = [
        'title' => __('SmartrMail', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add/Update Subscriber to List', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_smartrmail_settings_tab', 10, 1);
function adfoin_smartrmail_settings_tab($providers) {
    $providers['smartrmail'] = __('SmartrMail', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_smartrmail_settings_view', 10, 1);
function adfoin_smartrmail_settings_view($current_tab) {
    if ($current_tab !== 'smartrmail') return;

    $title = __('SmartrMail', 'advanced-form-integration');
    $key = 'smartrmail';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            // SmartrMail uses a single Private API Token
            ['key' => 'apiToken', 'label' => __('Private API Token', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your SmartrMail Private API Token. You can generate this in your SmartrMail account under Account > API.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_smartrmail_credentials', 'adfoin_get_smartrmail_credentials');
function adfoin_get_smartrmail_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('smartrmail'));
}

add_action('wp_ajax_adfoin_save_smartrmail_credentials', 'adfoin_save_smartrmail_credentials');
function adfoin_save_smartrmail_credentials() {

    adfoin_verify_nonce();

    if (isset($_POST['platform']) && $_POST['platform'] === 'smartrmail') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('smartrmail', $data);
    }

    wp_send_json_success();
}

function adfoin_smartrmail_credentials_list() {
    foreach (adfoin_read_credentials('smartrmail') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_smartrmail_lists', 'adfoin_get_smartrmail_lists');
function adfoin_get_smartrmail_lists() {
    adfoin_verify_nonce();

    $cred_id  = isset($_POST['credId']) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_smartrmail_request('lists', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body      = json_decode(wp_remote_retrieve_body($response), true);
    $http_code = wp_remote_retrieve_response_code($response);

    if ($http_code == 200 && is_array($body)) {
        $lists = [];
        foreach ($body as $list_item) {
            if (isset($list_item['id']) && isset($list_item['name'])) {
                $lists[$list_item['id']] = $list_item['name'];
            }
        }
        wp_send_json_success($lists);
    }

    wp_send_json_error(sprintf(
        __('Could not load SmartrMail lists: %s', 'advanced-form-integration'),
        adfoin_smartrmail_error_message($response)
    ));
}

add_action('adfoin_smartrmail_job_queue', 'adfoin_smartrmail_job_queue', 10, 1);
function adfoin_smartrmail_job_queue($data) {
    adfoin_smartrmail_send_data($data['record'], $data['posted_data']);
}

function adfoin_smartrmail_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data       = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $notes      = array();
    $subscriber = adfoin_smartrmail_base_subscriber($data, $posted_data, $record, $notes);

    if (!$subscriber) return;

    adfoin_smartrmail_sync($subscriber, $data, $record, false, $notes);
}

function adfoin_smartrmail_value($data, $key, $posted_data) {
    return trim((string) adfoin_get_parsed_values(isset($data[$key]) ? $data[$key] : '', $posted_data));
}

/*
 * Email, name and phone, shared by the free and PRO actions.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_smartrmail_base_subscriber($data, $posted_data, $record, &$notes) {
    if (empty($data['listId'])) {
        adfoin_smartrmail_log_skipped($record, __('Not sent: no SmartrMail list is selected in the integration.', 'advanced-form-integration'));
        return false;
    }

    $raw   = adfoin_smartrmail_value($data, 'email', $posted_data);
    $email = sanitize_email($raw);

    if (!$email) {
        adfoin_smartrmail_log_skipped(
            $record,
            $raw === ''
                ? __('Not sent: the mapped email field is empty.', 'advanced-form-integration')
                : sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw)
        );
        return false;
    }

    $subscriber = array('email' => $email);

    foreach (array('first_name', 'last_name') as $key) {
        $value = adfoin_smartrmail_value($data, $key, $posted_data);

        if ($value !== '') {
            $subscriber[$key] = $value;
        }
    }

    $raw_phone = adfoin_smartrmail_value($data, 'phone', $posted_data);

    if ($raw_phone !== '') {
        $phone = adfoin_smartrmail_phone($raw_phone);

        if ($phone) {
            $subscriber['phone'] = $phone;
        } else {
            $notes[] = sprintf(__('phone "%s" is not in international format, left empty', 'advanced-form-integration'), $raw_phone);
        }
    }

    return $subscriber;
}

/*
 * SmartrMail stores phones as "+" followed by the digits it is given, so a
 * local number (leading 0) would be saved wrongly. Returns '' for those.
 */
function adfoin_smartrmail_phone($value) {
    $phone = preg_replace('/[\s\-().]/', '', $value);

    if (strpos($phone, '00') === 0) {
        $phone = '+' . substr($phone, 2);
    }

    if (!preg_match('/^\+?[1-9]\d{6,14}$/', $phone)) {
        return '';
    }

    return '+' . ltrim($phone, '+');
}

function adfoin_smartrmail_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('smartrmail_not_sent', $message), 'https://go.smartrmail.com/api/v1/', array('method' => 'POST'), $record);
}

/*
 * Adds the subscriber to the list, or updates them when they already exist.
 *
 * SmartrMail quirks this works around:
 * - Adding a person who unsubscribed to a list re-subscribes them, so they are
 *   only updated, unless $resubscribe is set.
 * - Adding a person to a list they are already on returns 422 with an empty
 *   body (their details are still saved), so they are updated with a PUT.
 *
 * Each submission gets one log entry.
 */
function adfoin_smartrmail_sync($subscriber, $data, $record, $resubscribe = false, $notes = array()) {
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $id      = rawurlencode($subscriber['email']);
    $lookup  = adfoin_smartrmail_request("subscribers/{$id}", 'GET', array(), array(), $cred_id);
    $code    = is_wp_error($lookup) ? 0 : wp_remote_retrieve_response_code($lookup);

    if ($code != 200 && $code != 404) {
        adfoin_smartrmail_log($lookup, "subscribers/{$id}", 'GET', array(), $record, $cred_id);
        return $lookup;
    }

    $existing = $code == 200 ? json_decode(wp_remote_retrieve_body($lookup), true) : null;
    $list_url = 'lists/' . rawurlencode($data['listId']) . '/list_subscribers';

    if ($existing && empty($existing['subscribed'])) {
        if (!$resubscribe) {
            $notes[] = __('the subscriber had unsubscribed: details updated, not added to the list', 'advanced-form-integration');
            $response = adfoin_smartrmail_request("subscribers/{$id}", 'PUT', $subscriber, array(), $cred_id);
            adfoin_smartrmail_log($response, "subscribers/{$id}", 'PUT', $subscriber, $record, $cred_id, $notes);
            return $response;
        }

        $resub = adfoin_smartrmail_request("subscribers/{$id}/resubscribe", 'POST', array(), array(), $cred_id);

        if (is_wp_error($resub) || wp_remote_retrieve_response_code($resub) >= 300) {
            adfoin_smartrmail_log($resub, "subscribers/{$id}/resubscribe", 'POST', array(), $record, $cred_id);
            return $resub;
        }

        $notes[] = __('re-subscribed', 'advanced-form-integration');
    }

    $payload  = array('subscribers' => array($subscriber + array('subscribed' => true)));
    $response = adfoin_smartrmail_request($list_url, 'POST', $payload, array(), $cred_id);

    if ($existing && !is_wp_error($response) && wp_remote_retrieve_response_code($response) == 422) {
        $notes[]  = __('already on the list, details updated', 'advanced-form-integration');
        $response = adfoin_smartrmail_request("subscribers/{$id}", 'PUT', $subscriber, array(), $cred_id);
        adfoin_smartrmail_log($response, "subscribers/{$id}", 'PUT', $subscriber, $record, $cred_id, $notes);
        return $response;
    }

    if ($existing) {
        $notes[] = __('existing subscriber updated and added to the list', 'advanced-form-integration');
    }

    adfoin_smartrmail_log($response, $list_url, 'POST', $payload, $record, $cred_id, $notes);

    return $response;
}

function adfoin_smartrmail_error_message($response) {
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['error'])) {
        return $body['error'];
    }

    if (!empty($body['message'])) {
        return $body['message'];
    }

    return wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
}

function adfoin_smartrmail_build_request($endpoint, $method, $body_data, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('smartrmail', $cred_id);
    $api_token   = isset($credentials['apiToken']) ? $credentials['apiToken'] : '';

    if (!$api_token) {
        return new WP_Error('smartrmail_no_account', __('No SmartrMail account is selected, or the account has no API token. Check the integration and AFI Settings > SmartrMail.', 'advanced-form-integration'));
    }

    $url = 'https://go.smartrmail.com/api/v1/' . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'Advanced-Form-Integration-WordPress-Plugin',
            'Authorization' => 'token ' . $api_token
        ]
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $args['body'] = wp_json_encode($body_data);
    } elseif ($method === 'GET' && !empty($body_data)) {
        $url = add_query_arg($body_data, $url);
    }

    return array($url, $args);
}

function adfoin_smartrmail_request($endpoint, $method = 'GET', $body_data = [], $record = [], $cred_id = '') {
    $request = adfoin_smartrmail_build_request($endpoint, $method, $body_data, $cred_id);

    if (is_wp_error($request)) {
        if (!empty($record)) {
            adfoin_add_to_log($request, $endpoint, array('method' => $method, 'body' => $body_data), $record);
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
 * Logs a response that was fetched without a record (see adfoin_smartrmail_sync()).
 * Successful responses get the notes as their message; SmartrMail's empty 422
 * gets an explanation.
 */
function adfoin_smartrmail_log($response, $endpoint, $method, $body_data, $record, $cred_id, $notes = array()) {
    $request = adfoin_smartrmail_build_request($endpoint, $method, $body_data, $cred_id);
    $url     = is_wp_error($request) ? $endpoint : $request[0];
    $args    = is_wp_error($request) ? array('method' => $method, 'body' => $body_data) : $request[1];

    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);

        if ($code < 300 && $notes) {
            $response['response']['message'] = sprintf('OK (%s)', implode('; ', $notes));
        } elseif ($code == 422) {
            $response['response']['message'] = __('Unprocessable Entity: SmartrMail rejected the subscriber. Check the email, phone and custom field values.', 'advanced-form-integration');
        } elseif (strpos(wp_remote_retrieve_body($response), 'Name has already been taken') !== false) {
            $response['response']['message'] = __('A custom field with this name already exists in SmartrMail with a different type. Use the same type as in SmartrMail.', 'advanced-form-integration');
        }
    }

    adfoin_add_to_log($response, $url, $args, $record);
}

add_action('adfoin_action_fields', 'adfoin_smartrmail_action_fields');
function adfoin_smartrmail_action_fields() {
?>
<script type="text/template" id="smartrmail-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <th scope="row"><?php _e('SmartrMail Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_smartrmail_credentials_list(); ?>
                </select>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=smartrmail' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
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

        <?php adfoin_pro_feature_notice('add_subscriber', 'SmartrMail [PRO]', 'custom fields'); ?>
    </table>
</script>
<?php
}