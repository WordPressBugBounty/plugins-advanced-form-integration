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

// Send Trigger Data
function adfoin_dokan_send_trigger_data( $saved_records, $posted_data ) {
    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];
        if ( $job_queue ) {
            as_enqueue_async_action( "adfoin_{$action_provider}_job_queue", array(
                'data' => array(
                    'record' => $record,
                    'posted_data' => $posted_data,
                ),
            ) );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $posted_data );
        }
    }
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

    $posted_data = DokanHelper::formatVendorData( $vendor_id, $data );
    adfoin_dokan_send_trigger_data( $saved_records, $posted_data );
}

// Handle Vendor Update
function adfoin_dokan_handle_vendor_update( $vendor_id, $data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'vendorUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = DokanHelper::formatVendorData( $vendor_id, $data );
    adfoin_dokan_send_trigger_data( $saved_records, $posted_data );
}

// Handle Vendor Delete
function adfoin_dokan_handle_vendor_delete( $vendor_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'vendorDeleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $vendor_data = dokan()->vendor->get( $vendor_id )->to_array();
    adfoin_dokan_send_trigger_data( $saved_records, $vendor_data );
}

// Handle Refund Request
function adfoin_dokan_handle_refund_request( $refund ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundRequest' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $refund_data = DokanHelper::formatRefundData( $refund );
    adfoin_dokan_send_trigger_data( $saved_records, $refund_data );
}

// Handle Refund Approved
function adfoin_dokan_handle_refund_approved( $refund_data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundApproved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = DokanHelper::formatRefundData( $refund_data );
    adfoin_dokan_send_trigger_data( $saved_records, $posted_data );
}

// Handle Refund Cancelled
function adfoin_dokan_handle_refund_cancelled( $refund_data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundCancelled' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = DokanHelper::formatRefundData( $refund_data );
    adfoin_dokan_send_trigger_data( $saved_records, $posted_data );
}

// Handle Withdraw Request
function adfoin_dokan_handle_withdraw_request( $user_id, $amount, $method ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'newWithdrawRequest' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $withdraw_data = DokanHelper::formatWithdrawRequestData( $user_id, $amount, $method );
    adfoin_dokan_send_trigger_data( $saved_records, $withdraw_data );
}