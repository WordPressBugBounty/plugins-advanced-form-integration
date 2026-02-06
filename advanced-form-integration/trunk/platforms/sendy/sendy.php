<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_sendy_actions',
    10,
    1
);
function adfoin_sendy_actions(  $actions  ) {
    $actions['sendy'] = array(
        'title' => __( 'Sendy', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Sendy credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key', 'url' keys, or empty strings if not found
 */
function adfoin_sendy_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $api_key = '';
    $url = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'sendy' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                $url = $single['url'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = ( get_option( 'adfoin_sendy_api_key' ) ? get_option( 'adfoin_sendy_api_key' ) : '' );
        $url = ( get_option( 'adfoin_sendy_url' ) ? get_option( 'adfoin_sendy_url' ) : '' );
    }
    return array(
        'api_key' => $api_key,
        'url'     => $url,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_sendy_settings_tab',
    10,
    1
);
function adfoin_sendy_settings_tab(  $providers  ) {
    $providers['sendy'] = __( 'Sendy', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_sendy_settings_view',
    10,
    1
);
function adfoin_sendy_settings_view(  $current_tab  ) {
    if ( $current_tab != 'sendy' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = ( get_option( 'adfoin_sendy_api_key' ) ? get_option( 'adfoin_sendy_api_key' ) : '' );
    $old_url = ( get_option( 'adfoin_sendy_url' ) ? get_option( 'adfoin_sendy_url' ) : '' );
    $existing_creds = adfoin_read_credentials( 'sendy' );
    if ( $old_api_key && $old_url && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'      => uniqid(),
            'title'   => 'Default Account (Legacy)',
            'api_key' => $old_api_key,
            'url'     => $old_url,
        );
        adfoin_save_credentials( 'sendy', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'url',
        'label'         => __( 'Installation URL', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Please enter Sendy Installation URL', 'advanced-form-integration' ),
        'show_in_table' => true,
    ), array(
        'name'          => 'api_key',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Please enter API Key', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Enter your Sendy installation URL (e.g., https://yourdomain.com/sendy).', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Go to your Sendy settings and copy your API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the credentials in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'sendy',
        'Sendy',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendy_credentials', 'adfoin_get_sendy_credentials' );
function adfoin_get_sendy_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendy' );
}

add_action( 'wp_ajax_adfoin_save_sendy_credentials', 'adfoin_save_sendy_credentials' );
function adfoin_save_sendy_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendy', array('url', 'api_key') );
}

add_action( 'wp_ajax_adfoin_get_sendy_credentials_list', 'adfoin_sendy_get_credentials_list_ajax' );
function adfoin_sendy_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'url',
        'mask' => false,
    ), array(
        'name' => 'api_key',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendy', $fields );
}

add_action(
    'admin_post_adfoin_save_sendy_api_key',
    'adfoin_save_sendy_api_key',
    10,
    0
);
function adfoin_save_sendy_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_sendy_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST["adfoin_sendy_api_key"] );
    $url = sanitize_text_field( $_POST["adfoin_sendy_url"] );
    // Save tokens
    update_option( "adfoin_sendy_api_key", $api_key );
    update_option( "adfoin_sendy_url", $url );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=sendy" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_sendy_js_fields',
    10,
    1
);
function adfoin_sendy_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_sendy_action_fields' );
function adfoin_sendy_action_fields() {
    ?>
    <script type="text/template" id="sendy-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php 
    esc_html_e( 'Sendy Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendy' );
    ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Sendy List ID', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input  name="fieldData[listId]" type="text" v-model="fielddata.listId"  required="required">

                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'subscribe'">
                        <th scope="row">
                            <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                        </th>
                        <td scope="row">
                            <span><?php 
        printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                        </td>
                    </tr>
                    <?php 
    }
    ?>
            
        </table>
    </script>
    <?php 
}

/*
 * Saves connection mapping
 */
function adfoin_sendy_save_integration() {
    $params = array();
    parse_str( adfoin_sanitize_text_or_array_field( $_POST['formData'] ), $params );
    $trigger_data = ( isset( $_POST["triggerData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["triggerData"] ) : array() );
    $action_data = ( isset( $_POST["actionData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["actionData"] ) : array() );
    $field_data = ( isset( $_POST["fieldData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["fieldData"] ) : array() );
    $integration_title = ( isset( $trigger_data["integrationTitle"] ) ? $trigger_data["integrationTitle"] : "" );
    $form_provider_id = ( isset( $trigger_data["formProviderId"] ) ? $trigger_data["formProviderId"] : "" );
    $form_id = ( isset( $trigger_data["formId"] ) ? $trigger_data["formId"] : "" );
    $form_name = ( isset( $trigger_data["formName"] ) ? $trigger_data["formName"] : "" );
    $action_provider = ( isset( $action_data["actionProviderId"] ) ? $action_data["actionProviderId"] : "" );
    $task = ( isset( $action_data["task"] ) ? $action_data["task"] : "" );
    $type = ( isset( $params["type"] ) ? $params["type"] : "" );
    $all_data = array(
        'trigger_data' => $trigger_data,
        'action_data'  => $action_data,
        'field_data'   => $field_data,
    );
    global $wpdb;
    $integration_table = $wpdb->prefix . 'adfoin_integration';
    if ( $type == 'new_integration' ) {
        $result = $wpdb->insert( $integration_table, array(
            'title'           => $integration_title,
            'form_provider'   => $form_provider_id,
            'form_id'         => $form_id,
            'form_name'       => $form_name,
            'action_provider' => $action_provider,
            'task'            => $task,
            'data'            => json_encode( $all_data, true ),
            'status'          => 1,
        ) );
    }
    if ( $type == 'update_integration' ) {
        $id = esc_sql( trim( $params['edit_id'] ) );
        if ( $type != 'update_integration' && !empty( $id ) ) {
            return;
        }
        $result = $wpdb->update( $integration_table, array(
            'title'         => $integration_title,
            'form_provider' => $form_provider_id,
            'form_id'       => $form_id,
            'form_name'     => $form_name,
            'data'          => json_encode( $all_data, true ),
        ), array(
            'id' => $id,
        ) );
    }
    if ( $result ) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_sendy_job_queue',
    'adfoin_sendy_job_queue',
    10,
    1
);
function adfoin_sendy_job_queue(  $data  ) {
    adfoin_sendy_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Sendy API
 */
function adfoin_sendy_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    if ( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if ( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if ( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data["field_data"];
    $task = $record["task"];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $credentials = adfoin_sendy_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];
    $ins_url = $credentials['url'];
    if ( !$api_key || !$ins_url ) {
        return;
    }
    if ( $task == "subscribe" ) {
        $list_id = $data["listId"];
        $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
        $name = ( empty( $data["name"] ) ? "" : adfoin_get_parsed_values( $data["name"], $posted_data ) );
        $data = array(
            'api_key' => $api_key,
            'list'    => $list_id,
            'name'    => $name,
            'email'   => $email,
        );
        $url = $ins_url . "/subscribe";
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body'    => $data,
        );
        $return = wp_remote_post( $url, $args );
        adfoin_add_to_log(
            $return,
            $url,
            $args,
            $record
        );
    }
    return;
}
