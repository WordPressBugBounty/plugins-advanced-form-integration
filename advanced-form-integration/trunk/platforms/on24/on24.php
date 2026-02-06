<?php

add_filter( 'adfoin_action_providers', 'adfoin_on24_actions', 10, 1 );

function adfoin_on24_actions( $actions ) {
    $actions['on24'] = array(
        'title' => __( 'ON24', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_on24_settings_tab', 10, 1 );

function adfoin_on24_settings_tab( $providers ) {
    $providers['on24'] = __( 'ON24', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_on24_settings_view', 10, 1 );

function adfoin_on24_settings_view( $current_tab ) {
    if ( 'on24' !== $current_tab ) {
        return;
    }

    $title = __( 'ON24', 'advanced-form-integration' );
    $key   = 'on24';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'         => 'title',
                    'label'       => __( 'Credential Label', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => __( 'Primary ON24 Account', 'advanced-form-integration' ),
                ),
                array(
                    'key'    => 'clientId',
                    'label'  => __( 'Client ID', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'clientSecret',
                    'label'  => __( 'Client Secret', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'         => 'baseUrl',
                    'label'       => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => 'https://api.on24.com',
                ),
            ),
        )
    );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
            <li>%4$s</li>
        </ol>
        <p>%5$s</p>',
        esc_html__( 'Request API access for your ON24 account and create an OAuth client with the REST Registration scopes.', 'advanced-form-integration' ),
        esc_html__( 'Copy the Client ID and Client Secret from the ON24 developer portal.', 'advanced-form-integration' ),
        esc_html__( 'Enter the credentials here, optionally override the API base URL if ON24 assigns a regional endpoint, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential when configuring an ON24 action. Provide the Event ID (webcast ID) from ON24 when mapping a form.', 'advanced-form-integration' ),
        esc_html__( 'See the ON24 REST Registration guide for required attendee fields and custom question mappings.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_on24_action_fields' );

function adfoin_on24_action_fields() {
    ?>
    <script type="text/template" id="on24-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'ON24 Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_on24_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Event ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[eventId]" v-model="fielddata.eventId" placeholder="1234567" />
                    <p class="description"><?php esc_html_e( 'Enter the ON24 event ID (webcast ID) to register attendees.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr>
                <td scope="row-title">
                    <label><?php esc_html_e( 'Source Code (optional)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[sourceCode]" v-model="fielddata.sourceCode" placeholder="landing_page" />
                    <p class="description"><?php esc_html_e( 'Set a source code to track registrations (for example, form or campaign identifier).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Tips', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Map email, first name, and last name at minimum. Use JSON fields for answers to custom questions or demographics.', 'advanced-form-integration' ); ?></p>
                    <p><?php esc_html_e( 'Consult ON24 documentation for acceptable values (country codes, states, etc.).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_on24_credentials', 'adfoin_get_on24_credentials' );

function adfoin_get_on24_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'on24' ) );
}

add_action( 'wp_ajax_adfoin_save_on24_credentials', 'adfoin_save_on24_credentials' );

function adfoin_save_on24_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'on24' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'on24', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_on24_fields', 'adfoin_get_on24_fields' );

function adfoin_get_on24_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array(
            'key'      => 'email',
            'value'    => __( 'Email *', 'advanced-form-integration' ),
            'required' => true,
        ),
        array(
            'key'   => 'first_name',
            'value' => __( 'First Name', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'last_name',
            'value' => __( 'Last Name', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'company',
            'value' => __( 'Company', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'job_title',
            'value' => __( 'Job Title', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'phone',
            'value' => __( 'Phone Number', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'country',
            'value' => __( 'Country', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'state',
            'value' => __( 'State / Province', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'city',
            'value' => __( 'City', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'zip',
            'value' => __( 'Postal Code', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'custom_questions_json',
            'value'      => __( 'Custom Questions (JSON array)', 'advanced-form-integration' ),
            'type'       => 'textarea',
            'description'=> __( 'Example: [{"questionId":123,"response":"Yes"}]', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'demographics_json',
            'value'      => __( 'Demographics (JSON object)', 'advanced-form-integration' ),
            'type'       => 'textarea',
            'description'=> __( 'Example: {"industry":"Technology","employees":"100-500"}', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'custom_fields_json',
            'value'      => __( 'Additional Fields (JSON object)', 'advanced-form-integration' ),
            'type'       => 'textarea',
            'description'=> __( 'Merge arbitrary payload attributes accepted by ON24.', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_on24_job_queue', 'adfoin_on24_job_queue_handler', 10, 1 );

function adfoin_on24_job_queue_handler( $data ) {
    adfoin_on24_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_on24_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_on24_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $event_id = adfoin_on24_parse_value( $field_data, 'eventId', $posted_data );

    if ( '' === $event_id ) {
        adfoin_add_to_log( new WP_Error( 'on24_missing_event', __( 'ON24 event ID is required.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $payload = adfoin_on24_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    $payload['eventId'] = $event_id;

    $source_code = adfoin_on24_parse_value( $field_data, 'sourceCode', $posted_data );

    if ( '' !== $source_code ) {
        $payload['sourceCode'] = $source_code;
    }

    $endpoint = 'v2/registrants';
    $response = adfoin_on24_request( $credentials, $endpoint, 'POST', $payload, $record, true );

    if ( is_wp_error( $response ) ) {
        adfoin_add_to_log( $response, '', array(), $record );
    }
}

function adfoin_on24_collect_payload( $field_data, $posted_data ) {
    $email = adfoin_on24_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $email ) {
        return new WP_Error( 'on24_missing_email', __( 'ON24 requires an email address.', 'advanced-form-integration' ) );
    }

    $payload = array( 'email' => $email );

    $map = array(
        'first_name' => 'firstName',
        'last_name'  => 'lastName',
        'company'    => 'company',
        'job_title'  => 'jobTitle',
        'phone'      => 'phone',
        'country'    => 'country',
        'state'      => 'state',
        'city'       => 'city',
        'zip'        => 'zip',
    );

    foreach ( $map as $key => $api_key ) {
        $value = adfoin_on24_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    $questions = adfoin_on24_parse_value( $field_data, 'custom_questions_json', $posted_data );

    if ( '' !== $questions ) {
        $decoded = json_decode( $questions, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'on24_invalid_questions', __( 'Custom questions JSON must be an array.', 'advanced-form-integration' ) );
        }

        $payload['questionResponses'] = $decoded;
    }

    $demographics = adfoin_on24_parse_value( $field_data, 'demographics_json', $posted_data );

    if ( '' !== $demographics ) {
        $decoded = json_decode( $demographics, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'on24_invalid_demographics', __( 'Demographics JSON must be an object.', 'advanced-form-integration' ) );
        }

        $payload['demographics'] = $decoded;
    }

    $additional = adfoin_on24_parse_value( $field_data, 'custom_fields_json', $posted_data );

    if ( '' !== $additional ) {
        $decoded = json_decode( $additional, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'on24_invalid_custom', __( 'Additional fields JSON must be an object.', 'advanced-form-integration' ) );
        }

        $payload = array_merge( $payload, $decoded );
    }

    return $payload;
}

function adfoin_on24_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_on24_credentials_list() {
    $credentials = adfoin_read_credentials( 'on24' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_on24_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'on24', $cred_id );

    if ( empty( $credentials ) ) {
        return new WP_Error( 'on24_missing_credentials', __( 'ON24 credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_on24_get_access_token( $credentials, $force_refresh = false ) {
    $cred_identifier = isset( $credentials['id'] ) ? $credentials['id'] : md5( serialize( $credentials ) );
    $transient_key   = 'adfoin_on24_token_' . $cred_identifier;

    if ( ! $force_refresh ) {
        $cached = get_transient( $transient_key );

        if ( $cached && isset( $cached['token'] ) && $cached['token'] ) {
            return $cached['token'];
        }
    }

    $client_id     = isset( $credentials['clientId'] ) ? trim( $credentials['clientId'] ) : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? trim( $credentials['clientSecret'] ) : '';
    $base_url      = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.on24.com';

    if ( '' === $client_id || '' === $client_secret ) {
        return new WP_Error( 'on24_missing_auth', __( 'ON24 client ID or secret is missing.', 'advanced-form-integration' ) );
    }

    $token_url = $base_url . '/oauth/access_token';

    $args = array(
        'timeout' => 30,
        'body'    => array(
            'grant_type' => 'client_credentials',
        ),
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ),
    );

    $response = wp_remote_post( esc_url_raw( $token_url ), $args );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );
    $body   = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status >= 400 || ! isset( $body['access_token'] ) ) {
        $message = isset( $body['error_description'] ) ? $body['error_description'] : wp_remote_retrieve_body( $response );

        return new WP_Error( 'on24_token_error', $message ? $message : __( 'Failed to obtain ON24 access token.', 'advanced-form-integration' ) );
    }

    $expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3000;

    set_transient( $transient_key, array( 'token' => $body['access_token'] ), max( 60, $expires_in - 60 ) );

    return $body['access_token'];
}

function adfoin_on24_request( $credentials, $endpoint, $method = 'POST', $data = array(), $record = array(), $retry = false ) {
    $token = adfoin_on24_get_access_token( $credentials, false );

    if ( is_wp_error( $token ) ) {
        return $token;
    }

    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.on24.com';

    $url = $base_url . '/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );

    if ( 'GET' === strtoupper( $method ) ) {
        if ( ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }
    } else {
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

    if ( 401 === $status && ! $retry ) {
        adfoin_on24_get_access_token( $credentials, true );

        return adfoin_on24_request( $credentials, $endpoint, $method, $data, $record, true );
    }

    if ( $status >= 400 ) {
        $body = wp_remote_retrieve_body( $response );

        return new WP_Error( 'on24_http_error', $body ? $body : __( 'ON24 request failed.', 'advanced-form-integration' ), array( 'status' => $status ) );
    }

    return $response;
}
