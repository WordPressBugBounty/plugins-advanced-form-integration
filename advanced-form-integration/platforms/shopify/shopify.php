<?php

/**
 * Shopify — Create Customer via POST /admin/api/2024-04/customers.json.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager.
 * Auth: X-Shopify-Access-Token: <access_token> (Custom App admin API token).
 *
 * We target Shopify Custom Apps (the modern replacement for the deprecated
 * Private App flow). Merchants create a Custom App from their store admin,
 * grant Admin API scopes (write_customers), install it, and copy the
 * resulting Admin API access token (starts with "shpat_"). The shop
 * subdomain is normalized so users can paste either "mystore" or
 * "mystore.myshopify.com" (with or without scheme / trailing slash).
 *
 * Choosing Custom Apps over the full OAuth2 flow keeps this integration
 * one-step for the merchant — no Shopify Partner approval, no redirect
 * dance, no token refresh. The token is long-lived and tied to one shop,
 * which fits AFI's per-credential model exactly.
 *
 * @link https://shopify.dev/docs/api/admin-rest/2024-04/resources/customer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_shopify_actions', 10, 1 );

function adfoin_shopify_actions( $actions ) {
    $actions['shopify'] = array(
        'title' => __( 'Shopify', 'advanced-form-integration' ),
        'tasks' => array(
            'create_customer' => __( 'Create Customer', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_shopify_settings_tab', 10, 1 );

function adfoin_shopify_settings_tab( $providers ) {
    $providers['shopify'] = __( 'Shopify', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_shopify_settings_view', 10, 1 );

function adfoin_shopify_settings_view( $current_tab ) {
    if ( 'shopify' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'shop',
            'label'         => __( 'Shop Subdomain', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'placeholder'   => 'mystore or mystore.myshopify.com',
            'show_in_table' => true,
        ),
        array(
            'name'        => 'accessToken',
            'label'       => __( 'Admin API Access Token', 'advanced-form-integration' ),
            'type'        => 'text',
            'required'    => true,
            'mask'        => true,
            'placeholder' => 'shpat_...',
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In your Shopify Admin, go to Settings → Apps and sales channels → Develop apps.', 'advanced-form-integration' ),
        esc_html__( 'Click "Create an app", give it a name (e.g. WordPress Form Integration), then open the "Configuration" tab.', 'advanced-form-integration' ),
        esc_html__( 'Under "Admin API integration", grant the write_customers scope, then save.', 'advanced-form-integration' ),
        esc_html__( 'Open the "API credentials" tab, click "Install app", then reveal and copy the Admin API access token (shown only once; starts with shpat_).', 'advanced-form-integration' ),
        sprintf( __( 'Paste the token below along with your shop subdomain (e.g. %s). AFI calls %s with the access token in the X-Shopify-Access-Token header.', 'advanced-form-integration' ), '<code>mystore</code>', '<code>https://{shop}.myshopify.com/admin/api/2024-04/</code>' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'shopify', __( 'Shopify', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_shopify_credentials', 'adfoin_get_shopify_credentials', 10, 0 );

function adfoin_get_shopify_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'shopify' );
}

add_action( 'wp_ajax_adfoin_save_shopify_credentials', 'adfoin_save_shopify_credentials', 10, 0 );

function adfoin_save_shopify_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'shopify', array( 'shop', 'accessToken' ) );
}

function adfoin_shopify_credentials_list() {
    foreach ( adfoin_read_credentials( 'shopify' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_shopify_action_fields' );

function adfoin_shopify_action_fields() {
    ?>
    <script type="text/template" id="shopify-action-template">
        <table class="form-table" v-if="action.task == 'create_customer'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Shopify Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=shopify' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
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

add_action( 'wp_ajax_adfoin_get_shopify_fields', 'adfoin_get_shopify_fields' );

function adfoin_get_shopify_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        // Identity — email is the only hard requirement; Shopify also accepts
        // phone-only customers but we keep the UX simple and gate on email.
        array( 'key' => 'email',              'value' => __( 'Email (required)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'first_name',         'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name',          'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',              'value' => __( 'Phone (E.164, e.g. +14155551234)', 'advanced-form-integration' ) ),

        // Address — wrapped into a single addresses[] entry on the wire.
        array( 'key' => 'address1',           'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'address2',           'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'city',               'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'province',           'value' => __( 'Province / State', 'advanced-form-integration' ) ),
        array( 'key' => 'zip',                'value' => __( 'ZIP / Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country',            'value' => __( 'Country (ISO-2, default US)', 'advanced-form-integration' ) ),

        // Misc
        array( 'key' => 'tags',               'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'note',               'value' => __( 'Note', 'advanced-form-integration' ) ),
        array( 'key' => 'accepts_marketing',  'value' => __( 'Accepts Marketing (true / false)', 'advanced-form-integration' ) ),
        array( 'key' => 'send_email_welcome', 'value' => __( 'Send Welcome Email (true / false, default false)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_shopify_job_queue', 'adfoin_shopify_job_queue', 10, 1 );

function adfoin_shopify_job_queue( $data ) {
    adfoin_shopify_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_shopify_send_data( $record, $posted_data ) {
    if ( 'create_customer' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    // Modern CL guard — adfoin_check_conditional_logic returns true when the
    // record should be SKIPPED (i.e. conditions did not match), so we early
    // return on truthy. Matches the lexoffice / nocrmio convention.
    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = $record_data['field_data'] ?? array();
    $cred_id    = $field_data['credId'] ?? '';

    if ( ! $cred_id ) {
        return;
    }

    // Flatten every mapped form value up front. Shopify's wire payload is
    // nested (customer + addresses[] + email_marketing_consent{}) but the
    // form mapping is intentionally flat — we assemble the shape below.
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

    // Shopify accepts either email or phone to identify a customer, but we
    // require email since that's the field marked required in the UI.
    if ( empty( $values['email'] ) ) {
        return;
    }

    // Truthy-string coercion used for both accepts_marketing and
    // send_email_welcome — form mappings deliver "true"/"1"/"yes" strings.
    $is_truthy = static function ( $val ) {
        return in_array( strtolower( trim( (string) $val ) ), array( 'true', '1', 'yes', 'on' ), true );
    };

    $customer = array(
        'email'          => (string) $values['email'],
        // Skip Shopify's email-verification challenge — submissions come from
        // a form the merchant already trusts.
        'verified_email' => true,
    );

    if ( ! empty( $values['first_name'] ) ) { $customer['first_name'] = (string) $values['first_name']; }
    if ( ! empty( $values['last_name'] ) )  { $customer['last_name']  = (string) $values['last_name']; }
    if ( ! empty( $values['phone'] ) )      { $customer['phone']      = (string) $values['phone']; }
    if ( ! empty( $values['tags'] ) )       { $customer['tags']       = (string) $values['tags']; }
    if ( ! empty( $values['note'] ) )       { $customer['note']       = (string) $values['note']; }

    // Welcome email defaults to false — merchants opt in via the form field.
    $customer['send_email_welcome'] = isset( $values['send_email_welcome'] )
        ? $is_truthy( $values['send_email_welcome'] )
        : false;

    // Modern 2024-04 way to express marketing consent: a structured
    // email_marketing_consent object (the legacy flat accepts_marketing
    // boolean still works but is deprecated and not always honoured).
    if ( isset( $values['accepts_marketing'] ) && $is_truthy( $values['accepts_marketing'] ) ) {
        $customer['email_marketing_consent'] = array(
            'state'              => 'subscribed',
            'opt_in_level'       => 'single_opt_in',
            'consent_updated_at' => gmdate( 'c' ),
        );
    }

    // Build one addresses[] entry only if at least one address piece was
    // mapped — Shopify rejects empty addresses[] entries. Default country to
    // US so a partial address still validates.
    $address_keys = array( 'address1', 'address2', 'city', 'province', 'zip', 'country' );
    $address      = array();
    foreach ( $address_keys as $key ) {
        if ( ! empty( $values[ $key ] ) ) {
            $address[ $key ] = (string) $values[ $key ];
        }
    }
    if ( ! empty( $address ) ) {
        if ( empty( $address['country'] ) ) {
            $address['country'] = 'US';
        }
        // Mirror the customer's name into the address so the Shopify admin
        // shows a fully populated shipping/billing entry.
        if ( ! empty( $values['first_name'] ) ) { $address['first_name'] = (string) $values['first_name']; }
        if ( ! empty( $values['last_name'] ) )  { $address['last_name']  = (string) $values['last_name']; }
        if ( ! empty( $values['phone'] ) )      { $address['phone']      = (string) $values['phone']; }
        $customer['addresses'] = array( $address );
    }

    $payload = array( 'customer' => $customer );

    adfoin_shopify_request( 'customers.json', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_shopify_normalize_shop' ) ) :
/**
 * Accept either "mystore", "mystore.myshopify.com", or a full URL (with or
 * without protocol / trailing slash) and return the bare subdomain label
 * (e.g. "mystore"). The request builder then appends ".myshopify.com".
 */
