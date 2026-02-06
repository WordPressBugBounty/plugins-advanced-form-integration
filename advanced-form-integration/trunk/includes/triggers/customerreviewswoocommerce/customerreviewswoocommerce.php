<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter( 'adfoin_form_providers', 'adfoin_cr_reviews_register_provider' );

/**
 * Register the Customer Reviews for WooCommerce trigger provider when available.
 *
 * @param array $providers Registered providers.
 *
 * @return array
 */
function adfoin_cr_reviews_register_provider( $providers ) {
	if ( ! adfoin_cr_reviews_is_plugin_active() ) {
		unset( $providers['customerreviewswoocommerce'] );

		return $providers;
	}

	$providers['customerreviewswoocommerce'] = __( 'Customer Reviews for WooCommerce', 'advanced-form-integration' );

	return $providers;
}

/**
 * Check whether the Customer Reviews for WooCommerce plugin is active.
 *
 * @return bool
 */
function adfoin_cr_reviews_is_plugin_active() {
	return is_plugin_active( 'customer-reviews-woocommerce/ivole.php' );
}

/**
 * Return supported triggers.
 *
 * @return array<string,string>
 */
function adfoin_cr_reviews_triggers() {
	return array(
		'productReviewSubmitted' => __( 'Product Review Submitted', 'advanced-form-integration' ),
		'shopReviewSubmitted'    => __( 'Store Review Submitted', 'advanced-form-integration' ),
	);
}

/**
 * Provide trigger list for UI.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_cr_reviews_get_forms( $form_provider ) {
	if ( 'customerreviewswoocommerce' !== $form_provider ) {
		return;
	}

	return adfoin_cr_reviews_triggers();
}

/**
 * Provide field map for UI.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return array<string,string>|void
 */
function adfoin_cr_reviews_get_form_fields( $form_provider, $form_id ) {
	if ( 'customerreviewswoocommerce' !== $form_provider ) {
		return;
	}

	return adfoin_cr_reviews_fields();
}

/**
 * Resolve trigger label.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Trigger identifier.
 *
 * @return string|false
 */
function adfoin_cr_reviews_get_form_name( $form_provider, $form_id ) {
	if ( 'customerreviewswoocommerce' !== $form_provider ) {
		return false;
	}

	$triggers = adfoin_cr_reviews_triggers();

	return isset( $triggers[ $form_id ] ) ? $triggers[ $form_id ] : false;
}

/**
 * Field definitions exposed to the mapping UI.
 *
 * @return array<string,string>
 */
function adfoin_cr_reviews_fields() {
	return array(
		'trigger_key'             => __( 'Trigger Key', 'advanced-form-integration' ),
		'trigger_name'            => __( 'Trigger Name', 'advanced-form-integration' ),
		'triggered_at'            => __( 'Triggered At', 'advanced-form-integration' ),
		'review_id'               => __( 'Review ID', 'advanced-form-integration' ),
		'review_post_id'          => __( 'Review Post ID', 'advanced-form-integration' ),
		'review_post_type'        => __( 'Review Post Type', 'advanced-form-integration' ),
		'review_post_title'       => __( 'Review Post Title', 'advanced-form-integration' ),
		'review_type'             => __( 'Review Type', 'advanced-form-integration' ),
		'review_status'           => __( 'Review Status', 'advanced-form-integration' ),
		'review_rating'           => __( 'Review Rating', 'advanced-form-integration' ),
		'review_content'          => __( 'Review Content', 'advanced-form-integration' ),
		'review_excerpt'          => __( 'Review Excerpt', 'advanced-form-integration' ),
		'review_date'             => __( 'Review Date', 'advanced-form-integration' ),
		'review_date_gmt'         => __( 'Review Date (GMT)', 'advanced-form-integration' ),
		'review_permalink'        => __( 'Review Permalink', 'advanced-form-integration' ),
		'review_country'          => __( 'Reviewer Country', 'advanced-form-integration' ),
		'review_verified'         => __( 'Review Verified Owner', 'advanced-form-integration' ),
		'review_author'           => __( 'Reviewer Name', 'advanced-form-integration' ),
		'review_author_email'     => __( 'Reviewer Email', 'advanced-form-integration' ),
		'review_author_url'       => __( 'Reviewer URL', 'advanced-form-integration' ),
		'review_author_ip'        => __( 'Reviewer IP Address', 'advanced-form-integration' ),
		'review_user_id'          => __( 'Reviewer User ID', 'advanced-form-integration' ),
		'review_meta'             => __( 'Review Meta (JSON)', 'advanced-form-integration' ),
		'review_media_urls'       => __( 'Review Media URLs (JSON)', 'advanced-form-integration' ),
		'order_id'                => __( 'Order ID', 'advanced-form-integration' ),
		'order_number'            => __( 'Order Number', 'advanced-form-integration' ),
		'order_status'            => __( 'Order Status', 'advanced-form-integration' ),
		'order_total'             => __( 'Order Total', 'advanced-form-integration' ),
		'order_currency'          => __( 'Order Currency', 'advanced-form-integration' ),
		'order_billing_email'     => __( 'Billing Email', 'advanced-form-integration' ),
		'order_billing_first_name' => __( 'Billing First Name', 'advanced-form-integration' ),
		'order_billing_last_name' => __( 'Billing Last Name', 'advanced-form-integration' ),
		'order_customer_id'       => __( 'Customer User ID', 'advanced-form-integration' ),
		'product_id'              => __( 'Product ID', 'advanced-form-integration' ),
		'product_name'            => __( 'Product Name', 'advanced-form-integration' ),
		'product_sku'             => __( 'Product SKU', 'advanced-form-integration' ),
		'product_permalink'       => __( 'Product Permalink', 'advanced-form-integration' ),
	);
}

