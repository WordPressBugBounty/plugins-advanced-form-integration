<?php

add_filter( 'adfoin_action_providers', 'adfoin_donorperfect_actions', 10, 1 );
function adfoin_donorperfect_actions( $actions ) {
    $actions['donorperfect'] = array(
        'title' => 'DonorPerfect',
        'tasks' => array(
            'create_donor' => 'Create Donor',
            'create_gift'  => 'Create Gift',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_donorperfect_settings_tab', 10, 1 );
function adfoin_donorperfect_settings_tab( $providers ) { $providers['donorperfect'] = 'DonorPerfect'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_donorperfect_settings_view', 10, 1 );
function adfoin_donorperfect_settings_view( $current_tab ) {
    if ( $current_tab !== 'donorperfect' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'donorperfect',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In DonorPerfect, go to Settings > Integrations > API. Request an API token from your account manager and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'DonorPerfect', 'advanced-form-integration' ), 'donorperfect', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_donorperfect_action_fields' );
function adfoin_donorperfect_action_fields() {
    ?>
    <script type="text/template" id="donorperfect-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_donor' || action.task == 'create_gift'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_donor' || action.task == 'create_gift'">
                <td scope="row-title"><label><?php esc_attr_e( 'DonorPerfect Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_donorperfect_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_donor', 'DonorPerfect [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_donorperfect_credentials', 'adfoin_get_donorperfect_credentials' );
function adfoin_get_donorperfect_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'donorperfect' ) );
}

add_action( 'wp_ajax_adfoin_save_donorperfect_credentials', 'adfoin_save_donorperfect_credentials' );
function adfoin_save_donorperfect_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'donorperfect' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'donorperfect', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_donorperfect_fields', 'adfoin_get_donorperfect_fields' );
function adfoin_get_donorperfect_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_donor';

    if ( $task === 'create_gift' ) {
        $fields = array(
            array( 'key' => 'donorEmail', 'value' => 'Donor Email',   'description' => 'Used to look up the donor' ),
            array( 'key' => 'amount',     'value' => 'Amount',         'description' => '' ),
            array( 'key' => 'date',       'value' => 'Gift Date',      'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'method',     'value' => 'Gift Method',    'description' => 'Check / Credit / Cash' ),
            array( 'key' => 'campaign',   'value' => 'Campaign Code',  'description' => '' ),
            array( 'key' => 'fund',       'value' => 'Fund Code',      'description' => '' ),
            array( 'key' => 'solicit',    'value' => 'Solicitation Code', 'description' => '' ),
            array( 'key' => 'note',       'value' => 'Note',           'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'organization','value' => 'Organization', 'description' => '' ),
            array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'donorType', 'value' => 'Donor Type', 'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_donorperfect_credentials_list() {
    foreach ( adfoin_read_credentials( 'donorperfect' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_donorperfect_request( $action, $params = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'donorperfect', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $params['apikey'] = $api_key;
    $params['action'] = $action;
    $url = 'https://www.donorperfect.io/prod/xmlrequest.asp?' . http_build_query( $params );

    $args = array( 'timeout' => 30, 'method' => 'GET' );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_donorperfect_job_queue', 'adfoin_donorperfect_job_queue', 10, 1 );
function adfoin_donorperfect_job_queue( $data ) {
    adfoin_donorperfect_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_donorperfect_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $record['task'] === 'create_donor' ) {
        $params = array();
        foreach ( array( 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'home_phone', 'organization' => 'org_name', 'address' => 'address', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country', 'donorType' => 'donor_type', 'note' => 'narrative' ) as $local => $remote ) {
            if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $params[ $remote ] = $fields[ $local ];
        }
        adfoin_donorperfect_request( 'savedonor', $params, $record, $cred_id );
    } elseif ( $record['task'] === 'create_gift' ) {
        $params = array();
        foreach ( array( 'donorEmail' => 'email', 'amount' => 'amount', 'date' => 'gift_date', 'method' => 'gift_type', 'campaign' => 'campaign', 'fund' => 'fund', 'solicit' => 'solicit_code', 'note' => 'narrative' ) as $local => $remote ) {
            if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $params[ $remote ] = $fields[ $local ];
        }
        adfoin_donorperfect_request( 'savegift', $params, $record, $cred_id );
    }
}
