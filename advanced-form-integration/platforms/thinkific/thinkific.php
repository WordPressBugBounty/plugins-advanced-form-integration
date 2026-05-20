<?php

/**
 * Thinkific — online-course platform integration.
 *
 *   - create_user        → POST /users
 *   - create_enrollment  → GET /users?query[email]={email}  (lookup, required)
 *                          POST /enrollments  (course_id + user_id)
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: two custom headers — `X-Auth-API-Key` and `X-Auth-Subdomain`.
 * The subdomain is the prefix of the customer's *.thinkific.com URL
 * (e.g. `mycompany` for mycompany.thinkific.com).
 *
 * For create_enrollment, the user is looked up by email and the action
 * fails gracefully (via the central request log) if no match is found —
 * v1 deliberately does *not* auto-create users on enroll.
 *
 * @link https://developers.thinkific.com/api/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_thinkific_actions', 10, 1 );

function adfoin_thinkific_actions( $actions ) {
    $actions['thinkific'] = array(
        'title' => __( 'Thinkific', 'advanced-form-integration' ),
        'tasks' => array(
            'create_user'       => __( 'Create User', 'advanced-form-integration' ),
            'create_enrollment' => __( 'Enroll User in Course', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_thinkific_settings_tab', 10, 1 );

function adfoin_thinkific_settings_tab( $providers ) {
    $providers['thinkific'] = __( 'Thinkific', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_thinkific_settings_view', 10, 1 );

function adfoin_thinkific_settings_view( $current_tab ) {
    if ( 'thinkific' !== $current_tab ) {
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
            'mask'          => true,
            'placeholder'   => __( 'Paste your Thinkific API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'subdomain',
            'label'       => __( 'Subdomain', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'placeholder' => __( 'mycompany', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Sign in to your Thinkific Site Admin dashboard.', 'advanced-form-integration' ),
        esc_html__( 'Open Settings → Code & analytics → API keys.', 'advanced-form-integration' ),
        esc_html__( 'Generate (or reveal) your API key and copy it.', 'advanced-form-integration' ),
        esc_html__( 'Enter only the subdomain part of your Thinkific URL — e.g. "mycompany" for mycompany.thinkific.com.', 'advanced-form-integration' ),
        esc_html__( 'AFI calls https://api.thinkific.com/api/public/v1/ with the X-Auth-API-Key and X-Auth-Subdomain headers.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'thinkific', __( 'Thinkific', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_thinkific_credentials', 'adfoin_get_thinkific_credentials', 10, 0 );

function adfoin_get_thinkific_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'thinkific' );
}

add_action( 'wp_ajax_adfoin_save_thinkific_credentials', 'adfoin_save_thinkific_credentials', 10, 0 );

function adfoin_save_thinkific_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'thinkific', array( 'apiKey', 'subdomain' ) );
}

function adfoin_thinkific_credentials_list() {
    foreach ( adfoin_read_credentials( 'thinkific' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_thinkific_action_fields' );

function adfoin_thinkific_action_fields() {
    ?>
    <script type="text/template" id="thinkific-action-template">
        <table class="form-table" v-if="action.task == 'create_user' || action.task == 'create_enrollment'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Thinkific Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=thinkific' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_thinkific_fields', 'adfoin_get_thinkific_fields' );

function adfoin_get_thinkific_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_user';

    if ( 'create_enrollment' === $task ) {
        $fields = array(
            array( 'key' => 'course_id',    'value' => __( 'Course ID (required, integer)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'user_email',   'value' => __( 'User Email (required — looked up in Thinkific)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'activated_at', 'value' => __( 'Activated At (ISO-8601; defaults to now)', 'advanced-form-integration' ) ),
            array( 'key' => 'expiry_date',  'value' => __( 'Expiry Date (ISO-8601; optional, blank = lifetime)', 'advanced-form-integration' ) ),
        );
    } else {
        // create_user (default)
        $fields = array(
            array( 'key' => 'email',              'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
            array( 'key' => 'first_name',         'value' => __( 'First Name (one of First/Last required)', 'advanced-form-integration' ) ),
            array( 'key' => 'last_name',          'value' => __( 'Last Name (one of First/Last required)', 'advanced-form-integration' ) ),
            array( 'key' => 'password',           'value' => __( 'Password (optional — omit to let Thinkific auto-generate)', 'advanced-form-integration' ) ),
            array( 'key' => 'send_welcome_email', 'value' => __( 'Send Welcome Email (true / false — default true)', 'advanced-form-integration' ) ),
            array( 'key' => 'company',            'value' => __( 'Company', 'advanced-form-integration' ) ),
            array( 'key' => 'bio',                'value' => __( 'Bio', 'advanced-form-integration' ) ),
        );
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_thinkific_job_queue', 'adfoin_thinkific_job_queue', 10, 1 );

function adfoin_thinkific_job_queue( $data ) {
    adfoin_thinkific_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_thinkific_send_data( $record, $posted_data ) {
    $task = $record['task'] ?? '';

    if ( ! in_array( $task, array( 'create_user', 'create_enrollment' ), true ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    // Modern CL guard — short-circuit when conditional logic fails.
    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    // Resolve all field-mapped values up-front.
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

    if ( 'create_user' === $task ) {
        // Required: email + at least one of first_name / last_name.
        if ( empty( $values['email'] ) ) {
            return;
        }
        if ( empty( $values['first_name'] ) && empty( $values['last_name'] ) ) {
            return;
        }

        $payload = array(
            'email' => (string) $values['email'],
        );
        if ( ! empty( $values['first_name'] ) ) {
            $payload['first_name'] = (string) $values['first_name'];
        }
        if ( ! empty( $values['last_name'] ) ) {
            $payload['last_name'] = (string) $values['last_name'];
        }
        if ( ! empty( $values['password'] ) ) {
            $payload['password'] = (string) $values['password'];
        }
        if ( ! empty( $values['company'] ) ) {
            $payload['company'] = (string) $values['company'];
        }
        if ( ! empty( $values['bio'] ) ) {
            $payload['bio'] = (string) $values['bio'];
        }

        // send_welcome_email: default true; coerce common truthy/falsy strings.
        $payload['send_welcome_email'] = adfoin_thinkific_to_bool( $values['send_welcome_email'] ?? null, true );

        adfoin_thinkific_request( 'users', 'POST', $payload, $record, $cred_id );
        return;
    }

    // create_enrollment — lookup user by email; do NOT auto-create in v1.
    if ( empty( $values['course_id'] ) || empty( $values['user_email'] ) ) {
        return;
    }

    $email   = (string) $values['user_email'];
    $user_id = 0;

    // Thinkific's filter syntax is query[email]=... — must be sent as a literal
    // querystring (add_query_arg URL-encodes the brackets, which Thinkific
    // rejects). Build the querystring manually here.
    $lookup_endpoint = 'users?' . sprintf( 'query[email]=%s', rawurlencode( $email ) );
    $lookup          = adfoin_thinkific_request( $lookup_endpoint, 'GET', array(), $record, $cred_id );

    if ( is_wp_error( $lookup ) ) {
        return; // already logged by the request helper
    }

    $code = (int) wp_remote_retrieve_response_code( $lookup );
    if ( 200 === $code ) {
        $body = json_decode( wp_remote_retrieve_body( $lookup ), true );
        if ( ! empty( $body['items'][0]['id'] ) ) {
            $user_id = (int) $body['items'][0]['id'];
        }
    }

    if ( ! $user_id ) {
        // User not found — fail gracefully. The lookup response is already in
        // the request log; surface a short marker so the failure is obvious.
        if ( $record ) {
            adfoin_add_to_log(
                new WP_Error( 'thinkific_user_not_found', sprintf( 'No Thinkific user found for %s — skipping enrollment.', $email ) ),
                'https://api.thinkific.com/api/public/v1/users',
                array( 'method' => 'GET', 'query' => array( 'email' => $email ) ),
                $record
            );
        }
        return;
    }

    $enroll_payload = array(
        'course_id'    => (int) $values['course_id'],
        'user_id'      => $user_id,
        'activated_at' => ! empty( $values['activated_at'] ) ? (string) $values['activated_at'] : gmdate( 'Y-m-d\TH:i:s\Z' ),
    );

    if ( ! empty( $values['expiry_date'] ) ) {
        $enroll_payload['expiry_date'] = (string) $values['expiry_date'];
    }

    adfoin_thinkific_request( 'enrollments', 'POST', $enroll_payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_thinkific_to_bool' ) ) :
/**
 * Coerce a free-form mapped value into a boolean. Empty / null falls back
 * to $default — used for send_welcome_email which defaults to true.
 */
