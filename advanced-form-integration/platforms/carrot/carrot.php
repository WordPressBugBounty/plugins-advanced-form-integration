<?php

add_filter( 'adfoin_action_providers', 'adfoin_carrot_actions', 10, 1 );
function adfoin_carrot_actions( $actions ) {
    $actions['carrot'] = array(
        'title' => 'Carrot',
        'tasks' => array( 'create_lead' => 'Create Lead (via Webhook)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_carrot_settings_tab', 10, 1 );
function adfoin_carrot_settings_tab( $providers ) { $providers['carrot'] = 'Carrot'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_carrot_settings_view', 10, 1 );
function adfoin_carrot_settings_view( $current_tab ) {
    if ( $current_tab !== 'carrot' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'carrot',
        'fields'   => array(
            array( 'key' => 'webhookUrl', 'label' => __( 'Carrot Webhook URL', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',     'label' => __( 'Shared Secret (optional)', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In your Carrot dashboard, open Settings > Integrations > Webhooks. Create a webhook for inbound leads and paste the URL above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Carrot', 'advanced-form-integration' ), 'carrot', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_carrot_action_fields' );
function adfoin_carrot_action_fields() {
    ?>
    <script type="text/template" id="carrot-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Carrot Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_carrot_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Carrot [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_carrot_credentials', 'adfoin_get_carrot_credentials' );
function adfoin_get_carrot_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'carrot' ) );
}

add_action( 'wp_ajax_adfoin_save_carrot_credentials', 'adfoin_save_carrot_credentials' );
function adfoin_save_carrot_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'carrot' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'carrot', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_carrot_fields', 'adfoin_get_carrot_fields' );
function adfoin_get_carrot_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'propertyAddress','value' => 'Property Address', 'description' => '' ),
        array( 'key' => 'propertyCity','value' => 'Property City', 'description' => '' ),
        array( 'key' => 'propertyState','value' => 'Property State', 'description' => '' ),
        array( 'key' => 'propertyZip', 'value' => 'Property Zip', 'description' => '' ),
        array( 'key' => 'askingPrice', 'value' => 'Asking Price', 'description' => '' ),
        array( 'key' => 'leadType',    'value' => 'Lead Type',  'description' => 'motivated-seller / buyer / etc.' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_carrot_credentials_list() {
    foreach ( adfoin_read_credentials( 'carrot' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_carrot_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'carrot', $cred_id );
    $url         = isset( $credentials['webhookUrl'] ) ? $credentials['webhookUrl'] : '';
    $secret      = isset( $credentials['apiKey'] )     ? $credentials['apiKey']     : '';
    if ( ! $url ) return;

    $body    = wp_json_encode( $data );
    $headers = array( 'Content-Type' => 'application/json' );
    if ( $secret ) $headers['X-Carrot-Signature'] = hash_hmac( 'sha256', $body, $secret );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => $body,
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_carrot_job_queue', 'adfoin_carrot_job_queue', 10, 1 );
function adfoin_carrot_job_queue( $data ) {
    adfoin_carrot_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_carrot_send_data( $record, $posted_data ) {
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

    $body = array( 'event' => 'lead.created' );
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'propertyAddress', 'propertyCity', 'propertyState', 'propertyZip', 'askingPrice', 'leadType', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_carrot_request( $body, $record, $cred_id );
}
