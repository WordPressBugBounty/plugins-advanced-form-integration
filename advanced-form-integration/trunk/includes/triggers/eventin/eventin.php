<?php

// Get Eventin Triggers
function adfoin_eventin_get_forms( $form_provider ) {
    if ( $form_provider != 'eventin' ) {
        return;
    }

    $triggers = array(
        'eventCreated' => __( 'Event Created', 'advanced-form-integration' ),
        'eventUpdated' => __( 'Event Updated', 'advanced-form-integration' ),
        'eventDeleted' => __( 'Event Deleted', 'advanced-form-integration' ),
        'speakerCreated' => __( 'Speaker/Organizer Created', 'advanced-form-integration' ),
        'speakerUpdated' => __( 'Speaker/Organizer Updated', 'advanced-form-integration' ),
        'speakerDeleted' => __( 'Speaker/Organizer Deleted', 'advanced-form-integration' ),
        'attendeeUpdated' => __( 'Attendee Updated', 'advanced-form-integration' ),
        'attendeeDeleted' => __( 'Attendee Deleted', 'advanced-form-integration' ),
        'orderCreated' => __( 'Order Created', 'advanced-form-integration' ),
        'orderDeleted' => __( 'Order Deleted', 'advanced-form-integration' ),
        'scheduleDeleted' => __( 'Schedule Deleted', 'advanced-form-integration' ),
        'rsvpSubmitted' => __( 'RSVP Submitted', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Eventin Fields
function adfoin_eventin_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'eventin' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'eventCreated' || $form_id === 'eventUpdated' ) {
        $fields = [
            'title' => __( 'Event Title', 'advanced-form-integration' ),
            'start_date' => __( 'Start Date', 'advanced-form-integration' ),
            'end_date' => __( 'End Date', 'advanced-form-integration' ),
            'start_time' => __( 'Start Time', 'advanced-form-integration' ),
            'end_time' => __( 'End Time', 'advanced-form-integration' ),
            'event_type' => __( 'Event Type', 'advanced-form-integration' ),
            'address' => __( 'Address', 'advanced-form-integration' ),
            'integration' => __( 'Integration', 'advanced-form-integration' ),
            'custom_url' => __( 'Custom URL', 'advanced-form-integration' ),
            'timezone' => __( 'Timezone', 'advanced-form-integration' ),
        ];

        if ( $form_id === 'eventUpdated' ) {
            $fields['status'] = __( 'Event Status', 'advanced-form-integration' );
        }
    } elseif ( $form_id === 'speakerCreated' || $form_id === 'speakerUpdated' ) {
        $fields = [
            'name' => __( 'Name', 'advanced-form-integration' ),
            'category' => __( 'Category', 'advanced-form-integration' ),
            'designation' => __( 'Designation', 'advanced-form-integration' ),
            'email' => __( 'Email', 'advanced-form-integration' ),
            'summary' => __( 'Summary', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'orderCreated' ) {
        $fields = [
            'order_id' => __( 'Order ID', 'advanced-form-integration' ),
            'customer_name' => __( 'Customer Name', 'advanced-form-integration' ),
            'customer_email' => __( 'Customer Email', 'advanced-form-integration' ),
            'status' => __( 'Order Status', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'attendeeUpdated' ) {
        $fields = [
            'attendee_id' => __( 'Attendee ID', 'advanced-form-integration' ),
            'attendee_name' => __( 'Attendee Name', 'advanced-form-integration' ),
            'attendee_email' => __( 'Attendee Email', 'advanced-form-integration' ),
            'ticket_status' => __( 'Ticket Status', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'rsvpSubmitted' ) {
        $fields = [
            'rsvp_id'             => __( 'RSVP ID', 'advanced-form-integration' ),
            'parent_attendee_id'  => __( 'Parent RSVP ID', 'advanced-form-integration' ),
            'event_id'            => __( 'Event ID', 'advanced-form-integration' ),
            'event_title'         => __( 'Event Title', 'advanced-form-integration' ),
            'event_start_date'    => __( 'Event Start Date', 'advanced-form-integration' ),
            'event_end_date'      => __( 'Event End Date', 'advanced-form-integration' ),
            'event_start_time'    => __( 'Event Start Time', 'advanced-form-integration' ),
            'event_end_time'      => __( 'Event End Time', 'advanced-form-integration' ),
            'event_timezone'      => __( 'Event Timezone', 'advanced-form-integration' ),
            'attendee_name'       => __( 'Attendee Name', 'advanced-form-integration' ),
            'attendee_email'      => __( 'Attendee Email', 'advanced-form-integration' ),
            'phone'               => __( 'Phone', 'advanced-form-integration' ),
            'number_of_attendee'  => __( 'Number Of Attendees', 'advanced-form-integration' ),
            'status'              => __( 'RSVP Status', 'advanced-form-integration' ),
            'not_going_reason'    => __( 'Not Going Reason', 'advanced-form-integration' ),
            'submitted_on'        => __( 'Submitted On', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

add_action( 'eventin_order_created', 'adfoin_eventin_handle_order_created', 10, 1 );
// Handle Order Created
function adfoin_eventin_handle_order_created( $order ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'orderCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'order_id' => $order->get_id(),
        'customer_name' => $order->get_customer_name(),
        'customer_email' => $order->get_customer_email(),
        'status' => $order->get_status(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_attendee_updated', 'adfoin_eventin_handle_attendee_updated', 10, 2 );
// Handle Attendee Updated
function adfoin_eventin_handle_attendee_updated( $attendee, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'attendeeUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'attendee_id' => $attendee->get_id(),
        'attendee_name' => $attendee->get_name(),
        'attendee_email' => $attendee->get_email(),
        'ticket_status' => $attendee->get_ticket_status(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_event_created', 'adfoin_eventin_handle_event_created', 10, 2 );
// Handle Event Created
function adfoin_eventin_handle_event_created( $event, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'title' => $event->get_title(),
        'start_date' => $event->etn_start_date,
        'end_date' => $event->etn_end_date,
        'start_time' => $event->etn_start_time,
        'end_time' => $event->etn_end_time,
        'event_type' => $event->event_type,
        'address' => $event->get_address(),
        'integration' => $event->get_meeting_platform(),
        'custom_url' => $event->external_link,
        'timezone' => $event->get_timezone(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_event_updated', 'adfoin_eventin_handle_event_updated', 10, 2 );
// Handle Event Updated
function adfoin_eventin_handle_event_updated( $event, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'title' => $event->get_title(),
        'start_date' => $event->etn_start_date,
        'end_date' => $event->etn_end_date,
        'start_time' => $event->etn_start_time,
        'end_time' => $event->etn_end_time,
        'event_type' => $event->event_type,
        'address' => $event->get_address(),
        'integration' => $event->get_meeting_platform(),
        'custom_url' => $event->external_link,
        'timezone' => $event->get_timezone(),
        'status' => $event->get_status(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_event_deleted', 'adfoin_eventin_handle_event_deleted', 10, 1 );
// Handle Event Deleted
function adfoin_eventin_handle_event_deleted( $event_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'eventDeleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [ 'event_id' => $event_id ];
    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_speaker_created', 'adfoin_eventin_handle_speaker_created', 10, 2 );
// Handle Speaker Created
function adfoin_eventin_handle_speaker_created( $speaker, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'speakerCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'name' => $speaker->get_name(),
        'category' => $speaker->get_category(),
        'designation' => $speaker->get_designation(),
        'email' => $speaker->get_email(),
        'summary' => $speaker->get_summary(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'eventin_speaker_updated', 'adfoin_eventin_handle_speaker_updated', 10, 2 );
// Handle Speaker Updated
function adfoin_eventin_handle_speaker_updated( $speaker, $request ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'speakerUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = [
        'name' => $speaker->get_name(),
        'category' => $speaker->get_category(),
        'designation' => $speaker->get_designation(),
        'email' => $speaker->get_email(),
        'summary' => $speaker->get_summary(),
    ];

    $integration->send( $saved_records, $posted_data );
}

add_action( 'save_post_etn_rsvp', 'adfoin_eventin_handle_rsvp_submitted', 20, 3 );
add_action( 'updated_post_meta', 'adfoin_eventin_handle_rsvp_meta_updated', 20, 4 );

function adfoin_eventin_handle_rsvp_submitted( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    adfoin_eventin_queue_rsvp( $post_id );
}

function adfoin_eventin_handle_rsvp_meta_updated( $meta_id, $object_id, $meta_key, $meta_value ) {
    $relevant_keys = array(
        'event_id',
        'attendee_email',
        'phone',
        'etn_rsvp_value',
        'number_of_attendee',
        'rsvp_not_going_reason',
    );

    if ( ! in_array( $meta_key, $relevant_keys, true ) ) {
        return;
    }

    if ( 'etn_rsvp' !== get_post_type( $object_id ) ) {
        return;
    }

    adfoin_eventin_queue_rsvp( $object_id );
}

function adfoin_eventin_queue_rsvp( $post_id ) {
    if ( ! isset( $GLOBALS['adfoin_eventin_rsvp_queue'] ) || ! is_array( $GLOBALS['adfoin_eventin_rsvp_queue'] ) ) {
        $GLOBALS['adfoin_eventin_rsvp_queue'] = array();
    }

    $GLOBALS['adfoin_eventin_rsvp_queue'][ (int) $post_id ] = true;
}

function adfoin_eventin_build_rsvp_payload( $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post || 'etn_rsvp' !== $post->post_type ) {
        return false;
    }

    if ( 'trash' === $post->post_status ) {
        return false;
    }

    $meta_raw = get_post_meta( $post_id );
    $meta     = array();

    foreach ( $meta_raw as $key => $values ) {
        if ( 1 === count( $values ) ) {
            $meta[ $key ] = maybe_unserialize( $values[0] );
        } else {
            $meta[ $key ] = array_map( 'maybe_unserialize', $values );
        }
    }

    $event_id    = isset( $meta['event_id'] ) ? $meta['event_id'] : '';
    $parent_id   = wp_get_post_parent_id( $post_id );
    $email       = isset( $meta['attendee_email'] ) ? $meta['attendee_email'] : '';
    $phone       = isset( $meta['phone'] ) ? $meta['phone'] : '';
    $status      = isset( $meta['etn_rsvp_value'] ) ? $meta['etn_rsvp_value'] : '';
    $attendees   = isset( $meta['number_of_attendee'] ) ? $meta['number_of_attendee'] : '';
    $not_going   = isset( $meta['rsvp_not_going_reason'] ) ? $meta['rsvp_not_going_reason'] : '';

    if ( $parent_id ) {
        $parent_meta = get_post_meta( $parent_id );

        if ( '' === $email && isset( $parent_meta['attendee_email'][0] ) ) {
            $email = maybe_unserialize( $parent_meta['attendee_email'][0] );
        }
        if ( '' === $phone && isset( $parent_meta['phone'][0] ) ) {
            $phone = maybe_unserialize( $parent_meta['phone'][0] );
        }
        if ( '' === $status && isset( $parent_meta['etn_rsvp_value'][0] ) ) {
            $status = maybe_unserialize( $parent_meta['etn_rsvp_value'][0] );
        }
        if ( '' === $attendees && isset( $parent_meta['number_of_attendee'][0] ) ) {
            $attendees = maybe_unserialize( $parent_meta['number_of_attendee'][0] );
        }
        if ( '' === $not_going && isset( $parent_meta['rsvp_not_going_reason'][0] ) ) {
            $not_going = maybe_unserialize( $parent_meta['rsvp_not_going_reason'][0] );
        }
        if ( empty( $event_id ) && isset( $parent_meta['event_id'][0] ) ) {
            $event_id = maybe_unserialize( $parent_meta['event_id'][0] );
        }
    }

    if ( is_array( $meta ) ) {
        foreach ( $meta as $key => $value ) {
            if ( is_array( $value ) && 1 === count( $value ) ) {
                $meta[ $key ] = $value[0];
            }
        }
    }

    $meta['event_id']           = $event_id;
    $meta['attendee_email']     = $email;
    $meta['phone']              = $phone;
    $meta['etn_rsvp_value']     = $status;
    $meta['number_of_attendee'] = $attendees;
    $meta['rsvp_not_going_reason'] = $not_going;

    $event_title      = '';
    $event_start_date = '';
    $event_end_date   = '';
    $event_start_time = '';
    $event_end_time   = '';
    $event_timezone   = '';

    if ( $event_id ) {
        if ( class_exists( '\\Etn\\Core\\Event\\Event_Model' ) ) {
            try {
                $event_model      = new \Etn\Core\Event\Event_Model( $event_id );
                $event_title      = method_exists( $event_model, 'get_title' ) ? $event_model->get_title() : get_the_title( $event_id );
                $event_start_date = isset( $event_model->etn_start_date ) ? $event_model->etn_start_date : '';
                $event_end_date   = isset( $event_model->etn_end_date ) ? $event_model->etn_end_date : '';
                $event_start_time = isset( $event_model->etn_start_time ) ? $event_model->etn_start_time : '';
                $event_end_time   = isset( $event_model->etn_end_time ) ? $event_model->etn_end_time : '';
                $event_timezone   = isset( $event_model->event_timezone ) ? $event_model->event_timezone : '';
            } catch ( \Throwable $th ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Fall back to direct meta fetches when the model fails.
            }
        }

        if ( '' === $event_title ) {
            $event_title = get_the_title( $event_id );
        }

        if ( '' === $event_start_date ) {
            $event_start_date = get_post_meta( $event_id, 'etn_start_date', true );
        }

        if ( '' === $event_end_date ) {
            $event_end_date = get_post_meta( $event_id, 'etn_end_date', true );
        }

        if ( '' === $event_start_time ) {
            $event_start_time = get_post_meta( $event_id, 'etn_start_time', true );
        }

        if ( '' === $event_end_time ) {
            $event_end_time = get_post_meta( $event_id, 'etn_end_time', true );
        }

        if ( '' === $event_timezone ) {
            $event_timezone = get_post_meta( $event_id, 'event_timezone', true );
        }
    }

    $posted_data = [
        'rsvp_id'            => $post_id,
        'parent_attendee_id' => $parent_id ? $parent_id : '',
        'event_id'           => $event_id,
        'event_title'        => $event_title,
        'event_start_date'   => $event_start_date,
        'event_end_date'     => $event_end_date,
        'event_start_time'   => $event_start_time,
        'event_end_time'     => $event_end_time,
        'event_timezone'     => $event_timezone,
        'attendee_name'      => $post->post_title,
        'attendee_email'     => $email,
        'phone'              => $phone,
        'number_of_attendee' => $attendees,
        'status'             => $status,
        'not_going_reason'   => $not_going,
        'submitted_on'       => get_post_time( 'Y-m-d H:i:s', true, $post ),
    ];

    $posted_data['meta'] = $meta;

    return $posted_data;
}

function adfoin_eventin_dispatch_rsvp( $post_id ) {
    $payload = adfoin_eventin_build_rsvp_payload( $post_id );

    if ( false === $payload ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventin', 'rsvpSubmitted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $integration->send( $saved_records, $payload );
}

function adfoin_eventin_dispatch_queued_rsvps() {
    if ( empty( $GLOBALS['adfoin_eventin_rsvp_queue'] ) || ! is_array( $GLOBALS['adfoin_eventin_rsvp_queue'] ) ) {
        return;
    }

    $queued_ids = array_keys( $GLOBALS['adfoin_eventin_rsvp_queue'] );

    unset( $GLOBALS['adfoin_eventin_rsvp_queue'] );

    foreach ( $queued_ids as $post_id ) {
        adfoin_eventin_dispatch_rsvp( $post_id );
    }
}

add_action( 'shutdown', 'adfoin_eventin_dispatch_queued_rsvps', 20 );
