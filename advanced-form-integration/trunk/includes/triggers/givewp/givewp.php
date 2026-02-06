<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get GiveWP triggers.
 *
 * @param string $form_provider Integration provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_givewp_get_forms( $form_provider ) {
    if ( 'givewp' !== $form_provider ) {
        return;
    }

    return array(
        'donationViaForm'      => __( 'User makes donation via form', 'advanced-form-integration' ),
        'cancelRecurViaForm'   => __( 'User cancels recurring donation via form', 'advanced-form-integration' ),
        'subscriptionCreated'  => __( 'Subscription created', 'advanced-form-integration' ),
        'subscriptionUpdated'  => __( 'Subscription updated', 'advanced-form-integration' ),
    );
}

/**
 * Get GiveWP mapped fields for a trigger.
 *
 * @param string $form_provider Integration provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_givewp_get_form_fields( $form_provider, $form_id ) {
    if ( 'givewp' !== $form_provider ) {
        return;
    }

    $fields = array(
        'payment_id'            => __( 'Payment ID', 'advanced-form-integration' ),
        'payment_number'        => __( 'Payment Number', 'advanced-form-integration' ),
        'payment_key'           => __( 'Payment Key', 'advanced-form-integration' ),
        'status'                => __( 'Status', 'advanced-form-integration' ),
        'amount'                => __( 'Amount', 'advanced-form-integration' ),
        'subtotal'              => __( 'Subtotal', 'advanced-form-integration' ),
        'currency'              => __( 'Currency', 'advanced-form-integration' ),
        'transaction_id'        => __( 'Gateway Transaction ID', 'advanced-form-integration' ),
        'gateway'               => __( 'Gateway', 'advanced-form-integration' ),
        'payment_mode'          => __( 'Payment Mode', 'advanced-form-integration' ),
        'payment_date'          => __( 'Payment Date', 'advanced-form-integration' ),
        'donor_id'              => __( 'Donor ID', 'advanced-form-integration' ),
        'customer_id'           => __( 'Customer ID', 'advanced-form-integration' ),
        'user_id'               => __( 'User ID', 'advanced-form-integration' ),
        'campaign_id'           => __( 'Campaign ID', 'advanced-form-integration' ),
        'form_id'               => __( 'Form ID', 'advanced-form-integration' ),
        'form_title'            => __( 'Form Title', 'advanced-form-integration' ),
        'price_id'              => __( 'Price ID', 'advanced-form-integration' ),
        'subscription_id'       => __( 'Subscription ID', 'advanced-form-integration' ),
        'is_recurring'          => __( 'Is Recurring', 'advanced-form-integration' ),
        'fee_amount_recovered'  => __( 'Fee Amount Recovered', 'advanced-form-integration' ),
        'exchange_rate'         => __( 'Exchange Rate', 'advanced-form-integration' ),
        'company'               => __( 'Company', 'advanced-form-integration' ),
        'donor_phone'           => __( 'Donor Phone', 'advanced-form-integration' ),
        'donor_ip'              => __( 'Donor IP', 'advanced-form-integration' ),
        'comment'               => __( 'Comment', 'advanced-form-integration' ),
        'title'                 => __( 'Title', 'advanced-form-integration' ),
        'first_name'            => __( 'First Name', 'advanced-form-integration' ),
        'last_name'             => __( 'Last Name', 'advanced-form-integration' ),
        'email'                 => __( 'Email', 'advanced-form-integration' ),
        'address1'              => __( 'Address 1', 'advanced-form-integration' ),
        'address2'              => __( 'Address 2', 'advanced-form-integration' ),
        'city'                  => __( 'City', 'advanced-form-integration' ),
        'state'                 => __( 'State', 'advanced-form-integration' ),
        'zip'                   => __( 'Zip', 'advanced-form-integration' ),
        'country'               => __( 'Country', 'advanced-form-integration' ),
        'donation_admin_url'    => __( 'Donation Admin URL', 'advanced-form-integration' ),
    );

    if ( in_array( $form_id, array( 'subscriptionCreated', 'cancelRecurViaForm', 'subscriptionUpdated' ), true ) ) {
        $fields = array_merge(
            $fields,
            array(
                'sub_id'                  => __( 'Subscription ID', 'advanced-form-integration' ),
                'period'                  => __( 'Period', 'advanced-form-integration' ),
                'frequency'               => __( 'Frequency', 'advanced-form-integration' ),
                'initial_amount'          => __( 'Initial Amount', 'advanced-form-integration' ),
                'recurring_amount'        => __( 'Recurring Amount', 'advanced-form-integration' ),
                'recurring_fee_amount'    => __( 'Recurring Fee Amount', 'advanced-form-integration' ),
                'bill_times'              => __( 'Bill Times', 'advanced-form-integration' ),
                'created'                 => __( 'Created', 'advanced-form-integration' ),
                'expiration'              => __( 'Expiration', 'advanced-form-integration' ),
                'subscription_status'     => __( 'Subscription Status', 'advanced-form-integration' ),
                'profile_id'              => __( 'Profile ID', 'advanced-form-integration' ),
                'gateway'                 => __( 'Subscription Gateway', 'advanced-form-integration' ),
                'transaction_id'          => __( 'Subscription Transaction ID', 'advanced-form-integration' ),
                'parent_payment_id'       => __( 'Parent Payment ID', 'advanced-form-integration' ),
                'total_payments'          => __( 'Total Payments', 'advanced-form-integration' ),
                'donor_first_name'        => __( 'Donor First Name', 'advanced-form-integration' ),
                'donor_last_name'         => __( 'Donor Last Name', 'advanced-form-integration' ),
                'donor_email'             => __( 'Donor Email', 'advanced-form-integration' ),
                'donor_name'              => __( 'Donor Name', 'advanced-form-integration' ),
                'donor_user_id'           => __( 'Donor User ID', 'advanced-form-integration' ),
                'donor_address1'          => __( 'Donor Address 1', 'advanced-form-integration' ),
                'donor_address2'          => __( 'Donor Address 2', 'advanced-form-integration' ),
                'donor_city'              => __( 'Donor City', 'advanced-form-integration' ),
                'donor_state'             => __( 'Donor State', 'advanced-form-integration' ),
                'donor_zip'               => __( 'Donor Zip', 'advanced-form-integration' ),
                'donor_country'           => __( 'Donor Country', 'advanced-form-integration' ),
            )
        );
    }

    if ( 'subscriptionUpdated' === $form_id ) {
        $fields['update_status'] = __( 'Update Result', 'advanced-form-integration' );
        $fields['update_data']   = __( 'Updated Data', 'advanced-form-integration' );
        $fields['update_where']  = __( 'Update Where', 'advanced-form-integration' );
    }

    return $fields;
}

/**
 * Convert complex values to strings.
 *
 * @param mixed $value Input value.
 *
 * @return string
 */
