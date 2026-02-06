<?php

add_filter( 'adfoin_action_providers', 'adfoin_fluentbooking_actions', 10, 1 );

/**
 * Register Fluent Booking as an action provider.
 *
 * @param array $actions Existing providers.
 *
 * @return array
 */
function adfoin_fluentbooking_actions( $actions ) {
    $actions['fluentbooking'] = array(
        'title' => __( 'Fluent Booking', 'advanced-form-integration' ),
        'tasks' => array(
            'create_booking' => __( 'Create Booking', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_fluentbooking_action_fields' );

/**
 * Render the Fluent Booking action template.
 *
 * @return void
 */
function adfoin_fluentbooking_action_fields() {
    ?>
    <script type="text/template" id="fluentbooking-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_booking'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p class="description"><?php esc_html_e( 'Create a booking for an existing Fluent Booking event. Provide the calendar event ID along with the attendee timezone and start time.', 'advanced-form-integration' ); ?></p>
                    <p class="description"><?php esc_html_e( 'You may supply either a UTC start time or a local start time combined with the attendee timezone.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_fluentbooking_job_queue', 'adfoin_fluentbooking_job_queue', 10, 1 );

/**
 * Process queued Fluent Booking jobs.
 *
 * @param array $data Queue payload.
 *
 * @return void
 */
function adfoin_fluentbooking_job_queue( $data ) {
    adfoin_fluentbooking_send_data( $data['record'], $data['posted_data'] );
}

if ( ! function_exists( 'adfoin_fluentbooking_send_data' ) ) {
    /**
     * Dispatch Fluent Booking actions.
     *
     * @param array $record      Integration record.
     * @param array $posted_data Trigger payload.
     *
     * @return void
     */
    function adfoin_fluentbooking_send_data( $record, $posted_data ) {
        $record_data = json_decode( $record['data'], true );
        $cl          = isset( $record_data['action_data']['cl'] ) ? $record_data['action_data']['cl'] : array();

        if ( adfoin_check_conditional_logic( $cl, $posted_data ) ) {
            return;
        }

        $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
        $fields     = adfoin_fluentbooking_prepare_fields( $field_data, $posted_data );
        $task       = isset( $record['task'] ) ? $record['task'] : '';

        $result = array(
            'success' => false,
            'message' => __( 'Unsupported task.', 'advanced-form-integration' ),
        );

        if ( 'create_booking' === $task ) {
            $result = adfoin_fluentbooking_create_booking( $fields );
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

        adfoin_add_to_log( $log_response, 'fluentbooking', $log_args, $record );
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_prepare_fields' ) ) {
    /**
     * Prepare field mappings.
     *
     * @param array $field_data  Saved mapping.
     * @param array $posted_data Trigger payload.
     *
     * @return array
     */
    function adfoin_fluentbooking_prepare_fields( $field_data, $posted_data ) {
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

if ( ! function_exists( 'adfoin_fluentbooking_create_booking' ) ) {
    /**
     * Create a Fluent Booking booking.
     *
     * @param array $fields Parsed values.
     *
     * @return array
     */
    function adfoin_fluentbooking_create_booking( $fields ) {
        if ( ! class_exists( '\FluentBooking\App\Services\BookingService' ) || ! class_exists( '\FluentBooking\App\Models\CalendarSlot' ) ) {
            return array(
                'success' => false,
                'message' => __( 'Fluent Booking plugin is not active.', 'advanced-form-integration' ),
            );
        }

        $event_id = isset( $fields['eventId'] ) ? adfoin_fluentbooking_absint( $fields['eventId'] ) : 0;
        if ( ! $event_id ) {
            return array(
                'success' => false,
                'message' => __( 'Calendar event ID is required.', 'advanced-form-integration' ),
            );
        }

        $email = isset( $fields['email'] ) ? sanitize_email( $fields['email'] ) : '';
        if ( ! $email || ! is_email( $email ) ) {
            return array(
                'success' => false,
                'message' => __( 'A valid attendee email address is required.', 'advanced-form-integration' ),
            );
        }

        $timezone = isset( $fields['personTimeZone'] ) ? sanitize_text_field( $fields['personTimeZone'] ) : '';
        if ( '' === $timezone ) {
            return array(
                'success' => false,
                'message' => __( 'The attendee timezone is required.', 'advanced-form-integration' ),
            );
        }

        $calendar_slot = \FluentBooking\App\Models\CalendarSlot::find( $event_id );
        if ( ! $calendar_slot ) {
            return array(
                'success' => false,
                'message' => __( 'Calendar event not found.', 'advanced-form-integration' ),
            );
        }

        $start_time_utc = adfoin_fluentbooking_resolve_start_time( $fields, $timezone );
        if ( is_wp_error( $start_time_utc ) ) {
            return array(
                'success' => false,
                'message' => $start_time_utc->get_error_message(),
            );
        }

        $duration = isset( $fields['durationMinutes'] ) ? absint( $fields['durationMinutes'] ) : 0;
        if ( ! $duration ) {
            $duration = absint( $calendar_slot->duration );
        }
        if ( ! $duration ) {
            $duration = 30;
        }

        $end_time_utc = adfoin_fluentbooking_resolve_end_time( $fields, $start_time_utc, $timezone, $duration );
        if ( is_wp_error( $end_time_utc ) ) {
            return array(
                'success' => false,
                'message' => $end_time_utc->get_error_message(),
            );
        }

        $status = isset( $fields['status'] ) ? sanitize_key( $fields['status'] ) : 'scheduled';
        if ( ! $status ) {
            $status = 'scheduled';
        }

        $booking_data = array(
            'event_id'         => $calendar_slot->id,
            'calendar_id'      => $calendar_slot->calendar_id,
            'host_user_id'     => isset( $fields['hostUserId'] ) ? adfoin_fluentbooking_absint( $fields['hostUserId'] ) : $calendar_slot->user_id,
            'person_time_zone' => $timezone,
            'start_time'       => $start_time_utc,
            'end_time'         => $end_time_utc,
            'slot_minutes'     => $duration,
            'email'            => $email,
            'status'           => $status,
            'phone'            => isset( $fields['phone'] ) ? sanitize_text_field( $fields['phone'] ) : '',
            'message'          => isset( $fields['message'] ) ? sanitize_textarea_field( $fields['message'] ) : '',
            'country'          => isset( $fields['country'] ) ? sanitize_text_field( $fields['country'] ) : '',
            'ip_address'       => isset( $fields['ipAddress'] ) ? sanitize_text_field( $fields['ipAddress'] ) : adfoin_fluentbooking_current_ip(),
            'payment_method'   => isset( $fields['paymentMethod'] ) ? sanitize_text_field( $fields['paymentMethod'] ) : '',
            'payment_status'   => isset( $fields['paymentStatus'] ) ? sanitize_text_field( $fields['paymentStatus'] ) : '',
            'source'           => isset( $fields['source'] ) ? sanitize_text_field( $fields['source'] ) : 'advanced-form-integration',
            'source_url'       => isset( $fields['sourceUrl'] ) ? esc_url_raw( $fields['sourceUrl'] ) : '',
            'utm_source'       => isset( $fields['utmSource'] ) ? sanitize_text_field( $fields['utmSource'] ) : '',
            'utm_medium'       => isset( $fields['utmMedium'] ) ? sanitize_text_field( $fields['utmMedium'] ) : '',
            'utm_campaign'     => isset( $fields['utmCampaign'] ) ? sanitize_text_field( $fields['utmCampaign'] ) : '',
            'utm_term'         => isset( $fields['utmTerm'] ) ? sanitize_text_field( $fields['utmTerm'] ) : '',
            'utm_content'      => isset( $fields['utmContent'] ) ? sanitize_text_field( $fields['utmContent'] ) : '',
            'browser'          => isset( $fields['browser'] ) ? sanitize_text_field( $fields['browser'] ) : '',
            'device'           => isset( $fields['device'] ) ? sanitize_text_field( $fields['device'] ) : '',
            'person_user_id'   => isset( $fields['personUserId'] ) ? adfoin_fluentbooking_absint( $fields['personUserId'] ) : null,
            'person_contact_id'=> isset( $fields['personContactId'] ) ? adfoin_fluentbooking_absint( $fields['personContactId'] ) : null,
            'event_type'       => isset( $fields['eventType'] ) ? sanitize_text_field( $fields['eventType'] ) : $calendar_slot->event_type,
        );

        $name = isset( $fields['name'] ) ? sanitize_text_field( $fields['name'] ) : '';
        if ( $name ) {
            $booking_data['name'] = $name;
        }

        if ( isset( $fields['firstName'] ) ) {
            $booking_data['first_name'] = sanitize_text_field( $fields['firstName'] );
        }

        if ( isset( $fields['lastName'] ) ) {
            $booking_data['last_name'] = sanitize_text_field( $fields['lastName'] );
        }

        if ( ! empty( $fields['phone'] ) ) {
            $booking_data['phone'] = sanitize_text_field( $fields['phone'] );
        }

        if ( ! empty( $fields['locationType'] ) ) {
            $location_details = array(
                'type' => sanitize_key( $fields['locationType'] ),
            );

            if ( ! empty( $fields['locationDescription'] ) ) {
                $location_details['description'] = sanitize_text_field( $fields['locationDescription'] );
            }

            if ( ! empty( $fields['locationUrl'] ) ) {
                $location_details['online_platform_link'] = esc_url_raw( $fields['locationUrl'] );
            }

            $booking_data['location_details'] = $location_details;
        }

        $additional_guests = adfoin_fluentbooking_parse_guests( $fields );
        if ( is_wp_error( $additional_guests ) ) {
            return array(
                'success' => false,
                'message' => $additional_guests->get_error_message(),
            );
        }

        if ( $additional_guests ) {
            $booking_data['additional_guests'] = $additional_guests;
        }

        $custom_fields_data = adfoin_fluentbooking_prepare_custom_fields( $fields, $calendar_slot );
        if ( is_wp_error( $custom_fields_data ) ) {
            return array(
                'success' => false,
                'message' => $custom_fields_data->get_error_message(),
                'errors'  => $custom_fields_data->get_error_data(),
            );
        }

        try {
            $booking = \FluentBooking\App\Services\BookingService::createBooking(
                $booking_data,
                $calendar_slot,
                $custom_fields_data
            );

            if ( is_wp_error( $booking ) ) {
                return array(
                    'success' => false,
                    'message' => $booking->get_error_message(),
                );
            }

            return array(
                'success'    => true,
                'message'    => __( 'Booking created successfully.', 'advanced-form-integration' ),
                'booking_id' => isset( $booking->id ) ? (int) $booking->id : 0,
                'calendar_id'=> isset( $booking->calendar_id ) ? (int) $booking->calendar_id : 0,
                'status'     => isset( $booking->status ) ? $booking->status : '',
                'hash'       => isset( $booking->hash ) ? $booking->hash : '',
                'start_time' => isset( $booking->start_time ) ? $booking->start_time : '',
                'end_time'   => isset( $booking->end_time ) ? $booking->end_time : '',
            );

        } catch ( \Exception $exception ) {
            return array(
                'success' => false,
                'message' => $exception->getMessage(),
            );
        }
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_prepare_custom_fields' ) ) {
    /**
     * Prepare custom booking field data.
     *
     * @param array                                  $fields         Field values.
     * @param \FluentBooking\App\Models\CalendarSlot $calendar_slot  Slot instance.
     *
     * @return array|\WP_Error
     */
    function adfoin_fluentbooking_prepare_custom_fields( $fields, $calendar_slot ) {
        if ( empty( $fields['customFieldsJson'] ) ) {
            return array();
        }

        $decoded = json_decode( $fields['customFieldsJson'], true );

        if ( null === $decoded || ! is_array( $decoded ) ) {
            return new \WP_Error(
                'invalid_custom_fields',
                __( 'Custom fields JSON must decode to an associative array.', 'advanced-form-integration' )
            );
        }

        return \FluentBooking\App\Services\BookingFieldService::getCustomFieldsData( $decoded, $calendar_slot );
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_parse_guests' ) ) {
    /**
     * Prepare additional guests data.
     *
     * @param array $fields Field data.
     *
     * @return array|\WP_Error
     */
    function adfoin_fluentbooking_parse_guests( $fields ) {
        if ( ! empty( $fields['additionalGuestsJson'] ) ) {
            $decoded = json_decode( $fields['additionalGuestsJson'], true );
            if ( null === $decoded || ! is_array( $decoded ) ) {
                return new \WP_Error(
                    'invalid_additional_guests',
                    __( 'Additional guests JSON must decode to an array.', 'advanced-form-integration' )
                );
            }

            $sanitized = array();
            foreach ( $decoded as $guest ) {
                if ( ! is_array( $guest ) ) {
                    continue;
                }
                $name  = isset( $guest['name'] ) ? sanitize_text_field( $guest['name'] ) : '';
                $email = isset( $guest['email'] ) ? sanitize_email( $guest['email'] ) : '';
                if ( $email ) {
                    $sanitized[] = array_filter(
                        array(
                            'name'  => $name,
                            'email' => $email,
                        )
                    );
                }
            }

            return $sanitized;
        }

        if ( empty( $fields['additionalGuests'] ) ) {
            return array();
        }

        $raw     = preg_split( '/[\r\n,]+/', $fields['additionalGuests'] );
        $emails  = array();

        foreach ( $raw as $item ) {
            $email = sanitize_email( trim( $item ) );
            if ( $email ) {
                $emails[] = $email;
            }
        }

        return $emails;
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_resolve_start_time' ) ) {
    /**
     * Resolve the booking start time in UTC.
     *
     * @param array  $fields   Field values.
     * @param string $timezone Attendee timezone.
     *
     * @return string|\WP_Error
     */
    function adfoin_fluentbooking_resolve_start_time( $fields, $timezone ) {
        if ( ! empty( $fields['startTimeUtc'] ) ) {
            $timestamp = adfoin_fluentbooking_parse_datetime( $fields['startTimeUtc'], 'UTC' );
            if ( is_wp_error( $timestamp ) ) {
                return $timestamp;
            }

            return gmdate( 'Y-m-d H:i:s', $timestamp );
        }

        if ( ! empty( $fields['startTime'] ) ) {
            $timestamp = adfoin_fluentbooking_parse_datetime( $fields['startTime'], $timezone );
            if ( is_wp_error( $timestamp ) ) {
                return $timestamp;
            }

            return gmdate( 'Y-m-d H:i:s', $timestamp );
        }

        return new \WP_Error(
            'missing_start_time',
            __( 'Provide either a UTC start time or a local start time.', 'advanced-form-integration' )
        );
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_resolve_end_time' ) ) {
    /**
     * Resolve the booking end time in UTC.
     *
     * @param array  $fields        Field values.
     * @param string $start_time    UTC start time.
     * @param string $timezone      Attendee timezone.
     * @param int    $duration      Slot duration in minutes.
     *
     * @return string|\WP_Error
     */
    function adfoin_fluentbooking_resolve_end_time( $fields, $start_time, $timezone, $duration ) {
        if ( ! empty( $fields['endTimeUtc'] ) ) {
            $timestamp = adfoin_fluentbooking_parse_datetime( $fields['endTimeUtc'], 'UTC' );
            if ( is_wp_error( $timestamp ) ) {
                return $timestamp;
            }

            return gmdate( 'Y-m-d H:i:s', $timestamp );
        }

        if ( ! empty( $fields['endTime'] ) ) {
            $timestamp = adfoin_fluentbooking_parse_datetime( $fields['endTime'], $timezone );
            if ( is_wp_error( $timestamp ) ) {
                return $timestamp;
            }

            return gmdate( 'Y-m-d H:i:s', $timestamp );
        }

        $start_timestamp = strtotime( $start_time );
        if ( false === $start_timestamp ) {
            return new \WP_Error(
                'invalid_start_time',
                __( 'Unable to parse the start time.', 'advanced-form-integration' )
            );
        }

        return gmdate( 'Y-m-d H:i:s', $start_timestamp + ( $duration * MINUTE_IN_SECONDS ) );
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_parse_datetime' ) ) {
    /**
     * Parse a datetime string.
     *
     * @param string $value    Input value.
     * @param string $timezone Timezone identifier.
     *
     * @return int|\WP_Error
     */
    function adfoin_fluentbooking_parse_datetime( $value, $timezone ) {
        try {
            $tz = new \DateTimeZone( $timezone );
        } catch ( \Exception $exception ) {
            return new \WP_Error(
                'invalid_timezone',
                __( 'The supplied timezone is invalid.', 'advanced-form-integration' )
            );
        }

        try {
            $date = new \DateTime( trim( $value ), $tz );
        } catch ( \Exception $exception ) {
            return new \WP_Error(
                'invalid_datetime',
                __( 'Unable to parse the supplied date/time value.', 'advanced-form-integration' )
            );
        }

        return $date->getTimestamp();
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_absint' ) ) {
    /**
     * Convert values to absint.
     *
     * @param mixed $value Value.
     *
     * @return int
     */
    function adfoin_fluentbooking_absint( $value ) {
        return absint( $value );
    }
}

if ( ! function_exists( 'adfoin_fluentbooking_current_ip' ) ) {
    /**
     * Retrieve the current visitor IP.
     *
     * @return string
     */
    function adfoin_fluentbooking_current_ip() {
        if ( function_exists( '\FluentBooking\App\Services\Helper::getIp' ) ) {
            return \FluentBooking\App\Services\Helper::getIp();
        }

        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    }
}
