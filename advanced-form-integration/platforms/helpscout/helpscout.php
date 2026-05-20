<?php

/**
 * Help Scout — Mailbox API v2 integration.
 *
 *   - create_conversation → POST /v2/conversations
 *   - create_customer     → POST /v2/customers
 *
 * Multi-account credential storage via ADFOIN_Account_Manager (service-to-
 * service, no OAuth popup). Auth is OAuth2 client_credentials: POST
 * client_id + client_secret to /v2/oauth2/token, receive an access_token
 * good for ~2 days. There is no refresh token in this grant — when the
 * token expires we simply re-fetch via the same endpoint. The token is
 * cached in a transient keyed by md5(cred_id) for 47h (just under the
 * documented ~48h expiry).
 *
 * @link https://developer.helpscout.com/mailbox-api/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_helpscout_actions', 10, 1 );

function adfoin_helpscout_actions( $actions ) {
    $actions['helpscout'] = array(
        'title' => __( 'Help Scout', 'advanced-form-integration' ),
        'tasks' => array(
            'create_conversation' => __( 'Create Conversation', 'advanced-form-integration' ),
            'create_customer'     => __( 'Create Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_helpscout_settings_tab', 10, 1 );

function adfoin_helpscout_settings_tab( $providers ) {
    $providers['helpscout'] = __( 'Help Scout', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_helpscout_settings_view', 10, 1 );

function adfoin_helpscout_settings_view( $current_tab ) {
    if ( 'helpscout' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'client_id',
            'label'         => __( 'App ID (Client ID)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Help Scout App ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'client_secret',
            'label'       => __( 'App Secret (Client Secret)', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Help Scout App Secret', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Help Scout and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://secure.helpscout.net/users/apps/custom/">Your Apps &rarr; Create My App</a>' ),
        esc_html__( 'Name the app (e.g. "WordPress AFI"). The Redirection URL can be left blank for the client-credentials grant.', 'advanced-form-integration' ),
        esc_html__( 'Save the app, then copy the generated App ID and App Secret.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI exchanges them for an access token at https://api.helpscout.net/v2/oauth2/token.', 'advanced-form-integration' ),
        esc_html__( 'You will need a Mailbox ID for the Create Conversation task — find it in Help Scout under Manage > Mailboxes (the numeric value in the URL).', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'helpscout', __( 'Help Scout', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_helpscout_credentials', 'adfoin_get_helpscout_credentials', 10, 0 );

function adfoin_get_helpscout_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'helpscout' );
}

add_action( 'wp_ajax_adfoin_save_helpscout_credentials', 'adfoin_save_helpscout_credentials', 10, 0 );

function adfoin_save_helpscout_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'helpscout', array( 'client_id', 'client_secret' ) );
}

function adfoin_helpscout_credentials_list() {
    foreach ( adfoin_read_credentials( 'helpscout' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_helpscout_action_fields' );

function adfoin_helpscout_action_fields() {
    ?>
    <script type="text/template" id="helpscout-action-template">
        <table class="form-table" v-if="action.task == 'create_conversation' || action.task == 'create_customer'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Help Scout Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=helpscout' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_helpscout_fields', 'adfoin_get_helpscout_fields' );

function adfoin_get_helpscout_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_conversation';

    if ( 'create_customer' === $task ) {
        $fields = array(
            array( 'key' => 'first_name',   'value' => __( 'First Name (one of First/Last required)', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name',    'value' => __( 'Last Name (one of First/Last required)', 'advanced-form-integration' ) ),
            array( 'key' => 'email',        'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'phone',        'value' => __( 'Phone', 'advanced-form-integration' ) ),
            array( 'key' => 'organization', 'value' => __( 'Organization', 'advanced-form-integration' ) ),
            array( 'key' => 'job_title',    'value' => __( 'Job Title', 'advanced-form-integration' ) ),
            array( 'key' => 'location',     'value' => __( 'Location', 'advanced-form-integration' ) ),
            array( 'key' => 'background',   'value' => __( 'Background / Notes', 'advanced-form-integration' ) ),
        );
    } else {
        // create_conversation (default)
        $fields = array(
            array( 'key' => 'subject',             'value' => __( 'Subject (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'mailbox_id',          'value' => __( 'Mailbox ID (required, integer)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'customer_email',      'value' => __( 'Customer Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'customer_first_name', 'value' => __( 'Customer First Name', 'advanced-form-integration' ) ),
            array( 'key' => 'customer_last_name',  'value' => __( 'Customer Last Name', 'advanced-form-integration' ) ),
            array( 'key' => 'message_body',        'value' => __( 'Message Body (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'type',                'value' => __( 'Type (email / chat / phone — defaults to email)', 'advanced-form-integration' ) ),
            array( 'key' => 'status',              'value' => __( 'Status (active / pending / closed — defaults to active)', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_helpscout_job_queue', 'adfoin_helpscout_job_queue', 10, 1 );

function adfoin_helpscout_job_queue( $data ) {
    adfoin_helpscout_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_helpscout_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'create_conversation', 'create_customer' ), true ) ) {
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

    // Resolve all field-mapped values up-front.
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

    if ( 'create_conversation' === $task ) {
        // Required: subject, mailbox_id, message_body, customer_email.
        if (
            empty( $values['subject'] )
            || empty( $values['mailbox_id'] )
            || empty( $values['message_body'] )
            || empty( $values['customer_email'] )
        ) {
            return;
        }

        $customer = array( 'email' => (string) $values['customer_email'] );
        if ( ! empty( $values['customer_first_name'] ) ) {
            $customer['firstName'] = (string) $values['customer_first_name'];
        }
        if ( ! empty( $values['customer_last_name'] ) ) {
            $customer['lastName'] = (string) $values['customer_last_name'];
        }

        $payload = array(
            'subject'   => (string) $values['subject'],
            'customer'  => $customer,
            'mailboxId' => (int) $values['mailbox_id'],
            'type'      => ! empty( $values['type'] ) ? (string) $values['type'] : 'email',
            'status'    => ! empty( $values['status'] ) ? (string) $values['status'] : 'active',
            'threads'   => array(
                array(
                    'type'     => 'customer',
                    'customer' => $customer,
                    'text'     => (string) $values['message_body'],
                ),
            ),
        );

        adfoin_helpscout_request( 'conversations', 'POST', $payload, $record, $cred_id );
        return;
    }

    // create_customer
    // Required: email + at least one of first_name / last_name.
    if ( empty( $values['email'] ) ) {
        return;
    }
    if ( empty( $values['first_name'] ) && empty( $values['last_name'] ) ) {
        return;
    }

    $payload = array(
        'emails' => array(
            array(
                'type'  => 'work',
                'value' => (string) $values['email'],
            ),
        ),
    );

    if ( ! empty( $values['first_name'] ) ) {
        $payload['firstName'] = (string) $values['first_name'];
    }
    if ( ! empty( $values['last_name'] ) ) {
        $payload['lastName'] = (string) $values['last_name'];
    }
    if ( ! empty( $values['phone'] ) ) {
        $payload['phones'] = array(
            array(
                'type'  => 'work',
                'value' => (string) $values['phone'],
            ),
        );
    }
    if ( ! empty( $values['organization'] ) ) {
        $payload['organization'] = (string) $values['organization'];
    }
    if ( ! empty( $values['job_title'] ) ) {
        $payload['jobTitle'] = (string) $values['job_title'];
    }
    if ( ! empty( $values['location'] ) ) {
        $payload['location'] = (string) $values['location'];
    }
    if ( ! empty( $values['background'] ) ) {
        $payload['background'] = (string) $values['background'];
    }

    adfoin_helpscout_request( 'customers', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_helpscout_get_token' ) ) :
/**
 * Fetch (and cache) a Help Scout access token for the given credential.
 * Cached in a transient for 47h, just under Help Scout's documented ~48h
 * expiry. There is no refresh_token in the client_credentials grant — when
 * the token expires (or the cache is stale), we simply hit /v2/oauth2/token
 * again. Pass $force_refresh = true to bypass the cache (e.g. after a 401).
 */
