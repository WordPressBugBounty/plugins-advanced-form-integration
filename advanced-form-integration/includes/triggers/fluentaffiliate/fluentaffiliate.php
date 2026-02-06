<?php

// Get FluentAffiliate triggers.
function adfoin_fluentaffiliate_get_forms( $form_provider ) {
    if ( $form_provider !== 'fluentaffiliate' ) {
        return;
    }

    return array(
        'affiliateCreated'      => __( 'Affiliate Created', 'advanced-form-integration' ),
        'affiliateUpdated'      => __( 'Affiliate Updated', 'advanced-form-integration' ),
        'affiliateStatusChange' => __( 'Affiliate Status Changed', 'advanced-form-integration' ),
        'referralCreated'       => __( 'Referral Created', 'advanced-form-integration' ),
    );
}

// Get FluentAffiliate fields.
function adfoin_fluentaffiliate_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'fluentaffiliate' ) {
        return;
    }

    $affiliate_fields = array(
        'affiliate_id'        => __( 'Affiliate ID', 'advanced-form-integration' ),
        'user_id'             => __( 'User ID', 'advanced-form-integration' ),
        'group_id'            => __( 'Group ID', 'advanced-form-integration' ),
        'group_key'           => __( 'Group Key', 'advanced-form-integration' ),
        'group_name'          => __( 'Group Name', 'advanced-form-integration' ),
        'group_data'          => __( 'Group Data (JSON)', 'advanced-form-integration' ),
        'status'              => __( 'Status', 'advanced-form-integration' ),
        'custom_param'        => __( 'Custom Parameter', 'advanced-form-integration' ),
        'rate'                => __( 'Commission Rate', 'advanced-form-integration' ),
        'rate_type'           => __( 'Rate Type', 'advanced-form-integration' ),
        'total_earnings'      => __( 'Total Earnings', 'advanced-form-integration' ),
        'unpaid_earnings'     => __( 'Unpaid Earnings', 'advanced-form-integration' ),
        'referrals'           => __( 'Referral Count', 'advanced-form-integration' ),
        'visits'              => __( 'Visit Count', 'advanced-form-integration' ),
        'payment_email'       => __( 'Payment Email', 'advanced-form-integration' ),
        'note'                => __( 'Affiliate Note', 'advanced-form-integration' ),
        'settings'            => __( 'Affiliate Settings (JSON)', 'advanced-form-integration' ),
        'user_email'          => __( 'User Email', 'advanced-form-integration' ),
        'user_login'          => __( 'User Login', 'advanced-form-integration' ),
        'user_display_name'   => __( 'User Display Name', 'advanced-form-integration' ),
        'user_first_name'     => __( 'User First Name', 'advanced-form-integration' ),
        'user_last_name'      => __( 'User Last Name', 'advanced-form-integration' ),
        'user_roles'          => __( 'User Roles (JSON)', 'advanced-form-integration' ),
        'user_url'            => __( 'User Website', 'advanced-form-integration' ),
        'user_avatar'         => __( 'User Avatar URL', 'advanced-form-integration' ),
        'portal_url'          => __( 'Portal URL', 'advanced-form-integration' ),
        'admin_url'           => __( 'Admin URL', 'advanced-form-integration' ),
        'created_at'          => __( 'Created At', 'advanced-form-integration' ),
        'updated_at'          => __( 'Updated At', 'advanced-form-integration' ),
    );

    if ( 'affiliateCreated' === $form_id ) {
        return $affiliate_fields;
    }

    if ( 'affiliateUpdated' === $form_id ) {
        return array_merge(
            $affiliate_fields,
            array(
                'updated_by'   => __( 'Updated By', 'advanced-form-integration' ),
                'updated_data' => __( 'Updated Fields (JSON)', 'advanced-form-integration' ),
            )
        );
    }

    if ( 'affiliateStatusChange' === $form_id ) {
        return array_merge(
            $affiliate_fields,
            array(
                'old_status' => __( 'Old Status', 'advanced-form-integration' ),
                'new_status' => __( 'New Status', 'advanced-form-integration' ),
            )
        );
    }

    if ( 'referralCreated' === $form_id ) {
        return array_merge(
            $affiliate_fields,
            array(
                'referral_id'        => __( 'Referral ID', 'advanced-form-integration' ),
                'referral_parent_id' => __( 'Parent Referral ID', 'advanced-form-integration' ),
                'referral_customer_id' => __( 'Customer ID', 'advanced-form-integration' ),
                'referral_visit_id'  => __( 'Visit ID', 'advanced-form-integration' ),
                'referral_description' => __( 'Referral Description', 'advanced-form-integration' ),
                'referral_amount'    => __( 'Referral Amount', 'advanced-form-integration' ),
                'referral_order_total' => __( 'Order Total', 'advanced-form-integration' ),
                'referral_currency'  => __( 'Currency', 'advanced-form-integration' ),
                'referral_status'    => __( 'Referral Status', 'advanced-form-integration' ),
                'referral_type'      => __( 'Referral Type', 'advanced-form-integration' ),
                'referral_provider'  => __( 'Provider', 'advanced-form-integration' ),
                'referral_provider_id' => __( 'Provider ID', 'advanced-form-integration' ),
                'referral_provider_sub_id' => __( 'Provider Sub ID', 'advanced-form-integration' ),
                'referral_products'  => __( 'Products (JSON)', 'advanced-form-integration' ),
                'referral_settings'  => __( 'Referral Settings (JSON)', 'advanced-form-integration' ),
                'referral_utm_campaign' => __( 'Referral UTM Campaign', 'advanced-form-integration' ),
                'referral_provider_url' => __( 'Referral Provider URL', 'advanced-form-integration' ),
                'referral_admin_url' => __( 'Referral Admin URL', 'advanced-form-integration' ),
                'referral_created_at' => __( 'Referral Created At', 'advanced-form-integration' ),
                'referral_updated_at' => __( 'Referral Updated At', 'advanced-form-integration' ),
                'customer_email'     => __( 'Customer Email', 'advanced-form-integration' ),
                'customer_first_name'=> __( 'Customer First Name', 'advanced-form-integration' ),
                'customer_last_name' => __( 'Customer Last Name', 'advanced-form-integration' ),
                'customer_full_name' => __( 'Customer Full Name', 'advanced-form-integration' ),
                'customer_ip'        => __( 'Customer IP', 'advanced-form-integration' ),
                'visit_url'          => __( 'Visit URL', 'advanced-form-integration' ),
                'visit_referrer'     => __( 'Visit Referrer', 'advanced-form-integration' ),
                'visit_utm_campaign' => __( 'Visit UTM Campaign', 'advanced-form-integration' ),
                'visit_utm_source'   => __( 'Visit UTM Source', 'advanced-form-integration' ),
                'visit_utm_medium'   => __( 'Visit UTM Medium', 'advanced-form-integration' ),
            )
        );
    }

    return $affiliate_fields;
}

