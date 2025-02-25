<?php
/**
 * Events Manager Integration for Advanced Form Integration
 *
 * This file adds support for the “eventsmanager” plugin by defining three triggers:
 *   - Booking Approved
 *   - User Registered for an Event (with a specific ticket)
 *   - User Unregistered from an Event
 *
 * Each trigger gathers the relevant booking data and sends it to the integration.
 */

/**
 * Get Events Manager Triggers
 *
 * @param string $form_provider
 * @return array|void
 */
function adfoin_eventsmanager_get_forms( $form_provider ) {
	if ( $form_provider !== 'eventsmanager' ) {
		return;
	}

	$triggers = array(
		'new_booking'  => __( 'New Booking', 'advanced-form-integration' )
	);

	return $triggers;
}

/**
 * Get Events Manager Fields
 *
 * @param string $form_provider
 * @param string $form_id
 * @return array|void
 */
/**
 * Get Events Manager Fields
 *
 * @param string $form_provider
 * @param string $form_id
 * @return array|void
 */
function adfoin_eventsmanager_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'eventsmanager' ) {
        return;
    }

    $fields = array(
        'booking_id'       => __( 'Booking ID', 'advanced-form-integration' ),
        'booking_uuid'     => __( 'Booking UUID', 'advanced-form-integration' ),
        'event_id'         => __( 'Event ID', 'advanced-form-integration' ),
        'event_name'       => __( 'Event Name', 'advanced-form-integration' ),
        'person_id'        => __( 'Person ID', 'advanced-form-integration' ),
        'person_name'      => __( 'Person Name', 'advanced-form-integration' ),
        'person_email'     => __( 'Person Email', 'advanced-form-integration' ),
        'person_phone'     => __( 'Person Phone', 'advanced-form-integration' ),
        'booking_price'    => __( 'Booking Price', 'advanced-form-integration' ),
        'booking_spaces'   => __( 'Booking Spaces', 'advanced-form-integration' ),
        'booking_comment'  => __( 'Booking Comment', 'advanced-form-integration' ),
        'booking_status'   => __( 'Booking Status', 'advanced-form-integration' ),
        'booking_rsvp_status' => __( 'Booking RSVP Status', 'advanced-form-integration' ),
        'booking_tax_rate' => __( 'Booking Tax Rate', 'advanced-form-integration' ),
        'booking_taxes'    => __( 'Booking Taxes', 'advanced-form-integration' ),
        'booking_meta'     => __( 'Booking Meta', 'advanced-form-integration' ),
    );

    return $fields;
}
/**
 * Handle Custom Booking Action
 *
 * This function hooks into the 'em_booking' action and performs a custom action.
 *
 * @param EM_Booking $booking_obj
 * @param mixed      $booking_data
 */
add_action( 'em_booking_added', 'adfoin_eventsmanager_handle_custom_booking_action', 10, 1 );
function adfoin_eventsmanager_handle_custom_booking_action( $booking_obj ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'eventsmanager', 'new_booking' );

    if ( empty( $saved_records ) ) {
        return;
    }

    // Gather booking details.
    $booking_id       = isset( $booking_obj->booking_id ) ? $booking_obj->booking_id : '';
    $booking_uuid     = isset( $booking_obj->booking_uuid ) ? $booking_obj->booking_uuid : '';
    $event_id         = isset( $booking_obj->event_id ) ? $booking_obj->event_id : '';
    $person_id        = isset( $booking_obj->person_id ) ? $booking_obj->person_id : '';
    $booking_price    = isset( $booking_obj->booking_price ) ? $booking_obj->booking_price : '';
    $booking_spaces   = isset( $booking_obj->booking_spaces ) ? $booking_obj->booking_spaces : '';
    $booking_comment  = isset( $booking_obj->booking_comment ) ? $booking_obj->booking_comment : '';
    $booking_status   = isset( $booking_obj->booking_status ) ? $booking_obj->booking_status : '';
    $booking_rsvp_status = isset( $booking_obj->booking_rsvp_status ) ? $booking_obj->booking_rsvp_status : '';
    $booking_tax_rate = isset( $booking_obj->booking_tax_rate ) ? $booking_obj->booking_tax_rate : '';
    $booking_taxes    = isset( $booking_obj->booking_taxes ) ? $booking_obj->booking_taxes : '';
    $booking_meta     = isset( $booking_obj->booking_meta ) ? $booking_obj->booking_meta : '';

    $person_object = new EM_Person( $person_id );
    $person_name   = $person_object->get_name();
    $person_email  = $person_object->user_email;
    $person_phone  = $person_object->phone;

    $event_name = '';
    $event      = em_get_event( $event_id );
    if ( $event ) {
        $event_name = $event->event_name;
    }

    $posted_data = array(
        'booking_id'       => $booking_id,
        'booking_uuid'     => $booking_uuid,
        'event_id'         => $event_id,
        'event_name'       => $event_name,
        'person_id'        => $person_id,
        'person_name'      => $person_name,
        'person_email'     => $person_email,
        'person_phone'     => $person_phone,
        'booking_price'    => $booking_price,
        'booking_spaces'   => $booking_spaces,
        'booking_comment'  => $booking_comment,
        'booking_status'   => $booking_status,
        'booking_rsvp_status' => $booking_rsvp_status,
        'booking_tax_rate' => $booking_tax_rate,
        'booking_taxes'    => $booking_taxes,
        'booking_meta'     => $booking_meta,
    );

    $integration->send( $saved_records, $posted_data );
}
