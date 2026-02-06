<?php

add_filter( 'adfoin_action_providers', 'adfoin_salesmate_actions', 10, 1 );

function adfoin_salesmate_actions( $actions ) {
    $actions['salesmate'] = array(
        'title' => __( 'Salesmate', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_salesmate_settings_tab', 10, 1 );

function adfoin_salesmate_settings_tab( $providers ) {
    $providers['salesmate'] = __( 'Salesmate', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_salesmate_settings_view', 10, 1 );

function adfoin_salesmate_settings_view( $current_tab ) {
    if ( 'salesmate' !== $current_tab ) {
        return;
    }

    $title = __( 'Salesmate', 'advanced-form-integration' );
    $key   = 'salesmate';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'domain', 'label' => __( 'Salesmate Domain (without https://)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientId', 'label' => __( 'Client ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'accessToken', 'label' => __( 'Access Token', 'advanced-form-integration' ), 'hidden' => true ),
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
                </ol>
            </li>
        </ol>
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Enable API access', 'advanced-form-integration' ),
        esc_html__( 'Log in to Salesmate and go to Setup → Apps & Add-ons → API & SDK.', 'advanced-form-integration' ),
        esc_html__( 'Create an API client to obtain the Client ID and Client Secret, then generate an Access Token.', 'advanced-form-integration' ),
        esc_html__( 'Confirm your workspace domain (for example, mycompany.salesmate.io).', 'advanced-form-integration' ),
        esc_html__( 'Configure AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the domain (without https://), Client ID, Client Secret, and Access Token above.', 'advanced-form-integration' ),
        esc_html__( 'Save the credentials. Repeat to add additional workspaces or tokens.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends JSON requests to https://{domain}/apis/v1/ with the token headers required by Salesmate.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Salesmate [PRO] to assign tags, owners, and deals, or to trigger built-in sequences.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_salesmate_action_fields' );

function adfoin_salesmate_action_fields() {
    ?>
    <script type="text/template" id="salesmate-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Salesmate Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_salesmate_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need more fields?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Salesmate [PRO]</a> to add tags, owner assignments, custom fields, and deal creation.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_salesmate_credentials', 'adfoin_get_salesmate_credentials' );

function adfoin_get_salesmate_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'salesmate' ) );
}

add_action( 'wp_ajax_adfoin_save_salesmate_credentials', 'adfoin_save_salesmate_credentials' );

function adfoin_save_salesmate_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'salesmate' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'salesmate', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_salesmate_fields', 'adfoin_get_salesmate_fields' );

function adfoin_get_salesmate_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'jobTitle', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_salesmate_job_queue', 'adfoin_salesmate_job_queue', 10, 1 );

function adfoin_salesmate_job_queue( $data ) {
    adfoin_salesmate_send_contact( $data['record'], $data['posted_data'] );
}

function adfoin_salesmate_send_contact( $record, $posted_data ) {
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

    $payload = adfoin_salesmate_prepare_contact_payload( $fields );

    adfoin_salesmate_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_salesmate_request' ) ) :
function adfoin_salesmate_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'salesmate', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Salesmate credentials not found.', 'advanced-form-integration' ) );
    }

    $domain       = isset( $credentials['domain'] ) ? trim( $credentials['domain'] ) : '';
    $client_id    = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $client_secret= isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
    $access_token = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';

    if ( ! $domain || ! $client_id || ! $client_secret || ! $access_token ) {
        return new WP_Error( 'missing_auth', __( 'Salesmate domain, client ID, secret, or access token missing.', 'advanced-form-integration' ) );
    }

    $base = adfoin_salesmate_get_base_url( $domain );
    $url  = trailingslashit( $base ) . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'client-id'     => $client_id,
            'client-secret' => $client_secret,
            'access-token'  => $access_token,
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

if ( ! function_exists( 'adfoin_salesmate_get_base_url' ) ) :
function adfoin_salesmate_get_base_url( $domain ) {
    $domain = preg_replace( '#^https?://#', '', $domain );

    return 'https://' . $domain . '/apis/v1';
}
endif;

if ( ! function_exists( 'adfoin_salesmate_prepare_contact_payload' ) ) :
function adfoin_salesmate_prepare_contact_payload( $fields ) {
    $payload = array(
        'firstName' => $fields['firstName'] ?? '',
        'lastName'  => $fields['lastName'] ?? '',
        'email'     => $fields['email'] ?? '',
    );

    $map = array(
        'company'  => 'company',
        'jobTitle' => 'jobTitle',
        'phone'    => 'phone',
    );

    foreach ( $map as $field => $key ) {
        if ( isset( $fields[ $field ] ) && '' !== $fields[ $field ] ) {
            $payload[ $key ] = $fields[ $field ];
        }
    }

    return $payload;
}
endif;

function adfoin_salesmate_credentials_list() {
    foreach ( adfoin_read_credentials( 'salesmate' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