function adfoin_givewp_normalize_scalar( $value ) {
    if ( is_object( $value ) ) {
        if ( method_exists( $value, 'formatToDecimal' ) ) {
            $value = $value->formatToDecimal();
        } elseif ( method_exists( $value, '__toString' ) ) {
            $value = (string) $value;
        } else {
            $value = wp_json_encode( $value );
        }
    } elseif ( is_array( $value ) ) {
        $value = wp_json_encode( $value );
    }

    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( null === $value ) {
        return '';
    }

    return (string) $value;
}

/**
 * Normalize amount values.
 *
 * @param mixed $value Value to format.
 *
 * @return string
 */
function adfoin_givewp_normalize_amount( $value ) {
    $normalized = adfoin_givewp_normalize_scalar( $value );

    if ( '' === $normalized ) {
        return '';
    }

    if ( is_numeric( $normalized ) ) {
        return give_format_decimal( $normalized );
    }

    return $normalized;
}

/**
 * Trim values when possible.
 *
 * @param mixed $value Value to trim.
 *
 * @return mixed
 */
function adfoin_givewp_maybe_trim( $value ) {
    if ( is_string( $value ) ) {
        return trim( $value );
    }

    return $value;
}

/**
 * Retrieve a payment meta value.
 *
 * @param int    $payment_id Payment ID.
 * @param string $meta_key   Meta key.
 *
 * @return string
 */
function adfoin_givewp_get_payment_meta_value( $payment_id, $meta_key ) {
    $value = give_get_payment_meta( $payment_id, $meta_key, true );

    if ( '' === $value || null === $value ) {
        return '';
    }

    return adfoin_givewp_normalize_scalar( $value );
}

