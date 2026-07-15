<?php

add_filter( 'adfoin_action_providers', 'adfoin_ezlynx_actions', 10, 1 );
function adfoin_ezlynx_actions( $actions ) {
    $actions['ezlynx'] = array(
        'title' => 'EZLynx',
        'tasks' => array( 'create_applicant' => 'Create Applicant / Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ezlynx_settings_tab', 10, 1 );
function adfoin_ezlynx_settings_tab( $providers ) { $providers['ezlynx'] = 'EZLynx'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_ezlynx_settings_view', 10, 1 );
function adfoin_ezlynx_settings_view( $current_tab ) {
    if ( $current_tab !== 'ezlynx' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'ezlynx',
        'fields'   => array(
            array( 'key' => 'ezUser',           'label' => __( 'EZLynx Username (EZUser)', 'advanced-form-integration' ) ),
            array( 'key' => 'ezPassword',       'label' => __( 'EZLynx Password (EZPassword)', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'ezAppSecret',      'label' => __( 'App Secret (EZAppSecret)', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'accountUsername',  'label' => __( 'Account Username', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'EZLynx uses its own session-token scheme (not Basic auth or OAuth). Request API access from EZLynx to get an App Secret, then create a dedicated integration user and enter its username/password along with the account username above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'EZLynx', 'advanced-form-integration' ), 'ezlynx', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_ezlynx_action_fields' );
function adfoin_ezlynx_action_fields() {
    ?>
    <script type="text/template" id="ezlynx-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_applicant'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_applicant'">
                <td scope="row-title"><label><?php esc_attr_e( 'EZLynx Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_ezlynx_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_applicant', 'EZLynx [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_ezlynx_credentials', 'adfoin_get_ezlynx_credentials' );
function adfoin_get_ezlynx_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'ezlynx' ) );
}

add_action( 'wp_ajax_adfoin_save_ezlynx_credentials', 'adfoin_save_ezlynx_credentials' );
function adfoin_save_ezlynx_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'ezlynx' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'ezlynx', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_ezlynx_fields', 'adfoin_get_ezlynx_fields' );
function adfoin_get_ezlynx_fields() {
    adfoin_verify_nonce();
    // leadSource/lineOfBusiness/note dropped: not present anywhere in
    // EZLynx's confirmed real Applicant/v2 schema (Line of Business lives
    // on Opportunity records, not the Applicant, and there's no free-text
    // notes field on Applicant at all).
    $fields = array(
        array( 'key' => 'firstName',    'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'middleName',   'value' => 'Middle Name','description' => '' ),
        array( 'key' => 'lastName',     'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',        'value' => 'Email',      'description' => '' ),
        array( 'key' => 'cellPhone',    'value' => 'Cell Phone', 'description' => '' ),
        array( 'key' => 'homePhone',    'value' => 'Home Phone', 'description' => '' ),
        array( 'key' => 'workPhone',    'value' => 'Work Phone', 'description' => '' ),
        array( 'key' => 'dob',          'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',       'value' => 'Gender',     'description' => 'Male / Female' ),
        array( 'key' => 'maritalStatus','value' => 'Marital Status', 'description' => '' ),
        array( 'key' => 'ssn',          'value' => 'SSN',        'description' => '' ),
        array( 'key' => 'address',      'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',         'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',        'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',          'value' => 'Zip',        'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_ezlynx_credentials_list() {
    foreach ( adfoin_read_credentials( 'ezlynx' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * EZLynx does not use Basic auth or OAuth — it uses a proprietary
 * session-token scheme confirmed via EZLynx's own official Postman
 * collection: GET {base}authenticate with EZUser/EZPassword/EZAppSecret
 * headers (plus a literal EZToken:"authenticate" placeholder) returns a
 * session token in the EZToken response header, which must then be sent
 * on every subsequent request alongside EZAppSecret and AccountUsername.
 * Base URL confirmed from real captured traffic in that same collection
 * (services.{env}.devezlynx.com/EZLynxAPI/api/); production drops ".test".
 */
function adfoin_ezlynx_get_token( $cred_id ) {
    $cache_key = 'adfoin_ezlynx_token_' . md5( $cred_id );
    $cached    = get_transient( $cache_key );
    if ( $cached ) return $cached;

    $credentials = adfoin_get_credentials_by_id( 'ezlynx', $cred_id );
    $ez_user     = isset( $credentials['ezUser'] )      ? $credentials['ezUser']      : '';
    $ez_password = isset( $credentials['ezPassword'] )  ? $credentials['ezPassword']  : '';
    $app_secret  = isset( $credentials['ezAppSecret'] ) ? $credentials['ezAppSecret'] : '';
    if ( ! $ez_user || ! $ez_password || ! $app_secret ) return '';

    $response = wp_remote_request( 'https://services.devezlynx.com/EZLynxAPI/api/authenticate', array(
        'timeout' => 30,
        'method'  => 'GET',
        'headers' => array(
            'EZUser'      => $ez_user,
            'EZPassword'  => $ez_password,
            'EZAppSecret' => $app_secret,
            'EZToken'     => 'authenticate',
        ),
    ) );
    if ( is_wp_error( $response ) ) return '';
    $token = wp_remote_retrieve_header( $response, 'EZToken' );
    if ( $token ) set_transient( $cache_key, $token, 10 * MINUTE_IN_SECONDS );
    return $token;
}

function adfoin_ezlynx_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'ezlynx', $cred_id );
    $app_secret  = isset( $credentials['ezAppSecret'] )     ? $credentials['ezAppSecret']     : '';
    $account_uid = isset( $credentials['accountUsername'] ) ? $credentials['accountUsername'] : '';
    if ( ! $app_secret ) return;

    $token = adfoin_ezlynx_get_token( $cred_id );
    if ( ! $token ) return;

    $url  = 'https://services.devezlynx.com/EZLynxAPI/api/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'EZAppSecret'     => $app_secret,
            'EZToken'         => $token,
            'AccountUsername' => $account_uid,
            'Content-Type'    => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_ezlynx_job_queue', 'adfoin_ezlynx_job_queue', 10, 1 );
function adfoin_ezlynx_job_queue( $data ) {
    adfoin_ezlynx_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ezlynx_build_applicant( $fields ) {
    $body = array();
    foreach ( array( 'firstName' => 'FirstName', 'middleName' => 'MiddleName', 'lastName' => 'LastName', 'email' => 'Email', 'cellPhone' => 'CellPhone', 'homePhone' => 'HomePhone', 'workPhone' => 'WorkPhone', 'dob' => 'DOB', 'gender' => 'Gender', 'maritalStatus' => 'MaritalStatus', 'ssn' => 'SSN' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    $addr = array();
    foreach ( array( 'address' => 'AddressLine1', 'city' => 'City', 'state' => 'State', 'zip' => 'Zip' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['CurrentAddress'] = $addr;
    return $body;
}

function adfoin_ezlynx_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_applicant' ) return;

    adfoin_ezlynx_request( 'Applicant/v2/', 'POST', adfoin_ezlynx_build_applicant( $fields ), $record, $cred_id );
}
