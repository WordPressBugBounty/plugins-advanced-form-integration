<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Depicter (Popup & Slider Builder) trigger — fires when a visitor submits
 * one of Depicter's native lead-capture forms.
 *
 * Confirmed against the plugin's own source (app/src/Services/LeadService.php,
 * add() method):
 *
 *     do_action( 'depicter/lead/created', $leadId, $sourceId, $contentId );
 *
 * fires only after the lead row AND every submitted field have already
 * saved successfully via LeadRepository/LeadFieldRepository — not on a
 * validation/recaptcha failure (those return early in
 * LeadsAjaxController::submit() before LeadService::add() is even called).
 * The hook itself only passes IDs, not the field values, so the fields are
 * re-fetched here via `Depicter::leadFieldRepository()`, using the exact
 * same {name, value} column shape confirmed in
 * LeadFieldRepository::create() (lead_id, name, value, type).
 *
 * Depicter forms/popups are individual widgets embeddable across several
 * page builders (Elementor, Divi, Beaver, Gutenberg, etc.) with no single
 * enumerable "list of forms" API, so — same reasoning as the Jetpack/Otter/
 * Hustle triggers — this is one site-wide trigger rather than a per-form
 * picker; source_id/content_id are exposed as fields so users can filter by
 * them downstream if they have multiple lead forms.
 *
 * @link https://plugins.trac.wordpress.org/browser/depicter/trunk/app/src/Services/LeadService.php
 * @link https://plugins.trac.wordpress.org/browser/depicter/trunk/app/src/Database/Repository/LeadFieldRepository.php
 */

add_action( 'plugins_loaded', 'adfoin_depicter_register_hooks', 20 );

function adfoin_depicter_register_hooks() {
    if ( ! class_exists( '\Depicter' ) ) {
        return;
    }

    add_action( 'depicter/lead/created', 'adfoin_depicter_handle_lead_created', 10, 3 );
}

// Get Depicter Triggers
function adfoin_depicter_get_forms( $form_provider ) {
    if ( $form_provider !== 'depicter' ) {
        return;
    }

    return array(
        'leadCreated' => __( 'Lead/Form Submitted (site-wide)', 'advanced-form-integration' ),
    );
}

// Get Depicter Fields
function adfoin_depicter_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'depicter' || $form_id !== 'leadCreated' ) {
        return;
    }

    return array(
        'lead_id'         => __( 'Lead ID', 'advanced-form-integration' ),
        'source_id'       => __( 'Source ID (which popup/slider)', 'advanced-form-integration' ),
        'content_id'      => __( 'Content ID (which page/post it was on)', 'advanced-form-integration' ),
        'email'           => __( 'Email (best-effort, from a field literally named "email")', 'advanced-form-integration' ),
        'all_fields_json' => __( 'All Submitted Fields (JSON, for fields not listed above)', 'advanced-form-integration' ),
    );
}

// Handle Lead Created
function adfoin_depicter_handle_lead_created( $leadId, $sourceId, $contentId ) {
    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'depicter', 'leadCreated' );

    if ( empty( $saved_records ) ) {
        return;
    }

    $all_fields = array();
    $email      = '';

    if ( method_exists( '\Depicter', 'leadFieldRepository' ) ) {
        try {
            $fields     = \Depicter::leadFieldRepository()->select( array( 'name', 'value' ) )->where( 'lead_id', '=', $leadId )->findAll()->get();
            $fields_arr = $fields ? $fields->toArray() : array();

            foreach ( $fields_arr as $field ) {
                $name = isset( $field['name'] ) ? $field['name'] : '';

                if ( '' === $name ) {
                    continue;
                }

                $value                = isset( $field['value'] ) ? $field['value'] : '';
                $all_fields[ $name ]  = $value;

                if ( 'email' === strtolower( $name ) ) {
                    $email = $value;
                }
            }
        } catch ( \Exception $e ) {
            // Leave $all_fields empty rather than fail the whole dispatch.
        }
    }

    $posted_data = array(
        'lead_id'         => $leadId,
        'source_id'       => $sourceId,
        'content_id'      => $contentId,
        'email'           => $email,
        'all_fields_json' => wp_json_encode( $all_fields ),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
