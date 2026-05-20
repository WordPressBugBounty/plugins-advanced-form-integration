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
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Practice Better, go to Settings > Account > API. Generate an API key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Practice Better', 'advanced-form-integration' ), 'practicebetter', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_practicebetter_action_fields' );
function adfoin_practicebetter_action_fields() {
    ?>
    <script type="text/template" id="practicebetter-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
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
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'practicebetter' ) );
}

add_action( 'wp_ajax_adfoin_save_practicebetter_credentials', 'adfoin_save_practicebetter_credentials' );
function adfoin_save_practicebetter_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'practicebetter' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'practicebetter', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_practicebetter_fields', 'adfoin_get_practicebetter_fields' );
function adfoin_get_practicebetter_fields() {
    if ( ! adfoin_verify_nonce() ) return;
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

function adfoin_practicebetter_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'practicebetter', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) return;

    $url  = 'https://api.practicebetter.io/v1/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

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
