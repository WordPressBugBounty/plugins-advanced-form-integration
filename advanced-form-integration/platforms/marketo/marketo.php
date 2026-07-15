<?php

/**
 * Marketo (Adobe Marketo Engage) — Sync Lead via POST /rest/v1/leads.json.
 *
 * Multi-account credential storage via ADFOIN_Account_Manager. Auth is
 * OAuth2 client_credentials — token cached in a transient, re-fetched on
 * expiry, same pattern as the Help Scout integration in this plugin.
 *
 * Confirmed via experienceleague.adobe.com/en/docs/marketo-developer:
 * token endpoint {munchkin}.mktorest.com/identity/oauth/token, API base
 * {munchkin}.mktorest.com/rest/v1/, leads.json accepts
 * {action, lookupField, input:[{email, firstName, ...}]} and any custom
 * field by its Marketo field API name in the same object.
 *
 * @link https://experienceleague.adobe.com/en/docs/marketo-developer/marketo/rest/lead-database/leads
 */

add_filter( 'adfoin_action_providers', 'adfoin_marketo_actions', 10, 1 );

function adfoin_marketo_actions( $actions ) {
    $actions['marketo'] = array(
        'title' => __( 'Marketo', 'advanced-form-integration' ),
        'tasks' => array( 'sync_lead' => __( 'Create/Update Lead', 'advanced-form-integration' ) ),
    );
    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_marketo_settings_tab', 10, 1 );

function adfoin_marketo_settings_tab( $providers ) {
    $providers['marketo'] = __( 'Marketo', 'advanced-form-integration' );
    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_marketo_settings_view', 10, 1 );

function adfoin_marketo_settings_view( $current_tab ) {
    if ( 'marketo' !== $current_tab ) {
        return;
    }

    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }

    $fields = array(
        array( 'name' => 'munchkinId', 'label' => __( 'Munchkin / REST Endpoint ID', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'show_in_table' => true, 'placeholder' => '123-ABC-456' ),
        array( 'name' => 'clientId', 'label' => __( 'Client ID', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true ),
        array( 'name' => 'clientSecret', 'label' => __( 'Client Secret', 'advanced-form-integration' ), 'type' => 'text', 'required' => true, 'mask' => true ),
    );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In Marketo, go to Admin > Integration > Web Services to find your Munchkin/REST endpoint ID.', 'advanced-form-integration' ),
        esc_html__( 'Create an API-Only user with a Lead read/write role (Admin > Users & Roles), then create a Custom Service under Admin > Integration > LaunchPoint using that user to get a Client ID and Client Secret.', 'advanced-form-integration' ),
        esc_html__( 'Paste all three below.', 'advanced-form-integration' )
    );

    ADFOIN_Account_Manager::render_settings_view( 'marketo', __( 'Marketo', 'advanced-form-integration' ), $fields, $instructions );
}

add_action( 'wp_ajax_adfoin_get_marketo_credentials', 'adfoin_get_marketo_credentials', 10, 0 );

function adfoin_get_marketo_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_get_credentials_list( 'marketo' );
}

add_action( 'wp_ajax_adfoin_save_marketo_credentials', 'adfoin_save_marketo_credentials', 10, 0 );

function adfoin_save_marketo_credentials() {
    if ( ! class_exists( 'ADFOIN_Account_Manager' ) ) {
        require_once plugin_dir_path( __FILE__ ) . '../../includes/class-adfoin-account-manager.php';
    }
    ADFOIN_Account_Manager::ajax_save_credentials( 'marketo', array( 'munchkinId', 'clientId', 'clientSecret' ) );
}

function adfoin_marketo_credentials_list() {
    foreach ( adfoin_read_credentials( 'marketo' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

add_action( 'adfoin_action_fields', 'adfoin_marketo_action_fields' );

function adfoin_marketo_action_fields() {
    ?>
    <script type="text/template" id="marketo-action-template">
        <table class="form-table" v-if="action.task == 'sync_lead'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>
            <tr class="alternate">
                <td scope="row-title"><label><?php esc_html_e( 'Marketo Account', 'advanced-form-integration' ); ?></label></td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <option v-for="cred in credentialsList" :value="cred.id">{{ cred.title }}</option>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=marketo' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>
            <editable-field v-for="field in fields" v-bind:key="field.value" v-bind:field="field" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'sync_lead', 'Marketo [PRO]', 'custom fields & campaign trigger' ); ?>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_marketo_fields', 'adfoin_get_marketo_fields' );

function adfoin_get_marketo_fields() {
    adfoin_verify_nonce();
    $fields = array(
        array( 'key' => 'email',     'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',  'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'company',   'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',     'value' => __( 'Phone', 'advanced-form-integration' ) ),
    );
    wp_send_json_success( $fields );
}

function adfoin_marketo_build_lead( $fields ) {
    $map = array( 'email' => 'email', 'firstName' => 'firstName', 'lastName' => 'lastName', 'company' => 'company', 'phone' => 'phone' );
    $lead = array();
    foreach ( $map as $local => $remote ) {
        if ( ! empty( $fields[ $local ] ) ) $lead[ $remote ] = $fields[ $local ];
    }
    return $lead;
}

add_action( 'adfoin_marketo_job_queue', 'adfoin_marketo_job_queue', 10, 1 );
function adfoin_marketo_job_queue( $data ) {
    adfoin_marketo_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_marketo_send_data( $record, $posted_data ) {
    if ( 'sync_lead' !== ( $record['task'] ?? '' ) ) {
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

    $fields = array();
    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) continue;
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' !== $parsed && null !== $parsed ) $fields[ $key ] = $parsed;
    }

    if ( empty( $fields['email'] ) ) {
        return;
    }

    $body = array(
        'action'      => 'createOrUpdate',
        'lookupField' => 'email',
        'input'       => array( adfoin_marketo_build_lead( $fields ) ),
    );

    adfoin_marketo_request( 'leads.json', 'POST', $body, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_marketo_get_token' ) ) :
function adfoin_marketo_get_token( $cred_id, $force_refresh = false ) {
    if ( empty( $cred_id ) ) {
        return new WP_Error( 'marketo_missing_cred', __( 'Marketo credential ID is empty.', 'advanced-form-integration' ) );
    }

    $cache_key = 'adfoin_marketo_token_' . md5( (string) $cred_id );

    if ( ! $force_refresh ) {
        $cached = get_transient( $cache_key );
        if ( ! empty( $cached ) && is_string( $cached ) ) return $cached;
    }

    $credentials = adfoin_get_credentials_by_id( 'marketo', $cred_id );
    if ( ! is_array( $credentials ) || empty( $credentials['munchkinId'] ) || empty( $credentials['clientId'] ) || empty( $credentials['clientSecret'] ) ) {
        return new WP_Error( 'marketo_missing_credentials', __( 'Marketo credentials not configured.', 'advanced-form-integration' ) );
    }

    $endpoint = 'https://' . $credentials['munchkinId'] . '.mktorest.com/identity/oauth/token';
    $response = wp_remote_get( add_query_arg( array(
        'grant_type'    => 'client_credentials',
        'client_id'     => $credentials['clientId'],
        'client_secret' => $credentials['clientSecret'],
    ), $endpoint ), array( 'timeout' => 30 ) );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $code = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $code || empty( $body['access_token'] ) ) {
        $msg = $body['error_description'] ?? sprintf( 'HTTP %d', $code );
        return new WP_Error( 'marketo_auth_failed', $msg );
    }

    $token   = (string) $body['access_token'];
    $expires = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
    set_transient( $cache_key, $token, max( 60, $expires - 60 ) );

    return $token;
}
endif;

if ( ! function_exists( 'adfoin_marketo_request' ) ) :
function adfoin_marketo_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $token = adfoin_marketo_get_token( $cred_id );
    if ( is_wp_error( $token ) ) {
        if ( $record ) adfoin_add_to_log( $token, '', array(), $record );
        return $token;
    }

    $response = adfoin_marketo_dispatch( $token, $endpoint, $method, $data, $cred_id, $record );

    if ( ! is_wp_error( $response ) ) {
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $needs_refresh = 401 === (int) wp_remote_retrieve_response_code( $response )
            || ( isset( $body['success'] ) && ! $body['success'] && ! empty( $body['errors'][0]['code'] ) && in_array( $body['errors'][0]['code'], array( '601', '602' ), true ) );
        if ( $needs_refresh ) {
            $token = adfoin_marketo_get_token( $cred_id, true );
            if ( ! is_wp_error( $token ) ) {
                $response = adfoin_marketo_dispatch( $token, $endpoint, $method, $data, $cred_id, $record );
            }
        }
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_marketo_dispatch' ) ) :
function adfoin_marketo_dispatch( $token, $endpoint, $method, $data, $cred_id, $record ) {
    $credentials = adfoin_get_credentials_by_id( 'marketo', $cred_id );
    $url = 'https://' . $credentials['munchkinId'] . '.mktorest.com/rest/v1/' . ltrim( $endpoint, '/' );
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
