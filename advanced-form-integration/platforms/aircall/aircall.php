<?php

/**
 * Aircall — Create Contact via POST /v1/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: HTTP Basic Auth — username=api_id, password=api_token.
 * Both credentials are issued from Aircall Dashboard → Integrations & API → API Keys.
 *
 * @link https://developer.aircall.io/api-references/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_aircall_actions', 10, 1 );

function adfoin_aircall_actions( $actions ) {
    $actions['aircall'] = array(
        'title' => __( 'Aircall', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_aircall_settings_tab', 10, 1 );

function adfoin_aircall_settings_tab( $providers ) {
    $providers['aircall'] = __( 'Aircall', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_aircall_settings_view', 10, 1 );

function adfoin_aircall_settings_view( $current_tab ) {
    if ( 'aircall' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiId',
            'label'         => __( 'API ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Aircall API ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'apiToken',
            'label'       => __( 'API Token', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Paste your Aircall API token', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Aircall and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://dashboard.aircall.io/integrations/api-keys">Dashboard &rarr; Integrations &amp; API &rarr; API Keys</a>' ),
        esc_html__( 'Click "Generate an API key", give it a descriptive name (e.g. WordPress), and confirm.', 'advanced-form-integration' ),
        esc_html__( 'Copy both the API ID and the API Token immediately — Aircall only shows the token once.', 'advanced-form-integration' ),
        esc_html__( 'Paste them below. AFI authenticates each request as HTTP Basic Auth (api_id:api_token) against https://api.aircall.io/v1/.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'aircall', __( 'Aircall', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_aircall_credentials', 'adfoin_get_aircall_credentials', 10, 0 );

function adfoin_get_aircall_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'aircall' );
}

add_action( 'wp_ajax_adfoin_save_aircall_credentials', 'adfoin_save_aircall_credentials', 10, 0 );

function adfoin_save_aircall_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'aircall', array( 'apiId', 'apiToken' ) );
}

function adfoin_aircall_credentials_list() {
    foreach ( adfoin_read_credentials( 'aircall' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_aircall_action_fields' );

function adfoin_aircall_action_fields() {
    ?>
    <script type="text/template" id="aircall-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Aircall Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=aircall' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Shared Contact', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[isShared]" v-model="fielddata.isShared">
                        <option value="true"><?php esc_html_e( 'Yes (shared with all teammates)', 'advanced-form-integration' ); ?></option>
                        <option value="false"><?php esc_html_e( 'No (private)', 'advanced-form-integration' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Aircall contacts are shared by default so every user on the workspace sees them.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_aircall_fields', 'adfoin_get_aircall_fields' );

function adfoin_get_aircall_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'first_name',   'value' => __( 'First Name (required if no Last Name)', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',    'value' => __( 'Last Name (required if no First Name)', 'advanced-form-integration' ) ),
        array( 'key' => 'email',        'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone',        'value' => __( 'Phone Number — E.164 format, e.g. +15551234567 (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'description',  'value' => __( 'Description', 'advanced-form-integration' ) ),
        array( 'key' => 'information',  'value' => __( 'Information (internal note)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_aircall_job_queue', 'adfoin_aircall_job_queue', 10, 1 );

function adfoin_aircall_job_queue( $data ) {
    adfoin_aircall_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_aircall_send_data( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all field-mapped values up-front. The Aircall payload nests
    // phones/emails as label/value pairs — we assemble those below from the
    // flat key=>value pairs the form gives us.
    $reserved = array( 'credId' => 1, 'isShared' => 1 );
    $values   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // Aircall requires at least one of first/last name plus email and phone.
    if ( empty( $values['email'] ) || empty( $values['phone'] ) ) {
        return;
    }
    if ( empty( $values['first_name'] ) && empty( $values['last_name'] ) ) {
        return;
    }

    $payload = array();

    if ( ! empty( $values['first_name'] ) ) {
        $payload['first_name'] = (string) $values['first_name'];
    }
    if ( ! empty( $values['last_name'] ) ) {
        $payload['last_name'] = (string) $values['last_name'];
    }
    if ( ! empty( $values['company_name'] ) ) {
        $payload['company_name'] = (string) $values['company_name'];
    }
    if ( ! empty( $values['description'] ) ) {
        $payload['description'] = (string) $values['description'];
    }
    if ( ! empty( $values['information'] ) ) {
        $payload['information'] = (string) $values['information'];
    }

    // is_shared default true — cast the form-side string to a real boolean
    // so wp_json_encode emits JSON true/false rather than "true"/"false".
    $is_shared_raw = $field_data['isShared'] ?? 'true';
    $payload['is_shared'] = ! in_array( strtolower( (string) $is_shared_raw ), array( 'false', '0', 'no', '' ), true );

    $payload['phone_numbers'] = array(
        array(
            'label' => 'Work',
            'value' => (string) $values['phone'],
        ),
    );

    $payload['emails'] = array(
        array(
            'label' => 'Work',
            'value' => (string) $values['email'],
        ),
    );

    adfoin_aircall_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_aircall_request' ) ) :
function adfoin_aircall_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'aircall', $cred_id );

    if (
        ! is_array( $credentials )
        || empty( $credentials['apiId'] )
        || empty( $credentials['apiToken'] )
    ) {
        return new WP_Error( 'aircall_missing_credentials', __( 'Aircall API ID and API Token are both required.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.aircall.io/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $credentials['apiId'] . ':' . $credentials['apiToken'] ),
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
