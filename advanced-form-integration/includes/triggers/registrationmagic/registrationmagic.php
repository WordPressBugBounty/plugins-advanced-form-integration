<?php

/**
 * RegistrationMagic trigger integration.
 *
 * @package Advanced_Form_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide RegistrationMagic form list.
 *
 * @param string $form_provider Current trigger provider.
 *
 * @return array<string,string>|void
 */
function adfoin_registrationmagic_get_forms( $form_provider ) {
    if ( 'registrationmagic' !== $form_provider ) {
        return;
    }

    global $wpdb;

    $table = "{$wpdb->prefix}rm_forms";

    if ( ! adfoin_registrationmagic_table_exists( $table ) ) {
        return array();
    }

    $forms = $wpdb->get_results(
        "SELECT form_id, form_name FROM {$table} ORDER BY form_name ASC",
        ARRAY_A
    );

    if ( empty( $forms ) ) {
        return array();
    }

    $formatted = array();

    foreach ( $forms as $form ) {
        $formatted[ (string) $form['form_id'] ] = $form['form_name'];
    }

    return $formatted;
}

/**
 * Provide RegistrationMagic field list for a form.
 *
 * @param string $form_provider Current trigger provider.
 * @param string $form_id       Selected form id.
 *
 * @return array<string,string>|void
 */
function adfoin_registrationmagic_get_form_fields( $form_provider, $form_id ) {
    if ( 'registrationmagic' !== $form_provider ) {
        return;
    }

    $form_id = absint( $form_id );

    if ( ! $form_id ) {
        return array();
    }

    global $wpdb;

    $table = "{$wpdb->prefix}rm_fields";

    if ( ! adfoin_registrationmagic_table_exists( $table ) ) {
        return array();
    }

    $fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT field_id, field_label FROM {$table} WHERE form_id = %d ORDER BY page_no ASC, field_order ASC",
            $form_id
        ),
        ARRAY_A
    );

    $mapped_fields = array();

    if ( ! empty( $fields ) ) {
        foreach ( $fields as $field ) {
            $field_key   = 'field_' . (string) $field['field_id'];
            $field_label = ! empty( $field['field_label'] )
                ? $field['field_label']
                : sprintf(
                    /* translators: %d: field id */
                    __( 'Field %d', 'advanced-form-integration' ),
                    $field['field_id']
                );

            $mapped_fields[ $field_key ] = $field_label;
        }
    }

    $mapped_fields['form_id']           = __( 'Form ID', 'advanced-form-integration' );
    $mapped_fields['form_name']         = __( 'Form Name', 'advanced-form-integration' );
    $mapped_fields['submission_id']     = __( 'Submission ID', 'advanced-form-integration' );
    $mapped_fields['submission_token']  = __( 'Submission Token', 'advanced-form-integration' );
    $mapped_fields['submission_date']   = __( 'Submission Date', 'advanced-form-integration' );
    $mapped_fields['user_id']           = __( 'User ID', 'advanced-form-integration' );
    $mapped_fields['user_email']        = __( 'User Email', 'advanced-form-integration' );
    $mapped_fields['user_ip']           = __( 'User IP', 'advanced-form-integration' );
    $mapped_fields['user_browser']      = __( 'User Browser', 'advanced-form-integration' );
    $mapped_fields['wp_user_login']     = __( 'WP User Login', 'advanced-form-integration' );
    $mapped_fields['wp_user_email']     = __( 'WP User Email', 'advanced-form-integration' );
    $mapped_fields['wp_user_first_name']= __( 'WP User First Name', 'advanced-form-integration' );
    $mapped_fields['wp_user_last_name'] = __( 'WP User Last Name', 'advanced-form-integration' );
    $mapped_fields['wp_user_display_name'] = __( 'WP User Display Name', 'advanced-form-integration' );
    $mapped_fields['wp_user_roles']     = __( 'WP User Roles', 'advanced-form-integration' );
    $mapped_fields['submission_admin_url'] = __( 'Submission Admin URL', 'advanced-form-integration' );

    return $mapped_fields;
}

/**
 * Provide RegistrationMagic form name.
 *
 * @param string $form_provider Current trigger provider.
 * @param string $form_id       Selected form id.
 *
 * @return string|void
 */
function adfoin_registrationmagic_get_form_name( $form_provider, $form_id ) {
    if ( 'registrationmagic' !== $form_provider ) {
        return;
    }

    return adfoin_registrationmagic_get_form_title( absint( $form_id ) );
}

add_action( 'rm_submission_completed', 'adfoin_registrationmagic_handle_submission', 10, 3 );

/**
 * Handle RegistrationMagic submission events.
 *
 * @param int                              $form_id         RegistrationMagic form id.
 * @param int                              $user_id         Related WordPress user id.
 * @param array<int,stdClass>|array<mixed> $submission_data Submission field payload.
 *
 * @return void
 */
