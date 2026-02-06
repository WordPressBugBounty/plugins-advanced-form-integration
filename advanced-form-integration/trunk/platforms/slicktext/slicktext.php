<?php

add_filter( 'adfoin_action_providers', 'adfoin_slicktext_actions', 10, 1 );

function adfoin_slicktext_actions( $actions ) {
    $actions['slicktext'] = array(
        'title' => __( 'SlickText', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create / Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_slicktext_settings_tab', 10, 1 );

function adfoin_slicktext_settings_tab( $providers ) {
    $providers['slicktext'] = __( 'SlickText', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_slicktext_settings_view', 10, 1 );

function adfoin_slicktext_settings_view( $current_tab ) {
    if ( 'slicktext' !== $current_tab ) {
        return;
    }

    $title = __( 'SlickText', 'advanced-form-integration' );
    $key   = 'slicktext';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'apiKey',
                'label'  => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'apiSecret',
                'label'  => __( 'API Secret', 'advanced-form-integration' ),
                'hidden' => true,
            ),
            array(
                'key'    => 'baseUrl',
                'label'  => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
                'placeholder' => 'https://api.slicktext.com',
            ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
            <li>%4$s</li>
        </ol>
        <p>%5$s</p>',
        esc_html__( 'Log in to SlickText and open the Account → API Access page.', 'advanced-form-integration' ),
        esc_html__( 'Generate an API key and secret with permission to manage contacts.', 'advanced-form-integration' ),
        esc_html__( 'Copy the credentials into the fields below and click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential when creating an AFI action to add contacts to your SlickText lists.', 'advanced-form-integration' ),
        esc_html__( 'Requests are sent to the SlickText REST API v1 using HTTP Basic authentication.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_slicktext_action_fields' );

function adfoin_slicktext_action_fields() {
    ?>
    <script type="text/template" id="slicktext-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SlickText Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_slicktext_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need more options?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Use the JSON fields to pass custom merge tags or attributes exactly as SlickText expects them.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_slicktext_credentials', 'adfoin_get_slicktext_credentials' );

function adfoin_get_slicktext_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'slicktext' ) );
}

add_action( 'wp_ajax_adfoin_save_slicktext_credentials', 'adfoin_save_slicktext_credentials' );

function adfoin_save_slicktext_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'slicktext' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'slicktext', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_slicktext_fields', 'adfoin_get_slicktext_fields' );

function adfoin_get_slicktext_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'list_id', 'value' => __( 'List ID', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone_number', 'value' => __( 'Phone Number (E.164 / digits)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'birthday', 'value' => __( 'Birthday (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'address', 'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'notes', 'value' => __( 'Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'tags', 'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: {"favoriteStore":"Downtown"}', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_slicktext_job_queue', 'adfoin_slicktext_job_queue', 10, 1 );

function adfoin_slicktext_job_queue( $data ) {
    adfoin_slicktext_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_slicktext_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_slicktext_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_slicktext_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_slicktext_request( 'contacts', 'POST', $payload, $record, $credentials );
}

function adfoin_slicktext_collect_payload( $field_data, $posted_data ) {
    $list_id = adfoin_slicktext_parse_value( $field_data, 'list_id', $posted_data );
    $phone   = adfoin_slicktext_parse_value( $field_data, 'phone_number', $posted_data );

    if ( '' === $list_id ) {
        return new WP_Error( 'slicktext_missing_list', __( 'SlickText requires a list ID.', 'advanced-form-integration' ) );
    }

    if ( '' === $phone ) {
        return new WP_Error( 'slicktext_missing_phone', __( 'SlickText requires a phone number.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'list_id'      => $list_id,
        'phone_number' => $phone,
    );

    $map = array(
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'email'      => 'email_address',
        'birthday'   => 'birthdate',
        'address'    => 'address',
        'city'       => 'city',
        'state'      => 'state',
        'postal_code'=> 'postal_code',
        'country'    => 'country',
        'notes'      => 'notes',
    );

    foreach ( $map as $key => $api_field ) {
        $value = adfoin_slicktext_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_field ] = $value;
    }

    $tags = adfoin_slicktext_parse_value( $field_data, 'tags', $posted_data );

    if ( '' !== $tags ) {
        $payload['tags'] = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
    }

    $custom_fields = adfoin_slicktext_parse_value( $field_data, 'custom_fields_json', $posted_data );

    if ( '' !== $custom_fields ) {
        $decoded = json_decode( $custom_fields, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'slicktext_invalid_custom_fields', __( 'Custom fields JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['custom_fields'] = $decoded;
    }

    return $payload;
}

function adfoin_slicktext_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_slicktext_credentials_list() {
    $credentials = adfoin_read_credentials( 'slicktext' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_slicktext_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'slicktext', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'slicktext_missing_credentials', __( 'SlickText credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_slicktext_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $api_key    = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';
    $api_secret = isset( $credentials['apiSecret'] ) ? trim( $credentials['apiSecret'] ) : '';
    $base       = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.slicktext.com';

    if ( '' === $api_key || '' === $api_secret ) {
        return new WP_Error( 'slicktext_missing_auth', __( 'SlickText API key or secret is missing.', 'advanced-form-integration' ) );
    }

    $auth = base64_encode( $api_key . ':' . $api_secret );
    $url  = $base . '/v1/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status >= 400 ) {
        $body    = wp_remote_retrieve_body( $response );
        $message = $body ? $body : __( 'SlickText request failed.', 'advanced-form-integration' );

        return new WP_Error( 'slicktext_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
