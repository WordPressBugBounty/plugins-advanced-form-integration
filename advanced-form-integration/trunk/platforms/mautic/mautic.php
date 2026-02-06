<?php

add_filter(
    'adfoin_action_providers',
    'adfoin_mautic_actions',
    10,
    1
);
function adfoin_mautic_actions(  $actions  ) {
    $actions['mautic'] = array(
        'title' => __( 'Mautic', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Add or Update Contact', 'advanced-form-integration' ),
        ),
    );
    return $actions;
}

/**
 * Get Mautic credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'url', 'username', and 'password' keys, or empty strings if not found
 */
function adfoin_mautic_get_credentials(  $cred_id = ''  ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }
    $url = '';
    $username = '';
    $password = '';
    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'mautic' );
        foreach ( $credentials as $single ) {
            if ( $single['id'] == $cred_id ) {
                $url = $single['url'];
                $username = $single['username'];
                $password = $single['password'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $old_credentials = maybe_unserialize( get_option( 'adfoin_mautic_api_key' ) );
        if ( is_array( $old_credentials ) ) {
            $url = ( isset( $old_credentials['url'] ) ? $old_credentials['url'] : '' );
            $username = ( isset( $old_credentials['username'] ) ? $old_credentials['username'] : '' );
            $password = ( isset( $old_credentials['password'] ) ? $old_credentials['password'] : '' );
        }
    }
    return array(
        'url'      => $url,
        'username' => $username,
        'password' => $password,
    );
}

add_filter(
    'adfoin_settings_tabs',
    'adfoin_mautic_settings_tab',
    10,
    1
);
function adfoin_mautic_settings_tab(  $providers  ) {
    $providers['mautic'] = __( 'Mautic', 'advanced-form-integration' );
    return $providers;
}

add_action(
    'adfoin_settings_view',
    'adfoin_mautic_settings_view',
    10,
    1
);
function adfoin_mautic_settings_view(  $current_tab  ) {
    if ( $current_tab != 'mautic' ) {
        return;
    }
    // Load Account Manager
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    // Migrate old settings if they exist and no new credentials exist
    $old_credentials = maybe_unserialize( get_option( 'adfoin_mautic_api_key' ) );
    $existing_creds = adfoin_read_credentials( 'mautic' );
    if ( is_array( $old_credentials ) && !empty( $old_credentials ) && empty( $existing_creds ) ) {
        $new_cred = array(
            'id'       => uniqid(),
            'title'    => 'Default Account (Legacy)',
            'url'      => ( isset( $old_credentials['url'] ) ? $old_credentials['url'] : '' ),
            'username' => ( isset( $old_credentials['username'] ) ? $old_credentials['username'] : '' ),
            'password' => ( isset( $old_credentials['password'] ) ? $old_credentials['password'] : '' ),
        );
        adfoin_save_credentials( 'mautic', array($new_cred) );
    }
    $fields = array(array(
        'name'          => 'url',
        'label'         => __( 'Mautic Account URL', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Enter account URL', 'advanced-form-integration' ),
        'mask'          => false,
        'show_in_table' => true,
    ), array(
        'name'          => 'username',
        'label'         => __( 'Username', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Username', 'advanced-form-integration' ),
        'mask'          => false,
        'show_in_table' => true,
    ), array(
        'name'          => 'password',
        'label'         => __( 'Password', 'advanced-form-integration' ),
        'type'          => 'text',
        'required'      => true,
        'placeholder'   => __( 'Password', 'advanced-form-integration' ),
        'mask'          => true,
        'show_in_table' => false,
    ));
    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to your Mautic installation settings.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Navigate to Configuration > API Settings and enable both API and basic auth.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Hit Save button.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter full Mautic account URL (e.g. http://email.example.com), Username, and Password in the fields above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';
    ADFOIN_Account_Manager::render_settings_view(
        'mautic',
        'Mautic',
        $fields,
        $instructions
    );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_mautic_credentials', 'adfoin_get_mautic_credentials' );
function adfoin_get_mautic_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'mautic' );
}

add_action( 'wp_ajax_adfoin_save_mautic_credentials', 'adfoin_save_mautic_credentials' );
function adfoin_save_mautic_credentials() {
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mautic', array('url', 'username', 'password') );
}

add_action( 'wp_ajax_adfoin_get_mautic_credentials_list', 'adfoin_mautic_get_credentials_list_ajax' );
function adfoin_mautic_get_credentials_list_ajax() {
    if ( !wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }
    if ( !class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    $fields = array(array(
        'name' => 'url',
        'mask' => false,
    ), array(
        'name' => 'username',
        'mask' => false,
    ), array(
        'name' => 'password',
        'mask' => true,
    ));
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mautic', $fields );
}

add_action(
    'admin_post_adfoin_save_mautic_api_key',
    'adfoin_save_mautic_api_key',
    10,
    0
);
function adfoin_save_mautic_api_key() {
    // Security Check
    if ( !wp_verify_nonce( $_POST['_nonce'], 'adfoin_mautic_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }
    $url = ( isset( $_POST['adfoin_mautic_url'] ) ? sanitize_text_field( $_POST['adfoin_mautic_url'] ) : '' );
    $username = ( isset( $_POST['adfoin_mautic_username'] ) ? sanitize_text_field( $_POST['adfoin_mautic_username'] ) : '' );
    $password = ( isset( $_POST['adfoin_mautic_password'] ) ? sanitize_text_field( $_POST['adfoin_mautic_password'] ) : '' );
    $credentials = array(
        'url'      => $url,
        'username' => $username,
        'password' => $password,
    );
    // Save tokens
    update_option( "adfoin_mautic_api_key", maybe_serialize( $credentials ) );
    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=mautic" );
}

add_action(
    'adfoin_add_js_fields',
    'adfoin_mautic_js_fields',
    10,
    1
);
function adfoin_mautic_js_fields(  $field_data  ) {
}

add_action( 'adfoin_action_fields', 'adfoin_mautic_action_fields' );
function adfoin_mautic_action_fields() {
    ?>
    <script type="text/template" id="mautic-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row"><?php 
    esc_html_e( 'Mautic Account', 'advanced-form-integration' );
    ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""> <?php 
    _e( 'Select Account...', 'advanced-form-integration' );
    ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php 
    echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mautic' );
    ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php 
    esc_html_e( 'Manage Accounts', 'advanced-form-integration' );
    ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row">
                    <?php 
    esc_attr_e( 'Map Fields', 'advanced-form-integration' );
    ?>
                </th>
                <td scope="row">

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

add_action(
    'adfoin_mautic_job_queue',
    'adfoin_mautic_job_queue',
    10,
    1
);
function adfoin_mautic_job_queue(  $data  ) {
    adfoin_mautic_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Mautic API
 */
function adfoin_mautic_send_data(  $record, $posted_data  ) {
    $record_data = json_decode( $record['data'], true );
    if ( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if ( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if ( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }
    $data = $record_data['field_data'];
    $task = $record['task'];
    $cred_id = ( isset( $data['credId'] ) ? $data['credId'] : '' );
    if ( $task == 'add_contact' ) {
        $email = ( empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $firstname = ( empty( $data['firstname'] ) ? '' : adfoin_get_parsed_values( $data['firstname'], $posted_data ) );
        $lastname = ( empty( $data['lastname'] ) ? '' : adfoin_get_parsed_values( $data['lastname'], $posted_data ) );
        $title = ( empty( $data['title'] ) ? '' : adfoin_get_parsed_values( $data['title'], $posted_data ) );
        $mobile = ( empty( $data['mobile'] ) ? '' : adfoin_get_parsed_values( $data['mobile'], $posted_data ) );
        $phone = ( empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data ) );
        $fax = ( empty( $data['fax'] ) ? '' : adfoin_get_parsed_values( $data['fax'], $posted_data ) );
        $company = ( empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data ) );
        $position = ( empty( $data['position'] ) ? '' : adfoin_get_parsed_values( $data['position'], $posted_data ) );
        $address1 = ( empty( $data['address1'] ) ? '' : adfoin_get_parsed_values( $data['address1'], $posted_data ) );
        $address2 = ( empty( $data['address2'] ) ? '' : adfoin_get_parsed_values( $data['address2'], $posted_data ) );
        $city = ( empty( $data['city'] ) ? '' : adfoin_get_parsed_values( $data['city'], $posted_data ) );
        $state = ( empty( $data['state'] ) ? '' : adfoin_get_parsed_values( $data['state'], $posted_data ) );
        $zipcode = ( empty( $data['zipcode'] ) ? '' : adfoin_get_parsed_values( $data['zipcode'], $posted_data ) );
        $country = ( empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data ) );
        $website = ( empty( $data['website'] ) ? '' : adfoin_get_parsed_values( $data['website'], $posted_data ) );
        $facebook = ( empty( $data['facebook'] ) ? '' : adfoin_get_parsed_values( $data['facebook'], $posted_data ) );
        $instagram = ( empty( $data['instagram'] ) ? '' : adfoin_get_parsed_values( $data['instagram'], $posted_data ) );
        $linkedin = ( empty( $data['linkedin'] ) ? '' : adfoin_get_parsed_values( $data['linkedin'], $posted_data ) );
        $twitter = ( empty( $data['twitter'] ) ? '' : adfoin_get_parsed_values( $data['twitter'], $posted_data ) );
        $data = array(
            'email'     => trim( $email ),
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'title'     => $title,
            'mobile'    => $mobile,
            'phone'     => $phone,
            'fax'       => $fax,
            'company'   => $company,
            'postion'   => $position,
            'address1'  => $address1,
            'address2'  => $address2,
            'city'      => $city,
            'state'     => $state,
            'zipcode'   => $zipcode,
            'country'   => $country,
            'website'   => $website,
            'facebook'  => $facebook,
            'instagram' => $instagram,
            'linkedin'  => $linkedin,
            'twitter'   => $twitter,
        );
        $data = array_filter( $data );
        $return = adfoin_mautic_request(
            '/api/contacts/new',
            'POST',
            $data,
            $record,
            $cred_id
        );
    }
    return;
}

function adfoin_mautic_request(
    $endpoint,
    $method = 'GET',
    $data = array(),
    $record = array(),
    $cred_id = ''
) {
    $credentials = adfoin_mautic_get_credentials( $cred_id );
    $base_url = $credentials['url'];
    $username = $credentials['username'];
    $password = $credentials['password'];
    $url = $base_url . $endpoint;
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
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
