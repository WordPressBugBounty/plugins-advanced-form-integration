<?php

add_filter( 'adfoin_action_providers', 'adfoin_marketo_actions', 10, 1 );

function adfoin_marketo_actions( $actions ) {
    $actions['marketo'] = array(
        'title' => __( 'Marketo Engage', 'advanced-form-integration' ),
        'tasks' => array(
            'create_lead' => __( 'Create Lead (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_marketo_settings_tab', 10, 1 );

function adfoin_marketo_settings_tab( $providers ) {
    $providers['marketo'] = __( 'Marketo Engage', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_marketo_settings_view', 10, 1 );

function adfoin_marketo_settings_view( $current_tab ) {
    if ( 'marketo' !== $current_tab ) {
        return;
    }

    $title = __( 'Marketo Engage', 'advanced-form-integration' );
    $key   = 'marketo';

    $arguments = json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'instanceHost', 'label' => __( 'Instance Host (mktorest)', 'advanced-form-integration' ), 'hidden' => false ),
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
        </ol>
        <p>%8$s</p>',
        esc_html__( 'Create a Custom Service', 'advanced-form-integration' ),
        esc_html__( 'Log in to Marketo as an administrator and go to Admin → Users & Roles → SOAP API to ensure the API is enabled.', 'advanced-form-integration' ),
        esc_html__( 'Navigate to Admin → LaunchPoint → New Service, choose “Custom” and enter a name (e.g., “AFI Integration”).', 'advanced-form-integration' ),
        esc_html__( 'After saving, click “View Details” to reveal the Client ID and Client Secret. Copy both values and paste them above.', 'advanced-form-integration' ),
        esc_html__( 'Collect your instance host', 'advanced-form-integration' ),
        esc_html__( 'Go to Admin → Web Services and locate the “REST API” section. Copy the Base URL which looks like https://123-ABC-456.mktorest.com/rest.', 'advanced-form-integration' ),
        esc_html__( 'Strip the trailing /rest portion and paste the host (123-ABC-456.mktorest.com) into the Instance Host field above.', 'advanced-form-integration' ),
        esc_html__( 'AFI will call https://{host}/identity/oauth/token to obtain access tokens and reuse them across requests until they expire.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_marketo_action_fields' );

function adfoin_marketo_action_fields() {
    ?>
    <script type="text/template" id="marketo-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_marketo_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Want static lists and full lead mapping?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Marketo Engage [PRO]</a> for list membership and extended field support.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_marketo_fields', 'adfoin_get_marketo_fields' );

function adfoin_get_marketo_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email', 'value' => __( 'Email Address', 'advanced-form-integration' ), 'description' => '', 'required' => true ),
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'jobTitle', 'value' => __( 'Job Title', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'state', 'value' => __( 'State/Province', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ), 'description' => '' ),
        array( 'key' => 'leadSource', 'value' => __( 'Lead Source', 'advanced-form-integration' ), 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'wp_ajax_adfoin_get_marketo_credentials', 'adfoin_get_marketo_credentials' );

function adfoin_get_marketo_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'marketo' ) );
}

add_action( 'wp_ajax_adfoin_save_marketo_credentials', 'adfoin_save_marketo_credentials' );

function adfoin_save_marketo_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'marketo' === $_POST['platform'] ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) );
        adfoin_save_credentials( 'marketo', $data );
    }

    wp_send_json_success();
}

add_action( 'adfoin_marketo_job_queue', 'adfoin_marketo_job_queue', 10, 1 );

