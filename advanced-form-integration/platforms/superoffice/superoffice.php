<?php

add_filter( 'adfoin_action_providers', 'adfoin_superoffice_actions', 10, 1 );

function adfoin_superoffice_actions( $actions ) {
    $actions['superoffice'] = array(
        'title' => __( 'SuperOffice CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_superoffice_settings_tab', 10, 1 );

function adfoin_superoffice_settings_tab( $providers ) {
    $providers['superoffice'] = __( 'SuperOffice CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_superoffice_settings_view', 10, 1 );

function adfoin_superoffice_settings_view( $current_tab ) {
    if ( 'superoffice' !== $current_tab ) {
        return;
    }

    $title = __( 'SuperOffice CRM', 'advanced-form-integration' );
    $key   = 'superoffice';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'restBaseUrl', 'label' => __( 'REST Base URL', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'accessToken', 'label' => __( 'Access Token', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'appToken', 'label' => __( 'App Token (Optional)', 'advanced-form-integration' ), 'hidden' => true ),
            array( 'key' => 'contextIdentifier', 'label' => __( 'Context Identifier (Optional)', 'advanced-form-integration' ), 'hidden' => false ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                    <li>%4$s</li>
                </ol>
            </li>
            <li><strong>%5$s</strong>
                <ol>
                    <li>%6$s</li>
                    <li>%7$s</li>
                    <li>%8$s</li>
                </ol>
            </li>
        </ol>
        <p>%9$s</p>
        <p>%10$s</p>',
        esc_html__( 'Create an integration user', 'advanced-form-integration' ),
        esc_html__( 'Open SuperOffice Admin to register an application with REST API access.', 'advanced-form-integration' ),
        esc_html__( 'Generate a system user or OAuth access token with contact permissions.', 'advanced-form-integration' ),
        esc_html__( 'Copy the App Token if your environment requires the SO-AppToken header.', 'advanced-form-integration' ),
        esc_html__( 'Connect to AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the tenant REST base URL, for example https://sod.superoffice.com/api/v1/.', 'advanced-form-integration' ),
        esc_html__( 'Enter the bearer access token and optional App Token or Context Identifier, then save.', 'advanced-form-integration' ),
        esc_html__( 'Choose the saved credentials when you build an integration and map the fields you need.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends Authorization, SO-AppToken, and SO-ContextIdentifier headers with every request.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to SuperOffice CRM [PRO] to sync people, sales, and user-defined fields automatically.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_superoffice_action_fields' );

function adfoin_superoffice_action_fields() {
    ?>
    <script type="text/template" id="superoffice-action-template">
        <table class="form-table">
            <tr v-if="action.task == 'create_contact'">
                <th scope="row">
                    <?php esc_attr_e( 'Map Fields', 'advanced-form-integration' ); ?>
                </th>
                <td scope="row">
                    <div class="spinner" v-bind:class="{'is-active': fieldsLoading}" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>
                </td>
            </tr>

            <tr class="alternate" v-if="action.task == 'create_contact'">
                <td scope="row-title">
                    <label><?php esc_html_e( 'SuperOffice Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_superoffice_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate" v-if="action.task == 'create_contact'">
                <th scope="row"><?php esc_html_e( 'Need people or sales?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Unlock <a href="%s" target="_blank" rel="noopener">SuperOffice CRM [PRO]</a> to sync people, sales opportunities, and user-defined fields.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_superoffice_credentials', 'adfoin_get_superoffice_credentials' );

function adfoin_get_superoffice_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'superoffice' ) );
}

add_action( 'wp_ajax_adfoin_save_superoffice_credentials', 'adfoin_save_superoffice_credentials' );

function adfoin_save_superoffice_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'superoffice' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'superoffice', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_superoffice_fields', 'adfoin_get_superoffice_fields' );

function adfoin_get_superoffice_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'Name', 'value' => __( 'Company Name', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'Department', 'value' => __( 'Department', 'advanced-form-integration' ) ),
        array( 'key' => 'CategoryId', 'value' => __( 'Category ID', 'advanced-form-integration' ) ),
        array( 'key' => 'BusinessId', 'value' => __( 'Business ID', 'advanced-form-integration' ) ),
        array( 'key' => 'Number1', 'value' => __( 'Phone (Number1)', 'advanced-form-integration' ) ),
        array( 'key' => 'Number2', 'value' => __( 'Phone (Number2)', 'advanced-form-integration' ) ),
        array( 'key' => 'UrlAddress', 'value' => __( 'Website URL', 'advanced-form-integration' ) ),
        array( 'key' => 'Emails[0].Value', 'value' => __( 'Primary Email', 'advanced-form-integration' ) ),
        array( 'key' => 'Emails[0].Description', 'value' => __( 'Email Description', 'advanced-form-integration' ) ),
        array( 'key' => 'Phones[0].Value', 'value' => __( 'Primary Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'Phones[0].Description', 'value' => __( 'Phone Description', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Address1', 'value' => __( 'Address Line 1', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Address2', 'value' => __( 'Address Line 2', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.City', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Zipcode', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'PostalAddress.Country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'Description', 'value' => __( 'Description', 'advanced-form-integration' ), 'type' => 'textarea' ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_superoffice_job_queue', 'adfoin_superoffice_job_queue', 10, 1 );

function adfoin_superoffice_job_queue( $data ) {
    adfoin_superoffice_send_contact( $data['record'], $data['posted_data'] );
}

function adfoin_superoffice_send_contact( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_superoffice_get_credentials( $cred_id );

    if ( is_wp_error( $credentials ) ) {
        return;
    }

    $mapped_fields = array();

    foreach ( $field_data as $key => $value ) {
        if ( 'credId' === $key ) {
            continue;
        }

        $parsed = adfoin_get_parsed_values( $value, $posted_data );

        if ( '' === $parsed || null === $parsed ) {
            continue;
        }

        $mapped_fields[ $key ] = $parsed;
    }

    if ( empty( $mapped_fields['Name'] ) ) {
        return;
    }

    $payload = adfoin_superoffice_build_payload( $mapped_fields );

    if ( empty( $payload ) ) {
        return;
    }

    adfoin_superoffice_request( 'Contact', 'POST', $payload, $record, $credentials );
}

if ( ! function_exists( 'adfoin_superoffice_credentials_list' ) ) :
function adfoin_superoffice_credentials_list() {
    $credentials = adfoin_read_credentials( 'superoffice' );

    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
endif;

if ( ! function_exists( 'adfoin_superoffice_get_credentials' ) ) :
function adfoin_superoffice_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'superoffice', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_request' ) ) :
function adfoin_superoffice_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = array() ) {
    if ( empty( $credentials ) ) {
        return new WP_Error( 'missing_credentials', __( 'SuperOffice credentials missing.', 'advanced-form-integration' ) );
    }

    $base_url = isset( $credentials['restBaseUrl'] ) ? trim( $credentials['restBaseUrl'] ) : '';

    if ( '' === $base_url ) {
        return new WP_Error( 'missing_base_url', __( 'SuperOffice REST base URL is not set.', 'advanced-form-integration' ) );
    }

    $base_url = trailingslashit( $base_url );
    $url      = $base_url . ltrim( $endpoint, '/' );

    $headers = array(
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    );

    if ( ! empty( $credentials['accessToken'] ) ) {
        $headers['Authorization'] = 'Bearer ' . $credentials['accessToken'];
    }

    if ( ! empty( $credentials['appToken'] ) ) {
        $headers['SO-AppToken'] = $credentials['appToken'];
    }

    if ( ! empty( $credentials['contextIdentifier'] ) ) {
        $headers['SO-ContextIdentifier'] = $credentials['contextIdentifier'];
    }

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => $headers,
    );

    if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
        $args['body'] = wp_json_encode( $data );
    }

    $response = wp_remote_request( $url, $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_build_payload' ) ) :
function adfoin_superoffice_build_payload( $fields ) {
    $payload = array();

    foreach ( $fields as $path => $value ) {
        adfoin_superoffice_assign_path( $payload, $path, adfoin_superoffice_normalize_value( $path, $value ) );
    }

    return $payload;
}
endif;

if ( ! function_exists( 'adfoin_superoffice_assign_path' ) ) :
function adfoin_superoffice_assign_path( array &$target, $path, $value ) {
    if ( '' === $path ) {
        return;
    }

    $segments = explode( '.', $path );
    $last     = count( $segments ) - 1;
    $ref      =& $target;

    foreach ( $segments as $index => $segment ) {
        if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $segment, $matches ) ) {
            $key   = $matches[1];
            $array_index = (int) $matches[2];

            if ( ! isset( $ref[ $key ] ) || ! is_array( $ref[ $key ] ) ) {
                $ref[ $key ] = array();
            }

            if ( ! isset( $ref[ $key ][ $array_index ] ) || ! is_array( $ref[ $key ][ $array_index ] ) ) {
                $ref[ $key ][ $array_index ] = array();
            }

            if ( $index === $last ) {
                $ref[ $key ][ $array_index ] = $value;
            } else {
                $ref =& $ref[ $key ][ $array_index ];
            }

            continue;
        }

        if ( $index === $last ) {
            $ref[ $segment ] = $value;
        } else {
            if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
                $ref[ $segment ] = array();
            }

            $ref =& $ref[ $segment ];
        }
    }
}
endif;

if ( ! function_exists( 'adfoin_superoffice_normalize_value' ) ) :
function adfoin_superoffice_normalize_value( $path, $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    $trimmed = is_string( $value ) ? trim( $value ) : $value;

    $last_segment = $path;

    if ( false !== strpos( $path, '.' ) ) {
        $parts        = explode( '.', $path );
        $last_segment = end( $parts );
    }

    if ( preg_match( '/^([^\[\]]+)\[(\d+)\]$/', $last_segment, $matches ) ) {
        $last_segment = $matches[1];
    }

    $int_fields   = array( 'CategoryId', 'BusinessId', 'AssociateId', 'OwnerContactId', 'NumberOfEmployees', 'ContactId', 'PersonId', 'CountryId' );
    $float_fields = array( 'Amount', 'WeightedAmount' );
    $bool_fields  = array( 'HasConsent', 'ConsentGiven', 'ConsentObtained', 'Active', 'Done' );

    if ( in_array( $last_segment, $int_fields, true ) && '' !== $trimmed ) {
        return (int) $trimmed;
    }

    if ( in_array( $last_segment, $float_fields, true ) && '' !== $trimmed ) {
        return (float) $trimmed;
    }

    if ( in_array( $last_segment, $bool_fields, true ) ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        $lower = strtolower( (string) $trimmed );
        return in_array( $lower, array( '1', 'true', 'yes', 'on' ), true );
    }

    return $value;
}
endif;