function adfoin_thinkific_to_bool( $value, $default = false ) {
    if ( null === $value || '' === $value ) {
        return (bool) $default;
    }
    if ( is_bool( $value ) ) {
        return $value;
    }
    if ( is_numeric( $value ) ) {
        return (bool) (int) $value;
    }
    $normalized = strtolower( trim( (string) $value ) );
    if ( in_array( $normalized, array( 'false', 'no', 'off', '0', 'n' ), true ) ) {
        return false;
    }
    if ( in_array( $normalized, array( 'true', 'yes', 'on', '1', 'y' ), true ) ) {
        return true;
    }
    return (bool) $default;
}
endif;

if ( ! function_exists( 'adfoin_thinkific_sanitize_subdomain' ) ) :
/**
 * Strip protocol, trailing .thinkific.com, and slashes from a user-supplied
 * subdomain string. Tolerates pastes like "https://mycompany.thinkific.com/".
 */
function adfoin_thinkific_sanitize_subdomain( $raw ) {
    $value = trim( (string) $raw );
    if ( '' === $value ) {
        return '';
    }
    $value = preg_replace( '#^https?://#i', '', $value );
    $value = preg_replace( '#\.thinkific\.com.*$#i', '', $value );
    $value = trim( $value, "/ \t\n\r\0\x0B" );
    return $value;
}
endif;

if ( ! function_exists( 'adfoin_thinkific_request' ) ) :
/**
 * Authenticated JSON request against https://api.thinkific.com/api/public/v1/.
 * Adds the two custom auth headers (X-Auth-API-Key + X-Auth-Subdomain) and
 * logs the response when a $record is supplied.
 */
function adfoin_thinkific_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'thinkific', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) || empty( $credentials['subdomain'] ) ) {
        return new WP_Error( 'thinkific_missing_credentials', __( 'Thinkific API key or subdomain not configured.', 'advanced-form-integration' ) );
    }

    $subdomain = adfoin_thinkific_sanitize_subdomain( $credentials['subdomain'] );
    if ( '' === $subdomain ) {
        return new WP_Error( 'thinkific_bad_subdomain', __( 'Thinkific subdomain is empty after sanitization.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.thinkific.com/api/public/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-Auth-API-Key'   => (string) $credentials['apiKey'],
            'X-Auth-Subdomain' => $subdomain,
            'Accept'           => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( $data );
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