function adfoin_marketo_job_queue( $data ) {
    adfoin_marketo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_marketo_send_data( $record, $posted_data ) {
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

    if ( 'create_lead' === $task ) {
        adfoin_marketo_upsert_lead( $fields, $record, $cred_id );
    }
}

function adfoin_marketo_upsert_lead( $fields, $record, $cred_id ) {
    $email = isset( $fields['email'] ) ? trim( $fields['email'] ) : '';

    if ( ! $email ) {
        return;
    }

    $input = array(
        'email'      => $email,
        'firstName'  => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
        'lastName'   => isset( $fields['lastName'] ) ? $fields['lastName'] : '',
        'company'    => isset( $fields['company'] ) ? $fields['company'] : '',
        'phone'      => isset( $fields['phone'] ) ? $fields['phone'] : '',
        'jobTitle'   => isset( $fields['jobTitle'] ) ? $fields['jobTitle'] : '',
        'city'       => isset( $fields['city'] ) ? $fields['city'] : '',
        'state'      => isset( $fields['state'] ) ? $fields['state'] : '',
        'country'    => isset( $fields['country'] ) ? $fields['country'] : '',
        'leadSource' => isset( $fields['leadSource'] ) ? $fields['leadSource'] : '',
    );

    $input = array_filter( $input, static function( $value ) {
        return '' !== $value && null !== $value;
    } );

    $payload = array(
        'action'      => 'createOrUpdate',
        'lookupField' => 'email',
        'input'       => array( $input ),
    );

    adfoin_marketo_request( 'leads.json', 'POST', $payload, $record, $cred_id );
}

function adfoin_marketo_add_to_list( $fields, $record, $cred_id ) {
    $email   = isset( $fields['email'] ) ? trim( $fields['email'] ) : '';
    $list_id = isset( $fields['listId'] ) ? $fields['listId'] : '';

    if ( ! $email || ! $list_id ) {
        return;
    }

    $lead_id = adfoin_marketo_get_lead_id_by_email( $email, $cred_id );

    if ( ! $lead_id ) {
        $create_response = adfoin_marketo_request(
            'leads.json',
            'POST',
            array(
                'action'      => 'createOrUpdate',
                'lookupField' => 'email',
                'input'       => array(
                    array(
                        'email' => $email,
                        'firstName' => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
                        'lastName'  => isset( $fields['lastName'] ) ? $fields['lastName'] : '',
                    ),
                ),
            ),
            $record,
            $cred_id
        );

        if ( is_wp_error( $create_response ) ) {
            return;
        }

        $lead_id = adfoin_marketo_parse_lead_id( $create_response );
    }

    if ( ! $lead_id ) {
        return;
    }

    $endpoint = sprintf( 'lists/%s/leads.json', $list_id );
    $payload  = array(
        'id'    => $list_id,
        'input' => array(
            array( 'id' => $lead_id ),
        ),
    );

    adfoin_marketo_request( $endpoint, 'POST', $payload, $record, $cred_id );
}

function adfoin_marketo_get_lead_id_by_email( $email, $cred_id ) {
    $endpoint = sprintf(
        'leads.json?filterType=email&filterValues=%s&fields=id',
        rawurlencode( $email )
    );

    $response = adfoin_marketo_request( $endpoint, 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['result'][0]['id'] ) ) {
        return $body['result'][0]['id'];
    }

    return '';
}

function adfoin_marketo_parse_lead_id( $response ) {
    if ( is_wp_error( $response ) ) {
        return '';
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['result'][0]['id'] ) ) {
        return $body['result'][0]['id'];
    }

    return '';
}

function adfoin_marketo_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'marketo', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Marketo credentials not found.', 'advanced-form-integration' ) );
    }

    $host      = isset( $credentials['instanceHost'] ) ? rtrim( $credentials['instanceHost'], '/' ) : '';
    $client_id = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $secret    = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';

    if ( ! $host || ! $client_id || ! $secret ) {
        return new WP_Error( 'missing_credentials', __( 'Marketo credentials are incomplete.', 'advanced-form-integration' ) );
    }

    $token = adfoin_marketo_get_token( $credentials );

    if ( ! $token ) {
        return new WP_Error( 'auth_failed', __( 'Unable to authenticate with Marketo.', 'advanced-form-integration' ) );
    }

    $base = sprintf( 'https://%s/rest/v1/', $host );
    $url  = $base . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
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

function adfoin_marketo_get_token( $credentials ) {
    $host      = isset( $credentials['instanceHost'] ) ? rtrim( $credentials['instanceHost'], '/' ) : '';
    $client_id = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $secret    = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';

    if ( ! $host || ! $client_id || ! $secret ) {
        return '';
    }

    $cache_key = 'adfoin_marketo_token_' . md5( $host . $client_id );
    $cached    = get_transient( $cache_key );

    if ( $cached ) {
        return $cached;
    }

    $identity_url = sprintf( 'https://%s/identity/oauth/token', $host );
    $identity_url = add_query_arg(
        array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $client_id,
            'client_secret' => $secret,
        ),
        $identity_url
    );

    $response = wp_remote_get( $identity_url, array( 'timeout' => 30 ) );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code || empty( $body['access_token'] ) ) {
        return '';
    }

    $token   = sanitize_text_field( $body['access_token'] );
    $expires = isset( $body['expires_in'] ) ? absint( $body['expires_in'] ) : 3500;

    set_transient( $cache_key, $token, max( 60, $expires - 60 ) );

    return $token;
}

function adfoin_marketo_credentials_list() {
    foreach ( adfoin_read_credentials( 'marketo' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
