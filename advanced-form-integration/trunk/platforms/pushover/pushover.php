<?php

add_filter( 'adfoin_action_providers', 'adfoin_pushover_actions', 10, 1 );

function adfoin_pushover_actions( $actions ) {

    $actions['pushover'] = array(
        'title' => __( 'Pushover', 'advanced-form-integration' ),
        'tasks' => array(
            'push'   => __( 'Send Push Message', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get Pushover credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'user_key' and 'api_token' keys, or empty strings if not found
 */
function adfoin_pushover_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $user_key = '';
    $api_token = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'pushover' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $user_key = $single['user_key'];
                $api_token = $single['api_token'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $user_key = get_option( 'adfoin_pushover_user_key' ) ? get_option( 'adfoin_pushover_user_key' ) : '';
        $api_token = get_option( 'adfoin_pushover_api_token' ) ? get_option( 'adfoin_pushover_api_token' ) : '';
    }

    return array(
        'user_key' => $user_key,
        'api_token' => $api_token
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_pushover_settings_tab', 10, 1 );

function adfoin_pushover_settings_tab( $providers ) {
    $providers['pushover'] = __( 'Pushover', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_pushover_settings_view', 10, 1 );

function adfoin_pushover_settings_view( $current_tab ) {
    if( $current_tab != 'pushover' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_user_key = get_option( 'adfoin_pushover_user_key' ) ? get_option( 'adfoin_pushover_user_key' ) : '';
    $old_api_token = get_option( 'adfoin_pushover_api_token' ) ? get_option( 'adfoin_pushover_api_token' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'pushover' );

    if ( ( $old_user_key || $old_api_token ) && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'user_key' => $old_user_key,
            'api_token' => $old_api_token
        );
        adfoin_save_credentials( 'pushover', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'user_key',
            'label'         => __( 'User Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter User Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Token', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Login to your Pushover account to get User Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Go to https://pushover.net/apps and create a New Application to get API Token.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter both User Key and API Token in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'pushover', 'Pushover', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_pushover_credentials', 'adfoin_get_pushover_credentials' );
function adfoin_get_pushover_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'pushover' );
}

add_action( 'wp_ajax_adfoin_save_pushover_credentials', 'adfoin_save_pushover_credentials' );
function adfoin_save_pushover_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'pushover', array( 'user_key', 'api_token' ) );
}

add_action( 'wp_ajax_adfoin_get_pushover_credentials_list', 'adfoin_pushover_get_credentials_list_ajax' );
function adfoin_pushover_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'user_key', 'mask' => true ),
        array( 'name' => 'api_token', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'pushover', $fields );
}

add_action( 'admin_post_adfoin_save_pushover_api_key', 'adfoin_save_pushover_api_key', 10, 0 );

function adfoin_save_pushover_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_pushover_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $user_key  = sanitize_text_field( $_POST["adfoin_pushover_user_key"] );
    $api_token = sanitize_text_field( $_POST["adfoin_pushover_api_token"] );

    // Save tokens
    update_option( "adfoin_pushover_user_key", $user_key );
    update_option( "adfoin_pushover_api_token", $api_token );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=pushover" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_pushover_js_fields', 10, 1 );

function adfoin_pushover_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_pushover_action_fields' );

function adfoin_pushover_action_fields() {
    ?>
    <script type="text/template" id="pushover-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'push'">
                <td class="afi-label" scope="row"><?php esc_html_e( 'Pushover Account', 'advanced-form-integration' ); ?></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=pushover' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

        </table>
    </script>


    <?php
}

add_action( 'wp_ajax_adfoin_get_pushover_list', 'adfoin_get_pushover_list', 10, 0 );

/*
 * Saves connection mapping
 */
function adfoin_pushover_save_integration() {
    $params = array();
    parse_str( adfoin_sanitize_text_or_array_field( $_POST['formData'] ), $params );

    $trigger_data = isset( $_POST["triggerData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["triggerData"] ) : array();
    $action_data  = isset( $_POST["actionData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["actionData"] ) : array();
    $field_data   = isset( $_POST["fieldData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["fieldData"] ) : array();

    $integration_title = isset( $trigger_data["integrationTitle"] ) ? $trigger_data["integrationTitle"] : "";
    $form_provider_id  = isset( $trigger_data["formProviderId"] ) ? $trigger_data["formProviderId"] : "";
    $form_id           = isset( $trigger_data["formId"] ) ? $trigger_data["formId"] : "";
    $form_name         = isset( $trigger_data["formName"] ) ? $trigger_data["formName"] : "";
    $action_provider   = isset( $action_data["actionProviderId"] ) ? $action_data["actionProviderId"] : "";
    $task              = isset( $action_data["task"] ) ? $action_data["task"] : "";
    $type              = isset( $params["type"] ) ? $params["type"] : "";

    $all_data = array(
        'trigger_data' => $trigger_data,
        'action_data'  => $action_data,
        'field_data'   => $field_data
    );

    global $wpdb;

    $integration_table = $wpdb->prefix . 'adfoin_integration';

    if ( $type == 'new_integration' ) {

        $result = $wpdb->insert(
            $integration_table,
            array(
                'title'           => $integration_title,
                'form_provider'   => $form_provider_id,
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'action_provider' => $action_provider,
                'task'            => $task,
                'data'            => json_encode( $all_data, true ),
                'status'          => 1
            )
        );
    }

    if ( $type == 'update_integration' ) {

        $id = esc_sql( trim( $params['edit_id'] ) );

        if ( $type != 'update_integration' &&  !empty( $id ) ) {
            return;
        }

        $result = $wpdb->update( $integration_table,
            array(
                'title'           => $integration_title,
                'form_provider'   => $form_provider_id,
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'data'            => json_encode( $all_data, true ),
            ),
            array(
                'id' => $id
            )
        );
    }

    if ( $result ) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_pushover_job_queue', 'adfoin_pushover_job_queue', 10, 1 );

function adfoin_pushover_job_queue( $data ) {
    adfoin_pushover_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Pushover API
 */
function adfoin_pushover_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"]) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data["field_data"];
    $task    = $record["task"];
    
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $credentials = adfoin_pushover_get_credentials( $cred_id );
    $user_key = $credentials['user_key'];
    $api_token = $credentials['api_token'];

    if( !$user_key || !$api_token ) {
        return;
    }

    $title   = empty( $data["title"] ) ? "" : adfoin_get_parsed_values($data["title"], $posted_data );
    $message = empty( $data["message"] ) ? "" : adfoin_get_parsed_values($data["message"], $posted_data );
    $device  = empty( $data["device"] ) ? "" : adfoin_get_parsed_values($data["device"], $posted_data );

    if( $task == "push" ) {

        $request_data = array(
            "user"    => $user_key,
            "token"   => $api_token,
            "title"   => $title,
            "message" => $message,
            "device"  => $device
        );

//        $query = http_build_query( $request_data );
        $url   = "https://api.pushover.net/1/messages.json";

        $args = array(

            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $request_data
        );

        $return = wp_remote_post( $url, $args );

        adfoin_add_to_log( $return, $url, $args, $record );

        return;
    }
}