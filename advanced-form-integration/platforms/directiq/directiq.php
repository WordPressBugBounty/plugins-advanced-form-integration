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

// Legacy single-account import: surfaces old `adfoin_directiq_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'directiq', array(
            'apiKey' => 'adfoin_directiq_api_key',
            'apiSecret' => 'adfoin_directiq_api_secret',
        ) );
    }
}, 20 );

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_directiq_api_key', 'adfoin_save_directiq_api_key', 10, 0 );

function adfoin_save_directiq_api_key() {
    // Security Check
    // Authorization check
    adfoin_require_manage_options();

    if ( ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_directiq_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_key    = isset( $_POST['adfoin_directiq_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_directiq_api_key'] ) ) : '';
    $api_secret = isset( $_POST['adfoin_directiq_api_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_directiq_api_secret'] ) ) : '';

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
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=directiq' ); ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
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
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                    <p class="description" v-if="listError" style="color: #b32d2e;">{{ listError }}</p>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>

            <?php adfoin_pro_feature_notice( 'subscribe', 'DirectIQ [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

function adfoin_directiq_build_request($endpoint, $method, $data, $cred_id) {
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
        return new WP_Error( 'directiq_no_account', __( 'No DirectIQ account is selected, or the account has no API Key or API Secret. Check the integration and AFI Settings > DirectIQ.', 'advanced-form-integration' ) );
    }

    $method = strtoupper($method);

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
        ),
    );

    if ('POST' === $method || 'PUT' === $method) {
        $args['body'] = wp_json_encode($data);
    }

    return array( 'https://rest.directiq.com/' . ltrim($endpoint, '/'), $args );
}

function adfoin_directiq_request($endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '') {
    $request = adfoin_directiq_build_request( $endpoint, $method, $data, $cred_id );

    if ( is_wp_error( $request ) ) {
        if ( $record ) {
            adfoin_add_to_log( $request, $endpoint, array( 'method' => $method, 'body' => $data ), $record );
        }

        return $request;
    }

    list( $url, $args ) = $request;

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

/*
 * DirectIQ's validation errors as one line, e.g. "Email: The Email field is required."
 */
function adfoin_directiq_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $parts = array();

    foreach ( isset( $body['errors'] ) && is_array( $body['errors'] ) ? $body['errors'] : array() as $field => $messages ) {
        $parts[] = $field . ': ' . implode( ' ', (array) $messages );
    }

    if ( !$parts && !empty( $body['title'] ) ) {
        $parts[] = $body['title'];
    }

    return $parts ? implode( ' ', $parts ) : wp_remote_retrieve_response_code( $response ) . ' ' . wp_remote_retrieve_response_message( $response );
}

function adfoin_directiq_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'directiq_not_sent', $message ), 'https://rest.directiq.com/subscription/subscribe', array( 'method' => 'POST' ), $record );
}

/*
 * The account's lists as id => name, or WP_Error.
 */
function adfoin_directiq_lists( $cred_id ) {
    $response = adfoin_directiq_request( 'contacts/lists/list', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
        return new WP_Error( 'directiq_lists', adfoin_directiq_error_message( $response ) );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    return is_array( $body ) ? wp_list_pluck( $body, 'name', 'id' ) : array();
}

add_action( 'wp_ajax_adfoin_get_directiq_list', 'adfoin_get_directiq_list', 10, 0 );
/*
 * Get DirectIQ subscriber lists
 */
function adfoin_get_directiq_list() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists   = adfoin_directiq_lists( $cred_id );

    if ( is_wp_error( $lists ) ) {
        wp_send_json_error( sprintf( __( 'Could not load DirectIQ lists: %s', 'advanced-form-integration' ), $lists->get_error_message() ) );
    }

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

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
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

    if( $task != "subscribe" ) {
        return;
    }

    $contact = adfoin_directiq_base_contact( $data, $posted_data, $record );

    if ( !$contact ) return;

    adfoin_directiq_add_contact( $data['listId'], $contact, $record, $cred_id );
}

/*
 * Email and name, shared by the free and PRO actions.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_directiq_base_contact( $data, $posted_data, $record ) {
    if ( empty( $data['listId'] ) ) {
        adfoin_directiq_log_skipped( $record, __( 'Not sent: no DirectIQ list is selected in the integration.', 'advanced-form-integration' ) );
        return false;
    }

    $raw   = trim( (string) adfoin_get_parsed_values( isset( $data['email'] ) ? $data['email'] : '', $posted_data ) );
    $email = sanitize_email( $raw );

    if ( !$email ) {
        adfoin_directiq_log_skipped(
            $record,
            $raw === ''
                ? __( 'Not sent: the mapped email field is empty.', 'advanced-form-integration' )
                : sprintf( __( 'Not sent: "%s" is not a valid email address.', 'advanced-form-integration' ), $raw )
        );
        return false;
    }

    $contact = array( 'email' => $email );

    foreach ( array( 'firstName', 'lastName' ) as $key ) {
        $value = trim( (string) adfoin_get_parsed_values( isset( $data[ $key ] ) ? $data[ $key ] : '', $posted_data ) );

        if ( $value !== '' ) {
            $contact[ $key ] = $value;
        }
    }

    return $contact;
}

/*
 * Adds the contact to the list, or updates it when the email exists (tags are
 * added to the existing ones). Shared by the free and PRO actions; logs once.
 */
function adfoin_directiq_add_contact( $list_id, $contact, $record, $cred_id, $notes = array() ) {
    $endpoint = 'contacts/lists/addcontact/' . rawurlencode( $list_id );
    $response = adfoin_directiq_request( $endpoint, 'POST', $contact, array(), $cred_id );

    if ( !is_wp_error( $response ) ) {
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code == 404 ) {
            $response['response']['message'] = sprintf( __( 'The DirectIQ list %s no longer exists. Pick the list again in the integration.', 'advanced-form-integration' ), $list_id );
        } elseif ( $code >= 300 ) {
            $response['response']['message'] = adfoin_directiq_error_message( $response );
        } elseif ( $notes ) {
            $response['response']['message'] = sprintf( 'OK (%s)', implode( '; ', $notes ) );
        }
    }

    $request = adfoin_directiq_build_request( $endpoint, 'POST', $contact, $cred_id );

    adfoin_add_to_log(
        $response,
        is_wp_error( $request ) ? $endpoint : $request[0],
        is_wp_error( $request ) ? array( 'method' => 'POST', 'body' => $contact ) : $request[1],
        $record
    );

    return $response;
}
