<?php

add_filter( 'adfoin_action_providers', 'adfoin_zohomeeting_actions', 10, 1 );

function adfoin_zohomeeting_actions( $actions ) {
    $actions['zohomeeting'] = array(
        'title' => __( 'Zoho Meeting', 'advanced-form-integration' ),
        'tasks' => array(
            'register_contact' => __( 'Register Contact to Webinar', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_zohomeeting_settings_tab', 10, 1 );

function adfoin_zohomeeting_settings_tab( $providers ) {
    $providers['zohomeeting'] = __( 'Zoho Meeting', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_zohomeeting_settings_view', 10, 1 );

function adfoin_zohomeeting_settings_view( $current_tab ) {
    if ( 'zohomeeting' !== $current_tab ) {
        return;
    }

    $title = __( 'Zoho Meeting', 'advanced-form-integration' );
    $key   = 'zohomeeting';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'orgId',
                'label'  => __( 'Organization ID', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'authToken',
                'label'  => __( 'Auth Token', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'baseUrl',
                'label'  => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
                'placeholder' => 'https://meeting.zoho.com/meeting/api/v2',
            ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
        </ol>
        <p>%4$s</p>',
        esc_html__( 'Log in to Zoho Meeting, open Settings → Developer, and generate an auth token for the Webinar API.', 'advanced-form-integration' ),
        esc_html__( 'Copy the Organization ID and Auth Token and paste them below.', 'advanced-form-integration' ),
        esc_html__( 'Save the credentials, then map fields when configuring the Zoho Meeting action.', 'advanced-form-integration' ),
        esc_html__( 'This integration calls the Zoho Meeting bulk registration API.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_zohomeeting_action_fields' );

function adfoin_zohomeeting_action_fields() {
    ?>
    <script type="text/template" id="zohomeeting-action-template">
        <table class="form-table" v-if="action.task == 'register_contact'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Zoho Meeting Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_zohomeeting_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_zohomeeting_credentials', 'adfoin_get_zohomeeting_credentials' );

function adfoin_get_zohomeeting_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'zohomeeting' ) );
}

add_action( 'wp_ajax_adfoin_save_zohomeeting_credentials', 'adfoin_save_zohomeeting_credentials' );

function adfoin_save_zohomeeting_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'zohomeeting' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'zohomeeting', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_zohomeeting_fields', 'adfoin_get_zohomeeting_fields' );

function adfoin_get_zohomeeting_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'webinar_keys', 'value' => __( 'Webinar Keys (comma separated)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'zip', 'value' => __( 'ZIP / Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea' ),
    );

    wp_send_json_success( $fields );
}

function adfoin_zohomeeting_credentials_list() {
    $credentials = adfoin_read_credentials( 'zohomeeting' );

    if ( ! empty( $credentials ) && is_array( $credentials ) ) {
        foreach ( $credentials as $credential ) {
            $title = isset( $credential['title'] ) ? $credential['title'] : '';
            $id    = isset( $credential['id'] ) ? $credential['id'] : '';

            if ( $title && $id ) {
                echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</option>';
            }
        }
    }
}

function adfoin_zohomeeting_get_credentials( $cred_id ) {
    $credentials = adfoin_read_credentials( 'zohomeeting' );

    if ( empty( $credentials ) || ! is_array( $credentials ) ) {
        return new WP_Error( 'zohomeeting_no_credentials', __( 'No Zoho Meeting credentials found.', 'advanced-form-integration' ) );
    }

    foreach ( $credentials as $credential ) {
        if ( isset( $credential['id'] ) && $credential['id'] === $cred_id ) {
            return $credential;
        }
    }

    return new WP_Error( 'zohomeeting_invalid_cred_id', __( 'Invalid Zoho Meeting credential ID.', 'advanced-form-integration' ) );
}

add_action( 'adfoin_zohomeeting_job_queue', 'adfoin_zohomeeting_job_queue', 10, 1 );

function adfoin_zohomeeting_job_queue( $data ) {
    adfoin_zohomeeting_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_zohomeeting_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_zohomeeting_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_zohomeeting_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    $webinar_keys = $payload['webinarKeys'];
    $registrants  = $payload['registrants'];

    foreach ( $webinar_keys as $webinar_key ) {
        $body = array(
            'registrants' => array(
                array_merge( $registrants[0], array( 'webinarKey' => $webinar_key ) ),
            ),
        );

        adfoin_zohomeeting_request( 'bulkRegistrants', 'POST', $body, $record, $credentials );
    }
}

function adfoin_zohomeeting_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $auth_token = isset( $credentials['authToken'] ) ? trim( $credentials['authToken'] ) : '';
    $org_id     = isset( $credentials['orgId'] ) ? trim( $credentials['orgId'] ) : '';
    $base       = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://meeting.zoho.com/meeting/api/v2';

    if ( '' === $auth_token || '' === $org_id ) {
        return new WP_Error( 'zohomeeting_missing_auth', __( 'Zoho Meeting org ID or auth token is missing.', 'advanced-form-integration' ) );
    }

    $url = $base . '/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Zoho-oauthtoken ' . $auth_token,
            'orgId'         => $org_id,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
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
        $message = $body ? $body : __( 'Zoho Meeting request failed.', 'advanced-form-integration' );

        return new WP_Error( 'zohomeeting_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}

function adfoin_zohomeeting_collect_payload( $field_data, $posted_data ) {
    $webinar_keys_raw = adfoin_get_parsed_values( $field_data['webinar_keys'] ?? '', $posted_data );

    if ( '' === $webinar_keys_raw ) {
        return new WP_Error( 'zohomeeting_missing_webinar', __( 'Provide at least one webinar key.', 'advanced-form-integration' ) );
    }

    $webinar_keys = array_filter( array_map( 'trim', explode( ',', $webinar_keys_raw ) ) );

    $registrant = array();

    $email = adfoin_get_parsed_values( $field_data['email'] ?? '', $posted_data );
    if ( '' !== $email ) {
        $registrant['email'] = $email;
    }

    $phone = adfoin_get_parsed_values( $field_data['phone'] ?? '', $posted_data );
    if ( '' !== $phone ) {
        $registrant['phoneNumber'] = $phone;
    }

    if ( empty( $registrant['email'] ) && empty( $registrant['phoneNumber'] ) ) {
        return new WP_Error( 'zohomeeting_missing_contact', __( 'Provide an email or phone number.', 'advanced-form-integration' ) );
    }

    $map = array(
        'first_name' => 'firstName',
        'last_name'  => 'lastName',
        'company'    => 'company',
        'job_title'  => 'jobTitle',
        'country'    => 'country',
        'state'      => 'State',
        'city'       => 'city',
        'zip'        => 'zipCode',
    );

    foreach ( $map as $key => $api_field ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        $registrant[ $api_field ] = $value;
    }

    $custom = adfoin_get_parsed_values( $field_data['custom_fields_json'] ?? '', $posted_data );

    if ( '' !== $custom && null !== $custom ) {
        $decoded = json_decode( $custom, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'zohomeeting_invalid_custom_fields', __( 'Custom fields JSON is invalid.', 'advanced-form-integration' ) );
        }

        $registrant = array_merge( $registrant, $decoded );
    }

    return array(
        'webinarKeys' => $webinar_keys,
        'registrants' => array( $registrant ),
    );
}
