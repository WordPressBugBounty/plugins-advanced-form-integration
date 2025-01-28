<?php

// Get SureMembers Triggers
function adfoin_suremembers_get_forms($form_provider) {
    if ($form_provider != 'suremembers') {
        return;
    }

    $triggers = array(
        'accessGranted' => __('Access Granted', 'advanced-form-integration'),
        'accessRevoked' => __('Access Revoked', 'advanced-form-integration'),
        'groupUpdated'  => __('Group Updated', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get SureMembers Fields
function adfoin_suremembers_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'suremembers') {
        return;
    }

    $fields = array();

    if ($form_id === 'accessGranted' || $form_id === 'accessRevoked') {
        $fields = array_merge(
            adfoin_suremembers_access_group_fields(),
            adfoin_suremembers_user_fields()
        );
    } elseif ($form_id === 'groupUpdated') {
        $fields = array_merge(
            adfoin_suremembers_access_group_fields(),
            adfoin_suremembers_group_update_fields()
        );
    }

    return $fields;
}

// Access Group Fields
function adfoin_suremembers_access_group_fields() {
    return array(
        'group_id'   => __('Group ID', 'advanced-form-integration'),
        'group_name' => __('Group Name', 'advanced-form-integration'),
    );
}

// User Fields
function adfoin_suremembers_user_fields() {
    return array(
        'user_id'    => __('User ID', 'advanced-form-integration'),
        'user_login' => __('User Login', 'advanced-form-integration'),
        'first_name' => __('First Name', 'advanced-form-integration'),
        'last_name'  => __('Last Name', 'advanced-form-integration'),
        'email'      => __('Email', 'advanced-form-integration'),
    );
}

// Group Update Fields
function adfoin_suremembers_group_update_fields() {
    return array(
        'group_rules'            => __('Group Rules', 'advanced-form-integration'),
        'group_redirect_url'     => __('Redirect URL', 'advanced-form-integration'),
        'group_unauthorized_action' => __('Unauthorized Action', 'advanced-form-integration'),
        'group_preview_content'  => __('Preview Content', 'advanced-form-integration'),
    );
}

// Send Data
function adfoin_suremembers_send_trigger_data($saved_records, $posted_data) {
    foreach ($saved_records as $record) {
        $action_provider = $record['action_provider'];
        call_user_func("adfoin_{$action_provider}_send_data", $record, $posted_data);
    }
}

// Handle Access Granted
add_action('suremembers_after_access_grant', 'adfoin_suremembers_handle_access_grant', 10, 2);
function adfoin_suremembers_handle_access_grant($user_id, $group_ids) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('suremembers', 'accessGranted');

    if (empty($saved_records)) {
        return;
    }

    foreach ($group_ids as $group_id) {
        $group_name = get_the_title($group_id);

        $posted_data = array(
            'group_id'   => $group_id,
            'group_name' => $group_name,
        );

        $user_data = adfoin_suremembers_get_user_data($user_id);

        if ($user_data) {
            $posted_data = array_merge($posted_data, $user_data);
        }

        adfoin_suremembers_send_trigger_data($saved_records, $posted_data);
    }
}

// Handle Access Revoked
add_action('suremembers_after_access_revoke', 'adfoin_suremembers_handle_access_revoke', 10, 2);
function adfoin_suremembers_handle_access_revoke($user_id, $group_ids) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('suremembers', 'accessRevoked');

    if (empty($saved_records)) {
        return;
    }

    foreach ($group_ids as $group_id) {
        $group_name = get_the_title($group_id);

        $posted_data = array(
            'group_id'   => $group_id,
            'group_name' => $group_name,
        );

        $user_data = adfoin_suremembers_get_user_data($user_id);

        if ($user_data) {
            $posted_data = array_merge($posted_data, $user_data);
        }

        adfoin_suremembers_send_trigger_data($saved_records, $posted_data);
    }
}

// Handle Group Updated
add_action('suremembers_after_submit_form', 'adfoin_suremembers_handle_group_update', 10, 1);
function adfoin_suremembers_handle_group_update($group_id) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('suremembers', 'groupUpdated');

    if (empty($saved_records)) {
        return;
    }

    $group_data = array(
        'group_id'   => $group_id,
        'group_name' => get_the_title($group_id),
        'group_rules' => get_post_meta($group_id, 'rules', true),
    );

    adfoin_suremembers_send_trigger_data($saved_records, $group_data);
}

// Get User Data
function adfoin_suremembers_get_user_data($user_id) {
    $user = get_userdata($user_id);

    if (!$user) {
        return [];
    }

    return array(
        'user_id'    => $user_id,
        'user_login' => $user->user_login,
        'first_name' => $user->first_name,
        'last_name'  => $user->last_name,
        'email'      => $user->user_email,
    );
}