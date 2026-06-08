<?php

/**
 * Lexoffice / Lexware Office — Create Contact via POST /v1/contacts.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <api_token>
 *
 * Lexoffice contacts are split into "person" or "company" shapes with a
 * required role (customer/vendor) — the dispatcher assembles the nested
 * payload from the flat form-field map.
 *
 * @link https://developers.lexware.io/docs/
 */

add_filter( 'adfoin_action_providers', 'adfoin_lexoffice_actions', 10, 1 );

function adfoin_lexoffice_actions( $actions ) {
    $actions['lexoffice'] = array(
        'title' => __( 'Lexoffice', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_lexoffice_settings_tab', 10, 1 );

function adfoin_lexoffice_settings_tab( $providers ) {
    $providers['lexoffice'] = __( 'Lexoffice', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_lexoffice_settings_view', 10, 1 );

function adfoin_lexoffice_settings_view( $current_tab ) {
    if ( 'lexoffice' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'apiToken',
            'label'         => __( 'API Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'Paste your Lexoffice API token', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Lexoffice and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://app.lexoffice.de/addons/public-api">Public API settings</a>' ),
        esc_html__( 'Click "Create token" and give the token a descriptive name (e.g. WordPress).', 'advanced-form-integration' ),
        esc_html__( 'Copy the token immediately — Lexoffice only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.lexoffice.io/v1/ with this token in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'lexoffice', __( 'Lexoffice', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_lexoffice_credentials', 'adfoin_get_lexoffice_credentials', 10, 0 );

function adfoin_get_lexoffice_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'lexoffice' );
}

add_action( 'wp_ajax_adfoin_save_lexoffice_credentials', 'adfoin_save_lexoffice_credentials', 10, 0 );

function adfoin_save_lexoffice_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'lexoffice', array( 'apiToken' ) );
}

function adfoin_lexoffice_credentials_list() {
    foreach ( adfoin_read_credentials( 'lexoffice' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_lexoffice_action_fields' );

function adfoin_lexoffice_action_fields() {
    ?>
    <script type="text/template" id="lexoffice-action-template">
        <table class="form-table" v-if="action.task == 'create_contact'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Lexoffice Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=lexoffice' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Contact Type', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[contactType]" v-model="fielddata.contactType">
                        <option value="person"><?php esc_html_e( 'Person', 'advanced-form-integration' ); ?></option>
                        <option value="company"><?php esc_html_e( 'Company', 'advanced-form-integration' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Lexoffice splits contacts into Person and Company shapes. Field mappings below apply to both.', 'advanced-form-integration' ); ?></p>
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

add_action( 'wp_ajax_adfoin_get_lexoffice_fields', 'adfoin_get_lexoffice_fields' );

function adfoin_get_lexoffice_fields() {
    adfoin_verify_nonce();

    $fields = array(
        // Company (only used when contactType = company)
        array( 'key' => 'companyName',         'value' => __( 'Company Name (required for Company)', 'advanced-form-integration' ) ),
        array( 'key' => 'vatRegistrationId',   'value' => __( 'VAT Registration ID (e.g. DE123456789)', 'advanced-form-integration' ) ),

        // Person
        array( 'key' => 'salutation',          'value' => __( 'Salutation (e.g. Herr, Frau)', 'advanced-form-integration' ) ),
        array( 'key' => 'firstName',           'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',            'value' => __( 'Last Name (required for Person)', 'advanced-form-integration' ) ),

        // Contact channels
        array( 'key' => 'email',               'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',               'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile',              'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),

        // Billing address
        array( 'key' => 'street',              'value' => __( 'Street + Number', 'advanced-form-integration' ) ),
        array( 'key' => 'supplement',          'value' => __( 'Address Supplement (apt, floor, etc.)', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',                 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'city',                'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'countryCode',         'value' => __( 'Country Code (ISO-2, e.g. DE)', 'advanced-form-integration' ) ),

        // Misc
        array( 'key' => 'note',                'value' => __( 'Note', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_lexoffice_job_queue', 'adfoin_lexoffice_job_queue', 10, 1 );

function adfoin_lexoffice_job_queue( $data ) {
    adfoin_lexoffice_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_lexoffice_send_data( $record, $posted_data ) {
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

    // Resolve all flat values up-front. Lexoffice's nested payload shape is
    // assembled below — the form just feeds us flat key=>value pairs.
    $values = array();
    $reserved = array( 'credId' => 1, 'contactType' => 1 );
    foreach ( $field_data as $key => $value ) {
        if ( isset( $reserved[ $key ] ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $values[ $key ] = $parsed;
        }
    }

    $type = ( ( $field_data['contactType'] ?? 'person' ) === 'company' ) ? 'company' : 'person';

    $payload = array(
        'version' => 0,
        'roles'   => array(
            // Every Lexoffice contact must have at least one role. "customer"
            // covers the typical form-submission flow — vendor needs a
            // separate task if/when added later.
            'customer' => new stdClass(),
        ),
    );

    if ( 'company' === $type ) {
        if ( empty( $values['companyName'] ) ) {
            return; // company name is required on company contacts
        }
        $company = array(
            'name' => $values['companyName'],
        );
        if ( ! empty( $values['vatRegistrationId'] ) ) {
            $company['vatRegistrationId'] = $values['vatRegistrationId'];
        }
        // If the form also captured a primary person, attach them as the
        // company's primary contact person — Lexoffice supports this.
        if ( ! empty( $values['lastName'] ) ) {
            $cp = array(
                'lastName' => $values['lastName'],
                'primary'  => true,
            );
            if ( ! empty( $values['salutation'] ) ) { $cp['salutation']   = $values['salutation']; }
            if ( ! empty( $values['firstName'] ) )  { $cp['firstName']    = $values['firstName']; }
            if ( ! empty( $values['email'] ) )      { $cp['emailAddress'] = $values['email']; }
            if ( ! empty( $values['phone'] ) )      { $cp['phoneNumber']  = $values['phone']; }
            $company['contactPersons'] = array( $cp );
        }
        $payload['company'] = $company;
    } else {
        if ( empty( $values['lastName'] ) ) {
            return; // last name is required on person contacts
        }
        $person = array(
            'lastName' => $values['lastName'],
        );
        if ( ! empty( $values['salutation'] ) ) { $person['salutation'] = $values['salutation']; }
        if ( ! empty( $values['firstName'] ) )  { $person['firstName']  = $values['firstName']; }
        $payload['person'] = $person;
    }

    // Top-level email + phone arrays (Lexoffice supports business/office/
    // mobile/private/fax buckets; we map to business by default).
    if ( ! empty( $values['email'] ) ) {
        $payload['emailAddresses'] = array(
            'business' => array( $values['email'] ),
        );
    }

    $phones = array();
    if ( ! empty( $values['phone'] ) )  { $phones['business'] = array( $values['phone'] ); }
    if ( ! empty( $values['mobile'] ) ) { $phones['mobile']   = array( $values['mobile'] ); }
    if ( ! empty( $phones ) ) {
        $payload['phoneNumbers'] = $phones;
    }

    // Billing address — Lexoffice expects countryCode as ISO 3166-1 alpha-2.
    $address_keys = array( 'street', 'supplement', 'zip', 'city', 'countryCode' );
    $address = array();
    foreach ( $address_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $address[ $key ] = $values[ $key ];
        }
    }
    if ( ! empty( $address ) ) {
        // countryCode is required by Lexoffice when an address is supplied.
        if ( empty( $address['countryCode'] ) ) {
            $address['countryCode'] = 'DE';
        }
        $payload['addresses'] = array(
            'billing' => array( $address ),
        );
    }

    if ( ! empty( $values['note'] ) ) {
        $payload['note'] = $values['note'];
    }

    adfoin_lexoffice_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_lexoffice_request' ) ) :
function adfoin_lexoffice_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'lexoffice', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['apiToken'] ) ) {
        return new WP_Error( 'lexoffice_missing_credentials', __( 'Lexoffice API token not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.lexoffice.io/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $credentials['apiToken'],
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
