<?php

add_filter( 'adfoin_action_providers', 'adfoin_netsuite_actions', 10, 1 );

function adfoin_netsuite_actions( $actions ) {
    $actions['netsuite'] = array(
        'title' => __( 'Oracle NetSuite', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Lead/Customer Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_netsuite_settings_tab', 10, 1 );

function adfoin_netsuite_settings_tab( $providers ) {
    $providers['netsuite'] = __( 'Oracle NetSuite', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_netsuite_settings_view', 10, 1 );

function adfoin_netsuite_settings_view( $current_tab ) {
    if ( 'netsuite' !== $current_tab ) {
        return;
    }

    $title = __( 'Oracle NetSuite', 'advanced-form-integration' );
    $key   = 'netsuite';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'account', 'label' => __( 'Account ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'email', 'label' => __( 'Integration User Email', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'password', 'label' => __( 'Integration User Password', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'role', 'label' => __( 'Role Internal ID', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'restDomain', 'label' => __( 'REST Domain (optional)', 'advanced-form-integration' ), 'hidden' => false ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                    <li>%5$s</li>
                </ol>
            </li>
            <li><strong>%6$s</strong>
                <ol>
                    <li>%7$s</li>
                    <li>%8$s</li>
                    <li>%9$s</li>
                </ol>
            </li>
        </ol>
        <p>%10$s</p>
        <p>%11$s</p>
        <p>%12$s</p>',
        esc_html__( 'Prepare NetSuite credentials', 'advanced-form-integration' ),
        esc_html__( 'Create an integration user with Web Services permission and set a strong password.', 'advanced-form-integration' ),
        esc_html__( 'Record your NetSuite Account ID (Setup → Company → Company Information).', 'advanced-form-integration' ),
        esc_html__( 'Identify the internal ID of the role assigned to the integration user (Setup → Users/Roles → Manage Roles).', 'advanced-form-integration' ),
        esc_html__( 'Optionally note the REST domain from Setup → Company → Company Information → SuiteTalk (REST).', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the Account ID, integration email, password, and role ID above.', 'advanced-form-integration' ),
        esc_html__( 'Leave the REST domain blank to default to https://{account}.suitetalk.api.netsuite.com.', 'advanced-form-integration' ),
        esc_html__( 'Save to make the credentials available when building automations.', 'advanced-form-integration' ),
        esc_html__( 'AFI authenticates using NLAuth headers which require SuiteTalk REST Web Services to be enabled.', 'advanced-form-integration' ),
        esc_html__( 'If your security policy disallows basic credentials, switch this integration to Token Based Auth before going live.', 'advanced-form-integration' ),
        esc_html__( 'Map only field codes that exist on your record type; use JSON override to supply internal IDs or custom fields.' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_netsuite_action_fields' );

function adfoin_netsuite_action_fields() {
    ?>
    <script type="text/template" id="netsuite-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'NetSuite Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_netsuite_credentials_list(); ?>
                    </select>
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

add_action( 'wp_ajax_adfoin_get_netsuite_credentials', 'adfoin_get_netsuite_credentials' );

function adfoin_get_netsuite_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'netsuite' ) );
}

add_action( 'wp_ajax_adfoin_save_netsuite_credentials', 'adfoin_save_netsuite_credentials' );

function adfoin_save_netsuite_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'netsuite' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'netsuite', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_netsuite_fields', 'adfoin_get_netsuite_fields' );

function adfoin_get_netsuite_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'recordType', 'value' => __( 'Record Type (e.g., customer, lead)', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'recordJson', 'value' => __( 'Record JSON (optional)', 'advanced-form-integration' ), 'description' => __( 'Provide full JSON to merge with mapped fields; overrides duplicates.', 'advanced-form-integration' ) ),
        array( 'key' => 'companyName', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => false ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'entityStatus', 'value' => __( 'Entity Status Internal ID', 'advanced-form-integration' ), 'description' => __( 'Required for leads. Example: 13 for “Qualified”.', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_netsuite_job_queue', 'adfoin_netsuite_job_queue', 10, 1 );

function adfoin_netsuite_job_queue( $data ) {
    adfoin_netsuite_send_record( $data['record'], $data['posted_data'] );
}

function adfoin_netsuite_send_record( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $fields = array();
    foreach ( $data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) {
            $fields[ $key ] = $parsed;
        }
    }

    $record_type = isset( $fields['recordType'] ) ? strtolower( $fields['recordType'] ) : '';

    if ( ! $record_type ) {
        return;
    }

    unset( $fields['recordType'] );

    $record_json = array();

    if ( isset( $fields['recordJson'] ) ) {
        $decoded = json_decode( $fields['recordJson'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $record_json = $decoded;
        }
        unset( $fields['recordJson'] );
    }

    foreach ( $fields as $field_code => $value ) {
        if ( 'entityStatus' === $field_code ) {
            $record_json['entityStatus'] = array( 'id' => $value );
            continue;
        }

        $record_json[ $field_code ] = $value;
    }

    $payload = $record_json;

    adfoin_netsuite_request( sprintf( 'services/rest/record/v1/%s', $record_type ), 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_netsuite_request' ) ) :
function adfoin_netsuite_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'netsuite', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'NetSuite credentials not found.', 'advanced-form-integration' ) );
    }

    $account  = isset( $credentials['account'] ) ? $credentials['account'] : '';
    $email    = isset( $credentials['email'] ) ? $credentials['email'] : '';
    $password = isset( $credentials['password'] ) ? $credentials['password'] : '';
    $role     = isset( $credentials['role'] ) ? $credentials['role'] : '';

    if ( ! $account || ! $email || ! $password || ! $role ) {
        return new WP_Error( 'missing_auth', __( 'NetSuite account, email, password, or role are missing.', 'advanced-form-integration' ) );
    }

    $base = ! empty( $credentials['restDomain'] )
        ? untrailingslashit( $credentials['restDomain'] )
        : 'https://' . strtolower( $account ) . '.suitetalk.api.netsuite.com';

    $url = trailingslashit( $base ) . ltrim( $endpoint, '/' );

    $headers = array(
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
        'Authorization' => sprintf(
            'NLAuth nlauth_account=%s, nlauth_email=%s, nlauth_signature=%s, nlauth_role=%s',
            $account,
            $email,
            addslashes( $password ),
            $role
        ),
    );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => $headers,
    );

    if ( in_array( strtoupper( $method ), array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

function adfoin_netsuite_credentials_list() {
    foreach ( adfoin_read_credentials( 'netsuite' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
