<?php

add_filter( 'adfoin_action_providers', 'adfoin_salesloft_actions', 10, 1 );

function adfoin_salesloft_actions( $actions ) {

    $actions['salesloft'] = array(
        'title' => __( 'Salesloft', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Create / Update Person', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_salesloft_settings_tab', 10, 1 );

function adfoin_salesloft_settings_tab( $tabs ) {
    $tabs['salesloft'] = __( 'Salesloft', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_salesloft_settings_view', 10, 1 );

function adfoin_salesloft_settings_view( $current_tab ) {
    if ( 'salesloft' !== $current_tab ) {
        return;
    }

    $title     = __( 'Salesloft', 'advanced-form-integration' );
    $key       = 'salesloft';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'apiKey',
                    'label' => __( 'REST API Key', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing tag */
        __( '<p>Generate a Salesloft API key under <strong>Settings → API Keys</strong>, then paste it here. AFI will use the key as a Bearer token against <code>https://api.salesloft.com/v2/</code>. Refer to the %1$sSalesloft API docs%2$s for payload details.</p>', 'advanced-form-integration' ),
        '<a href="https://developers.salesloft.com/docs/api/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_salesloft_credentials', 'adfoin_get_salesloft_credentials', 10, 0 );

function adfoin_get_salesloft_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'salesloft' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_salesloft_credentials', 'adfoin_save_salesloft_credentials', 10, 0 );

function adfoin_save_salesloft_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'salesloft' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_salesloft_credentials_list() {
    $credentials = adfoin_read_credentials( 'salesloft' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_salesloft_action_fields' );

function adfoin_salesloft_action_fields() {
    ?>
    <script type="text/template" id="salesloft-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Salesloft Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_salesloft_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"custom_field": "value"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Optional JSON merged into the request body. Keys must match Salesloft field names.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://developers.salesloft.com/docs/api/people" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'People API reference', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_salesloft_job_queue', 'adfoin_salesloft_job_queue', 10, 1 );

function adfoin_salesloft_job_queue( $data ) {
    adfoin_salesloft_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_salesloft_send_data( $record, $posted_data ) {
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

    $email_field = isset( $field_data['email_address'] ) ? $field_data['email_address'] : '';
    $email       = $email_field ? sanitize_email( adfoin_get_parsed_values( $email_field, $posted_data ) ) : '';

    if ( empty( $cred_id ) || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'salesloft', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $payload = array(
        'email_address' => $email,
    );

    $text_fields = array(
        'first_name',
        'last_name',
        'company',
        'title',
        'phone',
        'mobile_phone',
        'city',
        'state',
        'country',
        'website',
        'linkedin_url',
        'twitter_handle',
        'account_id',
        'owner_id',
        'person_stage_id',
        'secondary_email',
        'work_city',
        'work_state',
        'work_country',
    );

    foreach ( $text_fields as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        if ( '' !== $value ) {
            if ( in_array( $key, array( 'owner_id', 'person_stage_id', 'account_id' ), true ) ) {
                $payload[ $key ] = (int) $value;
            } else {
                $payload[ $key ] = $value;
            }
        }
    }

    if ( ! empty( $field_data['tags'] ) ) {
        $tags_value = adfoin_get_parsed_values( $field_data['tags'], $posted_data );
        if ( $tags_value ) {
            $tags = array_filter( array_map( 'trim', explode( ',', $tags_value ) ) );
            if ( ! empty( $tags ) ) {
                $payload['tags'] = $tags;
            }
        }
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom_raw = adfoin_get_parsed_values( $field_data['customFields'], $posted_data );
        $custom     = json_decode( $custom_raw, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $payload = array_merge( $payload, $custom );
        }
    }

    $response = adfoin_salesloft_api_request( $credentials, 'people.json', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_salesloft_api_request( $credentials, $endpoint, $method = 'GET', $payload = array(), $record = array(), $query = array() ) {
    $api_key = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'salesloft_missing_key', __( 'Salesloft API key is missing.', 'advanced-form-integration' ) );
    }

    $base_url = 'https://api.salesloft.com/v2/';
    $url      = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );

    if ( ! empty( $query ) ) {
        $url = add_query_arg( $query, $url );
    }

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
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

function adfoin_salesloft_extract_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['error'] ) ) {
        if ( is_array( $body['error'] ) && isset( $body['error']['message'] ) ) {
            return $body['error']['message'];
        }

        if ( is_string( $body['error'] ) ) {
            return $body['error'];
        }
    }

    if ( isset( $body['message'] ) ) {
        return $body['message'];
    }

    return __( 'Unexpected Salesloft API error.', 'advanced-form-integration' );
}
