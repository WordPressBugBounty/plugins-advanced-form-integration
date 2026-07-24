<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Download Monitor trigger — fires once a file download has passed all
 * access checks (a genuine "lead magnet downloaded" event).
 *
 * Confirmed against the plugin's own source (src/DownloadHandler.php),
 * matching the plugin's own official action/filter reference docs:
 *
 *     do_action( 'dlm_downloading', $download, $version, $file_path );
 *
 * fires only after every "no access" branch above it has already
 * exited/redirected — i.e. the visitor has been confirmed allowed to
 * download — and right before the plugin logs the download itself. $download
 * is a Download\Download object with get_id()/get_title()/etc.; identity
 * (user_id/email) is resolved via wp_get_current_user() since Download
 * Monitor doesn't require login/registration by default (empty for
 * anonymous downloads, which is expected).
 *
 * @link https://plugins.trac.wordpress.org/browser/download-monitor/trunk/src/DownloadHandler.php
 * @link https://www.download-monitor.com/kb/action-and-filter-reference/
 */

add_action( 'plugins_loaded', 'adfoin_downloadmonitor_register_hooks', 20 );

function adfoin_downloadmonitor_register_hooks() {
    if ( ! defined( 'DLM_VERSION' ) ) {
        return;
    }

    add_action( 'dlm_downloading', 'adfoin_downloadmonitor_handle_download', 10, 3 );
}

// Get Download Monitor Triggers
function adfoin_downloadmonitor_get_forms( $form_provider ) {
    if ( $form_provider !== 'downloadmonitor' ) {
        return;
    }

    return array(
        'fileDownloaded' => __( 'File Downloaded', 'advanced-form-integration' ),
    );
}

// Get Download Monitor Fields
function adfoin_downloadmonitor_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'downloadmonitor' || $form_id !== 'fileDownloaded' ) {
        return;
    }

    return array(
        'download_id'    => __( 'Download ID', 'advanced-form-integration' ),
        'download_title' => __( 'Download Title', 'advanced-form-integration' ),
        'download_url'   => __( 'Download Page URL', 'advanced-form-integration' ),
        'user_id'        => __( 'User ID (only if logged in)', 'advanced-form-integration' ),
        'user_email'     => __( 'User Email (only if logged in)', 'advanced-form-integration' ),
        'download_time'  => __( 'Download Time', 'advanced-form-integration' ),
    );
}

// Handle File Downloaded
function adfoin_downloadmonitor_handle_download( $download, $version, $file_path ) {
    if ( ! is_object( $download ) || ! method_exists( $download, 'get_id' ) ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'downloadmonitor', 'fileDownloaded' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $user = wp_get_current_user();

    $posted_data = array(
        'download_id'    => $download->get_id(),
        'download_title' => method_exists( $download, 'get_title' ) ? $download->get_title() : '',
        'download_url'   => get_permalink( $download->get_id() ),
        'user_id'        => ( $user && $user->ID ) ? $user->ID : '',
        'user_email'     => ( $user && $user->ID ) ? $user->user_email : '',
        'download_time'  => current_time( 'mysql' ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
