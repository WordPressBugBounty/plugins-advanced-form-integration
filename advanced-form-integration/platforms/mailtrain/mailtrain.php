<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailtrain_actions', 10, 1 );

function adfoin_mailtrain_actions( $actions ) {

    $actions['mailtrain'] = array(
        'title' => __( 'Mailtrain', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Subscribe to List', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailtrain_settings_tab', 10, 1 );

function adfoin_mailtrain_settings_tab( $tabs ) {
    $tabs['mailtrain'] = __( 'Mailtrain', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_mailtrain_settings_view', 10, 1 );

function adfoin_mailtrain_settings_view( $current_tab ) {
    if ( 'mailtrain' !== $current_tab ) {
        return;
    }

    $title     = __( 'Mailtrain', 'advanced-form-integration' );
    $key       = 'mailtrain';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'Base URL (e.g. https://mailtrain.example.com)', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'accessToken',
                    'label' => __( 'Access Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'   => 'namespaceId',
                    'label' => __( 'Namespace ID (number)', 'advanced-form-integration' ),
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing anchor tag */
        __( '<p>Generate a personal API access token in Mailtrain via Account → API. Provide the instance base URL (without /api suffix) and the namespace ID that contains the lists you want to use. Each credential entry can point to a different namespace. See %1$sMailtrain API docs%2$s for details.</p>', 'advanced-form-integration' ),
        '<a href="https://github.com/Mailtrain-org/mailtrain/blob/v2/client/src/account/API.js" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_mailtrain_credentials', 'adfoin_get_mailtrain_credentials', 10, 0 );

function adfoin_get_mailtrain_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'mailtrain' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_mailtrain_credentials', 'adfoin_save_mailtrain_credentials', 10, 0 );

function adfoin_save_mailtrain_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'mailtrain' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_mailtrain_credentials_list() {
    $credentials = adfoin_read_credentials( 'mailtrain' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_mailtrain_action_fields' );

function adfoin_mailtrain_action_fields() {
    ?>
    <script type="text/template" id="mailtrain-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Mailtrain Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mailtrain_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Subscriber List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, cid) in fielddata.lists" :value="cid">{{ label }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Options', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <label style="margin-right: 15px;">
                        <input type="checkbox" name="fieldData[forceSubscribe]" value="true" v-model="fielddata.forceSubscribe">
                        <?php esc_html_e( 'Force subscribe (overwrite status)', 'advanced-form-integration' ); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="fieldData[requireConfirmation]" value="true" v-model="fielddata.requireConfirmation">
                        <?php esc_html_e( 'Send confirmation email', 'advanced-form-integration' ); ?>
                    </label>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Additional Merge JSON', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"MERGE_COMPANY":"Acme Inc"}'></textarea>
                    <p class="description"><?php esc_html_e( 'Provide extra merge tag values in JSON (keys must match MERGE_* tag names).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://github.com/Mailtrain-org/mailtrain/blob/v2/client/src/account/API.js" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'API reference', 'advanced-form-integration' ); ?></a>
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

add_action( 'wp_ajax_adfoin_get_mailtrain_lists', 'adfoin_get_mailtrain_lists', 10, 0 );

function adfoin_get_mailtrain_lists() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Select a Mailtrain credential first.', 'advanced-form-integration' ),
            )
        );
    }

    $credentials = adfoin_get_credentials_by_id( 'mailtrain', $cred_id );

    if ( empty( $credentials ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Invalid credential.', 'advanced-form-integration' ),
            )
        );
    }

    $lists = adfoin_mailtrain_fetch_lists( $credentials );

    if ( is_wp_error( $lists ) ) {
        wp_send_json_error(
            array(
                'message' => $lists->get_error_message(),
            )
        );
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_mailtrain_job_queue', 'adfoin_mailtrain_job_queue', 10, 1 );

function adfoin_mailtrain_job_queue( $data ) {
    adfoin_mailtrain_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailtrain_send_data( $record, $posted_data ) {
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
    $list_cid   = isset( $field_data['listId'] ) ? $field_data['listId'] : '';

    $email_raw = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email     = $email_raw ? sanitize_email( adfoin_get_parsed_values( $email_raw, $posted_data ) ) : '';

    if ( empty( $cred_id ) || empty( $list_cid ) || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'mailtrain', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $payload = array(
        'EMAIL' => $email,
    );

    if ( ! empty( $field_data['firstName'] ) ) {
        $payload['MERGE_FIRST_NAME'] = adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
    }

    if ( ! empty( $field_data['lastName'] ) ) {
        $payload['MERGE_LAST_NAME'] = adfoin_get_parsed_values( $field_data['lastName'], $posted_data );
    }

    if ( ! empty( $field_data['timezone'] ) ) {
        $payload['TIMEZONE'] = adfoin_get_parsed_values( $field_data['timezone'], $posted_data );
    }

    if ( ! empty( $field_data['customFields'] ) ) {
        $custom_raw = adfoin_get_parsed_values( $field_data['customFields'], $posted_data );
        $custom_decoded = json_decode( $custom_raw, true );

        if ( is_array( $custom_decoded ) ) {
            foreach ( $custom_decoded as $key => $value ) {
                if ( is_scalar( $value ) && $key ) {
                    $payload[ $key ] = (string) $value;
                }
            }
        }
    }

    if ( isset( $field_data['forceSubscribe'] ) && 'true' === $field_data['forceSubscribe'] ) {
        $payload['FORCE_SUBSCRIBE'] = 'yes';
    }

    if ( isset( $field_data['requireConfirmation'] ) && 'true' === $field_data['requireConfirmation'] ) {
        $payload['REQUIRE_CONFIRMATION'] = 'yes';
    }

    $endpoint = 'api/subscribe/' . rawurlencode( $list_cid );
    $response = adfoin_mailtrain_api_request( $credentials, $endpoint, 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( $status < 200 || $status >= 300 ) {
        return;
    }
}

function adfoin_mailtrain_fetch_lists( $credentials ) {
    $namespace_id = isset( $credentials['namespaceId'] ) ? intval( $credentials['namespaceId'] ) : 0;

    if ( $namespace_id <= 0 ) {
        return new WP_Error( 'mailtrain_namespace_missing', __( 'Namespace ID is missing or invalid.', 'advanced-form-integration' ) );
    }

    $endpoint = sprintf( 'api/lists-by-namespace/%d', $namespace_id );
    $response = adfoin_mailtrain_api_request( $credentials, $endpoint, 'GET' );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( 200 !== (int) $status ) {
        return new WP_Error( 'mailtrain_list_error', adfoin_mailtrain_extract_error_message( $response ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
        return new WP_Error( 'mailtrain_list_error', __( 'Unable to parse Mailtrain lists response.', 'advanced-form-integration' ) );
    }

    $lists = array();
    foreach ( $body['data'] as $item ) {
        if ( empty( $item['cid'] ) ) {
            continue;
        }

        $name = isset( $item['name'] ) ? $item['name'] : $item['cid'];
        $lists[ $item['cid'] ] = $name;
    }

    return $lists;
}

function adfoin_mailtrain_api_request( $credentials, $endpoint, $method = 'GET', $payload = array(), $record = array() ) {
    $base_url = adfoin_mailtrain_normalize_base_url( $credentials );

    if ( is_wp_error( $base_url ) ) {
        return $base_url;
    }

    $access_token = isset( $credentials['accessToken'] ) ? trim( $credentials['accessToken'] ) : '';

    if ( empty( $access_token ) ) {
        return new WP_Error( 'mailtrain_missing_token', __( 'Mailtrain access token is missing.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );
    $url = add_query_arg( 'access_token', $access_token, $url );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Content-Type' => 'application/json',
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

function adfoin_mailtrain_normalize_base_url( $credentials ) {
    $base_url = isset( $credentials['baseUrl'] ) ? trim( $credentials['baseUrl'] ) : '';

    if ( empty( $base_url ) ) {
        return new WP_Error( 'mailtrain_missing_base', __( 'Mailtrain base URL is missing.', 'advanced-form-integration' ) );
    }

    if ( ! preg_match( '#^https?://#i', $base_url ) ) {
        $base_url = 'https://' . $base_url;
    }

    return untrailingslashit( $base_url );
}

function adfoin_mailtrain_extract_error_message( $response ) {
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

    return __( 'Unexpected Mailtrain API error.', 'advanced-form-integration' );
}
