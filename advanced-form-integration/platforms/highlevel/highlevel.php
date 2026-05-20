<?php

add_filter('adfoin_action_providers', 'adfoin_highlevel_actions', 10, 1);

function adfoin_highlevel_actions($actions) {
    $actions['highlevel'] = [
        'title' => __('HighLevel', 'advanced-form-integration'),
        'tasks' => [
            'create_contact' => __('Create Contact', 'advanced-form-integration'),
        ]
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_highlevel_settings_tab', 10, 1);

function adfoin_highlevel_settings_tab($providers) {
    $providers['highlevel'] = __('HighLevel', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_highlevel_settings_view', 10, 1);

function adfoin_highlevel_settings_view($current_tab) {
    if ($current_tab != 'highlevel') {
        return;
    }

    $title = __('HighLevel', 'advanced-form-integration');
    $key   = 'highlevel';
    $arguments = wp_json_encode([
        'platform' => $key,
        'fields'   => [
            [
                'key'    => 'pitToken',
                'label'  => __('Private Integration Token', 'advanced-form-integration'),
                'hidden' => true,
            ],
            [
                'key'   => 'locationId',
                'label' => __('Location ID', 'advanced-form-integration'),
            ],
        ]
    ]);
    $instructions = __( '<p>In your HighLevel sub-account, open <strong>Settings &rarr; Private Integrations</strong> and create a Private Integration Token. Enable every scope this integration uses &mdash; <strong>Contacts</strong>, <strong>Opportunities</strong>, <strong>Pipelines</strong>, <strong>Custom Fields</strong>, <strong>Users</strong> and <strong>Workflows</strong> (both View &amp; Edit) &mdash; then paste the token above. A token missing a scope fails with <em>"The token is not authorized for this scope"</em>. Your <strong>Location ID</strong> is shown in <em>Settings &rarr; Business Profile</em>.</p>', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_highlevel_credentials', 'adfoin_get_highlevel_credentials', 10, 0);

function adfoin_get_highlevel_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('highlevel');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_highlevel_credentials', 'adfoin_save_highlevel_credentials', 10, 0);

function adfoin_save_highlevel_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Account Manager handles auth + nonce, merges by id so any legacy `apiKey`
    // stored on the record is preserved when the user edits a row.
    ADFOIN_Account_Manager::ajax_save_credentials( 'highlevel', [
        'pitToken'   => 'password',
        'locationId' => 'text',
    ] );
}

function adfoin_highlevel_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('highlevel');

    foreach ($credentials as $option) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_highlevel_action_fields');

function adfoin_highlevel_action_fields() {
    ?>
    <script type="text/template" id="highlevel-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?></th>
                <td></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('HighLevel Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_highlevel_credentials_list();
                        ?>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

            <?php adfoin_pro_feature_notice( 'create_contact', 'HighLevel [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_highlevel_fields', 'adfoin_get_highlevel_fields');

function adfoin_get_highlevel_fields() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $users     = adfoin_highlevel_get_users_list($cred_id);
    $ps        = adfoin_highlevel_get_pipelines_and_stages($cred_id);
    $workflows = adfoin_highlevel_get_workflows_list($cred_id);

    $contact_fields = [
        ['key' => 'contact_type', 'value' => 'Contact Type', 'description' => 'lead or customer'],
        ['key' => 'contact_firstName', 'value' => 'First Name', 'description' => ''],
        ['key' => 'contact_lastName', 'value' => 'Last Name', 'description' => ''],
        ['key' => 'contact_name', 'value' => 'Name', 'description' => ''],
        ['key' => 'contact_email', 'value' => 'Email', 'description' => ''],
        ['key' => 'contact_phone', 'value' => 'Phone', 'description' => ''],
        ['key' => 'contact_address1', 'value' => 'Address 1', 'description' => ''],
        ['key' => 'contact_city', 'value' => 'City', 'description' => ''],
        ['key' => 'contact_state', 'value' => 'State', 'description' => ''],
        ['key' => 'contact_postalCode', 'value' => 'Postal Code', 'description' => ''],
        ['key' => 'contact_website', 'value' => 'Website', 'description' => ''],
        ['key' => 'contact_timezone', 'value' => 'Timezone', 'description' => ''],
        ['key' => 'contact_companyName', 'value' => 'Company Name', 'description' => ''],
        ['key' => 'contact_source', 'value' => 'Source', 'description' => ''],
        ['key' => 'contact_dateOfBirth', 'value' => 'Date of Birth', 'description' => ''],
        ['key' => 'contact_assignedTo', 'value' => 'Owner ID', 'description' => $users],

    ];

    $workflow_fields = [
        ['key' => 'workflow_ids', 'value' => 'Workflow IDs', 'description' => $workflows ? 'Comma separated IDs. Available - ' . $workflows : 'Comma separated workflow IDs'],
    ];

    $opportunity_fields = [
        ['key' => 'opportunity_title', 'value' => 'Opportunity Name', 'description' => 'Required for opportunity creation'],
        ['key' => 'opportunity_pipeline', 'value' => 'Pipeline ID', 'description' => $ps['pipelines']],
        ['key' => 'opportunity_stage', 'value' => 'Stage ID', 'description' => $ps['stages']],
        ['key' => 'opportunity_value', 'value' => 'Opportunity Value', 'description' => ''],
        ['key' => 'opportunity_status', 'value' => 'Opportunity Status', 'description' => 'open, won, lost, abandoned'],
    ];

    $contact_fields = array_merge($contact_fields, $workflow_fields, $opportunity_fields);

    wp_send_json_success($contact_fields);
}

function adfoin_highlevel_get_users_list($cred_id) {
    $response = adfoin_highlevel_get_users($cred_id);

    if (is_wp_error($response) || ! $response) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || empty($body['users']) || ! is_array($body['users'])) {
        return false;
    }

    $users = [];
    foreach ($body['users'] as $user) {
        $name = isset($user['name'])
            ? $user['name']
            : trim( ( isset( $user['firstName'] ) ? $user['firstName'] : '' ) . ' ' . ( isset( $user['lastName'] ) ? $user['lastName'] : '' ) );
        $users[] = $name . ': ' . $user['id'];
    }

    return implode(', ', $users);
}

function adfoin_highlevel_get_pipelines_and_stages($cred_id) {
    $response = adfoin_highlevel_get_pipelines($cred_id);

    if (is_wp_error($response) || ! $response) {
        return ['pipelines' => '', 'stages' => ''];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        return ['pipelines' => '', 'stages' => ''];
    }

    $pipelines = [];
    $stages = [];

    if (isset($body['pipelines']) && is_array($body['pipelines'])) {
        foreach ($body['pipelines'] as $pipeline) {
            $pipelines[] = $pipeline['name'] . ': ' . $pipeline['id'];
            if (isset($pipeline['stages']) && is_array($pipeline['stages'])) {
                foreach ($pipeline['stages'] as $stage) {
                    $stages[] = $stage['name'] . ': ' . $stage['id'];
                }
            }
        }
    }

    return [
        'pipelines' => implode(', ', $pipelines),
        'stages' => implode(', ', $stages),
    ];
}

/*
 * Builds a "Name: id, Name: id" string of the account's workflows for the
 * Workflow IDs field description in the Create Contact action.
 */
function adfoin_highlevel_get_workflows_list($cred_id) {
    $response = adfoin_highlevel_get_workflows($cred_id);

    if (is_wp_error($response) || ! $response) {
        return '';
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || empty($body['workflows']) || ! is_array($body['workflows'])) {
        return '';
    }

    $workflows = [];
    foreach ($body['workflows'] as $workflow) {
        if (isset($workflow['name'], $workflow['id'])) {
            $workflows[] = $workflow['name'] . ': ' . $workflow['id'];
        }
    }

    return implode(', ', $workflows);
}

function adfoin_highlevel_search_contact($email, $cred_id) {
    $response = adfoin_highlevel_lookup_contact($email, $cred_id);

    if (is_wp_error($response) || ! $response) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || empty($body['contacts']) || ! is_array($body['contacts'])) {
        return false;
    }

    return isset($body['contacts'][0]['id']) ? $body['contacts'][0]['id'] : false;
}

add_action('adfoin_highlevel_job_queue', 'adfoin_highlevel_job_queue', 10, 1);

function adfoin_highlevel_job_queue($data) {
    adfoin_highlevel_send_data($data['record'], $data['posted_data']);
}

/*
 * Handles sending data to HighLevel API
 */
function adfoin_highlevel_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $task = $record['task'];
    $contact_id = '';
    $cred_id = empty($data['credId']) ? '' : $data['credId'];

    if ($task == 'create_contact') {
        $contact_data = array_filter([
            'type' => empty($data['contact_type']) ? 'lead' : adfoin_get_parsed_values( $data['contact_type'], $posted_data ),
            'firstName' => adfoin_get_parsed_values( $data['contact_firstName'], $posted_data ),
            'lastName' => adfoin_get_parsed_values( $data['contact_lastName'], $posted_data ),
            'name' => adfoin_get_parsed_values( $data['contact_name'], $posted_data ),
            'email' => adfoin_get_parsed_values( $data['contact_email'], $posted_data ),
            'phone' => adfoin_get_parsed_values( $data['contact_phone'], $posted_data ),
            'address1' => adfoin_get_parsed_values( $data['contact_address1'], $posted_data ),
            'city' => adfoin_get_parsed_values( $data['contact_city'], $posted_data ),
            'state' => adfoin_get_parsed_values( $data['contact_state'], $posted_data ),
            'postalCode' => adfoin_get_parsed_values( $data['contact_postalCode'], $posted_data ),
            'website' => adfoin_get_parsed_values( $data['contact_website'], $posted_data ),
            'timezone' => adfoin_get_parsed_values( $data['contact_timezone'], $posted_data ),
            'companyName' => adfoin_get_parsed_values( $data['contact_companyName'], $posted_data ),
            'source' => adfoin_get_parsed_values( $data['contact_source'], $posted_data ),
            'dateOfBirth' => adfoin_get_parsed_values( $data['contact_dateOfBirth'], $posted_data ),
            'assignedTo' => adfoin_get_parsed_values( $data['contact_assignedTo'], $posted_data ),
        ]);

        $email = isset($contact_data['email']) ? $contact_data['email'] : '';
        $contact_id = $email ? adfoin_highlevel_search_contact($email, $cred_id) : '';

        if ($contact_id) {
            $contact_response = adfoin_highlevel_update_contact($contact_id, $contact_data, $record, $cred_id);
        } else {
            $contact_response = adfoin_highlevel_create_contact($contact_data, $record, $cred_id);

            if (!is_wp_error($contact_response)) {
                $contact_body = json_decode(wp_remote_retrieve_body($contact_response), true);
                if (isset($contact_body['contact']['id'])) {
                    $contact_id = $contact_body['contact']['id'];
                } elseif (isset($contact_body['id'])) {
                    $contact_id = $contact_body['id'];
                }
            }
        }

        $pipeline_id       = adfoin_get_parsed_values( $data['opportunity_pipeline'], $posted_data );
        $opportunity_title = adfoin_get_parsed_values( $data['opportunity_title'], $posted_data );

        if ( $pipeline_id && $opportunity_title && $contact_id ) {
            $opportunity_data = array_filter([
                'title'     => $opportunity_title,
                'stageId'   => adfoin_get_parsed_values( $data['opportunity_stage'], $posted_data ),
                'value'     => adfoin_get_parsed_values( $data['opportunity_value'], $posted_data ),
                'status'    => adfoin_get_parsed_values( $data['opportunity_status'], $posted_data ),
                'contactId' => $contact_id,
            ]);

            if ( ! empty( $contact_data['assignedTo'] ) ) {
                $opportunity_data['assignedTo'] = $contact_data['assignedTo'];
            }

            adfoin_highlevel_create_opportunity( $pipeline_id, $opportunity_data, $record, $cred_id );
        }

        // Enroll the contact in any mapped workflows (comma-separated IDs — a
        // contact can belong to several workflows at once).
        $workflow_ids = adfoin_get_parsed_values( $data['workflow_ids'] ?? '', $posted_data );

        if ( $workflow_ids && $contact_id ) {
            $workflow_list = array_filter( array_map( 'trim', explode( ',', $workflow_ids ) ) );

            foreach ( $workflow_list as $workflow_id ) {
                adfoin_highlevel_add_to_workflow( $contact_id, $workflow_id, '', $record, $cred_id );
            }
        }
    }
}

/* ---------------------------------------------------------------------------
 * Auth routing.
 *
 * Two coexisting auth modes:
 *   - 'pit'    : Private Integration Token + Location ID, against
 *                services.leadconnectorhq.com (the current HighLevel API).
 *   - 'legacy' : v1 location API key against rest.gohighlevel.com (HighLevel
 *                positions v1 as legacy; still accepted at time of writing).
 *                Existing customer credentials silently use this path — the
 *                Settings UI only offers PIT for new accounts.
 *
 * Records that already contain `apiKey` and no `pitToken` keep working because
 * the Account Manager's save handler merges by `id`, never wiping fields that
 * aren't in the current field list.
 * ------------------------------------------------------------------------- */

function adfoin_highlevel_auth_mode($credentials) {
    if ( is_array( $credentials ) && ! empty( $credentials['pitToken'] ) ) {
        return 'pit';
    }
    return 'legacy';
}

function adfoin_highlevel_location_id($credentials) {
    return ( is_array( $credentials ) && isset( $credentials['locationId'] ) ) ? $credentials['locationId'] : '';
}

/* --- Endpoint wrappers. Each one accepts the same arguments regardless of
 *     auth mode and hides the v1-vs-LeadConnector path differences. --- */

function adfoin_highlevel_get_users($cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $loc = adfoin_highlevel_location_id($credentials);
        return adfoin_highlevel_request('users/?locationId=' . urlencode($loc), 'GET', [], [], $cred_id);
    }

    return adfoin_highlevel_request('users', 'GET', [], [], $cred_id);
}

function adfoin_highlevel_get_pipelines($cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $loc = adfoin_highlevel_location_id($credentials);
        return adfoin_highlevel_request('opportunities/pipelines?locationId=' . urlencode($loc), 'GET', [], [], $cred_id);
    }

    return adfoin_highlevel_request('pipelines', 'GET', [], [], $cred_id);
}

function adfoin_highlevel_lookup_contact($email, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $loc = adfoin_highlevel_location_id($credentials);
        $endpoint = 'contacts/?locationId=' . urlencode($loc) . '&query=' . urlencode($email);
        return adfoin_highlevel_request($endpoint, 'GET', [], [], $cred_id);
    }

    return adfoin_highlevel_request('contacts/lookup?email=' . urlencode($email), 'GET', [], [], $cred_id);
}

