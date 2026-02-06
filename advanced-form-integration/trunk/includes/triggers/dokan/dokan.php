<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

    $fields    = array();
    $eu_fields = adfoin_dokan_enabled_eu_field_labels();

    switch ( $form_id ) {
        case 'newVendor':
        case 'vendorUpdated':
        case 'vendorDeleted':
            $fields = array_merge(
                adfoin_dokan_vendor_identity_fields(),
                adfoin_dokan_vendor_media_fields(),
                adfoin_dokan_vendor_payment_fields(),
                adfoin_dokan_vendor_address_fields(),
                $eu_fields
            );
            break;
        case 'refundRequest':
        case 'refundApproved':
        case 'refundCancelled':
            $fields = array_merge(
                adfoin_dokan_refund_fields(),
                adfoin_dokan_refund_order_fields(),
                adfoin_dokan_vendor_summary_fields()
            );
            break;
        case 'newWithdrawRequest':
            $fields = array_merge(
                adfoin_dokan_withdraw_fields(),
                adfoin_dokan_vendor_summary_fields()
            );
            break;
        case 'userToVendor':
            $eu_fields = adfoin_dokan_enabled_eu_field_labels( 'user-to-vendor' );
            $fields = array_merge(
                adfoin_dokan_user_to_vendor_fields(),
                $eu_fields
            );
            break;
        default:
            $fields = array();
            break;
    }

    return $fields;
}

// Hooks for Dokan Actions
add_action( 'dokan_before_create_vendor', 'adfoin_dokan_handle_new_vendor', 10, 2 );
add_action( 'dokan_before_update_vendor', 'adfoin_dokan_handle_vendor_update', 10, 2 );
add_action( 'delete_user', 'adfoin_dokan_handle_vendor_delete', 10, 1 );
add_action( 'dokan_refund_request_created', 'adfoin_dokan_handle_refund_request', 10, 1 );
add_action( 'dokan_pro_refund_approved', 'adfoin_dokan_handle_refund_approved', 10, 3 );
add_action( 'dokan_pro_refund_cancelled', 'adfoin_dokan_handle_refund_cancelled', 10, 1 );
add_action( 'dokan_new_seller_created', 'adfoin_dokan_handle_user_to_vendor', 10, 2 );
add_action( 'dokan_after_withdraw_request', 'adfoin_dokan_handle_withdraw_request', 10, 3 );

