<?php

add_filter( 'adfoin_action_providers', 'adfoin_planningcenter_actions', 10, 1 );
function adfoin_planningcenter_actions( $actions ) {
    $actions['planningcenter'] = array(
        'title' => 'Planning Center',
        'tasks' => array( 'create_person' => 'Create Person (People)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_planningcenter_settings_tab', 10, 1 );
function adfoin_planningcenter_settings_tab( $providers ) { $providers['planningcenter'] = 'Planning Center'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_planningcenter_settings_view', 10, 1 );
function adfoin_planningcenter_settings_view( $current_tab ) {
    if ( $current_tab !== 'planningcenter' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'planningcenter',
        'fields'   => array(
            array( 'key' => 'appId',     'label' => __( 'Application ID', 'advanced-form-integration' ) ),
            array( 'key' => 'secret',    'label' => __( 'Secret', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Planning Center, go to your Personal Access Tokens page (api.planningcenteronline.com/oauth/applications). Create a Personal Access Token and copy the Application ID and Secret.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Planning Center', 'advanced-form-integration' ), 'planningcenter', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_planningcenter_action_fields' );
function adfoin_planningcenter_action_fields() {
    ?>
    <script type="text/template" id="planningcenter-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_person'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Planning Center Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_planningcenter_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_person', 'Planning Center [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_planningcenter_credentials', 'adfoin_get_planningcenter_credentials' );
function adfoin_get_planningcenter_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'planningcenter' ) );
}

add_action( 'wp_ajax_adfoin_save_planningcenter_credentials', 'adfoin_save_planningcenter_credentials' );
function adfoin_save_planningcenter_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'planningcenter' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'planningcenter', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_planningcenter_fields', 'adfoin_get_planningcenter_fields' );
function adfoin_get_planningcenter_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'middle_name','value' => 'Middle Name','description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'nickname',   'value' => 'Nickname',   'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'birthdate',  'value' => 'Birthdate',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',     'value' => 'Gender',     'description' => 'Male / Female' ),
        array( 'key' => 'street',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'status',     'value' => 'Status',     'description' => 'active / inactive' ),
        array( 'key' => 'membership', 'value' => 'Membership', 'description' => 'Member / Visitor / etc.' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_planningcenter_credentials_list() {
    foreach ( adfoin_read_credentials( 'planningcenter' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_planningcenter_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'planningcenter', $cred_id );
    $app_id      = isset( $credentials['appId'] )  ? $credentials['appId']  : '';
    $secret      = isset( $credentials['secret'] ) ? $credentials['secret'] : '';
    if ( ! $app_id || ! $secret ) return;

    $url  = 'https://api.planningcenteronline.com/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $app_id . ':' . $secret ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_planningcenter_create_person( $fields, $record, $cred_id ) {
    $attrs = array();
    foreach ( array( 'first_name', 'middle_name', 'last_name', 'nickname', 'birthdate', 'gender', 'status', 'membership' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $attrs[ $k ] = $fields[ $k ];
    }
    $body = array( 'data' => array( 'type' => 'Person', 'attributes' => $attrs ) );
    $response = adfoin_planningcenter_request( 'people/v2/people', 'POST', $body, $record, $cred_id );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) return $response;
    $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
    $person_id = isset( $resp_body['data']['id'] ) ? $resp_body['data']['id'] : '';
    if ( ! $person_id ) return $response;

    if ( ! empty( $fields['email'] ) ) {
        adfoin_planningcenter_request( "people/v2/people/{$person_id}/emails", 'POST', array(
            'data' => array( 'type' => 'Email', 'attributes' => array( 'address' => $fields['email'], 'primary' => true ) ),
        ), $record, $cred_id );
    }
    if ( ! empty( $fields['phone'] ) ) {
        adfoin_planningcenter_request( "people/v2/people/{$person_id}/phone_numbers", 'POST', array(
            'data' => array( 'type' => 'PhoneNumber', 'attributes' => array( 'number' => $fields['phone'], 'primary' => true ) ),
        ), $record, $cred_id );
    }
    if ( ! empty( $fields['street'] ) || ! empty( $fields['city'] ) || ! empty( $fields['zip'] ) ) {
        $addr_attrs = array();
        foreach ( array( 'street' => 'street_line_1', 'city' => 'city', 'state' => 'state', 'zip' => 'zip' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $addr_attrs[ $remote ] = $fields[ $local ];
        }
        adfoin_planningcenter_request( "people/v2/people/{$person_id}/addresses", 'POST', array(
            'data' => array( 'type' => 'Address', 'attributes' => $addr_attrs ),
        ), $record, $cred_id );
    }

    return $response;
}

add_action( 'adfoin_planningcenter_job_queue', 'adfoin_planningcenter_job_queue', 10, 1 );
function adfoin_planningcenter_job_queue( $data ) {
    adfoin_planningcenter_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_planningcenter_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_person' ) {
        adfoin_planningcenter_create_person( $fields, $record, $cred_id );
    }
}
