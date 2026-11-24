<?php

/**
 * Advanced Custom Fields (ACF) action platform — local same-site integration
 * (no REST/API keys). Slug is `acfac`, not `acf` — the trigger side already
 * uses that slug (includes/triggers/acf/acf.php); this codebase's convention
 * for a same-slug trigger/action pair is an `ac` suffix on the action (see
 * gravityformsac, wpformsac, buddypressac).
 *
 * Writes through the real global update_field( $selector, $value, $post_id )
 * function (includes/api/api-template.php), confirmed against the plugin's
 * own source. Its full body shows it gracefully falls back to a plain
 * meta-value save (via acf_update_value()) even when $selector doesn't match
 * a registered field — so the selector can be either the field's name or key,
 * same as ACF's own documented usage. $post_id follows ACF's own convention
 * ('123' for a post, 'user_5' for a user, 'option' for the options page,
 * 'term_9' for a term) rather than a plain integer only.
 *
 * @link https://plugins.trac.wordpress.org/browser/advanced-custom-fields/trunk/includes/api/api-template.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_acfac_actions', 10, 1 );

function adfoin_acfac_actions( $actions ) {

    $actions['acfac'] = array(
        'title' => __( 'Advanced Custom Fields', 'advanced-form-integration' ),
        'tasks' => array(
            'update_field' => __( 'Update Field', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_acfac_action_fields' );

function adfoin_acfac_action_fields() {
    ?>
    <script type="text/template" id="acfac-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'update_field'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_acfac_fields', 'adfoin_get_acfac_fields', 10, 0 );

function adfoin_get_acfac_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array(
            'key'         => 'postId',
            'value'       => __( 'Post ID', 'advanced-form-integration' ),
            'description' => __( 'Required. A post ID (123), or ACF\'s own conventions: user_5, term_9, option. If taken from a form field, only numeric IDs of public posts are accepted.', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'selector',
            'value'       => __( 'Field Name or Key', 'advanced-form-integration' ),
            'description' => __( 'Required. Type the registered ACF field\'s name (e.g. phone_number) or key (e.g. field_123). Form fields are not accepted here.', 'advanced-form-integration' ),
        ),
        array(
            'key'         => 'value',
            'value'       => __( 'Value', 'advanced-form-integration' ),
            'description' => __( 'Required.', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_acfac_job_queue', 'adfoin_acfac_job_queue', 10, 1 );

function adfoin_acfac_job_queue( $data ) {
    adfoin_acfac_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles writing an ACF field value
 */
function adfoin_acfac_send_data( $record, $posted_data ) {

    if ( ! function_exists( 'update_field' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'update_field' !== $task ) {
        return;
    }

    $prepared_data = array();

    foreach ( $field_data as $key => $value ) {
        if ( '' === $key ) {
            continue;
        }

        $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed_value || null === $parsed_value ) {
            continue;
        }

        $prepared_data[ $key ] = $parsed_value;
    }

    $request_payload = $prepared_data;
    $response_body   = array( 'success' => false );
    $status_code     = 400;

    $guard_error = '';

    if ( ! empty( $prepared_data['postId'] ) && ! empty( $prepared_data['selector'] ) && isset( $prepared_data['value'] ) ) {
        $guard_error = adfoin_acfac_validate_target( $field_data, $prepared_data, $record );

        if ( '' === $guard_error ) {
            $prepared_data['value'] = adfoin_acfac_sanitize_value( $prepared_data['value'], $prepared_data['selector'], $record );
            $request_payload        = $prepared_data;
        }
    }

    if ( empty( $prepared_data['postId'] ) || empty( $prepared_data['selector'] ) || ! isset( $prepared_data['value'] ) ) {
        $response_body['message'] = __( 'Post ID, field name/key, and value are all required.', 'advanced-form-integration' );
    } elseif ( '' !== $guard_error ) {
        $status_code              = 403;
        $response_body['skipped'] = true;
        $response_body['message'] = $guard_error;
    } else {
        $updated = update_field( $prepared_data['selector'], $prepared_data['value'], $prepared_data['postId'] );

        if ( $updated ) {
            $status_code              = 200;
            $response_body['success'] = true;
            $response_body['message'] = __( 'Field updated successfully.', 'advanced-form-integration' );
        } else {
            $response_body['message'] = __( 'Failed to update the field. Please verify the supplied post ID and field name/key.', 'advanced-form-integration' );
        }
    }

    $log_args = array(
        'method' => 'LOCAL',
        'body'   => $request_payload,
    );

    $log_response = array(
        'response' => array(
            'code'    => $status_code,
            'message' => $response_body['message'],
        ),
        'body'     => $response_body,
    );

    adfoin_add_to_log( $log_response, 'acfac', $log_args, $record );
}

/**
 * Whether an admin-saved mapping template pulls in submitted form data.
 *
 * @param mixed $template Raw field_data value as saved in the integration.
 *
 * @return bool
 */
