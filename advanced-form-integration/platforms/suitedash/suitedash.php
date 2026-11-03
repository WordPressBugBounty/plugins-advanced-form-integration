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
    $arguments = wp_json_encode([
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
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('suitedash'));
}

add_action('wp_ajax_adfoin_save_suitedash_credentials', 'adfoin_save_suitedash_credentials');
function adfoin_save_suitedash_credentials() {

    adfoin_verify_nonce();

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

function adfoin_suitedash_build_request($endpoint, $method, $data, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('suitedash', $cred_id);
    $public_id   = isset($credentials['apiKey'])    ? $credentials['apiKey']    : '';
    $secret_key  = isset($credentials['secretKey']) ? $credentials['secretKey'] : '';

    if ( ! $public_id || ! $secret_key ) {
        return new WP_Error( 'suitedash_no_account', __( 'No SuiteDash account is selected, or the account has no Public ID or Secret Key. Check the integration and AFI Settings > SuiteDash.', 'advanced-form-integration' ) );
    }

    $url = 'https://app.suitedash.com/secure-api/' . ltrim($endpoint, '/');

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
        $args['body'] = wp_json_encode($data);
    } elseif ($method === 'GET' && !empty($data)) {
        $url = add_query_arg($data, $url);
    }

    return [$url, $args];
}

function adfoin_suitedash_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $request = adfoin_suitedash_build_request($endpoint, $method, $data, $cred_id);

    if (is_wp_error($request)) {
        if (!empty($record)) {
            adfoin_add_to_log($request, $endpoint, ['method' => $method, 'body' => $data], $record);
        }

        return $request;
    }

    list($url, $args) = $request;

    $response = wp_remote_request($url, $args);

    if (!empty($record)) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

/*
 * SuiteDash's validation errors as one line, e.g. "last_name: This value cannot be blank."
 */
function adfoin_suitedash_error_message($response) {
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body  = json_decode(wp_remote_retrieve_body($response), true);
    $parts = [];

    $flatten = function ($errors, $prefix) use (&$flatten, &$parts) {
        foreach ((array) $errors as $field => $error) {
            $name = is_int($field) ? rtrim($prefix, '.') : $prefix . $field;

            if (is_array($error)) {
                $flatten($error, $name . '.');
            } else {
                $parts[] = ($name !== '' ? $name . ': ' : '') . $error;
            }
        }
    };

    if (!empty($body['errors'])) {
        $flatten($body['errors'], '');
    }

    if (!$parts && !empty($body['message'])) {
        $parts[] = $body['message'];
    }

    return $parts ? implode(' ', $parts) : wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
}

/*
 * Logs a response that was fetched without a record (see adfoin_suitedash_sync()).
 */
function adfoin_suitedash_log($response, $endpoint, $method, $data, $record, $cred_id, $notes = []) {
    $request = adfoin_suitedash_build_request($endpoint, $method, $data, $cred_id);

    if (!is_wp_error($response)) {
        if (wp_remote_retrieve_response_code($response) >= 300) {
            $response['response']['message'] = adfoin_suitedash_error_message($response);
        } elseif ($notes) {
            $response['response']['message'] = sprintf('OK (%s)', implode('; ', $notes));
        }
    }

    adfoin_add_to_log(
        $response,
        is_wp_error($request) ? $endpoint : $request[0],
        is_wp_error($request) ? ['method' => $method, 'body' => $data] : $request[1],
        $record
    );
}

function adfoin_suitedash_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('suitedash_not_sent', $message), 'https://app.suitedash.com/secure-api/', ['method' => 'POST'], $record);
}

/*
 * Returns the record's "data" from a lookup, null when not found, or the
 * failed response (WP_Error or array) when the lookup itself failed.
 */
function adfoin_suitedash_find($endpoint, $cred_id) {
    $response = adfoin_suitedash_request($endpoint, 'GET', [], [], $cred_id);
    $code     = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);

    if ($code == 404) {
        return null;
    }

    $body = $code == 200 ? json_decode(wp_remote_retrieve_body($response), true) : null;

    return !empty($body['data']['uid']) ? $body['data'] : ['failed' => $response];
}

