<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve Kali Forms triggers.
 *
 * @param string $form_provider Provider key.
 *
 * @return array<string,string>|void
 */
function adfoin_kaliforms_get_forms( $form_provider ) {
    if ( 'kaliforms' !== $form_provider ) {
        return;
    }

    global $wpdb;

    $forms = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'kaliforms_forms' AND post_status = 'publish'",
        ARRAY_A
    );

    if ( empty( $forms ) ) {
        return array();
    }

    $triggers = array();

    foreach ( $forms as $form ) {
        $triggers[ (string) $form['ID'] ] = $form['post_title'];
    }

    return $triggers;
}

/**
 * Retrieve Kali Forms mapped fields.
 *
 * @param string $form_provider Provider key.
 * @param string $form_id       Form ID.
 *
 * @return array<string,string>|void
 */
function adfoin_kaliforms_get_form_fields( $form_provider, $form_id ) {
    if ( 'kaliforms' !== $form_provider ) {
        return;
    }

    $form_id = absint( $form_id );

    if ( ! $form_id ) {
        return array();
    }

    $fields = array(
        'form_id'          => __( 'Form ID', 'advanced-form-integration' ),
        'form_title'       => __( 'Form Title', 'advanced-form-integration' )
    );

    $layout = get_post_meta( $form_id, 'kaliforms_field_components', true );
    $layout = json_decode( $layout, true );

    if ( empty( $layout ) || ! is_array( $layout ) ) {
        return $fields;
    }

    foreach ( $layout as $field ) {
        if ( empty( $field['properties']['name'] ) ) {
            continue;
        }

        $name  = sanitize_key( $field['properties']['name'] );
        $label = isset( $field['properties']['caption'] ) && ! empty( $field['properties']['caption'] ) 
            ? sanitize_text_field( $field['properties']['caption'] ) 
            : $name;

        $fields[ $name ] = $label;
    }

    return $fields;
}

/**
 * Normalize value to scalar string.
 *
 * @param mixed $value Value to normalize.
 *
 * @return string
 */
function adfoin_kaliforms_normalize_value( $value ) {
    if ( is_bool( $value ) ) {
        return $value ? 'true' : 'false';
    }

    if ( is_null( $value ) ) {
        return '';
    }

    if ( is_scalar( $value ) ) {
        return (string) $value;
    }

    $encoded = wp_json_encode( $value );

    return is_string( $encoded ) ? $encoded : '';
}

/**
 * Gather user context.
 *
 * @param int $user_id User ID.
 *
 * @return array<string,string>
 */
function adfoin_kaliforms_collect_user_context( $user_id ) {
    $context = array(
        'user_id'          => '',
        'user_email'       => '',
        'user_first_name'  => '',
        'user_last_name'   => '',
        'user_display_name'=> '',
        'user_roles'       => '',
    );

    if ( ! $user_id ) {
        return $context;
    }

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return $context;
    }

    $context['user_id']          = (string) $user_id;
    $context['user_email']       = adfoin_kaliforms_normalize_value( $user->user_email );
    $context['user_first_name']  = adfoin_kaliforms_normalize_value( $user->first_name );
    $context['user_last_name']   = adfoin_kaliforms_normalize_value( $user->last_name );
    $context['user_display_name']= adfoin_kaliforms_normalize_value( $user->display_name );
    $context['user_roles']       = adfoin_kaliforms_normalize_value( $user->roles );

    return $context;
}

/**
 * Build submission payload.
 *
 * @param int   $form_id      Form ID.
 * @param array $data         Submission data.
 * @param array $placeholders Placeholder data.
 *
 * @return array<string,string>
 */
function adfoin_kaliforms_prepare_submission_payload( $form_id, $data, $placeholders ) {
    $payload = array(
        'form_id'         => (string) $form_id,
        'form_title'      => get_the_title( $form_id ),
        'submission_id'   => isset( $data['kali_submission_id'] ) ? (string) $data['kali_submission_id'] : '',
        'submission_time' => current_time( 'mysql' ),
        'user_ip'         => adfoin_kaliforms_normalize_value( $data['user_ip'] ?? '' ),
        'user_agent'      => adfoin_kaliforms_normalize_value( $data['user_agent'] ?? '' ),
        'referrer'        => adfoin_kaliforms_normalize_value( $data['referrer'] ?? '' ),
        'source_url'      => adfoin_kaliforms_normalize_value( $data['source_url'] ?? '' ),
        'entry_url'       => adfoin_kaliforms_normalize_value( $data['entry_url'] ?? '' ),
    );

    $payload = array_merge(
        $payload,
        adfoin_kaliforms_collect_user_context( get_current_user_id() )
    );

    $submitted_fields = array_merge( $data, $placeholders );

    foreach ( $submitted_fields as $key => $value ) {
        if ( is_array( $value ) ) {
            $value = adfoin_kaliforms_normalize_value( $value );
        }

        if ( is_string( $key ) && ! array_key_exists( $key, $payload ) ) {
            $payload[ sanitize_key( $key ) ] = adfoin_kaliforms_normalize_value( $value );
        }
    }

    return array_map( 'trim', $payload );
}

add_action( 'kaliforms_after_form_process_action', 'adfoin_kaliforms_after_submission', 10, 1 );

/**
 * Dispatch Kali Forms submission.
 *
 * @param array $submission Submission context.
 *
 * @return void
 */
function adfoin_kaliforms_after_submission( $submission ) {
    if ( empty( $submission['data']['formId'] ) ) {
        return;
    }

    $form_id      = absint( $submission['data']['formId'] );
    $data         = isset( $submission['data'] ) ? (array) $submission['data'] : array();
    $placeholders = isset( $submission['placeholders'] ) ? (array) $submission['placeholders'] : array();

    $payload = adfoin_kaliforms_prepare_submission_payload( $form_id, $data, $placeholders );

    if ( empty( $payload ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'kaliforms', (string) $form_id );

    if ( empty( $saved_records ) ) {
        return;
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
                        'posted_data' => $payload,
                    ),
                )
            );
        } else {
            call_user_func( "adfoin_{$action_provider}_send_data", $record, $payload );
        }
    }
}
