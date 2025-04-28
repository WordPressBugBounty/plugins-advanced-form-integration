<?php

add_filter('adfoin_action_providers', 'adfoin_quickbase_actions', 10, 1);
function adfoin_quickbase_actions($actions) {
    $actions['quickbase'] = array(
        'title' => __('Quickbase', 'advanced-form-integration'),
        'tasks' => array(
            'add' => __('Add Record', 'advanced-form-integration')
        )
    );
    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_quickbase_settings_tab', 10, 1);
function adfoin_quickbase_settings_tab($providers) {
    $providers['quickbase'] = __('Quickbase', 'advanced-form-integration');
    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_quickbase_settings_view', 10, 1);
function adfoin_quickbase_settings_view($current_tab) {
    if ($current_tab != 'quickbase') return;

    $title = __('Quickbase', 'advanced-form-integration');
    $key = 'quickbase';
    $arguments = json_encode([
        'platform' => $key,
        'fields'   => [
            [ 'key' => 'accessToken', 'label' => __('User Token', 'advanced-form-integration'), 'hidden' => true ],
            [ 'key' => 'realmHostname', 'label' => __('Realm Hostname', 'advanced-form-integration'), 'hidden' => true ]
        ]
    ]);
    $instructions = __('To find your Quickbase User Token, visit your Quickbase admin settings and generate a user token with access to the apps and tables you want to integrate.', 'advanced-form-integration');

    echo adfoin_platform_settings_template($title, $key, $arguments, $instructions);
}

add_action('wp_ajax_adfoin_get_quickbase_credentials', 'adfoin_get_quickbase_credentials', 10, 0);
function adfoin_get_quickbase_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials('quickbase');
    wp_send_json_success($all_credentials);
}

add_action('wp_ajax_adfoin_save_quickbase_credentials', 'adfoin_save_quickbase_credentials', 10, 0);

function adfoin_save_quickbase_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field($_POST['platform']);
    if ('quickbase' === $platform) {
        $data = adfoin_array_map_recursive('sanitize_text_field', $_POST['data']);
        adfoin_save_credentials($platform, $data);
    }

    wp_send_json_success();
}

function adfoin_quickbase_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'quickbase' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_quickbase_action_fields' );

function adfoin_quickbase_action_fields() {
    ?>
    <script type="text/template" id="quickbase-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': tablesLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'add'">
                <td><label><?php esc_attr_e('Quickbase Account', 'advanced-form-integration'); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getTables">
                        <option value=""><?php _e('Select Account...', 'advanced-form-integration'); ?></option>
                        <?php adfoin_quickbase_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'add'">
                <td><label><?php esc_attr_e('Table', 'advanced-form-integration'); ?></label></td>
                <td>
                    <select name="fieldData[appId]" v-model="fielddata.appId" @change="getFields">
                        <option value=""><?php _e('Select...', 'advanced-form-integration'); ?></option>
                        <option v-for="(name, id) in fielddata.apps" :value="id">{{ name }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': appsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>
            <editable-field v-for="field in fields" :key="field.value" :field="field" :trigger="trigger" :action="action" :fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action('wp_ajax_adfoin_get_quickbase_tables', 'adfoin_get_quickbase_tables');

function adfoin_get_quickbase_tables() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $credentials = adfoin_get_credentials_by_id('quickbase', $cred_id);

    $token = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';
    $realm = isset($credentials['realmHostname']) ? $credentials['realmHostname'] : '';

    $url = 'https://' . $realm . '/db/main?a=API_GrantedDBs';
    $body = '<?xml version="1.0"?><qdbapi><excludeparents>1</excludeparents><usertoken>' . esc_html($token) . '</usertoken></qdbapi>';

    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/xml'),
        'body'    => $body
    ));

    if (is_wp_error($response)) wp_send_json_error();

    $xml = simplexml_load_string(wp_remote_retrieve_body($response));
    $apps = [];
    foreach ($xml->databases->dbinfo as $app) {
        $apps[(string)$app->dbid] = (string)$app->dbname;
    }

    wp_send_json_success($apps);
}

add_action('wp_ajax_adfoin_get_quickbase_fields', 'adfoin_get_quickbase_fields');
function adfoin_get_quickbase_fields() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId']);
    $app_id = sanitize_text_field($_POST['appId']);

    $response = adfoin_quickbase_request('fields?tableId=' . $app_id, 'GET', [], [], $cred_id);

    if (is_wp_error($response)) wp_send_json_error();

    $json = json_decode(wp_remote_retrieve_body($response), true);
    $fields = [];
    $skip_field_types = ['timestamp', 'address', 'dblink', 'recordid'];
    foreach ($json as $field) {
        if (in_array($field['fieldType'], $skip_field_types)) continue;

        $field_type = $field['fieldType'];
        array_push($fields, array(
            'key'   => $field['id'],
            'value' => $field['label'],
            'description' => '',
        ));
    }

    wp_send_json_success($fields);
}

function adfoin_quickbase_request($endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('quickbase', $cred_id);
    $token       = isset($credentials['accessToken']) ? $credentials['accessToken'] : '';
    $realm       = isset($credentials['realmHostname']) ? $credentials['realmHostname'] : '';
    $url         = 'https://api.quickbase.com/v1/' . ltrim($endpoint, '/');

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'QB-Realm-Hostname' => $realm,
            'User-Agent'        => 'AdvancedFormIntegrationWP',
            'Authorization'     => 'QB-USER-TOKEN ' . $token,
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json'
        ),
    );

    if ('POST' == $method || 'PUT' == $method || 'PATCH' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('adfoin_quickbase_job_queue', 'adfoin_quickbase_job_queue', 10, 1);
function adfoin_quickbase_job_queue($data) {
    adfoin_quickbase_send_data($data['record'], $data['posted_data']);
}

function adfoin_quickbase_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);
    if (isset($record_data["action_data"]["cl"]) && adfoin_check_conditional_logic($record_data["action_data"]["cl"], $posted_data)) return;

    $field_data = $record_data['field_data'];
    $table_id   = isset($field_data['appId']) ? $field_data['appId'] : '';
    unset($field_data['appId'], $field_data['credId']);

    $data = array(
        'to' => $table_id,
        'data' => array(),
        'fieldsToReturn' => array()
    );

    $record_data = array();

    foreach ($field_data as $key => $value) {
        if ($value !== '') {
            $parsed = adfoin_get_parsed_values($value, $posted_data);
            if ($parsed !== '') {
                $record_data[$key] = array(
                    'value' => $parsed
                );
                $data['fieldsToReturn'][] = (int)$key;
            }
        }
    }

    $data['data'][] = $record_data;

    adfoin_quickbase_request('records', 'POST', $data, $record);
}