<?php

add_filter( 'adfoin_action_providers', 'adfoin_asana_actions', 10, 1 );

function adfoin_asana_actions( $actions ) {

    $actions['asana'] = [
        'title' => __( 'Asana', 'advanced-form-integration' ),
        'tasks' => [ 'create_task' => __( 'Create Task', 'advanced-form-integration' ) ]
        ];

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_asana_settings_tab', 10, 1 );

function adfoin_asana_settings_tab( $providers ) {
    $providers['asana'] = __( 'Asana', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_asana_settings_view', 10, 1 );

function adfoin_asana_settings_view( $current_tab ) {
    if( $current_tab != 'asana' ) {
        return;
    }

    $title = __( 'Asana', 'advanced-form-integration' );
    $key   = 'asana';
    $arguments = json_encode([
        'platform' => $key,
        'fields'   => [
            [
                'key'   => 'accessToken',
                'label' => __( 'Personal Access Token', 'advanced-form-integration' ),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = __( '<p>To find the Personal Access Token go to <a target="_blank" rel="noopener noreferrer" href="https://app.asana.com/0/developer-console">developer console</a> and create new access token.</p>', 'advanced-form-integration' );
    
    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_asana_credentials', 'adfoin_get_asana_credentials', 10, 0 );

function adfoin_get_asana_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'asana' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_asana_credentials', 'adfoin_save_asana_credentials', 10, 0 );
/*
 * Get Asana credentials
 */
function adfoin_save_asana_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'asana' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

add_filter('adfoin_get_credentials', 'adfoin_asana_modify_credentials', 10, 2);

function adfoin_asana_modify_credentials( $credentials, $platform ) {

    if ( 'asana' == $platform && empty( $credentials ) ) {
        $private_key = get_option( 'adfoin_asana_access_token' ) ? get_option( 'adfoin_asana_access_token' ) : '';

        if( $private_key ) {
            $credentials[] = [
                'id'         => '123456',
                'title'      => __( 'Untitled', 'advanced-form-integration' ),
                'accessToken' => $private_key
            ];
        }
    }

    return $credentials;
}

function adfoin_asana_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'asana' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_asana_action_fields' );

function adfoin_asana_action_fields() {
    ?>

    <script type="text/template" id="asana-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_task'">
                <th scope="row">
                    <?php esc_attr_e( 'Task Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_task'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Asana Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getWorkspaces">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_asana_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'create_task'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Workspace', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[workspaceId]" v-model="fielddata.workspaceId" required="true" @change="getProjects">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.workspaces" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': workspaceLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_task'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Project', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[projectId]" v-model="fielddata.projectId" required="true" @change="getSections">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.projects" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': projectLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_task'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Section', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[sectionId]" v-model="fielddata.sectionId">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.sections" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': sectionLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_task'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Assignee', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[userId]" v-model="fielddata.userId">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.users" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': userLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>

    <?php
}

/*
 * Asana API Request
 */
function adfoin_asana_request( $endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'asana', $cred_id );
    $api_token   = isset( $credentials['accessToken'] ) ? $credentials['accessToken'] : '';
    $base_url    = 'https://app.asana.com/api/1.0/';
    $url         = $base_url . $endpoint;

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_token
        ],
    ];

    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_asana_workspaces', function() { adfoin_fetch_asana_data('workspaces'); });
add_action('wp_ajax_adfoin_get_asana_projects', function() { adfoin_fetch_asana_data('projects', $_POST['workspaceId'] ?? ''); });
add_action('wp_ajax_adfoin_get_asana_users', function() { adfoin_fetch_asana_data('users', $_POST['workspaceId'] ?? ''); });
add_action('wp_ajax_adfoin_get_asana_sections', function() { adfoin_fetch_asana_data('sections', $_POST['projectId'] ?? ''); });

// Fetch data from Asana API and respond with JSON
function adfoin_fetch_asana_data($type, $id = '') {
    if (!adfoin_verify_nonce()) return;

    $endpoint = $type === 'projects' ? "workspaces/{$id}/projects" :
    ($type === 'users' ? "workspaces/{$id}/users" :
    ($type === 'sections' ? "projects/{$id}/sections" : "{$type}"));

    $response = adfoin_asana_request( $endpoint, 'GET', [], sanitize_text_field( $_POST['credId'] ?? '' ) );
    $body = json_decode( wp_remote_retrieve_body( $response ) );

    if ( $body ) {
        wp_send_json_success( wp_list_pluck( $body->data, 'name', 'gid' ) );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_asana_job_queue', 'adfoin_asana_job_queue', 10, 1 );

function adfoin_asana_job_queue( $data ) {
    adfoin_asana_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Asana API
 */
function adfoin_asana_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? [], $posted_data ) ) return;

    $data         = $record_data['field_data'];
    $task         = $record['task'];
    $cred_id      = empty( $data['credId'] ) ? '' : $data['credId'];
    $workspace_id = empty( $data['workspaceId'] ) ? '' : $data['workspaceId'];
    $project_id   = empty( $data['projectId'] ) ? '' : $data['projectId'];
    $section_id   = empty( $data['sectionId'] ) ? '' : $data['sectionId'];
    $user_id      = empty( $data['userId'] ) ? '' : $data['userId'];
    $name         = empty( $data['name'] ) ? '' : adfoin_get_parsed_values( $data['name'], $posted_data );
    $notes        = empty( $data['notes'] ) ? '' : adfoin_get_parsed_values( $data['notes'], $posted_data );
    $due_on       = empty( $data['dueOn'] ) ? '' : adfoin_get_parsed_values( $data['dueOn'], $posted_data );
    $due_on_x     = empty( $data['dueOnX'] ) ? '' : adfoin_get_parsed_values( $data['dueOnX'], $posted_data );

    if( $task == 'create_task' ) {

        $body = [
            'data' => [
                    'workspace' => $workspace_id,
                    'projects'  => [$project_id],
                    'name'      => $name,
                    'notes'     => $notes,
                    'due_on'    => $due_on
                ]
            ];

        if( isset( $due_on_x ) && $due_on_x ) {
            $after_days = (int) $due_on_x;

            if( $after_days ) {
                $timezone             = wp_timezone();
                $date                 = date_create( '+' . $after_days . ' days', $timezone );
                $formatted_date       = date_format( $date, 'Y-m-d' );
                $body['data']['due_on'] = $formatted_date;
            }
        }

        if( $user_id ) {
            $body['data']['assignee'] = $user_id;
        }

        $body['data'] = array_filter( $body['data'] );
        $response     = adfoin_asana_request( 'tasks', 'POST', $body, $record );
        $task_id      = '';

        if( $section_id ) {
            if( '201' == wp_remote_retrieve_response_code( $response ) ) {
                $body    = json_decode( wp_remote_retrieve_body( $response ) );
                $task_id = $body->data->gid;
    
                $body = [
                    'data' => [
                        'task' => $task_id
                    ]
                ];
        
                $response = adfoin_asana_request( "sections/{$section_id}/addTask", 'POST', $body, $record, $cred_id );
                
            }
        }
    }

    return;
}