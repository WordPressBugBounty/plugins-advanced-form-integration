<?php

add_filter( 'adfoin_action_providers', 'adfoin_lofty_actions', 10, 1 );

function adfoin_lofty_actions( $actions ) {
    $actions['lofty'] = array(
        'title' => 'Lofty',
        'tasks' => array(
            'create_lead' => 'Create Lead'
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_lofty_settings_tab', 10, 1 );

function adfoin_lofty_settings_tab( $providers ) {
    $providers['lofty'] = 'Lofty';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_lofty_settings_view', 10, 1 );

function adfoin_lofty_settings_view( $current_tab ) {
    if ( $current_tab !== 'lofty' ) return;

    $title     = __( 'Lofty (formerly Chime)', 'advanced-form-integration' );
    $key       = 'lofty';
    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Lofty, open Settings > Integrations > API. Generate an API key and paste it above.', 'advanced-form-integration' );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_lofty_action_fields' );

function adfoin_lofty_action_fields() {
    ?>
    <script type="text/template" id="lofty-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title">
                    <label for="tablecell"><?php esc_attr_e( 'Lofty Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_lofty_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Lofty [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_lofty_credentials', 'adfoin_get_lofty_credentials' );
function adfoin_get_lofty_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'lofty' ) );
}

add_action( 'wp_ajax_adfoin_save_lofty_credentials', 'adfoin_save_lofty_credentials' );
function adfoin_save_lofty_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'lofty' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'lofty', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_lofty_fields', 'adfoin_get_lofty_fields' );
function adfoin_get_lofty_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name',   'description' => '' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',    'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',        'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',        'description' => '' ),
        array( 'key' => 'source',     'value' => 'Source',       'description' => '' ),
        array( 'key' => 'sourceUrl',  'value' => 'Source URL',   'description' => '' ),
        array( 'key' => 'stage',      'value' => 'Stage',        'description' => '' ),
        array( 'key' => 'leadType',   'value' => 'Lead Type',    'description' => 'buyer, seller, renter' ),
        array( 'key' => 'priceMin',   'value' => 'Price Min',    'description' => '' ),
        array( 'key' => 'priceMax',   'value' => 'Price Max',    'description' => '' ),
        array( 'key' => 'beds',       'value' => 'Beds',         'description' => '' ),
        array( 'key' => 'baths',      'value' => 'Baths',        'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',         'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',        'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',          'description' => '' ),
        array( 'key' => 'address',    'value' => 'Address',      'description' => '' ),
        array( 'key' => 'note',       'value' => 'Note',         'description' => '' ),
        array( 'key' => 'assignedTo', 'value' => 'Assigned Agent Email', 'description' => '' ),
    );

    wp_send_json_success( $fields );
}

function adfoin_lofty_credentials_list() {
    foreach ( adfoin_read_credentials( 'lofty' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_lofty_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'lofty', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) return;

    $base_url = 'https://api.lofty.com/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
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

function adfoin_lofty_create_lead( $fields, $record, $cred_id ) {
    $lead = array();
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'source', 'sourceUrl', 'stage', 'leadType', 'priceMin', 'priceMax', 'beds', 'baths', 'city', 'state', 'zip', 'address', 'note', 'assignedTo' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $lead[ $k ] = $fields[ $k ];
    }
    return adfoin_lofty_request( 'leads', 'POST', $lead, $record, $cred_id );
}

add_action( 'adfoin_lofty_job_queue', 'adfoin_lofty_job_queue', 10, 1 );
function adfoin_lofty_job_queue( $data ) {
    adfoin_lofty_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_lofty_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );
    $task    = $record['task'];

    $fields = array();
    foreach ( $data as $key => $value ) {
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $key ] = $parsed;
    }

    if ( $task === 'create_lead' ) {
        adfoin_lofty_create_lead( $fields, $record, $cred_id );
    }
}
