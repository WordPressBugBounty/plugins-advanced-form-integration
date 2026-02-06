<?php

add_filter('adfoin_action_providers', 'adfoin_zendesksell_actions', 10, 1);

function adfoin_zendesksell_actions($actions) {
    $actions['zendesksell'] = array(
        'title' => __('Zendesk Sell', 'advanced-form-integration'),
        'tasks' => array(
            'add_lead' => __('Create New Lead', 'advanced-form-integration')
        )
    );
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_zendesksell_settings_tab', 10, 1);

function adfoin_zendesksell_settings_tab($providers) {
    $providers['zendesksell'] = __('Zendesk Sell', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_zendesksell_settings_view', 10, 1);

function adfoin_zendesksell_settings_view($current_tab) {
    if ($current_tab != 'zendesksell') {
        return;
    }

    $title = __('Zendesk Sell', 'advanced-form-integration');
    $key = 'zendesksell';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'accessToken',
                'label' => __('Personal Access Token', 'advanced-form-integration'),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Go to your Zendesk Sell account > Settings > Integrations > Oauth > Access Tokens</li>
                    <li>Add Access Token and copy it.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action( 'wp_ajax_adfoin_get_zendesksell_credentials', 'adfoin_get_zendesksell_credentials', 10, 0 );

function adfoin_get_zendesksell_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'zendesksell' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_zendesksell_credentials', 'adfoin_save_zendesksell_credentials', 10, 0 );

function adfoin_save_zendesksell_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'zendesksell' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_zendesksell_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'zendesksell' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_zendesksell_action_fields', 10, 1);

function adfoin_zendesksell_action_fields() {
    ?>
    <script type="text/template" id="zendesksell-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_lead'">
                <th scope="row"><?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?></th>
                <td>
                    <div class="spinner" :class="{'is-active': fieldsLoading}" style="padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'add_lead'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Zendesk Sell Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_zendesksell_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" :key="field.value" :field="field" :trigger="trigger" :action="action" :fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

// 5. Ajax for fields
add_action('wp_ajax_adfoin_get_zendesksell_lead_fields', 'adfoin_get_zendesksell_lead_fields');

function adfoin_get_zendesksell_lead_fields() {
    if (!wp_verify_nonce($_POST['_nonce'], 'advanced-form-integration')) die(__('Security check Failed', 'advanced-form-integration'));

    $cred_id = isset($_POST['credId']) ? sanitize_text_field($_POST['credId']) : '';
    $owners = adfoin_get_zendesksell_owners( $cred_id );
    $owner_description = !empty($owners) ? implode(', ', $owners) : __('No owners found', 'advanced-form-integration');

    $fields = array(
        array('key' => 'email', 'value' => 'Email', 'description' => ''),
        array('key' => 'first_name', 'value' => 'First Name', 'description' => ''),
        array('key' => 'last_name', 'value' => 'Last Name', 'description' => ''),        
        array('key' => 'phone', 'value' => 'Phone', 'description' => ''),
        array('key' => 'mobile', 'value' => 'Mobile', 'description' => ''),
        array('key' => 'fax', 'value' => 'Fax', 'description' => ''),
        array('key' => 'organization_name', 'value' => 'Organization Name', 'description' => ''),
        array('key' => 'title', 'value' => 'Title', 'description' => ''),
        array('key' => 'status', 'value' => 'Status', 'description' => ''),
        array('key' => 'source_id', 'value' => 'Source ID', 'description' => ''),
        array('key' => 'description', 'value' => 'Description', 'description' => ''),
        array('key' => 'industry', 'value' => 'Industry', 'description' => ''),
        array('key' => 'website', 'value' => 'Website', 'description' => ''),
        array('key' => 'twitter', 'value' => 'Twitter', 'description' => ''),
        array('key' => 'facebook', 'value' => 'Facebook', 'description' => ''),
        array('key' => 'linkedin', 'value' => 'Linkedin', 'description' => ''),
        array('key' => 'skype', 'value' => 'Skype', 'description' => ''),
        array('key' => 'line1', 'value' => 'Address Line 1', 'description' => ''),
        array('key' => 'city', 'value' => 'City', 'description' => ''),
        array('key' => 'postal_code', 'value' => 'Postal Code', 'description' => ''),
        array('key' => 'state', 'value' => 'State', 'description' => ''),
        array('key' => 'country', 'value' => 'Country', 'description' => ''),
        array('key' => 'owner_id', 'value' => 'Owner ID', 'description' => $owner_description),
    );

    wp_send_json_success($fields);
}

function adfoin_get_zendesksell_owners( $cred_id = '' ) {
    $response = adfoin_zendesksell_request('users', 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) return array();

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $ursers = array();

    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $user) {
            $users[] = $user['data']['name'] . ': ' . $user['data']['id'];
        }
    }

    return $users;
}

function adfoin_zendesksell_job_queue( $data ) {
    adfoin_zendesksell_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_zendesksell_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    
    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task          = $record['task'];
    $cred_id = isset( $data['cred_id'] ) ? $data['cred_id'] : '';

    if( $task == 'add_lead' ) {
        $address_fields = array_filter(array(
            'line1'       => adfoin_get_parsed_values($data['line1'], $posted_data),
            'city'        => adfoin_get_parsed_values($data['city'], $posted_data),
            'postal_code' => adfoin_get_parsed_values($data['postal_code'], $posted_data),
            'state'       => adfoin_get_parsed_values($data['state'], $posted_data),
            'country'     => adfoin_get_parsed_values($data['country'], $posted_data),
        ));

        $lead_data = array(
            'data' => array_filter( array(
                'first_name'        => adfoin_get_parsed_values($data['first_name'], $posted_data),
                'last_name'         => adfoin_get_parsed_values($data['last_name'], $posted_data),
                'email'             => adfoin_get_parsed_values($data['email'], $posted_data),
                'phone'             => adfoin_get_parsed_values($data['phone'], $posted_data),
                'mobile'            => adfoin_get_parsed_values($data['mobile'], $posted_data),
                'fax'               => adfoin_get_parsed_values($data['fax'], $posted_data),
                'organization_name' => adfoin_get_parsed_values($data['organization_name'], $posted_data),
                'title'             => adfoin_get_parsed_values($data['title'], $posted_data),
                'status'            => (int) adfoin_get_parsed_values($data['status'], $posted_data),
                'source_id'         => (int) adfoin_get_parsed_values($data['source_id'], $posted_data),
                'description'       => adfoin_get_parsed_values($data['description'], $posted_data),
                'industry'          => adfoin_get_parsed_values($data['industry'], $posted_data),
                'website'           => adfoin_get_parsed_values($data['website'], $posted_data),
                'twitter'           => adfoin_get_parsed_values($data['twitter'], $posted_data),
                'facebook'          => adfoin_get_parsed_values($data['facebook'], $posted_data),
                'linkedin'          => adfoin_get_parsed_values($data['linkedin'], $posted_data),
                'skype'             => adfoin_get_parsed_values($data['skype'], $posted_data),
                'address'           => !empty($address_fields) ? $address_fields : null,
                'owner_id'          => (int) adfoin_get_parsed_values($data['owner_id'], $posted_data),
            ))
        );

        $response = adfoin_zendesksell_request('leads/upsert?email', 'POST', $lead_data, $record, $cred_id);
    }
}

function adfoin_zendesksell_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'zendesksell', $cred_id );
    $token = $credentials['accessToken'] ? $credentials['accessToken'] : '';
    $base_url = "https://api.getbase.com/v2/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json'
        ),
    );

    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}