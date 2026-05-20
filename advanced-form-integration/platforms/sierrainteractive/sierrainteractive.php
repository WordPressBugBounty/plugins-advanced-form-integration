<?php

add_filter( 'adfoin_action_providers', 'adfoin_sierrainteractive_actions', 10, 1 );
function adfoin_sierrainteractive_actions( $actions ) {
    $actions['sierrainteractive'] = array(
        'title' => 'Sierra Interactive',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sierrainteractive_settings_tab', 10, 1 );
function adfoin_sierrainteractive_settings_tab( $providers ) { $providers['sierrainteractive'] = 'Sierra Interactive'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_sierrainteractive_settings_view', 10, 1 );
function adfoin_sierrainteractive_settings_view( $current_tab ) {
    if ( $current_tab !== 'sierrainteractive' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'sierrainteractive',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'siteUrl','label' => __( 'Site URL (e.g. yoursite.sierra.com)', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Sierra Interactive, go to Admin > API Settings. Generate an API key and copy your site subdomain.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Sierra Interactive', 'advanced-form-integration' ), 'sierrainteractive', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_sierrainteractive_action_fields' );
function adfoin_sierrainteractive_action_fields() {
    ?>
    <script type="text/template" id="sierrainteractive-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Sierra Interactive Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_sierrainteractive_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Sierra Interactive [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_sierrainteractive_credentials', 'adfoin_get_sierrainteractive_credentials' );
function adfoin_get_sierrainteractive_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'sierrainteractive' ) );
}

add_action( 'wp_ajax_adfoin_save_sierrainteractive_credentials', 'adfoin_save_sierrainteractive_credentials' );
function adfoin_save_sierrainteractive_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'sierrainteractive' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'sierrainteractive', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_sierrainteractive_fields', 'adfoin_get_sierrainteractive_fields' );
function adfoin_get_sierrainteractive_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'leadType',    'value' => 'Lead Type',  'description' => 'Buyer / Seller / Renter' ),
        array( 'key' => 'source',      'value' => 'Source',     'description' => '' ),
        array( 'key' => 'subSource',   'value' => 'Sub Source', 'description' => '' ),
        array( 'key' => 'propertyUrl', 'value' => 'Property URL', 'description' => '' ),
        array( 'key' => 'priceMin',    'value' => 'Price Min',  'description' => '' ),
        array( 'key' => 'priceMax',    'value' => 'Price Max',  'description' => '' ),
        array( 'key' => 'beds',        'value' => 'Beds',       'description' => '' ),
        array( 'key' => 'baths',       'value' => 'Baths',      'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'message',     'value' => 'Message',    'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_sierrainteractive_credentials_list() {
    foreach ( adfoin_read_credentials( 'sierrainteractive' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_sierrainteractive_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'sierrainteractive', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )  ? $credentials['apiKey']  : '';
    $site_url    = isset( $credentials['siteUrl'] ) ? $credentials['siteUrl'] : '';
    if ( ! $api_key || ! $site_url ) return;

    $host = preg_replace( '#^https?://#', '', rtrim( $site_url, '/' ) );
    $url  = 'https://' . $host . '/api/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Sierra-ApiKey' => $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_sierrainteractive_job_queue', 'adfoin_sierrainteractive_job_queue', 10, 1 );
function adfoin_sierrainteractive_job_queue( $data ) {
    adfoin_sierrainteractive_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sierrainteractive_send_data( $record, $posted_data ) {
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
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'leadType', 'source', 'subSource', 'propertyUrl', 'message' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $criteria = array();
    foreach ( array( 'priceMin', 'priceMax', 'beds', 'baths', 'city', 'state', 'zip' ) as $k ) {
        if ( isset( $fields[ $k ] ) && $fields[ $k ] !== '' ) $criteria[ $k ] = $fields[ $k ];
    }
    if ( $criteria ) $body['searchCriteria'] = $criteria;

    adfoin_sierrainteractive_request( 'leads', 'POST', $body, $record, $cred_id );
}
