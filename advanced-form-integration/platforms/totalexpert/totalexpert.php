<?php

add_filter( 'adfoin_action_providers', 'adfoin_totalexpert_actions', 10, 1 );
function adfoin_totalexpert_actions( $actions ) {
    $actions['totalexpert'] = array(
        'title' => 'Total Expert',
        'tasks' => array( 'create_contact' => 'Create Contact' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_totalexpert_settings_tab', 10, 1 );
function adfoin_totalexpert_settings_tab( $providers ) { $providers['totalexpert'] = 'Total Expert'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_totalexpert_settings_view', 10, 1 );
function adfoin_totalexpert_settings_view( $current_tab ) {
    if ( $current_tab !== 'totalexpert' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'totalexpert',
        'fields'   => array(
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'companyId', 'label' => __( 'Company ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Total Expert, go to Account Settings > API. Generate an API key and copy your Company ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Total Expert', 'advanced-form-integration' ), 'totalexpert', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_totalexpert_action_fields' );
function adfoin_totalexpert_action_fields() {
    ?>
    <script type="text/template" id="totalexpert-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'Total Expert Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_totalexpert_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Total Expert [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_totalexpert_credentials', 'adfoin_get_totalexpert_credentials' );
function adfoin_get_totalexpert_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'totalexpert' ) );
}

add_action( 'wp_ajax_adfoin_save_totalexpert_credentials', 'adfoin_save_totalexpert_credentials' );
function adfoin_save_totalexpert_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'totalexpert' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'totalexpert', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_totalexpert_fields', 'adfoin_get_totalexpert_fields' );
function adfoin_get_totalexpert_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',      'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'street',      'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'contactType', 'value' => 'Contact Type', 'description' => 'Lead / Customer / Partner' ),
        array( 'key' => 'loanType',    'value' => 'Loan Type',  'description' => '' ),
        array( 'key' => 'loanAmount',  'value' => 'Loan Amount','description' => '' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_totalexpert_credentials_list() {
    foreach ( adfoin_read_credentials( 'totalexpert' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_totalexpert_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'totalexpert', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    $company_id  = isset( $credentials['companyId'] ) ? $credentials['companyId'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://public.totalexpert.com/api/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Company-Id'  => $company_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_totalexpert_job_queue', 'adfoin_totalexpert_job_queue', 10, 1 );
function adfoin_totalexpert_job_queue( $data ) {
    adfoin_totalexpert_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_totalexpert_send_data( $record, $posted_data ) {
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
    foreach ( array( 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'mobile' => 'mobile_phone', 'contactType' => 'contact_type', 'loanType' => 'loan_type', 'loanAmount' => 'loan_amount', 'leadSource' => 'lead_source', 'note' => 'notes' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    $addr = array();
    foreach ( array( 'street', 'city', 'state', 'zip' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $addr[ $k ] = $fields[ $k ];
    }
    if ( $addr ) $body['address'] = $addr;

    adfoin_totalexpert_request( 'contacts', 'POST', $body, $record, $cred_id );
}
