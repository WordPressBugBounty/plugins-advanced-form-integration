<?php

add_filter( 'adfoin_action_providers', 'adfoin_servicetitan_actions', 10, 1 );

function adfoin_servicetitan_actions( $actions ) {
    $actions['servicetitan'] = array(
        'title' => __( 'ServiceTitan', 'advanced-form-integration' ),
        'tasks' => array(
            'create_job' => __( 'Create Customer & Job', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_servicetitan_settings_tab', 10, 1 );

function adfoin_servicetitan_settings_tab( $providers ) {
    $providers['servicetitan'] = __( 'ServiceTitan', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_servicetitan_settings_view', 10, 1 );

function adfoin_servicetitan_settings_view( $current_tab ) {
    if ( 'servicetitan' !== $current_tab ) {
        return;
    }

    $title = __( 'ServiceTitan', 'advanced-form-integration' );
    $key   = 'servicetitan';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
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
                    'key'    => 'tenantId',
                    'label'  => __( 'Tenant ID', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'scope',
                    'label'  => __( 'OAuth Scope (optional)', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'apiBase',
                    'label'  => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'authUrl',
                    'label'  => __( 'Auth URL (optional)', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
            ),
        )
    );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                </ol>
            </li>
            <li><strong>%5$s</strong>
                <ol>
                    <li>%6$s</li>
                    <li>%7$s</li>
                </ol>
            </li>
        </ol>
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Create a ServiceTitan API application', 'advanced-form-integration' ),
        esc_html__( 'Visit developers.servicetitan.com, create an integration, and enable the Customer and Job scopes.', 'advanced-form-integration' ),
        esc_html__( 'Copy the Client ID, Client Secret, and your Tenant ID.', 'advanced-form-integration' ),
        esc_html__( 'Optional: note the scope string (for example offline_access servicetitan) and regional API/auth endpoints if your tenant uses them.', 'advanced-form-integration' ),
        esc_html__( 'Connect the credentials to AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the Client ID, Client Secret, and Tenant ID. Provide Scope or override URLs only if ServiceTitan instructed you to.', 'advanced-form-integration' ),
        esc_html__( 'Click “Save & Authenticate” to store multiple accounts and reuse them when mapping actions.', 'advanced-form-integration' ),
        esc_html__( 'Requests default to https://api.servicetitan.io/v2/tenant/{tenantId}/ and obtain tokens from https://auth.servicetitan.io/oauth/token using the client credentials flow.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to ServiceTitan [PRO] to merge advanced payloads, assign technicians, and update existing jobs.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_servicetitan_action_fields' );

function adfoin_servicetitan_action_fields() {
    ?>
    <script type="text/template" id="servicetitan-action-template">
        <table class="form-table" v-if="action.task == 'create_job'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'ServiceTitan Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_servicetitan_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need to assign technicians or custom JSON?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">ServiceTitan [PRO]</a> to update existing customers, attach tags, merge custom payloads, and assign technicians to jobs.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_servicetitan_credentials', 'adfoin_get_servicetitan_credentials' );

function adfoin_get_servicetitan_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'servicetitan' ) );
}

add_action( 'wp_ajax_adfoin_save_servicetitan_credentials', 'adfoin_save_servicetitan_credentials' );

function adfoin_save_servicetitan_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'servicetitan' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'servicetitan', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_servicetitan_fields', 'adfoin_get_servicetitan_fields' );

function adfoin_get_servicetitan_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'customer_id', 'value' => __( 'Customer ID (update existing)', 'advanced-form-integration' ) ),
        array( 'key' => 'customer_type', 'value' => __( 'Customer Type (Residential/Commercial)', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'Customer First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Customer Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Customer Email', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile_phone', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'primary_phone', 'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'service_address_line1', 'value' => __( 'Service Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'service_address_line2', 'value' => __( 'Service Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'service_city', 'value' => __( 'Service City', 'advanced-form-integration' ) ),
        array( 'key' => 'service_state', 'value' => __( 'Service State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'service_postal', 'value' => __( 'Service Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'service_country', 'value' => __( 'Service Country', 'advanced-form-integration' ) ),
        array( 'key' => 'customer_notes', 'value' => __( 'Customer Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'job_summary', 'value' => __( 'Job Summary', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'job_description', 'value' => __( 'Job Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'job_type_id', 'value' => __( 'Job Type ID', 'advanced-form-integration' ) ),
        array( 'key' => 'business_unit_id', 'value' => __( 'Business Unit ID', 'advanced-form-integration' ) ),
        array( 'key' => 'campaign_id', 'value' => __( 'Campaign ID', 'advanced-form-integration' ) ),
        array( 'key' => 'priority_id', 'value' => __( 'Priority ID', 'advanced-form-integration' ) ),
        array( 'key' => 'scheduled_date', 'value' => __( 'Scheduled Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'scheduled_time', 'value' => __( 'Scheduled Time (HH:MM)', 'advanced-form-integration' ) ),
        array( 'key' => 'scheduled_end_time', 'value' => __( 'Scheduled End Time (HH:MM)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_servicetitan_job_queue', 'adfoin_servicetitan_job_queue', 10, 1 );

function adfoin_servicetitan_job_queue( $data ) {
    adfoin_servicetitan_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_servicetitan_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_servicetitan_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $customer_id = '';

    if ( isset( $field_data['customer_id'] ) ) {
        $customer_id = trim( adfoin_get_parsed_values( $field_data['customer_id'], $posted_data ) );
    }

    $customer_payload = adfoin_servicetitan_collect_customer_fields( $field_data, $posted_data );

    if ( $customer_id ) {
        if ( ! empty( $customer_payload ) ) {
            adfoin_servicetitan_request(
                'customers/' . $customer_id,
                'PUT',
                $customer_payload,
                $record,
                $credentials
            );
        }
    } elseif ( ! empty( $customer_payload ) ) {
        $create_response = adfoin_servicetitan_request(
            'customers',
            'POST',
            $customer_payload,
            $record,
            $credentials
        );

        if ( ! is_wp_error( $create_response ) ) {
            $customer_id = adfoin_servicetitan_extract_value( $create_response, array( 'id' ) );
        }
    }

    if ( ! $customer_id ) {
        $customer_id = adfoin_servicetitan_extract_value( $customer_payload, array( 'id' ) );
    }

    if ( ! $customer_id ) {
        return;
    }

    $job_payload = adfoin_servicetitan_collect_job_fields( $field_data, $posted_data, $customer_id );

    if ( empty( $job_payload ) ) {
        return;
    }

    adfoin_servicetitan_request(
        'jobs',
        'POST',
        $job_payload,
        $record,
        $credentials
    );
}

function adfoin_servicetitan_credentials_list() {
    $credentials = adfoin_read_credentials( 'servicetitan' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_servicetitan_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'servicetitan', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'servicetitan_missing_credentials', __( 'ServiceTitan credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

if ( ! function_exists( 'adfoin_servicetitan_request' ) ) :
function adfoin_servicetitan_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $tenant_id  = isset( $credentials['tenantId'] ) ? trim( $credentials['tenantId'] ) : '';
    $base_url   = isset( $credentials['apiBase'] ) && $credentials['apiBase']
        ? rtrim( $credentials['apiBase'], '/' )
        : 'https://api.servicetitan.io';

    if ( '' === $tenant_id ) {
        return new WP_Error( 'servicetitan_missing_tenant', __( 'ServiceTitan tenant ID is missing.', 'advanced-form-integration' ) );
    }

    $access_token = adfoin_servicetitan_get_access_token( $credentials );

    if ( is_wp_error( $access_token ) ) {
        return $access_token;
    }

    $url = $base_url . '/v2/tenant/' . rawurlencode( $tenant_id ) . '/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status >= 400 ) {
        $details = wp_remote_retrieve_body( $response );
        return new WP_Error( 'servicetitan_http_error', $details ? $details : __( 'ServiceTitan request failed.', 'advanced-form-integration' ) );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_servicetitan_get_access_token' ) ) :
function adfoin_servicetitan_get_access_token( $credentials ) {
    $client_id     = isset( $credentials['clientId'] ) ? trim( $credentials['clientId'] ) : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? trim( $credentials['clientSecret'] ) : '';
    $scope         = isset( $credentials['scope'] ) ? trim( $credentials['scope'] ) : '';
    $auth_url      = isset( $credentials['authUrl'] ) && $credentials['authUrl']
        ? rtrim( $credentials['authUrl'], '/' )
        : 'https://auth.servicetitan.io/oauth/token';

    if ( '' === $client_id || '' === $client_secret ) {
        return new WP_Error( 'servicetitan_missing_oauth', __( 'ServiceTitan client credentials are missing.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_servicetitan_token_' . md5( $client_id . '|' . $client_secret . '|' . $auth_url . '|' . $scope );
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && isset( $cached['token'], $cached['expires'] ) && $cached['expires'] > time() + 60 ) {
        return $cached['token'];
    }

    $body = array(
        'grant_type'    => 'client_credentials',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
    );

    if ( '' !== $scope ) {
        $body['scope'] = $scope;
    }

    $response = wp_remote_post(
        $auth_url,
        array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body'    => http_build_query( $body, '', '&' ),
            'timeout' => 20,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );
    $data   = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status >= 400 ) {
        $message = isset( $data['error_description'] ) ? $data['error_description'] : __( 'Unable to obtain ServiceTitan access token.', 'advanced-form-integration' );
        return new WP_Error( 'servicetitan_token_error', $message );
    }

    if ( ! isset( $data['access_token'] ) ) {
        return new WP_Error( 'servicetitan_token_missing', __( 'ServiceTitan response missing access token.', 'advanced-form-integration' ) );
    }

    $expires_in = isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;

    set_transient(
        $cache_key,
        array(
            'token'   => $data['access_token'],
            'expires' => time() + $expires_in,
        ),
        $expires_in
    );

    return $data['access_token'];
}
endif;

function adfoin_servicetitan_collect_customer_fields( $field_data, $posted_data ) {
    $map = array(
        'first_name'        => 'firstName',
        'last_name'         => 'lastName',
        'customer_type'     => 'type',
        'email'             => 'email',
        'customer_notes'    => 'notes',
    );

    $payload = array();

    foreach ( $map as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    if ( empty( $payload['type'] ) ) {
        $payload['type'] = 'Residential';
    }

    $phones = array();

    if ( isset( $field_data['mobile_phone'] ) ) {
        $mobile = adfoin_get_parsed_values( $field_data['mobile_phone'], $posted_data );

        if ( '' !== $mobile && null !== $mobile ) {
            $phones[] = array(
                'type'   => 'Mobile',
                'number' => $mobile,
            );
        }
    }

    if ( isset( $field_data['primary_phone'] ) ) {
        $primary = adfoin_get_parsed_values( $field_data['primary_phone'], $posted_data );

        if ( '' !== $primary && null !== $primary ) {
            $phones[] = array(
                'type'   => 'Primary',
                'number' => $primary,
            );
        }
    }

    if ( ! empty( $phones ) ) {
        $payload['phones'] = $phones;
    }

    $service_address = adfoin_servicetitan_collect_address(
        $field_data,
        $posted_data,
        'service_',
        'Service'
    );

    if ( ! empty( $service_address ) ) {
        $payload['addresses'] = array( $service_address );
    }

    return $payload;
}

function adfoin_servicetitan_collect_address( $field_data, $posted_data, $prefix, $type ) {
    $map = array(
        $prefix . 'address_line1' => 'street',
        $prefix . 'address_line2' => 'street2',
        $prefix . 'city'          => 'city',
        $prefix . 'state'         => 'state',
        $prefix . 'postal'        => 'postalCode',
        $prefix . 'country'       => 'country',
    );

    $address = array(
        'type' => $type,
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        $address[ $api_key ] = $value;
    }

    return count( $address ) > 1 ? $address : array();
}

function adfoin_servicetitan_collect_job_fields( $field_data, $posted_data, $customer_id ) {
    $payload = array(
        'customerId' => (int) $customer_id,
    );

    $map = array(
        'job_summary'     => 'summary',
        'job_description' => 'description',
        'job_type_id'     => 'typeId',
        'business_unit_id'=> 'businessUnitId',
        'campaign_id'     => 'campaignId',
        'priority_id'     => 'priorityId',
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        if ( in_array( $api_key, array( 'typeId', 'businessUnitId', 'campaignId', 'priorityId' ), true ) ) {
            $payload[ $api_key ] = (int) $value;
        } else {
            $payload[ $api_key ] = $value;
        }
    }

    if ( empty( $payload['summary'] ) ) {
        $payload['summary'] = __( 'New Job', 'advanced-form-integration' );
    }

    $start_iso = adfoin_servicetitan_format_datetime(
        isset( $field_data['scheduled_date'] ) ? adfoin_get_parsed_values( $field_data['scheduled_date'], $posted_data ) : '',
        isset( $field_data['scheduled_time'] ) ? adfoin_get_parsed_values( $field_data['scheduled_time'], $posted_data ) : ''
    );

    if ( $start_iso ) {
        $payload['scheduledArrivalStart'] = $start_iso;
    }

    $end_iso = adfoin_servicetitan_format_datetime(
        isset( $field_data['scheduled_date'] ) ? adfoin_get_parsed_values( $field_data['scheduled_date'], $posted_data ) : '',
        isset( $field_data['scheduled_end_time'] ) ? adfoin_get_parsed_values( $field_data['scheduled_end_time'], $posted_data ) : ''
    );

    if ( $end_iso ) {
        $payload['scheduledArrivalEnd'] = $end_iso;
    }

    return $payload;
}

function adfoin_servicetitan_format_datetime( $date, $time ) {
    $date = trim( (string) $date );
    $time = trim( (string) $time );

    if ( '' === $date && '' === $time ) {
        return '';
    }

    if ( '' === $time ) {
        $time = '00:00';
    }

    return $date . 'T' . $time . ':00Z';
}

function adfoin_servicetitan_extract_value( $response, $path ) {
    if ( is_wp_error( $response ) ) {
        return '';
    }

    $body = is_array( $response ) ? wp_remote_retrieve_body( $response ) : $response;

    if ( empty( $body ) ) {
        return '';
    }

    $decoded = json_decode( $body, true );

    if ( ! is_array( $decoded ) ) {
        return '';
    }

    $current = $decoded;

    foreach ( $path as $segment ) {
        if ( is_array( $current ) && isset( $current[ $segment ] ) ) {
            $current = $current[ $segment ];
        } else {
            return '';
        }
    }

    if ( is_array( $current ) ) {
        return '';
    }

    return $current;
}

if ( ! function_exists( 'adfoin_servicetitan_prepare_list_field' ) ) :
function adfoin_servicetitan_prepare_list_field( $value ) {
    if ( is_array( $value ) ) {
        $list = $value;
    } else {
        $list = explode( ',', (string) $value );
    }

    $list = array_map( 'trim', $list );
    $list = array_filter( $list, 'strlen' );

    return array_values( array_unique( $list ) );
}
endif;

if ( ! function_exists( 'adfoin_servicetitan_merge_recursive' ) ) :
function adfoin_servicetitan_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
            $base[ $key ] = adfoin_servicetitan_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }

    return $base;
}
endif;
