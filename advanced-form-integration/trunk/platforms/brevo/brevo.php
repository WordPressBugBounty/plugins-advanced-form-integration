<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_brevo_actions',
    10,
    1
);
function adfoin_brevo_actions(  $actions  ) {
    $actions['brevo'] = array(
        'title' => __( 'Brevo', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To List', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_brevo_settings_tab',
    10,
    1
);
function adfoin_brevo_settings_tab(  $providers  ) {
    $providers['brevo'] = __( 'Brevo', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_brevo_settings_view',
    10,
    1
);
function adfoin_brevo_settings_view(  $current_tab  ) {
    if ( $current_tab != 'brevo' ) {
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
    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol><p style="color:red;">%s</p>',
        __( 'Go to Profile > SMTP & API', 'advanced-form-integration' ),
        __( 'Click on API Keys tab', 'advanced-form-integration' ),
        __( 'Generate a new API Key.', 'advanced-form-integration' ),
        __( 'Copy the API Key and add it here.', 'advanced-form-integration' ),
        __( 'Don\'t copy the SMTP key.', 'advanced-form-integration' )
    );
    ADFOIN_Account_Manager::render_settings_view(
        'brevo',
        __( 'Brevo', 'advanced-form-integration' ),
        $fields,
        $instructions
    );
}

add_action(
    'wp_ajax_adfoin_get_brevo_credentials',
    'adfoin_get_brevo_credentials',
    10,
    0
);
/*
 * Get Brevo credentials
 */
function adfoin_get_brevo_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'brevo' );
}

add_action(
    'wp_ajax_adfoin_save_brevo_credentials',
    'adfoin_save_brevo_credentials',
    10,
    0
);
/*
 * Save Brevo credentials
 */
function adfoin_save_brevo_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'brevo', array('apiKey') );
}

/*
 * Brevo Credentials List
 */
function adfoin_brevo_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'brevo' );
    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_brevo_action_fields' );
function adfoin_brevo_action_fields() {
    ?>
    <script type="text/template" id="brevo-action-template">
        <div>
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

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
    esc_attr_e( 'Brevo Account', 'advanced-form-integration' );
    ?>
                        </label>
                    </td>
                    <td>
                        <select name="fieldData[credId]" v-model="fielddata.credId" @change="getLists">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                            <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                        </select>
                        <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=brevo' );
    ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                        </a>
                    </td>
                </tr>
            
                <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                    <td scope="row-title">
                        <label for="tablecell">
                            <?php 
    esc_attr_e( 'Brevo List', 'advanced-form-integration' );
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
        printf( __( 'To unlock custom fields consider <a href="%s">upgrading to Pro</a>.', 'advanced-form-integration' ), admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) );
        ?></span>
                            </td>
                        </tr>
                        <?php 
    }
    ?>
            
        </table>
        </div>
    </script>
    <?php 
}

add_action(
    'wp_ajax_adfoin_get_brevo_list',
    'adfoin_get_brevo_list',
    10,
    0
);
/*
 * Get Brevo subscriber lists
 */
function adfoin_get_brevo_list() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $cred_id = ( isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '' );
    $page = 0;
    $limit = 50;
    $has_value = true;
    $all_data = array();
    while ( $has_value ) {
        $offset = $page * $limit;
        $endpoint = "contacts/lists?limit={$limit}&offset={$offset}";
        $response = adfoin_brevo_request(
            $endpoint,
            'GET',
            [],
            [],
            $cred_id
        );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error();
        }
        $body = json_decode( wp_remote_retrieve_body( $response ) );
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
    'adfoin_brevo_job_queue',
    'adfoin_brevo_job_queue',
    10,
    1
);
function adfoin_brevo_job_queue(  $data  ) {
    adfoin_brevo_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Brevo API
 */
function adfoin_brevo_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
        return;
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    // Backward compatibility: If no credId, use the first available credential
    if ( empty( $cred_id ) ) {
        $credentials = adfoin_read_credentials( 'brevo' );
        if ( !empty( $credentials ) && is_array( $credentials ) ) {
            $first_credential = reset( $credentials );
            $cred_id = ( isset( $first_credential['id'] ) ? $first_credential['id'] : '' );
        }
    }
    if ( $task == 'subscribe' ) {
        $list_id = $data['listId'];
        $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $first_name = ( empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data ) );
        $last_name = ( empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data ) );
        $sms = ( empty( $data['sms'] ) ? '' : adfoin_get_parsed_values( $data['sms'], $posted_data ) );
        $data = array(
            'email'         => $email,
            'attributes'    => array(
                'SMS'       => $sms,
                'FIRSTNAME' => $first_name,
                'LASTNAME'  => $last_name,
                'FIENAME'   => $last_name,
                'VORNAME'   => $first_name,
                'NACHNAME'  => $last_name,
                'NOMBRE'    => $first_name,
                'APELLIDOS' => $last_name,
                'NOME'      => $first_name,
                'COGNOME'   => $last_name,
                'SOBRENOME' => $last_name,
                'PRENOM'    => $first_name,
                'NOM'       => $last_name,
            ),
            'listIds'       => array(intval( $list_id )),
            'updateEnabled' => true,
        );
        $data = array_filter( $data );
        $response = adfoin_brevo_request(
            'contacts',
            'POST',
            $data,
            $record,
            $cred_id
        );
    }
    return;
}

/*
 * Brevo API Request
 */
function adfoin_brevo_request(
    $endpoint,
    $method = 'GET',
    $data = [],
    $record = [],
    $cred_id = ''
) {
    $credentials = adfoin_get_credentials_by_id( 'brevo', $cred_id );
    $api_key = ( isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '' );
    $base_url = 'https://api.brevo.com/v3/';
    $url = $base_url . $endpoint;
    $args = [
        'method'  => $method,
        'headers' => [
            'Content-Type' => 'application/json',
            'api-key'      => $api_key,
        ],
    ];
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
