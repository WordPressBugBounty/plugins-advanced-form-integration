<?php

add_filter( 'adfoin_action_providers', 'adfoin_brokermint_actions', 10, 1 );
function adfoin_brokermint_actions( $actions ) {
    $actions['brokermint'] = array(
        'title' => 'Brokermint',
        'tasks' => array(
            'create_contact'    => 'Create Contact',
            'create_transaction'=> 'Create Transaction',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_brokermint_settings_tab', 10, 1 );
function adfoin_brokermint_settings_tab( $providers ) { $providers['brokermint'] = 'Brokermint'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_brokermint_settings_view', 10, 1 );
function adfoin_brokermint_settings_view( $current_tab ) {
    if ( $current_tab !== 'brokermint' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'brokermint',
        'fields'   => array(
            array( 'key' => 'apiKey',   'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'sourceId', 'label' => __( 'Source ID', 'advanced-form-integration' ) ),
        ),
    ) );
    // Confirmed via Brokermint's API docs (my.brokermint.com/api_docs) and
    // third-party client sources — transaction creation goes through the
    // "incoming transactions" endpoint, which requires a source_id
    // identifying the sending integration. Ask support@brokermint.com for
    // both the API key and the Source ID to use.
    $instructions = __( 'In Brokermint, go to your User Profile > API Access to generate an API key, or email support@brokermint.com. Ask them for a Source ID as well — it is required to push incoming transactions.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'Brokermint', 'advanced-form-integration' ), 'brokermint', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_brokermint_action_fields' );
function adfoin_brokermint_action_fields() {
    ?>
    <script type="text/template" id="brokermint-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact' || action.task == 'create_transaction'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact' || action.task == 'create_transaction'">
                <td scope="row-title"><label><?php esc_attr_e( 'Brokermint Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_brokermint_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Brokermint [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_brokermint_credentials', 'adfoin_get_brokermint_credentials' );
function adfoin_get_brokermint_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'brokermint' ) );
}

add_action( 'wp_ajax_adfoin_save_brokermint_credentials', 'adfoin_save_brokermint_credentials' );
function adfoin_save_brokermint_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'brokermint' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'brokermint', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_brokermint_fields', 'adfoin_get_brokermint_fields' );
function adfoin_get_brokermint_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_contact';

    if ( $task === 'create_transaction' ) {
        $fields = array(
            array( 'key' => 'address',        'value' => 'Property Address', 'description' => '' ),
            array( 'key' => 'city',           'value' => 'Property City',    'description' => '' ),
            array( 'key' => 'state',          'value' => 'Property State',   'description' => '' ),
            array( 'key' => 'zip',            'value' => 'Property Zip',     'description' => '' ),
            array( 'key' => 'price',          'value' => 'Price',            'description' => '' ),
            array( 'key' => 'status',         'value' => 'Status',           'description' => '' ),
            array( 'key' => 'transactionType','value' => 'Transaction Type', 'description' => 'sale / listing / lease' ),
            array( 'key' => 'listingDate',    'value' => 'Listing Date',     'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'expirationDate', 'value' => 'Expiration Date',  'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'acceptanceDate', 'value' => 'Acceptance Date',  'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'closingDate',    'value' => 'Closing Date',     'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'agentName',      'value' => 'Agent Name',       'description' => '' ),
            array( 'key' => 'mlsNumber',      'value' => 'MLS Number',       'description' => 'Sent as a custom attribute — not a native Brokermint transaction field' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName',   'value' => 'First Name',  'description' => '' ),
            array( 'key' => 'lastName',    'value' => 'Last Name',   'description' => '' ),
            array( 'key' => 'email',       'value' => 'Email',       'description' => '' ),
            array( 'key' => 'phone',       'value' => 'Phone',       'description' => '' ),
            array( 'key' => 'mobilePhone', 'value' => 'Mobile Phone','description' => '' ),
            array( 'key' => 'company',     'value' => 'Company',     'description' => '' ),
            array( 'key' => 'street',      'value' => 'Address',     'description' => '' ),
            array( 'key' => 'city',        'value' => 'City',        'description' => '' ),
            array( 'key' => 'state',       'value' => 'State',       'description' => '' ),
            array( 'key' => 'zip',         'value' => 'Zip',         'description' => '' ),
            array( 'key' => 'contactType', 'value' => 'Contact Type','description' => 'client / lender / inspector / etc.' ),
            array( 'key' => 'leadSource',  'value' => 'Lead Source', 'description' => '' ),
            array( 'key' => 'note',        'value' => 'Comments',    'description' => '' ),
            array( 'key' => 'externalId',  'value' => 'External ID', 'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_brokermint_credentials_list() {
    foreach ( adfoin_read_credentials( 'brokermint' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_brokermint_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'brokermint', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url  = 'https://my.brokermint.com/api/v2/' . ltrim( $endpoint, '/' );
    $sep  = strpos( $url, '?' ) === false ? '?' : '&';
    $url .= $sep . 'api_key=' . rawurlencode( $api_key );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array( 'Content-Type' => 'application/json' ),
    );
    if ( $method === 'POST' || $method === 'PUT' ) $args['body'] = wp_json_encode( $data );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

add_action( 'adfoin_brokermint_job_queue', 'adfoin_brokermint_job_queue', 10, 1 );
function adfoin_brokermint_job_queue( $data ) {
    adfoin_brokermint_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_brokermint_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $record['task'] === 'create_contact' ) {
        // Confirmed field names from Brokermint's Ruby/Python API clients
        // (contact_mapping.rb) — the previous version used 'street'/'notes',
        // neither of which Brokermint's contacts endpoint recognizes.
        $body = array();
        foreach ( array( 'firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'mobilePhone' => 'mobile_phone', 'company' => 'company', 'contactType' => 'contact_type', 'leadSource' => 'lead_source', 'note' => 'comments', 'externalId' => 'external_id', 'street' => 'address', 'city' => 'city', 'state' => 'state', 'zip' => 'zip' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
        }
        adfoin_brokermint_request( 'contacts', 'POST', $body, $record, $cred_id );
    } elseif ( $record['task'] === 'create_transaction' ) {
        $credentials = adfoin_get_credentials_by_id( 'brokermint', $cred_id );
        $source_id   = isset( $credentials['sourceId'] ) ? $credentials['sourceId'] : '';
        if ( ! $source_id ) return;

        // Confirmed: transaction creation is not a flat POST /transactions —
        // it goes through the "incoming transactions" funnel, which wraps a
        // TransactionAttribute object in a `transactions` array alongside
        // the integration's source_id.
        $attrs = array();
        foreach ( array( 'address' => 'address', 'city' => 'city', 'state' => 'state', 'zip' => 'zip', 'price' => 'price', 'status' => 'status', 'transactionType' => 'transaction_type', 'listingDate' => 'listing_date', 'expirationDate' => 'expiration_date', 'acceptanceDate' => 'acceptance_date', 'closingDate' => 'closing_date', 'agentName' => 'agent_name' ) as $local => $remote ) {
            if ( ! empty( $fields[ $local ] ) ) $attrs[ $remote ] = $fields[ $local ];
        }
        if ( ! empty( $fields['mlsNumber'] ) ) {
            $attrs['custom_attributes'] = array( 'MLS Number' => $fields['mlsNumber'] );
        }

        $body = array(
            'source_id'    => $source_id,
            'transactions' => array( $attrs ),
        );
        adfoin_brokermint_request( 'incoming_transactions', 'POST', $body, $record, $cred_id );
    }
}
