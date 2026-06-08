<?php

/**
 * Field Nation — Create a Work Order via POST /api/rest/v2/workorders.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: OAuth2 password grant → /authentication/api/oauth/token, then
 * access_token is appended as a query string on every API call.
 *
 * @link https://developer.fieldnation.com/docs/rest-api/introduction/
 */

add_filter( 'adfoin_action_providers', 'adfoin_fieldnation_actions', 10, 1 );

function adfoin_fieldnation_actions( $actions ) {
    $actions['fieldnation'] = array(
        'title' => __( 'Field Nation', 'advanced-form-integration' ),
        'tasks' => array(
            'create_work_order' => __( 'Create Work Order', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_fieldnation_settings_tab', 10, 1 );

function adfoin_fieldnation_settings_tab( $providers ) {
    $providers['fieldnation'] = __( 'Field Nation', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_fieldnation_settings_view', 10, 1 );

function adfoin_fieldnation_settings_view( $current_tab ) {
    if ( 'fieldnation' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'clientId',
            'label'         => __( 'Client ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'clientSecret',
            'label'         => __( 'Client Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'username',
            'label'         => __( 'Account Username', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Your Field Nation login email', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'password',
            'label'         => __( 'Account Password', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'baseUrl',
            'label'         => __( 'API Base URL', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'placeholder'   => 'https://api.fieldnation.com',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Request API access from Field Nation; they issue you a Client ID and Client Secret per integration.', 'advanced-form-integration' ),
        esc_html__( 'Field Nation\'s OAuth2 uses the "password" grant — you also need the username and password of the Field Nation account that will own the work orders.', 'advanced-form-integration' ),
        esc_html__( 'Paste all four values below.', 'advanced-form-integration' ),
        sprintf(
            /* translators: %1$s: production URL, %2$s: sandbox URL. */
            esc_html__( 'Leave Base URL blank for production (%1$s) or set it to the sandbox URL (%2$s) for testing.', 'advanced-form-integration' ),
            '<code>https://api.fieldnation.com</code>',
            '<code>https://api-sandbox.fndev.net</code>'
        )
    );

    ADFOIN_Account_Manager::render_settings_view( 'fieldnation', __( 'Field Nation', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_fieldnation_credentials', 'adfoin_get_fieldnation_credentials', 10, 0 );

function adfoin_get_fieldnation_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'fieldnation' );
}

add_action( 'wp_ajax_adfoin_save_fieldnation_credentials', 'adfoin_save_fieldnation_credentials', 10, 0 );

function adfoin_save_fieldnation_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'fieldnation', array( 'clientId', 'clientSecret', 'username', 'password', 'baseUrl' ) );
}

function adfoin_fieldnation_credentials_list() {
    foreach ( adfoin_read_credentials( 'fieldnation' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_fieldnation_action_fields' );

function adfoin_fieldnation_action_fields() {
    ?>
    <script type="text/template" id="fieldnation-action-template">
        <table class="form-table" v-if="action.task == 'create_work_order'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Field Nation Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=fieldnation' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_work_order', 'Field Nation [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_fieldnation_fields', 'adfoin_get_fieldnation_fields', 10, 0 );

function adfoin_get_fieldnation_fields() {
    adfoin_verify_nonce();

    wp_send_json_success( adfoin_fieldnation_base_fields() );
}

function adfoin_fieldnation_base_fields() {
    return array(
        array( 'key' => 'title',           'value' => __( 'Title', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'description',     'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'typeOfWorkId',    'value' => __( 'Type of Work ID', 'advanced-form-integration' ), 'required' => true, 'description' => __( 'Numeric ID of the primary Type of Work (from Field Nation).', 'advanced-form-integration' ) ),
        array( 'key' => 'locationAddress1','value' => __( 'Address Line 1', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'locationCity',    'value' => __( 'City', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'locationState',   'value' => __( 'State (2-letter)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'locationZip',     'value' => __( 'ZIP / Postal Code', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'locationCountry', 'value' => __( 'Country (2-letter, default US)', 'advanced-form-integration' ) ),
        array( 'key' => 'scheduleStart',   'value' => __( 'Service Start (UTC, "YYYY-MM-DD HH:MM:SS")', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'payAmount',       'value' => __( 'Pay Amount (fixed total)', 'advanced-form-integration' ), 'required' => true ),
    );
}

add_action( 'adfoin_fieldnation_job_queue', 'adfoin_fieldnation_job_queue', 10, 1 );

function adfoin_fieldnation_job_queue( $data ) {
    adfoin_fieldnation_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_fieldnation_send_data( $record, $posted_data ) {
    if ( 'create_work_order' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $payload = adfoin_fieldnation_build_work_order( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    $payload = apply_filters( 'adfoin_fieldnation_work_order', $payload, $field_data, $posted_data );

    adfoin_fieldnation_request( 'workorders', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_fieldnation_build_work_order' ) ) :
/**
 * Build the nested work-order payload from flat field data.
 *
 * @return array|WP_Error
 */
function adfoin_fieldnation_build_work_order( $field_data, $posted_data ) {
    $title    = adfoin_fieldnation_parsed( $field_data, 'title', $posted_data );
    $type_id  = adfoin_fieldnation_parsed( $field_data, 'typeOfWorkId', $posted_data );
    $address1 = adfoin_fieldnation_parsed( $field_data, 'locationAddress1', $posted_data );
    $city     = adfoin_fieldnation_parsed( $field_data, 'locationCity', $posted_data );
    $state    = adfoin_fieldnation_parsed( $field_data, 'locationState', $posted_data );
    $zip      = adfoin_fieldnation_parsed( $field_data, 'locationZip', $posted_data );
    $country  = adfoin_fieldnation_parsed( $field_data, 'locationCountry', $posted_data );
    $start    = adfoin_fieldnation_parsed( $field_data, 'scheduleStart', $posted_data );
    $pay_raw  = adfoin_fieldnation_parsed( $field_data, 'payAmount', $posted_data );

    if ( '' === $title ) {
        return new WP_Error( 'fieldnation_missing_title', __( 'Work Order title is required.', 'advanced-form-integration' ) );
    }
    if ( '' === $type_id || ! is_numeric( $type_id ) ) {
        return new WP_Error( 'fieldnation_missing_type', __( 'Type of Work ID is required and must be numeric.', 'advanced-form-integration' ) );
    }
    if ( '' === $address1 || '' === $city || '' === $state || '' === $zip ) {
        return new WP_Error( 'fieldnation_missing_location', __( 'Work order location requires address line 1, city, state, and ZIP.', 'advanced-form-integration' ) );
    }
    if ( '' === $start ) {
        return new WP_Error( 'fieldnation_missing_schedule', __( 'Work order requires a service start datetime (UTC).', 'advanced-form-integration' ) );
    }
    if ( '' === $pay_raw || ! is_numeric( $pay_raw ) ) {
        return new WP_Error( 'fieldnation_missing_pay', __( 'Work order requires a numeric pay amount.', 'advanced-form-integration' ) );
    }

    $payload = array(
        'title'         => $title,
        'types_of_work' => array(
            array( 'id' => (int) $type_id, 'isPrimary' => true ),
        ),
        'location'      => array(
            'mode'     => 'custom',
            'address1' => $address1,
            'city'     => $city,
            'state'    => $state,
            'zip'      => $zip,
            'country'  => $country ?: 'US',
        ),
        'schedule'      => array(
            'service_window' => array(
                'mode'  => 'exact',
                'start' => array( 'utc' => $start ),
            ),
        ),
        'pay'           => array(
            'type' => 'fixed',
            'base' => array(
                'amount' => (float) $pay_raw,
                'units'  => 1,
            ),
        ),
    );

    $description = adfoin_fieldnation_parsed( $field_data, 'description', $posted_data );
    if ( '' !== $description ) {
        $payload['description'] = $description;
    }

    return $payload;
}
endif;

if ( ! function_exists( 'adfoin_fieldnation_parsed' ) ) :
function adfoin_fieldnation_parsed( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }
    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
    if ( is_array( $value ) ) {
        return '';
    }
    return is_string( $value ) ? trim( $value ) : (string) $value;
}
endif;

if ( ! function_exists( 'adfoin_fieldnation_request' ) ) :
/**
 * Call the Field Nation REST API. JSON body, access_token as query param.
 *
 * @param string $endpoint Path under /api/rest/v2/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_fieldnation_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = $cred_id && function_exists( 'adfoin_get_credentials_by_id' )
        ? adfoin_get_credentials_by_id( 'fieldnation', $cred_id )
        : array();

    if ( ! is_array( $credentials ) || empty( $credentials ) ) {
        return new WP_Error( 'fieldnation_missing_credentials', __( 'Field Nation credentials not found.', 'advanced-form-integration' ) );
    }

    $token = adfoin_fieldnation_get_access_token( $credentials );

    if ( is_wp_error( $token ) ) {
        if ( $record ) {
            adfoin_add_to_log( $token, '', array(), $record );
        }
        return $token;
    }

    $base_url = isset( $credentials['baseUrl'] ) ? trim( (string) $credentials['baseUrl'] ) : '';
    if ( ! $base_url ) {
        $base_url = 'https://api.fieldnation.com';
    }
    $base_url = untrailingslashit( $base_url );

    $url = $base_url . '/api/rest/v2/' . ltrim( $endpoint, '/' );
    $url = add_query_arg( 'access_token', $token, $url );

    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Accept' => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    } elseif ( ! empty( $data ) && is_array( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_fieldnation_get_access_token' ) ) :
/**
 * Fetch (and cache) an OAuth2 password-grant access token for the credential.
 *
 * @return string|WP_Error
 */
function adfoin_fieldnation_get_access_token( $credentials, $force_refresh = false ) {
    $client_id     = isset( $credentials['clientId'] )     ? trim( (string) $credentials['clientId'] )     : '';
    $client_secret = isset( $credentials['clientSecret'] ) ? trim( (string) $credentials['clientSecret'] ) : '';
    $username      = isset( $credentials['username'] )     ? trim( (string) $credentials['username'] )     : '';
    $password      = isset( $credentials['password'] )     ? (string) $credentials['password']             : '';
    $base_url      = isset( $credentials['baseUrl'] )      ? trim( (string) $credentials['baseUrl'] )      : '';

    if ( ! $base_url ) {
        $base_url = 'https://api.fieldnation.com';
    }
    $base_url = untrailingslashit( $base_url );

    if ( '' === $client_id || '' === $client_secret || '' === $username || '' === $password ) {
        return new WP_Error( 'fieldnation_missing_oauth', __( 'Field Nation credentials are incomplete.', 'advanced-form-integration' ) );
    }

    $cred_id   = isset( $credentials['id'] ) ? (string) $credentials['id'] : md5( $client_id . '|' . $username );
    $cache_key = 'adfoin_fn_token_' . $cred_id;

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( is_string( $cached ) && $cached ) {
            return $cached;
        }
    }

    $token_url = $base_url . '/authentication/api/oauth/token';

    $body = array(
        'grant_type'    => 'password',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'username'      => $username,
        'password'      => $password,
    );

    $response = wp_remote_post( $token_url, array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
        'body'    => wp_json_encode( $body ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code( $response );
    $data   = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status >= 400 || empty( $data['access_token'] ) ) {
        $message = isset( $data['error_description'] ) ? $data['error_description'] : __( 'Unable to obtain Field Nation access token.', 'advanced-form-integration' );
        return new WP_Error( 'fieldnation_token_error', $message, array( 'status' => $status ) );
    }

    $expires_in = isset( $data['expires_in'] ) ? (int) $data['expires_in'] : 3600;
    set_transient( $cache_key, $data['access_token'], max( 60, $expires_in - 60 ) );

    return $data['access_token'];
}
endif;