/*
 * Mapped values from the integration, parsed and trimmed, without empty ones.
 */
function adfoin_suitedash_collect($data, $posted_data) {
    $fields = [];

    foreach ($data as $key => $value) {
        if ($key === 'credId' || !is_string($value) || $value === '') continue;

        $value = trim((string) adfoin_get_parsed_values($value, $posted_data));

        if ($value !== '') {
            $fields[$key] = $value;
        }
    }

    return $fields;
}

function adfoin_suitedash_address($fields, $prefix) {
    $address = [];

    foreach (['address_line_1', 'address_line_2', 'city', 'state', 'country', 'zip'] as $key) {
        if (isset($fields[$prefix . $key])) {
            $address[$key] = $fields[$prefix . $key];
        }
    }

    return $address;
}

/*
 * Creates or updates the company (when a name is mapped), then the contact
 * (when an email is mapped). Lookups aren't logged; the company and the
 * contact each get one log entry.
 *
 * SuiteDash only accepts a role when creating, links a contact to a company
 * only when the contact is created, and needs "%20" (not "+") in names.
 */
function adfoin_suitedash_sync($fields, $record, $cred_id, $contact_custom = [], $company_custom = []) {
    $roles      = ['lead' => 'Lead', 'prospect' => 'Prospect', 'client' => 'Client'];
    $role       = isset($fields['role'], $roles[strtolower($fields['role'])]) ? $roles[strtolower($fields['role'])] : '';
    $co_role    = isset($fields['company_role'], $roles[strtolower($fields['company_role'])]) ? $roles[strtolower($fields['company_role'])] : '';
    $email      = '';
    $company    = isset($fields['company_name']) ? $fields['company_name'] : '';

    if (isset($fields['email'])) {
        $email = sanitize_email($fields['email']);

        if (!$email) {
            adfoin_suitedash_log_skipped($record, sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $fields['email']));
            return;
        }
    }

    if (!$email && $company === '') {
        adfoin_suitedash_log_skipped($record, __('Not sent: the mapped email and company name fields are empty.', 'advanced-form-integration'));
        return;
    }

    $created_with_company = false;

    if ($company !== '') {
        $company_data = ['name' => $company];

        foreach (['phone', 'home_phone', 'work_phone', 'shop_phone', 'website', 'background_info'] as $key) {
            if (isset($fields['company_' . $key])) {
                $company_data[$key] = $fields['company_' . $key];
            }
        }

        if (isset($fields['company_category_name'])) {
            $company_data['category'] = ['name' => $fields['company_category_name']];
        }

        if ($address = adfoin_suitedash_address($fields, 'company_')) {
            $company_data['address'] = $address;
        }

        if ($company_custom) {
            $company_data['public_custom_fields'] = $company_custom;
        }

        $endpoint = 'company/' . rawurlencode($company);
        $existing = adfoin_suitedash_find($endpoint, $cred_id);

        if (isset($existing['failed'])) {
            adfoin_suitedash_log($existing['failed'], $endpoint, 'GET', [], $record, $cred_id);
            return;
        }

        if ($existing) {
            $response = adfoin_suitedash_request($endpoint, 'PUT', $company_data, [], $cred_id);
            adfoin_suitedash_log($response, $endpoint, 'PUT', $company_data, $record, $cred_id, [__('existing company updated', 'advanced-form-integration')]);
        } else {
            $notes = [];
            $company_data['role'] = $co_role ? $co_role : ($role ? $role : 'Lead');

            if (!$co_role && !$role) {
                $notes[] = __('no role mapped, company added as Lead', 'advanced-form-integration');
            }

            // Client and Prospect companies need a primary contact. Sending the
            // email creates the contact, or links an existing one, to the company.
            if ($email) {
                $company_data['primaryContact'] = ['email' => $email, 'create_primary_contact_if_not_exists' => true];

                foreach (['first_name', 'last_name', 'name_prefix'] as $key) {
                    if (isset($fields[$key])) {
                        $company_data['primaryContact'][$key] = $fields[$key];
                    }
                }

                if (isset($fields['send_welcome_email'])) {
                    $company_data['primaryContact']['send_welcome_email'] = filter_var($fields['send_welcome_email'], FILTER_VALIDATE_BOOLEAN);
                }
            }

            $response = adfoin_suitedash_request('company', 'POST', $company_data, [], $cred_id);

            if (!$email && !is_wp_error($response) && strpos(wp_remote_retrieve_body($response), 'Primary Contact cannot be blank') !== false) {
                $response['response']['message'] = sprintf(__('SuiteDash needs a contact email to create a %s company. Map the contact email, or set the company role to Lead.', 'advanced-form-integration'), $company_data['role']);
                adfoin_add_to_log($response, 'https://app.suitedash.com/secure-api/company', ['method' => 'POST', 'body' => $company_data], $record);
                return;
            }

            adfoin_suitedash_log($response, 'company', 'POST', $company_data, $record, $cred_id, $notes);
            $created_with_company = isset($company_data['primaryContact']);
        }

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 300) {
            return;
        }
    }

    if (!$email) return;

    $contact_data = ['email' => $email];

    foreach (['first_name', 'last_name', 'name_prefix', 'home_email', 'work_email', 'phone', 'home_phone', 'work_phone', 'shop_phone', 'title', 'website', 'background_info'] as $key) {
        if (isset($fields[$key])) {
            $contact_data[$key] = $fields[$key];
        }
    }

    if ($address = adfoin_suitedash_address($fields, '')) {
        $contact_data['address'] = $address;
    }

    if (isset($fields['send_welcome_email'])) {
        $contact_data['send_welcome_email'] = filter_var($fields['send_welcome_email'], FILTER_VALIDATE_BOOLEAN);
    }

    if ($contact_custom) {
        $contact_data['custom_fields'] = $contact_custom;
    }

    $endpoint = 'contact/' . rawurlencode($email);
    $existing = adfoin_suitedash_find($endpoint, $cred_id);
    $notes    = [];

    if (isset($existing['failed'])) {
        adfoin_suitedash_log($existing['failed'], $endpoint, 'GET', [], $record, $cred_id);
        return;
    }

    if ($existing) {
        $notes[] = $created_with_company ? __('added as the company\'s primary contact', 'advanced-form-integration') : __('existing contact updated', 'advanced-form-integration');

        if ($company !== '' && !in_array(strtolower($company), array_map('strtolower', wp_list_pluck(isset($existing['companies']) ? $existing['companies'] : [], 'name')), true)) {
            $notes[] = sprintf(__('not linked to %s: SuiteDash only links a company when the contact is created', 'advanced-form-integration'), $company);
        }

        $response = adfoin_suitedash_request($endpoint, 'PUT', $contact_data, [], $cred_id);
        adfoin_suitedash_log($response, $endpoint, 'PUT', $contact_data, $record, $cred_id, $notes);

        return $response;
    }

    $contact_data['role'] = $role ? $role : ($co_role ? $co_role : 'Lead');

    if (!$role && !$co_role) {
        $notes[] = __('no role mapped, contact added as Lead', 'advanced-form-integration');
    }

    if ($company !== '') {
        $contact_data['company'] = ['name' => $company, 'create_company_if_not_exists' => false];
    }

    $response = adfoin_suitedash_request('contact', 'POST', $contact_data, [], $cred_id);
    adfoin_suitedash_log($response, 'contact', 'POST', $contact_data, $record, $cred_id, $notes);

    return $response;
}

