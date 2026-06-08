<?php

add_filter( 'adfoin_action_providers', 'adfoin_tithely_actions', 10, 1 );
function adfoin_tithely_actions( $actions ) {
    $actions['tithely'] = array(
        'title' => 'Tithe.ly',
        'tasks' => array( 'create_person' => 'Create Person / Subscriber' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_tithely_settings_tab', 10, 1 );
function adfoin_tithely_settings_tab( $providers ) { $providers['tithely'] = 'Tithe.ly'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_tithely_settings_view', 10, 1 );
function adfoin_tithely_settings_view( $current_tab ) {
    if ( $current_tab !== 'tithely' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'tithely',
        'fields'   => array(
            array( 'key' => 'accessToken', 'label' => __( 'OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'orgId',       'label' => __( 'Organization ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Tithe.ly Developer Console, create an OAuth app. Complete the OAuth flow to obtain an access token, then paste it along with your Organization ID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Tithe.ly', 'advanced-form-integration' ), 'tithely', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_tithely_action_fields' );
function adfoin_tithely_action_fields() {
    ?>
    <script type="text/template" id="tithely-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_person'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Tithe.ly Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_tithely_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_person', 'Tithe.ly [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_tithely_credentials', 'adfoin_get_tithely_credentials' );
function adfoin_get_tithely_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'tithely' ) );
}

add_action( 'wp_ajax_adfoin_save_tithely_credentials', 'adfoin_save_tithely_credentials' );
function adfoin_save_tithely_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'tithely' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'tithely', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_tithely_fields', 'adfoin_get_tithely_fields' );
function adfoin_get_tithely_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'birthdate',  'value' => 'Birthdate',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',     'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'street',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',    'value' => 'Country',    'description' => '' ),
        array( 'key' => 'note',       'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_tithely_credentials_list() {
    foreach ( adfoin_read_credentials( 'tithely' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_tithely_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'tithely', $cred_id );
    $token       = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
    $org_id      = isset( $credentials['orgId'] )       ? $credentials['orgId']       : '';
    if ( ! $token ) return;

    $url  = 'https://api.tithe.ly/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'X-Org-Id'      => $org_id,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_tithely_job_queue', 'adfoin_tithely_job_queue', 10, 1 );
function adfoin_tithely_job_queue( $data ) {
    adfoin_tithely_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_tithely_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_person' ) return;

    $body = array();
    foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'birthdate', 'gender', 'note' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    $addr = array();
    foreach ( array( 'street', 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $addr[ $k ] = $fields[ $k ];
    }
    if ( $addr ) $body['address'] = $addr;

    adfoin_tithely_request( 'people', 'POST', $body, $record, $cred_id );
}
