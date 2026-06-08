<?php

/**
 * JustCall — Create a Sales Dialer contact via
 * POST /v2.1/sales_dialer/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: {api_key}:{api_secret} (literal colon, no Basic).
 *
 * @link https://developer.justcall.io/reference/authentication
 */

add_filter( 'adfoin_action_providers', 'adfoin_justcall_actions', 10, 1 );

function adfoin_justcall_actions( $actions ) {
    $actions['justcall'] = array(
        'title' => __( 'JustCall', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact (Sales Dialer)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_justcall_settings_tab', 10, 1 );

function adfoin_justcall_settings_tab( $providers ) {
    $providers['justcall'] = __( 'JustCall', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_justcall_settings_view', 10, 1 );

function adfoin_justcall_settings_view( $current_tab ) {
    if ( 'justcall' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiSecret',
            'label'         => __( 'API Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'baseUrl',
            'label'         => __( 'API Base URL (optional)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'placeholder'   => 'https://api.justcall.io',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In JustCall go to Settings → Developer (API) and generate an API key + secret with Sales Dialer access.', 'advanced-form-integration' ),
        esc_html__( 'Paste both values below.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends Authorization: {key}:{secret} (literal colon) and posts to /v2.1/sales_dialer/contacts. The contact-create task requires Sales Dialer to be enabled on your JustCall plan.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'justcall', __( 'JustCall', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_justcall_credentials', 'adfoin_get_justcall_credentials', 10, 0 );

function adfoin_get_justcall_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'justcall' );
}

add_action( 'wp_ajax_adfoin_save_justcall_credentials', 'adfoin_save_justcall_credentials', 10, 0 );

function adfoin_save_justcall_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'justcall', array( 'apiKey', 'apiSecret', 'baseUrl' ) );
}

function adfoin_justcall_credentials_list() {
    foreach ( adfoin_read_credentials( 'justcall' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_justcall_action_fields' );

function adfoin_justcall_action_fields() {
    ?>
    <script type="text/template" id="justcall-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'JustCall Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=justcall' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'create_contact', 'JustCall [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_justcall_fields', 'adfoin_get_justcall_fields', 10, 0 );

function adfoin_get_justcall_fields() {
    adfoin_verify_nonce();

    wp_send_json_success( adfoin_justcall_base_fields() );
}

function adfoin_justcall_base_fields() {
    return array(
        array( 'key' => 'name',         'value' => __( 'Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone_number', 'value' => __( 'Phone Number (E.164, e.g. +14155551234)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email',        'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'occupation',   'value' => __( 'Occupation', 'advanced-form-integration' ) ),
        array( 'key' => 'address',      'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'birthday',     'value' => __( 'Birthday (YYYY-MM-DD)', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_justcall_job_queue', 'adfoin_justcall_job_queue', 10, 1 );

function adfoin_justcall_job_queue( $data ) {
    adfoin_justcall_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_justcall_send_data( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
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

    $name  = isset( $field_data['name'] )         ? trim( (string) adfoin_get_parsed_values( $field_data['name'], $posted_data ) )         : '';
    $phone = isset( $field_data['phone_number'] ) ? trim( (string) adfoin_get_parsed_values( $field_data['phone_number'], $posted_data ) ) : '';

    if ( '' === $name || '' === $phone ) {
        return;
    }

    $payload = array(
        'name'         => $name,
        'phone_number' => $phone,
    );

    foreach ( array( 'email', 'occupation', 'address', 'birthday' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $payload[ $key ] = $value;
        }
    }

    $payload = apply_filters( 'adfoin_justcall_contact_payload', $payload, $field_data, $posted_data );

    adfoin_justcall_request( 'sales_dialer/contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_justcall_request' ) ) :
/**
 * Call the JustCall v2.1 API.
 *
 * @param string $endpoint Path under /v2.1/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_justcall_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $api_key    = '';
    $api_secret = '';
    $base       = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'justcall', $cred_id );
        if ( is_array( $credentials ) ) {
            $api_key    = isset( $credentials['apiKey'] )    ? trim( (string) $credentials['apiKey'] )    : '';
            $api_secret = isset( $credentials['apiSecret'] ) ? trim( (string) $credentials['apiSecret'] ) : '';
            $base       = isset( $credentials['baseUrl'] )   ? trim( (string) $credentials['baseUrl'] )   : '';
        }
    }

    if ( ! $api_key || ! $api_secret ) {
        return new WP_Error( 'justcall_missing_auth', __( 'JustCall API key or secret is missing.', 'advanced-form-integration' ) );
    }

    if ( ! $base ) {
        $base = 'https://api.justcall.io';
    }
    $base = untrailingslashit( $base );

    $url    = $base . '/v2.1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            // JustCall expects a literal colon-separated key:secret, NOT a
            // base64-encoded HTTP Basic header.
            'Authorization' => $api_key . ':' . $api_secret,
            'Accept'        => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( $data, $url );
        }
    } else {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
