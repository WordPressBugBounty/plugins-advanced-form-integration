<?php

function adfoin_edd_get_forms( $form_provider ) {
    if( $form_provider != 'edd' ) {
        return;
    }

    $forms = array(
        'user_purchased_product' => __( 'User Purchased a Product', 'advanced-form-integration' ),
        // 'edd_complete_purchase' => __( 'Product Purchase with a Discount Code', 'advanced-form-integration' )
    );

    return $forms;
}

function adfoin_edd_get_form_fields( $form_provider, $form_id ) {
    if( $form_provider != 'edd' ) {
        return;
    }

    $fields = array();

    if( in_array( $form_id, array( 'user_purchased_product' ) ) ) {
        $fields['user_id'] = __( 'User ID', 'advanced-form-integration' );
        $fields['first_name'] = __( 'First Name', 'advanced-form-integration' );
        $fields['last_name'] = __( 'Last Name', 'advanced-form-integration' );
        $fields['user_email'] = __( 'User Email', 'advanced-form-integration' );
        $fields['product_name'] = __( 'Product Name', 'advanced-form-integration' );
        $fields['product_id'] = __( 'Product ID', 'advanced-form-integration' );
        $fields['order_item_id'] = __( 'Order Item ID', 'advanced-form-integration' );
        $fields['discount_codes'] = __( 'Discount Codes', 'advanced-form-integration' );
        $fields['order_discounts'] = __( 'Order Discounts', 'advanced-form-integration' );
        $fields['order_subtotal'] = __( 'Order Subtotal', 'advanced-form-integration' );
        $fields['order_total'] = __( 'Order Total', 'advanced-form-integration' );
        $fields['order_tax'] = __( 'Order Tax', 'advanced-form-integration' );
        $fields['payment_method'] = __( 'Payment Method', 'advanced-form-integration' );
    }

    return $fields;
}

function adfoin_edd_get_userdata( $user_id ) {
    $user_data = array();
    $user      = get_userdata($user_id);

    if ($user) {
        $user_data['user_id']    = $user->ID;
        $user_data['first_name'] = $user->first_name;
        $user_data['last_name']  = $user->last_name;
        $user_data['email'] = $user->user_email;
    }

    return $user_data;
}

add_action( 'edd_complete_purchase', 'adfoin_edd_complete_purchase', 10, 1 );



function adfoin_edd_complete_purchase( $payment_id ) {

    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'edd', 'user_purchased_product' );

    if ( empty( $saved_records ) ) {
        return;
    }
    
    $cart_items = edd_get_payment_meta_cart_details( $payment_id );

    if ( ! class_exists( '\EDD_Payment' ) || empty( $cart_items ) ) {
        return;
    }

    $payment = new EDD_Payment( $payment_id );

    foreach ( $cart_items as $item ) {
        $final_data = array(
            'user_id'         => $payment->user_id,
            'first_name'      => $payment->first_name,
            'last_name'       => $payment->last_name,
            'user_email'      => $payment->email,
            'product_name'    => $item['name'],
            'product_id'      => $item['id'],
            'order_item_id'   => $item['order_item_id'],
            'discount_codes'  => $payment->discounts,
            'order_discounts' => $item['discount'],
            'order_subtotal'  => $payment->subtotal,
            'order_total'     => $payment->total,
            'order_tax'       => $payment->tax,
            'payment_method'  => $payment->gateway,
            'status'          => $payment->status,
        );
    }

    $integration->send( $saved_records, $final_data );
}