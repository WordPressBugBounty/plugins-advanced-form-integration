<?php

if ( ! defined( 'ABSPATH' ) ) {
    return;
}

/**
 * Retrieve available Advanced Coupons triggers.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_advancedcoupons_get_forms( $form_provider ) {
    if ( 'advancedcoupons' !== $form_provider ) {
        return;
    }

    return array(
        'coupon_saved'            => __( 'Create or Update Coupon', 'advanced-form-integration' ),
        'store_credit_exceeds'    => __( 'Store Credit Exceeds Limit', 'advanced-form-integration' ),
        'lifetime_credit_exceeds' => __( 'Lifetime Credit Exceeds Limit', 'advanced-form-integration' ),
        'store_credit_received'   => __( 'Receive Store Credit', 'advanced-form-integration' ),
        'store_credit_adjusted'   => __( 'Adjust Store Credit', 'advanced-form-integration' ),
    );
}

/**
 * Describe Advanced Coupons fields per trigger.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_advancedcoupons_get_form_fields( $form_provider, $form_id ) {
    if ( 'advancedcoupons' !== $form_provider ) {
        return;
    }

    $form_id = (string) $form_id;

    if ( 'coupon_saved' === $form_id ) {
        return adfoin_advancedcoupons_coupon_fields();
    }

    $fields = adfoin_advancedcoupons_store_credit_base_fields();

    switch ( $form_id ) {
        case 'store_credit_exceeds':
            $fields['current_balance']               = __( 'Current Store Credit Balance', 'advanced-form-integration' );
            $fields['current_balance_formatted']     = __( 'Current Balance (Formatted)', 'advanced-form-integration' );
            $fields['balance_before_entry']          = __( 'Balance Before Entry', 'advanced-form-integration' );
            $fields['balance_before_entry_formatted']= __( 'Balance Before Entry (Formatted)', 'advanced-form-integration' );
            break;

        case 'lifetime_credit_exceeds':
            $fields['lifetime_credit_total']           = __( 'Lifetime Credit Total', 'advanced-form-integration' );
            $fields['lifetime_credit_total_formatted'] = __( 'Lifetime Credit Total (Formatted)', 'advanced-form-integration' );
            break;

        case 'store_credit_received':
            $fields['credit_received_amount']           = __( 'Credit Received Amount', 'advanced-form-integration' );
            $fields['credit_received_amount_formatted'] = __( 'Credit Received Amount (Formatted)', 'advanced-form-integration' );
            $fields['current_balance']                  = __( 'Current Store Credit Balance', 'advanced-form-integration' );
            $fields['current_balance_formatted']        = __( 'Current Balance (Formatted)', 'advanced-form-integration' );
            break;

        case 'store_credit_adjusted':
            $fields['new_balance']              = __( 'New Store Credit Balance', 'advanced-form-integration' );
            $fields['new_balance_formatted']    = __( 'New Balance (Formatted)', 'advanced-form-integration' );
            $fields['previous_balance']         = __( 'Previous Store Credit Balance', 'advanced-form-integration' );
            $fields['previous_balance_formatted']= __( 'Previous Balance (Formatted)', 'advanced-form-integration' );
            break;
    }

    return $fields;
}

/**
 * Coupon payload fields.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_coupon_fields() {
    return array(
        'coupon_id'                 => __( 'Coupon ID', 'advanced-form-integration' ),
        'code'                      => __( 'Coupon Code', 'advanced-form-integration' ),
        'description'               => __( 'Description', 'advanced-form-integration' ),
        'discount_type'             => __( 'Discount Type', 'advanced-form-integration' ),
        'amount'                    => __( 'Coupon Amount', 'advanced-form-integration' ),
        'amount_formatted'          => __( 'Coupon Amount (Formatted)', 'advanced-form-integration' ),
        'date_created'              => __( 'Date Created', 'advanced-form-integration' ),
        'date_modified'             => __( 'Date Modified', 'advanced-form-integration' ),
        'date_expires'              => __( 'Date Expires', 'advanced-form-integration' ),
        'usage_count'               => __( 'Usage Count', 'advanced-form-integration' ),
        'usage_limit'               => __( 'Usage Limit', 'advanced-form-integration' ),
        'usage_limit_per_user'      => __( 'Usage Limit Per User', 'advanced-form-integration' ),
        'limit_usage_to_x_items'    => __( 'Limit Usage To X Items', 'advanced-form-integration' ),
        'individual_use'            => __( 'Individual Use Only', 'advanced-form-integration' ),
        'free_shipping'             => __( 'Allows Free Shipping', 'advanced-form-integration' ),
        'minimum_amount'            => __( 'Minimum Spend', 'advanced-form-integration' ),
        'maximum_amount'            => __( 'Maximum Spend', 'advanced-form-integration' ),
        'product_ids'               => __( 'Included Product IDs', 'advanced-form-integration' ),
        'excluded_product_ids'      => __( 'Excluded Product IDs', 'advanced-form-integration' ),
        'product_categories'        => __( 'Included Product Category IDs', 'advanced-form-integration' ),
        'excluded_product_categories'=> __( 'Excluded Product Category IDs', 'advanced-form-integration' ),
        'email_restrictions'        => __( 'Allowed Email Addresses', 'advanced-form-integration' ),
        'used_by'                   => __( 'Already Used By', 'advanced-form-integration' ),
        'meta_data'                 => __( 'Meta Data (JSON)', 'advanced-form-integration' ),
    );
}

/**
 * Shared store credit fields.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_store_credit_base_fields() {
    return array(
        'entry_id'               => __( 'Entry ID', 'advanced-form-integration' ),
        'entry_type'             => __( 'Entry Type', 'advanced-form-integration' ),
        'entry_action'           => __( 'Entry Action', 'advanced-form-integration' ),
        'entry_note'             => __( 'Entry Note', 'advanced-form-integration' ),
        'entry_amount'           => __( 'Entry Amount', 'advanced-form-integration' ),
        'entry_amount_formatted' => __( 'Entry Amount (Formatted)', 'advanced-form-integration' ),
        'entry_date'             => __( 'Entry Date (Site Timezone)', 'advanced-form-integration' ),
        'entry_object_id'        => __( 'Related Object ID', 'advanced-form-integration' ),
        'user_id'                => __( 'User ID', 'advanced-form-integration' ),
        'user_email'             => __( 'User Email', 'advanced-form-integration' ),
        'user_login'             => __( 'User Login', 'advanced-form-integration' ),
        'user_display_name'      => __( 'User Display Name', 'advanced-form-integration' ),
        'user_first_name'        => __( 'User First Name', 'advanced-form-integration' ),
        'user_last_name'         => __( 'User Last Name', 'advanced-form-integration' ),
        'user_roles'             => __( 'User Roles', 'advanced-form-integration' ),
    );
}

add_action( 'acfw_after_save_coupon', 'adfoin_advancedcoupons_handle_coupon_saved', 10, 2 );

/**
 * Dispatch coupon save events.
 *
 * @param int       $coupon_id Coupon ID.
 * @param WC_Coupon $coupon    Coupon object.
 */
