<?php

add_filter('adfoin_action_providers', 'adfoin_suitedash_actions', 10, 1);
function adfoin_suitedash_actions($actions) {
    $actions['suitedash'] = [
        'title' => __('SuiteDash', 'advanced-form-integration'),
        'tasks' => [
            'add_company_and_contact' => __('Add Company and Contact', 'advanced-form-integration')
        ]
    ];
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_suitedash_settings_tab', 10, 1);
function adfoin_suitedash_settings_tab($providers) {
    $providers['suitedash'] = __('SuiteDash', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_suitedash_settings_view', 10, 1);
function adfoin_suitedash_settings_view($current_tab) {
    if ($current_tab !== 'suitedash') return;

    $title = __('SuiteDash', 'advanced-form-integration');
    $key = 'suitedash';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            ['key' => 'apiKey', 'label' => __('Public ID', 'advanced-form-integration'), 'hidden' => true],
            ['key' => 'secretKey', 'label' => __('Secret Key', 'advanced-form-integration'), 'hidden' => true],
        ]
    ]);
    $instructions = __('Go to your SuiteDash admin panel, navigate to Profile > Integrations > SECURE API tab to get your Public ID and generate your Secret Key.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_suitedash_credentials', 'adfoin_get_suitedash_credentials');
function adfoin_get_suitedash_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('suitedash'));
}

add_action('wp_ajax_adfoin_save_suitedash_credentials', 'adfoin_save_suitedash_credentials');
function adfoin_save_suitedash_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'suitedash') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('suitedash', $data);
    }

    wp_send_json_success();
}

function adfoin_suitedash_credentials_list() {
    foreach (adfoin_read_credentials('suitedash') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

function adfoin_suitedash_add_or_update_contact($fields, $record, $cred_id) {
    // Search for existing contact by email
    $search_response = adfoin_suitedash_request(
        'contact/' . urlencode($fields['email']),
        'GET',
        [],
        $record,
        $cred_id
    );

    $search_body = wp_remote_retrieve_body($search_response);
    $search_data = json_decode($search_body, true);

    if (!empty($search_data['data']) && isset($search_data['data']['uid'])) {
        // Update existing contact
        $contact_id = $search_data['data']['uid'];
        $update_response = adfoin_suitedash_request(
            'contact/' . $contact_id,
            'PUT',
            $fields,
            $record,
            $cred_id
        );
        return $update_response;
    } else {
        // Create new contact
        $create_response = adfoin_suitedash_request(
            'contact',
            'POST',
            $fields,
            $record,
            $cred_id
        );
        return $create_response;
    }
}


function adfoin_suitedash_add_or_update_company($fields, $record, $cred_id) {
    // Search for existing company by name
    $search_response = adfoin_suitedash_request(
        'company/' . urlencode($fields['name']),
        'GET',
        [],
        $record,
        $cred_id
    );

    $search_body = wp_remote_retrieve_body($search_response);
    $search_data = json_decode($search_body, true);

    if (!empty($search_data['data']) && isset($search_data['data']['uid'])) {
        // Update existing company
        $company_uid = $search_data['data']['uid'];

        $update_fields = $fields;
        if (isset($update_fields['primaryContact'])) {
            unset($update_fields['primaryContact']);
        }

        if (isset($update_fields['role'])) {
            unset($update_fields['role']);
        }
        
        $update_response = adfoin_suitedash_request(
            'company/' . $company_uid,
            'PUT',
            $update_fields,
            $record,
            $cred_id
        );
        return $update_response;
    } else {
        // Create new company
        $create_response = adfoin_suitedash_request(
            'company',
            'POST',
            $fields,
            $record,
            $cred_id
        );
        return $create_response;
    }
}

function adfoin_suitedash_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('suitedash', $cred_id);
    $public_id = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
    $secret_key = isset($credentials['secretKey']) ? $credentials['secretKey'] : '';
    $api_url = 'https://app.suitedash.com/secure-api';
    
    $url = rtrim($api_url, '/') . '/' . ltrim($endpoint, '/');

    $args = [
        'method' => $method,
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Public-ID' => $public_id,
            'X-Secret-Key' => $secret_key,
            'Accept' => 'application/json'
        ]
    ];

    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $args['body'] = json_encode($data);
    } elseif ($method === 'GET' && !empty($data)) {
        $url = add_query_arg($data, $url);
    }

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}


