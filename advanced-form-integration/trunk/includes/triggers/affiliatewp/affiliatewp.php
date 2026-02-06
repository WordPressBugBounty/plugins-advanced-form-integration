<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve AffiliateWP triggers.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_affiliatewp_get_forms( $form_provider ) {
    if ( 'affiliatewp' !== $form_provider ) {
        return;
    }

    return array(
        'affiliation_approved'    => __( 'Affiliation Approved', 'advanced-form-integration' ),
        'user_becomes_affiliate'  => __( 'User Becomes Affiliate', 'advanced-form-integration' ),
        'affiliate_makes_referral'=> __( 'Affiliate Makes Referral', 'advanced-form-integration' ),
        'referral_rejected'       => __( 'Referral Rejected', 'advanced-form-integration' ),
        'referral_paid'           => __( 'Referral Paid', 'advanced-form-integration' ),
    );
}

/**
 * Retrieve mapped fields for AffiliateWP triggers.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_affiliatewp_get_form_fields( $form_provider, $form_id ) {
    if ( 'affiliatewp' !== $form_provider ) {
        return;
    }

    $fields = array();

    $affiliate_fields = array(
        'affiliate_id'      => __( 'Affiliate ID', 'advanced-form-integration' ),
        'affiliate_status'  => __( 'Affiliate Status', 'advanced-form-integration' ),
        'affiliate_old_status' => __( 'Affiliate Old Status', 'advanced-form-integration' ),
        'rate'              => __( 'Rate', 'advanced-form-integration' ),
        'rate_type'         => __( 'Rate Type', 'advanced-form-integration' ),
        'flat_rate_basis'   => __( 'Flat Rate Basis', 'advanced-form-integration' ),
        'payment_email'     => __( 'Payment Email', 'advanced-form-integration' ),
        'account_email'     => __( 'Account Email', 'advanced-form-integration' ),
        'affiliate_note'    => __( 'Affiliate Note', 'advanced-form-integration' ),
        'earnings'          => __( 'Earnings', 'advanced-form-integration' ),
        'unpaid_earnings'   => __( 'Unpaid Earnings', 'advanced-form-integration' ),
        'referral_count'    => __( 'Referral Count', 'advanced-form-integration' ),
        'visit_count'       => __( 'Visit Count', 'advanced-form-integration' ),
        'conversion_rate'   => __( 'Conversion Rate', 'advanced-form-integration' ),
        'user_id'           => __( 'User ID', 'advanced-form-integration' ),
        'first_name'        => __( 'First Name', 'advanced-form-integration' ),
        'last_name'         => __( 'Last Name', 'advanced-form-integration' ),
        'display_name'      => __( 'Display Name', 'advanced-form-integration' ),
        'user_email'        => __( 'User Email', 'advanced-form-integration' ),
        'user_avatar'       => __( 'User Avatar URL', 'advanced-form-integration' ),
        'user_roles'        => __( 'User Roles', 'advanced-form-integration' ),
    );

    $referral_fields = array(
        'referral_id'         => __( 'Referral ID', 'advanced-form-integration' ),
        'referral_status'     => __( 'Referral Status', 'advanced-form-integration' ),
        'referral_old_status' => __( 'Referral Old Status', 'advanced-form-integration' ),
        'amount'              => __( 'Amount', 'advanced-form-integration' ),
        'currency'            => __( 'Currency', 'advanced-form-integration' ),
        'description'         => __( 'Description', 'advanced-form-integration' ),
        'context'             => __( 'Context', 'advanced-form-integration' ),
        'reference'           => __( 'Reference', 'advanced-form-integration' ),
        'campaign'            => __( 'Campaign', 'advanced-form-integration' ),
        'products'            => __( 'Products', 'advanced-form-integration' ),
        'custom_data'         => __( 'Custom Data', 'advanced-form-integration' ),
        'visit_id'            => __( 'Visit ID', 'advanced-form-integration' ),
        'date'                => __( 'Date', 'advanced-form-integration' ),
        'parent_id'           => __( 'Parent Referral ID', 'advanced-form-integration' ),
        'type'                => __( 'Referral Type', 'advanced-form-integration' ),
        'reference_url'       => __( 'Reference URL', 'advanced-form-integration' ),
    );

    if ( in_array( $form_id, array( 'affiliation_approved', 'user_becomes_affiliate' ), true ) ) {
        $fields = $affiliate_fields;
    } elseif ( 'affiliate_makes_referral' === $form_id ) {
        $fields = array_merge( $affiliate_fields, $referral_fields );
    } elseif ( in_array( $form_id, array( 'referral_rejected', 'referral_paid' ), true ) ) {
        $fields = array_merge( $affiliate_fields, $referral_fields );
    }

    return $fields;
}

/**
 * Normalize value into a scalar string.
 *
 * @param mixed $value Value to normalize.
 *
 * @return string
 */
