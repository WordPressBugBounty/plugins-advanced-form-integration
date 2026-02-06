<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_mailerlite2_actions',
    10,
    1
);
function adfoin_mailerlite2_actions(  $actions  ) {
    $actions['mailerlite2'] = array(
        'title' => __( 'MailerLite', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To Group', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_mailerlite2_settings_tab',
    10,
    1
);
function adfoin_mailerlite2_settings_tab(  $providers  ) {
    $providers['mailerlite2'] = __( 'MailerLite', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_mailerlite2_settings_view',
    10,
    1
);
function adfoin_mailerlite2_settings_view(  $current_tab  ) {
    if ( $current_tab != 'mailerlite2' ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name'          => 'apiKey',
        'label'         => __( 'MailerLite API Token', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'mask'          => true,
        'placeholder'   => __( 'Enter API Token', 'advanced-form-integration' ),
        'show_in_table' => true,
    ));
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Go to Integrations > API in MailerLite.', 'advanced-form-integration' ),
        __( 'Generate an API token with required scopes.', 'advanced-form-integration' ),
        __( 'Paste the token here and save.', 'advanced-form-integration' )
    );
    ADFOIN_Account_Manager::render_settings_view(
        'mailerlite2',
        __( 'MailerLite', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_mailerlite2_credentials',
    'adfoin_get_mailerlite2_credentials',
    10,
    0
);
function adfoin_get_mailerlite2_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailerlite2' );
}

add_action(
    'wp_ajax_adfoin_save_mailerlite2_credentials',
    'adfoin_save_mailerlite2_credentials',
    10,
    0
);
function adfoin_save_mailerlite2_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailerlite2', array('apiKey') );
}

add_filter(
    'adfoin_get_credentials',
    'adfoin_mailerlite2_modify_credentials',
    10,
    2
);
function adfoin_mailerlite2_modify_credentials(  $credentials, $platform  ) {
    if ( 'mailerlite2' === $platform && empty( $credentials ) ) {
        $api_key = get_option( 'adfoin_mailerlite2_api_key' );
        if ( $api_key ) {
            $credentials[] = array(
                'id'     => 'legacy',
                'title'  => __( 'Legacy Account', 'advanced-form-integration' ),
                'apiKey' => $api_key,
            );
        }
    }
    return $credentials;
}

add_action( 'adfoin_action_fields', 'adfoin_mailerlite2_action_fields' );
function adfoin_mailerlite2_action_fields() {
    ?>
    <script type="text/template" id="mailerlite2-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'subscribe_to_group'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Account', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?></option>
                        <option v-for="cred in credentialsList" :key="cred.id" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailerlite2' ) );
    ?>" target="_blank" class="dashicons dashicons-admin-settings" style="text-decoration: none; margin-top: 3px;"></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'subscribe_to_group'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">
                <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php 
    esc_attr_e( 'MailerLite Group', 'advanced-form-integration' );
    ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php 
    _e( 'Select Group...', 'advanced-form-integration' );
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

function adfoin_mailerlite2_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array()
) {
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : (( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' )) );
    if ( isset( $data['credId'] ) ) {
        unset($data['credId']);
    }
    $credentials = adfoin_get_credentials_by_id( 'mailerlite2', $cred_id );
    $api_token = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    if ( !$api_token ) {
        $api_token = ( get_option( 'adfoin_mailerlite2_api_key' ) ? get_option( 'adfoin_mailerlite2_api_key' ) : '' );
    }
    if ( !$api_token ) {
        return new WP_Error('missing_api_key', __( 'MailerLite API key not found', 'advanced-form-integration' ));
    }
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'User-Agent'    => 'advanced-form-integration',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $api_token,
        ),
    );
    $base_url = 'https://connect.mailerlite.com/api/';
    $url = $base_url . $endpoint;
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
    'wp_ajax_adfoin_get_mailerlite2_list',
    'adfoin_get_mailerlite2_list',
    10,
    0
);
/*
 * Get MailerLite subscriber lists
 */
function adfoin_get_mailerlite2_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $data = adfoin_mailerlite2_request( 'groups', 'GET', array(
        'credId' => $cred_id,
    ) );
    if ( !is_wp_error( $data ) ) {
        $body = json_decode( wp_remote_retrieve_body( $data ) );
        $lists = wp_list_pluck( $body->data, 'name', 'id' );
        wp_send_json_success( $lists );
    }
    wp_send_json_error();
}

add_action(
    'wp_ajax_adfoin_get_mailerlite2_custom_fields',
    'adfoin_get_mailerlite2_custom_fields',
    10,
    0
);
/*
 * Get MailerLite fields
 */
function adfoin_get_mailerlite2_custom_fields() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $data = adfoin_mailerlite2_request( 'fields', 'GET', array(
        'credId' => $cred_id,
    ) );
    if ( !is_wp_error( $data ) ) {
        $body = json_decode( wp_remote_retrieve_body( $data ) );
        $fields = array();
        foreach ( $body->data as $single ) {
            if ( true == $single->is_default ) {
                array_push( $fields, array(
                    'key'   => $single->key,
                    'value' => $single->name,
                ) );
            }
        }
        wp_send_json_success( $fields );
    } else {
        wp_send_json_error();
    }
}

add_action(
    'adfoin_mailerlite2_job_queue',
    'adfoin_mailerlite2_job_queue',
    10,
    1
);
function adfoin_mailerlite2_job_queue(  $data  ) {
    adfoin_mailerlite2_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to MailerLite API
 */
function adfoin_mailerlite2_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $list_id = ( isset( $data['listId'] ) ? $data['listId'] : '' );
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    if ( empty( $cred_id ) ) {
        $creds = adfoin_read_credentials( 'mailerlite2' );
        if ( !empty( $creds ) ) {
            $cred_id = $creds[0]['id'];
        }
    }
    if ( $task == 'subscribe' ) {
        $holder = array();
        foreach ( $data as $key => $value ) {
            $holder[$key] = adfoin_get_parsed_values( $value, $posted_data );
        }
        $email = ( isset( $holder['email'] ) ? $holder['email'] : '' );
        $status = ( isset( $holder['status'] ) ? $holder['status'] : '' );
        $ip_address = ( isset( $holder['ip_address'] ) ? $holder['ip_address'] : '' );
        unset($holder['list']);
        unset($holder['listId']);
        unset($holder['credId']);
        unset($holder['email']);
        unset($holder['status']);
        unset($holder['ip_address']);
        $holder = array_filter( $holder );
        $subscriber_data = array(
            'email' => $email,
        );
        if ( $holder ) {
            $subscriber_data['fields'] = $holder;
        }
        if ( $ip_address ) {
            $subscriber_data['ip_address'] = $ip_address;
        }
        if ( $status ) {
            $subscriber_data['status'] = $status;
        }
        if ( $list_id ) {
            $subscriber_data['groups'] = array($list_id);
        }
        $subscriber_data['credId'] = $cred_id;
        adfoin_mailerlite2_request(
            'subscribers',
            'POST',
            $subscriber_data,
            $record
        );
        return;
    }
}
