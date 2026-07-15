<?php

add_filter( 'adfoin_action_providers', 'adfoin_insellerate_actions', 10, 1 );
function adfoin_insellerate_actions( $actions ) {
    $actions['insellerate'] = array(
        'title' => 'Insellerate',
        'tasks' => array( 'create_lead' => 'Create Lead' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_insellerate_settings_tab', 10, 1 );
function adfoin_insellerate_settings_tab( $providers ) { $providers['insellerate'] = 'Insellerate'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_insellerate_settings_view', 10, 1 );
function adfoin_insellerate_settings_view( $current_tab ) {
    if ( $current_tab !== 'insellerate' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'insellerate',
        'fields'   => array(
            array( 'key' => 'authToken',  'label' => __( 'Basic Auth Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'orgId',      'label' => __( 'Organization ID', 'advanced-form-integration' ) ),
            array( 'key' => 'campaignId', 'label' => __( 'Campaign ID', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Insellerate provides the Basic Auth token during partner setup and requires your server IP to be whitelisted. The Organization ID and Campaign ID are part of the campaign Post URL (app.insellerate.com/api/v2/integration/leads/{orgId}/{campaignId}), found in the campaign settings.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Insellerate', 'advanced-form-integration' ), 'insellerate', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_insellerate_action_fields' );
function adfoin_insellerate_action_fields() {
    ?>
    <script type="text/template" id="insellerate-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title"><label><?php esc_attr_e( 'Insellerate Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_insellerate_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'Insellerate [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_insellerate_credentials', 'adfoin_get_insellerate_credentials' );
function adfoin_get_insellerate_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'insellerate' ) );
}

add_action( 'wp_ajax_adfoin_save_insellerate_credentials', 'adfoin_save_insellerate_credentials' );
function adfoin_save_insellerate_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'insellerate' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'insellerate', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_insellerate_fields', 'adfoin_get_insellerate_fields' );
function adfoin_get_insellerate_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'First_Name',  'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'Last_Name',   'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'Email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'Phone',       'value' => 'Home Phone', 'description' => '' ),
        array( 'key' => 'Mobile',      'value' => 'Mobile Phone', 'description' => '' ),
        array( 'key' => 'Work',        'value' => 'Work Phone', 'description' => '' ),
        array( 'key' => 'Address',     'value' => 'Subject Property Address', 'description' => '' ),
        array( 'key' => 'City_Name',   'value' => 'Subject Property City',    'description' => '' ),
        array( 'key' => 'State_Name',  'value' => 'Subject Property State',   'description' => '' ),
        array( 'key' => 'Zip_Code',    'value' => 'Subject Property Zip',     'description' => '' ),
        array( 'key' => 'Loan1_InitialAmount_Proposed', 'value' => 'Loan Amount',  'description' => '' ),
        array( 'key' => 'Loan1_PurposeType_Proposed',   'value' => 'Loan Purpose', 'description' => 'e.g. Purchase, Refinance - Cash-Out' ),
        array( 'key' => 'Loan1_Type_Proposed',          'value' => 'Loan Type',    'description' => 'e.g. Conventional, FHA, VA' ),
        array( 'key' => 'Home_Value',  'value' => 'Home Value',   'description' => '' ),
        array( 'key' => 'Credit_Score','value' => 'Credit Score', 'description' => '' ),
        array( 'key' => 'Ref_Id',      'value' => 'Reference ID', 'description' => '' ),
        array( 'key' => 'Notes',       'value' => 'Notes',        'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_insellerate_credentials_list() {
    foreach ( adfoin_read_credentials( 'insellerate' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_insellerate_request( $row = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'insellerate', $cred_id );
    $auth_token  = isset( $credentials['authToken'] )  ? $credentials['authToken']  : '';
    $org_id      = isset( $credentials['orgId'] )      ? $credentials['orgId']      : '';
    $campaign_id = isset( $credentials['campaignId'] ) ? $credentials['campaignId'] : '';
    if ( ! $auth_token || ! $org_id || ! $campaign_id ) return;

    $url  = 'https://app.insellerate.com/api/v2/integration/leads/' . rawurlencode( $org_id ) . '/' . rawurlencode( $campaign_id );
    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . $auth_token,
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( array( 'root' => array( 'row' => array( $row ) ) ) ),
    );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_insellerate_job_queue', 'adfoin_insellerate_job_queue', 10, 1 );
function adfoin_insellerate_job_queue( $data ) {
    adfoin_insellerate_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_insellerate_send_data( $record, $posted_data ) {
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

    unset( $fields['credId'] );

    adfoin_insellerate_request( $fields, $record, $cred_id );
}
