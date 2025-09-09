<?php

add_filter('adfoin_action_providers', 'adfoin_followupboss_actions', 10, 1);

function adfoin_followupboss_actions($actions) {
    $actions['followupboss'] = array(
        'title' => 'FollowUpBoss',
        'tasks' => array(
            'create_contact' => 'Create Contact'
        )
    );
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_followupboss_settings_tab', 10, 1);

function adfoin_followupboss_settings_tab($providers) {
    $providers['followupboss'] = 'FollowUpBoss';
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_followupboss_settings_view', 10, 1);

function adfoin_followupboss_settings_view($current_tab) {
    if ($current_tab !== 'followupboss') return;

    $title = __('FollowUpBoss', 'advanced-form-integration');
    $key = 'followupboss';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Go to your FollowUpBoss account settings to get your API Key. You can find it under Settings > API in your FollowUpBoss dashboard.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('adfoin_action_fields', 'adfoin_followupboss_action_fields');

function adfoin_followupboss_action_fields() {
    ?>
    <script type="text/template" id="followupboss-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row">
                    <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('FollowUpBoss Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_followupboss_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_followupboss_credentials', 'adfoin_get_followupboss_credentials');
function adfoin_get_followupboss_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('followupboss'));
}

add_action('wp_ajax_adfoin_save_followupboss_credentials', 'adfoin_save_followupboss_credentials');
function adfoin_save_followupboss_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'followupboss') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('followupboss', $data);
    }

    wp_send_json_success();
}

add_action('wp_ajax_adfoin_get_followupboss_fields', 'adfoin_get_followupboss_fields');

function adfoin_get_followupboss_fields() {
    if (!adfoin_verify_nonce()) return;
    
    $fields = [];
    
    // Required fields for FollowUpBoss contact creation
    array_push($fields, array('key' => 'firstName', 'value' => 'First Name', 'description' => 'Contact first name'));
    array_push($fields, array('key' => 'lastName', 'value' => 'Last Name', 'description' => 'Contact last name'));
    
    // Email fields
    array_push($fields, array('key' => 'email', 'value' => 'Email', 'description' => 'Primary email address'));
    array_push($fields, array('key' => 'secondary_email', 'value' => 'Secondary Email', 'description' => 'Secondary email address'));
    
    // Phone fields
    array_push($fields, array('key' => 'phone', 'value' => 'Phone', 'description' => 'Primary phone number'));
    array_push($fields, array('key' => 'mobile_phone', 'value' => 'Mobile Phone', 'description' => 'Mobile phone number'));
    array_push($fields, array('key' => 'work_phone', 'value' => 'Work Phone', 'description' => 'Work phone number'));
    array_push($fields, array('key' => 'home_phone', 'value' => 'Home Phone', 'description' => 'Home phone number'));
    array_push($fields, array('key' => 'fax', 'value' => 'Fax', 'description' => 'Fax number'));
    
    // Address fields
    array_push($fields, array('key' => 'address', 'value' => 'Address', 'description' => 'Street address'));
    array_push($fields, array('key' => 'city', 'value' => 'City', 'description' => 'City'));
    array_push($fields, array('key' => 'state', 'value' => 'State', 'description' => 'State or province'));
    array_push($fields, array('key' => 'zip', 'value' => 'ZIP Code', 'description' => 'ZIP or postal code'));
    array_push($fields, array('key' => 'country', 'value' => 'Country', 'description' => 'Country'));
    
    // Lead source and status
    array_push($fields, array('key' => 'source', 'value' => 'Source', 'description' => 'The source of the lead'));
    array_push($fields, array('key' => 'stage', 'value' => 'Stage', 'description' => 'Contact stage in pipeline'));
    array_push($fields, array('key' => 'contacted', 'value' => 'Contacted', 'description' => 'Whether the person has been contacted or not (true/false)'));
    array_push($fields, array('key' => 'price', 'value' => 'Price', 'description' => 'The price of the property of the person\'s first inquiry, or the estimated sell/buy price for this person'));
    
    // Assignment
    array_push($fields, array('key' => 'assignedTo', 'value' => 'Assigned To', 'description' => 'Full name of the agent assigned to this person'));
    array_push($fields, array('key' => 'assignedUserId', 'value' => 'Assigned User ID', 'description' => 'ID of the agent assigned to this person'));
    array_push($fields, array('key' => 'assignedPondId', 'value' => 'Assigned Pond ID', 'description' => 'ID of the pond assigned to this person'));
    array_push($fields, array('key' => 'assignedLenderName', 'value' => 'Assigned Lender Name', 'description' => 'Full name of the lender assigned to this person'));
    array_push($fields, array('key' => 'assignedLenderId', 'value' => 'Assigned Lender ID', 'description' => 'ID of the lender assigned to this person'));
    
    // Social and web
    array_push($fields, array('key' => 'website', 'value' => 'Website', 'description' => 'Website URL'));
    array_push($fields, array('key' => 'facebook', 'value' => 'Facebook', 'description' => 'Facebook profile URL'));
    array_push($fields, array('key' => 'twitter', 'value' => 'Twitter', 'description' => 'Twitter handle'));
    array_push($fields, array('key' => 'linkedin', 'value' => 'LinkedIn', 'description' => 'LinkedIn profile URL'));
    array_push($fields, array('key' => 'instagram', 'value' => 'Instagram', 'description' => 'Instagram handle'));
    
    // Additional fields
    array_push($fields, array('key' => 'birthday', 'value' => 'Birthday', 'description' => 'Birthday (YYYY-MM-DD format)'));
    array_push($fields, array('key' => 'spouse', 'value' => 'Spouse', 'description' => 'Spouse name'));
    array_push($fields, array('key' => 'anniversary', 'value' => 'Anniversary', 'description' => 'Anniversary date (YYYY-MM-DD format)'));
    array_push($fields, array('key' => 'notes', 'value' => 'Notes', 'description' => 'Additional notes'));
    array_push($fields, array('key' => 'background', 'value' => 'Background', 'description' => 'Background information on the person (can be a multi-line string)'));
    
    wp_send_json_success($fields);
}


