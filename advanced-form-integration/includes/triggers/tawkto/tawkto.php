<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADFOIN_TAWKTO_PRECHAT_FORM', 'prechat' );
define( 'ADFOIN_TAWKTO_OFFLINE_FORM', 'offline' );

/**
 * Determine whether the Tawk.to Live Chat plugin is active.
 *
 * @return bool
 */
function adfoin_tawkto_is_active() {
	if ( class_exists( 'TawkTo' ) ) {
		return true;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 'tawkto-live-chat/tawkto.php' );
}

/**
 * Return available Tawk.to triggers.
 *
 * @param string $form_provider Provider slug.
 * @return array<string,string>|void
 */
function adfoin_tawkto_get_forms( $form_provider ) {
	if ( 'tawkto' !== $form_provider ) {
		return;
	}

	return array(
		ADFOIN_TAWKTO_PRECHAT_FORM => __( 'Pre-Chat Form Submission', 'advanced-form-integration' ),
		ADFOIN_TAWKTO_OFFLINE_FORM => __( 'Offline Message Submission', 'advanced-form-integration' ),
	);
}

/**
 * Return the field map for a Tawk.to trigger.
 *
 * @param string $form_provider Provider slug.
 * @param string $form_id       Trigger key.
 * @return array<string,string>|void
 */
function adfoin_tawkto_get_form_fields( $form_provider, $form_id ) {
	if ( 'tawkto' !== $form_provider ) {
		return;
	}

	$forms = adfoin_tawkto_get_forms( $form_provider );

	if ( empty( $forms[ $form_id ] ) ) {
		return array();
	}

	$fields = array(
		'event_type'       => __( 'Event Type', 'advanced-form-integration' ),
		'event_time'       => __( 'Event Time', 'advanced-form-integration' ),
		'name'             => __( 'Visitor Name', 'advanced-form-integration' ),
		'email'            => __( 'Email', 'advanced-form-integration' ),
		'phone'            => __( 'Phone', 'advanced-form-integration' ),
		'message'          => __( 'Message', 'advanced-form-integration' ),
		'subject'          => __( 'Subject', 'advanced-form-integration' ),
		'department'       => __( 'Department', 'advanced-form-integration' ),
		'tags'             => __( 'Tags (comma separated)', 'advanced-form-integration' ),
		'agent'            => __( 'Assigned Agent', 'advanced-form-integration' ),
		'chat_id'          => __( 'Chat ID', 'advanced-form-integration' ),
		'conversation_id'  => __( 'Conversation ID', 'advanced-form-integration' ),
		'widget_id'        => __( 'Widget ID', 'advanced-form-integration' ),
		'property_id'      => __( 'Property ID', 'advanced-form-integration' ),
		'visitor_id'       => __( 'Visitor ID', 'advanced-form-integration' ),
		'ip_address'       => __( 'IP Address', 'advanced-form-integration' ),
		'language'         => __( 'Language', 'advanced-form-integration' ),
		'browser'          => __( 'Browser', 'advanced-form-integration' ),
		'timezone'         => __( 'Timezone', 'advanced-form-integration' ),
		'platform'         => __( 'Platform', 'advanced-form-integration' ),
		'page_url'         => __( 'Page URL', 'advanced-form-integration' ),
		'page_title'       => __( 'Page Title', 'advanced-form-integration' ),
		'referrer'         => __( 'Referrer', 'advanced-form-integration' ),
		'custom_fields'    => __( 'Custom Fields (JSON)', 'advanced-form-integration' ),
		'raw_payload'      => __( 'Raw Payload (JSON)', 'advanced-form-integration' ),
	);

	$special_tags = adfoin_get_special_tags();

	if ( is_array( $special_tags ) ) {
		$fields = $fields + $special_tags;
	}

	return $fields;
}

/**
 * Return the display name for a trigger.
 *
 * @param string $form_provider Provider slug.
 * @param string $form_id       Trigger key.
 * @return string|void
 */
function adfoin_tawkto_get_form_name( $form_provider, $form_id ) {
	$forms = adfoin_tawkto_get_forms( $form_provider );

	if ( empty( $forms[ $form_id ] ) ) {
		return;
	}

	return $forms[ $form_id ];
}

add_action( 'plugins_loaded', 'adfoin_tawkto_bootstrap', 20 );

/**
 * Register runtime hooks for the trigger when needed.
 *
 * @return void
 */
