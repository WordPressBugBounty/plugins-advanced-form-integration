<?php

add_filter( 'adfoin_action_providers', 'adfoin_postmark_actions', 10, 1 );

function adfoin_postmark_actions( $actions ) {
    $actions['postmark'] = array(
        'title' => __( 'Postmark', 'advanced-form-integration' ),
        'tasks' => array(
            'send_email' => __( 'Send Email (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_postmark_settings_tab', 10, 1 );

function adfoin_postmark_settings_tab( $providers ) {
    $providers['postmark'] = __( 'Postmark', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_postmark_settings_view', 10, 1 );

function adfoin_postmark_settings_view( $current_tab ) {
    if ( 'postmark' !== $current_tab ) {
        return;
    }

    $title = __( 'Postmark', 'advanced-form-integration' );
    $key   = 'postmark';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'serverToken', 'label' => __( 'Server Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'messageStream', 'label' => __( 'Message Stream ID (optional)', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'fromEmail', 'label' => __( 'Default From Email (optional)', 'advanced-form-integration' ), 'hidden' => false ),
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
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Generate a server token', 'advanced-form-integration' ),
        esc_html__( 'Log in to Postmark, open Server → API Tokens.', 'advanced-form-integration' ),
        esc_html__( 'Create (or copy) a Server Token that can send on the desired Message Stream.', 'advanced-form-integration' ),
        esc_html__( 'Optionally note the Message Stream ID (e.g., “outbound” or a custom stream).', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the server token into the field above. AFI saves it securely and reuses it per integration.', 'advanced-form-integration' ),
        esc_html__( 'You can define a default “From” address and stream to simplify mappings; both can be overridden per action.', 'advanced-form-integration' ),
        esc_html__( 'AFI calls https://api.postmarkapp.com/email with the Server Token set as the X-Postmark-Server-Token header.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Postmark [PRO] to send template-based emails, add metadata, and include attachments.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_postmark_action_fields' );

function adfoin_postmark_action_fields() {
    ?>
    <script type="text/template" id="postmark-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Postmark Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_postmark_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need templates?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Postmark [PRO]</a> to send template emails, add metadata, and include attachments.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_postmark_credentials', 'adfoin_get_postmark_credentials' );

function adfoin_get_postmark_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'postmark' ) );
}

add_action( 'wp_ajax_adfoin_save_postmark_credentials', 'adfoin_save_postmark_credentials' );

function adfoin_save_postmark_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'postmark' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'postmark', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_postmark_fields', 'adfoin_get_postmark_fields' );

function adfoin_get_postmark_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'to', 'value' => __( 'To (comma separated)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'from', 'value' => __( 'From Email', 'advanced-form-integration' ) ),
        array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'htmlBody', 'value' => __( 'HTML Body', 'advanced-form-integration' ) ),
        array( 'key' => 'textBody', 'value' => __( 'Text Body', 'advanced-form-integration' ) ),
        array( 'key' => 'replyTo', 'value' => __( 'Reply-To', 'advanced-form-integration' ) ),
        array( 'key' => 'tag', 'value' => __( 'Tag', 'advanced-form-integration' ) ),
        array( 'key' => 'messageStream', 'value' => __( 'Override Message Stream', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_postmark_job_queue', 'adfoin_postmark_job_queue', 10, 1 );

function adfoin_postmark_job_queue( $data ) {
    adfoin_postmark_send_email( $data['record'], $data['posted_data'] );
}

function adfoin_postmark_send_email( $record, $posted_data ) {
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

    if ( empty( $fields['to'] ) ) {
        return;
    }

    $payload = array(
        'To'      => $fields['to'],
        'Subject' => isset( $fields['subject'] ) ? $fields['subject'] : '',
    );

    $credentials = adfoin_get_credentials_by_id( 'postmark', $cred_id );
    $default_from = isset( $credentials['fromEmail'] ) ? $credentials['fromEmail'] : '';

    $payload['From'] = isset( $fields['from'] ) && $fields['from'] ? $fields['from'] : $default_from;

    if ( empty( $payload['From'] ) ) {
        return;
    }

    if ( isset( $fields['htmlBody'] ) ) {
        $payload['HtmlBody'] = $fields['htmlBody'];
    }

    if ( isset( $fields['textBody'] ) ) {
        $payload['TextBody'] = $fields['textBody'];
    }

    if ( empty( $payload['HtmlBody'] ) && empty( $payload['TextBody'] ) ) {
        return;
    }

    if ( isset( $fields['replyTo'] ) ) {
        $payload['ReplyTo'] = $fields['replyTo'];
    }

    if ( isset( $fields['tag'] ) ) {
        $payload['Tag'] = $fields['tag'];
    }

    $stream = isset( $fields['messageStream'] ) ? $fields['messageStream'] : '';

    if ( ! $stream && isset( $credentials['messageStream'] ) ) {
        $stream = $credentials['messageStream'];
    }

    if ( $stream ) {
        $payload['MessageStream'] = $stream;
    }

    adfoin_postmark_request( 'email', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_postmark_request' ) ) :
function adfoin_postmark_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'postmark', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Postmark credentials not found.', 'advanced-form-integration' ) );
    }

    $token = isset( $credentials['serverToken'] ) ? $credentials['serverToken'] : '';

    if ( ! $token ) {
        return new WP_Error( 'missing_token', __( 'Postmark Server Token missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.postmarkapp.com/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Accept'                   => 'application/json',
            'Content-Type'             => 'application/json',
            'X-Postmark-Server-Token'  => $token,
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

function adfoin_postmark_credentials_list() {
    foreach ( adfoin_read_credentials( 'postmark' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
