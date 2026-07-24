<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WP-Polls trigger — fires when a visitor casts a poll vote.
 *
 * Confirmed against the plugin's own source (wp-polls.php, ajax_vote_poll()
 * function):
 *
 *     do_action( 'wp_polls_vote_poll_success' );
 *
 * fires only after the vote counters have already been updated in the
 * database (both the per-answer polla_votes and pollq_totalvotes/
 * pollq_totalvoters queries succeeded — a failure throws an exception
 * before this point is reached). The hook itself takes no arguments, so
 * poll_id and the selected answer ID(s) are read from $_POST the same way
 * the plugin's own vote handler does (`$_POST['poll_id']` and the dynamic
 * `$_POST["poll_{$poll_id}"]` key) — safe here since the hook fires
 * synchronously within the same request. Answer/question text is resolved
 * via `$wpdb->pollsq` / `$wpdb->pollsa`, which the plugin registers as real
 * $wpdb properties at load time (`$wpdb->pollsq = $wpdb->prefix.'pollsq'`),
 * not a guessed table name.
 *
 * @link https://plugins.trac.wordpress.org/browser/wp-polls/trunk/wp-polls.php
 */

add_action( 'plugins_loaded', 'adfoin_wppolls_register_hooks', 20 );

function adfoin_wppolls_register_hooks() {
    if ( ! defined( 'WP_POLLS_VERSION' ) ) {
        return;
    }

    add_action( 'wp_polls_vote_poll_success', 'adfoin_wppolls_handle_vote', 10, 0 );
}

// Get WP-Polls Triggers
function adfoin_wppolls_get_forms( $form_provider ) {
    if ( $form_provider !== 'wppolls' ) {
        return;
    }

    return array(
        'pollVoted' => __( 'Poll Voted', 'advanced-form-integration' ),
    );
}

// Get WP-Polls Fields
function adfoin_wppolls_get_form_fields( $form_provider, $form_id ) {
    if ( $form_provider !== 'wppolls' || $form_id !== 'pollVoted' ) {
        return;
    }

    return array(
        'poll_id'          => __( 'Poll ID', 'advanced-form-integration' ),
        'question'         => __( 'Poll Question', 'advanced-form-integration' ),
        'selected_answers' => __( 'Selected Answer(s)', 'advanced-form-integration' ),
        'user_ip'          => __( 'Visitor IP', 'advanced-form-integration' ),
    );
}

// Handle Poll Voted
function adfoin_wppolls_handle_vote() {
    $poll_id = isset( $_POST['poll_id'] ) ? absint( $_POST['poll_id'] ) : 0;

    if ( ! $poll_id ) {
        return;
    }

    $integration   = new Advanced_Form_Integration_Integration();
    $saved_records = $integration->get_by_trigger( 'wppolls', 'pollVoted' );

    if ( empty( $saved_records ) ) {
        return;
    }

    global $wpdb;

    $question = $wpdb->get_var( $wpdb->prepare( "SELECT pollq_question FROM {$wpdb->pollsq} WHERE pollq_id = %d", $poll_id ) );

    $answer_ids_raw = isset( $_POST[ 'poll_' . $poll_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'poll_' . $poll_id ] ) ) : '';
    $answer_ids     = array_filter( array_map( 'intval', explode( ',', $answer_ids_raw ) ) );

    $selected_answers = '';

    if ( ! empty( $answer_ids ) ) {
        $placeholders     = implode( ',', array_fill( 0, count( $answer_ids ), '%d' ) );
        $answer_texts     = $wpdb->get_col( $wpdb->prepare( "SELECT polla_answers FROM {$wpdb->pollsa} WHERE polla_aid IN ({$placeholders})", $answer_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $selected_answers = implode( ', ', $answer_texts );
    }

    $posted_data = array(
        'poll_id'          => $poll_id,
        'question'         => $question,
        'selected_answers' => $selected_answers,
        'user_ip'          => adfoin_get_user_ip(),
    );

    adfoin_dispatch_integrations( $saved_records, $posted_data );
}
