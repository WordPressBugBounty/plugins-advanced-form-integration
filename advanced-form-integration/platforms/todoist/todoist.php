<?php

add_filter( 'adfoin_action_providers', 'adfoin_todoist_actions', 10, 1 );

function adfoin_todoist_actions( $actions ) {

    $actions['todoist'] = array(
        'title' => __( 'Todoist', 'advanced-form-integration' ),
        'tasks' => array(
            'create_task' => __( 'Create Task', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_todoist_settings_tab', 10, 1 );

function adfoin_todoist_settings_tab( $tabs ) {
    $tabs['todoist'] = __( 'Todoist', 'advanced-form-integration' );

    return $tabs;
}

add_action( 'adfoin_settings_view', 'adfoin_todoist_settings_view', 10, 1 );

function adfoin_todoist_settings_view( $current_tab ) {
    if ( 'todoist' !== $current_tab ) {
        return;
    }

    $title       = __( 'Todoist', 'advanced-form-integration' );
    $key         = 'todoist';
    $arguments   = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'   => 'apiToken',
                    'label' => __( 'REST API Token', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );
    $instructions = sprintf(
        /* translators: 1: opening anchor tag, 2: closing anchor tag */
        __( '<p>Create a Todoist <strong>API token</strong> under %1$sIntegrations → Developer%2$s and paste it here. The integration talks to <code>https://api.todoist.com/api/v1/</code> (Todoist&rsquo;s unified v1 API) to list projects, sections, and labels, and to create tasks.</p>', 'advanced-form-integration' ),
        '<a href="https://developer.todoist.com/api/v1/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_todoist_credentials', 'adfoin_get_todoist_credentials', 10, 0 );

function adfoin_get_todoist_credentials() {
    adfoin_verify_nonce();

    $credentials = adfoin_read_credentials( 'todoist' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_todoist_credentials', 'adfoin_save_todoist_credentials', 10, 0 );

function adfoin_save_todoist_credentials() {

    adfoin_verify_nonce();

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'todoist' === $platform ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_todoist_credentials_list() {
    $credentials = adfoin_read_credentials( 'todoist' );

    foreach ( $credentials as $credential ) {
        $id    = isset( $credential['id'] ) ? $credential['id'] : '';
        $title = isset( $credential['title'] ) ? $credential['title'] : '';
        echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action( 'adfoin_action_fields', 'adfoin_todoist_action_fields' );

function adfoin_todoist_action_fields() {
    ?>
    <script type="text/template" id="todoist-action-template">
        <table class="form-table" v-if="action.task == 'create_task'">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Task Fields', 'advanced-form-integration' ); ?></th>
                <td scope="row"></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Todoist Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_todoist_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Project', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[projectId]" v-model="fielddata.projectId" @change="getSections">
                        <option value=""><?php esc_html_e( 'Select…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in fielddata.projects" :value="id">{{ label }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': projectLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Section', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[sectionId]" v-model="fielddata.sectionId">
                        <option value=""><?php esc_html_e( 'Select…', 'advanced-form-integration' ); ?></option>
                        <option v-for="(label, id) in fielddata.sections" :value="id">{{ label }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': sectionLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Labels', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select multiple="multiple" name="fieldData[labelNames][]" size="5" v-model="fielddata.labelNames">
                        <option v-for="label in fielddata.labelsList" :value="label.name">{{ label.name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': labelLoading}"></div>
                    <p class="description"><?php esc_html_e( 'Hold Cmd/Ctrl to select multiple static labels. Dynamic labels can be supplied via the field mapper below.', 'advanced-form-integration' ); ?></p>
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
 * Todoist API request helper.
 *
 * @param string $endpoint API endpoint path without leading slash.
 * @param string $method   HTTP method.
 * @param array  $data     Payload array.
 * @param array  $record   Log context.
 * @param string $cred_id  Credential id.
 *
 * @return array|WP_Error
 */
function adfoin_todoist_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'todoist', $cred_id );
    $api_token   = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( empty( $api_token ) ) {
        return new WP_Error( 'todoist_missing_token', __( 'Todoist API token is missing.', 'advanced-form-integration' ) );
    }

    // Todoist unified v1 API. The older /rest/v2/ paths still return
    // bare arrays from list endpoints; v1 wraps them in
    // { results: [...], next_cursor: ... } — the list-fetch helpers
    // below normalize both shapes so this stays forward-compatible.
    $base_url = 'https://api.todoist.com/api/v1/';
    $url      = $base_url . ltrim( $endpoint, '/' );

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type'  => 'application/json',
            'X-Request-Id'  => wp_generate_uuid4(),
        ),
        'timeout' => 20,
    );

    if ( ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( ! empty( $record ) ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

/**
 * Fetch every item from a Todoist v1 paginated list endpoint.
 *
 * v1 list responses look like:
 *
 *     { "results": [ {…}, {…} ], "next_cursor": "abc123" | null }
 *
 * We follow `next_cursor` until exhausted, capped at 10 pages so a
 * runaway pagination loop can never hang the action editor. Old v2
 * endpoints return a bare array — handled by the fall-through.
 *
 * @param string $endpoint Path with no leading slash (e.g. "projects").
 * @param string $cred_id  Credential id.
 * @param array  $extra    Extra query args to add on each page.
 * @return array Flat array of items, or empty array on any error.
 */
function adfoin_todoist_fetch_paginated( $endpoint, $cred_id, $extra = array() ) {
    $items     = array();
    $cursor    = '';
    $max_pages = 10;

    for ( $i = 0; $i < $max_pages; $i++ ) {
        $query = $extra;
        if ( '' !== $cursor ) {
            $query['cursor'] = $cursor;
        }

        $path = $endpoint;
        if ( ! empty( $query ) ) {
            $separator = ( false === strpos( $path, '?' ) ) ? '?' : '&';
            $path .= $separator . http_build_query( $query );
        }

        $response = adfoin_todoist_request( $path, 'GET', array(), array(), $cred_id );
        if ( is_wp_error( $response ) ) {
            return $items;
        }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return $items;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            return $items;
        }

        // v1 shape: { results, next_cursor }. v2 shape: bare array.
        if ( isset( $body['results'] ) && is_array( $body['results'] ) ) {
            $items  = array_merge( $items, $body['results'] );
            $cursor = ! empty( $body['next_cursor'] ) ? (string) $body['next_cursor'] : '';
            if ( '' === $cursor ) {
                break;
            }
        } else {
            // Bare-array (legacy) — single page, done.
            $items = array_merge( $items, $body );
            break;
        }
    }

    return $items;
}

add_action( 'wp_ajax_adfoin_get_todoist_projects', 'adfoin_get_todoist_projects', 10, 0 );

function adfoin_get_todoist_projects() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error();
    }

    $rows     = adfoin_todoist_fetch_paginated( 'projects', $cred_id );
    $projects = array();

    foreach ( $rows as $project ) {
        if ( isset( $project['id'], $project['name'] ) ) {
            $projects[ (string) $project['id'] ] = $project['name'];
        }
    }

    wp_send_json_success( $projects );
}

add_action( 'wp_ajax_adfoin_get_todoist_sections', 'adfoin_get_todoist_sections', 10, 0 );

function adfoin_get_todoist_sections() {
    adfoin_verify_nonce();

    $cred_id    = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $project_id = isset( $_POST['projectId'] ) ? sanitize_text_field( wp_unslash( $_POST['projectId'] ) ) : '';

    if ( empty( $cred_id ) || empty( $project_id ) ) {
        wp_send_json_error();
    }

    $rows     = adfoin_todoist_fetch_paginated( 'sections', $cred_id, array( 'project_id' => $project_id ) );
    $sections = array();

    foreach ( $rows as $section ) {
        if ( isset( $section['id'], $section['name'] ) ) {
            $sections[ (string) $section['id'] ] = $section['name'];
        }
    }

    wp_send_json_success( $sections );
}

add_action( 'wp_ajax_adfoin_get_todoist_labels', 'adfoin_get_todoist_labels', 10, 0 );

function adfoin_get_todoist_labels() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error();
    }

    $rows   = adfoin_todoist_fetch_paginated( 'labels', $cred_id );
    $labels = array();

    foreach ( $rows as $label ) {
        if ( isset( $label['name'] ) ) {
            $labels[] = array(
                'id'   => isset( $label['id'] ) ? (string) $label['id'] : '',
                'name' => $label['name'],
            );
        }
    }

    wp_send_json_success( $labels );
}

add_action( 'adfoin_todoist_job_queue', 'adfoin_todoist_job_queue', 10, 1 );

function adfoin_todoist_job_queue( $data ) {
    adfoin_todoist_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_todoist_send_data( $record, $posted_data ) {
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

    if ( empty( $cred_id ) ) {
        return;
    }

    $content = empty( $field_data['content'] ) ? '' : adfoin_get_parsed_values( $field_data['content'], $posted_data );

    if ( empty( $content ) ) {
        return;
    }

    $description = empty( $field_data['description'] ) ? '' : adfoin_get_parsed_values( $field_data['description'], $posted_data );
    $project_id  = isset( $field_data['projectId'] ) ? $field_data['projectId'] : '';
    $section_id  = isset( $field_data['sectionId'] ) ? $field_data['sectionId'] : '';
    $due_date    = empty( $field_data['dueDate'] ) ? '' : adfoin_get_parsed_values( $field_data['dueDate'], $posted_data );
    $due_string  = empty( $field_data['dueString'] ) ? '' : adfoin_get_parsed_values( $field_data['dueString'], $posted_data );
    $priority    = empty( $field_data['priority'] ) ? '' : adfoin_get_parsed_values( $field_data['priority'], $posted_data );

    $static_labels = array();
    if ( ! empty( $field_data['labelNames'] ) ) {
        $static_labels = is_array( $field_data['labelNames'] ) ? $field_data['labelNames'] : array( $field_data['labelNames'] );
        $static_labels = array_filter( array_map( 'sanitize_text_field', $static_labels ) );
    }

    $dynamic_labels = array();
    if ( ! empty( $field_data['labels'] ) ) {
        $parsed = adfoin_get_parsed_values( $field_data['labels'], $posted_data );
        if ( $parsed ) {
            $pieces = array_map( 'trim', explode( ',', $parsed ) );
            $dynamic_labels = array_filter( $pieces );
        }
    }

    $labels = array_values( array_unique( array_merge( $static_labels, $dynamic_labels ) ) );

    $payload = array(
        'content' => $content,
    );

    if ( $description ) {
        $payload['description'] = $description;
    }

    if ( $project_id ) {
        $payload['project_id'] = $project_id;
    }

    if ( $section_id ) {
        $payload['section_id'] = $section_id;
    }

    if ( $due_string ) {
        $payload['due_string'] = $due_string;
    } elseif ( $due_date ) {
        $timestamp = strtotime( $due_date );
        if ( $timestamp ) {
            $payload['due_date'] = gmdate( 'Y-m-d', $timestamp );
        }
    }

    if ( $priority ) {
        $priority = (int) $priority;
        $priority = max( 1, min( 4, $priority ) );
        $payload['priority'] = $priority;
    }

    if ( ! empty( $labels ) ) {
        $payload['labels'] = array_values( $labels );
    }

    adfoin_todoist_request( 'tasks', 'POST', $payload, $record, $cred_id );
}
