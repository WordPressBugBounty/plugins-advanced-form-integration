<?php

add_filter( 'adfoin_action_providers', 'adfoin_adobecampaign_actions', 10, 1 );

function adfoin_adobecampaign_actions( $actions ) {
    $actions['adobecampaign'] = array(
        'title' => __( 'Adobe Campaign / Journey Optimizer', 'advanced-form-integration' ),
        'tasks' => array(
            'create_profile' => __( 'Create Profile (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_adobecampaign_settings_tab', 10, 1 );

function adfoin_adobecampaign_settings_tab( $providers ) {
    $providers['adobecampaign'] = __( 'Adobe Campaign / Journey Optimizer', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_adobecampaign_settings_view', 10, 1 );

function adfoin_adobecampaign_settings_view( $current_tab ) {
    if ( 'adobecampaign' !== $current_tab ) {
        return;
    }

    $title = __( 'Adobe Campaign / Journey Optimizer', 'advanced-form-integration' );
    $key   = 'adobecampaign';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'tenantSlug', 'label' => __( 'Tenant (mc.adobe.io slug)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'organizationId', 'label' => __( 'IMS Organization ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'sandboxName', 'label' => __( 'Sandbox Name', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientId', 'label' => __( 'Client ID (API Key)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'scope', 'label' => __( 'OAuth Scope (comma separated)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'imsEndpoint', 'label' => __( 'IMS Token Endpoint (optional)', 'advanced-form-integration' ), 'hidden' => false ),
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
                    <li>%9$s</li>
                </ol>
            </li>
        </ol>
        <p>%10$s</p>
        <p>%11$s</p>',
        esc_html__( 'Create an Adobe Developer console project', 'advanced-form-integration' ),
        esc_html__( 'Open https://developer.adobe.com/console and create (or reuse) a project aligned with your Adobe Campaign / Journey Optimizer tenant.', 'advanced-form-integration' ),
        esc_html__( 'Add an API → Journey Optimizer or Campaign Standard and choose the OAuth Server-to-Server credential option.', 'advanced-form-integration' ),
        esc_html__( 'Note the generated Client ID (API key), Client Secret, IMS Organization ID, Sandbox name, and the scopes Adobe assigns (e.g., campaign_sdk, profile_write).', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Enter the tenant slug that appears after https://mc.adobe.io/ (for example “mytenant”).', 'advanced-form-integration' ),
        esc_html__( 'Paste your IMS Organization ID, sandbox name (usually “prod”), and OAuth scope list exactly as provided by Adobe.', 'advanced-form-integration' ),
        esc_html__( 'Copy the Client ID and Client Secret into the fields above. Leave the IMS token endpoint blank to use the default https://ims-na1.adobelogin.com/ims/token/v3.', 'advanced-form-integration' ),
        esc_html__( 'Save the settings and AFI will request and cache service-to-service access tokens automatically.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends requests to https://mc.adobe.io/{tenant}/ using your IMS Org ID, sandbox, and API key headers that Adobe requires.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Adobe Campaign / Journey Optimizer [PRO] to update existing profiles, trigger journeys, and start Campaign workflows with custom payloads.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_adobecampaign_action_fields' );

function adfoin_adobecampaign_action_fields() {
    ?>
    <script type="text/template" id="adobecampaign-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Adobe Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_adobecampaign_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need advanced workflows?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Adobe Campaign / Journey Optimizer [PRO]</a> to upsert profiles, trigger journey events, and start Campaign workflows.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_adobecampaign_credentials', 'adfoin_get_adobecampaign_credentials' );

function adfoin_get_adobecampaign_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'adobecampaign' ) );
}

add_action( 'wp_ajax_adfoin_save_adobecampaign_credentials', 'adfoin_save_adobecampaign_credentials' );

function adfoin_save_adobecampaign_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'adobecampaign' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'adobecampaign', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_adobecampaign_fields', 'adfoin_get_adobecampaign_fields' );

function adfoin_get_adobecampaign_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email Address', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'preferredLanguage', 'value' => __( 'Preferred Language', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_adobecampaign_job_queue', 'adfoin_adobecampaign_job_queue', 10, 1 );

function adfoin_adobecampaign_job_queue( $data ) {
    adfoin_adobecampaign_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_adobecampaign_send_data( $record, $posted_data ) {
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
        'profile' => $fields,
    );

    adfoin_adobecampaign_request( 'campaign/profileAndServices/profile', 'POST', $payload, $record, $cred_id );
}

function adfoin_adobecampaign_credentials_list() {
    foreach ( adfoin_read_credentials( 'adobecampaign' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

if ( ! function_exists( 'adfoin_adobecampaign_request' ) ) :
function adfoin_adobecampaign_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $raw = false ) {
    $credentials = adfoin_get_credentials_by_id( 'adobecampaign', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Adobe Campaign credentials not found.', 'advanced-form-integration' ) );
    }

    $api_base = adfoin_adobecampaign_get_api_base( $credentials );
    $token    = adfoin_adobecampaign_get_token( $credentials );

    if ( ! $api_base || ! $token ) {
        return new WP_Error( 'auth_failed', __( 'Unable to authenticate with Adobe Campaign.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $api_base ) . ltrim( $endpoint, '/' );

    $headers = array(
        'Authorization'        => 'Bearer ' . $token,
        'Content-Type'         => 'application/json',
        'Accept'               => 'application/json',
        'x-api-key'            => $credentials['clientId'],
    );

    if ( ! empty( $credentials['organizationId'] ) ) {
        $headers['x-gw-ims-org-id'] = $credentials['organizationId'];
    }

    if ( ! empty( $credentials['sandboxName'] ) ) {
        $headers['x-sandbox-name'] = $credentials['sandboxName'];
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => $headers,
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = ( $raw && is_string( $data ) ) ? $data : wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_adobecampaign_get_api_base' ) ) :
function adfoin_adobecampaign_get_api_base( $credentials ) {
    if ( empty( $credentials['tenantSlug'] ) ) {
        return '';
    }

    return 'https://mc.adobe.io/' . trim( $credentials['tenantSlug'] );
}
endif;

if ( ! function_exists( 'adfoin_adobecampaign_get_token' ) ) :
function adfoin_adobecampaign_get_token( $credentials ) {
    $client = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';

    if ( ! $client || ! $secret ) {
        return '';
    }

    $scope = isset( $credentials['scope'] ) ? trim( $credentials['scope'] ) : '';
    $cache = 'adfoin_adobe_token_' . md5( $client . '|' . $scope );
    $token = get_transient( $cache );

    if ( $token ) {
        return $token;
    }

    $endpoint = ! empty( $credentials['imsEndpoint'] ) ? $credentials['imsEndpoint'] : 'https://ims-na1.adobelogin.com/ims/token/v3';

    $body = array(
        'client_id'     => $client,
        'client_secret' => $secret,
        'grant_type'    => 'client_credentials',
    );

    if ( $scope ) {
        $body['scope'] = $scope;
    }

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'body'    => $body,
    );

    $response = wp_remote_post( $endpoint, $args );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code || empty( $data['access_token'] ) ) {
        return '';
    }

    $token   = sanitize_text_field( $data['access_token'] );
    $expires = isset( $data['expires_in'] ) ? absint( $data['expires_in'] ) : 600;

    set_transient( $cache, $token, max( 60, $expires - 60 ) );

    return $token;
}
endif;
