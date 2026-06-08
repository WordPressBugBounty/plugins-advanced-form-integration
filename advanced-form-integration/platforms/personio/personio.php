<?php

/**
 * Personio — Create Applicant via POST /v1/recruiting/applicants.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: client_credentials — POST client_id+client_secret to /v1/auth,
 * receive a short-lived JWT (~24h) in `data.token`, then send it as
 * `Authorization: Bearer <token>` on subsequent requests. No user redirect
 * popup is needed (service-to-service), and there is no refresh token —
 * expired JWTs are obtained by re-calling /v1/auth.
 *
 * The applicants endpoint expects multipart/form-data (not JSON).
 *
 * @link https://developer.personio.de/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_personio_actions', 10, 1 );

function adfoin_personio_actions( $actions ) {
    $actions['personio'] = array(
        'title' => __( 'Personio', 'advanced-form-integration' ),
        'tasks' => array(
            'create_applicant' => __( 'Create Applicant', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_personio_settings_tab', 10, 1 );

function adfoin_personio_settings_tab( $providers ) {
    $providers['personio'] = __( 'Personio', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_personio_settings_view', 10, 1 );

function adfoin_personio_settings_view( $current_tab ) {
    if ( 'personio' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'client_id',
            'label'         => __( 'Client ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Personio API Client ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'client_secret',
            'label'       => __( 'Client Secret', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Personio API Client Secret', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Personio as an admin and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.personio.de/docs/getting-started-with-the-personio-api">Settings &rarr; Integrations &rarr; API Credentials</a>' ),
        esc_html__( 'Generate a new credential pair scoped to the Recruiting API (read/write applicants, read positions).', 'advanced-form-integration' ),
        esc_html__( 'Copy the Client ID and Client Secret — the secret is only shown once.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI exchanges them for a short-lived JWT at https://api.personio.de/v1/auth.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'personio', __( 'Personio', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_personio_credentials', 'adfoin_get_personio_credentials', 10, 0 );

function adfoin_get_personio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'personio' );
}

add_action( 'wp_ajax_adfoin_save_personio_credentials', 'adfoin_save_personio_credentials', 10, 0 );

function adfoin_save_personio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'personio', array( 'client_id', 'client_secret' ) );
}

function adfoin_personio_credentials_list() {
    foreach ( adfoin_read_credentials( 'personio' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_personio_action_fields' );

function adfoin_personio_action_fields() {
    ?>
    <script type="text/template" id="personio-action-template">
        <table class="form-table" v-if="action.task == 'create_applicant'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Personio Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=personio' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_personio_fields', 'adfoin_get_personio_fields' );

function adfoin_get_personio_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'first_name',           'value' => __( 'First Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'last_name',            'value' => __( 'Last Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email',                'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'job_position_id',      'value' => __( 'Job Position ID (required, integer)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'recruiting_channel_id','value' => __( 'Recruiting Channel ID (integer)', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',                'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'gender',               'value' => __( 'Gender (male / female / diverse)', 'advanced-form-integration' ) ),
        array( 'key' => 'birthday',             'value' => __( 'Birthday (YYYY-MM-DD)', 'advanced-form-integration' ) ),
        array( 'key' => 'location',             'value' => __( 'Location', 'advanced-form-integration' ) ),
        array( 'key' => 'salary_expectations',  'value' => __( 'Salary Expectations', 'advanced-form-integration' ) ),
        array( 'key' => 'available_from',       'value' => __( 'Available From', 'advanced-form-integration' ) ),
        array( 'key' => 'message',              'value' => __( 'Message / Cover Letter', 'advanced-form-integration' ) ),
        array( 'key' => 'tags',                 'value' => __( 'Tags (comma-separated)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_personio_job_queue', 'adfoin_personio_job_queue', 10, 1 );

function adfoin_personio_job_queue( $data ) {
    adfoin_personio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_personio_send_data( $record, $posted_data ) {
    if ( 'create_applicant' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    $reserved = array( 'credId' => 1 );
    $values   = array();
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // Required fields — abort silently if any are missing.
    if ( empty( $values['first_name'] ) || empty( $values['last_name'] ) || empty( $values['email'] ) || empty( $values['job_position_id'] ) ) {
        return;
    }

    $body = array(
        'first_name'      => (string) $values['first_name'],
        'last_name'       => (string) $values['last_name'],
        'email'           => (string) $values['email'],
        'job_position_id' => (int) $values['job_position_id'],
    );

    if ( ! empty( $values['recruiting_channel_id'] ) ) {
        $body['recruiting_channel_id'] = (int) $values['recruiting_channel_id'];
    }

    $optional_string_keys = array( 'phone', 'gender', 'birthday', 'location', 'salary_expectations', 'available_from', 'message' );
    foreach ( $optional_string_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $body[ $key ] = (string) $values[ $key ];
        }
    }

    // Tags — accept comma-separated string and expand to tags[] array.
    if ( ! empty( $values['tags'] ) ) {
        $tags = is_array( $values['tags'] )
            ? $values['tags']
            : array_filter( array_map( 'trim', explode( ',', (string) $values['tags'] ) ) );
        if ( ! empty( $tags ) ) {
            $body['tags'] = array_values( $tags );
        }
    }

    adfoin_personio_request( 'recruiting/applicants', 'POST', $body, $record, $cred_id, true );
}

if ( ! function_exists( 'adfoin_personio_get_token' ) ) :
/**
 * Fetch (and cache) a Personio JWT for the given credential. Caches for 23h,
 * a little under Personio's ~24h expiry. Set $force_refresh to bypass cache
 * (e.g. after a 401 response).
 */
