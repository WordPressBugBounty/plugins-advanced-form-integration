<?php

/**
 * Charitable trigger integration for Advanced Form Integration.
 *
 * Registers triggers, field maps, and action hooks for:
 *   - Guest makes a donation        (charitable_donation_save → anonymousDonation)
 *   - User makes a donation         (charitable_donation_save → userDonation)
 *   - Donation Completed            (charitable_donation_status_changed → donationCompleted)
 *   - Donation Pending              (charitable_donation_status_changed → donationPending)
 *   - Donation Failed               (charitable_donation_status_changed → donationFailed)
 *   - Donation Refunded             (charitable_donation_status_changed → donationRefunded)
 *   - Donation Cancelled            (charitable_donation_status_changed → donationCancelled)
 */

// ---------------------------------------------------------------------------
// Trigger list
// ---------------------------------------------------------------------------

/**
 * Return the available Charitable trigger events.
 *
 * @param string $form_provider Active form provider slug.
 *
 * @return array|void
 */
function adfoin_charitable_get_forms( $form_provider ) {
	if ( 'charitable' !== $form_provider ) {
		return;
	}

	return array(
		'anonymousDonation' => __( 'Guest makes a donation', 'advanced-form-integration' ),
		'userDonation'      => __( 'User makes a donation', 'advanced-form-integration' ),
		'donationCompleted' => __( 'Donation Completed', 'advanced-form-integration' ),
		'donationPending'   => __( 'Donation Pending', 'advanced-form-integration' ),
		'donationFailed'    => __( 'Donation Failed', 'advanced-form-integration' ),
		'donationRefunded'  => __( 'Donation Refunded', 'advanced-form-integration' ),
		'donationCancelled' => __( 'Donation Cancelled', 'advanced-form-integration' ),
	);
}

// ---------------------------------------------------------------------------
// Field maps
// ---------------------------------------------------------------------------

/**
 * Return the shared donation field labels used across all trigger types.
 *
 * @return array
 */
function adfoin_charitable_trigger_field_labels() {
	return array(
		'donation_id'             => __( 'Donation ID', 'advanced-form-integration' ),
		'donation_title'          => __( 'Donation Title', 'advanced-form-integration' ),
		'donation_amount'         => __( 'Donation Amount', 'advanced-form-integration' ),
		'donation_status'         => __( 'Donation Status', 'advanced-form-integration' ),
		'donation_payment_method' => __( 'Payment Method', 'advanced-form-integration' ),
		'donor_id'                => __( 'Donor ID', 'advanced-form-integration' ),
		'donor_first_name'        => __( 'Donor First Name', 'advanced-form-integration' ),
		'donor_last_name'         => __( 'Donor Last Name', 'advanced-form-integration' ),
		'donor_email'             => __( 'Donor Email', 'advanced-form-integration' ),
		'donor_address'           => __( 'Donor Address', 'advanced-form-integration' ),
		'donor_city'              => __( 'Donor City', 'advanced-form-integration' ),
		'donor_state'             => __( 'Donor State', 'advanced-form-integration' ),
		'donor_postcode'          => __( 'Donor Postcode', 'advanced-form-integration' ),
		'donor_country'           => __( 'Donor Country', 'advanced-form-integration' ),
		'donor_phone'             => __( 'Donor Phone', 'advanced-form-integration' ),
		'donor_company'           => __( 'Donor Company', 'advanced-form-integration' ),
		'campaign_id'             => __( 'Campaign ID', 'advanced-form-integration' ),
		'campaign_title'          => __( 'Campaign Title', 'advanced-form-integration' ),
		'campaign_goal'           => __( 'Campaign Goal', 'advanced-form-integration' ),
		'campaign_min_donation'   => __( 'Minimum Donation', 'advanced-form-integration' ),
		'campaign_end_date'       => __( 'Campaign End Date', 'advanced-form-integration' ),
	);
}

/**
 * Return the available merge fields for a given Charitable trigger.
 *
 * @param string $form_provider Active form provider slug.
 * @param string $form_id       Trigger ID.
 *
 * @return array|void
 */
