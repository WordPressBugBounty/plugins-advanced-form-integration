<?php

add_filter( 'adfoin_action_providers', 'adfoin_givewp_actions', 10, 1 );

function adfoin_givewp_actions( $actions ) {
    $actions['givewp'] = array(
        'title' => __( 'GiveWP', 'advanced-form-integration' ),
        'tasks' => array(
            'create_donor'            => __( 'Create Donor', 'advanced-form-integration' ),
            'update_donor'            => __( 'Update Donor', 'advanced-form-integration' ),
            'create_donation'         => __( 'Create Donation', 'advanced-form-integration' ),
            'update_donation_status'  => __( 'Update Donation Status', 'advanced-form-integration' ),
            'add_donation_note'       => __( 'Add Donation Note', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_givewp_action_fields' );

function adfoin_givewp_action_fields() {
    ?>
    <script type="text/template" id="givewp-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_donor'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Email is required. If a donor with that email already exists the record is updated, otherwise a new donor is created. Optional profile fields are saved to donor meta.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_donor'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a donor ID or donor email to update. Only the supplied fields are changed. Profile meta is updated when provided.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'create_donation'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Form ID and amount are required. Supply donor information via donor ID or contact fields. Optional metadata JSON is stored as payment meta and a note can be attached.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'update_donation_status'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a donation ID and a valid GiveWP status key (pending, publish, refunded, failed, cancelled, abandoned, preapproval, processing, or revoked).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'add_donation_note'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Adds an internal note to the selected donation. Notes are stored in the payment activity log.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_givewp_job_queue', 'adfoin_givewp_job_queue', 10, 1 );

function adfoin_givewp_job_queue( $data ) {
    adfoin_givewp_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_givewp_send_data( $record, $posted_data ) {
    if ( ! function_exists( 'Give' ) ) {
        adfoin_givewp_action_log( $record, __( 'GiveWP is not active.', 'advanced-form-integration' ), array(), false );
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

    if ( 'create_donor' === $task ) {
        adfoin_givewp_action_create_donor( $record, $parsed );
    } elseif ( 'update_donor' === $task ) {
        adfoin_givewp_action_update_donor( $record, $parsed );
    } elseif ( 'create_donation' === $task ) {
        adfoin_givewp_action_create_donation( $record, $parsed );
    } elseif ( 'update_donation_status' === $task ) {
        adfoin_givewp_action_update_donation_status( $record, $parsed );
    } elseif ( 'add_donation_note' === $task ) {
        adfoin_givewp_action_add_donation_note( $record, $parsed );
    }
}

function adfoin_givewp_action_create_donor( $record, $parsed ) {
    if ( ! class_exists( 'Give_Donor' ) ) {
        adfoin_givewp_action_log( $record, __( 'GiveWP donor class unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $email = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';
    if ( ! $email ) {
        adfoin_givewp_action_log( $record, __( 'Valid donor email is required.', 'advanced-form-integration' ), $parsed, false );
        return;
    }

    $first_name   = isset( $parsed['first_name'] ) ? sanitize_text_field( $parsed['first_name'] ) : '';
    $last_name    = isset( $parsed['last_name'] ) ? sanitize_text_field( $parsed['last_name'] ) : '';
    $name         = isset( $parsed['name'] ) ? sanitize_text_field( $parsed['name'] ) : '';
    $company      = isset( $parsed['company'] ) ? sanitize_text_field( $parsed['company'] ) : '';
    $phone        = isset( $parsed['phone'] ) ? sanitize_text_field( $parsed['phone'] ) : '';
    $title_prefix = isset( $parsed['title_prefix'] ) ? sanitize_text_field( $parsed['title_prefix'] ) : '';
    $address_line1 = isset( $parsed['address_line1'] ) ? sanitize_text_field( $parsed['address_line1'] ) : '';
    $address_line2 = isset( $parsed['address_line2'] ) ? sanitize_text_field( $parsed['address_line2'] ) : '';
    $address_city  = isset( $parsed['address_city'] ) ? sanitize_text_field( $parsed['address_city'] ) : '';
    $address_state = isset( $parsed['address_state'] ) ? sanitize_text_field( $parsed['address_state'] ) : '';
    $address_zip   = isset( $parsed['address_zip'] ) ? sanitize_text_field( $parsed['address_zip'] ) : '';
    $address_country = isset( $parsed['address_country'] ) ? strtoupper( sanitize_text_field( $parsed['address_country'] ) ) : '';
    $donor_note    = isset( $parsed['donor_note'] ) ? sanitize_textarea_field( $parsed['donor_note'] ) : '';

    $name = '' !== $name ? $name : trim( $first_name . ' ' . $last_name );
    $name = '' !== $name ? $name : $email;

    $user_id = isset( $parsed['user_id'] ) && '' !== $parsed['user_id'] ? absint( $parsed['user_id'] ) : 0;

    $existing = Give()->donors ? Give()->donors->get_donor_by( 'email', $email ) : false;
    $meta_payload = array(
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'company'        => $company,
        'phone'          => $phone,
        'title_prefix'   => $title_prefix,
        'address_line1'  => $address_line1,
        'address_line2'  => $address_line2,
        'address_city'   => $address_city,
        'address_state'  => $address_state,
        'address_zip'    => $address_zip,
        'address_country'=> $address_country,
    );

    $payload = array(
        'email' => $email,
    );

    if ( $existing && isset( $existing->id ) ) {
        $donor_id = (int) $existing->id;
        $donor    = new Give_Donor( $donor_id );

        $updates = array();
        if ( '' !== $name ) {
            $updates['name'] = $name;
        }
        if ( $user_id ) {
            $updates['user_id'] = $user_id;
        }
        if ( isset( $parsed['purchase_value'] ) && '' !== $parsed['purchase_value'] ) {
            $updates['purchase_value'] = floatval( $parsed['purchase_value'] );
        }
        if ( isset( $parsed['purchase_count'] ) && '' !== $parsed['purchase_count'] ) {
            $updates['purchase_count'] = absint( $parsed['purchase_count'] );
        }
        if ( isset( $parsed['payment_ids'] ) && '' !== $parsed['payment_ids'] ) {
            $updates['payment_ids'] = adfoin_givewp_action_sanitize_payment_ids( $parsed['payment_ids'] );
        }
        if ( isset( $parsed['token'] ) && '' !== $parsed['token'] ) {
            $updates['token'] = sanitize_text_field( $parsed['token'] );
        }
        if ( isset( $parsed['verify_key'] ) && '' !== $parsed['verify_key'] ) {
            $updates['verify_key'] = sanitize_text_field( $parsed['verify_key'] );
        }
        if ( isset( $parsed['verify_throttle'] ) && '' !== $parsed['verify_throttle'] ) {
            $updates['verify_throttle'] = sanitize_text_field( $parsed['verify_throttle'] );
        }

        if ( ! empty( $updates ) ) {
            $donor->update( $updates );
        }

        if ( ! empty( $meta_payload ) ) {
            adfoin_givewp_action_update_donor_meta_fields( $donor_id, $meta_payload );
        }

        if ( '' !== $donor_note ) {
            $donor->add_note( $donor_note );
        }

        $payload['donor_id'] = $donor_id;

        adfoin_givewp_action_log(
            $record,
            __( 'Donor updated successfully.', 'advanced-form-integration' ),
            $payload,
            true
        );

        return;
    }

    $donor = new Give_Donor();
    $create_args = array(
        'email' => $email,
        'name'  => $name,
    );

    if ( $user_id ) {
        $create_args['user_id'] = $user_id;
    }
    if ( isset( $parsed['purchase_value'] ) && '' !== $parsed['purchase_value'] ) {
        $create_args['purchase_value'] = floatval( $parsed['purchase_value'] );
    }
    if ( isset( $parsed['purchase_count'] ) && '' !== $parsed['purchase_count'] ) {
        $create_args['purchase_count'] = absint( $parsed['purchase_count'] );
    }
    if ( isset( $parsed['payment_ids'] ) && '' !== $parsed['payment_ids'] ) {
        $create_args['payment_ids'] = adfoin_givewp_action_sanitize_payment_ids( $parsed['payment_ids'] );
    }
    if ( isset( $parsed['token'] ) && '' !== $parsed['token'] ) {
        $create_args['token'] = sanitize_text_field( $parsed['token'] );
    }
    if ( isset( $parsed['verify_key'] ) && '' !== $parsed['verify_key'] ) {
        $create_args['verify_key'] = sanitize_text_field( $parsed['verify_key'] );
    }
    if ( isset( $parsed['verify_throttle'] ) && '' !== $parsed['verify_throttle'] ) {
        $create_args['verify_throttle'] = sanitize_text_field( $parsed['verify_throttle'] );
    }

    $create_args = array_filter(
        $create_args,
        static function( $value ) {
            return '' !== $value && null !== $value;
        }
    );

    $donor_id = $donor->create( $create_args );

    if ( ! $donor_id ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Failed to create donor.', 'advanced-form-integration' ),
            $create_args,
            false
        );
        return;
    }

    if ( ! empty( $meta_payload ) ) {
        adfoin_givewp_action_update_donor_meta_fields( $donor_id, $meta_payload );
    }

    if ( '' !== $donor_note ) {
        $new_donor = new Give_Donor( $donor_id );
        $new_donor->add_note( $donor_note );
    }

    $payload['donor_id'] = $donor_id;

    adfoin_givewp_action_log(
        $record,
        __( 'Donor created successfully.', 'advanced-form-integration' ),
        $payload,
        true
    );
}

function adfoin_givewp_action_update_donor( $record, $parsed ) {
    if ( ! class_exists( 'Give_Donor' ) ) {
        adfoin_givewp_action_log( $record, __( 'GiveWP donor class unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $donor_id = isset( $parsed['donor_id'] ) && '' !== $parsed['donor_id'] ? absint( $parsed['donor_id'] ) : 0;
    $email    = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';

    $donor = null;

    if ( $donor_id ) {
        $donor = new Give_Donor( $donor_id );
        if ( empty( $donor->id ) ) {
            $donor = null;
        }
    } elseif ( $email ) {
        $existing = Give()->donors ? Give()->donors->get_donor_by( 'email', $email ) : false;
        if ( $existing && isset( $existing->id ) ) {
            $donor = new Give_Donor( $existing->id );
        }
    }

    if ( ! $donor || empty( $donor->id ) ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Donor could not be located.', 'advanced-form-integration' ),
            array(
                'donor_id' => $donor_id,
                'email'    => $email,
            ),
            false
        );
        return;
    }

    $updates = array();

    if ( $email && $email !== $donor->email ) {
        $updates['email'] = $email;
    }

    if ( isset( $parsed['name'] ) && '' !== $parsed['name'] ) {
        $updates['name'] = sanitize_text_field( $parsed['name'] );
    }

    if ( isset( $parsed['user_id'] ) && '' !== $parsed['user_id'] ) {
        $updates['user_id'] = absint( $parsed['user_id'] );
    }

    if ( isset( $parsed['purchase_value'] ) && '' !== $parsed['purchase_value'] ) {
        $updates['purchase_value'] = floatval( $parsed['purchase_value'] );
    }

    if ( isset( $parsed['purchase_count'] ) && '' !== $parsed['purchase_count'] ) {
        $updates['purchase_count'] = absint( $parsed['purchase_count'] );
    }

    if ( isset( $parsed['payment_ids'] ) && '' !== $parsed['payment_ids'] ) {
        $updates['payment_ids'] = adfoin_givewp_action_sanitize_payment_ids( $parsed['payment_ids'] );
    }

    if ( isset( $parsed['token'] ) && '' !== $parsed['token'] ) {
        $updates['token'] = sanitize_text_field( $parsed['token'] );
    }

    if ( isset( $parsed['verify_key'] ) && '' !== $parsed['verify_key'] ) {
        $updates['verify_key'] = sanitize_text_field( $parsed['verify_key'] );
    }

    if ( isset( $parsed['verify_throttle'] ) && '' !== $parsed['verify_throttle'] ) {
        $updates['verify_throttle'] = sanitize_text_field( $parsed['verify_throttle'] );
    }

    if ( ! empty( $updates ) ) {
        $donor->update( $updates );
    }

    $meta_payload = array(
        'first_name'      => isset( $parsed['first_name'] ) ? sanitize_text_field( $parsed['first_name'] ) : '',
        'last_name'       => isset( $parsed['last_name'] ) ? sanitize_text_field( $parsed['last_name'] ) : '',
        'company'         => isset( $parsed['company'] ) ? sanitize_text_field( $parsed['company'] ) : '',
        'phone'           => isset( $parsed['phone'] ) ? sanitize_text_field( $parsed['phone'] ) : '',
        'title_prefix'    => isset( $parsed['title_prefix'] ) ? sanitize_text_field( $parsed['title_prefix'] ) : '',
        'address_line1'   => isset( $parsed['address_line1'] ) ? sanitize_text_field( $parsed['address_line1'] ) : '',
        'address_line2'   => isset( $parsed['address_line2'] ) ? sanitize_text_field( $parsed['address_line2'] ) : '',
        'address_city'    => isset( $parsed['address_city'] ) ? sanitize_text_field( $parsed['address_city'] ) : '',
        'address_state'   => isset( $parsed['address_state'] ) ? sanitize_text_field( $parsed['address_state'] ) : '',
        'address_zip'     => isset( $parsed['address_zip'] ) ? sanitize_text_field( $parsed['address_zip'] ) : '',
        'address_country' => isset( $parsed['address_country'] ) ? strtoupper( sanitize_text_field( $parsed['address_country'] ) ) : '',
    );

    adfoin_givewp_action_update_donor_meta_fields( $donor->id, $meta_payload );

    if ( isset( $parsed['donor_note'] ) && '' !== $parsed['donor_note'] ) {
        $donor->add_note( sanitize_textarea_field( $parsed['donor_note'] ) );
    }

    adfoin_givewp_action_log(
        $record,
        __( 'Donor updated successfully.', 'advanced-form-integration' ),
        array(
            'donor_id' => $donor->id,
        ),
        true
    );
}

function adfoin_givewp_action_create_donation( $record, $parsed ) {
    if ( ! function_exists( 'give_insert_payment' ) ) {
        adfoin_givewp_action_log( $record, __( 'GiveWP payment functions unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $form_id = isset( $parsed['form_id'] ) ? absint( $parsed['form_id'] ) : 0;
    if ( ! $form_id ) {
        adfoin_givewp_action_log( $record, __( 'Form ID is required to create a donation.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $amount_raw = isset( $parsed['amount'] ) ? $parsed['amount'] : '';
    $amount     = adfoin_givewp_action_sanitize_amount( $amount_raw, $form_id );
    if ( $amount <= 0 ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Donation amount must be greater than zero.', 'advanced-form-integration' ),
            array( 'amount' => $amount_raw ),
            false
        );
        return;
    }

    $donor_id  = isset( $parsed['donor_id'] ) && '' !== $parsed['donor_id'] ? absint( $parsed['donor_id'] ) : 0;
    $user_id   = isset( $parsed['user_id'] ) && '' !== $parsed['user_id'] ? absint( $parsed['user_id'] ) : 0;
    $email     = isset( $parsed['email'] ) ? sanitize_email( $parsed['email'] ) : '';
    $first_name = isset( $parsed['first_name'] ) ? sanitize_text_field( $parsed['first_name'] ) : '';
    $last_name  = isset( $parsed['last_name'] ) ? sanitize_text_field( $parsed['last_name'] ) : '';
    $title_prefix = isset( $parsed['title_prefix'] ) ? sanitize_text_field( $parsed['title_prefix'] ) : '';
    $company      = isset( $parsed['company'] ) ? sanitize_text_field( $parsed['company'] ) : '';
    $phone        = isset( $parsed['phone'] ) ? sanitize_text_field( $parsed['phone'] ) : '';
    $address_line1 = isset( $parsed['address_line1'] ) ? sanitize_text_field( $parsed['address_line1'] ) : '';
    $address_line2 = isset( $parsed['address_line2'] ) ? sanitize_text_field( $parsed['address_line2'] ) : '';
    $address_city  = isset( $parsed['address_city'] ) ? sanitize_text_field( $parsed['address_city'] ) : '';
    $address_state = isset( $parsed['address_state'] ) ? sanitize_text_field( $parsed['address_state'] ) : '';
    $address_zip   = isset( $parsed['address_zip'] ) ? sanitize_text_field( $parsed['address_zip'] ) : '';
    $address_country = isset( $parsed['address_country'] ) ? strtoupper( sanitize_text_field( $parsed['address_country'] ) ) : '';

    $contact_meta = array(
        'first_name'      => $first_name,
        'last_name'       => $last_name,
        'company'         => $company,
        'phone'           => $phone,
        'title_prefix'    => $title_prefix,
        'address_line1'   => $address_line1,
        'address_line2'   => $address_line2,
        'address_city'    => $address_city,
        'address_state'   => $address_state,
        'address_zip'     => $address_zip,
        'address_country' => $address_country,
    );

    $donor_details = adfoin_givewp_action_resolve_donor_for_donation( $donor_id, $email, $contact_meta, $user_id );
    if ( is_wp_error( $donor_details ) ) {
        adfoin_givewp_action_log( $record, $donor_details->get_error_message(), $donor_details->get_error_data(), false );
        return;
    }

    $donor_id = $donor_details['donor_id'];
    $email    = $donor_details['email'];
    $user_id  = $donor_details['user_id'];

    $contact_meta['first_name'] = '' !== $contact_meta['first_name'] ? $contact_meta['first_name'] : $donor_details['first_name'];
    $contact_meta['last_name']  = '' !== $contact_meta['last_name'] ? $contact_meta['last_name'] : $donor_details['last_name'];

    adfoin_givewp_action_update_donor_meta_fields( $donor_id, $contact_meta );

    $user_info = array(
        'first_name' => $contact_meta['first_name'],
        'last_name'  => $contact_meta['last_name'],
        'email'      => $email,
        'id'         => $user_id,
        'title'      => $title_prefix,
        'company'    => $company,
        'address'    => array(
            'line1'   => $address_line1,
            'line2'   => $address_line2,
            'city'    => $address_city,
            'state'   => $address_state,
            'zip'     => $address_zip,
            'country' => $address_country,
        ),
    );

    $currency = isset( $parsed['currency'] ) && '' !== $parsed['currency']
        ? strtoupper( sanitize_text_field( $parsed['currency'] ) )
        : adfoin_givewp_action_get_default_currency( $form_id );

    if ( ! $currency ) {
        $currency = give_get_currency();
    }

    $status = isset( $parsed['status'] ) ? sanitize_key( $parsed['status'] ) : 'pending';
    if ( 'complete' === $status ) {
        $status = 'publish';
    }

    $allowed_statuses = adfoin_givewp_action_get_allowed_statuses();
    if ( ! in_array( $status, $allowed_statuses, true ) ) {
        $status = 'pending';
    }

    $mode = isset( $parsed['mode'] ) ? strtolower( sanitize_key( $parsed['mode'] ) ) : '';
    if ( ! in_array( $mode, array( 'test', 'live' ), true ) ) {
        $mode = give_is_test_mode() ? 'test' : 'live';
    }

    $gateway = isset( $parsed['gateway'] ) && '' !== $parsed['gateway'] ? sanitize_key( $parsed['gateway'] ) : 'manual';
    $price_id = isset( $parsed['price_id'] ) && '' !== $parsed['price_id'] ? absint( $parsed['price_id'] ) : '';

    $purchase_key = isset( $parsed['purchase_key'] ) && '' !== $parsed['purchase_key']
        ? sanitize_text_field( $parsed['purchase_key'] )
        : adfoin_givewp_action_generate_purchase_key( $email );

    $form_title = isset( $parsed['donation_title'] ) && '' !== $parsed['donation_title']
        ? sanitize_text_field( $parsed['donation_title'] )
        : get_the_title( $form_id );

    $campaign_id = isset( $parsed['campaign_id'] ) && '' !== $parsed['campaign_id'] ? absint( $parsed['campaign_id'] ) : 0;

    $payment_data = array(
        'price'           => $amount,
        'give_form_title' => $form_title,
        'give_form_id'    => $form_id,
        'give_price_id'   => $price_id,
        'date'            => isset( $parsed['date'] ) && '' !== $parsed['date'] ? sanitize_text_field( $parsed['date'] ) : current_time( 'mysql' ),
        'user_email'      => $email,
        'purchase_key'    => $purchase_key,
        'currency'        => $currency,
        'user_info'       => $user_info,
        'status'          => $status,
        'gateway'         => $gateway,
        'mode'            => $mode,
        'donor_id'        => $donor_id,
        'campaign_id'     => $campaign_id,
    );

    $payment_id = give_insert_payment( $payment_data );

    if ( ! $payment_id ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Failed to create donation.', 'advanced-form-integration' ),
            $payment_data,
            false
        );
        return;
    }

    if ( isset( $parsed['meta_json'] ) && '' !== trim( $parsed['meta_json'] ) ) {
        $meta_array = adfoin_givewp_action_decode_json( $parsed['meta_json'] );
        if ( false === $meta_array ) {
            adfoin_givewp_action_log(
                $record,
                __( 'Meta JSON could not be parsed.', 'advanced-form-integration' ),
                array( 'meta_json' => $parsed['meta_json'] ),
                false
            );
        } else {
            foreach ( $meta_array as $meta_key => $meta_value ) {
                if ( '' === $meta_key ) {
                    continue;
                }
                give_update_payment_meta( $payment_id, sanitize_key( $meta_key ), maybe_serialize( $meta_value ) );
            }
        }
    }

    if ( isset( $parsed['donation_note'] ) && '' !== trim( $parsed['donation_note'] ) ) {
        give_insert_payment_note( $payment_id, sanitize_textarea_field( $parsed['donation_note'] ) );
    }

    if ( $status && 'pending' !== $status ) {
        give_update_payment_status( $payment_id, $status );
    }

    adfoin_givewp_action_log(
        $record,
        __( 'Donation created successfully.', 'advanced-form-integration' ),
        array(
            'donation_id' => $payment_id,
            'donor_id'    => $donor_id,
            'form_id'     => $form_id,
            'amount'      => $amount,
        ),
        true
    );
}

function adfoin_givewp_action_update_donation_status( $record, $parsed ) {
    if ( ! function_exists( 'give_update_payment_status' ) ) {
        adfoin_givewp_action_log( $record, __( 'GiveWP payment functions unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $donation_id = isset( $parsed['donation_id'] ) ? absint( $parsed['donation_id'] ) : 0;
    $status_raw  = isset( $parsed['status'] ) ? $parsed['status'] : '';
    $status      = sanitize_key( $status_raw );

    if ( ! $donation_id ) {
        adfoin_givewp_action_log( $record, __( 'Donation ID is required.', 'advanced-form-integration' ), array(), false );
        return;
    }

    if ( 'complete' === $status ) {
        $status = 'publish';
    }

    $allowed_statuses = adfoin_givewp_action_get_allowed_statuses();

    if ( ! in_array( $status, $allowed_statuses, true ) ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Invalid donation status supplied.', 'advanced-form-integration' ),
            array( 'status' => $status_raw ),
            false
        );
        return;
    }

    $payment = class_exists( 'Give_Payment' ) ? new Give_Payment( $donation_id ) : null;
    if ( ! $payment || empty( $payment->ID ) ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Donation could not be found.', 'advanced-form-integration' ),
            array( 'donation_id' => $donation_id ),
            false
        );
        return;
    }

    $updated = give_update_payment_status( $donation_id, $status );

    if ( ! $updated ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Failed to update donation status.', 'advanced-form-integration' ),
            array(
                'donation_id' => $donation_id,
                'status'      => $status,
            ),
            false
        );
        return;
    }

    adfoin_givewp_action_log(
        $record,
        __( 'Donation status updated successfully.', 'advanced-form-integration' ),
        array(
            'donation_id' => $donation_id,
            'status'      => $status,
        ),
        true
    );
}

function adfoin_givewp_action_add_donation_note( $record, $parsed ) {
    if ( ! function_exists( 'give_insert_payment_note' ) ) {
        adfoin_givewp_action_log( $record, __( 'GiveWP payment functions unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $donation_id = isset( $parsed['donation_id'] ) ? absint( $parsed['donation_id'] ) : 0;
    $note        = isset( $parsed['note'] ) ? sanitize_textarea_field( $parsed['note'] ) : '';

    if ( ! $donation_id || '' === $note ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Donation ID and note are required.', 'advanced-form-integration' ),
            array(
                'donation_id' => $donation_id,
                'note'        => $note,
            ),
            false
        );
        return;
    }

    $payment = class_exists( 'Give_Payment' ) ? new Give_Payment( $donation_id ) : null;
    if ( ! $payment || empty( $payment->ID ) ) {
        adfoin_givewp_action_log(
            $record,
            __( 'Donation could not be found.', 'advanced-form-integration' ),
            array( 'donation_id' => $donation_id ),
            false
        );
        return;
    }

    give_insert_payment_note( $donation_id, $note );

    adfoin_givewp_action_log(
        $record,
        __( 'Donation note added successfully.', 'advanced-form-integration' ),
        array(
            'donation_id' => $donation_id,
        ),
        true
    );
}

function adfoin_givewp_action_resolve_donor_for_donation( $donor_id, $email, $contact_meta, $user_id ) {
    if ( $donor_id ) {
        $donor = new Give_Donor( $donor_id );
        if ( empty( $donor->id ) ) {
            return new WP_Error(
                'givewp-donor-missing',
                __( 'The specified donor could not be found.', 'advanced-form-integration' ),
                array( 'donor_id' => $donor_id )
            );
        }

        if ( ! $email ) {
            $email = $donor->email;
        }

        if ( ! $user_id && isset( $donor->user_id ) ) {
            $user_id = (int) $donor->user_id;
        }

        $first_name = isset( $contact_meta['first_name'] ) && '' !== $contact_meta['first_name'] ? $contact_meta['first_name'] : $donor->get_meta( '_give_donor_first_name', true );
        $last_name  = isset( $contact_meta['last_name'] ) && '' !== $contact_meta['last_name'] ? $contact_meta['last_name'] : $donor->get_meta( '_give_donor_last_name', true );

        return array(
            'donor_id'   => $donor->id,
            'email'      => $email,
            'user_id'    => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );
    }

    if ( ! $email ) {
        return new WP_Error(
            'givewp-email-required',
            __( 'A donor email or donor ID is required to create a donation.', 'advanced-form-integration' ),
            array()
        );
    }

    $existing = Give()->donors ? Give()->donors->get_donor_by( 'email', $email ) : false;

    if ( $existing && isset( $existing->id ) ) {
        $donor = new Give_Donor( $existing->id );

        if ( ! $user_id && isset( $donor->user_id ) ) {
            $user_id = (int) $donor->user_id;
        }

        $first_name = isset( $contact_meta['first_name'] ) && '' !== $contact_meta['first_name'] ? $contact_meta['first_name'] : $donor->get_meta( '_give_donor_first_name', true );
        $last_name  = isset( $contact_meta['last_name'] ) && '' !== $contact_meta['last_name'] ? $contact_meta['last_name'] : $donor->get_meta( '_give_donor_last_name', true );

        return array(
            'donor_id'   => $donor->id,
            'email'      => $email,
            'user_id'    => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );
    }

    $donor = new Give_Donor();

    $name = trim( $contact_meta['first_name'] . ' ' . $contact_meta['last_name'] );
    $name = '' !== $name ? $name : $email;

    $create_args = array(
        'email' => $email,
        'name'  => $name,
    );

    if ( $user_id ) {
        $create_args['user_id'] = $user_id;
    }

    $donor_id = $donor->create( $create_args );

    if ( ! $donor_id ) {
        return new WP_Error(
            'givewp-donor-create-failed',
            __( 'Failed to create donor for the donation.', 'advanced-form-integration' ),
            $create_args
        );
    }

    return array(
        'donor_id'   => $donor_id,
        'email'      => $email,
        'user_id'    => $user_id,
        'first_name' => $contact_meta['first_name'],
        'last_name'  => $contact_meta['last_name'],
    );
}

function adfoin_givewp_action_update_donor_meta_fields( $donor_id, $fields ) {
    if ( ! $donor_id || ! isset( Give()->donor_meta ) ) {
        return;
    }

    $meta_map = adfoin_givewp_action_meta_map();

    foreach ( $meta_map as $field_key => $meta_key ) {
        if ( ! isset( $fields[ $field_key ] ) ) {
            continue;
        }

        $value = $fields[ $field_key ];

        if ( '' === $value ) {
            continue;
        }

        Give()->donor_meta->update_meta( $donor_id, $meta_key, $value );
    }
}

function adfoin_givewp_action_meta_map() {
    return array(
        'first_name'      => '_give_donor_first_name',
        'last_name'       => '_give_donor_last_name',
        'company'         => '_give_donor_company_name',
        'phone'           => '_give_donor_phone',
        'title_prefix'    => '_give_donor_title_prefix',
        'address_line1'   => '_give_donor_address_billing_line1_0',
        'address_line2'   => '_give_donor_address_billing_line2_0',
        'address_city'    => '_give_donor_address_billing_city_0',
        'address_state'   => '_give_donor_address_billing_state_0',
        'address_zip'     => '_give_donor_address_billing_zip_0',
        'address_country' => '_give_donor_address_billing_country_0',
    );
}

function adfoin_givewp_action_sanitize_payment_ids( $ids ) {
    if ( '' === trim( $ids ) ) {
        return '';
    }

    $parts = array_map( 'trim', explode( ',', $ids ) );
    $parts = array_filter(
        array_map(
            static function( $id ) {
                return absint( $id );
            },
            $parts
        )
    );

    if ( empty( $parts ) ) {
        return '';
    }

    return implode( ',', array_unique( $parts ) );
}

function adfoin_givewp_action_sanitize_amount( $amount, $form_id ) {
    if ( function_exists( 'give_sanitize_amount' ) ) {
        return give_sanitize_amount(
            $amount,
            array(
                'form_id' => $form_id,
            )
        );
    }

    return floatval( $amount );
}

function adfoin_givewp_action_get_default_currency( $form_id ) {
    if ( function_exists( 'give_get_currency' ) ) {
        return give_get_currency( $form_id );
    }

    return '';
}

function adfoin_givewp_action_get_allowed_statuses() {
    if ( function_exists( 'give_get_payment_status_keys' ) ) {
        return give_get_payment_status_keys();
    }

    return array( 'pending', 'publish', 'refunded', 'failed', 'cancelled', 'abandoned', 'preapproval', 'processing', 'revoked' );
}

function adfoin_givewp_action_generate_purchase_key( $email ) {
    $auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_generate_password( 32, false );

    return strtolower(
        md5(
            $email . date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) . $auth_key . wp_rand()
        )
    );
}

function adfoin_givewp_action_decode_json( $value ) {
    if ( '' === trim( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return false;
    }

    return $decoded;
}

function adfoin_givewp_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'givewp', $log_args, $record );
}

