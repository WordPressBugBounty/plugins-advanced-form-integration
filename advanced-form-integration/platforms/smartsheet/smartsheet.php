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

    $nonce     = wp_create_nonce( 'adfoin_smartsheet_settings' );
    $api_token = get_option( 'adfoin_smartsheet_api_token' ) ? get_option( 'adfoin_smartsheet_api_token' ) : '';
    ?>

    <form name="smartsheet_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_smartsheet_save_api_token">
        <input type="hidden" name="_nonce" value="<?php echo $nonce ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"> <?php _e( 'API Token', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_smartsheet_api_token"
                           value="<?php echo esc_attr( $api_token ); ?>" placeholder="<?php _e( 'Please enter API Token', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description" id="code-description"><?php _e( 'Go to Account > Personal Settings > API Access and generate a new token', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
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

    $data = adfoin_smartsheet_request( 'sheets?pageSize=1000' );

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

    $sheet_id  = isset( $_REQUEST['listId'] ) ? $_REQUEST['listId'] : '';
    $data = adfoin_smartsheet_request( "sheets/{$sheet_id}" );

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

    if( $task == 'add_row' ) {
        $attachment = isset( $data['attachment'] ) ? adfoin_get_parsed_values( $data['attachment'], $posted_data ) : '';

        unset( $data['listId'] );
        unset( $data['list'] );
        unset( $data['attachment'] );

        $holder = array();

        foreach ( $data as $key => $value ) {
            if( $value ) {
                $parsed_value = adfoin_get_parsed_values( $value, $posted_data );

                if( $parsed_value ) {
                    array_push( $holder, array( 'columnId' => $key, 'objectValue' => $parsed_value ) );
                }
            }
            
        }

        $to_be_sent = array( 'toTop' => 'true', 'cells' => $holder );
        $return = adfoin_smartsheet_request( "sheets/{$sheet_id}/rows", 'POST', array( $to_be_sent ), $record );
        $body = json_decode( wp_remote_retrieve_body( $return ), true );
        $row_id = isset( $body['result'][0]['id'] ) ? $body['result'][0]['id'] : '';

        if( $attachment && $row_id && $sheet_id ) {
            // split attachment by comma or new line
            $attachments = preg_split( '/[\s,]+/', $attachment );

            foreach ( $attachments as $single_attachment ) {
                adfoin_smartsheet_upload_file( $sheet_id, $row_id, $single_attachment );
            }
        }
    }

    return;
}

/*
 * Smartsheet API Request
 */
function adfoin_smartsheet_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

    $api_token = $api_token = get_option( 'adfoin_smartsheet_api_token' ) ? get_option( 'adfoin_smartsheet_api_token' ) : '';
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

function adfoin_smartsheet_upload_file($sheet_id, $row_id, $file_url) {
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

    $uploadResponse = adfoin_smartsheet_file_request( $endpoint, $payload, $payload_boundary );

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

function adfoin_smartsheet_file_request($endpoint, $payload, $payload_boundary, $record = array()) {
    $api_token = $api_token = get_option( 'adfoin_smartsheet_api_token' ) ? get_option( 'adfoin_smartsheet_api_token' ) : '';
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