<?php

add_filter( 'adfoin_action_providers', 'adfoin_honeybook_actions', 10, 1 );
function adfoin_honeybook_actions( $actions ) {
    $actions['honeybook'] = array(
        'title' => 'HoneyBook',
        'tasks' => array( 'create_lead' => 'Create Lead / Inquiry' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_honeybook_settings_tab', 10, 1 );
function adfoin_honeybook_settings_tab( $providers ) {
    $providers['honeybook'] = 'HoneyBook';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_honeybook_settings_view', 10, 1 );
function adfoin_honeybook_settings_view( $current_tab ) {
    if ( $current_tab !== 'honeybook' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'honeybook',
        'fields'   => array(
            array( 'key' => 'contactFormUrl', 'label' => __( 'HoneyBook Contact Form URL', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',         'label' => __( 'API Key (optional)', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'HoneyBook ingest is best done via your published Contact Form. In HoneyBook, build a Contact Form, copy its public submission URL, and paste it here. If you have an API key issued through the HoneyBook partner program, add it as well.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'HoneyBook', 'advanced-form-integration' ), 'honeybook', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_honeybook_action_fields' );
function adfoin_honeybook_action_fields() {
    ?>
    <script type="text/template" id="honeybook-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'HoneyBook Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_honeybook_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'HoneyBook [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_honeybook_credentials', 'adfoin_get_honeybook_credentials' );
function adfoin_get_honeybook_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'honeybook' ) );
}

add_action( 'wp_ajax_adfoin_save_honeybook_credentials', 'adfoin_save_honeybook_credentials' );
function adfoin_save_honeybook_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'honeybook' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'honeybook', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_honeybook_fields', 'adfoin_get_honeybook_fields' );
function adfoin_get_honeybook_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',  'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',   'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',      'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',      'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'projectType','value' => 'Project Type', 'description' => 'wedding, portrait, etc.' ),
        array( 'key' => 'eventDate',  'value' => 'Event Date',   'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'eventLocation','value' => 'Event Location', 'description' => '' ),
        array( 'key' => 'budget',     'value' => 'Budget',       'description' => '' ),
        array( 'key' => 'howHeard',   'value' => 'How They Heard About You', 'description' => '' ),
        array( 'key' => 'partnerName','value' => 'Partner Name', 'description' => 'For weddings' ),
        array( 'key' => 'message',    'value' => 'Message',      'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_honeybook_credentials_list() {
    foreach ( adfoin_read_credentials( 'honeybook' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_honeybook_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'honeybook', $cred_id );
    $form_url    = isset( $credentials['contactFormUrl'] ) ? $credentials['contactFormUrl'] : '';
    $api_key     = isset( $credentials['apiKey'] )         ? $credentials['apiKey']         : '';

    if ( ! $form_url ) return;

    $headers = array( 'Content-Type' => 'application/json' );
    if ( $api_key ) $headers['Authorization'] = 'Bearer ' . $api_key;

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => wp_json_encode( $data ),
    );
    $response = wp_remote_request( $form_url, $args );
    if ( $record ) adfoin_add_to_log( $response, $form_url, $args, $record );
    return $response;
}

add_action( 'adfoin_honeybook_job_queue', 'adfoin_honeybook_job_queue', 10, 1 );
function adfoin_honeybook_job_queue( $data ) {
    adfoin_honeybook_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_honeybook_send_data( $record, $posted_data ) {
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
    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'projectType', 'eventDate', 'eventLocation', 'budget', 'howHeard', 'partnerName', 'message' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
    }
    adfoin_honeybook_request( $body, $record, $cred_id );
}
