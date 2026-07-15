<?php

/**
 * Google Analytics 4 — send a server-side event via the Measurement
 * Protocol when a form is submitted.
 *
 * Auth: measurement_id (GA4 stream "G-XXXXX") + api_secret (GA4 Admin >
 * Data Streams > Measurement Protocol API secrets), both as query params.
 * Multi-account via ADFOIN_Account_Manager.
 *
 * Confirmed via developers.google.com/analytics/devguides/collection/protocol/ga4:
 * POST https://www.google-analytics.com/mp/collect?measurement_id=...&api_secret=...
 * body {"client_id":"...", "events":[{"name":"generate_lead","params":{...}}]}.
 * `client_id` is required — reuse the visitor's _ga cookie value if
 * captured, otherwise generate a random UUID server-side. Sending
 * `generate_lead` does NOT automatically mark it a conversion — the site
 * owner must separately toggle it as a "Key event" in GA4 Admin > Events.
 * Response is always 2xx regardless of payload validity; use
 * /debug/mp/collect while testing to see real validation errors.
 *
 * @link https://developers.google.com/analytics/devguides/collection/protocol/ga4
 */

add_filter( 'adfoin_action_providers', 'adfoin_ga4_actions', 10, 1 );

function adfoin_ga4_actions( $actions ) {
    $actions['ga4'] = array(
        'title' => __( 'Google Analytics 4', 'advanced-form-integration' ),
        'tasks' => array( 'send_event' => __( 'Send Event', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ga4_settings_tab', 10, 1 );

function adfoin_ga4_settings_tab( $providers ) {
    $providers['ga4'] = __( 'Google Analytics 4', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ga4_settings_view', 10, 1 );

function adfoin_ga4_settings_view( $current_tab ) {
    if ( 'ga4' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'measurementId',
            'label'         => __( 'Measurement ID (G-XXXXXXX)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'     => 'apiSecret',
            'label'    => __( 'Measurement Protocol API Secret', 'advanced-form-integration' ),
            'type'     => 'text',
            'required' => true,
            'mask'     => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In GA4, go to Admin > Data Streams > select your web stream to get the Measurement ID.', 'advanced-form-integration' ),
        esc_html__( 'On the same stream, open Measurement Protocol API secrets and create one.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. After events start arriving, mark your event name as a "Key event" under Admin > Events for it to appear in GA4 conversion reporting — sending the event alone does not do this automatically.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'ga4', __( 'Google Analytics 4', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_ga4_credentials', 'adfoin_get_ga4_credentials', 10, 0 );

function adfoin_get_ga4_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'ga4' );
}

add_action( 'wp_ajax_adfoin_save_ga4_credentials', 'adfoin_save_ga4_credentials', 10, 0 );

function adfoin_save_ga4_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'ga4', array( 'measurementId', 'apiSecret' ) );
}

function adfoin_ga4_credentials_list() {
    foreach ( adfoin_read_credentials( 'ga4' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_ga4_action_fields' );

function adfoin_ga4_action_fields() {
    ?>
    <script type="text/template" id="ga4-action-template">
        <table class="form-table" v-if="action.task == 'send_event'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'GA4 Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=ga4' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'send_event', 'Google Analytics 4 [PRO]', 'custom params & user data' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_ga4_fields', 'adfoin_get_ga4_fields' );

function adfoin_get_ga4_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'eventName',  'value' => __( 'Event Name', 'advanced-form-integration' ), 'required' => true, 'description' => __( 'Recommended: generate_lead', 'advanced-form-integration' ) ),
        array( 'key' => 'clientId',   'value' => __( 'Client ID (from _ga cookie, if available)', 'advanced-form-integration' ), 'description' => __( 'Leave blank to auto-generate a random one for this submission', 'advanced-form-integration' ) ),
        array( 'key' => 'value',      'value' => __( 'Value', 'advanced-form-integration' ) ),
        array( 'key' => 'currency',   'value' => __( 'Currency (e.g. USD)', 'advanced-form-integration' ) ),
        array( 'key' => 'leadSource', 'value' => __( 'Lead Source', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_ga4_job_queue', 'adfoin_ga4_job_queue', 10, 1 );

function adfoin_ga4_job_queue( $data ) {
    adfoin_ga4_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ga4_build_params( $fields ) {
    $params = array( 'engagement_time_msec' => 1 );
    if ( ! empty( $fields['value'] ) )      $params['value']       = floatval( $fields['value'] );
    if ( ! empty( $fields['currency'] ) )   $params['currency']    = $fields['currency'];
    if ( ! empty( $fields['leadSource'] ) ) $params['lead_source'] = $fields['leadSource'];
    return $params;
}

function adfoin_ga4_send_data( $record, $posted_data ) {
    if ( 'send_event' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );
    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    if ( ! $cred_id ) {
        return;
    }

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) continue;
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) $fields[ $key ] = $parsed;
    }

    if ( empty( $fields['eventName'] ) ) {
        return;
    }

    $client_id = ! empty( $fields['clientId'] ) ? $fields['clientId'] : wp_generate_uuid4();

    $body = array(
        'client_id' => $client_id,
        'events'    => array(
            array( 'name' => $fields['eventName'], 'params' => adfoin_ga4_build_params( $fields ) ),
        ),
    );

    adfoin_ga4_request( $body, $cred_id, $record );
}

if ( ! function_exists( 'adfoin_ga4_request' ) ) :
function adfoin_ga4_request( $body, $cred_id, $record = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'ga4', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['measurementId'] ) || empty( $credentials['apiSecret'] ) ) {
        return new WP_Error( 'ga4_missing_credentials', __( 'GA4 Measurement ID / API Secret not configured.', 'advanced-form-integration' ) );
    }

    $url = add_query_arg( array(
        'measurement_id' => $credentials['measurementId'],
        'api_secret'     => $credentials['apiSecret'],
    ), 'https://www.google-analytics.com/mp/collect' );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
