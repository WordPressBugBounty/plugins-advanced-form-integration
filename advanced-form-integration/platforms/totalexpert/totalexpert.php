<?php

/**
 * Total Expert — Create Contact via POST /v1/contacts against the Total
 * Expert Public API.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager (service-to-
 * service, no OAuth popup needed). Auth is OAuth2 client_credentials — a
 * client_id/client_secret pair is exchanged (Basic auth) at /v1/token for a
 * short-lived access token, used "As Admin" for every subsequent call.
 * There is no refresh token in this grant; the token is cached in a
 * transient and simply re-fetched on expiry/401, mirroring the Help Scout
 * integration's pattern.
 *
 * The previous version of this file assumed a static "API Key" pasted by
 * the user against a nonexistent `public.totalexpert.com/api/v1` host with
 * a fabricated `X-Company-Id` header — none of that matches Total Expert's
 * real (OAuth2, `.net` domain, no company header) contract, and the
 * `first_name`/`last_name`/etc. field names, while plausible-looking, were
 * unverified guesses. Endpoint, auth flow, and every field name below were
 * pulled directly from Total Expert's own public Postman collection
 * ("Total Expert Public API", collection id 9852f6aa-dc2d-b08c-f8f3-
 * d0c92f09f86c) — see the "Create Contact (as Admin)" request.
 *
 * @link https://developer.totalexpert.net/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'adfoin_action_providers', 'adfoin_totalexpert_actions', 10, 1 );
function adfoin_totalexpert_actions( $actions ) {
    $actions['totalexpert'] = array(
        'title' => __( 'Total Expert', 'advanced-form-integration' ),
        'tasks' => array( 'create_contact' => __( 'Create Contact', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_totalexpert_settings_tab', 10, 1 );
function adfoin_totalexpert_settings_tab( $providers ) {
    $providers['totalexpert'] = __( 'Total Expert', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_totalexpert_settings_view', 10, 1 );
function adfoin_totalexpert_settings_view( $current_tab ) {
    if ( 'totalexpert' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array(
            'name'          => 'client_id',
            'label'         => __( 'Client ID', 'advanced-form-integration' ),
            'type'          => 'text',
            'required'      => true,
            'mask'          => true,
            'show_in_table' => true,
        ),
        array(
            'name'     => 'client_secret',
            'label'    => __( 'Client Secret', 'advanced-form-integration' ),
            'type'     => 'text',
            'required' => true,
            'mask'     => true,
        ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Total Expert Public API access is granted through Total Expert\'s partner program — contact your Total Expert representative to request a Client ID and Client Secret for the client_credentials grant.', 'advanced-form-integration' ),
        esc_html__( 'Paste both below. AFI exchanges them for an access token at https://public.totalexpert.net/v1/token.', 'advanced-form-integration' ),
        esc_html__( 'New contacts are created "as Admin" using this client credential pair.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'totalexpert', __( 'Total Expert', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_totalexpert_credentials', 'adfoin_get_totalexpert_credentials', 10, 0 );
function adfoin_get_totalexpert_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'totalexpert' );
}

add_action( 'wp_ajax_adfoin_save_totalexpert_credentials', 'adfoin_save_totalexpert_credentials', 10, 0 );
function adfoin_save_totalexpert_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'totalexpert', array( 'client_id', 'client_secret' ) );
}

function adfoin_totalexpert_credentials_list() {
    foreach ( adfoin_read_credentials( 'totalexpert' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_totalexpert_action_fields' );
function adfoin_totalexpert_action_fields() {
    ?>
    <script type="text/template" id="totalexpert-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td><div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div></td>
            </tr>
            <tr valign="top" class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title"><label><?php esc_attr_e( 'Total Expert Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId" @change="getFields">
                        <option value=""><?php _e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_contact', 'Total Expert [PRO]', 'contact groups and custom fields' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_totalexpert_fields', 'adfoin_get_totalexpert_fields' );
function adfoin_get_totalexpert_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'firstName',   'value' => 'First Name',   'description' => '' ),
        array( 'key' => 'lastName',    'value' => 'Last Name',    'description' => '' ),
        array( 'key' => 'email',       'value' => 'Email',        'description' => '' ),
        array( 'key' => 'emailWork',   'value' => 'Work Email',   'description' => '' ),
        array( 'key' => 'phoneCell',   'value' => 'Cell Phone',   'description' => '' ),
        array( 'key' => 'phoneHome',   'value' => 'Home Phone',   'description' => '' ),
        array( 'key' => 'phoneOffice', 'value' => 'Office Phone', 'description' => '' ),
        array( 'key' => 'address',     'value' => 'Address',      'description' => '' ),
        array( 'key' => 'city',        'value' => 'City',         'description' => '' ),
        array( 'key' => 'state',       'value' => 'State',        'description' => '' ),
        array( 'key' => 'zipCode',     'value' => 'Zip Code',     'description' => '' ),
        array( 'key' => 'source',      'value' => 'Source',       'description' => '' ),
        array( 'key' => 'externalId',  'value' => 'External ID',  'description' => '' ),
        array( 'key' => 'birthday',    'value' => 'Birthday',     'description' => 'YYYY-MM-DD' ),
    );
    wp_send_json_success( $fields );
}

function adfoin_totalexpert_build_contact_payload( $fields ) {
    $body = array();
    foreach ( array(
        'firstName'   => 'first_name',
        'lastName'    => 'last_name',
        'email'       => 'email',
        'emailWork'   => 'email_work',
        'phoneCell'   => 'phone_cell',
        'phoneHome'   => 'phone_home',
        'phoneOffice' => 'phone_office',
        'address'     => 'address',
        'city'        => 'city',
        'state'       => 'state',
        'zipCode'     => 'zip_code',
        'source'      => 'source',
        'externalId'  => 'external_id',
        'birthday'    => 'birthday',
    ) as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $body[ $remote ] = $fields[ $local ];
    }
    return $body;
}

add_action( 'adfoin_totalexpert_job_queue', 'adfoin_totalexpert_job_queue', 10, 1 );
function adfoin_totalexpert_job_queue( $data ) {
    adfoin_totalexpert_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_totalexpert_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );
    if ( isset( $record_data['action_data']['cl'] ) && adfoin_check_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) return;
    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : $record_data;
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : ( isset( $record['cred_id'] ) ? $record['cred_id'] : '' );

    if ( ! $cred_id ) return;

    $fields = array();
    foreach ( $data as $k => $v ) {
        $parsed = adfoin_get_parsed_values( $v, $posted_data );
        if ( $parsed !== '' && $parsed !== null ) $fields[ $k ] = $parsed;
    }
    if ( $record['task'] !== 'create_contact' ) return;

    adfoin_totalexpert_request( 'contacts', 'POST', adfoin_totalexpert_build_contact_payload( $fields ), $record, $cred_id );
}

if ( ! function_exists( 'adfoin_totalexpert_get_token' ) ) :
/**
 * Fetch (and cache) a Total Expert access token via the client_credentials
 * grant. Docs state tokens are valid ~1 hour; cached for 55 minutes. There
 * is no refresh_token in this grant, so on expiry we simply re-request one.
 */
