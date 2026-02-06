<?php

add_filter( 'adfoin_action_providers', 'adfoin_nimble_actions', 10, 1 );

function adfoin_nimble_actions( $actions ) {

    $actions['nimble'] = array(
        'title' => __( 'Nimble', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact'   => __( 'Add New Contact', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

/**
 * Get Nimble credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key' key, or empty string if not found
 */
function adfoin_nimble_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_key = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'nimble' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = get_option( 'adfoin_nimble_api_key' ) ? get_option( 'adfoin_nimble_api_key' ) : '';
    }

    return array(
        'api_key' => $api_key
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_nimble_settings_tab', 10, 1 );

function adfoin_nimble_settings_tab( $providers ) {
    $providers['nimble'] = __( 'Nimble', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_nimble_settings_view', 10, 1 );

function adfoin_nimble_settings_view( $current_tab ) {
    if( $current_tab != 'nimble' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = get_option( 'adfoin_nimble_api_key' );
    $existing_creds = adfoin_read_credentials( 'nimble' );

    if ( $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'api_key' => $old_api_key
        );
        adfoin_save_credentials( 'nimble', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter your API Token', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to My Account > API Tokens in your Nimble account.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Generate a new API Token.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the API Token above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'nimble', 'Nimble', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_nimble_credentials', 'adfoin_get_nimble_credentials' );
function adfoin_get_nimble_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'nimble' );
}

add_action( 'wp_ajax_adfoin_save_nimble_credentials', 'adfoin_save_nimble_credentials' );
function adfoin_save_nimble_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'nimble', array( 'api_key' ) );
}

add_action( 'wp_ajax_adfoin_get_nimble_credentials_list', 'adfoin_nimble_get_credentials_list_ajax' );
function adfoin_nimble_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_key', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'nimble', $fields );
}

add_action( 'adfoin_action_fields', 'adfoin_nimble_action_fields' );

function adfoin_nimble_action_fields() {
    ?>
    <script type="text/template" id="nimble-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row"><?php esc_html_e( 'Nimble Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=nimble' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Contact Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            
        </table>
    </script>
    <?php
}

function adfoin_nimble_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {

    $credentials = adfoin_nimble_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];

    if(!$api_key ) {
        return new WP_Error( 'missing_credentials', 'API Key is missing' );
    }

    $base_url = 'https://api.nimble.com/api/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        )
    );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_nimble_job_queue', 'adfoin_nimble_job_queue', 10, 1 );

function adfoin_nimble_job_queue( $data ) {
    adfoin_nimble_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Nimble API
 */
function adfoin_nimble_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( 'cl', $record_data['action_data']) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    if( $task == 'add_contact' ) {
        $email       = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
        $first_name  = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
        $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );

        $data = array(
            'record_type' => 'person',
            'fields' => array(
                'email' => array(
                    array(
                        'value' => trim( $email )
                    )
                ),
                'first name' => array(
                    array(
                        'value' => $first_name
                    )
                ),
                'last name' => array(
                    array(
                        'value' => $last_name
                    )
                )
            )
        );

        $return = adfoin_nimble_request( 'contact', 'POST', $data, $record, $cred_id );

    }

    return;
}