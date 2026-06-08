<?php

add_filter( 'adfoin_action_providers', 'adfoin_elasticemail_actions', 10, 1 );

function adfoin_elasticemail_actions( $actions ) {

    $actions['elasticemail'] = array(
        'title' => __( 'Elastic Email', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact To List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_elasticemail_settings_tab', 10, 1 );

function adfoin_elasticemail_settings_tab( $providers ) {
    $providers['elasticemail'] = __( 'Elastic Email', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_elasticemail_settings_view', 10, 1 );

function adfoin_elasticemail_settings_view( $current_tab ) {
    if( $current_tab != 'elasticemail' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiKey', 
            'label' => __( 'Elastic Email API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Please go to Settings > API then create API Key', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'elasticemail', __( 'Elastic Email', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_elasticemail_credentials', 'adfoin_get_elasticemail_credentials', 10, 0 );
/*
 * Get Elastic Email credentials
 */
function adfoin_get_elasticemail_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'elasticemail' );
}

add_action( 'wp_ajax_adfoin_save_elasticemail_credentials', 'adfoin_save_elasticemail_credentials', 10, 0 );
/*
 * Save Elastic Email credentials
 */
function adfoin_save_elasticemail_credentials() {
    // Security Check (capability + nonce, with isset/unslash/sanitize)
    adfoin_verify_nonce();

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $api_key = isset( $_POST['apiKey'] ) ? sanitize_text_field( wp_unslash( $_POST['apiKey'] ) ) : '';

    if( !$api_key ) {
        wp_send_json_error( array( 'message' => __( 'API Key is required', 'advanced-form-integration' ) ) );
    }

    // Fetch publicAccountId from API
    $url = "https://api.elasticemail.com/v2/account/load?apikey={$api_key}";
    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Accept' => '*/*'
        )
    );

    $data = wp_remote_request( $url, $args );

    if( is_wp_error( $data ) ) {
        wp_send_json_error( array( 'message' => __( 'Failed to verify API Key', 'advanced-form-integration' ) ) );
    }

    $body = json_decode( $data["body"] );
    
    if( !isset( $body->data->publicaccountid ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid API Key', 'advanced-form-integration' ) ) );
    }

    $public_account_id = $body->data->publicaccountid;

    // Store both apiKey and publicAccountId
    $_POST['publicAccountId'] = $public_account_id;

    ADFOIN_Account_Manager::ajax_save_credentials( 'elasticemail', array( 'apiKey', 'publicAccountId' ) );
}

/*
 * Elastic Email Credentials List
 */
function adfoin_elasticemail_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'elasticemail' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

// Legacy single-account import: surfaces old `adfoin_elasticemail_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'elasticemail', array(
            'apiKey' => 'adfoin_elasticemail_api_key',
            'publicAccountId' => 'adfoin_elasticemail_public_accountid',
        ) );
    }
}, 20 );

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_elasticemail_api_key', 'adfoin_save_elasticemail_api_key', 10, 0 );

function adfoin_save_elasticemail_api_key() {
    // Security Check
    // Authorization check
    adfoin_require_manage_options();

    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_elasticemail_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key = sanitize_text_field( wp_unslash( $_POST["adfoin_elasticemail_api_key"] ) );

    // Save tokens
    update_option( "adfoin_elasticemail_api_key", $api_key );

    $url = "https://api.elasticemail.com/v2/account/load?apikey={$api_key}";

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Accept' => '*/*'
        )
    );

    $data = wp_remote_request( $url, $args );

    if( !is_wp_error( $data ) ) {
        $body = json_decode( $data["body"] );
        $public_account_id = $body->data->publicaccountid;

        update_option( "adfoin_elasticemail_public_accountid", $public_account_id );
    }

        advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=elasticemail" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_elasticemail_js_fields', 10, 1 );

function adfoin_elasticemail_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_elasticemail_action_fields' );

function adfoin_elasticemail_action_fields() {
?>
    <script type="text/template" id="elasticemail-action-template">
        <table class="form-table">
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
                        <?php esc_attr_e( 'Elastic Email Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=elasticemail' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                    
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
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Elastic Email [PRO]', 'custom fields' ); ?>
            
        </table>
    </script>


<?php
}

add_action( 'wp_ajax_adfoin_get_elasticemail_list', 'adfoin_get_elasticemail_list', 10, 0 );

/*
 * Get Elastic Email subscriber lists
 */
function adfoin_get_elasticemail_list() {
    // Security Check (capability + nonce, with isset/unslash/sanitize)
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $data = adfoin_elasticemail_request( 'list/list', 'GET', array(), array(), $cred_id );

    if( is_wp_error( $data ) || empty( $data['body'] ) ) {
        wp_send_json_error();
    }

    $body = json_decode( $data['body'] );

    if( ! isset( $body->data ) || ! is_array( $body->data ) ) {
        wp_send_json_error();
    }

    $lists = wp_list_pluck( $body->data, 'listname', 'publiclistid' );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_elasticemail_job_queue', 'adfoin_elasticemail_job_queue', 10, 1 );

function adfoin_elasticemail_job_queue( $data ) {
    adfoin_elasticemail_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Elastic Email API
 */
function adfoin_elasticemail_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data["field_data"];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = $data["listId"];
    $task    = $record["task"];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'elasticemail' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    $credentials = adfoin_get_credentials_by_id( 'elasticemail', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $public_acc = isset( $credentials['publicAccountId'] ) ? $credentials['publicAccountId'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_elasticemail_api_key' ) ? get_option( 'adfoin_elasticemail_api_key' ) : "";
    }

    if( empty( $public_acc ) ) {
        $public_acc = get_option( 'adfoin_elasticemail_public_accountid' ) ? get_option( 'adfoin_elasticemail_public_accountid' ) : "";
    }

    if(!$api_key || !$public_acc ) {
        return;
    }

    if( $task == "subscribe" ) {

        $email      = empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data );
        $first_name = empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data );
        $last_name  = empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data );

        $body = array(
            'publiclistid'    => $list_id,
            'firstName'       => $first_name,
            'lastName'        => $last_name,
            'email'           => $email,
            'publicAccountID' => $public_acc,
        );

        adfoin_elasticemail_request( 'contact/add', 'POST', $body, $record, $cred_id );
    }
}

/*
 * Shared Elastic Email API request wrapper. Adds apikey as a query parameter
 * (the v2 pattern), defaults to form-urlencoded for POST bodies, retries once
 * on HTTP 429, and centralises logging.
 */
function adfoin_elasticemail_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'elasticemail', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fall back to the legacy single-account option.
    if( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_elasticemail_api_key' ) ? get_option( 'adfoin_elasticemail_api_key' ) : '';
    }

    if( ! $api_key ) {
        return array();
    }

    $base_url = 'https://api.elasticemail.com/v2/';
    $url      = $base_url . $endpoint . '?apikey=' . rawurlencode( $api_key );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(),
    );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $args['body'] = $data;
    } else {
        $args['headers']['Accept'] = '*/*';
        if( ! empty( $data ) ) {
            $url .= '&' . http_build_query( $data );
        }
    }

    $response = wp_remote_request( $url, $args );

    // Defensive 429 retry: EE v2 doesn't formally document a per-token rate
    // limit, but a single short-backoff retry is harmless if the API ever
    // throttles. Honors Retry-After if present.
    if( 429 == (int) wp_remote_retrieve_response_code( $response ) ) {
        $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
        $wait        = is_numeric( $retry_after ) ? (int) $retry_after : 2;

        if( $wait < 1 ) {
            $wait = 1;
        }
        if( $wait > 30 ) {
            $wait = 30;
        }

        sleep( $wait );
        $response = wp_remote_request( $url, $args );
    }

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}