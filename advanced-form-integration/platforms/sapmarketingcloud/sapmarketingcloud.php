<?php

add_filter( 'adfoin_action_providers', 'adfoin_sapmarketingcloud_actions', 10, 1 );

function adfoin_sapmarketingcloud_actions( $actions ) {

    $actions['sapmarketingcloud'] = array(
        'title' => __( 'SAP Marketing Cloud', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create or Update Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_sapmarketingcloud_settings_tab', 10, 1 );

function adfoin_sapmarketingcloud_settings_tab( $providers ) {
    $providers['sapmarketingcloud'] = __( 'SAP Marketing Cloud', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_sapmarketingcloud_settings_view', 10, 1 );

function adfoin_sapmarketingcloud_settings_view( $current_tab ) {
    if ( $current_tab !== 'sapmarketingcloud' ) {
        return;
    }

    $nonce         = wp_create_nonce( 'adfoin_sapmarketingcloud_settings' );
    $base_url      = get_option( 'adfoin_sapmarketingcloud_base_url', '' );
    $oauth_url     = get_option( 'adfoin_sapmarketingcloud_oauth_url', '' );
    $client_id     = get_option( 'adfoin_sapmarketingcloud_client_id', '' );
    $client_secret = get_option( 'adfoin_sapmarketingcloud_client_secret', '' );
    ?>

    <form name="sapmarketingcloud_save_form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
          method="post" class="container">

        <input type="hidden" name="action" value="adfoin_save_sapmarketingcloud_credentials">
        <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>"/>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'API Base URL', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sapmarketingcloud_base_url"
                           value="<?php echo esc_attr( $base_url ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. https://mytenant.marketing.cloud.sap/sap/opu/odata/sap/API_MKT_CONTACT_SRV', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description"><?php esc_html_e( 'Use the service root for the Marketing Cloud OData API (no trailing slash).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'OAuth Token URL', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sapmarketingcloud_oauth_url"
                           value="<?php echo esc_attr( $oauth_url ); ?>"
                           placeholder="<?php esc_attr_e( 'e.g. https://mytenant.authentication.eu10.hana.ondemand.com/oauth/token', 'advanced-form-integration' ); ?>"
                           class="regular-text"/>
                    <p class="description"><?php esc_html_e( 'Client credentials flow endpoint from the SAP BTP subaccount (usually ends with /oauth/token).', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Client ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="text" name="adfoin_sapmarketingcloud_client_id"
                           value="<?php echo esc_attr( $client_id ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Client Secret', 'advanced-form-integration' ); ?></th>
                <td>
                    <input type="password" name="adfoin_sapmarketingcloud_client_secret"
                           value="<?php echo esc_attr( $client_secret ); ?>"
                           class="regular-text"/>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    <?php
}

add_action( 'admin_post_adfoin_save_sapmarketingcloud_credentials', 'adfoin_save_sapmarketingcloud_credentials', 10, 0 );

function adfoin_save_sapmarketingcloud_credentials() {
    if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'adfoin_sapmarketingcloud_settings' ) ) {
        wp_die( __( 'Security check Failed', 'advanced-form-integration' ) );
    }

    $base_url      = isset( $_POST['adfoin_sapmarketingcloud_base_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['adfoin_sapmarketingcloud_base_url'] ) ) ) : '';
    $oauth_url     = isset( $_POST['adfoin_sapmarketingcloud_oauth_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['adfoin_sapmarketingcloud_oauth_url'] ) ) ) : '';
    $client_id     = isset( $_POST['adfoin_sapmarketingcloud_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sapmarketingcloud_client_id'] ) ) : '';
    $client_secret = isset( $_POST['adfoin_sapmarketingcloud_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['adfoin_sapmarketingcloud_client_secret'] ) ) : '';

    update_option( 'adfoin_sapmarketingcloud_base_url', untrailingslashit( $base_url ) );
    update_option( 'adfoin_sapmarketingcloud_oauth_url', $oauth_url );
    update_option( 'adfoin_sapmarketingcloud_client_id', $client_id );
    update_option( 'adfoin_sapmarketingcloud_client_secret', $client_secret );

    delete_transient( 'adfoin_sapmarketingcloud_access_token' );

    advanced_form_integration_redirect( 'admin.php?page=advanced-form-integration-settings&tab=sapmarketingcloud' );
}

add_action( 'adfoin_add_js_fields', 'adfoin_sapmarketingcloud_js_fields', 10, 1 );

function adfoin_sapmarketingcloud_js_fields( $field_data ) {}

add_action( 'adfoin_action_fields', 'adfoin_sapmarketingcloud_action_fields' );

function adfoin_sapmarketingcloud_action_fields() {
    ?>
    <script type="text/template" id="sapmarketingcloud-action-template">
        <table class="form-table">
            <tr valign="top" v-if="action.task == 'create_contact'">
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

add_action( 'adfoin_sapmarketingcloud_job_queue', 'adfoin_sapmarketingcloud_job_queue', 10, 1 );

function adfoin_sapmarketingcloud_job_queue( $data ) {
    adfoin_sapmarketingcloud_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_sapmarketingcloud_send_data( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( isset( $record_data['action_data']['cl'] ) && 'yes' === $record_data['action_data']['cl']['active'] ) {
        if ( ! adfoin_match_conditional_logic( $record_data['action_data']['cl'], $posted_data ) ) {
            return;
        }
    }

    $data          = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $email         = empty( $data['email'] ) ? '' : trim( adfoin_get_parsed_values( $data['email'], $posted_data ) );
    $first_name    = empty( $data['firstName'] ) ? '' : adfoin_get_parsed_values( $data['firstName'], $posted_data );
    $last_name     = empty( $data['lastName'] ) ? '' : adfoin_get_parsed_values( $data['lastName'], $posted_data );
    $origin        = empty( $data['origin'] ) ? 'WEB_FORM' : adfoin_get_parsed_values( $data['origin'], $posted_data );
    $external_id   = empty( $data['externalId'] ) ? $email : adfoin_get_parsed_values( $data['externalId'], $posted_data );
    $country       = empty( $data['country'] ) ? '' : adfoin_get_parsed_values( $data['country'], $posted_data );
    $accepts_email = ! empty( $data['emailPermission'] ) ? filter_var( adfoin_get_parsed_values( $data['emailPermission'], $posted_data ), FILTER_VALIDATE_BOOLEAN ) : true;

    if ( empty( $email ) ) {
        return;
    }

    $payload = array(
        'InteractionContactOrigin' => $origin,
        'InteractionContactId'     => $external_id,
        'EmailAddress'             => $email,
        'IsEmailValid'             => true,
        'CommunicationMedium'      => 'EMAIL',
        'HasEmailOptIn'            => (bool) $accepts_email,
    );

    if ( $first_name ) {
        $payload['FirstName'] = $first_name;
    }

    if ( $last_name ) {
        $payload['LastName'] = $last_name;
    }

    if ( $country ) {
        $payload['Country'] = $country;
    }

    $headers = adfoin_sapmarketingcloud_headers();

    if ( empty( $headers['Authorization'] ) ) {
        return;
    }

    $endpoint         = adfoin_sapmarketingcloud_build_url( '/InteractionContactCollection' );
    $current_endpoint = $endpoint;

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

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) {
        // Try to update existing contact if conflict detected.
        $response_code = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );

        if ( 409 === $response_code || 412 === $response_code ) {
            $update_endpoint = adfoin_sapmarketingcloud_build_url(
                sprintf(
                    "/InteractionContactCollection(InteractionContactOrigin='%s',InteractionContactId='%s')",
                    rawurlencode( $origin ),
                    rawurlencode( $external_id )
                )
            );

            $args['method'] = 'PATCH';
            $current_endpoint = $update_endpoint;
            $response         = wp_remote_request( $update_endpoint, $args );
        }
    }

    adfoin_add_to_log( $response, $current_endpoint, $args, $record );
}

function adfoin_sapmarketingcloud_headers() {
    $token = adfoin_sapmarketingcloud_get_token();

    return array(
        'Content-Type'  => 'application/json',
        'Accept'        => 'application/json',
        'Authorization' => $token ? 'Bearer ' . $token : '',
    );
}

function adfoin_sapmarketingcloud_get_token( $force_refresh = false ) {
    $cached = get_transient( 'adfoin_sapmarketingcloud_access_token' );

    if ( ! $force_refresh && $cached ) {
        return $cached;
    }

    $oauth_url     = get_option( 'adfoin_sapmarketingcloud_oauth_url', '' );
    $client_id     = get_option( 'adfoin_sapmarketingcloud_client_id', '' );
    $client_secret = get_option( 'adfoin_sapmarketingcloud_client_secret', '' );

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

    set_transient( 'adfoin_sapmarketingcloud_access_token', $token, $expires - 60 );

    return $token;
}

function adfoin_sapmarketingcloud_build_url( $path ) {
    $base_url = get_option( 'adfoin_sapmarketingcloud_base_url', '' );
    $path     = ltrim( $path, '/' );

    if ( empty( $base_url ) ) {
        return '';
    }

    return trailingslashit( $base_url ) . $path;
}