function adfoin_followupboss_credentials_list() {
    foreach (adfoin_read_credentials('followupboss') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

function adfoin_followupboss_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('followupboss', $cred_id);
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';

    if (!$api_key) {
        return;
    }

    $base_url = 'https://api.followupboss.com/v1/';
    $url = $base_url . $endpoint;
    
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
            'Content-Type'  => 'application/json',
        ),
    );

    if ($method === 'POST' || $method === 'PUT') {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

function adfoin_followupboss_create_contact($fields, $record, $cred_id) {
    $contact_data = array();

    // createdAt
    if (!empty($fields['createdAt'])) {
        $contact_data['createdAt'] = $fields['createdAt'];
    }

    // Name fields
    if (!empty($fields['firstName'])) {
        $contact_data['firstName'] = $fields['firstName'];
    }
    if (!empty($fields['lastName'])) {
        $contact_data['lastName'] = $fields['lastName'];
    }

    // Stage, Source, SourceUrl
    if (!empty($fields['stage'])) {
        $contact_data['stage'] = $fields['stage'];
    }
    if (!empty($fields['source'])) {
        $contact_data['source'] = $fields['source'];
    }
    if (!empty($fields['sourceUrl'])) {
        $contact_data['sourceUrl'] = $fields['sourceUrl'];
    }

    // Contacted
    if (isset($fields['contacted'])) {
        $contact_data['contacted'] = filter_var($fields['contacted'], FILTER_VALIDATE_BOOLEAN);
    }

    // Price
    if (isset($fields['price']) && $fields['price'] !== '') {
        $contact_data['price'] = floatval($fields['price']);
    }

    // Assignment
    if (!empty($fields['assignedTo'])) {
        $contact_data['assignedTo'] = $fields['assignedTo'];
    }
    if (!empty($fields['assignedUserId'])) {
        $contact_data['assignedUserId'] = intval($fields['assignedUserId']);
    }
    if (!empty($fields['assignedPondId'])) {
        $contact_data['assignedPondId'] = intval($fields['assignedPondId']);
    }
    if (!empty($fields['assignedLenderName'])) {
        $contact_data['assignedLenderName'] = $fields['assignedLenderName'];
    }
    if (!empty($fields['assignedLenderId'])) {
        $contact_data['assignedLenderId'] = intval($fields['assignedLenderId']);
    }

    // Emails
    $emails = array();
    if (!empty($fields['email'])) {
        $emails[] = array(
            'value' => $fields['email'],
            'type' => !empty($fields['email_type']) ? $fields['email_type'] : 'work',
            'isPrimary' => true
        );
    }
    if (!empty($fields['secondary_email'])) {
        $emails[] = array(
            'value' => $fields['secondary_email'],
            'type' => !empty($fields['secondary_email_type']) ? $fields['secondary_email_type'] : 'other',
            'isPrimary' => false
        );
    }
    if (!empty($emails)) {
        $contact_data['emails'] = $emails;
    }

    // Phones
    $phones = array();
    if (!empty($fields['phone'])) {
        $phones[] = array(
            'value' => $fields['phone'],
            'type' => !empty($fields['phone_type']) ? $fields['phone_type'] : 'work',
            'isPrimary' => true
        );
    }
    if (!empty($fields['mobile_phone'])) {
        $phones[] = array(
            'value' => $fields['mobile_phone'],
            'type' => !empty($fields['mobile_phone_type']) ? $fields['mobile_phone_type'] : 'mobile',
            'isPrimary' => false
        );
    }
    if (!empty($fields['work_phone'])) {
        $phones[] = array(
            'value' => $fields['work_phone'],
            'type' => 'work',
            'isPrimary' => false
        );
    }
    if (!empty($fields['home_phone'])) {
        $phones[] = array(
            'value' => $fields['home_phone'],
            'type' => 'home',
            'isPrimary' => false
        );
    }
    if (!empty($fields['fax'])) {
        $phones[] = array(
            'value' => $fields['fax'],
            'type' => 'fax',
            'isPrimary' => false
        );
    }
    if (!empty($phones)) {
        $contact_data['phones'] = $phones;
    }

    // Addresses
    $addresses = array();
    if (!empty($fields['address']) || !empty($fields['city']) || !empty($fields['state']) || !empty($fields['zip']) || !empty($fields['country'])) {
        $addresses[] = array(
            'type' => !empty($fields['address_type']) ? $fields['address_type'] : 'home',
            'street' => !empty($fields['address']) ? $fields['address'] : '',
            'city' => !empty($fields['city']) ? $fields['city'] : '',
            'state' => !empty($fields['state']) ? $fields['state'] : '',
            'code' => !empty($fields['zip']) ? $fields['zip'] : '',
            'country' => !empty($fields['country']) ? $fields['country'] : ''
        );
    }
    if (!empty($addresses)) {
        $contact_data['addresses'] = $addresses;
    }

    // Tags
    if (!empty($fields['tags'])) {
        if (is_array($fields['tags'])) {
            $contact_data['tags'] = $fields['tags'];
        } else {
            $contact_data['tags'] = array_map('trim', explode(',', $fields['tags']));
        }
    }

    // Background
    if (!empty($fields['background'])) {
        $contact_data['background'] = $fields['background'];
    }

    // Collaborators
    if (!empty($fields['collaborators'])) {
        if (is_array($fields['collaborators'])) {
            $contact_data['collaborators'] = array_map('intval', $fields['collaborators']);
        } else {
            $contact_data['collaborators'] = array_map('intval', explode(',', $fields['collaborators']));
        }
    }

    // Custom fields (fields starting with 'custom')
    foreach ($fields as $key => $value) {
        if (strpos($key, 'custom') === 0 && $value !== '') {
            $contact_data[$key] = $value;
        }
    }

    // TimeframeId
    if (!empty($fields['timeframeId'])) {
        $contact_data['timeframeId'] = intval($fields['timeframeId']);
    }

    // Notes
    if (!empty($fields['notes'])) {
        $contact_data['notes'] = $fields['notes'];
    }

    // Anniversary, Birthday, Spouse, Social, Website
    if (!empty($fields['anniversary'])) {
        $contact_data['anniversary'] = $fields['anniversary'];
    }
    if (!empty($fields['birthday'])) {
        $contact_data['birthday'] = $fields['birthday'];
    }
    if (!empty($fields['spouse'])) {
        $contact_data['spouse'] = $fields['spouse'];
    }
    if (!empty($fields['website'])) {
        $contact_data['website'] = $fields['website'];
    }
    if (!empty($fields['facebook'])) {
        $contact_data['facebook'] = $fields['facebook'];
    }
    if (!empty($fields['twitter'])) {
        $contact_data['twitter'] = $fields['twitter'];
    }
    if (!empty($fields['linkedin'])) {
        $contact_data['linkedin'] = $fields['linkedin'];
    }
    if (!empty($fields['instagram'])) {
        $contact_data['instagram'] = $fields['instagram'];
    }

    // Send request to FollowUpBoss API
    return adfoin_followupboss_request('people?deduplicate=false', 'POST', $contact_data, $record, $cred_id);
}

add_action('adfoin_followupboss_job_queue', 'adfoin_followupboss_job_queue', 10, 1);

function adfoin_followupboss_job_queue($data) {
    adfoin_followupboss_send_data($data['record'], $data['posted_data']);
}

function adfoin_followupboss_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : $record_data;
    $cred_id = isset($data['credId']) ? $data['credId'] : (isset($record['cred_id']) ? $record['cred_id'] : '');
    $task = $record['task'];

    $contact_fields = array();
    foreach ($data as $key => $value) {
        $parsed_value = adfoin_get_parsed_values($value, $posted_data);
        if ($parsed_value !== '' && $parsed_value !== null) {
            $contact_fields[$key] = $parsed_value;
        }
    }

    if ($task == 'create_contact') {
        adfoin_followupboss_create_contact($contact_fields, $record, $cred_id);
    }
}