function adfoin_charitable_get_form_fields( $form_provider, $form_id ) {
	if ( 'charitable' !== $form_provider ) {
		return;
	}

	$status_triggers = array(
		'donationCompleted',
		'donationPending',
		'donationFailed',
		'donationRefunded',
		'donationCancelled',
	);

	if ( 'anonymousDonation' === $form_id
		|| 'userDonation' === $form_id
		|| in_array( $form_id, $status_triggers, true )
	) {
		return adfoin_charitable_trigger_field_labels();
	}

	return array();
}

// ---------------------------------------------------------------------------
// Shared payload builder
// ---------------------------------------------------------------------------

/**
 * Build the posted_data payload from a Charitable_Donation object.
 *
 * Returns null when required data is missing.
 *
 * @param object $donation Charitable_Donation instance.
 *
 * @return array|null
 */
function adfoin_charitable_build_payload( $donation ) {
	$donation_id = method_exists( $donation, 'get_donation_id' )
		? (int) $donation->get_donation_id()
		: (int) $donation->ID;

	$donor_data = method_exists( $donation, 'get_donor_data' )
		? $donation->get_donor_data()
		: array();

	if ( ! is_array( $donor_data ) ) {
		$donor_data = array();
	}

	$campaigns = method_exists( $donation, 'get_campaign_donations' )
		? $donation->get_campaign_donations()
		: array();

	if ( empty( $campaigns ) ) {
		return null;
	}

	$campaign_data = reset( $campaigns );
	$campaign_id   = isset( $campaign_data->campaign_id ) ? (int) $campaign_data->campaign_id : 0;

	if ( ! $campaign_id ) {
		return null;
	}

	$campaign       = function_exists( 'charitable_get_campaign' ) ? charitable_get_campaign( $campaign_id ) : null;
	$campaign_title = ( $campaign && isset( $campaign->post_title ) ) ? $campaign->post_title : '';

	$campaign_goal = '';
	if ( $campaign && method_exists( $campaign, 'get_goal' ) ) {
		$campaign_goal = function_exists( 'charitable_format_money' )
			? charitable_format_money( $campaign->get_goal() )
			: (string) $campaign->get_goal();
	}

	$campaign_min = '';
	if ( function_exists( 'charitable_get_minimum_donation_amount' ) ) {
		$min_amount   = charitable_get_minimum_donation_amount( $campaign_id );
		$campaign_min = function_exists( 'charitable_format_money' )
			? charitable_format_money( $min_amount )
			: (string) $min_amount;
	}

	$campaign_end_date = ( $campaign && method_exists( $campaign, 'get_end_date' ) )
		? $campaign->get_end_date()
		: '';

	$donor_email = isset( $donor_data['email'] ) ? $donor_data['email'] : '';
	$user        = ( $donor_email && is_email( $donor_email ) )
		? get_user_by( 'email', $donor_email )
		: false;

	$donation_amount = method_exists( $donation, 'get_total_donation_amount' )
		? $donation->get_total_donation_amount()
		: 0;

	$donation_status = method_exists( $donation, 'get_status_label' )
		? $donation->get_status_label()
		: get_post_status( $donation_id );

	$donation_gateway = method_exists( $donation, 'get_gateway_label' )
		? $donation->get_gateway_label()
		: '';

	return array(
		'donation_id'             => $donation_id,
		'donation_title'          => get_the_title( $donation_id ),
		'donation_amount'         => $donation_amount,
		'donation_status'         => $donation_status,
		'donation_payment_method' => $donation_gateway,
		'donor_id'                => ( $user && isset( $user->ID ) ) ? $user->ID : null,
		'donor_first_name'        => isset( $donor_data['first_name'] ) ? $donor_data['first_name'] : '',
		'donor_last_name'         => isset( $donor_data['last_name'] ) ? $donor_data['last_name'] : '',
		'donor_email'             => $donor_email,
		'donor_address'           => isset( $donor_data['address'] ) ? $donor_data['address'] : '',
		'donor_city'              => isset( $donor_data['city'] ) ? $donor_data['city'] : '',
		'donor_state'             => isset( $donor_data['state'] ) ? $donor_data['state'] : '',
		'donor_postcode'          => isset( $donor_data['postcode'] ) ? $donor_data['postcode'] : '',
		'donor_country'           => isset( $donor_data['country'] ) ? $donor_data['country'] : '',
		'donor_phone'             => isset( $donor_data['phone'] ) ? $donor_data['phone'] : '',
		'donor_company'           => isset( $donor_data['company'] ) ? $donor_data['company'] : '',
		'campaign_id'             => $campaign_id,
		'campaign_title'          => $campaign_title,
		'campaign_goal'           => $campaign_goal,
		'campaign_min_donation'   => $campaign_min,
		'campaign_end_date'       => $campaign_end_date,
	);
}

