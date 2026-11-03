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
                    <p class="description" v-if="listError" style="color: #b32d2e;">{{ listError }}</p>
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
 * Get Elastic Email lists (public list ID => name).
 */
function adfoin_get_elasticemail_list() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $lists   = adfoin_elasticemail_lists( $cred_id );

    if ( is_wp_error( $lists ) ) {
        wp_send_json_error( sprintf( __( 'Could not load Elastic Email lists: %s', 'advanced-form-integration' ), $lists->get_error_message() ) );
    }

    wp_send_json_success( $lists );
}

function adfoin_elasticemail_api_key( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'elasticemail', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fall back to the legacy single-account option.
    if ( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_elasticemail_api_key' ) ? get_option( 'adfoin_elasticemail_api_key' ) : '';
    }

    return $api_key;
}

function adfoin_elasticemail_v4_build_request( $endpoint, $method, $data, $cred_id ) {
    $api_key = adfoin_elasticemail_api_key( $cred_id );

    if ( !$api_key ) {
        return new WP_Error( 'elasticemail_no_account', __( 'No Elastic Email account is selected, or the account has no API Key. Check the integration and AFI Settings > Elastic Email.', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-ElasticEmail-ApiKey' => $api_key,
            'Content-Type'          => 'application/json',
        ),
    );

    if ( $data !== null && in_array( $method, array( 'POST', 'PUT' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    return array( 'https://api.elasticemail.com/v4/' . ltrim( $endpoint, '/' ), $args );
}

/*
 * Request to Elastic Email's current (v4) API.
 */
function adfoin_elasticemail_v4_request( $endpoint, $method = 'GET', $data = null, $cred_id = '' ) {
    $request = adfoin_elasticemail_v4_build_request( $endpoint, $method, $data, $cred_id );

    if ( is_wp_error( $request ) ) {
        return $request;
    }

    return wp_remote_request( $request[0], $request[1] );
}

function adfoin_elasticemail_error_message( $response ) {
    if ( is_wp_error( $response ) ) {
        return $response->get_error_message();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( !empty( $body['Error'] ) ) {
        return $body['Error'];
    }

    if ( !empty( $body['error'] ) ) {
        return $body['error'];
    }

    return wp_remote_retrieve_response_code( $response ) . ' ' . wp_remote_retrieve_response_message( $response );
}

/*
 * Logs a v4 response, or a v2 one ($v2 = true; v2 reports failures as HTTP 200
 * with "success": false, which is turned into a 400 here).
 */
function adfoin_elasticemail_log( $response, $endpoint, $method, $data, $record, $cred_id, $notes = array(), $v2 = false ) {
    if ( !is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $v2 && isset( $body['success'] ) && !$body['success'] ) {
            $response['response']['code'] = 400;
        }

        if ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
            $response['response']['message'] = adfoin_elasticemail_error_message( $response );
        } elseif ( $notes ) {
            $response['response']['message'] = sprintf( 'OK (%s)', implode( '; ', $notes ) );
        }
    }

    $request = $v2 ? null : adfoin_elasticemail_v4_build_request( $endpoint, $method, $data, $cred_id );

    if ( $v2 || is_wp_error( $request ) ) {
        $request = array( ( $v2 ? 'https://api.elasticemail.com/v2/' : 'https://api.elasticemail.com/v4/' ) . $endpoint, array( 'method' => $method, 'body' => $data ) );
    }

    adfoin_add_to_log( $response, $request[0], $request[1], $record );
}

function adfoin_elasticemail_log_skipped( $record, $message ) {
    adfoin_add_to_log( new WP_Error( 'elasticemail_not_sent', $message ), 'https://api.elasticemail.com/v4/contacts', array( 'method' => 'POST' ), $record );
}

/*
 * The account's lists as public list ID => name, or WP_Error.
 */
function adfoin_elasticemail_lists( $cred_id ) {
    $response = adfoin_elasticemail_v4_request( 'lists', 'GET', null, $cred_id );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
        return new WP_Error( 'elasticemail_lists', adfoin_elasticemail_error_message( $response ) );
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $lists = array();

    foreach ( is_array( $body ) ? $body : array() as $list ) {
        if ( isset( $list['PublicListID'], $list['ListName'] ) ) {
            $lists[ $list['PublicListID'] ] = $list['ListName'];
        }
    }

    return $lists;
}

/*
 * Returns the sanitized email, or '' after logging why the submission was skipped.
 */
function adfoin_elasticemail_parse_email( $raw, $record ) {
    $raw   = trim( (string) $raw );
    $email = sanitize_email( $raw );

    if ( !$email ) {
        adfoin_elasticemail_log_skipped(
            $record,
            $raw === ''
                ? __( 'Not sent: the mapped email field is empty.', 'advanced-form-integration' )
                : sprintf( __( 'Not sent: "%s" is not a valid email address.', 'advanced-form-integration' ), $raw )
        );
    }

    return $email;
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

    $data = $record_data["field_data"];

    if ( $record["task"] != "subscribe" ) {
        return;
    }

    $contact = adfoin_elasticemail_base_contact( $data, $posted_data, $record );

    if ( !$contact ) return;

    adfoin_elasticemail_add_contact( $contact, $data, $record );
}

/*
 * Credential ID of the integration; older integrations without one use the first account.
 */
function adfoin_elasticemail_cred_id( $data ) {
    if ( !empty( $data['credId'] ) ) {
        return $data['credId'];
    }

    $all_credentials = adfoin_read_credentials( 'elasticemail' );

    return !empty( $all_credentials ) ? $all_credentials[0]['id'] : '';
}

/*
 * Contact body with email and name, shared by the free and PRO actions.
 * Returns false (after logging why) when the submission can't be sent.
 */
function adfoin_elasticemail_base_contact( $data, $posted_data, $record ) {
    if ( empty( $data['listId'] ) ) {
        adfoin_elasticemail_log_skipped( $record, __( 'Not sent: no Elastic Email list is selected in the integration.', 'advanced-form-integration' ) );
        return false;
    }

    $email = adfoin_elasticemail_parse_email( adfoin_get_parsed_values( isset( $data['email'] ) ? $data['email'] : '', $posted_data ), $record );

    if ( !$email ) return false;

    $contact = array( 'Email' => $email );

    foreach ( array( 'firstName' => 'FirstName', 'lastName' => 'LastName' ) as $key => $api_key ) {
        $value = trim( (string) adfoin_get_parsed_values( isset( $data[ $key ] ) ? $data[ $key ] : '', $posted_data ) );

        if ( $value !== '' ) {
            $contact[ $api_key ] = $value;
        }
    }

    return $contact;
}

/*
 * Adds the contact to the list, or updates it when the email exists. Elastic
 * Email keeps people who unsubscribed unsubscribed; that is noted in the log.
 * Lists are saved by public ID, but v4 adds to a list by name.
 */
function adfoin_elasticemail_add_contact( $contact, $data, $record, $notes = array() ) {
    $cred_id = adfoin_elasticemail_cred_id( $data );
    $lists   = adfoin_elasticemail_lists( $cred_id );

    if ( is_wp_error( $lists ) ) {
        adfoin_add_to_log( $lists, 'https://api.elasticemail.com/v4/lists', array( 'method' => 'GET' ), $record );
        return $lists;
    }

    if ( !isset( $lists[ $data['listId'] ] ) ) {
        adfoin_elasticemail_log_skipped( $record, __( 'Not sent: the selected Elastic Email list no longer exists. Pick the list again in the integration.', 'advanced-form-integration' ) );
        return false;
    }

    $endpoint = 'contacts?listnames=' . rawurlencode( $lists[ $data['listId'] ] );
    $body     = array( $contact );
    $response = adfoin_elasticemail_v4_request( $endpoint, 'POST', $body, $cred_id );

    if ( !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
        $result = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $result[0]['Status'] ) && $result[0]['Status'] !== 'Active' ) {
            $notes[] = sprintf( __( 'contact status is %s in Elastic Email, not re-subscribed', 'advanced-form-integration' ), $result[0]['Status'] );
        }

        // Elastic Email drops custom fields that don't exist in the account.
        if ( !empty( $contact['CustomFields'] ) ) {
            $saved   = isset( $result[0]['CustomFields'] ) && is_array( $result[0]['CustomFields'] ) ? array_change_key_case( $result[0]['CustomFields'] ) : array();
            $missing = array();

            foreach ( array_keys( $contact['CustomFields'] ) as $name ) {
                if ( !array_key_exists( strtolower( $name ), $saved ) ) {
                    $missing[] = $name;
                }
            }

            if ( $missing ) {
                $notes[] = sprintf( __( 'custom fields not saved, create them in Elastic Email first: %s', 'advanced-form-integration' ), implode( ', ', $missing ) );
            }
        }
    }

    adfoin_elasticemail_log( $response, $endpoint, 'POST', $body, $record, $cred_id, $notes );

    return $response;
}

/*
 * Request to Elastic Email's older v2 API, still used for changing a
 * contact's status (v4 ignores status changes). Adds apikey as a query
 * parameter, retries once on HTTP 429 and logs when a record is given.
 */
function adfoin_elasticemail_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'elasticemail', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Backward compatibility: fall back to the legacy single-account option.
    if( empty( $api_key ) ) {
        $api_key = get_option( 'adfoin_elasticemail_api_key' ) ? get_option( 'adfoin_elasticemail_api_key' ) : '';
    }

    if( ! $api_key ) {
        return new WP_Error( 'elasticemail_no_account', __( 'No Elastic Email account is selected, or the account has no API Key. Check the integration and AFI Settings > Elastic Email.', 'advanced-form-integration' ) );
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