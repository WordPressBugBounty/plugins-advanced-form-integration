<?php

add_filter( 'adfoin_action_providers', 'adfoin_filevine_actions', 10, 1 );
function adfoin_filevine_actions( $actions ) {
    $actions['filevine'] = array(
        'title' => 'Filevine',
        'tasks' => array(
            'create_contact' => 'Create Contact',
            'create_project' => 'Create Project (Case)',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_filevine_settings_tab', 10, 1 );
function adfoin_filevine_settings_tab( $providers ) {
    $providers['filevine'] = 'Filevine';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_filevine_settings_view', 10, 1 );
function adfoin_filevine_settings_view( $current_tab ) {
    if ( $current_tab !== 'filevine' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'filevine',
        'fields'   => array(
            array( 'key' => 'accessToken', 'label' => __( 'OAuth Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'orgId',       'label' => __( 'Org ID', 'advanced-form-integration' ) ),
            array( 'key' => 'userId',      'label' => __( 'User ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In the Filevine Developer Portal, create an API integration. Complete the OAuth flow and paste the access token. Org ID and User ID identify the integration user context.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Filevine', 'advanced-form-integration' ), 'filevine', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_filevine_action_fields' );
function adfoin_filevine_action_fields() {
    ?>
    <script type="text/template" id="filevine-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_project'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_project'">
                <td scope="row-title"><label><?php esc_attr_e( 'Filevine Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_filevine_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Filevine [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_filevine_credentials', 'adfoin_get_filevine_credentials' );
function adfoin_get_filevine_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'filevine' ) );
}

add_action( 'wp_ajax_adfoin_save_filevine_credentials', 'adfoin_save_filevine_credentials' );
function adfoin_save_filevine_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'filevine' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'filevine', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_filevine_fields', 'adfoin_get_filevine_fields' );
function adfoin_get_filevine_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( $task === 'create_project' ) {
        $fields = array(
            array( 'key' => 'projectName', 'value' => 'Project Name', 'description' => '' ),
            array( 'key' => 'projectTypeId','value' => 'Project Type ID', 'description' => '' ),
            array( 'key' => 'clientEmail','value' => 'Client Email',  'description' => 'Used to find linked client contact' ),
            array( 'key' => 'phaseName',  'value' => 'Phase Name',    'description' => 'Initial phase' ),
            array( 'key' => 'description','value' => 'Description',   'description' => '' ),
            array( 'key' => 'incidentDate','value' => 'Incident Date','description' => 'YYYY-MM-DD' ),
            array( 'key' => 'note',       'value' => 'Note',          'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'middleName','value' => 'Middle Name','description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'mobile',    'value' => 'Mobile',     'description' => '' ),
            array( 'key' => 'fullName',  'value' => 'Full Name (org contact)', 'description' => '' ),
            array( 'key' => 'personType','value' => 'Person Type','description' => 'individual / organization' ),
            array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_filevine_credentials_list() {
    foreach ( adfoin_read_credentials( 'filevine' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_filevine_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'filevine', $cred_id );
    $token       = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
    $org_id      = isset( $credentials['orgId'] )       ? $credentials['orgId']       : '';
    $user_id     = isset( $credentials['userId'] )      ? $credentials['userId']      : '';

    if ( ! $token ) return;

    $url  = 'https://api.filevineapp.com/fv-app/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'x-fv-orgid'    => $org_id,
            'x-fv-userid'   => $user_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

function adfoin_filevine_create_contact( $fields, $record, $cred_id ) {
    $person = array();
    foreach ( array( 'firstName' => 'firstName', 'middleName' => 'middleName', 'lastName' => 'lastName', 'fullName' => 'fullName', 'personType' => 'personType' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $person[ $remote ] = $fields[ $local ];
    }
    $emails = array();
    if ( ! empty( $fields['email'] ) ) $emails[] = array( 'email' => $fields['email'], 'isPrimary' => true );
    if ( $emails ) $person['emails'] = $emails;

    $phones = array();
    if ( ! empty( $fields['phone'] ) )  $phones[] = array( 'number' => $fields['phone'],  'type' => 'work', 'isPrimary' => true );
    if ( ! empty( $fields['mobile'] ) ) $phones[] = array( 'number' => $fields['mobile'], 'type' => 'mobile' );
    if ( $phones ) $person['phones'] = $phones;

    $addr = array();
    foreach ( array( 'address' => 'street1', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'country' => 'country' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $addr[ $remote ] = $fields[ $local ];
    }
    if ( $addr ) $person['addresses'] = array( $addr );

    if ( ! empty( $fields['note'] ) ) $person['notes'] = $fields['note'];

    return adfoin_filevine_request( 'core/contacts', 'POST', $person, $record, $cred_id );
}

function adfoin_filevine_create_project( $fields, $record, $cred_id ) {
    $body = array();
    foreach ( array( 'projectName' => 'projectName', 'projectTypeId' => 'projectTypeId', 'phaseName' => 'phaseName', 'description' => 'description', 'incidentDate' => 'incidentDate', 'note' => 'notes', 'clientEmail' => 'clientEmail' ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    return adfoin_filevine_request( 'core/projects', 'POST', $body, $record, $cred_id );
}

add_action( 'adfoin_filevine_job_queue', 'adfoin_filevine_job_queue', 10, 1 );
function adfoin_filevine_job_queue( $data ) {
    adfoin_filevine_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_filevine_send_data( $record, $posted_data ) {
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
        adfoin_filevine_create_contact( $fields, $record, $cred_id );
    } elseif ( $record['task'] === 'create_project' ) {
        adfoin_filevine_create_project( $fields, $record, $cred_id );
    }
}
