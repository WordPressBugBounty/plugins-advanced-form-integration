<?php

add_filter( 'adfoin_action_providers', 'adfoin_practicepanther_actions', 10, 1 );
function adfoin_practicepanther_actions( $actions ) {
    $actions['practicepanther'] = array(
        'title' => 'PracticePanther',
        'tasks' => array(
            'create_contact' => 'Create Contact',
            'create_matter'  => 'Create Matter',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_practicepanther_settings_tab', 10, 1 );
function adfoin_practicepanther_settings_tab( $providers ) {
    $providers['practicepanther'] = 'PracticePanther';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_practicepanther_settings_view', 10, 1 );
function adfoin_practicepanther_settings_view( $current_tab ) {
    if ( $current_tab !== 'practicepanther' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'practicepanther',
        'fields'   => array(
            array( 'key' => 'accessToken', 'label' => __( 'OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'Register an OAuth app in PracticePanther, complete the auth flow, and paste the resulting access token.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'PracticePanther', 'advanced-form-integration' ), 'practicepanther', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_practicepanther_action_fields' );
function adfoin_practicepanther_action_fields() {
    ?>
    <script type="text/template" id="practicepanther-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_matter'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_matter'">
                <td scope="row-title"><label><?php esc_attr_e( 'PracticePanther Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_practicepanther_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'PracticePanther [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_practicepanther_credentials', 'adfoin_get_practicepanther_credentials' );
function adfoin_get_practicepanther_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'practicepanther' ) );
}

add_action( 'wp_ajax_adfoin_save_practicepanther_credentials', 'adfoin_save_practicepanther_credentials' );
function adfoin_save_practicepanther_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'practicepanther' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'practicepanther', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_practicepanther_fields', 'adfoin_get_practicepanther_fields' );
function adfoin_get_practicepanther_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $task = isset( $_POST['task'] ) ? sanitize_text_field( $_POST['task'] ) : 'create_contact';

    if ( $task === 'create_matter' ) {
        $fields = array(
            array( 'key' => 'name',          'value' => 'Matter Name',  'description' => '' ),
            array( 'key' => 'number',        'value' => 'Matter Number','description' => '' ),
            array( 'key' => 'client_email',  'value' => 'Client Email', 'description' => 'Used to find the linked contact' ),
            array( 'key' => 'practiceArea',  'value' => 'Practice Area','description' => '' ),
            array( 'key' => 'status',        'value' => 'Status',       'description' => 'Pending / Open / Closed' ),
            array( 'key' => 'openDate',      'value' => 'Open Date',    'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'rate',          'value' => 'Default Rate', 'description' => '' ),
            array( 'key' => 'description',   'value' => 'Description',  'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phoneMobile','value' => 'Mobile Phone', 'description' => '' ),
            array( 'key' => 'phoneHome', 'value' => 'Home Phone', 'description' => '' ),
            array( 'key' => 'phoneWork', 'value' => 'Work Phone', 'description' => '' ),
            array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
            array( 'key' => 'jobTitle',  'value' => 'Job Title',  'description' => '' ),
            array( 'key' => 'address1',  'value' => 'Address Line 1', 'description' => '' ),
            array( 'key' => 'address2',  'value' => 'Address Line 2', 'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'website',   'value' => 'Website',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_practicepanther_credentials_list() {
    foreach ( adfoin_read_credentials( 'practicepanther' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_practicepanther_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'practicepanther', $cred_id );
    $token       = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';

    if ( ! $token ) return;

    $url  = 'https://app.practicepanther.com/api/v2/' . ltrim( $endpoint, '/' );
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

function adfoin_practicepanther_create_contact( $fields, $record, $cred_id ) {
    $body = array(
        'first_name'   => isset( $fields['firstName'] )  ? $fields['firstName']  : '',
        'middle_name'  => isset( $fields['middleName'] ) ? $fields['middleName'] : '',
        'last_name'    => isset( $fields['lastName'] )   ? $fields['lastName']   : '',
        'email'        => isset( $fields['email'] )      ? $fields['email']      : '',
        'phone_mobile' => isset( $fields['phoneMobile'] )? $fields['phoneMobile']: '',
        'phone_home'   => isset( $fields['phoneHome'] )  ? $fields['phoneHome']  : '',
        'phone_work'   => isset( $fields['phoneWork'] )  ? $fields['phoneWork']  : '',
        'company'      => isset( $fields['company'] )    ? $fields['company']    : '',
        'job_title'    => isset( $fields['jobTitle'] )   ? $fields['jobTitle']   : '',
        'website'      => isset( $fields['website'] )    ? $fields['website']    : '',
        'notes'        => isset( $fields['note'] )       ? $fields['note']       : '',
    );
    $addr = array();
    foreach ( array( 'address1' => 'address1', 'address2' => 'address2', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $body['billing_address'] = $addr;
    return adfoin_practicepanther_request( 'contacts', 'POST', array_filter( $body ), $record, $cred_id );
}

function adfoin_practicepanther_create_matter( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'name' => 'name', 'number' => 'number', 'practiceArea' => 'practice_area', 'status' => 'status', 'openDate' => 'open_date', 'rate' => 'default_rate', 'description' => 'description' ) as $local => $remote ) {
        if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $body[ $remote ] = $fields[ $local ];
    }
    return adfoin_practicepanther_request( 'matters', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_practicepanther_job_queue', 'adfoin_practicepanther_job_queue', 10, 1 );
function adfoin_practicepanther_job_queue( $data ) {
    adfoin_practicepanther_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_practicepanther_send_data( $record, $posted_data ) {
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
        adfoin_practicepanther_create_contact( $fields, $record, $cred_id );
    } elseif ( $record['task'] === 'create_matter' ) {
        adfoin_practicepanther_create_matter( $fields, $record, $cred_id );
    }
}
