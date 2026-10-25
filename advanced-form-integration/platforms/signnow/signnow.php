<?php

/**
 * signNow — Send Document From Template.
 *
 * Auth: static API key from the signNow API dashboard (Apps and Keys > your
 * app > API Keys), sent as `Authorization: Bearer {key}`. Keys don't expire.
 * Multi-account via ADFOIN_Account_Manager.
 *
 * Flow per submission (three calls):
 *   POST /template/{template_id}/copy           -> new document id
 *   PUT  /v2/documents/{document_id}/prefill-texts  (Pro; text fields only)
 *   POST /document/{document_id}/invite         -> role-based field invite
 * The invite's `from` must be the account's login email, read from GET /user.
 *
 * @link https://docs.signnow.com/docs/authentication
 * @link https://docs.signnow.com/docs/signnow/template/operations/create-a-template-copy
 * @link https://docs.signnow.com/docs/document/operations/put-v2-documents-document_id-prefill-texts
 * @link https://docs.signnow.com/docs/document-field-invite/operations/post-document-doc_id-invite
 */

require_once dirname( __DIR__, 2 ) . '/includes/esign-template-picker.php';

add_filter( 'adfoin_action_providers', 'adfoin_signnow_actions', 10, 1 );

function adfoin_signnow_actions( $actions ) {
    $actions['signnow'] = array(
        'title' => __( 'signNow', 'advanced-form-integration' ),
        'tasks' => array( 'send_invite' => __( 'Send Document From Template', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_signnow_settings_tab', 10, 1 );

function adfoin_signnow_settings_tab( $providers ) {
    $providers['signnow'] = __( 'signNow', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_signnow_settings_view', 10, 1 );

function adfoin_signnow_settings_view( $current_tab ) {
    if ( 'signnow' !== $current_tab ) {
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

    $instructions  = '<ol class="afi-instructions-list">';
    $instructions .= '<li>' . sprintf( __( 'Open the %s and go to Apps and Keys. Create an application if you don\'t have one.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.signnow.com/webapp/api-dashboard/keys">signNow API dashboard</a>' ) . '</li>';
    $instructions .= '<li>' . __( 'Open the application, go to the API Keys tab and click Create New Key. Copy the key.', 'advanced-form-integration' ) . '</li>';
    $instructions .= '<li>' . __( 'Click Add Account here and paste the key. API keys don\'t expire; deleting the key in signNow disconnects AFI.', 'advanced-form-integration' ) . '</li>';
    $instructions .= '<li>' . __( 'signNow sells API access separately from regular plans. Development applications are for testing; use Go Live in the API dashboard before sending real documents.', 'advanced-form-integration' ) . '</li>';
    $instructions .= '</ol>';

    ADFOIN_Account_Manager::render_settings_view( 'signnow', __( 'signNow', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_signnow_credentials', 'adfoin_get_signnow_credentials', 10, 0 );

function adfoin_get_signnow_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'signnow' );
}

add_action( 'wp_ajax_adfoin_save_signnow_credentials', 'adfoin_save_signnow_credentials', 10, 0 );

function adfoin_save_signnow_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'signnow', array( 'apiKey' ) );
}

add_action( 'adfoin_action_fields', 'adfoin_signnow_action_fields' );

function adfoin_signnow_action_fields() {
    ?>
    <script type="text/template" id="signnow-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'send_invite'">
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'send_invite'">
                <td scope="row-title"><label><?php esc_html_e( 'signNow Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=signnow' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <?php adfoin_esign_template_picker_rows( 'send_invite' ); ?>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'send_invite', 'signNow [PRO]', 'prefilled text fields' ); ?>
        </table>
    </script>
    <?php
    adfoin_esign_template_picker_script();
}

/**
 * Mapping fields shared by the free and Pro actions. Template and role come
 * from the picker rows (templateId / roleName).
 */
function adfoin_signnow_base_fields() {
    return array(
        array( 'key' => 'documentName',   'value' => __( 'Document Name', 'advanced-form-integration' ), 'description' => __( 'Defaults to the template name.', 'advanced-form-integration' ) ),
        array( 'key' => 'recipientEmail', 'value' => __( 'Recipient Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'subject',        'value' => __( 'Email Subject', 'advanced-form-integration' ) ),
        array( 'key' => 'message',        'value' => __( 'Email Message', 'advanced-form-integration' ) ),
    );
}

add_action( 'wp_ajax_adfoin_get_signnow_fields', 'adfoin_get_signnow_fields' );

function adfoin_get_signnow_fields() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_signnow_base_fields() );
}

function adfoin_signnow_is_template_id( $value ) {
    return is_string( $value ) && (bool) preg_match( '/^[0-9a-f]{40}$/i', trim( $value ) );
}

function adfoin_signnow_cred_from_request() {
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    return ( $cred_id && adfoin_get_credentials_by_id( 'signnow', $cred_id ) ) ? $cred_id : '';
}

function adfoin_signnow_error_message( $response, $fallback ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['errors'][0]['message'] ) ) {
        return $body['errors'][0]['message'];
    }

    if ( isset( $body['error'] ) && is_string( $body['error'] ) ) {
        return $body['error'];
    }

    return $fallback;
}

add_action( 'wp_ajax_adfoin_get_signnow_templates', 'adfoin_get_signnow_templates' );

/**
 * Templates live in the account's "Templates" folder and its subfolders.
 */
function adfoin_get_signnow_templates() {
    adfoin_verify_nonce();

    $cred_id = adfoin_signnow_cred_from_request();

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'Select a signNow account.', 'advanced-form-integration' ) ) );
    }

    $templates = adfoin_signnow_list_templates( $cred_id );

    if ( is_wp_error( $templates ) ) {
        wp_send_json_error( array( 'message' => $templates->get_error_message() ) );
    }

    wp_send_json_success( $templates );
}

/**
 * @return array|WP_Error List of { id, name }.
 */
function adfoin_signnow_list_templates( $cred_id ) {
    $response = adfoin_signnow_request( 'user/folder', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return new WP_Error( 'signnow_folders', adfoin_signnow_error_message( $response, __( 'Could not load templates.', 'advanced-form-integration' ) ) );
    }

    $root    = json_decode( wp_remote_retrieve_body( $response ), true );
    $queue   = array();
    $found   = array();

    // "Templates" and "Team Templates", plus any other folder that reports
    // templates (a template can be moved). Trash is skipped.
    foreach ( isset( $root['folders'] ) ? (array) $root['folders'] : array() as $folder ) {
        $name = strtolower( $folder['name'] ?? '' );

        if ( empty( $folder['id'] ) || 'trash bin' === $name ) {
            continue;
        }

        if ( false !== strpos( $name, 'template' ) || (int) ( $folder['template_count'] ?? 0 ) > 0 ) {
            $queue[] = $folder['id'];
        }
    }

    // Walk the Templates folder and its subfolders (bounded).
    for ( $i = 0; $i < count( $queue ) && $i < 50; $i++ ) {
        $response = adfoin_signnow_request( 'user/folder/' . rawurlencode( $queue[ $i ] ) . '?limit=100', 'GET', array(), array(), $cred_id );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            continue;
        }

        $folder = json_decode( wp_remote_retrieve_body( $response ), true );

        foreach ( isset( $folder['documents'] ) ? (array) $folder['documents'] : array() as $document ) {
            if ( ! empty( $document['id'] ) && ! empty( $document['template'] ) ) {
                $found[ $document['id'] ] = array( 'id' => $document['id'], 'name' => $document['document_name'] ?? $document['id'] );
            }
        }

        foreach ( isset( $folder['folders'] ) ? (array) $folder['folders'] : array() as $sub ) {
            if ( ! empty( $sub['id'] ) ) {
                $queue[] = $sub['id'];
            }
        }
    }

    $templates = array_values( $found );

    usort( $templates, function ( $a, $b ) {
        return strcasecmp( $a['name'], $b['name'] );
    } );

    return $templates;
}

