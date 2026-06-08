<?php

/**
 * Discord — Send a message to a channel via POST /channels/{channel.id}/messages.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bot {token}.
 *
 * @link https://docs.discord.com/developers/reference
 */

add_filter( 'adfoin_action_providers', 'adfoin_discord_actions', 10, 1 );

function adfoin_discord_actions( $actions ) {
    $actions['discord'] = array(
        'title' => __( 'Discord', 'advanced-form-integration' ),
        'tasks' => array(
            'send_message' => __( 'Send Message', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_discord_settings_tab', 10, 1 );

function adfoin_discord_settings_tab( $providers ) {
    $providers['discord'] = __( 'Discord', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_discord_settings_view', 10, 1 );

function adfoin_discord_settings_view( $current_tab ) {
    if ( 'discord' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'botToken',
            'label'         => __( 'Bot Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s: Discord developer portal link. */
            esc_html__( 'Open the %s and create a new application.', 'advanced-form-integration' ),
            '<a href="https://discord.com/developers/applications" target="_blank" rel="noopener noreferrer">Discord Developer Portal</a>'
        ),
        esc_html__( 'In the application sidebar, open Bot and reset the token. Copy it.', 'advanced-form-integration' ),
        esc_html__( 'Open OAuth2 → URL Generator. Select scope "bot" and bot permissions "View Channels" and "Send Messages".', 'advanced-form-integration' ),
        esc_html__( 'Paste the generated URL into your browser and invite the bot to the server you want to post to.', 'advanced-form-integration' ),
        esc_html__( 'Paste the token below. AFI authenticates with Authorization: Bot {token}.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'discord', __( 'Discord', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_discord_credentials', 'adfoin_get_discord_credentials', 10, 0 );

function adfoin_get_discord_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'discord' );
}

add_action( 'wp_ajax_adfoin_save_discord_credentials', 'adfoin_save_discord_credentials', 10, 0 );

function adfoin_save_discord_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'discord', array( 'botToken' ) );
}

function adfoin_discord_credentials_list() {
    foreach ( adfoin_read_credentials( 'discord' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_discord_action_fields' );

function adfoin_discord_action_fields() {
    ?>
    <script type="text/template" id="discord-action-template">
        <table class="form-table" v-if="action.task == 'send_message'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Discord Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=discord' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Server', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[serverId]" v-model="fielddata.serverId">
                        <option value=""><?php esc_html_e( 'Select Server...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.servers" :value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': serverLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Channel', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[channelId]" v-model="fielddata.channelId">
                        <option value=""><?php esc_html_e( 'Select Channel...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.channels" :value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': channelLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'send_message', 'Discord [PRO]', 'rich embed fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_discord_servers', 'adfoin_get_discord_servers', 10, 0 );

function adfoin_get_discord_servers() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'No Discord account selected.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_discord_request( 'users/@me/guilds', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! is_array( $body ) ) {
        wp_send_json_error();
    }

    $servers = array();
    foreach ( $body as $guild ) {
        if ( isset( $guild['id'], $guild['name'] ) ) {
            $servers[ (string) $guild['id'] ] = (string) $guild['name'];
        }
    }

    wp_send_json_success( $servers );
}

add_action( 'wp_ajax_adfoin_get_discord_channels', 'adfoin_get_discord_channels', 10, 0 );

function adfoin_get_discord_channels() {
    adfoin_verify_nonce();

    $cred_id   = isset( $_POST['credId'] )   ? sanitize_text_field( wp_unslash( $_POST['credId'] ) )   : '';
    $server_id = isset( $_POST['serverId'] ) ? sanitize_text_field( wp_unslash( $_POST['serverId'] ) ) : '';

    if ( ! $cred_id || ! $server_id ) {
        wp_send_json_error( array( 'message' => __( 'Discord account and server are required.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_discord_request( 'guilds/' . rawurlencode( $server_id ) . '/channels', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! is_array( $body ) ) {
        wp_send_json_error();
    }

    // Discord channel types — keep only those that accept messages:
    // 0 GUILD_TEXT, 5 GUILD_ANNOUNCEMENT, 11 PUBLIC_THREAD, 12 PRIVATE_THREAD, 15 GUILD_FORUM.
    $sendable = array( 0, 5, 11, 12, 15 );

    $channels = array();
    foreach ( $body as $channel ) {
        if ( ! isset( $channel['id'], $channel['name'] ) ) {
            continue;
        }
        $type = isset( $channel['type'] ) ? (int) $channel['type'] : 0;
        if ( ! in_array( $type, $sendable, true ) ) {
            continue;
        }
        $channels[ (string) $channel['id'] ] = '#' . (string) $channel['name'];
    }

    wp_send_json_success( $channels );
}

add_action( 'adfoin_discord_job_queue', 'adfoin_discord_job_queue', 10, 1 );

function adfoin_discord_job_queue( $data ) {
    adfoin_discord_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_discord_send_data( $record, $posted_data ) {
    if ( 'send_message' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] )    ? $field_data['credId']    : '';
    $channel_id = isset( $field_data['channelId'] ) ? trim( (string) $field_data['channelId'] ) : '';
    $message    = isset( $field_data['message'] )
        ? (string) adfoin_get_parsed_values( $field_data['message'], $posted_data )
        : '';

    if ( ! $cred_id || ! $channel_id || '' === trim( $message ) ) {
        return;
    }

    if ( function_exists( 'mb_substr' ) ) {
        $message = mb_substr( $message, 0, 2000 );
    } else {
        $message = substr( $message, 0, 2000 );
    }

    $payload = apply_filters(
        'adfoin_discord_message_payload',
        array( 'content' => $message ),
        $field_data,
        $posted_data
    );

    adfoin_discord_request( 'channels/' . rawurlencode( $channel_id ) . '/messages', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_discord_request' ) ) :
/**
 * Call the Discord REST API.
 *
 * @param string $endpoint Path under /api/v10/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_discord_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $bot_token = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'discord', $cred_id );
        if ( is_array( $credentials ) && isset( $credentials['botToken'] ) ) {
            $bot_token = trim( (string) $credentials['botToken'] );
        }
    }

    if ( ! $bot_token ) {
        return new WP_Error( 'discord_missing_token', __( 'Discord bot token is missing.', 'advanced-form-integration' ) );
    }

    $url    = 'https://discord.com/api/v10/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bot ' . $bot_token,
            'Accept'        => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }
    } else {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
