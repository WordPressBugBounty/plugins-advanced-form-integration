<?php

add_filter( 'adfoin_action_providers', 'adfoin_clubready_actions', 10, 1 );
function adfoin_clubready_actions( $actions ) {
    $actions['clubready'] = array(
        'title' => 'ClubReady',
        'tasks' => array( 'create_prospect' => 'Create Prospect' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_clubready_settings_tab', 10, 1 );
function adfoin_clubready_settings_tab( $providers ) {
    $providers['clubready'] = 'ClubReady';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_clubready_settings_view', 10, 1 );
function adfoin_clubready_settings_view( $current_tab ) {
    if ( $current_tab !== 'clubready' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'clubready',
        'fields'   => array(
            array( 'key' => 'apiKey',  'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'storeId', 'label' => __( 'Store / Location ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In ClubReady, open Admin > Integration > API. Generate an API key for your store and paste your Store ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'ClubReady', 'advanced-form-integration' ), 'clubready', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_clubready_action_fields' );
function adfoin_clubready_action_fields() {
    ?>
    <script type="text/template" id="clubready-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_prospect'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_prospect'">
                <td scope="row-title"><label><?php esc_attr_e( 'ClubReady Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_clubready_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_prospect', 'ClubReady [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_clubready_credentials', 'adfoin_get_clubready_credentials' );
function adfoin_get_clubready_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'clubready' ) );
}

add_action( 'wp_ajax_adfoin_save_clubready_credentials', 'adfoin_save_clubready_credentials' );
function adfoin_save_clubready_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'clubready' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'clubready', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_clubready_fields', 'adfoin_get_clubready_fields' );
function adfoin_get_clubready_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'FirstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'LastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'Email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'Phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'CellPhone', 'value' => 'Cell Phone', 'description' => '' ),
        array( 'key' => 'DateOfBirth','value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'Gender',    'value' => 'Gender',     'description' => 'M / F' ),
        array( 'key' => 'Address',   'value' => 'Address',    'description' => '' ),
        array( 'key' => 'City',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'State',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'Zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'ReferredBy','value' => 'Referred By','description' => '' ),
        array( 'key' => 'LeadSource','value' => 'Lead Source','description' => '' ),
        array( 'key' => 'Interest',  'value' => 'Interest',   'description' => '' ),
        array( 'key' => 'Notes',     'value' => 'Notes',      'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_clubready_credentials_list() {
    foreach ( adfoin_read_credentials( 'clubready' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_clubready_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'clubready', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )  ? $credentials['apiKey']  : '';
    $store_id    = isset( $credentials['storeId'] ) ? $credentials['storeId'] : '';

    if ( ! $api_key || ! $store_id ) return;

    $url  = 'https://www.clubready.com/api/v1/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Store-Id'    => $store_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_clubready_create_prospect( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'FirstName', 'LastName', 'Email', 'Phone', 'CellPhone', 'DateOfBirth', 'Gender', 'Address', 'City', 'State', 'Zip', 'ReferredBy', 'LeadSource', 'Interest', 'Notes' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    return adfoin_clubready_request( 'prospects', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_clubready_job_queue', 'adfoin_clubready_job_queue', 10, 1 );
function adfoin_clubready_job_queue( $data ) {
    adfoin_clubready_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_clubready_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_prospect' ) {
        adfoin_clubready_create_prospect( $fields, $record, $cred_id );
    }
}
