<?php

add_filter( 'adfoin_action_providers', 'adfoin_paperlesspipeline_actions', 10, 1 );
function adfoin_paperlesspipeline_actions( $actions ) {
    $actions['paperlesspipeline'] = array(
        'title' => 'Paperless Pipeline',
        'tasks' => array( 'create_transaction' => 'Create Transaction' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_paperlesspipeline_settings_tab', 10, 1 );
function adfoin_paperlesspipeline_settings_tab( $providers ) { $providers['paperlesspipeline'] = 'Paperless Pipeline'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_paperlesspipeline_settings_view', 10, 1 );
function adfoin_paperlesspipeline_settings_view( $current_tab ) {
    if ( $current_tab !== 'paperlesspipeline' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'paperlesspipeline',
        'fields'   => array(
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'subdomain', 'label' => __( 'Subdomain (e.g. yourcompany)', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Paperless Pipeline, go to Settings > API. Generate an API key and copy your subdomain (the part before .paperlesspipeline.com).', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Paperless Pipeline', 'advanced-form-integration' ), 'paperlesspipeline', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_paperlesspipeline_action_fields' );
function adfoin_paperlesspipeline_action_fields() {
    ?>
    <script type="text/template" id="paperlesspipeline-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_transaction'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_transaction'">
                <td scope="row-title"><label><?php esc_attr_e( 'Paperless Pipeline Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_paperlesspipeline_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_transaction', 'Paperless Pipeline [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_paperlesspipeline_credentials', 'adfoin_get_paperlesspipeline_credentials' );
function adfoin_get_paperlesspipeline_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'paperlesspipeline' ) );
}

add_action( 'wp_ajax_adfoin_save_paperlesspipeline_credentials', 'adfoin_save_paperlesspipeline_credentials' );
function adfoin_save_paperlesspipeline_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'paperlesspipeline' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'paperlesspipeline', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_paperlesspipeline_fields', 'adfoin_get_paperlesspipeline_fields' );
function adfoin_get_paperlesspipeline_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'address',     'value' => 'Property Address', 'description' => '' ),
        array( 'key' => 'city',        'value' => 'Property City',    'description' => '' ),
        array( 'key' => 'state',       'value' => 'Property State',   'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Property Zip',     'description' => '' ),
        array( 'key' => 'price',       'value' => 'Sale Price',       'description' => '' ),
        array( 'key' => 'side',        'value' => 'Side',             'description' => 'list / sell / both' ),
        array( 'key' => 'status',      'value' => 'Status',           'description' => 'active / pending / closed / withdrawn' ),
        array( 'key' => 'mlsNumber',   'value' => 'MLS Number',       'description' => '' ),
        array( 'key' => 'closingDate', 'value' => 'Closing Date',     'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'agentEmail',  'value' => 'Agent Email',      'description' => '' ),
        array( 'key' => 'clientName',  'value' => 'Client Name',      'description' => '' ),
        array( 'key' => 'clientEmail', 'value' => 'Client Email',     'description' => '' ),
        array( 'key' => 'clientPhone', 'value' => 'Client Phone',     'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_paperlesspipeline_credentials_list() {
    foreach ( adfoin_read_credentials( 'paperlesspipeline' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_paperlesspipeline_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'paperlesspipeline', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    $subdomain   = isset( $credentials['subdomain'] ) ? $credentials['subdomain'] : '';
    if ( ! $api_key || ! $subdomain ) return;

    $url  = 'https://' . $subdomain . '.paperlesspipeline.com/api/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Token ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_paperlesspipeline_job_queue', 'adfoin_paperlesspipeline_job_queue', 10, 1 );
function adfoin_paperlesspipeline_job_queue( $data ) {
    adfoin_paperlesspipeline_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_paperlesspipeline_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_transaction' ) return;

    $body = array();
    foreach ( array( 'address' => 'address', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'price' => 'price', 'side' => 'side', 'status' => 'status', 'mlsNumber' => 'mls_number', 'closingDate' => 'closing_date' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    if ( ! empty( $fields['agentEmail'] ) ) $body['agent_email'] = $fields['agentEmail'];
    $client = array();
    foreach ( array( 'clientName' => 'name', 'clientEmail' => 'email', 'clientPhone' => 'phone' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $client[ $remote ] = $fields[ $local ];
    }
    if ( $client ) $body['contacts'] = array( $client );

    adfoin_paperlesspipeline_request( 'transactions', 'POST', $body, $record, $cred_id );
}
