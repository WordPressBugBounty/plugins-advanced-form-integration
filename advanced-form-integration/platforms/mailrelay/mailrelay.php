<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailrelay_actions', 10, 1 );

function adfoin_mailrelay_actions( $actions ) {

    $actions['mailrelay'] = array(
        'title' => __( 'MailRelay', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to Group', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailrelay_settings_tab', 10, 1 );

function adfoin_mailrelay_settings_tab( $providers ) {
    $providers['mailrelay'] = __( 'MailRelay', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailrelay_settings_view', 10, 1 );

function adfoin_mailrelay_settings_view( $current_tab ) {
    if( $current_tab != 'mailrelay' ) {
        return;
    }

    $title = __( 'MailRelay', 'advanced-form-integration' );
    $key = 'mailrelay';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'domain',
                'label' => __( 'Subdomain', 'advanced-form-integration' ),
                'hiden' => false
            ],
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
                    <li>Enter the subdomain of your Mailrelay account. For example: if app url is https://afi.ipzmarketing.com/, copy \'afi\'.</li>
                    <li>Go to Setting > API keys, click Add button and copy the API key.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        ),
        'https://mailrelay.com/help/api',
        __('Click here to get your API Key', 'advanced-form-integration')
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailrelay_credentials', 'adfoin_get_mailrelay_credentials', 10, 0 );

function adfoin_get_mailrelay_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'mailrelay' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_mailrelay_credentials', 'adfoin_save_mailrelay_credentials', 10, 0 );

function adfoin_save_mailrelay_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'mailrelay' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_mailrelay_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'mailrelay' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_mailrelay_action_fields' );

function adfoin_mailrelay_action_fields() {
    ?>
    <script type="text/template" id="mailrelay-action-template">
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
                    <label for="tablecell">
                        <?php esc_attr_e( 'Mailrelay Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getGroups">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_mailrelay_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Select Group', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId">
                        <option value=""> <?php _e( 'Select Group...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(group, index) in fielddata.groups" :value="index"> {{ group }} </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_mailrelay_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mailrelay', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $domain = isset( $credentials['domain'] ) ? $credentials['domain'] : '';

    $base_url = "https://{$domain}.ipzmarketing.com/api/v1/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'X-AUTH-TOKEN' => $api_key
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

add_action( 'wp_ajax_adfoin_get_mailrelay_groups', 'adfoin_get_mailrelay_groups', 10, 0 );

function adfoin_get_mailrelay_groups() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );

    $response = adfoin_mailrelay_request( 'groups?per_page=1000', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) ) {
        $groups = array();

        foreach ( $body as $group ) {
            $groups[ $group['id'] ] = $group['name'];
        }

        wp_send_json_success( $groups );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_mailrelay_job_queue', 'adfoin_mailrelay_job_queue', 10, 1 );

function adfoin_mailrelay_job_queue( $data ) {
    adfoin_mailrelay_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailrelay_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $group_id = isset( $data['groupId'] ) ? $data['groupId'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    if ( $task == 'subscribe' ) {
        $subscriber_data = array_filter(array(
            'email' => empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) ),
            'name' => isset( $data['name'] ) ? adfoin_get_parsed_values( $data['name'], $posted_data ) : '',
            'sms_phone' => isset( $data['sms_phone'] ) ? adfoin_get_parsed_values( $data['sms_phone'], $posted_data ) : '',
            'address' => isset( $data['address'] ) ? adfoin_get_parsed_values( $data['address'], $posted_data ) : '',
            'city' => isset( $data['city'] ) ? adfoin_get_parsed_values( $data['city'], $posted_data ) : '',
            'state' => isset( $data['state'] ) ? adfoin_get_parsed_values( $data['state'], $posted_data ) : '',
            'country' => isset( $data['country'] ) ? adfoin_get_parsed_values( $data['country'], $posted_data ) : '',
            'birthday' => isset( $data['birthday'] ) ? adfoin_get_parsed_values( $data['birthday'], $posted_data ) : '',
            'website' => isset( $data['website'] ) ? adfoin_get_parsed_values( $data['website'], $posted_data ) : '',
            'locale' => isset( $data['locale'] ) ? adfoin_get_parsed_values( $data['locale'], $posted_data ) : '',
            'time_zone' => isset( $data['time_zone'] ) ? adfoin_get_parsed_values( $data['time_zone'], $posted_data ) : '',
            'status' => isset( $data['status'] ) ? adfoin_get_parsed_values( $data['status'], $posted_data ) : 'active',
        ));

        if ( ! empty( $group_id ) ) {
            $subscriber_data['group_ids'] = array( $group_id );
        }

        $result = adfoin_mailrelay_request( 'subscribers/sync', 'POST', $subscriber_data, $record, $cred_id );
    }
}