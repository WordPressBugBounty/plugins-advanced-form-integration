<?php

add_filter( 'adfoin_action_providers', 'adfoin_seventeenhats_actions', 10, 1 );
function adfoin_seventeenhats_actions( $actions ) {
    $actions['seventeenhats'] = array(
        'title' => '17hats',
        'tasks' => array( 'create_lead' => 'Create Lead (via Lead Capture Form)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_seventeenhats_settings_tab', 10, 1 );
function adfoin_seventeenhats_settings_tab( $providers ) {
    $providers['seventeenhats'] = '17hats';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_seventeenhats_settings_view', 10, 1 );
function adfoin_seventeenhats_settings_view( $current_tab ) {
    if ( $current_tab !== 'seventeenhats' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'seventeenhats',
        'fields'   => array(
            array( 'key' => 'leadFormUrl', 'label' => __( 'Lead Capture Form URL', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( '17hats has no public REST API. Build a Lead Capture Form in 17hats, copy its submission URL, and paste it here. Submissions trigger your existing 17hats workflows.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( '17hats', 'advanced-form-integration' ), 'seventeenhats', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_seventeenhats_action_fields' );
function adfoin_seventeenhats_action_fields() {
    ?>
    <script type="text/template" id="seventeenhats-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( '17hats Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_seventeenhats_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', '17hats [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_seventeenhats_credentials', 'adfoin_get_seventeenhats_credentials' );
function adfoin_get_seventeenhats_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    wp_send_json_success( adfoin_read_credentials( 'seventeenhats' ) );
}

add_action( 'wp_ajax_adfoin_save_seventeenhats_credentials', 'adfoin_save_seventeenhats_credentials' );
function adfoin_save_seventeenhats_credentials() {
    if ( ! adfoin_verify_nonce() ) return;
    if ( $_POST['platform'] === 'seventeenhats' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'seventeenhats', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_seventeenhats_fields', 'adfoin_get_seventeenhats_fields' );
function adfoin_get_seventeenhats_fields() {
    if ( ! adfoin_verify_nonce() ) return;
    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'projectType','value' => 'Project Type', 'description' => '' ),
        array( 'key' => 'eventDate',  'value' => 'Event Date',  'description' => '' ),
        array( 'key' => 'budget',     'value' => 'Budget',     'description' => '' ),
        array( 'key' => 'referral',   'value' => 'Referral Source', 'description' => '' ),
        array( 'key' => 'message',    'value' => 'Message',    'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_seventeenhats_credentials_list() {
    foreach ( adfoin_read_credentials( 'seventeenhats' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_seventeenhats_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'seventeenhats', $cred_id );
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

add_action( 'adfoin_seventeenhats_job_queue', 'adfoin_seventeenhats_job_queue', 10, 1 );
function adfoin_seventeenhats_job_queue( $data ) {
    adfoin_seventeenhats_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_seventeenhats_send_data( $record, $posted_data ) {
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
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'projectType', 'eventDate', 'budget', 'referral', 'message' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_seventeenhats_request( $body, $record, $cred_id );
}
