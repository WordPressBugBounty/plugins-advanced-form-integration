<?php

/**
 * Funnel Builder triggers.
 */

// Get Funnel Builder triggers.
function adfoin_funnelbuilder_get_forms( $form_provider ) {
    if ( $form_provider !== 'funnelbuilder' ) {
        return;
    }

    return array(
        'stepViewed'    => __( 'Funnel Step Viewed', 'advanced-form-integration' ),
        'stepConverted' => __( 'Funnel Step Converted', 'advanced-form-integration' ),
    );
}

// Get Funnel Builder fields.
function adfoin_funnelbuilder_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'funnelbuilder' ) {
        return;
    }

    $fields = array(
        'event_type'   => __( 'Event Type', 'advanced-form-integration' ),
        'funnel_id'    => __( 'Funnel ID', 'advanced-form-integration' ),
        'funnel_title' => __( 'Funnel Title', 'advanced-form-integration' ),
        'step_id'      => __( 'Step ID', 'advanced-form-integration' ),
        'step_type'    => __( 'Step Type', 'advanced-form-integration' ),
        'step_title'   => __( 'Step Title', 'advanced-form-integration' ),
        'step_url'     => __( 'Step URL', 'advanced-form-integration' ),
        'session_key'  => __( 'Session Key', 'advanced-form-integration' ),
        'raw_step'     => __( 'Raw Step Data (JSON)', 'advanced-form-integration' ),
    );

    if ( 'stepConverted' === $form_id ) {
        $fields['order_id'] = __( 'Order ID', 'advanced-form-integration' );
    }

    return $fields;
}

/**
 * Prepare payload for trigger delivery.
 *
 * @param int   $step_id    Funnel step ID.
 * @param array $step_data  Step data supplied by Funnel Builder.
 * @param string $event_type Event type identifier.
 *
 * @return array
 */
function adfoin_funnelbuilder_prepare_payload( $step_id, $step_data, $event_type ) {
    $step_id   = absint( $step_id );
    $step_post = $step_id ? get_post( $step_id ) : null;
    $step_type = isset( $step_data['type'] ) ? $step_data['type'] : '';

    if ( empty( $step_type ) && $step_post instanceof WP_Post ) {
        $step_type = adfoin_funnelbuilder_map_post_type_to_step_type( $step_post->post_type );
    }

    $funnel_id = '';
    if ( isset( $step_data['funnel_id'] ) ) {
        $funnel_id = $step_data['funnel_id'];
    } elseif ( $step_id ) {
        $funnel_meta = get_post_meta( $step_id, '_bwf_in_funnel', true );
        if ( ! empty( $funnel_meta ) ) {
            $funnel_id = $funnel_meta;
        }
    }

    $funnel_title = '';
    if ( ! empty( $funnel_id ) && class_exists( 'WFFN_Funnel' ) ) {
        $funnel = new WFFN_Funnel( absint( $funnel_id ) );
        if ( method_exists( $funnel, 'get_title' ) ) {
            $funnel_title = $funnel->get_title();
        }
    }

    $session_key = '';
    if ( function_exists( 'WFFN_Core' ) ) {
        $core = WFFN_Core();
        if ( isset( $core->data ) && method_exists( $core->data, 'get_transient_key' ) ) {
            $session_key = $core->data->get_transient_key();
        }
    }

    $order_id = '';
    if ( isset( $step_data['order_id'] ) ) {
        $order_id = $step_data['order_id'];
    } elseif ( isset( $step_data['order'] ) && class_exists( 'WC_Order' ) && $step_data['order'] instanceof WC_Order ) {
        $order_id = $step_data['order']->get_id();
    }

    $step_title = '';
    $step_url   = '';

    if ( $step_post instanceof WP_Post ) {
        $step_title = $step_post->post_title;
        $step_url   = get_permalink( $step_post );
    } elseif ( $step_id ) {
        $step_title = get_the_title( $step_id );
        $step_url   = get_permalink( $step_id );
    }

    return array(
        'event_type'   => $event_type,
        'funnel_id'    => $funnel_id,
        'funnel_title' => $funnel_title,
        'step_id'      => $step_id,
        'step_type'    => $step_type,
        'step_title'   => $step_title,
        'step_url'     => $step_url,
        'session_key'  => $session_key,
        'order_id'     => $order_id,
        'raw_step'     => wp_json_encode( adfoin_funnelbuilder_sanitize_raw_step_data( $step_data ) ),
    );
}

