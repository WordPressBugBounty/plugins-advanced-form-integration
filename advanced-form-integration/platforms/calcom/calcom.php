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
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Event Type', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[event_type_id]" v-model="fielddata.event_type_id" required="required">
                        <option value=""><?php esc_html_e( 'Select Event Type...', 'advanced-form-integration' ); ?></option>
                        <option v-for="ev in eventTypesList" :value="ev.id">{{ ev.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': eventTypesLoading}"></div>
                    <p class="description"><?php esc_html_e( 'The booking is created against this event type. Its booking questions appear below.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_booking', 'Cal.com [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

/*
 * AJAX: list the account's event types for the dropdown.
 * GET /v2/event-types (cal-api-version 2024-06-14). The response is either a
 * flat list or grouped under eventTypeGroups[].eventTypes[] — handle both.
 */
add_action( 'wp_ajax_adfoin_get_calcom_event_types', 'adfoin_get_calcom_event_types' );

function adfoin_get_calcom_event_types() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'Missing credential id.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_calcom_request( 'event-types', 'GET', array(), array(), $cred_id, '2024-06-14' );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error( array( 'message' => adfoin_calcom_error_message( $response ) ) );
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $items = array();

    foreach ( adfoin_calcom_flatten_event_types( isset( $body['data'] ) ? $body['data'] : array() ) as $ev ) {
        if ( ! empty( $ev['id'] ) ) {
            $items[] = array(
                'id'    => $ev['id'],
                'title' => isset( $ev['title'] ) ? $ev['title'] : ( isset( $ev['slug'] ) ? $ev['slug'] : $ev['id'] ),
            );
        }
    }

    wp_send_json_success( $items );
}

/*
 * Normalize the /event-types payload (flat list OR grouped) into a flat list.
 */
function adfoin_calcom_flatten_event_types( $data ) {
    $out = array();

    if ( ! is_array( $data ) ) {
        return $out;
    }

    foreach ( $data as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }
        if ( isset( $item['eventTypes'] ) && is_array( $item['eventTypes'] ) ) {
            foreach ( $item['eventTypes'] as $ev ) {
                $out[] = $ev;
            }
        } else {
            $out[] = $item;
        }
    }

    return $out;
}

/*
 * Readable error message from a Cal.com response.
 */
function adfoin_calcom_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $msg  = '';

    if ( is_array( $body ) ) {
        if ( ! empty( $body['error']['message'] ) ) {
            $msg = $body['error']['message'];
        } elseif ( ! empty( $body['message'] ) ) {
            $msg = is_array( $body['message'] ) ? implode( ' ', $body['message'] ) : $body['message'];
        }
    }

    if ( 401 === $code || 403 === $code ) {
        $msg = trim( $msg . ' ' . __( 'Check your Cal.com API key.', 'advanced-form-integration' ) );
    }

    return $msg ? $msg : ( $code ? sprintf( __( 'Cal.com API returned HTTP %d.', 'advanced-form-integration' ), $code ) : __( 'Could not reach Cal.com.', 'advanced-form-integration' ) );
}

add_action( 'wp_ajax_adfoin_get_calcom_fields', 'adfoin_get_calcom_fields' );

