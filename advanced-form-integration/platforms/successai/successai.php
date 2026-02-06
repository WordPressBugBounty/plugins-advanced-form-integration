<?php

add_filter( 'adfoin_action_providers', 'adfoin_successai_actions', 10, 1 );

function adfoin_successai_actions( $actions ) {
    $actions['successai'] = array(
        'title' => __( 'Success.ai', 'advanced-form-integration' ),
        'tasks' => array(
            'add_prospect' => __( 'Add Prospect (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_successai_settings_tab', 10, 1 );

function adfoin_successai_settings_tab( $providers ) {
    $providers['successai'] = __( 'Success.ai', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_successai_settings_view', 10, 1 );

function adfoin_successai_settings_view( $current_tab ) {
    if ( 'successai' !== $current_tab ) {
        return;
    }

    $title = __( 'Success.ai', 'advanced-form-integration' );
    $key   = 'successai';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'workspaceId', 'label' => __( 'Workspace ID (optional)', 'advanced-form-integration' ), 'hidden' => false ),
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
        esc_html__( 'Log in to Success.ai, open Settings → API and copy your API key.', 'advanced-form-integration' ),
        esc_html__( 'If you operate multiple workspaces, copy the workspace ID to target a specific database.', 'advanced-form-integration' ),
        esc_html__( 'Configure AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the API key and optional workspace ID above and save.', 'advanced-form-integration' ),
        esc_html__( 'Repeat for additional workspaces if required.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends authenticated requests to https://api.success.ai/api using the key in the Authorization header.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Success.ai [PRO] to add tags, custom attributes, and trigger campaigns.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_successai_action_fields' );

function adfoin_successai_action_fields() {
    ?>
    <script type="text/template" id="successai-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Success.ai Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_successai_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need advanced mapping?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Success.ai [PRO]</a> to add tags, assign campaigns, and push custom attributes.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_successai_credentials', 'adfoin_get_successai_credentials' );

function adfoin_get_successai_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'successai' ) );
}

add_action( 'wp_ajax_adfoin_save_successai_credentials', 'adfoin_save_successai_credentials' );

function adfoin_save_successai_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'successai' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'successai', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_successai_fields', 'adfoin_get_successai_fields' );

function adfoin_get_successai_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'notes', 'value' => __( 'Notes', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_successai_job_queue', 'adfoin_successai_job_queue', 10, 1 );

function adfoin_successai_job_queue( $data ) {
    adfoin_successai_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_successai_send_data( $record, $posted_data ) {
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

    $payload = adfoin_successai_prepare_payload( $fields );

    adfoin_successai_request( 'prospects', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_successai_request' ) ) :
function adfoin_successai_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'successai', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Success.ai credentials not found.', 'advanced-form-integration' ) );
    }

    $api_key   = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $workspace = isset( $credentials['workspaceId'] ) ? $credentials['workspaceId'] : '';

    if ( ! $api_key ) {
        return new WP_Error( 'missing_key', __( 'Success.ai API key missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.success.ai/api/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    if ( $workspace ) {
        $args['headers']['X-Workspace-Id'] = $workspace;
    }

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

function adfoin_successai_credentials_list() {
    foreach ( adfoin_read_credentials( 'successai' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

if ( ! function_exists( 'adfoin_successai_prepare_payload' ) ) :
function adfoin_successai_prepare_payload( $fields ) {
    $payload = $fields;

    if ( isset( $payload['tags'] ) ) {
        $tags = $payload['tags'];

        if ( is_string( $tags ) ) {
            $tags = explode( ',', $tags );
        }

        if ( is_array( $tags ) ) {
            $tags = array_values( array_filter( array_map( 'trim', $tags ) ) );
        } else {
            $tags = array();
        }

        if ( $tags ) {
            $payload['tags'] = $tags;
        } else {
            unset( $payload['tags'] );
        }
    }

    if ( isset( $payload['customJson'] ) ) {
        $decoded = json_decode( $payload['customJson'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $payload = array_merge( $payload, $decoded );
        }

        unset( $payload['customJson'] );
    }

    return $payload;
}
endif;
