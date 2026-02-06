<?php

add_filter( 'adfoin_action_providers', 'adfoin_salesrocks_actions', 10, 1 );

function adfoin_salesrocks_actions( $actions ) {

    $actions['salesrocks'] = array(
        'title' => __( 'Sales Rocks', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

/**
 * Get Salesrocks credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'username' and 'password' keys, or empty strings if not found
 */
function adfoin_salesrocks_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $username = '';
    $password = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'salesrocks' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $username = $single['username'];
                $password = $single['password'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $username = get_option( 'adfoin_salesrocks_username' ) ? get_option( 'adfoin_salesrocks_username' ) : '';
        $password = get_option( 'adfoin_salesrocks_password' ) ? get_option( 'adfoin_salesrocks_password' ) : '';
    }

    return array(
        'username' => $username,
        'password' => $password
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_salesrocks_settings_tab', 10, 1 );

function adfoin_salesrocks_settings_tab( $providers ) {
    $providers['salesrocks'] = __( 'Sales Rocks', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_salesrocks_settings_view', 10, 1 );

function adfoin_salesrocks_settings_view( $current_tab ) {
    if( $current_tab != 'salesrocks' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_username = get_option( 'adfoin_salesrocks_username' );
    $old_password = get_option( 'adfoin_salesrocks_password' );
    
    $existing_creds = adfoin_read_credentials( 'salesrocks' );

    if ( $old_username && $old_password && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account',
            'username' => $old_username,
            'password' => $old_password
        );
        adfoin_save_credentials( 'salesrocks', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'username',
            'label'         => __( 'Username', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter Username', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'password',
            'label'         => __( 'Password', 'advanced-form-integration' ),
            'type'          => 'password',
            'required'      => true,
            'placeholder'   => __( 'Enter Password', 'advanced-form-integration' ),
            'mask'          => true,  // Mask password in table
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Sales Rocks account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your username and password above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'salesrocks', 'Sales Rocks', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_salesrocks_credentials', 'adfoin_get_salesrocks_credentials' );
function adfoin_get_salesrocks_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'salesrocks' );
}

add_action( 'wp_ajax_adfoin_save_salesrocks_credentials', 'adfoin_save_salesrocks_credentials' );
function adfoin_save_salesrocks_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'salesrocks', array( 'username', 'password' ) );
}

add_action( 'wp_ajax_adfoin_get_salesrocks_credentials_list', 'adfoin_salesrocks_get_credentials_list_ajax' );
function adfoin_salesrocks_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'username' ),
        array( 'name' => 'password', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'salesrocks', $fields );
}

add_action( 'adfoin_add_js_fields', 'adfoin_salesrocks_js_fields', 10, 1 );

function adfoin_salesrocks_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_salesrocks_action_fields' );

function adfoin_salesrocks_action_fields() {
    ?>
    <script type="text/template" id="salesrocks-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php esc_html_e( 'Sales Rocks Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=salesrocks' ); ?>" 
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
                        <?php esc_attr_e( 'List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select List...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

/*
 * Sales.rocks API Request
 */
function adfoin_salesrocks_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $access_token = adfoin_salesrocks_get_access_token( $cred_id );

    $base_url = 'https://api.sales.rocks/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ),
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

function adfoin_salesrocks_get_access_token( $cred_id = '' ) {
    $credentials = adfoin_salesrocks_get_credentials( $cred_id );
    $username = $credentials['username'];
    $password = $credentials['password'];
    
    $url          = 'https://api.sales.rocks/auth/accessToken';
    $access_token = '';

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30,
        'body' => json_encode( array(
            'username' => $username,
            'password' => $password
        ))
    );

    $response = wp_remote_post( $url, $args );
    
    if( 200 == wp_remote_retrieve_response_code( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if( isset( $body['AccessToken'] ) ) {
            $access_token = $body['AccessToken'];
        }
    }

    return $access_token;
}

add_action( 'wp_ajax_adfoin_get_salesrocks_list', 'adfoin_get_salesrocks_list', 10, 0 );
/*
 * Get Kalviyo subscriber lists
 */
function adfoin_get_salesrocks_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_salesrocks_request( 'editable-lists/getLists', 'POST', array(), array(), $cred_id );

    if( is_wp_error( $data ) ) {
        wp_send_json_error();
    }

    $body  = json_decode( wp_remote_retrieve_body( $data ) );
    $lists = wp_list_pluck( $body->data, 'name', 'uuid' );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_salesrocks_job_queue', 'adfoin_salesrocks_job_queue', 10, 1 );

function adfoin_salesrocks_job_queue( $data ) {
    adfoin_salesrocks_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Sales Rocks API
 */
function adfoin_salesrocks_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if( $task == 'subscribe' ) {
        $list_id = $data['listId'];
        $email   = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $name    = empty( $data['name'] ) ? '' : adfoin_get_parsed_values( $data['name'], $posted_data );

        $data = array(
            'list_id' => $list_id,
            'data' => array(
                array(
                    'name'  => $name,
                    'email' => trim( $email )
                )
            )
        );

        $return = adfoin_salesrocks_request( 'editable-lists/addToList', 'POST', $data, $record, $cred_id );
    }

    return;
}