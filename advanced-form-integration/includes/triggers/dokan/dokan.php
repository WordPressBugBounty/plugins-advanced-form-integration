<?php

// Get Dokan Triggers
function adfoin_dokan_get_forms( $form_provider ) {
    if ( $form_provider != 'dokan' ) {
        return;
    }

    $triggers = array(
        'newVendor' => __( 'New Vendor Added', 'advanced-form-integration' ),
        'vendorUpdated' => __( 'Vendor Updated', 'advanced-form-integration' ),
        'vendorDeleted' => __( 'Vendor Deleted', 'advanced-form-integration' ),
        'refundRequest' => __( 'New Refund Request', 'advanced-form-integration' ),
        'refundApproved' => __( 'Refund Approved', 'advanced-form-integration' ),
        'refundCancelled' => __( 'Refund Cancelled', 'advanced-form-integration' ),
        'newWithdrawRequest' => __( 'New Withdraw Request', 'advanced-form-integration' ),
        'userToVendor' => __( 'User Becomes Vendor', 'advanced-form-integration' ),
    );

    return $triggers;
}

// Get Dokan Fields
function adfoin_dokan_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider != 'dokan' ) {
        return;
    }

    $fields = array();

    if ( $form_id === 'newVendor' ) {
        $fields = [
            'vendor_id' => __( 'Vendor ID', 'advanced-form-integration' ),
            'store_name' => __( 'Store Name', 'advanced-form-integration' ),
            'store_url' => __( 'Store URL', 'advanced-form-integration' ),
            'vendor_email' => __( 'Vendor Email', 'advanced-form-integration' ),
            'phone' => __( 'Phone', 'advanced-form-integration' ),
            'first_name' => __( 'First Name', 'advanced-form-integration' ),
            'last_name' => __( 'Last Name', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'vendorUpdated' ) {
        $fields = [
            'vendor_id' => __( 'Vendor ID', 'advanced-form-integration' ),
            'store_name' => __( 'Updated Store Name', 'advanced-form-integration' ),
            'store_url' => __( 'Updated Store URL', 'advanced-form-integration' ),
            'vendor_email' => __( 'Updated Vendor Email', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'refundRequest' || $form_id === 'refundApproved' || $form_id === 'refundCancelled' ) {
        $fields = [
            'refund_id' => __( 'Refund ID', 'advanced-form-integration' ),
            'refund_amount' => __( 'Refund Amount', 'advanced-form-integration' ),
            'refund_reason' => __( 'Refund Reason', 'advanced-form-integration' ),
            'order_id' => __( 'Order ID', 'advanced-form-integration' ),
        ];
    } elseif ( $form_id === 'newWithdrawRequest' ) {
        $fields = [
            'vendor_id' => __( 'Vendor ID', 'advanced-form-integration' ),
            'withdraw_amount' => __( 'Withdraw Amount', 'advanced-form-integration' ),
            'withdraw_method' => __( 'Withdraw Method', 'advanced-form-integration' ),
        ];
    }

    return $fields;
}

// Hooks for Dokan Actions
add_action( 'dokan_before_create_vendor', 'adfoin_dokan_handle_new_vendor', 10, 2 );
add_action( 'dokan_before_update_vendor', 'adfoin_dokan_handle_vendor_update', 10, 2 );
add_action( 'delete_user', 'adfoin_dokan_handle_vendor_delete', 10, 1 );
add_action( 'dokan_refund_request_created', 'adfoin_dokan_handle_refund_request', 10, 1 );
add_action( 'dokan_pro_refund_approved', 'adfoin_dokan_handle_refund_approved', 10, 1 );
add_action( 'dokan_pro_refund_cancelled', 'adfoin_dokan_handle_refund_cancelled', 10, 1 );
add_action( 'dokan_after_withdraw_request', 'adfoin_dokan_handle_withdraw_request', 10, 3 );

// Handle New Vendor
function adfoin_dokan_handle_new_vendor( $vendor_id, $data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'newVendor' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if (empty($vendor_id) || empty($data)) {
        return;
    }

    $posted_data = [];
    foreach ($data as $key => $item) {
        if ($key === 'payment') {
            if (!empty($item['bank'])) {
                foreach ($item['bank'] as $bankKey => $bankItem) {
                    $posted_data[$bankKey] = $bankItem;
                }
            }

            if (!empty($item['paypal'])) {
                foreach ($item['paypal'] as $paypalKey => $paypalItem) {
                    $posted_data['paypal_' . $paypalKey] = $paypalItem;
                }
            }
        } elseif ($key === 'address') {
            foreach ($item as $addrKey => $addrItem) {
                $posted_data[$addrKey] = $addrItem;
            }
        } elseif ($key === 'social' || $key === '_links' || $key === 'store_open_close') {
            continue;
        } else {
            $posted_data[$key] = \is_array($item) ? implode(',', $item) : $item;
        }
    }

    $enabledEUFields = DokanHelper::getEnabledVendorEUFields();

    if (!empty($enabledEUFields)) {
        foreach ($enabledEUFields as $euFiled) {
            if ($euFiled['name'] === 'eu_bank_name') {
                $posted_data[$euFiled['name']] = isset($data['bank_name']) ? $data['bank_name'] : '';
            } else {
                $posted_data[$euFiled['name']] = isset($data[$euFiled['name']]) ? $data[$euFiled['name']] : '';
            }
        }
    }

    $posted_data['enabled'] = isset($data['enabled']) ? $data['enabled'] : false;
    $posted_data['trusted'] = isset($data['trusted']) ? $data['trusted'] : false;
    $posted_data['featured'] = isset($data['featured']) ? $data['featured'] : false;
    $posted_data['vendor_id'] = $vendor_id;

    $integration->send( $saved_records, $posted_data );
}

// Handle Vendor Update
function adfoin_dokan_handle_vendor_update( $vendor_id, $data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'vendorUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = DokanHelper::formatVendorData( $vendor_id, $data );
    $integration->send( $saved_records, $posted_data );
}

// Handle Vendor Delete
function adfoin_dokan_handle_vendor_delete( $vendor_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'vendorDeleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $vendor_data = dokan()->vendor->get( $vendor_id )->to_array();
    $integration->send( $saved_records, $vendor_data );
}

// Handle Refund Request
function adfoin_dokan_handle_refund_request( $refund ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundRequest' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $refund_data = DokanHelper::formatRefundData( $refund );
    $integration->send( $saved_records, $refund_data );
}

// Handle Refund Approved
function adfoin_dokan_handle_refund_approved( $refund_data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundApproved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = DokanHelper::formatRefundData( $refund_data );
    $integration->send( $saved_records, $posted_data );
}

// Handle Refund Cancelled
function adfoin_dokan_handle_refund_cancelled( $refund_data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundCancelled' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = DokanHelper::formatRefundData( $refund_data );
    $integration->send( $saved_records, $posted_data );
}

// Handle Withdraw Request
function adfoin_dokan_handle_withdraw_request( $user_id, $amount, $method ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'newWithdrawRequest' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $withdraw_data = DokanHelper::formatWithdrawRequestData( $user_id, $amount, $method );
    $integration->send( $saved_records, $withdraw_data );
}