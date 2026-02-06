<?php

add_filter( 'adfoin_action_providers', 'adfoin_justcall_actions', 10, 1 );

function adfoin_justcall_actions( $actions ) {
    $actions['justcall'] = array(
        'title' => __( 'JustCall', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create / Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_justcall_settings_tab', 10, 1 );

function adfoin_justcall_settings_tab( $providers ) {
    $providers['justcall'] = __( 'JustCall', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_justcall_settings_view', 10, 1 );

function adfoin_justcall_settings_view( $current_tab ) {
    if ( 'justcall' !== $current_tab ) {
        return;
    }

    $title = __( 'JustCall', 'advanced-form-integration' );
    $key   = 'justcall';

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
                'key'        => 'baseUrl',
                'label'      => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                'hidden'     => false,
                'placeholder'=> 'https://api.justcall.io',
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
        esc_html__( 'Log in to your JustCall dashboard and open Settings → Developer API.', 'advanced-form-integration' ),
        esc_html__( 'Generate an API key and API secret with permissions for contacts.', 'advanced-form-integration' ),
        esc_html__( 'Paste the credentials here, optionally override the base URL if JustCall provides a regional endpoint, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential when configuring a JustCall action to create contacts from your forms.', 'advanced-form-integration' ),
        esc_html__( 'AFI authenticates using HTTP Basic auth against the JustCall v2 REST API.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_justcall_action_fields' );

function adfoin_justcall_action_fields() {
    ?>
    <script type="text/template" id="justcall-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'JustCall Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_justcall_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Tips', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide at least one phone number. Use the JSON fields to include additional numbers, emails, or custom properties as required.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_justcall_credentials', 'adfoin_get_justcall_credentials' );

function adfoin_get_justcall_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'justcall' ) );
}

add_action( 'wp_ajax_adfoin_save_justcall_credentials', 'adfoin_save_justcall_credentials' );

function adfoin_save_justcall_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'justcall' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'justcall', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_justcall_fields', 'adfoin_get_justcall_fields' );

function adfoin_get_justcall_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'phone_number', 'value' => __( 'Primary Phone Number (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone_label', 'value' => __( 'Primary Phone Label', 'advanced-form-integration' ) ),
        array( 'key' => 'name', 'value' => __( 'Full Name', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Primary Email', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'website', 'value' => __( 'Website URL', 'advanced-form-integration' ) ),
        array( 'key' => 'address', 'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'tags', 'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'notes', 'value' => __( 'Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'emails_json', 'value' => __( 'Emails (JSON array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: [{"email":"alt@example.com","label":"work"}]', 'advanced-form-integration' ) ),
        array( 'key' => 'phone_numbers_json', 'value' => __( 'Phone Numbers (JSON array)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Override primary number. Example: [{"phone_number":"+15551234567","label":"mobile"}]', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'other_details_json', 'value' => __( 'Other Details (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_justcall_job_queue', 'adfoin_justcall_job_queue', 10, 1 );

function adfoin_justcall_job_queue( $data ) {
    adfoin_justcall_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_justcall_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_justcall_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_justcall_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_justcall_request( 'contacts', 'POST', $payload, $record, $credentials );
}

function adfoin_justcall_collect_payload( $field_data, $posted_data ) {
    $phone = adfoin_justcall_parse_value( $field_data, 'phone_number', $posted_data );

    if ( '' === $phone ) {
        return new WP_Error( 'justcall_missing_phone', __( 'JustCall requires a phone number.', 'advanced-form-integration' ) );
    }

    $payload = array();

    $name = adfoin_justcall_parse_value( $field_data, 'name', $posted_data );
    $first = adfoin_justcall_parse_value( $field_data, 'first_name', $posted_data );
    $last  = adfoin_justcall_parse_value( $field_data, 'last_name', $posted_data );

    if ( '' !== $name ) {
        $payload['name'] = $name;
    } elseif ( '' !== $first || '' !== $last ) {
        $payload['name'] = trim( $first . ' ' . $last );
    }

    $map = array(
        'email'       => 'email',
        'company'     => 'company',
        'job_title'   => 'job_title',
        'website'     => 'url',
        'address'     => 'address',
        'city'        => 'city',
        'state'       => 'state',
        'postal_code' => 'postal_code',
        'country'     => 'country',
        'notes'       => 'notes',
    );

    foreach ( $map as $key => $api_field ) {
        $value = adfoin_justcall_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_field ] = $value;
    }

    $tags = adfoin_justcall_parse_value( $field_data, 'tags', $posted_data );

    if ( '' !== $tags ) {
        $payload['tags'] = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
    }

    $phones_override = adfoin_justcall_parse_value( $field_data, 'phone_numbers_json', $posted_data );

    if ( '' !== $phones_override ) {
        $decoded = json_decode( $phones_override, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'justcall_invalid_phone_numbers', __( 'Phone numbers JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['phone_numbers'] = $decoded;
    } else {
        $label = adfoin_justcall_parse_value( $field_data, 'phone_label', $posted_data );

        $payload['phone_numbers'] = array(
            array(
                'phone_number' => $phone,
                'label'        => $label ? $label : 'mobile',
            ),
        );
    }

    $emails_override = adfoin_justcall_parse_value( $field_data, 'emails_json', $posted_data );

    if ( '' !== $emails_override ) {
        $decoded = json_decode( $emails_override, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'justcall_invalid_emails', __( 'Emails JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['emails'] = $decoded;
    } elseif ( '' !== $payload['email'] ?? '' ) {
        $payload['emails'] = array(
            array(
                'email' => $payload['email'],
                'label' => 'primary',
            ),
        );
    }

    $custom_fields = adfoin_justcall_parse_value( $field_data, 'custom_fields_json', $posted_data );

    if ( '' !== $custom_fields ) {
        $decoded = json_decode( $custom_fields, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'justcall_invalid_custom_fields', __( 'Custom fields JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['custom_fields'] = $decoded;
    }

    $other_details = adfoin_justcall_parse_value( $field_data, 'other_details_json', $posted_data );

    if ( '' !== $other_details ) {
        $decoded = json_decode( $other_details, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'justcall_invalid_other_details', __( 'Other details JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['other_details'] = $decoded;
    }

    return $payload;
}

function adfoin_justcall_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_justcall_credentials_list() {
    $credentials = adfoin_read_credentials( 'justcall' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_justcall_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'justcall', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'justcall_missing_credentials', __( 'JustCall credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_justcall_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $api_key    = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';
    $api_secret = isset( $credentials['apiSecret'] ) ? trim( $credentials['apiSecret'] ) : '';
    $base       = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.justcall.io';

    if ( '' === $api_key || '' === $api_secret ) {
        return new WP_Error( 'justcall_missing_auth', __( 'JustCall API key or secret is missing.', 'advanced-form-integration' ) );
    }

    $auth = base64_encode( $api_key . ':' . $api_secret );
    $url  = $base . '/v2/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $data ),
    );

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
        $message = $body ? $body : __( 'JustCall request failed.', 'advanced-form-integration' );

        return new WP_Error( 'justcall_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
