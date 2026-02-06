<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_zendesk_actions',
    10,
    1
);
function adfoin_zendesk_actions(  $actions  ) {
    $actions['zendesk'] = array(
        'title' => __( 'Zendesk Support', 'advanced-form-integration' ),
        'tasks' => array(
            'create_ticket' => __( 'Create Ticket', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_zendesk_settings_tab',
    10,
    1
);
function adfoin_zendesk_settings_tab(  $providers  ) {
    $providers['zendesk'] = __( 'Zendesk Support', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_zendesk_settings_view',
    10,
    1
);
function adfoin_zendesk_settings_view(  $current_tab  ) {
    if ( $current_tab !== 'zendesk' ) {
        return;
    }
    $title = __( 'Zendesk Support', 'advanced-form-integration' );
    $key = 'zendesk';
    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(array(
            'key'    => 'email',
            'label'  => __( 'Agent Email', 'advanced-form-integration' ),
            'hidden' => false,
        ), array(
            'key'    => 'subdomain',
            'label'  => __( 'Zendesk Subdomain', 'advanced-form-integration' ),
            'hidden' => false,
        ), array(
            'key'    => 'apiToken',
            'label'  => __( 'API Token', 'advanced-form-integration' ),
            'hidden' => true,
        )),
    ) );
    $instructions = sprintf(
        '<p>%s</p><ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'To connect your Zendesk account, complete the following:', 'advanced-form-integration' ),
        esc_html__( 'Log into Zendesk Support as an admin and open Admin Center → Apps and integrations → Zendesk API → Tokens.', 'advanced-form-integration' ),
        esc_html__( 'Enable Token Access (if not already enabled) and create a new API token. Copy the value.', 'advanced-form-integration' ),
        esc_html__( 'Copy your Support subdomain (the “example” part of https://example.zendesk.com) and the email address of the agent who owns the token.', 'advanced-form-integration' )
    );
    echo adfoin_platform_settings_template(
        $title,
        $key,
        $arguments,
        $instructions
    );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action(
    'wp_ajax_adfoin_get_zendesk_credentials',
    'adfoin_get_zendesk_credentials',
    10,
    0
);
function adfoin_get_zendesk_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $all_credentials = adfoin_read_credentials( 'zendesk' );
    wp_send_json_success( $all_credentials );
}

add_action(
    'wp_ajax_adfoin_save_zendesk_credentials',
    'adfoin_save_zendesk_credentials',
    10,
    0
);
function adfoin_save_zendesk_credentials() {
    if ( !adfoin_verify_nonce() ) {
        return;
    }
    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ?? '' ) );
    if ( 'zendesk' === $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] ?? array() );
        adfoin_save_credentials( $platform, $data );
    }
    wp_send_json_success();
}

