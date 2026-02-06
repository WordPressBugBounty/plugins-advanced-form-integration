<?php

add_filter( 'adfoin_action_providers', 'adfoin_planhat_actions', 10, 1 );

function adfoin_planhat_actions( $actions ) {

    $actions['planhat'] = array(
        'title' => __( 'Planhat CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_company' => __( 'Create Company', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_planhat_settings_tab', 10, 1 );

function adfoin_planhat_settings_tab( $providers ) {
    $providers['planhat'] = __( 'Planhat CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_planhat_settings_view', 10, 1 );

function adfoin_planhat_settings_view( $current_tab ) {
    if ( 'planhat' !== $current_tab ) {
        return;
    }

    $title = __( 'Planhat CRM', 'advanced-form-integration' );
    $key   = 'planhat';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'apiToken',
                'label'  => __( 'API Access Token', 'advanced-form-integration' ),
                'hidden' => true,
            ),
            array(
                'key'    => 'baseUrl',
                'label'  => __( 'Base URL (optional)', 'advanced-form-integration' ),
                'hidden' => false,
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
        esc_html__( 'Generate an API token', 'advanced-form-integration' ),
        esc_html__( 'In Planhat go to Settings → Service Accounts → API Access Tokens.', 'advanced-form-integration' ),
        esc_html__( 'Create a Service Account or use an existing one and generate a token with the scopes you need.', 'advanced-form-integration' ),
        esc_html__( 'Copy the token immediately – Planhat only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Connect the token to AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the token below and give the credential a recognizable title, then click "Save & Authenticate".', 'advanced-form-integration' ),
        esc_html__( 'Leave Base URL blank to use https://api.planhat.com or override it if your tenant uses a regional endpoint.', 'advanced-form-integration' ),
        esc_html__( 'Select the saved credential when creating an integration and map the company fields you want to send.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends requests with the Authorization: Bearer header and supports create/update operations in Pro.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Planhat CRM [PRO] to sync end users, update existing companies, and push custom field payloads.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_add_js_fields', 'adfoin_planhat_js_fields', 10, 1 );

function adfoin_planhat_js_fields( $field_data ) {} // phpcs:ignore WordPress.CodeAnalysis.UnusedFunctionParameter.Found

add_action( 'adfoin_action_fields', 'adfoin_planhat_action_fields' );

function adfoin_planhat_action_fields() {
    ?>
    <script type="text/template" id="planhat-action-template">
        <table class="form-table">
            <tr v-if="action.task == 'create_company'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_company'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Planhat Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_planhat_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate" v-if="action.task == 'create_company'">
                <th scope="row"><?php esc_html_e( 'Need end users or updates?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Planhat CRM [PRO]</a> to update existing companies, sync end users, and send custom field payloads.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_planhat_credentials', 'adfoin_get_planhat_credentials' );

function adfoin_get_planhat_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'planhat' ) );
}

add_action( 'wp_ajax_adfoin_save_planhat_credentials', 'adfoin_save_planhat_credentials' );

function adfoin_save_planhat_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'planhat' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'planhat', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_planhat_fields', 'adfoin_get_planhat_fields' );

function adfoin_get_planhat_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'name', 'value' => __( 'Company Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'externalId', 'value' => __( 'External ID', 'advanced-form-integration' ) ),
        array( 'key' => 'sourceId', 'value' => __( 'Source ID', 'advanced-form-integration' ) ),
        array( 'key' => 'status', 'value' => __( 'Status', 'advanced-form-integration' ) ),
        array( 'key' => 'phase', 'value' => __( 'Lifecycle Phase', 'advanced-form-integration' ) ),
        array( 'key' => 'description', 'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'zip', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'address', 'value' => __( 'Street Address', 'advanced-form-integration' ) ),
        array( 'key' => 'phonePrimary', 'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'web', 'value' => __( 'Website', 'advanced-form-integration' ) ),
        array( 'key' => 'tags', 'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_planhat_job_queue', 'adfoin_planhat_job_queue', 10, 1 );

function adfoin_planhat_job_queue( $data ) {
    adfoin_planhat_send_company( $data['record'], $data['posted_data'] );
}

function adfoin_planhat_send_company( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_planhat_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $mapped_fields = array();

    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed || null === $parsed ) {
            continue;
        }

        $mapped_fields[ $key ] = $parsed;
    }

    if ( empty( $mapped_fields['name'] ) ) {
        return;
    }

    if ( isset( $mapped_fields['tags'] ) && is_string( $mapped_fields['tags'] ) ) {
        $mapped_fields['tags'] = adfoin_planhat_prepare_list_field( $mapped_fields['tags'] );
    }

    $payload = adfoin_planhat_build_payload( $mapped_fields );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_planhat_request( 'companies', 'POST', $payload, $record, $credentials );
}

function adfoin_planhat_credentials_list() {
    $credentials = adfoin_read_credentials( 'planhat' );

    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_planhat_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'planhat', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Planhat credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

if ( ! function_exists( 'adfoin_planhat_request' ) ) :
function adfoin_planhat_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = array() ) {
    $token = isset( $credentials['apiToken'] ) ? trim( $credentials['apiToken'] ) : '';

    if ( '' === $token ) {
        return new WP_Error( 'missing_token', __( 'Planhat API token is missing.', 'advanced-form-integration' ) );
    }

    $base_url = isset( $credentials['baseUrl'] ) && '' !== trim( $credentials['baseUrl'] )
        ? rtrim( trim( $credentials['baseUrl'] ), '/' )
        : 'https://api.planhat.com';

    $url  = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ),
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

if ( ! function_exists( 'adfoin_planhat_build_payload' ) ) :
function adfoin_planhat_build_payload( $fields ) {
    $payload = array();

    foreach ( $fields as $path => $value ) {
        adfoin_planhat_assign_path( $payload, $path, adfoin_planhat_normalize_value( $path, $value ) );
    }

    return $payload;
}
endif;

if ( ! function_exists( 'adfoin_planhat_assign_path' ) ) :
function adfoin_planhat_assign_path( array &$target, $path, $value ) {
    if ( '' === $path ) {
        return;
    }

    $segments = explode( '.', $path );
    $ref      =& $target;

    foreach ( $segments as $index => $segment ) {
        $is_last = ( $index === count( $segments ) - 1 );

        if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $segment, $matches ) ) {
            $key   = $matches[1];
            $i     = (int) $matches[2];

            if ( ! isset( $ref[ $key ] ) || ! is_array( $ref[ $key ] ) ) {
                $ref[ $key ] = array();
            }

            if ( ! isset( $ref[ $key ][ $i ] ) || ! is_array( $ref[ $key ][ $i ] ) ) {
                $ref[ $key ][ $i ] = array();
            }

            if ( $is_last ) {
                $ref[ $key ][ $i ] = $value;
            } else {
                $ref =& $ref[ $key ][ $i ];
            }

            continue;
        }

        if ( $is_last ) {
            $ref[ $segment ] = $value;
        } else {
            if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
                $ref[ $segment ] = array();
            }

            $ref =& $ref[ $segment ];
        }
    }
}
endif;

if ( ! function_exists( 'adfoin_planhat_normalize_value' ) ) :
function adfoin_planhat_normalize_value( $path, $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    if ( is_string( $value ) ) {
        $trimmed = trim( $value );

        if ( '' === $trimmed ) {
            return '';
        }

        if ( in_array( strtolower( $trimmed ), array( 'true', 'false' ), true ) ) {
            return 'true' === strtolower( $trimmed );
        }

        if ( is_numeric( $trimmed ) ) {
            return $trimmed + 0;
        }
    }

    return $value;
}
endif;

function adfoin_planhat_prepare_list_field( $value ) {
    if ( is_array( $value ) ) {
        return array_values( array_filter( array_map( 'trim', $value ), 'strlen' ) );
    }

    if ( is_string( $value ) ) {
        $parts = array_map( 'trim', explode( ',', $value ) );
        $parts = array_filter( $parts, 'strlen' );

        return array_values( $parts );
    }

    return array();
}
