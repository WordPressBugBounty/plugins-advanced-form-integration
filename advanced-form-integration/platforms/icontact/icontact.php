<?php
add_filter( 'adfoin_action_providers', 'adfoin_icontact_actions', 10, 1 );
function adfoin_icontact_actions( $actions ) {
    $actions['icontact'] = array(
        'title' => __( 'iContact', 'advanced-form-integration' ),
        'tasks' => array( 'subscribe' => __( 'Add Contact', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_icontact_settings_tab', 10, 1 );
function adfoin_icontact_settings_tab( $providers ) {
    $providers['icontact'] = __( 'iContact', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_icontact_settings_view', 10, 1 );
function adfoin_icontact_settings_view( $current_tab ) {
    if ( $current_tab !== 'icontact' ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'appId',
            'label'         => __( 'App ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Enter your App ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'username',
            'label'       => __( 'API Username', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'placeholder' => __( 'Enter your API Username', 'advanced-form-integration' ),
        ),
        array(
            'name'        => 'password',
            'label'       => __( 'API Password', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Enter your API Password', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<p>%s</p>',
        __( 'Go to <strong>Settings &rarr; iContact Integrations &rarr; Custom API Integrations &rarr; Create</strong> to get your App ID and API credentials.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'icontact', __( 'iContact', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_icontact_credentials', 'adfoin_get_icontact_credentials' );
function adfoin_get_icontact_credentials() {
    adfoin_verify_nonce();
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'icontact' );
}

add_action( 'wp_ajax_adfoin_save_icontact_credentials', 'adfoin_save_icontact_credentials' );
function adfoin_save_icontact_credentials() {
    adfoin_verify_nonce();

    $platform = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : '';

    if ( 'icontact' !== $platform ) {
        wp_send_json_error( __( 'Invalid platform.', 'advanced-form-integration' ) );
        return;
    }

    // Sanitize the incoming credential data array
    $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();

    if ( empty( $data ) ) {
        wp_send_json_error( __( 'No credential data provided.', 'advanced-form-integration' ) );
        return;
    }

    $last_index   = count( $data ) - 1;
    $app_id       = isset( $data[ $last_index ]['appId'] )   ? $data[ $last_index ]['appId']   : '';
    $username     = isset( $data[ $last_index ]['username'] ) ? $data[ $last_index ]['username'] : '';
    $password     = isset( $data[ $last_index ]['password'] ) ? $data[ $last_index ]['password'] : '';

    if ( ! $app_id || ! $username || ! $password ) {
        wp_send_json_error( __( 'App ID, username, and password are all required.', 'advanced-form-integration' ) );
        return;
    }

    // Fetch accountId
    $account_url = 'https://app.icontact.com/icp/a/';
    $headers     = array(
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json',
        'Api-Version'  => '2.2',
        'Api-AppId'    => $app_id,
        'Api-Username' => $username,
        'Api-Password' => $password,
    );

    $account_res  = wp_remote_get( $account_url, array( 'headers' => $headers, 'timeout' => 20 ) );
    if ( is_wp_error( $account_res ) ) {
        wp_send_json_error( $account_res->get_error_message() );
        return;
    }

    $account_body = json_decode( wp_remote_retrieve_body( $account_res ), true );

    if ( ! isset( $account_body['accounts'][0]['accountId'] ) ) {
        wp_send_json_error( __( 'Unable to retrieve account ID. Please verify your credentials.', 'advanced-form-integration' ) );
        return;
    }

    $account_id                              = $account_body['accounts'][0]['accountId'];
    $data[ $last_index ]['accountId']        = $account_id;

    // Fetch clientFolderId
    $folder_url = "https://app.icontact.com/icp/a/{$account_id}/c/";
    $folder_res = wp_remote_get( $folder_url, array( 'headers' => $headers, 'timeout' => 20 ) );
    if ( is_wp_error( $folder_res ) ) {
        wp_send_json_error( $folder_res->get_error_message() );
        return;
    }

    $folder_body = json_decode( wp_remote_retrieve_body( $folder_res ), true );

    if ( ! isset( $folder_body['clientfolders'][0]['clientFolderId'] ) ) {
        wp_send_json_error( __( 'Unable to retrieve client folder ID.', 'advanced-form-integration' ) );
        return;
    }

    $data[ $last_index ]['clientFolderId'] = $folder_body['clientfolders'][0]['clientFolderId'];

    adfoin_save_credentials( 'icontact', $data );

    wp_send_json_success();
}

/*
 * Helper: render server-side credential options (kept for backward compat)
 */
function adfoin_icontact_credentials_list() {
    foreach ( adfoin_read_credentials( 'icontact' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'wp_ajax_adfoin_get_icontact_lists', 'adfoin_get_icontact_lists' );
function adfoin_get_icontact_lists() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';

    $response = adfoin_icontact_request( 'lists/', 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        return;
    }

    if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_error( array( 'message' => __( 'Failed to retrieve lists. Please verify your credentials.', 'advanced-form-integration' ) ) );
        return;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! isset( $body['lists'] ) || ! is_array( $body['lists'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Unexpected response from iContact API.', 'advanced-form-integration' ) ) );
        return;
    }

    $list_options = wp_list_pluck( $body['lists'], 'name', 'listId' );
    wp_send_json_success( $list_options );
}

add_action( 'adfoin_icontact_job_queue', 'adfoin_icontact_job_queue', 10, 1 );
function adfoin_icontact_job_queue( $data ) {
    adfoin_icontact_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_icontact_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = $record_data['field_data'];
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $list_id = isset( $data['listId'] ) ? $data['listId'] : '';
    unset( $data['credId'], $data['listId'] );

    // Build contact fields from mapped values
    $fields = array();
    foreach ( $data as $key => $value ) {
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    // Email is required
    $email = isset( $fields['email'] ) ? trim( $fields['email'] ) : '';
    if ( empty( $email ) || ! is_email( $email ) ) {
        return;
    }

    $fields['status'] = 'normal';

    // Create / update contact
    $contact_res = adfoin_icontact_request( 'contacts/', 'POST', array( $fields ), $record, $cred_id );

    if ( is_wp_error( $contact_res ) ) {
        return;
    }

    $contact_body = json_decode( wp_remote_retrieve_body( $contact_res ), true );

    if ( ! isset( $contact_body['contacts'][0]['contactId'] ) ) {
        return;
    }

    $contact_id = (int) $contact_body['contacts'][0]['contactId'];

    if ( ! $list_id ) {
        return;
    }

    // Subscribe contact to list
    $subscription = array(
        array(
            'contactId' => $contact_id,
            'listId'    => (int) $list_id,
            'status'    => 'normal',
        ),
    );

    adfoin_icontact_request( 'subscriptions/', 'POST', $subscription, $record, $cred_id );
}

add_action( 'adfoin_action_fields', 'adfoin_icontact_action_fields' );
function adfoin_icontact_action_fields() {
    ?>
    <script type="text/template" id="icontact-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'subscribe'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row"></td>
            </tr>

            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell"><?php esc_html_e( 'iContact Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getData">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=icontact' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <div class="afi-spinner" v-bind:class="{'is-active': credentialLoading}"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'subscribe'">
                <td scope="row-title">
                    <label for="tablecell"><?php esc_html_e( 'Mailing List', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[listId]" v-model="fielddata.listId" required>
                        <option value=""><?php esc_html_e( 'Select List...', 'advanced-form-integration' ); ?></option>
                        <option v-for="(name, id) in fielddata.lists" :value="id">{{ name }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': listLoading}"></div>
                </td>
            </tr>

            <editable-field v-for="field in fields" :key="field.value" :field="field" :trigger="trigger" :action="action" :fielddata="fielddata"></editable-field>

            <?php adfoin_pro_feature_notice( 'subscribe', 'iContact [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

/**
 * Make a request to the iContact API.
 *
 * @param string $endpoint  Endpoint path relative to the account/folder base URL.
 * @param string $method    HTTP method: GET, POST, PUT, DELETE.
 * @param array  $data      Request body data (for POST/PUT).
 * @param array  $record    AFI record, passed to the log handler.
 * @param string $cred_id   Credential ID to look up credentials.
 * @return array|WP_Error
 */
function adfoin_icontact_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials    = adfoin_get_credentials_by_id( 'icontact', $cred_id );
    $app_id         = isset( $credentials['appId'] )        ? $credentials['appId']        : '';
    $username       = isset( $credentials['username'] )     ? $credentials['username']     : '';
    $password       = isset( $credentials['password'] )     ? $credentials['password']     : '';
    $account_id     = isset( $credentials['accountId'] )    ? $credentials['accountId']    : '';
    $folder_id      = isset( $credentials['clientFolderId'] ) ? $credentials['clientFolderId'] : '';

    if ( ! $app_id || ! $username || ! $password || ! $account_id || ! $folder_id ) {
        return new WP_Error( 'missing_credentials', __( 'iContact credentials are incomplete or not configured.', 'advanced-form-integration' ) );
    }

    $url  = "https://app.icontact.com/icp/a/{$account_id}/c/{$folder_id}/{$endpoint}";

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'Api-Version'  => '2.2',
            'Api-AppId'    => $app_id,
            'Api-Username' => $username,
            'Api-Password' => $password,
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( ! empty( $record ) ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
