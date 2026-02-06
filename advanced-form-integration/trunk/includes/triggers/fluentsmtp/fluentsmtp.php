<?php

/**
 * FluentSMTP trigger registration.
 */
function adfoin_fluentsmtp_get_forms( $form_provider ) {
    if ( $form_provider !== 'fluentsmtp' ) {
        return;
    }

    return array(
        'emailSent'   => __( 'Email sent successfully (FluentSMTP)', 'advanced-form-integration' ),
        'emailFailed' => __( 'Email delivery failed (FluentSMTP)', 'advanced-form-integration' ),
    );
}

/**
 * FluentSMTP trigger fields.
 */
function adfoin_fluentsmtp_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'fluentsmtp' ) {
        return;
    }

    if ( 'emailFailed' === $form_id ) {
        return array(
            'status'        => __( 'Status', 'advanced-form-integration' ),
            'subject'       => __( 'Email Subject', 'advanced-form-integration' ),
            'body'          => __( 'Email Body', 'advanced-form-integration' ),
            'to'            => __( 'Recipients', 'advanced-form-integration' ),
            'to_raw'        => __( 'Recipients (raw)', 'advanced-form-integration' ),
            'from'          => __( 'From Address', 'advanced-form-integration' ),
            'error_message' => __( 'Error Message', 'advanced-form-integration' ),
            'response'      => __( 'Mailer Response (JSON)', 'advanced-form-integration' ),
            'extra'         => __( 'Extra Data (JSON)', 'advanced-form-integration' ),
            'headers'       => __( 'Headers (JSON)', 'advanced-form-integration' ),
            'attachments'   => __( 'Attachments (JSON)', 'advanced-form-integration' ),
            'log_id'        => __( 'FluentSMTP Log ID', 'advanced-form-integration' ),
            'timestamp'     => __( 'Event Timestamp', 'advanced-form-integration' ),
        );
    }

    return array(
        'status'      => __( 'Status', 'advanced-form-integration' ),
        'subject'     => __( 'Email Subject', 'advanced-form-integration' ),
        'body'        => __( 'Email Body', 'advanced-form-integration' ),
        'to'          => __( 'Recipients', 'advanced-form-integration' ),
        'to_raw'      => __( 'Recipients (raw)', 'advanced-form-integration' ),
        'headers'     => __( 'Headers (JSON)', 'advanced-form-integration' ),
        'attachments' => __( 'Attachments (JSON)', 'advanced-form-integration' ),
        'timestamp'   => __( 'Event Timestamp', 'advanced-form-integration' ),
    );
}

add_action( 'wp_mail_succeeded', 'adfoin_fluentsmtp_handle_email_sent', 10, 1 );
/**
 * Handle successfully delivered emails.
 *
 * @param array $mail_data Mail data provided by FluentSMTP.
 *
 * @return void
 */
function adfoin_fluentsmtp_handle_email_sent( $mail_data ) {
    if ( ! adfoin_fluentsmtp_is_active() ) {
        return;
    }

    $records = adfoin_fluentsmtp_get_records( 'emailSent' );
    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_fluentsmtp_prepare_success_payload( $mail_data );
    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentsmtp_send( $records, $payload );
}

add_action( 'fluentmail_email_sending_failed_no_fallback', 'adfoin_fluentsmtp_handle_email_failed', 10, 3 );
/**
 * Handle email delivery failures after all fallbacks.
 *
 * @param int   $log_id  FluentSMTP log identifier.
 * @param mixed $handler FluentSMTP handler instance (unused).
 * @param array $data    Logged email data.
 *
 * @return void
 */
function adfoin_fluentsmtp_handle_email_failed( $log_id, $handler, $data ) {
    if ( ! adfoin_fluentsmtp_is_active() ) {
        return;
    }

    $records = adfoin_fluentsmtp_get_records( 'emailFailed' );
    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_fluentsmtp_prepare_failure_payload( $log_id, $data );
    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentsmtp_send( $records, $payload );
}

/**
 * Build payload for successful deliveries.
 *
 * @param array $mail Mail data.
 *
 * @return array<string,string>
 */
