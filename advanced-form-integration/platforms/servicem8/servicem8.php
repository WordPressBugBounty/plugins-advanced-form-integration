<?php

/**
 * ServiceM8 — Create Client & Job via /company.json and /job.json.
 *
 * Auth: X-API-Key header. Updates use POST /company/{uuid}.json.
 *
 * @link https://developer.servicem8.com/docs/rest-overview
 * @link https://developer.servicem8.com/docs/authentication
 * @link https://developer.servicem8.com/reference/createjobs
 */

add_filter( 'adfoin_action_providers', 'adfoin_servicem8_actions', 10, 1 );

function adfoin_servicem8_actions( $actions ) {
    $actions['servicem8'] = array(
        'title' => __( 'ServiceM8', 'advanced-form-integration' ),
        'tasks' => array(
            'create_job' => __( 'Create Client & Job', 'advanced-form-integration' ),
        ),
    );

    return $actions;
}

add_filter( 'adfoin_settings_tabs', 'adfoin_servicem8_settings_tab', 10, 1 );

function adfoin_servicem8_settings_tab( $providers ) {
    $providers['servicem8'] = __( 'ServiceM8', 'advanced-form-integration' );

    return $providers;
}

add_action( 'adfoin_settings_view', 'adfoin_servicem8_settings_view', 10, 1 );

