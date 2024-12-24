<?php

add_filter( 'adfoin_action_providers', 'adfoin_instantly_actions', 10, 1 );

function adfoin_instantly_actions( $actions ) {

    $actions['instantly'] = array(
        'title' => __( 'Instantly', 'advanced-form-integration' ),
        'tasks' => array(
            'add_lead' => __( 'Add Lead', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_instantly_settings_tab', 10, 1 );

function adfoin_instantly_settings_tab( $providers ) {
    $providers['instantly'] = __( 'Instantly', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_instantly_settings_view', 10, 1 );

function adfoin_instantly_settings_view( $current_tab ) {
    if( $current_tab != 'instantly' ) {
        return;
    }

    $title = __( 'Instantly', 'advanced-form-integration' );
    $key = 'instantly';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'apiKey',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'required' => true
            ]
        ]
    ]);
    $instructions = '<p>Go to Instantly Dashboard > Settings > Integrations > API</p>';

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_instantly_credentials', 'adfoin_get_instantly_credentials', 10, 0 );

function adfoin_get_instantly_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'instantly' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_instantly_credentials', 'adfoin_save_instantly_credentials', 10, 0 );

function adfoin_save_instantly_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'instantly' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

add_action( 'adfoin_action_fields', 'adfoin_instantly_action_fields' );

function adfoin_instantly_action_fields() {
    ?>
    <script type="text/template" id="instantly-action-template">
        <table class="form-table">
            
            <tr valign="top" v-if="action.task == 'add_lead'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'add_lead'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Instantly Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getCampaigns">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_instantly_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" class="alternate"  v-if="action.task == 'add_lead'">
                <td scope="row">
                    <?php esc_attr_e( 'Select Campaign', 'advanced-form-integration' ); ?>
                </td>
                <td>
                    <select name="fieldData[campaignId]" v-model="fielddata.campaignId">
                        <option value=""> <?php _e( 'Select Campaign...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(campaign, index) in fielddata.campaigns" :value="index"> {{ campaign }} </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': campaignLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_instantly_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'instantly' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

function adfoin_instantly_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'instantly', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    $base_url = "https://api.instantly.ai/api/v1/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json'
        )
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode(array_merge(['api_key' => $api_key], $data));
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_instantly_job_queue', 'adfoin_instantly_job_queue', 10, 1 );

function adfoin_instantly_job_queue( $data ) {
    adfoin_instantly_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_instantly_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if (isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic($record_data['action_data']['cl'], $posted_data)) return;

    $data = $record_data['field_data'];
    $campaign_id = isset( $data['campaignId'] ) ? $data['campaignId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    if ( $task == 'add_lead' ) {
        $leads = [];

        foreach ($data['leads'] as $lead) {
            $leads[] = array_filter([
                'email' => adfoin_get_parsed_values($lead['email'], $posted_data),
                'first_name' => adfoin_get_parsed_values($lead['first_name'], $posted_data),
                'last_name' => adfoin_get_parsed_values($lead['last_name'], $posted_data),
                'company_name' => adfoin_get_parsed_values($lead['company_name'], $posted_data),
                'personalization' => adfoin_get_parsed_values($lead['personalization'], $posted_data),
                'phone' => adfoin_get_parsed_values($lead['phone'], $posted_data),
                'website' => adfoin_get_parsed_values($lead['website'], $posted_data)
            ]);
        }

        $lead_data = [
            'campaign_id' => $campaign_id,
            'leads' => $leads
        ];

        $result = adfoin_instantly_request( "lead/add", 'POST', $lead_data, $record, $cred_id );
    }
}

add_action( 'wp_ajax_adfoin_get_instantly_campaigns', 'adfoin_get_instantly_campaigns', 10, 0 );

function adfoin_get_instantly_campaigns() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $credentials = adfoin_get_credentials_by_id( 'instantly', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    $response = adfoin_instantly_request("campaign/list?api_key={$api_key}&skip=0&limit=100");

    if (is_wp_error($response)) {
        wp_send_json_error(__('Failed to fetch campaigns.', 'advanced-form-integration'));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body)) {
        wp_send_json_error(__('No campaigns found.', 'advanced-form-integration'));
    }

    $campaigns = [];

    foreach ($body as $campaign) {
        $campaigns[$campaign['id']] = $campaign['name'];
    }

    wp_send_json_success($campaigns);
}
