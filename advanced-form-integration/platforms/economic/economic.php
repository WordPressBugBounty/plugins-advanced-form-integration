<?php

/**
 * e-conomic — Create Customer via POST /customers.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 *
 * Auth: e-conomic uses a two-token header scheme (not OAuth/Bearer):
 *   X-AppSecretToken:        the developer/app secret (from the registered app)
 *   X-AgreementGrantToken:   the per-customer grant token issued when the
 *                            customer connects their e-conomic agreement to
 *                            the app.
 *
 * Customer relations: e-conomic requires vatZone, paymentTerms,
 * customerGroup and currency on every Customer. We default these to
 * standard Danish-domestic values (1 / 1 / 1 / DKK) and expose each as an
 * optional override on the credential record so advanced users can tune
 * them to match their chart.
 *
 * @link https://restdocs.e-conomic.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_economic_actions', 10, 1 );

function adfoin_economic_actions( $actions ) {
    $actions['economic'] = array(
        'title' => __( 'e-conomic', 'advanced-form-integration' ),
        'tasks' => array(
            'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_economic_settings_tab', 10, 1 );

function adfoin_economic_settings_tab( $providers ) {
    $providers['economic'] = __( 'e-conomic', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_economic_settings_view', 10, 1 );

function adfoin_economic_settings_view( $current_tab ) {
    if ( 'economic' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'app_secret_token',
            'label'         => __( 'App Secret Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'X-AppSecretToken value from your e-conomic app', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'          => 'agreement_grant_token',
            'label'         => __( 'Agreement Grant Token', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'X-AgreementGrantToken issued when the customer connected the app', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
        array(
            'name'        => 'default_vat_zone',
            'label'       => __( 'Default VAT Zone Number', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => '1',
        ),
        array(
            'name'        => 'default_payment_terms',
            'label'       => __( 'Default Payment Terms Number', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => '1',
        ),
        array(
            'name'        => 'default_customer_group',
            'label'       => __( 'Default Customer Group Number', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => '1',
        ),
        array(
            'name'        => 'default_currency',
            'label'       => __( 'Default Currency', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => 'DKK',
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Register a public app at %s (or reuse an existing one).', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://www.e-conomic.com/developer">e-conomic Developer</a>' ),
        esc_html__( 'On the app\'s settings page, copy the App Secret Token (X-AppSecretToken).', 'advanced-form-integration' ),
        esc_html__( 'Have your e-conomic customer install/connect the app to their e-conomic agreement — this produces an Agreement Grant Token (X-AgreementGrantToken).', 'advanced-form-integration' ),
        esc_html__( 'Paste both tokens above. AFI calls https://restapi.e-conomic.com/ with both headers on every request. The optional defaults below control the required vatZone, paymentTerms, customerGroup and currency relations on new customers — leave blank for Danish-domestic defaults (1 / 1 / 1 / DKK).', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'economic', __( 'e-conomic', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_economic_credentials', 'adfoin_get_economic_credentials', 10, 0 );

function adfoin_get_economic_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'economic' );
}

add_action( 'wp_ajax_adfoin_save_economic_credentials', 'adfoin_save_economic_credentials', 10, 0 );

function adfoin_save_economic_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'economic', array(
        'app_secret_token',
        'agreement_grant_token',
        'default_vat_zone',
        'default_payment_terms',
        'default_customer_group',
        'default_currency',
    ) );
}

function adfoin_economic_credentials_list() {
    foreach ( adfoin_read_credentials( 'economic' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_economic_action_fields' );

function adfoin_economic_action_fields() {
    ?>
    <script type="text/template" id="economic-action-template">
        <table class="form-table" v-if="action.task == 'create_customer'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'e-conomic Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=economic' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_economic_fields', 'adfoin_get_economic_fields' );

function adfoin_get_economic_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'name',            'value' => __( 'Name (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'email',           'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',           'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'address',         'value' => __( 'Address', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',             'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'city',            'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'country',         'value' => __( 'Country (defaults to "Danmark")', 'advanced-form-integration' ) ),
        array( 'key' => 'customer_number', 'value' => __( 'Customer Number (optional — auto-generated if blank)', 'advanced-form-integration' ) ),
        array( 'key' => 'notes',           'value' => __( 'Notes', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_economic_job_queue', 'adfoin_economic_job_queue', 10, 1 );

function adfoin_economic_job_queue( $data ) {
    adfoin_economic_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_economic_send_data( $record, $posted_data ) {
    if ( 'create_customer' !== ( $record['task'] ?? '' ) ) {
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

    if ( empty( $values['name'] ) ) {
        return; // name is required by e-conomic
    }

    $credentials = adfoin_get_credentials_by_id( 'economic', $cred_id );
    if ( ! is_array( $credentials ) ) {
        $credentials = array();
    }

    // Resolve the four required relations from credential-level defaults,
    // falling back to standard Danish-domestic values when blank.
    $vat_zone        = ( isset( $credentials['default_vat_zone'] )       && '' !== $credentials['default_vat_zone'] )       ? (int) $credentials['default_vat_zone']       : 1;
    $payment_terms   = ( isset( $credentials['default_payment_terms'] )  && '' !== $credentials['default_payment_terms'] )  ? (int) $credentials['default_payment_terms']  : 1;
    $customer_group  = ( isset( $credentials['default_customer_group'] ) && '' !== $credentials['default_customer_group'] ) ? (int) $credentials['default_customer_group'] : 1;
    $currency        = ( isset( $credentials['default_currency'] )       && '' !== $credentials['default_currency'] )       ? (string) $credentials['default_currency']    : 'DKK';

    // customerNumber is required and must be a free-format positive integer.
    // Auto-generate from time() when the form didn't map one — this is unique
    // enough for one site/agreement; users who need their own sequence can
    // map a form field.
    $customer_number = isset( $values['customer_number'] ) && '' !== $values['customer_number']
        ? (int) preg_replace( '/[^0-9]/', '', (string) $values['customer_number'] )
        : 0;
    if ( $customer_number <= 0 ) {
        $customer_number = (int) time();
    }

    $payload = array(
        'customerNumber' => $customer_number,
        'name'           => (string) $values['name'],
        'currency'       => $currency,
        'vatZone'        => array( 'vatZoneNumber' => $vat_zone ),
        'paymentTerms'   => array( 'paymentTermsNumber' => $payment_terms ),
        'customerGroup'  => array( 'customerGroupNumber' => $customer_group ),
    );

    if ( ! empty( $values['email'] ) ) {
        $payload['email'] = $values['email'];
    }
    if ( ! empty( $values['phone'] ) ) {
        $payload['phone'] = $values['phone'];
    }
    if ( ! empty( $values['address'] ) ) {
        $payload['address'] = $values['address'];
    }
    if ( ! empty( $values['zip'] ) ) {
        $payload['zip'] = $values['zip'];
    }
    if ( ! empty( $values['city'] ) ) {
        $payload['city'] = $values['city'];
    }
    // Country defaults to "Danmark" — e-conomic stores country as a free-text
    // string on the customer record, not an ISO code.
    $payload['country'] = ! empty( $values['country'] ) ? (string) $values['country'] : 'Danmark';

    if ( ! empty( $values['notes'] ) ) {
        // e-conomic exposes notes via a nested object on Customer.
        $payload['notes'] = array( 'heading' => '', 'textLine1' => (string) $values['notes'] );
    }

    adfoin_economic_request( 'customers', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_economic_request' ) ) :
function adfoin_economic_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'economic', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['app_secret_token'] ) || empty( $credentials['agreement_grant_token'] ) ) {
        return new WP_Error( 'economic_missing_credentials', __( 'e-conomic App Secret Token and Agreement Grant Token are required.', 'advanced-form-integration' ) );
    }

    $url    = 'https://restapi.e-conomic.com/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            // e-conomic's two-token scheme — both headers are required on
            // every call. No Authorization header is used.
            'X-AppSecretToken'      => $credentials['app_secret_token'],
            'X-AgreementGrantToken' => $credentials['agreement_grant_token'],
            'Accept'                => 'application/json',
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