add_action( 'plugins_loaded', 'adfoin_cr_reviews_bootstrap', 20 );

/**
 * Register runtime listeners once everything is loaded.
 *
 * @return void
 */
function adfoin_cr_reviews_bootstrap() {
	if ( ! adfoin_cr_reviews_is_plugin_active() ) {
		return;
	}

	add_action( 'comment_post', 'adfoin_cr_reviews_handle_comment_posted', 20, 3 );
}

/**
 * Normalize values for payload storage.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function adfoin_cr_reviews_normalize( $value ) {
	if ( is_bool( $value ) ) {
		return $value ? 'true' : 'false';
	}

	if ( null === $value || '' === $value ) {
		return '';
	}

	if ( is_scalar( $value ) ) {
		return (string) $value;
	}

	$encoded = wp_json_encode( $value );

	return is_string( $encoded ) ? $encoded : '';
}

/**
 * Handle new review submissions.
 *
 * @param int   $comment_id       Comment ID.
 * @param int   $comment_approved Approval status.
 * @param array $commentdata      Comment data array.
 *
 * @return void
 */
function adfoin_cr_reviews_handle_comment_posted( $comment_id, $comment_approved, $commentdata ) {
	$comment = get_comment( $comment_id );

	if ( ! $comment instanceof WP_Comment ) {
		return;
	}

	if ( 'review' !== get_comment_type( $comment ) ) {
		return;
	}

	if ( ! adfoin_cr_reviews_has_plugin_meta( $comment->comment_ID ) ) {
		return;
	}

	$form_id = adfoin_cr_reviews_resolve_form_id( $comment );

	if ( ! $form_id ) {
		return;
	}

	$payload = adfoin_cr_reviews_prepare_payload( $comment, $comment_approved, $form_id );

	if ( empty( $payload ) ) {
		return;
	}

	adfoin_cr_reviews_dispatch( $form_id, $payload );
}

/**
 * Determine if the review carries plugin specific meta.
 *
 * @param int $comment_id Comment ID.
 *
 * @return bool
 */
