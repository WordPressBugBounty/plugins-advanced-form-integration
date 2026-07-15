<?php

add_filter( 'adfoin_action_providers', 'adfoin_filevine_actions', 10, 1 );
function adfoin_filevine_actions( $actions ) {
    $actions['filevine'] = array(
        'title' => 'Filevine',
        'tasks' => array(
            'create_contact' => 'Create Contact',
            'create_project' => 'Create Project (Case)',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_filevine_settings_tab', 10, 1 );
function adfoin_filevine_settings_tab( $providers ) {
    $providers['filevine'] = 'Filevine';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_filevine_settings_view', 10, 1 );
function adfoin_filevine_settings_view( $current_tab ) {
    if ( $current_tab !== 'filevine' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'filevine',
        'fields'   => array(
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'apiSecret', 'label' => __( 'API Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'orgId',     'label' => __( 'Org ID', 'advanced-form-integration' ) ),
            array( 'key' => 'userId',    'label' => __( 'User ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In the Filevine Developer Portal, create an API integration to get an API Key and Secret. Org ID and User ID identify the integration user context. The plugin exchanges the Key/Secret for an access token automatically (client_credentials grant) and refreshes it as needed.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Filevine', 'advanced-form-integration' ), 'filevine', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_filevine_action_fields' );
function adfoin_filevine_action_fields() {
    ?>
    <script type="text/template" id="filevine-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_project'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_project'">
                <td scope="row-title"><label><?php esc_attr_e( 'Filevine Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_filevine_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Filevine [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_filevine_credentials', 'adfoin_get_filevine_credentials' );
function adfoin_get_filevine_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'filevine' ) );
}

add_action( 'wp_ajax_adfoin_save_filevine_credentials', 'adfoin_save_filevine_credentials' );
function adfoin_save_filevine_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'filevine' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'filevine', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_filevine_fields', 'adfoin_get_filevine_fields' );
function adfoin_get_filevine_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( $task === 'create_project' ) {
        $fields = array(
            array( 'key' => 'projectName', 'value' => 'Project Name', 'description' => '' ),
            array( 'key' => 'projectTypeId','value' => 'Project Type ID', 'description' => '' ),
            array( 'key' => 'clientEmail','value' => 'Client Email',  'description' => 'Used to find linked client contact' ),
            array( 'key' => 'phaseName',  'value' => 'Phase Name',    'description' => 'Initial phase' ),
            array( 'key' => 'description','value' => 'Description',   'description' => '' ),
            array( 'key' => 'incidentDate','value' => 'Incident Date','description' => 'YYYY-MM-DD' ),
            array( 'key' => 'note',       'value' => 'Note',          'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'mobile',    'value' => 'Mobile',     'description' => '' ),
            array( 'key' => 'fullName',  'value' => 'Full Name (org contact)', 'description' => '' ),
            array( 'key' => 'personType','value' => 'Person Type','description' => 'individual / organization' ),
            array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_filevine_credentials_list() {
    foreach ( adfoin_read_credentials( 'filevine' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Filevine uses OAuth2 client_credentials (API Key + Secret exchanged for a
 * ~1-hour bearer token via https://auth.filevine.io/connect/token) — not a
 * manually-pasted static token. Cache per-credential in a transient.
 * @link https://developer.filevine.io/docs/v2-us/branches/main/e0f5ad7e2c916-authentication
 */
function adfoin_filevine_get_token( $cred_id ) {
    $cache_key = 'adfoin_filevine_token_' . md5( $cred_id );
    $cached    = get_transient( $cache_key );
    if ( $cached ) return $cached;

    $credentials = adfoin_get_credentials_by_id( 'filevine', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    $api_secret  = isset( $credentials['apiSecret'] ) ? $credentials['apiSecret'] : '';

    if ( ! $api_key || ! $api_secret ) return '';

    $response = wp_remote_post( 'https://auth.filevine.io/connect/token', array(
        'timeout' => 30,
        'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
        'body'    => http_build_query( array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $api_key,
            'client_secret' => $api_secret,
        ) ),
    ) );
    if ( is_wp_error( $response ) ) return '';
    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $token = isset( $body['access_token'] ) ? $body['access_token'] : '';
    if ( $token ) {
        $ttl = isset( $body['expires_in'] ) ? max( 60, (int) $body['expires_in'] - 60 ) : 300;
        set_transient( $cache_key, $token, $ttl );
    }
    return $token;
}

function adfoin_filevine_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'filevine', $cred_id );
    $org_id      = isset( $credentials['orgId'] )  ? $credentials['orgId']  : '';
    $user_id     = isset( $credentials['userId'] ) ? $credentials['userId'] : '';

    $token = adfoin_filevine_get_token( $cred_id );
    if ( ! $token ) return;

    // Confirmed base host is api.filevine.io (fv-app/api.filevineapp.com
    // does not exist).
    // https://developer.filevine.io/docs/v2-us
    $url  = 'https://api.filevine.io/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'x-fv-orgid'    => $org_id,
            'x-fv-userid'   => $user_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_filevine_create_contact( $fields, $record, $cred_id ) {
    $person = array();
    foreach ( array( 'firstName' => 'firstName', 'middleName' => 'middleName', 'lastName' => 'lastName', 'fullName' => 'fullName' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $person[ $remote ] = $fields[ $local ];
    }
    // Confirmed field is `personTypes` (array), not `personType`.
    if ( ! empty( $fields['personType'] ) ) $person['personTypes'] = array( $fields['personType'] );
    $emails = array();
    if ( ! empty( $fields['email'] ) ) $emails[] = array( 'email' => $fields['email'], 'isPrimary' => true );
    if ( $emails ) $person['emails'] = $emails;

    $phones = array();
    if ( ! empty( $fields['phone'] ) )  $phones[] = array( 'number' => $fields['phone'],  'type' => 'work', 'isPrimary' => true );
    if ( ! empty( $fields['mobile'] ) ) $phones[] = array( 'number' => $fields['mobile'], 'type' => 'mobile' );
    if ( $phones ) $person['phones'] = $phones;

    $addr = array();
    foreach ( array( 'address' => 'street1', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $person['addresses'] = array( $addr );

    if ( ! empty( $fields['note'] ) ) $person['notes'] = $fields['note'];

    return adfoin_filevine_request( 'core/contacts', 'POST', $person, $record, $cred_id );
}

function adfoin_filevine_create_project( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'projectName' => 'projectName', 'projectTypeId' => 'projectTypeId', 'phaseName' => 'phaseName', 'description' => 'description', 'incidentDate' => 'incidentDate', 'note' => 'notes', 'clientEmail' => 'clientEmail' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    return adfoin_filevine_request( 'core/projects', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_filevine_job_queue', 'adfoin_filevine_job_queue', 10, 1 );
function adfoin_filevine_job_queue( $data ) {
    adfoin_filevine_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_filevine_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_contact' ) {
        adfoin_filevine_create_contact( $fields, $record, $cred_id );
    } elseif ( $record['task'] === 'create_project' ) {
        adfoin_filevine_create_project( $fields, $record, $cred_id );
    }
}
