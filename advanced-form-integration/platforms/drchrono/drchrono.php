<?php

add_filter( 'adfoin_action_providers', 'adfoin_drchrono_actions', 10, 1 );
function adfoin_drchrono_actions( $actions ) {
    $actions['drchrono'] = array(
        'title' => 'DrChrono',
        'tasks' => array( 'create_patient' => 'Create Patient' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_drchrono_settings_tab', 10, 1 );
function adfoin_drchrono_settings_tab( $providers ) {
    $providers['drchrono'] = 'DrChrono';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_drchrono_settings_view', 10, 1 );
function adfoin_drchrono_settings_view( $current_tab ) {
    if ( $current_tab !== 'drchrono' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'drchrono',
        'fields'   => array(
            array( 'key' => 'accessToken', 'label' => __( 'OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'doctorId',    'label' => __( 'Default Doctor ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In your DrChrono developer account create an API application, exchange an OAuth code for an access token, then paste the token and your default doctor ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'DrChrono', 'advanced-form-integration' ), 'drchrono', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_drchrono_action_fields' );
function adfoin_drchrono_action_fields() {
    ?>
    <script type="text/template" id="drchrono-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_patient'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_patient'">
                <td scope="row-title"><label><?php esc_attr_e( 'DrChrono Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_drchrono_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_patient', 'DrChrono [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_drchrono_credentials', 'adfoin_get_drchrono_credentials' );
function adfoin_get_drchrono_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'drchrono' ) );
}

add_action( 'wp_ajax_adfoin_save_drchrono_credentials', 'adfoin_save_drchrono_credentials' );
function adfoin_save_drchrono_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'drchrono' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'drchrono', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_drchrono_fields', 'adfoin_get_drchrono_fields' );
function adfoin_get_drchrono_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'middle_name','value' => 'Middle Name','description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'cell_phone', 'value' => 'Cell Phone', 'description' => '' ),
        array( 'key' => 'home_phone', 'value' => 'Home Phone', 'description' => '' ),
        array( 'key' => 'office_phone','value' => 'Office Phone','description' => '' ),
        array( 'key' => 'date_of_birth','value' => 'Date of Birth', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',     'value' => 'Gender',     'description' => 'Male, Female, Other' ),
        array( 'key' => 'ssn',        'value' => 'SSN',        'description' => '' ),
        array( 'key' => 'address',    'value' => 'Address',    'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip_code',   'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'patient_status','value' => 'Patient Status', 'description' => 'active / inactive / prospective' ),
        array( 'key' => 'doctor',     'value' => 'Doctor ID Override', 'description' => 'Optional, overrides default' ),
        array( 'key' => 'note',       'value' => 'Chart Note', 'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_drchrono_credentials_list() {
    foreach ( adfoin_read_credentials( 'drchrono' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_drchrono_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'drchrono', $cred_id );
    $token       = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';

    if ( ! $token ) return;

    $url  = 'https://drchrono.com/api/' . $endpoint;
    $args = array(
        'timeout' => 30,
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

function adfoin_drchrono_create_patient( $fields, $record, $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'drchrono', $cred_id );
    $body = array();
    foreach ( array( 'first_name', 'middle_name', 'last_name', 'email', 'cell_phone', 'home_phone', 'office_phone', 'date_of_birth', 'gender', 'ssn', 'address', 'city', 'state', 'zip_code', 'patient_status', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $body['doctor'] = ! empty( $fields['doctor'] ) ? $fields['doctor'] : ( isset( $credentials['doctorId'] ) ? $credentials['doctorId'] : '' );
    return adfoin_drchrono_request( 'patients', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_drchrono_job_queue', 'adfoin_drchrono_job_queue', 10, 1 );
function adfoin_drchrono_job_queue( $data ) {
    adfoin_drchrono_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_drchrono_send_data( $record, $posted_data ) {
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
        adfoin_drchrono_create_patient( $fields, $record, $cred_id );
    }
}