function adfoin_personio_get_token( $cred_id, $force_refresh = false ) {
    if ( empty( $cred_id ) ) {
        return new WP_Error( 'personio_missing_cred', __( 'Personio credential ID is empty.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_personio_token_' . md5( (string) $cred_id );

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( ! empty( $cached ) && is_string( $cached ) ) {
            return $cached;
        }
    }

    $credentials = adfoin_get_credentials_by_id( 'personio', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['client_id'] ) || empty( $credentials['client_secret'] ) ) {
        return new WP_Error( 'personio_missing_credentials', __( 'Personio client_id / client_secret not configured.', 'advanced-form-integration' ) );
    }

    $response = wp_remote_post(
        'https://api.personio.de/v1/auth',
        array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ),
            'body'    => array(
                'client_id'     => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $code = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $code || empty( $body['success'] ) || empty( $body['data']['token'] ) ) {
        $msg = isset( $body['error']['message'] ) ? $body['error']['message'] : sprintf( 'HTTP %d', $code );
        return new WP_Error( 'personio_auth_failed', is_string( $msg ) ? $msg : wp_json_encode( $msg ) );
    }

    $token = (string) $body['data']['token'];

    // 23h — Personio JWTs are typically ~24h.
    set_transient( $cache_key, $token, 23 * HOUR_IN_SECONDS );

    return $token;
}
endif;

if ( ! function_exists( 'adfoin_personio_request' ) ) :
/**
 * Authenticated request against https://api.personio.de/v1/. On 401, refresh
 * the JWT once and retry. If $multipart is true the body is passed through to
 * WP HTTP as a raw array (Content-Type unset) so the underlying transport
 * builds a proper multipart/form-data payload — required by the recruiting
 * applicants endpoint.
 */
function adfoin_personio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '', $multipart = false ) {
    $token = adfoin_personio_get_token( $cred_id );

    if ( is_wp_error( $token ) ) {
        if ( $record ) {
            adfoin_add_to_log( $token, 'https://api.personio.de/v1/auth', array( 'method' => 'POST' ), $record );
        }
        return $token;
    }

    $response = adfoin_personio_dispatch( $token, $endpoint, $method, $data, $record, $multipart );

    // On 401, refresh the JWT once and retry — covers the case where the
    // cached token was revoked or expired earlier than the 23h transient TTL.
    if ( ! is_wp_error( $response ) ) {
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 401 === $code ) {
            $token = adfoin_personio_get_token( $cred_id, true );
            if ( ! is_wp_error( $token ) ) {
                $response = adfoin_personio_dispatch( $token, $endpoint, $method, $data, $record, $multipart );
            }
        }
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_personio_dispatch' ) ) :
/**
 * Internal: build + execute a single Personio request with the supplied JWT.
 * Split out so adfoin_personio_request can cleanly retry on 401.
 */
function adfoin_personio_dispatch( $token, $endpoint, $method, $data, $record, $multipart ) {
    $url    = 'https://api.personio.de/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        if ( $multipart ) {
            // Leave Content-Type unset so WP_HTTP can negotiate multipart with
            // a boundary. Body is passed straight through as an array.
            $args['body'] = is_array( $data ) ? $data : array();
        } else {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body']                    = wp_json_encode( $data );
        }
    } elseif ( 'GET' === $method && is_array( $data ) && ! empty( $data ) ) {
        $url = add_query_arg( $data, $url );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
