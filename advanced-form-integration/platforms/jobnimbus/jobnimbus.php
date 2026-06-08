<?php

add_filter( 'adfoin_action_providers', 'adfoin_jobnimbus_actions', 10, 1 );
function adfoin_jobnimbus_actions( $actions ) {
    $actions['jobnimbus'] = array(
        'title' => 'JobNimbus',
        'tasks' => array(
            'create_contact' => 'Create Contact',
            'create_job'     => 'Create Job',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_jobnimbus_settings_tab', 10, 1 );
function adfoin_jobnimbus_settings_tab( $providers ) {
    $providers['jobnimbus'] = 'JobNimbus';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_jobnimbus_settings_view', 10, 1 );
function adfoin_jobnimbus_settings_view( $current_tab ) {
    if ( $current_tab !== 'jobnimbus' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'jobnimbus',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In JobNimbus, go to Settings > API. Generate an API key (assigned to a user) and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'JobNimbus', 'advanced-form-integration' ), 'jobnimbus', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_jobnimbus_action_fields' );
function adfoin_jobnimbus_action_fields() {
    ?>
    <script type="text/template" id="jobnimbus-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_job'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_job'">
                <td scope="row-title"><label><?php esc_attr_e( 'JobNimbus Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_jobnimbus_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'JobNimbus [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_jobnimbus_credentials', 'adfoin_get_jobnimbus_credentials' );
function adfoin_get_jobnimbus_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'jobnimbus' ) );
}

add_action( 'wp_ajax_adfoin_save_jobnimbus_credentials', 'adfoin_save_jobnimbus_credentials' );
function adfoin_save_jobnimbus_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'jobnimbus' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'jobnimbus', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_jobnimbus_fields', 'adfoin_get_jobnimbus_fields' );
function adfoin_get_jobnimbus_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( $task === 'create_job' ) {
        $fields = array(
            array( 'key' => 'name',         'value' => 'Job Name',     'description' => '' ),
            array( 'key' => 'contactEmail', 'value' => 'Contact Email','description' => 'Link the job to this contact' ),
            array( 'key' => 'description',  'value' => 'Description',  'description' => '' ),
            array( 'key' => 'status',       'value' => 'Status',       'description' => '' ),
            array( 'key' => 'recordType',   'value' => 'Record Type',  'description' => '' ),
            array( 'key' => 'salesRep',     'value' => 'Sales Rep',    'description' => '' ),
            array( 'key' => 'address',      'value' => 'Street',       'description' => '' ),
            array( 'key' => 'city',         'value' => 'City',         'description' => '' ),
            array( 'key' => 'state',        'value' => 'State',        'description' => '' ),
            array( 'key' => 'zip',          'value' => 'Zip',          'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'mobilePhone','value' => 'Mobile Phone','description' => '' ),
            array( 'key' => 'homePhone', 'value' => 'Home Phone', 'description' => '' ),
            array( 'key' => 'workPhone', 'value' => 'Work Phone', 'description' => '' ),
            array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
            array( 'key' => 'address',   'value' => 'Street',     'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'status',    'value' => 'Status',     'description' => '' ),
            array( 'key' => 'recordType','value' => 'Record Type','description' => 'lead / customer / vendor' ),
            array( 'key' => 'leadSource','value' => 'Lead Source','description' => '' ),
            array( 'key' => 'note',      'value' => 'Description / Note', 'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_jobnimbus_credentials_list() {
    foreach ( adfoin_read_credentials( 'jobnimbus' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_jobnimbus_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'jobnimbus', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) return;

    $url  = 'https://app.jobnimbus.com/api1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_jobnimbus_job_queue', 'adfoin_jobnimbus_job_queue', 10, 1 );
function adfoin_jobnimbus_job_queue( $data ) {
    adfoin_jobnimbus_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_jobnimbus_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $record['task'] === 'create_contact' ) {
        $body = array();
        foreach ( array( 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'mobilePhone' => 'mobile_phone', 'homePhone' => 'home_phone', 'workPhone' => 'work_phone', 'company' => 'company', 'address' => 'address_line1', 'city' => 'city', 'state' => 'state_text', 'zip' => 'zip', 'country' => 'country_name', 'status' => 'status_name', 'recordType' => 'record_type_name', 'leadSource' => 'source_name', 'note' => 'description' ) as $local => $remote ) {
            if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $body[ $remote ] = $fields[ $local ];
        }
        adfoin_jobnimbus_request( 'contacts', 'POST', $body, $record, $cred_id );
    } elseif ( $record['task'] === 'create_job' ) {
        $body = array();
        foreach ( array( 'name' => 'name', 'description' => 'description', 'status' => 'status_name', 'recordType' => 'record_type_name', 'salesRep' => 'sales_rep_name', 'address' => 'address_line1', 'city' => 'city', 'state' => 'state_text', 'zip' => 'zip' ) as $local => $remote ) {
            if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $body[ $remote ] = $fields[ $local ];
        }
        if ( ! empty( $fields['contactEmail'] ) ) $body['related'] = array( array( 'type' => 'contact', 'email' => $fields['contactEmail'] ) );
        adfoin_jobnimbus_request( 'jobs', 'POST', $body, $record, $cred_id );
    }
}
