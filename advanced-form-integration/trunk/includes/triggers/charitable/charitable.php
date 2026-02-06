<?php

// Get Charitable Triggers
function adfoin_charitable_get_forms($form_provider) {
    if ($form_provider != 'charitable') {
        return;
    }

    $triggers = array(
        'anonymousDonation' => __('Guest makes a donation', 'advanced-form-integration'),
        'userDonation'      => __('User makes a donation', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get Charitable Fields
function adfoin_charitable_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'charitable') {
        return;
    }

    $fields = array();

    if ($form_id === 'anonymousDonation' || $form_id === 'userDonation') {
        $fields = [
            'donation_id'            => __('Donation ID', 'advanced-form-integration'),
            'donation_title'         => __('Donation Title', 'advanced-form-integration'),
            'donation_amount'        => __('Donation Amount', 'advanced-form-integration'),
            'donation_status'        => __('Donation Status', 'advanced-form-integration'),
            'donation_payment_method'=> __('Payment Method', 'advanced-form-integration'),
            'donor_id'               => __('Donor ID', 'advanced-form-integration'),
            'donor_first_name'       => __('Donor First Name', 'advanced-form-integration'),
            'donor_last_name'        => __('Donor Last Name', 'advanced-form-integration'),
            'donor_email'            => __('Donor Email', 'advanced-form-integration'),
            'donor_country'          => __('Donor Country', 'advanced-form-integration'),
            'donor_phone'            => __('Donor Phone', 'advanced-form-integration'),
            'campaign_id'            => __('Campaign ID', 'advanced-form-integration'),
            'campaign_title'         => __('Campaign Title', 'advanced-form-integration'),
            'campaign_goal'          => __('Campaign Goal', 'advanced-form-integration'),
            'campaign_min_donation'  => __('Minimum Donation', 'advanced-form-integration'),
            'campaign_end_date'      => __('Campaign End Date', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into Charitable donation save action
add_action('charitable_donation_save', 'adfoin_charitable_handle_donation', 10, 2);

function adfoin_charitable_handle_donation($donation_id, $post) {
    $integration = new Advanced_Form_Integration_Integration();

    $donation = charitable_get_donation($donation_id);
    $donor    = $donation->get_donor_data();
    $user     = null;

    if (isset($donor['email'])) {
        $user = get_user_by('email', $donor['email']);
    }
    
    // Determine if it's an anonymous donation
    $trigger       = $user ? 'userDonation' : 'anonymousDonation';
    $saved_records = $integration->get_by_trigger('charitable', $trigger);

    if (empty($saved_records)) {
        return;
    }

    // Get campaign data
    $campaigns = $donation->get_campaign_donations();
    if (empty($campaigns)) {
        return;
    }

    $campaign_data = reset($campaigns);
    $campaign_id   = $campaign_data->campaign_id;
    $campaign      = charitable_get_campaign($campaign_id);

    if (!charitable_is_approved_status(get_post_status($donation_id))) {
        return;
    }

    // Prepare posted data
    $posted_data = array(
        'donation_id'            => $donation_id,
        'donation_title'         => get_the_title($donation_id),
        'donation_amount'        => $donation->get_total_donation_amount(),
        'donation_status'        => $donation->get_status_label(),
        'donation_payment_method'=> $donation->get_gateway_label(),
        'donor_id'               => $user ? $user->ID : null,
        'donor_first_name'       => $donor['first_name'],
        'donor_last_name'        => $donor['last_name'],
        'donor_email'            => $donor['email'],
        'donor_country'          => $donor['country'],
        'donor_phone'            => $donor['phone'],
        'campaign_id'            => $campaign_id,
        'campaign_title'         => $campaign->post_title,
        'campaign_goal'          => charitable_format_money($campaign->get_goal()),
        'campaign_min_donation'  => charitable_format_money(charitable_get_minimum_donation_amount($campaign_id)),
        'campaign_end_date'      => $campaign->get_end_date(),
    );

    $integration->send($saved_records, $posted_data);
}