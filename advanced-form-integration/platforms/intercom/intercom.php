<?php

add_filter('adfoin_action_providers', 'adfoin_intercom_actions', 10, 1);
function adfoin_intercom_actions($actions) {
    $actions['intercom'] = [
        'title' => __('Intercom', 'advanced-form-integration'),
        'tasks' => [
            'create_contact' => __('Create Contact', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_intercom_settings_tab', 10, 1);
function adfoin_intercom_settings_tab($providers) {
    $providers['intercom'] = __('Intercom', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_intercom_settings_view', 10, 1);
function adfoin_intercom_settings_view($current_tab) {
    if ($current_tab !== 'intercom') return;

    $title = __('Intercom', 'advanced-form-integration');
    $key = 'intercom';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'accessToken', 'label' => __('Access Token', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Go to your Intercom Developer Hub at https://developers.intercom.com/building-apps/docs/setting-up and create an app to get your Access Token. Make sure to add "Read and write all contact data" and "Read and write all ticket data" permissions.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_intercom_credentials', 'adfoin_get_intercom_credentials');
function adfoin_get_intercom_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('intercom'));
}

add_action('wp_ajax_adfoin_save_intercom_credentials', 'adfoin_save_intercom_credentials');
function adfoin_save_intercom_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'intercom') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('intercom', $data);
    }

    wp_send_json_success();
}

add_action('wp_ajax_adfoin_get_intercom_fields', 'adfoin_get_intercom_fields');
function adfoin_get_intercom_fields()
{
    if (!adfoin_verify_nonce()) return;
    
    $fields = [];
    
    // Required contact fields according to Intercom API
    array_push($fields, array('key' => 'email', 'value' => 'Email', 'description' => 'Contact email address (required for identification)'));
    array_push($fields, array('key' => 'external_id', 'value' => 'External ID', 'description' => 'External identifier from your system'));
    
    // Basic contact information
    array_push($fields, array('key' => 'name', 'value' => 'Name', 'description' => 'Full name of the contact'));
    array_push($fields, array('key' => 'phone', 'value' => 'Phone', 'description' => 'Contact phone number'));
    array_push($fields, array('key' => 'role', 'value' => 'Role', 'description' => 'Contact role: user or lead'));
    
    // Contact metadata
    array_push($fields, array('key' => 'owner_id', 'value' => 'Owner ID', 'description' => 'ID of the team member who owns this contact'));
    
    // Avatar and image
    array_push($fields, array('key' => 'avatar_image_url', 'value' => 'Avatar Image URL', 'description' => 'URL to contact avatar image'));
    
    // Location data
    array_push($fields, array('key' => 'location_city_name', 'value' => 'Location City', 'description' => 'Contact city'));
    array_push($fields, array('key' => 'location_continent_code', 'value' => 'Location Continent Code', 'description' => 'Continent code (e.g., EU, NA)'));
    array_push($fields, array('key' => 'location_country_name', 'value' => 'Location Country', 'description' => 'Contact country'));
    array_push($fields, array('key' => 'location_country_code', 'value' => 'Location Country Code', 'description' => 'ISO country code (e.g., US, GB)'));
    array_push($fields, array('key' => 'location_region_name', 'value' => 'Location Region', 'description' => 'Contact region/state'));
    array_push($fields, array('key' => 'location_timezone', 'value' => 'Location Timezone', 'description' => 'Contact timezone'));
    
    // Social profiles
    array_push($fields, array('key' => 'social_profile_name', 'value' => 'Social Profile Name', 'description' => 'Name of social profile (e.g., twitter, facebook)'));
    array_push($fields, array('key' => 'social_profile_url', 'value' => 'Social Profile URL', 'description' => 'URL to social profile'));
    array_push($fields, array('key' => 'social_profile_username', 'value' => 'Social Profile Username', 'description' => 'Username on social platform'));
    array_push($fields, array('key' => 'social_profile_id', 'value' => 'Social Profile ID', 'description' => 'ID on social platform'));
    
    // Company association
    array_push($fields, array('key' => 'company_id', 'value' => 'Company ID', 'description' => 'ID of associated company'));
    
    // Custom attributes (example fields)
    array_push($fields, array('key' => 'custom_source', 'value' => 'Custom: Source', 'description' => 'Custom attribute for lead source'));
    array_push($fields, array('key' => 'custom_industry', 'value' => 'Custom: Industry', 'description' => 'Custom attribute for industry'));
    array_push($fields, array('key' => 'custom_company_size', 'value' => 'Custom: Company Size', 'description' => 'Custom attribute for company size'));
    
    wp_send_json_success($fields);
}

function adfoin_intercom_credentials_list() {
    foreach (adfoin_read_credentials('intercom') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

function adfoin_intercom_create_contact($fields, $record, $cred_id, $role = 'lead') {
    $contact_data = array();
    
    // Required contact identification fields
    if (!empty($fields['email'])) {
        $contact_data['email'] = $fields['email'];
    }
    
    if (!empty($fields['external_id'])) {
        $contact_data['external_id'] = $fields['external_id'];
    }
    
    // Basic contact fields
    if (!empty($fields['name'])) {
        $contact_data['name'] = $fields['name'];
    }
    
    if (!empty($fields['phone'])) {
        $contact_data['phone'] = $fields['phone'];
    }
    
    // Set role (lead or user) - use field value if provided, otherwise use parameter
    if (!empty($fields['role'])) {
        $contact_data['role'] = $fields['role'];
    } else {
        $contact_data['role'] = $role;
    }
    
    // Contact metadata
    if (!empty($fields['signed_up_at'])) {
        $contact_data['signed_up_at'] = intval($fields['signed_up_at']);
    }
    
    if (!empty($fields['last_seen_at'])) {
        $contact_data['last_seen_at'] = intval($fields['last_seen_at']);
    }
    
    if (!empty($fields['owner_id'])) {
        $contact_data['owner_id'] = intval($fields['owner_id']);
    }
    
    if (!empty($fields['unsubscribed_from_emails'])) {
        $contact_data['unsubscribed_from_emails'] = filter_var($fields['unsubscribed_from_emails'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Avatar image
    if (!empty($fields['avatar_image_url'])) {
        $contact_data['avatar'] = array('image_url' => $fields['avatar_image_url']);
    }
    
    // Location data
    $location_data = array();
    if (!empty($fields['location_city_name'])) {
        $location_data['city_name'] = $fields['location_city_name'];
    }
    if (!empty($fields['location_continent_code'])) {
        $location_data['continent_code'] = $fields['location_continent_code'];
    }
    if (!empty($fields['location_country_name'])) {
        $location_data['country_name'] = $fields['location_country_name'];
    }
    if (!empty($fields['location_country_code'])) {
        $location_data['country_code'] = $fields['location_country_code'];
    }
    if (!empty($fields['location_region_name'])) {
        $location_data['region_name'] = $fields['location_region_name'];
    }
    if (!empty($fields['location_timezone'])) {
        $location_data['timezone'] = $fields['location_timezone'];
    }
    
    if (!empty($location_data)) {
        $contact_data['location_data'] = $location_data;
    }
    
    // Social profiles
    $social_profiles = array();
    if (!empty($fields['social_profile_name']) && !empty($fields['social_profile_url'])) {
        $social_profile = array(
            'name' => $fields['social_profile_name'],
            'url' => $fields['social_profile_url']
        );
        
        if (!empty($fields['social_profile_username'])) {
            $social_profile['username'] = $fields['social_profile_username'];
        }
        
        if (!empty($fields['social_profile_id'])) {
            $social_profile['id'] = $fields['social_profile_id'];
        }
        
        $social_profiles[] = $social_profile;
    }
    
    if (!empty($social_profiles)) {
        $contact_data['social_profiles'] = $social_profiles;
    }
    
    // Custom attributes
    $custom_attributes = array();
    foreach ($fields as $key => $value) {
        if (strpos($key, 'custom_') === 0 && !empty($value)) {
            $custom_field = str_replace('custom_', '', $key);
            $custom_attributes[$custom_field] = $value;
        }
    }
    
    if (!empty($custom_attributes)) {
        $contact_data['custom_attributes'] = $custom_attributes;
    }
    
    // Company association
    if (!empty($fields['company_id'])) {
        $contact_data['companies'] = array(
            array('company_id' => $fields['company_id'])
        );
    }
    
    return adfoin_intercom_request('contacts', 'POST', $contact_data, $record, $cred_id);
}


function adfoin_intercom_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('intercom', $cred_id);
    $access_token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';

    $base_url = 'https://api.intercom.io/';
    $url = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization'    => 'Bearer ' . $access_token,
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Intercom-Version' => '2.8'
        )
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

add_action('adfoin_action_fields', 'adfoin_intercom_action_fields');

function adfoin_intercom_action_fields() {
    ?>
    <script type="text/template" id="intercom-action-template">
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
                        <?php esc_attr_e('Intercom Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_intercom_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_intercom_job_queue($data) {
    adfoin_intercom_send_data($data['record'], $data['posted_data']);
}

function adfoin_intercom_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : $record_data;
    $cred_id = isset($data['credId']) ? $data['credId'] : (isset($record['cred_id']) ? $record['cred_id'] : '');
    $task = $record['task'];

    $contact_fields = array();
    foreach ($data as $key => $value) {
        $contact_fields[$key] = adfoin_get_parsed_values($value, $posted_data);
    }

    if ($task == 'create_contact') {
        // Use role from field data if provided, otherwise default to 'lead'
        $role = !empty($contact_fields['role']) ? $contact_fields['role'] : 'lead';

        $contact_response = adfoin_intercom_create_contact($contact_fields, $record, $cred_id, $role);
    }
}
