<?php

function adfoin_cartflows_get_forms( $form_provider ) {
    if ( 'cartflows' !== $form_provider ) {
        return;
    }

    if ( ! post_type_exists( 'cartflows_step' ) ) {
        return array();
    }

    $query_args = array(
        'post_type'      => 'cartflows_step',
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'   => 'wcf-step-type',
                'value' => 'checkout',
            ),
            array(
                'key'   => '_wcf_step_type',
                'value' => 'checkout',
            ),
            array(
                'key'   => 'wcf_step_type',
                'value' => 'checkout',
            ),
        ),
    );

    $steps = get_posts( $query_args );

    if ( empty( $steps ) ) {
        $fallback_args = $query_args;
        unset( $fallback_args['meta_query'] );
        $steps = get_posts( $fallback_args );
    }

    if ( empty( $steps ) ) {
        return array();
    }

    $forms = array();

    foreach ( $steps as $step ) {
        $title = get_the_title( $step->ID );
        if ( empty( $title ) ) {
            $title = sprintf( __( 'Checkout Step #%d', 'advanced-form-integration' ), $step->ID );
        }
        $forms[ (string) $step->ID ] = $title;
    }

    return $forms;
}

function adfoin_cartflows_get_form_fields( $form_provider, $form_id ) {
    if ( 'cartflows' !== $form_provider ) {
        return;
    }

    if ( empty( $form_id ) ) {
        return array();
    }

    $fields = array();

    if ( function_exists( 'adfoin_get_woocommerce_order_fields' ) ) {
        $fields = (array) adfoin_get_woocommerce_order_fields();
    }

    $fields['flow_id']        = __( 'Flow ID', 'advanced-form-integration' );
    $fields['flow_title']     = __( 'Flow Title', 'advanced-form-integration' );
    $fields['checkout_id']    = __( 'Checkout Step ID', 'advanced-form-integration' );
    $fields['checkout_title'] = __( 'Checkout Step Title', 'advanced-form-integration' );
    $fields['submission_date'] = __( 'Submission Date', 'advanced-form-integration' );

    $special_tags = adfoin_get_special_tags();

    if ( is_array( $fields ) && is_array( $special_tags ) ) {
        $fields = $fields + $special_tags;
    }

    return $fields;
}

function adfoin_cartflows_get_form_name( $form_provider, $form_id ) {
    if ( 'cartflows' !== $form_provider ) {
        return;
    }

    $step = get_post( $form_id );

    if ( $step && 'cartflows_step' === $step->post_type ) {
        return get_the_title( $step );
    }

    return '';
}

add_action( 'woocommerce_checkout_order_created', 'adfoin_cartflows_handle_order_created', 20, 1 );

function adfoin_cartflows_handle_order_created( $order ) {
    if ( ! class_exists( 'WC_Order' ) ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        $order = wc_get_order( $order );
    }

    if ( ! $order ) {
        return;
    }

    $checkout_id = $order->get_meta( '_wcf_checkout_id' );

    if ( ! $checkout_id ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'cartflows', (string) $checkout_id );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_cartflows_prepare_order_payload( $order, $saved_records );

    $special_tags = adfoin_get_special_tags_values( null );

    if ( is_array( $special_tags ) ) {
        $posted_data = array_merge( $posted_data, $special_tags );
    }

    $integration->send( $saved_records, $posted_data );
}

