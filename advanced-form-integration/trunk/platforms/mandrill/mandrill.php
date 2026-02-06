<?php

add_filter( 'adfoin_action_providers', 'adfoin_mandrill_actions', 10, 1 );

function adfoin_mandrill_actions( $actions ) {
    $actions['mandrill'] = array(
        'title' => __( 'Mandrill (Mailchimp Transactional)', 'advanced-form-integration' ),
        'tasks' => array(
            'send_email' => __( 'Send Email', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mandrill_settings_tab', 10, 1 );

function adfoin_mandrill_settings_tab( $providers ) {
    $providers['mandrill'] = __( 'Mandrill (Mailchimp Transactional)', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mandrill_settings_view', 10, 1 );

function adfoin_mandrill_settings_view( $current_tab ) {
    if ( 'mandrill' !== $current_tab ) {
        return;
    }

    $title = __( 'Mandrill (Mailchimp Transactional)', 'advanced-form-integration' );
    $key   = 'mandrill';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'fromEmail', 'label' => __( 'Default From Email (optional)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'fromName', 'label' => __( 'Default From Name (optional)', 'advanced-form-integration' ), 'hidden' => false ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                </ol>
            </li>
            <li><strong>%5$s</strong>
                <ol>
                    <li>%6$s</li>
                    <li>%7$s</li>
                </ol>
            </li>
        </ol>
        <p>%8$s</p>',
        esc_html__( 'Create a Mandrill API key', 'advanced-form-integration' ),
        esc_html__( 'Sign in to Mailchimp, open Automations → Transactional → API Keys.', 'advanced-form-integration' ),
        esc_html__( 'Generate a new key with “Allow” status or reuse an existing one with Send permissions.', 'advanced-form-integration' ),
        esc_html__( 'Optionally decide on a default From address and name that match a verified sending domain.', 'advanced-form-integration' ),
        esc_html__( 'Store the key in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the API key above; you can add multiple Mandrill servers by repeating this step.', 'advanced-form-integration' ),
        esc_html__( 'Save to make the credentials available when configuring automations.', 'advanced-form-integration' ),
        esc_html__( 'AFI posts to https://mandrillapp.com/api/1.0 using your key in the payload so keep it private.' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_mandrill_action_fields' );

function adfoin_mandrill_action_fields() {
    ?>
    <script type="text/template" id="mandrill-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Mandrill Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mandrill_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mandrill_credentials', 'adfoin_get_mandrill_credentials' );

function adfoin_get_mandrill_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'mandrill' ) );
}

add_action( 'wp_ajax_adfoin_save_mandrill_credentials', 'adfoin_save_mandrill_credentials' );

function adfoin_save_mandrill_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'mandrill' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'mandrill', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_mandrill_fields', 'adfoin_get_mandrill_fields' );

function adfoin_get_mandrill_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'to', 'value' => __( 'Recipients (comma separated)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'toName', 'value' => __( 'Recipient Name', 'advanced-form-integration' ) ),
        array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'htmlBody', 'value' => __( 'HTML Body', 'advanced-form-integration' ) ),
        array( 'key' => 'textBody', 'value' => __( 'Text Body', 'advanced-form-integration' ) ),
        array( 'key' => 'fromEmail', 'value' => __( 'From Email', 'advanced-form-integration' ) ),
        array( 'key' => 'fromName', 'value' => __( 'From Name', 'advanced-form-integration' ) ),
        array( 'key' => 'replyTo', 'value' => __( 'Reply-To', 'advanced-form-integration' ) ),
        array( 'key' => 'tags', 'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_mandrill_job_queue', 'adfoin_mandrill_job_queue', 10, 1 );

function adfoin_mandrill_job_queue( $data ) {
    adfoin_mandrill_send_email( $data['record'], $data['posted_data'] );
}

function adfoin_mandrill_send_email( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $fields = array();
    foreach ( $data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    $to      = isset( $fields['to'] ) ? $fields['to'] : '';
    $subject = isset( $fields['subject'] ) ? $fields['subject'] : '';

    if ( ! $to || ! $subject ) {
        return;
    }

    $credentials  = adfoin_get_credentials_by_id( 'mandrill', $cred_id );
    $default_from = isset( $credentials['fromEmail'] ) ? $credentials['fromEmail'] : '';
    $default_name = isset( $credentials['fromName'] ) ? $credentials['fromName'] : '';

    $from_email = isset( $fields['fromEmail'] ) && $fields['fromEmail'] ? $fields['fromEmail'] : $default_from;

    if ( ! $from_email ) {
        return;
    }

    $from_name = isset( $fields['fromName'] ) && $fields['fromName'] ? $fields['fromName'] : $default_name;

    $message = array(
        'subject'    => $subject,
        'from_email' => $from_email,
        'to'         => adfoin_mandrill_format_recipients( $to, $fields['toName'] ?? '' ),
    );

    if ( $from_name ) {
        $message['from_name'] = $from_name;
    }

    if ( isset( $fields['htmlBody'] ) && $fields['htmlBody'] ) {
        $message['html'] = $fields['htmlBody'];
    }

    if ( isset( $fields['textBody'] ) && $fields['textBody'] ) {
        $message['text'] = $fields['textBody'];
    }

    if ( empty( $message['html'] ) && empty( $message['text'] ) ) {
        return;
    }

    if ( isset( $fields['replyTo'] ) && $fields['replyTo'] ) {
        $message['headers']['Reply-To'] = $fields['replyTo'];
    }

    if ( isset( $fields['tags'] ) && $fields['tags'] ) {
        $tags = array_filter( array_map( 'trim', explode( ',', $fields['tags'] ) ) );
        if ( $tags ) {
            $message['tags'] = array_slice( $tags, 0, 50 );
        }
    }

    $payload = array(
        'key'     => $credentials['apiKey'],
        'message' => $message,
    );

    adfoin_mandrill_request( 'messages/send', 'POST', $payload, $record, $cred_id );
}

function adfoin_mandrill_format_recipients( $addresses, $name = '' ) {
    $list  = array_filter( array_map( 'trim', explode( ',', $addresses ) ) );
    $name  = trim( $name );
    $users = array();

    foreach ( $list as $email ) {
        $recipient = array(
            'email' => $email,
            'type'  => 'to',
        );

        if ( $name ) {
            $recipient['name'] = $name;
        }

        $users[] = $recipient;
    }

    return $users;
}

function adfoin_mandrill_credentials_list() {
    foreach ( adfoin_read_credentials( 'mandrill' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

if ( ! function_exists( 'adfoin_mandrill_request' ) ) :
function adfoin_mandrill_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mandrill', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Mandrill credentials not found.', 'advanced-form-integration' ) );
    }

    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) {
        return new WP_Error( 'missing_key', __( 'Mandrill API key missing.', 'advanced-form-integration' ) );
    }

    if ( ! isset( $data['key'] ) ) {
        $data['key'] = $api_key;
    }

    $url = 'https://mandrillapp.com/api/1.0/' . ltrim( $endpoint, '/' ) . '.json';

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body'    => wp_json_encode( $data ),
    );

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
