<?php

add_filter( 'adfoin_action_providers', 'adfoin_charitable_actions', 10, 1 );

function adfoin_charitable_actions( $actions ) {

    $actions['charitable'] = array(
        'title' => __( 'Charitable', 'advanced-form-integration' ),
        'tasks' => array(
            'create_donation' => __( 'Create Donation', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_charitable_action_fields' );

function adfoin_charitable_action_fields() {
    ?>
    <script type="text/template" id="charitable-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_donation'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Supply the campaign (form) ID and donation amount. Provide donor email or an existing donor/user ID. Additional donor details and donation metadata are optional.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_charitable_job_queue', 'adfoin_charitable_job_queue', 10, 1 );

function adfoin_charitable_job_queue( $data ) {
    adfoin_charitable_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_charitable_send_data( $record, $posted_data ) {
    if ( ! function_exists( 'charitable_create_donation' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'create_donation' !== $task ) {
        return;
    }

    $parsed = array();

    foreach ( $field_data as $key => $value ) {
        $parsed[ $key ] = adfoin_get_parsed_values( $value, $posted_data );
    }

    $campaign_id = isset( $parsed['campaign_id'] ) ? absint( $parsed['campaign_id'] ) : 0;
    $amount_raw  = isset( $parsed['amount'] ) ? $parsed['amount'] : '';

    if ( ! $campaign_id ) {
        adfoin_charitable_log(
            __( 'Campaign ID is required.', 'advanced-form-integration' ),
            $record,
            array()
        );
        return;
    }

    if ( '' === $amount_raw ) {
        adfoin_charitable_log(
            __( 'Donation amount is required.', 'advanced-form-integration' ),
            $record,
            array( 'campaign_id' => $campaign_id )
        );
        return;
    }

    $amount = charitable_sanitize_amount( $amount_raw, true );

    if ( is_wp_error( $amount ) || $amount <= 0 ) {
        adfoin_charitable_log(
            __( 'Donation amount is invalid.', 'advanced-form-integration' ),
            $record,
            array(
                'campaign_id' => $campaign_id,
                'amount'      => $amount_raw,
            )
        );
        return;
    }

    $campaign_name = isset( $parsed['campaign_name'] ) ? sanitize_text_field( $parsed['campaign_name'] ) : '';
    if ( ! $campaign_name ) {
        $campaign_name = get_post_field( 'post_title', $campaign_id, 'raw' );
    }

    $status = isset( $parsed['status'] ) ? sanitize_text_field( $parsed['status'] ) : 'charitable-completed';
    if ( function_exists( 'charitable_is_valid_donation_status' ) && ! charitable_is_valid_donation_status( $status ) ) {
        $status = 'charitable-completed';
    }

    $gateway = isset( $parsed['gateway'] ) ? sanitize_text_field( $parsed['gateway'] ) : '';
    if ( '' === $gateway ) {
        $gateway = 'manual';
    }

    $donation_key = isset( $parsed['donation_key'] ) ? sanitize_text_field( $parsed['donation_key'] ) : '';
    $donation_note = isset( $parsed['donation_note'] ) ? sanitize_textarea_field( $parsed['donation_note'] ) : '';
    $log_note      = isset( $parsed['log_note'] ) ? sanitize_textarea_field( $parsed['log_note'] ) : '';

    $user_id  = isset( $parsed['user_id'] ) ? absint( $parsed['user_id'] ) : 0;
    $donor_id = isset( $parsed['donor_id'] ) ? absint( $parsed['donor_id'] ) : 0;

    $donor_email = isset( $parsed['donor_email'] ) ? sanitize_email( $parsed['donor_email'] ) : '';

    if ( ! $donor_id && ! $user_id && ! $donor_email ) {
        adfoin_charitable_log(
            __( 'A donor email, donor ID, or user ID is required.', 'advanced-form-integration' ),
            $record,
            array(
                'campaign_id' => $campaign_id,
                'amount'      => $amount_raw,
            )
        );
        return;
    }

    if ( $donor_email && ! is_email( $donor_email ) ) {
        adfoin_charitable_log(
            __( 'Donor email is invalid.', 'advanced-form-integration' ),
            $record,
            array(
                'campaign_id' => $campaign_id,
                'amount'      => $amount_raw,
                'donor_email' => $donor_email,
            )
        );
        return;
    }

    $user_data = array();

    if ( $donor_email ) {
        $user_data['email'] = $donor_email;
    }

    $name_fields = array(
        'first_name' => 'donor_first_name',
        'last_name'  => 'donor_last_name',
        'address'    => 'donor_address',
        'address_2'  => 'donor_address_2',
        'city'       => 'donor_city',
        'state'      => 'donor_state',
        'postcode'   => 'donor_postcode',
        'country'    => 'donor_country',
        'phone'      => 'donor_phone',
        'company'    => 'donor_company',
    );

    foreach ( $name_fields as $field_key => $input_key ) {
        if ( isset( $parsed[ $input_key ] ) && '' !== $parsed[ $input_key ] ) {
            $user_data[ $field_key ] = sanitize_text_field( $parsed[ $input_key ] );
        }
    }

    $contact_consent = null;
    if ( isset( $parsed['contact_consent'] ) && '' !== $parsed['contact_consent'] ) {
        $contact_consent = adfoin_charitable_normalize_bool( $parsed['contact_consent'] );
    }

    $meta = array();

    if ( null !== $contact_consent ) {
        $meta['contact_consent'] = $contact_consent ? 1 : 0;
    }

    if ( isset( $parsed['meta_json'] ) && '' !== $parsed['meta_json'] ) {
        $decoded_meta = json_decode( $parsed['meta_json'], true );
        if ( is_array( $decoded_meta ) ) {
            foreach ( $decoded_meta as $meta_key => $value ) {
                $meta_key = sanitize_key( $meta_key );
                if ( '' !== $meta_key ) {
                    $meta[ $meta_key ] = sanitize_textarea_field( (string) $value );
                }
            }
        }
    }

    foreach ( $parsed as $key => $value ) {
        if ( 0 === strpos( $key, 'meta__' ) ) {
            $meta_key = sanitize_key( substr( $key, 6 ) );
            if ( '' !== $meta_key ) {
                $meta[ $meta_key ] = sanitize_textarea_field( $value );
            }
        }
    }

    $campaign = array(
        'campaign_id'   => $campaign_id,
        'amount'        => $amount,
        'campaign_name' => $campaign_name,
    );

    if ( isset( $parsed['anonymous'] ) && '' !== $parsed['anonymous'] ) {
        $campaign['anonymous'] = adfoin_charitable_normalize_bool( $parsed['anonymous'] );
    }

    $args = array(
        'campaigns' => array( $campaign ),
        'status'    => $status,
        'gateway'   => $gateway,
    );

    if ( $donation_key ) {
        $args['donation_key'] = $donation_key;
    }

    if ( $donation_note ) {
        $args['note'] = $donation_note;
    }

    if ( $log_note ) {
        $args['log_note'] = $log_note;
    }

    if ( $user_id ) {
        $args['user_id'] = $user_id;
    }

    if ( $donor_id ) {
        $args['donor_id'] = $donor_id;
    }

    if ( ! empty( $user_data ) ) {
        $args['user'] = $user_data;
    }

    if ( isset( $parsed['donation_plan'] ) && '' !== $parsed['donation_plan'] ) {
        $args['donation_plan'] = absint( $parsed['donation_plan'] );
    }

    if ( isset( $parsed['date_gmt'] ) && '' !== $parsed['date_gmt'] ) {
        $args['date_gmt'] = sanitize_text_field( $parsed['date_gmt'] );
    }

    if ( isset( $parsed['currency'] ) && '' !== $parsed['currency'] ) {
        $args['currency'] = sanitize_text_field( $parsed['currency'] );
    }

    if ( ! empty( $meta ) ) {
        $args['meta'] = $meta;
    }

    $transaction_id  = isset( $parsed['transaction_id'] ) ? sanitize_text_field( $parsed['transaction_id'] ) : '';
    $payment_id      = isset( $parsed['payment_id'] ) ? sanitize_text_field( $parsed['payment_id'] ) : '';
    $transaction_url = isset( $parsed['transaction_url'] ) ? esc_url_raw( $parsed['transaction_url'] ) : '';
    $receipt_url     = isset( $parsed['receipt_url'] ) ? esc_url_raw( $parsed['receipt_url'] ) : '';

    $request_payload = array(
        'campaign_id'  => $campaign_id,
        'amount'       => $amount,
        'status'       => $status,
        'gateway'      => $gateway,
        'donation_key' => $donation_key,
        'user_id'      => $user_id,
        'donor_id'     => $donor_id,
        'meta_keys'    => array_keys( $meta ),
    );

    $donation_id = charitable_create_donation( $args );

    if ( ! $donation_id ) {
        adfoin_charitable_log(
            __( 'Failed to create donation.', 'advanced-form-integration' ),
            $record,
            $request_payload
        );
        return;
    }

    $donation = charitable_get_donation( $donation_id );
    if ( $donation ) {
        if ( $transaction_id ) {
            $donation->set_gateway_transaction_id( $transaction_id );
        }

        if ( $payment_id ) {
            if ( method_exists( $donation, 'set_gateway_payment_id' ) ) {
                $donation->set_gateway_payment_id( $payment_id );
            } else {
                update_post_meta( $donation_id, '_gateway_payment_id', $payment_id );
            }
        }

        if ( $transaction_url ) {
            $donation->set_gateway_transaction_url( $transaction_url );
        }

        if ( $receipt_url ) {
            $donation->set_receipt_url( $receipt_url );
        }
    } else {
        if ( $transaction_id ) {
            update_post_meta( $donation_id, '_gateway_transaction_id', $transaction_id );
        }
        if ( $payment_id ) {
            update_post_meta( $donation_id, '_gateway_payment_id', $payment_id );
        }
        if ( $transaction_url ) {
            update_post_meta( $donation_id, '_gateway_transaction_url', $transaction_url );
        }
        if ( $receipt_url ) {
            update_post_meta( $donation_id, '_donation_receipt_url', $receipt_url );
        }
    }

    adfoin_charitable_log(
        __( 'Donation created successfully.', 'advanced-form-integration' ),
        $record,
        array_merge(
            $request_payload,
            array(
                'donation_id'      => $donation_id,
                'transaction_id'   => $transaction_id,
                'payment_id'       => $payment_id,
                'transaction_url'  => $transaction_url,
                'receipt_url'      => $receipt_url,
            )
        ),
        true
    );
}

function adfoin_charitable_normalize_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    $value = strtolower( trim( (string) $value ) );

    if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
        return true;
    }

    if ( in_array( $value, array( '0', 'false', 'no', 'off' ), true ) ) {
        return false;
    }

    return false;
}

function adfoin_charitable_log( $message, $record, $request_payload, $success = false ) {
    $log_response = array(
        'response' => array(
            'code'    => $success ? 200 : 400,
            'message' => $message,
        ),
        'body'     => array(
            'success' => $success,
            'message' => $message,
        ),
    );

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    adfoin_add_to_log( $log_response, 'charitable', $log_args, $record );
}
