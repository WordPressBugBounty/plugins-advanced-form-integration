<?php

add_filter( 'adfoin_action_providers', 'adfoin_fieldnation_actions', 10, 1 );

function adfoin_fieldnation_actions( $actions ) {
    $actions['fieldnation'] = array(
        'title' => __( 'Field Nation', 'advanced-form-integration' ),
        'tasks' => array(
            'create_work_order' => __( 'Create Work Order', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_fieldnation_settings_tab', 10, 1 );

function adfoin_fieldnation_settings_tab( $providers ) {
    $providers['fieldnation'] = __( 'Field Nation', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_fieldnation_settings_view', 10, 1 );

function adfoin_fieldnation_settings_view( $current_tab ) {
    if ( 'fieldnation' !== $current_tab ) {
        return;
    }

    $title = __( 'Field Nation', 'advanced-form-integration' );
    $key   = 'fieldnation';

    $arguments = wp_json_encode( array(
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
                'key'    => 'scope',
                'label'  => __( 'Scopes (optional)', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'authUrl',
                'label'  => __( 'Auth URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
                'placeholder' => 'https://api.fieldnation.com/oauth/token',
            ),
            array(
                'key'    => 'apiBase',
                'label'  => __( 'API Base URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
                'placeholder' => 'https://api.fieldnation.com',
            ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
            <li>%4$s</li>
            <li>%5$s</li>
            <li>%6$s</li>
        </ol>
        <p>%7$s</p>',
        esc_html__( 'Log in to your Field Nation account and open the Developer Portal.', 'advanced-form-integration' ),
        esc_html__( 'Create a new Client API application and copy the generated Client ID and Client Secret.', 'advanced-form-integration' ),
        esc_html__( 'Set the OAuth grant type to Client Credentials and, if prompted, allow the scopes required for creating work orders.', 'advanced-form-integration' ),
        esc_html__( 'Use https://api.fieldnation.com/oauth/token as the token endpoint unless Field Nation support provides a regional URL.', 'advanced-form-integration' ),
        esc_html__( 'Enter the credentials here and click “Save & Authenticate” to store multiple Field Nation accounts.', 'advanced-form-integration' ),
        esc_html__( 'Select the saved credentials while configuring an integration action.', 'advanced-form-integration' ),
        esc_html__( 'The integration uses the Field Nation Client API v2 endpoints to push work orders.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_fieldnation_action_fields' );

function adfoin_fieldnation_action_fields() {
    ?>
    <script type="text/template" id="fieldnation-action-template">
        <table class="form-table" v-if="action.task == 'create_work_order'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Field Nation Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_fieldnation_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <?php if ( adfoin_fs()->is_not_paying() ) : ?>
            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need advanced payloads?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Field Nation [PRO]</a> to update work orders, merge custom JSON, and assign technicians.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_fieldnation_credentials', 'adfoin_get_fieldnation_credentials' );

function adfoin_get_fieldnation_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'fieldnation' ) );
}

add_action( 'wp_ajax_adfoin_save_fieldnation_credentials', 'adfoin_save_fieldnation_credentials' );

function adfoin_save_fieldnation_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'fieldnation' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'fieldnation', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_fieldnation_fields', 'adfoin_get_fieldnation_fields' );

function adfoin_get_fieldnation_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'work_order_id', 'value' => __( 'Work Order ID (update existing)', 'advanced-form-integration' ) ),
        array( 'key' => 'title', 'value' => __( 'Title', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'instructions', 'value' => __( 'Instructions', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'category_id', 'value' => __( 'Category ID', 'advanced-form-integration' ) ),
        array( 'key' => 'subcategory_id', 'value' => __( 'Subcategory ID', 'advanced-form-integration' ) ),
        array( 'key' => 'service_type_id', 'value' => __( 'Service Type ID', 'advanced-form-integration' ) ),
        array( 'key' => 'external_id', 'value' => __( 'External ID', 'advanced-form-integration' ) ),
        array( 'key' => 'po_number', 'value' => __( 'PO Number', 'advanced-form-integration' ) ),
        array( 'key' => 'budget', 'value' => __( 'Budget / Total Pay', 'advanced-form-integration' ) ),
        array( 'key' => 'start_time', 'value' => __( 'Start Time (ISO 8601)', 'advanced-form-integration' ) ),
        array( 'key' => 'end_time', 'value' => __( 'End Time (ISO 8601)', 'advanced-form-integration' ) ),
        array( 'key' => 'due_date', 'value' => __( 'Due Date (ISO 8601)', 'advanced-form-integration' ) ),
        array( 'key' => 'address', 'value' => __( 'Street Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'travel_radius', 'value' => __( 'Travel Radius (miles)', 'advanced-form-integration' ) ),
        array( 'key' => 'custom_payload_json', 'value' => __( 'Work Order Payload (JSON merge)', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'Provide a JSON object to merge with the generated payload.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_fieldnation_job_queue', 'adfoin_fieldnation_job_queue', 10, 1 );

function adfoin_fieldnation_job_queue( $data ) {
    adfoin_fieldnation_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_fieldnation_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_fieldnation_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_fieldnation_collect_work_order_fields( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    if ( empty( $payload ) ) {
        return;
    }

    $work_order_id = '';

    if ( isset( $field_data['work_order_id'] ) ) {
        $work_order_id = trim( adfoin_get_parsed_values( $field_data['work_order_id'], $posted_data ) );
    }

    if ( $work_order_id ) {
        adfoin_fieldnation_request( 'api/v2/work_orders/' . rawurlencode( $work_order_id ), 'PATCH', $payload, $record, $credentials );
        return;
    }

    adfoin_fieldnation_request( 'api/v2/work_orders', 'POST', $payload, $record, $credentials );
}

function adfoin_fieldnation_collect_work_order_fields( $field_data, $posted_data ) {
    $title = isset( $field_data['title'] ) ? adfoin_get_parsed_values( $field_data['title'], $posted_data ) : '';
    $title = is_string( $title ) ? trim( $title ) : '';

    if ( '' === $title ) {
        return new WP_Error( 'fieldnation_missing_title', __( 'Work Order title is required.', 'advanced-form-integration' ) );
    }

    $payload = array( 'title' => $title );

    $map = array(
        'description'      => 'description',
        'instructions'     => 'instructions',
        'category_id'      => 'category_id',
        'subcategory_id'   => 'subcategory_id',
        'service_type_id'  => 'service_type_id',
        'external_id'      => 'external_id',
        'po_number'        => 'po_number',
        'start_time'       => 'start_time',
        'end_time'         => 'end_time',
        'due_date'         => 'due_date',
        'address'          => 'address',
        'city'             => 'city',
        'state'            => 'state',
        'postal_code'      => 'postal_code',
        'country'          => 'country',
        'travel_radius'    => 'travel_radius',
    );

    foreach ( $map as $key => $api_field ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }

        $payload[ $api_field ] = $value;
    }

    if ( isset( $field_data['budget'] ) ) {
        $budget = adfoin_get_parsed_values( $field_data['budget'], $posted_data );
        if ( '' !== $budget && null !== $budget ) {
            $payload['budget'] = (float) $budget;
        }
    }

    if ( isset( $field_data['custom_payload_json'] ) ) {
        $custom = adfoin_get_parsed_values( $field_data['custom_payload_json'], $posted_data );
        if ( '' !== $custom && null !== $custom ) {
            $decoded = json_decode( $custom, true );
            if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
                $payload = adfoin_fieldnation_merge_recursive( $payload, $decoded );
            }
        }
    }

    return $payload;
}

function adfoin_fieldnation_credentials_list() {
    $credentials = adfoin_read_credentials( 'fieldnation' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_fieldnation_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'fieldnation', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'fieldnation_missing_credentials', __( 'Field Nation credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

if ( ! function_exists( 'adfoin_fieldnation_request' ) ) :
function adfoin_fieldnation_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $credentials = array() ) {
    $base_url = isset( $credentials['apiBase'] ) && $credentials['apiBase']
        ? rtrim( $credentials['apiBase'], '/' )
        : 'https://api.fieldnation.com';

    $url = $base_url . '/' . ltrim( $endpoint, '/' );

    $access_token = adfoin_fieldnation_get_access_token( $credentials );

    if ( is_wp_error( $access_token ) ) {
        return $access_token;
    }

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
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
        $message = $body ? $body : __( 'Field Nation request failed.', 'advanced-form-integration' );

        return new WP_Error( 'fieldnation_http_error', $message, array( 'status' => $status ) );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_fieldnation_get_access_token' ) ) :
function adfoin_fieldnation_get_access_token( $credentials ) {
    $client_id     = isset( $credentials['clientId'] ) ? trim( $credentials['clientId'] ) : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? trim( $credentials['clientSecret'] ) : '';
    $scope         = isset( $credentials['scope'] ) ? trim( $credentials['scope'] ) : '';
    $auth_url      = isset( $credentials['authUrl'] ) && $credentials['authUrl']
        ? rtrim( $credentials['authUrl'], '/' )
        : 'https://api.fieldnation.com/oauth/token';

    if ( '' === $client_id || '' === $client_secret ) {
        return new WP_Error( 'fieldnation_missing_oauth', __( 'Field Nation client credentials are missing.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_fieldnation_token_' . md5( $client_id . '|' . $client_secret . '|' . $auth_url . '|' . $scope );
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
        $message = isset( $data['error_description'] ) ? $data['error_description'] : __( 'Unable to obtain Field Nation access token.', 'advanced-form-integration' );
        return new WP_Error( 'fieldnation_token_error', $message );
    }

    if ( ! isset( $data['access_token'] ) ) {
        return new WP_Error( 'fieldnation_token_missing', __( 'Field Nation response missing access token.', 'advanced-form-integration' ) );
    }

    $expires_in = isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;

    set_transient( $cache_key, array(
        'token'   => $data['access_token'],
        'expires' => time() + max( 60, $expires_in ),
    ), $expires_in );

    return $data['access_token'];
}
endif;

if ( ! function_exists( 'adfoin_fieldnation_merge_recursive' ) ) :
function adfoin_fieldnation_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( isset( $base[ $key ] ) && is_array( $base[ $key ] ) && is_array( $value ) ) {
            $base[ $key ] = adfoin_fieldnation_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }

    return $base;
}
endif;
