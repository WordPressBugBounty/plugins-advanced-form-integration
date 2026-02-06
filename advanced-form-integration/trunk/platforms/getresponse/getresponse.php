<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_getresponse_actions',
    10,
    1
);
function adfoin_getresponse_actions(  $actions  ) {
    $actions['getresponse'] = array(
        'title' => __( 'GetResponse', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_getresponse_settings_tab',
    10,
    1
);
function adfoin_getresponse_settings_tab(  $providers  ) {
    $providers['getresponse'] = __( 'GetResponse', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_getresponse_settings_view',
    10,
    1
);
function adfoin_getresponse_settings_view(  $current_tab  ) {
    if ( $current_tab != 'getresponse' ) {
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
    $instructions = sprintf( '<p>%s</p>', __( 'Go to <a href="https://app.getresponse.com/api" target="_blank">GetResponse API Settings</a> to get your API Key', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'getresponse',
        __( 'GetResponse', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_getresponse_credentials',
    'adfoin_get_getresponse_credentials',
    10,
    0
);
/*
 * Get GetResponse credentials
 */
function adfoin_get_getresponse_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'getresponse' );
}

add_action(
    'wp_ajax_adfoin_save_getresponse_credentials',
    'adfoin_save_getresponse_credentials',
    10,
    0
);
/*
 * Save GetResponse credentials
 */
function adfoin_save_getresponse_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'getresponse', array('apiKey') );
}

/*
 * GetResponse Credentials List
 */
function adfoin_getresponse_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'getresponse' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_getresponse_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_getresponse_modify_credentials(  $credentials, $platform  ) {
    if ( 'getresponse' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_getresponse_api_key' );
        if ( $api_key ) {
            $credentials = array(array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_key,
            ));
        }
    }
    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action(
    'admin_post_adfoin_getresponse_save_api_key',
    'adfoin_save_getresponse_api_key',
    10,
    0
);
function adfoin_save_getresponse_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_getresponse_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST["adfoin_getresponse_api_key"] );
    // Save keys
    update_option( "adfoin_getresponse_api_key", $api_key );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=getresponse" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_getresponse_js_fields',
    10,
    1
);
function adfoin_getresponse_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_getresponse_action_fields' );
function adfoin_getresponse_action_fields() {
    ?>
    <script type="text/template" id="getresponse-action-template">
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
    esc_attr_e( 'GetResponse Account', 'advanced-form-integration' );
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
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=getresponse' );
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
    esc_attr_e( 'GetResponse List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
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
        printf( __( 'To unlock custom fields and tags consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
    'wp_ajax_adfoin_get_getresponse_list',
    'adfoin_get_getresponse_list',
    10,
    0
);
/*
 * Get GetResponse subscriber lists
 */
function adfoin_get_getresponse_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $data = adfoin_getresponse_request(
        'campaigns',
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $body = json_decode( $data["body"] );
    $lists = wp_list_pluck( $body, "name", "campaignId" );
    wp_send_json_success( $lists );
}

add_action(
    'adfoin_getresponse_job_queue',
    'adfoin_getresponse_job_queue',
    10,
    1
);
function adfoin_getresponse_job_queue(  $data  ) {
    adfoin_getresponse_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to GetResponse API
 */
function adfoin_getresponse_send_data(  $record, $posted_data  ) {
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
        $all_credentials = adfoin_read_credentials( 'getresponse' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( $task == "subscribe" ) {
        $list_id = $data["listId"];
        $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
        $name = ( empty( $data["name"] ) ? "" : adfoin_get_parsed_values( $data["name"], $posted_data ) );
        $ip = ( empty( $data["ipAddress"] ) ? "" : adfoin_get_parsed_values( $data["ipAddress"], $posted_data ) );
        $data = array(
            'email'      => $email,
            'campaign'   => array(
                'campaignId' => $list_id,
            ),
            'dayOfCycle' => 0,
        );
        if ( $name ) {
            $data['name'] = $name;
        }
        if ( $ip ) {
            $data['ipAddress'] = $ip;
        }
        $return = adfoin_getresponse_request(
            'contacts',
            'POST',
            $data,
            $record,
            $cred_id
        );
    }
    return;
}

function adfoin_getresponse_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'getresponse', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_getresponse_api_key' );
    }
    if ( !$api_key ) {
        return array();
    }
    $base_url = 'https://api.getresponse.com/v3/';
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Auth-Token' => 'api-key ' . $api_key,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }
    $args = apply_filters( 'adfoin_getresponse_before_sent', $args, $url );
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
