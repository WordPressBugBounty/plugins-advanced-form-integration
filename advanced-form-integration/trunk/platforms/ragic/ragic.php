<?php

add_filter( 'adfoin_action_providers', 'adfoin_ragic_actions', 10, 1 );

function adfoin_ragic_actions( $actions ) {
    $actions['ragic'] = array(
        'title' => __( 'Ragic', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Record', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get Ragic credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_token' and 'base_url' keys, or empty strings if not found
 */
function adfoin_ragic_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_token = '';
    $base_url = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'ragic' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_token = $single['api_token'];
                $base_url = $single['base_url'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_token = get_option( 'adfoin_ragic_api_token' ) ? get_option( 'adfoin_ragic_api_token' ) : '';
        $base_url = get_option( 'adfoin_ragic_base_url' ) ? get_option( 'adfoin_ragic_base_url' ) : '';
    }

    return array(
        'api_token' => $api_token,
        'base_url' => $base_url
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ragic_settings_tab', 10, 1 );

function adfoin_ragic_settings_tab( $providers ) {
    $providers['ragic'] = __( 'Ragic', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_ragic_settings_view', 10, 1 );

function adfoin_ragic_settings_view( $current_tab ) {
    if( $current_tab != 'ragic' ) return;

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_ragic_api_token' ) ? get_option( 'adfoin_ragic_api_token' ) : '';
    $old_base_url = get_option( 'adfoin_ragic_base_url' ) ? get_option( 'adfoin_ragic_base_url' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'ragic' );

    if ( ( $old_api_token || $old_base_url ) && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'api_token' => $old_api_token,
            'base_url' => $old_base_url
        );
        adfoin_save_credentials( 'ragic', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'base_url',
            'label'         => __( 'Base URL', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter Base URL', 'advanced-form-integration' ),
            'mask'          => false,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Ragic account settings.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to API settings and generate an API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your Base URL (e.g., https://yourcompany.ragic.com).', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter both API Key and Base URL in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'ragic', 'Ragic', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_ragic_credentials', 'adfoin_get_ragic_credentials' );
function adfoin_get_ragic_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'ragic' );
}

add_action( 'wp_ajax_adfoin_save_ragic_credentials', 'adfoin_save_ragic_credentials' );
function adfoin_save_ragic_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'ragic', array( 'api_token', 'base_url' ) );
}

add_action( 'wp_ajax_adfoin_get_ragic_credentials_list', 'adfoin_ragic_get_credentials_list_ajax' );
function adfoin_ragic_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_token', 'mask' => true ),
        array( 'name' => 'base_url', 'mask' => false ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'ragic', $fields );
}

add_action( 'admin_post_adfoin_save_ragic_api_token', 'adfoin_save_ragic_api_token' );

function adfoin_save_ragic_api_token() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_ragic_settings' ) ) {
        die( __( 'Security check failed', 'advanced-form-integration' ) );
    }

    update_option( 'adfoin_ragic_api_token', sanitize_text_field( $_POST['adfoin_ragic_api_token'] ) );
    update_option( 'adfoin_ragic_base_url', sanitize_text_field( $_POST['adfoin_ragic_base_url'] ) );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=ragic' );
}


add_action( 'adfoin_action_fields', 'adfoin_ragic_action_fields', 10, 1 );

function adfoin_ragic_action_fields() {
    ?>
    <script type="text/template" id="ragic-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <td class="afi-label" scope="row"><?php esc_html_e( 'Ragic Account', 'advanced-form-integration' ); ?></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=ragic' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}


function adfoin_ragic_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_ragic_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];
    $base_url = $credentials['base_url'];
    
    $base_url = preg_replace( '/^http:/i', 'https:', $base_url );
    if ( strpos( $base_url, 'https://' ) !== 0 ) {
        $base_url = 'https://' . ltrim( $base_url, '/' );
    }
    $url = trailingslashit( $base_url ) . ltrim( $endpoint, '/' );
    $url = $url . '?api&v=3&APIKey=' . $api_token;

    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            // 'Authorization' => 'Basic ' . base64_encode( $api_token . ':' ),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_ragic_job_queue', 'adfoin_ragic_job_queue', 10, 1 );

function adfoin_ragic_job_queue( $data ) {
    adfoin_ragic_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ragic_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    
    if ( isset( $record_data["action_data"]["cl"] ) && adfoin_check_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) return;

    $data       = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $account_name = isset( $data['account_name'] ) ? $data['account_name'] : '';
    $tab    = isset( $data['tab'] ) ? $data['tab'] : '';
    $sheet_id     = isset( $data['sheet_id'] ) ? $data['sheet_id'] : '';
    $task       = $record['task'];

    unset( $data['credId'], $data['account_name'], $data['tab'], $data['sheet_id'] );

    if( $task == 'subscribe' ) {
        $endpoint = $account_name . '/' . $tab . '/' . $sheet_id;

        $subscription_data = array();

        foreach ( $data as $key => $value ) {
            if( $value ) {
                $pairs = explode( '||', $value );
                foreach ( $pairs as $pair ) {
                    $exploded = explode( '=', $pair, 2 );
                    $key   = trim( $exploded[0] );
                    $parsed_value = isset( $exploded[1] ) && $exploded[1] ? adfoin_get_parsed_values( $exploded[1], $posted_data ) : '';

                    if ( $parsed_value ) {
                        $subscription_data[ $key ] = $parsed_value;
                    }
                }
            }
        }

        adfoin_ragic_request( $endpoint, 'POST', $subscription_data, $record, $cred_id );
    }
}


