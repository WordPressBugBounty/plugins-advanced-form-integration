<?php

/**
 * LiveChat — Create Customer via POST /v3.5/agent/action/create_customer.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <pat_token>
 *
 * LiveChat (livechatinc.com) is a popular Polish-origin live-chat platform.
 * We use Personal Access Tokens (PAT) generated from the LiveChat developer
 * console — simpler than OAuth and doesn't require a redirect popup.
 *
 * The customer model is intentionally flat: name + email + an arbitrary list
 * of session_fields key/value pairs. Optional fields (phone, company, notes)
 * are flattened into session_fields by the dispatcher.
 *
 * @link https://developers.livechat.com/docs/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_livechat_actions', 10, 1 );

function adfoin_livechat_actions( $actions ) {
    $actions['livechat'] = array(
        'title' => __( 'LiveChat', 'advanced-form-integration' ),
        'tasks' => array(
            'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_livechat_settings_tab', 10, 1 );

function adfoin_livechat_settings_tab( $providers ) {
    $providers['livechat'] = __( 'LiveChat', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_livechat_settings_view', 10, 1 );

function adfoin_livechat_settings_view( $current_tab ) {
    if ( 'livechat' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'patToken',
            'label'         => __( 'Personal Access Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your LiveChat Personal Access Token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to LiveChat and open the %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developers.livechat.com/console/tools/personal-access-tokens">Developer Console &rarr; Personal Access Tokens</a>' ),
        esc_html__( 'Click "Create new token" and grant it the customers--all:rw and customers.ban--all:rw scopes (or the broadest scope your role allows).', 'advanced-form-integration' ),
        esc_html__( 'Copy the token immediately — LiveChat only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.livechatinc.com/v3.5/ with this token in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'livechat', __( 'LiveChat', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_livechat_credentials', 'adfoin_get_livechat_credentials', 10, 0 );

function adfoin_get_livechat_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'livechat' );
}

add_action( 'wp_ajax_adfoin_save_livechat_credentials', 'adfoin_save_livechat_credentials', 10, 0 );

function adfoin_save_livechat_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'livechat', array( 'patToken' ) );
}

function adfoin_livechat_credentials_list() {
    foreach ( adfoin_read_credentials( 'livechat' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_livechat_action_fields' );

function adfoin_livechat_action_fields() {
    ?>
    <script type="text/template" id="livechat-action-template">
        <table class="form-table" v-if="action.task == 'create_customer'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'LiveChat Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=livechat' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_livechat_fields', 'adfoin_get_livechat_fields' );

function adfoin_get_livechat_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'name',    'value' => __( 'Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email',   'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone',   'value' => __( 'Phone (stored as session field)', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company (stored as session field)', 'advanced-form-integration' ) ),
        array( 'key' => 'notes',   'value' => __( 'Notes (stored as session field)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_livechat_job_queue', 'adfoin_livechat_job_queue', 10, 1 );

function adfoin_livechat_job_queue( $data ) {
    adfoin_livechat_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_livechat_send_data( $record, $posted_data ) {
    if ( 'create_customer' !== ( $record['task'] ?? '' ) ) {
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

    $reserved = array( 'credId' => 1 );
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

    // LiveChat's customer model only has name + email at the top level. Pull
    // a name from the mapped "name" field, falling back to first+last name
    // pieces if the form captured them separately.
    $name = '';
    if ( ! empty( $values['name'] ) ) {
        $name = trim( (string) $values['name'] );
    } elseif ( ! empty( $values['first_name'] ) || ! empty( $values['last_name'] ) ) {
        $name = trim( ( $values['first_name'] ?? '' ) . ' ' . ( $values['last_name'] ?? '' ) );
    }

    // Required: name + email. Abort silently if either is missing.
    if ( '' === $name || empty( $values['email'] ) ) {
        return;
    }

    $body = array(
        'name'  => $name,
        'email' => (string) $values['email'],
    );

    // session_fields is an ordered list of single-key objects in LiveChat's
    // schema (not a flat map). Anything that isn't name/email gets flattened
    // here.
    $session_field_keys = array( 'phone', 'company', 'notes' );
    $session_fields     = array();
    foreach ( $session_field_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $session_fields[] = array( $key => (string) $values[ $key ] );
        }
    }
    if ( ! empty( $session_fields ) ) {
        $body['session_fields'] = $session_fields;
    }

    adfoin_livechat_request( 'agent/action/create_customer', 'POST', $body, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_livechat_request' ) ) :
function adfoin_livechat_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'livechat', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['patToken'] ) ) {
        return new WP_Error( 'livechat_missing_credentials', __( 'LiveChat Personal Access Token not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.livechatinc.com/v3.5/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['patToken'],
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( is_array( $data ) ? $data : array() );
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
