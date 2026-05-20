<?php

add_filter( 'adfoin_action_providers', 'adfoin_servicetitan_actions', 10, 1 );
function adfoin_servicetitan_actions( $actions ) {
    $actions['servicetitan'] = array(
        'title' => 'ServiceTitan',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_servicetitan_settings_tab', 10, 1 );
function adfoin_servicetitan_settings_tab( $providers ) {
    $providers['servicetitan'] = 'ServiceTitan';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_servicetitan_settings_view', 10, 1 );
function adfoin_servicetitan_settings_view( $current_tab ) {
    if ( $current_tab !== 'servicetitan' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'servicetitan',
        'fields'   => array(
            array( 'key' => 'appKey',      'label' => __( 'App Key (ST-App-Key)', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'tenantId',    'label' => __( 'Tenant ID', 'advanced-form-integration' ) ),
            array( 'key' => 'clientId',    'label' => __( 'Client ID', 'advanced-form-integration' ) ),
            array( 'key' => 'clientSecret','label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'environment', 'label' => __( 'Environment (production / integration)', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In the ServiceTitan Developer Portal, create an Integration App and request access from your customer. You will receive an App Key, Client ID/Secret, and a Tenant ID. Use "production" or "integration" for the environment.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'ServiceTitan', 'advanced-form-integration' ), 'servicetitan', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_servicetitan_action_fields' );
function adfoin_servicetitan_action_fields() {
    ?>
    <script type="text/template" id="servicetitan-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'ServiceTitan Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_servicetitan_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'ServiceTitan [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_servicetitan_credentials', 'adfoin_get_servicetitan_credentials' );
function adfoin_get_servicetitan_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'servicetitan' ) );
}

add_action( 'wp_ajax_adfoin_save_servicetitan_credentials', 'adfoin_save_servicetitan_credentials' );
function adfoin_save_servicetitan_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'servicetitan' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'servicetitan', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_servicetitan_fields', 'adfoin_get_servicetitan_fields' );
function adfoin_get_servicetitan_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name',   'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',    'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',        'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',        'description' => '' ),
        array( 'key' => 'address',     'value' => 'Street',       'description' => '' ),
        array( 'key' => 'unit',        'value' => 'Unit',         'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',         'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',        'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',          'description' => '' ),
        array( 'key' => 'country',     'value' => 'Country',      'description' => 'USA / CAN' ),
        array( 'key' => 'businessUnitId','value' => 'Business Unit ID', 'description' => 'Required by ServiceTitan' ),
        array( 'key' => 'jobTypeId',   'value' => 'Job Type ID',  'description' => '' ),
        array( 'key' => 'campaignId',  'value' => 'Campaign ID',  'description' => '' ),
        array( 'key' => 'priority',    'value' => 'Priority',     'description' => 'low / normal / high / urgent' ),
        array( 'key' => 'summary',     'value' => 'Summary',      'description' => 'Lead summary / customer request' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_servicetitan_credentials_list() {
    foreach ( adfoin_read_credentials( 'servicetitan' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_servicetitan_get_token( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'servicetitan', $cred_id );
    $client_id     = isset( $credentials['clientId'] )     ? $credentials['clientId']     : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
    $environment   = isset( $credentials['environment'] )  ? $credentials['environment']  : 'production';

    if ( ! $client_id || ! $client_secret ) return '';

    $auth_url = ( $environment === 'integration' )
        ? 'https://auth-integration.servicetitan.io/connect/token'
        : 'https://auth.servicetitan.io/connect/token';

    $response = wp_remote_post( $auth_url, array(
        'timeout' => 30,
        'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
        'body'    => http_build_query( array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ) ),
    ) );
    if ( is_wp_error( $response ) ) return '';
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    return isset( $body['access_token'] ) ? $body['access_token'] : '';
}

function adfoin_servicetitan_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'servicetitan', $cred_id );
    $app_key     = isset( $credentials['appKey'] )      ? $credentials['appKey']      : '';
    $tenant_id   = isset( $credentials['tenantId'] )    ? $credentials['tenantId']    : '';
    $environment = isset( $credentials['environment'] ) ? $credentials['environment'] : 'production';

    if ( ! $app_key || ! $tenant_id ) return;

    $token = adfoin_servicetitan_get_token( $cred_id );
    if ( ! $token ) return;

    $base = ( $environment === 'integration' )
        ? 'https://api-integration.servicetitan.io/'
        : 'https://api.servicetitan.io/';
    $url  = $base . str_replace( '{tenant}', $tenant_id, ltrim( $endpoint, '/' ) );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'ST-App-Key'    => $app_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_servicetitan_create_lead( $fields, $record, $cred_id ) {
    $lead = array(
        'customerName' => trim( ( isset( $fields['firstName'] ) ? $fields['firstName'] : '' ) . ' ' . ( isset( $fields['lastName'] ) ? $fields['lastName'] : '' ) ),
        'summary'      => isset( $fields['summary'] ) ? $fields['summary'] : '',
    );
    foreach ( array( 'businessUnitId' => 'businessUnitId', 'jobTypeId' => 'jobTypeId', 'campaignId' => 'campaignId', 'priority' => 'priority' ) as $local => $remote ) {
        if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) {
            $lead[ $remote ] = in_array( $local, array( 'businessUnitId', 'jobTypeId', 'campaignId' ), true ) ? intval( $fields[ $local ] ) : $fields[ $local ];
        }
    }

    $contact = array();
    if ( ! empty( $fields['email'] ) ) $contact[] = array( 'type' => 'Email', 'value' => $fields['email'] );
    if ( ! empty( $fields['phone'] ) ) $contact[] = array( 'type' => 'Phone', 'value' => $fields['phone'] );
    if ( $contact ) $lead['contactInformation'] = $contact;

    $address = array();
    foreach ( array( 'address' => 'street', 'unit' => 'unit', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $address[ $remote ] = $fields[ $local ];
    }
    if ( $address ) $lead['locationAddress'] = $address;

    return adfoin_servicetitan_request( 'crm/v2/tenant/{tenant}/leads', 'POST', $lead, $record, $cred_id );
}

add_action( 'adfoin_servicetitan_job_queue', 'adfoin_servicetitan_job_queue', 10, 1 );
function adfoin_servicetitan_job_queue( $data ) {
    adfoin_servicetitan_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_servicetitan_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_lead' ) {
        adfoin_servicetitan_create_lead( $fields, $record, $cred_id );
    }
}
