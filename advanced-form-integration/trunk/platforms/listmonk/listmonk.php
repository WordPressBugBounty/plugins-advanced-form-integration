<?php

add_filter( 'adfoin_action_providers', 'adfoin_listmonk_actions', 10, 1 );

function adfoin_listmonk_actions( $actions ) {

    $actions['listmonk'] = array(
        'title' => __( 'Listmonk', 'advanced-form-integration' ),
        'tasks' => array(
            'add_to_list' => __( 'Create / Subscribe Subscriber', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_listmonk_settings_tab', 10, 1 );

function adfoin_listmonk_settings_tab( $tabs ) {
    $tabs['listmonk'] = __( 'Listmonk', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_listmonk_settings_view', 10, 1 );

function adfoin_listmonk_settings_view( $current_tab ) {
    if ( 'listmonk' !== $current_tab ) {
        return;
    }

    $title     = __( 'Listmonk', 'advanced-form-integration' );
    $key       = 'listmonk';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'baseUrl',
                    'label' => __( 'Base URL (e.g. https://listmonk.example.com)', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'username',
                    'label' => __( 'API Username', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'password',
                    'label' => __( 'API Password / Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: opening link tag, 2: closing tag */
        __( '<p>Create an API user/token in Listmonk (Admin → Users) and note the username and token. Provide the instance base URL (no trailing slash). AFI authenticates using HTTP Basic auth for every API call. Refer to the %1$sListmonk API docs%2$s for full details.</p>', 'advanced-form-integration' ),
        '<a href="https://listmonk.app/docs/apis/apis/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_listmonk_credentials', 'adfoin_get_listmonk_credentials', 10, 0 );

function adfoin_get_listmonk_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'listmonk' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_listmonk_credentials', 'adfoin_save_listmonk_credentials', 10, 0 );

function adfoin_save_listmonk_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'listmonk' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_listmonk_credentials_list() {
    $credentials = adfoin_read_credentials( 'listmonk' );

    foreach ( $credentials as $credential ) {
        $label = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $credential['id'] ) . '">' . esc_html( $label ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_listmonk_action_fields' );

function adfoin_listmonk_action_fields() {
    ?>
    <script type="text/template" id="listmonk-action-template">
        <table class="form-table" v-if="action.task == 'add_to_list'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Listmonk Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_listmonk_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in fielddata.lists" :value="id">{{ label }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Status', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[status]" v-model="fielddata.status">
                        <option value="enabled"><?php esc_html_e( 'Enabled', 'advanced-form-integration' ); ?></option>
                        <option value="blocklisted"><?php esc_html_e( 'Blocklisted', 'advanced-form-integration' ); ?></option>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Confirmation', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="fieldData[preconfirm]" value="true" v-model="fielddata.preconfirm" true-value="true" false-value="">
                        <?php esc_html_e( 'Pre-confirm subscriptions (skip double opt-in emails)', 'advanced-form-integration' ); ?>
                    </label>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Attributes (JSON)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea rows="4" class="large-text" name="fieldData[customFields]" v-model="fielddata.customFields" placeholder='{"company":"Acme","tags":["beta"]}'></textarea>
                    <p class="description"><?php esc_html_e( 'Key/value pairs applied to Listmonk subscriber attribs. Must be valid JSON.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://listmonk.app/docs/apis/subscribers/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Subscriber API reference', 'advanced-form-integration' ); ?></a>
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

add_action( 'wp_ajax_adfoin_get_listmonk_lists', 'adfoin_get_listmonk_lists', 10, 0 );

function adfoin_get_listmonk_lists() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Select a Listmonk credential first.', 'advanced-form-integration' ),
            )
        );
    }

    $credentials = adfoin_get_credentials_by_id( 'listmonk', $cred_id );

    if ( empty( $credentials ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Invalid credential.', 'advanced-form-integration' ),
            )
        );
    }

    $response = adfoin_listmonk_api_request( $credentials, 'api/lists', 'GET', array(), array(), array( 'per_page' => 'all' ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error(
            array(
                'message' => $response->get_error_message(),
            )
        );
    }

    $status = wp_remote_retrieve_response_code( $response );

    if ( 200 !== (int) $status ) {
        wp_send_json_error(
            array(
                'message' => adfoin_listmonk_extract_error_message( $response ),
            )
        );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['data']['results'] ) || ! is_array( $body['data']['results'] ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Unable to parse list response.', 'advanced-form-integration' ),
            )
        );
    }

    $lists = array();

    foreach ( $body['data']['results'] as $list ) {
        if ( isset( $list['id'], $list['name'] ) ) {
            $lists[ $list['id'] ] = $list['name'];
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_listmonk_job_queue', 'adfoin_listmonk_job_queue', 10, 1 );

function adfoin_listmonk_job_queue( $data ) {
    adfoin_listmonk_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_listmonk_send_data( $record, $posted_data ) {
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
    $list_id    = isset( $field_data['listId'] ) ? intval( $field_data['listId'] ) : 0;

    $email_raw = isset( $field_data['email'] ) ? $field_data['email'] : '';
    $email     = $email_raw ? sanitize_email( adfoin_get_parsed_values( $email_raw, $posted_data ) ) : '';

    if ( empty( $cred_id ) || ! $list_id || empty( $email ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'listmonk', $cred_id );

    if ( empty( $credentials ) ) {
        return;
    }

    $name_raw = isset( $field_data['name'] ) ? $field_data['name'] : '';
    $name     = $name_raw ? adfoin_get_parsed_values( $name_raw, $posted_data ) : '';

    if ( empty( $name ) ) {
        $name = $email;
    }

    $status = isset( $field_data['status'] ) && 'blocklisted' === $field_data['status'] ? 'blocklisted' : 'enabled';

    $payload = array(
        'email'  => $email,
        'name'   => $name,
        'status' => $status,
        'lists'  => array( $list_id ),
    );

    if ( ! empty( $field_data['customFields'] ) ) {
        $attribs_raw = adfoin_get_parsed_values( $field_data['customFields'], $posted_data );
        $attribs     = json_decode( $attribs_raw, true );

        if ( json_last_error() === JSON_ERROR_NONE && is_array( $attribs ) ) {
            $payload['attribs'] = $attribs;
        }
    }

    if ( ! empty( $field_data['preconfirm'] ) && 'true' === $field_data['preconfirm'] ) {
        $payload['preconfirm_subscriptions'] = true;
    }

    $response = adfoin_listmonk_api_request( $credentials, 'api/subscribers', 'POST', $payload, $record );

    if ( is_wp_error( $response ) ) {
        return;
    }

    $status_code = wp_remote_retrieve_response_code( $response );

    if ( $status_code < 200 || $status_code >= 300 ) {
        return;
    }
}

function adfoin_listmonk_api_request( $credentials, $endpoint, $method = 'GET', $payload = array(), $record = array(), $query = array() ) {
    $base_url = adfoin_listmonk_normalize_base_url( $credentials );

    if ( is_wp_error( $base_url ) ) {
        return $base_url;
    }

    $username = isset( $credentials['username'] ) ? trim( $credentials['username'] ) : '';
    $password = isset( $credentials['password'] ) ? trim( $credentials['password'] ) : '';

    if ( empty( $username ) || empty( $password ) ) {
        return new WP_Error( 'listmonk_missing_auth', __( 'Listmonk username or password/token is missing.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );

    if ( ! empty( $query ) ) {
        $url = add_query_arg( $query, $url );
    }

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
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

function adfoin_listmonk_normalize_base_url( $credentials ) {
    $base_url = isset( $credentials['baseUrl'] ) ? trim( $credentials['baseUrl'] ) : '';

    if ( empty( $base_url ) ) {
        return new WP_Error( 'listmonk_missing_base', __( 'Listmonk base URL is missing.', 'advanced-form-integration' ) );
    }

    if ( ! preg_match( '#^https?://#i', $base_url ) ) {
        $base_url = 'https://' . $base_url;
    }

    return untrailingslashit( $base_url );
}

function adfoin_listmonk_extract_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['message'] ) ) {
        return $body['message'];
    }

    return __( 'Unexpected Listmonk API error.', 'advanced-form-integration' );
}
