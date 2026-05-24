<?php

add_filter( 'adfoin_action_providers', 'adfoin_mailerlite_actions', 10, 1 );

function adfoin_mailerlite_actions( $actions ) {

    $actions['mailerlite'] = array(
        'title' => __( 'MailerLite Classic', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe'   => __( 'Subscribe To Group', 'advanced-form-integration' )
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_mailerlite_settings_tab', 10, 1 );

function adfoin_mailerlite_settings_tab( $providers ) {
    $providers['mailerlite'] = __( 'MailerLite Classic', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_mailerlite_settings_view', 10, 1 );

function adfoin_mailerlite_settings_view( $current_tab ) {
    if( $current_tab != 'mailerlite' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'MailerLite API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Enter API Key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        __( 'Navigate to Integrations > Developer API.', 'advanced-form-integration' ),
        __( 'Copy your API key.', 'advanced-form-integration' ),
        __( 'Paste it below and save.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'mailerlite', __( 'MailerLite Classic', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_mailerlite_credentials', 'adfoin_get_mailerlite_credentials', 10, 0 );
function adfoin_get_mailerlite_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'mailerlite' );
}

add_action( 'wp_ajax_adfoin_save_mailerlite_credentials', 'adfoin_save_mailerlite_credentials', 10, 0 );
function adfoin_save_mailerlite_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'mailerlite', array( 'apiKey' ) );
}

// Legacy single-account import: surfaces old `adfoin_mailerlite_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'mailerlite', array(
            'apiKey' => 'adfoin_mailerlite_api_key',
        ) );
    }
}, 20 );
add_action( 'adfoin_add_js_fields', 'adfoin_mailerlite_js_fields', 10, 1 );

function adfoin_mailerlite_js_fields( $field_data ) { }

add_action( 'adfoin_action_fields', 'adfoin_mailerlite_action_fields' );

function adfoin_mailerlite_action_fields() {
    ?>
    <script type="text/template" id="mailerlite-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'subscribe_to_group'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :key="cred.id" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=mailerlite' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    <div class="spinner" v-bind:class="{'is-active': credentialLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe' || action.task == 'subscribe_to_group'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'MailerLite Group', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select Group...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': listLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Double Opt-in', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <input type="checkbox" name="fieldData[doubleoptin]" value="true" v-model="fielddata.doubleoptin">
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'MailerLite [PRO]', 'custom fields' ); ?>
            
        </table>
    </script>


    <?php
}

add_action( 'wp_ajax_adfoin_get_mailerlite_list', 'adfoin_get_mailerlite_list', 10, 0 );

/*
 * Get MailerLite subscriber lists
 */
function adfoin_get_mailerlite_list() {
    // Security Check
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $data = adfoin_mailerlite_request( 'groups', 'GET', array(), array(), $cred_id );

    if( !is_wp_error( $data ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $data ) );
        $lists = wp_list_pluck( $body, 'name', 'id' );

        wp_send_json_success( $lists );
    } else {
        wp_send_json_error();
    }
}

add_action( 'adfoin_mailerlite_job_queue', 'adfoin_mailerlite_job_queue', 10, 1 );

function adfoin_mailerlite_job_queue( $data ) {
    adfoin_mailerlite_send_data( $data['record'], $data['posted_data'] );
}

/*
 * Saves connection mapping
 */
function adfoin_mailerlite_save_integration() {
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
                'data'            => wp_json_encode( $all_data ),
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
                'data'            => wp_json_encode( $all_data ),
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

/*
 * Handles sending data to MailerLite API
 */
function adfoin_mailerlite_send_data( $record, $posted_data ) {

    $record_data = json_decode( $record["data"], true );

    if( array_key_exists( "cl", $record_data["action_data"] ) ) {
        if( $record_data["action_data"]["cl"]["active"] == "yes" ) {
            if( !adfoin_match_conditional_logic( $record_data["action_data"]["cl"], $posted_data ) ) {
                return;
            }
        }
    }

    $data         = $record_data["field_data"];
    $list_id      = isset( $data["listId"] ) ? $data["listId"] : '';
    $task         = $record["task"];
    $doubleoption = isset( $data["doubleoptin"] ) && $data["doubleoptin"] ? $data["doubleoptin"] : "";
    $cred_id      = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( empty( $cred_id ) ) {
        $creds = adfoin_read_credentials( 'mailerlite' );
        if ( ! empty( $creds ) ) {
            $cred_id = $creds[0]['id'];
        }
    }

    if( $task == "subscribe" ) {
        $email = empty( $data["email"] ) ? "" : adfoin_get_parsed_values($data["email"], $posted_data);
        $name  = empty( $data["name"] ) ? "" : adfoin_get_parsed_values($data["name"], $posted_data);

        unset( $data['credId'] );

        $subscriber_data = array(
            "email" => $email,
            "name"  => $name
        );

        if( "true" == $doubleoption ) {
            adfoin_mailerlite_request( 'settings/double_optin', 'POST', array( 'enable' => true ), $record, $cred_id );
        }

        $endpoint = $list_id ? "groups/{$list_id}/subscribers" : 'subscribers';
        $return   = adfoin_mailerlite_request( $endpoint, 'POST', $subscriber_data, $record, $cred_id );
    }
}

/**
 * MailerLite Classic request helper
 */
function adfoin_mailerlite_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'mailerlite', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $api_key ) {
        $api_key = get_option( 'adfoin_mailerlite_api_key' );
    }

    if ( ! $api_key ) {
        return new WP_Error( 'missing_api_key', __( 'MailerLite API key not found', 'advanced-form-integration' ) );
    }

    $base_url = 'http://api.mailerlite.com/api/v2/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'        => 'application/json',
            'X-MailerLite-ApiKey' => $api_key
        )
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
