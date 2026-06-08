<?php

/**
 * OpenPhone — modern SMB cloud phone system integration.
 *
 *   - create_contact → POST /v1/contacts
 *   - send_message   → POST /v1/messages
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 *
 * Auth quirk: OpenPhone takes the raw API key as the Authorization header
 * value — NOT the conventional "Bearer <key>" form. Per their docs:
 *   Authorization: op_live_xxx...
 *
 * The "createdByUserId" required by POST /v1/contacts must reference an
 * existing OpenPhone user (US... prefix). We expose it once on the credential
 * record as "defaultUserId" so it does not have to be entered per-form.
 *
 * @link https://www.openphone.com/docs/api-reference
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_openphone_actions', 10, 1 );

function adfoin_openphone_actions( $actions ) {
    $actions['openphone'] = array(
        'title' => __( 'OpenPhone', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
            'send_message'   => __( 'Send Message (SMS)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_openphone_settings_tab', 10, 1 );

function adfoin_openphone_settings_tab( $providers ) {
    $providers['openphone'] = __( 'OpenPhone', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_openphone_settings_view', 10, 1 );

function adfoin_openphone_settings_view( $current_tab ) {
    if ( 'openphone' !== $current_tab ) {
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
            'placeholder'   => 'op_...',
            'show_in_table' => true,
        ),
        array(
            'name'          => 'defaultUserId',
            'label'         => __( 'Default User ID (createdByUserId)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => 'US...',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to OpenPhone and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://my.openphone.com/settings/api">Settings &rarr; Developer &rarr; API</a>' ),
        esc_html__( 'Click "Generate API key", give it a descriptive name (e.g. WordPress AFI), then copy the key — OpenPhone only shows it in full once.', 'advanced-form-integration' ),
        esc_html__( 'Paste the key into the API Key field below. AFI sends it as the raw Authorization header value (no "Bearer " prefix), per OpenPhone API docs.', 'advanced-form-integration' ),
        sprintf( __( 'Get a Default User ID: call %s with your API key and copy the "id" (US...) of the user who should own contacts created from WordPress.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://www.openphone.com/docs/api-reference/users/list-users">GET /v1/users</a>' ),
        esc_html__( 'Paste the User ID into "Default User ID" — it is sent as createdByUserId, which the contacts endpoint requires.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'openphone', __( 'OpenPhone', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_openphone_credentials', 'adfoin_get_openphone_credentials', 10, 0 );

function adfoin_get_openphone_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'openphone' );
}

add_action( 'wp_ajax_adfoin_save_openphone_credentials', 'adfoin_save_openphone_credentials', 10, 0 );

function adfoin_save_openphone_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'openphone', array( 'apiKey', 'defaultUserId' ) );
}

function adfoin_openphone_credentials_list() {
    foreach ( adfoin_read_credentials( 'openphone' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_openphone_action_fields' );

function adfoin_openphone_action_fields() {
    ?>
    <script type="text/template" id="openphone-action-template">
        <table class="form-table" v-if="action.task == 'create_contact' || action.task == 'send_message'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'OpenPhone Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=openphone' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
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

add_action( 'wp_ajax_adfoin_get_openphone_fields', 'adfoin_get_openphone_fields' );

function adfoin_get_openphone_fields() {
    adfoin_verify_nonce();

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( 'send_message' === $task ) {
        $fields = array(
            array( 'key' => 'from_number', 'value' => __( 'From Number (required, an OpenPhone-owned number in E.164, e.g. +15551234567)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'to_number',   'value' => __( 'To Number (required, recipient in E.164, e.g. +15559876543)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'message',     'value' => __( 'Message (required, SMS body)', 'advanced-form-integration' ), 'required' => true ),
        );
    } else {
        // create_contact (default)
        $fields = array(
            array( 'key' => 'first_name', 'value' => __( 'First Name (one of First/Last required)', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name',  'value' => __( 'Last Name (one of First/Last required)', 'advanced-form-integration' ) ),
            array( 'key' => 'email',      'value' => __( 'Email', 'advanced-form-integration' ) ),
            array( 'key' => 'phone',      'value' => __( 'Phone (E.164, e.g. +15551234567)', 'advanced-form-integration' ) ),
            array( 'key' => 'company',    'value' => __( 'Company', 'advanced-form-integration' ) ),
            array( 'key' => 'role',       'value' => __( 'Role / Job Title', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_openphone_job_queue', 'adfoin_openphone_job_queue', 10, 1 );

function adfoin_openphone_job_queue( $data ) {
    adfoin_openphone_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_openphone_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'create_contact', 'send_message' ), true ) ) {
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

    // Resolve all flat values up-front. The task-specific payload is
    // assembled below — the form just feeds us flat key=>value pairs.
    $values   = array();
    $reserved = array( 'credId' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    if ( 'send_message' === $task ) {
        // Required: from_number, to_number, message body.
        if ( empty( $values['from_number'] ) || empty( $values['to_number'] ) || empty( $values['message'] ) ) {
            return;
        }

        $payload = array(
            'content' => (string) $values['message'],
            'from'    => (string) $values['from_number'],
            // OpenPhone accepts an array of recipients; we forward a single
            // recipient from the mapped field as a one-element array.
            'to'      => array( (string) $values['to_number'] ),
        );

        adfoin_openphone_request( 'messages', 'POST', $payload, $record, $cred_id );
        return;
    }

    // create_contact
    // Require at least one of first_name / last_name (the API allows either
    // alone, but a contact with neither is meaningless).
    if ( empty( $values['first_name'] ) && empty( $values['last_name'] ) ) {
        return;
    }

    // Pull createdByUserId from the credential record (defaultUserId). The
    // OpenPhone API rejects POST /v1/contacts without a real user ID.
    $credentials = adfoin_get_credentials_by_id( 'openphone', $cred_id );
    if ( ! is_array( $credentials ) || empty( $credentials['defaultUserId'] ) ) {
        return;
    }

    $default_fields = array();
    if ( ! empty( $values['first_name'] ) ) {
        $default_fields['firstName'] = (string) $values['first_name'];
    }
    if ( ! empty( $values['last_name'] ) ) {
        $default_fields['lastName'] = (string) $values['last_name'];
    }
    if ( ! empty( $values['company'] ) ) {
        $default_fields['company'] = (string) $values['company'];
    }
    if ( ! empty( $values['role'] ) ) {
        $default_fields['role'] = (string) $values['role'];
    }
    if ( ! empty( $values['email'] ) ) {
        $default_fields['emails'] = array(
            array(
                'name'  => 'Work',
                'value' => (string) $values['email'],
            ),
        );
    }
    if ( ! empty( $values['phone'] ) ) {
        $default_fields['phoneNumbers'] = array(
            array(
                'name'  => 'Work',
                'value' => (string) $values['phone'],
            ),
        );
    }

    $payload = array(
        'defaultFields'   => $default_fields,
        'createdByUserId' => (string) $credentials['defaultUserId'],
        'source'          => 'wordpress',
    );

    adfoin_openphone_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_openphone_request' ) ) :
/**
 * Authenticated JSON request against https://api.openphone.com/v1/.
 * Auth header is the raw API key — NOT prefixed with "Bearer ", per
 * OpenPhone's documentation.
 */
function adfoin_openphone_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'openphone', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'openphone_missing_credentials', __( 'OpenPhone API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.openphone.com/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            // OpenPhone expects the raw API key — no "Bearer " prefix.
            'Authorization' => $credentials['apiKey'],
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
