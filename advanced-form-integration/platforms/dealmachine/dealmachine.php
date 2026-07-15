<?php

add_filter( 'adfoin_action_providers', 'adfoin_dealmachine_actions', 10, 1 );
function adfoin_dealmachine_actions( $actions ) {
    $actions['dealmachine'] = array(
        'title' => 'DealMachine',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_dealmachine_settings_tab', 10, 1 );
function adfoin_dealmachine_settings_tab( $providers ) { $providers['dealmachine'] = 'DealMachine'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_dealmachine_settings_view', 10, 1 );
function adfoin_dealmachine_settings_view( $current_tab ) {
    if ( $current_tab !== 'dealmachine' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'dealmachine',
        'fields'   => array(
            array( 'key' => 'apiKey',  'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In DealMachine, open Automation > API Docs to find/generate your API key.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'DealMachine', 'advanced-form-integration' ), 'dealmachine', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_dealmachine_action_fields' );
function adfoin_dealmachine_action_fields() {
    ?>
    <script type="text/template" id="dealmachine-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'DealMachine Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_dealmachine_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'DealMachine [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_dealmachine_credentials', 'adfoin_get_dealmachine_credentials' );
function adfoin_get_dealmachine_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'dealmachine' ) );
}

add_action( 'wp_ajax_adfoin_save_dealmachine_credentials', 'adfoin_save_dealmachine_credentials' );
function adfoin_save_dealmachine_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'dealmachine' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'dealmachine', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_dealmachine_fields', 'adfoin_get_dealmachine_fields' );
function adfoin_get_dealmachine_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'propertyAddress','value' => 'Property Address', 'description' => '' ),
        array( 'key' => 'propertyCity','value' => 'Property City', 'description' => '' ),
        array( 'key' => 'propertyState','value' => 'Property State', 'description' => '' ),
        array( 'key' => 'propertyZip', 'value' => 'Property Zip', 'description' => '' ),
        array( 'key' => 'askingPrice', 'value' => 'Asking Price', 'description' => '' ),
        array( 'key' => 'motivation',  'value' => 'Seller Motivation', 'description' => '' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_dealmachine_credentials_list() {
    foreach ( adfoin_read_credentials( 'dealmachine' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Confirmed via DealMachine's official Postman collection
 * (documenter.getpostman.com/view/10528472/TzzBrbnN): base path is
 * /public/v1/ (not /v1/), and every request body — including the initial
 * "Add A Lead" call — is sent as form data, not JSON.
 */
function adfoin_dealmachine_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'dealmachine', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.dealmachine.com/public/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = $data;
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * DealMachine's "Add A Lead" only accepts the PROPERTY location (parsed
 * address, lat/lng, or a full address string) — it has no first_name,
 * email, phone, price, or note fields at all. DealMachine looks up the
 * owner/contact info itself via skip-tracing after the lead is added.
 * Contact/deal details a form collects are instead attached as a Note via
 * the separate POST /leads/{id}/create-note endpoint.
 */
function adfoin_dealmachine_build_address( $fields ) {
    $addr = array();
    foreach ( array( 'propertyAddress' => 'address', 'propertyCity' => 'city', 'propertyState' => 'state', 'propertyZip' => 'zip' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    return $addr;
}

function adfoin_dealmachine_build_note( $fields, $labels ) {
    $lines = array();
    foreach ( $labels as $key => $label ) {
        if ( ! empty( $fields[ $key ] ) ) $lines[] = "{$label}: {$fields[ $key ]}";
    }
    return implode( "\n", $lines );
}

function adfoin_dealmachine_create_lead( $fields, $labels, $record, $cred_id ) {
    $addr = adfoin_dealmachine_build_address( $fields );
    if ( count( $addr ) < 4 ) return; // address/city/state/zip are all required for a parsed address.

    $response = adfoin_dealmachine_request( 'leads/', 'POST', $addr, $record, $cred_id );
    if ( is_wp_error( $response ) ) return;
    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $lead_id = isset( $body['id'] ) ? $body['id'] : ( isset( $body['data']['id'] ) ? $body['data']['id'] : '' );
    if ( ! $lead_id ) return;

    $note = adfoin_dealmachine_build_note( $fields, $labels );
    if ( $note !== '' ) {
        adfoin_dealmachine_request( "leads/{$lead_id}/create-note", 'POST', array( 'note' => $note ), $record, $cred_id );
    }
    return $lead_id;
}

add_action( 'adfoin_dealmachine_job_queue', 'adfoin_dealmachine_job_queue', 10, 1 );
function adfoin_dealmachine_job_queue( $data ) {
    adfoin_dealmachine_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_dealmachine_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_lead' ) return;

    $labels = array(
        'firstName'   => 'First Name',
        'lastName'    => 'Last Name',
        'email'       => 'Email',
        'phone'       => 'Phone',
        'askingPrice' => 'Asking Price',
        'motivation'  => 'Seller Motivation',
        'leadSource'  => 'Lead Source',
        'note'        => 'Note',
    );

    adfoin_dealmachine_create_lead( $fields, $labels, $record, $cred_id );
}