function adfoin_advancedcoupons_handle_coupon_saved( $coupon_id, $coupon ) {
    if ( ! class_exists( 'WC_Coupon' ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'advancedcoupons', 'coupon_saved' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $payload = adfoin_advancedcoupons_prepare_coupon_payload( $coupon ? $coupon : $coupon_id );

    if ( empty( $payload ) ) {
        return;
    }

    $integration->send( $saved_records, $payload );
}

add_action( 'acfw_create_store_credit_entry', 'adfoin_advancedcoupons_handle_store_credit_entry', 10, 2 );

/**
 * Dispatch store credit events.
 *
 * @param array                                $entry_data Raw entry data inserted by Advanced Coupons.
 * @param ACFWF\Models\Objects\Store_Credit_Entry $entry      Entry object (unused).
 */
function adfoin_advancedcoupons_handle_store_credit_entry( $entry_data, $entry ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    if ( ! function_exists( 'ACFWF' ) || ! is_array( $entry_data ) ) {
        return;
    }

    $integration = new Advanced_Form_Integration_Integration();

    if ( ! empty( $entry_data['type'] ) && 'decrease' !== $entry_data['type'] ) {
        $records = $integration->get_by_trigger( 'advancedcoupons', 'store_credit_exceeds' );

        if ( ! empty( $records ) ) {
            $payload = adfoin_advancedcoupons_prepare_store_credit_balance_payload( $entry_data );

            if ( ! empty( $payload ) ) {
                $integration->send( $records, $payload );
            }
        }

        $records = $integration->get_by_trigger( 'advancedcoupons', 'store_credit_received' );

        if ( ! empty( $records ) ) {
            $payload = adfoin_advancedcoupons_prepare_store_credit_received_payload( $entry_data );

            if ( ! empty( $payload ) ) {
                $integration->send( $records, $payload );
            }
        }

        $records = $integration->get_by_trigger( 'advancedcoupons', 'lifetime_credit_exceeds' );

        if ( ! empty( $records ) ) {
            $payload = adfoin_advancedcoupons_prepare_lifetime_credit_payload( $entry_data );

            if ( ! empty( $payload ) ) {
                $integration->send( $records, $payload );
            }
        }
    }

    if ( ! empty( $entry_data['type'] ) ) {
        $records = $integration->get_by_trigger( 'advancedcoupons', 'store_credit_adjusted' );

        if ( ! empty( $records ) ) {
            $payload = adfoin_advancedcoupons_prepare_store_credit_adjusted_payload( $entry_data );

            if ( ! empty( $payload ) ) {
                $integration->send( $records, $payload );
            }
        }
    }
}

/**
 * Normalize arbitrary value to string.
 *
 * @param mixed $value Value to normalize.
 *
 * @return string
 */
function adfoin_advancedcoupons_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( null === $value ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Prepare user context.
 *
 * @param int $user_id User identifier.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_collect_user_context( $user_id ) {
    $context = array(
        'user_id'          => $user_id ? (string) absint( $user_id ) : '',
        'user_email'       => '',
        'user_login'       => '',
        'user_display_name'=> '',
        'user_first_name'  => '',
        'user_last_name'   => '',
        'user_roles'       => '',
    );

    if ( ! $user_id ) {
        return $context;
    }

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return $context;
    }

    $context['user_email']        = adfoin_advancedcoupons_normalize_value( $user->user_email );
    $context['user_login']        = adfoin_advancedcoupons_normalize_value( $user->user_login );
    $context['user_display_name'] = adfoin_advancedcoupons_normalize_value( $user->display_name );
    $context['user_first_name']   = adfoin_advancedcoupons_normalize_value( $user->first_name );
    $context['user_last_name']    = adfoin_advancedcoupons_normalize_value( $user->last_name );
    $context['user_roles']        = adfoin_advancedcoupons_join_list( $user->roles );

    return $context;
}

/**
 * Convert array values to comma separated string.
 *
 * @param mixed $list List of items.
 *
 * @return string
 */
function adfoin_advancedcoupons_join_list( $list ) {
    if ( empty( $list ) ) {
        return '';
    }

    if ( ! is_array( $list ) ) {
        $list = array( $list );
    }

    $list = array_map(
        static function ( $item ) {
            if ( is_scalar( $item ) || null === $item ) {
                return trim( (string) $item );
            }

            return adfoin_advancedcoupons_normalize_value( $item );
        },
        $list
    );

    $list = array_filter( $list, 'strlen' );

    return implode( ', ', $list );
}

/**
 * Normalize coupon meta data to JSON.
 *
 * @param array $meta_data Coupon meta data objects.
 *
 * @return string
 */
function adfoin_advancedcoupons_format_meta_data( $meta_data ) {
    if ( empty( $meta_data ) ) {
        return '';
    }

    $formatted = array();

    foreach ( $meta_data as $meta ) {
        if ( is_object( $meta ) && method_exists( $meta, 'get_data' ) ) {
            $data = $meta->get_data();
        } elseif ( is_array( $meta ) ) {
            $data = $meta;
        } else {
            continue;
        }

        $formatted[] = array(
            'id'    => isset( $data['id'] ) ? $data['id'] : '',
            'key'   => isset( $data['key'] ) ? $data['key'] : '',
            'value' => $data['value'] ?? '',
        );
    }

    $encoded = wp_json_encode( $formatted );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Format WooCommerce datetime values.
 *
 * @param WC_DateTime|DateTimeInterface|string|null $datetime Datetime value.
 *
 * @return string
 */
function adfoin_advancedcoupons_format_datetime_value( $datetime ) {
    if ( $datetime instanceof WC_DateTime ) {
        return $datetime->date_i18n( 'Y-m-d H:i:s' );
    }

    if ( $datetime instanceof DateTimeInterface ) {
        return wp_date( 'Y-m-d H:i:s', $datetime->getTimestamp() );
    }

    if ( empty( $datetime ) ) {
        return '';
    }

    return (string) $datetime;
}

/**
 * Format store credit entry date string to site timezone.
 *
 * @param string|DateTimeInterface $date Date value.
 *
 * @return string
 */
function adfoin_advancedcoupons_format_entry_date( $date ) {
    if ( $date instanceof DateTimeInterface ) {
        return wp_date( 'Y-m-d H:i:s', $date->getTimestamp() );
    }

    if ( empty( $date ) ) {
        return '';
    }

    $formatted = get_date_from_gmt( (string) $date, 'Y-m-d H:i:s' );

    return $formatted ? $formatted : (string) $date;
}

/**
 * Format numeric value to decimal string.
 *
 * @param float|int|string $value Value to format.
 *
 * @return string
 */
function adfoin_advancedcoupons_format_decimal_value( $value ) {
    $number = is_numeric( $value ) ? (float) $value : 0.0;

    if ( function_exists( 'wc_format_decimal' ) ) {
        $decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
        return wc_format_decimal( $number, $decimals );
    }

    return (string) $number;
}

/**
 * Format numeric value using Advanced Coupons currency filter when available.
 *
 * @param float|int|string $value Value to format.
 *
 * @return string
 */
function adfoin_advancedcoupons_format_currency_value( $value ) {
    $number = is_numeric( $value ) ? (float) $value : 0.0;
    $formatted = apply_filters( 'acfw_filter_amount', $number );

    if ( is_scalar( $formatted ) ) {
        return (string) $formatted;
    }

    return adfoin_advancedcoupons_format_decimal_value( $number );
}

/**
 * Return decimal, formatted, and float representations of an amount.
 *
 * @param mixed $amount Amount value.
 *
 * @return array{raw:string,formatted:string,float:float}
 */
function adfoin_advancedcoupons_prepare_amount_pair( $amount ) {
    $float = is_numeric( $amount ) ? (float) $amount : 0.0;

    return array(
        'raw'       => adfoin_advancedcoupons_format_decimal_value( $float ),
        'formatted' => adfoin_advancedcoupons_format_currency_value( $float ),
        'float'     => $float,
    );
}

/**
 * Prepare coupon payload from WC_Coupon.
 *
 * @param mixed $coupon Coupon identifier or object.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_prepare_coupon_payload( $coupon ) {
    if ( class_exists( 'WC_Coupon' ) && ! $coupon instanceof WC_Coupon ) {
        try {
            $coupon = new WC_Coupon( $coupon );
        } catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        }
    }

    if ( ! $coupon instanceof WC_Coupon ) {
        return array();
    }

    $amount_pair = adfoin_advancedcoupons_prepare_amount_pair( $coupon->get_amount() );

    return array(
        'coupon_id'                 => adfoin_advancedcoupons_normalize_value( $coupon->get_id() ),
        'code'                      => adfoin_advancedcoupons_normalize_value( $coupon->get_code() ),
        'description'               => wp_strip_all_tags( (string) $coupon->get_description() ),
        'discount_type'             => adfoin_advancedcoupons_normalize_value( $coupon->get_discount_type() ),
        'amount'                    => $amount_pair['raw'],
        'amount_formatted'          => $amount_pair['formatted'],
        'date_created'              => adfoin_advancedcoupons_format_datetime_value( $coupon->get_date_created() ),
        'date_modified'             => adfoin_advancedcoupons_format_datetime_value( $coupon->get_date_modified() ),
        'date_expires'              => adfoin_advancedcoupons_format_datetime_value( $coupon->get_date_expires() ),
        'usage_count'               => adfoin_advancedcoupons_normalize_value( $coupon->get_usage_count() ),
        'usage_limit'               => adfoin_advancedcoupons_normalize_value( $coupon->get_usage_limit() ),
        'usage_limit_per_user'      => adfoin_advancedcoupons_normalize_value( $coupon->get_usage_limit_per_user() ),
        'limit_usage_to_x_items'    => adfoin_advancedcoupons_normalize_value( $coupon->get_limit_usage_to_x_items() ),
        'individual_use'            => adfoin_advancedcoupons_normalize_value( $coupon->get_individual_use() ),
        'free_shipping'             => adfoin_advancedcoupons_normalize_value( $coupon->get_free_shipping() ),
        'minimum_amount'            => adfoin_advancedcoupons_format_decimal_value( $coupon->get_minimum_amount() ),
        'maximum_amount'            => adfoin_advancedcoupons_format_decimal_value( $coupon->get_maximum_amount() ),
        'product_ids'               => adfoin_advancedcoupons_join_list( $coupon->get_product_ids() ),
        'excluded_product_ids'      => adfoin_advancedcoupons_join_list( $coupon->get_excluded_product_ids() ),
        'product_categories'        => adfoin_advancedcoupons_join_list( $coupon->get_product_categories() ),
        'excluded_product_categories'=> adfoin_advancedcoupons_join_list( $coupon->get_excluded_product_categories() ),
        'email_restrictions'        => adfoin_advancedcoupons_join_list( $coupon->get_email_restrictions() ),
        'used_by'                   => adfoin_advancedcoupons_join_list( $coupon->get_used_by() ),
        'meta_data'                 => adfoin_advancedcoupons_format_meta_data( $coupon->get_meta_data() ),
    );
}

/**
 * Build base payload for store credit entries.
 *
 * @param array $entry_data Raw entry data.
 *
 * @return array{payload:array<string,string>,amount_raw:float,user_id:int}
 */
function adfoin_advancedcoupons_prepare_store_credit_base_payload( array $entry_data ) {
    $user_id = isset( $entry_data['user_id'] ) ? absint( $entry_data['user_id'] ) : 0;
    $payload = adfoin_advancedcoupons_collect_user_context( $user_id );
    $amount_pair = adfoin_advancedcoupons_prepare_amount_pair( $entry_data['amount'] ?? 0 );

    $payload['entry_id']               = isset( $entry_data['id'] ) ? adfoin_advancedcoupons_normalize_value( $entry_data['id'] ) : '';
    $payload['entry_type']             = isset( $entry_data['type'] ) ? adfoin_advancedcoupons_normalize_value( $entry_data['type'] ) : '';
    $payload['entry_action']           = isset( $entry_data['action'] ) ? adfoin_advancedcoupons_normalize_value( $entry_data['action'] ) : '';
    $payload['entry_note']             = isset( $entry_data['note'] ) ? adfoin_advancedcoupons_normalize_value( $entry_data['note'] ) : '';
    $payload['entry_amount']           = $amount_pair['raw'];
    $payload['entry_amount_formatted'] = $amount_pair['formatted'];
    $payload['entry_date']             = adfoin_advancedcoupons_format_entry_date( $entry_data['date'] ?? '' );
    $payload['entry_object_id']        = isset( $entry_data['object_id'] ) ? adfoin_advancedcoupons_normalize_value( $entry_data['object_id'] ) : '';

    return array(
        'payload'    => $payload,
        'amount_raw' => $amount_pair['float'],
        'user_id'    => $user_id,
    );
}

/**
 * Retrieve customer balance without formatting.
 *
 * @param int $user_id User ID.
 *
 * @return float
 */
function adfoin_advancedcoupons_get_customer_balance_raw( $user_id ) {
    if ( ! function_exists( 'ACFWF' ) || ! isset( ACFWF()->Store_Credits_Calculate ) ) {
        return 0.0;
    }

    $balance = ACFWF()->Store_Credits_Calculate->get_customer_balance( $user_id, true );

    return is_numeric( $balance ) ? (float) $balance : 0.0;
}

/**
 * Calculate lifetime credit total.
 *
 * @param int $user_id User ID.
 *
 * @return float
 */
function adfoin_advancedcoupons_get_lifetime_credit_total_raw( $user_id ) {
    global $wpdb;

    $table = $wpdb->prefix . 'acfw_store_credits';
    $total = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(entry_amount) FROM {$table} WHERE user_id = %d AND entry_type = %s", $user_id, 'increase' ) );

    return is_numeric( $total ) ? (float) $total : 0.0;
}

/**
 * Payload for Store Credit Exceeds trigger.
 *
 * @param array $entry_data Entry data.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_prepare_store_credit_balance_payload( array $entry_data ) {
    $base = adfoin_advancedcoupons_prepare_store_credit_base_payload( $entry_data );

    if ( ! $base['user_id'] ) {
        return array();
    }

    $current_balance = adfoin_advancedcoupons_get_customer_balance_raw( $base['user_id'] );
    $previous_balance = max( 0, $current_balance - $base['amount_raw'] );

    $payload = $base['payload'];
    $payload['current_balance']               = adfoin_advancedcoupons_format_decimal_value( $current_balance );
    $payload['current_balance_formatted']     = adfoin_advancedcoupons_format_currency_value( $current_balance );
    $payload['balance_before_entry']          = adfoin_advancedcoupons_format_decimal_value( $previous_balance );
    $payload['balance_before_entry_formatted']= adfoin_advancedcoupons_format_currency_value( $previous_balance );

    return $payload;
}

/**
 * Payload for Lifetime Credit Exceeds trigger.
 *
 * @param array $entry_data Entry data.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_prepare_lifetime_credit_payload( array $entry_data ) {
    $base = adfoin_advancedcoupons_prepare_store_credit_base_payload( $entry_data );

    if ( ! $base['user_id'] ) {
        return array();
    }

    $total = adfoin_advancedcoupons_get_lifetime_credit_total_raw( $base['user_id'] );

    $payload = $base['payload'];
    $payload['lifetime_credit_total']           = adfoin_advancedcoupons_format_decimal_value( $total );
    $payload['lifetime_credit_total_formatted'] = adfoin_advancedcoupons_format_currency_value( $total );

    return $payload;
}

/**
 * Payload for Receive Store Credit trigger.
 *
 * @param array $entry_data Entry data.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_prepare_store_credit_received_payload( array $entry_data ) {
    $base = adfoin_advancedcoupons_prepare_store_credit_base_payload( $entry_data );

    if ( ! $base['user_id'] ) {
        return array();
    }

    $current_balance = adfoin_advancedcoupons_get_customer_balance_raw( $base['user_id'] );

    $payload = $base['payload'];
    $payload['credit_received_amount']           = adfoin_advancedcoupons_format_decimal_value( $base['amount_raw'] );
    $payload['credit_received_amount_formatted'] = adfoin_advancedcoupons_format_currency_value( $base['amount_raw'] );
    $payload['current_balance']                  = adfoin_advancedcoupons_format_decimal_value( $current_balance );
    $payload['current_balance_formatted']        = adfoin_advancedcoupons_format_currency_value( $current_balance );

    return $payload;
}

/**
 * Payload for Adjust Store Credit trigger.
 *
 * @param array $entry_data Entry data.
 *
 * @return array<string,string>
 */
function adfoin_advancedcoupons_prepare_store_credit_adjusted_payload( array $entry_data ) {
    $base = adfoin_advancedcoupons_prepare_store_credit_base_payload( $entry_data );

    if ( ! $base['user_id'] ) {
        return array();
    }

    $new_balance = adfoin_advancedcoupons_get_customer_balance_raw( $base['user_id'] );
    $entry_type  = isset( $base['payload']['entry_type'] ) ? $base['payload']['entry_type'] : '';
    $previous_balance = ( 'decrease' === $entry_type )
        ? $new_balance + $base['amount_raw']
        : max( 0, $new_balance - $base['amount_raw'] );

    $payload = $base['payload'];
    $payload['new_balance']              = adfoin_advancedcoupons_format_decimal_value( $new_balance );
    $payload['new_balance_formatted']    = adfoin_advancedcoupons_format_currency_value( $new_balance );
    $payload['previous_balance']         = adfoin_advancedcoupons_format_decimal_value( $previous_balance );
    $payload['previous_balance_formatted']= adfoin_advancedcoupons_format_currency_value( $previous_balance );

    return $payload;
}
