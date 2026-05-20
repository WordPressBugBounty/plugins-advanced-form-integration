<?php

add_filter( 'adfoin_action_providers', 'adfoin_housecallpro_actions', 10, 1 );
function adfoin_housecallpro_actions( $actions ) {
    $actions['housecallpro'] = array(
        'title' => 'Housecall Pro',
        'tasks' => array(
            'create_customer' => 'Create Customer',
            'create_lead'     => 'Create Lead',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_housecallpro_settings_tab', 10, 1 );
function adfoin_housecallpro_settings_tab( $providers ) {
    $providers['housecallpro'] = 'Housecall Pro';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_housecallpro_settings_view', 10, 1 );
function adfoin_housecallpro_settings_view( $current_tab ) {
    if ( $current_tab !== 'housecallpro' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'housecallpro',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Housecall Pro, go to Settings > Integrations > API. Generate an API key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Housecall Pro', 'advanced-form-integration' ), 'housecallpro', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_housecallpro_action_fields' );
function adfoin_housecallpro_action_fields() {
    ?>
    <script type="text/template" id="housecallpro-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_customer' || action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_customer' || action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Housecall Pro Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_housecallpro_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_customer', 'Housecall Pro [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_housecallpro_credentials', 'adfoin_get_housecallpro_credentials' );
function adfoin_get_housecallpro_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'housecallpro' ) );
}

add_action( 'wp_ajax_adfoin_save_housecallpro_credentials', 'adfoin_save_housecallpro_credentials' );
function adfoin_save_housecallpro_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'housecallpro' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'housecallpro', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_housecallpro_fields', 'adfoin_get_housecallpro_fields' );
function adfoin_get_housecallpro_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',    'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
        array( 'key' => 'address',   'value' => 'Street',     'description' => '' ),
        array( 'key' => 'address2',  'value' => 'Street 2',   'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'leadSource','value' => 'Lead Source','description' => '' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_housecallpro_credentials_list() {
    foreach ( adfoin_read_credentials( 'housecallpro' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_housecallpro_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'housecallpro', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) return;

    $url  = 'https://api.housecallpro.com/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Token ' . $api_key,
            'Accept'        => 'application/vnd.housecallpro.com.v1+json',
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_housecallpro_build_customer( $fields ) {
    $body = array();
    foreach ( array( 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'company' => 'company', 'leadSource' => 'lead_source', 'note' => 'notes' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    $phones = array();
    if ( ! empty( $fields['phone'] ) )  $phones[] = array( 'number' => $fields['phone'],  'type' => 'work' );
    if ( ! empty( $fields['mobile'] ) ) $phones[] = array( 'number' => $fields['mobile'], 'type' => 'mobile' );
    if ( $phones ) $body['mobile_numbers'] = $phones;

    $addr = array();
    foreach ( array( 'address' => 'street', 'address2' => 'street_line_2', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['addresses'] = array( $addr );
    return $body;
}

add_action( 'adfoin_housecallpro_job_queue', 'adfoin_housecallpro_job_queue', 10, 1 );
function adfoin_housecallpro_job_queue( $data ) {
    adfoin_housecallpro_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_housecallpro_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    $body = adfoin_housecallpro_build_customer( $fields );
    if ( $record['task'] === 'create_customer' ) {
        adfoin_housecallpro_request( 'customers', 'POST', $body, $record, $cred_id );
    } elseif ( $record['task'] === 'create_lead' ) {
        $body['lead'] = true;
        adfoin_housecallpro_request( 'customers', 'POST', $body, $record, $cred_id );
    }
}
