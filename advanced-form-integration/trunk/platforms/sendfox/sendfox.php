<?php

add_filter( 'adfoin_action_providers', 'adfoin_sendfox_actions', 10, 1 );

function adfoin_sendfox_actions( $actions ) {

    $actions['sendfox'] = array(
        'title' => __( 'SendFox', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get Sendfox credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key' key, or empty string if not found
 */
function adfoin_sendfox_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_key = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'sendfox' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = get_option( 'adfoin_sendfox_api_key' ) ? get_option( 'adfoin_sendfox_api_key' ) : '';
    }

    return array(
        'api_key' => $api_key
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sendfox_settings_tab', 10, 1 );

function adfoin_sendfox_settings_tab( $providers ) {
    $providers['sendfox'] = __( 'SendFox', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sendfox_settings_view', 10, 1 );

function adfoin_sendfox_settings_view( $current_tab ) {
    if( $current_tab != 'sendfox' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = get_option( 'adfoin_sendfox_api_key' );
    
    $existing_creds = adfoin_read_credentials( 'sendfox' );

    if ( $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account',
            'api_key' => $old_api_key
        );
        adfoin_save_credentials( 'sendfox', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'SendFox Personal Access Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter Access Token', 'advanced-form-integration' ),
            'description'   => __( 'Go to <a target="_blank" rel="noopener noreferrer" href="https://sendfox.com/account/oauth">https://sendfox.com/account/oauth</a> and click "Create New Token"', 'advanced-form-integration' ),
            'mask'          => true,  // Mask API key in table
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your SendFox account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to <a target="_blank" rel="noopener noreferrer" href="https://sendfox.com/account/oauth">https://sendfox.com/account/oauth</a>.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Create New Token" and copy the token.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your Access Token above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'sendfox', 'SendFox', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendfox_credentials', 'adfoin_get_sendfox_credentials' );
function adfoin_get_sendfox_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendfox' );
}

add_action( 'wp_ajax_adfoin_save_sendfox_credentials', 'adfoin_save_sendfox_credentials' );
function adfoin_save_sendfox_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendfox', array( 'api_key' ) );
}

add_action( 'wp_ajax_adfoin_get_sendfox_credentials_list', 'adfoin_sendfox_get_credentials_list_ajax' );
function adfoin_sendfox_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_key', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendfox', $fields );
}

add_action( 'adfoin_add_js_fields', 'adfoin_sendfox_js_fields', 10, 1 );

function adfoin_sendfox_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_sendfox_action_fields' );

function adfoin_sendfox_action_fields() {
?>
    <script type="text/template" id="sendfox-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row"><?php esc_html_e( 'SendFox Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendfox' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'SendFox List', 'advanced-form-integration' ); ?>
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

/*
 * Sendfox API Request
 */
function adfoin_sendfox_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '') {

    $credentials = adfoin_sendfox_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];
    
    $base_url = 'https://api.sendfox.com/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_sendfox_list', 'adfoin_get_sendfox_list', 10, 0 );

/*
 * Get Mailchimp subscriber lists
 */
function adfoin_get_sendfox_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $lists = array();
    $data  = adfoin_sendfox_request( 'lists', 'GET', array(), array(), $cred_id );

    if( !is_wp_error( $data ) ) {
        $body              = json_decode( wp_remote_retrieve_body( $data ), true );
        $lists             = $body['data'];
        $lists_total       = absint( $body['total'] );
        $list_per_page     = absint( $body['per_page'] );
        $pagination_needed = absint( $lists_total / $list_per_page ) + 1;

        if( $pagination_needed >= 2) {
            $response_pages = array();
            $response_body  = array();

            for( $i = 2; $i <= $pagination_needed; $i++ ){
                $response_pages[$i] = adfoin_sendfox_request( 'lists?page=' . $i, 'GET', array(), array(), $cred_id );
                $response_body[$i]  = json_decode( wp_remote_retrieve_body( $response_pages[$i] ), true );

                if( $response_body[$i]['data'] && is_array( $response_body[$i]['data'] ) )
                {
                    $lists = array_merge( $lists, $response_body[$i]['data'] );
                }
            }
        }
        
        $final_list = wp_list_pluck( $lists, 'name', 'id' );

        wp_send_json_success( $final_list );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_sendfox_job_queue', 'adfoin_sendfox_job_queue', 10, 1 );

function adfoin_sendfox_job_queue( $data ) {
    adfoin_sendfox_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to SendFox API
 */
function adfoin_sendfox_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data['field_data'];
    $list_id = $data['listId'];
    $task    = $record['task'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $email   = empty( $data['email'] ) ? '' : adfoin_get_parsed_values($data['email'], $posted_data);

    if( $task == 'subscribe' ) {
        $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values($data['firstName'], $posted_data);
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values($data['lastName'], $posted_data);

        $subscriber_data = array(
            'email' => trim( $email )
        );

        if( $first_name ) { $subscriber_data['first_name'] = $first_name; }
        if( $last_name ) { $subscriber_data['last_name'] = $last_name; }

        if( $list_id ) {
            $subscriber_data['lists'] = array( $list_id );
        }

        $return = adfoin_sendfox_request( 'contacts', 'POST', $subscriber_data, $record, $cred_id );

        return;
    }
}