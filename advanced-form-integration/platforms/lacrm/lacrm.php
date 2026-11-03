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
    $arguments = wp_json_encode([
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
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('lacrm'));
}

add_action('wp_ajax_adfoin_save_lacrm_credentials', 'adfoin_save_lacrm_credentials');
function adfoin_save_lacrm_credentials() {

    adfoin_verify_nonce();

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

    adfoin_lacrm_sync($record, $data, $posted_data);
}

/*
 * Splits the mapped values into company and contact fields, keeping "0".
 */
function adfoin_lacrm_parse_fields($data, $posted_data) {
    $company_fields = [];
    $contact_fields = [];

    unset($data['credId'], $data['userId'], $data['users']);

    foreach ($data as $key => $value) {
        if (!is_string($value) || $value === '') continue;

        $value = trim((string) adfoin_get_parsed_values($value, $posted_data));

        if ($value === '') continue;

        if (strpos($key, 'company__') === 0) {
            $company_fields[substr($key, 9)] = $value;
        } else {
            $contact_fields[$key] = $value;
        }
    }

    return [$company_fields, $contact_fields];
}

/*
 * Creates or updates the company and the contact.
 *
 * $extra_company / $extra_contact are extra API parameters (custom fields)
 * added by the PRO action, keyed by LACRM field name.
 */
function adfoin_lacrm_sync($record, $data, $posted_data, $extra_company = [], $extra_contact = []) {
    $cred_id     = isset($data['credId']) ? $data['credId'] : '';
    $credentials = adfoin_get_credentials_by_id('lacrm', $cred_id);
    $api_token   = isset($credentials['apiToken']) ? $credentials['apiToken'] : '';

    if ($api_token === '') {
        adfoin_lacrm_log_skipped($record, __('Not sent: no Less Annoying CRM account is selected, or the account has no API key.', 'advanced-form-integration'));
        return;
    }

    list($company_fields, $contact_fields) = adfoin_lacrm_parse_fields($data, $posted_data);

    $company_name = isset($company_fields['Company Name']) ? $company_fields['Company Name'] : '';
    $raw_email    = isset($contact_fields['Email']) ? $contact_fields['Email'] : '';
    $email        = sanitize_email($raw_email);

    if ($raw_email !== '' && !$email) {
        adfoin_lacrm_log_skipped($record, sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw_email));
        return;
    }

    if ($company_name === '' && !$email) {
        adfoin_lacrm_log_skipped($record, __('Not sent: map a contact email or a company name. Both are empty for this submission.', 'advanced-form-integration'));
        return;
    }

    // LACRM requires an owner on new records; fall back to the API key's user.
    $user_id = isset($data['userId']) ? trim((string) $data['userId']) : '';

    if ($user_id === '') {
        $me      = adfoin_lacrm_request('GetUser', [], $api_token);
        $user_id = isset($me['UserId']) ? (string) $me['UserId'] : '';
    }

    if ($company_name !== '') {
        $company = adfoin_lacrm_find('company', $company_name, $api_token, $record);

        if ($company === false) return;

        $company_params = array_merge(adfoin_lacrm_contact_params($company_fields, $company ?: []), $extra_company);

        if ($company) {
            unset($company_params['Company Name']);

            if ($company_params) {
                adfoin_lacrm_request('EditContact', ['ContactId' => $company['ContactId']] + $company_params, $api_token, $record);
            }
        } else {
            $company_params = ['IsCompany' => true, 'AssignedTo' => $user_id, 'Company Name' => $company_name] + $company_params;
            adfoin_lacrm_request('CreateContact', $company_params, $api_token, $record);
        }
    }

    if (!$email) return;

    $contact = adfoin_lacrm_find('contact', $email, $api_token, $record);

    if ($contact === false) return;

    $contact_params = array_merge(adfoin_lacrm_contact_params($contact_fields, $contact ?: []), $extra_contact);

    if ($company_name !== '') {
        $contact_params['Company Name'] = $company_name;
    }

    if ($contact) {
        adfoin_lacrm_request('EditContact', ['ContactId' => $contact['ContactId']] + $contact_params, $api_token, $record);
    } else {
        // LACRM requires a name; use the email when none is mapped.
        if (empty($contact_params['Name'])) {
            $contact_params['Name'] = $email;
        }

        adfoin_lacrm_request('CreateContact', ['IsCompany' => false, 'AssignedTo' => $user_id] + $contact_params, $api_token, $record);
    }
}

