<?php

add_filter( 'adfoin_action_providers', 'adfoin_bloomerang_actions', 10, 1 );
function adfoin_bloomerang_actions( $actions ) {
    $actions['bloomerang'] = array(
        'title' => 'Bloomerang',
        'tasks' => array(
            'create_constituent' => 'Create Constituent',
            'create_transaction' => 'Create Donation / Transaction',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_bloomerang_settings_tab', 10, 1 );
function adfoin_bloomerang_settings_tab( $providers ) { $providers['bloomerang'] = 'Bloomerang'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_bloomerang_settings_view', 10, 1 );
function adfoin_bloomerang_settings_view( $current_tab ) {
    if ( $current_tab !== 'bloomerang' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'bloomerang',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In Bloomerang, go to Settings > API Keys. Generate a key and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Bloomerang', 'advanced-form-integration' ), 'bloomerang', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_bloomerang_action_fields' );
function adfoin_bloomerang_action_fields() {
    ?>
    <script type="text/template" id="bloomerang-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_constituent' || action.task == 'create_transaction'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_constituent' || action.task == 'create_transaction'">
                <td scope="row-title"><label><?php esc_attr_e( 'Bloomerang Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_bloomerang_credentials_list(); ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=bloomerang' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;"><span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?></a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_constituent', 'Bloomerang [PRO]', 'custom fields' ); ?>
            <?php adfoin_pro_feature_notice( 'create_transaction', 'Bloomerang [PRO]', 'custom fields, soft credits and recurring donations' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_bloomerang_credentials', 'adfoin_get_bloomerang_credentials' );
function adfoin_get_bloomerang_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'bloomerang' ) );
}

add_action( 'wp_ajax_adfoin_save_bloomerang_credentials', 'adfoin_save_bloomerang_credentials' );
function adfoin_save_bloomerang_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'bloomerang' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'bloomerang', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_bloomerang_fields', 'adfoin_get_bloomerang_fields' );
function adfoin_get_bloomerang_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_constituent';
    wp_send_json_success( $task === 'create_transaction' ? adfoin_bloomerang_transaction_fields() : adfoin_bloomerang_constituent_fields() );
}

function adfoin_bloomerang_constituent_fields() {
    return array(
        array( 'key' => 'firstName',    'value' => 'First Name',        'description' => '' ),
        array( 'key' => 'lastName',     'value' => 'Last Name',         'description' => '' ),
        array( 'key' => 'email',        'value' => 'Email',             'description' => '' ),
        array( 'key' => 'phone',        'value' => 'Phone',             'description' => '' ),
        array( 'key' => 'type',         'value' => 'Type',              'description' => 'Individual or Organization (default: Individual)' ),
        array( 'key' => 'organization', 'value' => 'Organization Name', 'description' => 'Required when Type is Organization' ),
        array( 'key' => 'jobTitle',     'value' => 'Job Title',         'description' => '' ),
        array( 'key' => 'street',       'value' => 'Street',            'description' => '' ),
        array( 'key' => 'city',         'value' => 'City',              'description' => '' ),
        array( 'key' => 'state',        'value' => 'State',             'description' => '' ),
        array( 'key' => 'zip',          'value' => 'Zip',               'description' => '' ),
        array( 'key' => 'country',      'value' => 'Country',           'description' => '' ),
    );
}

/**
 * Field list for Create Donation / Transaction, built against the
 * POST /transaction schema in Bloomerang's public OpenAPI spec
 * (bloomerang-api-documentation.s3.us-west-2.amazonaws.com/public_crm_generated.json).
 * The donor fields let one form submission find-or-create the constituent.
 */
function adfoin_bloomerang_transaction_fields() {
    return array(
        array( 'key' => 'donorEmail',        'value' => 'Donor Email',         'required' => true, 'description' => 'Used to find the donor. If no match is found and a name is mapped, the constituent is created.' ),
        array( 'key' => 'donorFirstName',    'value' => 'Donor First Name',    'description' => 'Needed to create a new donor (or Organization Name below)' ),
        array( 'key' => 'donorLastName',     'value' => 'Donor Last Name',     'description' => '' ),
        array( 'key' => 'donorOrganization', 'value' => 'Donor Organization Name', 'description' => 'Fill for organization donors instead of first/last name' ),
        array( 'key' => 'donorPhone',        'value' => 'Donor Phone',         'description' => '' ),
        array( 'key' => 'donorStreet',       'value' => 'Donor Street',        'description' => '' ),
        array( 'key' => 'donorCity',         'value' => 'Donor City',          'description' => '' ),
        array( 'key' => 'donorState',        'value' => 'Donor State',         'description' => '' ),
        array( 'key' => 'donorZip',          'value' => 'Donor Zip',           'description' => '' ),
        array( 'key' => 'donorCountry',      'value' => 'Donor Country',       'description' => '' ),
        array( 'key' => 'amount',            'value' => 'Amount',              'required' => true, 'description' => '' ),
        array( 'key' => 'date',              'value' => 'Transaction Date',    'description' => 'Defaults to today' ),
        array( 'key' => 'method',            'value' => 'Payment Method',      'description' => 'Cash, Check, CreditCard, Eft, InKind, ApplePay, GooglePay, PayPal, Venmo or None (default: Cash)' ),
        array( 'key' => 'fund',              'value' => 'Fund',                'description' => 'Fund name or ID (default: your Bloomerang default fund)' ),
        array( 'key' => 'campaign',          'value' => 'Campaign',            'description' => 'Campaign name or ID' ),
        array( 'key' => 'appeal',            'value' => 'Appeal',              'description' => 'Appeal name or ID' ),
        array( 'key' => 'note',              'value' => 'Note',                'description' => '' ),
        array( 'key' => 'nonDeductible',     'value' => 'Non-Deductible Amount', 'description' => '' ),
        array( 'key' => 'feeAmount',         'value' => 'Fee Amount',          'description' => '' ),
        array( 'key' => 'integrationUrl',    'value' => 'Integration URL',     'description' => 'Link shown on the transaction in Bloomerang, e.g. the form entry URL' ),
        array( 'key' => 'checkNumber',       'value' => 'Check Number',        'description' => 'Required when Payment Method is Check' ),
        array( 'key' => 'checkDate',         'value' => 'Check Date',          'description' => '' ),
        array( 'key' => 'cardType',          'value' => 'Card Type',           'description' => 'Credit card / Apple Pay / Google Pay: Visa, MasterCard, Discover, AmericanExpress, DinersClub or JCB' ),
        array( 'key' => 'cardLast4',         'value' => 'Card Last 4 Digits',  'description' => 'Required with Card Type' ),
        array( 'key' => 'cardExpMonth',      'value' => 'Card Expiry Month',   'description' => 'Required with Card Type' ),
        array( 'key' => 'cardExpYear',       'value' => 'Card Expiry Year',    'description' => 'Required with Card Type' ),
        array( 'key' => 'eftAccountType',    'value' => 'EFT Account Type',    'description' => 'EFT: Checking or Savings' ),
        array( 'key' => 'eftLast4',          'value' => 'EFT Last 4 Digits',   'description' => '' ),
        array( 'key' => 'eftRoutingNumber',  'value' => 'EFT Routing Number',  'description' => '' ),
        array( 'key' => 'inKindDescription', 'value' => 'In-Kind Description', 'description' => 'Required when Payment Method is InKind' ),
        array( 'key' => 'inKindType',        'value' => 'In-Kind Type',        'description' => 'Goods or Services' ),
        array( 'key' => 'inKindMarketValue', 'value' => 'In-Kind Market Value', 'description' => '' ),
    );
}

function adfoin_bloomerang_credentials_list() {
    foreach ( adfoin_read_credentials( 'bloomerang' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_bloomerang_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'bloomerang', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://api.bloomerang.co/v2/' . ltrim( $endpoint, '/' );
    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-API-Key'    => $api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_bloomerang_job_queue', 'adfoin_bloomerang_job_queue', 10, 1 );
function adfoin_bloomerang_job_queue( $data ) {
    adfoin_bloomerang_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_bloomerang_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );
    $fields  = adfoin_bloomerang_parse_fields( $data, $posted_data );

    if ( $record['task'] === 'create_constituent' ) {
        adfoin_bloomerang_request( 'constituent/merge', 'POST', adfoin_bloomerang_build_constituent( $fields ), $record, $cred_id );
    } elseif ( $record['task'] === 'create_transaction' ) {
        $body = adfoin_bloomerang_build_transaction( $fields, $cred_id, $record );
        if ( $body ) adfoin_bloomerang_request( 'transaction', 'POST', $body, $record, $cred_id );
    }
}

function adfoin_bloomerang_parse_fields( $data, $posted_data ) {
    $fields = array();
    foreach ( $data as $k => $v ) {
        if ( $k === 'credId' ) continue;
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( is_string( $parsed ) ) $parsed = trim( $parsed );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    return $fields;
}

/** Body for POST /constituent/merge (merges into an existing duplicate: name + email/phone/address). */
function adfoin_bloomerang_build_constituent( $fields, $prefix = '' ) {
    $get = function ( $key ) use ( $fields, $prefix ) {
        $k = $prefix ? $prefix . ucfirst( $key ) : $key;
        return isset( $fields[ $k ] ) ? $fields[ $k ] : '';
    };
    $org  = $get( 'organization' );
    $type = $prefix ? ( $org && ! $get( 'firstName' ) ? 'Organization' : 'Individual' ) : ( strcasecmp( $get( 'type' ), 'Organization' ) === 0 ? 'Organization' : 'Individual' );

    $body = array( 'Type' => $type );
    if ( $type === 'Organization' ) {
        $body['FullName'] = $org;
    } else {
        $body['FirstName'] = $get( 'firstName' );
        $body['LastName']  = $get( 'lastName' );
    }
    if ( $get( 'jobTitle' ) ) $body['JobTitle'] = $get( 'jobTitle' );
    if ( $get( 'email' ) )    $body['PrimaryEmail'] = array( 'Type' => 'Home', 'Value' => $get( 'email' ) );
    if ( $get( 'phone' ) )    $body['PrimaryPhone'] = array( 'Type' => 'Home', 'Number' => $get( 'phone' ) );
    $addr = array();
    foreach ( array( 'street' => 'Street', 'city' => 'City', 'state' => 'State', 'zip' => 'PostalCode', 'country' => 'Country' ) as $local => $remote ) {
        if ( $get( $local ) ) $addr[ $remote ] = $get( $local );
    }
    if ( $addr ) { $addr['Type'] = 'Home'; $body['PrimaryAddress'] = $addr; }
    return $body;
}

/**
 * Builds the POST /transaction body. Schema per Bloomerang's OpenAPI spec:
 * AccountId, Date, Amount, Method and Designations are always required, and
 * each Method adds its own required fields (CheckNumber, InKindDescription,
 * card or EFT details). Fund/Campaign/Appeal are IDs on the designation.
 */
function adfoin_bloomerang_build_transaction( $fields, $cred_id, $record = array() ) {
    $account_id = adfoin_bloomerang_resolve_donor( $fields, $cred_id, $record );
    if ( ! $account_id ) {
        if ( $record ) {
            $email = isset( $fields['donorEmail'] ) ? $fields['donorEmail'] : '';
            adfoin_add_to_log( new WP_Error( 'bloomerang_donor_not_found', sprintf( 'No Bloomerang constituent found for "%s". Map Donor First Name and Last Name (or Organization Name) so the donor is created automatically.', $email ) ), 'https://api.bloomerang.co/v2/transaction', array(), $record );
        }
        return null;
    }

    $amount = adfoin_bloomerang_money( isset( $fields['amount'] ) ? $fields['amount'] : 0 );
    $method = adfoin_bloomerang_normalize_method( isset( $fields['method'] ) ? $fields['method'] : '' );

    $designation = array( 'Type' => 'Donation', 'Amount' => $amount );
    foreach ( array( 'fund' => array( 'funds', 'FundId' ), 'campaign' => array( 'campaigns', 'CampaignId' ), 'appeal' => array( 'appeals', 'AppealId' ) ) as $key => $map ) {
        if ( empty( $fields[ $key ] ) ) continue;
        $id = ctype_digit( (string) $fields[ $key ] ) ? (int) $fields[ $key ] : adfoin_bloomerang_find_id( $map[0], $fields[ $key ], $cred_id );
        if ( $id ) $designation[ $map[1] ] = (int) $id;
    }
    // FundId is required on Donation and RecurringDonation designations.
    if ( empty( $designation['FundId'] ) ) {
        $default_fund = adfoin_bloomerang_default_fund( $cred_id );
        if ( $default_fund ) $designation['FundId'] = (int) $default_fund;
    }
    if ( ! empty( $fields['note'] ) )          $designation['Note'] = $fields['note'];
    if ( ! empty( $fields['nonDeductible'] ) ) $designation['NonDeductibleAmount'] = adfoin_bloomerang_money( $fields['nonDeductible'] );

    $body = array(
        'AccountId'    => (int) $account_id,
        'Date'         => adfoin_bloomerang_date( isset( $fields['date'] ) ? $fields['date'] : '' ),
        'Amount'       => $amount,
        'Method'       => $method,
        'Designations' => array( $designation ),
    );
    if ( ! empty( $fields['feeAmount'] ) )      $body['FeeAmount']      = adfoin_bloomerang_money( $fields['feeAmount'] );
    if ( ! empty( $fields['integrationUrl'] ) ) $body['IntegrationUrl'] = $fields['integrationUrl'];

    switch ( $method ) {
        case 'Check':
            if ( ! empty( $fields['checkNumber'] ) ) $body['CheckNumber'] = (string) $fields['checkNumber'];
            if ( ! empty( $fields['checkDate'] ) )   $body['CheckDate']   = adfoin_bloomerang_date( $fields['checkDate'] );
            break;
        case 'InKind':
            if ( ! empty( $fields['inKindDescription'] ) ) $body['InKindDescription'] = $fields['inKindDescription'];
            if ( ! empty( $fields['inKindType'] ) )        $body['InKindType']        = stripos( $fields['inKindType'], 'serv' ) === 0 ? 'Services' : 'Goods';
            if ( ! empty( $fields['inKindMarketValue'] ) ) $body['InKindMarketValue'] = adfoin_bloomerang_money( $fields['inKindMarketValue'] );
            break;
        case 'CreditCard':
        case 'ApplePay':
        case 'GooglePay':
            if ( ! empty( $fields['cardType'] ) )     $body['CreditCardType']           = adfoin_bloomerang_normalize_card( $fields['cardType'] );
            if ( ! empty( $fields['cardLast4'] ) )    $body['CreditCardLastFourDigits'] = substr( preg_replace( '/\D/', '', $fields['cardLast4'] ), -4 );
            if ( ! empty( $fields['cardExpMonth'] ) ) $body['CreditCardExpMonth']       = (int) $fields['cardExpMonth'];
            if ( ! empty( $fields['cardExpYear'] ) )  $body['CreditCardExpYear']        = (int) $fields['cardExpYear'];
            break;
        case 'Eft':
            if ( ! empty( $fields['eftAccountType'] ) )   $body['EftAccountType']    = stripos( $fields['eftAccountType'], 'sav' ) === 0 ? 'Savings' : 'Checking';
            if ( ! empty( $fields['eftLast4'] ) )         $body['EftLastFourDigits'] = substr( preg_replace( '/\D/', '', $fields['eftLast4'] ), -4 );
            if ( ! empty( $fields['eftRoutingNumber'] ) ) $body['EftRoutingNumber']  = (string) $fields['eftRoutingNumber'];
            break;
    }
    return $body;
}

/**
 * Finds the donor's constituent ID. With a name mapped, POST /constituent/merge
 * finds-or-creates (Bloomerang dedupes on name + email) and returns the Id;
 * otherwise falls back to an email search.
 */
function adfoin_bloomerang_resolve_donor( $fields, $cred_id, $record = array() ) {
    $email = isset( $fields['donorEmail'] ) ? $fields['donorEmail'] : '';
    $has_name = ! empty( $fields['donorOrganization'] ) || ( ! empty( $fields['donorFirstName'] ) && ! empty( $fields['donorLastName'] ) );

    if ( $has_name ) {
        $response = adfoin_bloomerang_request( 'constituent/merge', 'POST', adfoin_bloomerang_build_constituent( $fields, 'donor' ), $record, $cred_id );
        if ( $response && ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['Id'] ) ) return $body['Id'];
        }
    }
    return $email ? adfoin_bloomerang_find_constituent( $email, $cred_id ) : '';
}

function adfoin_bloomerang_find_constituent( $email, $cred_id ) {
    $response = adfoin_bloomerang_request( 'constituents/search?take=25&search=' . rawurlencode( $email ), 'GET', array(), array(), $cred_id );
    if ( ! $response || is_wp_error( $response ) ) return '';
    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $results = isset( $body['Results'] ) && is_array( $body['Results'] ) ? $body['Results'] : array();
    foreach ( $results as $row ) {
        if ( isset( $row['Type'] ) && $row['Type'] === 'Household' ) continue;
        if ( isset( $row['PrimaryEmail']['Value'] ) && strcasecmp( $row['PrimaryEmail']['Value'], $email ) === 0 ) return $row['Id'];
    }
    // Search matched but the primary email differs (e.g. secondary email): take the first non-household hit.
    foreach ( $results as $row ) {
        if ( ! empty( $row['Id'] ) && ( ! isset( $row['Type'] ) || $row['Type'] !== 'Household' ) ) return $row['Id'];
    }
    return '';
}

/** The account's default fund (or first active fund) for designations with no fund mapped. */
function adfoin_bloomerang_default_fund( $cred_id ) {
    foreach ( array( 'funds?isDefault=true&isActive=true', 'funds?isActive=true&take=1' ) as $endpoint ) {
        $response = adfoin_bloomerang_request( $endpoint, 'GET', array(), array(), $cred_id );
        if ( ! $response || is_wp_error( $response ) ) continue;
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['Results'][0]['Id'] ) ) return $body['Results'][0]['Id'];
    }
    return '';
}

function adfoin_bloomerang_money( $value ) {
    return round( (float) preg_replace( '/[^0-9.\-]/', '', (string) $value ), 2 );
}

function adfoin_bloomerang_date( $value ) {
    $ts = $value ? strtotime( $value ) : false;
    return $ts ? gmdate( 'Y-m-d', $ts ) : current_time( 'Y-m-d' );
}

function adfoin_bloomerang_normalize_method( $value ) {
    $map = array(
        'none' => 'None', 'cash' => 'Cash', 'check' => 'Check', 'cheque' => 'Check',
        'creditcard' => 'CreditCard', 'card' => 'CreditCard', 'credit' => 'CreditCard', 'debitcard' => 'CreditCard', 'stripe' => 'CreditCard',
        'eft' => 'Eft', 'ach' => 'Eft', 'banktransfer' => 'Eft', 'bank' => 'Eft',
        'inkind' => 'InKind', 'applepay' => 'ApplePay', 'googlepay' => 'GooglePay', 'paypal' => 'PayPal', 'venmo' => 'Venmo',
    );
    $key = strtolower( preg_replace( '/[^a-z]/i', '', (string) $value ) );
    return isset( $map[ $key ] ) ? $map[ $key ] : 'Cash';
}

function adfoin_bloomerang_normalize_card( $value ) {
    $key = strtolower( preg_replace( '/[^a-z]/i', '', (string) $value ) );
    $map = array( 'visa' => 'Visa', 'mastercard' => 'MasterCard', 'mc' => 'MasterCard', 'discover' => 'Discover', 'americanexpress' => 'AmericanExpress', 'amex' => 'AmericanExpress', 'dinersclub' => 'DinersClub', 'diners' => 'DinersClub', 'jcb' => 'JCB' );
    return isset( $map[ $key ] ) ? $map[ $key ] : $value;
}

/** Looks up a Fund/Campaign/Appeal ID by name via its `search` query param, preferring an exact name match. */
function adfoin_bloomerang_find_id( $endpoint, $search, $cred_id ) {
    $response = adfoin_bloomerang_request( $endpoint . '?search=' . rawurlencode( $search ), 'GET', array(), array(), $cred_id );
    if ( ! $response || is_wp_error( $response ) ) return '';
    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $results = isset( $body['Results'] ) ? $body['Results'] : ( is_array( $body ) ? $body : array() );
    foreach ( $results as $row ) {
        if ( isset( $row['Name'] ) && strcasecmp( trim( $row['Name'] ), trim( $search ) ) === 0 ) return $row['Id'];
    }
    return ! empty( $results[0]['Id'] ) ? $results[0]['Id'] : '';
}
