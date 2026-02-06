<?php

add_filter( 'adfoin_action_providers', 'adfoin_ongage_actions', 10, 1 );

function adfoin_ongage_actions( $actions ) {

    $actions['ongage'] = array(
        'title' => __( 'Ongage', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Create / Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ongage_settings_tab', 10, 1 );

function adfoin_ongage_settings_tab( $tabs ) {
    $tabs['ongage'] = __( 'Ongage', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_ongage_settings_view', 10, 1 );

function adfoin_ongage_settings_view( $current_tab ) {
    if ( 'ongage' !== $current_tab ) {
        return;
    }

    $title     = __( 'Ongage', 'advanced-form-integration' );
    $key       = 'ongage';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'accountCode',
                    'label' => __( 'Account Code', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'apiKey',
                    'label' => __( 'API Key', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'   => 'apiSecret',
                    'label' => __( 'API Secret', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'API Base URL', 'advanced-form-integration' ),
                    'placeholder' => 'https://api.ongage.net/api/v2/',
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor, 2: closing */
        __( '<p>Generate API credentials in Ongage and paste the Account Code, API Key, and API Secret above. The default API base URL is <code>https://api.ongage.net/api/v2/</code>. See the %1$sOngage API docs%2$s for contact payload details.</p>', 'advanced-form-integration' ),
        '<a href="https://ongage.atlassian.net/wiki/spaces/HELP/pages/13795818/API" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_ongage_credentials', 'adfoin_get_ongage_credentials', 10, 0 );

function adfoin_get_ongage_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'ongage' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_ongage_credentials', 'adfoin_save_ongage_credentials', 10, 0 );

function adfoin_save_ongage_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'ongage' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_ongage_credentials_list() {
    $credentials = adfoin_read_credentials( 'ongage' );

    foreach ( $credentials as $credential ) {
        $title = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $title ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_ongage_action_fields' );

function adfoin_ongage_action_fields() {
    ?>
    <script type="text/template" id="ongage-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Ongage Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_ongage_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'List ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="number" class="regular-text" name="fieldData[listId]" v-model="fielddata.listId" min="1" placeholder="<?php esc_attr_e( 'Numeric list ID', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Tags (comma separated)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[tags]" v-model="fielddata.tags" placeholder="<?php esc_attr_e( 'welcome,lead', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"source":"AFI","plan":"Pro"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Will be merged into the payload. Keys must match your Ongage custom fields.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://ongage.atlassian.net/wiki/spaces/HELP/pages/13795818/API" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'API documentation', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_ongage_job_queue', 'adfoin_ongage_job_queue', 10, 1 );

function adfoin_ongage_job_queue( $data ) {
    adfoin_ongage_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ongage_send_data( $record, $posted_data ) {
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
    $list_id    = isset( $field_data['listId'] ) ? absint( $field_data['listId'] ) : 0;

    $email_field = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email       = $email_field ? sanitize_email( adfoin_get_parsed_values( $email_field, $posted_data ) ) : '';

    if ( empty( $cred_id ) || ! $list_id || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'ongage', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $account_code = isset( $credentials['accountCode'] ) ? $credentials['accountCode'] : '';
    $api_key      = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $api_secret   = isset( $credentials['apiSecret'] ) ? $credentials['apiSecret'] : '';

    if ( empty( $account_code ) || empty( $api_key ) || empty( $api_secret ) ) {
        return;
    }

    $contact = array(
        'email' => $email,
        'lists' => array( $list_id ),
    );

    $map_fields = array(
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'company'    => 'company',
        'phone'      => 'phone',
        'city'       => 'city',
        'state'      => 'state',
        'country'    => 'country',
    );

    foreach ( $map_fields as $field_key => $payload_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );
        if ( '' !== $value ) {
            $contact[ $payload_key ] = $value;
        }
    }

    if ( ! empty( $field_data['tags'] ) ) {
        $tags = array_filter( array_map( 'trim', explode( ',', adfoin_get_parsed_values( $field_data['tags'], $posted_data ) ) ) );
        if ( $tags ) {
            $contact['tags'] = $tags;
        }
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customFields'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $contact = array_merge( $contact, $custom );
        }
    }

    $payload = array(
        'contact' => $contact,
    );

    $response = adfoin_ongage_api_request( $credentials, 'contacts', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_ongage_api_request( $credentials, $endpoint, $method = 'GET', $payload = array(), $record = array() ) {
    $base_url    = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? $credentials['baseUrl'] : 'https://api.ongage.net/api/v2/';
    $base_url    = trailingslashit( $base_url );
    $account     = isset( $credentials['accountCode'] ) ? $credentials['accountCode'] : '';
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $api_secret  = isset( $credentials['apiSecret'] ) ? $credentials['apiSecret'] : '';

    if ( ! $account || ! $api_key || ! $api_secret ) {
        return new WP_Error( 'ongage_missing_creds', __( 'Ongage credentials are missing.', 'advanced-form-integration' ) );
    }

    $url = $base_url . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
            'X-Account-Code'  => $account,
            'X-Api-Key'       => $api_key,
            'X-Api-Secret'    => $api_secret,
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
