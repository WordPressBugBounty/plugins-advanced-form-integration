<?php

add_filter( 'adfoin_action_providers', 'adfoin_fundraiseup_actions', 10, 1 );
function adfoin_fundraiseup_actions( $actions ) {
    $actions['fundraiseup'] = array(
        'title' => 'Fundraise Up',
        'tasks' => array( 'create_supporter' => 'Create Supporter' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_fundraiseup_settings_tab', 10, 1 );
function adfoin_fundraiseup_settings_tab( $providers ) { $providers['fundraiseup'] = 'Fundraise Up'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_fundraiseup_settings_view', 10, 1 );
function adfoin_fundraiseup_settings_view( $current_tab ) {
    if ( $current_tab !== 'fundraiseup' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'fundraiseup',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Fundraise Up, go to Settings > Integrations > API Keys. Generate a key with Supporters scope and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Fundraise Up', 'advanced-form-integration' ), 'fundraiseup', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_fundraiseup_action_fields' );
function adfoin_fundraiseup_action_fields() {
    ?>
    <script type="text/template" id="fundraiseup-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_supporter'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_supporter'">
                <td scope="row-title"><label><?php esc_attr_e( 'Fundraise Up Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_fundraiseup_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_supporter', 'Fundraise Up [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_fundraiseup_credentials', 'adfoin_get_fundraiseup_credentials' );
function adfoin_get_fundraiseup_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'fundraiseup' ) );
}

add_action( 'wp_ajax_adfoin_save_fundraiseup_credentials', 'adfoin_save_fundraiseup_credentials' );
function adfoin_save_fundraiseup_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'fundraiseup' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'fundraiseup', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_fundraiseup_fields', 'adfoin_get_fundraiseup_fields' );
function adfoin_get_fundraiseup_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'company',    'value' => 'Company',    'description' => '' ),
        array( 'key' => 'address',    'value' => 'Address',    'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'postcode',   'value' => 'Postcode',   'description' => '' ),
        array( 'key' => 'country',    'value' => 'Country',    'description' => '' ),
        array( 'key' => 'opted_in_marketing','value' => 'Opted In Marketing (true/false)', 'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_fundraiseup_credentials_list() {
    foreach ( adfoin_read_credentials( 'fundraiseup' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_fundraiseup_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'fundraiseup', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.fundraiseup.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_fundraiseup_job_queue', 'adfoin_fundraiseup_job_queue', 10, 1 );
function adfoin_fundraiseup_job_queue( $data ) {
    adfoin_fundraiseup_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_fundraiseup_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_supporter' ) return;

    $body = array();
    foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'company' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $addr = array();
    foreach ( array( 'address' => 'line1', 'city' => 'city', 'state' => 'state', 'postcode' => 'postcode', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['address'] = $addr;
    if ( isset( $fields['opted_in_marketing'] ) && $fields['opted_in_marketing'] !== '' ) {
        $body['opted_in_marketing'] = filter_var( $fields['opted_in_marketing'], FILTER_VALIDATE_BOOLEAN );
    }

    adfoin_fundraiseup_request( 'supporters', 'POST', $body, $record, $cred_id );
}
