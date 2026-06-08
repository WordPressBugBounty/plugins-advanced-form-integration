<?php

add_filter( 'adfoin_action_providers', 'adfoin_velocify_actions', 10, 1 );
function adfoin_velocify_actions( $actions ) {
    $actions['velocify'] = array(
        'title' => 'Velocify',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_velocify_settings_tab', 10, 1 );
function adfoin_velocify_settings_tab( $providers ) { $providers['velocify'] = 'Velocify'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_velocify_settings_view', 10, 1 );
function adfoin_velocify_settings_view( $current_tab ) {
    if ( $current_tab !== 'velocify' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'velocify',
        'fields'   => array(
            array( 'key' => 'clientId',       'label' => __( 'Client ID', 'advanced-form-integration' ) ),
            array( 'key' => 'sourceId',       'label' => __( 'Lead Source ID', 'advanced-form-integration' ) ),
            array( 'key' => 'campaignId',     'label' => __( 'Campaign ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Velocify (Top of Mind / MeridianLink), open Setup > Distribution Programs. Note your Client ID, Lead Source ID, and Campaign ID. Velocify accepts leads via its public ImportLead endpoint.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Velocify', 'advanced-form-integration' ), 'velocify', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_velocify_action_fields' );
function adfoin_velocify_action_fields() {
    ?>
    <script type="text/template" id="velocify-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Velocify Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_velocify_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Velocify [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_velocify_credentials', 'adfoin_get_velocify_credentials' );
function adfoin_get_velocify_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'velocify' ) );
}

add_action( 'wp_ajax_adfoin_save_velocify_credentials', 'adfoin_save_velocify_credentials' );
function adfoin_save_velocify_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'velocify' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'velocify', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_velocify_fields', 'adfoin_get_velocify_fields' );
function adfoin_get_velocify_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'FirstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'LastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'Email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'Phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'CellPhone',   'value' => 'Cell Phone', 'description' => '' ),
        array( 'key' => 'Address',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'City',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'State',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'PostalCode',  'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'LoanType',    'value' => 'Loan Type',  'description' => '' ),
        array( 'key' => 'LoanAmount',  'value' => 'Loan Amount','description' => '' ),
        array( 'key' => 'LoanPurpose', 'value' => 'Loan Purpose','description' => '' ),
        array( 'key' => 'PropertyType','value' => 'Property Type', 'description' => '' ),
        array( 'key' => 'CreditScore', 'value' => 'Credit Score', 'description' => '' ),
        array( 'key' => 'Comments',    'value' => 'Comments',   'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_velocify_credentials_list() {
    foreach ( adfoin_read_credentials( 'velocify' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_velocify_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'velocify', $cred_id );
    $client_id   = isset( $credentials['clientId'] )   ? $credentials['clientId']   : '';
    $source_id   = isset( $credentials['sourceId'] )   ? $credentials['sourceId']   : '';
    $campaign_id = isset( $credentials['campaignId'] ) ? $credentials['campaignId'] : '';
    if ( ! $client_id || ! $source_id ) return;

    $data['Client_ID']   = $client_id;
    $data['CampaignID']  = $campaign_id;
    $data['SourceID']    = $source_id;

    $url  = 'https://service.leads360.com/ImportLeads.aspx';
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

add_action( 'adfoin_velocify_job_queue', 'adfoin_velocify_job_queue', 10, 1 );
function adfoin_velocify_job_queue( $data ) {
    adfoin_velocify_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_velocify_send_data( $record, $posted_data ) {
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
    foreach ( array( 'FirstName', 'LastName', 'Email', 'Phone', 'CellPhone', 'Address', 'City', 'State', 'PostalCode', 'LoanType', 'LoanAmount', 'LoanPurpose', 'PropertyType', 'CreditScore', 'Comments' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_velocify_request( $body, $record, $cred_id );
}
