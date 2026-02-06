<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_elasticemail_actions',
    10,
    1
);
function adfoin_elasticemail_actions(  $actions  ) {
    $actions['elasticemail'] = array(
        'title' => __( 'Elastic Email', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Contact To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_elasticemail_settings_tab',
    10,
    1
);
function adfoin_elasticemail_settings_tab(  $providers  ) {
    $providers['elasticemail'] = __( 'Elastic Email', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_elasticemail_settings_view',
    10,
    1
);
function adfoin_elasticemail_settings_view(  $current_tab  ) {
    if ( $current_tab != 'elasticemail' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'Elastic Email API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf( '<p>%s</p>', __( 'Please go to Settings > API then create API Key', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'elasticemail',
        __( 'Elastic Email', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_elasticemail_credentials',
    'adfoin_get_elasticemail_credentials',
    10,
    0
);
/*
 * Get Elastic Email credentials
 */
function adfoin_get_elasticemail_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'elasticemail' );
}

add_action(
    'wp_ajax_adfoin_save_elasticemail_credentials',
    'adfoin_save_elasticemail_credentials',
    10,
    0
);
/*
 * Save Elastic Email credentials
 */
function adfoin_save_elasticemail_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = ( isset( $_POST['apiKey'] ) ? sanitize_text_field( $_POST['apiKey'] ) : '' );
    if ( !$api_key ) {
        wp_send_json_error( array(
            'message' => __( 'API Key is required', 'advanced-form-integration' ),
        ) );
    }
    // Fetch publicAccountId from API
    $url = "https://api.elasticemail.com/v2/account/load?apikey={$api_key}";
    $args = array(
        'headers' => array(
            'Accept' => '*/*',
        ),
    );
    $data = wp_remote_request( $url, $args );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error( array(
            'message' => __( 'Failed to verify API Key', 'advanced-form-integration' ),
        ) );
    }
    $body = json_decode( $data["body"] );
    if ( !isset( $body->data->publicaccountid ) ) {
        wp_send_json_error( array(
            'message' => __( 'Invalid API Key', 'advanced-form-integration' ),
        ) );
    }
    $public_account_id = $body->data->publicaccountid;
    // Store both apiKey and publicAccountId
    $_POST['publicAccountId'] = $public_account_id;
    ADFOIN_Account_Manager::ajax_save_credentials( 'elasticemail', array('apiKey', 'publicAccountId') );
}

/*
 * Elastic Email Credentials List
 */
function adfoin_elasticemail_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'elasticemail' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_elasticemail_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_elasticemail_modify_credentials(  $credentials, $platform  ) {
    if ( 'elasticemail' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_elasticemail_api_key' );
        $public_account_id = get_option( 'adfoin_elasticemail_public_accountid' );
        if ( $api_key && $public_account_id ) {
            $credentials = array(array(
                'id'              => 'legacy',
                'title'           => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey'          => $api_key,
                'publicAccountId' => $public_account_id,
            ));
        }
    }
    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action(
    'admin_post_adfoin_save_elasticemail_api_key',
    'adfoin_save_elasticemail_api_key',
    10,
    0
);
function adfoin_save_elasticemail_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_elasticemail_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST["adfoin_elasticemail_api_key"] );
    // Save tokens
    update_option( "adfoin_elasticemail_api_key", $api_key );
    $url = "https://api.elasticemail.com/v2/account/load?apikey={$api_key}";
    $args = array(
        'headers' => array(
            'Accept' => '*/*',
        ),
    );
    $data = wp_remote_request( $url, $args );
    if ( !is_wp_error( $data ) ) {
        $body = json_decode( $data["body"] );
        $public_account_id = $body->data->publicaccountid;
        update_option( "adfoin_elasticemail_public_accountid", $public_account_id );
    }
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=elasticemail" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_elasticemail_js_fields',
    10,
    1
);
function adfoin_elasticemail_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_elasticemail_action_fields' );
function adfoin_elasticemail_action_fields() {
    ?>
    <script type="text/template" id="elasticemail-action-template">
        <table class="form-table">
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
    esc_attr_e( 'Elastic Email Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=elasticemail' );
    ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
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

add_action(
    'wp_ajax_adfoin_get_elasticemail_list',
    'adfoin_get_elasticemail_list',
    10,
    0
);
/*
 * Get Elastic Email subscriber lists
 */
function adfoin_get_elasticemail_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $credentials = adfoin_get_credentials_by_id( 'elasticemail', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = get_option( "adfoin_elasticemail_api_key" );
    }
    if ( !$api_key ) {
        wp_send_json_error();
    }
    $url = "https://api.elasticemail.com/v2/list/list?apikey={$api_key}";
    $args = array(
        'headers' => array(
            'Accept' => '*/*',
        ),
    );
    $data = wp_remote_request( $url, $args );
    if ( !is_wp_error( $data ) ) {
        $body = json_decode( $data["body"] );
        $lists = wp_list_pluck( $body->data, 'listname', 'publiclistid' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_elasticemail_job_queue',
    'adfoin_elasticemail_job_queue',
    10,
    1
);
function adfoin_elasticemail_job_queue(  $data  ) {
    adfoin_elasticemail_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Elastic Email API
 */
function adfoin_elasticemail_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    if ( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if ( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if ( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data["field_data"];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $list_id = $data["listId"];
    $task = $record["task"];
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'elasticemail' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    $credentials = adfoin_get_credentials_by_id( 'elasticemail', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $public_acc = ( isset( $credentials['publicAccountId'] ) ? $credentials['publicAccountId'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = ( get_option( 'adfoin_elasticemail_api_key' ) ? get_option( 'adfoin_elasticemail_api_key' ) : "" );
    }
    if ( empty( $public_acc ) ) {
        $public_acc = ( get_option( 'adfoin_elasticemail_public_accountid' ) ? get_option( 'adfoin_elasticemail_public_accountid' ) : "" );
    }
    if ( !$api_key || !$public_acc ) {
        return;
    }
    if ( $task == "subscribe" ) {
        $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
        $first_name = ( empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data ) );
        $last_name = ( empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data ) );
        $data = array(
            'publiclistid'    => $list_id,
            'firstName'       => $first_name,
            'lastName'        => $last_name,
            'email'           => $email,
            'publicAccountID' => $public_acc,
        );
        $url = "https://api.elasticemail.com/v2/contact/add?apikey={$api_key}";
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
}