function adfoin_registrationmagic_handle_submission( $form_id, $user_id, $submission_data ) {
    $form_id = absint( $form_id );

    if ( ! $form_id || empty( $submission_data ) || ! is_array( $submission_data ) ) {
        return;
    }

    global $wpdb, $post;

    $saved_records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE status = 1 AND form_provider = %s AND form_id = %s",
            'registrationmagic',
            $form_id
        ),
        ARRAY_A
    );

    if ( empty( $saved_records ) ) {
        return;
    }

    $posted_data = adfoin_registrationmagic_build_payload( $form_id, $user_id, $submission_data );

    if ( empty( $posted_data ) ) {
        return;
    }

    $special_tag_values = adfoin_get_special_tags_values( $post );

    if ( is_array( $special_tag_values ) ) {
        $posted_data = $posted_data + $special_tag_values;
    }

    $job_queue = get_option( 'adfoin_general_settings_job_queue' );

    foreach ( $saved_records as $record ) {
        $action_provider = $record['action_provider'];

        if ( $job_queue ) {
            as_enqueue_async_action(
                "adfoin_{$action_provider}_job_queue",
                array(
                    'data' => array(
                        'record'      => $record,
                        'posted_data' => $posted_data,
                    ),
                )
            );
        } else {
            call_user_func(
                "adfoin_{$action_provider}_send_data",
                $record,
                $posted_data
            );
        }
    }
}

/**
 * Normalize submission payload for AFI.
 *
 * @param int                              $form_id         RegistrationMagic form id.
 * @param int                              $user_id         Related WordPress user id.
 * @param array<int,stdClass>|array<mixed> $submission_data Submission field payload.
 *
 * @return array<string,string>
 */
function adfoin_registrationmagic_build_payload( $form_id, $user_id, $submission_data ) {
    $payload     = array();
    $email_value = '';

    foreach ( $submission_data as $field_id => $field ) {
        if ( ! is_object( $field ) ) {
            continue;
        }

        $field_key   = 'field_' . (string) $field_id;
        $field_value = adfoin_registrationmagic_map_field_value( $field );

        $payload[ $field_key ] = $field_value;

        if ( ! empty( $field->label ) ) {
            adfoin_registrationmagic_assign_label_key( $payload, $field->label, $field_value );
        }

        if ( isset( $field->meta ) && ! empty( $field->meta ) ) {
            $payload[ $field_key . '_meta' ] = adfoin_registrationmagic_stringify( $field->meta );
        }

        if ( isset( $field->type ) && 'Email' === $field->type && ! empty( $field_value ) && '' === $email_value ) {
            $email_value = $field_value;
        }
    }

    $payload['form_id']   = (string) $form_id;
    $payload['form_name'] = adfoin_registrationmagic_get_form_title( $form_id );

    if ( $user_id ) {
        $payload['user_id'] = (string) $user_id;
    }

    $submission = adfoin_registrationmagic_resolve_submission( $form_id, $email_value );

    if ( $submission instanceof RM_Submissions ) {
        $payload['submission_id']    = (string) $submission->get_submission_id();
        $payload['submission_token'] = (string) $submission->get_unique_token();
        $payload['submission_date']  = (string) $submission->get_submitted_on();
        $payload['user_email']       = $submission->get_user_email();

        $user_ip = $submission->get_submission_ip();
        if ( ! empty( $user_ip ) ) {
            $payload['user_ip'] = $user_ip;
        }

        $user_browser = $submission->get_submission_browser();
        if ( ! empty( $user_browser ) ) {
            $payload['user_browser'] = $user_browser;
        }

        if ( empty( $payload['submission_admin_url'] ) ) {
            $payload['submission_admin_url'] = add_query_arg(
                array(
                    'page'            => 'rm_submission_view',
                    'rm_submission_id'=> $submission->get_submission_id(),
                ),
                admin_url( 'admin.php' )
            );
        }
    } elseif ( $email_value && empty( $payload['user_email'] ) ) {
        $payload['user_email'] = $email_value;
    }

    if ( $user_id ) {
        $user = get_userdata( $user_id );

        if ( $user instanceof WP_User ) {
            $payload['wp_user_login']       = $user->user_login;
            $payload['wp_user_email']       = $user->user_email;
            $payload['wp_user_first_name']  = $user->first_name;
            $payload['wp_user_last_name']   = $user->last_name;
            $payload['wp_user_display_name']= $user->display_name;

            if ( ! empty( $user->roles ) ) {
                $payload['wp_user_roles'] = implode( ', ', $user->roles );
            }
        }
    }

    if ( ! array_key_exists( 'submission_admin_url', $payload ) ) {
        $payload['submission_admin_url'] = '';
    }

    return $payload;
}

