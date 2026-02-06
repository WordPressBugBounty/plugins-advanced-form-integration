<?php

add_filter( 'adfoin_action_providers', 'adfoin_revue_actions', 10, 1 );

function adfoin_revue_actions( $actions ) {

    $actions['revue'] = array(
        'title' => __( 'Revue', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Create Subscriber', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get Revue credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_key' key, or empty string if not found
 */
function adfoin_revue_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_key = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'revue' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_key = $single['api_key'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_key = get_option( 'adfoin_revue_api_key' ) ? get_option( 'adfoin_revue_api_key' ) : '';
    }

    return array(
        'api_key' => $api_key
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_revue_settings_tab', 10, 1 );

function adfoin_revue_settings_tab( $providers ) {
    $providers['revue'] = __( 'Revue', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_revue_settings_view', 10, 1 );

function adfoin_revue_settings_view( $current_tab ) {
    if( $current_tab != 'revue' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_key = get_option( 'adfoin_revue_api_key' ) ? get_option( 'adfoin_revue_api_key' ) : '';
    
    $existing_creds = adfoin_read_credentials( 'revue' );

    if ( $old_api_key && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account (Legacy)',
            'api_key' => $old_api_key
        );
        adfoin_save_credentials( 'revue', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_key',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Key', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Revue account settings.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to Account Settings > Integrations.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Copy your API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter the API Key in the field above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'revue', 'Revue', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_revue_credentials', 'adfoin_get_revue_credentials' );
function adfoin_get_revue_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'revue' );
}

add_action( 'wp_ajax_adfoin_save_revue_credentials', 'adfoin_save_revue_credentials' );
function adfoin_save_revue_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'revue', array( 'api_key' ) );
}

add_action( 'wp_ajax_adfoin_get_revue_credentials_list', 'adfoin_revue_get_credentials_list_ajax' );
function adfoin_revue_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_key', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'revue', $fields );
}

add_action( 'admin_post_adfoin_save_revue_api_key', 'adfoin_save_revue_api_key', 10, 0 );

function adfoin_save_revue_api_key() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_revue_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = sanitize_text_field( $_POST["adfoin_revue_api_key"] );

    // Save tokens
    update_option( "adfoin_revue_api_key", $api_key );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=revue" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_revue_js_fields', 10, 1 );

function adfoin_revue_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_revue_action_fields' );

function adfoin_revue_action_fields() {
    ?>
    <script type="text/template" id="revue-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row"><?php esc_html_e( 'Revue Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=revue' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Subscriber Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Disable Double Opt-In', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doptin]" value="true" v-model="fielddata.doptin">
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>
    <?php
}

/*
 * Handles sending data to Revue API
 */
function adfoin_revue_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data["field_data"];
    $task = $record["task"];
    
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $credentials = adfoin_revue_get_credentials( $cred_id );
    $api_key = $credentials['api_key'];

    if( !$api_key ) {
        return;
    }

    if( $task == "subscribe" ) {
        $email      = empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data );
        $first_name = empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data );
        $last_name  = empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data );
        $doptin     = $data["doptin"];

        $data = array(
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name
        );

        if("true" == $doptin) {
            $data['double_opt_in'] = false;
        }

        $url = "https://www.getrevue.co/api/v2/subscribers";

        $args = array(

            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Token token="' . $api_key . '"'
            ),
            'body' => json_encode( $data )
        );

        $return = wp_remote_post( $url, $args );

        adfoin_add_to_log( $return, $url, $args, $record );
    }

    return;
}