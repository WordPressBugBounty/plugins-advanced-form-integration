<?php

add_filter( 'adfoin_action_providers', 'adfoin_blueshift_actions', 10, 1 );

function adfoin_blueshift_actions( $actions ) {

    $actions['blueshift'] = array(
        'title' => __( 'Blueshift', 'advanced-form-integration' ),
        'tasks' => array(
            'sync_user' => __( 'Create / Update User', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_blueshift_settings_tab', 10, 1 );

function adfoin_blueshift_settings_tab( $tabs ) {
    $tabs['blueshift'] = __( 'Blueshift', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_blueshift_settings_view', 10, 1 );

function adfoin_blueshift_settings_view( $current_tab ) {
    if ( 'blueshift' !== $current_tab ) {
        return;
    }

    $title     = __( 'Blueshift', 'advanced-form-integration' );
    $key       = 'blueshift';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'apiKey',
                    'label' => __( 'API Key', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'Base URL', 'advanced-form-integration' ),
                    'placeholder' => 'https://api.getblueshift.com',
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor, 2: closing */
        __( '<p>Use your Blueshift API key (Settings → API Keys) and base URL. The default user endpoint is <code>/v1/customers</code>. This action sends a POST request to create or update a customer. See %1$sBlueshift REST API docs%2$s for details.</p>', 'advanced-form-integration' ),
        '<a href="https://developer.blueshift.com/reference/authorization-1" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_blueshift_credentials', 'adfoin_get_blueshift_credentials', 10, 0 );

function adfoin_get_blueshift_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'blueshift' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_blueshift_credentials', 'adfoin_save_blueshift_credentials', 10, 0 );

function adfoin_save_blueshift_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'blueshift' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_blueshift_credentials_list() {
    $credentials = adfoin_read_credentials( 'blueshift' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_blueshift_action_fields' );

function adfoin_blueshift_action_fields() {
    ?>
    <script type="text/template" id="blueshift-action-template">
        <table class="form-table" v-if="action.task == 'sync_user'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Blueshift Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_blueshift_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Tags (comma separated)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[tags]" v-model="fielddata.tags" placeholder="<?php esc_attr_e( 'lead,onboarding', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Attributes (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customAttributes]" v-model="fielddata.customAttributes" placeholder='{"plan":"Pro","source":"AFI"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Will be merged into the payload. Keys must match your Blueshift profile attributes.', 'advanced-form-integration' ); ?></p>
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

add_action( 'adfoin_blueshift_job_queue', 'adfoin_blueshift_job_queue', 10, 1 );

function adfoin_blueshift_job_queue( $data ) {
    adfoin_blueshift_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_blueshift_send_data( $record, $posted_data ) {
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

    $uid_field = isset( $field_data['uid'] ) ? $field_data['uid'] : '';
    $uid       = $uid_field ? adfoin_get_parsed_values( $uid_field, $posted_data ) : '';

    if ( empty( $cred_id ) || empty( $uid ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'blueshift', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( empty( $api_key ) ) {
        return;
    }

    $payload = array(
        'customer_id' => $uid,
    );

    $field_map = array(
        'email'      => 'email',
        'phone'      => 'phone_number',
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'gender'     => 'gender',
        'city'       => 'city',
        'country'    => 'country',
    );

    foreach ( $field_map as $field_key => $payload_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' !== $value ) {
            $payload[ $payload_key ] = $value;
        }
    }

    if ( ! empty( $field_data['tags'] ) ) {
        $tags = array_filter( array_map( 'trim', explode( ',', adfoin_get_parsed_values( $field_data['tags'], $posted_data ) ) ) );
        if ( $tags ) {
            $payload['tags'] = $tags;
        }
    }

    if ( ! empty( $field_data['customAttributes'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customAttributes'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $payload['attributes'] = $custom;
        }
    }

    $response = adfoin_blueshift_api_request( $credentials, $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_blueshift_api_request( $credentials, $payload, $record ) {
    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? $credentials['baseUrl'] : 'https://api.getblueshift.com';
    $base_url = untrailingslashit( $base_url );
    $api_key  = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'blueshift_missing_key', __( 'Blueshift API key is missing.', 'advanced-form-integration' ) );
    }

    $endpoint = $base_url . '/v1/customers';

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_post( $endpoint, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $endpoint, $args, $record );
    }

    return $response;
}
