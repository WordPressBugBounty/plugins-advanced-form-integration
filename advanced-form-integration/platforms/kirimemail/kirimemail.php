<?php

/**
 * KIRIM.EMAIL action platform — public REST API confirmed at
 * https://api.kirim.email/v3 (docs published at https://www.kontakapi.com/,
 * domain per the vendor's 2022 "Change Of API Documentation Domain"
 * announcement). Endpoints used: GET /list, POST /subscriber/,
 * DELETE /subscriber/email/{email}.
 */

add_filter( 'adfoin_action_providers', 'adfoin_kirimemail_actions', 10, 1 );

function adfoin_kirimemail_actions( $actions ) {
    $actions['kirimemail'] = array(
        'title' => __( 'KIRIM.EMAIL', 'advanced-form-integration' ),
        'tasks' => array(
            'add_subscriber'    => __( 'Add Subscriber', 'advanced-form-integration' ),
            'remove_subscriber' => __( 'Remove Subscriber', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_kirimemail_settings_tab', 10, 1 );

function adfoin_kirimemail_settings_tab( $providers ) {
    $providers['kirimemail'] = __( 'KIRIM.EMAIL', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_kirimemail_settings_view', 10, 1 );

function adfoin_kirimemail_settings_view( $current_tab ) {
    if ( 'kirimemail' != $current_tab ) {
        return;
    }

    $title     = __( 'KIRIM.EMAIL', 'advanced-form-integration' );
    $key       = 'kirimemail';
    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'    => 'username',
                    'label'  => __( 'Username', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'apiKey',
                    'label'  => __( 'API Key', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = __(
        '<p>
            <ol>
                <li>Log in to your KIRIM.EMAIL account and open the Application page.</li>
                <li>Copy your account Username and API Key.</li>
            </ol>
        </p>',
        'advanced-form-integration'
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'wp_ajax_adfoin_get_kirimemail_credentials', 'adfoin_get_kirimemail_credentials', 10, 0 );

function adfoin_get_kirimemail_credentials() {
    adfoin_verify_nonce();

    wp_send_json_success( adfoin_read_credentials( 'kirimemail' ) );
}

add_action( 'wp_ajax_adfoin_save_kirimemail_credentials', 'adfoin_save_kirimemail_credentials', 10, 0 );

function adfoin_save_kirimemail_credentials() {
    adfoin_verify_nonce();

    $platform = sanitize_text_field( wp_unslash( $_POST['platform'] ) );

    if ( 'kirimemail' == $platform ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );

        adfoin_save_credentials( $platform, $data );
    }

    wp_send_json_success();
}

function adfoin_kirimemail_credentials_list() {
    $html        = '';
    $credentials = adfoin_read_credentials( 'kirimemail' );

    foreach ( $credentials as $option ) {
        $html .= '<option value="' . esc_attr( $option['id'] ) . '">' . esc_html( $option['title'] ) . '</option>';
    }

    echo $html;
}

add_action( 'adfoin_action_fields', 'adfoin_kirimemail_action_fields' );

function adfoin_kirimemail_action_fields() {
    ?>
    <script type="text/template" id="kirimemail-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_subscriber' || action.task == 'remove_subscriber'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'KIRIM.EMAIL Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <?php adfoin_kirimemail_credentials_list(); ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=kirimemail' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>
            <tr valign="top" v-if="action.task == 'add_subscriber'">
                <td scope="row">
                    <?php esc_attr_e( 'Select List', 'advanced-form-integration' ); ?>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required>
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(name, id) in fielddata.lists" :value="id"> {{ name }} </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

/**
 * KIRIM.EMAIL authenticates with a per-request HMAC, not a static API key
 * header: Auth-Id (account username) + Auth-Token
 * (hash_hmac('sha256', "{username}::{apiKey}::{timestamp}", apiKey)) +
 * Timestamp, all three sent together since the token is bound to that
 * timestamp. Documented under Getting Started > Authentication at
 * https://www.kontakapi.com/.
 */
function adfoin_kirimemail_request( $endpoint, $method = 'GET', $body = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'kirimemail', $cred_id );
    $username    = isset( $credentials['username'] ) ? $credentials['username'] : '';
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $timestamp   = time();
    $token       = hash_hmac( 'sha256', $username . '::' . $api_key . '::' . $timestamp, $api_key );

    $base_url = 'https://api.kirim.email/v3/';
    $url      = $base_url . ltrim( $endpoint, '/' );

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'Auth-Id'    => $username,
            'Auth-Token' => $token,
            'Timestamp'  => $timestamp,
        ),
    );

    if ( 'GET' != $method && 'DELETE' != $method ) {
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $args['body']                    = $body;
    }

    $response = wp_remote_request( $url, $args );

    if ( ! empty( $record ) ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_kirimemail_lists', 'adfoin_get_kirimemail_lists', 10, 0 );

function adfoin_get_kirimemail_lists() {
    adfoin_verify_nonce();

    $cred_id = sanitize_text_field( wp_unslash( $_POST['credId'] ) );

    $response = adfoin_kirimemail_request( 'list', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( is_array( $body ) && isset( $body['data'] ) && is_array( $body['data'] ) ) {
        $lists = array();

        foreach ( $body['data'] as $list ) {
            $lists[ $list['id'] ] = $list['name'];
        }

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_kirimemail_job_queue', 'adfoin_kirimemail_job_queue', 10, 1 );

function adfoin_kirimemail_job_queue( $data ) {
    adfoin_kirimemail_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_kirimemail_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    $task    = isset( $record['task'] ) ? $record['task'] : '';

    unset( $data['credId'], $data['listId'] );

    $fields = array();

    foreach ( $data as $key => $value ) {
        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    if ( empty( $fields['email'] ) ) {
        return;
    }

    if ( 'add_subscriber' == $task ) {
        // The API only accepts a single "full_name" field, not separate
        // first/last name — see Subscriber > Create Subscriber in the docs.
        $full_name = trim( trim( isset( $fields['first_name'] ) ? $fields['first_name'] : '' ) . ' ' . trim( isset( $fields['last_name'] ) ? $fields['last_name'] : '' ) );

        $body = array(
            'email' => $fields['email'],
        );

        if ( ! empty( $list_id ) ) {
            $body['lists'] = $list_id;
        }

        if ( '' !== $full_name ) {
            $body['full_name'] = $full_name;
        }

        adfoin_kirimemail_request( 'subscriber/', 'POST', $body, $record, $cred_id );
    } elseif ( 'remove_subscriber' == $task ) {
        adfoin_kirimemail_request( 'subscriber/email/' . rawurlencode( $fields['email'] ), 'DELETE', array(), $record, $cred_id );
    }
}
