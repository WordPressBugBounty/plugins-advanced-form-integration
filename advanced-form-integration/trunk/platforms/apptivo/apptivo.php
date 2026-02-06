<?php

define( 'ADFOIN_APPTIVO_KEY', 'apptivo' );

add_filter('adfoin_action_providers', 'adfoin_apptivo_actions', 10, 1);
function adfoin_apptivo_actions($actions) {
    $actions[ADFOIN_APPTIVO_KEY] = [
        'title' => __('Apptivo CRM', 'advanced-form-integration'),
        'tasks' => [
            'create_record' => __('Create Record', 'advanced-form-integration'),
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_apptivo_settings_tab', 10, 1);
function adfoin_apptivo_settings_tab($providers) {
    $providers[ADFOIN_APPTIVO_KEY] = __('Apptivo CRM', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_apptivo_settings_view', 10, 1);
function adfoin_apptivo_settings_view($current_tab) {
    if ($current_tab !== ADFOIN_APPTIVO_KEY) return;

    $title = __('Apptivo CRM', 'advanced-form-integration');
    $key = ADFOIN_APPTIVO_KEY;
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('API Key', 'advanced-form-integration'), 'type' => 'text', 'hidden' => true],
            ['key' => 'accessKey', 'label' => __('Access Key', 'advanced-form-integration'), 'type' => 'text', 'hidden' => true],
        ]
    ]);
    $instructions = __('Configure your Apptivo API access. You will need your API Key and Access Key.', 'advanced-form-integration');
    $instructions .= '<br><br>' . __('Refer to the Apptivo API documentation for details on obtaining your keys.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_apptivo_credentials', 'adfoin_get_apptivo_credentials');
function adfoin_get_apptivo_credentials() {
    if (!adfoin_verify_nonce()) {
        wp_send_json_error( __('Security check failed.', 'advanced-form-integration') );
        return;
    }

    wp_send_json_success(adfoin_read_credentials(ADFOIN_APPTIVO_KEY));
}

add_action('wp_ajax_adfoin_save_apptivo_credentials', 'adfoin_save_apptivo_credentials');
function adfoin_save_apptivo_credentials() {
    if (!adfoin_verify_nonce()) {
        wp_send_json_error( __('Security check failed.', 'advanced-form-integration') );
        return;
    }

    if (isset($_POST['platform']) && $_POST['platform'] === ADFOIN_APPTIVO_KEY && isset($_POST['data'])) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials(ADFOIN_APPTIVO_KEY, $data);
        wp_send_json_success();
    } else {
        wp_send_json_error( __('Invalid request data.', 'advanced-form-integration') );
    }
}

function adfoin_apptivo_credentials_list() {
    $credentials = adfoin_read_credentials(ADFOIN_APPTIVO_KEY);
    if ( ! empty( $credentials ) ) {
        foreach ($credentials as $option) {
            printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
        }
    }
}

add_action('adfoin_' . ADFOIN_APPTIVO_KEY . '_job_queue', 'adfoin_apptivo_job_queue', 10, 1);
function adfoin_apptivo_job_queue($data) {
    if (isset($data['record']) && isset($data['posted_data'])) {
        adfoin_apptivo_send_data($data['record'], $data['posted_data']);
    } else {
        error_log( 'ADFOIN Apptivo CRM Job Queue: Invalid data received.' );
    }
}

function adfoin_apptivo_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $entity_name = isset($data['entityName']) ? sanitize_text_field($data['entityName']) : '';

    unset($data['credId']);
    unset($data['entityName']);
    $fields_to_send = array();

    foreach ($data as $key => $value) {
        if (!empty($value)) {
            $value = adfoin_get_parsed_values($value, $posted_data);
            $value = sanitize_text_field($value);

            if (!empty($value)) {
                $fields_to_send[$key] = $value;
            }
        }
    }

    // Handle specific fields for Leads entity
    if ($entity_name === 'Leads') {
        $lead_specific_fields = [
            'title', 'firstName', 'lastName', 'jobTitle', 'wayToContact', 
            'leadStatusMeaning', 'leadSourceMeaning', 'leadTypeName', 
            'referredByName', 'assigneeObjectRefName', 'description', 
            'potentialAmount', 'leadRankMeaning', 'annualRevenue', 
            'industryName', 'faceBookURL', 'twitterURL', 'linkedInURL', 
            'website', 'businessPhone', 'mobilePhone', 'businessEmail', 
            'homeEmail', 'otherEmail', 'addressLine1', 'addressLine2', 
            'city', 'state', 'zipCode', 'countryName'
        ];

        foreach ($lead_specific_fields as $field) {
            if (isset($fields_to_send[$field])) {
                $fields_to_send[$field] = sanitize_text_field($fields_to_send[$field]);
            }
        }

        // Format address fields if any are filled
        $address_fields = ['addressLine1', 'addressLine2', 'city', 'state', 'zipCode', 'countryName'];
        $address_data = [];
        foreach ($address_fields as $field) {
            if (!empty($fields_to_send[$field])) {
                $address_data[$field] = $fields_to_send[$field];
                unset($fields_to_send[$field]); // Remove from main fields
            }
        }

        if (!empty($address_data)) {
            $fields_to_send['addresses'] = [
                [
                    "addressType" => "Communication",
                    "addressLine1" => isset($address_data['addressLine1']) ? $address_data['addressLine1'] : '',
                    "addressLine2" => isset($address_data['addressLine2']) ? $address_data['addressLine2'] : '',
                    "city" => isset($address_data['city']) ? $address_data['city'] : '',
                    "county" => "", // Optional field, left empty
                    "state" => isset($address_data['state']) ? $address_data['state'] : '',
                    "countryName" => isset($address_data['countryName']) ? $address_data['countryName'] : '',
                    "zipCode" => isset($address_data['zipCode']) ? $address_data['zipCode'] : '',
                ]
            ];
        }

        // Format phone numbers if any are filled
        $phone_fields = ['businessPhone', 'mobilePhone'];
        $phone_data = [];
        foreach ($phone_fields as $field) {
            if (!empty($fields_to_send[$field])) {
            $phone_data[] = [
                "phoneType" => $field === 'businessPhone' ? "Business" : "Mobile",
                "phoneNumber" => $fields_to_send[$field],
            ];
            unset($fields_to_send[$field]); // Remove from main fields
            }
        }

        if (!empty($phone_data)) {
            $fields_to_send['phoneNumbers'] = $phone_data;
        }

        // Format email addresses if any are filled
        $email_fields = ['businessEmail', 'homeEmail', 'otherEmail'];
        $email_data = [];
        foreach ($email_fields as $field) {
            if (!empty($fields_to_send[$field])) {
            $email_data[] = [
                "emailType" => $field === 'businessEmail' ? "Business" : ($field === 'homeEmail' ? "Home" : "Other"),
                "emailAddress" => $fields_to_send[$field],
            ];
            unset($fields_to_send[$field]); // Remove from main fields
            }
        }

        if (!empty($email_data)) {
            $fields_to_send['emailAddresses'] = $email_data;
        }
    }

    adfoin_apptivo_create_record( $entity_name, $fields_to_send, $record, $cred_id);
}

