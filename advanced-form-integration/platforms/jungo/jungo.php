<?php

add_filter( 'adfoin_action_providers', 'adfoin_jungo_actions', 10, 1 );
function adfoin_jungo_actions( $actions ) {
    $actions['jungo'] = array(
        'title' => 'Jungo',
        'tasks' => array( 'create_contact' => 'Create Contact / Loan Prospect' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_jungo_settings_tab', 10, 1 );
function adfoin_jungo_settings_tab( $providers ) { $providers['jungo'] = 'Jungo'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_jungo_settings_view', 10, 1 );
function adfoin_jungo_settings_view( $current_tab ) {
    if ( $current_tab !== 'jungo' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'jungo',
        'fields'   => array(
            array( 'key' => 'accessToken','label' => __( 'Salesforce OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'instanceUrl','label' => __( 'Instance URL (e.g. https://yourorg.my.salesforce.com)', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Jungo runs on Salesforce. Create a Connected App in your Salesforce org, complete the OAuth flow, and paste the resulting access token and your instance URL.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Jungo', 'advanced-form-integration' ), 'jungo', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_jungo_action_fields' );
function adfoin_jungo_action_fields() {
    ?>
    <script type="text/template" id="jungo-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'Jungo Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_jungo_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Jungo [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_jungo_credentials', 'adfoin_get_jungo_credentials' );
function adfoin_get_jungo_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'jungo' ) );
}

add_action( 'wp_ajax_adfoin_save_jungo_credentials', 'adfoin_save_jungo_credentials' );
function adfoin_save_jungo_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'jungo' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'jungo', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_jungo_fields', 'adfoin_get_jungo_fields' );
function adfoin_get_jungo_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'FirstName',     'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'LastName',      'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'Email',         'value' => 'Email',      'description' => '' ),
        array( 'key' => 'Phone',         'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'MobilePhone',   'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'MailingStreet', 'value' => 'Street',     'description' => '' ),
        array( 'key' => 'MailingCity',   'value' => 'City',       'description' => '' ),
        array( 'key' => 'MailingState',  'value' => 'State',      'description' => '' ),
        array( 'key' => 'MailingPostalCode','value' => 'Zip',     'description' => '' ),
        array( 'key' => 'LeadSource',    'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'mpg__Loan_Type__c', 'value' => 'Loan Type (Jungo)', 'description' => '' ),
        array( 'key' => 'mpg__Loan_Amount__c','value' => 'Loan Amount (Jungo)', 'description' => '' ),
        array( 'key' => 'Description',   'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_jungo_credentials_list() {
    foreach ( adfoin_read_credentials( 'jungo' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_jungo_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials  = adfoin_get_credentials_by_id( 'jungo', $cred_id );
    $token        = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
    $instance_url = isset( $credentials['instanceUrl'] ) ? $credentials['instanceUrl'] : '';
    if ( ! $token || ! $instance_url ) return;

    $url  = rtrim( $instance_url, '/' ) . '/services/data/v59.0/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' || $method === 'PATCH' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_jungo_job_queue', 'adfoin_jungo_job_queue', 10, 1 );
function adfoin_jungo_job_queue( $data ) {
    adfoin_jungo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_jungo_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_contact' ) return;

    // Jungo lives on Salesforce — Contact records carry the Jungo loan fields.
    adfoin_jungo_request( 'sobjects/Contact', 'POST', array_filter( $fields ), $record, $cred_id );
}
