<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_benchmark_actions',
    10,
    1
);
function adfoin_benchmark_actions(  $actions  ) {
    $actions['benchmark'] = array(
        'title' => __( 'Benchmark', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Contact', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_benchmark_settings_tab',
    10,
    1
);
function adfoin_benchmark_settings_tab(  $providers  ) {
    $providers['benchmark'] = __( 'Benchmark', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_benchmark_settings_view',
    10,
    1
);
function adfoin_benchmark_settings_view(  $current_tab  ) {
    if ( $current_tab != 'benchmark' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf( '<p>%s</p>', __( 'Go to Profile > Integrations > API Key and copy the key', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'benchmark',
        __( 'Benchmark', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_benchmark_credentials',
    'adfoin_get_benchmark_credentials',
    10,
    0
);
/*
 * Get Benchmark credentials
 */
function adfoin_get_benchmark_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'benchmark' );
}

add_action(
    'wp_ajax_adfoin_save_benchmark_credentials',
    'adfoin_save_benchmark_credentials',
    10,
    0
);
/*
 * Save Benchmark credentials
 */
function adfoin_save_benchmark_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'benchmark', array('apiKey') );
}

/*
 * Benchmark Credentials List
 */
function adfoin_benchmark_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'benchmark' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_benchmark_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_benchmark_modify_credentials(  $credentials, $platform  ) {
    if ( 'benchmark' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_benchmark_api_key' );
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
    'admin_post_adfoin_benchmark_save_api_key',
    'adfoin_save_benchmark_api_key',
    10,
    0
);
function adfoin_save_benchmark_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_benchmark_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST["adfoin_benchmark_api_key"] );
    // Save tokens
    update_option( "adfoin_benchmark_api_key", $api_key );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=benchmark" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_benchmark_js_fields',
    10,
    1
);
function adfoin_benchmark_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_benchmark_action_fields' );
function adfoin_benchmark_action_fields() {
    ?>
    <script type="text/template" id="benchmark-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Contact Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Benchmark Account', 'advanced-form-integration' );
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
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=benchmark' );
    ?>" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Contact List', 'advanced-form-integration' );
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

function adfoin_benchmark_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'benchmark', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Backward compatibility: fallback to old option if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = ( get_option( 'adfoin_benchmark_api_key' ) ? get_option( 'adfoin_benchmark_api_key' ) : '' );
    }
    if ( !$api_key ) {
        return array();
    }
    $base_url = 'https://clientapi.benchmarkemail.com/';
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'timeout' => 30,
        'headers' => array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json; charset=utf-8',
            'AuthToken'    => $api_key,
        ),
    );
    if ( 'POST' == $method || 'PUT' == $method || 'PATCH' == $method ) {
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
    'wp_ajax_adfoin_get_benchmark_list',
    'adfoin_get_benchmark_list',
    10,
    0
);
/*
 * Get subscriber lists
 */
function adfoin_get_benchmark_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $endpoint = "Contact/?pageSize=1000";
    $data = adfoin_benchmark_request(
        $endpoint,
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        wp_send_json_error();
    }
    $body = json_decode( $data["body"] );
    $lists = wp_list_pluck( $body->Response->Data, 'Name', 'ID' );
    wp_send_json_success( $lists );
}

function adfoin_benchmark_create_contact(
    $list_id,
    $properties,
    $record,
    $cred_id = ''
) {
    $endpoint = "Contact/{$list_id}/ContactDetails";
    $response = adfoin_benchmark_request(
        $endpoint,
        'POST',
        $properties,
        $record,
        $cred_id
    );
    return $response;
}

function adfoin_benchmark_update_contact(
    $list_id,
    $contact_id,
    $properties,
    $record,
    $cred_id = ''
) {
    $endpoint = "Contact/{$list_id}/ContactDetails/{$contact_id}";
    $response = adfoin_benchmark_request(
        $endpoint,
        'PATCH',
        $properties,
        $record,
        $cred_id
    );
    return $response;
}

// Check if contact exists
function adfoin_benchmark_check_if_contact_exists(  $email, $cred_id = ''  ) {
    $endpoint = "Contact/ContactDetails?Search={$email}";
    $data = adfoin_benchmark_request(
        $endpoint,
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( is_wp_error( $data ) ) {
        return false;
    }
    $body = json_decode( wp_remote_retrieve_body( $data ), true );
    if ( isset( 
        $body['Response'],
        $body['Response']['Data'],
        $body['Response']['Data'][0],
        $body['Response']['Data'][0]['ID']
     ) ) {
        return $body['Response']['Data'][0]['ID'];
    } else {
        return false;
    }
}

add_action(
    'adfoin_benchmark_job_queue',
    'adfoin_benchmark_job_queue',
    10,
    1
);
function adfoin_benchmark_job_queue(  $data  ) {
    adfoin_benchmark_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Benchmark API
 */
function adfoin_benchmark_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    if ( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if ( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if ( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data["field_data"];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    $task = $record["task"];
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'benchmark' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( $task == "subscribe" ) {
        $list_id = $data["listId"];
        $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
        $first_name = ( empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data ) );
        $middle_name = ( empty( $data["middleName"] ) ? "" : adfoin_get_parsed_values( $data["middleName"], $posted_data ) );
        $last_name = ( empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data ) );
        $data = array(
            "Data" => array(
                "Email"      => $email,
                "FirstName"  => $first_name,
                "MiddleName" => $middle_name,
                "LastName"   => $last_name,
                "EmailPerm"  => 1,
            ),
        );
        $data = array_filter( $data );
        $contact_id = adfoin_benchmark_check_if_contact_exists( $email, $cred_id );
        if ( $contact_id ) {
            $return = adfoin_benchmark_update_contact(
                $list_id,
                $contact_id,
                $data,
                $record,
                $cred_id
            );
        } else {
            $return = adfoin_benchmark_create_contact(
                $list_id,
                $data,
                $record,
                $cred_id
            );
        }
    }
    return;
}
