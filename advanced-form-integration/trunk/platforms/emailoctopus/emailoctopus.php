<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_emailoctopus_actions',
    10,
    1
);
function adfoin_emailoctopus_actions(  $actions  ) {
    $actions['emailoctopus'] = array(
        'title' => __( 'EmailOctopus', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_emailoctopus_settings_tab',
    10,
    1
);
function adfoin_emailoctopus_settings_tab(  $providers  ) {
    $providers['emailoctopus'] = __( 'EmailOctopus', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_emailoctopus_settings_view',
    10,
    1
);
function adfoin_emailoctopus_settings_view(  $current_tab  ) {
    if ( $current_tab != 'emailoctopus' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'EmailOctopus API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf( '<p><a href="https://emailoctopus.com/api-documentation/v2" target="_blank" rel="noopener noreferrer">%s</a></p>', __( 'Click here to the get API Key', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'emailoctopus',
        __( 'EmailOctopus', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_emailoctopus_credentials',
    'adfoin_get_emailoctopus_credentials',
    10,
    0
);
/*
 * Get EmailOctopus credentials
 */
function adfoin_get_emailoctopus_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'emailoctopus' );
}

add_action(
    'wp_ajax_adfoin_save_emailoctopus_credentials',
    'adfoin_save_emailoctopus_credentials',
    10,
    0
);
/*
 * Save EmailOctopus credentials
 */
function adfoin_save_emailoctopus_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'emailoctopus', array('apiKey') );
}

/*
 * EmailOctopus Credentials List
 */
function adfoin_emailoctopus_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'emailoctopus' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_emailoctopus_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_emailoctopus_modify_credentials(  $credentials, $platform  ) {
    if ( 'emailoctopus' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_emailoctopus_api_key' );
        if ( $api_key ) {
            $credentials = array(array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_key,
            ));
        }
    }
    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action(
    'admin_post_adfoin_save_emailoctopus_api_key',
    'adfoin_save_emailoctopus_api_key',
    10,
    0
);
function adfoin_save_emailoctopus_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_emailoctopus_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST['adfoin_emailoctopus_api_key'] );
    // Save tokens
    update_option( 'adfoin_emailoctopus_api_key', $api_key );
    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=emailoctopus' );
}

/*
 * EmailOctopus API Request
 */
function adfoin_emailoctopus_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'emailoctopus', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = ( get_option( 'adfoin_emailoctopus_api_key' ) ? get_option( 'adfoin_emailoctopus_api_key' ) : '' );
    }
    if ( !$api_key ) {
        return array();
    }
    $base_url = 'https://api.emailoctopus.com/';
    $url = $base_url . $endpoint;
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
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
    'adfoin_add_js_fields',
    'adfoin_emailoctopus_js_fields',
    10,
    1
);
function adfoin_emailoctopus_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_emailoctopus_action_fields' );
function adfoin_emailoctopus_action_fields() {
    ?>
    <script type="text/template" id="emailoctopus-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'EmailOctopus Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=emailoctopus' );
    ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe' || action.task == 'unsubscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'EmailOctopus List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required="required">
                        <option value=""> <?php 
    _e( 'Select List...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Double Opt-in', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" value="true" name="fieldData[doubleoptin]" v-model="fielddata.doubleoptin">
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
        printf( __( 'To unlock custom fields and update contact consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                        </td>
                    </tr>
                    <?php 
    }
    ?>x
        </table>
    </script>


    <?php 
}

add_action(
    'wp_ajax_adfoin_get_emailoctopus_list',
    'adfoin_get_emailoctopus_list',
    10,
    0
);
/*
 * Get emailoctopus subscriber lists
 */
function adfoin_get_emailoctopus_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $data = adfoin_emailoctopus_request(
        'lists',
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( !is_wp_error( $data ) ) {
        $body = json_decode( $data['body'] );
        $lists = wp_list_pluck( $body->data, 'name', 'id' );
        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_emailoctopus_job_queue',
    'adfoin_emailoctopus_job_queue',
    10,
    1
);
function adfoin_emailoctopus_job_queue(  $data  ) {
    adfoin_emailoctopus_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to emailoctopus API
 */
function adfoin_emailoctopus_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $list_id = $data['listId'];
    $task = $record['task'];
    $doubleoption = ( isset( $data['doubleoptin'] ) ? $data['doubleoptin'] : '' );
    $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'emailoctopus' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( $task == 'subscribe' ) {
        $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
        $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
        $subscriber_data = array(
            'email_address' => trim( $email ),
            'fields'        => array(
                'FirstName' => $first_name,
                'LastName'  => $last_name,
            ),
        );
        // For v2 API, double opt-in is controlled by the subscribed parameter
        if ( 'true' == $doubleoption ) {
            $subscriber_data['subscribed'] = false;
            // Will send confirmation email
        } else {
            $subscriber_data['subscribed'] = true;
            // Direct subscription
        }
        $return = adfoin_emailoctopus_request(
            "lists/{$list_id}/contacts",
            'POST',
            $subscriber_data,
            $record,
            $cred_id
        );
        if ( $return['response']['code'] == 200 ) {
            return array(1);
        } else {
            return array(0, $return);
        }
    }
}
