<?php

add_filter( 'adfoin_action_providers', 'adfoin_donorperfect_actions', 10, 1 );
function adfoin_donorperfect_actions( $actions ) {
    $actions['donorperfect'] = array(
        'title' => 'DonorPerfect',
        'tasks' => array(
            'create_donor' => 'Create Donor',
            'create_gift'  => 'Create Gift',
        )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_donorperfect_settings_tab', 10, 1 );
function adfoin_donorperfect_settings_tab( $providers ) { $providers['donorperfect'] = 'DonorPerfect'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_donorperfect_settings_view', 10, 1 );
function adfoin_donorperfect_settings_view( $current_tab ) {
    if ( $current_tab !== 'donorperfect' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'donorperfect',
        'fields'   => array(
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );
    $instructions = __( 'In DonorPerfect, go to Settings > Integrations > API. Request an API token from your account manager and paste it above.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'DonorPerfect', 'advanced-form-integration' ), 'donorperfect', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_donorperfect_action_fields' );
function adfoin_donorperfect_action_fields() {
    ?>
    <script type="text/template" id="donorperfect-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_donor' || action.task == 'create_gift'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_donor' || action.task == 'create_gift'">
                <td scope="row-title"><label><?php esc_attr_e( 'DonorPerfect Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_donorperfect_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_donor', 'DonorPerfect [PRO]', 'custom fields and tags' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_donorperfect_credentials', 'adfoin_get_donorperfect_credentials' );
function adfoin_get_donorperfect_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'donorperfect' ) );
}

add_action( 'wp_ajax_adfoin_save_donorperfect_credentials', 'adfoin_save_donorperfect_credentials' );
function adfoin_save_donorperfect_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'donorperfect' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'donorperfect', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_donorperfect_fields', 'adfoin_get_donorperfect_fields' );
function adfoin_get_donorperfect_fields() {
    adfoin_verify_nonce();
    $task = isset( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : 'create_donor';

    if ( $task === 'create_gift' ) {
        $fields = array(
            array( 'key' => 'donorEmail', 'value' => 'Donor Email',   'description' => 'Used to look up the donor' ),
            array( 'key' => 'amount',     'value' => 'Amount',         'description' => '' ),
            array( 'key' => 'date',       'value' => 'Gift Date',      'description' => 'YYYY-MM-DD' ),
            array( 'key' => 'method',     'value' => 'Gift Method',    'description' => 'Check / Credit / Cash' ),
            array( 'key' => 'campaign',   'value' => 'Campaign Code',  'description' => '' ),
            array( 'key' => 'fund',       'value' => 'Fund Code',      'description' => '' ),
            array( 'key' => 'solicit',    'value' => 'Solicitation Code', 'description' => '' ),
            array( 'key' => 'note',       'value' => 'Note',           'description' => '' ),
        );
    } else {
        $fields = array(
            array( 'key' => 'firstName', 'value' => 'First Name', 'description' => '' ),
            array( 'key' => 'lastName',  'value' => 'Last Name',  'description' => '' ),
            array( 'key' => 'email',     'value' => 'Email',      'description' => '' ),
            array( 'key' => 'phone',     'value' => 'Phone',      'description' => '' ),
            array( 'key' => 'organization','value' => 'Organization', 'description' => '' ),
            array( 'key' => 'address',   'value' => 'Address',    'description' => '' ),
            array( 'key' => 'city',      'value' => 'City',       'description' => '' ),
            array( 'key' => 'state',     'value' => 'State',      'description' => '' ),
            array( 'key' => 'zip',       'value' => 'Zip',        'description' => '' ),
            array( 'key' => 'country',   'value' => 'Country',    'description' => '' ),
            array( 'key' => 'donorType', 'value' => 'Donor Type', 'description' => '' ),
            array( 'key' => 'note',      'value' => 'Note',       'description' => '' ),
        );
    }
    wp_send_json_success( $fields );
}

function adfoin_donorperfect_credentials_list() {
    foreach ( adfoin_read_credentials( 'donorperfect' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * DonorPerfect's real XML API (confirmed via the official
 * "DonorPerfect Online XML API Documentation User Manual", v6.9) does not
 * take one query param per field. A predefined procedure call is
 * `?apikey=...&action=dp_savedonor&params=@donor_id=0, @first_name='X', @middle_name=null, ...`
 * — a single `params` value containing SQL-literal-style `@name=value`
 * pairs (strings single-quoted, empty values the literal word null, not
 * an empty string). Dynamic SQL queries instead put the whole SELECT
 * statement directly in `action` with no `params` at all. Base domain is
 * donorperfect.net, not donorperfect.io.
 */
function adfoin_donorperfect_build_params( $fields ) {
    $parts = array();
    foreach ( $fields as $key => $spec ) {
        $value = $spec['value'];
        $quote = $spec['quote'];
        if ( $value === null || $value === '' ) {
            $parts[] = "@{$key}=null";
        } elseif ( $quote ) {
            $parts[] = "@{$key}='" . str_replace( "'", "''", $value ) . "'";
        } else {
            $parts[] = "@{$key}={$value}";
        }
    }
    return implode( ', ', $parts );
}

function adfoin_donorperfect_request( $action, $params_string, $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'donorperfect', $cred_id );
    $api_key     = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';
    if ( ! $api_key ) return;

    $url = 'https://www.donorperfect.net/prod/xmlrequest.asp?apikey=' . rawurlencode( $api_key ) . '&action=' . rawurlencode( $action );
    if ( $params_string !== '' ) $url .= '&params=' . rawurlencode( $params_string );

    $args = array( 'timeout' => 30, 'method' => 'GET' );
    $response = wp_remote_request( $url, $args );
    if ( $record ) adfoin_add_to_log( $response, $url, $args, $record );
    return $response;
}

/**
 * Runs a raw Dynamic Query SELECT (the whole statement goes in `action`,
 * no `params`). Used to look up a donor_id by email since the predefined
 * dp_donorsearch procedure has no email parameter.
 */
function adfoin_donorperfect_query( $sql, $record = array(), $cred_id = '' ) {
    return adfoin_donorperfect_request( $sql, '', $record, $cred_id );
}

/** Returns the value of the first <field> in a DonorPerfect XML response. */
function adfoin_donorperfect_first_value( $response ) {
    if ( is_wp_error( $response ) ) return '';
    $xml = @simplexml_load_string( wp_remote_retrieve_body( $response ) );
    if ( ! $xml || ! isset( $xml->record ) ) return '';
    foreach ( $xml->record as $record ) {
        foreach ( $record->field as $field ) {
            return (string) $field['value'];
        }
    }
    return '';
}

function adfoin_donorperfect_find_donor_id( $email, $cred_id ) {
    $sql = "select donor_id from dp where email='" . str_replace( "'", "''", $email ) . "'";
    return adfoin_donorperfect_first_value( adfoin_donorperfect_query( $sql, array(), $cred_id ) );
}

/**
 * Saves a User Defined Field via dp_save_udf_xml. @matching_id is a
 * donor_id or gift_id depending on which record the UDF belongs to.
 * All incoming form values are treated as character (text) UDFs.
 */
function adfoin_donorperfect_save_udf( $matching_id, $field_name, $value, $cred_id ) {
    if ( ! $matching_id ) return;
    $params = adfoin_donorperfect_build_params( array(
        'matching_id'  => array( 'value' => $matching_id, 'quote' => false ),
        'field_name'   => array( 'value' => $field_name, 'quote' => true ),
        'data_type'    => array( 'value' => 'C', 'quote' => true ),
        'char_value'   => array( 'value' => $value, 'quote' => true ),
        'date_value'   => array( 'value' => null, 'quote' => true ),
        'number_value' => array( 'value' => null, 'quote' => true ),
        'user_id'      => array( 'value' => 'AFI', 'quote' => true ),
    ) );
    return adfoin_donorperfect_request( 'dp_save_udf_xml', $params, array(), $cred_id );
}

add_action( 'adfoin_donorperfect_job_queue', 'adfoin_donorperfect_job_queue', 10, 1 );
function adfoin_donorperfect_job_queue( $data ) {
    adfoin_donorperfect_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_donorperfect_build_donor_params( $fields ) {
    $is_org = ! empty( $fields['organization'] );
    return adfoin_donorperfect_build_params( array(
        'donor_id'        => array( 'value' => 0, 'quote' => false ),
        'first_name'      => array( 'value' => $is_org ? null : ( isset( $fields['firstName'] ) ? $fields['firstName'] : null ), 'quote' => true ),
        'last_name'       => array( 'value' => $is_org ? $fields['organization'] : ( isset( $fields['lastName'] ) ? $fields['lastName'] : null ), 'quote' => true ),
        'middle_name'     => array( 'value' => null, 'quote' => true ),
        'suffix'          => array( 'value' => null, 'quote' => true ),
        'title'           => array( 'value' => null, 'quote' => true ),
        'salutation'      => array( 'value' => null, 'quote' => true ),
        'prof_title'      => array( 'value' => null, 'quote' => true ),
        'opt_line'        => array( 'value' => null, 'quote' => true ),
        'address'         => array( 'value' => isset( $fields['address'] ) ? $fields['address'] : null, 'quote' => true ),
        'address2'        => array( 'value' => null, 'quote' => true ),
        'city'            => array( 'value' => isset( $fields['city'] ) ? $fields['city'] : null, 'quote' => true ),
        'state'           => array( 'value' => isset( $fields['state'] ) ? $fields['state'] : null, 'quote' => true ),
        'zip'             => array( 'value' => isset( $fields['zip'] ) ? $fields['zip'] : null, 'quote' => true ),
        'country'         => array( 'value' => isset( $fields['country'] ) ? $fields['country'] : null, 'quote' => true ),
        'address_type'    => array( 'value' => null, 'quote' => true ),
        'home_phone'      => array( 'value' => isset( $fields['phone'] ) ? $fields['phone'] : null, 'quote' => true ),
        'business_phone'  => array( 'value' => null, 'quote' => true ),
        'fax_phone'       => array( 'value' => null, 'quote' => true ),
        'mobile_phone'    => array( 'value' => null, 'quote' => true ),
        'email'           => array( 'value' => isset( $fields['email'] ) ? $fields['email'] : null, 'quote' => true ),
        'org_rec'         => array( 'value' => $is_org ? 'Y' : 'N', 'quote' => true ),
        'donor_type'      => array( 'value' => isset( $fields['donorType'] ) ? $fields['donorType'] : ( $is_org ? 'CO' : 'IN' ), 'quote' => true ),
        'nomail'          => array( 'value' => 'N', 'quote' => true ),
        'nomail_reason'   => array( 'value' => null, 'quote' => true ),
        'narrative'       => array( 'value' => isset( $fields['note'] ) ? $fields['note'] : null, 'quote' => true ),
        'donor_rcpt_type' => array( 'value' => null, 'quote' => true ),
        'user_id'         => array( 'value' => 'AFI', 'quote' => true ),
    ) );
}

function adfoin_donorperfect_build_gift_params( $donor_id, $fields ) {
    return adfoin_donorperfect_build_params( array(
        'gift_id'              => array( 'value' => 0, 'quote' => false ),
        'donor_id'             => array( 'value' => $donor_id, 'quote' => false ),
        'record_type'          => array( 'value' => 'G', 'quote' => true ),
        'gift_date'            => array( 'value' => isset( $fields['date'] ) ? $fields['date'] : gmdate( 'm/d/Y' ), 'quote' => true ),
        'amount'               => array( 'value' => isset( $fields['amount'] ) ? floatval( $fields['amount'] ) : 0, 'quote' => false ),
        'gl_code'              => array( 'value' => isset( $fields['gl_code'] ) ? $fields['gl_code'] : null, 'quote' => true ),
        'solicit_code'         => array( 'value' => isset( $fields['solicit'] ) ? $fields['solicit'] : null, 'quote' => true ),
        'sub_solicit_code'     => array( 'value' => null, 'quote' => true ),
        'campaign'             => array( 'value' => isset( $fields['campaign'] ) ? $fields['campaign'] : null, 'quote' => true ),
        'gift_type'            => array( 'value' => isset( $fields['method'] ) ? $fields['method'] : null, 'quote' => true ),
        'split_gift'           => array( 'value' => 'N', 'quote' => true ),
        'pledge_payment'       => array( 'value' => 'N', 'quote' => true ),
        'reference'            => array( 'value' => null, 'quote' => true ),
        'transaction_id'       => array( 'value' => null, 'quote' => true ),
        'memory_honor'         => array( 'value' => null, 'quote' => true ),
        'gfname'               => array( 'value' => null, 'quote' => true ),
        'glname'               => array( 'value' => null, 'quote' => true ),
        'fmv'                  => array( 'value' => 0, 'quote' => false ),
        'batch_no'             => array( 'value' => 0, 'quote' => false ),
        'gift_narrative'       => array( 'value' => isset( $fields['note'] ) ? $fields['note'] : null, 'quote' => true ),
        'ty_letter_no'         => array( 'value' => null, 'quote' => true ),
        'glink'                => array( 'value' => null, 'quote' => true ),
        'plink'                => array( 'value' => null, 'quote' => true ),
        'nocalc'               => array( 'value' => 'N', 'quote' => true ),
        'receipt'              => array( 'value' => 'N', 'quote' => true ),
        'old_amount'           => array( 'value' => null, 'quote' => true ),
        'user_id'              => array( 'value' => 'AFI', 'quote' => true ),
        'gift_aid_date'        => array( 'value' => null, 'quote' => true ),
        'gift_aid_amt'         => array( 'value' => null, 'quote' => true ),
        'gift_aid_eligible_g'  => array( 'value' => null, 'quote' => true ),
        'currency'             => array( 'value' => 'USD', 'quote' => true ),
        'receipt_delivery_g'   => array( 'value' => null, 'quote' => true ),
        'acknowledgepref'      => array( 'value' => null, 'quote' => true ),
    ) );
}

function adfoin_donorperfect_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }

    if ( $record['task'] === 'create_donor' ) {
        adfoin_donorperfect_request( 'dp_savedonor', adfoin_donorperfect_build_donor_params( $fields ), $record, $cred_id );
    } elseif ( $record['task'] === 'create_gift' ) {
        if ( empty( $fields['donorEmail'] ) ) return;
        $donor_id = adfoin_donorperfect_find_donor_id( $fields['donorEmail'], $cred_id );
        if ( ! $donor_id ) return;
        adfoin_donorperfect_request( 'dp_savegift', adfoin_donorperfect_build_gift_params( $donor_id, $fields ), $record, $cred_id );
    }
}
