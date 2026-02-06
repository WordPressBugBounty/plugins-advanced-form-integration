<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get FluentBooking triggers.
 *
 * @param string $form_provider Integration key.
 * @return array|void
 */
function adfoin_fluentbooking_get_forms( $form_provider ) {
	if ( $form_provider !== 'fluentbooking' ) {
		return;
	}

	return array(
		'bookingScheduled' => __( 'Booking Scheduled', 'advanced-form-integration' ),
		'bookingCompleted' => __( 'Booking Completed', 'advanced-form-integration' ),
		'bookingCancelled' => __( 'Booking Cancelled', 'advanced-form-integration' ),
	);
}

/**
 * Get FluentBooking fields.
 *
 * @param string $form_provider Integration key.
 * @param string $form_id       Trigger identifier.
 * @return array|void
 */
function adfoin_fluentbooking_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'fluentbooking' ) {
		return;
	}

	return array(
		'booking_id'                           => __( 'Booking ID', 'advanced-form-integration' ),
		'booking_hash'                         => __( 'Booking Hash', 'advanced-form-integration' ),
		'status'                               => __( 'Booking Status', 'advanced-form-integration' ),
		'status_label'                         => __( 'Booking Status Label', 'advanced-form-integration' ),
		'calendar_id'                          => __( 'Calendar ID', 'advanced-form-integration' ),
		'calendar_name'                        => __( 'Calendar Name', 'advanced-form-integration' ),
		'calendar_slug'                        => __( 'Calendar Slug', 'advanced-form-integration' ),
		'calendar_timezone'                    => __( 'Calendar Timezone', 'advanced-form-integration' ),
		'calendar_visibility'                  => __( 'Calendar Visibility', 'advanced-form-integration' ),
		'calendar_event_hash'                  => __( 'Event Hash', 'advanced-form-integration' ),
		'event_id'                             => __( 'Event ID', 'advanced-form-integration' ),
		'event_title'                          => __( 'Event Title', 'advanced-form-integration' ),
		'event_slug'                           => __( 'Event Slug', 'advanced-form-integration' ),
		'event_type'                           => __( 'Event Type', 'advanced-form-integration' ),
		'event_duration'                       => __( 'Event Duration (Minutes)', 'advanced-form-integration' ),
		'event_description'                    => __( 'Event Description', 'advanced-form-integration' ),
		'event_public_url'                     => __( 'Event Public URL', 'advanced-form-integration' ),
		'event_color'                          => __( 'Event Color Scheme', 'advanced-form-integration' ),
		'start_time'                           => __( 'Start Time (UTC)', 'advanced-form-integration' ),
		'end_time'                             => __( 'End Time (UTC)', 'advanced-form-integration' ),
		'slot_minutes'                         => __( 'Duration (Minutes)', 'advanced-form-integration' ),
		'person_time_zone'                     => __( 'Guest Time Zone', 'advanced-form-integration' ),
		'host_time_zone'                       => __( 'Host Time Zone', 'advanced-form-integration' ),
		'start_time_guest_text'                => __( 'Guest Local Time', 'advanced-form-integration' ),
		'start_time_host_text'                 => __( 'Host Local Time', 'advanced-form-integration' ),
		'person_user_id'                       => __( 'Guest WP User ID', 'advanced-form-integration' ),
		'person_contact_id'                    => __( 'Guest Contact ID', 'advanced-form-integration' ),
		'host_user_id'                         => __( 'Host WP User ID', 'advanced-form-integration' ),
		'group_id'                             => __( 'Group ID', 'advanced-form-integration' ),
		'parent_id'                            => __( 'Parent Booking ID', 'advanced-form-integration' ),
		'first_name'                           => __( 'Guest First Name', 'advanced-form-integration' ),
		'last_name'                            => __( 'Guest Last Name', 'advanced-form-integration' ),
		'full_name'                            => __( 'Guest Full Name', 'advanced-form-integration' ),
		'email'                                => __( 'Guest Email', 'advanced-form-integration' ),
		'phone'                                => __( 'Guest Phone', 'advanced-form-integration' ),
		'country'                              => __( 'Guest Country', 'advanced-form-integration' ),
		'message'                              => __( 'Guest Message', 'advanced-form-integration' ),
		'internal_note'                        => __( 'Internal Note', 'advanced-form-integration' ),
		'cancelled_by'                         => __( 'Cancelled By', 'advanced-form-integration' ),
		'cancel_reason'                        => __( 'Cancellation Reason', 'advanced-form-integration' ),
		'payment_status'                       => __( 'Payment Status', 'advanced-form-integration' ),
		'payment_method'                       => __( 'Payment Method', 'advanced-form-integration' ),
		'location_type'                        => __( 'Location Type', 'advanced-form-integration' ),
		'location_title'                       => __( 'Location Title', 'advanced-form-integration' ),
		'location_description'                 => __( 'Location Description', 'advanced-form-integration' ),
		'location_link'                        => __( 'Location Link', 'advanced-form-integration' ),
		'location_details_json'                => __( 'Location Details (JSON)', 'advanced-form-integration' ),
		'location_details_html'                => __( 'Location Details (HTML)', 'advanced-form-integration' ),
		'reschedule_url'                       => __( 'Reschedule URL', 'advanced-form-integration' ),
		'cancel_url'                           => __( 'Cancel URL', 'advanced-form-integration' ),
		'confirmation_url'                     => __( 'Confirmation URL', 'advanced-form-integration' ),
		'admin_booking_url'                    => __( 'Admin Booking URL', 'advanced-form-integration' ),
		'ics_download_url'                     => __( 'ICS Download URL', 'advanced-form-integration' ),
		'booking_title'                        => __( 'Booking Title', 'advanced-form-integration' ),
		'booking_details'                      => __( 'Booking Details', 'advanced-form-integration' ),
		'host_id'                              => __( 'Host ID', 'advanced-form-integration' ),
		'host_name'                            => __( 'Host Name', 'advanced-form-integration' ),
		'host_email'                           => __( 'Host Email', 'advanced-form-integration' ),
		'host_first_name'                      => __( 'Host First Name', 'advanced-form-integration' ),
		'host_last_name'                       => __( 'Host Last Name', 'advanced-form-integration' ),
		'host_avatar'                          => __( 'Host Avatar URL', 'advanced-form-integration' ),
		'hosts_json'                           => __( 'Additional Hosts (JSON)', 'advanced-form-integration' ),
		'additional_guests_json'               => __( 'Additional Guests (JSON)', 'advanced-form-integration' ),
		'additional_guests_html'               => __( 'Additional Guests (HTML)', 'advanced-form-integration' ),
		'total_guests'                         => __( 'Total Guest Count', 'advanced-form-integration' ),
		'custom_fields_json'                   => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
		'custom_fields_formatted'              => __( 'Custom Fields (Formatted JSON)', 'advanced-form-integration' ),
		'custom_fields_html'                   => __( 'Custom Fields (HTML)', 'advanced-form-integration' ),
		'custom_fields_text'                   => __( 'Custom Fields (Text)', 'advanced-form-integration' ),
		'meeting_bookmarks_json'               => __( 'Add-to-Calendar Links (JSON)', 'advanced-form-integration' ),
		'happening_status_json'                => __( 'Ongoing Status (JSON)', 'advanced-form-integration' ),
		'source'                               => __( 'Source', 'advanced-form-integration' ),
		'source_url'                           => __( 'Source URL', 'advanced-form-integration' ),
		'source_id'                            => __( 'Source ID', 'advanced-form-integration' ),
		'utm_source'                           => __( 'UTM Source', 'advanced-form-integration' ),
		'utm_medium'                           => __( 'UTM Medium', 'advanced-form-integration' ),
		'utm_campaign'                         => __( 'UTM Campaign', 'advanced-form-integration' ),
		'utm_term'                             => __( 'UTM Term', 'advanced-form-integration' ),
		'utm_content'                          => __( 'UTM Content', 'advanced-form-integration' ),
		'ip_address'                           => __( 'Guest IP Address', 'advanced-form-integration' ),
		'browser'                              => __( 'Guest Browser', 'advanced-form-integration' ),
		'device'                               => __( 'Guest Device', 'advanced-form-integration' ),
		'other_info'                           => __( 'Other Info', 'advanced-form-integration' ),
		'other_info_json'                      => __( 'Other Info (JSON)', 'advanced-form-integration' ),
		'created_at'                           => __( 'Created At', 'advanced-form-integration' ),
		'updated_at'                           => __( 'Updated At', 'advanced-form-integration' ),
		'calendar_event_settings_json'         => __( 'Event Settings (JSON)', 'advanced-form-integration' ),
		'calendar_event_location_type'         => __( 'Event Location Type', 'advanced-form-integration' ),
		'calendar_event_location_heading'      => __( 'Event Location Heading', 'advanced-form-integration' ),
		'calendar_event_location_settings_json'=> __( 'Event Location Settings (JSON)', 'advanced-form-integration' ),
		'calendar_event_max_bookings'          => __( 'Event Max Bookings Per Slot', 'advanced-form-integration' ),
		'calendar_event_availability_type'     => __( 'Event Availability Type', 'advanced-form-integration' ),
		'calendar_event_user_id'               => __( 'Event Owner User ID', 'advanced-form-integration' ),
		'calendar_event_is_display_spots'      => __( 'Event Display Spots', 'advanced-form-integration' ),
	);
}

