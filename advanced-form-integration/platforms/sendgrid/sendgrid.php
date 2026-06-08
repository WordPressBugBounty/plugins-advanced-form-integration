<?php

/**
 * SendGrid (Twilio) — Add/Update Marketing Contact via
 * PUT /v3/marketing/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer {api_key} with Marketing scope.
 *
 * @link https://www.twilio.com/docs/sendgrid/api-reference/contacts/add-or-update-a-contact
 */

add_filter( 'adfoin_action_providers', 'adfoin_sendgrid_actions', 10, 1 );

function adfoin_sendgrid_actions( $actions ) {

    $actions['sendgrid'] = array(
        'title' => __( 'SendGrid', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add or Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sendgrid_settings_tab', 10, 1 );

function adfoin_sendgrid_settings_tab( $providers ) {
    $providers['sendgrid'] = __( 'SendGrid', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendgrid_settings_view', 10, 1 );

function adfoin_sendgrid_settings_view( $current_tab ) {
    if ( 'sendgrid' !== $current_tab ) {
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
            'placeholder'   => __( 'SendGrid Settings → API Keys', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        sprintf(
            /* translators: %s: link to SendGrid API keys settings. */
            esc_html__( 'In %s create a key with Marketing permissions (read+write).', 'advanced-form-integration' ),
            '<a href="https://app.sendgrid.com/settings/api_keys" target="_blank" rel="noopener noreferrer">Settings → API Keys</a>'
        ),
        esc_html__( 'Paste it below. AFI sends Authorization: Bearer {key} to api.sendgrid.com/v3/.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'sendgrid', __( 'SendGrid', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_sendgrid_credentials', 'adfoin_get_sendgrid_credentials', 10, 0 );

function adfoin_get_sendgrid_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendgrid' );
}

add_action( 'wp_ajax_adfoin_save_sendgrid_credentials', 'adfoin_save_sendgrid_credentials', 10, 0 );

function adfoin_save_sendgrid_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendgrid', array( 'apiKey' ) );
}

if ( ! function_exists( 'adfoin_sendgrid_credentials_list' ) ) :
function adfoin_sendgrid_credentials_list() {
    foreach ( adfoin_read_credentials( 'sendgrid' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

/**
 * Migrate the legacy single-option API key into the multi-account store.
 */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'sendgrid', array(
            'apiKey' => 'adfoin_sendgrid_api_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_sendgrid_action_fields' );

function adfoin_sendgrid_action_fields() {
    ?>
    <script type="text/template" id="sendgrid-action-template">
        <table class="form-table" v-if="action.task == 'subscribe'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SendGrid Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendgrid' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php esc_html_e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.lists" :value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'SendGrid [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_sendgrid_lists', 'adfoin_get_sendgrid_lists', 10, 0 );

function adfoin_get_sendgrid_lists() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    if ( ! $cred_id ) {
        wp_send_json_error( array( 'message' => __( 'No SendGrid account selected.', 'advanced-form-integration' ) ) );
    }

    $lists      = array();
    $page_token = '';
    $attempts   = 0;

    do {
        $attempts++;

        $query = array( 'page_size' => 200 );
        if ( $page_token ) {
            $query['page_token'] = $page_token;
        }

        $response = adfoin_sendgrid_request( 'marketing/lists', 'GET', $query, array(), $cred_id );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            break;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['result'] ) && is_array( $body['result'] ) ) {
            foreach ( $body['result'] as $list ) {
                if ( isset( $list['id'], $list['name'] ) ) {
                    $lists[ (string) $list['id'] ] = (string) $list['name'];
                }
            }
        }

        $page_token = isset( $body['_metadata']['next'] )
            ? adfoin_sendgrid_extract_page_token( $body['_metadata']['next'] )
            : ( isset( $body['next_page_token'] ) ? (string) $body['next_page_token'] : '' );
    } while ( $page_token && $attempts < 10 );

    wp_send_json_success( $lists );
}

if ( ! function_exists( 'adfoin_sendgrid_extract_page_token' ) ) :
function adfoin_sendgrid_extract_page_token( $next_url ) {
    $parts = wp_parse_url( (string) $next_url );
    if ( empty( $parts['query'] ) ) {
        return '';
    }
    parse_str( $parts['query'], $params );
    return isset( $params['page_token'] ) ? (string) $params['page_token'] : '';
}
endif;

add_action( 'adfoin_sendgrid_job_queue', 'adfoin_sendgrid_job_queue', 10, 1 );

function adfoin_sendgrid_job_queue( $data ) {
    adfoin_sendgrid_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sendgrid_send_data( $record, $posted_data ) {
    if ( 'subscribe' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $list_id    = isset( $field_data['listId'] ) ? trim( (string) $field_data['listId'] ) : '';

    if ( ! $cred_id ) {
        return;
    }

    $email = isset( $field_data['email'] )
        ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
        : '';

    if ( ! $email ) {
        return;
    }

    $contact = array( 'email' => $email );

    foreach ( array( 'first_name', 'last_name' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $contact[ $key ] = $value;
        }
    }

    $payload = array( 'contacts' => array( $contact ) );

    if ( '' !== $list_id ) {
        $payload['list_ids'] = array( $list_id );
    }

    $payload = apply_filters( 'adfoin_sendgrid_contact_payload', $payload, $field_data, $posted_data );

    adfoin_sendgrid_request( 'marketing/contacts', 'PUT', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_sendgrid_request' ) ) :
/**
 * Call the SendGrid v3 API.
 *
 * @param string $endpoint Path under /v3/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_sendgrid_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $api_key = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'sendgrid', $cred_id );
        if ( is_array( $credentials ) && isset( $credentials['apiKey'] ) ) {
            $api_key = trim( (string) $credentials['apiKey'] );
        }
    }

    if ( ! $api_key ) {
        $api_key = (string) get_option( 'adfoin_sendgrid_api_key', '' );
    }

    if ( ! $api_key ) {
        return new WP_Error( 'sendgrid_missing_key', __( 'SendGrid API key is missing.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.sendgrid.com/v3/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Accept'        => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }
    } else {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
