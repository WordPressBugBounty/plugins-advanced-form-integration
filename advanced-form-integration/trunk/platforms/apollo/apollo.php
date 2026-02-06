<?php

add_filter('adfoin_action_providers', 'adfoin_apollo_actions', 10, 1);
function adfoin_apollo_actions($actions) {
    $actions['apollo'] = [
        'title' => __('Apollo.io', 'advanced-form-integration'),
        'tasks' => [
            'add_contact' => __('Add Contact', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_apollo_settings_tab', 10, 1);
function adfoin_apollo_settings_tab($providers) {
    $providers['apollo'] = __('Apollo.io', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_apollo_settings_view', 10, 1);
function adfoin_apollo_settings_view($current_tab) {
    if ($current_tab !== 'apollo') return;

    $title = __('Apollo.io', 'advanced-form-integration');
    $key = 'apollo';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiToken', 'label' => __('API Token', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Go to Admin Settings > Integrations > API and click on Connect. Again go to API Keys and create a new key. Insert a name and click on Create. Copy the API Key.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_apollo_credentials', 'adfoin_get_apollo_credentials');
function adfoin_get_apollo_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('apollo'));
}

add_action('wp_ajax_adfoin_save_apollo_credentials', 'adfoin_save_apollo_credentials');
function adfoin_save_apollo_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'apollo') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('apollo', $data);
    }

    wp_send_json_success();
}

function adfoin_apollo_credentials_list() {
    foreach (adfoin_read_credentials('apollo') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

function adfoin_apollo_add_or_update_contact($fields, $record, $cred_id) {
    // Search for existing contact
    $search_response = adfoin_apollo_request(

        'contacts/search?q_keywords=' . urlencode($fields['email']),
        'POST',
        '',
        $record,
        $cred_id
    );

    $search_body = wp_remote_retrieve_body($search_response);
    $search_data = json_decode($search_body, true);

    if (!empty($search_data['contacts'])) {
        // Update existing contact
        $contact_id = $search_data['contacts'][0]['id'];
        $update_response = adfoin_apollo_request(
            'contacts/' . $contact_id,
            'PUT',
            $fields,
            $record,
            $cred_id
        );
        return $update_response;
    } else {
        // Create new contact
        $create_response = adfoin_apollo_request(
            'contacts',
            'POST',
            $fields,
            $record,
            $cred_id
        );
        return $create_response;
    }
}

function adfoin_apollo_add_or_update_company($fields, $record, $cred_id) {
    // Search for existing contact
    $search_response = adfoin_apollo_request(

        'accounts/search?q_organization_name=' . urlencode($fields['name']),
        'POST',
        '',
        $record,
        $cred_id
    );

    $search_body = wp_remote_retrieve_body($search_response);
    $search_data = json_decode($search_body, true);

    if (!empty($search_data['accounts'])) {
        // Update existing contact
        $company_id = $search_data['accounts'][0]['id'];
        $update_response = adfoin_apollo_request(
            'accounts/' . $company_id,
            'PUT',
            $fields,
            $record,
            $cred_id
        );
        return $update_response;
    } else {
        // Create new contact
        $create_response = adfoin_apollo_request(
            'accounts',
            'POST',
            $fields,
            $record,
            $cred_id
        );
        return $create_response;
    }
}

function adfoin_apollo_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('apollo', $cred_id);
    $api_token = isset($credentials['apiToken']) ? $credentials['apiToken'] : '';
    $url = 'https://api.apollo.io/api/v1/' . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
            'X-Api-Key' => $api_token
        ]
    ];

    if (in_array($method, ['POST', 'PUT'])) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_apollo_users', 'adfoin_get_apollo_users');
function adfoin_get_apollo_users() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $response = adfoin_apollo_request('users/search?per_page=1000', 'GET', [], [], $cred_id);

    if (is_wp_error($response)) wp_send_json_error();

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['users'])) {
        $users = wp_list_pluck($body['users'], 'name', 'id');
        wp_send_json_success($users);
    } else {
        wp_send_json_error(__('Unable to retrieve users.', 'advanced-form-integration'));
    }
}

