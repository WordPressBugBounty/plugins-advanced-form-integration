<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_robly_actions',
    10,
    1
);
function adfoin_robly_actions(  $actions  ) {
    $actions['robly'] = array(
        'title' => __( 'Robly', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Contact To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Robly credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_id' and 'api_key' keys, or empty strings if not found
 */
function adfoin_robly_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $api_id = '';
    $api_key = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'robly' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $api_id = $single['api_id'];
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_id = ( get_option( 'adfoin_robly_api_id' ) ? get_option( 'adfoin_robly_api_id' ) : '' );
        $api_key = ( get_option( 'adfoin_robly_api_key' ) ? get_option( 'adfoin_robly_api_key' ) : '' );
    }
    return array(
        'api_id'  => $api_id,
        'api_key' => $api_key,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_robly_settings_tab',
    10,
    1
);
function adfoin_robly_settings_tab(  $providers  ) {
    $providers['robly'] = __( 'Robly', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_robly_settings_view',
    10,
    1
);
function adfoin_robly_settings_view(  $current_tab  ) {
    if ( $current_tab != 'robly' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_api_id = ( get_option( 'adfoin_robly_api_id' ) ? get_option( 'adfoin_robly_api_id' ) : '' );
    $old_api_key = ( get_option( 'adfoin_robly_api_key' ) ? get_option( 'adfoin_robly_api_key' ) : '' );
    $existing_creds = adfoin_read_credentials( 'robly' );
    if ( ($old_api_id || $old_api_key) && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'      => uniqid(),
            'title'   => 'Default Account (Legacy)',
            'api_id'  => $old_api_id,
            'api_key' => $old_api_key,
        );
        adfoin_save_credentials( 'robly', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'api_id',
        'label'         => __( 'API ID', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Please enter the API ID', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ), array(
        'name'          => 'api_key',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Please enter API Key', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => false,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Robly account settings.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to Settings > API Details to get API ID and API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter both API ID and API Key in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'robly',
        'Robly',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_robly_credentials', 'adfoin_get_robly_credentials' );
function adfoin_get_robly_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'robly' );
}

add_action( 'wp_ajax_adfoin_save_robly_credentials', 'adfoin_save_robly_credentials' );
function adfoin_save_robly_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'robly', array('api_id', 'api_key') );
}

add_action( 'wp_ajax_adfoin_get_robly_credentials_list', 'adfoin_robly_get_credentials_list_ajax' );
function adfoin_robly_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'api_id',
        'mask' => true,
    ), array(
        'name' => 'api_key',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'robly', $fields );
}

add_action(
    'admin_post_adfoin_save_robly_api_key',
    'adfoin_save_robly_api_key',
    10,
    0
);
function adfoin_save_robly_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_robly_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_id = sanitize_text_field( $_POST['adfoin_robly_api_id'] );
    $api_key = sanitize_text_field( $_POST['adfoin_robly_api_key'] );
    // Save tokens
    update_option( 'adfoin_robly_api_id', $api_id );
    update_option( 'adfoin_robly_api_key', $api_key );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=robly" );
}

add_action( 'adfoin_action_fields', 'adfoin_robly_action_fields' );
function adfoin_robly_action_fields() {
    ?>
    <script type="text/template" id="robly-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php 
    esc_html_e( 'Robly Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=robly' );
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
 * Robly API Request
 */
function adfoin_robly_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_robly_get_credentials( $cred_id );
    $api_id = $credentials['api_id'];
    $api_key = $credentials['api_key'];
    $base_url = 'https://api.robly.com/api/v1/';
    $url = $base_url . $endpoint;
    $final_data = array(
        'api_id'  => $api_id,
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
    'wp_ajax_adfoin_get_robly_list',
    'adfoin_get_robly_list',
    10,
    0
);
/*
 * Get Robly subscriber lists
 */
function adfoin_get_robly_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $return = adfoin_robly_request(
        'sub_lists/show?include_all=true',
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( !is_wp_error( $return ) ) {
        $body = json_decode( wp_remote_retrieve_body( $return ), true );
        $lists = array();
        if ( is_array( $body ) ) {
            foreach ( $body as $single ) {
                $id = $single['sub_list']['id'];
                $name = $single['sub_list']['name'];
                $lists[$id] = $name;
            }
        }
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

function adfoin_robly_check_if_contact_exists(  $email, $cred_id = ''  ) {
    $return = adfoin_robly_request(
        'contacts/search?email=' . $email,
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( !is_wp_error( $return ) ) {
        $body = json_decode( wp_remote_retrieve_body( $return ), true );
        if ( isset( $body['member'], $body['member']['id'] ) ) {
            return $body['member']['id'];
        } else {
            return;
        }
    } else {
        return false;
    }
}

add_action(
    'adfoin_robly_job_queue',
    'adfoin_robly_job_queue',
    10,
    1
);
function adfoin_robly_job_queue(  $data  ) {
    adfoin_robly_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Robly API
 */
function adfoin_robly_send_data(  $record, $posted_data  ) {
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
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    if ( $task == 'subscribe' ) {
        $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $fname = ( empty( $data['fname'] ) ? '' : adfoin_get_parsed_values( $data['fname'], $posted_data ) );
        $lname = ( empty( $data['lname'] ) ? '' : adfoin_get_parsed_values( $data['lname'], $posted_data ) );
        $data = array(
            'sub_lists[]' => $list_id,
            'email'       => trim( $email ),
            'fname'       => $fname,
            'lname'       => $lname,
        );
        $contact_id = adfoin_robly_check_if_contact_exists( $email, $cred_id );
        if ( $contact_id ) {
            $return = adfoin_robly_request(
                'contacts/update_full_contact?member_id=' . $contact_id,
                'POST',
                $data,
                $record,
                $cred_id
            );
        } else {
            $return = adfoin_robly_request(
                'sign_up/generate',
                'POST',
                $data,
                $record,
                $cred_id
            );
        }
    }
}
