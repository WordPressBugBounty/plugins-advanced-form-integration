<?php
add_filter( 'adfoin_action_providers', 'adfoin_sendpulse_actions', 10, 1 );

function adfoin_sendpulse_actions( $actions ) {

    $actions['sendpulse'] = array(
        'title' => __( 'SendPulse', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To Email List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get SendPulse credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'user_id', 'secret' keys, or empty strings if not found
 */
function adfoin_sendpulse_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $user_id = '';
    $secret = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'sendpulse' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $user_id = $single['user_id'];
                $secret = $single['secret'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $user_id = get_option( 'adfoin_sendpulse_id' ) ? get_option( 'adfoin_sendpulse_id' ) : '';
        $secret = get_option( 'adfoin_sendpulse_secret' ) ? get_option( 'adfoin_sendpulse_secret' ) : '';
    }

    return array(
        'user_id' => $user_id,
        'secret' => $secret
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sendpulse_settings_tab', 10, 1 );

function adfoin_sendpulse_settings_tab( $providers ) {
    $providers['sendpulse'] = __( 'SendPulse', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendpulse_settings_view', 10, 1 );

function adfoin_sendpulse_settings_view( $current_tab ) {
    if( $current_tab != 'sendpulse' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_id = get_option( 'adfoin_sendpulse_id' ) ? get_option( 'adfoin_sendpulse_id' ) : '';
    $old_secret = get_option( 'adfoin_sendpulse_secret' ) ? get_option( 'adfoin_sendpulse_secret' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'sendpulse' );

    if ( $old_id && $old_secret && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'user_id' => $old_id,
            'secret' => $old_secret
        );
        adfoin_save_credentials( 'sendpulse', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'user_id',
            'label'         => __( 'ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'secret',
            'label'         => __( 'Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter Secret', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to Account Settings > API in your SendPulse dashboard.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your ID and Secret.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the credentials in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'sendpulse', 'SendPulse', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendpulse_credentials', 'adfoin_get_sendpulse_credentials' );
function adfoin_get_sendpulse_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendpulse' );
}

add_action( 'wp_ajax_adfoin_save_sendpulse_credentials', 'adfoin_save_sendpulse_credentials' );
function adfoin_save_sendpulse_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendpulse', array( 'user_id', 'secret' ) );
}

add_action( 'wp_ajax_adfoin_get_sendpulse_credentials_list', 'adfoin_sendpulse_get_credentials_list_ajax' );
function adfoin_sendpulse_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'user_id', 'mask' => false ),
        array( 'name' => 'secret', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendpulse', $fields );
}

add_action( 'admin_post_adfoin_save_sendpulse_api_key', 'adfoin_save_sendpulse_api_key', 10, 0 );

function adfoin_save_sendpulse_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_sendpulse_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $id     = sanitize_text_field( $_POST['adfoin_sendpulse_id'] );
    $secret = sanitize_text_field( $_POST['adfoin_sendpulse_secret'] );

    // Save tokens
    update_option( "adfoin_sendpulse_id", $id );
    update_option( "adfoin_sendpulse_secret", $secret );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=sendpulse" );
}

add_action( 'adfoin_action_fields', 'adfoin_sendpulse_action_fields', 10, 1 );

function adfoin_sendpulse_action_fields() {
    ?>
    <script type="text/template" id="sendpulse-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php esc_html_e( 'SendPulse Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendpulse' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Contact Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Email List', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
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

add_action( 'wp_ajax_adfoin_get_sendpulse_list', 'adfoin_get_sendpulse_list', 10 );

function adfoin_get_sendpulse_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_sendpulse_get_credentials();
    
    if ( ! $credentials['user_id'] || ! $credentials['secret'] ) {
        wp_send_json_error( __( 'SendPulse credentials are missing.', 'advanced-form-integration' ) );
        return;
    }

    $response = adfoin_sendpulse_request('addressbooks', 'GET', array(), array(), $credentials);
    
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
        return;
    }
    
    $addressbooks = json_decode(wp_remote_retrieve_body($response));
    $lists = wp_list_pluck($addressbooks, 'name', 'id');

    wp_send_json_success($lists);
}

add_action( 'adfoin_sendpulse_job_queue', 'adfoin_sendpulse_job_queue', 10, 1 );

function adfoin_sendpulse_job_queue( $data ) {
    adfoin_sendpulse_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to SendPulse API
 */
function adfoin_sendpulse_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $task = $record['task'];
    
    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    $credentials = adfoin_sendpulse_get_credentials( $cred_id );
    
    $list_id = $field_data['listId'];
    $email   = empty( $field_data['email'] ) ? '' : adfoin_get_parsed_values($field_data['email'], $posted_data);
    $name    = empty( $field_data['name'] ) ? '' : adfoin_get_parsed_values($field_data['name'], $posted_data);
    $phone   = empty( $field_data['phone'] ) ? '' : adfoin_get_parsed_values($field_data['phone'], $posted_data);

    if( $task == 'subscribe' ) {
        $emails = array(
            'emails' => array(
                array(
                    'email' => $email,
                    'variables' => array_filter( array(
                        'name'  => $name ? $name : '',
                        'Phone' => $phone ? $phone : ''
                    ))
                )
            )
        );

        $response = adfoin_sendpulse_request('addressbooks/' . $list_id . '/emails', 'POST', $emails, $record, $credentials);
        $return = json_decode(wp_remote_retrieve_body($response));
    }
}

function adfoin_sendpulse_request($endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = null) {

    if ( ! $credentials ) {
        $credentials = adfoin_sendpulse_get_credentials();
    }
    
    $user_id = $credentials['user_id'];
    $secret = $credentials['secret'];

    // Get token
    $token = get_transient('sendpulse_access_token');
    if (!$token) {
        $token_response = wp_remote_post('https://api.sendpulse.com/oauth/access_token', array(
            'body' => json_encode(array(
                'grant_type'    => 'client_credentials',
                'client_id'     => $user_id,
                'client_secret' => $secret,
            )),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ));

        $token_body = json_decode(wp_remote_retrieve_body($token_response));
        $token = isset($token_body->access_token) ? $token_body->access_token : '';

        // Save token in transient for 1 hour
        set_transient('sendpulse_access_token', $token, 3500);
    }

    $url = 'https://api.sendpulse.com/' . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ),
    );

    if ('POST' == $method || 'PUT' == $method || 'PATCH' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}
