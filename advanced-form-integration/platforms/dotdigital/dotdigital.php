<?php

/**
 * Dotdigital — Create or Update Contact via POST /v2/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: HTTP Basic with apiuser-xxxxx@apiconnector.com + password.
 *
 * @link https://developer.dotdigital.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_dotdigital_actions', 10, 1 );

function adfoin_dotdigital_actions( $actions ) {

    $actions['dotdigital'] = array(
        'title' => __( 'Dotdigital', 'advanced-form-integration' ),
        'tasks' => array(
            'add_contact' => __( 'Create or Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_dotdigital_settings_tab', 10, 1 );

function adfoin_dotdigital_settings_tab( $providers ) {
    $providers['dotdigital'] = __( 'Dotdigital', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_dotdigital_settings_view', 10, 1 );

function adfoin_dotdigital_settings_view( $current_tab ) {
    if ( 'dotdigital' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiUser',
            'label'         => __( 'API Username', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => 'apiuser-xxxxx@apiconnector.com',
            'show_in_table' => true,
        ),
        array(
            'name'          => 'apiPass',
            'label'         => __( 'API Password', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'region',
            'label'         => __( 'Region', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => 'r1',
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Dotdigital go to Settings → Access → API users and create a new API user. Copy the email-style username and password.', 'advanced-form-integration' ),
        esc_html__( 'Note the region from your account URL or the API user page (e.g. r1, r2, r3).', 'advanced-form-integration' ),
        esc_html__( 'Paste them below. AFI calls https://{region}-api.dotdigital.com/v2/ with HTTP Basic auth.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'dotdigital', __( 'Dotdigital', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_dotdigital_credentials', 'adfoin_get_dotdigital_credentials', 10, 0 );

function adfoin_get_dotdigital_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'dotdigital' );
}

add_action( 'wp_ajax_adfoin_save_dotdigital_credentials', 'adfoin_save_dotdigital_credentials', 10, 0 );

function adfoin_save_dotdigital_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'dotdigital', array( 'apiUser', 'apiPass', 'region' ) );
}

function adfoin_dotdigital_credentials_list() {
    foreach ( adfoin_read_credentials( 'dotdigital' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * Migrate legacy single-option credentials into the multi-account store.
 */
add_action( 'plugins_loaded', function () {
    if ( class_exists( 'ADFOIN_Account_Manager' ) ) {
        ADFOIN_Account_Manager::register_legacy_option_importer( 'dotdigital', array(
            'apiUser' => 'adfoin_dotdigital_api_user',
            'apiPass' => 'adfoin_dotdigital_api_pass',
            'region'  => 'adfoin_dotdigital_api_region',
        ) );
    }
}, 20 );

add_action( 'adfoin_action_fields', 'adfoin_dotdigital_action_fields' );

function adfoin_dotdigital_action_fields() {
    ?>
    <script type="text/template" id="dotdigital-action-template">
        <table class="form-table" v-if="action.task == 'add_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Dotdigital Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=dotdigital' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Opt-In Type', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[optInType]" v-model="fielddata.optInType">
                        <option value="Unknown"><?php esc_html_e( 'Unknown', 'advanced-form-integration' ); ?></option>
                        <option value="Single"><?php esc_html_e( 'Single', 'advanced-form-integration' ); ?></option>
                        <option value="Double"><?php esc_html_e( 'Double', 'advanced-form-integration' ); ?></option>
                        <option value="VerifiedDouble"><?php esc_html_e( 'Verified Double', 'advanced-form-integration' ); ?></option>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'add_contact', 'Dotdigital [PRO]', 'custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_dotdigital_job_queue', 'adfoin_dotdigital_job_queue', 10, 1 );

function adfoin_dotdigital_job_queue( $data ) {
    adfoin_dotdigital_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_dotdigital_send_data( $record, $posted_data ) {
    if ( 'add_contact' !== ( $record['task'] ?? '' ) ) {
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

    $email = isset( $field_data['email'] )
        ? sanitize_email( adfoin_get_parsed_values( $field_data['email'], $posted_data ) )
        : '';

    if ( ! $email ) {
        return;
    }

    $payload = array(
        'email'      => $email,
        'optInType'  => isset( $field_data['optInType'] ) ? (string) $field_data['optInType'] : 'Unknown',
        'dataFields' => array(),
    );

    $simple = array(
        'firstName' => 'FIRSTNAME',
        'lastName'  => 'LASTNAME',
    );

    foreach ( $simple as $key => $df_key ) {
        if ( empty( $field_data[ $key ] ) ) {
            continue;
        }
        $value = trim( (string) adfoin_get_parsed_values( $field_data[ $key ], $posted_data ) );
        if ( '' !== $value ) {
            $payload['dataFields'][] = array( 'key' => $df_key, 'value' => $value );
        }
    }

    $payload = apply_filters( 'adfoin_dotdigital_contact_payload', $payload, $field_data, $posted_data );

    $response = adfoin_dotdigital_request( 'contacts', 'POST', $payload, $record, $cred_id );

    if ( is_wp_error( $response ) ) {
        return;
    }

    // Legacy fallback: older accounts still return 409 on existing contact.
    if ( 409 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $payload['matchIdentifiers'] = array( 'email' => $email );
        adfoin_dotdigital_request( 'contacts', 'PUT', $payload, $record, $cred_id );
    }
}

if ( ! function_exists( 'adfoin_dotdigital_request' ) ) :
/**
 * Call the Dotdigital v2 API.
 *
 * @param string $endpoint Path under /v2/.
 * @param string $method   HTTP verb.
 * @param mixed  $data     Body (POST/PUT) or query (GET).
 * @param array  $record   Submission record for logging.
 * @param string $cred_id  Saved credential id.
 *
 * @return array|WP_Error
 */
function adfoin_dotdigital_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $api_user = '';
    $api_pass = '';
    $region   = '';

    if ( $cred_id && function_exists( 'adfoin_get_credentials_by_id' ) ) {
        $credentials = adfoin_get_credentials_by_id( 'dotdigital', $cred_id );
        if ( is_array( $credentials ) ) {
            $api_user = isset( $credentials['apiUser'] ) ? trim( (string) $credentials['apiUser'] ) : '';
            $api_pass = isset( $credentials['apiPass'] ) ? trim( (string) $credentials['apiPass'] ) : '';
            $region   = isset( $credentials['region'] )  ? trim( (string) $credentials['region'] )  : '';
        }
    }

    if ( ! $api_user ) {
        $api_user = (string) get_option( 'adfoin_dotdigital_api_user', '' );
    }
    if ( ! $api_pass ) {
        $api_pass = (string) get_option( 'adfoin_dotdigital_api_pass', '' );
    }
    if ( ! $region ) {
        $region = (string) get_option( 'adfoin_dotdigital_api_region', '' );
    }

    if ( ! $api_user || ! $api_pass || ! $region ) {
        return new WP_Error( 'dotdigital_missing_credentials', __( 'Dotdigital credentials are not configured.', 'advanced-form-integration' ) );
    }

    $url    = sprintf( 'https://%s-api.dotdigital.com/v2/', $region ) . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $api_user . ':' . $api_pass ),
            'Accept'        => 'application/json',
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
