<?php

/**
 * WebinarGeek — Register subscribers for live or on-demand webinars.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: custom header `Webinargeek-Api-Token: <api_key>` (NOT Authorization).
 *
 * WebinarGeek is a Dutch GDPR-focused webinar platform. The API exposes two
 * separate subscriber endpoints — one for live webinars and one for on-demand
 * replays — but the payload shape is identical, so both tasks share the same
 * field list and dispatcher and only differ in the URL segment.
 *
 * @link https://api.webinargeek.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_webinargeek_actions', 10, 1 );

function adfoin_webinargeek_actions( $actions ) {
    $actions['webinargeek'] = array(
        'title' => __( 'WebinarGeek', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee (Live Webinar)', 'advanced-form-integration' ),
            'register_ondemand' => __( 'Register Attendee (On-Demand)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_webinargeek_settings_tab', 10, 1 );

function adfoin_webinargeek_settings_tab( $providers ) {
    $providers['webinargeek'] = __( 'WebinarGeek', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_webinargeek_settings_view', 10, 1 );

function adfoin_webinargeek_settings_view( $current_tab ) {
    if ( 'webinargeek' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your WebinarGeek API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In WebinarGeek, go to Settings → Integrations → API.', 'advanced-form-integration' ),
        esc_html__( 'Enable API access and copy the API key.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI sends it as Webinargeek-Api-Token on requests to https://app.webinargeek.com/api/v2/.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'webinargeek', __( 'WebinarGeek', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_webinargeek_credentials', 'adfoin_get_webinargeek_credentials', 10, 0 );

function adfoin_get_webinargeek_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'webinargeek' );
}

add_action( 'wp_ajax_adfoin_save_webinargeek_credentials', 'adfoin_save_webinargeek_credentials', 10, 0 );

function adfoin_save_webinargeek_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'webinargeek', array( 'api_key' ) );
}

function adfoin_webinargeek_credentials_list() {
    foreach ( adfoin_read_credentials( 'webinargeek' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_webinargeek_action_fields' );

function adfoin_webinargeek_action_fields() {
    ?>
    <script type="text/template" id="webinargeek-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee' || action.task == 'register_ondemand'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'WebinarGeek Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=webinargeek' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_webinargeek_fields', 'adfoin_get_webinargeek_fields' );

function adfoin_get_webinargeek_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'webinar_id', 'value' => __( 'Webinar ID (required, integer)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',  'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',      'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'company',    'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',      'value' => __( 'Phone', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_webinargeek_job_queue', 'adfoin_webinargeek_job_queue', 10, 1 );

function adfoin_webinargeek_job_queue( $data ) {
    adfoin_webinargeek_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_webinargeek_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'register_attendee', 'register_ondemand' ), true ) ) {
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

    // Resolve all flat values up-front. webinar_id becomes a path segment
    // below; everything else is wrapped in the subscriber{} envelope.
    $values   = array();
    $reserved = array( 'credId' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // Required: email + webinar_id.
    if ( empty( $values['email'] ) || empty( $values['webinar_id'] ) ) {
        return;
    }

    $webinar_id = absint( $values['webinar_id'] );

    if ( $webinar_id <= 0 ) {
        return;
    }

    $subscriber_keys = array( 'first_name', 'last_name', 'email', 'company', 'phone' );
    $subscriber      = array();
    foreach ( $subscriber_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $subscriber[ $key ] = (string) $values[ $key ];
        }
    }

    $payload = array(
        'subscriber' => $subscriber,
    );

    // Route to the live or on-demand subscriber endpoint based on task.
    $endpoint = ( 'register_ondemand' === $task )
        ? 'webinars/' . $webinar_id . '/on_demand_subscribers'
        : 'webinars/' . $webinar_id . '/subscribers';

    adfoin_webinargeek_request( $endpoint, 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_webinargeek_request' ) ) :
function adfoin_webinargeek_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'webinargeek', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['api_key'] ) ) {
        return new WP_Error( 'webinargeek_missing_credentials', __( 'WebinarGeek API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://app.webinargeek.com/api/v2/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Webinargeek-Api-Token' => $credentials['api_key'],
            'Accept'                => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( is_array( $data ) ? $data : array() );
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
