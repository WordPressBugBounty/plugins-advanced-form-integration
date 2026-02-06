<?php

add_filter( 'adfoin_action_providers', 'adfoin_sinch_actions', 10, 1 );

function adfoin_sinch_actions( $actions ) {

    $actions['sinch'] = array(
        'title' => __( 'Sinch SMS', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Update Group Members', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sinch_settings_tab', 10, 1 );

function adfoin_sinch_settings_tab( $tabs ) {
    $tabs['sinch'] = __( 'Sinch SMS', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_sinch_settings_view', 10, 1 );

function adfoin_sinch_settings_view( $current_tab ) {
    if ( 'sinch' !== $current_tab ) {
        return;
    }

    $title     = __( 'Sinch SMS', 'advanced-form-integration' );
    $key       = 'sinch';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'servicePlanId',
                    'label' => __( 'Service Plan ID', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'apiToken',
                    'label' => __( 'API Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'API Base URL', 'advanced-form-integration' ),
                    'placeholder' => 'https://us.sms.api.sinch.com/xms/v1',
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing tag */
        __( '<p>Provide your Sinch Service Plan ID and API token. Set the API base URL according to your region (default <code>https://us.sms.api.sinch.com/xms/v1</code>). This action updates group members via the <code>POST /xms/v1/{service_plan_id}/groups/{group_id}</code> endpoint. See the %1$sSinch SMS API docs%2$s for details.</p>', 'advanced-form-integration' ),
        '<a href="https://sinch.redocly.app/docs/sms/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_sinch_credentials', 'adfoin_get_sinch_credentials', 10, 0 );

function adfoin_get_sinch_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'sinch' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_sinch_credentials', 'adfoin_save_sinch_credentials', 10, 0 );

function adfoin_save_sinch_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'sinch' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_sinch_credentials_list() {
    $credentials = adfoin_read_credentials( 'sinch' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_sinch_action_fields' );

function adfoin_sinch_action_fields() {
    ?>
    <script type="text/template" id="sinch-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map & Configure', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Sinch Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_sinch_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Group ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[groupId]" v-model="fielddata.groupId" placeholder="<?php esc_attr_e( 'Target group ID', 'advanced-form-integration' ); ?>" required>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Add Numbers', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[addNumbers]" v-model="fielddata.addNumbers" placeholder="<?php esc_attr_e( 'Comma or newline separated numbers (use placeholders if needed)', 'advanced-form-integration' ); ?>"></textarea>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Remove Numbers', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[removeNumbers]" v-model="fielddata.removeNumbers" placeholder="<?php esc_attr_e( 'Comma or newline separated numbers', 'advanced-form-integration' ); ?>"></textarea>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://sinch.redocly.app/docs/sms/common-operations/groups" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'API documentation', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_sinch_job_queue', 'adfoin_sinch_job_queue', 10, 1 );

function adfoin_sinch_job_queue( $data ) {
    adfoin_sinch_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sinch_send_data( $record, $posted_data ) {
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
    $group_id   = isset( $field_data['groupId'] ) ? trim( $field_data['groupId'] ) : '';

    if ( empty( $cred_id ) || empty( $group_id ) ) {
        return;
    }

    $credentials    = adfoin_get_credentials_by_id( 'sinch', $cred_id );
    $service_plan   = isset( $credentials['servicePlanId'] ) ? $credentials['servicePlanId'] : '';
    $api_token      = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( empty( $credentials ) || empty( $service_plan ) || empty( $api_token ) ) {
        return;
    }

    $add_numbers    = adfoin_sinch_parse_numbers( $field_data, 'addNumbers', $posted_data );
    $remove_numbers = adfoin_sinch_parse_numbers( $field_data, 'removeNumbers', $posted_data );

    if ( empty( $add_numbers ) && empty( $remove_numbers ) ) {
        return;
    }

    $payload = array();

    if ( ! empty( $add_numbers ) ) {
        $payload['add'] = array_values( $add_numbers );
    }

    if ( ! empty( $remove_numbers ) ) {
        $payload['remove'] = array_values( $remove_numbers );
    }

    $response = adfoin_sinch_api_request( $credentials, $group_id, 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_sinch_api_request( $credentials, $group_id, $method = 'POST', $payload = array(), $record = array() ) {
    $base_url  = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? $credentials['baseUrl'] : 'https://us.sms.api.sinch.com/xms/v1';
    $base_url  = untrailingslashit( $base_url );
    $plan_id   = isset( $credentials['servicePlanId'] ) ? $credentials['servicePlanId'] : '';
    $api_token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( ! $plan_id || ! $api_token ) {
        return new WP_Error( 'sinch_missing_creds', __( 'Missing Sinch service plan ID or API token.', 'advanced-form-integration' ) );
    }

    $endpoint = sprintf( '%s/%s/groups/%s', $base_url, rawurlencode( $plan_id ), rawurlencode( $group_id ) );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_request( $endpoint, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $endpoint, $args, $record );
    }

    return $response;
}

function adfoin_sinch_parse_numbers( $field_data, $key, $posted_data ) {
    $numbers = array();

    if ( ! empty( $field_data[ $key ] ) ) {
        $raw = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        $numbers = array_merge( $numbers, adfoin_sinch_split_numbers( $raw ) );
    }

    if ( isset( $field_data['msisdn'] ) && $field_data['msisdn'] ) {
        $value = adfoin_get_parsed_values( $field_data['msisdn'], $posted_data );
        $numbers = array_merge( $numbers, adfoin_sinch_split_numbers( $value ) );
    }

    $numbers = array_filter( array_map( 'trim', $numbers ) );

    return array_values( array_unique( $numbers ) );
}

function adfoin_sinch_split_numbers( $value ) {
    if ( '' === trim( $value ) ) {
        return array();
    }

    $value = str_replace( array( "\r\n", "\r" ), "\n", $value );

    $parts = preg_split( '/[\s,]+/', $value );

    return array_filter( array_map( 'trim', $parts ) );
}
