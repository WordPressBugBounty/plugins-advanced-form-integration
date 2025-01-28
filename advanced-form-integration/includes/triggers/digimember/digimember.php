<?php

// Get DigiMember Triggers
function adfoin_digimember_get_forms($form_provider) {
    if ($form_provider != 'digimember') {
        return;
    }

    $triggers = array(
        'purchaseProduct' => __('User purchases a product', 'advanced-form-integration'),
    );

    return $triggers;
}

// Get DigiMember Fields
function adfoin_digimember_get_form_fields($form_provider, $form_id) {
    if ($form_provider != 'digimember') {
        return;
    }

    $fields = array();

    if ($form_id === 'purchaseProduct') {
        $fields = [
            'user_id'     => __('User ID', 'advanced-form-integration'),
            'user_name'   => __('User Name', 'advanced-form-integration'),
            'user_email'  => __('User Email', 'advanced-form-integration'),
            'product_id'  => __('Product ID', 'advanced-form-integration'),
            'product_name'=> __('Product Name', 'advanced-form-integration'),
            'order_id'    => __('Order ID', 'advanced-form-integration'),
            'purchase_date'=> __('Purchase Date', 'advanced-form-integration'),
            'payment_status'=> __('Payment Status', 'advanced-form-integration'),
        ];
    }

    return $fields;
}

// Hook into DigiMember "purchase product" action
add_action('digimember_purchase', 'adfoin_digimember_handle_purchase', 10, 4);
function adfoin_digimember_handle_purchase($user_id, $product_id, $order_id, $reason) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger('digimember', 'purchaseProduct');

    if (empty($saved_records)) {
        return;
    }

    // Bail if the purchase reason is not "order_paid"
    if ($reason !== 'order_paid') {
        return;
    }

    $user = get_userdata($user_id);
    $product = get_post($product_id);

    // Prepare posted data
    $posted_data = array(
        'user_id'        => $user_id,
        'user_name'      => $user->display_name,
        'user_email'     => $user->user_email,
        'product_id'     => $product_id,
        'product_name'   => $product->post_title,
        'order_id'       => $order_id,
        'purchase_date'  => current_time('mysql'),
        'payment_status' => 'Paid',
    );

    adfoin_digimember_send_trigger_data($saved_records, $posted_data);
}

// Send data
function adfoin_digimember_send_trigger_data($saved_records, $posted_data) {
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