<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Solid Security (formerly iThemes Security / Better WP Security) trigger.
 *
 * The hooks this file originally used — `itsec_lockout_host` and
 * `itsec_lockout_username` — do not exist anywhere in the plugin's current
 * source (confirmed against core/lockout.php and core/lib/lockout/*.php on
 * the plugins.svn.wordpress.org/better-wp-security mirror); they appear to
 * have been invented rather than verified. The real, confirmed hook for a
 * lockout escalating to a permanent IP ban is:
 *
 *     do_action( 'itsec_new_banned_ip', $ip, $context );
 *
 * fired from core/lockout.php's blacklist_ip(), where $context is either
 * null or an iThemesSecurity\Lib\Lockout\Context instance. Only the
 * Username_Context subclass exposes a public get_username() — Host_Context
 * carries no extra data beyond the IP itself — so the username is
 * best-effort and often empty (most bans originate from IP/firewall rules,
 * not a specific username lockout).
 *
 * @link https://plugins.trac.wordpress.org/browser/better-wp-security/trunk/core/lockout.php
 * @link https://plugins.trac.wordpress.org/browser/better-wp-security/trunk/core/lib/lockout/class-username-context.php
 */

add_action( 'plugins_loaded', 'adfoin_solidsecurity_register_hooks', 20 );

/**
 * Register hooks if Solid Security is active.
 */
function adfoin_solidsecurity_register_hooks() {
	if ( ! class_exists( 'ITSEC_Core' ) ) {
		return;
	}

	add_action( 'itsec_new_banned_ip', 'adfoin_solidsecurity_handle_banned_ip', 10, 2 );
}

/**
 * Get forms (triggers).
 *
 * @param string $form_provider Provider name.
 *
 * @return array|void
 */
function adfoin_solidsecurity_get_forms( $form_provider ) {
	if ( 'solidsecurity' !== $form_provider ) {
		return;
	}

	return array(
		'ipBanned' => __( 'IP Banned', 'advanced-form-integration' ),
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
function adfoin_solidsecurity_get_form_fields( $form_provider, $form_id ) {
	if ( 'solidsecurity' !== $form_provider || 'ipBanned' !== $form_id ) {
		return;
	}

	return array(
		'ip_address'     => __( 'Banned IP Address', 'advanced-form-integration' ),
		'lockout_module' => __( 'Lockout Module (what triggered the ban)', 'advanced-form-integration' ),
		'username'       => __( 'Username (only when the ban came from a username lockout)', 'advanced-form-integration' ),
		'ban_time'       => __( 'Ban Time', 'advanced-form-integration' ),
	);
}

/**
 * Handle a new permanent IP ban.
 *
 * @param string $ip      The banned IP address.
 * @param object|null $context Lockout\Context instance, or null.
 */
function adfoin_solidsecurity_handle_banned_ip( $ip, $context ) {
	$integration = new Advanced_Form_Integration_Integration();
	$records     = $integration->get_by_trigger( 'solidsecurity', 'ipBanned' );

	if ( empty( $records ) ) {
		return;
	}

	$lockout_module = ( $context && method_exists( $context, 'get_lockout_module' ) ) ? $context->get_lockout_module() : '';
	// Only Username_Context exposes get_username(); Host_Context and other
	// module contexts don't, so this is best-effort and often blank.
	$username = ( $context && method_exists( $context, 'get_username' ) ) ? $context->get_username() : '';

	$posted_data = array(
		'ip_address'     => $ip,
		'lockout_module' => $lockout_module,
		'username'       => $username,
		'ban_time'       => current_time( 'mysql' ),
	);

	adfoin_dispatch_integrations( $records, $posted_data );
}
