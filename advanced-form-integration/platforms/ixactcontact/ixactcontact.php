<?php

/**
 * IXACT Contact has no public REST API (confirmed — GetApp and IXACT's own
 * help docs describe no developer API). The real, documented integration
 * path is "Lead Capture via Email": each account gets a unique parsing
 * email address (User Profile > Lead Capture); IXACT scans the body of any
 * email sent to it and maps recognizable text to contact fields, dumping
 * anything unmapped into the contact's Notes. This replaces the previous
 * implementation, which POSTed JSON to a fabricated
 * api.ixactcontact.com/api/v1/contacts REST endpoint that doesn't exist.
 *
 * @link https://services.ixactcontact.com/apphelpv2/Contacts/Capturing_Leads_from_Emails.htm
 */

add_filter( 'adfoin_action_providers', 'adfoin_ixactcontact_actions', 10, 1 );
function adfoin_ixactcontact_actions( $actions ) {
    $actions['ixactcontact'] = array(
        'title' => 'IXACT Contact',
        'tasks' => array( 'create_contact' => 'Create Contact (via Lead Capture Email)' )
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_ixactcontact_settings_tab', 10, 1 );
function adfoin_ixactcontact_settings_tab( $providers ) { $providers['ixactcontact'] = 'IXACT Contact'; return $providers; }

add_action( 'adfoin_settings_view', 'adfoin_ixactcontact_settings_view', 10, 1 );
function adfoin_ixactcontact_settings_view( $current_tab ) {
    if ( $current_tab !== 'ixactcontact' ) return;
    $arguments = wp_json_encode( array(
        'platform' => 'ixactcontact',
        'fields'   => array(
            array( 'key' => 'captureEmail', 'label' => __( 'Lead Capture Email Address', 'advanced-form-integration' ) ),
        ),
    ) );
    $instructions = __( 'IXACT Contact has no REST API. In IXACT Contact, go to User Profile > Lead Capture to find your account\'s unique lead capture email address, then paste it above. Advanced Form Integration will email each submission to that address; IXACT Contact scans the email body and creates/updates the contact automatically.', 'advanced-form-integration' );
    echo adfoin_platform_settings_template( __( 'IXACT Contact', 'advanced-form-integration' ), 'ixactcontact', $arguments, $instructions );
}

add_action( 'adfoin_action_fields', 'adfoin_ixactcontact_action_fields' );
function adfoin_ixactcontact_action_fields() {
    ?>
    <script type="text/template" id="ixactcontact-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'IXACT Contact Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_ixactcontact_credentials_list(); ?>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'IXACT Contact [PRO]', 'tags and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_ixactcontact_credentials', 'adfoin_get_ixactcontact_credentials' );
function adfoin_get_ixactcontact_credentials() {
    adfoin_verify_nonce();
    wp_send_json_success( adfoin_read_credentials( 'ixactcontact' ) );
}

add_action( 'wp_ajax_adfoin_save_ixactcontact_credentials', 'adfoin_save_ixactcontact_credentials' );
function adfoin_save_ixactcontact_credentials() {
    adfoin_verify_nonce();
    if ( $_POST['platform'] === 'ixactcontact' ) {
        $data = adfoin_array_map_recursive( 'sanitize_text_field', $_POST['data'] );
        adfoin_save_credentials( 'ixactcontact', $data );
    }
    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_ixactcontact_fields', 'adfoin_get_ixactcontact_fields' );
function adfoin_get_ixactcontact_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name', 'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',  'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',      'description' => '' ),
        array( 'key' => 'phone',       'value' => 'Phone',      'description' => '' ),
        array( 'key' => 'mobile',      'value' => 'Mobile',     'description' => '' ),
        array( 'key' => 'street',      'value' => 'Street',     'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',       'description' => '' ),
        array( 'key' => 'province',    'value' => 'State / Province', 'description' => '' ),
        array( 'key' => 'postal',      'value' => 'Zip / Postal', 'description' => '' ),
        array( 'key' => 'country',     'value' => 'Country',    'description' => '' ),
        array( 'key' => 'contactType', 'value' => 'Contact Type', 'description' => 'Buyer / Seller / Other' ),
        array( 'key' => 'leadSource',  'value' => 'Lead Source','description' => '' ),
        array( 'key' => 'birthday',    'value' => 'Birthday',   'description' => 'YYYY-MM-DD' ),
        array( 'key' => 'note',        'value' => 'Note',       'description' => '' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_ixactcontact_credentials_list() {
    foreach ( adfoin_read_credentials( 'ixactcontact' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

/**
 * IXACT's parser is fuzzy/best-effort (its own docs say unmapped text lands
 * in Notes), so plain "Label: value" lines are the most reliable format —
 * it mirrors the kind of lead-notification emails (Zillow, realtor sites,
 * etc.) the parser is explicitly built to already handle.
 */
function adfoin_ixactcontact_build_email_body( $fields, $labels ) {
    $lines = array();
    foreach ( $labels as $key => $label ) {
        if ( ! empty( $fields[ $key ] ) ) $lines[] = "{$label}: {$fields[ $key ]}";
    }
    return implode( "\n", $lines );
}

function adfoin_ixactcontact_send_lead_email( $fields, $labels, $record, $cred_id ) {
    $credentials    = adfoin_get_credentials_by_id( 'ixactcontact', $cred_id );
    $capture_email  = isset( $credentials['captureEmail'] ) ? $credentials['captureEmail'] : '';
    if ( ! $capture_email || ! is_email( $capture_email ) ) return;

    $name    = trim( ( isset( $fields['firstName'] ) ? $fields['firstName'] : '' ) . ' ' . ( isset( $fields['lastName'] ) ? $fields['lastName'] : '' ) );
    $subject = $name !== '' ? "New Lead: {$name}" : 'New Lead';
    $body    = adfoin_ixactcontact_build_email_body( $fields, $labels );

    $sent = wp_mail( $capture_email, $subject, $body );

    if ( $record ) {
        $fake_response = array(
            'body'     => $sent ? 'Email sent to ' . $capture_email : 'wp_mail() failed',
            'response' => array( 'code' => $sent ? 200 : 500 ),
        );
        adfoin_add_to_log( $fake_response, $capture_email, array( 'body' => $body ), $record );
    }

    return $sent;
}

add_action( 'adfoin_ixactcontact_job_queue', 'adfoin_ixactcontact_job_queue', 10, 1 );
function adfoin_ixactcontact_job_queue( $data ) {
    adfoin_ixactcontact_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_ixactcontact_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_contact' ) return;

    $labels = array(
        'firstName'   => 'First Name',
        'lastName'    => 'Last Name',
        'email'       => 'Email',
        'phone'       => 'Phone',
        'mobile'      => 'Mobile',
        'street'      => 'Address',
        'city'        => 'City',
        'province'    => 'State',
        'postal'      => 'Zip',
        'country'     => 'Country',
        'contactType' => 'Contact Type',
        'leadSource'  => 'Lead Source',
        'birthday'    => 'Birthday',
        'note'        => 'Comments',
    );

    adfoin_ixactcontact_send_lead_email( $fields, $labels, $record, $cred_id );
}
