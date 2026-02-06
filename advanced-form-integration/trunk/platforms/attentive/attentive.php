<?php

add_filter( 'adfoin_action_providers', 'adfoin_attentive_actions', 10, 1 );

function adfoin_attentive_actions( $actions ) {
    $actions['attentive'] = array(
        'title' => __( 'Attentive', 'advanced-form-integration' ),
        'tasks' => array(
            'create_subscriber' => __( 'Create / Update Subscriber', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_attentive_settings_tab', 10, 1 );

function adfoin_attentive_settings_tab( $providers ) {
    $providers['attentive'] = __( 'Attentive', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_attentive_settings_view', 10, 1 );

function adfoin_attentive_settings_view( $current_tab ) {
    if ( 'attentive' !== $current_tab ) {
        return;
    }

    $title = __( 'Attentive', 'advanced-form-integration' );
    $key   = 'attentive';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'apiKey',
                'label'  => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'baseUrl',
                'label'  => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
                'placeholder' => 'https://api.attentivemobile.com',
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
        esc_html__( 'Log in to Attentive and open the API Keys section of the Developer Portal.', 'advanced-form-integration' ),
        esc_html__( 'Generate a Server-to-Server API key and copy the value. Keep it secure.', 'advanced-form-integration' ),
        esc_html__( 'Paste the key here, optionally override the API base URL if Attentive provides a regional endpoint, and click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential while configuring Attentive actions to send subscriber data from your forms.', 'advanced-form-integration' ),
        esc_html__( 'AFI calls the Attentive Subscribers API (v1) with your API key over HTTPS.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_attentive_action_fields' );

function adfoin_attentive_action_fields() {
    ?>
    <script type="text/template" id="attentive-action-template">
        <table class="form-table" v-if="action.task == 'create_subscriber'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Attentive Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_attentive_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need advanced segmentation?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Use the JSON fields for custom attributes, subscriptions, and metadata so the payload matches Attentive’s schema.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_attentive_credentials', 'adfoin_get_attentive_credentials' );

function adfoin_get_attentive_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'attentive' ) );
}

add_action( 'wp_ajax_adfoin_save_attentive_credentials', 'adfoin_save_attentive_credentials' );

function adfoin_save_attentive_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'attentive' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'attentive', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_attentive_fields', 'adfoin_get_attentive_fields' );

function adfoin_get_attentive_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'phone', 'value' => __( 'Phone (E.164, required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'external_id', 'value' => __( 'External ID', 'advanced-form-integration' ) ),
        array( 'key' => 'subscription_status', 'value' => __( 'Subscription Status (SUBSCRIBED/UNSUBSCRIBED)', 'advanced-form-integration' ) ),
        array( 'key' => 'subscription_channel', 'value' => __( 'Subscription Channel (SMS/EMAIL)', 'advanced-form-integration' ) ),
        array( 'key' => 'subscription_date', 'value' => __( 'Subscription Date (ISO8601)', 'advanced-form-integration' ) ),
        array( 'key' => 'list_ids', 'value' => __( 'List IDs (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'attributes_json', 'value' => __( 'Custom Attributes (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Example: {"favoriteStore":"NYC"}', 'advanced-form-integration' ) ),
        array( 'key' => 'subscriptions_json', 'value' => __( 'Subscriptions (JSON array override)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Set if you need full control. Example: [{"channel":"SMS","status":"SUBSCRIBED"}]', 'advanced-form-integration' ) ),
        array( 'key' => 'metadata_json', 'value' => __( 'Metadata (JSON object)', 'advanced-form-integration' ), 'type' => 'textarea' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_attentive_job_queue', 'adfoin_attentive_job_queue', 10, 1 );

function adfoin_attentive_job_queue( $data ) {
    adfoin_attentive_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_attentive_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_attentive_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_attentive_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_attentive_request( 'subscribers', 'POST', $payload, $record, $credentials );
}

function adfoin_attentive_collect_payload( $field_data, $posted_data ) {
    $phone = adfoin_attentive_parse_value( $field_data, 'phone', $posted_data );

    if ( '' === $phone ) {
        return new WP_Error( 'attentive_missing_phone', __( 'Attentive requires a phone number in E.164 format.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'phone' => $phone,
    );

    $map = array(
        'email'       => 'email',
        'first_name'  => 'firstName',
        'last_name'   => 'lastName',
        'external_id' => 'externalId',
    );

    foreach ( $map as $key => $api_field ) {
        $value = adfoin_attentive_parse_value( $field_data, $key, $posted_data );

        if ( '' === $value ) {
            continue;
        }

        $payload[ $api_field ] = $value;
    }

    $attributes = adfoin_attentive_parse_value( $field_data, 'attributes_json', $posted_data );

    if ( '' !== $attributes ) {
        $decoded = json_decode( $attributes, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'attentive_invalid_attributes', __( 'Custom attributes JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['attributes'] = $decoded;
    }

    $metadata = adfoin_attentive_parse_value( $field_data, 'metadata_json', $posted_data );

    if ( '' !== $metadata ) {
        $decoded = json_decode( $metadata, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'attentive_invalid_metadata', __( 'Metadata JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['metadata'] = $decoded;
    }

    $subscriptions_override = adfoin_attentive_parse_value( $field_data, 'subscriptions_json', $posted_data );

    if ( '' !== $subscriptions_override ) {
        $decoded = json_decode( $subscriptions_override, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'attentive_invalid_subscriptions', __( 'Subscriptions JSON is invalid.', 'advanced-form-integration' ) );
        }

        $payload['subscriptions'] = $decoded;
    } else {
        $status  = adfoin_attentive_parse_value( $field_data, 'subscription_status', $posted_data );
        $channel = adfoin_attentive_parse_value( $field_data, 'subscription_channel', $posted_data );

        if ( '' !== $status ) {
            $subscription = array(
                'status'  => strtoupper( $status ),
            );

            $subscription['channel'] = $channel ? strtoupper( $channel ) : 'SMS';

            $date = adfoin_attentive_parse_value( $field_data, 'subscription_date', $posted_data );

            if ( '' !== $date ) {
                $subscription['transactionDate'] = $date;
            }

            $payload['subscriptions'] = array( $subscription );
        }
    }

    $list_ids = adfoin_attentive_parse_value( $field_data, 'list_ids', $posted_data );

    if ( '' !== $list_ids ) {
        $payload['listIds'] = array_filter( array_map( 'trim', explode( ',', $list_ids ) ) );
    }

    return $payload;
}

function adfoin_attentive_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_attentive_credentials_list() {
    $credentials = adfoin_read_credentials( 'attentive' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_attentive_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'attentive', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'attentive_missing_credentials', __( 'Attentive credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_attentive_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $api_key = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';
    $base    = isset( $credentials['baseUrl'] ) && $credentials['baseUrl']
        ? rtrim( $credentials['baseUrl'], '/' )
        : 'https://api.attentivemobile.com';

    if ( '' === $api_key ) {
        return new WP_Error( 'attentive_missing_api_key', __( 'Attentive API key is missing.', 'advanced-form-integration' ) );
    }

    $url = $base . '/v1/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
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
        $message = $body ? $body : __( 'Attentive request failed.', 'advanced-form-integration' );

        return new WP_Error( 'attentive_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
