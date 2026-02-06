<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'adfoin_appointmenthourbooking_register_hooks', 20 );

/**
 * Register runtime hooks when Appointment Hour Booking is available.
 */
function adfoin_appointmenthourbooking_register_hooks() {
    if ( ! adfoin_appointmenthourbooking_is_active() ) {
        return;
    }

    add_action( 'cpappb_process_data', 'adfoin_appointmenthourbooking_handle_new_booking', 10, 1 );
    add_action( 'cpappb_update_status', 'adfoin_appointmenthourbooking_handle_status_update', 10, 2 );
}

/**
 * Determine if the Appointment Hour Booking plugin is active.
 *
 * @return bool
 */
function adfoin_appointmenthourbooking_is_active() {
    return class_exists( 'CP_AppBookingPlugin' );
}

/**
 * Retrieve Appointment Hour Booking trigger list.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_appointmenthourbooking_get_forms( $form_provider ) {
    if ( 'appointmenthourbooking' !== $form_provider ) {
        return;
    }

    $forms   = array();
    $forms[] = array(
        'id'   => 'any',
        'name' => __( 'Any Form', 'advanced-form-integration' ),
    );

    $db_forms = adfoin_appointmenthourbooking_fetch_forms();

    foreach ( $db_forms as $form ) {
        $forms[] = $form;
    }

    $options = array();

    foreach ( $forms as $form ) {
        $form_id   = $form['id'];
        $form_name = $form['name'];

        $options[ adfoin_appointmenthourbooking_build_form_key( 'new_booking', $form_id ) ] = sprintf(
            /* translators: %s - Appointment Hour Booking form name. */
            __( '%s – New Booking', 'advanced-form-integration' ),
            $form_name
        );

        $options[ adfoin_appointmenthourbooking_build_form_key( 'status_updated', $form_id ) ] = sprintf(
            /* translators: %s - Appointment Hour Booking form name. */
            __( '%s – Status Updated', 'advanced-form-integration' ),
            $form_name
        );
    }

    return $options;
}

/**
 * Retrieve field map for a given trigger selection.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier selected in UI.
 *
 * @return array<string,string>|void
 */
function adfoin_appointmenthourbooking_get_form_fields( $form_provider, $form_id ) {
    if ( 'appointmenthourbooking' !== $form_provider ) {
        return;
    }

    list( $event, $form_db_id ) = adfoin_appointmenthourbooking_parse_form_key( $form_id );

    $fields = adfoin_appointmenthourbooking_base_fields();

    if ( 'status_updated' === $event ) {
        $fields['booking_status']    = __( 'Booking Status', 'advanced-form-integration' );
        $fields['status_changed_at'] = __( 'Status Changed At', 'advanced-form-integration' );
    }

    $fields = array_merge( $fields, adfoin_appointmenthourbooking_appointment_fields() );

    if ( $form_db_id && 'any' !== $form_db_id ) {
        $fields = $fields + adfoin_appointmenthourbooking_get_custom_fields_for_form( (int) $form_db_id );
    }

    return $fields;
}

/**
 * Base/system fields available for every trigger.
 *
 * @return array<string,string>
 */
function adfoin_appointmenthourbooking_base_fields() {
    return array(
        'itemnumber'        => __( 'Entry ID', 'advanced-form-integration' ),
        'formid'            => __( 'Form ID', 'advanced-form-integration' ),
        'formname'          => __( 'Form Name', 'advanced-form-integration' ),
        'request_timestamp' => __( 'Request Timestamp', 'advanced-form-integration' ),
        'final_price'       => __( 'Final Price', 'advanced-form-integration' ),
        'final_price_short' => __( 'Final Price (Rounded)', 'advanced-form-integration' ),
        'referrer'          => __( 'Referrer URL', 'advanced-form-integration' ),
        'username'          => __( 'Submitted By (Username)', 'advanced-form-integration' ),
        'apps_json'         => __( 'Appointments (JSON)', 'advanced-form-integration' ),
        'event_type'        => __( 'Event Type', 'advanced-form-integration' ),
    );
}