/**
 * Map internal post type to Funnel Builder step slug.
 *
 * @param string $post_type Post type.
 *
 * @return string
 */
function adfoin_funnelbuilder_map_post_type_to_step_type( $post_type ) {
    $map = array(
        'wffn_lp'    => 'landing',
        'wffn_ty'    => 'wc_thankyou',
        'wffn_oty'   => 'optin_ty',
        'wffn_op'    => 'optin',
        'cartflows_step' => 'cartflows_step',
    );

    return isset( $map[ $post_type ] ) ? $map[ $post_type ] : $post_type;
}

/**
 * Sanitize raw step data before encoding.
 *
 * @param mixed $data Data supplied by Funnel Builder.
 *
 * @return array
 */
function adfoin_funnelbuilder_sanitize_raw_step_data( $data ) {
    if ( empty( $data ) ) {
        return array();
    }

    if ( is_object( $data ) ) {
        $data = get_object_vars( $data );
    }

    if ( ! is_array( $data ) ) {
        return array( 'value' => $data );
    }

    $sanitized = array();
    foreach ( $data as $key => $value ) {
        if ( is_scalar( $value ) || is_null( $value ) ) {
            $sanitized[ $key ] = $value;
            continue;
        }

        if ( is_object( $value ) ) {
            $sanitized[ $key ] = adfoin_funnelbuilder_sanitize_raw_step_data( get_object_vars( $value ) );
            continue;
        }

        if ( is_array( $value ) ) {
            $sanitized[ $key ] = adfoin_funnelbuilder_sanitize_raw_step_data( $value );
        }
    }

    return $sanitized;
}

/**
 * Dispatch data to saved integrations.
 *
 * @param string $trigger Trigger key.
 * @param array  $payload Data payload.
 *
 * @return void
 */
function adfoin_funnelbuilder_send( $trigger, $payload ) {
    $integration = new Advanced_Form_Integration_Integration();
    $records     = $integration->get_by_trigger( 'funnelbuilder', $trigger );

    if ( empty( $records ) ) {
        return;
    }

    $integration->send( $records, $payload );
}

// Handle step viewed events.
add_action( 'wffn_event_step_viewed', 'adfoin_funnelbuilder_handle_step_viewed', 10, 2 );

/**
 * Process step viewed events.
 *
 * @param int   $step_id   Step ID.
 * @param array $step_data Step data.
 *
 * @return void
 */
function adfoin_funnelbuilder_handle_step_viewed( $step_id, $step_data ) {
    if ( empty( $step_id ) ) {
        return;
    }

    $payload = adfoin_funnelbuilder_prepare_payload( $step_id, (array) $step_data, 'viewed' );
    adfoin_funnelbuilder_send( 'stepViewed', $payload );
}

// Handle step converted events.
add_action( 'wffn_event_step_converted', 'adfoin_funnelbuilder_handle_step_converted', 10, 2 );

/**
 * Process step converted events.
 *
 * @param int   $step_id   Step ID.
 * @param array $step_data Step data.
 *
 * @return void
 */
function adfoin_funnelbuilder_handle_step_converted( $step_id, $step_data ) {
    if ( empty( $step_id ) ) {
        return;
    }

    $payload = adfoin_funnelbuilder_prepare_payload( $step_id, (array) $step_data, 'converted' );
    adfoin_funnelbuilder_send( 'stepConverted', $payload );
}