/**
 * Retrieve formatted field value.
 *
 * @param stdClass $field Field object.
 *
 * @return string
 */
function adfoin_registrationmagic_map_field_value( $field ) {
    if ( isset( $field->value ) ) {
        return adfoin_registrationmagic_stringify( $field->value );
    }

    return '';
}

/**
 * Convert arbitrary value into a string.
 *
 * @param mixed $value Value to stringify.
 *
 * @return string
 */
function adfoin_registrationmagic_stringify( $value ) {
    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    if ( is_array( $value ) ) {
        $segments = array();

        foreach ( $value as $key => $item ) {
            $item_string = adfoin_registrationmagic_stringify( $item );

            if ( '' === $item_string ) {
                continue;
            }

            if ( is_string( $key ) ) {
                $segments[] = $key . ': ' . $item_string;
            } else {
                $segments[] = $item_string;
            }
        }

        return implode( ', ', $segments );
    }

    if ( is_object( $value ) ) {
        if ( isset( $value->value ) && is_scalar( $value->value ) ) {
            return (string) $value->value;
        }

        $encoded = wp_json_encode( $value );

        return is_string( $encoded ) ? $encoded : '';
    }

    return '';
}

/**
 * Generate a label-based key.
 *
 * @param string $label Field label.
 *
 * @return string
 */
function adfoin_registrationmagic_label_key( $label ) {
    $label = sanitize_text_field( $label );
    $label = strtolower( $label );
    $label = preg_replace( '/[^a-z0-9]+/', '_', $label );
    $label = trim( $label, '_' );

    if ( '' === $label ) {
        return '';
    }

    return 'label_' . $label;
}

/**
 * Ensure label key is unique before adding to payload.
 *
 * @param array<string,string> $payload Payload reference.
 * @param string               $label   Field label.
 * @param string               $value   Field value.
 *
 * @return void
 */
function adfoin_registrationmagic_assign_label_key( array &$payload, $label, $value ) {
    $base_key = adfoin_registrationmagic_label_key( $label );

    if ( '' === $base_key ) {
        return;
    }

    $candidate = $base_key;
    $suffix    = 1;

    while ( array_key_exists( $candidate, $payload ) ) {
        if ( $payload[ $candidate ] === $value ) {
            return;
        }

        ++$suffix;
        $candidate = $base_key . '_' . $suffix;
    }

    $payload[ $candidate ] = $value;
}

/**
 * Retrieve and cache RegistrationMagic form name.
 *
 * @param int $form_id Form id.
 *
 * @return string
 */
function adfoin_registrationmagic_get_form_title( $form_id ) {
    static $cache = array();

    $form_id = absint( $form_id );

    if ( ! $form_id ) {
        return '';
    }

    if ( isset( $cache[ $form_id ] ) ) {
        return $cache[ $form_id ];
    }

    global $wpdb;

    $table = "{$wpdb->prefix}rm_forms";

    if ( ! adfoin_registrationmagic_table_exists( $table ) ) {
        $cache[ $form_id ] = '';
        return '';
    }

    $title = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT form_name FROM {$table} WHERE form_id = %d LIMIT 1",
            $form_id
        )
    );

    $cache[ $form_id ] = $title ? $title : '';

    return $cache[ $form_id ];
}

/**
 * Cached table existence lookup.
 *
 * @param string $table Fully qualified table name.
 *
 * @return bool
 */
function adfoin_registrationmagic_table_exists( $table ) {
    static $cache = array();

    if ( isset( $cache[ $table ] ) ) {
        return $cache[ $table ];
    }

    global $wpdb;

    $result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

    $cache[ $table ] = ( $result === $table );

    return $cache[ $table ];
}

/**
 * Resolve the submission row related to the current event.
 *
 * @param int    $form_id Form identifier.
 * @param string $email   Captured email address.
 *
 * @return RM_Submissions|null
 */
function adfoin_registrationmagic_resolve_submission( $form_id, $email = '' ) {
    if ( ! class_exists( 'RM_Submissions' ) ) {
        return null;
    }

    global $wpdb;

    $table = "{$wpdb->prefix}rm_submissions";

    if ( ! adfoin_registrationmagic_table_exists( $table ) ) {
        return null;
    }

    $submission_id = 0;

    if ( $email ) {
        $submission_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT submission_id FROM {$table} WHERE form_id = %d AND user_email = %s ORDER BY submission_id DESC LIMIT 1",
                $form_id,
                $email
            )
        );
    }

    if ( ! $submission_id ) {
        $submission_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT submission_id FROM {$table} WHERE form_id = %d ORDER BY submission_id DESC LIMIT 1",
                $form_id
            )
        );
    }

    if ( ! $submission_id ) {
        return null;
    }

    $submission = new RM_Submissions();

    if ( ! $submission->load_from_db( $submission_id ) ) {
        return null;
    }

    return $submission;
}
