<?php

add_filter( 'adfoin_action_providers', 'adfoin_mycase_actions', 10, 1 );
function adfoin_mycase_actions( $actions ) {
    $actions['mycase'] = array(
        'title' => 'MyCase',
        'tasks' => array( 'create_contact' => 'Create Contact / Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mycase_settings_tab', 10, 1 );
function adfoin_mycase_settings_tab( $providers ) {
    $providers['mycase'] = 'MyCase';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mycase_settings_view', 10, 1 );
function adfoin_mycase_settings_view( $current_tab ) {
    if ( $current_tab !== 'mycase' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'mycase',
        'fields'   => array(
            array( 'key' => 'apiKey',    'label' => __( 'API Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'firmId',    'label' => __( 'Firm ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In MyCase, go to Settings > Apps & Integrations and generate an API token. Copy your Firm ID from the URL of your MyCase dashboard.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'MyCase', 'advanced-form-integration' ), 'mycase', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_mycase_action_fields' );
function adfoin_mycase_action_fields() {
    ?>
    <script type="text/template" id="mycase-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'MyCase Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mycase_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'MyCase [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mycase_credentials', 'adfoin_get_mycase_credentials' );
function adfoin_get_mycase_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'mycase' ) );
}

add_action( 'wp_ajax_adfoin_save_mycase_credentials', 'adfoin_save_mycase_credentials' );
function adfoin_save_mycase_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'mycase' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'mycase', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_mycase_fields', 'adfoin_get_mycase_fields' );
function adfoin_get_mycase_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',    'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
        array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'contactType','value' => 'Type',      'description' => 'client / prospect / lead' ),
        array( 'key' => 'caseType',  'value' => 'Case Type',  'description' => '' ),
        array( 'key' => 'leadSource','value' => 'Lead Source','description' => '' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_mycase_credentials_list() {
    foreach ( adfoin_read_credentials( 'mycase' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_mycase_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mycase', $cred_id );
    $token       = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $firm_id     = isset( $credentials['firmId'] ) ? $credentials['firmId'] : '';

    if ( ! $token ) return;

    $url  = 'https://api.mycase.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'X-Firm-Id'     => $firm_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_mycase_create_contact( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'firstName', 'middleName', 'lastName', 'email', 'phone', 'mobile', 'company', 'contactType', 'caseType', 'leadSource', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $address = array();
    foreach ( array( 'address', 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $address[ $k ] = $fields[ $k ];
    }
    if ( $address ) $body['address'] = $address;
    return adfoin_mycase_request( 'contacts', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_mycase_job_queue', 'adfoin_mycase_job_queue', 10, 1 );
function adfoin_mycase_job_queue( $data ) {
    adfoin_mycase_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mycase_send_data( $record, $posted_data ) {
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
        adfoin_mycase_create_contact( $fields, $record, $cred_id );
    }
}
