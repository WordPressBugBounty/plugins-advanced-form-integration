<?php

/**
 * Microsoft Teams — Post Message to a channel via Incoming Webhook.
 *
 * No OAuth, no app registration. The webhook URL itself is the credential.
 * Supports both:
 *   - Microsoft 365 Workflow webhooks (the current/recommended pattern):
 *     https://prod-XX.<region>.logic.azure.com:443/workflows/{guid}/triggers/manual/paths/invoke?...
 *   - Legacy Office 365 Connector webhooks (being deprecated, but still in use):
 *     https://<tenant>.webhook.office.com/webhookb2/...
 *
 * Both endpoints accept the same Adaptive Card "message" envelope, which is
 * what we POST below.
 *
 * @link https://learn.microsoft.com/en-us/power-automate/teams/teams-app-create
 * @link https://adaptivecards.io/
 */

add_filter( 'adfoin_action_providers', 'adfoin_msteams_actions', 10, 1 );

function adfoin_msteams_actions( $actions ) {
    $actions['msteams'] = array(
        'title' => __( 'Microsoft Teams', 'advanced-form-integration' ),
        'tasks' => array(
            'send_message' => __( 'Post Message to Channel', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_msteams_settings_tab', 10, 1 );

function adfoin_msteams_settings_tab( $providers ) {
    $providers['msteams'] = __( 'Microsoft Teams', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_msteams_settings_view', 10, 1 );

function adfoin_msteams_settings_view( $current_tab ) {
    if ( 'msteams' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'webhookUrl',
            'label'         => __( 'Incoming Webhook URL', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'https://prod-XX.westeurope.logic.azure.com:443/workflows/.../triggers/manual/paths/invoke?...', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Open Microsoft Teams and navigate to the channel where you want to receive form submissions.', 'advanced-form-integration' ),
        esc_html__( 'Click the three-dot (...) menu next to the channel name and choose "Workflows".', 'advanced-form-integration' ),
        esc_html__( 'Select the template "Post to a channel when a webhook request is received".', 'advanced-form-integration' ),
        esc_html__( 'Sign in if prompted, confirm the Team and Channel, then click "Add workflow".', 'advanced-form-integration' ),
        esc_html__( 'Copy the generated webhook URL — Teams only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. Legacy Office 365 Connector webhook URLs (webhook.office.com) are also supported.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'msteams', __( 'Microsoft Teams', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_msteams_credentials', 'adfoin_get_msteams_credentials', 10, 0 );

function adfoin_get_msteams_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'msteams' );
}

add_action( 'wp_ajax_adfoin_save_msteams_credentials', 'adfoin_save_msteams_credentials', 10, 0 );

function adfoin_save_msteams_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'msteams', array( 'webhookUrl' ) );
}

function adfoin_msteams_credentials_list() {
    foreach ( adfoin_read_credentials( 'msteams' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_msteams_action_fields' );

function adfoin_msteams_action_fields() {
    ?>
    <script type="text/template" id="msteams-action-template">
        <table class="form-table" v-if="action.task == 'send_message'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Microsoft Teams Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=msteams' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <p class="description"><?php esc_html_e( 'Each "account" is one Teams channel webhook. Add additional accounts to post to different channels.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_msteams_fields', 'adfoin_get_msteams_fields' );

function adfoin_get_msteams_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array(
            'key'      => 'title',
            'value'    => __( 'Title (card heading, e.g. "New Lead from Contact Form")', 'advanced-form-integration' ),
            'required' => true,
        ),
        array(
            'key'      => 'message',
            'value'    => __( 'Message (card body — supports markdown)', 'advanced-form-integration' ),
            'required' => true,
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_msteams_job_queue', 'adfoin_msteams_job_queue', 10, 1 );

function adfoin_msteams_job_queue( $data ) {
    adfoin_msteams_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_msteams_send_data( $record, $posted_data ) {
    if ( 'send_message' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'msteams', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['webhookUrl'] ) ) {
        return;
    }

    $webhook_url = $credentials['webhookUrl'];

    $title   = isset( $field_data['title'] )   ? adfoin_get_parsed_values( $field_data['title'],   $posted_data ) : '';
    $message = isset( $field_data['message'] ) ? adfoin_get_parsed_values( $field_data['message'], $posted_data ) : '';

    // Both title and message are required.
    if ( '' === $title || '' === $message ) {
        return;
    }

    // Adaptive Card payload. This shape works with both Workflow webhooks
    // (the current pattern) and the legacy Office 365 Connector webhooks.
    $payload = array(
        'type'        => 'message',
        'attachments' => array(
            array(
                'contentType' => 'application/vnd.microsoft.card.adaptive',
                'contentUrl'  => null,
                'content'     => array(
                    '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                    'type'    => 'AdaptiveCard',
                    'version' => '1.4',
                    'body'    => array(
                        array(
                            'type'   => 'TextBlock',
                            'size'   => 'Medium',
                            'weight' => 'Bolder',
                            'text'   => $title,
                        ),
                        array(
                            'type' => 'TextBlock',
                            'text' => $message,
                            'wrap' => true,
                        ),
                    ),
                ),
            ),
        ),
    );

    adfoin_msteams_request( $webhook_url, $payload, $record );
}

if ( ! function_exists( 'adfoin_msteams_request' ) ) :
function adfoin_msteams_request( $webhook_url, $payload, $record = array() ) {
    if ( ! adfoin_is_valid_http_url( $webhook_url ) ) {
        if ( $record ) {
            adfoin_add_to_log(
                new WP_Error( 'adfoin_msteams_invalid_url', __( 'Microsoft Teams webhook URL must be a valid http(s) URL.', 'advanced-form-integration' ) ),
                $webhook_url,
                array(),
                $record
            );
        }
        return new WP_Error( 'adfoin_msteams_invalid_url', __( 'Microsoft Teams webhook URL must be a valid http(s) URL.', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
        'body'    => wp_json_encode( $payload ),
    );

    $response = wp_remote_post( esc_url_raw( $webhook_url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $webhook_url, $args, $record );
    }

    return $response;
}
endif;
