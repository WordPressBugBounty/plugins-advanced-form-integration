<?php

add_filter( 'adfoin_action_providers', 'adfoin_mycred_actions', 10, 1 );

function adfoin_mycred_actions( $actions ) {
    $actions['mycred'] = array(
        'title' => __( 'myCred', 'advanced-form-integration' ),
        'tasks' => array(
            'award_points'     => __( 'Award Points', 'advanced-form-integration' ),
            'deduct_points'    => __( 'Deduct Points', 'advanced-form-integration' ),
            'set_balance'      => __( 'Set Balance', 'advanced-form-integration' ),
            'add_log_entry'    => __( 'Add Log Entry', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_mycred_action_fields' );

function adfoin_mycred_action_fields() {
    ?>
    <script type="text/template" id="mycred-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'award_points'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a user identifier and positive amount to credit. Reference and log entry default if left blank. Point type defaults to the site default key.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'deduct_points'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a user identifier and positive amount to debit. Reference and log entry default if not supplied. Point type defaults to the site default key.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'set_balance'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Sets the user balance to the supplied amount. The adjustment is logged automatically. Point type defaults to the site default key.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'add_log_entry'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Creates a custom myCred log entry without altering balances. A non-zero amount is still required for the log.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_mycred_job_queue', 'adfoin_mycred_job_queue', 10, 1 );

function adfoin_mycred_job_queue( $data ) {
    adfoin_mycred_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mycred_send_data( $record, $posted_data ) {
    if ( ! function_exists( 'mycred' ) ) {
        adfoin_mycred_action_log( $record, __( 'myCred is not active.', 'advanced-form-integration' ), array(), false );
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
        case 'award_points':
            adfoin_mycred_action_award_points( $record, $parsed );
            break;
        case 'deduct_points':
            adfoin_mycred_action_deduct_points( $record, $parsed );
            break;
        case 'set_balance':
            adfoin_mycred_action_set_balance( $record, $parsed );
            break;
        case 'add_log_entry':
            adfoin_mycred_action_add_log_entry( $record, $parsed );
            break;
        default:
            adfoin_mycred_action_log(
                $record,
                __( 'Unknown myCred task received.', 'advanced-form-integration' ),
                array( 'task' => $task ),
                false
            );
            break;
    }
}

function adfoin_mycred_action_award_points( $record, $parsed ) {
    $user = adfoin_mycred_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_mycred_action_log(
            $record,
            __( 'User could not be located for awarding points.', 'advanced-form-integration' ),
            adfoin_mycred_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $amount = isset( $parsed['amount'] ) ? floatval( $parsed['amount'] ) : 0;
    if ( $amount <= 0 ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Amount must be greater than zero.', 'advanced-form-integration' ),
            array( 'amount' => $parsed['amount'] ?? '' ),
            false
        );
        return;
    }

    $point_type = isset( $parsed['point_type'] ) && $parsed['point_type'] !== '' ? sanitize_key( $parsed['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;
    $reference  = isset( $parsed['reference'] ) && $parsed['reference'] !== '' ? sanitize_key( $parsed['reference'] ) : 'adfoin_award';
    $log_entry  = isset( $parsed['log_entry'] ) && $parsed['log_entry'] !== '' ? sanitize_text_field( $parsed['log_entry'] ) : __( 'Points awarded via Advanced Form Integration', 'advanced-form-integration' );
    $ref_id     = isset( $parsed['ref_id'] ) ? sanitize_text_field( $parsed['ref_id'] ) : '';
    $data       = adfoin_mycred_action_prepare_data( $parsed );

    $result = mycred_add( $reference, $user->ID, $amount, $log_entry, $ref_id, $data, $point_type );

    if ( ! $result ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Failed to award points.', 'advanced-form-integration' ),
            array(
                'user_id'    => $user->ID,
                'amount'     => $amount,
                'point_type' => $point_type,
            ),
            false
        );
        return;
    }

    adfoin_mycred_action_log(
        $record,
        __( 'Points awarded successfully.', 'advanced-form-integration' ),
        array(
            'user_id'    => $user->ID,
            'amount'     => $amount,
            'point_type' => $point_type,
        ),
        true
    );
}

function adfoin_mycred_action_deduct_points( $record, $parsed ) {
    $user = adfoin_mycred_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_mycred_action_log(
            $record,
            __( 'User could not be located for deducting points.', 'advanced-form-integration' ),
            adfoin_mycred_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $amount = isset( $parsed['amount'] ) ? floatval( $parsed['amount'] ) : 0;
    if ( $amount <= 0 ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Amount must be greater than zero.', 'advanced-form-integration' ),
            array( 'amount' => $parsed['amount'] ?? '' ),
            false
        );
        return;
    }

    $point_type = isset( $parsed['point_type'] ) && $parsed['point_type'] !== '' ? sanitize_key( $parsed['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;
    $reference  = isset( $parsed['reference'] ) && $parsed['reference'] !== '' ? sanitize_key( $parsed['reference'] ) : 'adfoin_deduct';
    $log_entry  = isset( $parsed['log_entry'] ) && $parsed['log_entry'] !== '' ? sanitize_text_field( $parsed['log_entry'] ) : __( 'Points deducted via Advanced Form Integration', 'advanced-form-integration' );
    $ref_id     = isset( $parsed['ref_id'] ) ? sanitize_text_field( $parsed['ref_id'] ) : '';
    $data       = adfoin_mycred_action_prepare_data( $parsed );

    $result = mycred_subtract( $reference, $user->ID, $amount, $log_entry, $ref_id, $data, $point_type );

    if ( ! $result ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Failed to deduct points.', 'advanced-form-integration' ),
            array(
                'user_id'    => $user->ID,
                'amount'     => $amount,
                'point_type' => $point_type,
            ),
            false
        );
        return;
    }

    adfoin_mycred_action_log(
        $record,
        __( 'Points deducted successfully.', 'advanced-form-integration' ),
        array(
            'user_id'    => $user->ID,
            'amount'     => $amount,
            'point_type' => $point_type,
        ),
        true
    );
}

function adfoin_mycred_action_set_balance( $record, $parsed ) {
    $user = adfoin_mycred_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_mycred_action_log(
            $record,
            __( 'User could not be located for balance update.', 'advanced-form-integration' ),
            adfoin_mycred_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $target_balance = isset( $parsed['target_balance'] ) ? floatval( $parsed['target_balance'] ) : null;
    if ( null === $target_balance ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Target balance is required.', 'advanced-form-integration' ),
            array( 'target_balance' => $parsed['target_balance'] ?? '' ),
            false
        );
        return;
    }

    $point_type = isset( $parsed['point_type'] ) && $parsed['point_type'] !== '' ? sanitize_key( $parsed['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;
    $mycred     = mycred( $point_type );
    if ( ! is_object( $mycred ) ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Unable to load myCred point type.', 'advanced-form-integration' ),
            array( 'point_type' => $point_type ),
            false
        );
        return;
    }

    $current_balance = $mycred->get_users_balance( $user->ID, $point_type );
    $difference      = $mycred->number( $target_balance - $current_balance );

    if ( 0 === $difference ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Balance already matches target amount.', 'advanced-form-integration' ),
            array(
                'user_id'         => $user->ID,
                'point_type'      => $point_type,
                'target_balance'  => $target_balance,
            ),
            true
        );
        return;
    }

    $new_balance = $mycred->update_users_balance( $user->ID, $difference, $point_type );

    if ( null === $new_balance ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Failed to update balance.', 'advanced-form-integration' ),
            array(
                'user_id'         => $user->ID,
                'point_type'      => $point_type,
                'target_balance'  => $target_balance,
            ),
            false
        );
        return;
    }

    $reference = isset( $parsed['reference'] ) && $parsed['reference'] !== '' ? sanitize_key( $parsed['reference'] ) : 'adfoin_balance_adjust';
    $log_entry = isset( $parsed['log_entry'] ) && $parsed['log_entry'] !== '' ? sanitize_text_field( $parsed['log_entry'] ) : __( 'Balance adjusted via Advanced Form Integration', 'advanced-form-integration' );
    $ref_id    = isset( $parsed['ref_id'] ) ? sanitize_text_field( $parsed['ref_id'] ) : '';
    $data      = adfoin_mycred_action_prepare_data( $parsed );

    if ( method_exists( $mycred, 'add_to_log' ) ) {
        $mycred->add_to_log( $reference, $user->ID, $difference, $log_entry, $ref_id, $data, $point_type );
    }

    adfoin_mycred_action_log(
        $record,
        __( 'Balance updated successfully.', 'advanced-form-integration' ),
        array(
            'user_id'         => $user->ID,
            'point_type'      => $point_type,
            'target_balance'  => $target_balance,
            'previous_balance'=> $current_balance,
            'new_balance'     => $new_balance,
        ),
        true
    );
}

function adfoin_mycred_action_add_log_entry( $record, $parsed ) {
    $user = adfoin_mycred_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_mycred_action_log(
            $record,
            __( 'User could not be located for log entry.', 'advanced-form-integration' ),
            adfoin_mycred_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $amount = isset( $parsed['amount'] ) ? floatval( $parsed['amount'] ) : 0;
    if ( 0 == $amount ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Amount must be non-zero for a log entry.', 'advanced-form-integration' ),
            array( 'amount' => $parsed['amount'] ?? '' ),
            false
        );
        return;
    }

    $point_type = isset( $parsed['point_type'] ) && $parsed['point_type'] !== '' ? sanitize_key( $parsed['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;
    $mycred     = mycred( $point_type );
    if ( ! is_object( $mycred ) ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Unable to load myCred point type.', 'advanced-form-integration' ),
            array( 'point_type' => $point_type ),
            false
        );
        return;
    }

    $reference = isset( $parsed['reference'] ) && $parsed['reference'] !== '' ? sanitize_key( $parsed['reference'] ) : 'adfoin_log';
    $log_entry = isset( $parsed['log_entry'] ) && $parsed['log_entry'] !== '' ? sanitize_text_field( $parsed['log_entry'] ) : __( 'Log entry recorded via Advanced Form Integration', 'advanced-form-integration' );
    $ref_id    = isset( $parsed['ref_id'] ) ? sanitize_text_field( $parsed['ref_id'] ) : '';
    $data      = adfoin_mycred_action_prepare_data( $parsed );

    $result = $mycred->add_to_log( $reference, $user->ID, $amount, $log_entry, $ref_id, $data, $point_type );

    if ( ! $result ) {
        adfoin_mycred_action_log(
            $record,
            __( 'Failed to add log entry.', 'advanced-form-integration' ),
            array(
                'user_id'    => $user->ID,
                'point_type' => $point_type,
                'amount'     => $amount,
            ),
            false
        );
        return;
    }

    adfoin_mycred_action_log(
        $record,
        __( 'Log entry added successfully.', 'advanced-form-integration' ),
        array(
            'user_id'    => $user->ID,
            'point_type' => $point_type,
            'amount'     => $amount,
        ),
        true
    );
}

function adfoin_mycred_action_prepare_data( $parsed ) {
    if ( empty( $parsed['data_json'] ) ) {
        return '';
    }

    $decoded = adfoin_mycred_action_decode_json( $parsed['data_json'] );

    if ( false === $decoded ) {
        return '';
    }

    return $decoded;
}

function adfoin_mycred_action_resolve_user( $parsed ) {
    if ( isset( $parsed['user_id'] ) && $parsed['user_id'] !== '' ) {
        $user = get_user_by( 'id', absint( $parsed['user_id'] ) );
        if ( $user ) {
            return $user;
        }
    }

    if ( isset( $parsed['user_email'] ) && $parsed['user_email'] !== '' ) {
        $email = sanitize_email( $parsed['user_email'] );
        if ( $email ) {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                return $user;
            }
        }
    }

    if ( isset( $parsed['user_login'] ) && $parsed['user_login'] !== '' ) {
        $login = sanitize_user( $parsed['user_login'], true );
        if ( $login ) {
            $user = get_user_by( 'login', $login );
            if ( $user ) {
                return $user;
            }
        }
    }

    return false;
}

function adfoin_mycred_action_user_debug_payload( $parsed ) {
    return array(
        'user_id'    => $parsed['user_id'] ?? '',
        'user_email' => $parsed['user_email'] ?? '',
        'user_login' => $parsed['user_login'] ?? '',
    );
}

function adfoin_mycred_action_decode_json( $value ) {
    if ( '' === trim( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        return false;
    }

    return $decoded;
}

function adfoin_mycred_action_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'mycred', $log_args, $record );
}

