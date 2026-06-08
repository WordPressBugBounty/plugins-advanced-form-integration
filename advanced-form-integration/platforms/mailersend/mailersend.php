<?php

/**
 * MailerSend — Send Email action via POST /v1/email.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 *
 * @link https://developers.mailersend.com/api/v1/email
 */

add_filter( 'adfoin_action_providers', 'adfoin_mailersend_actions', 10, 1 );

function adfoin_mailersend_actions( $actions ) {

    $actions['mailersend'] = array(
        'title' => __( 'MailerSend', 'advanced-form-integration' ),
        'tasks' => array(
            'send_email' => __( 'Send Email', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailersend_settings_tab', 10, 1 );

function adfoin_mailersend_settings_tab( $providers ) {
    $providers['mailersend'] = __( 'MailerSend', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailersend_settings_view', 10, 1 );

function adfoin_mailersend_settings_view( $current_tab ) {
    if ( $current_tab !== 'mailersend' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiToken',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'mlsn.…', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s — link to MailerSend tokens page. */
            esc_html__( 'Sign in to MailerSend and open %s.', 'advanced-form-integration' ),
            '<a href="https://app.mailersend.com/api-tokens" target="_blank" rel="noopener noreferrer">' . esc_html__( 'API Tokens', 'advanced-form-integration' ) . '</a>'
        ),
        esc_html__( 'Create a token with at least the "Email Send" permission. Tokens start with mlsn.', 'advanced-form-integration' ),
        esc_html__( 'Paste the token in the form below. AFI sends every request to https://api.mailersend.com/v1/ with this token in the Authorization: Bearer header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'mailersend', __( 'MailerSend', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailersend_credentials', 'adfoin_get_mailersend_credentials', 10, 0 );

function adfoin_get_mailersend_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailersend' );
}

add_action( 'wp_ajax_adfoin_save_mailersend_credentials', 'adfoin_save_mailersend_credentials', 10, 0 );

function adfoin_save_mailersend_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailersend', array( 'apiToken' ) );
}

/**
 * Render <option> tags for the account picker. Shared with Pro template.
 */
function adfoin_mailersend_credentials_list() {
    $credentials = adfoin_read_credentials( 'mailersend' );

    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Surface the legacy single-option token as a "Default Account (Legacy)"
 * record so users who set up MailerSend before the multi-account UI
 * existed don't need to re-paste their API token.
 */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'mailersend', array(
            'apiToken' => 'adfoin_mailersend_api_token',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_mailersend_action_fields' );

function adfoin_mailersend_action_fields() {
    ?>
    <script type="text/template" id="mailersend-action-template">
        <table class="form-table" v-if="action.task == 'send_email'">
            <tr valign="top">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row"></td>
            </tr>

            <tr valign="top" class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'MailerSend Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailersend' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata">
            </editable-field>
            <?php adfoin_pro_feature_notice( 'send_email', 'MailerSend [PRO]', 'tags and custom fields' ); ?>

            <tr valign="top" class="alternate">
                <th scope="row"><?php esc_html_e( 'Need templates, variables, CC/BCC?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( wp_kses(
                        __( '<a href="%s">Upgrade to AFI Pro</a> to send via MailerSend templates with variables, CC/BCC, reply-to, scheduled sends, and tags.', 'advanced-form-integration' ),
                        array( 'a' => array( 'href' => array() ) )
                    ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

/**
 * Centralized request helper. Reads the API token from the chosen
 * credential record; falls back to the legacy single-option token when
 * no $cred_id is supplied (covers transient migration states).
 */
function adfoin_mailersend_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $api_token = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'mailersend', $cred_id );
        if ( is_array( $credentials ) && ! empty( $credentials['apiToken'] ) ) {
            $api_token = trim( (string) $credentials['apiToken'] );
        }
    }

    if ( ! $api_token ) {
        $api_token = (string) get_option( 'adfoin_mailersend_api_token', '' );
    }

    if ( ! $api_token ) {
        return new WP_Error( 'adfoin_mailersend_missing_token', __( 'MailerSend API token is missing.', 'advanced-form-integration' ) );
    }

    $endpoint = ltrim( $endpoint, '/' );
    $url      = 'https://api.mailersend.com/v1/' . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_mailersend_job_queue', 'adfoin_mailersend_job_queue', 10, 1 );

function adfoin_mailersend_job_queue( $data ) {
    adfoin_mailersend_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_mailersend_send_data( $record, $posted_data ) {
    if ( 'send_email' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( empty( $cred_id ) ) {
        adfoin_add_to_log( new WP_Error( 'mailersend_missing_cred', __( 'No MailerSend account selected.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $payload = adfoin_mailersend_build_email_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    adfoin_mailersend_request( 'email', 'POST', $payload, $record, $cred_id );
}

/**
 * Translate the mapped form fields into MailerSend's POST /v1/email
 * envelope. Public so the Pro add-on can reuse it as the baseline
 * before layering template_id / variables / cc / bcc / etc.
 *
 * Returns WP_Error when from / to / subject is missing — those are
 * required by MailerSend and would otherwise 422.
 */
function adfoin_mailersend_build_email_payload( $field_data, $posted_data ) {
    $get = function ( $key ) use ( $field_data, $posted_data ) {
        if ( empty( $field_data[ $key ] ) ) {
            return '';
        }
        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        return is_string( $value ) ? trim( $value ) : '';
    };

    $from_email = $get( 'from_email' );
    $from_name  = $get( 'from_name' );
    $to_email   = $get( 'to_email' );
    $to_name    = $get( 'to_name' );
    $subject    = $get( 'subject' );
    $text_body  = $get( 'text' );
    $html_body  = $get( 'html' );

    if ( ! $from_email || ! $to_email || ! $subject ) {
        return new WP_Error( 'mailersend_missing_required', __( 'MailerSend send_email requires from_email, to_email, and subject.', 'advanced-form-integration' ) );
    }

    // MailerSend body fields — at least one of `text` / `html` /
    // `template_id` is required. The build helper only enforces from /
    // to / subject; the caller (free or Pro) layers in template_id
    // when relevant.
    $payload = array(
        'from'    => array_filter( array(
            'email' => $from_email,
            'name'  => $from_name,
        ) ),
        'to'      => array(
            array_filter( array(
                'email' => $to_email,
                'name'  => $to_name,
            ) ),
        ),
        'subject' => $subject,
    );

    if ( $text_body ) { $payload['text'] = $text_body; }
    if ( $html_body ) { $payload['html'] = $html_body; }

    return $payload;
}
