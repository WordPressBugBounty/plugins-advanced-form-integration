<?php

add_filter( 'adfoin_action_providers', 'adfoin_practicebetter_actions', 10, 1 );
function adfoin_practicebetter_actions( $actions ) {
    $actions['practicebetter'] = array(
        'title' => 'Practice Better',
        'tasks' => array( 'create_client' => 'Create Client' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_practicebetter_settings_tab', 10, 1 );
function adfoin_practicebetter_settings_tab( $providers ) {
    $providers['practicebetter'] = 'Practice Better';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_practicebetter_settings_view', 10, 1 );
function adfoin_practicebetter_settings_view( $current_tab ) {
    if ( $current_tab !== 'practicebetter' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'practicebetter',
        'fields'   => array(
            array( 'key' => 'clientId',     'label' => __( 'Client ID', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'accessToken',  'label' => __( 'Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'refreshToken', 'label' => __( 'Refresh Token', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'Practice Better\'s API requires the paid API Access add-on (My Profile > My Subscription > View add-ons). Create an API Key to get a Client ID and Secret, then use the Auth Token page in your Practice Better portal to generate an Access Token and Refresh Token. Paste all four here — the plugin will silently refresh the access token using the refresh token once it expires.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Practice Better', 'advanced-form-integration' ), 'practicebetter', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_practicebetter_action_fields' );
function adfoin_practicebetter_action_fields() {
    ?>
    <script type="text/template" id="practicebetter-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_client'">
                <td scope="row-title"><label><?php esc_attr_e( 'Practice Better Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_practicebetter_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_client', 'Practice Better [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_practicebetter_credentials', 'adfoin_get_practicebetter_credentials' );
function adfoin_get_practicebetter_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'practicebetter' ) );
}

add_action( 'wp_ajax_adfoin_save_practicebetter_credentials', 'adfoin_save_practicebetter_credentials' );
function adfoin_save_practicebetter_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'practicebetter' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'practicebetter', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_practicebetter_fields', 'adfoin_get_practicebetter_fields' );
function adfoin_get_practicebetter_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'dob',       'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',    'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State / Province', 'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Postal / Zip', 'description' => '' ),
        array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'timezone',  'value' => 'Timezone',   'description' => 'e.g. America/New_York' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_practicebetter_credentials_list() {
    foreach ( adfoin_read_credentials( 'practicebetter' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Practice Better access tokens are short-lived (OAuth2 authorization_code
 * grant). Exchange the stored refresh_token for a new access_token and
 * persist both back into the credential record.
 * @link https://help.practicebetter.io/hc/en-us/articles/16637584053275
 */
function adfoin_practicebetter_refresh_token( $credentials ) {
    $client_id     = isset( $credentials['clientId'] )     ? $credentials['clientId']     : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
    $refresh_token = isset( $credentials['refreshToken'] ) ? $credentials['refreshToken'] : '';
    $cred_id       = isset( $credentials['id'] )           ? $credentials['id']           : '';

    if ( ! $client_id || ! $client_secret || ! $refresh_token || ! $cred_id ) return '';

    $response = wp_remote_post( 'https://practicebetter.io/oauth/token', array(
        'timeout' => 30,
        'body'    => array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ),
    ) );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return '';
    }

    $body         = json_decode( wp_remote_retrieve_body( $response ), true );
    $access_token = isset( $body['access_token'] ) ? $body['access_token'] : '';
    if ( ! $access_token ) return '';

    $all = adfoin_read_credentials( 'practicebetter' );
    if ( is_array( $all ) ) {
        foreach ( $all as &$cred ) {
            if ( isset( $cred['id'] ) && (string) $cred['id'] === (string) $cred_id ) {
                $cred['accessToken'] = $access_token;
                if ( ! empty( $body['refresh_token'] ) ) {
                    $cred['refreshToken'] = $body['refresh_token'];
                }
                break;
            }
        }
        unset( $cred );
        adfoin_save_credentials( 'practicebetter', $all );
    }

    return $access_token;
}

function adfoin_practicebetter_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials  = adfoin_get_credentials_by_id( 'practicebetter', $cred_id );
    $access_token = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';

    if ( ! $access_token ) return;

    // Base URL and Bearer scheme confirmed against Practice Better's API
    // docs/integration guides (api.practicebetter.io/v1, Authorization:
    // Bearer <token>). https://help.practicebetter.io/hc/en-us/articles/16637584053275
    $url  = 'https://api.practicebetter.io/v1/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );

    $response = wp_remote_request( $url, $args );

    // Access tokens are short-lived — refresh once on 401 and retry.
    if ( 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $new_token = adfoin_practicebetter_refresh_token( $credentials );
        if ( $new_token ) {
            $args['headers']['Authorization'] = 'Bearer ' . $new_token;
            $response = wp_remote_request( $url, $args );
        }
    }

    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * Field names here are a best-effort mapping (firstName/lastName/email/etc.)
 * — Practice Better's exact Client schema is only viewable from within a
 * paid account's own API docs page, which isn't publicly reachable. If
 * creation 4xx's, check the logged response body for the real field names
 * and adjust this mapping.
 */
function adfoin_practicebetter_create_client( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'dob', 'gender', 'timezone', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $address = array();
    foreach ( array( 'address', 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $address[ $k ] = $fields[ $k ];
    }
    if ( $address ) $body['address'] = $address;
    return adfoin_practicebetter_request( 'clients', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_practicebetter_job_queue', 'adfoin_practicebetter_job_queue', 10, 1 );
function adfoin_practicebetter_job_queue( $data ) {
    adfoin_practicebetter_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_practicebetter_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_client' ) {
        adfoin_practicebetter_create_client( $fields, $record, $cred_id );
    }
}
