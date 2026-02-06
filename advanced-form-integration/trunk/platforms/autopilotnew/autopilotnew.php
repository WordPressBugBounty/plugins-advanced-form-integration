<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_autopilotnew_actions',
    10,
    1
);
function adfoin_autopilotnew_actions(  $actions  ) {
    $actions['autopilotnew'] = array(
        'title' => __( 'Ortto', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add/Update Person', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Ortto credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key', 'data_center' keys, or empty strings if not found
 */
function adfoin_autopilotnew_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $api_key = '';
    $data_center = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'autopilotnew' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                $data_center = $single['data_center'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = ( get_option( 'adfoin_autopilotnew_api_key' ) ? get_option( 'adfoin_autopilotnew_api_key' ) : '' );
        $data_center = ( get_option( 'adfoin_autopilotnew_data_center' ) ? get_option( 'adfoin_autopilotnew_data_center' ) : '' );
    }
    return array(
        'api_key'     => $api_key,
        'data_center' => $data_center,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_autopilotnew_settings_tab',
    10,
    1
);
function adfoin_autopilotnew_settings_tab(  $providers  ) {
    $providers['autopilotnew'] = __( 'Ortto', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_autopilotnew_settings_view',
    10,
    1
);
function adfoin_autopilotnew_settings_view(  $current_tab  ) {
    if ( $current_tab != 'autopilotnew' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = ( get_option( 'adfoin_autopilotnew_api_key' ) ? get_option( 'adfoin_autopilotnew_api_key' ) : '' );
    $old_data_center = ( get_option( 'adfoin_autopilotnew_data_center' ) ? get_option( 'adfoin_autopilotnew_data_center' ) : '' );
    $existing_creds = adfoin_read_credentials( 'autopilotnew' );
    if ( $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'          => uniqid(),
            'title'       => 'Default Account (Legacy)',
            'api_key'     => $old_api_key,
            'data_center' => ( $old_data_center ? $old_data_center : 'us' ),
        );
        adfoin_save_credentials( 'autopilotnew', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'data_center',
        'label'         => __( 'Data Center', 'advanced-form-integration' ),
        'type'          => 'select',
        'required'      => true,
        'options'       => array(
            'us' => 'US',
            'eu' => 'EU',
            'au' => 'AU',
        ),
        'show_in_table' => true,
    ), array(
        'name'          => 'api_key',
        'label'         => __( 'Private Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter Private Key', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Create a new data source in your Ortto account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Select Custom API as the data source type.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Put a name for your data source.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy the private key from the data source.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Save the data source in Ortto.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your data center and private key in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'autopilotnew',
        'Ortto',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_autopilotnew_credentials', 'adfoin_get_autopilotnew_credentials' );
function adfoin_get_autopilotnew_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'autopilotnew' );
}

add_action( 'wp_ajax_adfoin_save_autopilotnew_credentials', 'adfoin_save_autopilotnew_credentials' );
function adfoin_save_autopilotnew_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'autopilotnew', array('data_center', 'api_key') );
}

add_action( 'wp_ajax_adfoin_get_autopilotnew_credentials_list', 'adfoin_autopilotnew_get_credentials_list_ajax' );
function adfoin_autopilotnew_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'data_center',
        'mask' => false,
    ), array(
        'name' => 'api_key',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'autopilotnew', $fields );
}

add_action(
    'admin_post_adfoin_autopilotnew_save_api_key',
    'adfoin_save_autopilotnew_api_key',
    10,
    0
);
function adfoin_save_autopilotnew_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_autopilotnew_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = ( isset( $_POST['adfoin_autopilotnew_api_key'] ) ? sanitize_text_field( $_POST['adfoin_autopilotnew_api_key'] ) : '' );
    $data_center = ( isset( $_POST['adfoin_autopilotnew_data_center'] ) ? sanitize_text_field( $_POST['adfoin_autopilotnew_data_center'] ) : '' );
    // Save tokens
    update_option( 'adfoin_autopilotnew_api_key', $api_key );
    update_option( 'adfoin_autopilotnew_data_center', $data_center );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=autopilotnew' );
}

add_action( 'adfoin_action_fields', 'adfoin_autopilotnew_action_fields' );
function adfoin_autopilotnew_action_fields() {
    ?>
    <script type="text/template" id="autopilotnew-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php 
    esc_html_e( 'Ortto Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=autopilotnew' );
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
    esc_attr_e( 'Person Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

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
        printf( __( 'To unlock custom fields, consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
 * Handles sending data to Autopilot API
 */
function adfoin_autopilotnew_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    if ( $task == 'subscribe' ) {
        $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
        $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
        $data = array(
            'people'         => array(array(
                'fields' => array(
                    'str::email' => trim( $email ),
                ),
            )),
            'async'          => false,
            'merge_by'       => array('str::email'),
            'merge_strategy' => 2,
            'find_strategy'  => 0,
        );
        if ( $first_name ) {
            $data['people'][0]['fields']['str::first'] = $first_name;
        }
        if ( $last_name ) {
            $data['people'][0]['fields']['str::last'] = $last_name;
        }
        $return = adfoin_autopilotnew_request(
            'person/merge',
            'POST',
            $data,
            $record,
            $cred_id
        );
    }
    return;
}

add_action(
    'adfoin_autopilotnew_job_queue',
    'adfoin_autopilotnew_job_queue',
    10,
    1
);
function adfoin_autopilotnew_job_queue(  $data  ) {
    adfoin_autopilotnew_send_data( $data['record'], $data['posted_data'] );
}

// Ortto API Call
function adfoin_autopilotnew_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_autopilotnew_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];
    $data_center = $credentials['data_center'];
    if ( !$api_key ) {
        return new WP_Error('no_credentials', 'No Ortto API key found');
    }
    $base_url = 'https://api.ap3api.com/v1/';
    if ( $data_center && $data_center !== 'us' ) {
        if ( $data_center == 'eu' ) {
            $base_url = str_replace( 'ap3api', 'eu.ap3api', $base_url );
        }
        if ( $data_center == 'au' ) {
            $base_url = str_replace( 'ap3api', 'au.ap3api', $base_url );
        }
    }
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
            'X-Api-Key' => $api_key,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method ) {
        if ( $data ) {
            $args['body'] = json_encode( $data );
        }
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