function adfoin_servicem8_settings_view( $current_tab ) {
    if ( 'servicem8' !== $current_tab ) {
        return;
    }

    $title = __( 'ServiceM8', 'advanced-form-integration' );
    $key   = 'servicem8';

    // Single API Key field. We keep the underlying storage key as
    // `username` so credentials saved by older versions of the plugin
    // (which exposed username + password) continue to work — the
    // request helper reads `api_key` first, then `username`, so existing
    // tokens never need to be re-pasted.
    $arguments = wp_json_encode( array(
        'platform' => $key,
        'fields'   => array(
            array(
                'key'    => 'api_key',
                'label'  => __( 'API Key', 'advanced-form-integration' ),
                'hidden' => true,
            ),
        ),
    ) );

    $instructions = sprintf(
        '<ol>
            <li>%1$s</li>
            <li>%2$s</li>
            <li>%3$s</li>
            <li>%4$s</li>
        </ol>
        <p>%5$s</p>',
        esc_html__( 'Sign in to ServiceM8 and open Settings → API Keys.', 'advanced-form-integration' ),
        esc_html__( 'Click "New API Key", give it a name (e.g. "Advanced Form Integration"), and copy the generated key — ServiceM8 only shows it once.', 'advanced-form-integration' ),
        esc_html__( 'Paste the key in the field below and click Save & Authenticate.', 'advanced-form-integration' ),
        esc_html__( 'You can save multiple ServiceM8 accounts — each integration action lets you pick which one to push submissions to.', 'advanced-form-integration' ),
        esc_html__( 'AFI sends API requests to https://api.servicem8.com/api_1.0/ with the X-API-Key header.', 'advanced-form-integration' )
    );

    echo adfoin_platform_settings_template( $title, $key, $arguments, $instructions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'adfoin_action_fields', 'adfoin_servicem8_action_fields' );

function adfoin_servicem8_action_fields() {
    ?>
    <script type="text/template" id="servicem8-action-template">
        <table class="form-table" v-if="action.task == 'create_job'">
            <tr>
                <th scope="row"><?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?></th>
                <td>
                    <div class="afi-spinner" v-bind:class="{'is-active': fieldsLoading}"></div>
                </td>
            </tr>

            <tr class="alternate">
                <td scope="row-title">
                    <label><?php esc_html_e( 'ServiceM8 Account', 'advanced-form-integration' ); ?></label>
                </td>
                <td>
                    <select name="fieldData[credId]" v-model="fielddata.credId">
                        <option value=""><?php esc_html_e( 'Select Account...', 'advanced-form-integration' ); ?></option>
                        <?php adfoin_servicem8_credentials_list(); ?>
                    </select>
                </td>
            </tr>

            <editable-field v-for="field in fields"
                v-bind:key="field.value"
                v-bind:field="field"
                v-bind:trigger="trigger"
                v-bind:action="action"
                v-bind:fielddata="fielddata"></editable-field>
            <?php adfoin_pro_feature_notice( 'create_job', 'ServiceM8 [PRO]', 'WooCommerce autofill and attachments' ); ?>

            <tr class="alternate">
                <th scope="row"><?php esc_html_e( 'Need WooCommerce auto-fill or attachments?', 'advanced-form-integration' ); ?></th>
                <td>
                    <p><?php printf( __( 'Upgrade to <a href="%s" target="_blank" rel="noopener">AFI Pro</a> to map WooCommerce billing fields onto the client automatically, append cart items to the job description, and upload form attachments to the job.', 'advanced-form-integration' ), esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings-pricing' ) ) ); ?></p>
                </td>
            </tr>
        </table>
    </script>
    <?php
}

add_action( 'wp_ajax_adfoin_get_servicem8_credentials', 'adfoin_get_servicem8_credentials' );

function adfoin_get_servicem8_credentials() {
    adfoin_verify_nonce();

    wp_send_json_success( adfoin_read_credentials( 'servicem8' ) );
}

add_action( 'wp_ajax_adfoin_save_servicem8_credentials', 'adfoin_save_servicem8_credentials' );

function adfoin_save_servicem8_credentials() {
    adfoin_verify_nonce();

    if ( isset( $_POST['platform'] ) && 'servicem8' === $_POST['platform'] ) {
        $data = isset( $_POST['data'] ) ? adfoin_array_map_recursive( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
        adfoin_save_credentials( 'servicem8', $data );
    }

    wp_send_json_success();
}

add_action( 'wp_ajax_adfoin_get_servicem8_fields', 'adfoin_get_servicem8_fields' );

function adfoin_get_servicem8_fields() {
    adfoin_verify_nonce();

    // Free field set — every key matches the verbatim ServiceM8 API
    // field name so the send_data path can pass values straight
    // through without remapping. Pro overlays WC autofill on top.
    $fields = array(
        // Client (Company) — leave company_uuid blank to create, paste
        // a UUID to update.
        array( 'key' => 'company_uuid',     'value' => __( 'Existing Client UUID (skips create)', 'advanced-form-integration' ) ),
        array( 'key' => 'name',             'value' => __( 'Client Name (required when creating)', 'advanced-form-integration' ), 'description' => __( 'Required. ServiceM8 Company.name field — for individuals, pass "Firstname Lastname".', 'advanced-form-integration' ) ),
        array( 'key' => 'email',            'value' => __( 'Email', 'advanced-form-integration' ) ),
        array( 'key' => 'mobile',           'value' => __( 'Mobile', 'advanced-form-integration' ) ),
        array( 'key' => 'phone',            'value' => __( 'Phone', 'advanced-form-integration' ) ),
        array( 'key' => 'website',          'value' => __( 'Website', 'advanced-form-integration' ) ),
        array( 'key' => 'abn_number',       'value' => __( 'ABN / Tax Number', 'advanced-form-integration' ) ),
        array( 'key' => 'is_individual',    'value' => __( 'Is Individual (1 / 0)', 'advanced-form-integration' ), 'description' => __( '1 = individual, 0 = company. Defaults to 1 when only a single-word Name is supplied.', 'advanced-form-integration' ) ),

        // Company billing/postal address.
        array( 'key' => 'address',          'value' => __( 'Site Address (single line)', 'advanced-form-integration' ) ),
        array( 'key' => 'billing_address',  'value' => __( 'Billing Address (single line)', 'advanced-form-integration' ) ),
        array( 'key' => 'address_street',   'value' => __( 'Address Street', 'advanced-form-integration' ) ),
        array( 'key' => 'address_city',     'value' => __( 'Address City / Suburb', 'advanced-form-integration' ) ),
        array( 'key' => 'address_state',    'value' => __( 'Address State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'address_postcode', 'value' => __( 'Address Postcode', 'advanced-form-integration' ) ),
        array( 'key' => 'address_country',  'value' => __( 'Address Country', 'advanced-form-integration' ) ),

        // Job.
        array( 'key' => 'job_description',  'value' => __( 'Job Description', 'advanced-form-integration' ), 'type' => 'textarea', 'description' => __( 'The free-text body of the job. ServiceM8 has no separate "title" field.', 'advanced-form-integration' ) ),
        array( 'key' => 'status',           'value' => __( 'Job Status', 'advanced-form-integration' ), 'description' => __( 'Required. One of: Quote, Work Order, Unsuccessful, Completed. Defaults to Quote when blank.', 'advanced-form-integration' ) ),
        array( 'key' => 'job_address',      'value' => __( 'Job Address (single line)', 'advanced-form-integration' ) ),
        array( 'key' => 'geo_street',       'value' => __( 'Job Street', 'advanced-form-integration' ) ),
        array( 'key' => 'geo_city',         'value' => __( 'Job City / Suburb', 'advanced-form-integration' ) ),
        array( 'key' => 'geo_state',        'value' => __( 'Job State / Province', 'advanced-form-integration' ) ),
        array( 'key' => 'geo_postcode',     'value' => __( 'Job Postcode', 'advanced-form-integration' ) ),
        array( 'key' => 'geo_country',      'value' => __( 'Job Country', 'advanced-form-integration' ) ),
        array( 'key' => 'purchase_order_number', 'value' => __( 'Purchase Order Number', 'advanced-form-integration' ) ),
        array( 'key' => 'category_uuid',    'value' => __( 'Job Category UUID', 'advanced-form-integration' ) ),
        array( 'key' => 'queue_uuid',       'value' => __( 'Job Queue UUID', 'advanced-form-integration' ) ),
        array( 'key' => 'date',             'value' => __( 'Job Date (YYYY-MM-DD HH:MM:SS)', 'advanced-form-integration' ) ),
    );

    wp_send_json_success( $fields );
}

add_action( 'adfoin_servicem8_job_queue', 'adfoin_servicem8_job_queue', 10, 1 );

function adfoin_servicem8_job_queue( $data ) {
    adfoin_servicem8_process_job( $data['record'], $data['posted_data'] );
}

function adfoin_servicem8_process_job( $record, $posted_data ) {
    $record_data = json_decode( $record['data'], true );

    if ( adfoin_check_conditional_logic( $record_data['action_data']['cl'] ?? array(), $posted_data ) ) {
        return;
    }

    $field_data = isset( $record_data['field_data'] ) ? $record_data['field_data'] : array();
    $cred_id    = isset( $field_data['credId'] ) ? $field_data['credId'] : '';

    if ( ! $cred_id ) {
        return;
    }

    $credentials = adfoin_servicem8_get_credentials( $cred_id );
    if ( is_wp_error( $credentials ) ) {
        return;
    }

    // -- Resolve or create the client. --
    $company_uuid = isset( $field_data['company_uuid'] )
        ? trim( (string) adfoin_get_parsed_values( $field_data['company_uuid'], $posted_data ) )
        : '';

    $client_payload = adfoin_servicem8_collect_client_fields( $field_data, $posted_data );

    if ( $company_uuid ) {
        // Update existing client. Empty payload is OK — we still want
        // the existing client linked to the new job below.
        if ( ! empty( $client_payload ) ) {
            adfoin_servicem8_request( 'company/' . rawurlencode( $company_uuid ) . '.json', 'POST', $client_payload, $record, $credentials );
        }
    } elseif ( ! empty( $client_payload ) ) {
        if ( empty( $client_payload['name'] ) ) {
            adfoin_add_to_log( new WP_Error( 'servicem8_missing_name', __( 'Client Name is required to create a new ServiceM8 client.', 'advanced-form-integration' ) ), '', array(), $record );
            return;
        }
        $response = adfoin_servicem8_request( 'company.json', 'POST', $client_payload, $record, $credentials );
        if ( ! is_wp_error( $response ) ) {
            $company_uuid = adfoin_servicem8_extract_record_uuid( $response );
        }
    }

    if ( ! $company_uuid ) {
        adfoin_add_to_log( new WP_Error( 'servicem8_missing_company_uuid', __( 'Could not resolve a ServiceM8 client UUID — provide an Existing Client UUID or map at least a Client Name.', 'advanced-form-integration' ) ), '', array(), $record );
        return;
    }

    // -- Build the job payload. --
    $job_payload = adfoin_servicem8_collect_job_fields( $field_data, $posted_data );

    if ( empty( $job_payload ) && ! $company_uuid ) {
        return;
    }

    $job_payload['company_uuid'] = $company_uuid;

    // Status is required by the Job resource; default if user left it
    // blank so the call doesn't 400.
    $valid_statuses = array( 'Quote', 'Work Order', 'Unsuccessful', 'Completed' );
    if ( empty( $job_payload['status'] ) || ! in_array( $job_payload['status'], $valid_statuses, true ) ) {
        $job_payload['status'] = 'Quote';
    }

    adfoin_servicem8_request( 'job.json', 'POST', $job_payload, $record, $credentials );
}

function adfoin_servicem8_credentials_list() {
    $credentials = adfoin_read_credentials( 'servicem8' );

    foreach ( $credentials as $option ) {
        printf( '<option value="%s">%s</option>', esc_attr( $option['id'] ), esc_html( $option['title'] ) );
    }
}

function adfoin_servicem8_get_credentials( $cred_id ) {
    $credentials = adfoin_get_credentials_by_id( 'servicem8', $cred_id );

    if ( ! $credentials ) {
        return new WP_Error( 'missing_credentials', __( 'ServiceM8 credentials not found.', 'advanced-form-integration' ) );
    }

    return $credentials;
}

/**
 * ServiceM8 API request.
 *
 * @param string $endpoint
 * @param string $method
 * @param array  $data
 * @param array  $record
 * @param array  $credentials
 *
 * @return array|WP_Error
 */
if ( ! function_exists( 'adfoin_servicem8_request' ) ) :
function adfoin_servicem8_request( $endpoint, $method = 'GET', $data = array(), $record = array(), $credentials = array() ) {
    $api_key = '';
    if ( ! empty( $credentials['api_key'] ) ) {
        $api_key = trim( $credentials['api_key'] );
    } elseif ( ! empty( $credentials['username'] ) ) {
        // Fallback to legacy username field.
        $api_key = trim( $credentials['username'] );
    }

    if ( '' === $api_key ) {
        return new WP_Error( 'servicem8_missing_key', __( 'ServiceM8 API key is missing.', 'advanced-form-integration' ) );
    }

    $base_url = 'https://api.servicem8.com/api_1.0/';
    $url      = $base_url . ltrim( $endpoint, '/' );

    $args = array(
        'timeout' => 30,
        'method'  => strtoupper( $method ),
        'headers' => array(
            'X-API-Key'    => $api_key,
            'Accept'       => 'application/json',
        ),
    );

    if ( in_array( $args['method'], array( 'POST', 'PATCH' ), true ) ) {
        $args['headers']['Content-Type'] = 'application/json';
        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }
    }

    $response = wp_remote_request( esc_url_raw( $url ), $args );

    if ( $record ) {
        adfoin_add_to_log( $response, $url, $args, $record );
    }

    return $response;
}
endif;

/**
 * Pull the new record's UUID from the `x-record-uuid` response header
 * (the documented place — ServiceM8 doesn't return the UUID in the
 * body the way most REST APIs do). Falls back to body parsing for any
 * forward-compat scenario where Service M8 starts echoing it.
 */
function adfoin_servicem8_extract_record_uuid( $response ) {
    if ( is_wp_error( $response ) ) {
        return '';
    }

    $header_uuid = wp_remote_retrieve_header( $response, 'x-record-uuid' );
    if ( $header_uuid ) {
        return is_array( $header_uuid ) ? (string) reset( $header_uuid ) : (string) $header_uuid;
    }

    $body = wp_remote_retrieve_body( $response );
    if ( ! $body ) {
        return '';
    }
    $decoded = json_decode( $body, true );
    if ( isset( $decoded['uuid'] ) ) {
        return (string) $decoded['uuid'];
    }
    return '';
}

/**
 * Map editable-field values onto the ServiceM8 Company body using the
 * documented field names. The keys here are the verbatim API field
 * names — no remapping happens, so adding a new field is a one-line
 * change in both this whitelist and get_servicem8_fields().
 */
function adfoin_servicem8_collect_client_fields( $field_data, $posted_data ) {
    $passthrough = array(
        'name', 'email', 'mobile', 'phone', 'website', 'abn_number',
        'address', 'billing_address',
        'address_street', 'address_city', 'address_state',
        'address_postcode', 'address_country',
        'is_individual',
    );

    $payload = array();

    foreach ( $passthrough as $key ) {
        if ( ! isset( $field_data[ $key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $payload[ $key ] = is_string( $value ) ? trim( $value ) : $value;
    }

    if ( isset( $payload['is_individual'] ) ) {
        // ServiceM8 wants a 1/0 integer.
        $bool = strtolower( (string) $payload['is_individual'] );
        $payload['is_individual'] = in_array( $bool, array( '1', 'true', 'yes', 'on' ), true ) ? 1 : 0;
    }

    return $payload;
}

/**
 * Map editable-field values onto the ServiceM8 Job body. Same
 * pattern as the client mapper — keys are verbatim API names so
 * users can mix mapped fields with raw JSON merges in Pro without
 * any translation layer.
 */
function adfoin_servicem8_collect_job_fields( $field_data, $posted_data, $allowed_keys = array() ) {
    $passthrough = array(
        'job_description', 'status', 'job_address', 'work_done_description',
        'geo_street', 'geo_city', 'geo_state', 'geo_postcode', 'geo_country',
        'purchase_order_number', 'category_uuid', 'queue_uuid', 'date',
        'quote_date', 'work_order_date', 'completion_date',
    );

    $payload = array();

    foreach ( $passthrough as $key ) {
        if ( $allowed_keys && ! in_array( $key, $allowed_keys, true ) ) {
            continue;
        }
        if ( ! isset( $field_data[ $key ] ) ) {
            continue;
        }
        $value = adfoin_get_parsed_values( $field_data[ $key ], $posted_data );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $payload[ $key ] = is_string( $value ) ? trim( $value ) : $value;
    }

    return $payload;
}

/**
 * Pro helper: deep-merge user-supplied JSON into the payload.
 */
if ( ! function_exists( 'adfoin_servicem8_merge_recursive' ) ) :
function adfoin_servicem8_merge_recursive( array $base, array $additional ) {
    foreach ( $additional as $key => $value ) {
        if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
            $base[ $key ] = adfoin_servicem8_merge_recursive( $base[ $key ], $value );
        } else {
            $base[ $key ] = $value;
        }
    }
    return $base;
}
endif;
