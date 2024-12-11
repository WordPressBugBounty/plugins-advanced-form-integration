<?php

add_filter('adfoin_action_providers', 'adfoin_discord_actions', 10, 1);

function adfoin_discord_actions($actions)
{
    $actions['discord'] = [
        'title' => __('Discord', 'advanced-form-integration'),
        'tasks' => [
            'send_message' => __('Send Message', 'advanced-form-integration'),
        ],
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_discord_settings_tab', 10, 1);

function adfoin_discord_settings_tab($providers)
{
    $providers['discord'] = __('Discord', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_discord_settings_view', 10, 1);

function adfoin_discord_settings_view($current_tab) {
    if ($current_tab != 'discord') {
        return;
    }

    $title = __('Discord', 'advanced-form-integration');
    $key = 'discord';
    $arguments = json_encode([
        'platform' => $key,
        'fields' => [
            [
                'key' => 'botToken',
                'label' => __('Bot Token', 'advanced-form-integration'),
                'hidden' => true
            ]
        ]
    ]);

    $instructions = __(
        '<ol>
            <li>Go to the <a href="https://discord.com/developers" target="_blank">Discord Developer Portal</a> and click the <strong>New Application</strong> button. Provide a name for your application and save it.</li>
            <li>From the left sidebar, select the <strong>OAuth2</strong> option.</li>
            <li>Go to the <strong>OAuth2 URL Generator</strong> section.</li>
            <li>Under <strong>Scopes</strong>, select <strong>bot</strong>.</li>
            <li>Under <strong>Bot Permissions</strong> select <strong>Administrator</strong>.</li>
            <li>Copy the <strong>generated URL</strong>, paste it into a new browser tab, and press Enter.</li>
            <li>Select the server you want to add the bot to, click <strong>Continue</strong>, and then click <strong>Authorize</strong> to grant permissions.</li>
            <li>Navigate to the <strong>Bot</strong> menu in your application. Click <strong>Reset Token</strong>, and copy it.</li>
        </ol>',
        'advanced-form-integration'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );

    
}

add_action( 'wp_ajax_adfoin_get_discord_credentials', 'adfoin_get_discord_credentials', 10, 0 );

function adfoin_get_discord_credentials() {
    if (!adfoin_verify_nonce()) return;

    $all_credentials = adfoin_read_credentials( 'discord' );

    wp_send_json_success( $all_credentials );
}

add_action( 'wp_ajax_adfoin_save_discord_credentials', 'adfoin_save_discord_credentials', 10, 0 );
/*
 * Get Discord credentials
 */
function adfoin_save_discord_credentials() {
    if (!adfoin_verify_nonce()) return;

    $platform = sanitize_text_field( $_POST['platform'] );

    if( 'discord' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_discord_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'discord' );

    foreach( $credentials as $option ) {
        $html .= '<option value="'. $option['id'] .'">' . $option['title'] . '</option>';
    }

    echo $html;
}

add_action('adfoin_action_fields', 'adfoin_discord_action_fields');

function adfoin_discord_action_fields()
{
    ?>
    <script type="text/template" id="discord-action-template">
        <table class="form-table">
        <tr valign="top" v-if="action.task == 'send_message'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'send_message'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Discord Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getServers">
                    <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php
                            adfoin_discord_credentials_list();
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'send_message'">
                <th scope="row">
                    <?php esc_attr_e('Server', 'advanced-form-integration'); ?>
                </th>
                <td>
                    <select name="fieldData[serverId]" v-model="fielddata.serverId" required="true" @change="getChannels">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.servers" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': serverLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'send_message'">
                <th scope="row">
                    <?php esc_attr_e('Channel', 'advanced-form-integration'); ?>
                </th>
                <td>
                    <select name="fieldData[channelId]" v-model="fielddata.channelId" required="true">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.channels" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': channelLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

/*
 * Discord API Request
 */
function adfoin_discord_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id('discord', $cred_id);
    $bot_token = isset($credentials['botToken']) ? $credentials['botToken'] : '';
    $base_url = 'https://discord.com/api/v10/';
    $url = $base_url . $endpoint;

    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bot ' . $bot_token
        ],
    ];

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_discord_servers', 'adfoin_get_discord_servers');

function adfoin_get_discord_servers() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId'] ?? '');
    $response = adfoin_discord_request('users/@me/guilds', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response));

    if ($body) {
        wp_send_json_success(wp_list_pluck($body, 'name', 'id'));
    } else {
        wp_send_json_error();
    }
}

add_action('wp_ajax_adfoin_get_discord_channels', 'adfoin_get_discord_channels');

function adfoin_get_discord_channels() {
    if (!adfoin_verify_nonce()) return;

    $cred_id = sanitize_text_field($_POST['credId'] ?? '');
    $server_id = sanitize_text_field($_POST['serverId'] ?? '');
    $response = adfoin_discord_request("guilds/$server_id/channels", 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response));

    if ($body) {
        wp_send_json_success(wp_list_pluck($body, 'name', 'id'));
    } else {
        wp_send_json_error();
    }
}

add_action('adfoin_job_queue', 'adfoin_discord_job_queue', 10, 1);

function adfoin_discord_job_queue($data) {
    adfoin_discord_send_data($data['record'], $data['posted_data']);
}

/*
 * Handles sending message to Discord API
 */
function adfoin_discord_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if (adfoin_check_conditional_logic($record_data['action_data']['cl'] ?? [], $posted_data)) return;

    $data = $record_data['field_data'];
    $cred_id = empty($data['credId']) ? '' : $data['credId'];
    $server_id = empty($data['serverId']) ? '' : $data['serverId'];
    $channel_id = empty($data['channelId']) ? '' : $data['channelId'];
    $message = empty($data['message']) ? '' : adfoin_get_parsed_values($data['message'], $posted_data);

    if (!$server_id || !$channel_id || !$message) {
        return;
    }

    $response = adfoin_discord_request("channels/$channel_id/messages", 'POST', ['content' => $message], $record, $cred_id);

    if (is_wp_error($response)) {
        return $response;
    }

    $responseBody = wp_remote_retrieve_body($response);
    return json_decode($responseBody, true);
}


