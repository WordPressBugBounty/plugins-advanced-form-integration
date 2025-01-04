<?php
add_filter( 'adfoin_action_providers', 'adfoin_loops_actions', 10, 1 );

function adfoin_loops_actions( $actions ) {
    $actions['loops'] = array(
        'title' => __( 'Loops', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_loops_settings_tab', 10, 1 );

function adfoin_loops_settings_tab( $providers ) {
    $providers['loops'] = __( 'Loops', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_loops_settings_view', 10, 1 );

function adfoin_loops_settings_view( $current_tab ) {
    if( $current_tab != 'loops' ) {
        return;
    }

    $title = __( 'Loops', 'advanced-form-integration' );
    $key = 'loops';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'apiKey',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Go to Settings > API.</li>
                    <li>Generate and copy the key</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_loops_credentials', 'adfoin_get_loops_credentials', 10, 0 );

function adfoin_get_loops_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'loops' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_loops_credentials', 'adfoin_save_loops_credentials', 10, 0 );

function adfoin_save_loops_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'loops' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_loops_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'loops' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_loops_action_fields' );

function adfoin_loops_action_fields() {
    ?>
    <script type="text/template" id="loops-action-template">
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
                        <?php esc_attr_e( 'Loops Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_loops_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'List', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
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

function adfoin_loops_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'loops', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    $base_url = "https://app.loops.so/api/v1/";
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

add_action( 'wp_ajax_adfoin_get_loops_groups', 'adfoin_get_loops_groups', 10, 0 );

function adfoin_get_loops_groups() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $response = adfoin_loops_request( 'lists', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) ) {
        $lists = array();

        foreach ( $body as $list ) {
            $lists[ $list['id'] ] = $list['name'];
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action('wp_ajax_adfoin_get_loops_fields', 'adfoin_loops_get_fields', 10, 0);

function adfoin_loops_get_fields() {

    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $fields = array(
        array( 'key' => 'email', 'value' => 'Email' ),
        array( 'key' => 'firstName', 'value' => 'First Name' ),
        array( 'key' => 'lastName', 'value' => 'Last Name' ),
        array( 'key' => 'source', 'value' => 'Source' ),
        array( 'key' => 'userGroup', 'value' => 'User Group' ),
        array( 'key' => 'userId', 'value' => 'User ID' ),
    );

    $response = adfoin_loops_request( 'contacts/customFields', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if (is_array( $body)) {
        

        foreach ($body as $field) {
            $fields[] = ['key' => $field['key'], 'value' => $field['label']];
        }

        wp_send_json_success( $fields );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_loops_job_queue', 'adfoin_loops_job_queue', 10, 1 );

function adfoin_loops_job_queue( $data ) {
    adfoin_loops_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_loops_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $group_id = isset( $data['groupId'] ) ? $data['groupId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    unset( $data['groupId'], $data['credId'] );

    if ( $task == 'subscribe' ) {
        $subscriber_data = array();

        foreach ( $data as $key => $value ) {
            $value = adfoin_get_parsed_values( $value, $posted_data );

            if( $value ) {
                $subscriber_data[ $key ] = $value;
            }
        }

        if ( $group_id ) {
            $subscriber_data['mailingLists'] = array( $group_id => true );
        }

        $email = isset($subscriber_data['email']) ? $subscriber_data['email'] : '';

        if ($email) {
            $contact_id = adfoin_loops_find_contact($email, $cred_id);

            if ($contact_id) {
                $response = adfoin_loops_request('contacts/update', 'PUT', $subscriber_data, $record, $cred_id);
            } else {
                $response = adfoin_loops_request('contacts/create', 'POST', $subscriber_data, $record, $cred_id);
            }
        }

    }
}

function adfoin_loops_find_contact($email, $cred_id) {
    $endpoint = 'contacts/find?email=' . urlencode($email);
    $response = adfoin_loops_request($endpoint, 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_array($body) && isset($body['0']['id'])) {
        return $body['0']['id'];
    }

    return false;
}