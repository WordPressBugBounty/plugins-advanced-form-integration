<?php

/**
 * ON24 — Register an attendee via POST /v2/client/{clientId}/event/{eventId}/registrant.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: static accesstokenkey + accesstokensecret headers (no OAuth).
 *
 * @link https://apidoc.on24.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_on24_actions', 10, 1 );

function adfoin_on24_actions( $actions ) {
    $actions['on24'] = array(
        'title' => __( 'ON24', 'advanced-form-integration' ),
        'tasks' => array(
            'register_attendee' => __( 'Register Attendee', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_on24_settings_tab', 10, 1 );

function adfoin_on24_settings_tab( $providers ) {
    $providers['on24'] = __( 'ON24', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_on24_settings_view', 10, 1 );

function adfoin_on24_settings_view( $current_tab ) {
    if ( 'on24' !== $current_tab ) {
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
            'placeholder'   => __( 'Numeric ON24 account ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'accessTokenKey',
            'label'         => __( 'Access Token Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'accessTokenSecret',
            'label'         => __( 'Access Token Secret', 'advanced-form-integration' ),
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
            'placeholder'   => 'https://api.on24.com',
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In ON24 go to Account → API Tokens and create a token. Copy the Access Token Key and Access Token Secret.', 'advanced-form-integration' ),
        esc_html__( 'Find your numeric Client ID in your ON24 account URL or under account settings.', 'advanced-form-integration' ),
        sprintf(
            /* translators: %1$s: North America URL, %2$s: EU URL. */
            esc_html__( 'Leave Base URL blank for %1$s, or set %2$s for EU accounts.', 'advanced-form-integration' ),
            '<code>https://api.on24.com</code>',
            '<code>https://api.eu.on24.com</code>'
        )
    );

    ADFOIN_Account_Manager::render_settings_view( 'on24', __( 'ON24', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_on24_credentials', 'adfoin_get_on24_credentials', 10, 0 );

function adfoin_get_on24_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'on24' );
}

add_action( 'wp_ajax_adfoin_save_on24_credentials', 'adfoin_save_on24_credentials', 10, 0 );

function adfoin_save_on24_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'on24', array( 'clientId', 'accessTokenKey', 'accessTokenSecret', 'baseUrl' ) );
}

function adfoin_on24_credentials_list() {
    foreach ( adfoin_read_credentials( 'on24' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_on24_action_fields' );

function adfoin_on24_action_fields() {
    ?>
    <script type="text/template" id="on24-action-template">
        <table class="form-table" v-if="action.task == 'register_attendee'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'ON24 Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=on24' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Event ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[eventId]" v-model="fielddata.eventId" placeholder="1234567" />
                    <p class="description"><?php esc_html_e( 'Numeric webcast/event ID from ON24.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'register_attendee', 'ON24 [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_on24_fields', 'adfoin_get_on24_fields', 10, 0 );

function adfoin_get_on24_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email',     'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'firstname', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastname',  'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company',   'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'jobtitle',  'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'workphone', 'value' => __( 'Work Phone', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_on24_job_queue', 'adfoin_on24_job_queue', 10, 1 );

function adfoin_on24_job_queue( $data ) {
    adfoin_on24_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_on24_send_data( $record, $posted_data ) {
    if ( 'register_attendee' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] )  ? $field_data['credId']  : '';
    $event_id   = isset( $field_data['eventId'] ) ? trim( (string) $field_data['eventId'] ) : '';

    if ( ! $cred_id || ! $event_id ) {
        return;
    }

    $email = isset( $field_data['email'] ) ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) ) : '';

    if ( ! $email ) {
        return;
    }

    $body = array( 'email' => $email );

    $simple = array( 'firstname', 'lastname', 'company', 'jobtitle', 'workphone' );

    foreach ( $simple as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $body[ $key ] = $value;
        }
    }

    $body = apply_filters( 'adfoin_on24_registrant_body', $body, $field_data, $posted_data );

    adfoin_on24_request( 'event/' . rawurlencode( $event_id ) . '/registrant', 'POST', $body, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_on24_request' ) ) :
/**
 * Call the ON24 REST API. Sends body as application/x-www-form-urlencoded.
 *
 * @param string $endpoint Path under /v2/client/{clientId}/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_on24_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $client_id    = '';
    $token_key    = '';
    $token_secret = '';
    $base_url     = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'on24', $cred_id );
        if ( is_array( $credentials ) ) {
            $client_id    = isset( $credentials['clientId'] )          ? trim( (string) $credentials['clientId'] )          : '';
            $token_key    = isset( $credentials['accessTokenKey'] )    ? trim( (string) $credentials['accessTokenKey'] )    : '';
            $token_secret = isset( $credentials['accessTokenSecret'] ) ? trim( (string) $credentials['accessTokenSecret'] ) : '';
            $base_url     = isset( $credentials['baseUrl'] )           ? trim( (string) $credentials['baseUrl'] )           : '';
        }
    }

    if ( ! $client_id || ! $token_key || ! $token_secret ) {
        return new WP_Error( 'on24_missing_credentials', __( 'ON24 credentials are not configured.', 'advanced-form-integration' ) );
    }

    if ( ! $base_url ) {
        $base_url = 'https://api.on24.com';
    }
    $base_url = untrailingslashit( $base_url );

    $url    = $base_url . '/v2/client/' . rawurlencode( $client_id ) . '/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'accesstokenkey'    => $token_key,
            'accesstokensecret' => $token_secret,
            'Accept'            => 'application/json',
        ),
    );

    if ( 'GET' === $method ) {
        if ( is_array( $data ) && ! empty( $data ) ) {
            $url = add_query_arg( array_map( 'rawurlencode', $data ), $url );
        }
    } else {
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $args['body']                    = is_array( $data ) ? http_build_query( $data ) : (string) $data;
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