function adfoin_apptivo_create_record($entity_name, $fields, $record, $cred_id) {
    if (empty($entity_name)) {
         error_log('ADFOIN Apptivo CRM: Entity name is missing for create operation.');
         adfoin_add_to_log( new WP_Error('adfoin_apptivo_error', 'Entity name is required.'), 'N/A', $fields, $record);
         return new WP_Error('adfoin_apptivo_error', 'Entity name is required.');
    }
     if (empty($fields)) {
         error_log('ADFOIN Apptivo CRM: No fields provided for create operation.');
         adfoin_add_to_log( new WP_Error('adfoin_apptivo_error', 'No fields provided.'), $entity_name, [], $record);
         return new WP_Error('adfoin_apptivo_error', 'No fields provided.');
     }

    $response = adfoin_apptivo_request(
        $entity_name,
        'POST',
        $fields,
        $record,
        $cred_id
    );

    if (is_wp_error($response)) {
         error_log('ADFOIN Apptivo CRM Create Record Error: ' . $response->get_error_message());
         return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if ($response_code >= 200 && $response_code < 300) {
        error_log('ADFOIN Apptivo CRM: Record created successfully in entity: ' . $entity_name);
        return $response_data;
    } else {
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown API Error';
        error_log('ADFOIN Apptivo CRM API Error (' . $response_code . '): ' . $error_message);
        return new WP_Error('adfoin_apptivo_api_error', 'Apptivo CRM API Error: ' . $error_message, ['status' => $response_code, 'body' => $response_body]);
    }
}

function adfoin_apptivo_request($entity_name, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id(ADFOIN_APPTIVO_KEY, $cred_id);
    $api_key = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
    $access_key = isset($credentials['accessKey']) ? $credentials['accessKey'] : '';

    $data_key = '';
    $endpoint = '';
    switch ($entity_name) {
        case 'Contacts':
            $data_key = 'contactData';
            $endpoint = 'contacts';
            break;
        case 'Leads':
            $data_key = 'leadData';
            $endpoint = 'leads';
            break;
        case 'Customers':
            $data_key = 'customerData';
            $endpoint = 'customers';
            break;
        default:
            error_log('ADFOIN Apptivo CRM: Unknown entity name.');
            return new WP_Error('adfoin_apptivo_error', 'Unknown entity name.');
    }

    $base_url = 'https://app.apptivo.com/app/dao/v6/';
    $url = $base_url . ltrim($endpoint, '/') . '?a=save&apiKey=' . urlencode($api_key) . '&accessKey=' . urlencode($access_key);

    if (!empty($data)) {
        $url .= '&' . http_build_query([$data_key => wp_json_encode($data)]);
    }

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ];

    $response = wp_remote_request($url, $args);
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return json_decode($response_body, true);
}

