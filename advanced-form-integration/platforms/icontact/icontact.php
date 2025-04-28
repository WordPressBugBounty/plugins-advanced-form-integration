<?php
add_filter('adfoin_action_providers', 'adfoin_icontact_actions', 10, 1);
function adfoin_icontact_actions($actions) {
    $actions['icontact'] = [
        'title' => __('iContact', 'advanced-form-integration'),
        'tasks' => ['subscribe' => __('Add Contact', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_icontact_settings_tab', 10, 1);
function adfoin_icontact_settings_tab($providers) {
    $providers['icontact'] = __('iContact', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_icontact_settings_view', 10, 1);
function adfoin_icontact_settings_view($current_tab) {
    if ($current_tab !== 'icontact') return;

    $title = __('iContact', 'advanced-form-integration');
    $key = 'icontact';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'appId', 'label' => __('App ID', 'advanced-form-integration'), 'hidden' => true],
            ['key' => 'username', 'label' => __('API Username', 'advanced-form-integration'), 'hidden' => true],
            ['key' => 'password', 'label' => __('API Password', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Go to Settings > iContact Integrations > Custom API Integrations > Create', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_icontact_credentials', 'adfoin_get_icontact_credentials');
function adfoin_get_icontact_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('icontact'));
}

add_action('wp_ajax_adfoin_save_icontact_credentials', 'adfoin_save_icontact_credentials');
function adfoin_save_icontact_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'icontact') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        $last_index = count($data) - 1;
        $account_id = '';
        $client_folder_id = '';
        $url = "https://app.icontact.com/icp/a/";

        $headers = array(
            'Accept'        => 'application/json',
            'Api-Version'   => '2.2',
            'Api-AppId'     => $data[$last_index]['appId'],
            'Api-Username'  => $data[$last_index]['username'],
            'Api-Password'  => $data[$last_index]['password']
        );

        $accountRes = wp_remote_get($url, array('headers' => $headers));
        $accountResponseBody = json_decode(wp_remote_retrieve_body($accountRes), true);

        if (isset($accountResponseBody['accounts'][0]['accountId'])) {
            $account_id = $accountResponseBody['accounts'][0]['accountId'];
            $data[$last_index]['accountId'] = $account_id;
        } else {
            wp_send_json_error(__('Unable to retrieve account ID.', 'advanced-form-integration'));
            return;
        }

        $folderUrl = "{$url}{$data[$last_index]['accountId']}/c/";
        $folderRes = wp_remote_get($folderUrl, array('headers' => $headers));
        $responseBody = json_decode(wp_remote_retrieve_body($folderRes), true);

        if (isset($responseBody['clientfolders'][0]['clientFolderId'])) {
            $client_folder_id = $responseBody['clientfolders'][0]['clientFolderId'];
            $data[$last_index]['clientFolderId'] = $client_folder_id;
        } else {
            wp_send_json_error(__('Unable to retrieve client folder ID.', 'advanced-form-integration'));
            return;
        }
        adfoin_save_credentials('icontact', $data);
    }

    wp_send_json_success();
}

function adfoin_icontact_credentials_list() {
    foreach (adfoin_read_credentials('icontact') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_icontact_lists', 'adfoin_get_icontact_lists');
function adfoin_get_icontact_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);

    $listRes = adfoin_icontact_request('lists/', 'GET', [], [], $cred_id);
    $lists = json_decode(wp_remote_retrieve_body($listRes), true);

    if (isset($lists['lists'])) {
        $listOptions = wp_list_pluck($lists['lists'], 'name', 'listId');
        wp_send_json_success($listOptions);
    } else {
        wp_send_json_error(__('Unable to retrieve lists.', 'advanced-form-integration'));
    }
}

add_action('adfoin_icontact_job_queue', 'adfoin_icontact_job_queue', 10, 1);
function adfoin_icontact_job_queue($data) {
    adfoin_icontact_send_data($data['record'], $data['posted_data']);
}

function adfoin_icontact_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'];
    $listId = $data['listId'];
    unset($data['credId'], $data['listId']);

    $fields = [];
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $fields[$key] = $parsed;
        }
    }

    // Prepare contact data
    $fields['status'] = 'normal';
    $contactRes = adfoin_icontact_request('contacts/', 'POST', [$fields], $record, $cred_id);
    $contactResponse = json_decode(wp_remote_retrieve_body($contactRes), true);

    if (isset($contactResponse['contacts'][0])) {
        $contact = $contactResponse['contacts'][0];

        // Subscribe to List
        $subscription = [
            'contactId' => (int) $contact['contactId'],
            'listId' => (int) $listId,
            'status' => 'normal'
        ];

        $subscribed = adfoin_icontact_request('subscriptions', 'POST', [$subscription], $record, $cred_id);

        return;
    }
}

add_action( 'adfoin_action_fields', 'adfoin_icontact_action_fields' );

function adfoin_icontact_action_fields() {
?>
<script type="text/template" id="icontact-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'subscribe'">
            <th scope="row">
                <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
            </th>
            <td scope="row">
            </td>
        </tr>
        <tr class="alternate" v-if="action.task == 'subscribe'">
            <td><label><?php _e('iContact Account', 'advanced-form-integration'); ?></label></td>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_icontact_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'subscribe'">
            <td><label><?php _e('Mailing List', 'advanced-form-integration'); ?></label></td>
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

function adfoin_icontact_request($endpoint, $method, $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('icontact', $cred_id);

    $headers = array(
        'Accept'        => 'application/json',
        'Api-Version'   => '2.2',
        'Api-AppId'     => $credentials['appId'],
        'Api-Username'  => $credentials['username'],
        'Api-Password'  => $credentials['password']
    );

    $account_id = isset($credentials['accountId']) ? $credentials['accountId'] : '';
    $client_folder_id = isset($credentials['clientFolderId']) ? $credentials['clientFolderId'] : '';

    $url = "https://app.icontact.com/icp/a/{$account_id}/c/{$client_folder_id}/{$endpoint}";

    $args = array(
        'method'  => $method,
        'headers' => $headers
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = $data;
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $data, $record);
    }

    return $response;
}