// Handle New Vendor
function adfoin_dokan_handle_new_vendor( $vendor_id, $data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'newVendor' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( empty( $vendor_id ) || empty( $data ) ) {
        return;
    }

    $posted_data = formatVendorData( $vendor_id, $data );

    if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

// Handle Vendor Update
function adfoin_dokan_handle_vendor_update( $vendor_id, $data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'vendorUpdated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = formatVendorData( $vendor_id, $data );

    if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

// Handle Vendor Delete
function adfoin_dokan_handle_vendor_delete( $vendor_id ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'vendorDeleted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    if ( empty( $vendor_id ) || ! function_exists( 'dokan' ) ) {
        return;
    }

    $user = get_userdata( $vendor_id );

    if ( empty( $user ) || empty( $user->roles ) || ! in_array( 'seller', (array) $user->roles, true ) ) {
        return;
    }

    $vendor = dokan()->vendor->get( $vendor_id );

    if ( ! $vendor || is_wp_error( $vendor ) ) {
        return;
    }

    $vendor_data = formatVendorData( $vendor_id, $vendor->to_array() );

    if ( empty( $vendor_data ) || ! is_array( $vendor_data ) ) {
        return;
    }

    $integration->send( $saved_records, $vendor_data );
}

// Handle Refund Request
function adfoin_dokan_handle_refund_request( $refund ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundRequest' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $refund_data = formatRefundData( $refund );

    if ( empty( $refund_data ) || ! is_array( $refund_data ) ) {
        return;
    }

    $integration->send( $saved_records, $refund_data );
}

// Handle Refund Approved
function adfoin_dokan_handle_refund_approved( $refund_data, $args = null, $vendor_refund = null ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundApproved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = formatRefundData( $refund_data );

    if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

// Handle Refund Cancelled
function adfoin_dokan_handle_refund_cancelled( $refund_data ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'refundCancelled' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = formatRefundData( $refund_data );

    if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

// Handle User to Vendor conversion or registration
function adfoin_dokan_handle_user_to_vendor( $user_id, $shop_info ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'userToVendor' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = formatUserToVendorData( $user_id );

    if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
        return;
    }

    $integration->send( $saved_records, $posted_data );
}

// Handle Withdraw Request
function adfoin_dokan_handle_withdraw_request( $user_id, $amount, $method ) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'dokan', 'newWithdrawRequest' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $withdraw_data = formatWithdrawRequestData( $user_id, $amount, $method );

    if ( empty( $withdraw_data ) || ! is_array( $withdraw_data ) ) {
        return;
    }

    $integration->send( $saved_records, $withdraw_data );
}

function adfoin_dokan_vendor_identity_fields() {
    return array(
        'vendor_id'         => __( 'Vendor ID', 'advanced-form-integration' ),
        'store_name'        => __( 'Store Name', 'advanced-form-integration' ),
        'store_url'         => __( 'Store URL', 'advanced-form-integration' ),
        'shop_url'          => __( 'Shop URL', 'advanced-form-integration' ),
        'user_login'        => __( 'User Login', 'advanced-form-integration' ),
        'user_nicename'     => __( 'User Nicename', 'advanced-form-integration' ),
        'user_email'        => __( 'User Email', 'advanced-form-integration' ),
        'vendor_email'      => __( 'Vendor Email', 'advanced-form-integration' ),
        'email'             => __( 'Email', 'advanced-form-integration' ),
        'phone'             => __( 'Phone', 'advanced-form-integration' ),
        'vendor_phone'      => __( 'Vendor Phone', 'advanced-form-integration' ),
        'first_name'        => __( 'First Name', 'advanced-form-integration' ),
        'vendor_first_name' => __( 'Vendor First Name', 'advanced-form-integration' ),
        'last_name'         => __( 'Last Name', 'advanced-form-integration' ),
        'vendor_last_name'  => __( 'Vendor Last Name', 'advanced-form-integration' ),
        'enabled'           => __( 'Enabled', 'advanced-form-integration' ),
        'trusted'           => __( 'Trusted', 'advanced-form-integration' ),
        'featured'          => __( 'Featured', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_vendor_media_fields() {
    return array(
        'banner'      => __( 'Banner URL', 'advanced-form-integration' ),
        'banner_id'   => __( 'Banner ID', 'advanced-form-integration' ),
        'gravatar'    => __( 'Gravatar URL', 'advanced-form-integration' ),
        'gravatar_id' => __( 'Gravatar ID', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_vendor_payment_fields() {
    return array(
        'ac_name'        => __( 'Account Name', 'advanced-form-integration' ),
        'ac_type'        => __( 'Account Type', 'advanced-form-integration' ),
        'ac_number'      => __( 'Account Number', 'advanced-form-integration' ),
        'bank_name'      => __( 'Bank Name', 'advanced-form-integration' ),
        'bank_addr'      => __( 'Bank Address', 'advanced-form-integration' ),
        'routing_number' => __( 'Routing Number', 'advanced-form-integration' ),
        'iban'           => __( 'IBAN', 'advanced-form-integration' ),
        'swift'          => __( 'SWIFT/BIC', 'advanced-form-integration' ),
        'paypal_email'   => __( 'PayPal Email', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_vendor_address_fields() {
    return array(
        'street_1' => __( 'Street 1', 'advanced-form-integration' ),
        'street_2' => __( 'Street 2', 'advanced-form-integration' ),
        'city'     => __( 'City', 'advanced-form-integration' ),
        'zip'      => __( 'ZIP/Postcode', 'advanced-form-integration' ),
        'state'    => __( 'State/Province', 'advanced-form-integration' ),
        'country'  => __( 'Country', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_refund_fields() {
    return array(
        'refund_id'     => __( 'Refund ID', 'advanced-form-integration' ),
        'refund_amount' => __( 'Refund Amount', 'advanced-form-integration' ),
        'refund_reason' => __( 'Refund Reason', 'advanced-form-integration' ),
        'refund_date'   => __( 'Refund Date', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_refund_order_fields() {
    return array(
        'order_id'                   => __( 'Order ID', 'advanced-form-integration' ),
        'order_status'               => __( 'Order Status', 'advanced-form-integration' ),
        'order_currency'             => __( 'Order Currency', 'advanced-form-integration' ),
        'order_subtotal'             => __( 'Order Subtotal', 'advanced-form-integration' ),
        'order_total'                => __( 'Order Total', 'advanced-form-integration' ),
        'order_total_tax'            => __( 'Order Total Tax', 'advanced-form-integration' ),
        'order_payment_method_title' => __( 'Payment Method Title', 'advanced-form-integration' ),
        'order_transaction_id'       => __( 'Transaction ID', 'advanced-form-integration' ),
        'order_total_refunded'       => __( 'Amount Already Refunded', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_vendor_summary_fields() {
    return array(
        'vendor_id'          => __( 'Vendor ID', 'advanced-form-integration' ),
        'vendor_store_name'  => __( 'Store Name', 'advanced-form-integration' ),
        'vendor_shop_url'    => __( 'Shop URL', 'advanced-form-integration' ),
        'vendor_first_name'  => __( 'Vendor First Name', 'advanced-form-integration' ),
        'vendor_last_name'   => __( 'Vendor Last Name', 'advanced-form-integration' ),
        'vendor_email'       => __( 'Vendor Email', 'advanced-form-integration' ),
        'vendor_phone'       => __( 'Vendor Phone', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_withdraw_fields() {
    return array(
        'withdraw_amount' => __( 'Withdraw Amount', 'advanced-form-integration' ),
        'withdraw_method' => __( 'Withdraw Method', 'advanced-form-integration' ),
    );
}

function adfoin_dokan_user_to_vendor_fields() {
    return array(
        'vendor_id'          => __( 'Vendor ID', 'advanced-form-integration' ),
        'vendor_first_name'  => __( 'Vendor First Name', 'advanced-form-integration' ),
        'vendor_last_name'   => __( 'Vendor Last Name', 'advanced-form-integration' ),
        'store_name'         => __( 'Store Name', 'advanced-form-integration' ),
        'shop_url'           => __( 'Shop URL', 'advanced-form-integration' ),
        'vendor_phone'       => __( 'Vendor Phone', 'advanced-form-integration' ),
        'vendor_email'       => __( 'Vendor Email', 'advanced-form-integration' ),
    );
}

function getEnabledVendorEUFields( $context = '' ) {
    $fields = array();

    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( ! is_plugin_active( 'dokan-pro/dokan-pro.php' ) ) {
        return $fields;
    }

    if ( ! function_exists( 'dokan_pro' ) || ! dokan_pro()->module->is_active( 'germanized' ) ) {
        return $fields;
    }

    if ( ! class_exists( '\WeDevs\DokanPro\Modules\Germanized\Helper' ) ) {
        return $fields;
    }

    if ( 'user-to-vendor' === $context && ! \WeDevs\DokanPro\Modules\Germanized\Helper::is_enabled_on_registration_form() ) {
        return $fields;
    }

    $vendor_enabled_eu_field_keys = array();
    $enabled_eu_fields            = \WeDevs\DokanPro\Modules\Germanized\Helper::is_fields_enabled_for_seller();

    foreach ( (array) $enabled_eu_fields as $key => $enabled ) {
        if ( empty( $enabled ) ) {
            continue;
        }

        $formatted_key = str_replace( 'dokan_', '', $key );

        if ( 'bank_name' === $formatted_key ) {
            $formatted_key = 'eu_bank_name';
        }

        $vendor_enabled_eu_field_keys[] = $formatted_key;
    }

    if ( empty( $vendor_enabled_eu_field_keys ) ) {
        return $fields;
    }

    $available_fields = array(
        array(
            'name'  => 'company_name',
            'type'  => 'text',
            'label' => __( 'Company Name', 'advanced-form-integration' ),
        ),
        array(
            'name'  => 'company_id_number',
            'type'  => 'text',
            'label' => __( 'Company ID/EUID Number', 'advanced-form-integration' ),
        ),
        array(
            'name'  => 'vat_number',
            'type'  => 'text',
            'label' => __( 'VAT/TAX Number', 'advanced-form-integration' ),
        ),
        array(
            'name'  => 'eu_bank_name',
            'type'  => 'text',
            'label' => __( 'Name of Bank', 'advanced-form-integration' ),
        ),
        array(
            'name'  => 'bank_iban',
            'type'  => 'text',
            'label' => __( 'Bank IBAN', 'advanced-form-integration' ),
        ),
    );

    foreach ( $available_fields as $vendor_eu_field ) {
        if ( in_array( $vendor_eu_field['name'], $vendor_enabled_eu_field_keys, true ) ) {
            $fields[] = $vendor_eu_field;
        }
    }

    return $fields;
}

function adfoin_dokan_enabled_eu_field_labels( $context = '' ) {
    $labels      = array();
    $eu_fields   = getEnabledVendorEUFields( $context );

    if ( empty( $eu_fields ) ) {
        return $labels;
    }

    foreach ( $eu_fields as $field ) {
        if ( empty( $field['name'] ) ) {
            continue;
        }

        $label = ! empty( $field['label'] ) ? $field['label'] : ucwords( str_replace( '_', ' ', $field['name'] ) );
        $labels[ $field['name'] ] = $label;
    }

    return $labels;
}

function formatVendorData( $vendor_id, $data ) {
    if ( empty( $vendor_id ) || empty( $data ) ) {
        return false;
    }

    $vendor_data = array();

    foreach ( $data as $key => $item ) {
        if ( 'payment' === $key ) {
            if ( ! empty( $item['bank'] ) ) {
                foreach ( $item['bank'] as $bank_key => $bank_item ) {
                    $vendor_data[ $bank_key ] = $bank_item;
                }
            }

            if ( ! empty( $item['paypal'] ) ) {
                foreach ( $item['paypal'] as $paypal_key => $paypal_item ) {
                    $vendor_data[ 'paypal_' . $paypal_key ] = $paypal_item;
                }
            }
        } elseif ( 'address' === $key ) {
            foreach ( $item as $address_key => $address_item ) {
                $vendor_data[ $address_key ] = $address_item;
            }
        } elseif ( in_array( $key, array( 'social', '_links', 'store_open_close' ), true ) ) {
            continue;
        } else {
            $vendor_data[ $key ] = is_array( $item ) ? implode( ',', $item ) : $item;
        }
    }

    $enabled_eu_fields = getEnabledVendorEUFields();

    if ( ! empty( $enabled_eu_fields ) ) {
        foreach ( $enabled_eu_fields as $eu_field ) {
            if ( empty( $eu_field['name'] ) ) {
                continue;
            }

            if ( 'eu_bank_name' === $eu_field['name'] ) {
                $vendor_data[ $eu_field['name'] ] = isset( $data['bank_name'] ) ? $data['bank_name'] : '';
            } else {
                $vendor_data[ $eu_field['name'] ] = isset( $data[ $eu_field['name'] ] ) ? $data[ $eu_field['name'] ] : '';
            }
        }
    }

    $vendor_data['enabled']   = isset( $data['enabled'] ) ? $data['enabled'] : false;
    $vendor_data['trusted']   = isset( $data['trusted'] ) ? $data['trusted'] : false;
    $vendor_data['featured']  = isset( $data['featured'] ) ? $data['featured'] : false;
    $vendor_data['vendor_id'] = $vendor_id;

    if ( isset( $vendor_data['email'] ) && ! isset( $vendor_data['vendor_email'] ) ) {
        $vendor_data['vendor_email'] = $vendor_data['email'];
    }

    if ( isset( $vendor_data['user_email'] ) && empty( $vendor_data['vendor_email'] ) ) {
        $vendor_data['vendor_email'] = $vendor_data['user_email'];
    }

    if ( isset( $vendor_data['phone'] ) && ! isset( $vendor_data['vendor_phone'] ) ) {
        $vendor_data['vendor_phone'] = $vendor_data['phone'];
    }

    if ( isset( $vendor_data['first_name'] ) && ! isset( $vendor_data['vendor_first_name'] ) ) {
        $vendor_data['vendor_first_name'] = $vendor_data['first_name'];
    }

    if ( isset( $vendor_data['last_name'] ) && ! isset( $vendor_data['vendor_last_name'] ) ) {
        $vendor_data['vendor_last_name'] = $vendor_data['last_name'];
    }

    if ( isset( $vendor_data['shop_url'] ) && ! isset( $vendor_data['store_url'] ) ) {
        $vendor_data['store_url'] = $vendor_data['shop_url'];
    }

    return $vendor_data;
}

function formatRefundData( $refund ) {
    if ( ! $refund || ! function_exists( 'dokan' ) ) {
        return false;
    }

    $order_id  = $refund->get_order_id();
    $vendor_id = $refund->get_seller_id();
    $order     = dokan()->order->get( $order_id );
    $vendor    = dokan()->vendor->get( $vendor_id );

    if ( ! $order || is_wp_error( $order ) || ! $vendor || is_wp_error( $vendor ) ) {
        return false;
    }

    $vendor = $vendor->to_array();

    if ( empty( $vendor ) ) {
        return false;
    }

    $refund_data = array();

    $refund_data['refund_id']     = $refund->get_id();
    $refund_data['refund_amount'] = $refund->get_refund_amount();
    $refund_data['refund_reason'] = $refund->get_refund_reason();
    $refund_data['refund_date']   = $refund->get_date();

    $refund_data['order_id']                   = $order->get_id();
    $refund_data['order_status']               = $order->get_status();
    $refund_data['order_currency']             = $order->get_currency();
    $refund_data['order_subtotal']             = method_exists( $order, 'get_subtotal' ) ? $order->get_subtotal() : '';
    $refund_data['order_total']                = $order->get_total();
    $refund_data['order_total_tax']            = $order->get_total_tax();
    $refund_data['order_payment_method_title'] = $order->get_payment_method_title();
    $refund_data['order_transaction_id']       = $order->get_transaction_id();
    $refund_data['order_total_refunded']       = $order->get_total_refunded();

    $refund_data['vendor_id']         = isset( $vendor['id'] ) ? $vendor['id'] : '';
    $refund_data['vendor_store_name'] = isset( $vendor['store_name'] ) ? $vendor['store_name'] : '';
    $refund_data['vendor_shop_url']   = isset( $vendor['shop_url'] ) ? $vendor['shop_url'] : '';
    $refund_data['vendor_first_name'] = isset( $vendor['first_name'] ) ? $vendor['first_name'] : '';
    $refund_data['vendor_last_name']  = isset( $vendor['last_name'] ) ? $vendor['last_name'] : '';
    $refund_data['vendor_email']      = isset( $vendor['email'] ) ? $vendor['email'] : '';
    $refund_data['vendor_phone']      = isset( $vendor['phone'] ) ? $vendor['phone'] : '';

    return $refund_data;
}

function formatWithdrawRequestData( $user_id, $amount, $method ) {
    if ( empty( $user_id ) || empty( $amount ) || empty( $method ) || ! function_exists( 'dokan' ) ) {
        return false;
    }

    $vendor = dokan()->vendor->get( $user_id );

    if ( ! $vendor || is_wp_error( $vendor ) ) {
        return false;
    }

    $vendor = $vendor->to_array();

    if ( empty( $vendor ) ) {
        return false;
    }

    $withdraw_request_data = array();

    $withdraw_request_data['withdraw_amount']   = $amount;
    $withdraw_request_data['withdraw_method']   = $method;
    $withdraw_request_data['vendor_id']         = isset( $vendor['id'] ) ? $vendor['id'] : '';
    $withdraw_request_data['vendor_store_name'] = isset( $vendor['store_name'] ) ? $vendor['store_name'] : '';
    $withdraw_request_data['vendor_shop_url']   = isset( $vendor['shop_url'] ) ? $vendor['shop_url'] : '';
    $withdraw_request_data['vendor_first_name'] = isset( $vendor['first_name'] ) ? $vendor['first_name'] : '';
    $withdraw_request_data['vendor_last_name']  = isset( $vendor['last_name'] ) ? $vendor['last_name'] : '';
    $withdraw_request_data['vendor_email']      = isset( $vendor['email'] ) ? $vendor['email'] : '';
    $withdraw_request_data['vendor_phone']      = isset( $vendor['phone'] ) ? $vendor['phone'] : '';

    return $withdraw_request_data;
}

function formatUserToVendorData( $user_id ) {
    if ( empty( $user_id ) || ! function_exists( 'dokan' ) ) {
        return false;
    }

    $vendor = dokan()->vendor->get( $user_id );

    if ( ! $vendor || is_wp_error( $vendor ) ) {
        return false;
    }

    $vendor = $vendor->to_array();

    if ( empty( $vendor ) ) {
        return false;
    }

    $user_to_vendor_data = array(
        'vendor_id'          => isset( $vendor['id'] ) ? $vendor['id'] : $user_id,
        'vendor_first_name'  => isset( $vendor['first_name'] ) ? $vendor['first_name'] : '',
        'vendor_last_name'   => isset( $vendor['last_name'] ) ? $vendor['last_name'] : '',
        'store_name'         => isset( $vendor['store_name'] ) ? $vendor['store_name'] : '',
        'shop_url'           => isset( $vendor['shop_url'] ) ? $vendor['shop_url'] : '',
        'vendor_email'       => isset( $vendor['email'] ) ? $vendor['email'] : '',
        'vendor_phone'       => isset( $vendor['phone'] ) ? $vendor['phone'] : '',
    );

    $enabled_eu_fields = getEnabledVendorEUFields( 'user-to-vendor' );

    if ( ! empty( $enabled_eu_fields ) ) {
        foreach ( $enabled_eu_fields as $eu_field ) {
            if ( empty( $eu_field['name'] ) ) {
                continue;
            }

            if ( 'eu_bank_name' === $eu_field['name'] ) {
                $user_to_vendor_data[ $eu_field['name'] ] = isset( $vendor['bank_name'] ) ? $vendor['bank_name'] : '';
            } else {
                $user_to_vendor_data[ $eu_field['name'] ] = isset( $vendor[ $eu_field['name'] ] ) ? $vendor[ $eu_field['name'] ] : '';
            }
        }
    }

    return $user_to_vendor_data;
}
