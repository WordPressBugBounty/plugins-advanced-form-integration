<?php

add_filter( 'adfoin_action_providers', 'adfoin_classpass_actions', 10, 1 );
function adfoin_classpass_actions( $actions ) {
    $actions['classpass'] = array(
        'title' => 'ClassPass',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_classpass_settings_tab', 10, 1 );
function adfoin_classpass_settings_tab( $providers ) {
    $providers['classpass'] = 'ClassPass';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_classpass_settings_view', 10, 1 );
function adfoin_classpass_settings_view( $current_tab ) {
    if ( $current_tab !== 'classpass' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'classpass',
        'fields'   => array(
            array( 'key' => 'apiKey',   'label' => __( 'Partner API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'venueId',  'label' => __( 'Venue ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'ClassPass exposes lead capture only through their Partner Program. Request your Partner API key and Venue ID from your ClassPass account manager.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'ClassPass', 'advanced-form-integration' ), 'classpass', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_classpass_action_fields' );
function adfoin_classpass_action_fields() {
    ?>
    <script type="text/template" id="classpass-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'ClassPass Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_classpass_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'ClassPass [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_classpass_credentials', 'adfoin_get_classpass_credentials' );
function adfoin_get_classpass_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'classpass' ) );
}

add_action( 'wp_ajax_adfoin_save_classpass_credentials', 'adfoin_save_classpass_credentials' );
function adfoin_save_classpass_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'classpass' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'classpass', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_classpass_fields', 'adfoin_get_classpass_fields' );
function adfoin_get_classpass_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'source',    'value' => 'Source',     'description' => '' ),
        array( 'key' => 'interest',  'value' => 'Interest',   'description' => 'yoga, pilates, strength, etc.' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_classpass_credentials_list() {
    foreach ( adfoin_read_credentials( 'classpass' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_classpass_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'classpass', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )  ? $credentials['apiKey']  : '';
    $venue_id    = isset( $credentials['venueId'] ) ? $credentials['venueId'] : '';

    if ( ! $api_key || ! $venue_id ) return;

    $url  = 'https://api.classpass.com/partner/v1/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Venue-Id'    => $venue_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_classpass_create_lead( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'source', 'interest', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $location = array();
    foreach ( array( 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $location[ $k ] = $fields[ $k ];
    }
    if ( $location ) $body['location'] = $location;
    return adfoin_classpass_request( 'leads', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_classpass_job_queue', 'adfoin_classpass_job_queue', 10, 1 );
function adfoin_classpass_job_queue( $data ) {
    adfoin_classpass_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_classpass_send_data( $record, $posted_data ) {
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
        adfoin_classpass_create_lead( $fields, $record, $cred_id );
    }
}
