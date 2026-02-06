<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_moosend_actions',
    10,
    1
);
function adfoin_moosend_actions(  $actions  ) {
    $actions['moosend'] = array(
        'title' => __( 'Moosend', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Moosend credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_token' key, or empty string if not found
 */
function adfoin_moosend_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $api_token = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'moosend' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $api_token = $single['api_token'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_token = ( get_option( 'adfoin_moosend_api_token' ) ? get_option( 'adfoin_moosend_api_token' ) : '' );
    }
    return array(
        'api_token' => $api_token,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_moosend_settings_tab',
    10,
    1
);
function adfoin_moosend_settings_tab(  $providers  ) {
    $providers['moosend'] = __( 'Moosend', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_moosend_settings_view',
    10,
    1
);
function adfoin_moosend_settings_view(  $current_tab  ) {
    if ( $current_tab != 'moosend' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_moosend_api_token' );
    $existing_creds = adfoin_read_credentials( 'moosend' );
    if ( $old_api_token && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'        => uniqid(),
            'title'     => 'Default Account (Legacy)',
            'api_token' => $old_api_token,
        );
        adfoin_save_credentials( 'moosend', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'api_token',
        'label'         => __( 'API Token', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter your API Token', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to Settings > API Key in your Moosend account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your API Token.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the API Token above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'moosend',
        'Moosend',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_moosend_credentials', 'adfoin_get_moosend_credentials' );
function adfoin_get_moosend_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'moosend' );
}

add_action( 'wp_ajax_adfoin_save_moosend_credentials', 'adfoin_save_moosend_credentials' );
function adfoin_save_moosend_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'moosend', array('api_token') );
}

add_action( 'wp_ajax_adfoin_get_moosend_credentials_list', 'adfoin_moosend_get_credentials_list_ajax' );
function adfoin_moosend_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'api_token',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'moosend', $fields );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_moosend_js_fields',
    10,
    1
);
function adfoin_moosend_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_moosend_action_fields' );
function adfoin_moosend_action_fields() {
    ?>
    <script type="text/template" id="moosend-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php 
    esc_html_e( 'Moosend Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=moosend' );
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
    esc_attr_e( 'Moosend List', 'advanced-form-integration' );
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
    'wp_ajax_adfoin_get_moosend_list',
    'adfoin_get_moosend_list',
    10,
    0
);
/*
 * Get Moosend subscriber lists
 */
function adfoin_get_moosend_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $credentials = adfoin_moosend_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];
    if ( !$api_token ) {
        wp_send_json_error();
    }
    $data = adfoin_moosend_request(
        'lists.json?PageSize=1000',
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $body = json_decode( $data['body'] );
    $lists = wp_list_pluck( $body->Context->MailingLists, 'Name', 'ID' );
    wp_send_json_success( $lists );
}

/*
* Request to Moosend API
*/
function adfoin_moosend_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $base_url = 'https://api.moosend.com/v3/';
    $credentials = adfoin_moosend_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];
    if ( !$api_token ) {
        return new WP_Error('missing_credentials', 'API Token is missing');
    }
    $url = $base_url . $endpoint;
    $url = add_query_arg( array(
        'apikey' => $api_token,
    ), $url );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
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

add_action(
    'adfoin_moosend_job_queue',
    'adfoin_moosend_job_queue',
    10,
    1
);
function adfoin_moosend_job_queue(  $data  ) {
    adfoin_moosend_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to moosend API
 */
function adfoin_moosend_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $list_id = $data['listId'];
    $task = $record['task'];
    if ( $task == 'subscribe' ) {
        $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $subscriber = array(
            'email' => trim( $email ),
        );
        if ( $data['name'] ) {
            $subscriber['name'] = adfoin_get_parsed_values( $data['name'], $posted_data );
        }
        if ( $data['mobile'] ) {
            $subscriber['mobile'] = adfoin_get_parsed_values( $data['mobile'], $posted_data );
        }
        $return = adfoin_moosend_request(
            'subscribers/' . $list_id . '/subscribe.json',
            'POST',
            $subscriber,
            $record,
            $cred_id
        );
    }
    return;
}