add_action( 'fluent_booking/after_booking_scheduled', 'adfoin_fluentbooking_handle_booking_scheduled', 10, 2 );
add_action( 'fluent_booking/booking_schedule_completed', 'adfoin_fluentbooking_handle_booking_completed', 10, 2 );
add_action( 'fluent_booking/booking_schedule_cancelled', 'adfoin_fluentbooking_handle_booking_cancelled', 10, 2 );

/**
 * Handle FluentBooking "scheduled" trigger.
 *
 * @param mixed $booking       Booking payload (array or object).
 * @param mixed $calendar_slot Calendar slot payload (array or object).
 */
function adfoin_fluentbooking_handle_booking_scheduled( $booking, $calendar_slot ) {
	adfoin_fluentbooking_process_trigger( 'bookingScheduled', $booking, $calendar_slot );
}

/**
 * Handle FluentBooking "completed" trigger.
 *
 * @param mixed $booking       Booking payload.
 * @param mixed $calendar_slot Calendar slot payload.
 */
function adfoin_fluentbooking_handle_booking_completed( $booking, $calendar_slot ) {
	adfoin_fluentbooking_process_trigger( 'bookingCompleted', $booking, $calendar_slot );
}

/**
 * Handle FluentBooking "cancelled" trigger.
 *
 * @param mixed $booking       Booking payload.
 * @param mixed $calendar_slot Calendar slot payload.
 */
