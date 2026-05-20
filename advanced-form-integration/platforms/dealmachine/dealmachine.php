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
            array( 'key' => 'teamId',  'label' => __( 'Team ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In DealMachine, open Settings > Integrations > API. Generate an API key and copy your Team ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'DealMachine', 'advanced-form-integration' ), 'dealmachine', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_dealmachine_action_fields' );
function adfoin_dealmachine_action_fields() {
    ?>
    <script type="text/template" id="dealmachine-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
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
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'dealmachine' ) );
}

add_action( 'wp_ajax_adfoin_save_dealmachine_credentials', 'adfoin_save_dealmachine_credentials' );
function adfoin_save_dealmachine_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'dealmachine' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'dealmachine', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_dealmachine_fields', 'adfoin_get_dealmachine_fields' );
function adfoin_get_dealmachine_fields() {
    if ( ! adfoin_verify_nonce() ) return;
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

function adfoin_dealmachine_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'dealmachine', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $team_id     = isset( $credentials['teamId'] ) ? $credentials['teamId'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.dealmachine.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Team-Id'     => $team_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
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

    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'askingPrice', 'motivation', 'leadSource', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $prop = array();
    foreach ( array( 'propertyAddress' => 'address', 'propertyCity' => 'city', 'propertyState' => 'state', 'propertyZip' => 'zip' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $prop[ $remote ] = $fields[ $local ];
    }
    if ( $prop ) $body['property'] = $prop;

    adfoin_dealmachine_request( 'leads', 'POST', $body, $record, $cred_id );
}