function adfoin_get_apollo_contact_lists($cred_id) {
    $response = adfoin_apollo_request('labels', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $lists = [];

    if (!empty($body)) {
        $lists = wp_list_pluck($body, 'name');
    }

    return implode(', ', $lists);
}

add_action( 'wp_ajax_adfoin_get_apollo_fields', 'adfoin_get_apollo_fields', 10 );

/*
 * Get Apollo subscriber fields
 */
function adfoin_get_apollo_fields() {
    if ( !adfoin_verify_nonce() ) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );
    $company_stages = adfoin_get_apollo_account_stages( 'account', $cred_id );
    $contact_stages = adfoin_get_apollo_account_stages( 'contact', $cred_id );
    $contact_lists = adfoin_get_apollo_contact_lists( $cred_id );

    $company_fields = array();

    array_push($company_fields, array('key' => 'company__name', 'value' => 'Company Name', 'description' => ''));
    array_push($company_fields, array('key' => 'company__domain', 'value' => 'Company Domain', 'description' => ''));
    array_push($company_fields, array('key' => 'company__account_stage_id', 'value' => 'Company Stage ID', 'description' => $company_stages));
    array_push($company_fields, array('key' => 'company__phone', 'value' => 'Company Phone', 'description' => ''));
    array_push($company_fields, array('key' => 'company__raw_address', 'value' => 'Company Address', 'description' => ''));


    $fields = [];

    array_push($fields, array('key' => 'email', 'value' => 'Email', 'description' => ''));
    array_push($fields, array('key' => 'first_name', 'value' => 'First Name', 'description' => ''));
    array_push($fields, array('key' => 'last_name', 'value' => 'Last Name', 'description' => ''));
    array_push($fields, array('key' => 'title', 'value' => 'Job Title', 'description' => ''));
    array_push($fields, array('key' => 'contact_stage_id', 'value' => 'Contact Stage ID', 'description' => $contact_stages));
    array_push($fields, array('key' => 'website_url', 'value' => 'Website URL', 'description' => ''));
    array_push($fields, array('key' => 'present_raw_address', 'value' => 'Address', 'description' => ''));
    array_push($fields, array('key' => 'direct_phone', 'value' => 'Phone', 'description' => ''));
    array_push($fields, array('key' => 'corporate_phone', 'value' => 'Corporate Phone', 'description' => ''));
    array_push($fields, array('key' => 'mobile_phone', 'value' => 'Mobile Phone', 'description' => ''));
    array_push($fields, array('key' => 'other_phone', 'value' => 'Other Phone', 'description' => ''));
    array_push($fields, array('key' => 'label_names', 'value' => 'Contact Lists', 'description' => $contact_lists));

    $fields = array_merge($fields, $company_fields);

    wp_send_json_success( $fields );
}

function adfoin_get_apollo_account_stages($type, $cred_id) {
    if (!adfoin_verify_nonce()) return;

    $response = adfoin_apollo_request("{$type}_stages", 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $stages_string = '';
    $stages = [];
    if (!empty($body[$type . '_stages'])) {
        $stages = array_map(function ($stage) {
            return $stage['name'] . ': ' . $stage['id'];
        }, $body[$type . '_stages']);
    }
    $stages_string = implode(', ', $stages);
    return $stages_string;
}

add_action('adfoin_action_fields', 'adfoin_apollo_action_fields');
function adfoin_apollo_action_fields() {
?>
<script type="text/template" id="apollo-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_contact'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row">
            <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_contact'">
            <th scope="row"><?php _e('Apollo.io Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_apollo_credentials_list(); ?>
                </select>
            </td>
        </tr>
        <tr class="alternate" v-if="action.task == 'add_contact'">
            <th scope="row"><?php _e('Owner', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[userId]" v-model="fielddata.userId">
                    <option value=""><?php _e('Select Owner...', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.users" :value="id">{{ name }}</option>
                </select>
                <div class="spinner" v-bind:class="{'is-active': userLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

add_action('adfoin_apollo_job_queue', 'adfoin_apollo_job_queue', 10, 1);
function adfoin_apollo_job_queue($data) {
    adfoin_apollo_send_data($data['record'], $data['posted_data']);
}

function adfoin_apollo_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $owner_id = isset($data['userId']) ? $data['userId'] : '';

    unset($data['credId']);

    $fields = array();
    $company_fields = array();

    foreach ($data as $key => $value) {
        if (!empty($value)) {
            $value = adfoin_get_parsed_values($value, $posted_data);

            if (!empty($value)) {
                if (strpos($key, 'company__') === 0) {
                    $company_fields[str_replace('company__', '', $key)] = $value;
                } else {
                    $fields[$key] = $value;
                }
            }
        }
    }

    if (!empty($owner_id)) {
        $company_fields['owner_id'] = $owner_id;
    }

    if (isset($fields['label_names']) && !empty($fields['label_names'])) {
        $fields['label_names'] = explode(',', $fields['label_names']);
    }

    $company_response = adfoin_apollo_add_or_update_company($company_fields, $record, $cred_id);
    $company_body = wp_remote_retrieve_body($company_response);
    $company_data = json_decode($company_body, true);
    $company_id = !empty($company_data['account']['id']) ? $company_data['account']['id'] : '';
    
    if (!empty($company_id)) {
        $fields['account_id'] = $company_id;
    }

    $contact_response = adfoin_apollo_add_or_update_contact( $fields, $record, $cred_id);
}
