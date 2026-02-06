<?php

/**
 * FluentAuth trigger registration.
 */
function adfoin_fluentsecurity_get_forms( $form_provider ) {
    if ( $form_provider !== 'fluentsecurity' ) {
        return;
    }

    return array(
        'userRegistered'  => __( 'User registration (FluentAuth)', 'advanced-form-integration' ),
        'userLoginSuccess' => __( 'User login success (FluentAuth)', 'advanced-form-integration' ),
        'userLoginFailed' => __( 'User login failed (FluentAuth)', 'advanced-form-integration' ),
    );
}

/**
 * FluentAuth field definition.
 */
function adfoin_fluentsecurity_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'fluentsecurity' ) {
        return;
    }

    if ( 'userRegistered' === $form_id ) {
        return array(
            'user_id'       => __( 'User ID', 'advanced-form-integration' ),
            'username'      => __( 'Username', 'advanced-form-integration' ),
            'user_email'    => __( 'User Email', 'advanced-form-integration' ),
            'display_name'  => __( 'Display Name', 'advanced-form-integration' ),
            'first_name'    => __( 'First Name', 'advanced-form-integration' ),
            'last_name'     => __( 'Last Name', 'advanced-form-integration' ),
            'user_roles'    => __( 'User Roles', 'advanced-form-integration' ),
            'user_url'      => __( 'Website URL', 'advanced-form-integration' ),
            'registered_at' => __( 'Registered At', 'advanced-form-integration' ),
            'ip_address'    => __( 'IP Address', 'advanced-form-integration' ),
            'signup_meta'   => __( 'Signup Data (JSON)', 'advanced-form-integration' ),
        );
    }

    if ( 'userLoginSuccess' === $form_id ) {
        return array(
            'user_id'      => __( 'User ID', 'advanced-form-integration' ),
            'username'     => __( 'Username', 'advanced-form-integration' ),
            'user_email'   => __( 'User Email', 'advanced-form-integration' ),
            'display_name' => __( 'Display Name', 'advanced-form-integration' ),
            'user_roles'   => __( 'User Roles', 'advanced-form-integration' ),
            'login_media'  => __( 'Login Media', 'advanced-form-integration' ),
            'ip_address'   => __( 'IP Address', 'advanced-form-integration' ),
            'user_agent'   => __( 'User Agent', 'advanced-form-integration' ),
            'login_time'   => __( 'Login Time', 'advanced-form-integration' ),
        );
    }

    return array(
        'username'     => __( 'Username or Email', 'advanced-form-integration' ),
        'user_id'      => __( 'User ID', 'advanced-form-integration' ),
        'user_email'   => __( 'User Email', 'advanced-form-integration' ),
        'user_exists'  => __( 'User Exists', 'advanced-form-integration' ),
        'error_code'   => __( 'Error Code', 'advanced-form-integration' ),
        'error_message'=> __( 'Error Message', 'advanced-form-integration' ),
        'ip_address'   => __( 'IP Address', 'advanced-form-integration' ),
        'user_agent'   => __( 'User Agent', 'advanced-form-integration' ),
        'login_media'  => __( 'Login Media', 'advanced-form-integration' ),
        'attempt_time' => __( 'Attempt Time', 'advanced-form-integration' ),
    );
}

add_action( 'fluent_auth/after_creating_user', 'adfoin_fluentsecurity_handle_user_registered', 10, 2 );
/**
 * Handle FluentAuth signup completion.
 *
 * @param int   $user_id User ID.
 * @param array $data    Signup data array.
 *
 * @return void
 */
function adfoin_fluentsecurity_handle_user_registered( $user_id, $data ) {
    if ( ! adfoin_fluentsecurity_is_active() ) {
        return;
    }

    $records = adfoin_fluentsecurity_get_records( 'userRegistered' );
    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_fluentsecurity_prepare_signup_payload( $user_id, $data );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentsecurity_send( $records, $payload );
}

add_action( 'fluent_auth/user_login_success', 'adfoin_fluentsecurity_handle_login_success', 10, 1 );
/**
 * Handle FluentAuth login success events.
 *
 * @param \WP_User $user The authenticated user.
 *
 * @return void
 */
