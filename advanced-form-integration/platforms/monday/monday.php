<?php

add_filter('adfoin_action_providers', 'adfoin_monday_actions', 10, 1);

function adfoin_monday_actions($actions) {
    $actions['monday'] = [
        'title' => __('Monday.com', 'advanced-form-integration'),
        'tasks' => [
            'create_item' => __('Create Item', 'advanced-form-integration'),
        ]
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_monday_settings_tab', 10, 1);

function adfoin_monday_settings_tab($providers) {
    $providers['monday'] = __('Monday.com', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_monday_settings_view', 10, 1);

function adfoin_monday_settings_view($current_tab) {
    if ($current_tab != 'monday') {
        return;
    }

    $title = __('Monday.com', 'advanced-form-integration');
    $key   = 'monday';
    $arguments = json_encode([
        'platform' => $key,
        'fields'   => [
            [
                'key'   => 'accessToken',
                'label' => __('Access Token', 'advanced-form-integration'),
                'hidden' => true
            ]
        ]
    ]);
    $instructions = __('<p>Go to Profile > Developers > My access tokens and copy the access token.</p>', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_monday_credentials', 'adfoin_get_monday_credentials', 10, 0);

function adfoin_get_monday_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('monday');

    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_monday_credentials', 'adfoin_save_monday_credentials', 10, 0);

function adfoin_save_monday_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);

    if ('monday' == $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_monday_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials('monday');

    foreach ($credentials as $option) {
        $html .= '<option value="' . $option['id'] . '">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_monday_action_fields');

function adfoin_monday_action_fields() {
    ?>
    <script type="text/template" id="monday-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_item'">
                <th scope="row"><?php esc_attr_e('Map Fields', 'advanced-form-integration'); ?></th>
                <td>
                <div class="spinner" v-bind:class="{'is-active': itemsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_item'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Monday.com Account', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getBoards">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php
                            adfoin_monday_credentials_list();
                        ?>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_item'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Board', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[boardId]" v-model="fielddata.boardId" required="required" @change="getFields">
                        <option value=""> <?php _e( 'Select Board...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.boards" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': boardLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_item'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e('Group', 'advanced-form-integration'); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[groupId]" v-model="fielddata.groupId">
                        <option value=""><?php _e('Select Group...', 'advanced-form-integration'); ?></option>
                        <option v-for="(item, index) in fielddata.groups" :value="index">{{item}}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': groupLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_monday_columns', 'adfoin_get_monday_columns');

function adfoin_get_monday_columns() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $board_id = sanitize_text_field($_POST['boardId']);

    $query = 'query {
        boards (ids: ' . $board_id . ') {
            columns {
                id
                title
            }
        }
    }';

    $response = adfoin_monday_request('boards', 'POST', $query, $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching columns');
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || isset($body['errors'])) {
        wp_send_json_error('Error in response body');
        return;
    }

    $columns = $body['data']['boards'][0]['columns'] ?? [];
    $columns_array = [];

    if ($columns) {
        foreach ($columns as $column) {
            $columns_array[] = ['key' => $column['id'], 'value' => $column['title']];
        }
    }

    wp_send_json_success($columns_array);
}

add_action('wp_ajax_adfoin_get_monday_boards', 'adfoin_monday_get_boards');

function adfoin_monday_get_boards() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $query = 'query {
        boards {
            id
            name
        }
    }';

    $response = adfoin_monday_request('boards', 'POST', $query, $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching boards');
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || isset($body['errors'])) {
        wp_send_json_error('Error in response body');
        return;
    }

    $boards = $body['data']['boards'] ?? [];
    $boards_array = [];

    if ($boards) {
        foreach ($boards as $board) {
            $boards_array[$board['id']] = $board['name'];
        }
    }

    wp_send_json_success($boards_array);
}

add_action( 'wp_ajax_adfoin_get_monday_groups', 'adfoin_monday_get_groups' );

function adfoin_monday_get_groups() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $board_id = sanitize_text_field($_POST['boardId']);

    $query = 'query {
        boards (ids: ' . $board_id . ') {
            id
            groups {
                id
                title
            }
        }
    }';

    $response = adfoin_monday_request('boards', 'POST', $query, $cred_id);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching groups');
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || isset($body['errors'])) {
        wp_send_json_error('Error in response body');
        return;
    }

    $groups = $body['data']['boards'][0]['groups'] ?? [];
    $groups_array = [];

    if ($groups) {
        foreach ($groups as $group) {
            $groups_array[$group['id']] = $group['title'];
        }
    }

    wp_send_json_success($groups_array);
}

function adfoin_monday_request($endpoint, $method = 'POST', $data ='', $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('monday', $cred_id);
    $access_token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';
    $base_url = 'https://api.monday.com/v2';

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => $access_token,
        ],
    ];

    if (!empty($data)) {
        $args['body'] = json_encode(['query' => $data]);
    }

    $response = wp_remote_request($base_url, $args);

    if( $record ) {
        adfoin_add_to_log($response, $base_url, $args, $record);
    }

    return $response;
}

add_action('adfoin_monday_job_queue', 'adfoin_monday_job_queue', 10, 1);

function adfoin_monday_job_queue($data) {
    adfoin_monday_send_data($data['record'], $data['posted_data']);
}

function adfoin_monday_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = empty($data['credId']) ? '' : $data['credId'];

    if ($task == 'create_item') {
        $board_id = $data['boardId'];
        $group_id = $data['groupId'];
        $item_name = empty($data['name']) ? '' : adfoin_get_parsed_values($data['name'], $posted_data);
    
        unset($data['credId'], $data['boardId'], $data['groupId']);

        $column_values = [];

        foreach ($data as $key => $value) {
            $column_values[$key] = adfoin_get_parsed_values($value, $posted_data);
        }

        $column_values = json_encode(array_filter( $column_values ) );

        $query = 'mutation {
            create_item (board_id: ' . $board_id . ', group_id: "' . $group_id . '", item_name: "' . $item_name . '", column_values: "' . addslashes($column_values) . '") {
                id
            }
        }';

        $response = adfoin_monday_request('create_item', 'POST', $query, $cred_id);

        return;
    }
}