function adfoin_cartflows_prepare_order_payload( $order, $saved_records ) {
    $posted_data = array();

    $fields     = function_exists( 'adfoin_get_woocommerce_order_fields' ) ? adfoin_get_woocommerce_order_fields() : array();
    $field_keys = array_keys( $fields );

    foreach ( $field_keys as $key ) {
        $method = 'get_' . $key;

        if ( method_exists( $order, $method ) ) {
            $value = call_user_func( array( $order, $method ) );
            $posted_data[ $key ] = $value;

            if ( in_array( $key, array( 'date_created', 'date_modified', 'date_completed' ), true ) ) {
                $date = $order->$method();
                $posted_data[ $key ] = $date ? date( 'Y-m-d H:i:s', $date->getOffsetTimestamp() ) : '';
            }

            if ( 'tax_totals' === $key ) {
                $posted_data['tax_totals'] = wp_json_encode( $order->get_tax_totals() );
            }

            if ( 'shipping_methods' === $key ) {
                $shipping_methods = $order->get_shipping_methods();
                $methods_data     = array();

                if ( is_array( $shipping_methods ) ) {
                    foreach ( $shipping_methods as $method ) {
                        $methods_data[] = $method->get_data();
                    }
                }

                $posted_data['shipping_methods'] = wp_json_encode( $methods_data );
            }

            if ( 'taxes' === $key ) {
                $taxes      = $order->get_taxes();
                $taxes_data = array();

                if ( is_array( $taxes ) ) {
                    foreach ( $taxes as $tax ) {
                        $taxes_data[] = $tax->get_data();
                    }
                }

                $posted_data['taxes'] = wp_json_encode( $taxes_data );
            }
        }
    }

    if ( isset( $posted_data['billing_state'] ) && $posted_data['billing_state'] && function_exists( 'adfoin_woocommerce_get_full_state' ) ) {
        $posted_data['billing_state_full'] = adfoin_woocommerce_get_full_state( $order, 'billing' );
    }

    if ( isset( $posted_data['shipping_state'] ) && $posted_data['shipping_state'] && function_exists( 'adfoin_woocommerce_get_full_state' ) ) {
        $posted_data['shipping_state_full'] = adfoin_woocommerce_get_full_state( $order, 'shipping' );
    }

    $item_data = array();

    $items = $order->get_items();
    if ( is_array( $items ) ) {
        $line = 1;

        foreach ( $items as $item ) {
            $item_data[ $line ]['items_id']             = $item->get_product_id();
            $item_data[ $line ]['items_name']           = $item->get_name();
            $item_data[ $line ]['items_variation_id']   = $item->get_variation_id();
            $item_data[ $line ]['items_quantity']       = $item->get_quantity();
            $item_data[ $line ]['items_subtotal']       = $item->get_subtotal();
            $item_data[ $line ]['items_subtotal_tax']   = $item->get_subtotal_tax();
            $item_data[ $line ]['items_subtotal_with_tax'] = $item->get_subtotal() + $item->get_subtotal_tax();
            $item_data[ $line ]['items_total_tax']      = $item->get_total_tax();
            $item_data[ $line ]['items_total_with_tax'] = $item->get_total_tax() + $item->get_total();
            $item_data[ $line ]['items_total']          = $item->get_total();
            $item_data[ $line ]['items_number_in_cart'] = $line;
            $item_data[ $line ]['items']                = wp_json_encode( $item->get_data() );

            $product = $item->get_variation_id() ? wc_get_product( $item->get_variation_id() ) : wc_get_product( $item->get_product_id() );

            if ( $product ) {
                $item_data[ $line ]['items_sku']          = $product->get_sku();
                $item_data[ $line ]['items_price']        = $product->get_price();
                $item_data[ $line ]['items_sale_price']   = $product->get_sale_price();
                $item_data[ $line ]['items_regular_price'] = $product->get_regular_price();
            }

            $variation_id = $item->get_variation_id();

            if ( $variation_id && class_exists( 'WC_Product_Variation' ) ) {
                $variation   = new WC_Product_Variation( $variation_id );
                $attributes  = $variation->get_attributes();
                $item_data[ $line ]['items_attributes'] = implode( ',', $attributes );
            }

            if ( function_exists( 'adfoin_woocommerce_get_meta_tags' ) ) {
                $item_metas = adfoin_woocommerce_get_meta_tags( $saved_records, 'item' );

                foreach ( $item_metas as $item_meta ) {
                    $meta_tag             = str_replace( 'itemmeta_', '', $item_meta );
                    $meta_value           = wc_get_order_item_meta( (int) $item->get_id(), $meta_tag );
                    $item_data[ $line ][ $item_meta ] = $meta_value;
                }
            }

            $line++;
        }
    }

    $extra_data = maybe_unserialize( get_option( 'adfoin_wc_checkout_fields' ) );

    if ( is_array( $extra_data ) ) {
        $posted_data = $posted_data + $extra_data;
        update_option( 'adfoin_wc_checkout_fields', maybe_serialize( array() ) );
    }

    if ( isset( $posted_data['id'] ) ) {
        $meta_data = get_post_meta( $posted_data['id'] );

        if ( $meta_data ) {
            foreach ( $meta_data as $metakey => $metavalue ) {
                $posted_data[ $metakey ] = isset( $metavalue[0] ) ? $metavalue[0] : '';
            }
        }
    }

    if ( '1' === get_option( 'adfoin_general_settings_utm' ) ) {
        $utm_data    = adfoin_capture_utm_and_url_values();
        $posted_data = $posted_data + $utm_data;
    }

    if ( ! empty( $item_data ) ) {
        $merged_items = array();
        $item_keys    = array_keys( array_merge( ...$item_data ) );

        foreach ( $item_data as $item ) {
            foreach ( $item_keys as $key ) {
                if ( ! isset( $merged_items[ $key ] ) ) {
                    $merged_items[ $key ] = array();
                }

                $merged_items[ $key ][] = isset( $item[ $key ] ) ? $item[ $key ] : '';
            }
        }

        $posted_data = $posted_data + $merged_items;
    }

    if ( function_exists( 'adfoin_woocommerce_get_meta_tags' ) ) {
        $user_metas = adfoin_woocommerce_get_meta_tags( $saved_records, 'user' );

        if ( is_array( $user_metas ) && ! empty( $user_metas ) ) {
            $user_id = $order->get_user_id();

            foreach ( $user_metas as $user_meta ) {
                $meta_tag                     = str_replace( 'usermeta_', '', $user_meta );
                $meta_value                   = get_user_meta( (int) $user_id, $meta_tag, true );
                $posted_data[ $user_meta ] = $meta_value;
            }
        }
    }

    $flow_id     = $order->get_meta( '_wcf_flow_id' );
    $checkout_id = $order->get_meta( '_wcf_checkout_id' );

    $posted_data['flow_id']        = $flow_id;
    $posted_data['flow_title']     = $flow_id ? get_the_title( $flow_id ) : '';
    $posted_data['checkout_id']    = $checkout_id;
    $posted_data['checkout_title'] = $checkout_id ? get_the_title( $checkout_id ) : '';
    $posted_data['submission_date'] = current_time( 'mysql' );

    return $posted_data;
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_cartflows_trigger_fields' );
}

function adfoin_cartflows_trigger_fields() {
    ?>
    <tr v-if="trigger.formProviderId == 'cartflows'" is="cartflows" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></tr>
    <?php
}

add_action( 'adfoin_trigger_templates', 'adfoin_cartflows_trigger_template' );

function adfoin_cartflows_trigger_template() {
    ?>
    <script type="text/template" id="cartflows-template">
        <tr valign="top" class="alternate" v-if="trigger.formId">
            <td scope="row-title">
                <label for="tablecell">
                    <span class="dashicons dashicons-info-outline"></span>
                </label>
            </td>
            <td>
                <p>
                    <?php esc_attr_e( 'The basic AFI plugin supports standard WooCommerce order fields for CartFlows checkouts.', 'advanced-form-integration' ); ?>
                </p>
            </td>
        </tr>
    </script>
    <?php
}
