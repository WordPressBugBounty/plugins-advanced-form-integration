<?php

add_filter( 'adfoin_action_providers', 'adfoin_theeventscalendar_actions', 10, 1 );

function adfoin_theeventscalendar_actions( $actions ) {
    $actions['theeventscalendar'] = array(
        'title' => __( 'The Events Calendar', 'advanced-form-integration' ),
        'tasks' => array(
            'create_event' => __( 'Create Event', 'advanced-form-integration' ),
            'update_event' => __( 'Update Event', 'advanced-form-integration' ),
            'delete_event' => __( 'Delete Event', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_theeventscalendar_action_fields' );

function adfoin_theeventscalendar_action_fields() {
    ?>
    <script type="text/template" id="theeventscalendar-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_event'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'At minimum provide a title and start/end information. Row JSON values set meta such as venue or category IDs. Datetimes should use the site timezone unless a specific timezone is supplied.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_event'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Event ID is required. Only supplied fields are changed; omit fields to keep existing values.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'delete_event'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the Event ID to trash permanently.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_theeventscalendar_job_queue', 'adfoin_theeventscalendar_job_queue', 10, 1 );

function adfoin_theeventscalendar_job_queue( $data ) {
    adfoin_theeventscalendar_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_theeventscalendar_plugin_ready() {
    return class_exists( 'Tribe__Events__Main' );
}

function adfoin_theeventscalendar_send_data( $record, $posted_data ) {
    if ( ! adfoin_theeventscalendar_plugin_ready() ) {
        adfoin_theeventscalendar_action_log( $record, __( 'The Events Calendar is not active.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $parsed = array();

    if ( is_array( $field_data ) ) {
        foreach ( $field_data as $key => $value ) {
            $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
        }
    }

    switch ( $task ) {
        case 'create_event':
            adfoin_theeventscalendar_action_create_event( $record, $parsed );
            break;
        case 'update_event':
            adfoin_theeventscalendar_action_update_event( $record, $parsed );
            break;
        case 'delete_event':
            adfoin_theeventscalendar_action_delete_event( $record, $parsed );
            break;
        default:
            adfoin_theeventscalendar_action_log(
                $record,
                __( 'Unknown The Events Calendar task received.', 'advanced-form-integration' ),
                array( 'task' => $task ),
                false
            );
            break;
    }
}

function adfoin_theeventscalendar_action_create_event( $record, $parsed ) {
    $title = isset( $parsed['title'] ) ? sanitize_text_field( $parsed['title'] ) : '';

    if ( '' === $title ) {
        adfoin_theeventscalendar_action_log(
            $record,
            __( 'Event title is required.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $author_id = isset( $parsed['author_id'] ) && '' !== $parsed['author_id'] ? absint( $parsed['author_id'] ) : get_current_user_id();
    if ( $author_id && ! get_user_by( 'id', $author_id ) ) {
        $author_id = get_current_user_id();
    }

    $postarr = array(
        'post_type'    => 'tribe_events',
        'post_title'   => $title,
        'post_status'  => adfoin_theeventscalendar_sanitise_status( $parsed['status'] ?? 'publish' ),
        'post_content' => isset( $parsed['content'] ) ? wp_kses_post( $parsed['content'] ) : '',
        'post_excerpt' => isset( $parsed['excerpt'] ) ? sanitize_textarea_field( $parsed['excerpt'] ) : '',
    );

    if ( $author_id ) {
        $postarr['post_author'] = $author_id;
    }

    $event_id = wp_insert_post( $postarr, true );

    if ( is_wp_error( $event_id ) ) {
        adfoin_theeventscalendar_action_log(
            $record,
            __( 'Failed to create event.', 'advanced-form-integration' ),
            array(
                'error' => $event_id->get_error_message(),
            ),
            false
        );
        return;
    }

    adfoin_theeventscalendar_sync_event_meta( $event_id, $parsed );
    adfoin_theeventscalendar_assign_terms( $event_id, $parsed );
    adfoin_theeventscalendar_apply_meta_json( $event_id, $parsed );

    adfoin_theeventscalendar_action_log(
        $record,
        __( 'Event created successfully.', 'advanced-form-integration' ),
        array(
            'event_id' => $event_id,
        ),
        true
    );
}

function adfoin_theeventscalendar_action_update_event( $record, $parsed ) {
    $event_id = isset( $parsed['event_id'] ) ? absint( $parsed['event_id'] ) : 0;

    if ( ! $event_id || 'tribe_events' !== get_post_type( $event_id ) ) {
        adfoin_theeventscalendar_action_log(
            $record,
            __( 'Valid event ID is required for update.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    $update = array(
        'ID' => $event_id,
    );

    if ( isset( $parsed['title'] ) && '' !== $parsed['title'] ) {
        $update['post_title'] = sanitize_text_field( $parsed['title'] );
    }
    if ( isset( $parsed['content'] ) ) {
        $update['post_content'] = wp_kses_post( $parsed['content'] );
    }
    if ( isset( $parsed['excerpt'] ) ) {
        $update['post_excerpt'] = sanitize_textarea_field( $parsed['excerpt'] );
    }
    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        $update['post_status'] = adfoin_theeventscalendar_sanitise_status( $parsed['status'] );
    }
    if ( isset( $parsed['author_id'] ) && '' !== $parsed['author_id'] ) {
        $author_id = absint( $parsed['author_id'] );
        if ( $author_id && get_user_by( 'id', $author_id ) ) {
            $update['post_author'] = $author_id;
        }
    }

    if ( count( $update ) > 1 ) {
        $result = wp_update_post( $update, true );
        if ( is_wp_error( $result ) ) {
            adfoin_theeventscalendar_action_log(
                $record,
                __( 'Failed to update event.', 'advanced-form-integration' ),
                array(
                    'event_id' => $event_id,
                    'error'    => $result->get_error_message(),
                ),
                false
            );
            return;
        }
    }

    adfoin_theeventscalendar_sync_event_meta( $event_id, $parsed );
    adfoin_theeventscalendar_assign_terms( $event_id, $parsed );
    adfoin_theeventscalendar_apply_meta_json( $event_id, $parsed );

    adfoin_theeventscalendar_action_log(
        $record,
        __( 'Event updated successfully.', 'advanced-form-integration' ),
        array(
            'event_id' => $event_id,
        ),
        true
    );
}

function adfoin_theeventscalendar_action_delete_event( $record, $parsed ) {
    $event_id = isset( $parsed['event_id'] ) ? absint( $parsed['event_id'] ) : 0;

    if ( ! $event_id || 'tribe_events' !== get_post_type( $event_id ) ) {
        adfoin_theeventscalendar_action_log(
            $record,
            __( 'Valid event ID is required for deletion.', 'advanced-form-integration' ),
            $parsed,
            false
        );
        return;
    }

    wp_delete_post( $event_id, true );

    adfoin_theeventscalendar_action_log(
        $record,
        __( 'Event deleted successfully.', 'advanced-form-integration' ),
        array(
            'event_id' => $event_id,
        ),
        true
    );
}

function adfoin_theeventscalendar_sync_event_meta( $event_id, $parsed ) {
    $all_day = isset( $parsed['all_day'] ) ? adfoin_theeventscalendar_to_bool( $parsed['all_day'] ) : false;
    $timezone = adfoin_theeventscalendar_resolve_timezone( $parsed['timezone'] ?? '' );

    $start = adfoin_theeventscalendar_parse_event_datetime(
        $parsed['start_date'] ?? '',
        $parsed['start_time'] ?? '',
        $timezone,
        $all_day,
        'start'
    );

    $end = adfoin_theeventscalendar_parse_event_datetime(
        $parsed['end_date'] ?? '',
        $parsed['end_time'] ?? '',
        $timezone,
        $all_day,
        'end'
    );

    if ( $start && $end && $end['local'] < $start['local'] ) {
        $end = $start;
    }

    if ( $start ) {
        update_post_meta( $event_id, '_EventStartDate', $start['local'] );
        update_post_meta( $event_id, '_EventStartDateUTC', $start['utc'] );
    }

    if ( $end ) {
        update_post_meta( $event_id, '_EventEndDate', $end['local'] );
        update_post_meta( $event_id, '_EventEndDateUTC', $end['utc'] );
    }

    update_post_meta( $event_id, '_EventAllDay', $all_day ? 'yes' : 'no' );
    update_post_meta( $event_id, '_EventTimezone', $timezone );
    update_post_meta( $event_id, '_EventTimezoneAbbr', adfoin_theeventscalendar_get_timezone_abbr( $timezone, $start ? $start['local'] : '' ) );

    if ( isset( $parsed['venue_id'] ) && '' !== $parsed['venue_id'] ) {
        update_post_meta( $event_id, '_EventVenueID', absint( $parsed['venue_id'] ) );
    }
    if ( isset( $parsed['organizer_id'] ) && '' !== $parsed['organizer_id'] ) {
        update_post_meta( $event_id, '_EventOrganizerID', absint( $parsed['organizer_id'] ) );
    }
    if ( isset( $parsed['cost'] ) && '' !== $parsed['cost'] ) {
        update_post_meta( $event_id, '_EventCost', sanitize_text_field( $parsed['cost'] ) );
    }
    if ( isset( $parsed['featured'] ) && '' !== $parsed['featured'] ) {
        update_post_meta( $event_id, '_tribe_featured_event', adfoin_theeventscalendar_to_bool( $parsed['featured'] ) ? 1 : 0 );
    }
    if ( isset( $parsed['website_url'] ) && '' !== $parsed['website_url'] ) {
        update_post_meta( $event_id, '_EventURL', esc_url_raw( $parsed['website_url'] ) );
    }
    if ( isset( $parsed['hide_from_list'] ) && '' !== $parsed['hide_from_list'] ) {
        update_post_meta( $event_id, '_EventHideFromUpcoming', adfoin_theeventscalendar_to_bool( $parsed['hide_from_list'] ) ? 1 : 0 );
    }
}

function adfoin_theeventscalendar_assign_terms( $event_id, $parsed ) {
    if ( isset( $parsed['category_ids'] ) && '' !== $parsed['category_ids'] ) {
        $ids = adfoin_theeventscalendar_parse_csv_integers( $parsed['category_ids'] );
        if ( ! empty( $ids ) ) {
            wp_set_object_terms( $event_id, $ids, 'tribe_events_cat', false );
        }
    }

    if ( isset( $parsed['category_slugs'] ) && '' !== $parsed['category_slugs'] ) {
        $slugs = adfoin_theeventscalendar_parse_csv_strings( $parsed['category_slugs'] );
        if ( ! empty( $slugs ) ) {
            wp_set_object_terms( $event_id, $slugs, 'tribe_events_cat', false );
        }
    }

    if ( isset( $parsed['tag_slugs'] ) && '' !== $parsed['tag_slugs'] ) {
        $tags = adfoin_theeventscalendar_parse_csv_strings( $parsed['tag_slugs'] );
        if ( ! empty( $tags ) ) {
            wp_set_object_terms( $event_id, $tags, 'tribe_events_tag', false );
        }
    }
}

function adfoin_theeventscalendar_apply_meta_json( $event_id, $parsed ) {
    if ( ! isset( $parsed['meta_json'] ) || '' === trim( (string) $parsed['meta_json'] ) ) {
        return;
    }

    $decoded = json_decode( $parsed['meta_json'], true );

    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
        return;
    }

    foreach ( $decoded as $meta_key => $meta_value ) {
        if ( '' === $meta_key ) {
            continue;
        }

        $sanitized_key = sanitize_key( $meta_key );
        $value         = is_array( $meta_value ) ? maybe_serialize( $meta_value ) : sanitize_text_field( (string) $meta_value );
        update_post_meta( $event_id, $sanitized_key, $value );
    }
}

function adfoin_theeventscalendar_parse_event_datetime( $date, $time, $timezone, $all_day, $context ) {
    $date = trim( (string) $date );
    $time = trim( (string) $time );

    if ( '' === $date ) {
        return null;
    }

    if ( $all_day ) {
        $time = ( 'end' === $context ) ? '23:59:59' : '00:00:00';
    } else {
        if ( '' === $time ) {
            $time = '00:00';
        }
        if ( strlen( $time ) === 5 ) {
            $time .= ':00';
        }
    }

    $format = 'Y-m-d H:i:s';
    try {
        $tz        = new DateTimeZone( $timezone );
        $date_time = DateTime::createFromFormat( $format, $date . ' ' . $time, $tz );
        if ( ! $date_time ) {
            $date_time = new DateTime( $date . ' ' . $time, $tz );
        }
    } catch ( Exception $e ) {
        return null;
    }

    $local = $date_time->format( 'Y-m-d H:i:s' );
    $utc   = clone $date_time;
    $utc->setTimezone( new DateTimeZone( 'UTC' ) );

    return array(
        'local' => $local,
        'utc'   => $utc->format( 'Y-m-d H:i:s' ),
    );
}

function adfoin_theeventscalendar_sanitise_status( $status ) {
    $status = sanitize_key( $status );
    $allowed = array( 'publish', 'draft', 'pending', 'private', 'future' );
    if ( in_array( $status, $allowed, true ) ) {
        return $status;
    }
    return 'publish';
}

function adfoin_theeventscalendar_resolve_timezone( $timezone ) {
    $timezone = trim( (string) $timezone );
    if ( '' !== $timezone && in_array( $timezone, timezone_identifiers_list(), true ) ) {
        return $timezone;
    }

    $site_timezone = get_option( 'timezone_string' );
    if ( $site_timezone ) {
        return $site_timezone;
    }

    $offset = get_option( 'gmt_offset', 0 );
    $hours  = (int) $offset;
    $minutes = abs( $offset - $hours ) * 60;
    $prefix = $offset >= 0 ? '+' : '-';
    return sprintf( 'UTC%s%02d:%02d', $prefix, abs( $hours ), $minutes );
}

function adfoin_theeventscalendar_get_timezone_abbr( $timezone, $local_datetime ) {
    if ( '' === $timezone ) {
        return '';
    }

    try {
        $tz  = new DateTimeZone( $timezone );
        $dt  = new DateTime( $local_datetime ?: 'now', $tz );
        return $dt->format( 'T' );
    } catch ( Exception $e ) {
        return '';
    }
}

function adfoin_theeventscalendar_to_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }
    $value = strtolower( trim( (string) $value ) );
    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function adfoin_theeventscalendar_parse_csv_integers( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return array();
    }

    $parts = array_map( 'trim', explode( ',', $value ) );
    $ids   = array();
    foreach ( $parts as $part ) {
        $int = absint( $part );
        if ( $int ) {
            $ids[] = $int;
        }
    }

    return $ids;
}

function adfoin_theeventscalendar_parse_csv_strings( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return array();
    }

    $parts = array_map( 'sanitize_title', explode( ',', $value ) );
    return array_filter( $parts );
}

function adfoin_theeventscalendar_action_log( $record, $message, $payload, $success ) {
    $log_response = array(
        'response' => array(
            'code'    => $success ? 200 : 400,
            'message' => $message,
        ),
        'body' => array(
            'success' => $success,
            'message' => $message,
        ),
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $payload,
    );

    adfoin_add_to_log( $log_response, 'theeventscalendar', $log_args, $record );
}

