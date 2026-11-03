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
    $arguments = wp_json_encode([
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
    adfoin_verify_nonce();
    wp_send_json_success(adfoin_read_credentials('intercom'));
}

add_action('wp_ajax_adfoin_save_intercom_credentials', 'adfoin_save_intercom_credentials');
function adfoin_save_intercom_credentials() {

    adfoin_verify_nonce();

    if ($_POST['platform'] === 'intercom') {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials('intercom', $data);
    }

    wp_send_json_success();
}

add_action('wp_ajax_adfoin_get_intercom_fields', 'adfoin_get_intercom_fields');
function adfoin_get_intercom_fields()
{
    adfoin_verify_nonce();

    wp_send_json_success(array(
        array('key' => 'email', 'value' => 'Email', 'description' => 'Required. An existing contact with this email is updated instead of duplicated.'),
        array('key' => 'external_id', 'value' => 'External ID', 'description' => 'External identifier from your system'),
        array('key' => 'name', 'value' => 'Name', 'description' => 'Full name of the contact'),
        array('key' => 'phone', 'value' => 'Phone', 'description' => 'Contact phone number'),
        array('key' => 'role', 'value' => 'Role', 'description' => 'user or lead (default lead). A user can\'t be turned back into a lead.'),
        array('key' => 'owner_id', 'value' => 'Owner ID', 'description' => 'ID of the team member who owns this contact'),
        array('key' => 'avatar_image_url', 'value' => 'Avatar Image URL', 'description' => 'URL to contact avatar image'),
    ));
}

function adfoin_intercom_credentials_list() {
    foreach (adfoin_read_credentials('intercom') as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

/*
 * "user" or "lead" from a mapped value; anything else becomes a lead (with a note).
 */
function adfoin_intercom_role($value, &$notes) {
    $value = strtolower(trim((string) $value));

    if ($value === 'user' || $value === 'lead') {
        return $value;
    }

    if ($value !== '') {
        $notes[] = sprintf(__('role "%s" is not user or lead, added as lead', 'advanced-form-integration'), $value);
    }

    return 'lead';
}

/*
 * Contact body from mapped values, shared by the free and PRO actions.
 */
function adfoin_intercom_contact_body($fields, &$notes) {
    $contact = array('role' => adfoin_intercom_role(isset($fields['role']) ? $fields['role'] : '', $notes));

    foreach (array('email', 'external_id', 'name', 'phone') as $key) {
        if (isset($fields[$key]) && $fields[$key] !== '') {
            $contact[$key] = $fields[$key];
        }
    }

    foreach (array('owner_id', 'signed_up_at', 'last_seen_at') as $key) {
        if (isset($fields[$key]) && is_numeric($fields[$key])) {
            $contact[$key] = (int) $fields[$key];
        }
    }

    if (isset($fields['unsubscribed_from_emails']) && $fields['unsubscribed_from_emails'] !== '') {
        $contact['unsubscribed_from_emails'] = filter_var($fields['unsubscribed_from_emails'], FILTER_VALIDATE_BOOLEAN);
    }

    if (!empty($fields['avatar_image_url'])) {
        $contact['avatar'] = $fields['avatar_image_url'];
    }

    return $contact;
}

/*
 * Mapped values, parsed and trimmed.
 */
function adfoin_intercom_collect($data, $posted_data) {
    $fields = array();

    foreach ($data as $key => $value) {
        if ($key === 'credId' || !is_string($value)) continue;

        $fields[$key] = trim((string) adfoin_get_parsed_values($value, $posted_data));
    }

    return $fields;
}

/*
 * Returns the sanitized email, or '' after logging why the submission was skipped.
 */
function adfoin_intercom_parse_email($raw, $record) {
    $email = sanitize_email($raw);

    if (!$email) {
        adfoin_intercom_log_skipped(
            $record,
            $raw === ''
                ? __('Not sent: the mapped email field is empty.', 'advanced-form-integration')
                : sprintf(__('Not sent: "%s" is not a valid email address.', 'advanced-form-integration'), $raw)
        );
    }

    return $email;
}

function adfoin_intercom_log_skipped($record, $message) {
    adfoin_add_to_log(new WP_Error('intercom_not_sent', $message), 'https://api.intercom.io/', array('method' => 'POST'), $record);
}

/*
 * The contact ID for an email, '' when there is none, or a WP_Error / failed
 * response when the search itself failed.
 */
function adfoin_intercom_find_contact_by_email($email, $cred_id = '') {
    $response = adfoin_intercom_request('contacts/search', 'POST', array(
        'query' => array('field' => 'email', 'operator' => '=', 'value' => $email),
    ), array(), $cred_id);

    if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
        return is_wp_error($response) ? $response : new WP_Error('intercom_search', adfoin_intercom_error_message($response));
    }

    $result = json_decode(wp_remote_retrieve_body($response), true);

    return isset($result['data'][0]['id']) ? $result['data'][0]['id'] : '';
}

/*
 * Updates the contact with this email, or creates it. Intercom creates a
 * second contact for an email it already has, so it is searched first.
 * A user can't be turned back into a lead, so role is only sent on update
 * when it is "user". Logs once.
 */
function adfoin_intercom_upsert_contact($contact, $record, $cred_id, $notes = array()) {
    $contact_id = adfoin_intercom_find_contact_by_email($contact['email'], $cred_id);

    if (is_wp_error($contact_id)) {
        adfoin_intercom_log($contact_id, 'contacts/search', 'POST', array(), $record, $cred_id);
        return $contact_id;
    }

    if ($contact_id) {
        if ($contact['role'] !== 'user') {
            unset($contact['role']);
        }

        $notes[]  = __('existing contact updated', 'advanced-form-integration');
        $endpoint = 'contacts/' . $contact_id;
        $method   = 'PUT';
    } else {
        $endpoint = 'contacts';
        $method   = 'POST';
    }

    $response = adfoin_intercom_request($endpoint, $method, $contact, array(), $cred_id);
    adfoin_intercom_log($response, $endpoint, $method, $contact, $record, $cred_id, $notes);

    return $response;
}

function adfoin_intercom_build_request($endpoint, $method, $data, $cred_id) {
    $credentials  = adfoin_get_credentials_by_id('intercom', $cred_id);
    $access_token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';

    if ( empty( $access_token ) ) {
        return new WP_Error( 'intercom_no_account', __( 'No Intercom account is selected, or the account has no Access Token. Check the integration and AFI Settings > Intercom.', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization'    => 'Bearer ' . $access_token,
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'Intercom-Version' => '2.15',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT' ), true ) ) {
        $args['body'] = wp_json_encode($data);
    }

    return array('https://api.intercom.io/' . $endpoint, $args);
}

function adfoin_intercom_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $request = adfoin_intercom_build_request($endpoint, $method, $data, $cred_id);

    if (is_wp_error($request)) {
        if ($record) {
            adfoin_add_to_log($request, $endpoint, array('method' => $method, 'body' => $data), $record);
        }

        return $request;
    }

    list($url, $args) = $request;

    $response = wp_remote_request($url, $args);

    // 429 retry — honour Retry-After, clamp 1–30 s, one retry only.
    if ( ! is_wp_error( $response ) && 429 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $wait = (int) wp_remote_retrieve_header( $response, 'retry-after' );
        if ( $wait < 1 )  { $wait = 1; }
        if ( $wait > 30 ) { $wait = 30; }
        sleep( $wait );
        $response = wp_remote_request( $url, $args );
    }

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

/*
 * Intercom's error messages as one line.
 */
function adfoin_intercom_error_message($response) {
    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['errors']) && is_array($body['errors'])) {
        return implode(' ', array_filter(wp_list_pluck($body['errors'], 'message')));
    }

    return wp_remote_retrieve_response_code($response) . ' ' . wp_remote_retrieve_response_message($response);
}

/*
 * Logs a response that was fetched without a record.
 */
function adfoin_intercom_log($response, $endpoint, $method, $data, $record, $cred_id, $notes = array()) {
    $request = adfoin_intercom_build_request($endpoint, $method, $data, $cred_id);

    if (!is_wp_error($response)) {
        if (wp_remote_retrieve_response_code($response) >= 300) {
            $response['response']['message'] = adfoin_intercom_error_message($response);
        } elseif ($notes) {
            $response['response']['message'] = sprintf('OK (%s)', implode('; ', $notes));
        }
    }

    adfoin_add_to_log(
        $response,
        is_wp_error($request) ? $endpoint : $request[0],
        is_wp_error($request) ? array('method' => $method, 'body' => $data) : $request[1],
        $record
    );
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
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Intercom Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=intercom' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Intercom [PRO]', 'custom attributes, notes, tags and events' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_intercom_job_queue', 'adfoin_intercom_job_queue', 10, 1 );

function adfoin_intercom_job_queue($data) {
    adfoin_intercom_send_data($data['record'], $data['posted_data']);
}

function adfoin_intercom_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $fields  = adfoin_intercom_collect($data, $posted_data);
    $email   = adfoin_intercom_parse_email(isset($fields['email']) ? $fields['email'] : '', $record);

    if (!$email) return;

    $fields['email'] = $email;
    $notes           = array();

    adfoin_intercom_upsert_contact(adfoin_intercom_contact_body($fields, $notes), $record, $cred_id, $notes);
}
