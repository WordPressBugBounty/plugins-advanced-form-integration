<?php

add_filter( 'adfoin_action_providers', 'adfoin_salesforcemc_actions', 10, 1 );

function adfoin_salesforcemc_actions( $actions ) {
    $actions['salesforcemc'] = array(
        'title' => __( 'Salesforce Marketing Cloud', 'advanced-form-integration' ),
        'tasks' => array(
            'upsert_subscriber' => __( 'Create/Update Subscriber (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_salesforcemc_settings_tab', 10, 1 );

function adfoin_salesforcemc_settings_tab( $providers ) {
    $providers['salesforcemc'] = __( 'Salesforce Marketing Cloud', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_salesforcemc_settings_view', 10, 1 );

function adfoin_salesforcemc_settings_view( $current_tab ) {
    if ( 'salesforcemc' !== $current_tab ) {
        return;
    }

    $title = __( 'Salesforce Marketing Cloud', 'advanced-form-integration' );
    $key   = 'salesforcemc';

    $arguments = json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'accountName', 'label' => __( 'Tenant Subdomain', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientId', 'label' => __( 'Client ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
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
            <li><strong>%8$s</strong>
                <ol>
                    <li>%9$s</li>
                    <li>%10$s</li>
                    <li>%11$s</li>
                </ol>
            </li>
        </ol>
        <p>%12$s</p>
        <p>%13$s</p>',
        esc_html__( 'Create a Connected App (Installed Package)', 'advanced-form-integration' ),
        esc_html__( 'Sign in to your Marketing Cloud tenant and go to Setup → Apps → Installed Packages.', 'advanced-form-integration' ),
        esc_html__( 'Click New, give the package a name (e.g., “AFI Integration”), and save.', 'advanced-form-integration' ),
        esc_html__( 'Under Components choose “Add Component” → “API Integration”, select “Server-to-Server”, then enable the permissions you need (Contacts, Email, Automations).', 'advanced-form-integration' ),
        esc_html__( 'Collect credentials', 'advanced-form-integration' ),
        esc_html__( 'Marketing Cloud will display a Client ID and Client Secret—copy both values and paste them into the fields above.', 'advanced-form-integration' ),
        esc_html__( 'From the same view copy the “Authentication Base URI”. Its prefix (e.g., mc123) is the tenant subdomain used to build the Auth/REST endpoints.', 'advanced-form-integration' ),
        esc_html__( 'Determine your base URLs', 'advanced-form-integration' ),
        esc_html__( 'The tenant subdomain populates the Auth, REST, and SOAP endpoints. For example, with subdomain mc123 you will call https://mc123.auth.marketingcloudapis.com (token) and https://mc123.rest.marketingcloudapis.com (REST).', 'advanced-form-integration' ),
        esc_html__( 'Enter the subdomain (without protocol) in the Tenant Subdomain field above (mc123 from the example).', 'advanced-form-integration' ),
        esc_html__( 'Save the credentials. AFI will request OAuth tokens automatically using the Client Credentials flow, cache them briefly, and invoke the REST endpoints as needed.', 'advanced-form-integration' ),
        esc_html__( 'You can add multiple Marketing Cloud business units by repeating these steps and storing each credential set separately.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to the Salesforce Marketing Cloud [PRO] connector to sync list membership, trigger Journey Builder events, and map custom subscriber attributes.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_salesforcemc_action_fields' );

function adfoin_salesforcemc_action_fields() {
    ?>
    <script type="text/template" id="salesforcemc-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Salesforce Marketing Cloud Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_salesforcemc_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata">
            </editable-field>
            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need more?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Salesforce Marketing Cloud [PRO]</a> to add static list membership, trigger Journey Builder events, and map every subscriber attribute.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_salesforcemc_credentials', 'adfoin_get_salesforcemc_credentials' );

function adfoin_get_salesforcemc_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'salesforcemc' ) );
}

add_action( 'wp_ajax_adfoin_save_salesforcemc_credentials', 'adfoin_save_salesforcemc_credentials' );

function adfoin_save_salesforcemc_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'salesforcemc' === $_POST['platform'] ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) );
        adfoin_save_credentials( 'salesforcemc', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_salesforcemc_fields', 'adfoin_get_salesforcemc_fields' );

function adfoin_get_salesforcemc_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'subscriberKey', 'value' => __( 'Subscriber Key', 'advanced-form-integration' ), 'description' => __( 'Unique identifier. Defaults to email when not provided.', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email Address', 'advanced-form-integration' ), 'description' => '', 'required' => true ),
        array( 'key' => 'status', 'value' => __( 'Status', 'advanced-form-integration' ), 'description' => __( 'Active, Unsubscribed, Held, etc. Defaults to Active.', 'advanced-form-integration' ) ),
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'journeyDefinitionKey', 'value' => __( 'Journey Definition Key', 'advanced-form-integration' ), 'description' => __( 'Required for Journey event task.', 'advanced-form-integration' ) ),
        array( 'key' => 'journeyContactKey', 'value' => __( 'Journey Contact Key', 'advanced-form-integration' ), 'description' => __( 'Contact key for Journey entry event.', 'advanced-form-integration' ) ),
        array( 'key' => 'journeyData', 'value' => __( 'Journey Event Data (JSON)', 'advanced-form-integration' ), 'description' => __( 'JSON payload string passed to Journey event.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_salesforcemc_job_queue', 'adfoin_salesforcemc_job_queue', 10, 1 );

function adfoin_salesforcemc_job_queue( $data ) {
    adfoin_salesforcemc_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_salesforcemc_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task    = isset( $record['task'] ) ? $record['task'] : '';

    if ( ! $cred_id || ! $task ) {
        return;
    }

    $fields = array();
    foreach ( $data as $key => $value ) {
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    if ( 'upsert_subscriber' === $task ) {
        adfoin_salesforcemc_upsert_subscriber( $fields, $record, $cred_id );
        return;
    }

    if ( 'trigger_journey_event' === $task ) {
        adfoin_salesforcemc_trigger_journey( $fields, $record, $cred_id );
    }
}

function adfoin_salesforcemc_upsert_subscriber( $fields, $record, $cred_id ) {
    $email = isset( $fields['email'] ) ? trim( $fields['email'] ) : '';

    if ( ! $email ) {
        return;
    }

    $subscriber_key = isset( $fields['subscriberKey'] ) && $fields['subscriberKey'] ? $fields['subscriberKey'] : $email;
    $status         = isset( $fields['status'] ) && $fields['status'] ? $fields['status'] : 'Active';

    $payload = array(
        'EmailAddress'  => $email,
        'SubscriberKey' => $subscriber_key,
        'Status'        => $status,
    );

    if ( isset( $fields['firstName'] ) && $fields['firstName'] !== '' ) {
        $payload['Attributes'][] = array(
            'Name'  => 'First Name',
            'Value' => $fields['firstName'],
        );
    }

    if ( isset( $fields['lastName'] ) && $fields['lastName'] !== '' ) {
        $payload['Attributes'][] = array(
            'Name'  => 'Last Name',
            'Value' => $fields['lastName'],
        );
    }

    $list_id = isset( $fields['listId'] ) ? $fields['listId'] : '';

    if ( $list_id ) {
        $payload['Lists'][] = array(
            'ID'     => (int) $list_id,
            'Status' => 'Active',
        );
    }

    adfoin_salesforcemc_request( 'contacts/v1/subscribers', 'POST', $payload, $record, $cred_id );
}

function adfoin_salesforcemc_trigger_journey( $fields, $record, $cred_id ) {
    $definition_key = isset( $fields['journeyDefinitionKey'] ) ? $fields['journeyDefinitionKey'] : '';
    $contact_key    = isset( $fields['journeyContactKey'] ) ? $fields['journeyContactKey'] : '';

    if ( ! $definition_key || ! $contact_key ) {
        return;
    }

    $payload = array(
        'contactKey'    => $contact_key,
        'definitionKey' => $definition_key,
    );

    if ( ! empty( $fields['journeyData'] ) ) {
        $json = json_decode( $fields['journeyData'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $json ) ) {
            $payload['data'] = $json;
        }
    }

    adfoin_salesforcemc_request( 'interaction/v1/events', 'POST', $payload, $record, $cred_id );
}

function adfoin_salesforcemc_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $raw = false ) {
    $credentials = adfoin_get_credentials_by_id( 'salesforcemc', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Salesforce Marketing Cloud credentials not found.', 'advanced-form-integration' ) );
    }

    $rest_base = adfoin_salesforcemc_get_rest_base( $credentials );
    $token     = adfoin_salesforcemc_get_token( $credentials );

    if ( ! $rest_base || ! $token ) {
        return new WP_Error( 'auth_failed', __( 'Unable to authenticate with Salesforce Marketing Cloud.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $rest_base ) . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = $raw && is_string( $data ) ? $data : wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

function adfoin_salesforcemc_get_rest_base( $credentials ) {
    if ( empty( $credentials['accountName'] ) ) {
        return '';
    }

    return sprintf( 'https://%s.rest.marketingcloudapis.com', $credentials['accountName'] );
}

function adfoin_salesforcemc_get_auth_base( $credentials ) {
    if ( empty( $credentials['accountName'] ) ) {
        return '';
    }

    return sprintf( 'https://%s.auth.marketingcloudapis.com', $credentials['accountName'] );
}

function adfoin_salesforcemc_get_token( $credentials ) {
    $account = isset( $credentials['accountName'] ) ? $credentials['accountName'] : '';
    $client  = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $secret  = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';

    if ( ! $account || ! $client || ! $secret ) {
        return '';
    }

    $cache_key = 'adfoin_smc_token_' . md5( $account . $client );
    $cached    = get_transient( $cache_key );

    if ( $cached ) {
        return $cached;
    }

    $auth_base = adfoin_salesforcemc_get_auth_base( $credentials );

    if ( ! $auth_base ) {
        return '';
    }

    $url  = trailingslashit( $auth_base ) . 'v2/token';
    $body = array(
        'grant_type'    => 'client_credentials',
        'client_id'     => $client,
        'client_secret' => $secret,
    );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_request( $url, $args );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code || empty( $data['access_token'] ) ) {
        return '';
    }

    $token   = sanitize_text_field( $data['access_token'] );
    $expires = isset( $data['expires_in'] ) ? absint( $data['expires_in'] ) : 1800;

    set_transient( $cache_key, $token, max( 60, $expires - 60 ) );

    return $token;
}

function adfoin_salesforcemc_credentials_list() {
    foreach ( adfoin_read_credentials( 'salesforcemc' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
