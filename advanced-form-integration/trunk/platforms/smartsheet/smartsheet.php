<?php

add_filter( 'adfoin_action_providers', 'adfoin_smartsheet_actions', 10, 1 );

function adfoin_smartsheet_actions( $actions ) {

    $actions['smartsheet'] = array(
        'title' => __( 'Smartsheet', 'advanced-form-integration' ),
        'tasks' => array(
            'add_row'   => __( 'Add New Row', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

/**
 * Get Smartsheet credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_token' key, or empty string if not found
 */
function adfoin_smartsheet_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_token = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'smartsheet' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_token = $single['api_token'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_token = get_option( 'adfoin_smartsheet_api_token' ) ? get_option( 'adfoin_smartsheet_api_token' ) : '';
    }

    return array(
        'api_token' => $api_token
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_smartsheet_settings_tab', 10, 1 );

function adfoin_smartsheet_settings_tab( $providers ) {
    $providers['smartsheet'] = __( 'Smartsheet', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_smartsheet_settings_view', 10, 1 );

function adfoin_smartsheet_settings_view( $current_tab ) {
    if( $current_tab != 'smartsheet' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_smartsheet_api_token' ) ? get_option( 'adfoin_smartsheet_api_token' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'smartsheet' );

    if ( $old_api_token && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'api_token' => $old_api_token
        );
        adfoin_save_credentials( 'smartsheet', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Token', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Smartsheet account and navigate to Account > Personal Settings > API Access.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Generate a new API token.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy the API token and enter it in the field above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'smartsheet', 'Smartsheet', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_smartsheet_credentials', 'adfoin_get_smartsheet_credentials' );
function adfoin_get_smartsheet_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'smartsheet' );
}

add_action( 'wp_ajax_adfoin_save_smartsheet_credentials', 'adfoin_save_smartsheet_credentials' );
function adfoin_save_smartsheet_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'smartsheet', array( 'api_token' ) );
}

add_action( 'wp_ajax_adfoin_get_smartsheet_credentials_list', 'adfoin_smartsheet_get_credentials_list_ajax' );
function adfoin_smartsheet_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_token', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'smartsheet', $fields );
}

add_action( 'admin_post_adfoin_smartsheet_save_api_token', 'adfoin_save_smartsheet_api_token', 10, 0 );

function adfoin_save_smartsheet_api_token() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_smartsheet_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_token = sanitize_text_field( $_POST["adfoin_smartsheet_api_token"] );

    // Save tokens
    update_option( "adfoin_smartsheet_api_token", $api_token );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=smartsheet" );
}

add_action( 'adfoin_action_fields', 'adfoin_smartsheet_action_fields' );

function adfoin_smartsheet_action_fields() {
    ?>
    <script type="text/template" id="smartsheet-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_row'">
                <th scope="row"><?php esc_html_e( 'Smartsheet Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=smartsheet' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_row'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Sheet Name', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required" @change="getFields">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_row'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Add row to bottom', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" value="true" name="fieldData[add_row_to_bottom]" v-model="fielddata.add_row_to_bottom" />
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_smartsheet_list', 'adfoin_get_smartsheet_list', 10, 0 );
/*
 * Get Smartsheet lists
 */
function adfoin_get_smartsheet_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_smartsheet_request( 'sheets?pageSize=1000', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( wp_remote_retrieve_body( $data ) );
    $lists = wp_list_pluck( $body->data, 'name', 'id' );

    wp_send_json_success( $lists );
}

add_action( 'wp_ajax_adfoin_get_smartsheet_fields', 'adfoin_get_smartsheet_fields', 10, 0 );
/*
 * Get Smartsheet fields
 */
function adfoin_get_smartsheet_fields() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $sheet_id = isset( $_REQUEST['listId'] ) ? $_REQUEST['listId'] : '';
    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_smartsheet_request( "sheets/{$sheet_id}", 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( wp_remote_retrieve_body( $data ) );
    $fields = wp_list_pluck( $body->columns, 'title', 'id' );
    $fields['attachment'] = __( 'File Attachment', 'advanced-form-integration' );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_smartsheet_job_queue', 'adfoin_smartsheet_job_queue', 10, 1 );

function adfoin_smartsheet_job_queue( $data ) {
    adfoin_smartsheet_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Smartsheet API
 */
function adfoin_smartsheet_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data['field_data'];
    $sheet_id = $data['listId'];
    $task    = $record['task'];
    $add_row_to_bottom = isset( $data['add_row_to_bottom'] ) ? $data['add_row_to_bottom'] : false;
    
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if( $task == 'add_row' ) {
        $attachment = isset( $data['attachment'] ) ? adfoin_get_parsed_values( $data['attachment'], $posted_data ) : '';

        unset( $data['listId'], $data['list'], $data['attachment'], $data['add_row_to_bottom'], $data['credId'] );

        $holder = array();

        foreach ( $data as $key => $value ) {
            if( $value ) {
                $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

                if( $parsed_value ) {
                    array_push( $holder, array( 'columnId' => $key, 'objectValue' => $parsed_value ) );
                }
            }
            
        }

        if ( filter_var( $add_row_to_bottom, FILTER_VALIDATE_BOOLEAN ) ) {
            $to_be_sent = array( 'toBottom' => 'true', 'cells' => $holder );
        } else {
            $to_be_sent = array( 'toTop' => 'true', 'cells' => $holder );
        }

        $return = adfoin_smartsheet_request( "sheets/{$sheet_id}/rows", 'POST', array( $to_be_sent ), $record, $cred_id );
        $body = json_decode( wp_remote_retrieve_body( $return ), true );
        $row_id = isset( $body['result'][0]['id'] ) ? $body['result'][0]['id'] : '';

        if( $attachment && $row_id && $sheet_id ) {
            // split attachment by comma or new line
            $attachments = preg_split( '/[\s,]+/', $attachment );

            foreach ( $attachments as $single_attachment ) {
                adfoin_smartsheet_upload_file( $sheet_id, $row_id, $single_attachment, $cred_id );
            }
        }
    }

    return;
}

/*
 * Smartsheet API Request
 */
function adfoin_smartsheet_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

    $credentials = adfoin_smartsheet_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];
    
    if ( ! $api_token ) {
        return new WP_Error( 'no_credentials', 'No API token found' );
    }
    
    $base_url  = 'https://api.smartsheet.com/2.0/';
    $url       = $base_url . $endpoint;

    $args = array(
        'timeout' => 45,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_token
        ),
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

function adfoin_smartsheet_upload_file($sheet_id, $row_id, $file_url, $cred_id = '') {
    $payload_boundary = wp_generate_password(24);

    $file_path = adfoin_get_file_path_from_url( $file_url );
    $file_name = basename( $file_url );
    $file_type = mime_content_type( $file_path );

    $endpoint = "sheets/{$sheet_id}/rows/{$row_id}/attachments";

    $payload = adfoin_smartsheet_prepare_payload( $payload_boundary, $file_path, $file_name, $file_type );

    if ( empty( $payload ) ) {
        return false;
    }

    $payload .= '--' . $payload_boundary . '--';

    $uploadResponse = adfoin_smartsheet_file_request( $endpoint, $payload, $payload_boundary, array(), $cred_id );

    return $uploadResponse;
}

function adfoin_smartsheet_prepare_payload($payload_boundary, $file_path, $file_name, $file_type) {
    $payload = '';

    if ((is_readable($file_path) && !is_dir($file_path)) || filter_var($file_path, FILTER_VALIDATE_URL)) {
        $payload .= '--' . $payload_boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . $file_name . '"' . "\r\n";
        $payload .= 'Content-Type: ' . $file_type . "\r\n\r\n";
        $payload .= file_get_contents($file_path) . "\r\n";
    }

    return $payload;
}

function adfoin_smartsheet_file_request($endpoint, $payload, $payload_boundary, $record = array(), $cred_id = '') {
    $credentials = adfoin_smartsheet_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];
    
    if ( ! $api_token ) {
        return false;
    }
    
    $base_url = 'https://api.smartsheet.com/2.0/';
    $url = $base_url . $endpoint;

    $args = array(
        'timeout' => 60,
        'method'  => 'POST',
        'headers' => array(
            'Accept'        => 'application/json',
            'Content-Type'  => 'multipart/form-data; boundary=' . $payload_boundary,
            'Authorization' => 'Bearer ' . $api_token
        ),
        'body'    => $payload,
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}