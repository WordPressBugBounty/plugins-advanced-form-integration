<?php
add_filter('adfoin_action_providers', 'adfoin_audienceful_actions', 10, 1);
function adfoin_audienceful_actions($actions) {
    $actions['audienceful'] = array(
        'title' => __('Audienceful', 'advanced-form-integration'),
        'tasks' => array('add_person' => __('Add Person', 'advanced-form-integration'))
    );
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_audienceful_settings_tab', 10, 1);
function adfoin_audienceful_settings_tab($providers) {
    $providers['audienceful'] = __('Audienceful', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_audienceful_settings_view', 10, 1);
function adfoin_audienceful_settings_view($current_tab) {
    if ($current_tab !== 'audienceful') return;

    $title = __('Audienceful', 'advanced-form-integration');
    $key = 'audienceful';
    $arguments = wp_json_encode(array(
        'platform' => $key,
        'fields' => array(
            array('key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true)
        )
    ));
    $instructions = __('Get your API key from the Audienceful dashboard under Settings > API.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_audienceful_credentials', 'adfoin_get_audienceful_credentials');
function adfoin_get_audienceful_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('audienceful'));
}

add_action('wp_ajax_adfoin_save_audienceful_credentials', 'adfoin_save_audienceful_credentials');
function adfoin_save_audienceful_credentials() {
    adfoin_verify_nonce();

    if ($_POST['platform'] === 'audienceful') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('audienceful', $data);
    }

    wp_send_json_success();
}

function adfoin_audienceful_credentials_list() {
    foreach (adfoin_read_credentials('audienceful') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('adfoin_audienceful_job_queue', 'adfoin_audienceful_job_queue', 10, 1);
function adfoin_audienceful_job_queue($data) {
    adfoin_audienceful_send_data($data['record'], $data['posted_data']);
}

function adfoin_audienceful_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    $cl = isset($record_data['action_data']['cl']) ? $record_data['action_data']['cl'] : array();
    if (adfoin_check_conditional_logic($cl, $posted_data)) return;

    $data    = $record_data['field_data'];
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $email   = adfoin_audienceful_parse_email($data, $posted_data, $record, true);

    if (!$email) return;

    $person = adfoin_audienceful_base_person($data, $posted_data);
    $person['email'] = $email;

    foreach (array('first_name', 'last_name') as $key) {
        $value = adfoin_audienceful_value($data, $key, $posted_data);

        if ($value !== '') {
            $person['extra_data'][$key] = $value;
        }
    }

    adfoin_audienceful_upsert_person($person, $record, $cred_id);
}

function adfoin_audienceful_value($data, $key, $posted_data) {
    return trim((string) adfoin_get_parsed_values(isset($data[$key]) ? $data[$key] : '', $posted_data));
}

/*
 * Tags and notes, shared by the free and PRO actions.
 */
function adfoin_audienceful_base_person($data, $posted_data) {
    $person = array();

    $tags = array_filter(array_map('trim', explode(',', adfoin_audienceful_value($data, 'tags', $posted_data))), 'strlen');

    if ($tags) {
        $person['tags'] = implode(',', $tags);
    }

    $notes = adfoin_audienceful_value($data, 'notes', $posted_data);

    if ($notes !== '') {
        $person['notes'] = $notes;
    }

    return $person;
}

/*
 * Returns the sanitized email, or '' (after logging why, when $required) if it is missing or invalid.
 */
function adfoin_audienceful_parse_email($data, $posted_data, $record, $required) {
    $raw   = adfoin_audienceful_value($data, 'email', $posted_data);
    $email = sanitize_email($raw);

    if (!$email && ($required || $raw !== '')) {
        adfoin_audienceful_log_skipped(
            $record,
            $raw === ''
                ? __('Not sent: the mapped email field is empty.', 'advanced-form-integration')
                : sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw)
        );

        return false;
    }

    return $email;
}

function adfoin_audienceful_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('audienceful_not_sent', $message), 'https://app.audienceful.com/api/people/', array('method' => 'POST'), $record);
}

/*
 * Creates the person, or updates them when the email already exists.
 * Audienceful's POST merges tags and custom fields into an existing person but
 * ignores notes and phone number, so those are sent again with a PATCH (without
 * tags: a PATCH replaces them). Each submission gets one log entry.
 */