// ---------------------------------------------------------------------------
// Hook: charitable_donation_save
// Fires when any donation is saved (new or updated).
// Signature: do_action( 'charitable_donation_save', $donation_id, $args )
// ---------------------------------------------------------------------------

add_action( 'charitable_donation_save', 'adfoin_charitable_handle_donation_save', 10, 2 );

/**
 * Handle the charitable_donation_save action.
 *
 * Routes to anonymousDonation or userDonation based on whether the donor
 * has a registered WordPress account.
 *
 * @param int   $donation_id The saved donation's post ID.
 * @param array $args        Args array passed by Charitable (may be empty for older versions).
 *
 * @return void
 */
function adfoin_charitable_handle_donation_save( $donation_id, $args ) {
	if ( ! function_exists( 'charitable_get_donation' ) ) {
		return;
	}

	$donation = charitable_get_donation( $donation_id );
	if ( ! $donation ) {
		return;
	}

	$donor_data  = method_exists( $donation, 'get_donor_data' ) ? $donation->get_donor_data() : array();
	$donor_email = is_array( $donor_data ) && isset( $donor_data['email'] ) ? $donor_data['email'] : '';
	$user        = ( $donor_email && is_email( $donor_email ) ) ? get_user_by( 'email', $donor_email ) : false;
	$trigger     = $user ? 'userDonation' : 'anonymousDonation';

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'charitable', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$posted_data = adfoin_charitable_build_payload( $donation );
	if ( null === $posted_data ) {
		return;
	}

	adfoin_dispatch_integrations( $saved_records, $posted_data );
}

// ---------------------------------------------------------------------------
// Hook: charitable_donation_status_changed
// Fires when a donation status transitions.
// Signature: do_action( 'charitable_donation_status_changed', $donation, $new_status, $old_status )
// ---------------------------------------------------------------------------

add_action( 'charitable_donation_status_changed', 'adfoin_charitable_handle_status_changed', 10, 3 );

/**
 * Handle the charitable_donation_status_changed action.
 *
 * Maps Charitable status slugs to AFI trigger IDs and dispatches accordingly.
 *
 * @param object $donation   Charitable_Donation instance.
 * @param string $new_status New donation status slug (e.g. 'charitable-completed').
 * @param string $old_status Previous donation status slug.
 *
 * @return void
 */
function adfoin_charitable_handle_status_changed( $donation, $new_status, $old_status ) {
	if ( ! is_object( $donation ) ) {
		return;
	}

	$status_map = array(
		'charitable-completed' => 'donationCompleted',
		'charitable-pending'   => 'donationPending',
		'charitable-failed'    => 'donationFailed',
		'charitable-refunded'  => 'donationRefunded',
		'charitable-cancelled' => 'donationCancelled',
	);

	if ( ! isset( $status_map[ $new_status ] ) ) {
		return;
	}

	$trigger = $status_map[ $new_status ];

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'charitable', $trigger );

	if ( empty( $saved_records ) ) {
		return;
	}

	$posted_data = adfoin_charitable_build_payload( $donation );
	if ( null === $posted_data ) {
		return;
	}

	adfoin_dispatch_integrations( $saved_records, $posted_data );
}
