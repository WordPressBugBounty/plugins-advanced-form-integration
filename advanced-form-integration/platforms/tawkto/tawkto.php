<?php

/**
 * Tawk.to — Add/Update Contact via POST /v1/property/{property_id}/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_key>
 *
 * A Tawk.to "property" is a website install. The property ID is required
 * for nearly every endpoint and is stored on the credential record next
 * to the API key — the dispatcher reads both from the same credential.
 *
 * @link https://www.tawk.to/api/
 */

add_filter( 'adfoin_action_providers', 'adfoin_tawkto_actions', 10, 1 );

function adfoin_tawkto_actions( $actions ) {
    $actions['tawkto'] = array(
        'title' => __( 'Tawk.to', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Add/Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_tawkto_settings_tab', 10, 1 );

function adfoin_tawkto_settings_tab( $providers ) {
    $providers['tawkto'] = __( 'Tawk.to', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_tawkto_settings_view', 10, 1 );

function adfoin_tawkto_settings_view( $current_tab ) {
    if ( 'tawkto' !== $current_tab ) {
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
            'placeholder'   => __( 'Paste your Tawk.to API key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'propertyId',
            'label'         => __( 'Property ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => false,
            'placeholder'   => __( 'e.g. 5f1a2b3c4d5e6f7a8b9c0d1e (24-char hex)', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to %s and open your profile.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://dashboard.tawk.to/">Tawk.to Dashboard</a>' ),
        esc_html__( 'Go to My Profile → API Keys and click "Create API Key". Copy the generated key.', 'advanced-form-integration' ),
        esc_html__( 'Find your Property ID in the dashboard URL (e.g. /property/<PROPERTY_ID>/...) or under Administration → Property Settings.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI calls https://api.tawk.to/v1/ with the key in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'tawkto', __( 'Tawk.to', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_tawkto_credentials', 'adfoin_get_tawkto_credentials', 10, 0 );

function adfoin_get_tawkto_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'tawkto' );
}

add_action( 'wp_ajax_adfoin_save_tawkto_credentials', 'adfoin_save_tawkto_credentials', 10, 0 );

function adfoin_save_tawkto_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'tawkto', array( 'apiKey', 'propertyId' ) );
}

function adfoin_tawkto_credentials_list() {
    foreach ( adfoin_read_credentials( 'tawkto' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_tawkto_action_fields' );

function adfoin_tawkto_action_fields() {
    ?>
    <script type="text/template" id="tawkto-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Tawk.to Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=tawkto' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                    <p class="description"><?php esc_html_e( 'Each Tawk.to account is bound to a single Property ID — the destination property is set when you save the credential.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_tawkto_fields', 'adfoin_get_tawkto_fields' );

function adfoin_get_tawkto_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'name',    'value' => __( 'Name (required)', 'advanced-form-integration' ),     'required' => true ),
        array( 'key' => 'email',   'value' => __( 'Email (required)', 'advanced-form-integration' ),    'required' => true ),
        array( 'key' => 'phone',   'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'notes',   'value' => __( 'Notes', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country (ISO-2, e.g. US)', 'advanced-form-integration' ) ),
        array( 'key' => 'city',    'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company (sent as custom attribute)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_tawkto_job_queue', 'adfoin_tawkto_job_queue', 10, 1 );

function adfoin_tawkto_job_queue( $data ) {
    adfoin_tawkto_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_tawkto_send_data( $record, $posted_data ) {
    if ( 'create_contact' !== ( $record['task'] ?? '' ) ) {
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

    // Resolve flat values from the editable-field map. The keys correspond
    // 1:1 with the Tawk.to contact fields (except "company", which is sent
    // as a custom attribute since contacts don't have a first-class
    // company column).
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

    // Both name and email are required by the Tawk.to contacts endpoint;
    // bail silently if either is missing so we don't pollute the log with
    // guaranteed 4xx responses.
    if ( empty( $values['name'] ) || empty( $values['email'] ) ) {
        return;
    }

    $credentials = adfoin_get_credentials_by_id( 'tawkto', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['propertyId'] ) ) {
        return;
    }

    $property_id = $credentials['propertyId'];

    $payload = array(
        'name'  => $values['name'],
        'email' => $values['email'],
    );

    foreach ( array( 'phone', 'notes', 'country', 'city' ) as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $payload[ $key ] = $values[ $key ];
        }
    }

    // Tawk.to supports per-contact custom attributes; fold "company" in
    // there so we don't lose it when the user maps it.
    if ( ! empty( $values['company'] ) ) {
        $payload['attributes'] = array(
            'company' => $values['company'],
        );
    }

    $endpoint = 'property/' . rawurlencode( $property_id ) . '/contacts';

    adfoin_tawkto_request( $endpoint, 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_tawkto_request' ) ) :
function adfoin_tawkto_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'tawkto', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'tawkto_missing_credentials', __( 'Tawk.to API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.tawk.to/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiKey'],
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
