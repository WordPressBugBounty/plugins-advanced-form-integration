<?php

function adfoin_edd_get_forms( $form_provider ) {
    if( $form_provider != 'edd' ) {
        return;
    }

    $forms = array(
        'user_purchased_product' => __( 'User Purchased a Product', 'advanced-form-integration' )
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

    $product_names   = array();
    $product_ids     = array();
    $order_item_ids  = array();
    $order_discounts = 0;

    foreach ( $cart_items as $item ) {
        $product_names[]  = isset( $item['name'] ) ? $item['name'] : '';
        $product_ids[]    = isset( $item['id'] ) ? $item['id'] : '';
        $order_item_ids[] = isset( $item['order_item_id'] ) ? $item['order_item_id'] : '';
        $order_discounts += isset( $item['discount'] ) ? floatval( $item['discount'] ) : 0;
    }

    // Build final payload once, using concatenated product fields for multi-item orders.
    $final_data = array(
        'user_id'         => $payment->user_id,
        'first_name'      => $payment->first_name,
        'last_name'       => $payment->last_name,
        'user_email'      => $payment->email,
        'product_name'    => implode( ', ', array_filter( $product_names ) ),
        'product_id'      => implode( ', ', array_filter( $product_ids ) ),
        'order_item_id'   => implode( ', ', array_filter( $order_item_ids ) ),
        'discount_codes'  => $payment->discounts,
        'order_discounts' => $order_discounts,
        'order_subtotal'  => $payment->subtotal,
        'order_total'     => $payment->total,
        'order_tax'       => $payment->tax,
        'payment_method'  => $payment->gateway,
        'status'          => $payment->status,
    );

    $payment_meta = edd_get_payment_meta( $payment_id, '' );

    if ( ! empty( $payment_meta ) ) {
        foreach ( $payment_meta as $key => $value ) {
            if( isset( $value[0] ) ) {
                $final_data[ $key ] = $value[0];
            }
        }
    }

    $integration->send( $saved_records, $final_data );
}
