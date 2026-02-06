<?php

add_filter( 'adfoin_action_providers', 'adfoin_servicem8_actions', 10, 1 );

function adfoin_servicem8_actions( $actions ) {
    $actions['servicem8'] = array(
        'title' => __( 'ServiceM8', 'advanced-form-integration' ),
        'tasks' => array(
            'create_job' => __( 'Create Job', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_servicem8_settings_tab', 10, 1 );

function adfoin_servicem8_settings_tab( $providers ) {
    $providers['servicem8'] = __( 'ServiceM8', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_servicem8_settings_view', 10, 1 );

function adfoin_servicem8_settings_view( $current_tab ) {
    if ( 'servicem8' !== $current_tab ) {
        return;
    }

    $title = __( 'ServiceM8', 'advanced-form-integration' );
    $key   = 'servicem8';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'username',
                'label'  => __( 'API Username / Token', 'advanced-form-integration' ),
                'hidden' => false,
            ),
            array(
                'key'    => 'password',
                'label'  => __( 'Password / API Secret (optional)', 'advanced-form-integration' ),
                'hidden' => true,
            ),
        ),
    ) );

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
                    <li>%8$s</li>
                </ol>
            </li>
        </ol>
        <p>%9$s</p>
        <p>%10$s</p>',
        esc_html__( 'Generate API credentials', 'advanced-form-integration' ),
        esc_html__( 'Sign in to ServiceM8 and open Settings → ServiceM8 API.', 'advanced-form-integration' ),
        esc_html__( 'Create an API key (or note your ServiceM8 username and password).', 'advanced-form-integration' ),
        esc_html__( 'Copy the API key and, if supplied, the API secret. ServiceM8 only shows them once.', 'advanced-form-integration' ),
        esc_html__( 'Connect the credentials to AFI', 'advanced-form-integration' ),
        esc_html__( 'Enter the key in “API Username / Token”. If ServiceM8 provided a secret, paste it in “Password / API Secret”; otherwise leave it blank.', 'advanced-form-integration' ),
        esc_html__( 'Click “Save & Authenticate” to store multiple ServiceM8 accounts as needed.', 'advanced-form-integration' ),
        esc_html__( 'Pick the saved credentials when configuring an action and map the fields you want to push.', 'advanced-form-integration' ),
        esc_html__( 'Requests are sent with HTTP Basic authentication over https://api.servicem8.com/api_1.0/.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to ServiceM8 [PRO] to update existing clients, create jobs, and send custom payloads.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_servicem8_action_fields' );

function adfoin_servicem8_action_fields() {
    ?>
    <script type="text/template" id="servicem8-action-template">
        <table class="form-table">
            <tr v-if="action.task == 'create_job'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_job'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'ServiceM8 Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_servicem8_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate" v-if="action.task == 'create_job'">
                <th scope="row"><?php esc_html_e( 'Need more job options?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">ServiceM8 [PRO]</a> to update existing clients, add scheduling details, and include attachments.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_servicem8_credentials', 'adfoin_get_servicem8_credentials' );

function adfoin_get_servicem8_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'servicem8' ) );
}

add_action( 'wp_ajax_adfoin_save_servicem8_credentials', 'adfoin_save_servicem8_credentials' );

function adfoin_save_servicem8_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'servicem8' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'servicem8', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_servicem8_fields', 'adfoin_get_servicem8_fields' );

function adfoin_get_servicem8_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'client_uuid', 'value' => __( 'Client UUID (update existing)', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile', 'value' => __( 'Mobile', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_address', 'value' => __( 'Postal Address', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_city', 'value' => __( 'Postal City / Suburb', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_state', 'value' => __( 'Postal State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_postcode', 'value' => __( 'Postal Postcode', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_country', 'value' => __( 'Postal Country', 'advanced-form-integration' ) ),
        array( 'key' => 'client_notes', 'value' => __( 'Client Notes', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'client_tags', 'value' => __( 'Client Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'title', 'value' => __( 'Job Title', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'description', 'value' => __( 'Job Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'address', 'value' => __( 'Job Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'Job City / Suburb', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'Job State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'postcode', 'value' => __( 'Job Postcode', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Job Country', 'advanced-form-integration' ) ),
        array( 'key' => 'status', 'value' => __( 'Job Status', 'advanced-form-integration' ) ),
        array( 'key' => 'scheduled_date', 'value' => __( 'Scheduled Date (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'scheduled_time', 'value' => __( 'Scheduled Start Time (HH:MM)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_servicem8_job_queue', 'adfoin_servicem8_job_queue', 10, 1 );

function adfoin_servicem8_job_queue( $data ) {
    adfoin_servicem8_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_servicem8_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_servicem8_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $client_uuid = '';

    if ( isset( $field_data['client_uuid'] ) ) {
        $client_uuid = trim( adfoin_get_parsed_values( $field_data['client_uuid'], $posted_data ) );
    }

    $client_payload = adfoin_servicem8_collect_client_fields( $field_data, $posted_data );

    if ( $client_uuid ) {
        if ( ! empty( $client_payload ) ) {
            $client_payload['uuid'] = $client_uuid;
            adfoin_servicem8_request( 'client.json', 'PUT', $client_payload, $record, $credentials );
        }
    } elseif ( ! empty( $client_payload ) ) {
        $client_response = adfoin_servicem8_request( 'client.json', 'POST', $client_payload, $record, $credentials );

        if ( ! is_wp_error( $client_response ) ) {
            $client_uuid = adfoin_servicem8_extract_uuid_from_response( $client_response );
        }
    }

    if ( ! $client_uuid ) {
        $client_uuid = isset( $client_payload['uuid'] ) ? $client_payload['uuid'] : $client_uuid;
    }

    if ( ! $client_uuid ) {
        return;
    }

    $job_payload = adfoin_servicem8_collect_job_fields(
        $field_data,
        $posted_data,
        array( 'title', 'description', 'address', 'city', 'state', 'postcode', 'country', 'status', 'scheduled_date', 'scheduled_time' )
    );

    if ( empty( $job_payload ) ) {
        return;
    }

    $job_payload['client_uuid'] = $client_uuid;

    adfoin_servicem8_request( 'job.json', 'POST', $job_payload, $record, $credentials );
}

function adfoin_servicem8_credentials_list() {
    $credentials = adfoin_read_credentials( 'servicem8' );

    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_servicem8_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'servicem8', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'ServiceM8 credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

if ( ! function_exists( 'adfoin_servicem8_request' ) ) :
function adfoin_servicem8_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = array() ) {
    $username = isset( $credentials['username'] ) ? trim( $credentials['username'] ) : '';
    $password = isset( $credentials['password'] ) ? trim( $credentials['password'] ) : '';

    if ( '' === $username ) {
        return new WP_Error( 'missing_username', __( 'ServiceM8 API username/token is missing.', 'advanced-form-integration' ) );
    }

    $base_url = 'https://api.servicem8.com/api_1.0/';
    $url      = $base_url . ltrim( $endpoint, '/' );

    $headers = array(
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
        'Authorization'=> 'Basic ' . base64_encode( $username . ':' . $password ),
    );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => $headers,
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

function adfoin_servicem8_collect_client_fields( $field_data, $posted_data ) {
    $map = array(
        'company_name'    => 'company_name',
        'first_name'      => 'first_name',
        'last_name'       => 'last_name',
        'email'           => 'email',
        'mobile'          => 'mobile',
        'phone'           => 'phone',
        'postal_address'  => 'postal_address',
        'postal_city'     => 'postal_city',
        'postal_state'    => 'postal_state',
        'postal_postcode' => 'postal_postcode',
        'postal_country'  => 'postal_country',
        'billing_address' => 'billing_address',
        'billing_city'    => 'billing_city',
        'billing_state'   => 'billing_state',
        'billing_postcode'=> 'billing_postcode',
        'billing_country' => 'billing_country',
        'client_notes'    => 'notes',
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

    if ( isset( $field_data['client_tags'] ) ) {
        $tags = adfoin_get_parsed_values( $field_data['client_tags'], $posted_data );

        if ( '' !== $tags && null !== $tags ) {
            $payload['tags'] = adfoin_servicem8_prepare_list_field( $tags );
        }
    }

    return $payload;
}

function adfoin_servicem8_collect_job_fields( $field_data, $posted_data, $allowed_keys = array() ) {
    $map = array(
        'title'          => 'title',
        'description'    => 'description',
        'address'        => 'address',
        'city'           => 'city',
        'state'          => 'state',
        'postcode'       => 'postcode',
        'country'        => 'country',
        'status'         => 'status',
        'priority'       => 'priority',
        'scheduled_date' => 'scheduled_date',
        'scheduled_time' => 'scheduled_time',
        'scheduled_end'  => 'scheduled_end',
        'staff_uuid'     => 'staff_uuid',
        'category_uuid'  => 'category_uuid',
        'job_notes'      => 'notes',
    );

    $payload = array();

    foreach ( $map as $field_key => $api_key ) {
        if ( $allowed_keys && ! in_array( $field_key, $allowed_keys, true ) ) {
            continue;
        }

        if ( ! isset( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value || null === $value ) {
            continue;
        }

        $payload[ $api_key ] = $value;
    }

    if ( ( ! $allowed_keys || in_array( 'job_tags', $allowed_keys, true ) ) && isset( $field_data['job_tags'] ) ) {
        $tags = adfoin_get_parsed_values( $field_data['job_tags'], $posted_data );

        if ( '' !== $tags && null !== $tags ) {
            $payload['tags'] = adfoin_servicem8_prepare_list_field( $tags );
        }
    }

    return $payload;
}

function adfoin_servicem8_extract_uuid_from_response( $response ) {
    if ( is_wp_error( $response ) ) {
        return '';
    }

    $body = wp_remote_retrieve_body( $response );

    if ( ! $body ) {
        return '';
    }

    $decoded = json_decode( $body, true );

    if ( isset( $decoded['uuid'] ) ) {
        return $decoded['uuid'];
    }

    if ( is_array( $decoded ) ) {
        if ( isset( $decoded[0]['uuid'] ) ) {
            return $decoded[0]['uuid'];
        }

        if ( isset( $decoded['data'] ) && isset( $decoded['data']['uuid'] ) ) {
            return $decoded['data']['uuid'];
        }
    }

    return '';
}

function adfoin_servicem8_prepare_list_field( $value ) {
    if ( is_array( $value ) ) {
        $list = array_map( 'trim', $value );
    } else {
        $list = array_map( 'trim', explode( ',', (string) $value ) );
    }

    $list = array_filter( $list, 'strlen' );

    return array_values( array_unique( $list ) );
}
