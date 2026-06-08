<?php

add_filter('adfoin_action_providers', 'adfoin_snovio_actions', 10, 1);

function adfoin_snovio_actions($actions) {

    $actions['snovio'] = [
        'title' => __('Snov.io', 'advanced-form-integration'),
        'tasks' => [
            'add_contact' => __('Add Contact to List', 'advanced-form-integration'),
        ]
    ];

    return $actions;
}

add_filter('adfoin_settings_tabs', 'adfoin_snovio_settings_tab', 10, 1);

function adfoin_snovio_settings_tab($providers) {
    $providers['snovio'] = __('Snov.io', 'advanced-form-integration');

    return $providers;
}

add_action('adfoin_settings_view', 'adfoin_snovio_settings_view', 10, 1);

function adfoin_snovio_settings_view( $current_tab ) {
    if ( $current_tab != 'snovio' ) return;

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiUserId',
            'label'         => __( 'Client ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Enter Client ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiSecret',
            'label'         => __( 'Client Secret', 'advanced-form-integration' ),
            'type'          => 'password',
            'required'      => true,
            'placeholder'   => __( 'Enter Client Secret', 'advanced-form-integration' ),
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = '<ol class="afi-instructions-list">
        <li>' . __( 'Go to <a href="https://app.snov.io/account/api" target="_blank" rel="noopener noreferrer">Snov.io API settings</a> and copy your Client ID and Client Secret.', 'advanced-form-integration' ) . '</li>
        <li>' . __( 'Paste the credentials in the fields above and click "Add Account".', 'advanced-form-integration' ) . '</li>
    </ol>';

    ADFOIN_Account_Manager::render_settings_view( 'snovio', 'Snov.io', $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_snovio_credentials', 'adfoin_get_snovio_credentials' );
function adfoin_get_snovio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials( 'snovio' );
}

add_action( 'wp_ajax_adfoin_save_snovio_credentials', 'adfoin_save_snovio_credentials' );
function adfoin_save_snovio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'snovio', array( 'apiUserId' => 'text', 'apiSecret' => 'password' ) );
}

add_action( 'wp_ajax_adfoin_get_snovio_credentials_list', 'adfoin_snovio_get_credentials_list_ajax' );
function adfoin_snovio_get_credentials_list_ajax() {
    adfoin_verify_nonce();

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'apiUserId', 'mask' => false ),
        array( 'name' => 'apiSecret', 'mask' => true ),
    );

    ADFOIN_Account_Manager::ajax_get_credentials_list( 'snovio', $fields );
}

add_action('adfoin_action_fields', 'adfoin_snovio_action_fields');

