<?php
add_filter('adfoin_action_providers', 'adfoin_smartlead_actions', 10, 1);
function adfoin_smartlead_actions($actions) {
    $actions['smartlead'] = [
        'title' => __('Smartlead.ai', 'advanced-form-integration'),
        'tasks' => ['add_lead' => __('Add Lead to Campaign', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_smartlead_settings_tab', 10, 1);
function adfoin_smartlead_settings_tab($providers) {
    $providers['smartlead'] = __('Smartlead', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_smartlead_settings_view', 10, 1);
function adfoin_smartlead_settings_view($current_tab) {
    if ($current_tab !== 'smartlead') return;

    $title = __('Smartlead.ai', 'advanced-form-integration');
    $key = 'smartlead';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Get your Smartlead API Key from your Smartlead dashboard > API.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_smartlead_credentials', 'adfoin_get_smartlead_credentials');
function adfoin_get_smartlead_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('smartlead'));
}

add_action('wp_ajax_adfoin_save_smartlead_credentials', 'adfoin_save_smartlead_credentials');
function adfoin_save_smartlead_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'smartlead') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('smartlead', $data);
    }

    wp_send_json_success();
}

function adfoin_smartlead_credentials_list() {
    foreach (adfoin_read_credentials('smartlead') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_smartlead_campaigns', 'adfoin_get_smartlead_campaigns');
function adfoin_get_smartlead_campaigns() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $response = adfoin_smartlead_request('campaigns', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error();
    }

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error();
    }

    $campaigns = wp_list_pluck($json, 'name', 'id');
    wp_send_json_success($campaigns);
}

add_action('adfoin_smartlead_job_queue', 'adfoin_smartlead_job_queue', 10, 1);
function adfoin_smartlead_job_queue($data) {
    adfoin_smartlead_send_data($data['record'], $data['posted_data']);
}

function adfoin_smartlead_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data     = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id  = isset($data['credId']) ? $data['credId'] : '';
    $campaign = isset($data['campaignId']) ? $data['campaignId'] : '';
    unset($data['credId'], $data['campaignId']);

    $lead = array();
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $lead[$key] = $parsed;
        }
    }

    $body = array(
        'lead_list' => array($lead),
        'settings' => array(
            'ignore_global_block_list' => true,
            'ignore_unsubscribe_list' => true,
            'ignore_community_bounce_list' => true,
            'ignore_duplicate_leads_in_other_campaign' => true
        )
    );

    adfoin_smartlead_request("campaigns/$campaign/leads", 'POST', $body, $record, $cred_id);
}

function adfoin_smartlead_request($endpoint, $method = 'POST', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('smartlead', $cred_id);
    $api_key     = $credentials['apiKey'] ? $credentials['apiKey'] : '';

    $url = 'https://server.smartlead.ai/api/v1/' . ltrim($endpoint, '/') . '?api_key=' . urlencode($api_key);

    $args = [
        'method'  => $method,
        'timeout' => 30,
        'headers' => [
            'Accept'    => 'application/json',
            'Content-Type' => 'application/json'
        ]
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

add_action( 'adfoin_action_fields', 'adfoin_saleshandy_action_fields' );

function adfoin_saleshandy_action_fields() {
?>
    <script type="text/template" id="smartlead-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_lead'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'add_lead'">
                <td><label><?php _e('Smartlead Account', 'advanced-form-integration'); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getCampaigns">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php adfoin_smartlead_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'add_lead'">
                <td><label><?php _e('Campaign', 'advanced-form-integration'); ?></label></td>
                <td>
                    <select name="fieldData[campaignId]" v-model="fielddata.campaignId" required>
                        <option value=""><?php _e('Select Campaign...', 'advanced-form-integration'); ?></option>
                        <option v-for="(name, id) in fielddata.campaigns" :value="id">{{ name }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': campaignLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
