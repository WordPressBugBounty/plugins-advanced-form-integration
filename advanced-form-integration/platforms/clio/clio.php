<?php

add_filter( 'adfoin_action_providers', 'adfoin_clio_actions', 10, 1 );
function adfoin_clio_actions( $actions ) {
    $actions['clio'] = array(
        'title' => 'Clio',
        'tasks' => array(
            'create_contact' => 'Create Contact',
            'create_matter'  => 'Create Matter',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_clio_settings_tab', 10, 1 );
function adfoin_clio_settings_tab( $providers ) {
    $providers['clio'] = 'Clio';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_clio_settings_view', 10, 1 );
function adfoin_clio_settings_view( $current_tab ) {
    if ( $current_tab !== 'clio' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'clio',
        'fields'   => array(
            array( 'key' => 'accessToken', 'label' => __( 'OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'region',      'label' => __( 'Region (us / eu / au / ca)', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Register an OAuth app in Clio Developers, complete the auth flow, then paste the access token and your region (us, eu, au, or ca).', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Clio', 'advanced-form-integration' ), 'clio', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_clio_action_fields' );
function adfoin_clio_action_fields() {
    ?>
    <script type="text/template" id="clio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_matter'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_matter'">
                <td scope="row-title"><label><?php esc_attr_e( 'Clio Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_clio_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Clio [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_clio_credentials', 'adfoin_get_clio_credentials' );
function adfoin_get_clio_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'clio' ) );
}

add_action( 'wp_ajax_adfoin_save_clio_credentials', 'adfoin_save_clio_credentials' );
function adfoin_save_clio_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'clio' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'clio', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_clio_fields', 'adfoin_get_clio_fields' );
function adfoin_get_clio_fields() {
    if ( ! adfoin_verify_nonce() ) return;

    $task = isset( $_POST['task'] ) ? sanitize_text_field( $_POST['task'] ) : 'create_contact';

    if ( $task === 'create_matter' ) {
        $fields = array(
            array( 'key' => 'description', 'value' => 'Description',  'description' => '' ),
            array( 'key' => 'client_email','value' => 'Client Email',  'description' => 'Used to look up the client contact' ),
            array( 'key' => 'practiceArea','value' => 'Practice Area', 'description' => '' ),
            array( 'key' => 'responsibleAttorney','value' => 'Responsible Attorney Email', 'description' => '' ),
            array( 'key' => 'status',      'value' => 'Status',        'description' => 'Open / Pending / Closed' ),
            array( 'key' => 'openDate',    'value' => 'Open Date',     'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'displayNumber','value' => 'Matter Number','description' => '' ),
            array( 'key' => 'note',        'value' => 'Note',          'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'emailType', 'value' => 'Email Type', 'description' => 'Work / Home / Other' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'phoneType', 'value' => 'Phone Type', 'description' => 'Work / Home / Mobile' ),
            array( 'key' => 'type',      'value' => 'Type',       'description' => 'Person or Company' ),
            array( 'key' => 'company',   'value' => 'Company',    'description' => '' ),
            array( 'key' => 'jobTitle',  'value' => 'Job Title',  'description' => '' ),
            array( 'key' => 'street',    'value' => 'Street',     'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'province',  'value' => 'State / Province', 'description' => '' ),
            array( 'key' => 'postal',    'value' => 'Postal / Zip', 'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'website',   'value' => 'Website',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_clio_credentials_list() {
    foreach ( adfoin_read_credentials( 'clio' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_clio_base_url( $region ) {
    $region = strtolower( trim( $region ) );
    $map    = array(
        'eu' => 'https://eu.app.clio.com',
        'au' => 'https://au.app.clio.com',
        'ca' => 'https://ca.app.clio.com',
    );
    return isset( $map[ $region ] ) ? $map[ $region ] : 'https://app.clio.com';
}

function adfoin_clio_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'clio', $cred_id );
    $token       = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
    $region      = isset( $credentials['region'] )      ? $credentials['region']      : '';

    if ( ! $token ) return;

    $url  = adfoin_clio_base_url( $region ) . '/api/v4/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' || $method === 'PATCH' ) {
        $args['body'] = wp_json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_clio_create_contact( $fields, $record, $cred_id ) {
    $contact = array(
        'type'        => ! empty( $fields['type'] ) ? $fields['type'] : 'Person',
        'first_name'  => isset( $fields['firstName'] ) ? $fields['firstName'] : '',
        'middle_name' => isset( $fields['middleName'] ) ? $fields['middleName'] : '',
        'last_name'   => isset( $fields['lastName'] ) ? $fields['lastName'] : '',
    );
    if ( ! empty( $fields['company'] ) )  $contact['name'] = $fields['company'];
    if ( ! empty( $fields['jobTitle'] ) ) $contact['title'] = $fields['jobTitle'];
    if ( ! empty( $fields['website'] ) ) {
        $contact['web_sites'] = array( array( 'address' => $fields['website'], 'name' => 'Work' ) );
    }
    if ( ! empty( $fields['email'] ) ) {
        $contact['email_addresses'] = array( array(
            'address' => $fields['email'],
            'name'    => ! empty( $fields['emailType'] ) ? $fields['emailType'] : 'Work',
            'default_email' => true,
        ) );
    }
    if ( ! empty( $fields['phone'] ) ) {
        $contact['phone_numbers'] = array( array(
            'number' => $fields['phone'],
            'name'   => ! empty( $fields['phoneType'] ) ? $fields['phoneType'] : 'Work',
            'default_number' => true,
        ) );
    }
    $address = array();
    foreach ( array( 'street' => 'street', 'city' => 'city', 'province' => 'province', 'postal' => 'postal_code', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $address[ $remote ] = $fields[ $local ];
    }
    if ( $address ) {
        $address['name'] = 'Work';
        $contact['addresses'] = array( $address );
    }
    return adfoin_clio_request( 'contacts.json', 'POST', array( 'data' => array_filter( $contact ) ), $record, $cred_id );
}

function adfoin_clio_create_matter( $fields, $record, $cred_id ) {
    $matter = array();
    if ( ! empty( $fields['description'] ) )    $matter['description']    = $fields['description'];
    if ( ! empty( $fields['displayNumber'] ) )  $matter['display_number'] = $fields['displayNumber'];
    if ( ! empty( $fields['status'] ) )         $matter['status']         = strtolower( $fields['status'] );
    if ( ! empty( $fields['openDate'] ) )       $matter['open_date']      = $fields['openDate'];
    if ( ! empty( $fields['practiceArea'] ) )   $matter['practice_area']  = array( 'name' => $fields['practiceArea'] );
    if ( ! empty( $fields['note'] ) )           $matter['notes']          = $fields['note'];

    return adfoin_clio_request( 'matters.json', 'POST', array( 'data' => $matter ), $record, $cred_id );
}

add_action( 'adfoin_clio_job_queue', 'adfoin_clio_job_queue', 10, 1 );
function adfoin_clio_job_queue( $data ) {
    adfoin_clio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_clio_send_data( $record, $posted_data ) {
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
        adfoin_clio_create_contact( $fields, $record, $cred_id );
    } elseif ( $record['task'] === 'create_matter' ) {
        adfoin_clio_create_matter( $fields, $record, $cred_id );
    }
}
