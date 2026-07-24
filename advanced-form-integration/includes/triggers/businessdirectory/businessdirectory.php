<?php

/**
 * Business Directory Plugin trigger — fires when a new directory listing
 * is submitted.
 *
 * Confirmed against the plugin's own source (core/class-listing.php,
 * core/class-listings-api.php): the real "new listing" event is the
 * class-scoped action `WPBDP_Listing::listing_created`, fired via
 * do_action_ref_array() with a single by-reference argument — the
 * WPBDP_Listing instance. This is the same hook the plugin's own core
 * code uses for its built-in "new listing" notification emails, so it is
 * the correct place to hook rather than a generic post-status transition.
 *
 * Fields are enumerated site-wide via wpbdp_formfields_api()->get_fields()
 * (covers standard + category-specific custom fields), and raw values are
 * read back with WPBDP_Form_Field::value( $post_id, true ) — the `true`
 * skips the `wpbdp_form_field_value` display filter so we get the stored
 * value, not HTML-rendered output.
 *
 * @link https://businessdirectoryplugin.com/
 */
// Get Business Directory Plugin Triggers
function adfoin_businessdirectory_get_forms(  $form_provider  ) {
    if ( $form_provider !== 'businessdirectory' ) {
        return;
    }
    return array(
        'listingSubmitted' => __( 'New Listing Submitted', 'advanced-form-integration' ),
    );
}

// Get Business Directory Plugin Fields
function adfoin_businessdirectory_get_form_fields(  $form_provider, $form_id  ) {
    if ( $form_provider !== 'businessdirectory' || $form_id !== 'listingSubmitted' ) {
        return;
    }
    $fields = array(
        'listing_id'    => __( 'Listing ID', 'advanced-form-integration' ),
        'listing_title' => __( 'Listing Title', 'advanced-form-integration' ),
    );
    if ( function_exists( 'wpbdp_formfields_api' ) ) {
        foreach ( wpbdp_formfields_api()->get_fields() as $field ) {
            if ( !is_object( $field ) || !method_exists( $field, 'get_id' ) ) {
                continue;
            }
            // WPBDP has no dedicated "name" field type (a listing's name is
            // always the post title, already free above) and no dedicated
            // "email" type either — Email is a *validator* on an ordinary
            // text field (has_validator('email')), confirmed against the
            // plugin's own core/class-form-field.php. That's the only
            // reliable free-vs-pro signal available here.
            $is_email = method_exists( $field, 'has_validator' ) && $field->has_validator( 'email' );
            if ( adfoin_fs()->is_not_paying() ) {
                if ( $is_email ) {
                    $fields['field_' . $field->get_id()] = $field->get_label();
                }
            }
        }
    }
    return $fields;
}

// Handle New Listing Submitted
add_action(
    'WPBDP_Listing::listing_created',
    'adfoin_businessdirectory_handle_listing_created',
    10,
    1
);
function adfoin_businessdirectory_handle_listing_created(  $listing  ) {
    if ( !is_object( $listing ) || !method_exists( $listing, 'get_id' ) ) {
        return;
    }
    $integration = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'businessdirectory', 'listingSubmitted' );
    if ( empty( $saved_records ) ) {
        return;
    }
    $listing_id = $listing->get_id();
    $post = get_post( $listing_id );
    $posted_data = array(
        'listing_id'    => $listing_id,
        'listing_title' => ( $post ? $post->post_title : '' ),
    );
    if ( function_exists( 'wpbdp_formfields_api' ) ) {
        foreach ( wpbdp_formfields_api()->get_fields() as $field ) {
            if ( !is_object( $field ) || !method_exists( $field, 'get_id' ) || !method_exists( $field, 'value' ) ) {
                continue;
            }
            $is_email = method_exists( $field, 'has_validator' ) && $field->has_validator( 'email' );
            if ( adfoin_fs()->is_not_paying() ) {
                if ( !$is_email ) {
                    continue;
                }
            }
            $posted_data['field_' . $field->get_id()] = $field->value( $listing_id, true );
        }
    }
    $posted_data['post_id'] = $listing_id;
    adfoin_dispatch_integrations( $saved_records, $posted_data );
}

if ( adfoin_fs()->is_not_paying() ) {
    add_action( 'adfoin_trigger_extra_fields', 'adfoin_businessdirectory_trigger_fields' );
}
function adfoin_businessdirectory_trigger_fields() {
    ?>
    <div class="afi-upgrade-notice" v-if="trigger.formProviderId == 'businessdirectory' && trigger.formId">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <p><?php 
    esc_html_e( 'The basic AFI plugin supports the listing title and email fields only.', 'advanced-form-integration' );
    ?></p>
    </div>
    <?php 
}
