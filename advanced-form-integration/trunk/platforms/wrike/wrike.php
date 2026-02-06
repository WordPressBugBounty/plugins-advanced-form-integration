<?php

add_filter( 'adfoin_action_providers', 'adfoin_wrike_actions', 10, 1 );

function adfoin_wrike_actions( $actions ) {

    $actions['wrike'] = array(
        'title' => __( 'Wrike', 'advanced-form-integration' ),
        'tasks' => array(
            'create_task' => __( 'Create Task', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_wrike_settings_tab', 10, 1 );

function adfoin_wrike_settings_tab( $tabs ) {
    $tabs['wrike'] = __( 'Wrike', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_wrike_settings_view', 10, 1 );

function adfoin_wrike_settings_view( $current_tab ) {
    if ( 'wrike' !== $current_tab ) {
        return;
    }

    $title     = __( 'Wrike', 'advanced-form-integration' );
    $key       = 'wrike';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'title',
                    'label' => __( 'Credential Label', 'advanced-form-integration' ),
                ),
                array(
                    'key'    => 'apiToken',
                    'label'  => __( 'Permanent Access Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        /* translators: 1: open anchor, 2: close anchor */
        __( '<p>Generate a <strong>Permanent Access Token</strong> inside Wrike (Personal profile → Access → API). Paste the token here to authorize AFI. All requests go through the Wrike REST API v4 (<code>/folders/{id}/tasks</code>). See the %1$sWrike API docs%2$s for details.</p>', 'advanced-form-integration' ),
        '<a href="https://developers.wrike.com/documentation/api/overview" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_wrike_credentials', 'adfoin_get_wrike_credentials', 10, 0 );

function adfoin_get_wrike_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'wrike' );
    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_wrike_credentials', 'adfoin_save_wrike_credentials', 10, 0 );

function adfoin_save_wrike_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'wrike' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_wrike_credentials_list() {
    $credentials = adfoin_read_credentials( 'wrike' );

    foreach ( $credentials as $credential ) {
        $id    = isset( $credential['id'] ) ? $credential['id'] : '';
        $title = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_wrike_action_fields' );

function adfoin_wrike_action_fields() {
    ?>
    <script type="text/template" id="wrike-action-template">
        <table class="form-table" v-if="action.task == 'create_task'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Wrike Credential', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_wrike_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Folder / Project', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[folderId]" v-model="fielddata.folderId">
                        <option value=""><?php esc_html_e( 'Select folder…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in fielddata.folders" :value="id">{{ label }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': folderLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php esc_html_e( 'Tasks will be created inside this folder or project.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Docs', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <a href="https://developers.wrike.com/api/v4/tasks/#create-task" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Wrike API reference', 'advanced-form-integration' ); ?></a>
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

/**
 * Wrike API helper.
 *
 * @param string $endpoint Endpoint relative to v4 root.
 * @param string $method   HTTP method.
 * @param array  $data     Request body/query.
 * @param array  $record   Record log context.
 * @param string $cred_id  Credential id.
 *
 * @return array|WP_Error
 */
function adfoin_wrike_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'wrike', $cred_id );
    $token       = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( empty( $token ) ) {
        return new WP_Error( 'wrike_missing_token', __( 'Wrike access token missing.', 'advanced-form-integration' ) );
    }

    $base_url = 'https://www.wrike.com/api/v4/';
    $url      = $base_url . ltrim( $endpoint, '/' );

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
        ),
        'timeout' => 20,
    );

    if ( 'GET' === strtoupper( $method ) ) {
        if ( ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }
    } else {
        $args['body'] = $data;
    }

    $response = wp_remote_request( $url, $args );

    if ( ! empty( $record ) ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_wrike_folders', 'adfoin_get_wrike_folders', 10, 0 );

function adfoin_get_wrike_folders() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error( __( 'Credential missing.', 'advanced-form-integration' ) );
    }

    $response = adfoin_wrike_request( 'folders', 'GET', array( 'perPage' => 1000 ), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error( __( 'Unable to fetch folders.', 'advanced-form-integration' ) );
    }

    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $folders = array();

    if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
        foreach ( $body['data'] as $folder ) {
            if ( isset( $folder['id'], $folder['title'] ) ) {
                $path = isset( $folder['path'] ) && is_array( $folder['path'] ) ? implode( ' / ', $folder['path'] ) : '';
                $folders[ $folder['id'] ] = $path ? $path . ' / ' . $folder['title'] : $folder['title'];
            }
        }
    }

    wp_send_json_success( $folders );
}

add_action( 'adfoin_wrike_job_queue', 'adfoin_wrike_job_queue', 10, 1 );

function adfoin_wrike_job_queue( $data ) {
    adfoin_wrike_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_wrike_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'create_task' !== $task ) {
        return;
    }

    $cred_id  = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $folderId = isset( $field_data['folderId'] ) ? $field_data['folderId'] : '';
    $title    = empty( $field_data['title'] ) ? '' : adfoin_get_parsed_values( $field_data['title'], $posted_data );

    if ( empty( $cred_id ) || empty( $folderId ) || empty( $title ) ) {
        return;
    }

    $description   = empty( $field_data['description'] ) ? '' : adfoin_get_parsed_values( $field_data['description'], $posted_data );
    $importance    = empty( $field_data['importance'] ) ? '' : strtolower( adfoin_get_parsed_values( $field_data['importance'], $posted_data ) );
    $status        = empty( $field_data['status'] ) ? '' : strtoupper( adfoin_get_parsed_values( $field_data['status'], $posted_data ) );
    $start_date    = empty( $field_data['startDate'] ) ? '' : adfoin_get_parsed_values( $field_data['startDate'], $posted_data );
    $due_date      = empty( $field_data['dueDate'] ) ? '' : adfoin_get_parsed_values( $field_data['dueDate'], $posted_data );
    $responsibles  = empty( $field_data['responsibles'] ) ? '' : adfoin_get_parsed_values( $field_data['responsibles'], $posted_data );

    $payload = array(
        'title' => $title,
    );

    if ( $description ) {
        $payload['description'] = $description;
    }

    if ( in_array( $importance, array( 'low', 'normal', 'high' ), true ) ) {
        $payload['importance'] = $importance;
    }

    if ( $status && in_array( $status, array( 'ACTIVE', 'COMPLETED', 'DEFERRED', 'CANCELLED' ), true ) ) {
        $payload['status'] = $status;
    }

    if ( $start_date || $due_date ) {
        $dates = array();

        if ( $start_date ) {
            $timestamp = strtotime( $start_date );
            if ( false !== $timestamp ) {
                $dates['start'] = gmdate( 'Y-m-d', $timestamp );
            }
        }

        if ( $due_date ) {
            $timestamp = strtotime( $due_date );
            if ( false !== $timestamp ) {
                $dates['due'] = gmdate( 'Y-m-d', $timestamp );
            }
        }

        if ( ! empty( $dates ) ) {
            $payload['dates'] = $dates;
        }
    }

    if ( $responsibles ) {
        $emails = array_filter( array_map( 'trim', explode( ',', $responsibles ) ) );
        if ( ! empty( $emails ) ) {
            $payload['responsibles'] = $emails;
        }
    }

    adfoin_wrike_request( "folders/{$folderId}/tasks", 'POST', $payload, $record, $cred_id );
}