function adfoin_fluentbooking_handle_booking_cancelled( $booking, $calendar_slot ) {
	adfoin_fluentbooking_process_trigger( 'bookingCancelled', $booking, $calendar_slot );
}

/**
 * Common processor for FluentBooking triggers.
 *
 * @param string $trigger      Trigger key.
 * @param mixed  $booking      Booking payload.
 * @param mixed  $calendar_slot Calendar slot payload.
 */
function adfoin_fluentbooking_process_trigger( $trigger, $booking, $calendar_slot ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'fluentbooking', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload = adfoin_fluentbooking_build_payload( $booking, $calendar_slot );
	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $saved_records, $payload );
}

/**
 * Build a normalized payload from FluentBooking objects.
 *
 * @param mixed $booking       Booking payload.
 * @param mixed $calendar_slot Calendar slot payload.
 * @return array<string,mixed>
 */
function adfoin_fluentbooking_build_payload( $booking, $calendar_slot ) {
	if ( empty( $booking ) ) {
		return array();
	}

	$booking_id = adfoin_fluentbooking_get_attr( $booking, 'id' );
	$booking_hash = adfoin_fluentbooking_get_attr( $booking, 'hash' );

	if ( ! $booking_id && ! $booking_hash ) {
		return array();
	}

	$calendar = adfoin_fluentbooking_get_attr( $booking, 'calendar' );
	if ( ! $calendar && $calendar_slot ) {
		$calendar = adfoin_fluentbooking_get_attr( $calendar_slot, 'calendar' );
	}

	$host_details = adfoin_fluentbooking_call( $booking, 'getHostDetails', array( false ) );
	if ( ! is_array( $host_details ) ) {
		$host_details = array();
	}

	$hosts_details = adfoin_fluentbooking_call( $booking, 'getHostsDetails', array( false ) );
	if ( ! is_array( $hosts_details ) ) {
		$hosts_details = array();
	}

	$additional_guests = adfoin_fluentbooking_call( $booking, 'getAdditionalGuests', array() );
	if ( ! is_array( $additional_guests ) ) {
		$additional_guests = array();
	}

	$location_details = adfoin_fluentbooking_get_attr( $booking, 'location_details', array() );
	if ( ! is_array( $location_details ) ) {
		$location_details = array();
	}

	$custom_fields_raw = adfoin_fluentbooking_call( $booking, 'getCustomFormData', array( false ) );
	if ( ! is_array( $custom_fields_raw ) ) {
		$custom_fields_raw = array();
	}

	$custom_fields_formatted = adfoin_fluentbooking_call( $booking, 'getCustomFormData', array( true, false ) );
	if ( ! is_array( $custom_fields_formatted ) ) {
		$custom_fields_formatted = array();
	}

	$happening_status = adfoin_fluentbooking_call( $booking, 'getOngoingStatus', array() );
	if ( ! is_array( $happening_status ) ) {
		$happening_status = array();
	}

	$meeting_bookmarks = adfoin_fluentbooking_call( $booking, 'getMeetingBookmarks', array() );
	if ( ! is_array( $meeting_bookmarks ) ) {
		$meeting_bookmarks = array();
	}

	$guest_timezone = adfoin_fluentbooking_get_attr( $booking, 'person_time_zone' );
	$host_timezone  = adfoin_fluentbooking_call( $booking, 'getHostTimezone' );

	$guest_time_text = $guest_timezone
		? adfoin_fluentbooking_call( $booking, 'getFullBookingDateTimeText', array( $guest_timezone, false ) )
		: adfoin_fluentbooking_call( $booking, 'getFullBookingDateTimeText', array() );

	$host_time_text = $host_timezone
		? adfoin_fluentbooking_call( $booking, 'getFullBookingDateTimeText', array( $host_timezone, false ) )
		: '';

	$booking_title   = adfoin_fluentbooking_call( $booking, 'getBookingTitle', array() );
	$booking_details = adfoin_fluentbooking_call( $booking, 'getConfirmationData', array( false ) );

	$calendar_event_settings = adfoin_fluentbooking_get_attr( $calendar_slot, 'settings', array() );
	if ( ! is_array( $calendar_event_settings ) ) {
		$calendar_event_settings = array();
	}

	$calendar_event_location_settings = adfoin_fluentbooking_get_attr( $calendar_slot, 'location_settings', array() );
	if ( ! is_array( $calendar_event_location_settings ) ) {
		$calendar_event_location_settings = array();
	}

	$event_description = adfoin_fluentbooking_call( $calendar_slot, 'getDescription', array() );

	$created_at = adfoin_fluentbooking_get_attr( $booking, 'created_at' );
	$updated_at = adfoin_fluentbooking_get_attr( $booking, 'updated_at' );

	if ( $created_at instanceof \DateTimeInterface ) {
		$created_at = $created_at->format( 'Y-m-d H:i:s' );
	}
	if ( $updated_at instanceof \DateTimeInterface ) {
		$updated_at = $updated_at->format( 'Y-m-d H:i:s' );
	}

	$other_info      = adfoin_fluentbooking_get_attr( $booking, 'other_info' );
	$other_info_json = '';
	$other_info_text = '';
	if ( is_array( $other_info ) || ( is_object( $other_info ) && method_exists( $other_info, 'toArray' ) ) ) {
		$other_info_json = adfoin_fluentbooking_json( $other_info );
	} else {
		$other_info_text = adfoin_fluentbooking_to_string( $other_info );
	}

	$event_type = adfoin_fluentbooking_get_attr( $calendar_slot, 'event_type' );
	if ( '' === $event_type ) {
		$event_type = adfoin_fluentbooking_get_attr( $booking, 'event_type' );
	}

	$payload = array(
		'booking_id'                           => adfoin_fluentbooking_to_int( $booking_id ),
		'booking_hash'                         => adfoin_fluentbooking_to_string( $booking_hash ),
		'status'                               => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'status' ) ),
		'status_label'                         => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getBookingStatus', array() ) ),
		'calendar_id'                          => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'calendar_id' ) ),
		'calendar_name'                        => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar, 'title' ) ),
		'calendar_slug'                        => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar, 'slug' ) ),
		'calendar_timezone'                    => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar, 'author_timezone' ) ),
		'calendar_visibility'                  => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar, 'visibility' ) ),
		'calendar_event_hash'                  => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'hash' ) ),
		'event_id'                             => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'event_id' ) ),
		'event_title'                          => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'title' ) ),
		'event_slug'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'slug' ) ),
		'event_type'                           => adfoin_fluentbooking_to_string( $event_type ),
		'event_duration'                       => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $calendar_slot, 'duration' ) ?: adfoin_fluentbooking_get_attr( $booking, 'slot_minutes' ) ),
		'event_description'                    => adfoin_fluentbooking_to_string( $event_description ),
		'event_public_url'                     => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $calendar_slot, 'getPublicUrl', array() ) ),
		'event_color'                          => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'color_schema' ) ),
		'start_time'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'start_time' ) ),
		'end_time'                             => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'end_time' ) ),
		'slot_minutes'                         => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'slot_minutes' ) ),
		'person_time_zone'                     => adfoin_fluentbooking_to_string( $guest_timezone ),
		'host_time_zone'                       => adfoin_fluentbooking_to_string( $host_timezone ),
		'start_time_guest_text'                => adfoin_fluentbooking_to_string( $guest_time_text ),
		'start_time_host_text'                 => adfoin_fluentbooking_to_string( $host_time_text ),
		'person_user_id'                       => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'person_user_id' ) ),
		'person_contact_id'                    => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'person_contact_id' ) ),
		'host_user_id'                         => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'host_user_id' ) ),
		'group_id'                             => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'group_id' ) ),
		'parent_id'                            => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $booking, 'parent_id' ) ),
		'first_name'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'first_name' ) ),
		'last_name'                            => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'last_name' ) ),
		'full_name'                            => adfoin_fluentbooking_to_string( trim( adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'first_name' ) ) . ' ' . adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'last_name' ) ) ) ),
		'email'                                => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'email' ) ),
		'phone'                                => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'phone' ) ),
		'country'                              => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'country' ) ),
		'message'                              => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'message' ) ),
		'internal_note'                        => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'internal_note' ) ),
		'cancelled_by'                         => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'cancelled_by' ) ),
		'cancel_reason'                        => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getCancelReason', array( true, false ) ) ),
		'payment_status'                       => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'payment_status' ) ),
		'payment_method'                       => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'payment_method' ) ),
		'location_type'                        => adfoin_fluentbooking_to_string( isset( $location_details['type'] ) ? $location_details['type'] : '' ),
		'location_title'                       => adfoin_fluentbooking_to_string( isset( $location_details['title'] ) ? $location_details['title'] : '' ),
		'location_description'                 => adfoin_fluentbooking_to_string( isset( $location_details['description'] ) ? $location_details['description'] : '' ),
		'location_link'                        => adfoin_fluentbooking_to_string( isset( $location_details['online_platform_link'] ) ? $location_details['online_platform_link'] : '' ),
		'location_details_json'                => adfoin_fluentbooking_json( $location_details ),
		'location_details_html'                => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getLocationDetailsHtml', array() ) ),
		'reschedule_url'                       => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getRescheduleUrl', array() ) ),
		'cancel_url'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getCancelUrl', array() ) ),
		'confirmation_url'                     => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getConfirmationUrl', array() ) ),
		'admin_booking_url'                    => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getAdminViewUrl', array() ) ),
		'ics_download_url'                     => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getIcsDownloadUrl', array() ) ),
		'booking_title'                        => adfoin_fluentbooking_to_string( $booking_title ),
		'booking_details'                      => adfoin_fluentbooking_to_string( $booking_details ),
		'host_id'                              => adfoin_fluentbooking_to_int( isset( $host_details['id'] ) ? $host_details['id'] : 0 ),
		'host_name'                            => adfoin_fluentbooking_to_string( isset( $host_details['name'] ) ? $host_details['name'] : '' ),
		'host_email'                           => adfoin_fluentbooking_to_string( isset( $host_details['email'] ) ? $host_details['email'] : '' ),
		'host_first_name'                      => adfoin_fluentbooking_to_string( isset( $host_details['first_name'] ) ? $host_details['first_name'] : '' ),
		'host_last_name'                       => adfoin_fluentbooking_to_string( isset( $host_details['last_name'] ) ? $host_details['last_name'] : '' ),
		'host_avatar'                          => adfoin_fluentbooking_to_string( isset( $host_details['avatar'] ) ? $host_details['avatar'] : '' ),
		'hosts_json'                           => adfoin_fluentbooking_json( $hosts_details ),
		'additional_guests_json'               => adfoin_fluentbooking_json( $additional_guests ),
		'additional_guests_html'               => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getAdditionalGuests', array( true ) ) ),
		'total_guests'                         => adfoin_fluentbooking_to_int( adfoin_fluentbooking_call( $booking, 'getTotalGuestCount', array() ) ),
		'custom_fields_json'                   => adfoin_fluentbooking_json( $custom_fields_raw ),
		'custom_fields_formatted'              => adfoin_fluentbooking_json( $custom_fields_formatted ),
		'custom_fields_html'                   => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getAdditionalData', array( true ) ) ),
		'custom_fields_text'                   => adfoin_fluentbooking_to_string( adfoin_fluentbooking_call( $booking, 'getAdditionalData', array( false ) ) ),
		'meeting_bookmarks_json'               => adfoin_fluentbooking_json( $meeting_bookmarks ),
		'happening_status_json'                => adfoin_fluentbooking_json( $happening_status ),
		'source'                               => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'source' ) ),
		'source_url'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'source_url' ) ),
		'source_id'                            => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'source_id' ) ),
		'utm_source'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'utm_source' ) ),
		'utm_medium'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'utm_medium' ) ),
		'utm_campaign'                         => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'utm_campaign' ) ),
		'utm_term'                             => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'utm_term' ) ),
		'utm_content'                          => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'utm_content' ) ),
		'ip_address'                           => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'ip_address' ) ),
		'browser'                              => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'browser' ) ),
		'device'                               => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $booking, 'device' ) ),
		'other_info'                           => $other_info_text,
		'other_info_json'                      => $other_info_json,
		'created_at'                           => adfoin_fluentbooking_to_string( $created_at ),
		'updated_at'                           => adfoin_fluentbooking_to_string( $updated_at ),
		'calendar_event_settings_json'         => adfoin_fluentbooking_json( $calendar_event_settings ),
		'calendar_event_location_type'         => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'location_type' ) ),
		'calendar_event_location_heading'      => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'location_heading' ) ),
		'calendar_event_location_settings_json'=> adfoin_fluentbooking_json( $calendar_event_location_settings ),
		'calendar_event_max_bookings'          => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $calendar_slot, 'max_book_per_slot' ) ),
		'calendar_event_availability_type'     => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'availability_type' ) ),
		'calendar_event_user_id'               => adfoin_fluentbooking_to_int( adfoin_fluentbooking_get_attr( $calendar_slot, 'user_id' ) ),
		'calendar_event_is_display_spots'      => adfoin_fluentbooking_to_string( adfoin_fluentbooking_get_attr( $calendar_slot, 'is_display_spots' ) ),
	);

	return $payload;
}

