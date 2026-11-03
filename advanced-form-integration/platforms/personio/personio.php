<?php

/**
 * Personio — Create Applicant via POST /v1/recruiting/applications.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: the Recruiting API key (Settings → Integrations → API credentials →
 * Recruiting API key) — `Authorization: Bearer <access token>` plus the
 * `X-Company-ID` header. The Personnel API client_id/client_secret JWT from
 * /v1/auth is NOT accepted by the recruiting endpoints.
 *
 * The applications endpoint takes JSON; applicant details other than name,
 * email and position (phone, gender, birthday…) go in `attributes`.
 *
 * @link https://developer.personio.de/v1.0/reference/post_v1-recruiting-applications
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
            'name'          => 'company_id',
            'label'         => __( 'Company ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'Personio Company ID', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'access_token',
            'label'       => __( 'Recruiting API Access Token', 'advanced-form-integration' ),
            'type'        => 'password',
            'required'    => true,
            'mask'        => true,
            'placeholder' => __( 'Recruiting API access token', 'advanced-form-integration' ),
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Personio as an admin and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://developer.personio.de/docs/getting-started-with-the-personio-api">Settings &rarr; Integrations &rarr; API Credentials</a>' ),
        esc_html__( 'Open the "Recruiting API key" entry (not the Personnel API client ID / secret).', 'advanced-form-integration' ),
        esc_html__( 'Copy the Company ID and the Access Token shown there.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below.', 'advanced-form-integration' )
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
    ADFOIN_Account_Manager::ajax_save_credentials( 'personio', array( 'company_id' => 'text', 'access_token' => 'token' ) );
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
        array( 'key' => 'gender',               'value' => __( 'Gender (male / female / diverse / undefined)', 'advanced-form-integration' ) ),
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

    if ( ! empty( $values['message'] ) ) {
        $body['message'] = (string) $values['message'];
    }

    // Standard applicant attributes are sent as { id, value } pairs.
    $attribute_keys = array( 'phone', 'gender', 'birthday', 'location', 'salary_expectations', 'available_from' );
    $attributes     = array();
    foreach ( $attribute_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $attributes[] = array(
                'id'    => $key,
                'value' => (string) $values[ $key ],
            );
        }
    }
    if ( ! empty( $attributes ) ) {
        $body['attributes'] = $attributes;
    }

    // Tags — accept comma-separated string and expand to an array of strings.
    if ( ! empty( $values['tags'] ) ) {
        $tags = is_array( $values['tags'] )
            ? $values['tags']
            : array_filter( array_map( 'trim', explode( ',', (string) $values['tags'] ) ) );
        if ( ! empty( $tags ) ) {
            $body['tags'] = array_values( array_map( 'strval', $tags ) );
        }
    }

    adfoin_personio_request( 'recruiting/applications', 'POST', $body, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_personio_request' ) ) :
/**
 * Request against https://api.personio.de/v1/ authenticated with the
 * account's Recruiting API key (Bearer access token + X-Company-ID).
 */
function adfoin_personio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'personio', $cred_id );
    $company_id  = is_array( $credentials ) ? trim( (string) ( $credentials['company_id'] ?? '' ) ) : '';
    $token       = is_array( $credentials ) ? trim( (string) ( $credentials['access_token'] ?? '' ) ) : '';
    $url         = 'https://api.personio.de/v1/' . ltrim( $endpoint, '/' );

    if ( '' === $company_id || '' === $token ) {
        $error = new WP_Error( 'personio_missing_credentials', __( 'Personio needs the Company ID and Recruiting API access token. Edit the Personio account and add them (the Personnel API client ID / secret cannot create applications).', 'advanced-form-integration' ) );
        if ( $record ) {
            adfoin_add_to_log( $error, $url, array( 'method' => strtoupper( $method ) ), $record );
        }
        return $error;
    }

    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'X-Company-ID'  => $company_id,
            'Accept'        => 'application/json',
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
