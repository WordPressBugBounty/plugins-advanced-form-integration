<?php

add_filter( 'adfoin_action_providers', 'adfoin_doppler_actions', 10, 1 );

function adfoin_doppler_actions( $actions ) {
    $actions['doppler'] = array(
        'title' => __( 'Doppler', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_doppler_settings_tab', 10, 1 );

function adfoin_doppler_settings_tab( $providers ) {
    $providers['doppler'] = __( 'Doppler', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_doppler_settings_view', 10, 1 );

function adfoin_doppler_settings_view( $current_tab ) {
    if( $current_tab != 'doppler' ) return;

    $title = __( 'Doppler', 'advanced-form-integration' );
    $key = 'doppler';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'account_email',
                'label' => __( 'Account Email', 'advanced-form-integration' ),
                'hidden' => false
            ],
            [
                'key' => 'api_key',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Log in to your Doppler account.</li>
                    <li>Go to Profile &gt; Control Panel Advanced Preferences &gt; Doppler API. Copy the API Key.</li>
                    <li>Enter the API Key below.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_doppler_credentials', 'adfoin_get_doppler_credentials', 10, 0 );

function adfoin_get_doppler_credentials() {
    if (!adfoin_verify_nonce()) return;
    $all_credentials = adfoin_read_credentials( 'doppler' );
    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_doppler_credentials', 'adfoin_save_doppler_credentials', 10, 0 );

function adfoin_save_doppler_credentials() {
    if (!adfoin_verify_nonce()) return;
    $platform = sanitize_text_field( $_POST['platform'] );
    if( 'doppler' == $platform ) {
        $data_to_save = array();
        if (isset($_POST['data']) && is_array($_POST['data'])) {
            foreach ($_POST['data'] as $key => $value) {
                if (is_array($value)) {
                     $data_to_save[$key] = adfoin_array_map_recursive( 'sanitize_text_field', $value );
                } else {
                    $data_to_save[sanitize_key($key)] = sanitize_text_field( $value );
                }
            }
        }
        adfoin_save_credentials( $platform, $data_to_save );
    }
    wp_send_json_success();
}

function adfoin_doppler_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'doppler' );
    if (is_array($credentials)) {
        foreach( $credentials as $option ) {
            if (isset($option['id']) && isset($option['title'])) {
                 $html .= '<option value="'. esc_attr($option['id']) .'">' . esc_html($option['title']) . '</option>';
            }
        }
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_doppler_action_fields' );

function adfoin_doppler_action_fields() {
    ?>
    <script type="text/template" id="doppler-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label>
                        <?php esc_attr_e( 'Doppler Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_doppler_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Select List', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="list in fielddata.lists" :value="list.id"> {{ list.name }} </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_doppler_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'doppler', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';
    $account_email = isset( $credentials['account_email'] ) ? $credentials['account_email'] : '';

    $api_base_url = "https://restapi.fromdoppler.com/accounts/{$account_email}/";
    $url = $api_base_url . $endpoint;

    $args = array(
        'timeout'     => 30,
        'method'      => strtoupper( $method ),
        'user-agent'  => 'Advanced Form Integration (WordPress Plugin)',
        'headers'     => array(
            'Authorization' => 'token ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );

    if ( 'POST' == $args['method'] || 'PUT' == $args['method'] || 'PATCH' == $args['method'] ) {
        if (!empty($data)) {
            $args['body'] = json_encode($data);
        } else {
            $args['body'] = json_encode((object)array());
        }
    } elseif ('GET' == $args['method'] && !empty($data)) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_doppler_lists', 'adfoin_get_doppler_lists', 10, 0 );

function adfoin_get_doppler_lists() {
    if (!adfoin_verify_nonce()) return;
    $cred_id = isset($_POST['credId']) ? sanitize_text_field( $_POST['credId'] ) : '';

    $response = adfoin_doppler_request( 'lists', 'GET', array(), array(), $cred_id );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $response_code = wp_remote_retrieve_response_code( $response );

    if ( $response_code == 200 && isset( $body['items'] ) && is_array( $body['items'] ) ) {
        $lists = array();
        foreach ( $body['items'] as $list_item ) {
            if (isset($list_item['listId']) && isset($list_item['name'])) {
                 $lists[] = array('id' => $list_item['listId'], 'name' => $list_item['name']);
            }
        }
        wp_send_json_success( $lists );
    }
}

add_action( 'adfoin_doppler_job_queue', 'adfoin_doppler_job_queue', 10, 1 );

function adfoin_doppler_job_queue( $data ) {
    adfoin_doppler_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_doppler_send_data( $record, $posted_data ) {
    if (!is_array($record) || empty($record['data'])) {
        return;
    }
    $record_data = json_decode( $record['data'], true );

    if ( isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_map_data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $list_id = isset( $field_map_data['listId'] ) ? $field_map_data['listId'] : '';
    $cred_id = isset( $field_map_data['credId'] ) ? $field_map_data['credId'] : '';
    $task = isset($record['task']) ? $record['task'] : '';

    if ( $task == 'subscribe' ) {
        $subscriber_payload = array(
            'email' => '',
            'fields' => array()
        );

        $subscriber_payload['fields'] = array();

        foreach ($field_map_data as $key => $value_mapping) {
            if ($key == 'listId' || $key == 'credId') continue;
            $parsed_value = adfoin_get_parsed_values($value_mapping, $posted_data);
            
            if ($key == 'email') {
                $subscriber_payload['email'] = $parsed_value;
            } else {
                if( !empty($parsed_value) ) {
                    $subscriber_payload['fields'][] = array(
                        'name' => $key,
                        'value' => $parsed_value
                    );
                }
            }
        }

        if($list_id && !empty($subscriber_payload['email'])) {
            $endpoint = 'lists/' . urlencode($list_id) . '/subscribers';
            $result = adfoin_doppler_request($endpoint, 'POST', $subscriber_payload, $record, $cred_id);
        }
    }
}