/**
 * Collect donor details into a flat array.
 *
 * @param Give_Donor|int|null $donor Donor object or donor ID.
 *
 * @return array<string,string>
 */
function adfoin_givewp_collect_donor_details( $donor ) {
    if ( $donor instanceof Give_Donor === false ) {
        if ( $donor ) {
            $donor = new Give_Donor( $donor );
        }
    }

    $details = array(
        'donor_id'        => '',
        'donor_user_id'   => '',
        'donor_name'      => '',
        'donor_first_name'=> '',
        'donor_last_name' => '',
        'donor_email'     => '',
        'donor_address1'  => '',
        'donor_address2'  => '',
        'donor_city'      => '',
        'donor_state'     => '',
        'donor_zip'       => '',
        'donor_country'   => '',
    );

    if ( ! ( $donor instanceof Give_Donor ) ) {
        return $details;
    }

    $details['donor_id']      = adfoin_givewp_normalize_scalar( $donor->id );
    $details['donor_user_id'] = adfoin_givewp_normalize_scalar( $donor->user_id );
    $details['donor_name']    = adfoin_givewp_normalize_scalar( $donor->name ?? '' );
    $details['donor_email']   = adfoin_givewp_normalize_scalar( $donor->email ?? '' );

    if ( method_exists( $donor, 'get_first_name' ) ) {
        $details['donor_first_name'] = adfoin_givewp_normalize_scalar( $donor->get_first_name() );
    }

    if ( method_exists( $donor, 'get_last_name' ) ) {
        $details['donor_last_name'] = adfoin_givewp_normalize_scalar( $donor->get_last_name() );
    }

    if ( ! empty( $donor->address ) && is_array( $donor->address ) ) {
        $details['donor_address1'] = adfoin_givewp_normalize_scalar( $donor->address['line1'] ?? '' );
        $details['donor_address2'] = adfoin_givewp_normalize_scalar( $donor->address['line2'] ?? '' );
        $details['donor_city']     = adfoin_givewp_normalize_scalar( $donor->address['city'] ?? '' );
        $details['donor_state']    = adfoin_givewp_normalize_scalar( $donor->address['state'] ?? '' );
        $details['donor_zip']      = adfoin_givewp_normalize_scalar( $donor->address['zip'] ?? '' );
        $details['donor_country']  = adfoin_givewp_normalize_scalar( $donor->address['country'] ?? '' );
    }

    return $details;
}

/**
 * Build a normalized donation payload from a Give payment.
 *
 * @param Give_Payment $payment Payment object.
 *
 * @return array<string,string>
 */
