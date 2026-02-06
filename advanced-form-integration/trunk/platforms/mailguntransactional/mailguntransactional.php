<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailguntransactional_actions', 10, 1 );

function adfoin_mailguntransactional_actions( $actions ) {
    $actions['mailguntransactional'] = array(
        'title' => __( 'Mailgun Transactional', 'advanced-form-integration' ),
        'tasks' => array(
            'send_email' => __( 'Send Email (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailguntransactional_settings_tab', 10, 1 );

function adfoin_mailguntransactional_settings_tab( $providers ) {
    $providers['mailguntransactional'] = __( 'Mailgun Transactional', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailguntransactional_settings_view', 10, 1 );

function adfoin_mailguntransactional_settings_view( $current_tab ) {
    if ( 'mailguntransactional' !== $current_tab ) {
        return;
    }

    $title = __( 'Mailgun Transactional', 'advanced-form-integration' );
    $key   = 'mailguntransactional';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'Private API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'domain', 'label' => __( 'Sending Domain', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'region', 'label' => __( 'Region (US or EU)', 'advanced-form-integration' ), 'hidden' => false ),
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
                    <li>%8$s</li>
                </ol>
            </li>
        </ol>
        <p>%9$s</p>
        <p>%10$s</p>',
        esc_html__( 'Create a restricted API key', 'advanced-form-integration' ),
        esc_html__( 'Open https://app.mailgun.com/app/account/security/api_keys and create (or reuse) a Private API key with sending permissions.', 'advanced-form-integration' ),
        esc_html__( 'Locate the sending domain you wish to use under Sending → Domains.', 'advanced-form-integration' ),
        esc_html__( 'Note whether the domain lives in the US (api.mailgun.net) or EU (api.eu.mailgun.net) region.', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Add the API key, domain, and region to the fields above. Optionally define a default “From” address.', 'advanced-form-integration' ),
        esc_html__( 'Save the credentials; you can add multiple domains by creating additional entries.', 'advanced-form-integration' ),
        esc_html__( 'Use the same credential when configuring actions via the integration builder.', 'advanced-form-integration' ),
        esc_html__( 'AFI posts to https://api.mailgun.net/v3/{domain}/messages (or api.eu) with your key using HTTP Basic auth.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Mailgun Transactional [PRO] to add CC/BCC, tags, variables, and template-based sends.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_mailguntransactional_action_fields' );

function adfoin_mailguntransactional_action_fields() {
    ?>
    <script type="text/template" id="mailguntransactional-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Mailgun Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mailguntransactional_credentials_list(); ?>
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
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Mailgun Transactional [PRO]</a> for templates, tags, and metadata support.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_mailguntransactional_credentials', 'adfoin_get_mailguntransactional_credentials' );

function adfoin_get_mailguntransactional_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'mailguntransactional' ) );
}

add_action( 'wp_ajax_adfoin_save_mailguntransactional_credentials', 'adfoin_save_mailguntransactional_credentials' );

function adfoin_save_mailguntransactional_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'mailguntransactional' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'mailguntransactional', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_mailguntransactional_fields', 'adfoin_get_mailguntransactional_fields' );

function adfoin_get_mailguntransactional_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'to', 'value' => __( 'To (comma separated)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'from', 'value' => __( 'From Email', 'advanced-form-integration' ) ),
        array( 'key' => 'subject', 'value' => __( 'Subject', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'textBody', 'value' => __( 'Text Body', 'advanced-form-integration' ) ),
        array( 'key' => 'htmlBody', 'value' => __( 'HTML Body', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_mailguntransactional_job_queue', 'adfoin_mailguntransactional_job_queue', 10, 1 );

function adfoin_mailguntransactional_job_queue( $data ) {
    adfoin_mailguntransactional_send_email( $data['record'], $data['posted_data'] );
}

function adfoin_mailguntransactional_send_email( $record, $posted_data ) {
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

    $to = isset( $fields['to'] ) ? $fields['to'] : '';
    $subject = isset( $fields['subject'] ) ? $fields['subject'] : '';

    if ( ! $to || ! $subject ) {
        return;
    }

    $credentials  = adfoin_get_credentials_by_id( 'mailguntransactional', $cred_id );
    $default_from = isset( $credentials['fromEmail'] ) ? $credentials['fromEmail'] : '';
    $from         = isset( $fields['from'] ) && $fields['from'] ? $fields['from'] : $default_from;

    if ( ! $from ) {
        return;
    }

    $body = array(
        'from'    => $from,
        'to'      => $to,
        'subject' => $subject,
    );

    if ( isset( $fields['textBody'] ) ) {
        $body['text'] = $fields['textBody'];
    }

    if ( isset( $fields['htmlBody'] ) ) {
        $body['html'] = $fields['htmlBody'];
    }

    adfoin_mailguntransactional_request( 'messages', 'POST', $body, $record, $cred_id );
}

function adfoin_mailguntransactional_credentials_list() {
    foreach ( adfoin_read_credentials( 'mailguntransactional' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

if ( ! function_exists( 'adfoin_mailguntransactional_request' ) ) :
function adfoin_mailguntransactional_request( $endpoint, $method = 'GET', $body = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mailguntransactional', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Mailgun credentials not found.', 'advanced-form-integration' ) );
    }

    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $domain  = isset( $credentials['domain'] ) ? $credentials['domain'] : '';

    if ( ! $api_key || ! $domain ) {
        return new WP_Error( 'missing_auth', __( 'Mailgun API key or domain missing.', 'advanced-form-integration' ) );
    }

    $base = adfoin_mailguntransactional_get_api_base( $credentials );

    if ( ! $base ) {
        return new WP_Error( 'missing_region', __( 'Mailgun region missing.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $base . '/v3/' . $domain ) . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT' ), true ) ) {
        $args['body'] = $body;
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_mailguntransactional_get_api_base' ) ) :
function adfoin_mailguntransactional_get_api_base( $credentials ) {
    $region = isset( $credentials['region'] ) ? strtolower( $credentials['region'] ) : 'us';

    if ( 'eu' === $region ) {
        return 'https://api.eu.mailgun.net';
    }

    return 'https://api.mailgun.net';
}
endif;