/**
 * Roles on a template, each with the names of its text fields (the fields
 * Pro can prefill). Same shape as the DocuSign picker expects.
 *
 * @return array|WP_Error List of { roleName, type, textTabs[] }.
 */
function adfoin_signnow_get_template_roles( $cred_id, $template_id ) {
    $response = adfoin_signnow_request( 'document/' . rawurlencode( $template_id ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return new WP_Error( 'signnow_template', adfoin_signnow_error_message( $response, __( 'Could not read the template.', 'advanced-form-integration' ) ) );
    }

    $document = json_decode( wp_remote_retrieve_body( $response ), true );
    $roles    = array();

    foreach ( isset( $document['roles'] ) ? (array) $document['roles'] : array() as $role ) {
        if ( ! empty( $role['name'] ) ) {
            $roles[ $role['name'] ] = array(
                'roleName' => $role['name'],
                'type'     => 'signers',
                'order'    => isset( $role['signing_order'] ) ? (int) $role['signing_order'] : 1,
                'textTabs' => array(),
            );
        }
    }

    foreach ( isset( $document['fields'] ) ? (array) $document['fields'] : array() as $field ) {
        $name = $field['json_attributes']['name'] ?? ( $field['name'] ?? '' );
        $role = $field['role'] ?? '';

        if ( 'text' === ( $field['type'] ?? '' ) && '' !== $name && isset( $roles[ $role ] ) ) {
            $roles[ $role ]['textTabs'][] = $name;
        }
    }

    foreach ( $roles as &$role ) {
        $role['textTabs'] = array_values( array_unique( $role['textTabs'] ) );
    }
    unset( $role );

    return array_values( $roles );
}

add_action( 'wp_ajax_adfoin_get_signnow_template_roles', 'adfoin_get_signnow_template_roles_ajax' );

function adfoin_get_signnow_template_roles_ajax() {
    adfoin_verify_nonce();

    $template_id = isset( $_POST['templateId'] ) ? sanitize_text_field( wp_unslash( $_POST['templateId'] ) ) : '';

    if ( ! adfoin_signnow_is_template_id( $template_id ) ) {
        wp_send_json_success( array() );
    }

    $cred_id = adfoin_signnow_cred_from_request();

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'Select a signNow account.', 'advanced-form-integration' ) ) );
    }

    $roles = adfoin_signnow_get_template_roles( $cred_id, $template_id );

    if ( is_wp_error( $roles ) ) {
        wp_send_json_error( array( 'message' => $roles->get_error_message() ) );
    }

    wp_send_json_success( $roles );
}

