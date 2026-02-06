<?php

add_filter( 'adfoin_action_providers', 'adfoin_localiq_actions', 10, 1 );

function adfoin_localiq_actions( $actions ) {
    $actions['localiq'] = array(
        'title' => __( 'LocaliQ', 'advanced-form-integration' ),
        'tasks' => array(
            'submit_lead' => __( 'Submit Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_localiq_settings_tab', 10, 1 );

function adfoin_localiq_settings_tab( $providers ) {
    $providers['localiq'] = __( 'LocaliQ', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_localiq_settings_view', 10, 1 );

function adfoin_localiq_settings_view( $current_tab ) {
    if ( 'localiq' !== $current_tab ) {
        return;
    }

    $title = __( 'LocaliQ', 'advanced-form-integration' );
    $key   = 'localiq';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'accountId', 'label' => __( 'Account ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'endpoint', 'label' => __( 'API Endpoint (optional)', 'advanced-form-integration' ), 'hidden' => false ),
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
        <p>%9$s</p>',
        esc_html__( 'Request API credentials', 'advanced-form-integration' ),
        esc_html__( 'Contact your LocaliQ account team or visit the developer portal to enable API access.', 'advanced-form-integration' ),
        esc_html__( 'Create an API key with permissions to submit leads for the desired account.', 'advanced-form-integration' ),
        esc_html__( 'Note your LocaliQ Account ID and the base endpoint (default https://doc.api.localiq.com/v1).', 'advanced-form-integration' ),
        esc_html__( 'Configure AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the Account ID and API key above.', 'advanced-form-integration' ),
        esc_html__( 'Override the endpoint only if your account uses a regional base path.', 'advanced-form-integration' ),
        esc_html__( 'Save the credentials to make them available inside the automation builder.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends JSON POST requests to {endpoint}/accounts/{accountId}/leads with the API key in the Authorization header.' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_localiq_action_fields' );

function adfoin_localiq_action_fields() {
    ?>
    <script type="text/template" id="localiq-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'LocaliQ Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_localiq_credentials_list(); ?>
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

add_action( 'wp_ajax_adfoin_get_localiq_credentials', 'adfoin_get_localiq_credentials' );

function adfoin_get_localiq_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'localiq' ) );
}

add_action( 'wp_ajax_adfoin_save_localiq_credentials', 'adfoin_save_localiq_credentials' );

function adfoin_save_localiq_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'localiq' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'localiq', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_localiq_fields', 'adfoin_get_localiq_fields' );

function adfoin_get_localiq_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'postalCode', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'message', 'value' => __( 'Message', 'advanced-form-integration' ) ),
        array( 'key' => 'source', 'value' => __( 'Source', 'advanced-form-integration' ) ),
        array( 'key' => 'campaignId', 'value' => __( 'Campaign ID', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_localiq_job_queue', 'adfoin_localiq_job_queue', 10, 1 );

function adfoin_localiq_job_queue( $data ) {
    adfoin_localiq_send_lead( $data['record'], $data['posted_data'] );
}

function adfoin_localiq_send_lead( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $fields = array();
    foreach ( $data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    if ( empty( $fields['email'] ) ) {
        return;
    }

    $payload = array(
        'contact' => array(
            'firstName'  => $fields['firstName'] ?? '',
            'lastName'   => $fields['lastName'] ?? '',
            'email'      => $fields['email'],
            'phone'      => $fields['phone'] ?? '',
            'postalCode' => $fields['postalCode'] ?? '',
        ),
        'message' => $fields['message'] ?? '',
        'source'  => $fields['source'] ?? 'AFI',
    );

    if ( ! empty( $fields['campaignId'] ) ) {
        $payload['campaignId'] = $fields['campaignId'];
    }

    adfoin_localiq_request( 'leads', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_localiq_request' ) ) :
function adfoin_localiq_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'localiq', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'LocaliQ credentials not found.', 'advanced-form-integration' ) );
    }

    $account = isset( $credentials['accountId'] ) ? $credentials['accountId'] : '';
    $key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $account || ! $key ) {
        return new WP_Error( 'missing_auth', __( 'LocaliQ account ID or API key missing.', 'advanced-form-integration' ) );
    }

    $base = ! empty( $credentials['endpoint'] ) ? untrailingslashit( $credentials['endpoint'] ) : 'https://doc.api.localiq.com/v1';

    $url = sprintf(
        '%s/accounts/%s/%s',
        $base,
        rawurlencode( $account ),
        ltrim( $endpoint, '/' )
    );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $key,
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

function adfoin_localiq_credentials_list() {
    foreach ( adfoin_read_credentials( 'localiq' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
