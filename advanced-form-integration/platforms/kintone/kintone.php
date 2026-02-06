<?php

add_filter( 'adfoin_action_providers', 'adfoin_kintone_actions', 10, 1 );

function adfoin_kintone_actions( $actions ) {
    $actions['kintone'] = array(
        'title' => __( 'Kintone', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_kintone_settings_tab', 10, 1 );

function adfoin_kintone_settings_tab( $providers ) {
    $providers['kintone'] = __( 'Kintone', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_kintone_settings_view', 10, 1 );

function adfoin_kintone_settings_view( $current_tab ) {
    if ( 'kintone' !== $current_tab ) {
        return;
    }

    $title = __( 'Kintone', 'advanced-form-integration' );
    $key   = 'kintone';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'subdomain', 'label' => __( 'Kintone Subdomain', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'baseUrl', 'label' => __( 'Custom Base URL (optional)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'apiToken', 'label' => __( 'API Token', 'advanced-form-integration' ), 'hidden' => true ),
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
        esc_html__( 'Generate a REST API token', 'advanced-form-integration' ),
        esc_html__( 'Open your Kintone app, go to Settings → Users & Privileges → API Token.', 'advanced-form-integration' ),
        esc_html__( 'Create a token with “Add records” (and optionally “Edit records”) permissions.', 'advanced-form-integration' ),
        esc_html__( 'Copy the app ID from the URL or the App settings page.', 'advanced-form-integration' ),
        esc_html__( 'Configure AFI credentials', 'advanced-form-integration' ),
        esc_html__( 'Enter the subdomain portion of your Kintone URL (mycompany for https://mycompany.kintone.com).', 'advanced-form-integration' ),
        esc_html__( 'If you use a custom domain or regional host, provide the full base URL instead.', 'advanced-form-integration' ),
        esc_html__( 'Paste the API token and save. Repeat to add multiple apps or subdomains.', 'advanced-form-integration' ),
        esc_html__( 'AFI posts JSON to {base}/k/v1/record.json with the token supplied via the X-Cybozu-API-Token header.' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_kintone_action_fields' );

function adfoin_kintone_action_fields() {
    ?>
    <script type="text/template" id="kintone-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Kintone Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_kintone_credentials_list(); ?>
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

add_action( 'wp_ajax_adfoin_get_kintone_credentials', 'adfoin_get_kintone_credentials' );

function adfoin_get_kintone_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'kintone' ) );
}

add_action( 'wp_ajax_adfoin_save_kintone_credentials', 'adfoin_save_kintone_credentials' );

function adfoin_save_kintone_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'kintone' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'kintone', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_kintone_fields', 'adfoin_get_kintone_fields' );

function adfoin_get_kintone_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'appId', 'value' => __( 'App ID', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'recordJson', 'value' => __( 'Record JSON (optional)', 'advanced-form-integration' ), 'description' => __( 'Full JSON string for the record object. Values mapped below will merge with this.', 'advanced-form-integration' ) ),
        array( 'key' => 'CustomerName', 'value' => __( 'Example Field: CustomerName', 'advanced-form-integration' ), 'description' => __( 'Change the field code to match your Kintone field. Value becomes record[fieldCode].value.', 'advanced-form-integration' ) ),
        array( 'key' => 'Email', 'value' => __( 'Example Field: Email', 'advanced-form-integration' ) ),
        array( 'key' => 'Phone', 'value' => __( 'Example Field: Phone', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_kintone_job_queue', 'adfoin_kintone_job_queue', 10, 1 );

function adfoin_kintone_job_queue( $data ) {
    adfoin_kintone_send_record( $data['record'], $data['posted_data'] );
}

function adfoin_kintone_send_record( $record, $posted_data ) {
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
        if ( in_array( $key, array( 'credId' ), true ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    $app_id = isset( $fields['appId'] ) ? $fields['appId'] : '';

    if ( ! $app_id ) {
        return;
    }

    unset( $fields['appId'] );

    $record_json = array();

    if ( isset( $fields['recordJson'] ) ) {
        $decoded = json_decode( $fields['recordJson'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $record_json = $decoded;
        }
        unset( $fields['recordJson'] );
    }

    foreach ( $fields as $field_code => $value ) {
        $record_json[ $field_code ] = array( 'value' => $value );
    }

    if ( empty( $record_json ) ) {
        return;
    }

    $payload = array(
        'app'    => (int) $app_id,
        'record' => $record_json,
    );

    adfoin_kintone_request( 'k/v1/record.json', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_kintone_request' ) ) :
function adfoin_kintone_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'kintone', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Kintone credentials not found.', 'advanced-form-integration' ) );
    }

    $base = adfoin_kintone_get_base_url( $credentials );
    $token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( ! $base || ! $token ) {
        return new WP_Error( 'missing_auth', __( 'Kintone base URL or API token missing.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $base ) . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'        => 'application/json',
            'X-Cybozu-API-Token'  => $token,
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

function adfoin_kintone_get_base_url( $credentials ) {
    if ( ! empty( $credentials['baseUrl'] ) ) {
        return untrailingslashit( $credentials['baseUrl'] );
    }

    if ( empty( $credentials['subdomain'] ) ) {
        return '';
    }

    return 'https://' . trim( $credentials['subdomain'] ) . '.kintone.com';
}

function adfoin_kintone_credentials_list() {
    foreach ( adfoin_read_credentials( 'kintone' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
