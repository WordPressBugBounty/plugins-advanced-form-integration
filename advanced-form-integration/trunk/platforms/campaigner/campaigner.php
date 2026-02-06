<?php

add_filter('adfoin_action_providers', 'adfoin_campaigner_actions', 10, 1);

function adfoin_campaigner_actions($actions) {
    $actions['campaigner'] = array(
        'title' => __('Campaigner', 'advanced-form-integration'),
        'tasks' => array(
            'subscribe' => __('Subscribe to List', 'advanced-form-integration'),
        )
    );

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_campaigner_settings_tab', 10, 1);

function adfoin_campaigner_settings_tab($providers) {
    $providers['campaigner'] = __('Campaigner', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_campaigner_settings_view', 10, 1);

function adfoin_campaigner_settings_view($current_tab) {
    if ($current_tab != 'campaigner') {
        return;
    }

    $title = __('Campaigner', 'advanced-form-integration');
    $key = 'campaigner';
    $arguments = json_encode(array(
        'platform' => $key,
        'fields' => array(
            array(
                'key' => 'apiKey',
                'label' => __('API Key', 'advanced-form-integration'),
                'required' => true
            )
        )
    ));
    $instructions = sprintf(
        __(
            '<p>
                <li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>
            </p>',
            'advanced-form-integration'
        ),
        'https://knowledge.campaigner.com/s/article/Create-and-Manage-API-Keys', // URL
        __('Read how to get the API Key', 'advanced-form-integration') // Link text
    );

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_campaigner_credentials', 'adfoin_get_campaigner_credentials', 10, 0);

function adfoin_get_campaigner_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('campaigner');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_campaigner_credentials', 'adfoin_save_campaigner_credentials', 10, 0);

function adfoin_save_campaigner_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);

    if ('campaigner' == $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);

        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_campaigner_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('campaigner');

    foreach ($credentials as $option) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_campaigner_action_fields');

function adfoin_campaigner_action_fields() {
    ?>
    <script type="text/template" id="campaigner-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                </th>
                <td scope="row"></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Campaigner Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""> <?php _e('Select Account...', 'advanced-form-integration'); ?> </option>
                        <?php adfoin_campaigner_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Campaigner List', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e('Select List...', 'advanced-form-integration'); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index"> {{item}} </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('adfoin_campaigner_job_queue', 'adfoin_campaigner_job_queue', 10, 1);

function adfoin_campaigner_job_queue($data) {
    adfoin_campaigner_send_data($data['record'], $data['posted_data']);
}

function adfoin_campaigner_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic(isset($record_data['action_data']['cl']) ? $record_data['action_data']['cl'] : array(), $posted_data)) return;

    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = isset($data['credId']) ? $data['credId'] : '';

    if ($task === 'subscribe') {
        $email = adfoin_get_parsed_values($data['email'], $posted_data);
        $first_name = adfoin_get_parsed_values($data['firstName'], $posted_data);
        $last_name = adfoin_get_parsed_values($data['lastName'], $posted_data);
        $list_id = $data['listId'];

        $subscriber_data = array(
            'EmailAddress' => $email,
            'CustomFields' => array()
        );

        if($first_name) {
            $subscriber_data['CustomFields'][] = array(
                'Name' => 'First Name',
                'Value' => $first_name
            );
        }

        if($last_name) {
            $subscriber_data['CustomFields'][] = array(
                'Name' => 'Last Name',
                'Value' => $last_name
            );
        }

        if( $list_id ) {
            $subscriber_data['Lists'] = array($list_id);
        }

        adfoin_campaigner_request('Subscribers', 'POST', $subscriber_data, $record, $cred_id);
    }
}

function adfoin_campaigner_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('campaigner', $cred_id);
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
    $base_url = 'https://edapi.campaigner.com/v1/';
    $url = $base_url . $endpoint;

    $args = array(
        'method' => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'ApiKey' => $api_key
        ),
    );

    if (in_array($method, array('POST', 'PUT'))) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_campaigner_lists', 'adfoin_get_campaigner_lists', 10, 0);

function adfoin_get_campaigner_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);

    $response = adfoin_campaigner_request('Lists', 'GET', array(), array(), $cred_id);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    $lists = array();
    if (!empty($response_data['Lists'])) {
        foreach ($response_data['Lists'] as $list) {
            $lists[$list['ListID']] = $list['Name'];
        }
    }

    wp_send_json_success($lists);
}