/**
 * Safely access a property or array key.
 *
 * @param mixed  $source  Object or array source.
 * @param string $key     Property/key name.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function adfoin_fluentbooking_get_attr( $source, $key, $default = '' ) {
	if ( empty( $source ) ) {
		return $default;
	}

	if ( is_array( $source ) ) {
		return array_key_exists( $key, $source ) ? $source[ $key ] : $default;
	}

	if ( is_object( $source ) ) {
		try {
			if ( isset( $source->$key ) ) {
				return $source->$key;
			}
		} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
		} catch ( Error $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
		}

		if ( method_exists( $source, 'getAttribute' ) ) {
			try {
				$value = $source->getAttribute( $key );
				if ( $value !== null ) {
					return $value;
				}
			} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
			} catch ( Error $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
			}
		}

		if ( property_exists( $source, $key ) ) {
			return $source->$key;
		}
	}

	return $default;
}

/**
 * Safely invoke a method on an object.
 *
 * @param mixed  $object  Target instance.
 * @param string $method  Method name.
 * @param array  $args    Arguments.
 * @param mixed  $default Default value.
 * @return mixed
 */
function adfoin_fluentbooking_call( $object, $method, $args = array(), $default = '' ) {
	if ( ! is_object( $object ) || ! is_callable( array( $object, $method ) ) ) {
		return $default;
	}

	try {
		return call_user_func_array( array( $object, $method ), $args );
	} catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
		return $default;
	} catch ( Error $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
		return $default;
	}
}

