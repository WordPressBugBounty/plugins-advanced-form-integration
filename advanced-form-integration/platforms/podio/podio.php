<?php

add_filter( 'adfoin_action_providers', 'adfoin_podio_actions', 10, 1 );
function adfoin_podio_actions( $actions ) {
    $actions['podio'] = array(
        'title' => 'Podio',
        'tasks' => array( 'create_item' => 'Create Item' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_podio_settings_tab', 10, 1 );
function adfoin_podio_settings_tab( $providers ) { $providers['podio'] = 'Podio'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_podio_settings_view', 10, 1 );
function adfoin_podio_settings_view( $current_tab ) {
    if ( $current_tab !== 'podio' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'podio',
        'fields'   => array(
            array( 'key' => 'appId',       'label' => __( 'App ID', 'advanced-form-integration' ) ),
            array( 'key' => 'appToken',    'label' => __( 'App Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'clientId',    'label' => __( 'Client ID', 'advanced-form-integration' ) ),
            array( 'key' => 'clientSecret','label' => __( 'Client Secret', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Podio, open the target App > Tools > Developer to copy the App ID and generate an App Token. Create an API client at developers.podio.com for Client ID + Secret. Items will be created in this specific app.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Podio', 'advanced-form-integration' ), 'podio', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_podio_action_fields' );
function adfoin_podio_action_fields() {
    ?>
    <script type="text/template" id="podio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_item'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_item'">
                <td scope="row-title"><label><?php esc_attr_e( 'Podio Account / App', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_podio_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_item', 'Podio [PRO]', 'tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_podio_credentials', 'adfoin_get_podio_credentials' );
function adfoin_get_podio_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'podio' ) );
}

add_action( 'wp_ajax_adfoin_save_podio_credentials', 'adfoin_save_podio_credentials' );
function adfoin_save_podio_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'podio' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'podio', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_podio_fields', 'adfoin_get_podio_fields' );
function adfoin_get_podio_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'title',     'value' => 'Title',     'description' => 'Item title / primary text field' ),
        array( 'key' => 'cf__field1','value' => 'Field 1',   'description' => 'Replace "field1" with your Podio field external_id' ),
        array( 'key' => 'cf__field2','value' => 'Field 2',   'description' => '' ),
        array( 'key' => 'cf__field3','value' => 'Field 3',   'description' => '' ),
        array( 'key' => 'cf__field4','value' => 'Field 4',   'description' => '' ),
        array( 'key' => 'cf__field5','value' => 'Field 5',   'description' => '' ),
        array( 'key' => 'cf__field6','value' => 'Field 6',   'description' => '' ),
        array( 'key' => 'cf__field7','value' => 'Field 7',   'description' => '' ),
        array( 'key' => 'cf__field8','value' => 'Field 8',   'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_podio_credentials_list() {
    foreach ( adfoin_read_credentials( 'podio' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_podio_get_token( $cred_id ) {
    $credentials   = adfoin_get_credentials_by_id( 'podio', $cred_id );
    $app_id        = isset( $credentials['appId'] )        ? $credentials['appId']        : '';
    $app_token     = isset( $credentials['appToken'] )     ? $credentials['appToken']     : '';
    $client_id     = isset( $credentials['clientId'] )     ? $credentials['clientId']     : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';

    if ( ! $app_id || ! $app_token || ! $client_id || ! $client_secret ) return '';

    // Confirmed via developers.podio.com/authentication/app_auth — the
    // token endpoint is on the api. subdomain and versioned /v2, not the
    // bare podio.com/oauth/token the previous version of this file used.
    $response = wp_remote_post( 'https://api.podio.com/oauth/token/v2', array(
        'timeout' => 30,
        'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
        'body'    => http_build_query( array(
            'grant_type'    => 'app',
            'app_id'        => $app_id,
            'app_token'     => $app_token,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ) ),
    ) );
    if ( is_wp_error( $response ) ) return '';
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    return isset( $body['access_token'] ) ? $body['access_token'] : '';
}

function adfoin_podio_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $token = adfoin_podio_get_token( $cred_id );
    if ( ! $token ) return;

    $url  = 'https://api.podio.com/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'OAuth2 ' . $token,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_podio_job_queue', 'adfoin_podio_job_queue', 10, 1 );
function adfoin_podio_job_queue( $data ) {
    adfoin_podio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_podio_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_item' ) return;

    $credentials = adfoin_get_credentials_by_id( 'podio', $cred_id );
    $app_id      = isset( $credentials['appId'] ) ? $credentials['appId'] : '';
    if ( ! $app_id ) return;

    // The "Title" field mapped in the UI was never actually included in
    // the request body — confirmed real bug (data silently dropped). Most
    // Podio apps' title field uses the external_id "title" by convention;
    // if a given app's title field has a different external_id, map it
    // via the generic cf__ fields instead.
    $podio_fields = array();
    if ( ! empty( $fields['title'] ) ) $podio_fields['title'] = $fields['title'];
    foreach ( $fields as $k => $v ) {
        if ( strpos( $k, 'cf__' ) === 0 && $v !== '' ) $podio_fields[ substr( $k, 4 ) ] = $v;
    }

    $body = array( 'fields' => $podio_fields );
    adfoin_podio_request( 'item/app/' . $app_id . '/', 'POST', $body, $record, $cred_id );
}