/*
 * Standard company and contact fields, shared by the free and PRO editors.
 */
function adfoin_suitedash_standard_fields() {
    return [
        ['key' => 'company_name', 'value' => 'Company Name', 'description' => 'Creates or updates this company and links new contacts to it.'],
        ['key' => 'company_role', 'value' => 'Company Role', 'description' => 'Lead, Prospect or Client. Only used when the company is created.'],
        ['key' => 'company_phone', 'value' => 'Company Phone', 'description' => ''],
        ['key' => 'company_category_name', 'value' => 'Company Category Name', 'description' => ''],
        ['key' => 'company_home_phone', 'value' => 'Company Home Phone', 'description' => ''],
        ['key' => 'company_work_phone', 'value' => 'Company Work Phone', 'description' => ''],
        ['key' => 'company_shop_phone', 'value' => 'Company Shop Phone', 'description' => ''],
        ['key' => 'company_website', 'value' => 'Company Website', 'description' => ''],
        ['key' => 'company_background_info', 'value' => 'Company Background Info', 'description' => ''],
        ['key' => 'company_address_line_1', 'value' => 'Company Address Line 1', 'description' => ''],
        ['key' => 'company_address_line_2', 'value' => 'Company Address Line 2', 'description' => ''],
        ['key' => 'company_city', 'value' => 'Company City', 'description' => ''],
        ['key' => 'company_state', 'value' => 'Company State', 'description' => ''],
        ['key' => 'company_country', 'value' => 'Company Country', 'description' => ''],
        ['key' => 'company_zip', 'value' => 'Company ZIP', 'description' => ''],
        ['key' => 'email', 'value' => 'Contact Email', 'description' => 'Required for the contact. Leave empty to only add the company.'],
        ['key' => 'first_name', 'value' => 'Contact First Name', 'description' => 'Required by SuiteDash for new contacts.'],
        ['key' => 'last_name', 'value' => 'Contact Last Name', 'description' => 'Required by SuiteDash for new contacts.'],
        ['key' => 'name_prefix', 'value' => 'Contact Name Prefix', 'description' => 'Mr., Mrs., etc.'],
        ['key' => 'home_email', 'value' => 'Contact Home Email', 'description' => ''],
        ['key' => 'work_email', 'value' => 'Contact Work Email', 'description' => ''],
        ['key' => 'phone', 'value' => 'Contact Phone', 'description' => ''],
        ['key' => 'home_phone', 'value' => 'Contact Home Phone', 'description' => ''],
        ['key' => 'work_phone', 'value' => 'Contact Work Phone', 'description' => ''],
        ['key' => 'shop_phone', 'value' => 'Contact Shop Phone', 'description' => ''],
        ['key' => 'title', 'value' => 'Contact Title', 'description' => 'Job title'],
        ['key' => 'role', 'value' => 'Contact Role', 'description' => 'Lead, Prospect or Client. Only used when the contact is created; Lead when empty.'],
        ['key' => 'website', 'value' => 'Contact Website', 'description' => ''],
        ['key' => 'background_info', 'value' => 'Contact Background Info', 'description' => ''],
        ['key' => 'address_line_1', 'value' => 'Contact Address Line 1', 'description' => ''],
        ['key' => 'address_line_2', 'value' => 'Contact Address Line 2', 'description' => ''],
        ['key' => 'city', 'value' => 'Contact City', 'description' => ''],
        ['key' => 'state', 'value' => 'Contact State', 'description' => ''],
        ['key' => 'country', 'value' => 'Contact Country', 'description' => ''],
        ['key' => 'zip', 'value' => 'Contact ZIP', 'description' => ''],
        ['key' => 'send_welcome_email', 'value' => 'Send Welcome Email', 'description' => 'true/false'],
    ];
}

add_action('wp_ajax_adfoin_get_suitedash_fields', 'adfoin_get_suitedash_fields', 10);

function adfoin_get_suitedash_fields() {
    adfoin_verify_nonce();

    wp_send_json_success(adfoin_suitedash_standard_fields());
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
            <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_company_and_contact'">
            <th scope="row"><?php _e('SuiteDash Account', 'advanced-form-integration'); ?></th>
            <td>
                <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData" required>
                    <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                    <?php adfoin_suitedash_credentials_list(); ?>
                </select>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=suitedash' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>
        

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>
        <?php adfoin_pro_feature_notice( 'add_company_and_contact', 'SuiteDash [PRO]', 'custom fields' ); ?>
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

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    adfoin_suitedash_sync(adfoin_suitedash_collect($data, $posted_data), $record, $cred_id);
}
