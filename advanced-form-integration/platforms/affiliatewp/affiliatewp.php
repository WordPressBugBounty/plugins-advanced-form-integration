<?php

add_filter( 'adfoin_action_providers', 'adfoin_affiliatewp_actions', 10, 1 );

function adfoin_affiliatewp_actions( $actions ) {

    $actions['affiliatewp'] = array(
        'title' => __( 'AffiliateWP', 'advanced-form-integration' ),
        'tasks' => array(
            'add_affiliate' => __( 'Create Affiliate', 'advanced-form-integration' ),
            'add_referral'  => __( 'Create Referral', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_affiliatewp_action_fields' );

function adfoin_affiliatewp_action_fields() {
    ?>
    <script type="text/template" id="affiliatewp-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_affiliate'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Either provide an existing WordPress user ID or supply a user email to allow AffiliateWP to create the user automatically. Other mapped fields are optional.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_referral'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Set the affiliate ID directly or pass the related user ID / username so AffiliateWP can locate the affiliate. Amount, reference, and status fields are recommended.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_affiliatewp_job_queue', 'adfoin_affiliatewp_job_queue', 10, 1 );

function adfoin_affiliatewp_job_queue( $data ) {
    adfoin_affiliatewp_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_affiliatewp_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'affwp_add_affiliate' ) || ! function_exists( 'affwp_add_referral' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    $prepared_data = array();

    if ( is_array( $field_data ) ) {
        foreach ( $field_data as $key => $value ) {
            if ( '' === $key ) {
                continue;
            }

            $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

            if ( '' === $parsed_value || null === $parsed_value ) {
                continue;
            }

            $prepared_data[ $key ] = $parsed_value;
        }
    }

    $request_payload = $prepared_data;
    $response_body   = array( 'success' => false );
    $status_code     = 400;

    if ( 'add_affiliate' === $task ) {
        if ( ! function_exists( 'affwp_add_affiliate' ) ) {
            $response_body['message'] = __( 'AffiliateWP plugin is not active or missing required functions.', 'advanced-form-integration' );
        } else {
            $affiliate_args = adfoin_affiliatewp_prepare_affiliate_args( $prepared_data );

            if ( empty( $affiliate_args['user_id'] ) && empty( $affiliate_args['user_email'] ) ) {
                $response_body['message'] = __( 'Affiliate creation requires either a user ID or user email.', 'advanced-form-integration' );
            } else {
                $affiliate_id = affwp_add_affiliate( $affiliate_args );

                if ( $affiliate_id ) {
                    $status_code              = 200;
                    $response_body['success'] = true;
                    $response_body['message'] = __( 'Affiliate created successfully.', 'advanced-form-integration' );
                    $response_body['id']      = $affiliate_id;
                } else {
                    $response_body['message'] = __( 'Failed to create affiliate. Please verify the supplied data.', 'advanced-form-integration' );
                }
            }
        }
    } elseif ( 'add_referral' === $task ) {
        if ( ! function_exists( 'affwp_add_referral' ) ) {
            $response_body['message'] = __( 'AffiliateWP plugin is not active or missing required functions.', 'advanced-form-integration' );
        } else {
            $referral_args = adfoin_affiliatewp_prepare_referral_args( $prepared_data );

            if ( empty( $referral_args['affiliate_id'] ) && empty( $referral_args['user_id'] ) && empty( $referral_args['user_name'] ) ) {
                $response_body['message'] = __( 'Referral creation requires an affiliate, user ID, or username.', 'advanced-form-integration' );
            } else {
                $referral_id = affwp_add_referral( $referral_args );

                if ( $referral_id ) {
                    $status_code              = 200;
                    $response_body['success'] = true;
                    $response_body['message'] = __( 'Referral created successfully.', 'advanced-form-integration' );
                    $response_body['id']      = $referral_id;
                } else {
                    $response_body['message'] = __( 'Failed to create referral. Please verify the supplied data.', 'advanced-form-integration' );
                }
            }
        }
    } else {
        return;
    }

    if ( isset( $request_payload['user_pass'] ) ) {
        $request_payload['user_pass'] = '********';
    }

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    $log_response = array(
        'response' => array(
            'code'    => $status_code,
            'message' => $response_body['message'],
        ),
        'body'     => $response_body,
    );

    adfoin_add_to_log( $log_response, 'affiliatewp', $log_args, $record );
}

function adfoin_affiliatewp_prepare_affiliate_args( $data ) {
    $args = array();

    $map = array(
        'user_id'             => 'absint',
        'user_email'          => 'sanitize_email',
        'user_name'           => 'sanitize_text_field',
        'status'              => 'sanitize_text_field',
        'rate'                => 'sanitize_text_field',
        'rate_type'           => 'sanitize_text_field',
        'flat_rate_basis'     => 'sanitize_text_field',
        'payment_email'       => 'sanitize_email',
        'notes'               => 'wp_kses_post',
        'website_url'         => 'esc_url_raw',
        'date_registered'     => 'sanitize_text_field',
        'dynamic_coupon'      => null,
        'registration_method' => 'sanitize_text_field',
        'registration_url'    => 'esc_url_raw',
    );

    foreach ( $map as $key => $callback ) {
        if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] ) {
            continue;
        }

        $value = $data[ $key ];

        if ( 'dynamic_coupon' === $key ) {
            $args[ $key ] = adfoin_affiliatewp_to_bool( $value );
        } elseif ( $callback && is_callable( $callback ) ) {
            $args[ $key ] = call_user_func( $callback, $value );
        } else {
            $args[ $key ] = $value;
        }
    }

    return $args;
}

function adfoin_affiliatewp_prepare_referral_args( $data ) {
    $args = array();

    $map = array(
        'affiliate_id' => 'absint',
        'user_id'      => 'absint',
        'user_name'    => 'sanitize_text_field',
        'amount'       => 'sanitize_text_field',
        'description'  => 'sanitize_text_field',
        'order_total'  => 'sanitize_text_field',
        'reference'    => 'sanitize_text_field',
        'parent_id'    => 'absint',
        'currency'     => 'sanitize_text_field',
        'campaign'     => 'sanitize_text_field',
        'context'      => 'sanitize_text_field',
        'date'         => 'sanitize_text_field',
        'type'         => 'sanitize_text_field',
        'flag'         => 'sanitize_text_field',
        'status'       => 'sanitize_text_field',
        'visit_id'     => 'absint',
    );

    foreach ( $map as $key => $callback ) {
        if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] ) {
            continue;
        }

        $value = $data[ $key ];

        if ( $callback && is_callable( $callback ) ) {
            $args[ $key ] = call_user_func( $callback, $value );
        } else {
            $args[ $key ] = $value;
        }
    }

    if ( ! empty( $data['custom'] ) ) {
        $args['custom'] = adfoin_affiliatewp_maybe_json( $data['custom'] );
    }

    if ( ! empty( $data['products'] ) ) {
        $args['products'] = adfoin_affiliatewp_maybe_json( $data['products'] );
    }

    return $args;
}

function adfoin_affiliatewp_to_bool( $value ) {
    if ( is_bool( $value ) ) {
        return $value;
    }

    $value = strtolower( trim( (string) $value ) );

    return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
}

function adfoin_affiliatewp_maybe_json( $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    $decoded = json_decode( $value, true );

    if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
        return $decoded;
    }

    return $value;
}