function adfoin_tawkto_bootstrap() {
	if ( ! adfoin_tawkto_is_active() ) {
		return;
	}

	if ( ! adfoin_tawkto_should_listen() ) {
		return;
	}

	add_action( 'wp_footer', 'adfoin_tawkto_print_listener_script', 40 );
	add_action( 'wp_ajax_adfoin_tawkto_capture', 'adfoin_tawkto_capture_submission' );
	add_action( 'wp_ajax_nopriv_adfoin_tawkto_capture', 'adfoin_tawkto_capture_submission' );
}

/**
 * Determine whether we need to listen for events on this request.
 *
 * @return bool
 */
function adfoin_tawkto_should_listen() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		$cached = false;
		return $cached;
	}

	$cached = ! empty( adfoin_tawkto_get_records() );

	return $cached;
}

/**
 * Retrieve saved integrations for the provider.
 *
 * @param string $form_id Optional form key.
 * @return array<int,array<string,mixed>>
 */
function adfoin_tawkto_get_records( $form_id = '' ) {
	static $cache = array();

	$key = $form_id ? $form_id : '_all';

	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
		$cache[ $key ] = array();
		return $cache[ $key ];
	}

	$integration = new Advanced_Form_Integration_Integration();

	if ( '' === $form_id ) {
		$records      = $integration->get_by_trigger( 'tawkto' );
		$cache['_all'] = $records;
		return $cache['_all'];
	}

	$records        = $integration->get_by_trigger( 'tawkto', $form_id );
	$cache[ $form_id ] = $records;

	return $cache[ $form_id ];
}

/**
 * Output the inline script that relays Tawk.to events back to WordPress.
 *
 * @return void
 */
