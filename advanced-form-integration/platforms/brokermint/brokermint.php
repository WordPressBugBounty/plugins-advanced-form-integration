<?php

add_filter( 'adfoin_action_providers', 'adfoin_brokermint_actions', 10, 1 );
function adfoin_brokermint_actions( $actions ) {
    $actions['brokermint'] = array(
        'title' => 'Brokermint',
        'tasks' => array(
            'create_contact'    => 'Create Contact',
            'create_transaction'=> 'Create Transaction',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_brokermint_settings_tab', 10, 1 );
function adfoin_brokermint_settings_tab( $providers ) { $providers['brokermint'] = 'Brokermint'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_brokermint_settings_view', 10, 1 );
function adfoin_brokermint_settings_view( $current_tab ) {
    if ( $current_tab !== 'brokermint' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'brokermint',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Brokermint, go to your User Profile > API Access. Generate an API key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Brokermint', 'advanced-form-integration' ), 'brokermint', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_brokermint_action_fields' );
function adfoin_brokermint_action_fields() {
    ?>
    <script type="text/template" id="brokermint-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_transaction'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_transaction'">
                <td scope="row-title"><label><?php esc_attr_e( 'Brokermint Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_brokermint_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Brokermint [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_brokermint_credentials', 'adfoin_get_brokermint_credentials' );
function adfoin_get_brokermint_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'brokermint' ) );
}

add_action( 'wp_ajax_adfoin_save_brokermint_credentials', 'adfoin_save_brokermint_credentials' );
function adfoin_save_brokermint_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'brokermint' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'brokermint', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_brokermint_fields', 'adfoin_get_brokermint_fields' );
function adfoin_get_brokermint_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( $task === 'create_transaction' ) {
        $fields = array(
            array( 'key' => 'address',     'value' => 'Property Address', 'description' => '' ),
            array( 'key' => 'city',        'value' => 'Property City',    'description' => '' ),
            array( 'key' => 'state',       'value' => 'Property State',   'description' => '' ),
            array( 'key' => 'zip',         'value' => 'Property Zip',     'description' => '' ),
            array( 'key' => 'price',       'value' => 'Sale Price',       'description' => '' ),
            array( 'key' => 'status',      'value' => 'Status',           'description' => 'pending / active / closed' ),
            array( 'key' => 'type',        'value' => 'Type',             'description' => 'sale / listing / lease' ),
            array( 'key' => 'side',        'value' => 'Representation',   'description' => 'buyer / seller / dual' ),
            array( 'key' => 'mlsNumber',   'value' => 'MLS Number',       'description' => '' ),
            array( 'key' => 'closingDate', 'value' => 'Closing Date',     'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'agentEmail',  'value' => 'Agent Email',      'description' => '' ),
            array( 'key' => 'clientEmail', 'value' => 'Client Email',     'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'company',     'value' => 'Company',    'description' => '' ),
            array( 'key' => 'street',      'value' => 'Street',     'description' => '' ),
            array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',       'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'contactType', 'value' => 'Contact Type', 'description' => 'client / lender / inspector / etc.' ),
            array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_brokermint_credentials_list() {
    foreach ( adfoin_read_credentials( 'brokermint' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_brokermint_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'brokermint', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://my.brokermint.com/api/v2/' . ltrim( $endpoint, '/' );
    $sep  = strpos( $url, '?' ) === false ? '?' : '&';
    $url .= $sep . 'api_key=' . rawurlencode( $api_key );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array( 'Content-Type' => 'application/json' ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_brokermint_job_queue', 'adfoin_brokermint_job_queue', 10, 1 );
function adfoin_brokermint_job_queue( $data ) {
    adfoin_brokermint_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_brokermint_send_data( $record, $posted_data ) {
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
        $body = array();
        foreach ( array( 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'company' => 'company', 'contactType' => 'contact_type', 'note' => 'notes', 'street' => 'street', 'city' => 'city', 'state' => 'state', 'zip' => 'zip' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
        }
        adfoin_brokermint_request( 'contacts', 'POST', $body, $record, $cred_id );
    } elseif ( $record['task'] === 'create_transaction' ) {
        $body = array();
        foreach ( array( 'address' => 'address', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'price' => 'price', 'status' => 'status', 'type' => 'type', 'side' => 'representing', 'mlsNumber' => 'mls_number', 'closingDate' => 'closing_date' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
        }
        if ( ! empty( $fields['agentEmail'] ) )  $body['agent_email']  = $fields['agentEmail'];
        if ( ! empty( $fields['clientEmail'] ) ) $body['client_email'] = $fields['clientEmail'];
        adfoin_brokermint_request( 'transactions', 'POST', $body, $record, $cred_id );
    }
}