/**
 * Resolve Affiliate model.
 *
 * @param mixed $affiliate Affiliate data.
 *
 * @return \FluentAffiliate\App\Models\Affiliate|null
 */
function adfoin_fluentaffiliate_resolve_affiliate( $affiliate ) {
    if ( empty( $affiliate ) || ! class_exists( '\FluentAffiliate\App\Models\Affiliate' ) ) {
        return null;
    }

    if ( $affiliate instanceof \FluentAffiliate\App\Models\Affiliate ) {
        $affiliate->loadMissing( array( 'user', 'group' ) );
        return $affiliate;
    }

    if ( is_numeric( $affiliate ) ) {
        return \FluentAffiliate\App\Models\Affiliate::with( array( 'user', 'group' ) )->find( (int) $affiliate );
    }

    if ( is_array( $affiliate ) && isset( $affiliate['id'] ) ) {
        return \FluentAffiliate\App\Models\Affiliate::with( array( 'user', 'group' ) )->find( (int) $affiliate['id'] );
    }

    return null;
}

/**
 * Prepare affiliate payload.
 *
 * @param mixed $affiliate Affiliate data.
 *
 * @return array
 */
function adfoin_fluentaffiliate_prepare_affiliate_payload( $affiliate ) {
    $affiliate_model = adfoin_fluentaffiliate_resolve_affiliate( $affiliate );

    if ( ! $affiliate_model ) {
        return array();
    }

    $payload = array(
        'affiliate_id'    => $affiliate_model->id,
        'user_id'         => $affiliate_model->user_id,
        'group_id'        => $affiliate_model->group_id,
        'status'          => $affiliate_model->status,
        'custom_param'    => $affiliate_model->custom_param,
        'total_earnings'  => $affiliate_model->total_earnings,
        'unpaid_earnings' => $affiliate_model->unpaid_earnings,
        'referrals'       => $affiliate_model->referrals,
        'visits'          => $affiliate_model->visits,
        'rate'            => $affiliate_model->rate,
        'rate_type'       => $affiliate_model->rate_type,
        'payment_email'   => $affiliate_model->payment_email,
        'note'            => $affiliate_model->note,
        'created_at'      => $affiliate_model->created_at,
        'updated_at'      => $affiliate_model->updated_at,
    );

    if ( isset( $affiliate_model->settings ) && ! empty( $affiliate_model->settings ) ) {
        $payload['settings'] = wp_json_encode( $affiliate_model->settings );
    }

    if ( $affiliate_model->group ) {
        $group_value = $affiliate_model->group->value;
        $payload['group_key']  = $affiliate_model->group->meta_key ?? '';
        $payload['group_name'] = is_array( $group_value ) ? ( $group_value['name'] ?? '' ) : '';
        $payload['group_data'] = is_array( $group_value ) ? wp_json_encode( $group_value ) : ( $group_value ?: '' );
    }

    $user = null;
    if ( $affiliate_model->user ) {
        $user = $affiliate_model->user;
    } elseif ( ! empty( $affiliate_model->user_id ) ) {
        $user = get_userdata( $affiliate_model->user_id );
    }

    if ( $user ) {
        if ( is_object( $user ) && isset( $user->user_email ) ) {
            $payload['user_email']        = $user->user_email;
            $payload['user_login']        = $user->user_login ?? '';
            $payload['user_display_name'] = $user->display_name ?? '';
            $payload['user_first_name']   = get_user_meta( $user->ID, 'first_name', true );
            $payload['user_last_name']    = get_user_meta( $user->ID, 'last_name', true );
            $payload['user_url']          = $user->user_url ?? '';
            $payload['user_roles']        = ! empty( $user->roles ) ? wp_json_encode( $user->roles ) : '';
            $payload['user_avatar']       = get_avatar_url( $user->ID );
        } elseif ( isset( $user->user_email ) ) { // Fluent Affiliate user model
            $payload['user_email']        = $user->user_email;
            $payload['user_login']        = $user->user_login ?? '';
            $payload['user_display_name'] = $user->display_name ?? '';
            $payload['user_first_name']   = get_user_meta( $user->ID ?? 0, 'first_name', true );
            $payload['user_last_name']    = get_user_meta( $user->ID ?? 0, 'last_name', true );
            $payload['user_url']          = $user->user_url ?? '';
            $wp_user                      = get_user_by( 'ID', $user->ID ?? 0 );
            if ( $wp_user ) {
                $payload['user_roles'] = ! empty( $wp_user->roles ) ? wp_json_encode( $wp_user->roles ) : '';
                $payload['user_avatar'] = get_avatar_url( $wp_user->ID );
            }
        }
    }

    if ( class_exists( '\FluentAffiliate\App\Helper\Utility' ) ) {
        $payload['portal_url'] = \FluentAffiliate\App\Helper\Utility::getPortalPageUrl();
        $payload['admin_url']  = \FluentAffiliate\App\Helper\Utility::getAdminPageUrl( 'affiliates/' . $affiliate_model->id . '/view' );
    } else {
        $payload['portal_url'] = home_url();
        $payload['admin_url']  = admin_url( 'admin.php?page=fluent-affiliate#/affiliates/' . $affiliate_model->id . '/view' );
    }

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Format referral payload.
 *
 * @param mixed $referral Referral data.
 *
 * @return array
 */
function adfoin_fluentaffiliate_prepare_referral_payload( $referral ) {
    if ( empty( $referral ) || ! class_exists( '\FluentAffiliate\App\Models\Referral' ) ) {
        return array();
    }

    if ( $referral instanceof \FluentAffiliate\App\Models\Referral ) {
        $referral_model = $referral;
    } elseif ( is_numeric( $referral ) ) {
        $referral_model = \FluentAffiliate\App\Models\Referral::find( (int) $referral );
    } elseif ( is_array( $referral ) && isset( $referral['id'] ) ) {
        $referral_model = \FluentAffiliate\App\Models\Referral::find( (int) $referral['id'] );
    } else {
        $referral_model = null;
    }

    if ( ! $referral_model ) {
        return array();
    }

    $referral_model->loadMissing( array( 'affiliate', 'customer', 'visit' ) );

    $payload = array(
        'referral_id'         => $referral_model->id,
        'affiliate_id'        => $referral_model->affiliate_id,
        'referral_parent_id'  => $referral_model->parent_id,
        'referral_customer_id'=> $referral_model->customer_id,
        'referral_visit_id'   => $referral_model->visit_id,
        'referral_description'=> $referral_model->description,
        'referral_amount'     => $referral_model->amount,
        'referral_order_total'=> $referral_model->order_total,
        'referral_currency'   => $referral_model->currency,
        'referral_status'     => $referral_model->status,
        'referral_type'       => $referral_model->type,
        'referral_provider'   => $referral_model->provider,
        'referral_provider_id'=> $referral_model->provider_id,
        'referral_provider_sub_id' => $referral_model->provider_sub_id,
        'referral_utm_campaign'    => $referral_model->utm_campaign,
        'referral_created_at' => $referral_model->created_at,
        'referral_updated_at' => $referral_model->updated_at,
    );

    if ( ! empty( $referral_model->products ) ) {
        $payload['referral_products'] = is_array( $referral_model->products ) ? wp_json_encode( $referral_model->products ) : $referral_model->products;
    }

    if ( ! empty( $referral_model->settings ) ) {
        $payload['referral_settings'] = is_array( $referral_model->settings ) ? wp_json_encode( $referral_model->settings ) : $referral_model->settings;
    }

    if ( method_exists( $referral_model, 'getProviderReferenceUrl' ) ) {
        $payload['referral_provider_url'] = $referral_model->getProviderReferenceUrl();
    }

    if ( method_exists( $referral_model, 'getAdminViewUrl' ) ) {
        $payload['referral_admin_url'] = $referral_model->getAdminViewUrl();
    }

    if ( $referral_model->customer ) {
        $payload['customer_email']      = $referral_model->customer->email ?? '';
        $payload['customer_first_name'] = $referral_model->customer->first_name ?? '';
        $payload['customer_last_name']  = $referral_model->customer->last_name ?? '';
        $payload['customer_full_name']  = $referral_model->customer->full_name ?? '';
        $payload['customer_ip']         = $referral_model->customer->ip ?? '';
    }

    if ( $referral_model->visit ) {
        $payload['visit_url']          = $referral_model->visit->url ?? '';
        $payload['visit_referrer']     = $referral_model->visit->referrer ?? '';
        $payload['visit_utm_campaign'] = $referral_model->visit->utm_campaign ?? '';
        $payload['visit_utm_source']   = $referral_model->visit->utm_source ?? '';
        $payload['visit_utm_medium']   = $referral_model->visit->utm_medium ?? '';
    }

    return array_filter(
        $payload,
        function ( $value ) {
            return $value !== null && $value !== '';
        }
    );
}

/**
 * Dispatch data to FluentAffiliate integrations.
 *
 * @param string $trigger Trigger name.
 * @param array  $payload Payload.
 *
 * @return void
 */
function adfoin_fluentaffiliate_dispatch( $trigger, $payload ) {
    if ( empty( $payload ) ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();
    $records     = $integration->get_by_trigger( 'fluentaffiliate', $trigger );

    if ( empty( $records ) ) {
        return;
    }

    $integration->send( $records, $payload );
}

// Affiliate created.
add_action( 'fluent_affiliate/affiliate_created', 'adfoin_fluentaffiliate_handle_affiliate_created', 10, 1 );
function adfoin_fluentaffiliate_handle_affiliate_created( $affiliate ) {
    $payload = adfoin_fluentaffiliate_prepare_affiliate_payload( $affiliate );
    adfoin_fluentaffiliate_dispatch( 'affiliateCreated', $payload );
}

// Affiliate updated.
add_action( 'fluent_affiliate/affiliate_updated', 'adfoin_fluentaffiliate_handle_affiliate_updated', 10, 3 );
function adfoin_fluentaffiliate_handle_affiliate_updated( $affiliate, $updated_by = '', $updated_data = array() ) {
    $payload = adfoin_fluentaffiliate_prepare_affiliate_payload( $affiliate );

    if ( $updated_by ) {
        $payload['updated_by'] = $updated_by;
    }

    if ( ! empty( $updated_data ) ) {
        $payload['updated_data'] = wp_json_encode( $updated_data );
    }

    adfoin_fluentaffiliate_dispatch( 'affiliateUpdated', $payload );
}

// Affiliate status changes.
function adfoin_fluentaffiliate_register_status_hooks() {
    $statuses = array( 'active', 'pending', 'cancelled', 'rejected', 'inactive' );

    foreach ( $statuses as $status ) {
        add_action(
            'fluent_affiliate/affiliate_status_to_' . $status,
            function ( $affiliate, $old_status = '' ) use ( $status ) {
                $payload = adfoin_fluentaffiliate_prepare_affiliate_payload( $affiliate );

                $payload['new_status'] = $status;
                if ( $old_status ) {
                    $payload['old_status'] = $old_status;
                }

                adfoin_fluentaffiliate_dispatch( 'affiliateStatusChange', $payload );
            },
            10,
            2
        );
    }
}
adfoin_fluentaffiliate_register_status_hooks();

// Referral created (initially marked unpaid).
add_action( 'fluent_affiliate/referral_marked_unpaid', 'adfoin_fluentaffiliate_handle_referral_created', 10, 1 );
function adfoin_fluentaffiliate_handle_referral_created( $referral ) {
    $referral_payload = adfoin_fluentaffiliate_prepare_referral_payload( $referral );

    $affiliate_payload = array();

    if ( class_exists( '\FluentAffiliate\App\Models\Referral' ) && $referral instanceof \FluentAffiliate\App\Models\Referral ) {
        $referral->loadMissing( array( 'affiliate' ) );
        if ( $referral->affiliate ) {
            $affiliate_payload = adfoin_fluentaffiliate_prepare_affiliate_payload( $referral->affiliate );
        }
    } elseif ( is_array( $referral_payload ) && isset( $referral_payload['affiliate_id'] ) ) {
        $affiliate_payload = adfoin_fluentaffiliate_prepare_affiliate_payload( $referral_payload['affiliate_id'] );
    }

    $combined = array_merge( $affiliate_payload, $referral_payload );

    adfoin_fluentaffiliate_dispatch( 'referralCreated', $combined );
}
