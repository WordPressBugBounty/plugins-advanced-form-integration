<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'adfoin_custom_hook_register_all_hooks' );

/**
 * Queries all saved integrations and dynamically adds actions for custom hooks.
 */
function adfoin_custom_hook_register_all_hooks() {
	$integration = new Advanced_Form_Integration_Integration();

	// Note: This assumes a method exists to get all integrations for a provider.
	// The implementation details of this method are in the core of the plugin.
	$saved_records = $integration->get_by_trigger( 'custom_hook' );

	if ( empty( $saved_records ) ) {
		return;
	}

	$registered_hooks = array();

	// Get a unique list of all hook names that have been configured.
	foreach ( $saved_records as $record ) {
		if ( ! empty( $record['form_id'] ) ) {
			$registered_hooks[] = $record['form_id'];
		}
	}

	$unique_hooks = array_unique( $registered_hooks );

	// Add a generic listener to each unique hook.
	// The priority is set to 99 to run late and capture final values.
	// The accepted arguments count is high to capture everything.
	foreach ( $unique_hooks as $hook_name ) {
		if ( ! empty( $hook_name ) ) {
			add_action( $hook_name, 'adfoin_handle_custom_hook', 99, 99 );
		}
	}
}

/**
 * A generic handler for all custom hooks.
 */
function adfoin_handle_custom_hook() {
	$hook_name = current_action();
	$args      = func_get_args();

	$integration = new Advanced_Form_Integration_Integration();

	// Find records that are specifically listening for this hook.
	$records = $integration->get_by_trigger( 'custom_hook', $hook_name );

	if ( empty( $records ) ) {
		return;
	}

	$payload = adfoin_prepare_custom_hook_payload( $hook_name, $args );

	if ( empty( $payload ) ) {
		return;
	}

	$integration->send( $records, $payload );
}

/**
 * Prepare a generic payload from the hook data.
 *
 * @param string $hook_name The name of the action hook.
 * @param array  $args      The arguments passed to the hook.
 *
 * @return array
 */
function adfoin_prepare_custom_hook_payload( $hook_name, $args ) {
	$payload = array(
		'hook_name'          => $hook_name,
		'argument_count'     => count( $args ),
		'all_arguments_json' => wp_json_encode( $args ),
	);

	// Add the first 10 arguments as individual fields for easy mapping.
	for ( $i = 0; $i < 10; $i++ ) {
		$key = 'argument_' . ( $i + 1 );
		if ( isset( $args[ $i ] ) ) {
			if ( is_scalar( $args[ $i ] ) ) {
				$payload[ $key ] = (string) $args[ $i ];
			} else {
				$payload[ $key ] = wp_json_encode( $args[ $i ] );
			}
		} else {
			$payload[ $key ] = '';
		}
	}

	return $payload;
}

/**
 * Define the fields available for mapping.
 *
 * @param string $form_provider The provider name.
 * @param string $form_id       The hook name.
 *
 * @return array|void
 */
function adfoin_custom_hook_get_form_fields( $form_provider, $form_id ) {
	if ( 'custom_hook' !== $form_provider ) {
		return;
	}

	// Since fields are dynamic, we provide generic keys for the user to map.
	return array(
		'hook_name'          => __( 'Hook Name', 'advanced-form-integration' ),
		'argument_count'     => __( 'Argument Count', 'advanced-form-integration' ),
		'argument_1'         => __( 'Argument 1', 'advanced-form-integration' ),
		'argument_2'         => __( 'Argument 2', 'advanced-form-integration' ),
		'argument_3'         => __( 'Argument 3', 'advanced-form-integration' ),
		'argument_4'         => __( 'Argument 4', 'advanced-form-integration' ),
		'argument_5'         => __( 'Argument 5', 'advanced-form-integration' ),
		'argument_6'         => __( 'Argument 6', 'advanced-form-integration' ),
		'argument_7'         => __( 'Argument 7', 'advanced-form-integration' ),
		'argument_8'         => __( 'Argument 8', 'advanced-form-integration' ),
		'argument_9'         => __( 'Argument 9', 'advanced-form-integration' ),
		'argument_10'        => __( 'Argument 10', 'advanced-form-integration' ),
		'all_arguments_json' => __( 'All Arguments (JSON)', 'advanced-form-integration' ),
	);
}

/**
 * Register this provider.
 * The UI for this will be custom, allowing text input for the form_id (hook name).
 *
 * @param array $providers The existing providers.
 *
 * @return array
 */
function adfoin_custom_hook_add_provider( $providers ) {
	$providers['custom_hook'] = __( 'Custom Hook', 'advanced-form-integration' );
	return $providers;
}
add_filter( 'adfoin_form_providers', 'adfoin_custom_hook_add_provider' );
