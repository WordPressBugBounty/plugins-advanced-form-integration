<?php

add_filter('adfoin_action_providers', 'adfoin_followupboss_actions', 10, 1);

function adfoin_followupboss_actions($actions) {
    $actions['followupboss'] = array(
        'title' => __('Follow Up Boss', 'advanced-form-integration'),
        'tasks' => array(
            'create_contact' => __('Add or Update Lead', 'advanced-form-integration')
        )
    );
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_followupboss_settings_tab', 10, 1);

function adfoin_followupboss_settings_tab($providers) {
    $providers['followupboss'] = __('Follow Up Boss', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_followupboss_settings_view', 10, 1);

function adfoin_followupboss_settings_view($current_tab) {
    if ($current_tab !== 'followupboss') return;

    $title = __('Follow Up Boss', 'advanced-form-integration');
    $key = 'followupboss';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('In Follow Up Boss, go to Admin > API and copy your API Key.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('adfoin_action_fields', 'adfoin_followupboss_action_fields');

function adfoin_followupboss_action_fields() {
    ?>
    <script type="text/template" id="followupboss-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row">
                    <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Follow Up Boss Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_followupboss_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=followupboss' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

            <?php adfoin_pro_feature_notice('create_contact', 'Follow Up Boss [PRO]', 'custom fields, tags, address, price, agent and stage'); ?>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_followupboss_credentials', 'adfoin_get_followupboss_credentials');
function adfoin_get_followupboss_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('followupboss'));
}

add_action('wp_ajax_adfoin_save_followupboss_credentials', 'adfoin_save_followupboss_credentials');
function adfoin_save_followupboss_credentials() {
    adfoin_verify_nonce();

    if ($_POST['platform'] === 'followupboss') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('followupboss', $data);
    }

    wp_send_json_success();
}

/*
 * Fields of the free action. The PRO action adds to these.
 */
function adfoin_followupboss_base_fields() {
    return array(
        array('key' => 'email', 'value' => 'Email', 'description' => 'Email or phone is required. Follow Up Boss matches existing people by email or phone.'),
        array('key' => 'firstName', 'value' => 'First Name', 'description' => ''),
        array('key' => 'lastName', 'value' => 'Last Name', 'description' => ''),
        array('key' => 'phone', 'value' => 'Phone', 'description' => ''),
        array('key' => 'source', 'value' => 'Source', 'description' => 'Lead source shown in Follow Up Boss. Default: your site name.'),
        array('key' => 'type', 'value' => 'Event Type', 'description' => 'General Inquiry (default), Registration, Seller Inquiry, Property Inquiry, Visited Open House...'),
        array('key' => 'message', 'value' => 'Message', 'description' => 'The lead\'s message or inquiry.'),
    );
}

add_action('wp_ajax_adfoin_get_followupboss_fields', 'adfoin_get_followupboss_fields');

function adfoin_get_followupboss_fields() {
    adfoin_verify_nonce();

    wp_send_json_success(adfoin_followupboss_base_fields());
}

function adfoin_followupboss_credentials_list() {
    foreach (adfoin_read_credentials('followupboss') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

function adfoin_followupboss_build_request($endpoint, $method, $data, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('followupboss', $cred_id);
    $api_key     = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';

    if (!$api_key) {
        return new WP_Error('followupboss_no_account', __('No Follow Up Boss account is selected, or the account has no API Key. Check the integration and AFI Settings > Follow Up Boss.', 'advanced-form-integration'));
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
            'Content-Type'  => 'application/json',
            'X-System'      => 'AdvancedFormIntegration',
        ),
    );

    if ($method === 'POST' || $method === 'PUT') {
        $args['body'] = wp_json_encode($data);
    }

    return array('https://api.followupboss.com/v1/' . $endpoint, $args);
}

function adfoin_followupboss_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $request = adfoin_followupboss_build_request($endpoint, $method, $data, $cred_id);

    if (is_wp_error($request)) {
        if ($record) {
            adfoin_add_to_log($request, $endpoint, array('method' => $method, 'body' => $data), $record);
        }

        return $request;
    }

    list($url, $args) = $request;

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

function adfoin_followupboss_error_message($response) {
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['errorMessage'])) {
        return $body['errorMessage'];
    }

    return wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
}

function adfoin_followupboss_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('followupboss_not_sent', $message), 'https://api.followupboss.com/v1/events', array('method' => 'POST'), $record);
}