function adfoin_fluentsecurity_handle_login_success( $user ) {
    if ( ! adfoin_fluentsecurity_is_active() ) {
        return;
    }

    adfoin_fluentsecurity_dispatch_login_event( $user );
}

add_action( 'fluent_auth/after_logging_in_user', 'adfoin_fluentsecurity_handle_after_login', 10, 1 );
/**
 * Handle fallback login success events.
 *
 * @param int $user_id User identifier.
 *
 * @return void
 */
function adfoin_fluentsecurity_handle_after_login( $user_id ) {
    if ( ! adfoin_fluentsecurity_is_active() ) {
        return;
    }

    $user = get_user_by( 'ID', $user_id );
    if ( ! $user instanceof WP_User ) {
        return;
    }

    adfoin_fluentsecurity_dispatch_login_event( $user );
}

add_action( 'wp_login_failed', 'adfoin_fluentsecurity_handle_login_failed', 10, 2 );
/**
 * Handle FluentAuth login failures.
 *
 * @param string        $username Attempted username/email.
 * @param WP_Error|null $error    Error object when available.
 *
 * @return void
 */
function adfoin_fluentsecurity_handle_login_failed( $username, $error = null ) {
    if ( ! adfoin_fluentsecurity_is_active() ) {
        return;
    }

    $records = adfoin_fluentsecurity_get_records( 'userLoginFailed' );
    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_fluentsecurity_prepare_failed_payload( $username, $error );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_fluentsecurity_send( $records, $payload );
}

/**
 * Dispatch login success payload with duplicate protection.
 *
 * @param \WP_User $user Authenticated user object.
 *
 * @return void
 */
function adfoin_fluentsecurity_dispatch_login_event( $user ) {
    if ( ! $user instanceof WP_User ) {
        return;
    }

    static $recent = array();
    $now = microtime( true );

    if ( isset( $recent[ $user->ID ] ) && ( $now - $recent[ $user->ID ] ) < 2 ) {
        return;
    }

    $records = adfoin_fluentsecurity_get_records( 'userLoginSuccess' );
    if ( empty( $records ) ) {
        return;
    }

    $payload = adfoin_fluentsecurity_prepare_login_payload( $user );
    if ( empty( $payload ) ) {
        return;
    }

    $recent[ $user->ID ] = $now;

    adfoin_fluentsecurity_send( $records, $payload );
}

/**
 * Build signup payload.
 *
 * @param int   $user_id Created user ID.
 * @param array $data    Raw signup data.
 *
 * @return array<string,string>
 */
function adfoin_fluentsecurity_prepare_signup_payload( $user_id, $data ) {
    $user = get_user_by( 'ID', $user_id );

    if ( ! $user instanceof WP_User ) {
        return array();
    }

    $first_name = get_user_meta( $user->ID, 'first_name', true );
    $last_name  = get_user_meta( $user->ID, 'last_name', true );

    $formatted_data = array();
    if ( is_array( $data ) ) {
        $formatted_data = $data;
        unset( $formatted_data['user_pass'] );
    }

    return array(
        'user_id'       => (string) $user->ID,
        'username'      => $user->user_login,
        'user_email'    => $user->user_email,
        'display_name'  => $user->display_name,
        'first_name'    => $first_name ?: '',
        'last_name'     => $last_name ?: '',
        'user_roles'    => implode( ', ', (array) $user->roles ),
        'user_url'      => $user->user_url,
        'registered_at' => $user->user_registered,
        'ip_address'    => adfoin_fluentsecurity_get_ip(),
        'signup_meta'   => adfoin_fluentsecurity_format_json( $formatted_data ),
    );
}

/**
 * Build login payload.
 *
 * @param \WP_User $user Authenticated user.
 *
 * @return array<string,string>
 */
function adfoin_fluentsecurity_prepare_login_payload( $user ) {
    if ( ! $user instanceof WP_User ) {
        return array();
    }

    return array(
        'user_id'      => (string) $user->ID,
        'username'     => $user->user_login,
        'user_email'   => $user->user_email,
        'display_name' => $user->display_name,
        'user_roles'   => implode( ', ', (array) $user->roles ),
        'login_media'  => adfoin_fluentsecurity_get_login_media(),
        'ip_address'   => adfoin_fluentsecurity_get_ip(),
        'user_agent'   => adfoin_fluentsecurity_get_user_agent(),
        'login_time'   => current_time( 'mysql' ),
    );
}

