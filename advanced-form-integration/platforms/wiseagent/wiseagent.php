<?php

add_filter( 'adfoin_action_providers', 'adfoin_wiseagent_actions', 10, 1 );
function adfoin_wiseagent_actions( $actions ) {
    $actions['wiseagent'] = array(
        'title' => 'Wise Agent',
        'tasks' => array( 'create_contact' => 'Create Contact' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_wiseagent_settings_tab', 10, 1 );
function adfoin_wiseagent_settings_tab( $providers ) { $providers['wiseagent'] = 'Wise Agent'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_wiseagent_settings_view', 10, 1 );
function adfoin_wiseagent_settings_view( $current_tab ) {
    if ( $current_tab !== 'wiseagent' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'wiseagent',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Wise Agent, go to Profile > API. Generate an API key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Wise Agent', 'advanced-form-integration' ), 'wiseagent', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_wiseagent_action_fields' );
function adfoin_wiseagent_action_fields() {
    ?>
    <script type="text/template" id="wiseagent-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'Wise Agent Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_wiseagent_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Wise Agent [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_wiseagent_credentials', 'adfoin_get_wiseagent_credentials' );
function adfoin_get_wiseagent_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'wiseagent' ) );
}

add_action( 'wp_ajax_adfoin_save_wiseagent_credentials', 'adfoin_save_wiseagent_credentials' );
function adfoin_save_wiseagent_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'wiseagent' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'wiseagent', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_wiseagent_fields', 'adfoin_get_wiseagent_fields' );
function adfoin_get_wiseagent_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'FirstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'LastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'EmailAddress','value' => 'Email',      'description' => '' ),
        array( 'key' => 'Phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'Mobile',      'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'Address',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'City',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'State',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'Zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'Country',     'value' => 'Country',    'description' => '' ),
        array( 'key' => 'Source',      'value' => 'Source',     'description' => '' ),
        array( 'key' => 'Category',    'value' => 'Category',   'description' => 'Buyer / Seller / etc.' ),
        array( 'key' => 'Rank',        'value' => 'Rank',       'description' => 'A / B / C / D' ),
        array( 'key' => 'Notes',       'value' => 'Notes',      'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_wiseagent_credentials_list() {
    foreach ( adfoin_read_credentials( 'wiseagent' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_wiseagent_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'wiseagent', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://sync.thewiseagent.com/http/webconnect.asp';
    $data['requestType'] = $endpoint;
    $data['key']         = $api_key;

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

add_action( 'adfoin_wiseagent_job_queue', 'adfoin_wiseagent_job_queue', 10, 1 );
function adfoin_wiseagent_job_queue( $data ) {
    adfoin_wiseagent_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_wiseagent_send_data( $record, $posted_data ) {
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

    $body = array();
    foreach ( array( 'FirstName', 'LastName', 'EmailAddress', 'Phone', 'Mobile', 'Address', 'City', 'State', 'Zip', 'Country', 'Source', 'Category', 'Rank', 'Notes' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_wiseagent_request( 'AddNewLeadFromWebsite', 'POST', $body, $record, $cred_id );
}