function adfoin_highlevel_create_contact($data, $record, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $data['locationId'] = adfoin_highlevel_location_id($credentials);
        return adfoin_highlevel_request('contacts/', 'POST', $data, $record, $cred_id);
    }

    return adfoin_highlevel_request('contacts', 'POST', $data, $record, $cred_id);
}

function adfoin_highlevel_update_contact($id, $data, $record, $cred_id) {
    // Path is the same in both APIs; transport handles base URL + auth headers.
    return adfoin_highlevel_request('contacts/' . $id, 'PUT', $data, $record, $cred_id);
}

function adfoin_highlevel_create_opportunity($pipeline_id, $data, $record, $cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $data['pipelineId'] = $pipeline_id;
        $data['locationId'] = adfoin_highlevel_location_id($credentials);
        return adfoin_highlevel_request('opportunities/', 'POST', $data, $record, $cred_id);
    }

    return adfoin_highlevel_request('pipelines/' . $pipeline_id . '/opportunities', 'POST', $data, $record, $cred_id);
}

function adfoin_highlevel_get_custom_fields($cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $loc = adfoin_highlevel_location_id($credentials);
        return adfoin_highlevel_request('locations/' . urlencode($loc) . '/customFields', 'GET', [], [], $cred_id);
    }

    return adfoin_highlevel_request('custom-fields', 'GET', [], [], $cred_id);
}

