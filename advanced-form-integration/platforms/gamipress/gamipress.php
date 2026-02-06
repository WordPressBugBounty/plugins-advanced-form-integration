<?php

add_filter( 'adfoin_action_providers', 'adfoin_gamipress_actions', 10, 1 );

function adfoin_gamipress_actions( $actions ) {
    $actions['gamipress'] = array(
        'title' => __( 'GamiPress', 'advanced-form-integration' ),
        'tasks' => array(
            'award_points'       => __( 'Award Points', 'advanced-form-integration' ),
            'deduct_points'      => __( 'Deduct Points', 'advanced-form-integration' ),
            'award_achievement'  => __( 'Award Achievement', 'advanced-form-integration' ),
            'revoke_achievement' => __( 'Revoke Achievement', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_gamipress_action_fields' );

function adfoin_gamipress_action_fields() {
    ?>
    <script type="text/template" id="gamipress-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'award_points'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide a user identifier (ID, email, or login), the points amount, and the points type slug. Optional reason, log type, and admin context will be stored with the award.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'deduct_points'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'A user identifier, points amount, and points type slug are required. The amount should be positive; GamiPress handles the deduction internally.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'award_achievement'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Pass a user along with the achievement, step, or rank post ID. Optional trigger, site ID, and JSON args allow you to mirror native award calls.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'revoke_achievement'">
                <th scope="row"><?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the user and achievement (or rank) ID to revoke. Optionally pass a specific earning ID when more than one exists.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_gamipress_job_queue', 'adfoin_gamipress_job_queue', 10, 1 );

function adfoin_gamipress_job_queue( $data ) {
    adfoin_gamipress_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_gamipress_send_data( $record, $posted_data ) {
    if ( ! function_exists( 'gamipress_award_points_to_user' ) && ! function_exists( 'gamipress_award_achievement_to_user' ) ) {
        adfoin_gamipress_log( $record, __( 'GamiPress is not active.', 'advanced-form-integration' ), array(), false );
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

    if ( 'award_points' === $task ) {
        adfoin_gamipress_action_award_points( $record, $parsed );
    } elseif ( 'deduct_points' === $task ) {
        adfoin_gamipress_action_deduct_points( $record, $parsed );
    } elseif ( 'award_achievement' === $task ) {
        adfoin_gamipress_action_award_achievement( $record, $parsed );
    } elseif ( 'revoke_achievement' === $task ) {
        adfoin_gamipress_action_revoke_achievement( $record, $parsed );
    }
}

function adfoin_gamipress_action_award_points( $record, $parsed ) {
    if ( ! function_exists( 'gamipress_award_points_to_user' ) ) {
        adfoin_gamipress_log( $record, __( 'Award points function unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $user = adfoin_gamipress_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_gamipress_log(
            $record,
            __( 'User could not be located for awarding points.', 'advanced-form-integration' ),
            adfoin_gamipress_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $points_type = isset( $parsed['points_type'] ) ? sanitize_key( $parsed['points_type'] ) : '';
    if ( '' === $points_type ) {
        adfoin_gamipress_log(
            $record,
            __( 'Points type is required.', 'advanced-form-integration' ),
            array(),
            false
        );
        return;
    }

    $points = isset( $parsed['points'] ) ? floatval( $parsed['points'] ) : 0;
    if ( 0 === $points ) {
        adfoin_gamipress_log(
            $record,
            __( 'Points amount must be a non-zero number.', 'advanced-form-integration' ),
            array( 'points' => $parsed['points'] ?? '' ),
            false
        );
        return;
    }

    $args = adfoin_gamipress_action_prepare_points_args( $parsed );

    $result = gamipress_award_points_to_user( $user->ID, $points, $points_type, $args );

    if ( is_wp_error( $result ) ) {
        adfoin_gamipress_log(
            $record,
            sprintf(
                /* translators: %s error message */
                __( 'Failed to award points: %s', 'advanced-form-integration' ),
                $result->get_error_message()
            ),
            array(
                'user_id'     => $user->ID,
                'points'      => $points,
                'points_type' => $points_type,
                'args'        => $args,
            ),
            false
        );
        return;
    }

    $payload = array(
        'user_id'     => $user->ID,
        'points'      => $points,
        'points_type' => $points_type,
        'args'        => $args,
        'result'      => $result,
    );

    adfoin_gamipress_log(
        $record,
        __( 'Points awarded successfully.', 'advanced-form-integration' ),
        $payload,
        true
    );
}

function adfoin_gamipress_action_deduct_points( $record, $parsed ) {
    if ( ! function_exists( 'gamipress_deduct_points_to_user' ) ) {
        adfoin_gamipress_log( $record, __( 'Deduct points function unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $user = adfoin_gamipress_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_gamipress_log(
            $record,
            __( 'User could not be located for deducting points.', 'advanced-form-integration' ),
            adfoin_gamipress_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $points_type = isset( $parsed['points_type'] ) ? sanitize_key( $parsed['points_type'] ) : '';
    if ( '' === $points_type ) {
        adfoin_gamipress_log(
            $record,
            __( 'Points type is required.', 'advanced-form-integration' ),
            array(),
            false
        );
        return;
    }

    $points = isset( $parsed['points'] ) ? floatval( $parsed['points'] ) : 0;
    if ( $points <= 0 ) {
        adfoin_gamipress_log(
            $record,
            __( 'Points amount must be greater than zero to deduct.', 'advanced-form-integration' ),
            array( 'points' => $parsed['points'] ?? '' ),
            false
        );
        return;
    }

    $args = adfoin_gamipress_action_prepare_points_args( $parsed );

    $result = gamipress_deduct_points_to_user( $user->ID, $points, $points_type, $args );

    if ( is_wp_error( $result ) ) {
        adfoin_gamipress_log(
            $record,
            sprintf(
                /* translators: %s error message */
                __( 'Failed to deduct points: %s', 'advanced-form-integration' ),
                $result->get_error_message()
            ),
            array(
                'user_id'     => $user->ID,
                'points'      => $points,
                'points_type' => $points_type,
                'args'        => $args,
            ),
            false
        );
        return;
    }

    $payload = array(
        'user_id'     => $user->ID,
        'points'      => $points,
        'points_type' => $points_type,
        'args'        => $args,
        'result'      => $result,
    );

    adfoin_gamipress_log(
        $record,
        __( 'Points deducted successfully.', 'advanced-form-integration' ),
        $payload,
        true
    );
}

function adfoin_gamipress_action_award_achievement( $record, $parsed ) {
    if ( ! function_exists( 'gamipress_award_achievement_to_user' ) ) {
        adfoin_gamipress_log( $record, __( 'Award achievement function unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $user = adfoin_gamipress_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_gamipress_log(
            $record,
            __( 'User could not be located for awarding achievement.', 'advanced-form-integration' ),
            adfoin_gamipress_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $achievement_id = isset( $parsed['achievement_id'] ) ? absint( $parsed['achievement_id'] ) : 0;
    if ( ! $achievement_id || ! get_post( $achievement_id ) ) {
        adfoin_gamipress_log(
            $record,
            __( 'A valid achievement (or rank) ID is required.', 'advanced-form-integration' ),
            array( 'achievement_id' => $parsed['achievement_id'] ?? '' ),
            false
        );
        return;
    }

    $admin_id = isset( $parsed['admin_id'] ) ? absint( $parsed['admin_id'] ) : 0;
    $trigger  = isset( $parsed['trigger'] ) ? sanitize_text_field( $parsed['trigger'] ) : '';
    $site_id  = isset( $parsed['site_id'] ) ? absint( $parsed['site_id'] ) : 0;
    $args     = array();

    if ( isset( $parsed['args_json'] ) && '' !== trim( $parsed['args_json'] ) ) {
        $args = adfoin_gamipress_action_decode_json( $parsed['args_json'] );
    }

    $result = gamipress_award_achievement_to_user( $achievement_id, $user->ID, $admin_id, $trigger, $site_id, $args );

    if ( false === $result ) {
        adfoin_gamipress_log(
            $record,
        __( 'The supplied post is not an achievement, step, or rank.', 'advanced-form-integration' ),
            array( 'achievement_id' => $achievement_id ),
            false
        );
        return;
    }

    $payload = array(
        'user_id'        => $user->ID,
        'achievement_id' => $achievement_id,
        'admin_id'       => $admin_id,
        'trigger'        => $trigger,
        'site_id'        => $site_id,
        'args'           => $args,
    );

    adfoin_gamipress_log(
        $record,
        __( 'Achievement awarded successfully.', 'advanced-form-integration' ),
        $payload,
        true
    );
}

function adfoin_gamipress_action_revoke_achievement( $record, $parsed ) {
    if ( ! function_exists( 'gamipress_revoke_achievement_to_user' ) ) {
        adfoin_gamipress_log( $record, __( 'Revoke achievement function unavailable.', 'advanced-form-integration' ), array(), false );
        return;
    }

    $user = adfoin_gamipress_action_resolve_user( $parsed );
    if ( ! $user ) {
        adfoin_gamipress_log(
            $record,
            __( 'User could not be located for revoking achievement.', 'advanced-form-integration' ),
            adfoin_gamipress_action_user_debug_payload( $parsed ),
            false
        );
        return;
    }

    $achievement_id = isset( $parsed['achievement_id'] ) ? absint( $parsed['achievement_id'] ) : 0;
    if ( ! $achievement_id || ! get_post( $achievement_id ) ) {
        adfoin_gamipress_log(
            $record,
            __( 'A valid achievement (or rank) ID is required.', 'advanced-form-integration' ),
            array( 'achievement_id' => $parsed['achievement_id'] ?? '' ),
            false
        );
        return;
    }

    $earning_id = isset( $parsed['earning_id'] ) ? absint( $parsed['earning_id'] ) : 0;

    gamipress_revoke_achievement_to_user( $achievement_id, $user->ID, $earning_id );

    $payload = array(
        'user_id'        => $user->ID,
        'achievement_id' => $achievement_id,
        'earning_id'     => $earning_id,
    );

    adfoin_gamipress_log(
        $record,
        __( 'Achievement revoked successfully.', 'advanced-form-integration' ),
        $payload,
        true
    );
}

function adfoin_gamipress_action_prepare_points_args( $parsed ) {
    $args = array();

    $admin_id = isset( $parsed['admin_id'] ) ? absint( $parsed['admin_id'] ) : 0;
    if ( $admin_id ) {
        $args['admin_id'] = $admin_id;
    }

    $achievement_id = isset( $parsed['achievement_id'] ) ? absint( $parsed['achievement_id'] ) : 0;
    if ( $achievement_id ) {
        $args['achievement_id'] = $achievement_id;
    }

    if ( isset( $parsed['reason'] ) && '' !== trim( $parsed['reason'] ) ) {
        $args['reason'] = sanitize_text_field( $parsed['reason'] );
    }

    if ( isset( $parsed['log_type'] ) && '' !== trim( $parsed['log_type'] ) ) {
        $args['log_type'] = sanitize_key( $parsed['log_type'] );
    }

    return $args;
}

function adfoin_gamipress_action_resolve_user( $parsed ) {
    $user_id = isset( $parsed['user_id'] ) ? absint( $parsed['user_id'] ) : 0;
    if ( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        if ( $user ) {
            return $user;
        }
    }

    if ( ! empty( $parsed['user_email'] ) ) {
        $email = sanitize_email( $parsed['user_email'] );
        if ( $email ) {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                return $user;
            }
        }
    }

    if ( ! empty( $parsed['user_login'] ) ) {
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

function adfoin_gamipress_action_user_debug_payload( $parsed ) {
    return array(
        'user_id'    => isset( $parsed['user_id'] ) ? $parsed['user_id'] : '',
        'user_email' => isset( $parsed['user_email'] ) ? $parsed['user_email'] : '',
        'user_login' => isset( $parsed['user_login'] ) ? $parsed['user_login'] : '',
    );
}

function adfoin_gamipress_action_decode_json( $value ) {
    if ( '' === trim( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
        return $decoded;
    }

    return array();
}

function adfoin_gamipress_log( $record, $message, $payload, $success ) {
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

    adfoin_add_to_log( $log_response, 'gamipress', $log_args, $record );
}