/*
 * Builds the standard field parameters. When $existing (the current record)
 * is given, list fields keep their current entries: LACRM's EditContact
 * replaces the whole list, so new values are appended instead.
 */
function adfoin_lacrm_contact_params($fields, $existing = []) {
    $params = [];

    foreach (['Name', 'Company Name', 'Job Title', 'Background Info'] as $key) {
        if (isset($fields[$key])) {
            $params[$key] = $fields[$key];
        }
    }

    $lists = [
        'Email'   => isset($fields['Email']) ? ['Text' => $fields['Email'], 'Type' => 'Work'] : null,
        'Phone'   => isset($fields['Phone']) ? ['Text' => $fields['Phone'], 'Type' => 'Work'] : null,
        'Website' => isset($fields['Website']) ? ['Text' => $fields['Website']] : null,
        'Address' => adfoin_lacrm_address($fields),
    ];

    foreach ($lists as $key => $item) {
        if (!$item) continue;

        $current = isset($existing[$key]) && is_array($existing[$key]) ? $existing[$key] : [];

        if ($existing && adfoin_lacrm_list_has($current, $item)) continue;

        $params[$key] = $existing ? array_merge(adfoin_lacrm_clean_list($current), [$item]) : [$item];
    }

    return $params;
}

function adfoin_lacrm_list_has($list, $item) {
    $needle = strtolower(trim(isset($item['Text']) ? $item['Text'] : (($item['Street'] ?? '') . '|' . ($item['City'] ?? '') . '|' . ($item['Zip'] ?? ''))));

    foreach ($list as $entry) {
        $value = isset($item['Text']) ? ($entry['Text'] ?? '') : (($entry['Street'] ?? '') . '|' . ($entry['City'] ?? '') . '|' . ($entry['Zip'] ?? ''));

        if (strtolower(trim($value)) === $needle) return true;
    }

    return false;
}

/*
 * Drops read-only keys (TypeId) from list entries returned by LACRM.
 */
function adfoin_lacrm_clean_list($list) {
    return array_map(function ($entry) {
        unset($entry['TypeId']);
        return $entry;
    }, $list);
}

/*
 * Finds a company by exact name or a contact by exact email.
 * Returns the record, null when there is none, or false when the search
 * failed (logged), so the caller does not create a duplicate.
 */
function adfoin_lacrm_find($type, $search_term, $api_token, $record = []) {
    $parameters = [
        'SearchTerms'        => $search_term,
        'RecordTypeFilter'   => $type === 'company' ? 'Companies' : 'Contacts',
        'MaxNumberOfResults' => 25,
    ];

    $response = adfoin_lacrm_request('GetContacts', $parameters, $api_token);

    if (!is_array($response) || !isset($response['Results'])) {
        $reason = is_array($response) && isset($response['ErrorDescription']) ? $response['ErrorDescription'] : __('no response', 'advanced-form-integration');

        if ($record) {
            adfoin_lacrm_log_skipped($record, sprintf(__('Not sent: searching Less Annoying CRM for an existing record failed (%s).', 'advanced-form-integration'), $reason));
        }

        return false;
    }

    $needle = strtolower(trim($search_term));

    // Search matches partial text, so compare exactly.
    foreach ($response['Results'] as $result) {
        if ($type === 'company') {
            $name = isset($result['Company Name']) && $result['Company Name'] !== '' ? $result['Company Name'] : ($result['CompanyMetaData']['CompanyName'] ?? '');

            if (strtolower(trim((string) $name)) === $needle) return $result;
        } else {
            foreach ((array) ($result['Email'] ?? []) as $entry) {
                if (strtolower(trim($entry['Text'] ?? '')) === $needle) return $result;
            }
        }
    }

    return null;
}

/*
 * Kept for add-ons that call it; returns the matching ContactId or false.
 */
