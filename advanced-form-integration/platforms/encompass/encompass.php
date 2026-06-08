<?php

add_filter( 'adfoin_action_providers', 'adfoin_encompass_actions', 10, 1 );
function adfoin_encompass_actions( $actions ) {
    $actions['encompass'] = array(
        'title' => 'Encompass (ICE Mortgage)',
        'tasks' => array( 'create_loan' => 'Create Loan from Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_encompass_settings_tab', 10, 1 );
function adfoin_encompass_settings_tab( $providers ) { $providers['encompass'] = 'Encompass'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_encompass_settings_view', 10, 1 );
function adfoin_encompass_settings_view( $current_tab ) {
    if ( $current_tab !== 'encompass' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'encompass',
        'fields'   => array(
            array( 'key' => 'clientId',     'label' => __( 'Client ID', 'advanced-form-integration' ) ),
            array( 'key' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'instanceId',   'label' => __( 'Instance ID', 'advanced-form-integration' ) ),
            array( 'key' => 'username',     'label' => __( 'Encompass Username', 'advanced-form-integration' ) ),
            array( 'key' => 'password',     'label' => __( 'Encompass Password', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'Encompass (ICE Mortgage Technology) requires a partner Client ID + Secret via the Encompass Developer Portal. The integration runs as a specific Encompass user — provide their credentials.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Encompass (ICE Mortgage)', 'advanced-form-integration' ), 'encompass', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_encompass_action_fields' );
function adfoin_encompass_action_fields() {
    ?>
    <script type="text/template" id="encompass-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_loan'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_loan'">
                <td scope="row-title"><label><?php esc_attr_e( 'Encompass Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_encompass_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_loan', 'Encompass [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_encompass_credentials', 'adfoin_get_encompass_credentials' );
function adfoin_get_encompass_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'encompass' ) );
}

add_action( 'wp_ajax_adfoin_save_encompass_credentials', 'adfoin_save_encompass_credentials' );
function adfoin_save_encompass_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'encompass' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'encompass', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_encompass_fields', 'adfoin_get_encompass_fields' );
function adfoin_get_encompass_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',    'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',     'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',        'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',        'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'ssn',          'value' => 'SSN',        'description' => '' ),
        array( 'key' => 'dob',          'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'street',       'value' => 'Property Street', 'description' => '' ),
        array( 'key' => 'city',         'value' => 'Property City',   'description' => '' ),
        array( 'key' => 'state',        'value' => 'Property State',  'description' => '' ),
        array( 'key' => 'zip',          'value' => 'Property Zip',    'description' => '' ),
        array( 'key' => 'loanAmount',   'value' => 'Loan Amount','description' => '' ),
        array( 'key' => 'loanPurpose',  'value' => 'Loan Purpose','description' => 'Purchase / Refinance' ),
        array( 'key' => 'propertyValue','value' => 'Property Value', 'description' => '' ),
        array( 'key' => 'loanProgram',  'value' => 'Loan Program','description' => '' ),
        array( 'key' => 'creditScore',  'value' => 'Credit Score', 'description' => '' ),
        array( 'key' => 'note',         'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_encompass_credentials_list() {
    foreach ( adfoin_read_credentials( 'encompass' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_encompass_get_token( $cred_id ) {
    $credentials   = adfoin_get_credentials_by_id( 'encompass', $cred_id );
    $client_id     = isset( $credentials['clientId'] )     ? $credentials['clientId']     : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
    $instance_id   = isset( $credentials['instanceId'] )   ? $credentials['instanceId']   : '';
    $username      = isset( $credentials['username'] )     ? $credentials['username']     : '';
    $password      = isset( $credentials['password'] )     ? $credentials['password']     : '';

    if ( ! $client_id || ! $client_secret || ! $instance_id || ! $username || ! $password ) return '';

    $response = wp_remote_post( 'https://api.elliemae.com/oauth2/v1/token', array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
        ),
        'body'    => http_build_query( array(
            'grant_type' => 'password',
            'username'   => $username . '@encompass:' . $instance_id,
            'password'   => $password,
            'scope'      => 'lp',
        ) ),
    ) );
    if ( is_wp_error( $response ) ) return '';
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    return isset( $body['access_token'] ) ? $body['access_token'] : '';
}

function adfoin_encompass_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $token = adfoin_encompass_get_token( $cred_id );
    if ( ! $token ) return;

    $url  = 'https://api.elliemae.com/encompass/v3/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 45,
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

add_action( 'adfoin_encompass_job_queue', 'adfoin_encompass_job_queue', 10, 1 );
function adfoin_encompass_job_queue( $data ) {
    adfoin_encompass_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_encompass_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_loan' ) return;

    // Encompass uses the URLA/1003 schema. Map the simple form values into the loan object.
    $applicant = array(
        'firstName' => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
        'lastName'  => isset( $fields['lastName'] )  ? $fields['lastName']  : '',
    );
    if ( ! empty( $fields['ssn'] ) )           $applicant['taxIdentificationIdentifier'] = $fields['ssn'];
    if ( ! empty( $fields['dob'] ) )           $applicant['birthDate']                   = $fields['dob'];

    $contacts = array();
    if ( ! empty( $fields['email'] ) ) $contacts[] = array( 'contactPointTypeUrn' => 'urn:elli:cpt:Email',    'contactPointValue' => $fields['email'] );
    if ( ! empty( $fields['phone'] ) ) $contacts[] = array( 'contactPointTypeUrn' => 'urn:elli:cpt:Phone',    'contactPointValue' => $fields['phone'] );
    if ( $contacts ) $applicant['contactPoints'] = $contacts;

    $property = array();
    foreach ( array( 'street' => 'streetAddress', 'city' => 'city', 'state' => 'state', 'zip' => 'postalCode' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $property[ $remote ] = $fields[ $local ];
    }

    $loan = array(
        'applications'    => array( array( 'borrower' => $applicant ) ),
        'subjectProperty' => $property,
    );
    if ( ! empty( $fields['loanAmount'] ) )    $loan['loanAmount']    = floatval( $fields['loanAmount'] );
    if ( ! empty( $fields['loanPurpose'] ) )   $loan['loanPurpose']   = $fields['loanPurpose'];
    if ( ! empty( $fields['propertyValue'] ) ) $loan['propertyEstimatedValueAmount'] = floatval( $fields['propertyValue'] );
    if ( ! empty( $fields['loanProgram'] ) )   $loan['loanProgramName'] = $fields['loanProgram'];
    if ( ! empty( $fields['creditScore'] ) )   $loan['representativeCreditScore'] = intval( $fields['creditScore'] );
    if ( ! empty( $fields['note'] ) )          $loan['loanFolder'] = array( 'comments' => $fields['note'] );

    adfoin_encompass_request( 'loans', 'POST', $loan, $record, $cred_id );
}
