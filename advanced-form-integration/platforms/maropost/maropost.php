<?php

/**
 * Maropost — Create or Update Contact via POST /accounts/{id}/contacts.json.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth credential is sent as `?auth_token=<key>` on every request.
 *
 * @link https://api.maropost.com/api/
 */

add_filter( 'adfoin_action_providers', 'adfoin_maropost_actions', 10, 1 );

function adfoin_maropost_actions( $actions ) {

    $actions['maropost'] = array(
        'title' => __( 'Maropost', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create or Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_maropost_settings_tab', 10, 1 );

function adfoin_maropost_settings_tab( $providers ) {
    $providers['maropost'] = __( 'Maropost', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_maropost_settings_view', 10, 1 );

function adfoin_maropost_settings_view( $current_tab ) {
    if ( 'maropost' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'accountId',
            'label'         => __( 'Account ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Numeric account ID from your Maropost dashboard', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiToken',
            'label'         => __( 'Auth Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Generate under Connections → API in Maropost', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'host',
            'label'         => __( 'API Host (optional)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'placeholder'   => 'https://api.maropost.com',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Sign in to Maropost and open Connections → API.', 'advanced-form-integration' ),
        esc_html__( 'Create or copy an API key — Maropost calls this the "Auth Token".', 'advanced-form-integration' ),
        esc_html__( 'Note your numeric Account ID from the dashboard URL or account settings.', 'advanced-form-integration' ),
        sprintf(
            /* translators: %s — code-formatted host. */
            esc_html__( 'Paste both into the form below. AFI sends every request to %s with the auth_token query parameter — override the host only if Maropost gave you a different one.', 'advanced-form-integration' ),
            '<code>https://api.maropost.com</code>'
        )
    );

    ADFOIN_Account_Manager::render_settings_view( 'maropost', __( 'Maropost', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_maropost_credentials', 'adfoin_get_maropost_credentials', 10, 0 );

function adfoin_get_maropost_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'maropost' );
}

add_action( 'wp_ajax_adfoin_save_maropost_credentials', 'adfoin_save_maropost_credentials', 10, 0 );

function adfoin_save_maropost_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'maropost', array( 'accountId', 'apiToken', 'host' ) );
}

/**
 * Render <option> tags for an account picker. Shared by free + Pro
 * templates.
 */
function adfoin_maropost_credentials_list() {
    $credentials = adfoin_read_credentials( 'maropost' );

    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Migrate legacy single-option credentials into the multi-account store.
 */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'maropost', array(
            'accountId' => 'adfoin_maropost_account_id',
            'apiToken'  => 'adfoin_maropost_api_key',
            'host'      => 'adfoin_maropost_host',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_maropost_action_fields' );

function adfoin_maropost_action_fields() {
    ?>
    <script type="text/template" id="maropost-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr valign="top">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row"></td>
            </tr>

            <tr valign="top" class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Maropost Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=maropost' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" class="alternate">
                <td scope="row-title">
                    <label for="maropost_list">
                        <?php esc_attr_e( 'List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php esc_html_e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.lists" :value="index">{{ item }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata">
            </editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Maropost [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_maropost_lists', 'adfoin_get_maropost_lists', 10, 0 );

function adfoin_get_maropost_lists() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( empty( $cred_id ) ) {
        wp_send_json_error( array( 'message' => __( 'No Maropost account selected.', 'advanced-form-integration' ) ) );
    }

    $response = adfoin_maropost_request( '/lists.json', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body ) || ! is_array( $body ) ) {
        wp_send_json_error();
    }

    $lists = array();

    foreach ( $body as $list ) {
        if ( isset( $list['id'], $list['name'] ) ) {
            $lists[ $list['id'] ] = $list['name'];
        }
    }

    wp_send_json_success( $lists );
}

add_action( 'adfoin_maropost_job_queue', 'adfoin_maropost_job_queue', 10, 1 );

function adfoin_maropost_job_queue( $data ) {
    adfoin_maropost_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_maropost_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task    = isset( $record['task'] ) ? $record['task'] : '';
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( 'create_contact' !== $task ) {
        return;
    }

    if ( empty( $cred_id ) ) {
        adfoin_add_to_log( new WP_Error( 'maropost_missing_cred', __( 'No Maropost account selected.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $email = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );

    if ( empty( $email ) ) {
        return;
    }

    $params   = array();
    $params[] = 'contact[email]=' . rawurlencode( $email );

    $first_name = adfoin_get_parsed_values( $data['firstName'] ?? '', $posted_data );
    $last_name  = adfoin_get_parsed_values( $data['lastName'] ?? '', $posted_data );

    if ( $first_name ) {
        $params[] = 'contact[first_name]=' . rawurlencode( $first_name );
    }

    if ( $last_name ) {
        $params[] = 'contact[last_name]=' . rawurlencode( $last_name );
    }

    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';

    if ( $list_id ) {
        $params[] = 'add_list_ids[]=' . rawurlencode( $list_id );
    }

    $body = implode( '&', array_filter( $params ) );

    adfoin_maropost_request( '/contacts.json', 'POST', $body, $record, $cred_id );
}

/**
 * Maropost API request.
 *
 * @param string $endpoint Path under /accounts/{id}/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (string or array).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_maropost_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $host       = '';
    $account_id = '';
    $api_token  = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'maropost', $cred_id );
        if ( is_array( $credentials ) ) {
            $host       = isset( $credentials['host'] )      ? trim( (string) $credentials['host'] )      : '';
            $account_id = isset( $credentials['accountId'] ) ? trim( (string) $credentials['accountId'] ) : '';
            $api_token  = isset( $credentials['apiToken'] )  ? trim( (string) $credentials['apiToken'] )  : '';
        }
    }

    // Fallback to legacy single-option storage.
    if ( ! $api_token ) {
        $host       = $host       ?: get_option( 'adfoin_maropost_host', '' );
        $account_id = $account_id ?: get_option( 'adfoin_maropost_account_id', '' );
        $api_token  = $api_token  ?: get_option( 'adfoin_maropost_api_key', '' );
    }

    if ( ! $host ) {
        $host = 'https://api.maropost.com';
    }
    $host = untrailingslashit( $host );

    if ( ! $account_id || ! $api_token ) {
        return new WP_Error( 'maropost_missing_credentials', __( 'Maropost credentials are not configured.', 'advanced-form-integration' ) );
    }

    $endpoint    = '/' . ltrim( $endpoint, '/' );
    $base_url    = $host . '/accounts/' . rawurlencode( $account_id );
    $request_url = add_query_arg(
        array(
            'auth_token' => $api_token,
        ),
        $base_url . $endpoint
    );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
        if ( is_string( $data ) ) {
            $args['body']                    = $data;
            $args['headers']                 = isset( $args['headers'] ) ? $args['headers'] : array();
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        } else {
            $args['body'] = $data;
        }
    }

    $response = wp_remote_request( $request_url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $request_url, $args, $record );
    }

    return $response;
}
