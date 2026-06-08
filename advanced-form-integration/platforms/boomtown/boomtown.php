<?php

add_filter( 'adfoin_action_providers', 'adfoin_boomtown_actions', 10, 1 );

function adfoin_boomtown_actions( $actions ) {
    $actions['boomtown'] = array(
        'title' => 'BoomTown',
        'tasks' => array(
            'create_lead' => 'Create Lead'
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_boomtown_settings_tab', 10, 1 );

function adfoin_boomtown_settings_tab( $providers ) {
    $providers['boomtown'] = 'BoomTown';
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_boomtown_settings_view', 10, 1 );

function adfoin_boomtown_settings_view( $current_tab ) {
    if ( $current_tab !== 'boomtown' ) return;

    $title     = __( 'BoomTown', 'advanced-form-integration' );
    $key       = 'boomtown';
    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiKey',  'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'tenant',  'label' => __( 'Tenant / Account Code', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'Contact your BoomTown account manager to generate a Lead Capture API key for your tenant. Paste the key and your tenant code above.', 'advanced-form-integration' );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_boomtown_action_fields' );

function adfoin_boomtown_action_fields() {
    ?>
    <script type="text/template" id="boomtown-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td scope="row">
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_lead'">
                <td scope="row-title">
                    <label for="tablecell"><?php esc_attr_e( 'BoomTown Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_boomtown_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_lead', 'BoomTown [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_boomtown_credentials', 'adfoin_get_boomtown_credentials' );
function adfoin_get_boomtown_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'boomtown' ) );
}

add_action( 'wp_ajax_adfoin_save_boomtown_credentials', 'adfoin_save_boomtown_credentials' );
function adfoin_save_boomtown_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'boomtown' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'boomtown', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_boomtown_fields', 'adfoin_get_boomtown_fields' );
function adfoin_get_boomtown_fields() {
    adfoin_verify_nonce();

    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name',   'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',    'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',        'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',        'description' => '' ),
        array( 'key' => 'phoneType',   'value' => 'Phone Type',   'description' => 'mobile, home, work' ),
        array( 'key' => 'address',     'value' => 'Street',       'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',         'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',        'description' => '' ),
        array( 'key' => 'zip',         'value' => 'Zip',          'description' => '' ),
        array( 'key' => 'country',     'value' => 'Country',      'description' => '' ),
        array( 'key' => 'source',      'value' => 'Source',       'description' => 'Lead source label' ),
        array( 'key' => 'sourceUrl',   'value' => 'Source URL',   'description' => 'Originating page URL' ),
        array( 'key' => 'propertyUrl', 'value' => 'Property URL', 'description' => 'Listing URL of interest' ),
        array( 'key' => 'priceMin',    'value' => 'Price Min',    'description' => '' ),
        array( 'key' => 'priceMax',    'value' => 'Price Max',    'description' => '' ),
        array( 'key' => 'bedsMin',     'value' => 'Beds Min',     'description' => '' ),
        array( 'key' => 'bathsMin',    'value' => 'Baths Min',    'description' => '' ),
        array( 'key' => 'timeframe',   'value' => 'Timeframe',    'description' => 'e.g. 0-3 months, 3-6 months' ),
        array( 'key' => 'leadType',    'value' => 'Lead Type',    'description' => 'buyer, seller, both' ),
        array( 'key' => 'comments',    'value' => 'Comments',     'description' => '' ),
        array( 'key' => 'agentEmail',  'value' => 'Assigned Agent Email', 'description' => 'Owner agent email' ),
    );

    wp_send_json_success( $fields );
}

function adfoin_boomtown_credentials_list() {
    foreach ( adfoin_read_credentials( 'boomtown' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_boomtown_request( $endpoint, $method = 'POST', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'boomtown', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    $tenant      = isset( $credentials['tenant'] ) ? $credentials['tenant'] : '';

    if ( ! $api_key ) {
        return;
    }

    $base_url = 'https://api.boomtownroi.com/v1/';
    $url      = $base_url . $endpoint;

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'X-API-Key'    => $api_key,
            'X-Tenant'     => $tenant,
            'Content-Type' => 'application/json',
        ),
    );

    if ( $method === 'POST' || $method === 'PUT' ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}

function adfoin_boomtown_create_lead( $fields, $record, $cred_id ) {
    $lead = array();

    foreach ( array( 'firstName', 'lastName', 'email', 'phone', 'phoneType', 'source', 'sourceUrl', 'propertyUrl', 'timeframe', 'leadType', 'comments', 'agentEmail' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) {
            $lead[ $k ] = $fields[ $k ];
        }
    }

    $address = array();
    foreach ( array( 'address', 'city', 'state', 'zip', 'country' ) as $k ) {
        if ( ! empty( $fields[ $k ] ) ) {
            $address[ $k ] = $fields[ $k ];
        }
    }
    if ( ! empty( $address ) ) {
        $lead['address'] = $address;
    }

    $criteria = array();
    foreach ( array( 'priceMin', 'priceMax', 'bedsMin', 'bathsMin' ) as $k ) {
        if ( isset( $fields[ $k ] ) && $fields[ $k ] !== '' ) {
            $criteria[ $k ] = is_numeric( $fields[ $k ] ) ? floatval( $fields[ $k ] ) : $fields[ $k ];
        }
    }
    if ( ! empty( $criteria ) ) {
        $lead['searchCriteria'] = $criteria;
    }

    return adfoin_boomtown_request( 'leads', 'POST', $lead, $record, $cred_id );
}

add_action( 'adfoin_boomtown_job_queue', 'adfoin_boomtown_job_queue', 10, 1 );
function adfoin_boomtown_job_queue( $data ) {
    adfoin_boomtown_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_boomtown_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );
    $task    = $record['task'];

    $contact_fields = array();
    foreach ( $data as $key => $value ) {
        $parsed_value = adfoin_get_parsed_values( $value, $posted_data );
        if ( $parsed_value !== '' && $parsed_value !== null ) {
            $contact_fields[ $key ] = $parsed_value;
        }
    }

    if ( $task === 'create_lead' ) {
        adfoin_boomtown_create_lead( $contact_fields, $record, $cred_id );
    }
}
