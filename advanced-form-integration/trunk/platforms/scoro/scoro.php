<?php

add_filter( 'adfoin_action_providers', 'adfoin_scoro_actions', 10, 1 );

function adfoin_scoro_actions( $actions ) {
    $actions['scoro'] = array(
        'title' => __( 'Scoro CRM', 'advanced-form-integration' ),
        'tasks' => array(
            'create_contact' => __( 'Create Contact (Basic)', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_scoro_settings_tab', 10, 1 );

function adfoin_scoro_settings_tab( $providers ) {
    $providers['scoro'] = __( 'Scoro CRM', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_scoro_settings_view', 10, 1 );

function adfoin_scoro_settings_view( $current_tab ) {
    if ( 'scoro' !== $current_tab ) {
        return;
    }

    $title = __( 'Scoro CRM', 'advanced-form-integration' );
    $key   = 'scoro';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'companyAccount', 'label' => __( 'Company Account', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'userEmail', 'label' => __( 'User Email', 'advanced-form-integration' ), 'hidden' => false ),
            array( 'key' => 'apiKey', 'label' => __( 'API Key', 'advanced-form-integration' ), 'hidden' => true ),
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
                </ol>
            </li>
        </ol>
        <p>%8$s</p>
        <p>%9$s</p>',
        esc_html__( 'Generate an API key', 'advanced-form-integration' ),
        esc_html__( 'Log in to Scoro and navigate to Settings → Integrations → API & web services.', 'advanced-form-integration' ),
        esc_html__( 'Create a new API user or copy an existing user’s API key.', 'advanced-form-integration' ),
        esc_html__( 'Confirm your company subdomain (company account) and the user email tied to the key.', 'advanced-form-integration' ),
        esc_html__( 'Store the credentials', 'advanced-form-integration' ),
        esc_html__( 'Enter the company account (e.g. myworkspace), user email, and API key above, then click “Save & Authenticate”.', 'advanced-form-integration' ),
        esc_html__( 'Repeat to connect multiple Scoro accounts or users.', 'advanced-form-integration' ),
        esc_html__( 'AFI authenticates using HTTP Basic (email:API key) and the X-Company-Account header against https://api.scoro.com/api/v2/.', 'advanced-form-integration' ),
        esc_html__( 'Upgrade to Scoro CRM [PRO] to create companies, schedule tasks, update contacts, and sync custom fields.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_scoro_action_fields' );

function adfoin_scoro_action_fields() {
    ?>
    <script type="text/template" id="scoro-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Scoro Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_scoro_credentials_list(); ?>
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
                <th scope="row"><?php esc_html_e( 'Need companies or tasks?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">Scoro CRM [PRO]</a> to create companies, schedule tasks, apply tags, and push custom fields.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_scoro_credentials', 'adfoin_get_scoro_credentials' );

function adfoin_get_scoro_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'scoro' ) );
}

add_action( 'wp_ajax_adfoin_save_scoro_credentials', 'adfoin_save_scoro_credentials' );

function adfoin_save_scoro_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'scoro' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'scoro', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_scoro_fields', 'adfoin_get_scoro_fields' );

function adfoin_get_scoro_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'first_name', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'last_name', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile', 'value' => __( 'Mobile', 'advanced-form-integration' ) ),
        array( 'key' => 'position', 'value' => __( 'Job Title', 'advanced-form-integration' ) ),
        array( 'key' => 'company_name', 'value' => __( 'Company Name', 'advanced-form-integration' ) ),
        array( 'key' => 'tags', 'value' => __( 'Tags (comma separated)', 'advanced-form-integration' ) ),
        array( 'key' => 'contactJson', 'value' => __( 'Extra Fields (JSON)', 'advanced-form-integration' ), 'description' => __( 'Optional JSON object merged into the contact payload (custom fields, addresses, etc.).', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_scoro_job_queue', 'adfoin_scoro_job_queue', 10, 1 );

function adfoin_scoro_job_queue( $data ) {
    adfoin_scoro_send_contact( $data['record'], $data['posted_data'] );
}

function adfoin_scoro_send_contact( $record, $posted_data ) {
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

    $payload = adfoin_scoro_prepare_contact_payload( $fields );

    adfoin_scoro_request( 'contacts', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_scoro_request' ) ) :
function adfoin_scoro_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'scoro', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Scoro credentials not found.', 'advanced-form-integration' ) );
    }

    $company_account = isset( $credentials['companyAccount'] ) ? trim( $credentials['companyAccount'] ) : '';
    $user_email      = isset( $credentials['userEmail'] ) ? trim( $credentials['userEmail'] ) : '';
    $api_key         = isset( $credentials['apiKey'] ) ? trim( $credentials['apiKey'] ) : '';

    if ( ! $company_account || ! $user_email || ! $api_key ) {
        return new WP_Error( 'missing_auth', __( 'Scoro company account, user email, or API key missing.', 'advanced-form-integration' ) );
    }

    $url = 'https://api.scoro.com/api/v2/' . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'       => 'application/json',
            'Accept'             => 'application/json',
            'Authorization'      => 'Basic ' . base64_encode( $user_email . ':' . $api_key ),
            'X-Company-Account'  => $company_account,
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

if ( ! function_exists( 'adfoin_scoro_prepare_contact_payload' ) ) :
function adfoin_scoro_prepare_contact_payload( $fields ) {
    $contact = array(
        'type' => 'person',
    );

    $map = array(
        'first_name'   => 'first_name',
        'last_name'    => 'last_name',
        'email'        => 'email',
        'phone'        => 'phone',
        'mobile'       => 'mobile',
        'position'     => 'position',
        'company_name' => 'company_name',
    );

    foreach ( $map as $field_key => $api_key ) {
        if ( isset( $fields[ $field_key ] ) && '' !== $fields[ $field_key ] ) {
            $contact[ $api_key ] = $fields[ $field_key ];
        }
    }

    if ( isset( $fields['tags'] ) ) {
        $tags = adfoin_scoro_normalize_tags( $fields['tags'] );

        if ( ! empty( $tags ) ) {
            $contact['tags'] = $tags;
        }
    }

    if ( isset( $fields['contactJson'] ) ) {
        $extra = adfoin_scoro_parse_json_field( $fields['contactJson'] );

        if ( ! empty( $extra ) ) {
            $contact = array_merge( $contact, $extra );
        }
    }

    return array(
        'contact' => $contact,
    );
}
endif;

if ( ! function_exists( 'adfoin_scoro_parse_json_field' ) ) :
function adfoin_scoro_parse_json_field( $value ) {
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

if ( ! function_exists( 'adfoin_scoro_normalize_tags' ) ) :
function adfoin_scoro_normalize_tags( $value ) {
    if ( is_array( $value ) ) {
        $tags = array_map( 'trim', $value );
    } else {
        $tags = array_map( 'trim', explode( ',', (string) $value ) );
    }

    $tags = array_filter( $tags );

    return array_values( array_unique( $tags ) );
}
endif;

function adfoin_scoro_credentials_list() {
    foreach ( adfoin_read_credentials( 'scoro' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
