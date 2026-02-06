<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_campaignmonitor_actions',
    10,
    1
);
function adfoin_campaignmonitor_actions(  $actions  ) {
    $actions['campaignmonitor'] = array(
        'title' => __( 'Campaign Monitor', 'advanced-form-integration' ),
        'tasks' => array(
            'create_subscriber' => __( 'Subscribe to List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_campaignmonitor_settings_tab',
    10,
    1
);
function adfoin_campaignmonitor_settings_tab(  $providers  ) {
    $providers['campaignmonitor'] = __( 'Campaign Monitor', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_campaignmonitor_settings_view',
    10,
    1
);
function adfoin_campaignmonitor_settings_view(  $current_tab  ) {
    if ( $current_tab != 'campaignmonitor' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf( '<p>%s</p>', __( 'Go to Account Settings > API Keys', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'campaignmonitor',
        __( 'Campaign Monitor', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_campaignmonitor_credentials',
    'adfoin_get_campaignmonitor_credentials',
    10,
    0
);
/*
 * Get Campaign Monitor credentials
 */
function adfoin_get_campaignmonitor_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'campaignmonitor' );
}

add_action(
    'wp_ajax_adfoin_save_campaignmonitor_credentials',
    'adfoin_save_campaignmonitor_credentials',
    10,
    0
);
/*
 * Save Campaign Monitor credentials
 */
function adfoin_save_campaignmonitor_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'campaignmonitor', array('apiKey') );
}

/*
 * Campaign Monitor Credentials List
 */
function adfoin_campaignmonitor_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'campaignmonitor' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_campaignmonitor_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_campaignmonitor_modify_credentials(  $credentials, $platform  ) {
    if ( 'campaignmonitor' == $platform && empty( $credentials ) ) {
        $api_token = get_option( 'adfoin_campaignmonitor_api_token' );
        if ( $api_token ) {
            $credentials = array(array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_token,
            ));
        }
    }
    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action(
    'admin_post_adfoin_save_campaignmonitor_api_token',
    'adfoin_save_campaignmonitor_api_token',
    10,
    0
);
function adfoin_save_campaignmonitor_api_token() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_campaignmonitor_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_token = sanitize_text_field( $_POST["adfoin_campaignmonitor_api_token"] );
    // Save tokens
    update_option( "adfoin_campaignmonitor_api_token", $api_token );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=campaignmonitor" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_campaignmonitor_js_fields',
    10,
    1
);
function adfoin_campaignmonitor_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_campaignmonitor_action_fields' );
function adfoin_campaignmonitor_action_fields() {
    ?>
    <script type="text/template" id="campaignmonitor-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_subscriber'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Subscriber Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'create_subscriber'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Campaign Monitor Account', 'advanced-form-integration' );
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
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=campaignmonitor' );
    ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_subscriber'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Client', 'advanced-form-integration' );
    ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[accountId]" v-model="fielddata.accountId" required="true" @change="getList">
                        <option value=""><?php 
    _e( 'Select...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.accounts" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': accountLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_subscriber'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="true">
                        <option value=""><?php 
    _e( 'Select...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'create_subscriber'">
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
    'wp_ajax_adfoin_get_campaignmonitor_accounts',
    'adfoin_get_campaignmonitor_accounts',
    10,
    0
);
/*
 * Get Campaign Monitor accounts
 */
function adfoin_get_campaignmonitor_accounts() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $credentials = adfoin_get_credentials_by_id( 'campaignmonitor', $cred_id );
    $api_token = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old option if credentials not found
    if ( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_campaignmonitor_api_token' );
    }
    if ( !$api_token ) {
        return array();
    }
    $url = "https://api.createsend.com/api/v3.2/clients.json";
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_token . ':x' ),
        ),
    );
    $accounts = wp_remote_get( $url, $args );
    if ( !is_wp_error( $accounts ) ) {
        $body = json_decode( $accounts["body"] );
        $lists = wp_list_pluck( $body, 'Name', 'ClientID' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'wp_ajax_adfoin_get_campaignmonitor_list',
    'adfoin_get_campaignmonitor_list',
    10,
    0
);
/*
 * Get Campaign Monitor lists
 */
function adfoin_get_campaignmonitor_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $credentials = adfoin_get_credentials_by_id( 'campaignmonitor', $cred_id );
    $api_token = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old option if credentials not found
    if ( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_campaignmonitor_api_token' );
    }
    if ( !$api_token ) {
        wp_send_json_error();
    }
    $client = ( $_POST['accountId'] ? sanitize_text_field( $_POST['accountId'] ) : "" );
    if ( !$client ) {
        wp_send_json_error();
    }
    $url = "https://api.createsend.com/api/v3.2/clients/{$client}/lists.json";
    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
        ),
    );
    $accounts = wp_remote_get( $url, $args );
    if ( !is_wp_error( $accounts ) ) {
        $body = json_decode( $accounts["body"] );
        $lists = wp_list_pluck( $body, 'Name', 'ListID' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_campaignmonitor_job_queue',
    'adfoin_campaignmonitor_job_queue',
    10,
    1
);
function adfoin_campaignmonitor_job_queue(  $data  ) {
    adfoin_campaignmonitor_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Campaign Monitor API
 */
function adfoin_campaignmonitor_send_data(  $record, $posted_data  ) {
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
    $task = $record["task"];
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'campaignmonitor' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    $credentials = adfoin_get_credentials_by_id( 'campaignmonitor', $cred_id );
    $api_token = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old option if credentials not found
    if ( empty( $api_token ) ) {
        $api_token = ( get_option( 'adfoin_campaignmonitor_api_token' ) ? get_option( 'adfoin_campaignmonitor_api_token' ) : '' );
    }
    if ( !$api_token ) {
        return;
    }
    $account = ( empty( $data["accountId"] ) ? "" : $data["accountId"] );
    $list = ( empty( $data["listId"] ) ? "" : $data["listId"] );
    $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
    $name = ( empty( $data["name"] ) ? "" : adfoin_get_parsed_values( $data["name"], $posted_data ) );
    if ( $task == "create_subscriber" ) {
        $url = "https://api.createsend.com/api/v3.2/subscribers/{$list}.json";
        $body = array(
            "EmailAddress"                           => $email,
            "Name"                                   => $name,
            "ConsentToTrack"                         => "Yes",
            "Resubscribe"                            => true,
            "RestartSubscriptionBasedAutoresponders" => true,
        );
        $args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
            ),
            'body'    => json_encode( $body ),
        );
        $response = wp_remote_post( $url, $args );
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return;
}

/*
* Request to Campaign Monitor API
*/
function adfoin_campaignmonitor_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'campaignmonitor', $cred_id );
    $api_token = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old option if credentials not found
    if ( empty( $api_token ) ) {
        $api_token = ( get_option( 'adfoin_campaignmonitor_api_token' ) ? get_option( 'adfoin_campaignmonitor_api_token' ) : '' );
    }
    $base_url = 'https://api.createsend.com/api/v3.3/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
    $response = wp_remote_request( $url, $args );
    if ( $record ) {
        adfoin_add_to_log(
            $response,
            $url,
            $args,
            $record
        );
    }
    return $response;
}
