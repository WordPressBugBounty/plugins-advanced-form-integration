<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Solid Affiliate Triggers.
 *
 * @param string $form_provider Integration provider.
 * @return array|void
 */
function adfoin_solidaffiliate_get_forms( $form_provider ) {
	if ( $form_provider !== 'solid-affiliate' ) {
		return;
	}
	$triggers = array(
		'affiliateRegistered' => __( 'Affiliate Registered', 'advanced-form-integration' ),
		'referralAccepted'    => __( 'Referral Accepted', 'advanced-form-integration' ),
	);
	return $triggers;
}

/**
 * Get Solid Affiliate Form Fields.
 *
 * @param string $form_provider Integration provider.
 * @param string $form_id       Specific trigger ID.
 * @return array|void
 */
function adfoin_solidaffiliate_get_form_fields( $form_provider, $form_id ) {
	if ( $form_provider !== 'solid-affiliate' ) {
		return;
	}
	$fields = array();
	if ( $form_id === 'affiliateRegistered' ) {
		$fields = array(
			'affiliate_id' => __( 'Affiliate ID', 'advanced-form-integration' ),
			'user_email'   => __( 'User Email', 'advanced-form-integration' ),
			'first_name'   => __( 'First Name', 'advanced-form-integration' ),
			'last_name'    => __( 'Last Name', 'advanced-form-integration' ),
		);
	} elseif ( $form_id === 'referralAccepted' ) {
		$fields = array(
			'referral_id'  => __( 'Referral ID', 'advanced-form-integration' ),
			'affiliate_id' => __( 'Affiliate ID', 'advanced-form-integration' ),
			'referral_count' => __( 'Referral Count', 'advanced-form-integration' ),
		);
	}
	return $fields;
}

/**
 * Get basic user data.
 *
 * @param int $user_id The user ID.
 * @return array
 */
function adfoin_solidaffiliate_get_userdata( $user_id ) {
	$user_data = array();
	$user      = get_userdata( $user_id );
	if ( $user ) {
		$user_data['first_name'] = $user->first_name;
		$user_data['last_name']  = $user->last_name;
		$user_data['user_email'] = $user->user_email;
		$user_data['user_id']    = $user_id;
	}
	return $user_data;
}

/**
 * Handle Affiliate Registration.
 *
 * Fired when an affiliate is saved via Solid Affiliate.
 *
 * @param object $affiliate SolidAffiliate\Models\Affiliate instance.
 */
function adfoin_solidaffiliate_handle_affiliate_registration( $affiliate ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'solid-affiliate', 'affiliateRegistered' );
	if ( empty( $saved_records ) ) {
		return;
	}
	// Get affiliate attributes.
	$attributes = $affiliate->__get( 'attributes' );
	$user_id = intval( $attributes['user_id'] );
	$posted_data = array(
		'affiliate_id' => $affiliate->id,
	);
	// Merge in basic user data.
	$posted_data = array_merge( $posted_data, adfoin_solidaffiliate_get_userdata( $user_id ) );
	$integration->send( $saved_records, $posted_data );
}
add_action( 'data_model_solid_affiliate_affiliates_save', 'adfoin_solid_affiliate_handle_affiliate_registration' );

/**
 * Handle Referral Accepted.
 *
 * Fired when a referral is saved and accepted.
 *
 * @param object $referral SolidAffiliate\Models\Referral instance.
 */
function adfoin_solidaffiliate_handle_referral_accepted( $referral ) {
	$integration = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'solid-affiliate', 'referralAccepted' );
	if ( empty( $saved_records ) ) {
		return;
	}
	$attributes = $referral->__get( 'attributes' );
	$posted_data = array(
		'referral_id'  => $attributes['id'],
		'affiliate_id' => $attributes['affiliate_id'],
		'referral_count' => count( $referral->referrals() ), // Example: count of referrals
	);
	$integration->send( $saved_records, $posted_data );
}
add_action( 'data_model_solid_affiliate_referrals_save', 'adfoin_solid_affiliate_handle_referral_accepted' );