/**
 * Convert arbitrary values to strings.
 *
 * @param mixed  $value   Input value.
 * @param string $default Default value.
 * @return string
 */
function adfoin_fluentbooking_to_string( $value, $default = '' ) {
	if ( $value === null ) {
		return $default;
	}

	if ( is_string( $value ) ) {
		return $value;
	}

	if ( is_bool( $value ) ) {
		return $value ? '1' : '0';
	}

	if ( is_numeric( $value ) ) {
		return (string) $value;
	}

	if ( $value instanceof \DateTimeInterface ) {
		return $value->format( 'Y-m-d H:i:s' );
	}

	return $default;
}

/**
 * Cast values to integers.
 *
 * @param mixed $value Input value.
 * @return int
 */
function adfoin_fluentbooking_to_int( $value ) {
	return is_numeric( $value ) ? (int) $value : 0;
}

/**
 * Encode arrays/objects as JSON.
 *
 * @param mixed $value Input value.
 * @return string
 */
function adfoin_fluentbooking_json( $value ) {
	if ( is_object( $value ) && method_exists( $value, 'toArray' ) ) {
		$value = $value->toArray();
	}

	if ( ! is_array( $value ) || empty( $value ) ) {
		return '';
	}

	$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $value ) : json_encode( $value );

	return is_string( $encoded ) ? $encoded : '';
}