function adfoin_affiliatewp_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Gather user data for an affiliate.
 *
 * @param int $user_id User ID.
 *
 * @return array<string,string>
 */
function adfoin_affiliatewp_collect_user_data( $user_id ) {
    $data = array(
        'user_id'      => '',
        'first_name'   => '',
        'last_name'    => '',
        'display_name' => '',
        'user_email'   => '',
        'user_avatar'  => '',
        'user_roles'   => '',
    );

    if ( ! $user_id ) {
        return $data;
    }

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return $data;
    }

    $data['user_id']      = (string) $user_id;
    $data['first_name']   = adfoin_affiliatewp_normalize_value( $user->first_name );
    $data['last_name']    = adfoin_affiliatewp_normalize_value( $user->last_name );
    $data['display_name'] = adfoin_affiliatewp_normalize_value( $user->display_name );
    $data['user_email']   = adfoin_affiliatewp_normalize_value( $user->user_email );
    $data['user_avatar']  = get_avatar_url( $user_id );
    $data['user_roles']   = adfoin_affiliatewp_normalize_value( $user->roles );

    return $data;
}

/**
 * Prepare affiliate data payload.
 *
 * @param \AffWP\Affiliate|int $affiliate Affiliate object or ID.
 *
 * @return array<string,string>
 */
function adfoin_affiliatewp_prepare_affiliate_payload( $affiliate ) {
    if ( ! $affiliate instanceof \AffWP\Affiliate ) {
        $affiliate = affwp_get_affiliate( $affiliate );
    }

    if ( ! $affiliate ) {
        return array();
    }

    $user_data = adfoin_affiliatewp_collect_user_data( affwp_get_affiliate_user_id( $affiliate->affiliate_id ) );

    $payload = array(
        'affiliate_id'     => adfoin_affiliatewp_normalize_value( $affiliate->affiliate_id ),
        'affiliate_status' => adfoin_affiliatewp_normalize_value( $affiliate->status ),
        'rate'             => adfoin_affiliatewp_normalize_value( $affiliate->rate ),
        'rate_type'        => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_rate_type( $affiliate->affiliate_id ) ),
        'flat_rate_basis'  => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_flat_rate_basis( $affiliate->affiliate_id ) ),
        'payment_email'    => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_payment_email( $affiliate->affiliate_id ) ),
        'account_email'    => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_email( $affiliate->affiliate_id ) ),
        'affiliate_note'   => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_meta( $affiliate->affiliate_id, 'notes', true ) ),
        'earnings'         => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_earnings( $affiliate->affiliate_id ) ),
        'unpaid_earnings'  => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_unpaid_earnings( $affiliate->affiliate_id ) ),
        'referral_count'   => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_referral_count( $affiliate->affiliate_id ) ),
        'visit_count'      => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_visit_count( $affiliate->affiliate_id ) ),
        'conversion_rate'  => adfoin_affiliatewp_normalize_value( affwp_get_affiliate_conversion_rate( $affiliate->affiliate_id ) ),
    );

    $payload = array_merge( $payload, $user_data );

    return array_map( 'trim', $payload );
}

/**
 * Prepare referral payload.
 *
 * @param \AffWP\Referral|int $referral Referral object or ID.
 *
 * @return array<string,string>
 */
function adfoin_affiliatewp_prepare_referral_payload( $referral ) {
    if ( ! $referral instanceof \AffWP\Referral ) {
        $referral = affwp_get_referral( $referral );
    }

    if ( ! $referral ) {
        return array();
    }

    $payload = array(
        'referral_id'         => adfoin_affiliatewp_normalize_value( $referral->referral_id ),
        'referral_status'     => adfoin_affiliatewp_normalize_value( $referral->status ),
        'amount'              => adfoin_affiliatewp_normalize_value( $referral->amount ),
        'currency'            => adfoin_affiliatewp_normalize_value( $referral->currency ),
        'description'         => adfoin_affiliatewp_normalize_value( $referral->description ),
        'context'             => adfoin_affiliatewp_normalize_value( $referral->context ),
        'reference'           => adfoin_affiliatewp_normalize_value( $referral->reference ),
        'campaign'            => adfoin_affiliatewp_normalize_value( $referral->campaign ),
        'products'            => adfoin_affiliatewp_normalize_value( $referral->products ?? '' ),
        'custom_data'         => adfoin_affiliatewp_normalize_value( $referral->custom ),
        'visit_id'            => adfoin_affiliatewp_normalize_value( $referral->visit_id ),
        'date'                => adfoin_affiliatewp_normalize_value( $referral->date ),
        'parent_id'           => adfoin_affiliatewp_normalize_value( $referral->parent_id ),
        'type'                => adfoin_affiliatewp_normalize_value( $referral->type ),
        'reference_url'       => adfoin_affiliatewp_normalize_value(
            function_exists( 'affwp_get_affiliate_referral_url' )
                ? affwp_get_affiliate_referral_url( array( 'affiliate_id' => $referral->affiliate_id ) )
                : ''
        ),
    );

    // Ensure affiliate data is included.
    $affiliate_payload = adfoin_affiliatewp_prepare_affiliate_payload( $referral->affiliate_id );

    return array_map( 'trim', array_merge( $payload, $affiliate_payload ) );
}