/*
 * Mapped values, parsed and trimmed, without empty ones.
 */
function adfoin_followupboss_collect($data, $posted_data) {
    $fields = array();

    foreach ($data as $key => $value) {
        if ($key === 'credId' || !is_string($value) || $value === '') continue;

        $value = trim((string) adfoin_get_parsed_values($value, $posted_data));

        if ($value !== '') {
            $fields[$key] = $value;
        }
    }

    return $fields;
}

/*
 * The event body with the free fields. Returns false (after logging why)
 * when there is neither a valid email nor a phone to identify the lead.
 */
function adfoin_followupboss_base_event($fields, $record) {
    $person = array();

    if (isset($fields['email'])) {
        $email = sanitize_email($fields['email']);

        if (!$email) {
            adfoin_followupboss_log_skipped($record, sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $fields['email']));
            return false;
        }

        $person['emails'] = array(array('value' => $email));
    }

    if (isset($fields['phone'])) {
        $person['phones'] = array(array('value' => $fields['phone']));
    }

    if (empty($person['emails']) && empty($person['phones'])) {
        adfoin_followupboss_log_skipped($record, __('Not sent: map an email or a phone number; both are empty.', 'advanced-form-integration'));
        return false;
    }

    foreach (array('firstName', 'lastName') as $key) {
        if (isset($fields[$key])) {
            $person[$key] = $fields[$key];
        }
    }

    $event = array(
        'source' => isset($fields['source']) ? $fields['source'] : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        'system' => 'AdvancedFormIntegration',
        'type'   => isset($fields['type']) ? $fields['type'] : 'General Inquiry',
        'person' => $person,
    );

    if (isset($fields['message'])) {
        $event['message'] = $fields['message'];
    }

    return $event;
}

/*
 * Sends the lead as an event, Follow Up Boss's way of adding website leads:
 * it matches existing people by email or phone (no duplicates) and runs the
 * account's lead flow. Logs once; $after runs with the person ID on success
 * and may add notes (PRO stage update).
 */
function adfoin_followupboss_send_event($event, $record, $cred_id, $notes = array(), $after = null) {
    $response = adfoin_followupboss_request('events', 'POST', $event, array(), $cred_id);

    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code == 200) {
            array_unshift($notes, __('existing person updated', 'advanced-form-integration'));
        } elseif ($code == 204) {
            $notes[] = __('ignored by Follow Up Boss: the lead flow for this source is archived', 'advanced-form-integration');
        }

        if ($code < 300 && $after && !empty($body['id'])) {
            $notes = call_user_func($after, $body, $notes);
        }

        if ($code >= 300) {
            $response['response']['message'] = adfoin_followupboss_error_message($response);
        } elseif ($notes) {
            $response['response']['message'] = sprintf('OK (%s)', implode('; ', $notes));
        }
    }

    $request = adfoin_followupboss_build_request('events', 'POST', $event, $cred_id);

    adfoin_add_to_log(
        $response,
        is_wp_error($request) ? 'events' : $request[0],
        is_wp_error($request) ? array('method' => 'POST', 'body' => $event) : $request[1],
        $record
    );

    return $response;
}

add_action('adfoin_followupboss_job_queue', 'adfoin_followupboss_job_queue', 10, 1);

function adfoin_followupboss_job_queue($data) {
    adfoin_followupboss_send_data($data['record'], $data['posted_data']);
}

function adfoin_followupboss_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data    = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $event   = adfoin_followupboss_base_event(adfoin_followupboss_collect($data, $posted_data), $record);

    if (!$event) return;

    adfoin_followupboss_send_event($event, $record, $cred_id);
}
