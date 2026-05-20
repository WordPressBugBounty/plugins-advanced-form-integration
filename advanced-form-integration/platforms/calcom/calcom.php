<?php

/**
 * Cal.com — Create Booking via POST /v2/bookings.
 *
 * Cal.com is an open-source Calendly alternative. Bookings are created
 * against an event type (numeric ID configured on the Cal.com side) for a
 * specified UTC start time. The attendee block carries name/email/timezone/
 * language and is required.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_key>
 * Required v2 header: cal-api-version: 2024-08-13
 *
 * @link https://cal.com/docs/api-reference/v2/introduction
 */

add_filter( 'adfoin_action_providers', 'adfoin_calcom_actions', 10, 1 );

function adfoin_calcom_actions( $actions ) {
    $actions['calcom'] = array(
        'title' => __( 'Cal.com', 'advanced-form-integration' ),
        'tasks' => array(
            'create_booking' => __( 'Create Booking', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_calcom_settings_tab', 10, 1 );

function adfoin_calcom_settings_tab( $providers ) {
    $providers['calcom'] = __( 'Cal.com', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_calcom_settings_view', 10, 1 );

function adfoin_calcom_settings_view( $current_tab ) {
    if ( 'calcom' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => 'cal_live_...',
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Cal.com and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.cal.com/settings/developer/api-keys">Settings &rarr; Developer &rarr; API keys</a>' ),
        esc_html__( 'Click "Add" to generate a new API key. Give it a descriptive name (e.g. WordPress) and pick an expiry.', 'advanced-form-integration' ),
        esc_html__( 'Copy the key immediately — Cal.com only shows the secret once. It typically starts with "cal_live_".', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.cal.com/v2/ with this key in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'calcom', __( 'Cal.com', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_calcom_credentials', 'adfoin_get_calcom_credentials', 10, 0 );

function adfoin_get_calcom_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'calcom' );
}

add_action( 'wp_ajax_adfoin_save_calcom_credentials', 'adfoin_save_calcom_credentials', 10, 0 );

function adfoin_save_calcom_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'calcom', array( 'apiKey' ) );
}

function adfoin_calcom_credentials_list() {
    foreach ( adfoin_read_credentials( 'calcom' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_calcom_action_fields' );

function adfoin_calcom_action_fields() {
    ?>
    <script type="text/template" id="calcom-action-template">
        <table class="form-table" v-if="action.task == 'create_booking'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Cal.com Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=calcom' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_calcom_fields', 'adfoin_get_calcom_fields' );

function adfoin_get_calcom_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'event_type_id',     'value' => __( 'Event Type ID (numeric, from Cal.com event type settings)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'start',             'value' => __( 'Start (ISO 8601 UTC, e.g. 2026-01-15T10:00:00Z)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'attendee_name',     'value' => __( 'Attendee Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'attendee_email',    'value' => __( 'Attendee Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'attendee_timezone', 'value' => __( 'Attendee Timezone (IANA, e.g. America/New_York; defaults to UTC)', 'advanced-form-integration' ) ),
        array( 'key' => 'attendee_language', 'value' => __( 'Attendee Language (ISO 639-1, defaults to en)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_calcom_job_queue', 'adfoin_calcom_job_queue', 10, 1 );

function adfoin_calcom_job_queue( $data ) {
    adfoin_calcom_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_calcom_send_data( $record, $posted_data ) {
    if ( 'create_booking' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat values up-front. Cal.com expects a nested attendee
    // sub-object — the form just feeds us flat key=>value pairs.
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

    // Cal.com requires event_type_id + start + attendee identity. Bail early
    // if any of the four are missing so we don't log a guaranteed 400.
    if ( empty( $values['event_type_id'] ) || empty( $values['start'] ) || empty( $values['attendee_email'] ) || empty( $values['attendee_name'] ) ) {
        return;
    }

    $attendee = array(
        'name'     => $values['attendee_name'],
        'email'    => $values['attendee_email'],
        // Cal.com v2 uses camelCase timeZone — IANA name (e.g. America/New_York).
        'timeZone' => ! empty( $values['attendee_timezone'] ) ? $values['attendee_timezone'] : 'UTC',
        'language' => ! empty( $values['attendee_language'] ) ? $values['attendee_language'] : 'en',
    );

    $payload = array(
        'start'       => $values['start'],
        'eventTypeId' => (int) $values['event_type_id'],
        'attendee'    => $attendee,
        'metadata'    => array(
            'source' => 'wordpress_form',
        ),
    );

    adfoin_calcom_request( 'bookings', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_calcom_request' ) ) :
function adfoin_calcom_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'calcom', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'calcom_missing_credentials', __( 'Cal.com API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.cal.com/v2/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization'   => 'Bearer ' . $credentials['apiKey'],
            'cal-api-version' => '2024-08-13',
            'Accept'          => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
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
