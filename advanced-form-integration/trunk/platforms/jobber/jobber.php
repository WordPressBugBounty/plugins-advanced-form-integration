<?php

add_filter( 'adfoin_action_providers', 'adfoin_jobber_actions', 10, 1 );

function adfoin_jobber_actions( $actions ) {
    $actions['jobber'] = array(
        'title' => __( 'Jobber', 'advanced-form-integration' ),
        'tasks' => array(
            'create_job' => __( 'Create Client & Job', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_jobber_settings_tab', 10, 1 );

function adfoin_jobber_settings_tab( $providers ) {
    $providers['jobber'] = __( 'Jobber', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_jobber_settings_view', 10, 1 );

function adfoin_jobber_settings_view( $current_tab ) {
    if ( 'jobber' !== $current_tab ) {
        return;
    }

    $title = __( 'Jobber', 'advanced-form-integration' );
    $key   = 'jobber';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'    => 'accessToken',
                    'label'  => __( 'Access Token', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'accountId',
                    'label'  => __( 'Account ID', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'apiVersion',
                    'label'  => __( 'GraphQL Version (optional)', 'advanced-form-integration' ),
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
        esc_html__( 'Create a Jobber API application', 'advanced-form-integration' ),
        esc_html__( 'Log in to Jobber and open the Developer Center to generate a personal access token or OAuth client credentials.', 'advanced-form-integration' ),
        esc_html__( 'Copy the access token and account ID that Jobber associates with the token.', 'advanced-form-integration' ),
        esc_html__( 'Optionally note the latest Jobber GraphQL version (for example 2024-10-15) so your requests stay on the desired schema.', 'advanced-form-integration' ),
        esc_html__( 'Store credentials inside AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the Access Token and Account ID, then click “Save & Authenticate” to reuse them in multiple integrations.', 'advanced-form-integration' ),
        esc_html__( 'If Jobber releases a newer schema you can supply the GraphQL version; otherwise leave it blank to use Jobber’s default.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends requests to https://api.getjobber.com/api/graphql using Bearer authentication and the Jobber-Account-Id header.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Jobber [PRO] to push additional job options, custom payloads, and upload attachments.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_jobber_action_fields' );

function adfoin_jobber_action_fields() {
    ?>
    <script type="text/template" id="jobber-action-template">
        <table class="form-table" v-if="action.task == 'create_job'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Jobber Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_jobber_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need schedules, line items, or attachments?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Jobber [PRO]</a> to merge custom JSON payloads, schedule visits, and upload documents with your jobs.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_jobber_credentials', 'adfoin_get_jobber_credentials' );

function adfoin_get_jobber_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'jobber' ) );
}

add_action( 'wp_ajax_adfoin_save_jobber_credentials', 'adfoin_save_jobber_credentials' );

function adfoin_save_jobber_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'jobber' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'jobber', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_jobber_fields', 'adfoin_get_jobber_fields' );

function adfoin_get_jobber_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'client_id', 'value' => __( 'Client ID (update existing)', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'Client First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Client Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile_phone', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line1', 'value' => __( 'Street Address', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code', 'value' => __( 'Postal / Zip Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'notes', 'value' => __( 'Client Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'job_title', 'value' => __( 'Job Title', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'job_description', 'value' => __( 'Job Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'job_instructions', 'value' => __( 'Job Instructions / Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'job_status', 'value' => __( 'Job Status', 'advanced-form-integration' ) ),
        array( 'key' => 'job_start_date', 'value' => __( 'Start Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'job_start_time', 'value' => __( 'Start Time (HH:MM)', 'advanced-form-integration' ) ),
        array( 'key' => 'job_end_date', 'value' => __( 'End Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'job_end_time', 'value' => __( 'End Time (HH:MM)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_jobber_job_queue', 'adfoin_jobber_job_queue', 10, 1 );

function adfoin_jobber_job_queue( $data ) {
    adfoin_jobber_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_jobber_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_jobber_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $client_id = '';

    if ( isset( $field_data['client_id'] ) ) {
        $client_id = trim( adfoin_get_parsed_values( $field_data['client_id'], $posted_data ) );
    }

    $client_payload = adfoin_jobber_collect_client_fields( $field_data, $posted_data );

    if ( $client_id ) {
        if ( ! empty( $client_payload ) ) {
            $client_payload['id'] = $client_id;
            adfoin_jobber_mutation( 'clientUpdate', 'ClientUpdateInput', $client_payload, $record, $credentials );
        }
    } elseif ( ! empty( $client_payload ) ) {
        $create_response = adfoin_jobber_mutation( 'clientCreate', 'ClientCreateInput', $client_payload, $record, $credentials );

        if ( ! is_wp_error( $create_response ) ) {
            $client_id = adfoin_jobber_extract_nested_value( $create_response, array( 'data', 'clientCreate', 'client', 'id' ) );
        }
    }

    if ( ! $client_id ) {
        $client_id = adfoin_jobber_extract_nested_value( $client_payload, array( 'id' ) );
    }

    if ( ! $client_id ) {
        return;
    }

    $job_payload = adfoin_jobber_collect_job_fields( $field_data, $posted_data, $client_id );

    if ( empty( $job_payload ) ) {
        return;
    }

    adfoin_jobber_mutation( 'jobCreate', 'JobCreateInput', $job_payload, $record, $credentials );
}

function adfoin_jobber_credentials_list() {
    $credentials = adfoin_read_credentials( 'jobber' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_jobber_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'jobber', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'jobber_missing_credentials', __( 'Jobber credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

if ( ! function_exists( 'adfoin_jobber_mutation' ) ) :
function adfoin_jobber_mutation( $operation, $input_type, $payload, $record, $credentials ) {
    if ( empty( $payload ) ) {
        return new WP_Error( 'jobber_empty_payload', __( 'Jobber payload is empty.', 'advanced-form-integration' ) );
    }

    $query = sprintf(
        'mutation %1$s($input: %2$s!) { %1$s(input: $input) { %3$s userErrors { field message } } }',
        $operation,
        $input_type,
        'jobCreate' === $operation ? 'job { id }' : 'client { id }'
    );

    $response = adfoin_jobber_request(
        $query,
        array( 'input' => $payload ),
        $record,
        $credentials
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );

    if ( ! $body ) {
        return new WP_Error( 'jobber_empty_response', __( 'Empty response returned from Jobber.', 'advanced-form-integration' ) );
    }

    $decoded = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'jobber_invalid_json', __( 'Invalid JSON received from Jobber.', 'advanced-form-integration' ) );
    }

    if ( isset( $decoded['errors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_graphql_error', $decoded['errors'][0]['message'] );
    }

    if ( isset( $decoded[ $operation ] ) && isset( $decoded[ $operation ]['userErrors'][0]['message'] ) ) {
        // Fallback if the structure differs.
        return new WP_Error( 'jobber_user_error', $decoded[ $operation ]['userErrors'][0]['message'] );
    }

    $root = $decoded['data'][ $operation ] ?? null;

    if ( isset( $root['userErrors'][0]['message'] ) ) {
        return new WP_Error( 'jobber_user_error', $root['userErrors'][0]['message'] );
    }

    return $decoded;
}
endif;

if ( ! function_exists( 'adfoin_jobber_request' ) ) :
function adfoin_jobber_request( $query, $variables = array(), $record = array(), $credentials = array() ) {
    $access_token = isset( $credentials['accessToken'] ) ? trim( $credentials['accessToken'] ) : '';
    $account_id   = isset( $credentials['accountId'] ) ? trim( $credentials['accountId'] ) : '';
    $api_version  = isset( $credentials['apiVersion'] ) ? trim( $credentials['apiVersion'] ) : '';

    if ( '' === $access_token ) {
        return new WP_Error( 'jobber_missing_token', __( 'Jobber access token is missing.', 'advanced-form-integration' ) );
    }

    if ( '' === $account_id ) {
        return new WP_Error( 'jobber_missing_account', __( 'Jobber account ID is missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.getjobber.com/api/graphql';

    $headers = array(
        'Authorization'       => 'Bearer ' . $access_token,
        'Jobber-Account-Id'   => $account_id,
        'X-Jobber-Account-Id' => $account_id,
        'Content-Type'        => 'application/json',
        'Accept'              => 'application/json',
    );

    if ( '' !== $api_version ) {
        $headers['Jobber-GraphQL-Version']   = $api_version;
        $headers['X-Jobber-GraphQL-Version'] = $api_version;
    }

    $args = array(
        'timeout' => 30,
        'headers' => $headers,
        'body'    => wp_json_encode(
            array(
                'query'     => $query,
                'variables' => $variables,
            )
        ),
        'method'  => 'POST',
    );

    $response = wp_remote_post( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

function adfoin_jobber_collect_client_fields( $field_data, $posted_data ) {
    $map = array(
        'first_name'    => 'firstName',
        'last_name'     => 'lastName',
        'company_name'  => 'companyName',
        'notes'         => 'notes',
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

    if ( isset( $field_data['email'] ) ) {
        $email = adfoin_get_parsed_values( $field_data['email'], $posted_data );

        if ( '' !== $email && null !== $email ) {
            $payload['emails'] = array(
                array(
                    'address' => $email,
                ),
            );
        }
    }

    $phones = array();

    if ( isset( $field_data['mobile_phone'] ) ) {
        $value = adfoin_get_parsed_values( $field_data['mobile_phone'], $posted_data );

        if ( '' !== $value && null !== $value ) {
            $phones[] = array(
                'phoneType' => 'mobile',
                'number'    => $value,
            );
        }
    }

    if ( isset( $field_data['phone'] ) ) {
        $value = adfoin_get_parsed_values( $field_data['phone'], $posted_data );

        if ( '' !== $value && null !== $value ) {
            $phones[] = array(
                'phoneType' => 'home',
                'number'    => $value,
            );
        }
    }

    if ( ! empty( $phones ) ) {
        $payload['phones'] = $phones;
    }

    $address_fields = array(
        'address_line1' => 'addressLine1',
        'address_line2' => 'addressLine2',
        'city'          => 'city',
        'state'         => 'province',
        'postal_code'   => 'postalCode',
        'country'       => 'country',
    );

    $address_payload = array();

    foreach ( $address_fields as $field_key => $api_key ) {
        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        $address_payload[ $api_key ] = $value;
    }

    if ( ! empty( $address_payload ) ) {
        $payload['defaultBillingAddress'] = $address_payload;
    }

    return $payload;
}

function adfoin_jobber_collect_job_fields( $field_data, $posted_data, $client_id ) {
    $payload = array(
        'clientId' => $client_id,
    );

    $map = array(
        'job_title'        => 'title',
        'job_description'  => 'description',
        'job_instructions' => 'instructions',
        'job_status'       => 'status',
    );

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

    $start_at = adfoin_jobber_get_datetime_value( $field_data, $posted_data, 'job_start' );
    $end_at   = adfoin_jobber_get_datetime_value( $field_data, $posted_data, 'job_end' );

    if ( $start_at ) {
        $payload['startAt'] = $start_at;
    }

    if ( $end_at ) {
        $payload['endAt'] = $end_at;
    }

    if ( empty( $payload['title'] ) ) {
        $payload['title'] = __( 'New Job', 'advanced-form-integration' );
    }

    return $payload;
}

function adfoin_jobber_get_datetime_value( $field_data, $posted_data, $prefix ) {
    $date_key = $prefix . '_date';
    $time_key = $prefix . '_time';

    $date = isset( $field_data[ $date_key ] ) ? adfoin_get_parsed_values( $field_data[ $date_key ], $posted_data ) : '';
    $time = isset( $field_data[ $time_key ] ) ? adfoin_get_parsed_values( $field_data[ $time_key ], $posted_data ) : '';

    if ( '' === $date && '' === $time ) {
        return '';
    }

    $date = trim( (string) $date );
    $time = trim( (string) $time );

    if ( $date && $time ) {
        return $date . 'T' . $time . ':00Z';
    }

    if ( $date ) {
        return $date . 'T00:00:00Z';
    }

    return '';
}

function adfoin_jobber_extract_nested_value( $source, $path ) {
    if ( is_wp_error( $source ) ) {
        return '';
    }

    if ( is_array( $source ) && isset( $source[0] ) ) {
        $source = $source[0];
    }

    if ( is_array( $source ) || is_object( $source ) ) {
        foreach ( $path as $key ) {
            if ( is_array( $source ) && isset( $source[ $key ] ) ) {
                $source = $source[ $key ];
            } elseif ( is_object( $source ) && isset( $source->{$key} ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $source = $source->{$key}; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            } else {
                return '';
            }
        }
    }

    if ( is_array( $source ) || is_object( $source ) ) {
        return '';
    }

    return $source;
}

function adfoin_jobber_prepare_list_field( $value ) {
    if ( is_array( $value ) ) {
        $items = $value;
    } else {
        $items = explode( ',', (string) $value );
    }

    $items = array_map( 'trim', $items );
    $items = array_filter( $items, 'strlen' );

    return array_values( array_unique( $items ) );
}

if ( ! function_exists( 'adfoin_jobber_merge_recursive' ) ) :
function adfoin_jobber_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
            $base[ $key ] = adfoin_jobber_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }

    return $base;
}
endif;
