<?php

add_filter( 'adfoin_action_providers', 'adfoin_realgeeks_actions', 10, 1 );
function adfoin_realgeeks_actions( $actions ) {
    $actions['realgeeks'] = array(
        'title' => 'Real Geeks',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_realgeeks_settings_tab', 10, 1 );
function adfoin_realgeeks_settings_tab( $providers ) {
    $providers['realgeeks'] = 'Real Geeks';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_realgeeks_settings_view', 10, 1 );
function adfoin_realgeeks_settings_view( $current_tab ) {
    if ( $current_tab !== 'realgeeks' ) return;
    $title = __( 'Real Geeks', 'advanced-form-integration' );
    $key   = 'realgeeks';
    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'siteUuid', 'label' => __( 'Site UUID', 'advanced-form-integration' ) ),
            array( 'key' => 'username', 'label' => __( 'Product Identifier', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'secret',   'label' => __( 'Secret Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'Real Geeks issues Incoming Leads API access on request: contact their support to get your Site UUID plus a Product Identifier / Secret Key pair for HTTP Basic Auth.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_realgeeks_action_fields' );
function adfoin_realgeeks_action_fields() {
    ?>
    <script type="text/template" id="realgeeks-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td scope="row"><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label for="tablecell"><?php esc_attr_e( 'Real Geeks Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_realgeeks_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Real Geeks [PRO]', 'tags and extra lead fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_realgeeks_credentials', 'adfoin_get_realgeeks_credentials' );
function adfoin_get_realgeeks_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'realgeeks' ) );
}

add_action( 'wp_ajax_adfoin_save_realgeeks_credentials', 'adfoin_save_realgeeks_credentials' );
function adfoin_save_realgeeks_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'realgeeks' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'realgeeks', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_realgeeks_fields', 'adfoin_get_realgeeks_fields' );
function adfoin_get_realgeeks_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'source',   'value' => 'Source',   'description' => 'e.g. WordPress Form' ),
        array( 'key' => 'role',     'value' => 'Role',     'description' => 'e.g. Buyer, Seller' ),
        array( 'key' => 'address',  'value' => 'Street Address', 'description' => '' ),
        array( 'key' => 'city',     'value' => 'City',     'description' => '' ),
        array( 'key' => 'state',    'value' => 'State',    'description' => '' ),
        array( 'key' => 'zip',      'value' => 'Zip',      'description' => '' ),
        array( 'key' => 'notes',    'value' => 'Notes',    'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_realgeeks_credentials_list() {
    foreach ( adfoin_read_credentials( 'realgeeks' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_realgeeks_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'realgeeks', $cred_id );
    $username    = isset( $credentials['username'] ) ? $credentials['username'] : '';
    $secret      = isset( $credentials['secret'] )   ? $credentials['secret']   : '';
    $site_uuid   = isset( $credentials['siteUuid'] ) ? $credentials['siteUuid'] : '';

    if ( ! $username || ! $secret || ! $site_uuid ) return;

    // Incoming Leads API — HTTP Basic Auth (username/secret issued by Real
    // Geeks), site UUID is part of the URL path, not a header.
    // https://developers.realgeeks.com/incoming-leads-api/
    $base_url = 'https://receivers.leadrouter.realgeeks.com/rest/sites/' . rawurlencode( $site_uuid ) . '/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $secret ),
            'Content-Type'  => 'application/json',
        ),
    );

    if ( $method === 'POST' || $method === 'PUT' ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * Build a Create Lead request body matching Real Geeks' documented
 * Incoming Leads API schema (snake_case, flat — no nested search criteria).
 * @link https://developers.realgeeks.com/incoming-leads-api/
 */
function adfoin_realgeeks_build_lead_payload( $fields ) {
    $lead = array();
    $map  = array(
        'firstName' => 'first_name',
        'lastName'  => 'last_name',
        'email'     => 'email',
        'phone'     => 'phone',
        'source'    => 'source',
        'role'      => 'role',
        'address'   => 'street_address',
        'city'      => 'city',
        'state'     => 'state',
        'zip'       => 'zip',
        'notes'     => 'notes',
    );
    foreach ( $map as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $lead[ $remote ] = $fields[ $local ];
    }
    return $lead;
}

function adfoin_realgeeks_create_lead( $fields, $record, $cred_id ) {
    $lead = adfoin_realgeeks_build_lead_payload( $fields );

    return adfoin_realgeeks_request( 'leads', 'POST', $lead, $record, $cred_id );
}

add_action( 'adfoin_realgeeks_job_queue', 'adfoin_realgeeks_job_queue', 10, 1 );
function adfoin_realgeeks_job_queue( $data ) {
    adfoin_realgeeks_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_realgeeks_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );
    $task    = $record['task'];

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $task === 'create_lead' ) {
        adfoin_realgeeks_create_lead( $fields, $record, $cred_id );
    }
}
