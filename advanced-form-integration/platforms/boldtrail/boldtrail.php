<?php

add_filter( 'adfoin_action_providers', 'adfoin_boldtrail_actions', 10, 1 );
function adfoin_boldtrail_actions( $actions ) {
    $actions['boldtrail'] = array(
        'title' => 'BoldTrail (kvCORE)',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_boldtrail_settings_tab', 10, 1 );
function adfoin_boldtrail_settings_tab( $providers ) {
    $providers['boldtrail'] = 'BoldTrail (kvCORE)';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_boldtrail_settings_view', 10, 1 );
function adfoin_boldtrail_settings_view( $current_tab ) {
    if ( $current_tab !== 'boldtrail' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'boldtrail',
        'fields'   => array(
            array( 'key' => 'systemToken', 'label' => __( 'System Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'partnerKey',  'label' => __( 'Partner Key', 'advanced-form-integration' ),  'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In BoldTrail/kvCORE, open Settings > Integrations > Inbound API. Generate a system token and request your partner key.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'BoldTrail (kvCORE)', 'advanced-form-integration' ), 'boldtrail', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_boldtrail_action_fields' );
function adfoin_boldtrail_action_fields() {
    ?>
    <script type="text/template" id="boldtrail-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'BoldTrail Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_boldtrail_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'BoldTrail (kvCORE) [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_boldtrail_credentials', 'adfoin_get_boldtrail_credentials' );
function adfoin_get_boldtrail_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'boldtrail' ) );
}

add_action( 'wp_ajax_adfoin_save_boldtrail_credentials', 'adfoin_save_boldtrail_credentials' );
function adfoin_save_boldtrail_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'boldtrail' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'boldtrail', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_boldtrail_fields', 'adfoin_get_boldtrail_fields' );
function adfoin_get_boldtrail_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name',   'description' => '' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',    'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',        'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',        'description' => '' ),
        array( 'key' => 'source',     'value' => 'Source',       'description' => '' ),
        array( 'key' => 'sourceUrl',  'value' => 'Source URL',   'description' => '' ),
        array( 'key' => 'type',       'value' => 'Lead Type',    'description' => 'buyer / seller / both' ),
        array( 'key' => 'priceMin',   'value' => 'Price Min',    'description' => '' ),
        array( 'key' => 'priceMax',   'value' => 'Price Max',    'description' => '' ),
        array( 'key' => 'beds',       'value' => 'Beds',         'description' => '' ),
        array( 'key' => 'baths',      'value' => 'Baths',        'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',         'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',        'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',          'description' => '' ),
        array( 'key' => 'message',    'value' => 'Message',      'description' => '' ),
        array( 'key' => 'agentEmail', 'value' => 'Agent Email',  'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_boldtrail_credentials_list() {
    foreach ( adfoin_read_credentials( 'boldtrail' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_boldtrail_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials  = adfoin_get_credentials_by_id( 'boldtrail', $cred_id );
    $system_token = isset( $credentials['systemToken'] ) ? $credentials['systemToken'] : '';
    $partner_key  = isset( $credentials['partnerKey'] )  ? $credentials['partnerKey']  : '';

    if ( ! $system_token || ! $partner_key ) return;

    $url  = 'https://app.kvcore.com/inbox/api/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $system_token,
            'X-Partner-Key' => $partner_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_boldtrail_create_lead( $fields, $record, $cred_id ) {
    $lead = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'source', 'sourceUrl', 'type', 'message', 'agentEmail' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $lead[ $k ] = $fields[ $k ];
    }
    $criteria = array();
    foreach ( array( 'priceMin', 'priceMax', 'beds', 'baths', 'city', 'state', 'zip' ) as $k ) {
        if ( isset( $fields[ $k ] ) && $fields[ $k ] !== '' ) $criteria[ $k ] = $fields[ $k ];
    }
    if ( $criteria ) $lead['searchCriteria'] = $criteria;
    return adfoin_boldtrail_request( 'leads', 'POST', $lead, $record, $cred_id );
}

add_action( 'adfoin_boldtrail_job_queue', 'adfoin_boldtrail_job_queue', 10, 1 );
function adfoin_boldtrail_job_queue( $data ) {
    adfoin_boldtrail_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_boldtrail_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_lead' ) {
        adfoin_boldtrail_create_lead( $fields, $record, $cred_id );
    }
}
