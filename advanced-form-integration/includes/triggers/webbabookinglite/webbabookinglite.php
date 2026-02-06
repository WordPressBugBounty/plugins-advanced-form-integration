<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return available Webba Booking Lite triggers.
 *
 * @param string $form_provider Provider key.
 * @return array<string,string>|void
 */
function adfoin_webbabookinglite_get_forms( $form_provider ) {
	if ( 'webbabookinglite' !== $form_provider ) {
		return;
	}

	return array(
		'bookingCreated'   => __( 'Booking Created', 'advanced-form-integration' ),
		'bookingPaid'      => __( 'Booking Paid', 'advanced-form-integration' ),
		'bookingCancelled' => __( 'Booking Cancelled', 'advanced-form-integration' ),
	);
}

/**
 * Return mapped fields for Webba Booking Lite triggers.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 * @return array<string,string>|void
 */
function adfoin_webbabookinglite_get_form_fields( $form_provider, $form_id ) {
	if ( 'webbabookinglite' !== $form_provider ) {
		return;
	}

	return adfoin_webbabookinglite_field_catalog();
}

/**
 * Shared field definitions.
 *
 * @return array<string,string>
 */
function adfoin_webbabookinglite_field_catalog() {
	return array(
		'event_type'                          => __( 'Event Type', 'advanced-form-integration' ),
		'event_context_source'                => __( 'Event Context Source', 'advanced-form-integration' ),
		'event_context_json'                  => __( 'Event Context (JSON)', 'advanced-form-integration' ),
		'event_triggered_at'                  => __( 'Event Fired At (Timestamp)', 'advanced-form-integration' ),
		'event_triggered_at_iso'              => __( 'Event Fired At (ISO8601)', 'advanced-form-integration' ),
		'booking_id'                          => __( 'Booking ID', 'advanced-form-integration' ),
		'booking_token'                       => __( 'Booking Token', 'advanced-form-integration' ),
		'booking_admin_token'                 => __( 'Admin Token', 'advanced-form-integration' ),
		'booking_payment_cancel_token'        => __( 'Payment Cancel Token', 'advanced-form-integration' ),
		'booking_payment_id'                  => __( 'Payment ID', 'advanced-form-integration' ),
		'booking_status'                      => __( 'Booking Status', 'advanced-form-integration' ),
		'booking_prev_status'                 => __( 'Previous Booking Status', 'advanced-form-integration' ),
		'booking_next_status'                 => __( 'Next Booking Status', 'advanced-form-integration' ),
		'booking_created_by'                  => __( 'Created By', 'advanced-form-integration' ),
		'booking_description'                 => __( 'Booking Description', 'advanced-form-integration' ),
		'booking_timezone'                    => __( 'Service Timezone', 'advanced-form-integration' ),
		'booking_quantity'                    => __( 'Booked Quantity', 'advanced-form-integration' ),
		'booking_duration_minutes'            => __( 'Duration (Minutes)', 'advanced-form-integration' ),
		'booking_day_timestamp'               => __( 'Booking Day (Timestamp)', 'advanced-form-integration' ),
		'booking_day_date'                    => __( 'Booking Day (Service Timezone)', 'advanced-form-integration' ),
		'booking_created_on_timestamp'        => __( 'Created At (Timestamp)', 'advanced-form-integration' ),
		'booking_created_on_local'            => __( 'Created At (Service Timezone)', 'advanced-form-integration' ),
		'booking_created_on_utc'              => __( 'Created At (UTC)', 'advanced-form-integration' ),
		'booking_expiration_timestamp'        => __( 'Expiration Time (Timestamp)', 'advanced-form-integration' ),
		'booking_expiration_local'            => __( 'Expiration Date (Service Timezone)', 'advanced-form-integration' ),
		'booking_canceled_by'                 => __( 'Cancelled By', 'advanced-form-integration' ),
		'booking_google_event_id'             => __( 'Google Calendar Event ID', 'advanced-form-integration' ),
		'booking_zoom_meeting_id'             => __( 'Zoom Meeting ID', 'advanced-form-integration' ),
		'booking_zoom_meeting_url'            => __( 'Zoom Meeting URL', 'advanced-form-integration' ),
		'booking_zoom_meeting_password'       => __( 'Zoom Meeting Password', 'advanced-form-integration' ),
		'schedule_start_timestamp'            => __( 'Start Timestamp', 'advanced-form-integration' ),
		'schedule_start_local'                => __( 'Start DateTime (Service Timezone)', 'advanced-form-integration' ),
		'schedule_start_iso'                  => __( 'Start DateTime (ISO8601)', 'advanced-form-integration' ),
		'schedule_start_utc'                  => __( 'Start DateTime (UTC)', 'advanced-form-integration' ),
		'schedule_end_timestamp'              => __( 'End Timestamp', 'advanced-form-integration' ),
		'schedule_end_local'                  => __( 'End DateTime (Service Timezone)', 'advanced-form-integration' ),
		'schedule_end_iso'                    => __( 'End DateTime (ISO8601)', 'advanced-form-integration' ),
		'schedule_end_utc'                    => __( 'End DateTime (UTC)', 'advanced-form-integration' ),
		'schedule_slot_label'                 => __( 'Slot Label', 'advanced-form-integration' ),
		'customer_name'                       => __( 'Customer Full Name', 'advanced-form-integration' ),
		'customer_first_name'                 => __( 'Customer First Name', 'advanced-form-integration' ),
		'customer_last_name'                  => __( 'Customer Last Name', 'advanced-form-integration' ),
		'customer_email'                      => __( 'Customer Email', 'advanced-form-integration' ),
		'customer_phone'                      => __( 'Customer Phone', 'advanced-form-integration' ),
		'customer_locale'                     => __( 'Customer Locale', 'advanced-form-integration' ),
		'customer_language'                   => __( 'Customer Language', 'advanced-form-integration' ),
		'customer_time_offset_minutes'        => __( 'Customer Time Offset (Minutes)', 'advanced-form-integration' ),
		'customer_time_offset_formatted'      => __( 'Customer Time Offset (HH:MM)', 'advanced-form-integration' ),
		'customer_ip'                         => __( 'Customer IP', 'advanced-form-integration' ),
		'customer_custom_fields_json'         => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
		'customer_custom_fields_plain'        => __( 'Custom Fields (Text)', 'advanced-form-integration' ),
		'customer_custom_fields_count'        => __( 'Custom Field Count', 'advanced-form-integration' ),
		'customer_attachment_json'            => __( 'Uploaded Files (JSON)', 'advanced-form-integration' ),
		'customer_attachment_urls'            => __( 'Uploaded Files (List)', 'advanced-form-integration' ),
		'customer_attachment_count'           => __( 'Attachment Count', 'advanced-form-integration' ),
		'payment_method'                      => __( 'Payment Method', 'advanced-form-integration' ),
		'payment_price_each'                  => __( 'Price Per Person', 'advanced-form-integration' ),
		'payment_price_each_formatted'        => __( 'Price Per Person (Formatted)', 'advanced-form-integration' ),
		'payment_total'                       => __( 'Booking Total', 'advanced-form-integration' ),
		'payment_total_formatted'             => __( 'Booking Total (Formatted)', 'advanced-form-integration' ),
		'payment_amount_paid'                 => __( 'Amount Paid', 'advanced-form-integration' ),
		'payment_amount_paid_formatted'       => __( 'Amount Paid (Formatted)', 'advanced-form-integration' ),
		'payment_balance_due'                 => __( 'Balance Due', 'advanced-form-integration' ),
		'payment_balance_due_formatted'       => __( 'Balance Due (Formatted)', 'advanced-form-integration' ),
		'payment_amount_details_json'         => __( 'Payment Breakdown (JSON)', 'advanced-form-integration' ),
		'payment_amount_details_plain'        => __( 'Payment Breakdown (Text)', 'advanced-form-integration' ),
		'coupon_id'                           => __( 'Coupon ID', 'advanced-form-integration' ),
		'coupon_name'                         => __( 'Coupon Name', 'advanced-form-integration' ),
		'coupon_fixed_discount'               => __( 'Coupon Discount (Fixed)', 'advanced-form-integration' ),
		'coupon_fixed_discount_formatted'     => __( 'Coupon Discount (Fixed, Formatted)', 'advanced-form-integration' ),
		'coupon_percentage_discount'          => __( 'Coupon Discount (%)', 'advanced-form-integration' ),
		'coupon_usage_limit'                  => __( 'Coupon Usage Limit', 'advanced-form-integration' ),
		'coupon_used'                         => __( 'Coupon Times Used', 'advanced-form-integration' ),
		'coupon_remaining_uses'               => __( 'Coupon Remaining Uses', 'advanced-form-integration' ),
		'coupon_services_json'                => __( 'Coupon Services (JSON)', 'advanced-form-integration' ),
		'coupon_services_plain'               => __( 'Coupon Services (List)', 'advanced-form-integration' ),
		'coupon_date_range'                   => __( 'Coupon Date Range', 'advanced-form-integration' ),
		'service_id'                          => __( 'Service ID', 'advanced-form-integration' ),
		'service_name'                        => __( 'Service Name', 'advanced-form-integration' ),
		'service_description'                 => __( 'Service Description', 'advanced-form-integration' ),
		'service_description_plain'           => __( 'Service Description (Plain Text)', 'advanced-form-integration' ),
		'service_duration_minutes'            => __( 'Service Duration (Minutes)', 'advanced-form-integration' ),
		'service_price'                       => __( 'Service Base Price', 'advanced-form-integration' ),
		'service_price_formatted'             => __( 'Service Base Price (Formatted)', 'advanced-form-integration' ),
		'service_fee'                         => __( 'Service Fee', 'advanced-form-integration' ),
		'service_fee_formatted'               => __( 'Service Fee (Formatted)', 'advanced-form-integration' ),
		'service_min_quantity'                => __( 'Service Minimum Quantity', 'advanced-form-integration' ),
		'service_max_quantity'                => __( 'Service Maximum Quantity', 'advanced-form-integration' ),
		'service_step_minutes'                => __( 'Service Step (Minutes)', 'advanced-form-integration' ),
		'service_prepare_time_minutes'        => __( 'Preparation Time (Minutes)', 'advanced-form-integration' ),
		'service_interval_between_minutes'    => __( 'Interval Between Bookings (Minutes)', 'advanced-form-integration' ),
		'service_form_id'                     => __( 'Assigned Webba Form ID', 'advanced-form-integration' ),
		'service_payment_methods_json'        => __( 'Payment Methods (JSON)', 'advanced-form-integration' ),
		'service_payment_methods_plain'       => __( 'Payment Methods (List)', 'advanced-form-integration' ),
		'service_is_payable'                  => __( 'Service Requires Payment', 'advanced-form-integration' ),
		'service_has_only_arrival_payment_method' => __( 'Only Arrival Payment Allowed', 'advanced-form-integration' ),
		'service_google_calendar_ids_json'    => __( 'Google Calendar IDs (JSON)', 'advanced-form-integration' ),
		'service_google_calendar_ids_plain'   => __( 'Google Calendar IDs (List)', 'advanced-form-integration' ),
		'service_availability_range'          => __( 'Service Availability Range', 'advanced-form-integration' ),
		'service_category_id'                 => __( 'Service Category ID', 'advanced-form-integration' ),
		'service_category_names'              => __( 'Service Categories', 'advanced-form-integration' ),
		'service_woo_product_id'              => __( 'Linked WooCommerce Product ID', 'advanced-form-integration' ),
		'link_payment'                        => __( 'Customer Payment URL', 'advanced-form-integration' ),
		'link_cancel'                         => __( 'Customer Cancel URL', 'advanced-form-integration' ),
		'link_add_to_google'                  => __( 'Add To Google Calendar URL', 'advanced-form-integration' ),
		'link_admin_cancel'                   => __( 'Admin Cancel URL', 'advanced-form-integration' ),
		'link_admin_approve'                  => __( 'Admin Approve URL', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_webbabookinglite_bootstrap', 20 );

/**
 * Attach runtime listeners once both plugins are ready.
 */
function adfoin_webbabookinglite_bootstrap() {
	if ( ! adfoin_webbabookinglite_is_ready() ) {
		return;
	}

	add_action( 'wbk_booking_added', 'adfoin_webbabookinglite_handle_booking_added', 20, 1 );
	add_action( 'wbk_after_set_as_paid', 'adfoin_webbabookinglite_handle_booking_paid_batch', 20, 1 );
	add_action( 'wbk_booking_paid', 'adfoin_webbabookinglite_handle_booking_paid_direct', 20, 2 );
	add_action( 'webba_before_cancel_booking', 'adfoin_webbabookinglite_handle_booking_cancelled', 20, 1 );
}

/**
 * Determine whether Webba Booking Lite is available.
 *
 * @return bool
 */
function adfoin_webbabookinglite_is_ready() {
	return class_exists( 'WBK_Booking' ) && class_exists( 'WBK_Service' );
}

/**
 * Handle booking creation events.
 *
 * @param mixed $booking_data Raw booking context.
 */
function adfoin_webbabookinglite_handle_booking_added( $booking_data ) {
	if ( ! adfoin_webbabookinglite_is_ready() ) {
		return;
	}

	$payload = adfoin_webbabookinglite_prepare_booking_payload(
		$booking_data,
		array(
			'source' => 'wbk_booking_added',
		)
	);

	adfoin_webbabookinglite_dispatch( 'bookingCreated', $payload );
}

/**
 * Handle booking payment events fired after set_as_paid().
 *
 * @param array<int> $booking_ids Paid booking IDs.
 */
function adfoin_webbabookinglite_handle_booking_paid_batch( $booking_ids ) {
	if ( ! adfoin_webbabookinglite_is_ready() ) {
		return;
	}

	$booking_ids = array_unique( array_filter( array_map( 'absint', (array) $booking_ids ) ) );

	foreach ( $booking_ids as $booking_id ) {
		$payload = adfoin_webbabookinglite_prepare_booking_payload(
			$booking_id,
			array(
				'source'       => 'wbk_after_set_as_paid',
				'next_status'  => 'paid',
			)
		);

		adfoin_webbabookinglite_dispatch( 'bookingPaid', $payload );
	}
}

/**
 * Handle booking paid events fired directly (e.g. coupons).
 *
 * @param int    $booking_id Booking ID.
 * @param string $method     Payment method hint.
 */
function adfoin_webbabookinglite_handle_booking_paid_direct( $booking_id, $method = '' ) {
	if ( ! adfoin_webbabookinglite_is_ready() ) {
		return;
	}

	$booking_id = absint( $booking_id );
	if ( $booking_id < 1 ) {
		return;
	}

	$payload = adfoin_webbabookinglite_prepare_booking_payload(
		$booking_id,
		array(
			'source'          => 'wbk_booking_paid',
			'payment_method'  => $method,
			'next_status'     => 'paid',
		)
	);

	adfoin_webbabookinglite_dispatch( 'bookingPaid', $payload );
}

/**
 * Handle cancellation events.
 *
 * @param int $booking_id Booking ID.
 */
function adfoin_webbabookinglite_handle_booking_cancelled( $booking_id ) {
	if ( ! adfoin_webbabookinglite_is_ready() ) {
		return;
	}

	$booking_id = absint( $booking_id );
	if ( $booking_id < 1 ) {
		return;
	}

	$payload = adfoin_webbabookinglite_prepare_booking_payload(
		$booking_id,
		array(
			'source'      => 'webba_before_cancel_booking',
			'next_status' => 'cancelled',
		)
	);

	adfoin_webbabookinglite_dispatch( 'bookingCancelled', $payload );
}

/**
 * Dispatch payload to saved integrations.
 *
 * @param string               $trigger Trigger key.
 * @param array<string,mixed>  $payload Prepared payload.
 */
function adfoin_webbabookinglite_dispatch( $trigger, $payload ) {
	if ( empty( $payload ) || ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'webbabookinglite', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$payload['event_type'] = $trigger;

	$integration->send( $saved_records, $payload );
}

/**
 * Prepare the outbound payload.
 *
 * @param mixed                 $booking_source Booking source reference.
 * @param array<string,mixed>   $context        Extra context.
 * @return array<string,mixed>
 */
function adfoin_webbabookinglite_prepare_booking_payload( $booking_source, $context = array() ) {
	$booking = adfoin_webbabookinglite_resolve_booking( $booking_source );

	if ( ! $booking ) {
		return array();
	}

	list( $timezone, $timezone_string ) = adfoin_webbabookinglite_get_timezone();
	$utc_timezone                       = new DateTimeZone( 'UTC' );
	$date_format                        = class_exists( 'WBK_Format_Utils' ) ? WBK_Format_Utils::get_date_format() : get_option( 'date_format', 'F j, Y' );
	$time_format                        = class_exists( 'WBK_Format_Utils' ) ? WBK_Format_Utils::get_time_format() : get_option( 'time_format', 'H:i' );
	$human_datetime_format              = trim( $date_format . ' ' . $time_format );

	$start_timestamp    = (int) $booking->get_start();
	$end_timestamp      = (int) $booking->get_end();
	$day_timestamp      = (int) $booking->get( 'day' );
	$created_on         = (int) $booking->get( 'created_on' );
	$expiration_time    = (int) $booking->get( 'expiration_time' );
	$customer_offset    = (int) $booking->get( 'time_offset' );
	$quantity           = max( 1, (int) $booking->get_quantity() );
	$duration           = (int) $booking->get( 'duration' );
	$price_each         = (float) $booking->get_price();
	$total_price        = $price_each * $quantity;
	$amount_paid        = (float) $booking->get( 'amount_paid' );
	$balance_due        = max( $total_price - $amount_paid, 0 );
	$payment_method     = (string) $booking->get( 'payment_method' );

	if ( '' === $payment_method && ! empty( $context['payment_method'] ) ) {
		$payment_method = (string) $context['payment_method'];
	}

	$price_each_pair = adfoin_webbabookinglite_money_pair( $price_each );
	$total_pair      = adfoin_webbabookinglite_money_pair( $total_price );
	$paid_pair       = adfoin_webbabookinglite_money_pair( $amount_paid );
	$balance_pair    = adfoin_webbabookinglite_money_pair( $balance_due );

	$service_id          = absint( $booking->get( 'service_id' ) );
	$service_loaded      = false;
	$service_name        = '';
	$service_description = '';
	$service_price_pair  = adfoin_webbabookinglite_money_pair( 0 );
	$service_fee_pair    = adfoin_webbabookinglite_money_pair( 0 );
	$service_min_qty     = 0;
	$service_max_qty     = 0;
	$service_step        = 0;
	$service_prepare     = 0;
	$service_interval    = 0;
	$service_form_id     = '';
	$service_payment_methods = array();
	$service_is_payable       = '';
	$service_arrival_only     = '';
	$service_google_calendars = array();
	$service_availability_range = '';
	$service_woo_product_id   = 0;

	if ( $service_id > 0 ) {
		$service = new WBK_Service( $service_id );
		if ( ! method_exists( $service, 'is_loaded' ) || $service->is_loaded() ) {
			$service_loaded        = true;
			$service_name          = (string) $service->get_name();
			$service_description   = method_exists( $service, 'get_description' ) ? (string) $service->get_description( true ) : (string) $service->get( 'description' );
			$service_price_pair    = adfoin_webbabookinglite_money_pair( (float) $service->get_price() );
			$service_fee_pair      = adfoin_webbabookinglite_money_pair( (float) $service->get_fee() );
			$service_min_qty       = (int) $service->get_min_quantity();
			$service_max_qty       = (int) $service->get_max_quantity();
			$service_step          = (int) $service->get_step();
			$service_prepare       = (int) $service->get_prepare_time();
			$service_interval      = (int) $service->get_interval_between();
			$service_form_id       = (string) $service->get_form();
			$service_woo_product_id = (int) $service->get_woo_product();

			$methods_raw = $service->get_payment_methods();
			if ( ! empty( $methods_raw ) ) {
				$decoded = json_decode( $methods_raw, true );
				if ( is_array( $decoded ) ) {
					$service_payment_methods = array_filter( array_map( 'trim', $decoded ) );
				}
			}

			$service_is_payable   = $service->is_payable() ? 'yes' : 'no';
			$service_arrival_only = $service->has_only_arrival_payment_method() ? 'yes' : 'no';

			$service_google_calendars = $service->get_gg_calendars();
			if ( ! is_array( $service_google_calendars ) ) {
				$service_google_calendars = array();
			}

			$range = $service->get_availability_range();
			if ( is_array( $range ) && ! empty( $range ) ) {
				$service_availability_range = implode( ' - ', array_filter( array_map( 'trim', $range ) ) );
			}
		}
	}

	$service_category_id   = (int) $booking->get( 'service_category' );
	$service_category_names = '';
	if ( class_exists( 'WBK_Model_Utils' ) && method_exists( 'WBK_Model_Utils', 'get_category_names_by_service' ) && $service_id ) {
		$service_category_names = (string) WBK_Model_Utils::get_category_names_by_service( $service_id );
	}

	$coupon_id                  = absint( $booking->get( 'coupon' ) );
	$coupon_name                = '';
	$coupon_fixed_pair          = adfoin_webbabookinglite_money_pair( 0 );
	$coupon_percentage          = '';
	$coupon_usage_limit_value   = '';
	$coupon_used_value          = '';
	$coupon_remaining_uses      = '';
	$coupon_services            = array();
	$coupon_services_plain      = '';
	$coupon_date_range          = '';

	if ( $coupon_id && class_exists( 'WBK_Coupon' ) ) {
		$coupon = new WBK_Coupon( $coupon_id );
		if ( ! method_exists( $coupon, 'is_loaded' ) || $coupon->is_loaded() ) {
			$coupon_name       = (string) $coupon->get_name();
			$coupon_fixed_pair = adfoin_webbabookinglite_money_pair( (float) $coupon->get( 'amount_fixed' ) );
			$coupon_percentage = $coupon->get( 'amount_percentage' ) !== null ? (string) $coupon->get( 'amount_percentage' ) : '';

			$usage_limit = $coupon->get( 'maximum' );
			if ( '' !== $usage_limit && null !== $usage_limit && '0' !== (string) $usage_limit ) {
				$coupon_usage_limit_value = (int) $usage_limit;
			}

			$coupon_used = $coupon->get( 'used' );
			if ( '' !== $coupon_used && null !== $coupon_used ) {
				$coupon_used_value = (int) $coupon_used;
			}

			if ( '' !== $coupon_usage_limit_value && '' !== $coupon_used_value ) {
				$coupon_remaining_uses = max( $coupon_usage_limit_value - $coupon_used_value, 0 );
			}

			$services_raw = $coupon->get( 'services' );
			if ( ! empty( $services_raw ) ) {
				$decoded = json_decode( $services_raw, true );
				if ( is_array( $decoded ) ) {
					$coupon_services = array_map( 'strval', $decoded );
					$coupon_services_plain = implode( ', ', $coupon_services );
				}
			}

			$coupon_date_range = (string) $coupon->get( 'date_range' );
		}
	}

	$custom_fields = adfoin_webbabookinglite_extract_custom_fields( $booking->get( 'extra' ) );
	$attachments   = adfoin_webbabookinglite_normalize_attachments( $booking->get( 'attachment' ) );
	$amount_details = adfoin_webbabookinglite_normalize_amount_details( $booking->get( 'amount_details' ) );
	$links          = adfoin_webbabookinglite_build_manage_links( $booking );

	$customer_name       = trim( (string) $booking->get_name() );
	$customer_first_name = '';
	$customer_last_name  = '';
	if ( $customer_name ) {
		$name_parts = preg_split( '/\s+/u', $customer_name );
		if ( ! empty( $name_parts ) ) {
			$customer_first_name = array_shift( $name_parts );
			$customer_last_name  = implode( ' ', $name_parts );
		}
	}

	$start_local = adfoin_webbabookinglite_format_datetime( $start_timestamp, $timezone, $human_datetime_format );
	$end_local   = adfoin_webbabookinglite_format_datetime( $end_timestamp, $timezone, $human_datetime_format );

	$event_timestamp = time();
	$context_source  = isset( $context['source'] ) ? (string) $context['source'] : '';
	$next_status     = isset( $context['next_status'] ) ? (string) $context['next_status'] : '';

	return array(
		'event_context_source'              => $context_source,
		'event_context_json'                => adfoin_webbabookinglite_json_encode( $context ),
		'event_triggered_at'                => $event_timestamp,
		'event_triggered_at_iso'            => gmdate( DATE_ATOM, $event_timestamp ),
		'booking_id'                        => $booking->get_id(),
		'booking_token'                     => (string) $booking->get( 'token' ),
		'booking_admin_token'               => (string) $booking->get( 'admin_token' ),
		'booking_payment_cancel_token'      => (string) $booking->get( 'payment_cancel_token' ),
		'booking_payment_id'                => (string) $booking->get( 'payment_id' ),
		'booking_status'                    => (string) $booking->get( 'status' ),
		'booking_prev_status'               => (string) $booking->get( 'prev_status' ),
		'booking_next_status'               => $next_status,
		'booking_created_by'                => (string) $booking->get( 'created_by' ),
		'booking_description'               => (string) $booking->get( 'description' ),
		'booking_timezone'                  => $timezone_string,
		'booking_quantity'                  => $quantity,
		'booking_duration_minutes'          => $duration,
		'booking_day_timestamp'             => $day_timestamp,
		'booking_day_date'                  => adfoin_webbabookinglite_format_datetime( $day_timestamp, $timezone, $date_format ),
		'booking_created_on_timestamp'      => $created_on,
		'booking_created_on_local'          => adfoin_webbabookinglite_format_datetime( $created_on, $timezone, DATE_ATOM ),
		'booking_created_on_utc'            => adfoin_webbabookinglite_format_datetime( $created_on, $utc_timezone, DATE_ATOM ),
		'booking_expiration_timestamp'      => $expiration_time,
		'booking_expiration_local'          => adfoin_webbabookinglite_format_datetime( $expiration_time, $timezone, DATE_ATOM ),
		'booking_canceled_by'               => (string) $booking->get( 'canceled_by' ),
		'booking_google_event_id'           => (string) $booking->get( 'gg_event_id' ),
		'booking_zoom_meeting_id'           => (string) $booking->get( 'zoom_meeting_id' ),
		'booking_zoom_meeting_url'          => (string) $booking->get( 'zoom_meeting_url' ),
		'booking_zoom_meeting_password'     => (string) $booking->get( 'zoom_meeting_pwd' ),
		'schedule_start_timestamp'          => $start_timestamp,
		'schedule_start_local'              => $start_local,
		'schedule_start_iso'                => adfoin_webbabookinglite_format_datetime( $start_timestamp, $timezone, DATE_ATOM ),
		'schedule_start_utc'                => $start_timestamp ? gmdate( DATE_ATOM, $start_timestamp ) : '',
		'schedule_end_timestamp'            => $end_timestamp,
		'schedule_end_local'                => $end_local,
		'schedule_end_iso'                  => adfoin_webbabookinglite_format_datetime( $end_timestamp, $timezone, DATE_ATOM ),
		'schedule_end_utc'                  => $end_timestamp ? gmdate( DATE_ATOM, $end_timestamp ) : '',
		'schedule_slot_label'               => trim( $start_local && $end_local ? $start_local . ' - ' . $end_local : $start_local ),
		'customer_name'                     => $customer_name,
		'customer_first_name'               => $customer_first_name,
		'customer_last_name'                => $customer_last_name,
		'customer_email'                    => (string) $booking->get( 'email' ),
		'customer_phone'                    => (string) $booking->get_phone(),
		'customer_locale'                   => (string) $booking->get( 'locale' ),
		'customer_language'                 => (string) $booking->get( 'lang' ),
		'customer_time_offset_minutes'      => $customer_offset,
		'customer_time_offset_formatted'    => adfoin_webbabookinglite_format_offset( $customer_offset ),
		'customer_ip'                       => (string) $booking->get( 'user_ip' ),
		'customer_custom_fields_json'       => $custom_fields['json'],
		'customer_custom_fields_plain'      => $custom_fields['plain'],
		'customer_custom_fields_count'      => $custom_fields['count'],
		'customer_attachment_json'          => $attachments['json'],
		'customer_attachment_urls'          => $attachments['plain'],
		'customer_attachment_count'         => $attachments['count'],
		'payment_method'                    => $payment_method,
		'payment_price_each'                => $price_each_pair['raw'],
		'payment_price_each_formatted'      => $price_each_pair['formatted'],
		'payment_total'                     => $total_pair['raw'],
		'payment_total_formatted'           => $total_pair['formatted'],
		'payment_amount_paid'               => $paid_pair['raw'],
		'payment_amount_paid_formatted'     => $paid_pair['formatted'],
		'payment_balance_due'               => $balance_pair['raw'],
		'payment_balance_due_formatted'     => $balance_pair['formatted'],
		'payment_amount_details_json'       => $amount_details['json'],
		'payment_amount_details_plain'      => $amount_details['plain'],
		'coupon_id'                         => $coupon_id,
		'coupon_name'                       => $coupon_name,
		'coupon_fixed_discount'             => $coupon_fixed_pair['raw'],
		'coupon_fixed_discount_formatted'   => $coupon_fixed_pair['formatted'],
		'coupon_percentage_discount'        => '' === $coupon_percentage ? '' : (float) $coupon_percentage,
		'coupon_usage_limit'                => $coupon_usage_limit_value,
		'coupon_used'                       => $coupon_used_value,
		'coupon_remaining_uses'             => $coupon_remaining_uses,
		'coupon_services_json'              => adfoin_webbabookinglite_json_encode( $coupon_services ),
		'coupon_services_plain'             => $coupon_services_plain,
		'coupon_date_range'                 => $coupon_date_range,
		'service_id'                        => $service_id,
		'service_name'                      => $service_name,
		'service_description'               => $service_description,
		'service_description_plain'         => wp_strip_all_tags( $service_description ),
		'service_duration_minutes'          => $service_loaded ? (int) $service->get_duration() : 0,
		'service_price'                     => $service_price_pair['raw'],
		'service_price_formatted'           => $service_price_pair['formatted'],
		'service_fee'                       => $service_fee_pair['raw'],
		'service_fee_formatted'             => $service_fee_pair['formatted'],
		'service_min_quantity'              => $service_min_qty,
		'service_max_quantity'              => $service_max_qty,
		'service_step_minutes'              => $service_step,
		'service_prepare_time_minutes'      => $service_prepare,
		'service_interval_between_minutes'  => $service_interval,
		'service_form_id'                   => $service_form_id,
		'service_payment_methods_json'      => adfoin_webbabookinglite_json_encode( $service_payment_methods ),
		'service_payment_methods_plain'     => implode( ', ', $service_payment_methods ),
		'service_is_payable'                => $service_is_payable,
		'service_has_only_arrival_payment_method' => $service_arrival_only,
		'service_google_calendar_ids_json'  => adfoin_webbabookinglite_json_encode( $service_google_calendars ),
		'service_google_calendar_ids_plain' => implode( ', ', $service_google_calendars ),
		'service_availability_range'        => $service_availability_range,
		'service_category_id'               => $service_category_id,
		'service_category_names'            => $service_category_names,
		'service_woo_product_id'            => $service_woo_product_id,
		'link_payment'                      => $links['payment'],
		'link_cancel'                       => $links['cancel'],
		'link_add_to_google'                => $links['google'],
		'link_admin_cancel'                 => $links['admin_cancel'],
		'link_admin_approve'                => $links['admin_approve'],
	);
}

/**
 * Resolve a booking model from mixed input.
 *
 * @param mixed $booking_source Source data.
 * @return WBK_Booking|null
 */
function adfoin_webbabookinglite_resolve_booking( $booking_source ) {
	if ( $booking_source instanceof WBK_Booking ) {
		return $booking_source;
	}

	$booking_id = 0;

	if ( is_numeric( $booking_source ) ) {
		$booking_id = absint( $booking_source );
	} elseif ( is_array( $booking_source ) && isset( $booking_source['id'] ) ) {
		$booking_id = absint( $booking_source['id'] );
	}

	if ( $booking_id < 1 ) {
		return null;
	}

	$booking = new WBK_Booking( $booking_id );

	if ( method_exists( $booking, 'is_loaded' ) && ! $booking->is_loaded() ) {
		return null;
	}

	return $booking;
}

/**
 * Get the service timezone with a safe fallback.
 *
 * @return array{DateTimeZone,string}
 */
function adfoin_webbabookinglite_get_timezone() {
	$timezone_string = get_option( 'wbk_timezone', 'UTC' );

	try {
		$timezone = new DateTimeZone( $timezone_string );
	} catch ( Exception $e ) {
		$timezone        = new DateTimeZone( 'UTC' );
		$timezone_string = 'UTC';
	}

	return array( $timezone, $timezone_string );
}

/**
 * Format a timestamp in a specific timezone.
 *
 * @param int          $timestamp Timestamp.
 * @param DateTimeZone $timezone  Target timezone.
 * @param string       $format    Format string.
 * @return string
 */
function adfoin_webbabookinglite_format_datetime( $timestamp, DateTimeZone $timezone, $format ) {
	if ( empty( $timestamp ) ) {
		return '';
	}

	return wp_date( $format, $timestamp, $timezone );
}

/**
 * Prepare monetary values.
 *
 * @param float $amount Amount.
 * @return array{raw:float,formatted:string}
 */
function adfoin_webbabookinglite_money_pair( $amount ) {
	$amount = (float) $amount;

	return array(
		'raw'       => $amount,
		'formatted' => adfoin_webbabookinglite_format_price( $amount ),
	);
}

/**
 * Format money according to Webba settings.
 *
 * @param float $amount Amount.
 * @return string
 */
function adfoin_webbabookinglite_format_price( $amount ) {
	if ( class_exists( 'WBK_Format_Utils' ) && method_exists( 'WBK_Format_Utils', 'format_price' ) ) {
		return WBK_Format_Utils::format_price( $amount );
	}

	if ( function_exists( 'number_format_i18n' ) ) {
		return number_format_i18n( $amount, 2 );
	}

	return (string) $amount;
}

/**
 * Convert minutes offset to +/-HH:MM.
 *
 * @param int $minutes Minutes offset.
 * @return string
 */
function adfoin_webbabookinglite_format_offset( $minutes ) {
	if ( ! is_numeric( $minutes ) ) {
		return '';
	}

	$minutes       = (int) $minutes;
	$sign          = $minutes >= 0 ? '+' : '-';
	$absolute      = abs( $minutes );
	$hours         = floor( $absolute / 60 );
	$remaining_min = $absolute % 60;

	return sprintf( '%s%02d:%02d', $sign, $hours, $remaining_min );
}

/**
 * Normalize custom field data.
 *
 * @param string $raw Raw JSON string.
 * @return array{json:string,plain:string,count:int}
 */
function adfoin_webbabookinglite_extract_custom_fields( $raw ) {
	$data  = array();
	$plain = array();

	if ( ! empty( $raw ) ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				$field_id    = isset( $field[0] ) ? $field[0] : '';
				$field_label = isset( $field[1] ) ? $field[1] : $field_id;
				$field_value = isset( $field[2] ) ? $field[2] : '';

				if ( is_array( $field_value ) ) {
					$field_value = implode( ', ', $field_value );
				}

				$data[]  = array(
					'id'    => $field_id,
					'label' => $field_label,
					'value' => $field_value,
				);
				$plain[] = trim( sprintf( '%s: %s', $field_label ? $field_label : $field_id, $field_value ) );
			}
		}
	}

	return array(
		'json'  => adfoin_webbabookinglite_json_encode( $data ),
		'plain' => implode( "\n", array_filter( $plain ) ),
		'count' => count( $data ),
	);
}

/**
 * Normalize attachment paths.
 *
 * @param string $raw Raw JSON list.
 * @return array{json:string,plain:string,count:int}
 */
function adfoin_webbabookinglite_normalize_attachments( $raw ) {
	$list = array();

	if ( ! empty( $raw ) ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $path ) {
				if ( ! is_string( $path ) || '' === $path ) {
					continue;
				}
				$list[] = adfoin_webbabookinglite_maybe_convert_path_to_url( $path );
			}
		}
	}

	return array(
		'json'  => adfoin_webbabookinglite_json_encode( $list ),
		'plain' => implode( ', ', $list ),
		'count' => count( $list ),
	);
}

