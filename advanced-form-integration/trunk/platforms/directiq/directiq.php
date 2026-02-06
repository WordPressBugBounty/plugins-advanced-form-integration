<?php

add_filter( 'adfoin_action_providers', 'adfoin_directiq_actions', 10, 1 );

function adfoin_directiq_actions( $actions ) {

    $actions['directiq'] = array(
        'title' => __( 'DirectIQ', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Add Contact To List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_directiq_settings_tab', 10, 1 );

function adfoin_directiq_settings_tab( $providers ) {
    $providers['directiq'] = __( 'DirectIQ', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_directiq_settings_view', 10, 1 );

function adfoin_directiq_settings_view( $current_tab ) {
    if ( $current_tab != 'directiq' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        ),
        array( 
            'name' => 'apiSecret', 
            'label' => __( 'API Secret', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Secret', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Enter the API Key and API Secret provided by DirectIQ.', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'directiq', __( 'DirectIQ', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_directiq_credentials', 'adfoin_get_directiq_credentials', 10, 0 );
/*
 * Get DirectIQ credentials
 */
function adfoin_get_directiq_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'directiq' );
}

add_action( 'wp_ajax_adfoin_save_directiq_credentials', 'adfoin_save_directiq_credentials', 10, 0 );
/*
 * Save DirectIQ credentials
 */
function adfoin_save_directiq_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'directiq', array( 'apiKey', 'apiSecret' ) );
}

/*
 * DirectIQ Credentials List
 */
function adfoin_directiq_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'directiq' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_directiq_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_directiq_modify_credentials( $credentials, $platform ) {
    if ( 'directiq' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_directiq_api_key' );
        $api_secret = get_option( 'adfoin_directiq_api_secret' );

        if( $api_key && $api_secret ) {
            $credentials = array(
                array(
                    'id'        => 'legacy',
                    'title'     => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiKey'    => $api_key,
                    'apiSecret' => $api_secret
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_directiq_api_key', 'adfoin_save_directiq_api_key', 10, 0 );

function adfoin_save_directiq_api_key() {
    // Security Check
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_directiq_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key    = isset( $_POST['adfoin_directiq_api_key'] ) ? sanitize_text_field( $_POST['adfoin_directiq_api_key'] ) : '';
    $api_secret = isset( $_POST['adfoin_directiq_api_secret'] ) ? sanitize_text_field( $_POST['adfoin_directiq_api_secret'] ) : '';

    // Save credentials
    update_option( "adfoin_directiq_api_key", $api_key );
    update_option( "adfoin_directiq_api_secret", $api_secret );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=directiq" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_directiq_js_fields', 10, 1 );

function adfoin_directiq_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_directiq_action_fields' );

function adfoin_directiq_action_fields() {
    ?>
    <script type="text/template" id="directiq-action-template">
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
                        <?php esc_attr_e( 'DirectIQ Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=directiq' ); ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'DirectIQ List', 'advanced-form-integration' ); ?>
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

function adfoin_directiq_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '') {
    $credentials = adfoin_get_credentials_by_id( 'directiq', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $api_secret = isset( $credentials['apiSecret'] ) ? $credentials['apiSecret'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_key ) ) {
        $api_key = get_option('adfoin_directiq_api_key', '');
    }

    if( empty( $api_secret ) ) {
        $api_secret = get_option('adfoin_directiq_api_secret', '');
    }

    if( !$api_key || !$api_secret ) {
        return array();
    }

    $base_url = 'https://rest.directiq.com/';
    $url = $base_url . ltrim($endpoint, '/');

    $method = strtoupper($method);

    // Build Basic Auth header using api key and api secret
    $auth_value = base64_encode( $api_key . ':' . $api_secret );

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . $auth_value,
        ),
    );

    if ('POST' === $method || 'PUT' === $method) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_directiq_list', 'adfoin_get_directiq_list', 10, 0 );
/*
 * Get DirectIQ subscriber lists
 */
function adfoin_get_directiq_list() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $data = adfoin_directiq_request('contacts/lists/list', 'GET', array(), array(), $cred_id);
    $body = json_decode( wp_remote_retrieve_body( $data ) );
    if ( is_wp_error( $data ) || empty( $body ) ) {
        wp_send_json_error();
    }

    $lists = wp_list_pluck( $body, 'name', 'id' );

    wp_send_json_success( $lists );
}

add_action( 'adfoin_directiq_job_queue', 'adfoin_directiq_job_queue', 10, 1 );

function adfoin_directiq_job_queue( $data ) {
    adfoin_directiq_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to DirectIQ API
 */
function adfoin_directiq_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data = $record_data["field_data"];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record["task"];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'directiq' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == "subscribe" ) {
        $list_id    = $data["listId"];

        $data = array_filter(array(
            'listId'    => $list_id,
            'Email'     => empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ),
            'FirstName' => empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data ),
            'LastName'  => empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data )
        ));

        $return = adfoin_directiq_request( 'subscription/subscribe', 'POST', $data, $record, $cred_id );
    }

    return;
}