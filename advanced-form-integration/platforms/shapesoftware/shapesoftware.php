<?php

add_filter( 'adfoin_action_providers', 'adfoin_shapesoftware_actions', 10, 1 );
function adfoin_shapesoftware_actions( $actions ) {
    $actions['shapesoftware'] = array(
        'title' => 'Shape Software',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_shapesoftware_settings_tab', 10, 1 );
function adfoin_shapesoftware_settings_tab( $providers ) { $providers['shapesoftware'] = 'Shape Software'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_shapesoftware_settings_view', 10, 1 );
function adfoin_shapesoftware_settings_view( $current_tab ) {
    if ( $current_tab !== 'shapesoftware' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'shapesoftware',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'crmId',  'label' => __( 'System ID (crmid)', 'advanced-form-integration' ) ),
        ),
    ) );
    // Confirmed via setshape.com/api-getting-started + api-add-leads — the
    // Open API token lives under Settings > API Integrations, and the
    // account's numeric "systemId" (crmid) is embedded directly in the
    // endpoint path, not sent as a header.
    $instructions = __( 'In Shape, go to Settings > API Integrations > Shape Open API to generate a token. Your System ID (crmid) is shown on the same page / included in your API response payloads.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Shape Software', 'advanced-form-integration' ), 'shapesoftware', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_shapesoftware_action_fields' );
function adfoin_shapesoftware_action_fields() {
    ?>
    <script type="text/template" id="shapesoftware-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Shape Software Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_shapesoftware_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Shape Software [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_shapesoftware_credentials', 'adfoin_get_shapesoftware_credentials' );
function adfoin_get_shapesoftware_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'shapesoftware' ) );
}

add_action( 'wp_ajax_adfoin_save_shapesoftware_credentials', 'adfoin_save_shapesoftware_credentials' );
function adfoin_save_shapesoftware_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'shapesoftware' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'shapesoftware', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_shapesoftware_fields', 'adfoin_get_shapesoftware_fields' );
function adfoin_get_shapesoftware_fields() {
    adfoin_verify_nonce();
    // Confirmed real fields for POST /add/new/lead/{crmid} (setshape.com/api-add-leads):
    // firstname/lastname/email are required, phone/source optional, recordtype
    // required only when source is omitted. Everything else Shape's docs
    // describe as account-specific custom-field mapping, not fixed keys.
    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name',      'description' => 'Required' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',       'description' => 'Required' ),
        array( 'key' => 'email',      'value' => 'Email',           'description' => 'Required' ),
        array( 'key' => 'phone',      'value' => 'Phone',           'description' => '' ),
        array( 'key' => 'source',     'value' => 'Source',          'description' => 'Auto-created in Shape if it doesn\'t already exist' ),
        array( 'key' => 'recordType', 'value' => 'Record Type',     'description' => 'Required only if Source is left blank' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_shapesoftware_credentials_list() {
    foreach ( adfoin_read_credentials( 'shapesoftware' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_shapesoftware_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'shapesoftware', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    // Confirmed base host + auth scheme via setshape.com/api-getting-started:
    // the token is sent raw (no "Bearer " prefix).
    $url  = 'https://secure-api.setshape.com/api/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_shapesoftware_job_queue', 'adfoin_shapesoftware_job_queue', 10, 1 );
function adfoin_shapesoftware_job_queue( $data ) {
    adfoin_shapesoftware_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_shapesoftware_send_data( $record, $posted_data ) {
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

    $credentials = adfoin_get_credentials_by_id( 'shapesoftware', $cred_id );
    $crm_id      = isset( $credentials['crmId'] ) ? $credentials['crmId'] : '';
    if ( ! $crm_id ) return;

    $body = array();
    foreach ( array( 'firstName' => 'firstname', 'lastName' => 'lastname', 'email' => 'email', 'phone' => 'phone', 'source' => 'source', 'recordType' => 'recordtype' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    if ( empty( $body['firstname'] ) || empty( $body['lastname'] ) || empty( $body['email'] ) ) return;

    adfoin_shapesoftware_request( 'add/new/lead/' . rawurlencode( $crm_id ), 'POST', $body, $record, $cred_id );
}
