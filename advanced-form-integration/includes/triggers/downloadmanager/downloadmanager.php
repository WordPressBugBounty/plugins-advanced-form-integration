<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Download Manager trigger — fires once a file has been fully streamed to
 * the visitor (a real "lead magnet downloaded" event — e.g. someone
 * downloading a gated PDF).
 *
 * Confirmed against the plugin's own source (src/wpdm-start-download.php):
 *
 *     do_action( 'after_download', $package );
 *
 * fires right before the script dies, after `wpdm_download_file()` has
 * already streamed the file — deliberately not the earlier
 * `wpdm_onstart_download` hook, which fires before the IP/blocked-email
 * checks and the actual file streaming, so it could fire even for a
 * download that gets rejected or interrupted. $package is a plain array
 * (accessed as $package['ID'] in the plugin's own code); the global
 * $current_user (set via wp_get_current_user() at the top of the same
 * file) is used to resolve the downloader's identity when logged in —
 * empty for anonymous downloads, which is expected unless the site owner
 * has gated the package behind login/registration.
 *
 * @link https://plugins.trac.wordpress.org/browser/download-manager/trunk/src/wpdm-start-download.php
 */

add_action( 'plugins_loaded', 'adfoin_downloadmanager_register_hooks', 20 );

function adfoin_downloadmanager_register_hooks() {
    if ( ! function_exists( 'WPDM' ) ) {
        return;
    }

    add_action( 'after_download', 'adfoin_downloadmanager_handle_download', 10, 1 );
}

// Get Download Manager Triggers
function adfoin_downloadmanager_get_forms( $form_provider ) {
    if ( $form_provider !== 'downloadmanager' ) {
        return;
    }

    return array(
        'fileDownloaded' => __( 'File Downloaded', 'advanced-form-integration' ),
    );
}

// Get Download Manager Fields
function adfoin_downloadmanager_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'downloadmanager' || $form_id !== 'fileDownloaded' ) {
        return;
    }

    return array(
        'package_id'    => __( 'Package/File ID', 'advanced-form-integration' ),
        'package_title' => __( 'Package/File Title', 'advanced-form-integration' ),
        'package_url'   => __( 'Package/File Page URL', 'advanced-form-integration' ),
        'user_id'       => __( 'User ID (only if logged in)', 'advanced-form-integration' ),
        'user_email'    => __( 'User Email (only if logged in)', 'advanced-form-integration' ),
        'client_ip'     => __( 'Client IP', 'advanced-form-integration' ),
        'download_time' => __( 'Download Time', 'advanced-form-integration' ),
    );
}

// Handle File Downloaded
function adfoin_downloadmanager_handle_download( $package ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'downloadmanager', 'fileDownloaded' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $package_id = ( is_array( $package ) && isset( $package['ID'] ) ) ? $package['ID'] : 0;
    $user       = wp_get_current_user();

    $posted_data = array(
        'package_id'    => $package_id,
        'package_title' => $package_id ? get_the_title( $package_id ) : '',
        'package_url'   => $package_id ? get_permalink( $package_id ) : '',
        'user_id'       => ( $user && $user->ID ) ? $user->ID : '',
        'user_email'    => ( $user && $user->ID ) ? $user->user_email : '',
        'client_ip'     => function_exists( 'wpdm_get_client_ip' ) ? wpdm_get_client_ip() : '',
        'download_time' => current_time( 'mysql' ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