function adfoin_givewp_prepare_donation_payload( Give_Payment $payment ) {
    $payment_id = $payment->ID;

    $user_info = give_get_payment_meta_user_info( $payment_id );
    $address   = array();

    if ( isset( $user_info['address'] ) && is_array( $user_info['address'] ) ) {
        $address = $user_info['address'];
    }

    $donor_details = adfoin_givewp_collect_donor_details( give_get_payment_donor_id( $payment_id ) );

    $payload = array_merge(
        array(
            'payment_id'           => adfoin_givewp_normalize_scalar( $payment_id ),
            'payment_number'       => adfoin_givewp_normalize_scalar( $payment->number ?? '' ),
            'payment_key'          => adfoin_givewp_normalize_scalar( $payment->key ?? '' ),
            'status'               => adfoin_givewp_normalize_scalar( get_post_status( $payment_id ) ),
            'amount'               => adfoin_givewp_normalize_amount( $payment->total ?? '' ),
            'subtotal'             => adfoin_givewp_normalize_amount( $payment->subtotal ?? '' ),
            'currency'             => adfoin_givewp_normalize_scalar( $payment->currency ?? '' ),
            'transaction_id'       => adfoin_givewp_normalize_scalar( $payment->transaction_id ?? '' ),
            'gateway'              => adfoin_givewp_normalize_scalar( $payment->gateway ?? '' ),
            'payment_mode'         => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_payment_mode' ),
            'payment_date'         => adfoin_givewp_normalize_scalar( get_post_field( 'post_date', $payment_id ) ),
            'donor_id'             => adfoin_givewp_normalize_scalar( $payment->donor_id ?? $donor_details['donor_id'] ),
            'customer_id'          => adfoin_givewp_normalize_scalar( $payment->customer_id ?? '' ),
            'user_id'              => adfoin_givewp_normalize_scalar( $user_info['id'] ?? $payment->user_id ?? '' ),
            'campaign_id'          => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_campaign_id' ),
            'form_id'              => adfoin_givewp_normalize_scalar( $payment->form_id ?? '' ),
            'form_title'           => adfoin_givewp_normalize_scalar( $payment->form_id ? get_the_title( $payment->form_id ) : '' ),
            'price_id'             => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_payment_price_id' ),
            'subscription_id'      => adfoin_givewp_get_payment_meta_value( $payment_id, 'subscription_id' ),
            'is_recurring'         => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_is_donation_recurring' ),
            'fee_amount_recovered' => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_fee_amount' ),
            'exchange_rate'        => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_cs_exchange_rate' ),
            'company'              => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_donation_company' ),
            'donor_phone'          => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_payment_donor_phone' ),
            'donor_ip'             => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_payment_donor_ip' ),
            'comment'              => adfoin_givewp_get_payment_meta_value( $payment_id, '_give_donation_comment' ),
            'title'                => adfoin_givewp_normalize_scalar( $user_info['title'] ?? '' ),
            'first_name'           => adfoin_givewp_normalize_scalar( $user_info['first_name'] ?? $donor_details['donor_first_name'] ),
            'last_name'            => adfoin_givewp_normalize_scalar( $user_info['last_name'] ?? $donor_details['donor_last_name'] ),
            'email'                => adfoin_givewp_normalize_scalar( $user_info['email'] ?? $donor_details['donor_email'] ),
            'address1'             => adfoin_givewp_normalize_scalar( $address['line1'] ?? '' ),
            'address2'             => adfoin_givewp_normalize_scalar( $address['line2'] ?? '' ),
            'city'                 => adfoin_givewp_normalize_scalar( $address['city'] ?? '' ),
            'state'                => adfoin_givewp_normalize_scalar( $address['state'] ?? '' ),
            'zip'                  => adfoin_givewp_normalize_scalar( $address['zip'] ?? '' ),
            'country'              => adfoin_givewp_normalize_scalar( $address['country'] ?? '' ),
            'donation_admin_url'   => adfoin_givewp_normalize_scalar(
                admin_url(
                    add_query_arg(
                        array(
                            'post_type' => 'give_forms',
                            'page'      => 'give-payment-history',
                            'view'      => 'view-payment-details',
                            'id'        => $payment_id,
                        ),
                        'edit.php'
                    )
                )
            ),
        ),
        $donor_details
    );

    return array_map( 'adfoin_givewp_maybe_trim', $payload );
}

/**
 * Build a normalized subscription payload.
 *
 * @param Give_Subscription $subscription Subscription object.
 *
 * @return array<string,string>
 */
function adfoin_givewp_prepare_subscription_payload( Give_Subscription $subscription ) {
    if ( empty( $subscription->id ) ) {
        return array();
    }

    $donor = $subscription->donor instanceof Give_Donor ? $subscription->donor : new Give_Donor( $subscription->donor_id );
    $donor_details = adfoin_givewp_collect_donor_details( $donor );

    $payload = array_merge(
        array(
            'sub_id'                 => adfoin_givewp_normalize_scalar( $subscription->id ),
            'form_id'                => adfoin_givewp_normalize_scalar( $subscription->form_id ),
            'form_title'             => adfoin_givewp_normalize_scalar( $subscription->form_id ? get_the_title( $subscription->form_id ) : '' ),
            'period'                 => adfoin_givewp_normalize_scalar( $subscription->period ),
            'frequency'              => adfoin_givewp_normalize_scalar( $subscription->frequency ),
            'initial_amount'         => adfoin_givewp_normalize_amount( $subscription->initial_amount ),
            'recurring_amount'       => adfoin_givewp_normalize_amount( $subscription->recurring_amount ),
            'recurring_fee_amount'   => adfoin_givewp_normalize_amount( $subscription->recurring_fee_amount ),
            'bill_times'             => adfoin_givewp_normalize_scalar( $subscription->bill_times ),
            'payment_mode'           => adfoin_givewp_normalize_scalar( $subscription->payment_mode ),
            'created'                => adfoin_givewp_normalize_scalar( $subscription->created ),
            'expiration'             => adfoin_givewp_normalize_scalar( $subscription->expiration ),
            'subscription_status'    => adfoin_givewp_normalize_scalar( $subscription->status ),
            'customer_id'            => adfoin_givewp_normalize_scalar( $subscription->customer_id ),
            'campaign_id'            => adfoin_givewp_normalize_scalar( $subscription->campaign_id ),
            'gateway'                => adfoin_givewp_normalize_scalar( $subscription->gateway ),
            'profile_id'             => adfoin_givewp_normalize_scalar( $subscription->profile_id ),
            'transaction_id'         => adfoin_givewp_normalize_scalar( $subscription->transaction_id ),
            'parent_payment_id'      => adfoin_givewp_normalize_scalar( $subscription->parent_payment_id ),
            'total_payments'         => adfoin_givewp_normalize_scalar(
                method_exists( $subscription, 'get_total_payments' ) ? $subscription->get_total_payments() : ''
            ),
            'notes'                  => adfoin_givewp_normalize_scalar( $subscription->notes ),
        ),
        $donor_details
    );

    // Backwards compatibility fields.
    $payload['first_name'] = $payload['donor_first_name'];
    $payload['last_name']  = $payload['donor_last_name'];
    $payload['email']      = $payload['donor_email'];

    return array_map( 'adfoin_givewp_maybe_trim', $payload );
}

