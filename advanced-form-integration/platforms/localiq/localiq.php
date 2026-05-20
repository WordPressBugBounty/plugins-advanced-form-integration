<?php

/**
 * LocaliQ — Submit Lead to a LocaliQ-provided intake URL.
 *
 * LocaliQ's public API (api.localiqservices.com) is read-only for
 * reporting. Lead intake is delivered via a per-account URL that
 * LocaliQ's onboarding team supplies — paste that URL + Bearer token
 * into the credential record.
 *
 * @link https://doc.api.localiq.com/
 */

add_filter( 'adfoin_action_providers', 'adfoin_localiq_actions', 10, 1 );

function adfoin_localiq_actions( $actions ) {
    $actions['localiq'] = array(
        'title' => __( 'LocaliQ', 'advanced-form-integration' ),
        'tasks' => array(
            'submit_lead' => __( 'Submit Lead', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_localiq_settings_tab', 10, 1 );

function adfoin_localiq_settings_tab( $providers ) {
    $providers['localiq'] = __( 'LocaliQ', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_localiq_settings_view', 10, 1 );

function adfoin_localiq_settings_view( $current_tab ) {
    if ( 'localiq' !== $current_tab ) {
        return;
    }

    $title = __( 'LocaliQ', 'advanced-form-integration' );
    $key   = 'localiq';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'         => 'endpoint',
                'label'       => __( 'Lead Intake URL', 'advanced-form-integration' ),
                'hidden'      => false,
                'placeholder' => 'https://…/leads',
            ),
            array(
                'key'    => 'apiKey',
                'label'  => __( 'Bearer Token', 'advanced-form-integration' ),
                'hidden' => true,
            ),
        ),
    ) );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'Ask your LocaliQ account team for a Lead Intake URL and Bearer token. LocaliQ\'s public API is read-only for reporting and does not expose a standard "submit lead" endpoint — intake URLs are provisioned per account.', 'advanced-form-integration' ),
        esc_html__( 'Paste the full URL (including any per-account path segments) and the Bearer token above.', 'advanced-form-integration' ),
        esc_html__( 'AFI POSTs JSON to that URL with Authorization: Bearer {token}.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_localiq_action_fields' );

function adfoin_localiq_action_fields() {
    ?>
    <script type="text/template" id="localiq-action-template">
        <table class="form-table" v-if="action.task == 'submit_lead'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td></td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'LocaliQ Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_localiq_credentials_list(); ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=localiq' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
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

add_action( 'wp_ajax_adfoin_get_localiq_credentials', 'adfoin_get_localiq_credentials' );

function adfoin_get_localiq_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    wp_send_json_success( adfoin_read_credentials( 'localiq' ) );
}

add_action( 'wp_ajax_adfoin_save_localiq_credentials', 'adfoin_save_localiq_credentials' );

function adfoin_save_localiq_credentials() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    if ( isset( $_POST['platform'] ) && 'localiq' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'localiq', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_localiq_fields', 'adfoin_get_localiq_fields' );

function adfoin_get_localiq_fields() {
    if ( ! adfoin_verify_nonce() ) {
        return;
    }

    $fields = array(
        array( 'key' => 'firstName',  'value' => __( 'First Name', 'advanced-form-integration' ) ),
        array( 'key' => 'lastName',   'value' => __( 'Last Name', 'advanced-form-integration' ) ),
        array( 'key' => 'email',      'value' => __( 'Email', 'advanced-form-integration' ), 'required' => true ),
        array( 'key' => 'phone',      'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'postalCode', 'value' => __( 'Postal Code', 'advanced-form-integration' ) ),
        array( 'key' => 'message',    'value' => __( 'Message', 'advanced-form-integration' ), 'type' => 'textarea' ),
        array( 'key' => 'source',     'value' => __( 'Source', 'advanced-form-integration' ), 'description' => __( 'Defaults to "AFI" when blank.', 'advanced-form-integration' ) ),
        array( 'key' => 'campaignId', 'value' => __( 'Campaign ID', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_localiq_job_queue', 'adfoin_localiq_job_queue', 10, 1 );

function adfoin_localiq_job_queue( $data ) {
    adfoin_localiq_send_lead( $data['record'], $data['posted_data'] );
}

function adfoin_localiq_send_lead( $record, $posted_data ) {
    if ( 'submit_lead' !== ( $record['task'] ?? '' ) ) {
        return;
    }

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

    $payload = array(
        'contact' => array_filter( array(
            'firstName'  => $fields['firstName']  ?? '',
            'lastName'   => $fields['lastName']   ?? '',
            'email'      => $fields['email'],
            'phone'      => $fields['phone']      ?? '',
            'postalCode' => $fields['postalCode'] ?? '',
        ) ),
        'message' => $fields['message'] ?? '',
        'source'  => $fields['source']  ?? 'AFI',
    );

    if ( ! empty( $fields['campaignId'] ) ) {
        $payload['campaignId'] = $fields['campaignId'];
    }

    adfoin_localiq_request( $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_localiq_request' ) ) :
/**
 * POST a JSON payload to the configured LocaliQ intake URL.
 *
 * @return array|WP_Error
 */
function adfoin_localiq_request( $data, $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'localiq', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'LocaliQ credentials not found.', 'advanced-form-integration' ) );
    }

    $endpoint = isset( $credentials['endpoint'] ) ? trim( $credentials['endpoint'] ) : '';
    $token    = isset( $credentials['apiKey'] )   ? trim( $credentials['apiKey'] )   : '';

    if ( ! $endpoint || ! $token ) {
        return new WP_Error( 'missing_auth', __( 'LocaliQ intake URL or Bearer token missing.', 'advanced-form-integration' ) );
    }

    $args = array(
        'timeout' => 30,
        'method'  => 'POST',
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ),
        'body'    => wp_json_encode( $data ),
    );

    $response = wp_remote_request( esc_url_raw( $endpoint ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $endpoint, $args, $record );
    }

    return $response;
}
endif;

function adfoin_localiq_credentials_list() {
    foreach ( adfoin_read_credentials( 'localiq' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
