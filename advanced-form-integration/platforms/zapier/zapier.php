<?php

add_filter( 'adfoin_action_providers', 'adfoin_zapier_actions', 10, 1 );

function adfoin_zapier_actions( $actions ) {

    $actions['zapier'] = array(
        'title' => __( 'Zapier', 'advanced-form-integration' ),
        'tasks' => array(
            'send_to_webhook' => __( 'Send Data to Webhook', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_zapier_settings_tab', 10, 1 );

function adfoin_zapier_settings_tab( $providers ) {
    $providers['zapier'] = __( 'Zapier', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_zapier_settings_view', 10, 1 );

function adfoin_zapier_settings_view( $current_tab ) {
    if( $current_tab != 'zapier' ) {
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

add_action( 'adfoin_add_js_fields', 'adfoin_zapier_js_fields', 10, 1 );

function adfoin_zapier_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_zapier_action_fields' );

function adfoin_zapier_action_fields() {
    ?>
    <script type="text/template" id="zapier-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'send_to_webhook'">
                <th scope="row">
                    <?php esc_attr_e( 'Zapier Webhook Fields', 'advanced-form-integration' ); ?>
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

add_action( 'adfoin_zapier_job_queue', 'adfoin_zapier_job_queue', 10, 1 );

function adfoin_zapier_job_queue( $data ) {
    adfoin_zapier_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Zapier API
 */
function adfoin_zapier_send_data( $record, $posted_data ) {

    $data    = json_decode( $record["data"], true );
    $data    = $data["field_data"];
    $task    = $record["task"];

    if( $task == "send_to_webhook" ) {
        $webhook_url = $data["webhookUrl"];

        if( !$webhook_url ) {
            return;
        }

        if ( ! adfoin_is_valid_http_url( $webhook_url ) ) {
            adfoin_add_to_log(
                new WP_Error( 'adfoin_zapier_invalid_url', __( 'Zap webhook URL must be a valid http(s) URL.', 'advanced-form-integration' ) ),
                $webhook_url,
                array(),
                $record
            );
            return;
        }

        $args = array(
            'timeout' => 30,

            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $posted_data )
        );

        $return = wp_remote_post( $webhook_url, $args );

        adfoin_add_to_log( $return, $webhook_url, $args, $record );
    }
}