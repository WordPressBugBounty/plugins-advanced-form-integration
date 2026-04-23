<?php

add_filter( 'adfoin_action_providers', 'adfoin_bigmarker_actions', 10, 1 );

function adfoin_bigmarker_actions( $actions ) {
    $actions['bigmarker'] = array(
        'title' => __( 'BigMarker', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_bigmarker_settings_tab', 10, 1 );

function adfoin_bigmarker_settings_tab( $providers ) {
    $providers['bigmarker'] = __( 'BigMarker', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_bigmarker_settings_view', 10, 1 );

function adfoin_bigmarker_settings_view( $current_tab ) {
    if ( 'bigmarker' !== $current_tab ) {
        return;
    }

    $title = __( 'BigMarker', 'advanced-form-integration' );
    $key   = 'bigmarker';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'    => 'apiKey',
                    'label'  => __( 'API Key', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'         => 'baseUrl',
                    'label'       => __( 'Base URL', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => 'https://xxx.bigmarker.com',
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
        esc_html__( 'Go to your BigMarker Account Settings → API Keys to get your API key, or use the login API.', 'advanced-form-integration' ),
        esc_html__( 'Copy your API key and the base URL provided (e.g., https://xxx.bigmarker.com).', 'advanced-form-integration' ),
        esc_html__( 'Paste them here and click "Save & Authenticate".', 'advanced-form-integration' ),
        esc_html__( 'Your API keys carry many privileges, keep them secret and never share in public areas.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_bigmarker_credentials', 'adfoin_get_bigmarker_credentials' );

function adfoin_get_bigmarker_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'bigmarker' ) );
}

add_action( 'wp_ajax_adfoin_save_bigmarker_credentials', 'adfoin_save_bigmarker_credentials' );

function adfoin_save_bigmarker_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'bigmarker' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'bigmarker', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_bigmarker_conferences', 'adfoin_get_bigmarker_conferences' );

function adfoin_get_bigmarker_conferences() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( __( 'Credential ID is required.', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_bigmarker_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        wp_send_json_error( $credentials->get_error_message() );
    }

    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? rtrim( $credentials['baseUrl'], '/' ) : 'https://www.bigmarker.com';
    $url      = $base_url . '/api/v1/conferences/?per_page=100&type=all';

    $response = adfoin_bigmarker_get_request( $url, $credentials );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! isset( $data['conferences'] ) || ! is_array( $data['conferences'] ) ) {
        wp_send_json_error( __( 'Invalid response from BigMarker API.', 'advanced-form-integration' ) );
    }

    $conferences = array();

    foreach ( $data['conferences'] as $conference ) {
        if ( isset( $conference['id'], $conference['title'], $conference['conference_address'] ) ) {
            preg_match( '#/([^/]+)/([^/]+)/?$#', $conference['conference_address'], $matches );
            
            $conferences[] = array(
                'id'              => $conference['id'],
                'title'           => $conference['title'],
                'channel_slug'    => isset( $matches[1] ) ? $matches[1] : '',
                'conference_slug' => isset( $matches[2] ) ? $matches[2] : '',
            );
        }
    }

    wp_send_json_success( $conferences );
}

add_action( 'adfoin_action_fields', 'adfoin_bigmarker_action_fields' );

function adfoin_bigmarker_action_fields() {
    ?>
    <script type="text/template" id="bigmarker-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'BigMarker Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_bigmarker_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td scope="row-title">
                    <label><?php esc_html_e( 'Conference', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': conferencesLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <select name="fieldData[conferenceId]" v-model="fielddata.conferenceId" v-if="!conferencesLoading">
                        <option value=""><?php esc_html_e( 'Select conference…', 'advanced-form-integration' ); ?></option>
                        <option v-for="conference in conferences" :value="conference.id">{{ conference.title }}</option>
                    </select>
                    <input type="hidden" name="fieldData[channelSlug]" v-model="fielddata.channelSlug" />
                    <input type="hidden" name="fieldData[conferenceSlug]" v-model="fielddata.conferenceSlug" />
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
                    <p><?php esc_html_e( 'Email, First Name, and Last Name are required fields for BigMarker registration.', 'advanced-form-integration' ); ?></p>
                    <p><?php esc_html_e( 'Provide JSON for Custom Fields to pass custom field values. Keys should be custom field IDs or API names.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_bigmarker_fields', 'adfoin_get_bigmarker_fields' );

function adfoin_get_bigmarker_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email *', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name', 'value' => __( 'First Name *', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name *', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'temporary_password', 'value' => __( 'Temporary Password', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_fields', 'value' => __( 'Custom Fields (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'URL encoded JSON object. Keys are custom field IDs or API names. Example: {"field_id":"value"}', 'advanced-form-integration' ) ),
        array( 'key' => 'utm_bmcr_source', 'value' => __( 'UTM Source Code', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_user_id', 'value' => __( 'Custom User ID', 'advanced-form-integration' ), 'description' => __( 'External custom user ID', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_bigmarker_job_queue', 'adfoin_bigmarker_job_queue', 10, 1 );

function adfoin_bigmarker_job_queue( $data ) {
    adfoin_bigmarker_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_bigmarker_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_bigmarker_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $conference_id = adfoin_bigmarker_parse_value( $field_data, 'conferenceId', $posted_data );

    if ( '' === $conference_id ) {
        adfoin_add_to_log( new WP_Error( 'bigmarker_missing_conference_id', __( 'Conference ID is required for BigMarker registration.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $payload = adfoin_bigmarker_collect_payload( $field_data, $posted_data, $conference_id );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? rtrim( $credentials['baseUrl'], '/' ) : 'https://www.bigmarker.com';
    $endpoint = $base_url . '/api/v1/conferences/register_or_update';

    $response = adfoin_bigmarker_request( $endpoint, $payload, $credentials, $record );

    if ( is_wp_error( $response ) ) {
        adfoin_add_to_log( $response, '', array(), $record );
    }
}

function adfoin_bigmarker_collect_payload( $field_data, $posted_data, $conference_id ) {
    $email = adfoin_bigmarker_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $email ) {
        return new WP_Error( 'bigmarker_missing_email', __( 'BigMarker requires an email address.', 'advanced-form-integration' ) );
    }

    $first_name = adfoin_bigmarker_parse_value( $field_data, 'first_name', $posted_data );
    $last_name  = adfoin_bigmarker_parse_value( $field_data, 'last_name', $posted_data );

    if ( '' === $first_name ) {
        return new WP_Error( 'bigmarker_missing_first_name', __( 'BigMarker requires first name.', 'advanced-form-integration' ) );
    }

    if ( '' === $last_name ) {
        return new WP_Error( 'bigmarker_missing_last_name', __( 'BigMarker requires last name.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'id'         => $conference_id,
        'email'      => $email,
        'first_name' => $first_name,
        'last_name'  => $last_name,
    );

    $map = array(
        'temporary_password' => 'temporary_password',
        'utm_bmcr_source'    => 'utm_bmcr_source',
        'custom_user_id'     => 'custom_user_id',
    );

    foreach ( $map as $key => $api_key ) {
        $value = adfoin_bigmarker_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    $custom_fields = adfoin_bigmarker_parse_value( $field_data, 'custom_fields', $posted_data );

    if ( '' !== $custom_fields ) {
        $decoded = json_decode( $custom_fields, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'bigmarker_invalid_custom', __( 'Custom fields JSON must be an object.', 'advanced-form-integration' ) );
        }

        $payload['custom_fields'] = wp_json_encode( $decoded );
    }

    return $payload;
}

function adfoin_bigmarker_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_bigmarker_credentials_list() {
    $credentials = adfoin_read_credentials( 'bigmarker' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_bigmarker_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'bigmarker', $cred_id );

    if ( empty( $credentials ) ) {
        return new WP_Error( 'bigmarker_missing_credentials', __( 'BigMarker credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_bigmarker_request( $url, $payload, $credentials, $record = array() ) {
    $api_key = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';

    if ( '' === $api_key ) {
        return new WP_Error( 'bigmarker_missing_api_key', __( 'BigMarker API key is missing.', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => 'PUT',
        'headers' => array(
            'API-KEY' => $api_key,
        ),
        'body'    => $payload,
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
        $message = $body ? $body : __( 'BigMarker request failed.', 'advanced-form-integration' );

        return new WP_Error( 'bigmarker_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
