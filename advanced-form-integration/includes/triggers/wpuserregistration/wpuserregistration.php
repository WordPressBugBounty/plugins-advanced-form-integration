<?php

// Get WP User Registration Triggers
function adfoin_wpuserregistration_get_forms($form_provider) {
    if ($form_provider != 'wpuserregistration') {
        return;
    }

    $triggers = [
        'userCreated'      => __('User Created', 'advanced-form-integration'),
        'userProfileUpdate'=> __('User Profile Updated', 'advanced-form-integration'),
        'userLogin'        => __('User Login', 'advanced-form-integration'),
        'userPasswordReset'=> __('User Password Reset', 'advanced-form-integration'),
        'userDeleted'      => __('User Deleted', 'advanced-form-integration'),
    ];

    return $triggers;
}

// Get WP User Registration Fields
function adfoin_wpuserregistration_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'wpuserregistration') {
        return;
    }

    $fields = [
        'user_id'       => __('User ID', 'advanced-form-integration'),
        'user_email'    => __('Email', 'advanced-form-integration'),
        'user_login'    => __('Username', 'advanced-form-integration'),
        'nickname'      => __('Nickname', 'advanced-form-integration'),
        'display_name'  => __('Display Name', 'advanced-form-integration'),
        'first_name'    => __('First Name', 'advanced-form-integration'),
        'last_name'     => __('Last Name', 'advanced-form-integration'),
        'user_url'      => __('Website', 'advanced-form-integration'),
        'description'   => __('Biographical Info', 'advanced-form-integration'),
    ];

    if (in_array($form_id, ['userLogin', 'userPasswordReset', 'userDeleted'])) {
        unset($fields['first_name'], $fields['last_name'], $fields['description']);
    }

    return $fields;
}

// Handle User Creation
function adfoin_wpuserregistration_handle_user_created($user_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('wpuserregistration', 'userCreated');

    if (empty($saved_records)) {
        return;
    }

    $user = get_userdata($user_id);
    $posted_data = [
        'user_id'      => $user_id,
        'user_email'   => $user->user_email,
        'user_login'   => $user->user_login,
        'nickname'     => $user->nickname,
        'display_name' => $user->display_name,
        'first_name'   => $user->first_name,
        'last_name'    => $user->last_name,
        'user_url'     => $user->user_url,
        'description'  => $user->description,
    ];

    adfoin_wpuserregistration_send_trigger_data($saved_records, $posted_data);
}

// Handle User Profile Update
function adfoin_wpuserregistration_handle_profile_update($user_id, $old_user_data) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('wpuserregistration', 'userProfileUpdate');

    if (empty($saved_records)) {
        return;
    }

    $user = get_userdata($user_id);
    $posted_data = [
        'user_id'      => $user_id,
        'user_email'   => $user->user_email,
        'user_login'   => $user->user_login,
        'nickname'     => $user->nickname,
        'display_name' => $user->display_name,
        'first_name'   => $user->first_name,
        'last_name'    => $user->last_name,
        'user_url'     => $user->user_url,
        'description'  => $user->description,
    ];

    adfoin_wpuserregistration_send_trigger_data($saved_records, $posted_data);
}

// Handle User Login
function adfoin_wpuserregistration_handle_user_login($user_login, $user) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('wpuserregistration', 'userLogin');

    if (empty($saved_records)) {
        return;
    }

    $posted_data = [
        'user_id'      => $user->ID,
        'user_email'   => $user->user_email,
        'user_login'   => $user_login,
        'nickname'     => $user->nickname,
        'display_name' => $user->display_name,
        'user_url'     => $user->user_url,
    ];

    adfoin_wpuserregistration_send_trigger_data($saved_records, $posted_data);
}

// Handle Password Reset
function adfoin_wpuserregistration_handle_password_reset($user) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('wpuserregistration', 'userPasswordReset');

    if (empty($saved_records)) {
        return;
    }

    $posted_data = [
        'user_id'      => $user->ID,
        'user_email'   => $user->user_email,
        'user_login'   => $user->user_login,
        'nickname'     => $user->nickname,
        'display_name' => $user->display_name,
        'user_url'     => $user->user_url,
    ];

    adfoin_wpuserregistration_send_trigger_data($saved_records, $posted_data);
}

// Handle User Deletion
function adfoin_wpuserregistration_handle_user_deleted($user_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('wpuserregistration', 'userDeleted');

    if (empty($saved_records)) {
        return;
    }

    $posted_data = [
        'user_id' => $user_id,
    ];

    adfoin_wpuserregistration_send_trigger_data($saved_records, $posted_data);
}

// Send Trigger Data
function adfoin_wpuserregistration_send_trigger_data($saved_records, $posted_data) {
    $job_queue = get_option('adfoin_general_settings_job_queue');

    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];

        if ($job_queue) {
            as_enqueue_async_action("adfoin_{$action_provider}_job_queue", [
                'data' => [
                    'record'      => $record,
                    'posted_data' => $posted_data,
                ],
            ]);
        } else {
            call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
        }
    }
}

// Add Hooks
add_action('user_register', 'adfoin_wpuserregistration_handle_user_created', 10, 1);
add_action('profile_update', 'adfoin_wpuserregistration_handle_profile_update', 10, 2);
add_action('wp_login', 'adfoin_wpuserregistration_handle_user_login', 10, 2);
add_action('password_reset', 'adfoin_wpuserregistration_handle_password_reset', 10, 1);
add_action('delete_user', 'adfoin_wpuserregistration_handle_user_deleted', 10, 1);