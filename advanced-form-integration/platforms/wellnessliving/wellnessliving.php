<?php

add_filter( 'adfoin_action_providers', 'adfoin_wellnessliving_actions', 10, 1 );
function adfoin_wellnessliving_actions( $actions ) {
    $actions['wellnessliving'] = array(
        'title' => 'WellnessLiving',
        'tasks' => array( 'create_client' => 'Create Client' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_wellnessliving_settings_tab', 10, 1 );
function adfoin_wellnessliving_settings_tab( $providers ) {
    $providers['wellnessliving'] = 'WellnessLiving';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_wellnessliving_settings_view', 10, 1 );
function adfoin_wellnessliving_settings_view( $current_tab ) {
    if ( $current_tab !== 'wellnessliving' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'wellnessliving',
        'fields'   => array(
            array( 'key' => 'apiId',     'label' => __( 'API ID', 'advanced-form-integration' ) ),
            array( 'key' => 'apiSecret', 'label' => __( 'API Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'businessId','label' => __( 'Business ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In WellnessLiving, contact your account manager to obtain API ID, API Secret, and Business ID for integration.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'WellnessLiving', 'advanced-form-integration' ), 'wellnessliving', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_wellnessliving_action_fields' );
function adfoin_wellnessliving_action_fields() {
    ?>
    <script type="text/template" id="wellnessliving-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_client'">
                <td scope="row-title"><label><?php esc_attr_e( 'WellnessLiving Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_wellnessliving_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_client', 'WellnessLiving [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_wellnessliving_credentials', 'adfoin_get_wellnessliving_credentials' );
function adfoin_get_wellnessliving_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'wellnessliving' ) );
}

add_action( 'wp_ajax_adfoin_save_wellnessliving_credentials', 'adfoin_save_wellnessliving_credentials' );
function adfoin_save_wellnessliving_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'wellnessliving' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'wellnessliving', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_wellnessliving_fields', 'adfoin_get_wellnessliving_fields' );
function adfoin_get_wellnessliving_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'cellPhone', 'value' => 'Cell Phone', 'description' => '' ),
        array( 'key' => 'birthDate', 'value' => 'Birth Date', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',    'value' => 'Gender',     'description' => 'M / F / O' ),
        array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State / Province', 'description' => '' ),
        array( 'key' => 'postal',    'value' => 'Postal / Zip', 'description' => '' ),
        array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'sourceCode','value' => 'Source Code','description' => '' ),
        array( 'key' => 'referredBy','value' => 'Referred By','description' => '' ),
        array( 'key' => 'emergencyContact','value' => 'Emergency Contact', 'description' => '' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_wellnessliving_credentials_list() {
    foreach ( adfoin_read_credentials( 'wellnessliving' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_wellnessliving_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'wellnessliving', $cred_id );
    $api_id      = isset( $credentials['apiId'] )      ? $credentials['apiId']      : '';
    $api_secret  = isset( $credentials['apiSecret'] )  ? $credentials['apiSecret']  : '';
    $business_id = isset( $credentials['businessId'] ) ? $credentials['businessId'] : '';

    if ( ! $api_id || ! $api_secret || ! $business_id ) return;

    $url   = 'https://api.wellnessliving.com/' . $endpoint;
    $nonce = wp_generate_uuid4();
    $time  = gmdate( 'Y-m-d\TH:i:s\Z' );
    $body  = wp_json_encode( $data );
    $sig   = base64_encode( hash_hmac( 'sha256', $api_id . $time . $nonce . $body, $api_secret, true ) );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Wellnessliving ' . $sig,
            'X-API-Id'      => $api_id,
            'X-API-Nonce'   => $nonce,
            'X-API-Time'    => $time,
            'X-Business-Id' => $business_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = $body;
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_wellnessliving_create_client( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'cellPhone', 'birthDate', 'gender', 'sourceCode', 'referredBy', 'emergencyContact', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $address = array();
    foreach ( array( 'address', 'city', 'state', 'postal', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $address[ $k ] = $fields[ $k ];
    }
    if ( $address ) $body['address'] = $address;
    return adfoin_wellnessliving_request( 'client/add', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_wellnessliving_job_queue', 'adfoin_wellnessliving_job_queue', 10, 1 );
function adfoin_wellnessliving_job_queue( $data ) {
    adfoin_wellnessliving_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_wellnessliving_send_data( $record, $posted_data ) {
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
        adfoin_wellnessliving_create_client( $fields, $record, $cred_id );
    }
}