function adfoin_cr_reviews_has_plugin_meta( $comment_id ) {
	$meta_keys = array(
		'ivole_order',
		'ivole_order_locl',
		'ivole_order_priv',
		'ivole_order_unve',
	);

	foreach ( $meta_keys as $meta_key ) {
		$value = get_comment_meta( $comment_id, $meta_key, true );

		if ( ! empty( $value ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Resolve trigger key based on the comment context.
 *
 * @param WP_Comment $comment Comment object.
 *
 * @return string|false
 */
function adfoin_cr_reviews_resolve_form_id( WP_Comment $comment ) {
	$post_id      = (int) $comment->comment_post_ID;
	$post_type    = $post_id ? get_post_type( $post_id ) : '';
	$shop_page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;

	if ( 'product' === $post_type ) {
		return 'productReviewSubmitted';
	}

	if ( $post_id && $shop_page_id && $shop_page_id === $post_id ) {
		return 'shopReviewSubmitted';
	}

	// Some shop reviews can be stored on non-product posts (custom review pages).
	if ( $shop_page_id && get_post_type( $shop_page_id ) === $post_type ) {
		return 'shopReviewSubmitted';
	}

	// Default to product reviews when stored against products or product variations.
	if ( 'product_variation' === $post_type ) {
		return 'productReviewSubmitted';
	}

	return 'shopReviewSubmitted';
}

/**
 * Collect payload for the dispatched event.
 *
 * @param WP_Comment $comment          Comment object.
 * @param int        $comment_approved Approval status.
 * @param string     $form_id          Trigger key.
 *
 * @return array<string,string>
 */
function adfoin_cr_reviews_prepare_payload( WP_Comment $comment, $comment_approved, $form_id ) {
	$comment_id = (int) $comment->comment_ID;
	$post_id    = (int) $comment->comment_post_ID;
	$post_type  = $post_id ? get_post_type( $post_id ) : '';

	$payload = array(
		'trigger_key'      => adfoin_cr_reviews_normalize( $form_id ),
		'trigger_name'     => adfoin_cr_reviews_normalize( adfoin_cr_reviews_get_form_name( 'customerreviewswoocommerce', $form_id ) ),
		'triggered_at'     => adfoin_cr_reviews_normalize( current_time( 'mysql' ) ),
		'review_id'        => adfoin_cr_reviews_normalize( $comment_id ),
		'review_post_id'   => adfoin_cr_reviews_normalize( $post_id ),
		'review_post_type' => adfoin_cr_reviews_normalize( $post_type ),
		'review_post_title' => adfoin_cr_reviews_normalize( $post_id ? get_the_title( $post_id ) : '' ),
		'review_type'      => adfoin_cr_reviews_normalize( 'productReviewSubmitted' === $form_id ? 'product' : 'store' ),
		'review_status'    => adfoin_cr_reviews_normalize( adfoin_cr_reviews_status_label( $comment_approved, $comment ) ),
		'review_rating'    => adfoin_cr_reviews_normalize( get_comment_meta( $comment_id, 'rating', true ) ),
		'review_content'   => adfoin_cr_reviews_normalize( $comment->comment_content ),
		'review_excerpt'   => adfoin_cr_reviews_normalize( wp_trim_words( $comment->comment_content, 30, '...' ) ),
		'review_date'      => adfoin_cr_reviews_normalize( $comment->comment_date ),
		'review_date_gmt'  => adfoin_cr_reviews_normalize( $comment->comment_date_gmt ),
		'review_permalink' => adfoin_cr_reviews_normalize( get_comment_link( $comment_id ) ),
		'review_country'   => adfoin_cr_reviews_normalize( get_comment_meta( $comment_id, 'ivole_country', true ) ),
		'review_verified'  => adfoin_cr_reviews_normalize( adfoin_cr_reviews_is_verified_owner( $comment ) ),
		'review_author'    => adfoin_cr_reviews_normalize( $comment->comment_author ),
		'review_author_email' => adfoin_cr_reviews_normalize( $comment->comment_author_email ),
		'review_author_url' => adfoin_cr_reviews_normalize( $comment->comment_author_url ),
		'review_author_ip' => adfoin_cr_reviews_normalize( $comment->comment_author_IP ),
		'review_user_id'   => adfoin_cr_reviews_normalize( $comment->user_id ),
		'review_meta'      => adfoin_cr_reviews_normalize( adfoin_cr_reviews_collect_comment_meta( $comment_id ) ),
		'review_media_urls' => adfoin_cr_reviews_normalize( adfoin_cr_reviews_collect_media_urls( $comment_id ) ),
	);

	if ( $post_id && 'product' === $post_type && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post_id );

		if ( is_object( $product ) ) {
			$product_id = method_exists( $product, 'get_id' ) ? $product->get_id() : $post_id;

			$payload['product_id']        = adfoin_cr_reviews_normalize( $product_id );
			$payload['product_name']      = adfoin_cr_reviews_normalize( method_exists( $product, 'get_name' ) ? $product->get_name() : get_the_title( $post_id ) );
			$payload['product_sku']       = adfoin_cr_reviews_normalize( method_exists( $product, 'get_sku' ) ? $product->get_sku() : '' );
			$payload['product_permalink'] = adfoin_cr_reviews_normalize( get_permalink( $product_id ) );
		}
	}

	$order_id = adfoin_cr_reviews_extract_order_id( $comment_id );

	if ( $order_id && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );

		if ( is_object( $order ) ) {
			$payload['order_id']                = adfoin_cr_reviews_normalize( $order_id );
			$payload['order_number']            = adfoin_cr_reviews_normalize( method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order_id );
			$payload['order_status']            = adfoin_cr_reviews_normalize( method_exists( $order, 'get_status' ) ? $order->get_status() : '' );
			$payload['order_total']             = adfoin_cr_reviews_normalize( method_exists( $order, 'get_total' ) ? $order->get_total() : '' );
			$payload['order_currency']          = adfoin_cr_reviews_normalize( method_exists( $order, 'get_currency' ) ? $order->get_currency() : '' );
			$payload['order_billing_email']     = adfoin_cr_reviews_normalize( method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : '' );
			$payload['order_billing_first_name'] = adfoin_cr_reviews_normalize( method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : '' );
			$payload['order_billing_last_name'] = adfoin_cr_reviews_normalize( method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : '' );
			$payload['order_customer_id']       = adfoin_cr_reviews_normalize( method_exists( $order, 'get_user_id' ) ? $order->get_user_id() : '' );
		}
	}

	return $payload;
}

/**
 * Attempt to detect the originating order ID.
 *
 * @param int $comment_id Comment ID.
 *
 * @return int
 */
function adfoin_cr_reviews_extract_order_id( $comment_id ) {
	$meta_keys = array(
		'ivole_order',
		'ivole_order_locl',
		'ivole_order_priv',
		'ivole_order_unve',
	);

	foreach ( $meta_keys as $meta_key ) {
		$order_id = (int) get_comment_meta( $comment_id, $meta_key, true );

		if ( $order_id > 0 ) {
			return $order_id;
		}
	}

	return 0;
}

/**
 * Convert comment approval state to a readable label.
 *
 * @param int|string $comment_approved Approval indicator.
 * @param WP_Comment $comment          Comment object.
 *
 * @return string
 */
function adfoin_cr_reviews_status_label( $comment_approved, WP_Comment $comment ) {
	$status = (string) $comment_approved;

	if ( '1' === $status || 'approve' === $status ) {
		return 'approved';
	}

	if ( '0' === $status || 'hold' === $status ) {
		return 'pending';
	}

	if ( 'spam' === $status ) {
		return 'spam';
	}

	if ( 'trash' === $status || 'post-trashed' === $status ) {
		return 'trash';
	}

	return $comment->comment_approved ? 'approved' : 'pending';
}

/**
 * Determine whether the review belongs to a verified owner.
 *
 * @param WP_Comment $comment Comment object.
 *
 * @return bool
 */
function adfoin_cr_reviews_is_verified_owner( WP_Comment $comment ) {
	if ( class_exists( 'CR_Reviews' ) && method_exists( 'CR_Reviews', 'cr_review_is_from_verified_owner' ) ) {
		return (bool) CR_Reviews::cr_review_is_from_verified_owner( $comment );
	}

	return (bool) get_comment_meta( $comment->comment_ID, 'verified', true );
}

/**
 * Collect comment meta data and flatten simple arrays.
 *
 * @param int $comment_id Comment ID.
 *
 * @return array<string,mixed>
 */
function adfoin_cr_reviews_collect_comment_meta( $comment_id ) {
	$meta       = get_comment_meta( $comment_id );
	$formatted  = array();

	foreach ( $meta as $key => $values ) {
		if ( ! is_array( $values ) ) {
			$formatted[ $key ] = maybe_unserialize( $values );
			continue;
		}

		if ( 1 === count( $values ) ) {
			$formatted[ $key ] = maybe_unserialize( $values[0] );
			continue;
		}

		$formatted[ $key ] = array_map( 'maybe_unserialize', $values );
	}

	return $formatted;
}

/**
 * Collect media URLs from review meta.
 *
 * @param int $comment_id Comment ID.
 *
 * @return array<int,string>
 */
function adfoin_cr_reviews_collect_media_urls( $comment_id ) {
	$meta        = get_comment_meta( $comment_id );
	$media_keys  = array();
	$media_urls  = array();

	foreach ( array_keys( $meta ) as $key ) {
		if ( 0 === strpos( $key, 'ivole_review_image' ) || 0 === strpos( $key, 'ivole_review_video' ) ) {
			$media_keys[] = $key;
		}
	}

	foreach ( $media_keys as $key ) {
		$values = get_comment_meta( $comment_id, $key );

        foreach ( $values as $value ) {
			$maybe = maybe_unserialize( $value );

			if ( is_numeric( $maybe ) ) {
				$url = wp_get_attachment_url( (int) $maybe );

				if ( $url ) {
					$media_urls[] = $url;
				}

				continue;
			}

			if ( is_array( $maybe ) && isset( $maybe['url'] ) ) {
				$media_urls[] = (string) $maybe['url'];
			}
		}
	}

	return array_values( array_unique( array_filter( $media_urls ) ) );
}

/**
 * Dispatch payload to configured actions.
 *
 * @param string              $form_id Trigger key.
 * @param array<string,string> $payload Payload data.
 *
 * @return void
 */
function adfoin_cr_reviews_dispatch( $form_id, array $payload ) {
	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		return;
	}

	$integration   = new Advanced_Form_Integration_Integration();
	$saved_records = $integration->get_by_trigger( 'customerreviewswoocommerce', $form_id );

	if ( empty( $saved_records ) ) {
		return;
	}

	if ( '1' == get_option( 'adfoin_general_settings_utm' ) && function_exists( 'adfoin_capture_utm_and_url_values' ) ) {
		$payload = array_merge( $payload, adfoin_capture_utm_and_url_values() );
	}

	foreach ( $saved_records as $record ) {
		$action_provider = $record['action_provider'];

		if ( function_exists( "adfoin_{$action_provider}_send_data" ) ) {
			call_user_func( "adfoin_{$action_provider}_send_data", $record, $payload );
		}
	}
}