function adfoin_get_suitedash_contact_lists($cred_id) {
    $response = adfoin_suitedash_request('contact-lists', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $lists = [];

    if (!empty($body['data'])) {
        $lists = wp_list_pluck($body['data'], 'name');
    }

    return implode(', ', $lists);
}

add_action('wp_ajax_adfoin_get_suitedash_fields', 'adfoin_get_suitedash_fields', 10);

/*
 * Get SuiteDash fields
 */
function adfoin_get_suitedash_fields() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $task = sanitize_text_field($_POST['task']);
    
    $fields = [];

    if ($task === 'add_company_and_contact') {
        // Company fields
        array_push($fields, array('key' => 'company_name', 'value' => 'Company Name', 'description' => ''));
        array_push($fields, array('key' => 'company_role', 'value' => 'Company Role', 'description' => 'Lead, Client, or Prospect'));
        array_push($fields, array('key' => 'company_phone', 'value' => 'Company Phone', 'description' => ''));
        array_push($fields, array('key' => 'company_category_name', 'value' => 'Company Category Name', 'description' => ''));
        array_push($fields, array('key' => 'company_home_phone', 'value' => 'Company Home Phone', 'description' => ''));
        array_push($fields, array('key' => 'company_work_phone', 'value' => 'Company Work Phone', 'description' => ''));
        array_push($fields, array('key' => 'company_shop_phone', 'value' => 'Company Shop Phone', 'description' => ''));
        array_push($fields, array('key' => 'company_website', 'value' => 'Company Website', 'description' => ''));
        array_push($fields, array('key' => 'company_background_info', 'value' => 'Company Background Info', 'description' => ''));
        array_push($fields, array('key' => 'company_address_line_1', 'value' => 'Company Address Line 1', 'description' => ''));
        array_push($fields, array('key' => 'company_address_line_2', 'value' => 'Company Address Line 2', 'description' => ''));
        array_push($fields, array('key' => 'company_city', 'value' => 'Company City', 'description' => ''));
        array_push($fields, array('key' => 'company_state', 'value' => 'Company State', 'description' => ''));
        array_push($fields, array('key' => 'company_country', 'value' => 'Company Country', 'description' => ''));
        array_push($fields, array('key' => 'company_zip', 'value' => 'Company ZIP', 'description' => ''));
        
        // Consolidated Contact fields (will be used as primary contact if company_name exists, otherwise as standalone contact)
        array_push($fields, array('key' => 'first_name', 'value' => 'Contact First Name', 'description' => 'Contact first name'));
        array_push($fields, array('key' => 'last_name', 'value' => 'Contact Last Name', 'description' => 'Contact last name'));
        array_push($fields, array('key' => 'name_prefix', 'value' => 'Contact Name Prefix', 'description' => 'Contact name prefix (Mr., Mrs., etc.)'));
        array_push($fields, array('key' => 'email', 'value' => 'Contact Email', 'description' => 'Required for contact'));
        array_push($fields, array('key' => 'home_email', 'value' => 'Contact Home Email', 'description' => 'Contact home email address'));
        array_push($fields, array('key' => 'work_email', 'value' => 'Contact Work Email', 'description' => 'Contact work email address'));
        array_push($fields, array('key' => 'phone', 'value' => 'Contact Phone', 'description' => 'Contact main phone number'));
        array_push($fields, array('key' => 'home_phone', 'value' => 'Contact Home Phone', 'description' => 'Contact home phone number'));
        array_push($fields, array('key' => 'work_phone', 'value' => 'Contact Work Phone', 'description' => 'Contact work phone number'));
        array_push($fields, array('key' => 'shop_phone', 'value' => 'Contact Shop Phone', 'description' => 'Contact shop phone number'));
        array_push($fields, array('key' => 'title', 'value' => 'Contact Title', 'description' => 'Contact job title'));
        array_push($fields, array('key' => 'role', 'value' => 'Contact Role', 'description' => 'Lead, Client, or Prospect'));
        array_push($fields, array('key' => 'website', 'value' => 'Contact Website', 'description' => 'Contact website URL'));
        array_push($fields, array('key' => 'background_info', 'value' => 'Contact Background Info', 'description' => 'Additional background information'));
        array_push($fields, array('key' => 'address_line_1', 'value' => 'Contact Address Line 1', 'description' => 'Contact address line 1'));
        array_push($fields, array('key' => 'address_line_2', 'value' => 'Contact Address Line 2', 'description' => 'Contact address line 2'));
        array_push($fields, array('key' => 'city', 'value' => 'Contact City', 'description' => 'Contact city'));
        array_push($fields, array('key' => 'state', 'value' => 'Contact State', 'description' => 'Contact state'));
        array_push($fields, array('key' => 'country', 'value' => 'Contact Country', 'description' => 'Contact country'));
        array_push($fields, array('key' => 'zip', 'value' => 'Contact ZIP', 'description' => 'Contact ZIP/postal code'));
        array_push($fields, array('key' => 'send_welcome_email', 'value' => 'Send Welcome Email', 'description' => 'true/false'));
        array_push($fields, array('key' => 'create_if_not_exists', 'value' => 'Create Contact If Not Exists', 'description' => 'true/false'));
        array_push($fields, array('key' => 'prevent_individual_mode', 'value' => 'Prevent Individual Mode', 'description' => 'true/false'));
    }

    wp_send_json_success($fields);
}

