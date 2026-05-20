<?php

add_filter( 'adfoin_action_providers', 'adfoin_buildertrend_actions', 10, 1 );
function adfoin_buildertrend_actions( $actions ) {
    $actions['buildertrend'] = array(
        'title' => 'Buildertrend',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_buildertrend_settings_tab', 10, 1 );
function adfoin_buildertrend_settings_tab( $providers ) {
    $providers['buildertrend'] = 'Buildertrend';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_buildertrend_settings_view', 10, 1 );
function adfoin_buildertrend_settings_view( $current_tab ) {
    if ( $current_tab !== 'buildertrend' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'buildertrend',
        'fields'   => array(
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'builderId', 'label' => __( 'Builder ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'In Buildertrend, contact your account manager for API access. They will issue an API key and a Builder ID for your company.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Buildertrend', 'advanced-form-integration' ), 'buildertrend', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_buildertrend_action_fields' );
function adfoin_buildertrend_action_fields() {
    ?>
    <script type="text/template" id="buildertrend-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Buildertrend Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_buildertrend_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Buildertrend [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_buildertrend_credentials', 'adfoin_get_buildertrend_credentials' );
function adfoin_get_buildertrend_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'buildertrend' ) );
}

add_action( 'wp_ajax_adfoin_save_buildertrend_credentials', 'adfoin_save_buildertrend_credentials' );
function adfoin_save_buildertrend_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'buildertrend' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'buildertrend', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_buildertrend_fields', 'adfoin_get_buildertrend_fields' );
function adfoin_get_buildertrend_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',     'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'street',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'country',    'value' => 'Country',    'description' => '' ),
        array( 'key' => 'projectType','value' => 'Project Type', 'description' => '' ),
        array( 'key' => 'budget',     'value' => 'Budget',     'description' => '' ),
        array( 'key' => 'leadSource', 'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'description','value' => 'Description','description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_buildertrend_credentials_list() {
    foreach ( adfoin_read_credentials( 'buildertrend' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_buildertrend_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'buildertrend', $cred_id );
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    $builder_id  = isset( $credentials['builderId'] ) ? $credentials['builderId'] : '';

    if ( ! $api_key || ! $builder_id ) return;

    $url  = 'https://api.buildertrend.com/v1/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'X-Builder-Id'  => $builder_id,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_buildertrend_job_queue', 'adfoin_buildertrend_job_queue', 10, 1 );
function adfoin_buildertrend_job_queue( $data ) {
    adfoin_buildertrend_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_buildertrend_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_lead' ) return;

    $body = array();
    foreach ( array( 'firstName' => 'firstName', 'lastName' => 'lastName', 'email' => 'email', 'phone' => 'phone', 'mobile' => 'mobile', 'projectType' => 'projectType', 'budget' => 'budget', 'leadSource' => 'leadSource', 'description' => 'description' ) as $local => $remote ) {
        if ( isset( $fields[ $local ] ) && $fields[ $local ] !== '' ) $body[ $remote ] = $fields[ $local ];
    }
    $addr = array();
    foreach ( array( 'street', 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $addr[ $k ] = $fields[ $k ];
    }
    if ( $addr ) $body['address'] = $addr;

    adfoin_buildertrend_request( 'leads', 'POST', $body, $record, $cred_id );
}
