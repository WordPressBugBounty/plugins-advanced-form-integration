<?php

/**
 * SuperOffice CRM — Create Contact (company) via POST /api/v1/Contact.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer {access_token}, plus optional SO-AppToken
 * and SO-ContextIdentifier headers for partner / multi-tenant setups.
 *
 * @link https://docs.superoffice.com/en/api/index.html
 */

add_filter( 'adfoin_action_providers', 'adfoin_superoffice_actions', 10, 1 );

function adfoin_superoffice_actions( $actions ) {
    $actions['superoffice'] = array(
        'title' => __( 'SuperOffice CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact (Company)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_superoffice_settings_tab', 10, 1 );

function adfoin_superoffice_settings_tab( $providers ) {
    $providers['superoffice'] = __( 'SuperOffice CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_superoffice_settings_view', 10, 1 );

function adfoin_superoffice_settings_view( $current_tab ) {
    if ( 'superoffice' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'restBaseUrl',
            'label'         => __( 'REST Base URL', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => 'https://sod.superoffice.com/Cust12345/api/v1/',
            'show_in_table' => true,
        ),
        array(
            'name'          => 'accessToken',
            'label'         => __( 'Access Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'appToken',
            'label'         => __( 'App Token (optional)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'mask'          => true,
            'show_in_table' => false,
        ),
        array(
            'name'          => 'contextIdentifier',
            'label'         => __( 'Context Identifier (optional)', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => false,
            'show_in_table' => false,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In SuperOffice Admin, generate a system user or OAuth access token with the permissions you need (Contact read/write, Person read/write for AFI Pro).', 'advanced-form-integration' ),
        sprintf(
            /* translators: 1: example REST URL, 2: production prefix. */
            esc_html__( 'Paste the full tenant REST URL — for example %1$s for sandbox or %2$s for production.', 'advanced-form-integration' ),
            '<code>https://sod.superoffice.com/Cust12345/api/v1/</code>',
            '<code>https://online.superoffice.com/Cust12345/api/v1/</code>'
        ),
        esc_html__( 'App Token and Context Identifier are only required for partner-app / multi-tenant flows — leave blank for a single-tenant integration.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'superoffice', __( 'SuperOffice CRM', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_superoffice_credentials', 'adfoin_get_superoffice_credentials', 10, 0 );

function adfoin_get_superoffice_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'superoffice' );
}

add_action( 'wp_ajax_adfoin_save_superoffice_credentials', 'adfoin_save_superoffice_credentials', 10, 0 );

function adfoin_save_superoffice_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'superoffice', array( 'restBaseUrl', 'accessToken', 'appToken', 'contextIdentifier' ) );
}

if ( ! function_exists( 'adfoin_superoffice_credentials_list' ) ) :
function adfoin_superoffice_credentials_list() {
    foreach ( adfoin_read_credentials( 'superoffice' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

add_action( 'adfoin_action_fields', 'adfoin_superoffice_action_fields' );

function adfoin_superoffice_action_fields() {
    ?>
    <script type="text/template" id="superoffice-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SuperOffice Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <div class="afi-spinner" v-bind:class="{'is-active': credLoading}"></div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=superoffice' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
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
            <?php adfoin_pro_feature_notice( 'create_contact', 'SuperOffice CRM [PRO]', 'user-defined fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_superoffice_fields', 'adfoin_get_superoffice_fields', 10, 0 );

function adfoin_get_superoffice_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'Name',                     'value' => __( 'Company Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'Department',               'value' => __( 'Department', 'advanced-form-integration' ) ),
        array( 'key' => 'CategoryId',               'value' => __( 'Category ID', 'advanced-form-integration' ) ),
        array( 'key' => 'BusinessId',               'value' => __( 'Business ID', 'advanced-form-integration' ) ),
        array( 'key' => 'Number1',                  'value' => __( 'Phone (Number1)', 'advanced-form-integration' ) ),
        array( 'key' => 'Number2',                  'value' => __( 'Phone (Number2)', 'advanced-form-integration' ) ),
        array( 'key' => 'UrlAddress',               'value' => __( 'Website URL', 'advanced-form-integration' ) ),
        array( 'key' => 'Emails[0].Value',          'value' => __( 'Primary Email', 'advanced-form-integration' ) ),
        array( 'key' => 'Emails[0].Description',    'value' => __( 'Email Description', 'advanced-form-integration' ) ),
        array( 'key' => 'Phones[0].Value',          'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'Phones[0].Description',    'value' => __( 'Phone Description', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Address1',   'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Address2',   'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.City',       'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Zipcode',    'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Country',    'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'Description',              'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_superoffice_job_queue', 'adfoin_superoffice_job_queue', 10, 1 );

function adfoin_superoffice_job_queue( $data ) {
    adfoin_superoffice_send_contact( $data['record'], $data['posted_data'] );
}

function adfoin_superoffice_send_contact( $record, $posted_data ) {
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

    $credentials = adfoin_superoffice_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $mapped_fields = array();

    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed || null === $parsed ) {
            continue;
        }

        $mapped_fields[ $key ] = $parsed;
    }

    if ( empty( $mapped_fields['Name'] ) ) {
        return;
    }

    $payload = adfoin_superoffice_build_payload( $mapped_fields );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_superoffice_request( 'Contact', 'POST', $payload, $record, $credentials );
}

if ( ! function_exists( 'adfoin_superoffice_get_credentials' ) ) :
function adfoin_superoffice_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'superoffice', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_request' ) ) :
/**
 * Call the SuperOffice REST API.
 *
 * @param string $endpoint    Path under the tenant /api/v1/.
 * @param string $method      HTTP verb.
 * @param mixed  $data        Body (POST/PUT/PATCH).
 * @param array  $record      Submission record for logging.
 * @param array  $credentials Saved credentials.
 *
 * @return array|WP_Error
 */
function adfoin_superoffice_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = array() ) {
    if ( empty( $credentials ) ) {
        return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials missing.', 'advanced-form-integration' ) );
    }

    $base_url = isset( $credentials['restBaseUrl'] ) ? trim( $credentials['restBaseUrl'] ) : '';

    if ( '' === $base_url ) {
        return new WP_Error( 'missing_base_url', __( 'SuperOffice REST base URL is not set.', 'advanced-form-integration' ) );
    }

    $base_url = trailingslashit( $base_url );
    $url      = $base_url . ltrim( $endpoint, '/' );

    $headers = array(
        'Accept' => 'application/json',
    );

    if ( ! empty( $credentials['accessToken'] ) ) {
        $headers['Authorization'] = 'Bearer ' . $credentials['accessToken'];
    }

    if ( ! empty( $credentials['appToken'] ) ) {
        $headers['SO-AppToken'] = $credentials['appToken'];
    }

    if ( ! empty( $credentials['contextIdentifier'] ) ) {
        $headers['SO-ContextIdentifier'] = $credentials['contextIdentifier'];
    }

    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => $headers,
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
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

if ( ! function_exists( 'adfoin_superoffice_build_payload' ) ) :
function adfoin_superoffice_build_payload( $fields ) {
    $payload = array();

    foreach ( $fields as $path => $value ) {
        adfoin_superoffice_assign_path( $payload, $path, adfoin_superoffice_normalize_value( $path, $value ) );
    }

    return $payload;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_assign_path' ) ) :
function adfoin_superoffice_assign_path( array &$target, $path, $value ) {
    if ( '' === $path ) {
        return;
    }

    $segments = explode( '.', $path );
    $last     = count( $segments ) - 1;
    $ref      =& $target;

    foreach ( $segments as $index => $segment ) {
        if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $segment, $matches ) ) {
            $key   = $matches[1];
            $array_index = (int) $matches[2];

            if ( ! isset( $ref[ $key ] ) || ! is_array( $ref[ $key ] ) ) {
                $ref[ $key ] = array();
            }

            if ( ! isset( $ref[ $key ][ $array_index ] ) || ! is_array( $ref[ $key ][ $array_index ] ) ) {
                $ref[ $key ][ $array_index ] = array();
            }

            if ( $index === $last ) {
                $ref[ $key ][ $array_index ] = $value;
            } else {
                $ref =& $ref[ $key ][ $array_index ];
            }

            continue;
        }

        if ( $index === $last ) {
            $ref[ $segment ] = $value;
        } else {
            if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
                $ref[ $segment ] = array();
            }

            $ref =& $ref[ $segment ];
        }
    }
}
endif;

if ( ! function_exists( 'adfoin_superoffice_normalize_value' ) ) :
function adfoin_superoffice_normalize_value( $path, $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    $trimmed = is_string( $value ) ? trim( $value ) : $value;

    $last_segment = $path;

    if ( false !== strpos( $path, '.' ) ) {
        $parts        = explode( '.', $path );
        $last_segment = end( $parts );
    }

    if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $last_segment, $matches ) ) {
        $last_segment = $matches[1];
    }

    $int_fields   = array( 'CategoryId', 'BusinessId', 'AssociateId', 'OwnerContactId', 'NumberOfEmployees', 'ContactId', 'PersonId', 'CountryId', 'PositionId' );
    $float_fields = array( 'Amount', 'WeightedAmount' );
    $bool_fields  = array( 'HasConsent', 'ConsentGiven', 'ConsentObtained', 'Active', 'Done' );

    if ( in_array( $last_segment, $int_fields, true ) && '' !== $trimmed ) {
        return (int) $trimmed;
    }

    if ( in_array( $last_segment, $float_fields, true ) && '' !== $trimmed ) {
        return (float) $trimmed;
    }

    if ( in_array( $last_segment, $bool_fields, true ) ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        $lower = strtolower( (string) $trimmed );
        return in_array( $lower, array( '1', 'true', 'yes', 'on' ), true );
    }

    return $value;
}
endif;