function adfoin_snovio_action_fields() {
    ?>
    <script type="text/template" id="snovio-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row"><?php esc_html_e( 'Snov.io Account', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="handleAccountChange">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <span v-if="credentialLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                    <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=snovio' ); ?>"
                       target="_blank"
                       style="margin-left:10px;text-decoration:none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top:3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row"><?php esc_html_e( 'List', 'advanced-form-integration' ); ?></th>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId">
                        <option value=""><?php _e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(item, index) in fielddata.list" :value="index">{{ item }}</option>
                    </select>
                    <span v-if="listLoading"><img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" style="width:20px;vertical-align:middle;" /></span>
                </td>
            </tr>

            <tr valign="top" v-if="action.task == 'add_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_contact', 'Snov.io [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

/*
 * Snov.io API Request
 */
function adfoin_snovio_request($endpoint, $method = 'GET', $data = [], $record = [], $cred_id = '') {

    $credentials  = adfoin_get_credentials_by_id('snovio', $cred_id);
    $api_user_id  = isset($credentials['apiUserId']) ? $credentials['apiUserId'] : '';
    $api_secret   = isset($credentials['apiSecret']) ? $credentials['apiSecret'] : '';

    // Get access token
    $auth_url = 'https://api.snov.io/v1/oauth/access_token';
    $auth_body = [
        'grant_type' => 'client_credentials',
        'client_id' => $api_user_id,
        'client_secret' => $api_secret,
    ];

    $auth_response = wp_remote_post($auth_url, ['body' => $auth_body]);
    $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);

    if (empty($auth_body['access_token'])) {
        return new WP_Error('auth_error', 'Failed to retrieve access token.');
    }

    $access_token = $auth_body['access_token'];

    // API request
    $url = "https://api.snov.io/v1/{$endpoint}";
    $args = [
        'timeout' => 30,
        'method' => $method,
        'headers' => [
            'Authorization' => "Bearer $access_token",
            'Content-Type' => 'application/json',
        ],
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $args['body'] = wp_json_encode($data);
    }

    $response = wp_remote_request($url, $args);

    if ($record) {
        adfoin_add_to_log($response, $url, $args, $record);
    }

    return $response;
}

add_action('wp_ajax_adfoin_get_snovio_lists', 'adfoin_get_snovio_lists', 10, 0);

function adfoin_get_snovio_lists() {
    adfoin_verify_nonce();

    $cred_id = sanitize_text_field( wp_unslash( $_POST['credId'] ) );
    $response = adfoin_snovio_request('get-user-lists', 'GET', [], [], $cred_id);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    if (!is_array($body)) {
        wp_send_json_error(__('Invalid response from Snov.io API', 'advanced-form-integration'));
    }

    $lists = [];
    foreach ($body as $field) {
        if (isset($field['id']) && isset($field['name'])) {
            $lists[$field['id']] = $field['name'];
        }
    }

    wp_send_json_success($lists);
}

/*
 * Add Contact to Snov.io List
 */
function adfoin_snovio_send_data($record, $posted_data) {
    $record_data = json_decode($record['data'], true);

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    // Retrieve data
    $data    = isset($record_data['field_data']) ? $record_data['field_data'] : array();
    $list_id = isset($data['listId']) ? $data['listId'] : '';
    $cred_id = isset($data['credId']) ? $data['credId'] : '';
    $task    = isset($record['task']) ? $record['task'] : '';

    if ($task === 'add_contact') {
        $email = adfoin_get_parsed_values( isset( $data['email'] ) ? $data['email'] : '', $posted_data );
        if ( empty( $email ) ) {
            return;
        }

        // Prepare contact data
        $contact_data = array(
            'email'         => $email,
            'fullName'      => adfoin_get_parsed_values(isset($data['fullName']) ? $data['fullName'] : '', $posted_data),
            'firstName'     => adfoin_get_parsed_values(isset($data['firstName']) ? $data['firstName'] : '', $posted_data),
            'lastName'      => adfoin_get_parsed_values(isset($data['lastName']) ? $data['lastName'] : '', $posted_data),
            'phones'        => array_filter( explode(',', adfoin_get_parsed_values(isset($data['phones']) ? $data['phones'] : '', $posted_data))),
            'country'       => adfoin_get_parsed_values(isset($data['country']) ? $data['country'] : '', $posted_data),
            'locality'      => adfoin_get_parsed_values(isset($data['locality']) ? $data['locality'] : '', $posted_data),
            'socialLinks'   => array_filter(array(
                'linkedIn' => adfoin_get_parsed_values(isset($data['socialLinks[linkedIn]']) ? $data['socialLinks[linkedIn]'] : '', $posted_data),
                'twitter'  => adfoin_get_parsed_values(isset($data['social[twitter]']) ? $data['social[twitter]'] : '', $posted_data),
            )),
            'position'      => adfoin_get_parsed_values(isset($data['position']) ? $data['position'] : '', $posted_data),
            'companyName'   => adfoin_get_parsed_values(isset($data['companyName']) ? $data['companyName'] : '', $posted_data),
            'companySite'   => adfoin_get_parsed_values(isset($data['companySite']) ? $data['companySite'] : '', $posted_data),
            'listId'        => $list_id,
            'updateContact' => true,
        );

        // Make API request
        adfoin_snovio_request('add-prospect-to-list', 'POST', array_filter($contact_data), $record, $cred_id);
    }
}


add_action('adfoin_snovio_job_queue', 'adfoin_snovio_job_queue', 10, 1);

function adfoin_snovio_job_queue($data) {
    adfoin_snovio_send_data($data['record'], $data['posted_data']);
}