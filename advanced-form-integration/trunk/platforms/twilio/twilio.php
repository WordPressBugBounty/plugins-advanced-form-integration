<?php

add_filter( 'adfoin_action_providers', 'adfoin_twilio_actions', 10, 1 );

function adfoin_twilio_actions( $actions ) {

    $actions['twilio'] = array(
        'title' => __( 'Twilio', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Send SMS', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get Twilio credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'account_sid', 'auth_token' keys, or empty strings if not found
 */
function adfoin_twilio_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $account_sid = '';
    $auth_token = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'twilio' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $account_sid = $single['account_sid'];
                $auth_token = $single['auth_token'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $account_sid = get_option( 'adfoin_twilio_account_sid' ) ? get_option( 'adfoin_twilio_account_sid' ) : '';
        $auth_token = get_option( 'adfoin_twilio_auth_token' ) ? get_option( 'adfoin_twilio_auth_token' ) : '';
    }

    return array(
        'account_sid' => $account_sid,
        'auth_token' => $auth_token
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_twilio_settings_tab', 10, 1 );

function adfoin_twilio_settings_tab( $providers ) {
    $providers['twilio'] = __( 'Twilio', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_twilio_settings_view', 10, 1 );

function adfoin_twilio_settings_view( $current_tab ) {
    if( $current_tab != 'twilio' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_account_sid = get_option( 'adfoin_twilio_account_sid' );
    $old_auth_token = get_option( 'adfoin_twilio_auth_token' );
    
    $existing_creds = adfoin_read_credentials( 'twilio' );

    if ( $old_account_sid && $old_auth_token && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account',
            'account_sid' => $old_account_sid,
            'auth_token' => $old_auth_token
        );
        adfoin_save_credentials( 'twilio', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'account_sid',
            'label'         => __( 'Account SID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter Account SID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'auth_token',
            'label'         => __( 'Auth Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter Auth Token', 'advanced-form-integration' ),
            'mask'          => true,  // Mask auth token in table
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Log in to your Twilio Console.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Go to Account > Account Info section.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your Account SID and Auth Token.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the details above and click "Add Account".', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'twilio', 'Twilio', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_twilio_credentials', 'adfoin_get_twilio_credentials' );
function adfoin_get_twilio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'twilio' );
}

add_action( 'wp_ajax_adfoin_save_twilio_credentials', 'adfoin_save_twilio_credentials' );
function adfoin_save_twilio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'twilio', array( 'account_sid', 'auth_token' ) );
}

add_action( 'wp_ajax_adfoin_get_twilio_credentials_list', 'adfoin_twilio_get_credentials_list_ajax' );
function adfoin_twilio_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'account_sid', 'mask' => false ),
        array( 'name' => 'auth_token', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'twilio', $fields );
}

add_action( 'adfoin_add_js_fields', 'adfoin_twilio_js_fields', 10, 1 );

function adfoin_twilio_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_twilio_action_fields' );

function adfoin_twilio_action_fields() {
    ?>
    <script type="text/template" id="twilio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php esc_html_e( 'Twilio Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getPhoneNumbers">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=twilio' ); ?>" 
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

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'From', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[from]" v-model="fielddata.from" required="required">
                        <option value=""> <?php _e( 'Select Number...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

<!--            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">-->
<!--                <td scope="row-title">-->
<!--                    <label for="tablecell">-->
<!--                        --><?php //esc_attr_e( 'To', 'advanced-form-integration' ); ?>
<!--                    </label>-->
<!--                </td>-->
<!--                <td>-->
<!--                    <input class="regular-text" type="text" v-model="fielddata.to" required="required">-->
<!--                </td>-->
<!--            </tr>-->
<!---->
<!--            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">-->
<!--                <td scope="row-title">-->
<!--                    <label for="tablecell">-->
<!--                        --><?php //esc_attr_e( 'Body', 'advanced-form-integration' ); ?>
<!--                    </label>-->
<!--                </td>-->
<!--                <td>-->
<!--                    <textarea type="text" v-model="fielddata.body"></textarea>-->
<!--                </td>-->
<!--            </tr>-->

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>


        </table>
    </script>


    <?php
}

add_action( 'wp_ajax_adfoin_get_twilio_list', 'adfoin_get_twilio_list', 10, 0 );

/*
 * Get Twilio phone numbers
 */
function adfoin_get_twilio_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_twilio_get_credentials();
    $account_sid = $credentials['account_sid'];
    $auth_token = $credentials['auth_token'];

    if( !$account_sid || !$auth_token ) {
        wp_send_json_error();
        return;
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/IncomingPhoneNumbers.json";

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $account_sid . ':' . $auth_token )
        )
    );
    $data  = wp_remote_get( $url, $args );

    if( !is_wp_error( $data ) ) {
        $body  = json_decode( $data["body"] );

        if ( isset( $body->incoming_phone_numbers ) ) {
            $lists = wp_list_pluck( $body->incoming_phone_numbers, 'phone_number', 'phone_number' );
            wp_send_json_success( $lists );
        } else {
            wp_send_json_error();
        }
    } else {
        wp_send_json_error();
    }
}

/*
 * Saves connection mapping
 */
function adfoin_twilio_save_integration() {
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

add_action( 'adfoin_twilio_job_queue', 'adfoin_twilio_job_queue', 10, 1 );

function adfoin_twilio_job_queue( $data ) {
    adfoin_twilio_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Twilio API
 */
function adfoin_twilio_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );
    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    
    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    
    $credentials = adfoin_twilio_get_credentials( $cred_id );
    $account_sid = $credentials['account_sid'];
    $auth_token = $credentials['auth_token'];

    if(!$account_sid || !$auth_token ) {
        return;
    }

    if( array_key_exists( "cl", $record_data["action_data"]) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data["field_data"];
//    $list_id = $data["listId"];
    $task    = $record["task"];

    if( $task == "subscribe" ) {
        $from = empty( $data["from"] ) ? "" : adfoin_get_parsed_values( $data["from"], $posted_data );
        $to   = empty( $data["to"] ) ? "" : adfoin_get_parsed_values( $data["to"], $posted_data );
        $body = empty( $data["body"] ) ? "" : adfoin_get_parsed_values( $data["body"], $posted_data );

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";

        $sms_data = array(
            'From' => $from,
            'To'   => $to,
            'Body' => $body
        );

        $args = array(

            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode( $account_sid . ':' . $auth_token )
            ),
            'body' => $sms_data
        );

        $return = wp_remote_post( $url, $args );

        adfoin_add_to_log( $return, $url, $args, $record );

        return;
    }
}