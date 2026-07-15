<?php

/**
 * Twilio Segment — send `identify` + `track` events via the HTTP Tracking
 * API when a form is submitted, fanning the data out to whatever
 * destinations the customer has wired up in their Segment workspace.
 *
 * Auth: HTTP Basic, Source Write Key as username, blank password. Multi-
 * account via ADFOIN_Account_Manager.
 *
 * Confirmed via segment.com/docs/connections/sources/catalog/libraries/server/http-api:
 * POST https://api.segment.io/v1/track and /v1/identify,
 * Authorization: Basic base64(writeKey:), JSON body with userId or
 * anonymousId (at least one required), event/traits, properties, context,
 * timestamp (ISO8601).
 *
 * @link https://segment.com/docs/connections/sources/catalog/libraries/server/http-api/
 */

add_filter( 'adfoin_action_providers', 'adfoin_segment_actions', 10, 1 );

function adfoin_segment_actions( $actions ) {
    $actions['segment'] = array(
        'title' => __( 'Twilio Segment', 'advanced-form-integration' ),
        'tasks' => array( 'track_event' => __( 'Track Event', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_segment_settings_tab', 10, 1 );

function adfoin_segment_settings_tab( $providers ) {
    $providers['segment'] = __( 'Twilio Segment', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_segment_settings_view', 10, 1 );

function adfoin_segment_settings_view( $current_tab ) {
    if ( 'segment' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'writeKey', 'label' => __( 'Source Write Key', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true, 'show_in_table' => true ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Segment, create (or select) an "HTTP API" source.', 'advanced-form-integration' ),
        esc_html__( 'Copy its Write Key and paste it below.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'segment', __( 'Twilio Segment', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_segment_credentials', 'adfoin_get_segment_credentials', 10, 0 );

function adfoin_get_segment_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'segment' );
}

add_action( 'wp_ajax_adfoin_save_segment_credentials', 'adfoin_save_segment_credentials', 10, 0 );

function adfoin_save_segment_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'segment', array( 'writeKey' ) );
}

function adfoin_segment_credentials_list() {
    foreach ( adfoin_read_credentials( 'segment' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_segment_action_fields' );

function adfoin_segment_action_fields() {
    ?>
    <script type="text/template" id="segment-action-template">
        <table class="form-table" v-if="action.task == 'track_event'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Segment Source', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=segment' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'track_event', 'Twilio Segment [PRO]', 'custom traits & properties' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_segment_fields', 'adfoin_get_segment_fields' );

function adfoin_get_segment_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'userId',      'value' => __( 'User ID (email works well)', 'advanced-form-integration' ), 'description' => __( 'At least one of User ID / leave blank for an auto-generated anonymous ID', 'advanced-form-integration' ) ),
        array( 'key' => 'eventName',   'value' => __( 'Event Name', 'advanced-form-integration' ), 'required' => true, 'description' => __( 'e.g. "Form Submitted"', 'advanced-form-integration' ) ),
        array( 'key' => 'email',       'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'firstName',   'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',    'value' => __( 'Last Name', 'advanced-form-integration' ) ),
    );
    wp_send_json_success( $fields );
}

function adfoin_segment_get_ip() {
    foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ) as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
        }
    }
    return '';
}

add_action( 'adfoin_segment_job_queue', 'adfoin_segment_job_queue', 10, 1 );
function adfoin_segment_job_queue( $data ) {
    adfoin_segment_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_segment_send_data( $record, $posted_data ) {
    if ( 'track_event' !== ( $record['task'] ?? '' ) ) {
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

    $user_id      = ! empty( $fields['userId'] ) ? $fields['userId'] : '';
    $anonymous_id = $user_id ? '' : wp_generate_uuid4();

    $properties = array();
    if ( ! empty( $fields['email'] ) )     $properties['email']      = $fields['email'];
    if ( ! empty( $fields['firstName'] ) ) $properties['first_name'] = $fields['firstName'];
    if ( ! empty( $fields['lastName'] ) )  $properties['last_name']  = $fields['lastName'];

    $body = array(
        'event'      => $fields['eventName'],
        'properties' => $properties,
        'context'    => array( 'ip' => adfoin_segment_get_ip() ),
        'timestamp'  => gmdate( 'c' ),
    );
    if ( $user_id )      $body['userId'] = $user_id;
    if ( $anonymous_id )  $body['anonymousId'] = $anonymous_id;

    adfoin_segment_request( 'track', $body, $cred_id, $record );
}

if ( ! function_exists( 'adfoin_segment_request' ) ) :
function adfoin_segment_request( $endpoint, $body, $cred_id, $record = array() ) {
    $credentials = adfoin_get_credentials_by_id( 'segment', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['writeKey'] ) ) {
        return new WP_Error( 'segment_missing_credentials', __( 'Segment Write Key not configured.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.segment.io/v1/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $credentials['writeKey'] . ':' ),
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
    );

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
