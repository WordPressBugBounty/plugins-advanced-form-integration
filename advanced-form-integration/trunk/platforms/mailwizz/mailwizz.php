<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailwizz_actions', 10, 1 );

function adfoin_mailwizz_actions( $actions ) {

    $actions['mailwizz'] = array(
        'title' => __( 'MailWizz', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

/**
 * Get MailWizz credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_url' and 'api_key' keys, or empty strings if not found
 */
function adfoin_mailwizz_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_url = '';
    $api_key = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'mailwizz' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_url = $single['api_url'];
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_url = get_option( 'adfoin_mailwizz_api_url' ) ? get_option( 'adfoin_mailwizz_api_url' ) : '';
        $api_key = get_option( 'adfoin_mailwizz_api_key' ) ? get_option( 'adfoin_mailwizz_api_key' ) : '';
    }

    return array(
        'api_url' => $api_url,
        'api_key' => $api_key
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailwizz_settings_tab', 10, 1 );

function adfoin_mailwizz_settings_tab( $providers ) {
    $providers['mailwizz'] = __( 'MailWizz', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailwizz_settings_view', 10, 1 );

function adfoin_mailwizz_settings_view( $current_tab ) {
    if( $current_tab != 'mailwizz' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_url = get_option( 'adfoin_mailwizz_api_url' ) ? get_option( 'adfoin_mailwizz_api_url' ) : '';
    $old_api_key = get_option( 'adfoin_mailwizz_api_key' ) ? get_option( 'adfoin_mailwizz_api_key' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'mailwizz' );

    if ( ( $old_api_url || $old_api_key ) && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'api_url' => $old_api_url,
            'api_key' => $old_api_key
        );
        adfoin_save_credentials( 'mailwizz', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_url',
            'label'         => __( 'API URL', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter API URL', 'advanced-form-integration' ),
            'mask'          => false,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your MailWizz installation.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Login as customer and go to API Keys menu.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Create new API Key and copy it.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter API URL (e.g. https://yourdomain.com/api/index.php) and API Key in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'mailwizz', 'MailWizz', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_mailwizz_credentials', 'adfoin_get_mailwizz_credentials' );
function adfoin_get_mailwizz_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'mailwizz' );
}

add_action( 'wp_ajax_adfoin_save_mailwizz_credentials', 'adfoin_save_mailwizz_credentials' );
function adfoin_save_mailwizz_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailwizz', array( 'api_url', 'api_key' ) );
}

add_action( 'wp_ajax_adfoin_get_mailwizz_credentials_list', 'adfoin_mailwizz_get_credentials_list_ajax' );
function adfoin_mailwizz_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_url', 'mask' => false ),
        array( 'name' => 'api_key', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailwizz', $fields );
}

add_action( 'admin_post_adfoin_mailwizz_save_api_key', 'adfoin_save_mailwizz_api_key', 10, 0 );

function adfoin_save_mailwizz_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_mailwizz_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_url = sanitize_text_field( $_POST['adfoin_mailwizz_api_url'] );
    $api_key = sanitize_text_field( $_POST['adfoin_mailwizz_api_key'] );

    // Save keys
    update_option( 'adfoin_mailwizz_api_url', $api_url );
    update_option( 'adfoin_mailwizz_api_key', $api_key );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=mailwizz" );
}

add_action( 'adfoin_action_fields', 'adfoin_mailwizz_action_fields' );

function adfoin_mailwizz_action_fields() {
    ?>
    <script type="text/template" id="mailwizz-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php esc_html_e( 'MailWizz Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getList">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailwizz' ); ?>" 
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
 * Mailwizz API Request
 */
function adfoin_mailwizz_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_mailwizz_get_credentials( $cred_id );
    $api_url = $credentials['api_url'];
    $api_key = $credentials['api_key'];
    $url = $api_url . $endpoint;

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'Content-Type'    => 'application/x-www-form-urlencoded',
            'X-MW-PUBLIC-KEY' => $api_key,
        ),
    );

    if ('POST' == $method || 'PUT' == $method) {
        $args['body'] = $data;
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_mailwizz_list', 'adfoin_get_mailwizz_list', 10, 0 );
/*
 * Get MailWizz subscriber lists
 */
function adfoin_get_mailwizz_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $lists = array();
    $page = 1;
    $hasnext = true;

    do{
        $data = adfoin_mailwizz_request( "/lists?page={$page}&per_page=50", 'GET', array(), array(), $cred_id );

        if( is_wp_error( $data ) ) {
            wp_send_json_error();
        }

        $body = json_decode( wp_remote_retrieve_body( $data ) );
        
        foreach( $body->data->records as $list ) {
            $lists[$list->general->list_uid] = $list->general->display_name;
        }

        if( $body->data->next_page ) {
            $page = $body->data->next_page;
        }else{
            $hasnext = false;
        }
    } while( $hasnext );
    
    wp_send_json_success( $lists );
}

add_action( 'adfoin_mailwizz_job_queue', 'adfoin_mailwizz_job_queue', 10, 1 );

function adfoin_mailwizz_job_queue( $data ) {
    adfoin_mailwizz_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Mailwizz API
 */
function adfoin_mailwizz_send_data( $record, $posted_data ) {

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
        $email      = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );

        $body_data = array(
            'EMAIL' => trim( $email ),
            'FNAME' => $first_name,
            'LNAME' => $last_name
        );

        $return = adfoin_mailwizz_request( '/lists/' . $list_id . '/subscribers', 'POST', $body_data, $record, $cred_id );
    }

    return;
}