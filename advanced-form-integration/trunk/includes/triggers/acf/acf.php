<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'adfoin_acf_register_hooks', 20 );

/**
 * Register hooks if ACF is active.
 */
function adfoin_acf_register_hooks() {
	if ( ! function_exists( 'acf' ) ) {
		return;
	}

	add_action( 'acf/save_post', 'adfoin_acf_handle_post_saved', 20 );
}

/**
 * Get forms (triggers).
 *
 * @param string $form_provider Provider name.
 *
 * @return array|void
 */
function adfoin_acf_get_forms( $form_provider ) {
	if ( 'acf' !== $form_provider ) {
		return;
	}

	return array(
		'acf_post_updated' => __( 'Post with ACF Fields Updated', 'advanced-form-integration' ),
	);
}

/**
 * Get form fields.
 *
 * @param string $form_provider Provider name.
 * @param string $form_id       Form ID.
 *
 * @return array|void
 */
function adfoin_acf_get_form_fields( $form_provider, $form_id ) {
	if ( 'acf' !== $form_provider ) {
		return;
	}

	if ( 'acf_post_updated' === $form_id ) {
		return array(
			'post_id'         => __( 'Post ID', 'advanced-form-integration' ),
			'post_title'      => __( 'Post Title', 'advanced-form-integration' ),
			'post_type'       => __( 'Post Type', 'advanced-form-integration' ),
			'post_status'     => __( 'Post Status', 'advanced-form-integration' ),
			'permalink'       => __( 'Permalink', 'advanced-form-integration' ),
			'acf_fields_json' => __( 'All ACF Fields (JSON)', 'advanced-form-integration' ),
		);
	}
}

/**
 * Handle ACF post save.
 *
 * @param int|string $post_id Post ID.
 */
function adfoin_acf_handle_post_saved( $post_id ) {
	// Options pages have non-integer IDs.
	if ( is_numeric( $post_id ) ) {
		$post = get_post( (int) $post_id );
		if ( ! $post ) {
			return;
		}
	}

	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'acf', 'acf_post_updated' );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_acf_prepare_payload( $post_id );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

/**
 * Prepare payload for ACF trigger.
 *
 * @param int|string $post_id Post ID.
 *
 * @return array
 */
function adfoin_acf_prepare_payload( $post_id ) {
	$payload = array();

	// Handle posts, pages, and CPTs.
	if ( is_numeric( $post_id ) ) {
		$post = get_post( (int) $post_id );
		if ( $post ) {
			$payload['post_id']     = $post->ID;
			$payload['post_title']  = $post->post_title;
			$payload['post_type']   = $post->post_type;
			$payload['post_status'] = $post->post_status;
			$payload['permalink']   = get_permalink( $post );
		}
	} else {
		// Handle options pages, etc.
		$payload['post_id'] = $post_id;
	}

	$acf_fields = get_fields( $post_id );

	if ( empty( $acf_fields ) || ! is_array( $acf_fields ) ) {
		$payload['acf_fields_json'] = '[]';
		return $payload;
	}

	$payload['acf_fields_json'] = wp_json_encode( $acf_fields );

	// Add each ACF field to the payload individually for easier mapping.
	foreach ( $acf_fields as $key => $value ) {
		$payload_key = 'acf_field_' . $key;
		if ( is_array( $value ) || is_object( $value ) ) {
			$payload[ $payload_key ] = wp_json_encode( $value );
		} else {
			$payload[ $payload_key ] = $value;
		}
	}

	return $payload;
}