/**
 * Appointment specific helper fields.
 *
 * @return array<string,string>
 */
function adfoin_appointmenthourbooking_appointment_fields() {
    return array(
        'appointment_service'      => __( 'First Appointment Service', 'advanced-form-integration' ),
        'appointment_service_list' => __( 'All Services (Comma Separated)', 'advanced-form-integration' ),
        'appointment_date'         => __( 'First Appointment Date', 'advanced-form-integration' ),
        'appointment_slot'         => __( 'First Appointment Slot', 'advanced-form-integration' ),
        'appointment_start_time'   => __( 'First Appointment Start Time', 'advanced-form-integration' ),
        'appointment_end_time'     => __( 'First Appointment End Time', 'advanced-form-integration' ),
        'appointment_quantity'     => __( 'First Appointment Quantity', 'advanced-form-integration' ),
        'appointment_status'       => __( 'First Appointment Status', 'advanced-form-integration' ),
    );
}

/**
 * Extract custom field labels for a specific form.
 *
 * @param int $form_id Form identifier.
 *
 * @return array<string,string>
 */
function adfoin_appointmenthourbooking_get_custom_fields_for_form( $form_id ) {
    $form = adfoin_appointmenthourbooking_get_form_by_id( $form_id );

    if ( empty( $form['structure'] ) ) {
        return array();
    }

    $structure = json_decode( adfoin_appointmenthourbooking_clean_json( $form['structure'] ) );

    if ( ! is_array( $structure ) || empty( $structure[0] ) || ! is_array( $structure[0] ) ) {
        return array();
    }

    $fields = array();

    foreach ( $structure[0] as $field ) {
        if ( empty( $field->name ) ) {
            continue;
        }

        $label = ! empty( $field->title ) ? $field->title : $field->name;
        $fields[ $field->name ] = sprintf(
            /* translators: %s - Field label defined inside Appointment Hour Booking form builder. */
            __( '%s (Field)', 'advanced-form-integration' ),
            $label
        );
    }

    return $fields;
}

/**
 * Handle cpappb_process_data (new booking) events.
 *
 * @param array $data Submitted payload.
 */
function adfoin_appointmenthourbooking_handle_new_booking( $data ) {
    if ( empty( $data ) || ! is_array( $data ) ) {
        return;
    }

    $form_id = isset( $data['formid'] ) ? (int) $data['formid'] : 0;

    $integration = new Advanced_Form_Integration_Integration();
    $records     = adfoin_appointmenthourbooking_collect_saved_records( $integration, 'new_booking', $form_id );

    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_appointmenthourbooking_prepare_payload( $data );
    $payload['event_type'] = 'new_booking';

    $integration->send( $records, $payload );
}

/**
 * Handle booking status updates.
 *
 * @param int    $entry_id Booking entry ID.
 * @param string $status   New status label.
 */
function adfoin_appointmenthourbooking_handle_status_update( $entry_id, $status ) {
    $entry = adfoin_appointmenthourbooking_fetch_entry( $entry_id );

    if ( ! $entry ) {
        return;
    }

    $data = maybe_unserialize( $entry['posted_data'] );

    if ( empty( $data ) || ! is_array( $data ) ) {
        return;
    }

    $data['itemnumber']        = $entry_id;
    $data['booking_status']    = $status;
    $data['status_changed_at'] = current_time( 'mysql' );

    $form_id = isset( $data['formid'] ) ? (int) $data['formid'] : 0;

    $integration = new Advanced_Form_Integration_Integration();
    $records     = adfoin_appointmenthourbooking_collect_saved_records( $integration, 'status_updated', $form_id );

    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_appointmenthourbooking_prepare_payload( $data );
    $payload['event_type'] = 'status_updated';

    $integration->send( $records, $payload );
}

