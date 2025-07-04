<?php
add_filter( 'adfoin_action_providers', 'adfoin_sender_actions', 10, 1 );

function adfoin_sender_actions( $actions ) {
    $actions['sender'] = array(
        'title' => __( 'Sender', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to Group', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sender_settings_tab', 10, 1 );

function adfoin_sender_settings_tab( $providers ) {
    $providers['sender'] = __( 'Sender', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sender_settings_view', 10, 1 );

function adfoin_sender_settings_view( $current_tab ) {
    if( $current_tab != 'sender' ) {
        return;
    }

    $title = __( 'Sender', 'advanced-form-integration' );
    $key = 'sender';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'apiKey',
                'label' => __( 'API access token', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Go to Settings > API access tokens.</li>
                    <li>Create and copy the token</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_sender_credentials', 'adfoin_get_sender_credentials', 10, 0 );

function adfoin_get_sender_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'sender' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_sender_credentials', 'adfoin_save_sender_credentials', 10, 0 );

function adfoin_save_sender_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'sender' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_sender_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'sender' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_sender_action_fields' );

function adfoin_sender_action_fields() {
    ?>
    <script type="text/template" id="sender-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Sender Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_sender_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Group', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId">
                        <option value=""> <?php _e( 'Select Group...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(list, index) in fielddata.groups" :value="index"> {{ list }} </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_sender_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'sender', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    $base_url = "https://api.sender.net/v2/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
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

add_action( 'wp_ajax_adfoin_get_sender_groups', 'adfoin_get_sender_groups', 10, 0 );

function adfoin_get_sender_groups() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $response = adfoin_sender_request( 'groups', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset($body['data']) && is_array( $body['data'] ) ) {
        $lists = array();

        foreach ( $body['data'] as $list ) {
            $lists[ $list['id'] ] = $list['title'];
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action('wp_ajax_adfoin_get_sender_fields', 'adfoin_sender_get_fields', 10, 0);

function adfoin_sender_get_fields() {

    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $response = adfoin_sender_request( 'fields', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset($body['data']) && is_array( $body['data'] ) ) {
        $fields = array();

        foreach ($body['data'] as $field) {
            $key = str_replace(['{', '}'], '', $field['name']);
            $key = trim($key);
            if (!empty($field['default']) && $field['default'] === true) {
            $key = 'default__' . $key;
            } else {
            $key = 'custom__' . $key;
            }

            $field_array = ['key' => $key, 'value' => $field['title']];

            if ($key === 'default__phone' || $key === 'custom__phone') {
            $field_array['description'] = __('Add country code (e.g. +1)', 'advanced-form-integration');
            }

            $fields[] = $field_array;
        }

        wp_send_json_success( $fields );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_sender_job_queue', 'adfoin_sender_job_queue', 10, 1 );

function adfoin_sender_job_queue( $data ) {
    adfoin_sender_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sender_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $group_id = isset( $data['groupId'] ) ? $data['groupId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    unset( $data['groupId'], $data['credId'] );

    if ( $task == 'subscribe' ) {
        $subscriber_data = array();
        $custom_fields = array();

        foreach ( $data as $clean_key => $value ) {
            $value = adfoin_get_parsed_values( $value, $posted_data );

            if ( empty( $value ) ) {
                continue;
            }

            if ( strpos( $clean_key, 'default__' ) === 0 ) {
                $field_name = substr( $clean_key, 9 );
                $subscriber_data[ $field_name ] = $value;
            } elseif ( strpos( $clean_key, 'custom__' ) === 0 ) {
                $field_name = substr( $clean_key, 8 );
                $custom_fields[ $field_name ] = $value;
            }
        }

        if ( $group_id ) {
            $subscriber_data['groups'] = [ $group_id ];
        }

        if ( !empty( $custom_fields ) ) {
            $subscriber_data['fields'] = $custom_fields;
        }

        $response = adfoin_sender_request(
            'subscribers',
            'POST',
            $subscriber_data,
            $record,
            $cred_id
        );
    }
}
