<?php

add_filter( 'adfoin_action_providers', 'adfoin_softr_actions', 10, 1 );

function adfoin_softr_actions( $actions ) {
    $actions['softr'] = array(
        'title' => __( 'Softr', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Record (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_softr_settings_tab', 10, 1 );

function adfoin_softr_settings_tab( $providers ) {
    $providers['softr'] = __( 'Softr', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_softr_settings_view', 10, 1 );

function adfoin_softr_settings_view( $current_tab ) {
    if ( 'softr' !== $current_tab ) {
        return;
    }

    $title = __( 'Softr', 'advanced-form-integration' );
    $key   = 'softr';

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
        esc_html__( 'Collect credentials', 'advanced-form-integration' ),
        esc_html__( 'Open Softr Studio → Settings → API & Embed to copy your App ID and REST API key.', 'advanced-form-integration' ),
        esc_html__( 'Ensure the data source (Airtable/Postgres) collection you wish to update is exposed via the REST API.', 'advanced-form-integration' ),
        esc_html__( 'Configure AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the App ID and API key above and save. Repeat to store additional apps.', 'advanced-form-integration' ),
        esc_html__( 'When mapping fields, provide the collection ID (e.g., collection_1) and field keys used in your Softr data source.', 'advanced-form-integration' ),
        esc_html__( 'AFI calls https://studio-api.softr.io/v1/api/apps/{appId}/collections/{collection}/records guarded by Softr-Api-Key and Softr-Api-Id headers.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Softr [PRO] to push nested JSON, update existing records, and trigger list automations.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_softr_action_fields' );

function adfoin_softr_action_fields() {
    ?>
    <script type="text/template" id="softr-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Softr Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_softr_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need nested data?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Softr [PRO]</a> to send custom JSON blocks, update records, and add meta tags.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_softr_credentials', 'adfoin_get_softr_credentials' );

function adfoin_get_softr_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'softr' ) );
}

add_action( 'wp_ajax_adfoin_save_softr_credentials', 'adfoin_save_softr_credentials' );

function adfoin_save_softr_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'softr' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'softr', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_softr_fields', 'adfoin_get_softr_fields' );

function adfoin_get_softr_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'collection', 'value' => __( 'Collection ID (e.g., collection_1)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'field_key_1', 'value' => __( 'Field Key: field_key_1', 'advanced-form-integration' ) ),
        array( 'key' => 'field_key_2', 'value' => __( 'Field Key: field_key_2', 'advanced-form-integration' ) ),
        array( 'key' => 'recordJson', 'value' => __( 'Record JSON (optional)', 'advanced-form-integration' ), 'description' => __( 'Provide full JSON object; mapped fields merge with this.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_softr_job_queue', 'adfoin_softr_job_queue', 10, 1 );

function adfoin_softr_job_queue( $data ) {
    adfoin_softr_send_record( $data['record'], $data['posted_data'] );
}

function adfoin_softr_send_record( $record, $posted_data ) {
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

    $collection = isset( $fields['collection'] ) ? $fields['collection'] : '';

    if ( ! $collection ) {
        return;
    }

    unset( $fields['collection'] );

    $payload = adfoin_softr_prepare_record_payload( $fields );

    adfoin_softr_request( 'collections/' . rawurlencode( $collection ) . '/records', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_softr_request' ) ) :
function adfoin_softr_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'softr', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Softr credentials not found.', 'advanced-form-integration' ) );
    }

    $app_id = isset( $credentials['appId'] ) ? $credentials['appId'] : '';
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $app_id || ! $api_key ) {
        return new WP_Error( 'missing_auth', __( 'Softr app ID or API key missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://studio-api.softr.io/v1/api/apps/' . rawurlencode( $app_id ) . '/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'       => 'application/json',
            'Softr-Api-Key'      => $api_key,
            'Softr-Api-Id'       => $app_id,
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

if ( ! function_exists( 'adfoin_softr_prepare_record_payload' ) ) :
function adfoin_softr_prepare_record_payload( $fields ) {
    $payload = array();

    if ( isset( $fields['recordJson'] ) ) {
        $decoded = json_decode( $fields['recordJson'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $payload = $decoded;
        }
        unset( $fields['recordJson'] );
    }

    foreach ( $fields as $key => $value ) {
        $payload[ $key ] = $value;
    }

    return $payload;
}
endif;

function adfoin_softr_credentials_list() {
    foreach ( adfoin_read_credentials( 'softr' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
