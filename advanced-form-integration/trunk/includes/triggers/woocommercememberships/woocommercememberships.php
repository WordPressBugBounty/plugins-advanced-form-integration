<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter( 'adfoin_form_providers', 'adfoin_woocommercememberships_filter_provider' );

/**
 * Register WooCommerce Memberships provider when the plugin is active.
 *
 * @param array $providers Registered providers.
 *
 * @return array
 */
function adfoin_woocommercememberships_filter_provider( $providers ) {
	if ( ! is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) ) {
		unset( $providers['woocommercememberships'] );

		return $providers;
	}

	$providers['woocommercememberships'] = __( 'WooCommerce Memberships', 'advanced-form-integration' );

	return $providers;
}

/**
 * Return available triggers.
 *
 * @return array<string,string>
 */
function adfoin_woocommercememberships_triggers() {
	return array(
		'membershipCreated'          => __( 'Membership Created', 'advanced-form-integration' ),
		'membershipSaved'            => __( 'Membership Saved', 'advanced-form-integration' ),
		'membershipDeleted'          => __( 'Membership Deleted', 'advanced-form-integration' ),
		'membershipTransferred'      => __( 'Membership Transferred', 'advanced-form-integration' ),
		'membershipStatusChanged'    => __( 'Membership Status Changed', 'advanced-form-integration' ),
		'membershipStatusActive'     => __( 'Membership Status Active', 'advanced-form-integration' ),
		'membershipStatusDelayed'    => __( 'Membership Status Delayed', 'advanced-form-integration' ),
		'membershipStatusComplimentary' => __( 'Membership Status Complimentary', 'advanced-form-integration' ),
		'membershipStatusPending'    => __( 'Membership Status Pending Cancellation', 'advanced-form-integration' ),
		'membershipStatusPaused'     => __( 'Membership Status Paused', 'advanced-form-integration' ),
		'membershipStatusExpired'    => __( 'Membership Status Expired', 'advanced-form-integration' ),
		'membershipStatusCancelled'  => __( 'Membership Status Cancelled', 'advanced-form-integration' ),
	);
}

/**
 * List triggers for provider selection.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommercememberships_get_forms( $form_provider ) {
	if ( 'woocommercememberships' !== $form_provider ) {
		return;
	}

	return adfoin_woocommercememberships_triggers();
}

/**
 * List available fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return array<string,string>|void
 */
function adfoin_woocommercememberships_get_form_fields( $form_provider, $form_id ) {
	if ( 'woocommercememberships' !== $form_provider ) {
		return;
	}

	return adfoin_get_woocommercememberships_fields();
}

/**
 * Resolve trigger label.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger key.
 *
 * @return string|false
 */
function adfoin_woocommercememberships_get_form_name( $form_provider, $form_id ) {
	if ( 'woocommercememberships' !== $form_provider ) {
		return false;
	}

	$triggers = adfoin_woocommercememberships_triggers();

	return isset( $triggers[ $form_id ] ) ? $triggers[ $form_id ] : false;
}

/**
 * Field definitions.
 *
 * @return array<string,string>
 */
