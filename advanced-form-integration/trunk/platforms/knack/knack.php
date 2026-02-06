<?php

add_filter( 'adfoin_action_providers', 'adfoin_knack_actions', 10, 1 );

function adfoin_knack_actions( $actions ) {
    $actions['knack'] = array(
        'title' => __( 'Knack', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_knack_settings_tab', 10, 1 );

function adfoin_knack_settings_tab( $providers ) {
    $providers['knack'] = __( 'Knack', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_knack_settings_view', 10, 1 );

function adfoin_knack_settings_view( $current_tab ) {
    if ( 'knack' !== $current_tab ) {
        return;
    }

    $title = __( 'Knack', 'advanced-form-integration' );
    $key   = 'knack';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'appId', 'label' => __( 'App ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                </ol>
            </li>
            <li><strong>%4$s</strong>
                <ol>
                    <li>%5$s</li>
                    <li>%6$s</li>
                </ol>
            </li>
        </ol>
        <p>%7$s</p>
        <p>%8$s</p>',
        esc_html__( 'Collect your Knack credentials', 'advanced-form-integration' ),
        esc_html__( 'Open the Knack builder, go to Settings → API & Code, and copy your App ID.', 'advanced-form-integration' ),
        esc_html__( 'Click “API Keys” to create a REST API key (or reuse an existing one).', 'advanced-form-integration' ),
        esc_html__( 'Configure AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the App ID and API key above and save.', 'advanced-form-integration' ),
        esc_html__( 'Repeat this step to store additional Knack apps if needed.', 'advanced-form-integration' ),
        esc_html__( 'AFI calls https://api.knack.com/v1/objects/{object_key}/records using the App ID and API key headers.', 'advanced-form-integration' ),
        esc_html__( 'Provide the object key (e.g., object_1) and the field codes (field_1, etc.) when mapping data.' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_knack_action_fields' );

function adfoin_knack_action_fields() {
    ?>
    <script type="text/template" id="knack-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Knack Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_knack_credentials_list(); ?>
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

add_action( 'wp_ajax_adfoin_get_knack_credentials', 'adfoin_get_knack_credentials' );

function adfoin_get_knack_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'knack' ) );
}

add_action( 'wp_ajax_adfoin_save_knack_credentials', 'adfoin_save_knack_credentials' );

function adfoin_save_knack_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'knack' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'knack', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_knack_fields', 'adfoin_get_knack_fields' );

function adfoin_get_knack_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'objectKey', 'value' => __( 'Object Key (e.g., object_1)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'recordJson', 'value' => __( 'Record JSON (optional)', 'advanced-form-integration' ), 'description' => __( 'Full JSON payload; mapped fields merge with this data.', 'advanced-form-integration' ) ),
        array( 'key' => 'field_1', 'value' => __( 'Example Field: field_1', 'advanced-form-integration' ), 'description' => __( 'Replace with your actual field codes (field_1, field_2, etc.).', 'advanced-form-integration' ) ),
        array( 'key' => 'field_2', 'value' => __( 'Example Field: field_2', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_knack_job_queue', 'adfoin_knack_job_queue', 10, 1 );

function adfoin_knack_job_queue( $data ) {
    adfoin_knack_send_record( $data['record'], $data['posted_data'] );
}

function adfoin_knack_send_record( $record, $posted_data ) {
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

    $object_key = isset( $fields['objectKey'] ) ? $fields['objectKey'] : '';

    if ( ! $object_key ) {
        return;
    }

    unset( $fields['objectKey'] );

    $record_json = array();

    if ( isset( $fields['recordJson'] ) ) {
        $decoded = json_decode( $fields['recordJson'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $record_json = $decoded;
        }
        unset( $fields['recordJson'] );
    }

    foreach ( $fields as $field_code => $value ) {
        $record_json[ $field_code ] = $value;
    }

    adfoin_knack_request( sprintf( 'objects/%s/records', $object_key ), 'POST', $record_json, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_knack_request' ) ) :
function adfoin_knack_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'knack', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Knack credentials not found.', 'advanced-form-integration' ) );
    }

    $app_id = isset( $credentials['appId'] ) ? $credentials['appId'] : '';
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $app_id || ! $api_key ) {
        return new WP_Error( 'missing_auth', __( 'Knack App ID or API key missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.knack.com/v1/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'     => 'application/json',
            'X-Knack-Application-Id' => $app_id,
            'X-Knack-REST-API-Key'   => $api_key,
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

function adfoin_knack_credentials_list() {
    foreach ( adfoin_read_credentials( 'knack' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
