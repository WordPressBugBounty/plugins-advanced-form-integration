<?php

add_filter( 'adfoin_action_providers', 'adfoin_webhook_actions', 10, 1 );

function adfoin_webhook_actions( $actions ) {

    $actions['webhook'] = [
        'title' => __( 'Webhook', 'advanced-form-integration' ),
        'tasks' => [
            'send_to_webhook' => __( 'Send Data to Webhook', 'advanced-form-integration' )
        ]
    ];

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_webhook_settings_tab', 10, 1 );

function adfoin_webhook_settings_tab( $providers ) {
    $providers['webhook'] = __( 'Webhook', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_webhook_settings_view', 10, 1 );

function adfoin_webhook_settings_view( $current_tab ) {
    if( $current_tab != 'webhook' ) {
        return;
    }
    ?>
    <br>
    <br>
    <p class="description" id="code-description"><?php printf( '%s <a href="%s">%s</a>',
            __( 'Nothing need to be done here. Please create ', 'advanced-form-integration'),
            admin_url( 'admin.php?page=advanced-form-integration-new'),
            __( 'New Integration', 'advanced-form-integration')); ?></p>

    <?php
}

add_action( 'adfoin_add_js_fields', 'adfoin_webhook_js_fields', 10, 1 );

function adfoin_webhook_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_webhook_action_fields' );

function adfoin_webhook_action_fields() {
    ?>
    <script type="text/template" id="webhook-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'send_to_webhook'">
                <th scope="row">
                    <?php esc_attr_e( 'Webhook Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'send_to_webhook'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Webhook URL', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <input type="text" class="regular-text" v-model="fielddata.webhookUrl" name="fieldData[webhookUrl]" placeholder="<?php _e( 'Enter URL here', 'advanced-form-integration'); ?>" required="required">
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_webhook_job_queue', 'adfoin_webhook_job_queue', 10, 1 );

function adfoin_webhook_job_queue( $data ) {
    adfoin_webhook_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Webhook API
 */
function adfoin_webhook_send_data( $record, $posted_data ) {

    $data    = json_decode( $record["data"], true );
    $data    = $data["field_data"];
    $task    = $record["task"];

    if( $task == "send_to_webhook" ) {
        $webhook_url = adfoin_get_parsed_values( $data["webhookUrl"], $posted_data );

        if( !$webhook_url ) {
            return;
        }

        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $posted_data )
        );

        $return = wp_remote_post( $webhook_url, $args );

        adfoin_add_to_log( $return, $webhook_url, $args, $record );
    }

    return;
}