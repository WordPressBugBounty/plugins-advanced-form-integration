<?php

add_filter( 'adfoin_action_providers', 'adfoin_trello_actions', 10, 1 );

function adfoin_trello_actions( $actions ) {

    $actions['trello'] = array(
        'title' => __( 'Trello', 'advanced-form-integration' ),
        'tasks' => array(
            'add_card'   => __( 'Add New Card', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

/**
 * Get Trello credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_token' key, or empty string if not found
 */
function adfoin_trello_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_token = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'trello' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_token = $single['api_token'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_token = get_option( 'adfoin_trello_api_token' ) ? get_option( 'adfoin_trello_api_token' ) : '';
    }

    return array(
        'api_token' => $api_token
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_trello_settings_tab', 10, 1 );

function adfoin_trello_settings_tab( $providers ) {
    $providers['trello'] = __( 'Trello', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_trello_settings_view', 10, 1 );

function adfoin_trello_settings_view( $current_tab ) {
    if( $current_tab != 'trello' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_trello_api_token' );
    
    $existing_creds = adfoin_read_credentials( 'trello' );

    if ( $old_api_token && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account',
            'api_token' => $old_api_token
        );
        adfoin_save_credentials( 'trello', array( $new_cred ) );
    }

    $api_key = adfoin_trello_get_api_key();
    $auth_url = "https://trello.com/1/authorize?expiration=never&name=Advanced%20Form%20Integration&scope=read%2Cwrite%2Caccount&response_type=token&key={$api_key}";

    $fields = array(
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Token', 'advanced-form-integration' ),
            'description'   => '<a href="' . esc_url( $auth_url ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Click here to get token', 'advanced-form-integration' ) . '</a>',
            'mask'          => true,  // Mask API token in table
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Click the "Click here to get token" link above to authorize the application.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy the token from the authorization page.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your API Token above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'trello', 'Trello', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_trello_credentials', 'adfoin_get_trello_credentials' );
function adfoin_get_trello_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'trello' );
}

add_action( 'wp_ajax_adfoin_save_trello_credentials', 'adfoin_save_trello_credentials' );
function adfoin_save_trello_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'trello', array( 'api_token' ) );
}

add_action( 'wp_ajax_adfoin_get_trello_credentials_list', 'adfoin_trello_get_credentials_list_ajax' );
function adfoin_trello_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_token', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'trello', $fields );
}

add_action( 'adfoin_action_fields', 'adfoin_trello_action_fields' );

function adfoin_trello_action_fields() {
    ?>
    <script type="text/template" id="trello-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_card'">
                <th scope="row"><?php esc_html_e( 'Trello Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getBoards">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=trello' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_card'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_card'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Select Trello Board', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[boardId]" v-model="fielddata.boardId" required="required" @change="getLists">
                        <option value=""> <?php _e( 'Select Board...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.boards" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': boardLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_card'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Select Trello List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.lists" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

        </table>
    </script>
    <?php
}

function adfoin_trello_get_api_key() {
    return '13b9118a04aaece3faae1eda7e424edc';
}

add_action( 'wp_ajax_adfoin_get_trello_boards', 'adfoin_get_trello_boards', 10, 0 );
/*
 * Get Trello boards
 */
function adfoin_get_trello_boards() {
    
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = adfoin_trello_get_api_key();
    $credentials = adfoin_trello_get_credentials();
    $api_token = $credentials['api_token'];

    if( !$api_key || !$api_token ) {
        wp_send_json_error();
        return;
    }

    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json'
        )
    );

    $url = "https://api.trello.com/1/members/me/boards?&filter=open&key={$api_key}&token={$api_token}";

    $result = wp_remote_get( $url, $args);

    if( is_wp_error( $result ) || '200' != $result['response']['code'] ) {
        wp_send_json_error();
    }

    $body = json_decode( wp_remote_retrieve_body( $result ) );
    
    if ( $body && is_array( $body ) ) {
        $boards = wp_list_pluck( $body, 'name', 'id' );
        wp_send_json_success( $boards );
    } else {
        wp_send_json_error();
    }
}

add_action( 'wp_ajax_adfoin_get_trello_lists', 'adfoin_get_trello_lists', 10, 0 );

function adfoin_get_trello_lists() {
    
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = adfoin_trello_get_api_key();
    $credentials = adfoin_trello_get_credentials();
    $api_token = $credentials['api_token'];
    $board_id = isset( $_POST['boardId'] ) ? sanitize_text_field( $_POST['boardId'] ) : '';

    if( !$api_key || !$api_token || !$board_id ) {
        wp_send_json_error();
        return;
    }

    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json'
        )
    );

    $url = "https://api.trello.com/1/boards/{$board_id}/lists?filter=open&key={$api_key}&token={$api_token}";

    $result = wp_remote_get( $url, $args);

    if( is_wp_error( $result ) || '200' != $result['response']['code'] ) {
        wp_send_json_error();
    }
    
    $body = json_decode( wp_remote_retrieve_body( $result ) );
    
    if ( $body && is_array( $body ) ) {
        $lists = wp_list_pluck( $body, 'name', 'id' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

/*
 * Saves connection mapping
 */
function adfoin_trello_save_integration() {
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

add_action( 'adfoin_trello_job_queue', 'adfoin_trello_job_queue', 10, 1 );

function adfoin_trello_job_queue( $data ) {
    adfoin_trello_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Trello API
 */
function adfoin_trello_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );
    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    
    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    
    $api_key = adfoin_trello_get_api_key();
    $credentials = adfoin_trello_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];

    if( !$api_key || !$api_token ) {
        return;
    }

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data["field_data"];
    $task    = $record["task"];

    if( $task == "add_card" ) {

        $board_id    = $data["boardId"];
        $list_id     = $data["listId"];
        $name        = empty( $data["name"] ) ? "" : adfoin_get_parsed_values( $data["name"], $posted_data );
        $description = empty( $data["description"] ) ? "" : adfoin_get_parsed_values( $data["description"], $posted_data );
        $url         = "https://api.trello.com/1/cards?key={$api_key}&token={$api_token}&idList={$list_id}";
        $pos         = empty( $data["pos"] ) ? "" : adfoin_get_parsed_values( $data["pos"], $posted_data );

        $body = array(
            'name' => $name,
            'desc' => $description,
            'pos'  => $pos
        );

        $args = array(

            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode( $body )
        );

        $return = wp_remote_post( $url, $args );

        adfoin_add_to_log( $return, $url, $args, $record );
    }

    return;
}