function adfoin_highlevel_get_workflows($cred_id) {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $loc = adfoin_highlevel_location_id($credentials);
        return adfoin_highlevel_request('workflows/?locationId=' . urlencode($loc), 'GET', [], [], $cred_id);
    }

    return adfoin_highlevel_request('workflows/', 'GET', [], [], $cred_id);
}

function adfoin_highlevel_add_to_workflow($contact_id, $workflow_id, $event_time, $record, $cred_id) {
    // Path is identical in both APIs; transport supplies the base URL + headers.
    // An empty body must serialize to {} (not []), so default to a plain object.
    $endpoint = 'contacts/' . $contact_id . '/workflow/' . $workflow_id;
    $body     = $event_time ? ['eventStartTime' => $event_time] : new stdClass();

    return adfoin_highlevel_request($endpoint, 'POST', $body, $record, $cred_id);
}

/*
 * HighLevel API Request (low-level transport).
 *
 * Picks base URL + headers from the stored credential's auth mode. Endpoint
 * paths and any locationId-in-body / locationId-in-query injection are the
 * responsibility of the wrappers above.
 */
function adfoin_highlevel_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('highlevel', $cred_id);

    if ( adfoin_highlevel_auth_mode($credentials) === 'pit' ) {
        $base_url = 'https://services.leadconnectorhq.com/';
        $headers  = [
            'Authorization' => 'Bearer ' . $credentials['pitToken'],
            'Version'       => '2021-07-28',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    } else {
        $api_key  = isset($credentials['apiKey']) ? $credentials['apiKey'] : '';
        $base_url = 'https://rest.gohighlevel.com/v1/';
        $headers  = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];
    }

    $url = $base_url . $endpoint;

    $args = [
        'timeout' => 30,
        'method'  => $method,
        'headers' => $headers,
    ];

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}