function adfoin_get_calcom_fields() {
    adfoin_verify_nonce();

    $cred_id       = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $event_type_id = isset( $_POST['eventTypeId'] ) ? (int) $_POST['eventTypeId'] : 0;

    $fields = array(
        array( 'key' => 'start',             'value' => __( 'Start (ISO 8601 UTC, e.g. 2026-01-15T10:00:00Z)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'attendee_name',     'value' => __( 'Attendee Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'attendee_email',    'value' => __( 'Attendee Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'attendee_timezone', 'value' => __( 'Attendee Timezone (IANA; defaults to your site timezone)', 'advanced-form-integration' ) ),
        array( 'key' => 'attendee_language', 'value' => __( 'Attendee Language (ISO 639-1, defaults to en)', 'advanced-form-integration' ) ),
        array( 'key' => 'attendee_phone',    'value' => __( 'Attendee Phone (E.164, for SMS reminders)', 'advanced-form-integration' ) ),
        array( 'key' => 'guests',            'value' => __( 'Guests (comma-separated emails)', 'advanced-form-integration' ) ),
        array( 'key' => 'length_in_minutes', 'value' => __( 'Length in Minutes (variable-length event types)', 'advanced-form-integration' ) ),
        array( 'key' => 'recurrence_count',  'value' => __( 'Recurrence Count (recurring event types)', 'advanced-form-integration' ) ),
    );

    // Append the selected event type's booking questions (so required custom
    // questions can be answered — otherwise Cal.com rejects the booking).
    if ( $cred_id && $event_type_id ) {
        $response = adfoin_calcom_request( 'event-types/' . $event_type_id, 'GET', array(), array(), $cred_id, '2024-06-14' );

        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body    = json_decode( wp_remote_retrieve_body( $response ), true );
            $booking = isset( $body['data']['bookingFields'] ) && is_array( $body['data']['bookingFields'] ) ? $body['data']['bookingFields'] : array();

            foreach ( $booking as $bf ) {
                $slug = isset( $bf['slug'] ) ? $bf['slug'] : '';
                if ( '' === $slug || in_array( $slug, array( 'name', 'email', 'guests', 'location', 'title', 'rescheduleReason', 'attendeePhoneNumber' ), true ) ) {
                    continue;
                }
                if ( ! empty( $bf['hidden'] ) ) {
                    continue;
                }
                $label = ! empty( $bf['label'] ) ? $bf['label'] : ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );
                $fields[] = array(
                    'key'         => 'question_' . $slug,
                    'value'       => $label . ' [Question]',
                    'description' => ! empty( $bf['required'] ) ? 'Required' : '',
                );
            }
        }
    }

    wp_send_json_success( $fields );
}

/*
 * Build the POST /v2/bookings payload from the mapped field data. Shared by the
 * free task and the Pro "create booking" task. Returns the payload array, or
 * null when a required field (event type / start / attendee name+email) is
 * missing.
 */
function adfoin_calcom_build_booking_payload( $field_data, $posted_data ) {
    $event_type_id = isset( $field_data['event_type_id'] ) ? (int) $field_data['event_type_id'] : 0;
    $reserved      = array( 'credId' => 1, 'event_type_id' => 1 );
    $values        = array();

    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    if ( ! $event_type_id || empty( $values['start'] ) || empty( $values['attendee_email'] ) || empty( $values['attendee_name'] ) ) {
        return null;
    }

    $attendee = array(
        'name'     => $values['attendee_name'],
        'email'    => $values['attendee_email'],
        'timeZone' => ! empty( $values['attendee_timezone'] ) ? $values['attendee_timezone'] : wp_timezone_string(),
        'language' => ! empty( $values['attendee_language'] ) ? $values['attendee_language'] : 'en',
    );

    if ( ! empty( $values['attendee_phone'] ) ) {
        $attendee['phoneNumber'] = $values['attendee_phone'];
    }

    $payload = array(
        'start'       => $values['start'],
        'eventTypeId' => $event_type_id,
        'attendee'    => $attendee,
        'metadata'    => array( 'source' => 'wordpress_form' ),
    );

    if ( ! empty( $values['guests'] ) ) {
        $payload['guests'] = array_values( array_filter( array_map( 'trim', explode( ',', $values['guests'] ) ), 'strlen' ) );
    }
    if ( ! empty( $values['length_in_minutes'] ) ) {
        $payload['lengthInMinutes'] = (int) $values['length_in_minutes'];
    }
    if ( ! empty( $values['recurrence_count'] ) ) {
        $payload['recurrenceCount'] = (int) $values['recurrence_count'];
    }

    $responses = array();
    foreach ( $values as $key => $value ) {
        if ( 0 === strpos( $key, 'question_' ) ) {
            $responses[ substr( $key, 9 ) ] = $value;
        }
    }
    if ( ! empty( $responses ) ) {
        $payload['bookingFieldsResponses'] = $responses;
    }

    return $payload;
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

    $payload = adfoin_calcom_build_booking_payload( $field_data, $posted_data );

    // Null means a required field (event type / start / attendee) was missing —
    // bail rather than logging a guaranteed 400.
    if ( null === $payload ) {
        return;
    }

    adfoin_calcom_request( 'bookings', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_calcom_request' ) ) :
/**
 * @param string $version cal-api-version header. Bookings need 2024-08-13,
 *                        event-types 2024-06-14, slots 2024-09-04.
 */
function adfoin_calcom_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $version = '2024-08-13' ) {
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
            'cal-api-version' => $version,
            'Accept'          => 'application/json',
            // Cal.com's API is behind Cloudflare, which 403s requests with no
            // User-Agent. WordPress sets a default UA so this normally works,
            // but be explicit so a stripped UA can't break every booking.
            'User-Agent'      => 'advanced-form-integration',
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
