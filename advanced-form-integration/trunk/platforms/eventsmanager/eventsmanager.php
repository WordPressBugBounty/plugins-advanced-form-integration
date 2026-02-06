<?php

add_filter( 'adfoin_action_providers', 'adfoin_eventsmanager_actions', 10, 1 );

/**
 * Register Events Manager as an action provider.
 *
 * @param array $actions Existing providers.
 *
 * @return array
 */
function adfoin_eventsmanager_actions( $actions ) {
    $actions['eventsmanager'] = array(
        'title' => __( 'Events Manager', 'advanced-form-integration' ),
        'tasks' => array(
            'create_event' => __( 'Create Event', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_eventsmanager_action_fields' );

/**
 * Render the Events Manager action template.
 *
 * @return void
 */
function adfoin_eventsmanager_action_fields() {
    ?>
    <script type="text/template" id="eventsmanager-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_event'">
                <th scope="row">
                    <?php esc_attr_e( 'Event Details', 'advanced-form-integration' ); ?>
                </th>
                <td>
                    <p class="description" v-pre><?php esc_html_e( 'Provide event information. Use {{field_key}} placeholders to map form values.', 'advanced-form-integration' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Dates must use YYYY-MM-DD. Times may use 24-hour or AM/PM formats.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                            :key="field.value"
                            :field="field"
                            :trigger="trigger"
                            :action="action"
                            :fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_eventsmanager_job_queue', 'adfoin_eventsmanager_job_queue', 10, 1 );

/**
 * Process queued Events Manager actions.
 *
 * @param array $data Job queue payload.
 *
 * @return void
 */
function adfoin_eventsmanager_job_queue( $data ) {
    adfoin_eventsmanager_send_data( $data['record'], $data['posted_data'] );
}

if ( ! function_exists( 'adfoin_eventsmanager_send_data' ) ) {
    /**
     * Route Events Manager task execution.
     *
     * @param array $record      Integration record data.
     * @param array $posted_data Trigger payload.
     *
     * @return void
     */
    function adfoin_eventsmanager_send_data( $record, $posted_data ) {
        $record_data = json_decode( $record['data'], true );
        $cl          = isset( $record_data['action_data']['cl'] ) ? $record_data['action_data']['cl'] : array();

        if ( adfoin_check_conditional_logic( $cl, $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $fields     = adfoin_eventsmanager_prepare_fields( $field_data, $posted_data );
        $task       = isset( $record['task'] ) ? $record['task'] : '';

        $result = array(
            'success' => false,
            'message' => __( 'Unsupported task.', 'advanced-form-integration' ),
        );

        if ( 'create_event' === $task ) {
            $result = adfoin_eventsmanager_create_event( $fields );
        }

        $log_args = array(
            'method' => 'LOCAL',
            'body'   => $fields,
        );

        $log_response = array(
            'response' => array(
                'code'    => ! empty( $result['success'] ) ? 200 : 400,
                'message' => isset( $result['message'] ) ? $result['message'] : '',
            ),
            'body'     => $result,
        );

        adfoin_add_to_log( $log_response, 'eventsmanager', $log_args, $record );
    }
}

if ( ! function_exists( 'adfoin_eventsmanager_prepare_fields' ) ) {
    /**
     * Prepare parsed field values.
     *
     * @param array $field_data  Saved field mapping.
     * @param array $posted_data Trigger payload.
     *
     * @return array
     */
    function adfoin_eventsmanager_prepare_fields( $field_data, $posted_data ) {
        $prepared = array();

        if ( empty( $field_data ) || ! is_array( $field_data ) ) {
            return $prepared;
        }

        foreach ( $field_data as $key => $value ) {
            if ( '' === $key || null === $value ) {
                continue;
            }

            $parsed = adfoin_get_parsed_values( $value, $posted_data );

            if ( is_string( $parsed ) ) {
                $prepared[ $key ] = html_entity_decode( $parsed, ENT_QUOTES, get_bloginfo( 'charset' ) );
            } else {
                $prepared[ $key ] = $parsed;
            }
        }

        return $prepared;
    }
}

if ( ! function_exists( 'adfoin_eventsmanager_create_event' ) ) {
    /**
     * Create a new Events Manager event.
     *
     * @param array $fields Parsed values.
     *
     * @return array
     */
    function adfoin_eventsmanager_create_event( $fields ) {
        if ( ! class_exists( 'EM_Event' ) ) {
            return array(
                'success' => false,
                'message' => __( 'Events Manager plugin is not active.', 'advanced-form-integration' ),
            );
        }

        $name = isset( $fields['eventName'] ) ? trim( wp_specialchars_decode( $fields['eventName'] ) ) : '';
        if ( '' === $name ) {
            return array(
                'success' => false,
                'message' => __( 'Event name is required.', 'advanced-form-integration' ),
            );
        }

        $start_date = adfoin_eventsmanager_normalize_date( isset( $fields['startDate'] ) ? $fields['startDate'] : '' );
        if ( ! $start_date ) {
            return array(
                'success' => false,
                'message' => __( 'A valid start date is required (YYYY-MM-DD).', 'advanced-form-integration' ),
            );
        }

        $end_date_input = isset( $fields['endDate'] ) ? $fields['endDate'] : '';
        $end_date       = adfoin_eventsmanager_normalize_date( $end_date_input, true );
        if ( false === $end_date ) {
            return array(
                'success' => false,
                'message' => __( 'End date must use the YYYY-MM-DD format.', 'advanced-form-integration' ),
            );
        }
        if ( '' === $end_date ) {
            $end_date = $start_date;
        }

        $all_day = adfoin_eventsmanager_to_bool( isset( $fields['allDay'] ) ? $fields['allDay'] : '' );

        $start_time = adfoin_eventsmanager_normalize_time(
            isset( $fields['startTime'] ) ? $fields['startTime'] : '',
            $all_day ? '00:00:00' : '00:00:00'
        );
        if ( false === $start_time ) {
            return array(
                'success' => false,
                'message' => __( 'Start time is invalid.', 'advanced-form-integration' ),
            );
        }

        $default_end_time = $all_day ? '23:59:59' : $start_time;
        $end_time         = adfoin_eventsmanager_normalize_time(
            isset( $fields['endTime'] ) ? $fields['endTime'] : '',
            $default_end_time
        );
        if ( false === $end_time ) {
            return array(
                'success' => false,
                'message' => __( 'End time is invalid.', 'advanced-form-integration' ),
            );
        }

        $timezone = isset( $fields['timezone'] ) ? trim( $fields['timezone'] ) : '';
        if ( '' === $timezone ) {
            $timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC';
        }

        $event = new EM_Event();
        $event->event_name     = wp_strip_all_tags( $name );
        $event->post_content   = isset( $fields['eventDescription'] ) ? wp_kses_post( $fields['eventDescription'] ) : '';
        $event->post_excerpt   = isset( $fields['eventExcerpt'] ) ? sanitize_text_field( $fields['eventExcerpt'] ) : '';
        $event->event_slug     = isset( $fields['slug'] ) ? sanitize_title( $fields['slug'] ) : '';
        $event->event_timezone = $timezone;
        $event->event_all_day  = $all_day ? 1 : 0;
        $event->event_start_date = $start_date;
        $event->event_end_date   = $end_date;
        $event->event_start_time = $start_time;
        $event->event_end_time   = $end_time;

        $owner_id = adfoin_eventsmanager_absint_or_null( isset( $fields['ownerId'] ) ? $fields['ownerId'] : '' );
        if ( $owner_id ) {
            $event->event_owner = $owner_id;
        } elseif ( get_current_user_id() ) {
            $event->event_owner = get_current_user_id();
        }

        $location_id = adfoin_eventsmanager_absint_or_null( isset( $fields['locationId'] ) ? $fields['locationId'] : '' );
        if ( $location_id ) {
            $event->location_id = $location_id;
        }

        $force_status = isset( $fields['forceStatus'] ) ? sanitize_key( $fields['forceStatus'] ) : '';
        if ( $force_status && in_array( $force_status, array( 'publish', 'draft', 'pending', 'private', 'future' ), true ) ) {
            $event->force_status = $force_status;
        }

        $event_status = isset( $fields['eventStatus'] ) ? trim( $fields['eventStatus'] ) : '';
        if ( '' !== $event_status && is_numeric( $event_status ) ) {
            $event->event_status = (int) $event_status;
        }

        $event->event_private = adfoin_eventsmanager_to_bool( isset( $fields['eventPrivate'] ) ? $fields['eventPrivate'] : '' ) ? 1 : 0;

        $rsvp_enabled = adfoin_eventsmanager_to_bool( isset( $fields['rsvpEnabled'] ) ? $fields['rsvpEnabled'] : '' );
        $event->event_rsvp = $rsvp_enabled ? 1 : 0;

        if ( $rsvp_enabled ) {
            $rsvp_date = adfoin_eventsmanager_normalize_date( isset( $fields['rsvpDate'] ) ? $fields['rsvpDate'] : '', true );
            if ( false === $rsvp_date ) {
                return array(
                    'success' => false,
                    'message' => __( 'RSVP date must use the YYYY-MM-DD format.', 'advanced-form-integration' ),
                );
            }
            if ( '' !== $rsvp_date ) {
                $event->event_rsvp_date = $rsvp_date;
            }

            $rsvp_time = adfoin_eventsmanager_normalize_time(
                isset( $fields['rsvpTime'] ) ? $fields['rsvpTime'] : '',
                $event->event_start_time
            );
            if ( false === $rsvp_time ) {
                return array(
                    'success' => false,
                    'message' => __( 'RSVP time is invalid.', 'advanced-form-integration' ),
                );
            }
            $event->event_rsvp_time    = $rsvp_time;
            $event->event_spaces       = adfoin_eventsmanager_absint_or_null( isset( $fields['totalSpaces'] ) ? $fields['totalSpaces'] : '' );
            $event->event_rsvp_spaces  = adfoin_eventsmanager_absint_or_null( isset( $fields['rsvpSpaces'] ) ? $fields['rsvpSpaces'] : '' );
        }

        $saved = $event->save();

        if ( ! $saved ) {
            $errors  = method_exists( $event, 'get_errors' ) ? $event->get_errors() : array();
            $message = __( 'Unable to create the event.', 'advanced-form-integration' );
            if ( empty( $errors ) && ! empty( $event->errors ) ) {
                $errors = (array) $event->errors;
            }
            if ( ! empty( $errors ) ) {
                $message = implode( ' ', array_map( 'wp_strip_all_tags', (array) $errors ) );
            }

            return array(
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            );
        }

        return array(
            'success'   => true,
            'message'   => __( 'Event created successfully.', 'advanced-form-integration' ),
            'entity_id' => method_exists( $event, 'get_event_id' ) ? $event->get_event_id() : $event->event_id,
            'post_id'   => isset( $event->post_id ) ? $event->post_id : 0,
        );
    }
}

if ( ! function_exists( 'adfoin_eventsmanager_normalize_date' ) ) {
    /**
     * Normalize a date string.
     *
     * @param string $value       Raw value.
     * @param bool   $allow_empty Allow empty string.
     *
     * @return string|false Null if invalid, empty string if allowed and blank.
     */
    function adfoin_eventsmanager_normalize_date( $value, $allow_empty = false ) {
        $value = is_string( $value ) ? trim( $value ) : '';

        if ( '' === $value ) {
            return $allow_empty ? '' : null;
        }

        $date = DateTime::createFromFormat( 'Y-m-d', $value );
        if ( $date && $date->format( 'Y-m-d' ) === $value ) {
            return $value;
        }

        $timestamp = strtotime( $value );
        if ( false === $timestamp ) {
            return false;
        }

        return gmdate( 'Y-m-d', $timestamp );
    }
}

if ( ! function_exists( 'adfoin_eventsmanager_normalize_time' ) ) {
    /**
     * Normalize a time string to HH:MM:SS.
     *
     * @param string $value   Raw value.
     * @param string $default Default time when empty.
     *
     * @return string|false Normalized time or false if invalid.
     */
    function adfoin_eventsmanager_normalize_time( $value, $default = '00:00:00' ) {
        $value = is_string( $value ) ? trim( $value ) : '';

        if ( '' === $value ) {
            return $default;
        }

        $timestamp = strtotime( '1970-01-01 ' . $value );
        if ( false === $timestamp ) {
            return false;
        }

        return gmdate( 'H:i:s', $timestamp );
    }
}

if ( ! function_exists( 'adfoin_eventsmanager_to_bool' ) ) {
    /**
     * Convert a string-ish value to boolean.
     *
     * @param mixed $value Source value.
     *
     * @return bool
     */
    function adfoin_eventsmanager_to_bool( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( is_numeric( $value ) ) {
            return (int) $value !== 0;
        }

        $value = strtolower( trim( (string) $value ) );

        return in_array( $value, array( '1', 'true', 'yes', 'on', 'enabled' ), true );
    }
}

if ( ! function_exists( 'adfoin_eventsmanager_absint_or_null' ) ) {
    /**
     * Convert a value to absint or return null.
     *
     * @param mixed $value Source value.
     *
     * @return int|null
     */
    function adfoin_eventsmanager_absint_or_null( $value ) {
        if ( is_numeric( $value ) ) {
            return absint( $value );
        }

        $value = trim( (string) $value );
        if ( '' === $value ) {
            return null;
        }

        return ctype_digit( $value ) ? absint( $value ) : null;
    }
}
