<?php

add_filter( 'adfoin_action_providers', 'adfoin_outreach_actions', 10, 1 );

function adfoin_outreach_actions( $actions ) {

    $actions['outreach'] = array(
        'title' => __( 'Outreach', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Create / Update Prospect', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_outreach_settings_tab', 10, 1 );

function adfoin_outreach_settings_tab( $tabs ) {
    $tabs['outreach'] = __( 'Outreach', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_outreach_settings_view', 10, 1 );

function adfoin_outreach_settings_view( $current_tab ) {
    if ( 'outreach' !== $current_tab ) {
        return;
    }

    $title     = __( 'Outreach', 'advanced-form-integration' );
    $key       = 'outreach';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'clientId',
                    'label' => __( 'Client ID', 'advanced-form-integration' ),
                ),
                array(
                    'key'    => 'clientSecret',
                    'label'  => __( 'Client Secret', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'    => 'refreshToken',
                    'label'  => __( 'Refresh Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing anchor tag */
        __( '<p>Create an OAuth application in Outreach and generate a refresh token (see %1$sOutreach API docs%2$s). Provide the Client ID, Client Secret, and Refresh Token so AFI can exchange it for access tokens on demand.</p>', 'advanced-form-integration' ),
        '<a href="https://developers.outreach.io/api/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_outreach_credentials', 'adfoin_get_outreach_credentials', 10, 0 );

function adfoin_get_outreach_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'outreach' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_outreach_credentials', 'adfoin_save_outreach_credentials', 10, 0 );

function adfoin_save_outreach_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'outreach' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_outreach_credentials_list() {
    $credentials = adfoin_read_credentials( 'outreach' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_outreach_action_fields' );

function adfoin_outreach_action_fields() {
    ?>
    <script type="text/template" id="outreach-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Outreach Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_outreach_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Company Name', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[companyName]" v-model="fielddata.companyName" placeholder="<?php esc_attr_e( 'Optional company name', 'advanced-form-integration' ); ?>">
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Custom Fields (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"custom_field":"value"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Additional prospect attributes. Must be valid JSON.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://developers.outreach.io/api/reference/1.0/prospect" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Prospect API reference', 'advanced-form-integration' ); ?></a>
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

add_action( 'adfoin_outreach_job_queue', 'adfoin_outreach_job_queue', 10, 1 );

function adfoin_outreach_job_queue( $data ) {
    adfoin_outreach_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_outreach_send_data( $record, $posted_data ) {
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

    $credentials = adfoin_get_credentials_by_id( 'outreach', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $payload = array(
        'data' => array(
            'type'       => 'prospect',
            'attributes' => array(
                'emails' => array(
                    array(
                        'type'  => 'work',
                        'value' => $email,
                    ),
                ),
            ),
        ),
    );

    $attribute_fields = array(
        'first_name'    => 'first_name',
        'last_name'     => 'last_name',
        'job_title'     => 'job_title',
        'company'       => 'company',
        'work_phone'    => 'work_phone',
        'mobile_phone'  => 'mobile_phone',
        'website'       => 'website_url',
        'linkedin_url'  => 'linkedin_url',
        'twitter_handle'=> 'twitter_handle',
        'owner_id'      => 'owner_id',
        'stage_id'      => 'stage_id',
        'account_id'    => 'account_id',
        'city'          => 'city',
        'state'         => 'state',
        'country'       => 'country',
        'tags'          => 'tags',
    );

    foreach ( $attribute_fields as $field_key => $attribute_key ) {
        if ( empty( $field_data[ $field_key ] ) ) {
            continue;
        }

        $value = adfoin_get_parsed_values( $field_data[ $field_key ], $posted_data );

        if ( '' === $value ) {
            continue;
        }

        if ( in_array( $field_key, array( 'owner_id', 'stage_id', 'account_id' ), true ) ) {
            $payload['data']['attributes'][ $attribute_key ] = (int) $value;
        } elseif ( 'tags' === $field_key ) {
            $payload['data']['attributes'][ $attribute_key ] = array_filter( array_map( 'trim', explode( ',', $value ) ) );
        } else {
            $payload['data']['attributes'][ $attribute_key ] = $value;
        }
    }

    if ( ! empty( $field_data['companyName'] ) ) {
        $payload['data']['attributes']['company_name'] = adfoin_get_parsed_values( $field_data['companyName'], $posted_data );
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom = json_decode( adfoin_get_parsed_values( $field_data['customFields'], $posted_data ), true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $custom ) ) {
            $payload['data']['attributes'] = array_merge( $payload['data']['attributes'], $custom );
        }
    }

    $response = adfoin_outreach_api_request( $credentials, 'prospects', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_outreach_api_request( $credentials, $endpoint, $method = 'GET', $payload = array(), $record = array() ) {
    $token = adfoin_outreach_get_access_token( $credentials );

    if ( is_wp_error( $token ) ) {
        return $token;
    }

    $url = 'https://api.outreach.io/api/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
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

function adfoin_outreach_get_access_token( $credentials ) {
    $client_id     = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
    $refresh_token = isset( $credentials['refreshToken'] ) ? $credentials['refreshToken'] : '';

    if ( ! $client_id || ! $client_secret || ! $refresh_token ) {
        return new WP_Error( 'outreach_missing_credentials', __( 'Outreach Client ID, Secret, or Refresh Token is missing.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_outreach_token_' . md5( $client_id . $refresh_token );
    $cached    = get_transient( $cache_key );

    if ( $cached ) {
        return $cached;
    }

    $response = wp_remote_post(
        'https://api.outreach.io/oauth/token',
        array(
            'timeout' => 30,
            'body'    => array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( $status < 200 || $status >= 300 ) {
        return new WP_Error( 'outreach_auth_failed', adfoin_outreach_extract_error_message( $response ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['access_token'] ) ) {
        return new WP_Error( 'outreach_auth_failed', __( 'Outreach access token missing from response.', 'advanced-form-integration' ) );
    }

    $token      = $body['access_token'];
    $expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 2400;

    set_transient( $cache_key, $token, max( 60, $expires_in - 60 ) );

    return $token;
}

function adfoin_outreach_extract_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['message'] ) ) {
        return $body['message'];
    }

    if ( isset( $body['error_description'] ) ) {
        return $body['error_description'];
    }

    if ( isset( $body['error'] ) ) {
        if ( is_string( $body['error'] ) ) {
            return $body['error'];
        }

        if ( is_array( $body['error'] ) && isset( $body['error']['message'] ) ) {
            return $body['error']['message'];
        }
    }

    return __( 'Unexpected Outreach API error.', 'advanced-form-integration' );
}
