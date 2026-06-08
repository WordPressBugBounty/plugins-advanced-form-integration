<?php

/**
 * Donorbox — Create Donor via POST /api/v1/donors.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: HTTP Basic — Authorization: Basic base64(login_email:api_key).
 *
 * The login_email must belong to a Donorbox account admin; the API key is
 * generated from Donorbox dashboard → Account → API.
 *
 * @link https://github.com/donorbox/donorbox-api
 */

add_filter( 'adfoin_action_providers', 'adfoin_donorbox_actions', 10, 1 );

function adfoin_donorbox_actions( $actions ) {
    $actions['donorbox'] = array(
        'title' => __( 'Donorbox', 'advanced-form-integration' ),
        'tasks' => array(
            'create_donor' => __( 'Create Donor', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_donorbox_settings_tab', 10, 1 );

function adfoin_donorbox_settings_tab( $providers ) {
    $providers['donorbox'] = __( 'Donorbox', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_donorbox_settings_view', 10, 1 );

function adfoin_donorbox_settings_view( $current_tab ) {
    if ( 'donorbox' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'loginEmail',
            'label'         => __( 'Donorbox Login Email', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => __( 'admin@yourorg.org', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiKey',
            'label'         => __( 'API Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Donorbox API key', 'advanced-form-integration' ),
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Donorbox and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://donorbox.org/orgadmin/account">Account → API</a>' ),
        esc_html__( 'Generate a new API key and copy it immediately — Donorbox only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Enter the login email of a Donorbox account admin below. The same email MUST be an admin on the Donorbox organization, otherwise the API will reject the request.', 'advanced-form-integration' ),
        esc_html__( 'Paste the API key below.', 'advanced-form-integration' ),
        esc_html__( 'AFI calls https://donorbox.org/api/v1/ using HTTP Basic Auth with these credentials.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'donorbox', __( 'Donorbox', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_donorbox_credentials', 'adfoin_get_donorbox_credentials', 10, 0 );

function adfoin_get_donorbox_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'donorbox' );
}

add_action( 'wp_ajax_adfoin_save_donorbox_credentials', 'adfoin_save_donorbox_credentials', 10, 0 );

function adfoin_save_donorbox_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'donorbox', array( 'loginEmail', 'apiKey' ) );
}

function adfoin_donorbox_credentials_list() {
    foreach ( adfoin_read_credentials( 'donorbox' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_donorbox_action_fields' );

function adfoin_donorbox_action_fields() {
    ?>
    <script type="text/template" id="donorbox-action-template">
        <table class="form-table" v-if="action.task == 'create_donor'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Donorbox Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=donorbox' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_donorbox_fields', 'adfoin_get_donorbox_fields' );

function adfoin_get_donorbox_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'first_name',     'value' => __( 'First Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'last_name',      'value' => __( 'Last Name (required)', 'advanced-form-integration' ),  'required' => true ),
        array( 'key' => 'email',          'value' => __( 'Email (required)', 'advanced-form-integration' ),      'required' => true ),
        array( 'key' => 'phone',          'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'street_address', 'value' => __( 'Street Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city',           'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state',          'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',            'value' => __( 'ZIP / Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country',        'value' => __( 'Country (defaults to United States)', 'advanced-form-integration' ) ),
        array( 'key' => 'company',        'value' => __( 'Company / Organization', 'advanced-form-integration' ) ),
        array( 'key' => 'is_anonymous',   'value' => __( 'Is Anonymous (true / false — defaults to false)', 'advanced-form-integration' ) ),
        array( 'key' => 'notes',          'value' => __( 'Notes', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_donorbox_job_queue', 'adfoin_donorbox_job_queue', 10, 1 );

function adfoin_donorbox_job_queue( $data ) {
    adfoin_donorbox_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_donorbox_send_data( $record, $posted_data ) {
    if ( 'create_donor' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve all flat values up-front.
    $values   = array();
    $reserved = array( 'credId' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    // Required fields: email + first_name + last_name.
    if ( empty( $values['email'] ) || empty( $values['first_name'] ) || empty( $values['last_name'] ) ) {
        return;
    }

    // Default country.
    if ( empty( $values['country'] ) ) {
        $values['country'] = 'United States';
    }

    // Cast is_anonymous to bool — accept "true"/"1"/"yes"/true, everything else
    // (including unset) becomes false.
    if ( array_key_exists( 'is_anonymous', $values ) ) {
        $raw = $values['is_anonymous'];
        if ( is_string( $raw ) ) {
            $raw = strtolower( trim( $raw ) );
            $values['is_anonymous'] = in_array( $raw, array( 'true', '1', 'yes', 'on' ), true );
        } else {
            $values['is_anonymous'] = (bool) $raw;
        }
    } else {
        $values['is_anonymous'] = false;
    }

    $donor = array(
        'first_name'   => $values['first_name'],
        'last_name'    => $values['last_name'],
        'email'        => $values['email'],
        'is_anonymous' => $values['is_anonymous'],
    );

    $optional_keys = array( 'phone', 'street_address', 'city', 'state', 'zip', 'country', 'company', 'notes' );
    foreach ( $optional_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $donor[ $key ] = $values[ $key ];
        }
    }

    $payload = array( 'donor' => $donor );

    adfoin_donorbox_request( 'donors', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_donorbox_request' ) ) :
function adfoin_donorbox_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'donorbox', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['loginEmail'] ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'donorbox_missing_credentials', __( 'Donorbox login email or API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://donorbox.org/api/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $auth = base64_encode( $credentials['loginEmail'] . ':' . $credentials['apiKey'] );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
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
