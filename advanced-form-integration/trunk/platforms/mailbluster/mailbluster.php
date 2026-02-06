<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_mailbluster_actions',
    10,
    1
);
function adfoin_mailbluster_actions(  $actions  ) {
    $actions['mailbluster'] = array(
        'title' => __( 'MailBluster', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create New Lead', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_mailbluster_settings_tab',
    10,
    1
);
function adfoin_mailbluster_settings_tab(  $providers  ) {
    $providers['mailbluster'] = __( 'MailBluster', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_mailbluster_settings_view',
    10,
    1
);
function adfoin_mailbluster_settings_view(  $current_tab  ) {
    if ( $current_tab != 'mailbluster' ) {
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
    $instructions = sprintf( '<p>%s</p>', __( 'Go to Settings > API Keys and create new API Key', 'advanced-form-integration' ) );
    ADFOIN_Account_Manager::render_settings_view(
        'mailbluster',
        __( 'MailBluster', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_mailbluster_credentials',
    'adfoin_get_mailbluster_credentials',
    10,
    0
);
/*
 * Get MailBluster credentials
 */
function adfoin_get_mailbluster_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailbluster' );
}

add_action(
    'wp_ajax_adfoin_save_mailbluster_credentials',
    'adfoin_save_mailbluster_credentials',
    10,
    0
);
/*
 * Save MailBluster credentials
 */
function adfoin_save_mailbluster_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailbluster', array('apiKey') );
}

/*
 * MailBluster Credentials List
 */
function adfoin_mailbluster_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'mailbluster' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_mailbluster_modify_credentials',
    10,
    2
);
/*
 * Modify credentials for backward compatibility
 */
function adfoin_mailbluster_modify_credentials(  $credentials, $platform  ) {
    if ( 'mailbluster' == $platform && empty( $credentials ) ) {
        $api_token = get_option( 'adfoin_mailbluster_api_token' );
        if ( $api_token ) {
            $credentials = array(array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_token,
            ));
        }
    }
    return $credentials;
}

add_action( 'adfoin_action_fields', 'adfoin_mailbluster_action_fields' );
function adfoin_mailbluster_action_fields() {
    ?>
    <script type="text/template" id="mailbluster-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Account', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="(item, index) in fielddata.credentialsList" :value="index" > {{item}}  </option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailbluster' );
    ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Lead Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'add_contact'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'Double Opt-In', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doptin]" value="true" v-model="fielddata.doptin">
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php 
    if ( adfoin_fs()->is_not_paying() ) {
        ?>
                    <tr valign="top" v-if="action.task == 'add_contact'">
                        <th scope="row">
                            <?php 
        esc_attr_e( 'Go Pro', 'advanced-form-integration' );
        ?>
                        </th>
                        <td scope="row">
                            <span><?php 
        printf( __( 'To unlock tags and custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
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
 * Mailbluster API Request
 */
function adfoin_mailbluster_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'mailbluster', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    // Fallback to old option for backward compatibility
    if ( !$api_key ) {
        $api_key = get_option( 'adfoin_mailbluster_api_token' );
    }
    $base_url = 'https://api.mailbluster.com/api/';
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => $api_key,
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

/*
* Check if lead exists
*/
function adfoin_mailbluster_lead_exists(  $hash, $cred_id = ''  ) {
    if ( !$hash ) {
        return false;
    }
    $return = adfoin_mailbluster_request(
        'leads/' . $hash,
        'GET',
        array(),
        array(),
        $cred_id
    );
    if ( $return['response']['code'] == 200 ) {
        return true;
    } else {
        return false;
    }
}

add_action(
    'adfoin_mailbluster_job_queue',
    'adfoin_mailbluster_job_queue',
    10,
    1
);
function adfoin_mailbluster_job_queue(  $data  ) {
    adfoin_mailbluster_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to MailBluster API
 */
function adfoin_mailbluster_send_data(  $record, $posted_data  ) {
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
    $task = $record['task'];
    // Backward compatibility: if no cred_id, use first available credential
    if ( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'mailbluster' );
        if ( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }
    if ( $task == 'add_contact' ) {
        $basic_fields = array(
            'email',
            'firstName',
            'lastName',
            'fullName',
            'timezone',
            'ipAddress'
        );
        $body = array(
            'subscribed' => true,
        );
        $doptin = ( isset( $data['doptin'] ) ? $data['doptin'] : '' );
        unset($data['doptin']);
        foreach ( $basic_fields as $field ) {
            if ( isset( $data[$field] ) ) {
                $parsed_field = adfoin_get_parsed_values( $data[$field], $posted_data );
                if ( $parsed_field ) {
                    $body[$field] = $parsed_field;
                }
            }
        }
        if ( $doptin ) {
            $body['doubleOptIn'] = true;
        }
        if ( $body ) {
            $email_hash = md5( $body['email'] );
            if ( adfoin_mailbluster_lead_exists( $email_hash, $cred_id ) ) {
                adfoin_mailbluster_request(
                    'leads/' . $email_hash,
                    'PUT',
                    $body,
                    $record,
                    $cred_id
                );
            } else {
                adfoin_mailbluster_request(
                    'leads',
                    'POST',
                    $body,
                    $record,
                    $cred_id
                );
            }
        }
    }
    return;
}
