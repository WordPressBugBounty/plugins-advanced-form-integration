<?php

add_filter( 'adfoin_action_providers', 'adfoin_webinarjam_actions', 10, 1 );

function adfoin_webinarjam_actions( $actions ) {

    $actions['webinarjam'] = array(
        'title' => __( 'WebinarJam', 'advanced-form-integration' ),
        'tasks' => array(
            'register_webinar' => __( 'Register to webinar', 'advanced-form-integration' )
        )
    );

    return $actions;
}

/**
 * Get WebinarJam credentials by ID
 * 
 * @param string $cred_id Credential ID (optional, can be from $_POST)
 * @return array Array with 'api_token' key, or empty string if not found
 */
function adfoin_webinarjam_get_credentials( $cred_id = '' ) {
    // If no cred_id provided, try to get from POST
    if ( empty( $cred_id ) && isset( $_POST['credId'] ) ) {
        $cred_id = sanitize_text_field( $_POST['credId'] );
    }

    $api_token = '';

    if ( $cred_id ) {
        $credentials = adfoin_read_credentials( 'webinarjam' );
        foreach( $credentials as $single ) {
            if( $single['id'] == $cred_id ) {
                $api_token = $single['api_token'];
                break;
            }
        }
    } else {
        // Fallback to old options if no cred_id provided
        $api_token = get_option( 'adfoin_webinarjam_api_token' ) ? get_option( 'adfoin_webinarjam_api_token' ) : '';
    }

    return array(
        'api_token' => $api_token
    );
}

add_filter( 'adfoin_settings_tabs', 'adfoin_webinarjam_settings_tab', 10, 1 );

function adfoin_webinarjam_settings_tab( $providers ) {
    $providers['webinarjam'] = __( 'WebinarJam', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_webinarjam_settings_view', 10, 1 );

function adfoin_webinarjam_settings_view( $current_tab ) {
    if( $current_tab != 'webinarjam' ) {
        return;
    }

    // Load Account Manager
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    // Migrate old settings if they exist and no new credentials exist
    $old_api_token = get_option( 'adfoin_webinarjam_api_token' );
    
    $existing_creds = adfoin_read_credentials( 'webinarjam' );

    if ( $old_api_token && empty( $existing_creds ) ) {
        $new_cred = array(
            'id' => uniqid(),
            'title' => 'Default Account',
            'api_token' => $old_api_token
        );
        adfoin_save_credentials( 'webinarjam', array( $new_cred ) );
    }

    $fields = array(
        array(
            'name'          => 'api_token',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Please enter API Token', 'advanced-form-integration' ),
            'mask'          => true,  // Mask API token in table
            'show_in_table' => true,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
            <li>' . __( 'Go to My webinars, click ADVANCED menu of any listed webinar.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Go to API custom integration and copy API Key.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Enter your API Token above.', 'advanced-form-integration' ) . '</li>
            <li>' . __( 'Click "Add Account" and save your credentials.', 'advanced-form-integration' ) . '</li>
        </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'webinarjam', 'WebinarJam', $fields, $instructions );
}

// AJAX Hooks for Account Manager
add_action( 'wp_ajax_adfoin_get_webinarjam_credentials', 'adfoin_get_webinarjam_credentials' );
function adfoin_get_webinarjam_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'webinarjam' );
}

add_action( 'wp_ajax_adfoin_save_webinarjam_credentials', 'adfoin_save_webinarjam_credentials' );
function adfoin_save_webinarjam_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'webinarjam', array( 'api_token' ) );
}

add_action( 'wp_ajax_adfoin_get_webinarjam_credentials_list', 'adfoin_webinarjam_get_credentials_list_ajax' );
function adfoin_webinarjam_get_credentials_list_ajax() {
    if ( ! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'api_token', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'webinarjam', $fields );
}

add_action( 'adfoin_add_js_fields', 'adfoin_webinarjam_js_fields', 10, 1 );

function adfoin_webinarjam_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_webinarjam_action_fields' );

