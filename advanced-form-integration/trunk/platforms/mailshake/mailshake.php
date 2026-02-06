<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailshake_actions', 10, 1 );

function adfoin_mailshake_actions( $actions ) {

    $actions['mailshake'] = array(
        'title' => __( 'Mailshake', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Add Recipient to Campaign', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailshake_settings_tab', 10, 1 );

function adfoin_mailshake_settings_tab( $tabs ) {
    $tabs['mailshake'] = __( 'Mailshake', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_mailshake_settings_view', 10, 1 );

function adfoin_mailshake_settings_view( $current_tab ) {
    if ( 'mailshake' !== $current_tab ) {
        return;
    }

    $title     = __( 'Mailshake', 'advanced-form-integration' );
    $key       = 'mailshake';
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
        __( '<p>Create an API key in Mailshake (Settings → API) and paste it here. AFI calls <code>https://api.mailshake.com/2017-04-01/</code> with your API key as the <code>X-API-Key</code> header. See the %1$sMailshake API docs%2$s for field references.</p>', 'advanced-form-integration' ),
        '<a href="https://api-docs.mailshake.com" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_mailshake_credentials', 'adfoin_get_mailshake_credentials', 10, 0 );

function adfoin_get_mailshake_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'mailshake' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_mailshake_credentials', 'adfoin_save_mailshake_credentials', 10, 0 );

function adfoin_save_mailshake_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'mailshake' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_mailshake_credentials_list() {
    $credentials = adfoin_read_credentials( 'mailshake' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_mailshake_action_fields' );

function adfoin_mailshake_action_fields() {
    ?>
    <script type="text/template" id="mailshake-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Mailshake Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mailshake_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Campaign ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[campaignId]" v-model="fielddata.campaignId" placeholder="<?php esc_attr_e( 'Enter campaign ID (numeric)', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Assign to Lead Catcher', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[leadCatcherId]" v-model="fielddata.leadCatcherId" placeholder="<?php esc_attr_e( 'Optional lead catcher ID', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"phone":"123","company":"Acme"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Additional recipient fields. Must be valid JSON.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://api-docs.mailshake.com/#tag/Recipients" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Recipient API reference', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_mailshake_job_queue', 'adfoin_mailshake_job_queue', 10, 1 );

function adfoin_mailshake_job_queue( $data ) {
    adfoin_mailshake_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailshake_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) ) {
        $cl = $record_data['action_data']['cl'];
        if ( isset( $cl['active'] ) && 'yes' === $cl['active'] ) {
            if ( ! adfoin_match_conditional_logic( $cl, $posted_data ) ) {
                return;
            }
        }
    }

    $field_data  = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id     = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $campaign_id = isset( $field_data['campaignId'] ) ? absint( $field_data['campaignId'] ) : 0;

    $email_field = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email       = $email_field ? sanitize_email( adfoin_get_parsed_values( $email_field, $posted_data ) ) : '';

    if ( empty( $cred_id ) || empty( $campaign_id ) || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'mailshake', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $payload = array(
        'campaignID' => $campaign_id,
        'email'      => $email,
    );

    if ( ! empty( $field_data['first_name'] ) ) {
        $payload['firstName'] = adfoin_get_parsed_values( $field_data['first_name'], $posted_data );
    }

    if ( ! empty( $field_data['last_name'] ) ) {
        $payload['lastName'] = adfoin_get_parsed_values( $field_data['last_name'], $posted_data );
    }

    if ( ! empty( $field_data['phone'] ) ) {
        $payload['phone'] = adfoin_get_parsed_values( $field_data['phone'], $posted_data );
    }

    if ( ! empty( $field_data['leadCatcherId'] ) ) {
        $payload['leadCatcherID'] = absint( adfoin_get_parsed_values( $field_data['leadCatcherId'], $posted_data ) );
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customFields'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $payload['fields'] = $custom;
        }
    }

    if ( ! empty( $field_data['notes'] ) ) {
        $payload['notes'] = adfoin_get_parsed_values( $field_data['notes'], $posted_data );
    }

    $response = adfoin_mailshake_api_request( $credentials, 'recipients/import', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_mailshake_api_request( $credentials, $endpoint, $payload = array(), $record = array() ) {
    $api_key = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'mailshake_missing_key', __( 'Mailshake API key is missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.mailshake.com/2017-04-01/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'X-API-Key'    => $api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_post( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
