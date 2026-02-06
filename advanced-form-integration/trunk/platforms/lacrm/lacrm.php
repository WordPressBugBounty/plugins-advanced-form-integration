<?php
add_filter('adfoin_action_providers', 'adfoin_lacrm_actions', 10, 1);
function adfoin_lacrm_actions($actions) {
    $actions['lacrm'] = [
        'title' => __('Less Annoying CRM', 'advanced-form-integration'),
        'tasks' => [
            'add_contact' => __('Add Contact', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_lacrm_settings_tab', 10, 1);
function adfoin_lacrm_settings_tab($providers) {
    $providers['lacrm'] = __('Less Annoying CRM', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_lacrm_settings_view', 10, 1);
function adfoin_lacrm_settings_view($current_tab) {
    if ($current_tab !== 'lacrm') return;

    $title = __('Less Annoying CRM', 'advanced-form-integration');
    $key = 'lacrm';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiToken', 'label' => __('API Token', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Provide your Less Annoying CRM API Token.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_lacrm_credentials', 'adfoin_get_lacrm_credentials');
function adfoin_get_lacrm_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('lacrm'));
}

add_action('wp_ajax_adfoin_save_lacrm_credentials', 'adfoin_save_lacrm_credentials');
function adfoin_save_lacrm_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'lacrm') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('lacrm', $data);
    }

    wp_send_json_success();
}

function adfoin_lacrm_credentials_list() {
    foreach (adfoin_read_credentials('lacrm') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_action('adfoin_lacrm_job_queue', 'adfoin_lacrm_job_queue', 10, 1);
function adfoin_lacrm_job_queue($data) {
    adfoin_lacrm_send_data($data['record'], $data['posted_data']);
}

function adfoin_lacrm_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $user_id = isset($data['userId']) ? $data['userId'] : '';
    $company_fields = [];
    $contact_fields = [];

    unset($data['credId'], $data['userId']);

    $fields = array();
    foreach ($data as $key => $value) {
        if (!empty($value)) {
            $value = adfoin_get_parsed_values($value, $posted_data);
            if (!empty($value)) {
                $fields[$key] = $value;
                if (strpos($key, 'company__') === 0) {
                    $company_fields[str_replace('company__', '', $key)] = $value;
                } else {
                    $contact_fields[$key] = $value;
                }
            }
        }
    }

    $credentials = adfoin_get_credentials_by_id('lacrm', $cred_id);
    $api_token = isset($credentials['apiToken']) ? $credentials['apiToken'] : '';

    // Search or Edit/Create Company
    $company_id = null;
    if (!empty($company_fields['Company Name'])) {
        $company_id = adfoin_lacrm_search('company', $company_fields['Company Name'], $api_token);
        if ($company_id) {
            // Edit existing company
            $company_params = array_filter([
                'ContactId' => $company_id,
                'AssignedTo' => $user_id,
                'Company Name' => $company_fields['Company Name'],
                'Email' => isset($company_fields['Email']) ? [['Text' => $company_fields['Email'], 'Type' => 'Work']] : null,
                'Phone' => isset($company_fields['Phone']) ? [['Text' => $company_fields['Phone'], 'Type' => 'Work']] : null,
                'Background Info' => isset($company_fields['Background Info']) ? $company_fields['Background Info'] : '',
                'Website' => isset($company_fields['Website']) ? $company_fields['Website'] : '',
                'Address' => array_filter([
                    'Street' => isset($company_fields['address__Street']) ? $company_fields['address__Street'] : '',
                    'City' => isset($company_fields['address_City']) ? $company_fields['address_City'] : '',
                    'State' => isset($company_fields['address_State']) ? $company_fields['address_State'] : '',
                    'Zip' => isset($company_fields['address_Zip']) ? $company_fields['address_Zip'] : '',
                    'Country' => isset($company_fields['address_Country']) ? $company_fields['address_Country'] : ''
                ])
            ]);

            adfoin_lacrm_request('EditContact', $company_params, $api_token, $record);
        } else {
            // Create new company
            $company_params = array_filter([
                'IsCompany' => true,
                'AssignedTo' => $user_id,
                'Company Name' => $company_fields['Company Name'],
                'Email' => isset($company_fields['Email']) ? [['Text' => $company_fields['Email'], 'Type' => 'Work']] : null,
                'Phone' => isset($company_fields['Phone']) ? [['Text' => $company_fields['Phone'], 'Type' => 'Work']] : null,
                'Background Info' => isset($company_fields['Background Info']) ? $company_fields['Background Info'] : '',
                'Website' => isset($company_fields['Website']) ? $company_fields['Website'] : '',
                'Address' => array_filter([
                    'Street' => isset($company_fields['address__Street']) ? $company_fields['address__Street'] : '',
                    'City' => isset($company_fields['address_City']) ? $company_fields['address_City'] : '',
                    'State' => isset($company_fields['address_State']) ? $company_fields['address_State'] : '',
                    'Zip' => isset($company_fields['address_Zip']) ? $company_fields['address_Zip'] : '',
                    'Country' => isset($company_fields['address_Country']) ? $company_fields['address_Country'] : ''
                ])
            ]);

            $response = adfoin_lacrm_request('CreateContact', $company_params, $api_token, $record);
            $company_id = isset($response['ContactId']) ? $response['ContactId'] : null;
        }
    }

    // Search or Edit/Add Contact
    $contact_id = null;
    if (!empty($contact_fields['Email'])) {
        $contact_id = adfoin_lacrm_search('contact', $contact_fields['Email'], $api_token);
        if ($contact_id) {
            // Edit existing contact
            $contact_params = array_filter([
                'ContactId' => $contact_id,
                'AssignedTo' => $user_id,
                'Name' => $contact_fields['Name'],
                'Job Title' => isset($contact_fields['Job Title']) ? $contact_fields['Job Title'] : '',
                'Email' => isset($contact_fields['Email']) ? [['Text' => $contact_fields['Email'], 'Type' => 'Work']] : null,
                'Phone' => isset($contact_fields['Phone']) ? [['Text' => $contact_fields['Phone'], 'Type' => 'Work']] : null,
                'Background Info' => isset($contact_fields['Background Info']) ? $contact_fields['Background Info'] : '',
                'Website' => isset($contact_fields['Website']) ? $contact_fields['Website'] : '',
                'Address' => array_filter([
                    'Street' => isset($contact_fields['address__Street']) ? $contact_fields['address__Street'] : '',
                    'City' => isset($contact_fields['address_City']) ? $contact_fields['address_City'] : '',
                    'State' => isset($contact_fields['address_State']) ? $contact_fields['address_State'] : '',
                    'Zip' => isset($contact_fields['address_Zip']) ? $contact_fields['address_Zip'] : '',
                    'Country' => isset($contact_fields['address_Country']) ? $contact_fields['address_Country'] : ''
                ])
            ]);

            adfoin_lacrm_request('EditContact', $contact_params, $api_token, $record);
        } else {
            // Add new contact
            $contact_params = array_filter([
                'AssignedTo' => $user_id,
                'Name' => $contact_fields['Name'],
                'Job Title' => isset($contact_fields['Job Title']) ? $contact_fields['Job Title'] : '',
                'Email' => isset($contact_fields['Email']) ? [['Text' => $contact_fields['Email'], 'Type' => 'Work']] : null,
                'Phone' => isset($contact_fields['Phone']) ? [['Text' => $contact_fields['Phone'], 'Type' => 'Work']] : null,
                'Background Info' => isset($contact_fields['Background Info']) ? $contact_fields['Background Info'] : '',
                'Website' => isset($contact_fields['Website']) ? $contact_fields['Website'] : '',
                'Address' => array_filter([
                    'Street' => isset($contact_fields['address__Street']) ? $contact_fields['address__Street'] : '',
                    'City' => isset($contact_fields['address_City']) ? $contact_fields['address_City'] : '',
                    'State' => isset($contact_fields['address_State']) ? $contact_fields['address_State'] : '',
                    'Zip' => isset($contact_fields['address_Zip']) ? $contact_fields['address_Zip'] : '',
                    'Country' => isset($contact_fields['address_Country']) ? $contact_fields['address_Country'] : ''
                ])
            ]);

            $contact_params['IsCompany'] = false;

            if ($company_id) {
                $contact_params['Company Name'] = $company_fields['Company Name'];
            }

            $response = adfoin_lacrm_request('CreateContact', $contact_params, $api_token, $record);
            $contact_id = isset($response['ContactId']) ? $response['ContactId'] : null;
        }
    }
}

function adfoin_lacrm_search($type, $search_term, $api_token) {
    $parameters = array(
        'SearchTerms' => $search_term,
        'RecordTypeFilter' => $type === 'company' ? 'Companies' : 'Contacts',
        'MaxNumberOfResults' => 1
    );

    $response = adfoin_lacrm_request('GetContacts', $parameters, $api_token);

    if (isset($response['Results']) && !empty($response['Results'])) {
        return isset($response['Results'][0]['ContactId']) ? $response['Results'][0]['ContactId'] : false;
    }

    return false;
}

function adfoin_lacrm_request($function, $parameters, $api_token, $record = []) {
    $url = 'https://api.lessannoyingcrm.com/v2/';
    $headers = [
        'Content-Type: application/json',
        'Authorization' => $api_token
    ];
    $body = [
        'Function' => $function,
        'Parameters' => $parameters
    ];

    $response = wp_remote_post($url, [
        'headers' => $headers,
        'body' => $body,
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $response_body = wp_remote_retrieve_body($response);

    if($record) {
        adfoin_add_to_log($response, $url, $body, $record);
    }

    return json_decode($response_body, true);
}

add_action('wp_ajax_adfoin_get_lacrm_users', 'adfoin_get_lacrm_users');
function adfoin_get_lacrm_users() {
    if (!adfoin_verify_nonce()) {
        wp_send_json_error(['message' => __('Invalid nonce', 'advanced-form-integration')]);
        return;
    }

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $credentials = adfoin_get_credentials_by_id('lacrm', $cred_id);
    $api_token = isset($credentials['apiToken']) ? $credentials['apiToken'] : '';

    if (empty($api_token)) {
        wp_send_json_error(['message' => __('Invalid credentials', 'advanced-form-integration')]);
        return;
    }

    $response = adfoin_lacrm_request('GetUsers', [], $api_token);

    if (is_array($response)) {
        $users = [];
        foreach ($response as $user) {
            $users[$user['UserId']] = $user['FirstName'] . ' ' . $user['LastName'];
        }
        wp_send_json_success($users);
    } else {
        wp_send_json_error(['message' => __('Failed to fetch users', 'advanced-form-integration')]);
    }
}

add_action('adfoin_action_fields', 'adfoin_lacrm_action_fields');
function adfoin_lacrm_action_fields() {
?>
<script type="text/template" id="lacrm-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_contact'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row"></td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td scope="row"><?php _e('Less Annoying CRM Account', 'advanced-form-integration'); ?></td>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getUsers" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_lacrm_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td scope="row"><?php esc_attr_e('Assign to', 'advanced-form-integration'); ?></td>
            <td>
                <select name="fieldData[userId]" v-model="fielddata.userId" required>
                    <option value=""><?php _e('Select...', 'advanced-form-integration'); ?></option>
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