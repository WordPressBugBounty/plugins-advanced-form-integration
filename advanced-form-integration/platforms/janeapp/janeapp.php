<?php

add_filter( 'adfoin_action_providers', 'adfoin_janeapp_actions', 10, 1 );
function adfoin_janeapp_actions( $actions ) {
    $actions['janeapp'] = array(
        'title' => 'Jane App',
        'tasks' => array( 'create_patient' => 'Create Patient' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_janeapp_settings_tab', 10, 1 );
function adfoin_janeapp_settings_tab( $providers ) {
    $providers['janeapp'] = 'Jane App';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_janeapp_settings_view', 10, 1 );
function adfoin_janeapp_settings_view( $current_tab ) {
    if ( $current_tab !== 'janeapp' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'janeapp',
        'fields'   => array(
            array( 'key' => 'clinicUrl', 'label' => __( 'Clinic URL (e.g. yourclinic.janeapp.com)', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',    'label' => __( 'API Token', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Jane, go to Settings > Integrations. Generate an API token for your clinic and copy your clinic subdomain.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Jane App', 'advanced-form-integration' ), 'janeapp', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_janeapp_action_fields' );
function adfoin_janeapp_action_fields() {
    ?>
    <script type="text/template" id="janeapp-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_patient'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_patient'">
                <td scope="row-title"><label><?php esc_attr_e( 'Jane App Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_janeapp_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_patient', 'Jane App [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_janeapp_credentials', 'adfoin_get_janeapp_credentials' );
function adfoin_get_janeapp_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'janeapp' ) );
}

add_action( 'wp_ajax_adfoin_save_janeapp_credentials', 'adfoin_save_janeapp_credentials' );
function adfoin_save_janeapp_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'janeapp' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'janeapp', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_janeapp_fields', 'adfoin_get_janeapp_fields' );
function adfoin_get_janeapp_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name',  'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',   'description' => '' ),
        array( 'key' => 'preferredName','value' => 'Preferred Name', 'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',       'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',       'description' => '' ),
        array( 'key' => 'dateOfBirth', 'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',      'value' => 'Gender',      'description' => '' ),
        array( 'key' => 'pronouns',    'value' => 'Pronouns',    'description' => '' ),
        array( 'key' => 'address',     'value' => 'Address',     'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',        'description' => '' ),
        array( 'key' => 'province',    'value' => 'Province / State', 'description' => '' ),
        array( 'key' => 'postal',      'value' => 'Postal / Zip', 'description' => '' ),
        array( 'key' => 'country',     'value' => 'Country',     'description' => '' ),
        array( 'key' => 'referralSource','value' => 'Referral Source', 'description' => '' ),
        array( 'key' => 'notes',       'value' => 'Notes',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_janeapp_credentials_list() {
    foreach ( adfoin_read_credentials( 'janeapp' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_janeapp_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'janeapp', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    $clinic_url  = isset( $credentials['clinicUrl'] ) ? $credentials['clinicUrl'] : '';

    if ( ! $api_key || ! $clinic_url ) return;

    $clinic_url = preg_replace( '#^https?://#', '', rtrim( $clinic_url, '/' ) );
    $url        = 'https://' . $clinic_url . '/api/v1/' . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_janeapp_create_patient( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'firstName', 'lastName', 'preferredName', 'email', 'phone', 'dateOfBirth', 'gender', 'pronouns', 'referralSource', 'notes' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $address = array();
    foreach ( array( 'address', 'city', 'province', 'postal', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $address[ $k ] = $fields[ $k ];
    }
    if ( $address ) $body['address'] = $address;
    return adfoin_janeapp_request( 'patients', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_janeapp_job_queue', 'adfoin_janeapp_job_queue', 10, 1 );
function adfoin_janeapp_job_queue( $data ) {
    adfoin_janeapp_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_janeapp_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_patient' ) {
        adfoin_janeapp_create_patient( $fields, $record, $cred_id );
    }
}