function adfoin_lacrm_search($type, $search_term, $api_token) {
    $found = adfoin_lacrm_find($type, $search_term, $api_token);

    return $found ? $found['ContactId'] : false;
}

function adfoin_lacrm_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('lacrm_not_sent', $message), 'https://api.lessannoyingcrm.com/v2/', ['method' => 'POST'], $record);
}

function adfoin_lacrm_request($function, $parameters, $api_token, $record = []) {
    $url = 'https://api.lessannoyingcrm.com/v2/';
    // The v2 API takes a JSON body and the raw API key as Authorization.
    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => $api_token
    ];
    $body = [
        'Function' => $function,
        'Parameters' => (object) $parameters
    ];

    $response = wp_remote_post($url, [
        'headers' => $headers,
        'body' => wp_json_encode($body),
        'timeout' => 30
    ]);

    if($record) {
        // Log the request body only, so the API key stays out of the log.
        adfoin_add_to_log($response, $url, ['method' => 'POST', 'body' => $body], $record);
    }

    if (is_wp_error($response)) {
        return [];
    }

    $response_body = wp_remote_retrieve_body($response);

    return json_decode($response_body, true);
}

/**
 * Build the v2 Address parameter: an array of address objects. Returns null
 * when no address part was mapped, so array_filter() drops the key.
 */
function adfoin_lacrm_address($fields) {
    $address = array_filter([
        'Street' => isset($fields['address__Street']) ? $fields['address__Street'] : '',
        'City' => isset($fields['address_City']) ? $fields['address_City'] : '',
        'State' => isset($fields['address_State']) ? $fields['address_State'] : '',
        'Zip' => isset($fields['address_Zip']) ? $fields['address_Zip'] : '',
        'Country' => isset($fields['address_Country']) ? $fields['address_Country'] : ''
    ], 'strlen');

    if (empty($address)) {
        return null;
    }

    $address['Type'] = 'Work';

    return $address;
}

add_action('wp_ajax_adfoin_get_lacrm_users', 'adfoin_get_lacrm_users');
function adfoin_get_lacrm_users() {
    adfoin_verify_nonce();

    $cred_id = isset($_POST['credId']) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $credentials = adfoin_get_credentials_by_id('lacrm', $cred_id);
    $api_token = isset($credentials['apiToken']) ? $credentials['apiToken'] : '';

    if (empty($api_token)) {
        wp_send_json_error(__('This account has no API key. Check it under AFI Settings > Less Annoying CRM.', 'advanced-form-integration'));
    }

    $response = adfoin_lacrm_request('GetUsers', [], $api_token);

    if (!is_array($response) || isset($response['ErrorDescription'])) {
        $reason = is_array($response) && isset($response['ErrorDescription']) ? $response['ErrorDescription'] : __('no response', 'advanced-form-integration');
        wp_send_json_error(sprintf(__('Could not load users: %s', 'advanced-form-integration'), $reason));
    }

    $users = [];

    foreach ($response as $user) {
        if (isset($user['UserId'])) {
            $users[$user['UserId']] = trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? ''));
        }
    }

    wp_send_json_success($users);
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
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=lacrm' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
            </td>
        </tr>

        <tr class="alternate" v-if="action.task == 'add_contact'">
            <td scope="row"><?php esc_attr_e('Assign to', 'advanced-form-integration'); ?></td>
            <td>
                <select name="fieldData[userId]" v-model="fielddata.userId">
                    <option value=""><?php _e('API key owner (default)', 'advanced-form-integration'); ?></option>
                    <option v-for="(name, id) in fielddata.users" :value="id">{{ name }}</option>
                </select>
                <div class="afi-spinner" v-bind:class="{'is-active': userLoading}"></div>
                <p class="description" style="color: #b32d2e;" v-if="userError">{{ userError }}</p>
            </td>
        </tr>

        

        <editable-field v-for="field in fields"
                        :key="field.value"
                        :field="field"
                        :trigger="trigger"
                        :action="action"
                        :fielddata="fielddata">
        </editable-field>
        <?php adfoin_pro_feature_notice( 'add_contact', 'Less Annoying CRM [PRO]', 'custom fields' ); ?>
    </table>
</script>
<?php
}