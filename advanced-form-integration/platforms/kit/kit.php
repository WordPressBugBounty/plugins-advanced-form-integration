<?php
add_filter( 'adfoin_action_providers', 'adfoin_kit_actions', 10, 1 );

function adfoin_kit_actions( $actions ) {
    $actions['kit'] = array(
        'title' => __( 'Kit', 'advanced-form-integration' ),
        'tasks' => array(
            'subscribe' => __( 'Subscribe To Sequence', 'advanced-form-integration' ),
        )
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_kit_settings_tab', 10, 1 );

function adfoin_kit_settings_tab( $providers ) {
    $providers['kit'] = __( 'Kit', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_kit_settings_view', 10, 1 );

function adfoin_kit_settings_view( $current_tab ) {
    if( $current_tab != 'kit' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 
            'name' => 'apiKey', 
            'label' => __( 'API Key', 'advanced-form-integration' ), 
            'type' => 'text', 
            'required' => true,
            'mask' => true,
            'placeholder' => __( 'Enter your API Key', 'advanced-form-integration' ),
            'show_in_table' => true
        )
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __('Go to Account Settings > Developer. Create and copy V4 API Key', 'advanced-form-integration')
    );

    ADFOIN_Account_Manager::render_settings_view( 'kit', __( 'Kit', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_kit_credentials', 'adfoin_get_kit_credentials', 10, 0 );
/*
 * Get Kit credentials
 */
function adfoin_get_kit_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'kit' );
}

add_action( 'wp_ajax_adfoin_save_kit_credentials', 'adfoin_save_kit_credentials', 10, 0 );
/*
 * Save Kit credentials
 */
function adfoin_save_kit_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'kit', array( 'apiKey' ) );
}

/*
 * Kit Credentials List
 */
function adfoin_kit_get_credentials_list() {
    $credentials = adfoin_read_credentials( 'kit' );

    foreach ($credentials as $option) {
        printf('<option value="%s">%s</option>', esc_attr($option['id']), esc_html($option['title']));
    }
}

// Legacy single-account import: surfaces old `adfoin_kit_*` options
// as a Legacy Account record when the new credentials store is empty.
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'kit', array(
            'apiKey' => 'adfoin_kit_api_key',
        ) );
    }
}, 20 );

add_action( 'adfoin_add_js_fields', 'adfoin_kit_js_fields', 10, 1 );

function adfoin_kit_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_kit_action_fields' );

function adfoin_kit_action_fields() {
    ?>
    <script type="text/template" id="kit-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Account', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in credentialsList" :value="item.id">{{item.title}}</option>
                    </select>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=kit' ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Subscriber Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">

                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Sequence', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""> <?php _e( 'Select Sequence...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.list" :value="index" > {{item}}  </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                    <p class="description" id="code-description"><?php _e( 'Either sequence or form must be selected', 'advanced-form-integration' ); ?></a></p>
                </td>
            </tr>

            <tr valign="top" class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell">
                        <?php esc_attr_e( 'Form', 'advanced-form-integration' ); ?>
                    </label>
                </td>
                <td>
                    <select name="fieldData[formId]" v-model="fielddata.formId">
                        <option value=""> <?php _e( 'Select Form...', 'advanced-form-integration' ); ?> </option>
                        <option v-for="(item, index) in fielddata.forms" :value="index" > {{item}}  </option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': formsLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'subscribe', 'Kit [PRO]', 'custom fields and tags' ); ?>
            
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_kit_list', 'adfoin_get_kit_list', 10, 0 );

function adfoin_get_kit_list() {
    // Security Check
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $data = adfoin_kit_request( 'sequences', 'GET', array(), array(), $cred_id );

    if( !is_wp_error( $data ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $data ) );
        $lists = wp_list_pluck( $body->sequences, 'name', 'id' );

        wp_send_json_success( $lists );
    }
}

add_action( 'wp_ajax_adfoin_get_kit_forms', 'adfoin_get_kit_forms', 10, 0 );

function adfoin_get_kit_forms() {
    // Security Check
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $data = adfoin_kit_request( 'forms', 'GET', array(), array(), $cred_id );

    if( !is_wp_error( $data ) ) {
        $body  = json_decode( wp_remote_retrieve_body( $data ) );
        $forms = wp_list_pluck( $body->forms, 'name', 'id' );

        wp_send_json_success( $forms );
    }
}

add_action( 'adfoin_kit_job_queue', 'adfoin_kit_job_queue', 10, 1 );

function adfoin_kit_job_queue( $data ) {
    adfoin_kit_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_kit_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record["data"], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $task = $record['task'];

    // Backward compatibility: if no cred_id, use first available credential
    if( empty( $cred_id ) ) {
        $all_credentials = adfoin_read_credentials( 'kit' );
        if( !empty( $all_credentials ) ) {
            $cred_id = $all_credentials[0]['id'];
        }
    }

    if( $task == 'subscribe' ) {
        $sequence_id = isset( $data['listId'] ) ? $data['listId'] : '';
        $form_id     = isset( $data['formId'] ) ? $data['formId'] : '';
        $email       = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );
        $first_name  = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );

        $subscriber_data = array_filter(array(
            'first_name'    => $first_name,
            'email_address' => $email,
            'state'         => 'active'
        ));

        $subscriber_return = adfoin_kit_request('subscribers', 'POST', $subscriber_data, $record, $cred_id);

        if ($sequence_id && $email) {
            $sequence_subscribe_endpoint = "sequences/{$sequence_id}/subscribers";
            $sequence_subscribe_data = array(
            'email_address' => $email
            );
            $sequence_subscribe_return = adfoin_kit_request($sequence_subscribe_endpoint, 'POST', $sequence_subscribe_data, $record, $cred_id);
        }
        
        if ($form_id && $email) {
            $form_subscribe_endpoint = "forms/{$form_id}/subscribers";
            $form_subscribe_data = array(
            'email_address' => $email
            );
            $form_subscribe_return = adfoin_kit_request($form_subscribe_endpoint, 'POST', $form_subscribe_data, $record, $cred_id);
        }
    }

    return;
}

function adfoin_kit_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'kit', $cred_id );
    $api_key = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    // Fallback to old option for backward compatibility
    if( !$api_key ) {
        $api_key = get_option( 'adfoin_kit_api_key' ) ? get_option( 'adfoin_kit_api_key' ) : '';
    }

    if ( empty( $api_key ) ) {
        return new WP_Error( 'missing_credentials', __( 'Kit API Key is not configured.', 'advanced-form-integration' ) );
    }

    $base_url = 'https://api.kit.com/v4/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-Kit-Api-Key' => $api_key
        )
    );

    if( 'POST' == $method || 'PUT' == $method ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}