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
                    'key'         => 'apiKey',
                    'label'       => __( 'Airmeet Access Key', 'advanced-form-integration' ),
                    'hidden'      => false,
                ),
                array(
                    'key'         => 'apiSecret',
                    'label'       => __( 'Airmeet Secret Key', 'advanced-form-integration' ),
                    'hidden'      => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
        </ol>
        <p>%4$s</p>',
        esc_html__( 'Navigate to https://airmeet.com and go to the CM dashboard.', 'advanced-form-integration' ),
        esc_html__( 'Click on the Integrations tab and then click on API access keys.', 'advanced-form-integration' ),
        esc_html__( 'Copy the Access Key and Secret Key, paste them here, then click "Save & Authenticate".', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential when configuring an Airmeet action. Provide the Airmeet ID from the event URL or dashboard when mapping a form.', 'advanced-form-integration' )
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
                    <label><?php esc_html_e( 'Airmeet Event', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[airmeetId]" v-model="fielddata.airmeetId" @focus="getAirmeets">
                        <option value=""><?php esc_html_e( 'Select Airmeet...', 'advanced-form-integration' ); ?></option>
                        <option v-for="airmeet in fielddata.airmeets" :value="airmeet.uid">{{ airmeet.name }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': airmeetsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php esc_html_e( 'Select an event from your Airmeet account.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Important Notes', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Email, First Name, and Last Name are required fields.', 'advanced-form-integration' ); ?></p>
                    <p><?php esc_html_e( 'For Hybrid Conference format, attendance_type (IN-PERSON or VIRTUAL) is mandatory.', 'advanced-form-integration' ); ?></p>
                    <p><?php esc_html_e( 'Name fields are trimmed to 35 characters. Organisation, Designation, City, and Country are trimmed to 70 characters.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_airmeet_list', 'adfoin_get_airmeet_list' );

function adfoin_get_airmeet_list() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( __( 'Credential ID is required.', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_airmeet_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        wp_send_json_error( $credentials->get_error_message() );
    }

    $response = adfoin_airmeet_request( $credentials, 'airmeets?size=500', 'GET' );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
        wp_send_json_error( __( 'Invalid response from Airmeet API.', 'advanced-form-integration' ) );
    }

    $airmeets = array();

    foreach ( $data['data'] as $airmeet ) {
        if ( isset( $airmeet['uid'], $airmeet['name'] ) ) {
            $airmeets[] = array(
                'uid'    => $airmeet['uid'],
                'name'   => $airmeet['name'],
                'status' => isset( $airmeet['status'] ) ? $airmeet['status'] : '',
            );
        }
    }

    wp_send_json_success( $airmeets );
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
            'description' => __( 'Required field', 'advanced-form-integration' ),
        ),
        array(
            'key'      => 'firstName',
            'value'    => __( 'First Name *', 'advanced-form-integration' ),
            'required' => true,
            'description' => __( 'Required field (max 35 characters)', 'advanced-form-integration' ),
        ),
        array(
            'key'      => 'lastName',
            'value'    => __( 'Last Name *', 'advanced-form-integration' ),
            'required' => true,
            'description' => __( 'Required field (max 35 characters)', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'organisation',
            'value' => __( 'Organisation', 'advanced-form-integration' ),
            'description' => __( 'Max 70 characters', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'designation',
            'value' => __( 'Designation / Job Title', 'advanced-form-integration' ),
            'description' => __( 'Max 70 characters', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'city',
            'value' => __( 'City', 'advanced-form-integration' ),
            'description' => __( 'Max 70 characters', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'country',
            'value' => __( 'Country', 'advanced-form-integration' ),
            'description' => __( 'Max 70 characters', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'attendance_type',
            'value' => __( 'Attendance Type', 'advanced-form-integration' ),
            'description' => __( 'IN-PERSON or VIRTUAL (mandatory for Hybrid Conference)', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'registerAttendee',
            'value' => __( 'Register Attendee', 'advanced-form-integration' ),
            'description' => __( 'true = confirmed registration, false = invitation only (default: false)', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'sendEmailInvite',
            'value' => __( 'Send Email Invite', 'advanced-form-integration' ),
            'description' => __( 'true = send email with calendar invite (default: true)', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_airmeet_job_queue', 'adfoin_airmeet_job_queue_handler', 10, 1 );

function adfoin_airmeet_job_queue_handler( $data ) {
    adfoin_airmeet_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_airmeet_send_data( $record, $posted_data ) {
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

    $endpoint = sprintf( 'airmeet/%s/attendee', rawurlencode( $airmeet_id ) );
    $response = adfoin_airmeet_request( $credentials, $endpoint, 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        adfoin_add_to_log( $response, '', array(), $record );
    }
}

function adfoin_airmeet_collect_payload( $field_data, $posted_data ) {
    $email     = adfoin_airmeet_parse_value( $field_data, 'email', $posted_data );
    $firstName = adfoin_airmeet_parse_value( $field_data, 'firstName', $posted_data );
    $lastName  = adfoin_airmeet_parse_value( $field_data, 'lastName', $posted_data );

    if ( '' === $email ) {
        return new WP_Error( 'airmeet_missing_email', __( 'Email is required.', 'advanced-form-integration' ) );
    }

    if ( '' === $firstName ) {
        return new WP_Error( 'airmeet_missing_firstname', __( 'First Name is required.', 'advanced-form-integration' ) );
    }

    if ( '' === $lastName ) {
        return new WP_Error( 'airmeet_missing_lastname', __( 'Last Name is required.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'email'     => $email,
        'firstName' => substr( $firstName, 0, 35 ),
        'lastName'  => substr( $lastName, 0, 35 ),
    );

    $map = array(
        'organisation'    => 'organisation',
        'designation'     => 'designation',
        'city'            => 'city',
        'country'         => 'country',
        'attendance_type' => 'attendance_type',
    );

    foreach ( $map as $key => $api_key ) {
        $value = adfoin_airmeet_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        if ( in_array( $key, array( 'organisation', 'designation', 'city', 'country' ), true ) ) {
            $value = substr( $value, 0, 70 );
        }

        $payload[ $api_key ] = $value;
    }

    $register_attendee = adfoin_airmeet_parse_value( $field_data, 'registerAttendee', $posted_data );

    if ( '' !== $register_attendee ) {
        $payload['registerAttendee'] = adfoin_airmeet_normalize_boolean( $register_attendee );
    }

    $send_email_invite = adfoin_airmeet_parse_value( $field_data, 'sendEmailInvite', $posted_data );

    if ( '' !== $send_email_invite ) {
        $payload['sendEmailInvite'] = adfoin_airmeet_normalize_boolean( $send_email_invite );
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

function adfoin_airmeet_get_access_token( $credentials ) {
    $api_key    = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';
    $api_secret = isset( $credentials['apiSecret'] ) ? trim( $credentials['apiSecret'] ) : '';

    if ( '' === $api_key || '' === $api_secret ) {
        return new WP_Error( 'airmeet_missing_auth', __( 'Airmeet API key or secret is missing.', 'advanced-form-integration' ) );
    }

    $transient_key = 'airmeet_token_' . md5( $api_key . $api_secret );
    $cached_token  = get_transient( $transient_key );

    if ( false !== $cached_token ) {
        return $cached_token;
    }

    $url = 'https://api-gateway.airmeet.com/prod/auth';

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Content-Type'          => 'application/json',
            'Accept'                => 'application/json',
            'X-Airmeet-Access-Key'  => $api_key,
            'X-Airmeet-Secret-Key'  => $api_secret,
        ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status >= 400 ) {
        $body    = wp_remote_retrieve_body( $response );
        $message = $body ? $body : __( 'Airmeet authentication failed.', 'advanced-form-integration' );

        return new WP_Error( 'airmeet_auth_error', $message, array( 'status' => $status ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! isset( $data['token'] ) || empty( $data['token'] ) ) {
        return new WP_Error( 'airmeet_invalid_token', __( 'Failed to retrieve access token from Airmeet.', 'advanced-form-integration' ) );
    }

    $token = $data['token'];

    set_transient( $transient_key, $token, 25 * DAY_IN_SECONDS );

    return $token;
}

function adfoin_airmeet_request( $credentials, $endpoint, $method = 'POST', $data = array(), $record = array() ) {
    $access_token = adfoin_airmeet_get_access_token( $credentials );

    if ( is_wp_error( $access_token ) ) {
        return $access_token;
    }

    $url = 'https://api-gateway.airmeet.com/prod/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Content-Type'           => 'application/json',
            'Accept'                 => 'application/json',
            'X-Airmeet-Access-Token' => $access_token,
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
