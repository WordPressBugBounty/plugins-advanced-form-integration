<?php
add_filter( 'adfoin_action_providers', 'adfoin_resend_actions', 10, 1 );

function adfoin_resend_actions( $actions ) {
    $actions['resend'] = array(
        'title' => __( 'Resend', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add contact', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_resend_settings_tab', 10, 1 );

function adfoin_resend_settings_tab( $providers ) {
    $providers['resend'] = __( 'Resend', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_resend_settings_view', 10, 1 );

function adfoin_resend_settings_view( $current_tab ) {
    if( $current_tab != 'resend' ) {
        return;
    }

    $title = __( 'Resend', 'advanced-form-integration' );
    $key = 'resend';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
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
                <ul>
                    <li>Go to API Keys and create a new key with full access</li>
                </ul>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_resend_credentials', 'adfoin_get_resend_credentials', 10, 0 );

function adfoin_get_resend_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'resend' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_resend_credentials', 'adfoin_save_resend_credentials', 10, 0 );

function adfoin_save_resend_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'resend' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_resend_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'resend' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_resend_action_fields' );

function adfoin_resend_action_fields() {
    ?>
    <script type="text/template" id="resend-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'>
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Resend Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_resend_credentials_list();
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

function adfoin_resend_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'resend', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';

    $base_url = "https://api.resend.com/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
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

add_action( 'wp_ajax_adfoin_get_resend_lists', 'adfoin_get_resend_lists', 10, 0 );

function adfoin_get_resend_lists() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $response = adfoin_resend_request( 'audiences', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) && isset( $body['data'] ) ) {
        $lists = array();

        foreach ( $body['data'] as $list ) {
            $lists[ $list['id'] ] = $list['name'];
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_resend_job_queue', 'adfoin_resend_job_queue', 10, 1 );

function adfoin_resend_job_queue( $data ) {
    adfoin_resend_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_resend_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    unset( $data['listId'], $data['credId'] );

    if ( $task == 'subscribe' ) {
        $email_data = array();

        foreach ($data as $key => $value) {
            $value = adfoin_get_parsed_values($value, $posted_data);

            if ($value) {
                $email_data[$key] = $value;
            }
        }

        if ($list_id) {
            $result = adfoin_resend_request('audiences/' . $list_id . '/contacts', 'POST', $email_data, $record, $cred_id);
            if (is_wp_error($result)) {
                error_log('Error sending data to Resend: ' . $result->get_error_message());
            }
        }
    }
}