function adfoin_acfac_is_dynamic( $template ) {
    return is_string( $template ) && false !== strpos( $template, '{{' );
}

/**
 * Security guard for the write target. Form submissions are often public and
 * unauthenticated, so submitted values must not be able to choose what gets
 * written:
 *
 * - The field name/key must be typed in by the admin (no form-field tags) and
 *   must match a registered ACF field. update_field() would otherwise fall
 *   back to a raw meta write under any key.
 * - The admin may type any of ACF's object IDs (123, user_5, term_9, option).
 *   A post ID taken from the submission must be a plain numeric ID of an
 *   existing post in a public post type. Users, terms and the options store
 *   can never be targeted from submitted data.
 *
 * @param array $field_data    Raw saved mapping templates.
 * @param array $prepared_data Parsed values (postId may be normalised).
 * @param array $record        Integration record.
 *
 * @return string Empty string when allowed, otherwise the reason it was refused.
 */
function adfoin_acfac_validate_target( $field_data, &$prepared_data, $record ) {
    $selector_template = isset( $field_data['selector'] ) ? $field_data['selector'] : '';
    $post_id_template  = isset( $field_data['postId'] ) ? $field_data['postId'] : '';
    $selector          = is_string( $prepared_data['selector'] ) ? trim( $prepared_data['selector'] ) : '';
    $post_id           = is_scalar( $prepared_data['postId'] ) ? trim( (string) $prepared_data['postId'] ) : '';

    if ( adfoin_acfac_is_dynamic( $selector_template ) ) {
        return __( 'Refused: the ACF field name/key must be entered directly, not taken from a form field.', 'advanced-form-integration' );
    }

    /**
     * Allow writing to selectors that are not registered ACF fields (raw meta
     * write). Off by default; only enable if you really need it, since the
     * value then bypasses ACF's field handling.
     *
     * @param bool   $allow    Default false.
     * @param string $selector Field name/key.
     * @param array  $record   Integration record.
     */
    $allow_unregistered = apply_filters( 'adfoin_acfac_allow_unregistered_selector', false, $selector, $record );

    if ( ! $allow_unregistered && ( ! function_exists( 'acf_get_field' ) || ! acf_get_field( $selector ) ) ) {
        return sprintf(
            /* translators: %s: ACF field name or key */
            __( 'Refused: "%s" is not a registered ACF field name or key.', 'advanced-form-integration' ),
            $selector
        );
    }

    if ( ! adfoin_acfac_is_dynamic( $post_id_template ) ) {
        // Admin-typed target: ACF's own ID conventions are allowed.
        return '';
    }

    $allowed = false;

    if ( '' !== $post_id && ctype_digit( $post_id ) && (int) $post_id > 0 ) {
        $post = get_post( (int) $post_id );

        if ( $post && 'revision' !== $post->post_type && 'trash' !== $post->post_status ) {
            $type_object = get_post_type_object( $post->post_type );
            $allowed     = $type_object && ! empty( $type_object->public );
        }
    }

    /**
     * Filter whether a post ID taken from a form submission may be written to.
     *
     * Default: only existing posts of public post types. Users, terms and the
     * options page can never be targeted from submitted data.
     *
     * @param bool   $allowed  Whether the write is allowed.
     * @param string $post_id  Submitted post ID.
     * @param string $selector Field name/key.
     * @param array  $record   Integration record.
     */
    $allowed = (bool) apply_filters( 'adfoin_acfac_allow_dynamic_post_id', $allowed, $post_id, $selector, $record );

    if ( ! $allowed || ! ctype_digit( $post_id ) ) {
        return sprintf(
            /* translators: %s: submitted post ID */
            __( 'Refused: the submitted post ID "%s" is not an existing post in a public post type.', 'advanced-form-integration' ),
            $post_id
        );
    }

    $prepared_data['postId'] = (int) $post_id;

    return '';
}

/**
 * Strip unsafe HTML (scripts, event handlers) from a value before saving.
 * Form submissions come from visitors who don't have unfiltered_html.
 *
 * @param mixed  $value    Parsed value.
 * @param string $selector Field name/key.
 * @param array  $record   Integration record.
 *
 * @return mixed
 */
function adfoin_acfac_sanitize_value( $value, $selector, $record ) {
    /**
     * Opt out of HTML sanitization for the ACF value. Not recommended when the
     * mapped value comes from a public form.
     *
     * @param bool   $sanitize Default true.
     * @param string $selector Field name/key.
     * @param array  $record   Integration record.
     */
    if ( ! apply_filters( 'adfoin_acfac_sanitize_value', true, $selector, $record ) ) {
        return $value;
    }

    if ( is_array( $value ) ) {
        return map_deep( $value, 'wp_kses_post' );
    }

    return is_string( $value ) ? wp_kses_post( $value ) : $value;
}
