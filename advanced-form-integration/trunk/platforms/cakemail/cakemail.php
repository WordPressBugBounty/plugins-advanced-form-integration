<?php

add_filter('adfoin_action_providers', 'adfoin_cakemail_actions', 10, 1);
function adfoin_cakemail_actions($actions) {
    $actions['cakemail'] = [
        'title' => __('Cakemail', 'advanced-form-integration'),
        'tasks' => [
            'add_subscriber' => __('Add Contact to List', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_cakemail_settings_tab', 10, 1);
function adfoin_cakemail_settings_tab($providers) {
    $providers['cakemail'] = __('Cakemail', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_cakemail_settings_view', 10, 1);
function adfoin_cakemail_settings_view($current_tab) {
    if ($current_tab !== 'cakemail') return;

    $title = __('Cakemail', 'advanced-form-integration');
    $key = 'cakemail';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'username', 'label' => __('Username', 'advanced-form-integration'), 'hidden' => true],
            ['key' => 'password', 'label' => __('Password', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your Cakemail account username and password.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_cakemail_credentials', 'adfoin_get_cakemail_credentials');
function adfoin_get_cakemail_credentials() {
    if (!adfoin_verify_nonce()) return;
    $credentials = adfoin_read_credentials('cakemail');
    wp_send_json_success($credentials);
}

add_action('wp_ajax_adfoin_save_cakemail_credentials', 'adfoin_save_cakemail_credentials');
function adfoin_save_cakemail_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'cakemail') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('cakemail', $data);
    }

    wp_send_json_success();
}

/**
 * Get a valid Cakemail access token for the given credentials ID.
 * Handles expiry and refresh.
 */
function adfoin_get_cakemail_access_token($cred_id) {
    $credentials = adfoin_get_credentials_by_id('cakemail', $cred_id);
    $username = isset($credentials['username']) ? $credentials['username'] : '';
    $password = isset($credentials['password']) ? $credentials['password'] : '';

    if (empty($username) || empty($password)) {
        return '';
    }

    $token_data = get_option('adfoin_cakemail_token_' . $cred_id, array());
    $expires_in = isset($token_data['expires_in']) ? (int)$token_data['expires_in'] : 0;
    $fetched_at = isset($token_data['fetched_at']) ? (int)$token_data['fetched_at'] : 0;
    $access_token = isset($token_data['access_token']) ? $token_data['access_token'] : '';

    // If token exists and not expired (subtract 60s as buffer)
    if (!empty($access_token) && $expires_in && $fetched_at && (time() - $fetched_at) < ($expires_in - 60)) {
        return $access_token;
    }

    // Get new token
    $token_response = adfoin_cakemail_generate_token($username, $password);

    if (is_wp_error($token_response)) {
        return '';
    }

    $http_code = isset($token_response['http_code']) ? $token_response['http_code'] : 0;
    $body = isset($token_response['body']) ? $token_response['body'] : array();

    if ($http_code === 200 && !empty($body['access_token'])) {
        $new_token_data = array(
            'access_token'  => $body['access_token'],
            'refresh_token' => isset($body['refresh_token']) ? $body['refresh_token'] : '',
            'expires_in'    => isset($body['expires_in']) ? $body['expires_in'] : 0,
            'fetched_at'    => time()
        );
        update_option('adfoin_cakemail_token_' . $cred_id, $new_token_data);
        return $body['access_token'];
    }

    return '';
}

// Token generation function for Cakemail
function adfoin_cakemail_generate_token($username, $password) {
    $args = array(
        'body' => array(
            'grant_type' => 'password',
            'username'   => $username,
            'password'   => $password
        ),
        'headers' => array(
            'accept'       => 'application/json',
            'content-type' => 'application/x-www-form-urlencoded',
        ),
        'timeout' => 30
    );

    $response = wp_remote_post('https://api.cakemail.dev/token', $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return array(
        'http_code' => $http_code,
        'body'      => $body
    );
}

function adfoin_cakemail_credentials_list() {
    foreach (adfoin_read_credentials('cakemail') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_cakemail_lists', 'adfoin_get_cakemail_lists');

function adfoin_get_cakemail_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $response = adfoin_cakemail_request('lists?per_page=1000', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $http_code = wp_remote_retrieve_response_code($response);

    if ($http_code === 200 && !empty($body['data'])) {
        $lists = [];
        foreach($body['data'] as $list_item) {
            if (isset($list_item['id']) && isset($list_item['name'])) {
                $lists[$list_item['id']] = $list_item['name'];
            }
        }
        wp_send_json_success($lists);
    }

    wp_die();
}

add_action('adfoin_cakemail_job_queue', 'adfoin_cakemail_job_queue', 10, 1);
function adfoin_cakemail_job_queue($data) {
    adfoin_cakemail_send_data($data['record'], $data['posted_data']);
}

function adfoin_cakemail_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $list_id = isset($data['listId']) ? $data['listId'] : '';
    $double_opt_in = isset($data['doubleOptIn']) ? $data['doubleOptIn'] : false;
    $double_opt_in = ($double_opt_in === 'true' || $double_opt_in === true);
    $email = adfoin_get_parsed_values(isset($data['email']) ? $data['email'] : '', $posted_data);
    $tags = adfoin_get_parsed_values(isset($data['tags']) ? $data['tags'] : '', $posted_data);
    $interests = adfoin_get_parsed_values(isset($data['interests']) ? $data['interests'] : '', $posted_data);

    // Prepare custom fields according to Cakemail API
    $custom_fields = array();
    foreach ($data as $key => $value) {
        if (in_array($key, array('credId', 'listId', 'doubleOptIn', 'email', 'tags', 'interests'))) {
            continue;
        }
        $parsed_value = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed_value !== '') {
            $custom_fields[] = array(
                'name'  => $key,
                'value' => $parsed_value
            );
        }
    }

    $payload = array(
        'email' => $email,
    );

    if (!empty($custom_fields)) {
        $payload['custom_attributes'] = $custom_fields;
    }

    $endpoint = sprintf(
        'lists/%s/contacts?send_double_opt_in=%s&resubscribe=true',
        urlencode($list_id),
        $double_opt_in ? 'true' : 'false'
    );

    $contact_response = adfoin_cakemail_request($endpoint, 'POST', $payload, $record, $cred_id);

    if( !empty($tags) || !empty($interests) ) {
        if (!is_wp_error($contact_response)) {
            $contact_body = json_decode(wp_remote_retrieve_body($contact_response), true);
            $contact_code = wp_remote_retrieve_response_code($contact_response);

            if ($contact_code === 201 && !empty($contact_body['id'])) {
                $contact_id = $contact_body['id'];

                if (!empty($tags)) {
                    $tags_array = is_array($tags) ? $tags : explode(',', $tags);
                    $tags_array = array_map('trim', $tags_array);
                    $tags_array = array_filter($tags_array);

                    if (!empty($tags_array)) {
                        $tags_endpoint = sprintf('lists/%s/contacts/%s/tag', urlencode($list_id), urlencode($contact_id));
                        $tags_payload = [
                            'tags' => $tags_array
                        ];
                        adfoin_cakemail_request($tags_endpoint, 'POST', $tags_payload, $record, $cred_id);
                    }
                }

                if (!empty($interests)) {
                    $interests_array = is_array($interests) ? $interests : explode(',', $interests);
                    $interests_array = array_map('trim', $interests_array);
                    $interests_array = array_filter($interests_array);

                    if (!empty($interests_array)) {
                        // Prepare payload as per the required format
                        $add_interests_endpoint = sprintf('lists/%s/contacts/add-interests', urlencode($list_id));
                        $add_interests_payload = [
                            'interests'   => $interests_array,
                            'contact_ids' => [$contact_id],
                        ];
                        adfoin_cakemail_request($add_interests_endpoint, 'POST', $add_interests_payload, $record, $cred_id);
                    }
                }
            }
        }
    }
}

function adfoin_cakemail_get_access_token($cred_id) {
    $credentials = adfoin_get_credentials_by_id('cakemail', $cred_id);
    $username = isset($credentials['username']) ? $credentials['username'] : '';
    $password = isset($credentials['password']) ? $credentials['password'] : '';

    if (empty($username) || empty($password)) {
        return '';
    }

    $token_data = get_option('adfoin_cakemail_token_' . $cred_id, array());
    $expires_in = isset($token_data['expires_in']) ? (int)$token_data['expires_in'] : 0;
    $fetched_at = isset($token_data['fetched_at']) ? (int)$token_data['fetched_at'] : 0;
    $access_token = isset($token_data['access_token']) ? $token_data['access_token'] : '';

    // If token exists and not expired (subtract 60s as buffer)
    if (!empty($access_token) && $expires_in && $fetched_at && (time() - $fetched_at) < ($expires_in - 60)) {
        return $access_token;
    }

    // Get new token
    $token_response = adfoin_cakemail_generate_token($username, $password);

    if (is_wp_error($token_response)) {
        return '';
    }

    $http_code = isset($token_response['http_code']) ? $token_response['http_code'] : 0;
    $body = isset($token_response['body']) ? $token_response['body'] : array();

    if ($http_code === 200 && !empty($body['access_token'])) {
        $new_token_data = array(
            'access_token'  => $body['access_token'],
            'refresh_token' => isset($body['refresh_token']) ? $body['refresh_token'] : '',
            'expires_in'    => isset($body['expires_in']) ? $body['expires_in'] : 0,
            'fetched_at'    => time()
        );
        update_option('adfoin_cakemail_token_' . $cred_id, $new_token_data);
        return $body['access_token'];
    }

    return '';
}

function adfoin_cakemail_request($endpoint, $method = 'GET', $body_data = array(), $record = array(), $cred_id = '') {

    $access_token = adfoin_cakemail_get_access_token($cred_id);

    $base_url = 'https://api.cakemail.dev/';
    $url = $base_url . ltrim($endpoint, '/');

    $args = array(
        'method' => $method,
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        )
    );

    if ($method === 'GET') {
        // Add query params if needed
        if ($endpoint === 'lists') {
            $url = add_query_arg(array('per_page' => 100), $url);
        }
    } elseif ($method === 'POST' || $method === 'PUT') {
        $args['body'] = json_encode($body_data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('adfoin_action_fields', 'adfoin_cakemail_action_fields');
function adfoin_cakemail_action_fields() {
?>
<script type="text/template" id="cakemail-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_subscriber'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <td scope="row"><?php _e('Cakemail Account', 'advanced-form-integration'); ?></td>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_cakemail_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_subscriber'">
            <td scope="row"><?php _e('Mailing List', 'advanced-form-integration'); ?></td>
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

add_action('wp_ajax_adfoin_get_cakemail_custom_fields', 'adfoin_get_cakemail_custom_fields');
function adfoin_get_cakemail_custom_fields() {
    if (!adfoin_verify_nonce()) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $list_id = isset($_POST['listId']) ? sanitize_text_field($_POST['listId']) : '';
    $endpoint = sprintf('lists/%s/custom-attributes', urlencode($list_id));
    $response = adfoin_cakemail_request($endpoint, 'GET', [], [], $cred_id);
    $http_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($http_code === 200 && !empty($body['data'])) {
        $fields = [];
        foreach ($body['data'] as $field) {
            $fields[] = [
                'key'         => isset($field['name']) ? $field['name'] : '',
                'label'       => isset($field['name']) ? $field['name'] : '',
                'description' => '',
            ];
        }
        wp_send_json_success($fields);
    } else {
        wp_send_json_error(['message' => 'Failed to fetch custom fields']);
    }
}