/**
 * Convert filesystem paths to URLs when possible.
 *
 * @param string $path Path or URL.
 * @return string
 */
function adfoin_webbabookinglite_maybe_convert_path_to_url( $path ) {
	if ( empty( $path ) ) {
		return '';
	}

	$normalized_path = wp_normalize_path( $path );
	$normalized_root = wp_normalize_path( ABSPATH );

	if ( 0 === strpos( $normalized_path, $normalized_root ) ) {
		$relative = ltrim( substr( $normalized_path, strlen( $normalized_root ) ), '/' );
		return trailingslashit( site_url() ) . $relative;
	}

	if ( filter_var( $path, FILTER_VALIDATE_URL ) ) {
		return $path;
	}

	return $path;
}

/**
 * Normalize payment amount details.
 *
 * @param string $raw Raw JSON string.
 * @return array{json:string,plain:string}
 */
function adfoin_webbabookinglite_normalize_amount_details( $raw ) {
	if ( empty( $raw ) ) {
		return array(
			'json'  => '',
			'plain' => '',
		);
	}

	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) ) {
		$plain = array();
		foreach ( $decoded as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = adfoin_webbabookinglite_json_encode( $value );
			}
			$plain[] = sprintf( '%s: %s', $key, $value );
		}

		return array(
			'json'  => adfoin_webbabookinglite_json_encode( $decoded ),
			'plain' => implode( "\n", $plain ),
		);
	}

	return array(
		'json'  => (string) $raw,
		'plain' => (string) $raw,
	);
}

