<?php

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

    $nonce      = wp_create_nonce( 'adfoin_maropost_settings' );
    $host       = get_option( 'adfoin_maropost_host', 'https://r1.maropost.com' );
    $account_id = get_option( 'adfoin_maropost_account_id', '' );
    $api_key    = get_option( 'adfoin_maropost_api_key', '' );
    ?>

    <form name="maropost_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_maropost_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'API Host', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_maropost_host"
                           value="<?php echo esc_attr( $host ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. https://r1.maropost.com', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description"><?php esc_html_e( 'Use the base URL provided in your Maropost account (r1, r2, etc.).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Account ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_maropost_account_id"
                           value="<?php echo esc_attr( $account_id ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'API Key (Authenticity Token)', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_maropost_api_key"
                           value="<?php echo esc_attr( $api_key ); ?>"
                           class="regular-text"/>
                    <p class="description"><?php esc_html_e( 'Create an API key under Connections → API in your Maropost dashboard.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_maropost_credentials', 'adfoin_save_maropost_credentials', 10, 0 );

function adfoin_save_maropost_credentials() {
    if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_nonce'] ), 'adfoin_maropost_settings' ) ) {
        wp_die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $host       = isset( $_POST['adfoin_maropost_host'] ) ? esc_url_raw( trim( wp_unslash( $_POST['adfoin_maropost_host'] ) ) ) : '';
    $account_id = isset( $_POST['adfoin_maropost_account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_maropost_account_id'] ) ) : '';
    $api_key    = isset( $_POST['adfoin_maropost_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_maropost_api_key'] ) ) : '';

    update_option( 'adfoin_maropost_host', untrailingslashit( $host ) );
    update_option( 'adfoin_maropost_account_id', $account_id );
    update_option( 'adfoin_maropost_api_key', $api_key );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=maropost' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_maropost_js_fields', 10, 1 );

function adfoin_maropost_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_maropost_action_fields' );

function adfoin_maropost_action_fields() {
    ?>
    <script type="text/template" id="maropost-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row"></td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
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
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_maropost_lists', 'adfoin_get_maropost_lists', 10, 0 );

function adfoin_get_maropost_lists() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $response = adfoin_maropost_request( '/lists.json', 'GET' );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error();
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

    $data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task = isset( $record['task'] ) ? $record['task'] : '';

    if ( 'create_contact' !== $task ) {
        return;
    }

    $email = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );

    if ( empty( $email ) ) {
        return;
    }

    $params = array();
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

    adfoin_maropost_request( '/contacts.json', 'POST', $body, $record );
}

function adfoin_maropost_request( $endpoint, $method = 'GET', $data = array(), $record = array() ) {
    $host       = untrailingslashit( get_option( 'adfoin_maropost_host', '' ) );
    $account_id = get_option( 'adfoin_maropost_account_id', '' );
    $api_key    = get_option( 'adfoin_maropost_api_key', '' );

    if ( ! $host || ! $account_id || ! $api_key ) {
        return new WP_Error( 'missing_credentials', __( 'Maropost credentials are not configured.', 'advanced-form-integration' ) );
    }

    $endpoint    = '/' . ltrim( $endpoint, '/' );
    $base_url    = $host . '/accounts/' . rawurlencode( $account_id );
    $request_url = add_query_arg(
        array(
            'authenticity_token' => $api_key,
        ),
        $base_url . $endpoint
    );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
        if ( is_string( $data ) ) {
            $args['body'] = $data;
            $args['headers'] = isset( $args['headers'] ) ? $args['headers'] : array();
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
