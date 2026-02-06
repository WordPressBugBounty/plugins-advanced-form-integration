<?php

add_filter( 'adfoin_action_providers', 'adfoin_sapsalescloud_actions', 10, 1 );

function adfoin_sapsalescloud_actions( $actions ) {

    $actions['sapsalescloud'] = array(
        'title' => __( 'SAP Sales Cloud', 'advanced-form-integration' ),
        'tasks' => array(
            'create_lead' => __( 'Create Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sapsalescloud_settings_tab', 10, 1 );

function adfoin_sapsalescloud_settings_tab( $providers ) {
    $providers['sapsalescloud'] = __( 'SAP Sales Cloud', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sapsalescloud_settings_view', 10, 1 );

function adfoin_sapsalescloud_settings_view( $current_tab ) {
    if ( 'sapsalescloud' !== $current_tab ) {
        return;
    }

    $nonce         = wp_create_nonce( 'adfoin_sapsalescloud_settings' );
    $base_url      = get_option( 'adfoin_sapsalescloud_base_url', '' );
    $oauth_url     = get_option( 'adfoin_sapsalescloud_oauth_url', '' );
    $client_id     = get_option( 'adfoin_sapsalescloud_client_id', '' );
    $client_secret = get_option( 'adfoin_sapsalescloud_client_secret', '' );
    ?>

    <form name="sapsalescloud_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_sapsalescloud_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'API Base URL', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sapsalescloud_base_url"
                           value="<?php echo esc_attr( $base_url ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. https://mytenant.crm.ondemand.com/sap/c4c/odata/v1/c4codataapi', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description"><?php esc_html_e( 'Use the c4codataapi service root without trailing slash.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'OAuth Token URL', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sapsalescloud_oauth_url"
                           value="<?php echo esc_attr( $oauth_url ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. https://mytenant.authentication.eu10.hana.ondemand.com/oauth/token', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description"><?php esc_html_e( 'Client credentials flow endpoint from the SAP BTP subaccount (usually ends with /oauth/token).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Client ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sapsalescloud_client_id"
                           value="<?php echo esc_attr( $client_id ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Client Secret', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="password" name="adfoin_sapsalescloud_client_secret"
                           value="<?php echo esc_attr( $client_secret ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_sapsalescloud_credentials', 'adfoin_save_sapsalescloud_credentials', 10, 0 );

function adfoin_save_sapsalescloud_credentials() {
    if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_sapsalescloud_settings' ) ) {
        wp_die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $base_url      = isset( $_POST['adfoin_sapsalescloud_base_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['adfoin_sapsalescloud_base_url'] ) ) ) : '';
    $oauth_url     = isset( $_POST['adfoin_sapsalescloud_oauth_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['adfoin_sapsalescloud_oauth_url'] ) ) ) : '';
    $client_id     = isset( $_POST['adfoin_sapsalescloud_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sapsalescloud_client_id'] ) ) : '';
    $client_secret = isset( $_POST['adfoin_sapsalescloud_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sapsalescloud_client_secret'] ) ) : '';

    update_option( 'adfoin_sapsalescloud_base_url', untrailingslashit( $base_url ) );
    update_option( 'adfoin_sapsalescloud_oauth_url', $oauth_url );
    update_option( 'adfoin_sapsalescloud_client_id', $client_id );
    update_option( 'adfoin_sapsalescloud_client_secret', $client_secret );

    delete_transient( 'adfoin_sapsalescloud_access_token' );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=sapsalescloud' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_sapsalescloud_js_fields', 10, 1 );

function adfoin_sapsalescloud_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_sapsalescloud_action_fields' );

function adfoin_sapsalescloud_action_fields() {
    ?>
    <script type="text/template" id="sapsalescloud-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_lead'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row"></td>
            </tr>
            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata">
            </editable-field>
        </table>
    </script>
    <?php
}

add_action( 'adfoin_sapsalescloud_job_queue', 'adfoin_sapsalescloud_job_queue', 10, 1 );

function adfoin_sapsalescloud_job_queue( $data ) {
    adfoin_sapsalescloud_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sapsalescloud_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === ( $record_data['action_data']['cl']['active'] ?? '' ) ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $data       = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $lead_name  = empty( $data['leadName'] ) ? '' : adfoin_get_parsed_values( $data['leadName'], $posted_data );
    $company    = empty( $data['company'] ) ? '' : adfoin_get_parsed_values( $data['company'], $posted_data );
    $first_name = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
    $last_name  = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
    $email      = empty( $data['email'] ) ? '' : adfoin_get_parsed_values( $data['email'], $posted_data );
    $phone      = empty( $data['phone'] ) ? '' : adfoin_get_parsed_values( $data['phone'], $posted_data );
    $origin     = empty( $data['originCode'] ) ? '001' : adfoin_get_parsed_values( $data['originCode'], $posted_data );

    if ( empty( $lead_name ) && $first_name && $last_name ) {
        $lead_name = trim( $first_name . ' ' . $last_name );
    }

    if ( empty( $lead_name ) && $company ) {
        $lead_name = sprintf( __( 'Lead for %s', 'advanced-form-integration' ), $company );
    }

    if ( empty( $lead_name ) ) {
        $lead_name = __( 'Website Lead', 'advanced-form-integration' );
    }

    $payload = array(
        'Name'                 => $lead_name,
        'Company'              => $company,
        'ContactFirstName'     => $first_name,
        'ContactLastName'      => $last_name,
        'ContactEmail'         => $email,
        'ContactPhone'         => $phone,
        'OriginTypeCode'       => $origin,
        'UserStatusCode'       => '1',
    );

    $headers = adfoin_sapsalescloud_headers();

    if ( empty( $headers['Authorization'] ) ) {
        return;
    }

    $endpoint = adfoin_sapsalescloud_build_url( '/LeadCollection' );

    if ( empty( $endpoint ) ) {
        return;
    }

    $args = array(
        'headers' => $headers,
        'timeout' => 30,
        'body'    => wp_json_encode( $payload ),
        'method'  => 'POST',
    );

    $response = wp_remote_post( $endpoint, $args );

    adfoin_add_to_log( $response, $endpoint, $args, $record );
}

function adfoin_sapsalescloud_headers() {
    $token = adfoin_sapsalescloud_get_token();

    return array(
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
        'Authorization' => $token ? 'Bearer ' . $token : '',
    );
}

function adfoin_sapsalescloud_get_token( $force_refresh = false ) {
    $cached = get_transient( 'adfoin_sapsalescloud_access_token' );

    if ( ! $force_refresh && $cached ) {
        return $cached;
    }

    $oauth_url     = get_option( 'adfoin_sapsalescloud_oauth_url', '' );
    $client_id     = get_option( 'adfoin_sapsalescloud_client_id', '' );
    $client_secret = get_option( 'adfoin_sapsalescloud_client_secret', '' );

    if ( ! $oauth_url || ! $client_id || ! $client_secret ) {
        return '';
    }

    $body = array(
        'grant_type'    => 'client_credentials',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
    );

    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body'    => http_build_query( $body, '', '&' ),
    );

    $response = wp_remote_post( $oauth_url, $args );

    if ( is_wp_error( $response ) ) {
        return '';
    }

    $code = wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code || empty( $data['access_token'] ) ) {
        return '';
    }

    $token   = sanitize_text_field( $data['access_token'] );
    $expires = isset( $data['expires_in'] ) ? absint( $data['expires_in'] ) : 3500;

    set_transient( 'adfoin_sapsalescloud_access_token', $token, $expires - 60 );

    return $token;
}

function adfoin_sapsalescloud_build_url( $path ) {
    $base_url = get_option( 'adfoin_sapsalescloud_base_url', '' );
    $path     = ltrim( $path, '/' );

    if ( empty( $base_url ) ) {
        return '';
    }

    return trailingslashit( $base_url ) . $path;
}
