<?php

add_filter( 'adfoin_action_providers', 'adfoin_hawksoft_actions', 10, 1 );
function adfoin_hawksoft_actions( $actions ) {
    $actions['hawksoft'] = array(
        'title' => 'HawkSoft',
        'tasks' => array( 'create_client' => 'Create Client' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_hawksoft_settings_tab', 10, 1 );
function adfoin_hawksoft_settings_tab( $providers ) { $providers['hawksoft'] = 'HawkSoft'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_hawksoft_settings_view', 10, 1 );
function adfoin_hawksoft_settings_view( $current_tab ) {
    if ( $current_tab !== 'hawksoft' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'hawksoft',
        'fields'   => array(
            array( 'key' => 'apiKey',   'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'agencyId', 'label' => __( 'Agency ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'HawkSoft API access is granted through their Integration Partner Program. Once approved, enter the API key and Agency ID provided by HawkSoft.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'HawkSoft', 'advanced-form-integration' ), 'hawksoft', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_hawksoft_action_fields' );
function adfoin_hawksoft_action_fields() {
    ?>
    <script type="text/template" id="hawksoft-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_client'">
                <td scope="row-title"><label><?php esc_attr_e( 'HawkSoft Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_hawksoft_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_client', 'HawkSoft [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_hawksoft_credentials', 'adfoin_get_hawksoft_credentials' );
function adfoin_get_hawksoft_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'hawksoft' ) );
}

add_action( 'wp_ajax_adfoin_save_hawksoft_credentials', 'adfoin_save_hawksoft_credentials' );
function adfoin_save_hawksoft_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'hawksoft' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'hawksoft', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_hawksoft_fields', 'adfoin_get_hawksoft_fields' );
function adfoin_get_hawksoft_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',      'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'dob',         'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',      'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'address',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'clientType',  'value' => 'Client Type','description' => 'individual / commercial' ),
        array( 'key' => 'lineOfBusiness','value' => 'Line of Business', 'description' => '' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_hawksoft_credentials_list() {
    foreach ( adfoin_read_credentials( 'hawksoft' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_hawksoft_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'hawksoft', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )   ? $credentials['apiKey']   : '';
    $agency_id   = isset( $credentials['agencyId'] ) ? $credentials['agencyId'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.hawksoft.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Agency-Id'   => $agency_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_hawksoft_job_queue', 'adfoin_hawksoft_job_queue', 10, 1 );
function adfoin_hawksoft_job_queue( $data ) {
    adfoin_hawksoft_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_hawksoft_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_client' ) return;

    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'mobile', 'dob', 'gender', 'clientType', 'lineOfBusiness', 'leadSource', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $addr = array();
    foreach ( array( 'address' => 'street', 'city' => 'city', 'state' => 'state', 'zip' => 'zip' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['address'] = $addr;

    adfoin_hawksoft_request( 'clients', 'POST', $body, $record, $cred_id );
}
