<?php
add_filter( 'adfoin_action_providers', 'adfoin_systemeio_actions', 10, 1 );

function adfoin_systemeio_actions( $actions ) {
    $actions['systemeio'] = array(
        'title' => __( 'Systeme.io', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe to List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_systemeio_settings_tab', 10, 1 );

function adfoin_systemeio_settings_tab( $providers ) {
    $providers['systemeio'] = __( 'Systeme.io', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_systemeio_settings_view', 10, 1 );

function adfoin_systemeio_settings_view( $current_tab ) {
    if( $current_tab != 'systemeio' ) {
        return;
    }

    $title = __( 'Systeme.io', 'advanced-form-integration' );
    $key = 'systemeio';
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
                    <li>Go to Profile> Settings > Public API keys.</li>
                    <li>Create and copy the token</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_systemeio_credentials', 'adfoin_get_systemeio_credentials', 10, 0 );

function adfoin_get_systemeio_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'systemeio' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_systemeio_credentials', 'adfoin_save_systemeio_credentials', 10, 0 );

function adfoin_save_systemeio_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'systemeio' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_systemeio_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'systemeio' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_systemeio_action_fields' );

function adfoin_systemeio_action_fields() {
    ?>
    <script type="text/template" id="systemeio-action-template">
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
                        <?php esc_attr_e( 'Systeme.io Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_systemeio_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_systemeio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'systemeio', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    $base_url = "https://api.systeme.io/api/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'X-API-Key'     => $api_key
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

add_action('wp_ajax_adfoin_get_systemeio_fields', 'adfoin_systemeio_get_fields', 10, 0);

function adfoin_systemeio_get_fields() {

    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field( $_POST['credId'] );
    $fields = array();
    $tags = adfoin_systemeio_get_tags($cred_id);

    if($tags) {
        $fields[] = ['key' => 'tag', 'value' => 'Tag ID', 'description' => $tags ];
    }

    $response = adfoin_systemeio_request( 'contact_fields', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if (isset($body['items']) && is_array( $body['items'])) {
        foreach ($body['items'] as $field) {
            $fields[] = ['key' => $field['slug'], 'value' => $field['fieldName']];
        }

        wp_send_json_success( $fields );
    } else {
        wp_send_json_error();
    }
}

function adfoin_systemeio_get_tags( $cred_id) {
    $tags = array();
    $response = adfoin_systemeio_request( 'tags', 'GET', '', '', $cred_id );

    if ( is_wp_error( $response ) ) {
        return $tags;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if (isset($body['items']) && is_array($body['items'])) {
        foreach ($body['items'] as $tag) {
            $tags[] = $tag['name'] . ': ' . $tag['id'];
        }
    }

    $tags = implode(', ', $tags);

    return $tags;
}

add_action( 'adfoin_systemeio_job_queue', 'adfoin_systemeio_job_queue', 10, 1 );

function adfoin_systemeio_job_queue( $data ) {
    adfoin_systemeio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_systemeio_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];
    $tag_id = isset( $data['tag'] ) ? adfoin_get_parsed_values($data['tag'], $posted_data) : '';

    unset( $data['groupId'], $data['credId'], $data['tag'] );

    if ( $task == 'subscribe' ) {
        $subscriber_data = array();

        foreach ( $data as $key => $value ) {
            $value = adfoin_get_parsed_values( $value, $posted_data );

            if( $value ) {
                $subscriber_data[ $key ] = $value;
            }
        }

        $email = isset($subscriber_data['email']) ? $subscriber_data['email'] : '';

        if ($email) {
            $contact_id = adfoin_systemeio_find_contact($email, $cred_id);

            $subscriber_data_formatted = array(
                'fields' => array()
            );

            foreach ($subscriber_data as $key => $value) {
                if ($key !== 'email') {
                    $subscriber_data_formatted['fields'][] = array(
                    'slug' => $key,
                    'value' => $value
                    );
                }
            }

            if ($contact_id) {
                $response = adfoin_systemeio_request('contacts/' . $contact_id, 'PATCH', $subscriber_data_formatted, $record, $cred_id);
            } else {
                $subscriber_data_formatted['email'] = $email;
                $response = adfoin_systemeio_request('contacts', 'POST', $subscriber_data_formatted, $record, $cred_id);
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
                $contact_id = isset($response_body['id']) ? $response_body['id'] : '';
            }

            // Add tag
            if ($tag_id && $contact_id) {
                $response = adfoin_systemeio_request('contacts/' . $contact_id . '/tags', 'POST', array('tagId' => intval($tag_id)), $record, $cred_id);
            }
        }

    }
}

function adfoin_systemeio_find_contact($email, $cred_id) {
    $endpoint = 'contacts?email=' . urlencode($email);
    $response = adfoin_systemeio_request($endpoint, 'GET', array(), array(), $cred_id);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_array($body) && isset($body['items'][0]['id'])) {
        return $body['items'][0]['id'];
    }

    return false;
}