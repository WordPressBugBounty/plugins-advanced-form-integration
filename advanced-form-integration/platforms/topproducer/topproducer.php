<?php

add_filter( 'adfoin_action_providers', 'adfoin_topproducer_actions', 10, 1 );
function adfoin_topproducer_actions( $actions ) {
    $actions['topproducer'] = array(
        'title' => 'Top Producer',
        'tasks' => array( 'create_contact' => 'Create Contact' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_topproducer_settings_tab', 10, 1 );
function adfoin_topproducer_settings_tab( $providers ) {
    $providers['topproducer'] = 'Top Producer';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_topproducer_settings_view', 10, 1 );
function adfoin_topproducer_settings_view( $current_tab ) {
    if ( $current_tab !== 'topproducer' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'topproducer',
        'fields'   => array(
            array( 'key' => 'apiKey',   'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'agentId',  'label' => __( 'Agent ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Top Producer, go to Setup > API Access. Create an API key and copy your Agent ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Top Producer', 'advanced-form-integration' ), 'topproducer', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_topproducer_action_fields' );
function adfoin_topproducer_action_fields() {
    ?>
    <script type="text/template" id="topproducer-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'Top Producer Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_topproducer_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Top Producer [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_topproducer_credentials', 'adfoin_get_topproducer_credentials' );
function adfoin_get_topproducer_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'topproducer' ) );
}

add_action( 'wp_ajax_adfoin_save_topproducer_credentials', 'adfoin_save_topproducer_credentials' );
function adfoin_save_topproducer_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'topproducer' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'topproducer', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_topproducer_fields', 'adfoin_get_topproducer_fields' );
function adfoin_get_topproducer_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',    'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'address',   'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'category',  'value' => 'Category',   'description' => 'buyer / seller / lead' ),
        array( 'key' => 'source',    'value' => 'Source',     'description' => '' ),
        array( 'key' => 'priceMin',  'value' => 'Price Min',  'description' => '' ),
        array( 'key' => 'priceMax',  'value' => 'Price Max',  'description' => '' ),
        array( 'key' => 'timeframe', 'value' => 'Timeframe',  'description' => '' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_topproducer_credentials_list() {
    foreach ( adfoin_read_credentials( 'topproducer' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_topproducer_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'topproducer', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $agent_id    = isset( $credentials['agentId'] ) ? $credentials['agentId'] : '';

    if ( ! $api_key ) return;

    $url  = 'https://api.topproducer.com/v3/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Agent-Id'    => $agent_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_topproducer_create_contact( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'mobile', 'category', 'source', 'timeframe', 'note', 'priceMin', 'priceMax' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $address = array();
    foreach ( array( 'address', 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $address[ $k ] = $fields[ $k ];
    }
    if ( $address ) $body['address'] = $address;
    return adfoin_topproducer_request( 'contacts', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_topproducer_job_queue', 'adfoin_topproducer_job_queue', 10, 1 );
function adfoin_topproducer_job_queue( $data ) {
    adfoin_topproducer_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_topproducer_send_data( $record, $posted_data ) {
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
        adfoin_topproducer_create_contact( $fields, $record, $cred_id );
    }
}
