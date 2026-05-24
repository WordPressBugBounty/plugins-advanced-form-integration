<?php

add_filter( 'adfoin_action_providers', 'adfoin_neoncrm_actions', 10, 1 );
function adfoin_neoncrm_actions( $actions ) {
    $actions['neoncrm'] = array(
        'title' => 'NeonCRM',
        'tasks' => array(
            'create_account' => 'Create Account (Constituent)',
            'create_donation'=> 'Create Donation',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_neoncrm_settings_tab', 10, 1 );
function adfoin_neoncrm_settings_tab( $providers ) { $providers['neoncrm'] = 'NeonCRM'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_neoncrm_settings_view', 10, 1 );
function adfoin_neoncrm_settings_view( $current_tab ) {
    if ( $current_tab !== 'neoncrm' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'neoncrm',
        'fields'   => array(
            array( 'key' => 'orgId',   'label' => __( 'Org ID', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',  'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In NeonCRM, open Global Settings > API Keys. Create a key for an Admin user and copy your Org ID from the dashboard URL.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'NeonCRM', 'advanced-form-integration' ), 'neoncrm', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_neoncrm_action_fields' );
function adfoin_neoncrm_action_fields() {
    ?>
    <script type="text/template" id="neoncrm-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_account' || action.task == 'create_donation'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_account' || action.task == 'create_donation'">
                <td scope="row-title"><label><?php esc_attr_e( 'NeonCRM Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_neoncrm_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_account', 'NeonCRM [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_neoncrm_credentials', 'adfoin_get_neoncrm_credentials' );
function adfoin_get_neoncrm_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'neoncrm' ) );
}

add_action( 'wp_ajax_adfoin_save_neoncrm_credentials', 'adfoin_save_neoncrm_credentials' );
function adfoin_save_neoncrm_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'neoncrm' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'neoncrm', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_neoncrm_fields', 'adfoin_get_neoncrm_fields' );
function adfoin_get_neoncrm_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_account';

    if ( $task === 'create_donation' ) {
        $fields = array(
            array( 'key' => 'donorEmail', 'value' => 'Donor Email',  'description' => 'Used to look up the account' ),
            array( 'key' => 'amount',     'value' => 'Amount',       'description' => '' ),
            array( 'key' => 'date',       'value' => 'Donation Date','description' => 'YYYY-MM-DD' ),
            array( 'key' => 'campaign',   'value' => 'Campaign',     'description' => '' ),
            array( 'key' => 'fund',       'value' => 'Fund',         'description' => '' ),
            array( 'key' => 'source',     'value' => 'Source',       'description' => '' ),
            array( 'key' => 'tribute',    'value' => 'Tribute',      'description' => '' ),
            array( 'key' => 'note',       'value' => 'Note',         'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'type',      'value' => 'Account Type', 'description' => 'Individual / Company' ),
            array( 'key' => 'company',   'value' => 'Company Name', 'description' => '' ),
            array( 'key' => 'street',    'value' => 'Street',     'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'source',    'value' => 'Source',     'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_neoncrm_credentials_list() {
    foreach ( adfoin_read_credentials( 'neoncrm' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_neoncrm_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'neoncrm', $cred_id );
    $org_id      = isset( $credentials['orgId'] )  ? $credentials['orgId']  : '';
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $org_id || ! $api_key ) return;

    $url  = 'https://api.neoncrm.com/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $org_id . ':' . $api_key ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_neoncrm_job_queue', 'adfoin_neoncrm_job_queue', 10, 1 );
function adfoin_neoncrm_job_queue( $data ) {
    adfoin_neoncrm_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_neoncrm_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $record['task'] === 'create_account' ) {
        $type = ! empty( $fields['type'] ) ? strtolower( $fields['type'] ) : 'individual';
        $body = array(
            'individualAccount' => $type === 'individual' ? array(
                'primaryContact' => array(
                    'firstName' => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
                    'lastName'  => isset( $fields['lastName'] )  ? $fields['lastName']  : '',
                    'email1'    => isset( $fields['email'] )     ? $fields['email']     : '',
                ),
            ) : null,
            'companyAccount' => $type === 'company' ? array(
                'primaryContact' => array(
                    'firstName' => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
                    'lastName'  => isset( $fields['lastName'] )  ? $fields['lastName']  : '',
                    'email1'    => isset( $fields['email'] )     ? $fields['email']     : '',
                ),
                'name' => isset( $fields['company'] ) ? $fields['company'] : '',
            ) : null,
        );
        $body = array_filter( $body );

        $contact = ( $type === 'company' ) ? $body['companyAccount']['primaryContact'] : $body['individualAccount']['primaryContact'];
        if ( ! empty( $fields['phone'] ) ) $contact['phone1'] = $fields['phone'];

        $addr = array();
        foreach ( array( 'street' => 'addressLine1', 'city' => 'city', 'state' => 'stateProvince', 'zip' => 'zipCode', 'country' => 'country' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
        }
        if ( $addr ) {
            $addr['isPrimaryAddress'] = true;
            $contact['addresses'] = array( $addr );
        }
        if ( $type === 'company' ) $body['companyAccount']['primaryContact'] = $contact;
        else                       $body['individualAccount']['primaryContact'] = $contact;

        if ( ! empty( $fields['source'] ) ) $body['source'] = $fields['source'];
        if ( ! empty( $fields['note'] ) )   $body['note']   = $fields['note'];

        adfoin_neoncrm_request( 'accounts', 'POST', $body, $record, $cred_id );
    } elseif ( $record['task'] === 'create_donation' ) {
        $body = array(
            'amount' => isset( $fields['amount'] ) ? floatval( $fields['amount'] ) : 0,
            'date'   => isset( $fields['date'] )   ? $fields['date']   : '',
            'donorEmail' => isset( $fields['donorEmail'] ) ? $fields['donorEmail'] : '',
        );
        foreach ( array( 'campaign' => 'campaign', 'fund' => 'fund', 'source' => 'source', 'tribute' => 'tribute', 'note' => 'note' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = array( 'name' => $fields[ $local ] );
        }
        adfoin_neoncrm_request( 'donations', 'POST', $body, $record, $cred_id );
    }
}
