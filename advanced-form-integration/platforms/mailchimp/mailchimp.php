<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailchimp_actions', 10, 1 );

function adfoin_mailchimp_actions( $actions ) {

    $actions['mailchimp'] = array(
        'title' => __( 'Mailchimp', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To List', 'advanced-form-integration' ),
            'unsubscribe' => __( 'Unsubscribe From List', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailchimp_settings_tab', 10, 1 );

function adfoin_mailchimp_settings_tab( $providers ) {
    $providers['mailchimp'] = __( 'Mailchimp', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailchimp_settings_view', 10, 1 );

function adfoin_mailchimp_settings_view( $current_tab ) {
    if( $current_tab != 'mailchimp' ) {
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
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Please go to Account > Extras > API Keys', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'mailchimp', __( 'Mailchimp', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailchimp_credentials', 'adfoin_get_mailchimp_credentials', 10, 0 );
/*
 * Get Mailchimp credentials
 */
function adfoin_get_mailchimp_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailchimp' );
}

add_action( 'wp_ajax_adfoin_save_mailchimp_credentials', 'adfoin_save_mailchimp_credentials', 10, 0 );
/*
 * Save Mailchimp credentials
 */
function adfoin_save_mailchimp_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailchimp', array( 'apiKey' ) );
}

/*
 * Mailchimp Credentials List
 */
function adfoin_mailchimp_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'mailchimp' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

// Legacy single-account import: surfaces old `adfoin_mailchimp_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'mailchimp', array(
            'apiKey' => 'adfoin_mailchimp_api_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_add_js_fields', 'adfoin_mailchimp_js_fields', 10, 1 );

function adfoin_mailchimp_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_mailchimp_action_fields' );

function adfoin_mailchimp_action_fields() {
?>
    <script type="text/template" id="mailchimp-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :key="cred.id" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailchimp' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Mailchimp Audience', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php _e( 'Select Audience...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Double Opt-In', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" value="true" name="fieldData[doubleoptin]" v-model="fielddata.doubleoptin">
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Mailchimp [PRO]', 'custom fields and tags' ); ?>
            
        </table>
    </script>


<?php
}

add_action( 'wp_ajax_adfoin_get_mailchimp_list', 'adfoin_get_mailchimp_list', 10, 0 );

/*
 * Get Mailchimp subscriber lists
 */
function adfoin_get_mailchimp_list() {
    // Security Check
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $data = adfoin_mailchimp_request( 'lists?count=1000', 'GET', array(), array(), $cred_id );

    if( !is_wp_error( $data ) ) {
        $body  = json_decode( $data["body"] );
        $lists = wp_list_pluck( $body->lists, 'name', 'id' );

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

/**
 * Makes a request to the Mailchimp API.
 *
 * @param string $endpoint The API endpoint to request.
 * @param string $method The HTTP method to use for the request (e.g., GET, POST, PUT).
 * @param array $data The data to send in the request body (for POST and PUT requests).
 * @param array $record The record to log (optional).
 * @param string $cred_id The credential ID to use.
 *
 * @return mixed The response from the Mailchimp API.
 */
function adfoin_mailchimp_request( $endpoint, $method, $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mailchimp', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Fallback to old option for backward compatibility
    if( !$api_key ) {
        $api_key = get_option( 'adfoin_mailchimp_api_key' ) ? get_option( 'adfoin_mailchimp_api_key' ) : '';
    }

    if( !$api_key ) {
        return new WP_Error( 'missing_api_key', __( 'Mailchimp API key not found', 'advanced-form-integration' ) );
    }

    $parts  = explode( '-', $api_key );
    $prefix = isset( $parts[1] ) ? $parts[1] : '';

    if ( empty( $prefix ) ) {
        return new WP_Error( 'invalid_api_key', __( 'Mailchimp API key is invalid', 'advanced-form-integration' ) );
    }

    $base_url = "https://{$prefix}.api.mailchimp.com/3.0/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            // Mailchimp expects Basic auth where the API key is the password part
            'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
        )
    );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'adfoin_mailchimp_job_queue', 'adfoin_mailchimp_job_queue', 10, 1 );

function adfoin_mailchimp_job_queue( $data ) {
    adfoin_mailchimp_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Mailchimp API
 */
function adfoin_mailchimp_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data    = $record_data["field_data"];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = $data["listId"];
    $dopt    = $data["doubleoptin"];
    $task    = $record["task"];
    $email   = empty( $data["email"] ) ? "" : adfoin_get_parsed_values($data["email"], $posted_data);

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'mailchimp' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == "subscribe" ) {

        // Bail early on misconfiguration rather than firing a doomed API call.
        if ( empty( $email ) || empty( $list_id ) ) {
            return;
        }

        $first_name   = empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values($data["firstName"], $posted_data);
        $last_name    = empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values($data["lastName"], $posted_data);

        $status = "true" == $dopt ? "pending" : "subscribed";

        // Use Mailchimp's "Add or update list member" endpoint
        // (PUT lists/{id}/members/{subscriber_hash}) so a second submission
        // with the same email automatically updates merge fields instead of
        // failing with 400 Member Exists. The subscriber hash is the MD5 of
        // the lowercased email — purely hex, so no URL-encoding concerns.
        //
        // Payload notes:
        //  - status_if_new sets the status for newly-created contacts.
        //  - status is intentionally omitted so existing members keep their
        //    current state. Setting status would attempt to resubscribe
        //    unsubscribed/cleaned/archived contacts, which Mailchimp rejects
        //    (and would surprise the form admin who didn't ask to override
        //    a recipient's prior unsubscribe).
        $subscriber_data = array(
            "email_address" => $email,
            "status_if_new" => $status,
        );

        if( $first_name || $last_name ) {
            $subscriber_data["merge_fields"] = array();

            if( $first_name ) {
                $subscriber_data["merge_fields"]["FNAME"] = $first_name;
            }

            if( $last_name ) {
                $subscriber_data["merge_fields"]["LNAME"] = $last_name;
            }
        }

        $hash     = md5( strtolower( trim( $email ) ) );
        $endpoint = "lists/{$list_id}/members/{$hash}";
        $return   = adfoin_mailchimp_request( $endpoint, 'PUT', $subscriber_data, $record, $cred_id );
    }

    if( $task == 'unsubscribe' ) {

        $search_endpoint = "search-members?query={$email}";
        $member          = adfoin_mailchimp_request( $search_endpoint, 'GET', array(), array(), $cred_id );

        if( !is_wp_error( $member ) ) {
            $body      = json_decode( $member['body'], true );
            $id        = $body['exact_matches']['members'][0]['id'];
            $unsub_end = "lists/{$list_id}/members/{$id}";
            $return    = adfoin_mailchimp_request( $unsub_end, 'DELETE', array(), $record, $cred_id );

            if ( $return['response']['code'] == 204 ) {
                return array( 1 );
            } else {
                return array( 0, $return )  ;
            }

        } else {
            return;
        }
    }
}
