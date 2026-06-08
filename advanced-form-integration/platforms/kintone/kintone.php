<?php

/**
 * Kintone — Create Record via POST /k/v1/record.json.
 *
 * Auth: X-Cybozu-API-Token header. Field codes are discovered per app
 * via GET /k/v1/app/form/fields.json once credId + appId are set.
 *
 * @link https://kintone.dev/en/docs/kintone/rest-api/
 */

add_filter( 'adfoin_action_providers', 'adfoin_kintone_actions', 10, 1 );

function adfoin_kintone_actions( $actions ) {
    $actions['kintone'] = array(
        'title' => __( 'Kintone', 'advanced-form-integration' ),
        'tasks' => array(
            'create_record' => __( 'Create Record', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_kintone_settings_tab', 10, 1 );

function adfoin_kintone_settings_tab( $providers ) {
    $providers['kintone'] = __( 'Kintone', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_kintone_settings_view', 10, 1 );

function adfoin_kintone_settings_view( $current_tab ) {
    if ( 'kintone' !== $current_tab ) {
        return;
    }

    $title = __( 'Kintone', 'advanced-form-integration' );
    $key   = 'kintone';

    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array( 'key' => 'subdomain', 'label' => __( 'Kintone Subdomain', 'advanced-form-integration' ), 'hidden' => false, 'placeholder' => 'mycompany' ),
            array( 'key' => 'baseUrl',   'label' => __( 'Custom Base URL (optional)', 'advanced-form-integration' ), 'hidden' => false, 'placeholder' => 'https://mycompany.cybozu.com' ),
            array( 'key' => 'apiToken',  'label' => __( 'API Token', 'advanced-form-integration' ), 'hidden' => true ),
        ),
    ) );

    $instructions = sprintf(
        '<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
        esc_html__( 'In your Kintone app: Settings → Users & Privileges → API Token. Generate a token with the "Add records" permission.', 'advanced-form-integration' ),
        esc_html__( 'Subdomain is the part before .kintone.com (e.g. mycompany). For cybozu.com or custom hosts, supply the full Custom Base URL instead.', 'advanced-form-integration' ),
        esc_html__( 'Each saved credential is per-app: generate a separate token (and AFI credential) for each Kintone app you want to write to.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_kintone_action_fields' );

function adfoin_kintone_action_fields() {
    ?>
    <script type="text/template" id="kintone-action-template">
        <table class="form-table" v-if="action.task == 'create_record'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Kintone Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_kintone_credentials_list(); ?>
                    </select>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=kintone' ) ); ?>" target="_blank" style="margin-left: 10px; text-decoration: none; vertical-align: middle;">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span> <?php esc_html_e( 'Manage Accounts', 'advanced-form-integration' ); ?>
                    </a>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'App ID', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <input type="text" name="fieldData[appId]" v-model="fielddata.appId" class="regular-text" required="required" @change="loadFields">
                    <p class="description"><?php esc_html_e( 'Numeric App ID from your Kintone app URL or App settings.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'Record JSON (advanced)', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <textarea name="fieldData[recordJson]" v-model="fielddata.recordJson" rows="4" class="large-text code"
                              placeholder='{"FieldCode":{"value":"…"}}'></textarea>
                    <p class="description"><?php esc_html_e( 'Optional. Raw record body, merged with the mapped fields above. Use for subtables, multi-select arrays, or any shape the basic mapper does not cover.', 'advanced-form-integration' ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_kintone_credentials', 'adfoin_get_kintone_credentials' );

function adfoin_get_kintone_credentials() {
    adfoin_verify_nonce();

    wp_send_json_success( adfoin_read_credentials( 'kintone' ) );
}

add_action( 'wp_ajax_adfoin_save_kintone_credentials', 'adfoin_save_kintone_credentials' );

function adfoin_save_kintone_credentials() {
    adfoin_verify_nonce();

    if ( isset( $_POST['platform'] ) && 'kintone' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'kintone', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_kintone_fields', 'adfoin_get_kintone_fields' );

/**
 * Returns the Kintone app's field schema when credId + appId are provided.
 * Falls back to an empty list when either is missing — the editor still
 * surfaces the App ID input and the recordJson textarea via the template.
 */
function adfoin_get_kintone_fields() {
    adfoin_verify_nonce();

    $cred_id = isset( $_POST['credId'] ) ? sanitize_text_field( wp_unslash( $_POST['credId'] ) ) : '';
    $app_id  = isset( $_POST['appId'] )  ? sanitize_text_field( wp_unslash( $_POST['appId'] ) )  : '';

    if ( ! $cred_id || ! $app_id ) {
        wp_send_json_success( array() );
    }

    $response = adfoin_kintone_request( 'k/v1/app/form/fields.json?app=' . rawurlencode( $app_id ), 'GET', array(), array(), $cred_id );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        wp_send_json_success( array() );
    }

    $body   = json_decode( wp_remote_retrieve_body( $response ), true );
    $fields = array();

    // Kintone returns properties keyed by field code with `type`, `label`,
    // and per-type metadata. Skip the always-system / read-only field
    // types — writing to them errors.
    $skip_types = array(
        'RECORD_NUMBER', 'CREATOR', 'CREATED_TIME', 'MODIFIER', 'UPDATED_TIME',
        'CATEGORY', 'STATUS', 'STATUS_ASSIGNEE', 'CALC', 'GROUP', 'REFERENCE_TABLE',
    );

    if ( isset( $body['properties'] ) && is_array( $body['properties'] ) ) {
        foreach ( $body['properties'] as $code => $prop ) {
            $type = isset( $prop['type'] ) ? (string) $prop['type'] : '';
            if ( in_array( $type, $skip_types, true ) ) {
                continue;
            }
            $label = isset( $prop['label'] ) && $prop['label'] !== '' ? $prop['label'] : $code;
            $fields[] = array(
                'key'         => $code,
                'value'       => sprintf( '%s (%s)', $label, $type ),
                'description' => isset( $prop['required'] ) && $prop['required'] ? __( 'Required', 'advanced-form-integration' ) : '',
                'required'    => ! empty( $prop['required'] ),
            );
        }
    }

    wp_send_json_success( $fields );
}

add_action( 'adfoin_kintone_job_queue', 'adfoin_kintone_job_queue', 10, 1 );

function adfoin_kintone_job_queue( $data ) {
    adfoin_kintone_send_record( $data['record'], $data['posted_data'] );
}

function adfoin_kintone_send_record( $record, $posted_data ) {
    if ( 'create_record' !== ( $record['task'] ?? '' ) ) {
        return;
    }

    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $data    = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id = isset( $data['credId'] ) ? $data['credId'] : '';
    $app_id  = isset( $data['appId'] )  ? trim( (string) $data['appId'] )  : '';

    if ( ! $cred_id || ! $app_id ) {
        adfoin_add_to_log( new WP_Error( 'kintone_missing_required', __( 'Kintone account or App ID not selected.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    $control_keys = array( 'credId', 'appId', 'recordJson' );

    $record_body = array();

    if ( ! empty( $data['recordJson'] ) ) {
        $decoded = json_decode( (string) $data['recordJson'], true );
        if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
            $record_body = $decoded;
        }
    }

    // Wrap each mapped value in Kintone's required `{value: ...}` shape.
    // Subtables / multi-selects / user-pickers need different shapes —
    // those should be supplied via the Record JSON escape hatch above.
    foreach ( $data as $field_code => $value ) {
        if ( in_array( $field_code, $control_keys, true ) ) {
            continue;
        }
        $parsed = adfoin_get_parsed_values( $value, $posted_data );
        if ( '' === $parsed || null === $parsed ) {
            continue;
        }
        $record_body[ $field_code ] = array( 'value' => $parsed );
    }

    if ( empty( $record_body ) ) {
        return;
    }

    $payload = array(
        'app'    => (int) $app_id,
        'record' => $record_body,
    );

    adfoin_kintone_request( 'k/v1/record.json', 'POST', $payload, $record, $cred_id );
}

if ( ! function_exists( 'adfoin_kintone_request' ) ) :
/**
 * Kintone REST API request.
 *
 * @return array|WP_Error
 */
function adfoin_kintone_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $cred_id = '' ) {
    $credentials = adfoin_get_credentials_by_id( 'kintone', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'Kintone credentials not found.', 'advanced-form-integration' ) );
    }

    $base  = adfoin_kintone_get_base_url( $credentials );
    $token = isset( $credentials['apiToken'] ) ? $credentials['apiToken'] : '';

    if ( ! $base || ! $token ) {
        return new WP_Error( 'missing_auth', __( 'Kintone base URL or API token missing.', 'advanced-form-integration' ) );
    }

    $url = trailingslashit( $base ) . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => $method,
        'headers' => array(
            'Content-Type'       => 'application/json',
            'X-Cybozu-API-Token' => $token,
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

function adfoin_kintone_get_base_url( $credentials ) {
    if ( ! empty( $credentials['baseUrl'] ) ) {
        return untrailingslashit( $credentials['baseUrl'] );
    }

    if ( empty( $credentials['subdomain'] ) ) {
        return '';
    }

    return 'https://' . trim( $credentials['subdomain'] ) . '.kintone.com';
}

function adfoin_kintone_credentials_list() {
    foreach ( adfoin_read_credentials( 'kintone' ) as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}
