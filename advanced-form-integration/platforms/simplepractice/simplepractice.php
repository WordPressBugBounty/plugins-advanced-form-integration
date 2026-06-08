<?php

add_filter( 'adfoin_action_providers', 'adfoin_simplepractice_actions', 10, 1 );
function adfoin_simplepractice_actions( $actions ) {
    $actions['simplepractice'] = array(
        'title' => 'SimplePractice',
        'tasks' => array( 'create_client' => 'Create Client (Intake)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_simplepractice_settings_tab', 10, 1 );
function adfoin_simplepractice_settings_tab( $providers ) {
    $providers['simplepractice'] = 'SimplePractice';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_simplepractice_settings_view', 10, 1 );
function adfoin_simplepractice_settings_view( $current_tab ) {
    if ( $current_tab !== 'simplepractice' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'simplepractice',
        'fields'   => array(
            array( 'key' => 'webhookUrl', 'label' => __( 'Intake Webhook URL', 'advanced-form-integration' ) ),
            array( 'key' => 'apiKey',     'label' => __( 'Shared Secret', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'SimplePractice does not expose a public CRUD API. Use the Intake Webhook URL provided by your SimplePractice integration partner. Submissions are POSTed as JSON; sign with your shared secret if configured.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'SimplePractice', 'advanced-form-integration' ), 'simplepractice', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_simplepractice_action_fields' );
function adfoin_simplepractice_action_fields() {
    ?>
    <script type="text/template" id="simplepractice-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_client'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_client'">
                <td scope="row-title"><label><?php esc_attr_e( 'SimplePractice Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_simplepractice_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_client', 'SimplePractice [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_simplepractice_credentials', 'adfoin_get_simplepractice_credentials' );
function adfoin_get_simplepractice_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'simplepractice' ) );
}

add_action( 'wp_ajax_adfoin_save_simplepractice_credentials', 'adfoin_save_simplepractice_credentials' );
function adfoin_save_simplepractice_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'simplepractice' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'simplepractice', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_simplepractice_fields', 'adfoin_get_simplepractice_fields' );
function adfoin_get_simplepractice_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',     'value' => 'First Name',     'description' => '' ),
        array( 'key' => 'lastName',      'value' => 'Last Name',      'description' => '' ),
        array( 'key' => 'email',         'value' => 'Email',          'description' => '' ),
        array( 'key' => 'phone',         'value' => 'Phone',          'description' => '' ),
        array( 'key' => 'dateOfBirth',   'value' => 'Date of Birth',  'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'gender',        'value' => 'Gender',         'description' => '' ),
        array( 'key' => 'address',       'value' => 'Address',        'description' => '' ),
        array( 'key' => 'city',          'value' => 'City',           'description' => '' ),
        array( 'key' => 'state',         'value' => 'State',          'description' => '' ),
        array( 'key' => 'zip',           'value' => 'Zip',            'description' => '' ),
        array( 'key' => 'emergencyName', 'value' => 'Emergency Contact Name',  'description' => '' ),
        array( 'key' => 'emergencyPhone','value' => 'Emergency Contact Phone', 'description' => '' ),
        array( 'key' => 'referralSource','value' => 'Referral Source','description' => '' ),
        array( 'key' => 'notes',         'value' => 'Notes',          'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_simplepractice_credentials_list() {
    foreach ( adfoin_read_credentials( 'simplepractice' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_simplepractice_request( $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'simplepractice', $cred_id );
    $url         = isset( $credentials['webhookUrl'] ) ? $credentials['webhookUrl'] : '';
    $secret      = isset( $credentials['apiKey'] )     ? $credentials['apiKey']     : '';

    if ( ! $url ) return;

    $body    = wp_json_encode( $data );
    $headers = array( 'Content-Type' => 'application/json' );
    if ( $secret ) {
        $headers['X-SimplePractice-Signature'] = hash_hmac( 'sha256', $body, $secret );
    }

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => $body,
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_simplepractice_job_queue', 'adfoin_simplepractice_job_queue', 10, 1 );
function adfoin_simplepractice_job_queue( $data ) {
    adfoin_simplepractice_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_simplepractice_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] === 'create_client' ) {
        $body = array( 'event' => 'client.created' );
        foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'dateOfBirth', 'gender', 'address', 'city', 'state', 'zip', 'emergencyName', 'emergencyPhone', 'referralSource', 'notes' ) as $k ) {
            if ( ! empty( $fields[ $k ] ) ) $body[ $k ] = $fields[ $k ];
        }
        adfoin_simplepractice_request( $body, $record, $cred_id );
    }
}
