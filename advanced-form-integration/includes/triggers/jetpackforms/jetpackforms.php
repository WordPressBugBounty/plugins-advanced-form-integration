<?php

/**
 * Jetpack Forms trigger — fires on a successful Jetpack/Grunion contact
 * form submission (works for both the bundled Jetpack module and the
 * standalone "Jetpack Forms" plugin; both ship the same contact-form
 * package).
 *
 * Confirmed against Automattic/jetpack source
 * (projects/packages/forms/src/contact-form/class-contact-form.php and
 * class-contact-form-plugin.php): `grunion_after_feedback_post_inserted`
 * fires unconditionally after validation passes, regardless of whether
 * the form's own "Email Notifications" setting is on. The other two
 * candidate hooks (`grunion_pre_message_sent` / `grunion_after_message_sent`)
 * are gated behind that email-notification setting and silently never fire
 * once a site owner turns email off in favor of this integration — do not
 * use them.
 *
 * Jetpack Forms has no reliable, fully-enumerable "list of forms" the way
 * CF7 does: most Jetpack forms are inline shortcodes/blocks dropped into
 * ordinary post content with no separate listing (only newer "reusable
 * forms" are a real, queryable CPT, and that's the minority case). Rather
 * than offer a form picker that would silently fail to distinguish the
 * dominant inline-form case, this trigger is a single site-wide "Any
 * Jetpack Form Submitted" event — the one thing that's honestly reliable
 * for every Jetpack Forms installation.
 *
 * Field values are read from $_POST using Jetpack's own confirmed naming
 * convention `g{contact-form-id}-{field-id}` (contact-form-id comes from
 * the hidden `contact-form-id` field Jetpack always renders) rather than
 * the hook's `$all_values`/`$entry_values`, whose keys are a
 * "{index}_{label}" shape that shifts if fields are reordered/relabeled.
 *
 * @link https://github.com/Automattic/jetpack/blob/trunk/projects/packages/forms/src/contact-form/class-contact-form.php
 */

// Get Jetpack Forms Triggers
function adfoin_jetpackforms_get_forms( $form_provider ) {
    if ( $form_provider !== 'jetpackforms' ) {
        return;
    }

    return array(
        'anyForm' => __( 'Any Jetpack Form Submitted (site-wide)', 'advanced-form-integration' ),
    );
}

// Get Jetpack Forms Fields
function adfoin_jetpackforms_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'jetpackforms' || $form_id !== 'anyForm' ) {
        return;
    }

    // Jetpack's own default contact-form template — reliable common ground
    // across most installs. Fields beyond these vary per-form and aren't
    // enumerable in advance, so they're only available via all_fields_json.
    return array(
        'name'             => __( 'Name', 'advanced-form-integration' ),
        'email'            => __( 'Email', 'advanced-form-integration' ),
        'subject'          => __( 'Subject', 'advanced-form-integration' ),
        'message'          => __( 'Message', 'advanced-form-integration' ),
        'post_id'          => __( 'Post ID (page the form was on)', 'advanced-form-integration' ),
        'feedback_post_id' => __( 'Feedback Post ID (the submission record)', 'advanced-form-integration' ),
        'all_fields_json'  => __( 'All Fields (JSON, for fields not listed above)', 'advanced-form-integration' ),
    );
}

// Handle Jetpack Forms Submission
add_action( 'grunion_after_feedback_post_inserted', 'adfoin_jetpackforms_handle_submission', 10, 4 );
function adfoin_jetpackforms_handle_submission( $post_id, $fields, $is_spam, $entry_values ) {
    if ( $is_spam ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'jetpackforms', 'anyForm' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $contact_form_id = isset( $_POST['contact-form-id'] ) ? sanitize_text_field( wp_unslash( $_POST['contact-form-id'] ) ) : '';

    $posted_data = array(
        'post_id'          => $entry_values['entry_page'] ?? '',
        'feedback_post_id' => $post_id,
    );

    $all_fields  = array();
    $name_guess  = '';
    $email_guess = '';
    $subject_guess = '';
    $message_guess = '';

    if ( is_array( $fields ) ) {
        foreach ( $fields as $field ) {
            if ( ! is_object( $field ) || ! method_exists( $field, 'get_attribute' ) ) {
                continue;
            }

            $field_id = $field->get_attribute( 'id' );
            $label    = $field->get_attribute( 'label' );
            $type     = $field->get_attribute( 'type' );

            $post_key = $contact_form_id ? ( 'g' . $contact_form_id . '-' . $field_id ) : '';
            $value    = ( $post_key && isset( $_POST[ $post_key ] ) ) ? adfoin_sanitize_text_or_array_field( $_POST[ $post_key ] ) : '';

            if ( $label ) {
                $all_fields[ $label ] = $value;
            }

            // Best-effort aliasing onto the common field names, based on
            // Jetpack's own field type/label conventions.
            $label_lower = strtolower( (string) $label );
            if ( 'email' === $type || false !== strpos( $label_lower, 'email' ) ) {
                $email_guess = $value;
            } elseif ( 'name' === $type || false !== strpos( $label_lower, 'name' ) ) {
                $name_guess = $value;
            } elseif ( false !== strpos( $label_lower, 'subject' ) ) {
                $subject_guess = $value;
            } elseif ( 'textarea' === $type || false !== strpos( $label_lower, 'message' ) || false !== strpos( $label_lower, 'comment' ) ) {
                $message_guess = $value;
            }
        }
    }

    $posted_data['name']            = $name_guess;
    $posted_data['email']           = $email_guess;
    $posted_data['subject']         = $subject_guess;
    $posted_data['message']         = $message_guess;
    $posted_data['all_fields_json'] = wp_json_encode( $all_fields );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