/**
 * Build failure payload.
 *
 * @param string        $username Attempted username/email.
 * @param WP_Error|null $error    Error object when available.
 *
 * @return array<string,string>
 */
function adfoin_fluentsecurity_prepare_failed_payload( $username, $error ) {
    $raw_username = is_scalar( $username ) ? (string) $username : '';
    $sanitized    = sanitize_text_field( $raw_username );

    $user = false;
    if ( $sanitized ) {
        if ( is_email( $sanitized ) ) {
            $user = get_user_by( 'email', $sanitized );
        } else {
            $user = get_user_by( 'login', $sanitized );
        }
    }

    $is_error     = ( $error instanceof WP_Error );
    $error_code   = $is_error ? $error->get_error_code() : '';
    $error_message = $is_error ? $error->get_error_message() : '';

    return array(
        'username'      => $sanitized,
        'user_id'       => $user ? (string) $user->ID : '',
        'user_email'    => $user ? $user->user_email : '',
        'user_exists'   => $user ? 'yes' : 'no',
        'error_code'    => $error_code,
        'error_message' => $error_message,
        'ip_address'    => adfoin_fluentsecurity_get_ip(),
        'user_agent'    => adfoin_fluentsecurity_get_user_agent(),
        'login_media'   => adfoin_fluentsecurity_get_login_media(),
        'attempt_time'  => current_time( 'mysql' ),
    );
}

/**
 * Check if FluentAuth is active.
 *
 * @return bool
 */
function adfoin_fluentsecurity_is_active() {
    return defined( 'FLUENT_AUTH_VERSION' );
}

/**
 * Return cached integration instance.
 *
 * @return Advanced_Form_Integration_Integration|null
 */
function adfoin_fluentsecurity_integration_instance() {
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
 * Fetch integration records for FluentAuth triggers.
 *
 * @param string $form_id Trigger identifier.
 *
 * @return array<int,array>
 */
function adfoin_fluentsecurity_get_records( $form_id ) {
    static $cache = array();

    if ( array_key_exists( $form_id, $cache ) ) {
        return $cache[ $form_id ];
    }

    $integration = adfoin_fluentsecurity_integration_instance();
    if ( ! $integration ) {
        $cache[ $form_id ] = array();

        return $cache[ $form_id ];
    }

    $records = $integration->get_by_trigger( 'fluentsecurity', $form_id );
    $cache[ $form_id ] = is_array( $records ) ? $records : array();

    return $cache[ $form_id ];
}

/**
 * Dispatch payload to registered integrations.
 *
 * @param array $records Saved integration records.
 * @param array $payload Payload data.
 *
 * @return void
 */
function adfoin_fluentsecurity_send( $records, $payload ) {
    if ( empty( $records ) || empty( $payload ) ) {
        return;
    }

    $integration = adfoin_fluentsecurity_integration_instance();
    if ( ! $integration ) {
        return;
    }

    $integration->send( $records, $payload );
}

/**
 * Format value as JSON string when applicable.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function adfoin_fluentsecurity_format_json( $value ) {
    if ( empty( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return $encoded ? $encoded : '';
}

/**
 * Retrieve requester IP address.
 *
 * @return string
 */
function adfoin_fluentsecurity_get_ip() {
    if ( class_exists( '\FluentAuth\App\Helpers\Helper' ) ) {
        return (string) \FluentAuth\App\Helpers\Helper::getIp();
    }

    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    return $ip;
}

/**
 * Retrieve current user agent string.
 *
 * @return string
 */
function adfoin_fluentsecurity_get_user_agent() {
    return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
}

/**
 * Retrieve current login media if available.
 *
 * @return string
 */
function adfoin_fluentsecurity_get_login_media() {
    if ( class_exists( '\FluentAuth\App\Helpers\Helper' ) ) {
        return (string) \FluentAuth\App\Helpers\Helper::getLoginMedia();
    }

    return 'web';
}
