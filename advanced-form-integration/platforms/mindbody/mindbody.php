<?php

add_filter( 'adfoin_action_providers', 'adfoin_mindbody_actions', 10, 1 );
function adfoin_mindbody_actions( $actions ) {
    $actions['mindbody'] = array(
        'title' => 'Mindbody',
        'tasks' => array( 'create_client' => 'Create Client' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mindbody_settings_tab', 10, 1 );
function adfoin_mindbody_settings_tab( $providers ) {
    $providers['mindbody'] = 'Mindbody';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mindbody_settings_view', 10, 1 );
function adfoin_mindbody_settings_view( $current_tab ) {
    if ( $current_tab !== 'mindbody' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'mindbody',
        'fields'   => array(
            array( 'key' => 'apiKey',     'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'siteId',     'label' => __( 'Site ID', 'advanced-form-integration' ) ),
            array( 'key' => 'sourceName', 'label' => __( 'Source Name', 'advanced-form-integration' ) ),
            array( 'key' => 'sourcePassword','label' => __( 'Source Password', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In the Mindbody Developer Portal, request a Public API key and your Source credentials. Enter your Site ID(s) — comma-separated for chains.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Mindbody', 'advanced-form-integration' ), 'mindbody', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_mindbody_action_fields' );
function adfoin_mindbody_action_fields() {
    ?>
    <script type="text/template" id="mindbody-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_client'">
                <td scope="row-title"><label><?php esc_attr_e( 'Mindbody Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mindbody_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_client', 'Mindbody [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mindbody_credentials', 'adfoin_get_mindbody_credentials' );
function adfoin_get_mindbody_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'mindbody' ) );
}

add_action( 'wp_ajax_adfoin_save_mindbody_credentials', 'adfoin_save_mindbody_credentials' );
function adfoin_save_mindbody_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'mindbody' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'mindbody', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_mindbody_fields', 'adfoin_get_mindbody_fields' );
function adfoin_get_mindbody_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'FirstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'LastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'Email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'MobilePhone','value' => 'Mobile Phone','description' => '' ),
        array( 'key' => 'HomePhone', 'value' => 'Home Phone', 'description' => '' ),
        array( 'key' => 'WorkPhone', 'value' => 'Work Phone', 'description' => '' ),
        array( 'key' => 'BirthDate', 'value' => 'Birth Date', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'Gender',    'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'AddressLine1','value' => 'Address',  'description' => '' ),
        array( 'key' => 'AddressLine2','value' => 'Address 2','description' => '' ),
        array( 'key' => 'City',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'State',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'PostalCode','value' => 'Postal Code','description' => '' ),
        array( 'key' => 'Country',   'value' => 'Country',    'description' => '' ),
        array( 'key' => 'ReferredBy','value' => 'Referred By','description' => '' ),
        array( 'key' => 'EmergencyContactInfoName',  'value' => 'Emergency Contact Name',  'description' => '' ),
        array( 'key' => 'EmergencyContactInfoPhone', 'value' => 'Emergency Contact Phone', 'description' => '' ),
        array( 'key' => 'LiabilityRelease', 'value' => 'Liability Release (true/false)', 'description' => '' ),
        array( 'key' => 'IsProspect', 'value' => 'Is Prospect (true/false)', 'description' => 'Defaults to true — most web-form submissions are prospects, not paying clients yet.' ),
        array( 'key' => 'SendAccountEmails',  'value' => 'Send Account Emails (true/false)',  'description' => '' ),
        array( 'key' => 'SendPromotionalEmails','value' => 'Send Promotional Emails (true/false)','description' => '' ),
        array( 'key' => 'Notes',     'value' => 'Notes',      'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_mindbody_credentials_list() {
    foreach ( adfoin_read_credentials( 'mindbody' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_mindbody_get_token( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'mindbody', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )         ? $credentials['apiKey']         : '';
    $site_id     = isset( $credentials['siteId'] )         ? $credentials['siteId']         : '';
    $username    = isset( $credentials['sourceName'] )     ? $credentials['sourceName']     : '';
    $password    = isset( $credentials['sourcePassword'] ) ? $credentials['sourcePassword'] : '';

    if ( ! $api_key || ! $site_id || ! $username || ! $password ) return '';

    $response = wp_remote_post( 'https://api.mindbodyonline.com/public/v6/usertoken/issue', array(
        'timeout' => 30,
        'headers' => array(
            'Api-Key'      => $api_key,
            'SiteId'       => $site_id,
            'Content-Type' => 'application/json',
        ),
        'body'    => wp_json_encode( array( 'Username' => $username, 'Password' => $password ) ),
    ) );
    if ( is_wp_error( $response ) ) return '';
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    return isset( $body['AccessToken'] ) ? $body['AccessToken'] : '';
}

function adfoin_mindbody_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mindbody', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $site_id     = isset( $credentials['siteId'] ) ? $credentials['siteId'] : '';

    if ( ! $api_key || ! $site_id ) return;

    $token = adfoin_mindbody_get_token( $cred_id );
    if ( ! $token ) return;

    $url  = 'https://api.mindbodyonline.com/public/v6/' . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Api-Key'       => $api_key,
            'SiteId'        => $site_id,
            'Authorization' => $token,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * Field names confirmed against Mindbody's AddClientRequest model
 * (Swagger-generated SDK docs). Emergency contact fields nest under
 * "EmergencyContactInfo*", and the liability flag is "LiabilityRelease" —
 * not the "EmergencyContactName"/"LiabilityAgreementOnFile" this used to
 * send, which Mindbody would have silently ignored (unknown JSON properties
 * are dropped, not rejected).
 * @link https://developers.mindbodyonline.com/PublicDocumentation/V6
 */
function adfoin_mindbody_create_client( $fields, $record, $cred_id ) {
    $client = array();
    foreach ( array( 'FirstName', 'LastName', 'Email', 'MobilePhone', 'HomePhone', 'WorkPhone', 'BirthDate', 'Gender', 'AddressLine1', 'AddressLine2', 'City', 'State', 'PostalCode', 'Country', 'ReferredBy', 'EmergencyContactInfoName', 'EmergencyContactInfoPhone', 'Notes' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $client[ $k ] = $fields[ $k ];
    }
    // Most web-form submissions are prospects, not paying clients yet —
    // default IsProspect to true unless explicitly overridden.
    $client['IsProspect'] = isset( $fields['IsProspect'] ) && $fields['IsProspect'] !== ''
        ? filter_var( $fields['IsProspect'], FILTER_VALIDATE_BOOLEAN )
        : true;
    foreach ( array( 'LiabilityRelease', 'SendAccountEmails', 'SendPromotionalEmails' ) as $k ) {
        if ( isset( $fields[ $k ] ) && $fields[ $k ] !== '' ) {
            $client[ $k ] = filter_var( $fields[ $k ], FILTER_VALIDATE_BOOLEAN );
        }
    }
    return adfoin_mindbody_request( 'client/addclient', 'POST', $client, $record, $cred_id );
}

add_action( 'adfoin_mindbody_job_queue', 'adfoin_mindbody_job_queue', 10, 1 );
function adfoin_mindbody_job_queue( $data ) {
    adfoin_mindbody_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mindbody_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_client' ) {
        adfoin_mindbody_create_client( $fields, $record, $cred_id );
    }
}
