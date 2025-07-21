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
    $arguments = json_encode(array(
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
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('audienceful'));
}

add_action('wp_ajax_adfoin_save_audienceful_credentials', 'adfoin_save_audienceful_credentials');
function adfoin_save_audienceful_credentials() {
    if (!adfoin_verify_nonce()) return;

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

    $data = $record_data['field_data'];
    $cred_id = $data['credId'];
    unset($data['credId']);

    $fields = array();
    foreach ($data as $key => $value) {
        $parsed = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed !== '') {
            $fields[$key] = $parsed;
        }
    }

    $body = array_filter(array(
        'email' => isset($fields['email']) ? $fields['email'] : '',
        'tags'  => isset($fields['tags']) ? $fields['tags'] : '',
        'notes' => isset($fields['notes']) ? $fields['notes'] : '',
    ));

    unset($fields['email'], $fields['tags'], $fields['notes']);

    if (!empty($fields)) {
        $body['extra_data'] = $fields;
    }

    adfoin_audienceful_request('people', 'POST', $body, $record, $cred_id);
}

function adfoin_audienceful_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('audienceful', $cred_id);
    $url = 'https://app.audienceful.com/api/' . ltrim($endpoint, '/');

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'X-Api-Key' => $credentials['apiKey'],
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json'
        )
    );

    if (in_array($method, array('POST', 'PUT'))) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_audienceful_fields', 'adfoin_get_audienceful_fields');
function adfoin_get_audienceful_fields() {
    if (!adfoin_verify_nonce()) {
        wp_send_json_error(array('message' => __('Invalid nonce', 'advanced-form-integration')));
    }

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $task = isset($_POST['task']) ? sanitize_text_field($_POST['task']) : '';

    $fields = array();

    if ($task === 'add_person' && $cred_id) {
        // Use adfoin_audienceful_request to get fields from API
        $response = adfoin_audienceful_request('people/fields/', 'GET', array(), array(), $cred_id);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (is_array($body)) {
                foreach ($body as $field) {
                    if(isset($field['editable']) && $field['editable'] == true) {
                        $fields[] = array(
                            'key' => isset($field['data_name']) ? $field['data_name'] : '',
                            'value' => isset($field['name']) ? $field['name'] : '',
                            'description' => ''
                        );
                    }
                }
            }
        }
    }

    wp_send_json_success($fields);
}

add_action('adfoin_action_fields', 'adfoin_audienceful_action_fields');
function adfoin_audienceful_action_fields() {
?>
<script type="text/template" id="audienceful-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_person'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td>
                <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>
        <tr class="alternate" v-if="action.task == 'add_person'">
            <th scope="row"><?php esc_attr_e('Audienceful Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_audienceful_credentials_list(); ?>
                </select>
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