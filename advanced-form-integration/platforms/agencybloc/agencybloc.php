<?php

add_filter( 'adfoin_action_providers', 'adfoin_agencybloc_actions', 10, 1 );
function adfoin_agencybloc_actions( $actions ) {
    $actions['agencybloc'] = array(
        'title' => 'AgencyBloc',
        'tasks' => array(
            'create_individual' => 'Create Individual',
            'create_lead'       => 'Create Lead',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_agencybloc_settings_tab', 10, 1 );
function adfoin_agencybloc_settings_tab( $providers ) { $providers['agencybloc'] = 'AgencyBloc'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_agencybloc_settings_view', 10, 1 );
function adfoin_agencybloc_settings_view( $current_tab ) {
    if ( $current_tab !== 'agencybloc' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'agencybloc',
        'fields'   => array(
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'agencyId',  'label' => __( 'Agency ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In AgencyBloc, go to Setup > API Access. Generate an API key and copy your Agency ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'AgencyBloc', 'advanced-form-integration' ), 'agencybloc', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_agencybloc_action_fields' );
function adfoin_agencybloc_action_fields() {
    ?>
    <script type="text/template" id="agencybloc-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_individual' || action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_individual' || action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'AgencyBloc Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_agencybloc_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_individual', 'AgencyBloc [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_agencybloc_credentials', 'adfoin_get_agencybloc_credentials' );
function adfoin_get_agencybloc_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'agencybloc' ) );
}

add_action( 'wp_ajax_adfoin_save_agencybloc_credentials', 'adfoin_save_agencybloc_credentials' );
function adfoin_save_agencybloc_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'agencybloc' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'agencybloc', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_agencybloc_fields', 'adfoin_get_agencybloc_fields' );
function adfoin_get_agencybloc_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',      'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'dob',         'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',      'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'ssn',         'value' => 'SSN',        'description' => '' ),
        array( 'key' => 'address',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'lineOfBusiness','value' => 'Line of Business', 'description' => 'Health / Life / etc.' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_agencybloc_credentials_list() {
    foreach ( adfoin_read_credentials( 'agencybloc' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_agencybloc_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'agencybloc', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )   ? $credentials['apiKey']   : '';
    $agency_id   = isset( $credentials['agencyId'] ) ? $credentials['agencyId'] : '';
    if ( ! $api_key || ! $agency_id ) return;

    $url  = 'https://api.agencybloc.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Agency-Id'   => $agency_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_agencybloc_job_queue', 'adfoin_agencybloc_job_queue', 10, 1 );
function adfoin_agencybloc_job_queue( $data ) {
    adfoin_agencybloc_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_agencybloc_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    $body = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'mobile', 'dob', 'gender', 'ssn', 'leadSource', 'lineOfBusiness', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $addr = array();
    foreach ( array( 'address' => 'street', 'city' => 'city', 'state' => 'state', 'zip' => 'zip' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['address'] = $addr;

    $endpoint = $record['task'] === 'create_lead' ? 'leads' : 'individuals';
    adfoin_agencybloc_request( $endpoint, 'POST', $body, $record, $cred_id );
}
