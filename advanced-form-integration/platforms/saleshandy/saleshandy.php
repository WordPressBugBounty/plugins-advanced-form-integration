<?php

add_filter('adfoin_action_providers', 'adfoin_saleshandy_actions', 10, 1);
function adfoin_saleshandy_actions($actions) {
    $actions['saleshandy'] = [
        'title' => __('Saleshandy', 'advanced-form-integration'),
        'tasks' => ['add_prospect' => __('Add Prospect to Sequence', 'advanced-form-integration')]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_saleshandy_settings_tab', 10, 1);
function adfoin_saleshandy_settings_tab($providers) {
    $providers['saleshandy'] = __('Saleshandy', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_saleshandy_settings_view', 10, 1);
function adfoin_saleshandy_settings_view($current_tab) {
    if ($current_tab !== 'saleshandy') return;

    $title = __('Saleshandy', 'advanced-form-integration');
    $key = 'saleshandy';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'accessToken', 'label' => __('Bearer Token', 'advanced-form-integration'), 'hidden' => true]
        ]
    ]);
    $instructions = __('Go to Saleshandy account > Setting > API Key and create API Key.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_saleshandy_credentials', 'adfoin_get_saleshandy_credentials');
function adfoin_get_saleshandy_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('saleshandy'));
}

add_action('wp_ajax_adfoin_save_saleshandy_credentials', 'adfoin_save_saleshandy_credentials');
function adfoin_save_saleshandy_credentials() {
    if (!adfoin_verify_nonce()) return;
    $platform = sanitize_text_field($_POST['platform']);
    if ($platform === 'saleshandy') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials($platform, $data);
    }
    wp_send_json_success();
}

function adfoin_saleshandy_credentials_list() {
    foreach (adfoin_read_credentials('saleshandy') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('wp_ajax_adfoin_get_saleshandy_sequences', 'adfoin_get_saleshandy_sequences');
function adfoin_get_saleshandy_sequences() {
    if (!adfoin_verify_nonce()) return;
    $cred_id = sanitize_text_field($_POST['credId']);

    $response = adfoin_saleshandy_request(
        'v1/sequences?pageSize=1000&page=1&sort=ASC&sortBy=sequence.createdAt',
        'GET',
        [],
        [],
        $cred_id
    );

    if (is_wp_error($response)) wp_send_json_error();
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['payload']) || !is_array($data['payload'])) {
        wp_send_json_error();
    }

    $sequences = [];
    foreach ($data['payload'] as $sequence) {
        if (isset($sequence['steps']) && is_array($sequence['steps'])) {
            foreach ($sequence['steps'] as $step) {
                $sequences[$step['id']] = $sequence['title'] . ': ' . $step['name'];
            }
        }
    }

    wp_send_json_success($sequences);
}

add_action('adfoin_saleshandy_job_queue', 'adfoin_saleshandy_job_queue', 10, 1);
function adfoin_saleshandy_job_queue($data) {
    adfoin_saleshandy_send_data($data['record'], $data['posted_data']);
}

function adfoin_saleshandy_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = $data['credId'];
    $stepId = $data['sequenceId']; // Assuming sequenceId maps to stepId
    unset($data['credId'], $data['sequenceId']);

    $fields = [];
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $fields[] = [
                'id' => $key,
                'value' => $parsed
            ];
        }
    }

    $body = [
        'prospectList' => [
            [
                'fields' => $fields
            ]
        ],
        'stepId' => $stepId,
        'verifyProspects' => true,
        'conflictAction' => 'overwrite'
    ];

    adfoin_saleshandy_request("v1/prospects/import", 'POST', $body, $record, $cred_id);
}

function adfoin_saleshandy_request($endpoint, $method, $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('saleshandy', $cred_id);
    $token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';

    $url = "https://leo-open-api-gateway.saleshandy.com/$endpoint";

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'x-api-key'     => $token
        )
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $data, $record);
    }

    return $response;
}

add_action( 'adfoin_action_fields', 'adfoin_saleshandy_action_fields' );

function adfoin_saleshandy_action_fields() {
?>
    <script type="text/template" id="saleshandy-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_prospect'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'add_prospect'">
                <td><label><?php _e('Saleshandy Account', 'advanced-form-integration'); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php adfoin_saleshandy_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'add_prospect'">
                <td><label><?php _e('Sequence', 'advanced-form-integration'); ?></label></td>
                <td>
                    <select name="fieldData[sequenceId]" v-model="fielddata.sequenceId" required>
                        <option value=""><?php _e('Select Sequence...', 'advanced-form-integration'); ?></option>
                        <option v-for="(name, id) in fielddata.sequences" :value="id">{{ name }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': sequenceLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

add_action('wp_ajax_adfoin_get_saleshandy_fields', 'adfoin_get_saleshandy_fields');
function adfoin_get_saleshandy_fields() {
    if (!adfoin_verify_nonce()) return;
    $cred_id = sanitize_text_field($_POST['credId']);

    $response = adfoin_saleshandy_request(
        'v1/fields?systemFields=true',
        'GET',
        [],
        [],
        $cred_id
    );

    if (is_wp_error($response)) wp_send_json_error();
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['payload']) || !is_array($data['payload'])) {
        wp_send_json_error();
    }

    $fields = [];
    foreach ($data['payload'] as $field) {
        $fields[] = [
            'key' => $field['id'],
            'value' => $field['label'],
            'description' => ''
        ];
    }

    wp_send_json_success($fields);
}


