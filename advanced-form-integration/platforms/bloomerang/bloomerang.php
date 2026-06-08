<?php

add_filter( 'adfoin_action_providers', 'adfoin_bloomerang_actions', 10, 1 );
function adfoin_bloomerang_actions( $actions ) {
    $actions['bloomerang'] = array(
        'title' => 'Bloomerang',
        'tasks' => array(
            'create_constituent' => 'Create Constituent',
            'create_transaction' => 'Create Donation / Transaction',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_bloomerang_settings_tab', 10, 1 );
function adfoin_bloomerang_settings_tab( $providers ) { $providers['bloomerang'] = 'Bloomerang'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_bloomerang_settings_view', 10, 1 );
function adfoin_bloomerang_settings_view( $current_tab ) {
    if ( $current_tab !== 'bloomerang' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'bloomerang',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Bloomerang, go to Settings > API Keys. Generate a key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Bloomerang', 'advanced-form-integration' ), 'bloomerang', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_bloomerang_action_fields' );
function adfoin_bloomerang_action_fields() {
    ?>
    <script type="text/template" id="bloomerang-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_constituent' || action.task == 'create_transaction'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_constituent' || action.task == 'create_transaction'">
                <td scope="row-title"><label><?php esc_attr_e( 'Bloomerang Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_bloomerang_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_constituent', 'Bloomerang [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_bloomerang_credentials', 'adfoin_get_bloomerang_credentials' );
function adfoin_get_bloomerang_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'bloomerang' ) );
}

add_action( 'wp_ajax_adfoin_save_bloomerang_credentials', 'adfoin_save_bloomerang_credentials' );
function adfoin_save_bloomerang_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'bloomerang' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'bloomerang', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_bloomerang_fields', 'adfoin_get_bloomerang_fields' );
function adfoin_get_bloomerang_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_constituent';

    if ( $task === 'create_transaction' ) {
        $fields = array(
            array( 'key' => 'donorEmail', 'value' => 'Donor Email',      'description' => 'Used to look up the constituent' ),
            array( 'key' => 'amount',     'value' => 'Amount',           'description' => '' ),
            array( 'key' => 'date',       'value' => 'Transaction Date', 'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'method',     'value' => 'Method',           'description' => 'Credit Card / Check / Cash / etc.' ),
            array( 'key' => 'campaign',   'value' => 'Campaign',         'description' => '' ),
            array( 'key' => 'fund',       'value' => 'Fund',             'description' => '' ),
            array( 'key' => 'appeal',     'value' => 'Appeal',           'description' => '' ),
            array( 'key' => 'note',       'value' => 'Note',             'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'type',      'value' => 'Type',       'description' => 'Individual / Organization' ),
            array( 'key' => 'organization','value' => 'Organization Name', 'description' => '' ),
            array( 'key' => 'jobTitle',  'value' => 'Job Title',  'description' => '' ),
            array( 'key' => 'street',    'value' => 'Street',     'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_bloomerang_credentials_list() {
    foreach ( adfoin_read_credentials( 'bloomerang' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_bloomerang_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'bloomerang', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.bloomerang.co/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-API-Key'    => $api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_bloomerang_job_queue', 'adfoin_bloomerang_job_queue', 10, 1 );
function adfoin_bloomerang_job_queue( $data ) {
    adfoin_bloomerang_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_bloomerang_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $record['task'] === 'create_constituent' ) {
        $body = array(
            'Type'       => ! empty( $fields['type'] ) ? $fields['type'] : 'Individual',
            'FirstName'  => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
            'LastName'   => isset( $fields['lastName'] )  ? $fields['lastName']  : '',
            'JobTitle'   => isset( $fields['jobTitle'] )  ? $fields['jobTitle']  : '',
        );
        if ( ! empty( $fields['organization'] ) ) $body['FullName'] = $fields['organization'];
        if ( ! empty( $fields['email'] ) ) $body['PrimaryEmail'] = array( 'Value' => $fields['email'] );
        if ( ! empty( $fields['phone'] ) ) $body['PrimaryPhone'] = array( 'Number' => $fields['phone'], 'Type' => 'Home' );
        $addr = array();
        foreach ( array( 'street' => 'Street', 'city' => 'City', 'state' => 'State', 'zip' => 'PostalCode', 'country' => 'Country' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
        }
        if ( $addr ) { $addr['Type'] = 'Home'; $body['PrimaryAddress'] = $addr; }
        if ( ! empty( $fields['note'] ) ) $body['Note'] = $fields['note'];

        adfoin_bloomerang_request( 'constituent', 'POST', array_filter( $body ), $record, $cred_id );
    } elseif ( $record['task'] === 'create_transaction' ) {
        $body = array();
        if ( isset( $fields['amount'] ) && $fields['amount'] !== '' ) $body['Amount'] = floatval( $fields['amount'] );
        foreach ( array( 'date' => 'Date', 'method' => 'Method', 'campaign' => 'Campaign', 'fund' => 'Fund', 'appeal' => 'Appeal', 'note' => 'Note', 'donorEmail' => 'AccountEmail' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
        }
        adfoin_bloomerang_request( 'transaction', 'POST', $body, $record, $cred_id );
    }
}
