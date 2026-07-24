<?php

/**
 * WP User Frontend (WPUF) trigger — fires when a front-end post/listing
 * submission form is submitted.
 *
 * Confirmed against the official weDevsOfficial/wp-user-frontend source:
 * `do_action('wpuf_add_post_after_insert', $post_id, $form_id, $form_settings, $meta_vars)`
 * fires right after wp_insert_post() + meta/taxonomy/attachment handling
 * completes for a NEW post (use this, not `wpuf_draft_post_after_insert`,
 * which only fires on an explicit mid-form "Save Draft" click).
 *
 * Forms are a real, listable CPT (`wpuf_forms`); each form's configured
 * fields are enumerated with the plugin's own `wpuf_get_form_fields()`
 * helper, which returns each field's `name` (the exact post-meta key the
 * value is stored under) and `label`. A handful of field names are
 * special-cased onto native post columns/taxonomy instead of postmeta
 * (post_title, post_content, post_excerpt, featured_image) — handled
 * below rather than read as meta.
 *
 * @link https://wedevs.com/docs/wp-user-frontend-pro/developer-docs/actions/wpuf_add_post_after_insert/
 */
// Get WPUF Forms
function adfoin_wpuf_get_forms(  $form_provider  ) {
    if ( $form_provider !== 'wpuf' ) {
        return;
    }
    $forms = array();
    $posts = get_posts( array(
        'post_type'      => 'wpuf_forms',
        'posts_per_page' => -1,
        'post_status'    => array('publish', 'draft', 'pending'),
    ) );
    foreach ( $posts as $post ) {
        $forms[$post->ID] = $post->post_title;
    }
    return $forms;
}

// Get WPUF Form Fields
function adfoin_wpuf_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider !== 'wpuf' || !$form_id ) {
        return;
    }
    $fields = array(
        'post_id' => __( 'Post ID', 'advanced-form-integration' ),
    );
    if ( function_exists( 'wpuf_get_form_fields' ) ) {
        $form_fields = wpuf_get_form_fields( $form_id );
        if ( is_array( $form_fields ) ) {
            foreach ( $form_fields as $field ) {
                if ( empty( $field['name'] ) ) {
                    continue;
                }
                // WPUF (free) has a real 'email' input_type but no dedicated
                // first/last-name type — every text field is just a
                // site-owner-chosen postmeta key, indistinguishable from an
                // arbitrary field by type alone. Gate on email only; a full
                // name/email split isn't reliably possible here.
                $is_email = isset( $field['input_type'] ) && 'email' === $field['input_type'];
                if ( adfoin_fs()->is_not_paying() ) {
                    if ( !$is_email ) {
                        continue;
                    }
                }
                $fields[$field['name']] = ( !empty( $field['label'] ) ? $field['label'] : $field['name'] );
            }
        }
    }
    return $fields;
}

// WPUF stores post_title/post_content/post_excerpt/featured_image on native
// post columns/taxonomy rather than postmeta — resolved accordingly below.
function adfoin_wpuf_resolve_field_value(  $field_name, $post  ) {
    switch ( $field_name ) {
        case 'post_title':
            return $post->post_title;
        case 'post_content':
            return $post->post_content;
        case 'post_excerpt':
            return $post->post_excerpt;
        case 'featured_image':
            $thumb_id = get_post_thumbnail_id( $post->ID );
            return ( $thumb_id ? wp_get_attachment_url( $thumb_id ) : '' );
        default:
            return get_post_meta( $post->ID, $field_name, true );
    }
}

// Handle New Post Submitted
add_action(
    'wpuf_add_post_after_insert',
    'adfoin_wpuf_handle_post_submitted',
    10,
    4
);
function adfoin_wpuf_handle_post_submitted(
    $post_id,
    $form_id,
    $form_settings,
    $meta_vars
) {
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wpuf', $form_id );
    if ( empty( $saved_records ) ) {
        return;
    }
    $post = get_post( $post_id );
    if ( !$post ) {
        return;
    }
    $posted_data = array(
        'post_id' => $post_id,
    );
    if ( function_exists( 'wpuf_get_form_fields' ) ) {
        $form_fields = wpuf_get_form_fields( $form_id );
        if ( is_array( $form_fields ) ) {
            foreach ( $form_fields as $field ) {
                if ( empty( $field['name'] ) ) {
                    continue;
                }
                $is_email = isset( $field['input_type'] ) && 'email' === $field['input_type'];
                if ( adfoin_fs()->is_not_paying() ) {
                    if ( !$is_email ) {
                        continue;
                    }
                }
                $posted_data[$field['name']] = adfoin_wpuf_resolve_field_value( $field['name'], $post );
            }
        }
    }
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_wpuf_trigger_fields' );
}
function adfoin_wpuf_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'wpuf' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
