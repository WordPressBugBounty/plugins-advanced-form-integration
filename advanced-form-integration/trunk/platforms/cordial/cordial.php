<?php

add_filter( 'adfoin_action_providers', 'adfoin_cordial_actions', 10, 1 );

function adfoin_cordial_actions( $actions ) {

    $actions['cordial'] = array(
        'title' => __( 'Cordial', 'advanced-form-integration' ),
        'tasks' => array(
            'sync_contact' => __( 'Create / Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_cordial_settings_tab', 10, 1 );

function adfoin_cordial_settings_tab( $tabs ) {
    $tabs['cordial'] = __( 'Cordial', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_cordial_settings_view', 10, 1 );

function adfoin_cordial_settings_view( $current_tab ) {
    if ( 'cordial' !== $current_tab ) {
        return;
    }

    $title     = __( 'Cordial', 'advanced-form-integration' );
    $key       = 'cordial';
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
                    'key'   => 'username',
                    'label' => __( 'Username', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'API Base URL', 'advanced-form-integration' ),
                    'placeholder' => 'https://api.cordial.io/v2',
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor, 2: closing */
        __( '<p>Use your Cordial REST credentials (Username + API Key) and the proper base URL (default <code>https://api.cordial.io/v2</code>). This action creates/updates contacts via <code>/contacts</code>. See the %1$sCordial API docs%2$s for payload details.</p>', 'advanced-form-integration' ),
        '<a href="https://support.cordial.com/hc/en-us/articles/203885498-RESTful-API-summary-and-usage" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_cordial_credentials', 'adfoin_get_cordial_credentials', 10, 0 );

function adfoin_get_cordial_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'cordial' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_cordial_credentials', 'adfoin_save_cordial_credentials', 10, 0 );

function adfoin_save_cordial_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'cordial' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_cordial_credentials_list() {
    $credentials = adfoin_read_credentials( 'cordial' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_cordial_action_fields' );

function adfoin_cordial_action_fields() {
    ?>
    <script type="text/template" id="cordial-action-template">
        <table class="form-table" v-if="action.task == 'sync_contact'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Cordial Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_cordial_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Lists (comma separated IDs)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[listIds]" v-model="fielddata.listIds" placeholder="<?php esc_attr_e( '123,456', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Attributes (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customAttributes]" v-model="fielddata.customAttributes" placeholder='{"BrandPreference":"Acme"}'></textarea>
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

add_action( 'adfoin_cordial_job_queue', 'adfoin_cordial_job_queue', 10, 1 );

function adfoin_cordial_job_queue( $data ) {
    adfoin_cordial_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_cordial_send_data( $record, $posted_data ) {
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

    $credentials = adfoin_get_credentials_by_id( 'cordial', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $payload = array(
        'channels' => array(
            'email' => array(
                'address' => $email,
            ),
        ),
    );

    $fields = array(
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'phone'      => 'phone',
        'city'       => 'city',
        'state'      => 'state',
        'country'    => 'country',
    );

    foreach ( $fields as $field_key => $payload_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' !== $value ) {
            $payload[ $payload_key ] = $value;
        }
    }

    if ( ! empty( $field_data['listIds'] ) ) {
        $lists = array_filter( array_map( 'trim', preg_split( '/[\s,]+/', adfoin_get_parsed_values( $field_data['listIds'], $posted_data ) ) ) );
        if ( $lists ) {
            $payload['listIds'] = array_map( 'intval', $lists );
        }
    }

    if ( ! empty( $field_data['customAttributes'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customAttributes'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $payload['attributes'] = $custom;
        }
    }

    $response = adfoin_cordial_api_request( $credentials, $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_cordial_api_request( $credentials, $payload, $record ) {
    $base_url = isset( $credentials['baseUrl'] ) && $credentials['baseUrl'] ? $credentials['baseUrl'] : 'https://api.cordial.io/v2';
    $base_url = untrailingslashit( $base_url );
    $api_key  = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $username = isset( $credentials['username'] ) ? $credentials['username'] : '';

    if ( empty( $api_key ) || empty( $username ) ) {
        return new WP_Error( 'cordial_missing_creds', __( 'Cordial username or API key missing.', 'advanced-form-integration' ) );
    }

    $endpoint = $base_url . '/contacts';

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $api_key ),
        ),
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_post( $endpoint, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $endpoint, $args, $record );
    }

    return $response;
}
