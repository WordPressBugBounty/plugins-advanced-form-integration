<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_sendx_actions',
    10,
    1
);
function adfoin_sendx_actions(  $actions  ) {
    $actions['sendx'] = array(
        'title' => __( 'SendX', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Contact', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get SendX credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'team_id', 'api_key' keys, or empty strings if not found
 */
function adfoin_sendx_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $team_id = '';
    $api_key = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'sendx' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $team_id = $single['team_id'];
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $team_id = ( get_option( 'adfoin_sendx_team_id' ) ? get_option( 'adfoin_sendx_team_id' ) : '' );
        $api_key = ( get_option( 'adfoin_sendx_api_key' ) ? get_option( 'adfoin_sendx_api_key' ) : '' );
    }
    return array(
        'team_id' => $team_id,
        'api_key' => $api_key,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_sendx_settings_tab',
    10,
    1
);
function adfoin_sendx_settings_tab(  $providers  ) {
    $providers['sendx'] = __( 'SendX', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_sendx_settings_view',
    10,
    1
);
function adfoin_sendx_settings_view(  $current_tab  ) {
    if ( $current_tab != 'sendx' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_team_id = ( get_option( 'adfoin_sendx_team_id' ) ? get_option( 'adfoin_sendx_team_id' ) : '' );
    $old_api_key = ( get_option( 'adfoin_sendx_api_key' ) ? get_option( 'adfoin_sendx_api_key' ) : '' );
    $existing_creds = adfoin_read_credentials( 'sendx' );
    if ( $old_team_id && $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'      => uniqid(),
            'title'   => 'Default Account (Legacy)',
            'team_id' => $old_team_id,
            'api_key' => $old_api_key,
        );
        adfoin_save_credentials( 'sendx', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'team_id',
        'label'         => __( 'Team ID', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter Team ID', 'advanced-form-integration' ),
        'show_in_table' => true,
    ), array(
        'name'          => 'api_key',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => true,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to <a href="https://app.sendx.io/setting" target="_blank" rel="noopener noreferrer">SendX settings page</a> and scroll down to the bottom.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your Team ID and API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the credentials in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'sendx',
        'SendX',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_sendx_credentials', 'adfoin_get_sendx_credentials' );
function adfoin_get_sendx_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'sendx' );
}

add_action( 'wp_ajax_adfoin_save_sendx_credentials', 'adfoin_save_sendx_credentials' );
function adfoin_save_sendx_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'sendx', array('team_id', 'api_key') );
}

add_action( 'wp_ajax_adfoin_get_sendx_credentials_list', 'adfoin_sendx_get_credentials_list_ajax' );
function adfoin_sendx_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'team_id',
        'mask' => false,
    ), array(
        'name' => 'api_key',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'sendx', $fields );
}

add_action(
    'admin_post_adfoin_save_sendx_api_key',
    'adfoin_save_sendx_api_key',
    10,
    0
);
function adfoin_save_sendx_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_sendx_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $team_id = sanitize_text_field( $_POST['adfoin_sendx_team_id'] );
    $api_key = sanitize_text_field( $_POST['adfoin_sendx_api_key'] );
    // Save tokens
    update_option( 'adfoin_sendx_team_id', $team_id );
    update_option( 'adfoin_sendx_api_key', $api_key );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=sendx" );
}

add_action( 'adfoin_action_fields', 'adfoin_sendx_action_fields' );
function adfoin_sendx_action_fields() {
    ?>
    <script type="text/template" id="sendx-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php 
    esc_html_e( 'SendX Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=sendx' );
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
 * SendX API Request
 */
function adfoin_sendx_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $credentials = null
) {
    if ( !$credentials ) {
        $credentials = adfoin_sendx_get_credentials();
    }
    $team_id = $credentials['team_id'];
    $api_key = $credentials['api_key'];
    $base_url = 'http://app.sendx.io/api/v1/';
    $url = $base_url . $endpoint;
    $url = add_query_arg( array(
        'team_id' => $team_id,
    ), $url );
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'api_key'      => $api_key,
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
    'adfoin_sendx_job_queue',
    'adfoin_sendx_job_queue',
    10,
    1
);
function adfoin_sendx_job_queue(  $data  ) {
    adfoin_sendx_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to SendX API
 */
function adfoin_sendx_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $field_data = ( isset( $record_data['field_data'] ) ? $record_data['field_data'] : array() );
    $task = $record['task'];
    $cred_id = ( isset( $field_data['credId'] ) ? $field_data['credId'] : '' );
    $credentials = adfoin_sendx_get_credentials( $cred_id );
    if ( $task == 'subscribe' ) {
        $email = ( empty( $field_data['email'] ) ? '' : adfoin_get_parsed_values( $field_data['email'], $posted_data ) );
        $first_name = ( empty( $field_data['firstName'] ) ? '' : adfoin_get_parsed_values( $field_data['firstName'], $posted_data ) );
        $last_name = ( empty( $field_data['lastName'] ) ? '' : adfoin_get_parsed_values( $field_data['lastName'], $posted_data ) );
        $company = ( empty( $field_data['company'] ) ? '' : adfoin_get_parsed_values( $field_data['company'], $posted_data ) );
        $birthday = ( empty( $field_data['birthday'] ) ? '' : adfoin_get_parsed_values( $field_data['birthday'], $posted_data ) );
        $contact_data = array(
            'email'     => trim( $email ),
            'firstName' => $first_name,
            'lastName'  => $last_name,
            'company'   => $company,
            'birthday'  => $birthday,
        );
        $return = adfoin_sendx_request(
            'contact/identify',
            'POST',
            $contact_data,
            $record,
            $credentials
        );
    }
}
