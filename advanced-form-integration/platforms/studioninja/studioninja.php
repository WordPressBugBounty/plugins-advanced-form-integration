<?php

add_filter( 'adfoin_action_providers', 'adfoin_studioninja_actions', 10, 1 );
function adfoin_studioninja_actions( $actions ) {
    $actions['studioninja'] = array(
        'title' => 'Studio Ninja',
        'tasks' => array( 'create_lead' => 'Create Lead (via Lead Capture Form)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_studioninja_settings_tab', 10, 1 );
function adfoin_studioninja_settings_tab( $providers ) {
    $providers['studioninja'] = 'Studio Ninja';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_studioninja_settings_view', 10, 1 );
function adfoin_studioninja_settings_view( $current_tab ) {
    if ( $current_tab !== 'studioninja' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'studioninja',
        'fields'   => array(
            array( 'key' => 'leadFormUrl', 'label' => __( 'Contact Form Submission URL', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Studio Ninja has no public REST API. Build a Contact Form in Studio Ninja, copy its submission URL, and paste it here. Submissions create new Studio Ninja leads.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Studio Ninja', 'advanced-form-integration' ), 'studioninja', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_studioninja_action_fields' );
function adfoin_studioninja_action_fields() {
    ?>
    <script type="text/template" id="studioninja-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Studio Ninja Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_studioninja_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Studio Ninja [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_studioninja_credentials', 'adfoin_get_studioninja_credentials' );
function adfoin_get_studioninja_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'studioninja' ) );
}

add_action( 'wp_ajax_adfoin_save_studioninja_credentials', 'adfoin_save_studioninja_credentials' );
function adfoin_save_studioninja_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'studioninja' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'studioninja', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_studioninja_fields', 'adfoin_get_studioninja_fields' );
function adfoin_get_studioninja_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'partnerName','value' => 'Partner Name','description' => '' ),
        array( 'key' => 'shootType',  'value' => 'Shoot / Job Type', 'description' => '' ),
        array( 'key' => 'shootDate',  'value' => 'Shoot Date', 'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'location',   'value' => 'Location',   'description' => '' ),
        array( 'key' => 'package',    'value' => 'Package',    'description' => '' ),
        array( 'key' => 'referral',   'value' => 'Referral Source', 'description' => '' ),
        array( 'key' => 'message',    'value' => 'Message',    'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_studioninja_credentials_list() {
    foreach ( adfoin_read_credentials( 'studioninja' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_studioninja_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'studioninja', $cred_id );
    $url         = isset( $credentials['leadFormUrl'] ) ? $credentials['leadFormUrl'] : '';
    if ( ! $url ) return;

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $data ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_studioninja_job_queue', 'adfoin_studioninja_job_queue', 10, 1 );
function adfoin_studioninja_job_queue( $data ) {
    adfoin_studioninja_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_studioninja_send_data( $record, $posted_data ) {
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
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'partnerName', 'shootType', 'shootDate', 'location', 'package', 'referral', 'message' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_studioninja_request( $body, $record, $cred_id );
}
