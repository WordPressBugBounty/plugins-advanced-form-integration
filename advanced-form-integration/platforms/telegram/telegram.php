<?php

/**
 * Telegram — Send Message via the Bot API (https://core.telegram.org/bots/api).
 *
 * No saved-credentials system here: the bot token is entered directly on
 * the action mapping (fieldData[bot_api_key]), matching the existing
 * telegram-component.js, which already posts bot_api_key straight to the
 * server rather than a stored credId. Telegram has no "list my chats"
 * endpoint, so — like every other Telegram Zapier-style integration — the
 * chat picker is populated from getUpdates: the user must message the bot
 * at least once before its chat shows up in the dropdown.
 */

add_filter( 'adfoin_action_providers', 'adfoin_telegram_actions', 10, 1 );

function adfoin_telegram_actions( $actions ) {
    $actions['telegram'] = array(
        'title' => __( 'Telegram', 'advanced-form-integration' ),
        'tasks' => array(
            'send_message' => __( 'Send Message', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_action( 'adfoin_action_fields', 'adfoin_telegram_action_fields' );

function adfoin_telegram_action_fields() {
    ?>
    <script type="text/template" id="telegram-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'send_message'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Bot API Token', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="text" class="regular-text" name="fieldData[bot_api_key]" v-model="fielddata.bot_api_key" @change="fetchChats" required>
                    <p class="description"><?php esc_attr_e( 'Get this from @BotFather on Telegram.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'send_message'">
                <td scope="row">
                    <?php esc_attr_e( 'Chat', 'advanced-form-integration' ); ?>
                </td>
                <td>
                    <select name="fieldData[chat_id]" v-model="fielddata.chat_id" required>
                        <option value=""> <?php _e( 'Select Chat...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="chat in chatList" :key="chat.id" :value="chat.id"> {{ chat.title }} </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': chatLoading}"></div>
                    <p class="description"><?php esc_attr_e( 'Not listed? Send any message to the bot, then re-enter the token above to refresh.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_telegram_updates', 'adfoin_get_telegram_updates', 10, 0 );

function adfoin_get_telegram_updates() {
    adfoin_verify_nonce();

    $bot_api_key = sanitize_text_field( wp_unslash( $_POST['bot_api_key'] ) );

    if ( empty( $bot_api_key ) ) {
        wp_send_json_error( __( 'Enter the Bot API Token first.', 'advanced-form-integration' ) );
    }

    $response = wp_remote_get( 'https://api.telegram.org/bot' . $bot_api_key . '/getUpdates', array( 'timeout' => 30 ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['ok'] ) ) {
        wp_send_json_error( isset( $body['description'] ) ? $body['description'] : __( 'Unable to fetch updates. Check the Bot API Token.', 'advanced-form-integration' ) );
    }

    $chats = array();

    foreach ( (array) $body['result'] as $update ) {
        $message = isset( $update['message'] ) ? $update['message'] : ( isset( $update['channel_post'] ) ? $update['channel_post'] : null );

        if ( empty( $message['chat']['id'] ) ) {
            continue;
        }

        $chat  = $message['chat'];
        $label = ! empty( $chat['title'] )
            ? $chat['title']
            : trim( ( isset( $chat['first_name'] ) ? $chat['first_name'] : '' ) . ' ' . ( isset( $chat['last_name'] ) ? $chat['last_name'] : '' ) );

        $chats[ $chat['id'] ] = array(
            'id'    => $chat['id'],
            'title' => $label ? $label : $chat['id'],
        );
    }

    wp_send_json_success( array_values( $chats ) );
}

add_action( 'adfoin_telegram_job_queue', 'adfoin_telegram_job_queue', 10, 1 );

function adfoin_telegram_job_queue( $data ) {
    adfoin_telegram_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_telegram_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'send_message' !== $task ) {
        return;
    }

    $bot_api_key = isset( $data['bot_api_key'] ) ? $data['bot_api_key'] : '';
    $chat_id     = isset( $data['chat_id'] ) ? $data['chat_id'] : '';
    $text        = isset( $data['text'] ) ? adfoin_get_parsed_values( $data['text'], $posted_data ) : '';

    if ( empty( $bot_api_key ) || empty( $chat_id ) || '' === $text || null === $text ) {
        return;
    }

    $url  = 'https://api.telegram.org/bot' . $bot_api_key . '/sendMessage';
    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
        'body'    => array(
            'chat_id' => $chat_id,
            'text'    => $text,
        ),
    );

    $response = wp_remote_request( $url, $args );

    adfoin_add_to_log( $response, $url, $args, $record );
}
