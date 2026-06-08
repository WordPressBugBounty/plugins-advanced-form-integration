<?php

add_filter( 'adfoin_action_providers', 'adfoin_homebot_actions', 10, 1 );
function adfoin_homebot_actions( $actions ) {
    $actions['homebot'] = array(
        'title' => 'HomeBot',
        'tasks' => array( 'create_client' => 'Add Client / Homeowner (via Partner Webhook)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_homebot_settings_tab', 10, 1 );
function adfoin_homebot_settings_tab( $providers ) { $providers['homebot'] = 'HomeBot'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_homebot_settings_view', 10, 1 );
function adfoin_homebot_settings_view( $current_tab ) {
    if ( $current_tab !== 'homebot' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'homebot',
        'fields'   => array(
            array( 'key' => 'webhookUrl', 'label' => __( 'Partner Webhook URL', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',     'label' => __( 'Partner API Key (optional)', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'HomeBot access is granted through their partner program. Once enrolled, paste your Partner Webhook URL above. Submissions create new HomeBot clients tied to your LO/agent profile.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'HomeBot', 'advanced-form-integration' ), 'homebot', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_homebot_action_fields' );
function adfoin_homebot_action_fields() {
    ?>
    <script type="text/template" id="homebot-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_client'">
                <td scope="row-title"><label><?php esc_attr_e( 'HomeBot Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_homebot_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_client', 'HomeBot [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_homebot_credentials', 'adfoin_get_homebot_credentials' );
function adfoin_get_homebot_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'homebot' ) );
}

add_action( 'wp_ajax_adfoin_save_homebot_credentials', 'adfoin_save_homebot_credentials' );
function adfoin_save_homebot_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'homebot' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'homebot', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_homebot_fields', 'adfoin_get_homebot_fields' );
function adfoin_get_homebot_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',     'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',      'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',         'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',         'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'propertyAddress','value' => 'Property Address', 'description' => '' ),
        array( 'key' => 'propertyCity', 'value' => 'Property City', 'description' => '' ),
        array( 'key' => 'propertyState','value' => 'Property State', 'description' => '' ),
        array( 'key' => 'propertyZip',  'value' => 'Property Zip', 'description' => '' ),
        array( 'key' => 'purchasePrice','value' => 'Purchase Price', 'description' => '' ),
        array( 'key' => 'purchaseDate', 'value' => 'Purchase Date',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'currentLoanAmount','value' => 'Current Loan Amount', 'description' => '' ),
        array( 'key' => 'rate',         'value' => 'Interest Rate', 'description' => '' ),
        array( 'key' => 'note',         'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_homebot_credentials_list() {
    foreach ( adfoin_read_credentials( 'homebot' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_homebot_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'homebot', $cred_id );
    $url         = isset( $credentials['webhookUrl'] ) ? $credentials['webhookUrl'] : '';
    $api_key     = isset( $credentials['apiKey'] )     ? $credentials['apiKey']     : '';
    if ( ! $url ) return;

    $headers = array( 'Content-Type' => 'application/json' );
    if ( $api_key ) $headers['Authorization'] = 'Bearer ' . $api_key;

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => wp_json_encode( $data ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_homebot_job_queue', 'adfoin_homebot_job_queue', 10, 1 );
function adfoin_homebot_job_queue( $data ) {
    adfoin_homebot_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_homebot_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_client' ) return;

    $body = array( 'event' => 'client.created' );
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'propertyAddress', 'propertyCity', 'propertyState', 'propertyZip', 'purchasePrice', 'purchaseDate', 'currentLoanAmount', 'rate', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_homebot_request( $body, $record, $cred_id );
}
