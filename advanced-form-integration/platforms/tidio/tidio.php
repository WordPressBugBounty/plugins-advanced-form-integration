<?php

/**
 * Tidio — Create Contact via POST /api/v1/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_key>
 *
 * Tidio's public Contacts endpoint upserts by email — a second submission
 * with the same email updates the existing contact rather than creating a
 * duplicate. Custom properties are sent as a list of name/value pairs.
 *
 * @link https://api.tidio.co/
 */

add_filter( 'adfoin_action_providers', 'adfoin_tidio_actions', 10, 1 );

function adfoin_tidio_actions( $actions ) {
    $actions['tidio'] = array(
        'title' => __( 'Tidio', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_tidio_settings_tab', 10, 1 );

function adfoin_tidio_settings_tab( $providers ) {
    $providers['tidio'] = __( 'Tidio', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_tidio_settings_view', 10, 1 );

function adfoin_tidio_settings_view( $current_tab ) {
    if ( 'tidio' !== $current_tab ) {
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
            'placeholder'   => __( 'Paste your Tidio Public API Key', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Tidio and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://www.tidio.com/panel/settings/integrations/api">Settings &rarr; Integrations &rarr; API</a>' ),
        esc_html__( 'Click "Generate API Key" (Public API Key, also called API Key v3).', 'advanced-form-integration' ),
        esc_html__( 'Copy the key — Tidio only shows it in full once.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.tidio.co/api/v1/ with this key in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'tidio', __( 'Tidio', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_tidio_credentials', 'adfoin_get_tidio_credentials', 10, 0 );

function adfoin_get_tidio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'tidio' );
}

add_action( 'wp_ajax_adfoin_save_tidio_credentials', 'adfoin_save_tidio_credentials', 10, 0 );

function adfoin_save_tidio_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'tidio', array( 'apiKey' ) );
}

function adfoin_tidio_credentials_list() {
    foreach ( adfoin_read_credentials( 'tidio' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_tidio_action_fields' );

function adfoin_tidio_action_fields() {
    ?>
    <script type="text/template" id="tidio-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Tidio Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=tidio' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_tidio_fields', 'adfoin_get_tidio_fields' );

function adfoin_get_tidio_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'email',   'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'name',    'value' => __( 'Name', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',   'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'city',    'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country (ISO-2, e.g. US)', 'advanced-form-integration' ) ),
        array( 'key' => 'tags',    'value' => __( 'Tags (comma-separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company (custom property)', 'advanced-form-integration' ) ),
        array( 'key' => 'source',  'value' => __( 'Source (custom property)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_tidio_job_queue', 'adfoin_tidio_job_queue', 10, 1 );

function adfoin_tidio_job_queue( $data ) {
    adfoin_tidio_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_tidio_send_data( $record, $posted_data ) {
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

    // Resolve all flat values up-front. The Tidio payload is assembled
    // below from these resolved values.
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

    // Email is required by the Tidio Contacts endpoint — bail early if the
    // form didn't supply one rather than firing a guaranteed 400.
    if ( empty( $values['email'] ) ) {
        return;
    }

    $payload = array(
        'email' => $values['email'],
    );

    foreach ( array( 'name', 'phone', 'city', 'country' ) as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $payload[ $key ] = $values[ $key ];
        }
    }

    // Tags arrive from the form as a comma-separated string; Tidio expects
    // an array of strings. Trim each piece and drop empties.
    if ( ! empty( $values['tags'] ) ) {
        $tags = array_filter( array_map( 'trim', explode( ',', (string) $values['tags'] ) ), 'strlen' );
        if ( ! empty( $tags ) ) {
            $payload['tags'] = array_values( $tags );
        }
    }

    // Custom properties are name/value pairs. Only emit the ones the form
    // actually populated — sending empty values would clear them in Tidio.
    $custom_properties = array();
    foreach ( array( 'company', 'source' ) as $prop ) {
        if ( ! empty( $values[ $prop ] ) ) {
            $custom_properties[] = array(
                'name'  => $prop,
                'value' => (string) $values[ $prop ],
            );
        }
    }
    if ( ! empty( $custom_properties ) ) {
        $payload['custom_properties'] = $custom_properties;
    }

    adfoin_tidio_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_tidio_request' ) ) :
function adfoin_tidio_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'tidio', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiKey'] ) ) {
        return new WP_Error( 'tidio_missing_credentials', __( 'Tidio API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.tidio.co/api/v1/' . ltrim( $endpoint, '/' );
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
