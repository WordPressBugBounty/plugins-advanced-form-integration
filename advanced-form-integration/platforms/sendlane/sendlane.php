<?php

add_filter( 'adfoin_action_providers', 'adfoin_sendlane_actions', 10, 1 );

function adfoin_sendlane_actions( $actions ) {

    $actions['sendlane'] = array(
        'title' => __( 'Sendlane', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add/Update Contact', 'advanced-form-integration' ),
            'unsubscribe' => __( 'Unsubscribe Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}



add_filter( 'adfoin_settings_tabs', 'adfoin_sendlane_settings_tab', 10, 1 );

function adfoin_sendlane_settings_tab( $providers ) {
    $providers['sendlane'] = __( 'Sendlane', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendlane_settings_view', 10, 1 );

function adfoin_sendlane_settings_view( $current_tab ) {
    if ( 'sendlane' !== $current_tab ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'Sendlane v2 API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter your v2 API Token', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Log in to your Sendlane account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to the API section in your dashboard.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Generate a <strong>v2 access token</strong> and copy it.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Paste the token in the field above and click "Add Account".', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'sendlane', 'Sendlane', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendlane_credentials', 'adfoin_get_sendlane_credentials' );
function adfoin_get_sendlane_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendlane' );
}

add_action( 'wp_ajax_adfoin_save_sendlane_credentials', 'adfoin_save_sendlane_credentials' );
function adfoin_save_sendlane_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendlane', array( 'api_key' => 'password' ) );
}

add_action( 'wp_ajax_adfoin_get_sendlane_credentials_list', 'adfoin_sendlane_get_credentials_list_ajax' );
function adfoin_sendlane_get_credentials_list_ajax() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendlane', array(
        array( 'name' => 'api_key', 'mask' => true ),
    ) );
}

add_action( 'adfoin_add_js_fields', 'adfoin_sendlane_js_fields', 10, 1 );

function adfoin_sendlane_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_sendlane_action_fields' );

function adfoin_sendlane_action_fields() {
    ?>
    <script type="text/template" id="sendlane-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row"><?php esc_html_e( 'Sendlane Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendlane' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Sendlane List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""><?php _e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index">{{ item }}</option>
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
            <?php adfoin_pro_feature_notice( 'subscribe', 'Sendlane [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_sendlane_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'sendlane', $cred_id );
    $api_key = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';

    if ( ! $api_key ) {
        return new WP_Error( 'missing_credentials', __( 'Sendlane API credentials not found', 'advanced-form-integration' ) );
    }

    $url = 'https://api.sendlane.com/v2/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_sendlane_lists', 'adfoin_get_sendlane_lists', 10, 0 );

function adfoin_get_sendlane_lists() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists   = array();
    $page    = 1;
    $safe    = 0;

    do {
        $safe++;
        $response = adfoin_sendlane_request( 'lists?page=' . $page, 'GET', array(), array(), $cred_id );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            wp_send_json_error();
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
            foreach ( $body['data'] as $list ) {
                if ( isset( $list['id'], $list['name'] ) ) {
                    $lists[ $list['id'] ] = $list['name'];
                }
            }
        }

        $last_page = isset( $body['meta']['last_page'] ) ? (int) $body['meta']['last_page'] : 1;
        $page++;

    } while ( $page <= $last_page && $safe < 20 );

    wp_send_json_success( $lists );
}

function adfoin_sendlane_find_contact_id( $email, $cred_id = '' ) {
    $response = adfoin_sendlane_request( 'contacts?email=' . rawurlencode( $email ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        return 0;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( isset( $body['data'][0]['id'] ) ) {
        return (int) $body['data'][0]['id'];
    }

    return 0;
}

add_action( 'adfoin_sendlane_job_queue', 'adfoin_sendlane_job_queue', 10, 1 );

function adfoin_sendlane_job_queue( $data ) {
    adfoin_sendlane_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sendlane_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = isset( $record['task'] ) ? $record['task'] : '';
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $list_id    = isset( $field_data['listId'] ) ? $field_data['listId'] : '';
    $email      = empty( $field_data['email'] ) ? '' : trim( adfoin_get_parsed_values( $field_data['email'], $posted_data ) );

    if ( ! $email ) {
        return;
    }

    if ( 'unsubscribe' === $task ) {
        $contact_id = adfoin_sendlane_find_contact_id( $email, $cred_id );
        if ( $contact_id ) {
            adfoin_sendlane_request( 'contacts/' . $contact_id . '/unsubscribe', 'POST', array(), $record, $cred_id );
        }
        return;
    }

    if ( 'subscribe' !== $task || ! $list_id ) {
        return;
    }

    $first = empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
    $last  = empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data );
    $phone = empty( $field_data['phone'] ) ? '' : adfoin_get_parsed_values( $field_data['phone'], $posted_data );

    $contact = array( 'email' => $email );
    if ( $first ) { $contact['first_name'] = $first; }
    if ( $last )  { $contact['last_name']  = $last; }
    if ( $phone ) { $contact['phone']       = $phone; }

    adfoin_sendlane_request( 'lists/' . $list_id . '/contacts', 'POST', array( 'contacts' => array( $contact ) ), $record, $cred_id );
}
