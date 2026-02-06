<?php

add_filter( 'adfoin_action_providers', 'adfoin_adobeconnect_actions', 10, 1 );

function adfoin_adobeconnect_actions( $actions ) {
    $actions['adobeconnect'] = array(
        'title' => __( 'Adobe Connect', 'advanced-form-integration' ),
        'tasks' => array(
            'create_user' => __( 'Create / Update User', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_adobeconnect_settings_tab', 10, 1 );

function adfoin_adobeconnect_settings_tab( $providers ) {
    $providers['adobeconnect'] = __( 'Adobe Connect', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_adobeconnect_settings_view', 10, 1 );

function adfoin_adobeconnect_settings_view( $current_tab ) {
    if ( 'adobeconnect' !== $current_tab ) {
        return;
    }

    $title = __( 'Adobe Connect', 'advanced-form-integration' );
    $key   = 'adobeconnect';

    $arguments = wp_json_encode(
        array(
            'platform' => $key,
            'fields'   => array(
                array(
                    'key'         => 'title',
                    'label'       => __( 'Credential Label', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => __( 'Production Adobe Connect', 'advanced-form-integration' ),
                ),
                array(
                    'key'         => 'baseUrl',
                    'label'       => __( 'Account URL', 'advanced-form-integration' ),
                    'hidden'      => false,
                    'placeholder' => 'https://example.adobeconnect.com',
                ),
                array(
                    'key'    => 'username',
                    'label'  => __( 'Login Username', 'advanced-form-integration' ),
                    'hidden' => false,
                ),
                array(
                    'key'    => 'password',
                    'label'  => __( 'Login Password', 'advanced-form-integration' ),
                    'hidden' => true,
                ),
            ),
        )
    );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
            <li>%4$s</li>
        </ol>
        <p>%5$s</p>',
        esc_html__( 'Sign in to the Adobe Connect account that hosts your meetings or training rooms.', 'advanced-form-integration' ),
        esc_html__( 'Ensure the administrator user has permissions to create users and manage group membership.', 'advanced-form-integration' ),
        esc_html__( 'Enter the Adobe Connect URL (for example https://example.adobeconnect.com) and the login credentials, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Use the saved credential when creating an action so AFI can authenticate, create the user, and optionally add them to groups.', 'advanced-form-integration' ),
        esc_html__( 'If your account uses SSO, create a dedicated local Adobe Connect user for API access.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_adobeconnect_action_fields' );

function adfoin_adobeconnect_action_fields() {
    ?>
    <script type="text/template" id="adobeconnect-action-template">
        <table class="form-table" v-if="action.task == 'create_user'">
            <tr>
                <th scope="row"><?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Adobe Connect Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_adobeconnect_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Notes', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php esc_html_e( 'Provide the login and email to create or update a user. Include group IDs to enrol the user into seminars, meetings, or learning plans.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_adobeconnect_credentials', 'adfoin_get_adobeconnect_credentials' );

function adfoin_get_adobeconnect_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'adobeconnect' ) );
}

add_action( 'wp_ajax_adfoin_save_adobeconnect_credentials', 'adfoin_save_adobeconnect_credentials' );

function adfoin_save_adobeconnect_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'adobeconnect' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'adobeconnect', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_adobeconnect_fields', 'adfoin_get_adobeconnect_fields' );

function adfoin_get_adobeconnect_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array(
            'key'      => 'login',
            'value'    => __( 'Login (username) *', 'advanced-form-integration' ),
            'required' => true,
        ),
        array(
            'key'      => 'email',
            'value'    => __( 'Email Address *', 'advanced-form-integration' ),
            'required' => true,
        ),
        array(
            'key'   => 'first_name',
            'value' => __( 'First Name', 'advanced-form-integration' ),
        ),
        array(
            'key'   => 'last_name',
            'value' => __( 'Last Name', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'password',
            'value'      => __( 'Password (required for new users)', 'advanced-form-integration' ),
            'description'=> __( 'Leave blank to keep the existing password when updating.', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'send_email',
            'value'      => __( 'Send Welcome Email (true/false)', 'advanced-form-integration' ),
            'description'=> __( 'Defaults to false. Use true to have Adobe Connect email the user.', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'type',
            'value'      => __( 'Principal Type', 'advanced-form-integration' ),
            'description'=> __( 'Examples: user, admin, host. Defaults to user.', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'group_ids',
            'value'      => __( 'Group IDs (comma separated)', 'advanced-form-integration' ),
            'description'=> __( 'Supply one or more group IDs (seminars, meetings, or training catalogs) to enrol the user.', 'advanced-form-integration' ),
        ),
        array(
            'key'        => 'extra_fields_json',
            'value'      => __( 'Extra Fields (JSON)', 'advanced-form-integration' ),
            'type'       => 'textarea',
            'description'=> __( 'Key/value pairs for additional principal-update parameters. Example: {"middle-name":"Alan","locale":"en"}', 'advanced-form-integration' ),
        ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_adobeconnect_job_queue', 'adfoin_adobeconnect_job_queue_handler', 10, 1 );

function adfoin_adobeconnect_job_queue_handler( $data ) {
    adfoin_adobeconnect_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_adobeconnect_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_adobeconnect_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        adfoin_add_to_log( $credentials, '', array(), $record );
        return;
    }

    $payload = adfoin_adobeconnect_collect_payload( $field_data, $posted_data );

    if ( is_wp_error( $payload ) ) {
        adfoin_add_to_log( $payload, '', array(), $record );
        return;
    }

    $auth = adfoin_adobeconnect_login( $credentials, $record );

    if ( is_wp_error( $auth ) ) {
        adfoin_add_to_log( $auth, '', array(), $record );
        return;
    }

    $response = adfoin_adobeconnect_post( $auth, $payload['request'], $record );

    if ( is_wp_error( $response ) ) {
        adfoin_add_to_log( $response, '', array(), $record );
        return;
    }

    $xml = adfoin_adobeconnect_parse_xml( $response );

    if ( is_wp_error( $xml ) ) {
        adfoin_add_to_log( $xml, '', array(), $record );
        return;
    }

    $status = adfoin_adobeconnect_validate_status( $xml, 'principal-update' );

    if ( is_wp_error( $status ) ) {
        adfoin_add_to_log( $status, '', array(), $record );
        return;
    }

    $principal_id = adfoin_adobeconnect_extract_principal_id( $xml, $payload['login'], $auth, $record );

    if ( is_wp_error( $principal_id ) ) {
        adfoin_add_to_log( $principal_id, '', array(), $record );
        return;
    }

    if ( empty( $payload['groups'] ) ) {
        return;
    }

    foreach ( $payload['groups'] as $group_id ) {
        $membership_request = array(
            'action'       => 'group-membership-update',
            'group-id'     => $group_id,
            'principal-id' => $principal_id,
            'is-member'    => 'true',
        );

        $membership_response = adfoin_adobeconnect_post( $auth, $membership_request, $record );

        if ( is_wp_error( $membership_response ) ) {
            adfoin_add_to_log( $membership_response, '', array(), $record );
            continue;
        }

        $membership_xml = adfoin_adobeconnect_parse_xml( $membership_response );

        if ( is_wp_error( $membership_xml ) ) {
            adfoin_add_to_log( $membership_xml, '', array(), $record );
            continue;
        }

        $membership_status = adfoin_adobeconnect_validate_status( $membership_xml, 'group-membership-update' );

        if ( is_wp_error( $membership_status ) ) {
            adfoin_add_to_log( $membership_status, '', array(), $record );
        }
    }
}

function adfoin_adobeconnect_collect_payload( $field_data, $posted_data ) {
    $login = adfoin_adobeconnect_parse_value( $field_data, 'login', $posted_data );
    $email = adfoin_adobeconnect_parse_value( $field_data, 'email', $posted_data );

    if ( '' === $login ) {
        return new WP_Error( 'adobeconnect_missing_login', __( 'Adobe Connect requires a login (username).', 'advanced-form-integration' ) );
    }

    if ( '' === $email ) {
        return new WP_Error( 'adobeconnect_missing_email', __( 'Adobe Connect requires an email address.', 'advanced-form-integration' ) );
    }

    $request = array(
        'action'       => 'principal-update',
        'login'        => $login,
        'email'        => $email,
        'type'         => adfoin_adobeconnect_parse_value( $field_data, 'type', $posted_data ) ?: 'user',
        'has-children' => 'false',
    );

    $first_name = adfoin_adobeconnect_parse_value( $field_data, 'first_name', $posted_data );

    if ( '' !== $first_name ) {
        $request['first-name'] = $first_name;
    }

    $last_name = adfoin_adobeconnect_parse_value( $field_data, 'last_name', $posted_data );

    if ( '' !== $last_name ) {
        $request['last-name'] = $last_name;
    }

    $password = adfoin_adobeconnect_parse_value( $field_data, 'password', $posted_data );

    if ( '' !== $password ) {
        $request['password'] = $password;
    }

    $send_email = adfoin_adobeconnect_parse_value( $field_data, 'send_email', $posted_data );

    if ( '' !== $send_email ) {
        $request['send-email'] = adfoin_adobeconnect_normalize_boolean( $send_email );
    }

    $extra = adfoin_adobeconnect_parse_value( $field_data, 'extra_fields_json', $posted_data );

    if ( '' !== $extra ) {
        $decoded = json_decode( $extra, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'adobeconnect_invalid_extra_fields', __( 'Adobe Connect extra fields JSON is invalid.', 'advanced-form-integration' ) );
        }

        foreach ( $decoded as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $request[ $key ] = (string) $value;
            }
        }
    }

    $groups_raw = adfoin_adobeconnect_parse_value( $field_data, 'group_ids', $posted_data );
    $groups     = array();

    if ( '' !== $groups_raw ) {
        $groups = array_filter( array_map( 'trim', explode( ',', $groups_raw ) ) );
    }

    return array(
        'request' => $request,
        'groups'  => $groups,
        'login'   => $login,
    );
}

function adfoin_adobeconnect_parse_value( $field_data, $key, $posted_data ) {
    if ( ! isset( $field_data[ $key ] ) ) {
        return '';
    }

    $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );

    if ( is_array( $value ) ) {
        return '';
    }

    return is_string( $value ) ? trim( $value ) : '';
}

function adfoin_adobeconnect_normalize_boolean( $value ) {
    $value = strtolower( trim( (string) $value ) );

    $truthy = array( '1', 'true', 'yes', 'on' );

    return in_array( $value, $truthy, true ) ? 'true' : 'false';
}

function adfoin_adobeconnect_credentials_list() {
    $credentials = adfoin_read_credentials( 'adobeconnect' );

    foreach ( $credentials as $option ) {
        printf(
            '<option value="%s">%s</option>',
            esc_attr( $option['id'] ),
            esc_html( $option['title'] )
        );
    }
}

function adfoin_adobeconnect_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'adobeconnect', $cred_id );

    if ( empty( $credentials ) ) {
        return new WP_Error( 'adobeconnect_missing_credentials', __( 'Adobe Connect credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

function adfoin_adobeconnect_login( $credentials, $record ) {
    $base_url = isset( $credentials['baseUrl'] ) ? trim( $credentials['baseUrl'] ) : '';
    $username = isset( $credentials['username'] ) ? trim( $credentials['username'] ) : '';
    $password = isset( $credentials['password'] ) ? trim( $credentials['password'] ) : '';

    if ( '' === $base_url || '' === $username || '' === $password ) {
        return new WP_Error( 'adobeconnect_incomplete_credentials', __( 'Adobe Connect base URL, username, and password are required.', 'advanced-form-integration' ) );
    }

    $base = rtrim( $base_url, '/' );
    $url  = $base . '/api/xml';

    $args = array(
        'timeout' => 30,
        'body'    => array(
            'action'   => 'login',
            'login'    => $username,
            'password' => $password,
        ),
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
    );

    $response = wp_remote_post( esc_url_raw( $url ), $args );

    adfoin_add_to_log( $response, $url, $args, $record );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $xml = adfoin_adobeconnect_parse_xml( $response );

    if ( is_wp_error( $xml ) ) {
        return $xml;
    }

    $status = adfoin_adobeconnect_validate_status( $xml, 'login' );

    if ( is_wp_error( $status ) ) {
        return $status;
    }

    $cookies = wp_remote_retrieve_cookies( $response );

    if ( empty( $cookies ) ) {
        return new WP_Error( 'adobeconnect_missing_session', __( 'Adobe Connect login succeeded but no session cookie was returned.', 'advanced-form-integration' ) );
    }

    return array(
        'base'    => $base,
        'cookies' => $cookies,
    );
}

function adfoin_adobeconnect_post( $auth, $params, $record ) {
    $url = $auth['base'] . '/api/xml';

    $args = array(
        'timeout' => 30,
        'body'    => $params,
        'cookies' => $auth['cookies'],
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
    );

    $response = wp_remote_post( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    return $response;
}

function adfoin_adobeconnect_parse_xml( $response ) {
    $body = wp_remote_retrieve_body( $response );

    if ( '' === $body ) {
        return new WP_Error( 'adobeconnect_empty_response', __( 'Adobe Connect returned an empty response.', 'advanced-form-integration' ) );
    }

    libxml_use_internal_errors( true );

    $xml = simplexml_load_string( $body );

    if ( false === $xml ) {
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return new WP_Error( 'adobeconnect_invalid_xml', __( 'Adobe Connect response could not be parsed.', 'advanced-form-integration' ), array( 'body' => $body, 'errors' => $errors ) );
    }

    libxml_clear_errors();

    return $xml;
}

function adfoin_adobeconnect_validate_status( $xml, $context ) {
    if ( ! isset( $xml->status ) ) {
        return new WP_Error( 'adobeconnect_missing_status', sprintf( __( 'Adobe Connect response for %s is missing a status node.', 'advanced-form-integration' ), $context ) );
    }

    $code    = strtolower( (string) $xml->status['code'] );
    $subcode = (string) $xml->status['subcode'];

    if ( 'ok' === $code ) {
        return true;
    }

    $message = $subcode
        ? sprintf( __( 'Adobe Connect %1$s request failed: %2$s (%3$s).', 'advanced-form-integration' ), $context, $code, $subcode )
        : sprintf( __( 'Adobe Connect %1$s request failed: %2$s.', 'advanced-form-integration' ), $context, $code );

    return new WP_Error( 'adobeconnect_api_error', $message );
}

function adfoin_adobeconnect_extract_principal_id( $xml, $login, $auth, $record ) {
    if ( isset( $xml->principal ) && isset( $xml->principal['principal-id'] ) ) {
        return (string) $xml->principal['principal-id'];
    }

    return adfoin_adobeconnect_fetch_principal_id( $auth, $login, $record );
}

function adfoin_adobeconnect_fetch_principal_id( $auth, $login, $record ) {
    if ( '' === $login ) {
        return new WP_Error( 'adobeconnect_missing_principal', __( 'Unable to determine the Adobe Connect principal ID.', 'advanced-form-integration' ) );
    }

    $params = array(
        'action'       => 'principal-list',
        'filter-login' => $login,
    );

    $response = adfoin_adobeconnect_post( $auth, $params, $record );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $xml = adfoin_adobeconnect_parse_xml( $response );

    if ( is_wp_error( $xml ) ) {
        return $xml;
    }

    $status = adfoin_adobeconnect_validate_status( $xml, 'principal-list' );

    if ( is_wp_error( $status ) ) {
        return $status;
    }

    if ( isset( $xml->principal ) ) {
        foreach ( $xml->principal as $principal ) {
            if ( (string) $principal['login'] === $login && isset( $principal['principal-id'] ) ) {
                return (string) $principal['principal-id'];
            }
        }
    }

    return new WP_Error( 'adobeconnect_principal_not_found', __( 'Unable to locate the Adobe Connect principal ID for the provided login.', 'advanced-form-integration' ) );
}
