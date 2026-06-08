<?php

add_filter( 'adfoin_action_providers', 'adfoin_breezechms_actions', 10, 1 );
function adfoin_breezechms_actions( $actions ) {
    $actions['breezechms'] = array(
        'title' => 'Breeze ChMS',
        'tasks' => array( 'create_person' => 'Create Person' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_breezechms_settings_tab', 10, 1 );
function adfoin_breezechms_settings_tab( $providers ) { $providers['breezechms'] = 'Breeze ChMS'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_breezechms_settings_view', 10, 1 );
function adfoin_breezechms_settings_view( $current_tab ) {
    if ( $current_tab !== 'breezechms' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'breezechms',
        'fields'   => array(
            array( 'key' => 'subdomain', 'label' => __( 'Subdomain (e.g. yourchurch)', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',    'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Breeze ChMS, go to Account Settings > API. Generate an API key and copy your Breeze subdomain (the part before .breezechms.com).', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Breeze ChMS', 'advanced-form-integration' ), 'breezechms', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_breezechms_action_fields' );
function adfoin_breezechms_action_fields() {
    ?>
    <script type="text/template" id="breezechms-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_person'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_person'">
                <td scope="row-title"><label><?php esc_attr_e( 'Breeze Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_breezechms_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_person', 'Breeze ChMS [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_breezechms_credentials', 'adfoin_get_breezechms_credentials' );
function adfoin_get_breezechms_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'breezechms' ) );
}

add_action( 'wp_ajax_adfoin_save_breezechms_credentials', 'adfoin_save_breezechms_credentials' );
function adfoin_save_breezechms_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'breezechms' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'breezechms', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_breezechms_fields', 'adfoin_get_breezechms_fields' );
function adfoin_get_breezechms_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'first_name', 'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'last_name',  'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Mobile Phone', 'description' => '' ),
        array( 'key' => 'home_phone', 'value' => 'Home Phone',   'description' => '' ),
        array( 'key' => 'work_phone', 'value' => 'Work Phone',   'description' => '' ),
        array( 'key' => 'birthdate',  'value' => 'Birthdate',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',     'value' => 'Gender',     'description' => '' ),
        array( 'key' => 'marital',    'value' => 'Marital Status', 'description' => '' ),
        array( 'key' => 'street',     'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',       'value' => 'City',       'description' => '' ),
        array( 'key' => 'state',      'value' => 'State',      'description' => '' ),
        array( 'key' => 'zip',        'value' => 'Zip',        'description' => '' ),
        array( 'key' => 'note',       'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_breezechms_credentials_list() {
    foreach ( adfoin_read_credentials( 'breezechms' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_breezechms_request( $endpoint, $method = 'GET', $params = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'breezechms', $cred_id );
    $subdomain   = isset( $credentials['subdomain'] ) ? $credentials['subdomain'] : '';
    $api_key     = isset( $credentials['apiKey'] )    ? $credentials['apiKey']    : '';
    if ( ! $subdomain || ! $api_key ) return;

    $url = 'https://' . $subdomain . '.breezechms.com/api/' . ltrim( $endpoint, '/' );
    if ( $params ) $url .= '?' . http_build_query( $params );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Api-Key' => $api_key,
            'Accept'  => 'application/json',
        ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_breezechms_job_queue', 'adfoin_breezechms_job_queue', 10, 1 );
function adfoin_breezechms_job_queue( $data ) {
    adfoin_breezechms_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_breezechms_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_person' ) return;

    $details = array();
    if ( ! empty( $fields['email'] ) )      $details['email_primary'] = $fields['email'];
    if ( ! empty( $fields['phone'] ) )      $details['phone_mobile']  = $fields['phone'];
    if ( ! empty( $fields['home_phone'] ) ) $details['phone_home']    = $fields['home_phone'];
    if ( ! empty( $fields['work_phone'] ) ) $details['phone_work']    = $fields['work_phone'];
    if ( ! empty( $fields['birthdate'] ) )  $details['birthdate']     = $fields['birthdate'];
    if ( ! empty( $fields['gender'] ) )     $details['gender']        = $fields['gender'];
    if ( ! empty( $fields['marital'] ) )    $details['marital_status']= $fields['marital'];
    if ( ! empty( $fields['street'] ) )     $details['street']        = $fields['street'];
    if ( ! empty( $fields['city'] ) )       $details['city']          = $fields['city'];
    if ( ! empty( $fields['state'] ) )      $details['state']         = $fields['state'];
    if ( ! empty( $fields['zip'] ) )        $details['zip']           = $fields['zip'];
    if ( ! empty( $fields['note'] ) )       $details['note']          = $fields['note'];

    adfoin_breezechms_request( 'people/add', 'GET', array(
        'first'   => isset( $fields['first_name'] ) ? $fields['first_name'] : '',
        'last'    => isset( $fields['last_name'] )  ? $fields['last_name']  : '',
        'fields_json' => wp_json_encode( $details ),
    ), $record, $cred_id );
}
