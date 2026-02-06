<?php

add_filter( 'adfoin_action_providers', 'adfoin_airmeet_actions', 10, 1 );

function adfoin_airmeet_actions( $actions ) {
    $actions['airmeet'] = array(
        'title' => __( 'Airmeet', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_airmeet_settings_tab', 10, 1 );

function adfoin_airmeet_settings_tab( $providers ) {
    $providers['airmeet'] = __( 'Airmeet', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_airmeet_settings_view', 10, 1 );

function adfoin_airmeet_settings_view( $current_tab ) {
    if ( 'airmeet' !== $current_tab ) {
        return;
    }

    $title = __( 'Airmeet', 'advanced-form-integration' );
    $key   = 'airmeet';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'         => 'title',
                    'label'       => __( 'Credential Label', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => __( 'Main Airmeet Account', 'advanced-form-integration' ),
                ),
                array(
                    'key'         => 'apiKey',
                    'label'       => __( 'API Key', 'advanced-form-integration' ),
                    'hidden'      => false,
                ),
                array(
                    'key'         => 'apiSecret',
                    'label'       => __( 'API Secret', 'advanced-form-integration' ),
                    'hidden'      => true,
                ),
                array(
                    'key'         => 'baseUrl',
                    'label'       => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => 'https://api.airmeet.com/public/v1',
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
        esc_html__( 'Log in to Airmeet, open Settings → Integrations → Public API, and create an API key/secret pair.', 'advanced-form-integration' ),
        esc_html__( 'Copy the generated API key and secret (stored securely; the secret is shown only once).', 'advanced-form-integration' ),
        esc_html__( 'Paste the credentials here, optionally override the API base URL if Airmeet instructs you to use a regional endpoint, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential when configuring an Airmeet action. Provide the Airmeet ID from the event’s URL or dashboard when mapping a form.', 'advanced-form-integration' ),
        esc_html__( 'Refer to Airmeet’s Public API guide for supported attendee attributes and ticket classes.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_airmeet_action_fields' );

function adfoin_airmeet_action_fields() {
    ?>
    <script type="text/template" id="airmeet-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Airmeet Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_airmeet_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Airmeet ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[airmeetId]" v-model="fielddata.airmeetId" placeholder="am-XXXXXXXX" />
                    <p class="description"><?php esc_html_e( 'Use the Airmeet identifier from the event URL (for example, am-abc123).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr>
                <td scope="row-title">
                    <label><?php esc_html_e( 'Ticket Class ID (optional)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[ticketClassId]" v-model="fielddata.ticketClassId" placeholder="tc-XXXXXXXX" />
                    <p class="description"><?php esc_html_e( 'Provide a ticket class ID when the Airmeet uses paid/segmented registrations.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Send Confirmation Email', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[sendEmail]" v-model="fielddata.sendEmail">
                        <option value=""><?php esc_html_e( 'Use Airmeet default', 'advanced-form-integration' ); ?></option>
                        <option value="true"><?php esc_html_e( 'Yes', 'advanced-form-integration' ); ?></option>
                        <option value="false"><?php esc_html_e( 'No', 'advanced-form-integration' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Overrides Airmeet’s default when registering attendees via API.', 'advanced-form-integration' ); ?></p>
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
                    <p><?php esc_html_e( 'Map at least the attendee email. Add JSON payloads for custom attributes or tags when required by your Airmeet configuration.', 'advanced-form-integration' ); ?></p>
                    <p><?php esc_html_e( 'See Airmeet → Settings → Public API for acceptable field names.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_airmeet_credentials', 'adfoin_get_airmeet_credentials' );

function adfoin_get_airmeet_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'airmeet' ) );
}

add_action( 'wp_ajax_adfoin_save_airmeet_credentials', 'adfoin_save_airmeet_credentials' );

function adfoin_save_airmeet_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'airmeet' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'airmeet', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_airmeet_fields', 'adfoin_get_airmeet_fields' );

function adfoin_get_airmeet_fields() {
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
            'key'   => 'phone_number',
            'value' => __( 'Phone Number', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'company',
            'value' => __( 'Company', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'designation',
            'value' => __( 'Designation / Job Title', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'country',
            'value' => __( 'Country', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'city',
            'value' => __( 'City', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'role',
            'value' => __( 'Role (attendee, speaker, host)', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'tags_json',
            'value'      => __( 'Tags (JSON array)', 'advanced-form-integration' ),
            'type'       => 'textarea',
            'description'=> __( 'Example: ["vip","priority"]', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'custom_fields_json',
            'value'      => __( 'Custom Attributes (JSON object)', 'advanced-form-integration' ),
            'type'       => 'textarea',
            'description'=> __( 'Key/value pairs sent as additional attendee attributes.', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_airmeet_job_queue', 'adfoin_airmeet_job_queue_handler', 10, 1 );

function adfoin_airmeet_job_queue_handler( $data ) {
    adfoin_airmeet_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_airmeet_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_airmeet_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $airmeet_id = adfoin_airmeet_parse_value( $field_data, 'airmeetId', $posted_data );

    if ( '' === $airmeet_id ) {
        adfoin_add_to_log( new WP_Error( 'airmeet_missing_id', __( 'Airmeet ID is required.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $payload = adfoin_airmeet_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    $ticket_class_id = adfoin_airmeet_parse_value( $field_data, 'ticketClassId', $posted_data );

    if ( '' !== $ticket_class_id ) {
        $payload['ticket_class_id'] = $ticket_class_id;
    }

    $send_email = adfoin_airmeet_parse_value( $field_data, 'sendEmail', $posted_data );

    if ( '' !== $send_email ) {
        $payload['send_email'] = adfoin_airmeet_normalize_boolean( $send_email );
    }

    $endpoint = sprintf( 'airmeets/%s/participants', rawurlencode( $airmeet_id ) );
    $response = adfoin_airmeet_request( $credentials, $endpoint, 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        adfoin_add_to_log( $response, '', array(), $record );
    }
}

function adfoin_airmeet_collect_payload( $field_data, $posted_data ) {
    $email = adfoin_airmeet_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $email ) {
        return new WP_Error( 'airmeet_missing_email', __( 'Airmeet requires an attendee email.', 'advanced-form-integration' ) );
    }

    $payload = array( 'email' => $email );

    $map = array(
        'first_name'  => 'first_name',
        'last_name'   => 'last_name',
        'phone_number'=> 'phone_number',
        'company'     => 'company',
        'designation' => 'designation',
        'country'     => 'country',
        'city'        => 'city',
        'role'        => 'role',
    );

    foreach ( $map as $key => $api_key ) {
        $value = adfoin_airmeet_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    $tags = adfoin_airmeet_parse_value( $field_data, 'tags_json', $posted_data );

    if ( '' !== $tags ) {
        $decoded = json_decode( $tags, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'airmeet_invalid_tags', __( 'Airmeet tags JSON must be an array.', 'advanced-form-integration' ) );
        }

        $payload['tags'] = array_values( array_map( 'strval', $decoded ) );
    }

    $custom = adfoin_airmeet_parse_value( $field_data, 'custom_fields_json', $posted_data );

    if ( '' !== $custom ) {
        $decoded = json_decode( $custom, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'airmeet_invalid_custom', __( 'Airmeet custom attributes JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['custom_attributes'] = $decoded;
    }

    return $payload;
}

function adfoin_airmeet_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_airmeet_normalize_boolean( $value ) {
    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function adfoin_airmeet_credentials_list() {
    $credentials = adfoin_read_credentials( 'airmeet' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_airmeet_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'airmeet', $cred_id );

    if ( empty( $credentials ) ) {
        return new WP_Error( 'airmeet_missing_credentials', __( 'Airmeet credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_airmeet_request( $credentials, $endpoint, $method = 'POST', $data = array(), $record = array() ) {
    $api_key    = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';
    $api_secret = isset( $credentials['apiSecret'] ) ? trim( $credentials['apiSecret'] ) : '';
    $base_url   = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.airmeet.com/public/v1';

    if ( '' === $api_key || '' === $api_secret ) {
        return new WP_Error( 'airmeet_missing_auth', __( 'Airmeet API key or secret is missing.', 'advanced-form-integration' ) );
    }

    $url = $base_url . '/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'x-api-key'    => $api_key,
            'x-api-secret' => $api_secret,
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

    if ( $status >= 400 ) {
        $body    = wp_remote_retrieve_body( $response );
        $message = $body ? $body : __( 'Airmeet request failed.', 'advanced-form-integration' );

        return new WP_Error( 'airmeet_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
