<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'adfoin_latepoint_bootstrap', 20 );

function adfoin_latepoint_bootstrap() {
    if ( ! adfoin_latepoint_is_ready() ) {
        return;
    }

    add_action( 'latepoint_booking_created', 'adfoin_latepoint_handle_booking_created', 10, 1 );
    add_action( 'latepoint_booking_updated', 'adfoin_latepoint_handle_booking_updated', 10, 3 );
}

function adfoin_latepoint_is_ready() {
    return class_exists( 'OsBookingModel' );
}

function adfoin_latepoint_get_forms( $form_provider ) {
    if ( 'latepoint' !== $form_provider ) {
        return;
    }

    return array(
        'bookingCreated' => __( 'New Booking Created', 'advanced-form-integration' ),
        'bookingUpdated' => __( 'Booking Updated', 'advanced-form-integration' ),
    );
}

function adfoin_latepoint_get_form_fields( $form_provider, $form_id ) {
    if ( 'latepoint' !== $form_provider ) {
        return;
    }

    return adfoin_latepoint_field_catalog();
}

function adfoin_latepoint_field_catalog() {
    return array(
        'event_type'                     => __( 'Event Type', 'advanced-form-integration' ),
        'booking_id'                     => __( 'Booking ID', 'advanced-form-integration' ),
        'booking_code'                   => __( 'Booking Code', 'advanced-form-integration' ),
        'booking_status'                 => __( 'Booking Status', 'advanced-form-integration' ),
        'booking_previous_status'        => __( 'Previous Booking Status', 'advanced-form-integration' ),
        'booking_total_attendees'        => __( 'Total Attendees', 'advanced-form-integration' ),
        'booking_duration'               => __( 'Duration (Minutes)', 'advanced-form-integration' ),
        'booking_buffer_before'          => __( 'Buffer Before (Minutes)', 'advanced-form-integration' ),
        'booking_buffer_after'           => __( 'Buffer After (Minutes)', 'advanced-form-integration' ),
        'booking_start_date'             => __( 'Start Date', 'advanced-form-integration' ),
        'booking_start_time'             => __( 'Start Time (12h)', 'advanced-form-integration' ),
        'booking_start_time_24h'         => __( 'Start Time (24h)', 'advanced-form-integration' ),
        'booking_start_datetime'         => __( 'Start DateTime (Formatted)', 'advanced-form-integration' ),
        'booking_start_datetime_rfc3339' => __( 'Start DateTime (RFC3339)', 'advanced-form-integration' ),
        'booking_start_datetime_utc'     => __( 'Start DateTime (UTC)', 'advanced-form-integration' ),
        'booking_end_date'               => __( 'End Date', 'advanced-form-integration' ),
        'booking_end_time'               => __( 'End Time (12h)', 'advanced-form-integration' ),
        'booking_end_time_24h'           => __( 'End Time (24h)', 'advanced-form-integration' ),
        'booking_end_datetime'           => __( 'End DateTime (Formatted)', 'advanced-form-integration' ),
        'booking_end_datetime_rfc3339'   => __( 'End DateTime (RFC3339)', 'advanced-form-integration' ),
        'booking_end_datetime_utc'       => __( 'End DateTime (UTC)', 'advanced-form-integration' ),
        'booking_timezone'               => __( 'Timezone', 'advanced-form-integration' ),
        'booking_time_status'            => __( 'Time Status', 'advanced-form-integration' ),
        'booking_customer_comment'       => __( 'Customer Comment', 'advanced-form-integration' ),
        'booking_created_at'             => __( 'Created At', 'advanced-form-integration' ),
        'booking_updated_at'             => __( 'Updated At', 'advanced-form-integration' ),
        'booking_manage_url_agent'       => __( 'Manage Booking URL (Agent)', 'advanced-form-integration' ),
        'booking_manage_url_customer'    => __( 'Manage Booking URL (Customer)', 'advanced-form-integration' ),
        'booking_is_bundle'              => __( 'Part Of Bundle', 'advanced-form-integration' ),
        'booking_is_upcoming'            => __( 'Is Upcoming', 'advanced-form-integration' ),
        'booking_total_price_formatted'  => __( 'Booking Total (Formatted)', 'advanced-form-integration' ),
        'booking_order_item_id'          => __( 'Order Item ID', 'advanced-form-integration' ),
        'booking_recurrence_id'          => __( 'Recurrence ID', 'advanced-form-integration' ),
        'booking_payload_json'           => __( 'Booking Payload (JSON)', 'advanced-form-integration' ),
        'customer_id'                    => __( 'Customer ID', 'advanced-form-integration' ),
        'customer_uuid'                  => __( 'Customer UUID', 'advanced-form-integration' ),
        'customer_first_name'            => __( 'Customer First Name', 'advanced-form-integration' ),
        'customer_last_name'             => __( 'Customer Last Name', 'advanced-form-integration' ),
        'customer_full_name'             => __( 'Customer Full Name', 'advanced-form-integration' ),
        'customer_email'                 => __( 'Customer Email', 'advanced-form-integration' ),
        'customer_phone'                 => __( 'Customer Phone', 'advanced-form-integration' ),
        'customer_timezone'              => __( 'Customer Timezone', 'advanced-form-integration' ),
        'customer_notes'                 => __( 'Customer Notes', 'advanced-form-integration' ),
        'customer_admin_notes'           => __( 'Customer Admin Notes', 'advanced-form-integration' ),
        'customer_is_guest'              => __( 'Customer Is Guest', 'advanced-form-integration' ),
        'customer_wordpress_user_id'     => __( 'Customer WordPress User ID', 'advanced-form-integration' ),
        'service_id'                     => __( 'Service ID', 'advanced-form-integration' ),
        'service_name'                   => __( 'Service Name', 'advanced-form-integration' ),
        'service_duration'               => __( 'Service Duration (Minutes)', 'advanced-form-integration' ),
        'service_duration_name'          => __( 'Service Duration Label', 'advanced-form-integration' ),
        'service_price_min'              => __( 'Service Price Min', 'advanced-form-integration' ),
        'service_price_min_formatted'    => __( 'Service Price Min (Formatted)', 'advanced-form-integration' ),
        'service_price_max'              => __( 'Service Price Max', 'advanced-form-integration' ),
        'service_price_max_formatted'    => __( 'Service Price Max (Formatted)', 'advanced-form-integration' ),
        'service_charge_amount'          => __( 'Service Charge Amount', 'advanced-form-integration' ),
        'service_charge_amount_formatted'=> __( 'Service Charge Amount (Formatted)', 'advanced-form-integration' ),
        'service_deposit_amount'         => __( 'Service Deposit Amount', 'advanced-form-integration' ),
        'service_deposit_amount_formatted'=> __( 'Service Deposit Amount (Formatted)', 'advanced-form-integration' ),
        'service_buffer_before'          => __( 'Service Buffer Before (Minutes)', 'advanced-form-integration' ),
        'service_buffer_after'           => __( 'Service Buffer After (Minutes)', 'advanced-form-integration' ),
        'service_category_name'          => __( 'Service Category', 'advanced-form-integration' ),
        'agent_id'                       => __( 'Agent ID', 'advanced-form-integration' ),
        'agent_first_name'               => __( 'Agent First Name', 'advanced-form-integration' ),
        'agent_last_name'                => __( 'Agent Last Name', 'advanced-form-integration' ),
        'agent_full_name'                => __( 'Agent Full Name', 'advanced-form-integration' ),
        'agent_display_name'             => __( 'Agent Display Name', 'advanced-form-integration' ),
        'agent_email'                    => __( 'Agent Email', 'advanced-form-integration' ),
        'agent_phone'                    => __( 'Agent Phone', 'advanced-form-integration' ),
        'location_id'                    => __( 'Location ID', 'advanced-form-integration' ),
        'location_name'                  => __( 'Location Name', 'advanced-form-integration' ),
        'location_full_address'          => __( 'Location Address', 'advanced-form-integration' ),
        'location_category_name'         => __( 'Location Category', 'advanced-form-integration' ),
        'order_id'                       => __( 'Order ID', 'advanced-form-integration' ),
        'order_confirmation_code'        => __( 'Order Confirmation Code', 'advanced-form-integration' ),
        'order_status'                   => __( 'Order Status', 'advanced-form-integration' ),
        'order_payment_status'           => __( 'Order Payment Status', 'advanced-form-integration' ),
        'order_fulfillment_status'       => __( 'Order Fulfillment Status', 'advanced-form-integration' ),
        'order_subtotal'                 => __( 'Order Subtotal', 'advanced-form-integration' ),
        'order_subtotal_formatted'       => __( 'Order Subtotal (Formatted)', 'advanced-form-integration' ),
        'order_total'                    => __( 'Order Total', 'advanced-form-integration' ),
        'order_total_formatted'          => __( 'Order Total (Formatted)', 'advanced-form-integration' ),
        'order_coupon_code'              => __( 'Coupon Code', 'advanced-form-integration' ),
        'order_coupon_discount'          => __( 'Coupon Discount', 'advanced-form-integration' ),
        'order_coupon_discount_formatted'=> __( 'Coupon Discount (Formatted)', 'advanced-form-integration' ),
        'order_tax_total'                => __( 'Order Tax Total', 'advanced-form-integration' ),
        'order_tax_total_formatted'      => __( 'Order Tax Total (Formatted)', 'advanced-form-integration' ),
        'order_total_paid'               => __( 'Order Total Paid', 'advanced-form-integration' ),
        'order_total_paid_formatted'     => __( 'Order Total Paid (Formatted)', 'advanced-form-integration' ),
        'order_balance_due'              => __( 'Order Balance Due', 'advanced-form-integration' ),
        'order_balance_due_formatted'    => __( 'Order Balance Due (Formatted)', 'advanced-form-integration' ),
        'order_customer_comment'         => __( 'Order Customer Comment', 'advanced-form-integration' ),
        'order_source_url'               => __( 'Order Source URL', 'advanced-form-integration' ),
        'order_source_id'                => __( 'Order Source ID', 'advanced-form-integration' ),
        'order_manage_url_customer'      => __( 'Manage Order URL (Customer)', 'advanced-form-integration' ),
        'order_price_breakdown_json'     => __( 'Order Price Breakdown (JSON)', 'advanced-form-integration' ),
        'order_transactions_json'        => __( 'Order Transactions (JSON)', 'advanced-form-integration' ),
    );
}