function adfoin_fluentsmtp_prepare_success_payload( $mail ) {
    if ( empty( $mail ) ) {
        return array();
    }

    if ( is_object( $mail ) ) {
        $mail = (array) $mail;
    }

    if ( ! is_array( $mail ) ) {
        return array();
    }

    return array(
        'status'      => 'sent',
        'subject'     => isset( $mail['subject'] ) ? (string) $mail['subject'] : '',
        'body'        => isset( $mail['message'] ) ? (string) $mail['message'] : '',
        'to'          => adfoin_fluentsmtp_format_recipients( isset( $mail['to'] ) ? $mail['to'] : array() ),
        'to_raw'      => adfoin_fluentsmtp_format_json( isset( $mail['to'] ) ? $mail['to'] : array(), true ),
        'headers'     => adfoin_fluentsmtp_format_json( isset( $mail['headers'] ) ? $mail['headers'] : array() ),
        'attachments' => adfoin_fluentsmtp_format_json( isset( $mail['attachments'] ) ? $mail['attachments'] : array(), true ),
        'timestamp'   => current_time( 'mysql' ),
    );
}

/**
 * Build payload for failed deliveries.
 *
 * @param int   $log_id FluentSMTP log identifier.
 * @param array $data   Logged email data.
 *
 * @return array<string,string>
 */
function adfoin_fluentsmtp_prepare_failure_payload( $log_id, $data ) {
    if ( empty( $data ) ) {
        return array();
    }

    if ( is_object( $data ) ) {
        $data = (array) $data;
    }

    if ( ! is_array( $data ) ) {
        return array();
    }

    return array(
        'status'        => isset( $data['status'] ) ? (string) $data['status'] : 'failed',
        'subject'       => isset( $data['subject'] ) ? (string) $data['subject'] : '',
        'body'          => isset( $data['body'] ) ? (string) $data['body'] : '',
        'to'            => adfoin_fluentsmtp_format_recipients( isset( $data['to'] ) ? $data['to'] : array() ),
        'to_raw'        => adfoin_fluentsmtp_format_json( isset( $data['to'] ) ? $data['to'] : array(), true ),
        'from'          => isset( $data['from'] ) ? (string) $data['from'] : '',
        'error_message' => adfoin_fluentsmtp_extract_error_message( $data ),
        'response'      => adfoin_fluentsmtp_format_json( isset( $data['response'] ) ? $data['response'] : '' ),
        'extra'         => adfoin_fluentsmtp_format_json( isset( $data['extra'] ) ? $data['extra'] : '' ),
        'headers'       => adfoin_fluentsmtp_format_json( isset( $data['headers'] ) ? $data['headers'] : array() ),
        'attachments'   => adfoin_fluentsmtp_format_json( isset( $data['attachments'] ) ? $data['attachments'] : array(), true ),
        'log_id'        => $log_id ? (string) $log_id : '',
        'timestamp'     => current_time( 'mysql' ),
    );
}

/**
 * Determine if FluentSMTP is active.
 *
 * @return bool
 */
function adfoin_fluentsmtp_is_active() {
    return function_exists( 'fluentSmtpInit' ) || defined( 'FLUENTMAIL_PLUGIN_FILE' );
}

/**
 * Retrieve cached integration instance.
 *
 * @return Advanced_Form_Integration_Integration|null
 */
function adfoin_fluentsmtp_integration_instance() {
    static $instance = null;

    if ( null !== $instance ) {
        return $instance;
    }

    if ( ! class_exists( 'Advanced_Form_Integration_Integration' ) ) {
        return null;
    }

    $instance = new Advanced_Form_Integration_Integration();

    return $instance;
}

/**
 * Retrieve cached integration records for a FluentSMTP form.
 *
 * @param string $form_id Trigger identifier.
 *
 * @return array<int,array>
 */
function adfoin_fluentsmtp_get_records( $form_id ) {
    static $cache = array();

    if ( array_key_exists( $form_id, $cache ) ) {
        return $cache[ $form_id ];
    }

    $integration = adfoin_fluentsmtp_integration_instance();

    if ( ! $integration ) {
        $cache[ $form_id ] = array();

        return $cache[ $form_id ];
    }

    $records = $integration->get_by_trigger( 'fluentsmtp', $form_id );

    $cache[ $form_id ] = is_array( $records ) ? $records : array();

    return $cache[ $form_id ];
}

