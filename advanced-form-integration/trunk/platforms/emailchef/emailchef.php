<?php

add_filter( 'adfoin_action_providers', 'adfoin_emailchef_actions', 10, 1 );

function adfoin_emailchef_actions( $actions ) {
    $actions['emailchef'] = array(
        'title' => __( 'Emailchef', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_emailchef_settings_tab', 10, 1 );

function adfoin_emailchef_settings_tab( $providers ) {
    $providers['emailchef'] = __( 'Emailchef', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_emailchef_settings_view', 10, 1 );

function adfoin_emailchef_settings_view( $current_tab ) {
    if( $current_tab != 'emailchef' ) {
        return;
    }

    $title = __( 'eMailChef', 'advanced-form-integration' );
    $key = 'emailchef';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'consumer_key',
                'label' => __( 'Consumer Key', 'advanced-form-integration' ),
                'hidden' => false
            ],
            [
                'key' => 'consumer_secret',
                'label' => __( 'Consumer Secret', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Log in to your eMailChef account.</li>
                    <li>Navigate to the API / Integrations section to find your Consumer Key and Consumer Secret.</li>
                    <li>Enter these credentials below.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_emailchef_credentials', 'adfoin_get_emailchef_credentials', 10, 0 );

function adfoin_get_emailchef_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'emailchef' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_emailchef_credentials', 'adfoin_save_emailchef_credentials', 10, 0 );

function adfoin_save_emailchef_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'emailchef' == $platform ) {
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


function adfoin_emailchef_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'emailchef' );

    if (is_array($credentials)) {
        foreach( $credentials as $option ) {
            if (isset($option['id']) && isset($option['title'])) {
                 $html .= '<option value="'. esc_attr($option['id']) .'">' . esc_html($option['title']) . '</option>';
            }
        }
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_emailchef_action_fields' );

function adfoin_emailchef_action_fields() {
    ?>
    <script type="text/template" id="emailchef-action-template">
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
                        <?php esc_attr_e( 'eMailChef Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_emailchef_credentials_list();
                        ?>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=emailchef' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
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

function adfoin_emailchef_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'emailchef', $cred_id );
    $consumer_key = isset( $credentials['consumer_key'] ) ? $credentials['consumer_key'] : '';
    $consumer_secret = isset( $credentials['consumer_secret'] ) ? $credentials['consumer_secret'] : '';

    if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
        if ($record) {
            adfoin_add_to_log(new WP_Error('missing_credentials', 'eMailChef Consumer Key or Secret not set.'), $endpoint, array(), $record);
        }
        return new WP_Error('missing_credentials', 'eMailChef Consumer Key or Secret not set.');
    }

    $api_base_url = 'https://app.emailchef.com/apps/api/v1/';
    $url = $api_base_url . $endpoint;

    $args = array(
        'timeout'     => 30,
        'method'      => strtoupper( $method ),
        'user-agent'  => 'Advanced Form Integration (WordPress Plugin)',
        'headers'     => array(
            'consumerKey'    => $consumer_key,
            'consumerSecret' => $consumer_secret,
            'Content-Type'   => 'application/json',
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

add_action( 'wp_ajax_adfoin_get_emailchef_lists', 'adfoin_get_emailchef_lists', 10, 0 );

function adfoin_get_emailchef_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = isset($_POST['credId']) ? sanitize_text_field( $_POST['credId'] ) : '';

    if (empty($cred_id)) {
        wp_send_json_error(array('message' => 'Missing credential ID.'));
        return;
    }

    $response = adfoin_emailchef_request( 'lists', 'GET', array(), array('id' => 'api_call_get_lists'), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error(array('message' => $response->get_error_message()));
        return;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $response_code = wp_remote_retrieve_response_code( $response );

    if ( $response_code == 200 && is_array( $body ) ) {
        $lists = array();
        foreach ( $body as $list_item ) {
            if (isset($list_item['id']) && isset($list_item['name'])) {
                 $lists[] = array('id' => $list_item['id'], 'name' => $list_item['name']);
            }
        }
        wp_send_json_success( $lists );
    }
}

add_action( 'adfoin_emailchef_job_queue', 'adfoin_emailchef_job_queue', 10, 1 );

function adfoin_emailchef_job_queue( $data ) {
    adfoin_emailchef_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_emailchef_send_data( $record, $posted_data ) {
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
            'instance_in' => array(
                'status' => 'subscribed',
                'mode' => 'ADMIN'
            )
        );
        $custom_fields_payload = array();

        foreach ($field_map_data as $key => $value_mapping) {
            if ($key == 'listId' || $key == 'credId') continue;

            $parsed_value = adfoin_get_parsed_values($value_mapping, $posted_data);
            if ($parsed_value === null || $parsed_value === false) {
                if ($parsed_value === null) continue;
            }

            if ($key == 'email') {
                $subscriber_payload['instance_in']['email'] = $parsed_value;
            } elseif ($key == 'firstname') {
                $subscriber_payload['instance_in']['firstname'] = $parsed_value;
            } elseif ($key == 'lastname') {
                $subscriber_payload['instance_in']['lastname'] = $parsed_value;
            } elseif ($key == 'status' && !empty($parsed_value)) {
                $subscriber_payload['instance_in']['status'] = $parsed_value;
            }
        }
        
        if (!empty($custom_fields_payload)) {
            $subscriber_payload['instance_in']['custom_fields'] = $custom_fields_payload;
        }

        if($list_id) {
            $subscriber_payload['instance_in']['list_id'] = $list_id;
        }

        $result = adfoin_emailchef_request('contacts', 'POST', $subscriber_payload, $record, $cred_id);
    }
}
