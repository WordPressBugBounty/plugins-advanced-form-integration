<?php

add_filter( 'adfoin_action_providers', 'adfoin_gist_actions', 10, 1 );

function adfoin_gist_actions( $actions ) {

    $actions['gist'] = array(
        'title' => __( 'GIST', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_gist_settings_tab', 10, 1 );

function adfoin_gist_settings_tab( $providers ) {
    $providers['gist'] = __( 'GIST', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_gist_settings_view', 10, 1 );

function adfoin_gist_settings_view( $current_tab ) {
    if( $current_tab != 'gist' ) {
        return;
    }

    $title = __( 'GIST', 'advanced-form-integration' );
    $key = 'gist';
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
                <ol>
                    <li>Log in to your GIST account.</li>
                    <li>Navigate to Settings > API Keys and generate an API key.</li>
                    <li>Copy the API key and paste it into the field below.</li>
                </ol>
            </p>',
            'advanced-form-integration'
        )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_gist_credentials', 'adfoin_get_gist_credentials', 10, 0 );

function adfoin_get_gist_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'gist' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_gist_credentials', 'adfoin_save_gist_credentials', 10, 0 );

function adfoin_save_gist_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'gist' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_gist_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'gist' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_gist_action_fields' );

function adfoin_gist_action_fields() {
    ?>
    <script type="text/template" id="gist-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td>

                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'GIST Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_gist_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

function adfoin_gist_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'gist', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';

    $base_url = "https://api.getgist.com/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => "Bearer $api_key"
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

add_action( 'adfoin_gist_job_queue', 'adfoin_gist_job_queue', 10, 1 );

function adfoin_gist_job_queue( $data ) {
    adfoin_gist_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_gist_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    if ( $task == 'create_contact' ) {
        $contact_data = array_filter(array(
            'type' => isset($data['type']) ? adfoin_get_parsed_values($data['type'], $posted_data) : '',
            'full_name' => isset($data['full_name']) ? adfoin_get_parsed_values($data['full_name'], $posted_data) : '',
            'name' => isset($data['name']) ? adfoin_get_parsed_values($data['name'], $posted_data) : '',
            'email' => isset($data['email']) ? adfoin_get_parsed_values($data['email'], $posted_data) : '',
            'user_id' => isset($data['user_id']) ? adfoin_get_parsed_values($data['user_id'], $posted_data) : '',
            'phone_number' => isset($data['phone_number']) ? adfoin_get_parsed_values($data['phone_number'], $posted_data) : '',
            'phone' => isset($data['phone']) ? adfoin_get_parsed_values($data['phone'], $posted_data) : '',
            'first_name' => isset($data['first_name']) ? adfoin_get_parsed_values($data['first_name'], $posted_data) : '',
            'last_name' => isset($data['last_name']) ? adfoin_get_parsed_values($data['last_name'], $posted_data) : '',
            'salutation' => isset($data['salutation']) ? adfoin_get_parsed_values($data['salutation'], $posted_data) : '',
            'job_title' => isset($data['job_title']) ? adfoin_get_parsed_values($data['job_title'], $posted_data) : '',
            'company_name' => isset($data['company_name']) ? adfoin_get_parsed_values($data['company_name'], $posted_data) : '',
            'website_url' => isset($data['website_url']) ? adfoin_get_parsed_values($data['website_url'], $posted_data) : '',
            'mobile_phone_number' => isset($data['mobile_phone_number']) ? adfoin_get_parsed_values($data['mobile_phone_number'], $posted_data) : '',
            'fax_number' => isset($data['fax_number']) ? adfoin_get_parsed_values($data['fax_number'], $posted_data) : '',
            'preferred_language' => isset($data['preferred_language']) ? adfoin_get_parsed_values($data['preferred_language'], $posted_data) : '',
            'industry' => isset($data['industry']) ? adfoin_get_parsed_values($data['industry'], $posted_data) : '',
            'date_of_birth' => isset($data['date_of_birth']) ? adfoin_get_parsed_values($data['date_of_birth'], $posted_data) : '',
            'gender' => isset($data['gender']) ? adfoin_get_parsed_values($data['gender'], $posted_data) : '',
            'company_size' => isset($data['company_size']) ? adfoin_get_parsed_values($data['company_size'], $posted_data) : '',
            'landing_url' => isset($data['landing_url']) ? adfoin_get_parsed_values($data['landing_url'], $posted_data) : '',
            'location_data' => array_filter(array(
                'city_name' => isset($data['city_name']) ? adfoin_get_parsed_values($data['city_name'], $posted_data) : '',
                'region_name' => isset($data['region_name']) ? adfoin_get_parsed_values($data['region_name'], $posted_data) : '',
                'country_name' => isset($data['country_name']) ? adfoin_get_parsed_values($data['country_name'], $posted_data) : '',
                'country_code' => isset($data['country_code']) ? adfoin_get_parsed_values($data['country_code'], $posted_data) : '',
                'continent_name' => isset($data['continent_name']) ? adfoin_get_parsed_values($data['continent_name'], $posted_data) : '',
                'continent_code' => isset($data['continent_code']) ? adfoin_get_parsed_values($data['continent_code'], $posted_data) : '',
                'latitude' => isset($data['latitude']) ? adfoin_get_parsed_values($data['latitude'], $posted_data) : '',
                'longitude' => isset($data['longitude']) ? adfoin_get_parsed_values($data['longitude'], $posted_data) : '',
                'postal_code' => isset($data['postal_code']) ? adfoin_get_parsed_values($data['postal_code'], $posted_data) : '',
                'time_zone' => isset($data['time_zone']) ? adfoin_get_parsed_values($data['time_zone'], $posted_data) : '',
            )),
        ));

        adfoin_gist_request( 'contacts', 'POST', $contact_data, $record, $cred_id );
    }
}