/**
 * Dispatch payload to saved FluentSMTP integrations.
 *
 * @param array<int,array> $records Saved integration records.
 * @param array            $payload Payload data.
 *
 * @return void
 */
function adfoin_fluentsmtp_send( $records, $payload ) {
    if ( empty( $records ) || empty( $payload ) ) {
        return;
    }

    $integration = adfoin_fluentsmtp_integration_instance();

    if ( ! $integration ) {
        return;
    }

    $integration->send( $records, $payload );
}

/**
 * Attempt to decode stored values.
 *
 * @param mixed $value Raw value.
 *
 * @return mixed
 */
function adfoin_fluentsmtp_decode_value( $value ) {
    if ( is_string( $value ) || is_numeric( $value ) ) {
        $maybe_unserialized = maybe_unserialize( $value );
        if ( $maybe_unserialized !== $value ) {
            $value = $maybe_unserialized;
        }
    } else {
        $value = maybe_unserialize( $value );
    }

    if ( is_string( $value ) ) {
        $decoded = json_decode( $value, true );
        if ( JSON_ERROR_NONE === json_last_error() ) {
            return $decoded;
        }
    }

    return $value;
}

/**
 * Convert a value to a JSON string or scalar.
 *
 * @param mixed $value       Raw value.
 * @param bool  $force_array Whether to wrap scalars into an array before encoding.
 *
 * @return string
 */
function adfoin_fluentsmtp_format_json( $value, $force_array = false ) {
    $decoded = adfoin_fluentsmtp_decode_value( $value );

    if ( $force_array && ( is_scalar( $decoded ) || $decoded === null ) ) {
        $decoded = ( '' === $decoded || null === $decoded ) ? array() : array( (string) $decoded );
    }

    if ( null === $decoded || $decoded === array() || $decoded === '' ) {
        return '';
    }

    if ( is_scalar( $decoded ) ) {
        return (string) $decoded;
    }

    $encoded = wp_json_encode( $decoded );

    return $encoded ? $encoded : '';
}

/**
 * Format recipient data to a readable string.
 *
 * @param mixed $value Raw recipient value.
 *
 * @return string
 */
function adfoin_fluentsmtp_format_recipients( $value ) {
    $decoded = adfoin_fluentsmtp_decode_value( $value );

    if ( empty( $decoded ) ) {
        return '';
    }

    if ( is_string( $decoded ) ) {
        return trim( $decoded );
    }

    if ( ! is_array( $decoded ) ) {
        return '';
    }

    $collected = array();
    array_walk_recursive(
        $decoded,
        function ( $item ) use ( &$collected ) {
            if ( is_scalar( $item ) ) {
                $item = trim( (string) $item );
                if ( '' !== $item ) {
                    $collected[] = $item;
                }
            }
        }
    );

    if ( empty( $collected ) ) {
        return '';
    }

    $collected = array_unique( $collected );

    return implode( ', ', $collected );
}

/**
 * Extract a human readable error message from failure data.
 *
 * @param array $data Raw failure data.
 *
 * @return string
 */
function adfoin_fluentsmtp_extract_error_message( $data ) {
    if ( ! empty( $data['message'] ) ) {
        return is_scalar( $data['message'] ) ? (string) $data['message'] : adfoin_fluentsmtp_format_json( $data['message'] );
    }

    if ( empty( $data['response'] ) ) {
        return '';
    }

    $response = adfoin_fluentsmtp_decode_value( $data['response'] );

    if ( is_array( $response ) ) {
        $keys = array( 'message', 'error', 'error_message', 'detail' );

        foreach ( $keys as $key ) {
            if ( ! empty( $response[ $key ] ) ) {
                return is_scalar( $response[ $key ] ) ? (string) $response[ $key ] : wp_json_encode( $response[ $key ] );
            }
        }

        if ( ! empty( $response['errors'] ) ) {
            return wp_json_encode( $response['errors'] );
        }
    } elseif ( is_scalar( $response ) ) {
        return (string) $response;
    }

    return '';
}
