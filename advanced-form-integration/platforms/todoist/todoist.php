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
        __( '<p>Create a Todoist <strong>REST API token</strong> under %1$sIntegrations → Developer%2$s and paste it here. The integration uses <code>https://api.todoist.com/rest/v2</code> endpoints to list projects/sections and create tasks.</p>', 'advanced-form-integration' ),
        '<a href="https://developer.todoist.com/api/v1/" target="_blank" rel="noopener noreferrer">',
        '</a>'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'wp_ajax_adfoin_get_todoist_credentials', 'adfoin_get_todoist_credentials', 10, 0 );

function adfoin_get_todoist_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $credentials = adfoin_read_credentials( 'todoist' );

    wp_send_json_success( $credentials );
}

add_action( 'wp_ajax_adfoin_save_todoist_credentials', 'adfoin_save_todoist_credentials', 10, 0 );

function adfoin_save_todoist_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

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
                    <div class="spinner" v-bind:class="{'is-active': projectLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
                    <div class="spinner" v-bind:class="{'is-active': sectionLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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
                    <div class="spinner" v-bind:class="{'is-active': labelLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

    $base_url = 'https://api.todoist.com/rest/v2/';
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

add_action( 'wp_ajax_adfoin_get_todoist_projects', 'adfoin_get_todoist_projects', 10, 0 );

function adfoin_get_todoist_projects() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error();
    }

    $response = adfoin_todoist_request( 'projects', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error();
    }

    $body     = json_decode( wp_remote_retrieve_body( $response ), true );
    $projects = array();

    if ( is_array( $body ) ) {
        foreach ( $body as $project ) {
            if ( isset( $project['id'], $project['name'] ) ) {
                $projects[ $project['id'] ] = $project['name'];
            }
        }
    }

    wp_send_json_success( $projects );
}

add_action( 'wp_ajax_adfoin_get_todoist_sections', 'adfoin_get_todoist_sections', 10, 0 );

function adfoin_get_todoist_sections() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id    = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $project_id = isset( $_POST['projectId'] ) ? sanitize_text_field( wp_unslash( $_POST['projectId'] ) ) : '';

    if ( empty( $cred_id ) || empty( $project_id ) ) {
        wp_send_json_error();
    }

    $endpoint = sprintf( 'sections?project_id=%s', rawurlencode( $project_id ) );
    $response = adfoin_todoist_request( $endpoint, 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error();
    }

    $body     = json_decode( wp_remote_retrieve_body( $response ), true );
    $sections = array();

    if ( is_array( $body ) ) {
        foreach ( $body as $section ) {
            if ( isset( $section['id'], $section['name'] ) ) {
                $sections[ $section['id'] ] = $section['name'];
            }
        }
    }

    wp_send_json_success( $sections );
}

add_action( 'wp_ajax_adfoin_get_todoist_labels', 'adfoin_get_todoist_labels', 10, 0 );

function adfoin_get_todoist_labels() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error();
    }

    $response = adfoin_todoist_request( 'labels', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error();
    }

    $body   = json_decode( wp_remote_retrieve_body( $response ), true );
    $labels = array();

    if ( is_array( $body ) ) {
        foreach ( $body as $label ) {
            if ( isset( $label['name'] ) ) {
                $labels[] = array(
                    'id'   => isset( $label['id'] ) ? $label['id'] : '',
                    'name' => $label['name'],
                );
            }
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
