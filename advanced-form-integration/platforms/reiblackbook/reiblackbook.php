<?php

add_filter( 'adfoin_action_providers', 'adfoin_reiblackbook_actions', 10, 1 );
function adfoin_reiblackbook_actions( $actions ) {
    $actions['reiblackbook'] = array(
        'title' => 'REI BlackBook',
        'tasks' => array( 'create_lead' => 'Create Lead (via Form URL)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_reiblackbook_settings_tab', 10, 1 );
function adfoin_reiblackbook_settings_tab( $providers ) { $providers['reiblackbook'] = 'REI BlackBook'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_reiblackbook_settings_view', 10, 1 );
function adfoin_reiblackbook_settings_view( $current_tab ) {
    if ( $current_tab !== 'reiblackbook' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'reiblackbook',
        'fields'   => array(
            array( 'key' => 'formUrl', 'label' => __( 'Form Submission URL', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'REI BlackBook does not expose a public REST API. Build a Capture Form in REI BlackBook, copy its public submission URL, and paste it here. Submissions trigger your existing REI BlackBook automations.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'REI BlackBook', 'advanced-form-integration' ), 'reiblackbook', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_reiblackbook_action_fields' );
function adfoin_reiblackbook_action_fields() {
    ?>
    <script type="text/template" id="reiblackbook-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'REI BlackBook Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_reiblackbook_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'REI BlackBook [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_reiblackbook_credentials', 'adfoin_get_reiblackbook_credentials' );
function adfoin_get_reiblackbook_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'reiblackbook' ) );
}

add_action( 'wp_ajax_adfoin_save_reiblackbook_credentials', 'adfoin_save_reiblackbook_credentials' );
function adfoin_save_reiblackbook_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'reiblackbook' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'reiblackbook', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_reiblackbook_fields', 'adfoin_get_reiblackbook_fields' );
function adfoin_get_reiblackbook_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'property_address','value' => 'Property Address', 'description' => '' ),
        array( 'key' => 'property_city','value' => 'Property City', 'description' => '' ),
        array( 'key' => 'property_state','value' => 'Property State', 'description' => '' ),
        array( 'key' => 'property_zip','value' => 'Property Zip', 'description' => '' ),
        array( 'key' => 'asking_price','value' => 'Asking Price', 'description' => '' ),
        array( 'key' => 'motivation', 'value' => 'Seller Motivation', 'description' => '' ),
        array( 'key' => 'note',       'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_reiblackbook_credentials_list() {
    foreach ( adfoin_read_credentials( 'reiblackbook' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_reiblackbook_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'reiblackbook', $cred_id );
    $url         = isset( $credentials['formUrl'] ) ? $credentials['formUrl'] : '';
    if ( ! $url ) return;

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
        'body'    => http_build_query( $data ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_reiblackbook_job_queue', 'adfoin_reiblackbook_job_queue', 10, 1 );
function adfoin_reiblackbook_job_queue( $data ) {
    adfoin_reiblackbook_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_reiblackbook_send_data( $record, $posted_data ) {
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
    foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'property_address', 'property_city', 'property_state', 'property_zip', 'asking_price', 'motivation', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_reiblackbook_request( $body, $record, $cred_id );
}
