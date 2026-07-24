<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PrettyLinks trigger — fires when a tracked short link is clicked.
 *
 * Confirmed against the plugin's own source
 * (src/Redirect/ClickWriter.php), with a proper docblock declaring it a
 * real public extension point:
 *
 *     do_action( 'prli_click_written', $linkId, $clickId, $url );
 *
 * fires right after the click row is inserted (does NOT fire in
 * count-only tracking mode, since there's no row to correlate to). The
 * hook itself only passes the link ID, click ID, and the resolved outbound
 * URL — link name/slug/pretty-URL are resolved below via the plugin's own
 * `PrettyLinks\Repositories\Links::find()` repository method rather than
 * querying its `prli_links` table directly, since the plugin just went
 * through a full v4 rewrite of its data model and this is the one place
 * that's documented as the stable read path for a link by ID.
 *
 * @link https://plugins.trac.wordpress.org/browser/pretty-link/trunk/src/Redirect/ClickWriter.php
 * @link https://plugins.trac.wordpress.org/browser/pretty-link/trunk/src/Repositories/Links.php
 */

add_action( 'plugins_loaded', 'adfoin_prettylinks_register_hooks', 20 );

function adfoin_prettylinks_register_hooks() {
    if ( ! defined( 'PRLI_FILE' ) ) {
        return;
    }

    add_action( 'prli_click_written', 'adfoin_prettylinks_handle_click', 10, 3 );
}

// Get PrettyLinks Triggers
function adfoin_prettylinks_get_forms( $form_provider ) {
    if ( $form_provider !== 'prettylinks' ) {
        return;
    }

    return array(
        'linkClicked' => __( 'Link Clicked', 'advanced-form-integration' ),
    );
}

// Get PrettyLinks Fields
function adfoin_prettylinks_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'prettylinks' || $form_id !== 'linkClicked' ) {
        return;
    }

    return array(
        'link_id'      => __( 'Link ID', 'advanced-form-integration' ),
        'link_name'     => __( 'Link Name', 'advanced-form-integration' ),
        'link_slug'     => __( 'Link Slug', 'advanced-form-integration' ),
        'pretty_url'    => __( 'Pretty (Short) URL', 'advanced-form-integration' ),
        'target_url'    => __( 'Target URL (destination clicked through to)', 'advanced-form-integration' ),
        'click_id'      => __( 'Click ID', 'advanced-form-integration' ),
        'total_clicks'  => __( 'Total Clicks (running count for this link)', 'advanced-form-integration' ),
    );
}

// Handle Link Clicked
function adfoin_prettylinks_handle_click( $linkId, $clickId, $url ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'prettylinks', 'linkClicked' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $link = null;

    if ( class_exists( '\PrettyLinks\Repositories\Links' ) ) {
        $repo = new \PrettyLinks\Repositories\Links();
        $link = $repo->find( (int) $linkId );
    }

    $posted_data = array(
        'link_id'      => $linkId,
        'link_name'    => $link ? $link['name'] : '',
        'link_slug'    => $link ? $link['slug'] : '',
        'pretty_url'   => $link ? $link['pretty_url'] : '',
        'target_url'   => $url,
        'click_id'     => $clickId,
        'total_clicks' => $link ? $link['clicks'] : '',
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