add_action( 'give_update_payment_status', 'adfoin_update_payment_status', 10, 3 );

/**
 * Handle GiveWP payment status updates.
 *
 * @param int    $payment_id Payment ID.
 * @param string $status     New status.
 * @param string $old_status Previous status.
 *
 * @return void
 */
function adfoin_update_payment_status( $payment_id, $status, $old_status ) {
    if ( 'publish' !== $status || 'publish' === $old_status ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'donationViaForm' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payment = new Give_Payment( $payment_id );

    if ( empty( $payment->ID ) ) {
        return;
    }

    $posted_data = adfoin_givewp_prepare_donation_payload( $payment );

    if ( empty( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

add_action( 'give_subscription_cancelled', 'adfoin_givewp_subscription_cancelled', 10, 2 );

/**
 * Handle subscription cancellations.
 *
 * @param int               $sub_id       Subscription ID.
 * @param Give_Subscription $subscription Subscription object.
 *
 * @return void
 */
function adfoin_givewp_subscription_cancelled( $sub_id, $subscription ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'cancelRecurViaForm' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( ! ( $subscription instanceof Give_Subscription ) ) {
        $subscription = new Give_Subscription( $sub_id );
    }

    if ( empty( $subscription->id ) ) {
        return;
    }

    $posted_data = adfoin_givewp_prepare_subscription_payload( $subscription );

    if ( empty( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

add_action( 'give_subscription_inserted', 'adfoin_givewp_subscription_inserted', 10, 2 );

/**
 * Handle subscription creation.
 *
 * @param int   $sub_id Subscription ID.
 * @param array $data   Raw subscription data array.
 *
 * @return void
 */
function adfoin_givewp_subscription_inserted( $sub_id, $data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'subscriptionCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $subscription = new Give_Subscription( $sub_id );

    if ( empty( $subscription->id ) ) {
        return;
    }

    $posted_data = adfoin_givewp_prepare_subscription_payload( $subscription );

    if ( empty( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

add_action( 'give_subscription_updated', 'adfoin_givewp_subscription_updated', 10, 4 );

/**
 * Handle subscription updates.
 *
 * @param bool        $status          Update result.
 * @param int         $subscription_id Subscription ID.
 * @param array       $data            Updated data.
 * @param string|null $where           Where clause.
 *
 * @return void
 */
function adfoin_givewp_subscription_updated( $status, $subscription_id, $data, $where ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'givewp', 'subscriptionUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $subscription = new Give_Subscription( $subscription_id );

    if ( empty( $subscription->id ) ) {
        return;
    }

    $posted_data = adfoin_givewp_prepare_subscription_payload( $subscription );

    if ( empty( $posted_data ) ) {
        return;
    }

    $posted_data['update_status'] = adfoin_givewp_normalize_scalar( $status );
    $posted_data['update_data']   = adfoin_givewp_normalize_scalar( $data );
    $posted_data['update_where']  = adfoin_givewp_normalize_scalar( $where );

    $integration->send( $saved_records, $posted_data );
}
