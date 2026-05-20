<?php

/**
 * EZ Texting — Create Contact via POST /v1/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: appKey + appSecret exchanged at a.eztexting.com/v1/tokens/create
 * for an access token, then sent as Authorization: Bearer on api.eztexting.com.
 *
 * @link https://www.eztexting.com/developers/sms-api-documentation/rest
 */

add_filter( 'adfoin_action_providers', 'adfoin_eztexting_actions', 10, 1 );

function adfoin_eztexting_actions( $actions ) {
    $actions['eztexting'] = array(
        'title' => __( 'EZ Texting', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_eztexting_settings_tab', 10, 1 );

function adfoin_eztexting_settings_tab( $providers ) {
    $providers['eztexting'] = __( 'EZ Texting', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_eztexting_settings_view', 10, 1 );

function adfoin_eztexting_settings_view( $current_tab ) {
    if ( 'eztexting' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'appKey',
            'label'         => __( 'App Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'          => 'appSecret',
            'label'         => __( 'App Secret', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In EZ Texting open your profile → API and create an App Key / App Secret pair.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI exchanges them for an access token at a.eztexting.com/v1/tokens/create and caches it until expiry.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'eztexting', __( 'EZ Texting', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_eztexting_credentials', 'adfoin_get_eztexting_credentials', 10, 0 );

function adfoin_get_eztexting_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'eztexting' );
}

add_action( 'wp_ajax_adfoin_save_eztexting_credentials', 'adfoin_save_eztexting_credentials', 10, 0 );

function adfoin_save_eztexting_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'eztexting', array( 'appKey', 'appSecret' ) );
}

if ( ! function_exists( 'adfoin_eztexting_credentials_list' ) ) :
function adfoin_eztexting_credentials_list() {
    foreach ( adfoin_read_credentials( 'eztexting' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

add_action( 'adfoin_action_fields', 'adfoin_eztexting_action_fields' );

function adfoin_eztexting_action_fields() {
    ?>
    <script type="text/template" id="eztexting-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'EZ Texting Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="spinner" v-bind:class="{'is-active': credLoading}" style="float:none;display:inline-block;width:20px;height:20px;vertical-align:middle;margin:0 6px;"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=eztexting' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'create_contact', 'EZ Texting [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_eztexting_fields', 'adfoin_get_eztexting_fields', 10, 0 );

function adfoin_get_eztexting_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_eztexting_base_fields() );
}

function adfoin_eztexting_base_fields() {
    return array(
        array( 'key' => 'phoneNumber', 'value' => __( 'Phone Number (E.164 or digits)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'firstName',   'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',    'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',       'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'note',        'value' => __( 'Note', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'groupIds',    'value' => __( 'Group IDs (comma separated)', 'advanced-form-integration' ) ),
    );
}

add_action( 'adfoin_eztexting_job_queue', 'adfoin_eztexting_job_queue', 10, 1 );

function adfoin_eztexting_job_queue( $data ) {
    adfoin_eztexting_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_eztexting_send_data( $record, $posted_data ) {
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

    $phone = isset( $field_data['phoneNumber'] )
        ? trim( (string) adfoin_get_parsed_values( $field_data['phoneNumber'], $posted_data ) )
        : '';

    if ( '' === $phone ) {
        return;
    }

    $payload = array( 'phoneNumber' => $phone );

    foreach ( array( 'firstName', 'lastName', 'email', 'note' ) as $key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $payload[ $key ] = $value;
        }
    }

    if ( ! empty( $field_data['groupIds'] ) ) {
        $raw    = adfoin_get_parsed_values( $field_data['groupIds'], $posted_data );
        $groups = is_array( $raw )
            ? array_map( 'trim', array_map( 'strval', $raw ) )
            : array_map( 'trim', explode( ',', (string) $raw ) );
        $groups = array_values( array_filter( $groups, 'strlen' ) );
        if ( ! empty( $groups ) ) {
            $payload['groupIds'] = $groups;
        }
    }

    $payload = apply_filters( 'adfoin_eztexting_contact_payload', $payload, $field_data, $posted_data );

    adfoin_eztexting_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_eztexting_get_access_token' ) ) :
/**
 * Exchange appKey + appSecret for an access token, cached per credential.
 *
 * @return string|WP_Error
 */
function adfoin_eztexting_get_access_token( $credentials, $force_refresh = false ) {
    $app_key    = isset( $credentials['appKey'] )    ? trim( (string) $credentials['appKey'] )    : '';
    $app_secret = isset( $credentials['appSecret'] ) ? trim( (string) $credentials['appSecret'] ) : '';

    if ( '' === $app_key || '' === $app_secret ) {
        return new WP_Error( 'eztexting_missing_auth', __( 'EZ Texting App Key or App Secret is missing.', 'advanced-form-integration' ) );
    }

    $cred_id   = isset( $credentials['id'] ) ? (string) $credentials['id'] : md5( $app_key );
    $cache_key = 'adfoin_eztexting_token_' . $cred_id;

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( is_string( $cached ) && $cached ) {
            return $cached;
        }
    }

    $response = wp_remote_post( 'https://a.eztexting.com/v1/tokens/create', array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
        'body'    => wp_json_encode( array(
            'appKey'    => $app_key,
            'appSecret' => $app_secret,
        ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['accessToken'] ) ) {
        $message = $body['message'] ?? __( 'Unable to obtain EZ Texting access token.', 'advanced-form-integration' );
        return new WP_Error( 'eztexting_token_error', $message, array( 'status' => wp_remote_retrieve_response_code( $response ) ) );
    }

    $expires_in = isset( $body['expiresIn'] ) ? (int) $body['expiresIn'] : 3600;
    set_transient( $cache_key, $body['accessToken'], max( 60, $expires_in - 60 ) );

    return $body['accessToken'];
}
endif;

if ( ! function_exists( 'adfoin_eztexting_request' ) ) :
/**
 * Call the EZ Texting v1 API. Bearer token via cached exchange.
 *
 * @param string $endpoint Path under /v1/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT/PATCH) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_eztexting_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = $cred_id && function_exists( 'adfoin_get_credentials_by_id' )
        ? adfoin_get_credentials_by_id( 'eztexting', $cred_id )
        : array();

    if ( ! is_array( $credentials ) || empty( $credentials ) ) {
        return new WP_Error( 'eztexting_missing_credentials', __( 'EZ Texting credentials not found.', 'advanced-form-integration' ) );
    }

    $token = adfoin_eztexting_get_access_token( $credentials );

    if ( is_wp_error( $token ) ) {
        if ( $record ) {
            adfoin_add_to_log( $token, '', array(), $record );
        }
        return $token;
    }

    $url    = 'https://api.eztexting.com/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
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

    // One-shot reactive refresh on 401.
    if ( ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $token = adfoin_eztexting_get_access_token( $credentials, true );
        if ( ! is_wp_error( $token ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
            $response = wp_remote_request( $url, $args );
            if ( $record ) {
                adfoin_add_to_log( $response, $url, $args, $record );
            }
        }
    }

    return $response;
}
endif;
