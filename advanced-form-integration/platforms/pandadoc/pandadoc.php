<?php

/**
 * PandaDoc — Create + Send Document From Template.
 *
 * Auth: static API Key (Settings > Integrations > API). Multi-account via
 * ADFOIN_Account_Manager.
 *
 * Confirmed via developers.pandadoc.com: base
 * https://api.pandadoc.com/public/v1/, header
 * `Authorization: API-Key {key}`. POST /documents creates a document from
 * a template — this is ASYNC (starts in status "document.uploaded",
 * rendering finishes ~3-5s later at status "document.draft"); calling
 * Send before it reaches "document.draft" returns 404. We poll
 * GET /documents/{id} a bounded number of times (this runs inside the
 * plugin's own async job-queue dispatch, not the visitor's request, so a
 * short blocking poll here is fine) before calling POST /documents/{id}/send.
 *
 * @link https://developers.pandadoc.com/reference/create-document
 * @link https://developers.pandadoc.com/docs/reliable-document-workflow
 */

add_filter( 'adfoin_action_providers', 'adfoin_pandadoc_actions', 10, 1 );

function adfoin_pandadoc_actions( $actions ) {
    $actions['pandadoc'] = array(
        'title' => __( 'PandaDoc', 'advanced-form-integration' ),
        'tasks' => array( 'send_document' => __( 'Create & Send Document From Template', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_pandadoc_settings_tab', 10, 1 );

function adfoin_pandadoc_settings_tab( $providers ) {
    $providers['pandadoc'] = __( 'PandaDoc', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_pandadoc_settings_view', 10, 1 );

function adfoin_pandadoc_settings_view( $current_tab ) {
    if ( 'pandadoc' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'In PandaDoc, go to %s to generate an API key (Org Admin required).', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.pandadoc.com/a/#/api-dashboard/configuration">Settings &raquo; Integrations &raquo; API</a>' ),
        esc_html__( 'Paste it below. You\'ll need a Template ID (from the template\'s URL in the PandaDoc editor) when mapping fields on your form action.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'pandadoc', __( 'PandaDoc', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_pandadoc_credentials', 'adfoin_get_pandadoc_credentials', 10, 0 );

function adfoin_get_pandadoc_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'pandadoc' );
}

add_action( 'wp_ajax_adfoin_save_pandadoc_credentials', 'adfoin_save_pandadoc_credentials', 10, 0 );

function adfoin_save_pandadoc_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'pandadoc', array( 'apiKey' ) );
}

function adfoin_pandadoc_credentials_list() {
    foreach ( adfoin_read_credentials( 'pandadoc' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_pandadoc_action_fields' );

function adfoin_pandadoc_action_fields() {
    ?>
    <script type="text/template" id="pandadoc-action-template">
        <table class="form-table" v-if="action.task == 'send_document'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'PandaDoc Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=pandadoc' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'send_document', 'PandaDoc [PRO]', 'merge field tokens' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_pandadoc_fields', 'adfoin_get_pandadoc_fields' );

function adfoin_get_pandadoc_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'templateUuid', 'value' => __( 'Template ID', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'documentName', 'value' => __( 'Document Name', 'advanced-form-integration' ) ),
        array( 'key' => 'recipientEmail', 'value' => __( 'Recipient Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'recipientFirstName', 'value' => __( 'Recipient First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'recipientLastName',  'value' => __( 'Recipient Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'role', 'value' => __( 'Recipient Role', 'advanced-form-integration' ), 'description' => __( 'Must match a role name defined on the template', 'advanced-form-integration' ) ),
        array( 'key' => 'message', 'value' => __( 'Send Message', 'advanced-form-integration' ) ),
        array( 'key' => 'subject', 'value' => __( 'Email Subject', 'advanced-form-integration' ) ),
    );
    wp_send_json_success( $fields );
}

function adfoin_pandadoc_build_create_payload( $fields ) {
    $payload = array(
        'name'          => ! empty( $fields['documentName'] ) ? $fields['documentName'] : 'Document for ' . ( $fields['recipientEmail'] ?? '' ),
        'template_uuid' => $fields['templateUuid'],
        'recipients'    => array( array(
            'email'      => $fields['recipientEmail'],
            'first_name' => $fields['recipientFirstName'] ?? '',
            'last_name'  => $fields['recipientLastName'] ?? '',
            'role'       => ! empty( $fields['role'] ) ? $fields['role'] : 'Client',
        ) ),
    );
    return $payload;
}

add_action( 'adfoin_pandadoc_job_queue', 'adfoin_pandadoc_job_queue', 10, 1 );
function adfoin_pandadoc_job_queue( $data ) {
    adfoin_pandadoc_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_pandadoc_send_data( $record, $posted_data ) {
    if ( 'send_document' !== ( $record['task'] ?? '' ) ) {
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

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) continue;
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) $fields[ $key ] = $parsed;
    }

    if ( empty( $fields['templateUuid'] ) || empty( $fields['recipientEmail'] ) ) {
        return;
    }

    adfoin_pandadoc_create_and_send( adfoin_pandadoc_build_create_payload( $fields ), $fields, $cred_id, $record );
}

/**
 * Shared create->poll->send flow used by both tiers.
 */
function adfoin_pandadoc_create_and_send( $create_payload, $fields, $cred_id, $record ) {
    $create_response = adfoin_pandadoc_request( 'documents', 'POST', $create_payload, $record, $cred_id );

    if ( is_wp_error( $create_response ) ) {
        return;
    }
    $body = json_decode( wp_remote_retrieve_body( $create_response ), true );
    $doc_id = $body['id'] ?? '';
    if ( ! $doc_id ) {
        return;
    }

    $ready = false;
    for ( $i = 0; $i < 5; $i++ ) {
        sleep( 2 );
        $status_response = adfoin_pandadoc_request( 'documents/' . rawurlencode( $doc_id ), 'GET', array(), array(), $cred_id );
        if ( is_wp_error( $status_response ) ) continue;
        $status_body = json_decode( wp_remote_retrieve_body( $status_response ), true );
        if ( ( $status_body['status'] ?? '' ) === 'document.draft' ) {
            $ready = true;
            break;
        }
    }

    if ( ! $ready ) {
        return;
    }

    $send_payload = array( 'silent' => false );
    if ( ! empty( $fields['message'] ) ) $send_payload['message'] = $fields['message'];
    if ( ! empty( $fields['subject'] ) ) $send_payload['subject'] = $fields['subject'];

    adfoin_pandadoc_request( 'documents/' . rawurlencode( $doc_id ) . '/send', 'POST', $send_payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_pandadoc_request' ) ) :
function adfoin_pandadoc_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'pandadoc', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'pandadoc_missing_credentials', __( 'PandaDoc API Key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.pandadoc.com/public/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'API-Key ' . $credentials['apiKey'],
            'Content-Type'  => 'application/json',
        ),
    );
    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
