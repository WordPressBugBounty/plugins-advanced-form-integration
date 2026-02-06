<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_selzy_actions',
    10,
    1
);
function adfoin_selzy_actions(  $actions  ) {
    $actions['selzy'] = array(
        'title' => __( 'Selzy', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Contact To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Selzy credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key' key, or empty string if not found
 */
function adfoin_selzy_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $api_key = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'selzy' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = ( get_option( 'adfoin_selzy_api_key' ) ? get_option( 'adfoin_selzy_api_key' ) : '' );
    }
    return array(
        'api_key' => $api_key,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_selzy_settings_tab',
    10,
    1
);
function adfoin_selzy_settings_tab(  $providers  ) {
    $providers['selzy'] = __( 'Selzy', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_selzy_settings_view',
    10,
    1
);
function adfoin_selzy_settings_view(  $current_tab  ) {
    if ( $current_tab != 'selzy' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = get_option( 'adfoin_selzy_api_key' );
    $existing_creds = adfoin_read_credentials( 'selzy' );
    if ( $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'      => uniqid(),
            'title'   => 'Default Account',
            'api_key' => $old_api_key,
        );
        adfoin_save_credentials( 'selzy', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'api_key',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Please enter API Key', 'advanced-form-integration' ),
        'description'   => __( 'Go to Profile > Settings > Integrations & API. Click on show full and copy the API Key', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Selzy account Profile > Settings > Integrations & API.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click on show full and copy the API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your API Key above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'selzy',
        'Selzy',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_selzy_credentials', 'adfoin_get_selzy_credentials' );
function adfoin_get_selzy_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'selzy' );
}

add_action( 'wp_ajax_adfoin_save_selzy_credentials', 'adfoin_save_selzy_credentials' );
function adfoin_save_selzy_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'selzy', array('api_key') );
}

add_action( 'wp_ajax_adfoin_get_selzy_credentials_list', 'adfoin_selzy_get_credentials_list_ajax' );
function adfoin_selzy_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'api_key',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'selzy', $fields );
}

add_action( 'adfoin_action_fields', 'adfoin_selzy_action_fields' );
function adfoin_selzy_action_fields() {
    ?>
    <script type="text/template" id="selzy-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php 
    esc_html_e( 'Selzy Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=selzy' );
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

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Skip Double Opt-in', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doubleOptin]" value="true" v-model="fielddata.doubleOptin">
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

/*
 * Selzy API Request
 */
function adfoin_selzy_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_selzy_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];
    $base_url = 'https://api.selzy.com/en/api/';
    $url = $base_url . $endpoint;
    $final_data = array(
        'format'  => 'json',
        'api_key' => $api_key,
    );
    if ( $data ) {
        $final_data = $final_data + $data;
    }
    $url = add_query_arg( $final_data, $url );
    $args = array(
        'method'       => $method,
        'Content-Type' => 'application/json',
    );
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

add_action(
    'wp_ajax_adfoin_get_selzy_list',
    'adfoin_get_selzy_list',
    10,
    0
);
/*
 * Get Mailchimp subscriber lists
 */
function adfoin_get_selzy_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $return = adfoin_selzy_request(
        'getLists',
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( !is_wp_error( $return ) ) {
        $body = json_decode( wp_remote_retrieve_body( $return ), true );
        $lists = array();
        if ( isset( $body['result'] ) && is_array( $body['result'] ) ) {
            $lists = wp_list_pluck( $body['result'], 'title', 'id' );
        }
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_selzy_job_queue',
    'adfoin_selzy_job_queue',
    10,
    1
);
function adfoin_selzy_job_queue(  $data  ) {
    adfoin_selzy_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Selzy API
 */
function adfoin_selzy_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $list_id = $data['listId'];
    $dopt = $data['doubleOptin'];
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    if ( $task == 'subscribe' ) {
        $req_data = array(
            'fields' => array(),
        );
        if ( isset( $data['email'] ) && $data['email'] ) {
            $req_data['fields']['email'] = trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );
        }
        if ( isset( $data['name'] ) && $data['name'] ) {
            $req_data['fields']['Name'] = adfoin_get_parsed_values( $data['name'], $posted_data );
        }
        if ( isset( $data['phone'] ) && $data['phone'] ) {
            $req_data['fields']['phone'] = adfoin_get_parsed_values( $data['phone'], $posted_data );
        }
        if ( $list_id ) {
            $req_data['list_ids'] = $list_id;
        }
        if ( 'true' == $dopt ) {
            $req_data['double_optin'] = 3;
        }
        $return = adfoin_selzy_request(
            'subscribe',
            'GET',
            $req_data,
            $record,
            $cred_id
        );
    }
}
