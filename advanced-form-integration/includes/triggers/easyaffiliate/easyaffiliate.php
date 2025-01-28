<?php

// Get EasyAffiliate Triggers
function adfoin_easyaffiliate_get_forms($form_provider) {
    if ($form_provider != 'easyaffiliate') {
        return;
    }

    $triggers = array(
        'becomeAffiliate' => __('User becomes an affiliate', 'advanced-form-integration'),
        'earnReferral'    => __('User earns a referral', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get EasyAffiliate Fields
function adfoin_easyaffiliate_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'easyaffiliate') {
        return;
    }

    $fields = array();

    if ($form_id === 'becomeAffiliate') {
        $fields = [
            'user_id'   => __('User ID', 'advanced-form-integration'),
            'user_name' => __('User Name', 'advanced-form-integration'),
            'user_email'=> __('User Email', 'advanced-form-integration'),
        ];
    } elseif ($form_id === 'earnReferral') {
        $fields = [
            'user_id'       => __('User ID', 'advanced-form-integration'),
            'user_name'     => __('User Name', 'advanced-form-integration'),
            'user_email'    => __('User Email', 'advanced-form-integration'),
            'referral_id'   => __('Referral ID', 'advanced-form-integration'),
            'referral_amount'=> __('Referral Amount', 'advanced-form-integration'),
            'transaction_id'=> __('Transaction ID', 'advanced-form-integration'),
            'transaction_date'=> __('Transaction Date', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into EasyAffiliate "become affiliate" action
add_action('esaf_event_affiliate-added', 'adfoin_easyaffiliate_handle_become_affiliate', 10, 1);
function adfoin_easyaffiliate_handle_become_affiliate($args) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('easyaffiliate', 'becomeAffiliate');

    if (empty($saved_records)) {
        return;
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    // Bail if no user
    if (empty($user)) {
        return;
    }

    $posted_data = array(
        'user_id'    => $user_id,
        'user_name'  => $user->display_name,
        'user_email' => $user->user_email,
    );

    adfoin_easyaffiliate_send_trigger_data($saved_records, $posted_data);
}

// Hook into EasyAffiliate "earn referral" action
add_action('esaf_event_transaction-recorded', 'adfoin_easyaffiliate_handle_earn_referral', 10, 1);
function adfoin_easyaffiliate_handle_earn_referral($args) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('easyaffiliate', 'earnReferral');

    if (empty($saved_records)) {
        return;
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    // Bail if no user
    if (empty($user)) {
        return;
    }

    $referral_id = $args['referral_id'] ?? null;
    $referral_amount = $args['amount'] ?? null;
    $transaction_id = $args['transaction_id'] ?? null;
    $transaction_date = $args['date'] ?? current_time('mysql');

    $posted_data = array(
        'user_id'         => $user_id,
        'user_name'       => $user->display_name,
        'user_email'      => $user->user_email,
        'referral_id'     => $referral_id,
        'referral_amount' => $referral_amount,
        'transaction_id'  => $transaction_id,
        'transaction_date'=> $transaction_date,
    );

    adfoin_easyaffiliate_send_trigger_data($saved_records, $posted_data);
}

// Send data
function adfoin_easyaffiliate_send_trigger_data($saved_records, $posted_data) {
    $job_queue = get_option('adfoin_general_settings_job_queue');

    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];
        if ($job_queue) {
            as_enqueue_async_action("adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data,
                ),
            ));
        } else {
            call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
        }
    }
}