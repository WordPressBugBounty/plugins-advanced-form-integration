<?php

add_filter( 'adfoin_action_providers', 'adfoin_reply_actions', 10, 1 );

function adfoin_reply_actions( $actions ) {

    $actions['reply'] = array(
        'title' => __( 'Reply.io', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_reply_settings_tab', 10, 1 );

function adfoin_reply_settings_tab( $tabs ) {
    $tabs['reply'] = __( 'Reply.io', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_reply_settings_view', 10, 1 );

function adfoin_reply_settings_view( $current_tab ) {
    if ( 'reply' !== $current_tab ) {
        return;
    }

    $title     = __( 'Reply.io', 'advanced-form-integration' );
    $key       = 'reply';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'apiKey',
                    'label' => __( 'API Key', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing tag */
        __( '<p>Generate an API key in Reply.io (Settings → API) and paste it here. AFI will call the Reply API with your key via the <code>X-API-Key</code> header. See %1$sReply API docs%2$s for the contact payload.</p>', 'advanced-form-integration' ),
        '<a href="https://apidocs.reply.io/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_reply_credentials', 'adfoin_get_reply_credentials', 10, 0 );

function adfoin_get_reply_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'reply' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_reply_credentials', 'adfoin_save_reply_credentials', 10, 0 );

function adfoin_save_reply_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'reply' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_reply_credentials_list() {
    $credentials = adfoin_read_credentials( 'reply' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_reply_action_fields' );

function adfoin_reply_action_fields() {
    ?>
    <script type="text/template" id="reply-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Reply.io Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_reply_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Sequence ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" v-model="fielddata.sequenceId" name="fieldData[sequenceId]" placeholder="<?php esc_attr_e( 'Optional sequence ID to add the contact to', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"company":"Acme","custom_phone":"123"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Optional additional fields. Must be valid JSON.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://apidocs.reply.io/#contacts_create" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contact API reference', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_reply_job_queue', 'adfoin_reply_job_queue', 10, 1 );

function adfoin_reply_job_queue( $data ) {
    adfoin_reply_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_reply_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) ) {
        $cl = $record_data['action_data']['cl'];
        if ( isset( $cl['active'] ) && 'yes' === $cl['active'] ) {
            if ( ! adfoin_match_conditional_logic( $cl, $posted_data ) ) {
                return;
            }
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    $email_field = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email       = $email_field ? sanitize_email( adfoin_get_parsed_values( $email_field, $posted_data ) ) : '';

    if ( empty( $cred_id ) || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'reply', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $payload = array(
        'email' => $email,
    );

    $fields = array(
        'first_name' => 'firstName',
        'last_name'  => 'lastName',
        'company'    => 'company',
        'title'      => 'title',
        'phone'      => 'phone',
        'city'       => 'city',
        'state'      => 'state',
        'country'    => 'country',
        'website'    => 'website',
        'linkedin'   => 'linkedin',
        'phone_numbers' => 'optPhone',
    );

    foreach ( $fields as $field_key => $reply_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' !== $value ) {
            $payload[ $reply_key ] = $value;
        }
    }

    if ( ! empty( $field_data['sequenceId'] ) ) {
        $payload['sequenceId'] = absint( adfoin_get_parsed_values( $field_data['sequenceId'], $posted_data ) );
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customFields'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $payload['customFields'] = $custom;
        }
    }

    $response = adfoin_reply_api_request( $credentials, 'contacts', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_reply_api_request( $credentials, $endpoint, $method = 'GET', $payload = array(), $record = array() ) {
    $api_key = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'reply_missing_key', __( 'Reply.io API key is missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.reply.io/v1/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'X-API-Key'   => $api_key,
            'Content-Type'=> 'application/json',
            'Accept'      => 'application/json',
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $payload );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
