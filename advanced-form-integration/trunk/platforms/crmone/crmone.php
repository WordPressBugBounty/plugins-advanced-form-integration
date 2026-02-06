<?php

add_filter( 'adfoin_action_providers', 'adfoin_crmone_actions', 10, 1 );

function adfoin_crmone_actions( $actions ) {
    $actions['crmone'] = array(
        'title' => __( 'CRMOne', 'advanced-form-integration' ),
        'tasks' => array(
            'create_lead' => __( 'Create Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_crmone_settings_tab', 10, 1 );

function adfoin_crmone_settings_tab( $providers ) {
    $providers['crmone'] = __( 'CRMOne', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_crmone_settings_view', 10, 1 );

function adfoin_crmone_settings_view( $current_tab ) {
    if ( 'crmone' !== $current_tab ) {
        return;
    }

    $title = __( 'CRMOne', 'advanced-form-integration' );
    $key   = 'crmone';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'apiBase', 'label' => __( 'API Base URL', 'advanced-form-integration' ), 'hidden' => false ),
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
        esc_html__( 'Create an API key', 'advanced-form-integration' ),
        esc_html__( 'Log in to CRMOne and navigate to Settings → Integrations → API Keys.', 'advanced-form-integration' ),
        esc_html__( 'Generate a new key with permissions to create and update leads.', 'advanced-form-integration' ),
        esc_html__( 'Copy the key and note the base URL for your workspace (for example https://app.crmone.com/api).', 'advanced-form-integration' ),
        esc_html__( 'Store credentials in AFI', 'advanced-form-integration' ),
        esc_html__( 'Paste the API base URL and key into the fields above. Include the version segment if required by your account (e.g., /v1).', 'advanced-form-integration' ),
        esc_html__( 'Save the settings. You can add multiple CRMOne accounts by repeating this process.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends authenticated POST requests using the API key in the Authorization header (Bearer scheme).', 'advanced-form-integration' ),
        esc_html__( 'Need advanced objects? Contact support to extend this action or use CRMOne’s custom webhook matrix.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_crmone_action_fields' );

function adfoin_crmone_action_fields() {
    ?>
    <script type="text/template" id="crmone-action-template">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'CRMOne Credentials', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select credentials…', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_crmone_credentials_list(); ?>
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

add_action( 'wp_ajax_adfoin_get_crmone_credentials', 'adfoin_get_crmone_credentials' );

function adfoin_get_crmone_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'crmone' ) );
}

add_action( 'wp_ajax_adfoin_save_crmone_credentials', 'adfoin_save_crmone_credentials' );

function adfoin_save_crmone_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'crmone' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'crmone', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_crmone_fields', 'adfoin_get_crmone_fields' );

function adfoin_get_crmone_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'firstName', 'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName', 'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email', 'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone', 'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'company', 'value' => __( 'Company', 'advanced-form-integration' ) ),
        array( 'key' => 'source', 'value' => __( 'Lead Source', 'advanced-form-integration' ) ),
        array( 'key' => 'notes', 'value' => __( 'Notes', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_crmone_job_queue', 'adfoin_crmone_job_queue', 10, 1 );

function adfoin_crmone_job_queue( $data ) {
    adfoin_crmone_send_data( $data['record'], $data['posted_data'] );
}

function adfoin_crmone_send_data( $record, $posted_data ) {
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

    if ( empty( $fields['email'] ) ) {
        return;
    }

    adfoin_crmone_request( 'leads', 'POST', $fields, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_crmone_request' ) ) :
function adfoin_crmone_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'crmone', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'CRMOne credentials not found.', 'advanced-form-integration' ) );
    }

    $base = isset( $credentials['apiBase'] ) ? untrailingslashit( $credentials['apiBase'] ) : '';
    $key  = isset( $credentials['apiKey'] ) ? $credentials['apiKey'] : '';

    if ( ! $base || ! $key ) {
        return new WP_Error( 'missing_auth', __( 'CRMOne API base URL or key missing.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $base ) . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $key,
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

function adfoin_crmone_credentials_list() {
    foreach ( adfoin_read_credentials( 'crmone' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
