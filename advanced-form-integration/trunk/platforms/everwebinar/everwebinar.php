<?php

add_filter( 'adfoin_action_providers', 'adfoin_everwebinar_actions', 10, 1 );

function adfoin_everwebinar_actions( $actions ) {

    $actions['everwebinar'] = array(
        'title' => __( 'EverWebinar', 'advanced-form-integration' ),
        'tasks' => array(
            'register_webinar' => __( 'Register to webinar', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_everwebinar_settings_tab', 10, 1 );

function adfoin_everwebinar_settings_tab( $providers ) {
    $providers['everwebinar'] = __( 'EverWebinar', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_everwebinar_settings_view', 10, 1 );

function adfoin_everwebinar_settings_view( $current_tab ) {
    if( $current_tab != 'everwebinar' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiToken', 
            'label' => __( 'API Token', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Token', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to <b>My webinars</b>, click <b>ADVANCED</b> menu of any listed webinar, go to <b>API custom integration</b> and copy API Key', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'everwebinar', __( 'EverWebinar', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_everwebinar_credentials', 'adfoin_get_everwebinar_credentials', 10, 0 );
/*
 * Get EverWebinar credentials
 */
function adfoin_get_everwebinar_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'everwebinar' );
}

add_action( 'wp_ajax_adfoin_save_everwebinar_credentials', 'adfoin_save_everwebinar_credentials', 10, 0 );
/*
 * Save EverWebinar credentials
 */
function adfoin_save_everwebinar_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'everwebinar', array( 'apiToken' ) );
}

/*
 * EverWebinar Credentials List
 */
function adfoin_everwebinar_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'everwebinar' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

add_filter( 'adfoin_get_credentials', 'adfoin_everwebinar_modify_credentials', 10, 2 );
/*
 * Modify credentials for backward compatibility
 */
function adfoin_everwebinar_modify_credentials( $credentials, $platform ) {
    if ( 'everwebinar' == $platform && empty( $credentials ) ) {
        $api_token = get_option( 'adfoin_everwebinar_api_token' );

        if( $api_token ) {
            $credentials = array(
                array(
                    'id'       => 'legacy',
                    'title'    => __( 'Legacy Account', 'advanced-form-integration' ),
                    'apiToken' => $api_token
                )
            );
        }
    }

    return $credentials;
}

// Deprecated - kept for backward compatibility
add_action( 'admin_post_adfoin_save_everwebinar_api_token', 'adfoin_save_everwebinar_api_token', 10, 0 );

function adfoin_save_everwebinar_api_token() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'adfoin_everwebinar_settings' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $api_token   = sanitize_text_field( $_POST["adfoin_everwebinar_api_token"] );

    // Save tokens
    update_option( "adfoin_everwebinar_api_token", $api_token );

    advanced_form_integration_redirect( "admin.php?page=advanced-form-integration-settings&tab=everwebinar" );
}

add_action( 'adfoin_add_js_fields', 'adfoin_everwebinar_js_fields', 10, 1 );

function adfoin_everwebinar_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_everwebinar_action_fields' );

function adfoin_everwebinar_action_fields() {
    ?>

    <script type="text/template" id="everwebinar-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'register_webinar'">
                <th scope="row">
                    <?php esc_attr_e( 'Registrant Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'register_webinar'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'EverWebinar Account', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""> <?php _e( 'Select Account...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=everwebinar' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'register_webinar'">
                <td>
                    <label for="tablecell">
                        <?php esc_attr_e( 'Instructions', 'advanced-form-integration' ); ?>
                    </label>
                </td>

                <td>
                    <p><?php _e('To learn more details on the fields go to the link: ', 'advanced-form-integration' );?><a target="_blank" rel="noopener noreferrer" href="https://documentation.everwebinar.com/register-a-person-to-a-specific-webinar/">https://documentation.everwebinar.com/register-a-person-to-a-specific-webinar-2/</a></p>
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
                    <div class="spinner" v-bind:class="{'is-active': scheduleLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                    <p class="description"><?php _e( 'Required', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
        </table>
    </script>

    <?php
}

add_action( 'wp_ajax_adfoin_get_everwebinar_webinars', 'adfoin_get_everwebinar_webinars', 10, 0 );
/*
 * Get EverWebinar webinars
 */
function adfoin_get_everwebinar_webinars() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $credentials = adfoin_get_credentials_by_id( 'everwebinar', $cred_id );
    $api_token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_everwebinar_api_token' ) ? get_option( 'adfoin_everwebinar_api_token' ) : '';
    }

    if( ! $api_token ) {
        wp_send_json_error();
    }

    $url    = "https://api.webinarjam.com/everwebinar/webinars";

    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body' => array(
            'api_key' => $api_token
        )
    );

    $accounts = wp_remote_request( $url, $args );

    if( !is_wp_error( $accounts ) ) {
        $body  = json_decode( $accounts["body"] );
        $lists = wp_list_pluck( $body->webinars, 'name', 'webinar_id' );

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'wp_ajax_adfoin_get_everwebinar_schedules', 'adfoin_get_everwebinar_schedules', 10, 0 );
/*
 * Get EverWebinar schedules
 */
function adfoin_get_everwebinar_schedules() {
    // Security Check
    if (! wp_verify_nonce( $_POST['_nonce'], 'advanced-form-integration' ) ) {
        die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( $_POST['credId'] ) : '';
    $credentials = adfoin_get_credentials_by_id( 'everwebinar', $cred_id );
    $api_token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_everwebinar_api_token' ) ? get_option( 'adfoin_everwebinar_api_token' ) : '';
    }

    if( ! $api_token ) {
        wp_send_json_error();
    }

    $webinar_id = $_POST["webinarId"] ? sanitize_text_field( $_POST["webinarId"] ) : "";

    if( ! $webinar_id ) {
        wp_send_json_error();
    }

    $url    = "https://api.webinarjam.com/everwebinar/webinar";

    $args = array(
        'method'  => 'POST',
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body' => array(
            'api_key'    => $api_token,
            'webinar_id' => $webinar_id
        )
    );

    $webinars = wp_remote_request( $url, $args );

    if( !is_wp_error( $webinars ) ) {
        $body  = json_decode( $webinars["body"] );
        $schedules = wp_list_pluck( $body->webinar->schedules, 'date', 'schedule' );

        wp_send_json_success( $schedules );
    } else {
        wp_send_json_error();
    }
}

/*
 * Saves connection mapping
 */
function adfoin_everwebinar_save_integration() {
    $params = array();
    parse_str( adfoin_sanitize_text_or_array_field( $_POST['formData'] ), $params );

    $trigger_data = isset( $_POST["triggerData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["triggerData"] ) : array();
    $action_data  = isset( $_POST["actionData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["actionData"] ) : array();
    $field_data   = isset( $_POST["fieldData"] ) ? adfoin_sanitize_text_or_array_field( $_POST["fieldData"] ) : array();

    $integration_title = isset( $trigger_data["integrationTitle"] ) ? $trigger_data["integrationTitle"] : "";
    $form_provider_id  = isset( $trigger_data["formProviderId"] ) ? $trigger_data["formProviderId"] : "";
    $form_id           = isset( $trigger_data["formId"] ) ? $trigger_data["formId"] : "";
    $form_name         = isset( $trigger_data["formName"] ) ? $trigger_data["formName"] : "";
    $action_provider   = isset( $action_data["actionProviderId"] ) ? $action_data["actionProviderId"] : "";
    $task              = isset( $action_data["task"] ) ? $action_data["task"] : "";
    $type              = isset( $params["type"] ) ? $params["type"] : "";



    $all_data = array(
        'trigger_data' => $trigger_data,
        'action_data'  => $action_data,
        'field_data'   => $field_data
    );

    global $wpdb;

    $integration_table = $wpdb->prefix . 'adfoin_integration';

    if ( $type == 'new_integration' ) {

        $result = $wpdb->insert(
            $integration_table,
            array(
                'title'           => $integration_title,
                'form_provider'   => $form_provider_id,
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'action_provider' => $action_provider,
                'task'            => $task,
                'data'            => json_encode( $all_data, true ),
                'status'          => 1
            )
        );

    }

    if ( $type == 'update_integration' ) {

        $id = esc_sql( trim( $params['edit_id'] ) );

        if ( $type != 'update_integration' &&  !empty( $id ) ) {
            return;
        }

        $result = $wpdb->update( $integration_table,
            array(
                'title'           => $integration_title,
                'form_provider'   => $form_provider_id,
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'data'            => json_encode( $all_data, true ),
            ),
            array(
                'id' => $id
            )
        );
    }

    if ( $result ) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_everwebinar_job_queue', 'adfoin_everwebinar_job_queue', 10, 1 );

function adfoin_everwebinar_job_queue( $data ) {
    adfoin_everwebinar_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Handles sending data to Everwebinar API
 */
function adfoin_everwebinar_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data         = $record_data["field_data"];
    $cred_id      = isset( $data['credId'] ) ? $data['credId'] : '';
    $task         = $record["task"];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'everwebinar' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    $credentials = adfoin_get_credentials_by_id( 'everwebinar', $cred_id );
    $api_token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    // Backward compatibility: fallback to old options if credentials not found
    if( empty( $api_token ) ) {
        $api_token = get_option( 'adfoin_everwebinar_api_token' ) ? get_option( 'adfoin_everwebinar_api_token' ) : '';
    }

    if( !$api_token ) {
        return;
    }

    $webinar_id   = empty( $data["webinarId"] ) ? "" : $data["webinarId"];
    $schedule_id  = empty( $data["scheduleId"] ) ? "" : $data["scheduleId"];
    $email        = empty( $data["email"] ) ? "" : adfoin_get_parsed_values( $data["email"], $posted_data );
    $first_name   = empty( $data["firstName"] ) ? "" : adfoin_get_parsed_values( $data["firstName"], $posted_data );
    $last_name    = empty( $data["lastName"] ) ? "" : adfoin_get_parsed_values( $data["lastName"], $posted_data );
    $ip_address   = empty( $data["ipAddress"] ) ? "" : adfoin_get_parsed_values( $data["ipAddress"], $posted_data );
    $country_code = empty( $data["phoneCountryCode"] ) ? "" : adfoin_get_parsed_values( $data["phoneCountryCode"], $posted_data );
    $phone        = empty( $data["phone"] ) ? "" : adfoin_get_parsed_values( $data["phone"], $posted_data );
    $timezone     = empty( $data["timezone"] ) ? "" : adfoin_get_parsed_values( $data["timezone"], $posted_data );
    $date         = empty( $data["date"] ) ? "" : adfoin_get_parsed_values( $data["date"], $posted_data );

    if( $task == "register_webinar" ) {

        $url = "https://api.webinarjam.com/everwebinar/register";

        $args = array(
            'method'  => 'POST',
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'api_key'            => $api_token,
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
            )
        );

        $response = wp_remote_request( $url, $args );

        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return;
}