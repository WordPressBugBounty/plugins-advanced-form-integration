<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_activecampaign_actions',
    10,
    1
);
function adfoin_activecampaign_actions(  $actions  ) {
    $actions['activecampaign'] = array(
        'title' => __( 'ActiveCampaign', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Add Contact/Deal/Note', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_activecampaign_settings_tab',
    10,
    1
);
function adfoin_activecampaign_settings_tab(  $providers  ) {
    $providers['activecampaign'] = __( 'ActiveCampaign', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_activecampaign_settings_view',
    10,
    1
);
function adfoin_activecampaign_settings_view(  $current_tab  ) {
    if ( $current_tab != 'activecampaign' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'url',
        'label'         => __( 'URL', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter your ActiveCampaign URL', 'advanced-form-integration' ),
        'show_in_table' => true,
    ), array(
        'name'          => 'apiKey',
        'label'         => __( 'API Key', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter your API Key', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Go to Settings > Developer', 'advanced-form-integration' ),
        __( 'Copy your URL and API Key', 'advanced-form-integration' ),
        __( 'Add them here and click Save', 'advanced-form-integration' )
    );
    ADFOIN_Account_Manager::render_settings_view(
        'activecampaign',
        __( 'ActiveCampaign', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_activecampaign_credentials',
    'adfoin_get_activecampaign_credentials',
    10,
    0
);
/*
 * Get ActiveCampaign credentials
 */
function adfoin_get_activecampaign_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'activecampaign' );
}

add_action(
    'wp_ajax_adfoin_save_activecampaign_credentials',
    'adfoin_save_activecampaign_credentials',
    10,
    0
);
/*
 * Save ActiveCampaign credentials
 */
function adfoin_save_activecampaign_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'activecampaign', array('url', 'apiKey') );
}

/*
 * ActiveCampaign Credentials List
 */
function adfoin_activecampaign_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'activecampaign' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_activecampaign_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_activecampaign_modify_credentials(  $credentials, $platform  ) {
    if ( 'activecampaign' == $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_activecampaign_api_key' );
        $url = get_option( 'adfoin_activecampaign_url' );
        if ( $api_key && $url ) {
            $credentials = array(array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_key,
                'url'    => $url,
            ));
        }
    }
    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action(
    'admin_post_adfoin_save_activecampaign_api_key',
    'adfoin_save_activecampaign_api_key',
    10,
    0
);
function adfoin_save_activecampaign_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_activecampaign_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $api_key = sanitize_text_field( $_POST["adfoin_activecampaign_api_key"] );
    $url = sanitize_text_field( $_POST["adfoin_activecampaign_url"] );
    // Save tokens
    update_option( "adfoin_activecampaign_api_key", $api_key );
    update_option( "adfoin_activecampaign_url", $url );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=activecampaign" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_activecampaign_js_fields',
    10,
    1
);
function adfoin_activecampaign_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_activecampaign_action_fields' );
function adfoin_activecampaign_action_fields() {
    ?>
    <script type="text/template" id="activecampaign-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td>
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Instructions', 'advanced-form-integration' );
    ?>
                    </label>
                </td>

                <td>
                    <p><?php 
    _e( 'This action will create/update contact at first and then add it to other tasks if filled. For example if you want to add the contact to a list or automation or deal just select/fill those fields only, leave other fields blank.', 'advanced-form-integration' );
    ?></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'ActiveCampaign Account', 'advanced-form-integration' );
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
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=activecampaign' );
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
    esc_attr_e( 'Account', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[accountId]" v-model="fielddata.accountId">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.accounts" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': accountLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'List', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
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
    esc_attr_e( 'Automation', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[automationId]" v-model="fielddata.automationId">
                        <option value=""> <?php 
    _e( 'Select Automation...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="(item, index) in fielddata.automations" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': automationLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Update if contact already exists', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input name="fieldData[update]" value="true" type="checkbox" v-model="fielddata.update">
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
        printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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

/*
 * ActiveCampaign API Request
 */
function adfoin_activecampaign_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'activecampaign', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $base_url = ( isset( $credentials['url'] ) ? $credentials['url'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = ( get_option( 'adfoin_activecampaign_api_key' ) ? get_option( 'adfoin_activecampaign_api_key' ) : '' );
    }
    if ( empty( $base_url ) ) {
        $base_url = ( get_option( 'adfoin_activecampaign_url' ) ? get_option( 'adfoin_activecampaign_url' ) : '' );
    }
    $url = $base_url . '/api/3/' . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Api-Token'    => $api_key,
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
    'wp_ajax_adfoin_get_activecampaign_list',
    'adfoin_get_activecampaign_list',
    10,
    0
);
/*
 * Get ActiveCampaign subscriber lists
 */
function adfoin_get_activecampaign_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $page = 0;
    $limit = 100;
    $has_value = true;
    $all_data = array();
    while ( $has_value ) {
        $offset = $page * $limit;
        $endpoint = "lists?limit={$limit}&offset={$offset}";
        $data = adfoin_activecampaign_request(
            $endpoint,
            'GET',
            array(),
            array(),
            $cred_id
        );
        $body = json_decode( wp_remote_retrieve_body( $data ) );
        if ( empty( $body->lists ) ) {
            $has_value = false;
        } else {
            $lists = wp_list_pluck( $body->lists, 'name', 'id' );
            $all_data = $all_data + $lists;
            $page++;
        }
    }
    wp_send_json_success( $all_data );
}

add_action(
    'wp_ajax_adfoin_get_activecampaign_automations',
    'adfoin_get_activecampaign_automations',
    10,
    0
);
/*
 * Get ActiveCampaign subscriber automations
 */
function adfoin_get_activecampaign_automations() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $page = 0;
    $limit = 100;
    $has_value = true;
    $all_data = array();
    while ( $has_value ) {
        $offset = $page * $limit;
        $endpoint = "automations?limit={$limit}&offset={$offset}";
        $data = adfoin_activecampaign_request(
            $endpoint,
            'GET',
            array(),
            array(),
            $cred_id
        );
        $body = json_decode( wp_remote_retrieve_body( $data ) );
        if ( empty( $body->automations ) ) {
            $has_value = false;
        } else {
            $automations = wp_list_pluck( $body->automations, 'name', 'id' );
            $all_data = $all_data + $automations;
            $page++;
        }
    }
    wp_send_json_success( $all_data );
}

add_action(
    'wp_ajax_adfoin_get_activecampaign_accounts',
    'adfoin_get_activecampaign_accounts',
    10,
    0
);
/*
 * Get ActiveCampaign subscriber automations
 */
function adfoin_get_activecampaign_accounts() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $page = 0;
    $limit = 100;
    $has_value = true;
    $all_data = array();
    while ( $has_value ) {
        $offset = $page * $limit;
        $endpoint = "accounts?limit={$limit}&offset={$offset}";
        $data = adfoin_activecampaign_request(
            $endpoint,
            'GET',
            array(),
            array(),
            $cred_id
        );
        $body = json_decode( wp_remote_retrieve_body( $data ) );
        if ( empty( $body->accounts ) ) {
            $has_value = false;
        } else {
            $accounts = wp_list_pluck( $body->accounts, 'name', 'id' );
            $all_data = $all_data + $accounts;
            $page++;
        }
    }
    wp_send_json_success( $all_data );
}

add_action(
    'wp_ajax_adfoin_get_activecampaign_deal_fields',
    'adfoin_get_activecampaign_deal_fields',
    10,
    0
);
/*
 * Get Pipedrive Dal Fields
 */
function adfoin_get_activecampaign_deal_fields() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $credentials = adfoin_get_credentials_by_id( 'activecampaign', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $base_url = ( isset( $credentials['url'] ) ? $credentials['url'] : '' );
    // Backward compatibility: fallback to old options if credentials not found
    if ( empty( $api_key ) ) {
        $api_key = ( get_option( 'adfoin_activecampaign_api_key' ) ? get_option( 'adfoin_activecampaign_api_key' ) : '' );
    }
    if ( empty( $base_url ) ) {
        $base_url = ( get_option( 'adfoin_activecampaign_url' ) ? get_option( 'adfoin_activecampaign_url' ) : '' );
    }
    if ( !$api_key || !$base_url ) {
        return array();
    }
    $url = "{$base_url}/api/3/dealGroups?limit=100";
    $args = array(
        "headers" => array(
            "Content-Type" => "application/json",
            "Api-Token"    => $api_key,
        ),
    );
    $data = wp_remote_get( $url, $args );
    $stages = "";
    $stage_body = json_decode( wp_remote_retrieve_body( $data ) );
    $pipelines = wp_list_pluck( $stage_body->dealGroups, "title", "id" );
    if ( isset( $stage_body->dealStages ) ) {
        if ( is_array( $stage_body->dealStages ) ) {
            foreach ( $stage_body->dealStages as $single ) {
                $stages .= $pipelines[$single->group] . '/' . $single->title . ': ' . $single->id . ' ';
            }
        }
    }
    $user_url = "{$base_url}/api/3/users?limit=100";
    $user_data = wp_remote_get( $user_url, $args );
    $users = "";
    $user_body = json_decode( wp_remote_retrieve_body( $user_data ) );
    foreach ( $user_body->users as $single ) {
        $users .= $single->username . ': ' . $single->id . ' ';
    }
    $deal_fields = array(
        array(
            'key'         => 'dealTitle',
            'value'       => 'Title [Deal]',
            'description' => 'Required for deal creation',
        ),
        array(
            'key'         => 'dealDescription',
            'value'       => 'Description [Deal]',
            'description' => '',
        ),
        array(
            'key'         => 'dealCurrency',
            'value'       => 'Currency [Deal]',
            'description' => '',
        ),
        array(
            'key'         => 'dealStage',
            'value'       => 'Stage ID [Deal]',
            'description' => $stages,
        ),
        array(
            'key'         => 'dealOwner',
            'value'       => 'Owner ID [Deal]',
            'description' => $users,
        ),
        array(
            'key'         => 'dealValue',
            'value'       => 'Value [Deal]',
            'description' => '',
        )
    );
    wp_send_json_success( $deal_fields );
    return;
}

add_action(
    'adfoin_activecampaign_job_queue',
    'adfoin_activecampaign_job_queue',
    10,
    1
);
function adfoin_activecampaign_job_queue(  $data  ) {
    adfoin_activecampaign_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to ActiveCampaign API
 */
function adfoin_activecampaign_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record["data"], true );
    if ( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if ( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if ( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data["field_data"];
    $list_id = $data["listId"];
    $aut_id = $data["automationId"];
    $acc_id = $data["accountId"];
    $cred_id = ( isset( $data["credId"] ) ? $data["credId"] : '' );
    $task = $record["task"];
    $update = $data["update"];
    $email = ( empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data ) );
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'activecampaign' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( $task == "subscribe" ) {
        $first_name = ( empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data ) );
        $last_name = ( empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data ) );
        $phone_number = ( empty( $data["phoneNumber"] ) ? "" : adfoin_get_parsed_values( $data["phoneNumber"], $posted_data ) );
        $deal_tile = ( empty( $data["dealTitle"] ) ? "" : adfoin_get_parsed_values( $data["dealTitle"], $posted_data ) );
        $note = ( empty( $data["note"] ) ? "" : adfoin_get_parsed_values( $data["note"], $posted_data ) );
        $endpoint = "contacts";
        $method = "POST";
        if ( "true" == $update ) {
            $endpoint = "contact/sync";
        }
        $request_data = array(
            "contact" => array(
                "email"      => $email,
                "first_name" => $first_name,
                "last_name"  => $last_name,
                "phone"      => $phone_number,
            ),
        );
        $request_data = array_map( 'array_filter', $request_data );
        $return = adfoin_activecampaign_request(
            $endpoint,
            $method,
            $request_data,
            $record,
            $cred_id
        );
        $contact_id = "";
        if ( !is_wp_error( $return ) ) {
            $return_body = json_decode( wp_remote_retrieve_body( $return ) );
            $contact_id = ( isset( $return_body->contact->id ) ? $return_body->contact->id : '' );
        }
        if ( $contact_id && $list_id ) {
            $endpoint = "contactLists";
            $request_data = array(
                "contactList" => array(
                    "list"    => $list_id,
                    "contact" => $contact_id,
                    "status"  => 1,
                ),
            );
            adfoin_activecampaign_request(
                $endpoint,
                "POST",
                $request_data,
                $record
            );
        }
        if ( $contact_id && $aut_id ) {
            $endpoint = "contactAutomations";
            $request_data = array(
                "contactAutomation" => array(
                    "automation" => $aut_id,
                    "contact"    => $contact_id,
                ),
            );
            adfoin_activecampaign_request(
                $endpoint,
                "POST",
                $request_data,
                $record
            );
        }
        if ( $contact_id && $acc_id ) {
            $endpoint = "accountContacts";
            $request_data = array(
                "accountContact" => array(
                    "account" => $acc_id,
                    "contact" => $contact_id,
                ),
            );
            adfoin_activecampaign_request(
                $endpoint,
                "POST",
                $request_data,
                $record
            );
        }
        if ( $contact_id && $deal_tile ) {
            $deal_description = ( empty( $data["dealDescription"] ) ? "" : adfoin_get_parsed_values( $data["dealDescription"], $posted_data ) );
            $deal_currency = ( empty( $data["dealCurrency"] ) ? "" : adfoin_get_parsed_values( $data["dealCurrency"], $posted_data ) );
            $deal_stage = ( empty( $data["dealStage"] ) ? "" : adfoin_get_parsed_values( $data["dealStage"], $posted_data ) );
            $deal_owner = ( empty( $data["dealOwner"] ) ? "" : adfoin_get_parsed_values( $data["dealOwner"], $posted_data ) );
            $deal_value = ( empty( $data["dealValue"] ) ? "" : adfoin_get_parsed_values( $data["dealValue"], $posted_data ) );
            $endpoint = "deals";
            $request_data = array(
                "deal" => array(
                    "title"       => $deal_tile,
                    "description" => $deal_description,
                    "currency"    => $deal_currency,
                    "owner"       => $deal_owner,
                    "value"       => $deal_value * 100,
                    "stage"       => $deal_stage,
                    "contact"     => $contact_id,
                ),
            );
            adfoin_activecampaign_request(
                $endpoint,
                "POST",
                $request_data,
                $record
            );
        }
        if ( $contact_id && $note ) {
            $endpoint = "notes";
            $request_data = array(
                "note" => array(
                    "note"    => $note,
                    "relid"   => $contact_id,
                    "reltype" => "Subscriber",
                ),
            );
            adfoin_activecampaign_request(
                $endpoint,
                "POST",
                $request_data,
                $record
            );
        }
        return;
    }
}
