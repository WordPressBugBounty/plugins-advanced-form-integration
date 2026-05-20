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
            array( 'key' => 'apiKey',  'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'siteUuid','label' => __( 'Site UUID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In your Real Geeks admin, open Settings > Integrations. Generate a Lead API token and copy your Site UUID.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_realgeeks_action_fields' );
function adfoin_realgeeks_action_fields() {
    ?>
    <script type="text/template" id="realgeeks-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td scope="row"><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
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
            <?php adfoin_pro_feature_notice( 'create_lead', 'Real Geeks [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_realgeeks_credentials', 'adfoin_get_realgeeks_credentials' );
function adfoin_get_realgeeks_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'realgeeks' ) );
}

add_action( 'wp_ajax_adfoin_save_realgeeks_credentials', 'adfoin_save_realgeeks_credentials' );
function adfoin_save_realgeeks_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'realgeeks' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'realgeeks', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_realgeeks_fields', 'adfoin_get_realgeeks_fields' );
function adfoin_get_realgeeks_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name',   'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',    'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',        'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',        'description' => '' ),
        array( 'key' => 'source',      'value' => 'Source',       'description' => 'e.g. WordPress Form' ),
        array( 'key' => 'pageUrl',     'value' => 'Page URL',     'description' => 'Originating page' ),
        array( 'key' => 'propertyUrl', 'value' => 'Property URL', 'description' => '' ),
        array( 'key' => 'propertyMls', 'value' => 'MLS #',        'description' => '' ),
        array( 'key' => 'priceMin',    'value' => 'Price Min',    'description' => '' ),
        array( 'key' => 'priceMax',    'value' => 'Price Max',    'description' => '' ),
        array( 'key' => 'beds',        'value' => 'Beds',         'description' => '' ),
        array( 'key' => 'baths',       'value' => 'Baths',        'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',         'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',        'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',          'description' => '' ),
        array( 'key' => 'message',     'value' => 'Message',      'description' => '' ),
        array( 'key' => 'assignedAgent','value' => 'Assigned Agent Email', 'description' => '' ),
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
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $site_uuid   = isset( $credentials['siteUuid'] ) ? $credentials['siteUuid'] : '';

    if ( ! $api_key || ! $site_uuid ) return;

    $base_url = 'https://api.realgeeks.com/leads/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Token ' . $api_key,
            'X-Site-UUID'   => $site_uuid,
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

function adfoin_realgeeks_create_lead( $fields, $record, $cred_id ) {
    $lead = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'source', 'pageUrl', 'propertyUrl', 'propertyMls', 'message', 'assignedAgent' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $lead[ $k ] = $fields[ $k ];
    }
    $criteria = array();
    foreach ( array( 'priceMin', 'priceMax', 'beds', 'baths', 'city', 'state', 'zip' ) as $k ) {
        if ( isset( $fields[ $k ] ) && $fields[ $k ] !== '' ) $criteria[ $k ] = $fields[ $k ];
    }
    if ( $criteria ) $lead['searchCriteria'] = $criteria;

    return adfoin_realgeeks_request( '', 'POST', $lead, $record, $cred_id );
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
