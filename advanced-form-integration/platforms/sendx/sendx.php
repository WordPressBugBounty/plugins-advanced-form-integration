<?php

add_filter( 'adfoin_action_providers', 'adfoin_sendx_actions', 10, 1 );

function adfoin_sendx_actions( $actions ) {

    $actions['sendx'] = array(
        'title' => __( 'SendX', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sendx_settings_tab', 10, 1 );

function adfoin_sendx_settings_tab( $providers ) {
    $providers['sendx'] = __( 'SendX', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendx_settings_view', 10, 1 );

function adfoin_sendx_settings_view( $current_tab ) {
    if( $current_tab != 'sendx' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $old_api_key = get_option( 'adfoin_sendx_api_key' ) ? get_option( 'adfoin_sendx_api_key' ) : '';
    $existing_creds = adfoin_read_credentials( 'sendx' );

    if ( $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'      => wp_generate_uuid4(),
            'title'   => 'Default Account (Legacy)',
            'api_key' => $old_api_key,
        );
        adfoin_save_credentials( 'sendx', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'password',
            'required'      => true,
            'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to <a href="https://app.sendx.io/setting" target="_blank" rel="noopener noreferrer">SendX Settings</a> and find your Team API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy the API Key and paste it in the field above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" to save.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'sendx', 'SendX', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendx_credentials', 'adfoin_get_sendx_credentials' );
function adfoin_get_sendx_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendx' );
}

add_action( 'wp_ajax_adfoin_save_sendx_credentials', 'adfoin_save_sendx_credentials' );
function adfoin_save_sendx_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendx', array( 'api_key' => 'password' ) );
}

add_action( 'wp_ajax_adfoin_get_sendx_credentials_list', 'adfoin_sendx_get_credentials_list_ajax' );
function adfoin_sendx_get_credentials_list_ajax() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_key', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendx', $fields );
}

add_action( 'adfoin_action_fields', 'adfoin_sendx_action_fields' );

function adfoin_sendx_action_fields() {
?>
    <script type="text/template" id="sendx-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php esc_html_e( 'SendX Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendx' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'SendX [PRO]', 'custom fields' ); ?>
        </table>
    </script>


<?php
}

/*
 * SendX API Request
 */
function adfoin_sendx_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $account = adfoin_get_credentials_by_id( 'sendx', $cred_id );

    if ( is_wp_error( $account ) || empty( $account ) ) {
        return new WP_Error( 'no_credentials', __( 'SendX credentials not found.', 'advanced-form-integration' ) );
    }

    $api_key = isset( $account['api_key'] ) ? $account['api_key'] : '';

    $url = 'https://api.sendx.io/api/v1/rest/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'X-Team-ApiKey' => $api_key,
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_sendx_job_queue', 'adfoin_sendx_job_queue', 10, 1 );

function adfoin_sendx_job_queue( $data ) {
    adfoin_sendx_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to SendX API
 */
function adfoin_sendx_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task       = $record['task'];
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( $task == 'subscribe' ) {
        $email      = empty( $field_data['email'] ) ? '' : adfoin_get_parsed_values( $field_data['email'], $posted_data );
        $first_name = empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data );
        $last_name  = empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data );
        $company    = empty( $field_data['company'] ) ? '' : adfoin_get_parsed_values( $field_data['company'], $posted_data );
        $birthday   = empty( $field_data['birthday'] ) ? '' : adfoin_get_parsed_values( $field_data['birthday'], $posted_data );

        if ( empty( $email ) ) {
            return;
        }

        $contact_data = array(
            'email'     => trim( $email ),
            'firstName' => $first_name,
            'lastName'  => $last_name,
            'company'   => $company,
            'birthday'  => $birthday,
        );

        adfoin_sendx_request( 'contact/identify', 'POST', $contact_data, $record, $cred_id );
    }
}