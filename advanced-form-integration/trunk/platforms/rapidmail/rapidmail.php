<?php
add_filter( 'adfoin_action_providers', 'adfoin_rapidmail_actions', 10, 1 );

function adfoin_rapidmail_actions( $actions ) {
    $actions['rapidmail'] = array(
        'title' => __( 'Rapidmail', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_rapidmail_settings_tab', 10, 1 );

function adfoin_rapidmail_settings_tab( $providers ) {
    $providers['rapidmail'] = __( 'Rapidmail', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_rapidmail_settings_view', 10, 1 );

function adfoin_rapidmail_settings_view( $current_tab ) {
    if( $current_tab != 'rapidmail' ) {
        return;
    }

    $title = __( 'Rapidmail', 'advanced-form-integration' );
    $key = 'rapidmail';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'username',
                'label' => __( 'Username', 'advanced-form-integration' ),
                'hidden' => true
            ],
            [
                'key' => 'password',
                'label' => __( 'Password', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = sprintf(
        __(
            '<p>
                <ol>
                    <li>Go to your Rapidmail account > Profile > API.</li>
                    <li>Crete new API user and copy Username/Password.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_rapidmail_credentials', 'adfoin_get_rapidmail_credentials', 10, 0 );

function adfoin_get_rapidmail_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'rapidmail' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_rapidmail_credentials', 'adfoin_save_rapidmail_credentials', 10, 0 );

function adfoin_save_rapidmail_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'rapidmail' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_rapidmail_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'rapidmail' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_rapidmail_action_fields' );

function adfoin_rapidmail_action_fields() {
    ?>
    <script type="text/template" id="rapidmail-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Rapidmail Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_rapidmail_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td scope="row">
                    <?php esc_attr_e( 'Select List', 'advanced-form-integration' ); ?>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(list, index) in fielddata.lists" :value="index"> {{ list }} </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_rapidmail_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'rapidmail', $cred_id );
    $username = isset( $credentials['username'] ) ? $credentials['username'] : '';
    $password = isset( $credentials['password'] ) ? $credentials['password'] : '';

    $base_url = "https://apiv3.emailsys.net/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password )
        ),
    );

    if ( 'POST' == $method || 'PATCH' == $method ) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_rapidmail_lists', 'adfoin_get_rapidmail_lists', 10, 0 );

function adfoin_get_rapidmail_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $response = adfoin_rapidmail_request( 'recipientlists', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) && isset( $body['_embedded']['recipientlists'] ) ) {
        $lists = array();

        foreach ( $body['_embedded']['recipientlists'] as $list ) {
            $lists[ $list['id'] ] = $list['name'];
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_rapidmail_job_queue', 'adfoin_rapidmail_job_queue', 10, 1 );

function adfoin_rapidmail_job_queue( $data ) {
    adfoin_rapidmail_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_rapidmail_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    unset( $data['listId'], $data['credId'] );

    if ( $task == 'subscribe' ) {
        $subscriber_data = array();

        foreach ($data as $key => $value) {
            $value = adfoin_get_parsed_values($value, $posted_data);

            if ($value) {
                $subscriber_data[$key] = $value;
            }
        }

        if ( ! empty( $list_id ) ) {
            $subscriber_data['recipientlist_id'] = $list_id;
        }

        if (!empty($subscriber_data['email'])) {
            $email = $subscriber_data['email'];
            $recipient_id = adfoin_rapidmail_find_contact($email, $list_id, $cred_id);

            if ($recipient_id) {
                $endpoint = 'recipients/' . $recipient_id;
                unset($subscriber_data['recipientlist_id']);
                $result = adfoin_rapidmail_request($endpoint, 'PATCH', $subscriber_data, $record, $cred_id);
            } else {
                $result = adfoin_rapidmail_request('recipients', 'POST', $subscriber_data, $record, $cred_id);
            }
        }
    }
}

function adfoin_rapidmail_find_contact($email, $list_id, $cred_id) {
    $endpoint = 'recipients?email=' . urlencode($email) . '&recipientlist_id=' . urlencode($list_id);
    $response = adfoin_rapidmail_request($endpoint, 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_array($body) && isset($body['_embedded']['recipients'][0]['id'])) {
        return $body['_embedded']['recipients'][0]['id'];
    }

    return false;
}