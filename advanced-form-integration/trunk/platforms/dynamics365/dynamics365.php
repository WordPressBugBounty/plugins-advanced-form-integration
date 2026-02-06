<?php

add_filter('adfoin_action_providers', 'adfoin_dynamics365_actions', 10, 1);

function adfoin_dynamics365_actions($actions) {
    $actions['dynamics365'] = array(
        'title' => 'Dynamics 365 CRM',
        'tasks' => array(
            'create_contact' => 'Create Contact'
        )
    );
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_dynamics365_settings_tab', 10, 1);

function adfoin_dynamics365_settings_tab($providers) {
    $providers['dynamics365'] = 'Dynamics 365 CRM';
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_dynamics365_settings_view', 10, 1);

function adfoin_dynamics365_settings_view($current_tab) {
    if ($current_tab !== 'dynamics365') return;

    $title = __('Dynamics 365 CRM', 'advanced-form-integration');
    $key = 'dynamics365';
    $arguments = json_encode(array(
        'platform' => $key,
        'fields' => array(
            array('key' => 'instanceUrl', 'label' => __('Instance URL', 'advanced-form-integration'), 'hidden' => false),
            array('key' => 'clientId', 'label' => __('Client ID', 'advanced-form-integration'), 'hidden' => false),
            array('key' => 'clientSecret', 'label' => __('Client Secret', 'advanced-form-integration'), 'hidden' => true),
            array('key' => 'tenantId', 'label' => __('Tenant (Directory) ID', 'advanced-form-integration'), 'hidden' => false),
        )
    ));

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                    <li>%5$s</li>
                </ol>
            </li>
            <li><strong>%6$s</strong>
                <ol>
                    <li>%7$s</li>
                    <li>%8$s</li>
                    <li>%9$s</li>
                    <li>%10$s</li>
                </ol>
            </li>
            <li><strong>%11$s</strong>
                <ol>
                    <li>%12$s</li>
                    <li>%13$s</li>
                    <li>%14$s</li>
                </ol>
            </li>
            <li><strong>%15$s</strong>
                <ol>
                    <li>%16$s</li>
                    <li>%17$s</li>
                    <li>%18$s</li>
                </ol>
            </li>
        </ol>
        <p>%19$s</p>',
        esc_html__( 'Create an Azure App Registration', 'advanced-form-integration' ),
        esc_html__( 'Sign in to portal.azure.com with an administrator account.', 'advanced-form-integration' ),
        esc_html__( 'Navigate to Azure Active Directory → App registrations → New registration.', 'advanced-form-integration' ),
        esc_html__( 'Enter a friendly name (e.g., “AFI Dynamics Integration”), leave the redirect URI blank, and register the app.', 'advanced-form-integration' ),
        esc_html__( 'From the Overview blade copy the “Application (client) ID” and the “Directory (tenant) ID”; you will paste these above.', 'advanced-form-integration' ),
        esc_html__( 'Create a Client Secret', 'advanced-form-integration' ),
        esc_html__( 'Open Certificates & secrets → New client secret.', 'advanced-form-integration' ),
        esc_html__( 'Supply a description, choose an expiry, and click Add.', 'advanced-form-integration' ),
        esc_html__( 'Copy the generated value immediately; this is the Client Secret you must store in the settings above. It will not be shown again.', 'advanced-form-integration' ),
        esc_html__( 'For security, save the secret in a password vault so you can rotate it before it expires.', 'advanced-form-integration' ),
        esc_html__( 'Grant API Permissions', 'advanced-form-integration' ),
        esc_html__( 'Go to API permissions → Add a permission → Microsoft APIs → Dynamics CRM.', 'advanced-form-integration' ),
        esc_html__( 'Choose Delegated permissions, tick user_impersonation, and add the permission.', 'advanced-form-integration' ),
        esc_html__( 'Click “Grant admin consent” so the application can call the Dynamics API.', 'advanced-form-integration' ),
        esc_html__( 'Collect Your Instance URL', 'advanced-form-integration' ),
        esc_html__( 'Open your Dynamics environment and copy the base URL (e.g., https://yourorg.crm.dynamics.com).', 'advanced-form-integration' ),
        esc_html__( 'Paste the exact host into Instance URL above. Regional hosts (crm4, crm11, etc.) must match your tenant.', 'advanced-form-integration' ),
        esc_html__( 'Click Save in this settings screen to store the credentials.', 'advanced-form-integration' ),
        esc_html__( 'After saving, the integration requests Azure AD tokens using the Client Credentials flow and calls the Dynamics 365 Web API (v9.2).', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('adfoin_action_fields', 'adfoin_dynamics365_action_fields');

function adfoin_dynamics365_action_fields() {
    ?>
    <script type="text/template" id="dynamics365-action-template">
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
                        <?php esc_attr_e('Dynamics 365 Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_dynamics365_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_dynamics365_credentials', 'adfoin_get_dynamics365_credentials');
function adfoin_get_dynamics365_credentials() {
    if (!adfoin_verify_nonce()) return;
    wp_send_json_success(adfoin_read_credentials('dynamics365'));
}

add_action('wp_ajax_adfoin_save_dynamics365_credentials', 'adfoin_save_dynamics365_credentials');
function adfoin_save_dynamics365_credentials() {
    if (!adfoin_verify_nonce()) return;

    if ($_POST['platform'] === 'dynamics365') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('dynamics365', $data);
    }

    wp_send_json_success();
}

add_action('wp_ajax_adfoin_get_dynamics365_fields', 'adfoin_get_dynamics365_fields');

function adfoin_get_dynamics365_fields() {
    if (!adfoin_verify_nonce()) return;
    
    $fields = [];
    
    // Required fields for Dynamics 365 contact creation
    array_push($fields, array('key' => 'firstname', 'value' => 'First Name', 'description' => 'Contact first name'));
    array_push($fields, array('key' => 'lastname', 'value' => 'Last Name', 'description' => 'Contact last name'));
    
    // Email fields
    array_push($fields, array('key' => 'emailaddress1', 'value' => 'Primary Email', 'description' => 'Primary email address'));
    array_push($fields, array('key' => 'emailaddress2', 'value' => 'Secondary Email', 'description' => 'Secondary email address'));
    array_push($fields, array('key' => 'emailaddress3', 'value' => 'Third Email', 'description' => 'Third email address'));
    
    // Phone fields
    array_push($fields, array('key' => 'telephone1', 'value' => 'Business Phone', 'description' => 'Business phone number'));
    array_push($fields, array('key' => 'telephone2', 'value' => 'Home Phone', 'description' => 'Home phone number'));
    array_push($fields, array('key' => 'telephone3', 'value' => 'Other Phone', 'description' => 'Other phone number'));
    array_push($fields, array('key' => 'mobilephone', 'value' => 'Mobile Phone', 'description' => 'Mobile phone number'));
    array_push($fields, array('key' => 'fax', 'value' => 'Fax', 'description' => 'Fax number'));
    
    // Address fields
    array_push($fields, array('key' => 'address1_line1', 'value' => 'Address Line 1', 'description' => 'Primary address line 1'));
    array_push($fields, array('key' => 'address1_line2', 'value' => 'Address Line 2', 'description' => 'Primary address line 2'));
    array_push($fields, array('key' => 'address1_city', 'value' => 'City', 'description' => 'Primary address city'));
    array_push($fields, array('key' => 'address1_stateorprovince', 'value' => 'State/Province', 'description' => 'Primary address state or province'));
    array_push($fields, array('key' => 'address1_postalcode', 'value' => 'Postal Code', 'description' => 'Primary address postal code'));
    array_push($fields, array('key' => 'address1_country', 'value' => 'Country', 'description' => 'Primary address country'));
    
    // Business information
    array_push($fields, array('key' => 'jobtitle', 'value' => 'Job Title', 'description' => 'Contact job title'));
    array_push($fields, array('key' => 'department', 'value' => 'Department', 'description' => 'Contact department'));
    array_push($fields, array('key' => 'companyname', 'value' => 'Company Name', 'description' => 'Company name'));
    array_push($fields, array('key' => 'websiteurl', 'value' => 'Website URL', 'description' => 'Website URL'));
    
    // Lead source and status
    array_push($fields, array('key' => 'leadsourcecode', 'value' => 'Lead Source', 'description' => 'Source of the lead'));
    array_push($fields, array('key' => 'statuscode', 'value' => 'Status Code', 'description' => 'Contact status code'));
    array_push($fields, array('key' => 'preferredcontactmethodcode', 'value' => 'Preferred Contact Method', 'description' => 'Preferred contact method'));
    
    // Assignment
    array_push($fields, array('key' => 'ownerid', 'value' => 'Owner ID', 'description' => 'ID of the user who owns this contact'));
    
    // Social and web
    array_push($fields, array('key' => 'websiteurl', 'value' => 'Website', 'description' => 'Website URL'));
    
    // Additional fields
    array_push($fields, array('key' => 'birthdate', 'value' => 'Birth Date', 'description' => 'Birth date (YYYY-MM-DD format)'));
    array_push($fields, array('key' => 'spousesname', 'value' => 'Spouse Name', 'description' => 'Spouse name'));
    array_push($fields, array('key' => 'anniversary', 'value' => 'Anniversary', 'description' => 'Anniversary date (YYYY-MM-DD format)'));
    array_push($fields, array('key' => 'description', 'value' => 'Description', 'description' => 'Additional description or notes'));
    
    wp_send_json_success($fields);
}


function adfoin_dynamics365_credentials_list() {
    foreach (adfoin_read_credentials('dynamics365') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

function adfoin_dynamics365_get_access_token($credentials) {
    $tenant_id = $credentials['tenantId'];
    $client_id = $credentials['clientId'];
    $client_secret = $credentials['clientSecret'];
    $instance_url = isset( $credentials['instanceUrl'] ) ? rtrim( $credentials['instanceUrl'], '/' ) : '';

    if ( empty( $tenant_id ) || empty( $client_id ) || empty( $client_secret ) || empty( $instance_url ) ) {
        return false;
    }
    
    $token_url = "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token";

    $parsed_instance = wp_parse_url( $instance_url );
    $resource_origin  = isset( $parsed_instance['scheme'], $parsed_instance['host'] )
        ? $parsed_instance['scheme'] . '://' . $parsed_instance['host']
        : 'https://crm.dynamics.com';

    $scope = $resource_origin . '/.default';
    
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'body' => http_build_query(array(
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => $scope
        ))
    );
    
    $response = wp_remote_request($token_url, $args);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return isset($body['access_token']) ? $body['access_token'] : false;
}

function adfoin_dynamics365_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('dynamics365', $cred_id);
    $instance_url = isset($credentials['instanceUrl']) ? rtrim($credentials['instanceUrl'], '/') : '';

    if (!$instance_url) {
        return;
    }

    $access_token = adfoin_dynamics365_get_access_token($credentials);
    if (!$access_token) {
        return;
    }

    $url = $instance_url . '/api/data/v9.2/' . $endpoint;
    
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            'OData-MaxVersion' => '4.0',
            'OData-Version' => '4.0',
            'Accept' => 'application/json'
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

function adfoin_dynamics365_create_contact($fields, $record, $cred_id) {
    $contact_data = array();

    // Name fields
    if (!empty($fields['firstname'])) {
        $contact_data['firstname'] = $fields['firstname'];
    }
    if (!empty($fields['lastname'])) {
        $contact_data['lastname'] = $fields['lastname'];
    }

    // Email fields
    if (!empty($fields['emailaddress1'])) {
        $contact_data['emailaddress1'] = $fields['emailaddress1'];
    }
    if (!empty($fields['emailaddress2'])) {
        $contact_data['emailaddress2'] = $fields['emailaddress2'];
    }
    if (!empty($fields['emailaddress3'])) {
        $contact_data['emailaddress3'] = $fields['emailaddress3'];
    }

    // Phone fields
    if (!empty($fields['telephone1'])) {
        $contact_data['telephone1'] = $fields['telephone1'];
    }
    if (!empty($fields['telephone2'])) {
        $contact_data['telephone2'] = $fields['telephone2'];
    }
    if (!empty($fields['telephone3'])) {
        $contact_data['telephone3'] = $fields['telephone3'];
    }
    if (!empty($fields['mobilephone'])) {
        $contact_data['mobilephone'] = $fields['mobilephone'];
    }
    if (!empty($fields['fax'])) {
        $contact_data['fax'] = $fields['fax'];
    }

    // Address fields
    if (!empty($fields['address1_line1'])) {
        $contact_data['address1_line1'] = $fields['address1_line1'];
    }
    if (!empty($fields['address1_line2'])) {
        $contact_data['address1_line2'] = $fields['address1_line2'];
    }
    if (!empty($fields['address1_city'])) {
        $contact_data['address1_city'] = $fields['address1_city'];
    }
    if (!empty($fields['address1_stateorprovince'])) {
        $contact_data['address1_stateorprovince'] = $fields['address1_stateorprovince'];
    }
    if (!empty($fields['address1_postalcode'])) {
        $contact_data['address1_postalcode'] = $fields['address1_postalcode'];
    }
    if (!empty($fields['address1_country'])) {
        $contact_data['address1_country'] = $fields['address1_country'];
    }

    // Business information
    if (!empty($fields['jobtitle'])) {
        $contact_data['jobtitle'] = $fields['jobtitle'];
    }
    if (!empty($fields['department'])) {
        $contact_data['department'] = $fields['department'];
    }
    if (!empty($fields['companyname'])) {
        $contact_data['companyname'] = $fields['companyname'];
    }
    if (!empty($fields['websiteurl'])) {
        $contact_data['websiteurl'] = $fields['websiteurl'];
    }

    // Lead source and status
    if (!empty($fields['leadsourcecode'])) {
        $contact_data['leadsourcecode'] = intval($fields['leadsourcecode']);
    }
    if (!empty($fields['statuscode'])) {
        $contact_data['statuscode'] = intval($fields['statuscode']);
    }
    if (!empty($fields['preferredcontactmethodcode'])) {
        $contact_data['preferredcontactmethodcode'] = intval($fields['preferredcontactmethodcode']);
    }

    // Assignment
    if (!empty($fields['ownerid'])) {
        $contact_data['ownerid@odata.bind'] = '/systemusers(' . $fields['ownerid'] . ')';
    }

    // Additional fields
    if (!empty($fields['birthdate'])) {
        $contact_data['birthdate'] = $fields['birthdate'];
    }
    if (!empty($fields['spousesname'])) {
        $contact_data['spousesname'] = $fields['spousesname'];
    }
    if (!empty($fields['anniversary'])) {
        $contact_data['anniversary'] = $fields['anniversary'];
    }
    if (!empty($fields['description'])) {
        $contact_data['description'] = $fields['description'];
    }

    // Send request to Dynamics 365 API
    return adfoin_dynamics365_request('contacts', 'POST', $contact_data, $record, $cred_id);
}

add_action('adfoin_dynamics365_job_queue', 'adfoin_dynamics365_job_queue', 10, 1);

function adfoin_dynamics365_job_queue($data) {
    adfoin_dynamics365_send_data($data['record'], $data['posted_data']);
}

function adfoin_dynamics365_send_data($record, $posted_data) {
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
        adfoin_dynamics365_create_contact($contact_fields, $record, $cred_id);
    }
}