/**
 * Dispatch payloads to saved records.
 *
 * @param array<int,array>    $saved_records Saved records.
 * @param array<string,mixed> $posted_data   Payload.
 *
 * @return void
 */
function adfoin_affiliatewp_send_payload( $saved_records, $posted_data ) {
    if ( empty( $saved_records ) || empty( $posted_data ) ) {
        return;
    }

    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];

        if ( $job_queue ) {
            as_enqueue_async_action(
                "adfoin_{$action_provider}_job_queue",
                array(
                    'data' => array(
                        'record'      => $record,
                        'posted_data' => $posted_data,
                    ),
                )
            );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
}

add_action( 'affwp_set_affiliate_status', 'adfoin_affiliatewp_handle_status_change', 10, 3 );

/**
 * Handle affiliate status changes.
 *
 * @param int    $affiliate_id Affiliate ID.
 * @param string $status       New status.
 * @param string $old_status   Previous status.
 *
 * @return void
 */
function adfoin_affiliatewp_handle_status_change( $affiliate_id, $status, $old_status ) {
    $integration = new Advanced_Form_Integration_Integration();

    $is_approval = 'active' === $status && 'active' !== $old_status;

    $triggers = array();

    if ( $is_approval ) {
        $triggers[] = 'affiliation_approved';
    }

    if ( $status !== $old_status ) {
        $triggers[] = 'user_becomes_affiliate';
    }

    if ( empty( $triggers ) ) {
        return;
    }

    $payload = adfoin_affiliatewp_prepare_affiliate_payload( $affiliate_id );

    if ( empty( $payload ) ) {
        return;
    }

    $payload['affiliate_old_status'] = adfoin_affiliatewp_normalize_value( $old_status );

    foreach ( $triggers as $trigger ) {
        $saved_records = $integration->get_by_trigger( 'affiliatewp', $trigger );

        if ( empty( $saved_records ) ) {
            continue;
        }

        adfoin_affiliatewp_send_payload( $saved_records, $payload );
    }
}

add_action( 'affwp_insert_referral', 'adfoin_affiliatewp_handle_new_referral', 20 );

/**
 * Handle referral creation.
 *
 * @param int $referral_id Referral ID.
 *
 * @return void
 */
function adfoin_affiliatewp_handle_new_referral( $referral_id ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'affiliatewp', 'affiliate_makes_referral' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = adfoin_affiliatewp_prepare_referral_payload( $referral_id );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_affiliatewp_send_payload( $saved_records, $payload );
}

add_action( 'affwp_set_referral_status', 'adfoin_affiliatewp_handle_referral_status_change', 99, 3 );

/**
 * Handle referral status updates.
 *
 * @param int    $referral_id Referral ID.
 * @param string $status      New status.
 * @param string $old_status  Previous status.
 *
 * @return void
 */
function adfoin_affiliatewp_handle_referral_status_change( $referral_id, $status, $old_status ) {
    if ( $status === $old_status ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $payload     = adfoin_affiliatewp_prepare_referral_payload( $referral_id );

    if ( empty( $payload ) ) {
        return;
    }

    $payload['referral_status']     = adfoin_affiliatewp_normalize_value( $status );
    $payload['referral_old_status'] = adfoin_affiliatewp_normalize_value( $old_status );

    if ( 'rejected' === $status ) {
        $records = $integration->get_by_trigger( 'affiliatewp', 'referral_rejected' );
        adfoin_affiliatewp_send_payload( $records, $payload );
    } elseif ( 'paid' === $status ) {
        $records = $integration->get_by_trigger( 'affiliatewp', 'referral_paid' );
        adfoin_affiliatewp_send_payload( $records, $payload );
    }
}
