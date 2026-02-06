<?php

add_filter( 'adfoin_action_providers', 'adfoin_eztexting_actions', 10, 1 );

function adfoin_eztexting_actions( $actions ) {
    $actions['eztexting'] = array(
        'title' => __( 'EZ Texting', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create / Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_eztexting_settings_tab', 10, 1 );

function adfoin_eztexting_settings_tab( $providers ) {
    $providers['eztexting'] = __( 'EZ Texting', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_eztexting_settings_view', 10, 1 );

function adfoin_eztexting_settings_view( $current_tab ) {
    if ( 'eztexting' !== $current_tab ) {
        return;
    }

    $title = __( 'EZ Texting', 'advanced-form-integration' );
    $key   = 'eztexting';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'username',
                'label'  => __( 'API Username', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'password',
                'label'  => __( 'API Password', 'advanced-form-integration' ),
                'hidden' => true,
            ),
            array(
                'key'    => 'baseUrl',
                'label'  => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
                'placeholder' => 'https://api.eztexting.com',
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
        esc_html__( 'Log in to EZ Texting and open the API Settings page.', 'advanced-form-integration' ),
        esc_html__( 'Create or locate your API username and password (or token) for REST access.', 'advanced-form-integration' ),
        esc_html__( 'Enter the credentials below, optionally override the API base URL if EZ Texting provides a regional endpoint, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Assign the saved credential when configuring an action to push contacts to EZ Texting lists.', 'advanced-form-integration' ),
        esc_html__( 'Requests are sent to EZ Texting’s REST v2 API using Basic authentication.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_eztexting_action_fields' );

function adfoin_eztexting_action_fields() {
    ?>
    <script type="text/template" id="eztexting-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'EZ Texting Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_eztexting_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Notes', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide at least a phone number or email. Use comma-separated group IDs to subscribe the contact to one or more campaigns.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_eztexting_credentials', 'adfoin_get_eztexting_credentials' );

function adfoin_get_eztexting_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'eztexting' ) );
}

add_action( 'wp_ajax_adfoin_save_eztexting_credentials', 'adfoin_save_eztexting_credentials' );

function adfoin_save_eztexting_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'eztexting' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'eztexting', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_eztexting_fields', 'adfoin_get_eztexting_fields' );

function adfoin_get_eztexting_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'phone', 'value' => __( 'Phone Number (E.164 or digits)', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email Address', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'address', 'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'birthday', 'value' => __( 'Birthday (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'anniversary', 'value' => __( 'Anniversary (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_fields_json', 'value' => __( 'Custom Fields (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: {"FavoriteStore":"Downtown"}', 'advanced-form-integration' ) ),
        array( 'key' => 'group_ids', 'value' => __( 'Group IDs (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'opt_in', 'value' => __( 'Opt-in Status (true/false)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_eztexting_job_queue', 'adfoin_eztexting_job_queue_handler', 10, 1 );

function adfoin_eztexting_job_queue_handler( $data ) {
    adfoin_eztexting_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_eztexting_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_eztexting_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_eztexting_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_eztexting_request( 'contacts', 'POST', $payload, $record, $credentials );
}

function adfoin_eztexting_collect_payload( $field_data, $posted_data ) {
    $phone = adfoin_eztexting_parse_value( $field_data, 'phone', $posted_data );
    $email = adfoin_eztexting_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $phone && '' === $email ) {
        return new WP_Error( 'eztexting_missing_contact', __( 'Provide at least a phone number or an email for EZ Texting.', 'advanced-form-integration' ) );
    }

    $payload = array();

    if ( '' !== $phone ) {
        $payload['phoneNumber'] = $phone;
    }

    if ( '' !== $email ) {
        $payload['email'] = $email;
    }

    $map = array(
        'first_name'  => 'firstName',
        'last_name'   => 'lastName',
        'company'     => 'companyName',
        'address'     => 'address',
        'city'        => 'city',
        'state'       => 'state',
        'postal_code' => 'postalCode',
        'country'     => 'country',
        'birthday'    => 'birthday',
        'anniversary' => 'anniversary',
    );

    foreach ( $map as $key => $api_field ) {
        $value = adfoin_eztexting_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_field ] = $value;
    }

    $custom = adfoin_eztexting_parse_value( $field_data, 'custom_fields_json', $posted_data );

    if ( '' !== $custom ) {
        $decoded = json_decode( $custom, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'eztexting_invalid_custom_fields', __( 'Custom fields JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['customFields'] = $decoded;
    }

    $groups = adfoin_eztexting_parse_value( $field_data, 'group_ids', $posted_data );

    if ( '' !== $groups ) {
        $payload['groupIds'] = array_filter( array_map( 'trim', explode( ',', $groups ) ) );
    }

    $opt_in = adfoin_eztexting_parse_value( $field_data, 'opt_in', $posted_data );

    if ( '' !== $opt_in ) {
        $payload['optIn'] = in_array( strtolower( $opt_in ), array( '1', 'true', 'yes', 'on' ), true );
    }

    return $payload;
}

function adfoin_eztexting_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_eztexting_credentials_list() {
    $credentials = adfoin_read_credentials( 'eztexting' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_eztexting_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'eztexting', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'eztexting_missing_credentials', __( 'EZ Texting credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_eztexting_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $username = isset( $credentials['username'] ) ? trim( $credentials['username'] ) : '';
    $password = isset( $credentials['password'] ) ? trim( $credentials['password'] ) : '';
    $base     = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.eztexting.com';

    if ( '' === $username || '' === $password ) {
        return new WP_Error( 'eztexting_missing_auth', __( 'EZ Texting API username or password is missing.', 'advanced-form-integration' ) );
    }

    $auth = base64_encode( $username . ':' . $password );
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
        $message = $body ? $body : __( 'EZ Texting request failed.', 'advanced-form-integration' );

        return new WP_Error( 'eztexting_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
