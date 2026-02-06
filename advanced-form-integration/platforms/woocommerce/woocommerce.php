<?php

add_filter( 'adfoin_action_providers', 'adfoin_woocommerce_actions', 10, 1 );

function adfoin_woocommerce_actions( $actions ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return $actions;
    }

    $actions['woocommerce'] = array(
        'title' => __( 'WooCommerce', 'advanced-form-integration' ),
        'tasks' => array(
            'create_customer'     => __( 'Create Customer', 'advanced-form-integration' ),
            'create_order'        => __( 'Create Order', 'advanced-form-integration' ),
            'create_subscription' => __( 'Create Subscription', 'advanced-form-integration' ),
            'create_booking'      => __( 'Create Booking', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_woocommerce_action_fields' );

function adfoin_woocommerce_action_fields() {
    ?>
    <script type="text/template" id="woocommerce-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_customer'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Email is required. Username/password are optional—WooCommerce will auto-generate them when left blank. Map any billing/shipping fields to store on the customer profile.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_order'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a customer (ID or email) and order details. Line items, shipping, fees, and coupons accept JSON arrays matching WooCommerce REST payloads.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_subscription'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Requires WooCommerce Subscriptions. Set billing period/interval, schedule, customer, and supply subscription line items JSON.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'create_booking'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Requires WooCommerce Bookings. Supply a bookable product, schedule, and optional resource/person settings. When customer details are provided, they will be attached to the booking.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field
                v-for="field in fields"
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

add_action( 'adfoin_woocommerce_job_queue', 'adfoin_woocommerce_job_queue', 10, 1 );

function adfoin_woocommerce_job_queue( $data ) {
    adfoin_woocommerce_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_woocommerce_send_data( $record, $posted_data ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        adfoin_woocommerce_action_log( $record, __( 'WooCommerce is not active.', 'advanced-form-integration' ), array(), false );
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
        case 'create_customer':
            adfoin_woocommerce_action_create_customer( $record, $parsed );
            break;
        case 'create_order':
            adfoin_woocommerce_action_create_order( $record, $parsed );
            break;
        case 'create_subscription':
            adfoin_woocommerce_action_create_subscription( $record, $parsed );
            break;
        case 'create_booking':
            adfoin_woocommerce_action_create_booking( $record, $parsed );
            break;
        default:
            adfoin_woocommerce_action_log( $record, __( 'Unsupported WooCommerce task.', 'advanced-form-integration' ), array( 'task' => $task ), false );
    }
}

function adfoin_woocommerce_action_create_customer( $record, $parsed ) {
    $email = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';

    if ( empty( $email ) || ! is_email( $email ) ) {
        adfoin_woocommerce_action_log( $record, __( 'Valid customer email is required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $username = isset( $parsed['username'] ) ? sanitize_user( $parsed['username'], true ) : '';
    $password = isset( $parsed['password'] ) ? $parsed['password'] : '';

    $customer_id = wc_create_new_customer( $email, $username ?: '', $password );

    if ( is_wp_error( $customer_id ) ) {
        adfoin_woocommerce_action_log( $record, $customer_id->get_error_message(), $parsed, false );
        return;
    }

    $user_id = absint( $customer_id );

    $profile_keys = array(
        'first_name'    => 'first_name',
        'last_name'     => 'last_name',
        'display_name'  => 'display_name',
        'customer_note' => 'description',
    );

    foreach ( $profile_keys as $field_key => $user_key ) {
        if ( isset( $parsed[ $field_key ] ) && '' !== $parsed[ $field_key ] ) {
            if ( 'display_name' === $user_key ) {
                wp_update_user(
                    array(
                        'ID'           => $user_id,
                        'display_name' => sanitize_text_field( $parsed[ $field_key ] ),
                    )
                );
            } else {
                update_user_meta( $user_id, $user_key, sanitize_text_field( $parsed[ $field_key ] ) );
            }
        }
    }

    $billing_fields  = adfoin_woocommerce_extract_address_fields( $parsed, 'billing' );
    $shipping_fields = adfoin_woocommerce_extract_address_fields( $parsed, 'shipping' );

    foreach ( $billing_fields as $meta_key => $value ) {
        update_user_meta( $user_id, 'billing_' . $meta_key, $value );
    }

    foreach ( $shipping_fields as $meta_key => $value ) {
        update_user_meta( $user_id, 'shipping_' . $meta_key, $value );
    }

    if ( isset( $parsed['role'] ) && '' !== $parsed['role'] ) {
        $role = sanitize_text_field( $parsed['role'] );
        $user = get_userdata( $user_id );

        if ( $user && wp_roles()->is_role( $role ) ) {
            $user->set_role( $role );
        }
    }

    adfoin_woocommerce_action_log(
        $record,
        __( 'WooCommerce customer created successfully.', 'advanced-form-integration' ),
        array(
            'customer_id' => $user_id,
            'email'       => $email,
        ),
        true
    );
}

function adfoin_woocommerce_action_create_order( $record, $parsed ) {
    if ( ! function_exists( 'wc_create_order' ) ) {
        adfoin_woocommerce_action_log( $record, __( 'WooCommerce order API unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $order_args = array();

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        $order_args['status'] = sanitize_key( $parsed['status'] );
    }

    $order = wc_create_order( $order_args );

    if ( is_wp_error( $order ) ) {
        adfoin_woocommerce_action_log( $record, $order->get_error_message(), $parsed, false );
        return;
    }

    $customer_id = isset( $parsed['customer_id'] ) ? absint( $parsed['customer_id'] ) : 0;

    if ( $customer_id ) {
        $order->set_customer_id( $customer_id );
    }

    if ( isset( $parsed['customer_note'] ) ) {
        $order->set_customer_note( sanitize_textarea_field( $parsed['customer_note'] ) );
    }

    $billing_address  = adfoin_woocommerce_collect_address( $parsed, 'billing' );
    $shipping_address = adfoin_woocommerce_collect_address( $parsed, 'shipping' );

    if ( ! empty( $billing_address ) ) {
        $order->set_address( $billing_address, 'billing' );
    }

    if ( ! empty( $shipping_address ) ) {
        $order->set_address( $shipping_address, 'shipping' );
    }

    if ( isset( $parsed['customer_email'] ) && '' !== $parsed['customer_email'] ) {
        $order->set_billing_email( sanitize_email( $parsed['customer_email'] ) );
    }

    if ( isset( $parsed['customer_phone'] ) && '' !== $parsed['customer_phone'] ) {
        $order->set_billing_phone( sanitize_text_field( $parsed['customer_phone'] ) );
    }

    adfoin_woocommerce_attach_line_items( $order, isset( $parsed['line_items_json'] ) ? $parsed['line_items_json'] : '' );
    adfoin_woocommerce_attach_shipping_lines( $order, isset( $parsed['shipping_lines_json'] ) ? $parsed['shipping_lines_json'] : '' );
    adfoin_woocommerce_attach_fee_lines( $order, isset( $parsed['fee_lines_json'] ) ? $parsed['fee_lines_json'] : '' );
    adfoin_woocommerce_attach_coupon_lines( $order, isset( $parsed['coupon_lines_json'] ) ? $parsed['coupon_lines_json'] : '' );

    if ( isset( $parsed['payment_method'] ) && '' !== $parsed['payment_method'] ) {
        $order->set_payment_method( sanitize_text_field( $parsed['payment_method'] ) );
    }

    if ( isset( $parsed['payment_method_title'] ) && '' !== $parsed['payment_method_title'] ) {
        $order->set_payment_method_title( sanitize_text_field( $parsed['payment_method_title'] ) );
    }

    if ( isset( $parsed['transaction_id'] ) && '' !== $parsed['transaction_id'] ) {
        $order->set_transaction_id( sanitize_text_field( $parsed['transaction_id'] ) );
    }

    if ( isset( $parsed['shipping_total'] ) && '' !== $parsed['shipping_total'] ) {
        $order->set_shipping_total( floatval( $parsed['shipping_total'] ) );
    }

    if ( isset( $parsed['discount_total'] ) && '' !== $parsed['discount_total'] ) {
        $order->set_discount_total( floatval( $parsed['discount_total'] ) );
    }

    if ( isset( $parsed['discount_tax'] ) && '' !== $parsed['discount_tax'] ) {
        $order->set_discount_tax( floatval( $parsed['discount_tax'] ) );
    }

    if ( isset( $parsed['shipping_tax'] ) && '' !== $parsed['shipping_tax'] ) {
        $order->set_shipping_tax( floatval( $parsed['shipping_tax'] ) );
    }

    if ( isset( $parsed['cart_tax'] ) && '' !== $parsed['cart_tax'] ) {
        $order->set_cart_tax( floatval( $parsed['cart_tax'] ) );
    }

    $order->calculate_totals( false );

    if ( isset( $parsed['total'] ) && '' !== $parsed['total'] ) {
        $order->set_total( floatval( $parsed['total'] ) );
    }

    $set_paid = adfoin_woocommerce_is_truthy( isset( $parsed['set_paid'] ) ? $parsed['set_paid'] : '' );

    if ( $set_paid ) {
        $order->payment_complete( isset( $parsed['transaction_id'] ) ? sanitize_text_field( $parsed['transaction_id'] ) : '' );
    }

    $order->save();

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        $order->update_status( sanitize_key( $parsed['status'] ) );
    }

    if ( isset( $parsed['order_note'] ) && '' !== $parsed['order_note'] ) {
        $order->add_order_note( sanitize_textarea_field( $parsed['order_note'] ) );
    }

    adfoin_woocommerce_action_log(
        $record,
        __( 'WooCommerce order created successfully.', 'advanced-form-integration' ),
        array(
            'order_id' => $order->get_id(),
        ),
        true
    );
}

function adfoin_woocommerce_action_create_subscription( $record, $parsed ) {
    if ( ! function_exists( 'wcs_create_subscription' ) ) {
        adfoin_woocommerce_action_log( $record, __( 'WooCommerce Subscriptions is not active.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $customer_id = isset( $parsed['customer_id'] ) ? absint( $parsed['customer_id'] ) : 0;

    if ( ! $customer_id ) {
        adfoin_woocommerce_action_log( $record, __( 'Customer ID is required for subscriptions.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $schedule_args = array(
        'start_date'   => adfoin_woocommerce_parse_date( isset( $parsed['start_date'] ) ? $parsed['start_date'] : '' ),
        'trial_end'    => adfoin_woocommerce_parse_date( isset( $parsed['trial_end'] ) ? $parsed['trial_end'] : '' ),
        'next_payment' => adfoin_woocommerce_parse_date( isset( $parsed['next_payment'] ) ? $parsed['next_payment'] : '' ),
        'end_date'     => adfoin_woocommerce_parse_date( isset( $parsed['end_date'] ) ? $parsed['end_date'] : '' ),
    );

    $subscription_args = array(
        'customer_id'      => $customer_id,
        'status'           => isset( $parsed['status'] ) && '' !== $parsed['status'] ? sanitize_key( $parsed['status'] ) : 'pending',
        'billing_period'   => isset( $parsed['billing_period'] ) ? sanitize_key( $parsed['billing_period'] ) : 'month',
        'billing_interval' => isset( $parsed['billing_interval'] ) && '' !== $parsed['billing_interval'] ? absint( $parsed['billing_interval'] ) : 1,
    );

    if ( isset( $parsed['currency'] ) && '' !== $parsed['currency'] ) {
        $subscription_args['currency'] = strtoupper( sanitize_text_field( $parsed['currency'] ) );
    }

    if ( ! empty( array_filter( $schedule_args ) ) ) {
        $subscription_args['schedule'] = array_filter( $schedule_args );
    }

    $subscription = wcs_create_subscription( $subscription_args );

    if ( is_wp_error( $subscription ) || ! $subscription instanceof WC_Subscription ) {
        $message = is_wp_error( $subscription ) ? $subscription->get_error_message() : __( 'Failed to create subscription.', 'advanced-form-integration' );
        adfoin_woocommerce_action_log( $record, $message, $parsed, false );
        return;
    }

    adfoin_woocommerce_attach_line_items( $subscription, isset( $parsed['line_items_json'] ) ? $parsed['line_items_json'] : '' );
    adfoin_woocommerce_attach_shipping_lines( $subscription, isset( $parsed['shipping_lines_json'] ) ? $parsed['shipping_lines_json'] : '' );
    adfoin_woocommerce_attach_fee_lines( $subscription, isset( $parsed['fee_lines_json'] ) ? $parsed['fee_lines_json'] : '' );
    adfoin_woocommerce_attach_coupon_lines( $subscription, isset( $parsed['coupon_lines_json'] ) ? $parsed['coupon_lines_json'] : '' );

    $billing_address  = adfoin_woocommerce_collect_address( $parsed, 'billing' );
    $shipping_address = adfoin_woocommerce_collect_address( $parsed, 'shipping' );

    if ( ! empty( $billing_address ) ) {
        $subscription->set_address( $billing_address, 'billing' );
    }

    if ( ! empty( $shipping_address ) ) {
        $subscription->set_address( $shipping_address, 'shipping' );
    }

    if ( isset( $parsed['payment_method'] ) && '' !== $parsed['payment_method'] ) {
        $subscription->set_payment_method( sanitize_text_field( $parsed['payment_method'] ) );
    }

    $requires_manual = adfoin_woocommerce_is_truthy( isset( $parsed['requires_manual_renewal'] ) ? $parsed['requires_manual_renewal'] : '' );
    $subscription->set_requires_manual_renewal( $requires_manual );

    $totals = array(
        'total'          => 'set_total',
        'discount_total' => 'set_discount_total',
        'discount_tax'   => 'set_discount_tax',
        'shipping_total' => 'set_shipping_total',
        'shipping_tax'   => 'set_shipping_tax',
        'cart_tax'       => 'set_cart_tax',
    );

    foreach ( $totals as $input_key => $method ) {
        if ( isset( $parsed[ $input_key ] ) && '' !== $parsed[ $input_key ] && method_exists( $subscription, $method ) ) {
            $subscription->{$method}( floatval( $parsed[ $input_key ] ) );
        }
    }

    if ( isset( $parsed['currency'] ) && '' !== $parsed['currency'] ) {
        $subscription->set_currency( strtoupper( sanitize_text_field( $parsed['currency'] ) ) );
    }

    $subscription->calculate_totals();
    $subscription->save();

    if ( isset( $parsed['status'] ) && '' !== $parsed['status'] ) {
        $subscription->update_status( sanitize_key( $parsed['status'] ) );
    }

    if ( isset( $parsed['subscription_note'] ) && '' !== $parsed['subscription_note'] ) {
        $subscription->add_order_note( sanitize_textarea_field( $parsed['subscription_note'] ) );
    }

    adfoin_woocommerce_action_log(
        $record,
        __( 'WooCommerce subscription created successfully.', 'advanced-form-integration' ),
        array(
            'subscription_id' => $subscription->get_id(),
        ),
        true
    );
}

function adfoin_woocommerce_action_create_booking( $record, $parsed ) {
    if ( ! class_exists( 'WC_Booking' ) ) {
        adfoin_woocommerce_action_log( $record, __( 'WooCommerce Bookings is not active.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $product_id = isset( $parsed['product_id'] ) ? absint( $parsed['product_id'] ) : 0;

    if ( ! $product_id ) {
        adfoin_woocommerce_action_log( $record, __( 'Bookable product ID is required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $start_timestamp = adfoin_woocommerce_parse_datetime_to_timestamp( isset( $parsed['start_date'] ) ? $parsed['start_date'] : '' );
    $end_timestamp   = adfoin_woocommerce_parse_datetime_to_timestamp( isset( $parsed['end_date'] ) ? $parsed['end_date'] : '' );

    if ( ! $start_timestamp || ! $end_timestamp ) {
        adfoin_woocommerce_action_log( $record, __( 'Valid start and end dates are required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $booking = new WC_Booking();
    $booking->set_product_id( $product_id );
    $booking->set_start( $start_timestamp );
    $booking->set_end( $end_timestamp );

    if ( isset( $parsed['resource_id'] ) && '' !== $parsed['resource_id'] ) {
        $booking->set_resource_id( absint( $parsed['resource_id'] ) );
    }

    if ( isset( $parsed['quantity'] ) && '' !== $parsed['quantity'] ) {
        $booking->set_persons( array( 0 => absint( $parsed['quantity'] ) ) );
    }

    if ( isset( $parsed['person_ids_json'] ) && '' !== $parsed['person_ids_json'] ) {
        $persons = json_decode( $parsed['person_ids_json'], true );
        if ( is_array( $persons ) ) {
            $clean = array();
            foreach ( $persons as $person_id => $quantity ) {
                $clean[ absint( $person_id ) ] = absint( $quantity );
            }
            if ( ! empty( $clean ) ) {
                $booking->set_persons( $clean );
            }
        }
    }

    if ( isset( $parsed['all_day'] ) && '' !== $parsed['all_day'] ) {
        $booking->set_all_day( adfoin_woocommerce_is_truthy( $parsed['all_day'] ) );
    }

    if ( isset( $parsed['customer_id'] ) && '' !== $parsed['customer_id'] ) {
        $booking->set_customer_id( absint( $parsed['customer_id'] ) );
    }

    if ( isset( $parsed['order_status'] ) && '' !== $parsed['order_status'] ) {
        $booking->set_status( sanitize_key( $parsed['order_status'] ) );
    }

    if ( isset( $parsed['pricing_base_cost'] ) && '' !== $parsed['pricing_base_cost'] && method_exists( $booking, 'set_base_cost' ) ) {
        $booking->set_base_cost( floatval( $parsed['pricing_base_cost'] ) );
    }

    if ( isset( $parsed['pricing_block_cost'] ) && '' !== $parsed['pricing_block_cost'] && method_exists( $booking, 'set_block_cost' ) ) {
        $booking->set_block_cost( floatval( $parsed['pricing_block_cost'] ) );
    }

    if ( isset( $parsed['pricing_display_cost'] ) && '' !== $parsed['pricing_display_cost'] && method_exists( $booking, 'set_display_cost' ) ) {
        $booking->set_display_cost( floatval( $parsed['pricing_display_cost'] ) );
    }

    if ( isset( $parsed['customer_name'] ) && '' !== $parsed['customer_name'] ) {
        $booking->set_customer_name( sanitize_text_field( $parsed['customer_name'] ) );
    }

    if ( isset( $parsed['customer_email'] ) && '' !== $parsed['customer_email'] ) {
        $booking->set_customer_email( sanitize_email( $parsed['customer_email'] ) );
    }

    if ( isset( $parsed['customer_phone'] ) && '' !== $parsed['customer_phone'] ) {
        $booking->update_meta_data( '_booking_customer_phone', sanitize_text_field( $parsed['customer_phone'] ) );
    }

    if ( isset( $parsed['booking_note'] ) && '' !== $parsed['booking_note'] ) {
        $booking->set_customer_note( sanitize_textarea_field( $parsed['booking_note'] ) );
    }

    if ( isset( $parsed['meta_json'] ) && '' !== $parsed['meta_json'] ) {
        $meta = json_decode( $parsed['meta_json'], true );
        if ( is_array( $meta ) ) {
            foreach ( $meta as $key => $value ) {
                $booking->update_meta_data( sanitize_key( $key ), $value );
            }
        }
    }

    $booking->save();

    if ( isset( $parsed['order_meta_json'] ) && '' !== $parsed['order_meta_json'] ) {
        $order_meta = json_decode( $parsed['order_meta_json'], true );
        if ( is_array( $order_meta ) ) {
            foreach ( $order_meta as $key => $value ) {
                $booking->update_meta_data( sanitize_key( $key ), $value );
            }
            $booking->save();
        }
    }

    if ( isset( $parsed['order_note'] ) && '' !== $parsed['order_note'] ) {
        $booking->add_order_note( sanitize_textarea_field( $parsed['order_note'] ) );
    }

    adfoin_woocommerce_action_log(
        $record,
        __( 'WooCommerce booking created successfully.', 'advanced-form-integration' ),
        array(
            'booking_id' => $booking->get_id(),
        ),
        true
    );
}

function adfoin_woocommerce_collect_address( $parsed, $type ) {
    $fields = array(
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'country',
        'email',
        'phone',
    );

    $address = array();

    foreach ( $fields as $field ) {
        $key = $type . '_' . $field;

        if ( isset( $parsed[ $key ] ) && '' !== $parsed[ $key ] ) {
            $value = 'email' === $field ? sanitize_email( $parsed[ $key ] ) : sanitize_text_field( $parsed[ $key ] );
            $address[ $field ] = $value;
        }
    }

    return $address;
}

function adfoin_woocommerce_extract_address_fields( $parsed, $type ) {
    $fields = array(
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'country',
        'email',
        'phone',
    );

    $values = array();

    foreach ( $fields as $field ) {
        $key = $type . '_' . $field;

        if ( isset( $parsed[ $key ] ) && '' !== $parsed[ $key ] ) {
            $values[ $field ] = 'email' === $field ? sanitize_email( $parsed[ $key ] ) : sanitize_text_field( $parsed[ $key ] );
        }
    }

    return $values;
}

function adfoin_woocommerce_attach_line_items( $order, $json ) {
    if ( empty( $json ) ) {
        return;
    }

    $items = json_decode( $json, true );

    if ( empty( $items ) || ! is_array( $items ) ) {
        return;
    }

    foreach ( $items as $item ) {
        $product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
        $variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
        $quantity     = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 1;
        $totals       = isset( $item['totals'] ) && is_array( $item['totals'] ) ? $item['totals'] : array();
        $args         = array();

        if ( ! empty( $totals ) ) {
            $args['totals'] = array_map( 'floatval', $totals );
        }

        if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
            $args['meta_data'] = $item['meta_data'];
        }

        if ( $product_id ) {
            $product = wc_get_product( $product_id );

            if ( $product ) {
                $order->add_product( $product, $quantity, array_merge( $args, array( 'variation_id' => $variation_id ) ) );
                continue;
            }
        }

        $order_item = new WC_Order_Item_Product();
        $name       = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : __( 'Custom Item', 'advanced-form-integration' );

        $order_item->set_name( $name );
        $order_item->set_quantity( $quantity );

        if ( isset( $totals['total'] ) ) {
            $order_item->set_total( floatval( $totals['total'] ) );
        }

        if ( isset( $totals['subtotal'] ) ) {
            $order_item->set_subtotal( floatval( $totals['subtotal'] ) );
        }

        if ( isset( $item['sku'] ) ) {
            $order_item->set_sku( sanitize_text_field( $item['sku'] ) );
        }

        if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
            foreach ( $item['meta_data'] as $meta ) {
                if ( isset( $meta['key'], $meta['value'] ) ) {
                    $order_item->add_meta_data( $meta['key'], $meta['value'], true );
                }
            }
        }

        $order->add_item( $order_item );
    }
}

function adfoin_woocommerce_attach_shipping_lines( $order, $json ) {
    if ( empty( $json ) ) {
        return;
    }

    $items = json_decode( $json, true );

    if ( empty( $items ) || ! is_array( $items ) ) {
        return;
    }

    foreach ( $items as $item ) {
        $shipping = new WC_Order_Item_Shipping();
        $shipping->set_method_title( isset( $item['method_title'] ) ? sanitize_text_field( $item['method_title'] ) : '' );
        $shipping->set_method_id( isset( $item['method_id'] ) ? sanitize_text_field( $item['method_id'] ) : '' );
        $shipping->set_total( isset( $item['total'] ) ? floatval( $item['total'] ) : 0 );
        $shipping->set_taxes( isset( $item['taxes'] ) ? $item['taxes'] : array() );

        if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
            foreach ( $item['meta_data'] as $meta ) {
                if ( isset( $meta['key'], $meta['value'] ) ) {
                    $shipping->add_meta_data( $meta['key'], $meta['value'], true );
                }
            }
        }

        $order->add_item( $shipping );
    }
}

function adfoin_woocommerce_attach_fee_lines( $order, $json ) {
    if ( empty( $json ) ) {
        return;
    }

    $items = json_decode( $json, true );

    if ( empty( $items ) || ! is_array( $items ) ) {
        return;
    }

    foreach ( $items as $item ) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name( isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : __( 'Fee', 'advanced-form-integration' ) );
        $fee->set_amount( isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0 );
        $fee->set_total( isset( $item['total'] ) ? floatval( $item['total'] ) : 0 );
        $fee->set_tax_class( isset( $item['tax_class'] ) ? sanitize_text_field( $item['tax_class'] ) : '' );
        $fee->set_taxes( isset( $item['taxes'] ) ? $item['taxes'] : array() );

        if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
            foreach ( $item['meta_data'] as $meta ) {
                if ( isset( $meta['key'], $meta['value'] ) ) {
                    $fee->add_meta_data( $meta['key'], $meta['value'], true );
                }
            }
        }

        $order->add_item( $fee );
    }
}

function adfoin_woocommerce_attach_coupon_lines( $order, $json ) {
    if ( empty( $json ) ) {
        return;
    }

    $items = json_decode( $json, true );

    if ( empty( $items ) || ! is_array( $items ) ) {
        return;
    }

    foreach ( $items as $item ) {
        $coupon = new WC_Order_Item_Coupon();
        $coupon->set_code( isset( $item['code'] ) ? sanitize_text_field( $item['code'] ) : '' );
        $coupon->set_discount( isset( $item['discount'] ) ? floatval( $item['discount'] ) : 0 );
        $coupon->set_discount_tax( isset( $item['discount_tax'] ) ? floatval( $item['discount_tax'] ) : 0 );

        if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
            foreach ( $item['meta_data'] as $meta ) {
                if ( isset( $meta['key'], $meta['value'] ) ) {
                    $coupon->add_meta_data( $meta['key'], $meta['value'], true );
                }
            }
        }

        $order->add_item( $coupon );
    }
}

function adfoin_woocommerce_parse_date( $value ) {
    if ( empty( $value ) ) {
        return '';
    }

    $timestamp = strtotime( $value );

    if ( false === $timestamp ) {
        return '';
    }

    return gmdate( 'Y-m-d H:i:s', $timestamp );
}

function adfoin_woocommerce_parse_datetime_to_timestamp( $value ) {
    if ( empty( $value ) ) {
        return 0;
    }

    $timestamp = strtotime( $value );

    if ( false === $timestamp ) {
        return 0;
    }

    return $timestamp;
}

function adfoin_woocommerce_is_truthy( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function adfoin_woocommerce_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'woocommerce', $log_args, $record );
}
