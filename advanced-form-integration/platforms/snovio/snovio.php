<?php

add_filter('adfoin_action_providers', 'adfoin_snovio_actions', 10, 1);

function adfoin_snovio_actions($actions) {

    $actions['snovio'] = [
        'title' => __('Snov.io', 'advanced-form-integration'),
        'tasks' => [
            'add_contact' => __('Add Contact to List', 'advanced-form-integration'),
        ]
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_snovio_settings_tab', 10, 1);

function adfoin_snovio_settings_tab($providers) {
    $providers['snovio'] = __('Snov.io', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_snovio_settings_view', 10, 1);

function adfoin_snovio_settings_view($current_tab) {
    if ($current_tab != 'snovio') return;

    $title = __('Snov.io', 'advanced-form-integration');
    $key   = 'snovio';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiUserId', 'label' => __('API User ID', 'advanced-form-integration'), 'hidden' => true],
            ['key' => 'apiSecret', 'label' => __('API Secret', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        __('Go to your Snov.io account settings to generate an API User ID and API Secret.', 'advanced-form-integration'),
        __('Copy the credentials and paste them here.', 'advanced-form-integration')
    );

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

function adfoin_snovio_credentials_list() {
    $credentials = adfoin_read_credentials('snovio');
    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action( 'wp_ajax_adfoin_get_snovio_credentials', 'adfoin_get_snovio_credentials', 10, 0 );

function adfoin_get_snovio_credentials() {
    if ( !adfoin_verify_nonce() ) return;

    $all_credentials = adfoin_read_credentials( 'snovio' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_snovio_credentials', 'adfoin_save_snovio_credentials', 10, 0 );

function adfoin_save_snovio_credentials() {
    if ( !adfoin_verify_nonce() ) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'snovio' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

add_action('adfoin_action_fields', 'adfoin_snovio_action_fields');

function adfoin_snovio_action_fields() {
    ?>
    <script type="text/template" id="snovio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                </th>
                <td></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Snov.io Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php adfoin_snovio_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('List', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php _e('Select...', 'advanced-form-integration'); ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index">{{item}}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

/*
 * Snov.io API Request
 */
function adfoin_snovio_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {

    $credentials  = adfoin_get_credentials_by_id('snovio', $cred_id);
    $api_user_id  = isset($credentials['apiUserId']) ? $credentials['apiUserId'] : '';
    $api_secret   = isset($credentials['apiSecret']) ? $credentials['apiSecret'] : '';

    // Get access token
    $auth_url = 'https://api.snov.io/v1/oauth/access_token';
    $auth_body = [
        'grant_type' => 'client_credentials',
        'client_id' => $api_user_id,
        'client_secret' => $api_secret,
    ];

    $auth_response = wp_remote_post($auth_url, ['body' => $auth_body]);
    $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);

    if (empty($auth_body['access_token'])) {
        return new WP_Error('auth_error', 'Failed to retrieve access token.');
    }

    $access_token = $auth_body['access_token'];

    // API request
    $url = "https://api.snov.io/v1/{$endpoint}";
    $args = [
        'timeout' => 30,
        'method' => $method,
        'headers' => [
            'Authorization' => "Bearer $access_token",
            'Content-Type' => 'application/json',
        ],
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_snovio_lists', 'adfoin_get_snovio_lists', 10, 0);

function adfoin_get_snovio_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $response = adfoin_snovio_request('get-user-lists', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    if (!is_array($body)) {
        wp_send_json_error(__('Invalid response from Snov.io API', 'advanced-form-integration'));
    }

    $lists = [];
    foreach ($body as $field) {
        if (isset($field['id']) && isset($field['name'])) {
            $lists[$field['id']] = $field['name'];
        }
    }

    wp_send_json_success($lists);
}

/*
 * Add Contact to Snov.io List
 */
function adfoin_snovio_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    // Check conditional logic
    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    // Retrieve data
    $data    = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $list_id = isset($data['listId']) ? $data['listId'] : '';
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $task    = isset($record['task']) ? $record['task'] : '';

    if ($task === 'add_contact') {
        // Prepare contact data
        $contact_data = array(
            'email'         => adfoin_get_parsed_values(isset($data['email']) ? $data['email'] : '', $posted_data),
            'fullName'      => adfoin_get_parsed_values(isset($data['fullName']) ? $data['fullName'] : '', $posted_data),
            'firstName'     => adfoin_get_parsed_values(isset($data['firstName']) ? $data['firstName'] : '', $posted_data),
            'lastName'      => adfoin_get_parsed_values(isset($data['lastName']) ? $data['lastName'] : '', $posted_data),
            'phones'        => array_filter( explode(',', adfoin_get_parsed_values(isset($data['phones']) ? $data['phones'] : '', $posted_data))),
            'country'       => adfoin_get_parsed_values(isset($data['country']) ? $data['country'] : '', $posted_data),
            'locality'      => adfoin_get_parsed_values(isset($data['locality']) ? $data['locality'] : '', $posted_data),
            'socialLinks'   => array_filter(array(
                'linkedIn' => adfoin_get_parsed_values(isset($data['socialLinks[linkedIn]']) ? $data['socialLinks[linkedIn]'] : '', $posted_data),
                'twitter'  => adfoin_get_parsed_values(isset($data['social[twitter]']) ? $data['social[twitter]'] : '', $posted_data),
            )),
            'position'      => adfoin_get_parsed_values(isset($data['position']) ? $data['position'] : '', $posted_data),
            'companyName'   => adfoin_get_parsed_values(isset($data['companyName']) ? $data['companyName'] : '', $posted_data),
            'companySite'   => adfoin_get_parsed_values(isset($data['companySite']) ? $data['companySite'] : '', $posted_data),
            'listId'        => $list_id,
            'updateContact' => true,
        );

        // Make API request
        adfoin_snovio_request('add-prospect-to-list', 'POST', array_filter($contact_data), $record, $cred_id);
    }
}


add_action('adfoin_snovio_job_queue', 'adfoin_snovio_job_queue', 10, 1);

function adfoin_snovio_job_queue($data) {
    adfoin_snovio_send_data($data['record'], $data['posted_data']);
}