add_action( 'wp_ajax_adfoin_get_apptivo_entities', 'adfoin_get_apptivo_entities', 10 );
function adfoin_get_apptivo_entities() {
    if ( !adfoin_verify_nonce() ) {
        wp_send_json_error( __('Security check failed.', 'advanced-form-integration') );
        return;
    }

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';

    if (empty($cred_id)) {
         wp_send_json_error( __('Credential ID is missing.', 'advanced-form-integration') );
         return;
    }

    $entities = [
        // ['name' => 'Contacts', 'label' => __('Contact', 'advanced-form-integration')],
        // ['name' => 'Customers', 'label' => __('Customer', 'advanced-form-integration')],
        ['name' => 'Leads', 'label' => __('Lead', 'advanced-form-integration')],
    ];

    wp_send_json_success( $entities );
}

add_action( 'wp_ajax_adfoin_get_apptivo_fields', 'adfoin_get_apptivo_fields', 10 );
function adfoin_get_apptivo_fields() {
    if ( !adfoin_verify_nonce() ) {
        wp_send_json_error( __('Security check failed.', 'advanced-form-integration') );
        return;
    }

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $entity_name = isset($_POST['entityName']) ? sanitize_text_field($_POST['entityName']) : '';

    if (empty($cred_id) || empty($entity_name)) {
         wp_send_json_error( __('Credential ID or Entity Name is missing.', 'advanced-form-integration') );
         return;
    }


    $fields = [];

    switch ($entity_name) {
        case 'Contacts':
            $fields[] = ['key' => 'firstName', 'value' => 'First Name', 'description' => ''];
            $fields[] = ['key' => 'lastName', 'value' => 'Last Name', 'description' => ''];
            $fields[] = ['key' => 'email', 'value' => 'Email', 'description' => 'Primary Email'];
            $fields[] = ['key' => 'title', 'value' => 'Job Title', 'description' => ''];
            $fields[] = ['key' => 'phone', 'value' => 'Phone', 'description' => ''];
            $fields[] = ['key' => 'address1', 'value' => 'Address Line 1', 'description' => ''];
            $fields[] = ['key' => 'city', 'value' => 'City', 'description' => ''];
            $fields[] = ['key' => 'state', 'value' => 'State/Province', 'description' => ''];
            $fields[] = ['key' => 'zip', 'value' => 'Postal Code', 'description' => ''];
            $fields[] = ['key' => 'country', 'value' => 'Country', 'description' => ''];
            
            break;
        case 'Customers':
            $fields[] = ['key' => 'customerName', 'value' => 'Customer Name', 'description' => ''];
            $fields[] = ['key' => 'website', 'value' => 'Website', 'description' => ''];
            $fields[] = ['key' => 'phone', 'value' => 'Phone', 'description' => ''];
            $fields[] = ['key' => 'address1', 'value' => 'Address Line 1', 'description' => ''];
            $fields[] = ['key' => 'city', 'value' => 'City', 'description' => ''];
            $fields[] = ['key' => 'state', 'value' => 'State/Province', 'description' => ''];
            $fields[] = ['key' => 'zip', 'value' => 'Postal Code', 'description' => ''];
            $fields[] = ['key' => 'country', 'value' => 'Country', 'description' => ''];
            
            break;
        case 'Leads':
            $fields[] = ['key' => 'title', 'value' => 'Title', 'description' => ''];
            $fields[] = ['key' => 'firstName', 'value' => 'First Name', 'description' => ''];
            $fields[] = ['key' => 'lastName', 'value' => 'Last Name', 'description' => ''];
            $fields[] = ['key' => 'jobTitle', 'value' => 'Job Title', 'description' => ''];
            $fields[] = ['key' => 'wayToContact', 'value' => 'Best Way to Contact', 'description' => ''];
            $fields[] = ['key' => 'leadStatusMeaning', 'value' => 'Lead Status', 'description' => ''];
            $fields[] = ['key' => 'leadSourceMeaning', 'value' => 'Lead Source', 'description' => ''];
            $fields[] = ['key' => 'leadTypeName', 'value' => 'Lead Type', 'description' => ''];
            $fields[] = ['key' => 'referredByName', 'value' => 'Referred By', 'description' => ''];
            $fields[] = ['key' => 'assigneeObjectRefName', 'value' => 'Assigned To', 'description' => ''];
            $fields[] = ['key' => 'description', 'value' => 'Description', 'description' => ''];
            $fields[] = ['key' => 'potentialAmount', 'value' => 'Potential Amount', 'description' => ''];
            $fields[] = ['key' => 'leadRankMeaning', 'value' => 'Rank', 'description' => ''];
            $fields[] = ['key' => 'annualRevenue', 'value' => 'Annual Revenue', 'description' => ''];
            $fields[] = ['key' => 'industryName', 'value' => 'Industry', 'description' => ''];
            $fields[] = ['key' => 'faceBookURL', 'value' => 'Facebook', 'description' => ''];
            $fields[] = ['key' => 'twitterURL', 'value' => 'Twitter', 'description' => ''];
            $fields[] = ['key' => 'linkedInURL', 'value' => 'LinkedIn', 'description' => ''];
            $fields[] = ['key' => 'website', 'value' => 'Website', 'description' => ''];
            $fields[] = ['key' => 'businessPhone', 'value' => 'Business Phone', 'description' => ''];
            $fields[] = ['key' => 'mobilePhone', 'value' => 'Mobile Phone', 'description' => ''];
            $fields[] = ['key' => 'businessEmail', 'value' => 'Business Email', 'description' => ''];
            $fields[] = ['key' => 'homeEmail', 'value' => 'Home Email', 'description' => ''];
            $fields[] = ['key' => 'otherEmail', 'value' => 'Other Email', 'description' => ''];
            $fields[] = ['key' => 'addressLine1', 'value' => 'Address Line 1', 'description' => ''];
            $fields[] = ['key' => 'addressLine2', 'value' => 'Address Line 2', 'description' => ''];
            $fields[] = ['key' => 'city', 'value' => 'City', 'description' => ''];
            $fields[] = ['key' => 'state', 'value' => 'State/Province', 'description' => ''];
            $fields[] = ['key' => 'zipCode', 'value' => 'Postal Code', 'description' => ''];
            $fields[] = ['key' => 'countryName', 'value' => 'Country', 'description' => ''];
             break;
        default:
            
            break;
    }
    

    wp_send_json_success( $fields );
}

add_action('adfoin_action_fields', 'adfoin_apptivo_action_fields');
/**
 * Output the template for Apptivo CRM action fields in the admin.
 */
function adfoin_apptivo_action_fields() {
?>
<script type="text/template" id="apptivo-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'create_record'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': entitiesLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'create_record'">
            <td scope="row"><?php _e('Apptivo Account', 'advanced-form-integration'); ?></td>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_apptivo_credentials_list(); ?>
                </select>
            </td>
        </tr>

        <tr  class="alternate" v-if="action.task == 'create_record'">
             <td scope="row"><?php _e('Select Entity', 'advanced-form-integration'); ?></td>
             <td>
                 <select name="fieldData[entityName]" v-model="fielddata.entityName" @change="getEntityFields">
                     <option value=""><?php _e('Select Entity...', 'advanced-form-integration'); ?></option>
                     <option v-for="entity in entities" :value="entity.name">{{ entity.label }}</option>
                 </select>
             </td>
        </tr>

        <editable-field v-for="field in entityFields"
                        :key="field.key"
                        :field="{ type: 'text', value: field.key, title: field.value, task: ['create_record'], required: field.required, description: field.description }"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>
    </table>
</script>
<?php
}