/**
 * Prepare payload for delivery to AFI actions.
 *
 * @param array $data Raw booking data.
 *
 * @return array<string,string>
 */
function adfoin_appointmenthourbooking_prepare_payload( array $data ) {
    $payload = array();

    foreach ( $data as $key => $value ) {
        if ( 'apps' === $key ) {
            continue;
        }

        $payload[ $key ] = adfoin_appointmenthourbooking_normalize_value( $value );
    }

    $apps = array();

    if ( isset( $data['apps'] ) && is_array( $data['apps'] ) ) {
        $apps = $data['apps'];
    }

    $payload['apps_json'] = adfoin_appointmenthourbooking_normalize_value( $apps );

    $payload = array_merge( $payload, adfoin_appointmenthourbooking_first_appointment_details( $apps ) );

    return $payload;
}

/**
 * Normalize mixed values to strings.
 *
 * @param mixed $value Value to normalize.
 *
 * @return string
 */
function adfoin_appointmenthourbooking_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    if ( null === $value ) {
        return '';
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Derive helper appointment fields from the first booked slot.
 *
 * @param array $appointments Appointment list.
 *
 * @return array<string,string>
 */
function adfoin_appointmenthourbooking_first_appointment_details( array $appointments ) {
    if ( empty( $appointments ) ) {
        return array(
            'appointment_service'      => '',
            'appointment_service_list' => '',
            'appointment_date'         => '',
            'appointment_slot'         => '',
            'appointment_start_time'   => '',
            'appointment_end_time'     => '',
            'appointment_quantity'     => '',
            'appointment_status'       => '',
        );
    }

    $first = $appointments[0];

    $slot_parts = array();

    if ( ! empty( $first['slot'] ) && is_string( $first['slot'] ) ) {
        $slot_parts = array_map( 'trim', explode( '/', $first['slot'] ) );
    }

    $services = array();

    foreach ( $appointments as $app ) {
        if ( ! empty( $app['service'] ) ) {
            $services[] = $app['service'];
        }
    }

    return array(
        'appointment_service'      => adfoin_appointmenthourbooking_normalize_value( $first['service'] ?? '' ),
        'appointment_service_list' => implode( ', ', array_unique( $services ) ),
        'appointment_date'         => adfoin_appointmenthourbooking_normalize_value( $first['date'] ?? '' ),
        'appointment_slot'         => adfoin_appointmenthourbooking_normalize_value( $first['slot'] ?? '' ),
        'appointment_start_time'   => $slot_parts[0] ?? '',
        'appointment_end_time'     => $slot_parts[1] ?? '',
        'appointment_quantity'     => adfoin_appointmenthourbooking_normalize_value( $first['quant'] ?? '' ),
        'appointment_status'       => adfoin_appointmenthourbooking_normalize_value( $first['cancelled'] ?? '' ),
    );
}

/**
 * Fetch booking entry data from the Appointment Hour Booking table.
 *
 * @param int $entry_id Entry identifier.
 *
 * @return array<string,mixed>|null
 */
function adfoin_appointmenthourbooking_fetch_entry( $entry_id ) {
    global $wpdb;

    $table = $wpdb->prefix . 'cpappbk_messages';

    if ( ! adfoin_appointmenthourbooking_table_exists( $table ) ) {
        return null;
    }

    $entry = $wpdb->get_row( $wpdb->prepare( "SELECT id, formid, posted_data FROM {$table} WHERE id = %d", $entry_id ), ARRAY_A );

    return $entry ? $entry : null;
}

/**
 * Collect saved AFI integrations for a form/event combination.
 *
 * @param Advanced_Form_Integration_Integration $integration Integration manager instance.
 * @param string                                $event       Event key.
 * @param int                                   $form_id     Form identifier.
 *
 * @return array<int,array>
 */
function adfoin_appointmenthourbooking_collect_saved_records( Advanced_Form_Integration_Integration $integration, $event, $form_id ) {
    $records = array();

    $specific_key = adfoin_appointmenthourbooking_build_form_key( $event, $form_id );
    $any_key      = adfoin_appointmenthourbooking_build_form_key( $event, 'any' );

    $specific_records = $integration->get_by_trigger( 'appointmenthourbooking', $specific_key );
    $any_records      = $integration->get_by_trigger( 'appointmenthourbooking', $any_key );

    if ( is_array( $specific_records ) && ! empty( $specific_records ) ) {
        $records = array_merge( $records, $specific_records );
    }

    if ( is_array( $any_records ) && ! empty( $any_records ) ) {
        $records = array_merge( $records, $any_records );
    }

    return $records;
}

/**
 * Build a normalized form key used in AFI dropdown/storage.
 *
 * @param string     $event   Event identifier.
 * @param int|string $form_id Form ID or "any".
 *
 * @return string
 */
function adfoin_appointmenthourbooking_build_form_key( $event, $form_id ) {
    $event = in_array( $event, array( 'new_booking', 'status_updated' ), true ) ? $event : 'new_booking';

    if ( 'any' === $form_id || 'all' === $form_id || '' === $form_id || null === $form_id ) {
        return $event . '|any';
    }

    return $event . '|' . absint( $form_id );
}

/**
 * Parse stored form key back to event + form ID.
 *
 * @param string $form_key Stored option value.
 *
 * @return array{0:string,1:int|string}
 */
function adfoin_appointmenthourbooking_parse_form_key( $form_key ) {
    $parts = explode( '|', (string) $form_key );

    $event   = isset( $parts[0] ) ? $parts[0] : 'new_booking';
    $form_id = isset( $parts[1] ) ? $parts[1] : 'any';

    if ( 'any' !== $form_id ) {
        $form_id = absint( $form_id );
    }

    return array( $event, $form_id );
}

/**
 * Retrieve Appointment Hour Booking forms from database.
 *
 * @return array<int,array{ id:int, name:string, structure:string }>
 */
function adfoin_appointmenthourbooking_fetch_forms() {
    global $wpdb;

    $table = $wpdb->prefix . 'cpappbk_forms';

    if ( ! adfoin_appointmenthourbooking_table_exists( $table ) ) {
        return array();
    }

    $results = $wpdb->get_results( "SELECT id, form_name, form_structure FROM {$table} ORDER BY form_name ASC", ARRAY_A );

    if ( empty( $results ) ) {
        return array();
    }

    $forms = array();

    foreach ( $results as $row ) {
        $forms[] = array(
            'id'        => (int) $row['id'],
            'name'      => ! empty( $row['form_name'] ) ? $row['form_name'] : sprintf( __( 'Form #%d', 'advanced-form-integration' ), (int) $row['id'] ),
            'structure' => $row['form_structure'],
        );
    }

    return $forms;
}

/**
 * Retrieve a single form definition.
 *
 * @param int $form_id Form identifier.
 *
 * @return array{id:int,name:string,structure:string}|array
 */
function adfoin_appointmenthourbooking_get_form_by_id( $form_id ) {
    static $forms_index = null;

    if ( null === $forms_index ) {
        $forms_index = array();
        foreach ( adfoin_appointmenthourbooking_fetch_forms() as $form ) {
            $forms_index[ $form['id'] ] = $form;
        }
    }

    return isset( $forms_index[ $form_id ] ) ? $forms_index[ $form_id ] : array();
}

/**
 * Basic JSON sanitizer borrowed from the plugin behaviour.
 *
 * @param string $json JSON string.
 *
 * @return string
 */
function adfoin_appointmenthourbooking_clean_json( $json ) {
    return trim( preg_replace( '/\s+/', ' ', (string) $json ) );
}

/**
 * Check if a database table exists.
 *
 * @param string $table_name Table name with prefix.
 *
 * @return bool
 */
function adfoin_appointmenthourbooking_table_exists( $table_name ) {
    global $wpdb;

    $like   = $wpdb->esc_like( $table_name );
    $result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

    return $result === $table_name;
}