function adfoin_tawkto_print_listener_script() {
	if ( is_admin() ) {
		return;
	}

	$records = adfoin_tawkto_get_records();

	if ( empty( $records ) ) {
		return;
	}

	$enabled_forms = array();

	foreach ( $records as $record ) {
		if ( empty( $record['form_id'] ) ) {
			continue;
		}

		$enabled_forms[ $record['form_id'] ] = true;
	}

	if ( empty( $enabled_forms ) ) {
		return;
	}

	$settings = array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'adfoin-tawkto-capture' ),
		'forms'    => array_keys( $enabled_forms ),
	);

	adfoin_tawkto_print_bootstrap_script();

	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		wp_print_inline_script_tag(
			'(function ( window ) {' .
				'adfoinTawktoInit(window, ' . wp_json_encode( $settings ) . ');' .
			'}( window ));',
			array(
				'id' => 'adfoin-tawkto-init',
			)
		);
	} else {
		echo '<script id="adfoin-tawkto-init">(function ( window ) {';
		echo 'adfoinTawktoInit(window,' . wp_json_encode( $settings ) . ');';
		echo '}( window ));</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Print the bootstrap helper once.
 *
 * @return void
 */
function adfoin_tawkto_print_bootstrap_script() {
	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;

	$script = <<<'JS'
if ( typeof window.adfoinTawktoInit !== 'function' ) {
	window.adfoinTawktoInit = function ( scope, settings ) {
		if ( ! settings || ! settings.forms || ! settings.forms.length ) {
			return;
		}

		var listeners = {
			prechat: 'onPrechatSubmit',
			offline: 'onOfflineSubmit'
		};

		var targetEvents = settings.forms.filter( function ( item ) {
			return listeners[ item ];
		} );

		if ( ! targetEvents.length ) {
			return;
		}

		var attach = function () {
			var api = scope.Tawk_API = scope.Tawk_API || {};
			api.__adfoinHandlers = api.__adfoinHandlers || {};

			var wrapHandler = function ( property, eventKey ) {
				if ( api.__adfoinHandlers[ property ] ) {
					return;
				}

				var previous = typeof api[ property ] === 'function' ? api[ property ] : null;

				api[ property ] = function () {
					try {
						var payload = arguments.length ? arguments[0] : {};
						adfoinTawktoDispatch( eventKey, payload, settings );
					} catch ( err ) {
						// Intentionally ignored.
					}

					if ( previous ) {
						try {
							return previous.apply( this, arguments );
						} catch ( err ) {
							// Intentionally ignored.
						}
					}
				};

				api.__adfoinHandlers[ property ] = true;
			};

			targetEvents.forEach( function ( key ) {
				wrapHandler( listeners[ key ], key );
			} );
		};

		var retries = 20;

		var attempt = function () {
			if ( scope.Tawk_API ) {
				attach();
				return;
			}

			retries -= 1;

			if ( retries <= 0 ) {
				return;
			}

			scope.setTimeout( attempt, 500 );
		};

		attempt();
	};
}

if ( typeof window.adfoinTawktoDispatch !== 'function' ) {
	window.adfoinTawktoDispatch = function ( eventType, payload, settings ) {
		var data = {
			event_type: eventType,
			data: payload || {},
			nonce: settings.nonce || ''
		};

		var url = settings.ajaxUrl + '?action=adfoin_tawkto_capture&nonce=' + encodeURIComponent( settings.nonce || '' );
		var body = JSON.stringify( data );

		if ( window.navigator && typeof window.navigator.sendBeacon === 'function' ) {
			try {
				var queued = window.navigator.sendBeacon( url, new Blob( [ body ], { type: 'application/json' } ) );
				if ( queued ) {
					return;
				}
			} catch ( err ) {
				// Fall back to fetch.
			}
		}

		if ( window.fetch ) {
			window.fetch( url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json'
				},
				body: body
			} ).catch( function () {} );
			return;
		}

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', url, true );
		xhr.setRequestHeader( 'Content-Type', 'application/json' );
		xhr.send( body );
	};
}
JS;

	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		wp_print_inline_script_tag( $script, array( 'id' => 'adfoin-tawkto-bootstrap' ) );
	} else {
		echo '<script id="adfoin-tawkto-bootstrap">' . $script . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Handle incoming AJAX payloads from the listener script.
 *
 * @return void
 */
function adfoin_tawkto_capture_submission() {
	$raw_payload = file_get_contents( 'php://input' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$decoded     = json_decode( $raw_payload, true );

	if ( ! is_array( $decoded ) ) {
		$decoded = array();
	}

	$nonce = '';

	if ( isset( $_GET['nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $decoded['nonce'] ) ) {
		$nonce = sanitize_text_field( $decoded['nonce'] );
	}

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'adfoin-tawkto-capture' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid request.', 'advanced-form-integration' ),
			),
			403
		);
	}

	$event_type = isset( $decoded['event_type'] ) ? sanitize_key( $decoded['event_type'] ) : '';

	$forms = adfoin_tawkto_get_forms( 'tawkto' );

	if ( empty( $event_type ) || empty( $forms[ $event_type ] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Unknown event.', 'advanced-form-integration' ),
			),
			400
		);
	}

	$records = adfoin_tawkto_get_records( $event_type );

	if ( empty( $records ) ) {
		wp_send_json_success(
			array(
				'dispatched' => 0,
			)
		);
	}

	$data        = isset( $decoded['data'] ) && is_array( $decoded['data'] ) ? $decoded['data'] : array();
	$posted_data = adfoin_tawkto_normalize_payload( $event_type, $data );

	$integration = new Advanced_Form_Integration_Integration();
	$integration->send( $records, $posted_data );

	wp_send_json_success(
		array(
			'dispatched' => count( $records ),
		)
	);
}

/**
 * Normalize the Tawk.to payload into a flat array for AFI.
 *
 * @param string               $event_type Trigger key.
 * @param array<string,mixed>  $data       Raw payload.
 * @return array<string,string>
 */
