<?php

add_filter( 'adfoin_action_providers', 'adfoin_mstodo_actions', 10, 1 );

function adfoin_mstodo_actions( $actions ) {

    $actions['mstodo'] = array(
        'title' => __( 'Microsoft To Do', 'advanced-form-integration' ),
        'tasks' => array(
            'create_task' => __( 'Create Task', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mstodo_settings_tab', 10, 1 );

function adfoin_mstodo_settings_tab( $tabs ) {
    $tabs['mstodo'] = __( 'Microsoft To Do', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_mstodo_settings_view', 10, 1 );

function adfoin_mstodo_settings_view( $current_tab ) {
    if ( 'mstodo' !== $current_tab ) {
        return;
    }

    $title = __( 'Microsoft To Do', 'advanced-form-integration' );
    $key   = 'mstodo';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'tenantId',
                    'label' => __( 'Tenant ID (Directory ID)', 'advanced-form-integration' ),
                ),
                array(
                    'key'   => 'clientId',
                    'label' => __( 'Client ID (Application ID)', 'advanced-form-integration' ),
                ),
                array(
                    'key'    => 'clientSecret',
                    'label'  => __( 'Client Secret', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
                array(
                    'key'    => 'refreshToken',
                    'label'  => __( 'OAuth Refresh Token (Tasks.ReadWrite granted)', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: link open, 2: link close */
        __( '<p>This action uses the Microsoft Graph To Do endpoints (<code>/me/todo/lists</code> and <code>/tasks</code>). Create an Azure AD app, allow the <strong>Tasks.ReadWrite</strong> and <strong>offline_access</strong> delegated scopes, grant admin consent, and capture a refresh token using the OAuth authorization code flow. Paste the tenant ID, client credentials, and refresh token above. See the %1$sMicrosoft Graph To Do docs%2$s for details.</p>', 'advanced-form-integration' ),
        '<a href="https://learn.microsoft.com/graph/api/resources/todo-overview" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_mstodo_credentials', 'adfoin_get_mstodo_credentials', 10, 0 );

function adfoin_get_mstodo_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'mstodo' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_mstodo_credentials', 'adfoin_save_mstodo_credentials', 10, 0 );

function adfoin_save_mstodo_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'mstodo' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_mstodo_credentials_list() {
    $credentials = adfoin_read_credentials( 'mstodo' );

    foreach ( $credentials as $credential ) {
        $id    = isset( $credential['id'] ) ? $credential['id'] : '';
        $title = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_mstodo_action_fields' );

function adfoin_mstodo_action_fields() {
    ?>
    <script type="text/template" id="mstodo-action-template">
        <table class="form-table" v-if="action.task == 'create_task'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Graph Notice', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="notice notice-info inline">
                        <p><?php esc_html_e( 'Microsoft To Do requires a delegated Microsoft Graph connection. Ensure your refresh token is kept private and reauthorize when it expires.', 'advanced-form-integration' ); ?></p>
                        <p><a href="https://learn.microsoft.com/graph/api/todotask-post-tasks" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Graph To Do API reference', 'advanced-form-integration' ); ?></a></p>
                    </div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Credential', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_mstodo_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'To Do List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php esc_html_e( 'Select list…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in fielddata.lists" :value="id">{{ label }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php esc_html_e( 'Lists are pulled from Microsoft To Do / Planner via Graph.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_mstodo_lists', 'adfoin_get_mstodo_lists', 10, 0 );

function adfoin_get_mstodo_lists() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error( __( 'Credential is required.', 'advanced-form-integration' ) );
    }

    $response = adfoin_mstodo_request( 'me/todo/lists', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error( __( 'Unable to fetch lists.', 'advanced-form-integration' ) );
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $lists = array();

    if ( isset( $body['value'] ) && is_array( $body['value'] ) ) {
        foreach ( $body['value'] as $list ) {
            if ( isset( $list['id'], $list['displayName'] ) ) {
                $lists[ $list['id'] ] = $list['displayName'];
            }
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_mstodo_job_queue', 'adfoin_mstodo_job_queue', 10, 1 );

function adfoin_mstodo_job_queue( $data ) {
    adfoin_mstodo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mstodo_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'create_task' !== $task ) {
        return;
    }

    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $list_id = isset( $field_data['listId'] ) ? $field_data['listId'] : '';

    if ( empty( $cred_id ) || empty( $list_id ) ) {
        return;
    }

    $title = empty( $field_data['title'] ) ? '' : adfoin_get_parsed_values( $field_data['title'], $posted_data );

    if ( empty( $title ) ) {
        return;
    }

    $body_content     = empty( $field_data['bodyContent'] ) ? '' : adfoin_get_parsed_values( $field_data['bodyContent'], $posted_data );
    $body_type        = empty( $field_data['bodyContentType'] ) ? 'text' : strtolower( adfoin_get_parsed_values( $field_data['bodyContentType'], $posted_data ) );
    $due_datetime     = empty( $field_data['dueDateTime'] ) ? '' : adfoin_get_parsed_values( $field_data['dueDateTime'], $posted_data );
    $due_timezone     = empty( $field_data['dueTimeZone'] ) ? 'UTC' : adfoin_get_parsed_values( $field_data['dueTimeZone'], $posted_data );
    $reminder_dt      = empty( $field_data['reminderDateTime'] ) ? '' : adfoin_get_parsed_values( $field_data['reminderDateTime'], $posted_data );
    $reminder_timezone= empty( $field_data['reminderTimeZone'] ) ? 'UTC' : adfoin_get_parsed_values( $field_data['reminderTimeZone'], $posted_data );
    $importance       = empty( $field_data['importance'] ) ? '' : strtolower( adfoin_get_parsed_values( $field_data['importance'], $posted_data ) );
    $categories_value = empty( $field_data['categories'] ) ? '' : adfoin_get_parsed_values( $field_data['categories'], $posted_data );

    $payload = array(
        'title' => $title,
    );

    if ( $body_content ) {
        if ( ! in_array( $body_type, array( 'text', 'html' ), true ) ) {
            $body_type = 'text';
        }
        $payload['body'] = array(
            'contentType' => $body_type,
            'content'     => $body_content,
        );
    }

    if ( $due_datetime ) {
        $payload['dueDateTime'] = array(
            'dateTime' => $due_datetime,
            'timeZone' => $due_timezone ? $due_timezone : 'UTC',
        );
    }

    if ( $reminder_dt ) {
        $payload['reminderDateTime'] = array(
            'dateTime' => $reminder_dt,
            'timeZone' => $reminder_timezone ? $reminder_timezone : 'UTC',
        );
    }

    if ( in_array( $importance, array( 'low', 'normal', 'high' ), true ) ) {
        $payload['importance'] = $importance;
    }

    if ( $categories_value ) {
        $categories = array_filter( array_map( 'trim', explode( ',', $categories_value ) ) );
        if ( ! empty( $categories ) ) {
            $payload['categories'] = array_values( $categories );
        }
    }

    adfoin_mstodo_request( "me/todo/lists/{$list_id}/tasks", 'POST', $payload, $record, $cred_id );
}

function adfoin_mstodo_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $access_token = adfoin_mstodo_get_access_token( $cred_id );

    if ( is_wp_error( $access_token ) ) {
        return $access_token;
    }

    $url  = 'https://graph.microsoft.com/v1.0/' . ltrim( $endpoint, '/' );
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ),
        'timeout' => 20,
    );

    if ( 'GET' !== $method && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( ! empty( $record ) ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

function adfoin_mstodo_get_access_token( $cred_id ) {
    if ( empty( $cred_id ) ) {
        return new WP_Error( 'mstodo_missing_cred', __( 'Microsoft To Do credential missing.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_mstodo_token_' . $cred_id;
    $cached    = get_transient( $cache_key );

    if ( $cached ) {
        return $cached;
    }

    $credentials = adfoin_get_credentials_by_id( 'mstodo', $cred_id );

    if ( empty( $credentials ) ) {
        return new WP_Error( 'mstodo_cred_not_found', __( 'Microsoft To Do credentials not found.', 'advanced-form-integration' ) );
    }

    $tenant_id     = isset( $credentials['tenantId'] ) ? $credentials['tenantId'] : '';
    $client_id     = isset( $credentials['clientId'] ) ? $credentials['clientId'] : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? $credentials['clientSecret'] : '';
    $refresh_token = isset( $credentials['refreshToken'] ) ? $credentials['refreshToken'] : '';

    if ( empty( $tenant_id ) || empty( $client_id ) || empty( $client_secret ) || empty( $refresh_token ) ) {
        return new WP_Error( 'mstodo_incomplete_creds', __( 'Microsoft To Do credentials are incomplete.', 'advanced-form-integration' ) );
    }

    $token_url = trailingslashit( 'https://login.microsoftonline.com/' . $tenant_id ) . 'oauth2/v2.0/token';

    $body = array(
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refresh_token,
        'scope'         => 'offline_access Tasks.ReadWrite https://graph.microsoft.com/.default',
    );

    $response = wp_remote_post(
        $token_url,
        array(
            'body'    => $body,
            'timeout' => 20,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return new WP_Error( 'mstodo_token_error', __( 'Failed to refresh Microsoft Graph token.', 'advanced-form-integration' ) );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $data['access_token'] ) ) {
        return new WP_Error( 'mstodo_token_missing', __( 'Microsoft Graph token missing in response.', 'advanced-form-integration' ) );
    }

    $access_token = $data['access_token'];
    $expires_in   = isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;
    $ttl          = max( 60, $expires_in - 120 );

    set_transient( $cache_key, $access_token, $ttl );

    return $access_token;
}