add_action('adfoin_action_fields', 'adfoin_suitedash_action_fields');
function adfoin_suitedash_action_fields() {
?>
<script type="text/template" id="suitedash-action-template">
    <table class="form-table">
        <tr valign="top" v-if="action.task == 'add_company_and_contact'">
            <th scope="row">
                <?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?>
            </th>
            <td scope="row">
            <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_company_and_contact'">
            <th scope="row"><?php _e('SuiteDash Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_suitedash_credentials_list(); ?>
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

add_action('adfoin_suitedash_job_queue', 'adfoin_suitedash_job_queue', 10, 1);
function adfoin_suitedash_job_queue($data) {
    adfoin_suitedash_send_data($data['record'], $data['posted_data']);
}

function adfoin_suitedash_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) {
        return;
    }

    $data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $task = isset($record['task']) ? $record['task'] : '';

    unset($data['credId']);

    $fields = array();

    foreach ($data as $key => $value) {
        if (!empty($value)) {
            $value = adfoin_get_parsed_values($value, $posted_data);

            if (!empty($value)) {
                $fields[$key] = $value;
            }
        }
    }


    if ($task === 'add_company_and_contact') {
        // Build consolidated contact data from single set of fields
        $contact_data = [];
        if (!empty($fields['first_name'])) {
            $contact_data['first_name'] = $fields['first_name'];
        }
        if (!empty($fields['last_name'])) {
            $contact_data['last_name'] = $fields['last_name'];
        }
        if (!empty($fields['name_prefix'])) {
            $contact_data['name_prefix'] = $fields['name_prefix'];
        }
        if (!empty($fields['email'])) {
            $contact_data['email'] = $fields['email'];
        }
        if (!empty($fields['home_email'])) {
            $contact_data['home_email'] = $fields['home_email'];
        }
        if (!empty($fields['work_email'])) {
            $contact_data['work_email'] = $fields['work_email'];
        }
        if (!empty($fields['phone'])) {
            $contact_data['phone'] = $fields['phone'];
        }
        if (!empty($fields['home_phone'])) {
            $contact_data['home_phone'] = $fields['home_phone'];
        }
        if (!empty($fields['work_phone'])) {
            $contact_data['work_phone'] = $fields['work_phone'];
        }
        if (!empty($fields['shop_phone'])) {
            $contact_data['shop_phone'] = $fields['shop_phone'];
        }
        if (!empty($fields['title'])) {
            $contact_data['title'] = $fields['title'];
        }
        if (!empty($fields['role'])) {
            $contact_data['role'] = $fields['role'];
        }
        if (!empty($fields['website'])) {
            $contact_data['website'] = $fields['website'];
        }
        if (!empty($fields['background_info'])) {
            $contact_data['background_info'] = $fields['background_info'];
        }
        
        // Build contact address object if any address field is provided
        $contact_address = array();
        if (!empty($fields['address_line_1'])) {
            $contact_address['address_line_1'] = $fields['address_line_1'];
        }
        if (!empty($fields['address_line_2'])) {
            $contact_address['address_line_2'] = $fields['address_line_2'];
        }
        if (!empty($fields['city'])) {
            $contact_address['city'] = $fields['city'];
        }
        if (!empty($fields['state'])) {
            $contact_address['state'] = $fields['state'];
        }
        if (!empty($fields['country'])) {
            $contact_address['country'] = $fields['country'];
        }
        if (!empty($fields['zip'])) {
            $contact_address['zip'] = $fields['zip'];
        }
        
        if (!empty($contact_address)) {
            $contact_data['address'] = $contact_address;
        }
        
        if (!empty($fields['send_welcome_email'])) {
            $contact_data['send_welcome_email'] = filter_var($fields['send_welcome_email'], FILTER_VALIDATE_BOOLEAN);
        }
        if (!empty($fields['create_if_not_exists'])) {
            $contact_data['create_primary_contact_if_not_exists'] = filter_var($fields['create_if_not_exists'], FILTER_VALIDATE_BOOLEAN);
        }
        if (!empty($fields['prevent_individual_mode'])) {
            $contact_data['prevent_individual_mode'] = filter_var($fields['prevent_individual_mode'], FILTER_VALIDATE_BOOLEAN);
        }
        
        $response = null;
        
        // If company_name exists, add contact as primary contact for company
        if (!empty($fields['company_name'])) {
            $company_fields = [];
            $company_fields['name'] = $fields['company_name'];
            
            if (!empty($fields['company_role'])) {
                $company_fields['role'] = $fields['company_role'];
            }
            
            if (!empty($fields['company_phone'])) {
                $company_fields['phone'] = $fields['company_phone'];
            }
            
            if (!empty($fields['company_category_name'])) {
                $company_fields['category'] = array('name' => $fields['company_category_name']);
            }
            
            if (!empty($fields['company_home_phone'])) {
                $company_fields['home_phone'] = $fields['company_home_phone'];
            }
            
            if (!empty($fields['company_work_phone'])) {
                $company_fields['work_phone'] = $fields['company_work_phone'];
            }
            
            if (!empty($fields['company_shop_phone'])) {
                $company_fields['shop_phone'] = $fields['company_shop_phone'];
            }
            
            if (!empty($fields['company_website'])) {
                $company_fields['website'] = $fields['company_website'];
            }
            
            if (!empty($fields['company_background_info'])) {
                $company_fields['background_info'] = $fields['company_background_info'];
            }
            
            // Build address object if any address field is provided
            $address = array();
            if (!empty($fields['company_address_line_1'])) {
                $address['address_line_1'] = $fields['company_address_line_1'];
            }
            if (!empty($fields['company_address_line_2'])) {
                $address['address_line_2'] = $fields['company_address_line_2'];
            }
            if (!empty($fields['company_city'])) {
                $address['city'] = $fields['company_city'];
            }
            if (!empty($fields['company_state'])) {
                $address['state'] = $fields['company_state'];
            }
            if (!empty($fields['company_country'])) {
                $address['country'] = $fields['company_country'];
            }
            if (!empty($fields['company_zip'])) {
                $address['zip'] = $fields['company_zip'];
            }
            
            if (!empty($address)) {
                $company_fields['address'] = $address;
            }
            
            // Add contact data as primary contact
            if (!empty($contact_data)) {
                $company_fields['primaryContact'] = $contact_data;
            }
            
            $response = adfoin_suitedash_add_or_update_company($company_fields, $record, $cred_id);
        } 
        // Otherwise, add as standalone contact
        elseif (!empty($contact_data['email']) || (!empty($contact_data['role']) && $contact_data['role'] === 'Lead')) {
            $response = adfoin_suitedash_add_or_update_contact($contact_data, $record, $cred_id);
        }
        
    }
}