function adfoin_tawkto_normalize_payload( $event_type, $data ) {
	$normalized = array(
		'event_type'  => $event_type,
		'event_time'  => current_time( 'mysql' ),
		'raw_payload' => wp_json_encode( $data ),
	);

	$normalized['name']            = adfoin_tawkto_extract_value( $data, array( 'name', 'visitor.name' ) );
	$normalized['email']           = adfoin_tawkto_extract_value( $data, array( 'email', 'visitor.email' ) );
	$normalized['phone']           = adfoin_tawkto_extract_value( $data, array( 'phone', 'visitor.phone' ) );
	$normalized['message']         = adfoin_tawkto_extract_value( $data, array( 'message', 'question', 'visitor.message' ) );
	$normalized['subject']         = adfoin_tawkto_extract_value( $data, array( 'subject', 'message.subject' ) );
	$normalized['department']      = adfoin_tawkto_extract_value( $data, array( 'department', 'department.name' ) );
	$normalized['tags']            = adfoin_tawkto_format_list( adfoin_tawkto_extract_any( $data, array( 'tags' ) ) );
	$normalized['agent']           = adfoin_tawkto_extract_value( $data, array( 'agent', 'agent.name' ) );
	$normalized['chat_id']         = adfoin_tawkto_extract_value( $data, array( 'chatId' ) );
	$normalized['conversation_id'] = adfoin_tawkto_extract_value( $data, array( 'conversationId', 'conversation.id' ) );
	$normalized['widget_id']       = adfoin_tawkto_extract_value( $data, array( 'widgetId' ) );
	$normalized['property_id']     = adfoin_tawkto_extract_value( $data, array( 'pageId', 'propertyId' ) );
	$normalized['visitor_id']      = adfoin_tawkto_extract_value( $data, array( 'visitorId', 'visitor.id' ) );
	$normalized['ip_address']      = adfoin_tawkto_extract_value( $data, array( 'ip', 'visitor.ipAddress' ) );
	$normalized['language']        = adfoin_tawkto_extract_value( $data, array( 'language', 'visitor.language' ) );
	$normalized['browser']         = adfoin_tawkto_extract_value( $data, array( 'browser', 'visitor.browser' ) );
	$normalized['timezone']        = adfoin_tawkto_extract_value( $data, array( 'timezone', 'visitor.timezone' ) );
	$normalized['platform']        = adfoin_tawkto_extract_value( $data, array( 'platform', 'visitor.platform' ) );
	$normalized['page_url']        = adfoin_tawkto_extract_value( $data, array( 'page.url', 'origin.url' ) );
	$normalized['page_title']      = adfoin_tawkto_extract_value( $data, array( 'page.title' ) );
	$normalized['referrer']        = adfoin_tawkto_extract_value( $data, array( 'page.referrer', 'referrer' ) );

	$collection_keys = array( 'answers', 'fields', 'questions', 'details', 'formFields', 'form_data' );

	$normalized['name']  = $normalized['name'] ? $normalized['name'] : adfoin_tawkto_extract_from_collection( $data, $collection_keys, array( 'name', 'full_name' ) );
	$normalized['email'] = $normalized['email'] ? $normalized['email'] : adfoin_tawkto_extract_from_collection( $data, $collection_keys, array( 'email', 'e_mail' ) );
	$normalized['phone'] = $normalized['phone'] ? $normalized['phone'] : adfoin_tawkto_extract_from_collection( $data, $collection_keys, array( 'phone', 'tel', 'phone_number' ) );
	$normalized['message'] = $normalized['message'] ? $normalized['message'] : adfoin_tawkto_extract_from_collection( $data, $collection_keys, array( 'message', 'question', 'description', 'comment' ) );

	$custom_fields = adfoin_tawkto_collect_custom_fields(
		$data,
		$collection_keys,
		array(
			$normalized['name'],
			$normalized['email'],
			$normalized['phone'],
			$normalized['message'],
			$normalized['subject'],
		)
	);

	$normalized['custom_fields'] = wp_json_encode( $custom_fields );

	foreach ( $normalized as $key => $value ) {
		$normalized[ $key ] = is_string( $value ) ? $value : '';
	}

	return $normalized;
}

/**
 * Extract the first non-empty string from the provided paths.
 *
 * @param array<string,mixed> $data  Payload.
 * @param array<int,string>   $paths Paths to inspect.
 * @return string
 */
function adfoin_tawkto_extract_value( $data, $paths ) {
	foreach ( $paths as $path ) {
		$value = adfoin_tawkto_get_from_path( $data, $path );

		if ( '' !== $value && null !== $value ) {
			return adfoin_tawkto_clean_value( $value );
		}
	}

	return '';
}

/**
 * Retrieve data from the payload using dot notation.
 *
 * @param array<string,mixed> $data Payload.
 * @param string              $path Dot separated path.
 * @return mixed
 */
function adfoin_tawkto_get_from_path( $data, $path ) {
	if ( empty( $path ) ) {
		return null;
	}

	$segments = explode( '.', $path );
	$current  = $data;

	foreach ( $segments as $segment ) {
		if ( is_array( $current ) && array_key_exists( $segment, $current ) ) {
			$current = $current[ $segment ];
			continue;
		}

		if ( is_array( $current ) && is_numeric( $segment ) && array_key_exists( (int) $segment, $current ) ) {
			$current = $current[ (int) $segment ];
			continue;
		}

		return null;
	}

	return $current;
}

/**
 * Extract a top-level array value.
 *
 * @param array<string,mixed> $data Payload.
 * @param array<int,string>   $keys Keys to test.
 * @return mixed
 */
