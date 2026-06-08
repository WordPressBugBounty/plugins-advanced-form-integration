<?php

add_filter( 'adfoin_action_providers', 'adfoin_skyslope_actions', 10, 1 );
function adfoin_skyslope_actions( $actions ) {
    $actions['skyslope'] = array(
        'title' => 'SkySlope',
        'tasks' => array( 'create_listing' => 'Create Listing / Transaction' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_skyslope_settings_tab', 10, 1 );
function adfoin_skyslope_settings_tab( $providers ) { $providers['skyslope'] = 'SkySlope'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_skyslope_settings_view', 10, 1 );
function adfoin_skyslope_settings_view( $current_tab ) {
    if ( $current_tab !== 'skyslope' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'skyslope',
        'fields'   => array(
            array( 'key' => 'apiKey',     'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'companyId',  'label' => __( 'Company ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'SkySlope API access is granted through their partner program. Request an API key and Company ID from your SkySlope account manager.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'SkySlope', 'advanced-form-integration' ), 'skyslope', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_skyslope_action_fields' );
function adfoin_skyslope_action_fields() {
    ?>
    <script type="text/template" id="skyslope-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_listing'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_listing'">
                <td scope="row-title"><label><?php esc_attr_e( 'SkySlope Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_skyslope_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_listing', 'SkySlope [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_skyslope_credentials', 'adfoin_get_skyslope_credentials' );
function adfoin_get_skyslope_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'skyslope' ) );
}

add_action( 'wp_ajax_adfoin_save_skyslope_credentials', 'adfoin_save_skyslope_credentials' );
function adfoin_save_skyslope_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'skyslope' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'skyslope', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_skyslope_fields', 'adfoin_get_skyslope_fields' );
function adfoin_get_skyslope_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'listingType',     'value' => 'Listing Type',     'description' => 'Listing / Sale / Lease' ),
        array( 'key' => 'mlsNumber',       'value' => 'MLS Number',       'description' => '' ),
        array( 'key' => 'propertyAddress', 'value' => 'Property Address', 'description' => '' ),
        array( 'key' => 'propertyCity',    'value' => 'Property City',    'description' => '' ),
        array( 'key' => 'propertyState',   'value' => 'Property State',   'description' => '' ),
        array( 'key' => 'propertyZip',     'value' => 'Property Zip',     'description' => '' ),
        array( 'key' => 'listPrice',       'value' => 'List Price',       'description' => '' ),
        array( 'key' => 'salePrice',       'value' => 'Sale Price',       'description' => '' ),
        array( 'key' => 'listingDate',     'value' => 'Listing Date',     'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'closingDate',     'value' => 'Closing Date',     'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'agentEmail',      'value' => 'Agent Email',      'description' => '' ),
        array( 'key' => 'clientFirstName', 'value' => 'Client First Name', 'description' => '' ),
        array( 'key' => 'clientLastName',  'value' => 'Client Last Name', 'description' => '' ),
        array( 'key' => 'clientEmail',     'value' => 'Client Email',     'description' => '' ),
        array( 'key' => 'clientPhone',     'value' => 'Client Phone',     'description' => '' ),
        array( 'key' => 'note',            'value' => 'Note',             'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_skyslope_credentials_list() {
    foreach ( adfoin_read_credentials( 'skyslope' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_skyslope_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'skyslope', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    $company_id  = isset( $credentials['companyId'] ) ? $credentials['companyId'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.skyslope.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Company-Id'  => $company_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_skyslope_job_queue', 'adfoin_skyslope_job_queue', 10, 1 );
function adfoin_skyslope_job_queue( $data ) {
    adfoin_skyslope_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_skyslope_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_listing' ) return;

    $body = array();
    foreach ( array( 'listingType', 'mlsNumber', 'listPrice', 'salePrice', 'listingDate', 'closingDate', 'agentEmail', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $prop = array();
    foreach ( array( 'propertyAddress' => 'street', 'propertyCity' => 'city', 'propertyState' => 'state', 'propertyZip' => 'zip' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $prop[ $remote ] = $fields[ $local ];
    }
    if ( $prop ) $body['propertyAddress'] = $prop;
    $client = array();
    foreach ( array( 'clientFirstName' => 'firstName', 'clientLastName' => 'lastName', 'clientEmail' => 'email', 'clientPhone' => 'phone' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $client[ $remote ] = $fields[ $local ];
    }
    if ( $client ) $body['client'] = $client;

    adfoin_skyslope_request( 'listings', 'POST', $body, $record, $cred_id );
}