function adfoin_shopify_normalize_shop( $input ) {
    $shop = trim( (string) $input );
    if ( '' === $shop ) {
        return '';
    }

    // Strip protocol and any path component.
    $shop = preg_replace( '#^https?://#i', '', $shop );
    $shop = trim( $shop, "/ \t\n\r\0\x0B" );
    $shop = explode( '/', $shop )[0];
    $shop = strtolower( $shop );

    // Strip trailing .myshopify.com (and anything else after the first dot —
    // only the subdomain label is meaningful for the Admin API host).
    $shop = explode( '.', $shop )[0];

    return $shop;
}
endif;

if ( ! function_exists( 'adfoin_shopify_request' ) ) :
function adfoin_shopify_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'shopify', $cred_id );

    if ( ! is_array( $credentials ) || empty( $credentials['shop'] ) || empty( $credentials['accessToken'] ) ) {
        return new WP_Error( 'shopify_missing_credentials', __( 'Shopify shop subdomain or access token not configured.', 'advanced-form-integration' ) );
    }

    $shop = adfoin_shopify_normalize_shop( $credentials['shop'] );
    if ( '' === $shop ) {
        return new WP_Error( 'shopify_invalid_shop', __( 'Shopify shop subdomain is invalid.', 'advanced-form-integration' ) );
    }

    $url    = 'https://' . $shop . '.myshopify.com/admin/api/2024-04/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-Shopify-Access-Token' => $credentials['accessToken'],
            'Accept'                 => 'application/json',
        ),
    );

    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body']                    = wp_json_encode( is_array( $data ) ? $data : array() );
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