add_action( 'adfoin_signnow_job_queue', 'adfoin_signnow_job_queue', 10, 1 );

function adfoin_signnow_job_queue( $data ) {
    adfoin_signnow_send_data( $data['record'], $data['posted_data'] );
}

/**
 * Parse the mapped values of a send_invite record.
 *
 * @return array|null { cred_id, fields } or null when the record should not run.
 */
function adfoin_signnow_parse_record( $record, $posted_data ) {
    if ( 'send_invite' !== ( $record['task'] ?? '' ) ) {
        return null;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return null;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';
    $fields     = array();

    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = trim( $parsed );
        }
    }

    if ( ! $cred_id || empty( $fields['templateId'] ) || empty( $fields['recipientEmail'] ) ) {
        return null;
    }

    return array( 'cred_id' => $cred_id, 'fields' => $fields );
}

function adfoin_signnow_send_data( $record, $posted_data ) {
    $parsed = adfoin_signnow_parse_record( $record, $posted_data );

    if ( $parsed ) {
        adfoin_signnow_send_from_template( $parsed['fields'], array(), $parsed['cred_id'], $record );
    }
}

/**
 * Copy the template, optionally prefill text fields, then send the invite.
 * Shared by the free and Pro actions.
 *
 * @param array $prefill field_name => text (Pro).
 * @return string Document id, or '' on failure.
 */
function adfoin_signnow_send_from_template( $fields, $prefill, $cred_id, $record ) {
    $copy = array();

    if ( ! empty( $fields['documentName'] ) ) {
        $copy['document_name'] = $fields['documentName'];
    }

    $response    = adfoin_signnow_request( 'template/' . rawurlencode( $fields['templateId'] ) . '/copy', 'POST', (object) $copy, $record, $cred_id );
    $body        = is_wp_error( $response ) ? array() : json_decode( wp_remote_retrieve_body( $response ), true );
    $document_id = $body['id'] ?? '';

    if ( ! $document_id ) {
        return '';
    }

    if ( $prefill ) {
        $texts = array();

        foreach ( $prefill as $name => $value ) {
            $texts[] = array( 'field_name' => (string) $name, 'prefilled_text' => (string) $value );
        }

        adfoin_signnow_request( 'v2/documents/' . rawurlencode( $document_id ) . '/prefill-texts', 'PUT', array( 'fields' => $texts ), $record, $cred_id );
    }

    $from = adfoin_signnow_account_email( $cred_id );

    if ( ! $from ) {
        return $document_id;
    }

    $recipient = array(
        'email' => $fields['recipientEmail'],
        'role'  => ! empty( $fields['roleName'] ) ? $fields['roleName'] : 'Signer 1',
        'order' => 1,
    );

    // Use the role's signing order from the template when it can be read.
    $roles = adfoin_signnow_get_template_roles( $cred_id, $fields['templateId'] );

    if ( ! is_wp_error( $roles ) ) {
        foreach ( $roles as $role ) {
            if ( $role['roleName'] === $recipient['role'] ) {
                $recipient['order'] = max( 1, (int) $role['order'] );
            }
        }
    }

    $invite = array(
        'from' => $from,
        'to'   => array( $recipient ),
    );

    if ( ! empty( $fields['subject'] ) ) {
        $invite['subject'] = $fields['subject'];
    }

    if ( ! empty( $fields['message'] ) ) {
        $invite['message'] = $fields['message'];
    }

    adfoin_signnow_request( 'document/' . rawurlencode( $document_id ) . '/invite', 'POST', $invite, $record, $cred_id );

    return $document_id;
}

/**
 * The invite's `from` must be the account's login email. Cached per account.
 */
function adfoin_signnow_account_email( $cred_id ) {
    $cache_key = 'adfoin_signnow_email_' . md5( (string) $cred_id );
    $email     = get_transient( $cache_key );

    if ( $email ) {
        return $email;
    }

    $response = adfoin_signnow_request( 'user', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        return '';
    }

    $user  = json_decode( wp_remote_retrieve_body( $response ), true );
    $email = $user['primary_email'] ?? ( $user['emails'][0] ?? '' );

    if ( $email ) {
        set_transient( $cache_key, $email, DAY_IN_SECONDS );
    }

    return $email;
}

if ( ! function_exists( 'adfoin_signnow_request' ) ) :
function adfoin_signnow_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'signnow', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'signnow_missing_credentials', __( 'signNow API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.signnow.com/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiKey'],
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        // Keep the API key out of the log table.
        $logged                             = $args;
        $logged['headers']['Authorization'] = 'Bearer ***';
        adfoin_add_to_log( $response, $url, $logged, $record );
    }

    return $response;
}
endif;
