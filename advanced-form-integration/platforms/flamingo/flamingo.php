<?php

/**
 * Flamingo action platform — local same-site integration (no REST/API
 * keys). Flamingo (800k+ active installs, companion to Contact Form 7 but
 * usable standalone) stores contacts and inbound messages via two real
 * static methods, confirmed against the plugin's own source:
 *
 * - Flamingo_Contact::add( $args ) (includes/class-contact.php) — $args
 *   needs 'email' (required, validated) and 'name'; returns a Flamingo_Contact
 *   object or null if the email is empty/invalid.
 * - Flamingo_Inbound_Message::add( $args ) (includes/class-inbound-message.php)
 *   — accepts 'channel', 'subject', 'from_name', 'from_email', and 'fields'
 *   (an arbitrary associative array of the submitted data); always returns a
 *   new Flamingo_Inbound_Message object.
 *
 * @link https://plugins.trac.wordpress.org/browser/flamingo/trunk/includes/class-contact.php
 * @link https://plugins.trac.wordpress.org/browser/flamingo/trunk/includes/class-inbound-message.php
 */

add_filter( 'adfoin_action_providers', 'adfoin_flamingo_actions', 10, 1 );

function adfoin_flamingo_actions( $actions ) {

    $actions['flamingo'] = array(
        'title' => __( 'Flamingo', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add Contact', 'advanced-form-integration' ),
            'log_message' => __( 'Log Message', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_flamingo_action_fields' );

function adfoin_flamingo_action_fields() {
    ?>
    <script type="text/template" id="flamingo-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact' || action.task == 'log_message'">
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

add_action( 'wp_ajax_adfoin_get_flamingo_fields', 'adfoin_get_flamingo_fields', 10, 0 );

function adfoin_get_flamingo_fields() {
    adfoin_verify_nonce();

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';

    if ( 'log_message' === $task ) {
        $fields = array(
            array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'description' => '' ),
            array( 'key' => 'from_name', 'value' => __( 'From Name', 'advanced-form-integration' ), 'description' => '' ),
            array( 'key' => 'from_email', 'value' => __( 'From Email', 'advanced-form-integration' ), 'description' => '' ),
            array( 'key' => 'message', 'value' => __( 'Message', 'advanced-form-integration' ), 'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'description' => __( 'Required.', 'advanced-form-integration' ) ),
            array( 'key' => 'name', 'value' => __( 'Name', 'advanced-form-integration' ), 'description' => '' ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_flamingo_job_queue', 'adfoin_flamingo_job_queue', 10, 1 );

function adfoin_flamingo_job_queue( $data ) {
    adfoin_flamingo_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles creating a Flamingo contact or inbound message
 */
function adfoin_flamingo_send_data( $record, $posted_data ) {

    if ( ! class_exists( 'Flamingo_Contact' ) && ! class_exists( 'Flamingo_Inbound_Message' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

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

    if ( 'add_contact' === $task ) {
        if ( empty( $prepared_data['email'] ) || ! is_email( $prepared_data['email'] ) ) {
            $response_body['message'] = __( 'A valid email address is required.', 'advanced-form-integration' );
        } elseif ( ! class_exists( 'Flamingo_Contact' ) ) {
            $response_body['message'] = __( 'Flamingo is not active.', 'advanced-form-integration' );
        } else {
            $contact = Flamingo_Contact::add(
                array(
                    'email' => $prepared_data['email'],
                    'name'  => isset( $prepared_data['name'] ) ? $prepared_data['name'] : '',
                )
            );

            if ( $contact ) {
                $status_code              = 200;
                $response_body['success'] = true;
                $response_body['message'] = __( 'Contact added successfully.', 'advanced-form-integration' );
                $response_body['id']      = $contact->id;
            } else {
                $response_body['message'] = __( 'Failed to add the contact. Please verify the supplied email.', 'advanced-form-integration' );
            }
        }
    } elseif ( 'log_message' === $task ) {
        if ( ! class_exists( 'Flamingo_Inbound_Message' ) ) {
            $response_body['message'] = __( 'Flamingo is not active.', 'advanced-form-integration' );
        } else {
            $message = Flamingo_Inbound_Message::add(
                array(
                    'channel'    => __( 'Advanced Form Integration', 'advanced-form-integration' ),
                    'subject'    => isset( $prepared_data['subject'] ) ? $prepared_data['subject'] : '',
                    'from_name'  => isset( $prepared_data['from_name'] ) ? $prepared_data['from_name'] : '',
                    'from_email' => isset( $prepared_data['from_email'] ) ? $prepared_data['from_email'] : '',
                    'fields'     => $posted_data,
                )
            );

            if ( $message ) {
                $status_code              = 200;
                $response_body['success'] = true;
                $response_body['message'] = __( 'Message logged successfully.', 'advanced-form-integration' );
                $response_body['id']      = $message->id;
            } else {
                $response_body['message'] = __( 'Failed to log the message.', 'advanced-form-integration' );
            }
        }
    } else {
        return;
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

    adfoin_add_to_log( $log_response, 'flamingo', $log_args, $record );
}