function adfoin_webinarjam_action_fields() {
    ?>

    <script type="text/template" id="webinarjam-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'register_webinar'">
                <th scope="row"><?php esc_html_e( 'WebinarJam Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getWebinars">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=webinarjam' ); ?>" 
                       target="_blank" 
                       style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'register_webinar'">
                <th scope="row">
                    <?php esc_attr_e( 'Registrant Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'register_webinar'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <p><?php _e('To learn more details on the fields go to the link: ', 'advanced-form-integration' );?><a target="_blank" rel="noopener noreferrer" href="https://documentation.webinarjam.com/register-a-person-to-a-specific-webinar/">https://documentation.webinarjam.com/register-a-person-to-a-specific-webinar/</a></p>
                </td>
            </tr>
            <tr class="alternate" v-if="action.task == 'register_webinar'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Webinar', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[webinarId]" v-model="fielddata.webinarId" required="true" @change="getSchedule">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.webinars" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': webinarLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php _e( 'Required', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'register_webinar'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Schedule', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <select name="fieldData[scheduleId]" v-model="fielddata.scheduleId" required="true">
                        <option value=""><?php _e( 'Select...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.schedules" :value="index" > {{item}}  </option>
                    </select>
                    <p class="description"><?php _e( 'Required', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>

    <?php
}

function adfoin_webinarjam_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_webinarjam_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];

    if ( ! $api_token ) {
        return new WP_Error( 'missing_api_token', __( 'API token is missing.', 'advanced-form-integration' ) );
    }

    $base_url = "https://api.webinarjam.com/webinarjam/";
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body' => array_merge( $data, array( 'api_key' => $api_token ) ),
    );

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

add_action( 'wp_ajax_adfoin_get_webinarjam_webinars', 'adfoin_get_webinarjam_webinars', 10, 0 );
/*
 * Get Webinarjam accounts
 */
function adfoin_get_webinarjam_webinars() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_webinarjam_get_credentials();
    $api_token = $credentials['api_token'];

    if( !$api_token ) {
        wp_send_json_error();
        return;
    }

    $response = adfoin_webinarjam_request( 'webinars', 'POST' );

    if( !is_wp_error( $response ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $response ) );
        $lists = wp_list_pluck( $body->webinars, 'name', 'webinar_id' );

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'wp_ajax_adfoin_get_webinarjam_schedules', 'adfoin_get_webinarjam_schedules', 10, 0 );
/*
 * Get Webinarjam list
 */
function adfoin_get_webinarjam_schedules() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $credentials = adfoin_webinarjam_get_credentials();
    $api_token = $credentials['api_token'];

    if( !$api_token ) {
        wp_send_json_error();
        return;
    }

    $webinar_id = $_POST["webinarId"] ? sanitize_text_field( $_POST["webinarId"] ) : "";

    if( ! $webinar_id ) {
        wp_send_json_error();
    }

    $response = adfoin_webinarjam_request( 'webinar', 'POST', array( 'webinar_id' => $webinar_id ) );

    if( !is_wp_error( $response ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $response ) );
        $schedules = wp_list_pluck( $body->webinar->schedules, 'date', 'schedule' );

        wp_send_json_success( $schedules );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_webinarjam_job_queue', 'adfoin_webinarjam_job_queue', 10, 1 );

function adfoin_webinarjam_job_queue( $data ) {
    adfoin_webinarjam_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Webinarjam API
 */
function adfoin_webinarjam_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record['data'], true );
    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    
    $cred_id = isset( $field_data['credId'] ) ? $field_data['credId'] : '';
    
    $credentials = adfoin_webinarjam_get_credentials( $cred_id );
    $api_token = $credentials['api_token'];

    if( !$api_token ) {
        return;
    }

    if( array_key_exists( 'cl', $record_data['action_data'] ) ) {
        if( $record_data['action_data']['cl']['active'] == 'yes' ) {
            if( !adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
                return;
            }
        }
    }

    $data         = $record_data['field_data'];
    $task         = $record['task'];
    $webinar_id   = empty( $data['webinarId'] ) ? '' : $data['webinarId'];
    $schedule_id  = empty( $data['scheduleId'] ) ? '' : $data['scheduleId'];
    $email        = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
    $first_name   = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
    $last_name    = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
    $ip_address   = empty( $data['ipAddress'] ) ? '' : adfoin_get_parsed_values( $data['ipAddress'], $posted_data );
    $country_code = empty( $data['phoneCountryCode'] ) ? '' : adfoin_get_parsed_values( $data['phoneCountryCode'], $posted_data );
    $phone        = empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data );
    $timezone     = empty( $data['timezone'] ) ? '' : adfoin_get_parsed_values( $data['timezone'], $posted_data );
    $date         = empty( $data['date'] ) ? '' : adfoin_get_parsed_values( $data['date'], $posted_data );

    if( $task == 'register_webinar' ) {

        $endpoint = 'register';
        $request_data = array_filter(array(
            'webinar_id'         => $webinar_id,
            'schedule'           => $schedule_id,
            'email'              => $email,
            'first_name'         => $first_name,
            'last_name'          => $last_name,
            'phone_country_code' => $country_code,
            'phone'              => $phone,
            'ip_address'         => $ip_address,
            'timezone'           => $timezone,
            'date'               => $date,
        ));

        $response = adfoin_webinarjam_request( $endpoint, 'POST', $request_data, $record, $cred_id );

        adfoin_add_to_log( $response, $endpoint, $request_data, $record );
    }

    return;
}
