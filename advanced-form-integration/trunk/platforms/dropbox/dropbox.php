<?php

add_filter('adfoin_action_providers', 'adfoin_dropbox_actions', 10, 1);

function adfoin_dropbox_actions($actions)
{
    $actions['dropbox'] = [
        'title' => __('Dropbox', 'advanced-form-integration'),
        'tasks' => [
            'upload_file' => __('Upload File', 'advanced-form-integration'),
        ],
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_dropbox_settings_tab', 10, 1);

function adfoin_dropbox_settings_tab($providers)
{
    $providers['dropbox'] = __('Dropbox', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_dropbox_settings_view', 10, 1);

function adfoin_dropbox_settings_view($current_tab) {
    if ($current_tab != 'dropbox') {
        return;
    }

    $title = __('Dropbox', 'advanced-form-integration');
    $key = 'dropbox';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'accessToken',
                'label' => __('Access Token', 'advanced-form-integration'),
                'hidden' => true
            ]
        ]
    ]);

    $instructions = __(
        '<ol>
            <li>Go to the <a href="https://www.dropbox.com/developers/apps" target="_blank">Dropbox Developer Portal</a> and create a new app.</li>
            <li>Select the required permissions under "App Permissions" (e.g., "files.content.write").</li>
            <li>Generate an <strong>Access Token</strong> and copy it.</li>
        </ol>',
        'advanced-form-integration'
    );

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_dropbox_credentials', 'adfoin_get_dropbox_credentials', 10, 0);

function adfoin_get_dropbox_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('dropbox');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_dropbox_credentials', 'adfoin_save_dropbox_credentials', 10, 0);

function adfoin_save_dropbox_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);

    if ('dropbox' == $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_dropbox_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('dropbox');

    foreach ($credentials as $option) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_dropbox_action_fields');

function adfoin_dropbox_action_fields()
{
    ?>
    <script type="text/template" id="dropbox-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'upload_file'">
                <th scope="row">
                    <?php esc_attr_e('Dropbox Account', 'advanced-form-integration'); ?>
                </th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFolders">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php adfoin_dropbox_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'upload_file'">
                <th scope="row">
                    <?php esc_attr_e('Folder', 'advanced-form-integration'); ?>
                </th>
                <td>
                    <select name="fieldData[folderId]" v-model="fielddata.folderId">
                        <option value=""><?php _e('Select Folder...', 'advanced-form-integration'); ?></option>
                        <option v-for="(item, index) in fielddata.folders" :value="index">{{item}}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': folderLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_dropbox_folders', 'adfoin_get_dropbox_folders');

function adfoin_get_dropbox_folders() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId'] ?? '');
    $response = adfoin_dropbox_request('/2/files/list_folder', 'POST', ['path' => ''], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['entries'])) {
        $folders = ['/' => __('Root', 'advanced-form-integration')];
        foreach ($body['entries'] as $entry) {
            if ($entry['.tag'] === 'folder') {
                $folders[$entry['id']] = $entry['name'];
            }
        }
        wp_send_json_success($folders);
    } else {
        wp_send_json_error();
    }
}

add_action('adfoin_job_queue', 'adfoin_dropbox_job_queue', 10, 1);

function adfoin_dropbox_job_queue($data) {
    adfoin_dropbox_send_data($data['record'], $data['posted_data']);
}

function adfoin_dropbox_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = empty($data['credId']) ? '' : $data['credId'];
    $folder_id = empty($data['folderId']) ? '' : $data['folderId'];
    $file_path = empty($data['fileField']) ? '' : adfoin_get_parsed_values($data['fileField'], $posted_data);

    if (!$folder_id || !$file_path) {
        return;
    }

    $file_content = file_get_contents($file_path);
    $folder_id = $folder_id == '/' ? '' : $folder_id;

    $response = adfoin_dropbox_file_request(
        '/2/files/upload',
        'POST',
        ['path' => "$folder_id/" . basename($file_path), 'mode' => 'add', 'autorename' => false, 'mute' => false, 'strict_conflict' => false],
        $file_content,
        $cred_id
    );

    if (is_wp_error($response)) {
        return $response;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

function adfoin_dropbox_file_request($endpoint, $method = 'POST', $data = [], $body = null, $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('dropbox', $cred_id);
    $access_token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';
    $url = 'https://content.dropboxapi.com' . $endpoint;

    $args = [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Dropbox-API-Arg' => json_encode($data),
            'Content-Type' => 'application/octet-stream',
        ],
        'body' => $body
    ];

    $result = wp_remote_request($url, $args);

    if (!is_wp_error($result)) {
        return $result;
    }
}

function adfoin_dropbox_request($endpoint, $method = 'POST', $data = [], $body = null, $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('dropbox', $cred_id);
    $access_token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';
    $url = 'https://api.dropboxapi.com' . $endpoint;

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ],
        'body' => json_encode($data)
    ];

    if ($body) {
        $args['body'] = $body;
    }

    $result = wp_remote_request($url, $args);

    if (!is_wp_error($result)) {
        return $result;
    }
}