function adfoin_audienceful_upsert_person($person, $record, $cred_id) {
    $response = adfoin_audienceful_request('people/', 'POST', $person, array(), $cred_id);

    if (is_wp_error($response)) {
        adfoin_audienceful_log($response, 'people/', 'POST', $person, $record, $cred_id);
        return $response;
    }

    $code   = wp_remote_retrieve_response_code($response);
    $update = array_intersect_key($person, array_flip(array('notes', 'phone_number')));

    if ($code != 200 || empty($person['email']) || !$update) {
        adfoin_audienceful_log($response, 'people/', 'POST', $person, $record, $cred_id, $code == 200 ? __('OK (existing person updated)', 'advanced-form-integration') : '');
        return $response;
    }

    $update   = array('email' => $person['email']) + $update;
    $response = adfoin_audienceful_request('people/', 'PATCH', $update, array(), $cred_id);

    adfoin_audienceful_log($response, 'people/', 'PATCH', $update, $record, $cred_id, __('OK (existing person updated)', 'advanced-form-integration'));

    return $response;
}

function adfoin_audienceful_build_request($endpoint, $method, $data, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('audienceful', $cred_id);
    $api_key     = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';

    if (!$api_key) {
        return new WP_Error('audienceful_no_account', __('No Audienceful account is selected, or the account has no API key. Check the integration and AFI Settings > Audienceful.', 'advanced-form-integration'));
    }

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'X-Api-Key'    => $api_key,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        )
    );

    if (in_array($method, array('POST', 'PUT', 'PATCH'))) {
        $args['body'] = wp_json_encode($data);
    }

    return array('https://app.audienceful.com/api/' . ltrim($endpoint, '/'), $args);
}

function adfoin_audienceful_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '') {
    $request = adfoin_audienceful_build_request($endpoint, $method, $data, $cred_id);

    if (is_wp_error($request)) {
        if (!empty($record)) {
            adfoin_add_to_log($request, $endpoint, array('method' => $method, 'body' => $data), $record);
        }

        return $request;
    }

    list($url, $args) = $request;

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, adfoin_audienceful_args_for_log($args), $record);
    }

    return $response;
}

function adfoin_audienceful_args_for_log($args) {
    $args['headers']['X-Api-Key'] = substr($args['headers']['X-Api-Key'], 0, 4) . '****';

    return $args;
}

/*
 * Logs a response that was fetched without a record (see adfoin_audienceful_upsert_person()).
 */
function adfoin_audienceful_log($response, $endpoint, $method, $data, $record, $cred_id, $message = '') {
    $request = adfoin_audienceful_build_request($endpoint, $method, $data, $cred_id);
    $url     = is_wp_error($request) ? $endpoint : $request[0];
    $args    = is_wp_error($request) ? array('method' => $method, 'body' => $data) : adfoin_audienceful_args_for_log($request[1]);

    if ($message && !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
        $response['response']['message'] = $message;
    }

    adfoin_add_to_log($response, $url, $args, $record);
}

/*
 * Editable custom fields of the account, used by Audienceful [PRO].
 */
add_action('wp_ajax_adfoin_get_audienceful_fields', 'adfoin_get_audienceful_fields');
function adfoin_get_audienceful_fields() {
    adfoin_verify_nonce();

    $cred_id  = isset($_POST['credId']) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $response = adfoin_audienceful_request('people/fields/', 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (wp_remote_retrieve_response_code($response) != 200 || !is_array($body)) {
        wp_send_json_error(sprintf(
            __('Could not load Audienceful fields: %s', 'advanced-form-integration'),
            isset($body['detail']) ? $body['detail'] : wp_remote_retrieve_response_code($response)
        ));
    }

    $fields = array();

    foreach ($body as $field) {
        if (empty($field['editable']) || empty($field['data_name'])) continue;

        $fields[] = array(
            'key'   => $field['data_name'],
            'value' => isset($field['name']) ? $field['name'] : $field['data_name'],
            'type'  => isset($field['type']) ? $field['type'] : 'string'
        );
    }

    wp_send_json_success($fields);
}

add_action('adfoin_action_fields', 'adfoin_audienceful_action_fields');
function adfoin_audienceful_action_fields() {
?>
<script type="text/template" id="audienceful-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_person'">
            <th scope="row"><?php esc_attr_e('Audienceful Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_audienceful_credentials_list(); ?>
                </select>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=audienceful' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>
        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>

        <?php adfoin_pro_feature_notice('add_person', 'Audienceful [PRO]', 'phone number and custom fields'); ?>
    </table>
</script>
<?php
}