function adfoin_latepoint_handle_booking_created( $booking ) {
    if ( ! adfoin_latepoint_is_ready() ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'latepoint', 'bookingCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = adfoin_latepoint_prepare_booking_payload( $booking );

    if ( empty( $payload ) ) {
        return;
    }

    $payload['event_type'] = 'bookingCreated';

    $integration->send( $saved_records, $payload );
}

function adfoin_latepoint_handle_booking_updated( $booking, $old_booking = null, $initiated_by = '' ) {
    if ( ! adfoin_latepoint_is_ready() ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'latepoint', 'bookingUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = adfoin_latepoint_prepare_booking_payload( $booking, $old_booking );

    if ( empty( $payload ) ) {
        return;
    }

    $payload['event_type'] = 'bookingUpdated';

    $integration->send( $saved_records, $payload );
}

function adfoin_latepoint_prepare_booking_payload( $booking, $old_booking = null ) {
    $booking     = adfoin_latepoint_resolve_booking_model( $booking );
    $old_booking = adfoin_latepoint_resolve_booking_model( $old_booking );

    if ( ! $booking ) {
        return array();
    }

    $order    = ( isset( $booking->order ) && $booking->order instanceof OsOrderModel ) ? $booking->order : new OsOrderModel( $booking->order_id );
    $customer = ( isset( $booking->customer ) && $booking->customer instanceof OsCustomerModel ) ? $booking->customer : ( $booking->customer_id ? new OsCustomerModel( $booking->customer_id ) : null );
    $service  = ( isset( $booking->service ) && $booking->service instanceof OsServiceModel ) ? $booking->service : ( $booking->service_id ? new OsServiceModel( $booking->service_id ) : null );
    $agent    = ( isset( $booking->agent ) && $booking->agent instanceof OsAgentModel ) ? $booking->agent : ( $booking->agent_id ? new OsAgentModel( $booking->agent_id ) : null );
    $location = ( isset( $booking->location ) && $booking->location instanceof OsLocationModel ) ? $booking->location : ( $booking->location_id ? new OsLocationModel( $booking->location_id ) : null );

    $order_subtotal_pair  = adfoin_latepoint_prepare_money_pair( $order->subtotal ?? '' );
    $order_total_pair     = adfoin_latepoint_prepare_money_pair( $order->total ?? '' );
    $order_tax_pair       = adfoin_latepoint_prepare_money_pair( $order->tax_total ?? '' );
    $coupon_discount_pair = adfoin_latepoint_prepare_money_pair( $order->coupon_discount ?? '' );
    $total_paid_pair      = adfoin_latepoint_prepare_money_pair( method_exists( $order, 'get_total_amount_paid_from_transactions' ) ? $order->get_total_amount_paid_from_transactions() : '' );
    $balance_pair         = adfoin_latepoint_prepare_money_pair( max( $order_total_pair['float'] - $total_paid_pair['float'], 0 ) );

    $service_price_min_pair    = adfoin_latepoint_prepare_money_pair( $service->price_min ?? '' );
    $service_price_max_pair    = adfoin_latepoint_prepare_money_pair( $service->price_max ?? '' );
    $service_charge_pair       = adfoin_latepoint_prepare_money_pair( $service->charge_amount ?? '' );
    $service_deposit_pair      = adfoin_latepoint_prepare_money_pair( $service->deposit_amount ?? '' );

    $location_category_name = '';
    if ( $location && method_exists( $location, 'generate_data_vars' ) ) {
        $location_vars        = $location->generate_data_vars();
        $location_category_name = $location_vars['category']['name'] ?? '';
    }

    $service_category_name = '';
    if ( $service && method_exists( $service, 'get_category_name' ) ) {
        $service_category_name = $service->get_category_name();
    }

    $customer_timezone = '';
    if ( $customer && method_exists( $customer, 'get_selected_timezone_name' ) ) {
        $customer_timezone = $customer->get_selected_timezone_name();
    }

    $transactions_payload = array();
    if ( method_exists( $order, 'get_transactions' ) ) {
        foreach ( $order->get_transactions() as $transaction ) {
            if ( is_object( $transaction ) && method_exists( $transaction, 'generate_data_vars' ) ) {
                $transactions_payload[] = $transaction->generate_data_vars();
            }
        }
    }

    $price_breakdown = array();
    if ( ! empty( $order->price_breakdown ) ) {
        $decoded_breakdown = json_decode( $order->price_breakdown, true );
        if ( is_array( $decoded_breakdown ) ) {
            $price_breakdown = $decoded_breakdown;
        }
    }

    $payload = array(
        'event_type'                     => '',
        'booking_id'                     => adfoin_latepoint_normalize_value( $booking->id ),
        'booking_code'                   => adfoin_latepoint_normalize_value( $booking->booking_code ?? '' ),
        'booking_status'                 => adfoin_latepoint_normalize_value( $booking->status ?? '' ),
        'booking_previous_status'        => adfoin_latepoint_normalize_value( $old_booking ? $old_booking->status : '' ),
        'booking_total_attendees'        => adfoin_latepoint_normalize_value( $booking->total_attendees ?? '' ),
        'booking_duration'               => adfoin_latepoint_normalize_value( $booking->duration ?? '' ),
        'booking_buffer_before'          => adfoin_latepoint_normalize_value( $booking->buffer_before ?? '' ),
        'booking_buffer_after'           => adfoin_latepoint_normalize_value( $booking->buffer_after ?? '' ),
        'booking_start_date'             => adfoin_latepoint_normalize_value( method_exists( $booking, 'format_start_date' ) ? $booking->format_start_date() : '' ),
        'booking_start_time'             => adfoin_latepoint_normalize_value( isset( $booking->start_time ) ? OsTimeHelper::minutes_to_hours_and_minutes( $booking->start_time ) : '' ),
        'booking_start_time_24h'         => adfoin_latepoint_normalize_value( isset( $booking->start_time ) ? OsTimeHelper::minutes_to_army_hours_and_minutes( $booking->start_time ) : '' ),
        'booking_start_datetime'         => adfoin_latepoint_normalize_value( method_exists( $booking, 'format_start_date_and_time' ) ? $booking->format_start_date_and_time( OsSettingsHelper::get_readable_datetime_format() ) : '' ),
        'booking_start_datetime_rfc3339' => adfoin_latepoint_normalize_value( method_exists( $booking, 'format_start_date_and_time_rfc3339' ) ? $booking->format_start_date_and_time_rfc3339() : '' ),
        'booking_start_datetime_utc'     => adfoin_latepoint_normalize_value( $booking->start_datetime_utc ?? '' ),
        'booking_end_date'               => adfoin_latepoint_normalize_value( method_exists( $booking, 'format_end_date_and_time' ) ? $booking->format_end_date_and_time( OsSettingsHelper::get_date_format() ) : '' ),
        'booking_end_time'               => adfoin_latepoint_normalize_value( isset( $booking->end_time ) ? OsTimeHelper::minutes_to_hours_and_minutes( $booking->end_time ) : '' ),
        'booking_end_time_24h'           => adfoin_latepoint_normalize_value( isset( $booking->end_time ) ? OsTimeHelper::minutes_to_army_hours_and_minutes( $booking->end_time ) : '' ),
        'booking_end_datetime'           => adfoin_latepoint_normalize_value( method_exists( $booking, 'format_end_date_and_time' ) ? $booking->format_end_date_and_time( OsSettingsHelper::get_readable_datetime_format() ) : '' ),
        'booking_end_datetime_rfc3339'   => adfoin_latepoint_normalize_value( method_exists( $booking, 'format_end_date_and_time_rfc3339' ) ? $booking->format_end_date_and_time_rfc3339() : '' ),
        'booking_end_datetime_utc'       => adfoin_latepoint_normalize_value( $booking->end_datetime_utc ?? '' ),
        'booking_timezone'               => adfoin_latepoint_normalize_value( OsTimeHelper::get_wp_timezone_name() ),
        'booking_time_status'            => adfoin_latepoint_normalize_value( method_exists( $booking, 'time_status' ) ? $booking->time_status() : '' ),
        'booking_customer_comment'       => adfoin_latepoint_normalize_value( $order->customer_comment ?? '' ),
        'booking_created_at'             => adfoin_latepoint_normalize_value( $booking->created_at ?? '' ),
        'booking_updated_at'             => adfoin_latepoint_normalize_value( $booking->updated_at ?? '' ),
        'booking_manage_url_agent'       => adfoin_latepoint_normalize_value( class_exists( 'OsBookingHelper' ) ? OsBookingHelper::generate_direct_manage_booking_url( $booking, 'agent' ) : '' ),
        'booking_manage_url_customer'    => adfoin_latepoint_normalize_value( class_exists( 'OsBookingHelper' ) ? OsBookingHelper::generate_direct_manage_booking_url( $booking, 'customer' ) : '' ),
        'booking_is_bundle'              => adfoin_latepoint_normalize_value( $booking->is_part_of_bundle() ? 'true' : 'false' ),
        'booking_is_upcoming'            => adfoin_latepoint_normalize_value( $booking->is_upcoming() ? 'true' : 'false' ),
        'booking_total_price_formatted'  => adfoin_latepoint_normalize_value( method_exists( $booking, 'get_formatted_price' ) ? $booking->get_formatted_price() : '' ),
        'booking_order_item_id'          => adfoin_latepoint_normalize_value( $booking->order_item_id ?? '' ),
        'booking_recurrence_id'          => adfoin_latepoint_normalize_value( $booking->recurrence_id ?? '' ),
        'booking_payload_json'           => adfoin_latepoint_normalize_value( method_exists( $booking, 'generate_data_vars' ) ? $booking->generate_data_vars() : array() ),
        'customer_id'                    => adfoin_latepoint_normalize_value( $customer->id ?? '' ),
        'customer_uuid'                  => adfoin_latepoint_normalize_value( $customer->uuid ?? '' ),
        'customer_first_name'            => adfoin_latepoint_normalize_value( $customer->first_name ?? '' ),
        'customer_last_name'             => adfoin_latepoint_normalize_value( $customer->last_name ?? '' ),
        'customer_full_name'             => adfoin_latepoint_normalize_value( isset( $customer->full_name ) ? $customer->full_name : trim( ( $customer->first_name ?? '' ) . ' ' . ( $customer->last_name ?? '' ) ) ),
        'customer_email'                 => adfoin_latepoint_normalize_value( $customer->email ?? '' ),
        'customer_phone'                 => adfoin_latepoint_normalize_value( $customer->phone ?? '' ),
        'customer_timezone'              => adfoin_latepoint_normalize_value( $customer_timezone ),
        'customer_notes'                 => adfoin_latepoint_normalize_value( $customer->notes ?? '' ),
        'customer_admin_notes'           => adfoin_latepoint_normalize_value( $customer->admin_notes ?? '' ),
        'customer_is_guest'              => adfoin_latepoint_normalize_value( isset( $customer->is_guest ) ? ( $customer->is_guest ? 'true' : 'false' ) : '' ),
        'customer_wordpress_user_id'     => adfoin_latepoint_normalize_value( $customer->wordpress_user_id ?? '' ),
        'service_id'                     => adfoin_latepoint_normalize_value( $service->id ?? '' ),
        'service_name'                   => adfoin_latepoint_normalize_value( $service->name ?? '' ),
        'service_duration'               => adfoin_latepoint_normalize_value( $service->duration ?? '' ),
        'service_duration_name'          => adfoin_latepoint_normalize_value( $service->duration_name ?? '' ),
        'service_price_min'              => $service_price_min_pair['raw'],
        'service_price_min_formatted'    => $service_price_min_pair['formatted'],
        'service_price_max'              => $service_price_max_pair['raw'],
        'service_price_max_formatted'    => $service_price_max_pair['formatted'],
        'service_charge_amount'          => $service_charge_pair['raw'],
        'service_charge_amount_formatted'=> $service_charge_pair['formatted'],
        'service_deposit_amount'         => $service_deposit_pair['raw'],
        'service_deposit_amount_formatted'=> $service_deposit_pair['formatted'],
        'service_buffer_before'          => adfoin_latepoint_normalize_value( $service->buffer_before ?? '' ),
        'service_buffer_after'           => adfoin_latepoint_normalize_value( $service->buffer_after ?? '' ),
        'service_category_name'          => adfoin_latepoint_normalize_value( $service_category_name ),
        'agent_id'                       => adfoin_latepoint_normalize_value( $agent->id ?? '' ),
        'agent_first_name'               => adfoin_latepoint_normalize_value( $agent->first_name ?? '' ),
        'agent_last_name'                => adfoin_latepoint_normalize_value( $agent->last_name ?? '' ),
        'agent_full_name'                => adfoin_latepoint_normalize_value( $agent->full_name ?? '' ),
        'agent_display_name'             => adfoin_latepoint_normalize_value( $agent->display_name ?? '' ),
        'agent_email'                    => adfoin_latepoint_normalize_value( $agent->email ?? '' ),
        'agent_phone'                    => adfoin_latepoint_normalize_value( $agent->phone ?? '' ),
        'location_id'                    => adfoin_latepoint_normalize_value( $location->id ?? '' ),
        'location_name'                  => adfoin_latepoint_normalize_value( $location->name ?? '' ),
        'location_full_address'          => adfoin_latepoint_normalize_value( $location->full_address ?? '' ),
        'location_category_name'         => adfoin_latepoint_normalize_value( $location_category_name ),
        'order_id'                       => adfoin_latepoint_normalize_value( $order->id ?? '' ),
        'order_confirmation_code'        => adfoin_latepoint_normalize_value( $order->confirmation_code ?? '' ),
        'order_status'                   => adfoin_latepoint_normalize_value( $order->status ?? '' ),
        'order_payment_status'           => adfoin_latepoint_normalize_value( $order->payment_status ?? '' ),
        'order_fulfillment_status'       => adfoin_latepoint_normalize_value( $order->fulfillment_status ?? '' ),
        'order_subtotal'                 => $order_subtotal_pair['raw'],
        'order_subtotal_formatted'       => $order_subtotal_pair['formatted'],
        'order_total'                    => $order_total_pair['raw'],
        'order_total_formatted'          => $order_total_pair['formatted'],
        'order_coupon_code'              => adfoin_latepoint_normalize_value( $order->coupon_code ?? '' ),
        'order_coupon_discount'          => $coupon_discount_pair['raw'],
        'order_coupon_discount_formatted'=> $coupon_discount_pair['formatted'],
        'order_tax_total'                => $order_tax_pair['raw'],
        'order_tax_total_formatted'      => $order_tax_pair['formatted'],
        'order_total_paid'               => $total_paid_pair['raw'],
        'order_total_paid_formatted'     => $total_paid_pair['formatted'],
        'order_balance_due'              => $balance_pair['raw'],
        'order_balance_due_formatted'    => $balance_pair['formatted'],
        'order_customer_comment'         => adfoin_latepoint_normalize_value( $order->customer_comment ?? '' ),
        'order_source_url'               => adfoin_latepoint_normalize_value( $order->source_url ?? '' ),
        'order_source_id'                => adfoin_latepoint_normalize_value( $order->source_id ?? '' ),
        'order_manage_url_customer'      => adfoin_latepoint_normalize_value( method_exists( $order, 'manage_by_key_url' ) ? $order->manage_by_key_url( 'customer' ) : '' ),
        'order_price_breakdown_json'     => adfoin_latepoint_normalize_value( $price_breakdown ),
        'order_transactions_json'        => adfoin_latepoint_normalize_value( $transactions_payload ),
    );

    return $payload;
}

function adfoin_latepoint_resolve_booking_model( $booking ) {
    if ( empty( $booking ) ) {
        return null;
    }

    if ( is_numeric( $booking ) ) {
        $booking = new OsBookingModel( absint( $booking ) );
    }

    if ( ! ( $booking instanceof OsBookingModel ) ) {
        return null;
    }

    if ( empty( $booking->id ) && isset( $booking->id ) ) {
        $booking = new OsBookingModel( $booking->id );
    }

    if ( empty( $booking->id ) ) {
        return null;
    }

    if ( $booking->customer_id && ( ! isset( $booking->customer ) || ! ( $booking->customer instanceof OsCustomerModel ) ) ) {
        $booking->customer = new OsCustomerModel( $booking->customer_id );
    }

    if ( $booking->service_id && ( ! isset( $booking->service ) || ! ( $booking->service instanceof OsServiceModel ) ) ) {
        $booking->service = new OsServiceModel( $booking->service_id );
    }

    if ( $booking->agent_id && ( ! isset( $booking->agent ) || ! ( $booking->agent instanceof OsAgentModel ) ) ) {
        $booking->agent = new OsAgentModel( $booking->agent_id );
    }

    if ( $booking->location_id && ( ! isset( $booking->location ) || ! ( $booking->location instanceof OsLocationModel ) ) ) {
        $booking->location = new OsLocationModel( $booking->location_id );
    }

    if ( $booking->order_id && ( ! isset( $booking->order ) || ! ( $booking->order instanceof OsOrderModel ) ) ) {
        $booking->order = new OsOrderModel( $booking->order_id );
    }

    return $booking;
}

function adfoin_latepoint_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( null === $value || '' === $value ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

function adfoin_latepoint_prepare_money_pair( $amount ) {
    $has_value = ( '' !== $amount && null !== $amount );
    $float     = is_numeric( $amount ) ? (float) $amount : 0.0;
    $raw       = $has_value ? number_format( $float, 2, '.', '' ) : '';

    return array(
        'raw'       => $raw,
        'formatted' => ( $has_value && class_exists( 'OsMoneyHelper' ) ) ? OsMoneyHelper::format_price( $float ) : ( $has_value ? $raw : '' ),
        'float'     => $has_value ? $float : 0.0,
    );
}
