<?php

add_filter( 'adfoin_action_providers', 'adfoin_tebra_actions', 10, 1 );
function adfoin_tebra_actions( $actions ) {
    $actions['tebra'] = array(
        'title' => 'Tebra (Kareo)',
        'tasks' => array( 'create_patient' => 'Create Patient' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_tebra_settings_tab', 10, 1 );
function adfoin_tebra_settings_tab( $providers ) {
    $providers['tebra'] = 'Tebra (Kareo)';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_tebra_settings_view', 10, 1 );
function adfoin_tebra_settings_view( $current_tab ) {
    if ( $current_tab !== 'tebra' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'tebra',
        'fields'   => array(
            array( 'key' => 'customerKey', 'label' => __( 'Customer Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'username',    'label' => __( 'Username', 'advanced-form-integration' ) ),
            array( 'key' => 'password',    'label' => __( 'Password', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'practiceName','label' => __( 'Practice Name', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Tebra (Kareo) uses a SOAP API. Request a Customer Key from Tebra Support, then enter the integration username/password and your practice name.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Tebra (Kareo)', 'advanced-form-integration' ), 'tebra', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_tebra_action_fields' );
function adfoin_tebra_action_fields() {
    ?>
    <script type="text/template" id="tebra-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_patient'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_patient'">
                <td scope="row-title"><label><?php esc_attr_e( 'Tebra Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_tebra_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_patient', 'Tebra (Kareo) [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_tebra_credentials', 'adfoin_get_tebra_credentials' );
function adfoin_get_tebra_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'tebra' ) );
}

add_action( 'wp_ajax_adfoin_save_tebra_credentials', 'adfoin_save_tebra_credentials' );
function adfoin_save_tebra_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'tebra' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'tebra', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_tebra_fields', 'adfoin_get_tebra_fields' );
function adfoin_get_tebra_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Home Phone', 'description' => '' ),
        array( 'key' => 'mobile',    'value' => 'Mobile Phone', 'description' => '' ),
        array( 'key' => 'workPhone', 'value' => 'Work Phone', 'description' => '' ),
        array( 'key' => 'dob',       'value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',    'value' => 'Gender',     'description' => 'M / F / U' ),
        array( 'key' => 'ssn',       'value' => 'SSN',        'description' => '' ),
        array( 'key' => 'address',   'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'caseTypeId','value' => 'Case Type ID','description' => '' ),
        array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_tebra_credentials_list() {
    foreach ( adfoin_read_credentials( 'tebra' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_tebra_request( $action, $params = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'tebra', $cred_id );
    $customer    = isset( $credentials['customerKey'] )  ? $credentials['customerKey']  : '';
    $username    = isset( $credentials['username'] )     ? $credentials['username']     : '';
    $password    = isset( $credentials['password'] )     ? $credentials['password']     : '';
    $practice    = isset( $credentials['practiceName'] ) ? $credentials['practiceName'] : '';

    if ( ! $customer || ! $username || ! $password ) return;

    $url     = 'https://webservice.kareo.com/services/soap/2.1/KareoServices.svc';
    $request = array_merge( array(
        'CustomerKey'  => $customer,
        'User'         => $username,
        'Password'     => $password,
        'PracticeName' => $practice,
    ), $params );

    $args = array(
        'timeout' => 45,
        'method'  => 'POST',
        'headers' => array(
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction'   => 'http://www.kareo.com/api/schemas/KareoServices/' . $action,
        ),
        'body'    => adfoin_tebra_build_envelope( $action, $request ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_tebra_build_envelope( $action, $params ) {
    $body = '';
    foreach ( $params as $key => $value ) {
        if ( is_array( $value ) ) {
            $body .= "<{$key}>" . adfoin_tebra_build_envelope( '', $value ) . "</{$key}>";
        } else {
            $body .= "<{$key}>" . htmlspecialchars( (string) $value, ENT_XML1, 'UTF-8' ) . "</{$key}>";
        }
    }
    if ( ! $action ) return $body;
    return '<?xml version="1.0" encoding="utf-8"?>' .
        '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ker="http://www.kareo.com/api/schemas/">' .
        '<soap:Body><ker:' . $action . '><ker:request>' . $body . '</ker:request></ker:' . $action . '></soap:Body>' .
        '</soap:Envelope>';
}

function adfoin_tebra_create_patient( $fields, $record, $cred_id ) {
    $patient = array();
    foreach ( array( 'firstName', 'middleName', 'lastName', 'email', 'phone', 'mobile', 'workPhone', 'dob', 'gender', 'ssn', 'note', 'caseTypeId' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $patient[ $k ] = $fields[ $k ];
    }
    foreach ( array( 'address', 'city', 'state', 'zip' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $patient[ $k ] = $fields[ $k ];
    }
    return adfoin_tebra_request( 'CreatePatient', array( 'Patient' => $patient ), $record, $cred_id );
}

add_action( 'adfoin_tebra_job_queue', 'adfoin_tebra_job_queue', 10, 1 );
function adfoin_tebra_job_queue( $data ) {
    adfoin_tebra_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_tebra_send_data( $record, $posted_data ) {
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
        adfoin_tebra_create_patient( $fields, $record, $cred_id );
    }
}
