<?php

/**
 * Stripe — Create Customer via POST /v1/customers.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: Authorization: Bearer <secret_key>  (sk_live_... or sk_test_...).
 *
 * Stripe's API is form-urlencoded (NOT JSON) and uses bracket notation
 * for nested objects (e.g. address[line1], metadata[source]). PHP's
 * http_build_query handles this natively when fed a nested array.
 *
 * @link https://stripe.com/docs/api/customers/create
 */

add_filter( 'adfoin_action_providers', 'adfoin_stripe_actions', 10, 1 );

function adfoin_stripe_actions( $actions ) {
    $actions['stripe'] = array(
        'title' => __( 'Stripe', 'advanced-form-integration' ),
        'tasks' => array(
            'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_stripe_settings_tab', 10, 1 );

function adfoin_stripe_settings_tab( $providers ) {
    $providers['stripe'] = __( 'Stripe', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_stripe_settings_view', 10, 1 );

function adfoin_stripe_settings_view( $current_tab ) {
    if ( 'stripe' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'secretKey',
            'label'         => __( 'Secret Key', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'placeholder'   => __( 'sk_live_... or sk_test_...', 'advanced-form-integration' ),
            'show_in_table' => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        sprintf( __( 'Sign in to Stripe and open %s.', 'advanced-form-integration' ), '<a target="_blank" rel="noopener noreferrer" href="https://dashboard.stripe.com/apikeys">Developers &rarr; API keys</a>' ),
        esc_html__( 'Recommended: click "Create restricted key" and grant only the "Customers — Write" permission. Restricted keys limit the blast radius if the key leaks.', 'advanced-form-integration' ),
        esc_html__( 'Alternatively, copy your Secret key (sk_live_... or sk_test_...) for full account access — simpler but broader.', 'advanced-form-integration' ),
        esc_html__( 'Paste it below. AFI calls https://api.stripe.com/v1/ with this key in the Authorization header.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'stripe', __( 'Stripe', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_stripe_credentials', 'adfoin_get_stripe_credentials', 10, 0 );

function adfoin_get_stripe_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'stripe' );
}

add_action( 'wp_ajax_adfoin_save_stripe_credentials', 'adfoin_save_stripe_credentials', 10, 0 );

function adfoin_save_stripe_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'stripe', array( 'secretKey' ) );
}

function adfoin_stripe_credentials_list() {
    foreach ( adfoin_read_credentials( 'stripe' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_stripe_action_fields' );

function adfoin_stripe_action_fields() {
    ?>
    <script type="text/template" id="stripe-action-template">
        <table class="form-table" v-if="action.task == 'create_customer'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Stripe Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=stripe' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_stripe_fields', 'adfoin_get_stripe_fields' );

function adfoin_get_stripe_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        // Core customer
        array( 'key' => 'email',              'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'name',               'value' => __( 'Full Name', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',              'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'description',        'value' => __( 'Description', 'advanced-form-integration' ) ),

        // Address (nested under address[...] when sent)
        array( 'key' => 'address_line1',      'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'address_line2',      'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'address_city',       'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'address_state',      'value' => __( 'State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'address_postal_code','value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'address_country',    'value' => __( 'Country (ISO-2, defaults to US)', 'advanced-form-integration' ) ),

        // Metadata
        array( 'key' => 'metadata_json',      'value' => __( 'Metadata (optional JSON, e.g. {"plan":"gold"})', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_stripe_job_queue', 'adfoin_stripe_job_queue', 10, 1 );

function adfoin_stripe_job_queue( $data ) {
    adfoin_stripe_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_stripe_send_data( $record, $posted_data ) {
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

    // Flatten parsed values up-front; Stripe's nested shape is assembled
    // below from this map (the form only feeds flat key=>value pairs).
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

    // Email is the only hard requirement for Create Customer.
    if ( empty( $values['email'] ) ) {
        return;
    }

    $payload = array(
        'email' => $values['email'],
    );

    foreach ( array( 'name', 'phone', 'description' ) as $top ) {
        if ( ! empty( $values[ $top ] ) ) {
            $payload[ $top ] = $values[ $top ];
        }
    }

    // Nested address — only attach when at least one address field was mapped.
    $address_map = array(
        'address_line1'       => 'line1',
        'address_line2'       => 'line2',
        'address_city'        => 'city',
        'address_state'       => 'state',
        'address_postal_code' => 'postal_code',
        'address_country'     => 'country',
    );
    $address = array();
    foreach ( $address_map as $flat => $stripe_key ) {
        if ( ! empty( $values[ $flat ] ) ) {
            $address[ $stripe_key ] = $values[ $flat ];
        }
    }
    if ( ! empty( $address ) ) {
        // Stripe is happiest with an ISO-2 country; default to US when the
        // form didn't supply one but other address parts were mapped.
        if ( empty( $address['country'] ) ) {
            $address['country'] = 'US';
        }
        $payload['address'] = $address;
    }

    // Metadata — auto-stamp source=wordpress_form, then merge any user-
    // supplied JSON blob on top so users can override the source if needed.
    $metadata = array(
        'source' => 'wordpress_form',
    );
    if ( ! empty( $values['metadata_json'] ) ) {
        $decoded = json_decode( $values['metadata_json'], true );
        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $mk => $mv ) {
                if ( is_scalar( $mv ) ) {
                    // Stripe metadata values must be strings; coerce
                    // scalars and skip nested arrays/objects.
                    $metadata[ (string) $mk ] = (string) $mv;
                }
            }
        }
    }
    $payload['metadata'] = $metadata;

    adfoin_stripe_request( 'customers', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_stripe_request' ) ) :
function adfoin_stripe_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'stripe', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['secretKey'] ) ) {
        return new WP_Error( 'stripe_missing_credentials', __( 'Stripe API key not configured.', 'advanced-form-integration' ) );
    }

    $url    = 'https://api.stripe.com/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization'  => 'Bearer ' . $credentials['secretKey'],
            'Accept'         => 'application/json',
            'Stripe-Version' => '2024-12-18.acacia',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        // Stripe expects application/x-www-form-urlencoded with bracket
        // notation for nested objects (address[line1]=..., metadata[k]=...).
        // PHP's http_build_query produces exactly that when handed a nested
        // array — RFC1738 keeps spaces as "+" which Stripe accepts.
        $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $args['body']                    = http_build_query( (array) $data, '', '&', PHP_QUERY_RFC1738 );
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