function adfoin_helpscout_get_token( $cred_id, $force_refresh = false ) {
    if ( empty( $cred_id ) ) {
        return new WP_Error( 'helpscout_missing_cred', __( 'Help Scout credential ID is empty.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_helpscout_token_' . md5( (string) $cred_id );

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( ! empty( $cached ) && is_string( $cached ) ) {
            return $cached;
        }
    }

    $credentials = adfoin_get_credentials_by_id( 'helpscout', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['client_id'] ) || empty( $credentials['client_secret'] ) ) {
        return new WP_Error( 'helpscout_missing_credentials', __( 'Help Scout client_id / client_secret not configured.', 'advanced-form-integration' ) );
    }

    $response = wp_remote_post(
        'https://api.helpscout.net/v2/oauth2/token',
        array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'grant_type'    => 'client_credentials',
                'client_id'     => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $code = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $code || empty( $body['access_token'] ) ) {
        $msg = '';
        if ( isset( $body['error_description'] ) ) {
            $msg = $body['error_description'];
        } elseif ( isset( $body['error'] ) ) {
            $msg = is_string( $body['error'] ) ? $body['error'] : wp_json_encode( $body['error'] );
        } else {
            $msg = sprintf( 'HTTP %d', $code );
        }
        return new WP_Error( 'helpscout_auth_failed', $msg );
    }

    $token = (string) $body['access_token'];

    // 47h — Help Scout client_credentials tokens are documented as ~48h.
    set_transient( $cache_key, $token, 47 * HOUR_IN_SECONDS );

    return $token;
}
endif;

if ( ! function_exists( 'adfoin_helpscout_request' ) ) :
/**
 * Authenticated JSON request against https://api.helpscout.net/v2/.
 * On 401, force-refresh the token once and retry — covers the case where
 * the cached token was revoked or expired earlier than the 47h transient
 * TTL.
 */
function adfoin_helpscout_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $token = adfoin_helpscout_get_token( $cred_id );

    if ( is_wp_error( $token ) ) {
        if ( $record ) {
            adfoin_add_to_log( $token, 'https://api.helpscout.net/v2/oauth2/token', array( 'method' => 'POST' ), $record );
        }
        return $token;
    }

    $response = adfoin_helpscout_dispatch( $token, $endpoint, $method, $data, $record );

    if ( ! is_wp_error( $response ) ) {
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 401 === $code ) {
            $token = adfoin_helpscout_get_token( $cred_id, true );
            if ( ! is_wp_error( $token ) ) {
                $response = adfoin_helpscout_dispatch( $token, $endpoint, $method, $data, $record );
            }
        }
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_helpscout_dispatch' ) ) :
/**
 * Internal: build + execute a single Help Scout request with the supplied
 * bearer token. Split out so adfoin_helpscout_request can cleanly retry
 * on 401.
 */
function adfoin_helpscout_dispatch( $token, $endpoint, $method, $data, $record ) {
    $url    = 'https://api.helpscout.net/v2/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
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