/**
 * Safe JSON encode helper.
 *
 * @param mixed $data Data to encode.
 * @return string
 */
function adfoin_webbabookinglite_json_encode( $data ) {
	$encoded = wp_json_encode( $data );

	return ( false === $encoded ) ? '' : $encoded;
}

/**
 * Build customer/admin manage URLs.
 *
 * @param WBK_Booking $booking Booking instance.
 * @return array<string,string>
 */
function adfoin_webbabookinglite_build_manage_links( $booking ) {
	$links = array(
		'payment'      => '',
		'cancel'       => '',
		'google'       => '',
		'admin_cancel' => '',
		'admin_approve'=> '',
	);

	$landing = trim( get_option( 'wbk_email_landing', '' ) );
	if ( '' === $landing ) {
		return $links;
	}

	$token       = $booking->get( 'token' );
	$admin_token = $booking->get( 'admin_token' );

	if ( $token ) {
		$links['payment'] = add_query_arg( 'order_payment', $token, $landing );
		$links['cancel']  = add_query_arg( 'cancelation', $token, $landing );
		$links['google']  = add_query_arg( 'ggeventadd', $token, $landing );
	}

	if ( $admin_token ) {
		$links['admin_cancel']  = add_query_arg( 'admin_cancel', $admin_token, $landing );
		$links['admin_approve'] = add_query_arg( 'admin_approve', $admin_token, $landing );
	}

	return $links;
}
