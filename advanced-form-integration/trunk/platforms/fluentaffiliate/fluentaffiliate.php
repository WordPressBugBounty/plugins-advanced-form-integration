<?php

add_filter( 'adfoin_action_providers', 'adfoin_fluentaffiliate_actions', 10, 1 );

function adfoin_fluentaffiliate_actions( $actions ) {

    $actions['fluentaffiliate'] = array(
        'title' => __( 'Fluent Affiliate', 'advanced-form-integration' ),
        'tasks' => array(
            'create_affiliate' => __( 'Create Affiliate', 'advanced-form-integration' ),
            'create_referral'  => __( 'Create Referral', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_fluentaffiliate_action_fields' );

function adfoin_fluentaffiliate_action_fields() {
    ?>
    <script type="text/template" id="fluentaffiliate-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_affiliate'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide an existing WordPress user ID or email. If no user exists, a new user will be created using the supplied email/username. Optional fields may be left blank.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'create_referral'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Affiliate ID and amount are required. Status should be unpaid, pending, or rejected. Amounts are stored in the site currency.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_fluentaffiliate_job_queue', 'adfoin_fluentaffiliate_job_queue', 10, 1 );

function adfoin_fluentaffiliate_job_queue( $data ) {
    adfoin_fluentaffiliate_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_fluentaffiliate_send_data( $record, $posted_data ) {
    if ( ! class_exists( '\FluentAffiliate\App\Models\User' ) ) {
        adfoin_fluentaffiliate_log( $record, __( 'Fluent Affiliate is not active.', 'advanced-form-integration' ), array(), false );
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

    if ( 'create_affiliate' === $task ) {
        adfoin_fluentaffiliate_create_affiliate( $record, $parsed );
    } elseif ( 'create_referral' === $task ) {
        adfoin_fluentaffiliate_create_referral( $record, $parsed );
    }
}

function adfoin_fluentaffiliate_create_affiliate( $record, $parsed ) {
    $user_id = isset( $parsed['user_id'] ) ? absint( $parsed['user_id'] ) : 0;
    $email   = isset( $parsed['user_email'] ) ? sanitize_email( $parsed['user_email'] ) : '';

    if ( ! $user_id && $email ) {
        $existing_user = get_user_by( 'email', $email );
        if ( $existing_user ) {
            $user_id = $existing_user->ID;
        }
    }

    $new_user_created = false;

    if ( ! $user_id && $email ) {
        $login = isset( $parsed['user_login'] ) ? sanitize_user( $parsed['user_login'], true ) : '';

        if ( ! $login ) {
            $login = sanitize_user( current( explode( '@', $email ) ), true );
        }

        if ( ! $login ) {
            $login = 'affiliate_' . wp_generate_password( 6, false );
        }

        $original_login = $login;
        $suffix         = 1;
        while ( username_exists( $login ) ) {
            $login = $original_login . '_' . $suffix;
            $suffix++;
        }

        $user_args = array(
            'user_login' => $login,
            'user_pass'  => wp_generate_password( 18 ),
            'user_email' => $email,
        );

        if ( ! empty( $parsed['display_name'] ) ) {
            $user_args['display_name'] = sanitize_text_field( $parsed['display_name'] );
        }

        if ( ! empty( $parsed['role'] ) ) {
            $user_args['role'] = sanitize_key( $parsed['role'] );
        }

        if ( ! empty( $parsed['user_url'] ) ) {
            $user_args['user_url'] = esc_url_raw( $parsed['user_url'] );
        }

        $user_id = wp_insert_user( $user_args );

        if ( is_wp_error( $user_id ) ) {
            adfoin_fluentaffiliate_log(
                $record,
                sprintf(
                    /* translators: %s error message */
                    __( 'Failed to create WordPress user: %s', 'advanced-form-integration' ),
                    $user_id->get_error_message()
                ),
                array(
                    'user_email' => $email,
                    'user_login' => $user_args['user_login'],
                ),
                false
            );
            return;
        }

        $new_user_created = true;
    }

    if ( ! $user_id ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'User ID or valid email is required to create an affiliate.', 'advanced-form-integration' ),
            array(),
            false
        );
        return;
    }

    if ( ! empty( $parsed['first_name'] ) ) {
        update_user_meta( $user_id, 'first_name', sanitize_text_field( $parsed['first_name'] ) );
    }

    if ( ! empty( $parsed['last_name'] ) ) {
        update_user_meta( $user_id, 'last_name', sanitize_text_field( $parsed['last_name'] ) );
    }

    if ( ! empty( $parsed['display_name'] ) ) {
        wp_update_user(
            array(
                'ID'           => $user_id,
                'display_name' => sanitize_text_field( $parsed['display_name'] ),
            )
        );
    }

    if ( ! empty( $parsed['user_url'] ) ) {
        wp_update_user(
            array(
                'ID'       => $user_id,
                'user_url' => esc_url_raw( $parsed['user_url'] ),
            )
        );
    }

    $status = isset( $parsed['status'] ) ? strtolower( sanitize_text_field( $parsed['status'] ) ) : '';
    $valid_statuses = array( 'pending', 'active', 'inactive' );
    if ( ! in_array( $status, $valid_statuses, true ) ) {
        if ( class_exists( '\FluentAffiliate\App\Modules\Auth\AuthHelper' ) ) {
            $status = \FluentAffiliate\App\Modules\Auth\AuthHelper::getInitialAffiliateStatus();
        } else {
            $status = 'pending';
        }
    }

    $rate_type = isset( $parsed['rate_type'] ) ? strtolower( sanitize_key( $parsed['rate_type'] ) ) : 'default';
    $allowed_rate_types = array( 'default', 'group', 'flat', 'percentage' );
    if ( ! in_array( $rate_type, $allowed_rate_types, true ) ) {
        $rate_type = 'default';
    }

    $rate = null;
    if ( isset( $parsed['rate'] ) && $parsed['rate'] !== '' ) {
        $rate = floatval( $parsed['rate'] );
        if ( $rate < 0 ) {
            $rate = 0;
        }
    }

    $group_id = null;
    if ( 'group' === $rate_type ) {
        $group_id_raw = isset( $parsed['group_id'] ) ? absint( $parsed['group_id'] ) : 0;
        if ( ! $group_id_raw ) {
            adfoin_fluentaffiliate_log(
                $record,
                __( 'Group ID is required when rate type is group.', 'advanced-form-integration' ),
                array( 'user_id' => $user_id ),
                false
            );
            return;
        }

        if ( class_exists( '\FluentAffiliate\App\Models\AffiliateGroup' ) ) {
            $group = \FluentAffiliate\App\Models\AffiliateGroup::query()->find( $group_id_raw );
            if ( ! $group ) {
                adfoin_fluentaffiliate_log(
                    $record,
                    __( 'Provided Affiliate Group was not found.', 'advanced-form-integration' ),
                    array(
                        'user_id'  => $user_id,
                        'group_id' => $group_id_raw,
                    ),
                    false
                );
                return;
            }
        }

        $group_id = $group_id_raw;
    }

    $payment_email = isset( $parsed['payment_email'] ) ? sanitize_email( $parsed['payment_email'] ) : '';
    if ( ! $payment_email ) {
        if ( $email ) {
            $payment_email = $email;
        } else {
            $wp_user = get_user_by( 'id', $user_id );
            if ( $wp_user ) {
                $payment_email = $wp_user->user_email;
            }
        }
    }

    if ( $payment_email && ! is_email( $payment_email ) ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Payment email is invalid.', 'advanced-form-integration' ),
            array(
                'user_id'       => $user_id,
                'payment_email' => $payment_email,
            ),
            false
        );
        return;
    }

    $settings = array();
    if ( isset( $parsed['settings_disable_new_ref_email'] ) && '' !== $parsed['settings_disable_new_ref_email'] ) {
        $settings['disable_new_ref_email'] = adfoin_fluentaffiliate_normalize_bool( $parsed['settings_disable_new_ref_email'] ) ? 'yes' : 'no';
    }

    $extra = array(
        'status'        => $status,
        'rate_type'     => $rate_type,
        'payment_email' => $payment_email,
    );

    if ( null !== $rate && 'default' !== $rate_type ) {
        $extra['rate'] = $rate;
    }

    if ( null !== $group_id ) {
        $extra['group_id'] = $group_id;
    }

    if ( isset( $parsed['note'] ) && '' !== $parsed['note'] ) {
        $extra['note'] = sanitize_textarea_field( $parsed['note'] );
    }

    if ( isset( $parsed['custom_param'] ) && '' !== $parsed['custom_param'] ) {
        $extra['custom_param'] = sanitize_text_field( $parsed['custom_param'] );
    }

    if ( ! empty( $settings ) ) {
        $extra['settings'] = $settings;
    }

    $extra = array_filter(
        $extra,
        static function ( $value ) {
            return null !== $value && '' !== $value;
        }
    );

    $user_model = \FluentAffiliate\App\Models\User::query()->find( $user_id );

    if ( ! $user_model ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Unable to load Fluent Affiliate user model.', 'advanced-form-integration' ),
            array( 'user_id' => $user_id ),
            false
        );
        return;
    }

    $affiliate = $user_model->syncAffiliateProfile( $extra );

    if ( ! $affiliate ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Failed to sync affiliate profile.', 'advanced-form-integration' ),
            array(
                'user_id' => $user_id,
                'data'    => $extra,
            ),
            false
        );
        return;
    }

    adfoin_fluentaffiliate_log(
        $record,
        __( 'Affiliate created/updated successfully.', 'advanced-form-integration' ),
        array(
            'user_id'           => $user_id,
            'affiliate_id'      => $affiliate->id,
            'status'            => $affiliate->status,
            'new_user_created'  => $new_user_created,
        ),
        true
    );
}

function adfoin_fluentaffiliate_create_referral( $record, $parsed ) {
    if ( ! class_exists( '\FluentAffiliate\App\Models\Referral' ) ) {
        adfoin_fluentaffiliate_log( $record, __( 'Fluent Affiliate referral model is not available.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $affiliate_id = isset( $parsed['affiliate_id'] ) ? absint( $parsed['affiliate_id'] ) : 0;

    if ( ! $affiliate_id ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Affiliate ID is required to create a referral.', 'advanced-form-integration' ),
            array(),
            false
        );
        return;
    }

    $affiliate = \FluentAffiliate\App\Models\Affiliate::query()->find( $affiliate_id );

    if ( ! $affiliate ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Affiliate was not found.', 'advanced-form-integration' ),
            array( 'affiliate_id' => $affiliate_id ),
            false
        );
        return;
    }

    if ( 'active' !== $affiliate->status ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Cannot create referral for an inactive affiliate.', 'advanced-form-integration' ),
            array(
                'affiliate_id' => $affiliate_id,
                'status'       => $affiliate->status,
            ),
            false
        );
        return;
    }

    $amount_raw = isset( $parsed['amount'] ) ? $parsed['amount'] : '';
    if ( '' === $amount_raw ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Referral amount is required.', 'advanced-form-integration' ),
            array(
                'affiliate_id' => $affiliate_id,
            ),
            false
        );
        return;
    }

    $amount = floatval( $amount_raw );
    $amount = ( (int) ( $amount * 100 ) ) / 100;

    $status = isset( $parsed['status'] ) ? strtolower( sanitize_text_field( $parsed['status'] ) ) : 'unpaid';
    $allowed_status = array( 'unpaid', 'pending', 'rejected' );
    if ( ! in_array( $status, $allowed_status, true ) ) {
        $status = 'unpaid';
    }

    $type = isset( $parsed['type'] ) ? strtolower( sanitize_text_field( $parsed['type'] ) ) : 'sale';
    $allowed_types = array( 'sale', 'opt_in' );
    if ( ! in_array( $type, $allowed_types, true ) ) {
        $type = 'sale';
    }

    $referral_data = array(
        'affiliate_id' => $affiliate_id,
        'amount'       => $amount,
        'status'       => $status,
        'type'         => $type,
        'description'  => isset( $parsed['description'] ) ? sanitize_textarea_field( $parsed['description'] ) : '',
        'provider'     => isset( $parsed['provider'] ) ? sanitize_key( $parsed['provider'] ) : 'manual',
        'provider_id'  => isset( $parsed['provider_id'] ) && '' !== $parsed['provider_id'] ? intval( $parsed['provider_id'] ) : null,
    );

    if ( isset( $parsed['order_total'] ) && '' !== $parsed['order_total'] ) {
        $referral_data['order_total'] = ( (int) ( floatval( $parsed['order_total'] ) * 100 ) ) / 100;
    }

    if ( isset( $parsed['currency'] ) && '' !== $parsed['currency'] ) {
        $referral_data['currency'] = sanitize_text_field( $parsed['currency'] );
    }

    if ( isset( $parsed['utm_campaign'] ) && '' !== $parsed['utm_campaign'] ) {
        $referral_data['utm_campaign'] = sanitize_text_field( $parsed['utm_campaign'] );
    }

    if ( isset( $parsed['provider_sub_id'] ) && '' !== $parsed['provider_sub_id'] ) {
        $referral_data['provider_sub_id'] = sanitize_text_field( $parsed['provider_sub_id'] );
    }

    if ( isset( $parsed['customer_id'] ) && '' !== $parsed['customer_id'] ) {
        $referral_data['customer_id'] = intval( $parsed['customer_id'] );
    }

    if ( isset( $parsed['visit_id'] ) && '' !== $parsed['visit_id'] ) {
        $referral_data['visit_id'] = intval( $parsed['visit_id'] );
    }

    if ( isset( $parsed['parent_id'] ) && '' !== $parsed['parent_id'] ) {
        $referral_data['parent_id'] = intval( $parsed['parent_id'] );
    }

    if ( isset( $parsed['products'] ) && '' !== $parsed['products'] ) {
        $products = json_decode( $parsed['products'], true );
        if ( is_array( $products ) ) {
            $referral_data['products'] = $products;
        }
    }

    if ( isset( $parsed['settings_json'] ) && '' !== $parsed['settings_json'] ) {
        $settings = json_decode( $parsed['settings_json'], true );
        if ( is_array( $settings ) ) {
            $referral_data['settings'] = $settings;
        }
    }

    $referral_data = array_filter(
        $referral_data,
        static function ( $value ) {
            return null !== $value && '' !== $value;
        }
    );

    $referral = \FluentAffiliate\App\Models\Referral::create( $referral_data );

    if ( ! $referral ) {
        adfoin_fluentaffiliate_log(
            $record,
            __( 'Failed to create referral.', 'advanced-form-integration' ),
            array(
                'affiliate_id' => $affiliate_id,
                'amount'       => $amount,
            ),
            false
        );
        return;
    }

    do_action( 'fluent_affiliate/referral_marked_unpaid', $referral );

    $affiliate->recountEarnings();

    adfoin_fluentaffiliate_log(
        $record,
        __( 'Referral created successfully.', 'advanced-form-integration' ),
        array(
            'affiliate_id' => $affiliate_id,
            'referral_id'  => $referral->id,
            'amount'       => $amount,
            'status'       => $status,
        ),
        true
    );
}

function adfoin_fluentaffiliate_normalize_bool( $value ) {
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

function adfoin_fluentaffiliate_log( $record, $message, $payload, $success ) {
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
        'body'   => $payload,
    );

    adfoin_add_to_log( $log_response, 'fluentaffiliate', $log_args, $record );
}