function adfoin_totalexpert_get_token( $cred_id, $force_refresh = false ) {
    if ( empty( $cred_id ) ) {
        return new WP_Error( 'totalexpert_missing_cred', __( 'Total Expert credential ID is empty.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_totalexpert_token_' . md5( (string) $cred_id );

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( ! empty( $cached ) && is_string( $cached ) ) {
            return $cached;
        }
    }

    $credentials = adfoin_get_credentials_by_id( 'totalexpert', $cred_id );
    if ( ! is_array( $credentials ) || empty( $credentials['client_id'] ) || empty( $credentials['client_secret'] ) ) {
        return new WP_Error( 'totalexpert_missing_credentials', __( 'Total Expert client_id / client_secret not configured.', 'advanced-form-integration' ) );
    }

    $response = wp_remote_post( 'https://public.totalexpert.net/v1/token', array(
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $credentials['client_id'] . ':' . $credentials['client_secret'] ),
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( array( 'grant_type' => 'client_credentials' ) ),
    ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $code = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $code || empty( $body['access_token'] ) ) {
        $msg = isset( $body['error_description'] ) ? $body['error_description'] : ( isset( $body['error'] ) ? $body['error'] : sprintf( 'HTTP %d', $code ) );
        return new WP_Error( 'totalexpert_auth_failed', $msg );
    }

    $token = (string) $body['access_token'];
    set_transient( $cache_key, $token, 55 * MINUTE_IN_SECONDS );

    return $token;
}
endif;

if ( ! function_exists( 'adfoin_totalexpert_request' ) ) :
function adfoin_totalexpert_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $token = adfoin_totalexpert_get_token( $cred_id );

    if ( is_wp_error( $token ) ) {
        if ( $record ) {
            adfoin_add_to_log( $token, 'https://public.totalexpert.net/v1/token', array( 'method' => 'POST' ), $record );
        }
        return $token;
    }

    $response = adfoin_totalexpert_dispatch( $token, $endpoint, $method, $data, $record );

    if ( ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $token = adfoin_totalexpert_get_token( $cred_id, true );
        if ( ! is_wp_error( $token ) ) {
            $response = adfoin_totalexpert_dispatch( $token, $endpoint, $method, $data, $record );
        }
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_totalexpert_dispatch' ) ) :
function adfoin_totalexpert_dispatch( $token, $endpoint, $method, $data, $record ) {
    $url    = 'https://public.totalexpert.net/v1/' . ltrim( $endpoint, '/' );
    $method = strtoupper( $method );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;
