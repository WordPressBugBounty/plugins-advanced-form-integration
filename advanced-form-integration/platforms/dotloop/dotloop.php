<?php

add_filter( 'adfoin_action_providers', 'adfoin_dotloop_actions', 10, 1 );
function adfoin_dotloop_actions( $actions ) {
    $actions['dotloop'] = array(
        'title' => 'Dotloop',
        'tasks' => array(
            'create_loop'   => 'Create Loop',
            'create_person' => 'Add Person to Loop',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_dotloop_settings_tab', 10, 1 );
function adfoin_dotloop_settings_tab( $providers ) { $providers['dotloop'] = 'Dotloop'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_dotloop_settings_view', 10, 1 );
function adfoin_dotloop_settings_view( $current_tab ) {
    if ( $current_tab !== 'dotloop' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'dotloop',
        'fields'   => array(
            array( 'key' => 'accessToken','label' => __( 'OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'profileId',  'label' => __( 'Profile ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Register an app in the Dotloop Developer Portal, complete the OAuth flow, and paste the resulting access token. Your Profile ID identifies which Dotloop profile receives loops.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Dotloop', 'advanced-form-integration' ), 'dotloop', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_dotloop_action_fields' );
function adfoin_dotloop_action_fields() {
    ?>
    <script type="text/template" id="dotloop-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_loop' || action.task == 'create_person'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_loop' || action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Dotloop Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_dotloop_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_loop', 'Dotloop [PRO]', 'tags and extra fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_dotloop_credentials', 'adfoin_get_dotloop_credentials' );
function adfoin_get_dotloop_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'dotloop' ) );
}

add_action( 'wp_ajax_adfoin_save_dotloop_credentials', 'adfoin_save_dotloop_credentials' );
function adfoin_save_dotloop_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'dotloop' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'dotloop', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_dotloop_fields', 'adfoin_get_dotloop_fields' );
function adfoin_get_dotloop_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $task = isset( $_POST['task'] ) ? sanitize_text_field( $_POST['task'] ) : 'create_loop';

    if ( $task === 'create_person' ) {
        $fields = array(
            array( 'key' => 'loopId',    'value' => 'Loop ID',    'description' => 'Existing loop to attach this person to' ),
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'role',      'value' => 'Role',       'description' => 'BUYER / SELLER / LISTING_AGENT / BUYING_AGENT / etc.' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'name',           'value' => 'Loop Name',       'description' => '' ),
            array( 'key' => 'transactionType','value' => 'Transaction Type','description' => 'PURCHASE_OFFER / LISTING_FOR_SALE / etc.' ),
            array( 'key' => 'status',         'value' => 'Status',          'description' => 'ACTIVE / ARCHIVED' ),
            array( 'key' => 'street',         'value' => 'Property Street', 'description' => '' ),
            array( 'key' => 'city',           'value' => 'Property City',   'description' => '' ),
            array( 'key' => 'state',          'value' => 'Property State',  'description' => '' ),
            array( 'key' => 'zip',            'value' => 'Property Zip',    'description' => '' ),
            array( 'key' => 'price',          'value' => 'Sale Price',      'description' => '' ),
            array( 'key' => 'firstName',      'value' => 'Client First Name', 'description' => '' ),
            array( 'key' => 'lastName',       'value' => 'Client Last Name','description' => '' ),
            array( 'key' => 'email',          'value' => 'Client Email',    'description' => '' ),
            array( 'key' => 'phone',          'value' => 'Client Phone',    'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_dotloop_credentials_list() {
    foreach ( adfoin_read_credentials( 'dotloop' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_dotloop_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'dotloop', $cred_id );
    $token       = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
    if ( ! $token ) return;

    $url  = 'https://api-gateway.dotloop.com/public/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_dotloop_job_queue', 'adfoin_dotloop_job_queue', 10, 1 );
function adfoin_dotloop_job_queue( $data ) {
    adfoin_dotloop_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_dotloop_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    $credentials = adfoin_get_credentials_by_id( 'dotloop', $cred_id );
    $profile_id  = isset( $credentials['profileId'] ) ? $credentials['profileId'] : '';
    if ( ! $profile_id ) return;

    if ( $record['task'] === 'create_loop' ) {
        $body = array(
            'name'            => isset( $fields['name'] )            ? $fields['name']            : '',
            'transactionType' => isset( $fields['transactionType'] ) ? $fields['transactionType'] : 'PURCHASE_OFFER',
            'status'          => isset( $fields['status'] )          ? $fields['status']          : 'ACTIVE',
        );
        $prop = array();
        foreach ( array( 'street' => 'streetName', 'city' => 'city', 'state' => 'state', 'zip' => 'zipCode' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $prop[ $remote ] = $fields[ $local ];
        }
        if ( $prop ) $body['propertyAddress'] = $prop;
        if ( ! empty( $fields['price'] ) ) $body['contractAgreement'] = array( 'salePrice' => floatval( $fields['price'] ) );

        $response = adfoin_dotloop_request( "profile/{$profile_id}/loop", 'POST', $body, $record, $cred_id );

        if ( ! empty( $fields['email'] ) && ! is_wp_error( $response ) ) {
            $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
            $loop_id   = isset( $resp_body['id'] ) ? $resp_body['id'] : '';
            if ( $loop_id ) {
                adfoin_dotloop_request( "profile/{$profile_id}/loop/{$loop_id}/participant", 'POST', array(
                    'firstName' => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
                    'lastName'  => isset( $fields['lastName'] )  ? $fields['lastName']  : '',
                    'email'     => $fields['email'],
                    'phone'     => isset( $fields['phone'] ) ? $fields['phone'] : '',
                    'role'      => 'BUYER',
                ), $record, $cred_id );
            }
        }
    } elseif ( $record['task'] === 'create_person' ) {
        $loop_id = isset( $fields['loopId'] ) ? $fields['loopId'] : '';
        if ( ! $loop_id ) return;
        $body = array();
        foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'role' ) as $k ) {
            if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
        }
        adfoin_dotloop_request( "profile/{$profile_id}/loop/{$loop_id}/participant", 'POST', $body, $record, $cred_id );
    }
}