function adfoin_zendesk_credentials_list() {
    $html = '';
    $credentials = adfoin_read_credentials( 'zendesk' );
    foreach ( $credentials as $option ) {
        $html .= '<option value="' . esc_attr( $option['id'] ) . '">' . esc_html( $option['title'] ) . '</option>';
    }
    echo $html;
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action(
    'adfoin_action_fields',
    'adfoin_zendesk_action_fields',
    10,
    1
);
function adfoin_zendesk_action_fields() {
    ?>
    <script type="text/template" id="zendesk-action-template">
        <table class="form-table">
            <tr v-if="action.task == 'create_ticket'">
                <th scope="row"><?php 
    esc_html_e( 'Ticket Fields', 'advanced-form-integration' );
    ?></th>
                <td>
                    <div class="spinner" :class="{'is-active': false}" style="display:none;"></div>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'create_ticket'">
                <td scope="row-title">
                    <label for="zendesk-credential"><?php 
    esc_html_e( 'Zendesk Account', 'advanced-form-integration' );
    ?></label>
                </td>
                <td>
                    <select id="zendesk-credential" name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php 
    esc_html_e( 'Select Account...', 'advanced-form-integration' );
    ?></option>
                        <?php 
    adfoin_zendesk_credentials_list();
    ?>
                    </select>
                </td>
            </tr>
            <editable-field
                v-for="field in fields"
                :key="field.value"
                :field="field"
                :trigger="trigger"
                :action="action"
                :fielddata="fielddata">
            </editable-field>

            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                        <tr valign="top" v-if="action.task == 'subscribe'">
                            <th scope="row">
                                <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                            </th>
                            <td scope="row">
                                <span><?php 
        printf( __( 'To unlock additional fields and tags consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                            </td>
                        </tr>
                        <?php 
    }
    ?>
        </table>
    </script>
    <?php 
}

add_action(
    'adfoin_job_queue',
    'adfoin_zendesk_job_queue',
    10,
    1
);
function adfoin_zendesk_job_queue(  $data  ) {
    if ( ($data['action_provider'] ?? '') !== 'zendesk' || ($data['task'] ?? '') !== 'create_ticket' ) {
        return;
    }
    adfoin_zendesk_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_zendesk_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }
    $field_data = $record_data['field_data'] ?? array();
    $cred_id = $field_data['credId'] ?? '';
    if ( empty( $cred_id ) ) {
        return;
    }
    $credentials = adfoin_get_credentials_by_id( 'zendesk', $cred_id );
    $subdomain = $credentials['subdomain'] ?? '';
    $email = $credentials['email'] ?? '';
    $api_token = $credentials['apiToken'] ?? '';
    if ( empty( $subdomain ) || empty( $email ) || empty( $api_token ) ) {
        return;
    }
    $ticket = array();
    $subject = adfoin_get_parsed_values( $field_data['ticket_subject'] ?? '', $posted_data );
    if ( !$subject ) {
        $subject = __( 'New Ticket', 'advanced-form-integration' );
    }
    $ticket['subject'] = $subject;
    $comment = adfoin_get_parsed_values( $field_data['ticket_comment'] ?? '', $posted_data );
    $comment_value = ( $comment !== '' ? $comment : $subject );
    $ticket['comment'] = array(
        'body'   => $comment_value,
        'public' => true,
    );
    $requester_email = adfoin_get_parsed_values( $field_data['requester_email'] ?? '', $posted_data );
    $requester_name = adfoin_get_parsed_values( $field_data['requester_name'] ?? '', $posted_data );
    if ( $requester_email ) {
        $ticket['requester'] = array(
            'email' => $requester_email,
        );
        if ( $requester_name ) {
            $ticket['requester']['name'] = $requester_name;
        }
    }
    $priority = adfoin_get_parsed_values( $field_data['ticket_priority'] ?? '', $posted_data );
    if ( $priority ) {
        $ticket['priority'] = strtolower( $priority );
    }
    $status = adfoin_get_parsed_values( $field_data['ticket_status'] ?? '', $posted_data );
    if ( $status ) {
        $ticket['status'] = strtolower( $status );
    }
    if ( empty( $ticket ) ) {
        return;
    }
    adfoin_zendesk_request(
        'tickets.json',
        'POST',
        array(
            'ticket' => $ticket,
        ),
        $record,
        $cred_id
    );
}

function adfoin_zendesk_request(
    $endpoint,
    $method = 'POST',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'zendesk', $cred_id );
    $subdomain = $credentials['subdomain'] ?? '';
    $email = $credentials['email'] ?? '';
    $api_token = $credentials['apiToken'] ?? '';
    if ( empty( $subdomain ) || empty( $email ) || empty( $api_token ) ) {
        return new WP_Error('zendesk_credentials_missing', __( 'Zendesk credentials are incomplete.', 'advanced-form-integration' ));
    }
    $base_url = sprintf( 'https://%s.zendesk.com/api/v2/', $subdomain );
    $url = $base_url . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $email . '/token:' . $api_token ),
        ),
    );
    if ( in_array( strtoupper( $method ), array('POST', 'PUT', 'PATCH'), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}