function adfoin_get_woocommercememberships_fields() {
	return array(
		'membership_trigger'                     => __( 'Trigger Name', 'advanced-form-integration' ),
		'membership_form_id'                     => __( 'Trigger Key', 'advanced-form-integration' ),
		'membership_triggered_at'                => __( 'Trigger Time', 'advanced-form-integration' ),
		'membership_id'                          => __( 'Membership ID', 'advanced-form-integration' ),
		'membership_user_id'                     => __( 'Member User ID', 'advanced-form-integration' ),
		'membership_user_email'                  => __( 'Member Email', 'advanced-form-integration' ),
		'membership_user_first_name'             => __( 'Member First Name', 'advanced-form-integration' ),
		'membership_user_last_name'              => __( 'Member Last Name', 'advanced-form-integration' ),
		'membership_user_display_name'           => __( 'Member Display Name', 'advanced-form-integration' ),
		'membership_user_roles'                  => __( 'Member Roles', 'advanced-form-integration' ),
		'membership_view_url'                    => __( 'Membership View URL', 'advanced-form-integration' ),
		'membership_cancel_url'                  => __( 'Membership Cancel URL', 'advanced-form-integration' ),
		'membership_renew_url'                   => __( 'Membership Renew URL', 'advanced-form-integration' ),
        'membership_transfer_new_user_id'        => __( 'Transferred To: User ID', 'advanced-form-integration' ),
        'membership_transfer_new_user_email'     => __( 'Transferred To: Email', 'advanced-form-integration' ),
        'membership_transfer_new_user_display'   => __( 'Transferred To: Display Name', 'advanced-form-integration' ),
        'membership_transfer_previous_user_id'   => __( 'Transferred From: User ID', 'advanced-form-integration' ),
        'membership_transfer_previous_user_email'=> __( 'Transferred From: Email', 'advanced-form-integration' ),
        'membership_transfer_previous_user_display' => __( 'Transferred From: Display Name', 'advanced-form-integration' ),
		'membership_status'                      => __( 'Membership Status', 'advanced-form-integration' ),
		'membership_status_prefixed'             => __( 'Membership Status (Prefixed)', 'advanced-form-integration' ),
		'membership_status_label'                => __( 'Membership Status Label', 'advanced-form-integration' ),
		'membership_previous_status'             => __( 'Previous Status', 'advanced-form-integration' ),
		'membership_previous_status_label'       => __( 'Previous Status Label', 'advanced-form-integration' ),
		'membership_new_status'                  => __( 'New Status', 'advanced-form-integration' ),
		'membership_new_status_label'            => __( 'New Status Label', 'advanced-form-integration' ),
		'membership_is_active'                   => __( 'Is Active', 'advanced-form-integration' ),
		'membership_is_update'                   => __( 'Is Update', 'advanced-form-integration' ),
		'membership_deleted_flag'                => __( 'Is Deleted', 'advanced-form-integration' ),
		'membership_start_date'                  => __( 'Start Date (UTC)', 'advanced-form-integration' ),
		'membership_start_date_local'            => __( 'Start Date (Local)', 'advanced-form-integration' ),
		'membership_end_date'                    => __( 'End Date (UTC)', 'advanced-form-integration' ),
		'membership_end_date_local'              => __( 'End Date (Local)', 'advanced-form-integration' ),
		'membership_cancelled_date'              => __( 'Cancelled Date (UTC)', 'advanced-form-integration' ),
		'membership_cancelled_date_local'        => __( 'Cancelled Date (Local)', 'advanced-form-integration' ),
		'membership_paused_date'                 => __( 'Paused Date (UTC)', 'advanced-form-integration' ),
		'membership_paused_date_local'           => __( 'Paused Date (Local)', 'advanced-form-integration' ),
		'membership_total_active_time'           => __( 'Total Active Time (Seconds)', 'advanced-form-integration' ),
		'membership_total_inactive_time'         => __( 'Total Inactive Time (Seconds)', 'advanced-form-integration' ),
		'membership_last_active_date'            => __( 'Last Active Date', 'advanced-form-integration' ),
		'membership_last_active_since'           => __( 'Last Active Since', 'advanced-form-integration' ),
		'membership_type'                        => __( 'Membership Type', 'advanced-form-integration' ),
		'membership_previous_owners'             => __( 'Previous Owners (JSON)', 'advanced-form-integration' ),
		'membership_renewal_login_token'         => __( 'Renewal Login Token', 'advanced-form-integration' ),
		'membership_plan_id'                     => __( 'Plan ID', 'advanced-form-integration' ),
		'membership_plan_name'                   => __( 'Plan Name', 'advanced-form-integration' ),
		'membership_plan_slug'                   => __( 'Plan Slug', 'advanced-form-integration' ),
		'membership_plan_access_method'          => __( 'Plan Access Method', 'advanced-form-integration' ),
		'membership_plan_access_length'          => __( 'Plan Access Length', 'advanced-form-integration' ),
		'membership_plan_access_length_amount'   => __( 'Plan Access Length Amount', 'advanced-form-integration' ),
		'membership_plan_access_length_period'   => __( 'Plan Access Length Period', 'advanced-form-integration' ),
		'membership_plan_access_length_type'     => __( 'Plan Access Length Type', 'advanced-form-integration' ),
		'membership_plan_access_length_human'    => __( 'Plan Access Length (Human)', 'advanced-form-integration' ),
		'membership_plan_has_access_length'      => __( 'Plan Has Access Length', 'advanced-form-integration' ),
		'membership_plan_access_start'           => __( 'Plan Access Start (UTC)', 'advanced-form-integration' ),
		'membership_plan_access_start_local'     => __( 'Plan Access Start (Local)', 'advanced-form-integration' ),
		'membership_plan_access_end'             => __( 'Plan Access End (UTC)', 'advanced-form-integration' ),
		'membership_plan_access_end_local'       => __( 'Plan Access End (Local)', 'advanced-form-integration' ),
		'membership_plan_expiration_date'        => __( 'Plan Expiration Date', 'advanced-form-integration' ),
		'membership_product_id'                  => __( 'Product ID', 'advanced-form-integration' ),
		'membership_product_name'                => __( 'Product Name', 'advanced-form-integration' ),
		'membership_product_sku'                 => __( 'Product SKU', 'advanced-form-integration' ),
		'membership_product_type'                => __( 'Product Type', 'advanced-form-integration' ),
		'membership_product_url'                 => __( 'Product URL', 'advanced-form-integration' ),
		'membership_order_id'                    => __( 'Order ID', 'advanced-form-integration' ),
		'membership_order_number'                => __( 'Order Number', 'advanced-form-integration' ),
		'membership_order_status'                => __( 'Order Status', 'advanced-form-integration' ),
		'membership_order_total'                 => __( 'Order Total', 'advanced-form-integration' ),
		'membership_order_currency'              => __( 'Order Currency', 'advanced-form-integration' ),
		'membership_order_payment_method'        => __( 'Order Payment Method', 'advanced-form-integration' ),
		'membership_order_payment_method_title'  => __( 'Order Payment Method Title', 'advanced-form-integration' ),
		'membership_order_transaction_id'        => __( 'Order Transaction ID', 'advanced-form-integration' ),
		'membership_order_created'               => __( 'Order Created', 'advanced-form-integration' ),
		'membership_order_url'                   => __( 'Order Admin URL', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_woocommercememberships_bootstrap', 20 );

/**
 * Attach event listeners.
 */
function adfoin_woocommercememberships_bootstrap() {
	if ( ! function_exists( 'wc_memberships_get_user_membership' ) ) {
		return;
	}

	add_action( 'wc_memberships_user_membership_created', 'adfoin_woocommercememberships_handle_created', 10, 2 );
	add_action( 'wc_memberships_user_membership_saved', 'adfoin_woocommercememberships_handle_saved', 10, 2 );
	add_action( 'wc_memberships_user_membership_status_changed', 'adfoin_woocommercememberships_handle_status_changed', 10, 3 );
	add_action( 'wc_memberships_user_membership_deleted', 'adfoin_woocommercememberships_handle_deleted', 10, 1 );
	add_action( 'wc_memberships_user_membership_transferred', 'adfoin_woocommercememberships_handle_transferred', 10, 3 );
}

/**
 * Membership created handler.
 *
 * @param mixed $plan Membership plan object or null.
 * @param array $args Context arguments.
 */
function adfoin_woocommercememberships_handle_created( $plan, $args ) {
	if ( empty( $args['user_membership_id'] ) || ! empty( $args['is_update'] ) ) {
		return;
	}

	$membership = adfoin_woocommercememberships_get_membership( $args['user_membership_id'] );

	if ( ! $membership ) {
		return;
	}

	$extra = array(
		'membership_is_update' => adfoin_woocommercememberships_bool_to_string( ! empty( $args['is_update'] ) ),
	);

	adfoin_woocommercememberships_dispatch( 'membershipCreated', $membership, $extra );
}

/**
 * Membership saved handler.
 *
 * @param mixed $plan Membership plan.
 * @param array $args Context arguments.
 */
function adfoin_woocommercememberships_handle_saved( $plan, $args ) {
	if ( empty( $args['user_membership_id'] ) ) {
		return;
	}

	$membership = adfoin_woocommercememberships_get_membership( $args['user_membership_id'] );

	if ( ! $membership ) {
		return;
	}

	$extra = array(
		'membership_is_update' => adfoin_woocommercememberships_bool_to_string( ! empty( $args['is_update'] ) ),
	);

	adfoin_woocommercememberships_dispatch( 'membershipSaved', $membership, $extra );
}

/**
 * Membership status change handler.
 *
 * @param \WC_Memberships_User_Membership|int $membership Membership identifier.
 * @param string                              $old_status Previous status (without prefix).
 * @param string                              $new_status New status (without prefix).
 */
function adfoin_woocommercememberships_handle_status_changed( $membership, $old_status, $new_status ) {
	$membership = adfoin_woocommercememberships_get_membership( $membership );

	if ( ! $membership ) {
		return;
	}

	$old_status = strtolower( (string) $old_status );
	$new_status = strtolower( (string) $new_status );

	$extra = array(
		'membership_previous_status'       => $old_status,
		'membership_previous_status_label' => adfoin_woocommercememberships_status_label( $old_status ),
		'membership_new_status'            => $new_status,
		'membership_new_status_label'      => adfoin_woocommercememberships_status_label( $new_status ),
	);

	adfoin_woocommercememberships_dispatch( 'membershipStatusChanged', $membership, $extra );

	$status_map = adfoin_woocommercememberships_status_map();

	if ( isset( $status_map[ $new_status ] ) ) {
		adfoin_woocommercememberships_dispatch( $status_map[ $new_status ], $membership, $extra );
	}
}

/**
 * Membership deleted handler.
 *
 * @param \WC_Memberships_User_Membership $membership Membership object.
 */
function adfoin_woocommercememberships_handle_deleted( $membership ) {
	$membership = adfoin_woocommercememberships_get_membership( $membership );

	if ( ! $membership ) {
		return;
	}

	$extra = array(
		'membership_deleted_flag' => 'yes',
	);

	adfoin_woocommercememberships_dispatch( 'membershipDeleted', $membership, $extra );
}

/**
 * Membership transferred handler.
 *
 * @param \WC_Memberships_User_Membership|int $membership Membership identifier.
 * @param \WP_User                            $new_owner  New owner.
 * @param \WP_User                            $previous_owner Previous owner.
 */
function adfoin_woocommercememberships_handle_transferred( $membership, $new_owner, $previous_owner ) {
	$membership = adfoin_woocommercememberships_get_membership( $membership );

	if ( ! $membership ) {
		return;
	}

	$extra = array(
		'membership_transfer_new_user_id'       => $new_owner instanceof WP_User ? $new_owner->ID : '',
		'membership_transfer_new_user_email'    => $new_owner instanceof WP_User ? $new_owner->user_email : '',
		'membership_transfer_new_user_display'  => $new_owner instanceof WP_User ? $new_owner->display_name : '',
		'membership_transfer_previous_user_id'  => $previous_owner instanceof WP_User ? $previous_owner->ID : '',
		'membership_transfer_previous_user_email' => $previous_owner instanceof WP_User ? $previous_owner->user_email : '',
		'membership_transfer_previous_user_display' => $previous_owner instanceof WP_User ? $previous_owner->display_name : '',
	);

	adfoin_woocommercememberships_dispatch( 'membershipTransferred', $membership, $extra );
}

/**
 * Map status strings to trigger keys.
 *
 * @return array<string,string>
 */
function adfoin_woocommercememberships_status_map() {
	return array(
		'active'        => 'membershipStatusActive',
		'delayed'       => 'membershipStatusDelayed',
		'complimentary' => 'membershipStatusComplimentary',
		'pending'       => 'membershipStatusPending',
		'paused'        => 'membershipStatusPaused',
		'expired'       => 'membershipStatusExpired',
		'cancelled'     => 'membershipStatusCancelled',
	);
}

/**
 * Dispatch data to saved records.
 *
 * @param string                            $form_id     Trigger key.
 * @param \WC_Memberships_User_Membership   $membership  Membership object.
 * @param array<string,mixed>               $extra       Additional data.
 */
function adfoin_woocommercememberships_dispatch( $form_id, WC_Memberships_User_Membership $membership, array $extra = array() ) {
	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'woocommercememberships', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	$data = adfoin_woocommercememberships_build_payload( $membership );

	$data = array_merge(
		$data,
		array(
			'membership_form_id'      => $form_id,
			'membership_trigger'      => adfoin_woocommercememberships_get_form_name( 'woocommercememberships', $form_id ),
			'membership_triggered_at' => current_time( 'mysql' ),
		),
		$extra
	);

	if ( '1' == get_option( 'adfoin_general_settings_utm' ) ) {
		$data = array_merge( $data, adfoin_capture_utm_and_url_values() );
	}

	$plan    = $membership->get_plan();
	$user    = $membership->get_user();
	$order   = method_exists( $membership, 'get_order' ) ? $membership->get_order() : false;
	$product = method_exists( $membership, 'get_product' ) ? $membership->get_product() : false;

	$meta_placeholders = adfoin_woocommercememberships_meta_placeholders( $saved_records );
	$meta_values       = adfoin_woocommercememberships_meta_values( $membership, $plan, $user, $product, $order, $meta_placeholders );

	if ( ! empty( $meta_values ) ) {
		$data = array_merge( $data, $meta_values );
	}

	foreach ( $saved_records as $record ) {
		$action_provider = $record['action_provider'];

		if ( function_exists( "adfoin_{$action_provider}_send_data" ) ) {
			call_user_func( "adfoin_{$action_provider}_send_data", $record, $data );
		}
	}
}

/**
 * Build membership payload.
 *
 * @param \WC_Memberships_User_Membership $membership Membership object.
 *
 * @return array<string,mixed>
 */
function adfoin_woocommercememberships_build_payload( WC_Memberships_User_Membership $membership ) {
	$status_prefixed = $membership->get_status();
	$status          = adfoin_woocommercememberships_trim_status( $status_prefixed );

	$user = $membership->get_user();

	$plan    = $membership->get_plan();
	$product = method_exists( $membership, 'get_product' ) ? $membership->get_product() : false;
	$order   = method_exists( $membership, 'get_order' ) ? $membership->get_order() : false;

	$payload = array(
		'membership_id'                 => $membership->get_id(),
		'membership_user_id'            => $membership->get_user_id(),
		'membership_user_email'         => $user instanceof WP_User ? $user->user_email : '',
		'membership_user_first_name'    => $user instanceof WP_User ? get_user_meta( $user->ID, 'first_name', true ) : '',
		'membership_user_last_name'     => $user instanceof WP_User ? get_user_meta( $user->ID, 'last_name', true ) : '',
		'membership_user_display_name'  => $user instanceof WP_User ? $user->display_name : '',
		'membership_user_roles'         => $user instanceof WP_User ? implode( ',', (array) $user->roles ) : '',
		'membership_view_url'           => method_exists( $membership, 'get_view_membership_url' ) ? $membership->get_view_membership_url() : '',
		'membership_cancel_url'         => method_exists( $membership, 'get_cancel_membership_url' ) ? $membership->get_cancel_membership_url() : '',
		'membership_renew_url'          => method_exists( $membership, 'get_renew_membership_url' ) ? $membership->get_renew_membership_url() : '',
		'membership_transfer_new_user_id'       => '',
		'membership_transfer_new_user_email'    => '',
		'membership_transfer_new_user_display'  => '',
		'membership_transfer_previous_user_id'  => '',
		'membership_transfer_previous_user_email' => '',
		'membership_transfer_previous_user_display' => '',
		'membership_status'             => $status,
		'membership_status_prefixed'    => $status_prefixed,
		'membership_status_label'       => adfoin_woocommercememberships_status_label( $status ),
		'membership_previous_status'    => '',
		'membership_previous_status_label' => '',
		'membership_new_status'         => '',
		'membership_new_status_label'   => '',
		'membership_is_active'          => adfoin_woocommercememberships_bool_to_string( method_exists( $membership, 'is_active' ) ? $membership->is_active() : false ),
		'membership_is_update'          => '',
		'membership_deleted_flag'       => '',
		'membership_start_date'         => $membership->get_start_date(),
		'membership_start_date_local'   => method_exists( $membership, 'get_local_start_date' ) ? $membership->get_local_start_date() : '',
		'membership_end_date'           => $membership->get_end_date(),
		'membership_end_date_local'     => method_exists( $membership, 'get_local_end_date' ) ? $membership->get_local_end_date() : '',
		'membership_cancelled_date'     => method_exists( $membership, 'get_cancelled_date' ) ? $membership->get_cancelled_date() : '',
		'membership_cancelled_date_local' => method_exists( $membership, 'get_local_cancelled_date' ) ? $membership->get_local_cancelled_date() : '',
		'membership_paused_date'        => method_exists( $membership, 'get_paused_date' ) ? $membership->get_paused_date() : '',
		'membership_paused_date_local'  => method_exists( $membership, 'get_local_paused_date' ) ? $membership->get_local_paused_date() : '',
		'membership_total_active_time'  => method_exists( $membership, 'get_total_active_time' ) ? $membership->get_total_active_time() : '',
		'membership_total_inactive_time'=> method_exists( $membership, 'get_total_inactive_time' ) ? $membership->get_total_inactive_time() : '',
		'membership_last_active_date'   => method_exists( $membership, 'get_last_active_date' ) && $membership->get_last_active_date() instanceof DateTime ? adfoin_woocommercememberships_format_datetime( $membership->get_last_active_date()->getTimestamp() ) : '',
		'membership_last_active_since'  => method_exists( $membership, 'get_last_active_since' ) ? $membership->get_last_active_since() : '',
		'membership_type'               => method_exists( $membership, 'get_type' ) ? $membership->get_type() : '',
		'membership_previous_owners'    => method_exists( $membership, 'get_previous_owners' ) ? adfoin_woocommercememberships_encode_or_empty( $membership->get_previous_owners() ) : '',
		'membership_renewal_login_token'=> method_exists( $membership, 'get_renewal_login_token' ) ? $membership->get_renewal_login_token() : '',
	);

	if ( $plan instanceof WC_Memberships_Membership_Plan ) {
		$payload['membership_plan_id']                   = $plan->get_id();
		$payload['membership_plan_name']                 = $plan->get_name();
		$payload['membership_plan_slug']                 = $plan->get_slug();
		$payload['membership_plan_access_method']        = method_exists( $plan, 'get_access_method' ) ? $plan->get_access_method() : '';
		$payload['membership_plan_access_length']        = method_exists( $plan, 'get_access_length' ) ? $plan->get_access_length() : '';
		$payload['membership_plan_access_length_amount'] = method_exists( $plan, 'get_access_length_amount' ) ? $plan->get_access_length_amount() : '';
		$payload['membership_plan_access_length_period'] = method_exists( $plan, 'get_access_length_period' ) ? $plan->get_access_length_period() : '';
		$payload['membership_plan_access_length_type']   = method_exists( $plan, 'get_access_length_type' ) ? $plan->get_access_length_type() : '';
		$payload['membership_plan_access_length_human']  = method_exists( $plan, 'get_human_access_length' ) ? $plan->get_human_access_length() : '';
		$payload['membership_plan_has_access_length']    = adfoin_woocommercememberships_bool_to_string( method_exists( $plan, 'has_access_length' ) ? $plan->has_access_length() : false );
		$payload['membership_plan_access_start']         = method_exists( $plan, 'get_access_start_date' ) ? $plan->get_access_start_date() : '';
		$payload['membership_plan_access_start_local']   = method_exists( $plan, 'get_local_access_start_date' ) ? $plan->get_local_access_start_date() : '';
		$payload['membership_plan_access_end']           = method_exists( $plan, 'get_access_end_date' ) ? $plan->get_access_end_date() : '';
		$payload['membership_plan_access_end_local']     = method_exists( $plan, 'get_local_access_end_date' ) ? $plan->get_local_access_end_date() : '';
		$payload['membership_plan_expiration_date']      = method_exists( $plan, 'get_expiration_date' ) ? $plan->get_expiration_date() : '';
	}

	if ( $product instanceof WC_Product ) {
		$payload['membership_product_id']   = $product->get_id();
		$payload['membership_product_name'] = $product->get_name();
		$payload['membership_product_sku']  = $product->get_sku();
		$payload['membership_product_type'] = $product->get_type();
		$payload['membership_product_url']  = $product->get_permalink();
	}

	if ( $order instanceof WC_Order ) {
		$payload['membership_order_id']                   = $order->get_id();
		$payload['membership_order_number']               = $order->get_order_number();
		$payload['membership_order_status']               = $order->get_status();
		$payload['membership_order_total']                = $order->get_total();
		$payload['membership_order_currency']             = $order->get_currency();
		$payload['membership_order_payment_method']       = $order->get_payment_method();
		$payload['membership_order_payment_method_title'] = $order->get_payment_method_title();
		$payload['membership_order_transaction_id']       = $order->get_transaction_id();
		$payload['membership_order_created']              = adfoin_woocommercememberships_format_wc_datetime( $order->get_date_created() );
		$payload['membership_order_url']                  = admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );
	}

	return $payload;
}

/**
 * Format timestamp to string.
 *
 * @param int|string $timestamp Timestamp or strtotime string.
 *
 * @return string
 */
function adfoin_woocommercememberships_format_datetime( $timestamp ) {
	if ( empty( $timestamp ) ) {
		return '';
	}

	$timestamp = is_numeric( $timestamp ) ? (int) $timestamp : strtotime( (string) $timestamp );

	if ( ! $timestamp ) {
		return '';
	}

	return gmdate( 'Y-m-d H:i:s', $timestamp );
}

/**
 * Format WooCommerce datetime object.
 *
 * @param WC_DateTime|null $datetime Datetime object.
 *
 * @return string
 */
function adfoin_woocommercememberships_format_wc_datetime( $datetime ) {
	if ( $datetime instanceof WC_DateTime ) {
		return $datetime->date( 'Y-m-d H:i:s' );
	}

	return '';
}

/**
 * Retrieve human-friendly label for membership status.
 *
 * @param string $status Status key.
 *
 * @return string
 */
function adfoin_woocommercememberships_status_label( $status ) {
	if ( function_exists( 'wc_memberships_get_user_membership_status_name' ) ) {
		$prefixed = 0 === strpos( $status, 'wcm-' ) ? $status : 'wcm-' . ltrim( $status, 'wcm-' );
		$label    = wc_memberships_get_user_membership_status_name( $prefixed );

		if ( ! empty( $label ) && ! is_array( $label ) ) {
			return $label;
		}
	}

	return ucwords( str_replace( '-', ' ', (string) $status ) );
}

/**
 * Trim status prefix.
 *
 * @param string $status Status.
 *
 * @return string
 */
function adfoin_woocommercememberships_trim_status( $status ) {
	$status = (string) $status;

	return 0 === strpos( $status, 'wcm-' ) ? substr( $status, 4 ) : $status;
}

/**
 * Convert boolean to yes/no.
 *
 * @param mixed $value Boolean-ish value.
 *
 * @return string
 */
function adfoin_woocommercememberships_bool_to_string( $value ) {
	return $value ? 'yes' : 'no';
}

/**
 * Resolve membership object from mixed input.
 *
 * @param mixed $membership Membership identifier or object.
 *
 * @return \WC_Memberships_User_Membership|false
 */
function adfoin_woocommercememberships_get_membership( $membership ) {
	if ( $membership instanceof WC_Memberships_User_Membership ) {
		return $membership;
	}

	$membership_id = 0;

	if ( is_numeric( $membership ) ) {
		$membership_id = (int) $membership;
	} elseif ( is_array( $membership ) && isset( $membership['user_membership_id'] ) ) {
		$membership_id = (int) $membership['user_membership_id'];
	}

	if ( ! $membership_id ) {
		return false;
	}

	$membership_object = wc_memberships_get_user_membership( $membership_id );

	return $membership_object instanceof WC_Memberships_User_Membership ? $membership_object : false;
}

/**
 * Encode arrays as JSON when needed.
 *
 * @param mixed $data Arbitrary data.
 *
 * @return string
 */
function adfoin_woocommercememberships_encode_or_empty( $data ) {
	if ( empty( $data ) ) {
		return '';
	}

	return wp_json_encode( $data );
}

/**
 * Extract dynamic meta placeholders.
 *
 * @param array<int,array<string,mixed>> $records Records.
 *
 * @return array<string,string[]>
 */
function adfoin_woocommercememberships_meta_placeholders( $records ) {
	$placeholders = array(
		'membership' => array(),
		'plan'       => array(),
		'user'       => array(),
		'product'    => array(),
		'order'      => array(),
	);

	if ( empty( $records ) ) {
		return $placeholders;
	}

	$prefixes = array(
		'membership' => 'membershipmeta_',
		'plan'       => 'planmeta_',
		'user'       => 'usermeta_',
		'product'    => 'productmeta_',
		'order'      => 'ordermeta_',
	);

	foreach ( $records as $record ) {
		if ( empty( $record['data'] ) ) {
			continue;
		}

		$data = json_decode( $record['data'], true );

		if ( empty( $data['field_data'] ) || ! is_array( $data['field_data'] ) ) {
			continue;
		}

		foreach ( $data['field_data'] as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}

			foreach ( $prefixes as $type => $prefix ) {
				if ( false === strpos( $value, $prefix ) ) {
					continue;
				}

				preg_match_all( '/' . preg_quote( $prefix, '/' ) . '.+?\\}\\}/', $value, $matches );

				if ( empty( $matches[0] ) ) {
					continue;
				}

				foreach ( $matches[0] as $tag ) {
					$tag = str_replace( '}}', '', $tag );

					if ( $tag ) {
						$placeholders[ $type ][] = $tag;
					}
				}
			}
		}
	}

	foreach ( $placeholders as $type => $tags ) {
		$placeholders[ $type ] = array_unique( $tags );
	}

	return $placeholders;
}

/**
 * Resolve placeholder meta values.
 *
 * @param \WC_Memberships_User_Membership $membership   Membership.
 * @param \WC_Memberships_Membership_Plan $plan         Plan.
 * @param \WP_User|null                   $user         User.
 * @param \WC_Product|false               $product      Product.
 * @param \WC_Order|false                 $order        Order.
 * @param array<string,string[]>          $placeholders Placeholders.
 *
 * @return array<string,mixed>
 */
function adfoin_woocommercememberships_meta_values( $membership, $plan, $user, $product, $order, $placeholders ) {
	$values = array();

	if ( ! empty( $placeholders['membership'] ) ) {
		$meta = get_post_meta( $membership->get_id() );

		foreach ( $placeholders['membership'] as $tag ) {
			$key            = str_replace( 'membershipmeta_', '', $tag );
			$values[ $tag ] = isset( $meta[ $key ] ) ? adfoin_woocommercememberships_encode_or_empty( maybe_unserialize( $meta[ $key ][0] ?? $meta[ $key ] ) ) : '';
		}
	}

	if ( $plan instanceof WC_Memberships_Membership_Plan && ! empty( $placeholders['plan'] ) ) {
		$meta = get_post_meta( $plan->get_id() );

		foreach ( $placeholders['plan'] as $tag ) {
			$key            = str_replace( 'planmeta_', '', $tag );
			$values[ $tag ] = isset( $meta[ $key ] ) ? adfoin_woocommercememberships_encode_or_empty( maybe_unserialize( $meta[ $key ][0] ?? $meta[ $key ] ) ) : '';
		}
	}

	if ( $user instanceof WP_User && ! empty( $placeholders['user'] ) ) {
		foreach ( $placeholders['user'] as $tag ) {
			$key            = str_replace( 'usermeta_', '', $tag );
			$meta_value     = get_user_meta( $user->ID, $key, true );
			$values[ $tag ] = is_array( $meta_value ) ? adfoin_woocommercememberships_encode_or_empty( $meta_value ) : $meta_value;
		}
	}

	if ( $product instanceof WC_Product && ! empty( $placeholders['product'] ) ) {
		$meta = get_post_meta( $product->get_id() );

		foreach ( $placeholders['product'] as $tag ) {
			$key            = str_replace( 'productmeta_', '', $tag );
			$values[ $tag ] = isset( $meta[ $key ] ) ? adfoin_woocommercememberships_encode_or_empty( maybe_unserialize( $meta[ $key ][0] ?? $meta[ $key ] ) ) : '';
		}
	}

	if ( $order instanceof WC_Order && ! empty( $placeholders['order'] ) ) {
		foreach ( $placeholders['order'] as $tag ) {
			$key            = str_replace( 'ordermeta_', '', $tag );
			$meta_value     = $order->get_meta( $key, true );
			$values[ $tag ] = is_array( $meta_value ) ? adfoin_woocommercememberships_encode_or_empty( $meta_value ) : $meta_value;
		}
	}

	return $values;
}
