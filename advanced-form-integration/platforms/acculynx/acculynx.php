<?php

add_filter( 'adfoin_action_providers', 'adfoin_acculynx_actions', 10, 1 );
function adfoin_acculynx_actions( $actions ) {
    $actions['acculynx'] = array(
        'title' => 'AccuLynx',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_acculynx_settings_tab', 10, 1 );
function adfoin_acculynx_settings_tab( $providers ) {
    $providers['acculynx'] = 'AccuLynx';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_acculynx_settings_view', 10, 1 );
function adfoin_acculynx_settings_view( $current_tab ) {
    if ( $current_tab !== 'acculynx' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'acculynx',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In AccuLynx, go to Settings > API Access. Generate an API key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'AccuLynx', 'advanced-form-integration' ), 'acculynx', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_acculynx_action_fields' );
function adfoin_acculynx_action_fields() {
    ?>
    <script type="text/template" id="acculynx-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'AccuLynx Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_acculynx_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'AccuLynx [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_acculynx_credentials', 'adfoin_get_acculynx_credentials' );
function adfoin_get_acculynx_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'acculynx' ) );
}

add_action( 'wp_ajax_adfoin_save_acculynx_credentials', 'adfoin_save_acculynx_credentials' );
function adfoin_save_acculynx_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'acculynx' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'acculynx', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_acculynx_fields', 'adfoin_get_acculynx_fields' );
function adfoin_get_acculynx_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',      'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'street',      'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',     'value' => 'Country',    'description' => '' ),
        array( 'key' => 'jobType',     'value' => 'Job Type',   'description' => 'roofing / siding / windows / etc.' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'damageType',  'value' => 'Damage Type','description' => '' ),
        array( 'key' => 'insuranceCarrier','value' => 'Insurance Carrier', 'description' => '' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_acculynx_credentials_list() {
    foreach ( adfoin_read_credentials( 'acculynx' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_acculynx_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'acculynx', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) return;

    $url  = 'https://api.acculynx.com/api/v2/' . ltrim( $endpoint, '/' );
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

add_action( 'adfoin_acculynx_job_queue', 'adfoin_acculynx_job_queue', 10, 1 );
function adfoin_acculynx_job_queue( $data ) {
    adfoin_acculynx_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_acculynx_send_data( $record, $posted_data ) {
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
    foreach ( array( 'firstName' => 'firstName', 'lastName' => 'lastName', 'email' => 'email', 'phone' => 'phone', 'mobile' => 'mobile', 'jobType' => 'jobType', 'leadSource' => 'leadSource', 'damageType' => 'damageType', 'insuranceCarrier' => 'insuranceCarrier', 'note' => 'note' ) as $local => $remote ) {
        if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $body[ $remote ] = $fields[ $local ];
    }
    $addr = array();
    foreach ( array( 'street' => 'street', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['address'] = $addr;

    adfoin_acculynx_request( 'leads', 'POST', $body, $record, $cred_id );
}