function adfoin_tawkto_extract_any( $data, $keys ) {
	foreach ( $keys as $key ) {
		if ( isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}
	}

	return null;
}

/**
 * Convert a mixed value into a comma separated list.
 *
 * @param mixed $value Value to format.
 * @return string
 */
function adfoin_tawkto_format_list( $value ) {
	if ( empty( $value ) ) {
		return '';
	}

	if ( is_string( $value ) ) {
		return sanitize_text_field( $value );
	}

	if ( is_array( $value ) ) {
		$prepared = array();
		foreach ( $value as $entry ) {
			$prepared[] = adfoin_tawkto_clean_value( $entry );
		}

		$prepared = array_filter( $prepared, 'strlen' );
		return implode( ', ', $prepared );
	}

	return '';
}

/**
 * Attempt to extract a value from collection entries that have labels.
 *
 * @param array<string,mixed> $data            Payload.
 * @param array<int,string>   $collection_keys Keys that may contain labelled entries.
 * @param array<int,string>   $matches         Slugs to match against.
 * @return string
 */
function adfoin_tawkto_extract_from_collection( $data, $collection_keys, $matches ) {
	foreach ( $collection_keys as $collection_key ) {
		if ( empty( $data[ $collection_key ] ) || ! is_array( $data[ $collection_key ] ) ) {
			continue;
		}

		foreach ( $data[ $collection_key ] as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$label = '';

			if ( isset( $entry['label'] ) ) {
				$label = $entry['label'];
			} elseif ( isset( $entry['name'] ) ) {
				$label = $entry['name'];
			} elseif ( isset( $entry['question'] ) ) {
				$label = $entry['question'];
			}

			if ( '' === $label ) {
				continue;
			}

			$slug = sanitize_title( $label );

			if ( in_array( $slug, $matches, true ) ) {
				$value = '';

				if ( isset( $entry['value'] ) ) {
					$value = $entry['value'];
				} elseif ( isset( $entry['answer'] ) ) {
					$value = $entry['answer'];
				} elseif ( isset( $entry['text'] ) ) {
					$value = $entry['text'];
				}

				return adfoin_tawkto_clean_value( $value );
			}
		}
	}

	return '';
}

/**
 * Collect custom fields into a label => value map.
 *
 * @param array<string,mixed> $data            Payload.
 * @param array<int,string>   $collection_keys Keys to inspect.
 * @param array<int,string>   $exclude_values  Values to omit.
 * @return array<string,string>
 */
function adfoin_tawkto_collect_custom_fields( $data, $collection_keys, $exclude_values ) {
	$collected = array();

	foreach ( $collection_keys as $collection_key ) {
		if ( empty( $data[ $collection_key ] ) || ! is_array( $data[ $collection_key ] ) ) {
			continue;
		}

		foreach ( $data[ $collection_key ] as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$label = '';

			if ( isset( $entry['label'] ) ) {
				$label = $entry['label'];
			} elseif ( isset( $entry['name'] ) ) {
				$label = $entry['name'];
			} elseif ( isset( $entry['question'] ) ) {
				$label = $entry['question'];
			}

			if ( '' === $label ) {
				continue;
			}

			$value = '';

			if ( isset( $entry['value'] ) ) {
				$value = $entry['value'];
			} elseif ( isset( $entry['answer'] ) ) {
				$value = $entry['answer'];
			} elseif ( isset( $entry['text'] ) ) {
				$value = $entry['text'];
			}

			$value = adfoin_tawkto_clean_value( $value );

			if ( '' === $value ) {
				continue;
			}

			if ( in_array( $value, $exclude_values, true ) ) {
				continue;
			}

			$collected[ $label ] = $value;
		}
	}

	return $collected;
}

/**
 * Normalize a mixed value into a string.
 *
 * @param mixed $value Value to sanitize.
 * @return string
 */
function adfoin_tawkto_clean_value( $value ) {
	if ( is_string( $value ) ) {
		return sanitize_textarea_field( $value );
	}

	if ( is_numeric( $value ) ) {
		return (string) $value;
	}

	if ( is_bool( $value ) ) {
		return $value ? '1' : '0';
	}

	if ( is_array( $value ) ) {
		$prepared = array();
		foreach ( $value as $item ) {
			$prepared[] = adfoin_tawkto_clean_value( $item );
		}

		$prepared = array_filter( $prepared, 'strlen' );

		return implode( ', ', $prepared );
	}

	return '';
}
