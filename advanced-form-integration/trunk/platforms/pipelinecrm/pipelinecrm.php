<?php

add_filter( 'adfoin_action_providers', 'adfoin_pipelinecrm_actions', 10, 1 );

function adfoin_pipelinecrm_actions( $actions ) {
    $actions['pipelinecrm'] = array(
        'title' => __( 'Pipeline CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_person' => __( 'Create Person (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_pipelinecrm_settings_tab', 10, 1 );

function adfoin_pipelinecrm_settings_tab( $providers ) {
    $providers['pipelinecrm'] = __( 'Pipeline CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_pipelinecrm_settings_view', 10, 1 );

function adfoin_pipelinecrm_settings_view( $current_tab ) {
    if ( 'pipelinecrm' !== $current_tab ) {
        return;
    }

    $title = __( 'Pipeline CRM', 'advanced-form-integration' );
    $key   = 'pipelinecrm';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'userEmail', 'label' => __( 'User Email', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'apiToken', 'label' => __( 'API Token', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li><strong>%1$s</strong>
                <ol>
                    <li>%2$s</li>
                    <li>%3$s</li>
                </ol>
            </li>
            <li><strong>%4$s</strong>
                <ol>
                    <li>%5$s</li>
                    <li>%6$s</li>
                    <li>%7$s</li>
                </ol>
            </li>
        </ol>
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Create an API token', 'advanced-form-integration' ),
        esc_html__( 'Log in to Pipeline CRM and open Account Settings → API to generate a personal API token.', 'advanced-form-integration' ),
        esc_html__( 'Note the email address associated with the token. Both the email and token are required on each request.', 'advanced-form-integration' ),
        esc_html__( 'Connect to AFI', 'advanced-form-integration' ),
        esc_html__( 'Enter the user email and API token in the fields above, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Repeat the process to store multiple Pipeline CRM accounts if needed.', 'advanced-form-integration' ),
        esc_html__( 'When mapping actions, choose the desired credentials from the dropdown.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends requests to https://api.pipelinecrm.com/api/v3 guarded by X-API-Token and X-User-Email headers.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Pipeline CRM [PRO] to manage deals, assign owners, push tags, and sync custom fields.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_pipelinecrm_action_fields' );

function adfoin_pipelinecrm_action_fields() {
    ?>
    <script type="text/template" id="pipelinecrm-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Pipeline CRM Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_pipelinecrm_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need deals or custom fields?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Pipeline CRM [PRO]</a> to assign owners, push tags, and create deals or notes.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_pipelinecrm_credentials', 'adfoin_get_pipelinecrm_credentials' );

function adfoin_get_pipelinecrm_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'pipelinecrm' ) );
}

add_action( 'wp_ajax_adfoin_save_pipelinecrm_credentials', 'adfoin_save_pipelinecrm_credentials' );

function adfoin_save_pipelinecrm_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'pipelinecrm' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'pipelinecrm', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_pipelinecrm_fields', 'adfoin_get_pipelinecrm_fields' );

function adfoin_get_pipelinecrm_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => false ),
        array( 'key' => 'mobile_phone', 'value' => __( 'Mobile Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'work_phone', 'value' => __( 'Work Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'title', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'website', 'value' => __( 'Website', 'advanced-form-integration' ) ),
        array( 'key' => 'address', 'value' => __( 'Street Address', 'advanced-form-integration' ) ),
        array( 'key' => 'city', 'value' => __( 'City', 'advanced-form-integration' ) ),
        array( 'key' => 'state', 'value' => __( 'State / Region', 'advanced-form-integration' ) ),
        array( 'key' => 'postal_code', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'country', 'value' => __( 'Country', 'advanced-form-integration' ) ),
        array( 'key' => 'source', 'value' => __( 'Lead Source', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_pipelinecrm_job_queue', 'adfoin_pipelinecrm_job_queue', 10, 1 );

function adfoin_pipelinecrm_job_queue( $data ) {
    adfoin_pipelinecrm_send_person( $data['record'], $data['posted_data'] );
}

function adfoin_pipelinecrm_send_person( $record, $posted_data ) {
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

    if ( empty( $fields['first_name'] ) && empty( $fields['last_name'] ) && empty( $fields['email'] ) ) {
        return;
    }

    $payload = adfoin_pipelinecrm_prepare_person_payload( $fields );

    adfoin_pipelinecrm_request( 'persons', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_pipelinecrm_request' ) ) :
function adfoin_pipelinecrm_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'pipelinecrm', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Pipeline CRM credentials not found.', 'advanced-form-integration' ) );
    }

    $user_email = isset( $credentials['userEmail'] ) ? $credentials['userEmail'] : '';
    $api_token  = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( ! $user_email || ! $api_token ) {
        return new WP_Error( 'missing_auth', __( 'Pipeline CRM email or API token missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.pipelinecrm.com/api/v3/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'X-API-Token'   => $api_token,
            'X-User-Email'  => $user_email,
        ),
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

if ( ! function_exists( 'adfoin_pipelinecrm_prepare_person_payload' ) ) :
function adfoin_pipelinecrm_prepare_person_payload( $fields ) {
    $person = array();

    $map = array(
        'first_name'   => 'first_name',
        'last_name'    => 'last_name',
        'email'        => 'email',
        'mobile_phone' => 'mobile_phone',
        'work_phone'   => 'work_phone',
        'title'        => 'title',
        'company'      => 'company_name',
        'website'      => 'website',
        'address'      => 'address',
        'city'         => 'city',
        'state'        => 'state',
        'postal_code'  => 'postal_code',
        'country'      => 'country',
        'source'       => 'source',
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( isset( $fields[ $field_key ] ) && '' !== $fields[ $field_key ] ) {
            $person[ $api_key ] = $fields[ $field_key ];
        }
    }

    if ( isset( $fields['owner_id'] ) && '' !== $fields['owner_id'] ) {
        $person['owner_id'] = (int) $fields['owner_id'];
    }

    if ( isset( $fields['status'] ) && '' !== $fields['status'] ) {
        $person['status'] = $fields['status'];
    }

    if ( isset( $fields['tags'] ) ) {
        $tags = adfoin_pipelinecrm_normalize_tags( $fields['tags'] );

        if ( ! empty( $tags ) ) {
            $person['tags'] = $tags;
        }
    }

    if ( isset( $fields['customFieldsJson'] ) ) {
        $custom_fields = adfoin_pipelinecrm_parse_json_field( $fields['customFieldsJson'] );

        if ( ! empty( $custom_fields ) ) {
            $person['custom_fields'] = $custom_fields;
        }
    }

    return array(
        'person' => $person,
    );
}
endif;

if ( ! function_exists( 'adfoin_pipelinecrm_parse_json_field' ) ) :
function adfoin_pipelinecrm_parse_json_field( $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    if ( ! is_string( $value ) || '' === trim( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
        return array();
    }

    return $decoded;
}
endif;

if ( ! function_exists( 'adfoin_pipelinecrm_normalize_tags' ) ) :
function adfoin_pipelinecrm_normalize_tags( $value ) {
    if ( is_array( $value ) ) {
        $tags = array_map( 'trim', $value );
    } else {
        $tags = array_map( 'trim', explode( ',', (string) $value ) );
    }

    $tags = array_filter( $tags );

    return array_values( array_unique( $tags ) );
}
endif;

function adfoin_pipelinecrm_credentials_list() {
    foreach ( adfoin_read_credentials( 'pipelinecrm' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
