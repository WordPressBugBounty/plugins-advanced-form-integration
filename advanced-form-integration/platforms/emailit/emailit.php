<?php

add_filter( 'adfoin_action_providers', 'adfoin_emailit_actions', 10, 1 );
function adfoin_emailit_actions( $actions ) {
    $actions['emailit'] = array(
        'title' => __( 'Emailit', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to Audience', 'advanced-form-integration' ),
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_emailit_settings_tab', 10, 1 );
function adfoin_emailit_settings_tab( $providers ) {
    $providers['emailit'] = __( 'Emailit', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_emailit_settings_view', 10, 1 );
function adfoin_emailit_settings_view( $current_tab ) {
    if( $current_tab != 'emailit' ) return;
    $title = __( 'Emailit', 'advanced-form-integration' );
    $key = 'emailit';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'api_key',
                'label' => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => false
            ]
        ]
    ]);
    $instructions = __(
        '<p>
            <ol>
                <li>Log in to your Emailit account.</li>
                <li>Go to Credentials and create an API Key.</li>
            </ol>
        </p>',
        'advanced-form-integration'
    );
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_emailit_credentials', 'adfoin_get_emailit_credentials', 10, 0 );
function adfoin_get_emailit_credentials() {
    if (!adfoin_verify_nonce()) return;
    $all_credentials = adfoin_read_credentials( 'emailit' );
    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_emailit_credentials', 'adfoin_save_emailit_credentials', 10, 0 );
function adfoin_save_emailit_credentials() {
    if (!adfoin_verify_nonce()) return;
    $platform = sanitize_text_field( $_POST['platform'] );
    if( 'emailit' == $platform ) {
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

function adfoin_emailit_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'emailit' );
    if (is_array($credentials)) {
        foreach( $credentials as $option ) {
            if (isset($option['id']) && isset($option['title'])) {
                 $html .= '<option value="'. esc_attr($option['id']) .'">' . esc_html($option['title']) . '</option>';
            }
        }
    }
    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_emailit_action_fields' );
function adfoin_emailit_action_fields() {
    ?>
    <script type="text/template" id="emailit-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label>
                        <?php esc_attr_e( 'Emailit Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getAudiences">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_emailit_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label>
                        <?php esc_attr_e( 'Audiences', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[audienceId]" v-model="fielddata.audienceId">
                        <option value=""> <?php _e( 'Select Audience...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="audience in fielddata.audiences" v-bind:key="audience.id" v-bind:value="audience.id">
                            {{ audience.name }}
                        </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_emailit_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'emailit', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';
    if ( empty( $api_key ) ) {
        if ($record) {
            adfoin_add_to_log(new WP_Error('missing_credentials', 'Emailit API Key not set.'), $endpoint, array(), $record);
        }
        return new WP_Error('missing_credentials', 'Emailit API Key not set.');
    }
    $api_base_url = 'https://api.emailit.com/v1/';
    $url = $api_base_url . $endpoint;
    $args = array(
        'timeout'     => 30,
        'method'      => strtoupper( $method ),
        'user-agent'  => 'Advanced Form Integration (WordPress Plugin)',
        'headers'     => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'   => 'application/json',
        ),
    );
    if ( in_array($args['method'], ['POST', 'PUT', 'PATCH']) ) {
        $args['body'] = !empty($data) ? json_encode($data) : json_encode((object)array());
    } elseif ('GET' == $args['method'] && !empty($data)) {
        $url = add_query_arg( $data, $url );
    }
    $response = wp_remote_request( $url, $args );
    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }
    return $response;
}

add_action( 'adfoin_emailit_job_queue', 'adfoin_emailit_job_queue', 10, 1 );
function adfoin_emailit_job_queue( $data ) {
    adfoin_emailit_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_emailit_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( isset($record_data['action_data']['cl']) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_map_data = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $cred_id = isset($field_map_data['credId']) ? $field_map_data['credId'] : '';
    $task = isset($record['task']) ? $record['task'] : '';
    if ($task == 'subscribe') {
        $audience_id = isset($field_map_data['audienceId']) ? $field_map_data['audienceId'] : '';
        $email = isset($field_map_data['email']) ? adfoin_get_parsed_values($field_map_data['email'], $posted_data) : '';

        $payload = array(
            'email' => $email,
        );

        // Optional fields
        $first_name = isset($field_map_data['first_name']) ? adfoin_get_parsed_values($field_map_data['first_name'], $posted_data) : '';
        $last_name = isset($field_map_data['last_name']) ? adfoin_get_parsed_values($field_map_data['last_name'], $posted_data) : '';

        if ($first_name !== '') {
            $payload['first_name'] = $first_name;
        }
        if ($last_name !== '') {
            $payload['last_name'] = $last_name;
        }

        // Custom fields (should be an associative array)
        // $custom_fields = isset($field_map_data['custom_fields']) ? adfoin_get_parsed_values($field_map_data['custom_fields'], $posted_data) : '';
        // if (!empty($custom_fields)) {
        //     $decoded = is_array($custom_fields) ? $custom_fields : json_decode($custom_fields, true);
        //     if (is_array($decoded)) {
        //         $payload['custom_fields'] = $decoded;
        //     }
        // }

        $endpoint = 'audiences/subscribe/' . urlencode($audience_id);
        $result = adfoin_emailit_request($endpoint, 'POST', $payload, $record, $cred_id);
    }
}

/**
 * Fetches Emailit audiences (lists) using the API and returns them for the UI.
 *
 * @since 1.0.0
 */
add_action( 'wp_ajax_adfoin_get_emailit_audiences', 'adfoin_get_emailit_audiences', 10, 0 );

function adfoin_get_emailit_audiences() {
    if ( ! adfoin_verify_nonce() ) return;

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

    $response = adfoin_emailit_request( 'audiences', 'GET', array(), array(), $cred_id );

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $response_code = wp_remote_retrieve_response_code( $response );

    if ( $response_code == 200 && isset( $body['data'] ) && is_array( $body['data'] ) ) {
        $audiences = array();
        foreach ( $body['data'] as $aud ) {
            if ( isset( $aud['token'] ) && isset( $aud['name'] ) ) {
                $audiences[] = array( 'id' => $aud['token'], 'name' => $aud['name'] );
            }
        }
        wp_send_json_success( $audiences );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to fetch audiences.' ) );
    }
}